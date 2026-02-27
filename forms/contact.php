<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga del autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verificar Captcha
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = "6LezCHosAAAAAMqFQiUQIhThPnBckk1N8d3QeBOY";
    $recaptcha_response = $_POST['recaptcha_response'] ?? '';

    $response = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"] || $responseKeys["score"] < 0.5) {
        echo "error: Captcha inválido. Intente de nuevo.";
        exit;
    }

    // 2. Sanitizar datos
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = nl2br(htmlspecialchars($_POST['message'] ?? ''));

    if (!$email) {
        echo "error: Email inválido";
        exit;
    }

    // 3. Configurar PHPMailer
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN SMTP (HOSTINGER) ---
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';             // Servidor SMTP de Hostinger
        $mail->SMTPAuth = true;
        // 👇 REEMPLAZA ESTOS DATOS CON TUS CREDENCIALES DE HOSTINGER
        $mail->Username = 'contacto@enzoamarilla.dev';      // Tu correo creado en Hostinger
        $mail->Password = 'TU_CONTRASEÑA_DE_HOSTINGER';    // Tu contraseña de ese correo
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // SSL
        $mail->Port = 465;                              // Puerto para SSL

        // --- DESTINATARIOS ---
        // Importante: El 'setFrom' debe ser tu correo de Hostinger para evitar spam
        $mail->setFrom('contacto@enzoamarilla.dev', 'Web Portfolio - ' . $name);
        $mail->addAddress('enzo100amarilla@gmail.com');       // Tu Gmail principal

        // Esto permite que cuando le des a "Responder" en Gmail, le respondas al cliente
        $mail->addReplyTo($email, $name);

        // --- CONTENIDO DEL MAIL ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Mensaje desde la Web: $subject";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; border: 1px solid #ddd; padding: 20px;'>
                <h2 style='color: #333;'>Nuevo mensaje de contacto</h2>
                <p><strong>Nombre:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Asunto:</strong> $subject</p>
                <hr>
                <p><strong>Mensaje:</strong><br>$message</p>
            </div>
        ";
        $mail->AltBody = "Nombre: $name\nEmail: $email\nAsunto: $subject\nMensaje: $message";

        $mail->send();
        echo "OK"; // El JS de tu web busca este string para confirmar éxito

    } catch (Exception $e) {
        // En producción podrías querer un mensaje más genérico, pero esto ayuda a debuguear
        echo "error: No se pudo enviar el correo. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "error: Método no permitido";
}
?>