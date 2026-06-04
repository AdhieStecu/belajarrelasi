<?php
/**
 * Database Configuration File
 * Using PDO (PHP Data Objects) for security and flexibility.
 */

// Database credentials (default for XAMPP MySQL/MariaDB)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peliharaan');
define('DB_CHARSET', 'utf8mb4');

try {
    // Data Source Name (DSN)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // PDO options for safety and error handling
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];
    
    // Establish PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // In production, we log this and hide the raw message to prevent credential exposure.
    // For development, we store the error code/message to display in our test utility.
    $connection_error = $e->getMessage();
}
