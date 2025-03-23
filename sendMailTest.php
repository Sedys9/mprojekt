<?php
// Načti autoloader z Composeru
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Nastavení SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.seznam.cz'; // Např. Gmail SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'help.sportnutrition@seznam.cz';  // Zadej svůj e-mail
    $mail->Password   = 'HeLpMatProjekt20254E'; // Zadej heslo (v případě Gmail doporučuji App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Použij TLS
    $mail->Port       = 587;

    // Nastavení kódování na UTF-8
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    // Nastavení odesílatele a příjemce
    $mail->setFrom('help.sportnutrition@seznam.cz', 'SportNutrition');
    $mail->addAddress('matyas.seda@seznam.cz', 'Adresát');

    // Předmět a tělo zprávy
    $mail->Subject = 'Testovací e-mail';
    $mail->Body    = 'Toto je ukázkový e-mail odeslaný přes PHPMailer.';

    // Odeslání e-mailu
    $mail->send();
    echo 'E-mail byl úspěšně odeslán!';
} catch (Exception $e) {
    echo "Chyba při odeslání e-mailu: {$mail->ErrorInfo}";
}
