<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// =============================================
// PROCESAR SOLICITUD DE CHEQUES
// =============================================
if ($_POST && isset($_POST['guardar_solicitud'])) {
   $descripcion_general = isset($_POST["descripcion_general"]) ? $_POST["descripcion_general"] : "";
   $quincena_solicitud = isset($_POST["quincena_solicitud"]) ? $_POST["quincena_solicitud"] : "15";
   $fecha_solicitud = isset($_POST["fecha_solicitud"]) ? $_POST["fecha_solicitud"] : date('Y-m-d');
   $id_condominio = $_SESSION['idcondominio'];
   $solicitado_por = $_SESSION['usuario'];

   try {
      // Generar número de solicitud automático
      $numero_solicitud = "SOL-" . date('Ymd-His');

      // Calcular total general
      $total_general = 0;
      if (isset($_POST['cheques'])) {
         foreach ($_POST['cheques'] as $cheque) {
            if (isset($cheque['gastos']) && is_array($cheque['gastos'])) {
               foreach ($cheque['gastos'] as $gasto_id) {
                  // Obtener monto del gasto
                  $sentencia_gasto = $conexion->prepare("SELECT monto FROM tbl_gastos WHERE id = :id");
                  $sentencia_gasto->bindParam(":id", $gasto_id);
                  $sentencia_gasto->execute();
                  $gasto_data = $sentencia_gasto->fetch(PDO::FETCH_ASSOC);
                  $total_general += floatval($gasto_data['monto']);
               }
            }
         }
      }

      // Insertar solicitud principal
      $sentencia_solicitud = $conexion->prepare("INSERT INTO tbl_solicitudes_cheques 
            (numero_solicitud, descripcion_general, total_general, quincena_solicitud, fecha_solicitud, id_condominio, solicitado_por) 
            VALUES (:numero_solicitud, :descripcion_general, :total_general, :quincena_solicitud, :fecha_solicitud, :id_condominio, :solicitado_por)");

      $sentencia_solicitud->bindParam(":numero_solicitud", $numero_solicitud);
      $sentencia_solicitud->bindParam(":descripcion_general", $descripcion_general);
      $sentencia_solicitud->bindParam(":total_general", $total_general);
      $sentencia_solicitud->bindParam(":quincena_solicitud", $quincena_solicitud);
      $sentencia_solicitud->bindParam(":fecha_solicitud", $fecha_solicitud);
      $sentencia_solicitud->bindParam(":id_condominio", $id_condominio);
      $sentencia_solicitud->bindParam(":solicitado_por", $solicitado_por);
      $sentencia_solicitud->execute();

      $id_solicitud = $conexion->lastInsertId();

      // Insertar detalles de cheques
      if (isset($_POST['cheques']) && $id_solicitud) {
         foreach ($_POST['cheques'] as $index => $cheque) {
            $numero_cheque = "CHQ-" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            if (isset($cheque['gastos']) && is_array($cheque['gastos'])) {
               foreach ($cheque['gastos'] as $gasto_id) {
                  // Obtener datos del gasto
                  $sentencia_gasto = $conexion->prepare("SELECT tipo_gasto, detalles, monto, quincena FROM tbl_gastos WHERE id = :id");
                  $sentencia_gasto->bindParam(":id", $gasto_id);
                  $sentencia_gasto->execute();
                  $gasto_data = $sentencia_gasto->fetch(PDO::FETCH_ASSOC);

                  if ($gasto_data) {
                     $sentencia_detalle = $conexion->prepare("INSERT INTO tbl_detalle_solicitud_cheques 
                                (id_solicitud, id_gasto, numero_cheque, tipo_gasto, detalles, monto, quincena_gasto) 
                                VALUES (:id_solicitud, :id_gasto, :numero_cheque, :tipo_gasto, :detalles, :monto, :quincena_gasto)");

                     $sentencia_detalle->bindParam(":id_solicitud", $id_solicitud);
                     $sentencia_detalle->bindParam(":id_gasto", $gasto_id);
                     $sentencia_detalle->bindParam(":numero_cheque", $numero_cheque);
                     $sentencia_detalle->bindParam(":tipo_gasto", $gasto_data['tipo_gasto']);
                     $sentencia_detalle->bindParam(":detalles", $gasto_data['detalles']);
                     $sentencia_detalle->bindParam(":monto", $gasto_data['monto']);
                     $sentencia_detalle->bindParam(":quincena_gasto", $gasto_data['quincena']);
                     $sentencia_detalle->execute();
                  }
               }
            }
         }
      }

      $_SESSION['mensaje'] = "✅ Solicitud de cheques guardada correctamente (N°: $numero_solicitud)";
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: historial_solicitudes.php");
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al guardar la solicitud: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// OBTENER GASTOS PARA LA SOLICITUD
// =============================================
$idcondominio = $_SESSION['idcondominio'];
$mes_actual = date('F');
$anio_actual = date('Y');

// Obtener todos los gastos del mes actual (ambas quincenas)
$sentencia_gastos = $conexion->prepare("SELECT * FROM tbl_gastos 
                                      WHERE id_condominio = :id_condominio 
                                      AND mes = :mes 
                                      AND anio = :anio
                                      ORDER BY quincena, tipo_gasto, detalles");
$sentencia_gastos->bindParam(":id_condominio", $idcondominio);
$sentencia_gastos->bindParam(":mes", $mes_actual);
$sentencia_gastos->bindParam(":anio", $anio_actual);
$sentencia_gastos->execute();
$gastos = $sentencia_gastos->fetchAll(PDO::FETCH_ASSOC);

// Agrupar gastos por tipo para mejor organización
$gastos_por_tipo = [];
$tipos_gastos = [
   'Nomina_Empleados' => 'Nómina Empleados',
   'Servicios_Basicos' => 'Servicios Básicos',
   'Gastos_Menores_Material_Gastable' => 'Gastos Menores, Material Gastable',
   'lmprevistos' => 'Imprevistos',
   'Cargos_Bancarios' => 'Cargos Bancarios',
   'Servicios_lgualados' => 'Servicios Igualados'
];

foreach ($gastos as $gasto) {
   $tipo_nombre = $tipos_gastos[$gasto['tipo_gasto']] ?? $gasto['tipo_gasto'];
   $gastos_por_tipo[$tipo_nombre][] = $gasto;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Solicitud de Cheques</title>
   <style>
      .gasto-item {
         transition: all 0.3s ease;
         border-left: 4px solid transparent;
      }

      .gasto-item:hover {
         background-color: #f8f9fa;
      }

      .gasto-item.quincena-15 {
         border-left-color: #0d6efd;
      }

      .gasto-item.quincena-30 {
         border-left-color: #fd7e14;
      }

      .cheque-card {
         border: 2px solid #dee2e6;
         border-radius: 10px;
         transition: all 0.3s ease;
      }

      .cheque-card:hover {
         border-color: #0d6efd;
      }

      .gasto-seleccionado {
         background-color: #e7f3ff !important;
         border: 1px solid #0d6efd !important;
      }

      .badge-quincena-15 {
         background-color: #0d6efd;
      }

      .badge-quincena-30 {
         background-color: #fd7e14;
      }
   </style>
</head>

<body>
   <div class="container-fluid">
      <br>

      <!-- Header -->
      <div class="card">
         <div class="card-header text-center bg-dark text-white">
            <h2>🧾 SOLICITUD DE CHEQUES</h2>
            <p class="mb-0">Selecciona gastos y organízalos en cheques</p>
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

      <form action="" method="post" id="formSolicitudCheques">
         <div class="row">
            <!-- Columna izquierda: Gastos disponibles -->
            <div class="col-md-5">
               <div class="card">
                  <div class="card-header bg-success text-white">
                     <h5 class="mb-0">📋 GASTOS DISPONIBLES</h5>
                  </div>
                  <div class="card-body">
                     <!-- Filtros -->
                     <div class="row mb-3">
                        <div class="col-md-6">
                           <label class="form-label fw-bold">Filtrar por Quincena:</label>
                           <select class="form-select" id="filtroQuincena">
                              <option value="todas">Todas las quincenas</option>
                              <option value="15">Quincena 1-15</option>
                              <option value="30">Quincena 16-30</option>
                           </select>
                        </div>
                        <div class="col-md-6">
                           <label class="form-label fw-bold">Filtrar por Tipo:</label>
                           <select class="form-select" id="filtroTipo">
                              <option value="todos">Todos los tipos</option>
                              <?php foreach ($tipos_gastos as $key => $nombre): ?>
                                 <option value="<?php echo $key; ?>"><?php echo $nombre; ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                     </div>

                     <!-- Lista de gastos -->
                     <div class="gastos-container" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($gastos_por_tipo as $tipo_nombre => $gastos_tipo): ?>
                           <div class="card mb-3 tipo-gasto" data-tipo="<?php echo array_search($tipo_nombre, $tipos_gastos); ?>">
                              <div class="card-header py-2 bg-light">
                                 <h6 class="mb-0"><?php echo $tipo_nombre; ?></h6>
                              </div>
                              <div class="card-body p-2">
                                 <?php foreach ($gastos_tipo as $gasto): ?>
                                    <div class="gasto-item p-2 mb-2 rounded quincena-<?php echo $gasto['quincena']; ?>"
                                       data-gasto-id="<?php echo $gasto['id']; ?>"
                                       data-quincena="<?php echo $gasto['quincena']; ?>"
                                       data-tipo="<?php echo $gasto['tipo_gasto']; ?>">
                                       <div class="form-check">
                                          <input class="form-check-input gasto-checkbox"
                                             type="checkbox"
                                             value="<?php echo $gasto['id']; ?>"
                                             id="gasto_<?php echo $gasto['id']; ?>">
                                          <label class="form-check-label w-100" for="gasto_<?php echo $gasto['id']; ?>">
                                             <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold"><?php echo $gasto['detalles']; ?></span>
                                                <span class="badge badge-quincena-<?php echo $gasto['quincena']; ?>">
                                                   <?php echo $gasto['quincena'] == '15' ? '1-15' : '16-30'; ?>
                                                </span>
                                             </div>
                                             <div class="text-end">
                                                <strong class="text-success">RD$ <?php echo number_format($gasto['monto'], 2, '.', ','); ?></strong>
                                             </div>
                                          </label>
                                       </div>
                                    </div>
                                 <?php endforeach; ?>
                              </div>
                           </div>
                        <?php endforeach; ?>

                        <?php if (empty($gastos)): ?>
                           <div class="text-center text-muted py-4">
                              <p>No hay gastos disponibles para el mes actual</p>
                              <p class="small">Los gastos aparecerán aquí una vez que sean registrados</p>
                           </div>
                        <?php endif; ?>
                     </div>

                     <!-- Resumen selección -->
                     <div class="card mt-3">
                        <div class="card-body py-2 bg-info text-white">
                           <div class="row text-center">
                              <div class="col-6">
                                 <small>Gastos seleccionados:</small>
                                 <div class="fw-bold" id="contadorGastos">0</div>
                              </div>
                              <div class="col-6">
                                 <small>Total seleccionado:</small>
                                 <div class="fw-bold" id="totalSeleccionado">RD$ 0.00</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Columna derecha: Cheques de la solicitud -->
            <div class="col-md-7">
               <div class="card">
                  <div class="card-header bg-warning text-dark">
                     <h5 class="mb-0">🧾 CHEQUES DE LA SOLICITUD</h5>
                  </div>
                  <div class="card-body">
                     <!-- Información general de la solicitud -->
                     <div class="row mb-4">
                        <div class="col-md-6">
                           <label for="descripcion_general" class="form-label fw-bold">Descripción General:</label>
                           <textarea class="form-control" name="descripcion_general" id="descripcion_general"
                              placeholder="Descripción de la solicitud de cheques..." rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                           <label for="quincena_solicitud" class="form-label fw-bold">Quincena Solicitud:</label>
                           <select name="quincena_solicitud" id="quincena_solicitud" class="form-select" required>
                              <option value="15">Quincena 1-15</option>
                              <option value="30">Quincena 16-30</option>
                           </select>
                        </div>
                        <div class="col-md-3">
                           <label for="fecha_solicitud" class="form-label fw-bold">Fecha Solicitud:</label>
                           <input type="date" class="form-control" name="fecha_solicitud" id="fecha_solicitud"
                              value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                     </div>

                     <!-- Contenedor de cheques -->
                     <div id="chequesContainer">
                        <!-- Los cheques se agregarán aquí dinámicamente -->
                        <div class="cheque-card p-3 mb-3" data-cheque-index="0">
                           <div class="d-flex justify-content-between align-items-center mb-2">
                              <h6 class="mb-0 text-primary">CHEQUE CHQ-001</h6>
                              <button type="button" class="btn btn-danger btn-sm quitar-cheque" disabled>
                                 🗑️
                              </button>
                           </div>
                           <div class="mb-2">
                              <label class="form-label fw-bold">Gastos para este cheque:</label>
                              <select class="form-select gastos-cheque" name="cheques[0][gastos][]" multiple size="4" disabled>
                                 <option value="">Selecciona gastos primero</option>
                              </select>
                           </div>
                           <div class="d-flex justify-content-between align-items-center">
                              <small class="text-muted">Arrastra gastos aquí o selecciona del dropdown</small>
                              <strong class="text-success cheque-total">RD$ 0.00</strong>
                           </div>
                        </div>
                     </div>

                     <!-- Botón agregar cheque -->
                     <div class="text-center mb-4">
                        <button type="button" class="btn btn-outline-primary" id="agregarCheque">
                           ➕ AGREGAR OTRO CHEQUE
                        </button>
                     </div>

                     <!-- Resumen general -->
                     <div class="card bg-dark text-white">
                        <div class="card-body text-center">
                           <h4 class="mb-0">
                              🟰 TOTAL SOLICITUD:
                              <span id="totalSolicitud">RD$ 0.00</span>
                           </h4>
                           <small id="resumenCheques">0 cheques | 0 gastos</small>
                        </div>
                     </div>

                     <!-- Botones de acción -->
                     <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                           ← Volver a Gastos
                        </a>
                        <div>
                           <a href="historial_solicitudes.php" class="btn btn-info">
                              📋 Ver Historial
                           </a>
                           <button type="submit" name="guardar_solicitud" class="btn btn-success" id="btnGuardar" disabled>
                              💾 GUARDAR SOLICITUD
                           </button>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </form>
   </div>

   <br>

   <script>
      // Variables globales
      let gastosSeleccionados = new Set();
      let cheques = [{
         index: 0,
         gastos: new Set(),
         total: 0
      }];
      let nextChequeIndex = 1;

      // Inicialización
      document.addEventListener('DOMContentLoaded', function() {
         inicializarEventos();
         actualizarResumen();
      });

      function inicializarEventos() {
         // Filtros
         document.getElementById('filtroQuincena').addEventListener('change', filtrarGastos);
         document.getElementById('filtroTipo').addEventListener('change', filtrarGastos);

         // Checkboxes de gastos
         document.querySelectorAll('.gasto-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', manejarSeleccionGasto);
         });

         // Botón agregar cheque
         document.getElementById('agregarCheque').addEventListener('click', agregarCheque);

         // Drag and drop
         inicializarDragAndDrop();
      }

      function manejarSeleccionGasto(event) {
         const checkbox = event.target;
         const gastoId = checkbox.value;
         const gastoItem = checkbox.closest('.gasto-item');

         if (checkbox.checked) {
            gastosSeleccionados.add(gastoId);
            gastoItem.classList.add('gasto-seleccionado');
         } else {
            gastosSeleccionados.delete(gastoId);
            gastoItem.classList.remove('gasto-seleccionado');

            // Remover de todos los cheques
            cheques.forEach(cheque => {
               cheque.gastos.delete(gastoId);
            });
         }

         actualizarDropdownsGastos();
         actualizarResumen();
      }

      function actualizarDropdownsGastos() {
         const gastosArray = Array.from(gastosSeleccionados);

         document.querySelectorAll('.gastos-cheque').forEach((dropdown, index) => {
            // Limpiar dropdown
            dropdown.innerHTML = '';

            if (gastosArray.length === 0) {
               dropdown.innerHTML = '<option value="">Selecciona gastos primero</option>';
               dropdown.disabled = true;
               return;
            }

            dropdown.disabled = false;

            // Agregar opciones
            gastosArray.forEach(gastoId => {
               const gastoItem = document.querySelector(`[data-gasto-id="${gastoId}"]`);
               if (gastoItem) {
                  const detalles = gastoItem.querySelector('.fw-bold').textContent;
                  const monto = gastoItem.querySelector('.text-success').textContent;
                  const selected = cheques[index].gastos.has(gastoId) ? 'selected' : '';

                  const option = document.createElement('option');
                  option.value = gastoId;
                  option.textContent = `${detalles} - ${monto}`;
                  option.selected = selected;
                  dropdown.appendChild(option);
               }
            });

            // Evento change para el dropdown
            dropdown.onchange = function() {
               const selectedOptions = Array.from(this.selectedOptions).map(opt => opt.value);
               cheques[index].gastos = new Set(selectedOptions);
               calcularTotalCheque(index);
               actualizarResumen();
            };
         });
      }

      function agregarCheque() {
         const chequesContainer = document.getElementById('chequesContainer');
         const nuevoCheque = document.createElement('div');
         nuevoCheque.className = 'cheque-card p-3 mb-3';
         nuevoCheque.setAttribute('data-cheque-index', nextChequeIndex);

         nuevoCheque.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary">CHEQUE CHQ-${String(nextChequeIndex + 1).padStart(3, '0')}</h6>
                    <button type="button" class="btn btn-danger btn-sm quitar-cheque">
                        🗑️
                    </button>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Gastos para este cheque:</label>
                    <select class="form-select gastos-cheque" name="cheques[${nextChequeIndex}][gastos][]" multiple size="4" ${gastosSeleccionados.size === 0 ? 'disabled' : ''}>
                        <option value="">Selecciona gastos primero</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Arrastra gastos aquí o selecciona del dropdown</small>
                    <strong class="text-success cheque-total">RD$ 0.00</strong>
                </div>
            `;

         chequesContainer.appendChild(nuevoCheque);

         // Agregar al array de cheques
         cheques.push({
            index: nextChequeIndex,
            gastos: new Set(),
            total: 0
         });

         // Evento para botón quitar
         nuevoCheque.querySelector('.quitar-cheque').addEventListener('click', function() {
            if (cheques.length > 1) {
               quitarCheque(nextChequeIndex);
            }
         });

         nextChequeIndex++;
         actualizarDropdownsGastos();
         actualizarBotonesQuitar();
      }

      function quitarCheque(index) {
         // Remover del array
         cheques = cheques.filter(cheque => cheque.index !== index);

         // Remover del DOM
         const chequeElement = document.querySelector(`[data-cheque-index="${index}"]`);
         if (chequeElement) {
            chequeElement.remove();
         }

         // Reindexar y actualizar
         reindexarCheques();
         actualizarDropdownsGastos();
         actualizarResumen();
      }

      function reindexarCheques() {
         const chequesContainer = document.getElementById('chequesContainer');
         const chequeElements = chequesContainer.querySelectorAll('.cheque-card');

         chequeElements.forEach((element, newIndex) => {
            const oldIndex = parseInt(element.getAttribute('data-cheque-index'));
            element.setAttribute('data-cheque-index', newIndex);

            // Actualizar número de cheque
            element.querySelector('h6').textContent = `CHEQUE CHQ-${String(newIndex + 1).padStart(3, '0')}`;

            // Actualizar name del select
            const select = element.querySelector('select');
            select.name = `cheques[${newIndex}][gastos][]`;

            // Actualizar índice en el array
            if (cheques[newIndex]) {
               cheques[newIndex].index = newIndex;
            }
         });

         nextChequeIndex = cheques.length;
      }

      function calcularTotalCheque(chequeIndex) {
         const cheque = cheques[chequeIndex];
         let total = 0;

         cheque.gastos.forEach(gastoId => {
            const gastoItem = document.querySelector(`[data-gasto-id="${gastoId}"]`);
            if (gastoItem) {
               const montoText = gastoItem.querySelector('.text-success').textContent;
               const monto = parseFloat(montoText.replace('RD$', '').replace(/,/g, '').trim());
               total += monto;
            }
         });

         cheque.total = total;

         // Actualizar en la UI
         const chequeElement = document.querySelector(`[data-cheque-index="${chequeIndex}"]`);
         if (chequeElement) {
            chequeElement.querySelector('.cheque-total').textContent = `RD$ ${total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
         }
      }

      function actualizarResumen() {
         // Contador de gastos seleccionados
         document.getElementById('contadorGastos').textContent = gastosSeleccionados.size;

         // Total seleccionado
         let totalSeleccionado = 0;
         gastosSeleccionados.forEach(gastoId => {
            const gastoItem = document.querySelector(`[data-gasto-id="${gastoId}"]`);
            if (gastoItem) {
               const montoText = gastoItem.querySelector('.text-success').textContent;
               const monto = parseFloat(montoText.replace('RD$', '').replace(/,/g, '').trim());
               totalSeleccionado += monto;
            }
         });
         document.getElementById('totalSeleccionado').textContent = `RD$ ${totalSeleccionado.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

         // Calcular totales de cheques
         let totalSolicitud = 0;
         let totalGastosAsignados = 0;

         cheques.forEach(cheque => {
            calcularTotalCheque(cheque.index);
            totalSolicitud += cheque.total;
            totalGastosAsignados += cheque.gastos.size;
         });

         // Total solicitud
         document.getElementById('totalSolicitud').textContent = `RD$ ${totalSolicitud.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

         // Resumen cheques
         document.getElementById('resumenCheques').textContent =
            `${cheques.length} cheques | ${totalGastosAsignados} gastos asignados`;

         // Habilitar/deshabilitar botón guardar
         const btnGuardar = document.getElementById('btnGuardar');
         btnGuardar.disabled = totalSolicitud === 0;

         actualizarBotonesQuitar();
      }

      function actualizarBotonesQuitar() {
         document.querySelectorAll('.quitar-cheque').forEach((btn, index) => {
            btn.disabled = cheques.length <= 1;
         });
      }

      function filtrarGastos() {
         const filtroQuincena = document.getElementById('filtroQuincena').value;
         const filtroTipo = document.getElementById('filtroTipo').value;

         document.querySelectorAll('.tipo-gasto').forEach(tipoElement => {
            const tipo = tipoElement.getAttribute('data-tipo');
            let mostrarTipo = false;

            tipoElement.querySelectorAll('.gasto-item').forEach(gastoItem => {
               const quincena = gastoItem.getAttribute('data-quincena');
               const tipoGasto = gastoItem.getAttribute('data-tipo');

               let mostrar = true;

               if (filtroQuincena !== 'todas' && quincena !== filtroQuincena) {
                  mostrar = false;
               }

               if (filtroTipo !== 'todos' && tipoGasto !== filtroTipo) {
                  mostrar = false;
               }

               gastoItem.style.display = mostrar ? 'block' : 'none';

               if (mostrar) {
                  mostrarTipo = true;
               }
            });

            tipoElement.style.display = mostrarTipo ? 'block' : 'none';
         });
      }

      function inicializarDragAndDrop() {
         // Implementación básica de drag and drop
         document.querySelectorAll('.gasto-item').forEach(item => {
            item.draggable = true;

            item.addEventListener('dragstart', function(e) {
               e.dataTransfer.setData('text/plain', this.getAttribute('data-gasto-id'));
               this.style.opacity = '0.4';
            });

            item.addEventListener('dragend', function() {
               this.style.opacity = '1';
            });
         });

         document.querySelectorAll('.cheque-card').forEach(cheque => {
            cheque.addEventListener('dragover', function(e) {
               e.preventDefault();
               this.style.backgroundColor = '#f0f8ff';
            });

            cheque.addEventListener('dragleave', function() {
               this.style.backgroundColor = '';
            });

            cheque.addEventListener('drop', function(e) {
               e.preventDefault();
               this.style.backgroundColor = '';

               const gastoId = e.dataTransfer.getData('text/plain');
               const chequeIndex = parseInt(this.getAttribute('data-cheque-index'));

               // Agregar gasto al cheque
               if (!cheques[chequeIndex].gastos.has(gastoId)) {
                  cheques[chequeIndex].gastos.add(gastoId);

                  // Marcar checkbox si no está marcado
                  const checkbox = document.querySelector(`#gasto_${gastoId}`);
                  if (checkbox && !checkbox.checked) {
                     checkbox.checked = true;
                     gastosSeleccionados.add(gastoId);
                     checkbox.closest('.gasto-item').classList.add('gasto-seleccionado');
                  }

                  actualizarDropdownsGastos();
                  actualizarResumen();
               }
            });
         });
      }
   </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>