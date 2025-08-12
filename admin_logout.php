<?php
session_start();
$_SESSION = []; // Clear session
session_destroy(); // End session
header("Location: home.php"); // Redirect to login page
exit();
?>
