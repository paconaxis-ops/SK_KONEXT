<?php
include("db.php");

$query = "
SELECT 
s.id,
s.fullname,
b.barangay_name,
s.school,
s.status
FROM scholars s
LEFT JOIN barangays b ON b.id = s.barangay_id
ORDER BY s.id DESC
";

$result = mysqli_query($conn, $query);

$total = mysqli_num_rows($result);

$html = "";

while($row = mysqli_fetch_assoc($result)){

$statusClass = ($row['status'] == 'Active') ? 'success' : 'danger';

$html .= "

<tr>
<td>{$row['id']}</td>
<td>{$row['fullname']}</td>
<td>{$row['barangay_name']}</td>
<td>{$row['school']}</td>
<td><span class='badge badge-$statusClass'>{$row['status']}</span></td>
<td>
<button class='action-btn edit-btn'>Edit</button>
<button class='action-btn delete-btn'>Delete</button>
</td>
</tr>

";
}

echo json_encode([
    "html" => $html,
    "total" => $total
]);
?>