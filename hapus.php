<?php
// Require authentication
require_once 'auth.php';

// Import database configuration
require_once 'config.php';

// Redirect to test_connection.php if connection fails
if (!isset($pdo)) {
    header("Location: test_connection.php");
    exit;
}

// Security check: Only allow POST requests for deletion to prevent CSRF and accidental deletes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode penghapusan tidak valid!";
    header("Location: dashboard.php");
    exit;
}

// Check if id_hewan is provided
if (!isset($_POST['id_hewan']) || trim($_POST['id_hewan']) === '') {
    $_SESSION['error'] = "ID Hewan tidak ditentukan!";
    header("Location: dashboard.php");
    exit;
}

$id_hewan = intval($_POST['id_hewan']);

try {
    // 1. Fetch pet name first to show in the success message
    $name_stmt = $pdo->prepare("SELECT nama_hewan FROM hewan WHERE id_hewan = ?");
    $name_stmt->execute([$id_hewan]);
    $nama_hewan = $name_stmt->fetchColumn();
    
    if (!$nama_hewan) {
        $_SESSION['error'] = "Data hewan tidak ditemukan!";
        header("Location: dashboard.php");
        exit;
    }
    
    // 2. Execute deletion
    $delete_stmt = $pdo->prepare("DELETE FROM hewan WHERE id_hewan = ?");
    $delete_stmt->execute([$id_hewan]);
    
    $_SESSION['success'] = "Data hewan '$nama_hewan' berhasil dihapus!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal menghapus data: " . $e->getMessage();
}

// Redirect back to main dashboard
header("Location: dashboard.php");
exit;
