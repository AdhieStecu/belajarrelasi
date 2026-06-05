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

// Check if ID parameter is provided
if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    $_SESSION['error'] = "ID Hewan tidak ditentukan!";
    header("Location: dashboard.php");
    exit;
}

$id_hewan = intval($_GET['id']);

// Fetch existing pet details
try {
    $stmt = $pdo->prepare("SELECT * FROM hewan WHERE id_hewan = ?");
    $stmt->execute([$id_hewan]);
    $hewan = $stmt->fetch();
    
    if (!$hewan) {
        $_SESSION['error'] = "Data hewan tidak ditemukan!";
        header("Location: dashboard.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal mengambil data hewan: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}

// Fetch all breeds with their categories for the dropdown selection
try {
    $ras_query = "SELECT r.id_ras, r.nama_ras, j.nama_jenis 
                  FROM ras r 
                  JOIN jenis j ON r.id_jenis = j.id_jenis 
                  ORDER BY j.nama_jenis ASC, r.nama_ras ASC";
    $ras_list = $pdo->query($ras_query)->fetchAll();
} catch (PDOException $e) {
    $ras_list = [];
    $error_msg = "Gagal mengambil data ras: " . $e->getMessage();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_hewan = isset($_POST['nama_hewan']) ? trim($_POST['nama_hewan']) : '';
    $umur = isset($_POST['umur']) ? trim($_POST['umur']) : '';
    $id_ras = isset($_POST['id_ras']) ? trim($_POST['id_ras']) : '';
    $berat_badan = isset($_POST['berat_badan']) ? trim($_POST['berat_badan']) : '';
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    // Server-side validation
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Keamanan CSRF tidak valid. Silakan coba lagi.";
    } elseif ($nama_hewan === '' || $umur === '' || $id_ras === '' || $berat_badan === '') {
        $error_msg = "Semua bidang formulir wajib diisi!";
    } elseif (!is_numeric($umur) || intval($umur) < 0) {
        $error_msg = "Umur harus berupa angka positif!";
    } elseif (!is_numeric($berat_badan) || floatval($berat_badan) <= 0) {
        $error_msg = "Berat badan harus berupa angka positif!";
    } else {
        try {
            // Check if selected breed exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM ras WHERE id_ras = ?");
            $check_stmt->execute([$id_ras]);
            if ($check_stmt->fetchColumn() == 0) {
                $error_msg = "Ras hewan yang dipilih tidak valid!";
            } else {
                // Update pet record
                $update_query = "UPDATE hewan SET nama_hewan = :nama, umur = :umur, id_ras = :id_ras, berat_badan = :berat_badan WHERE id_hewan = :id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute([
                    'nama' => $nama_hewan,
                    'umur' => intval($umur),
                    'id_ras' => intval($id_ras),
                    'berat_badan' => floatval($berat_badan),
                    'id' => $id_hewan
                ]);
                
                $_SESSION['success'] = "Data hewan '$nama_hewan' berhasil diubah!";
                header("Location: dashboard.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Gagal mengubah data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hewan - Belajar Relasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<!-- Navigation Bar -->
<nav class="main-navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            <span>🐾</span> PetRelasi
        </a>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link"><i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i> Dashboard</a></li>
            <li><a href="kelola_relasi.php" class="nav-link"><i data-lucide="git-branch" style="width: 16px; height: 16px;"></i> Kelola Relasi</a></li>
            <li><a href="ensiklopedia.php" class="nav-link"><i data-lucide="book-open" style="width: 16px; height: 16px;"></i> Ensiklopedia</a></li>
            <li><a href="tambah.php" class="nav-link"><i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Tambah Hewan</a></li>
        </ul>
        <div class="nav-right">
            <a href="profile.php" class="nav-user" style="text-decoration: none;">
                <span>Halo, </span>
                <span class="user-badge"><i data-lucide="user" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></span>
            </a>
            <a href="logout.php" class="btn btn-logout btn-sm"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Keluar</a>
        </div>
    </div>
</nav>

<div class="page-wrapper">
    <div class="container" style="max-width: 600px;">
    <!-- Header -->
    <header>
        <div class="brand">
            <h1>Edit Data Hewan</h1>
            <p>Perbarui informasi peliharaan Anda</p>
        </div>
    </header>

    <!-- Error Alert -->
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
            <?= htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="card">
        <form method="POST" action="edit.php?id=<?= $id_hewan; ?>">
            <?php csrf_input(); ?>
            <!-- Animal Name -->
            <div class="form-group">
                <label for="nama_hewan">Nama Hewan</label>
                <input type="text" id="nama_hewan" name="nama_hewan" class="form-control" placeholder="Contoh: Milo, Bleki" value="<?= htmlspecialchars(isset($_POST['nama_hewan']) ? $_POST['nama_hewan'] : $hewan['nama_hewan']); ?>" required autocomplete="off">
            </div>

            <!-- Animal Age -->
            <div class="form-group">
                <label for="umur">Umur (Bulan)</label>
                <input type="number" id="umur" name="umur" class="form-control" placeholder="Contoh: 12" min="0" value="<?= htmlspecialchars(isset($_POST['umur']) ? $_POST['umur'] : $hewan['umur']); ?>" required>
            </div>

            <!-- Animal Weight -->
            <div class="form-group">
                <label for="berat_badan">Berat Badan (Kg)</label>
                <input type="number" step="0.1" id="berat_badan" name="berat_badan" class="form-control" placeholder="Contoh: 4.5" min="0.1" value="<?= htmlspecialchars(isset($_POST['berat_badan']) ? $_POST['berat_badan'] : $hewan['berat_badan']); ?>" required>
            </div>

            <!-- Animal Breed (Relational) -->
            <div class="form-group">
                <label for="id_ras">Kategori Jenis &amp; Ras</label>
                <select id="id_ras" name="id_ras" class="form-control" required>
                    <option value="">-- Pilih Kategori &amp; Ras --</option>
                    <?php 
                    $current_jenis = '';
                    $selected_ras = isset($_POST['id_ras']) ? $_POST['id_ras'] : $hewan['id_ras'];
                    
                    foreach ($ras_list as $ras): 
                        // Visual grouping by animal type (e.g. Kucing, Anjing)
                        if ($current_jenis !== $ras['nama_jenis']): 
                            if ($current_jenis !== '') echo '</optgroup>';
                            $current_jenis = $ras['nama_jenis'];
                            echo '<optgroup label="' . htmlspecialchars($current_jenis) . '">';
                        endif;
                    ?>
                        <option value="<?= $ras['id_ras']; ?>" <?= $selected_ras == $ras['id_ras'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($ras['nama_ras']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_jenis !== '') echo '</optgroup>'; ?>
                </select>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
