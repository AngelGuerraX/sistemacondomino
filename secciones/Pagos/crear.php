<?php

include("../../bd.php");
if($_POST){
  print_r($_POST);

  
    //recoleccion de datos
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $condominos=(isset($_POST["condominos"])?$_POST["condominos"]:"");
    $mantenimiento=(isset($_POST["mantenimiento"])?$_POST["mantenimiento"]:"");
    $gas=(isset($_POST["gas"])?$_POST["gas"]:"");
    $correo=(isset($_POST["correo"])?$_POST["correo"]:"");
    $telefono=(isset($_POST["telefono"])?$_POST["telefono"]:"");
    $forma_de_pago=(isset($_POST["forma_de_pago"])?$_POST["forma_de_pago"]:"");
    $fecha_ultimo_pago=(isset($_POST["fecha_ultimo_pago"])?$_POST["fecha_ultimo_pago"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("INSERT INTO tbl_aptos (apto,condominos,mantenimiento,gas,correo,telefono,forma_de_pago,fecha_ultimo_pago)
    VALUES (:apto, :condominos, :mantenimiento, :gas, :correo, :telefono, :forma_de_pago, :fecha_ultimo_pago)");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":condominos", $condominos);
    $sentencia->bindParam(":mantenimiento", $mantenimiento);
    $sentencia->bindParam(":gas", $gas);
    $sentencia->bindParam(":correo", $correo);
    $sentencia->bindParam(":telefono", $telefono);
    $sentencia->bindParam(":forma_de_pago", $forma_de_pago);
    $sentencia->bindParam(":fecha_ultimo_pago", $fecha_ultimo_pago);
    $sentencia->execute();
    header("Location:index.php");
}
?>

<?php include("../../templates/header.php");?>
<br><br><br>
<div class="containerr">
  <div class="left">
    <?php include('../../left_panel.php')?>
  </div>

    <div class="center">
    <p style="color: #ffffff; font-size: 29px;">Datos del Condominio</p> 
    <div class="card" style="font-size: 22px;">
    
        <div class="card-header">
          
        <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>   
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                  <label for="nombre" class="form-label">Apto:</label>
                  <input type="text"
                    class="form-control" name="apto" id="apto" aria-describedby="helpId" placeholder="Escriba el apto">
                </div>
                <div class="mb-3">
                  <label for="ubicacion" class="form-label">Condominos:</label>
                  <input type="text"
                    class="form-control" name="condominos" id="condominos" aria-describedby="helpId" placeholder="Escriba la condominos">
                </div>
                <div class="mb-3">
                  <label for="cuenta_bancaria" class="form-label">Mantenimiento</label>
                  <input type="text"
                    class="form-control" name="mantenimiento" id="mantenimiento" aria-describedby="helpId" placeholder="Escriba la mantenimiento">
                </div>
                <div class="mb-3">
                  <label for="telefono" class="form-label">Gas:</label>
                  <input type="text"
                    class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas">
                </div>
                <div class="mb-3">
                  <label for="mora" class="form-label">Correo:</label>
                  <input type="email"
                    class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Escriba la correo">
                </div>
                <div class="mb-3">
                  <label for="gas" class="form-label">Telefono:</label>
                  <input type="text"
                    class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono">
                </div>
                <div class="mb-3">
                  <label for="no_aptos" class="form-label">Metodo de pago:</label>
                  <input type="text"
                    class="form-control" name="forma_de_pago" id="forma_de_pago" aria-describedby="helpId" placeholder="Escriba el forma_de_pago">
                </div>
                <div class="mb-3">
                  <label for="saldo_actual" class="form-label">Ultimo Pago:</label>
                  <input type="text"
                    class="form-control" name="fecha_ultimo_pago" id="fecha_ultimo_pago" aria-describedby="helpId" placeholder="">
                </div>

                <button type="sumit" class="btn btn-success">Agregar</button>
                <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>    
    </div>

<div class="right">
<?php include('../../right_panel.php')?>
</div>
</div>
<br>
<?php include("../../templates/footer.php"); ?>