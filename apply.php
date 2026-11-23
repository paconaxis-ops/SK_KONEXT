<?php

session_start();

include("db.php");


/* =========================================
   REQUIRE VERIFIED APPLICANT ACCOUNT
========================================= */

if(!isset($_SESSION['applicant_id'])){

    header("Location: login.php?apply=1");
    exit();

}

$applicant_id = (int)$_SESSION['applicant_id'];


/* =========================================
   GET APPLICANT ACCOUNT
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

    header("Location: login.php?apply=1");
    exit();

}


/* =========================================
   CHECK ACTIVE SCHOLARSHIP PROGRAM
========================================= */

$program_query = mysqli_query($conn,"
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

$active_program = $program_query
    ? mysqli_fetch_assoc($program_query)
    : null;

$scholarship_open = ($active_program !== null);


$success = false;
$error = "";

$queue_number = "";


/* =========================================
   SUBMIT APPLICATION
========================================= */

if(isset($_POST['submit_application'])){


    /* =========================================
       RE-CHECK PROGRAM BEFORE SUBMISSION
       Prevents bypassing closed applications.
    ========================================= */

    $program_query = mysqli_query($conn,"
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

    $active_program = $program_query
        ? mysqli_fetch_assoc($program_query)
        : null;


    if(!$active_program){

        $error = "Scholarship applications are currently closed.";

    }else{


        /* =========================================
           GET CURRENT BATCH
        ========================================= */

        $batch_id = (int)$active_program['id'];


        /* =========================================
           CHECK AVAILABLE SLOTS
        ========================================= */

        $slots = (int)$active_program['slots'];

        $approved_query = mysqli_query($conn,"
            SELECT COUNT(*) AS total
            FROM scholars
            WHERE batch_id = $batch_id
            AND LOWER(TRIM(status)) = 'Approved'
        ");

        $approved_count = 0;

        if($approved_query){

            $approved_data = mysqli_fetch_assoc($approved_query);

            $approved_count = (int)$approved_data['total'];

        }


        if($slots > 0 && $approved_count >= $slots){

            $error = "Sorry, all scholarship slots for this program have already been filled.";

        }else{


            /* =========================================
               GET APPLICANT ACCOUNT INFORMATION
            ========================================= */

            $fullname = trim($applicant['fullname']);

            $contact_number = trim($applicant['phone']);

            $email = trim($applicant['email'] ?? '');


            /* =========================================
               GET FORM DATA
            ========================================= */

            $school = trim($_POST['school'] ?? '');

            $course = trim($_POST['course'] ?? '');

            $education_level = trim($_POST['education_level'] ?? '');

            $year_level = trim($_POST['year_level'] ?? '');

            $barangay_id = (int)($_POST['barangay_id'] ?? 0);


            /* =========================================
               BASIC VALIDATION
            ========================================= */

            if(
                $fullname == '' ||
                $contact_number == '' ||
                $email == '' ||
                $school == '' ||
                $course == '' ||
                $education_level == '' ||
                $year_level == '' ||
                $barangay_id <= 0
            ){

                $error = "Please complete all required fields.";

            }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){

                $error = "Please enter a valid email address.";

            }else{


                /* =========================================
                   VERIFY BARANGAY EXISTS
                ========================================= */

                $barangay_check = mysqli_query($conn,"
                    SELECT id
                    FROM barangays
                    WHERE id = $barangay_id
                    LIMIT 1
                ");


                if(!$barangay_check || mysqli_num_rows($barangay_check) == 0){

                    $error = "Please select a valid barangay.";

                }else{


                    /* =========================================
                       CHECK DUPLICATE APPLICATION
                       SAME APPLICANT / SAME BATCH
                    ========================================= */

                    $contact_safe =
                        mysqli_real_escape_string(
                            $conn,
                            $contact_number
                        );


                    $duplicate_query = mysqli_query($conn,"
                        SELECT id, status, queue_number
                        FROM scholarship_applications
                        WHERE contact_number = '$contact_safe'
                        AND batch_id = $batch_id
                        LIMIT 1
                    ");


                    if(
                        $duplicate_query &&
                        mysqli_num_rows($duplicate_query) > 0
                    ){

                        $duplicate =
                            mysqli_fetch_assoc(
                                $duplicate_query
                            );


                        $error =
                            "You have already submitted an application for this scholarship program. "
                            . "Application Number: "
                            . htmlspecialchars(
                                $duplicate['queue_number']
                            );

                    }else{


                        /* =========================================
                           ESCAPE DATA
                        ========================================= */

                        $fullname_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $fullname
                            );

                        $email_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $email
                            );

                        $school_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $school
                            );

                        $course_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $course
                            );

                        $education_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $education_level
                            );

                        $year_safe =
                            mysqli_real_escape_string(
                                $conn,
                                $year_level
                            );


                        /* =========================================
                           GENERATE QUEUE NUMBER
                        ========================================= */

                        $getLast = mysqli_fetch_assoc(
                            mysqli_query($conn,"
                                SELECT id
                                FROM scholarship_applications
                                ORDER BY id DESC
                                LIMIT 1
                            ")
                        );


                        $nextNumber =
                            ($getLast
                                ? $getLast['id'] + 1
                                : 1
                            );


                        $queue_number =
                            date('Y') . '-' .
                            str_pad(
                                $nextNumber,
                                4,
                                '0',
                                STR_PAD_LEFT
                            );


                        /* =========================================
                           INSERT APPLICATION
                        ========================================= */

                        $insert = mysqli_query($conn,"
                            INSERT INTO scholarship_applications
                            (
                                fullname,
                                email,
                                contact_number,
                                school,
                                course,
                                education_level,
                                year_level,
                                barangay_id,
                                queue_number,
                                status,
                                batch_id,
                                application_date
                            )
                            VALUES
                            (
                                '$fullname_safe',
                                '$email_safe',
                                '$contact_safe',
                                '$school_safe',
                                '$course_safe',
                                '$education_safe',
                                '$year_safe',
                                '$barangay_id',
                                '$queue_number',
                                'Pending',
                                '$batch_id',
                                NOW()
                            )
                        ");


                        if($insert){

                            $success = true;

                        }else{

                            $error =
                                "Unable to submit your application. Please try again.";

                        }

                    }

                }

            }

        }

    }

}


/* =========================================
   GET BARANGAYS
========================================= */

$barangays = mysqli_query($conn,"
    SELECT *
    FROM barangays
    ORDER BY name ASC
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Scholarship Application | SK KONEXT
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
rel="stylesheet">


<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
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

background:
linear-gradient(
rgba(8,17,32,.85),
rgba(8,17,32,.92)
),
url('https://www.rappler.com/tachyon/r3-assets/612F469A6EA84F6BAE882D2B94A4B421/img/17D39E6963C54F1791BEDB722E89E798/sk-new-logo-20160722.jpg?resize=640%2C360&zoom=1');

background-size:cover;
background-position:center;
background-attachment:fixed;

color:white;

padding:40px 20px;

}


.container-box{

max-width:850px;

margin:auto;

background:rgba(255,255,255,.08);

border:1px solid rgba(255,255,255,.15);

backdrop-filter:blur(18px);

border-radius:30px;

padding:45px;

box-shadow:
0 25px 70px rgba(0,0,0,.4);

}


.header{

text-align:center;

margin-bottom:35px;

}


.header i{

font-size:55px;

color:#38bdf8;

margin-bottom:15px;

}


.header h1{

font-size:35px;

font-weight:700;

}


.header p{

opacity:.7;

margin-top:10px;

}


.program-box{

background:rgba(56,189,248,.10);

border:1px solid rgba(56,189,248,.25);

border-radius:20px;

padding:25px;

margin-bottom:30px;

}


.program-box h4{

color:#38bdf8;

font-weight:700;

margin-bottom:15px;

}


.program-details{

display:flex;

flex-wrap:wrap;

gap:25px;

}


.program-detail{

min-width:130px;

}


.program-detail small{

display:block;

opacity:.6;

margin-bottom:4px;

}


.program-detail strong{

font-size:18px;

}


.form-group{

margin-bottom:20px;

}


.form-group label{

font-weight:500;

}


.form-control{

background:rgba(255,255,255,.08);

border:1px solid rgba(255,255,255,.15);

color:white;

border-radius:12px;

padding:12px 15px;

height:auto;

}


.form-control:focus{

background:rgba(255,255,255,.1);

color:white;

border-color:#38bdf8;

box-shadow:none;

}


.form-control option{

background:#081120;

color:white;

}


.form-control[readonly]{

opacity:.75;

cursor:not-allowed;

}


.agreement{

background:rgba(255,255,255,.05);

padding:20px;

border-radius:15px;

margin-top:25px;

}


.agreement label{

font-size:14px;

margin-bottom:12px;

display:block;

}


.submit-btn{

width:100%;

padding:15px;

border:none;

border-radius:14px;

font-weight:600;

font-size:16px;

background:linear-gradient(
135deg,
#00c6ff,
#0072ff
);

color:white;

margin-top:25px;

transition:.3s;

}


.submit-btn:hover{

transform:translateY(-2px);

}


.submit-btn:disabled{

opacity:.45;

cursor:not-allowed;

transform:none;

}


.success-box{

background:rgba(34,197,94,.15);

border:1px solid rgba(34,197,94,.3);

border-radius:20px;

padding:35px;

text-align:center;

margin-bottom:25px;

}


.success-box i{

color:#4ade80;

}


.queue{

font-size:30px;

font-weight:700;

color:#38bdf8;

margin:15px 0;

}


.error-box{

background:rgba(239,68,68,.15);

border:1px solid rgba(239,68,68,.3);

border-radius:15px;

padding:20px;

margin-bottom:25px;

color:#fecaca;

}


.back-home{

display:inline-block;

margin-top:20px;

color:white;

background:rgba(255,255,255,.1);

padding:12px 22px;

border-radius:12px;

text-decoration:none;

}


.back-home:hover{

color:white;

text-decoration:none;

background:rgba(255,255,255,.18);

}


@media(max-width:600px){

.container-box{

padding:25px;

}

.header h1{

font-size:28px;

}

.program-details{

gap:15px;

}

}

</style>

</head>


<body>


<div class="container-box">


<!-- HEADER -->

<div class="header">

<i class="fa-solid fa-graduation-cap"></i>

<h1>
Scholarship Application
</h1>

<p>
SK KONEXT Scholarship Program
</p>

</div>



<?php if($success){ ?>


<!-- SUCCESS -->

<div class="success-box">

<i class="fa-solid fa-circle-check fa-3x"></i>

<h3 class="mt-3">
Application Submitted Successfully
</h3>

<p>
Your scholarship application has been received.
</p>

<div class="queue">

<?= htmlspecialchars($queue_number) ?>

</div>

<p>
Please keep your application number for reference.
</p>

<a
href="index.php"
class="back-home">

<i class="fa-solid fa-arrow-left"></i>

Back to Home

</a>

</div>


<?php }else{ ?>


<?php if($error != ''){ ?>

<div class="error-box">

<i class="fa-solid fa-circle-exclamation"></i>

<?= htmlspecialchars($error) ?>

</div>

<?php } ?>



<?php if($scholarship_open){ ?>


<!-- PROGRAM INFORMATION -->

<div class="program-box">

<h4>

<i class="fa-solid fa-award"></i>

<?= htmlspecialchars($active_program['title']) ?>

</h4>


<div class="program-details">


<div class="program-detail">

<small>
Budget
</small>

<strong>

₱<?= number_format(
    (float)$active_program['budget'],
    2
) ?>

</strong>

</div>


<div class="program-detail">

<small>
Available Slots
</small>

<strong>

<?= (int)$active_program['slots'] ?>

</strong>

</div>


<div class="program-detail">

<small>
Start Date
</small>

<strong>

<?= date(
    'M d, Y',
    strtotime($active_program['start_date'])
) ?>

</strong>

</div>


<?php if(!empty($active_program['end_date'])){ ?>

<div class="program-detail">

<small>
End Date
</small>

<strong>

<?= date(
    'M d, Y',
    strtotime($active_program['end_date'])
) ?>

</strong>

</div>

<?php } ?>


</div>

</div>



<!-- APPLICATION FORM -->

<form
method="POST"
action="">


<div class="form-group">

<label>
Full Name
</label>

<input
type="text"
name="fullname"
class="form-control"
value="<?= htmlspecialchars($applicant['fullname']) ?>"
readonly
required>

</div>



<div class="form-group">

<label>
Email Address
</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($applicant['email'] ?? '') ?>"
required>

</div>



<div class="form-group">

<label>
Contact Number
</label>

<input
type="text"
name="contact_number"
class="form-control"
value="<?= htmlspecialchars($applicant['phone']) ?>"
readonly
required>

</div>



<div class="form-group">

<label>
School
</label>

<input
type="text"
name="school"
class="form-control"
required>

</div>



<div class="form-group">

<label>
Course
</label>

<input
type="text"
name="course"
class="form-control"
required>

</div>



<div class="form-group">

<label>
Education Level
</label>

<select
name="education_level"
id="education_level"
class="form-control"
required>

<option value="">
Select Education Level
</option>

<option value="Senior High School">
Senior High School
</option>

<option value="College">
College
</option>

</select>

</div>



<div class="form-group">

<label>
Year Level
</label>

<select
name="year_level"
id="year_level"
class="form-control"
disabled
required>

<option value="">
Select Year Level
</option>

</select>

</div>



<div class="form-group">

<label>
Barangay
</label>

<select
name="barangay_id"
class="form-control"
required>

<option value="">
Select Barangay
</option>


<?php while($b = mysqli_fetch_assoc($barangays)){ ?>

<option value="<?= (int)$b['id'] ?>">

<?= htmlspecialchars($b['name']) ?>

</option>

<?php } ?>


</select>

</div>



<!-- AGREEMENTS -->

<div class="agreement">

<strong>
Applicant Agreement
</strong>

<br><br>


<label>

<input
type="checkbox"
class="agreement-check">

 I certify that the information I provided is true and correct.

</label>


<label>

<input
type="checkbox"
class="agreement-check">

 I understand that submitting an application does not guarantee approval.

</label>


<label>

<input
type="checkbox"
class="agreement-check">

 I agree to the verification of the information provided.

</label>


<label>

<input
type="checkbox"
class="agreement-check">

 I understand that the scholarship program has limited slots.

</label>


</div>



<button
type="submit"
name="submit_application"
id="submitBtn"
class="submit-btn"
disabled>

<i class="fa-solid fa-paper-plane"></i>

Submit Application

</button>


</form>


<?php }else{ ?>


<!-- CLOSED -->

<div
class="success-box"
style="background:rgba(71,85,105,.25);border-color:rgba(148,163,184,.25);">

<i
class="fa-solid fa-lock fa-3x"
style="color:#94a3b8;">
</i>

<h3 class="mt-3">

Scholarship Applications Currently Closed

</h3>

<p>

There is currently no active scholarship program accepting applications.

</p>

<a
href="index.php"
class="back-home">

<i class="fa-solid fa-arrow-left"></i>

Back to Home

</a>

</div>


<?php } ?>


<?php } ?>


</div>



<script>

/* =========================================
   EDUCATION LEVEL → YEAR LEVEL
========================================= */

const education =
document.getElementById("education_level");

const year =
document.getElementById("year_level");


if(education && year){

education.addEventListener("change",function(){

year.innerHTML =
'<option value="">Select Year Level</option>';

year.disabled = true;


if(this.value === "Senior High School"){

year.innerHTML +=
'<option value="Grade 11">Grade 11</option>';

year.innerHTML +=
'<option value="Grade 12">Grade 12</option>';

year.disabled = false;

}


if(this.value === "College"){

year.innerHTML +=
'<option value="1st Year">1st Year</option>';

year.innerHTML +=
'<option value="2nd Year">2nd Year</option>';

year.innerHTML +=
'<option value="3rd Year">3rd Year</option>';

year.innerHTML +=
'<option value="4th Year">4th Year</option>';

year.disabled = false;

}

});

}


/* =========================================
   AGREEMENT CHECKBOXES
========================================= */

const checks =
document.querySelectorAll(".agreement-check");

const submit =
document.getElementById("submitBtn");


function checkAgreements(){

if(!submit){
return;
}

let allChecked = true;


checks.forEach(function(check){

if(!check.checked){

allChecked = false;

}

});


submit.disabled = !allChecked;

}


checks.forEach(function(check){

check.addEventListener(
"change",
checkAgreements
);

});


checkAgreements();

</script>


</body>

</html>