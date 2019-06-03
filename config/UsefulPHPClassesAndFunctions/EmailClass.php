<?php

require_once(__DIR__ .'/../PHPMailer/class.phpmailer.php');

class Email {    
    public function __construct($Recipient, $Message, $Subject) {        
        $mail = new PHPMailer(true); // defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
        try {
            $mail->IsSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'smtp.mandrillapp.com';                 // Specify main and backup server
            $mail->Port = 587;                                    // Set the SMTP port
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'REMOVED';            // SMTP username
            $mail->Password = 'REMOVED';           // SMTP password
            $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
            $mail->From = 'REMOVED';
            $mail->FromName = 'Origin';
            $mail->AddAddress($Recipient);                        // Add a recipient
            $mail->IsHTML(true);                                  // Set email format to HTML
            $mail->Subject = $Subject;
            $mail->Body    = $Message;
            $mail->AltBody = $Message;
            $mail->Send();      
        } catch (phpmailerException $e) {
            throw new EmailException('Can not send PHPMailer email. ' . $e->errorMessage());
        }
    }
}