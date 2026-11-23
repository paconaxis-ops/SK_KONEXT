<?php
session_start();
include("db.php");

// only SK or admin can add pins
if($_SESSION['role'] !== "sk_member" && $_SESSION['role'] !== "admin"){
    echo "Unauthorized";
    exit();
}

$title = $_POST['title'];
$details = $_POST['details'];
$lat = $_POST['lat'];
$lng = $_POST['lng'];

// FORCE USER BARANGAY (IMPORTANT SECURITY RULE)
$barangay_id = $_SESSION['barangay_id'];

$query = "INSERT INTO map_pins(title,details,lat,lng,barangay_id)
VALUES('$title','$details','$lat','$lng','$barangay_id')";

mysqli_query($conn,$query);

echo "saved";
?>