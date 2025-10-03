<?php

include("../../bd.php");

if (isset($_GET['txID'])) {

  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

  $sentencia = $conexion->prepare("SELECT * FROM tbl_condominios WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);
  $nombre = $registro["nombre"];
  $ubicacion = $registro["ubicacion"];
  $cuenta_bancaria = $registro["cuenta_bancaria"];
  $telefono = $registro["telefono"];
  $id_empresa = $registro["id_empresa"];
  $mora = $registro["mora"];
  $gas = $registro["gas"];
  $no_aptos = $registro["no_aptos"];
  $saldo_actual = $registro["saldo_actual"];
  $fecha_ingreso = $registro["fecha_ingreso"];
}
if ($_POST) {
  print_r($_POST);


  //recoleccion de datos
  $nombre = (isset($_POST["nombre"]) ? $_POST["nombre"] : "");
  $ubicacion = (isset($_POST["ubicacion"]) ? $_POST["ubicacion"] : "");
  $cuenta_bancaria = (isset($_POST["cuenta_bancaria"]) ? $_POST["cuenta_bancaria"] : "");
  $telefono = (isset($_POST["telefono"]) ? $_POST["telefono"] : "");
  $id_empresa = (isset($_POST["id_empresa"]) ? $_POST["id_empresa"] : "");
  $mora = (isset($_POST["mora"]) ? $_POST["mora"] : "");
  $gas = (isset($_POST["gas"]) ? $_POST["gas"] : "");
  $no_aptos = (isset($_POST["no_aptos"]) ? $_POST["no_aptos"] : "");
  $saldo_actual = (isset($_POST["saldo_actual"]) ? $_POST["saldo_actual"] : "");
  $fecha_ingreso = (isset($_POST["fecha_ingreso"]) ? $_POST["fecha_ingreso"] : "");

  //preparar insercion
  $sentencia = $conexion->prepare("UPDATE tbl_condominios SET nombre=:nombre, ubicacion=:ubicacion, cuenta_bancaria=:cuenta_bancaria, telefono=:telefono, id_empresa=:id_empresa, mora=:mora, gas=:gas, no_aptos=:no_aptos, saldo_actual=:saldo_actual, fecha_ingreso=:fecha_ingreso WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->bindParam(":nombre", $nombre);
  $sentencia->bindParam(":ubicacion", $ubicacion);
  $sentencia->bindParam(":cuenta_bancaria", $cuenta_bancaria);
  $sentencia->bindParam(":telefono", $telefono);
  $sentencia->bindParam(":id_empresa", $id_empresa);
  $sentencia->bindParam(":mora", $mora);
  $sentencia->bindParam(":gas", $gas);
  $sentencia->bindParam(":no_aptos", $no_aptos);
  $sentencia->bindParam(":saldo_actual", $saldo_actual);
  $sentencia->bindParam(":fecha_ingreso", $fecha_ingreso);
  $sentencia->execute();
  header("Location:index.php");
}
?>

<?php include("../../templates/header.php"); ?>
<br><br>
<div class="center">
  <div class="card" style="font-size: 22px;">

    <div class="card-header">

      <p style="color: #000000ff; font-size: 29px;">Datos del Condominio</p>
      <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>
    </div>
    <div class="card-body">
      <form action="" method="post" enctype="multipart/form-data" class="row row-cols-1 row-cols-md-3 g-4">
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre:</label>
          <input type="text"
            class="form-control" name="nombre" id="nombre" aria-describedby="helpId" placeholder="Escriba el nombre" value="<?php echo $nombre; ?>">
        </div>
        <div class="mb-3">
          <label for="ubicacion" class="form-label">Ubicacion:</label>
          <input type="text"
            class="form-control" name="ubicacion" id="ubicacion" aria-describedby="helpId" placeholder="Escriba la ubicacion" value="<?php echo $ubicacion; ?>">
        </div>
        <div class="mb-3">
          <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria:</label>
          <input type="text"
            class="form-control" name="cuenta_bancaria" id="cuenta_bancaria" aria-describedby="helpId" placeholder="Escriba la cuenta bancaria" value="<?php echo $cuenta_bancaria; ?>">
        </div>
        <div class="mb-3">
          <label for="telefono" class="form-label">Telefono:</label>
          <input type="text"
            class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono; ?>">
        </div>
        <div class="mb-3">
          <label for="mora" class="form-label">Mora:</label>
          <input type="text"
            class="form-control" name="mora" id="mora" aria-describedby="helpId" placeholder="Escriba la mora" value="<?php echo $mora; ?>">
        </div>
        <div class="mb-3">
          <label for="gas" class="form-label">Gas:</label>
          <input type="text"
            class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas" value="<?php echo $gas; ?>">
        </div>
        <div class="mb-3">
          <label for="no_aptos" class="form-label">No aptos:</label>
          <input type="text"
            class="form-control" name="no_aptos" id="no_aptos" aria-describedby="helpId" placeholder="Escriba el numero de aptos" value="<?php echo $no_aptos; ?>">
        </div>
        <div class="mb-3">
          <label for="saldo_actual" class="form-label">Saldo Actual:</label>
          <input type="number"
            class="form-control" name="saldo_actual" id="saldo_actual" aria-describedby="helpId" placeholder="Escriba el saldo actual" value="<?php echo $saldo_actual; ?>">
        </div>
        <div class="mb-3">
          <label for="fecha_ingreso" class="form-label">Fecha de Ingreso:</label>
          <input type="date"
            class="form-control" name="fecha_ingreso" id="fecha_ingreso" aria-describedby="helpId" placeholder="Escriba la fecha de ingreso" value="<?php echo $fecha_ingreso; ?>">
        </div>
        <div class="mb-3">
          <button type="submit" class="btn btn-success">Actualizar</button>
          <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
        </div>
      </form>
    </div>
    <div class="card-footer text-muted">
    </div>
  </div>
  <br>
  <?php include("../../templates/footer.php"); ?>