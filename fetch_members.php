<?php

include("db.php");

/* =========================================================
   FETCH MEMBERS
   ========================================================= */

$query = "SELECT * FROM members ORDER BY id DESC";
$result = mysqli_query($conn, $query);

/* =========================================================
   DISPLAY DATA
   ========================================================= */

if(mysqli_num_rows($result) > 0){

while($row = mysqli_fetch_assoc($result)){

?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['name']; ?></td>

<td><?php echo $row['barangay']; ?></td>

<td><?php echo $row['position']; ?></td>

<td>

<button class="btn btn-success btn-sm">
Edit
</button>

<button class="btn btn-danger btn-sm">
Delete
</button>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="5" class="text-center">

No Member Records Found

</td>

</tr>

<?php
}
?>