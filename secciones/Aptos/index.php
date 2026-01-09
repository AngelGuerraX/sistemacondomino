<?php
$thetitle = "Aptos";
include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// 1. PROCESAR ELIMINACIÓN
if (isset($_GET['txID'])) {
   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

   // Verificar si el apartamento tiene tickets o pagos antes de eliminar
   $sentencia_verificar = $conexion->prepare("
      SELECT COUNT(*) as total_tickets FROM tbl_tickets WHERE id_apto = :id_apto
      UNION ALL
      SELECT COUNT(*) as total_pagos FROM tbl_pagos WHERE id_apto = :id_apto
   ");
   $sentencia_verificar->bindParam(":id_apto", $txtID);
   $sentencia_verificar->execute();
   $resultados = $sentencia_verificar->fetchAll(PDO::FETCH_COLUMN);

   $tiene_tickets = $resultados[0] > 0;
   $tiene_pagos = $resultados[1] > 0;

   if (!$tiene_tickets && !$tiene_pagos) {
      $sentencia = $conexion->prepare("DELETE FROM tbl_aptos WHERE id=:id AND id_condominio=:id_condominio");
      $sentencia->bindParam(":id", $txtID);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->execute();
      $_SESSION['mensaje'] = "✅ Apartamento eliminado correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   } else {
      $_SESSION['mensaje'] = "❌ No se puede eliminar el apartamento porque tiene tickets o pagos registrados";
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: index.php");
   exit;
}

// 2. PROCESAR NUEVO APARTAMENTO
if (isset($_POST['nuevo_apartamento'])) {
   $apto = $_POST['apto'];
   $condominos = $_POST['condominos'];
   $mantenimiento = $_POST['mantenimiento'];
   $gas = $_POST['gas'];
   $telefono = $_POST['telefono'];
   $correo = $_POST['correo'];
   $forma_pago = $_POST['forma_pago'];
   $tiene_inquilino = isset($_POST['tiene_inquilino']) ? 1 : 0;
   $usuario_registro = $_SESSION['usuario'];

   try {
      $sentencia = $conexion->prepare("INSERT INTO tbl_aptos 
         (apto, condominos, mantenimiento, gas, telefono, correo, forma_de_pago, tiene_inquilino, id_condominio, balance, fecha_ultimo_pago)
         VALUES 
         (:apto, :condominos, :mantenimiento, :gas, :telefono, :correo, :forma_pago, :tiene_inquilino, :id_condominio, 0, '')");

      $sentencia->bindParam(":apto", $apto);
      $sentencia->bindParam(":condominos", $condominos);
      $sentencia->bindParam(":mantenimiento", $mantenimiento);
      $sentencia->bindParam(":gas", $gas);
      $sentencia->bindParam(":telefono", $telefono);
      $sentencia->bindParam(":correo", $correo);
      $sentencia->bindParam(":forma_pago", $forma_pago);
      $sentencia->bindParam(":tiene_inquilino", $tiene_inquilino);
      $sentencia->bindParam(":id_condominio", $idcondominio);

      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Apartamento creado correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al crear apartamento: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: index.php");
   exit;
}

// 3. PROCESAR EDICIÓN DE APARTAMENTO
if (isset($_POST['editar_apartamento'])) {
   $id = $_POST['id'];
   $apto = $_POST['apto'];
   $condominos = $_POST['condominos'];
   $mantenimiento = $_POST['mantenimiento'];
   $gas = $_POST['gas'];
   $telefono = $_POST['telefono'];
   $correo = $_POST['correo'];
   $forma_pago = $_POST['forma_pago'];
   $tiene_inquilino = isset($_POST['tiene_inquilino']) ? 1 : 0;

   try {
      $sentencia = $conexion->prepare("UPDATE tbl_aptos SET 
         apto = :apto, 
         condominos = :condominos, 
         mantenimiento = :mantenimiento, 
         gas = :gas, 
         telefono = :telefono, 
         correo = :correo, 
         forma_de_pago = :forma_pago,
         tiene_inquilino = :tiene_inquilino
         WHERE id = :id AND id_condominio = :id_condominio");

      $sentencia->bindParam(":id", $id);
      $sentencia->bindParam(":apto", $apto);
      $sentencia->bindParam(":condominos", $condominos);
      $sentencia->bindParam(":mantenimiento", $mantenimiento);
      $sentencia->bindParam(":gas", $gas);
      $sentencia->bindParam(":telefono", $telefono);
      $sentencia->bindParam(":correo", $correo);
      $sentencia->bindParam(":forma_pago", $forma_pago);
      $sentencia->bindParam(":tiene_inquilino", $tiene_inquilino);
      $sentencia->bindParam(":id_condominio", $idcondominio);

      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Apartamento actualizado correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al actualizar apartamento: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: index.php");
   exit;
}

// 4. PROCESAR GESTIÓN DE INQUILINO
if (isset($_POST['gestionar_inquilino'])) {
   $id_apto = $_POST['id_apto'];
   $accion = $_POST['accion'];

   if ($accion == 'asignar') {
      $nombre = $_POST['nombre_inquilino'];
      $correo = $_POST['correo_inquilino'];
      $telefono = $_POST['telefono_inquilino'];
      $fecha_inicio = $_POST['fecha_inicio'];
      $fecha_fin = $_POST['fecha_fin'];

      // Verificar si ya existe un inquilino activo
      $sentencia_verificar = $conexion->prepare("SELECT id FROM tbl_inquilinos WHERE id_apto = :id_apto AND activo = 1");
      $sentencia_verificar->bindParam(":id_apto", $id_apto);
      $sentencia_verificar->execute();

      if ($sentencia_verificar->fetch()) {
         $_SESSION['mensaje'] = "❌ Ya existe un inquilino activo para este apartamento";
         $_SESSION['tipo_mensaje'] = "danger";
      } else {
         $sentencia = $conexion->prepare("INSERT INTO tbl_inquilinos 
            (id_apto, id_condominio, nombre, correo, telefono, fecha_inicio, fecha_fin, activo, balance, usuario_registro)
            VALUES 
            (:id_apto, :id_condominio, :nombre, :correo, :telefono, :fecha_inicio, :fecha_fin, 1, 0, :usuario_registro)");

         $sentencia->bindParam(":id_apto", $id_apto);
         $sentencia->bindParam(":id_condominio", $idcondominio);
         $sentencia->bindParam(":nombre", $nombre);
         $sentencia->bindParam(":correo", $correo);
         $sentencia->bindParam(":telefono", $telefono);
         $sentencia->bindParam(":fecha_inicio", $fecha_inicio);
         $sentencia->bindParam(":fecha_fin", $fecha_fin);
         $sentencia->bindParam(":usuario_registro", $_SESSION['usuario']);

         $sentencia->execute();

         // Actualizar el campo tiene_inquilino en tbl_aptos
         $sentencia_actualizar = $conexion->prepare("UPDATE tbl_aptos SET tiene_inquilino = 1 WHERE id = :id_apto");
         $sentencia_actualizar->bindParam(":id_apto", $id_apto);
         $sentencia_actualizar->execute();

         $_SESSION['mensaje'] = "✅ Inquilino asignado correctamente";
         $_SESSION['tipo_mensaje'] = "success";
      }
   } elseif ($accion == 'remover') {
      // Marcar inquilino como inactivo
      $sentencia = $conexion->prepare("UPDATE tbl_inquilinos SET activo = 0, fecha_fin = NOW() WHERE id_apto = :id_apto AND activo = 1");
      $sentencia->bindParam(":id_apto", $id_apto);
      $sentencia->execute();

      // Actualizar el campo tiene_inquilino en tbl_aptos
      $sentencia_actualizar = $conexion->prepare("UPDATE tbl_aptos SET tiene_inquilino = 0 WHERE id = :id_apto");
      $sentencia_actualizar->bindParam(":id_apto", $id_apto);
      $sentencia_actualizar->execute();

      $_SESSION['mensaje'] = "✅ Inquilino removido correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   }

   header("Location: index.php");
   exit;
}

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
   echo "<div class='alert alert-{$_SESSION['tipo_mensaje']}'>{$_SESSION['mensaje']}</div>";
   unset($_SESSION['mensaje']);
   unset($_SESSION['tipo_mensaje']);
}

// Obtener lista de apartamentos con información de inquilinos
$sentencia = $conexion->prepare("
   SELECT a.*, i.nombre as nombre_inquilino, i.balance as balance_inquilino, i.activo as inquilino_activo
   FROM tbl_aptos a
   LEFT JOIN tbl_inquilinos i ON a.id = i.id_apto AND i.activo = 1
   WHERE a.id_condominio = :idcondominio
   ORDER BY a.apto ASC  
");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_aptos = $sentencia->fetchAll((PDO::FETCH_ASSOC));
?>

<br>

<div class="card">
   <div class="card-header bg-dark text-white">
      <center>
         <h2>🏢 GESTIÓN DE APARTAMENTOS</h2>
      </center>
   </div>
   <div class="card-body">
      <!-- Botón para abrir modal nuevo apartamento -->
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoApto">
         ➕ Nuevo Apartamento
      </button>

      <br><br>

      <div class="table-responsive">
         <table class="table table-striped" id="tabla_id">
            <thead class="table-dark">
               <tr>
                  <th scope="col">Apto</th>
                  <th scope="col">Condominos</th>
                  <th scope="col">Tipo</th>
                  <th scope="col">Mant.</th>
                  <th scope="col">Gas</th>
                  <th scope="col">Teléfono</th>
                  <th scope="col">Correo</th>
                  <th scope="col">Último Pago</th>
                  <th scope="col">Balance</th>
                  <th scope="col">Acciones</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($lista_aptos as $registro) {
                  $tiene_inquilino = $registro['tiene_inquilino'];
                  $clase_fila = $tiene_inquilino ? 'table-success' : '';
               ?>
                  <tr class="<?php echo $clase_fila; ?>">
                     <td><strong><?php echo $registro['apto']; ?></strong></td>
                     <td><?php echo $registro['condominos']; ?></td>
                     <td>
                        <?php if ($tiene_inquilino): ?>
                           <span class="badge bg-success" title="Inquilino: <?php echo $registro['nombre_inquilino'] ?? 'N/A'; ?>">
                              👥 Inquilino
                           </span>
                        <?php else: ?>
                           <span class="badge bg-warning">🏠 Propietario</span>
                        <?php endif; ?>
                     </td>
                     <td><?php echo number_format(floatval($registro['mantenimiento']), 2, '.', ','); ?></td>
                     <td><?php echo $registro['gas']; ?></td>
                     <td><?php echo $registro['telefono']; ?></td>
                     <td><?php echo $registro['correo']; ?></td>
                     <td><?php echo $registro['fecha_ultimo_pago'] ?: 'N/A'; ?></td>
                     <td>
                        <span class="<?php echo floatval($registro['balance']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                           <?php echo number_format(floatval($registro['balance']), 2, '.', ','); ?>
                        </span>
                     </td>
                     <td>
                        <div class="btn-group" role="group">
                           <!-- Botón Ver Tickets -->
                           <a class="btn btn-primary btn-sm" href="editar.php?txID=<?php echo $registro['id']; ?>" title="Ver Tickets y Pagos">
                              📋
                           </a>

                           <!-- Botón Editar -->
                           <button type="button" class="btn btn-warning btn-sm"
                              data-bs-toggle="modal"
                              data-bs-target="#modalEditarApto"
                              data-id="<?php echo $registro['id']; ?>"
                              data-apto="<?php echo $registro['apto']; ?>"
                              data-condominos="<?php echo $registro['condominos']; ?>"
                              data-mantenimiento="<?php echo $registro['mantenimiento']; ?>"
                              data-gas="<?php echo $registro['gas']; ?>"
                              data-telefono="<?php echo $registro['telefono']; ?>"
                              data-correo="<?php echo $registro['correo']; ?>"
                              data-forma-pago="<?php echo $registro['forma_de_pago']; ?>"
                              data-tiene-inquilino="<?php echo $registro['tiene_inquilino']; ?>"
                              title="Editar Apartamento">
                              ✏️
                           </button>

                           <!-- Botón Gestionar Inquilino -->
                           <button type="button" class="btn btn-info btn-sm"
                              data-bs-toggle="modal"
                              data-bs-target="#modalInquilino"
                              data-id-apto="<?php echo $registro['id']; ?>"
                              data-apto="<?php echo $registro['apto']; ?>"
                              data-condominos="<?php echo $registro['condominos']; ?>"
                              data-tiene-inquilino="<?php echo $registro['tiene_inquilino']; ?>"
                              data-nombre-inquilino="<?php echo $registro['nombre_inquilino'] ?? ''; ?>"
                              data-balance-inquilino="<?php echo $registro['balance_inquilino'] ?? 0; ?>"
                              title="Gestionar Inquilino">
                              👥
                           </button>

                           <!-- Botón Eliminar -->
                           <button type="button" class="btn btn-danger btn-sm"
                              onclick="borrar(<?php echo $registro['id']; ?>)"
                              title="Eliminar Apartamento">
                              🗑️
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

<!-- MODAL NUEVO APARTAMENTO -->
<div class="modal fade" id="modalNuevoApto" tabindex="-1" aria-labelledby="modalNuevoAptoLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <div class="modal-content bg-light text-dark">
         <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="modalNuevoAptoLabel">➕ Nuevo Apartamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <form method="POST" id="formNuevoApto">
            <input type="hidden" name="nuevo_apartamento" value="1">
            <div class="modal-body bg-light text-dark">
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Número de Apartamento *</label>
                     <input type="text" class="form-control" name="apto" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Condóminos *</label>
                     <input type="text" class="form-control" name="condominos" required>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Mantenimiento (RD$) *</label>
                     <input type="number" step="0.01" class="form-control" name="mantenimiento" value="10000" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Gas</label>
                     <input type="number" step="0.01" class="form-control" name="gas" value="0">
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Teléfono</label>
                     <input type="text" class="form-control" name="telefono">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Correo Electrónico</label>
                     <input type="email" class="form-control" name="correo">
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Forma de Pago *</label>
                     <select class="form-select" name="forma_pago" required>
                        <option value="">Seleccionar...</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Depósito">Depósito</option>
                     </select>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Tipo de Residente</label>
                     <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tiene_inquilino" id="tiene_inquilino_nuevo">
                        <label class="form-check-label" for="tiene_inquilino_nuevo">
                           Tiene Inquilino
                        </label>
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" class="btn btn-success">💾 Guardar Apartamento</button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- MODAL EDITAR APARTAMENTO -->
<div class="modal fade" id="modalEditarApto" tabindex="-1" aria-labelledby="modalEditarAptoLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="modalEditarAptoLabel">✏️ Editar Apartamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <form method="POST" id="formEditarApto">
            <input type="hidden" name="editar_apartamento" value="1">
            <input type="hidden" name="id" id="editar_id">
            <div class="modal-body text-dark">
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Número de Apartamento *</label>
                     <input type="text" class="form-control" name="apto" id="editar_apto" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Condóminos *</label>
                     <input type="text" class="form-control" name="condominos" id="editar_condominos" required>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Mantenimiento (RD$) *</label>
                     <input type="number" step="0.01" class="form-control" name="mantenimiento" id="editar_mantenimiento" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Gas</label>
                     <input type="number" step="0.01" class="form-control" name="gas" id="editar_gas">
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Teléfono</label>
                     <input type="text" class="form-control" name="telefono" id="editar_telefono">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Correo Electrónico</label>
                     <input type="email" class="form-control" name="correo" id="editar_correo">
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Forma de Pago *</label>
                     <select class="form-select" name="forma_pago" id="editar_forma_pago" required>
                        <option value="">Seleccionar...</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Depósito">Depósito</option>
                     </select>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Tipo de Residente</label>
                     <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tiene_inquilino" id="editar_tiene_inquilino">
                        <label class="form-check-label" for="editar_tiene_inquilino">
                           Tiene Inquilino
                        </label>
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" class="btn btn-warning">💾 Actualizar Apartamento</button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- MODAL GESTIONAR INQUILINO -->
<div class="modal fade" id="modalInquilino" tabindex="-1" aria-labelledby="modalInquilinoLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="modalInquilinoLabel">👥 Gestionar Inquilino</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <form method="POST" id="formInquilino">
            <input type="hidden" name="gestionar_inquilino" value="1">
            <input type="hidden" name="id_apto" id="inquilino_id_apto">
            <input type="hidden" name="accion" id="inquilino_accion" value="asignar">

            <div class="modal-body text-dark">
               <!-- Información del Apartamento -->
               <div class="card mb-3">
                  <div class="card-header bg-light">
                     <strong>Información del Apartamento</strong>
                  </div>
                  <div class="card-body">
                     <p><strong>Apto:</strong> <span id="info_apto"></span></p>
                     <p><strong>Condóminos:</strong> <span id="info_condominos"></span></p>
                     <p><strong>Estado:</strong> <span id="info_estado" class="badge"></span></p>
                  </div>
               </div>

               <!-- Información del Inquilino Actual -->
               <div id="info_inquilino_actual" class="card mb-3" style="display: none;">
                  <div class="card-header bg-success text-white">
                     <strong>Inquilino Actual</strong>
                  </div>
                  <div class="card-body">
                     <p><strong>Nombre:</strong> <span id="info_nombre_inquilino"></span></p>
                     <p><strong>Balance:</strong> RD$<span id="info_balance_inquilino"></span></p>
                     <button type="button" class="btn btn-danger btn-sm" onclick="removerInquilino()">
                        🗑️ Remover Inquilino
                     </button>
                  </div>
               </div>

               <!-- Formulario para asignar inquilino -->
               <div id="form_asignar_inquilino">
                  <div class="mb-3">
                     <label class="form-label">Nombre del Inquilino *</label>
                     <input type="text" class="form-control" name="nombre_inquilino" id="nombre_inquilino">
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Correo Electrónico</label>
                     <input type="email" class="form-control" name="correo_inquilino" id="correo_inquilino">
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Teléfono</label>
                     <input type="text" class="form-control" name="telefono_inquilino" id="telefono_inquilino">
                  </div>
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                     </div>
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Fin</label>
                        <input type="date" class="form-control" name="fecha_fin" id="fecha_fin">
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <button type="submit" class="btn btn-success" id="btn_asignar_inquilino">➕ Asignar Inquilino</button>
            </div>
         </form>
      </div>
   </div>
</div>

<script>
   // Función para eliminar apartamento
   function borrar(id) {
      Swal.fire({
         title: '¿Estás seguro?',
         text: "Esta acción no se puede deshacer",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, eliminar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id;
         }
      });
   }

   // Configurar modal de editar apartamento
   const modalEditarApto = document.getElementById('modalEditarApto');
   modalEditarApto.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;

      document.getElementById('editar_id').value = button.getAttribute('data-id');
      document.getElementById('editar_apto').value = button.getAttribute('data-apto');
      document.getElementById('editar_condominos').value = button.getAttribute('data-condominos');
      document.getElementById('editar_mantenimiento').value = button.getAttribute('data-mantenimiento');
      document.getElementById('editar_gas').value = button.getAttribute('data-gas');
      document.getElementById('editar_telefono').value = button.getAttribute('data-telefono');
      document.getElementById('editar_correo').value = button.getAttribute('data-correo');
      document.getElementById('editar_forma_pago').value = button.getAttribute('data-forma-pago');

      const tieneInquilino = button.getAttribute('data-tiene-inquilino') === '1';
      document.getElementById('editar_tiene_inquilino').checked = tieneInquilino;
   });

   // Configurar modal de inquilino
   const modalInquilino = document.getElementById('modalInquilino');
   modalInquilino.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const idApto = button.getAttribute('data-id-apto');
      const apto = button.getAttribute('data-apto');
      const condominos = button.getAttribute('data-condominos');
      const tieneInquilino = button.getAttribute('data-tiene-inquilino') === '1';
      const nombreInquilino = button.getAttribute('data-nombre-inquilino');
      const balanceInquilino = button.getAttribute('data-balance-inquilino');

      // Actualizar información del apartamento
      document.getElementById('inquilino_id_apto').value = idApto;
      document.getElementById('info_apto').textContent = apto;
      document.getElementById('info_condominos').textContent = condominos;

      const infoEstado = document.getElementById('info_estado');
      const infoInquilinoActual = document.getElementById('info_inquilino_actual');
      const formAsignarInquilino = document.getElementById('form_asignar_inquilino');
      const btnAsignarInquilino = document.getElementById('btn_asignar_inquilino');

      if (tieneInquilino && nombreInquilino) {
         // Mostrar información del inquilino actual
         infoEstado.textContent = 'Con Inquilino';
         infoEstado.className = 'badge bg-success';
         document.getElementById('info_nombre_inquilino').textContent = nombreInquilino;
         document.getElementById('info_balance_inquilino').textContent = parseFloat(balanceInquilino).toFixed(2);
         infoInquilinoActual.style.display = 'block';
         formAsignarInquilino.style.display = 'none';
         btnAsignarInquilino.style.display = 'none';
      } else {
         // Mostrar formulario para asignar inquilino
         infoEstado.textContent = 'Sin Inquilino';
         infoEstado.className = 'badge bg-warning';
         infoInquilinoActual.style.display = 'none';
         formAsignarInquilino.style.display = 'block';
         btnAsignarInquilino.style.display = 'block';

         // Establecer fecha de inicio por defecto (hoy)
         document.getElementById('fecha_inicio').valueAsDate = new Date();
      }
   });

   // Función para remover inquilino
   function removerInquilino() {
      Swal.fire({
         title: '¿Remover inquilino?',
         text: "El inquilino será marcado como inactivo",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, remover',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            document.getElementById('inquilino_accion').value = 'remover';
            document.getElementById('formInquilino').submit();
         }
      });
   }

   // Inicializar fecha de inicio en el modal nuevo
   document.addEventListener('DOMContentLoaded', function() {
      const fechaInicio = document.getElementById('fecha_inicio');
      if (fechaInicio) {
         fechaInicio.valueAsDate = new Date();
      }
   });
</script>

<?php include("../../templates/footer.php"); ?>