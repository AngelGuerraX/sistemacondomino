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

if ($_POST) {

  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
  //recoleccion de datos
  $apto = (isset($_POST["apto"]) ? $_POST["apto"] : "");
  $condominos = (isset($_POST["condominos"]) ? $_POST["condominos"] : "");
  $mantenimiento = (isset($_POST["mantenimiento"]) ? $_POST["mantenimiento"] : "");
  $gas = (isset($_POST["gas"]) ? $_POST["gas"] : "");
  $telefono = (isset($_POST["telefono"]) ? $_POST["telefono"] : "");
  $correo = (isset($_POST["correo"]) ? $_POST["correo"] : "");
  $forma_de_pago = (isset($_POST["forma_de_pago"]) ? $_POST["forma_de_pago"] : "");
  $fecha_ultimo_pago = (isset($_POST["fecha_ultimo_pago"]) ? $_POST["fecha_ultimo_pago"] : "");

  //preparar insercion
  $sentencia = $conexion->prepare("UPDATE tbl_aptos SET apto=:apto, condominos=:condominos, mantenimiento=:mantenimiento, gas=:gas, telefono=:telefono, correo=:correo, forma_de_pago=:forma_de_pago , fecha_ultimo_pago=:fecha_ultimo_pago 
    WHERE id=:id");

  //Asignando los valores de metodo post(del formulario)
  $sentencia->bindParam(":apto", $apto);
  $sentencia->bindParam(":condominos", $condominos);
  $sentencia->bindParam(":mantenimiento", $mantenimiento);
  $sentencia->bindParam(":gas", $gas);
  $sentencia->bindParam(":telefono", $telefono);
  $sentencia->bindParam(":correo", $correo);
  $sentencia->bindParam(":forma_de_pago", $forma_de_pago);
  $sentencia->bindParam(":fecha_ultimo_pago", $fecha_ultimo_pago);
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  header("Location:index.php");
};
?>
<br>
<div class="card" style="font-size: 22px;">
  <div class="card-header">
    <p style="color: #0d0505ff; font-size: 29px;">Datos del Condominio</p>
    <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>
  </div>
  <div class="card">
    <div class="card-body">
      <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="nombre" class="form-label">Apto:</label>
          <input type="text"
            class="form-control" name="apto" id="apto" aria-describedby="helpId" placeholder="Escriba el no. de apto" value="<?php echo $apto; ?>">
        </div>
        <div class="mb-3">
          <label for="ubicacion" class="form-label">Condominos:</label>
          <input type="text"
            class="form-control" name="condominos" id="condominos" aria-describedby="helpId" placeholder="Escriba los condominos" value="<?php echo $condominos; ?>">
        </div>
        <div class="mb-3">
          <label for="cuenta_bancaria" class="form-label">Mantenimiento</label>
          <input type="text"
            class="form-control" name="mantenimiento" id="mantenimiento" aria-describedby="helpId" placeholder="Escriba el mantenimiento" value="<?php $mantenimientoshow = number_format(floatval($registro['mantenimiento']), 2, '.', ',');
                                                                                                                                                  echo $mantenimientoshow; ?>">
        </div>
        <div class="mb-3">
          <label for="telefono" class="form-label">Gas:</label>
          <input type="text"
            class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas" value="<?php echo $gas; ?>">
        </div>
        <div class="mb-3">
          <label for="mora" class="form-label">Correo:</label>
          <input type="email"
            class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Escriba la correo" value="<?php echo $correo; ?>">
        </div>
        <div class="mb-3">
          <label for="gas" class="form-label">Telefono:</label>
          <input type="text"
            class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono; ?>">
        </div>
        <div class="mb-3">
          <label for="no_aptos" class="form-label">Metodo de pago:</label>
          <input type="text"
            class="form-control" name="forma_de_pago" id="forma_de_pago" aria-describedby="helpId" placeholder="Escriba la forma de pago" value="<?php echo $forma_de_pago; ?>">
        </div>
        <div class="mb-3">
          <label for="saldo_actual" class="form-label">Ultimo Pago:</label>
          <input type="text"
            class="form-control" name="fecha_ultimo_pago" id="fecha_ultimo_pago" aria-describedby="helpId" placeholder="" value="<?php echo $fecha_ultimo_pago; ?>">
        </div>

        <button type="sumit" class="btn btn-success">Actualizar</button>
        <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
      </form>
    </div>
    <div class="card-footer text-muted">
    </div>
  </div>
</div>
</div>
<br>


<?php include("../../templates/footer.php"); ?>