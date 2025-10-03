<?php include("../../templates/header.php");
include("../../bd.php");

if (isset($_GET['txID'])) {

  $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

  $sentencia = $conexion->prepare("SELECT * FROM tbl_condominios WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);
  $nombre = $registro["nombre"];
}

if ($_POST) {

  $txtID_usuario = $_SESSION['id'];

  $sentencia = $conexion->prepare("UPDATE tbl_usuarios SET idcondominio=:idcondominio, online=:online WHERE id=:id");
  $sentencia->bindParam(":idcondominio", $txtID);
  $sentencia->bindParam(":online", $nombre);
  $sentencia->bindParam(":id", $txtID_usuario);
  $sentencia->execute();


  $sentencia = $conexion->prepare("SELECT *,count(*) as n_usuarios
    FROM tbl_usuarios
    ");

  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);

  if ($registro["n_usuarios"] > 0) {
    $_SESSION['id'] = $registro["id"];
    $_SESSION['usuario'] = $registro["usuario"];
    $_SESSION['idcondominio'] = $registro["idcondominio"];
    $_SESSION['online'] = $registro["online"];
    $_SESSION['mes'] = $registro["mes"];
    $_SESSION['anio'] = $registro["anio"];
    header("Location:index.php");
  } else {
    $mensaje = "Error: no se aplican las variables de seccion";
  }

  header("Location:index.php");
};

?>
<br><br>
<div class="center">
  <div class="card">
    <div class="card-header">
      <a name="" id="" class="btn btn-primary" href="index.php" role="button">Atras</a>
    </div>
    <div class="card-body">
      <form action="" method="post" enctype="multipart/form-data">

        <p style="color: #000000ff; font-size: 29px;">Datos del Condominio</p>
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre::</label>
          <input type="text"
            class="form-control" name="nombre" id="nombre" aria-describedby="helpId" placeholder="Escriba los apellidos" value="<?php echo $nombre; ?>">
        </div>
        <button type="sumit" class="btn btn-success">Actualizar</button>
        <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
      </form>
    </div>
    <div class="card-footer text-muted">
    </div>
  </div>
</div>
<br>
<?php include("../../templates/footer.php"); ?>