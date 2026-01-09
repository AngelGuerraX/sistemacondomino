<?php
ob_start();
?>


<?php

session_start();
include("../../bd.php");

$txtID = isset($_GET['txID']) ? $_GET['txID'] : "";

$mes_actual = $_SESSION['mes'];

$aniio = $_SESSION['anio'];
$mes = $_SESSION['mes'];
$idcondominio = $_SESSION['idcondominio'];


$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque = $sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque2 = $sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='nulo'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_chequenulo = $sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia = $conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque3 = $sentencia->fetchAll((PDO::FETCH_ASSOC));


$sentencia = $conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro = $sentencia->fetch(PDO::FETCH_LAZY);

$sumamas = $registro["montosum"];

$sentencia = $conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro = $sentencia->fetch(PDO::FETCH_LAZY);

$sumamenos = $registro["montosum"];

$sentencia = $conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$registro = $sentencia->fetch(PDO::FETCH_ASSOC); // Usamos FETCH_ASSOC para asegurar formato array

// Inicializamos en 0 por defecto
$cargo_bancario_show = 0;
$balance_banco = 0;
$balance_conciliado = 0;
$balance_libro = 0;

// SOLUCIÓN: Solo asignamos valores si se encontró el registro en la BD
if ($registro) {
    $cargo_bancario_show = $registro["menos_cargos_bancarios"];
    $balance_banco = $registro["balance_banco"];
    $balance_conciliado = $registro["balance_conciliado"];
    $balance_libro = $registro["balance_libro"];
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Tabla de datos</title>
    <style>
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

        .con2p {
            margin: 2px;
            background-color: lightgray;
        }

        .table-conciliacion {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .col-descripcion {
            width: 75%;
            padding: 2px 0;
            white-space: nowrap;
            overflow: hidden;
        }

        .col-monto {
            width: 15%;
            text-align: right;
            padding: 2px 0;
            font-weight: bold;
            padding-left: 200px;
            white-space: nowrap;
            overflow: hidden;
        }

        .table-subitem {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .col-subdescripcion {
            width: 70%;
            padding: 2px 0;
            white-space: nowrap;
            float: left;
            overflow: hidden;
        }

        .col-subdescripcion2 {
            width: 70%;
            padding: 2px 0;
            padding-left: 200px;
            white-space: nowrap;
            overflow: hidden;
        }

        .col-submonto {
            width: 20%;
            text-align: left;
            padding: 2px 0;
            overflow: hidden;
            white-space: nowrap;
            padding-left: 5px;

        }

        .table-detalle {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            white-space: nowrap;
            overflow: hidden;
        }

        .col-detalle {
            width: 65%;
            padding: 2px 0;
            padding-left: 200px;
            overflow: hidden;
            white-space: nowrap;
        }

        .col-detalle-monto {
            width: 55%;
            text-align: left;
            padding: 2px 0;
            overflow: hidden;
            white-space: nowrap;
        }

        .linea-separadora {
            border-bottom: 2px solid #000;
            margin: 10px 0;
        }
    </style>

<body>


    <br>

    <div class="con1">
        <p class="con2p">Condominio <?php echo $_SESSION['online']; ?></p>
        <p class="con1p">CONCILIACION BANCARIA</p>
        <p class="con1p">CUENTA CORRIENTE NO. <?php ?></p>
        <p class="con1p">AL 30 DE <?php echo $_SESSION['mes']; ?> DEL AÑO <?php echo $_SESSION['anio']; ?></p>
        <p class="con1p">VALORES EN RD$</p>
    </div>



    <br><br>
    <!-- Balance Según Banco -->
    <table class="table-conciliacion">
        <tr>
            <td class="col-descripcion">
                <strong>BALANCE SEGUN BANCO............................................................................................................................</strong>
                <span style="float: right;"></span>
            </td>
            <td class="col-monto">
                <?php
                $balance_bancoshow = number_format(floatval($balance_banco), 2, '.', ',');
                echo $balance_bancoshow;
                ?>
            </td>
        </tr>
    </table>

    <!-- Depositos en Transito -->
    <table class="table-subitem">
        <tr>
            <td class="col-subdescripcion">
                Mas (+) Depositos en Transito.....................................................................................................
                <span style="float: right;"></span>
            </td>
            <td class="col-submonto">
                <?php
                $sumamasshow = number_format(floatval($sumamas), 2, '.', ',');
                echo $sumamasshow;
                ?>
            </td>
        </tr>
    </table>

    <!-- Lista de Depositos -->
    <table class="table-detalle">
        <?php foreach ($lista_cheque as $row): ?>
            <tr>
                <td class="col-detalle">
                    <?php echo $row['detalle']; ?>
                </td>
                <td class="col-detalle-monto">
                    <?php
                    $montoshow = number_format(floatval($row['monto']), 2, '.', ',');
                    echo $montoshow;
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Cheques en Transito -->
    <table class="table-subitem">
        <tr>
            <td class="col-subdescripcion">
                Menos (-) Cheques en Transito.....................................................................................................
                <span style="float: right;"></span>
            </td>
            <td class="col-submonto">
                <?php
                $sumamenosshow = number_format(floatval($sumamenos), 2, '.', ',');
                echo $sumamenosshow;
                ?>
            </td>
        </tr>
    </table>

    <!-- Lista de Cheques -->
    <table class="table-detalle">
        <?php foreach ($lista_cheque2 as $row): ?>
            <tr>
                <td class="col-detalle">
                    <?php echo $row['detalle']; ?>
                </td>
                <td class="col-detalle-monto">
                    <?php
                    $montoshow = number_format(floatval($row['monto']), 2, '.', ',');
                    echo $montoshow;
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Balance Conciliado (Primera vez) -->
    <table class="table-conciliacion">
        <tr>
            <td class="col-descripcion">
                <strong>BALANCE CONCILIADO AL 30 DE <?php echo $_SESSION['mes']; ?> DEL AÑO <?php echo $_SESSION['anio']; ?>.....................................................................................................</strong>
                <span style="float: right;"></span>
            </td>
            <td class="col-monto">
                <?php
                $balance_conciliadoshow = number_format(floatval($balance_conciliado), 2, '.', ',');
                echo $balance_conciliadoshow;
                ?>
            </td>
        </tr>
    </table>

    <br><br><br><br>

    <!-- Balance Según Libro -->
    <table class="table-conciliacion">
        <tr>
            <td class="col-descripcion">
                <strong>BALANCE SEGUN LIBRO.....................................................................................................</strong>
                <span style="float: right;"></span>
            </td>
            <td class="col-monto">
                <?php
                $balance_libroshow = number_format(floatval($balance_libro), 2, '.', ',');
                echo $balance_libroshow;
                ?>
            </td>
        </tr>
    </table>

    <!-- Cargos Bancarios -->
    <table class="table-subitem">
        <tr>
            <td class="col-subdescripcion2">
                Menos (-) Cargos Bancarios.....................................................................................................
                <span style="float: right;"></span>
            </td>
            <td class="col-submonto">
                <?php
                $cargo_bancario_showshow = number_format(floatval($cargo_bancario_show), 2, '.', ',');
                echo $cargo_bancario_showshow;
                ?>
            </td>
        </tr>
    </table>


    <!-- Balance Conciliado (Segunda vez) -->
    <table class="table-conciliacion">
        <tr>
            <td class="col-descripcion">
                <strong>BALANCE CONCILIADO AL 30 DE <?php echo $_SESSION['mes']; ?> DEL AÑO <?php echo $_SESSION['anio']; ?>.....................................................................................................</strong>
                <span style="float: right;"></span>
            </td>
            <td class="col-monto">
                <?php
                $balance_conciliadoshow = number_format(floatval($balance_conciliado), 2, '.', ',');
                echo $balance_conciliadoshow;
                ?>
            </td>
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

$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('letter');
//$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("archivo_.pdf", array("Attachment" => false));

?>