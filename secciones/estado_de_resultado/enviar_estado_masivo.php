<?php
// AUMENTAR TIEMPO DE EJECUCIÓN (El envío masivo puede tardar)
set_time_limit(300); // 5 minutos máximo
session_start();
include("../../bd.php");

require '../../libs/PHPMailer/src/Exception.php';
require '../../libs/PHPMailer/src/PHPMailer.php';
require '../../libs/PHPMailer/src/SMTP.php';
require '../../libs/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

// 1. OBTENER ID DEL ESTADO A ENVIAR
if (!isset($_GET['id'])) {
    die("Error: No se seleccionó ningún reporte.");
}
$id_estado = $_GET['id'];

// 2. OBTENER CREDENCIALES DE ENVÍO (IGUAL QUE TUS OTROS ARCHIVOS)
$usuario_sesion = $_SESSION['usuario'];
$stmt_user = $conexion->prepare("SELECT correo, correopass FROM tbl_usuarios WHERE usuario = :usu");
$stmt_user->execute([':usu' => $usuario_sesion]);
$emisor = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (empty($emisor['correo']) || empty($emisor['correopass'])) {
    die("❌ Error: Tu usuario no tiene configurado el correo o la clave SMTP.");
}

// Configuración Brevo
$smtp_host = 'smtp-relay.brevo.com';
$smtp_user = '9f763d001@smtp-brevo.com'; // TU USUARIO TÉCNICO
$smtp_pass = $emisor['correopass'];      // TU CLAVE SMTP DE LA BD
$smtp_from = $emisor['correo'];          // TU CORREO REAL

// 3. GENERAR EL PDF EN MEMORIA
// Obtenemos los datos del reporte
$stmt_rep = $conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id = :id");
$stmt_rep->execute([':id' => $id_estado]);
$reporte = $stmt_rep->fetch(PDO::FETCH_ASSOC);

if (!$reporte) die("Reporte no encontrado.");

// Construimos el HTML del PDF manualmente para no depender de includes externos complejos
$mes = $reporte['mes'];
$anio = $reporte['anio'];
$html = "
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; }
        h1 { text-align: center; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        th { background-color: #f2f2f2; text-align: center; }
        .total { font-weight: bold; background-color: #e9ecef; }
    </style>
</head>
<body>
    <h1>Estado de Resultados</h1>
    <h3 style='text-align: center;'>Periodo: $mes $anio</h3>
    <hr>
    <table>
        <tr>
            <th>Concepto</th>
            <th>Monto (RD$)</th>
        </tr>
        <tr>
            <td style='text-align: left;'>Total Ingresos (Cobros)</td>
            <td style='color: green;'>" . number_format($reporte['ingresos'], 2) . "</td>
        </tr>
        <tr>
            <td style='text-align: left;'>Total Gastos</td>
            <td style='color: red;'>" . number_format($reporte['gastos'], 2) . "</td>
        </tr>
        <tr class='total'>
            <td style='text-align: left;'>Cierre del Mes</td>
            <td>" . number_format($reporte['cierre_mes'], 2) . "</td>
        </tr>
        <tr>
            <td style='text-align: left;'>Resultado Anterior</td>
            <td>" . number_format($reporte['resultado_anterior'], 2) . "</td>
        </tr>
        <tr class='total' style='background-color: #cfe2ff;'>
            <td style='text-align: left;'>RESULTADO ACUMULADO ACTUAL</td>
            <td style='font-size: 14px;'>" . number_format($reporte['resultado_actual'], 2) . "</td>
        </tr>
    </table>
    <br><br>
    <p style='text-align: center; font-size: 12px; color: #777;'>Generado automáticamente por el Sistema de Condominio.</p>
</body>
</html>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$pdf_content = $dompdf->output(); // Guardamos el PDF en esta variable

// 4. OBTENER LISTA DE DESTINATARIOS (Propietarios + Inquilinos)
$id_condominio = $reporte['id_condominio'];
$destinatarios = [];

// A. Propietarios
$sql_prop = "SELECT correo, condominos as nombre, 'Propietario' as rol FROM tbl_aptos WHERE id_condominio = :id AND correo != ''";
$stmt_p = $conexion->prepare($sql_prop);
$stmt_p->execute([':id' => $id_condominio]);
$props = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

// B. Inquilinos
$sql_inq = "SELECT i.correo, i.nombre, 'Inquilino' as rol 
            FROM tbl_inquilinos i 
            JOIN tbl_aptos a ON i.id_apto = a.id 
            WHERE a.id_condominio = :id AND i.correo != ''";
$stmt_i = $conexion->prepare($sql_inq);
$stmt_i->execute([':id' => $id_condominio]);
$inqs = $stmt_i->fetchAll(PDO::FETCH_ASSOC);

// Unir listas
$todos = array_merge($props, $inqs);

// 5. ENVIAR CORREOS
$resultados = []; // Aquí guardaremos quien se envió y quien no

foreach ($todos as $persona) {
    $mail = new PHPMailer(true);
    $estado_envio = 'Pendiente';
    $mensaje_detalle = '';

    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp_from, 'Administracion Condominio');
        $mail->addAddress($persona['correo'], $persona['nombre']);

        // Adjuntar el PDF generado en memoria
        $mail->addStringAttachment($pdf_content, "Estado_Resultado_{$mes}_{$anio}.pdf");

        $mail->isHTML(true);
        $mail->Subject = "Reporte Financiero: $mes $anio";
        $mail->Body    = "
            <h3>Estimado(a) {$persona['nombre']},</h3>
            <p>Adjunto encontrará el <strong>Estado de Resultado</strong> correspondiente al periodo <strong>$mes $anio</strong>.</p>
            <p>Atentamente,<br>La Administración.</p>
        ";

        $mail->send();

        $estado_envio = 'Enviado';
        $mensaje_detalle = 'OK';
    } catch (Exception $e) {
        $estado_envio = 'Error';
        $mensaje_detalle = $mail->ErrorInfo;
    }

    // Guardar en el array de reporte
    $resultados[] = [
        'nombre' => $persona['nombre'],
        'email'  => $persona['correo'],
        'rol'    => $persona['rol'],
        'estado' => $estado_envio,
        'msg'    => $mensaje_detalle
    ];

    // Pequeña pausa para no saturar si son muchos (opcional)
    usleep(100000); // 0.1 segundos
}

// 6. GUARDAR RESULTADOS EN SESIÓN Y VOLVER
$_SESSION['resultados_envio'] = $resultados;

header("Location: ver_estado_resultado.php");
exit;
