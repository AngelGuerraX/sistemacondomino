
<?php include("../../templates/header.php"); ?> 
<?php 
 
include("../../bd.php");


if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("DELETE FROM tbl_nomina WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

$sentencia=$conexion->prepare("SELECT * FROM tbl_empleados WHERE idcondominio=:idcondominio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_tbl_cargar_camion=$sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia=$conexion->prepare("SELECT * FROM tbl_nomina WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_nomina=$sentencia->fetchAll((PDO::FETCH_ASSOC));
?>
  <br>
  <div class="containerr">
    <div class="left">
      <?php include('../../left_panel.php')?>
    </div>
      <div class="center">
 
<div class="card">
    <div class="card-header">         
      <h6 style="color: black; font-size: 25px;"> Lista de Empleados </a> </h6>      
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table" id="">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Nombres</th>
                <th scope="col">Apellidos</th>
                <th scope="col">Identificacion</th>
                <th scope="col">Telefono</th>
                <th scope="col">Cargo</th>
                <th scope="col">Horario</th>
                <th scope="col">Ingreso</th>
                <th scope="col">Salario</th>
                <th scope="col">Pago</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_tbl_cargar_camion as $registro) { ?>
             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['nombres']?></td>
                <td scope="row"><?php echo $registro['apellidos']?></td>
                <td scope="row"><?php echo $registro['cedula_pasaporte']?></td>
                <td scope="row"><?php echo $registro['telefono']?></td>
                <td scope="row"><?php echo $registro['cargo']?></td>
                <td scope="row"><?php echo $registro['horario']?></td>
                <td scope="row"><?php echo $registro['fecha_ingreso']?></td>
                <td scope="row"><?php $salarioshow = number_format(floatval($registro['salario']), 2, '.', ','); echo $salarioshow;?></td>
                <td scope="row"><?php echo $registro['forma_de_pago']?></td> 
                <td>
                  <a class="btn btn-dark" href="crear_nomina.php?txID=<?php echo $registro['id']?>" role="button">Nomina</a>
                </td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
    </div>    
    </div>
 </div>
 <br>
 <div class="card">
    <div class="card-header">
      <h6 style="color: black; font-size: 25px;"> Nomina </h6>
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table" id="">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">ID Empleado</th>
                <th scope="col">Dia Feriado</th>
                <th scope="col">Dia Extra</th>
                <th scope="col">Desc TSS</th>
                <th scope="col">Dia</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_nomina as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['idempleado']?></td>
                <td scope="row"><?php $dfss = number_format(floatval($registro['d_f']), 2, '.', ','); echo $dfss;?></td>
                <td scope="row"><?php $dess = number_format(floatval($registro['d_e']), 2, '.', ','); echo $dess;?></td>
                <td scope="row"><?php echo $registro['dia']?></td>
                <td scope="row"><?php echo $registro['idcondominio']?></td>
                <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a>
               </td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
    </div>    
    </div>
    </div>
      </div>
    <div class="right">
    <?php include('../../right_panel.php')?>
    </div>
  </div>

<br>


<script>

function borrar(id){


   Swal.fire({
   title: '¿Quieres borrar el registro?',
   showCancelButton: true,
   confirmButtonText: 'Si, borrar'
   }).then((result) => {
   /* Read more about isConfirmed, isDenied below */
   if (result.isConfirmed) {
      window.location="index.php?txID="+id;
   }
   })

   //
}

</script>




<?php include("../../templates/footer.php"); ?>