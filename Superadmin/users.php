<?php

include("../db.php");

include("../includes/auth.php");

allowRoles(['superadmin']);

/* =========================================
   ADD USER
========================================= */

if(isset($_POST['add_user'])){

    $fullname = mysqli_real_escape_string($conn,$_POST['fullname']);
    $username = mysqli_real_escape_string($conn,$_POST['username']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);
    $role = mysqli_real_escape_string($conn,$_POST['role']);
    $barangay_id = mysqli_real_escape_string($conn,$_POST['barangay_id']);

    /* =========================================
       CHECK ONE SK CHAIRMAN PER BARANGAY
    ========================================= */

    if($role == 'sk_member'){

        $check_chairman = mysqli_query($conn,"
        SELECT id
        FROM users
        WHERE role='sk_member'
        AND barangay_id='$barangay_id'
        AND status='active'
        LIMIT 1
        ");

        if(mysqli_num_rows($check_chairman) > 0){

            $error = "This barangay already has an assigned SK Chairman.";

        }else{

            mysqli_query($conn,"
            INSERT INTO users(
            fullname,
            username,
            password,
            role,
            barangay_id,
            status
            )
            VALUES(
            '$fullname',
            '$username',
            '$password',
            '$role',
            '$barangay_id',
            'active'
            )
            ");

            $admin_id = $_SESSION['user_id'];

            mysqli_query($conn,"
            INSERT INTO audit_logs(user_id,action)
            VALUES(
            '$admin_id',
            'Created new user: $fullname'
            )
            ");

            $success = "User Added Successfully";
        }

    }else{

        mysqli_query($conn,"
        INSERT INTO users(
        fullname,
        username,
        password,
        role,
        barangay_id,
        status
        )
        VALUES(
        '$fullname',
        '$username',
        '$password',
        '$role',
        '$barangay_id',
        'active'
        )
        ");

        $admin_id = $_SESSION['user_id'];

        mysqli_query($conn,"
        INSERT INTO audit_logs(user_id,action)
        VALUES(
        '$admin_id',
        'Created new user: $fullname'
        )
        ");

        $success = "User Added Successfully";
    }
}


/* =========================================
   DELETE USER
========================================= */

if(isset($_GET['delete'])){

    $id = $_GET['delete'];

    mysqli_query($conn,"
    DELETE FROM users
    WHERE id='$id'
    ");

    header("Location: users.php");

    exit();
}


/* =========================================
   DISABLE USER
========================================= */

if(isset($_GET['disable'])){

    $id = $_GET['disable'];

    mysqli_query($conn,"
    UPDATE users
    SET status='disabled'
    WHERE id='$id'
    ");

    header("Location: users.php");

    exit();
}


/* =========================================
   ACTIVATE USER
========================================= */

if(isset($_GET['activate'])){

    $id = $_GET['activate'];

    /*
    CHECK IF THE USER BEING ACTIVATED
    IS AN SK CHAIRMAN
    */

    $check_user = mysqli_query($conn,"
    SELECT role, barangay_id
    FROM users
    WHERE id='$id'
    LIMIT 1
    ");

    $user_data = mysqli_fetch_assoc($check_user);

    /*
    IF SK CHAIRMAN, CHECK IF ANOTHER
    ACTIVE SK CHAIRMAN ALREADY EXISTS
    */

    if($user_data && $user_data['role'] == 'sk_member'){

        $barangay_id = $user_data['barangay_id'];

        $check_existing = mysqli_query($conn,"
        SELECT id
        FROM users
        WHERE role='sk_member'
        AND barangay_id='$barangay_id'
        AND status='active'
        AND id!='$id'
        LIMIT 1
        ");

        if(mysqli_num_rows($check_existing) > 0){

            $error = "This barangay already has an active SK Chairman.";

        }else{

            mysqli_query($conn,"
            UPDATE users
            SET status='active'
            WHERE id='$id'
            ");

            header("Location: users.php");

            exit();
        }

    }else{

        mysqli_query($conn,"
        UPDATE users
        SET status='active'
        WHERE id='$id'
        ");

        header("Location: users.php");

        exit();
    }
}


/* =========================================
   SEARCH
========================================= */

$search = "";

if(isset($_GET['search'])){

    $search = $_GET['search'];

    $users = mysqli_query($conn,"
    SELECT users.*, barangays.name AS barangay_name
    FROM users
    LEFT JOIN barangays
    ON barangays.id = users.barangay_id
    WHERE
    fullname LIKE '%$search%'
    OR username LIKE '%$search%'
    OR role LIKE '%$search%'
    ORDER BY users.id DESC
    ");

}else{

    $users = mysqli_query($conn,"
    SELECT users.*, barangays.name AS barangay_name
    FROM users
    LEFT JOIN barangays
    ON barangays.id = users.barangay_id
    ORDER BY users.id DESC
    ");
}


/* =========================================
   STATS
========================================= */

$stats = mysqli_query($conn,"
SELECT

(SELECT COUNT(*) FROM users) as total_users,

(SELECT COUNT(*) FROM users WHERE role='president') as presidents,

(SELECT COUNT(*) FROM users WHERE role='sk_member') as members,

(SELECT COUNT(*) FROM users WHERE status='active') as active_users

");

$stat = mysqli_fetch_assoc($stats);


/* =========================================
   BARANGAYS
========================================= */

$barangays = mysqli_query($conn,"
SELECT * FROM barangays
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="utf-8">

<title>Manage Users | SK KONEXT</title>

<meta name="viewport"
content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<!-- FONT AWESOME -->

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- GOOGLE FONTS -->

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
rel="stylesheet">


<style>

/* =====================================================
   GLOBAL
===================================================== */

*{

    margin:0;
    padding:0;
    box-sizing:border-box;

}

html{

    scroll-behavior:smooth;

}

body{

    font-family:'DM Sans',sans-serif;
    background:#07100d;
    color:#e8f1ed;
    min-height:100vh;

}


/* =====================================================
   BACKGROUND
===================================================== */

body::before{

    content:"";

    position:fixed;

    inset:0;

    background:

        radial-gradient(
            circle at 10% 10%,
            rgba(34,197,94,.10),
            transparent 30%
        ),

        radial-gradient(
            circle at 90% 20%,
            rgba(16,185,129,.08),
            transparent 28%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(34,197,94,.06),
            transparent 35%
        );

    pointer-events:none;

    z-index:-1;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar{

    position:fixed;

    left:18px;
    top:18px;

    width:255px;

    height:calc(100vh - 36px);

    background:

        linear-gradient(
            180deg,
            rgba(18,34,28,.97),
            rgba(9,22,17,.98)
        );

    border:1px solid rgba(148,163,184,.12);

    border-radius:28px;

    padding:24px 16px;

    overflow-y:auto;

    box-shadow:
        0 25px 70px rgba(0,0,0,.35);

    backdrop-filter:blur(20px);

    z-index:1000;

}


.sidebar::-webkit-scrollbar{

    width:5px;

}


.sidebar::-webkit-scrollbar-track{

    background:transparent;

}


.sidebar::-webkit-scrollbar-thumb{

    background:
        rgba(148,163,184,.18);

    border-radius:20px;

}


/* =====================================================
   LOGO
===================================================== */

.navbar-brand{

    display:flex !important;
    align-items:center;
    gap:11px;
    padding:10px 13px !important;
    margin-bottom:25px;

    color:#f4fff9 !important;
    font-family:'Manrope',sans-serif;
    font-size:20px;
    font-weight:800;
    letter-spacing:-.5px;

    text-decoration:none !important;

}

.navbar-brand i{

    width:38px;
    height:38px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:linear-gradient(
        135deg,
        #22c55e,
        #059669
    );

    color:white;

    box-shadow:0 8px 25px rgba(34,197,94,.20);

}


/* =====================================================
   SIDEBAR LINKS
===================================================== */

.sidebar a:not(.navbar-brand){

    position:relative;

    display:flex;
    align-items:center;
    gap:12px;

    padding:13px 14px;
    margin:5px 0;

    border-radius:14px;

    color:#a9bcb3;
    font-size:14px;
    font-weight:500;

    text-decoration:none;

    transition:
        background .25s ease,
        color .25s ease,
        transform .25s ease;

}

.sidebar a:not(.navbar-brand) i{

    width:21px;
    text-align:center;
    font-size:15px;
    color:#71867b;

    transition:.25s;

}

.sidebar a:not(.navbar-brand):hover{

    background:rgba(34,197,94,.08);
    color:#f1fff6;

    transform:translateX(3px);

}

.sidebar a:not(.navbar-brand):hover i{

    color:#4ade80;

}


/* ACTIVE MANAGE USERS */

.sidebar a[href="users.php"]{

    background:linear-gradient(
        135deg,
        rgba(34,197,94,.20),
        rgba(16,185,129,.08)
    );

    color:#ecfff3;

    border:1px solid rgba(74,222,128,.13);

}

.sidebar a[href="users.php"] i{

    color:#4ade80;

}


/* =====================================================
   MAIN
===================================================== */

.main{

    margin-left:291px;

    padding:
        28px
        32px
        55px
        32px;

    min-height:100vh;

    max-width:1650px;

}


/* =====================================================
   TOPBAR
===================================================== */

.topbar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    margin-bottom:34px;

}


.topbar h2{

    margin:0;

    font-family:'Manrope',sans-serif;

    font-size:32px;

    font-weight:800;

    letter-spacing:-1px;

    color:#f4fbf7;

}


.topbar p{

    margin:8px 0 0;

    color:#81978d;

    font-size:14px;

}


.topbar h2::before{

    content:"";

    display:inline-block;

    width:8px;

    height:8px;

    margin-right:10px;

    margin-bottom:5px;

    border-radius:50%;

    background:#4ade80;

    box-shadow:
        0 0 12px rgba(74,222,128,.7);

}


/* =====================================================
   STAT CARDS
===================================================== */

.stat-card{

    position:relative;

    height:100%;

    min-height:165px;

    padding:23px;

    border-radius:22px;

    background:

        linear-gradient(
            145deg,
            rgba(18,34,28,.94),
            rgba(10,23,18,.88)
        );

    border:1px solid
        rgba(148,163,184,.10);

    box-shadow:
        0 20px 45px rgba(0,0,0,.18);

    overflow:hidden;

    transition:
        transform .3s ease,
        border-color .3s ease,
        box-shadow .3s ease;

}


.stat-card::after{

    content:"";

    position:absolute;

    width:130px;
    height:130px;

    right:-65px;
    bottom:-65px;

    border-radius:50%;

    background:

        radial-gradient(
            circle,
            rgba(34,197,94,.13),
            transparent 70%
        );

}


.stat-card:hover{

    transform:translateY(-5px);

    border-color:
        rgba(74,222,128,.18);

    box-shadow:
        0 25px 55px rgba(0,0,0,.27);

}


.stat-card h3{

    margin:0 0 5px;

    font-family:'Manrope',sans-serif;

    font-size:32px;

    line-height:1;

    font-weight:800;

    color:#f4fbf7;

}


.stat-card p{

    margin:8px 0 0;

    color:#82968d;

    font-size:13px;

}


/* STAT ICON */

.stat-card::before{

    position:absolute;

    right:22px;
    top:22px;

    width:45px;
    height:45px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:13px;

    background:
        rgba(34,197,94,.09);

    color:#4ade80;

}


/* =====================================================
   PANELS
===================================================== */

.panel{

    position:relative;

    background:

        linear-gradient(
            145deg,
            rgba(18,34,28,.94),
            rgba(9,22,17,.91)
        );

    border:1px solid
        rgba(148,163,184,.10);

    border-radius:24px;

    margin-top:25px;

    padding:26px;

    box-shadow:
        0 20px 50px rgba(0,0,0,.18);

    overflow:hidden;

}


.panel::before{

    content:"";

    position:absolute;

    top:0;
    left:0;

    width:100%;
    height:1px;

    background:

        linear-gradient(
            90deg,
            transparent,
            rgba(74,222,128,.22),
            transparent
        );

}


.panel h4{

    margin:0;

    color:#effaf4;

    font-family:'Manrope',sans-serif;

    font-size:18px;

    font-weight:700;

}


/* =====================================================
   FORM
===================================================== */

.form-control{

    height:48px !important;

    border-radius:12px !important;

    border:1px solid
        rgba(148,163,184,.10) !important;

    background:
        rgba(255,255,255,.045) !important;

    color:#e4f0ea !important;

    box-shadow:none !important;

    transition:
        border-color .2s,
        box-shadow .2s;

}


.form-control::placeholder{

    color:#6f857a !important;

}


.form-control:focus{

    border-color:
        rgba(74,222,128,.35) !important;

    box-shadow:
        0 0 0 3px
        rgba(34,197,94,.07) !important;

}


select.form-control option{

    background:#102019;

    color:#fff;

}


/* =====================================================
   MODERN BUTTON
===================================================== */

.btn-modern{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    min-height:46px;

    padding:11px 18px;

    border:1px solid
        rgba(74,222,128,.15);

    border-radius:12px;

    background:

        linear-gradient(
            135deg,
            #22c55e,
            #059669
        );

    color:white;

    font-size:13px;

    font-weight:700;

    box-shadow:
        0 8px 25px rgba(34,197,94,.14);

    transition:
        transform .2s,
        box-shadow .2s;

}


.btn-modern:hover{

    color:white;

    transform:translateY(-2px);

    box-shadow:
        0 12px 30px
        rgba(34,197,94,.22);

}


/* =====================================================
   ALERT
===================================================== */

.alert-success{

    background:
        rgba(34,197,94,.08);

    color:#8aefae;

    border:
        1px solid rgba(74,222,128,.16);

    border-radius:12px;

    font-size:13px;

}


/* =====================================================
   SEARCH PANEL
===================================================== */

.search-panel{

    display:flex;

    align-items:center;

    gap:12px;

}


.search-panel .form-control{

    flex:1;

}


/* =====================================================
   TABLE
===================================================== */

.table-responsive{

    border-radius:16px;

    border:1px solid
        rgba(148,163,184,.08);

}


.table{

    margin-bottom:0;

    background:
        rgba(5,14,10,.18);

    color:#c8d8d0;

}


.table thead,

.table tr:first-child{

    background:
        rgba(255,255,255,.035);

}


.table th{

    padding:15px 13px;

    border-top:none !important;

    border-bottom:
        1px solid rgba(148,163,184,.08) !important;

    color:#82988d;

    font-size:11px;

    font-weight:800;

    text-transform:uppercase;

    letter-spacing:.5px;

    white-space:nowrap;

}


.table td{

    padding:15px 13px;

    vertical-align:middle;

    border-color:
        rgba(148,163,184,.07) !important;

    color:#c7d6cf;

    font-size:12px;

}


.table tbody tr{

    transition:
        background .2s ease;

}


.table tbody tr:hover{

    background:
        rgba(34,197,94,.035);

}


/* =====================================================
   ROLE BADGES
===================================================== */

.badge-role{

    display:inline-flex;

    align-items:center;

    padding:7px 11px;

    border-radius:20px;

    font-size:10px;

    font-weight:800;

    letter-spacing:.4px;

    border:1px solid transparent;

}


.superadmin{

    background:
        rgba(239,68,68,.10);

    color:#ff8c8c;

    border-color:
        rgba(239,68,68,.16);

}


.president{

    background:
        rgba(59,130,246,.10);

    color:#75b4ff;

    border-color:
        rgba(59,130,246,.16);

}


.skmember{

    background:
        rgba(34,197,94,.10);

    color:#70e99a;

    border-color:
        rgba(34,197,94,.16);

}


/* =====================================================
   STATUS
===================================================== */

.active{

    display:inline-flex;

    padding:6px 11px;

    border-radius:20px;

    background:
        rgba(34,197,94,.10);

    color:#70e99a;

    border:
        1px solid rgba(34,197,94,.16);

    font-size:10px;

    font-weight:800;

}


.disabled{

    display:inline-flex;

    padding:6px 11px;

    border-radius:20px;

    background:
        rgba(239,68,68,.10);

    color:#ff8c8c;

    border:
        1px solid rgba(239,68,68,.16);

    font-size:10px;

    font-weight:800;

}


/* =====================================================
   ACTION BUTTONS
===================================================== */

.action-btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:7px 11px;

    margin:2px;

    border-radius:9px;

    color:white !important;

    text-decoration:none !important;

    font-size:11px;

    font-weight:700;

    border:1px solid transparent;

    transition:
        transform .2s,
        opacity .2s;

}


.action-btn:hover{

    transform:translateY(-2px);

    color:white !important;

    opacity:.9;

}


.delete-btn{

    background:
        rgba(239,68,68,.13);

    color:#ff8585 !important;

    border-color:
        rgba(239,68,68,.18);

}


.disable-btn{

    background:
        rgba(245,158,11,.12);

    color:#f6c76a !important;

    border-color:
        rgba(245,158,11,.18);

}


.activate-btn{

    background:
        rgba(34,197,94,.12);

    color:#72e99a !important;

    border-color:
        rgba(34,197,94,.18);

}


/* =====================================================
   PANEL SPACING
===================================================== */

.panel form{

    position:relative;

    z-index:2;

}


.panel > h4{

    display:flex;

    align-items:center;

    gap:10px;

}


.panel > h4::before{

    content:"";

    width:7px;
    height:7px;

    border-radius:50%;

    background:#4ade80;

    box-shadow:
        0 0 10px rgba(74,222,128,.55);

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:991px){

    .sidebar{

        position:relative;

        left:auto;
        top:auto;

        width:calc(100% - 30px);

        height:auto;

        margin:15px;

        border-radius:22px;

    }


    .main{

        margin-left:0;

        padding:20px;

    }

}


@media(max-width:600px){

    .main{

        padding:15px;

    }


    .topbar{

        align-items:flex-start;

        flex-direction:column;

        margin-bottom:25px;

    }


    .topbar h2{

        font-size:26px;

    }


    .panel{

        padding:20px;

        border-radius:20px;

    }


    .btn-modern{

        width:100%;

    }


    .table-responsive{

        overflow-x:auto;

    }


    .action-btn{

        margin-bottom:4px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <a class="navbar-brand" href="dashboard.php">

        <i class="fa-solid fa-shield-halved"></i>

        <span>SK KONEXT</span>

    </a>


    <a href="dashboard.php">

        <i class="fa-solid fa-chart-line"></i>

        <span>Dashboard</span>

    </a>


    <a href="users.php">

        <i class="fa-solid fa-users"></i>

        <span>Manage Users</span>

    </a>


    <a href="scholars.php">

        <i class="fa-solid fa-user-graduate"></i>

        <span>Scholars</span>

    </a>


    <a href="map.php">

        <i class="fa-solid fa-map-location-dot"></i>

        <span>Map System</span>

    </a>


    <a href="reports.php">

        <i class="fa-solid fa-file-invoice"></i>

        <span>Reports</span>

    </a>

    <a href="residents.php">

<i class="fa-solid fa-users-viewfinder"></i>

<span>Resident Profiling</span>

</a>


    <a href="../logout.php">

        <i class="fa-solid fa-right-from-bracket"></i>

        <span>Logout</span>

    </a>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


<!-- TOPBAR -->

<div class="topbar">

<div>

<h2>

User Governance Management

</h2>

<p>

Manage all governance accounts

</p>

</div>

</div>


<!-- =====================================================
     STATS
===================================================== -->

<div class="row">


<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<h3>

<?= $stat['total_users'] ?>

</h3>

<p>

Total Users

</p>

</div>

</div>


<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<h3>

<?= $stat['presidents'] ?>

</h3>

<p>

Presidents

</p>

</div>

</div>


<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<h3>

<?= $stat['members'] ?>

</h3>

<p>

SK Chairmen

</p>

</div>

</div>


<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<h3>

<?= $stat['active_users'] ?>

</h3>

<p>

Active Users

</p>

</div>

</div>


</div>


<!-- =====================================================
     ADD USER
===================================================== -->

<div class="panel">

<h4>

Create Governance Account

</h4>

<br>


<?php if(isset($success)){ ?>

<div class="alert alert-success">

<i class="fa-solid fa-circle-check"></i>

<?= htmlspecialchars($success) ?>

</div>

<?php } ?>


<?php if(isset($error)){ ?>

<div class="alert alert-danger">

<i class="fa-solid fa-circle-exclamation"></i>

<?= htmlspecialchars($error) ?>

</div>

<?php } ?>


<form method="POST">


<div class="row">


<div class="col-md-6">

<input

type="text"

name="fullname"

class="form-control mb-3"

placeholder="Full Name"

required>

</div>


<div class="col-md-6">

<input

type="text"

name="username"

class="form-control mb-3"

placeholder="Username"

required>

</div>


<div class="col-md-6">

<input

type="password"

name="password"

class="form-control mb-3"

placeholder="Password"

required>

</div>


<div class="col-md-3">

<select

name="role"

class="form-control mb-3"

required>

<option value="">

Select Role

</option>

<option value="president">

President

</option>

<option value="sk_member">

SK Chairman

</option>

</select>

</div>


<div class="col-md-3">

<select

name="barangay_id"

class="form-control mb-3"

required>

<option value="">

Barangay

</option>


<?php while($b=mysqli_fetch_assoc($barangays)){ ?>

<option value="<?= $b['id'] ?>">

<?= htmlspecialchars($b['name']) ?>

</option>

<?php } ?>


</select>

</div>


</div>


<button

type="submit"

name="add_user"

class="btn-modern">

<i class="fa-solid fa-user-plus"></i>

Create User

</button>


</form>

</div>


<!-- =====================================================
     SEARCH
===================================================== -->

<div class="panel">


<form method="GET">

<div class="row align-items-center">


<div class="col-md-10 mb-2 mb-md-0">

<input

type="text"

name="search"

class="form-control"

placeholder="Search users..."

value="<?= htmlspecialchars($search) ?>">

</div>


<div class="col-md-2">

<button

class="btn-modern btn-block">

<i class="fa-solid fa-search"></i>

Search

</button>

</div>


</div>

</form>


</div>


<!-- =====================================================
     USER TABLE
===================================================== -->

<div class="panel">


<h4>

Governance Users

</h4>

<br>


<div class="table-responsive">


<table class="table">


<thead>

<tr>

<th>ID</th>

<th>Fullname</th>

<th>Username</th>

<th>Role</th>

<th>Barangay</th>

<th>Status</th>

<th>Actions</th>

</tr>

</thead>


<tbody>


<?php while($u=mysqli_fetch_assoc($users)){ ?>


<tr>


<td>

<?= htmlspecialchars($u['id']) ?>

</td>


<td>

<strong style="color:#edf7f2;">

<?= htmlspecialchars($u['fullname']) ?>

</strong>

</td>


<td>

<?= htmlspecialchars($u['username']) ?>

</td>


<td>


<?php if($u['role']=="superadmin"){ ?>

<span class="badge-role superadmin">

SUPERADMIN

</span>

<?php } ?>


<?php if($u['role']=="president"){ ?>

<span class="badge-role president">

PRESIDENT

</span>

<?php } ?>


<?php if($u['role']=="sk_member"){ ?>

<span class="badge-role skmember">

SK CHAIRMAN

</span>

<?php } ?>


</td>


<td>

<?= htmlspecialchars($u['barangay_name'] ?? 'Not Assigned') ?>

</td>


<td>


<?php if($u['status']=="active"){ ?>

<span class="active">

Active

</span>

<?php }else{ ?>

<span class="disabled">

Disabled

</span>

<?php } ?>


</td>


<td>


<?php if($u['status']=="active"){ ?>


<a

href="?disable=<?= $u['id'] ?>"

class="action-btn disable-btn">

Disable

</a>


<?php }else{ ?>


<a

href="?activate=<?= $u['id'] ?>"

class="action-btn activate-btn">

Activate

</a>


<?php } ?>


<a

href="?delete=<?= $u['id'] ?>"

class="action-btn delete-btn"

onclick="return confirm('Delete this user?')">

Delete

</a>


</td>


</tr>


<?php } ?>


</tbody>

</table>


</div>


</div>


</div>


</body>

</html>