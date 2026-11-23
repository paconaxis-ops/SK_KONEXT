<?php
include("db.php");

/* =========================================================
   FETCH SCHOLARS (FIXED + JOIN BARANGAY)
========================================================= */

$query = "
SELECT 
s.id,
s.fullname,
b.barangay_name,
s.school,
s.status
FROM scholars s
LEFT JOIN barangays b ON b.id = s.barangay_id
ORDER BY s.id DESC
";

$result = mysqli_query($conn, $query);

/* TOTAL SCHOLARS */
$countQuery = "SELECT COUNT(*) as total FROM scholars";
$countResult = mysqli_query($conn, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['total'];
?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<title>SK Scholar System</title>
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

body{
background:#eef3f9;
font-family:Poppins;
}

/* =========================
   TOP BAR
========================= */

.topbar{
background:linear-gradient(135deg,#0d6efd,#0047ab);
padding:20px;
color:white;
}

.topbar h2{
font-weight:700;
}

/* =========================
   CARD
========================= */

.dashboard-card{
background:white;
border-radius:20px;
padding:25px;
margin-top:-25px;
box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

/* =========================
   COUNT BOX
========================= */

.count-box{
background:linear-gradient(135deg,#0d6efd,#0052cc);
color:white;
padding:25px;
border-radius:20px;
text-align:center;
}

.count-box h1{
font-size:50px;
}

/* =========================
   SEARCH
========================= */

.search-box{
position:relative;
margin-top:20px;
}

.search-box input{
width:100%;
padding:14px 50px 14px 20px;
border:none;
border-radius:50px;
box-shadow:0 5px 15px rgba(0,0,0,0.1);
outline:none;
}

.search-box i{
position:absolute;
right:20px;
top:15px;
color:#0d6efd;
}

/* =========================
   TABLE
========================= */

.table-container{
margin-top:20px;
overflow-x:auto;
}

thead{
background:#0d6efd;
color:white;
}

tbody tr:hover{
background:#f1f7ff;
}

/* =========================
   BUTTONS
========================= */

.action-btn{
border:none;
padding:7px 12px;
border-radius:20px;
color:white;
font-size:13px;
}

.edit-btn{background:#28a745;}
.delete-btn{background:#dc3545;}

</style>

</head>

<body>

<!-- TOPBAR -->
<div class="topbar">

<div class="container d-flex justify-content-between align-items-center">

<h2>
<i class="fa-solid fa-user-graduate"></i>
SK Scholar System
</h2>

<a href="index.html" class="btn btn-light">
<i class="fa-solid fa-house"></i> Home
</a>

</div>

</div>

<!-- MAIN -->
<div class="container">

<div class="dashboard-card">

<!-- COUNT -->
<div class="row">

<div class="col-md-4">
<div class="count-box">
<h5>Total Scholars</h5>
<h1 id="totalCount"><?= $total ?></h1>
</div>
</div>

</div>

<!-- SEARCH -->
<div class="search-box">

<input type="text" id="searchInput" placeholder="Search Scholar...">

<i class="fa-solid fa-search"></i>

</div>

<!-- TABLE -->
<div class="table-container">

<table class="table table-bordered table-hover">

<thead>
<tr>
<th>ID</th>
<th>Full Name</th>
<th>Barangay</th>
<th>School</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="scholarTable">

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['id'] ?></td>
<td><?= $row['fullname'] ?></td>
<td><?= $row['barangay_name'] ?></td>
<td><?= $row['school'] ?></td>

<td>
<span class="badge badge-<?= $row['status']=='Active'?'success':'danger' ?>">
<?= $row['status'] ?>
</span>
</td>

<td>

<button class="action-btn edit-btn">
<i class="fa fa-pen"></i>
</button>

<button class="action-btn delete-btn">
<i class="fa fa-trash"></i>
</button>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<!-- SEARCH SCRIPT -->
<script>

document.getElementById("searchInput").addEventListener("keyup", function(){

let filter = this.value.toLowerCase();
let rows = document.querySelectorAll("#scholarTable tr");

rows.forEach(row => {
let text = row.innerText.toLowerCase();
row.style.display = text.includes(filter) ? "" : "none";
});

});

</script>

</body>
</html> 