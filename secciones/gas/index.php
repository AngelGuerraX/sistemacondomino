<?php
include("../../bd.php");
$thetitle = "GAS";
include("../../templates/header.php");

// Incluir la calculadora central de balances
include '../pagos/actualizar_balance.php';

// 1. INICIALIZACIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
$idcondominio = $_SESSION['idcondominio'];
$id_usuario_registro = $_SESSION['usuario'];

// Variables de fecha
$mes_actual_num = date('n');
$anio_actual = date('Y');
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_actual_espanol = $meses[$mes_actual_num - 1];

// Obtener precio del galón
$sentencia_precio = $conexion->prepare("SELECT precio_galon FROM tbl_configuracion_gas WHERE id_condominio = :id");
$sentencia_precio->execute([':id' => $idcondominio]);
$config_gas = $sentencia_precio->fetch(PDO::FETCH_ASSOC);
$precio_galon = $config_gas ? $config_gas['precio_galon'] : 147.60;

// =================================================================================
// 2. LOGICA DE ELIMINACIÓN
// =================================================================================
if (isset($_GET['eliminar_gas'])) {
   $id_eliminar = $_GET['eliminar_gas'];

   try {
      $conexion->beginTransaction();

      $stmt_info = $conexion->prepare("SELECT id_apto FROM tbl_gas WHERE id = :id AND id_condominio = :id_cond");
      $stmt_info->execute([':id' => $id_eliminar, ':id_cond' => $idcondominio]);
      $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

      if ($info) {
         $stmt_del = $conexion->prepare("DELETE FROM tbl_gas WHERE id = :id");
         $stmt_del->execute([':id' => $id_eliminar]);

         actualizarBalanceApto($info['id_apto'], $idcondominio);

         $conexion->commit();
         $mensaje_gas = "✅ Registro eliminado y balances recalculados.";
      }
   } catch (Exception $e) {
      $conexion->rollBack();
      $mensaje_gas = "❌ Error al eliminar: " . $e->getMessage();
   }
}

// =================================================================================
// 3. LOGICA DE REGISTRO MASIVO (TABLA GRANDE)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['accion'])) {
   $mes = $_POST['el_mes'];
   $anio = $_POST['el_anio'];
   $fecha_registro = date('Y-m-d H:i:s');

   try {
      $conexion->beginTransaction();

      foreach ($_POST['lectura_actual'] as $id_apto => $lectura_actual) {
         if ($lectura_actual !== '' && is_numeric($lectura_actual)) {

            $lectura_anterior = $_POST['lectura_anterior'][$id_apto];
            $consumo_galones = floatval($lectura_actual) - floatval($lectura_anterior);

            if ($consumo_galones >= 0) {
               $consumo_m3 = $consumo_galones * 1.2; // Factor automatico solo en masivo
               $total_gas = $consumo_galones * $precio_galon;
               $id_condominio_apto = $_POST['id_condominio'][$id_apto];

               $sql_insert = "INSERT INTO tbl_gas 
                        (id_apto, id_condominio, id_usuario_registro, lectura_anterior, lectura_actual, consumo_galones, consumo_m3, precio_galon, total_gas, mes, anio, fecha_registro, estado)
                        VALUES 
                        (:id_apto, :id_cond, :user, :la, :lac, :cg, :cm3, :pg, :total, :mes, :anio, :fecha, 'Pendiente')
                        ON DUPLICATE KEY UPDATE 
                        lectura_actual = VALUES(lectura_actual),
                        consumo_galones = VALUES(consumo_galones),
                        consumo_m3 = VALUES(consumo_m3),
                        total_gas = VALUES(total_gas),
                        precio_galon = VALUES(precio_galon)";

               $stmt_ins = $conexion->prepare($sql_insert);
               $stmt_ins->execute([
                  ':id_apto' => $id_apto,
                  ':id_cond' => $id_condominio_apto,
                  ':user' => $id_usuario_registro,
                  ':la' => $lectura_anterior,
                  ':lac' => $lectura_actual,
                  ':cg' => $consumo_galones,
                  ':cm3' => $consumo_m3,
                  ':pg' => $precio_galon,
                  ':total' => $total_gas,
                  ':mes' => $mes,
                  ':anio' => $anio,
                  ':fecha' => $fecha_registro
               ]);

               actualizarBalanceApto($id_apto, $idcondominio);
            }
         }
      }

      $conexion->commit();
      $mensaje_gas = "✅ Lecturas masivas guardadas correctamente.";
      echo "<script>window.location.href = '?mensaje=" . urlencode($mensaje_gas) . "';</script>";
      exit;
   } catch (Exception $e) {
      $conexion->rollBack();
      $mensaje_gas = "❌ Error: " . $e->getMessage();
   }
}

// =================================================================================
// 4. LOGICA DE REGISTRO MANUAL INDIVIDUAL (DESDE MODAL)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'registro_manual_gas') {
   try {
      $conexion->beginTransaction();

      $id_apto = $_POST['manual_id_apto'];
      // Buscar ID Condominio del apto
      $stmt_c = $conexion->prepare("SELECT id_condominio FROM tbl_aptos WHERE id = :id");
      $stmt_c->execute([':id' => $id_apto]);
      $id_cond_apto = $stmt_c->fetchColumn();

      // Recibimos los valores DIRECTOS del formulario (Lo que escribiste)
      $lectura_ant = floatval($_POST['manual_lec_ant']);
      $lectura_act = floatval($_POST['manual_lec_act']);
      $precio_aplicado = floatval($_POST['manual_precio']);

      $consumo_gal = floatval($_POST['manual_consumo']); // Manual
      $consumo_m3  = floatval($_POST['manual_m3']);      // Manual
      $total_cobrar = floatval($_POST['manual_total']);  // Manual

      if ($consumo_gal < 0) {
         throw new Exception("El consumo no puede ser negativo.");
      }

      $sql_insert = "INSERT INTO tbl_gas 
            (id_apto, id_condominio, id_usuario_registro, lectura_anterior, lectura_actual, consumo_galones, consumo_m3, precio_galon, total_gas, mes, anio, fecha_registro, estado)
            VALUES 
            (:id_apto, :id_cond, :user, :la, :lac, :cg, :cm3, :pg, :total, :mes, :anio, :fecha, 'Pendiente')";

      $stmt = $conexion->prepare($sql_insert);
      $stmt->execute([
         ':id_apto' => $id_apto,
         ':id_cond' => $id_cond_apto,
         ':user'    => $id_usuario_registro,
         ':la'      => $lectura_ant,
         ':lac'     => $lectura_act,
         ':cg'      => $consumo_gal,
         ':cm3'     => $consumo_m3,
         ':pg'      => $precio_aplicado,
         ':total'   => $total_cobrar,
         ':mes'     => $_POST['manual_mes'],
         ':anio'    => $_POST['manual_anio'],
         ':fecha'   => date('Y-m-d H:i:s')
      ]);

      actualizarBalanceApto($id_apto, $idcondominio);

      $conexion->commit();
      $mensaje_gas = "✅ Registro manual agregado correctamente.";
      echo "<script>window.location.href = '?mensaje=" . urlencode($mensaje_gas) . "';</script>";
      exit;
   } catch (Exception $e) {
      $conexion->rollBack();
      $mensaje_gas = "❌ Error manual: " . $e->getMessage();
   }
}

if (isset($_GET['mensaje'])) {
   $mensaje_gas = urldecode($_GET['mensaje']);
}

// =================================================================================
// 5. CONSULTAS PARA LA VISTA
// =================================================================================

// Lista de Apartamentos
$stmt_aptos = $conexion->prepare("
    SELECT a.id, a.apto, a.id_condominio, a.condominos, a.tiene_inquilino,
           i.nombre AS nombre_inquilino
    FROM tbl_aptos a
    LEFT JOIN tbl_inquilinos i ON a.id = i.id_apto AND i.activo = 1
    WHERE a.id_condominio = :id 
    ORDER BY a.apto ASC
");
$stmt_aptos->execute([':id' => $idcondominio]);
$lista_aptos = $stmt_aptos->fetchAll(PDO::FETCH_ASSOC);

// Últimas lecturas
$ultimas_lecturas = [];
foreach ($lista_aptos as $apto) {
   $stmt_lec = $conexion->prepare("SELECT lectura_actual FROM tbl_gas WHERE id_apto = :id ORDER BY fecha_registro DESC LIMIT 1");
   $stmt_lec->execute([':id' => $apto['id']]);
   $ultimas_lecturas[$apto['id']] = $stmt_lec->fetchColumn() ?: 0;
}

// Filtros Historial
$mes_mostrar = $_GET['mes_buscar'] ?? $mes_actual_espanol;
$anio_mostrar = $_GET['anio_buscar'] ?? $anio_actual;

// Historial
$stmt_hist = $conexion->prepare("
    SELECT g.*, a.apto, a.tiene_inquilino 
    FROM tbl_gas g 
    JOIN tbl_aptos a ON g.id_apto = a.id 
    WHERE g.mes = :mes AND g.anio = :anio AND g.id_condominio = :id
    ORDER BY g.fecha_registro DESC
");
$stmt_hist->execute([':mes' => $mes_mostrar, ':anio' => $anio_mostrar, ':id' => $idcondominio]);
$gas_registrados = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

$total_gas_mes = array_sum(array_column($gas_registrados, 'total_gas'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Registro de Gas</title>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <style>
      .calculado {
         background-color: #f8f9fa;
         font-weight: bold;
         color: #495057;
      }

      .inquilino-row {
         background-color: #e8f5e8;
      }

      .propietario-row {
         background-color: #fff3cd;
      }

      .table-responsive {
         max-height: 70vh;
      }

      .table th,
      .table td {
         vertical-align: middle;
         white-space: nowrap;
      }
   </style>
</head>

<body>

   <div class="container-fluid mt-4 px-4">

      <?php if (isset($mensaje_gas)): ?>
         <div class="alert alert-info text-center shadow-sm"><?= $mensaje_gas ?></div>
      <?php endif; ?>

      <div class="card shadow mb-5">
         <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📜 Historial de Lecturas</h5>
            <form method="get" class="d-flex gap-2">
               <select name="mes_buscar" class="form-select form-select-sm" style="width: auto;">
                  <?php foreach ($meses as $m) echo "<option value='$m' " . ($m == $mes_mostrar ? 'selected' : '') . ">$m</option>"; ?>
               </select>
               <select name="anio_buscar" class="form-select form-select-sm" style="width: auto;">
                  <?php for ($i = 2024; $i <= 2030; $i++) echo "<option value='$i' " . ($i == $anio_mostrar ? 'selected' : '') . ">$i</option>"; ?>
               </select>
               <button type="submit" class="btn btn-light btn-sm">🔍</button>
            </form>
         </div>
         <div class="card-body p-0">
            <div class="table-responsive">
               <table class="table table-striped mb-0 text-center">
                  <thead class="table-light">
                     <tr>
                        <th>Fecha</th>
                        <th>Apto</th>
                        <th>Lecturas (Ant / Act)</th>
                        <th>Consumo (Gal)</th>
                        <th>M3</th>
                        <th>Precio</th>
                        <th>Total</th>
                        <th></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($gas_registrados as $g): ?>
                        <tr>
                           <td><?= date('d/m/y H:i', strtotime($g['fecha_registro'])) ?></td>
                           <td class="fw-bold"><?= $g['apto'] ?></td>
                           <td><?= number_format($g['lectura_anterior'], 3) ?> / <?= number_format($g['lectura_actual'], 3) ?></td>
                           <td><?= number_format($g['consumo_galones'], 3) ?></td>
                           <td><?= number_format($g['consumo_m3'], 3) ?></td>
                           <td>$<?= number_format($g['precio_galon'], 2) ?></td>
                           <td class="text-success fw-bold">$<?= number_format($g['total_gas'], 2) ?></td>
                           <td class="text-end">
                              <a href="?eliminar_gas=<?= $g['id'] ?>&mes_buscar=<?= $mes_mostrar ?>&anio_buscar=<?= $anio_mostrar ?>"
                                 class="btn btn-sm btn-outline-danger"
                                 onclick="return confirm('¿Borrar este registro? Se recalcularán los saldos.')">🗑️</a>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                     <?php if (empty($gas_registrados)) echo "<tr><td colspan='8' class='text-center text-muted py-3'>No hay registros en este periodo</td></tr>"; ?>
                  </tbody>
                  <tfoot class="table-dark">
                     <tr>
                        <td colspan="6" class="text-end">Total Mes:</td>
                        <td colspan="2">$<?= number_format($total_gas_mes, 2) ?></td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         </div>
      </div>

      <div class="card shadow mb-4 border-primary">
         <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">🔥 Nuevo Registro de Gas</h4>

            <button type="button" class="btn btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalGasManual">
               🛠️ Registro Manual Individual
            </button>
         </div>

         <div class="card-body">
            <p class="text-center">Precio Galón Actual: <strong>$<?= number_format($precio_galon, 2) ?></strong></p>

            <form method="post" id="formGas">
               <div class="row mb-3">
                  <div class="col-md-6">
                     <label class="form-label fw-bold">Mes a facturar:</label>
                     <select class="form-select" name="el_mes" required>
                        <?php foreach ($meses as $idx => $m) echo "<option value='$m' " . ($idx + 1 == $mes_actual_num ? 'selected' : '') . ">$m</option>"; ?>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label fw-bold">Año:</label>
                     <select class="form-select" name="el_anio" required>
                        <?php for ($i = 2024; $i <= 2030; $i++) echo "<option value='$i' " . ($i == $anio_actual ? 'selected' : '') . ">$i</option>"; ?>
                     </select>
                  </div>
               </div>

               <div class="table-responsive">
                  <table class="table table-bordered align-middle">
                     <thead class="table-dark text-center">
                        <tr>
                           <th>Apto</th>
                           <th>Tipo</th>
                           <th>Lectura Anterior</th>
                           <th style="width: 150px;">Lectura Actual</th>
                           <th>Consumo (Gal)</th>
                           <th>M3 (x1.2)</th>
                           <th>Precio x Galón</th>
                           <th>Total Gas</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($lista_aptos as $apto):
                           $lec_ant = $ultimas_lecturas[$apto['id']];
                           $row_class = $apto['tiene_inquilino'] ? 'inquilino-row' : 'propietario-row';
                           $badge = $apto['tiene_inquilino'] ? '<span class="badge bg-success">Inquilino</span>' : '<span class="badge bg-warning text-dark">Propietario</span>';
                           $nombre = $apto['tiene_inquilino'] ? $apto['nombre_inquilino'] : $apto['condominos'];
                        ?>
                           <tr class="<?= $row_class ?>" data-id="<?= $apto['id'] ?>">
                              <td>
                                 <strong><?= $apto['apto'] ?></strong><br>
                                 <small class="text-muted"><?= $nombre ?></small>
                                 <input type="hidden" name="id_condominio[<?= $apto['id'] ?>]" value="<?= $apto['id_condominio'] ?>">
                              </td>
                              <td class="text-center"><?= $badge ?></td>

                              <td class="text-center">
                                 <?= number_format($lec_ant, 3) ?>
                                 <input type="hidden" name="lectura_anterior[<?= $apto['id'] ?>]" value="<?= $lec_ant ?>">
                              </td>

                              <td>
                                 <input type="number" step="0.001" class="form-control text-center lectura-input"
                                    name="lectura_actual[<?= $apto['id'] ?>]"
                                    data-anterior="<?= $lec_ant ?>"
                                    placeholder="0.000">
                              </td>

                              <td class="text-center fw-bold"><span class="consumo">0.000</span></td>
                              <td class="text-center text-muted"><span class="consumo-m3">0.000</span></td>
                              <td class="text-center">$<?= number_format($precio_galon, 2) ?></td>
                              <td class="text-end fw-bold text-success"><span class="total">$0.00</span></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>

               <div class="text-center mt-3">
                  <button type="submit" class="btn btn-success btn-lg px-5">💾 Guardar Lecturas Masivas</button>
               </div>
            </form>
         </div>
      </div>

   </div>

   <div class="modal fade" id="modalGasManual" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
               <h5 class="modal-title">🛠️ Registrar Gas Manualmente</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
               <input type="hidden" name="accion" value="registro_manual_gas">
               <div class="modal-body">

                  <div class="mb-3">
                     <label class="form-label fw-bold">Apartamento:</label>
                     <select class="form-select" name="manual_id_apto" id="manual_id_apto" required onchange="cargarLecturaAnterior()">
                        <option value="" selected disabled>Seleccione...</option>
                        <?php foreach ($lista_aptos as $apto): ?>
                           <option value="<?= $apto['id'] ?>" data-lectura="<?= $ultimas_lecturas[$apto['id']] ?>">
                              <?= $apto['apto'] ?> - <?= $apto['tiene_inquilino'] ? $apto['nombre_inquilino'] : $apto['condominos'] ?>
                           </option>
                        <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="row mb-3">
                     <div class="col-6">
                        <label class="form-label small">Mes</label>
                        <select name="manual_mes" class="form-select form-select-sm">
                           <?php foreach ($meses as $idx => $m) echo "<option value='$m' " . ($idx + 1 == $mes_actual_num ? 'selected' : '') . ">$m</option>"; ?>
                        </select>
                     </div>
                     <div class="col-6">
                        <label class="form-label small">Año</label>
                        <input type="number" name="manual_anio" class="form-control form-select-sm" value="<?= $anio_actual ?>">
                     </div>
                  </div>

                  <hr>

                  <div class="row mb-3">
                     <div class="col-6">
                        <label class="form-label fw-bold text-muted">Lectura Anterior</label>
                        <input type="number" step="0.001" name="manual_lec_ant" id="manual_lec_ant" class="form-control bg-light" required oninput="calcularManual()">
                     </div>
                     <div class="col-6">
                        <label class="form-label fw-bold text-primary">Lectura Actual</label>
                        <input type="number" step="0.001" name="manual_lec_act" id="manual_lec_act" class="form-control border-primary fw-bold" required oninput="calcularManual()">
                     </div>
                  </div>

                  <div class="mb-3">
                     <label class="form-label small">Precio por Galón</label>
                     <input type="number" step="0.01" name="manual_precio" id="manual_precio" class="form-control form-control-sm" value="<?= $precio_galon ?>" oninput="calcularManual()">
                  </div>

                  <div class="row mb-3">
                     <div class="col-md-4">
                        <label class="form-label fw-bold">Consumo (Gl):</label>
                        <input type="number" step="0.001" name="manual_consumo" id="manual_consumo" class="form-control fw-bold" placeholder="0.000">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">Consumo (M3):</label>
                        <input type="number" step="0.001" name="manual_m3" id="manual_m3" class="form-control fw-bold text-primary" placeholder="0.000">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Total a Cobrar:</label>
                        <div class="input-group">
                           <span class="input-group-text">$</span>
                           <input type="number" step="0.01" name="manual_total" id="manual_total" class="form-control fw-bold text-success" placeholder="0.00">
                        </div>
                     </div>
                  </div>

               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-dark" id="btn_guardar_manual" disabled>Guardar Registro Manual</button>
               </div>
            </form>
         </div>
      </div>
   </div>

   <script>
      const precioGlobal = <?= $precio_galon ?>;

      $(document).ready(function() {
         // Lógica Masiva
         $('.lectura-input').on('input', function() {
            let input = $(this);
            let fila = input.closest('tr');
            let actual = parseFloat(input.val());
            let anterior = parseFloat(input.data('anterior'));

            if (!isNaN(actual) && actual >= anterior) {
               let consumo = actual - anterior;
               let consumoM3 = consumo * 1.2;
               let total = consumo * precioGlobal;

               fila.find('.consumo').text(consumo.toFixed(3));
               fila.find('.consumo-m3').text(consumoM3.toFixed(3));
               fila.find('.total').text('$' + total.toLocaleString('en-US', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
               }));
               input.removeClass('is-invalid');
            } else {
               fila.find('.consumo').text('0.000');
               fila.find('.consumo-m3').text('0.000');
               fila.find('.total').text('$0.00');
               if (!isNaN(actual)) input.addClass('is-invalid');
            }
         });

         $('#formGas').on('submit', function(e) {
            let errores = false;
            $('.lectura-input').each(function() {
               let val = $(this).val();
               if (val !== '' && parseFloat(val) < parseFloat($(this).data('anterior'))) {
                  errores = true;
                  $(this).addClass('is-invalid');
               }
            });
            if (errores) {
               e.preventDefault();
               alert('⚠️ Hay lecturas menores a la anterior.');
            }
         });
      });

      // --- LÓGICA MANUAL INTELIGENTE ---
      function cargarLecturaAnterior() {
         let select = document.getElementById('manual_id_apto');
         let opcion = select.options[select.selectedIndex];
         let lecturaGuardada = parseFloat(opcion.getAttribute('data-lectura')) || 0;

         document.getElementById('manual_lec_ant').value = lecturaGuardada.toFixed(3);
         document.getElementById('manual_lec_act').value = '';
         document.getElementById('manual_consumo').value = '';
         document.getElementById('manual_m3').value = '';
         document.getElementById('manual_total').value = '';
         validarBoton();
      }

      // Esta función sugiere valores, pero NO bloquea
      function calcularManual() {
         let ant = parseFloat(document.getElementById('manual_lec_ant').value) || 0;
         let act = parseFloat(document.getElementById('manual_lec_act').value) || 0;
         let precio = parseFloat(document.getElementById('manual_precio').value) || 0;

         if (act > ant) {
            let consumo = act - ant;
            let m3 = consumo * 1.2;
            let total = consumo * precio;

            // Sugerir valores
            document.getElementById('manual_consumo').value = consumo.toFixed(3);
            document.getElementById('manual_m3').value = m3.toFixed(3);
            document.getElementById('manual_total').value = total.toFixed(2);
         }
         validarBoton();
      }

      function validarBoton() {
         let btn = document.getElementById('btn_guardar_manual');
         // Habilitar siempre que haya un apto seleccionado, dejando libertad al usuario
         if (document.getElementById('manual_id_apto').value !== "") {
            btn.disabled = false;
         } else {
            btn.disabled = true;
         }
      }
   </script>

   <?php include("../../templates/footer.php"); ?>