<?php
ob_start(); // Iniciar almacenamiento en búfer para PDF
session_start();
include("../../bd.php");

// 1. OBTENER DATOS (Soporta GET y POST)
if (isset($_REQUEST['id_apto'])) {
    $id_apto = $_REQUEST['id_apto'];
} else {
    die("Error: Falta ID de apartamento");
}

// RECIBIR LA NOTA DEL MODAL
$nota_adicional = isset($_POST['nota_reporte']) ? trim($_POST['nota_reporte']) : "";

$idcondominio = $_SESSION['idcondominio'];

// Datos del Condominio y Apto
$sentencia = $conexion->prepare("
    SELECT a.*, c.nombre as nombre_condominio,
           c.mora as show_mora,
           c.gas as show_gas,
           c.cuota as show_cuota
    FROM tbl_aptos a 
    JOIN tbl_condominios c ON a.id_condominio = c.id 
    WHERE a.id = :id
");
$sentencia->bindParam(":id", $id_apto);
$sentencia->execute();
$data = $sentencia->fetch(PDO::FETCH_ASSOC);

// Variables de control para mostrar/ocultar columnas
$ver_mora  = ($data['show_mora'] === 'si');
$ver_gas   = ($data['show_gas'] === 'si');
$ver_cuota = ($data['show_cuota'] === 'si');

// 2. CONSULTA MAESTRA (TIMELINE HISTÓRICO)
$sql_timeline = "
    SELECT 
        t.mes, t.anio, t.fecha_actual,
        
        -- MANTENIMIENTO
        CAST(t.mantenimiento AS DECIMAL(10,2)) as mant_base,
        CAST(t.mora AS DECIMAL(10,2)) as mant_mora,
        
        -- GAS
        (SELECT COALESCE(SUM(total_gas), 0) FROM tbl_gas g 
         WHERE g.id_apto = t.id_apto AND g.mes = t.mes AND g.anio = t.anio) as gas_base,
         
        (SELECT COALESCE(SUM(mora), 0) FROM tbl_gas g 
         WHERE g.id_apto = t.id_apto AND g.mes = t.mes AND g.anio = t.anio) as gas_mora,
          
        -- CUOTAS
        (SELECT COALESCE(SUM(monto), 0) FROM tbl_cuotas_extras c 
         WHERE c.id_apto = t.id_apto AND c.mes = t.mes AND c.anio = t.anio) as cuota_base,
         
        (SELECT COALESCE(SUM(mora), 0) FROM tbl_cuotas_extras c 
         WHERE c.id_apto = t.id_apto AND c.mes = t.mes AND c.anio = t.anio) as cuota_mora,

        -- PAGOS
        (SELECT COALESCE(SUM(p.monto), 0) FROM tbl_pagos p 
         WHERE p.id_apto = t.id_apto AND p.mes_ingreso = t.mes AND p.anio_ingreso = t.anio) as pago_prop,

        (SELECT COALESCE(SUM(pi.monto), 0) FROM tbl_pagos_inquilinos pi 
         JOIN tbl_inquilinos i ON pi.id_inquilino = i.id
         WHERE i.id_apto = t.id_apto AND pi.mes_gas = t.mes AND pi.anio_gas = t.anio) as pago_inq

    FROM tbl_tickets t
    WHERE t.id_apto = :id
    ORDER BY t.fecha_actual ASC, t.anio ASC, t.id ASC
";

$stmt = $conexion->prepare($sql_timeline);
$stmt->bindParam(":id", $id_apto);
$stmt->execute();
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

$saldo_acumulado = 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta Detallado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .header-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .titulo-cyan {
            background-color: #e0e0e0;
            padding: 8px;
            font-weight: bold;
            font-size: 16px;
            border: 1px solid #000;
            text-transform: uppercase;
        }

        .subtitulo {
            font-weight: bold;
            margin-top: 5px;
            text-decoration: underline;
            font-size: 12px;
        }

        .tabla-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .tabla-info th {
            text-align: left;
            border: 1px solid #000;
            padding: 4px;
            background-color: #fff;
            width: 15%;
        }

        .tabla-info td {
            text-align: left;
            border: 1px solid #000;
            padding: 4px;
            background-color: #ffffffff;
            font-weight: bold;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabla-datos th {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            background-color: #e0e0e0;
        }

        .tabla-datos td {
            border: 1px solid #000;
            padding: 3px;
            text-align: right;
            font-size: 10px;
        }

        .col-mes {
            text-align: left !important;
            font-weight: bold;
            font-style: italic;
        }

        .col-pagos {
            font-weight: bold;
            color: #00008B;
        }

        .col-balance {
            font-weight: bold;
        }

        .txt-mora {
            color: #d9534f;
            font-size: 9px;
        }

        .bg-grupo {
            background-color: #f2f2f2;
        }

        .total-final-box {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            font-style: italic;
            text-decoration: underline;
        }

        /* ESTILO PARA LA NOTA */
        .nota-box {
            margin-top: 30px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            padding: 10px;
            font-size: 11px;
            text-align: left;
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="header-container">
        <div class="titulo-cyan"><?php echo strtoupper($data['nombre_condominio']); ?></div>
        <div style="font-weight: bold; margin-top: 5px; font-size: 12px;">RELACION DE CUENTAS POR COBRAR</div>
        <div class="subtitulo">ESTADO DE CUENTA DETALLADO</div>
    </div>

    <table class="tabla-info">
        <tr>
            <th style="width: 10%; background-color: #e0e0e0;">CONDOMINOS:</th>
            <td><?php echo $data['condominos']; ?></td>
            <th style="width: 10%; background-color: #e0e0e0;">APTO:</th>
            <td style="width: 10%;"><?php echo $data['apto']; ?></td>
        </tr>
    </table>

    <table class="tabla-datos">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">MESES</th>
                <th colspan="<?php echo $ver_mora ? '2' : '1'; ?>">MANTENIMIENTO</th>
                <?php if ($ver_cuota): ?> <th colspan="<?php echo $ver_mora ? '2' : '1'; ?>">CUOTA EXTRA</th> <?php endif; ?>
                <?php if ($ver_gas): ?> <th colspan="<?php echo $ver_mora ? '2' : '1'; ?>">GAS</th> <?php endif; ?>
                <th rowspan="2" style="width: 10%;">TOTAL<br>DEUDA</th>
                <th rowspan="2" style="width: 10%;">PAGOS</th>
                <th rowspan="2" style="width: 10%;">BALANCE</th>
            </tr>
            <tr>
                <th style="width: 8%;">Monto</th>
                <?php if ($ver_mora): ?> <th style="width: 6%;">Mora</th> <?php endif; ?>

                <?php if ($ver_cuota): ?>
                    <th style="width: 8%;">Monto</th>
                    <?php if ($ver_mora): ?> <th style="width: 6%;">Mora</th> <?php endif; ?>
                <?php endif; ?>

                <?php if ($ver_gas): ?>
                    <th style="width: 8%;">Monto</th>
                    <?php if ($ver_mora): ?> <th style="width: 6%;">Mora</th> <?php endif; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historial as $row):
                $m_base = floatval($row['mant_base']);
                $m_mora = floatval($row['mant_mora']);
                $c_base = floatval($row['cuota_base']);
                $c_mora = floatval($row['cuota_mora']);
                $g_base = floatval($row['gas_base']);
                $g_mora = floatval($row['gas_mora']);

                $total_deuda_mes = $m_base + $m_mora + $c_base + $c_mora + $g_base + $g_mora;
                $total_pagos_mes = floatval($row['pago_prop']) + floatval($row['pago_inq']);
                $saldo_acumulado = $saldo_acumulado + $total_deuda_mes - $total_pagos_mes;

                $mes_corto = substr(strtoupper($row['mes']), 0, 3);
                $anio_corto = substr($row['anio'], 2, 2);
                $fecha_display = "*$mes_corto. $anio_corto";
            ?>
                <tr>
                    <td class="col-mes"><?php echo $fecha_display; ?></td>
                    <td><?php echo number_format($m_base, 2); ?></td>
                    <?php if ($ver_mora): ?> <td class="txt-mora"><?php echo ($m_mora > 0) ? number_format($m_mora, 2) : '-'; ?></td> <?php endif; ?>

                    <?php if ($ver_cuota): ?>
                        <td><?php echo ($c_base > 0) ? number_format($c_base, 2) : '-'; ?></td>
                        <?php if ($ver_mora): ?> <td class="txt-mora"><?php echo ($c_mora > 0) ? number_format($c_mora, 2) : '-'; ?></td> <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($ver_gas): ?>
                        <td><?php echo ($g_base > 0) ? number_format($g_base, 2) : '-'; ?></td>
                        <?php if ($ver_mora): ?> <td class="txt-mora"><?php echo ($g_mora > 0) ? number_format($g_mora, 2) : '-'; ?></td> <?php endif; ?>
                    <?php endif; ?>

                    <td class="bg-grupo"><strong><?php echo number_format($total_deuda_mes, 2); ?></strong></td>
                    <td class="col-pagos"><?php echo ($total_pagos_mes > 0) ? number_format($total_pagos_mes, 2) : '-'; ?></td>
                    <td class="col-balance"><?php echo number_format($saldo_acumulado, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-final-box">
        BALANCE....... $<?php echo number_format($saldo_acumulado, 2); ?>
    </div>

    <?php if (!empty($nota_adicional)): ?>
        <div class="nota-box">
            <strong>NOTA / OBSERVACIONES:</strong><br>
            <?php echo nl2br(htmlspecialchars($nota_adicional)); ?>
        </div>
    <?php endif; ?>

</body>

</html>

<?php
$html = ob_get_clean();
require_once '../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();
$filename = "EstadoCuenta_" . $data['apto'] . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>