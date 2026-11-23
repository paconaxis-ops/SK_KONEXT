
<?php

session_start();

include("db.php");


/* =========================================================
   LOGIN LOG HELPER
========================================================= */

function saveLoginLog(
    $conn,
    $user_id,
    $fullname,
    $username,
    $role,
    $status
){

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    /* DEVICE TYPE */

    if(preg_match('/tablet|ipad/i', $user_agent)){

        $device_type = 'Tablet';

    }
    elseif(preg_match('/mobile|android|iphone|ipod/i', $user_agent)){

        $device_type = 'Mobile';

    }
    else{

        $device_type = 'Desktop';

    }


    /* BROWSER */

    if(stripos($user_agent, 'Edg/') !== false){

        $browser = 'Microsoft Edge';

    }
    elseif(stripos($user_agent, 'OPR/') !== false){

        $browser = 'Opera';

    }
    elseif(stripos($user_agent, 'Chrome/') !== false){

        $browser = 'Google Chrome';

    }
    elseif(stripos($user_agent, 'Firefox/') !== false){

        $browser = 'Mozilla Firefox';

    }
    elseif(stripos($user_agent, 'Safari/') !== false){

        $browser = 'Safari';

    }
    else{

        $browser = 'Unknown Browser';

    }


    /* OPERATING SYSTEM */

    if(stripos($user_agent, 'Windows') !== false){

        $operating_system = 'Windows';

    }
    elseif(stripos($user_agent, 'Android') !== false){

        $operating_system = 'Android';

    }
    elseif(
        stripos($user_agent, 'iPhone') !== false ||
        stripos($user_agent, 'iPad') !== false
    ){

        $operating_system = 'iOS';

    }
    elseif(stripos($user_agent, 'Mac OS') !== false){

        $operating_system = 'macOS';

    }
    elseif(stripos($user_agent, 'Linux') !== false){

        $operating_system = 'Linux';

    }
    else{

        $operating_system = 'Unknown OS';

    }


    /* SAVE TO DATABASE */

    $stmt = mysqli_prepare($conn,"
        INSERT INTO login_logs
        (
            user_id,
            fullname,
            username,
            role,
            ip_address,
            device_type,
            browser,
            operating_system,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if($stmt){

        mysqli_stmt_bind_param(
            $stmt,
            "issssssss",
            $user_id,
            $fullname,
            $username,
            $role,
            $ip_address,
            $device_type,
            $browser,
            $operating_system,
            $status
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

    }

}


/* =========================================================
   REDIRECT IF ALREADY LOGGED IN
========================================================= */

/* ADMIN / STAFF LOGIN */

if(isset($_SESSION['role'])){

    if($_SESSION['role'] == 'superadmin'){

        header("Location: superadmin/dashboard.php");

        exit();

    }

    if($_SESSION['role'] == 'president'){

        header("Location: president/dashboard.php");

        exit();

    }

    if($_SESSION['role'] == 'sk_member'){

        header("Location: skmember/dashboard.php");

        exit();

    }

}


/* =========================================================
   LOGIN SYSTEM
========================================================= */

if(isset($_POST['login'])){

    $username = trim($_POST['username']);

    $password = $_POST['password'];

    $login_success = false;


    /* =====================================================
       FIRST: CHECK ADMIN / STAFF ACCOUNT
    ===================================================== */

    $username_safe = mysqli_real_escape_string(
        $conn,
        $username
    );


    $query = mysqli_query($conn,"

        SELECT *
        FROM users
        WHERE username='$username_safe'
        AND status='active'
        LIMIT 1

    ");


    if($query && mysqli_num_rows($query) > 0){

        $user = mysqli_fetch_assoc($query);


        /*
            KEEPING YOUR EXISTING PASSWORD SYSTEM

            Your users table currently uses plain-text
            passwords, so this remains compatible.
        */

        if($password === $user['password']){

            /* =============================================
               SAVE SUCCESSFUL LOGIN LOG
            ============================================= */

            saveLoginLog(
                $conn,
                (int)$user['id'],
                $user['fullname'],
                $user['username'],
                $user['role'],
                'Success'
            );


            /* =============================================
               ADMIN SESSION
            ============================================= */

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['fullname'] = $user['fullname'];

            $_SESSION['role'] = $user['role'];

            $_SESSION['barangay_id'] = $user['barangay_id'];


            /* =============================================
               ROLE REDIRECT
            ============================================= */

            if($user['role'] == 'superadmin'){

                header(
                    "Location: superadmin/dashboard.php"
                );

                exit();

            }

            elseif($user['role'] == 'president'){

                header(
                    "Location: president/dashboard.php"
                );

                exit();

            }

            elseif($user['role'] == 'sk_member'){

                header(
                    "Location: skmember/dashboard.php"
                );

                exit();

            }

            else{

                header("Location: index.php");

                exit();

            }

        }

    }


    /* =====================================================
       SECOND: CHECK APPLICANT ACCOUNT
    ===================================================== */

    $phone = preg_replace(
        '/[\s\-]/',
        '',
        $username
    );


    /*
        Convert Philippine formats into 09XXXXXXXXX

        +639XXXXXXXXX
        639XXXXXXXXX
        09XXXXXXXXX
    */

    if(preg_match('/^\+639[0-9]{9}$/', $phone)){

        $phone = '0' . substr($phone,3);

    }

    elseif(preg_match('/^639[0-9]{9}$/', $phone)){

        $phone = '0' . substr($phone,2);

    }


    $phone_safe = mysqli_real_escape_string(
        $conn,
        $phone
    );


    $applicant_query = mysqli_query($conn,"

        SELECT *
        FROM applicant_accounts
        WHERE phone='$phone_safe'
        LIMIT 1

    ");


    if(
        $applicant_query &&
        mysqli_num_rows($applicant_query) > 0
    ){

        $applicant = mysqli_fetch_assoc(
            $applicant_query
        );


        /* =============================================
           CHECK ACCOUNT VERIFICATION
        ============================================= */

        if((int)$applicant['is_verified'] !== 1){

            $error =
                "This account has not been verified yet. Please complete SMS verification.";

        }


        /* =============================================
           CHECK PASSWORD
        ============================================= */

        elseif(
            !password_verify(
                $password,
                $applicant['password']
            )
        ){

            $error =
                "Invalid mobile number or password.";

        }


        else{

            /* =============================================
               SAVE SUCCESSFUL APPLICANT LOGIN LOG
            ============================================= */

            saveLoginLog(
                $conn,
                (int)$applicant['id'],
                $applicant['fullname'],
                $applicant['phone'],
                'Applicant',
                'Success'
            );


            /* =============================================
               APPLICANT LOGIN
            ============================================= */

            $_SESSION['applicant_id'] =
                $applicant['id'];

            $_SESSION['applicant_name'] =
                $applicant['fullname'];

            $_SESSION['applicant_phone'] =
                $applicant['phone'];

            $_SESSION['applicant_email'] =
                $applicant['email'];


            /* =============================================
               APPLY SCHOLARSHIP REDIRECT
            ============================================= */

            if(
                isset($_GET['apply']) &&
                $_GET['apply'] == '1'
            ){

                header(
                    "Location: apply.php"
                );

                exit();

            }


            /* =============================================
               NORMAL APPLICANT LOGIN
            ============================================= */

            header(
                "Location: applicant_dashboard.php"
            );

            exit();

        }

    }


    /* =====================================================
       INVALID LOGIN
    ===================================================== */

    if(!isset($error)){

        $error =
            "Invalid Username/Mobile Number or Password.";

    }


    /* =====================================================
       SAVE FAILED LOGIN ATTEMPT
    ===================================================== */

    if(!isset($login_success)){

        saveLoginLog(
            $conn,
            null,
            'Unknown Account',
            $username,
            'Unknown',
            'Failed'
        );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Login | SK KONEXT</title>


<!-- =====================================================
     GOOGLE FONT
===================================================== -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<!-- =====================================================
     FONT AWESOME
===================================================== -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<style>

/* =========================================================
   RESET
========================================================= */

*{

    margin:0;

    padding:0;

    box-sizing:border-box;

}


html{

    scroll-behavior:smooth;

}


body{

    min-height:100vh;

    font-family:'Inter',sans-serif;

    color:white;

    background:#06100b;

    overflow-x:hidden;

}


/* =========================================================
   BACKGROUND
========================================================= */

.page-bg{

    position:fixed;

    inset:0;

    overflow:hidden;

    pointer-events:none;

    z-index:0;

}


.page-bg::before{

    content:"";

    position:absolute;

    inset:0;

    background:

        radial-gradient(
            circle at 15% 20%,
            rgba(34,197,94,.16),
            transparent 28%
        ),

        radial-gradient(
            circle at 85% 80%,
            rgba(74,222,128,.10),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #06100b 0%,
            #091b10 50%,
            #06100b 100%
        );

}


/* =========================================================
   GRID
========================================================= */

.grid-background{

    position:absolute;

    inset:0;

    opacity:.08;

    background-image:

        linear-gradient(
            rgba(74,222,128,.25) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(74,222,128,.25) 1px,
            transparent 1px
        );

    background-size:55px 55px;

}


/* =========================================================
   FLOATING GLOW
========================================================= */

.glow{

    position:absolute;

    border-radius:50%;

    filter:blur(2px);

    animation:floatGlow 9s ease-in-out infinite;

}


.glow-one{

    width:360px;

    height:360px;

    top:-160px;

    left:-120px;

    background:

        radial-gradient(
            circle,
            rgba(34,197,94,.18),
            transparent 68%
        );

}


.glow-two{

    width:420px;

    height:420px;

    right:-170px;

    bottom:-190px;

    background:

        radial-gradient(
            circle,
            rgba(74,222,128,.13),
            transparent 68%
        );

    animation-delay:2s;

}


@keyframes floatGlow{

    0%{

        transform:translate(0,0);

    }

    50%{

        transform:translate(20px,25px);

    }

    100%{

        transform:translate(0,0);

    }

}


/* =========================================================
   PAGE LAYOUT
========================================================= */

.page-wrapper{

    position:relative;

    z-index:2;

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:30px 20px;

}


/* =========================================================
   LOGIN CONTAINER
========================================================= */

.login-container{

    width:100%;

    max-width:1050px;

    display:grid;

    grid-template-columns:1fr 430px;

    gap:25px;

    align-items:stretch;

}


/* =========================================================
   LEFT INFORMATION PANEL
========================================================= */

.info-panel{

    position:relative;

    overflow:hidden;

    min-height:650px;

    padding:55px;

    border-radius:32px;

    border:1px solid rgba(255,255,255,.08);

    background:

        linear-gradient(
            145deg,
            rgba(16,40,24,.78),
            rgba(7,22,13,.86)
        );

    backdrop-filter:blur(18px);

    box-shadow:

        0 30px 80px rgba(0,0,0,.35);

}


.info-panel::before{

    content:"";

    position:absolute;

    width:300px;

    height:300px;

    right:-120px;

    top:-120px;

    border-radius:50%;

    background:

        radial-gradient(
            circle,
            rgba(74,222,128,.13),
            transparent 70%
        );

}


.info-panel::after{

    content:"";

    position:absolute;

    width:220px;

    height:220px;

    left:-120px;

    bottom:-100px;

    border-radius:50%;

    background:

        radial-gradient(
            circle,
            rgba(34,197,94,.10),
            transparent 70%
        );

}


/* =========================================================
   BRAND
========================================================= */

.brand{

    position:relative;

    z-index:2;

    display:flex;

    align-items:center;

    gap:14px;

}


.brand-logo{

    width:58px;

    height:58px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:18px;

    background:

        linear-gradient(
            135deg,
            rgba(74,222,128,.20),
            rgba(22,163,74,.08)
        );

    border:1px solid rgba(74,222,128,.22);

    color:#4ade80;

    font-size:24px;

    box-shadow:

        0 10px 30px rgba(0,0,0,.2);

}


.brand-name{

    font-size:21px;

    font-weight:800;

    letter-spacing:.3px;

}


.brand-subtitle{

    margin-top:3px;

    color:#89a894;

    font-size:10px;

    letter-spacing:.5px;

}


/* =========================================================
   INFO CONTENT
========================================================= */

.info-content{

    position:relative;

    z-index:2;

    margin-top:115px;

}


.info-label{

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:8px 12px;

    border-radius:30px;

    color:#86efac;

    background:rgba(74,222,128,.09);

    border:1px solid rgba(74,222,128,.14);

    font-size:10px;

    font-weight:800;

    text-transform:uppercase;

    letter-spacing:1px;

}


.info-title{

    margin-top:20px;

    max-width:570px;

    font-size:48px;

    line-height:1.08;

    font-weight:800;

    letter-spacing:-1.5px;

}


.info-title span{

    color:#4ade80;

}


.info-description{

    max-width:540px;

    margin-top:20px;

    color:#9ab0a1;

    font-size:13px;

    line-height:1.9;

}


/* =========================================================
   FEATURE LIST
========================================================= */

.features{

    display:grid;

    grid-template-columns:repeat(2,1fr);

    gap:12px;

    margin-top:32px;

}


.feature{

    display:flex;

    align-items:center;

    gap:10px;

    padding:12px;

    border-radius:13px;

    background:rgba(255,255,255,.035);

    border:1px solid rgba(255,255,255,.05);

}


.feature-icon{

    width:32px;

    height:32px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:10px;

    color:#4ade80;

    background:rgba(74,222,128,.08);

    font-size:12px;

}


.feature span{

    color:#a7b9ad;

    font-size:10px;

    font-weight:600;

}


/* =========================================================
   LOGIN CARD
========================================================= */

.login-card{

    position:relative;

    padding:38px;

    border-radius:32px;

    background:

        linear-gradient(
            145deg,
            rgba(20,35,25,.88),
            rgba(8,20,13,.94)
        );

    border:1px solid rgba(255,255,255,.10);

    backdrop-filter:blur(22px);

    box-shadow:

        0 30px 80px rgba(0,0,0,.45);

}


/* =========================================================
   CARD TOP
========================================================= */

.login-card-top{

    text-align:center;

    margin-bottom:28px;

}


.login-icon{

    width:68px;

    height:68px;

    margin:0 auto 17px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:21px;

    color:#4ade80;

    background:

        linear-gradient(
            135deg,
            rgba(74,222,128,.18),
            rgba(22,163,74,.08)
        );

    border:1px solid rgba(74,222,128,.20);

    box-shadow:

        0 15px 35px rgba(34,197,94,.08);

    font-size:27px;

    animation:iconPulse 3s ease-in-out infinite;

}


@keyframes iconPulse{

    0%,
    100%{

        transform:translateY(0);

    }

    50%{

        transform:translateY(-4px);

    }

}


.login-title{

    color:#f3fff6;

    font-size:25px;

    font-weight:800;

}


.login-subtitle{

    margin-top:6px;

    color:#819689;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:1px;

    font-weight:700;

}


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

.success-msg{

    display:flex;

    align-items:flex-start;

    gap:10px;

    padding:13px;

    margin-bottom:18px;

    border-radius:13px;

    background:rgba(34,197,94,.10);

    border:1px solid rgba(74,222,128,.20);

    color:#a7f3c0;

    font-size:10px;

    line-height:1.6;

}


.success-msg i{

    color:#4ade80;

    margin-top:2px;

}


/* =========================================================
   ERROR MESSAGE
========================================================= */

.error-msg{

    display:flex;

    align-items:flex-start;

    gap:10px;

    padding:13px;

    margin-bottom:18px;

    border-radius:13px;

    background:rgba(239,68,68,.09);

    border:1px solid rgba(248,113,113,.18);

    color:#fecaca;

    font-size:10px;

    line-height:1.6;

}


.error-msg i{

    color:#f87171;

    margin-top:2px;

}


/* =========================================================
   INPUT
========================================================= */

.input-group-custom{

    position:relative;

    margin-bottom:15px;

}


.input-icon{

    position:absolute;

    left:17px;

    top:50%;

    transform:translateY(-50%);

    z-index:2;

    color:#4ade80;

    font-size:13px;

}


.form-control{

    width:100%;

    height:56px;

    padding:0 17px 0 47px;

    border-radius:14px;

    border:1px solid rgba(255,255,255,.08);

    outline:none;

    background:rgba(255,255,255,.055);

    color:white;

    font-family:'Inter',sans-serif;

    font-size:11px;

    transition:.25s;

}


.form-control::placeholder{

    color:#718477;

}


.form-control:focus{

    border-color:rgba(74,222,128,.45);

    background:rgba(74,222,128,.055);

    box-shadow:

        0 0 0 3px rgba(74,222,128,.07);

    color:white;

}


.form-control:-webkit-autofill,

.form-control:-webkit-autofill:hover,

.form-control:-webkit-autofill:focus{

    -webkit-text-fill-color:white;

    -webkit-box-shadow:0 0 0 1000px #102217 inset;

    transition:background-color 5000s ease-in-out 0s;

}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-btn{

    width:100%;

    height:56px;

    margin-top:6px;

    border:0;

    border-radius:14px;

    color:#06100b;

    background:

        linear-gradient(
            135deg,
            #4ade80,
            #22c55e
        );

    font-family:'Inter',sans-serif;

    font-size:12px;

    font-weight:800;

    letter-spacing:.2px;

    cursor:pointer;

    transition:.25s;

    box-shadow:

        0 12px 30px rgba(34,197,94,.15);

}


.login-btn:hover{

    transform:translateY(-3px);

    box-shadow:

        0 16px 35px rgba(34,197,94,.25);

}


.login-btn:active{

    transform:translateY(-1px);

}


/* =========================================================
   REGISTER
========================================================= */

.register-link{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:7px;

    margin-top:21px;

    color:#70d995;

    font-size:10px;

    font-weight:600;

    text-decoration:none;

    transition:.25s;

}


.register-link:hover{

    color:#bbf7d0;

    text-decoration:none;

}


/* =========================================================
   BACK HOME
========================================================= */

.back-home{

    display:flex;

    justify-content:center;

    margin-top:25px;

    padding-top:20px;

    border-top:1px solid rgba(255,255,255,.06);

}


.back-home a{

    display:inline-flex;

    align-items:center;

    gap:7px;

    color:#72877a;

    font-size:9px;

    font-weight:600;

    text-decoration:none;

    transition:.25s;

}


.back-home a:hover{

    color:#4ade80;

    transform:translateX(-3px);

}


/* =========================================================
   FOOTER
========================================================= */

.login-footer{

    margin-top:17px;

    text-align:center;

    color:#52665a;

    font-size:8px;

}


/* =========================================================
   SECURITY LABEL
========================================================= */

.security-label{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:5px;

    margin-top:10px;

    color:#4f6657;

    font-size:8px;

}


.security-label i{

    color:#4ade80;

    font-size:8px;

}


/* =========================================================
   ANIMATION
========================================================= */

.login-card{

    animation:loginAppear .6s ease both;

}


.info-panel{

    animation:infoAppear .7s ease both;

}


@keyframes loginAppear{

    from{

        opacity:0;

        transform:translateY(18px);

    }

    to{

        opacity:1;

        transform:translateY(0);

    }

}


@keyframes infoAppear{

    from{

        opacity:0;

        transform:translateX(-18px);

    }

    to{

        opacity:1;

        transform:translateX(0);

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:850px){

    body{

        overflow:auto;

    }


    .page-wrapper{

        padding:20px 14px;

    }


    .login-container{

        max-width:480px;

        grid-template-columns:1fr;

    }


    .info-panel{

        display:none;

    }


    .login-card{

        padding:32px 25px;

        border-radius:26px;

    }

}


@media(max-width:430px){

    .page-wrapper{

        padding:15px 10px;

    }


    .login-card{

        padding:28px 20px;

        border-radius:23px;

    }


    .login-title{

        font-size:23px;

    }


    .form-control,

    .login-btn{

        height:53px;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     BACKGROUND
========================================================= -->

<div class="page-bg">

    <div class="grid-background"></div>

    <div class="glow glow-one"></div>

    <div class="glow glow-two"></div>

</div>


<!-- =========================================================
     PAGE
========================================================= -->

<div class="page-wrapper">


<div class="login-container">


<!-- =========================================================
     LEFT PANEL
========================================================= -->

<section class="info-panel">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-logo">

            <i class="fa-solid fa-landmark"></i>

        </div>


        <div>

            <div class="brand-name">

                SK KONEXT

            </div>


            <div class="brand-subtitle">

                Governance Technology

            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="info-content">


        <div class="info-label">

            <i class="fa-solid fa-circle-nodes"></i>

            Digital Governance Platform

        </div>


        <h1 class="info-title">

            Connect with your

            <span>community.</span>

        </h1>


        <p class="info-description">

            Access the SK KONEXT platform for barangay

            information, scholarship services, community

            activities, projects, announcements, and

            digital governance tools.

        </p>


        <!-- FEATURES -->

        <div class="features">


            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-map-location-dot"></i>

                </div>

                <span>

                    Community Map

                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-graduation-cap"></i>

                </div>

                <span>

                    Scholarship Services

                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-bullhorn"></i>

                </div>

                <span>

                    Barangay Updates

                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-people-group"></i>

                </div>

                <span>

                    Community Programs

                </span>

            </div>


        </div>


    </div>


</section>


<!-- =========================================================
     LOGIN CARD
========================================================= -->

<section class="login-card">


    <!-- TOP -->

    <div class="login-card-top">


        <div class="login-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div class="login-title">

            Welcome Back

        </div>


        <div class="login-subtitle">

            Sign in to SK KONEXT

        </div>


    </div>


    <!-- =====================================================
         SUCCESS MESSAGE
    ===================================================== -->

    <?php if(
        isset($_GET['registered']) &&
        $_GET['registered'] == '1'
    ){ ?>


        <div class="success-msg">

            <i class="fa-solid fa-circle-check"></i>

            <span>

                Account successfully verified.

                You can now login.

            </span>

        </div>


    <?php } ?>


    <!-- =====================================================
         ERROR MESSAGE
    ===================================================== -->

    <?php if(isset($error)){ ?>


        <div class="error-msg">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>

                <?= htmlspecialchars($error) ?>

            </span>

        </div>


    <?php } ?>


    <!-- =====================================================
         LOGIN FORM
    ===================================================== -->

    <form
        method="POST"
        action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>"
    >


        <!-- USERNAME / MOBILE -->

        <div class="input-group-custom">

            <i class="fa-solid fa-user input-icon"></i>

            <input
                type="text"
                name="username"
                class="form-control"
                placeholder="Username or Mobile Number"
                autocomplete="username"
                required
            >

        </div>


        <!-- PASSWORD -->

        <div class="input-group-custom">

            <i class="fa-solid fa-lock input-icon"></i>

            <input
                type="password"
                name="password"
                class="form-control"
                placeholder="Enter Password"
                autocomplete="current-password"
                required
            >

        </div>


        <!-- LOGIN BUTTON -->

        <button
            type="submit"
            name="login"
            class="login-btn"
        >

            <i class="fa-solid fa-right-to-bracket"></i>

            &nbsp;

            Secure Login

        </button>


    </form>


    <!-- =====================================================
         REGISTER
    ===================================================== -->

    <a
        href="register.php"
        class="register-link"
    >

        <i class="fa-solid fa-user-plus"></i>

        New applicant? Create an account

    </a>


    <!-- =====================================================
         BACK HOME
    ===================================================== -->

    <div class="back-home">

        <a href="index.php">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Home

        </a>

    </div>


    <!-- FOOTER -->

    <div class="login-footer">

        SK KONEXT © 2026

        &nbsp;•&nbsp;

        Governance Technology System

    </div>


    <div class="security-label">

        <i class="fa-solid fa-lock"></i>

        Secure access portal

    </div>


</section>


</div>

</div>


</body>

</html>