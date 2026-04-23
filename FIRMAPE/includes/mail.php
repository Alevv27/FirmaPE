<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

function enviarCodigo($correo, $codigo) {

    $mail = new PHPMailer(true);

    try {
        // 🔐 CONFIGURACIÓN SMTP (SIN DEBUG)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'kzapataol11@ucvvirtual.edu.pe'; // 👉 tu correo real
        $mail->Password = 'ewcpsevzyznflvog';        // 👉 clave de aplicación SIN espacios

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // 📧 DATOS DEL CORREO
        $mail->setFrom('kzapataol11@ucvvirtual.edu.p', 'RECUPERACION DE CONTRASEÑA');
        $mail->addAddress($correo);

        $mail->addReplyTo('TU_CORREO@gmail.com');

        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña';

        $mail->Body = "
        <div style='font-family:Arial; text-align:center;'>
            <h2 style='color:skyblue;'>Recuperación de contraseña</h2>
            <p>Tu código de verificación es:</p>
            <h1 style='color:black;'>$codigo</h1>
            <p>No compartas este código con nadie.</p>
        </div>
        ";

        $mail->AltBody = "Tu código es: $codigo";

        return $mail->send();

    } catch (Exception $e) {
        // ❌ NO muestra errores en pantalla (modo limpio)
        return false;
    }
}