
<?php

session_start();
include("db.php");

/* =========================================
   LOGOUT
========================================= */

if(isset($_GET['logout'])){

    unset(
        $_SESSION['applicant_id'],
        $_SESSION['applicant_name'],
        $_SESSION['applicant_phone'],
        $_SESSION['applicant_email']
    );

    header("Location: login.php");
    exit();

}

/* =========================================
   REQUIRE APPLICANT LOGIN
========================================= */

if(!isset($_SESSION['applicant_id'])){

    header("Location: login.php");
    exit();

}

$applicant_id = (int)$_SESSION['applicant_id'];

/* =========================================
   GET APPLICANT
========================================= */

$applicant_query = mysqli_query($conn,"
    SELECT *
    FROM applicant_accounts
    WHERE id = $applicant_id
    AND is_verified = 1
    LIMIT 1
");

$applicant = $applicant_query
    ? mysqli_fetch_assoc($applicant_query)
    : null;

if(!$applicant){

    unset(
        $_SESSION['applicant_id'],
        $_SESSION['applicant_name'],
        $_SESSION['applicant_phone'],
        $_SESSION['applicant_email']
    );

    header("Location: login.php");
    exit();

}

/* =========================================
   PHONE VERIFICATION STATUS
========================================= */

$phone_verified = (int)($applicant['phone_verified'] ?? 0) === 1;

/* =========================================
   GET ACTIVE APPLICATION AND HISTORY
========================================= */

$application = null;
$history_applications = [];

/* =========================================
   GENERATE APPLICATION NUMBER
========================================= */

function generateApplicationNumber($conn, $batch_id, $batch_title, $application_id){

    $words = preg_split('/[^A-Za-z0-9]+/', trim($batch_title));
    $prefix = '';

    foreach($words as $word){
        if($word !== ''){
            $prefix .= strtoupper(substr($word, 0, 1));
        }
    }

    if($prefix === ''){
        $prefix = 'APP';
    }

    $batch_id = (int)$batch_id;
    $application_id = (int)$application_id;

    $number_query = mysqli_query($conn,"
        SELECT COUNT(*) AS total
        FROM scholarship_applications
        WHERE batch_id = $batch_id
        AND id <= $application_id
    ");

    $number_data = $number_query
        ? mysqli_fetch_assoc($number_query)
        : ['total' => 1];

    $sequence = max(1, (int)($number_data['total'] ?? 1));

    return $prefix . '-' . str_pad($sequence, 2, '0', STR_PAD_LEFT);
}

$application_query = mysqli_query($conn,"
    SELECT
        sa.*,
        b.name AS barangay_name,
        sb.title AS batch_title,
        sb.status AS batch_status
    FROM scholarship_applications sa

    LEFT JOIN barangays b
        ON b.id = sa.barangay_id

    LEFT JOIN scholar_batches sb
        ON sb.id = sa.batch_id

    WHERE sa.applicant_id = $applicant_id

    ORDER BY sa.id DESC
");

if($application_query){

    while($row = mysqli_fetch_assoc($application_query)){

        $row['display_application_number'] = (
            !empty($row['batch_id']) && !empty($row['batch_title'])
        )
            ? generateApplicationNumber(
                $conn,
                $row['batch_id'],
                $row['batch_title'],
                $row['id']
            )
            : 'N/A';

        $batch_status = strtolower(
            trim($row['batch_status'] ?? '')
        );

        if($batch_status === 'completed'){

            $history_applications[] = $row;

        }elseif($application === null){

            $application = $row;

        }

    }

}

/* =========================================
   APPLICATION STATUS
========================================= */

$status = $application
    ? strtolower(trim($application['status']))
    : 'none';

/* =========================================
   PROGRESS
========================================= */

$steps = [];

if(!$application){

    $steps = [
        [
            'title' => 'Application Submitted',
            'description' => 'Submit your scholarship application to begin the process.',
            'status' => 'waiting'
        ],
        [
            'title' => 'Under Review',
            'description' => 'Your application will be reviewed by the scholarship committee.',
            'status' => 'waiting'
        ],
        [
            'title' => 'Approved',
            'description' => 'Approval will be shown here once your application is accepted.',
            'status' => 'waiting'
        ],
        [
            'title' => 'Scholarship Disbursement',
            'description' => 'Scholarship assistance will be released after approval.',
            'status' => 'waiting'
        ],
        [
            'title' => 'Scholarship Claimed',
            'description' => 'Your scholarship has been successfully claimed.',
            'status' => 'waiting'
        ]
    ];

}else{

    if($status === 'pending'){

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your scholarship application has been successfully submitted.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application is currently being reviewed.',
                'status' => 'current'
            ],
            [
                'title' => 'Approved',
                'description' => 'Waiting for the scholarship committee decision.',
                'status' => 'waiting'
            ],
            [
                'title' => 'Scholarship Disbursement',
                'description' => 'Scholarship assistance will be released after approval.',
                'status' => 'waiting'
            ],
            [
                'title' => 'Scholarship Claimed',
                'description' => 'Your scholarship has been successfully claimed.',
                'status' => 'waiting'
            ]
        ];

    }elseif($status === 'approved'){

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your scholarship application has been successfully submitted.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application has completed the review stage.',
                'status' => 'completed'
            ],
            [
                'title' => 'Approved',
                'description' => 'Congratulations! Your scholarship application has been approved.',
                'status' => 'current'
            ],
            [
                'title' => 'Scholarship Disbursement',
                'description' => 'Waiting for the scholarship assistance to be released.',
                'status' => 'waiting'
            ],
            [
                'title' => 'Scholarship Claimed',
                'description' => 'Your scholarship has not yet been claimed.',
                'status' => 'waiting'
            ]
        ];

    }elseif($status === 'released'){

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your scholarship application has been successfully submitted.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application has completed the review stage.',
                'status' => 'completed'
            ],
            [
                'title' => 'Approved',
                'description' => 'Your scholarship application was approved.',
                'status' => 'completed'
            ],
            [
                'title' => 'Scholarship Disbursement',
                'description' => 'Your scholarship assistance has been released.',
                'status' => 'current'
            ],
            [
                'title' => 'Scholarship Claimed',
                'description' => 'Your scholarship has not yet been claimed.',
                'status' => 'waiting'
            ]
        ];

    }elseif($status === 'claimed'){

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your scholarship application has been successfully submitted.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application has completed the review stage.',
                'status' => 'completed'
            ],
            [
                'title' => 'Approved',
                'description' => 'Your scholarship application was approved.',
                'status' => 'completed'
            ],
            [
                'title' => 'Scholarship Disbursement',
                'description' => 'Your scholarship assistance has been released.',
                'status' => 'completed'
            ],
            [
                'title' => 'Scholarship Claimed',
                'description' => 'Your scholarship has been successfully claimed.',
                'status' => 'current'
            ]
        ];

    }elseif($status === 'rejected'){

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your application was submitted successfully.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application was reviewed by the scholarship committee.',
                'status' => 'completed'
            ],
            [
                'title' => 'Application Rejected',
                'description' => 'Your scholarship application was not approved.',
                'status' => 'rejected'
            ]
        ];

    }else{

        $steps = [
            [
                'title' => 'Application Submitted',
                'description' => 'Your scholarship application has been submitted.',
                'status' => 'completed'
            ],
            [
                'title' => 'Under Review',
                'description' => 'Your application is being reviewed.',
                'status' => 'current'
            ],
            [
                'title' => 'Approved',
                'description' => 'Waiting for the scholarship committee decision.',
                'status' => 'waiting'
            ],
            [
                'title' => 'Scholarship Disbursement',
                'description' => 'Scholarship assistance will be released after approval.',
                'status' => 'waiting'
            ],
            [
                'title' => 'Scholarship Claimed',
                'description' => 'Your scholarship has been successfully claimed.',
                'status' => 'waiting'
            ]
        ];

    }

}

/* =========================================
   STATUS LABEL
========================================= */

if(!$application){

    $status_label = "No Application";

}elseif($status === 'pending'){

    $status_label = "Under Review";

}elseif($status === 'approved'){

    $status_label = "Approved";

}elseif($status === 'released'){

    $status_label = "Scholarship Disbursement";

}elseif($status === 'claimed'){

    $status_label = "Scholarship Claimed";

}elseif($status === 'rejected'){

    $status_label = "Rejected";

}else{

    $status_label = ucfirst($status);

}

/* =========================================
   ASTRONAUT SCHOLARSHIP NOTIFICATION
========================================= */

$astronaut_notification = null;

/* Check current application status */

if($application){

    if($status === 'approved'){

        $astronaut_notification = [
            'id' => $application['id'],
            'status' => 'approved',
            'title' => 'Application Approved!',
            'message' => 'Great news! Your scholarship application has been approved. Congratulations!'
        ];

    }elseif($status === 'released'){

        $astronaut_notification = [
            'id' => $application['id'],
            'status' => 'released',
            'title' => 'Scholarship Released!',
            'message' => 'Your scholarship assistance has been released. Please check the details of your disbursement.'
        ];

    }elseif($status === 'claimed'){

        $astronaut_notification = [
            'id' => $application['id'],
            'status' => 'claimed',
            'title' => 'Scholarship Claimed!',
            'message' => 'Congratulations! You have successfully claimed your scholarship. Your application process is complete.'
        ];

    }

}

/* Check completed scholarship batches in history */

if(!empty($history_applications)){

    foreach($history_applications as $history){

        if(
            !$astronaut_notification &&
            strtolower(trim($history['status'] ?? '')) === 'claimed'
        ){

            $astronaut_notification = [
                'id' => $history['id'],
                'status' => 'claimed',
                'title' => 'Scholarship Completed!',
                'message' => 'Congratulations! Your scholarship program has been completed successfully.'
            ];

            break;

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Applicant Dashboard | SK KONEXT</title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link
href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Manrope:wght@500;600;700;800&display=swap"
rel="stylesheet">

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
        radial-gradient(
            circle at 15% 15%,
            rgba(74,222,128,.13),
            transparent 28%
        ),
        radial-gradient(
            circle at 85% 30%,
            rgba(22,163,74,.10),
            transparent 25%
        ),
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
        linear-gradient(
            rgba(255,255,255,.035) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.035) 1px,
            transparent 1px
        );
    background-size:45px 45px;
}

a{
    text-decoration:none;
    color:inherit;
}

/* =========================================
   NAVBAR
========================================= */

.navbar{
    position:relative;
    z-index:20;
    width:min(1180px,calc(100% - 40px));
    min-height:70px;
    margin:22px auto 0;
    padding:10px 18px 10px 22px;
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

.nav-links a,
.nav-links span.nav-scholarship{
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

.nav-scholarship{
    background:var(--green) !important;
    color:#06100b !important;
}

.nav-scholarship:hover{
    background:#86efac !important;
}

.nav-scholarship.disabled{
    background:rgba(255,255,255,.07) !important;
    color:rgba(255,255,255,.35) !important;
    border:1px solid rgba(255,255,255,.08);
    cursor:not-allowed;
    opacity:.65;
}

.apply-button.disabled{
    background:rgba(255,255,255,.10);
    color:rgba(255,255,255,.45);
    cursor:not-allowed;
    pointer-events:none;
    border:1px solid rgba(255,255,255,.10);
}

.phone-verification-notice{
    margin-bottom:20px;
    padding:18px 20px;
    border:1px solid rgba(250,204,21,.25);
    border-radius:16px;
    background:rgba(250,204,21,.07);
    color:#fde68a;
    display:flex;
    align-items:flex-start;
    gap:13px;
}

.phone-verification-notice i{
    font-size:20px;
    margin-top:2px;
}

.phone-verification-notice strong{
    display:block;
    margin-bottom:5px;
    font-size:14px;
}

.phone-verification-notice p{
    color:rgba(255,255,255,.65);
    font-size:12px;
    line-height:1.6;
}

.phone-verification-notice a{
    display:inline-block;
    margin-top:8px;
    color:#86efac;
    font-size:12px;
    font-weight:800;
    text-decoration:underline;
}

.nav-settings{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.10);
    color:#fff !important;
}

.nav-settings:hover{
    background:rgba(74,222,128,.12) !important;
    border-color:rgba(74,222,128,.25) !important;
    color:var(--green-light) !important;
}

.nav-map{
    background:rgba(74,222,128,.10) !important;
    border:1px solid rgba(74,222,128,.18);
    color:var(--green-light) !important;
}

.nav-map:hover{
    background:rgba(74,222,128,.18) !important;
}

.nav-account{
    display:flex;
    align-items:center;
    gap:8px;
    padding:9px 13px;
    border-radius:11px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);
    font-size:12px;
    color:#fff;
}

.nav-account i{
    color:var(--green);
}

.logout-button{
    display:flex;
    align-items:center;
    justify-content:center;
    width:38px;
    height:38px;
    border-radius:11px;
    background:rgba(239,68,68,.10);
    border:1px solid rgba(239,68,68,.20);
    color:#fca5a5;
    transition:.2s ease;
}

.logout-button:hover{
    background:#ef4444;
    border-color:#ef4444;
    color:#fff;
    transform:translateY(-1px);
}

/* =========================================
   HERO
========================================= */

.hero{
    position:relative;
    z-index:1;
    max-width:1180px;
    margin:0 auto;
    padding:85px 20px 55px;
}

.hero-content{
    max-width:760px;
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
    margin:22px 0 14px;
    font-family:"Manrope",sans-serif;
    font-size:clamp(42px,7vw,72px);
    line-height:.98;
    letter-spacing:-3px;
    font-weight:900;
}

.hero h1 span{
    color:var(--green);
}

.hero p{
    max-width:690px;
    color:var(--muted);
    font-size:16px;
    line-height:1.75;
}

/* =========================================
   MAIN
========================================= */

.main-container{
    position:relative;
    z-index:2;
    width:min(1000px,calc(100% - 40px));
    margin:10px auto 80px;
}

/* =========================================
   CARDS
========================================= */

.card{
    background:rgba(255,255,255,.055);
    border:1px solid var(--border);
    border-radius:24px;
    box-shadow:0 25px 80px rgba(0,0,0,.22);
    backdrop-filter:blur(16px);
}

/* =========================================
   WELCOME CARD
========================================= */

.welcome-card{
    padding:28px 30px;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
}

.welcome-left{
    display:flex;
    align-items:center;
    gap:16px;
}

.welcome-icon{
    width:58px;
    height:58px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:17px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.20);
    color:var(--green);
    font-size:22px;
}

.welcome-text h2{
    font-family:"Manrope",sans-serif;
    font-size:21px;
    font-weight:800;
    margin-bottom:4px;
}

.welcome-text p{
    color:var(--muted);
    font-size:13px;
}

.status-badge{
    padding:9px 14px;
    border-radius:999px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.22);
    color:var(--green-light);
    font-size:11px;
    font-weight:800;
}

/* =========================================
   APPLICATION CARD
========================================= */

.application-card{
    padding:32px;
}

.application-heading{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    margin-bottom:30px;
}

.application-heading h2{
    font-family:"Manrope",sans-serif;
    font-size:24px;
    font-weight:800;
    margin-bottom:5px;
}

.application-heading h2 i{
    color:var(--green);
}

.application-heading p{
    color:var(--muted);
    font-size:13px;
}

.application-status{
    white-space:nowrap;
    padding:9px 14px;
    border-radius:999px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.22);
    color:var(--green-light);
    font-size:11px;
    font-weight:800;
}

/* =========================================
   APPLICATION INFO
========================================= */

.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
    margin-bottom:35px;
}

.info-box{
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
    color:rgba(255,255,255,.45);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.6px;
}

.info-box strong{
    display:block;
    margin-top:5px;
    color:#fff;
    font-size:14px;
    word-break:break-word;
}

/* =========================================
   VERTICAL PROGRESS
========================================= */

.progress-title{
    font-family:"Manrope",sans-serif;
    font-size:18px;
    font-weight:800;
    margin-bottom:25px;
}

.progress-title i{
    color:var(--green);
    margin-right:7px;
}

.vertical-progress{
    position:relative;
    padding-left:34px;
}

.progress-step{
    position:relative;
    min-height:92px;
    padding-bottom:25px;
}

.progress-step:last-child{
    min-height:auto;
    padding-bottom:0;
}

.progress-step::before{
    content:"";
    position:absolute;
    left:-25px;
    top:28px;
    width:2px;
    height:calc(100% - 3px);
    background:rgba(255,255,255,.12);
}

.progress-step:last-child::before{
    display:none;
}

.progress-step.completed::before{
    background:var(--green);
}

.step-dot{
    position:absolute;
    left:-34px;
    top:0;
    width:20px;
    height:20px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#17251c;
    border:2px solid rgba(255,255,255,.20);
    font-size:9px;
    z-index:2;
}

.progress-step.completed .step-dot{
    background:var(--green);
    border-color:var(--green);
    color:#06100b;
}

.progress-step.current .step-dot{
    background:var(--green);
    border-color:var(--green);
    color:#06100b;
    box-shadow:
        0 0 0 5px rgba(74,222,128,.10),
        0 0 20px rgba(74,222,128,.30);
}

.progress-step.rejected .step-dot{
    background:#ef4444;
    border-color:#ef4444;
    color:white;
    box-shadow:0 0 0 5px rgba(239,68,68,.10);
}

.step-content h3{
    font-family:"Manrope",sans-serif;
    font-size:15px;
    font-weight:800;
    margin-bottom:5px;
}

.step-content p{
    color:rgba(255,255,255,.48);
    font-size:12px;
    line-height:1.6;
    max-width:600px;
}

.progress-step.completed .step-content h3{
    color:#fff;
}

.progress-step.current .step-content h3{
    color:var(--green-light);
}

.progress-step.rejected .step-content h3{
    color:#fca5a5;
}

.progress-step.waiting .step-content{
    opacity:.48;
}

/* =========================================
   NO APPLICATION
========================================= */

.no-application{
    text-align:center;
    padding:55px 25px;
}

.no-application-icon{
    width:75px;
    height:75px;
    margin:0 auto 20px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:rgba(74,222,128,.08);
    border:1px solid rgba(74,222,128,.18);
    color:var(--green);
    font-size:28px;
}

.no-application h2{
    font-family:"Manrope",sans-serif;
    font-size:24px;
    font-weight:800;
    margin-bottom:10px;
}

.no-application p{
    max-width:560px;
    margin:auto;
    color:var(--muted);
    font-size:14px;
    line-height:1.7;
}

.apply-button{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-top:25px;
    padding:12px 20px;
    border-radius:12px;
    background:var(--green);
    color:#06100b;
    font-size:13px;
    font-weight:800;
    transition:.2s ease;
}

.apply-button:hover{
    color:#06100b;
    background:#86efac;
    transform:translateY(-1px);
}

.no-application .settings-reminder{
    margin-top:14px;
    font-size:12px;
    color:var(--muted);
}

.no-application .settings-reminder a{
    color:var(--green-light);
    font-weight:800;
    text-decoration:underline;
}

/* =========================================
   FLOATING SCHOLARSHIP HISTORY
========================================= */

.history-floating{
    position:fixed;
    right:25px;
    bottom:25px;
    z-index:999;
}

.history-toggle{
    display:flex;
    align-items:center;
    gap:10px;
    padding:14px 18px;
    border:none;
    border-radius:50px;
    background:var(--green);
    color:#06100b;
    font-family:"DM Sans",sans-serif;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 10px 35px rgba(0,0,0,.35);
    transition:.2s ease;
}

.history-toggle:hover{
    background:#86efac;
    transform:translateY(-3px);
}

.history-count{
    display:flex;
    align-items:center;
    justify-content:center;
    min-width:21px;
    height:21px;
    padding:0 5px;
    border-radius:50px;
    background:#06100b;
    color:#86efac;
    font-size:10px;
}

.history-panel{
    display:none;
    position:absolute;
    right:0;
    bottom:65px;
    width:min(360px,calc(100vw - 30px));
    max-height:450px;
    overflow-y:auto;
    padding:20px;
    border:1px solid var(--border);
    border-radius:20px;
    background:#0b1b12;
    box-shadow:0 20px 60px rgba(0,0,0,.5);
}

.history-panel.show{
    display:block;
    animation:historyFade .2s ease;
}

@keyframes historyFade{
    from{
        opacity:0;
        transform:translateY(10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.history-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:18px;
}

.history-header h3{
    font-family:"Manrope",sans-serif;
    font-size:17px;
    font-weight:800;
}

.history-header i{
    color:var(--green);
    margin-right:7px;
}

.history-close{
    width:30px;
    height:30px;
    border:1px solid var(--border);
    border-radius:9px;
    background:rgba(255,255,255,.06);
    color:white;
    cursor:pointer;
}

.history-item{
    padding:15px;
    margin-bottom:10px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:14px;
    background:rgba(255,255,255,.045);
}

.history-item:last-child{
    margin-bottom:0;
}

.history-item h4{
    font-size:13px;
    font-weight:800;
    margin-bottom:7px;
}

.history-item p{
    color:var(--muted);
    font-size:11px;
    line-height:1.7;
}

.history-completed{
    display:inline-block;
    margin-top:9px;
    padding:5px 9px;
    border-radius:20px;
    background:rgba(74,222,128,.10);
    border:1px solid rgba(74,222,128,.20);
    color:var(--green-light);
    font-size:10px;
    font-weight:800;
}

.history-empty{
    padding:20px 10px;
    text-align:center;
    color:var(--muted);
    font-size:12px;
    line-height:1.7;
}

/* =========================================
   2D INTERACTIVE ASTRONAUT NOTIFICATION
========================================= */

.astronaut-notification{
    position:fixed;
    right:20px;
    bottom:100px;
    z-index:900;
    display:flex;
    align-items:center;
    gap:4px;
    cursor:pointer;
    transform-origin:bottom right;
    animation:buddyFloat 4s ease-in-out infinite;
    transition:opacity .3s ease, transform .3s ease;
    user-select:none;
    -webkit-tap-highlight-color:transparent;
}

.astronaut-notification:hover{
    filter:drop-shadow(0 0 16px rgba(74,222,128,.35));
}

.astronaut-notification.hidden{
    display:none;
}

.astronaut-notification .astronaut-figure{
    width:115px;
    flex-shrink:0;
    transform-origin:bottom center;
    transition:transform .3s ease;
}

.astronaut-notification .astronaut-figure svg{
    display:block;
    width:100%;
    height:auto;
    overflow:visible;
}

.astronaut-notification:hover .astronaut-figure{
    transform:rotate(-5deg) translateY(-5px);
}

.astronaut-notification .astronaut-sign{
    width:220px;
    padding:16px;
    background:rgba(8,27,17,.96);
    border:1px solid rgba(74,222,128,.55);
    border-radius:15px 15px 15px 4px;
    box-shadow:0 12px 35px rgba(0,0,0,.45);
    backdrop-filter:blur(12px);
    text-align:left;
    position:relative;
}

.astronaut-notification .sign-label{
    display:block;
    color:#86efac;
    font-size:9px;
    font-weight:800;
    letter-spacing:1.3px;
    margin-bottom:8px;
}

.astronaut-notification .notification-title{
    color:#4ade80;
    font-family:"Manrope",sans-serif;
    font-size:15px;
    font-weight:800;
    margin-bottom:7px;
}

.astronaut-notification .notification-message{
    color:white;
    font-family:"DM Sans",sans-serif;
    font-size:12px;
    line-height:1.6;
    overflow-wrap:anywhere;
}

.astronaut-notification .sign-hint{
    display:block;
    margin-top:10px;
    color:rgba(255,255,255,.48);
    font-size:8px;
    letter-spacing:.8px;
}

.astronaut-notification .notification-close{
    position:absolute;
    top:8px;
    right:9px;
    width:23px;
    height:23px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid rgba(255,255,255,.12);
    border-radius:7px;
    background:rgba(255,255,255,.07);
    color:white;
    font-size:11px;
    cursor:pointer;
}

.astronaut-notification .notification-close:hover{
    background:#ef4444;
}

@keyframes buddyFloat{
    0%,100%{
        transform:translateY(0) rotate(0);
    }
    50%{
        transform:translateY(-9px) rotate(1deg);
    }
}

.astronaut-notification.message-change .astronaut-figure{
    animation:astronautWave .5s ease;
}

@keyframes astronautWave{
    0%,100%{transform:rotate(0)}
    30%{transform:rotate(-9deg) translateY(-5px)}
    65%{transform:rotate(6deg)}
}

/* =========================================
   HAND BUTTON WHEN NOTIFICATION IS CLOSED
========================================= */

.astronaut-hand-button{
    display:none;
    position:fixed;
    right:22px;
    bottom:100px;
    z-index:901;
    width:52px;
    height:52px;
    align-items:center;
    justify-content:center;
    border:1px solid rgba(74,222,128,.45);
    border-radius:50%;
    background:#0b1b12;
    color:#86efac;
    font-size:23px;
    cursor:pointer;
    box-shadow:0 8px 25px rgba(0,0,0,.4);
    animation:handPulse 2s infinite;
}

.astronaut-hand-button.show{
    display:flex;
}

.astronaut-hand-button:hover{
    background:#4ade80;
    color:#06100b;
    transform:translateY(-3px);
}

@keyframes handPulse{
    0%,100%{
        box-shadow:0 0 0 0 rgba(74,222,128,.25);
    }
    50%{
        box-shadow:0 0 0 9px rgba(74,222,128,0);
    }
}

/* =========================================
   FOOTER
========================================= */

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

/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:800px){

    .info-grid{
        grid-template-columns:1fr 1fr;
    }

    .welcome-card{
        align-items:flex-start;
        flex-direction:column;
    }

}

@media(max-width:600px){

    .navbar{
        width:calc(100% - 24px);
        margin-top:12px;
        padding:10px 12px;
    }

    .nav-account{
        font-size:11px;
    }

    .hero{
        padding:70px 15px 45px;
    }

    .hero h1{
        letter-spacing:-2px;
    }

    .main-container{
        width:calc(100% - 24px);
    }

    .application-card{
        padding:22px;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .application-heading{
        flex-direction:column;
    }

    .application-status{
        align-self:flex-start;
    }

    .history-floating{
        right:15px;
        bottom:15px;
    }

    .astronaut-notification{
        right:8px;
        bottom:75px;
        gap:0;
    }

    .astronaut-notification .astronaut-figure{
        width:85px;
    }

    .astronaut-notification .astronaut-sign{
        width:175px;
        padding:12px;
    }

    .astronaut-notification .notification-title{
        font-size:13px;
    }

    .astronaut-notification .notification-message{
        font-size:11px;
    }

    .astronaut-hand-button{
        right:15px;
        bottom:80px;
        width:46px;
        height:46px;
        font-size:20px;
    }

}

@media(max-width:450px){

    .brand span{
        font-size:14px;
    }

    .brand img{
        width:36px;
        height:36px;
    }

    .nav-account span{
        display:none;
    }

}

@media(max-width:380px){

    .astronaut-notification .astronaut-figure{
        width:72px;
    }

    .astronaut-notification .astronaut-sign{
        width:155px;
        padding:10px;
    }

}

@media(prefers-reduced-motion:reduce){

    .astronaut-notification,
    .astronaut-hand-button{
        animation:none !important;
    }

}

</style>

</head>

<body>

<!-- =========================================
     NAVBAR
========================================= -->

<nav class="navbar">

    <a href="applicant_dashboard.php" class="brand">

        <img
        src="assets/sk-logo.png.webp"
        alt="SK KONEXT Logo">

        <span>SK KONEXT</span>

    </a>

    <div class="nav-links">

        <?php if($phone_verified){ ?>

            <a href="scholarship.php" class="nav-scholarship">

                <i class="fa-solid fa-graduation-cap"></i>

                Scholarship

            </a>

        <?php }else{ ?>

            <span
                class="nav-scholarship disabled"
                title="Verify your mobile number first"
                aria-disabled="true">

                <i class="fa-solid fa-lock"></i>

                Scholarship

            </span>

        <?php } ?>

        <a href="applicant_settings.php" class="nav-settings">

            <i class="fa-solid fa-gear"></i>

            Settings

        </a>

    </div>

    <div style="display:flex;align-items:center;gap:8px;">

        <div class="nav-account">

            <i class="fa-solid fa-circle-user"></i>

            <span>
                <?= htmlspecialchars($applicant['fullname']) ?>
            </span>

        </div>

        <a
            href="applicant_dashboard.php?logout=1"
            class="logout-button"
            title="Logout">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</nav>

<!-- =========================================
     HERO
========================================= -->

<section class="hero">

    <div class="hero-content">

        <div class="live-badge">

            <span class="live-dot"></span>

            APPLICANT DASHBOARD

        </div>

        <h1>
            Welcome Back,<br>
            <span>
                <?= htmlspecialchars($applicant['fullname']) ?>.
            </span>
        </h1>

        <p>
            Track your SK KONEXT scholarship application
            and stay updated on every stage of the process.
        </p>

    </div>

</section>

<!-- =========================================
     MAIN
========================================= -->

<main class="main-container">

    <!-- WELCOME CARD -->

    <div class="card welcome-card">

        <div class="welcome-left">

            <div class="welcome-icon">
                <i class="fa-solid fa-user-graduate"></i>
            </div>

            <div class="welcome-text">

                <h2>Scholarship Applicant</h2>

                <p>
                    Your scholarship application progress is shown below.
                </p>

            </div>

        </div>

        <div class="status-badge">

            <i class="fa-solid fa-shield-check"></i>

            VERIFIED ACCOUNT

        </div>

    </div>

    <!-- PHONE VERIFICATION NOTICE -->

    <?php if(!$phone_verified){ ?>

        <div class="phone-verification-notice">

            <i class="fa-solid fa-mobile-screen-button"></i>

            <div>

                <strong>Mobile Number Verification Required</strong>

                <p>
                    Your mobile number has not been verified yet.
                    You need to complete the verification process before
                    accessing scholarship applications.
                </p>

                <a href="applicant_settings.php">
                    Go to Settings to request number verification
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

        </div>

    <?php } ?>

    <?php if($application){ ?>

    <!-- APPLICATION -->

    <div class="card application-card">

        <div class="application-heading">

            <div>

                <h2>
                    <i class="fa-solid fa-graduation-cap"></i>
                    Scholarship Application
                </h2>

                <p>
                    Track the current status of your scholarship application.
                </p>

            </div>

            <div class="application-status">
                <?= htmlspecialchars($status_label) ?>
            </div>

        </div>

        <!-- APPLICATION INFORMATION -->

        <div class="info-grid">

            <div class="info-box">

                <i class="fa-solid fa-hashtag"></i>

                <small>Application Number</small>

                <strong>
                    <?= htmlspecialchars(
                        $application['display_application_number'] ?? 'N/A'
                    ) ?>
                </strong>

            </div>

            <div class="info-box">

                <i class="fa-solid fa-award"></i>

                <small>Scholarship Program</small>

                <strong>
                    <?= htmlspecialchars(
                        $application['batch_title'] ?? 'N/A'
                    ) ?>
                </strong>

            </div>

            <div class="info-box">

                <i class="fa-solid fa-calendar"></i>

                <small>Submitted</small>

                <strong>
                    <?php
                    if(!empty($application['application_date'])){
                        echo date(
                            "M d, Y",
                            strtotime($application['application_date'])
                        );
                    }else{
                        echo "N/A";
                    }
                    ?>
                </strong>

            </div>

            <div class="info-box">

                <i class="fa-solid fa-school"></i>

                <small>School</small>

                <strong>
                    <?= htmlspecialchars(
                        $application['school'] ?? 'N/A'
                    ) ?>
                </strong>

            </div>

            <div class="info-box">

                <i class="fa-solid fa-book"></i>

                <small>Course / Strand</small>

                <strong>
                    <?= htmlspecialchars(
                        $application['course'] ?? 'N/A'
                    ) ?>
                </strong>

            </div>

            <div class="info-box">

                <i class="fa-solid fa-location-dot"></i>

                <small>Barangay</small>

                <strong>
                    <?= htmlspecialchars(
                        $application['barangay_name'] ?? 'N/A'
                    ) ?>
                </strong>

            </div>

        </div>

        <!-- VERTICAL PROGRESS -->

        <div class="progress-title">

            <i class="fa-solid fa-list-check"></i>

            Scholarship Application Progress

        </div>

        <div class="vertical-progress">

            <?php foreach($steps as $step){ ?>

                <div class="progress-step <?= htmlspecialchars($step['status']) ?>">

                    <div class="step-dot">

                        <?php if($step['status'] === 'completed'){ ?>

                            <i class="fa-solid fa-check"></i>

                        <?php }elseif($step['status'] === 'rejected'){ ?>

                            <i class="fa-solid fa-xmark"></i>

                        <?php }elseif($step['status'] === 'current'){ ?>

                            <i class="fa-solid fa-circle"></i>

                        <?php }else{ ?>

                            <i class="fa-solid fa-circle"></i>

                        <?php } ?>

                    </div>

                    <div class="step-content">

                        <h3>
                            <?= htmlspecialchars($step['title']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($step['description']) ?>
                        </p>

                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

    <?php }else{ ?>

    <!-- NO ACTIVE APPLICATION -->

    <div class="card no-application">

        <div class="no-application-icon">

            <i class="fa-solid fa-file-circle-plus"></i>

        </div>

        <h2>
            No Active Scholarship Application
        </h2>

        <p>
            You currently have no active scholarship application.
            When a new scholarship program is available, you can apply
            and track your application progress here.
        </p>

        <?php if($phone_verified){ ?>

            <a href="scholarship.php" class="apply-button">

                <i class="fa-solid fa-graduation-cap"></i>

                View Scholarship

            </a>

        <?php }else{ ?>

            <span
                class="apply-button disabled"
                aria-disabled="true">

                <i class="fa-solid fa-lock"></i>

                Verify Mobile Number First

            </span>

            <p class="settings-reminder">
                Please complete mobile number verification in
                <a href="applicant_settings.php">Settings</a>.
            </p>

        <?php } ?>

    </div>

    <?php } ?>

</main>

<!-- =========================================
     FLOATING SCHOLARSHIP HISTORY
========================================= -->

<div class="history-floating">

    <div class="history-panel" id="historyPanel">

        <div class="history-header">

            <h3>
                <i class="fa-solid fa-box-archive"></i>
                Scholarship History
            </h3>

            <button
                type="button"
                class="history-close"
                onclick="toggleHistory()">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <?php if(!empty($history_applications)){ ?>

            <?php foreach($history_applications as $history){ ?>

                <div class="history-item">

                    <h4>
                        <?= htmlspecialchars(
                            $history['batch_title'] ?? 'Scholarship Program'
                        ) ?>
                    </h4>

                    <p>
                        Application No.:
                        <?= htmlspecialchars(
                            $history['display_application_number'] ?? 'N/A'
                        ) ?>
                    </p>

                    <p>
                        Application Date:

                        <?php
                        if(!empty($history['application_date'])){
                            echo date(
                                "M d, Y",
                                strtotime($history['application_date'])
                            );
                        }else{
                            echo "N/A";
                        }
                        ?>

                    </p>

                    <p>
                        Application Status:
                        <?= htmlspecialchars(
                            ucfirst($history['status'] ?? 'N/A')
                        ) ?>
                    </p>

                    <span class="history-completed">

                        <i class="fa-solid fa-check-circle"></i>

                        BATCH COMPLETED

                    </span>

                </div>

            <?php } ?>

        <?php }else{ ?>

            <div class="history-empty">

                <i
                    class="fa-solid fa-folder-open"
                    style="font-size:25px;color:var(--green);margin-bottom:12px;">
                </i>

                <p>
                    No completed scholarship programs yet.
                    Your previous scholarship applications will appear
                    here once their batches are completed.
                </p>

            </div>

        <?php } ?>

    </div>

    <button
        type="button"
        class="history-toggle"
        onclick="toggleHistory()">

        <i class="fa-solid fa-box-archive"></i>

        Scholarship History

        <span class="history-count">
            <?= count($history_applications) ?>
        </span>

    </button>

</div>

<!-- =========================================
     2D INTERACTIVE ASTRONAUT NOTIFICATION
========================================= -->

<?php if($astronaut_notification): ?>

<div
    class="astronaut-notification"
    id="astronautNotification"
    role="button"
    tabindex="0"
    aria-label="Dismiss scholarship notification"
>

    <div class="astronaut-sign">

        <button
            type="button"
            class="notification-close"
            id="astronautClose"
            aria-label="Close notification"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <span class="sign-label">
            SK KONEXT SCHOLARSHIP UPDATE
        </span>

        <h3 class="notification-title">
            <?= htmlspecialchars($astronaut_notification['title']) ?>
        </h3>

        <p class="notification-message">
            <?= htmlspecialchars($astronaut_notification['message']) ?>
        </p>

        <span class="sign-hint">
            CLICK TO DISMISS
        </span>

    </div>

    <div class="astronaut-figure">

        <svg viewBox="0 0 180 220"
             role="img"
             aria-label="Cartoon astronaut">

            <!-- BACKPACK -->
            <rect x="30" y="99" width="30" height="66" rx="12"
                  fill="#23834b" stroke="#86efac" stroke-width="3"/>

            <rect x="36" y="111" width="17" height="10" rx="4"
                  fill="#86efac"/>

            <!-- LEGS -->
            <path d="M63 155 L58 184 Q56 196 69 198 L84 198 Q91 196 87 187 L85 155Z"
                  fill="#e8f5ed" stroke="#8cae9a" stroke-width="3"/>

            <path d="M96 155 L98 184 Q98 196 111 198 L126 198 Q137 195 128 184 L119 155Z"
                  fill="#e8f5ed" stroke="#8cae9a" stroke-width="3"/>

            <path d="M61 185 L85 185 M100 185 L130 185"
                  stroke="#4ade80" stroke-width="5"
                  stroke-linecap="round"/>

            <!-- BODY -->
            <path d="M55 101 Q58 87 75 86 L105 86 Q122 88 126 105 L121 158 Q91 171 60 158Z"
                  fill="#f4fff7" stroke="#9ab9a5" stroke-width="3"/>

            <!-- CHEST PANEL -->
            <rect x="76" y="111" width="30" height="25" rx="6"
                  fill="#153d27" stroke="#4ade80" stroke-width="2"/>

            <circle cx="85" cy="120" r="3" fill="#4ade80"/>
            <circle cx="96" cy="120" r="3" fill="#86efac"/>

            <path d="M83 128 H99"
                  stroke="#86efac" stroke-width="2"
                  stroke-linecap="round"/>

            <!-- LEFT ARM -->
            <path d="M60 105 Q42 111 33 91 L23 76"
                  fill="none" stroke="#e8f5ed"
                  stroke-width="17" stroke-linecap="round"/>

            <path d="M60 105 Q42 111 33 91 L23 76"
                  fill="none" stroke="#9ab9a5"
                  stroke-width="2.5" stroke-linecap="round"/>

            <circle cx="22" cy="74" r="10"
                    fill="#f4fff7" stroke="#9ab9a5" stroke-width="3"/>

            <!-- RIGHT ARM -->
            <path d="M121 106 Q143 113 144 135"
                  fill="none" stroke="#e8f5ed"
                  stroke-width="17" stroke-linecap="round"/>

            <path d="M121 106 Q143 113 144 135"
                  fill="none" stroke="#9ab9a5"
                  stroke-width="2.5" stroke-linecap="round"/>

            <circle cx="145" cy="137" r="10"
                    fill="#f4fff7" stroke="#9ab9a5" stroke-width="3"/>

            <!-- HELMET -->
            <circle cx="90" cy="62" r="48"
                    fill="#f4fff7" stroke="#9ab9a5" stroke-width="4"/>

            <circle cx="90" cy="62" r="37"
                    fill="#163b2a" stroke="#4ade80" stroke-width="3"/>

            <path d="M65 47 Q80 32 98 39"
                  fill="none" stroke="#a7f3d0"
                  stroke-width="5" stroke-linecap="round" opacity=".8"/>

            <circle cx="109" cy="76" r="4"
                    fill="#4ade80" opacity=".8"/>

            <!-- HELMET SIDE DETAILS -->
            <rect x="38" y="54" width="9" height="19" rx="4"
                  fill="#4ade80"/>

            <rect x="133" y="54" width="9" height="19" rx="4"
                  fill="#4ade80"/>

        </svg>

    </div>

</div>

<!-- HAND BUTTON -->

<button
    type="button"
    class="astronaut-hand-button"
    id="astronautHandButton"
    title="Show scholarship notification"
    aria-label="Show scholarship notification"
>
    <i class="fa-solid fa-hand"></i>
</button>

<?php endif; ?>

<!-- =========================================
     FOOTER
========================================= -->

<footer class="footer">

    © <?= date('Y') ?> SK KONEXT.
    Applicant Portal.

</footer>

<script>

function toggleHistory(){

    const panel = document.getElementById('historyPanel');

    panel.classList.toggle('show');

}

/* =========================================
   ASTRONAUT SCHOLARSHIP NOTIFICATION
========================================= */

<?php if($astronaut_notification): ?>

const astronautNotification = document.getElementById('astronautNotification');
const astronautHandButton = document.getElementById('astronautHandButton');
const astronautClose = document.getElementById('astronautClose');

const notificationKey = 'skkonext_notification_<?= (int)$applicant_id ?>_<?= (int)$astronaut_notification['id'] ?>_<?= htmlspecialchars($astronaut_notification['status'], ENT_QUOTES) ?>';

function closeAstronautNotification(){

    if(!astronautNotification || !astronautHandButton) return;

    astronautNotification.classList.add('hidden');
    astronautHandButton.classList.add('show');

    try{

        localStorage.setItem(notificationKey, 'dismissed');

    }catch(error){

        console.log('Notification preference could not be saved.');

    }

}

function showAstronautNotification(){

    if(!astronautNotification || !astronautHandButton) return;

    astronautNotification.classList.remove('hidden');
    astronautHandButton.classList.remove('show');

}

if(astronautNotification && astronautHandButton){

    let notificationDismissed = false;

    try{

        notificationDismissed =
            localStorage.getItem(notificationKey) === 'dismissed';

    }catch(error){

        notificationDismissed = false;

    }

    if(notificationDismissed){

        astronautNotification.classList.add('hidden');
        astronautHandButton.classList.add('show');

    }else{

        astronautNotification.classList.remove('hidden');
        astronautHandButton.classList.remove('show');

    }

    /* Click astronaut to dismiss */

    astronautNotification.addEventListener('click', function(){

        closeAstronautNotification();

    });

    /* Close button */

    astronautClose.addEventListener('click', function(event){

        event.stopPropagation();

        closeAstronautNotification();

    });

    /* Hand button to reopen */

    astronautHandButton.addEventListener('click', function(){

        showAstronautNotification();

    });

    /* Keyboard support */

    astronautNotification.addEventListener('keydown', function(event){

        if(event.key === 'Enter' || event.key === ' '){

            event.preventDefault();

            closeAstronautNotification();

        }

    });

}

<?php endif; ?>

</script>

</body>
</html>