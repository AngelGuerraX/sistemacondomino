<?php
// ==========================================================================
// 1. SESIÓN Y BASE DE DATOS
// ==========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("../../bd.php");

// 2. LIBRERÍAS
require '../../libs/PHPMailer/src/Exception.php';
require '../../libs/PHPMailer/src/PHPMailer.php';
require '../../libs/PHPMailer/src/SMTP.php';
require '../../libs/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

// ==========================================================================
// 3. VALIDAR DATOS (Solo necesitamos el ID del PAGO)
// ==========================================================================
$id_pago = $_GET['id_pago'] ?? '';

if (!$id_pago) {
    die("❌ Error: No se especificó el pago a enviar.");
}

// ==========================================================================
// 4. OBTENER CREDENCIALES DEL EMISOR (TU USUARIO)
// ==========================================================================
$usuario_sesion = $_SESSION['usuario'];
$id_usuario_sesion = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

$stmt_user = $conexion->prepare("SELECT correo, correopass FROM tbl_usuarios WHERE usuario = :usu");
$stmt_user->execute([':usu' => $usuario_sesion]);
$emisor = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (empty($emisor['correo']) || empty($emisor['correopass'])) {
    die("❌ Error: Tu usuario no tiene configurado el correo o la contraseña de aplicación.");
}

// --------------------------------------------------------------------------
// CONFIGURACIÓN ESPECIAL DE BREVO (Igual que en factura individual)
// --------------------------------------------------------------------------
$smtp_host       = 'smtp-relay.brevo.com';
$smtp_user_login = '9f763d001@smtp-brevo.com'; // <--- TU USUARIO TÉCNICO
$smtp_password   = $emisor['correopass'];      // La clave xsmtp... de la BD
$email_remitente = $emisor['correo'];          // Tu Hotmail real
$smtp_port       = 587;
$smtp_secure     = 'tls';

// ==========================================================================
// 5. OBTENER DATOS DEL DESTINATARIO Y DETALLES
// ==========================================================================
// Buscar datos del propietario
$stmt = $conexion->prepare("
    SELECT p.*, a.correo as correo_prop, a.condominos, a.apto
    FROM tbl_pagos p 
    JOIN tbl_aptos a ON p.id_apto = a.id 
    WHERE p.id_pago = :id
");
$stmt->execute([':id' => $id_pago]);
$datos_destino = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback inquilino
if (!$datos_destino) {
    $stmt = $conexion->prepare("
        SELECT p.*, i.correo as correo_prop, i.nombre as condominos, a.apto
        FROM tbl_pagos_inquilinos p 
        JOIN tbl_inquilinos i ON p.id_inquilino = i.id
        JOIN tbl_aptos a ON i.id_apto = a.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id_pago]);
    $datos_destino = $stmt->fetch(PDO::FETCH_ASSOC);
}

$email_cliente = $datos_destino['correo_prop'] ?? '';
$nombre_cliente = $datos_destino['condominos'] ?? 'Cliente';
$numero_apto = $datos_destino['apto'] ?? '';

if (empty($email_cliente)) die("❌ Error: El cliente no tiene correo registrado.");

// Obtener TODOS los items pagados en esta transacción
$stmt_detalles = $conexion->prepare("SELECT * FROM tbl_pagos_detalle WHERE id_pago = :id");
$stmt_detalles->execute([':id' => $id_pago]);
$lista_detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================================
// 6. GENERAR PDFs Y PREPARAR ENVÍO
// ==========================================================================

$mail = new PHPMailer(true);

try {
    // A) CONFIGURACIÓN SMTP (BREVO)
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user_login; // Usuario técnico
    $mail->Password   = $smtp_password;   // Clave xsmtp
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port       = $smtp_port;
    $mail->CharSet    = 'UTF-8';

    // Remitente (Tu Hotmail real)
    $mail->setFrom($email_remitente, 'Administracion Condominio');
    $mail->addAddress($email_cliente, $nombre_cliente);

    // ACTIVAMOS MODO ENVÍO
    $modo_envio = true;

    // B) GENERAR PDF DEL RECIBO PRINCIPAL (RESUMEN)
    ob_start();
    $_GET['id_pago'] = $id_pago;
    $_GET['txID'] = $datos_destino['id_apto'] ?? '';

    include("plantilla_recibo_paquete.php");
    $html_recibo = ob_get_clean();
    ob_clean();

    if (!empty($html_recibo)) {
        $dompdf_recibo = new Dompdf();
        $options = $dompdf_recibo->getOptions();
        $options->set(['isRemoteEnabled' => true]);
        $dompdf_recibo->setOptions($options);
        $dompdf_recibo->loadHtml($html_recibo);
        $dompdf_recibo->setPaper('letter', 'portrait');
        $dompdf_recibo->render();
        $mail->addStringAttachment($dompdf_recibo->output(), "Recibo_General_$id_pago.pdf");
    }

    // C) GENERAR PDFs INDIVIDUALES (BUCLE)
    foreach ($lista_detalles as $d) {
        if ($d['tipo_deuda'] == 'adelanto') continue;

        $_GET = []; // Limpiar GET
        $html_capturado = "";

        ob_start();

        if ($d['tipo_deuda'] == 'ticket') {
            $t = $conexion->prepare("SELECT * FROM tbl_tickets WHERE id=:id");
            $t->execute([':id' => $d['id_deuda']]);
            $ticket = $t->fetch(PDO::FETCH_ASSOC);

            $_GET['id_apto'] = $ticket['id_apto'];
            $_GET['mes'] = $ticket['mes'];
            $_GET['anio'] = $ticket['anio'];
            include("factura_mantenimiento.php");
        } elseif ($d['tipo_deuda'] == 'gas') {
            $t = $conexion->prepare("SELECT * FROM tbl_gas WHERE id=:id");
            $t->execute([':id' => $d['id_deuda']]);
            $gas = $t->fetch(PDO::FETCH_ASSOC);

            $_GET['id_apto'] = $gas['id_apto'];
            $_GET['mes'] = $gas['mes'];
            $_GET['anio'] = $gas['anio'];
            include("factura_gas.php");
        } elseif ($d['tipo_deuda'] == 'cuota') {
            $_GET['id'] = $d['id_deuda'];
            include("factura_cuota.php");
        }

        $html_capturado = ob_get_clean();
        ob_clean();

        if (!empty($html_capturado)) {
            $dompdf = new Dompdf();
            $options = $dompdf->getOptions();
            $options->set(['isRemoteEnabled' => true]);
            $dompdf->setOptions($options);
            $dompdf->loadHtml($html_capturado);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();

            $nombre_adjunto = "Factura_" . ucfirst($d['tipo_deuda']) . "_" . $d['id_deuda'] . ".pdf";
            $mail->addStringAttachment($dompdf->output(), $nombre_adjunto);
        }
    }

    // 7. CUERPO DEL CORREO Y ENVÍO
    $mail->isHTML(true);
    $asunto_correo = "Comprobantes de Pago - Apto " . $numero_apto;
    $mail->Subject = $asunto_correo;
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h3 style='color: #0d6efd;'>Hola, $nombre_cliente</h3>
            <p>Hemos recibido su pago correctamente.</p>
            <p>Adjunto a este correo encontrará:</p>
            <ul>
                <li>El <strong>Recibo de Caja</strong> general.</li>
                <li>El detalle de las <strong>Facturas</strong> canceladas/abonadas.</li>
            </ul>
            <p>Gracias por estar al día.</p>
            <hr>
            <p style='font-size: 12px; color: #666;'>Administración del Condominio.</p>
        </div>
    ";

    $mail->send();

    // =================================================================
    // 8. GUARDAR EN HISTORIAL
    // =================================================================
    $sql_log = "INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado) 
                VALUES (:idp, :dest, :asunto, :user, 'Enviado')";

    $stmt_log = $conexion->prepare($sql_log);
    $stmt_log->execute([
        ':idp' => $id_pago,
        ':dest' => $email_cliente,
        ':asunto' => $asunto_correo,
        ':user' => $id_usuario_sesion
    ]);

    echo "<script>
        alert('✅ Paquete de facturas enviado correctamente a: $email_cliente'); 
        window.history.back();
    </script>";
} catch (Exception $e) {

    // Si falla, guardamos el error en el historial
    $error_msg = ($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
    $asunto_fail = "Intento Fallido - Apto " . $numero_apto;

    try {
        $sql_log = "INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado, mensaje_error) 
                    VALUES (:idp, :dest, :asunto, :user, 'Error', :error)";

        $stmt_log = $conexion->prepare($sql_log);
        $stmt_log->execute([
            ':idp' => $id_pago,
            ':dest' => $email_cliente,
            ':asunto' => $asunto_fail,
            ':user' => $id_usuario_sesion,
            ':error' => substr($error_msg, 0, 255)
        ]);
    } catch (Exception $ex) {
        // Ignorar si falla el log para no hacer loop
    }

    echo "<script>
        alert('❌ Error al enviar: " . addslashes($error_msg) . "'); 
        window.history.back();
    </script>";
}
