<?php
session_start();

if($_POST){
include("./bd.php");


$sentencia=$conexion->prepare("SELECT *,count(*) as n_usuarios
FROM tbl_usuarios
WHERE usuario= :usuario AND password=:password
");

$usuario=$_POST["usuario"];
$contrasena=$_POST["contrasena"];

$sentencia->bindParam(":usuario", $usuario);
$sentencia->bindParam(":password", $contrasena);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

if($registro["n_usuarios"]>0){
  $_SESSION['usuario']=$registro["usuario"];
  $_SESSION['puesto']=$registro["puesto"];
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
  <title>Orica</title>
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
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>

</html>