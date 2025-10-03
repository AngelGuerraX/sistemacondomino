<?php     

    $parametro1 = $_GET["parametro1"];
    $parametro2 = $_GET["parametro2"];

    //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_usuarios SET online=:online 
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":online", $parametro1);
    $sentencia->bindParam(":id", $parametro2);
    $sentencia->execute();

    ?>

<?php
if(isset($_GET['txID'])){

$txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

$sentencia=$conexion->prepare("SELECT * FROM tbl_condominios WHERE id=:id");
$sentencia->bindParam(":id", $txtID);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);
$nombre=$registro["nombre"];
}

if($_POST){

 $txtID_usuario= $_SESSION['id'];

 $sentencia=$conexion->prepare("UPDATE tbl_usuarios SET idcondominio=:idcondominio, online=:online WHERE id=:id");
 $sentencia->bindParam(":idcondominio", $id);
 $sentencia->bindParam(":online", $nombre);
 $sentencia->bindParam(":id", $txtID_usuario);
 $sentencia->execute();
 
};

?>