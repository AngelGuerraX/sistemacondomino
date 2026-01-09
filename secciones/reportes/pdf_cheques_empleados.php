<?php
ob_start();
session_start();
include("../../bd.php");

// =============================================
// OBTENER PARÁMETROS Y CONFIGURACIÓN
// =============================================

$idcondominio = $_SESSION['idcondominio'];

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

$mes_actual_nombre = $meses_espanol[date('n') - 1];
$anio_actual = date('Y');
$quincena_actual = (date('j') <= 15) ? '15' : '30';

$mes = $_GET['mes'] ?? $mes_actual_nombre;
$anio = $_GET['anio'] ?? $anio_actual;
$quincena = $_GET['quincena'] ?? $quincena_actual;

// =============================================
// OBTENER DATOS DEL CONDOMINIO
// =============================================

$sentencia_condominio = $conexion->prepare("SELECT nombre FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$condominio = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);
$nombre_condominio = $condominio['nombre'] ?? 'CONDOMINIO';

// =============================================
// OBTENER NÓMINA DEL PERÍODO
// =============================================

$sentencia_nomina = $conexion->prepare("SELECT n.*, e.nombres, e.apellidos, e.cedula_pasaporte, e.cargo 
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
// FUNCIÓN PARA CONVERTIR NÚMERO A LETRAS
// =============================================

function numeroALetras($numero)
{
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];

    $partes = explode('.', number_format($numero, 2, '.', ''));
    $entero = intval($partes[0]);
    $decimal = isset($partes[1]) ? intval($partes[1]) : 0;

    if ($entero == 0) {
        $texto = 'CERO';
    } else {
        $texto = '';

        if ($entero >= 1000) {
            $miles = floor($entero / 1000);
            $texto .= $unidades[$miles] . ' MIL ';
            $entero %= 1000;
        }

        if ($entero >= 100) {
            $centenas = floor($entero / 100);
            if ($centenas == 1) {
                $texto .= 'CIEN ';
            } else {
                $texto .= $unidades[$centenas] . 'CIENTOS ';
            }
            $entero %= 100;
        }

        if ($entero >= 10 && $entero <= 19) {
            $texto .= $especiales[$entero - 10] . ' ';
        } else {
            if ($entero >= 20) {
                $decena = floor($entero / 10);
                $texto .= $decenas[$decena];
                $entero %= 10;
                if ($entero > 0) {
                    $texto .= ' Y ';
                }
            }
            if ($entero > 0) {
                $texto .= $unidades[$entero] . ' ';
            }
        }
    }

    $texto = trim($texto) . ' PESOS DOMINICANOS';

    if ($decimal > 0) {
        $texto .= ' CON ' . str_pad($decimal, 2, '0', STR_PAD_LEFT) . '/100';
    }

    return $texto;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cheques de Nómina - <?php echo $nombre_condominio; ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
        }

        .pagina {
            page-break-after: always;
        }

        .contenedor {
            width: 100%;
            border: 2px solid #000;
            padding: 12px;
            margin-bottom: 25px;
            height: 250px;
            position: relative;
        }

        .titulo {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .fila {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .fila label {
            font-weight: bold;
        }

        .firmas {
            display: flex;
            justify-content: space-between;
            position: absolute;
            bottom: 15px;
            width: 90%;
            left: 5%;
            text-align: center;
        }

        .firmas div {
            width: 45%;
        }

        .firmas hr {
            border: none;
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <?php if (empty($nomina_empleados)): ?>
        <p>No hay empleados en nómina para el período seleccionado.</p>
    <?php else: ?>
        <?php
        $contador = 0;
        $por_pagina = 3;
        $total = count($nomina_empleados);

        for ($i = 0; $i < $total; $i++):
            $emp = $nomina_empleados[$i];
            $contador++;

            $nombre_completo = $emp['nombres'] . ' ' . $emp['apellidos'];
            $monto = floatval($emp['total_quincena']);
            $fecha = date('d/m/Y');
        ?>

            <?php if ($contador % $por_pagina == 1): ?>
                <div class="pagina">
                <?php endif; ?>

                <div class="contenedor">
                    <div class="titulo">CHEQUE DE NÓMINA - <?php echo strtoupper($nombre_condominio); ?></div>

                    <div class="fila"><label>FECHA:</label> <?php echo $fecha; ?></div>
                    <div class="fila"><label>NO. IDENTIDAD:</label> <?php echo $emp['cedula_pasaporte']; ?></div>
                    <div class="fila"><label>EMPLEADO:</label> <?php echo $nombre_completo; ?></div>
                    <div class="fila"><label>CARGO:</label> <?php echo $emp['cargo']; ?></div>
                    <div class="fila"><label>CONCEPTO:</label> <?php echo ($quincena == '15' ? '1-15' : '16-30') . " de $mes $anio"; ?></div>
                    <div class="fila"><label>MONTO:</label> RD$ <?php echo number_format($monto, 2); ?></div>

                    <div class="firmas">
                        <div>
                            <hr>
                            <p>Recibido por Aprobado por</p>
                        </div>
                    </div>
                </div>
                <?php if ($contador % $por_pagina == 0 || $i == $total - 1): ?>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    <?php endif; ?>

    <style>
        .container1 {
            padding: 3px;
            border-color: black;
            border: 2px solid black;
            height: auto;
            width: 740px;
        }

        .columnas {
            border: 2px solid black;
            width: auto;
            height: auto;
            padding: 10px;
        }

        .columna1 {
            border: 1px solid black;
            height: 50px;
        }
    </style>
    <div class="container1">
        <div class="columna1">
            <center>
                <h5>DETALLES DE PAGO</h5>
            </center>
        </div>
        <div class="columnas">
            <form action="" method="post" enctype="multipart/form-data">

                <div style="font-size: 14px; margin-top: 3px;">
                    <label for="nombre" class="">FECHA:</label> <br>
                    <input type="text" style="border: 1px solid black; margin-top: 5px; width: 100px; " value="<?php echo date('d/m/Y'); ?>">
                </div>

                <div style="font-size: 14px; margin-top: 3px; margin-left: 550px; margin-top: -100px;">
                    <label for="nombre" class="">NO. IDENTIDAD:</label> <br>
                    <input type="text" style="border: 1px solid black; margin-top: 5px; width: 100px; " value="<?php echo $emp['cedula_pasaporte']; ?>">
                </div>

                <div style="font-size: 14px; margin-top: 3px;">
                    <label for="nombre" class="">EMPLEADO:</label> <br>
                    <input type="text" style="border: 1px solid black; margin-top: 5px; width: 700px; " value="<?php echo $nombre_completo; ?>">
                </div>
                <div style="font-size: 14px; margin-top: 3px;">
                    <label for="nombre" class="">CONCEPTO:</label><br>
                    <input type="text" style="border: 1px solid black; margin-top: 5px; width: 700px;" value="<?php echo ($quincena == '15' ? '1-15' : '16-30') . " de $mes $anio"; ?>">
                </div>
                <div style="font-size: 14px; margin-top: 3px;">
                    <label>MONTO:</label><br>
                    <input type="text" style="border: 1px solid black; width: 700px;" value="RD$ <?php echo number_format($monto, 2); ?>">
                </div>
                <br> <br>
                <div style="font-size: 14px;">
                    <label style="font-size: 15px; margin-left: 90px;">_______________________</label> <label style="font-size: 15px; margin-left: 40px;">_________________________</label>
                    <br>
                    <label style="font-size: 15px; margin-left: 140px;">Aprobado por</label> <label style="font-size: 15px; margin-left: 145px;">Recibido por</label>
                </div>
            </form>
        </div>
    </div>


</body>

</html>

<?php
$html = ob_get_clean();

require_once '../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(['isRemoteEnabled' => true]);
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$filename = "cheques_nomina_" . preg_replace('/\s+/', '_', $nombre_condominio) . "_{$mes}_{$anio}_Q{$quincena}.pdf";
$dompdf->stream($filename, ["Attachment" => false]);
?>