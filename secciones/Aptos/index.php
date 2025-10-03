<?php include("../../templates/header.php"); ?>
<?php

include("../../bd.php");


$idcondominio = $_SESSION['idcondominio'];
if (isset($_GET['txID'])) {

   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

   $sentencia = $conexion->prepare("DELETE FROM tbl_aptos WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$sentencia = $conexion->prepare("SELECT * FROM tbl_aptos where id_condominio=:idcondominio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_tbl_cargar_camion = $sentencia->fetchAll((PDO::FETCH_ASSOC));

?>


<br>


<div class="card">
   <div class="card-header">
      <center>
         <h2>APARTAMENTOS</h2>
      </center>
   </div>
   <div class="card-body">
      <a name="" id="" class="btn btn-dark"
         href="crear.php" role="button">
         Nuevo
      </a>
      <br>
      <br>
      <div class="table-responsive">
         <table class="table" id="tabla_id">
            <thead>
               <tr>
                  <th scope="col">Ver</th>
                  <th scope="col">Apto</th>
                  <th scope="col">Condominos</th>
                  <th scope="col">Mant</th>
                  <th scope="col">Gas</th>
                  <th scope="col">Telefono</th>
                  <th scope="col">Correo</th>
                  <th scope="col">Ultimo pago</th>
                  <th scope="col">Acciones:</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($lista_tbl_cargar_camion as $registro) { ?>

                  <tr class="">
                     <td scope="row"><a class="btn btn-primary" href="editar.php?txID=<?php echo $registro['id'] ?>" role="button">Ver</a></td>
                     <td scope="row"><?php echo $registro['apto'] ?></td>
                     <td scope="row"><?php echo $registro['condominos'] ?></td>
                     <td scope="row"><?php $mantenimientoshow = number_format(floatval($registro['mantenimiento']), 2, '.', ',');
                                       echo $mantenimientoshow; ?></td>
                     <td scope="row"><?php echo $registro['gas'] ?></td>
                     <td scope="row"><?php echo $registro['telefono'] ?></td>
                     <td scope="row"><?php echo $registro['correo'] ?></td>
                     <td scope="row"><?php echo $registro['fecha_ultimo_pago'] ?></td>
                     <td> <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a> </td>
                  </tr>
               <?php } ?>
            </tbody>
         </table>
      </div>

   </div>
</div>

<br>


<script>
   function borrar(id) {


      Swal.fire({
         title: '¿Quieres borrar el registro?',
         showCancelButton: true,
         confirmButtonText: 'Si, borrar'
      }).then((result) => {
         /* Read more about isConfirmed, isDenied below */
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id;
         }
      })

      //
   }
</script>




<?php include("../../templates/footer.php"); ?>