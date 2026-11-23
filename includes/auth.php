<?php

session_start();

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

/* =========================================
   ROLE CHECK FUNCTION
========================================= */

function allowRoles($roles){

    if(!in_array($_SESSION['role'], $roles)){

        header("Location: ../login.php");
        exit();

    }

}
?>