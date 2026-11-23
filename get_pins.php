<?php

include("db.php");

/* =========================================================
   GET ALL PINS
   ========================================================= */

$query = "SELECT * FROM pins ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$pins = array();

while($row = mysqli_fetch_assoc($result)){

    $pins[] = $row;

}

/* =========================================================
   RETURN JSON DATA
   ========================================================= */

echo json_encode($pins);

?>