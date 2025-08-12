<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// require 'includes/PHPMailer/PHPMailer.php'; // If manual
// require 'includes/PHPMailer/SMTP.php';
// require 'includes/PHPMailer/Exception.php';

function sendReplyMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';        // For Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';     // Your Gmail
        $mail->Password = 'wyte veku tlfx bvsf';        // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'Conquer High School');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
