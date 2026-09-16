<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

function sendOTP($recipient, $otp)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($recipient);

        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';

        $mail->Body = "
            <h2>Email Verification</h2>
            <p>Your verification code is:</p>
            <h1>$otp</h1>
            <p>This code expires in 10 minutes.</p>
        ";

        $mail->AltBody = "Your verification code is: $otp";

        return $mail->send();

    } catch (Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo);
        return false;
    }
}