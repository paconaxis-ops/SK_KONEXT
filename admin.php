<?php
session_start();
$conn = mysqli_connect("localhost","root","","sk_konext");

if(!$conn){
    die("DB Connection Failed");
}

/* =======================
   ADD RECORD
======================= */
if(isset($_POST['add_record'])){

$type = $_POST['type'];
$barangay_id = $_POST['barangay_id'];
$title = $_POST['title'];
$description = $_POST['description'];

mysqli_query($conn,"
INSERT INTO records(type,barangay_id,title,description,created_at)
VALUES('$type','$barangay_id','$title','$description',NOW())
");

echo "<script>alert('Record Saved');</script>";
}

/* =======================
   ADD SK MEMBER
======================= */
if(isset($_POST['add_sk'])){

mysqli_query($conn,"
INSERT INTO sk_members(fullname,position,contact_number,barangay_id)
VALUES(
'{$_POST['fullname']}',
'{$_POST['position']}',
'{$_POST['contact']}',
'{$_POST['barangay_id']}'
)
");

echo "<script>alert('SK Member Added');</script>";
}

/* =======================
   ADD SCHOLAR
======================= */
if(isset($_POST['add_scholar'])){

mysqli_query($conn,"
INSERT INTO scholars(fullname,school,course,barangay_id,status)
VALUES(
'{$_POST['fullname']}',
'{$_POST['school']}',
'{$_POST['course']}',
'{$_POST['barangay_id']}',
'Active'
)
");

echo "<script>alert('Scholar Added');</script>";
}

/* =======================
   STATS
======================= */
$stats = mysqli_query($conn,"
SELECT 
(SELECT COUNT(*) FROM barangays) as barangays,
(SELECT COUNT(*) FROM scholars) as scholars,
(SELECT COUNT(*) FROM sk_members) as sk,
(SELECT COUNT(*) FROM records) as records
");

$stat = mysqli_fetch_assoc($stats);

/* =======================
   BARANGAYS
======================= */
$barangays = mysqli_query($conn,"
SELECT * FROM barangays
");
?>

<!DOCTYPE html>
<html>
<head>
<title>SK KONEXT ADMIN</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body{
margin:0;
font-family:Poppins;
background:linear-gradient(135deg,#0f172a,#1e3a8a);
color:white;
}

/* SIDEBAR */
.sidebar{
position:fixed;
width:260px;
height:100vh;
background:rgba(255,255,255,0.08);
backdrop-filter:blur(10px);
padding:20px;
}

.sidebar a{
display:block;
padding:12px;
margin:8px 0;
color:white;
text-decoration:none;
border-radius:10px;
}

.sidebar a:hover{
background:rgba(255,255,255,0.15);
}

/* MAIN */
.main{
margin-left:280px;
padding:20px;
}

/* CARD */
.card-box{
background:rgba(255,255,255,0.08);
backdrop-filter:blur(10px);
padding:20px;
border-radius:18px;
margin-bottom:20px;
}

/* STAT */
.stat{
text-align:center;
padding:15px;
background:rgba(255,255,255,0.1);
border-radius:12px;
}

/* BUTTON */
.btn-modern{
background:linear-gradient(135deg,#00c6ff,#0072ff);
border:none;
color:white;
padding:10px 15px;
border-radius:12px;
font-weight:600;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

<h3>⚡ SK KONEXT</h3>

<a href="index.html">🏠 Home</a>
<a href="#dashboard">📊 Dashboard</a>
<a href="#barangay">📍 Barangay</a>
<a href="#sk">👥 SK Members</a>
<a href="#scholar">🎓 Scholars</a>
<a href="#record">📝 Records</a>
<a href="#mapprojects">🗺️ Map Projects</a>

</div>

<!-- MAIN -->
<div class="main">

<!-- DASHBOARD -->
<div id="dashboard" class="card-box">

<h3>📊 Dashboard</h3>

<div class="row">

<div class="col-md-3 stat">
<h2><?= $stat['barangays'] ?></h2>
<p>Barangays</p>
</div>

<div class="col-md-3 stat">
<h2><?= $stat['scholars'] ?></h2>
<p>Scholars</p>
</div>

<div class="col-md-3 stat">
<h2><?= $stat['sk'] ?></h2>
<p>SK Members</p>
</div>

<div class="col-md-3 stat">
<h2><?= $stat['records'] ?></h2>
<p>Records</p>
</div>

</div>

</div>

<!-- BARANGAY -->
<div id="barangay" class="card-box">

<h3>📍 Barangays</h3>

<table class="table table-dark">
<tr>
<th>Name</th>
</tr>

<?php while($b=mysqli_fetch_assoc($barangays)){ ?>
<tr>
<td><?= $b['barangay_name'] ?></td>
</tr>
<?php } ?>

</table>

</div>

<!-- SK MEMBERS -->
<div id="sk" class="card-box">

<h3>👥 Add SK Member</h3>

<form method="POST">

<input name="fullname" class="form-control mb-2" placeholder="Full Name">
<input name="position" class="form-control mb-2" placeholder="Position">
<input name="contact" class="form-control mb-2" placeholder="Contact">

<select name="barangay_id" class="form-control mb-2">

<?php
$skb=mysqli_query($conn,"SELECT * FROM barangays");
while($r=mysqli_fetch_assoc($skb)){ ?>
<option value="<?= $r['id'] ?>"><?= $r['barangay_name'] ?></option>
<?php } ?>

</select>

<button name="add_sk" class="btn btn-modern">Add SK Member</button>

</form>

</div>

<!-- SCHOLARS -->
<div id="scholar" class="card-box">

<h3>🎓 Add Scholar</h3>

<form method="POST">

<input name="fullname" class="form-control mb-2" placeholder="Full Name">
<input name="school" class="form-control mb-2" placeholder="School">
<input name="course" class="form-control mb-2" placeholder="Course">

<select name="barangay_id" class="form-control mb-2">

<?php
$sb=mysqli_query($conn,"SELECT * FROM barangays");
while($r=mysqli_fetch_assoc($sb)){ ?>
<option value="<?= $r['id'] ?>"><?= $r['barangay_name'] ?></option>
<?php } ?>

</select>

<button name="add_scholar" class="btn btn-modern">Add Scholar</button>

</form>

</div>

<!-- RECORDS -->
<div id="record" class="card-box">

<h3>📝 Records System</h3>

<form method="POST">

<select name="type" class="form-control mb-2">
<option>Event</option>
<option>Meeting</option>
<option>Project</option>
<option>Announcement</option>
</select>

<select name="barangay_id" class="form-control mb-2">

<?php
$rb=mysqli_query($conn,"SELECT * FROM barangays");
while($r=mysqli_fetch_assoc($rb)){ ?>
<option value="<?= $r['id'] ?>"><?= $r['barangay_name'] ?></option>
<?php } ?>

</select>

<input name="title" class="form-control mb-2" placeholder="Title">
<textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>

<button name="add_record" class="btn btn-modern">Save Record</button>

</form>

</div>

</div>

</body>
</html>