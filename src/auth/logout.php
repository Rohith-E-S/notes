<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy(); // Destroy session
header("Location: login.php"); // Redirect to login
exit();
?>
