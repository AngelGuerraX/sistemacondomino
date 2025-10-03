<?php include("../../templates/header.php");
include("../../bd.php");

if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("SELECT * FROM tbl_empleados WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();   
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);
   $nombres=$registro["nombres"];
   $apellidos=$registro["apellidos"];
   $cedula_pasaporte=$registro["cedula_pasaporte"];
   $telefono=$registro["telefono"];
   $cargo=$registro["cargo"];
   $horario=$registro["horario"];
   $fecha_ingreso=$registro["fecha_ingreso"];
   $salario=$registro["salario"];
   $forma_de_pago=$registro["forma_de_pago"];
}

if($_POST){

  $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
    //recoleccion de datos
    $nombres=(isset($_POST["nombres"])?$_POST["nombres"]:"");
    $apellidos=(isset($_POST["apellidos"])?$_POST["apellidos"]:"");
    $cedula_pasaporte=(isset($_POST["cedula_pasaporte"])?$_POST["cedula_pasaporte"]:"");
    $telefono=(isset($_POST["telefono"])?$_POST["telefono"]:"");
    $cargo=(isset($_POST["cargo"])?$_POST["cargo"]:"");
    $horario=(isset($_POST["horario"])?$_POST["horario"]:"");
    $fecha_ingreso=(isset($_POST["fecha_ingreso"])?$_POST["fecha_ingreso"]:"");
    $salario=(isset($_POST["salario"])?$_POST["salario"]:"");
    $forma_de_pago=(isset($_POST["forma_de_pago"])?$_POST["forma_de_pago"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_empleados SET nombres=:nombres, apellidos=:apellidos, cedula_pasaporte=:cedula_pasaporte, telefono=:telefono, cargo=:cargo, horario=:horario, fecha_ingreso=:fecha_ingreso, salario=:salario, forma_de_pago=:forma_de_pago 
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":nombres", $nombres);
    $sentencia->bindParam(":apellidos", $apellidos);
    $sentencia->bindParam(":cedula_pasaporte", $cedula_pasaporte);
    $sentencia->bindParam(":telefono", $telefono);
    $sentencia->bindParam(":cargo", $cargo);
    $sentencia->bindParam(":horario", $horario);
    $sentencia->bindParam(":fecha_ingreso", $fecha_ingreso);
    $sentencia->bindParam(":salario", $salario);
    $sentencia->bindParam(":forma_de_pago", $forma_de_pago);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:index.php");
};

?>
   


   
  <br><br><br>

<div class="containerr">
  <div class="left">
    <?php include('../../left_panel.php')?>
  </div>
    <div class="center">
        <div class="card">
          <div class="card-body">
            <blockquote class="blockquote mb-0">
              <p>Quote</p>
              <footer class="blockquote-footer">Footer <cite title="Source title">Source title</cite></footer>
            </blockquote>
          </div>
        </div>

        <br><br>

   <div class="card">
        <div class="card-header">
            Editar Usuarios
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                  <label for="nombres" class="form-label">Nombres:</label>
                  <input type="text"
                    class="form-control" name="nombres" id="nombres" aria-describedby="helpId" placeholder="Escriba los nombres" value="<?php echo $nombres;?>">
                </div>
                <div class="mb-3">
                  <label for="apellidos" class="form-label">Apellidos:</label>
                  <input type="text"
                    class="form-control" name="apellidos" id="apellidos" aria-describedby="helpId" placeholder="Escriba los apellidos" value="<?php echo $apellidos;?>">
                </div>
                <div class="mb-3">
                  <label for="cedula_pasaporte" class="form-label">Identificacion:</label>
                  <input type="text"
                    class="form-control" name="cedula_pasaporte" id="cedula_pasaporte" aria-describedby="helpId" placeholder="Escriba la cedula o pasaporte" value="<?php echo $cedula_pasaporte;?>">
                </div>
                <div class="mb-3">
                  <label for="telefono" class="form-label">Telefono:</label>
                  <input type="text"
                    class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono;?>">
                </div>
                <div class="mb-3">
                  <label for="cargo" class="form-label">Cargo:</label>
                  <input type="text"
                    class="form-control" name="cargo" id="cargo" aria-describedby="helpId" placeholder="Escriba el cargo" value="<?php echo $cargo;?>">
                </div>
                <div class="mb-3">
                  <label for="horario" class="form-label">Horario:</label>
                  <input type="text"
                    class="form-control" name="horario" id="horario" aria-describedby="helpId" placeholder="Escriba el horario" value="<?php echo $horario;?>">
                </div>
                <div class="mb-3">
                  <label for="fecha_ingreso" class="form-label">Ingreso:</label>
                  <input type="date"
                    class="form-control" name="fecha_ingreso" id="fecha_ingreso" aria-describedby="helpId" placeholder="Seleccione la fecha" value="<?php echo $fecha_ingreso;?>">
                </div>
                <div class="mb-3">
                  <label for="salario" class="form-label">Salario:</label>
                  <input type="number"
                    class="form-control" name="salario" id="salario" aria-describedby="helpId" placeholder="Escriba el sueldo" value="<?php echo $salario;?>">
                </div>
                <div class="mb-3">
                  <label for="forma_de_pago" class="form-label">Pago:</label>
                  <input type="text"
                    class="form-control" name="forma_de_pago" id="forma_de_pago" aria-describedby="helpId" placeholder="Escriba el metodo de pago" value="<?php echo $forma_de_pago;?>">
                </div>

                <button type="sumit" class="btn btn-success">Actualizar</button>
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