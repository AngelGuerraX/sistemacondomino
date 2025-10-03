<?php 
include("../../bd.php");

if(isset($_GET['txID'])){

    $txtID=(isset($_GET['txID'] ))?$_GET['txID']:"";
 
    $sentencia=$conexion->prepare("SELECT * FROM tbl_cargar_camion WHERE id=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro=$sentencia->fetch(PDO::FETCH_LAZY);
    $usuario=$registro["usuario"];
    $fecha=$registro["fecha"];
    $hora=$registro["hora"];
    $Operador=$registro["operador"];
    $Camion_MMU=$registro["camion_mmu"];
    $Cant_Requerida=$registro["cant_requerida"];
    $inicio=$registro["t_inicio"];
    $final=$registro["t_final"];
    $rango=$registro["rango_t"];

    $IIPULG=$registro["inv_inicial_pulg"];
    $inv_i_mt=$registro["inv_inicial_mt"];
    $ENTRADAMT=$registro["entrada_mt"];
    $ENTRADAPULG=$registro["entrada_pulg"];
    $IFPULG=$registro["inv_final_pulg"];
    $IFMT=$registro["inv_final_mt"];
    $IDespacho=$registro["despacho_mt"];
    $IRestante=$registro["restante_en_silo"];
    $Descripcion=$registro["descripcion"]; 
 }

?>
<?php include("../../templates/header.php"); ?>
<?php include("../../css/camion_mmu.php"); ?>
<br>   
   <h3>Camion MMU </h3>
   <br>   
   <h4>
   <div class="card">
        <div class="card-header">
            Vista Reporte
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">

              <div class="mb-3">
                <label for="usuario" class="form-label">Usuario:</label>
                <input type="text"
                  class="form-control bg-light" name="usuario" id="usuario" aria-describedby="helpId" placeholder="usuario" readonly value="<?php echo $usuario;?>">
              </div>
              <div class="mb-3">
                <label for="fecha" class="form-label">Fecha:</label>
                <input type="date" class="form-control bg-light" name="fecha" id="fecha" aria-describedby="emailHelpId" readonly placeholder="Fecha de carga" value="<?php echo $fecha;?>">
              </div>
              <div class="mb-3">
                <label for="hora" class="form-label">Hora:</label>
                <input type="time" class="form-control bg-light" name="hora" id="hora" aria-describedby="emailHelpId" readonly placeholder="Hora de carga" value="<?php echo $hora;?>">
              </div>
                <div class="mb-3">
                  <label for="Operador" class="form-label">Operador:</label>
                  <input type="text"
                    class="form-control bg-light" name="Operador" id="Operador" aria-describedby="helpId" readonly placeholder="Operador" value="<?php echo $Operador;?>">
                </div>
                <div class="mb-3">
                  <label for="Camion_MMU" class="form-label">Camion MMU:</label>
                  <input type="text" class="form-control bg-light" name="Camion_MMU" id="Camion_MMU" readonly aria-describedby="emailHelpId" placeholder="Camion MMU" value="<?php echo $Camion_MMU;?>">
                </div>
                <div class="mb-3">
                  <label for="Cant_Requerida" class="form-label">Cantidad Requerida:</label>
                  <input type="text" class="form-control bg-light" name="Cant_Requerida" id="Cant_Requerida" readonly aria-describedby="emailHelpId" placeholder="Cantidad Requerida" value="<?php echo $Cant_Requerida;?>">
                </div>
                <div class="mb-3">
                  <label for="T_inicial" class="form-label">Tiempo Inicial:</label>
                  <input type="time" class="form-control bg-light" name="inicio" id="inicio" readonly aria-describedby="emailHelpId" placeholder="Tiempo Inicial" value="<?php echo $inicio;?>">
                </div>
                <div class="mb-3">
                  <label for="T_final" class="form-label">Tiempo Final:</label>
                  <input type="time" class="form-control bg-light" name="final" id="final" readonly aria-describedby="emailHelpId" placeholder="Tiempo Final" value="<?php echo $final;?>">
                </div>
                <div class="mb-3">
                  <label for="T_rango" class="form-label">Rango de Tiempo:</label>
                  <input type="text" class="form-control bg-light" name="rango" id="rango" readonly aria-describedby="emailHelpId" placeholder="Rango de Tiempo" readonly value="<?php echo $rango;?>">
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
                <input type="text" class="form-control bg-light" name="IIPULG" id="IIPULG" aria-describedby="helpId" placeholder="Escribe el inventario inicial" value="<?php echo $IIPULG;?>" readonly>
              </div> 
                <div class="mb-3">
                  <label for="inv_i_mt" class="form-label ">Inventario Inicial (MT):</label>
                  <input type="text" class="form-control bg-light" name="inv_i_mt" id="inv_i_mt" aria-describedby="helpId" placeholder="" readonly value="<?php echo $inv_i_mt;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="ENTRADAMT" class="form-label">Entrada (MT):</label>
                  <input type="text" class="form-control bg-light" name="ENTRADAMT" id="ENTRADAMT" aria-describedby="emailHelpId" placeholder="Escribe la Entrada" value="<?php echo $ENTRADAMT;?>" readonly>
                </div>                
                <div class="mb-3">
                  <label for="ENTRADAPULG" class="form-label">Entrada (Pulg):</label>
                  <input type="text" class="form-control bg-light" name="ENTRADAPULG" id="ENTRADAPULG" aria-describedby="emailHelpId" placeholder="" readonly value="<?php echo $ENTRADAPULG;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="IFPULG" class="form-label">Inventario Final (pulg):</label>
                  <input type="text" class="form-control bg-light" name="IFPULG" id="IFPULG" aria-describedby="emailHelpId" placeholder="Escribe el inventario final" value="<?php echo $IFPULG;?>" readonly>
                </div>
                <div class="mb-3">
                  <label for="IFMT" class="form-label">Inventario Final (MT):</label>
                  <input type="text" class="form-control bg-light" name="IFMT" id="IFMT" aria-describedby="emailHelpId" placeholder="" readonly value="<?php echo $IFMT;?>">
                </div>
              
                <div class="mb-3">
                  <label for="IDespacho" class="form-label">Despacho Camion:</label>
                  <input type="text" class="form-control bg-light" name="IDespacho" id="IDespacho" aria-describedby="emailHelpId" placeholder="" readonly value="<?php echo $IDespacho;?>">
                </div>
                <div class="mb-3">
                  <label for="IRestante" class="form-label">Restante en silo:</label>
                  <input type="text" class="form-control bg-light" name="IRestante" id="IRestante" aria-describedby="emailHelpId" placeholder="" readonly value="<?php echo $IRestante;?>">
                </div>
                <div class="mb-3">
                  <label for="Descripcion" class="form-label">Descripcion:</label>
                  <textarea class="form-control bg-light" id="Descripcion" name="Descripcion" rows="3" value="<?php echo $Descripcion;?>" readonly></textarea>
                </div>

              <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>

    <br><br>

    </h4>
<?php include("../../templates/footer.php"); ?>