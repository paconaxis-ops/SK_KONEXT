<?php

/* =========================================================
   SK KONEXT SESSION SYSTEM
   FILE: session.php
   ========================================================= */

/* ========= START SESSION ========= */

session_start();

/* ========= DATABASE CONNECTION ========= */

include("db.php");

/* =========================================================
   CHECK LOGIN SESSION
   ========================================================= */

if (!isset($_SESSION['admin_logged_in'])) {

    header("Location: login.php");
    exit();

}

/* =========================================================
   OPTIONAL SESSION VARIABLES
   ========================================================= */

$admin_id = $_SESSION['admin_id'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

/* =========================================================
   AUTO TIMEZONE
   ========================================================= */

date_default_timezone_set('Asia/Manila');

?>