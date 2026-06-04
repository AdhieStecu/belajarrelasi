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
    
    // Server-side validation
    if ($nama_hewan === '' || $umur === '' || $id_ras === '') {
        $error_msg = "Semua bidang formulir wajib diisi!";
    } elseif (!is_numeric($umur) || intval($umur) < 0) {
        $error_msg = "Umur harus berupa angka positif!";
    } else {
        try {
            // Check if selected breed exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM ras WHERE id_ras = ?");
            $check_stmt->execute([$id_ras]);
            if ($check_stmt->fetchColumn() == 0) {
                $error_msg = "Ras hewan yang dipilih tidak valid!";
            } else {
                // Insert new pet record
                $insert_query = "INSERT INTO hewan (nama_hewan, umur, id_ras) VALUES (:nama, :umur, :id_ras)";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([
                    'nama' => $nama_hewan,
                    'umur' => intval($umur),
                    'id_ras' => intval($id_ras)
                ]);
                
                $_SESSION['success'] = "Data hewan '$nama_hewan' berhasil ditambahkan!";
                header("Location: dashboard.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Hewan - Belajar Relasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="main-navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            <span>🐾</span> BelajarRelasi
        </a>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li><a href="tambah.php" class="nav-link active">Tambah Hewan</a></li>
        </ul>
        <div class="nav-right">
            <div class="nav-user">
                <span>Halo, </span>
                <span class="user-badge"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></span>
            </div>
            <a href="logout.php" class="btn btn-logout btn-sm">Keluar</a>
        </div>
    </div>
</nav>

<div class="page-wrapper">
    <div class="container" style="max-width: 600px;">
    <!-- Header -->
    <header>
        <div class="brand">
            <h1>Tambah Hewan Baru</h1>
            <p>Masukkan detail peliharaan baru Anda</p>
        </div>
    </header>

    <!-- Error Alert -->
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="card">
        <form method="POST" action="tambah.php">
            <!-- Animal Name -->
            <div class="form-group">
                <label for="nama_hewan">Nama Hewan</label>
                <input type="text" id="nama_hewan" name="nama_hewan" class="form-control" placeholder="Contoh: Milo, Bleki" value="<?= isset($_POST['nama_hewan']) ? htmlspecialchars($_POST['nama_hewan']) : ''; ?>" required>
            </div>

            <!-- Animal Age -->
            <div class="form-group">
                <label for="umur">Umur (Bulan)</label>
                <input type="number" id="umur" name="umur" class="form-control" placeholder="Contoh: 12" min="0" value="<?= isset($_POST['umur']) ? htmlspecialchars($_POST['umur']) : ''; ?>" required>
            </div>

            <!-- Animal Breed (Relational) -->
            <div class="form-group">
                <label for="id_ras">Kategori Jenis &amp; Ras</label>
                <select id="id_ras" name="id_ras" class="form-control" required>
                    <option value="">-- Pilih Kategori &amp; Ras --</option>
                    <?php 
                    $current_jenis = '';
                    foreach ($ras_list as $ras): 
                        // Visual grouping by animal type (e.g. Kucing, Anjing)
                        if ($current_jenis !== $ras['nama_jenis']): 
                            if ($current_jenis !== '') echo '</optgroup>';
                            $current_jenis = $ras['nama_jenis'];
                            echo '<optgroup label="' . htmlspecialchars($current_jenis) . '">';
                        endif;
                    ?>
                        <option value="<?= $ras['id_ras']; ?>" <?= (isset($_POST['id_ras']) && $_POST['id_ras'] == $ras['id_ras']) ? 'selected' : ''; ?>>
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
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

</body>
</html>
