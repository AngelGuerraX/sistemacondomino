<?php ob_start();?>
<?php session_start();
include("../../bd.php");
    
$txtID = isset($_GET['txID']) ? $_GET['txID'] : "";
    

/*
$mes_actual=$_SESSION['mes'];
$gas_actual=$_SESSION['mes'] . "_gas";
$mora_actual=$_SESSION['mes'] . "_mora";
$c_actual=$_SESSION['mes'] . "_c";
$sentencia = $conexion->prepare("SELECT a.id, a.apto, a.condominos, fecha_ultimo_pago, a.gas, ". $mes_actual ." AS `mes_actual`, ". $gas_actual .
" AS `gas_actual`, ". $mora_actual ." AS `mora_actual`, ". $c_actual ." AS `c_actual` FROM tbl_aptos a 
INNER JOIN tbl_meses_debidos m ON m.id_apto = a.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE m.ano = :anio and a.id_condominio = :id_condominio ORDER BY a.apto ASC");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":anio", $_SESSION['anio']);
$sentencia->bindParam(":id_condominio", $_SESSION['idcondominio']);
$sentencia->execute();
$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);  

*/


$sentencia = $conexion->prepare("SELECT A.id, A.apto, A.condominos, A.mantenimiento, A.fecha_ultimo_pago, T.id_apto, T.estado, T.mes, T.anio
FROM tbl_aptos A
LEFT JOIN tbl_tickets T ON A.id = T.id_apto AND T.mes = :mes AND T.anio = :anio
WHERE A.id_condominio = :idcondominio;
");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $anio);
$sentencia->execute();
$rows = $sentencia->fetchAll((PDO::FETCH_ASSOC));


//////////////////////////////////////////
////////   GASTOS DEL MES ACTUAL   ///////
//////////////////////////////////////////
$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio']; 
 
$tipo_gasto="Nomina_Empleados";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id_condominio and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id_condominio", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Nomina=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
$tipo_gasto="Servicios_Basicos";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Servicios=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
$tipo_gasto="Gastos_Menores_Material_Gastable";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Material=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
$tipo_gasto="lmprevistos";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_lmprevistos=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
$tipo_gasto="Cargos_Bancarios";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_Cargos=$sentencia->fetchAll((PDO::FETCH_ASSOC));
 
$tipo_gasto="Servicios_lgualados";
$sentencia=$conexion->prepare("SELECT * FROM tbl_gastos where id_condominio=:id and tipo_gasto=:tipo_gasto and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":tipo_gasto", $tipo_gasto);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_gasto_lgualados=$sentencia->fetchAll((PDO::FETCH_ASSOC));

//BUSCA MES ANTERIOR
$mes_actual = $_SESSION['mes'];
$aniio = $_SESSION['anio'];
$meses = array(
   "enero", "febrero", "marzo", "abril", "mayo", "junio",
   "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
);
$indice_mes_actual = array_search($mes_actual, $meses);
$indice_mes_anterior = ($indice_mes_actual - 1 + 12) % 12;
$anio_anterior = $aniio;
if ($indice_mes_anterior === 11) {
   $anio_anterior--;
}
$mes_anterior = $meses[$indice_mes_anterior];

$sentencia = $conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id_condominio=:id and mes=:mes and anio=:anio");
$sentencia->bindParam(":id", $idcondominio);
$sentencia->bindParam(":mes", $mes_anterior);
$sentencia->bindParam(":anio", $anio_anterior);
$sentencia->execute();
$lista_tbl_estado2 = $sentencia->fetchAll(PDO::FETCH_ASSOC);
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
        }
        th {
            margin-left: 0px;
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

<div class="con1">
    <p class="con2p">Condominio <?php echo $_SESSION['online'];?></p>
    <p class="con1p">ESTADO DE RESULTADO</p>
    <p class="con1p">AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?></p>
</div>
<?php
$totalg = 0; // Variable para almacenar el total
foreach ($rows as $row) {
    $subtotal = floatval($row['mes']) + floatval($row['gas']) + floatval($row['mora']) + floatval($row['cuota']);
    $totalg += $subtotal; // Sumar el subtotal al total
    // Resto del código para mostrar los datos de cada fila
}
?>

 
 


<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;">INGRESOS..................................................................................................................................................................................</h6>
<h6 style="font-size: 12px; margin: 0px; margin-top: -11; float: right;"><?php $total_ingresos= number_format(floatval($totalg), 2, '.', ','); echo $total_ingresos;?></h6>

<table>
        <tr> 
            <th align="left">Apto</th>
            <th align="left">Condominos</th>
            <th align="left">Ultimo Pago</th>
            <th align="left">Mantenimiento</th>
            <th align="left">Gas</th>
            <th align="left">Mora</th>
            <th align="left">Cuota Extra</th>
            <th align="left">Total</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr> 
                <td><?php echo $row['apto']; ?></td>
                <td><?php echo $row['condominos']; ?></td>
                <td><?php echo $row['fecha_ultimo_pago']; ?></td>
                <td><?php $subtotalFormatted1 = number_format(floatval($row['mes']), 2, '.', ','); echo $subtotalFormatted1 ?></td>
                <td><?php $subtotalFormatted2 = number_format(floatval($row['gas']), 2, '.', ','); echo $subtotalFormatted2 ?></td>
                <td><?php $subtotalFormatted3 = number_format(floatval($row['mora']), 2, '.', ','); echo $subtotalFormatted3 ?></td>
                <td><?php $subtotalFormatted4 = number_format(floatval($row['cuota']), 2, '.', ','); echo $subtotalFormatted4 ?></td>
                <td><?php $total1 = floatval($row['mes']) + floatval($row['gas']) + floatval($row['mora']) + floatval($row['cuota']);$subtotalFormatted5= number_format(floatval($total1), 2, '.', ','); echo $subtotalFormatted5 ?></td>
            </tr>
        <?php endforeach; ?>
    </table>    

    <?php
$totalNomina = 0;
$totalServicios = 0;
$totalMaterial = 0;
$totalImprevistos = 0;
$totalCargos = 0;
$totalIgualados = 0;

foreach ($lista_gasto_Nomina as $row) {
    $subtotaln1 = floatval($row['monto']);
    $totalNomina += $subtotaln1;
}

foreach ($lista_gasto_Servicios as $row) {
    $subtotaln2 = floatval($row['monto']);
    $totalServicios += $subtotaln2;
}

foreach ($lista_gasto_Material as $row) {
    $subtotaln3 = floatval($row['monto']);
    $totalMaterial += $subtotaln3;
}

foreach ($lista_gasto_lmprevistos as $row) {
    $subtotaln4 = floatval($row['monto']);
    $totalImprevistos += $subtotaln4;
}

foreach ($lista_gasto_Cargos as $row) {
    $subtotaln6 = floatval($row['monto']);
    $totalCargos += $subtotaln6;
}

foreach ($lista_gasto_lgualados as $row) {
    $subtotaln8 = floatval($row['monto']);
    $totalIgualados += $subtotaln8;
}

$totalGastos = $totalNomina + $totalServicios + $totalMaterial + $totalImprevistos + $totalCargos + $totalIgualados;


foreach ($lista_tbl_estado2 as $row) {
    $res_anterior = $row['resultado_actual'];
 
    }
    if (isset($row['resultado_actual'])) {
       
    $res_anterior = $row['resultado_actual'];
     } else {
      
    $res_anterior = 0;
     }

?>
    <br>
    
<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;">GASTOS..................................................................................................................................................................................</h6>

<h6 style="font-size: 12px; margin: 0px; margin-top: -11; float: right;"><?php $totalGastosshow= number_format(floatval($totalGastos), 2, '.', ','); echo $totalGastosshow;?></h6>
<table>
        <tr>
            <th width="430" align="left">Nomina Empleados</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_Nomina as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn1 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn1; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <table>
        <tr>
            <th width="430" align="left">Servicios Básicos</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_Servicios as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn2 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn2; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <table>
        <tr>
            <th width="430" align="left">Gastos Menores, Material Gastable</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_Material as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn3 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn3; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <table>
        <tr>
            <th width="430" align="left">Imprevistos</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_lmprevistos as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn4 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn4; ?></td>
        </tr>
        <?php endforeach; ?>
    </table> 
    <table>
        <tr>
            <th width="430" align="left">Cargos Bancarios</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_Cargos as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn6 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn6; ?></td>
        </tr>
        <?php endforeach; ?>
    </table> 
    <table>
        <tr>
            <th width="430" align="left">Servicios Igualados</th>
            <th width="100"></th>
        </tr>
        <?php foreach ($lista_gasto_lgualados as $row): ?>
        <tr>
            <td class="titlecontent" width="430"><?php echo $row['detalles']; ?></td>
            <td width="100"><?php  $subtotalFormattedn8 = number_format(floatval($row['monto']), 2, '.', ','); echo $subtotalFormattedn8; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>    


<br>


<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;">CIERRE DEL MES......................................................................................................................................................................</h6>

<h6 style="font-size: 12px; margin: 0px; margin-top: -11; float: right;">
<?php
$cierre = $totalg - $totalGastos;
$cierreshow = number_format(floatval($cierre), 2, '.', ','); echo $cierreshow;
?>
</h6>



<h6 style="font-size: 12px; margin: 0px; margin-top: 15px; font-weight: 100;margin-left: 20px; font-family: Arial, sans-serif;">Resultado de las Operaciones del Mes Anterior</h6>
<h6 style="font-size: 12px; margin: 0px; margin-top: -11; float: right;"><?php $res_anteriorshow = number_format(floatval($res_anterior), 2, '.', ','); echo $res_anteriorshow;?></h6>



<h6 style="font-size: 12px; margin: 0px; margin-top: 15px;">Resultado Actual de las Operaciones.....................................................................................................................................</h6>
<h6 style="font-size: 12px; margin: 0px; margin-top: -11; float: right;"><?php $res_actual=$cierre + $res_anterior; $res_actualshow = number_format(floatval($res_actual), 2, '.', ','); echo $res_actualshow;?></h6>



    
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
