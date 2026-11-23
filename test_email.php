
<?php

require_once 'email_config.php';

$toEmail = 'axispacon@gmail.com';
$toName  = 'SK KONEXT Administrator';
$otp     = '123456';

$result = sendOtpEmail($toEmail, $toName, $otp);

if ($result === true) {

    echo "SUCCESS! Email sent successfully.";

} else {

    echo "FAILED! Email was not sent. Please check your SMTP configuration and PHP error log.";

}
