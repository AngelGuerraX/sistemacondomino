<?php
include("../../templates/header.php");
include("../../bd.php");

// 1. VALIDAR SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

// 2. CONFIGURAR FILTROS DE FECHA
// Por defecto: Desde el día 1 del mes actual hasta hoy
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// 3. CONSULTA MAESTRA (UNIÓN DE PROPIETARIOS E INQUILINOS)
// Usamos UNION ALL para combinar ambas tablas y ordenarlas por fecha
$sql = "
    SELECT * FROM (
        -- PAGOS PROPIETARIOS
        SELECT 
            p.id_pago as id,
            p.fecha_pago,
            p.monto,
            p.forma_pago,
            p.concepto,
            p.usuario_registro,
            'propietario' as tipo_pagador,
            a.apto,
            a.condominos as nombre_pagador,
            CONCAT(p.mes_ingreso, ' ', p.anio_ingreso) as periodo
        FROM tbl_pagos p
        INNER JOIN tbl_aptos a ON p.id_apto = a.id
        WHERE p.id_condominio = :id1 
        AND p.fecha_pago BETWEEN :fi1 AND :ff1

        UNION ALL

        -- PAGOS INQUILINOS
        SELECT 
            pi.id as id,
            pi.fecha_pago,
            pi.monto,
            pi.forma_pago,
            pi.concepto,
            pi.usuario_registro,
            'inquilino' as tipo_pagador,
            a.apto,
            i.nombre as nombre_pagador,
            CONCAT(pi.mes_gas, ' ', pi.anio_gas) as periodo
        FROM tbl_pagos_inquilinos pi
        INNER JOIN tbl_inquilinos i ON pi.id_inquilino = i.id
        INNER JOIN tbl_aptos a ON i.id_apto = a.id
        WHERE pi.id_condominio = :id2 
        AND pi.fecha_pago BETWEEN :fi2 AND :ff2
    ) as tabla_unificada
    ORDER BY fecha_pago DESC, id DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute([
    ':id1' => $id_condominio,
    ':fi1' => $fecha_inicio,
    ':ff1' => $fecha_fin,
    ':id2' => $id_condominio,
    ':fi2' => $fecha_inicio,
    ':ff2' => $fecha_fin
]);
$lista_pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. CALCULAR TOTALES EN PHP
$total_general = 0;
$total_efectivo = 0;
$total_transferencia = 0;
$total_cheque = 0;

foreach ($lista_pagos as $p) {
    $total_general += $p['monto'];
    if (stripos($p['forma_pago'], 'Efectivo') !== false) $total_efectivo += $p['monto'];
    elseif (stripos($p['forma_pago'], 'Transferencia') !== false) $total_transferencia += $p['monto'];
    elseif (stripos($p['forma_pago'], 'Cheque') !== false) $total_cheque += $p['monto'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Global de Pagos</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .bg-gradient-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }

        .card-total {
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .card-total:hover {
            transform: translateY(-5px);
        }

        .b-efectivo {
            border-color: #1cc88a;
        }

        .b-transf {
            border-color: #36b9cc;
        }

        .b-total {
            border-color: #4e73df;
        }

        .badge-prop {
            background-color: #4e73df;
        }

        .badge-inq {
            background-color: #f6c23e;
            color: #333;
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-gray-800">📊 Reporte Global de Ingresos</h2>
            <a href="index.php" class="btn btn-secondary">⬅ Volver al Panel</a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">📅 Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">📅 Fecha Fin:</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">🔍 Filtrar</button>
                    </div>
                    <div class="col-md-2">
                        <a href="reporte_pagos_global.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card card-total b-total shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Recaudado</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800">RD$ <?php echo number_format($total_general, 2); ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card card-total b-efectivo shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">En Efectivo</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">RD$ <?php echo number_format($total_efectivo, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card card-total b-transf shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Transferencias / Cheques</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">RD$ <?php echo number_format($total_transferencia + $total_cheque, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-gradient-primary">
                <h6 class="m-0 font-weight-bold text-white">Listado Detallado de Transacciones</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaPagos" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Apto</th>
                                <th>Pagador</th>
                                <th>Concepto / Detalle</th>
                                <th>Forma Pago</th>
                                <th>Usuario</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center">Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_pagos as $p):
                                $badge_clase = ($p['tipo_pagador'] == 'propietario') ? 'badge-prop' : 'badge-inq';
                                $icono = ($p['tipo_pagador'] == 'propietario') ? '🏠' : '👤';
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_pago'])); ?></td>
                                    <td class="fw-bold"><?php echo $p['apto']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_clase; ?>"><?php echo $icono; ?> <?php echo ucfirst($p['tipo_pagador']); ?></span>
                                        <br><small><?php echo $p['nombre_pagador']; ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block"><?php echo $p['periodo']; ?></small>
                                        <?php echo $p['concepto']; ?>
                                    </td>
                                    <td><?php echo $p['forma_pago']; ?></td>
                                    <td class="small text-muted"><?php echo $p['usuario_registro']; ?></td>
                                    <td class="text-end fw-bold text-dark">RD$ <?php echo number_format($p['monto'], 2); ?></td>
                                    <td class="text-center">
                                        <a href="ver_recibo.php?id_pago=<?php echo $p['id']; ?>&txID=0&tipo=<?php echo $p['tipo_pagador']; ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-info text-white"
                                            title="Ver Recibo">
                                            🖨️
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaPagos').DataTable({
                "order": [
                    [0, "desc"]
                ], // Ordenar por fecha descendente
                "pageLength": 25,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                }
            });
        });
    </script>

</body>

</html>

<?php include("../../templates/footer.php"); ?>