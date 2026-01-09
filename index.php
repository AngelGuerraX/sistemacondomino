<?php include("bd.php");
$thetitle = "Panel Principal";
include("templates/header.php");

// 1. OBTENER DATOS CLAVE (Lógica original mantenida)
$idcondominio = $_SESSION['idcondominio'];

// Total Apartamentos
$sentencia = $conexion->prepare("SELECT COUNT(*) as total FROM tbl_aptos WHERE id_condominio = :id");
$sentencia->execute([':id' => $idcondominio]);
$total_aptos = $sentencia->fetch(PDO::FETCH_ASSOC)['total'];

// Total Empleados
$sentencia = $conexion->prepare("SELECT COUNT(*) as total FROM tbl_empleados WHERE idcondominio = :id AND activo = 'si'");
$sentencia->execute([':id' => $idcondominio]);
$total_empleados = $sentencia->fetch(PDO::FETCH_ASSOC)['total'];

// Ingresos Mes
$sentencia_ingresos = $conexion->prepare("
    SELECT SUM(monto) as total FROM tbl_pagos 
    WHERE id_condominio = :id AND MONTH(fecha_pago) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())
");
$sentencia_ingresos->execute([':id' => $idcondominio]);
$ingresos_mes = $sentencia_ingresos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Gastos Mes
$meses_es = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$mes_actual_txt = $meses_es[date('n')];
$anio_actual = date('Y');

$sentencia_gastos = $conexion->prepare("
    SELECT SUM(monto) as total FROM tbl_gastos 
    WHERE id_condominio = :id AND mes = :mes AND anio = :anio
");
$sentencia_gastos->execute([':id' => $idcondominio, ':mes' => $mes_actual_txt, ':anio' => $anio_actual]);
$gastos_mes = $sentencia_gastos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$balance_caja = $ingresos_mes - $gastos_mes;
?>

<div class="container-fluid px-4">

  <div class="row mt-4 mb-4 align-items-center">
    <div class="col-md-8">
      <h2 class="fw-bold text-dark">Hola, <?php echo $_SESSION['usuario']; ?> 👋</h2>
      <p class="text-muted">Resumen de <strong><?php echo $_SESSION['online']; ?></strong> para <?php echo $mes_actual_txt; ?></p>
    </div>
    <div class="col-md-4">
      <div class="card bg-primary text-white shadow border-0">
        <div class="card-body d-flex justify-content-between align-items-center p-3">
          <div>
            <small class="text-white-50 text-uppercase fw-bold">Balance de Caja</small>
            <h3 class="mb-0 fw-bold">RD$ <?php echo number_format($balance_caja, 2); ?></h3>
          </div>
          <i class="fas fa-wallet fa-2x text-white-50"></i>
        </div>
      </div>
    </div>
  </div>

  <h6 class="text-uppercase text-muted fw-bold mb-3 small"><i class="fas fa-rocket"></i> Accesos Directos</h6>
  <div class="row mb-4">
    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/pagos/index.php" class="btn btn-success w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-cash-register fa-2x mb-2"></i>
        <span class="fw-bold">Registrar Cobro</span>
      </a>
    </div>
    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/gastos/index.php" class="btn btn-danger w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
        <span class="fw-bold">Registrar Gasto</span>
      </a>
    </div>
    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/reportes/reportes.php" target="_blank" class="btn btn-secondary w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-file fa-2x mb-2"></i>
        <span class="fw-bold">Solicitud de Cheques</span>
      </a>
    </div>
    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/aptos/index.php" class="btn btn-primary w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-building fa-2x mb-2"></i>
        <span class="fw-bold">Directorio Aptos</span>
      </a>
    </div>
    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/nomina/index.php" class="btn btn-info text-white w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-user-tie fa-2x mb-2"></i>
        <span class="fw-bold">Ir a Nómina</span>
      </a>
    </div>

    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/gas/index.php" class="btn btn-warning w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-fire fa-2x mb-2"></i>
        <span class="fw-bold">Gestión de Gas</span>
      </a>
    </div>

    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/conciliacion_bancaria/index.php" class="btn btn-dark w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-university fa-2x mb-2"></i>
        <span class="fw-bold">Conciliación</span>
      </a>
    </div>

    <div class="col-md-3 mb-2">
      <a href="<?php echo $url_base; ?>secciones/estado_de_resultado/index.php" class="btn btn-secondary w-100 py-3 shadow-sm border-0 d-flex flex-column align-items-center btn-hover">
        <i class="fas fa-chart-line fa-2x mb-2"></i>
        <span class="fw-bold">Resultados</span>
      </a>
    </div>
  </div>

  <div class="row">

    <div class="col-lg-4 mb-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-bold border-0 py-3">Resumen Financiero</div>
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted"><i class="fas fa-arrow-up text-success"></i> Ingresos</span>
            <span class="fw-bold text-dark">RD$ <?php echo number_format($ingresos_mes, 2); ?></span>
          </div>
          <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted"><i class="fas fa-arrow-down text-danger"></i> Gastos</span>
            <span class="fw-bold text-dark">RD$ <?php echo number_format($gastos_mes, 2); ?></span>
          </div>
          <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($ingresos_mes > 0) ? ($gastos_mes / $ingresos_mes) * 100 : 0; ?>%"></div>
          </div>

          <hr>
          <div class="row text-center mt-4">
            <div class="col-6 border-end">
              <h5 class="fw-bold m-0"><?php echo $total_aptos; ?></h5>
              <small class="text-muted">Aptos</small>
            </div>
            <div class="col-6">
              <h5 class="fw-bold m-0"><?php echo $total_empleados; ?></h5>
              <small class="text-muted">Empleados</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8 mb-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-bold border-0 py-3 d-flex justify-content-between align-items-center">
          <span><i class="fas fa-history"></i> Historial de Actividad Reciente</span>
          <a href="<?php echo $url_base; ?>secciones/pagos/index.php" class="btn btn-sm btn-outline-secondary">Ver Todo</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light text-secondary small text-uppercase">
                <tr>
                  <th class="ps-4">Fecha</th>
                  <th>Descripción</th>
                  <th>Monto</th>
                  <th class="text-end pe-4">Detalle</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // CONSULTA PARA LOGS (ÚLTIMOS PAGOS)
                $sentencia_logs = $conexion->prepare("
                                SELECT p.id_pago, p.fecha_pago, p.monto, p.usuario_registro, a.apto, a.condominos
                                FROM tbl_pagos p
                                JOIN tbl_aptos a ON p.id_apto = a.id
                                WHERE p.id_condominio = :id
                                ORDER BY p.id_pago DESC
                                LIMIT 6
                            ");
                $sentencia_logs->execute([':id' => $idcondominio]);
                $lista_logs = $sentencia_logs->fetchAll(PDO::FETCH_ASSOC);

                if (count($lista_logs) > 0) {
                  foreach ($lista_logs as $log):
                ?>
                    <tr>
                      <td class="ps-4 small text-muted"><?php echo date('d/m/Y', strtotime($log['fecha_pago'])); ?></td>
                      <td>
                        <div class="fw-bold text-dark">Pago Apto <?php echo $log['apto']; ?></div>
                        <div class="small text-muted"><i class="fas fa-user-circle"></i> <?php echo $log['usuario_registro']; ?></div>
                      </td>
                      <td class="fw-bold text-success">
                        + RD$ <?php echo number_format($log['monto'], 2); ?>
                      </td>
                      <td class="text-end pe-4">
                        <a href="<?php echo $url_base; ?>secciones/pagos/ver_recibo.php?id_pago=<?php echo $log['id_pago']; ?>" target="_blank" class="btn btn-sm btn-light text-dark border">
                          <i class="fas fa-print"></i> Recibo
                        </a>
                      </td>
                    </tr>
                  <?php endforeach;
                } else { ?>
                  <tr>
                    <td colspan="4" class="text-center py-4 text-muted">No hay actividad reciente registrada.</td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row text-center mt-3 mb-5 opacity-75">
    <div class="col-md-3">
      <a href="<?php echo $url_base; ?>secciones/nomina/index.php" class="text-decoration-none text-secondary small btn btn-dark"><i class="fas fa-user-tie"></i> Ir a Nómina</a>
    </div>
    <div class="col-md-3">
      <a href="<?php echo $url_base; ?>secciones/gas/index.php" class="text-decoration-none text-secondary small btn btn-dark"><i class="fas fa-fire"></i> Gestión de Gas</a>
    </div>
    <div class="col-md-3">
      <a href="<?php echo $url_base; ?>secciones/conciliacion_bancaria/index.php" class="text-decoration-none text-secondary small btn btn-dark"><i class="fas fa-university"></i> Conciliación</a>
    </div>
    <div class="col-md-3">
      <a href="<?php echo $url_base; ?>secciones/estado_de_resultado/index.php" class="text-decoration-none text-secondary small btn btn-dark"><i class="fas fa-chart-line"></i> Resultados</a>
    </div>
  </div>

</div>

<style>
  .btn-hover {
    transition: transform 0.2s;
  }

  .btn-hover:hover {
    transform: translateY(-3px);
  }

  .card {
    border-radius: 12px;
  }

  .progress {
    border-radius: 10px;
    background-color: #f0f0f0;
  }
</style>

<?php include("templates/footer.php"); ?>