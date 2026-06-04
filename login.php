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

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if ($username === '' || $password === '') {
        $error_msg = "Username dan password wajib diisi!";
    } else {
        try {
            // Find user in database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Store user session info
                $_SESSION['user'] = [
                    'id_user'      => $user['id_user'],
                    'username'     => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap']
                ];
                
                $_SESSION['success'] = "Selamat datang kembali, " . htmlspecialchars($user['nama_lengkap']) . "!";
                header("Location: dashboard.php");
                exit;
            } else {
                $error_msg = "Username atau password salah!";
            }
        } catch (PDOException $e) {
            $error_msg = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peliharaan</title>
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
            height: 100vh;
            overflow: hidden;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
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

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-size: 1.65rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .logo-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: bounce 2s infinite alternate;
        }

        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-8px); }
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

<div class="login-card card">
    <div class="login-header">
        <div class="logo-icon">🐾</div>
        <h2>Masuk Sistem</h2>
        <p>Silakan masuk menggunakan akun Administrator Anda</p>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span style="font-size: 0.85rem;"><?= htmlspecialchars($error_msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span style="font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['success']); ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="admin" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autocomplete="username">
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">Masuk Sekarang</button>
    </form>

    <div class="auth-footer">
        Belum punya akun? <a href="register.php">Daftar disini</a>
    </div>
</div>

</body>
</html>
