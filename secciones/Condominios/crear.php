<?php

include("../../bd.php");
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
  $sentencia = $conexion->prepare("INSERT INTO tbl_condominios (nombre,ubicacion,cuenta_bancaria,telefono,id_empresa,mora,gas,no_aptos,saldo_actual,fecha_ingreso)
    VALUES (:nombre, :ubicacion, :cuenta_bancaria, :telefono, :id_empresa, :mora, :gas, :no_aptos, :saldo_actual, :fecha_ingreso)");

  //Asignando los valores de metodo post(del formulario)
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
            class="form-control" name="nombre" id="nombre" aria-describedby="helpId" placeholder="Escriba el nombre">
        </div>
        <div class="mb-3">
          <label for="ubicacion" class="form-label">Ubicacion:</label>
          <input type="text"
            class="form-control" name="ubicacion" id="ubicacion" aria-describedby="helpId" placeholder="Escriba la ubicacion">
        </div>
        <div class="mb-3">
          <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria:</label>
          <input type="text"
            class="form-control" name="cuenta_bancaria" id="cuenta_bancaria" aria-describedby="helpId" placeholder="Escriba la cuenta bancaria">
        </div>
        <div class="mb-3">
          <label for="telefono" class="form-label">Telefono:</label>
          <input type="text"
            class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono">
        </div>
        <div class="mb-3">
          <label for="mora" class="form-label">Mora:</label>
          <input type="text"
            class="form-control" name="mora" id="mora" aria-describedby="helpId" placeholder="Escriba la mora">
        </div>
        <div class="mb-3">
          <label for="gas" class="form-label">Gas:</label>
          <input type="text"
            class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas">
        </div>
        <div class="mb-3">
          <label for="no_aptos" class="form-label">No aptos:</label>
          <input type="text"
            class="form-control" name="no_aptos" id="no_aptos" aria-describedby="helpId" placeholder="Escriba el numero de aptos">
        </div>
        <div class="mb-3">
          <label for="saldo_actual" class="form-label">Saldo Actual:</label>
          <input type="number"
            class="form-control" name="saldo_actual" id="saldo_actual" aria-describedby="helpId" placeholder="Escriba el saldo actual">
        </div>
        <div class="mb-3">
          <label for="fecha_ingreso" class="form-label">Fecha de Ingreso:</label>
          <input type="date"
            class="form-control" name="fecha_ingreso" id="fecha_ingreso" aria-describedby="helpId" placeholder="Escriba la fecha de ingreso">
        </div>
        <div class="mb-3">
          <button type="submit" class="btn btn-success">Agregar</button>
          <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
        </div>
      </form>
    </div>
    <div class="card-footer text-muted">
    </div>
  </div>
  <br>
  <?php include("../../templates/footer.php"); ?>