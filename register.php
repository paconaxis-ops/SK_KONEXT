
<?php

include("db.php");
require_once("email_config.php");

session_start();

$message = "";
$message_type = "";


/* =========================================
   GET BARANGAYS
========================================= */

$barangays = [];

$barangay_query = mysqli_query($conn,"
    SELECT id, name
    FROM barangays
    WHERE name IS NOT NULL
    AND name <> ''
    ORDER BY name ASC
");

if($barangay_query){
    while($row = mysqli_fetch_assoc($barangay_query)){
        $barangays[] = $row;
    }
}


/* =========================================
   HANDLE REGISTRATION
========================================= */

if(isset($_POST['register'])){

    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $barangay_id = (int)($_POST['barangay_id'] ?? 0);

    $education_level = trim($_POST['education_level'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $course = trim($_POST['course'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /* =========================================
       CLEAN PHONE NUMBER
    ========================================= */

    $phone = preg_replace('/[\s\-]/', '', $phone);


    /* =========================================
       VALIDATE PHONE NUMBER
    ========================================= */

    if(preg_match('/^09[0-9]{9}$/', $phone)){

        $phone_db = $phone;

    }elseif(preg_match('/^\+639[0-9]{9}$/', $phone)){

        $phone_db = '0' . substr($phone, 3);

    }elseif(preg_match('/^639[0-9]{9}$/', $phone)){

        $phone_db = '0' . substr($phone, 2);

    }else{

        $message = "Please enter a valid Philippine mobile number.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       REQUIRED FIELDS
    ========================================= */

    if(
        $fullname == "" ||
        $email == "" ||
        $password == "" ||
        $date_of_birth == "" ||
        $gender == "" ||
        $barangay_id <= 0 ||
        $education_level == "" ||
        $year_level == "" ||
        $school == "" ||
        $course == ""
    ){

        $message = "Please complete all required fields.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       VALIDATE EMAIL
    ========================================= */

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){

        $message = "Please enter a valid email address.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       VALIDATE DATE OF BIRTH
    ========================================= */

    $dob_date = DateTime::createFromFormat('Y-m-d', $date_of_birth);

    if(
        !$dob_date ||
        $dob_date->format('Y-m-d') !== $date_of_birth ||
        $date_of_birth > date('Y-m-d')
    ){

        $message = "Please enter a valid date of birth.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       VALIDATE GENDER
    ========================================= */

    if(!in_array($gender, ['Male', 'Female', 'Prefer not to say'], true)){

        $message = "Please select a valid gender.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       VALIDATE BARANGAY
    ========================================= */

    $barangay_check = mysqli_prepare($conn,"
        SELECT id
        FROM barangays
        WHERE id = ?
        AND name IS NOT NULL
        AND name <> ''
        LIMIT 1
    ");

    mysqli_stmt_bind_param($barangay_check, "i", $barangay_id);
    mysqli_stmt_execute($barangay_check);

    $barangay_result = mysqli_stmt_get_result($barangay_check);

    if(!$barangay_result || mysqli_num_rows($barangay_result) == 0){

        $message = "Please select a valid barangay.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       VALIDATE EDUCATION DETAILS
    ========================================= */

    $allowed_education = [
        'Elementary',
        'Junior High School',
        'Senior High School',
        'College',
        'Vocational / Technical',
        'Graduate Studies',
        'Other'
    ];

    if(!in_array($education_level, $allowed_education, true)){

        $message = "Please select a valid education level.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       PASSWORD MATCH
    ========================================= */

    if($password !== $confirm_password){

        $message = "Passwords do not match.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       PASSWORD LENGTH
    ========================================= */

    if(strlen($password) < 8){

        $message = "Password must be at least 8 characters.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       CHECK VERIFIED PHONE
    ========================================= */

    $check_phone = mysqli_prepare($conn,"
        SELECT id
        FROM applicant_accounts
        WHERE phone = ?
        AND is_verified = 1
        LIMIT 1
    ");

    mysqli_stmt_bind_param($check_phone, "s", $phone_db);
    mysqli_stmt_execute($check_phone);

    $phone_result = mysqli_stmt_get_result($check_phone);

    if($phone_result && mysqli_num_rows($phone_result) > 0){

        $message = "This number is already registered. Please login instead.";
        $message_type = "warning";

        goto show_form;
    }


    /* =========================================
       CHECK VERIFIED EMAIL
    ========================================= */

    $check_email = mysqli_prepare($conn,"
        SELECT id
        FROM applicant_accounts
        WHERE email = ?
        AND is_verified = 1
        LIMIT 1
    ");

    mysqli_stmt_bind_param($check_email, "s", $email);
    mysqli_stmt_execute($check_email);

    $email_result = mysqli_stmt_get_result($check_email);

    if($email_result && mysqli_num_rows($email_result) > 0){

        $message = "This email is already registered. Please login instead.";
        $message_type = "warning";

        goto show_form;
    }


    /* =========================================
       DELETE OLD UNVERIFIED REGISTRATIONS
    ========================================= */

    $delete_old = mysqli_prepare($conn,"
        SELECT id
        FROM applicant_accounts
        WHERE is_verified = 0
        AND (phone = ? OR email = ?)
    ");

    mysqli_stmt_bind_param($delete_old, "ss", $phone_db, $email);
    mysqli_stmt_execute($delete_old);

    $old_result = mysqli_stmt_get_result($delete_old);

    while($old = mysqli_fetch_assoc($old_result)){

        $old_id = (int)$old['id'];

        mysqli_query($conn,"
            DELETE FROM applicant_profiles
            WHERE applicant_id = $old_id
        ");

        mysqli_query($conn,"
            DELETE FROM applicant_accounts
            WHERE id = $old_id
            AND is_verified = 0
        ");
    }


    /* =========================================
       HASH PASSWORD
    ========================================= */

    $password_hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* =========================================
       GENERATE OTP
    ========================================= */

    $otp = (string)random_int(100000, 999999);


    /* =========================================
       INSERT ACCOUNT
       MYSQL SETS OTP EXPIRATION
    ========================================= */

    $insert = mysqli_prepare($conn,"
        INSERT INTO applicant_accounts
        (
            fullname,
            email,
            phone,
            password,
            is_verified,
            otp_code,
            otp_expires
        )
        VALUES
        (?, ?, ?, ?, 0, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
    ");

    mysqli_stmt_bind_param(
        $insert,
        "sssss",
        $fullname,
        $email,
        $phone_db,
        $password_hash,
        $otp
    );


    if(!mysqli_stmt_execute($insert)){

        error_log("SK KONEXT Registration Error: " . mysqli_error($conn));

        $message = "Registration failed. Please try again.";
        $message_type = "danger";

        goto show_form;
    }


    $account_id = mysqli_insert_id($conn);


    /* =========================================
       SAVE RESIDENT PROFILE
    ========================================= */

    $profile = mysqli_prepare($conn,"
        INSERT INTO applicant_profiles
        (
            applicant_id,
            date_of_birth,
            gender,
            contact_number,
            school,
            course,
            education_level,
            year_level,
            barangay_id,
            updated_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            date_of_birth = VALUES(date_of_birth),
            gender = VALUES(gender),
            contact_number = VALUES(contact_number),
            school = VALUES(school),
            course = VALUES(course),
            education_level = VALUES(education_level),
            year_level = VALUES(year_level),
            barangay_id = VALUES(barangay_id),
            updated_at = NOW()
    ");

    mysqli_stmt_bind_param(
        $profile,
        "isssssssi",
        $account_id,
        $date_of_birth,
        $gender,
        $phone_db,
        $school,
        $course,
        $education_level,
        $year_level,
        $barangay_id
    );


    if(!mysqli_stmt_execute($profile)){

        error_log("SK KONEXT Profile Registration Error: " . mysqli_stmt_error($profile));

        mysqli_query($conn,"
            DELETE FROM applicant_accounts
            WHERE id = $account_id
            AND is_verified = 0
        ");

        $message = "Unable to save your resident information. Please try again.";
        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       SEND OTP THROUGH EMAIL
    ========================================= */

    $email_sent = sendOtpEmail(
        $email,
        $fullname,
        $otp
    );


    /* =========================================
       CHECK EMAIL SENDING
    ========================================= */

    if($email_sent !== true){

        mysqli_query($conn,"
            DELETE FROM applicant_profiles
            WHERE applicant_id = $account_id
        ");

        mysqli_query($conn,"
            DELETE FROM applicant_accounts
            WHERE id = $account_id
            AND is_verified = 0
        ");

        unset($_SESSION['pending_account_id']);

        $message = "We could not send the verification email. Please check your email address and try again.";

        $message_type = "danger";

        goto show_form;
    }


    /* =========================================
       SAVE PENDING ACCOUNT SESSION
    ========================================= */

    $_SESSION['pending_account_id'] = $account_id;


    /* =========================================
       REDIRECT TO EMAIL VERIFICATION
    ========================================= */

    header("Location: verify_registration.php");
    exit();

}


show_form:

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<title>Register | SK KONEXT</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
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
    padding:40px 20px;
    color:white;
    background:
        radial-gradient(circle at 15% 20%,rgba(34,197,94,.18),transparent 30%),
        radial-gradient(circle at 85% 80%,rgba(16,185,129,.13),transparent 30%),
        linear-gradient(135deg,#020807 0%,#06140f 45%,#020605 100%);
    overflow-x:hidden;
    position:relative;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    opacity:.18;
    background-image:
        linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
    background-size:50px 50px;
}

.orb{
    position:fixed;
    border-radius:50%;
    filter:blur(2px);
    pointer-events:none;
    animation:floatOrb 8s ease-in-out infinite;
}

.orb.one{
    width:240px;
    height:240px;
    background:rgba(34,197,94,.12);
    top:-80px;
    left:-70px;
}

.orb.two{
    width:300px;
    height:300px;
    background:rgba(16,185,129,.08);
    bottom:-120px;
    right:-100px;
    animation-delay:2s;
}

@keyframes floatOrb{
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-25px);}
}

.register-card{
    position:relative;
    z-index:2;
    width:100%;
    max-width:600px;
    padding:38px;
    border-radius:28px;
    background:linear-gradient(145deg,rgba(255,255,255,.105),rgba(255,255,255,.045));
    border:1px solid rgba(255,255,255,.12);
    backdrop-filter:blur(22px);
    -webkit-backdrop-filter:blur(22px);
    box-shadow:0 30px 80px rgba(0,0,0,.55),0 0 45px rgba(34,197,94,.07);
    animation:cardEnter .7s ease;
}

@keyframes cardEnter{
    from{opacity:0;transform:translateY(25px) scale(.98);}
    to{opacity:1;transform:translateY(0) scale(1);}
}

.portal-badge{
    width:max-content;
    margin:0 auto 18px;
    padding:7px 13px;
    border-radius:50px;
    background:rgba(34,197,94,.10);
    border:1px solid rgba(74,222,128,.18);
    color:#86efac;
    font-size:10px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
}

.logo-wrap{
    display:flex;
    justify-content:center;
    margin-bottom:17px;
}

.logo-icon{
    width:70px;
    height:70px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:22px;
    background:linear-gradient(145deg,#22c55e,#15803d);
    box-shadow:0 12px 30px rgba(34,197,94,.24),inset 0 1px rgba(255,255,255,.25);
    font-size:30px;
    color:white;
}

.logo{
    text-align:center;
    font-size:30px;
    line-height:1.2;
    font-weight:800;
    letter-spacing:-.7px;
    margin-bottom:7px;
}

.logo span{color:#4ade80;}

.subtitle{
    text-align:center;
    color:rgba(255,255,255,.58);
    font-size:13px;
    margin-bottom:28px;
}

.section-title{
    font-size:12px;
    font-weight:700;
    color:#4ade80;
    text-transform:uppercase;
    letter-spacing:1px;
    margin:25px 0 15px;
    padding-bottom:9px;
    border-bottom:1px solid rgba(255,255,255,.09);
}

.form-group{margin-bottom:15px;}

.form-group label{
    display:block;
    margin-bottom:7px;
    color:rgba(255,255,255,.88);
    font-size:12px;
    font-weight:600;
}

.form-group label small{
    color:rgba(255,255,255,.42);
    font-weight:400;
}

.input-wrap{position:relative;}

.input-icon{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:#4ade80;
    font-size:14px;
    pointer-events:none;
}

.form-control{
    width:100%;
    height:52px;
    padding:0 16px 0 45px;
    border-radius:13px;
    border:1px solid rgba(255,255,255,.09);
    outline:none;
    background:rgba(255,255,255,.055);
    color:white;
    font-size:13px;
    transition:.25s ease;
}

.form-control::placeholder{color:rgba(255,255,255,.32);}

.form-control:hover{border-color:rgba(74,222,128,.20);}

.form-control:focus{
    background:rgba(255,255,255,.08);
    border-color:rgba(74,222,128,.65);
    box-shadow:0 0 0 3px rgba(34,197,94,.08),0 0 20px rgba(34,197,94,.06);
    color:white;
}

select.form-control{
    appearance:auto;
    color:white;
}

select.form-control option{
    background:#10251b;
    color:white;
}

input[type="date"]::-webkit-calendar-picker-indicator{
    filter:invert(1);
    cursor:pointer;
}

.phone-note{
    display:flex;
    align-items:center;
    gap:6px;
    margin-top:7px;
    color:rgba(255,255,255,.40);
    font-size:10px;
}

.phone-note i{color:#4ade80;}

.password-toggle{
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
    color:rgba(255,255,255,.40);
    cursor:pointer;
    font-size:14px;
}

.password-toggle:hover{color:#4ade80;}

.alert{
    border-radius:13px;
    border:1px solid rgba(255,255,255,.10);
    padding:12px 14px;
    margin-bottom:20px;
    font-size:12px;
    background:rgba(255,255,255,.06);
    color:white;
}

.alert-danger{
    background:rgba(239,68,68,.12);
    border-color:rgba(248,113,113,.20);
}

.alert-warning{
    background:rgba(245,158,11,.12);
    border-color:rgba(251,191,36,.20);
}

.register-btn{
    width:100%;
    height:54px;
    margin-top:7px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#4ade80,#16a34a);
    color:#031008;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 12px 28px rgba(34,197,94,.18);
    transition:.25s ease;
}

.register-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 35px rgba(34,197,94,.25);
    background:linear-gradient(135deg,#86efac,#22c55e);
}

.divider{
    display:flex;
    align-items:center;
    gap:12px;
    margin:23px 0 18px;
    color:rgba(255,255,255,.25);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.divider::before,.divider::after{
    content:"";
    height:1px;
    flex:1;
    background:rgba(255,255,255,.08);
}

.back-link{
    display:block;
    text-align:center;
    text-decoration:none;
    color:#4ade80;
    font-size:12px;
    font-weight:500;
    margin-top:12px;
}

.back-link:hover{
    color:#86efac;
    text-decoration:none;
}

.home-link{color:rgba(255,255,255,.45);}

.security-note{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    margin-top:25px;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,.07);
    color:rgba(255,255,255,.32);
    font-size:10px;
    text-align:center;
}

.security-note i{color:#4ade80;}

@media(max-width:600px){
    body{padding:20px 14px;}
    .register-card{padding:27px 22px;border-radius:22px;}
    .logo{font-size:27px;}
    .logo-icon{width:62px;height:62px;border-radius:19px;font-size:26px;}
    .subtitle{font-size:12px;}
}

</style>

</head>

<body>

<div class="orb one"></div>
<div class="orb two"></div>

<div class="register-card">

    <div class="portal-badge">
        <i class="fa-solid fa-circle-check"></i>
        SK KONEXT PORTAL
    </div>

    <div class="logo-wrap">
        <div class="logo-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    <div class="logo">
        SK <span>KONEXT</span>
    </div>

    <div class="subtitle">
        Create your applicant and resident account
    </div>

    <?php if($message != ""){ ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="section-title">
            <i class="fa-solid fa-user"></i>
            Personal Information
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user input-icon"></i>
                <input
                    type="text"
                    name="fullname"
                    class="form-control"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label>Email <small>(Required for verification)</small></label>
            <div class="input-wrap">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label>Mobile Number</label>
            <div class="input-wrap">
                <i class="fa-solid fa-mobile-screen-button input-icon"></i>
                <input
                    type="tel"
                    name="phone"
                    class="form-control"
                    placeholder="09XXXXXXXXX"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                    maxlength="13"
                    inputmode="tel"
                    pattern="(09[0-9]{9}|\+639[0-9]{9}|639[0-9]{9})"
                    oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                    required
                >
            </div>
            <div class="phone-note">
                <i class="fa-solid fa-circle-info"></i>
                Your mobile number will be verified separately in your dashboard.
            </div>
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <div class="input-wrap">
                <i class="fa-solid fa-calendar input-icon"></i>
                <input
                    type="date"
                    name="date_of_birth"
                    class="form-control"
                    max="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <div class="input-wrap">
                <i class="fa-solid fa-venus-mars input-icon"></i>
                <select name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?= (($_POST['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= (($_POST['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Prefer not to say" <?= (($_POST['gender'] ?? '') == 'Prefer not to say') ? 'selected' : '' ?>>Prefer not to say</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Barangay</label>
            <div class="input-wrap">
                <i class="fa-solid fa-location-dot input-icon"></i>
                <select name="barangay_id" class="form-control" required>
                    <option value="">Select Barangay</option>
                    <?php foreach($barangays as $barangay){ ?>
                        <option
                            value="<?= (int)$barangay['id'] ?>"
                            <?= ((int)($_POST['barangay_id'] ?? 0) === (int)$barangay['id']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($barangay['name']) ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>


        <div class="section-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Educational Background
        </div>

        <div class="form-group">
            <label>Education Level</label>
            <div class="input-wrap">
                <i class="fa-solid fa-book-open input-icon"></i>
                <select name="education_level" class="form-control" required>
                    <option value="">Select Education Level</option>
                    <?php
                    $education_options = [
                        'Elementary',
                        'Junior High School',
                        'Senior High School',
                        'College',
                        'Vocational / Technical',
                        'Graduate Studies',
                        'Other'
                    ];

                    foreach($education_options as $option){
                    ?>
                        <option
                            value="<?= htmlspecialchars($option) ?>"
                            <?= (($_POST['education_level'] ?? '') == $option) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Year Level / Grade Level</label>
            <div class="input-wrap">
                <i class="fa-solid fa-layer-group input-icon"></i>
                <select name="year_level" class="form-control" required>
                    <option value="">Select Year / Grade Level</option>
                    <?php
                    $year_options = [
                        'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
                        'Grade 7','Grade 8','Grade 9','Grade 10',
                        'Grade 11','Grade 12',
                        '1st Year','2nd Year','3rd Year','4th Year','5th Year',
                        'Graduate','Not Applicable'
                    ];

                    foreach($year_options as $option){
                    ?>
                        <option
                            value="<?= htmlspecialchars($option) ?>"
                            <?= (($_POST['year_level'] ?? '') == $option) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>School Name</label>
            <div class="input-wrap">
                <i class="fa-solid fa-school input-icon"></i>
                <input
                    type="text"
                    name="school"
                    class="form-control"
                    placeholder="Enter your school name"
                    value="<?= htmlspecialchars($_POST['school'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label>Course / Strand</label>
            <div class="input-wrap">
                <i class="fa-solid fa-book input-icon"></i>
                <input
                    type="text"
                    name="course"
                    class="form-control"
                    placeholder="Example: BSIT, STEM, HUMSS"
                    value="<?= htmlspecialchars($_POST['course'] ?? '') ?>"
                    required
                >
            </div>
        </div>


        <div class="section-title">
            <i class="fa-solid fa-lock"></i>
            Account Security
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="Create a password"
                    required
                >
                <i
                    class="fa-solid fa-eye password-toggle"
                    onclick="togglePassword('password', this)"
                ></i>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    class="form-control"
                    placeholder="Confirm your password"
                    required
                >
                <i
                    class="fa-solid fa-eye password-toggle"
                    onclick="togglePassword('confirm_password', this)"
                ></i>
            </div>
        </div>

        <button
            type="submit"
            name="register"
            class="register-btn"
        >
            <i class="fa-solid fa-envelope"></i>
            Create Account & Verify Email
        </button>

    </form>

    <div class="divider">Account Access</div>

    <a href="login.php" class="back-link">
        <i class="fa-solid fa-right-to-bracket"></i>
        Already have an account? Login
    </a>

    <a href="index.php" class="back-link home-link">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="security-note">
        <i class="fa-solid fa-shield-halved"></i>
        Your account will be verified through email before access.
    </div>

</div>

<script>

function togglePassword(inputId, icon){

    const input = document.getElementById(inputId);

    if(input.type === "password"){

        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

    }else{

        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");

    }

}

</script>

</body>
</html>
