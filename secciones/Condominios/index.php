<?php
$thetitle = "Gestión de Condominios";
include("../../bd.php");

// Lógica de borrado (Si se recibe txID)
if (isset($_GET['txID'])) {
   $txtID = $_GET['txID'];
   $sentencia = $conexion->prepare("DELETE FROM tbl_condominios WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
   header("Location: index.php"); // Redirigir para limpiar la URL
   exit;
}

// Consultar datos
$sentencia = $conexion->prepare("SELECT * FROM tbl_condominios");
$sentencia->execute();
$lista_condominios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<div class="container-fluid px-4 mt-4">

   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="mt-4"><i class="fas fa-building me-2 text-primary"></i>Condominios</h1>
         <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
            <li class="breadcrumb-item active">Listado de Condominios</li>
         </ol>
      </div>
      <a href="crear.php" class="btn btn-primary btn-lg shadow-sm">
         <i class="fas fa-plus me-2"></i> Nuevo Condominio
      </a>
   </div>

   <div class="card shadow mb-4">
      <div class="card-header bg-white py-3">
         <i class="fas fa-table me-1"></i>
         Registros Actuales
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped" id="tablaCondominios" width="100%" cellspacing="0">
               <thead class="table-dark">
                  <tr>
                     <th width="5%" class="text-center">ID</th>
                     <th width="20%">Nombre</th>
                     <th>Ubicación</th>
                     <th>Cuenta Bancaria</th>
                     <th>Teléfono</th>
                     <th class="text-center">Mora</th>
                     <th class="text-center">Gas</th>
                     <th class="text-center">Cuota</th>
                     <th class="text-end">Saldo</th>
                     <th width="15%" class="text-center">Acciones</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($lista_condominios as $registro) { ?>
                     <tr>
                        <td class="text-center align-middle fw-bold"><?php echo $registro['id']; ?></td>
                        <td class="align-middle fw-bold text-primary"><?php echo $registro['nombre']; ?></td>
                        <td class="align-middle small"><?php echo $registro['ubicacion']; ?></td>
                        <td class="align-middle small font-monospace"><?php echo $registro['cuenta_bancaria']; ?></td>
                        <td class="align-middle small"><?php echo $registro['telefono']; ?></td>

                        <td class="text-center align-middle">
                           <?php if (strtolower($registro['mora']) == 'si'): ?>
                              <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                           <?php else: ?>
                              <span class="badge bg-secondary rounded-pill"><i class="fas fa-times"></i></span>
                           <?php endif; ?>
                        </td>

                        <td class="text-center align-middle">
                           <?php if (strtolower($registro['gas']) == 'si'): ?>
                              <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                           <?php else: ?>
                              <span class="badge bg-secondary rounded-pill"><i class="fas fa-times"></i></span>
                           <?php endif; ?>
                        </td>

                        <td class="text-center align-middle">
                           <?php if (strtolower($registro['cuota']) == 'si'): ?>
                              <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>
                           <?php else: ?>
                              <span class="badge bg-secondary rounded-pill"><i class="fas fa-times"></i></span>
                           <?php endif; ?>
                        </td>

                        <td class="text-end align-middle fw-bold text-dark">
                           RD$ <?php echo number_format($registro['saldo_actual'], 2, '.', ','); ?>
                        </td>

                        <td class="text-center align-middle">
                           <div class="btn-group" role="group">
                              <a href="administrar.php?txID=<?php echo $registro['id']; ?>" class="btn btn-outline-info btn-sm" title="Administrar">
                                 <i class="fas fa-cogs"></i>
                              </a>
                              <a href="editar.php?txID=<?php echo $registro['id']; ?>" class="btn btn-outline-warning btn-sm" title="Editar">
                                 <i class="fas fa-pen"></i>
                              </a>
                              <button onclick="borrar(<?php echo $registro['id']; ?>)" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                 <i class="fas fa-trash"></i>
                              </button>
                           </div>
                        </td>
                     </tr>
                  <?php } ?>
               </tbody>
            </table>
         </div>
      </div>
   </div>
</div>

<script>
   $(document).ready(function() {
      $('#tablaCondominios').DataTable({
         "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
         },
         "order": [
            [0, "asc"]
         ], // Ordenar por ID ascendente
         "pageLength": 10
      });
   });

   function borrar(id) {
      Swal.fire({
         title: '¿Está seguro?',
         text: "Se eliminará este condominio y toda su información vinculada.",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, borrar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id;
         }
      })
   }
</script>

<?php include("../../templates/footer.php"); ?>