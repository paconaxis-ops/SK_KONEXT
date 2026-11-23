<?php
$conn = mysqli_connect("localhost", "root", "", "sk_konext");

if(!$conn){
    die("DB Error: " . mysqli_connect_error());
}
?>