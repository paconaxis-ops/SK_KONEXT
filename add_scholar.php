<?php

include("db.php");

/* =========================================================
   ADD SCHOLAR
   ========================================================= */

if(isset($_POST['add_scholar'])){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $school = mysqli_real_escape_string($conn, $_POST['school']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "INSERT INTO scholars
              (name, barangay, school, status)
              VALUES
              ('$name', '$barangay', '$school', '$status')";

    $result = mysqli_query($conn, $query);

    if($result){

        header("Location: scholar.php");
        exit();

    }else{

        echo "Failed to Add Scholar";

    }

}

?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<title>Add Scholar</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<style>

*{
    font-family:'Poppins',sans-serif;
}

body{

    background:#eef3f9;

}

.form-container{

    max-width:600px;
    margin:60px auto;
    background:#fff;
    padding:40px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);

}

.form-title{

    text-align:center;
    margin-bottom:30px;
    font-weight:700;
    color:#0d6efd;

}

.btn-custom{

    background:#0d6efd;
    color:#fff;
    border:none;

}

.btn-custom:hover{

    background:#0047ab;
    color:#fff;

}

</style>

</head>

<body>

<div class="container">

<div class="form-container">

<h2 class="form-title">

Add Scholar

</h2>

<form method="POST">

<div class="form-group">

<label>Full Name</label>

<input type="text"
name="name"
class="form-control"
required>

</div>

<div class="form-group">

<label>Barangay</label>

<input type="text"
name="barangay"
class="form-control"
required>

</div>

<div class="form-group">

<label>School</label>

<input type="text"
name="school"
class="form-control"
required>

</div>

<div class="form-group">

<label>Status</label>

<select name="status"
class="form-control"
required>

<option value="Active">Active</option>
<option value="Inactive">Inactive</option>

</select>

</div>

<button type="submit"
name="add_scholar"
class="btn btn-custom btn-block">

Add Scholar

</button>

<a href="scholar.php"
class="btn btn-secondary btn-block">

Back

</a>

</form>

</div>

</div>

</body>
</html>