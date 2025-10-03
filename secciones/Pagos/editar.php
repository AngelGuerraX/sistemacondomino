<?php include("../../templates/header.php");
include("../../bd.php");

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
    //////////////////////////////////////////////


    echo "✅ Pago registrado y balance actualizado correctamente.";
  } catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error al registrar el pago: " . $e->getMessage() . "</div>";
  }
}



if (isset($_POST['boton'])) {

  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
  //recoleccion de datos
  $id = (isset($_POST["id"]) ? $_POST["id"] : "");
  $mantenimiento = (isset($_POST["mantenimiento"]) ? $_POST["mantenimiento"] : "");
  $gas = (isset($_POST["gas"]) ? $_POST["gas"] : "");
  $mora = (isset($_POST["mora"]) ? $_POST["mora"] : "");
  $cuota = (isset($_POST["cuota_e"]) ? $_POST["cuota_e"] : "");
  // Verificación del checkbox y asignación del valor correspondiente
  $estado_pago = isset($_POST['estado_pago']) ? 'Pago' : 'Pendiente';

  //preparar insercion
  $sentencia = $conexion->prepare("UPDATE tbl_tickets SET mantenimiento=:mantenimiento, gas=:gas, mora=:mora, cuota=:cuota, estado=:estado 
    WHERE id=:id");
  //Asignando los valores de metodo post(del formulario)
  $sentencia->bindParam(":mantenimiento", $mantenimiento);
  $sentencia->bindParam(":gas", $gas);
  $sentencia->bindParam(":mora", $mora);
  $sentencia->bindParam(":cuota", $cuota);
  $sentencia->bindParam(":estado", $estado_pago);
  $sentencia->bindParam(":id", $id);
  $sentencia->execute();
};


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

$sentencia = $conexion->prepare("SELECT * FROM tbl_pagos where id_condominio=:idcondominio and id_apto=:txtID");
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
          <?php
          } ?>
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
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
      ➕ Nuevo Pago
    </button>


    <!-- Modal -->
    <div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">

          <!-- Header -->
          <div class="modal-header bg-dark text-white">
            <h2 class="modal-title" id="modalPagoLabel">REGISTRAR PAGO</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <!-- Body con el formulario -->
          <div class="modal-body text-bold bg-success text-white">
            <form action="" method="POST" id="pagos">
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
            <button type="submit" form="pagos" class="btn btn-primary">💾 Guardar Pago</button>
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
            <th scope="col">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_pagos as $registro) { ?>
            <tr>
              <td><?php echo $registro['concepto']; ?></td>
              <td><?php echo number_format(floatval($registro['monto']), 2, '.', ','); ?></td>
              <td><?php echo $registro['forma_pago']; ?></td>
              <td><?php echo $registro['fecha_pago']; ?></td>
              <td><?php echo $registro['usuario_registro']; ?></td>
              <td>
                <button class="btn btn-primary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#modalPago"
                  data-id="<?php echo $registro['id_pago']; ?>"
                  data-apto="<?php echo $registro['id_apto']; ?>"
                  data-concepto="<?php echo $registro['concepto']; ?>"
                  data-monto="<?php echo $registro['monto']; ?>"
                  data-forma="<?php echo $registro['forma_pago']; ?>"
                  data-fecha="<?php echo $registro['fecha_pago']; ?>"
                  data-usuario="<?php echo $registro['usuario_registro']; ?>">
                  Editar
                </button>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- Modal único para editar pagos -->
    <div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h3 class="modal-title" id="modalPagoLabel">Editar Pago</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form action="" method="post" id="formPago">
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
      var modalPago = document.getElementById('modalPago');
      modalPago.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;

        document.getElementById('pago_id').value = button.getAttribute('data-id');
        document.getElementById('pago_apto').value = button.getAttribute('data-apto');
        document.getElementById('pago_concepto').value = button.getAttribute('data-concepto');
        document.getElementById('pago_monto').value = button.getAttribute('data-monto');
        document.getElementById('pago_forma').value = button.getAttribute('data-forma');
        document.getElementById('pago_fecha').value = button.getAttribute('data-fecha').replace(' ', 'T');
        document.getElementById('pago_usuario').value = button.getAttribute('data-usuario');
      });
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
            <th>Fecha</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_tickets as $ticket) { ?>
            <tr>
              <td><?php echo $ticket['mes']; ?></td>
              <td><?php echo number_format($ticket['mantenimiento'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['mora'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['gas'], 2, '.', ','); ?></td>
              <td><?php echo number_format($ticket['cuota'], 2, '.', ','); ?></td>
              <td><?php echo $ticket['fecha_actual']; ?></td>
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
                  Editar
                </button>
              </td>
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
              <input type="hidden" name="id" id="ticket_id">

              <div class="row mb-3">
                <div class="col-4">
                  <label>Mes:</label>
                  <input type="text" class="form-control" id="ticket_mes" readonly>
                </div>
                <div class="col-4">
                  <label>Mantenimiento:</label>
                  <input type="number" step="0.01" class="form-control" name="mantenimiento" id="ticket_mantenimiento">
                </div>
                <div class="col-4">
                  <label>Gas:</label>
                  <input type="number" step="0.01" class="form-control" name="gas" id="ticket_gas">
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-4">
                  <label>Cuota Extra:</label>
                  <input type="number" step="0.01" class="form-control" name="cuota" id="ticket_cuota">
                </div>
                <div class="col-4">
                  <label>Mora:</label>
                  <input type="number" step="0.01" class="form-control" name="mora" id="ticket_mora">
                </div>
                <div class="col-4">
                  <label>Fecha:</label>
                  <input type="date" class="form-control bg-warning text-dark" name="fecha_actual" id="ticket_fecha">
                </div>
              </div>

              <div class="mb-3">
                <label>Total:</label>
                <input type="number" step="0.01" class="form-control" name="total" id="ticket_total" readonly>
              </div>

              <div class="mb-3">
                <button type="button" id="btnAplicarMora" class="btn btn-danger mt-1">Aplicar Mora</button>
                <button type="button" id="btnCalcular" class="btn btn-primary mt-1">Calcular</button>
              </div>

            </form>
          </div>
        </div>
        <div class="modal-footer bg-dark text-white">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" form="formEditarTicket" class="btn btn-primary">💾 Guardar cambios</button>
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
  const moraInput = document.getElementById('ticket_mora');
  const totalInput = document.getElementById('ticket_total');
  const btnAplicar = document.getElementById('btnAplicarMora');



  function calcularTotalTicket() {
    const mant = parseFloat(mantInput.value) || 0;
    const cuota = parseFloat(cuotaInput.value) || 0;
    const mora = parseFloat(moraInput.value) || 0;
    totalInput.value = (mant + cuota + mora).toFixed(2);
  }

  btnAplicar.addEventListener('click', () => {
    const mant = parseFloat(mantInput.value) || 0;
    const cuota = parseFloat(cuotaInput.value) || 0;
    const moraCalc = (mant + cuota) * varMora;
    moraInput.value = moraCalc.toFixed(2);
    calcularTotalTicket();
  });

  mantInput.addEventListener('input', calcularTotalTicket);
  cuotaInput.addEventListener('input', calcularTotalTicket);

  modalTicketEl.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('ticket_id').value = button.getAttribute('data-id');
    document.getElementById('ticket_mes').value = button.getAttribute('data-mes');
    mantInput.value = button.getAttribute('data-mantenimiento');
    moraInput.value = button.getAttribute('data-mora');
    document.getElementById('ticket_gas').value = button.getAttribute('data-gas');
    cuotaInput.value = button.getAttribute('data-cuota');
    document.getElementById('ticket_estado').checked = button.getAttribute('data-estado') === 'Pago';
    document.getElementById('ticket_fecha').value = button.getAttribute('data-fecha');
    calcularTotalTicket();
  });
</script>

</div>
</div>


<br>
<?php include("../../templates/footer.php"); ?>