<?php

include("../../bd.php");
    
   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

if($_POST){
  print_r($_POST);  
    //recoleccion de datos    
   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
    $ano=(isset($_POST["ano"])?$_POST["ano"]:"");
    //preparar insercion
    $sentencia=$conexion->prepare("INSERT INTO tbl_meses_debidos (id_apto,ano)
    VALUES (:id,:ano)");
    //Asignando los valores de metodo post(del formulario)
   $sentencia->bindParam(":id", $txtID);
    $sentencia->bindParam(":ano", $ano);
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
                  <label for="nombre" class="form-label">ID:</label>
                  <input type="text" class="form-control" name="txtID" id="txtID" aria-describedby="helpId" placeholder="id" value="<?php echo $txtID;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="ubicacion" class="form-label">Año:</label>
                  <input type="number" class="form-control" name="ano" id="ano" aria-describedby="helpId" placeholder="Escriba el año">
                </div>

                <button type="sumit" class="btn btn-success">Agregar</button>
                <a name="" id="" class="btn btn-danger" href="editar.php" role="button">Cancelar</a>
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