<?php
// Start session to detect active logins
session_start();
$is_logged_in = isset($_SESSION['user']);
$user_name = $is_logged_in ? $_SESSION['user']['nama_lengkap'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetRelasi - Kelola Peliharaan dengan Mudah</title>
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
 
<!-- Landing Navbar -->
<nav class="main-navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <i data-lucide="paw-print" style="width: 24px; height: 24px; color: #818cf8; vertical-align: middle;"></i> PetRelasi
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link active"><i data-lucide="home" style="width: 14px; height: 14px;"></i> Beranda</a></li>
            <li><a href="ensiklopedia.php" class="nav-link"><i data-lucide="book-open" style="width: 14px; height: 14px;"></i> Ensiklopedia</a></li>
            <li><a href="#features" class="nav-link"><i data-lucide="sparkles" style="width: 14px; height: 14px;"></i> Fitur</a></li>
        </ul>
        <div class="nav-right">
            <?php if ($is_logged_in): ?>
                <a href="profile.php" class="nav-user" style="text-decoration: none;">
                    <span class="user-badge"><i data-lucide="user" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i><?= htmlspecialchars($user_name); ?></span>
                </a>
                <a href="dashboard.php" class="btn btn-primary btn-sm"><i data-lucide="layout-dashboard" style="width: 14px; height: 14px;"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-logout btn-sm"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Keluar</a>
            <?php else: ?>
                <a href="login.php" class="nav-link"><i data-lucide="log-in" style="width: 14px; height: 14px;"></i> Masuk</a>
                <a href="register.php" class="btn btn-primary btn-sm"><i data-lucide="arrow-right-circle" style="width: 14px; height: 14px;"></i> Mulai Sekarang</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
 
<div class="page-wrapper">
    <!-- Hero Section -->
    <section class="hero-section container">
        <div class="hero-content">
            <span class="hero-badge"><i data-lucide="award" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px; color: #a5b4fc;"></i> Web Manajemen Peliharaan #1</span>
            <h1>Kelola Peliharaan Anda dengan Sistem Relasi Modern</h1>
            <p>Platform intuitif untuk mendokumentasikan peliharaan kesayangan, mengelompokkan kategori ras, dan menganalisis relasi database secara presisi dan efisien.</p>
            <div class="hero-actions">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                        Kembali ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">
                        <i data-lucide="rocket" style="width: 18px; height: 18px;"></i>
                        Mulai Kelola Peliharaan
                    </a>
                    <a href="login.php" class="btn btn-secondary">
                        <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                        Masuk ke Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-visual">
            <div class="image-container">
                <img src="assets/images/hero_pets.png" alt="PetRelasi Hero Banner">
            </div>
        </div>
    </section>
 
    <!-- Features Section -->
    <section id="features" class="features-section container">
        <div class="section-header">
            <h2>Mengapa Memilih PetRelasi?</h2>
            <p>Fitur premium yang didesain khusus untuk efisiensi manajemen data terstruktur.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card-premium">
                <div class="feature-icon" style="color: #818cf8;"><i data-lucide="git-branch" style="width: 36px; height: 36px;"></i></div>
                <h3>Relasi Database Dinamis</h3>
                <p>Menggunakan konsep relasi database *One-to-Many* yang solid, menghubungkan kategori jenis, ras, dan profil hewan peliharaan secara terintegrasi.</p>
            </div>
            <div class="feature-card-premium">
                <div class="feature-icon" style="color: #34d399;"><i data-lucide="bar-chart-3" style="width: 36px; height: 36px;"></i></div>
                <h3>Dashboard Statistik</h3>
                <p>Analisis visual instan berupa jumlah total peliharaan, total variasi ras, kategori jenis, serta filter pencarian data real-time.</p>
            </div>
            <div class="feature-card-premium">
                <div class="feature-icon" style="color: #fbbf24;"><i data-lucide="shield-check" style="width: 36px; height: 36px;"></i></div>
                <h3>Keamanan Terproteksi</h3>
                <p>Keamanan rute halaman dengan autentikasi enkripsi tingkat tinggi (BCRYPT) menjamin data penting Anda terlindung dari akses ilegal.</p>
            </div>
        </div>
    </section>
 
    <!-- Relational Visualizer Section -->
    <section id="relation-map" class="relation-map-section container">
        <div class="section-header">
            <h2>Peta Relasi Database</h2>
            <p>Bagaimana data peliharaan Anda terhubung secara relasional di belakang layar.</p>
        </div>
        <div class="relation-visualizer-container">
            <div class="visualizer-card">
                <div class="visualizer-header type-header">
                    <span>1</span> KATEGORI JENIS
                </div>
                <div class="visualizer-body">
                    <p class="table-name">tabel: <code>jenis</code></p>
                    <div class="field-item"><span><i data-lucide="key" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #fbbf24;"></i> id_jenis</span> <span class="field-type">INT</span></div>
                    <div class="field-item"><span><i data-lucide="type" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> nama_jenis</span> <span class="field-type">VARCHAR</span></div>
                </div>
                <div class="visualizer-footer">
                    Contoh: Kucing, Anjing, Kelinci
                </div>
            </div>
 
            <div class="visualizer-arrow"><i data-lucide="arrow-right" style="width: 24px; height: 24px;"></i></div>
 
            <div class="visualizer-card">
                <div class="visualizer-header breed-header">
                    <span>N</span> RAS HEWAN
                </div>
                <div class="visualizer-body">
                    <p class="table-name">tabel: <code>ras</code></p>
                    <div class="field-item"><span><i data-lucide="key" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #fbbf24;"></i> id_ras</span> <span class="field-type">INT</span></div>
                    <div class="field-item"><span><i data-lucide="type" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> nama_ras</span> <span class="field-type">VARCHAR</span></div>
                    <div class="field-item fk-field"><span><i data-lucide="link" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #60a5fa;"></i> id_jenis</span> <span class="field-type">INT (FK)</span></div>
                </div>
                <div class="visualizer-footer">
                    Contoh: Persia, Golden Retriever
                </div>
            </div>
 
            <div class="visualizer-arrow"><i data-lucide="arrow-right" style="width: 24px; height: 24px;"></i></div>
 
            <div class="visualizer-card">
                <div class="visualizer-header pet-header">
                    <span>N</span> PROFIL HEWAN
                </div>
                <div class="visualizer-body">
                    <p class="table-name">tabel: <code>hewan</code></p>
                    <div class="field-item"><span><i data-lucide="key" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #fbbf24;"></i> id_hewan</span> <span class="field-type">INT</span></div>
                    <div class="field-item"><span><i data-lucide="type" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> nama_hewan</span> <span class="field-type">VARCHAR</span></div>
                    <div class="field-item"><span><i data-lucide="calendar" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> umur</span> <span class="field-type">INT</span></div>
                    <div class="field-item fk-field"><span><i data-lucide="link" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #60a5fa;"></i> id_ras</span> <span class="field-type">INT (FK)</span></div>
                </div>
                <div class="visualizer-footer">
                    Contoh: Milo (Kucing Persia)
                </div>
            </div>
        </div>
    </section>
 
    <!-- Bottom Call-To-Action -->
    <section id="about" class="cta-section container">
        <div class="cta-card">
            <h2>Mulai Kelola Profil Peliharaan Anda Sekarang</h2>
            <p>Bergabunglah bersama ribuan pecinta peliharaan lainnya untuk mengorganisir data hewan kesayangan Anda dengan aman dan terstruktur.</p>
            <div style="margin-top: 2rem;">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary" style="padding: 0.85rem 2rem;">
                        <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                        Masuk ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary" style="padding: 0.85rem 2rem;">
                        <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                        Buat Akun Gratis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
 
    <!-- Footer -->
    <footer class="landing-footer container">
        <div class="footer-divider"></div>
        <div class="footer-content">
            <p>&copy; 2026 PetRelasi Platform. Dibuat dengan <i data-lucide="heart" style="width: 12px; height: 12px; color: #ef4444; fill: #ef4444; display: inline;"></i> untuk pembelajaran Database Relasional PHP.</p>
            <div class="footer-links">
                <a href="#features">Fitur</a>
                <a href="#relation-map">Peta Relasi</a>
                <a href="login.php">Masuk</a>
            </div>
        </div>
    </footer>
</div>
 
<script>
    lucide.createIcons();
</script>
</body>
</html>
