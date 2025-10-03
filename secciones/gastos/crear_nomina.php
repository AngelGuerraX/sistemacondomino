<?php include("../../templates/header.php");?>
<?php include("../../bd.php");

if($_POST){
   $tipo_gasto = isset($_POST["sel_mas_menos"]) ? $_POST["sel_mas_menos"] : "";
   $detalles = isset($_POST["detalles"]) ? $_POST["detalles"] : "";
   $monto = isset($_POST["monto"]) ? $_POST["monto"] : "";
   $mes = $_SESSION['mes'];
   $anio = $_SESSION['anio'];
   $id_condominio = $_SESSION['idcondominio'];

   $sentencia = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto, detalles, monto, mes, anio, id_condominio)
                                   VALUES (:tipo_gasto, :detalles, :monto, :mes, :anio, :id_condominio)");

   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":detalles", $detalles);
   $sentencia->bindParam(":monto", $monto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   $sentencia->bindParam(":id_condominio", $id_condominio);
   $sentencia->execute();

}
?>

<br><br><br>
<div class="containerr">
  <div class="left">
    <?php include('../../left_panel.php')?>
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
               <h2 class="form-label" for="add_mas_cheque"> <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>   - Añadir Cheque:</h2><br> 
          <form method="POST" style="display: block;">
            <h5 for="mes">Seleccione un mes:</h4>
            <select name="sel_mas_menos" id="mes">
              <option value="Nomina_Empleados">Nomina Empleados</option>
              <option value="Servicios_Basicos">Servicios Basicos</option>
              <option value="Gastos_Menores_Material_Gastable">Gastos Menores, Material Gastable</option>
              <option value="lmprevistos">lmprevistos</option>
              <option value="Cargos_Bancarios">Cargos Bancarios</option>
              <option value="Servicios_lgualados">Servicios lgualados</option>
            </select>
              
            <br><br> 
            
            <div class="mb-3">
               <h6 for="detalle" class="form-label">Detalles:</h6>
               <input type="text" class="form-control" name="detalles" id="detalles" aria-describedby="helpId" placeholder="Introducir Detalles">
            </div>
            <div class="mb-3">
               <h6 for="monto" class="form-label">Monto:</h6>
               <input type="text" class="form-control" name="monto" id="monto" aria-describedby="helpId" placeholder="Introducir Monto">
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