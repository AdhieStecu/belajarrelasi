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

// 1. Fetch Statistics Data
try {
    $total_hewan = $pdo->query("SELECT COUNT(*) FROM hewan")->fetchColumn();
    $total_ras = $pdo->query("SELECT COUNT(*) FROM ras")->fetchColumn();
    $total_jenis = $pdo->query("SELECT COUNT(*) FROM jenis")->fetchColumn();
    
    // Fetch Category Distribution for Chart.js
    $cat_query = "SELECT j.nama_jenis, COUNT(h.id_hewan) as count 
                  FROM hewan h 
                  JOIN ras r ON h.id_ras = r.id_ras 
                  JOIN jenis j ON r.id_jenis = j.id_jenis 
                  GROUP BY j.nama_jenis";
    $chart_categories = $pdo->query($cat_query)->fetchAll();

    // Fetch Breed Distribution for Chart.js
    $breed_query = "SELECT r.nama_ras, COUNT(h.id_hewan) as count 
                    FROM hewan h 
                    JOIN ras r ON h.id_ras = r.id_ras 
                    GROUP BY r.nama_ras 
                    LIMIT 6";
    $chart_breeds = $pdo->query($breed_query)->fetchAll();
} catch (PDOException $e) {
    $total_hewan = $total_ras = $total_jenis = 0;
    $chart_categories = [];
    $chart_breeds = [];
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
    $query = "SELECT h.id_hewan, h.nama_hewan, h.umur, h.berat_badan, r.id_ras, r.nama_ras, j.id_jenis, j.nama_jenis, r.sifat, r.ukuran, r.perawatan_bulu, r.cocok_apartemen 
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

// Fetch Care Tracker Schedules
$care_schedules = [];
try {
    $sch_query = "SELECT j.id_jadwal, j.kegiatan, j.tanggal, j.status, h.nama_hewan, h.id_hewan
                  FROM jadwal_perawatan j
                  JOIN hewan h ON j.id_hewan = h.id_hewan
                  ORDER BY j.status ASC, j.tanggal ASC LIMIT 10";
    $care_schedules = $pdo->query($sch_query)->fetchAll();
} catch (PDOException $e) {
    $care_schedules = [];
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
    <!-- Chart.js and Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="dashboard.php" class="nav-link active"><i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i> Dashboard</a></li>
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
    <div class="container">
    <!-- Header -->
    <header>
        <div class="brand">
            <h1>Dashboard Peliharaan</h1>
            <p>Sistem Informasi Hubungan Kategori &amp; Manajemen Data Terpadu</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="kelola_relasi.php" class="btn btn-secondary">
                <i data-lucide="git-branch" style="width: 16px; height: 16px;"></i>
                Kelola Relasi
            </a>
            <a href="tambah.php" class="btn btn-primary">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                Tambah Hewan
            </a>
        </div>
    </header>

    <!-- Stats & Charts Grid -->
    <div class="stats-charts-grid">
        <!-- Stats cards stacked -->
        <div class="stats-col">
            <div class="stat-card">
                <div>
                    <span class="stat-label">Total Peliharaan</span>
                    <span class="stat-value"><?= $total_hewan; ?></span>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
                    <i data-lucide="paw-print" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div>
                    <span class="stat-label">Total Ras</span>
                    <span class="stat-value"><?= $total_ras; ?></span>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    <i data-lucide="git-branch" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div>
                    <span class="stat-label">Kategori Jenis</span>
                    <span class="stat-value"><?= $total_jenis; ?></span>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                    <i data-lucide="folder" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
        </div>
        
        <!-- Charts Card -->
        <div class="card chart-card-container">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i data-lucide="bar-chart-3" style="width: 18px; height: 18px; color: #818cf8; vertical-align: middle; margin-right: 4px;"></i>
                    Visualisasi Analitik Database
                </h3>
            </div>
            <div class="chart-canvases">
                <div class="canvas-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="canvas-wrapper">
                    <canvas id="breedChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
            <?= htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
            <?= htmlspecialchars($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Layout Grid -->
    <div class="dashboard-content-grid">
        <!-- Col 1: Pet Table Card (2/3 width) -->
        <div class="card pet-table-card">
            <div class="card-header">
                <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="table" style="width: 18px; height: 18px; color: #818cf8;"></i>
                    Daftar Peliharaan Terdaftar
                </h3>
            </div>
            
            <!-- Search and Filters Form -->
            <form method="GET" action="dashboard.php" class="search-filter-row">
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
                            if ($filter_jenis !== '' && $ras['id_jenis'] != $filter_jenis) continue;
                            ?>
                            <option value="<?= $ras['id_ras']; ?>" <?= $filter_ras == $ras['id_ras'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($ras['nama_ras']); ?> (<?= htmlspecialchars($ras['nama_jenis']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-secondary btn-filter" style="padding: 0 0.85rem;" title="Cari">
                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                </button>
                
                <?php if ($search !== '' || $filter_jenis !== '' || $filter_ras !== ''): ?>
                    <a href="dashboard.php" class="btn btn-secondary" title="Reset filter" style="padding: 0.625rem 1rem;">Reset</a>
                <?php endif; ?>
            </form>

            <!-- Pet List Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 60px; text-align: center;">Profil</th>
                            <th>Nama</th>
                            <th>Umur</th>
                            <th>Kategori</th>
                            <th>Ras</th>
                            <th>Berat</th>
                            <th style="width: 240px; text-align: center;">Aksi Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hewan_list)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon" style="font-size: 2.5rem; opacity: 0.35;"><i data-lucide="frown" style="width: 48px; height: 48px; margin: 0 auto 0.5rem auto;"></i></div>
                                        <p>Tidak ada data peliharaan ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($hewan_list as $hewan): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-secondary);"><?= $no++; ?></td>
                                    <td style="text-align: center;">
                                        <div class="pet-avatar-wrapper">
                                            <img src="https://api.dicebear.com/7.x/bottts/svg?seed=<?= urlencode($hewan['nama_hewan']); ?>" alt="Pet" class="pet-avatar-img">
                                        </div>
                                    </td>
                                    <td style="font-weight: 700; color: #ffffff;"><?= htmlspecialchars($hewan['nama_hewan']); ?></td>
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
                                        <strong style="color: #a5b4fc; font-weight: 600;"><?= htmlspecialchars($hewan['berat_badan']); ?> kg</strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                                            <button class="btn btn-secondary btn-sm ktp-trigger" 
                                                    data-id="<?= $hewan['id_hewan']; ?>"
                                                    data-nama="<?= htmlspecialchars($hewan['nama_hewan']); ?>"
                                                    data-umur="<?= htmlspecialchars($hewan['umur']); ?>"
                                                    data-berat="<?= htmlspecialchars($hewan['berat_badan']); ?>"
                                                    data-jenis="<?= htmlspecialchars($hewan['nama_jenis']); ?>"
                                                    data-ras="<?= htmlspecialchars($hewan['nama_ras']); ?>"
                                                    data-sifat="<?= htmlspecialchars($hewan['sifat'] ?? 'Manja, Aktif'); ?>"
                                                    data-ukuran="<?= htmlspecialchars($hewan['ukuran'] ?? 'Sedang'); ?>"
                                                    data-perawatan="<?= htmlspecialchars($hewan['perawatan_bulu'] ?? 'Sedang'); ?>"
                                                    data-apartemen="<?= htmlspecialchars($hewan['cocok_apartemen'] ?? '0'); ?>"
                                                    title="Lihat KTP Digital">
                                                <i data-lucide="credit-card" style="width: 13px; height: 13px;"></i>
                                                KTP
                                            </button>
                                            
                                            <button class="btn btn-secondary btn-sm calc-trigger"
                                                    data-nama="<?= htmlspecialchars($hewan['nama_hewan']); ?>"
                                                    data-berat="<?= htmlspecialchars($hewan['berat_badan']); ?>"
                                                    data-jenis="<?= htmlspecialchars($hewan['nama_jenis']); ?>"
                                                    data-umur="<?= htmlspecialchars($hewan['umur']); ?>"
                                                    title="Kalkulator Nutrisi">
                                                <i data-lucide="calculator" style="width: 13px; height: 13px;"></i>
                                                Nutrisi
                                            </button>

                                            <a href="edit.php?id=<?= $hewan['id_hewan']; ?>" class="btn btn-secondary btn-sm" title="Edit data">
                                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                                Edit
                                            </a>
                                            
                                            <form method="POST" action="hapus.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data <?= htmlspecialchars($hewan['nama_hewan']); ?>?')" style="display: inline;">
                                                <?php csrf_input(); ?>
                                                <input type="hidden" name="id_hewan" value="<?= $hewan['id_hewan']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus data">
                                                    <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
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

        <!-- Col 2: Care Tracker Card (1/3 width) -->
        <div class="card care-tracker-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="card-header" style="margin-bottom: 0; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                    <i data-lucide="calendar" style="width: 18px; height: 18px; color: #10b981;"></i>
                    Agenda Care Tracker
                </h3>
            </div>

            <!-- Form to Add Schedule directly -->
            <form method="POST" action="tambah_jadwal.php" class="add-schedule-inline-form" style="display: flex; flex-direction: column; gap: 0.75rem; background: rgba(0,0,0,0.15); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.04);">
                <h4 style="font-size: 0.85rem; color: #ffffff; font-weight: 600;">+ Jadwal Perawatan Baru</h4>
                <?php csrf_input(); ?>
                
                <?php
                // Fetch list of all pets to populate scheduling dropdown
                try {
                    $all_pets_dropdown = $pdo->query("SELECT id_hewan, nama_hewan FROM hewan ORDER BY nama_hewan ASC")->fetchAll();
                } catch (PDOException $e) {
                    $all_pets_dropdown = [];
                }
                ?>
                <div>
                    <select name="id_hewan" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" required>
                        <option value="">-- Pilih Peliharaan --</option>
                        <?php foreach ($all_pets_dropdown as $pet_opt): ?>
                            <option value="<?= $pet_opt['id_hewan']; ?>"><?= htmlspecialchars($pet_opt['nama_hewan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <select name="kegiatan" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" required>
                        <option value="">-- Pilih Kegiatan --</option>
                        <option value="Vaksinasi Medis">Vaksinasi Medis</option>
                        <option value="Grooming & Bulu">Grooming & Bulu</option>
                        <option value="Obat Cacing">Obat Cacing</option>
                        <option value="Potong Kuku">Potong Kuku</option>
                        <option value="Periksa Dokter">Periksa Dokter</option>
                        <option value="Pemberian Vitamin">Pemberian Vitamin</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="tanggal" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; font-size: 0.8rem; padding: 0.5rem;">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Tambahkan
                </button>
            </form>

            <!-- Care Timeline Container -->
            <div class="care-timeline">
                <?php if (empty($care_schedules)): ?>
                    <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
                        <i data-lucide="calendar-heart" style="width: 32px; height: 32px; margin: 0 auto 0.5rem auto; opacity: 0.35;"></i>
                        <p style="font-size: 0.8rem;">Belum ada agenda terdaftar.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($care_schedules as $sch): ?>
                        <?php 
                        $status_class = $sch['status'] === 'Selesai' ? 'status-selesai' : 'status-pending'; 
                        ?>
                        <div class="timeline-item <?= $status_class; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content-wrapper">
                                <div class="timeline-meta">
                                    <span class="timeline-pet">🐾 <?= htmlspecialchars($sch['nama_hewan']); ?></span>
                                    <span class="timeline-date"><?= date('d M Y', strtotime($sch['tanggal'])); ?></span>
                                </div>
                                <h4 class="timeline-activity"><?= htmlspecialchars($sch['kegiatan']); ?></h4>
                                <div class="timeline-actions">
                                    <?php if ($sch['status'] !== 'Selesai'): ?>
                                        <form method="POST" action="selesaikan_jadwal.php" style="display: inline;">
                                            <?php csrf_input(); ?>
                                            <input type="hidden" name="id_jadwal" value="<?= $sch['id_jadwal']; ?>">
                                            <input type="hidden" name="action" value="selesai">
                                            <button type="submit" class="btn-timeline-action btn-timeline-complete" title="Tandai Selesai">
                                                <i data-lucide="check" style="width: 13px; height: 13px;"></i>
                                                Selesai
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge-selesai-text"><i data-lucide="check-check" style="width: 12px; height: 12px; display:inline-block; vertical-align:middle; margin-right:2px;"></i> Beres</span>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="selesaikan_jadwal.php" style="display: inline;">
                                        <?php csrf_input(); ?>
                                        <input type="hidden" name="id_jadwal" value="<?= $sch['id_jadwal']; ?>">
                                        <input type="hidden" name="action" value="hapus">
                                        <button type="submit" class="btn-timeline-action btn-timeline-delete" title="Hapus Agenda" onclick="return confirm('Hapus agenda ini?')">
                                            <i data-lucide="trash" style="width: 13px; height: 13px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL KTP DIGITAL -->
<!-- ========================================== -->
<div id="ktpModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-card-header">
            <h3>Holographic KTP Peliharaan</h3>
            <button class="modal-close-btn" id="closeKtpModal">&times;</button>
        </div>
        <div class="modal-card-body">
            <!-- Glassmorphic holographic card wrapper -->
            <div class="ktp-card-hologram" id="ktpCardContainer">
                <div class="ktp-card-glare"></div>
                
                <div class="ktp-card-header-inner">
                    <span class="ktp-card-logo">🐾 PetRelasi Premium</span>
                    <span class="ktp-card-chip"></span>
                </div>
                
                <div class="ktp-card-content">
                    <div class="ktp-avatar-section">
                        <div class="ktp-avatar-placeholder" id="ktpCardAvatar">M</div>
                        <div class="ktp-signature">Jejak Kaki</div>
                    </div>
                    <div class="ktp-info-section">
                        <div class="ktp-info-row">
                            <span class="ktp-label">NAMA:</span>
                            <span class="ktp-value" id="ktpCardNama">Milo</span>
                        </div>
                        <div class="ktp-info-row">
                            <span class="ktp-label">JENIS:</span>
                            <span class="ktp-value" id="ktpCardJenis">Kucing (Persia)</span>
                        </div>
                        <div class="ktp-info-row">
                            <span class="ktp-label">UMUR:</span>
                            <span class="ktp-value" id="ktpCardUmur">12 Bulan</span>
                        </div>
                        <div class="ktp-info-row">
                            <span class="ktp-label">BERAT:</span>
                            <span class="ktp-value" id="ktpCardBerat">4.5 Kg</span>
                        </div>
                        <div class="ktp-info-row">
                            <span class="ktp-label">SIFAT:</span>
                            <span class="ktp-value" id="ktpCardSifat">Manja, Tenang</span>
                        </div>
                        <div class="ktp-info-row">
                            <span class="ktp-label">PEMILIK:</span>
                            <span class="ktp-value"><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="ktp-card-footer">
                    <span class="ktp-card-id" id="ktpCardId">ID: PET-01</span>
                    <div class="ktp-qrcode">
                        <div class="qr-inner-box"></div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <button id="downloadKtpBtn" class="btn btn-primary" style="width: 100%;">
                    <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                    Unduh KTP Digital (.png)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL KALKULATOR NUTRISI -->
<!-- ========================================== -->
<div id="calcModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-card-header">
            <h3>Kalkulator Nutrisi & Energi Harian</h3>
            <button class="modal-close-btn" id="closeCalcModal">&times;</button>
        </div>
        <div class="modal-card-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="calc-pet-preview" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 0.75rem 1rem; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); display:block;">Peliharaan</span>
                    <strong style="font-size: 1.05rem; color: #ffffff;" id="calcPetName">Milo</strong>
                </div>
                <span class="badge badge-jenis" id="calcPetJenis" style="padding: 0.35rem 0.75rem;">Kucing</span>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="calcWeight" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.35rem; display:block;">Berat Badan Aktual (kg)</label>
                <input type="number" id="calcWeight" step="0.1" min="0.1" class="form-control" placeholder="Contoh: 4.5">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="calcActivity" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.35rem; display:block;">Status Aktivitas / Fisik</label>
                <select id="calcActivity" class="form-control">
                    <option value="sedentary">Kurang Aktif / Indoor / Dikebiri</option>
                    <option value="normal" selected>Normal / Aktif Sedang</option>
                    <option value="active">Sangat Aktif / Kerja / Hamil</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="calcFeedType" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.35rem; display:block;">Tipe Pakan yang Diberikan</label>
                <select id="calcFeedType" class="form-control">
                    <option value="kering" selected>Makanan Kering (Dry Food - ~350 kkal/100g)</option>
                    <option value="basah">Makanan Basah (Wet Food - ~85 kkal/100g)</option>
                    <option value="campuran">Campuran (50% Kering, 50% Basah)</option>
                </select>
            </div>

            <div class="calc-result-box" style="background: rgba(79, 70, 229, 0.1); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 12px; padding: 1.25rem; display:flex; flex-direction:column; gap:0.6rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary); font-size: 0.85rem;">Resting Energy (RER):</span>
                    <strong style="color: #ffffff; font-size: 0.9rem;" id="resultRER">0 kkal</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="color: var(--text-secondary); font-size: 0.85rem;">Daily Energy (DER):</span>
                    <strong style="color: #a5b4fc; font-size: 1.05rem;" id="resultDER">0 kkal</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.25rem;">
                    <span style="color: #ffffff; font-weight: 600; font-size: 0.9rem;">Porsi Harian:</span>
                    <strong style="color: #10b981; font-size: 1.15rem;" id="resultPortion">0 gram / hari</strong>
                </div>
                <p style="color: var(--text-muted); font-size: 0.72rem; line-height: 1.4; margin-top: 0.4rem; font-style:italic;">
                    * Formula ilmiah: RER = 70 * (Berat Badan)^0.75. DER disesuaikan dengan pengali status metabolisme.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // ==========================================
    // GLOWING PAW PRINT CURSOR TRAIL
    // ==========================================
    let lastPawX = 0;
    let lastPawY = 0;
    document.addEventListener('mousemove', (e) => {
        const dist = Math.hypot(e.pageX - lastPawX, e.pageY - lastPawY);
        if (dist > 60) {
            createPawPrint(e.pageX, e.pageY);
            lastPawX = e.pageX;
            lastPawY = e.pageY;
        }
    });

    function createPawPrint(x, y) {
        const paw = document.createElement('div');
        paw.className = 'paw-trail';
        paw.textContent = '🐾';
        paw.style.left = `${x}px`;
        paw.style.top = `${y}px`;
        
        const rot = Math.random() * 40 - 20; // Random rotation -20deg to 20deg
        paw.style.transform = `translate(-50%, -50%) rotate(${rot}deg)`;
        
        document.body.appendChild(paw);
        setTimeout(() => {
            paw.remove();
        }, 850);
    }

    // ==========================================
    // MODAL CONTROL UTILITIES
    // ==========================================
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal(overlay.id);
            }
        });
    });

    // ==========================================
    // KTP DIGITAL LOGIC
    // ==========================================
    let activePetKtpData = null;

    document.querySelectorAll('.ktp-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
            const petData = {
                id: btn.getAttribute('data-id'),
                nama: btn.getAttribute('data-nama'),
                umur: btn.getAttribute('data-umur'),
                berat: btn.getAttribute('data-berat'),
                jenis: btn.getAttribute('data-jenis'),
                ras: btn.getAttribute('data-ras'),
                sifat: btn.getAttribute('data-sifat'),
                ukuran: btn.getAttribute('data-ukuran'),
                perawatan: btn.getAttribute('data-perawatan'),
                apartemen: btn.getAttribute('data-apartemen')
            };
            
            activePetKtpData = petData;
            
            // Set text labels inside modal HTML
            document.getElementById('ktpCardNama').textContent = petData.nama;
            document.getElementById('ktpCardJenis').textContent = `${petData.jenis} (${petData.ras})`;
            document.getElementById('ktpCardUmur').textContent = `${petData.umur} Bulan`;
            document.getElementById('ktpCardBerat').textContent = `${petData.berat} Kg`;
            document.getElementById('ktpCardSifat').textContent = petData.sifat;
            document.getElementById('ktpCardId').textContent = `ID: PET-${petData.id.padStart(2, '0')}`;
            
            // Avatar profile initials
            const avatarDiv = document.getElementById('ktpCardAvatar');
            avatarDiv.textContent = petData.nama.charAt(0).toUpperCase();
            
            // Assign gradient backgrounds based on species
            if (petData.jenis.toLowerCase().includes('kucing')) {
                avatarDiv.style.background = 'linear-gradient(135deg, #6366f1, #4f46e5)';
            } else if (petData.jenis.toLowerCase().includes('anjing')) {
                avatarDiv.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            } else {
                avatarDiv.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
            }
            
            openModal('ktpModal');
        });
    });

    document.getElementById('closeKtpModal').addEventListener('click', () => closeModal('ktpModal'));

    // Holographic tilt effect on mouse movement
    const ktpContainer = document.getElementById('ktpCardContainer');
    const ktpGlare = ktpContainer.querySelector('.ktp-card-glare');
    
    ktpContainer.addEventListener('mousemove', (e) => {
        const rect = ktpContainer.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const xc = rect.width / 2;
        const yc = rect.height / 2;
        
        const tiltX = (yc - y) / 10;
        const tiltY = (x - xc) / 10;
        ktpContainer.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(1.03)`;
        
        const glareX = (x / rect.width) * 100;
        const glareY = (y / rect.height) * 100;
        ktpGlare.style.background = `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255, 255, 255, 0.15) 0%, transparent 65%)`;
    });

    ktpContainer.addEventListener('mouseleave', () => {
        ktpContainer.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
        ktpGlare.style.background = 'transparent';
    });

    // Drawing Paw Vector helper for Canvas
    function drawPawPrint(ctx, cx, cy, size) {
        ctx.fillStyle = 'rgba(255, 255, 255, 0.45)';
        // Main pad
        ctx.beginPath();
        ctx.arc(cx, cy + size * 0.1, size * 0.28, 0, Math.PI * 2);
        ctx.fill();
        // 4 toes
        ctx.beginPath();
        ctx.arc(cx - size * 0.35, cy - size * 0.12, size * 0.14, 0, Math.PI * 2); // left toe
        ctx.fill();
        ctx.beginPath();
        ctx.arc(cx - size * 0.13, cy - size * 0.33, size * 0.14, 0, Math.PI * 2); // center-left toe
        ctx.fill();
        ctx.beginPath();
        ctx.arc(cx + size * 0.13, cy - size * 0.33, size * 0.14, 0, Math.PI * 2); // center-right toe
        ctx.fill();
        ctx.beginPath();
        ctx.arc(cx + size * 0.35, cy - size * 0.12, size * 0.14, 0, Math.PI * 2); // right toe
        ctx.fill();
    }

    // Export HTML5 Canvas as PNG
    document.getElementById('downloadKtpBtn').addEventListener('click', () => {
        if (!activePetKtpData) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = 500;
        canvas.height = 300;
        const ctx = canvas.getContext('2d');
        
        // 1. Draw elegant dark card background gradient
        const bgGrad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        bgGrad.addColorStop(0, '#090e1a');
        bgGrad.addColorStop(0.5, '#0e172e');
        bgGrad.addColorStop(1, '#1b2a4a');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // 2. Draw border
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
        ctx.lineWidth = 2;
        ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2);
        
        // 3. Draw Header Title
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 16px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('🐾 PetRelasi Premium', 25, 35);
        
        // 4. Draw card chip icon (gold rectangle with grid lines)
        ctx.fillStyle = '#d97706'; // Gold color
        ctx.beginPath();
        ctx.roundRect(canvas.width - 65, 20, 40, 28, 5);
        ctx.fill();
        ctx.strokeStyle = '#92400e';
        ctx.lineWidth = 1;
        ctx.strokeRect(canvas.width - 60, 24, 30, 20);
        
        // 5. Draw Profile Circular Avatar (immune to CORS)
        const avX = 65;
        const avY = 115;
        const avRadius = 40;
        
        const avGrad = ctx.createLinearGradient(avX - avRadius, avY - avRadius, avX + avRadius, avY + avRadius);
        if (activePetKtpData.jenis.toLowerCase().includes('kucing')) {
            avGrad.addColorStop(0, '#6366f1');
            avGrad.addColorStop(1, '#4f46e5');
        } else if (activePetKtpData.jenis.toLowerCase().includes('anjing')) {
            avGrad.addColorStop(0, '#10b981');
            avGrad.addColorStop(1, '#059669');
        } else {
            avGrad.addColorStop(0, '#f59e0b');
            avGrad.addColorStop(1, '#d97706');
        }
        
        ctx.fillStyle = avGrad;
        ctx.beginPath();
        ctx.arc(avX, avY, avRadius, 0, Math.PI * 2);
        ctx.fill();
        
        // Profile Letter Initial
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 36px "Plus Jakarta Sans", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(activePetKtpData.nama.charAt(0).toUpperCase(), avX, avY + 2);
        
        // Reset text alignment for other items
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
        
        // 6. Draw Signature / Paw Print stamp
        ctx.fillStyle = '#9ca3af';
        ctx.font = 'normal 9px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('JEJAK KAKI', 40, 185);
        drawPawPrint(ctx, 65, 220, 24);
        
        // 7. Draw Pet Metadata Labels and Details
        const startX = 145;
        const startY = 80;
        const rowHeight = 22;
        
        const infoRows = [
            { label: 'NAMA', val: activePetKtpData.nama.toUpperCase() },
            { label: 'JENIS', val: `${activePetKtpData.jenis} (${activePetKtpData.ras})` },
            { label: 'UMUR', val: `${activePetKtpData.umur} BULAN` },
            { label: 'BERAT', val: `${activePetKtpData.berat} KG` },
            { label: 'SIFAT', val: activePetKtpData.sifat },
            { label: 'PEMILIK', val: '<?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?>'.toUpperCase() }
        ];
        
        infoRows.forEach((row, index) => {
            const currentY = startY + (index * rowHeight);
            
            // Draw label in small grey caps
            ctx.fillStyle = '#9ca3af';
            ctx.font = 'bold 9px "Plus Jakarta Sans", sans-serif';
            ctx.fillText(row.label, startX, currentY);
            
            // Draw value in white bold
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
            ctx.fillText(row.val, startX + 65, currentY);
        });
        
        // 8. Draw Card Footer ID & Mock QR Code
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        ctx.font = 'bold 11px monospace';
        ctx.fillText(`ID: PET-${activePetKtpData.id.padStart(2, '0')}`, 25, 275);
        
        // Draw pixelated QR Code matrix
        const qrX = canvas.width - 65;
        const qrY = canvas.height - 65;
        const qrSize = 48;
        
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX, qrY, qrSize, qrSize);
        ctx.fillStyle = '#080d1a';
        
        // Corner squares of QR
        ctx.fillRect(qrX + 2, qrY + 2, 10, 10);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX + 4, qrY + 4, 6, 6);
        ctx.fillStyle = '#080d1a';
        ctx.fillRect(qrX + 5, qrY + 5, 4, 4);

        ctx.fillRect(qrX + qrSize - 12, qrY + 2, 10, 10);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX + qrSize - 10, qrY + 4, 6, 6);
        ctx.fillStyle = '#080d1a';
        ctx.fillRect(qrX + qrSize - 9, qrY + 5, 4, 4);

        ctx.fillRect(qrX + 2, qrY + qrSize - 12, 10, 10);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX + 4, qrY + qrSize - 10, 6, 6);
        ctx.fillStyle = '#080d1a';
        ctx.fillRect(qrX + 5, qrY + qrSize - 9, 4, 4);
        
        // Random pixel matrix noise inside QR code
        for (let x = 12; x < qrSize - 12; x += 4) {
            for (let y = 2; y < qrSize - 2; y += 4) {
                if (Math.random() > 0.45) {
                    ctx.fillStyle = '#080d1a';
                    ctx.fillRect(qrX + x, qrY + y, 4, 4);
                }
            }
        }
        for (let x = 2; x < qrSize - 2; x += 4) {
            for (let y = 12; y < qrSize - 12; y += 4) {
                if (Math.random() > 0.45) {
                    ctx.fillStyle = '#080d1a';
                    ctx.fillRect(qrX + x, qrY + y, 4, 4);
                }
            }
        }
        
        // Trigger file download
        const link = document.createElement('a');
        link.download = `KTP_${activePetKtpData.nama}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    // ==========================================
    // NUTRITION CALCULATOR LOGIC
    // ==========================================
    let calcActivePetSpecies = "";

    document.querySelectorAll('.calc-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
            const petName = btn.getAttribute('data-nama');
            const petWeight = btn.getAttribute('data-berat');
            const petJenis = btn.getAttribute('data-jenis');
            
            calcActivePetSpecies = petJenis;
            document.getElementById('calcPetName').textContent = petName;
            document.getElementById('calcPetJenis').textContent = petJenis;
            document.getElementById('calcWeight').value = petWeight;
            
            // Adjust badges styles based on animal type
            const petJenisBadge = document.getElementById('calcPetJenis');
            if (petJenis.toLowerCase().includes('kucing')) {
                petJenisBadge.className = 'badge badge-jenis';
            } else if (petJenis.toLowerCase().includes('anjing')) {
                petJenisBadge.className = 'badge badge-ras';
            } else {
                petJenisBadge.className = 'badge';
                petJenisBadge.style.background = 'rgba(245, 158, 11, 0.15)';
                petJenisBadge.style.color = '#fbbf24';
                petJenisBadge.style.border = '1px solid rgba(245, 158, 11, 0.3)';
            }
            
            calculateCalories();
            openModal('calcModal');
        });
    });

    document.getElementById('closeCalcModal').addEventListener('click', () => closeModal('calcModal'));

    // Calculator inputs event listeners
    const inputWeight = document.getElementById('calcWeight');
    const inputActivity = document.getElementById('calcActivity');
    const inputFeedType = document.getElementById('calcFeedType');

    [inputWeight, inputActivity, inputFeedType].forEach(el => {
        el.addEventListener('input', calculateCalories);
        el.addEventListener('change', calculateCalories);
    });

    function calculateCalories() {
        const weight = parseFloat(inputWeight.value);
        if (isNaN(weight) || weight <= 0) {
            document.getElementById('resultRER').textContent = '0 kkal';
            document.getElementById('resultDER').textContent = '0 kkal';
            document.getElementById('resultPortion').textContent = '0 gram / hari';
            return;
        }

        // 1. Calculate Resting Energy Requirement (RER)
        // Scientific Formula: RER = 70 * (weight)^0.75
        const rer = 70 * Math.pow(weight, 0.75);
        
        // 2. Determine metabolic factor based on animal type and activity level
        const isCat = calcActivePetSpecies.toLowerCase().includes('kucing');
        const activity = inputActivity.value;
        let factor = 1.0;
        
        if (isCat) {
            // Feline specific values
            if (activity === 'sedentary') factor = 1.2;
            else if (activity === 'normal') factor = 1.4;
            else if (activity === 'active') factor = 1.6;
        } else {
            // Canine or others values
            if (activity === 'sedentary') factor = 1.6;
            else if (activity === 'normal') factor = 1.8;
            else if (activity === 'active') factor = 2.0;
        }
        
        // 3. Calculate Daily Energy Requirement (DER)
        const der = rer * factor;
        
        // 4. Calculate food portion based on food type density
        const feedType = inputFeedType.value;
        let portionText = '';
        
        if (feedType === 'kering') {
            // Dry food is about 350 kcal per 100g -> 3.5 kcal/g
            const dryPortion = der / 3.5;
            portionText = `${Math.round(dryPortion)} gram / hari`;
        } else if (feedType === 'basah') {
            // Wet food is about 85 kcal per 100g -> 0.85 kcal/g
            const wetPortion = der / 0.85;
            portionText = `${Math.round(wetPortion)} gram / hari`;
        } else if (feedType === 'campuran') {
            // Mixed (50% calorie dry, 50% calorie wet)
            const dryPortion = (der * 0.5) / 3.5;
            const wetPortion = (der * 0.5) / 0.85;
            portionText = `${Math.round(dryPortion)}g Kering + ${Math.round(wetPortion)}g Basah`;
        }
        
        // Display output
        document.getElementById('resultRER').textContent = `${Math.round(rer)} kkal`;
        document.getElementById('resultDER').textContent = `${Math.round(der)} kkal`;
        document.getElementById('resultPortion').textContent = portionText;
    }

    // ==========================================
    // CHARTS DATA & CREATION
    // ==========================================
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryLabels = <?= json_encode(array_column($chart_categories, 'nama_jenis')); ?>;
    const categoryCounts = <?= json_encode(array_column($chart_categories, 'count')); ?>;
    
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryCounts,
                backgroundColor: [
                    'rgba(79, 70, 229, 0.6)',  // Indigo
                    'rgba(16, 185, 129, 0.6)', // Emerald
                    'rgba(245, 158, 11, 0.6)', // Amber
                    'rgba(59, 130, 246, 0.6)', // Blue
                    'rgba(239, 68, 68, 0.6)'   // Red
                ],
                borderColor: 'rgba(17, 24, 43, 0.95)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#9ca3af',
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 10
                        },
                        boxWidth: 8,
                        padding: 6
                    }
                },
                title: {
                    display: true,
                    text: 'Kategori Jenis',
                    color: '#ffffff',
                    font: {
                        family: 'Plus Jakarta Sans',
                        size: 11,
                        weight: 'bold'
                    },
                    padding: { bottom: 5 }
                }
            }
        }
    });

    const breedCtx = document.getElementById('breedChart').getContext('2d');
    const breedLabels = <?= json_encode(array_column($chart_breeds, 'nama_ras')); ?>;
    const breedCounts = <?= json_encode(array_column($chart_breeds, 'count')); ?>;

    new Chart(breedCtx, {
        type: 'bar',
        data: {
            labels: breedLabels,
            datasets: [{
                label: 'Jumlah Peliharaan',
                data: breedCounts,
                backgroundColor: 'rgba(99, 102, 241, 0.45)', // Indigo-light
                borderColor: 'rgba(99, 102, 241, 0.85)',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 8 }
                    }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 8 },
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Sebaran Ras Terbanyak',
                    color: '#ffffff',
                    font: {
                        family: 'Plus Jakarta Sans',
                        size: 11,
                        weight: 'bold'
                    },
                    padding: { bottom: 5 }
                }
            }
        }
    });
</script>
</body>
</html>

