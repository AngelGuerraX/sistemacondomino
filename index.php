<?php
include("bd.php");
?>
<?php
$la_imagen = 2;
include("templates/header.php");
?>
<br>
<div class="center">
  <div class="card">
    <div class="card-body">
      <h1 style="text-transform: uppercase;" class="display-8" id="nombre_condominio_online"><?php echo $_SESSION['online']; ?> - <?php echo $_SESSION['mes']; ?> - <?php echo $_SESSION['anio']; ?></h1>
    </div>
  </div> <br>

  <div class="row row-cols-1 row-cols-md-3 g-4">
    <div class="col">
      <div class="card">
        <img src="img/g_pago.jpg" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Pagos</h5>
          <p class="card-text">Aplicar pagos a los apartamentos.</p>
          <a name="" id="" class="btn btn-success" href="secciones/pagos/index.php" role="button">Establecer pagos</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="img/gestion_nomina.png" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Estado de Resultado</h5>
          <p class="card-text">Verificar gestion de resultado.</p>
          <a name="" id="" class="btn btn-success" href="secciones/estado_de_resultado/index.php" role="button">Gestionar Estados</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="img/g_nomina.jpg" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Solicitud de Cheques</h5>
          <p class="card-text">Solicitar cheques generales.</p>
          <a name="" id="" class="btn btn-success" href="secciones/pagos/index.php" role="button">Solicitar Cheques</a>
        </div>
      </div>
    </div>
  </div>
  <br>

  <div class="row row-cols-1 row-cols-md-3 g-4">
    <div class="col">
      <div class="card">
        <img src="img/g_pago.jpg" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Pagos</h5>
          <p class="card-text">Aplicar pagos a los apartamentos.</p>
          <a name="" id="" class="btn btn-success" href="secciones/pagos/index.php" role="button">Establecer pagos</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="img/gestion_nomina.png" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Estado de Resultado</h5>
          <p class="card-text">Verificar gestion de resultado.</p>
          <a name="" id="" class="btn btn-success" href="secciones/estado_de_resultado/index.php" role="button">Gestionar Estados</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="img/g_nomina.jpg" height="250" class="card-img-top" alt="...">
        <div class="card-body">
          <h5 class="card-title">Solicitud de Cheques</h5>
          <p class="card-text">Solicitar cheques generales.</p>
          <a name="" id="" class="btn btn-success" href="secciones/pagos/index.php" role="button">Solicitar Cheques</a>
        </div>
      </div>
    </div>
  </div>
  <br>
  <?php include("templates/footer.php"); ?>