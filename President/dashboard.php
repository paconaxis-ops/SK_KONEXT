<?php
include("../includes/auth.php");

allowRoles(['president']);

header("Location: ../barangay/dashboard.php");
exit();
?>