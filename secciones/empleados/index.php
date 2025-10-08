<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// =============================================
// PROCESAR NUEVO EMPLEADO
// =============================================
if ($_POST && isset($_POST['nombres'])) {
   $nombres = $_POST['nombres'];
   $apellidos = $_POST['apellidos'];
   $cedula_pasaporte = $_POST['cedula_pasaporte'];
   $telefono = $_POST['telefono'];
   $cargo = $_POST['cargo'];
   $horario = $_POST['horario'];
   $fecha_ingreso = $_POST['fecha_ingreso'];
   $salario = $_POST['salario'];
   $forma_de_pago = $_POST['forma_de_pago'];
   $tiene_afp_ars = isset($_POST['tiene_afp_ars']) ? 'si' : 'no';
   $activo = isset($_POST['activo']) ? 'si' : 'no';

   try {
      // Verificar si ya existe un empleado con la misma cédula
      $sentencia_verificar = $conexion->prepare("SELECT id FROM tbl_empleados WHERE cedula_pasaporte = :cedula AND idcondominio = :id_condominio");
      $sentencia_verificar->bindParam(":cedula", $cedula_pasaporte);
      $sentencia_verificar->bindParam(":id_condominio", $idcondominio);
      $sentencia_verificar->execute();

      if ($sentencia_verificar->fetch()) {
         $_SESSION['mensaje'] = "⚠️ Ya existe un empleado con esta cédula/pasaporte";
         $_SESSION['tipo_mensaje'] = "warning";
      } else {
         $sentencia = $conexion->prepare("INSERT INTO tbl_empleados 
                (nombres, apellidos, cedula_pasaporte, telefono, cargo, horario, fecha_ingreso, salario, forma_de_pago, idcondominio, tiene_afp_ars, activo) 
                VALUES (:nombres, :apellidos, :cedula_pasaporte, :telefono, :cargo, :horario, :fecha_ingreso, :salario, :forma_de_pago, :idcondominio, :tiene_afp_ars, :activo)");

         $sentencia->bindParam(":nombres", $nombres);
         $sentencia->bindParam(":apellidos", $apellidos);
         $sentencia->bindParam(":cedula_pasaporte", $cedula_pasaporte);
         $sentencia->bindParam(":telefono", $telefono);
         $sentencia->bindParam(":cargo", $cargo);
         $sentencia->bindParam(":horario", $horario);
         $sentencia->bindParam(":fecha_ingreso", $fecha_ingreso);
         $sentencia->bindParam(":salario", $salario);
         $sentencia->bindParam(":forma_de_pago", $forma_de_pago);
         $sentencia->bindParam(":idcondominio", $idcondominio);
         $sentencia->bindParam(":tiene_afp_ars", $tiene_afp_ars);
         $sentencia->bindParam(":activo", $activo);
         $sentencia->execute();

         $_SESSION['mensaje'] = "✅ Empleado agregado correctamente";
         $_SESSION['tipo_mensaje'] = "success";
      }

      header("Location: index.php");
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al agregar empleado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// ACTUALIZAR EMPLEADO
// =============================================
if ($_POST && isset($_POST['editar_empleado'])) {
   $id = $_POST['id'];
   $nombres = $_POST['nombres'];
   $apellidos = $_POST['apellidos'];
   $cedula_pasaporte = $_POST['cedula_pasaporte'];
   $telefono = $_POST['telefono'];
   $cargo = $_POST['cargo'];
   $horario = $_POST['horario'];
   $fecha_ingreso = $_POST['fecha_ingreso'];
   $salario = $_POST['salario'];
   $forma_de_pago = $_POST['forma_de_pago'];
   $tiene_afp_ars = isset($_POST['tiene_afp_ars']) ? 'si' : 'no';
   $activo = isset($_POST['activo']) ? 'si' : 'no';

   try {
      // Verificar si la cédula ya existe en otro empleado
      $sentencia_verificar = $conexion->prepare("SELECT id FROM tbl_empleados WHERE cedula_pasaporte = :cedula AND id != :id AND idcondominio = :id_condominio");
      $sentencia_verificar->bindParam(":cedula", $cedula_pasaporte);
      $sentencia_verificar->bindParam(":id", $id);
      $sentencia_verificar->bindParam(":id_condominio", $idcondominio);
      $sentencia_verificar->execute();

      if ($sentencia_verificar->fetch()) {
         $_SESSION['mensaje'] = "⚠️ Ya existe otro empleado con esta cédula/pasaporte";
         $_SESSION['tipo_mensaje'] = "warning";
      } else {
         $sentencia = $conexion->prepare("UPDATE tbl_empleados SET 
                nombres = :nombres,
                apellidos = :apellidos,
                cedula_pasaporte = :cedula_pasaporte,
                telefono = :telefono,
                cargo = :cargo,
                horario = :horario,
                fecha_ingreso = :fecha_ingreso,
                salario = :salario,
                forma_de_pago = :forma_de_pago,
                tiene_afp_ars = :tiene_afp_ars,
                activo = :activo
                WHERE id = :id AND idcondominio = :id_condominio");

         $sentencia->bindParam(":nombres", $nombres);
         $sentencia->bindParam(":apellidos", $apellidos);
         $sentencia->bindParam(":cedula_pasaporte", $cedula_pasaporte);
         $sentencia->bindParam(":telefono", $telefono);
         $sentencia->bindParam(":cargo", $cargo);
         $sentencia->bindParam(":horario", $horario);
         $sentencia->bindParam(":fecha_ingreso", $fecha_ingreso);
         $sentencia->bindParam(":salario", $salario);
         $sentencia->bindParam(":forma_de_pago", $forma_de_pago);
         $sentencia->bindParam(":tiene_afp_ars", $tiene_afp_ars);
         $sentencia->bindParam(":activo", $activo);
         $sentencia->bindParam(":id", $id);
         $sentencia->bindParam(":id_condominio", $idcondominio);
         $sentencia->execute();

         $_SESSION['mensaje'] = "✅ Empleado actualizado correctamente";
         $_SESSION['tipo_mensaje'] = "success";
      }

      header("Location: index.php");
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al actualizar empleado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// ELIMINAR EMPLEADO
// =============================================
if (isset($_GET['eliminar'])) {
   $id = $_GET['eliminar'];

   try {
      $sentencia = $conexion->prepare("DELETE FROM tbl_empleados WHERE id = :id AND idcondominio = :id_condominio");
      $sentencia->bindParam(":id", $id);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Empleado eliminado correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al eliminar empleado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: index.php");
   exit;
}

// =============================================
// OBTENER LISTA DE EMPLEADOS
// =============================================
$sentencia_empleados = $conexion->prepare("
    SELECT * FROM tbl_empleados 
    WHERE idcondominio = :id_condominio 
    ORDER BY activo DESC, nombres, apellidos
");
$sentencia_empleados->bindParam(":id_condominio", $idcondominio);
$sentencia_empleados->execute();
$empleados = $sentencia_empleados->fetchAll(PDO::FETCH_ASSOC);

// Contar empleados activos e inactivos
$empleados_activos = 0;
$empleados_inactivos = 0;
$total_salarios = 0;

foreach ($empleados as $empleado) {
   if ($empleado['activo'] == 'si') {
      $empleados_activos++;
      $total_salarios += $empleado['salario'];
   } else {
      $empleados_inactivos++;
   }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Gestión de Empleados</title>
   <style>
      .empleado-activo {
         border-left: 4px solid #28a745;
      }

      .empleado-inactivo {
         border-left: 4px solid #dc3545;
      }

      .badge-activo {
         background-color: #28a745;
      }

      .badge-inactivo {
         background-color: #6c757d;
      }

      .badge-tss {
         background-color: #17a2b8;
      }

      .card-empleado {
         transition: transform 0.2s ease-in-out;
      }

      .card-empleado:hover {
         transform: translateY(-2px);
      }

      .table-hover tbody tr:hover {
         background-color: rgba(0, 0, 0, 0.075);
      }
   </style>
</head>

<body>
   <div class="container-fluid py-4">
      <!-- Header -->
      <div class="row mb-4">
         <div class="col-12">
            <div class="card bg-gradient-primary text-dark shadow">
               <div class="card-body">
                  <div class="row align-items-center">
                     <div class="col-md-8">
                        <h1 class="h2 mb-1">👥 Gestión de Empleados</h1>
                        <p class="mb-0 opacity-8">Administra la información de los empleados del condominio</p>
                     </div>
                     <div class="col-md-4 text-end">
                        <div class="bg-white bg-opacity-20 rounded p-3 d-inline-block">
                           <i class="fas fa-users fa-2x"></i>
                        </div>
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

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 card-empleado">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                           Total Empleados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo count($empleados); ?>
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
            <div class="card border-left-success shadow h-100 py-2 card-empleado">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                           Empleados Activos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo $empleados_activos; ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 card-empleado">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                           Empleados Inactivos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           <?php echo $empleados_inactivos; ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 card-empleado">
               <div class="card-body">
                  <div class="row no-gutters align-items-center">
                     <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                           Total Nómina Mensual</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           RD$ <?php echo number_format($total_salarios, 2, '.', ','); ?>
                        </div>
                     </div>
                     <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Botones Principales -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="d-flex justify-content-between">
               <div>
                  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoEmpleado">
                     <i class="fas fa-plus-circle me-2"></i> Nuevo Empleado
                  </button>
                  <a href="../nomina/index.php" class="btn btn-primary">
                     <i class="fas fa-money-bill-wave me-2"></i> Ir a Nómina
                  </a>
               </div>
               <div>
                  <button type="button" class="btn btn-outline-secondary" id="btnVerActivos">
                     <i class="fas fa-eye me-2"></i> Ver Activos
                  </button>
                  <button type="button" class="btn btn-outline-secondary" id="btnVerTodos">
                     <i class="fas fa-list me-2"></i> Ver Todos
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- Lista de Empleados -->
      <div class="card shadow">
         <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
               <i class="fas fa-list me-2"></i>Lista de Empleados
            </h6>
            <span class="badge bg-primary">
               <?php echo count($empleados); ?> empleados
            </span>
         </div>
         <div class="card-body">
            <?php if (count($empleados) > 0): ?>
               <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="tablaEmpleados" width="100%" cellspacing="0">
                     <thead class="table-light">
                        <tr>
                           <th>Nombre Completo</th>
                           <th>Cédula</th>
                           <th>Cargo</th>
                           <th>Teléfono</th>
                           <th>Salario Mensual</th>
                           <th>Fecha Ingreso</th>
                           <th>Estado</th>
                           <th>TSS</th>
                           <th>Acciones</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($empleados as $empleado):
                           $clase_fila = $empleado['activo'] == 'si' ? 'empleado-activo' : 'empleado-inactivo';
                        ?>
                           <tr class="<?php echo $clase_fila; ?>">
                              <td>
                                 <strong><?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?></strong>
                                 <?php if ($empleado['activo'] != 'si'): ?>
                                    <br><small class="text-muted">(Inactivo)</small>
                                 <?php endif; ?>
                              </td>
                              <td><?php echo htmlspecialchars($empleado['cedula_pasaporte']); ?></td>
                              <td><?php echo htmlspecialchars($empleado['cargo']); ?></td>
                              <td><?php echo htmlspecialchars($empleado['telefono']); ?></td>
                              <td class="text-end">
                                 <strong>RD$ <?php echo number_format($empleado['salario'], 2, '.', ','); ?></strong>
                              </td>
                              <td>
                                 <?php
                                 if ($empleado['fecha_ingreso']) {
                                    echo date('d/m/Y', strtotime($empleado['fecha_ingreso']));
                                 } else {
                                    echo '<span class="text-muted">No especificada</span>';
                                 }
                                 ?>
                              </td>
                              <td>
                                 <span class="badge <?php echo $empleado['activo'] == 'si' ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $empleado['activo'] == 'si' ? 'Activo' : 'Inactivo'; ?>
                                 </span>
                              </td>
                              <td>
                                 <span class="badge <?php echo $empleado['tiene_afp_ars'] == 'si' ? 'badge-tss' : 'bg-secondary'; ?>">
                                    TSS: <?php echo $empleado['tiene_afp_ars'] == 'si' ? 'Sí' : 'No'; ?>
                                 </span>
                              </td>
                              <td>
                                 <div class="btn-group btn-group-sm">
                                    <!-- Botón Editar -->
                                    <button class="btn btn-outline-primary btn-sm"
                                       data-bs-toggle="modal"
                                       data-bs-target="#modalEditarEmpleado"
                                       data-id="<?php echo $empleado['id']; ?>"
                                       data-nombres="<?php echo htmlspecialchars($empleado['nombres']); ?>"
                                       data-apellidos="<?php echo htmlspecialchars($empleado['apellidos']); ?>"
                                       data-cedula="<?php echo htmlspecialchars($empleado['cedula_pasaporte']); ?>"
                                       data-telefono="<?php echo htmlspecialchars($empleado['telefono']); ?>"
                                       data-cargo="<?php echo htmlspecialchars($empleado['cargo']); ?>"
                                       data-horario="<?php echo htmlspecialchars($empleado['horario']); ?>"
                                       data-fecha-ingreso="<?php echo $empleado['fecha_ingreso']; ?>"
                                       data-salario="<?php echo $empleado['salario']; ?>"
                                       data-forma-pago="<?php echo htmlspecialchars($empleado['forma_de_pago']); ?>"
                                       data-tiene-afp-ars="<?php echo $empleado['tiene_afp_ars']; ?>"
                                       data-activo="<?php echo $empleado['activo']; ?>"
                                       title="Editar empleado">
                                       <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Botón Eliminar -->
                                    <button class="btn btn-outline-danger btn-sm"
                                       onclick="confirmarEliminacion(<?php echo $empleado['id']; ?>, '<?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?>')"
                                       title="Eliminar empleado">
                                       <i class="fas fa-trash"></i>
                                    </button>
                                 </div>
                              </td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <div class="text-center py-5">
                  <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                  <h4 class="text-muted">No hay empleados registrados</h4>
                  <p class="text-muted">Comienza agregando el primer empleado al sistema</p>
                  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoEmpleado">
                     <i class="fas fa-plus-circle me-2"></i> Agregar Primer Empleado
                  </button>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <!-- ============================================= -->
   <!-- MODAL NUEVO EMPLEADO -->
   <!-- ============================================= -->
   <div class="modal fade" id="modalNuevoEmpleado" tabindex="-1" aria-labelledby="modalNuevoEmpleadoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header bg-success text-white">
               <h2 class="modal-title" id="modalNuevoEmpleadoLabel">
                  <i class="fas fa-user-plus me-2"></i>Nuevo Empleado
               </h2>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-dark">
               <form action="" method="post" id="formNuevoEmpleado">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Nombres:</label>
                        <input type="text" class="form-control" name="nombres" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Apellidos:</label>
                        <input type="text" class="form-control" name="apellidos" required>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Cédula/Pasaporte:</label>
                        <input type="text" class="form-control" name="cedula_pasaporte" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Teléfono:</label>
                        <input type="text" class="form-control" name="telefono">
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo:</label>
                        <input type="text" class="form-control" name="cargo" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Horario:</label>
                        <input type="text" class="form-control" name="horario" placeholder="Ej: Lunes a Viernes 8:00 AM - 5:00 PM">
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha de Ingreso:</label>
                        <input type="date" class="form-control" name="fecha_ingreso">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Forma de Pago:</label>
                        <select class="form-select" name="forma_de_pago" required>
                           <option value="">Seleccionar...</option>
                           <option value="Efectivo">Efectivo</option>
                           <option value="Transferencia">Transferencia</option>
                           <option value="Cheque">Cheque</option>
                           <option value="Depósito">Depósito</option>
                        </select>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Salario Mensual (RD$):</label>
                        <input type="number" step="0.01" class="form-control" name="salario" required>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Opciones:</label>
                        <div class="mt-2">
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" name="tiene_afp_ars" id="tiene_afp_ars" checked>
                              <label class="form-check-label" for="tiene_afp_ars">
                                 Aplica TSS (AFP/ARS)
                              </label>
                           </div>
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
                              <label class="form-check-label" for="activo">
                                 Empleado Activo
                              </label>
                           </div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>
            <div class="modal-footer bg-success text-white">
               <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" form="formNuevoEmpleado" class="btn btn-primary">💾 Guardar Empleado</button>
            </div>
         </div>
      </div>
   </div>

   <!-- ============================================= -->
   <!-- MODAL EDITAR EMPLEADO -->
   <!-- ============================================= -->
   <div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-labelledby="modalEditarEmpleadoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header bg-primary">
               <h2 class="modal-title" id="modalEditarEmpleadoLabel">
                  <i class="fas fa-user-edit me-2"></i>Editar Empleado
               </h2>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-dark">
               <form action="" method="post" id="formEditarEmpleado">
                  <input type="hidden" name="editar_empleado" value="1">
                  <input type="hidden" name="id" id="empleado_id">

                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Nombres:</label>
                        <input type="text" class="form-control" name="nombres" id="empleado_nombres" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Apellidos:</label>
                        <input type="text" class="form-control" name="apellidos" id="empleado_apellidos" required>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Cédula/Pasaporte:</label>
                        <input type="text" class="form-control" name="cedula_pasaporte" id="empleado_cedula" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Teléfono:</label>
                        <input type="text" class="form-control" name="telefono" id="empleado_telefono">
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo:</label>
                        <input type="text" class="form-control" name="cargo" id="empleado_cargo" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Horario:</label>
                        <input type="text" class="form-control" name="horario" id="empleado_horario" placeholder="Ej: Lunes a Viernes 8:00 AM - 5:00 PM">
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha de Ingreso:</label>
                        <input type="date" class="form-control" name="fecha_ingreso" id="empleado_fecha_ingreso">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label fw-bold">Forma de Pago:</label>
                        <select class="form-select" name="forma_de_pago" id="empleado_forma_pago" required>
                           <option value="">Seleccionar...</option>
                           <option value="Efectivo">Efectivo</option>
                           <option value="Transferencia">Transferencia</option>
                           <option value="Cheque">Cheque</option>
                           <option value="Depósito">Depósito</option>
                        </select>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Salario Mensual (RD$):</label>
                        <input type="number" step="0.01" class="form-control" name="salario" id="empleado_salario" required>
                     </div>

                     <div class="col-md-6">
                        <label class="form-label fw-bold">Opciones:</label>
                        <div class="mt-2">
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" name="tiene_afp_ars" id="empleado_tiene_afp_ars">
                              <label class="form-check-label" for="empleado_tiene_afp_ars">
                                 Aplica TSS (AFP/ARS)
                              </label>
                           </div>
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" name="activo" id="empleado_activo">
                              <label class="form-check-label" for="empleado_activo">
                                 Empleado Activo
                              </label>
                           </div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>
            <div class="modal-footer bg-primary text-white">
               <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" form="formEditarEmpleado" class="btn btn-success">💾 Guardar Cambios</button>
            </div>
         </div>
      </div>
   </div>

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Inicializar DataTable
         $('#tablaEmpleados').DataTable({
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

         // Modal de edición
         const modalEditarEmpleado = document.getElementById('modalEditarEmpleado');
         modalEditarEmpleado.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            // Obtener datos del empleado
            document.getElementById('empleado_id').value = button.getAttribute('data-id');
            document.getElementById('empleado_nombres').value = button.getAttribute('data-nombres');
            document.getElementById('empleado_apellidos').value = button.getAttribute('data-apellidos');
            document.getElementById('empleado_cedula').value = button.getAttribute('data-cedula');
            document.getElementById('empleado_telefono').value = button.getAttribute('data-telefono');
            document.getElementById('empleado_cargo').value = button.getAttribute('data-cargo');
            document.getElementById('empleado_horario').value = button.getAttribute('data-horario');
            document.getElementById('empleado_fecha_ingreso').value = button.getAttribute('data-fecha-ingreso');
            document.getElementById('empleado_salario').value = button.getAttribute('data-salario');
            document.getElementById('empleado_forma_pago').value = button.getAttribute('data-forma-pago');

            // Checkboxes
            document.getElementById('empleado_tiene_afp_ars').checked = button.getAttribute('data-tiene-afp-ars') === 'si';
            document.getElementById('empleado_activo').checked = button.getAttribute('data-activo') === 'si';
         });

         // Filtros de vista
         document.getElementById('btnVerActivos').addEventListener('click', function() {
            $('#tablaEmpleados').DataTable().search('Activo').draw();
         });

         document.getElementById('btnVerTodos').addEventListener('click', function() {
            $('#tablaEmpleados').DataTable().search('').draw();
         });
      });
      // ... (código anterior se mantiene igual)

      function confirmarEliminacion(id, nombre) {
         Swal.fire({
            title: '¿Eliminar empleado?',
            html: `Esta acción eliminará permanentemente al empleado:<br><strong>${nombre}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            focusCancel: true
         }).then((result) => {
            if (result.isConfirmed) {
               window.location.href = `index.php?eliminar=${id}`;
            }
         });
      }

      // Validación de formularios
      document.getElementById('formNuevoEmpleado')?.addEventListener('submit', function(e) {
         const cedula = this.querySelector('input[name="cedula_pasaporte"]').value;
         const salario = this.querySelector('input[name="salario"]').value;

         if (!validarCedula(cedula)) {
            e.preventDefault();
            Swal.fire('Error', 'Por favor ingrese una cédula/pasaporte válida', 'error');
            return;
         }

         if (salario <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'El salario debe ser mayor a 0', 'error');
            return;
         }
      });

      document.getElementById('formEditarEmpleado')?.addEventListener('submit', function(e) {
         const cedula = this.querySelector('input[name="cedula_pasaporte"]').value;
         const salario = this.querySelector('input[name="salario"]').value;

         if (!validarCedula(cedula)) {
            e.preventDefault();
            Swal.fire('Error', 'Por favor ingrese una cédula/pasaporte válida', 'error');
            return;
         }

         if (salario <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'El salario debe ser mayor a 0', 'error');
            return;
         }
      });

      function validarCedula(cedula) {
         return cedula.trim().length >= 3; // Validación básica
      }

      // Formatear inputs de salario
      document.querySelectorAll('input[name="salario"]').forEach(input => {
         input.addEventListener('blur', function() {
            if (this.value) {
               this.value = parseFloat(this.value).toFixed(2);
            }
         });

         input.addEventListener('focus', function() {
            this.select();
         });
      });

      // Auto-completar fecha actual en nuevo empleado
      document.getElementById('modalNuevoEmpleado')?.addEventListener('show.bs.modal', function() {
         const fechaInput = this.querySelector('input[name="fecha_ingreso"]');
         if (!fechaInput.value) {
            fechaInput.valueAsDate = new Date();
         }
      });

      // Mostrar/ocultar filas inactivas
      let mostrarInactivos = true;

      document.getElementById('btnVerActivos')?.addEventListener('click', function() {
         mostrarInactivos = false;
         $('#tablaEmpleados').DataTable().column(6).search('Activo').draw();
         this.classList.add('active');
         document.getElementById('btnVerTodos').classList.remove('active');
      });

      document.getElementById('btnVerTodos')?.addEventListener('click', function() {
         mostrarInactivos = true;
         $('#tablaEmpleados').DataTable().column(6).search('').draw();
         this.classList.add('active');
         document.getElementById('btnVerActivos').classList.remove('active');
      });

      // Inicializar botón activo
      document.getElementById('btnVerTodos').classList.add('active');
   </script>

   <?php include("../../templates/footer.php"); ?>