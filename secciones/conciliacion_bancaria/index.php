
<?php include("../../templates/header.php"); ?> 
<?php 
include("../../bd.php");

if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
   $sentencia=$conexion->prepare("DELETE FROM tbl_cheques WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();
}

$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque=$sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque2=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='nulo'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_chequenulo=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque3=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

$sumamas=$registro["montosum"];

$sentencia=$conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

$sumamenos=$registro["montosum"];


$sentencia=$conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);


if($_POST){
      
   $aniio=$_SESSION['anio'];
   $mes=$_SESSION['mes'];
   $idcondominio=$_SESSION['idcondominio'];
   $sentencia=$conexion->prepare("SELECT *,count(*) as existentes
   FROM tbl_conciliacion_bancaria
   WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio
   ");
   
   
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $aniio);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);
   
   if($registro["existentes"]>0){
      $balancesbanco=(isset($_POST["balancesbanco"])?$_POST["balancesbanco"]:"");
      $mat=(isset($_POST["mat"])?$_POST["mat"]:"");
      $menost=(isset($_POST["menost"])?$_POST["menost"]:"");
      $balanceconciliado=(isset($_POST["balanceconciliado"])?$_POST["balanceconciliado"]:"");
      $balanceslibro=(isset($_POST["balanceslibro"])?$_POST["balanceslibro"]:"");
      $cargo_bancario=(isset($_POST["cargo_bancario"])?$_POST["cargo_bancario"]:"");

      $sentencia=$conexion->prepare("UPDATE tbl_conciliacion_bancaria SET balance_banco=:balancesbanco, mas_en_transito=:mat, menos_en_transito=:menost, balance_conciliado=:balanceconciliado, balance_libro=:balanceslibro, menos_cargos_bancarios=:cargo_bancario 
      WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio");

      $sentencia->bindParam(":balancesbanco", $balancesbanco);
      $sentencia->bindParam(":mat", $mat);
      $sentencia->bindParam(":menost", $menost);
      $sentencia->bindParam(":balanceconciliado", $balanceconciliado);
      $sentencia->bindParam(":balanceslibro", $balanceslibro);
      $sentencia->bindParam(":cargo_bancario", $cargo_bancario);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $aniio);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->execute();
      header("Location:index.php");
   }else{
      print_r($_POST);

      $balancesbanco=(isset($_POST["balancesbanco"])?$_POST["balancesbanco"]:"");
      $mat=(isset($_POST["mat"])?$_POST["mat"]:"");
      $menost=(isset($_POST["menost"])?$_POST["menost"]:"");
      $balanceconciliado=(isset($_POST["balanceconciliado"])?$_POST["balanceconciliado"]:"");
      $balanceslibro=(isset($_POST["balanceslibro"])?$_POST["balanceslibro"]:"");
      $cargo_bancario=(isset($_POST["cargo_bancario"])?$_POST["cargo_bancario"]:""); 

      $sentencia=$conexion->prepare("INSERT INTO tbl_conciliacion_bancaria (balance_banco,mas_en_transito,menos_en_transito,balance_conciliado,balance_libro,menos_cargos_bancarios,id_condominio,mes,anio)
      VALUES (:balancesbanco, :mat, :menost, :balanceconciliado, :balanceslibro, :cargo_bancario, :id_condominio, :mes, :anio)");

      $sentencia->bindParam(":balancesbanco", $balancesbanco);
      $sentencia->bindParam(":mat", $mat);
      $sentencia->bindParam(":menost", $menost);
      $sentencia->bindParam(":balanceconciliado", $balanceconciliado);
      $sentencia->bindParam(":balanceslibro", $balanceslibro);
      $sentencia->bindParam(":cargo_bancario", $cargo_bancario);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $aniio);
      $sentencia->execute();
      header("Location:index.php");
   }   
   $lista_tbl_usuarios=$sentencia->fetch((PDO::FETCH_LAZY));
   
   }


?>

  <br><br><br>
  <div class="containerr">
    <div class="left">
      <?php include('../../left_panel.php')?>
    </div>
      <div class="center"> 
            <div class="card-body">
         </div>
         <div class="card">
            <div class="card-body">
              <h1 class="display-8" id="nombre_condominio_online"><?php echo $_SESSION['online'];?>  -  <?php echo $_SESSION['mes'];?>  -  <?php echo $_SESSION['anio'];?></h1>
            </div>
          </div>    <br>
<div class="card">
    <div class="card-header"> 
      <h4>Conciliacion Bancaria  - <a name="" id="" class="btn btn-dark" href="<?php echo $ruta_base ?>secciones/conciliacion_bancaria/pdf_conciliacion.php" role="button">Generar PDF</a></h4> 
    </div>
    <div class="card-body">

      
   <div class="card">
        <div class="card-header"> 
            Conciliacion Bancaria
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                  <label for="balancesbanco" class="form-label">Balance Segun Banco:</label>
                  <input type="text" class="form-control" name="balancesbanco" id="balancesbanco" aria-describedby="helpId" placeholder="" value=  "">

                  <label for="cargo_bancario" class="form-label">Cargos Bancarios:</label>
                  <input type="text" class="form-control" name="cargo_bancario" id="cargo_bancario" aria-describedby="helpId" placeholder="" value="">

                  <label for="balanceconciliado" class="form-label">Balance Conciliado a <?php echo $_SESSION['mes'];?> del <?php echo $_SESSION['anio']?>:</label>
                  <input type="text" class="form-control" name="balanceconciliado" id="balanceconciliado" aria-describedby="helpId" placeholder="" value="" readonly>
                  
                  <label for="balanceslibro" class="form-label">Balance Segun Libro:</label>
                  <input type="text" class="form-control" name="balanceslibro" id="balanceslibro" aria-describedby="helpId" placeholder="" value="" readonly>
 
               </div>
                <div class="mb-3">
                  <label for="mat" class="form-label">Mas (+) Depositos en transito:</label>
                  <input type="text" class="form-control" name="mat" id="mat" aria-describedby="helpId" placeholder="" value="<?php $sumamasshow = number_format(floatval($sumamas), 2, '.', ','); echo $sumamasshow; ?>" readonly>

                  <label for="menost" class="form-label">Menos (-) Cheques en transito:</label>
                  <input type="text" class="form-control" name="menost" id="menost" aria-describedby="helpId" placeholder="" value="<?php $sumamenosshow = number_format(floatval($sumamenos), 2, '.', ','); echo $sumamenosshow;?>" readonly>

                  <label for="" class="form-label">En transito:</label>
                  <input type="text" class="form-control" name="transito" id="transito" aria-describedby="helpId" placeholder="" value="<?php $res = $sumamas - $sumamenos; $resshow = number_format(floatval($res), 2, '.', ','); echo $resshow;?>" readonly>
                </div>
                <div class="mb-3">
                </div>
                <button type="sumit" class="btn btn-success">Actualizar</button>
                <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>

        <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table">
          <thead>
             <tr>
                <th scope="col">Balance Segun Banco</th>
                <th scope="col">Cargos Bancarios</th>
                <th scope="col">Balance Conciliado</th>
                <th scope="col">Balance Segun Libro</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_cheque3 as $registro) { ?>

             <tr class="">
                <td scope="row"><?php $Balancesbshow = number_format(floatval($registro['balance_banco']), 2, '.', ','); echo $Balancesbshow;?></td>
                <td scope="row"><?php $menos_cargos_bancariosshow = number_format(floatval($registro['menos_cargos_bancarios']), 2, '.', ','); echo $menos_cargos_bancariosshow;?></td>
                <td scope="row"><?php $balance_conciliadoshow = number_format(floatval($registro['balance_conciliado']), 2, '.', ','); echo $balance_conciliadoshow;?></td>
                <td scope="row"><?php $balance_libroshow = number_format(floatval($registro['balance_libro']), 2, '.', ','); echo $balance_libroshow;?></td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
    </div>    
    </div>

        <div class="card-footer text-muted">
        </div>
    </div>


    </div>
 </div>
   <br>  
   
   <div class="card-body">      
   <div class="card">
   <a name="" id="" class="btn btn-dark" href="<?php echo $ruta_base ?>secciones/conciliacion_bancaria/crear.php" role="button">CREAR CHEQUE</a>
   </div>
   </div>
   <br>
   

   <div class="card">
    <div class="card-header"> 
      <h4>Mas (+) Depositos en Transito</h4>
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Detalles</th>
                <th scope="col">Monto</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_cheque as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['detalle'];?></td>
                <td scope="row"><?php $precio= $registro['monto']; $precio_formateado = number_format($precio, 2, '.', ','); echo $precio_formateado ?></td>
                <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a></td>
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
      <h4>Menos (-) Cheques en Transito</h4>
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Detalles</th>
                <th scope="col">Monto</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_cheque2 as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['detalle'];?></td>
                <td scope="row"><?php $precio= $registro['monto']; $precio_formateado = number_format($precio, 2, '.', ','); echo $precio_formateado ?></td>
                <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a></td>
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
      <h4>Cheques Nulos</h4>
    </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Detalles</th>
                <th scope="col">Monto</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_chequenulo as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['detalle'];?></td>
                <td scope="row"><?php $precio= $registro['monto']; $precio_formateado = number_format($precio, 2, '.', ','); echo $precio_formateado ?></td>
                <td><a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a></td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
    </div>    
    </div>
 </div>
   <br><br> 
 <br/>          
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