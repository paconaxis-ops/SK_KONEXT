
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

/* =========================================
   SK KONEXT EMAIL CONFIGURATION
========================================= */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);

define('SMTP_USERNAME', 'axispacon@gmail.com');

/*
   ILAGAY DITO ANG 16-CHARACTER GOOGLE APP PASSWORD.
   HUWAG ILAGAY ANG NORMAL GMAIL PASSWORD.
   Alisin ang spaces kapag kinopya.
*/

define('SMTP_PASSWORD', 'mycawmmlmrvxtyxr');


/* =========================================
   SEND OTP EMAIL
========================================= */

function sendOtpEmail($recipientEmail, $recipientName, $otp)
{
    $mail = new PHPMailer(true);

    try {

        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->CharSet = 'UTF-8';

        // SENDER
        $mail->setFrom(
            SMTP_USERNAME,
            'SK KONEXT'
        );

        // RECIPIENT
        $mail->addAddress(
            $recipientEmail,
            $recipientName
        );

        // EMAIL CONTENT
        $mail->isHTML(true);
        $mail->Subject = 'SK KONEXT - Email Verification Code';

        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 25px; border: 1px solid #ddd; border-radius: 10px;">

                <h2 style="color: #14532d;">SK KONEXT</h2>

                <p>Hello <strong>' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>

                <p>Thank you for registering your SK KONEXT account.</p>

                <p>Use the verification code below to verify your email address:</p>

                <div style="background: #f0fdf4; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;">

                    <h1 style="color: #166534; letter-spacing: 8px; margin: 0;">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</h1>

                </div>

                <p>This verification code will expire in <strong>5 minutes</strong>.</p>

                <p>If you did not request this code, please ignore this email.</p>

                <hr>

                <p style="font-size: 12px; color: #777;">
                    SK KONEXT - Residence Profiling and Budget Transparency and Monitoring System
                </p>

            </div>
        ';

        $mail->AltBody = "Hello $recipientName. Your SK KONEXT verification code is $otp. This code expires in 5 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log('SK KONEXT Email Error: ' . $mail->ErrorInfo);

        return false;
    }
}
