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

// --- FECHA ACTUAL ---
$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$fecha_actual_texto = date('j') . " de " . $meses_es[date('n') - 1] . " de " . date('Y');
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

// Datos del Gas
$sentencia_gas = $conexion->prepare("
    SELECT * FROM tbl_gas 
    WHERE id_apto = :id_apto AND mes = :mes AND anio = :anio AND id_condominio = :id_condominio
    ORDER BY fecha_registro DESC LIMIT 1
");
$sentencia_gas->execute([':id_apto' => $id_apto, ':mes' => $mes, ':anio' => $anio, ':id_condominio' => $idcondominio]);
$gas = $sentencia_gas->fetch(PDO::FETCH_ASSOC);

// Valores por defecto
if (!$gas) {
    $gas = [
        'lectura_anterior' => 0,
        'lectura_actual' => 0,
        'consumo_galones' => 0,
        'consumo_m3' => 0,
        'precio_galon' => 0,
        'total_gas' => 0,
        'mora' => 0
    ];
}

$monto_total_gas = floatval($gas['total_gas']) + floatval($gas['mora']);
$monto_letras = numeroALetras($monto_total_gas);
$nombre_archivo = "Factura_Gas_" . $apto['apto'] . "_" . $mes . ".pdf";

// Fechas del periodo
$fecha_obj = DateTime::createFromFormat('!m', array_search($mes, ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"]) + 1);
$fecha_obj->setDate($anio, $fecha_obj->format('m'), 1);
$inicio_mes = $fecha_obj->format('01/m/Y');
$fin_mes = $fecha_obj->format('t/m/Y');

// Determinar si hay mora para mostrar
$tiene_mora = floatval($gas['mora']) > 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura Gas</title>
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
            box-sizing: border-box;
        }

        /* CABECERA */
        .header {
            text-align: center;
        }

        .condominio {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .direccion {
            font-size: 10px;
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

        .data-row {
            width: 100%;
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
            min-height: 18px;
            display: inline-block;
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

        /* TABLA DE FECHAS DEL PERIODO */
        .dates-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dates-table td {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        /* TABLA PRINCIPAL TIPO EXCEL */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        table tbody {
            border-collapse: collapse;
        }

        .main-table th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            border: 2px solid #000;
        }

        .main-table td {
            text-align: center;
            font-size: 12px;
            border: 2px solid #000;
        }

        /* FILA DE TOTALES */
        .total-row {
            font-weight: bold;
            border-collapse: collapse;
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
            FACTURA - GAS
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
                    <?php echo $monto_letras; ?> (RD$ <?php echo number_format($monto_total_gas, 2); ?>)
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
                </td>
            </tr>
        </table>

        <table class="data-row">
            <tr>
                <td class="data-label">Por Concepto de:</td>
                <td class="data-value">
                    Pago del Gas, Apto. <?php echo $apto['apto']; ?>.
                </td>
            </tr>
        </table>

        <br>
        <table class="main-table">
            <thead>
                <tr>
                    <td><strong><?php echo $inicio_mes; ?></strong></td>
                    <td><strong><?php echo $fin_mes; ?></strong></td>
                    <td colspan="<?php echo $tiene_mora ? '8' : '7'; ?>" style="border: none;"></td>
                </tr>
                <tr>
                    <th>Lectura Final</th>
                    <th>Lectura Actual</th>
                    <th>Cons. Galones</th>
                    <th>M3</th>
                    <th>Precio x Galón</th>
                    <th>Total Gas</th>
                    <?php if ($tiene_mora): ?>
                        <th class="mora-col">Mora</th>
                        <th class="mora-col">Total Ingreso</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo number_format($gas['lectura_anterior'], 3); ?></td>
                    <td><?php echo number_format($gas['lectura_actual'], 3); ?></td>
                    <td><?php echo number_format($gas['consumo_galones'], 3); ?></td>
                    <td><?php echo number_format($gas['consumo_m3'], 3); ?></td>
                    <td>$<?php echo number_format($gas['precio_galon'], 2); ?></td>
                    <td>$<?php echo number_format($gas['total_gas'], 2); ?></td>
                    <?php if ($tiene_mora): ?>
                        <td class="mora-col">$<?php echo number_format($gas['mora'], 2); ?></td>
                        <td class="mora-col">$ <?php echo number_format($monto_total_gas, 2); ?></td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <td colspan="<?php echo $tiene_mora ? '6' : '4'; ?>" style="border: none;"></td>
                    <td style="border: none; text-align: right; font-weight: bold;">Total a pagar.....</td>
                    <td <?php echo $tiene_mora ? 'colspan="1"' : ''; ?>>RD$ <?php echo number_format($monto_total_gas, 2); ?></td>

            </tbody>
        </table>

        <?php if (!$gas || $gas['total_gas'] == 0): ?>
            <div style="text-align: center; color: #666; margin-top: 20px; font-style: italic;">
                * No se encontraron lecturas registradas para este periodo.
            </div>
        <?php endif; ?>
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