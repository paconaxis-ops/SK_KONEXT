<?php

include("db.php");

/* =========================================================
   DELETE SCHOLAR
   ========================================================= */

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $query = "DELETE FROM scholars WHERE id='$id'";

    $result = mysqli_query($conn, $query);

    if($result){

        header("Location: scholar.php");
        exit();

    }else{

        echo "Failed to delete scholar";

    }

}else{

    header("Location: scholar.php");
    exit();

}

?>