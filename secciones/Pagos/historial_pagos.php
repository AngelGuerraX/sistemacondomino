<?php
include("../../templates/header.php");
include("../../bd.php");

// 1. VALIDACIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

if (!isset($_GET['id_apto'])) {
  header("Location: index.php");
  exit;
}
$id_apto = $_GET['id_apto'];

include 'actualizar_balance.php';

// =================================================================================
// 2. LÓGICA DE ELIMINACIÓN DE PAGO (BACKEND)
// =================================================================================
if (isset($_GET['eliminar_pago']) || isset($_GET['eliminar_pago_inquilino'])) {

  $id_pago_borrar = isset($_GET['eliminar_pago']) ? $_GET['eliminar_pago'] : $_GET['eliminar_pago_inquilino'];
  $tipo_pagador_borrar = isset($_GET['eliminar_pago']) ? 'propietario' : 'inquilino';

  try {
    $conexion->beginTransaction();

    // A. Obtener detalles para saber qué deudas reversar
    $stmt_detalles = $conexion->prepare("SELECT * FROM tbl_pagos_detalle WHERE id_pago = :idp AND tipo_pagador = :tipo_p");
    $stmt_detalles->execute([':idp' => $id_pago_borrar, ':tipo_p' => $tipo_pagador_borrar]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

    // B. Reversar abonos en las tablas de deuda originales
    foreach ($detalles as $detalle) {
      $monto_revertir = floatval($detalle['monto']);
      $id_deuda = $detalle['id_deuda'];
      $tipo_deuda = $detalle['tipo_deuda'];

      if ($tipo_deuda == 'adelanto') continue;

      $tabla = '';
      // Definir tabla según el tipo
      if ($tipo_deuda == 'ticket') {
        $tabla = 'tbl_tickets';
      } elseif ($tipo_deuda == 'gas') {
        $tabla = 'tbl_gas';
      } elseif ($tipo_deuda == 'cuota') {
        $tabla = 'tbl_cuotas_extras';
      }

      // Ejecutar reversión
      if ($tabla) {
        // Restamos el abono y si queda en 0 (o casi 0), vuelve a estado Pendiente
        $sql_reversion = "UPDATE $tabla SET 
                                  abono = abono - :monto,
                                  estado = CASE WHEN (abono - :monto) <= 0.01 THEN 'Pendiente' ELSE 'Abonado' END
                                  WHERE id = :id";
        $conexion->prepare($sql_reversion)->execute([':monto' => $monto_revertir, ':id' => $id_deuda]);
      }
    }

    // C. Eliminar los registros
    // 1. Borrar detalle
    $conexion->prepare("DELETE FROM tbl_pagos_detalle WHERE id_pago = :idp AND tipo_pagador = :tipo_p")
      ->execute([':idp' => $id_pago_borrar, ':tipo_p' => $tipo_pagador_borrar]);

    // 2. Borrar pago principal
    $tbl_principal = ($tipo_pagador_borrar == 'propietario') ? 'tbl_pagos' : 'tbl_pagos_inquilinos';
    $pk_principal = ($tipo_pagador_borrar == 'propietario') ? 'id_pago' : 'id';

    $conexion->prepare("DELETE FROM $tbl_principal WHERE $pk_principal = :idp")
      ->execute([':idp' => $id_pago_borrar]);

    // D. Recalcular Balance
    actualizarBalanceApto($id_apto, $id_condominio);

    $conexion->commit();

    $_SESSION['mensaje'] = "✅ Pago eliminado y deudas restauradas correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
  } catch (Exception $e) {
    $conexion->rollBack();
    $_SESSION['mensaje'] = "❌ Error al eliminar: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
  }

  header("Location: historial_pagos.php?id_apto=" . $id_apto);
  exit;
}

// =================================================================================
// 3. CONSULTA DE DATOS PARA VISUALIZACIÓN
// =================================================================================

// Recalcular balance al entrar
actualizarBalanceApto($id_apto, $id_condominio);

// Datos del Apartamento
$stmt = $conexion->prepare("SELECT * FROM tbl_aptos WHERE id = :id AND id_condominio = :id_condominio");
$stmt->execute([':id' => $id_apto, ':id_condominio' => $id_condominio]);
$apto = $stmt->fetch(PDO::FETCH_ASSOC);

// A. Pagos PROPIETARIO
$sql_prop = "SELECT id_pago, monto, fecha_pago, forma_pago, mes_ingreso, anio_ingreso, usuario_registro, 'propietario' as tipo_entidad, '' as nombre_extra FROM tbl_pagos WHERE id_apto = :id_apto AND id_condominio = :id_cond";
$stmt_prop = $conexion->prepare($sql_prop);
$stmt_prop->execute([':id_apto' => $id_apto, ':id_cond' => $id_condominio]);
$pagos_prop = $stmt_prop->fetchAll(PDO::FETCH_ASSOC);

// B. Pagos INQUILINO
$sql_inq = "SELECT p.id as id_pago, p.monto, p.fecha_pago, p.forma_pago, p.mes_gas as mes_ingreso, p.anio_gas as anio_ingreso, p.usuario_registro, 'inquilino' as tipo_entidad, i.nombre as nombre_extra FROM tbl_pagos_inquilinos p INNER JOIN tbl_inquilinos i ON p.id_inquilino = i.id WHERE i.id_apto = :id_apto AND p.id_condominio = :id_cond";
$stmt_inq = $conexion->prepare($sql_inq);
$stmt_inq->execute([':id_apto' => $id_apto, ':id_cond' => $id_condominio]);
$pagos_inq = $stmt_inq->fetchAll(PDO::FETCH_ASSOC);

// C. Unir y Ordenar por fecha descendente
$historial_completo = array_merge($pagos_prop, $pagos_inq);
usort($historial_completo, function ($a, $b) {
  return strtotime($b['fecha_pago']) - strtotime($a['fecha_pago']);
});
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Historial de Pagos</title>
</head>

<body>

  <div class="container mt-4">

    <div class="d-flex justify-content-between bg-dark p-4 b-3 text-white align-items-center mb-3 border border-3 border-dark rounded">
      <div>
        <h2>📜 Historial Unificado</h2>
        <h4 class="text-secondary">APARTAMENTO: <strong><?php echo $apto['apto']; ?></strong></h4>
        <?php $clase_balance = $apto['balance'] >= 0 ? "text-success" : "text-danger"; ?>
        <h5 class="mt-2">Balance Total: <span class="<?php echo $clase_balance; ?>">RD$ <?php echo number_format($apto['balance'], 2); ?></span></h5>
      </div>
      <div>
        <a href="index.php" class="btn btn-danger">X</a>
        <a class="btn btn-light text-dark me-2" href="gestion_pagos.php?txID=<?php echo $apto['id'] ?>">Gestionar Pagos</a>
        <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#modalPDF">
          📄 Cuentas por cobrar (PDF)
        </button>
      </div>

      <div class="modal fade" id="modalPDF" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title text-dark">Generar Estado de Cuenta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="estado_cuenta.php" method="POST" target="_blank">
              <div class="modal-body">
                <input type="hidden" name="id_apto" value="<?php echo $apto['id']; ?>">
                <div class="mb-3">
                  <label class="form-label fw-bold text-dark">Nota al pie del reporte (Opcional):</label>
                  <textarea class="form-control" name="nota_reporte" rows="5" placeholder="Escribe aquí observaciones..."></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark">🖨️ Generar PDF</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
        <?php
        echo $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card shadow">
      <div class="card-header bg-dark text-white d-flex justify-content-between">
        <span class="mb-0">Transacciones (Propietario e Inquilino)</span>
        <small>Ordenado por fecha desc.</small>
      </div>
      <div class="card-body p-0">

        <?php if (empty($historial_completo)): ?>
          <div class="p-5 text-center text-muted">
            <h4>No hay movimientos registrados.</h4>
          </div>
        <?php else: ?>

          <div class="accordion" id="accordionPagos">
            <?php foreach ($historial_completo as $index => $pago):
              $id_unico = $pago['tipo_entidad'] . "_" . $pago['id_pago'];
              $fecha_formateada = date("d/m/Y", strtotime($pago['fecha_pago']));

              // Estilos según quien pagó
              $bg_header = ($pago['tipo_entidad'] == 'inquilino') ? "bg-warning bg-opacity-25" : "bg-light";
              $texto_tipo = ($pago['tipo_entidad'] == 'inquilino') ? "INQUILINO (" . $pago['nombre_extra'] . ")" : "PROPIETARIO";
              $clase_monto = ($pago['tipo_entidad'] == 'inquilino') ? "text-dark" : "text-primary";
            ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading_<?php echo $id_unico; ?>">
                  <button class="accordion-button collapsed <?php echo $bg_header; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $id_unico; ?>">
                    <div class="row w-100 align-items-center">
                      <div class="col-md-3">
                        <span class="fw-bold text-muted small">#<?php echo str_pad($pago['id_pago'], 5, "0", STR_PAD_LEFT); ?></span><br>
                        <span class="fw-bold small"><?php echo $texto_tipo; ?></span>
                      </div>
                      <div class="col-md-2 text-center"><small class="text-muted d-block">Fecha Pago</small><strong><?php echo $fecha_formateada; ?></strong></div>
                      <div class="col-md-3 text-center">
                        <?php if ($pago['mes_ingreso']): ?>
                          <small class="text-muted d-block">Periodo</small>
                          <span><?php echo $pago['mes_ingreso'] . " " . $pago['anio_ingreso']; ?></span>
                        <?php endif; ?>
                      </div>
                      <div class="col-md-3 text-end pe-4">
                        <h5 class="mb-0 fw-bold <?php echo $clase_monto; ?>">RD$ <?php echo number_format($pago['monto'], 2); ?></h5>
                        <small class="text-muted"><?php echo $pago['forma_pago']; ?></small>
                      </div>
                    </div>
                  </button>
                </h2>
                <div id="collapse_<?php echo $id_unico; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionPagos">
                  <div class="accordion-body bg-white border-top">

                    <h6 class="text-secondary border-bottom pb-2 small fw-bold">DESGLOSE DE CONCEPTOS:</h6>
                    <table class="table table-sm table-striped align-middle">
                      <thead>
                        <tr>
                          <th>Tipo</th>
                          <th>Descripción / Periodo</th>
                          <th class="text-center">Estado Pago</th>
                          <th class="text-end">Monto Aplicado</th>
                          <th class="text-center">Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        // CONSULTA: Traemos datos, montos originales y descripciones
                        $sql_det = "SELECT d.*, 
                                                            CASE 
                                                                WHEN d.tipo_deuda = 'ticket' THEN (SELECT (CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2))) FROM tbl_tickets WHERE id = d.id_deuda)
                                                                WHEN d.tipo_deuda = 'gas' THEN (SELECT (total_gas + mora) FROM tbl_gas WHERE id = d.id_deuda)
                                                                WHEN d.tipo_deuda = 'cuota' THEN (SELECT (monto + mora) FROM tbl_cuotas_extras WHERE id = d.id_deuda)
                                                                ELSE 0
                                                            END as monto_original_deuda,
                                                            CASE 
                                                                WHEN d.tipo_deuda = 'ticket' THEN (SELECT CONCAT('Mantenimiento ', mes, ' ', anio) FROM tbl_tickets WHERE id = d.id_deuda)
                                                                WHEN d.tipo_deuda = 'gas' THEN (SELECT CONCAT('Gas ', mes, ' ', anio) FROM tbl_gas WHERE id = d.id_deuda)
                                                                WHEN d.tipo_deuda = 'cuota' THEN (SELECT descripcion FROM tbl_cuotas_extras WHERE id = d.id_deuda)
                                                                WHEN d.tipo_deuda = 'adelanto' THEN 'Saldo a Favor Generado'
                                                            END as desc_extra
                                                            FROM tbl_pagos_detalle d
                                                            WHERE d.id_pago = :id_pago AND d.tipo_pagador = :tipo_pagador";

                        $stmt_det = $conexion->prepare($sql_det);
                        $stmt_det->execute([':id_pago' => $pago['id_pago'], ':tipo_pagador' => $pago['tipo_entidad']]);
                        $detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($detalles as $d):
                          $es_abono = false;
                          $etiqueta = "";
                          $botones_factura = "";

                          if ($d['tipo_deuda'] != 'adelanto') {
                            // Si se pagó menos del original (con tolerancia de 1 peso), es abono
                            if ($d['monto'] < ($d['monto_original_deuda'] - 1)) {
                              $es_abono = true;
                              $etiqueta = "<span class='badge bg-warning text-dark'>Abono Parcial</span>";
                            } else {
                              $etiqueta = "<span class='badge bg-success'>Pago Completo</span>";

                              // ---- GENERAR LINKS INDIVIDUALES ----
                              $url_ver = "";
                              $url_enviar = "";

                              // 1. Mantenimiento
                              if ($d['tipo_deuda'] == 'ticket') {
                                $parts = explode(' ', $d['desc_extra']); // "Mantenimiento Enero 2024"
                                $mes_f = $parts[1] ?? '';
                                $anio_f = $parts[2] ?? '';
                                $url_ver = "factura_mantenimiento.php?id_apto={$id_apto}&mes={$mes_f}&anio={$anio_f}";
                                $url_enviar = "enviar_factura_individual.php?tipo=ticket&id={$d['id_deuda']}&id_pago={$pago['id_pago']}";
                              }
                              // 2. Gas
                              elseif ($d['tipo_deuda'] == 'gas') {
                                $parts = explode(' ', $d['desc_extra']); // "Gas Enero 2024"
                                $mes_g = $parts[1] ?? '';
                                $anio_g = $parts[2] ?? '';
                                // Asumimos que factura_gas necesita mes/año también
                                $url_ver = "factura_gas.php?id_apto={$id_apto}&mes={$mes_g}&anio={$anio_g}";
                                $url_enviar = "enviar_factura_individual.php?tipo=gas&id={$d['id_deuda']}&id_pago={$pago['id_pago']}";
                              }
                              // 3. Cuota Extra
                              elseif ($d['tipo_deuda'] == 'cuota') {
                                $url_ver = "factura_cuota.php?id={$d['id_deuda']}";
                                $url_enviar = "enviar_factura_individual.php?tipo=cuota&id={$d['id_deuda']}&id_pago={$pago['id_pago']}";
                              }

                              // Renderizar botones si existe URL
                              if ($url_ver) {
                                $botones_factura = "
                                                                    <a href='$url_ver' target='_blank' class='btn btn-sm btn-outline-dark' title='Ver Factura PDF'><i class='fas fa-eye'></i></a>
                                                                    <a href='$url_enviar' class='btn btn-sm btn-outline-primary' title='Enviar PDF por Email' onclick=\"return confirm('¿Enviar esta factura específica por correo?');\"><i class='fas fa-envelope'></i></a>
                                                                ";
                              }
                            }
                          } else {
                            $etiqueta = "<span class='badge bg-info text-dark'>Crédito / Saldo Favor</span>";
                          }
                        ?>
                          <tr>
                            <td><strong class="text-uppercase"><?php echo $d['tipo_deuda']; ?></strong></td>
                            <td><?php echo $d['desc_extra']; ?></td>
                            <td class="text-center"><?php echo $etiqueta; ?></td>
                            <td class="text-end fw-bold">RD$ <?php echo number_format($d['monto'], 2); ?></td>
                            <td class="text-center"><?php echo $botones_factura; ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                      <div class="me-auto text-start">
                        <small class="text-muted">Registrado por: <strong><?php echo $pago['usuario_registro']; ?></strong></small>
                      </div>

                      <a href="ver_recibo.php?id_pago=<?php echo $pago['id_pago']; ?>&txID=<?php echo $id_apto; ?>&tipo=<?php echo $pago['tipo_entidad']; ?>" target="_blank" class="btn btn-dark">
                        🖨️ Recibo de Pago
                      </a>

                      <a href="enviar_paquete_facturas.php?id_pago=<?php echo $pago['id_pago']; ?>" class="btn btn-primary" onclick="return confirm('¿Enviar Recibo y todas las Facturas adjuntas por correo?');">
                        📧 Enviar Todo (Recibo + Facturas)
                      </a>

                      <?php
                      $url_eliminar = "historial_pagos.php?id_apto=$id_apto" . (($pago['tipo_entidad'] == 'propietario') ? "&eliminar_pago=" : "&eliminar_pago_inquilino=") . $pago['id_pago'];
                      ?>
                      <a href="<?php echo $url_eliminar; ?>" class="btn btn-danger" onclick="return confirm('⚠️ ¿ESTÁS SEGURO?\n\nAl eliminar este pago:\n1. Se reversará el dinero de las facturas.\n2. Si la deuda estaba pagada, volverá a estar pendiente.\n3. El balance se ajustará.\n\nEsta acción no se puede deshacer.');">
                        🗑️ Eliminar
                      </a>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include("../../templates/footer.php"); ?>
</body>

</html>