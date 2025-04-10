<?php
require_once 'includes/db_connect.php'; // Ensure session is started

// Unset all session variables
$_SESSION = array();

// If using session cookies, delete the cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

set_flash_message('logout_success', 'You have been logged out successfully.', 'info');
header('Location: index.php'); // Redirect to home page or login page
exit;
?>