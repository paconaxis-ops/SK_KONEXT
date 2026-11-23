<?php
session_start();
include("db.php");

/* =========================================================
   GET BARANGAY
========================================================= */

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($name == '') {
    die("Barangay not selected.");
}


/* =========================================================
   FIND BARANGAY
========================================================= */

$name_safe = mysqli_real_escape_string($conn, $name);

$barangay_query = mysqli_query($conn, "
    SELECT *
    FROM barangays
    WHERE TRIM(name) = TRIM('$name_safe')
    LIMIT 1
");

if (!$barangay_query) {
    die("Database error: " . mysqli_error($conn));
}

$barangay = mysqli_fetch_assoc($barangay_query);

if (!$barangay) {
    die("Barangay not found.");
}


/* =========================================================
   BARANGAY INFORMATION
========================================================= */

$barangay_id = (int)$barangay['id'];
$barangay_name = $barangay['name'];


/* =========================================================
   USER ROLE
========================================================= */

$role = isset($_SESSION['role'])
    ? $_SESSION['role']
    : 'guest';

$is_admin = ($role === 'admin');


/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

$upload_folder = "Superadmin/Uploads/";

if (!is_dir($upload_folder)) {
    @mkdir($upload_folder, 0777, true);
}


/* =========================================================
   ADD ANNOUNCEMENT - ADMIN ONLY
========================================================= */

if (isset($_POST['add_post']) && $is_admin) {

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['post_title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['post_description']
    );

    $image_name = "";

    if (
        isset($_FILES['post_image']) &&
        $_FILES['post_image']['error'] == 0
    ) {

        $original = basename(
            $_FILES['post_image']['name']
        );

        $extension = strtolower(
            pathinfo(
                $original,
                PATHINFO_EXTENSION
            )
        );

        $allowed_images = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];

        if (in_array($extension, $allowed_images)) {

            $image_name =
                time() .
                "_image_" .
                uniqid() .
                "." .
                $extension;

            move_uploaded_file(
                $_FILES['post_image']['tmp_name'],
                $upload_folder . $image_name
            );
        }
    }

    $insert_post = mysqli_query($conn, "
        INSERT INTO barangay_posts
        (
            barangay_id,
            title,
            description,
            image
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$image_name'
        )
    ");

    if (!$insert_post) {
        die("Unable to create announcement: " . mysqli_error($conn));
    }

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name) .
        "&success=announcement"
    );

    exit;
}


/* =========================================================
   ADD PROJECT - ADMIN ONLY
========================================================= */

if (isset($_POST['add_project']) && $is_admin) {

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['project_title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['project_description']
    );

    $status = mysqli_real_escape_string(
        $conn,
        $_POST['project_status']
    );

    $insert_project = mysqli_query($conn, "
        INSERT INTO barangay_projects
        (
            barangay_id,
            title,
            description,
            status
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$status'
        )
    ");

    if (!$insert_project) {
        die("Unable to create project: " . mysqli_error($conn));
    }

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name) .
        "&success=project"
    );

    exit;
}


/* =========================================================
   ADD EVENT - ADMIN ONLY
========================================================= */

if (isset($_POST['add_event']) && $is_admin) {

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['event_title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['event_description']
    );

    $event_date = mysqli_real_escape_string(
        $conn,
        $_POST['event_date']
    );

    $location = mysqli_real_escape_string(
        $conn,
        $_POST['event_location']
    );

    $insert_event = mysqli_query($conn, "
        INSERT INTO barangay_events
        (
            barangay_id,
            title,
            description,
            event_date,
            location
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$event_date',
            '$location'
        )
    ");

    if (!$insert_event) {
        die("Unable to create event: " . mysqli_error($conn));
    }

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name) .
        "&success=event"
    );

    exit;
}


/* =========================================================
   DELETE ANNOUNCEMENT
========================================================= */

if (isset($_GET['delete_post']) && $is_admin) {

    $id = intval($_GET['delete_post']);

    $query = mysqli_query($conn, "
        SELECT image
        FROM barangay_posts
        WHERE id='$id'
        AND barangay_id='$barangay_id'
        LIMIT 1
    ");

    $post = mysqli_fetch_assoc($query);

    if ($post && !empty($post['image'])) {

        $possible_paths = [
            "Superadmin/Uploads/" . $post['image'],
            "uploads/announcements/" . $post['image']
        ];

        foreach ($possible_paths as $path) {

            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    mysqli_query($conn, "
        DELETE FROM barangay_posts
        WHERE id='$id'
        AND barangay_id='$barangay_id'
    ");

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name)
    );

    exit;
}


/* =========================================================
   DELETE PROJECT
========================================================= */

if (isset($_GET['delete_project']) && $is_admin) {

    $id = intval($_GET['delete_project']);

    mysqli_query($conn, "
        DELETE FROM barangay_projects
        WHERE id='$id'
        AND barangay_id='$barangay_id'
    ");

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name)
    );

    exit;
}


/* =========================================================
   DELETE EVENT
========================================================= */

if (isset($_GET['delete_event']) && $is_admin) {

    $id = intval($_GET['delete_event']);

    mysqli_query($conn, "
        DELETE FROM barangay_events
        WHERE id='$id'
        AND barangay_id='$barangay_id'
    ");

    header(
        "Location: barangay_view.php?name=" .
        urlencode($barangay_name)
    );

    exit;
}


/* =========================================================
   LOAD ANNOUNCEMENTS
========================================================= */

$posts = mysqli_query($conn, "
    SELECT *
    FROM barangay_posts
    WHERE barangay_id='$barangay_id'
    ORDER BY id DESC
");

if (!$posts) {
    die("Unable to load announcements: " . mysqli_error($conn));
}


/* =========================================================
   LOAD PROJECTS
========================================================= */

$projects = mysqli_query($conn, "
    SELECT *
    FROM barangay_projects
    WHERE barangay_id='$barangay_id'
    ORDER BY id DESC
");

if (!$projects) {
    die("Unable to load projects: " . mysqli_error($conn));
}


/* =========================================================
   LOAD EVENTS
========================================================= */

$events = mysqli_query($conn, "
    SELECT *
    FROM barangay_events
    WHERE barangay_id='$barangay_id'
    ORDER BY event_date ASC, id ASC
");

if (!$events) {
    die("Unable to load events: " . mysqli_error($conn));
}


/* =========================================================
   COUNTS
========================================================= */

$post_count = mysqli_num_rows($posts);
$project_count = mysqli_num_rows($projects);
$event_count = mysqli_num_rows($events);


/* =========================================================
   COMPLETED PROJECTS
========================================================= */

$completed_query = mysqli_query($conn, "
    SELECT id
    FROM barangay_projects
    WHERE barangay_id='$barangay_id'
    AND status='Completed'
");

$completed_count = $completed_query
    ? mysqli_num_rows($completed_query)
    : 0;


/* =========================================================
   SUCCESS
========================================================= */

$success = isset($_GET['success'])
    ? $_GET['success']
    : '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
<?= htmlspecialchars($barangay_name) ?> | SK KONEXT
</title>


<!-- BOOTSTRAP -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- GOOGLE FONT -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   ROOT
========================================================= */

:root{

    --bg:#06100b;
    --bg2:#091810;

    --green:#4ade80;
    --green-dark:#16a34a;
    --green-soft:#86efac;

    --text:#f4fff7;
    --muted:#8ea399;

    --glass:rgba(12,30,20,.72);
    --glass-light:rgba(255,255,255,.045);

    --border:rgba(255,255,255,.09);

    --danger:#fb7185;

}


/* =========================================================
   RESET
========================================================= */

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{

    margin:0;

    font-family:'Inter',sans-serif;

    color:var(--text);

    background:
        radial-gradient(
            circle at 10% 5%,
            rgba(34,197,94,.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 20%,
            rgba(74,222,128,.08),
            transparent 28%
        ),
        linear-gradient(
            135deg,
            #06100b,
            #09150f 55%,
            #06100b
        );

    min-height:100vh;

}


/* =========================================================
   BACKGROUND EFFECTS
========================================================= */

.page-glow{

    position:fixed;

    width:420px;
    height:420px;

    border-radius:50%;

    background:
        rgba(34,197,94,.06);

    filter:blur(90px);

    pointer-events:none;

    z-index:0;

}

.glow-one{

    top:-170px;
    left:-150px;

}

.glow-two{

    right:-180px;
    bottom:-170px;

}


/* =========================================================
   TOP HEADER
========================================================= */

.top-header{

    position:relative;

    min-height:400px;

    overflow:hidden;

    border-bottom:1px solid rgba(255,255,255,.06);

}


.top-header:before{

    content:"";

    position:absolute;

    inset:0;

    background:
        linear-gradient(
            135deg,
            rgba(74,222,128,.09),
            transparent 40%
        );

    pointer-events:none;

}


.header-grid{

    position:absolute;

    inset:0;

    opacity:.06;

    background-image:
        linear-gradient(
            rgba(255,255,255,.7) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.7) 1px,
            transparent 1px
        );

    background-size:55px 55px;

}


/* =========================================================
   HEADER NAV
========================================================= */

.header-content{

    position:relative;

    z-index:5;

    max-width:1250px;

    margin:auto;

    padding:24px 25px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

}


.brand{

    display:flex;

    align-items:center;

    gap:12px;

}


.brand-logo{

    width:48px;
    height:48px;

    border-radius:15px;

    display:flex;

    align-items:center;

    justify-content:center;

    color:var(--green);

    background:
        linear-gradient(
            145deg,
            rgba(74,222,128,.17),
            rgba(255,255,255,.04)
        );

    border:1px solid rgba(74,222,128,.2);

    box-shadow:
        0 0 25px rgba(74,222,128,.08);

    font-size:19px;

}


.brand-text strong{

    display:block;

    color:#f3fff7;

    font-size:18px;

    font-weight:900;

    letter-spacing:.5px;

}


.brand-text span{

    display:block;

    color:#819589;

    font-size:9px;

    margin-top:3px;

    letter-spacing:.4px;

}


.header-actions{

    display:flex;

    align-items:center;

    gap:9px;

}


.header-btn{

    display:inline-flex;

    align-items:center;

    gap:7px;

    color:#d8e9dd;

    text-decoration:none;

    padding:10px 14px;

    border-radius:12px;

    background:
        rgba(255,255,255,.035);

    border:1px solid var(--border);

    backdrop-filter:blur(12px);

    font-size:10px;

    font-weight:800;

    transition:.25s;

}


.header-btn:hover{

    color:#fff;

    text-decoration:none;

    background:
        rgba(74,222,128,.10);

    border-color:
        rgba(74,222,128,.25);

    transform:translateY(-2px);

}


.admin-badge{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:9px 12px;

    border-radius:30px;

    color:#05250f;

    background:var(--green);

    font-size:9px;

    font-weight:900;

    box-shadow:
        0 0 25px rgba(74,222,128,.16);

}


/* =========================================================
   HERO
========================================================= */

.hero-area{

    position:relative;

    z-index:5;

    max-width:1250px;

    margin:35px auto 0;

    padding:0 25px;

}


.hero-box{

    position:relative;

    overflow:hidden;

    min-height:225px;

    border-radius:28px;

    padding:40px;

    background:
        linear-gradient(
            135deg,
            rgba(17,43,27,.88),
            rgba(9,27,17,.72)
        );

    border:1px solid rgba(255,255,255,.09);

    box-shadow:
        0 30px 80px rgba(0,0,0,.25);

    backdrop-filter:blur(18px);

}


.hero-box:before{

    content:"";

    position:absolute;

    width:360px;
    height:360px;

    right:-120px;
    top:-170px;

    border-radius:50%;

    background:
        radial-gradient(
            circle,
            rgba(74,222,128,.17),
            transparent 65%
        );

}


.hero-box:after{

    content:"";

    position:absolute;

    width:1px;

    height:100%;

    left:48%;

    top:0;

    background:
        linear-gradient(
            transparent,
            rgba(74,222,128,.15),
            transparent
        );

}


.hero-content{

    position:relative;

    z-index:3;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:30px;

}


.hero-left{

    max-width:800px;

}


.hero-label{

    display:inline-flex;

    align-items:center;

    gap:7px;

    color:var(--green);

    background:
        rgba(74,222,128,.08);

    border:1px solid rgba(74,222,128,.15);

    padding:7px 11px;

    border-radius:30px;

    font-size:9px;

    font-weight:900;

    text-transform:uppercase;

    letter-spacing:1px;

    margin-bottom:15px;

}


.hero-title{

    margin:0;

    color:#f5fff8;

    font-size:39px;

    line-height:1.12;

    font-weight:900;

    letter-spacing:-1.2px;

}


.hero-title span{

    display:block;

    color:var(--green);

}


.hero-description{

    max-width:700px;

    margin:14px 0 0;

    color:#92a69a;

    font-size:12px;

    line-height:1.8;

}


.hero-icon{

    position:relative;

    flex-shrink:0;

    width:120px;
    height:120px;

    display:flex;

    justify-content:center;

    align-items:center;

    border-radius:32px;

    color:var(--green);

    background:
        linear-gradient(
            145deg,
            rgba(74,222,128,.13),
            rgba(255,255,255,.025)
        );

    border:1px solid rgba(74,222,128,.17);

    box-shadow:
        0 0 45px rgba(74,222,128,.06);

    font-size:45px;

}


/* =========================================================
   MAIN
========================================================= */

.main-container{

    position:relative;

    z-index:2;

    max-width:1250px;

    margin:25px auto 0;

    padding:0 25px 55px;

}


/* =========================================================
   QUICK NAV
========================================================= */

.quick-nav{

    display:flex;

    gap:9px;

    margin-bottom:20px;

    overflow-x:auto;

    padding:2px 0 7px;

}


.quick-nav::-webkit-scrollbar{

    height:3px;

}

.quick-nav::-webkit-scrollbar-thumb{

    background:rgba(74,222,128,.3);

    border-radius:20px;

}


.quick-nav a{

    flex-shrink:0;

    display:inline-flex;

    align-items:center;

    gap:7px;

    color:#8fa499;

    text-decoration:none;

    padding:10px 14px;

    border-radius:12px;

    background:
        rgba(255,255,255,.025);

    border:1px solid var(--border);

    font-size:10px;

    font-weight:800;

    transition:.25s;

}


.quick-nav a i{

    color:var(--green);

}


.quick-nav a:hover{

    color:#fff;

    background:
        rgba(74,222,128,.07);

    border-color:
        rgba(74,222,128,.22);

    transform:translateY(-2px);

}


/* =========================================================
   STATS
========================================================= */

.stats-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:13px;

    margin-bottom:22px;

}


.stat-card{

    position:relative;

    overflow:hidden;

    padding:20px;

    border-radius:19px;

    background:
        linear-gradient(
            145deg,
            rgba(18,39,27,.76),
            rgba(10,25,16,.65)
        );

    border:1px solid var(--border);

    backdrop-filter:blur(15px);

    transition:.3s;

}


.stat-card:after{

    content:"";

    position:absolute;

    width:110px;
    height:110px;

    right:-55px;
    bottom:-60px;

    border-radius:50%;

    background:
        rgba(74,222,128,.07);

}


.stat-card:hover{

    transform:translateY(-4px);

    border-color:
        rgba(74,222,128,.2);

    box-shadow:
        0 15px 40px rgba(0,0,0,.18);

}


.stat-top{

    display:flex;

    justify-content:space-between;

    align-items:center;

}


.stat-icon{

    width:42px;
    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:13px;

    color:var(--green);

    background:
        rgba(74,222,128,.09);

    border:1px solid rgba(74,222,128,.1);

    font-size:16px;

}


.stat-arrow{

    color:#4c6455;

    font-size:10px;

}


.stat-number{

    margin-top:17px;

    color:#f2fff5;

    font-size:28px;

    font-weight:900;

}


.stat-label{

    margin-top:2px;

    color:#788d80;

    font-size:10px;

}


/* =========================================================
   ALERT
========================================================= */

.alert{

    border-radius:14px;

    border:1px solid rgba(74,222,128,.18);

    color:#caffd8;

    background:
        rgba(22,163,74,.10);

    font-size:11px;

    box-shadow:none;

}


.alert i{

    margin-right:7px;

    color:var(--green);

}


/* =========================================================
   SECTION
========================================================= */

.section{

    position:relative;

    overflow:hidden;

    padding:24px;

    margin-bottom:20px;

    border-radius:23px;

    background:
        linear-gradient(
            145deg,
            rgba(14,32,21,.82),
            rgba(8,22,14,.68)
        );

    border:1px solid var(--border);

    box-shadow:
        0 15px 50px rgba(0,0,0,.13);

    backdrop-filter:blur(17px);

}


.section:before{

    content:"";

    position:absolute;

    width:160px;
    height:160px;

    border-radius:50%;

    top:-100px;
    right:-70px;

    background:
        rgba(74,222,128,.045);

}


.section-header{

    position:relative;

    z-index:2;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    margin-bottom:20px;

}


.section-title{

    display:flex;

    align-items:center;

    gap:12px;

}


.section-icon{

    width:42px;
    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    flex-shrink:0;

    border-radius:13px;

    color:var(--green);

    background:
        rgba(74,222,128,.08);

    border:1px solid rgba(74,222,128,.12);

    font-size:16px;

}


.section-heading h3{

    margin:0;

    color:#edf9f1;

    font-size:17px;

    font-weight:900;

}


.section-heading p{

    margin:4px 0 0;

    color:#718579;

    font-size:9px;

}


/* =========================================================
   ADMIN BUTTON
========================================================= */

.admin-add-btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    border:0;

    color:#03150a;

    background:
        linear-gradient(
            135deg,
            #4ade80,
            #22c55e
        );

    padding:10px 14px;

    border-radius:11px;

    font-size:9px;

    font-weight:900;

    cursor:pointer;

    box-shadow:
        0 8px 25px rgba(34,197,94,.13);

    transition:.25s;

}


.admin-add-btn:hover{

    color:#03150a;

    transform:translateY(-2px);

    box-shadow:
        0 12px 30px rgba(34,197,94,.2);

}


/* =========================================================
   ANNOUNCEMENTS
========================================================= */

.announcement-card{

    overflow:hidden;

    margin-bottom:14px;

    border-radius:18px;

    background:
        rgba(255,255,255,.025);

    border:1px solid rgba(255,255,255,.07);

    transition:.3s;

}


.announcement-card:hover{

    transform:translateY(-3px);

    border-color:
        rgba(74,222,128,.17);

    box-shadow:
        0 15px 35px rgba(0,0,0,.15);

}


.announcement-image-wrapper{

    position:relative;

    background:#0b1810;

}


.announcement-image{

    width:100%;

    max-height:380px;

    object-fit:cover;

    display:block;

    opacity:.92;

}


.image-label{

    position:absolute;

    left:15px;
    bottom:15px;

    padding:7px 10px;

    border-radius:9px;

    color:#dffff0;

    background:
        rgba(4,20,10,.78);

    border:1px solid rgba(255,255,255,.1);

    backdrop-filter:blur(10px);

    font-size:8px;

    font-weight:800;

}


.image-label i{

    color:var(--green);

    margin-right:4px;

}


.announcement-body{

    padding:20px;

}


.announcement-meta{

    display:flex;

    align-items:center;

    gap:6px;

    margin-bottom:8px;

    color:#718579;

    font-size:9px;

}


.announcement-meta i{

    color:var(--green);

}


.announcement-title{

    color:#effff4;

    font-size:17px;

    font-weight:900;

    margin-bottom:9px;

}


.announcement-description{

    color:#8da195;

    font-size:11px;

    line-height:1.8;

}


/* =========================================================
   RECORD GRID
========================================================= */

.record-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:13px;

}


.record-card{

    position:relative;

    overflow:hidden;

    padding:20px;

    border-radius:17px;

    background:
        rgba(255,255,255,.025);

    border:1px solid rgba(255,255,255,.07);

    transition:.3s;

}


.record-card:before{

    content:"";

    position:absolute;

    left:0;
    top:18px;
    bottom:18px;

    width:3px;

    border-radius:20px;

    background:
        linear-gradient(
            #4ade80,
            #16a34a
        );

}


.record-card:hover{

    transform:translateY(-3px);

    border-color:
        rgba(74,222,128,.17);

    box-shadow:
        0 15px 35px rgba(0,0,0,.15);

}


.record-top{

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:12px;

    padding-left:7px;

}


.record-title{

    color:#ecfff2;

    font-size:14px;

    font-weight:900;

}


.record-description{

    color:#879b8e;

    font-size:10px;

    line-height:1.75;

    margin:11px 0;

    padding-left:7px;

}


.status{

    flex-shrink:0;

    padding:6px 9px;

    border-radius:30px;

    font-size:7px;

    font-weight:900;

    text-transform:uppercase;

    letter-spacing:.4px;

}


.status-completed{

    color:#86efac;

    background:
        rgba(34,197,94,.12);

    border:1px solid rgba(34,197,94,.16);

}


.status-ongoing{

    color:#fde68a;

    background:
        rgba(234,179,8,.10);

    border:1px solid rgba(234,179,8,.14);

}


.status-upcoming{

    color:#93c5fd;

    background:
        rgba(59,130,246,.10);

    border:1px solid rgba(59,130,246,.14);

}


.record-date{

    padding-left:7px;

    color:#657a6d;

    font-size:8px;

}


.record-date i{

    color:var(--green);

    margin-right:3px;

}


/* =========================================================
   EVENTS
========================================================= */

.event-card{

    position:relative;

    display:flex;

    align-items:center;

    gap:17px;

    padding:16px;

    margin-bottom:11px;

    border-radius:17px;

    background:
        rgba(255,255,255,.025);

    border:1px solid rgba(255,255,255,.07);

    transition:.3s;

}


.event-card:hover{

    transform:translateX(4px);

    border-color:
        rgba(74,222,128,.17);

}


.event-date-box{

    width:67px;
    height:67px;

    flex-shrink:0;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    border-radius:17px;

    color:var(--green);

    background:
        linear-gradient(
            145deg,
            rgba(74,222,128,.12),
            rgba(34,197,94,.045)
        );

    border:1px solid rgba(74,222,128,.13);

}


.event-day{

    font-size:22px;

    line-height:1;

    font-weight:900;

}


.event-month{

    margin-top:4px;

    color:#789184;

    font-size:8px;

    text-transform:uppercase;

    font-weight:900;

}


.event-info{

    min-width:0;

    flex:1;

}


.event-info h5{

    margin:0 0 6px;

    color:#edfff2;

    font-size:14px;

    font-weight:900;

}


.event-info p{

    color:#84988b;

    font-size:10px;

    line-height:1.7;

    margin:0 0 7px;

}


.event-location{

    display:flex;

    align-items:center;

    flex-wrap:wrap;

    gap:5px;

    color:#687d70;

    font-size:8px;

}


.event-location i{

    color:var(--green);

}


/* =========================================================
   DELETE BUTTON
========================================================= */

.delete-btn{

    margin-top:13px;

}


.btn-outline-danger{

    color:#fb7185;

    background:rgba(251,113,133,.05);

    border-color:rgba(251,113,133,.18);

    border-radius:9px;

    font-size:9px;

}


.btn-outline-danger:hover{

    color:#fff;

    background:#e11d48;

    border-color:#e11d48;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state{

    text-align:center;

    padding:45px 20px;

}


.empty-icon{

    width:64px;
    height:64px;

    margin:0 auto 14px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:20px;

    color:#60766a;

    background:
        rgba(255,255,255,.035);

    border:1px solid rgba(255,255,255,.06);

    font-size:24px;

}


.empty-state h5{

    margin:0 0 5px;

    color:#91a598;

    font-size:13px;

    font-weight:800;

}


.empty-state p{

    margin:0;

    color:#566b5e;

    font-size:9px;

}


/* =========================================================
   MODALS
========================================================= */

.modal-content{

    overflow:hidden;

    border:1px solid rgba(74,222,128,.12);

    border-radius:20px;

    background:
        #0b1b11;

    color:#eaffef;

    box-shadow:
        0 30px 80px rgba(0,0,0,.45);

}


.modal-header{

    padding:20px 22px;

    background:
        rgba(74,222,128,.045);

    border-bottom:1px solid rgba(255,255,255,.07);

}


.modal-title{

    color:#effff3;

    font-size:16px;

    font-weight:900;

}


.modal-title i{

    color:var(--green);

}


.modal-header .close{

    color:#9bb0a2;

    opacity:1;

    text-shadow:none;

}


.modal-body{

    padding:23px;

}


.modal-footer{

    padding:15px 22px;

    background:
        rgba(255,255,255,.02);

    border-top:1px solid rgba(255,255,255,.06);

}


.form-label{

    display:block;

    color:#9db1a4;

    font-size:10px;

    font-weight:800;

    margin-bottom:6px;

}


.form-control{

    color:#ecfff2;

    background:
        rgba(255,255,255,.035);

    border:1px solid rgba(255,255,255,.09);

    border-radius:11px;

    font-size:11px;

    padding:10px 12px;

}


.form-control::placeholder{

    color:#52685b;

}


.form-control:focus{

    color:#fff;

    background:
        rgba(255,255,255,.045);

    border-color:
        rgba(74,222,128,.4);

    box-shadow:
        0 0 0 3px rgba(74,222,128,.08);

}


select.form-control option{

    color:#111;

    background:#fff;

}


/* =========================================================
   FOOTER
========================================================= */

.footer{

    padding:12px 0 30px;

    text-align:center;

    color:#52685b;

    font-size:9px;

}


.footer i{

    color:var(--green);

    margin-right:4px;

}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes fadeUp{

    from{

        opacity:0;

        transform:translateY(15px);

    }

    to{

        opacity:1;

        transform:translateY(0);

    }

}


.hero-box{

    animation:fadeUp .5s ease both;

}


.stat-card{

    animation:fadeUp .5s ease both;

}


.section{

    animation:fadeUp .6s ease both;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:900px){

    .stats-grid{

        grid-template-columns:
            repeat(2,1fr);

    }

    .record-grid{

        grid-template-columns:1fr;

    }

}


@media(max-width:600px){

    .top-header{

        min-height:370px;

    }


    .header-content{

        padding:17px 13px;

    }


    .brand-logo{

        width:42px;
        height:42px;

        border-radius:13px;

    }


    .brand-text strong{

        font-size:15px;

    }


    .brand-text span{

        font-size:8px;

    }


    .header-actions{

        gap:5px;

    }


    .admin-badge{

        font-size:7px;

        padding:7px 8px;

    }


    .header-btn{

        font-size:8px;

        padding:8px 9px;

    }


    .header-btn i{

        margin:0;

    }


    .header-btn{

        font-size:0;

    }


    .header-btn i{

        font-size:11px;

    }


    .hero-area{

        margin-top:30px;

        padding:0 12px;

    }


    .hero-box{

        padding:26px;

        border-radius:22px;

        min-height:210px;

    }


    .hero-title{

        font-size:28px;

    }


    .hero-description{

        font-size:10px;

    }


    .hero-icon{

        display:none;

    }


    .hero-box:after{

        display:none;

    }


    .main-container{

        padding:0 12px 35px;

    }


    .stats-grid{

        gap:9px;

    }


    .stat-card{

        padding:15px;

        border-radius:16px;

    }


    .stat-icon{

        width:36px;
        height:36px;

        font-size:13px;

    }


    .stat-number{

        font-size:23px;

    }


    .stat-label{

        font-size:8px;

    }


    .section{

        padding:17px;

        border-radius:18px;

    }


    .section-header{

        align-items:flex-start;

    }


    .section-heading h3{

        font-size:14px;

    }


    .section-heading p{

        font-size:8px;

    }


    .admin-add-btn{

        font-size:8px;

        padding:8px 9px;

    }


    .admin-add-btn i{

        margin:0;

    }


    .event-card{

        align-items:flex-start;

    }


    .event-date-box{

        width:55px;
        height:55px;

        border-radius:14px;

    }


    .event-day{

        font-size:18px;

    }


    .event-month{

        font-size:7px;

    }


    .event-info h5{

        font-size:12px;

    }


    .event-info p{

        font-size:9px;

    }

}

</style>

</head>


<body>


<div class="page-glow glow-one"></div>
<div class="page-glow glow-two"></div>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="top-header">

    <div class="header-grid"></div>


    <!-- NAVIGATION -->

    <div class="header-content">


        <div class="brand">

            <div class="brand-logo">

                <i class="fa-solid fa-landmark"></i>

            </div>


            <div class="brand-text">

                <strong>SK KONEXT</strong>

                <span>Barangay Information Portal</span>

            </div>

        </div>


        <div class="header-actions">


            <?php if ($is_admin) { ?>

                <span class="admin-badge">

                    <i class="fa-solid fa-shield-halved"></i>

                    ADMIN

                </span>

            <?php } ?>


            <a
                href="map.php"
                class="header-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>Back</span>

            </a>


            <a
                href="barangay.php"
                class="header-btn"
            >

                <i class="fa-solid fa-map"></i>

                <span>Directory</span>

            </a>


        </div>

    </div>


    <!-- HERO -->

    <div class="hero-area">

        <div class="hero-box">

            <div class="hero-content">


                <div class="hero-left">


                    <div class="hero-label">

                        <i class="fa-solid fa-location-dot"></i>

                        Barangay Community Portal

                    </div>


                    <h1 class="hero-title">

                        Welcome to

                        <span>
                            <?= htmlspecialchars($barangay_name) ?>
                        </span>

                    </h1>


                    <p class="hero-description">

                        Stay connected with your barangay.
                        Explore announcements, community projects,
                        activities, and upcoming events in one place.

                    </p>


                </div>


                <div class="hero-icon">

                    <i class="fa-solid fa-building-columns"></i>

                </div>


            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main-container">


<!-- =========================================================
     QUICK NAVIGATION
========================================================= -->

<nav class="quick-nav">

    <a href="#announcements">

        <i class="fa-solid fa-bullhorn"></i>

        Announcements

    </a>


    <a href="#projects">

        <i class="fa-solid fa-folder-open"></i>

        Projects

    </a>


    <a href="#events">

        <i class="fa-solid fa-calendar-days"></i>

        Events

    </a>

</nav>


<!-- =========================================================
     STATISTICS
========================================================= -->

<section class="stats-grid">


    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-icon">

                <i class="fa-solid fa-bullhorn"></i>

            </div>

            <div class="stat-arrow">

                <i class="fa-solid fa-arrow-up-right"></i>

            </div>

        </div>


        <div class="stat-number">
            <?= $post_count ?>
        </div>


        <div class="stat-label">
            Announcements
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-icon">

                <i class="fa-solid fa-folder-open"></i>

            </div>

            <div class="stat-arrow">

                <i class="fa-solid fa-arrow-up-right"></i>

            </div>

        </div>


        <div class="stat-number">
            <?= $project_count ?>
        </div>


        <div class="stat-label">
            Total Projects
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div class="stat-arrow">

                <i class="fa-solid fa-check"></i>

            </div>

        </div>


        <div class="stat-number">
            <?= $completed_count ?>
        </div>


        <div class="stat-label">
            Completed Projects
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-icon">

                <i class="fa-solid fa-calendar-days"></i>

            </div>

            <div class="stat-arrow">

                <i class="fa-solid fa-arrow-up-right"></i>

            </div>

        </div>


        <div class="stat-number">
            <?= $event_count ?>
        </div>


        <div class="stat-label">
            Community Events
        </div>

    </div>


</section>


<!-- =========================================================
     SUCCESS MESSAGES
========================================================= -->

<?php if ($success == 'announcement') { ?>

<div class="alert alert-success mb-3">

    <i class="fa-solid fa-circle-check"></i>

    Announcement published successfully.

</div>

<?php } ?>


<?php if ($success == 'project') { ?>

<div class="alert alert-success mb-3">

    <i class="fa-solid fa-circle-check"></i>

    Project added successfully.

</div>

<?php } ?>


<?php if ($success == 'event') { ?>

<div class="alert alert-success mb-3">

    <i class="fa-solid fa-circle-check"></i>

    Event added successfully.

</div>

<?php } ?>


<!-- =========================================================
     ANNOUNCEMENTS
========================================================= -->

<section
    class="section"
    id="announcements"
>


    <div class="section-header">


        <div class="section-title">


            <div class="section-icon">

                <i class="fa-solid fa-bullhorn"></i>

            </div>


            <div class="section-heading">

                <h3>
                    Announcements
                </h3>

                <p>
                    Latest barangay updates and notices
                </p>

            </div>


        </div>


        <?php if ($is_admin) { ?>

        <button
            class="admin-add-btn"
            data-toggle="modal"
            data-target="#announcementModal"
        >

            <i class="fa-solid fa-plus"></i>

            Add Announcement

        </button>

        <?php } ?>


    </div>


    <?php if ($post_count == 0) { ?>

        <div class="empty-state">

            <div class="empty-icon">

                <i class="fa-regular fa-newspaper"></i>

            </div>

            <h5>
                No announcements yet
            </h5>

            <p>
                New barangay announcements will appear here.
            </p>

        </div>

    <?php } ?>


    <?php while ($post = mysqli_fetch_assoc($posts)) { ?>


    <article class="announcement-card">


        <?php if (!empty($post['image'])) { ?>


            <?php

            $image_path =
                "Superadmin/Uploads/" .
                $post['image'];

            if (!file_exists($image_path)) {

                $image_path =
                    "uploads/announcements/" .
                    $post['image'];

            }

            ?>


            <div class="announcement-image-wrapper">

                <img
                    src="<?= htmlspecialchars($image_path) ?>"
                    class="announcement-image"
                    alt="Barangay Announcement"
                >


                <div class="image-label">

                    <i class="fa-solid fa-image"></i>

                    Barangay Update

                </div>

            </div>


        <?php } ?>


        <div class="announcement-body">


            <div class="announcement-meta">

                <i class="fa-regular fa-clock"></i>

                Posted

                <?= date(
                    "F d, Y • h:i A",
                    strtotime($post['created_at'])
                ) ?>

            </div>


            <div class="announcement-title">

                <?= htmlspecialchars(
                    $post['title']
                ) ?>

            </div>


            <div class="announcement-description">

                <?= nl2br(
                    htmlspecialchars(
                        $post['description']
                    )
                ) ?>

            </div>


            <?php if ($is_admin) { ?>

            <div class="delete-btn">

                <a
                    href="?name=<?= urlencode($barangay_name) ?>&delete_post=<?= $post['id'] ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Are you sure you want to delete this announcement?')"
                >

                    <i class="fa-solid fa-trash"></i>

                    Delete

                </a>

            </div>

            <?php } ?>


        </div>


    </article>


    <?php } ?>


</section>


<!-- =========================================================
     PROJECTS
========================================================= -->

<section
    class="section"
    id="projects"
>


    <div class="section-header">


        <div class="section-title">


            <div class="section-icon">

                <i class="fa-solid fa-folder-open"></i>

            </div>


            <div class="section-heading">

                <h3>
                    Projects & Activities
                </h3>

                <p>
                    Barangay projects and community activities
                </p>

            </div>


        </div>


        <?php if ($is_admin) { ?>

        <button
            class="admin-add-btn"
            data-toggle="modal"
            data-target="#projectModal"
        >

            <i class="fa-solid fa-plus"></i>

            Add Project

        </button>

        <?php } ?>


    </div>


    <?php if ($project_count == 0) { ?>

        <div class="empty-state">

            <div class="empty-icon">

                <i class="fa-solid fa-folder-open"></i>

            </div>

            <h5>
                No projects yet
            </h5>

            <p>
                Barangay projects and activities will appear here.
            </p>

        </div>

    <?php } ?>


    <div class="record-grid">


    <?php while ($project = mysqli_fetch_assoc($projects)) { ?>


        <div class="record-card">


            <div class="record-top">


                <div class="record-title">

                    <?= htmlspecialchars(
                        $project['title']
                    ) ?>

                </div>


                <?php

                $status_class =
                    "status-upcoming";

                if (
                    $project['status'] === "Completed"
                ) {

                    $status_class =
                        "status-completed";

                }

                if (
                    $project['status'] === "Ongoing"
                ) {

                    $status_class =
                        "status-ongoing";

                }

                ?>


                <span class="status <?= $status_class ?>">

                    <?= htmlspecialchars(
                        $project['status']
                    ) ?>

                </span>


            </div>


            <div class="record-description">

                <?= nl2br(
                    htmlspecialchars(
                        $project['description']
                    )
                ) ?>

            </div>


            <?php if (!empty($project['created_at'])) { ?>

            <div class="record-date">

                <i class="fa-regular fa-calendar"></i>

                Added

                <?= date(
                    "F d, Y",
                    strtotime(
                        $project['created_at']
                    )
                ) ?>

            </div>

            <?php } ?>


            <?php if ($is_admin) { ?>

            <div style="margin-top:13px;">

                <a
                    href="?name=<?= urlencode($barangay_name) ?>&delete_project=<?= $project['id'] ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Are you sure you want to delete this project?')"
                >

                    <i class="fa-solid fa-trash"></i>

                    Delete

                </a>

            </div>

            <?php } ?>


        </div>


    <?php } ?>


    </div>


</section>


<!-- =========================================================
     EVENTS
========================================================= -->

<section
    class="section"
    id="events"
>


    <div class="section-header">


        <div class="section-title">


            <div class="section-icon">

                <i class="fa-solid fa-calendar-days"></i>

            </div>


            <div class="section-heading">

                <h3>
                    Upcoming Events
                </h3>

                <p>
                    Community activities and upcoming events
                </p>

            </div>


        </div>


        <?php if ($is_admin) { ?>

        <button
            class="admin-add-btn"
            data-toggle="modal"
            data-target="#eventModal"
        >

            <i class="fa-solid fa-plus"></i>

            Add Event

        </button>

        <?php } ?>


    </div>


    <?php if ($event_count == 0) { ?>

        <div class="empty-state">

            <div class="empty-icon">

                <i class="fa-regular fa-calendar"></i>

            </div>

            <h5>
                No upcoming events
            </h5>

            <p>
                Upcoming barangay events will appear here.
            </p>

        </div>

    <?php } ?>


    <?php while ($event = mysqli_fetch_assoc($events)) { ?>


        <div class="event-card">


            <div class="event-date-box">

                <div class="event-day">

                    <?= date(
                        "d",
                        strtotime(
                            $event['event_date']
                        )
                    ) ?>

                </div>


                <div class="event-month">

                    <?= date(
                        "M",
                        strtotime(
                            $event['event_date']
                        )
                    ) ?>

                </div>

            </div>


            <div class="event-info">


                <h5>

                    <?= htmlspecialchars(
                        $event['title']
                    ) ?>

                </h5>


                <p>

                    <?= nl2br(
                        htmlspecialchars(
                            $event['description']
                        )
                    ) ?>

                </p>


                <div class="event-location">

                    <i class="fa-solid fa-location-dot"></i>

                    <span>

                        <?= htmlspecialchars(
                            $event['location']
                        ) ?>

                    </span>

                    <span>•</span>

                    <span>

                        <?= date(
                            "F d, Y",
                            strtotime(
                                $event['event_date']
                            )
                        ) ?>

                    </span>

                </div>


                <?php if ($is_admin) { ?>

                <div style="margin-top:10px;">

                    <a
                        href="?name=<?= urlencode($barangay_name) ?>&delete_event=<?= $event['id'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Are you sure you want to delete this event?')"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete

                    </a>

                </div>

                <?php } ?>


            </div>


        </div>


    <?php } ?>


</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">

    <i class="fa-solid fa-landmark"></i>

    SK KONEXT

    &nbsp;•&nbsp;

    <?= htmlspecialchars($barangay_name) ?>

    &nbsp;•&nbsp;

    Barangay Information Portal

</footer>


</main>


<!-- =========================================================
     ADMIN MODALS
========================================================= -->

<?php if ($is_admin) { ?>


<!-- =========================================================
     ANNOUNCEMENT MODAL
========================================================= -->

<div
    class="modal fade"
    id="announcementModal"
    tabindex="-1"
>

<div class="modal-dialog modal-lg">

<div class="modal-content">


<div class="modal-header">

    <h5 class="modal-title">

        <i class="fa-solid fa-bullhorn"></i>

        &nbsp;

        Create Announcement

    </h5>


    <button
        type="button"
        class="close"
        data-dismiss="modal"
    >

        &times;

    </button>

</div>


<form
    method="POST"
    enctype="multipart/form-data"
>


<div class="modal-body">


    <label class="form-label">
        Announcement Title
    </label>


    <input
        type="text"
        name="post_title"
        class="form-control mb-3"
        placeholder="Enter announcement title"
        required
    >


    <label class="form-label">
        Description
    </label>


    <textarea
        name="post_description"
        class="form-control mb-3"
        rows="6"
        placeholder="Write your announcement..."
        required
    ></textarea>


    <label class="form-label">

        Announcement Image

        <small style="color:#60766a;">
            (Optional)
        </small>

    </label>


    <input
        type="file"
        name="post_image"
        class="form-control"
        accept=".jpg,.jpeg,.png,.gif,.webp"
    >


</div>


<div class="modal-footer">


    <button
        type="button"
        class="btn btn-dark"
        data-dismiss="modal"
    >

        Cancel

    </button>


    <button
        type="submit"
        name="add_post"
        class="admin-add-btn"
    >

        <i class="fa-solid fa-paper-plane"></i>

        Publish Announcement

    </button>


</div>


</form>


</div>

</div>

</div>


<!-- =========================================================
     PROJECT MODAL
========================================================= -->

<div
    class="modal fade"
    id="projectModal"
    tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content">


<div class="modal-header">

    <h5 class="modal-title">

        <i class="fa-solid fa-folder-plus"></i>

        &nbsp;

        Add Project

    </h5>


    <button
        type="button"
        class="close"
        data-dismiss="modal"
    >

        &times;

    </button>

</div>


<form method="POST">


<div class="modal-body">


    <label class="form-label">
        Project Name
    </label>


    <input
        type="text"
        name="project_title"
        class="form-control mb-3"
        placeholder="Example: Community Clean-Up Drive"
        required
    >


    <label class="form-label">
        Description
    </label>


    <textarea
        name="project_description"
        class="form-control mb-3"
        rows="5"
        placeholder="Describe the project or activity..."
        required
    ></textarea>


    <label class="form-label">
        Status
    </label>


    <select
        name="project_status"
        class="form-control"
        required
    >

        <option value="Upcoming">
            Upcoming
        </option>


        <option value="Ongoing">
            Ongoing
        </option>


        <option value="Completed">
            Completed
        </option>

    </select>


</div>


<div class="modal-footer">


    <button
        type="button"
        class="btn btn-dark"
        data-dismiss="modal"
    >

        Cancel

    </button>


    <button
        type="submit"
        name="add_project"
        class="admin-add-btn"
    >

        <i class="fa-solid fa-save"></i>

        Save Project

    </button>


</div>


</form>


</div>

</div>

</div>


<!-- =========================================================
     EVENT MODAL
========================================================= -->

<div
    class="modal fade"
    id="eventModal"
    tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content">


<div class="modal-header">

    <h5 class="modal-title">

        <i class="fa-solid fa-calendar-plus"></i>

        &nbsp;

        Add Event

    </h5>


    <button
        type="button"
        class="close"
        data-dismiss="modal"
    >

        &times;

    </button>

</div>


<form method="POST">


<div class="modal-body">


    <label class="form-label">
        Event Name
    </label>


    <input
        type="text"
        name="event_title"
        class="form-control mb-3"
        placeholder="Example: Youth Sports Festival"
        required
    >


    <label class="form-label">
        Description
    </label>


    <textarea
        name="event_description"
        class="form-control mb-3"
        rows="4"
        placeholder="Describe the event..."
        required
    ></textarea>


    <label class="form-label">
        Event Date
    </label>


    <input
        type="date"
        name="event_date"
        class="form-control mb-3"
        required
    >


    <label class="form-label">
        Location
    </label>


    <input
        type="text"
        name="event_location"
        class="form-control"
        placeholder="Example: Barangay Covered Court"
        required
    >


</div>


<div class="modal-footer">


    <button
        type="button"
        class="btn btn-dark"
        data-dismiss="modal"
    >

        Cancel

    </button>


    <button
        type="submit"
        name="add_event"
        class="admin-add-btn"
    >

        <i class="fa-solid fa-calendar-check"></i>

        Save Event

    </button>


</div>


</form>


</div>

</div>

</div>


<?php } ?>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
></script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>