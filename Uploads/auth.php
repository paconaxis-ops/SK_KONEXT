<?php

session_start();

/* =========================
   NOT LOGGED IN
========================= */

if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}

/* =========================
   ROLE CHECK FUNCTION
========================= */

function allowRoles($roles){

    if(!in_array($_SESSION['role'], $roles)){

        echo "
        <h2 style='font-family:Arial;
        text-align:center;
        margin-top:100px;
        color:red;'>
        ACCESS DENIED
        </h2>
        ";

        exit();

    }

}
?>