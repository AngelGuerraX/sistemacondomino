<?php include("../../templates/header.php");
include("../../bd.php");


if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

   $sentencia=$conexion->prepare("SELECT * FROM tbl_aptos WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();   
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);

   $id_apto=$registro["id_apto"];
   $enero=$registro["enero"];
   $enero_mora=$registro["enero_mora"];
   $apto=$registro["enero_gas"];
   $apto=$registro["febrero"];
   $apto=$registro["febrero_mora"];
   $apto=$registro["febrero_gas"];
   $apto=$registro["marzo"];
   $apto=$registro["marzo_mora"];
   $apto=$registro["marzo_gas"];
   $apto=$registro["abril"];
   $apto=$registro["abril_mora"];
   $apto=$registro["abril_gas"];
   $apto=$registro["mayo"];
   $apto=$registro["mayo_mora"];
   $apto=$registro["mayo_gas"];
   $apto=$registro["junio"];
   $apto=$registro["junio_mora"];
   $apto=$registro["junio_gas"];
   $apto=$registro["julio"];
   $apto=$registro["julio_mora"];
   $apto=$registro["julio_gas"];
   $apto=$registro["agosto"];
   $apto=$registro["agosto_mora"];
   $apto=$registro["agosto_gas"];
   $apto=$registro["septiembre"];
   $apto=$registro["septiembre_mora"];
   $apto=$registro["septiembre_gas"];
   $apto=$registro["octubre"];
   $apto=$registro["octubre_mora"];
   $apto=$registro["octubre_gas"];
   $apto=$registro["noviembre"];
   $noviembre_mora=$registro["noviembre_mora"];
   $apto=$registro["noviembre_gas"];
   $apto=$registro["diciembre"];
   $apto=$registro["diciembre_mora"];
   $apto=$registro["diciembre_gas"];
   $condominos=$registro["ano"];  
}

if($_POST){

    $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
    //recoleccion de datos
    $id_apto=(isset($_POST["id_apto"])?$_POST["id_apto"]:"");
    $enero=(isset($_POST["enero"])?$_POST["enero"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");
    $apto=(isset($_POST["apto"])?$_POST["apto"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_aptos SET 
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, 
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero,
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero,
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero,
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero,
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero, enero=:enero,
      enero=:enero, enero=:enero, enero=:enero, enero=:enero, 
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);
    $sentencia->bindParam(":apto", $apto);


    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:index.php");
};
?>

   
  <br>

<div class="containerr">
  <div class="left">
    <?php include('../../left_panel.php')?>
  </div>
  <div class="center">
     
      <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>   <br><br>

    <div class="card" style="font-size: 22px;">    
        <div class="card-header">
          <h4>DATOS DEL APARTAMENTO</h4> 
        </div>
        <div class="card">
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data" class="formulario_pagos">
            <div class="mb-3">
                  <label for="nombre" class="form-label">Apto:</label>
                  <input type="text"
                    class="form-control" name="apto" id="apto" aria-describedby="helpId" placeholder="Escriba el no. de apto" value="<?php echo $apto;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="ubicacion" class="form-label">Condominos:</label>
                  <input type="text"
                    class="form-control" name="condominos" id="condominos" aria-describedby="helpId" placeholder="Escriba los condominos" value="<?php echo $condominos;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="cuenta_bancaria" class="form-label">Mantenimiento</label>
                  <input type="text"
                    class="form-control" name="mantenimiento" id="mantenimiento" aria-describedby="helpId" placeholder="Escriba el mantenimiento" value="<?php echo $mantenimiento;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="telefono" class="form-label">Gas:</label>
                  <input type="text"
                    class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas" value="<?php echo $gas;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="mora" class="form-label">Correo:</label>
                  <input type="email"
                    class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Escriba la correo" value="<?php echo $correo;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="gas" class="form-label">Telefono:</label>
                  <input type="text"
                    class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="no_aptos" class="form-label">Metodo de pago:</label>
                  <input type="text"
                    class="form-control" name="forma_de_pago" id="forma_de_pago" aria-describedby="helpId" placeholder="Escriba la forma de pago" value="<?php echo $forma_de_pago;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="saldo_actual" class="form-label">Ultimo Pago:</label>
                  <input type="text"
                    class="form-control" name="fecha_ultimo_pago" id="fecha_ultimo_pago" aria-describedby="helpId" placeholder="" value="<?php echo $fecha_ultimo_pago;?>" readonly>
                </div>
                <div class="mb-3">
                
                <a class="btn btn-success" href="<?php echo $ruta_base ?>secciones/aptos/editar.php?txID=<?php echo $registro['id']?>" role="button">Editar</a>
            
                </div>

            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>
   </div>   

    <br>
    <div class="card">
      <div class="card-header">
        <h4>MESES DEBIDOS</h4>    
      <a name="" id="" class="btn btn-dark" href="<?php echo $ruta_base ?>secciones/pagos/crear_anios.php?txID=<?php echo $registro['id']?>" role="button">Añadir año</a> 
      </div>
    <div class="card-body">
    <div class="table-responsive-sm">
       <table class="table" id="tabla_id">
          <thead>
             <tr>
                <th scope="col">ID</th>
                <th scope="col">Apto</th>
                <th scope="col">Condominos</th>
                <th scope="col">Mantenimiento</th>
                <th scope="col">Gas</th>
                <th scope="col">Telefono</th>
                <th scope="col">Correo</th>
                <th scope="col">Ultimo pago</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
                <th scope="col">Acciones:</th>
             </tr>
          </thead>
          <tbody>
       <?php foreach ($lista_tbl_cargar_camion as $registro) { ?>

             <tr class="">
                <td scope="row"><?php echo $registro['id']?></td>
                <td scope="row"><?php echo $registro['apto']?></td>
                <td scope="row"><?php echo $registro['condominos']?></td>
                <td scope="row"><?php echo $registro['mantenimiento']?></td>
                <td scope="row"><?php echo $registro['gas']?></td>
                <td scope="row"><?php echo $registro['telefono']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['correo']?></td>
                <td scope="row"><?php echo $registro['fecha_ultimo_pago']?></td>
                <td> <a class="btn btn-success" href="editar.php?txID=<?php echo $registro['id']?>" role="button">Establecer pago</a>
                  | <a class="btn btn-danger" href="javascript:borrar(<?php echo $registro['id']?>);" role="button">Eliminar</a>
          </td>
             </tr>
       <?php } ?>
          </tbody>
       </table>
    </div>
    
    </div>
    </div> </div>


    <div class="right">
    <?php include('../../right_panel.php')?>
    </div>
  </div>

<br>



<?php include("../../templates/footer.php"); ?>