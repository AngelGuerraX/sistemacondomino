<?php

include("../../bd.php");


if (isset($_GET['txID'])) {

   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

   $sentencia = $conexion->prepare("DELETE FROM tbl_condominios WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$sentencia = $conexion->prepare("SELECT * FROM tbl_condominios");
$sentencia->execute();
$lista_tbl_cargar_camion = $sentencia->fetchAll((PDO::FETCH_ASSOC));

?>

<?php include("../../templates/header.php"); ?>

<br>


<div class="card">
   <div class="card-header">
      <center>
         <h2>CONDOMINIOS</h2>
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
                  <th scope="col">ID</th>
                  <th scope="col">Nombre</th>
                  <th scope="col">Ubicación</th>
                  <th scope="col">Cuenta Bancaria</th>
                  <th scope="col">Telefono</th>
                  <th scope="col">Mora</th>
                  <th scope="col">Gas</th>
                  <th scope="col">Cuota</th>
                  <th scope="col">Saldo Actual</th>
                  <th scope="col">Acciones:</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($lista_tbl_cargar_camion as $registro) { ?>

                  <tr class="">
                     <td scope="row"><?php echo $registro['id'] ?></td>
                     <td scope="row"><?php echo $registro['nombre'] ?></td>
                     <td scope="row"><?php echo $registro['ubicacion'] ?></td>
                     <td scope="row"><?php echo $registro['cuenta_bancaria'] ?></td>
                     <td scope="row"><?php echo $registro['telefono'] ?></td>
                     <td scope="row"><?php echo $registro['mora'] ?></td>
                     <td scope="row"><?php echo $registro['gas'] ?></td>
                     <td scope="row"><?php echo $registro['cuota'] ?></td>
                     <td scope="row"><?php $precio = $registro['saldo_actual'];
                                       $precio_formateado = number_format($precio, 2, '.', ',');
                                       echo $precio_formateado ?></td>
                     <td>
                        <a class="btn btn-dark" href="administrar.php?txID=<?php echo $registro['id'] ?>" role="button">Administrar</a>
                        | <a class="btn btn-dark" href="editar.php?txID=<?php echo $registro['id'] ?>" role="button">Editar</a>
                     </td>
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