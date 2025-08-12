<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'njorogepaul5357@gmail.com';
    $mail->Password = 'mbcg yupb pndi hosd'; // App password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('conquerschool@gmail.com', 'Conquer High School');
    $mail->addAddress('youremail@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a <b>test email</b> from PHPMailer.';

    $mail->send();
    echo '✅ Email sent successfully!';
} catch (Exception $e) {
    echo '❌ Email failed: ', $mail->ErrorInfo;
}
