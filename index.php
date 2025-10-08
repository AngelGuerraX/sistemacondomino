<?php include("bd.php"); ?>

<?php include("templates/header.php"); ?>

<div class="container-fluid py-4">
  <!-- Header del Dashboard -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-dark text-white shadow">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h1 class="h2 mb-1">Dashboard Principal</h1>
              <p class="mb-0 opacity-8">Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?> - Resumen completo del sistema</p>
            </div>
            <div class="col-md-4 text-end">
              <div class="bg-white bg-dark rounded p-3 d-inline-block">
                <i class="fas fa-building fa-2x bg-dark text-white"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tarjetas de Resumen Rápidas -->
  <div class="row mb-4">
    <!-- Ingresos del Mes -->
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                Ingresos del Mes</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?php
                $sentencia_ingresos = $conexion->prepare("SELECT SUM(CAST(monto AS DECIMAL(10,2))) as total 
                                                                        FROM tbl_pagos 
                                                                        WHERE id_condominio = :id_condominio 
                                                                        AND MONTH(fecha_pago) = MONTH(CURRENT_DATE())
                                                                        AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())");
                $sentencia_ingresos->bindParam(":id_condominio", $idcondominio);
                $sentencia_ingresos->execute();
                $ingresos = $sentencia_ingresos->fetch(PDO::FETCH_ASSOC);
                echo 'RD$ ' . number_format($ingresos['total'] ?? 0, 2, '.', ',');
                ?>
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gastos del Mes -->
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                Gastos del Mes</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?php
                $sentencia_gastos = $conexion->prepare("SELECT SUM(CAST(monto AS DECIMAL(10,2))) as total 
                                                                      FROM tbl_gastos 
                                                                      WHERE id_condominio = :id_condominio 
                                                                      AND mes = :mes 
                                                                      AND anio = :anio");
                $sentencia_gastos->bindParam(":id_condominio", $idcondominio);
                $sentencia_gastos->bindParam(":mes", $mes);
                $sentencia_gastos->bindParam(":anio", $aniio);
                $sentencia_gastos->execute();
                $gastos = $sentencia_gastos->fetch(PDO::FETCH_ASSOC);
                echo 'RD$ ' . number_format($gastos['total'] ?? 0, 2, '.', ',');
                ?>
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-receipt fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Apartamentos Activos -->
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                Apartamentos Activos</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?php
                $sentencia_aptos = $conexion->prepare("SELECT COUNT(*) as total FROM tbl_aptos WHERE id_condominio = :id_condominio");
                $sentencia_aptos->bindParam(":id_condominio", $idcondominio);
                $sentencia_aptos->execute();
                $aptos = $sentencia_aptos->fetch(PDO::FETCH_ASSOC);
                echo $aptos['total'] ?? 0;
                ?>
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-home fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagos Pendientes -->
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                Pagos Pendientes</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?php
                $sentencia_pendientes = $conexion->prepare("SELECT COUNT(*) as total FROM tbl_tickets 
                                                                          WHERE id_condominio = :id_condominio 
                                                                          AND estado = 'Pendiente'
                                                                          AND mes = :mes 
                                                                          AND anio = :anio");
                $sentencia_pendientes->bindParam(":id_condominio", $idcondominio);
                $sentencia_pendientes->bindParam(":mes", $mes);
                $sentencia_pendientes->bindParam(":anio", $aniio);
                $sentencia_pendientes->execute();
                $pendientes = $sentencia_pendientes->fetch(PDO::FETCH_ASSOC);
                echo $pendientes['total'] ?? 0;
                ?>
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos y Estadísticas -->
  <div class="row">
    <!-- Gráfico de Ingresos vs Gastos -->
    <div class="col-xl-8 col-lg-7">
      <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-primary">Ingresos vs Gastos - Últimos 6 Meses</h6>
        </div>
        <div class="card-body">
          <div class="chart-area">
            <canvas id="ingresosGastosChart" height="300"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Distribución de Gastos -->
    <div class="col-xl-4 col-lg-5">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Distribución de Gastos</h6>
        </div>
        <div class="card-body bg-dark text-white">
          <div class="chart-pie pt-4 pb-2">
            <canvas id="gastosChart" height="250"></canvas>
          </div>
          <div class="mt-4 text-center small" id="gastosLegend"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Acciones Rápidas y Últimos Movimientos -->
  <div class="row">
    <!-- Acciones Rápidas -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <a href="<?php echo $url_base; ?>secciones/pagos/index.php" class="btn btn-success btn-block btn-lg h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                <span>Registrar Pago</span>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="<?php echo $url_base; ?>secciones/gastos/index.php" class="btn btn-danger btn-block btn-lg h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-receipt fa-2x mb-2"></i>
                <span>Registrar Gasto</span>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="<?php echo $url_base; ?>secciones/gastos/solicitud_cheques.php" class="btn btn-info btn-block btn-lg h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                <span>Solicitar Cheques</span>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="<?php echo $url_base; ?>secciones/aptos/index.php" class="btn btn-warning btn-block btn-lg h-100 d-flex flex-column justify-content-center">
                <i class="fas fa-home fa-2x mb-2"></i>
                <span>Gestionar Apartamentos</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Últimos Movimientos -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Últimos Movimientos</h6>
          <a href="#" class="btn btn-sm btn-outline-primary">Ver Todos</a>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            <?php
            $sentencia_movimientos = $conexion->prepare("
                            (SELECT 'pago' as tipo, concepto as descripcion, monto, fecha_pago as fecha, usuario_registro 
                             FROM tbl_pagos 
                             WHERE id_condominio = :id_condominio 
                             ORDER BY fecha_pago DESC LIMIT 5)
                            UNION ALL
                            (SELECT 'gasto' as tipo, detalles as descripcion, monto, CONCAT(anio, '-', 
                             CASE mes 
                                 WHEN 'Enero' THEN '01'
                                 WHEN 'Febrero' THEN '02'
                                 WHEN 'Marzo' THEN '03'
                                 WHEN 'Abril' THEN '04'
                                 WHEN 'Mayo' THEN '05'
                                 WHEN 'Junio' THEN '06'
                                 WHEN 'Julio' THEN '07'
                                 WHEN 'Agosto' THEN '08'
                                 WHEN 'Septiembre' THEN '09'
                                 WHEN 'Octubre' THEN '10'
                                 WHEN 'Noviembre' THEN '11'
                                 WHEN 'Diciembre' THEN '12'
                             END, '-01') as fecha, 'Sistema' as usuario_registro
                             FROM tbl_gastos 
                             WHERE id_condominio = :id_condominio2 
                             ORDER BY anio DESC, 
                             FIELD(mes, 'Diciembre','Noviembre','Octubre','Septiembre','Agosto','Julio','Junio','Mayo','Abril','Marzo','Febrero','Enero') DESC 
                             LIMIT 5)
                            ORDER BY fecha DESC LIMIT 8
                        ");
            $sentencia_movimientos->bindParam(":id_condominio", $idcondominio);
            $sentencia_movimientos->bindParam(":id_condominio2", $idcondominio);
            $sentencia_movimientos->execute();
            $movimientos = $sentencia_movimientos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($movimientos as $movimiento):
              $badge_class = $movimiento['tipo'] == 'pago' ? 'bg-success' : 'bg-danger';
              $icon = $movimiento['tipo'] == 'pago' ? 'fa-arrow-up' : 'fa-arrow-down';
            ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <span class="badge <?php echo $badge_class; ?> me-3">
                    <i class="fas <?php echo $icon; ?>"></i>
                  </span>
                  <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($movimiento['descripcion']); ?></h6>
                    <small class="text-muted">Por: <?php echo htmlspecialchars($movimiento['usuario_registro']); ?></small>
                  </div>
                </div>
                <div class="text-end">
                  <strong class="<?php echo $movimiento['tipo'] == 'pago' ? 'text-success' : 'text-danger'; ?>">
                    RD$ <?php echo number_format($movimiento['monto'], 2, '.', ','); ?>
                  </strong>
                  <br>
                  <small class="text-muted">
                    <?php echo date('d/m/Y', strtotime($movimiento['fecha'])); ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estado de Apartamentos -->
  <div class="row">
    <div class="col-12">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Estado de Apartamentos</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Apartamento</th>
                  <th>Condómino</th>
                  <th>Balance</th>
                  <th>Último Pago</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sentencia_aptos_detalle = $conexion->prepare("
                                    SELECT a.*, 
                                           (SELECT MAX(fecha_pago) FROM tbl_pagos p WHERE p.id_apto = a.id) as ultimo_pago
                                    FROM tbl_aptos a 
                                    WHERE a.id_condominio = :id_condominio 
                                    ORDER BY CAST(SUBSTRING_INDEX(a.apto, '-', -1) AS UNSIGNED)
                                ");
                $sentencia_aptos_detalle->bindParam(":id_condominio", $idcondominio);
                $sentencia_aptos_detalle->execute();
                $aptos_detalle = $sentencia_aptos_detalle->fetchAll(PDO::FETCH_ASSOC);

                foreach ($aptos_detalle as $apto):
                  $balance = floatval($apto['balance']);
                  $estado_class = $balance >= 0 ? 'success' : 'danger';
                  $estado_text = $balance >= 0 ? 'Al día' : 'Moroso';
                ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($apto['apto']); ?></strong></td>
                    <td><?php echo htmlspecialchars($apto['condominos']); ?></td>
                    <td class="font-weight-bold <?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                      RD$ <?php echo number_format(abs($balance), 2, '.', ','); ?>
                    </td>
                    <td>
                      <?php
                      if ($apto['ultimo_pago']) {
                        echo date('d/m/Y', strtotime($apto['ultimo_pago']));
                      } else {
                        echo '<span class="text-muted">Sin pagos</span>';
                      }
                      ?>
                    </td>
                    <td>
                      <span class="badge bg-<?php echo $estado_class; ?>">
                        <?php echo $estado_text; ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?php echo $url_base; ?>secciones/aptos/editar.php?txID=<?php echo $apto['id']; ?>"
                        class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> Ver
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts para los gráficos -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Ingresos vs Gastos
    const ingresosGastosCtx = document.getElementById('ingresosGastosChart').getContext('2d');

    // Datos de ejemplo - en producción estos vendrían de la base de datos
    const ingresosGastosChart = new Chart(ingresosGastosCtx, {
      type: 'line',
      data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
        datasets: [{
          label: 'Ingresos',
          data: [120000, 150000, 180000, 160000, 190000, 210000],
          borderColor: '#1cc88a',
          backgroundColor: 'rgba(28, 200, 138, 0.1)',
          fill: true,
          tension: 0.4
        }, {
          label: 'Gastos',
          data: [80000, 90000, 110000, 95000, 120000, 130000],
          borderColor: '#e74a3b',
          backgroundColor: 'rgba(231, 74, 59, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return 'RD$ ' + value.toLocaleString();
              }
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': RD$ ' + context.parsed.y.toLocaleString();
              }
            }
          }
        }
      }
    });

    // Gráfico de Distribución de Gastos
    const gastosCtx = document.getElementById('gastosChart').getContext('2d');
    const gastosChart = new Chart(gastosCtx, {
      type: 'doughnut',
      data: {
        labels: ['Nómina', 'Servicios', 'Materiales', 'Imprevistos', 'Cargos Bancarios'],
        datasets: [{
          data: [40, 25, 15, 12, 8],
          backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
          hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
          hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
      },
      options: {
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            display: false
          }
        }
      },
    });

    // Inicializar DataTable
    $('#dataTable').DataTable({
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
      },
      pageLength: 10,
      responsive: true
    });
  });
</script>

<?php include("templates/footer.php"); ?>