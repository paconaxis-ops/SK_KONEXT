<?php

include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin','president','sk_member']);

$user_role = $_SESSION['role'];
$user_barangay = $_SESSION['barangay_id'] ?? null;

$success = "";
$error = "";

/* =====================================================
   HELPER: GENERATE APPLICATION NUMBER
===================================================== */

function generateApplicationNumber($conn, $batch_id, $batch_title, $application_id){

    $batch_id = (int)$batch_id;
    $application_id = (int)$application_id;

    // Generate initials from batch title.
    $words = preg_split('/\s+/', trim($batch_title));
    $prefix = '';

    foreach($words as $word){

        $word = trim($word);

        if($word !== '' && preg_match('/[a-zA-Z]/', $word)){
            $prefix .= strtoupper(substr($word, 0, 1));
        }

    }

    if($prefix == ''){
        $prefix = 'APP';
    }

    // Get the application's sequence within its batch.
    $sequence_query = mysqli_query($conn,"
        SELECT COUNT(*) AS sequence_number
        FROM scholarship_applications
        WHERE batch_id = '$batch_id'
        AND id <= '$application_id'
    ");

    $sequence = 1;

    if($sequence_query){

        $sequence_data = mysqli_fetch_assoc($sequence_query);

        $sequence = (int)$sequence_data['sequence_number'];

    }

    return $prefix . '-' . str_pad($sequence, 2, '0', STR_PAD_LEFT);
}
/* =====================================================
   HELPER: GET ACTIVE BATCH
===================================================== */

$active_batch_query = mysqli_query($conn,"
    SELECT *
    FROM scholar_batches
    WHERE status = 'Active'
    ORDER BY id DESC
    LIMIT 1
");

$active_batch = $active_batch_query
    ? mysqli_fetch_assoc($active_batch_query)
    : null;


/* =====================================================
   CREATE NEW BATCH
   SUPERADMIN ONLY
===================================================== */

if(isset($_POST['create_batch'])){

    if($user_role != 'superadmin'){
        die("Access Denied");
    }

    $title = trim($_POST['title'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = !empty($_POST['end_date'])
        ? $_POST['end_date']
        : null;

    $budget = isset($_POST['budget'])
        ? (float)$_POST['budget']
        : 0;

    $slots = isset($_POST['slots'])
        ? (int)$_POST['slots']
        : 0;


    if($title == '' || $start_date == ''){

        $error = "Batch title and start date are required.";

    }elseif($budget < 0){

        $error = "Budget cannot be negative.";

    }elseif($slots <= 0){

        $error = "Number of slots must be greater than 0.";

    }else{

        $check_active = mysqli_query($conn,"
            SELECT id
            FROM scholar_batches
            WHERE status = 'Active'
            LIMIT 1
        ");

        if($check_active && mysqli_num_rows($check_active) > 0){

            $error = "There is already an active scholarship batch. Complete it first before creating a new batch.";

        }else{

            $title_safe = mysqli_real_escape_string(
                $conn,
                $title
            );

            $start_safe = mysqli_real_escape_string(
                $conn,
                $start_date
            );

            $budget_safe = number_format(
                $budget,
                2,
                '.',
                ''
            );

            if($end_date !== null){

                $end_safe = mysqli_real_escape_string(
                    $conn,
                    $end_date
                );

                $insert_batch = mysqli_query($conn,"
                    INSERT INTO scholar_batches
                    (
                        title,
                        start_date,
                        end_date,
                        status,
                        application_status,
                        budget,
                        slots
                    )
                    VALUES
                    (
                        '$title_safe',
                        '$start_safe',
                        '$end_safe',
                        'Active',
                        'Closed',
                        '$budget_safe',
                        '$slots'
                    )
                ");

            }else{

                $insert_batch = mysqli_query($conn,"
                    INSERT INTO scholar_batches
                    (
                        title,
                        start_date,
                        status,
                        application_status,
                        budget,
                        slots
                    )
                    VALUES
                    (
                        '$title_safe',
                        '$start_safe',
                        'Active',
                        'Closed',
                        '$budget_safe',
                        '$slots'
                    )
                ");
            }

            if($insert_batch){

                header("Location: scholars.php?created=1");
                exit();

            }else{

                $error = "Failed to create scholarship batch: "
                    . mysqli_error($conn);

            }
        }
    }
}


/* =====================================================
   OPEN APPLICATIONS
   SUPERADMIN ONLY
===================================================== */

if(isset($_GET['open_applications'])){

    if($user_role != 'superadmin'){
        die("Access Denied");
    }

    $batch_id = (int)$_GET['open_applications'];

    mysqli_query($conn,"
        UPDATE scholar_batches
        SET application_status = 'Closed'
        WHERE status = 'Active'
    ");

    mysqli_query($conn,"
        UPDATE scholar_batches
        SET application_status = 'Open'
        WHERE id = '$batch_id'
        AND status = 'Active'
    ");

    header("Location: scholars.php?opened=1");
    exit();
}


/* =====================================================
   CLOSE APPLICATIONS
   SUPERADMIN ONLY
===================================================== */

if(isset($_GET['close_applications'])){

    if($user_role != 'superadmin'){
        die("Access Denied");
    }

    $batch_id = (int)$_GET['close_applications'];

    mysqli_query($conn,"
        UPDATE scholar_batches
        SET application_status = 'Closed'
        WHERE id = '$batch_id'
        AND status = 'Active'
    ");

    header("Location: scholars.php?closed=1");
    exit();
}


/* =====================================================
   SCHOLARSHIP DISBURSEMENT
   SUPERADMIN ONLY
===================================================== */

if(isset($_GET['disburse_batch'])){

    if($user_role != 'superadmin'){
        die("Access Denied");
    }

    $batch_id = (int)$_GET['disburse_batch'];

    mysqli_query($conn,"
        UPDATE scholarship_applications
        SET status = 'Released'
        WHERE batch_id = '$batch_id'
        AND status = 'Approved'
    ");

    header("Location: scholars.php?disbursed=1");
    exit();
}


/* =====================================================
   COMPLETE ACTIVE BATCH
   SUPERADMIN ONLY
===================================================== */

if(isset($_GET['complete_batch'])){

    if($user_role != 'superadmin'){
        die("Access Denied");
    }

    $batch_id = (int)$_GET['complete_batch'];

    mysqli_query($conn,"
        UPDATE scholarship_applications
        SET status = 'Claimed'
        WHERE batch_id = '$batch_id'
        AND status = 'Released'
    ");

    mysqli_query($conn,"
        UPDATE scholar_batches
        SET
            status = 'Completed',
            application_status = 'Closed',
            end_date = IF(
                end_date IS NULL,
                CURDATE(),
                end_date
            )
        WHERE id = '$batch_id'
        AND status = 'Active'
    ");

    header("Location: scholars.php?completed=1");
    exit();
}


/* =====================================================
   DELETE SCHOLAR
===================================================== */

if(isset($_GET['delete'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $id = (int)$_GET['delete'];

    if($user_role == 'president'){

        mysqli_query($conn,"
            DELETE FROM scholars
            WHERE id = '$id'
            AND barangay_id = '$user_barangay'
        ");

    }else{

        mysqli_query($conn,"
            DELETE FROM scholars
            WHERE id = '$id'
        ");
    }

    header("Location: scholars.php");
    exit();
}


/* =====================================================
   BULK DELETE SELECTED SCHOLARS
===================================================== */

if(isset($_POST['bulk_delete_scholars'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $delete_ids = $_POST['scholar_ids'] ?? [];

    if(!empty($delete_ids)){

        $safe_ids = [];

        foreach($delete_ids as $id){

            $id = (int)$id;

            if($id > 0){
                $safe_ids[] = $id;
            }

        }

        if(!empty($safe_ids)){

            $id_list = implode(',', $safe_ids);

            if($user_role == 'president'){

                mysqli_query($conn,"
                    DELETE FROM scholars
                    WHERE id IN ($id_list)
                    AND barangay_id = '$user_barangay'
                ");

            }else{

                mysqli_query($conn,"
                    DELETE FROM scholars
                    WHERE id IN ($id_list)
                ");

            }

            header("Location: scholars.php?deleted=selected");
            exit();

        }

    }

    header("Location: scholars.php");
    exit();
}


/* =====================================================
   DELETE ALL SCHOLARS
===================================================== */

if(isset($_POST['delete_all_scholars'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    if($user_role == 'president'){

        mysqli_query($conn,"
            DELETE FROM scholars
            WHERE barangay_id = '$user_barangay'
        ");

    }else{

        mysqli_query($conn,"
            DELETE FROM scholars
        ");

    }

    header("Location: scholars.php?deleted=all");
    exit();
}


/* =====================================================
   DELETE SCHOLARSHIP APPLICATION
   KEPT FOR DELETE SELECTED / EXISTING FUNCTION
===================================================== */

if(isset($_GET['delete_application'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $id = (int)$_GET['delete_application'];

    if($user_role == 'president'){

        mysqli_query($conn,"
            DELETE FROM scholarship_applications
            WHERE id = '$id'
            AND barangay_id = '$user_barangay'
        ");

    }else{

        mysqli_query($conn,"
            DELETE FROM scholarship_applications
            WHERE id = '$id'
        ");

    }

    header("Location: scholars.php?application_deleted=1");
    exit();
}


/* =====================================================
   BULK DELETE SELECTED APPLICATIONS
===================================================== */

if(isset($_POST['bulk_delete_applications'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $delete_ids = $_POST['application_ids'] ?? [];

    if(!empty($delete_ids)){

        $safe_ids = [];

        foreach($delete_ids as $id){

            $id = (int)$id;

            if($id > 0){
                $safe_ids[] = $id;
            }

        }

        if(!empty($safe_ids)){

            $id_list = implode(',', $safe_ids);

            if($user_role == 'president'){

                mysqli_query($conn,"
                    DELETE FROM scholarship_applications
                    WHERE id IN ($id_list)
                    AND barangay_id = '$user_barangay'
                ");

            }else{

                mysqli_query($conn,"
                    DELETE FROM scholarship_applications
                    WHERE id IN ($id_list)
                ");

            }

            header("Location: scholars.php?applications_deleted=selected");
            exit();

        }

    }

    header("Location: scholars.php");
    exit();
}


/* =====================================================
   APPROVE APPLICATION
===================================================== */

if(isset($_GET['approve'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $id = (int)$_GET['approve'];

    $app_query = mysqli_query($conn,"
        SELECT *
        FROM scholarship_applications
        WHERE id = '$id'
        LIMIT 1
    ");

    $app = $app_query
        ? mysqli_fetch_assoc($app_query)
        : null;

    if($app){

        if(
            $user_role == 'president' &&
            $app['barangay_id'] != $user_barangay
        ){

            die("Access Denied");

        }


        if($app['status'] == 'Pending'){

            $batch_id = isset($app['batch_id'])
                ? (int)$app['batch_id']
                : 0;


            if($batch_id <= 0){

                die(
                    "This application is not connected to a scholarship batch."
                );

            }


            $batch_query = mysqli_query($conn,"
                SELECT *
                FROM scholar_batches
                WHERE id = '$batch_id'
                LIMIT 1
            ");

            $batch = $batch_query
                ? mysqli_fetch_assoc($batch_query)
                : null;


            if(!$batch){

                die(
                    "The scholarship batch connected to this application was not found."
                );

            }


            $slot_query = mysqli_query($conn,"
                SELECT COUNT(*) AS total
                FROM scholars
                WHERE batch_id = '$batch_id'
                AND status = 'Active'
            ");

            $slot_data = $slot_query
                ? mysqli_fetch_assoc($slot_query)
                : ['total' => 0];

            $approved_count = (int)$slot_data['total'];

            $batch_slots = (int)$batch['slots'];


            if(
                $batch_slots > 0 &&
                $approved_count >= $batch_slots
            ){

                die(
                    "This scholarship batch has already reached its maximum number of slots."
                );

            }


            $fullname_check = mysqli_real_escape_string(
                $conn,
                $app['fullname']
            );

            $duplicate_query = mysqli_query($conn,"
                SELECT id
                FROM scholars
                WHERE fullname = '$fullname_check'
                AND batch_id = '$batch_id'
                LIMIT 1
            ");

            if(
                $duplicate_query &&
                mysqli_num_rows($duplicate_query) > 0
            ){

                die(
                    "This applicant is already registered as a scholar in this batch."
                );

            }


            $fullname = mysqli_real_escape_string(
                $conn,
                $app['fullname']
            );

            $school = mysqli_real_escape_string(
                $conn,
                $app['school']
            );

            $course = mysqli_real_escape_string(
                $conn,
                $app['course']
            );

            $education_level = isset(
                $app['education_level']
            )
                ? mysqli_real_escape_string(
                    $conn,
                    $app['education_level']
                )
                : '';

            $year_level = mysqli_real_escape_string(
                $conn,
                $app['year_level']
            );

            $barangay_id = (int)$app['barangay_id'];


            $insert_scholar = mysqli_query($conn,"
                INSERT INTO scholars
                (
                    fullname,
                    school,
                    course,
                    education_level,
                    year_level,
                    barangay_id,
                    status,
                    batch_id,
                    approved_date
                )
                VALUES
                (
                    '$fullname',
                    '$school',
                    '$course',
                    '$education_level',
                    '$year_level',
                    '$barangay_id',
                    'Active',
                    '$batch_id',
                    NOW()
                )
            ");


            if($insert_scholar){

                mysqli_query($conn,"
                    UPDATE scholarship_applications
                    SET status = 'Approved'
                    WHERE id = '$id'
                    AND status = 'Pending'
                ");

            }else{

                $error = "Failed to approve application: "
                    . mysqli_error($conn);

            }

        }
    }

    header("Location: scholars.php");
    exit();
}


/* =====================================================
   REJECT APPLICATION
===================================================== */

if(isset($_GET['reject'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $id = (int)$_GET['reject'];

    $app_query = mysqli_query($conn,"
        SELECT *
        FROM scholarship_applications
        WHERE id = '$id'
        LIMIT 1
    ");

    $app = $app_query
        ? mysqli_fetch_assoc($app_query)
        : null;

    if($app){

        if(
            $user_role == 'president' &&
            $app['barangay_id'] != $user_barangay
        ){

            die("Access Denied");

        }

        mysqli_query($conn,"
            UPDATE scholarship_applications
            SET status = 'Rejected'
            WHERE id = '$id'
            AND status = 'Pending'
        ");
    }

    header("Location: scholars.php");
    exit();
}


/* =====================================================
   ADD SCHOLAR MANUALLY
===================================================== */

if(isset($_POST['add_scholar'])){

    if($user_role == 'sk_member'){
        die("Access Denied");
    }

    $fullname = mysqli_real_escape_string(
        $conn,
        $_POST['fullname']
    );

    $school = mysqli_real_escape_string(
        $conn,
        $_POST['school']
    );

    $course = mysqli_real_escape_string(
        $conn,
        $_POST['course']
    );

    $education_level = mysqli_real_escape_string(
        $conn,
        $_POST['education_level']
    );

    $year_level = mysqli_real_escape_string(
        $conn,
        $_POST['year_level']
    );

    // Manual entries are automatically Active; status is no longer selected in the form.
    $status = 'Active';

    // Upload scholar image.
    $image_path = '';
    $upload_error = '';
    $upload_dir = dirname(__DIR__) . '/uploads/scholar_images/';

    if(!isset($_FILES['scholar_image']) || $_FILES['scholar_image']['error'] !== UPLOAD_ERR_OK){
        $upload_error = 'Please upload an image for the scholar.';
    } else {
        $image_tmp = $_FILES['scholar_image']['tmp_name'];
        $image_size = (int)$_FILES['scholar_image']['size'];
        $image_info = @getimagesize($image_tmp);
        $image_mime = $image_info['mime'] ?? '';
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if(!$image_info || !isset($allowed_mimes[$image_mime])){
            $upload_error = 'Only JPG, PNG, or WEBP images are allowed.';
        } elseif($image_size > 5 * 1024 * 1024){
            $upload_error = 'Image must not exceed 5 MB.';
        } else {
            if(!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)){
                $upload_error = 'Unable to create the scholar image upload folder.';
            } else {
                $image_filename = 'scholar_' . bin2hex(random_bytes(8)) . '.' . $allowed_mimes[$image_mime];
                if(move_uploaded_file($image_tmp, $upload_dir . $image_filename)){
                    $image_path = 'uploads/scholar_images/' . $image_filename;
                } else {
                    $upload_error = 'Failed to upload the scholar image.';
                }
            }
        }
    }


    if($user_role == 'president'){

        $barangay_id = (int)$user_barangay;

    }else{

        $barangay_id = (int)$_POST['barangay_id'];

    }


    $batch_query = mysqli_query($conn,"
        SELECT id
        FROM scholar_batches
        WHERE status = 'Active'
        ORDER BY id DESC
        LIMIT 1
    ");

    $batch = $batch_query
        ? mysqli_fetch_assoc($batch_query)
        : null;


    if($upload_error !== ''){

        $error = $upload_error;

    }elseif(!$batch){

        $error = "There is no active scholarship batch. Create a batch first.";

    }else{

        $batch_id = (int)$batch['id'];


        $slot_query = mysqli_query($conn,"
            SELECT COUNT(*) AS total
            FROM scholars
            WHERE batch_id = '$batch_id'
            AND status = 'Active'
        ");

        $slot_data = $slot_query
            ? mysqli_fetch_assoc($slot_query)
            : ['total' => 0];

        $approved_count = (int)$slot_data['total'];


        $batch_query2 = mysqli_query($conn,"
            SELECT slots
            FROM scholar_batches
            WHERE id = '$batch_id'
            LIMIT 1
        ");

        $batch_data2 = $batch_query2
            ? mysqli_fetch_assoc($batch_query2)
            : null;

        $batch_slots = $batch_data2
            ? (int)$batch_data2['slots']
            : 0;


        if(
            $batch_slots > 0 &&
            $approved_count >= $batch_slots
        ){

            $error = "The active scholarship batch has already reached its maximum slots.";

        }else{

            $insert_scholar = mysqli_query($conn,"
                INSERT INTO scholars
                (
                    fullname,
                    school,
                    course,
                    education_level,
                    year_level,
                    barangay_id,
                    status,
                    batch_id,
                    approved_date,
                    image_path
                )
                VALUES
                (
                    '$fullname',
                    '$school',
                    '$course',
                    '$education_level',
                    '$year_level',
                    '$barangay_id',
                    '$status',
                    '$batch_id',
                    NOW(),
                    '" . mysqli_real_escape_string($conn, $image_path) . "'
                )
            ");

            if($insert_scholar){

                $success = "Scholar Added Successfully";

            }else{

                $error = "Failed to add scholar: "
                    . mysqli_error($conn);

            }

        }
    }
}


/* =====================================================
   ACTIVE BATCH AGAIN
===================================================== */

$active_batch_query = mysqli_query($conn,"
    SELECT *
    FROM scholar_batches
    WHERE status = 'Active'
    ORDER BY id DESC
    LIMIT 1
");

$active_batch = $active_batch_query
    ? mysqli_fetch_assoc($active_batch_query)
    : null;


/* =====================================================
   SCHOLARSHIP COUNTS + BUDGET CALCULATION
===================================================== */

$approved_application_count = 0;
$total_application_count = 0;

$total_budget = 0;
$budget_per_scholar = 0;
$remaining_budget = 0;
$approved_percentage = 0;

if($active_batch){

    $current_batch_id = (int)$active_batch['id'];
    $batch_slots = (int)$active_batch['slots'];

    /* TOTAL APPLICATIONS */

    $total_application_query = mysqli_query($conn,"
        SELECT COUNT(*) AS total
        FROM scholarship_applications
        WHERE batch_id = '$current_batch_id'
    ");

    if($total_application_query){

        $total_application_data =
            mysqli_fetch_assoc(
                $total_application_query
            );

        $total_application_count =
            (int)$total_application_data['total'];
    }


    /* APPROVED APPLICATIONS */

    $approved_application_query = mysqli_query($conn,"
        SELECT COUNT(*) AS total
        FROM scholarship_applications
        WHERE batch_id = '$current_batch_id'
        AND status = 'Approved'
    ");

    if($approved_application_query){

        $approved_application_data =
            mysqli_fetch_assoc(
                $approved_application_query
            );

        $approved_application_count =
            (int)$approved_application_data['total'];
    }


    /* BUDGET CALCULATION */

    $total_budget =
        (float)$active_batch['budget'];

    if($batch_slots > 0){

        $budget_per_scholar =
            $total_budget / $batch_slots;

        $remaining_budget =
            $total_budget -
            (
                $approved_application_count *
                $budget_per_scholar
            );
    }


    if($remaining_budget < 0){

        $remaining_budget = 0;

    }


    /* APPROVED PROGRESS */

    if($batch_slots > 0){

        $approved_percentage =
            (
                $approved_application_count /
                $batch_slots
            ) * 100;

        if($approved_percentage > 100){

            $approved_percentage = 100;

        }
    }
}


/* =====================================================
   SCHOLARS
===================================================== */

$scholar_sql = "
    SELECT
        scholars.*,
        barangays.name AS barangay_name,
        scholar_batches.title AS batch_title,
        linked_app.id AS linked_application_id

    FROM scholars

    LEFT JOIN barangays
        ON barangays.id = scholars.barangay_id

    LEFT JOIN scholar_batches
        ON scholar_batches.id = scholars.batch_id

    LEFT JOIN scholarship_applications AS linked_app
        ON linked_app.fullname = scholars.fullname
        AND linked_app.batch_id = scholars.batch_id
        AND linked_app.status IN ('Approved','Released','Claimed')
";


$where = [];


if($user_role == 'president'){

    $where[] = "
        scholars.barangay_id = '$user_barangay'
    ";

}


if(!empty($where)){

    $scholar_sql .= "
        WHERE " .
        implode(" AND ",$where);

}


$scholar_sql .= "
    ORDER BY scholars.id DESC
";


$scholars = mysqli_query(
    $conn,
    $scholar_sql
);


/* =====================================================
   APPLICATIONS
   ONLY SHOW APPLICATIONS FROM ACTIVE BATCH
===================================================== */

$application_sql = "
    SELECT
        scholarship_applications.*,
        barangays.name AS barangay_name,
        scholar_batches.title AS batch_title

    FROM scholarship_applications

    LEFT JOIN barangays
        ON barangays.id =
           scholarship_applications.barangay_id

    LEFT JOIN scholar_batches
        ON scholar_batches.id =
           scholarship_applications.batch_id
";


$app_where = [];


/* ONLY ACTIVE BATCH */

if($active_batch){

    $current_application_batch_id =
        (int)$active_batch['id'];

    $app_where[] = "
        scholarship_applications.batch_id =
        '$current_application_batch_id'
    ";

}else{

    /*
       No active batch means no applications
       should appear in the application table.
    */

    $app_where[] = "
        1 = 0
    ";

}


/* PRESIDENT ONLY SEES OWN BARANGAY */

if($user_role == 'president'){

    $app_where[] = "
        scholarship_applications.barangay_id =
        '$user_barangay'
    ";

}


if(!empty($app_where)){

    $application_sql .= "
        WHERE " .
        implode(" AND ",$app_where);

}


$application_sql .= "
    ORDER BY scholarship_applications.id DESC
";


$applications = mysqli_query(
    $conn,
    $application_sql
);


/* =====================================================
   BARANGAYS
===================================================== */

$barangays = mysqli_query($conn,"
    SELECT *
    FROM barangays
    ORDER BY name ASC
");


/* =====================================================
   ARCHIVED BATCHES
===================================================== */

$archived_batches = mysqli_query($conn,"
    SELECT *
    FROM scholar_batches
    WHERE status = 'Completed'
    ORDER BY id DESC
");


/* =====================================================
   ARCHIVED BATCH RECORDS
===================================================== */

$archive_records = [];

if($archived_batches){

    mysqli_data_seek($archived_batches,0);

    while($archive = mysqli_fetch_assoc($archived_batches)){

        $archive_id = (int)$archive['id'];

        $archive_records[$archive_id] = [
            'batch' => $archive,
            'scholars' => [],
            'applications' => []
        ];


        $archive_scholars_query = mysqli_query($conn,"
    SELECT
        scholars.*,
        barangays.name AS barangay_name,
        linked_app.id AS linked_application_id
    FROM scholars

    LEFT JOIN barangays
        ON barangays.id = scholars.barangay_id

    LEFT JOIN scholarship_applications AS linked_app
        ON linked_app.fullname = scholars.fullname
        AND linked_app.batch_id = scholars.batch_id
        AND linked_app.status IN ('Approved','Released','Claimed')

    WHERE scholars.batch_id = '$archive_id'

    " . (
        $user_role == 'president'
        ? " AND scholars.barangay_id = '$user_barangay' "
        : ""
    ) . "

    ORDER BY scholars.id DESC
");


        if($archive_scholars_query){

            while($archive_scholar = mysqli_fetch_assoc(
                $archive_scholars_query
            )){

                $archive_records[$archive_id]['scholars'][] =
                    $archive_scholar;

            }

        }


        $archive_applications_query = mysqli_query($conn,"
            SELECT
                scholarship_applications.*,
                barangays.name AS barangay_name
            FROM scholarship_applications
            LEFT JOIN barangays
                ON barangays.id = scholarship_applications.barangay_id
            WHERE scholarship_applications.batch_id = '$archive_id'
            " . (
                $user_role == 'president'
                ? " AND scholarship_applications.barangay_id = '$user_barangay' "
                : ""
            ) . "
            ORDER BY scholarship_applications.id DESC
        ");


        if($archive_applications_query){

            while($archive_application = mysqli_fetch_assoc(
                $archive_applications_query
            )){

                $archive_records[$archive_id]['applications'][] =
                    $archive_application;

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Scholar Management | SK KONEXT</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
rel="stylesheet">


<style>

/* TABLE PAGINATION */

.table-pagination{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:12px;
    padding:15px 5px;
    color:#71877c;
    font-size:13px;
}

.pagination-buttons{
    display:flex;
    align-items:center;
    gap:5px;
    flex-wrap:wrap;
}

.pagination-buttons button{
    border:1px solid #dce8df;
    background:#fff;
    color:#365b46;
    padding:7px 12px;
    border-radius:7px;
    cursor:pointer;
    font-size:12px;
}

.pagination-buttons button.active{
    background:#285c40;
    color:#fff;
    border-color:#285c40;
}

.pagination-buttons button:disabled{
    opacity:.4;
    cursor:not-allowed;
}    

:root{
    --bg:#06110d;
    --bg-soft:#091914;
    --card:rgba(14,31,24,.78);
    --card-strong:#0d2119;
    --border:rgba(148,163,184,.13);
    --text:#f4f8f5;
    --muted:#91a59b;
    --green:#32d583;
    --green-dark:#123f2b;
    --green-soft:rgba(50,213,131,.12);
    --danger:#ff6b6b;
    --warning:#f5c451;
    --blue:#6da9ff;
}

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
    background:
        radial-gradient(circle at 15% 5%,rgba(41,120,79,.17),transparent 30%),
        radial-gradient(circle at 85% 15%,rgba(25,91,61,.13),transparent 30%),
        #06110d;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    min-height:100vh;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    background:
        linear-gradient(rgba(255,255,255,.012) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.012) 1px,transparent 1px);
    background-size:40px 40px;
    mask-image:linear-gradient(to bottom,black,transparent 80%);
    z-index:-1;
}


/* =====================================================
   TOP NAV
===================================================== */

.topbar{
    position:fixed;
    top:18px;
    left:24px;
    right:24px;
    height:68px;
    z-index:1000;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 16px 0 20px;
    background:rgba(7,20,14,.82);
    border:1px solid rgba(255,255,255,.08);
    border-radius:20px;
    backdrop-filter:blur(22px);
    -webkit-backdrop-filter:blur(22px);
    box-shadow:0 20px 50px rgba(0,0,0,.25);
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    color:white;
    text-decoration:none !important;
}

.brand-mark{
    width:40px;
    height:40px;
    border-radius:13px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(145deg,#2fd17f,#14643e);
    color:white;
    box-shadow:0 8px 25px rgba(50,213,131,.2);
}

.brand-text{
    font-family:'Manrope',sans-serif;
    font-weight:800;
    letter-spacing:-.4px;
    font-size:17px;
}

.brand-sub{
    color:#789086;
    font-size:11px;
    margin-left:5px;
    font-weight:500;
}

.dashboard-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 17px;
    border-radius:12px;
    color:#dff8e9 !important;
    text-decoration:none !important;
    background:rgba(50,213,131,.09);
    border:1px solid rgba(50,213,131,.2);
    font-weight:700;
    font-size:13px;
    transition:.2s ease;
}

.dashboard-btn:hover{
    background:rgba(50,213,131,.17);
    border-color:rgba(50,213,131,.38);
    transform:translateY(-1px);
}


/* =====================================================
   MAIN
===================================================== */

.main{
    width:min(1540px,100%);
    margin:0 auto;
    padding:125px 30px 70px;
}


/* =====================================================
   HERO
===================================================== */

.page-title{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:30px;
    margin-bottom:32px;
}

.eyebrow{
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--green);
    text-transform:uppercase;
    letter-spacing:2px;
    font-size:11px;
    font-weight:800;
    margin-bottom:10px;
}

.eyebrow-dot{
    width:6px;
    height:6px;
    border-radius:50%;
    background:var(--green);
    box-shadow:0 0 15px rgba(50,213,131,.7);
}

.page-title h2{
    margin:0;
    font-family:'Manrope',sans-serif;
    font-size:clamp(32px,4vw,52px);
    line-height:1.05;
    letter-spacing:-2px;
    font-weight:800;
    color:#f4f8f5;
}

.page-title p{
    margin:12px 0 0;
    color:var(--muted);
    font-size:14px;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn-custom{
    border:1px solid transparent;
    border-radius:11px;
    padding:10px 15px;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none !important;
    transition:transform .18s ease,background .18s ease,border-color .18s ease,box-shadow .18s ease;
}

.btn-custom:hover{
    transform:translateY(-1px);
}

.btn-primary-custom{
    background:#31c97b;
    color:#04120b !important;
    border-color:#31c97b;
    box-shadow:0 10px 25px rgba(50,213,131,.15);
}

.btn-primary-custom:hover{
    background:#48dc90;
    border-color:#48dc90;
}

.btn-success-custom{
    background:rgba(50,213,131,.11);
    color:#70e8a7 !important;
    border-color:rgba(50,213,131,.22);
}

.btn-success-custom:hover{
    background:rgba(50,213,131,.18);
}

.btn-danger-custom{
    background:rgba(255,107,107,.09);
    color:#ff8585 !important;
    border-color:rgba(255,107,107,.18);
}

.btn-danger-custom:hover{
    background:rgba(255,107,107,.15);
}

.btn-danger-custom:disabled{
    opacity:.4;
    cursor:not-allowed;
    transform:none;
}

.btn-warning-custom{
    background:rgba(245,196,81,.1);
    color:#f6cf70 !important;
    border-color:rgba(245,196,81,.2);
}

.btn-warning-custom:hover{
    background:rgba(245,196,81,.16);
}

.btn-secondary-custom{
    background:rgba(148,163,184,.08);
    color:#bdc9c3 !important;
    border-color:rgba(148,163,184,.15);
}

.btn-secondary-custom:hover{
    background:rgba(148,163,184,.14);
}

.btn-light-custom{
    background:#eef8f2;
    color:#0a2115 !important;
    border-color:#eef8f2;
}

.btn-info-custom{
    background:rgba(109,169,255,.1);
    color:#8dbbff !important;
    border-color:rgba(109,169,255,.2);
}


/* =====================================================
   ALERTS
===================================================== */

.alert-box{
    position:relative;
    padding:15px 18px;
    border-radius:14px;
    margin-bottom:18px;
    border:1px solid transparent;
    font-size:13px;
    backdrop-filter:blur(12px);
}

.alert-box i{
    margin-right:7px;
}

.alert-success{
    background:rgba(50,213,131,.08);
    color:#8beab3;
    border-color:rgba(50,213,131,.16);
}

.alert-danger{
    background:rgba(255,107,107,.08);
    color:#ff9898;
    border-color:rgba(255,107,107,.16);
}

.alert-box a{
    color:inherit !important;
}


/* =====================================================
   ACTIVE BATCH
===================================================== */

.batch-card{
    position:relative;
    overflow:hidden;
    padding:30px;
    margin-bottom:25px;
    border-radius:24px;
    background:
        radial-gradient(circle at 90% 0%,rgba(75,219,142,.16),transparent 30%),
        linear-gradient(135deg,rgba(18,57,40,.95),rgba(8,29,20,.97));
    border:1px solid rgba(92,225,151,.15);
    box-shadow:0 25px 70px rgba(0,0,0,.2);
}

.batch-card::after{
    content:"";
    position:absolute;
    right:-90px;
    bottom:-130px;
    width:300px;
    height:300px;
    border-radius:50%;
    border:1px solid rgba(50,213,131,.09);
    box-shadow:
        0 0 0 35px rgba(50,213,131,.025),
        0 0 0 70px rgba(50,213,131,.018);
    pointer-events:none;
}

.batch-label{
    display:inline-flex;
    align-items:center;
    gap:7px;
    color:#71e9a8;
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:1.5px;
    margin-bottom:11px;
}

.batch-card h3{
    margin:0 0 9px;
    font-family:'Manrope',sans-serif;
    font-size:25px;
    font-weight:800;
    letter-spacing:-.7px;
    color:#f4fff8;
}

.batch-date{
    color:#91aea0;
    font-size:13px;
    margin-bottom:18px;
}

.batch-status-row{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.batch-status{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 11px;
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.08);
    border-radius:999px;
    color:#dce9e2;
    font-size:11px;
    font-weight:700;
}

.batch-status i{
    font-size:6px;
    color:#55e79a;
}

.batch-info{
    display:grid;
    grid-template-columns:repeat(4,minmax(150px,1fr));
    gap:12px;
    margin-top:23px;
}

.batch-info-item{
    padding:16px;
    border-radius:15px;
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.07);
    color:#b5c7bd;
    font-size:12px;
}

.batch-info-item i{
    color:#52dd96;
    margin-right:7px;
}

.batch-info-item strong{
    display:block;
    color:#f3fbf6;
    font-size:16px;
    margin-top:5px;
}

.batch-actions{
    display:flex;
    gap:9px;
    flex-wrap:wrap;
    margin-top:22px;
    position:relative;
    z-index:2;
}


/* =====================================================
   PROGRESS CARDS
===================================================== */

.progress-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
    margin-top:12px;
    position:relative;
    z-index:2;
}

.progress-card{
    padding:18px;
    border-radius:16px;
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.07);
}

.progress-card-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:15px;
    margin-bottom:12px;
}

.progress-card-title{
    color:#aebfb6;
    font-size:11px;
    font-weight:700;
}

.progress-card-title i{
    color:#52dd96;
    margin-right:6px;
}

.progress-card-number{
    color:#f3fbf6;
    font-size:18px;
    font-weight:800;
    white-space:nowrap;
}

.progress-track{
    width:100%;
    height:9px;
    overflow:hidden;
    border-radius:999px;
    background:rgba(255,255,255,.07);
}

.progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#25bd70,#54e69b);
    box-shadow:0 0 15px rgba(50,213,131,.2);
    transition:width .4s ease;
}

.progress-footer{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:8px;
    color:#71877c;
    font-size:10px;
}


/* =====================================================
   PANELS
===================================================== */

.panel{
    background:
        linear-gradient(145deg,rgba(13,31,23,.9),rgba(8,22,16,.84));
    border:1px solid var(--border);
    border-radius:21px;
    padding:23px;
    margin-bottom:22px;
    box-shadow:0 20px 60px rgba(0,0,0,.14);
    overflow:hidden;
}

.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:19px;
    flex-wrap:wrap;
}

.panel-header h4{
    margin:0;
    font-family:'Manrope',sans-serif;
    font-size:17px;
    font-weight:800;
    letter-spacing:-.3px;
    color:#eaf4ee;
}

.panel-header h4 i{
    color:#4cdb92;
    margin-right:7px;
}

.panel-subtitle{
    color:#71877c;
    font-size:12px;
    margin-top:5px;
}


/* =====================================================
   SEARCH + SORT
===================================================== */

.table-tools{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:17px;
}

.search-box{
    flex:1;
    min-width:240px;
    position:relative;
}

.search-box::before{
    content:"\f002";
    font-family:"Font Awesome 6 Free";
    font-weight:900;
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#61766c;
    font-size:12px;
}

.search-box input,
.sort-select{
    width:100%;
    height:43px;
    border:1px solid rgba(148,163,184,.13);
    border-radius:11px;
    background:rgba(255,255,255,.035);
    color:#dce8e1;
    padding:0 13px;
    outline:none;
    font-size:12px;
    transition:.2s;
}

.search-box input{
    padding-left:38px;
}

.search-box input::placeholder{
    color:#5f756a;
}

.search-box input:focus,
.sort-select:focus{
    border-color:rgba(50,213,131,.38);
    background:rgba(50,213,131,.035);
    box-shadow:0 0 0 3px rgba(50,213,131,.05);
}

.sort-select{
    width:190px;
}

.sort-select option{
    background:#10231a;
    color:white;
}


/* =====================================================
   BULK ACTIONS
===================================================== */

.bulk-actions{
    display:flex;
    align-items:center;
    gap:9px;
    flex-wrap:wrap;
    margin-bottom:17px;
    padding:12px 14px;
    background:rgba(255,255,255,.025);
    border:1px solid rgba(148,163,184,.09);
    border-radius:13px;
}

.select-all-label{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin:0;
    padding:9px 12px;
    border-radius:10px;
    background:rgba(50,213,131,.06);
    border:1px solid rgba(50,213,131,.12);
    color:#a9bbb2;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
}

.select-all-label input,
.scholar-checkbox,
.application-checkbox{
    width:16px;
    height:16px;
    accent-color:#32d583;
    cursor:pointer;
}

.checkbox-column{
    width:42px;
    min-width:42px;
    text-align:center !important;
}

.scholar-checkbox,
.application-checkbox{
    margin:0;
}

.bulk-actions .btn-custom:disabled{
    opacity:.4;
    cursor:not-allowed;
    transform:none;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper{
    width:100%;
    overflow-x:auto;
    border:1px solid rgba(148,163,184,.09);
    border-radius:15px;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:1080px;
}

thead th{
    background:rgba(255,255,255,.035);
    color:#738a7f;
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.7px;
    padding:14px 13px;
    white-space:nowrap;
    border-bottom:1px solid rgba(148,163,184,.09);
}

tbody td{
    padding:14px 13px;
    border-top:1px solid rgba(148,163,184,.07);
    vertical-align:middle;
    color:#b7c7bf;
    font-size:12px;
}

tbody td strong{
    color:#edf7f1;
    font-weight:700;
}

tbody tr{
    transition:.16s ease;
}

tbody tr:hover{
    background:rgba(50,213,131,.035);
}

tbody tr:first-child td{
    border-top:none;
}

tbody td:first-child{
    color:#6e857a;
}

tbody td:last-child{
    white-space:nowrap;
}

.action-cell{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}


/* =====================================================
   STATUS
===================================================== */

.status-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:6px 10px;
    border-radius:999px;
    font-size:10px;
    font-weight:800;
    border:1px solid transparent;
}

.status-active{
    background:rgba(50,213,131,.1);
    color:#65e3a0;
    border-color:rgba(50,213,131,.15);
}

.status-pending{
    background:rgba(245,196,81,.1);
    color:#f4ce70;
    border-color:rgba(245,196,81,.15);
}

.status-approved{
    background:rgba(50,213,131,.1);
    color:#65e3a0;
    border-color:rgba(50,213,131,.15);
}

.status-rejected{
    background:rgba(255,107,107,.1);
    color:#ff8989;
    border-color:rgba(255,107,107,.15);
}

.status-open{
    background:rgba(50,213,131,.1);
    color:#65e3a0;
}

.status-closed{
    background:rgba(148,163,184,.08);
    color:#99aaa1;
}


/* =====================================================
   FORMS
===================================================== */

.form-group{
    margin-bottom:18px;
}

.form-group label{
    color:#a8b9b0;
    font-size:11px;
    font-weight:700;
    margin-bottom:7px;
}

.form-control{
    height:44px;
    border-radius:10px;
    background:rgba(255,255,255,.035) !important;
    border:1px solid rgba(148,163,184,.13) !important;
    color:#e8f1eb !important;
    font-size:12px;
    box-shadow:none !important;
}

.form-control::placeholder{
    color:#60766b;
}

.form-control:focus{
    border-color:rgba(50,213,131,.38) !important;
    background:rgba(50,213,131,.035) !important;
    color:white !important;
}

.form-control option{
    background:#10231a;
    color:white;
}

.form-control:disabled{
    opacity:.5;
}

.input-group-text{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(148,163,184,.13);
    color:#83a093;
}


/* =====================================================
   PROGRAM NOTE
===================================================== */

.program-note{
    background:rgba(109,169,255,.06);
    border:1px solid rgba(109,169,255,.13);
    color:#9cc3ff;
    padding:14px 16px;
    border-radius:12px;
    font-size:12px;
}

.program-note i{
    margin-right:6px;
}

.program-note strong{
    color:#b7d2ff;
}


/* =====================================================
   ARCHIVE
===================================================== */

.archive-toggle{
    width:100%;
    border:1px solid rgba(148,163,184,.1);
    background:rgba(255,255,255,.035);
    padding:15px 17px;
    border-radius:12px;
    text-align:left;
    font-weight:700;
    color:#bdccc5;
    cursor:pointer;
    transition:.2s;
}

.archive-toggle:hover{
    background:rgba(50,213,131,.045);
    border-color:rgba(50,213,131,.15);
}

.archive-toggle i{
    color:#56dc95;
    margin-right:7px;
}

.archive-list{
    margin-top:12px;
}

.archive-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    padding:17px;
    background:rgba(255,255,255,.025);
    border:1px solid rgba(148,163,184,.09);
    border-radius:14px;
    margin-bottom:9px;
    flex-wrap:wrap;
    transition:.2s;
}

.archive-item:hover{
    background:rgba(50,213,131,.035);
    border-color:rgba(50,213,131,.12);
}

.archive-info strong{
    display:block;
    color:#e6f0ea;
    font-size:13px;
    margin-bottom:5px;
}

.archive-info strong i{
    color:#55dc94;
    margin-right:6px;
}

.archive-info small{
    color:#71877c;
    font-size:11px;
}

.archive-details{
    width:100%;
    margin-top:3px;
    padding:17px;
    background:rgba(0,0,0,.14);
    border:1px solid rgba(148,163,184,.08);
    border-radius:13px;
}

.archive-details-title{
    color:#aebfb6;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.7px;
    margin-bottom:12px;
}

.archive-mini-table{
    width:100%;
    min-width:700px;
    border-collapse:collapse;
}

.archive-mini-table th{
    padding:10px;
    text-align:left;
    color:#71877c;
    font-size:9px;
    text-transform:uppercase;
    border-bottom:1px solid rgba(148,163,184,.08);
}

.archive-mini-table td{
    padding:10px;
    color:#b7c7bf;
    font-size:11px;
    border-top:1px solid rgba(148,163,184,.06);
}


/* =====================================================
   MODAL
===================================================== */

.modal-backdrop.show{
    opacity:.78;
}

.modal-content{
    background:#0b1b14;
    border:1px solid rgba(255,255,255,.09);
    border-radius:20px;
    box-shadow:0 35px 100px rgba(0,0,0,.5);
    color:#eaf4ee;
}

.modal-header{
    padding:22px 23px 12px;
    border:none;
}

.modal-title{
    font-family:'Manrope',sans-serif;
    font-size:18px;
    font-weight:800;
}

.modal-title i{
    color:#4cdb92;
    margin-right:7px;
}

.modal-header .close{
    color:#a9bbb2;
    opacity:1;
    text-shadow:none;
}

.modal-header .close:hover{
    color:white;
}

.modal-body{
    padding:15px 23px 20px;
}

.modal-footer{
    border-top:1px solid rgba(148,163,184,.09);
    padding:14px 23px 19px;
}


/* =====================================================
   SCHOLAR IMAGE PREVIEW
===================================================== */
#scholarImageModal .modal-dialog{
    max-width:420px;
    margin:1.75rem auto;
}

#scholarImageModal .modal-content{
    overflow:hidden;
    border:1px solid rgba(76,219,146,.22);
    background:linear-gradient(145deg,#10251b,#09150f);
    box-shadow:0 22px 65px rgba(0,0,0,.45);
}

#scholarImageModal .modal-header{
    align-items:center;
    padding:15px 18px 10px;
}

#scholarImageModal .modal-body{
    padding:8px 18px 18px;
}

.scholar-image-frame{
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
    min-height:150px;
    max-height:60vh;
    padding:8px;
    overflow:hidden;
    border-radius:14px;
    background:rgba(0,0,0,.22);
    border:1px solid rgba(255,255,255,.08);
}

#scholarImagePreview{
    display:block;
    max-width:100%;
    max-height:55vh;
    width:auto;
    height:auto;
    object-fit:contain;
    border-radius:8px;
    opacity:0;
    transform:scale(.94);
    transition:opacity .22s ease, transform .22s ease;
}

#scholarImagePreview.image-ready{
    opacity:1;
    transform:scale(1);
}

#scholarImageModal.show .modal-dialog{
    animation:scholarPreviewPop .24s ease-out both;
}

@keyframes scholarPreviewPop{
    from{opacity:0;transform:translateY(10px) scale(.96)}
    to{opacity:1;transform:translateY(0) scale(1)}
}

@media (max-width:480px){
    #scholarImageModal .modal-dialog{max-width:calc(100% - 28px);}
}


/* =====================================================
   COLLAPSE
===================================================== */

.collapse:not(.show){
    display:none;
}


/* =====================================================
   SCROLLBAR
===================================================== */

::-webkit-scrollbar{
    width:8px;
    height:8px;
}

::-webkit-scrollbar-track{
    background:#07130e;
}

::-webkit-scrollbar-thumb{
    background:#234435;
    border-radius:20px;
}

::-webkit-scrollbar-thumb:hover{
    background:#32634a;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:900px){

    .topbar{
        left:14px;
        right:14px;
        top:12px;
    }

    .main{
        padding:110px 18px 50px;
    }

    .page-title{
        align-items:flex-start;
        flex-direction:column;
    }

    .batch-info{
        grid-template-columns:1fr;
    }

    .progress-grid{
        grid-template-columns:1fr;
    }

}


@media(max-width:600px){

    .topbar{
        height:62px;
        padding:0 11px 0 13px;
        border-radius:17px;
    }

    .brand-sub{
        display:none;
    }

    .brand-text{
        font-size:15px;
    }

    .brand-mark{
        width:36px;
        height:36px;
    }

    .dashboard-btn{
        padding:9px 12px;
        font-size:11px;
    }

    .dashboard-btn span{
        display:none;
    }

    .main{
        padding:95px 12px 40px;
    }

    .page-title h2{
        font-size:34px;
        letter-spacing:-1.5px;
    }

    .page-title p{
        font-size:13px;
    }

    .batch-card{
        padding:22px;
        border-radius:19px;
    }

    .batch-card h3{
        font-size:21px;
    }

    .panel{
        padding:17px;
        border-radius:17px;
    }

    .sort-select{
        width:100%;
    }

    .search-box{
        min-width:100%;
    }

    .btn-custom{
        font-size:11px;
        padding:9px 11px;
    }

}

</style>

</head>


<body>


<header class="topbar">

<a href="dashboard.php" class="brand">

    <div class="brand-mark">
        <i class="fa-solid fa-graduation-cap"></i>
    </div>

    <div class="brand-text">
        SK KONEXT
        <span class="brand-sub">
            Scholarship
        </span>
    </div>

</a>


<a href="dashboard.php" class="dashboard-btn">

    <i class="fa-solid fa-house"></i>

    <span>Dashboard</span>

</a>

</header>


<div class="main">


<div class="page-title">

<div>

<div class="eyebrow">

    <span class="eyebrow-dot"></span>

    Scholarship Management

</div>

<h2>
Scholar Management
</h2>

<p>
Manage scholarship applications, scholars, batches, and program records.
</p>

</div>


<?php if($user_role == 'superadmin'){ ?>

<button
    class="btn-custom btn-primary-custom"
    data-toggle="modal"
    data-target="#newBatchModal">

    <i class="fa-solid fa-plus"></i>

    New Batch

</button>

<?php } ?>

</div>


<?php if(isset($_GET['created'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
New scholarship batch created successfully.
Applications are currently closed.
</div>

<?php } ?>


<?php if(isset($_GET['opened'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship applications are now open.
</div>

<?php } ?>


<?php if(isset($_GET['closed'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship applications are now closed.
</div>

<?php } ?>


<?php if(isset($_GET['completed'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship batch completed successfully.
</div>

<?php } ?>


<?php if(isset($_GET['approved'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship application approved successfully.
</div>

<?php } ?>


<?php if(isset($_GET['rejected'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship application rejected.
</div>

<?php } ?>


<?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'selected'){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Selected scholar records deleted successfully.
</div>

<?php } ?>


<?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'all'){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
All allowed scholar records deleted successfully.
</div>

<?php } ?>


<?php if(isset($_GET['application_deleted'])){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Scholarship application deleted successfully.
</div>

<?php } ?>


<?php if(isset($_GET['applications_deleted']) && $_GET['applications_deleted'] == 'selected'){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
Selected scholarship applications deleted successfully.
</div>

<?php } ?>


<?php if($success != ""){ ?>

<div class="alert-box alert-success">
<i class="fa-solid fa-circle-check"></i>
<?= htmlspecialchars($success) ?>
</div>

<?php } ?>


<?php if($error != ""){ ?>

<div class="alert-box alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<?= htmlspecialchars($error) ?>
</div>

<?php } ?>


<!-- =====================================================
   ACTIVE BATCH
===================================================== -->

<div class="batch-card">

<?php if($active_batch){ ?>

<div class="batch-label">

    <i class="fa-solid fa-sparkles"></i>

    Active Scholarship Program

</div>


<h3>

<i class="fa-solid fa-layer-group"></i>

<?= htmlspecialchars(
    $active_batch['title']
) ?>

</h3>


<div class="batch-date">

<i class="fa-regular fa-calendar"></i>

Start:
<?= date(
    'F d, Y',
    strtotime($active_batch['start_date'])
) ?>

<?php if(!empty($active_batch['end_date'])){ ?>

&nbsp;&nbsp;•&nbsp;&nbsp;

End:
<?= date(
    'F d, Y',
    strtotime($active_batch['end_date'])
) ?>

<?php } ?>

</div>


<div class="batch-status-row">

<span class="batch-status">

<i class="fa-solid fa-circle"></i>

ACTIVE

</span>


<?php if(
    $active_batch['application_status']
    == 'Open'
){ ?>

<span class="batch-status">

<i class="fa-solid fa-lock-open"></i>

APPLICATIONS OPEN

</span>

<?php }else{ ?>

<span class="batch-status">

<i class="fa-solid fa-lock"></i>

APPLICATIONS CLOSED

</span>

<?php } ?>

</div>


<?php

$active_scholar_count_query = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM scholars
    WHERE batch_id = " .
    (int)$active_batch['id'] .
    "
    AND status = 'Active'
    "
);

$active_scholar_count = 0;

if($active_scholar_count_query){

    $active_scholar_count =
        (int)mysqli_fetch_assoc(
            $active_scholar_count_query
        )['total'];

}

?>


<div class="batch-info">

<div class="batch-info-item">

<i class="fa-solid fa-peso-sign"></i>

Budget

<strong>
₱<?= number_format(
    $active_batch['budget'],
    2
) ?>
</strong>

</div>


<div class="batch-info-item">

<i class="fa-solid fa-wallet"></i>

Remaining Budget

<strong>
₱<?= number_format(
    $remaining_budget,
    2
) ?>
</strong>

</div>


<div class="batch-info-item">

<i class="fa-solid fa-users"></i>

Available Slots

<strong>
<?= max(0, (int)$active_batch['slots'] - $active_scholar_count) ?>
</strong>

</div>


<div class="batch-info-item">

<i class="fa-solid fa-user-graduate"></i>

Approved Scholars

<strong>
<?= $active_scholar_count ?>
/
<?= (int)$active_batch['slots'] ?>
</strong>

</div>

</div>


<div class="progress-grid">


<div class="progress-card">

<div class="progress-card-header">

<div class="progress-card-title">

<i class="fa-solid fa-user-check"></i>

Approved Applications

</div>

<div class="progress-card-number">

<?= $approved_application_count ?>

/

<?= (int)$active_batch['slots'] ?>

</div>

</div>


<div class="progress-track">

<div
    class="progress-fill"
    style="width:<?= $approved_percentage ?>%;">

</div>

</div>


<div class="progress-footer">

<span>
<?= number_format($approved_percentage,1) ?>% of available slots
</span>

<span>
<?= (int)$active_batch['slots'] ?> Slots
</span>

</div>

</div>


<div class="progress-card">

<div class="progress-card-header">

<div class="progress-card-title">

<i class="fa-solid fa-file-lines"></i>

Applications

</div>

<div class="progress-card-number">

<?= $total_application_count ?>

</div>

</div>


<div class="progress-track">

<div
    class="progress-fill"
    style="width:100%;">

</div>

</div>


<div class="progress-footer">

<span>
Total applicants submitted
</span>

<span>
No Limit
</span>

</div>

</div>


</div>


<div
    style="
    margin-top:12px;
    color:#71877c;
    font-size:11px;
    position:relative;
    z-index:2;
    ">

<i class="fa-solid fa-calculator" style="color:#52dd96;margin-right:5px;"></i>

Budget per scholar:

<strong style="color:#b9cbc1;">
₱<?= number_format($budget_per_scholar,2) ?>
</strong>

&nbsp;•&nbsp;

Total budget:

<strong style="color:#b9cbc1;">
₱<?= number_format($total_budget,2) ?>
</strong>

</div>


<?php if($user_role == 'superadmin'){ ?>

<div class="batch-actions">


<?php if(
    $active_batch['application_status']
    == 'Open'
){ ?>

<a
    href="scholars.php?close_applications=<?= $active_batch['id'] ?>"
    class="btn-custom btn-warning-custom"
    onclick="return confirm('Close scholarship applications?')">

    <i class="fa-solid fa-lock"></i>

    Close Applications

</a>

<?php }else{ ?>

<a
    href="scholars.php?open_applications=<?= $active_batch['id'] ?>"
    class="btn-custom btn-success-custom"
    onclick="return confirm('Open scholarship applications for this batch?')">

    <i class="fa-solid fa-lock-open"></i>

    Open Applications

</a>

<?php } ?>


<a
    href="scholars.php?disburse_batch=<?= $active_batch['id'] ?>"
    class="btn-custom btn-success-custom"
    onclick="return confirm('Move approved scholarship applications to scholarship disbursement?')">

    <i class="fa-solid fa-money-bill-transfer"></i>

    Scholarship Disbursement

</a>


<a
    href="scholars.php?complete_batch=<?= $active_batch['id'] ?>"
    class="btn-custom btn-light-custom"
    onclick="return confirm('Are you sure you want to complete this scholarship batch? Applications will be closed.')">

    <i class="fa-solid fa-check"></i>

    Complete Batch

</a>

</div>

<?php } ?>


<?php }else{ ?>

<div class="batch-label">

    <i class="fa-solid fa-circle-info"></i>

    Program Status

</div>

<h3>
No Active Scholarship Batch
</h3>

<div class="batch-date">
There is currently no active scholarship batch.
</div>


<?php if($user_role == 'superadmin'){ ?>

<button
    class="btn-custom btn-light-custom"
    data-toggle="modal"
    data-target="#newBatchModal">

    <i class="fa-solid fa-plus"></i>

    Create A Batch

</button>

<?php } ?>

<?php } ?>

</div>


<!-- =====================================================
   APPLICATION RECORDS
===================================================== -->

<div class="panel">

<div class="panel-header">

<div>

<h4>

<i class="fa-solid fa-file-lines"></i>

Scholarship Applications

</h4>

<div class="panel-subtitle">
Only applications from the currently active scholarship batch are shown here.
</div>

</div>

</div>


<div class="table-tools">

<div class="search-box">

<input
    type="text"
    id="applicationSearch"
    placeholder="Search applications...">

</div>

<select
    id="applicationSort"
    class="sort-select">

    <option value="newest">
        Newest First
    </option>

    <option value="oldest">
        Oldest First
    </option>

    <option value="name">
        Name A-Z
    </option>

    <option value="name_desc">
        Name Z-A
    </option>

    <option value="status">
        Status
    </option>

</select>

<select
    id="applicationTableBarangayFilter"
    class="sort-select"
    onchange="applyTableFilters('applicationSearch','applicationTable')">

    <option value="">All Barangays</option>

    <?php
    mysqli_data_seek($barangays, 0);

    while($barangay = mysqli_fetch_assoc($barangays)){
    ?>

        <option value="<?= htmlspecialchars($barangay['name']) ?>">
            <?= htmlspecialchars($barangay['name']) ?>
        </option>

    <?php } ?>

</select>

</div>


<?php if($user_role != 'sk_member'){ ?>

<div class="bulk-actions">

<label class="select-all-label">

<input
    type="checkbox"
    id="selectAllApplications">

<span>
Select All Applications
</span>

</label>


<form
    method="POST"
    id="bulkDeleteApplicationsForm"
    style="display:inline;">

    <div id="selectedApplicationInputs"></div>

    <button
        type="submit"
        name="bulk_delete_applications"
        class="btn-custom btn-danger-custom"
        id="deleteSelectedApplicationsBtn"
        disabled
        onclick="return confirmDeleteSelectedApplications();">

        <i class="fa-solid fa-trash"></i>

        Delete Selected

    </button>

</form>

</div>

<?php } ?>


<div class="table-wrapper">

<table id="applicationTable">

<thead>

<tr>

<?php if($user_role != 'sk_member'){ ?>

<th class="checkbox-column"></th>

<?php } ?>

<th>ID</th>
<th>Full Name</th>
<th>Education</th>
<th>School</th>
<th>Course</th>
<th>Year</th>
<th>Barangay</th>
<th>Batch</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if(
    $applications &&
    mysqli_num_rows($applications) > 0
){ ?>

<?php while(
    $app = mysqli_fetch_assoc(
        $applications
    )
){ ?>

<tr>

<?php if($user_role != 'sk_member'){ ?>

<td class="checkbox-column">
    <input
        type="checkbox"
        class="application-checkbox"
        value="<?= htmlspecialchars($app['id']) ?>">
</td>

<?php } ?>

<td>
    <strong>
        <?= htmlspecialchars(generateApplicationNumber(
            $conn,
            $app['batch_id'],
            $app['batch_title'] ?? '',
            $app['id']
        )) ?>
    </strong>
</td>
<td>
<strong>
<?= htmlspecialchars(
    $app['fullname']
) ?>
</strong>
</td>

<td>
<?= htmlspecialchars(
    $app['education_level'] ?? ''
) ?>
</td>

<td>
<?= htmlspecialchars(
    $app['school']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $app['course']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $app['year_level']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $app['barangay_name']
    ?? 'Not Assigned'
) ?>
</td>

<td>
<?= htmlspecialchars(
    $app['batch_title']
    ?? 'No Batch'
) ?>
</td>

<td>

<?php

$date_value =
    $app['application_date']
    ?? null;

if($date_value){

    echo date(
        'M d, Y',
        strtotime($date_value)
    );

}else{

    echo '-';

}

?>

</td>


<td>

<?php if(
    $app['status'] == 'Pending'
){ ?>

<span class="status-badge status-pending">
Pending
</span>

<?php }elseif(
    $app['status'] == 'Approved'
){ ?>

<span class="status-badge status-approved">
Approved
</span>

<?php }elseif(
    $app['status'] == 'Rejected'
){ ?>

<span class="status-badge status-rejected">
Rejected
</span>

<?php }else{ ?>

<span class="status-badge">

<?= htmlspecialchars(
    $app['status']
) ?>

</span>

<?php } ?>

</td>


<td>

<div class="action-cell">

<?php if(!empty($app['id_image'])){ ?>

<a
    href="../<?= htmlspecialchars(ltrim($app['id_image'], '/'), ENT_QUOTES, 'UTF-8') ?>"
    class="btn-custom btn-light-custom"
    target="_blank"
    rel="noopener noreferrer">

    <i class="fa-solid fa-image"></i>

    View ID Image

</a>

<?php } ?>

<?php if($app['status'] == 'Pending'){ ?>

<?php if($user_role != 'sk_member'){ ?>

<a
    href="scholars.php?approve=<?= $app['id'] ?>"
    class="btn-custom btn-success-custom"
    onclick="return confirm('Approve this scholarship application?')">

    <i class="fa-solid fa-check"></i>

    Approve

</a>


<a
    href="scholars.php?reject=<?= $app['id'] ?>"
    class="btn-custom btn-danger-custom"
    onclick="return confirm('Reject this scholarship application?')">

    <i class="fa-solid fa-xmark"></i>

    Reject

</a>

<?php }else{ ?>

<span style="color:#63796e;font-size:11px;">
View Only
</span>

<?php } ?>


<?php }elseif($app['status'] == 'Approved'){ ?>




<?php }elseif($app['status'] == 'Rejected'){ ?>




<?php }else{ ?>


<?php } ?>

</div>

</td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>

<td
    colspan="<?= $user_role != 'sk_member' ? '12' : '11' ?>"
    style="
    text-align:center;
    padding:40px;
    color:#657b70;
    ">

<i
    class="fa-regular fa-folder-open"
    style="
    font-size:25px;
    display:block;
    margin-bottom:10px;
    "></i>

<?php if($active_batch){ ?>

No scholarship applications found for the current active batch.

<?php }else{ ?>

There is no active scholarship batch.

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>


<!-- =====================================================
   ADD SCHOLAR
===================================================== -->

<?php if($user_role != 'sk_member'){ ?>

<div class="panel">

<div class="panel-header">

<div>

<h4>

<i class="fa-solid fa-user-plus"></i>

Add Scholar Manually

</h4>

<div class="panel-subtitle">
Register a scholar directly into the active scholarship batch.
</div>

</div>

</div>


<?php if(!$active_batch){ ?>

<div class="program-note">

<i class="fa-solid fa-circle-info"></i>

There is currently no active scholarship batch.
Create a batch first before adding a scholar.

</div>

<br>

<?php } ?>


<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-6">

<div class="form-group">

<label>Full Name</label>

<input
    type="text"
    name="fullname"
    class="form-control"
    required>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>School</label>

<input
    type="text"
    name="school"
    class="form-control"
    required>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>Course</label>

<input
    type="text"
    name="course"
    class="form-control"
    required>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>Education Level</label>

<select
    name="education_level"
    id="manualEducation"
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

</div>


<div class="col-md-6">

<div class="form-group">

<label>Year Level</label>

<select
    name="year_level"
    id="manualYear"
    class="form-control"
    required
    disabled>

    <option value="">
        Select Education Level First
    </option>

</select>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>Scholar Image (JPG, PNG, or WEBP; max 5 MB)</label>

<input
    type="file"
    name="scholar_image"
    class="form-control"
    accept="image/jpeg,image/png,image/webp"
    required>

<small class="form-text text-muted">
    Scholar status will automatically be set to Active.
</small>

</div>

</div>


<?php if($user_role == 'superadmin'){ ?>

<div class="col-md-6">

<div class="form-group">

<label>Barangay</label>

<select
    name="barangay_id"
    class="form-control"
    required>

    <option value="">
        Select Barangay
    </option>

    <?php

    if($barangays){

        mysqli_data_seek(
            $barangays,
            0
        );

        while(
            $b = mysqli_fetch_assoc(
                $barangays
            )
        ){

    ?>

    <option value="<?= $b['id'] ?>">

        <?= htmlspecialchars(
            $b['name']
        ) ?>

    </option>

    <?php

        }

    }

    ?>

</select>

</div>

</div>

<?php } ?>


</div>


<button
    type="submit"
    name="add_scholar"
    class="btn-custom btn-primary-custom"
    <?= !$active_batch ? 'disabled' : '' ?>>

    <i class="fa-solid fa-plus"></i>

    Add Scholar

</button>

</form>

</div>

<?php } ?>


<!-- =====================================================
   SCHOLAR RECORDS
===================================================== -->

<div class="panel">

<div class="panel-header">

<div>

<h4>

<i class="fa-solid fa-graduation-cap"></i>

Scholar Records

</h4>

<div class="panel-subtitle">
View registered scholarship beneficiaries and their current status.
</div>

</div>

</div>


<div class="table-tools">

<div class="search-box">

<input
    type="text"
    id="scholarSearch"
    placeholder="Search scholar records...">

</div>

<select
    id="scholarSort"
    class="sort-select">

    <option value="newest">
        Newest First
    </option>

    <option value="oldest">
        Oldest First
    </option>

    <option value="name">
        Name A-Z
    </option>

    <option value="name_desc">
        Name Z-A
    </option>

    <option value="barangay">
        Barangay
    </option>

</select>

<select
    id="scholarTableBarangayFilter"
    class="sort-select"
    onchange="applyTableFilters('scholarSearch','scholarTable')">

    <option value="">All Barangays</option>

    <?php
    mysqli_data_seek($barangays, 0);

    while($barangay = mysqli_fetch_assoc($barangays)){
    ?>

        <option value="<?= htmlspecialchars($barangay['name']) ?>">
            <?= htmlspecialchars($barangay['name']) ?>
        </option>

    <?php } ?>

</select>

</div>


<?php if($user_role != 'sk_member'){ ?>

<div class="bulk-actions">

<label class="select-all-label">

<input
    type="checkbox"
    id="selectAllScholars">

<span>
Select All
</span>

</label>


<form
    method="POST"
    id="bulkDeleteForm"
    style="display:inline;">

    <div id="selectedScholarInputs"></div>

    <button
        type="submit"
        name="bulk_delete_scholars"
        class="btn-custom btn-danger-custom"
        id="deleteSelectedBtn"
        disabled
        onclick="return confirmDeleteSelected();">

        <i class="fa-solid fa-trash"></i>

        Delete Selected

    </button>

</form>


<form
    method="POST"
    style="display:inline;">

    <button
        type="submit"
        name="delete_all_scholars"
        class="btn-custom btn-danger-custom"
        onclick="return confirmDeleteAll();">

        <i class="fa-solid fa-trash-can"></i>

        Delete All

    </button>

</form>

</div>

<?php } ?>


<div class="table-wrapper">

<table id="scholarTable">

<thead>

<tr>

<?php if($user_role != 'sk_member'){ ?>

<th class="checkbox-column"></th>

<?php } ?>

<th>ID</th>
<th>Full Name</th>
<th>Education</th>
<th>School</th>
<th>Course</th>
<th>Year</th>
<th>Barangay</th>
<th>Batch</th>
<th>Approved Date</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if(
    $scholars &&
    mysqli_num_rows($scholars) > 0
){ ?>

<?php while(
    $scholar = mysqli_fetch_assoc(
        $scholars
    )
){ ?>

<tr>

<?php if($user_role != 'sk_member'){ ?>

<td class="checkbox-column">

<input
    type="checkbox"
    class="scholar-checkbox"
    value="<?= $scholar['id'] ?>">

</td>

<?php } ?>

<td>
<strong>
<?php

if(!empty($scholar['linked_application_id'])){

    echo htmlspecialchars(
        generateApplicationNumber(
            $conn,
            $scholar['batch_id'],
            $scholar['batch_title'] ?? '',
            $scholar['linked_application_id']
        )
    );

}else{

    echo 'MANUAL';

}

?>
</strong>
</td>

<td>

<strong>
<?= htmlspecialchars(
    $scholar['fullname']
) ?>
</strong>

</td>

<td>
<?= htmlspecialchars(
    $scholar['education_level'] ?? ''
) ?>
</td>

<td>
<?= htmlspecialchars(
    $scholar['school']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $scholar['course']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $scholar['year_level']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $scholar['barangay_name']
    ?? 'Not Assigned'
) ?>
</td>

<td>
<?= htmlspecialchars(
    $scholar['batch_title']
    ?? 'No Batch'
) ?>
</td>

<td>

<?php

$date_value =
    $scholar['approved_date']
    ?? null;

if($date_value){

    echo date(
        'M d, Y',
        strtotime($date_value)
    );

}else{

    echo '-';

}

?>

</td>


<td>

<?php if(
    $scholar['status'] == 'Active'
){ ?>

<span class="status-badge status-active">
Active
</span>

<?php }else{ ?>

<span class="status-badge">

<?= htmlspecialchars(
    $scholar['status']
) ?>

</span>

<?php } ?>

</td>


<td>

<div class="action-cell">

<?php if(!empty($scholar['image_path']) && is_file(dirname(__DIR__) . '/' . ltrim($scholar['image_path'], '/'))){ ?>

<button
    type="button"
    class="btn-custom btn-light-custom"
    data-toggle="modal"
    data-target="#scholarImageModal"
    data-image="../<?= htmlspecialchars(ltrim($scholar['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
    data-scholar="<?= htmlspecialchars($scholar['full_name'] ?? $scholar['name'] ?? 'Scholar', ENT_QUOTES, 'UTF-8') ?>">

    <i class="fa-solid fa-image"></i>

    View Image

</button>

<?php } ?>

<?php if(
    $user_role != 'sk_member'
){ ?>

<a
    href="scholars.php?delete=<?= $scholar['id'] ?>"
    class="btn-custom btn-danger-custom"
    onclick="return confirm('Are you sure you want to delete this scholar record?')">

    <i class="fa-solid fa-trash"></i>

    Delete

</a>

<?php }else{ ?>

<span style="color:#63796e;font-size:11px;">

View Only

</span>

<?php } ?>

</div>

</td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>

<td
    colspan="<?= $user_role != 'sk_member' ? '12' : '11' ?>"
    style="
    text-align:center;
    padding:40px;
    color:#657b70;
    ">

<i
    class="fa-solid fa-user-graduate"
    style="
    font-size:25px;
    display:block;
    margin-bottom:10px;
    "></i>

No scholar records found.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>


<!-- =====================================================
   ARCHIVED BATCHES
===================================================== -->

<div class="panel" id="archivedSection">

<div class="panel-header">

<div>

<h4>

<i class="fa-solid fa-box-archive"></i>

Archived Scholarship Batches

</h4>

<div class="panel-subtitle">
Completed scholarship programs and historical records.
</div>

</div>

</div>


<button
    class="archive-toggle"
    type="button"
    data-toggle="collapse"
    data-target="#archiveList"
    aria-expanded="false"
    aria-controls="archiveList">

    <i class="fa-solid fa-chevron-down" id="archiveChevron"></i>

    Show / Hide Archived Batches

</button>


<div
    class="collapse"
    id="archiveList">

<div class="archive-list">

<?php if(
    !empty($archive_records)
){ ?>

<?php foreach(
    $archive_records as $archive_id => $archive_data
){ ?>

<?php

$archive = $archive_data['batch'];

$archive_scholars =
    $archive_data['scholars'];

$archive_applications =
    $archive_data['applications'];

?>

<div class="archive-item">

<div class="archive-info">

<strong>

<i class="fa-solid fa-box-archive"></i>

<?= htmlspecialchars(
    $archive['title']
) ?>

</strong>


<small>

Started:

<?= date(
    'M d, Y',
    strtotime(
        $archive['start_date']
    )
) ?>


<?php if(
    !empty($archive['end_date'])
){ ?>

&nbsp; | &nbsp;

Completed:

<?= date(
    'M d, Y',
    strtotime(
        $archive['end_date']
    )
) ?>

<?php } ?>


<br>

Budget:

₱<?= number_format(
    $archive['budget'],
    2
) ?>

&nbsp; | &nbsp;

Slots:

<?= (int)$archive['slots'] ?>

&nbsp; | &nbsp;

Applications:

<?= count($archive_applications) ?>

&nbsp; | &nbsp;

Approved:

<?= count(
    array_filter(
        $archive_applications,
        function($item){
            return $item['status'] == 'Approved';
        }
    )
) ?>

</small>

</div>


<button
    type="button"
    class="btn-custom btn-secondary-custom archive-view-btn"
    data-target="archiveDetails<?= $archive_id ?>">

    <i class="fa-solid fa-eye"></i>

    View Batch

</button>


<div
    class="archive-details"
    id="archiveDetails<?= $archive_id ?>"
    style="display:none;">

<div class="archive-details-title">

<i class="fa-solid fa-file-lines"></i>

Scholarship Applications

</div>


<?php if(!empty($archive_applications)){ ?>

<div class="table-wrapper">

<table class="archive-mini-table">

<thead>

<tr>

<th>ID</th>
<th>Full Name</th>
<th>School</th>
<th>Course</th>
<th>Year</th>
<th>Barangay</th>
<th>Date</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach(
    $archive_applications as $archive_app
){ ?>

<tr>

<td>
<strong>
<?= htmlspecialchars(
    generateApplicationNumber(
        $conn,
        $archive_id,
        $archive['title'],
        $archive_app['id']
    )
) ?>
</strong>
</td>

<td>
<strong>
<?= htmlspecialchars(
    $archive_app['fullname']
) ?>
</strong>
</td>

<td>
<?= htmlspecialchars(
    $archive_app['school']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_app['course']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_app['year_level']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_app['barangay_name']
    ?? 'Not Assigned'
) ?>
</td>

<td>

<?php

$archive_date =
    $archive_app['application_date']
    ?? null;

echo $archive_date
    ? date(
        'M d, Y',
        strtotime($archive_date)
    )
    : '-';

?>

</td>

<td>

<?php if(
    $archive_app['status'] == 'Approved'
){ ?>

<span class="status-badge status-approved">
Approved
</span>

<?php }elseif(
    $archive_app['status'] == 'Rejected'
){ ?>

<span class="status-badge status-rejected">
Rejected
</span>

<?php }else{ ?>

<span class="status-badge status-pending">
Pending
</span>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php }else{ ?>

<div
    style="
    padding:18px;
    color:#657b70;
    text-align:center;
    ">

No applications found for this batch.

</div>

<?php } ?>


<br>


<div class="archive-details-title">

<i class="fa-solid fa-user-graduate"></i>

Scholar Records

</div>


<?php if(!empty($archive_scholars)){ ?>

<div class="table-wrapper">

<table class="archive-mini-table">

<thead>

<tr>

<th>ID</th>
<th>Full Name</th>
<th>Education</th>
<th>School</th>
<th>Course</th>
<th>Year</th>
<th>Barangay</th>
<th>Approved Date</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach(
    $archive_scholars as $archive_scholar
){ ?>

<tr>

<td>
<strong>
<?php

if(!empty($archive_scholar['linked_application_id'])){

    echo htmlspecialchars(
        generateApplicationNumber(
            $conn,
            $archive_id,
            $archive['title'],
            $archive_scholar['linked_application_id']
        )
    );

}else{

    echo 'MANUAL';

}

?>
</strong>
</td>

<td>

<strong>
<?= htmlspecialchars(
    $archive_scholar['fullname']
) ?>
</strong>

</td>

<td>
<?= htmlspecialchars(
    $archive_scholar['education_level']
    ?? ''
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_scholar['school']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_scholar['course']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_scholar['year_level']
) ?>
</td>

<td>
<?= htmlspecialchars(
    $archive_scholar['barangay_name']
    ?? 'Not Assigned'
) ?>
</td>

<td>

<?php

$archive_approved_date =
    $archive_scholar['approved_date']
    ?? null;

echo $archive_approved_date
    ? date(
        'M d, Y',
        strtotime(
            $archive_approved_date
        )
    )
    : '-';

?>

</td>

<td>

<?php if(
    $archive_scholar['status'] == 'Active'
){ ?>

<span class="status-badge status-active">
Active
</span>

<?php }else{ ?>

<span class="status-badge">
<?= htmlspecialchars(
    $archive_scholar['status']
) ?>
</span>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php }else{ ?>

<div
    style="
    padding:18px;
    color:#657b70;
    text-align:center;
    ">

No scholar records found for this batch.

</div>

<?php } ?>

</div>

</div>

<?php } ?>

<?php }else{ ?>

<div
    style="
    padding:20px;
    color:#657b70;
    text-align:center;
    ">

No completed batches yet.

</div>

<?php } ?>

</div>

</div>

</div>


<!-- =====================================================
   NEW BATCH MODAL
===================================================== -->

<?php if($user_role == 'superadmin'){ ?>

<div
    class="modal fade"
    id="newBatchModal"
    tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title">

<i class="fa-solid fa-layer-group"></i>

Create Scholarship Batch

</h5>

<button
    type="button"
    class="close"
    data-dismiss="modal">

    <span>&times;</span>

</button>

</div>


<form method="POST">

<div class="modal-body">


<div class="form-group">

<label>
Batch / Program Title
</label>

<input
    type="text"
    name="title"
    class="form-control"
    placeholder="Example: SK KONEXT Scholarship Program 2026"
    required>

</div>


<div class="row">

<div class="col-md-6">

<div class="form-group">

<label>
Start Date
</label>

<input
    type="date"
    name="start_date"
    class="form-control"
    required>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>
End Date
<span style="font-weight:400;color:#71877c;">
(Optional)
</span>
</label>

<input
    type="date"
    name="end_date"
    class="form-control">

</div>

</div>

</div>


<div class="row">

<div class="col-md-6">

<div class="form-group">

<label>
Scholarship Budget
</label>

<div class="input-group">

<div class="input-group-prepend">

<span class="input-group-text">
₱
</span>

</div>

<input
    type="number"
    name="budget"
    class="form-control"
    min="0"
    step="0.01"
    placeholder="0.00"
    required>

</div>

</div>

</div>


<div class="col-md-6">

<div class="form-group">

<label>
Number of Slots
</label>

<input
    type="number"
    name="slots"
    class="form-control"
    min="1"
    placeholder="Example: 50"
    required>

</div>

</div>

</div>


<div class="program-note">

<i class="fa-solid fa-circle-info"></i>

<strong>Application Status:</strong>

<br>

This new scholarship program will initially be created as
<strong>Active</strong> but
<strong>Applications Closed</strong>.

After confirming the program details and funding,
the Superadmin can click
<strong>Open Applications</strong>.

</div>


</div>


<div class="modal-footer">

<button
    type="button"
    class="btn-custom btn-secondary-custom"
    data-dismiss="modal">

    Cancel

</button>


<button
    type="submit"
    name="create_batch"
    class="btn-custom btn-primary-custom">

    <i class="fa-solid fa-plus"></i>

    Create Batch

</button>

</div>

</form>

</div>

</div>

</div>

<?php } ?>


<!-- =====================================================
   JAVASCRIPT LIBRARIES
===================================================== -->

<!-- Scholar image preview modal -->
<div class="modal fade" id="scholarImageModal" tabindex="-1" role="dialog" aria-labelledby="scholarImageModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scholarImageModalTitle"><i class="fa-solid fa-image"></i> Scholar Image</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="scholar-image-frame">
                    <img id="scholarImagePreview" src="" alt="Scholar image preview">
                    <div id="scholarImageUnavailable" style="display:none;text-align:center;color:#c5d4cb;font-size:13px;padding:20px;">
                        <i class="fa-solid fa-image-slash" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Image is unavailable.
                    </div>
                </div>
                <div id="scholarImageCaption" style="text-align:center;color:#9db5a7;font-size:12px;margin-top:10px;"></div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).on('show.bs.modal', '#scholarImageModal', function (event) {
    var button = $(event.relatedTarget);
    var imageUrl = button.data('image');
    var scholarName = button.data('scholar') || 'Scholar';
    var image = document.getElementById('scholarImagePreview');

    var unavailable = document.getElementById('scholarImageUnavailable');
    image.classList.remove('image-ready');
    image.style.display = 'none';
    unavailable.style.display = 'none';
    image.onerror = function () {
        this.classList.remove('image-ready');
        this.style.display = 'none';
        unavailable.style.display = 'block';
    };
    image.onload = function () {
        unavailable.style.display = 'none';
        this.style.display = 'block';
        requestAnimationFrame(function () { image.classList.add('image-ready'); });
    };
    image.src = '';
    image.alt = scholarName + ' image';
    document.getElementById('scholarImageCaption').textContent = scholarName;
    if (imageUrl) {
        image.src = imageUrl;
    } else {
        unavailable.style.display = 'block';
    }
});

$(document).on('shown.bs.modal', '#scholarImageModal', function () {
    var image = document.getElementById('scholarImagePreview');
    if (image.complete && image.naturalWidth > 0) {
        image.style.display = 'block';
        requestAnimationFrame(function () { image.classList.add('image-ready'); });
    }
});
</script>



<script>

/* =====================================================
   TABLE SEARCH
===================================================== */

/* =====================================================
   TABLE FILTER + PAGINATION
===================================================== */

function applyTableFilters(inputId, tableId, requestedPage = 1){

    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const barangayFilter = document.getElementById(tableId + 'BarangayFilter');

    if(!table) return;

    const search = input ? input.value.trim().toLowerCase() : '';

    const selectedBarangay = barangayFilter
        ? barangayFilter.value.trim().toLowerCase()
        : '';

    const rows = Array.from(table.querySelectorAll('tbody tr'));

    const filteredRows = rows.filter(function(row){

        const rowText = row.innerText.toLowerCase();

        const barangay = row.cells[6]
            ? row.cells[6].innerText.trim().toLowerCase()
            : '';

        return rowText.includes(search) &&
            (selectedBarangay === '' || barangay === selectedBarangay);

    });

    const rowsPerPage = 10;
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

    let currentPage = requestedPage;

    if(totalPages === 0){
        currentPage = 1;
    }else{
        currentPage = Math.max(1, Math.min(currentPage, totalPages));
    }

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    rows.forEach(function(row){
        row.style.display = 'none';
    });

    filteredRows.slice(start, end).forEach(function(row){
        row.style.display = '';
    });

    /* PAGINATION CONTAINER */

    let pagination = document.getElementById(tableId + 'Pagination');

    if(!pagination){

        pagination = document.createElement('div');
        pagination.id = tableId + 'Pagination';
        pagination.className = 'table-pagination';

        table.closest('.table-wrapper').insertAdjacentElement(
            'afterend',
            pagination
        );

    }

    if(filteredRows.length === 0){

        pagination.innerHTML = '<span>No records found.</span>';
        return;

    }

    let buttons = '';

    buttons += `
        <button type="button"
            ${currentPage === 1 ? 'disabled' : ''}
            data-page="${currentPage - 1}">
            Previous
        </button>
    `;

    for(let i = 1; i <= totalPages; i++){

        buttons += `
            <button type="button"
                class="${i === currentPage ? 'active' : ''}"
                data-page="${i}">
                ${i}
            </button>
        `;

    }

    buttons += `
        <button type="button"
            ${currentPage === totalPages ? 'disabled' : ''}
            data-page="${currentPage + 1}">
            Next
        </button>
    `;

    pagination.innerHTML = `
        <span>
            Showing ${start + 1}-${Math.min(end, filteredRows.length)}
            of ${filteredRows.length} records
        </span>
        <div class="pagination-buttons">
            ${buttons}
        </div>
    `;

    pagination.querySelectorAll('button[data-page]').forEach(function(button){

        button.addEventListener('click', function(){

            applyTableFilters(
                inputId,
                tableId,
                parseInt(this.dataset.page)
            );

        });

    });

}


function setupSearch(inputId, tableId){

    const input = document.getElementById(inputId);

    if(!input) return;

    input.addEventListener('keyup', function(){
        applyTableFilters(inputId, tableId, 1);
    });

}
    




setupSearch(
    'applicationSearch',
    'applicationTable'
);


setupSearch(
    'scholarSearch',
    'scholarTable'
);


/* =====================================================
   TABLE SORTING
===================================================== */

function setupSort(selectId, tableId){

    const select =
        document.getElementById(selectId);

    const table =
        document.getElementById(tableId);

    if(!select || !table){
        return;
    }

    select.addEventListener(
        'change',
        function(){

            const value = this.value;

            const tbody =
                table.querySelector(
                    'tbody'
                );

            const rows =
                Array.from(
                    tbody.querySelectorAll(
                        'tr'
                    )
                );


            rows.sort(function(a,b){

                const nameA =
                    a.cells[1]
                    ? a.cells[1]
                        .innerText
                        .trim()
                        .toLowerCase()
                    : '';

                const nameB =
                    b.cells[1]
                    ? b.cells[1]
                        .innerText
                        .trim()
                        .toLowerCase()
                    : '';


                const idA =
                    parseInt(
                        a.cells[0]
                        ? a.cells[0].innerText
                        : 0
                    ) || 0;


                const idB =
                    parseInt(
                        b.cells[0]
                        ? b.cells[0].innerText
                        : 0
                    ) || 0;


                const barangayA =
                    a.cells[6]
                    ? a.cells[6]
                        .innerText
                        .trim()
                        .toLowerCase()
                    : '';


                const barangayB =
                    b.cells[6]
                    ? b.cells[6]
                        .innerText
                        .trim()
                        .toLowerCase()
                    : '';


                if(value === 'newest'){
                    return idB - idA;
                }


                if(value === 'oldest'){
                    return idA - idB;
                }


                if(value === 'name'){
                    return nameA.localeCompare(
                        nameB
                    );
                }


                if(value === 'name_desc'){
                    return nameB.localeCompare(
                        nameA
                    );
                }


                if(value === 'barangay'){
                    return barangayA.localeCompare(
                        barangayB
                    );
                }


                if(value === 'status'){

                    const statusA =
                        a.cells[9]
                        ? a.cells[9]
                            .innerText
                            .trim()
                            .toLowerCase()
                        : '';


                    const statusB =
                        b.cells[9]
                        ? b.cells[9]
                            .innerText
                            .trim()
                            .toLowerCase()
                        : '';


                    return statusA.localeCompare(
                        statusB
                    );

                }


                return 0;

            });


            rows.forEach(function(row){

                tbody.appendChild(row);

            });

            applyTableFilters(
    tableId === 'applicationTable'
        ? 'applicationSearch'
        : 'scholarSearch',
    tableId,
    1
);

        }
    );

}


setupSort(
    'applicationSort',
    'applicationTable'
);


setupSort(
    'scholarSort',
    'scholarTable'
);

applyTableFilters(
    'applicationSearch',
    'applicationTable',
    1
);

applyTableFilters(
    'scholarSearch',
    'scholarTable',
    1
);

/* =====================================================
   BARANGAY FILTER
===================================================== */



/* =====================================================
   MANUAL SCHOLAR EDUCATION LEVEL
===================================================== */

const manualEducation =
    document.getElementById(
        'manualEducation'
    );

const manualYear =
    document.getElementById(
        'manualYear'
    );


if(manualEducation && manualYear){

    manualEducation.addEventListener(
        'change',
        function(){

            manualYear.innerHTML = '';


            if(
                this.value ===
                'Senior High School'
            ){

                manualYear.disabled = false;

                manualYear.innerHTML = `

                    <option value="">
                        Select Year Level
                    </option>

                    <option value="11">
                        Grade 11
                    </option>

                    <option value="12">
                        Grade 12
                    </option>

                `;

            }

            else if(
                this.value === 'College'
            ){

                manualYear.disabled = false;

                manualYear.innerHTML = `

                    <option value="">
                        Select Year Level
                    </option>

                    <option value="1">
                        1st Year
                    </option>

                    <option value="2">
                        2nd Year
                    </option>

                    <option value="3">
                        3rd Year
                    </option>

                    <option value="4">
                        4th Year
                    </option>

                `;

            }

            else{

                manualYear.disabled = true;

                manualYear.innerHTML = `

                    <option value="">
                        Select Education Level First
                    </option>

                `;

            }

        }
    );

}


/* =====================================================
   SCHOLAR BULK SELECTION
===================================================== */

const selectAllScholars =
    document.getElementById(
        'selectAllScholars'
    );

const deleteSelectedBtn =
    document.getElementById(
        'deleteSelectedBtn'
    );

const selectedScholarInputs =
    document.getElementById(
        'selectedScholarInputs'
    );


function getScholarCheckboxes(){

    return Array.from(
        document.querySelectorAll(
            '.scholar-checkbox'
        )
    );

}


function updateScholarSelection(){

    const checkboxes =
        getScholarCheckboxes();

    const checked =
        checkboxes.filter(
            checkbox => checkbox.checked
        );


    if(deleteSelectedBtn){

        deleteSelectedBtn.disabled =
            checked.length === 0;

    }


    if(selectAllScholars){

        selectAllScholars.checked =
            checkboxes.length > 0 &&
            checked.length === checkboxes.length;

        selectAllScholars.indeterminate =
            checked.length > 0 &&
            checked.length < checkboxes.length;

    }

}


if(selectAllScholars){

    selectAllScholars.addEventListener(
        'change',
        function(){

            const checkboxes =
                getScholarCheckboxes();

            checkboxes.forEach(
                checkbox => {

                    checkbox.checked =
                        this.checked;

                }
            );

            updateScholarSelection();

        }
    );

}


document.addEventListener(
    'change',
    function(event){

        if(
            event.target.classList.contains(
                'scholar-checkbox'
            )
        ){

            updateScholarSelection();

        }

    }
);


function confirmDeleteSelected(){

    const selected =
        getScholarCheckboxes()
        .filter(
            checkbox => checkbox.checked
        );


    if(selected.length === 0){

        alert(
            'Please select at least one scholar to delete.'
        );

        return false;

    }


    if(
        !confirm(
            'Are you sure you want to delete ' +
            selected.length +
            ' selected scholar record(s)? This action cannot be undone.'
        )
    ){

        return false;

    }


    if(selectedScholarInputs){

        selectedScholarInputs.innerHTML = '';


        selected.forEach(
            function(checkbox){

                const input =
                    document.createElement(
                        'input'
                    );

                input.type = 'hidden';

                input.name =
                    'scholar_ids[]';

                input.value =
                    checkbox.value;

                selectedScholarInputs.appendChild(
                    input
                );

            }
        );

    }


    return true;

}


function confirmDeleteAll(){

    return confirm(
        'WARNING: This will permanently delete ALL scholar records you are allowed to manage. This action cannot be undone. Are you sure you want to continue?'
    );

}


updateScholarSelection();


/* =====================================================
   APPLICATION BULK SELECTION
===================================================== */

const selectAllApplications =
    document.getElementById(
        'selectAllApplications'
    );

const deleteSelectedApplicationsBtn =
    document.getElementById(
        'deleteSelectedApplicationsBtn'
    );

const selectedApplicationInputs =
    document.getElementById(
        'selectedApplicationInputs'
    );


function getApplicationCheckboxes(){

    return Array.from(
        document.querySelectorAll(
            '.application-checkbox'
        )
    );

}


function updateApplicationSelection(){

    const checkboxes =
        getApplicationCheckboxes();

    const checked =
        checkboxes.filter(
            checkbox => checkbox.checked
        );


    if(deleteSelectedApplicationsBtn){

        deleteSelectedApplicationsBtn.disabled =
            checked.length === 0;

    }


    if(selectAllApplications){

        selectAllApplications.checked =
            checkboxes.length > 0 &&
            checked.length === checkboxes.length;

        selectAllApplications.indeterminate =
            checked.length > 0 &&
            checked.length < checkboxes.length;

    }

}


if(selectAllApplications){

    selectAllApplications.addEventListener(
        'change',
        function(){

            const checkboxes =
                getApplicationCheckboxes();

            checkboxes.forEach(
                checkbox => {

                    checkbox.checked =
                        this.checked;

                }
            );

            updateApplicationSelection();

        }
    );

}


document.addEventListener(
    'change',
    function(event){

        if(
            event.target.classList.contains(
                'application-checkbox'
            )
        ){

            updateApplicationSelection();

        }

    }
);


function confirmDeleteSelectedApplications(){

    const selected =
        getApplicationCheckboxes()
        .filter(
            checkbox => checkbox.checked
        );


    if(selected.length === 0){

        alert(
            'Please select at least one scholarship application to delete.'
        );

        return false;

    }


    if(
        !confirm(
            'Are you sure you want to permanently delete ' +
            selected.length +
            ' selected scholarship application(s)? This action cannot be undone.'
        )
    ){

        return false;

    }


    if(selectedApplicationInputs){

        selectedApplicationInputs.innerHTML = '';


        selected.forEach(
            function(checkbox){

                const input =
                    document.createElement(
                        'input'
                    );

                input.type = 'hidden';

                input.name =
                    'application_ids[]';

                input.value =
                    checkbox.value;

                selectedApplicationInputs.appendChild(
                    input
                );

            }
        );

    }


    return true;

}


updateApplicationSelection();


/* =====================================================
   ARCHIVED BATCH OPEN / CLOSE
===================================================== */

const archiveToggle =
    document.querySelector(
        '.archive-toggle'
    );

const archiveList =
    document.getElementById(
        'archiveList'
    );

const archiveChevron =
    document.getElementById(
        'archiveChevron'
    );


if(archiveList){

    $('#archiveList').on(
        'shown.bs.collapse',
        function(){

            if(archiveChevron){

                archiveChevron.classList.remove(
                    'fa-chevron-down'
                );

                archiveChevron.classList.add(
                    'fa-chevron-up'
                );

            }

        }
    );


    $('#archiveList').on(
        'hidden.bs.collapse',
        function(){

            if(archiveChevron){

                archiveChevron.classList.remove(
                    'fa-chevron-up'
                );

                archiveChevron.classList.add(
                    'fa-chevron-down'
                );

            }

        }
    );

}


/* =====================================================
   INDIVIDUAL ARCHIVED BATCH VIEW
===================================================== */

document.querySelectorAll(
    '.archive-view-btn'
).forEach(
    function(button){

        button.addEventListener(
            'click',
            function(){

                const targetId =
                    this.getAttribute(
                        'data-target'
                    );

                const target =
                    document.getElementById(
                        targetId
                    );

                if(!target){
                    return;
                }


                const isOpen =
                    target.style.display !== 'none';


                if(isOpen){

                    target.style.display =
                        'none';

                    this.innerHTML =
                        '<i class="fa-solid fa-eye"></i> View Batch';

                }else{

                    target.style.display =
                        'block';

                    this.innerHTML =
                        '<i class="fa-solid fa-eye-slash"></i> Close Batch';

                }

            }
        );

    }
);

</script>


</body>

</html>