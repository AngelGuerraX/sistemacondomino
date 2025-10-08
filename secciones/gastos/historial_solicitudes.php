<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// =============================================
// CAMBIAR ESTADO DE SOLICITUD
// =============================================
if (isset($_GET['cambiar_estado'])) {
   $id_solicitud = $_GET['cambiar_estado'];
   $nuevo_estado = $_GET['estado'];
   $id_condominio = $_SESSION['idcondominio'];

   try {
      $sentencia = $conexion->prepare("UPDATE tbl_solicitudes_cheques 
                                       SET estado = :estado 
                                       WHERE id = :id 
                                       AND id_condominio = :id_condominio");
      $sentencia->bindParam(":estado", $nuevo_estado);
      $sentencia->bindParam(":id", $id_solicitud);
      $sentencia->bindParam(":id_condominio", $id_condominio);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Estado de solicitud actualizado";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al actualizar estado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: historial_solicitudes.php");
   exit;
}

// =============================================
// OBTENER SOLICITUDES
// =============================================
$idcondominio = $_SESSION['idcondominio'];

// Filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : '';
$filtro_anio = isset($_GET['anio']) ? $_GET['anio'] : '';

// Construir consulta con filtros
$sql = "SELECT * FROM tbl_solicitudes_cheques WHERE id_condominio = :id_condominio";
$params = [':id_condominio' => $idcondominio];

if ($filtro_estado !== 'todos') {
   $sql .= " AND estado = :estado";
   $params[':estado'] = $filtro_estado;
}

if ($filtro_mes) {
   $sql .= " AND MONTH(fecha_solicitud) = :mes";
   $params[':mes'] = $filtro_mes;
}

if ($filtro_anio) {
   $sql .= " AND YEAR(fecha_solicitud) = :anio";
   $params[':anio'] = $filtro_anio;
}

$sql .= " ORDER BY fecha_creacion DESC";

$sentencia_solicitudes = $conexion->prepare($sql);
foreach ($params as $key => $value) {
   $sentencia_solicitudes->bindValue($key, $value);
}
$sentencia_solicitudes->execute();
$solicitudes = $sentencia_solicitudes->fetchAll(PDO::FETCH_ASSOC);

// Obtener detalles para cada solicitud
foreach ($solicitudes as &$solicitud) {
   $sentencia_detalles = $conexion->prepare("
        SELECT d.*, 
               GROUP_CONCAT(DISTINCT d.numero_cheque) as cheques,
               COUNT(DISTINCT d.numero_cheque) as total_cheques,
               COUNT(*) as total_gastos
        FROM tbl_detalle_solicitud_cheques d 
        WHERE d.id_solicitud = :id_solicitud 
        GROUP BY d.id_solicitud
    ");
   $sentencia_detalles->bindParam(":id_solicitud", $solicitud['id']);
   $sentencia_detalles->execute();
   $detalles = $sentencia_detalles->fetch(PDO::FETCH_ASSOC);

   $solicitud['detalles'] = $detalles ?: [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Historial de Solicitudes de Cheques</title>
   <style>
      .estado-pendiente {
         background-color: #fff3cd;
         border-color: #ffeaa7;
      }

      .estado-aprobado {
         background-color: #d1ecf1;
         border-color: #bee5eb;
      }

      .estado-rechazado {
         background-color: #f8d7da;
         border-color: #f5c6cb;
      }

      .badge-estado {
         font-size: 0.8em;
      }
   </style>
</head>

<body>
   <div class="container-fluid">
      <br>

      <!-- Header -->
      <div class="card">
         <div class="card-header text-center bg-info text-white">
            <h2>📋 HISTORIAL DE SOLICITUDES DE CHEQUES</h2>
         </div>
      </div>

      <br>

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
      <div class="card mb-4">
         <div class="card-header bg-light">
            <h5 class="mb-0">🔍 Filtros de Búsqueda</h5>
         </div>
         <div class="card-body">
            <form action="" method="GET" class="row g-3">
               <div class="col-md-3">
                  <label class="form-label fw-bold">Estado:</label>
                  <select name="estado" class="form-select">
                     <option value="todos">Todos los estados</option>
                     <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                     <option value="aprobado" <?php echo $filtro_estado == 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                     <option value="rechazado" <?php echo $filtro_estado == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label fw-bold">Mes:</label>
                  <select name="mes" class="form-select">
                     <option value="">Todos los meses</option>
                     <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $filtro_mes == $i ? 'selected' : ''; ?>>
                           <?php echo DateTime::createFromFormat('!m', $i)->format('F'); ?>
                        </option>
                     <?php endfor; ?>
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label fw-bold">Año:</label>
                  <select name="anio" class="form-select">
                     <option value="">Todos los años</option>
                     <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $filtro_anio == $i ? 'selected' : ''; ?>>
                           <?php echo $i; ?>
                        </option>
                     <?php endfor; ?>
                  </select>
               </div>
               <div class="col-md-3 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary w-100">Filtrar</button>
               </div>
            </form>
         </div>
      </div>

      <!-- Botón nueva solicitud -->
      <div class="d-flex justify-content-between mb-3">
         <a href="index.php" class="btn btn-secondary">
            ← Volver a Gastos
         </a>
         <a href="solicitud_cheques.php" class="btn btn-success">
            ➕ Nueva Solicitud de Cheques
         </a>
      </div>

      <!-- Lista de solicitudes -->
      <div class="card">
         <div class="card-header bg-dark text-white">
            <h5 class="mb-0">📄 Solicitudes de Cheques</h5>
         </div>
         <div class="card-body">
            <?php if (count($solicitudes) > 0): ?>
               <div class="table-responsive">
                  <table class="table table-striped">
                     <thead>
                        <tr>
                           <th>N° Solicitud</th>
                           <th>Fecha</th>
                           <th>Descripción</th>
                           <th>Quincena</th>
                           <th>Cheques</th>
                           <th>Total</th>
                           <th>Estado</th>
                           <th>Solicitado por</th>
                           <th>Acciones</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                           <tr>
                              <td>
                                 <strong><?php echo $solicitud['numero_solicitud']; ?></strong>
                              </td>
                              <td>
                                 <?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?>
                              </td>
                              <td>
                                 <?php echo $solicitud['descripcion_general'] ?: '<em class="text-muted">Sin descripción</em>'; ?>
                              </td>
                              <td>
                                 <span class="badge bg-info">
                                    <?php echo $solicitud['quincena_solicitud'] == '15' ? '1-15' : '16-30'; ?>
                                 </span>
                              </td>
                              <td>
                                 <?php if (!empty($solicitud['detalles'])): ?>
                                    <small><?php echo $solicitud['detalles']['total_cheques']; ?> cheques</small>
                                 <?php else: ?>
                                    <small class="text-muted">0 cheques</small>
                                 <?php endif; ?>
                              </td>
                              <td>
                                 <strong class="text-success">
                                    RD$ <?php echo number_format($solicitud['total_general'], 2, '.', ','); ?>
                                 </strong>
                              </td>
                              <td>
                                 <span class="badge badge-estado 
                                                <?php echo $solicitud['estado'] == 'pendiente' ? 'bg-warning text-dark' : ($solicitud['estado'] == 'aprobado' ? 'bg-success' : 'bg-danger'); ?>">
                                    <?php echo ucfirst($solicitud['estado']); ?>
                                 </span>
                              </td>
                              <td>
                                 <small><?php echo $solicitud['solicitado_por']; ?></small>
                              </td>
                              <td>
                                 <div class="btn-group btn-group-sm">
                                    <!-- Botón Ver Detalles -->
                                    <button class="btn btn-info btn-sm"
                                       data-bs-toggle="modal"
                                       data-bs-target="#modalDetalles"
                                       data-solicitud-id="<?php echo $solicitud['id']; ?>"
                                       data-numero="<?php echo $solicitud['numero_solicitud']; ?>"
                                       title="Ver detalles">
                                       👁️
                                    </button>

                                    <!-- Botones Cambiar Estado -->
                                    <?php if ($solicitud['estado'] == 'pendiente'): ?>
                                       <a href="?cambiar_estado=<?php echo $solicitud['id']; ?>&estado=aprobado"
                                          class="btn btn-success btn-sm"
                                          title="Marcar como Aprobado"
                                          onclick="return confirm('¿Marcar esta solicitud como APROBADA?')">
                                          ✅
                                       </a>
                                       <a href="?cambiar_estado=<?php echo $solicitud['id']; ?>&estado=rechazado"
                                          class="btn btn-danger btn-sm"
                                          title="Marcar como Rechazado"
                                          onclick="return confirm('¿Marcar esta solicitud como RECHAZADA?')">
                                          ❌
                                       </a>
                                    <?php else: ?>
                                       <a href="?cambiar_estado=<?php echo $solicitud['id']; ?>&estado=pendiente"
                                          class="btn btn-warning btn-sm"
                                          title="Volver a Pendiente"
                                          onclick="return confirm('¿Volver a marcar como PENDIENTE?')">
                                          ⏳
                                       </a>
                                    <?php endif; ?>
                                 </div>
                              </td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            <?php else: ?>
               <div class="text-center text-muted py-5">
                  <h4>No hay solicitudes de cheques</h4>
                  <p>Comienza creando tu primera solicitud de cheques</p>
                  <a href="solicitud_cheques.php" class="btn btn-success btn-lg">
                     ➕ Crear Primera Solicitud
                  </a>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <br>

   <!-- Modal Ver Detalles -->
   <div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header bg-primary text-white">
               <h2 class="modal-title" id="modalDetallesLabel">Detalles de Solicitud</h2>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detallesSolicitud">
               <!-- Los detalles se cargarán aquí via JavaScript -->
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
         </div>
      </div>
   </div>

   <script>
      // Modal de detalles
      document.addEventListener('DOMContentLoaded', function() {
         const modalDetalles = document.getElementById('modalDetalles');

         modalDetalles.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const solicitudId = button.getAttribute('data-solicitud-id');
            const numeroSolicitud = button.getAttribute('data-numero');

            // Cargar detalles via AJAX
            cargarDetallesSolicitud(solicitudId, numeroSolicitud);
         });
      });

      function cargarDetallesSolicitud(solicitudId, numeroSolicitud) {
         const detallesContainer = document.getElementById('detallesSolicitud');
         detallesContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando detalles...</p>
                </div>
            `;

         fetch(`detalles_solicitud.php?id=${solicitudId}`)
            .then(response => response.text())
            .then(html => {
               detallesContainer.innerHTML = html;
            })
            .catch(error => {
               detallesContainer.innerHTML = `
                        <div class="alert alert-danger">
                            Error al cargar los detalles: ${error}
                        </div>
                    `;
            });
      }
   </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>