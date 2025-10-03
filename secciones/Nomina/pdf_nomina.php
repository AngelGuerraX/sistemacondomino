<?php ob_start();
session_start();
include("../../bd.php");    
$txtID = isset($_GET['txID']) ? $_GET['txID'] : "";    
$aniio=$_SESSION['anio'];
$mes=$_SESSION['mes'];
$idcondominio=$_SESSION['idcondominio'];

$sentencia = $conexion->prepare("SELECT e.id, e.nombres, e.apellidos, e.cedula_pasaporte, e.cargo, e.fecha_ingreso, e.salario, n.d_f, n.d_e FROM tbl_empleados e 
INNER JOIN tbl_nomina n ON n.idempleado = e.id
INNER JOIN tbl_usuarios u on u.id=:id WHERE n.anio = :anio and n.mes = :mes and n.idcondominio = :idcondominio");
$sentencia->bindParam(":id", $_SESSION['id']);
$sentencia->bindParam(":anio", $_SESSION['anio']);
$sentencia->bindParam(":mes", $_SESSION['mes']);
$sentencia->bindParam(":idcondominio", $_SESSION['idcondominio']);
$sentencia->execute();

$rows = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia=$conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes);
$sentencia->bindParam(":anio", $aniio);
$sentencia->execute();
$lista_cheque=$sentencia->fetchAll((PDO::FETCH_ASSOC));

$sentencia=$conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Tabla de datos</title>
    
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <style>
        @page {
            size: letter landscape; /* Establecer el tamaño del papel a "carta" y la orientación a horizontal */       }
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
        .con3{
            margin: 0px;
            font-family: Arial, sans-serif;
            font-style: italic;
            font-weight: 100;
            text-align: right;
        }
        
        .con2p{
            font-size: 25px;
            margin: 2px;
            background-color: lightgray;
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
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
        .container1{
            padding: 3px;
            border-color: black;
            border: 2px solid black;
            height: auto;
            width: 950px;
        }
        .columnas{
            border: 2px solid black;
            width: auto;
            height: auto;
            padding: 10px;

        }


    </style>
</head>
<body>

<div class="con1">
    <p class="con2p">Condominio <?php echo $_SESSION['online'];?></p>
    <p class="con1p">Nomina Quincenal Empleados</p>
    <p class="con1p">AL 30 DE <?php echo $_SESSION['mes'];?> DEL AÑO <?php echo $_SESSION['anio'];?></p>  
    <p class="con1p">VALORES EN RD$</p>
</div>
<br><br><br>
    <p class="con3">Fecha correspondiente al <?php echo $_SESSION['anio'];?> de <?php echo $_SESSION['mes'];?> del <?php echo $_SESSION['anio'];?></p>

<table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Cedula Pasaporte</th>
            <th>Cargo</th>
            <th>Fecha Ingreso</th>
            <th>Salario</th>
            <th>Dias Feriado</th>
            <th>Dias Extras</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['nombres']; echo "  "; echo $row['apellidos']; ?></td>
                <td><?php echo $row['cedula_pasaporte']; ?></td>
                <td><?php echo $row['cargo']; ?></td>
                <td><?php echo $row['fecha_ingreso']; ?></td>
                <td><?php echo $row['salario']; ?></td>
                <td><?php echo $row['d_f']; ?></td>
                <td><?php echo $row['d_e']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

<div class="container1">
        <div class="columnas">
            <center><h2>DETALLES DE PAGO</h2></center>            
        </div>
        <div class="columnas">
            <form action="" method="post" enctype="multipart/form-data">
                <div style="font-size: 20px; margin-top: 10px;">
                    <label for="nombre" style="font-size: 24px;">FECHA:</label>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 140px" value="30/04/2025">

                    <label style="font-size: 20px; margin-left: 210px;">NO. IDENTIDAD:</label> 
                    <input type="text" style="border-radius: 5px; border: 1px solid black; width: 242px;" value="402-342566-8">
                </div>
                <div style="font-size: 20px;">
                    <label for="nombre" class="">EMPLEADO:</label> <br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 890px; " value="Juan pedro sosa">
                </div> 
                <div style="font-size: 20px; margin-top: 15px;"> 
                    <label for="nombre" class="">CONCEPTO:</label><br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; margin-top: 5px; width: 890px;" value="Juan pedro sosa">
                </div>
                <div style="font-size: 20px; margin-top: 15px;">
                    <label style="font-size: 24px;">MONTO:</label><br>
                    <input type="text" style="border-radius: 5px; border: 1px solid black; width: 890px;" value="RD$ 25,000.00">
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
