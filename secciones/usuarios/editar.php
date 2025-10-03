<?php include("../../templates/header.php");
include("../../bd.php");

if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("SELECT * FROM tbl_usuarios WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);
   $usuario=$registro["usuario"];
   $password=$registro["password"];
   $correo=$registro["correo"];

}

if($_POST){

   $txtID=(isset($_POST['txID'] ))?$_GET['txID']:"";
    //recoleccion de datos
    $usuario=(isset($_POST["usuario"])?$_POST["usuario"]:"");
    $password=(isset($_POST["password"])?$_POST["password"]:"");
    $correo=(isset($_POST["correo"])?$_POST["correo"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_usuarios SET usuario=:usuario, password=:password, correo=:correo
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":usuario", $usuario);
    $sentencia->bindParam(":password", $password);
    $sentencia->bindParam(":correo", $correo);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:index.php");
};

?>   

<br/> 
    <div class="card">
        <div class="card-header">
            Datos del Usuarios
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
               <div class="mb-3">
                 <label for="txID" class="form-label">ID:</label>
                 <input type="text"
                  value="<?php echo $txtID;?>"
                  class="form-control" name="txID" id="txID" readonly aria-describedby="helpId" placeholder="">
               </div>
                <div class="mb-3">
                  <label for="usuario" class="form-label">Nombre del usuario:</label>
                  <input type="text"
                  value="<?php echo $usuario;?>"
                    class="form-control" name="usuario" id="usuario" aria-describedby="helpId" placeholder="Nombre del usuario">
                </div>
               <div class="mb-3">
                 <label for="password" class="form-label">Contraseña</label>
                 <input type="password"
                  value="<?php echo $password;?>"
                   class="form-control" name="password" id="password" aria-describedby="helpId" placeholder="Contraseña">
               </div>

               <div class="mb-3">
                 <label for="correo" class="form-label">Correo</label>
                 <input type="email"
                  value="<?php echo $correo;?>" class="form-control" name="correo" id="correo" aria-describedby="emailHelpId" placeholder="correo electronico">
               </div>

               <button type="sumit" href="index.php" class="btn btn-success">Actualizar</button>
                <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>


<?php include("../../templates/footer.php"); ?>