<?php
session_start();
include("db.php");

if($_SESSION['role'] !== "sk_member" && $_SESSION['role'] !== "admin"){
    echo "Unauthorized";
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];

// ONLY DELETE IF SAME BARANGAY
$barangay_id = $_SESSION['barangay_id'];

$query = "DELETE FROM map_pins 
WHERE id='$id' AND barangay_id='$barangay_id'";

mysqli_query($conn,$query);

echo "deleted";
?>