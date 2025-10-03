<?php
session_start();
include("../../bd.php");
if($_POST){

      
    $anio=$_SESSION['anio'];
    $mes=$_SESSION['mes'];
    $idcondominio=$_SESSION['idcondominio'];
    //recoleccion de datos
    $detalle=(isset($_POST["detalle"])?$_POST["detalle"]:"");
    $monto=(isset($_POST["monto"])?$_POST["monto"]:"");
    $tipo_cheque=$_POST["sel_mas_menos"]; 

        //preparar insercion
    $sentencia=$conexion->prepare("INSERT INTO tbl_cheques (detalle,tipo_cheque,monto,idcondominio,mes,anio)
    VALUES (:detalle, :tipo_cheque, :monto, :idcondominio, :mes, :anio)");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":detalle", $detalle);
    $sentencia->bindParam(":tipo_cheque", $tipo_cheque);
    $sentencia->bindParam(":monto", $monto);
    $sentencia->bindParam(":idcondominio", $idcondominio);
    $sentencia->bindParam(":mes", $mes);
    $sentencia->bindParam(":anio", $anio);
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

 
<div class="card">
        <div class="card-header">
        <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a> 
        </div>
      <div class="card-body">
        
         <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
               <h2 class="form-label" for="add_mas_cheque">Añadir Cheque:</h2><br> 
          <form method="POST" style="display: block;">
            <h5 for="mes">Seleccione un mes:</h4>
            <select name="sel_mas_menos" id="mes">
              <option value="mas">Mas (+) Depositos en Transito</option>
              <option value="menos">Menos (-) Cheques en Transito</option>
              <option value="nulo">Cheque nulo</option>
            </select>
              
            <br><br> 
            
            <div class="mb-3">
               <h6 for="text" class="form-label">Detalles:</h6>
               <input type="text" class="form-control" name="detalle" id="detalle" aria-describedby="helpId" placeholder="introducir Cheque" value="Cheque No. 0000">
            </div>
            <div class="mb-3">
               <h6 for="monto" class="form-label">Monto:</h6>
               <input type="text" class="form-control" name="monto" id="monto" aria-describedby="helpId" placeholder="introducir monto" value="0">
            </div>
               <br>
               <button type="sumit" class="btn btn-success">Agregar</button>
            </div>      
         </form>
      </div> 
   </div> 
 <br><br><br> 

      <div class="card-footer text-muted">
      </div>
    </div>   

<div class="right">
<?php include('../../right_panel.php')?>
</div>
</div>
<br>
<?php include("../../templates/footer.php"); ?>