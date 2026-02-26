<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar captcha
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = "6LdmFs8rAAAAACrnuI5Ym3cxLwOr2fqpxiSz0AZ2"; // 👈 tu clave secreta
    $recaptcha_response = $_POST['recaptcha_response'];

    // Hacer la petición a Google
    $response = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $responseKeys = json_decode($response, true);

    if(!$responseKeys["success"] || $responseKeys["score"] < 0.5){
        echo "error: Captcha inválido. Intente de nuevo.";
        exit;
    }

    $name    = htmlspecialchars($_POST['name']);
    $email   = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars($_POST['subject']);
    $message = nl2br(htmlspecialchars($_POST['message']));

    if (!$email) {
        echo "error: Email inválido";
        exit;
    }

    $to = "enzo100amarilla@gmail.com"; 
    $subjectMail = "Asunto: $subject";

    $body = "
        <h3>Nuevo mensaje desde la web</h3>
        <p><strong>Nombre:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Mensaje:</strong><br>$message</p>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";

    if (mail($to, $subjectMail, $body, $headers)) {
        echo "OK"; // 👈 el JS busca esto
    } else {
        echo "error: No se pudo enviar el mensaje.";
    }
} else {
    echo "error: Método no permitido";
}
?>