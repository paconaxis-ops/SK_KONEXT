<?php

include("../db.php");
include("../includes/auth.php");

allowRoles(['president', 'sk_member']);


/* =========================================
   CHECK BARANGAY ASSIGNMENT
========================================= */

if(empty($_SESSION['barangay_id'])){
    die("No Barangay has been assigned to this account.");
}

$barangay_id = (int) $_SESSION['barangay_id'];
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];


/* =========================================
   GET BARANGAY NAME
========================================= */

$barangay_query = mysqli_query($conn, "
    SELECT name
    FROM barangays
    WHERE id = '$barangay_id'
    LIMIT 1
");

$barangay = mysqli_fetch_assoc($barangay_query);

if(!$barangay){
    die("Barangay information not found.");
}

$barangay_name = $barangay['name'];


/* =========================================
   ADD EVENT
========================================= */

if(isset($_POST['add_event'])){

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    $event_date = mysqli_real_escape_string(
        $conn,
        $_POST['event_date']
    );

    $location = mysqli_real_escape_string(
        $conn,
        $_POST['location']
    );


    mysqli_query($conn, "
        INSERT INTO barangay_events
        (
            barangay_id,
            title,
            description,
            event_date,
            location,
            created_at
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$event_date',
            '$location',
            NOW()
        )
    ");

    header("Location: events.php");
    exit();
}


/* =========================================
   DELETE EVENT
========================================= */

if(isset($_GET['delete'])){

    $event_id = (int) $_GET['delete'];

    /*
       Barangay restriction is retained.
    */

    mysqli_query($conn, "
        DELETE FROM barangay_events
        WHERE id = '$event_id'
        AND barangay_id = '$barangay_id'
    ");

    header("Location: events.php");
    exit();
}


/* =========================================
   GET EVENTS
========================================= */

$events_query = mysqli_query($conn, "
    SELECT *
    FROM barangay_events
    WHERE barangay_id = '$barangay_id'
    ORDER BY event_date ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
<?= htmlspecialchars($barangay_name) ?> | Events | SK KONEXT
</title>

<meta name="viewport"
      content="width=device-width, initial-scale=1">


<!-- GOOGLE FONTS -->

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.googleapis.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
      rel="stylesheet">


<!-- FONT AWESOME -->

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


<style>

/* =========================================================
   GLOBAL
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

    font-family:"DM Sans",sans-serif;

    background:#06100b;

    color:#ffffff;

    min-height:100vh;

    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}


/* =========================================================
   VARIABLES
========================================================= */

:root{

    --green:#4ade80;
    --green-dark:#16a34a;
    --green-light:#86efac;

    --dark:#06100b;

    --muted:rgba(255,255,255,.58);

    --border:rgba(255,255,255,.10);
}


/* =========================================================
   BACKGROUND
========================================================= */

body::before{

    content:"";

    position:fixed;

    inset:0;

    pointer-events:none;

    background:

        radial-gradient(
            circle at 10% 10%,
            rgba(74,222,128,.12),
            transparent 30%
        ),

        radial-gradient(
            circle at 90% 80%,
            rgba(22,163,74,.10),
            transparent 30%
        );

    z-index:-2;
}


body::after{

    content:"";

    position:fixed;

    inset:0;

    pointer-events:none;

    background-image:

        linear-gradient(
            rgba(255,255,255,.018) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(255,255,255,.018) 1px,
            transparent 1px
        );

    background-size:65px 65px;

    mask-image:
        linear-gradient(
            to bottom,
            black,
            transparent 90%
        );

    z-index:-1;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar{

    position:fixed;

    left:20px;

    top:20px;

    width:250px;

    height:calc(100vh - 40px);

    padding:24px 18px;

    background:
        rgba(7,17,12,.78);

    border:
        1px solid var(--border);

    border-radius:24px;

    backdrop-filter:blur(22px);

    -webkit-backdrop-filter:blur(22px);

    box-shadow:
        0 25px 70px rgba(0,0,0,.30);

    z-index:1000;

    display:flex;

    flex-direction:column;
}


/* =========================================================
   BRAND
========================================================= */

.logo{

    display:flex;

    align-items:center;

    gap:10px;

    padding:4px 10px 25px;

    font-family:"Manrope",sans-serif;

    font-size:21px;

    font-weight:800;

    letter-spacing:-.5px;
}

.logo-icon{

    width:38px;

    height:38px;

    border-radius:11px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        rgba(74,222,128,.10);

    border:
        1px solid rgba(74,222,128,.20);

    color:var(--green);

    box-shadow:
        0 0 25px rgba(74,222,128,.08);
}

.logo span{
    color:var(--green);
}


/* =========================================================
   LOCATION
========================================================= */

.sidebar-location{

    padding:12px 13px;

    margin:0 5px 25px;

    border-radius:14px;

    background:
        rgba(255,255,255,.04);

    border:
        1px solid rgba(255,255,255,.07);
}

.sidebar-location small{

    display:block;

    color:rgba(255,255,255,.38);

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:1.5px;

    margin-bottom:4px;
}

.sidebar-location strong{

    display:block;

    color:#fff;

    font-size:13px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;
}


/* =========================================================
   NAVIGATION
========================================================= */

.sidebar-nav{

    display:flex;

    flex-direction:column;

    gap:7px;
}

.sidebar a{

    display:flex;

    align-items:center;

    gap:13px;

    padding:13px 14px;

    border-radius:13px;

    color:rgba(255,255,255,.60);

    font-size:13px;

    font-weight:600;

    transition:
        background .25s ease,
        color .25s ease,
        transform .25s ease;
}

.sidebar a i{

    width:20px;

    text-align:center;

    font-size:15px;
}

.sidebar a:hover{

    background:
        rgba(255,255,255,.06);

    color:#fff;

    transform:translateX(3px);
}

.sidebar a.active{

    background:
        linear-gradient(
            135deg,
            rgba(74,222,128,.17),
            rgba(22,163,74,.08)
        );

    border:
        1px solid rgba(74,222,128,.16);

    color:var(--green-light);

    box-shadow:
        inset 0 0 20px rgba(74,222,128,.025);
}


/* =========================================================
   LOGOUT
========================================================= */

.sidebar-bottom{

    margin-top:auto;

    padding-top:15px;

    border-top:
        1px solid rgba(255,255,255,.07);
}

.logout{

    color:#ff8f8f !important;
}

.logout:hover{

    background:
        rgba(255,80,80,.07) !important;

    color:#ffaaaa !important;
}


/* =========================================================
   MAIN
========================================================= */

.main{

    margin-left:290px;

    padding:35px;

    max-width:1600px;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:25px;

    margin-bottom:30px;
}

.page-kicker{

    color:var(--green);

    font-size:10px;

    font-weight:700;

    letter-spacing:2px;

    text-transform:uppercase;

    margin-bottom:7px;
}

.topbar h1{

    font-family:"Manrope",sans-serif;

    font-size:32px;

    font-weight:800;

    letter-spacing:-1.5px;

    margin-bottom:5px;
}

.topbar p{

    color:var(--muted);

    font-size:13px;

    margin:0;
}


/* =========================================================
   USER
========================================================= */

.user-info{

    display:flex;

    align-items:center;

    gap:12px;

    padding:10px 14px 10px 10px;

    background:
        rgba(255,255,255,.05);

    border:
        1px solid var(--border);

    border-radius:16px;

    backdrop-filter:blur(15px);
}

.user-avatar{

    width:38px;

    height:38px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        rgba(74,222,128,.12);

    color:var(--green);

    font-size:15px;
}

.user-text strong{

    display:block;

    font-size:12px;

    font-weight:700;
}

.user-text small{

    color:rgba(255,255,255,.40);

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.8px;
}


/* =========================================================
   PAGE INTRO
========================================================= */

.page-intro{

    position:relative;

    overflow:hidden;

    padding:30px;

    margin-bottom:22px;

    border-radius:23px;

    background:
        linear-gradient(
            120deg,
            rgba(74,222,128,.12),
            rgba(255,255,255,.035)
        );

    border:
        1px solid rgba(74,222,128,.16);
}

.page-intro::after{

    content:"";

    position:absolute;

    width:200px;

    height:200px;

    right:-90px;

    top:-100px;

    border-radius:50%;

    background:
        rgba(74,222,128,.08);

    filter:blur(30px);
}

.intro-icon{

    width:48px;

    height:48px;

    border-radius:14px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        rgba(74,222,128,.10);

    color:var(--green);

    margin-bottom:17px;

    font-size:18px;
}

.page-intro h2{

    font-family:"Manrope",sans-serif;

    font-size:27px;

    font-weight:800;

    letter-spacing:-1px;

    margin-bottom:8px;
}

.page-intro p{

    color:var(--muted);

    font-size:13px;

    margin:0;

    line-height:1.7;
}


/* =========================================================
   CONTENT GRID
========================================================= */

.content-grid{

    display:grid;

    grid-template-columns:
        380px 1fr;

    gap:18px;

    align-items:start;
}


/* =========================================================
   PANEL
========================================================= */

.panel{

    padding:25px;

    border-radius:22px;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid var(--border);

    box-shadow:
        0 18px 45px rgba(0,0,0,.14);
}

.panel-title{

    display:flex;

    align-items:center;

    gap:10px;

    font-family:"Manrope",sans-serif;

    font-size:17px;

    font-weight:800;

    margin-bottom:22px;
}

.panel-title i{

    color:var(--green);

    font-size:15px;
}


/* =========================================================
   FORM
========================================================= */

.form-group{

    margin-bottom:18px;
}

.form-group label{

    display:block;

    color:rgba(255,255,255,.60);

    font-size:11px;

    font-weight:600;

    text-transform:uppercase;

    letter-spacing:1px;

    margin-bottom:8px;
}

.form-control{

    width:100%;

    padding:13px 14px;

    border-radius:12px;

    border:
        1px solid rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.045);

    color:#fff;

    font-family:"DM Sans",sans-serif;

    font-size:13px;

    outline:none;

    transition:.25s ease;
}

.form-control:focus{

    border-color:
        rgba(74,222,128,.45);

    background:
        rgba(74,222,128,.035);

    box-shadow:
        0 0 0 3px rgba(74,222,128,.06);
}

.form-control::placeholder{

    color:rgba(255,255,255,.25);
}

textarea.form-control{

    min-height:120px;

    resize:vertical;
}

input[type="date"]{

    color-scheme:dark;
}


/* =========================================================
   ADD BUTTON
========================================================= */

.btn-green{

    width:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:9px;

    padding:13px 18px;

    border-radius:12px;

    border:1px solid var(--green);

    background:var(--green);

    color:#06100b;

    font-family:"DM Sans",sans-serif;

    font-size:13px;

    font-weight:800;

    cursor:pointer;

    transition:.3s ease;
}

.btn-green:hover{

    transform:translateY(-2px);

    box-shadow:
        0 12px 30px rgba(74,222,128,.18);
}


/* =========================================================
   EVENTS HEADER
========================================================= */

.events-panel{

    min-width:0;
}

.events-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    padding-bottom:18px;

    margin-bottom:18px;

    border-bottom:
        1px solid rgba(255,255,255,.07);
}

.events-count{

    padding:7px 11px;

    border-radius:10px;

    background:
        rgba(74,222,128,.08);

    border:
        1px solid rgba(74,222,128,.12);

    color:var(--green-light);

    font-size:10px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:1px;
}


/* =========================================================
   EVENT CARD
========================================================= */

.event-card{

    position:relative;

    padding:21px;

    margin-bottom:12px;

    border-radius:17px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid rgba(255,255,255,.075);

    transition:
        transform .3s ease,
        border-color .3s ease,
        background .3s ease;
}

.event-card:hover{

    transform:translateY(-3px);

    border-color:
        rgba(74,222,128,.20);

    background:
        rgba(74,222,128,.045);
}

.event-card:last-child{

    margin-bottom:0;
}

.event-title{

    font-family:"Manrope",sans-serif;

    font-size:19px;

    font-weight:800;

    letter-spacing:-.3px;

    margin-bottom:8px;

    padding-right:70px;
}

.event-description{

    color:var(--muted);

    font-size:13px;

    line-height:1.7;

    white-space:pre-line;

    margin-bottom:15px;
}


/* =========================================================
   EVENT BADGES
========================================================= */

.event-info{

    display:flex;

    flex-wrap:wrap;

    gap:8px;
}

.event-badge{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:8px 11px;

    border-radius:10px;

    background:
        rgba(74,222,128,.07);

    border:
        1px solid rgba(74,222,128,.10);

    color:
        rgba(255,255,255,.70);

    font-size:11px;
}

.event-badge i{

    color:var(--green);

    font-size:11px;
}


/* =========================================================
   EVENT META
========================================================= */

.event-date{

    margin-top:15px;

    padding-top:13px;

    border-top:
        1px solid rgba(255,255,255,.06);

    color:
        rgba(255,255,255,.30);

    font-size:10px;
}

.delete-btn{

    color:#ff8f8f;

    font-size:11px;

    font-weight:600;

    transition:.2s ease;
}

.delete-btn:hover{

    color:#ffb0b0;

    text-decoration:none;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state{

    text-align:center;

    padding:65px 25px;

    color:rgba(255,255,255,.35);
}

.empty-icon{

    width:65px;

    height:65px;

    margin:0 auto 17px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:18px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid rgba(255,255,255,.07);

    color:
        rgba(255,255,255,.28);

    font-size:24px;
}

.empty-state p{

    font-size:13px;

    margin:0;
}


/* =========================================================
   FOOTER
========================================================= */

.footer{

    margin-top:30px;

    padding-top:20px;

    border-top:
        1px solid rgba(255,255,255,.06);

    color:
        rgba(255,255,255,.28);

    font-size:10px;

    display:flex;

    justify-content:space-between;

    gap:20px;
}

.footer strong{

    color:rgba(255,255,255,.55);
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:1100px){

    .sidebar{

        width:220px;
    }

    .main{

        margin-left:250px;

        padding:25px;
    }

    .content-grid{

        grid-template-columns:1fr;
    }

}


@media(max-width:800px){

    .sidebar{

        position:relative;

        left:auto;

        top:auto;

        width:calc(100% - 24px);

        height:auto;

        margin:12px;

        padding:16px;

        border-radius:20px;
    }

    .logo{

        padding-bottom:15px;
    }

    .sidebar-location{

        margin-bottom:15px;
    }

    .sidebar-nav{

        flex-direction:row;

        flex-wrap:wrap;
    }

    .sidebar a{

        flex:1 1 calc(50% - 7px);
    }

    .sidebar-bottom{

        margin-top:12px;
    }

    .main{

        margin-left:0;

        padding:20px 15px;
    }

    .topbar{

        flex-direction:column;

        align-items:flex-start;
    }

    .user-info{

        width:100%;
    }

}


@media(max-width:500px){

    .sidebar a{

        flex:1 1 100%;
    }

    .page-intro{

        padding:24px;
    }

    .page-intro h2{

        font-size:23px;
    }

    .panel{

        padding:20px;
    }

    .events-header{

        align-items:flex-start;

        flex-direction:column;
    }

    .event-title{

        font-size:17px;

        padding-right:0;
    }

    .event-info{

        flex-direction:column;

        align-items:flex-start;
    }

    .footer{

        flex-direction:column;
    }
}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media(prefers-reduced-motion:reduce){

    *{

        animation:none !important;

        transition:none !important;

        scroll-behavior:auto !important;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="logo">

        <div class="logo-icon">

            <i class="fa-solid fa-bolt"></i>

        </div>

        <div>

            SK <span>KONEXT</span>

        </div>

    </div>


    <!-- CURRENT BARANGAY -->

    <div class="sidebar-location">

        <small>
            Current Barangay
        </small>

        <strong>
            <?= htmlspecialchars($barangay_name) ?>
        </strong>

    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-nav">


        <a href="dashboard.php">

            <i class="fa-solid fa-chart-line"></i>

            Dashboard

        </a>


        <a href="posts.php">

            <i class="fa-solid fa-bullhorn"></i>

            Announcements

        </a>


        <a href="events.php"
           class="active">

            <i class="fa-solid fa-calendar-days"></i>

            Events

        </a>


        <a href="projects.php">

            <i class="fa-solid fa-folder-open"></i>

            Projects

        </a>


    </nav>


    <!-- LOGOUT -->

    <div class="sidebar-bottom">

        <a href="../logout.php"
           class="logout">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- =================================================
         TOPBAR
    ================================================= -->

    <header class="topbar">


        <div>

            <div class="page-kicker">
                Community Management
            </div>

            <h1>
                Events
            </h1>

            <p>
                Organize and manage events for <?= htmlspecialchars($barangay_name) ?>.
            </p>

        </div>


        <!-- USER -->

        <div class="user-info">

            <div class="user-avatar">

                <i class="fa-solid fa-user"></i>

            </div>

            <div class="user-text">

                <strong>
                    <?= htmlspecialchars($fullname) ?>
                </strong>

                <small>

                    <?= strtoupper(
                        str_replace('_',' ',$role)
                    ) ?>

                </small>

            </div>

        </div>


    </header>



    <!-- =================================================
         INTRO
    ================================================= -->

    <section class="page-intro">

        <div class="intro-icon">

            <i class="fa-solid fa-calendar-days"></i>

        </div>

        <h2>
            Community Events
        </h2>

        <p>

            Create and manage upcoming activities,
            programs, and events for your barangay.

        </p>

    </section>



    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content-grid">


        <!-- =================================================
             CREATE EVENT
        ================================================= -->

        <div class="panel">


            <div class="panel-title">

                <i class="fa-solid fa-calendar-plus"></i>

                Create Event

            </div>


            <form method="POST">


                <!-- TITLE -->

                <div class="form-group">

                    <label>
                        Event Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter event title"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        placeholder="Describe the event..."
                        required
                    ></textarea>

                </div>


                <!-- DATE -->

                <div class="form-group">

                    <label>
                        Event Date
                    </label>

                    <input
                        type="date"
                        name="event_date"
                        class="form-control"
                        required
                    >

                </div>


                <!-- LOCATION -->

                <div class="form-group">

                    <label>
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        class="form-control"
                        placeholder="Enter event location"
                        required
                    >

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    name="add_event"
                    class="btn-green">

                    <i class="fa-solid fa-plus"></i>

                    Add Event

                </button>


            </form>


        </div>



        <!-- =================================================
             EVENT LIST
        ================================================= -->

        <div class="panel events-panel">


            <div class="events-header">


                <div class="panel-title"
                     style="margin-bottom:0;">

                    <i class="fa-solid fa-calendar-days"></i>

                    <?= htmlspecialchars($barangay_name) ?>
                    Events

                </div>


                <?php

                $event_count =
                    ($events_query)
                    ? mysqli_num_rows($events_query)
                    : 0;

                ?>

                <div class="events-count">

                    <?= $event_count ?>

                    Event<?= $event_count != 1 ? 's' : '' ?>

                </div>


            </div>



            <?php if($event_count > 0){ ?>


                <?php while(
                    $event =
                    mysqli_fetch_assoc($events_query)
                ){ ?>


                    <article class="event-card">


                        <!-- TITLE -->

                        <div class="event-title">

                            <?= htmlspecialchars(
                                $event['title']
                            ) ?>

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="event-description">

                            <?= htmlspecialchars(
                                $event['description']
                            ) ?>

                        </div>


                        <!-- EVENT INFORMATION -->

                        <div class="event-info">


                            <span class="event-badge">

                                <i class="fa-solid fa-calendar"></i>

                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $event['event_date']
                                    )
                                ) ?>

                            </span>


                            <span class="event-badge">

                                <i class="fa-solid fa-location-dot"></i>

                                <?= htmlspecialchars(
                                    $event['location']
                                ) ?>

                            </span>


                        </div>


                        <!-- META -->

                        <div class="event-date">

                            <i class="fa-regular fa-clock"></i>

                            Created:

                            <?= date(
                                "F d, Y h:i A",
                                strtotime(
                                    $event['created_at']
                                )
                            ) ?>


                            <span
                                style="float:right;">

                                <a
                                    href="events.php?delete=<?= (int)$event['id'] ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Delete this event?');"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                    Delete

                                </a>

                            </span>

                        </div>


                    </article>


                <?php } ?>


            <?php }else{ ?>


                <!-- EMPTY -->

                <div class="empty-state">


                    <div class="empty-icon">

                        <i class="fa-regular fa-calendar-xmark"></i>

                    </div>


                    <p>
                        No events have been created yet.
                    </p>


                </div>


            <?php } ?>


        </div>


    </section>



    <!-- =================================================
         FOOTER
    ================================================= -->

    <footer class="footer">

        <div>

            <strong>
                SK KONEXT
            </strong>

            &nbsp;•&nbsp;

            Barangay Management Portal

        </div>

        <div>

            Sangguniang Kabataan

        </div>

    </footer>


</main>


</body>

</html>