<?php
include "db.php"; // Includes session_start() and $conn
if(isset($_SESSION['admin_id'])){
    logActivity($conn, $_SESSION['admin_id'], 'Logout', 'Admin logged out');
}
session_destroy();
header("Location: login.php");
exit;
?>