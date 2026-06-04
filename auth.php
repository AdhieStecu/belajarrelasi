<?php
/**
 * Authentication Middleware Helper
 * Protects pages from unauthorized access.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login.php if session variable is not set
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
