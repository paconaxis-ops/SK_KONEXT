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
   ADD ANNOUNCEMENT
========================================= */

if(isset($_POST['add_post'])){

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['title']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    $image = "";


    /* IMAGE UPLOAD */

    if(isset($_FILES['image']) &&
       $_FILES['image']['error'] == 0){

        $upload_dir = "../Superadmin/Uploads/";

        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        $filename = time() . "_" .
                    basename($_FILES['image']['name']);

        $target = $upload_dir . $filename;

        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];

        $extension = strtolower(
            pathinfo($filename, PATHINFO_EXTENSION)
        );

        if(in_array($extension, $allowed)){

            if(move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $target
            )){

                $image = $filename;

            }

        }

    }


    /* INSERT */

    mysqli_query($conn, "
        INSERT INTO barangay_posts
        (
            barangay_id,
            title,
            description,
            image,
            created_at
        )
        VALUES
        (
            '$barangay_id',
            '$title',
            '$description',
            '$image',
            NOW()
        )
    ");

    header("Location: posts.php");
    exit();
}


/* =========================================
   DELETE ANNOUNCEMENT
========================================= */

if(isset($_GET['delete'])){

    $post_id = (int) $_GET['delete'];

    /*
       IMPORTANT:
       Barangay restriction prevents
       deleting another Barangay's post.
    */

    $delete_query = mysqli_query($conn, "
        SELECT image
        FROM barangay_posts
        WHERE id = '$post_id'
        AND barangay_id = '$barangay_id'
        LIMIT 1
    ");

    if(mysqli_num_rows($delete_query) > 0){

        $post = mysqli_fetch_assoc($delete_query);

        if(!empty($post['image'])){

            $image_path =
                "../Superadmin/Uploads/" .
                $post['image'];

            if(file_exists($image_path)){
                unlink($image_path);
            }

        }

        mysqli_query($conn, "
            DELETE FROM barangay_posts
            WHERE id = '$post_id'
            AND barangay_id = '$barangay_id'
        ");

    }

    header("Location: posts.php");
    exit();
}


/* =========================================
   GET ANNOUNCEMENTS
========================================= */

$posts_query = mysqli_query($conn, "
    SELECT *
    FROM barangay_posts
    WHERE barangay_id = '$barangay_id'
    ORDER BY created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
<?= htmlspecialchars($barangay_name) ?> | Announcements
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
    --bg2:#091810;
    --panel:rgba(15,31,22,.78);
    --panel2:rgba(18,39,27,.72);
    --border:rgba(255,255,255,.08);
    --text:#f4fff7;
    --muted:#8da497;
    --green:#4ade80;
    --green2:#22c55e;
    --danger:#fb7185;
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
    top:0;
    left:0;

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


/* BRAND */

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


/* NAV */

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


/* LOGOUT */

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
    padding:32px 38px 50px;
    min-height:100vh;
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


/* USER */

.user-info{
    display:flex;
    align-items:center;
    gap:12px;

    padding:10px 15px;

    background:rgba(16,35,24,.72);

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

    background:rgba(74,222,128,.12);

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

.status-pill{
    display:flex;
    align-items:center;
    gap:7px;

    padding:8px 12px;

    border:1px solid rgba(74,222,128,.15);
    border-radius:50px;

    background:rgba(74,222,128,.06);

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

    background:rgba(74,222,128,.10);

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

    border:1px solid rgba(255,255,255,.08);

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


/* FILE */

.file-wrapper{
    position:relative;
}

.file-wrapper input{
    padding:11px 13px;
}

input[type="file"]{
    color:#83988b;
}

input[type="file"]::file-selector-button{
    margin-right:10px;

    padding:7px 12px;

    border:none;
    border-radius:8px;

    background:rgba(74,222,128,.10);

    color:#9be7b2;

    font-family:'DM Sans',sans-serif;
    font-size:11px;
    font-weight:700;

    cursor:pointer;
}


/* BUTTON */

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
        0 10px 25px rgba(34,197,94,.18);

    transition:.25s;
}

.btn-publish:hover{
    transform:translateY(-2px);

    box-shadow:
        0 15px 30px rgba(34,197,94,.25);
}


/* =========================================
   ANNOUNCEMENT HEADER
========================================= */

.announcement-header{
    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-bottom:22px;
}

.announcement-count{
    padding:6px 10px;

    border-radius:8px;

    background:rgba(74,222,128,.08);

    color:#9be7b2;

    font-size:11px;
    font-weight:700;
}


/* =========================================
   POSTS GRID
========================================= */

.posts-grid{
    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:20px;
}


/* =========================================
   POST CARD
========================================= */

.post-card{
    overflow:hidden;

    background:
        rgba(7,19,12,.65);

    border:1px solid rgba(255,255,255,.07);

    border-radius:20px;

    transition:.3s ease;
}

.post-card:hover{
    transform:translateY(-4px);

    border-color:
        rgba(74,222,128,.22);

    box-shadow:
        0 18px 35px rgba(0,0,0,.22);
}


/* IMAGE */

.image-wrapper{
    position:relative;

    height:210px;

    overflow:hidden;

    background:#0b1b11;
}

.post-image{
    width:100%;
    height:100%;

    object-fit:cover;

    display:block;

    transition:.4s ease;
}

.post-card:hover .post-image{
    transform:scale(1.04);
}

.image-overlay{
    position:absolute;
    inset:0;

    background:
        linear-gradient(
            to top,
            rgba(4,12,7,.65),
            transparent 55%
        );
}


/* NO IMAGE */

.no-image{
    height:210px;

    display:flex;
    flex-direction:column;

    align-items:center;
    justify-content:center;

    color:#4c6254;

    background:
        radial-gradient(
            circle,
            rgba(74,222,128,.05),
            transparent 60%
        );
}

.no-image i{
    font-size:30px;
    margin-bottom:8px;
}

.no-image span{
    font-size:11px;
}


/* POST CONTENT */

.post-content{
    padding:20px;
}

.post-title{
    font-family:'Manrope',sans-serif;

    font-size:18px;
    font-weight:800;

    line-height:1.3;

    color:#f1fff5;

    margin-bottom:9px;
}

.post-description{
    color:#8ea497;

    font-size:13px;
    line-height:1.65;

    white-space:pre-line;

    display:-webkit-box;
    -webkit-line-clamp:4;
    -webkit-box-orient:vertical;

    overflow:hidden;
}


/* FOOTER */

.post-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-top:18px;
    padding-top:15px;

    border-top:1px solid rgba(255,255,255,.06);
}

.post-date{
    display:flex;
    align-items:center;
    gap:7px;

    color:#647a6c;

    font-size:10px;
}

.post-date i{
    color:#70a782;
}


/* DELETE */

.delete-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;

    padding:7px 10px;

    border-radius:9px;

    background:rgba(251,113,133,.06);

    color:#d88793;

    text-decoration:none;

    font-size:10px;
    font-weight:700;

    transition:.2s;
}

.delete-btn:hover{
    background:rgba(251,113,133,.12);
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

    background:rgba(74,222,128,.07);

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

    .posts-grid{
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
        border-bottom:1px solid var(--border);
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

    .posts-grid{
        grid-template-columns:1fr;
    }

    .image-wrapper,
    .no-image{
        height:180px;
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


    <a href="posts.php" class="active">

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
                Announcements
            </h1>

            <p>
                Manage community announcements for
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
         CREATE ANNOUNCEMENT
    ========================================= -->

    <div class="page-header">

        <div>

            <h2>
                Publish an Announcement
            </h2>

            <p>
                Share important updates with your community.
            </p>

        </div>


        <div class="status-pill">

            <span class="status-dot"></span>

            Community Updates

        </div>

    </div>



    <div class="panel">


        <div class="panel-header">

            <div class="panel-icon">

                <i class="fa-solid fa-pen-to-square"></i>

            </div>


            <div class="panel-title">

                <h3>
                    Create Announcement
                </h3>

                <p>
                    Add a title, message, and optional image.
                </p>

            </div>

        </div>



        <form method="POST"
              enctype="multipart/form-data">


            <div class="form-grid">


                <!-- TITLE -->

                <div class="form-group">

                    <label>
                        Announcement Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter announcement title"
                        required
                    >

                </div>


                <!-- IMAGE -->

                <div class="form-group">

                    <label>
                        Cover Image
                    </label>

                    <div class="file-wrapper">

                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept="image/*"
                        >

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        placeholder="Write your announcement here..."
                        required
                    ></textarea>

                </div>


            </div>


            <button
                type="submit"
                name="add_post"
                class="btn-publish">

                <i class="fa-solid fa-paper-plane"></i>

                Publish Announcement

            </button>


        </form>

    </div>



    <!-- =========================================
         ANNOUNCEMENT LIST
    ========================================= -->

    <div class="panel">


        <div class="announcement-header">

            <div class="panel-header"
                 style="margin-bottom:0;">

                <div class="panel-icon">

                    <i class="fa-solid fa-bullhorn"></i>

                </div>


                <div class="panel-title">

                    <h3>

                        <?= htmlspecialchars(
                            $barangay_name
                        ) ?>

                        Announcements

                    </h3>

                    <p>
                        Published community updates
                    </p>

                </div>

            </div>


            <div class="announcement-count">

                <?= mysqli_num_rows($posts_query) ?>

                Updates

            </div>

        </div>



        <?php if(mysqli_num_rows($posts_query) > 0){ ?>


            <div class="posts-grid">


                <?php while($post = mysqli_fetch_assoc($posts_query)){ ?>


                    <div class="post-card">


                        <!-- IMAGE -->

                        <?php if(!empty($post['image'])){ ?>

                            <div class="image-wrapper">

                                <img
                                    src="../Superadmin/Uploads/<?= htmlspecialchars($post['image']) ?>"
                                    class="post-image"
                                    alt="Announcement Image"
                                >

                                <div class="image-overlay"></div>

                            </div>

                        <?php }else{ ?>

                            <div class="no-image">

                                <i class="fa-regular fa-image"></i>

                                <span>
                                    No image attached
                                </span>

                            </div>

                        <?php } ?>


                        <!-- CONTENT -->

                        <div class="post-content">


                            <div class="post-title">

                                <?= htmlspecialchars(
                                    $post['title']
                                ) ?>

                            </div>


                            <div class="post-description">

                                <?= htmlspecialchars(
                                    $post['description']
                                ) ?>

                            </div>


                            <div class="post-footer">


                                <div class="post-date">

                                    <i class="fa-regular fa-clock"></i>

                                    <?= date(
                                        "M d, Y • h:i A",
                                        strtotime(
                                            $post['created_at']
                                        )
                                    ) ?>

                                </div>


                                <a
                                    href="posts.php?delete=<?= (int)$post['id'] ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Delete this announcement?');"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                    Delete

                                </a>


                            </div>


                        </div>


                    </div>


                <?php } ?>


            </div>


        <?php }else{ ?>


            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-solid fa-bullhorn"></i>

                </div>


                <h4>
                    No announcements yet
                </h4>


                <p>
                    Create your first community announcement above.
                </p>

            </div>


        <?php } ?>


    </div>


</div>


</body>

</html>