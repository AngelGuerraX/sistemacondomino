<?php 
include("../../bd.php");

?>
<?php include("../../templates/header.php"); ?>
<?php include("../../css/camion_mmu.php"); ?>
<br>   
   <h3>Generar Reportes</h3>
   <br>

   
<?php       
   if($_SESSION['puesto'] == 'administrador'){         
      include("index2.php");
   }else{
      include("mensaje_no_admin.php");
   }
?>


    <script src="../../js/camion.js"></script> 
<?php include("../../templates/footer.php"); ?>