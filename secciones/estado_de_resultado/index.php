
<?php include("../../templates/header.php"); ?> 
<?php 
 
include("../../bd.php");

if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("DELETE FROM tbl_estado_resultado WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio']; 
 
$sentencia=$conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id_condominio=:id");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->execute();
$lista_tbl_estado=$sentencia->fetchAll((PDO::FETCH_ASSOC));

//BUSCA MES ANTERIOR
$mes_actual = $_SESSION['mes'];
$aniio = $_SESSION['anio'];
$meses = array(
   "enero", "febrero", "marzo", "abril", "mayo", "junio",
   "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
);
$indice_mes_actual = array_search($mes_actual, $meses);
$indice_mes_anterior = ($indice_mes_actual - 1 + 12) % 12;
$anio_anterior = $aniio;
if ($indice_mes_anterior === 11) {
   $anio_anterior--;
}
$mes_anterior = $meses[$indice_mes_anterior];

$sentencia = $conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id_condominio=:id and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":mes", $mes_anterior);
$sentencia->bindParam(":anio", $anio_anterior);
$sentencia->execute();
$lista_tbl_estado2 = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    
$mes_actual=$_SESSION['mes'];
$gas_actual=$_SESSION['mes'] . "_gas";
$mora_actual=$_SESSION['mes'] . "_mora";
$c_actual=$_SESSION['mes'] . "_c";

$sentencia = $conexion->prepare("SELECT a.id, a.apto, a.condominos, fecha_ultimo_pago, a.gas, ". $mes_actual ." AS `mes_actual`, ". $gas_actual ." AS `gas_actual`, ". $mora_actual ." AS `mora_actual`, ". $c_actual ." AS `c_actual` FROM tbl_aptos a 
INNER JOIN tbl_meses_debidos m ON m.id_apto = a.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE m.ano = :ano and a.id_condominio = :id_condominio ORDER BY a.apto ASC");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":ano", $_SESSION['anio']);
$sentencia->bindParam(":id_condominio", $_SESSION['idcondominio']);
$sentencia->execute();

$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);
 
$totalg = 0; // Variable para almacenar el total
foreach ($rows as $row) {
    $subtotal = floatval($row['mes_actual']) + floatval($row['gas_actual']) + floatval($row['mora_actual']) + floatval($row['c_actual']);
    $totalg += $subtotal; // Sumar el subtotal al total
    // Resto del código para mostrar los datos de cada fila
}



//////////////////////////////////////////
////////   GASTOS DEL MES ACTUAL   ///////
//////////////////////////////////////////
$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio']; 
 
$tipo_gasto="Nomina_Empleados";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id_condominio and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id_condominio", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Nomina=$sentencia->fetchAll((PDO::FETCH_ASSOC));

 
$tipo_gasto="Servicios_Basicos";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Servicios=$sentencia->fetchAll((PDO::FETCH_ASSOC));

 
$tipo_gasto="Gastos_Menores_Material_Gastable";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Material=$sentencia->fetchAll((PDO::FETCH_ASSOC));

 
$tipo_gasto="lmprevistos";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_lmprevistos=$sentencia->fetchAll((PDO::FETCH_ASSOC));

 
$tipo_gasto="Cargos_Bancarios";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Cargos=$sentencia->fetchAll((PDO::FETCH_ASSOC));

 
$tipo_gasto="Servicios_lgualados";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_lgualados=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
?>

<?php
$totalNomina = 0;
$totalServicios = 0;
$totalMaterial = 0;
$totalImprevistos = 0;
$totalCargos = 0;
$totalIgualados = 0;

foreach ($lista_gasto_Nomina as $row) {
    $subtotaln1 = floatval($row['monto']);
    $totalNomina += $subtotaln1;
}

foreach ($lista_gasto_Servicios as $row) {
    $subtotaln2 = floatval($row['monto']);
    $totalServicios += $subtotaln2;
}

foreach ($lista_gasto_Material as $row) {
    $subtotaln3 = floatval($row['monto']);
    $totalMaterial += $subtotaln3;
}

foreach ($lista_gasto_lmprevistos as $row) {
    $subtotaln4 = floatval($row['monto']);
    $totalImprevistos += $subtotaln4;
}

foreach ($lista_gasto_Cargos as $row) {
    $subtotaln6 = floatval($row['monto']);
    $totalCargos += $subtotaln6;
}

foreach ($lista_gasto_lgualados as $row) {
    $subtotaln8 = floatval($row['monto']);
    $totalIgualados += $subtotaln8;
}
//SUMA DE LOS GASTOS
$totalGastos = $totalNomina + $totalServicios + $totalMaterial + $totalImprevistos + $totalCargos + $totalIgualados;


foreach ($lista_tbl_estado2 as $row) {
   $res_anterior = $row['resultado_actual'];

   }
   if (isset($row['resultado_actual'])) {
      
   $res_anterior = $row['resultado_actual'];
    } else {
     
   $res_anterior = 0;
    }
    

//CALCULO DE INGRESOS, GASTOS, CIERRE, CUADRE ANTERIOR, CUADRE ACRUAL
$cierre = $totalg - $totalGastos; 
$res_actual = $cierre + $res_anterior;

$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

if($_POST){
      
   $aniio=$_SESSION['anio'];
   $mes=$_SESSION['mes'];
   $idcondominio=$_SESSION['idcondominio'];
   $sentencia=$conexion->prepare("SELECT *,count(*) as existentes
   FROM tbl_estado_resultado
   WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio
   ");
   
   
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);
   
   if($registro["existentes"]>0){
      $ingresos=$totalg;
      $gastos=$totalGastos;
      $cierre_mes=$cierre;
      $resultado_anterior=$res_anterior;
      $resultado_actual=$res_actual;

      $sentencia=$conexion->prepare("UPDATE tbl_estado_resultado SET ingresos=:ingresos, gastos=:gastos, cierre_mes=:cierre_mes, resultado_anterior=:resultado_anterior, resultado_actual=:resultado_actual
      WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio");

      $sentencia->bindParam(":ingresos", $ingresos);
      $sentencia->bindParam(":gastos", $gastos);
      $sentencia->bindParam(":cierre_mes", $cierre_mes);
      $sentencia->bindParam(":resultado_anterior", $resultado_anterior);
      $sentencia->bindParam(":resultado_actual", $resultado_actual);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $aniio);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->execute();
      header("Location:index.php");
   }else{
      print_r($_POST);

      $ingresos=$totalg;
      $gastos=$totalGastos;
      $cierre_mes=$cierre;
      $resultado_anterior=$res_anterior;
      $resultado_actual=$res_actual;

      $sentencia=$conexion->prepare("INSERT INTO tbl_estado_resultado (ingresos,gastos,cierre_mes,resultado_anterior,resultado_actual,id_condominio,mes,anio)
      VALUES (:ingresos, :gastos, :cierre_mes, :resultado_anterior, :resultado_actual, :id_condominio, :mes, :anio)");

      $sentencia->bindParam(":ingresos", $ingresos);
      $sentencia->bindParam(":gastos", $gastos);
      $sentencia->bindParam(":cierre_mes", $cierre_mes);
      $sentencia->bindParam(":resultado_anterior", $resultado_anterior);
      $sentencia->bindParam(":resultado_actual", $resultado_actual);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $aniio);
      $sentencia->execute();
      header("Location:index.php");
   }      
   }

   $sentencia=$conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
   $sentencia->bindParam(":idcondominio", $idcondominio);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->execute();
   $lista_cheque3=$sentencia->fetchAll((PDO::FETCH_ASSOC));


   

?>

  <br>

  <div class="containerr">
    <div class="left">
      <?php include('../../left_panel.php')?>
    </div>
      <div class="center"> 

      <div class="card">
            <div class="card-body">
              <h1 class="display-8" id="nombre_condominio_online"> | <?php echo $_SESSION['online'];?>  -  <?php echo $_SESSION['mes'];?>  -  <?php echo $_SESSION['anio'];?> || <a class="btn btn-dark" target="_blank" href="pdf_estado.php" role="button">Mostrar PDF</a></h1>
               
            </div>
          </div>    
          <br>

          
         <div class="card">
          </div>    
          <br>

      
   <div class="card">
        <div class="card-header"> 
           <h3>Estado actual</h3> 
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
               <h5>
                  <label for="ingresos" class="form-label">Ingresos:</label>
                  <input type="text" class="form-control" name="ingresos" id="ingresos" aria-describedby="helpId" placeholder="" readonly value= "<?php $total_ingresos= number_format(floatval($totalg), 2, '.', ','); echo $total_ingresos;?>">
                  
                  <label for="gastos" class="form-label">Gastos:</label>
                  <input type="text" class="form-control" name="gastos" id="gastos" aria-describedby="helpId" placeholder="" readonly value="<?php $totalGastosshow= number_format(floatval($totalGastos), 2, '.', ','); echo $totalGastosshow;?>">
                  <br>
                  </h5>
                  <button type="sumit" class="btn btn-success">GUARDAR ESTADO DE RESULTADO</button>
            </div>
               <div class="mb-3">
               <h5>
                  <label for="cierre_mes" class="form-label">Cierre del Mes:</label>
                  <input type="text" class="form-control" name="cierre_mes" id="cierre_mes" aria-describedby="helpId" placeholder="" readonly value= "<?php $cierreshow = number_format(floatval($cierre), 2, '.', ','); echo $cierreshow; ?>">

                  <label for="resultado_anterior" class="form-label">Resultado Anterior:</label>
                  <input type="text" class="form-control" name="resultado_anterior" id="resultado_anterior" aria-describedby="helpId" placeholder="" readonly value="<?php $res_anteriorshow = number_format(floatval($res_anterior), 2, '.', ','); echo $res_anteriorshow;?>">

                  <label for="resultado_actual" class="form-label">Resultado Actual:</label>
                  <input type="text" class="form-control" name="resultado_actual" id="resultado_actual" aria-describedby="helpId" placeholder="" readonly value="<?php $res_actualshow = number_format(floatval($res_actual), 2, '.', ','); echo $res_actualshow;?>">
                  </h5>
               </div>
               <div class="mb-3">
               </div>
               <div class="mb-3">
               </div>
               <div class="mb-3">
               </div>
               <div class="mb-3">
               </div>
               <div class="mb-3">
               </div>

            </form>
        </div>


        <div class="card-footer text-muted">
        </div>
    </div>

    <br>

 <div class="card">
    <div class="card-header"> 
           <h3>Estados anteriores</h3> 
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Mes</th>
                <th scope="col">Ingreso</th>
                <th scope="col">Gastos</th>
                <th scope="col">Cierre</th>
                <th scope="col">Cuadre Anterior</th>
                <th scope="col">Cuadre Actual</th>
                <th scope="col">Año</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_tbl_estado as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['mes']?></td>
                <td scope="row"><?php echo number_format(floatval($registro['ingresos']), 2, '.', ',')?></td>
                <td scope="row"><?php echo number_format(floatval($registro['gastos']), 2, '.', ',')?></td>
                <td scope="row"><?php echo number_format(floatval($registro['cierre_mes']), 2, '.', ',')?></td>
                <td scope="row"><?php echo number_format(floatval($registro['resultado_anterior']), 2, '.', ',')?></td>
                <td scope="row"><?php echo number_format(floatval($registro['resultado_actual']), 2, '.', ',')?></td>
                <td scope="row"><?php echo $registro['anio']?></td>
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