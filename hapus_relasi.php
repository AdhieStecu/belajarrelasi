<?php
// Require authentication & database connection
require_once 'auth.php';
require_once 'config.php';

// Security check: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode penghapusan tidak valid!";
    header("Location: kelola_relasi.php");
    exit;
}

// Validate CSRF token
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validate_csrf_token($csrf_token)) {
    $_SESSION['error'] = "Keamanan CSRF tidak valid. Silakan coba lagi.";
    header("Location: kelola_relasi.php");
    exit;
}

$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id === 0 || ($type !== 'jenis' && $type !== 'ras')) {
    $_SESSION['error'] = "Parameter penghapusan tidak valid!";
    header("Location: kelola_relasi.php");
    exit;
}

try {
    if ($type === 'jenis') {
        // 1. Fetch category name
        $name_stmt = $pdo->prepare("SELECT nama_jenis FROM jenis WHERE id_jenis = ?");
        $name_stmt->execute([$id]);
        $nama_jenis = $name_stmt->fetchColumn();
        
        if (!$nama_jenis) {
            $_SESSION['error'] = "Kategori jenis tidak ditemukan!";
            header("Location: kelola_relasi.php");
            exit;
        }
        
        // 2. Check if there are associated breeds (ras)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM ras WHERE id_jenis = ?");
        $check_stmt->execute([$id]);
        $linked_ras = $check_stmt->fetchColumn();
        
        if ($linked_ras > 0) {
            $_SESSION['error'] = "Kategori '$nama_jenis' tidak dapat dihapus karena masih memiliki $linked_ras ras hewan yang terikat! Hapus ras terkait terlebih dahulu.";
        } else {
            // 3. Execute delete
            $delete_stmt = $pdo->prepare("DELETE FROM jenis WHERE id_jenis = ?");
            $delete_stmt->execute([$id]);
            $_SESSION['success'] = "Kategori '$nama_jenis' berhasil dihapus!";
        }
        
    } elseif ($type === 'ras') {
        // 1. Fetch breed name
        $name_stmt = $pdo->prepare("SELECT nama_ras FROM ras WHERE id_ras = ?");
        $name_stmt->execute([$id]);
        $nama_ras = $name_stmt->fetchColumn();
        
        if (!$nama_ras) {
            $_SESSION['error'] = "Ras hewan tidak ditemukan!";
            header("Location: kelola_relasi.php");
            exit;
        }
        
        // 2. Check if there are associated pets (hewan)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM hewan WHERE id_ras = ?");
        $check_stmt->execute([$id]);
        $linked_pets = $check_stmt->fetchColumn();
        
        if ($linked_pets > 0) {
            $_SESSION['error'] = "Ras '$nama_ras' tidak dapat dihapus karena masih digunakan oleh $linked_pets profil peliharaan! Hubungkan hewan tersebut ke ras lain atau hapus datanya terlebih dahulu.";
        } else {
            // 3. Execute delete
            $delete_stmt = $pdo->prepare("DELETE FROM ras WHERE id_ras = ?");
            $delete_stmt->execute([$id]);
            $_SESSION['success'] = "Ras '$nama_ras' berhasil dihapus!";
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal memproses penghapusan: " . $e->getMessage();
}

// Redirect back to relational manager
header("Location: kelola_relasi.php");
exit;
