<?php include("templates/header.php");
include("bd.php");

if($_POST){
    $mes = $_POST["mes"];
    $anio = $_POST["anio"];
    $txtID= $_SESSION['id'];
    $sentencia=$conexion->prepare("UPDATE tbl_usuarios SET mes=:mes, anio=:anio WHERE id=:id");
    $sentencia->bindParam(":mes", $mes);
    $sentencia->bindParam(":anio", $anio);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    
$sentencia=$conexion->prepare("SELECT *,count(*) as n_usuarios
FROM tbl_usuarios
");

$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

if($registro["n_usuarios"]>0){
  $_SESSION['id']=$registro["id"];
  $_SESSION['usuario']=$registro["usuario"];
  $_SESSION['idcondominio']=$registro["idcondominio"]; 
  $_SESSION['online']=$registro["online"];
  $_SESSION['mes']=$registro["mes"];
  $_SESSION['anio']=$registro["anio"]; 
  header("Location:index.php");
}else{
  $mensaje="Error: no se aplican las variables de seccion";
}

    header("Location:index.php");
};
?>  
<br> 

<div class="containerr">
  <div class="left">
    <?php include('left_panel.php')?>
  </div>
  <div class="center">    
  <div class="card">
        <div class="card-header">
            Cambiar Administracion
        </div>
        <div class="card-body"><h5>
          <form method="POST" style="display: block;">
            <label for="mes">Seleccione un mes:</label>
            <select name="mes" id="mes">
              <option value="Enero">Enero</option>
              <option value="Febrero">Febrero</option>
              <option value="Marzo">Marzo</option>
              <option value="Abril">Abril</option>
              <option value="Mayo">Mayo</option>
              <option value="Junio">Junio</option>
              <option value="Julio">Julio</option>
              <option value="Agosto">Agosto</option>
              <option value="Septiembre">Septiembre</option>
              <option value="Octubre">Octubre</option>
              <option value="Noviembre">Noviembre</option>
              <option value="Diciembre">Diciembre</option>
            </select>

            <label for="anio">Seleccione un año:</label>
            <select name="anio" id="anio">
              <?php
                $anioActual = date("Y");
                $rangoAnios = 5;
                $anioInicial = $anioActual - $rangoAnios;
                $anioFinal = $anioActual + $rangoAnios;
                
                for ($i = $anioInicial; $i <= $anioFinal; $i++) {
                  echo "<option value='$i'>$i</option>";
                }
              ?>
            </select>

            <button type="sumit" class="btn btn-success">Actualizar</button>
            <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>


          </form>

          </div>
      </div></h5>
        <div class="card-footer text-muted">
        </div>
    </div>    






    <div class="right">
      <?php include('right_panel.php')?>
    </div>
  </div>

<br>



<?php include("templates/footer.php"); ?>