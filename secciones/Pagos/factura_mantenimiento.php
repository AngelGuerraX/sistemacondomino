<?php
// 1. Siempre iniciar un buffer nuevo para este archivo
ob_start();

// 2. Controlar la sesión para que no de error si ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Usar include_once para evitar error de re-declaración
include_once("../../bd.php");

// ==========================================
// ... (El resto de tu código de funciones y consultas sigue igual) ...

// ==========================================
// 1. CONFIGURACIÓN Y FUNCIONES AUXILIARES
// ==========================================

$id_apto = $_GET['id_apto'] ?? '';
$mes = $_GET['mes'] ?? '';
$anio = $_GET['anio'] ?? '';
$idcondominio = $_SESSION['idcondominio'];
// ==========================================
// FUNCIONES PARA CONVERTIR NÚMEROS A LETRAS
// ==========================================

// 1. Primero definimos la función auxiliar (PROTEGIDA)
if (!function_exists('convertirGrupo')) {
    function convertirGrupo($n, $unidades, $decenas, $diez_veinte, $centenas)
    {
        $output = '';
        if ($n == 100) return 'cien ';
        $c = floor($n / 100);
        $n = $n % 100;
        if ($c > 0) $output .= $centenas[$c] . ' ';
        if ($n >= 10 && $n <= 19) {
            $output .= $diez_veinte[$n - 10] . ' ';
            return $output;
        }
        $d = floor($n / 10);
        $u = $n % 10;
        if ($d > 0) {
            if ($d == 2 && $u > 0) $output .= 'veinti';
            else {
                $output .= $decenas[$d];
                if ($u > 0) $output .= ' y ';
            }
        }
        if ($d != 2) {
            if ($u > 0) $output .= $unidades[$u] . ' ';
        } elseif ($d == 2 && $u > 0) {
            $output .= $unidades[$u] . ' ';
        }
        return $output;
    }
}

// 2. Luego definimos la función principal (PROTEGIDA)
if (!function_exists('numeroALetras')) {
    function numeroALetras($amount)
    {
        $amount = number_format($amount, 2, '.', '');
        $split = explode('.', $amount);
        $entero = (int)$split[0];
        $decimal = $split[1];

        $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas  = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $diez_veinte = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciseis', 'diecisiete', 'dieciocho', 'diecinueve'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        $output = '';
        $miles = floor($entero / 1000);
        $resto = $entero % 1000;

        if ($miles > 0) {
            if ($miles == 1) $output .= 'mil ';
            // Llamamos a la función auxiliar que ya está definida fuera
            else $output .= convertirGrupo($miles, $unidades, $decenas, $diez_veinte, $centenas) . 'mil ';
        }
        if ($resto > 0 || $entero == 0) {
            $output .= convertirGrupo($resto, $unidades, $decenas, $diez_veinte, $centenas);
        }
        return ucfirst(trim($output)) . " pesos con " . $decimal . "/100";
    }
}
// ==========================================
// 2. OBTENCIÓN DE DATOS
// ==========================================

// Datos del Apartamento
$sentencia_apto = $conexion->prepare("
    SELECT a.*, c.nombre as condominio_nombre, c.ubicacion 
    FROM tbl_aptos a 
    LEFT JOIN tbl_condominios c ON a.id_condominio = c.id 
    WHERE a.id = :id_apto AND a.id_condominio = :id_condominio
");
$sentencia_apto->execute([':id_apto' => $id_apto, ':id_condominio' => $idcondominio]);
$apto = $sentencia_apto->fetch(PDO::FETCH_ASSOC);

if (!$apto) die("Apartamento no encontrado");

// Datos del Ticket
$sentencia_ticket = $conexion->prepare("
    SELECT * FROM tbl_tickets 
    WHERE id_apto = :id_apto AND mes = :mes AND anio = :anio AND id_condominio = :id_condominio
");
$sentencia_ticket->execute([':id_apto' => $id_apto, ':mes' => $mes, ':anio' => $anio, ':id_condominio' => $idcondominio]);
$ticket = $sentencia_ticket->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 3. LÓGICA DE SECUENCIA Y MONTOS
// ==========================================

$numero_factura_mostrar = "";
$monto_mantenimiento = 0;
$monto_mora = 0;

if ($ticket) {
    // Si el ticket ya existe, usamos su ID real
    $numero_factura_mostrar = str_pad($ticket['id'], 6, "0", STR_PAD_LEFT);
    $monto_mantenimiento = $ticket['mantenimiento'];
    $monto_mora = $ticket['mora'];
} else {
    // Si NO existe, buscamos el último ID y le sumamos 1 para proyectar la secuencia
    $stmt_max = $conexion->prepare("SELECT MAX(id) as ultimo FROM tbl_tickets");
    $stmt_max->execute();
    $ultimo = $stmt_max->fetch(PDO::FETCH_ASSOC);
    $siguiente = ($ultimo['ultimo']) ? $ultimo['ultimo'] + 1 : 1;

    $numero_factura_mostrar = str_pad($siguiente, 6, "0", STR_PAD_LEFT);

    // Valores por defecto del apto
    $monto_mantenimiento = $apto['mantenimiento'];
    $monto_mora = 0;
}

$monto_total = floatval($monto_mantenimiento) + floatval($monto_mora);
$monto_letras = numeroALetras($monto_total);

// Nombre del archivo PDF
$nombre_archivo = "Factura_" . $numero_factura_mostrar . "_" . $apto['apto'] . ".pdf";
// AL INICIO DEL PHP, ANTES DEL HTML
setlocale(LC_TIME, 'es_ES.UTF-8', 'esp'); // Configurar idioma español
$fecha_actual_texto = strftime("%d de %B de %Y");
// Si strftime no funciona en tu servidor (PHP 8.1+), usa esto:
$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$fecha_actual_texto = date('j') . " de " . $meses_es[date('n') - 1] . " de " . date('Y');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura Mantenimiento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            color: #000;
        }

        .container {
            width: 100%;
            border: 2px solid #000;
        }

        /* CABECERA */
        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .condominio {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .direccion {
            font-size: 10px;
            margin-bottom: 10px;
        }

        /* CAJA DEL TÍTULO TIPO EXCEL */
        .titulo-box {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* INFORMACIÓN DE FECHA Y NÚMERO */
        .info-top {
            width: 100%;
        }

        .info-top td {
            vertical-align: top;
        }

        /* ESTILOS DE FILAS DE DATOS (Label - Valor subrayado) */
        .data-row {
            width: 100%;
            margin-bottom: 5px;
            border-collapse: collapse;
        }

        .data-label {
            width: 130px;
            vertical-align: bottom;
            font-style: italic;
            text-decoration: underline;

        }

        .data-value {
            vertical-align: bottom;
            padding-left: 8px;
        }

        /* CHECKBOXES SIMULADOS */
        .checkbox-container {
            font-size: 14px;
            padding-top: 2px;
        }

        .chk {
            display: inline-block;
            margin-right: 10px;
        }

        /* TABLA PRINCIPAL TIPO EXCEL */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-table th {
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            border: 2px solid #000;
        }

        .main-table td {
            text-align: center;
            font-size: 12px;
            border: 2px solid #000;
        }

        /* FILA DE TOTALES */
        .total-row td {
            font-weight: bold;
        }

        /* PIE DE PÁGINA / FIRMA */
        .footer {
            margin-top: 60px;
            text-align: center;
        }


        .print-date {
            font-size: 9px;
            margin-top: 5px;
            color: #555;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header">
            <div class="condominio">CONDOMINIO <?php echo strtoupper($apto['condominio_nombre'] ?? 'Seleccione un condominio'); ?></div>
            <div class="direccion"><?php echo $apto['ubicacion'] ?? 'Dirección no especificada'; ?></div>
        </div>

        <div class="titulo-box">
            FACTURA - MANTENIMIENTO
        </div>
        <table class="info-top">
            <tr>
                <td align="left"></td>
                <td align="right" style="font-size: 14px;">
                    <?php echo $fecha_actual_texto; ?>
                </td>
            </tr>
        </table>

        <table class="data-row">
            <tr>
                <td class="data-label">Datos del Cliente:</td>
                <td class="data-value"><strong><?php echo strtoupper($apto['condominos']); ?></strong></td>
            </tr>
        </table>

        <table class="data-row">
            <tr>
                <td class="data-label">Suma Recibida:</td>
                <td class="data-value">
                    <?php echo $monto_letras; ?> (RD$ <?php echo number_format($monto_total, 2); ?>)
                </td>
            </tr>
        </table>

        <table class="data-row">
            <tr>
                <td class="data-label">Pago Realizado en:</td>
                <td class="data-value checkbox-container">
                    <span class="chk">( ) Efectivo</span>
                    <span class="chk">( ) Cheque No.</span>
                    <span class="chk">( ) Depósito</span>
                    <span class="chk">( ) Transf.</span>
                </td>
            </tr>
        </table>

        <table class="data-row">
            <tr>
                <td class="data-label">Por Concepto de:</td>
                <td class="data-value">
                    Pago de Mantenimiento, Apto. <?php echo $apto['apto']; ?>, correspondiente al mes de <?php echo $mes . " " . $anio; ?>.
                </td>
            </tr>
        </table>

        <table class="main-table">
            <thead>
                <tr>
                    <th width="33%">FACTURA</th>
                    <th width="33%">MONTO</th>
                    <th width="34%">APLICADO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>No. <?php echo $numero_factura_mostrar; ?></td>
                    <td>RD$ <?php echo number_format($monto_total, 2); ?></td>
                    <td>RD$ <?php echo number_format($monto_total, 2); ?></td>
                </tr>

                <tr class="total-row">
                    <td style="border: 0px;"></td>
                    <td style="border: 0px; text-align: right;">Total a Pagar...</td>
                    <td>RD$ <?php echo number_format($monto_total, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <br>
    </div>

</body>

</html>

<?php
// CÓDIGO NUEVO (SOLUCIÓN)
$html = ob_get_clean();

// Si definimos que es modo envío, SOLO devolvemos el HTML y paramos.
if (isset($modo_envio) && $modo_envio === true) {
    echo $html;
    return; // Detenemos la ejecución aquí para que no genere el PDF
}

// Si NO es modo envío (es decir, el usuario quiere ver el PDF en el navegador):
require_once '../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream($nombre_archivo, array("Attachment" => false));
?>