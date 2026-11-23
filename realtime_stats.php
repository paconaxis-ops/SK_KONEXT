<?php
include("db_connect.php");

$stats = mysqli_query($conn,"
SELECT 
(SELECT COUNT(*) FROM barangays) as barangays,
(SELECT COUNT(*) FROM scholars) as scholars,
(SELECT COUNT(*) FROM sk_members) as sk,
(SELECT COUNT(*) FROM records) as records
");

echo json_encode(mysqli_fetch_assoc($stats));
?>