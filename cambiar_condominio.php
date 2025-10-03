<?php include("templates/header.php");
include("bd.php");

if($_POST){

    $txtID= $_SESSION['id'];
    //recoleccion de datos
    $idcondominio=(isset($_POST["idcondominio"])?$_POST["idcondominio"]:"");
    $text_condominio=(isset($_POST["text_condominio"])?$_POST["text_condominio"]:"");

        //preparar insercion
    $sentencia=$conexion->prepare("UPDATE tbl_usuarios SET idcondominio=:idcondominio, online=:online
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":idcondominio", $idcondominio);
    $sentencia->bindParam(":online", $text_condominio);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:cerrar.php");
};

?>
   


   
  <br> 

<div class="containerr">
  <div class="left">
    <?php include('left_panel.php')?>
  </div>
    <div class="center">

    
<h2 style="-webkit-text-stroke: 1px black; color: white; font-size: 35px;"> Actividades </h2>


   <div class="card">
        <div class="card-header">
            Cambiar Administracion
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">

              <div class="modal-body">
              <form method="post" action="procesar_consulta.php">
                <div class="mb-3">
                  <label for="" class="form-label">Condominios:</label>
                  <select class="form-select form-select-lg" id="dropcondominios">
                    <?php foreach ($lista_tbl_condominios as $registro_id_cond) { ?>           
                      <option value="<?php echo $registro_id_cond['nombre']; $_SESSION['online']=$registro_id_cond["nombre"];?>">
                        <?php echo $registro_id_cond['nombre'];
                          $idcondominio=$registro_id_cond["id"];
                        ?>
                      </option>           
                    <?php }?>
                  </select>
                </div>
                <br>

                <div class="mb-3">
                  <label for="" class="form-label">Condominio Seleccionado:</label>
                  <input type="text" class="form-control" name="text_condominio" id="text_condominio" placeholder="Seleccionar Condominio" readonly>                
                  <input type="text" class="form-control" name="idcondominio" id="idcondominio" placeholder="Seleccionar idcondominio" readonly>
                </div>
                <button type="sumit" class="btn btn-success">Actualizar</button>
                <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
            </div>
          </div>
      </div>
        <div class="card-footer text-muted">
        </div>
    </div>    



    <div class="right">
      <?php include('right_panel.php')?>
    </div>
  </div>

<br>



<?php include("templates/footer.php"); ?>