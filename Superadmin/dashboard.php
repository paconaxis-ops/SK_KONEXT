
<?php

include("../db.php");
include("../includes/auth.php");

/* =========================================
   SUPERADMIN ONLY
========================================= */

allowRoles(['superadmin']);

/* =========================================
   STATS
========================================= */

$stats = mysqli_query($conn,"
SELECT
(SELECT COUNT(*) FROM users) as users,
(SELECT COUNT(*) FROM scholars) as scholars,
(SELECT COUNT(*) FROM barangays) as barangays,
(SELECT COUNT(*) FROM records) as records
");

$stat = mysqli_fetch_assoc($stats);

/* =========================================
   LOGIN ACTIVITY LOGS - PAGINATION
========================================= */

// Sorting
$log_sort = strtoupper($_GET['log_sort'] ?? 'DESC');

if(!in_array($log_sort, ['ASC', 'DESC'])){
    $log_sort = 'DESC';
}

// Pagination settings
$records_per_page = 10;

$current_page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if($current_page < 1){
    $current_page = 1;
}

// Get total login records
$count_query = mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM login_logs
");

$total_logs = 0;

if($count_query){
    $count_data = mysqli_fetch_assoc($count_query);
    $total_logs = (int)$count_data['total'];
}

// Calculate total pages
$total_pages = max(
    1,
    (int)ceil($total_logs / $records_per_page)
);

// Prevent invalid page
if($current_page > $total_pages){
    $current_page = $total_pages;
}

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Fetch only 10 records
$logs = mysqli_query($conn,"
    SELECT *
    FROM login_logs
    ORDER BY created_at $log_sort, id $log_sort
    LIMIT $records_per_page OFFSET $offset
");

// Display range
$showing_start = $total_logs > 0
    ? $offset + 1
    : 0;

$showing_end = min(
    $offset + $records_per_page,
    $total_logs
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">

<title>Superadmin Dashboard | SK KONEXT</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- GOOGLE FONTS -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
rel="stylesheet">

<link rel="stylesheet" href="assets/style.css">


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
            rgba(18,34,28,.96),
            rgba(9,22,17,.97)
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


/* =====================================================
   SIDEBAR SCROLLBAR
===================================================== */

.sidebar::-webkit-scrollbar{
    width:5px;
}

.sidebar::-webkit-scrollbar-track{
    background:transparent;
}

.sidebar::-webkit-scrollbar-thumb{
    background:rgba(148,163,184,.18);
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

    background:
        linear-gradient(
            135deg,
            #22c55e,
            #059669
        );

    color:white;

    box-shadow:
        0 8px 25px rgba(34,197,94,.20);

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


/* ACTIVE DASHBOARD */

.sidebar a[href="dashboard.php"]{

    background:
        linear-gradient(
            135deg,
            rgba(34,197,94,.20),
            rgba(16,185,129,.08)
        );

    color:#ecfff3;

    border:1px solid rgba(74,222,128,.13);

}

.sidebar a[href="dashboard.php"] i{

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

    max-width:1600px;

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


.topbar-left{

    position:relative;

}


.topbar-eyebrow{

    display:flex;

    align-items:center;

    gap:8px;

    color:#4ade80;

    text-transform:uppercase;

    font-size:11px;

    font-weight:700;

    letter-spacing:2px;

    margin-bottom:8px;

}


.topbar-eyebrow span{

    width:7px;
    height:7px;

    border-radius:50%;

    background:#4ade80;

    box-shadow:
        0 0 12px rgba(74,222,128,.7);

}


.topbar h2{

    margin:0;

    font-family:'Manrope',sans-serif;

    font-size:34px;

    line-height:1.1;

    font-weight:800;

    letter-spacing:-1.2px;

    color:#f4fbf7;

}


.topbar p{

    margin:9px 0 0;

    color:#81978d;

    font-size:14px;

}


/* =====================================================
   ADMIN BADGE
===================================================== */

.admin-badge{

    display:flex;

    align-items:center;

    gap:10px;

    padding:10px 15px;

    border-radius:16px;

    background:rgba(18,34,28,.75);

    border:1px solid rgba(148,163,184,.12);

    color:#dcebe3;

    font-size:13px;

    font-weight:600;

    backdrop-filter:blur(15px);

}


.admin-badge::before{

    content:"";

    width:8px;
    height:8px;

    border-radius:50%;

    background:#4ade80;

    box-shadow:
        0 0 10px rgba(74,222,128,.7);

}


/* =====================================================
   STAT GRID
===================================================== */

.stat-card{

    position:relative;

    height:100%;

    min-height:175px;

    padding:23px;

    border-radius:22px;

    background:
        linear-gradient(
            145deg,
            rgba(18,34,28,.92),
            rgba(10,23,18,.86)
        );

    border:1px solid rgba(148,163,184,.10);

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

    width:120px;
    height:120px;

    right:-55px;
    bottom:-55px;

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


.stat-icon{

    width:48px;
    height:48px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:15px;

    margin-bottom:20px;

    background:
        rgba(34,197,94,.10);

    border:1px solid
        rgba(74,222,128,.10);

    color:#4ade80;

    font-size:19px;

}


.stat-card h3{

    margin:0 0 4px;

    font-family:'Manrope',sans-serif;

    color:#f4fbf7;

    font-size:32px;

    line-height:1;

    font-weight:800;

}


.stat-card p{

    margin:8px 0 0;

    color:#82968d;

    font-size:13px;

}


/* =====================================================
   PANEL
===================================================== */

.panel{

    position:relative;

    background:
        linear-gradient(
            145deg,
            rgba(18,34,28,.94),
            rgba(9,22,17,.91)
        );

    border:1px solid rgba(148,163,184,.10);

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


.panel-heading{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    margin-bottom:18px;

}


.panel-heading-left{

    display:flex;

    align-items:center;

    gap:11px;

}


.panel-heading-icon{

    width:38px;
    height:38px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:rgba(34,197,94,.09);

    color:#4ade80;

    font-size:15px;

}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions{

    display:grid;

    grid-template-columns:
        repeat(5,minmax(0,1fr));

    gap:12px;

}


.quick-btn{

    display:flex;

    align-items:center;

    gap:11px;

    min-height:52px;

    padding:13px 15px;

    background:
        rgba(255,255,255,.025);

    border:1px solid
        rgba(148,163,184,.10);

    color:#c7d8cf !important;

    border-radius:14px;

    text-decoration:none !important;

    font-size:13px;

    font-weight:600;

    transition:
        transform .25s ease,
        background .25s ease,
        border-color .25s ease;

}


.quick-btn i{

    width:30px;
    height:30px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:9px;

    background:
        rgba(34,197,94,.09);

    color:#4ade80;

    font-size:13px;

}


.quick-btn:hover{

    transform:translateY(-3px);

    background:
        rgba(34,197,94,.07);

    border-color:
        rgba(74,222,128,.20);

    color:#f0fff5 !important;

}


.quick-btn:hover i{

    background:
        rgba(34,197,94,.14);

}


/* =====================================================
   ACTIVITY HEADER
===================================================== */

.activity-count{

    padding:6px 10px;

    border-radius:20px;

    background:
        rgba(34,197,94,.08);

    color:#72e99a;

    font-size:11px;

    font-weight:700;

}


/* =====================================================
   ACTIVITY ITEM
===================================================== */

.activity-item{

    position:relative;

    display:flex;

    align-items:center;

    gap:15px;

    padding:16px 5px;

    border-bottom:
        1px solid rgba(148,163,184,.08);

}


.activity-item:last-child{

    border-bottom:none;

}


.activity-icon{

    flex:0 0 auto;

    width:42px;
    height:42px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:13px;

    background:
        rgba(34,197,94,.08);

    border:1px solid
        rgba(74,222,128,.08);

    color:#4ade80;

}


.activity-content{

    min-width:0;

    flex:1;

}


.activity-title{

    color:#e8f4ed;

    font-size:14px;

    font-weight:600;

    margin-bottom:5px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.activity-meta{

    color:#72877d;

    font-size:12px;

    line-height:1.8;

}


.activity-meta i{

    margin-right:3px;

}


.activity-empty{

    padding:35px 10px;

    text-align:center;

    color:#71867b;

}


.activity-empty i{

    display:block;

    font-size:28px;

    margin-bottom:10px;

    color:#40584b;

}


/* =====================================================
   LOGIN LOG PAGINATION
===================================================== */

.log-pagination{

    display:flex;

    justify-content:space-between;

    align-items:center;

    flex-wrap:wrap;

    gap:15px;

    padding-top:20px;

    margin-top:15px;

    border-top:1px solid rgba(148,163,184,.10);

}


.pagination-info{

    color:#81978d;

    font-size:12px;

}


.pagination-buttons{

    display:flex;

    align-items:center;

    flex-wrap:wrap;

    gap:6px;

}


.page-btn{

    display:flex;

    align-items:center;

    justify-content:center;

    min-width:34px;

    height:34px;

    padding:0 10px;

    border-radius:9px;

    background:rgba(255,255,255,.025);

    border:1px solid rgba(148,163,184,.12);

    color:#a9bcb3;

    font-size:12px;

    font-weight:600;

    text-decoration:none !important;

    transition:.2s;

}


.page-btn:hover{

    background:rgba(34,197,94,.10);

    border-color:rgba(74,222,128,.25);

    color:#4ade80;

}


.page-btn.active{

    background:rgba(34,197,94,.15);

    border-color:rgba(74,222,128,.30);

    color:#4ade80;

}


.page-btn.disabled{

    opacity:.35;

    pointer-events:none;

}


/* =====================================================
   SECTION LABEL
===================================================== */

.section-label{

    color:#70877b;

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:1.7px;

    font-weight:700;

    margin-bottom:18px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1200px){

    .quick-actions{

        grid-template-columns:
            repeat(3,minmax(0,1fr));

    }

}


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

    .quick-actions{

        grid-template-columns:
            repeat(2,minmax(0,1fr));

    }

}


@media(max-width:600px){

    .main{

        padding:16px;

    }

    .topbar{

        align-items:flex-start;

        flex-direction:column;

        margin-bottom:25px;

    }

    .topbar h2{

        font-size:28px;

    }

    .admin-badge{

        align-self:flex-start;

    }

    .quick-actions{

        grid-template-columns:1fr;

    }

    .panel{

        padding:20px;

        border-radius:20px;

    }

    .activity-item{

        align-items:flex-start;

    }

    .activity-title{

        white-space:normal;

    }

    .panel-heading{

        align-items:flex-start;

        flex-wrap:wrap;

    }

    .log-pagination{

        flex-direction:column;

        align-items:flex-start;

    }

}


/* =====================================================
   EXTRA SMALL
===================================================== */

@media(max-width:380px){

    .sidebar{

        width:calc(100% - 20px);

        margin:10px;

        padding:18px 12px;

    }

    .main{

        padding:12px;

    }

    .stat-card{

        min-height:155px;

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

<a href="phone_verification.php">
    <i class="fa-solid fa-mobile-screen-button"></i>
    <span>Phone Verification</span>
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


<!-- =====================================================
     TOPBAR
===================================================== -->

<div class="topbar">

<div class="topbar-left">

<div class="topbar-eyebrow">

<span></span>

</div>

<h2>Dashboard</h2>

<p>
Welcome back, <?= htmlspecialchars($_SESSION['fullname']) ?>
</p>

</div>


<div class="admin-badge">

SK President

</div>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="section-label">

System Overview

</div>


<div class="row">


<!-- USERS -->

<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-users"></i>

</div>

<h3>
<?= $stat['users'] ?>
</h3>

<p>
Total Users
</p>

</div>

</div>


<!-- SCHOLARS -->

<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-user-graduate"></i>

</div>

<h3>
<?= $stat['scholars'] ?>
</h3>

<p>
Total Scholars
</p>

</div>

</div>


<!-- BARANGAYS -->

<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-building"></i>

</div>

<h3>
<?= $stat['barangays'] ?>
</h3>

<p>
Barangays
</p>

</div>

</div>


<!-- RECORDS -->

<div class="col-xl-3 col-md-6 mb-4">

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-folder-open"></i>

</div>

<h3>
<?= $stat['records'] ?>
</h3>

<p>
Records
</p>

</div>

</div>


</div>


<!-- =====================================================
     QUICK ACTIONS
===================================================== -->

<div class="panel">

<div class="panel-heading">

<div class="panel-heading-left">

<div class="panel-heading-icon">

<i class="fa-solid fa-bolt"></i>

</div>

<h4>
Quick Actions
</h4>

</div>

</div>


<div class="quick-actions">


<a
href="users.php"
class="quick-btn">

<i class="fa-solid fa-user-plus"></i>

<span>Add User</span>

</a>


<a
href="map.php"
class="quick-btn">

<i class="fa-solid fa-map-pin"></i>

<span>Add Map Project</span>

</a>


<a
href="residents.php"
class="quick-btn">

<i class="fa-solid fa-folder-plus"></i>

<span>Resident Profiling</span>

</a>


<a
href="scholars.php"
class="quick-btn">

<i class="fa-solid fa-user-graduate"></i>

<span>Scholar Management</span>

</a>


<a
href="phone_verification.php"
class="quick-btn">

<i class="fa-solid fa-file-signature"></i>

<span>Phone Verification</span>

</a>


</div>

</div>


<!-- =====================================================
     LOGIN ACTIVITY LOGS
===================================================== -->

<div class="panel">

<div class="panel-heading">

<div class="panel-heading-left">

<div class="panel-heading-icon">

<i class="fa-solid fa-shield-halved"></i>

</div>

<h4>
Login Activity Logs
</h4>

</div>


<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

<form method="GET" style="margin:0;">

<input
    type="hidden"
    name="page"
    value="1"
>

<select
    name="log_sort"
    onchange="this.form.submit()"
    style="
        background:#12221c;
        color:#dcebe3;
        border:1px solid rgba(148,163,184,.15);
        border-radius:10px;
        padding:8px 12px;
        font-size:12px;
        outline:none;
    "
>

<option value="DESC" <?= $log_sort == 'DESC' ? 'selected' : '' ?>>
Newest to Oldest
</option>

<option value="ASC" <?= $log_sort == 'ASC' ? 'selected' : '' ?>>
Oldest to Newest
</option>

</select>

</form>


<div class="activity-count">

<?= number_format($total_logs) ?> Total Logs

</div>

</div>

</div>


<?php

$has_logs = false;

if($logs){

while($log = mysqli_fetch_assoc($logs)){

$has_logs = true;

$is_success = strtolower($log['status']) === 'success';

?>


<div class="activity-item">


<div class="activity-icon"
style="<?= $is_success
    ? 'color:#4ade80;background:rgba(34,197,94,.08);'
    : 'color:#f87171;background:rgba(239,68,68,.08);'
?>">

<i class="fa-solid <?= $is_success
    ? 'fa-right-to-bracket'
    : 'fa-triangle-exclamation'
?>"></i>

</div>


<div class="activity-content">


<div class="activity-title">

<?= htmlspecialchars(
    $log['fullname'] ?: $log['username'] ?: 'Unknown Account'
) ?>

<span style="
    margin-left:7px;
    font-size:10px;
    color:<?= $is_success ? '#4ade80' : '#f87171' ?>;
">

<?= htmlspecialchars(strtoupper($log['status'])) ?>

</span>

</div>


<div class="activity-meta">

<?= htmlspecialchars($log['role'] ?? 'Unknown Role') ?>

&nbsp; • &nbsp;

<?= htmlspecialchars($log['device_type'] ?? 'Unknown Device') ?>

&nbsp; • &nbsp;

<?= htmlspecialchars($log['browser'] ?? 'Unknown Browser') ?>

<br>

<i class="fa-solid fa-globe"></i>

<?= htmlspecialchars($log['ip_address'] ?? 'Unknown IP') ?>

&nbsp; • &nbsp;

<i class="fa-regular fa-clock"></i>

<?= htmlspecialchars($log['created_at']) ?>

</div>


</div>


</div>


<?php

}

}

?>


<?php if(!$has_logs){ ?>

<div class="activity-empty">

<i class="fa-solid fa-shield-halved"></i>

No login activity available yet.

</div>

<?php } ?>


<?php if($total_logs > 0){ ?>

<!-- =====================================================
     PAGINATION
===================================================== -->

<div class="log-pagination">


<div class="pagination-info">

Showing

<strong><?= number_format($showing_start) ?></strong>

to

<strong><?= number_format($showing_end) ?></strong>

of

<strong><?= number_format($total_logs) ?></strong>

login records

</div>


<div class="pagination-buttons">


<!-- PREVIOUS -->

<a
href="?page=<?= max(1, $current_page - 1) ?>&log_sort=<?= $log_sort ?>"
class="page-btn <?= $current_page <= 1 ? 'disabled' : '' ?>"
>

<i class="fa-solid fa-chevron-left"></i>

</a>


<!-- PAGE NUMBERS -->

<?php

$start_page = max(1, $current_page - 2);

$end_page = min(
    $total_pages,
    $current_page + 2
);

if($start_page > 1){

?>

<a
href="?page=1&log_sort=<?= $log_sort ?>"
class="page-btn"
>
1
</a>

<?php

if($start_page > 2){

?>

<span style="color:#71867b;padding:0 3px;">
...
</span>

<?php

}

}

for($i = $start_page; $i <= $end_page; $i++){

?>

<a
href="?page=<?= $i ?>&log_sort=<?= $log_sort ?>"
class="page-btn <?= $current_page == $i ? 'active' : '' ?>"
>

<?= $i ?>

</a>

<?php

}

if($end_page < $total_pages){

if($end_page < $total_pages - 1){

?>

<span style="color:#71867b;padding:0 3px;">
...
</span>

<?php

}

?>

<a
href="?page=<?= $total_pages ?>&log_sort=<?= $log_sort ?>"
class="page-btn"
>

<?= $total_pages ?>

</a>

<?php

}

?>


<!-- NEXT -->

<a
href="?page=<?= min($total_pages, $current_page + 1) ?>&log_sort=<?= $log_sort ?>"
class="page-btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>"
>

<i class="fa-solid fa-chevron-right"></i>

</a>


</div>

</div>

<?php } ?>


</div>


</div>


</body>

</html>
