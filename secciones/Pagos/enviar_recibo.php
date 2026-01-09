<?php
session_start();

include("../../bd.php");
// Ajusta estas rutas según donde hayas puesto la carpeta PHPMailer y Dompdf
require '../../libs/PHPMailer/src/Exception.php';
require '../../libs/PHPMailer/src/PHPMailer.php';
require '../../libs/PHPMailer/src/SMTP.php';
require '../../libs/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

if (isset($_GET['id_pago'])) {
    $id_pago = $_GET['id_pago'];
    $id_usuario = $_SESSION['usuario']; // Asumo que esto es el ID del usuario logueado

    // 1. OBTENER DATOS DEL EMISOR (TU USUARIO)// CÓDIGO CORREGIDO (Buscar por nombre de usuario)
    $stmt_user = $conexion->prepare("SELECT correo, correopass FROM tbl_usuarios WHERE usuario = :id");
    $stmt_user->execute([':id' => $id_usuario]);
    $emisor = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$emisor || empty($emisor['correo']) || empty($emisor['correopass'])) {
        die("❌ Error: El usuario no tiene correo o contraseña configurada.");
    }

    // 2. DETECTAR CONFIGURACIÓN SMTP AUTOMÁTICA
    $host = '';
    $port = 587;
    $smtp_secure = 'tls';

    if (strpos($emisor['correo'], 'gmail.com') !== false) {
        $host = 'smtp.gmail.com';
        $port = 465;
        $smtp_secure = 'ssl';
    } elseif (strpos($emisor['correo'], 'outlook.com') !== false || strpos($emisor['correo'], 'hotmail.com') !== false) {
        $host = 'smtp.office365.com';
        $port = 587;
        $smtp_secure = 'tls';
    } else {
        die("❌ Error: Proveedor de correo no reconocido (Solo Gmail o Outlook).");
    }

    // 3. OBTENER DATOS DEL PAGO Y DEL APARTAMENTO
    // (Necesitamos el correo del propietario)
    $stmt_pago = $conexion->prepare("
        SELECT p.*, a.apto, a.correo as correo_propietario, a.condominos 
        FROM tbl_pagos p 
        JOIN tbl_aptos a ON p.id_apto = a.id 
        WHERE p.id_pago = :id
    ");
    $stmt_pago->execute([':id' => $id_pago]);
    $datos_pago = $stmt_pago->fetch(PDO::FETCH_ASSOC);

    if (!$datos_pago) {
        // Intento buscar en inquilinos si no es propietario
        $stmt_pago = $conexion->prepare("
            SELECT p.*, i.correo as correo_propietario, i.nombre as condominos, a.apto
            FROM tbl_pagos_inquilinos p 
            JOIN tbl_inquilinos i ON p.id_inquilino = i.id 
            JOIN tbl_aptos a ON i.id_apto = a.id
            WHERE p.id = :id
        ");
        $stmt_pago->execute([':id' => $id_pago]);
        $datos_pago = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    }

    if (empty($datos_pago['correo_propietario'])) {
        die("❌ Error: El apartamento/inquilino no tiene un correo registrado.");
    }

    // 4. GENERAR EL PDF EN MEMORIA (Reutilizando tu lógica de ver_recibo)
    // Aquí capturamos el HTML del recibo para convertirlo
    ob_start();
    // Definimos variables para que el include funcione
    $id_pago_pdf = $id_pago;
    // Truco: Incluimos el contenido del recibo. 
    // Asegúrate de crear un archivo 'plantilla_recibo.php' con SOLO el HTML del recibo
    // O copia y pega el código HTML de ver_recibo.php aquí abajo dentro del buffer.
    include("plantilla_recibo_body.php");
    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $pdf_content = $dompdf->output(); // PDF en variable, no en archivo

    // 5. ENVIAR CORREO CON PHPMAILER
    $mail = new PHPMailer(true);

    try {
        // Configuración del Servidor
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $emisor['correo'];
        $mail->Password   = $emisor['correopass'];
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port       = $port;

        // Destinatarios
        $mail->setFrom($emisor['correo'], 'Administracion Condominio');
        $mail->addAddress($datos_pago['correo_propietario'], $datos_pago['condominos']);

        // Adjuntos
        $mail->addStringAttachment($pdf_content, 'Recibo_Pago_' . $id_pago . '.pdf');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recibo de Pago - Apto ' . $datos_pago['apto'];
        $mail->Body    = '
            <h3>Hola, ' . $datos_pago['condominos'] . '</h3>
            <p>Adjunto encontrará su recibo de pago correspondiente a la fecha ' . date('d/m/Y', strtotime($datos_pago['fecha_pago'])) . '.</p>
            <p>Monto: <strong>RD$ ' . number_format($datos_pago['monto'], 2) . '</strong></p>
            <br>
            <p>Atentamente,<br>La Administración</p>
        ';

        $mail->send();

        // 6. GUARDAR LOG ÉXITO
        $stmt_log = $conexion->prepare("INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado) VALUES (:idp, :dest, :asu, :user, 'Enviado')");
        $stmt_log->execute([
            ':idp' => $id_pago,
            ':dest' => $datos_pago['correo_propietario'],
            ':asu' => $mail->Subject,
            ':user' => $id_usuario
        ]);

        echo "<script>alert('✅ Correo enviado correctamente a: " . $datos_pago['correo_propietario'] . "'); window.history.back();</script>";
    } catch (Exception $e) {
        // 7. GUARDAR LOG ERROR
        $stmt_log = $conexion->prepare("INSERT INTO tbl_historial_correos (id_pago, destinatario, asunto, enviado_por, estado, mensaje_error) VALUES (:idp, :dest, :asu, :user, 'Error', :err)");
        $stmt_log->execute([
            ':idp' => $id_pago,
            ':dest' => $datos_pago['correo_propietario'],
            ':asu' => 'Intento de envío',
            ':user' => $id_usuario,
            ':err' => $mail->ErrorInfo
        ]);

        echo "❌ El mensaje no pudo ser enviado. Error Mailer: {$mail->ErrorInfo}";
    }
}
