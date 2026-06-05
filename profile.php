<?php
// Require authentication & database connection
require_once 'auth.php';
require_once 'config.php';

$id_user = $_SESSION['user']['id_user'];
$success_msg = '';
$error_msg = '';

// Fetch current user details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "User tidak ditemukan!";
        header("Location: dashboard.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Kesalahan database: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Keamanan CSRF tidak valid. Silakan coba lagi.";
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        // 1. Update Profile Information (Name and Username)
        if ($action === 'update_profile') {
            $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            
            if ($nama_lengkap === '' || $username === '') {
                $error_msg = "Nama lengkap dan username tidak boleh kosong!";
            } elseif (strlen($username) < 3) {
                $error_msg = "Username minimal harus 3 karakter!";
            } elseif (preg_match('/\s/', $username)) {
                $error_msg = "Username tidak boleh mengandung spasi!";
            } else {
                try {
                    // Check duplicate username for other users
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id_user != ?");
                    $check_stmt->execute([$username, $id_user]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_msg = "Username '$username' sudah terdaftar! Gunakan username lain.";
                    } else {
                        // Update
                        $update_stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ? WHERE id_user = ?");
                        $update_stmt->execute([$nama_lengkap, $username, $id_user]);
                        
                        // Update session values
                        $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
                        $_SESSION['user']['username'] = $username;
                        
                        $_SESSION['success'] = "Profil berhasil diperbarui!";
                        header("Location: profile.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = "Gagal memperbarui profil: " . $e->getMessage();
                }
            }
        }
        
        // 2. Change Password
        elseif ($action === 'change_password') {
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                $error_msg = "Semua bidang password wajib diisi!";
            } elseif (strlen($new_password) < 6) {
                $error_msg = "Password baru minimal harus 6 karakter!";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "Konfirmasi password baru tidak cocok!";
            } else {
                // Verify current password
                if (password_verify($current_password, $user['password'])) {
                    try {
                        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $pwd_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                        $pwd_stmt->execute([$new_hashed, $id_user]);
                        
                        $_SESSION['success'] = "Kata sandi berhasil diubah!";
                        header("Location: profile.php");
                        exit;
                    } catch (PDOException $e) {
                        $error_msg = "Gagal mengubah kata sandi: " . $e->getMessage();
                    }
                } else {
                    $error_msg = "Kata sandi saat ini salah!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Belajar Relasi</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 800px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .avatar-circle-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            color: #ffffff;
            font-weight: 700;
            margin: 0 auto 1rem auto;
            border: 3px solid rgba(255,255,255,0.08);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
        }

        .profile-summary-card {
            text-align: center;
            padding: 2.5rem 2rem;
        }

        .profile-summary-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .profile-summary-card p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .meta-list {
            text-align: left;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 1.25rem;
            margin-top: 1.25rem;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .meta-label {
            color: var(--text-muted);
        }

        .meta-val {
            color: #ffffff;
            font-weight: 500;
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
            <li><a href="kelola_relasi.php" class="nav-link"><i data-lucide="git-branch" style="width: 16px; height: 16px;"></i> Kelola Relasi</a></li>
            <li><a href="ensiklopedia.php" class="nav-link"><i data-lucide="book-open" style="width: 16px; height: 16px;"></i> Ensiklopedia</a></li>
            <li><a href="tambah.php" class="nav-link"><i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Tambah Hewan</a></li>
        </ul>
        <div class="nav-right">
            <a href="profile.php" class="nav-user active" style="text-decoration: none;">
                <span class="user-badge"><i data-lucide="user" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i><?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?></span>
            </a>
            <a href="logout.php" class="btn btn-logout btn-sm"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i></a>
        </div>
    </div>
</nav>

<div class="page-wrapper">
    <div class="container" style="max-width: 950px;">
        <!-- Header -->
        <header>
            <div class="brand">
                <h1>Pengaturan Profil</h1>
                <p>Kelola kredensial akun administrator Anda</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Ke Dashboard
            </a>
        </header>

        <!-- Alert Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                <?= htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            
            <!-- LEFT: Profile Info Card -->
            <div class="card profile-summary-card">
                <div class="avatar-circle-large">
                    <?= strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                </div>
                <h2><?= htmlspecialchars($user['nama_lengkap']); ?></h2>
                <p>@<?= htmlspecialchars($user['username']); ?></p>
                
                <span class="badge badge-jenis" style="background: rgba(16, 185, 129, 0.15); color: #a7f3d0; border-color: rgba(16, 185, 129, 0.3);">
                    Administrator Utama
                </span>
                
                <div class="meta-list">
                    <div class="meta-item">
                        <span class="meta-label">User ID</span>
                        <span class="meta-val">#<?= $user['id_user']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Status Akun</span>
                        <span class="meta-val" style="color: #10b981;">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Edit Form Area -->
            <div>
                <!-- Card Update Profil -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="margin-bottom: 1.5rem;">
                        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="user-cog" style="color: #818cf8; width: 20px; height: 20px;"></i>
                            Informasi Profil
                        </h3>
                    </div>
                    
                    <form method="POST" action="profile.php">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required autocomplete="name">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required autocomplete="username">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <!-- Card Ganti Password -->
                <div class="card">
                    <div class="card-header" style="margin-bottom: 1.5rem;">
                        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="key" style="color: #f43f5e; width: 20px; height: 20px;"></i>
                            Ubah Kata Sandi
                        </h3>
                    </div>
                    
                    <form method="POST" action="profile.php">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Kata Sandi Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">Kata Sandi Baru (Minimal 6 karakter)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-danger" style="width: 100%; background: #ef4444;">
                            <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                            Ubah Kata Sandi
                        </button>
                    </form>
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
