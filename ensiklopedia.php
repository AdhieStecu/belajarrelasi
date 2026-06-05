<?php
// Start session for auth checks
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user']);
$user_name = $is_logged_in ? $_SESSION['user']['nama_lengkap'] : '';

require_once 'config.php';

// Fetch options for filters
try {
    $jenis_options = $pdo->query("SELECT * FROM jenis ORDER BY nama_jenis ASC")->fetchAll();
} catch (PDOException $e) {
    $jenis_options = [];
}

// 1. Get Filters from GET
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_jenis = isset($_GET['id_jenis']) ? trim($_GET['id_jenis']) : '';
$filter_ukuran = isset($_GET['ukuran']) ? trim($_GET['ukuran']) : '';
$filter_perawatan = isset($_GET['perawatan_bulu']) ? trim($_GET['perawatan_bulu']) : '';
$filter_apartemen = isset($_GET['cocok_apartemen']) ? 1 : '';

// 2. Build Query
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(r.nama_ras LIKE :search OR r.sifat LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filter_jenis !== '') {
    $where_clauses[] = "r.id_jenis = :id_jenis";
    $params['id_jenis'] = $filter_jenis;
}

if ($filter_ukuran !== '') {
    $where_clauses[] = "r.ukuran = :ukuran";
    $params['ukuran'] = $filter_ukuran;
}

if ($filter_perawatan !== '') {
    $where_clauses[] = "r.perawatan_bulu = :perawatan";
    $params['perawatan'] = $filter_perawatan;
}

if ($filter_apartemen !== '') {
    $where_clauses[] = "r.cocok_apartemen = 1";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    $query = "SELECT r.*, j.nama_jenis 
              FROM ras r
              JOIN jenis j ON r.id_jenis = j.id_jenis
              $where_sql
              ORDER BY j.nama_jenis ASC, r.nama_ras ASC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ras_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $ras_list = [];
    $error_msg = "Gagal memuat data ensiklopedia: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ensiklopedia Ras - PetRelasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .encyclopedia-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .encyclopedia-layout {
                grid-template-columns: 1fr;
            }
        }

        .filter-sidebar {
            position: sticky;
            top: 100px;
        }

        .breed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .breed-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .breed-card:hover {
            transform: translateY(-4px);
            border-color: rgba(79, 70, 229, 0.3);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.12);
        }

        .breed-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .breed-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
        }

        .trait-tag {
            display: inline-block;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            color: var(--text-secondary);
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }

        .spec-list {
            margin-top: 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .spec-item i {
            width: 14px;
            height: 14px;
            color: #818cf8;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="main-navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <i data-lucide="paw-print" style="width: 24px; height: 24px; color: #818cf8; vertical-align: middle;"></i> PetRelasi
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i data-lucide="home" style="width: 16px; height: 16px;"></i> Beranda</a></li>
            <li><a href="ensiklopedia.php" class="nav-link active"><i data-lucide="book-open" style="width: 16px; height: 16px;"></i> Ensiklopedia</a></li>
            <?php if ($is_logged_in): ?>
                <li><a href="dashboard.php" class="nav-link"><i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i> Dashboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-right">
            <?php if ($is_logged_in): ?>
                <a href="profile.php" class="nav-user" style="text-decoration: none;">
                    <span class="user-badge"><i data-lucide="user" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i><?= htmlspecialchars($user_name); ?></span>
                </a>
                <a href="logout.php" class="btn btn-logout btn-sm"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Keluar</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Masuk</a>
                <a href="register.php" class="btn btn-primary btn-sm">Mulai Sekarang</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="page-wrapper">
    <div class="container" style="max-width: 1200px;">
        <!-- Header -->
        <header>
            <div class="brand">
                <h1>Ensiklopedia Ras Hewan</h1>
                <p>Temukan karakteristik unik, ukuran, dan perawatan spesifik dari berbagai ras peliharaan</p>
            </div>
            <?php if ($is_logged_in): ?>
                <a href="kelola_relasi.php" class="btn btn-primary">
                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                    Kelola Ras
                </a>
            <?php endif; ?>
        </header>

        <div class="encyclopedia-layout">
            
            <!-- LEFT: Filters -->
            <div class="card filter-sidebar" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="sliders-horizontal" style="width: 16px; height: 16px; color: #818cf8;"></i>
                    Filter Pencarian
                </h3>
                <form method="GET" action="ensiklopedia.php">
                    <div class="form-group">
                        <label for="q">Kata Kunci / Sifat</label>
                        <input type="text" id="q" name="q" class="form-control" placeholder="Cari ras atau sifat..." value="<?= htmlspecialchars($search); ?>">
                    </div>

                    <div class="form-group">
                        <label for="id_jenis">Kategori Hewan</label>
                        <select id="id_jenis" name="id_jenis" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Hewan --</option>
                            <?php foreach ($jenis_options as $jenis): ?>
                                <option value="<?= $jenis['id_jenis']; ?>" <?= $filter_jenis == $jenis['id_jenis'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($jenis['nama_jenis']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ukuran">Ukuran Tubuh</label>
                        <select id="ukuran" name="ukuran" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Ukuran --</option>
                            <option value="Kecil" <?= $filter_ukuran === 'Kecil' ? 'selected' : ''; ?>>Kecil</option>
                            <option value="Sedang" <?= $filter_ukuran === 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="Besar" <?= $filter_ukuran === 'Besar' ? 'selected' : ''; ?>>Besar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="perawatan_bulu">Perawatan Bulu</label>
                        <select id="perawatan_bulu" name="perawatan_bulu" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Perawatan --</option>
                            <option value="Rendah" <?= $filter_perawatan === 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                            <option value="Sedang" <?= $filter_perawatan === 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="Tinggi" <?= $filter_perawatan === 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.25rem;">
                        <input type="checkbox" id="cocok_apartemen" name="cocok_apartemen" value="1" <?= $filter_apartemen === 1 ? 'checked' : ''; ?> onchange="this.form.submit()" style="width: auto; cursor: pointer;">
                        <label for="cocok_apartemen" style="margin-bottom: 0; cursor: pointer; font-size: 0.85rem;">Cocok di Apartemen</label>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                        <a href="ensiklopedia.php" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">Reset</a>
                        <button type="submit" class="btn btn-primary btn-sm" style="flex: 1.5;">Terapkan</button>
                    </div>
                </form>
            </div>

            <!-- RIGHT: Breed Results Grid -->
            <div>
                <?php if (empty($ras_list)): ?>
                    <div class="card" style="text-align: center; padding: 4rem 2rem;">
                        <i data-lucide="search-code" style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 1rem auto; opacity: 0.4;"></i>
                        <h3 style="color: #ffffff; margin-bottom: 0.5rem;">Hasil tidak ditemukan</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Coba ubah kata kunci pencarian atau bersihkan filter Anda.</p>
                    </div>
                <?php else: ?>
                    <div class="breed-grid">
                        <?php foreach ($ras_list as $ras): ?>
                            <div class="breed-card">
                                <div>
                                    <div class="breed-header">
                                        <div class="breed-name"><?= htmlspecialchars($ras['nama_ras']); ?></div>
                                        <span class="badge badge-jenis" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                                            <?= htmlspecialchars($ras['nama_jenis']); ?>
                                        </span>
                                    </div>

                                    <!-- Traits Tags -->
                                    <div style="margin-top: 0.5rem;">
                                        <?php 
                                        if (!empty($ras['sifat'])):
                                            $traits = explode(',', $ras['sifat']);
                                            foreach ($traits as $trait):
                                        ?>
                                            <span class="trait-tag">#<?= htmlspecialchars(trim($trait)); ?></span>
                                        <?php 
                                            endforeach;
                                        else:
                                            echo '<span class="trait-tag" style="font-style: italic;">Sifat belum terdefinisi</span>';
                                        endif; 
                                        ?>
                                    </div>
                                </div>

                                <!-- Specs list -->
                                <div class="spec-list">
                                    <div class="spec-item">
                                        <i data-lucide="expand" title="Ukuran"></i>
                                        <span>Ukuran: <strong><?= htmlspecialchars($ras['ukuran'] ?? 'Sedang'); ?></strong></span>
                                    </div>
                                    <div class="spec-item">
                                        <i data-lucide="scissors" title="Perawatan"></i>
                                        <span>Perawatan Bulu: <strong><?= htmlspecialchars($ras['perawatan_bulu'] ?? 'Sedang'); ?></strong></span>
                                    </div>
                                    <div class="spec-item">
                                        <?php if (($ras['cocok_apartemen'] ?? 0) == 1): ?>
                                            <i data-lucide="building-2" style="color: #10b981;"></i>
                                            <span style="color: #a7f3d0;">Sangat cocok untuk Apartemen</span>
                                        <?php else: ?>
                                            <i data-lucide="trees" style="color: #f59e0b;"></i>
                                            <span>Butuh halaman/ruang terbuka</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
