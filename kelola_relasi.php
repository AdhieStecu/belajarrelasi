<?php
// Require authentication & database connection
require_once 'auth.php';
require_once 'config.php';

// Handle POST actions (Add/Edit for jenis and ras)
$success_msg = '';
$error_msg = '';

// Edit states
$edit_jenis_data = null;
$edit_ras_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Keamanan CSRF tidak valid. Silakan coba lagi.";
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        // Add Kategori Jenis
        if ($action === 'add_jenis') {
            $nama_jenis = isset($_POST['nama_jenis']) ? trim($_POST['nama_jenis']) : '';
            if ($nama_jenis === '') {
                $error_msg = "Nama kategori wajib diisi!";
            } else {
                try {
                    // Check duplicate
                    $check = $pdo->prepare("SELECT COUNT(*) FROM jenis WHERE nama_jenis = ?");
                    $check->execute([$nama_jenis]);
                    if ($check->fetchColumn() > 0) {
                        $error_msg = "Kategori '$nama_jenis' sudah ada!";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO jenis (nama_jenis) VALUES (?)");
                        $stmt->execute([$nama_jenis]);
                        $_SESSION['success'] = "Kategori '$nama_jenis' berhasil ditambahkan!";
                        header("Location: kelola_relasi.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = "Gagal menambahkan kategori: " . $e->getMessage();
                }
            }
        }
        
        // Edit Kategori Jenis
        elseif ($action === 'edit_jenis') {
            $id_jenis = isset($_POST['id_jenis']) ? intval($_POST['id_jenis']) : 0;
            $nama_jenis = isset($_POST['nama_jenis']) ? trim($_POST['nama_jenis']) : '';
            if ($nama_jenis === '') {
                $error_msg = "Nama kategori wajib diisi!";
            } else {
                try {
                    // Check duplicate for other ids
                    $check = $pdo->prepare("SELECT COUNT(*) FROM jenis WHERE nama_jenis = ? AND id_jenis != ?");
                    $check->execute([$nama_jenis, $id_jenis]);
                    if ($check->fetchColumn() > 0) {
                        $error_msg = "Kategori '$nama_jenis' sudah terdaftar!";
                    } else {
                        $stmt = $pdo->prepare("UPDATE jenis SET nama_jenis = ? WHERE id_jenis = ?");
                        $stmt->execute([$nama_jenis, $id_jenis]);
                        $_SESSION['success'] = "Kategori berhasil diperbarui!";
                        header("Location: kelola_relasi.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = "Gagal mengubah kategori: " . $e->getMessage();
                }
            }
        }
        
        // Add Ras
        elseif ($action === 'add_ras') {
            $nama_ras = isset($_POST['nama_ras']) ? trim($_POST['nama_ras']) : '';
            $id_jenis = isset($_POST['id_jenis']) ? intval($_POST['id_jenis']) : 0;
            $sifat = isset($_POST['sifat']) ? trim($_POST['sifat']) : '';
            $ukuran = isset($_POST['ukuran']) ? trim($_POST['ukuran']) : '';
            $perawatan_bulu = isset($_POST['perawatan_bulu']) ? trim($_POST['perawatan_bulu']) : '';
            $cocok_apartemen = isset($_POST['cocok_apartemen']) ? 1 : 0;
            
            if ($nama_ras === '' || $id_jenis === 0) {
                $error_msg = "Nama ras dan kategori wajib diisi!";
            } else {
                try {
                    // Check duplicate for same breed in same category
                    $check = $pdo->prepare("SELECT COUNT(*) FROM ras WHERE nama_ras = ? AND id_jenis = ?");
                    $check->execute([$nama_ras, $id_jenis]);
                    if ($check->fetchColumn() > 0) {
                        $error_msg = "Ras '$nama_ras' sudah ada di kategori ini!";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO ras (nama_ras, id_jenis, sifat, ukuran, perawatan_bulu, cocok_apartemen) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nama_ras, $id_jenis, $sifat, $ukuran, $perawatan_bulu, $cocok_apartemen]);
                        $_SESSION['success'] = "Ras '$nama_ras' berhasil ditambahkan!";
                        header("Location: kelola_relasi.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = "Gagal menambahkan ras: " . $e->getMessage();
                }
            }
        }
        
        // Edit Ras
        elseif ($action === 'edit_ras') {
            $id_ras = isset($_POST['id_ras']) ? intval($_POST['id_ras']) : 0;
            $nama_ras = isset($_POST['nama_ras']) ? trim($_POST['nama_ras']) : '';
            $id_jenis = isset($_POST['id_jenis']) ? intval($_POST['id_jenis']) : 0;
            $sifat = isset($_POST['sifat']) ? trim($_POST['sifat']) : '';
            $ukuran = isset($_POST['ukuran']) ? trim($_POST['ukuran']) : '';
            $perawatan_bulu = isset($_POST['perawatan_bulu']) ? trim($_POST['perawatan_bulu']) : '';
            $cocok_apartemen = isset($_POST['cocok_apartemen']) ? 1 : 0;
            
            if ($nama_ras === '' || $id_jenis === 0) {
                $error_msg = "Semua bidang wajib diisi!";
            } else {
                try {
                    // Check duplicate
                    $check = $pdo->prepare("SELECT COUNT(*) FROM ras WHERE nama_ras = ? AND id_jenis = ? AND id_ras != ?");
                    $check->execute([$nama_ras, $id_jenis, $id_ras]);
                    if ($check->fetchColumn() > 0) {
                        $error_msg = "Ras '$nama_ras' sudah terdaftar di kategori tersebut!";
                    } else {
                        $stmt = $pdo->prepare("UPDATE ras SET nama_ras = ?, id_jenis = ?, sifat = ?, ukuran = ?, perawatan_bulu = ?, cocok_apartemen = ? WHERE id_ras = ?");
                        $stmt->execute([$nama_ras, $id_jenis, $sifat, $ukuran, $perawatan_bulu, $cocok_apartemen, $id_ras]);
                        $_SESSION['success'] = "Ras berhasil diperbarui!";
                        header("Location: kelola_relasi.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = "Gagal mengubah ras: " . $e->getMessage();
                }
            }
        }
    }
}

// Handle GET edit prefill
if (isset($_GET['edit_jenis'])) {
    $id_edit = intval($_GET['edit_jenis']);
    $stmt = $pdo->prepare("SELECT * FROM jenis WHERE id_jenis = ?");
    $stmt->execute([$id_edit]);
    $edit_jenis_data = $stmt->fetch();
}

if (isset($_GET['edit_ras'])) {
    $id_edit = intval($_GET['edit_ras']);
    $stmt = $pdo->prepare("SELECT * FROM ras WHERE id_ras = ?");
    $stmt->execute([$id_edit]);
    $edit_ras_data = $stmt->fetch();
}

// Fetch all Jenis Categories (along with the count of breeds and animals in it)
$jenis_list = [];
try {
    $jenis_query = "SELECT j.id_jenis, j.nama_jenis, 
                    (SELECT COUNT(*) FROM ras r WHERE r.id_jenis = j.id_jenis) as total_ras,
                    (SELECT COUNT(*) FROM hewan h JOIN ras r ON h.id_ras = r.id_ras WHERE r.id_jenis = j.id_jenis) as total_hewan
                    FROM jenis j 
                    ORDER BY j.nama_jenis ASC";
    $jenis_list = $pdo->query($jenis_query)->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Gagal memuat kategori: " . $e->getMessage();
}

// Fetch all Breeds
$ras_list = [];
try {
    $ras_query = "SELECT r.id_ras, r.nama_ras, r.id_jenis, j.nama_jenis,
                  (SELECT COUNT(*) FROM hewan h WHERE h.id_ras = r.id_ras) as total_hewan
                  FROM ras r
                  JOIN jenis j ON r.id_jenis = j.id_jenis
                  ORDER BY j.nama_jenis ASC, r.nama_ras ASC";
    $ras_list = $pdo->query($ras_query)->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Gagal memuat ras: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Relasi - Belajar Relasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .grid-relasi {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .grid-relasi {
                grid-template-columns: 1fr;
            }
        }

        .section-title-icon {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 0.5rem;
        }

        .badge-count {
            background: rgba(255,255,255,0.08);
            color: var(--text-secondary);
            font-size: 0.75rem;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            margin-left: 0.25rem;
        }
        
        .form-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="main-navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            <span>🐾</span> BelajarRelasi
        </a>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link"><i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i> Dashboard</a></li>
            <li><a href="kelola_relasi.php" class="nav-link active"><i data-lucide="git-branch" style="width: 16px; height: 16px;"></i> Kelola Relasi</a></li>
            <li><a href="ensiklopedia.php" class="nav-link"><i data-lucide="book-open" style="width: 16px; height: 16px;"></i> Ensiklopedia</a></li>
            <li><a href="tambah.php" class="nav-link"><i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Tambah Hewan</a></li>
        </ul>
        <div class="nav-right">
            <a href="profile.php" class="nav-user" style="text-decoration: none;">
                <span class="user-badge"><i data-lucide="user" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></span>
            </a>
            <a href="logout.php" class="btn btn-logout btn-sm"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i></a>
        </div>
    </div>
</nav>

<div class="page-wrapper">
    <div class="container" style="max-width: 1100px;">
        <!-- Header -->
        <header>
            <div class="brand">
                <h1>Kelola Struktur Relasi</h1>
                <p>Tambah, edit, dan analisis relasi Kategori Jenis &amp; Ras Hewan</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Kembali ke Dashboard
            </a>
        </header>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                <?= htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                <?= htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="grid-relasi">
            
            <!-- COLUMN 1: KATEGORI JENIS -->
            <div>
                <div class="section-title-icon">
                    <i data-lucide="folder" style="width: 18px; height: 18px; color: #a5b4fc;"></i>
                    1. KATEGORI JENIS (Induk Utama)
                </div>

                <!-- Form Card Jenis -->
                <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
                    <?php if ($edit_jenis_data): ?>
                        <div class="form-card-title">
                            <i data-lucide="edit-3" style="width: 16px; height: 16px; color: var(--warning-color);"></i>
                            Edit Kategori Jenis
                        </div>
                        <form method="POST" action="kelola_relasi.php">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="action" value="edit_jenis">
                            <input type="hidden" name="id_jenis" value="<?= $edit_jenis_data['id_jenis']; ?>">
                            
                            <div class="form-group">
                                <label for="nama_jenis">Nama Kategori</label>
                                <input type="text" id="nama_jenis" name="nama_jenis" class="form-control" value="<?= htmlspecialchars($edit_jenis_data['nama_jenis']); ?>" required>
                            </div>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="kelola_relasi.php" class="btn btn-secondary btn-sm">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="form-card-title">
                            <i data-lucide="plus" style="width: 16px; height: 16px; color: var(--success-color);"></i>
                            Tambah Kategori Baru
                        </div>
                        <form method="POST" action="kelola_relasi.php">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="action" value="add_jenis">
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <input type="text" name="nama_jenis" class="form-control" placeholder="Contoh: Burung, Reptil" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Tambahkan
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Table Card Jenis -->
                <div class="card" style="padding: 1.5rem;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th style="text-align: center; width: 90px;">Info Relasi</th>
                                    <th style="text-align: center; width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jenis_list)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-muted);">Belum ada kategori.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jenis_list as $jenis): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: #ffffff;">
                                                <?= htmlspecialchars($jenis['nama_jenis']); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-jenis" title="Jumlah Ras" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;">
                                                    <?= $jenis['total_ras']; ?> ras
                                                </span>
                                                <span class="badge badge-ras" title="Jumlah Profil Hewan" style="font-size: 0.7rem; padding: 0.15rem 0.4rem; background: rgba(59, 130, 246, 0.15); color: #93c5fd; border-color: rgba(59, 130, 246, 0.3);">
                                                    <?= $jenis['total_hewan']; ?> pet
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                                    <a href="kelola_relasi.php?edit_jenis=<?= $jenis['id_jenis']; ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="Edit">
                                                        <i data-lucide="edit-2" style="width: 12px; height: 12px;"></i>
                                                    </a>
                                                    <form method="POST" action="hapus_relasi.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kategori <?= htmlspecialchars($jenis['nama_jenis']); ?>? Semua Ras di dalamnya juga akan terpengaruh.')" style="display: inline;">
                                                        <?php csrf_input(); ?>
                                                        <input type="hidden" name="type" value="jenis">
                                                        <input type="hidden" name="id" value="<?= $jenis['id_jenis']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.5rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;" title="Hapus">
                                                            <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: RAS HEWAN -->
            <div>
                <div class="section-title-icon">
                    <i data-lucide="layers" style="width: 18px; height: 18px; color: #a7f3d0;"></i>
                    2. RAS HEWAN (Relasi Terikat ke Jenis)
                </div>

                <!-- Form Card Ras -->
                <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
                    <?php if ($edit_ras_data): ?>
                        <div class="form-card-title">
                            <i data-lucide="edit-3" style="width: 16px; height: 16px; color: var(--warning-color);"></i>
                            Edit Ras Hewan
                        </div>
                        <form method="POST" action="kelola_relasi.php">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="action" value="edit_ras">
                            <input type="hidden" name="id_ras" value="<?= $edit_ras_data['id_ras']; ?>">
                            
                            <div class="form-group">
                                <label for="id_jenis_edit">Pilih Kategori Jenis</label>
                                <select id="id_jenis_edit" name="id_jenis" class="form-control" required>
                                    <?php foreach ($jenis_list as $jenis): ?>
                                        <option value="<?= $jenis['id_jenis']; ?>" <?= $edit_ras_data['id_jenis'] == $jenis['id_jenis'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($jenis['nama_jenis']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nama_ras_edit">Nama Ras</label>
                                <input type="text" id="nama_ras_edit" name="nama_ras" class="form-control" value="<?= htmlspecialchars($edit_ras_data['nama_ras']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="sifat_edit">Sifat/Karakteristik</label>
                                <input type="text" id="sifat_edit" name="sifat" class="form-control" value="<?= htmlspecialchars($edit_ras_data['sifat'] ?? ''); ?>" placeholder="Contoh: Manja, Tenang, Lembut">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="ukuran_edit">Ukuran Tubuh</label>
                                    <select id="ukuran_edit" name="ukuran" class="form-control">
                                        <option value="Kecil" <?= ($edit_ras_data['ukuran'] ?? '') == 'Kecil' ? 'selected' : ''; ?>>Kecil</option>
                                        <option value="Sedang" <?= ($edit_ras_data['ukuran'] ?? '') == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                        <option value="Besar" <?= ($edit_ras_data['ukuran'] ?? '') == 'Besar' ? 'selected' : ''; ?>>Besar</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="perawatan_edit">Perawatan Bulu</label>
                                    <select id="perawatan_edit" name="perawatan_bulu" class="form-control">
                                        <option value="Rendah" <?= ($edit_ras_data['perawatan_bulu'] ?? '') == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                                        <option value="Sedang" <?= ($edit_ras_data['perawatan_bulu'] ?? '') == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                        <option value="Tinggi" <?= ($edit_ras_data['perawatan_bulu'] ?? '') == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                                <input type="checkbox" id="apartemen_edit" name="cocok_apartemen" value="1" <?= ($edit_ras_data['cocok_apartemen'] ?? 0) == 1 ? 'checked' : ''; ?> style="width: auto;">
                                <label for="apartemen_edit" style="margin-bottom: 0; cursor: pointer; color: var(--text-secondary); font-size: 0.85rem;">Cocok untuk Apartemen/Ruangan Sempit</label>
                            </div>

                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="kelola_relasi.php" class="btn btn-secondary btn-sm">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="form-card-title">
                            <i data-lucide="plus" style="width: 16px; height: 16px; color: var(--success-color);"></i>
                            Tambah Ras Baru
                        </div>
                        <form method="POST" action="kelola_relasi.php">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="action" value="add_ras">
                            
                            <div class="form-group">
                                <label for="id_jenis_add">Kategori Jenis</label>
                                <select id="id_jenis_add" name="id_jenis" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($jenis_list as $jenis): ?>
                                        <option value="<?= $jenis['id_jenis']; ?>">
                                            <?= htmlspecialchars($jenis['nama_jenis']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nama_ras_add">Nama Ras</label>
                                <input type="text" id="nama_ras_add" name="nama_ras" class="form-control" placeholder="Contoh: Persia, Pug, Lovebird" required autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label for="sifat_add">Sifat/Karakteristik</label>
                                <input type="text" id="sifat_add" name="sifat" class="form-control" placeholder="Contoh: Manja, Cerdas, Aktif" autocomplete="off">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="ukuran_add">Ukuran Tubuh</label>
                                    <select id="ukuran_add" name="ukuran" class="form-control">
                                        <option value="Kecil">Kecil</option>
                                        <option value="Sedang" selected>Sedang</option>
                                        <option value="Besar">Besar</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="perawatan_add">Perawatan Bulu</label>
                                    <select id="perawatan_add" name="perawatan_bulu" class="form-control">
                                        <option value="Rendah">Rendah</option>
                                        <option value="Sedang" selected>Sedang</option>
                                        <option value="Tinggi">Tinggi</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                                <input type="checkbox" id="apartemen_add" name="cocok_apartemen" value="1" style="width: auto;">
                                <label for="apartemen_add" style="margin-bottom: 0; cursor: pointer; color: var(--text-secondary); font-size: 0.85rem;">Cocok untuk Apartemen/Ruangan Sempit</label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Tambah Ras Baru
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Table Card Ras -->
                <div class="card" style="padding: 1.5rem;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Nama Ras</th>
                                    <th style="text-align: center; width: 90px;">Jumlah Pet</th>
                                    <th style="text-align: center; width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ras_list)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada ras terdaftar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ras_list as $ras): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-jenis" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars($ras['nama_jenis']); ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 600; color: #ffffff;">
                                                <?= htmlspecialchars($ras['nama_ras']); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-ras" style="font-size: 0.75rem;">
                                                    <?= $ras['total_hewan']; ?> pet
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                                    <a href="kelola_relasi.php?edit_ras=<?= $ras['id_ras']; ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="Edit">
                                                        <i data-lucide="edit-2" style="width: 12px; height: 12px;"></i>
                                                    </a>
                                                    <form method="POST" action="hapus_relasi.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Ras <?= htmlspecialchars($ras['nama_ras']); ?>?')" style="display: inline;">
                                                        <?php csrf_input(); ?>
                                                        <input type="hidden" name="type" value="ras">
                                                        <input type="hidden" name="id" value="<?= $ras['id_ras']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.5rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;" title="Hapus">
                                                            <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
