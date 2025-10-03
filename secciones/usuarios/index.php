<?php 

include("../../bd.php");


if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("DELETE FROM tbl_usuarios WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$sentencia=$conexion->prepare("SELECT * FROM tbl_usuarios");
$sentencia->execute();
$lista_tbl_usuarios=$sentencia->fetchAll((PDO::FETCH_ASSOC));

?>
<?php include("../../templates/header.php"); ?>

<?php       
   if($_SESSION['puesto'] == 'administrador'){         
      include("index2.php");
   }else{
      include("mensaje_no_admin.php");
   }
?>

<?php include("../../templates/footer.php"); ?>