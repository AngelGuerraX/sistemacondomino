<?php
session_start();

if($_POST){
include("bd.php");


$sentencia=$conexion->prepare("SELECT *,count(*) as n_usuarios
FROM tbl_usuarios
WHERE usuario= :usuario AND password=:password
");

date_default_timezone_set('America/Santo_Domingo'); // Configurar la zona horaria de República Dominicana
setlocale(LC_TIME, 'es_ES.utf8'); // Establecer la configuración regional en español

$dia = date('d');   // Obtener el día actual
$numero_mes = date('m');   // Obtener el mes actual
$anio = date('Y');  

switch ($numero_mes) {
    case 1:
        $mes = "Enero";
        break;
    case 2:
        $mes = "Febrero";
        break;
    case 3:
        $mes = "Marzo";
        break;
    case 4:
        $mes = "Abril";
        break;
    case 5:
        $mes = "Mayo";
        break;
    case 6:
        $mes = "Junio";
        break;
    case 7:
        $mes = "Julio";
        break;
    case 8:
        $mes = "Agosto";
        break;
    case 9:
        $mes = "Septiembre";
        break;
    case 10:
        $mes = "Octubre";
        break;
    case 11:
        $mes = "Noviembre";
        break;
    case 12:
        $mes = "Diciembre";
        break;
    default:
        $mes = "Mes inválido";
}


$usuario=$_POST["usuario"];
$contrasena=$_POST["contrasena"];

$sentencia->bindParam(":usuario", $usuario);
$sentencia->bindParam(":password", $contrasena);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

if($registro["n_usuarios"]>0){
  $_SESSION['id']=$registro["id"];
  $_SESSION['usuario']=$registro["usuario"];
  $_SESSION['idcondominio']=$registro["idcondominio"]; 
  $_SESSION['online']=$registro["online"];
  $_SESSION['dia'] = $dia;
  $_SESSION['mes'] = $mes;
  $_SESSION['anio'] = $anio;
  header("Location:index.php");
}else{
  $mensaje="Error: El usuario o contraseña incorrectos";
}

$lista_tbl_usuarios=$sentencia->fetch((PDO::FETCH_LAZY));

}

?>

<!doctype html>
<html lang="en">

<head>
  <title>Alplame</title>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS v5.2.1 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">    
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body>
  <header>
    <!-- place navbar here -->
  </header>
  


  <div class="login-box"> 
      <img src="img/logo.png" class="avatar" alt="Avatar Image">
      <h1>Sistema Condomino</h1>
      <form action="" method="post">
        <!-- USERNAME INPUT -->
        <label>Usuario:</label>
        <input type="text" for="usuario" name="usuario" id="usuario" placeholder="Escriba su usuario">
        <!-- PASSWORD INPUT -->
        <label for="contrasena">Contraseña:</label>
        <input type="password" for="contrasena" name="contrasena" id="contrasena" placeholder="Escriba su contraseña">
        <input type="submit" class="form-control" value="Entrar">
        <br><br>
        <?php if(isset($mensaje)) {?>
            <div class="alert alert-danger" role="alert">
                <strong><?php echo $mensaje;?></strong>
            </div>
        <?php }?>
      </form>
    </div>

  <footer>
    <!-- place footer here -->
  </footer>
  <!-- Bootstrap JavaScript Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
    integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous">
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js"
    integrity="sha384-7VPbUDkoPSGFnVtYi0QogXtr74QeVeeIs99Qfg5YCF+TidwNdjvaKZX19NZ/e6oz" crossorigin="anonymous">
  </script>
</body>

</html>