<?php include("../../templates/header.php");
include("../../bd.php");

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Procesar eliminación de pago
if (isset($_GET['eliminar_pago'])) {
  $id_pago = $_GET['eliminar_pago'];
  $idcondominio = $_SESSION['idcondominio'];

  try {
    $sentencia = $conexion->prepare("DELETE FROM tbl_pagos WHERE id_pago = :id_pago AND id_condominio = :id_condominio");
    $sentencia->bindParam(":id_pago", $id_pago);
    $sentencia->bindParam(":id_condominio", $idcondominio);
    $sentencia->execute();

    $_SESSION['mensaje'] = "✅ Pago eliminado correctamente";
    $_SESSION['tipo_mensaje'] = "success";
  } catch (Exception $e) {
    $_SESSION['mensaje'] = "❌ Error al eliminar el pago: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
  }

  header("Location: " . $_SERVER['HTTP_REFERER']);
  exit;
}

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
  echo "<div class='alert alert-{$_SESSION['tipo_mensaje']}'>{$_SESSION['mensaje']}</div>";
  unset($_SESSION['mensaje']);
  unset($_SESSION['tipo_mensaje']);
}

if (isset($_GET['txID'])) {
  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
  $sentencia = $conexion->prepare("SELECT * FROM tbl_aptos WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);

  $apto = $registro["apto"];
  $condominos = $registro["condominos"];
  $mantenimiento = $registro["mantenimiento"];
  $gas = $registro["gas"];
  $telefono = $registro["telefono"];
  $correo = $registro["correo"];
  $forma_de_pago = $registro["forma_de_pago"];
  $fecha_ultimo_pago = $registro["fecha_ultimo_pago"];
}

// INSERTAR PAGOS
if (isset($_POST['monto'])) { // verificamos que se envió el formulario
  // Datos recibidos del formulario
  $id_Apto = isset($_POST["id_apto"]) ? $_POST["id_apto"] : "";
  $id_condominio = $_SESSION['idcondominio'];
  $monto = isset($_POST["monto"]) ? $_POST["monto"] : "";
  $concepto = isset($_POST["concepto"]) ? $_POST["concepto"] : "";
  $fecha_pago = isset($_POST["fecha_pago"]) ? $_POST["fecha_pago"] : "";
  $forma_pago = isset($_POST["forma_pago"]) ? $_POST["forma_pago"] : "";
  $usuario_registro = $_SESSION['usuario']; // quien lo registra

  $fechaIngreso = date("Y-m-d");

  try {
    $sentencia = $conexion->prepare("INSERT INTO tbl_pagos 
            (id_apto, id_condominio, monto, concepto, forma_pago, fecha_pago, FechaDeIngreso, usuario_registro)
            VALUES 
            (:id_apto, :id_condominio, :monto, :concepto, :forma_pago, :fecha_pago, :FechaDeIngreso, :usuario_registro)");

    $sentencia->bindParam(":id_apto", $id_Apto);
    $sentencia->bindParam(":id_condominio", $id_condominio);
    $sentencia->bindParam(":monto", $monto);
    $sentencia->bindParam(":concepto", $concepto);
    $sentencia->bindParam(":forma_pago", $forma_pago);
    $sentencia->bindParam(":fecha_pago", $fecha_pago);
    $sentencia->bindParam(":FechaDeIngreso", $fechaIngreso);
    $sentencia->bindParam(":usuario_registro", $usuario_registro);

    $sentencia->execute();

    ////////////////////////////////////////////////
    // include '../../actualizar_balance.php';
    ////////////////////////////////////////////
    $_SESSION['mensaje'] = "✅ Pago registrado y balance actualizado correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    // Después de registrar el pago
    include 'actualizar_balance.php';
    $resultado = actualizarBalanceApto($id_Apto, $id_condominio);

    if ($resultado['success']) {
      $_SESSION['mensaje'] = "✅ Pago registrado y balance actualizado correctamente.";
      $_SESSION['tipo_mensaje'] = "success";
    } else {
      $_SESSION['mensaje'] = "❌ Error al actualizar el balance: " . $resultado['error'];
      $_SESSION['tipo_mensaje'] = "danger";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
  } catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error al registrar el pago: " . $e->getMessage() . "</div>";
  }
}
if (isset($_POST['formEditarTicket'])) {
  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
  //recoleccion de datos
  $id = (isset($_POST["id"]) ? $_POST["id"] : "");
  $mantenimiento = (isset($_POST["mantenimiento"]) ? $_POST["mantenimiento"] : "");
  $gas = (isset($_POST["gas"]) ? $_POST["gas"] : "");
  $mora = (isset($_POST["mora"]) ? $_POST["mora"] : "");
  $cuota = (isset($_POST["cuota_e"]) ? $_POST["cuota_e"] : "");
  // Verificación del checkbox y asignación del valor correspondiente
  $estado_pago = isset($_POST['estado_pago']) ? 'Pago' : 'Pendiente';
  $mes_ultimo_pago = (isset($_POST["mes"]) ? $_POST["mes"] : "");

  //preparar insercion
  $sentencia = $conexion->prepare("UPDATE tbl_tickets SET mantenimiento=:mantenimiento, gas=:gas, mora=:mora, cuota=:cuota, estado=:estado WHERE id=:id");
  //Asignando los valores de metodo post(del formulario)
  $sentencia->bindParam(":mantenimiento", $mantenimiento);
  $sentencia->bindParam(":gas", $gas);
  $sentencia->bindParam(":mora", $mora);
  $sentencia->bindParam(":cuota", $cuota);
  $sentencia->bindParam(":estado", $estado_pago);
  $sentencia->bindParam(":id", $id);
  $sentencia->execute();

  // ACTUALIZAR FECHA_ULTIMO_PAGO EN TBL_APTOS CUANDO SE MARCA COMO PAGO
  if ($estado_pago == 'Pago') {
    // Obtener el id_condominio de la sesión
    $id_condominio = $_SESSION['idcondominio'];

    // Actualizar fecha_ultimo_pago en tbl_aptos con el mes del ticket
    $sentencia_fecha = $conexion->prepare("
            UPDATE tbl_aptos 
            SET fecha_ultimo_pago = :fecha_ultimo_pago 
            WHERE id = :id_apto 
            AND id_condominio = :id_condominio
        ");
    $sentencia_fecha->bindParam(":fecha_ultimo_pago", $mes_ultimo_pago);
    $sentencia_fecha->bindParam(":id_apto", $txtID);
    $sentencia_fecha->bindParam(":id_condominio", $id_condominio);
    $sentencia_fecha->execute();

    $_SESSION['mensaje'] = "✅ Ticket actualizado, marcado como Pagado y fecha de último pago actualizada.";
    $_SESSION['tipo_mensaje'] = "success";
  } else {
    $_SESSION['mensaje'] = "✅ Ticket actualizado y marcado como Pendiente.";
    $_SESSION['tipo_mensaje'] = "warning";
  }

  // Actualizar balance
  include 'actualizar_balance.php';
  $resultado = actualizarBalanceApto($txtID, $id_condominio);

  if (!$resultado['success']) {
    $_SESSION['mensaje'] = "⚠️ Ticket actualizado pero error al actualizar balance: " . $resultado['error'];
    $_SESSION['tipo_mensaje'] = "danger";
  }

  header("Location: " . $_SERVER['HTTP_REFERER']);
  exit;
}
//Seleccion de mes para color de fondo de meses debidos
$mes_actual = $_SESSION['mes'];

$idcondominio = $_SESSION['idcondominio'];
$txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

$sentencia = $conexion->prepare("SELECT * FROM tbl_aptos where id_condominio=:idcondominio and id=:txtID");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":txtID", $txtID);
$sentencia->execute();
$lista_aptos = $sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia = $conexion->prepare("SELECT * FROM tbl_tickets where id_condominio=:idcondominio and id_apto=:txtID");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":txtID", $txtID);
$sentencia->execute();
$lista_tickets = $sentencia->fetchAll((PDO::FETCH_ASSOC));

// 🔹 Obtener el valor más reciente de gas desde tbl_gas
$mes_actual = date('n'); // número del mes actual
$anio_actual = date('Y');

$sentencia_gas = $conexion->prepare("
  SELECT gas_insertado 
  FROM tbl_gas 
  WHERE id_apto = :id_apto 
    AND id_condominio = :id_condominio 
    AND mes = :mes 
    AND anio = :anio
  ORDER BY fecha_registro DESC 
  LIMIT 1
");
$sentencia_gas->bindParam(":id_apto", $txtID);
$sentencia_gas->bindParam(":id_condominio", $idcondominio);
$sentencia_gas->bindParam(":mes", $mes_actual);
$sentencia_gas->bindParam(":anio", $anio_actual);
$sentencia_gas->execute();
$gas_data = $sentencia_gas->fetch(PDO::FETCH_ASSOC);

$gas_actual = $gas_data ? $gas_data['gas_insertado'] : 0; // si no hay, poner 0

$sentencia = $conexion->prepare("SELECT * FROM tbl_pagos where id_condominio=:idcondominio and id_apto=:txtID ORDER BY fecha_pago DESC");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":txtID", $txtID);
$sentencia->execute();
$lista_pagos = $sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia = $conexion->prepare("SELECT * FROM tbl_condominios where id=:idcondominio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_condominios = $sentencia->fetchAll((PDO::FETCH_ASSOC));

foreach ($lista_condominios as $registro) {
  $varmora = $registro['cant_mora'];
}

foreach ($lista_aptos as $registro) {
  $varmantenimiento = $registro['mantenimiento'];
}

?>
<br>
<div class="card" style="font-size: 18px;">
  <div class="card-header text-center bg-dark text-white">
    <h4>DATOS DEL APARTAMENTO</h4>
  </div>
  <div class="card-body ">
    <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th scope="col">Apto</th>
            <th scope="col">Condominos</th>
            <th scope="col">Balance</th>
            <th scope="col">Mantenimiento</th>
            <th scope="col">Gas</th>
            <th scope="col">Telefono</th>
            <th scope="col">Correo</th>
            <th scope="col">Ultimo pago</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_aptos as $registro) { ?>
            <tr class="">
              <td scope="row"><?php echo $registro['apto'] ?></td>
              <td scope="row"><?php echo $registro['condominos'] ?></td>
              <td scope="row"><?php $balanceShow = number_format(floatval($registro['balance']), 2, '.', ',');
                              echo $balanceShow; ?></td>
              <td scope="row"><?php $mantenimientoShow = number_format(floatval($registro['mantenimiento']), 2, '.', ',');
                              echo $mantenimientoShow; ?></td>
              <td scope="row"><?php echo $registro['gas'] ?></td>
              <td scope="row"><?php echo $registro['telefono'] ?></td>
              <td scope="row"><?php echo $registro['correo'] ?></td>
              <td scope="row"><?php echo $registro['fecha_ultimo_pago'] ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<br>
<div class="card">
  <div class="card-header text-center  bg-dark text-white">
    <h4>PAGOS</h4>
  </div>
  <div class="card-body">

    <!-- Botón para abrir el modal -->
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoPago">
      ➕ Nuevo Pago
    </button>

    <!-- Modal NUEVO PAGO -->
    <div class="modal fade" id="modalNuevoPago" tabindex="-1" aria-labelledby="modalNuevoPagoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <!-- Header -->
          <div class="modal-header bg-dark text-white">
            <h2 class="modal-title" id="modalNuevoPagoLabel">REGISTRAR PAGO</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <!-- Body con el formulario -->
          <div class="modal-body text-bold bg-success text-white">
            <form action="" method="POST" id="formNuevoPago">
              <div class="row">
                <!-- Apartamento -->
                <div class="mb-3 col-2 fs-5 fw-bold">
                  <label for="id_apto" class="form-label">Apto:</label>
                  <input type="text" class="form-control fs-5" id="id_apto" name="id_apto_display" value="<?php echo $registro['apto']; ?>" readonly>
                </div>

                <!-- Condómino -->
                <div class="mb-3 col-10 fs-5 fw-bold">
                  <label for="condomino" class="form-label">Condomino:</label>
                  <input type="text" class="form-control fs-5" id="condomino" name="condomino_display" value="<?php echo $registro['condominos']; ?>" readonly>
                </div>
              </div>

              <!-- Concepto -->
              <div class="mb-3 fs-5 fw-bold">
                <label for="concepto" class="form-label">Concepto</label>
                <input type="text" class="form-control fs-5" id="concepto" name="concepto" value="Pago del Mes" required>
              </div>

              <!-- Monto -->
              <div class="mb-3 fs-5 fw-bold">
                <label for="monto" class="form-label">Monto (RD$)</label>
                <input type="number" step="0.01" class="form-control fs-5" id="monto" name="monto" placeholder="$" required>
              </div>

              <!-- Forma de Pago -->
              <div class="mb-3 fs-5 fw-bold">
                <label for="forma_pago" class="form-label">Forma de Pago</label>
                <select name="forma_pago" id="forma_pago" class="form-select fs-5" required>
                  <option value="">Seleccione...</option>
                  <option value="Efectivo">Efectivo</option>
                  <option value="Transferencia">Transferencia</option>
                  <option value="Cheque">Cheque</option>
                </select>
              </div>

              <!-- fecha de pago -->
              <div class="mb-3 fs-5 fw-bold">
                <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                <input type="date" class="form-control fs-5 bg-warning text-dark" id="fecha_pago" name="fecha_pago" required>
              </div>

              <!-- Inputs ocultos -->
              <input type="hidden" name="usuario_registro" value="<?php echo $_SESSION['usuario']; ?>">
              <input type="hidden" name="id_apto" value="<?php echo $registro['id']; ?>">
            </form>
          </div>

          <!-- Footer con botones -->
          <div class="modal-footer bg-dark text-white">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="formNuevoPago" class="btn btn-primary">💾 Guardar Pago</button>
          </div>
        </div>
      </div>
    </div>

    <br><br>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th scope="col">Concepto</th>
            <th scope="col">Monto</th>
            <th scope="col">Forma de Pago</th>
            <th scope="col">Fecha de Pago</th>
            <th scope="col">Registrado por</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_pagos as $registro) { ?>
            <tr>
              <td>
                <button class="btn btn-primary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#modalEditarPago"
                  data-id="<?php echo $registro['id_pago']; ?>"
                  data-apto="<?php echo $registro['id_apto']; ?>"
                  data-concepto="<?php echo $registro['concepto']; ?>"
                  data-monto="<?php echo $registro['monto']; ?>"
                  data-forma="<?php echo $registro['forma_pago']; ?>"
                  data-fecha="<?php echo $registro['fecha_pago']; ?>"
                  data-usuario="<?php echo $registro['usuario_registro']; ?>">
                  <?php echo $registro['concepto']; ?>
                </button>
              </td>
              <td><?php echo number_format(floatval($registro['monto']), 2, '.', ','); ?></td>
              <td><?php echo $registro['forma_pago']; ?></td>
              <td><?php $fechaDB = $registro['fecha_pago'];
                  $fechaFormateada = date("d/m/Y", strtotime($fechaDB));
                  echo $fechaFormateada;
                  ?></td>
              <td><?php echo $registro['usuario_registro']; ?></td>
              <td>
                <a class="btn btn-danger btn-sm"
                  href="javascript:eliminarPago(<?php echo $registro['id_pago']; ?>)"
                  title="Eliminar pago">
                  ❌
                </a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- Modal EDITAR PAGO -->
    <div class="modal fade" id="modalEditarPago" tabindex="-1" aria-labelledby="modalEditarPagoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h3 class="modal-title" id="modalEditarPagoLabel">Editar Pago</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form action="" method="post" id="formEditarPago">
              <input type="hidden" name="id_pago" id="pago_id">

              <div class="mb-3">
                <label class="form-label">Apartamento</label>
                <input type="text" class="form-control" name="id_apto" id="pago_apto" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Concepto</label>
                <input type="text" class="form-control" name="concepto" id="pago_concepto">
              </div>
              <div class="mb-3">
                <label class="form-label">Monto</label>
                <input type="number" step="0.01" class="form-control" name="monto" id="pago_monto">
              </div>
              <div class="mb-3">
                <label class="form-label">Forma de Pago</label>
                <select name="forma_pago" id="pago_forma" class="form-select">
                  <option value="Efectivo">Efectivo</option>
                  <option value="Transferencia">Transferencia</option>
                  <option value="Cheque">Cheque</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha de Pago</label>
                <input type="datetime-local" class="form-control" name="fecha_pago" id="pago_fecha">
              </div>
              <div class="mb-3">
                <label class="form-label">Registrado por</label>
                <input type="text" class="form-control" name="usuario_registro" id="pago_usuario" readonly>
              </div>

              <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Script para el modal de EDITAR PAGO
      var modalEditarPago = document.getElementById('modalEditarPago');
      modalEditarPago.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;

        document.getElementById('pago_id').value = button.getAttribute('data-id');
        document.getElementById('pago_apto').value = button.getAttribute('data-apto');
        document.getElementById('pago_concepto').value = button.getAttribute('data-concepto');
        document.getElementById('pago_monto').value = button.getAttribute('data-monto');
        document.getElementById('pago_forma').value = button.getAttribute('data-forma');

        // Formatear la fecha para datetime-local
        var fechaOriginal = button.getAttribute('data-fecha');
        var fechaFormateada = fechaOriginal.replace(' ', 'T');
        document.getElementById('pago_fecha').value = fechaFormateada;

        document.getElementById('pago_usuario').value = button.getAttribute('data-usuario');
      });

      // Función para eliminar pago
      function eliminarPago(idPago) {
        Swal.fire({
          title: '¿Eliminar este pago?',
          text: "Esta acción no se puede deshacer",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location = "?txID=<?php echo $txtID; ?>&eliminar_pago=" + idPago;
          }
        });
      }
    </script>
  </div>
</div>
<br>
<div class="card">
  <div class="card-header text-center bg-dark text-white">
    <h4>TICKETS</h4>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Mes</th>
            <th>Mant.</th>
            <th>Mora</th>
            <th>Gas</th>
            <th>Cuota</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_tickets as $ticket) {
            $total_ticket = $ticket['mantenimiento'] + $ticket['mora'] + $ticket['gas'] + $ticket['cuota'];
          ?>
            <tr>
              <td>
                <button class="btn btn-primary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#modalTicket"
                  data-id="<?php echo $ticket['id']; ?>"
                  data-mes="<?php echo $ticket['mes']; ?>"
                  data-mantenimiento="<?php echo $ticket['mantenimiento']; ?>"
                  data-mora="<?php echo $ticket['mora']; ?>"
                  data-gas="<?php echo $ticket['gas']; ?>"
                  data-cuota="<?php echo $ticket['cuota']; ?>"
                  data-estado="<?php echo $ticket['estado']; ?>"
                  data-fecha="<?php echo $ticket['fecha_actual']; ?>">
                  <?php echo $ticket['mes']; ?>
                </button>
              </td>
              <td><?php echo number_format($ticket['mantenimiento'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['mora'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['gas'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['cuota'], 2, '.', ','); ?></td>
              <td><strong><?php echo number_format($total_ticket, 2, '.', ','); ?></strong></td>
              <td>
                <span class="badge <?php echo $ticket['estado'] == 'Pago' ? 'bg-success' : 'bg-warning'; ?>">
                  <?php echo $ticket['estado']; ?>
                </span>
              </td>
              <td><?php $fechaDB = $ticket['fecha_actual'];
                  $fechaFormateada = date("d/m/Y", strtotime($fechaDB));
                  echo $fechaFormateada;
                  ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- Modal Editar Ticket -->
    <div class="modal fade" id="modalTicket" tabindex="-1" aria-labelledby="modalTicketLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h3 class="modal-title">Editar Ticket</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body bg-success text-white">
            <form method="POST" id="formEditarTicket">
              <input type="hidden" name="formEditarTicket" value="1">
              <input type="hidden" name="id" id="ticket_id">

              <div class="row mb-3">
                <div class="col-4">
                  <label>Mes:</label>
                  <input type="text" class="form-control" id="ticket_mes" name="mes" readonly>
                </div>
                <div class="col-4">
                  <label>Mantenimiento:</label>
                  <input type="number" step="0.01" class="form-control" name="mantenimiento" id="ticket_mantenimiento" value="<?php echo $varmantenimiento; ?>" required>
                </div>
                <div class="col-4">
                  <label>Gas:</label>
                  <input type="number" step="0.01" class="form-control" name="gas" id="ticket_gas" value="<?php echo $gas_actual; ?>" readonly>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-4">
                  <label>Cuota Extra:</label>
                  <input type="number" step="0.01" class="form-control" name="cuota_e" id="ticket_cuota" readonly>
                </div>
                <div class="col-4">
                  <label>Mora:</label>
                  <input type="number" step="0.01" class="form-control" name="mora" id="ticket_mora">
                </div>
                <div class="col-4">
                  <label>Fecha:</label>
                  <input type="date" class="form-control bg-warning text-dark" name="fecha_actual" id="ticket_fecha" required>
                </div>
              </div>

              <div class="mb-3">
                <label>Total:</label>
                <input type="number" step="0.01" class="form-control" name="total" id="ticket_total" readonly required>
              </div>

              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="estado_pago" id="estado_pago">
                <label class="form-check-label" for="estado_pago">Marcar como Pagado</label>
              </div>

              <div class="mb-3">
                <button type="button" id="btnAplicarMora" class="btn btn-danger mt-1">Aplicar Mora</button>
                <button type="button" id="btnCalcular" class="btn btn-primary mt-1">Calcular Total</button>
              </div>

          </div>
          <div class="modal-footer bg-dark text-white">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="formEditarTicket" class="btn btn-primary">💾 Guardar cambios</button>
          </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const varMora = <?php echo isset($varmora) ? $varmora : 0; ?>;
  const modalTicketEl = document.getElementById('modalTicket');
  const mantInput = document.getElementById('ticket_mantenimiento');
  const cuotaInput = document.getElementById('ticket_cuota');
  const gasInput = document.getElementById('ticket_gas');
  const moraInput = document.getElementById('ticket_mora');
  const totalInput = document.getElementById('ticket_total');
  const btnAplicar = document.getElementById('btnAplicarMora');
  const btnCalcular = document.getElementById('btnCalcular');

  function calcularTotalTicket() {
    const mant = parseFloat(mantInput.value) || 0;
    const cuota = parseFloat(cuotaInput.value) || 0;
    const mora = parseFloat(moraInput.value) || 0;
    const gas = parseFloat(gasInput.value) || 0;
    totalInput.value = (mant + cuota + mora + gas).toFixed(2);
  }

  btnAplicar.addEventListener('click', () => {
    const mant = parseFloat(mantInput.value) || 0;
    const cuota = parseFloat(cuotaInput.value) || 0;
    const moraCalc = (mant + cuota) * varMora;
    moraInput.value = moraCalc.toFixed(2);
    calcularTotalTicket();
  });

  btnCalcular.addEventListener('click', () => {
    calcularTotalTicket();
  });

  mantInput.addEventListener('input', calcularTotalTicket);
  cuotaInput.addEventListener('input', calcularTotalTicket);
  gasInput.addEventListener('input', calcularTotalTicket);
  moraInput.addEventListener('input', calcularTotalTicket);

  modalTicketEl.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;

    document.getElementById('ticket_id').value = button.getAttribute('data-id');
    document.getElementById('ticket_mes').value = button.getAttribute('data-mes');

    // Si el mantenimiento del ticket es 0, usar el valor por defecto del apartamento
    const mantTicket = button.getAttribute('data-mantenimiento');
    mantInput.value = mantTicket > 0 ? mantTicket : <?php echo $varmantenimiento; ?>;

    moraInput.value = button.getAttribute('data-mora');
    gasInput.value = button.getAttribute('data-gas');
    cuotaInput.value = button.getAttribute('data-cuota');

    // Formatear fecha para input date
    var fechaOriginal = button.getAttribute('data-fecha');
    var fechaFormateada = fechaOriginal.split(' ')[0]; // Tomar solo la parte de la fecha
    document.getElementById('ticket_fecha').value = fechaFormateada;

    // Marcar checkbox si está pagado
    const estadoCheckbox = document.getElementById('estado_pago');
    estadoCheckbox.checked = button.getAttribute('data-estado') === 'Pago';

    // Recalcular el total
    calcularTotalTicket();
  });
</script>

</div>
</div>

<br>
<?php include("../../templates/footer.php"); ?>