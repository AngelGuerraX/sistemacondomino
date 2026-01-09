<?php
ob_start();
session_start();
include("../../bd.php");

// Obtener parámetros
$idcondominio = $_SESSION['idcondominio'];
$mes = $_GET['mes'] ?? $_SESSION['mes'] ?? date('n');
$anio = $_GET['anio'] ?? $_SESSION['anio'] ?? date('Y');
$quincena = $_GET['quincena'] ?? '30';

// Convertir mes numérico a nombre para mostrar
$mes_nombre = $_SESSION['mes'] ?? 'AGOSTO'; // Mantener el formato actual

// Obtener nóminas del período con la nueva estructura
$sentencia = $conexion->prepare("
    SELECT 
        n.*,
        e.nombres,
        e.apellidos,
        e.cedula_pasaporte,
        e.cargo,
        e.fecha_ingreso,
        e.salario as salario_mensual
    FROM tbl_nomina n
    INNER JOIN tbl_empleados e ON n.id_empleado = e.id
    WHERE n.id_condominio = :id_condominio 
    AND n.mes = :mes 
    AND n.anio = :anio 
    AND n.quincena = :quincena
    ORDER BY e.nombres, e.apellidos
");
$sentencia->bindParam(":id_condominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $anio);
$sentencia->bindParam(":quincena", $quincena);
$sentencia->execute();

$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Tabla de datos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <style>
        @page {
            size: letter landscape;
        }

        * {
            font-family: Arial, sans-serif;
        }

        .con1 {
            text-transform: uppercase;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            font-family: Arial, sans-serif;
        }

        .con1p {
            margin: 0px;
            font-family: Arial, sans-serif;
        }

        .con3 {
            margin: 0px;
            font-family: Arial, sans-serif;
            font-style: italic;
            font-weight: 100;
            text-align: right;
        }

        .con2p {
            font-size: 25px;
            margin: 2px;
            background-color: lightgray;
            font-family: Arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            font-size: 12px;
        }

        th {
            background-color: yellow;
        }

        .input-wrapper input {
            background-color: #eee;
            border: none;
            padding: 1rem;
            font-size: 1rem;
            width: 13em;
            border-radius: 10px;
            color: lightcoral;
            box-shadow: 0 0.4rem #dfd9d9;
            cursor: pointer;
            display: flex;
        }

        .input-wrapper input:focus {
            outline-color: lightcoral;
        }

        .container1 {
            padding: 3px;
            border-color: black;
            border: 2px solid black;
            height: auto;
            width: 950px;
        }

        .columnas {
            border: 2px solid black;
            width: auto;
            height: auto;
            padding: 10px;
        }

        .text-right {
            text-align: right;
        }

        .moneda {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>

<body>

    <div class="con1">
        <p class="con2p">Condominio <?php echo $_SESSION['online']; ?></p>
        <p class="con1p">Nomina Quincenal Empleados</p>
        <p class="con1p">AL <?php echo $quincena == '15' ? '15' : '30'; ?> DE <?php echo strtoupper($mes_nombre); ?> DEL AÑO <?php echo $anio; ?></p>
        <p class="con1p">VALORES EN RD$</p>
    </div>
    <br>
    <p class="con3">Fecha correspondiente al <?php echo $quincena; ?> de <?php echo $mes_nombre; ?> del <?php echo $anio; ?></p>

    <table>
        <tr>
            <th>No.</th>
            <th>Nombre</th>
            <th>Cédula / Pasaporte</th>
            <th>Ocupación / Cargo</th>
            <th>Fecha Inicio</th>
            <th>Salario Mensual</th>
            <th>Salario Quincenal</th>
            <th>Horas Extras</th>
            <th>Incentivos</th>
            <th>Descuento por Préstamo</th>
            <th>Desc. / TSS</th>
            <th>Quincena</th>
        </tr>
        <?php
        $contador = 1;
        $total_general = 0;
        foreach ($rows as $row):
            $total_quincena = $row['total_quincena'];
            $total_general += $total_quincena;
        ?>
            <tr>
                <td><?php echo $contador++; ?></td>
                <td><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></td>
                <td><?php echo htmlspecialchars($row['cedula_pasaporte']); ?></td>
                <td><?php echo htmlspecialchars($row['cargo']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($row['fecha_ingreso'])); ?></td>
                <td class="text-right"><?php echo number_format($row['salario_mensual'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['salario_quincenal'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['horas_extras'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['incentivos'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['descuento_prestamo'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['descuento_tss'], 2); ?></td>
                <td class="text-right"><strong><?php echo number_format($total_quincena, 2); ?></strong></td>
            </tr>
        <?php endforeach; ?>

        <!-- Fila de total general -->
        <tr style="background-color: #ffffffff;">
            <td colspan="11" class="text-right"><strong>TOTAL GENERAL:</strong></td>
            <td class="text-right moneda"><strong><?php echo number_format($total_general, 2); ?></strong></td>
        </tr>
    </table>

    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

    <div class="container1">
        <div class="columnas">
            <center>
                <h2>DETALLES DE PAGO</h2>
            </center>
        </div>
        <div class="columnas">
            <form action="" method="post" enctype="multipart/form-data">
                <div style="font-size: 20px; margin-top: 10px;">
                    <label for="nombre" style="font-size: 24px;">FECHA:</label>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 140px" value="<?php echo date('d/m/Y'); ?>">

                    <label style="font-size: 20px; margin-left: 210px;">NO. IDENTIDAD:</label>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; width: 242px;" value="COND-<?php echo $idcondominio; ?>-<?php echo date('Ym'); ?>">
                </div>
                <div style="font-size: 20px;">
                    <label for="nombre" class="">EMPLEADO:</label> <br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 890px; " value="NÓMINA QUINCENAL EMPLEADOS - <?php echo strtoupper($mes_nombre); ?> <?php echo $anio; ?>">
                </div>
                <div style="font-size: 20px; margin-top: 15px;">
                    <label for="nombre" class="">CONCEPTO:</label><br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 890px;" value="Pago de nómina quincenal <?php echo $quincena == '15' ? '1-15' : '16-30'; ?> de <?php echo $mes_nombre; ?> <?php echo $anio; ?>">
                </div>
                <div style="font-size: 20px; margin-top: 15px;">
                    <label style="font-size: 24px;">MONTO:</label><br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; width: 890px;" value="RD$ <?php echo number_format($total_general, 2); ?>">
                </div>
                <br> <br>
                <div style="font-size: 20px;">
                    <label style="font-size: 24px; margin-left: 90px;">__________________</label> <label style="font-size: 24px; margin-left: 230px;">__________________</label>
                    <br>
                    <label style="font-size: 24px; margin-left: 140px;">Aprobado por</label> <label style="font-size: 24px; margin-left: 325px;">Recibido por</label>
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
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();

$filename = "nomina_" . $_SESSION['online'] . "_" . $mes_nombre . "_" . $anio . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>