<?php
include("../db.php");
include("../includes/auth.php");


/* =========================================
   ALLOW PRESIDENT AND SK MEMBER
========================================= */

allowRoles(['president', 'sk_member']);


/* =========================================
   GET LOGGED-IN USER'S BARANGAY
========================================= */

if(empty($_SESSION['barangay_id'])){
    die("No Barangay has been assigned to this account.");
}

$barangay_id = (int) $_SESSION['barangay_id'];


/* =========================================
   GET BARANGAY INFORMATION
========================================= */

$barangay_query = mysqli_query($conn, "
    SELECT *
    FROM barangays
    WHERE id = '$barangay_id'
    LIMIT 1
");

if(!$barangay_query || mysqli_num_rows($barangay_query) == 0){
    die("Barangay information not found.");
}

$barangay = mysqli_fetch_assoc($barangay_query);


/* =========================================
   BARANGAY NAME
========================================= */

$barangay_name = !empty($barangay['name'])
    ? $barangay['name']
    : $barangay['barangay_name'];


/* =========================================
   LOGGED-IN USER
========================================= */

$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? '';


/* =========================================
   GET SK MEMBERS
========================================= */

$members_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM users
    WHERE barangay_id = '$barangay_id'
    AND role = 'sk_member'
    AND status = 'active'
");

$members_data = mysqli_fetch_assoc($members_query);
$total_members = $members_data['total'] ?? 0;


/* =========================================
   GET PRESIDENTS
========================================= */

$president_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM users
    WHERE barangay_id = '$barangay_id'
    AND role = 'president'
    AND status = 'active'
");

$president_data = mysqli_fetch_assoc($president_query);
$total_presidents = $president_data['total'] ?? 0;


/* =========================================
   SCHOLARS
========================================= */

$total_scholars = $barangay['total_scholars'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars($barangay_name) ?> | SK KONEXT
</title>


<!-- GOOGLE FONTS -->

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
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
    --dark-2:#091810;

    --white:#ffffff;

    --muted:rgba(255,255,255,.58);

    --glass:rgba(255,255,255,.055);

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
   BARANGAY MINI LABEL
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

    margin-bottom:28px;
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
   USER CARD
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

    color:white;
}

.user-text small{

    color:rgba(255,255,255,.40);

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.8px;
}


/* =========================================================
   WELCOME CARD
========================================================= */

.welcome-card{

    position:relative;

    overflow:hidden;

    padding:32px;

    margin-bottom:25px;

    border-radius:25px;

    background:

        linear-gradient(
            120deg,
            rgba(74,222,128,.14),
            rgba(255,255,255,.035)
        );

    border:
        1px solid rgba(74,222,128,.18);

    box-shadow:
        0 25px 60px rgba(0,0,0,.18);
}

.welcome-card::after{

    content:"";

    position:absolute;

    width:250px;

    height:250px;

    right:-100px;

    top:-120px;

    border-radius:50%;

    background:
        rgba(74,222,128,.08);

    filter:blur(30px);

    pointer-events:none;
}

.welcome-kicker{

    color:var(--green);

    font-size:10px;

    font-weight:700;

    letter-spacing:2px;

    text-transform:uppercase;

    margin-bottom:10px;
}

.welcome-card h2{

    font-family:"Manrope",sans-serif;

    font-size:28px;

    font-weight:800;

    letter-spacing:-1px;

    margin-bottom:8px;
}

.welcome-card p{

    color:var(--muted);

    font-size:14px;

    line-height:1.7;

    margin:0;
}

.welcome-card strong{

    color:white;
}


/* =========================================================
   STAT GRID
========================================================= */

.stat-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:15px;

    margin-bottom:25px;
}

.stat-card{

    position:relative;

    overflow:hidden;

    min-height:175px;

    padding:25px;

    border-radius:21px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.07),
            rgba(255,255,255,.025)
        );

    border:
        1px solid var(--border);

    transition:
        transform .3s ease,
        border-color .3s ease,
        background .3s ease;
}

.stat-card::after{

    content:"";

    position:absolute;

    width:110px;

    height:110px;

    right:-45px;

    bottom:-45px;

    border-radius:50%;

    background:
        rgba(74,222,128,.06);

    filter:blur(15px);
}

.stat-card:hover{

    transform:translateY(-5px);

    border-color:
        rgba(74,222,128,.25);

    background:
        linear-gradient(
            145deg,
            rgba(74,222,128,.09),
            rgba(255,255,255,.025)
        );
}

.stat-icon{

    width:43px;

    height:43px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:13px;

    background:
        rgba(74,222,128,.10);

    color:var(--green);

    margin-bottom:28px;

    font-size:17px;
}

.stat-number{

    font-family:"Manrope",sans-serif;

    font-size:42px;

    line-height:1;

    font-weight:800;

    letter-spacing:-2px;

    margin-bottom:7px;
}

.stat-label{

    color:var(--muted);

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:1px;
}


/* =========================================================
   CONTENT GRID
========================================================= */

.content-grid{

    display:grid;

    grid-template-columns:
        1.25fr .75fr;

    gap:15px;
}


/* =========================================================
   PANEL
========================================================= */

.panel{

    padding:27px;

    border-radius:22px;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid var(--border);

    box-shadow:
        0 18px 45px rgba(0,0,0,.14);
}

.panel-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    margin-bottom:22px;
}

.panel-title{

    display:flex;

    align-items:center;

    gap:11px;

    font-family:"Manrope",sans-serif;

    font-size:17px;

    font-weight:800;
}

.panel-title i{

    color:var(--green);

    font-size:15px;
}


/* =========================================================
   INFO ROWS
========================================================= */

.info-list{

    display:flex;

    flex-direction:column;
}

.info-row{

    display:flex;

    justify-content:space-between;

    gap:25px;

    padding:16px 0;

    border-bottom:
        1px solid rgba(255,255,255,.07);
}

.info-row:last-child{

    border-bottom:none;

    padding-bottom:0;
}

.info-label{

    color:rgba(255,255,255,.38);

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:1px;
}

.info-value{

    max-width:65%;

    text-align:right;

    color:rgba(255,255,255,.85);

    font-size:13px;

    font-weight:600;

    line-height:1.6;
}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions{

    display:flex;

    flex-direction:column;

    gap:10px;
}

.quick-btn{

    display:flex;

    align-items:center;

    gap:13px;

    padding:14px;

    border-radius:14px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid rgba(255,255,255,.06);

    color:rgba(255,255,255,.75);

    font-size:12px;

    font-weight:600;

    transition:.3s ease;
}

.quick-btn:hover{

    transform:translateX(4px);

    background:
        rgba(74,222,128,.07);

    border-color:
        rgba(74,222,128,.17);

    color:var(--green-light);
}

.quick-icon{

    width:39px;

    height:39px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    flex-shrink:0;

    background:
        rgba(74,222,128,.09);

    color:var(--green);

    font-size:14px;
}

.quick-arrow{

    margin-left:auto;

    color:rgba(255,255,255,.25);

    font-size:10px;
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

        align-items:flex-start;

        flex-direction:column;
    }

    .user-info{

        width:100%;
    }

    .stat-grid{

        grid-template-columns:1fr;
    }

}


@media(max-width:500px){

    .sidebar a{

        flex:1 1 100%;
    }

    .topbar h1{

        font-size:27px;
    }

    .welcome-card{

        padding:25px;
    }

    .welcome-card h2{

        font-size:23px;
    }

    .panel{

        padding:21px;
    }

    .info-row{

        display:block;
    }

    .info-value{

        max-width:100%;

        text-align:left;

        margin-top:5px;
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


        <a href="dashboard.php"
           class="active">

            <i class="fa-solid fa-chart-line"></i>

            Dashboard

        </a>


        <a href="posts.php">

            <i class="fa-solid fa-bullhorn"></i>

            Announcements

        </a>


        <a href="events.php">

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
                Barangay Administration
            </div>

            <h1>
                <?= htmlspecialchars($barangay_name) ?>
            </h1>

            <p>
                Manage your barangay's SK activities and community information.
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
         WELCOME
    ================================================= -->

    <section class="welcome-card">


        <div class="welcome-kicker">

            SK KONEXT • COMMUNITY PORTAL

        </div>


        <h2>

            Welcome,
            <?= htmlspecialchars($fullname) ?>.

        </h2>


        <p>

            You are currently managing

            <strong>
                <?= htmlspecialchars($barangay_name) ?>
            </strong>

            through the SK KONEXT platform.

        </p>


    </section>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <section class="stat-grid">


        <!-- MEMBERS -->

        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-users"></i>

            </div>

            <div class="stat-number">

                <?= number_format($total_members) ?>

            </div>

            <div class="stat-label">

                Active SK Members

            </div>

        </div>


        <!-- PRESIDENT -->

        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-user-tie"></i>

            </div>

            <div class="stat-number">

                <?= number_format($total_presidents) ?>

            </div>

            <div class="stat-label">

                Active President

            </div>

        </div>


        <!-- SCHOLARS -->

        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-graduation-cap"></i>

            </div>

            <div class="stat-number">

                <?= number_format($total_scholars) ?>

            </div>

            <div class="stat-label">

                Total Scholars

            </div>

        </div>


    </section>



    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content-grid">


        <!-- =================================================
             BARANGAY INFORMATION
        ================================================= -->

        <div class="panel">


            <div class="panel-header">

                <div class="panel-title">

                    <i class="fa-solid fa-building-columns"></i>

                    Barangay Information

                </div>

            </div>


            <div class="info-list">


                <!-- NAME -->

                <div class="info-row">

                    <div class="info-label">
                        Barangay
                    </div>

                    <div class="info-value">

                        <?= htmlspecialchars($barangay_name) ?>

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="info-row">

                    <div class="info-label">
                        Description
                    </div>

                    <div class="info-value">

                        <?= !empty($barangay['description'])

                            ? htmlspecialchars(
                                $barangay['description']
                              )

                            : 'No description available.'
                        ?>

                    </div>

                </div>


                <!-- ID -->

                <div class="info-row">

                    <div class="info-label">
                        Barangay ID
                    </div>

                    <div class="info-value">

                        #<?= htmlspecialchars($barangay_id) ?>

                    </div>

                </div>


            </div>


        </div>



        <!-- =================================================
             QUICK ACTIONS
        ================================================= -->

        <div class="panel">


            <div class="panel-header">

                <div class="panel-title">

                    <i class="fa-solid fa-bolt"></i>

                    Quick Actions

                </div>

            </div>


            <div class="quick-actions">


                <!-- ANNOUNCEMENTS -->

                <a href="posts.php"
                   class="quick-btn">

                    <div class="quick-icon">

                        <i class="fa-solid fa-bullhorn"></i>

                    </div>

                    <span>
                        Manage Announcements
                    </span>

                    <i class="fa-solid fa-arrow-right quick-arrow"></i>

                </a>


                <!-- EVENTS -->

                <a href="events.php"
                   class="quick-btn">

                    <div class="quick-icon">

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>

                    <span>
                        Manage Events
                    </span>

                    <i class="fa-solid fa-arrow-right quick-arrow"></i>

                </a>


                <!-- PROJECTS -->

                <a href="projects.php"
                   class="quick-btn">

                    <div class="quick-icon">

                        <i class="fa-solid fa-folder-open"></i>

                    </div>

                    <span>
                        Manage Projects
                    </span>

                    <i class="fa-solid fa-arrow-right quick-arrow"></i>

                </a>


            </div>


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