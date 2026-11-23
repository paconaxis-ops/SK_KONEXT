<?php
include("db.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;

if($id){

    mysqli_query($conn, "DELETE FROM pins WHERE id='$id'");
    echo "success";

}else{
    echo "invalid";
}
?>