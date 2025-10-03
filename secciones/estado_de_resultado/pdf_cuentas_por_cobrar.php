<?php ob_start();?>
<?php session_start();
include("../../bd.php");
    




$txtID = isset($_GET['txID']) ? $_GET['txID'] : "";

//BUSCA MES ACTUAL//////////////////////////////////////////////////////////////////////////////////////////////
$mes_actual=$_SESSION['mes'];
$gas_actual=$_SESSION['mes'] . "_gas";
$mora_actual=$_SESSION['mes'] . "_mora";
$c_actual=$_SESSION['mes'] . "_c";

$meses = array(
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
 );

 $aniio = $_SESSION['anio'];
//BUSCA MES 2 ATRAS/////////////////////////////////////
$indice_mes_actual = array_search($mes_actual, $meses);
$indice_mes_anterior = ($indice_mes_actual - 1 + 12) % 12;
$anio_anterior = $aniio;
if ($indice_mes_anterior === 11) {
   $anio_anterior--;
}
$mes_anterior = $meses[$indice_mes_anterior];
$mes_actual2 = $mes_anterior;
$gas_actual2=$mes_actual2 . "_gas";
$mora_actual2=$mes_actual2 . "_mora";
$c_actual2=$mes_actual2 . "_c";
///////////////////////////////////////////////////////
$mes_actual=$_SESSION['mes'];

$sentencia = $conexion->prepare("SELECT a.id, a.apto, a.condominos, a.mantenimiento,
". $mes_actual ." AS `mes_actual`, ". $gas_actual ." AS `gas_actual`, ". $mora_actual ." AS `mora_actual`, ". $c_actual ." AS `c_actual` ,
". $mes_actual2 ." AS `mes_actual2`, ". $gas_actual2 ." AS `gas_actual2`, ". $mora_actual2 ." AS `mora_actual2`, ". $c_actual2 ." AS `c_actual2` 
 FROM tbl_aptos a 
INNER JOIN tbl_meses_debidos m ON m.id_apto = a.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE m.ano = :anio and a.id_condominio = :id_condominio and
 ". $mes_actual ." = '0' or m.ano = :anio and a.id_condominio = :id_condominio and ". $mes_actual ." = ''
ORDER BY a.apto ASC");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":anio", $_SESSION['anio']);
$sentencia->bindParam(":id_condominio", $_SESSION['idcondominio']);
$sentencia->execute();
$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////CONSULTA CEUNTAS POR PAGAR/////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
$indice_mes_actual = array_search($mes_actual, $meses);
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente = $meses[$indice_mes_siguiente];
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente1 = $meses[$indice_mes_siguiente];
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente2 = $meses[$indice_mes_siguiente];
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente3 = $meses[$indice_mes_siguiente];
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente4 = $meses[$indice_mes_siguiente];
$indice_mes_siguiente = ($indice_mes_actual + 1) % 12;
$mes_siguiente = $meses[$indice_mes_siguiente];

$sentencia = $conexion->prepare("SELECT a.id, a.apto, a.condominos, a.mantenimiento,
". $mes_actual ." AS `mes_actual`, ". $gas_actual ." AS `gas_actual`, ". $mora_actual ." AS `mora_actual`, ". $c_actual ." AS `c_actual` ,
". $mes_actual2 ." AS `mes_actual2`, ". $gas_actual2 ." AS `gas_actual2`, ". $mora_actual2 ." AS `mora_actual2`, ". $c_actual2 ." AS `c_actual2` ,
". $mes_actual2 ." AS `mes_actual2`, ". $gas_actual2 ." AS `gas_actual2`, ". $mora_actual2 ." AS `mora_actual2`, ". $c_actual2 ." AS `c_actual2` 
 FROM tbl_aptos a 
INNER JOIN tbl_meses_debidos m ON m.id_apto = a.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE m.ano = :anio and a.id_condominio = :id_condominio and ". $mes_siguiente ." <> '0' 
ORDER BY a.apto ASC");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":anio", $_SESSION['anio']);
$sentencia->bindParam(":id_condominio", $_SESSION['idcondominio']);
$sentencia->execute();
$rows2 = $sentencia->fetchAll(PDO::FETCH_ASSOC);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
<!DOCTYPE html>
<html>
<head>
    <title>Tabla de datos</title>
    <style>
        * {
            font-family: Arial, sans-serif;
        }

        .con1{
            text-transform: uppercase;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            font-family: Arial, sans-serif;
        }
        .con1p{
            margin: -2px;
            font-size: 13px;
            font-family: Arial, sans-serif;
        }
        
        .con2p{
            margin: 0px;
            background-color: lightgray;
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%; 
            
        }
        th, td {
            padding: 2px;
            font-size: 12px; 
            margin-left: 0px;
            border: 1px solid black;
        }
        
        .con3{
            margin: 0px;
            font-family: Arial, sans-serif;
            font-weight: 100;
            text-align: right;
        }
        .titlecontent{
            padding-left: 37px;
        }

    </style>
</head>
<body>
<br><br><br>

<div class="con1">
    <p class="con2p">Condominio <?php echo $_SESSION['online'];?></p>
    <p class="con1p">RELACION DE CUENTAS POR COBRAR, PAGOS ADELANTADOS Y CUENTAS POR PAGAR</p>
    <p class="con1p">AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?></p>
    <p class="con1p">VALORED EN RD$</p>
</div>
<?php
$totalg = 0;
foreach ($rows as $row) {
    $subtotal = floatval($row['mes_actual']) + floatval($row['gas_actual']) + floatval($row['mora_actual']) + floatval($row['c_actual']);
    $totalg += $subtotal; // Sumar el subtotal al total
}?>
<br>
<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;"><u>CUENTAS POR COBRAR:</u></h6>
<table>
        <tr> 
            <th align="left">Apto</th>
            <th align="left">Condominos</th>
            <th align="left">Deuda Anterior</th>
            <th align="left"><?php echo $mes_actual; ?></th>
            <th align="left">Mora</th>
            <th align="left">Gas</th>
            <th align="left">Cuota Extra</th>
            <th align="left">Total</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr> 
                <td><?php echo $row['apto']; ?></td>
                <td><?php echo $row['condominos']; ?></td>
                <td><?php echo "debido antes"; ?></td>
                <td><?php $mantenimientoshow = number_format(floatval($row['mantenimiento']), 2, '.', ','); echo $mantenimientoshow ; ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['mora_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['gas_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['c_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $total1 = floatval($row['mes_actual']) + floatval($row['gas_actual']) + floatval($row['mora_actual']) + floatval($row['c_actual']);$subtotalFormatted5= number_format(floatval($total1), 2, '.', ','); echo $subtotalFormatted5 ?></td>
            </tr>
        <?php endforeach; ?>
    </table>   



    <br><br>
<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;"><u>CUENTAS PAGADAS POR ADELANTADO:</u></h6>
<table>
        <tr> 
            <th align="left">Apto</th>
            <th align="left">Condominos</th>
            <th align="left">Deuda Anterior</th>
            <th align="left"><?php echo $mes_actual; ?></th>
            <th align="left">Mora</th>
            <th align="left">Gas</th>
            <th align="left">Cuota Extra</th>
            <th align="left">Total</th>
        </tr>
        <?php foreach ($rows2 as $row): ?>
            <tr> 
                <td><?php echo $row['apto']; ?></td>
                <td><?php echo $row['condominos']; ?></td>
                <td><?php echo "debido antes"; ?></td>
                <td><?php $mantenimientoshow = number_format(floatval($row['mantenimiento']), 2, '.', ','); echo $mantenimientoshow ; ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['mora_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['gas_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['c_actual']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $total1 = floatval($row['mes_actual']) + floatval($row['gas_actual']) + floatval($row['mora_actual']) + floatval($row['c_actual']);$subtotalFormatted5= number_format(floatval($total1), 2, '.', ','); echo $subtotalFormatted5 ?></td>
            </tr>
        <?php endforeach; ?>
    </table>   




</body>



</html>

<?php 
$html = ob_get_clean();
//echo $html;

require_once '../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
$dompdf = new Dompdf();

$options= $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('letter');
//$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("archivo_.pdf", array("Attachment" => false));

?>
