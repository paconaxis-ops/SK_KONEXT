<?php
include("db.php");

/* =========================================================
   SAVE PIN (FULL FEATURE)
   ========================================================= */

$title = $_POST['title'] ?? '';
$details = $_POST['details'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';
$category = $_POST['category'] ?? '';

$imagePath = "";

/* ================= IMAGE UPLOAD ================= */
if(isset($_FILES['image'])){

    $fileName = time() . "_" . $_FILES['image']['name'];
    $target = "uploads/" . $fileName;

    if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
        $imagePath = $target;
    }
}

/* ================= INSERT ================= */

$query = "INSERT INTO pins 
(title, details, barangay, lat, lng, category, image_path)
VALUES 
('$title','$details','$barangay','$lat','$lng','$category','$imagePath')";

$result = mysqli_query($conn, $query);

if($result){
    echo "success";
}else{
    echo "error";
}
?>