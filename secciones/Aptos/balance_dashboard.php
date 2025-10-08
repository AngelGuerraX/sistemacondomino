<?php
include("../../templates/header.php");
include("../../bd.php");
include("actualizar_balance.php");

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

$id_condominio = $_SESSION['idcondominio'];

// SOLO actualizar cuando se presiona el botón explícitamente
if (isset($_POST['actualizar_balances'])) {
   $resultado = actualizarTodosBalances($id_condominio);

   if ($resultado['success']) {
      $_SESSION['mensaje'] = "✅ Balances actualizados correctamente para {$resultado['total_aptos']} apartamentos";
      $_SESSION['tipo_mensaje'] = "success";
   } else {
      $_SESSION['mensaje'] = "❌ Error: " . $resultado['error'];
      $_SESSION['tipo_mensaje'] = "danger";
   }

   // Redirigir para evitar reenvío del formulario
   header("Location: balance_dashboard.php");
   exit;
}

// Procesar actualización individual
if (isset($_GET['actualizar_individual'])) {
   $id_apto = $_GET['actualizar_individual'];
   $resultado = actualizarBalanceApto($id_apto, $id_condominio);

   if ($resultado['success']) {
      $_SESSION['mensaje'] = "✅ Balance del apartamento actualizado: " . $resultado['formula'];
      $_SESSION['tipo_mensaje'] = "success";
   } else {
      $_SESSION['mensaje'] = "❌ Error actualizando balance: " . $resultado['error'];
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: balance_dashboard.php");
   exit;
}

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
   echo "<div class='alert alert-{$_SESSION['tipo_mensaje']}'>{$_SESSION['mensaje']}</div>";
   unset($_SESSION['mensaje']);
   unset($_SESSION['tipo_mensaje']);
}

// Obtener datos para el dashboard (SIN actualizar automáticamente)
$sentencia_resumen = $conexion->prepare("
    SELECT 
        COUNT(*) as total_aptos,
        SUM(balance) as balance_total,
        AVG(balance) as balance_promedio,
        SUM(CASE WHEN balance < 0 THEN 1 ELSE 0 END) as aptos_deudores,
        SUM(CASE WHEN balance >= 0 THEN 1 ELSE 0 END) as aptos_al_dia,
        SUM(CASE WHEN balance < -1000 THEN 1 ELSE 0 END) as aptos_morosos
    FROM tbl_aptos 
    WHERE id_condominio = :id_condominio
");
$sentencia_resumen->bindParam(":id_condominio", $id_condominio);
$sentencia_resumen->execute();
$resumen = $sentencia_resumen->fetch(PDO::FETCH_ASSOC);

// Obtener apartamentos con mayor deuda
$sentencia_deudores = $conexion->prepare("
    SELECT id, apto, condominos, balance, telefono, correo
    FROM tbl_aptos 
    WHERE id_condominio = :id_condominio 
    AND balance < 0
    ORDER BY balance ASC
    LIMIT 10
");
$sentencia_deudores->bindParam(":id_condominio", $id_condominio);
$sentencia_deudores->execute();
$top_deudores = $sentencia_deudores->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los apartamentos para la tabla
$sentencia_aptos = $conexion->prepare("
    SELECT a.id, a.apto, a.condominos, a.balance, a.telefono, a.correo, a.fecha_ultimo_pago,
           (SELECT SUM(monto) FROM tbl_pagos p WHERE p.id_apto = a.id AND p.id_condominio = a.id_condominio) as total_pagos,
           (SELECT SUM(mantenimiento + mora + gas + cuota) FROM tbl_tickets t WHERE t.id_apto = a.id AND t.id_condominio = a.id_condominio AND t.estado = 'Pendiente') as deuda_pendiente
    FROM tbl_aptos a
    WHERE a.id_condominio = :id_condominio 
    ORDER BY a.balance ASC, a.apto ASC
");
$sentencia_aptos->bindParam(":id_condominio", $id_condominio);
$sentencia_aptos->execute();
$lista_aptos = $sentencia_aptos->fetchAll(PDO::FETCH_ASSOC);

// Obtener información del condominio
$sentencia_condominio = $conexion->prepare("SELECT nombre FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $id_condominio);
$sentencia_condominio->execute();
$condominio = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
   <!-- Header -->
   <div class="card mb-4">
      <div class="card-header bg-dark text-white">
         <div class="row align-items-center">
            <div class="col-md-8">
               <h3 class="mb-0">💰 GESTIÓN DE BALANCES</h3>
               <small class="text-warning"><?= $condominio['nombre'] ?? 'Condominio' ?> | Fórmula: BALANCE = TOTAL PAGOS - DEUDA PENDIENTE</small>
            </div>
            <div class="col-md-4 text-end">
               <!-- Form para actualizar balances -->
               <form method="POST" class="d-inline">
                  <button type="submit" name="actualizar_balances" class="btn btn-warning btn-lg">
                     🔄 Actualizar Todos
                  </button>
               </form>
               <!-- Botón para exportar Excel -->
               <a href="exportar_excel_general.php" class="btn btn-success btn-lg">
                  📊 Exportar Excel
               </a>
            </div>
         </div>
      </div>
   </div>

   <!-- Tarjetas de Resumen -->
   <div class="row mb-4">
      <div class="col-xl-2 col-md-4">
         <div class="card bg-primary text-white">
            <div class="card-body text-center">
               <h2><?= $resumen['total_aptos'] ?></h2>
               <h6>TOTAL APARTAMENTOS</h6>
            </div>
         </div>
      </div>
      <div class="col-xl-2 col-md-4">
         <div class="card bg-success text-white">
            <div class="card-body text-center">
               <h2><?= $resumen['aptos_al_dia'] ?></h2>
               <h6>AL DÍA</h6>
               <small>
                  <?= $resumen['total_aptos'] > 0 ?
                     number_format(($resumen['aptos_al_dia'] / $resumen['total_aptos']) * 100, 1) : 0 ?>%
               </small>
            </div>
         </div>
      </div>
      <div class="col-xl-2 col-md-4">
         <div class="card bg-warning text-white">
            <div class="card-body text-center">
               <h2><?= $resumen['aptos_deudores'] ?></h2>
               <h6>CON DEUDA</h6>
               <small>
                  <?= $resumen['total_aptos'] > 0 ?
                     number_format(($resumen['aptos_deudores'] / $resumen['total_aptos']) * 100, 1) : 0 ?>%
               </small>
            </div>
         </div>
      </div>
      <div class="col-xl-2 col-md-4">
         <div class="card bg-danger text-white">
            <div class="card-body text-center">
               <h2><?= $resumen['aptos_morosos'] ?></h2>
               <h6>MOROSOS</h6>
               <small>(> RD$ 1,000)</small>
            </div>
         </div>
      </div>
      <div class="col-xl-2 col-md-4">
         <div class="card bg-info text-white">
            <div class="card-body text-center">
               <h4>RD$ <?= number_format($resumen['balance_total'], 2) ?></h4>
               <h6>BALANCE TOTAL</h6>
            </div>
         </div>
      </div>
      <div class="col-xl-2 col-md-4">
         <div class="card bg-secondary text-white">
            <div class="card-body text-center">
               <h4>RD$ <?= number_format($resumen['balance_promedio'], 2) ?></h4>
               <h6>PROMEDIO</h6>
            </div>
         </div>
      </div>
   </div>

   <div class="row">
      <!-- Top Deudores -->
      <div class="col-md-6">
         <div class="card">
            <div class="card-header bg-warning text-white">
               <h5>🚨 TOP 10 DEUDORES</h5>
            </div>
            <div class="card-body">
               <?php if (count($top_deudores) > 0): ?>
                  <div class="table-responsive">
                     <table class="table table-sm table-striped">
                        <thead>
                           <tr>
                              <th>Apto</th>
                              <th>Condómino</th>
                              <th>Deuda</th>
                              <th>Contacto</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($top_deudores as $deudor): ?>
                              <tr>
                                 <td><strong><?= $deudor['apto'] ?></strong></td>
                                 <td><?= $deudor['condominos'] ?></td>
                                 <td class="text-danger fw-bold">
                                    RD$ <?= number_format($deudor['balance'], 2) ?>
                                 </td>
                                 <td>
                                    <small>
                                       <?= $deudor['telefono'] ?><br>
                                       <?= $deudor['correo'] ?>
                                    </small>
                                 </td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                  </div>
               <?php else: ?>
                  <div class="text-center text-success py-4">
                     <i class="fas fa-check-circle fa-3x mb-3"></i>
                     <p>¡No hay deudores!</p>
                  </div>
               <?php endif; ?>
            </div>
         </div>
      </div>

      <!-- Estadísticas Rápidas -->
      <div class="col-md-6">
         <div class="card">
            <div class="card-header bg-success text-white">
               <h5>📈 ESTADÍSTICAS RÁPIDAS</h5>
            </div>
            <div class="card-body">
               <div class="list-group">
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                     <span>Balance Promedio:</span>
                     <strong>RD$ <?= number_format($resumen['balance_promedio'], 2) ?></strong>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                     <span>Porcentaje al día:</span>
                     <strong>
                        <?= $resumen['total_aptos'] > 0 ?
                           number_format(($resumen['aptos_al_dia'] / $resumen['total_aptos']) * 100, 1) : 0 ?>%
                     </strong>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                     <span>Deuda total acumulada:</span>
                     <strong class="text-danger">
                        RD$ <?= number_format(abs($resumen['balance_total']), 2) ?>
                     </strong>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                     <span>Última actualización:</span>
                     <strong><?= date('d/m/Y H:i:s') ?></strong>
                  </div>
               </div>

               <!-- Botones de acción rápida -->
               <div class="mt-3 text-center">
                  <a href="javascript:void(0)" onclick="actualizarTodos()" class="btn btn-warning btn-sm">
                     🔄 Actualizar Ahora
                  </a>
                  <a href="exportar_excel_general.php" class="btn btn-success btn-sm">
                     📄 Descargar Reporte
                  </a>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Tabla Completa de Apartamentos -->
   <div class="card mt-4">
      <div class="card-header bg-dark text-white">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">📋 LISTA COMPLETA DE APARTAMENTOS</h5>
            </div>
            <div class="col-md-6 text-end">
               <small>Mostrando <?= count($lista_aptos) ?> apartamentos</small>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-striped table-hover" id="tablaBalances">
               <thead class="table-dark">
                  <tr>
                     <th>#</th>
                     <th>Apartamento</th>
                     <th>Condómino</th>
                     <th>Total Pagos</th>
                     <th>Deuda Pendiente</th>
                     <th>Balance</th>
                     <th>Teléfono</th>
                     <th>Último Pago</th>
                     <th>Estado</th>
                     <th>Acciones</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  $contador = 1;
                  foreach ($lista_aptos as $apto):
                     $estado = $apto['balance'] >= 0 ? 'success' : 'danger';
                     $texto_estado = $apto['balance'] >= 0 ? 'AL DÍA' : 'DEUDOR';
                  ?>
                     <tr>
                        <td><?= $contador++ ?></td>
                        <td><strong><?= $apto['apto'] ?></strong></td>
                        <td><?= $apto['condominos'] ?></td>
                        <td class="text-success">
                           <small>RD$ <?= number_format($apto['total_pagos'] ?? 0, 2) ?></small>
                        </td>
                        <td class="text-danger">
                           <small>RD$ <?= number_format($apto['deuda_pendiente'] ?? 0, 2) ?></small>
                        </td>
                        <td class="fw-bold <?= $estado == 'success' ? 'text-success' : 'text-danger' ?>">
                           RD$ <?= number_format($apto['balance'], 2) ?>
                        </td>
                        <td><?= $apto['telefono'] ?></td>
                        <td>
                           <small>
                              <?= $apto['fecha_ultimo_pago'] ?
                                 date('d/m/Y', strtotime($apto['fecha_ultimo_pago'])) :
                                 '<span class="text-muted">Nunca</span>' ?>
                           </small>
                        </td>
                        <td>
                           <span class="badge bg-<?= $estado ?>">
                              <?= $texto_estado ?>
                           </span>
                        </td>
                        <td>
                           <div class="btn-group btn-group-sm">
                              <a href="detalle_balance_apto.php?txID=<?= $apto['id'] ?>"
                                 class="btn btn-primary" title="Ver detalle">
                                 👁️
                              </a>
                              <a href="?actualizar_individual=<?= $apto['id'] ?>"
                                 class="btn btn-warning" title="Actualizar balance">
                                 🔄
                              </a>
                              <a href="javascript:void(0)"
                                 onclick="debugBalance(<?= $apto['id'] ?>)"
                                 class="btn btn-info" title="Debug cálculo">
                                 🔍
                              </a>
                           </div>
                        </td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
               <tfoot class="table-secondary">
                  <tr>
                     <td colspan="3" class="text-end"><strong>TOTALES:</strong></td>
                     <td class="text-success">
                        <strong>RD$ <?= number_format(array_sum(array_column($lista_aptos, 'total_pagos')), 2) ?></strong>
                     </td>
                     <td class="text-danger">
                        <strong>RD$ <?= number_format(array_sum(array_column($lista_aptos, 'deuda_pendiente')), 2) ?></strong>
                     </td>
                     <td class="<?= $resumen['balance_total'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <strong>RD$ <?= number_format($resumen['balance_total'], 2) ?></strong>
                     </td>
                     <td colspan="4"></td>
                  </tr>
               </tfoot>
            </table>
         </div>
      </div>
   </div>

   <!-- Información adicional -->
   <div class="row mt-4">
      <div class="col-md-12">
         <div class="card">
            <div class="card-header bg-info text-white">
               <h6>💡 INFORMACIÓN</h6>
            </div>
            <div class="card-body">
               <div class="row">
                  <div class="col-md-4">
                     <h6>📊 Cómo se calcula:</h6>
                     <ul class="small">
                        <li><strong>Balance = Total Pagos - Deuda Pendiente</strong></li>
                        <li>Deuda Pendiente: Suma de tickets con estado "Pendiente"</li>
                        <li>Total Pagos: Suma de todos los pagos registrados</li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h6>🎯 Estados:</h6>
                     <ul class="small">
                        <li><span class="badge bg-success">AL DÍA</span> - Balance positivo o cero</li>
                        <li><span class="badge bg-danger">DEUDOR</span> - Balance negativo</li>
                        <li><span class="badge bg-warning">MOROSO</span> - Deuda > RD$ 1,000</li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h6>⚡ Acciones rápidas:</h6>
                     <ul class="small">
                        <li><strong>👁️ Ver</strong> - Detalle completo del apartamento</li>
                        <li><strong>🔄 Actualizar</strong> - Recalcular balance individual</li>
                        <li><strong>🔍 Debug</strong> - Ver cálculo detallado</li>
                     </ul>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Modal para Debug -->
<div class="modal fade" id="modalDebug" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header bg-info text-white">
            <h5 class="modal-title">🔍 DEBUG - CÁLCULO DETALLADO</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="debugContent">
            <!-- Contenido se carga via AJAX -->
         </div>
      </div>
   </div>
</div>

<script>
   function actualizarTodos() {
      Swal.fire({
         title: '¿Actualizar todos los balances?',
         text: 'Esto recalculará los balances de todos los apartamentos',
         icon: 'question',
         showCancelButton: true,
         confirmButtonText: 'Sí, actualizar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            document.querySelector('button[name="actualizar_balances"]').click();
         }
      });
   }

   function debugBalance(idApto) {
      // Mostrar loading
      Swal.fire({
         title: 'Cargando información...',
         allowOutsideClick: false,
         didOpen: () => {
            Swal.showLoading()
         }
      });

      // Cargar información de debug via AJAX
      fetch('debug_balance.php?id_apto=' + idApto)
         .then(response => response.text())
         .then(html => {
            Swal.close();
            document.getElementById('debugContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDebug')).show();
         })
         .catch(error => {
            Swal.close();
            Swal.fire('Error', 'No se pudo cargar la información', 'error');
         });
   }

   // Inicializar DataTable
   $(document).ready(function() {
      $('#tablaBalances').DataTable({
         "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
         },
         "order": [
            [5, "asc"]
         ],
         "pageLength": 25,
         "responsive": true,
         "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
      });
   });

   // Auto-ocultar alertas después de 5 segundos
   setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
         const bsAlert = new bootstrap.Alert(alert);
         bsAlert.close();
      });
   }, 5000);
</script>

<?php include("../../templates/footer.php"); ?>