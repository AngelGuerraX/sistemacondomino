<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");
$mes = $_SESSION['mes'];
$anio = $_SESSION['anio'];
$idcondominio = $_SESSION['idcondominio'];
if (isset($_GET['txID'])) {
   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

   $sentencia = $conexion->prepare("DELETE FROM tbl_aptos WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}



// Obtener los registros de la tabla tbl_aptos filtrados por id_condominio
$query = "SELECT * FROM tbl_aptos WHERE id_condominio = :id_condominio";
$statement = $conexion->prepare($query);
$statement->bindParam(':id_condominio', $idcondominio);
$statement->execute();
$rows = $statement->fetchAll();


if (count($rows) > 0) {


   $sentencia = $conexion->prepare("SELECT A.id, A.apto, A.condominos, A.mantenimiento, A.fecha_ultimo_pago, T.id_apto, T.estado, T.mes, T.anio
   FROM tbl_aptos A
   LEFT JOIN tbl_tickets T ON A.id = T.id_apto AND T.mes = :mes AND T.anio = :anio
   WHERE A.id_condominio = :idcondominio;
   ");
   $sentencia->bindParam(":idcondominio", $idcondominio);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   $sentencia->execute();
   $Lista_tbl_aptos = $sentencia->fetchAll((PDO::FETCH_ASSOC));
} else {
   echo "Error al insertar el Registro: " . $sentencia->errorInfo()[2];
}

?>
<br>


<div class="center">
   <div class="card">
      <div class="card-header bg-dark text-white text-center">
         <h2> INGRESOS </h2>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table" id="tabla_id">
               <thead>
                  <tr>
                     <th scope="col">Apto</th>
                     <th scope="col">Condominos</th>
                     <th scope="col">Ultimo pago</th>
                     <th scope="col">Estado</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($Lista_tbl_aptos as $registro) { ?>

                     <tr class="">
                        <td scope="row"><a class="btn btn-success" href="editar.php?txID=<?php echo $registro['id'] ?>" role="button"><?php echo $registro['apto'] ?></a></td>
                        <td scope="row"><?php echo $registro['condominos'] ?></td>
                        <td scope="row"><?php echo $registro['fecha_ultimo_pago'] ?></td>
                        <td>
                           <?php if ($registro['estado'] === 'Pago') { ?>
                           <?php } else { ?>
                              <a class="btn btn-danger" href="editar.php?txID=<?php echo $registro['id'] ?>" role="button">Pendiente</a>
                           <?php } ?>
                        </td>
                     </tr>
                  <?php } ?>
               </tbody>
            </table>
         </div>
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