<?php 
ob_start();
?>


<?php

session_start();
include("../../bd.php");
    
$txtID = isset($_GET['txID']) ? $_GET['txID'] : "";
    
$mes_actual=$_SESSION['mes'];

$sentencia = $conexion->prepare("SELECT a.id, a.apto, a.condominos, fecha_ultimo_pago, a.gas, ". $mes_actual ." AS `mes_actual` FROM tbl_aptos a 
INNER JOIN tbl_meses_debidos m ON m.id_apto = a.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE m.ano = :ano");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":ano", $_SESSION['anio']);
$sentencia->execute();

// Obtener los datos en un arreglo
$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);


$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque=$sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque2=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='nulo'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_chequenulo=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque3=$sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia=$conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

$sumamas=$registro["montosum"];

$sentencia=$conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);

$sumamenos=$registro["montosum"];


$sentencia=$conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro=$sentencia->fetch(PDO::FETCH_LAZY);


$cargo_bancario_show=$registro["menos_cargos_bancarios"];
$balance_banco=$registro["balance_banco"];
$balance_conciliado=$registro["balance_conciliado"];
$balance_libro=$registro["balance_libro"];


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
            font-size: 20px;
            font-weight: 600;
            font-family: Arial, sans-serif;
        }
        .con1p{
            margin: 0px;
            font-family: Arial, sans-serif;
        }
        
        .con2p{
            margin: 2px;
            background-color: lightgray;
            font-family: Arial, sans-serif;
        }


    </style>
</head>
<body>


<br>

<div class="con1">
    <p class="con2p">Condominio <?php echo $_SESSION['online'];?></p>
    <p class="con1p">CONCILIACION BANCARIA</p>
    <p class="con1p">CUENTA CORRIENTE NO. <?php ?></p>
    <p class="con1p">AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?></p>  
    <p class="con1p">VALORES EN RD$</p>
</div>



<br><br><br><br>
<?php   ?>

<table>
  <tr>
    <td><strong>BALANCE SEGUN BANCO............................................................................................</strong></td>
    <td><?php $balance_bancoshow = number_format(floatval($balance_banco), 2, '.', ','); echo $balance_bancoshow ;?></td>
  </tr>
  </table>

  <table>
  <tr>
    <td style="margin-left: 150px;">Mas (+) Depositos en Transito.................................................................</td>
    <td><?php  $sumamasshow = number_format(floatval($sumamas), 2, '.', ','); echo $sumamasshow;?></td>
  </tr>
</table>


<div style="margin-left: 200px;">
    <table>
        <?php foreach ($lista_cheque as $row): ?>
            <tr>
                <td><?php echo $row['detalle']. "  ______   ";?></td>
                <td><?php $montoshow = number_format(floatval($row['monto']), 2, '.', ','); echo $montoshow;?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>


<table>
  <tr>
    <td>Menos (-) Cheques en Transito................................................................</td>
    <td><?php $sumamenosshow = number_format(floatval($sumamenos), 2, '.', ','); echo $sumamenosshow;?></td>
  </tr>
</table>


<div style="margin-left: 200px;">
    <table>
        <?php foreach ($lista_cheque2 as $row): ?>
            <tr>
                <td><?php echo $row['detalle']; ?></td>
                <td><?php $montoshow = number_format(floatval($row['monto']), 2, '.', ','); echo $montoshow;?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<br>
<table>
    <tr>  
        <td style="text-transform: uppercase;"><strong>BALANCE CONCILIADO AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?>......................................</strong></td>
        <td><?php $balance_conciliadoshow = number_format(floatval($balance_conciliado), 2, '.', ','); echo $balance_conciliadoshow;?></td>
    </tr>
</table>

<br><br><br><br>

<table>
    <tr>  
        <td style="text-transform: uppercase;"><strong>BALANCE SEGUN LIBRO.............................................................................................</strong></td>
        <td><?php $balance_libroshow = number_format(floatval($balance_libro), 2, '.', ','); echo $balance_libroshow;?></td>
    </tr>
</table>

<div style="margin-left: 200px;">
    <table>
            <tr>
                <td>Menos (-) Cargos Bancarios.................</td>
                <td><?php $cargo_bancario_showshow = number_format(floatval($cargo_bancario_show), 2, '.', ','); echo $cargo_bancario_showshow;?></td>
            </tr>
    </table>
</div>


<br>


<table>
    <tr>  
        <td style="text-transform: uppercase;"><strong>BALANCE CONCILIADO AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?>...............................</strong></td>
        <td><?php $balance_conciliadoshow = number_format(floatval($balance_conciliado), 2, '.', ','); echo $balance_conciliadoshow;?></td>
    </tr>
</table>

<br><br><br>


    <h4 style="text-decoration: underline;">CHEQUES NULOS</h4>

    <div style="text-align: center; margin-left: 30;">
    <table>
        <?php foreach ($lista_chequenulo as $row): ?>
            <tr>
                <td><?php echo $row['detalle']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

    
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
