<?php
$thetitle = "Reporte de Movimientos";
include("../../templates/header.php");
include("../../bd.php");

// 1. OBTENER FILTROS
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');       // Último día del mes actual
$idcondominio = $_SESSION['idcondominio'];

// 2. CONSULTAR APARTAMENTOS
$stmt_aptos = $conexion->prepare("SELECT id, apto, condominos FROM tbl_aptos WHERE id_condominio = :id ORDER BY apto ASC");
$stmt_aptos->execute([':id' => $idcondominio]);
$apartamentos = $stmt_aptos->fetchAll(PDO::FETCH_ASSOC);

$reporte_data = [];
$totales_generales = [
    'cargos' => 0,
    'pagos' => 0,
    'balance_periodo' => 0
];

// 3. PROCESAR DATOS POR APARTAMENTO
foreach ($apartamentos as $apto) {
    $id_apto = $apto['id'];

    // A. CARGOS GENERADOS EN EL PERIODO (Lo que se les cobró)
    // Mantenimiento (Usamos fecha_actual o construimos fecha con mes/anio)
    $sql_mtto = "SELECT COALESCE(SUM(CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2))), 0) 
                 FROM tbl_tickets 
                 WHERE id_apto = :id AND fecha_actual BETWEEN :inicio AND :fin";

    // Gas (Usamos fecha_registro)
    $sql_gas = "SELECT COALESCE(SUM(total_gas + mora), 0) 
                FROM tbl_gas 
                WHERE id_apto = :id AND fecha_registro BETWEEN :inicio AND :fin";

    // Cuotas Extras (Usamos fecha_registro)
    $sql_cuotas = "SELECT COALESCE(SUM(monto + mora), 0) 
                   FROM tbl_cuotas_extras 
                   WHERE id_apto = :id AND fecha_registro BETWEEN :inicio AND :fin";

    $stmt = $conexion->prepare($sql_mtto);
    $stmt->execute([':id' => $id_apto, ':inicio' => $fecha_inicio, ':fin' => $fecha_fin . ' 23:59:59']);
    $cargo_mtto = $stmt->fetchColumn();
    $stmt = $conexion->prepare($sql_gas);
    $stmt->execute([':id' => $id_apto, ':inicio' => $fecha_inicio, ':fin' => $fecha_fin . ' 23:59:59']);
    $cargo_gas = $stmt->fetchColumn();
    $stmt = $conexion->prepare($sql_cuotas);
    $stmt->execute([':id' => $id_apto, ':inicio' => $fecha_inicio, ':fin' => $fecha_fin . ' 23:59:59']);
    $cargo_cuota = $stmt->fetchColumn();

    $total_cargos = $cargo_mtto + $cargo_gas + $cargo_cuota;

    // B. PAGOS RECIBIDOS EN EL PERIODO (Lo que pagaron)
    // Pagos de Propietarios
    $sql_pagos_prop = "SELECT COALESCE(SUM(monto), 0) FROM tbl_pagos WHERE id_apto = :id AND fecha_pago BETWEEN :inicio AND :fin";
    // Pagos de Inquilinos (hay que buscar el ID del inquilino asociado a este apto, simplificado sumamos si hay relación)
    // Nota: Esta consulta asume una subquery para vincular inquilinos al apto
    $sql_pagos_inq = "SELECT COALESCE(SUM(p.monto), 0) 
                      FROM tbl_pagos_inquilinos p 
                      JOIN tbl_inquilinos i ON p.id_inquilino = i.id 
                      WHERE i.id_apto = :id AND p.fecha_pago BETWEEN :inicio AND :fin";

    $stmt = $conexion->prepare($sql_pagos_prop);
    $stmt->execute([':id' => $id_apto, ':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
    $pago_prop = $stmt->fetchColumn();
    $stmt = $conexion->prepare($sql_pagos_inq);
    $stmt->execute([':id' => $id_apto, ':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
    $pago_inq = $stmt->fetchColumn();

    $total_pagos = $pago_prop + $pago_inq;

    // C. BALANCE DEL PERIODO (No es la deuda total histórica, solo el flujo de estas fechas)
    $balance_periodo = $total_pagos - $total_cargos;

    // Guardar en array
    $reporte_data[] = [
        'apto' => $apto['apto'],
        'nombre' => $apto['condominos'],
        'cargos' => $total_cargos,
        'pagos' => $total_pagos,
        'diferencia' => $balance_periodo
    ];

    // Acumular totales
    $totales_generales['cargos'] += $total_cargos;
    $totales_generales['pagos'] += $total_pagos;
    $totales_generales['balance_periodo'] += $balance_periodo;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mb-3 fw-bold text-dark"><i class="fas fa-chart-line text-primary"></i> Reporte de Movimientos</h1>

    <div class="card shadow-sm border-0 mb-4 bg-white rounded">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-secondary">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-secondary">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-filter"></i> Filtrar Datos</button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-success w-100 fw-bold" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Total Facturado (Deuda Generada)</h6>
                            <h2 class="fw-bold mb-0">RD$ <?php echo number_format($totales_generales['cargos'], 2); ?></h2>
                        </div>
                        <i class="fas fa-file-invoice-dollar fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Total Cobrado (Ingresos Reales)</h6>
                            <h2 class="fw-bold mb-0">RD$ <?php echo number_format($totales_generales['pagos'], 2); ?></h2>
                        </div>
                        <i class="fas fa-hand-holding-usd fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Efectividad de Cobro</h6>
                            <?php
                            $porcentaje = ($totales_generales['cargos'] > 0) ? ($totales_generales['pagos'] / $totales_generales['cargos']) * 100 : 0;
                            ?>
                            <h2 class="fw-bold mb-0"><?php echo number_format($porcentaje, 1); ?>%</h2>
                        </div>
                        <i class="fas fa-chart-pie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Comparativa Facturación vs Cobros</h6>
                </div>
                <div class="card-body">
                    <canvas id="movimientosChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark">Desglose por Apartamento</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Apto</th>
                                    <th>Propietario</th>
                                    <th class="text-end text-danger">Facturado (Deuda Generada)</th>
                                    <th class="text-end text-success">Pagado (Ingreso Real)</th>
                                    <th class="text-end">Balance Periodo</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_data as $row):
                                    $class_bal = $row['diferencia'] >= 0 ? 'text-success' : 'text-danger';
                                    $estado = $row['diferencia'] >= 0 ? '<span class="badge bg-success">Superávit</span>' : '<span class="badge bg-danger">Déficit</span>';
                                    if ($row['cargos'] == 0 && $row['pagos'] == 0) $estado = '<span class="badge bg-secondary">-</span>';
                                ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $row['apto']; ?></td>
                                        <td><?php echo $row['nombre']; ?></td>
                                        <td class="text-end fw-bold text-danger">RD$ <?php echo number_format($row['cargos'], 2); ?></td>
                                        <td class="text-end fw-bold text-success">RD$ <?php echo number_format($row['pagos'], 2); ?></td>
                                        <td class="text-end fw-bold <?php echo $class_bal; ?>">RD$ <?php echo number_format($row['diferencia'], 2); ?></td>
                                        <td class="text-center"><?php echo $estado; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">TOTALES DEL PERIODO:</td>
                                    <td class="text-end text-danger">RD$ <?php echo number_format($totales_generales['cargos'], 2); ?></td>
                                    <td class="text-end text-success">RD$ <?php echo number_format($totales_generales['pagos'], 2); ?></td>
                                    <td class="text-end">RD$ <?php echo number_format($totales_generales['balance_periodo'], 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Configuración del Gráfico
    const ctx = document.getElementById('movimientosChart').getContext('2d');
    const movimientosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($reporte_data, 'apto')); ?>,
            datasets: [{
                    label: 'Facturado (Deuda)',
                    data: <?php echo json_encode(array_column($reporte_data, 'cargos')); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pagado (Ingreso)',
                    data: <?php echo json_encode(array_column($reporte_data, 'pagos')); ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RD$ ' + value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php include("../../templates/footer.php"); ?>