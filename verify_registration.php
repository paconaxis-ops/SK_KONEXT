
<?php

include("db.php");

session_start();

$message = "";
$message_type = "";

if(!isset($_SESSION['pending_account_id'])){

    header("Location: register.php");
    exit();

}

$account_id = (int)$_SESSION['pending_account_id'];


/* =========================================
   GET ACCOUNT
========================================= */

$query = mysqli_query($conn,"
    SELECT *
    FROM applicant_accounts
    WHERE id=$account_id
    LIMIT 1
");

if(!$query || mysqli_num_rows($query) == 0){

    unset($_SESSION['pending_account_id']);

    header("Location: register.php");
    exit();

}

$account = mysqli_fetch_assoc($query);


/* =========================================
   VERIFY EMAIL OTP
========================================= */

if(isset($_POST['verify'])){

    $otp = trim($_POST['otp']);

    if(!preg_match('/^[0-9]{6}$/',$otp)){

        $message = "Please enter the 6-digit verification code.";
        $message_type = "danger";

    }else{

        $otp_safe = mysqli_real_escape_string(
            $conn,
            $otp
        );

        /* =========================================
           CHECK OTP + EXPIRATION
        ========================================= */

        $verify = mysqli_query($conn,"
            SELECT id
            FROM applicant_accounts
            WHERE id=$account_id
            AND otp_code='$otp_safe'
            AND otp_expires >= NOW()
            AND is_verified=0
            LIMIT 1
        ");

        if($verify && mysqli_num_rows($verify) > 0){

            /* =========================================
               ACCOUNT VERIFIED
            ========================================= */

            $update = mysqli_query($conn,"
                UPDATE applicant_accounts
                SET
                    is_verified=1,
                    otp_code=NULL,
                    otp_expires=NULL
                WHERE id=$account_id
                AND is_verified=0
            ");

            if($update && mysqli_affected_rows($conn) > 0){

                unset($_SESSION['pending_account_id']);

                header("Location: login.php?registered=1");
                exit();

            }else{

                $message = "Unable to verify your account. Please try again.";
                $message_type = "danger";

            }

        }else{

            $message = "Invalid or expired verification code.";
            $message_type = "danger";

        }

    }

}

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<title>Verify Email - SK KONEXT</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;

    background:
    linear-gradient(
        rgba(0,0,0,.75),
        rgba(0,0,0,.75)
    ),
    url('https://www.rappler.com/tachyon/r3-assets/612F469A6EA84F6BAE882D2B94A4B421/img/17D39E6963C54F1791BEDB722E89E798/sk-new-logo-20160722.jpg?resize=640%2C360&zoom=1');

    background-size:cover;
    background-position:center;

    color:white;
    padding:30px;
}

.verify-card{
    width:100%;
    max-width:450px;
    padding:40px;

    background:rgba(255,255,255,.08);
    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,.15);
    border-radius:25px;

    text-align:center;
}

.icon{
    font-size:55px;
    color:#38bdf8;
    margin-bottom:20px;
}

.verify-card h2{
    font-weight:700;
    margin-bottom:10px;
}

.subtitle{
    opacity:.7;
    margin-bottom:15px;
    line-height:1.7;
}

.email-address{
    color:#38bdf8;
    font-weight:600;
    word-break:break-word;
    margin-bottom:25px;
}

.form-control{
    height:60px;
    border-radius:12px;
    border:none;

    background:rgba(255,255,255,.1);
    color:white;

    text-align:center;
    font-size:25px;
    letter-spacing:8px;
}

.form-control:focus{
    background:rgba(255,255,255,.15);
    color:white;
    box-shadow:0 0 0 2px #38bdf8;
}

.form-control::placeholder{
    color:rgba(255,255,255,.5);
}

.verify-btn{
    width:100%;
    padding:14px;
    margin-top:20px;

    border:none;
    border-radius:12px;

    background:linear-gradient(
        135deg,
        #00c6ff,
        #0072ff
    );

    color:white;
    font-weight:600;
    cursor:pointer;
}

.alert{
    border-radius:12px;
}

</style>

</head>

<body>

<div class="verify-card">

    <div class="icon">
        <i class="fa-solid fa-envelope-circle-check"></i>
    </div>

    <h2>Verify Email Address</h2>

    <div class="subtitle">
        Enter the 6-digit verification code sent to your email address.
    </div>

    <div class="email-address">
        <?= htmlspecialchars($account['email']) ?>
    </div>

    <?php if($message != ""){ ?>

        <div class="alert alert-<?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php } ?>

    <form method="POST">

        <input
            type="text"
            name="otp"
            class="form-control"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            autocomplete="one-time-code"
            placeholder="••••••"
            required
        >

        <button
            type="submit"
            name="verify"
            class="verify-btn"
        >
            <i class="fa-solid fa-circle-check"></i>
            Verify Email
        </button>

    </form>

    <div style="margin-top:20px;opacity:.6;font-size:13px;">
        The verification code expires after 5 minutes.
    </div>

    <a
        href="register.php"
        style="display:block;margin-top:15px;color:#38bdf8;"
    >
        ← Back to Registration
    </a>

</div>

</body>
</html>
