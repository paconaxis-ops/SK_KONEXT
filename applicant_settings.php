
<?php
session_start();
include("db.php");

if(!isset($_SESSION['applicant_id']) || (int)$_SESSION['applicant_id'] <= 0){
    header("Location: login.php");
    exit();
}

$applicant_id = (int)$_SESSION['applicant_id'];
$error = "";
$success = "";

if(empty($_SESSION['phone_verification_csrf'])){
    $_SESSION['phone_verification_csrf'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['phone_verification_csrf'];

function clean($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* GET APPLICANT */

$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM applicant_accounts
    WHERE id = ?
    AND is_verified = 1
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$applicant = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$applicant){
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

/* =========================================
   REQUEST MOBILE NUMBER VERIFICATION
========================================= */

if(isset($_POST['request_phone_verification'])){

    $submitted_token = $_POST['csrf_token'] ?? '';

    if(!hash_equals($csrf_token, $submitted_token)){

        $error = "Invalid request. Please refresh the page and try again.";

    }elseif((int)$applicant['phone_verified'] === 1){

        $success = "Your mobile number is already verified.";

    }elseif(trim($applicant['phone'] ?? '') === ''){

        $error = "No registered mobile number was found in your account.";

    }else{

        /* CHECK EXISTING PENDING REQUEST */

        $pending_stmt = mysqli_prepare($conn, "
            SELECT id
            FROM phone_verification_requests
            WHERE applicant_id = ?
            AND status = 'Pending'
            LIMIT 1
        ");

        mysqli_stmt_bind_param($pending_stmt, "i", $applicant_id);
        mysqli_stmt_execute($pending_stmt);
        $pending_result = mysqli_stmt_get_result($pending_stmt);
        $pending_request = mysqli_fetch_assoc($pending_result);
        mysqli_stmt_close($pending_stmt);

        if($pending_request){

            $error = "You already have a pending mobile number verification request.";

        }else{

            /* SAVE REQUEST */

            $registered_phone = trim($applicant['phone']);

            $request_stmt = mysqli_prepare($conn, "
                INSERT INTO phone_verification_requests (
                    applicant_id,
                    phone_number,
                    status,
                    requested_at
                )
                VALUES (?, ?, 'Pending', NOW())
            ");

            if($request_stmt){

                mysqli_stmt_bind_param(
                    $request_stmt,
                    "is",
                    $applicant_id,
                    $registered_phone
                );

                if(mysqli_stmt_execute($request_stmt)){

                    $success = "Your mobile number verification request has been submitted. Please wait for the superadmin to review it.";

                }else{

                    $error = "Unable to submit your request. Please try again.";
                }

                mysqli_stmt_close($request_stmt);

            }else{

                $error = "Unable to process your request. Please try again.";
            }
        }
    }
}

/* =========================================
   GET LATEST PHONE VERIFICATION REQUEST
========================================= */

$latest_request = null;

$request_stmt = mysqli_prepare($conn, "
    SELECT
        id,
        phone_number,
        status,
        requested_at,
        reviewed_at,
        admin_remarks
    FROM phone_verification_requests
    WHERE applicant_id = ?
    ORDER BY id DESC
    LIMIT 1
");

if($request_stmt){

    mysqli_stmt_bind_param($request_stmt, "i", $applicant_id);
    mysqli_stmt_execute($request_stmt);

    $request_result = mysqli_stmt_get_result($request_stmt);
    $latest_request = mysqli_fetch_assoc($request_result);

    mysqli_stmt_close($request_stmt);
}

/* =========================================
   GET PROFILE
========================================= */

$profile = [
    'contact_number' => $applicant['phone'],
    'school' => '',
    'course' => '',
    'education_level' => '',
    'year_level' => '',
    'barangay_id' => ''
];

$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM applicant_profiles
    WHERE applicant_id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);

$profile_result = mysqli_stmt_get_result($stmt);
$saved_profile = mysqli_fetch_assoc($profile_result);

mysqli_stmt_close($stmt);

if($saved_profile){
    $profile = array_merge($profile, $saved_profile);
}

/* =========================================
   GET BARANGAY
========================================= */

$barangay_name = "Not specified";
$barangay_id = (int)($profile['barangay_id'] ?? 0);

if($barangay_id > 0){

    $stmt = mysqli_prepare($conn, "
        SELECT name
        FROM barangays
        WHERE id = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, "i", $barangay_id);
    mysqli_stmt_execute($stmt);

    $barangay_result = mysqli_stmt_get_result($stmt);
    $barangay_data = mysqli_fetch_assoc($barangay_result);

    mysqli_stmt_close($stmt);

    if($barangay_data){
        $barangay_name = $barangay_data['name'];
    }
}

/* =========================================
   SAVE SETTINGS
========================================= */

if(isset($_POST['save_settings'])){

    $submitted_token = $_POST['csrf_token'] ?? '';

    if(!hash_equals($csrf_token, $submitted_token)){

        $error = "Invalid request. Please refresh the page and try again.";

    }else{

        $school = trim($_POST['school'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $education_level = trim($_POST['education_level'] ?? '');
        $year_level = trim($_POST['year_level'] ?? '');

        $barangay_id = (int)($profile['barangay_id'] ?? 0);

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if($education_level === 'Senior High' && !in_array($year_level, ['11','12'], true)){

            $error = "Please select a valid Senior High year level.";

        }elseif($education_level === 'College' && !in_array($year_level, ['1','2','3','4'], true)){

            $error = "Please select a valid College year level.";

        }elseif($education_level === '' && $year_level !== ''){

            $error = "Please select your education level first.";

        }elseif(!in_array($education_level, ['', 'Senior High', 'College'], true)){

            $error = "Invalid education level.";
        }

        $change_password = (
            $current_password !== '' ||
            $new_password !== '' ||
            $confirm_password !== ''
        );

        if($error === '' && $change_password){

            if($current_password === '' || $new_password === '' || $confirm_password === ''){

                $error = "Please complete all password fields.";

            }elseif(!password_verify($current_password, $applicant['password'])){

                $error = "Your current password is incorrect.";

            }elseif(strlen($new_password) < 8){

                $error = "Your new password must be at least 8 characters.";

            }elseif($new_password !== $confirm_password){

                $error = "New password and confirmation password do not match.";
            }
        }

        if($error === ''){

            mysqli_begin_transaction($conn);

            try{

                if($change_password){

                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $update_account = mysqli_prepare($conn, "
                        UPDATE applicant_accounts
                        SET password = ?
                        WHERE id = ?
                        AND is_verified = 1
                    ");

                    mysqli_stmt_bind_param(
                        $update_account,
                        "si",
                        $hashed_password,
                        $applicant_id
                    );

                    if(!mysqli_stmt_execute($update_account)){
                        throw new Exception("Unable to update password.");
                    }

                    mysqli_stmt_close($update_account);
                }

                $registered_phone = $applicant['phone'];

                $save_profile = mysqli_prepare($conn, "
                    INSERT INTO applicant_profiles (
                        applicant_id,
                        contact_number,
                        school,
                        course,
                        education_level,
                        year_level,
                        barangay_id
                    )
                    VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, 0))
                    ON DUPLICATE KEY UPDATE
                        school = VALUES(school),
                        course = VALUES(course),
                        education_level = VALUES(education_level),
                        year_level = VALUES(year_level)
                ");

                mysqli_stmt_bind_param(
                    $save_profile,
                    "isssssi",
                    $applicant_id,
                    $registered_phone,
                    $school,
                    $course,
                    $education_level,
                    $year_level,
                    $barangay_id
                );

                if(!mysqli_stmt_execute($save_profile)){
                    throw new Exception("Unable to save applicant profile.");
                }

                mysqli_stmt_close($save_profile);

                mysqli_commit($conn);

                $profile['contact_number'] = $registered_phone;
                $profile['school'] = $school;
                $profile['course'] = $course;
                $profile['education_level'] = $education_level;
                $profile['year_level'] = $year_level;

                $success = "Your applicant settings have been updated successfully.";

                $_POST = [];

            }catch(Exception $e){

                mysqli_rollback($conn);
                $error = "Unable to save your settings. Please try again.";
            }
        }
    }
}

$phone_verified = (int)($applicant['phone_verified'] ?? 0);

/* =========================================
   PHONE VERIFICATION DISPLAY STATUS
========================================= */

$phone_request_status = $latest_request['status'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Applicant Settings | SK KONEXT</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --green:#4ade80;
    --green-dark:#16a34a;
    --green-light:#86efac;
    --dark:#06100b;
    --white:#fff;
    --muted:rgba(255,255,255,.60);
    --border:rgba(255,255,255,.12);
}

body{
    min-height:100vh;
    font-family:"DM Sans",sans-serif;
    color:var(--white);
    background:
        radial-gradient(circle at 15% 15%,rgba(74,222,128,.13),transparent 28%),
        radial-gradient(circle at 85% 30%,rgba(22,163,74,.10),transparent 25%),
        var(--dark);
    padding-bottom:40px;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    opacity:.28;
    background-image:
        linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
    background-size:45px 45px;
}

a{
    text-decoration:none;
    color:inherit;
}

.navbar{
    position:relative;
    z-index:2;
    width:min(1180px,calc(100% - 40px));
    min-height:70px;
    margin:22px auto 0;
    padding:10px 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    background:rgba(6,16,11,.76);
    border:1px solid var(--border);
    border-radius:18px;
    backdrop-filter:blur(18px);
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-family:"Manrope",sans-serif;
    font-weight:800;
}

.brand img{
    width:42px;
    height:42px;
    object-fit:contain;
}

.nav-actions{
    display:flex;
    align-items:center;
    gap:8px;
}

.nav-link{
    padding:10px 13px;
    border-radius:11px;
    color:rgba(255,255,255,.75);
    font-size:13px;
    font-weight:700;
}

.nav-link:hover,
.nav-link.active{
    background:rgba(255,255,255,.07);
    color:#fff;
}

.page{
    position:relative;
    z-index:1;
    width:min(850px,calc(100% - 32px));
    margin:55px auto;
}

.page-heading{
    margin-bottom:25px;
}

.page-heading h1{
    font-family:"Manrope",sans-serif;
    font-size:clamp(30px,5vw,42px);
    font-weight:900;
    margin-bottom:8px;
}

.page-heading h1 i{
    color:var(--green);
}

.page-heading p{
    color:var(--muted);
    font-size:14px;
    line-height:1.7;
}

.settings-card{
    padding:30px;
    background:rgba(255,255,255,.055);
    border:1px solid var(--border);
    border-radius:23px;
    box-shadow:0 25px 80px rgba(0,0,0,.22);
    backdrop-filter:blur(16px);
    margin-bottom:20px;
}

.section-heading{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:22px;
}

.section-icon{
    width:43px;
    height:43px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:13px;
    color:var(--green);
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.20);
}

.section-heading h2{
    font-family:"Manrope",sans-serif;
    font-size:18px;
    font-weight:800;
}

.section-heading p{
    margin-top:3px;
    color:var(--muted);
    font-size:11px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
}

.form-group{
    min-width:0;
}

.form-group.full{
    grid-column:1/-1;
}

label{
    display:block;
    margin-bottom:8px;
    color:rgba(255,255,255,.83);
    font-size:12px;
    font-weight:700;
}

.form-control{
    width:100%;
    height:49px;
    padding:0 14px;
    border-radius:12px;
    border:1px solid rgba(255,255,255,.13);
    outline:none;
    background:rgba(0,0,0,.20);
    color:#fff;
    font-family:"DM Sans",sans-serif;
    font-size:13px;
}

.form-control:focus{
    border-color:rgba(74,222,128,.55);
    box-shadow:0 0 0 3px rgba(74,222,128,.08);
}

.form-control option{
    background:#0b1910;
    color:#fff;
}

.form-control:read-only{
    background:rgba(255,255,255,.045);
    border-color:rgba(255,255,255,.08);
    color:rgba(255,255,255,.68);
    cursor:not-allowed;
}

.locked-field{
    position:relative;
}

.locked-field .lock-icon{
    position:absolute;
    right:15px;
    top:17px;
    color:rgba(255,255,255,.40);
    font-size:13px;
    pointer-events:none;
}

.form-help{
    display:block;
    margin-top:7px;
    color:rgba(255,255,255,.42);
    font-size:10px;
}

.alert{
    padding:14px 17px;
    margin-bottom:18px;
    border-radius:13px;
    font-size:13px;
    border:1px solid var(--border);
    background:rgba(255,255,255,.05);
}

.alert-success{
    color:#bbf7d0;
    border-color:rgba(74,222,128,.30);
    background:rgba(74,222,128,.09);
}

.alert-danger{
    color:#fecaca;
    border-color:rgba(248,113,113,.30);
    background:rgba(248,113,113,.08);
}

/* PHONE VERIFICATION */

.phone-verification{
    margin-top:16px;
    padding:19px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:14px;
    background:rgba(0,0,0,.16);
}

.phone-status{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    font-weight:800;
    margin-bottom:10px;
}

.phone-status.verified{
    color:#86efac;
}

.phone-status.pending{
    color:#facc15;
}

.phone-status.rejected{
    color:#fca5a5;
}

.phone-status.unverified{
    color:#facc15;
}

.phone-description{
    color:rgba(255,255,255,.60);
    font-size:11px;
    line-height:1.7;
    margin-bottom:13px;
}

.request-details{
    margin-top:12px;
    padding:13px;
    border-radius:11px;
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.08);
}

.request-details small{
    display:block;
    color:rgba(255,255,255,.45);
    font-size:10px;
    margin-bottom:5px;
}

.request-details strong{
    display:block;
    color:rgba(255,255,255,.85);
    font-size:12px;
}

.admin-remarks{
    margin-top:12px;
    padding:12px;
    border-radius:10px;
    background:rgba(248,113,113,.07);
    border:1px solid rgba(248,113,113,.18);
    color:#fecaca;
    font-size:11px;
    line-height:1.6;
}

.btn-request{
    min-height:44px;
    padding:0 18px;
    border:0;
    border-radius:10px;
    background:var(--green);
    color:#06100b;
    font-family:"DM Sans",sans-serif;
    font-size:12px;
    font-weight:800;
    cursor:pointer;
    transition:.2s;
}

.btn-request:hover{
    background:#86efac;
    transform:translateY(-1px);
}

.btn-request i{
    margin-right:6px;
}

.password-note{
    margin-bottom:18px;
    color:rgba(255,255,255,.48);
    font-size:11px;
    line-height:1.6;
}

.btn-save{
    width:100%;
    height:53px;
    margin-top:22px;
    border:0;
    border-radius:13px;
    background:var(--green);
    color:#06100b;
    font-family:"DM Sans",sans-serif;
    font-size:14px;
    font-weight:800;
    cursor:pointer;
    transition:.2s;
}

.btn-save:hover{
    background:#86efac;
    transform:translateY(-1px);
}

@media(max-width:650px){

    .navbar{
        width:calc(100% - 24px);
        margin-top:12px;
        padding:10px 13px;
    }

    .brand{
        font-size:13px;
    }

    .brand img{
        width:35px;
        height:35px;
    }

    .nav-link{
        padding:9px;
        font-size:11px;
    }

    .scholarship-disabled{
    opacity:.45;
    cursor:not-allowed;
    pointer-events:none;
}

    .page{
        margin:38px auto;
    }

    .settings-card{
        padding:21px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .form-group.full{
        grid-column:auto;
    }

    .btn-request{
        width:100%;
    }
}

</style>
</head>

<body>

<nav class="navbar">

    <a href="applicant_dashboard.php" class="brand">
        <img src="assets/sk-logo.png.webp" alt="SK KONEXT Logo">
        <span>SK KONEXT</span>
    </a>

    <div class="nav-actions">

        <a href="applicant_dashboard.php" class="nav-link">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <?php if($phone_verified === 1){ ?>

    <a href="scholarship.php" class="nav-link">
        <i class="fa-solid fa-graduation-cap"></i> Scholarship
    </a>

<?php }else{ ?>

    <span class="nav-link scholarship-disabled"
          title="Verify your mobile number first"
          aria-disabled="true">
        <i class="fa-solid fa-lock"></i> Scholarship
    </span>

<?php } ?>

        <a href="applicant_settings.php" class="nav-link active">
            <i class="fa-solid fa-gear"></i> Settings
        </a>

    </div>

</nav>

<main class="page">

    <div class="page-heading">
        <h1><i class="fa-solid fa-user-gear"></i> Applicant Settings</h1>
        <p>Manage your educational details and password. Your registered account information is protected and cannot be changed.</p>
    </div>

    <?php if($success !== ''){ ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= clean($success) ?>
        </div>
    <?php } ?>

    <?php if($error !== ''){ ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= clean($error) ?>
        </div>
    <?php } ?>

    <form method="POST">

        <input type="hidden" name="csrf_token" value="<?= clean($csrf_token) ?>">

        <!-- ACCOUNT INFORMATION -->

        <div class="settings-card">

            <div class="section-heading">
                <div class="section-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <h2>Account Information</h2>
                    <p>Your registered account information is protected.</p>
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Full Name</label>
                    <div class="locked-field">
                        <input type="text" class="form-control"
                               value="<?= clean($applicant['fullname']) ?>" readonly>
                        <i class="fa-solid fa-lock lock-icon"></i>
                    </div>
                    <small class="form-help">This information cannot be changed.</small>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="locked-field">
                        <input type="email" class="form-control"
                               value="<?= clean($applicant['email']) ?>" readonly>
                        <i class="fa-solid fa-lock lock-icon"></i>
                    </div>
                    <small class="form-help">Your registered email address is protected.</small>
                </div>

                <div class="form-group">
                    <label>Registered Mobile Number</label>
                    <div class="locked-field">
                        <input type="text" class="form-control"
                               value="<?= clean($applicant['phone']) ?>" readonly>
                        <i class="fa-solid fa-lock lock-icon"></i>
                    </div>
                    <small class="form-help">Your registered contact number is protected.</small>
                </div>

                <!-- MANUAL PHONE VERIFICATION -->

                <div class="form-group full">

                    <div class="phone-verification">

                        <?php if($phone_verified === 1){ ?>

                            <div class="phone-status verified">
                                <i class="fa-solid fa-circle-check"></i>
                                Mobile Number Verified
                            </div>

                            <p class="phone-description">
                                Your registered mobile number has been approved and verified by the SK KONEXT administrator.
                            </p>

                        <?php }elseif($phone_request_status === 'Pending'){ ?>

                            <div class="phone-status pending">
                                <i class="fa-solid fa-clock"></i>
                                Verification Request Pending
                            </div>

                            <p class="phone-description">
                                Your mobile number verification request has been submitted. Please wait for the superadmin to review your request.
                            </p>

                            <div class="request-details">
                                <small>Request Submitted</small>
                                <strong>
                                    <?= !empty($latest_request['requested_at'])
                                        ? date("F d, Y - h:i A", strtotime($latest_request['requested_at']))
                                        : 'Pending review' ?>
                                </strong>
                            </div>

                        <?php }elseif($phone_request_status === 'Rejected'){ ?>

                            <div class="phone-status rejected">
                                <i class="fa-solid fa-circle-xmark"></i>
                                Verification Request Rejected
                            </div>

                            <p class="phone-description">
                                Your previous verification request was rejected. Please check the administrator's remarks below before submitting another request.
                            </p>

                            <?php if(!empty($latest_request['admin_remarks'])){ ?>

                                <div class="admin-remarks">
                                    <strong>Administrator Remarks:</strong><br>
                                    <?= nl2br(clean($latest_request['admin_remarks'])) ?>
                                </div>

                            <?php } ?>

                            <div class="request-details">
                                <small>Reviewed Date</small>
                                <strong>
                                    <?= !empty($latest_request['reviewed_at'])
                                        ? date("F d, Y - h:i A", strtotime($latest_request['reviewed_at']))
                                        : 'Not available' ?>
                                </strong>
                            </div>

                            <p class="phone-description" style="margin-top:13px;">
                                You may submit another verification request after reviewing the remarks.
                            </p>

                            <button
                                type="submit"
                                name="request_phone_verification"
                                value="1"
                                class="btn-request"
                            >
                                <i class="fa-solid fa-rotate"></i>
                                Request Verification Again
                            </button>

                        <?php }else{ ?>

                            <div class="phone-status unverified">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Mobile Number Not Verified
                            </div>

                            <p class="phone-description">
                                Your registered mobile number needs to be verified before you can apply for a scholarship. Submit a request below. The superadmin will manually review your registered number.
                            </p>

                            <button
                                type="submit"
                                name="request_phone_verification"
                                value="1"
                                class="btn-request"
                            >
                                <i class="fa-solid fa-mobile-screen-button"></i>
                                Request Number Verification
                            </button>

                        <?php } ?>

                    </div>

                </div>

                <div class="form-group full">
                    <label>Barangay</label>
                    <div class="locked-field">
                        <input type="text" class="form-control"
                               value="<?= clean($barangay_name) ?>" readonly>
                        <i class="fa-solid fa-lock lock-icon"></i>
                    </div>
                    <small class="form-help">Your registered barangay cannot be changed.</small>
                </div>

            </div>

        </div>

        <!-- EDUCATIONAL INFORMATION -->

        <div class="settings-card">

            <div class="section-heading">
                <div class="section-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <h2>Educational Information</h2>
                    <p>These details will be used to pre-fill your scholarship application.</p>
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group full">
                    <label>School</label>
                    <input
                        type="text"
                        name="school"
                        class="form-control"
                        value="<?= clean($_POST['school'] ?? $profile['school']) ?>"
                        placeholder="Enter your school"
                    >
                </div>

                <div class="form-group full">
                    <label>Course / Strand</label>
                    <input
                        type="text"
                        name="course"
                        class="form-control"
                        value="<?= clean($_POST['course'] ?? $profile['course']) ?>"
                        placeholder="Enter your course or strand"
                    >
                </div>

                <div class="form-group">
                    <label>Education Level</label>

                    <?php
                    $selected_education = $_POST['education_level'] ?? $profile['education_level'];
                    ?>

                    <select name="education_level" id="education_level" class="form-control">

                        <option value="">Select education level</option>

                        <option value="Senior High" <?= $selected_education === 'Senior High' ? 'selected' : '' ?>>
                            Senior High
                        </option>

                        <option value="College" <?= $selected_education === 'College' ? 'selected' : '' ?>>
                            College
                        </option>

                    </select>
                </div>

                <div class="form-group">
                    <label>Year Level</label>

                    <?php
                    $selected_year = (string)($_POST['year_level'] ?? $profile['year_level']);
                    ?>

                    <select name="year_level" id="year_level" class="form-control">
                        <option value="">Select year level</option>
                    </select>
                </div>

            </div>

        </div>

        <!-- CHANGE PASSWORD -->

        <div class="settings-card">

            <div class="section-heading">
                <div class="section-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h2>Change Password</h2>
                    <p>Leave these fields blank if you do not want to change your password.</p>
                </div>
            </div>

            <p class="password-note">
                For security, enter your current password before setting a new one.
                Your new password must contain at least 8 characters.
            </p>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        class="form-control"
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input
                        type="password"
                        name="new_password"
                        class="form-control"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        autocomplete="new-password"
                    >
                </div>

            </div>

            <button type="submit" name="save_settings" value="1" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes
            </button>

        </div>

    </form>

</main>

<script>

const educationSelect = document.getElementById("education_level");
const yearSelect = document.getElementById("year_level");

const savedYearLevel = <?= json_encode((string)($_POST['year_level'] ?? $profile['year_level'])) ?>;

function updateYearLevels(selectedYear = ""){

    const education = educationSelect.value;

    yearSelect.innerHTML = '<option value="">Select year level</option>';

    let options = [];

    if(education === "Senior High"){

        options = [
            {value:"11",label:"Grade 11"},
            {value:"12",label:"Grade 12"}
        ];

    }else if(education === "College"){

        options = [
            {value:"1",label:"1st Year"},
            {value:"2",label:"2nd Year"},
            {value:"3",label:"3rd Year"},
            {value:"4",label:"4th Year"}
        ];
    }

    options.forEach(function(option){

        const element = document.createElement("option");

        element.value = option.value;
        element.textContent = option.label;

        if(String(option.value) === String(selectedYear)){
            element.selected = true;
        }

        yearSelect.appendChild(element);
    });
}

educationSelect.addEventListener("change", function(){
    updateYearLevels("");
});

updateYearLevels(savedYearLevel);

</script>

</body>
</html>
