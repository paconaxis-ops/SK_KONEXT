<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "sk_konext"; // ⚠️ change ONLY if your DB name is different

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn){
    die("Database connection failed: " . mysqli_connect_error());
}

?>