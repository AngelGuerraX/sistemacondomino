<?php
include("../../bd.php");
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

$id_condominio = $_SESSION['idcondominio'];
$id_apto = $_GET['id_apto'] ?? '';

if (!$id_apto) {
   die("ID de apartamento no especificado");
}

// Obtener información del apartamento
$sentencia_apto = $conexion->prepare("SELECT apto, condominos FROM tbl_aptos WHERE id = :id");
$sentencia_apto->bindParam(":id", $id_apto);
$sentencia_apto->execute();
$apto = $sentencia_apto->fetch(PDO::FETCH_ASSOC);

// Obtener tickets pendientes
$sentencia_tickets = $conexion->prepare("
    SELECT mes, anio, mantenimiento, mora, gas, cuota, 
           (mantenimiento + mora + gas + cuota) as total_ticket
    FROM tbl_tickets 
    WHERE id_apto = :id_apto 
    AND id_condominio = :id_condominio 
    AND estado = 'Pendiente'
    ORDER BY anio, FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')
");
$sentencia_tickets->bindParam(":id_apto", $id_apto);
$sentencia_tickets->bindParam(":id_condominio", $id_condominio);
$sentencia_tickets->execute();
$tickets = $sentencia_tickets->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos
$sentencia_pagos = $conexion->prepare("
    SELECT concepto, monto, fecha_pago, forma_pago
    FROM tbl_pagos 
    WHERE id_apto = :id_apto 
    AND id_condominio = :id_condominio
    ORDER BY fecha_pago DESC
");
$sentencia_pagos->bindParam(":id_apto", $id_apto);
$sentencia_pagos->bindParam(":id_condominio", $id_condominio);
$sentencia_pagos->execute();
$pagos = $sentencia_pagos->fetchAll(PDO::FETCH_ASSOC);

$total_deuda = array_sum(array_column($tickets, 'total_ticket'));
$total_pagos = array_sum(array_column($pagos, 'monto'));
$balance = $total_pagos - $total_deuda;
?>

<div class="container-fluid">
   <h6>Apartamento: <strong><?= $apto['apto'] ?></strong> - <?= $apto['condominos'] ?></h6>

   <div class="row">
      <div class="col-md-6">
         <h6 class="text-danger">📋 Tickets Pendientes (<?= count($tickets) ?>)</h6>
         <?php if (count($tickets) > 0): ?>
            <div class="table-responsive">
               <table class="table table-sm table-striped">
                  <thead>
                     <tr>
                        <th>Mes/Año</th>
                        <th>Mantenimiento</th>
                        <th>Mora</th>
                        <th>Gas</th>
                        <th>Cuota</th>
                        <th>Total</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tickets as $ticket): ?>
                        <tr>
                           <td><?= $ticket['mes'] ?>/<?= $ticket['anio'] ?></td>
                           <td>RD$ <?= number_format($ticket['mantenimiento'], 2) ?></td>
                           <td>RD$ <?= number_format($ticket['mora'], 2) ?></td>
                           <td>RD$ <?= number_format($ticket['gas'], 2) ?></td>
                           <td>RD$ <?= number_format($ticket['cuota'], 2) ?></td>
                           <td class="text-danger"><strong>RD$ <?= number_format($ticket['total_ticket'], 2) ?></strong></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
                  <tfoot class="table-warning">
                     <tr>
                        <td colspan="5"><strong>TOTAL DEUDA:</strong></td>
                        <td><strong>RD$ <?= number_format($total_deuda, 2) ?></strong></td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         <?php else: ?>
            <div class="alert alert-success">
               <i class="fas fa-check-circle"></i> No hay tickets pendientes
            </div>
         <?php endif; ?>
      </div>

      <div class="col-md-6">
         <h6 class="text-success">💵 Pagos Realizados (<?= count($pagos) ?>)</h6>
         <?php if (count($pagos) > 0): ?>
            <div class="table-responsive">
               <table class="table table-sm table-striped">
                  <thead>
                     <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                        <th>Forma Pago</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($pagos as $pago): ?>
                        <tr>
                           <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                           <td><?= $pago['concepto'] ?></td>
                           <td class="text-success">RD$ <?= number_format($pago['monto'], 2) ?></td>
                           <td><span class="badge bg-secondary"><?= $pago['forma_pago'] ?></span></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
                  <tfoot class="table-success">
                     <tr>
                        <td colspan="2"><strong>TOTAL PAGOS:</strong></td>
                        <td colspan="2"><strong>RD$ <?= number_format($total_pagos, 2) ?></strong></td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         <?php else: ?>
            <div class="alert alert-warning">
               <i class="fas fa-exclamation-triangle"></i> No se han registrado pagos
            </div>
         <?php endif; ?>
      </div>
   </div>

   <div class="row mt-3">
      <div class="col-md-12">
         <div class="alert alert-info">
            <h6>🧮 RESUMEN DEL CÁLCULO</h6>
            <div class="row text-center">
               <div class="col-md-4">
                  <h5 class="text-success">RD$ <?= number_format($total_pagos, 2) ?></h5>
                  <p>Total Pagado</p>
               </div>
               <div class="col-md-4">
                  <h5 class="text-danger">- RD$ <?= number_format($total_deuda, 2) ?></h5>
                  <p>Deuda Pendiente</p>
               </div>
               <div class="col-md-4">
                  <h5 class="<?= $balance >= 0 ? 'text-primary' : 'text-warning' ?>">
                     = RD$ <?= number_format($balance, 2) ?>
                  </h5>
                  <p>Balance Final</p>
               </div>
            </div>
            <div class="text-center mt-2">
               <code>
                  <?= number_format($total_pagos, 2) ?> - <?= number_format($total_deuda, 2) ?> = <?= number_format($balance, 2) ?>
               </code>
            </div>
         </div>
      </div>
   </div>
</div>