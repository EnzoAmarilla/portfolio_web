<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Carga del autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verificar Captcha
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = $_ENV['RECAPTCHA_SECRET'] ?? '';
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
        // --- CONFIGURACIÓN SMTP ---
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 465;

        // --- DESTINATARIOS ---
        $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'contacto@enzoamarilla.dev';
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Web Portfolio';

        $mail->setFrom($fromEmail, $fromName . ' - ' . $name);
        $mail->addAddress($_ENV['SMTP_TO_EMAIL'] ?? 'enzo100amarilla@gmail.com');

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
        echo "OK";

    } catch (Exception $e) {
        echo "error: No se pudo enviar el correo.";
    }
} else {
    echo "error: Método no permitido";
}
?>