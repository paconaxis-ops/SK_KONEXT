<?php

include("db.php");

/* =========================================================
   FETCH SCHOLARS
   ========================================================= */

$query = "SELECT * FROM scholars ORDER BY id DESC";

$result = mysqli_query($conn, $query);

/* =========================================================
   DISPLAY RECORDS
   ========================================================= */

if(mysqli_num_rows($result) > 0){

while($row = mysqli_fetch_assoc($result)){

?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['name']; ?></td>

<td><?php echo $row['barangay']; ?></td>

<td><?php echo $row['school']; ?></td>

<td><?php echo $row['status']; ?></td>

<td>

<button class="btn btn-success btn-sm">

<i class="fa-solid fa-pen"></i>
Edit

</button>

<button class="btn btn-danger btn-sm">

<i class="fa-solid fa-trash"></i>
Delete

</button>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="6" class="text-center">

No Scholar Records Found

</td>

</tr>

<?php
}
?>