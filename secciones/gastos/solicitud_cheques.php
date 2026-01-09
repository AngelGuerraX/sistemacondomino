<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// =============================================
// CONFIGURACIÓN INICIAL Y FILTROS
// =============================================

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
   echo "<div class='alert alert-{$_SESSION['tipo_mensaje']} alert-dismissible fade show' role='alert'>
            {$_SESSION['mensaje']}
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
   unset($_SESSION['mensaje']);
   unset($_SESSION['tipo_mensaje']);
}

// Configuración de meses y años
$meses = [
   'Enero',
   'Febrero',
   'Marzo',
   'Abril',
   'Mayo',
   'Junio',
   'Julio',
   'Agosto',
   'Septiembre',
   'Octubre',
   'Noviembre',
   'Diciembre'
];

$anios = range(date('Y') - 1, date('Y') + 1);

// Obtener filtros o usar valores por defecto
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : $meses[date('n') - 1];
$anio_filtro = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$quincena_filtro = isset($_GET['quincena']) ? $_GET['quincena'] : (date('j') <= 15 ? '15' : '30');

$idcondominio = $_SESSION['idcondominio'];

// =============================================
// FUNCIONES MEJORADAS
// =============================================

// Función mejorada para obtener gastos disponibles (EXCLUYE los ya usados)
function obtenerGastosParaCheques($conexion, $idcondominio, $mes, $anio, $quincena)
{
   $sentencia = $conexion->prepare("SELECT g.* 
                                    FROM tbl_gastos g 
                                    WHERE g.id_condominio = :id 
                                    AND g.mes = :mes 
                                    AND g.anio = :anio
                                    AND g.quincena = :quincena
                                    AND g.id NOT IN (
                                        SELECT dsc.id_gasto 
                                        FROM tbl_detalle_solicitud_cheques dsc
                                        INNER JOIN tbl_solicitudes_cheques sc ON dsc.id_solicitud = sc.id
                                        WHERE sc.id_condominio = :id_condominio
                                        AND sc.estado != 'rechazado'
                                    )
                                    ORDER BY g.tipo_gasto, g.detalles");

   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   $sentencia->bindParam(":quincena", $quincena);
   $sentencia->execute();
   return $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener gastos ya usados (para mostrar en la tabla)
function obtenerTodosLosGastos($conexion, $idcondominio, $mes, $anio, $quincena)
{
   $sentencia = $conexion->prepare("SELECT g.*,
                                    CASE WHEN EXISTS (
                                        SELECT 1 FROM tbl_detalle_solicitud_cheques dsc 
                                        INNER JOIN tbl_solicitudes_cheques sc ON dsc.id_solicitud = sc.id
                                        WHERE dsc.id_gasto = g.id AND sc.estado != 'rechazado'
                                    ) THEN 1 ELSE 0 END as ya_usado
                                    FROM tbl_gastos g 
                                    WHERE g.id_condominio = :id 
                                    AND g.mes = :mes 
                                    AND g.anio = :anio
                                    AND g.quincena = :quincena
                                    ORDER BY g.tipo_gasto, g.detalles");

   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   $sentencia->bindParam(":quincena", $quincena);
   $sentencia->execute();
   return $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

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

// Función para obtener información básica de la solicitud
function obtenerInfoSolicitud($conexion, $id_solicitud, $idcondominio)
{
   $sentencia = $conexion->prepare("SELECT * FROM tbl_solicitudes_cheques 
                                   WHERE id = :id_solicitud AND id_condominio = :id_condominio");

   $sentencia->bindParam(":id_solicitud", $id_solicitud);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   return $sentencia->fetch(PDO::FETCH_ASSOC);
}

// =============================================
// PROCESAR SOLICITUD DE CHEQUES
// =============================================
if ($_POST && isset($_POST['solicitar_cheques'])) {
   $descripcion_general = isset($_POST["descripcion_general"]) ? $_POST["descripcion_general"] : "";
   $quincena_solicitud = isset($_POST["quincena_solicitud"]) ? $_POST["quincena_solicitud"] : "";
   $fecha_solicitud = isset($_POST["fecha_solicitud"]) ? $_POST["fecha_solicitud"] : date('Y-m-d');
   $solicitado_por = isset($_POST["solicitado_por"]) ? $_POST["solicitado_por"] : $_SESSION['usuario'];

   try {
      $conexion->beginTransaction();

      // Generar número de solicitud único
      $numero_solicitud = "SOL-" . date('Ymd') . "-" . rand(1000, 9999);

      // Insertar solicitud principal
      $sentencia_solicitud = $conexion->prepare("INSERT INTO tbl_solicitudes_cheques 
                                                (numero_solicitud, descripcion_general, total_general, quincena_solicitud, fecha_solicitud, id_condominio, solicitado_por) 
                                                VALUES (:numero_solicitud, :descripcion_general, :total_general, :quincena_solicitud, :fecha_solicitud, :id_condominio, :solicitado_por)");

      $total_general = 0;
      $sentencia_solicitud->bindParam(":numero_solicitud", $numero_solicitud);
      $sentencia_solicitud->bindParam(":descripcion_general", $descripcion_general);
      $sentencia_solicitud->bindParam(":total_general", $total_general);
      $sentencia_solicitud->bindParam(":quincena_solicitud", $quincena_solicitud);
      $sentencia_solicitud->bindParam(":fecha_solicitud", $fecha_solicitud);
      $sentencia_solicitud->bindParam(":id_condominio", $idcondominio);
      $sentencia_solicitud->bindParam(":solicitado_por", $solicitado_por);
      $sentencia_solicitud->execute();

      $id_solicitud = $conexion->lastInsertId();

      // Procesar gastos seleccionados
      if (isset($_POST['gastos_seleccionados']) && is_array($_POST['gastos_seleccionados'])) {
         foreach ($_POST['gastos_seleccionados'] as $id_gasto) {
            // Obtener información del gasto
            $sentencia_gasto = $conexion->prepare("SELECT * FROM tbl_gastos WHERE id = :id_gasto");
            $sentencia_gasto->bindParam(":id_gasto", $id_gasto);
            $sentencia_gasto->execute();
            $gasto = $sentencia_gasto->fetch(PDO::FETCH_ASSOC);

            if ($gasto) {
               // Generar número de cheque único
               $numero_cheque = "CHQ-" . date('Ymd') . "-" . rand(100, 999);

               // Insertar detalle de la solicitud
               $sentencia_detalle = $conexion->prepare("INSERT INTO tbl_detalle_solicitud_cheques 
                                                      (id_solicitud, id_gasto, numero_cheque, tipo_gasto, detalles, monto, quincena_gasto) 
                                                      VALUES (:id_solicitud, :id_gasto, :numero_cheque, :tipo_gasto, :detalles, :monto, :quincena_gasto)");

               $sentencia_detalle->bindParam(":id_solicitud", $id_solicitud);
               $sentencia_detalle->bindParam(":id_gasto", $id_gasto);
               $sentencia_detalle->bindParam(":numero_cheque", $numero_cheque);
               $sentencia_detalle->bindParam(":tipo_gasto", $gasto['tipo_gasto']);
               $sentencia_detalle->bindParam(":detalles", $gasto['detalles']);
               $sentencia_detalle->bindParam(":monto", $gasto['monto']);
               $sentencia_detalle->bindParam(":quincena_gasto", $gasto['quincena']);
               $sentencia_detalle->execute();

               $total_general += floatval($gasto['monto']);
            }
         }
      }

      // Actualizar el total general en la solicitud
      $sentencia_actualizar_total = $conexion->prepare("UPDATE tbl_solicitudes_cheques SET total_general = :total_general WHERE id = :id_solicitud");
      $sentencia_actualizar_total->bindParam(":total_general", $total_general);
      $sentencia_actualizar_total->bindParam(":id_solicitud", $id_solicitud);
      $sentencia_actualizar_total->execute();

      $conexion->commit();

      $_SESSION['mensaje'] = "✅ Solicitud de cheques #$numero_solicitud creada correctamente - Total: RD$ " . number_format($total_general, 2);
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes_filtro . "&anio=" . $anio_filtro . "&quincena=" . $quincena_filtro);
      exit;
   } catch (Exception $e) {
      $conexion->rollBack();
      $_SESSION['mensaje'] = "❌ Error al crear solicitud de cheques: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// ELIMINAR SOLICITUD DE CHEQUES
// =============================================
if (isset($_GET['eliminar_solicitud'])) {
   $id_solicitud = $_GET['eliminar_solicitud'];

   try {
      $conexion->beginTransaction();

      // Obtener información de la solicitud antes de eliminar (para el mensaje)
      $solicitud_info = obtenerInfoSolicitud($conexion, $id_solicitud, $idcondominio);

      if ($solicitud_info) {
         // 1. Eliminar los detalles de la solicitud (cheques)
         $sentencia_eliminar_detalles = $conexion->prepare("DELETE FROM tbl_detalle_solicitud_cheques WHERE id_solicitud = :id_solicitud");
         $sentencia_eliminar_detalles->bindParam(":id_solicitud", $id_solicitud);
         $sentencia_eliminar_detalles->execute();

         // 2. Eliminar la solicitud principal
         $sentencia_eliminar_solicitud = $conexion->prepare("DELETE FROM tbl_solicitudes_cheques WHERE id = :id_solicitud AND id_condominio = :id_condominio");
         $sentencia_eliminar_solicitud->bindParam(":id_solicitud", $id_solicitud);
         $sentencia_eliminar_solicitud->bindParam(":id_condominio", $idcondominio);
         $sentencia_eliminar_solicitud->execute();

         $conexion->commit();

         $_SESSION['mensaje'] = "✅ Solicitud #{$solicitud_info['numero_solicitud']} eliminada correctamente. Los gastos ahora están disponibles para nuevas solicitudes.";
         $_SESSION['tipo_mensaje'] = "success";
      } else {
         $_SESSION['mensaje'] = "❌ No se encontró la solicitud a eliminar.";
         $_SESSION['tipo_mensaje'] = "danger";
      }

      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes_filtro . "&anio=" . $anio_filtro . "&quincena=" . $quincena_filtro);
      exit;
   } catch (Exception $e) {
      $conexion->rollBack();
      $_SESSION['mensaje'] = "❌ Error al eliminar solicitud: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes_filtro . "&anio=" . $anio_filtro . "&quincena=" . $quincena_filtro);
      exit;
   }
}

// =============================================
// OBTENER GASTOS MEJORADO
// =============================================

// Gastos disponibles para nuevos cheques (EXCLUYENDO usados)
$gastos_disponibles = obtenerGastosParaCheques($conexion, $idcondominio, $mes_filtro, $anio_filtro, $quincena_filtro);

// Todos los gastos (para mostrar en la tabla con estado)
$todos_los_gastos = obtenerTodosLosGastos($conexion, $idcondominio, $mes_filtro, $anio_filtro, $quincena_filtro);

// Agrupar gastos por tipo
$gastos_por_tipo = [];
$tipos_gastos = [
   'Nomina_Empleados' => 'Nómina Empleados',
   'Servicios_Basicos' => 'Servicios Básicos',
   'Gastos_Menores_Material_Gastable' => 'Gastos Menores, Material Gastable',
   'Imprevistos' => 'Imprevistos',
   'Cargos_Bancarios' => 'Cargos Bancarios',
   'Servicios_Igualados' => 'Servicios Igualados'
];

foreach ($todos_los_gastos as $gasto) {
   $tipo = $gasto['tipo_gasto'];
   if (!isset($gastos_por_tipo[$tipo])) {
      $gastos_por_tipo[$tipo] = [];
   }
   $gastos_por_tipo[$tipo][] = $gasto;
}

// =============================================
// OBTENER SOLICITUDES EXISTENTES
// =============================================
function obtenerSolicitudesCheques($conexion, $idcondominio, $mes = null, $anio = null)
{
   $query = "SELECT s.*, 
             COUNT(d.id) as cantidad_cheques,
             (SELECT COUNT(*) FROM tbl_detalle_solicitud_cheques d2 WHERE d2.id_solicitud = s.id) as total_cheques
             FROM tbl_solicitudes_cheques s
             LEFT JOIN tbl_detalle_solicitud_cheques d ON s.id = d.id_solicitud
             WHERE s.id_condominio = :id_condominio";

   if ($mes && $anio) {
      $query .= " AND s.quincena_solicitud IN ('15', '30')";
   }

   $query .= " GROUP BY s.id ORDER BY s.fecha_creacion DESC";

   $sentencia = $conexion->prepare($query);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   return $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

$solicitudes_existentes = obtenerSolicitudesCheques($conexion, $idcondominio);
?>

<br>
<div class="card">
   <div class="card-header text-center bg-dark text-white">
      <h2>🏦 SOLICITUD DE CHEQUES</h2>
   </div>
   <div class="card-body">

      <!-- FILTROS DE MES, AÑO Y QUINCENA -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="card">
               <div class="card-header bg-secondary text-white">
                  <h5 class="mb-0">🔍 Filtros de Búsqueda</h5>
               </div>
               <div class="card-body">
                  <form action="" method="GET" class="row g-3">
                     <div class="col-md-4">
                        <label for="mes" class="form-label fw-bold">Mes:</label>
                        <select name="mes" id="mes" class="form-select">
                           <?php foreach ($meses as $mes): ?>
                              <option value="<?php echo $mes; ?>" <?php echo $mes_filtro == $mes ? 'selected' : ''; ?>>
                                 <?php echo $mes; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="anio" class="form-label fw-bold">Año:</label>
                        <select name="anio" id="anio" class="form-select">
                           <?php foreach ($anios as $anio_option): ?>
                              <option value="<?php echo $anio_option; ?>" <?php echo $anio_filtro == $anio_option ? 'selected' : ''; ?>>
                                 <?php echo $anio_option; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="quincena" class="form-label fw-bold">Quincena:</label>
                        <select name="quincena" id="quincena" class="form-select">
                           <option value="15" <?php echo $quincena_filtro == '15' ? 'selected' : ''; ?>>Quincena 1-15</option>
                           <option value="30" <?php echo $quincena_filtro == '30' ? 'selected' : ''; ?>>Quincena 16-30</option>
                        </select>
                     </div>
                     <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>

      <!-- INFORMACIÓN DEL FILTRO ACTUAL -->
      <div class="alert alert-info">
         <strong>
            📅 Mostrando gastos de: <?php echo $mes_filtro . ' ' . $anio_filtro; ?> -
            Quincena <?php echo $quincena_filtro == '15' ? '1-15' : '16-30'; ?>
         </strong>
         <span class="badge bg-primary ms-2">
            <?php echo count($gastos_disponibles); ?> gastos disponibles
         </span>
         <span class="badge bg-secondary ms-2">
            <?php echo count($todos_los_gastos); ?> gastos totales
         </span>
      </div>

      <!-- BOTÓN NUEVA SOLICITUD -->
      <div class="row mb-4">
         <div class="col-md-12">
            <?php if (count($gastos_disponibles) > 0): ?>
               <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalSolicitudCheques">
                  🏦 NUEVA SOLICITUD DE CHEQUES
               </button>
            <?php else: ?>
               <div class="alert alert-warning">
                  <strong>⚠️ No hay gastos disponibles</strong>
                  <p class="mb-0">No se encontraron gastos disponibles para el período seleccionado. Verifique los filtros o registre gastos primero.</p>
               </div>
            <?php endif; ?>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- MODAL SOLICITUD DE CHEQUES MEJORADO -->
      <!-- ============================================= -->
      <div class="modal fade" id="modalSolicitudCheques" tabindex="-1" aria-labelledby="modalSolicitudChequesLabel" aria-hidden="true">
         <div class="modal-dialog modal-xl">
            <div class="modal-content">
               <div class="modal-header bg-success text-white">
                  <h2 class="modal-title" id="modalSolicitudChequesLabel">🏦 NUEVA SOLICITUD DE CHEQUES</h2>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               <div class="modal-body">
                  <form action="" method="post" id="formSolicitudCheques">
                     <input type="hidden" name="solicitar_cheques" value="1">

                     <!-- Información de la Solicitud -->
                     <div class="row mb-4">
                        <div class="col-md-6">
                           <label for="descripcion_general" class="form-label fw-bold">Descripción General:</label>
                           <input type="text" class="form-control" name="descripcion_general" id="descripcion_general"
                              value="Solicitud de cheques - <?php echo $mes_filtro . ' ' . $anio_filtro . ' - Quincena ' . ($quincena_filtro == '15' ? '1-15' : '16-30'); ?>"
                              required>
                        </div>
                        <div class="col-md-3">
                           <label for="quincena_solicitud" class="form-label fw-bold">Quincena:</label>
                           <select name="quincena_solicitud" id="quincena_solicitud" class="form-select" required>
                              <option value="15" <?php echo $quincena_filtro == '15' ? 'selected' : ''; ?>>Quincena 1-15</option>
                              <option value="30" <?php echo $quincena_filtro == '30' ? 'selected' : ''; ?>>Quincena 16-30</option>
                           </select>
                        </div>
                        <div class="col-md-3">
                           <label for="fecha_solicitud" class="form-label fw-bold">Fecha Solicitud:</label>
                           <input type="date" class="form-control" name="fecha_solicitud" id="fecha_solicitud"
                              value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                     </div>

                     <!-- TABLA MEJORADA DE GASTOS -->
                     <div class="card">
                        <div class="card-header bg-primary text-white">
                           <h5 class="mb-0">📋 Gastos Disponibles para Cheques</h5>
                           <small class="text-warning">⚠️ Los gastos marcados como "YA USADO" no se pueden seleccionar</small>
                        </div>
                        <div class="card-body">
                           <div class="table-responsive">
                              <table class="table table-striped">
                                 <thead>
                                    <tr>
                                       <th width="50">
                                          <input type="checkbox" id="seleccionar_todos" onchange="seleccionarTodos(this)">
                                          <br><small class="text-muted">Todos</small>
                                       </th>
                                       <th>Tipo</th>
                                       <th>Detalles</th>
                                       <th>Monto</th>
                                       <th>Estado</th>
                                    </tr>
                                 </thead>
                                 <tbody>
                                    <?php
                                    $total_disponible = 0;
                                    $total_todos = 0;

                                    foreach ($gastos_por_tipo as $tipo_key => $gastos_tipo):
                                       $tipo_nombre = $tipos_gastos[$tipo_key] ?? $tipo_key;
                                    ?>
                                       <tr class="table-active">
                                          <td colspan="5" class="fw-bold"><?php echo $tipo_nombre; ?></td>
                                       </tr>
                                       <?php foreach ($gastos_tipo as $gasto):
                                          $ya_usado = $gasto['ya_usado'] == 1;
                                          $total_todos += floatval($gasto['monto']);
                                          if (!$ya_usado) {
                                             $total_disponible += floatval($gasto['monto']);
                                          }
                                       ?>
                                          <tr class="<?php echo $ya_usado ? 'table-secondary' : ''; ?>">
                                             <td>
                                                <?php if (!$ya_usado): ?>
                                                   <input type="checkbox" name="gastos_seleccionados[]"
                                                      value="<?php echo $gasto['id']; ?>"
                                                      class="gasto-checkbox"
                                                      onchange="calcularTotal()">
                                                <?php else: ?>
                                                   <span class="text-danger" title="Este gasto ya fue usado en otra solicitud">❌</span>
                                                <?php endif; ?>
                                             </td>
                                             <td><?php echo $tipo_nombre; ?></td>
                                             <td>
                                                <?php echo $gasto['detalles']; ?>
                                                <?php if ($ya_usado): ?>
                                                   <br><small class="text-danger">⚠️ Ya usado en solicitud anterior</small>
                                                <?php endif; ?>
                                             </td>
                                             <td class="fw-bold">RD$ <?php echo number_format($gasto['monto'], 2, '.', ','); ?></td>
                                             <td>
                                                <?php if ($ya_usado): ?>
                                                   <span class="badge bg-danger">YA USADO</span>
                                                <?php else: ?>
                                                   <span class="badge bg-success">DISPONIBLE</span>
                                                <?php endif; ?>
                                             </td>
                                          </tr>
                                       <?php endforeach; ?>
                                    <?php endforeach; ?>
                                 </tbody>
                                 <tfoot class="table-dark">
                                    <tr>
                                       <td colspan="3" class="text-end fw-bold">Total General:</td>
                                       <td class="fw-bold">RD$ <?php echo number_format($total_todos, 2, '.', ','); ?></td>
                                       <td></td>
                                    </tr>
                                    <tr>
                                       <td colspan="3" class="text-end fw-bold">Total Disponible:</td>
                                       <td class="fw-bold text-success">RD$ <?php echo number_format($total_disponible, 2, '.', ','); ?></td>
                                       <td></td>
                                    </tr>
                                    <tr>
                                       <td colspan="3" class="text-end fw-bold">Total Seleccionado:</td>
                                       <td id="total_seleccionado" class="fw-bold text-warning">RD$ 0.00</td>
                                       <td></td>
                                    </tr>
                                 </tfoot>
                              </table>
                           </div>
                        </div>
                     </div>

                     <!-- Campo oculto para solicitado por -->
                     <input type="hidden" name="solicitado_por" value="<?php echo $_SESSION['usuario']; ?>">
                  </form>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" form="formSolicitudCheques" class="btn btn-success btn-lg" id="btnSolicitarCheques">
                     🏦 SOLICITAR CHEQUES
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- MODAL DETALLE DE SOLICITUD -->
      <!-- ============================================= -->
      <div class="modal fade" id="modalDetalleSolicitud" tabindex="-1" aria-labelledby="modalDetalleSolicitudLabel" aria-hidden="true">
         <div class="modal-dialog modal-lg">
            <div class="modal-content">
               <div class="modal-header bg-primary text-white">
                  <h2 class="modal-title" id="modalDetalleSolicitudLabel">📋 DETALLE DE SOLICITUD</h2>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               <div class="modal-body" id="contenidoDetalleSolicitud">
                  <!-- El contenido se carga dinámicamente via JavaScript -->
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- SOLICITUDES EXISTENTES -->
      <!-- ============================================= -->
      <?php if (count($solicitudes_existentes) > 0): ?>
         <div class="card mt-4">
            <div class="card-header bg-dark text-white">
               <h4 class="mb-0">📋 Solicitudes de Cheques Existentes</h4>
            </div>
            <div class="card-body">
               <div class="table-responsive">
                  <table class="table table-striped">
                     <thead>
                        <tr>
                           <th># Solicitud</th>
                           <th>Descripción</th>
                           <th>Quincena</th>
                           <th>Fecha</th>
                           <th>Total</th>
                           <th>Estado</th>
                           <th>Acciones</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($solicitudes_existentes as $solicitud): ?>
                           <tr>
                              <td class="fw-bold"><?php echo $solicitud['numero_solicitud']; ?></td>
                              <td><?php echo $solicitud['descripcion_general']; ?></td>
                              <td>
                                 <span class="badge bg-<?php echo $solicitud['quincena_solicitud'] == '15' ? 'info' : 'warning'; ?>">
                                    <?php echo $solicitud['quincena_solicitud'] == '15' ? '1-15' : '16-30'; ?>
                                 </span>
                              </td>
                              <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></td>
                              <td class="fw-bold text-success">RD$ <?php echo number_format($solicitud['total_general'], 2, '.', ','); ?></td>
                              <td>
                                 <span class="badge bg-<?php
                                                         echo $solicitud['estado'] == 'aprobado' ? 'success' : ($solicitud['estado'] == 'rechazado' ? 'danger' : 'warning');
                                                         ?>">
                                    <?php echo ucfirst($solicitud['estado']); ?>
                                 </span>
                              </td>
                              <td>
                                 <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary"
                                       onclick="verDetalleSolicitud(<?php echo $solicitud['id']; ?>)"
                                       title="Ver detalle">
                                       👁️ Ver
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                       onclick="confirmarEliminarSolicitud(<?php echo $solicitud['id']; ?>, '<?php echo $solicitud['numero_solicitud']; ?>')"
                                       title="Eliminar solicitud">
                                       🗑️ Eliminar
                                    </button>
                                 </div>
                              </td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      <?php endif; ?>

   </div>
</div>
<br>

<script>
   // Función para seleccionar/deseleccionar todos los checkboxes disponibles
   function seleccionarTodos(source) {
      const checkboxes = document.querySelectorAll('.gasto-checkbox:not([disabled])');
      checkboxes.forEach(checkbox => {
         checkbox.checked = source.checked;
      });
      calcularTotal();
   }

   // Función para calcular el total seleccionado
   function calcularTotal() {
      const checkboxes = document.querySelectorAll('.gasto-checkbox:checked');
      let total = 0;

      checkboxes.forEach(checkbox => {
         const row = checkbox.closest('tr');
         const montoText = row.querySelector('td:nth-child(4)').textContent;
         const monto = parseFloat(montoText.replace('RD$', '').replace(/,/g, '').trim());
         total += monto;
      });

      document.getElementById('total_seleccionado').textContent = 'RD$ ' + total.toLocaleString('es-DO', {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2
      });

      // Validar que al menos un gasto esté seleccionado
      const btnSolicitar = document.getElementById('btnSolicitarCheques');
      if (btnSolicitar) {
         btnSolicitar.disabled = checkboxes.length === 0;
         if (checkboxes.length === 0) {
            btnSolicitar.classList.remove('btn-success');
            btnSolicitar.classList.add('btn-secondary');
         } else {
            btnSolicitar.classList.remove('btn-secondary');
            btnSolicitar.classList.add('btn-success');
         }
      }
   }

   // Función para ver detalle de solicitud
   function verDetalleSolicitud(idSolicitud) {
      // Mostrar loading
      document.getElementById('contenidoDetalleSolicitud').innerHTML = `
         <div class="text-center">
            <div class="spinner-border text-primary" role="status">
               <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles de la solicitud...</p>
         </div>
      `;

      // Abrir modal
      const modal = new bootstrap.Modal(document.getElementById('modalDetalleSolicitud'));
      modal.show();

      // Cargar detalles via AJAX
      fetch(`obtener_detalle_solicitud.php?id_solicitud=${idSolicitud}`)
         .then(response => response.text())
         .then(html => {
            document.getElementById('contenidoDetalleSolicitud').innerHTML = html;
         })
         .catch(error => {
            document.getElementById('contenidoDetalleSolicitud').innerHTML = `
               <div class="alert alert-danger">
                  <strong>Error:</strong> No se pudieron cargar los detalles de la solicitud.
                  <br>${error}
               </div>
            `;
         });
   }

   // Función para confirmar eliminación de solicitud
   function confirmarEliminarSolicitud(idSolicitud, numeroSolicitud) {
      Swal.fire({
         title: '¿Estás seguro?',
         html: `Vas a eliminar la solicitud <strong>${numeroSolicitud}</strong><br><br>
                <span class="text-warning">⚠️ Esta acción liberará todos los gastos para que puedan usarse en nuevas solicitudes.</span>`,
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, eliminar',
         cancelButtonText: 'Cancelar',
         reverseButtons: true
      }).then((result) => {
         if (result.isConfirmed) {
            // Redirigir para eliminar
            window.location.href = `?eliminar_solicitud=${idSolicitud}&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&quincena=<?php echo $quincena_filtro; ?>`;
         }
      });
   }

   // Inicializar el total al cargar la página
   document.addEventListener('DOMContentLoaded', function() {
      calcularTotal();

      // Validar checkboxes al abrir el modal
      const modal = document.getElementById('modalSolicitudCheques');
      if (modal) {
         modal.addEventListener('show.bs.modal', function() {
            setTimeout(calcularTotal, 100);
         });
      }
   });
</script>

<?php include("../../templates/footer.php"); ?>