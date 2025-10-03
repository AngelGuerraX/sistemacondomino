<?php 
include("../../bd.php");

?>
<?php include("../../templates/header.php"); ?>
<?php include("../../css/camion_mmu.php"); ?>
<br>   
   <h3>Cargar MMU </h3>
   <br>   
   <h4>
   <div class="card">
        <div class="card-header">
            Añadir Carga
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">

              <?php
                if(isset($_POST['usuario'])){
                  $fecha = $_POST['fecha'];
                  $hora = $_POST['hora'];
                  $Camion_MMU = $_POST['Camion_MMU'];
                  $Operador = $_POST['Operador'];
                  $Cant_Requerida = $_POST['Cant_Requerida'];
                  $inicio = $_POST['inicio'];
                  $final = $_POST['final'];
                  $rango = $_POST['rango'];
                  
                  $IIPULG=$_POST["IIPULG"];
                  $inv_i_mt=$_POST["inv_i_mt"];
                  $ENTRADAMT=$_POST["ENTRADAMT"];
                  $ENTRADAPULG=$_POST["ENTRADAPULG"];
                  $IFPULG=$_POST["IFPULG"];
                  $IFMT=$_POST["IFMT"];
                  $IDespacho=$_POST["IDespacho"];
                  $IRestante=$_POST["IRestante"];
                  $Descripcion=$_POST["Descripcion"];

                  $campos = array();

                  if($fecha == ""){
                    array_push($campos, "La fecha no puede estar vacia.");
                  }
                  if($hora == ""){
                    array_push($campos, "La hora no puede estar vacia.");
                  }
                  if($Camion_MMU == ""){
                    array_push($campos, "El Camion MMU no puede estar vacio.");
                  }
                  if($Operador == ""){
                    array_push($campos, "El Operador no puede estar vacio");
                  }
                  if($Cant_Requerida == ""){
                    array_push($campos, "Cantidad requerida esta vacio.");
                  }
                  if($inicio == ""){
                    array_push($campos, "El tiempo inicial esta vacio.");
                  }
                  if($final == ""){
                    array_push($campos, "El tiempo final esta vacio.");
                  }
                  if($rango == ""){
                    array_push($campos, "El rango no puede estar vacio");
                  }

                  if($IIPULG == ""){
                    array_push($campos, "El inventario inicial pulg esta vacio");
                  }
                  if($inv_i_mt == ""){
                    array_push($campos, "El inventario inicial mt esta vacio");
                  }
                  if($ENTRADAMT == ""){
                    array_push($campos, "La entrada mt esta vacia");
                  }
                  if($ENTRADAPULG == ""){
                    array_push($campos, "La entrada pulg esta vacia.");
                  }
                  if($IFPULG == ""){
                    array_push($campos, "El inventario final pulg esta vacio.");
                  }
                  if($IFMT == ""){
                    array_push($campos, "El inventario final mt esta vacio.");
                  }


                  if(count($campos) > 0){
                    echo "<div class='alert alert-danger' role='alert'>";
                    for($i = 0; $i < count($campos); $i++){
                      echo "<li>".$campos[$i]."</li>";
                    }
                  }else{
                    echo "<div class='alert alert-success' role='alert'>
                    Datos Correctos";
                    if($_POST){
                        //recoleccion de datos
                        $fecha=(isset($_POST["fecha"])?$_POST["fecha"]:"");
                        $hora=(isset($_POST["hora"])?$_POST["hora"]:"");
                        $usuario=(isset($_POST["usuario"])?$_POST["usuario"]:"");
                        $Operador=(isset($_POST["Operador"])?$_POST["Operador"]:"");
                        $Camion_MMU=(isset($_POST["Camion_MMU"])?$_POST["Camion_MMU"]:"");
                        $Cant_Requerida=(isset($_POST["Cant_Requerida"])?$_POST["Cant_Requerida"]:"");
                        $inicio=(isset($_POST["inicio"])?$_POST["inicio"]:"");
                        $final=(isset($_POST["final"])?$_POST["final"]:"");
                        $rango=(isset($_POST["rango"])?$_POST["rango"]:"");
                    
                        $IIPULG=(isset($_POST["IIPULG"])?$_POST["IIPULG"]:"");
                        $inv_i_mt=(isset($_POST["inv_i_mt"])?$_POST["inv_i_mt"]:"");
                        $ENTRADAMT=(isset($_POST["ENTRADAMT"])?$_POST["ENTRADAMT"]:"");
                        $ENTRADAPULG=(isset($_POST["ENTRADAPULG"])?$_POST["ENTRADAPULG"]:"");
                        $IFPULG=(isset($_POST["IFPULG"])?$_POST["IFPULG"]:"");
                        $IFMT=(isset($_POST["IFMT"])?$_POST["IFMT"]:"");
                        $IDespacho=(isset($_POST["IDespacho"])?$_POST["IDespacho"]:"");
                        $IRestante=(isset($_POST["IRestante"])?$_POST["IRestante"]:"");
                        $Descripcion=(isset($_POST["Descripcion"])?$_POST["Descripcion"]:"");
                            //preparar insercion
                        $sentencia=$conexion->prepare("INSERT INTO tbl_cargar_camion (fecha, hora, usuario, operador, camion_mmu, cant_requerida, t_inicio, t_final, rango_t, inv_inicial_pulg, inv_inicial_mt, entrada_mt, entrada_pulg, inv_final_pulg, inv_final_mt, despacho_mt, restante_en_silo, descripcion, id)
                        VALUES (:fecha, :hora, :usuario, :Operador, :Camion_MMU, :Cant_Requerida, :inicio, :final, :rango, :IIPULG, :inv_i_mt, :ENTRADAMT, :ENTRADAPULG, :IFPULG, :IFMT, :IDespacho, :IRestante, :Descripcion, null)");
                        
                        //Asignando los valores de metodo post(del formulario)
                        $sentencia->bindParam(":fecha", $fecha);
                        $sentencia->bindParam(":hora", $hora);
                        $sentencia->bindParam(":usuario", $usuario);
                        $sentencia->bindParam(":Operador", $Operador);
                        $sentencia->bindParam(":Camion_MMU", $Camion_MMU);
                        $sentencia->bindParam(":Cant_Requerida", $Cant_Requerida);
                        $sentencia->bindParam(":inicio", $inicio);
                        $sentencia->bindParam(":final", $final);
                        $sentencia->bindParam(":rango", $rango);
                    
                        $sentencia->bindParam(":IIPULG", $IIPULG);
                        $sentencia->bindParam(":inv_i_mt", $inv_i_mt);
                        $sentencia->bindParam(":ENTRADAMT", $ENTRADAMT);
                        $sentencia->bindParam(":ENTRADAPULG", $ENTRADAPULG);
                        $sentencia->bindParam(":IFPULG", $IFPULG);
                        $sentencia->bindParam(":IFMT", $IFMT);
                        $sentencia->bindParam(":IDespacho", $IDespacho);
                        $sentencia->bindParam(":IRestante", $IRestante);
                        $sentencia->bindParam(":Descripcion", $Descripcion);
                        $sentencia->execute();                        
                        header("Location:index.php");
                    }
                  }
                  echo "</div>";
                }            
              ?>

              <div class="mb-3">
                <label for="usuario" class="form-label">Usuario:</label>
                <input type="text"
                  class="form-control" name="usuario" id="usuario" aria-describedby="helpId" placeholder="usuario" readonly value="<?php echo $_SESSION['usuario'];?>">
              </div>
              <div class="mb-3">
                <label for="fecha" class="form-label">Fecha:</label>
                <input type="date" class="form-control" name="fecha" id="fecha" aria-describedby="emailHelpId" placeholder="Fecha de carga">
              </div>
              <div class="mb-3">
                <label for="hora" class="form-label">Hora:</label>
                <input type="time" class="form-control" name="hora" id="hora" aria-describedby="emailHelpId" placeholder="Hora de carga">
              </div>
                <div class="mb-3">
                  <label for="Operador" class="form-label">Operador:</label>
                  <input type="text"
                    class="form-control" name="Operador" id="Operador" aria-describedby="helpId" placeholder="Operador">
                </div>
                <div class="mb-3">
                  <label for="Camion_MMU" class="form-label">Camion MMU:</label>
                  <input type="text" class="form-control" name="Camion_MMU" id="Camion_MMU" aria-describedby="emailHelpId" placeholder="Camion MMU">
                </div>
                <div class="mb-3">
                  <label for="Cant_Requerida" class="form-label">Cantidad Requerida:</label>
                  <input type="text" class="form-control" name="Cant_Requerida" id="Cant_Requerida" aria-describedby="emailHelpId" placeholder="Cantidad Requerida">
                </div>
                <div class="mb-3">
                  <label for="T_inicial" class="form-label">Tiempo Inicial:</label>
                  <input type="time" class="form-control" name="inicio" id="inicio" aria-describedby="emailHelpId" placeholder="Tiempo Inicial">
                </div>
                <div class="mb-3">
                  <label for="T_final" class="form-label">Tiempo Final:</label>
                  <input type="time" class="form-control" name="final" id="final" aria-describedby="emailHelpId" placeholder="Tiempo Final">
                </div>
                <div class="mb-3">
                  <label for="T_rango" class="form-label">Rango de Tiempo:</label>
                  <input type="text" class="form-control" name="rango" id="rango" aria-describedby="emailHelpId" placeholder="Rango de Tiempo" readonly>
                </div>
        </div> 
        </div>
    </div>   
</div>

<br>
<br>

<div class="card">
        <div class="card-header">
            Datos Carga
        </div>
        <div class="card-body">

            
            
            <div class="mb-3">
                <label for="IIPULG" class="form-label">Inventario Inicial (pulg):</label>
                <input type="text" class="form-control" name="IIPULG" id="IIPULG" aria-describedby="helpId" placeholder="Escribe el inventario inicial">
              </div> 
                <div class="mb-3">
                  <label for="inv_i_mt" class="form-label ">Inventario Inicial (MT):</label>
                  <input type="text" class="form-control bg-light" name="inv_i_mt" id="inv_i_mt" aria-describedby="helpId" placeholder="" readonly>
                </div>
                <div class="mb-3">
                  <label for="ENTRADAMT" class="form-label">Entrada (MT):</label>
                  <input type="text" class="form-control" name="ENTRADAMT" id="ENTRADAMT" aria-describedby="emailHelpId" placeholder="Escribe la Entrada">
                </div>                
                <div class="mb-3">
                  <label for="ENTRADAPULG" class="form-label">Entrada (Pulg):</label>
                  <input type="text" class="form-control bg-light" name="ENTRADAPULG" id="ENTRADAPULG" aria-describedby="emailHelpId" placeholder="" readonly>
                </div>
                <div class="mb-3">
                  <label for="IFPULG" class="form-label">Inventario Final (pulg):</label>
                  <input type="text" class="form-control" name="IFPULG" id="IFPULG" aria-describedby="emailHelpId" placeholder="Escribe el inventario final">
                </div>
                <div class="mb-3">
                  <label for="IFMT" class="form-label">Inventario Final (MT):</label>
                  <input type="text" class="form-control bg-light" name="IFMT" id="IFMT" aria-describedby="emailHelpId" placeholder="" readonly>
                </div>
              
                <div class="mb-3">
                  <label for="IDespacho" class="form-label">Despacho Camion:</label>
                  <input type="text" class="form-control" name="IDespacho" id="IDespacho" aria-describedby="emailHelpId" placeholder="" readonly>
                </div>
                <div class="mb-3">
                  <label for="IRestante" class="form-label">Restante en silo:</label>
                  <input type="text" class="form-control" name="IRestante" id="IRestante" aria-describedby="emailHelpId" placeholder="" readonly>
                </div>
                <div class="mb-3">
                  <label for="Descripcion" class="form-label">Descripcion:</label>
                  <textarea class="form-control" id="Descripcion" name="Descripcion" rows="3"></textarea>
                </div>

                <button type="sumit" class="btn btn-success">Agregar</button>
              <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>

    <br><br>

    </h4>

    <script src="../../js/camion.js"></script> 
<?php include("../../templates/footer.php"); ?>