<?php
session_start();
include("../../bd.php");

if (!isset($_SESSION['usuario'])) {
   die('No autorizado');
}

$id_solicitud = $_GET['id_solicitud'] ?? 0;
$idcondominio = $_SESSION['idcondominio'];

// Función para obtener detalle completo de una solicitud
function obtenerDetalleSolicitud($conexion, $id_solicitud, $idcondominio)
{
   $sentencia = $conexion->prepare("SELECT s.*, d.*, g.tipo_gasto, g.detalles as detalle_gasto, g.monto as monto_gasto
                                   FROM tbl_solicitudes_cheques s
                                   INNER JOIN tbl_detalle_solicitud_cheques d ON s.id = d.id_solicitud
                                   INNER JOIN tbl_gastos g ON d.id_gasto = g.id
                                   WHERE s.id = :id_solicitud AND s.id_condominio = :id_condominio
                                   ORDER BY d.id");

   $sentencia->bindParam(":id_solicitud", $id_solicitud);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   return $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los detalles
$detalles = obtenerDetalleSolicitud($conexion, $id_solicitud, $idcondominio);

if (empty($detalles)) {
   echo '<div class="alert alert-warning">No se encontraron detalles para esta solicitud.</div>';
   exit;
}

// La primera fila contiene la información de la solicitud
$solicitud = $detalles[0];
$total_cheques = count($detalles);
?>

<div class="row">
   <div class="col-md-6">
      <h5>Información de la Solicitud</h5>
      <table class="table table-sm">
         <tr>
            <th class="bg-light">Número:</th>
            <td><strong><?php echo $solicitud['numero_solicitud']; ?></strong></td>
         </tr>
         <tr>
            <th class="bg-light">Descripción:</th>
            <td><?php echo $solicitud['descripcion_general']; ?></td>
         </tr>
         <tr>
            <th class="bg-light">Quincena:</th>
            <td>
               <span class="badge bg-<?php echo $solicitud['quincena_solicitud'] == '15' ? 'info' : 'warning'; ?>">
                  <?php echo $solicitud['quincena_solicitud'] == '15' ? '1-15' : '16-30'; ?>
               </span>
            </td>
         </tr>
         <tr>
            <th class="bg-light">Fecha Solicitud:</th>
            <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></td>
         </tr>
         <tr>
            <th class="bg-light">Solicitado por:</th>
            <td><?php echo $solicitud['solicitado_por']; ?></td>
         </tr>
      </table>
   </div>
   <div class="col-md-6">
      <h5>Resumen</h5>
      <table class="table table-sm">
         <tr>
            <th class="bg-light">Total Solicitud:</th>
            <td class="fw-bold text-success fs-5">RD$ <?php echo number_format($solicitud['total_general'], 2, '.', ','); ?></td>
         </tr>
         <tr>
            <th class="bg-light">Cantidad de Cheques:</th>
            <td><span class="badge bg-primary"><?php echo $total_cheques; ?> cheques</span></td>
         </tr>
         <tr>
            <th class="bg-light">Estado:</th>
            <td>
               <span class="badge bg-<?php
                                       echo $solicitud['estado'] == 'aprobado' ? 'success' : ($solicitud['estado'] == 'rechazado' ? 'danger' : 'warning');
                                       ?>">
                  <?php echo ucfirst($solicitud['estado']); ?>
               </span>
            </td>
         </tr>
         <tr>
            <th class="bg-light">Fecha Creación:</th>
            <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_creacion'])); ?></td>
         </tr>
      </table>
   </div>
</div>

<hr>

<h5 class="mt-4">📋 Detalle de Cheques</h5>
<div class="table-responsive">
   <table class="table table-striped table-sm">
      <thead class="table-dark">
         <tr>
            <th># Cheque</th>
            <th>Tipo de Gasto</th>
            <th>Detalles</th>
            <th>Monto</th>
            <th>Quincena</th>
         </tr>
      </thead>
      <tbody>
         <?php
         $total = 0;
         $tipos_gastos = [
            'Nomina_Empleados' => 'Nómina Empleados',
            'Servicios_Basicos' => 'Servicios Básicos',
            'Gastos_Menores_Material_Gastable' => 'Gastos Menores, Material Gastable',
            'Imprevistos' => 'Imprevistos',
            'Cargos_Bancarios' => 'Cargos Bancarios',
            'Servicios_Igualados' => 'Servicios Igualados'
         ];

         foreach ($detalles as $cheque):
            $tipo_nombre = $tipos_gastos[$cheque['tipo_gasto']] ?? $cheque['tipo_gasto'];
            $total += floatval($cheque['monto']);
         ?>
            <tr>
               <td class="fw-bold"><?php echo $cheque['numero_cheque']; ?></td>
               <td><?php echo $tipo_nombre; ?></td>
               <td><?php echo $cheque['detalle_gasto']; ?></td>
               <td class="fw-bold">RD$ <?php echo number_format($cheque['monto'], 2, '.', ','); ?></td>
               <td>
                  <span class="badge bg-<?php echo $cheque['quincena_gasto'] == '15' ? 'info' : 'warning'; ?>">
                     <?php echo $cheque['quincena_gasto'] == '15' ? '1-15' : '16-30'; ?>
                  </span>
               </td>
            </tr>
         <?php endforeach; ?>
      </tbody>
      <tfoot class="table-dark">
         <tr>
            <td colspan="3" class="text-end fw-bold">Total:</td>
            <td class="fw-bold">RD$ <?php echo number_format($total, 2, '.', ','); ?></td>
            <td></td>
         </tr>
      </tfoot>
   </table>
</div>