<?php

include("db.php");

/* =========================================================
   ADD MEMBER
   ========================================================= */

if(isset($_POST['add_member'])){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);

    $query = "INSERT INTO members (name, barangay, position)
              VALUES ('$name', '$barangay', '$position')";

    $result = mysqli_query($conn, $query);

    if($result){

        header("Location: admin.php");
        exit();

    }else{

        echo "Failed to add member";

    }

}

?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<title>Add Member</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<style>

body{
    background:#eef3f9;
    font-family:'Poppins',sans-serif;
}

.form-box{

    max-width:600px;
    margin:60px auto;
    background:#fff;
    padding:40px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);

}

.title{

    text-align:center;
    margin-bottom:25px;
    color:#0d6efd;
    font-weight:700;

}

.btn-custom{

    background:#0d6efd;
    color:#fff;

}

.btn-custom:hover{

    background:#0047ab;
    color:#fff;

}

</style>

</head>

<body>

<div class="container">

<div class="form-box">

<h2 class="title">Add Member</h2>

<form method="POST">

<div class="form-group">

<label>Name</label>
<input type="text" name="name" class="form-control" required>

</div>

<div class="form-group">

<label>Barangay</label>
<input type="text" name="barangay" class="form-control" required>

</div>

<div class="form-group">

<label>Position</label>
<input type="text" name="position" class="form-control" required>

</div>

<button type="submit" name="add_member" class="btn btn-custom btn-block">
Add Member
</button>

<a href="admin.php" class="btn btn-secondary btn-block">
Back
</a>

</form>

</div>

</div>

</body>
</html>