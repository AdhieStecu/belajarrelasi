<?php
// Start session
session_start();

// Import database configuration
require_once 'config.php';

// Redirect to test_connection.php if connection fails
if (!isset($pdo)) {
    header("Location: test_connection.php");
    exit;
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Server-side validation
    if ($nama_lengkap === '' || $username === '' || $password === '' || $confirm_password === '') {
        $error_msg = "Semua bidang formulir wajib diisi!";
    } elseif (strlen($username) < 3) {
        $error_msg = "Username minimal harus 3 karakter!";
    } elseif (preg_match('/\s/', $username)) {
        $error_msg = "Username tidak boleh mengandung spasi!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password minimal harus 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Konfirmasi password tidak cocok!";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_msg = "Username '$username' sudah terdaftar! Gunakan username lain.";
            } else {
                // Hash password securely
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user to database
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
                $insert_stmt->execute([$username, $hashed_password, $nama_lengkap]);
                
                $_SESSION['success'] = "Registrasi berhasil! Silakan masuk dengan akun baru Anda.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Gagal mendaftarkan akun: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Peliharaan</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .register-card {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .register-header h2 {
            font-size: 1.65rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .logo-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            display: inline-block;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="register-card card">
    <div class="register-header">
        <div class="logo-icon">🐾</div>
        <h2>Buat Akun Baru</h2>
        <p>Daftarkan akun untuk dapat mengelola data peliharaan</p>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span style="font-size: 0.85rem;"><?= htmlspecialchars($error_msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="register.php">
        <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama Anda" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Pilih username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Password (Minimal 6 karakter)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Daftar Akun</button>
    </form>

    <div class="auth-footer">
        Sudah punya akun? <a href="login.php">Masuk disini</a>
    </div>
</div>

</body>
</html>
