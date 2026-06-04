<?php
// Start session for status alerts
session_start();

// Import database configuration
require_once 'config.php';

// Redirect to test_connection.php if connection fails
if (!isset($pdo)) {
    header("Location: test_connection.php");
    exit;
}

// 1. Fetch Statistics Data
try {
    $total_hewan = $pdo->query("SELECT COUNT(*) FROM hewan")->fetchColumn();
    $total_ras = $pdo->query("SELECT COUNT(*) FROM ras")->fetchColumn();
    $total_jenis = $pdo->query("SELECT COUNT(*) FROM jenis")->fetchColumn();
} catch (PDOException $e) {
    $total_hewan = $total_ras = $total_jenis = 0;
}

// 2. Fetch Filter Options (Jenis & Ras)
try {
    $jenis_options = $pdo->query("SELECT * FROM jenis ORDER BY nama_jenis ASC")->fetchAll();
    $ras_options = $pdo->query("SELECT r.*, j.nama_jenis FROM ras r JOIN jenis j ON r.id_jenis = j.id_jenis ORDER BY r.nama_ras ASC")->fetchAll();
} catch (PDOException $e) {
    $jenis_options = $ras_options = [];
}

// 3. Build Search & Filter Query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_jenis = isset($_GET['id_jenis']) ? trim($_GET['id_jenis']) : '';
$filter_ras = isset($_GET['id_ras']) ? trim($_GET['id_ras']) : '';

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "h.nama_hewan LIKE :search";
    $params['search'] = "%$search%";
}

if ($filter_jenis !== '') {
    $where_clauses[] = "r.id_jenis = :id_jenis";
    $params['id_jenis'] = $filter_jenis;
}

if ($filter_ras !== '') {
    $where_clauses[] = "h.id_ras = :id_ras";
    $params['id_ras'] = $filter_ras;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Get the main pet list with JOINS
try {
    $query = "SELECT h.id_hewan, h.nama_hewan, h.umur, r.nama_ras, j.nama_jenis 
              FROM hewan h
              JOIN ras r ON h.id_ras = r.id_ras
              JOIN jenis j ON r.id_jenis = j.id_jenis
              $where_sql
              ORDER BY h.id_hewan DESC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $hewan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $hewan_list = [];
    $error_message = "Gagal mengambil data hewan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peliharaan - Belajar Relasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <!-- Header -->
    <header>
        <div class="brand">
            <h1>Peliharaan Dashboard</h1>
            <p>Sistem Informasi Peliharaan &amp; Relasi Database</p>
        </div>
        <a href="tambah.php" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Tambah Hewan
        </a>
    </header>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Total Hewan</span>
            <span class="stat-value"><?= $total_hewan; ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Ras</span>
            <span class="stat-value"><?= $total_ras; ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Kategori Jenis</span>
            <span class="stat-value"><?= $total_jenis; ?></span>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <?= htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= htmlspecialchars($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Main Card (Data Table) -->
    <div class="card">
        <!-- Search and Filters Form -->
        <form method="GET" action="index.php" class="search-filter-row">
            <div class="search-box">
                <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama hewan..." class="form-control">
            </div>
            
            <div class="filter-box">
                <select name="id_jenis" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Jenis --</option>
                    <?php foreach ($jenis_options as $jenis): ?>
                        <option value="<?= $jenis['id_jenis']; ?>" <?= $filter_jenis == $jenis['id_jenis'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($jenis['nama_jenis']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-box">
                <select name="id_ras" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Ras --</option>
                    <?php foreach ($ras_options as $ras): ?>
                        <?php 
                        // Optional filter checking to show only breeds matching selected category type
                        if ($filter_jenis !== '' && $ras['id_jenis'] != $filter_jenis) continue;
                        ?>
                        <option value="<?= $ras['id_ras']; ?>" <?= $filter_ras == $ras['id_ras'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($ras['nama_ras']); ?> (<?= htmlspecialchars($ras['nama_jenis']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary btn-filter">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
            
            <?php if ($search !== '' || $filter_jenis !== '' || $filter_ras !== ''): ?>
                <a href="index.php" class="btn btn-secondary" title="Reset filter">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Pet List Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Nama Hewan</th>
                        <th>Umur (Bulan)</th>
                        <th>Kategori Jenis</th>
                        <th>Ras Hewan</th>
                        <th style="width: 180px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hewan_list)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon">🐾</div>
                                    <p>Tidak ada data hewan ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($hewan_list as $hewan): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($hewan['nama_hewan']); ?></td>
                                <td><?= htmlspecialchars($hewan['umur']); ?> bln</td>
                                <td>
                                    <span class="badge badge-jenis">
                                        <?= htmlspecialchars($hewan['nama_jenis']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-ras">
                                        <?= htmlspecialchars($hewan['nama_ras']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                        <a href="edit.php?id=<?= $hewan['id_hewan']; ?>" class="btn btn-secondary btn-sm" title="Edit data">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"></path></svg>
                                            Edit
                                        </a>
                                        <!-- Safe deletion using a form instead of simple URL links -->
                                        <form method="POST" action="hapus.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data <?= htmlspecialchars($hewan['nama_hewan']); ?>?')" style="display: inline;">
                                            <input type="hidden" name="id_hewan" value="<?= $hewan['id_hewan']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus data">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                                Hapus
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

</body>
</html>
