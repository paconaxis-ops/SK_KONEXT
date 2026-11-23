<?php
session_start();
include("db.php");

/* =========================================================
   AUTH CHECK
========================================================= */

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: login.php");
    exit();
}

/* =========================================================
   DASHBOARD STATS
========================================================= */

$stats = mysqli_query($conn,"
SELECT
(SELECT COUNT(*) FROM scholars) as scholars,
(SELECT COUNT(*) FROM sk_members) as skmembers,
(SELECT COUNT(*) FROM barangays) as barangays,
(SELECT COUNT(*) FROM records) as records,
(SELECT COUNT(*) FROM map_projects) as projects
");

$stat = mysqli_fetch_assoc($stats);

/* =========================================================
   RECENT RECORDS
========================================================= */

$records = mysqli_query($conn,"
SELECT records.*, barangays.barangay_name
FROM records
LEFT JOIN barangays
ON barangays.id = records.barangay_id
ORDER BY records.id DESC
LIMIT 5
");

?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<title>SK KONEXT Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#f4f7fb;
overflow-x:hidden;
}

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar{

position:fixed;
width:260px;
height:100vh;
background:#0f172a;
padding:25px;
color:white;
overflow-y:auto;

}

.logo{

font-size:24px;
font-weight:700;
margin-bottom:35px;

}

.logo span{
color:#38bdf8;
}

.sidebar a{

display:flex;
align-items:center;
padding:14px;
margin-bottom:10px;
color:#cbd5e1;
text-decoration:none;
border-radius:14px;
transition:.3s;

}

.sidebar a i{

width:25px;

}

.sidebar a:hover{

background:#1e293b;
color:white;
transform:translateX(5px);

}

.active-menu{

background:#1e40af !important;
color:white !important;

}

/* =========================================================
   MAIN
========================================================= */

.main{

margin-left:260px;
padding:30px;

}

/* =========================================================
   TOPBAR
========================================================= */

.topbar{

display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;

}

.topbar h3{

font-weight:700;
color:#0f172a;

}

.profile-box{

background:white;
padding:12px 20px;
border-radius:50px;
box-shadow:0 5px 15px rgba(0,0,0,.08);

}

/* =========================================================
   STAT CARDS
========================================================= */

.stat-card{

background:white;
padding:25px;
border-radius:24px;
box-shadow:0 10px 25px rgba(0,0,0,.06);
transition:.3s;
height:100%;

}

.stat-card:hover{

transform:translateY(-5px);

}

.stat-icon{

width:60px;
height:60px;
border-radius:18px;
display:flex;
justify-content:center;
align-items:center;
font-size:24px;
margin-bottom:20px;
color:white;

}

.bg-blue{
background:#2563eb;
}

.bg-green{
background:#16a34a;
}

.bg-orange{
background:#ea580c;
}

.bg-purple{
background:#7c3aed;
}

.bg-red{
background:#dc2626;
}

.stat-card h2{

font-size:36px;
font-weight:700;
margin-bottom:5px;

}

.stat-card p{

color:#64748b;
margin:0;

}

/* =========================================================
   MODULES
========================================================= */

.section-title{

margin-top:40px;
margin-bottom:20px;
font-weight:700;
color:#0f172a;

}

.module-card{

background:white;
padding:30px;
border-radius:24px;
box-shadow:0 10px 25px rgba(0,0,0,.06);
transition:.3s;
height:100%;
text-align:center;

}

.module-card:hover{

transform:translateY(-6px);

}

.module-card i{

font-size:45px;
margin-bottom:20px;
color:#2563eb;

}

.module-card h4{

font-weight:600;

}

.module-card p{

color:#64748b;
font-size:14px;

}

.open-btn{

display:inline-block;
margin-top:15px;
padding:10px 24px;
background:#2563eb;
color:white;
border-radius:40px;
text-decoration:none;
font-weight:600;

}

.open-btn:hover{

background:#1d4ed8;
color:white;
text-decoration:none;

}

/* =========================================================
   ACTIVITY TABLE
========================================================= */

.activity-box{

background:white;
padding:25px;
border-radius:24px;
box-shadow:0 10px 25px rgba(0,0,0,.06);

}

.table thead{

background:#2563eb;
color:white;

}

.badge-modern{

padding:8px 14px;
border-radius:30px;
font-weight:500;

}

/* =========================================================
   MOBILE
========================================================= */

@media(max-width:991px){

.sidebar{

width:100%;
height:auto;
position:relative;

}

.main{

margin-left:0;

}

}

</style>

</head>

<body>

<!-- =========================================================
   SIDEBAR
========================================================= -->

<div class="sidebar">

<div class="logo">

⚡ SK <span>KONEXT</span>

</div>

<a href="dashboard.php" class="active-menu">
<i class="fa-solid fa-chart-line"></i>
Dashboard
</a>

<a href="scholar.php">
<i class="fa-solid fa-user-graduate"></i>
Scholars
</a>

<a href="map.php">
<i class="fa-solid fa-map-location-dot"></i>
Geo Map
</a>

<a href="#">
<i class="fa-solid fa-users"></i>
SK Members
</a>

<a href="#">
<i class="fa-solid fa-folder-open"></i>
Records
</a>

<a href="#">
<i class="fa-solid fa-building"></i>
Barangays
</a>

<a href="index.php">
<i class="fa-solid fa-house"></i>
Back to Website
</a>

</div>

<!-- =========================================================
   MAIN
========================================================= -->

<div class="main">

<!-- TOPBAR -->

<div class="topbar">

<div>

<h3>Welcome back, Admin</h3>
<p style="color:#64748b;">
Realtime governance monitoring dashboard
</p>

</div>

<div class="profile-box">

<i class="fa-solid fa-user-shield"></i>
 <?= $_SESSION['admin_username']; ?>

</div>

</div>

<!-- =========================================================
   STATS
========================================================= -->

<div class="row">

<div class="col-md-4 col-lg-2 mb-4">

<div class="stat-card">

<div class="stat-icon bg-blue">
<i class="fa-solid fa-user-graduate"></i>
</div>

<h2><?= $stat['scholars']; ?></h2>
<p>Scholars</p>

</div>

</div>

<div class="col-md-4 col-lg-2 mb-4">

<div class="stat-card">

<div class="stat-icon bg-green">
<i class="fa-solid fa-users"></i>
</div>

<h2><?= $stat['skmembers']; ?></h2>
<p>SK Members</p>

</div>

</div>

<div class="col-md-4 col-lg-2 mb-4">

<div class="stat-card">

<div class="stat-icon bg-orange">
<i class="fa-solid fa-building"></i>
</div>

<h2><?= $stat['barangays']; ?></h2>
<p>Barangays</p>

</div>

</div>

<div class="col-md-4 col-lg-2 mb-4">

<div class="stat-card">

<div class="stat-icon bg-purple">
<i class="fa-solid fa-folder-open"></i>
</div>

<h2><?= $stat['records']; ?></h2>
<p>Records</p>

</div>

</div>

<div class="col-md-4 col-lg-2 mb-4">

<div class="stat-card">

<div class="stat-icon bg-red">
<i class="fa-solid fa-map"></i>
</div>

<h2><?= $stat['projects']; ?></h2>
<p>Geo Projects</p>

</div>

</div>

</div>

<!-- =========================================================
   MODULES
========================================================= -->

<h4 class="section-title">
System Modules
</h4>

<div class="row">

<div class="col-md-4 mb-4">

<div class="module-card">

<i class="fa-solid fa-user-graduate"></i>

<h4>Scholar Management</h4>

<p>
Manage scholarship applications,
approvals, and scholar records.
</p>

<a href="scholar.php" class="open-btn">
Open Module
</a>

</div>

</div>

<div class="col-md-4 mb-4">

<div class="module-card">

<i class="fa-solid fa-map-location-dot"></i>

<h4>Geo Governance Map</h4>

<p>
Monitor realtime projects,
activities, and barangay locations.
</p>

<a href="map.php" class="open-btn">
Open Map
</a>

</div>

</div>

<div class="col-md-4 mb-4">

<div class="module-card">

<i class="fa-solid fa-folder-open"></i>

<h4>Records System</h4>

<p>
Track SK activities,
announcements, and reports.
</p>

<a href="#" class="open-btn">
Open Records
</a>

</div>

</div>

</div>

<!-- =========================================================
   RECENT ACTIVITY
========================================================= -->

<h4 class="section-title">
Recent Activities
</h4>

<div class="activity-box">

<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>Type</th>
<th>Barangay</th>
<th>Title</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php while($r=mysqli_fetch_assoc($records)){ ?>

<tr>

<td>

<span class="badge badge-primary badge-modern">

<?= $r['type']; ?>

</span>

</td>

<td><?= $r['barangay_name']; ?></td>

<td><?= $r['title']; ?></td>

<td><?= date("M d, Y", strtotime($r['created_at'])); ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>
</html>