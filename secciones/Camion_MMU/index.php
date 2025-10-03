<?php 
 
include("../../bd.php");


if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("DELETE FROM tbl_cargar_camion WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$sentencia=$conexion->prepare("SELECT * FROM tbl_cargar_camion");
$sentencia->execute();
$lista_tbl_cargar_camion=$sentencia->fetchAll((PDO::FETCH_ASSOC));

?>
<?php include("../../templates/header.php"); ?>
   <br/>
 
   <h3> Cargas del camion: </h3>
   <div class="card">
      <div class="card-header">         
      <a name="" id="" class="btn btn-primary"
          href="crear.php" role="button">
          Añadir Carga
        </a>
         
      <?php        
        if($_SESSION['puesto'] == 'administrador'){         
          include("btn_generar_reporte.php");
        }
      ?>


      </div>
      <div class="card-body">
      <div class="table-responsive-sm">
         <table class="table" id="tabla_id">
            <thead>
               <tr>
                  <th scope="col">ID</th>
                  <th scope="col">fecha</th>
                  <th scope="col">Operador</th>
                  <th scope="col">Camion MMU</th>
                  <th scope="col">Despacho</th>
                  <th scope="col">Restante</th>
                  <th scope="col">Usuario</th>
                  <th scope="col">Acciones</th>
               </tr>
            </thead>
            <tbody>
         <?php foreach ($lista_tbl_cargar_camion as $registro) { ?>

               <tr class="">
                  <td scope="row"><?php echo $registro['id']?></td>
                  <td scope="row"><?php echo $registro['fecha']?></td>
                  <td scope="row"><?php echo $registro['operador']?></td>
                  <td scope="row"><?php echo $registro['camion_mmu']?></td>
                  <td scope="row"><?php echo $registro['despacho_mt']?></td>
                  <td scope="row"><?php echo $registro['restante_en_silo']?></td>
                  <td scope="row"><?php echo $registro['usuario']?></td>
                  <td> <a class="btn btn-primary" href="editar.php?txID=<?php echo $registro['id']?>" role="button">ver</a>
                    | <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a>
            </td>
               </tr>
         <?php } ?>
            </tbody>
         </table>
      </div>
      
      </div>
   </div>

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