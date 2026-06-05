<?php
// Require authentication & database connection
require_once 'auth.php';
require_once 'config.php';

// Security check: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode modifikasi jadwal tidak valid!";
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

$id_jadwal = isset($_POST['id_jadwal']) ? intval($_POST['id_jadwal']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($id_jadwal === 0 || ($action !== 'selesai' && $action !== 'hapus')) {
    $_SESSION['error'] = "Parameter modifikasi jadwal tidak valid!";
    header("Location: dashboard.php");
    exit;
}

try {
    // Check if schedule exists
    $sch_check = $pdo->prepare("SELECT j.kegiatan, h.nama_hewan 
                                FROM jadwal_perawatan j 
                                JOIN hewan h ON j.id_hewan = h.id_hewan 
                                WHERE j.id_jadwal = ?");
    $sch_check->execute([$id_jadwal]);
    $sch_data = $sch_check->fetch();
    
    if (!$sch_data) {
        $_SESSION['error'] = "Jadwal tidak ditemukan!";
    } else {
        if ($action === 'selesai') {
            // Update status to 'Selesai'
            $stmt = $pdo->prepare("UPDATE jadwal_perawatan SET status = 'Selesai' WHERE id_jadwal = ?");
            $stmt->execute([$id_jadwal]);
            $_SESSION['success'] = "Jadwal '" . $sch_data['kegiatan'] . "' untuk " . $sch_data['nama_hewan'] . " ditandai Selesai!";
        } elseif ($action === 'hapus') {
            // Delete schedule
            $stmt = $pdo->prepare("DELETE FROM jadwal_perawatan WHERE id_jadwal = ?");
            $stmt->execute([$id_jadwal]);
            $_SESSION['success'] = "Jadwal '" . $sch_data['kegiatan'] . "' untuk " . $sch_data['nama_hewan'] . " berhasil dihapus!";
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal memproses modifikasi jadwal: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
