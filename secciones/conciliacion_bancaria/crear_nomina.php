<?php include("../../../templates/header.php");?>
<?php include("../../../bd.php");

if($_POST){
  
    //recoleccion de datos 
    $tipo_gasto='Nomina Empleados';
    $detalles=(isset($_POST["detalles"])?$_POST["detalles"]:"");
    $monto=(isset($_POST["monto"])?$_POST["monto"]:"");
    $mes=$_SESSION['mes'];
    $año=$_SESSION['anio'];
    $id_condominio=$_SESSION['idcondominio'];
    $fecha='a';
    $id_gasto='as';

//preparar insercion
$sentencia = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto,detalles,monto,mes,año,id_condominio,fecha,id_gasto)
    VALUES (:tipo_gasto, :detalles, :monto, :mes, :año, :id_condominio, :fecha, :id_gasto)");

//Asignando los valores de metodo post(del formulario)
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":detalles", $detalles);
$sentencia->bindParam(":monto", $monto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":año", $año);
$sentencia->bindParam(":id_condominio", $id_condominio);
$sentencia->bindParam(":fecha", $fecha);
$sentencia->bindParam(":id_gasto", $id_gasto); 
$sentencia->execute();


}

?>

<br><br><br>
<div class="containerr">
  <div class="left">
    <?php include('../../../left_panel.php')?>
  </div>
    <div class="center">
    <br/> 
    <div class="card">
        <div class="card-header">
            Datos del Usuarios
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                  <label for="detalles" class="form-label">Detalles:</label>
                  <input type="text"
                    class="form-control" name="detalles" id="detalles" aria-describedby="helpId" placeholder="Escriba los detalles">
                </div>
                <div class="mb-3">
                  <label for="monto" class="form-label">Monto:</label>
                  <input type="text"
                    class="form-control" name="monto" id="monto" aria-describedby="helpId" placeholder="Escriba el monto">
                </div>
                <div class="mb-3">
                  <label for="fecha" class="form-label">fecha:</label>
                  <input type="text"
                    class="form-control" name="fecha" id="fecha" aria-describedby="helpId" placeholder="Seleccione la fecha">
                </div>
                <div class="mb-3">
                  <label for="id_gasto" class="form-label">id_gasto:</label>
                  <input type="text"
                    class="form-control" name="id_gasto" id="id_gasto" aria-describedby="helpId" placeholder="Escriba el id_gasto">
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
<?php include('../../../right_panel.php')?>
</div>
</div>
<br>
<?php include("../../../templates/footer.php"); ?>