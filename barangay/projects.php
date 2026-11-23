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
   ADD PROJECT
========================================= */

if(isset($_POST['add_project'])){

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );


    mysqli_query($conn, "
        INSERT INTO barangay_projects
        (
            barangay_id,
            title,
            description,
            status,
            created_at
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$status',
            NOW()
        )
    ");

    header("Location: projects.php");
    exit();
}


/* =========================================
   DELETE PROJECT
========================================= */

if(isset($_GET['delete'])){

    $project_id = (int) $_GET['delete'];

    /*
       Barangay restriction prevents
       deleting another Barangay's project.
    */

    mysqli_query($conn, "
        DELETE FROM barangay_projects
        WHERE id = '$project_id'
        AND barangay_id = '$barangay_id'
    ");

    header("Location: projects.php");
    exit();
}


/* =========================================
   GET PROJECTS
========================================= */

$projects_query = mysqli_query($conn, "
    SELECT *
    FROM barangay_projects
    WHERE barangay_id = '$barangay_id'
    ORDER BY created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
<?= htmlspecialchars($barangay_name) ?> | Projects
</title>

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
      rel="stylesheet">


<style>

/* =========================================
   GLOBAL
========================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --bg:#06100b;
    --panel:rgba(15,31,22,.78);
    --border:rgba(255,255,255,.08);

    --text:#f4fff7;
    --muted:#8da497;

    --green:#4ade80;
    --green2:#22c55e;

    --blue:#60a5fa;
    --yellow:#fbbf24;
    --red:#fb7185;
}

body{
    min-height:100vh;

    background:
        radial-gradient(
            circle at 80% 10%,
            rgba(34,197,94,.10),
            transparent 30%
        ),
        radial-gradient(
            circle at 20% 80%,
            rgba(74,222,128,.06),
            transparent 30%
        ),
        var(--bg);

    color:var(--text);

    font-family:'DM Sans',sans-serif;
}


/* =========================================
   BACKGROUND GRID
========================================= */

body::before{
    content:"";

    position:fixed;
    inset:0;

    background-image:
        linear-gradient(
            rgba(255,255,255,.025) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.025) 1px,
            transparent 1px
        );

    background-size:50px 50px;

    pointer-events:none;

    z-index:-1;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar{
    position:fixed;

    left:0;
    top:0;

    width:270px;
    height:100vh;

    padding:28px 18px;

    background:
        linear-gradient(
            180deg,
            rgba(8,22,14,.96),
            rgba(5,14,9,.98)
        );

    border-right:1px solid var(--border);

    z-index:1000;
}


/* =========================================
   LOGO
========================================= */

.logo{
    display:flex;
    align-items:center;
    gap:10px;

    padding:8px 12px;

    margin-bottom:38px;

    font-family:'Manrope',sans-serif;

    font-size:23px;
    font-weight:800;

    color:white;
}

.logo-icon{
    width:38px;
    height:38px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--green2)
        );

    color:#041008;

    box-shadow:
        0 8px 25px rgba(34,197,94,.25);
}

.logo span{
    color:var(--green);
}


/* =========================================
   NAVIGATION
========================================= */

.nav-title{
    padding:0 13px;

    margin-bottom:12px;

    color:#53695c;

    font-size:10px;

    font-weight:700;

    letter-spacing:1.5px;

    text-transform:uppercase;
}

.sidebar a{
    display:flex;
    align-items:center;

    gap:13px;

    padding:13px 15px;

    margin-bottom:7px;

    border-radius:13px;

    color:#91a499;

    text-decoration:none;

    font-size:14px;

    font-weight:600;

    transition:.25s ease;
}

.sidebar a i{
    width:19px;

    text-align:center;

    font-size:15px;
}

.sidebar a:hover{
    color:white;

    background:rgba(74,222,128,.07);

    transform:translateX(3px);
}

.sidebar a.active{
    color:#06100b;

    background:
        linear-gradient(
            135deg,
            var(--green),
            #34d399
        );

    box-shadow:
        0 10px 25px rgba(34,197,94,.18);
}


/* =========================================
   LOGOUT
========================================= */

.logout-link{
    position:absolute;

    left:18px;
    right:18px;
    bottom:25px;
}

.logout-link a{
    color:#9b777c;
}

.logout-link a:hover{
    color:#fecdd3;

    background:rgba(251,113,133,.08);
}


/* =========================================
   MAIN
========================================= */

.main{
    margin-left:270px;

    min-height:100vh;

    padding:32px 38px 50px;
}


/* =========================================
   TOPBAR
========================================= */

.topbar{
    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:34px;
}

.page-label{
    color:var(--green);

    font-size:11px;

    font-weight:700;

    letter-spacing:1.8px;

    text-transform:uppercase;

    margin-bottom:7px;
}

.topbar h1{
    font-family:'Manrope',sans-serif;

    font-size:32px;

    font-weight:800;

    letter-spacing:-1px;

    margin-bottom:5px;
}

.topbar p{
    color:var(--muted);

    font-size:14px;
}


/* =========================================
   USER INFO
========================================= */

.user-info{
    display:flex;

    align-items:center;

    gap:12px;

    padding:10px 15px;

    background:
        rgba(16,35,24,.72);

    border:1px solid var(--border);

    border-radius:15px;

    backdrop-filter:blur(14px);
}

.user-avatar{
    width:40px;
    height:40px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:
        rgba(74,222,128,.12);

    color:var(--green);

    font-size:16px;
}

.user-name{
    font-size:13px;

    font-weight:700;
}

.user-role{
    color:var(--muted);

    font-size:10px;

    font-weight:700;

    letter-spacing:.8px;

    text-transform:uppercase;

    margin-top:2px;
}


/* =========================================
   PAGE HEADER
========================================= */

.page-header{
    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    margin-bottom:22px;
}

.page-header h2{
    font-family:'Manrope',sans-serif;

    font-size:19px;

    font-weight:800;
}

.page-header p{
    color:var(--muted);

    font-size:13px;

    margin-top:5px;
}


/* STATUS */

.status-pill{
    display:flex;

    align-items:center;

    gap:7px;

    padding:8px 12px;

    border:1px solid
        rgba(74,222,128,.15);

    border-radius:50px;

    background:
        rgba(74,222,128,.06);

    color:#9be7b2;

    font-size:11px;

    font-weight:700;
}

.status-dot{
    width:7px;
    height:7px;

    border-radius:50%;

    background:var(--green);

    box-shadow:
        0 0 10px rgba(74,222,128,.7);
}


/* =========================================
   PANELS
========================================= */

.panel{
    padding:26px;

    margin-bottom:26px;

    background:
        linear-gradient(
            145deg,
            rgba(16,34,23,.88),
            rgba(10,25,17,.78)
        );

    border:1px solid var(--border);

    border-radius:24px;

    box-shadow:
        0 25px 60px rgba(0,0,0,.22);

    backdrop-filter:blur(18px);
}

.panel-header{
    display:flex;

    align-items:center;

    gap:13px;

    margin-bottom:23px;
}

.panel-icon{
    width:42px;
    height:42px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:13px;

    background:
        rgba(74,222,128,.10);

    color:var(--green);

    font-size:17px;
}

.panel-title h3{
    font-family:'Manrope',sans-serif;

    font-size:16px;

    font-weight:800;
}

.panel-title p{
    color:var(--muted);

    font-size:12px;

    margin-top:3px;
}


/* =========================================
   FORM
========================================= */

.form-grid{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:18px;
}

.form-group{
    margin-bottom:18px;
}

.form-group.full{
    grid-column:1 / -1;
}

label{
    display:block;

    color:#b6c9bc;

    font-size:12px;

    font-weight:600;

    margin-bottom:8px;
}

.form-control{
    width:100%;

    padding:13px 15px;

    color:white;

    background:
        rgba(4,15,9,.65);

    border:1px solid
        rgba(255,255,255,.08);

    border-radius:13px;

    outline:none;

    font-family:'DM Sans',sans-serif;

    font-size:13px;

    transition:.2s;
}

.form-control::placeholder{
    color:#53695c;
}

.form-control:focus{
    border-color:
        rgba(74,222,128,.55);

    box-shadow:
        0 0 0 3px
        rgba(74,222,128,.07);
}

textarea.form-control{
    min-height:140px;

    resize:vertical;
}


/* SELECT */

select.form-control{
    cursor:pointer;
}

select.form-control option{
    background:#0b1b11;

    color:white;
}


/* =========================================
   ADD BUTTON
========================================= */

.btn-publish{
    display:inline-flex;

    align-items:center;

    gap:9px;

    padding:13px 19px;

    border:none;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--green2)
        );

    color:#041008;

    font-family:'DM Sans',sans-serif;

    font-size:13px;

    font-weight:800;

    cursor:pointer;

    box-shadow:
        0 10px 25px
        rgba(34,197,94,.18);

    transition:.25s;
}

.btn-publish:hover{
    transform:translateY(-2px);

    box-shadow:
        0 15px 30px
        rgba(34,197,94,.25);
}


/* =========================================
   PROJECT LIST HEADER
========================================= */

.projects-header{
    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:22px;
}

.projects-count{
    padding:6px 10px;

    border-radius:8px;

    background:
        rgba(74,222,128,.08);

    color:#9be7b2;

    font-size:11px;

    font-weight:700;
}


/* =========================================
   PROJECT GRID
========================================= */

.projects-grid{
    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:20px;
}


/* =========================================
   PROJECT CARD
========================================= */

.project-card{
    position:relative;

    padding:22px;

    background:
        rgba(7,19,12,.65);

    border:1px solid
        rgba(255,255,255,.07);

    border-radius:20px;

    transition:.3s ease;
}

.project-card:hover{
    transform:translateY(-4px);

    border-color:
        rgba(74,222,128,.22);

    box-shadow:
        0 18px 35px
        rgba(0,0,0,.22);
}


/* PROJECT ICON */

.project-top{
    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:15px;

    margin-bottom:16px;
}

.project-icon{
    width:44px;
    height:44px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:13px;

    background:
        rgba(74,222,128,.08);

    color:var(--green);

    font-size:17px;
}


/* TITLE */

.project-title{
    font-family:'Manrope',sans-serif;

    font-size:18px;

    font-weight:800;

    line-height:1.3;

    color:#f1fff5;

    margin-bottom:9px;
}

.project-description{
    color:#8ea497;

    font-size:13px;

    line-height:1.65;

    white-space:pre-line;

    display:-webkit-box;

    -webkit-line-clamp:5;

    -webkit-box-orient:vertical;

    overflow:hidden;

    margin-bottom:18px;
}


/* =========================================
   STATUS BADGES
========================================= */

.status-badge{
    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:7px 11px;

    border-radius:9px;

    font-size:10px;

    font-weight:800;

    text-transform:uppercase;

    letter-spacing:.5px;
}

.status-badge::before{
    content:"";

    width:6px;
    height:6px;

    border-radius:50%;

    background:currentColor;
}


/* ONGOING */

.status-ongoing{
    background:
        rgba(96,165,250,.10);

    color:#93c5fd;

    border:1px solid
        rgba(96,165,250,.12);
}


/* COMPLETED */

.status-completed{
    background:
        rgba(74,222,128,.10);

    color:#86efac;

    border:1px solid
        rgba(74,222,128,.12);
}


/* PLANNED */

.status-planned{
    background:
        rgba(251,191,36,.09);

    color:#fcd34d;

    border:1px solid
        rgba(251,191,36,.12);
}


/* DEFAULT */

.status-default{
    background:
        rgba(148,163,184,.08);

    color:#a8b4bc;

    border:1px solid
        rgba(148,163,184,.10);
}


/* =========================================
   PROJECT FOOTER
========================================= */

.project-footer{
    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    margin-top:18px;

    padding-top:15px;

    border-top:1px solid
        rgba(255,255,255,.06);
}

.project-date{
    display:flex;

    align-items:center;

    gap:7px;

    color:#647a6c;

    font-size:10px;
}

.project-date i{
    color:#70a782;
}


/* =========================================
   DELETE BUTTON
========================================= */

.delete-btn{
    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:7px 10px;

    border-radius:9px;

    background:
        rgba(251,113,133,.06);

    color:#d88793;

    text-decoration:none;

    font-size:10px;

    font-weight:700;

    transition:.2s;
}

.delete-btn:hover{
    background:
        rgba(251,113,133,.12);

    color:#ff9aaa;

    text-decoration:none;
}


/* =========================================
   EMPTY STATE
========================================= */

.empty-state{
    padding:60px 20px;

    text-align:center;

    border:1px dashed
        rgba(255,255,255,.08);

    border-radius:18px;

    background:
        rgba(4,15,9,.3);
}

.empty-icon{
    width:64px;
    height:64px;

    display:flex;

    align-items:center;
    justify-content:center;

    margin:0 auto 15px;

    border-radius:20px;

    background:
        rgba(74,222,128,.07);

    color:#527461;

    font-size:24px;
}

.empty-state h4{
    font-family:'Manrope',sans-serif;

    font-size:15px;

    font-weight:800;

    margin-bottom:5px;
}

.empty-state p{
    color:#61776a;

    font-size:12px;
}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:1100px){

    .sidebar{
        width:230px;
    }

    .main{
        margin-left:230px;

        padding:28px;
    }

    .projects-grid{
        grid-template-columns:1fr;
    }

}


@media(max-width:800px){

    .sidebar{
        position:relative;

        width:100%;
        height:auto;

        padding:18px;

        border-right:none;

        border-bottom:
            1px solid var(--border);
    }

    .logo{
        margin-bottom:18px;
    }

    .nav-title{
        display:none;
    }

    .sidebar a{
        display:inline-flex;

        margin-right:5px;

        margin-bottom:5px;
    }

    .logout-link{
        position:static;

        margin-top:8px;
    }

    .main{
        margin-left:0;

        padding:22px 18px 40px;
    }

    .topbar{
        align-items:flex-start;

        flex-direction:column;

        gap:16px;
    }

    .topbar h1{
        font-size:27px;
    }

    .user-info{
        width:100%;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .form-group.full{
        grid-column:auto;
    }

}


@media(max-width:520px){

    .sidebar a{
        padding:10px 11px;

        font-size:12px;
    }

    .sidebar a i{
        width:auto;
    }

    .panel{
        padding:18px;

        border-radius:19px;
    }

    .page-header{
        align-items:flex-start;

        flex-direction:column;

        gap:12px;
    }

    .projects-header{
        align-items:flex-start;

        gap:10px;
    }

    .project-card{
        padding:18px;
    }

    .project-footer{
        align-items:flex-start;

        flex-direction:column;
    }

}


/* =========================================
   ACCESSIBILITY
========================================= */

@media(prefers-reduced-motion:reduce){

    *,
    *::before,
    *::after{
        scroll-behavior:auto !important;

        transition:none !important;

        animation:none !important;
    }

}

</style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<div class="sidebar">


    <div class="logo">

        <div class="logo-icon">

            <i class="fa-solid fa-bolt"></i>

        </div>

        SK <span>KONEXT</span>

    </div>


    <div class="nav-title">
        Workspace
    </div>


    <a href="dashboard.php">

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


    <a href="projects.php" class="active">

        <i class="fa-solid fa-folder-open"></i>

        Projects

    </a>


    <div class="logout-link">

        <a href="../logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>

</div>



<!-- =========================================
     MAIN
========================================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <div class="page-label">
                SK KONEXT
            </div>

            <h1>
                Projects
            </h1>

            <p>

                Manage community projects for
                <?= htmlspecialchars($barangay_name) ?>

            </p>

        </div>


        <div class="user-info">

            <div class="user-avatar">

                <i class="fa-solid fa-user"></i>

            </div>


            <div>

                <div class="user-name">

                    <?= htmlspecialchars($fullname) ?>

                </div>

                <div class="user-role">

                    <?= strtoupper(
                        str_replace('_',' ',$role)
                    ) ?>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================================
         PAGE HEADER
    ========================================= -->

    <div class="page-header">

        <div>

            <h2>
                Community Projects
            </h2>

            <p>
                Create and monitor projects in your barangay.
            </p>

        </div>


        <div class="status-pill">

            <span class="status-dot"></span>

            Project Management

        </div>

    </div>



    <!-- =========================================
         CREATE PROJECT
    ========================================= -->

    <div class="panel">


        <div class="panel-header">

            <div class="panel-icon">

                <i class="fa-solid fa-folder-plus"></i>

            </div>


            <div class="panel-title">

                <h3>
                    Create Project
                </h3>

                <p>
                    Add a new community project and its current status.
                </p>

            </div>

        </div>


        <form method="POST">


            <div class="form-grid">


                <!-- TITLE -->

                <div class="form-group">

                    <label>
                        Project Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter project title"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Project Status
                    </label>

                    <select
                        name="status"
                        class="form-control"
                        required
                    >

                        <option value="Planned">
                            Planned
                        </option>

                        <option value="Ongoing">
                            Ongoing
                        </option>

                        <option value="Completed">
                            Completed
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Project Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        placeholder="Describe the project, its purpose, activities, or expected results..."
                        required
                    ></textarea>

                </div>


            </div>


            <button
                type="submit"
                name="add_project"
                class="btn-publish">

                <i class="fa-solid fa-plus"></i>

                Add Project

            </button>


        </form>

    </div>



    <!-- =========================================
         PROJECT LIST
    ========================================= -->

    <div class="panel">


        <div class="projects-header">


            <div class="panel-header"
                 style="margin-bottom:0;">

                <div class="panel-icon">

                    <i class="fa-solid fa-folder-open"></i>

                </div>


                <div class="panel-title">

                    <h3>

                        <?= htmlspecialchars(
                            $barangay_name
                        ) ?>

                        Projects

                    </h3>

                    <p>
                        Community projects and their current status.
                    </p>

                </div>

            </div>


            <div class="projects-count">

                <?= mysqli_num_rows($projects_query) ?>

                Projects

            </div>


        </div>



        <?php if(mysqli_num_rows($projects_query) > 0){ ?>


            <div class="projects-grid">


                <?php while($project = mysqli_fetch_assoc($projects_query)){ ?>


                    <?php

                    $status_class = 'status-default';

                    if(
                        strtolower(
                            $project['status']
                        ) == 'ongoing'
                    ){

                        $status_class =
                            'status-ongoing';

                    }

                    elseif(
                        strtolower(
                            $project['status']
                        ) == 'completed'
                    ){

                        $status_class =
                            'status-completed';

                    }

                    elseif(
                        strtolower(
                            $project['status']
                        ) == 'planned'
                    ){

                        $status_class =
                            'status-planned';

                    }

                    ?>


                    <div class="project-card">


                        <div class="project-top">


                            <div class="project-icon">

                                <i class="fa-solid fa-folder-open"></i>

                            </div>


                            <span
                                class="status-badge <?= $status_class ?>"
                            >

                                <?= htmlspecialchars(
                                    $project['status']
                                ) ?>

                            </span>


                        </div>


                        <div class="project-title">

                            <?= htmlspecialchars(
                                $project['title']
                            ) ?>

                        </div>


                        <div class="project-description">

                            <?= htmlspecialchars(
                                $project['description']
                            ) ?>

                        </div>


                        <div class="project-footer">


                            <div class="project-date">

                                <i class="fa-regular fa-clock"></i>

                                Created

                                <?= date(
                                    "M d, Y • h:i A",
                                    strtotime(
                                        $project['created_at']
                                    )
                                ) ?>

                            </div>


                            <a
                                href="projects.php?delete=<?= (int)$project['id'] ?>"
                                class="delete-btn"
                                onclick="return confirm('Delete this project?');"
                            >

                                <i class="fa-solid fa-trash"></i>

                                Delete

                            </a>


                        </div>


                    </div>


                <?php } ?>


            </div>


        <?php }else{ ?>


            <div class="empty-state">


                <div class="empty-icon">

                    <i class="fa-solid fa-folder-open"></i>

                </div>


                <h4>
                    No projects yet
                </h4>


                <p>
                    Create your first community project above.
                </p>


            </div>


        <?php } ?>


    </div>


</div>


</body>

</html>