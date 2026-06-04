<?php
/**
 * Logout Page
 * Safely destroys user sessions and redirects to login page.
 */

session_start();

// 1. Clear session array
$_SESSION = [];

// 2. Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// 4. Start a temporary new session to show a logout confirmation alert
session_start();
$_SESSION['success'] = "Anda berhasil keluar dari sistem!";

// 5. Redirect back to login page
header("Location: login.php");
exit;
