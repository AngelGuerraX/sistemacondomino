 <?php include("../../templates/header.php"); ?>
 <?php

   include("../../bd.php");

   if (isset($_GET['txID'])) {

      $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

      $sentencia = $conexion->prepare("DELETE FROM tbl_gastos WHERE id=:id");
      $sentencia->bindParam(":id", $txtID);
      $sentencia->execute();
   }


   $aniio = $_SESSION['anio'];
   $mes = $_SESSION['mes'];
   $idcondominio = $_SESSION['idcondominio'];

   $tipo_gasto = "Nomina_Empleados";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id_condominio and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_Nomina = $sentencia->fetchAll((PDO::FETCH_ASSOC));


   $tipo_gasto = "Servicios_Basicos";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_Servicios = $sentencia->fetchAll((PDO::FETCH_ASSOC));


   $tipo_gasto = "Gastos_Menores_Material_Gastable";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_Material = $sentencia->fetchAll((PDO::FETCH_ASSOC));


   $tipo_gasto = "lmprevistos";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_lmprevistos = $sentencia->fetchAll((PDO::FETCH_ASSOC));


   $tipo_gasto = "Cargos_Bancarios";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_Cargos = $sentencia->fetchAll((PDO::FETCH_ASSOC));


   $tipo_gasto = "Servicios_lgualados";
   $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_gasto_lgualados = $sentencia->fetchAll((PDO::FETCH_ASSOC));

   ?>

 <br>
 <div class="card">
    <div class="card-header text-center bg-dark text-white">
       <h2> GASTOS </h2>
    </div>
    <div class="card-body">

       <a class="btn btn-primary" href="<?php echo $url_base ?>secciones/gastos/crear_nomina.php" role="button">NUEVO GASTO</a>
       <br><br>
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Nomina Empleados</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_Nomina as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $montoshow = number_format(floatval($registro['monto']), 2, '.', ',');
                                             echo $montoshow; ?></td>
                            <td> <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a>
                            </td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
          </div>
       </div>



       <br />
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Servicios Basicos</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_Servicios as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $precio = $registro['monto'];
                                             $precio_formateado = number_format($precio, 2, '.', ',');
                                             echo $precio_formateado ?></td>
                            <td> <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a>
                            </td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
          </div>
       </div>



       <br />
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Gastos Menores, Material Gastable</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_Material as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $precio = $registro['monto'];
                                             $precio_formateado = number_format($precio, 2, '.', ',');
                                             echo $precio_formateado ?></td>
                            <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a></td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
          </div>
       </div>


       <br />
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Imprevistos</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_lmprevistos as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $precio = $registro['monto'];
                                             $precio_formateado = number_format($precio, 2, '.', ',');
                                             echo $precio_formateado ?></td>
                            <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a></td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
          </div>
       </div>


       <br />
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Cargos Bancarios</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_Cargos as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $precio = $registro['monto'];
                                             $precio_formateado = number_format($precio, 2, '.', ',');
                                             echo $precio_formateado ?></td>
                            <td> <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a>
                            </td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
          </div>
       </div>


       <br />
       <div class="card">
          <div class="card-header bg-dark text-white">
             <h4>Servicios Igualados</h4>
          </div>
          <div class="card-body">
             <div class="table-responsive-sm">
                <table class="table">
                   <thead>
                      <tr>
                         <th scope="col">Detalles</th>
                         <th scope="col">Monto</th>
                         <th scope="col">Acciones:</th>
                      </tr>
                   </thead>
                   <tbody>
                      <?php foreach ($lista_gasto_lgualados as $registro) { ?>

                         <tr class="">
                            <td scope="row"><?php echo $registro['detalles'] ?></td>
                            <td scope="row"><?php $precio = $registro['monto'];
                                             $precio_formateado = number_format($precio, 2, '.', ',');
                                             echo $precio_formateado ?></td>
                            <td> <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a>
                            </td>
                         </tr>
                      <?php } ?>
                   </tbody>
                </table>
             </div>
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