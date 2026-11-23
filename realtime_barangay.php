<?php
include("db_connect.php");

$data = mysqli_query($conn,"
SELECT 
b.barangay_name,
COUNT(s.id) as scholars,
COUNT(sk.id) as sk_members
FROM barangays b
LEFT JOIN scholars s ON s.barangay_id = b.id
LEFT JOIN sk_members sk ON sk.barangay_id = b.id
GROUP BY b.id
");

$result = [];

while($row = mysqli_fetch_assoc($data)){
    $result[] = $row;
}

echo json_encode($result);
?>