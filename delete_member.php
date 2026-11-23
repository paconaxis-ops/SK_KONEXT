<?php

include("db.php");

/* =========================================================
   DELETE MEMBER
   ========================================================= */

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $query = "DELETE FROM members WHERE id='$id'";

    $result = mysqli_query($conn, $query);

    if($result){

        header("Location: admin.php");
        exit();

    }else{

        echo "Failed to delete member";

    }

}else{

    header("Location: admin.php");
    exit();

}

?>