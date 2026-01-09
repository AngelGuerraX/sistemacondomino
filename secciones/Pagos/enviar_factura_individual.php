<?php
// ==========================================================================
// 1. CONFIGURACIÓN INICIAL Y LIBRERÍAS
// ==========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("../../bd.php");

require '../../libs/PHPMailer/src/Exception.php';
require '../../libs/PHPMailer/src/PHPMailer.php';
require '../../libs/PHPMailer/src/SMTP.php';
require '../../libs/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

// ==========================================================================
// 2. VALIDACIÓN DE DATOS
// ==========================================================================
$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? '';
$id_pago = $_GET['id_pago'] ?? '';

if (!$tipo || !$id || !$id_pago) {
    die("❌ Error: Faltan datos para procesar el envío.");
}

// ==========================================================================
// 3. OBTENER CREDENCIALES
// ==========================================================================
$usuario_sesion = $_SESSION['usuario'];
$id_usuario_sesion = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

$stmt_user = $conexion->prepare("SELECT correo, correopass FROM tbl_usuarios WHERE usuario = :usu");
$stmt_user->execute([':usu' => $usuario_sesion]);
$emisor = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (empty($emisor['correo']) || empty($emisor['correopass'])) {
    die("❌ Error: Falta configurar el correo o la Clave SMTP en el perfil del usuario.");
}

// --------------------------------------------------------------------------
// CONFIGURACIÓN ESPECIAL DE BREVO
// --------------------------------------------------------------------------
// Aquí separamos el usuario de conexión del correo remitente
$smtp_user_login = '9f763d001@smtp-brevo.com'; // <--- TU USUARIO TÉCNICO DE BREVO
$smtp_password   = $emisor['correopass'];      // La clave xsmtp... de la BD
$email_remitente = $emisor['correo'];          // Tu Hotmail real de la BD

// ==========================================================================
// 4. OBTENER DATOS CLIENTE
// ==========================================================================
$stmt = $conexion->prepare("SELECT p.*, a.correo as correo_prop, a.condominos, a.apto, a.id as id_apto_real FROM tbl_pagos p JOIN tbl_aptos a ON p.id_apto = a.id WHERE p.id_pago = :id");
$stmt->execute([':id' => $id_pago]);
$datos_destino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos_destino) {
    $stmt = $conexion->prepare("SELECT p.*, i.correo as correo_prop, i.nombre as condominos, a.apto, a.id as id_apto_real FROM tbl_pagos_inquilinos p JOIN tbl_inquilinos i ON p.id_inquilino = i.id JOIN tbl_aptos a ON i.id_apto = a.id WHERE p.id = :id");
    $stmt->execute([':id' => $id_pago]);
    $datos_destino = $stmt->fetch(PDO::FETCH_ASSOC);
}

$email_cliente = $datos_destino['correo_prop'] ?? '';
$nombre_cliente = $datos_destino['condominos'] ?? 'Estimado Cliente';
$numero_apto = $datos_destino['apto'] ?? '';
$id_apto_real = $datos_destino['id_apto_real'] ?? 0;

if (empty($email_cliente)) die("❌ Error: El cliente no tiene correo registrado.");

// ==========================================================================
// 5. GENERAR PDF
// ==========================================================================
$modo_envio = true;
ob_start();

$asunto_email = "Factura";

if ($tipo == 'ticket') {
    $t = $conexion->prepare("SELECT * FROM tbl_tickets WHERE id=:id");
    $t->execute([':id' => $id]);
    $ticket = $t->fetch(PDO::FETCH_ASSOC);
    $_GET['id_apto'] = $id_apto_real;
    $_GET['mes'] = $ticket['mes'];
    $_GET['anio'] = $ticket['anio'];
    include("factura_mantenimiento.php");
    $asunto_email = "Factura Mantenimiento " . $ticket['mes'] . " " . $ticket['anio'];
} elseif ($tipo == 'gas') {
    $t = $conexion->prepare("SELECT * FROM tbl_gas WHERE id=:id");
    $t->execute([':id' => $id]);
    $gas = $t->fetch(PDO::FETCH_ASSOC);
    $_GET['id_apto'] = $id_apto_real;
    $_GET['mes'] = $gas['mes'];
    $_GET['anio'] = $gas['anio'];
    include("factura_gas.php");
    $asunto_email = "Factura Gas " . $gas['mes'] . " " . $gas['anio'];
} elseif ($tipo == 'cuota') {
    $_GET['id'] = $id;
    include("factura_cuota.php");
    $asunto_email = "Factura Cuota Extra";
}

$html_capturado = ob_get_clean();
ob_clean();

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(['isRemoteEnabled' => true]);
$dompdf->setOptions($options);
$dompdf->loadHtml($html_capturado);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$pdf_content = $dompdf->output();

// ==========================================================================
// 6. ENVIAR CORREO
// ==========================================================================
$mail = new PHPMailer(true);
$asunto_final = $asunto_email . " - Apto " . $numero_apto;

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;

    // AQUÍ ESTÁ EL TRUCO: Usamos variables distintas
    $mail->Username   = $smtp_user_login;  // El 9f763... para loguearse
    $mail->Password   = $smtp_password;    // La clave xsmtp...

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Y aquí usamos tu Hotmail REAL como remitente
    $mail->setFrom($email_remitente, 'Administracion Condominio');
    $mail->addAddress($email_cliente, $nombre_cliente);

    $filename = "Factura_" . ucfirst($tipo) . "_" . $id . ".pdf";
    $mail->addStringAttachment($pdf_content, $filename);

    $mail->isHTML(true);
    $mail->Subject = $asunto_final;
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h3 style='color: #0d6efd;'>Hola, $nombre_cliente</h3>
            <p>Adjunto a este correo encontrará el comprobante solicitado.</p>
            <p><strong>Detalle:</strong> " . strtoupper($tipo) . " (Apto $numero_apto)</p>
            <hr>
            <p style='font-size: 12px; color: #666;'>Sistema de Administración.</p>
        </div>
    ";

    $mail->send();

    // LOG EXITOSO
    $sql_log = "INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado) 
                VALUES (:idp, :dest, :asunto, :user, 'Enviado')";
    $stmt_log = $conexion->prepare($sql_log);
    $stmt_log->execute([':idp' => $id_pago, ':dest' => $email_cliente, ':asunto' => $asunto_final, ':user' => $id_usuario_sesion]);

    echo "<script>alert('✅ Enviado correctamente vía Brevo.'); window.history.back();</script>";
} catch (Exception $e) {
    // LOG ERROR
    $error_msg = ($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
    try {
        $sql_log = "INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado, mensaje_error) 
                    VALUES (:idp, :dest, :asunto, :user, 'Error', :error)";
        $stmt_log = $conexion->prepare($sql_log);
        $stmt_log->execute([':idp' => $id_pago, ':dest' => $email_cliente, ':asunto' => $asunto_final . " (Fallido)", ':user' => $id_usuario_sesion, ':error' => substr($error_msg, 0, 255)]);
    } catch (Exception $ex) {
    }

    echo "<script>alert('❌ Error al enviar: " . addslashes($error_msg) . "'); window.history.back();</script>";
}
