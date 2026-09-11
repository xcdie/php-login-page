<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOtpEmail($toEmail, $otp) {
    $mail = new PHPMailer(true); 
    try {
        // Server Settings
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->Host       = 'smtp.example.com'; 
        $mail->Username   = 'jamlickmarsh400@gmail.com';
        $mail->Password   = '20590770'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('jamlickmarsh400@gmail.com', 'jamlick');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Security OTP Verification Code';
        $mail->Body    = "Your verification code is: <b>$otp</b>. It expires in 10 minutes.";
        $mail->AltBody = "Your verification code is: $otp. It expires in 10 minutes.";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
