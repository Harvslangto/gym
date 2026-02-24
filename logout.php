<?php
include "db.php"; // Includes session_start() and $conn
if(isset($_SESSION['admin_id'])){
    logActivity($conn, $_SESSION['admin_id'], 'Logout', 'Admin logged out');
}

// Unset all session variables to clean the slate.
$_SESSION = array();

// Instruct the browser to expire the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session on the server.
session_destroy();
header("Location: login.php");
exit;
?>