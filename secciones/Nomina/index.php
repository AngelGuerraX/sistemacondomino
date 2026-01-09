<?php
include("../../templates/header.php");
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// Procesar eliminación de nómina
if (isset($_GET['eliminar_nomina'])) {
   $id_nomina = $_GET['eliminar_nomina'];

   try {
      $conexion->beginTransaction();

      // 1. Obtener información de la nómina antes de eliminar
      $sentencia_info = $conexion->prepare("
            SELECT n.quincena, n.mes, n.anio, n.total_quincena 
            FROM tbl_nomina n 
            WHERE n.id = :id_nomina AND n.id_condominio = :id_condominio
        ");
      $sentencia_info->bindParam(":id_nomina", $id_nomina);
      $sentencia_info->bindParam(":id_condominio", $idcondominio);
      $sentencia_info->execute();
      $nomina_info = $sentencia_info->fetch(PDO::FETCH_ASSOC);

      if ($nomina_info) {
         // 2. Eliminar el gasto asociado en tbl_gastos
         // NOTA: En tu BD, el campo 'mes' en tbl_nomina es VARCHAR con nombres de mes
         $mes_nombre = $nomina_info['mes']; // Ya viene como nombre del mes (Enero, Febrero, etc.)
         $detalles_gasto = "Pago de nómina " . ($nomina_info['quincena'] == '15' ? '1-15' : '16-30') . " - " . $mes_nombre . " " . $nomina_info['anio'];

         $sentencia_eliminar_gasto = $conexion->prepare("
                DELETE FROM tbl_gastos 
                WHERE tipo_gasto = 'Nomina_Empleados' 
                AND detalles = :detalles 
                AND quincena = :quincena 
                AND mes = :mes 
                AND anio = :anio 
                AND id_condominio = :id_condominio
            ");
         $sentencia_eliminar_gasto->bindParam(":detalles", $detalles_gasto);
         $sentencia_eliminar_gasto->bindParam(":quincena", $nomina_info['quincena']);
         $sentencia_eliminar_gasto->bindParam(":mes", $mes_nombre);
         $sentencia_eliminar_gasto->bindParam(":anio", $nomina_info['anio']);
         $sentencia_eliminar_gasto->bindParam(":id_condominio", $idcondominio);
         $sentencia_eliminar_gasto->execute();

         // 3. Eliminar la nómina
         $sentencia_eliminar_nomina = $conexion->prepare("
                DELETE FROM tbl_nomina 
                WHERE quincena = :quincena 
                AND mes = :mes 
                AND anio = :anio 
                AND id_condominio = :id_condominio
            ");
         $sentencia_eliminar_nomina->bindParam(":quincena", $nomina_info['quincena']);
         $sentencia_eliminar_nomina->bindParam(":mes", $mes_nombre);
         $sentencia_eliminar_nomina->bindParam(":anio", $nomina_info['anio']);
         $sentencia_eliminar_nomina->bindParam(":id_condominio", $idcondominio);
         $sentencia_eliminar_nomina->execute();

         $conexion->commit();

         $_SESSION['mensaje'] = "✅ Nómina eliminada correctamente";
         $_SESSION['tipo_mensaje'] = "success";
      } else {
         throw new Exception("Nómina no encontrada");
      }
   } catch (Exception $e) {
      $conexion->rollBack();
      $_SESSION['mensaje'] = "❌ Error al eliminar nómina: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: index.php");
   exit;
}

// Función para obtener nombre del mes (para compatibilidad)
function obtenerNombreMes($mes)
{
   // Si ya es un nombre de mes, devolverlo tal cual
   if (is_string($mes) && !is_numeric($mes)) {
      return $mes;
   }

   // Si es numérico, convertirlo
   $meses = [
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
   return $meses[$mes] ?? $mes;
}

// Obtener parámetros de filtro
$mes_filtro = $_GET['mes'] ?? '';
$anio_filtro = $_GET['anio'] ?? date('Y');
$quincena_filtro = $_GET['quincena'] ?? '';

// Convertir mes numérico a nombre para filtros (si es necesario)
if ($mes_filtro && is_numeric($mes_filtro)) {
   $mes_filtro_nombre = obtenerNombreMes($mes_filtro);
} else {
   $mes_filtro_nombre = $mes_filtro;
}

// Obtener nóminas con filtros - CORREGIDO PARA TU ESTRUCTURA
$query = "
    SELECT n.*, 
           e.nombres, 
           e.apellidos,
           e.cedula_pasaporte,
           e.cargo
    FROM tbl_nomina n
    INNER JOIN tbl_empleados e ON n.id_empleado = e.id
    WHERE n.id_condominio = :id_condominio
";

$params = [':id_condominio' => $idcondominio];

if ($mes_filtro) {
   // Usar el nombre del mes para el filtro (ya que tu BD almacena nombres)
   $query .= " AND n.mes = :mes";
   $params[':mes'] = $mes_filtro_nombre;
}

if ($anio_filtro) {
   $query .= " AND n.anio = :anio";
   $params[':anio'] = $anio_filtro;
}

if ($quincena_filtro) {
   $query .= " AND n.quincena = :quincena";
   $params[':quincena'] = $quincena_filtro;
}

$query .= " ORDER BY n.anio DESC, n.mes DESC, n.quincena DESC, e.nombres";

$sentencia_nominas = $conexion->prepare($query);
foreach ($params as $key => $value) {
   $sentencia_nominas->bindValue($key, $value);
}
$sentencia_nominas->execute();
$nominas = $sentencia_nominas->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales manualmente (ya que no podemos usar window functions con PDO fácilmente)
$total_general = 0;
$total_empleados = count($nominas);

foreach ($nominas as $nomina) {
   $total_general += floatval($nomina['total_quincena']);
}

$meses = [
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <style>
      .badge-quincena {
         background-color: #6f42c1;
      }

      .btn-eliminar {
         transition: all 0.3s ease;
      }

      .btn-eliminar:hover {
         transform: scale(1.1);
      }

      .table-hover tbody tr:hover {
         background-color: #f8f9fa;
      }
   </style>
</head>

<body>
   <div class="container-fluid py-4">
      <!-- Header -->
      <div class="row mb-4">
         <div class="col-12">
            <div class="card card-total bg-dark text-white">
               <div class="card-body">
                  <div class="row align-items-center">
                     <div class="col-md-8">
                        <h1 class="h2 mb-1">Nóminas 💰</h1>
                        <p class="mb-0 opacity-8">Gestión completa de nóminas del condominio</p>
                     </div>
                     <div class="col-md-4 text-end">
                        <a href="generar_nomina.php" class="btn btn-light btn-lg">
                           <i class="fas fa-plus me-2"></i> Nueva Nómina
                        </a>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Mensajes -->
      <?php if (isset($_SESSION['mensaje'])): ?>
         <div class='alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show' role='alert'>
            <?php echo $_SESSION['mensaje']; ?>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
         </div>
         <?php
         unset($_SESSION['mensaje']);
         unset($_SESSION['tipo_mensaje']);
         ?>
      <?php endif; ?>

      <!-- Filtros -->
      <div class="card bg-dark text-white shadow mb-4">
         <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-light">
               <i class="fas fa-filter me-2"></i>Filtrar Nóminas
            </h6>
         </div>
         <div class="card-body bg-light text-dark">
            <form method="GET" class="row g-3">
               <div class="col-md-3">
                  <label class="form-label">Mes:</label>
                  <select name="mes" class="form-select">
                     <option value="">Todos los meses</option>
                     <?php foreach ($meses as $numero => $nombre): ?>
                        <option value="<?php echo $numero; ?>" <?php echo $mes_filtro == $numero ? 'selected' : ''; ?>>
                           <?php echo $nombre; ?>
                        </option>
                     <?php endforeach; ?>
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Año:</label>
                  <select name="anio" class="form-select">
                     <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $anio_filtro == $i ? 'selected' : ''; ?>>
                           <?php echo $i; ?>
                        </option>
                     <?php endfor; ?>
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Quincena:</label>
                  <select name="quincena" class="form-select">
                     <option value="">Todas</option>
                     <option value="15" <?php echo $quincena_filtro == '15' ? 'selected' : ''; ?>>1-15</option>
                     <option value="30" <?php echo $quincena_filtro == '30' ? 'selected' : ''; ?>>16-30</option>
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">&nbsp;</label>
                  <div class="d-grid">
                     <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i> Filtrar
                     </button>
                  </div>
               </div>
            </form>
         </div>
      </div>

      <!-- Resumen -->
      <div class="row mb-4">
         <div class="col-md-4">
            <div class="card bg-dark text-white border-left-primary shadow h-100 py-2">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-light text-uppercase mb-1">
                           Total Nóminas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo number_format($total_empleados); ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-md-4">
            <div class="card bg-dark text-white border-left-success shadow h-100 py-2">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-light text-uppercase mb-1">
                           Total Pagado
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           RD$ <?php echo number_format($total_general, 2); ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-md-4">
            <div class="card bg-dark text-white border-left-info shadow h-100 py-2">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                           Período Actual
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php
                           if ($mes_filtro) {
                              echo $meses[$mes_filtro] . ' ' . $anio_filtro;
                           } else {
                              echo 'Todos ' . $anio_filtro;
                           }
                           ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Lista de Nóminas -->
      <div class="card shadow">
         <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-light">
               <i class="fas fa-list me-2"></i>Lista de Nóminas
            </h6>
            <div>
               <span class="badge bg-primary"><?php echo count($nominas); ?> registros</span>
            </div>
         </div>
         <div class="card-body">
            <?php if (count($nominas) > 0): ?>
               <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                     <thead class="table-light">
                        <tr>
                           <th>Empleado</th>
                           <th>Cédula</th>
                           <th>Cargo</th>
                           <th>Período</th>
                           <th>Salario Quincenal</th>
                           <th>Horas Extras</th>
                           <th>Incentivos</th>
                           <th>Descuentos</th>
                           <th>Total a Pagar</th>
                           <th>Fecha Pago</th>
                           <th>Acciones</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($nominas as $nomina): ?>
                           <tr>
                              <td>
                                 <strong><?php echo htmlspecialchars($nomina['nombres'] . ' ' . $nomina['apellidos']); ?></strong>
                              </td>
                              <td><?php echo htmlspecialchars($nomina['cedula_pasaporte']); ?></td>
                              <td><?php echo htmlspecialchars($nomina['cargo']); ?></td>
                              <td>
                                 <span class="badge badge-quincena bg-<?php echo $nomina['quincena'] == '15' ? 'info' : 'warning'; ?> text-white">
                                    <?php echo $nomina['quincena'] == '15' ? '1-15' : '16-30'; ?>
                                 </span>
                                 <?php echo $nomina['mes'] . ' ' . $nomina['anio']; ?>
                              </td>
                              <td class="text-end">RD$ <?php echo number_format($nomina['salario_quincenal'], 2); ?></td>
                              <td class="text-end">RD$ <?php echo number_format($nomina['horas_extras'], 2); ?></td>
                              <td class="text-end">RD$ <?php echo number_format($nomina['incentivos'], 2); ?></td>
                              <td class="text-end text-danger">
                                 -RD$ <?php echo number_format($nomina['descuento_prestamo'] + $nomina['descuento_tss'], 2); ?>
                              </td>
                              <td class="text-end fw-bold text-success">
                                 RD$ <?php echo number_format($nomina['total_quincena'], 2); ?>
                              </td>
                              <td><?php echo date('d/m/Y', strtotime($nomina['fecha_pago'])); ?></td>
                              <td>
                                 <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEliminar"
                                    data-id="<?php echo $nomina['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($nomina['nombres'] . ' ' . $nomina['apellidos']); ?>"
                                    data-periodo="<?php echo ($nomina['quincena'] == '15' ? '1-15' : '16-30') . ' ' . $nomina['mes'] . ' ' . $nomina['anio']; ?>">
                                    <i class="fas fa-trash"></i>
                                 </button>
                              </td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                     <tfoot class="table-dark">
                        <tr>
                           <th colspan="8" class="text-end">TOTAL GENERAL:</th>
                           <th class="text-end">RD$ <?php echo number_format($total_general, 2); ?></th>
                           <th colspan="2"></th>
                        </tr>
                     </tfoot>
                  </table>
               </div>
            <?php else: ?>
               <div class="text-center py-5">
                  <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                  <h4 class="text-muted">No hay nóminas registradas</h4>
                  <p class="text-muted">Genera la primera nómina para comenzar</p>
                  <a href="generar_nomina.php" class="btn btn-primary">
                     <i class="fas fa-plus me-2"></i> Generar Nómina
                  </a>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <!-- Modal de Confirmación para Eliminar -->
   <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header bg-danger text-white">
               <h5 class="modal-title">
                  <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
               </h5>
               <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-dark">
               <p>¿Está seguro de eliminar la nómina de <strong id="nombreEmpleado"></strong> y todas las nóminas correspondientes al período <strong id="periodoNomina"></strong>?</p>
               <div class="alert alert-warning">
                  <i class="fas fa-info-circle me-2"></i>
                  <strong>Advertencia:</strong> Esta acción también eliminará el gasto asociado en el libro de gastos y no se puede deshacer.
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-2"></i>Cancelar
               </button>
               <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">
                  <i class="fas fa-trash me-2"></i>Eliminar Nómina
               </a>
            </div>
         </div>
      </div>
   </div>

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const modalEliminar = document.getElementById('modalEliminar');

         modalEliminar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const idNomina = button.getAttribute('data-id');
            const nombreEmpleado = button.getAttribute('data-nombre');
            const periodoNomina = button.getAttribute('data-periodo');

            document.getElementById('nombreEmpleado').textContent = nombreEmpleado;
            document.getElementById('periodoNomina').textContent = periodoNomina;
            document.getElementById('btnConfirmarEliminar').href = `index.php?eliminar_nomina=${idNomina}`;
         });
      });
   </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>