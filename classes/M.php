<?php

namespace PHPMailer\src\PHPMailer;
namespace PHPMailer\src\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

class M
{
    public function sendEmail($address,$content){ try
    {
        $mail = new PHPMailer;
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'karol.kurowski.2001@gmail.com'; // SMTP username
        $mail->SMTPSecure = 'tsl'; // Enable encryption, 'ssl' also accepted
        $mail->From = 'karol.kurowski.2001@gmail.com';
        $mail->addAddress($address);
        $mail->WordWrap = 40;
        $mail->isHTML(true);
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Port = 587;
// Add a recipient
// Set word wrap to 40 characters // Set email format to HTML
        $mail->Subject = 'Your security code';
        $mail->Body = 'This is your authentication code <B>'.$content.'</B>'; $mail->AltBody = 'This is your authentication code '.$content.'';
        if (!$mail->send())
        {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo; }
        else {
            echo 'Message has been sent';
        } }catch
    (\Exception $e){
        echo "Exception &nbsp" . $e->getMessage();
    } }
}