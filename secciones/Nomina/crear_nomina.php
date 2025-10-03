<?php include("../../templates/header.php");?>
<?php include("../../bd.php");

$txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

if($_POST){
  print_r($_POST);

    $idempleado=(isset($_POST["idempleado"])?$_POST["idempleado"]:"");
    $d_f=(isset($_POST["d_f"])?$_POST["d_f"]:"");
    $d_e=(isset($_POST["d_e"])?$_POST["d_e"]:"");
    $desc_tss=(isset($_POST["desc_tss"])?$_POST["desc_tss"]:"");
    $dia=(isset($_POST["dia"])?$_POST["dia"]:"");

    $sentencia=$conexion->prepare("INSERT INTO tbl_nomina (idempleado,d_f,d_e,desc_tss,dia,mes,anio,idcondominio)
    VALUES (:idempleado, :d_f, :d_e, :desc_tss, :dia, :mes, :anio, :idcondominio)");

    $sentencia->bindParam(":idempleado", $idempleado);
    $sentencia->bindParam(":d_f", $d_f);
    $sentencia->bindParam(":d_e", $d_e);
    $sentencia->bindParam(":desc_tss", $desc_tss);
    $sentencia->bindParam(":dia", $dia);
    $sentencia->bindParam(":mes", $mes);
    $sentencia->bindParam(":anio", $aniio);
    $sentencia->bindParam(":idcondominio", $idcondominio);
    $sentencia->execute();
    header("Location:index.php");

}?>

<br><br><br>

<div class="containerr">
  <div class="left">
    <?php include('../../left_panel.php')?>
  </div>
    <div class="center">
    <div class="card">
        <div class="card-header">
            Crear Nomina
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                  <label for="idempleado" class="form-label">idempleado:</label>
                  <input type="text"
                    class="form-control" name="idempleado" id="idempleado" aria-describedby="helpId" placeholder="Escriba los nombres" readonly value="<?php echo $txtID ?>">
                </div>
                <div class="mb-3">
                  <label for="d_f" class="form-label">Dias Feriados:</label>
                  <input type="text"
                    class="form-control" name="d_f" id="d_f" aria-describedby="helpId" placeholder="Escriba el telefono">
                </div>
                <div class="mb-3">
                  <label for="d_e" class="form-label">Dias Extra:</label>
                  <input type="text"
                    class="form-control" name="d_e" id="d_e" aria-describedby="helpId" placeholder="Escriba el cargo">
                </div>
                <div class="mb-3">
                  <label for="desc_tss" class="form-label">Descuento TSS:</label>
                  <input type="text"
                    class="form-control" name="desc_tss" id="desc_tss" aria-describedby="helpId" placeholder="Escriba el horario">
                </div>
                <div class="mb-3">
                  <label for="dia">Dia de pago:</label><br>
                  <select name="dia" id="dia">
                    <option value="15">Dia 15</option>
                    <option value="30">Dia 30</option>
                  </select>
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