<?php
ob_start();
session_start();
$url_base = "http://localhost/sistemacondomino/";
if (!isset($_SESSION['usuario'])) {
  header("Location:" . $url_base . "login.php");
  $check = null;
}

$servidor = "localhost";
$BaseDeDatos = "sistemacondominio";
$usuario = "root";
$contrasenia = "";

try {
  $conexion = new PDO("mysql:host=$servidor;bdname=$BaseDeDatos", $usuario, $contrasenia);
  $conexion->exec("USE $BaseDeDatos");
} catch (Exception $ex) {
  echo $ex->getMessage();
}


$aniio = $_SESSION['anio'] ?? '';
$mes = $_SESSION['mes'] ?? '';
$idcondominio = $_SESSION['idcondominio'] ?? '';

// Obtener información del usuario si está logueado
$usuario_nombre = $_SESSION['usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? 'Rol no definido';

$sentencia = $conexion->prepare("SELECT * FROM tbl_condominios");
$sentencia->execute();
$lista_tbl_condominios = $sentencia->fetchAll((PDO::FETCH_ASSOC));
$condominios = $lista_tbl_condominios;
// Procesar el cambio de condominio
if ($_POST && isset($_POST['condominio_id'])) {
  $txtID = $_POST['condominio_id'];
  $txtID_usuario = $_SESSION['id'];

  // Obtener nombre del condominio seleccionado
  $sentencia = $conexion->prepare("SELECT nombre FROM tbl_condominios WHERE id=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();
  $condominio = $sentencia->fetch(PDO::FETCH_LAZY);
  $nombre = $condominio["nombre"];

  // Actualizar usuario con el nuevo condominio
  $sentencia = $conexion->prepare("UPDATE tbl_usuarios SET idcondominio=:idcondominio, online=:online WHERE id=:id");
  $sentencia->bindParam(":idcondominio", $txtID);
  $sentencia->bindParam(":online", $nombre);
  $sentencia->bindParam(":id", $txtID_usuario);
  $sentencia->execute();

  // Actualizar variables de sesión
  $sentencia = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE id=:id");
  $sentencia->bindParam(":id", $txtID_usuario);
  $sentencia->execute();
  $registro = $sentencia->fetch(PDO::FETCH_LAZY);

  if ($registro) {
    $_SESSION['id'] = $registro["id"];
    $_SESSION['usuario'] = $registro["usuario"];
    $_SESSION['idcondominio'] = $registro["idcondominio"];
    $_SESSION['online'] = $registro["online"];
    $_SESSION['mes'] = $registro["mes"];
    $_SESSION['anio'] = $registro["anio"];

    // Mostrar mensaje de éxito
    $_SESSION['mensaje'] = "Condominio actualizado correctamente";
    $_SESSION['tipo_mensaje'] = "success";
  } else {
    $_SESSION['mensaje'] = "Error: no se pudieron actualizar las variables de sesión";
    $_SESSION['tipo_mensaje'] = "error";
  }
}


?>
<!doctype html>
<html lang="en">

<head>
  <title>Sistema Condomino</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS v5.2.1 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $url_base; ?>img/favicon.ico">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.css" />
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --accent-color: #e74c3c;
      --light-color: #ecf0f1;
      --dark-color: #2c3e50;
      --success-color: #27ae60;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
    }

    .navbar-custom {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
      padding: 0.5rem 1rem;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: white !important;
      display: flex;
      align-items: center;
    }

    .navbar-brand img {
      margin-right: 10px;
      border-radius: 5px;
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      border-radius: 0.25rem;
      transition: all 0.3s ease;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: white !important;
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateY(-1px);
    }

    .navbar-toggler {
      border: none;
      color: white !important;
    }

    .user-dropdown .dropdown-toggle::after {
      display: none;
    }

    .user-dropdown .dropdown-menu {
      border: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      border-radius: 0.5rem;
      margin-top: 10px;
    }

    .user-info {
      padding: 1rem;
      border-bottom: 1px solid #eee;
    }

    .user-name {
      font-weight: 600;
      color: var(--dark-color);
    }

    .user-role {
      font-size: 0.85rem;
      color: #6c757d;
    }

    .notification-badge {
      position: absolute;
      top: 3px;
      right: 3px;
      background-color: var(--accent-color);
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .search-form {
      position: relative;
      width: 300px;
    }

    .search-form .form-control {
      border-radius: 20px;
      padding-left: 40px;
      background-color: rgba(255, 255, 255, 0.15);
      border: none;
      color: white;
    }

    .search-form .form-control::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .search-form .form-control:focus {
      background-color: rgba(255, 255, 255, 0.25);
      box-shadow: none;
      color: white;
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255, 255, 255, 0.7);
    }

    @media (max-width: 992px) {
      .search-form {
        width: 100%;
        margin: 10px 0;
      }

      .navbar-nav {
        margin-top: 10px;
      }
    }

    .badge-custom {
      font-size: 0.7rem;
      padding: 0.25rem 0.5rem;
    }

    .breadcrumb a {
      text-decoration: none;
      color: #0d6efd;
      transition: color 0.2s ease-in-out;
    }

    .breadcrumb a:hover {
      color: #0a58ca;
    }

    .breadcrumb-item+.breadcrumb-item::before {
      content: "›";
      color: #6c757d;
      font-weight: bold;
    }

    /* Estilos específicos para el menú de condominio */
    .condominio-menu {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      padding: 0.5rem 0;
    }

    .condominio-menu .nav-link {
      color: #495057 !important;
      font-weight: 500;
      position: relative;
    }

    .condominio-menu .nav-link:hover {
      color: #0d6efd !important;
      background-color: transparent;
    }

    .condominio-menu .dropdown-menu {
      border: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      border-radius: 0.5rem;
    }

    .condominio-menu .dropdown-item {
      padding: 0.5rem 1rem;
    }

    .condominio-menu .dropdown-item:hover {
      background-color: #f8f9fa;
    }
  </style>
</head>

<body onload="init();" class="bg-dark">
  <header>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom ">
      <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand" href="<?php echo $url_base; ?>index.php">
          <i class="fas fa-building fa-lg"></i>
          <span>Sistema Condomino</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
          <!-- Search Form -->
          <form class="search-form me-auto">
            <i class="fas fa-search search-icon"></i>
            <input type="search" class="form-control" placeholder="Buscar..." aria-label="Search">
          </form>

          <!-- Navbar Items -->
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <!-- Notifications -->
            <li class="nav-item dropdown">
              <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <h6 class="dropdown-header">Notificaciones</h6>
                </li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i> 5 pagos pendientes</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-file-invoice text-info me-2"></i> 3 gastos por aprobar</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-tools text-success me-2"></i> Mantenimiento programado</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
              </ul>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown user-dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_nombre); ?>&background=3498db&color=fff"
                  class="rounded-circle me-2" width="32" height="32">
                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($usuario_nombre); ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($usuario_nombre); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($usuario_rol); ?></div>
                  </div>
                </li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="<?php echo $url_base; ?>cerrar.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Menú de Navegación Principal -->
    <nav class="navbar navbar-expand-lg navbar-light condominio-menu">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#condominioMenu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="condominioMenu">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <!-- INICIO -->
            <li class="nav-item">
              <a class="nav-link" href="<?php echo $url_base; ?>index.php">INICIO</a>
            </li>

            <!-- CONDOMINIO -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                CONDOMINIO
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/aptos/index.php">Apartamentos</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/empleados/index.php">Empleados</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/condominios/index.php">Condominio</a></li>
              </ul>
            </li>

            <!-- ADMINISTRAR -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                ADMINISTRAR
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/pagos/index.php">Ingresos</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/gastos/index.php">Gastos</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/nomina/index.php">Nomina</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/conciliacion_bancaria/index.php">Solicitud de cheques</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/conciliacion_bancaria/index.php">Conciliación Bancaria</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/estado_de_resultado/index.php">Estado de resultado</a></li>
                <li><a class="dropdown-item" href="#">Facturas</a></li>
              </ul>
            </li>

            <!-- PDF -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                REPORTES
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" target="_blank" href="<?php echo $url_base; ?>secciones/nomina/pdf_nomina.php">Nomina</a></li>
                <li><a class="dropdown-item" target="_blank" href="#">Estado de Cuentas</a></li>
                <li><a class="dropdown-item" target="_blank" href="#">Solicitud de cheques</a></li>
                <li><a class="dropdown-item" target="_blank" href="<?php echo $url_base; ?>secciones/conciliacion_bancaria/pdf_conciliacion.php">Conciliación Bancaria</a></li>
                <li><a class="dropdown-item" target="_blank" href="<?php echo $url_base; ?>secciones/estado_de_resultado/pdf_cuentas_por_cobrar.php">Cuentas Por Cobrar</a></li>
                <li><a class="dropdown-item" target="_blank" href="<?php echo $url_base; ?>secciones/estado_de_resultado/pdf_estado.php">Estado de Resultado</a></li>
              </ul>
            </li>

            <!-- CONFIGURACION -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                CONFIGURACION
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/pagos/crear_anios.php">Generar tickets</a></li>
                <li><a class="dropdown-item" href="<?php echo $url_base; ?>secciones/configuracion/edit_pdf_estado.php">Estado de resultado</a></li>
              </ul>
            </li>

            <!-- CONTACTO -->
            <li class="nav-item">
              <a class="nav-link" href="<?php echo $url_base; ?>cerrar.php">CERRAR</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-white shadow-sm py-2 px-3 border-bottom">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        <!-- Breadcrumb -->
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="<?php echo $url_base; ?>index.php">
              <i class="fas fa-home me-1 text-primary"></i> Inicio
            </a>
          </li>
          <?php
          $current_url = $_SERVER['REQUEST_URI'];
          $url_parts = explode('/', $current_url);

          if (count($url_parts) > 2) {
            $section = $url_parts[count($url_parts) - 2];
            $page = str_replace('.php', '', $url_parts[count($url_parts) - 1]);

            if ($section != 'secciones') {
              echo '<li class="breadcrumb-item"><a href="#">' . ucfirst($section) . '</a></li>';
            }

            if ($page != 'index') {
              echo '<li class="breadcrumb-item active fw-bold" aria-current="page">' . ucfirst($page) . '</li>';
            }
          }
          ?>
        </ol>

        <!-- Quick Actions -->
        <div class="d-flex align-items-center">
          <?php if (!empty($idcondominio)): ?>


            <!-- Button trigger modal -->
            <a data-bs-toggle="modal" data-bs-target="#staticBackdrop">

              <span class="badge rounded-pill bg-primary me-2 shadow-sm">
                <i class="fas fa-building me-1"></i> <?php echo $_SESSION['online']; ?>
              </span>
            </a>

            <!-- Modal -->
            <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form action="" method="post">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCambiarCondominioLabel">Seleccionar Condominio</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label for="condominio_id" class="form-label">Condominio:</label>
                        <select class="form-select" name="condominio_id" id="condominio_id" required>
                          <option value="" selected disabled>Seleccione un condominio</option>
                          <?php foreach ($condominios as $condominio): ?>
                            <option value="<?php echo $condominio['id']; ?>"
                              <?php echo (isset($_SESSION['idcondominio']) && $_SESSION['idcondominio'] == $condominio['id']) ? 'selected' : ''; ?>>
                              <?php echo $condominio['nombre']; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="alert alert-warning">
                        <small>Al cambiar de condominio, se actualizarán sus permisos y datos de acceso.</small>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <button type="submit" class="btn btn-primary">Cambiar Condominio</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

          <?php endif; ?>
          <span class="badge rounded-pill bg-success shadow-sm">
            <i class="fas fa-circle me-1"></i> En línea
          </span>
        </div>
      </div>
    </nav>
  </header>

  <main class="container mt-1">