<?php

include("../../bd.php");
if($_POST){
  print_r($_POST);

  
    //recoleccion de datos
    $nombredelusuario=(isset($_POST["nombredelusuario"])?$_POST["nombredelusuario"]:"");
    $password=(isset($_POST["password"])?$_POST["password"]:"");
    $correo=(isset($_POST["correo"])?$_POST["correo"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("INSERT INTO tbl_usuarios (id,usuario,password,correo)
    VALUES (null, :usuario, :password, :correo)");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":usuario", $nombredelusuario);
    $sentencia->bindParam(":password", $password);
    $sentencia->bindParam(":correo", $correo);
    $sentencia->execute();
    header("Location:index.php");

}

?>


<?php include("../../templates/header.php");?>
<br/> 
    <div class="card">
        <div class="card-header">
            Datos del Usuarios
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                  <label for="nombredelusuario" class="form-label">Nombre del usuario:</label>
                  <input type="text"
                    class="form-control" name="nombredelusuario" id="nombredelusuario" aria-describedby="helpId" placeholder="Nombre del usuario">
                </div>
               <div class="mb-3">
                 <label for="password" class="form-label">Contraseña</label>
                 <input type="password"
                   class="form-control" name="password" id="password" aria-describedby="helpId" placeholder="Contraseña">
               </div>

               <div class="mb-3">
                 <label for="correo" class="form-label">Correo</label>
                 <input type="email" class="form-control" name="correo" id="correo" aria-describedby="emailHelpId" placeholder="correo electronico">
               </div>

                <button type="sumit" class="btn btn-success">Agregar</button>
                <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>


<?php include("../../templates/footer.php"); ?>