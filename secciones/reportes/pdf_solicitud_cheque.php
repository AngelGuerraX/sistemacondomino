<?php
ob_start();
session_start();
include("../../bd.php");

// =============================================
// OBTENER PARÁMETROS Y CONFIGURACIÓN
// =============================================

$idcondominio = $_SESSION['idcondominio'];

// Configurar meses en español
$meses_espanol = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
];

// Valores actuales del sistema (por defecto)
$mes_actual_nombre = $meses_espanol[date('n') - 1];
$anio_actual = date('Y');
$quincena_actual = (date('j') <= 15) ? '15' : '30';

// Obtener parámetros de la URL o usar valores actuales
$mes = $_GET['mes'] ?? $mes_actual_nombre;
$anio = $_GET['anio'] ?? $anio_actual;
$quincena = $_GET['quincena'] ?? $quincena_actual;

// Convertir nombre de mes a número para consultas
$mes_numero = array_search($mes, $meses_espanol) + 1;

// Validar parámetros
if (!in_array($mes, $meses_espanol)) {
    $mes = $mes_actual_nombre;
    $mes_numero = date('n');
}

if (!is_numeric($anio) || $anio < 2020 || $anio > 2030) {
    $anio = $anio_actual;
}

if (!in_array($quincena, ['15', '30'])) {
    $quincena = $quincena_actual;
}

// =============================================
// OBTENER DATOS DEL CONDOMINIO
// =============================================

$sentencia_condominio = $conexion->prepare("SELECT nombre FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$condominio = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);
$nombre_condominio = $condominio['nombre'] ?? 'CONDOMINIO';

// =============================================
// OBTENER NÓMINA DEL PERÍODO (CORREGIDO)
// =============================================

$sentencia_nomina = $conexion->prepare("SELECT n.*, e.nombres, e.apellidos, e.cargo 
                                       FROM tbl_nomina n
                                       INNER JOIN tbl_empleados e ON n.id_empleado = e.id
                                       WHERE n.id_condominio = :idcondominio 
                                       AND n.mes = :mes 
                                       AND n.anio = :anio
                                       AND n.quincena = :quincena
                                       ORDER BY e.nombres, e.apellidos");
$sentencia_nomina->bindParam(":idcondominio", $idcondominio);
$sentencia_nomina->bindParam(":mes", $mes);
$sentencia_nomina->bindParam(":anio", $anio);
$sentencia_nomina->bindParam(":quincena", $quincena);
$sentencia_nomina->execute();
$nomina_empleados = $sentencia_nomina->fetchAll(PDO::FETCH_ASSOC);

// =============================================
// OBTENER SOLICITUDES DEL PERÍODO
// =============================================

$sentencia_solicitudes = $conexion->prepare("SELECT * FROM tbl_solicitudes_cheques 
                                            WHERE id_condominio = :idcondominio 
                                            AND quincena_solicitud = :quincena
                                            AND MONTH(fecha_solicitud) = :mes 
                                            AND YEAR(fecha_solicitud) = :anio
                                            ORDER BY fecha_solicitud, id");
$sentencia_solicitudes->bindParam(":idcondominio", $idcondominio);
$sentencia_solicitudes->bindParam(":quincena", $quincena);
$sentencia_solicitudes->bindParam(":mes", $mes_numero);
$sentencia_solicitudes->bindParam(":anio", $anio);
$sentencia_solicitudes->execute();
$solicitudes = $sentencia_solicitudes->fetchAll(PDO::FETCH_ASSOC);

// =============================================
// OBTENER DETALLES DE CADA SOLICITUD
// =============================================

$solicitudes_completas = [];
$total_solicitudes = 0;

foreach ($solicitudes as $solicitud) {
    $sentencia_detalles = $conexion->prepare("SELECT d.*, g.tipo_gasto, g.detalles as descripcion_original
                                             FROM tbl_detalle_solicitud_cheques d
                                             INNER JOIN tbl_gastos g ON d.id_gasto = g.id
                                             WHERE d.id_solicitud = :id_solicitud
                                             ORDER BY d.id");
    $sentencia_detalles->bindParam(":id_solicitud", $solicitud['id']);
    $sentencia_detalles->execute();
    $detalles = $sentencia_detalles->fetchAll(PDO::FETCH_ASSOC);

    $detalles_procesados = [];
    $total_solicitud = 0;

    foreach ($detalles as $detalle) {
        $detalle['descripcion_completa'] = $detalle['detalles'] ?: $detalle['descripcion_original'];
        $detalles_procesados[] = $detalle;
        $total_solicitud += floatval($detalle['monto']);
    }

    $solicitudes_completas[] = [
        'solicitud' => $solicitud,
        'detalles' => $detalles_procesados,
        'total_solicitud' => $total_solicitud
    ];

    $total_solicitudes += $total_solicitud;
}

// =============================================
// CALCULAR TOTALES
// =============================================

$total_nomina = 0;
foreach ($nomina_empleados as $empleado) {
    $total_nomina += floatval($empleado['total_quincena']);
}

$total_general = $total_nomina + $total_solicitudes;

// Variable para numeración continua
$contador_global = 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Solicitud de Cheques</title>
    <style>
        * {
            font-family: Arial, sans-serif;
        }

        .con1 {
            text-transform: uppercase;
            text-align: center;
            font-size: 17px;
            font-weight: 600;
            font-family: Arial, sans-serif;
        }

        .con1p {
            margin: -2px;
            font-family: Arial, sans-serif;
        }

        .con2p {
            margin: 0px;
            background-color: lightgray;
            font-family: Arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            text-align: left;
            margin-bottom: 20px;
        }

        th,
        td {
            font-size: 14px;
            padding-left: 8px;
            padding-right: 8px;
            white-space: nowrap;
            border: 1px solid #000000ff;
        }

        th {
            background-color: #ffffffff;
        }

        .text-right {
            text-align: right;
        }

        .solicitud-header {
            background-color: #ffffffff;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
        }

        .solicitud-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .solicitud-numero {
            font-weight: bold;
        }

        .solicitud-total {
            font-weight: bold;
            color: #006600;
        }

        .numero-col {
            width: 30px;
            text-align: center;
        }

        .descripcion-col {
            width: 70%;
        }

        .monto-col {
            width: 20%;
            text-align: right;
        }

        .total-fila {
            background-color: #ffffffff;
            font-weight: bold;
            border-top: 2px solid #333;
        }

        .total-general {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #333;
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .fecha-generacion {
            text-align: right;
            font-size: 9px;
            color: #666;
            margin-bottom: 10px;
        }

        .nomina-header {
            background-color: #ffffffff;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="con1">
        <p class="con2p">Condominio <?php echo $nombre_condominio; ?></p>
        <p class="con1p">SOLICITUD DE CHEQUES</p>
        <p class="con1p"><?php echo $quincena . ' de ' . $mes . ' del ' . $anio; ?></p>
    </div>

    <br><br>

    <?php if (empty($nomina_empleados) && empty($solicitudes_completas)): ?>
        <div class="no-data">
            No hay nómina ni solicitudes de cheques para el período seleccionado.
        </div>
    <?php else: ?>
        <!-- SECCIÓN NÓMINA -->
        <?php if (!empty($nomina_empleados)): ?>
            <table>
                <thead>
                    <tr>
                        <th class="numero-col">No.</th>
                        <th align="left" class="descripcion-col">Nomina Quincenal</th>
                        <th align="left" class="monto-col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nomina_empleados as $empleado):
                        $contador_global++;
                        $nombre_completo = $empleado['nombres'] . ' ' . $empleado['apellidos'];
                    ?>
                        <tr>
                            <td class="numero-col"><?php echo $contador_global; ?></td>
                            <td class="descripcion-col"><?php echo $nombre_completo; ?></td>
                            <td class="monto-col text-right">RD$ <?php echo number_format($empleado['total_quincena'], 2, '.', ','); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-fila">
                        <td colspan="2" class="text-right">Total</td>
                        <td class="monto-col text-right">RD$ <?php echo number_format($total_nomina, 2, '.', ','); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- SECCIÓN SOLICITUDES DE CHEQUES -->
        <?php foreach ($solicitudes_completas as $solicitud_completa):
            $solicitud = $solicitud_completa['solicitud'];
            $detalles = $solicitud_completa['detalles'];
            $total_solicitud = $solicitud_completa['total_solicitud'];
        ?>

            <table>
                <thead>
                    <tr>
                        <th class="numero-col">No.</th>
                        <th align="left" class="descripcion-col">Descripción</th>
                        <th align="left" class="monto-col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle):
                        $contador_global++;
                    ?>
                        <tr>
                            <td class="numero-col"><?php echo $contador_global; ?></td>
                            <td class="descripcion-col"><?php echo $detalle['descripcion_completa']; ?></td>
                            <td class="monto-col text-right">RD$ <?php echo number_format($detalle['monto'], 2, '.', ','); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-fila">
                        <td colspan="2" class="text-right">Total</td>
                        <td class="monto-col text-right">RD$ <?php echo number_format($total_solicitud, 2, '.', ','); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; ?>

    <?php endif; ?>
    <br><br>

    <div style="width:60%; margin:30px auto 10px; text-align:center;">
        <div style="border-top:1px solid #000; height:1px; margin-bottom:8px;"></div>
        <div style="font-size:12px; color:#000;">Autorizado por</div>
    </div>

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
$dompdf->setPaper('letter');
$dompdf->render();

$filename = "solicitud_cheques_" . $nombre_condominio . "_" . $mes . "_" . $anio . "_Q" . $quincena . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>