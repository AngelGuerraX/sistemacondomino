
<?php

$idcondominio=$_SESSION['idcondominio'];

$sentencia=$conexion->prepare("SELECT * FROM tbl_aptos where id_condominio=:idcondominio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_tbl_cargar_camion=$sentencia->fetchAll((PDO::FETCH_ASSOC));

?>



    <div class="card-body" style="background-color: #fff; border-radius: 10px;">
    <div class="table-responsive-sm">
       <table class="table" id="tabla_id">
          <thead>
             <tr>
                <th scope="col">Apto</th>
                <th scope="col">Condominos</th>
                <th scope="col">Mantenimiento</th>
                <th scope="col">Ultimo pago</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_tbl_cargar_camion as $registro2) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro2['apto']?></td>
                <td scope="row"><?php echo $registro2['condominos']?></td>
                <td scope="row"><?php $mantenimientoshow = number_format(floatval($registro2['mantenimiento']), 2, '.', ','); echo $mantenimientoshow;?></td>
                <td scope="row"><?php echo $registro2['fecha_ultimo_pago']?></td>
                <td> <a class="btn btn-success" href="editar.php?txID=<?php echo $registro2['id']?>" role="button">Seleccionar Apto</a></td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
       </div></div>

