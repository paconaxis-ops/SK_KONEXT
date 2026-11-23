<?php
include("../includes/auth.php");

allowRoles(['sk_member']);

header("Location: ../barangay/dashboard.php");
exit();
?>