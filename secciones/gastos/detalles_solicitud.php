<?php
include("../../bd.php");

if (isset($_GET['id'])) {
   $solicitud_id = $_GET['id'];
   $id_condominio = $_SESSION['idcondominio'];

   // Obtener datos de la solicitud
   $sentencia_solicitud = $conexion->prepare("SELECT * FROM tbl_solicitudes_cheques WHERE id = :id AND id_condominio = :id_condominio");
   $sentencia_solicitud->bindParam(":id", $solicitud_id);
   $sentencia_solicitud->bindParam(":id_condominio", $id_condominio);
   $sentencia_solicitud->execute();
   $solicitud = $sentencia_solicitud->fetch(PDO::FETCH_ASSOC);

   if ($solicitud) {
      // Obtener detalles agrupados por cheque
      $sentencia_detalles = $conexion->prepare("
            SELECT numero_cheque, 
                   COUNT(*) as total_gastos,
                   SUM(monto) as total_cheque,
                   GROUP_CONCAT(CONCAT(detalles, ' - RD$ ', FORMAT(monto, 2)) SEPARATOR '||') as gastos_detalles
            FROM tbl_detalle_solicitud_cheques 
            WHERE id_solicitud = :id_solicitud 
            GROUP BY numero_cheque 
            ORDER BY numero_cheque
        ");
      $sentencia_detalles->bindParam(":id_solicitud", $solicitud_id);
      $sentencia_detalles->execute();
      $detalles_cheques = $sentencia_detalles->fetchAll(PDO::FETCH_ASSOC);
?>

      <div class="container-fluid">
         <!-- Información general -->
         <div class="row mb-4">
            <div class="col-md-6">
               <h5>Información General</h5>
               <table class="table table-sm">
                  <tr>
                     <th>N° Solicitud:</th>
                     <td><?php echo $solicitud['numero_solicitud']; ?></td>
                  </tr>
                  <tr>
                     <th>Fecha:</th>
                     <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></td>
                  </tr>
                  <tr>
                     <th>Quincena:</th>
                     <td>
                        <span class="badge bg-info">
                           <?php echo $solicitud['quincena_solicitud'] == '15' ? '1-15' : '16-30'; ?>
                        </span>
                     </td>
                  </tr>
                  <tr>
                     <th>Estado:</th>
                     <td>
                        <span class="badge <?php echo $solicitud['estado'] == 'pendiente' ? 'bg-warning text-dark' : ($solicitud['estado'] == 'aprobado' ? 'bg-success' : 'bg-danger'); ?>">
                           <?php echo ucfirst($solicitud['estado']); ?>
                        </span>
                     </td>
                  </tr>
               </table>
            </div>
            <div class="col-md-6">
               <h5>Resumen</h5>
               <table class="table table-sm">
                  <tr>
                     <th>Total Cheques:</th>
                     <td><?php echo count($detalles_cheques); ?></td>
                  </tr>
                  <tr>
                     <th>Total General:</th>
                     <td class="fw-bold text-success">
                        RD$ <?php echo number_format($solicitud['total_general'], 2, '.', ','); ?>
                     </td>
                  </tr>
                  <tr>
                     <th>Solicitado por:</th>
                     <td><?php echo $solicitud['solicitado_por']; ?></td>
                  </tr>
                  <tr>
                     <th>Fecha creación:</th>
                     <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_creacion'])); ?></td>
                  </tr>
               </table>
            </div>
         </div>

         <!-- Descripción -->
         <?php if ($solicitud['descripcion_general']): ?>
            <div class="row mb-4">
               <div class="col-12">
                  <h5>Descripción</h5>
                  <div class="card">
                     <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($solicitud['descripcion_general'])); ?>
                     </div>
                  </div>
               </div>
            </div>
         <?php endif; ?>

         <!-- Detalles por cheque -->
         <div class="row">
            <div class="col-12">
               <h5>Detalles de Cheques</h5>
               <?php if (count($detalles_cheques) > 0): ?>
                  <?php foreach ($detalles_cheques as $cheque): ?>
                     <div class="card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                           <h6 class="mb-0"><?php echo $cheque['numero_cheque']; ?></h6>
                           <strong class="text-success">
                              Total: RD$ <?php echo number_format($cheque['total_cheque'], 2, '.', ','); ?>
                           </strong>
                        </div>
                        <div class="card-body">
                           <ul class="list-group list-group-flush">
                              <?php
                              $gastos = explode('||', $cheque['gastos_detalles']);
                              foreach ($gastos as $gasto):
                              ?>
                                 <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($gasto); ?>
                                 </li>
                              <?php endforeach; ?>
                           </ul>
                        </div>
                     </div>
                  <?php endforeach; ?>
               <?php else: ?>
                  <div class="alert alert-warning">
                     No hay detalles disponibles para esta solicitud.
                  </div>
               <?php endif; ?>
            </div>
         </div>
      </div>
<?php
   } else {
      echo '<div class="alert alert-danger">Solicitud no encontrada.</div>';
   }
} else {
   echo '<div class="alert alert-danger">ID de solicitud no proporcionado.</div>';
}
?>