<?php include("../../templates/header.php");
include("../../bd.php");


if(isset($_GET['txID'])){

   $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
   $anioo= $_SESSION['anio'];
   $sentencia=$conexion->prepare("SELECT * FROM tbl_meses_debidos WHERE id_apto=:id and ano=:anio");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->bindParam(":anio", $anioo);
   $sentencia->execute();   
   $registro=$sentencia->fetch(PDO::FETCH_LAZY);

    
  if ($registro !== false) {
    $id_apto=$registro["id_apto"];
    $enero=$registro["enero"];
    $enero_mora=$registro["enero_mora"];
    $enero_gas=$registro["enero_gas"];
    $febrero=$registro["febrero"];
    $febrero_mora=$registro["febrero_mora"];
    $febrero_gas=$registro["febrero_gas"];
    $marzo=$registro["marzo"];
    $marzo_mora=$registro["marzo_mora"];
    $marzo_gas=$registro["marzo_gas"];
    $abril=$registro["abril"];
    $abril_mora=$registro["abril_mora"];
    $abril_gas=$registro["abril_gas"];
    $mayo=$registro["mayo"];
    $mayo_mora=$registro["mayo_mora"];
    $mayo_gas=$registro["mayo_gas"];
    $junio=$registro["junio"];
    $junio_mora=$registro["junio_mora"];
    $junio_gas=$registro["junio_gas"];
    $julio=$registro["julio"];
    $julio_mora=$registro["julio_mora"];
    $julio_gas=$registro["julio_gas"];
    $agosto=$registro["agosto"];
    $agosto_mora=$registro["agosto_mora"];
    $agosto_gas=$registro["agosto_gas"];
    $septiembre=$registro["septiembre"];
    $septiembre_mora=$registro["septiembre_mora"];
    $septiembre_gas=$registro["septiembre_gas"];
    $octubre=$registro["octubre"];
    $octubre_mora=$registro["octubre_mora"];
    $octubre_gas=$registro["octubre_gas"];
    $noviembre=$registro["noviembre"];
    $noviembre_mora=$registro["noviembre_mora"];
    $noviembre_gas=$registro["noviembre_gas"];
    $diciembre=$registro["diciembre"];
    $diciembre_mora=$registro["diciembre_mora"];
    $diciembre_gas=$registro["diciembre_gas"];
    $ano=$registro["ano"]; 
    } else {



    }
    
   
}

if($_POST){

    $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
    //recoleccion de datos
    $id_apto=(isset($_POST["id_apto"])?$_POST["id_apto"]:"");
    $enero=(isset($_POST["enero"])?$_POST["enero"]:"");
    $enero_mora=(isset($_POST["enero_mora"])?$_POST["enero_mora"]:"");
    $enero_gas=(isset($_POST["enero_gas"])?$_POST["enero_gas"]:"");
    $febrero=(isset($_POST["febrero"])?$_POST["febrero"]:"");
    $febrero_mora=(isset($_POST["febrero_mora"])?$_POST["febrero_mora"]:"");
    $febrero_gas=(isset($_POST["febrero_gas"])?$_POST["febrero_gas"]:"");
    $marzo=(isset($_POST["marzo"])?$_POST["marzo"]:"");
    $marzo_mora=(isset($_POST["marzo_mora"])?$_POST["marzo_mora"]:"");
    $marzo_gas=(isset($_POST["marzo_gas"])?$_POST["marzo_gas"]:"");
    $abril=(isset($_POST["abril"])?$_POST["abril"]:"");
    $abril_mora=(isset($_POST["abril_mora"])?$_POST["abril_mora"]:"");
    $abril_gas=(isset($_POST["abril_gas"])?$_POST["abril_gas"]:"");
    $mayo=(isset($_POST["mayo"])?$_POST["mayo"]:"");
    $mayo_mora=(isset($_POST["mayo_mora"])?$_POST["mayo_mora"]:"");
    $mayo_gas=(isset($_POST["mayo_gas"])?$_POST["mayo_gas"]:"");
    $junio=(isset($_POST["junio"])?$_POST["junio"]:"");
    $junio_mora=(isset($_POST["junio_mora"])?$_POST["junio_mora"]:"");
    $junio_gas=(isset($_POST["junio_gas"])?$_POST["junio_gas"]:"");
    $julio=(isset($_POST["julio"])?$_POST["julio"]:"");
    $julio_mora=(isset($_POST["julio_mora"])?$_POST["julio_mora"]:"");
    $julio_gas=(isset($_POST["julio_gas"])?$_POST["julio_gas"]:"");
    $agosto=(isset($_POST["agosto"])?$_POST["agosto"]:"");
    $agosto_mora=(isset($_POST["agosto_mora"])?$_POST["agosto_mora"]:"");
    $agosto_gas=(isset($_POST["agosto_gas"])?$_POST["agosto_gas"]:"");
    $septiembre=(isset($_POST["septiembre"])?$_POST["septiembre"]:"");
    $septiembre_mora=(isset($_POST["septiembre_mora"])?$_POST["septiembre_mora"]:"");
    $septiembre_gas=(isset($_POST["septiembre_gas"])?$_POST["septiembre_gas"]:"");
    $octubre=(isset($_POST["octubre"])?$_POST["octubre"]:"");
    $octubre_mora=(isset($_POST["octubre_mora"])?$_POST["octubre_mora"]:"");
    $octubre_gas=(isset($_POST["octubre_gas"])?$_POST["octubre_gas"]:"");
    $noviembre=(isset($_POST["noviembre"])?$_POST["noviembre"]:"");
    $noviembre_mora=(isset($_POST["noviembre_mora"])?$_POST["noviembre_mora"]:"");
    $noviembre_gas=(isset($_POST["noviembre_gas"])?$_POST["noviembre_gas"]:"");
    $diciembre=(isset($_POST["diciembre"])?$_POST["diciembre"]:"");
    $diciembre_mora=(isset($_POST["diciembre_mora"])?$_POST["diciembre_mora"]:"");
    $diciembre_gas=(isset($_POST["diciembre_gas"])?$_POST["diciembre_gas"]:"");


        //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_aptos SET 
      enero=:enero, enero_mora=:enero_mora, enero_gas=:enero_gas, febrero=:febrero, febrero_mora=:febrero_mora,
      febrero_gas=:febrero_gas, marzo=:marzo, marzo_mora=:marzo_mora, marzo_gas=:marzo_gas, abril=:abril, 
      abril_mora=:abril_mora, abril_gas=:abril_gas, mayo=:mayo, mayo_mora=:mayo_mora, mayo_gas=:mayo_gas,
      junio=:junio, junio_mora=:junio_mora, junio_gas=:junio_gas, julio=:julio, julio_mora=:julio_mora, 
      julio_gas=:julio_gas, agosto=:agosto agosto_mora=:agosto_mora, agosto_gas=:agosto_gas, septiembre=:septiembre,
      septiembre_mora=:septiembre_mora, septiembre_gas=:septiembre_gas, octubre=:octubre,
      octubre_mora=:octubre_mora, octubre_gas=:octubre_gas, noviembre=:noviembre, noviembre_mora=:noviembre_mora,
      noviembre_gas=:noviembre_gas, diciembre=:diciembre, diciembre_mora=:diciembre_mora, diciembre_gas=:diciembre_gas,
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":enero", $enero);
    $sentencia->bindParam(":enero_mora", $enero_mora);
    $sentencia->bindParam(":enero_gas", $enero_gas);
    $sentencia->bindParam(":febrero", $febrero);
    $sentencia->bindParam(":febrero_mora", $febrero_mora);
    $sentencia->bindParam(":febrero_gas", $febrero_gas);
    $sentencia->bindParam(":marzo", $marzo);
    $sentencia->bindParam(":marzo_mora", $marzo_mora);
    $sentencia->bindParam(":marzo_gas", $marzo_gas);
    $sentencia->bindParam(":abril", $abril);
    $sentencia->bindParam(":abril_mora", $abril_mora);
    $sentencia->bindParam(":abril_gas", $abril_gas);
    $sentencia->bindParam(":mayo", $mayo);
    $sentencia->bindParam(":mayo_mora", $mayo_mora);
    $sentencia->bindParam(":mayo_gas", $mayo_gas);
    $sentencia->bindParam(":junio", $junio);
    $sentencia->bindParam(":junio_mora", $junio_mora);
    $sentencia->bindParam(":junio_gas", $junio_gas);
    $sentencia->bindParam(":julio", $julio);
    $sentencia->bindParam(":julio_mora", $julio_mora);
    $sentencia->bindParam(":julio_gas", $julio_gas);
    $sentencia->bindParam(":agosto", $agosto);
    $sentencia->bindParam(":agosto_mora", $agosto_mora);
    $sentencia->bindParam(":agosto_gas", $agosto_gas);
    $sentencia->bindParam(":septiembre", $septiembre);
    $sentencia->bindParam(":septiembre_mora", $septiembre_mora);
    $sentencia->bindParam(":septiembre_gas", $septiembre_gas);
    $sentencia->bindParam(":octubre", $octubre);
    $sentencia->bindParam(":octubre_mora", $octubre_mora);
    $sentencia->bindParam(":octubre_gas", $octubre_gas);
    $sentencia->bindParam(":noviembre", $noviembre);
    $sentencia->bindParam(":noviembre_mora", $noviembre_mora);
    $sentencia->bindParam(":noviembre_gas", $noviembre_gas);
    $sentencia->bindParam(":diciembre", $diciembre);
    $sentencia->bindParam(":diciembre_mora", $diciembre_mora);
    $sentencia->bindParam(":diciembre_gas", $diciembre_gas);


    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:index.php");
};


if(isset($_GET['txID'])){

  $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";

  $sentencia=$conexion->prepare("SELECT * FROM tbl_aptos WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();   
  $registro=$sentencia->fetch(PDO::FETCH_LAZY);

  $apto=$registro["apto"];
  $condominos=$registro["condominos"];
  $mantenimiento=$registro["mantenimiento"];
  $gas=$registro["gas"];
  $telefono=$registro["telefono"];
  $correo=$registro["correo"];
  $forma_de_pago=$registro["forma_de_pago"];
  $fecha_ultimo_pago=$registro["fecha_ultimo_pago"]; 
}




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


            <form action="" method="post" enctype="multipart/form-data" class="formulario_pagos">

                
            <div class="mb-3">
                  <label for="enero" class="form-label">Enero:</label>
                  <input type="text"
                    class="form-control" name="enero" id="enero" aria-describedby="helpId" placeholder="-------" value="<?php echo $enero;?>">

                  <label for="enero_mora" class="form-label">Mora Enero:</label>
                  <input type="text" class="form-control" name="enero_mora" id="enero_mora" aria-describedby="helpId" placeholder="-------" value="<?php echo $enero_mora;?>">

                  <label for="enero_gas" class="form-label">Gas Enero:</label>
                  <input type="text"
                    class="form-control" name="enero_gas" id="enero_gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $enero_gas;?>">
                </div>
                
                
                <div class="mb-3">
                  <label for="telefono" class="form-label">Febrero:</label>
                  <input type="text" class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $febrero;?>">

                  <label for="febrero_mora" class="form-label">Mora Febrero:</label>
                  <input type="text" class="form-control" name="febrero_mora" id="febrero_mora" aria-describedby="helpId" placeholder="-------" value="<?php echo $febrero_mora;?>">

                  <label for="febrero_gas" class="form-label">Gas Febrero:</label>
                  <input type="text" class="form-control" name="febrero_gas" id="febrero_gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $febrero_gas;?>">
                </div>
                
                <div class="mb-3">
                  <label for="telefono" class="form-label">Marzo:</label>
                  <input type="text" class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $marzo;?>">

                  <label for="marzo_mora" class="form-label">Mora Marzo:</label>
                  <input type="text" class="form-control" name="marzo_mora" id="marzo_mora" aria-describedby="helpId" placeholder="-------" value="<?php echo $marzo_mora;?>">

                  <label for="marzo_gas" class="form-label">Gas Marzo:</label>
                  <input type="text" class="form-control" name="marzo_gas" id="marzo_gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $marzo_gas;?>">
                </div>
                
                <div class="mb-3">
                  <label for="abril" class="form-label">Abril:</label>
                  <input type="text" class="form-control" name="abril" id="abril" aria-describedby="helpId" placeholder="-------" value="<?php echo $abril;?>">

                  <label for="abril_mora" class="form-label">Mora Abril:</label>
                  <input type="text"  class="form-control" name="abril_mora" id="abril_mora" aria-describedby="helpId" placeholder="-------" value="<?php echo $abril_mora;?>">

                  <label for="abril_gas" class="form-label">Gas Abril:</label>
                  <input type="text" class="form-control" name="abril_gas" id="abril_gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $abril_gas;?>">
                </div>
                
                <div class="mb-3">
                  <label for="mayo" class="form-label">Mayo:</label>
                  <input type="text" class="form-control" name="mayo" id="mayo" aria-describedby="helpId" placeholder="-------" value="<?php echo $mayo;?>" readonly>

                  <label for="mora" class="form-label">Gas Mayo:</label>
                  <input type="email" class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="-------" value="<?php echo $mayo_mora;?>" readonly>

                  <label for="gas" class="form-label">Mora Mayo:</label>
                  <input type="text" class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="-------" value="<?php echo $mayo_gas;?>" readonly>
                </div>
                
                <div class="mb-3"><br><br><br><br><br><br><br><br><br><br><br><br><br></div>
                <div class="mb-3">
                  <label for="junio" class="form-label">Junio:</label>
                  <input type="text" class="form-control" name="junio" id="junio" aria-describedby="helpId" placeholder="-------" value="<?php echo $junio;?>" readonly>

                  <label for="junio_mora" class="form-label">Junio Mora:</label>
                  <input type="text" class="form-control" name="junio_mora" id="junio_mora" aria-describedby="helpId" placeholder="-------" value="<?php echo $junio_mora;?>" readonly>

                  <label for="junio_gas" class="form-label">Junio Gas:</label>
                  <input type="text" class="form-control" name="junio_gas" id="junio_gas" aria-describedby="helpId" placeholder="-------" value="<?php echo $junio_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="julio" class="form-label">Julio:</label>
                  <input type="text"
                    class="form-control" name="gas" id="julio" aria-describedby="helpId" placeholder="......." value="<?php echo $julio;?>" readonly>

                  <label for="julio_mora" class="form-label">Julio Mora:</label>
                  <input type="email"
                    class="form-control" name="julio_mora" id="Julio_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $julio_mora;?>" readonly>

                  <label for="julio_gas" class="form-label">Julio_Gas:</label>
                  <input type="text"
                    class="form-control" name="julio_gas" id="julio_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $julio_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="agosto" class="form-label">Agosto:</label>
                  <input type="text"
                    class="form-control" name="agosto" id="agosto" aria-describedby="helpId" placeholder="......." value="<?php echo $agosto;?>" readonly>

                  <label for="agosto_mora" class="form-label">Agosto_Mora:</label>
                  <input type="email"
                    class="form-control" name="agosto_mora" id="agosto_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $agosto_mora;?>" readonly>

                  <label for="agosto_gas" class="form-label">Agosto_Gas:</label>
                  <input type="text"
                    class="form-control" name="agosto_gas" id="agosto_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $agosto_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="septiembre" class="form-label">Septiembre:</label>
                  <input type="text"
                    class="form-control" name="septiembre" id="septiembre" aria-describedby="helpId" placeholder="......." value="<?php echo $septiembre;?>" readonly>

                  <label for="septiembre_mora" class="form-label">Septiembre_Mora:</label>
                  <input type="email"
                    class="form-control" name="septiembre_mora" id="septiembre_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $septiembre_mora;?>" readonly>

                  <label for="septiembre_gas" class="form-label">Septiembre_Gas:</label>
                  <input type="text"
                    class="form-control" name="septiembre_gas" id="septiembre_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $septiembre_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="octubre" class="form-label">Octubre:</label>
                  <input type="text"
                    class="form-control" name="octubre" id="octubre" aria-describedby="helpId" placeholder="......." value="<?php echo $octubre;?>" readonly>

                  <label for="octubre_mora" class="form-label">Octubre_Mora:</label>
                  <input type="email"
                    class="form-control" name="octubre_mora" id="octubre_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $octubre_mora;?>" readonly>

                  <label for="octubre_gas" class="form-label">Octubre_Gas:</label>
                  <input type="text"
                    class="form-control" name="octubre_gas" id="octubre_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $octubre_gas;?>" readonly>
                </div>
                
                <div class="mb-3"><br><br><br><br><br><br><br><br><br><br><br><br><br></div>

                <div class="mb-3">
                  <label for="noviembre" class="form-label">Noviembre:</label>
                  <input type="text" class="form-control" name="noviembre" id="noviembre" aria-describedby="helpId" placeholder="......." value="<?php echo $noviembre;?>" readonly>

                  <label for="noviembre_mora" class="form-label">Noviembre_Mora:</label>
                  <input type="email"
                    class="form-control" name="noviembre_mora" id="noviembre_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $noviembre_mora;?>" readonly>

                  <label for="noviembre_gas" class="form-label">Noviembre_Gas:</label>
                  <input type="text"
                    class="form-control" name="noviembre_gas" id="noviembre_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $noviembre_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="diciembre" class="form-label">Diciembre:</label>
                  <input type="text"
                    class="form-control" name="diciembre" id="diciembre" aria-describedby="helpId" placeholder="......." value="<?php echo $diciembre;?>" readonly>

                  <label for="diciembre_mora" class="form-label">Diciembre_mora:</label>
                  <input type="email"
                    class="form-control" name="diciembre_mora" id="diciembre_mora" aria-describedby="helpId" placeholder="......." value="<?php echo $diciembre_mora;?>" readonly>

                  <label for="diciembre_gas" class="form-label">Diciembre_Gas:</label>
                  <input type="text"
                    class="form-control" name="diciembre_gas" id="diciembre_gas" aria-describedby="helpId" placeholder="......." value="<?php echo $diciembre_gas;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="telefono" class="form-label">Gas:</label>
                  <input type="text"
                    class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas" value="<?php echo $gas;?>" readonly>

                  <label for="mora" class="form-label">Correo:</label>
                  <input type="email"
                    class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Escriba la correo" value="<?php echo $correo;?>" readonly>

                  <label for="gas" class="form-label">Telefono:</label>
                  <input type="text"
                    class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono;?>" readonly>
                </div>
                
                <div class="mb-3">
                  <label for="telefono" class="form-label">Gas:</label>
                  <input type="text"
                    class="form-control" name="gas" id="gas" aria-describedby="helpId" placeholder="Escriba el gas" value="<?php echo $gas;?>" readonly>

                  <label for="mora" class="form-label">Correo:</label>
                  <input type="email"
                    class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Escriba la correo" value="<?php echo $correo;?>" readonly>

                  <label for="gas" class="form-label">Telefono:</label>
                  <input type="text"
                    class="form-control" name="telefono" id="telefono" aria-describedby="helpId" placeholder="Escriba el telefono" value="<?php echo $telefono;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="saldo_actual" class="form-label">Ultimo Pago:</label>
                  <input type="text"
                    class="form-control" name="fecha_ultimo_pago" id="fecha_ultimo_pago" aria-describedby="helpId" placeholder="" value="<?php echo $fecha_ultimo_pago;?>" readonly>
                </div>

    </div>
    </div> </div>
    <div class="right">
    <?php include('../../right_panel.php')?>
    </div>
  </div>
<br>
<?php include("../../templates/footer.php"); ?>