<?php

include("../../templates/header.php");
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];
$anio_actual = date('Y');
$anios = range($anio_actual - 5, $anio_actual + 1);

// =================================================================================
// 0. LÓGICA DE REPORTE DE ENVÍO MASIVO (NUEVO)
// =================================================================================
// Si venimos de enviar correos, aquí capturamos el resultado para mostrarlo
$reporte_envio = null;
if (isset($_SESSION['resultados_envio'])) {
   $reporte_envio = $_SESSION['resultados_envio'];
   unset($_SESSION['resultados_envio']); // Borramos la variable para que no salga siempre
}

// =================================================================================
// 1. LÓGICA: GUARDAR AUTOMÁTICO (CALCULADO)
// =================================================================================
if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_cierre') {
   try {
      $mes_guardar = $_POST['mes_guardar'];
      $anio_guardar = $_POST['anio_guardar'];

      $meses_nombres = [
         1 => 'Enero',
         2 => 'Febrero',
         3 => 'Marzo',
         4 => 'Abril',
         5 => 'Mayo',
         6 => 'Junio',
         7 => 'Julio',
         8 => 'Agosto',
         9 => 'Septiembre',
         10 => 'Octubre',
         11 => 'Noviembre',
         12 => 'Diciembre'
      ];
      $mes_nombre = $meses_nombres[$mes_guardar];

      // A. CALCULAR INGRESOS
      $total_ingresos = 0;
      $stmt1 = $conexion->prepare("SELECT COALESCE(SUM(monto),0) FROM tbl_pagos WHERE id_condominio=:id AND mes_ingreso=:mes AND anio_ingreso=:anio");
      $stmt1->execute([':id' => $idcondominio, ':mes' => $mes_nombre, ':anio' => $anio_guardar]);
      $total_ingresos += floatval($stmt1->fetchColumn());

      $stmt2 = $conexion->prepare("SELECT COALESCE(SUM(monto),0) FROM tbl_pagos_inquilinos WHERE id_condominio=:id AND mes_gas=:mes AND anio_gas=:anio");
      $stmt2->execute([':id' => $idcondominio, ':mes' => $mes_nombre, ':anio' => $anio_guardar]);
      $total_ingresos += floatval($stmt2->fetchColumn());

      // B. CALCULAR GASTOS
      $stmt_gastos = $conexion->prepare("SELECT COALESCE(SUM(monto),0) FROM tbl_gastos WHERE id_condominio=:id AND mes=:mes AND anio=:anio");
      $stmt_gastos->execute([':id' => $idcondominio, ':mes' => $mes_nombre, ':anio' => $anio_guardar]);
      $total_gastos = floatval($stmt_gastos->fetchColumn());

      // C. RESULTADOS
      $cierre_mes = $total_ingresos - $total_gastos;

      // Buscar anterior
      $ind_actual = array_search($mes_nombre, $meses_nombres);
      $ind_ant = ($mes_guardar == 1) ? 12 : $mes_guardar - 1;
      $anio_ant = ($mes_guardar == 1) ? $anio_guardar - 1 : $anio_guardar;
      $mes_ant_nom = $meses_nombres[$ind_ant];

      $stmt_prev = $conexion->prepare("SELECT resultado_actual FROM tbl_estado_resultado WHERE id_condominio=:id AND mes=:mes AND anio=:anio");
      $stmt_prev->execute([':id' => $idcondominio, ':mes' => $mes_ant_nom, ':anio' => $anio_ant]);
      $res_anterior = floatval($stmt_prev->fetchColumn());

      $resultado_actual = $cierre_mes + $res_anterior;

      // D. GUARDAR
      $check = $conexion->prepare("SELECT id FROM tbl_estado_resultado WHERE id_condominio=:id AND mes=:mes AND anio=:anio");
      $check->execute([':id' => $idcondominio, ':mes' => $mes_nombre, ':anio' => $anio_guardar]);
      $existe = $check->fetch();

      if ($existe) {
         $sql_upd = "UPDATE tbl_estado_resultado SET ingresos=:ing, gastos=:gas, cierre_mes=:cie, resultado_anterior=:ant, resultado_actual=:act WHERE id=:id";
         $conexion->prepare($sql_upd)->execute([':ing' => $total_ingresos, ':gas' => $total_gastos, ':cie' => $cierre_mes, ':ant' => $res_anterior, ':act' => $resultado_actual, ':id' => $existe['id']]);
         $mensaje = "✅ Cierre de $mes_nombre actualizado (Automático).";
      } else {
         $sql_ins = "INSERT INTO tbl_estado_resultado (ingresos, gastos, cierre_mes, resultado_anterior, resultado_actual, mes, anio, id_condominio) VALUES (:ing, :gas, :cie, :ant, :act, :mes, :anio, :id)";
         $conexion->prepare($sql_ins)->execute([':ing' => $total_ingresos, ':gas' => $total_gastos, ':cie' => $cierre_mes, ':ant' => $res_anterior, ':act' => $resultado_actual, ':mes' => $mes_nombre, ':anio' => $anio_guardar, ':id' => $idcondominio]);
         $mensaje = "✅ Cierre de $mes_nombre creado (Automático).";
      }
   } catch (Exception $e) {
      $error = "Error: " . $e->getMessage();
   }
}

// =================================================================================
// 2. LÓGICA: GUARDAR MANUAL (PARA EL PRIMER MES O CORRECCIONES)
// =================================================================================
if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_manual') {
   try {
      $mes_nombre = $_POST['mes_manual'];
      $anio_manual = $_POST['anio_manual'];

      $ingresos = floatval($_POST['m_ingresos']);
      $gastos = floatval($_POST['m_gastos']);
      $res_ant = floatval($_POST['m_anterior']);

      // Calculamos nosotros para asegurar consistencia
      $cierre = $ingresos - $gastos;
      $res_act = $cierre + $res_ant;

      // Verificar si existe para sobrescribir
      $check = $conexion->prepare("SELECT id FROM tbl_estado_resultado WHERE id_condominio=:id AND mes=:mes AND anio=:anio");
      $check->execute([':id' => $idcondominio, ':mes' => $mes_nombre, ':anio' => $anio_manual]);
      $existe = $check->fetch();

      if ($existe) {
         $sql = "UPDATE tbl_estado_resultado SET ingresos=:i, gastos=:g, cierre_mes=:c, resultado_anterior=:ra, resultado_actual=:ract WHERE id=:id";
         $conexion->prepare($sql)->execute([':i' => $ingresos, ':g' => $gastos, ':c' => $cierre, ':ra' => $res_ant, ':ract' => $res_act, ':id' => $existe['id']]);
         $mensaje = "✅ Registro manual de $mes_nombre actualizado.";
      } else {
         $sql = "INSERT INTO tbl_estado_resultado (ingresos, gastos, cierre_mes, resultado_anterior, resultado_actual, mes, anio, id_condominio) VALUES (:i, :g, :c, :ra, :ract, :m, :a, :id)";
         $conexion->prepare($sql)->execute([':i' => $ingresos, ':g' => $gastos, ':c' => $cierre, ':ra' => $res_ant, ':ract' => $res_act, ':m' => $mes_nombre, ':a' => $anio_manual, ':id' => $idcondominio]);
         $mensaje = "✅ Registro manual inicial de $mes_nombre creado.";
      }
   } catch (Exception $e) {
      $error = "Error Manual: " . $e->getMessage();
   }
}

// 3. ELIMINAR
if (isset($_GET['borrar_id'])) {
   $conexion->prepare("DELETE FROM tbl_estado_resultado WHERE id=?")->execute([$_GET['borrar_id']]);
   echo "<script>window.location.href='ver_estado_resultado.php';</script>";
   exit;
}

// 4. CONSULTAR HISTORIAL
$query = $conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id_condominio = :id ORDER BY anio DESC, id DESC");
$query->execute([':id' => $idcondominio]);
$historial = $query->fetchAll(PDO::FETCH_ASSOC);

function mesANumero($nombreMes)
{
   $meses = [
      'Enero' => 1,
      'Febrero' => 2,
      'Marzo' => 3,
      'Abril' => 4,
      'Mayo' => 5,
      'Junio' => 6,
      'Julio' => 7,
      'Agosto' => 8,
      'Septiembre' => 9,
      'Octubre' => 10,
      'Noviembre' => 11,
      'Diciembre' => 12
   ];
   return $meses[ucfirst(strtolower($nombreMes))] ?? 0;
}

usort($historial, function ($a, $b) {
   if ($a['anio'] == $b['anio']) {
      return mesANumero($b['mes']) - mesANumero($a['mes']);
   }
   return $b['anio'] - $a['anio'];
});
?>

<div class="container mt-4">

   <?php if ($reporte_envio): ?>
      <div class="card shadow mb-4 border-primary">
         <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📧 Resultados del Envío Masivo</h5>
            <button type="button" class="btn-close btn-close-white" onclick="this.closest('.card').remove()"></button>
         </div>
         <div class="card-body">
            <div class="alert alert-info py-2">
               <strong>Resumen:</strong> Se intentó enviar a <strong><?= count($reporte_envio) ?></strong> destinatarios.
            </div>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
               <table class="table table-sm table-bordered">
                  <thead class="table-light">
                     <tr>
                        <th>Destinatario</th>
                        <th>Rol</th>
                        <th class="text-center">Estado</th>
                        <th>Detalle Técnico</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($reporte_envio as $log): ?>
                        <tr class="<?= $log['estado'] == 'Enviado' ? 'table-success' : 'table-danger' ?>">
                           <td>
                              <strong><?= $log['nombre'] ?></strong><br>
                              <small><?= $log['email'] ?></small>
                           </td>
                           <td><?= $log['rol'] ?></td>
                           <td class="fw-bold text-center align-middle">
                              <?= $log['estado'] == 'Enviado' ? '<span class="text-success">✅ Enviado</span>' : '<span class="text-danger">❌ Error</span>' ?>
                           </td>
                           <td class="small text-muted align-middle"><?= $log['msg'] ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   <?php endif; ?>

   <?php if (isset($mensaje)): ?>
      <div class="alert alert-success alert-dismissible fade show"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
   <?php endif; ?>
   <?php if (isset($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
   <?php endif; ?>

   <div class="card shadow mb-4">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
         <h4 class="mb-0">📊 Estados de Resultado</h4>
         <div>
            <button type="button" class="btn btn-primary fw-bold me-2" data-bs-toggle="modal" data-bs-target="#modalManual">
               ➕ Carga Manual
            </button>
            <button type="button" class="btn btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalPDF">
               ⚙️ Generar Automático
            </button>
         </div>
      </div>
      <div class="card-body text-center py-3">
         <p class="mb-0 small text-muted">Use <strong>Carga Manual</strong> para el primer mes del sistema. Use <strong>Generar Automático</strong> para meses siguientes.</p>
      </div>
   </div>

   <div class="card shadow">
      <div class="card-header bg-light fw-bold">📜 Historial de Meses Cerrados</div>
      <div class="card-body p-0">
         <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 text-center">
               <thead class="table-dark">
                  <tr>
                     <th>Periodo</th>
                     <th>Ingresos</th>
                     <th>Gastos</th>
                     <th>Cierre Mes</th>
                     <th>Res. Anterior</th>
                     <th>Res. Actual</th>
                     <th width="20%">Acciones</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if (empty($historial)): ?>
                     <tr>
                        <td colspan="7" class="py-4 text-muted">No hay estados de resultado guardados.</td>
                     </tr>
                  <?php else: ?>
                     <?php foreach ($historial as $h): $mes_num = mesANumero($h['mes']); ?>
                        <tr>
                           <td class="fw-bold"><?= $h['mes'] . " " . $h['anio'] ?></td>
                           <td class="text-success">RD$ <?= number_format($h['ingresos'], 2) ?></td>
                           <td class="text-danger">RD$ <?= number_format($h['gastos'], 2) ?></td>
                           <td class="fw-bold">RD$ <?= number_format($h['cierre_mes'], 2) ?></td>
                           <td class="text-muted">RD$ <?= number_format($h['resultado_anterior'], 2) ?></td>
                           <td class="bg-light fw-bold text-primary">RD$ <?= number_format($h['resultado_actual'], 2) ?></td>
                           <td>
                              <div class="btn-group" role="group">
                                 <a href="generar_pdf_estado.php?mes=<?= $mes_num ?>&anio=<?= $h['anio'] ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                    📄
                                 </a>

                                 <a href="enviar_estado_masivo.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('¿Enviar este Estado de Resultado a TODOS los correos registrados?\n\nPuede tardar unos minutos.');" title="Enviar masivo por correo">
                                    📧 Enviar
                                 </a>

                                 <a href="?borrar_id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('¿Borrar?');" title="Eliminar registro">❌</a>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  <?php endif; ?>
               </tbody>
            </table>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalPDF" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Generador Automático</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form method="POST" id="formConfig" target="_blank">
            <div class="modal-body">
               <div class="row mb-3">
                  <div class="col-6">
                     <label class="form-label fw-bold">Mes:</label>
                     <select name="mes" id="selMes" class="form-select">
                        <?php $meses_list = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
                        foreach ($meses_list as $num => $nombre) {
                           echo "<option value='$num' " . (($num == date('n')) ? 'selected' : '') . ">$nombre</option>";
                        } ?>
                     </select>
                  </div>
                  <div class="col-6">
                     <label class="form-label fw-bold">Año:</label>
                     <select name="anio" id="selAnio" class="form-select">
                        <?php foreach ($anios as $a): echo "<option value='$a' " . ($a == date('Y') ? 'selected' : '') . ">$a</option>";
                        endforeach; ?>
                     </select>
                  </div>
               </div>
               <hr>
               <h6 class="text-muted small">Apariencia PDF</h6>
               <div class="row">
                  <div class="col-6 mb-2">
                     <label class="form-label small">Títulos:</label>
                     <select name="font_title" class="form-select form-select-sm">
                        <option value="10px">10px</option>
                        <option value="11px" selected>11px</option>
                        <option value="12px">12px</option>
                     </select>
                  </div>
                  <div class="col-6 mb-2">
                     <label class="form-label small">Texto:</label>
                     <select name="font_td" class="form-select form-select-sm">
                        <option value="9px">9px</option>
                        <option value="10px">10px</option>
                        <option value="11px" selected>11px</option>
                     </select>
                  </div>
               </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
               <button type="button" class="btn btn-warning fw-bold" onclick="guardarCierre()">💾 Calcular y Guardar</button>
               <button type="button" class="btn btn-danger" onclick="verPDF()">📄 Ver PDF</button>
            </div>
            <input type="hidden" name="accion" id="accionInput">
            <input type="hidden" name="mes_guardar" id="mesGuardarInput">
            <input type="hidden" name="anio_guardar" id="anioGuardarInput">
         </form>
      </div>
   </div>
</div>

<div class="modal fade" id="modalManual" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Carga Manual / Inicial</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
         </div>
         <form method="POST">
            <input type="hidden" name="accion" value="guardar_manual">
            <div class="modal-body">
               <div class="alert alert-info py-2 small">
                  <i class="fas fa-info-circle"></i> Ingrese los datos manualmente. El sistema calculará los totales.
               </div>

               <div class="row mb-3">
                  <div class="col-6">
                     <label class="form-label fw-bold">Mes (Texto):</label>
                     <select name="mes_manual" class="form-select" required>
                        <?php foreach ($meses_list as $num => $nombre) {
                           echo "<option value='$nombre'>$nombre</option>";
                        } ?>
                     </select>
                  </div>
                  <div class="col-6">
                     <label class="form-label fw-bold">Año:</label>
                     <select name="anio_manual" class="form-select">
                        <?php foreach ($anios as $a): echo "<option value='$a' " . ($a == date('Y') ? 'selected' : '') . ">$a</option>";
                        endforeach; ?>
                     </select>
                  </div>
               </div>

               <div class="row g-3">
                  <div class="col-md-4">
                     <label class="form-label text-success fw-bold">Ingresos (+)</label>
                     <input type="number" step="0.01" class="form-control" name="m_ingresos" id="m_ingresos" required oninput="calcularManual()">
                  </div>
                  <div class="col-md-4">
                     <label class="form-label text-danger fw-bold">Gastos (-)</label>
                     <input type="number" step="0.01" class="form-control" name="m_gastos" id="m_gastos" required oninput="calcularManual()">
                  </div>
                  <div class="col-md-4">
                     <label class="form-label fw-bold">Cierre Mes (=)</label>
                     <input type="text" class="form-control bg-light fw-bold" id="m_cierre" readonly>
                  </div>
               </div>

               <hr>

               <div class="row g-3">
                  <div class="col-md-6">
                     <label class="form-label fw-bold text-muted">Resultado Anterior (Arrastre)</label>
                     <input type="number" step="0.01" class="form-control border-secondary" name="m_anterior" id="m_anterior" value="0.00" oninput="calcularManual()">
                     <div class="form-text">Si es el primer mes, déjelo en 0 o ponga el saldo inicial.</div>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label fw-bold text-primary">Resultado Actual (Final)</label>
                     <input type="text" class="form-control bg-primary text-white fw-bold" id="m_resultado" readonly>
                  </div>
               </div>

            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" class="btn btn-primary fw-bold">💾 Guardar Registro</button>
            </div>
         </form>
      </div>
   </div>
</div>

<script>
   function verPDF() {
      const form = document.getElementById('formConfig');
      form.method = 'GET';
      form.action = 'generar_pdf_estado.php';
      form.target = '_blank';
      form.submit();
   }

   function guardarCierre() {
      const form = document.getElementById('formConfig');
      document.getElementById('accionInput').value = 'guardar_cierre';
      document.getElementById('mesGuardarInput').value = document.getElementById('selMes').value;
      document.getElementById('anioGuardarInput').value = document.getElementById('selAnio').value;
      form.method = 'POST';
      form.action = '';
      form.target = '_self';
      form.submit();
   }

   // CÁLCULO EN TIEMPO REAL PARA EL MODAL MANUAL
   function calcularManual() {
      const ing = parseFloat(document.getElementById('m_ingresos').value) || 0;
      const gas = parseFloat(document.getElementById('m_gastos').value) || 0;
      const ant = parseFloat(document.getElementById('m_anterior').value) || 0;

      const cierre = ing - gas;
      const final = cierre + ant;

      document.getElementById('m_cierre').value = cierre.toLocaleString('en-US', {
         minimumFractionDigits: 2
      });
      document.getElementById('m_resultado').value = final.toLocaleString('en-US', {
         minimumFractionDigits: 2
      });
   }
</script>

<?php include("../../templates/footer.php"); ?>