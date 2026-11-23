
<?php

include("db.php");
session_start();

/* =========================================
   REQUIRE APPLICANT LOGIN
========================================= */

if (
    !isset($_SESSION['applicant_id']) ||
    (int)$_SESSION['applicant_id'] <= 0
) {
    header("Location: login.php?apply=1");
    exit();
}

$applicant_id = (int)$_SESSION['applicant_id'];

$error = "";
$success = "";

/* =========================================
   GET VERIFIED APPLICANT ACCOUNT
========================================= */

$applicant_account_query = mysqli_query($conn, "
    SELECT fullname, email, phone, phone_verified
    FROM applicant_accounts
    WHERE id = $applicant_id
    AND is_verified = 1
    LIMIT 1
");

$applicant_account = $applicant_account_query
    ? mysqli_fetch_assoc($applicant_account_query)
    : null;

if (!$applicant_account) {
    session_unset();
    session_destroy();
    header("Location: login.php?apply=1");
    exit();
}

/* =========================================
   REQUIRE APPROVED PHONE VERIFICATION
========================================= */

if ((int)$applicant_account['phone_verified'] !== 1) {
    header("Location: applicant_settings.php?phone=required");
    exit();
}

/* =========================================
   GET SAVED APPLICANT PROFILE
========================================= */

$profile_query = mysqli_query($conn, "
    SELECT *
    FROM applicant_profiles
    WHERE applicant_id = $applicant_id
    LIMIT 1
");

$applicant_profile = $profile_query
    ? mysqli_fetch_assoc($profile_query)
    : null;

/* =========================================
   GET LATEST APPLICATION AS FALLBACK
========================================= */

$latest_application_query = mysqli_query($conn, "
    SELECT *
    FROM scholarship_applications
    WHERE applicant_id = $applicant_id
    ORDER BY id DESC
    LIMIT 1
");

$latest_application = $latest_application_query
    ? mysqli_fetch_assoc($latest_application_query)
    : null;

/* =========================================
   AUTO-FILL VALUES
========================================= */

$prefill_fullname = !empty($applicant_account['fullname'])
    ? $applicant_account['fullname']
    : ($latest_application['fullname'] ?? '');

$prefill_email = !empty($applicant_account['email'])
    ? $applicant_account['email']
    : ($latest_application['email'] ?? '');

$prefill_contact = !empty($applicant_profile['contact_number'])
    ? $applicant_profile['contact_number']
    : (!empty($latest_application['contact_number'])
        ? $latest_application['contact_number']
        : ($applicant_account['phone'] ?? ''));

$prefill_school = !empty($applicant_profile['school'])
    ? $applicant_profile['school']
    : ($latest_application['school'] ?? '');

$prefill_course = !empty($applicant_profile['course'])
    ? $applicant_profile['course']
    : ($latest_application['course'] ?? '');

$prefill_education = !empty($applicant_profile['education_level'])
    ? $applicant_profile['education_level']
    : ($latest_application['education_level'] ?? '');

$prefill_year = !empty($applicant_profile['year_level'])
    ? $applicant_profile['year_level']
    : ($latest_application['year_level'] ?? '');

$prefill_barangay = !empty($applicant_profile['barangay_id'])
    ? $applicant_profile['barangay_id']
    : ($latest_application['barangay_id'] ?? '');

/* =========================================
   APPLY
========================================= */

if (isset($_POST['apply'])) {

    /* DATA PRIVACY CONSENT */

    if (
        !isset($_POST['privacy_consent']) ||
        $_POST['privacy_consent'] !== '1'
    ) {
        $error = "Please read and agree to the Data Privacy Consent before submitting.";
    }

    /* VALIDATE ID IMAGE */

    if (
        !isset($_FILES['id_image']) ||
        $_FILES['id_image']['error'] !== UPLOAD_ERR_OK
    ) {
        $error = $error ?: "Please upload a valid ID image.";
    } else {

        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        $image_info = @getimagesize($_FILES['id_image']['tmp_name']);
        $image_mime = $image_info['mime'] ?? '';

        if (
            !$image_info ||
            !isset($allowed_mimes[$image_mime]) ||
            $_FILES['id_image']['size'] > 5 * 1024 * 1024
        ) {
            $error = "ID image must be a JPG or PNG image and must not exceed 5 MB.";
        }
    }

    /* GET FORM VALUES */

    $fullname = mysqli_real_escape_string(
        $conn,
        trim($_POST['fullname'] ?? '')
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'] ?? '')
    );

    $contact = mysqli_real_escape_string(
        $conn,
        trim($_POST['contact'] ?? '')
    );

    $school = mysqli_real_escape_string(
        $conn,
        trim($_POST['school'] ?? '')
    );

    $course = mysqli_real_escape_string(
        $conn,
        trim($_POST['course'] ?? '')
    );

    $education_level = mysqli_real_escape_string(
        $conn,
        $_POST['education_level'] ?? ''
    );

    $year_level = mysqli_real_escape_string(
        $conn,
        $_POST['year_level'] ?? ''
    );

    $barangay_id = (int)($_POST['barangay_id'] ?? 0);
    $batch_id = (int)($_POST['batch_id'] ?? 0);

    /* =========================================
       CHECK REQUIRED FIELDS
    ========================================= */

    if (
        !$error &&
        (
            $fullname === '' ||
            $email === '' ||
            $contact === '' ||
            $school === '' ||
            $course === '' ||
            $education_level === '' ||
            $year_level === '' ||
            $barangay_id <= 0
        )
    ) {
        $error = "Please complete all required fields.";
    }

    /* =========================================
       CHECK ACTIVE BATCH
    ========================================= */

    if (!$error) {

        $batch_check = mysqli_query($conn, "
            SELECT *
            FROM scholar_batches
            WHERE id = $batch_id
            AND status = 'Active'
            AND application_status = 'Open'
            AND start_date <= CURDATE()
            AND (
                end_date IS NULL
                OR end_date >= CURDATE()
            )
            LIMIT 1
        ");

        if (!$batch_check || mysqli_num_rows($batch_check) == 0) {

            $error = "This scholarship program is currently closed or unavailable.";

        } else {

            $batch = mysqli_fetch_assoc($batch_check);

            /* =========================================
               CHECK DUPLICATE APPLICATION
            ========================================= */

            $existing_application = mysqli_query($conn, "
                SELECT id
                FROM scholarship_applications
                WHERE applicant_id = $applicant_id
                AND batch_id = $batch_id
                LIMIT 1
            ");

            if (
                $existing_application &&
                mysqli_num_rows($existing_application) > 0
            ) {
                $error = "You have already submitted an application for this scholarship program.";
            }
        }
    }

    /* =========================================
       UPLOAD ID IMAGE
    ========================================= */

    if (!$error) {

        $upload_dir = __DIR__ . '/uploads/scholarship_ids/';

        if (
            !is_dir($upload_dir) &&
            !mkdir($upload_dir, 0755, true)
        ) {
            $error = "Unable to prepare the ID image upload folder.";
        }

        if (!$error) {

            $image_extension = $allowed_mimes[$image_mime];

            $image_filename =
                'applicant_' . $applicant_id . '_' .
                bin2hex(random_bytes(16)) . '.' .
                $image_extension;

            $image_absolute_path = $upload_dir . $image_filename;

            $id_image_path =
                'uploads/scholarship_ids/' . $image_filename;

            if (
                !move_uploaded_file(
                    $_FILES['id_image']['tmp_name'],
                    $image_absolute_path
                )
            ) {
                $error = "Unable to upload the ID image. Please try again.";
            }
        }
    }

    /* =========================================
       INSERT APPLICATION
    ========================================= */

    if (!$error) {

        $consent_value = 1;

        $escaped_image_path = mysqli_real_escape_string(
            $conn,
            $id_image_path
        );

        $insert = mysqli_query($conn, "
            INSERT INTO scholarship_applications(
                applicant_id,
                fullname,
                email,
                contact_number,
                school,
                course,
                education_level,
                year_level,
                barangay_id,
                batch_id,
                id_image,
                privacy_consent,
                privacy_consent_at,
                status,
                application_date
            )
            VALUES(
                $applicant_id,
                '$fullname',
                '$email',
                '$contact',
                '$school',
                '$course',
                '$education_level',
                '$year_level',
                $barangay_id,
                $batch_id,
                '$escaped_image_path',
                $consent_value,
                NOW(),
                'Pending',
                NOW()
            )
        ");

        if ($insert) {

            /* =========================================
               SAVE DETAILS FOR FUTURE AUTO-FILL
            ========================================= */

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
                    contact_number = VALUES(contact_number),
                    school = VALUES(school),
                    course = VALUES(course),
                    education_level = VALUES(education_level),
                    year_level = VALUES(year_level),
                    barangay_id = VALUES(barangay_id)
            ");

            if ($save_profile) {

                mysqli_stmt_bind_param(
                    $save_profile,
                    "isssssi",
                    $applicant_id,
                    $contact,
                    $school,
                    $course,
                    $education_level,
                    $year_level,
                    $barangay_id
                );

                mysqli_stmt_execute($save_profile);
                mysqli_stmt_close($save_profile);
            }

            $success =
                "Application submitted successfully! " .
                "Please wait for the scholarship committee to review your application.";

            /* UPDATE AUTO-FILL VALUES */

            $prefill_fullname = $fullname;
            $prefill_email = $email;
            $prefill_contact = $contact;
            $prefill_school = $school;
            $prefill_course = $course;
            $prefill_education = $education_level;
            $prefill_year = $year_level;
            $prefill_barangay = $barangay_id;

        } else {

            if (
                isset($image_absolute_path) &&
                is_file($image_absolute_path)
            ) {
                unlink($image_absolute_path);
            }

            $error = "Something went wrong while submitting your application.";
        }
    }
}

/* =========================================
   ACTIVE SCHOLARSHIP BATCH
========================================= */

$batch_query = mysqli_query($conn, "
    SELECT *
    FROM scholar_batches
    WHERE status = 'Active'
    AND application_status = 'Open'
    AND start_date <= CURDATE()
    AND (
        end_date IS NULL
        OR end_date >= CURDATE()
    )
    ORDER BY id DESC
    LIMIT 1
");

$active_batch = null;

if ($batch_query && mysqli_num_rows($batch_query) > 0) {
    $active_batch = mysqli_fetch_assoc($batch_query);
}

/* =========================================
   SCHOLARSHIP STATISTICS
========================================= */

$application_count = 0;
$approved_count = 0;
$remaining_slots = 0;

$total_budget = 0;
$budget_per_scholar = 0;
$used_budget = 0;
$remaining_budget = 0;

$applications_percentage = 0;
$approved_percentage = 0;

if ($active_batch) {

    $batch_id_count = (int)$active_batch['id'];
    $total_slots = (int)$active_batch['slots'];
    $total_budget = (float)$active_batch['budget'];

    /* TOTAL APPLICATIONS */

    $count_query = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM scholarship_applications
        WHERE batch_id = $batch_id_count
        AND status != 'Rejected'
    ");

    if ($count_query) {
        $count_data = mysqli_fetch_assoc($count_query);
        $application_count = (int)$count_data['total'];
    }

    /* APPROVED APPLICANTS */

    $approved_query = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM scholarship_applications
        WHERE batch_id = $batch_id_count
        AND status = 'Approved'
    ");

    if ($approved_query) {
        $approved_data = mysqli_fetch_assoc($approved_query);
        $approved_count = (int)$approved_data['total'];
    }

    /* REMAINING SLOTS */

    $remaining_slots = $total_slots - $approved_count;

    if ($remaining_slots < 0) {
        $remaining_slots = 0;
    }

    /* BUDGET PER SCHOLAR */

    if ($total_slots > 0) {
        $budget_per_scholar = $total_budget / $total_slots;
    }

    /* USED BUDGET */

    $used_budget = $approved_count * $budget_per_scholar;

    /* REMAINING BUDGET */

    $remaining_budget = $total_budget - $used_budget;

    if ($remaining_budget < 0) {
        $remaining_budget = 0;
    }

    /* APPROVED PROGRESS */

    if ($total_slots > 0) {

        $approved_percentage =
            ($approved_count / $total_slots) * 100;

        if ($approved_percentage > 100) {
            $approved_percentage = 100;
        }
    }
}

/* =========================================
   BARANGAYS
========================================= */

$barangays = mysqli_query($conn, "
    SELECT id, name
    FROM barangays
    ORDER BY name ASC
");

/* =========================================
   FORM VALUES
========================================= */

$form_fullname = $_POST['fullname'] ?? $prefill_fullname;
$form_email = $_POST['email'] ?? $prefill_email;
$form_contact = $_POST['contact'] ?? $prefill_contact;
$form_school = $_POST['school'] ?? $prefill_school;
$form_course = $_POST['course'] ?? $prefill_course;
$form_education = $_POST['education_level'] ?? $prefill_education;
$form_year = $_POST['year_level'] ?? $prefill_year;
$form_barangay = $_POST['barangay_id'] ?? $prefill_barangay;

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Scholarship Application | SK KONEXT</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

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
    --dark-2:#091810;
    --white:#fff;
    --muted:rgba(255,255,255,.60);
    --glass:rgba(255,255,255,.07);
    --border:rgba(255,255,255,.12);
}

html{
    scroll-behavior:smooth;
}

body{
    min-height:100vh;
    font-family:"DM Sans",sans-serif;
    color:var(--white);
    background:
        radial-gradient(circle at 15% 15%,rgba(74,222,128,.13),transparent 28%),
        radial-gradient(circle at 85% 30%,rgba(22,163,74,.10),transparent 25%),
        var(--dark);
    overflow-x:hidden;
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

/* NAVBAR */

.navbar{
    position:relative;
    z-index:20;
    width:min(1180px,calc(100% - 40px));
    height:70px;
    margin:22px auto 0;
    padding:0 18px 0 22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    background:rgba(6,16,11,.76);
    border:1px solid var(--border);
    border-radius:18px;
    backdrop-filter:blur(18px);
    box-shadow:0 20px 60px rgba(0,0,0,.20);
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-family:"Manrope",sans-serif;
    font-weight:800;
    letter-spacing:.2px;
    white-space:nowrap;
}

.brand img{
    width:42px;
    height:42px;
    object-fit:contain;
}

.nav-links{
    display:flex;
    align-items:center;
    gap:6px;
}

.nav-links a{
    color:rgba(255,255,255,.68);
    padding:10px 14px;
    border-radius:11px;
    font-size:13px;
    font-weight:600;
    transition:.2s ease;
}

.nav-links a:hover,
.nav-links a.active{
    color:#fff;
    background:rgba(255,255,255,.07);
}

.nav-account{
    display:flex;
    align-items:center;
    gap:8px;
    padding:9px 13px;
    border-radius:11px;
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.09);
    color:rgba(255,255,255,.82);
    font-size:13px;
    font-weight:600;
}

.nav-account i{
    color:var(--green);
}

.nav-logout{
    display:flex;
    align-items:center;
    justify-content:center;
    width:40px;
    height:40px;
    border-radius:11px;
    background:rgba(248,113,113,.08);
    border:1px solid rgba(248,113,113,.18);
    color:#fca5a5;
    transition:.2s ease;
}

.nav-logout:hover{
    background:rgba(248,113,113,.16);
    color:#fff;
}

/* HERO */

.hero{
    position:relative;
    z-index:1;
    max-width:1180px;
    margin:0 auto;
    padding:90px 20px 55px;
    text-align:center;
}

.live-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border:1px solid rgba(74,222,128,.28);
    border-radius:999px;
    background:rgba(74,222,128,.08);
    color:var(--green-light);
    font-size:12px;
    font-weight:700;
    letter-spacing:.8px;
}

.live-dot{
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--green);
    box-shadow:0 0 15px var(--green);
}

.hero h1{
    margin:22px auto 14px;
    max-width:900px;
    font-family:"Manrope",sans-serif;
    font-size:clamp(42px,7vw,76px);
    line-height:.98;
    letter-spacing:-3px;
    font-weight:900;
}

.hero h1 span{
    color:var(--green);
}

.hero p{
    max-width:690px;
    margin:0 auto;
    color:var(--muted);
    font-size:16px;
    line-height:1.75;
}

/* MAIN */

.main-container{
    position:relative;
    z-index:2;
    width:min(1000px,calc(100% - 40px));
    margin:15px auto 80px;
}

/* ALERTS */

.alert{
    border-radius:16px;
    padding:15px 18px;
    margin-bottom:18px;
    border:1px solid var(--border);
    background:var(--glass);
    color:#fff;
    font-size:14px;
}

.alert-success{
    border-color:rgba(74,222,128,.30);
    background:rgba(74,222,128,.09);
}

.alert-danger{
    border-color:rgba(248,113,113,.30);
    background:rgba(248,113,113,.08);
}

/* GLASS CARDS */

.program-card,
.form-card,
.closed-card{
    background:rgba(255,255,255,.055);
    border:1px solid var(--border);
    border-radius:24px;
    box-shadow:0 25px 80px rgba(0,0,0,.22);
    backdrop-filter:blur(16px);
}

.program-card{
    padding:30px;
    margin-bottom:20px;
}

/* PROGRAM HEADER */

.program-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:25px;
}

.program-title{
    display:flex;
    align-items:center;
    gap:14px;
}

.program-icon{
    width:54px;
    height:54px;
    flex:0 0 54px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:16px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.20);
    color:var(--green);
    font-size:21px;
}

.program-title h2{
    font-family:"Manrope",sans-serif;
    font-size:19px;
    font-weight:800;
    margin:0 0 3px;
}

.program-title span{
    color:var(--muted);
    font-size:12px;
}

.status-badge{
    white-space:nowrap;
    padding:8px 13px;
    border-radius:999px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.22);
    color:var(--green-light);
    font-size:11px;
    font-weight:800;
}

.program-name{
    color:#fff;
    font-family:"Manrope",sans-serif;
    font-size:29px;
    line-height:1.2;
    font-weight:800;
    margin-bottom:23px;
}

/* INFO GRID */

.info-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
}

.info-box{
    min-width:0;
    padding:17px;
    border-radius:16px;
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.09);
}

.info-box i{
    color:var(--green);
    margin-bottom:9px;
    font-size:16px;
}

.info-box small{
    display:block;
    color:rgba(255,255,255,.48);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.6px;
}

.info-box strong{
    display:block;
    margin-top:4px;
    color:#fff;
    font-size:14px;
}

/* PROGRESS */

.progress-section{
    margin-top:24px;
    padding:18px;
    border-radius:17px;
    background:rgba(255,255,255,.035);
    border:1px solid rgba(255,255,255,.07);
}

.progress-heading{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:15px;
    margin-bottom:10px;
}

.progress-label{
    color:rgba(255,255,255,.82);
    font-size:12px;
    font-weight:700;
}

.progress-label i{
    color:var(--green);
    margin-right:5px;
}

.progress-value{
    color:var(--green-light);
    font-size:12px;
    font-weight:800;
    white-space:nowrap;
}

.progress{
    width:100%;
    height:10px;
    overflow:hidden;
    border-radius:20px;
    background:rgba(255,255,255,.09);
}

.progress-bar{
    height:100%;
    border-radius:20px;
    background:var(--green);
    box-shadow:0 0 18px rgba(74,222,128,.35);
    transition:width .5s ease;
}

.progress-subtext{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:8px;
    color:rgba(255,255,255,.40);
    font-size:10px;
}

/* APPLICATION COUNT */

.application-number{
    margin-top:24px;
    padding:18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    border-radius:17px;
    background:rgba(255,255,255,.035);
    border:1px solid rgba(255,255,255,.07);
}

.application-number-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.application-number-icon{
    width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:rgba(74,222,128,.08);
    border:1px solid rgba(74,222,128,.15);
    color:var(--green);
}

.application-number-text small{
    display:block;
    color:rgba(255,255,255,.45);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.6px;
}

.application-number-text strong{
    display:block;
    margin-top:3px;
    color:#fff;
    font-size:20px;
    font-weight:800;
}

/* BUDGET SUMMARY */

.budget-summary{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
    margin-top:12px;
}

.budget-box{
    padding:17px;
    border-radius:16px;
    background:rgba(255,255,255,.035);
    border:1px solid rgba(255,255,255,.08);
}

.budget-box i{
    color:var(--green);
    font-size:15px;
    margin-bottom:8px;
}

.budget-box small{
    display:block;
    color:rgba(255,255,255,.45);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.6px;
}

.budget-box strong{
    display:block;
    margin-top:5px;
    color:#fff;
    font-size:15px;
    font-weight:800;
}

.budget-box.remaining strong{
    color:var(--green-light);
}

/* FORM */

.form-card{
    padding:32px;
}

.form-heading{
    margin-bottom:25px;
}

.form-heading h2{
    font-family:"Manrope",sans-serif;
    font-size:23px;
    font-weight:800;
    margin-bottom:5px;
}

.form-heading h2 i{
    color:var(--green);
}

.form-heading p{
    color:var(--muted);
    font-size:13px;
    line-height:1.6;
}

.btn-back{
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:10px 15px;
    margin-bottom:18px;
    border-radius:11px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.14);
    color:rgba(255,255,255,.85);
    font-size:12px;
    font-weight:700;
    transition:.2s ease;
}

.btn-back:hover{
    background:rgba(74,222,128,.10);
    border-color:rgba(74,222,128,.35);
    color:var(--green-light);
    transform:translateX(-3px);
}

.form-row{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:0 18px;
}

.form-group{
    margin-bottom:19px;
}

.form-group label{
    display:block;
    color:rgba(255,255,255,.82);
    font-size:12px;
    font-weight:700;
    margin-bottom:8px;
}

.form-control{
    width:100%;
    height:52px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.12);
    outline:none;
    background:rgba(0,0,0,.18);
    color:#fff;
    padding:0 15px;
    font-family:"DM Sans",sans-serif;
    font-size:14px;
    transition:.2s ease;
}

.form-control::placeholder{
    color:rgba(255,255,255,.32);
}

.form-control:focus{
    border-color:rgba(74,222,128,.55);
    box-shadow:0 0 0 3px rgba(74,222,128,.08);
}

select.form-control{
    cursor:pointer;
}

select.form-control option{
    background:#0b1910;
    color:#fff;
}

.form-help{
    display:block;
    color:rgba(255,255,255,.40);
    font-size:11px;
    margin-top:6px;
}

.year-note{
    display:none;
    color:var(--green-light);
    font-size:11px;
    margin-top:6px;
}

.btn-submit{
    width:100%;
    height:55px;
    border:0;
    border-radius:15px;
    background:var(--green);
    color:#06100b;
    font-family:"DM Sans",sans-serif;
    font-size:14px;
    font-weight:800;
    cursor:pointer;
    transition:.22s ease;
}

.btn-submit:hover:not(:disabled){
    background:#86efac;
    transform:translateY(-1px);
    box-shadow:0 12px 30px rgba(74,222,128,.18);
}

/* CLOSED */

.closed-card{
    padding:65px 30px;
    text-align:center;
}

.closed-icon{
    width:78px;
    height:78px;
    margin:0 auto 20px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    color:var(--green);
    background:rgba(74,222,128,.08);
    border:1px solid rgba(74,222,128,.18);
    font-size:28px;
}

.closed-card h2{
    font-family:"Manrope",sans-serif;
    font-size:25px;
    font-weight:800;
}

.closed-card p{
    max-width:570px;
    margin:10px auto 0;
    color:var(--muted);
    font-size:14px;
    line-height:1.7;
}

/* FOOTER */

.footer{
    position:relative;
    z-index:2;
    width:min(1180px,calc(100% - 40px));
    margin:0 auto;
    padding:25px 0 35px;
    border-top:1px solid rgba(255,255,255,.08);
    color:rgba(255,255,255,.40);
    text-align:center;
    font-size:12px;
}

/* RESPONSIVE */

@media(max-width:900px){

    .nav-links a{
        padding:9px 8px;
        font-size:12px;
    }

    .info-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .budget-summary{
        grid-template-columns:1fr;
    }
}

@media(max-width:700px){

    .navbar{
        width:calc(100% - 24px);
        margin-top:12px;
        height:auto;
        min-height:64px;
        padding:10px 12px;
    }

    .nav-links{
        display:none;
    }

    .hero{
        padding:75px 15px 45px;
    }

    .hero h1{
        letter-spacing:-2px;
    }

    .main-container{
        width:calc(100% - 24px);
        margin-bottom:55px;
    }

    .program-card,
    .form-card{
        padding:22px;
    }

    .program-header{
        align-items:flex-start;
        flex-direction:column;
    }

    .status-badge{
        align-self:flex-start;
    }

    .progress-heading{
        flex-direction:column;
        gap:5px;
    }

    .application-number{
        align-items:flex-start;
        flex-direction:column;
    }
}

@media(max-width:500px){

    .brand{
        font-size:14px;
    }

    .brand img{
        width:36px;
        height:36px;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .program-name{
        font-size:24px;
    }

    .form-card{
        padding:19px;
    }

    .budget-summary{
        grid-template-columns:1fr;
    }

    .form-row{
        grid-template-columns:1fr;
    }
}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <a href="applicant_dashboard.php" class="brand">

        <img src="assets/sk-logo.png.webp" alt="SK KONEXT Logo">

        <span>SK KONEXT</span>

    </a>

    <div class="nav-links" style="margin-left:auto;">

        <a href="scholarship.php" class="active">
            <i class="fa-solid fa-graduation-cap"></i>
            Scholarship
        </a>

        <a href="applicant_settings.php">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>

    </div>

    <div style="display:flex;align-items:center;gap:8px;">

        <div class="nav-account">

            <i class="fa-solid fa-circle-user"></i>

            <span>
                <?= htmlspecialchars($_SESSION['applicant_name'] ?? $applicant_account['fullname'] ?? 'Applicant') ?>
            </span>

        </div>

        <a
            href="applicant_dashboard.php?logout=1"
            class="nav-logout"
            title="Logout"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

    </div>

</nav>

<!-- HERO -->

<section class="hero">

    <div class="live-badge">
        <span class="live-dot"></span>
        SCHOLARSHIP APPLICATION
    </div>

    <h1>
        Build Your Future<br>
        <span>Through Education.</span>
    </h1>

    <p>
        Apply for the current SK KONEXT scholarship program
        and take the next step toward your educational goals.
    </p>

</section>

<main class="main-container">

    <?php if($success){ ?>

        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($success) ?>
        </div>

    <?php } ?>

    <?php if($error){ ?>

        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>

    <?php } ?>

    <?php if($active_batch){ ?>

        <!-- PROGRAM CARD -->

        <div class="program-card">

            <div class="program-header">

                <div class="program-title">

                    <div class="program-icon">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>

                    <div>
                        <h2>Current Scholarship Program</h2>
                        <span>Applications are currently open</span>
                    </div>

                </div>

                <div class="status-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    APPLICATIONS OPEN
                </div>

            </div>

            <div class="program-name">
                <?= htmlspecialchars($active_batch['title']) ?>
            </div>

            <!-- BASIC INFORMATION -->

            <div class="info-grid">

                <div class="info-box">
                    <i class="fa-solid fa-peso-sign"></i>
                    <small>Scholarship Budget</small>
                    <strong>
                        ₱<?= number_format($active_batch['budget'], 2) ?>
                    </strong>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-users"></i>
                    <small>Scholarship Slots</small>
                    <strong><?= number_format($remaining_slots) ?></strong>
                </div>

                <div class="info-box">
                    <i class="fa-regular fa-calendar"></i>
                    <small>Start Date</small>
                    <strong>
                        <?= date("M d, Y", strtotime($active_batch['start_date'])) ?>
                    </strong>
                </div>

                <div class="info-box">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <small>End Date</small>
                    <strong>
                        <?php
                        if(!empty($active_batch['end_date'])){
                            echo date("M d, Y", strtotime($active_batch['end_date']));
                        }else{
                            echo "No Deadline";
                        }
                        ?>
                    </strong>
                </div>

            </div>

            <?php if($active_batch['slots'] > 0){ ?>

                <!-- TOTAL APPLICATIONS -->

                <div class="application-number">

                    <div class="application-number-left">

                        <div class="application-number-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>

                        <div class="application-number-text">
                            <small>Total Applications</small>
                            <strong><?= number_format($application_count) ?></strong>
                        </div>

                    </div>

                </div>

                <!-- APPROVED APPLICANTS -->

                <div class="progress-section">

                    <div class="progress-heading">

                        <span class="progress-label">
                            <i class="fa-solid fa-circle-check"></i>
                            Approved Applicants
                        </span>

                        <span class="progress-value">
                            <?= number_format($approved_count) ?>
                            /
                            <?= number_format($active_batch['slots']) ?>
                        </span>

                    </div>

                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="width:<?= $approved_percentage ?>%"
                        ></div>

                    </div>

                    <div class="progress-subtext">

                        <span>Approved scholarship applicants</span>

                        <span>
                            <?= number_format($approved_percentage, 1) ?>%
                        </span>

                    </div>

                </div>

                <!-- BUDGET SUMMARY -->

                <div class="budget-summary">

                    <div class="budget-box">
                        <i class="fa-solid fa-coins"></i>
                        <small>Budget Per Scholar</small>
                        <strong>
                            ₱<?= number_format($budget_per_scholar, 2) ?>
                        </strong>
                    </div>

                    <div class="budget-box">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                        <small>Used Budget</small>
                        <strong>
                            ₱<?= number_format($used_budget, 2) ?>
                        </strong>
                    </div>

                    <div class="budget-box remaining">
                        <i class="fa-solid fa-wallet"></i>
                        <small>Remaining Budget</small>
                        <strong>
                            ₱<?= number_format($remaining_budget, 2) ?>
                        </strong>
                    </div>

                </div>

            <?php } ?>

        </div>

        <!-- APPLICATION FORM -->

        <div class="form-card">

            <a href="applicant_dashboard.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Dashboard
            </a>

            <div class="form-heading">

                <h2>
                    <i class="fa-solid fa-file-pen"></i>
                    Application Form
                </h2>

                <p>
                    Please provide accurate information before submitting your application.
                    Your saved applicant details will automatically appear below.
                </p>

            </div>

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="batch_id"
                    value="<?= (int)$active_batch['id'] ?>"
                >

                <div class="form-row">

                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label>Full Name</label>

                        <input
                            type="text"
                            name="fullname"
                            class="form-control"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($form_fullname) ?>"
                            required
                        >

                    </div>

                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>Email Address</label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($form_email) ?>"
                            required
                        >

                    </div>

                    <!-- CONTACT -->

                    <div class="form-group">

                        <label>Contact Number</label>

                        <input
                            type="text"
                            name="contact"
                            class="form-control"
                            placeholder="09XXXXXXXXX"
                            value="<?= htmlspecialchars($form_contact) ?>"
                            required
                        >

                    </div>

                    <!-- BARANGAY -->

                    <div class="form-group">

                        <label>Barangay</label>

                        <select
                            name="barangay_id"
                            class="form-control"
                            required
                        >

                            <option value="">Select your barangay</option>

                            <?php if($barangays){ ?>

                                <?php while($b = mysqli_fetch_assoc($barangays)){ ?>

                                    <option
                                        value="<?= (int)$b['id'] ?>"
                                        <?= (string)$form_barangay === (string)$b['id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($b['name']) ?>
                                    </option>

                                <?php } ?>

                            <?php } ?>

                        </select>

                    </div>

                    <!-- EDUCATION LEVEL -->

                    <div class="form-group">

                        <label>Education Level</label>

                        <select
                            name="education_level"
                            id="education_level"
                            class="form-control"
                            required
                        >

                            <option value="">Select education level</option>

                            <option
                                value="Senior High"
                                <?= $form_education === 'Senior High' ? 'selected' : '' ?>
                            >
                                Senior High
                            </option>

                            <option
                                value="College"
                                <?= $form_education === 'College' ? 'selected' : '' ?>
                            >
                                College
                            </option>

                        </select>

                    </div>

                    <!-- YEAR LEVEL -->

                    <div class="form-group">

                        <label>Year Level</label>

                        <select
                            name="year_level"
                            id="year_level"
                            class="form-control"
                            required
                            disabled
                        >

                            <option value="">Select education level first</option>

                        </select>

                        <small class="year-note" id="year_note"></small>

                    </div>

                    <!-- SCHOOL -->

                    <div class="form-group">

                        <label>School</label>

                        <input
                            type="text"
                            name="school"
                            class="form-control"
                            placeholder="Enter your school"
                            value="<?= htmlspecialchars($form_school) ?>"
                            required
                        >

                    </div>

                    <!-- COURSE -->

                    <div class="form-group">

                        <label>Course / Strand</label>

                        <input
                            type="text"
                            name="course"
                            class="form-control"
                            placeholder="Enter your course or strand"
                            value="<?= htmlspecialchars($form_course) ?>"
                            required
                        >

                    </div>

                    <!-- ID IMAGE UPLOAD -->

                    <div class="form-group">

                        <label for="id_image">
                            Valid ID Image (JPG or PNG, max 5 MB)
                        </label>

                        <input
                            type="file"
                            name="id_image"
                            id="id_image"
                            class="form-control"
                            accept="image/jpeg,image/png"
                            required
                        >

                        <small class="form-help">
                            Upload a clear image of your ID. The superadmin can review it with your application.
                        </small>

                        <img
                            id="id_image_preview"
                            alt="ID image preview"
                            style="display:none;max-width:220px;max-height:220px;margin-top:10px;border-radius:10px;border:1px solid rgba(255,255,255,.2);"
                        >

                    </div>

                </div>

                <!-- DATA PRIVACY CONSENT -->

                <div
                    class="form-group"
                    style="margin-top:8px;padding:16px;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(255,255,255,.035);"
                >

                    <label style="font-size:13px;margin-bottom:8px;">
                        Data Privacy Consent
                    </label>

                    <p style="color:rgba(255,255,255,.65);font-size:12px;line-height:1.7;margin-bottom:12px;">
                        I consent to the collection, use, and secure storage of the personal information and ID image I provide for the purpose of processing and verifying my scholarship application. I understand that access should be limited to authorized personnel and that my information will be handled according to the applicable data privacy policy.
                    </p>

                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:12px;line-height:1.6;">

                        <input
                            type="checkbox"
                            name="privacy_consent"
                            id="privacy_consent"
                            value="1"
                            required
                            style="margin-top:3px;flex:0 0 auto;"
                        >

                        <span>
                            I have read and agree to the Data Privacy Consent.
                        </span>

                    </label>

                </div>

                <button
                    type="submit"
                    name="apply"
                    id="submit_application"
                    class="btn-submit"
                    disabled
                    style="opacity:.55;cursor:not-allowed;"
                >

                    <i class="fa-solid fa-paper-plane"></i>
                    Submit Scholarship Application

                </button>

            </form>

        </div>

    <?php }else{ ?>

        <!-- CLOSED -->

        <div class="closed-card">

            <div class="closed-icon">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h2>Applications Currently Closed</h2>

            <p>
                There is currently no scholarship program accepting applications.
                Please check back later for the next SK KONEXT scholarship opportunity.
            </p>

        </div>

    <?php } ?>

</main>

<!-- FOOTER -->

<footer class="footer">

    © <?= date('Y') ?> SK KONEXT.
    Scholarship Application Portal.

</footer>

<script>

/* =========================================
   PRIVACY CONSENT
========================================= */

const privacyConsent = document.getElementById("privacy_consent");
const submitApplication = document.getElementById("submit_application");

if(privacyConsent && submitApplication){

    privacyConsent.addEventListener("change", function(){

        submitApplication.disabled = !this.checked;

        submitApplication.style.opacity =
            this.checked ? "1" : ".55";

        submitApplication.style.cursor =
            this.checked ? "pointer" : "not-allowed";

    });

}

/* =========================================
   ID IMAGE PREVIEW AND VALIDATION
========================================= */

const idImageInput = document.getElementById("id_image");
const idImagePreview = document.getElementById("id_image_preview");

if(idImageInput && idImagePreview){

    idImageInput.addEventListener("change", function(){

        const file = this.files && this.files[0];

        if(!file){

            idImagePreview.style.display = "none";
            idImagePreview.removeAttribute("src");

            return;
        }

        if(
            !["image/jpeg", "image/png"].includes(file.type) ||
            file.size > 5 * 1024 * 1024
        ){

            alert("Please select a JPG or PNG image not larger than 5 MB.");

            this.value = "";

            idImagePreview.style.display = "none";
            idImagePreview.removeAttribute("src");

            return;
        }

        idImagePreview.src = URL.createObjectURL(file);
        idImagePreview.style.display = "block";

    });

}

/* =========================================
   EDUCATION LEVEL / YEAR LEVEL
========================================= */

const educationLevel = document.getElementById("education_level");
const yearLevel = document.getElementById("year_level");
const yearNote = document.getElementById("year_note");

const savedYearLevel = <?= json_encode((string)$form_year) ?>;

function updateYearLevels(selectedYear = ""){

    yearLevel.innerHTML = "";

    yearNote.style.display = "none";
    yearNote.textContent = "";

    if(educationLevel.value === "Senior High"){

        yearLevel.disabled = false;

        yearLevel.innerHTML = `
            <option value="">Select year level</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
        `;

        yearNote.textContent =
            "Senior High applicants can select Grade 11 or Grade 12.";

        yearNote.style.display = "block";

    }else if(educationLevel.value === "College"){

        yearLevel.disabled = false;

        yearLevel.innerHTML = `
            <option value="">Select year level</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
        `;

        yearNote.textContent =
            "College applicants can select 1st Year to 4th Year.";

        yearNote.style.display = "block";

    }else{

        yearLevel.innerHTML = `
            <option value="">Select education level first</option>
        `;

        yearLevel.disabled = true;

    }

    yearLevel.value = selectedYear;
}

if(educationLevel && yearLevel){

    educationLevel.addEventListener("change", function(){

        updateYearLevels("");

    });

    updateYearLevels(savedYearLevel);

}

</script>

</body>
</html>
