<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// Obtener configuración del condominio
$sentencia_condominio = $conexion->prepare("SELECT porcentaje_ars, porcentaje_afp FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$config_tss = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);

// Filtros
$filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : date('n');
$filtro_anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$filtro_quincena = isset($_GET['quincena']) ? $_GET['quincena'] : '15';

// Obtener nóminas históricas
$sentencia_nominas = $conexion->prepare("
    SELECT n.*, e.nombres, e.apellidos, e.cedula_pasaporte, e.cargo
    FROM tbl_nomina n
    INNER JOIN tbl_empleados e ON n.id_empleado = e.id
    WHERE n.id_condominio = :id_condominio 
    AND n.mes = :mes 
    AND n.anio = :anio 
    AND n.quincena = :quincena
    ORDER BY e.nombres
");
$sentencia_nominas->bindParam(":id_condominio", $idcondominio);
$sentencia_nominas->bindParam(":mes", $filtro_mes);
$sentencia_nominas->bindParam(":anio", $filtro_anio);
$sentencia_nominas->bindParam(":quincena", $filtro_quincena);
$sentencia_nominas->execute();
$nominas = $sentencia_nominas->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_nomina = 0;
foreach ($nominas as $nomina) {
   $total_nomina += floatval($nomina['total_quincena']);
}

// Meses para filtro
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

// Años disponibles
$anios = range(2020, date('Y') + 1);
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Gestión de Nómina</title>
   <style>
      .card-nomina {
         border-left: 4px solid #0d6efd;
      }

      .badge-quincena-15 {
         background-color: #0d6efd;
      }

      .badge-quincena-30 {
         background-color: #fd7e14;
      }

      .table-hover tbody tr:hover {
         background-color: rgba(0, 0, 0, 0.075);
      }

      .estadistica-card {
         transition: transform 0.2s ease-in-out;
      }

      .estadistica-card:hover {
         transform: translateY(-2px);
      }
   </style>
</head>

<body>
   <div class="container-fluid py-4">
      <!-- Header -->
      <div class="row mb-4">
         <div class="col-12">
            <div class="card bg-light shadow">
               <div class="card-body">
                  <div class="row align-items-center">
                     <div class="col-md-8">
                        <h1 class="h2 mb-1">💰 Gestión de Nómina</h1>
                        <p class="mb-0 opacity-8">Administración completa de pagos a empleados</p>
                     </div>
                     <div class="col-md-4 text-end">
                        <div class="bg-white bg-opacity-20 rounded p-3 d-inline-block">
                           <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Tarjetas de Información -->
      <div class="row mb-4">
         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 estadistica-card">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                           Total Nómina Actual</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           RD$ <?php echo number_format($total_nomina, 2, '.', ','); ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 estadistica-card">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                           Empleados Activos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php
                           $sentencia_empleados = $conexion->prepare("SELECT COUNT(*) as total FROM tbl_empleados WHERE idcondominio = :id_condominio AND activo = 'si'");
                           $sentencia_empleados->bindParam(":id_condominio", $idcondominio);
                           $sentencia_empleados->execute();
                           $empleados = $sentencia_empleados->fetch(PDO::FETCH_ASSOC);
                           echo $empleados['total'];
                           ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 estadistica-card">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                           % ARS (TSS)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo $config_tss['porcentaje_ars'] ?? '3.04'; ?>%
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 estadistica-card">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                           % AFP (TSS)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo $config_tss['porcentaje_afp'] ?? '0.87'; ?>%
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-percent fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Filtros y Botones -->
      <div class="row mb-4">
         <div class="col-md-8">
            <div class="card bg-dark text-white shadow h-100">
               <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
               </div>
               <div class="card-body bg-light text-dark">
                  <form action="" method="GET" class="row g-3">
                     <div class="col-md-3">
                        <label class="form-label fw-bold">Mes:</label>
                        <select name="mes" class="form-select">
                           <?php foreach ($meses as $numero => $nombre): ?>
                              <option value="<?php echo $numero; ?>" <?php echo $filtro_mes == $numero ? 'selected' : ''; ?>>
                                 <?php echo $nombre; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label class="form-label fw-bold">Año:</label>
                        <select name="anio" class="form-select">
                           <?php foreach ($anios as $anio): ?>
                              <option value="<?php echo $anio; ?>" <?php echo $filtro_anio == $anio ? 'selected' : ''; ?>>
                                 <?php echo $anio; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label class="form-label fw-bold">Quincena:</label>
                        <select name="quincena" class="form-select">
                           <option value="15" <?php echo $filtro_quincena == '15' ? 'selected' : ''; ?>>Quincena 1-15</option>
                           <option value="30" <?php echo $filtro_quincena == '30' ? 'selected' : ''; ?>>Quincena 16-30</option>
                        </select>
                     </div>
                     <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                           <i class="fas fa-search me-1"></i> Buscar
                        </button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
         <div class="col-md-4">
            <div class="card shadow  h-100">
               <div class="card-body d-flex flex-column justify-content-center">
                  <a href="generar_nomina.php" class="btn btn-success btn-lg mb-3">
                     <i class="fas fa-plus-circle me-2"></i> Generar Nueva Nómina
                  </a>
                  <a href="../empleados/index.php" class="btn btn-outline-primary">
                     <i class="fas fa-users me-2"></i> Gestionar Empleados
                  </a>
               </div>
            </div>
         </div>
      </div>

      <!-- Lista de Nóminas -->
      <div class="card shadow">
         <div class="card-header bg-dark py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
               <i class="fas fa-history me-2"></i>Nómina <?php echo $meses[$filtro_mes] . ' ' . $filtro_anio; ?>
               - <span class="badge badge-quincena-<?php echo $filtro_quincena; ?>">
                  Quincena <?php echo $filtro_quincena == '15' ? '1-15' : '16-30'; ?>
               </span>
            </h6>
            <span class="badge bg-dark fs-6">
               Total: RD$ <?php echo number_format($total_nomina, 2, '.', ','); ?>
            </span>
         </div>
         <div class="card-body">
            <?php if (count($nominas) > 0): ?>
               <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="tablaNominas" width="100%" cellspacing="0">
                     <thead class="table-light">
                        <tr>
                           <th>Empleado</th>
                           <th>Cédula</th>
                           <th>Cargo</th>
                           <th class="text-end">Salario Q.</th>
                           <th class="text-end">Horas Extras</th>
                           <th class="text-end">Incentivos</th>
                           <th class="text-end">Desc. Préstamo</th>
                           <th class="text-end">Desc. TSS</th>
                           <th class="text-end">Total a Pagar</th>
                           <th>Fecha Pago</th>
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
                              <td class="text-end">RD$ <?php echo number_format($nomina['salario_quincenal'], 2, '.', ','); ?></td>
                              <td class="text-end">RD$ <?php echo number_format($nomina['horas_extras'], 2, '.', ','); ?></td>
                              <td class="text-end">RD$ <?php echo number_format($nomina['incentivos'], 2, '.', ','); ?></td>
                              <td class="text-end text-danger">RD$ <?php echo number_format($nomina['descuento_prestamo'], 2, '.', ','); ?></td>
                              <td class="text-end text-danger">RD$ <?php echo number_format($nomina['descuento_tss'], 2, '.', ','); ?></td>
                              <td class="text-end fw-bold text-success">
                                 RD$ <?php echo number_format($nomina['total_quincena'], 2, '.', ','); ?>
                              </td>
                              <td><?php echo date('d/m/Y', strtotime($nomina['fecha_pago'])); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                     <tfoot class="table-dark">
                        <tr>
                           <th colspan="3" class="text-end">TOTALES:</th>
                           <th class="text-end">RD$ <?php echo number_format(array_sum(array_column($nominas, 'salario_quincenal')), 2, '.', ','); ?></th>
                           <th class="text-end">RD$ <?php echo number_format(array_sum(array_column($nominas, 'horas_extras')), 2, '.', ','); ?></th>
                           <th class="text-end">RD$ <?php echo number_format(array_sum(array_column($nominas, 'incentivos')), 2, '.', ','); ?></th>
                           <th class="text-end">RD$ <?php echo number_format(array_sum(array_column($nominas, 'descuento_prestamo')), 2, '.', ','); ?></th>
                           <th class="text-end">RD$ <?php echo number_format(array_sum(array_column($nominas, 'descuento_tss')), 2, '.', ','); ?></th>
                           <th class="text-end">RD$ <?php echo number_format($total_nomina, 2, '.', ','); ?></th>
                           <th></th>
                        </tr>
                     </tfoot>
                  </table>
               </div>
            <?php else: ?>
               <div class="text-center py-5">
                  <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                  <h4 class="text-muted">No hay nóminas registradas</h4>
                  <p class="text-muted">No se encontraron registros de nómina para el período seleccionado</p>
                  <a href="generar_nomina.php" class="btn btn-success btn-lg">
                     <i class="fas fa-plus-circle me-2"></i> Generar Primera Nómina
                  </a>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Inicializar DataTable
         $('#tablaNominas').DataTable({
            language: {
               url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            responsive: true,
            order: [
               [0, 'asc']
            ],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
         });
      });
   </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>