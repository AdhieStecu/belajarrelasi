<?php
// Require authentication & database connection
require_once 'auth.php';
require_once 'config.php';

// Security check: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode tambah jadwal tidak valid!";
    header("Location: dashboard.php");
    exit;
}

// Validate CSRF token
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validate_csrf_token($csrf_token)) {
    $_SESSION['error'] = "Keamanan CSRF tidak valid. Silakan coba lagi.";
    header("Location: dashboard.php");
    exit;
}

$id_hewan = isset($_POST['id_hewan']) ? intval($_POST['id_hewan']) : 0;
$kegiatan = isset($_POST['kegiatan']) ? trim($_POST['kegiatan']) : '';
$tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';

if ($id_hewan === 0 || $kegiatan === '' || $tanggal === '') {
    $_SESSION['error'] = "Semua bidang jadwal wajib diisi!";
    header("Location: dashboard.php");
    exit;
}

try {
    // Check if pet exists
    $pet_check = $pdo->prepare("SELECT nama_hewan FROM hewan WHERE id_hewan = ?");
    $pet_check->execute([$id_hewan]);
    $nama_hewan = $pet_check->fetchColumn();
    
    if (!$nama_hewan) {
        $_SESSION['error'] = "Data hewan tidak ditemukan!";
    } else {
        // Insert care schedule
        $stmt = $pdo->prepare("INSERT INTO jadwal_perawatan (id_hewan, kegiatan, tanggal, status) VALUES (?, ?, ?, 'Belum Selesai')");
        $stmt->execute([$id_hewan, $kegiatan, $tanggal]);
        $_SESSION['success'] = "Jadwal '$kegiatan' untuk $nama_hewan berhasil ditambahkan!";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal menjadwalkan perawatan: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
