<?php
// Include the database configuration
require_once 'config.php';

// Handle automatic database initialization/seeding
$setup_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup_db') {
    try {
        // Connect to MySQL server without specifying database name first
        $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $temp_pdo = new PDO($dsn_no_db, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database if not exists
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        
        // Connect to the newly created database
        $temp_pdo->exec("USE `" . DB_NAME . "`");
        
        // Read database.sql
        $sql_file = __DIR__ . '/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Execute the entire database.sql schema and seed in one go
            $temp_pdo->exec($sql);
            $_SESSION['success'] = "Database dan tabel berhasil diinisialisasi secara otomatis!";
        } else {
            $_SESSION['success'] = "Database berhasil dibuat, tetapi berkas database.sql tidak ditemukan.";
        }
        
        header("Location: test_connection.php");
        exit;
    } catch (PDOException $e) {
        $setup_error = "Gagal memproses inisialisasi database: " . $e->getMessage();
    }
}

// Re-check connection status (it might have succeeded after setup)
// Reload config.php connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    unset($connection_error);
} catch (PDOException $e) {
    $connection_error = $e->getMessage();
}

$is_success = isset($pdo) && !isset($connection_error);
$error_msg = isset($connection_error) ? $connection_error : '';
$db_created = true;

// Detect if the error is specifically because the database doesn't exist
if (!$is_success && strpos(strtolower($error_msg), "unknown database") !== false) {
    $db_created = false;
}

$tables_exist = false;
if ($is_success) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'hewan'");
        $tables_exist = ($stmt->fetch() !== false);
    } catch (PDOException $e) {
        $tables_exist = false;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Tester - Peliharaan</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(20, 28, 47, 0.6);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            
            --success-color: #10b981;
            --success-glow: rgba(16, 185, 129, 0.15);
            --error-color: #ef4444;
            --error-glow: rgba(239, 68, 68, 0.15);
            --warning-color: #f59e0b;
            --warning-glow: rgba(245, 158, 11, 0.15);
            
            --accent-blue: #3b82f6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.05) 0%, transparent 40%);
        }

        .container {
            width: 100%;
            max-width: 600px;
            perspective: 1000px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Top decorative line based on status */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: <?php echo $is_success ? 'var(--success-color)' : 'var(--error-color)'; ?>;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .status-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: <?php echo $is_success ? 'var(--success-glow)' : 'var(--error-glow)'; ?>;
            border: 1px solid <?php echo $is_success ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>;
        }

        .status-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
            background-color: <?php echo $is_success ? 'var(--success-color)' : 'var(--error-color)'; ?>;
            color: white;
            box-shadow: 0 0 20px <?php echo $is_success ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)'; ?>;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .status-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: <?php echo $is_success ? '#a7f3d0' : '#fecaca'; ?>;
        }

        .status-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
            max-width: 320px;
            line-height: 1.5;
        }

        .details-list {
            list-style: none;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
        }

        .details-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .details-item:last-child {
            border-bottom: none;
        }

        .details-label {
            color: var(--text-secondary);
        }

        .details-value {
            font-family: monospace;
            color: var(--text-primary);
            font-weight: 500;
        }

        .guide-box {
            background: rgba(245, 158, 11, 0.05);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .guide-title {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 600;
            color: #fde68a;
            margin-bottom: 0.75rem;
        }

        .guide-title svg {
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        .guide-steps {
            list-style-type: decimal;
            margin-left: 1.25rem;
            font-size: 0.875rem;
            line-height: 1.6;
            color: #d1d5db;
        }

        .guide-steps li {
            margin-bottom: 0.5rem;
        }

        .code-block {
            background: #060913;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            margin: 0.5rem 0;
            color: #38bdf8;
            overflow-x: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-copy {
            background: rgba(255, 255, 255, 0.08);
            border: none;
            color: var(--text-primary);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-copy:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-action {
            display: block;
            text-align: center;
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            padding: 0.875rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-action:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <h1>Status Koneksi Database</h1>
            <p>Konfigurasi Database Peliharaan</p>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="status-container" style="background: var(--success-glow); border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 1.5rem; padding: 1rem; border-radius: 12px;">
                <div style="color: #a7f3d0; font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($_SESSION['success']); ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($setup_error !== ''): ?>
            <div class="status-container" style="background: var(--error-glow); border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 1.5rem; padding: 1rem; border-radius: 12px;">
                <div style="color: #fecaca; font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($setup_error); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($is_success && $tables_exist): ?>
            <div class="status-container">
                <div class="status-icon">✓</div>
                <div class="status-title">Koneksi Berhasil!</div>
                <div class="status-desc">PHP berhasil terhubung ke database <strong><?php echo DB_NAME; ?></strong> dan semua tabel siap digunakan.</div>
            </div>
        <?php elseif ($is_success && !$tables_exist): ?>
            <div class="status-container" style="background: var(--warning-glow); border: 1px solid rgba(245, 158, 11, 0.2);">
                <div class="status-icon" style="background-color: var(--warning-color); box-shadow: 0 0 20px rgba(245, 158, 11, 0.4); color: white;">!</div>
                <div class="status-title" style="color: #fde68a;">Tabel Belum Dibuat</div>
                <div class="status-desc">Terhubung ke database <strong><?php echo DB_NAME; ?></strong>, tetapi tabel-tabel relasi belum dibuat.</div>
            </div>
        <?php else: ?>
            <div class="status-container">
                <div class="status-icon">✗</div>
                <div class="status-title">Koneksi Gagal</div>
                <div class="status-desc">Tidak dapat terhubung ke database. Cek detail error di bawah.</div>
            </div>
        <?php endif; ?>

        <ul class="details-list">
            <li class="details-item">
                <span class="details-label">Database Host</span>
                <span class="details-value"><?php echo DB_HOST; ?></span>
            </li>
            <li class="details-item">
                <span class="details-label">Database Name</span>
                <span class="details-value"><?php echo DB_NAME; ?></span>
            </li>
            <li class="details-item">
                <span class="details-label">Database User</span>
                <span class="details-value"><?php echo DB_USER; ?></span>
            </li>
            <?php if (!$is_success): ?>
                <li class="details-item" style="flex-direction: column; gap: 0.5rem; align-items: flex-start;">
                    <span class="details-label">Error Message:</span>
                    <span class="details-value" style="color: var(--error-color); word-break: break-all; text-align: left; font-size: 0.8rem; width: 100%;">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </span>
                </li>
            <?php endif; ?>
        </ul>

        <?php if ($is_success && $tables_exist): ?>
            <div class="guide-box" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2);">
                <div class="guide-title" style="color: #a7f3d0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Aplikasi Siap Digunakan!
                </div>
                <div style="font-size: 0.875rem; line-height: 1.6; color: #d1d5db; margin-bottom: 1rem;">
                    Semua sistem terhubung dengan sempurna. Anda dapat masuk menggunakan akun administrator default:
                    <div style="margin-top: 0.5rem; background: rgba(0,0,0,0.25); padding: 0.5rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem;">
                        Username: <span style="color: #a5b4fc;">admin</span><br>
                        Password: <span style="color: #a5b4fc;">admin123</span>
                    </div>
                </div>
                <a href="index.php" class="btn-action" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">Ke Halaman Utama</a>
            </div>
        <?php else: ?>
            <!-- Setup & Guide Box -->
            <div class="guide-box" style="background: rgba(16, 185, 129, 0.03); border-color: rgba(16, 185, 129, 0.15);">
                <div class="guide-title" style="color: #a7f3d0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    Setup Database Satu-Klik (Direkomendasikan)
                </div>
                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.5;">
                    Sistem dapat membuat database dan tabel-tabel peliharaan secara otomatis dengan mengeksekusi migrasi SQL yang tersedia.
                </p>
                <form method="POST" action="test_connection.php">
                    <input type="hidden" name="action" value="setup_db">
                    <button type="submit" class="btn-action" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        Inisialisasi Database Otomatis
                    </button>
                </form>
            </div>

            <!-- Manual Guide Fallback -->
            <div class="guide-box">
                <div class="guide-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Cara Inisialisasi Manual
                </div>
                <ol class="guide-steps">
                    <li>Buka phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank" style="color: #60a5fa; text-decoration: underline;">localhost/phpmyadmin</a></li>
                    <li>Jalankan perintah SQL untuk membuat database:
                        <div class="code-block">
                            <span id="sql-cmd">CREATE DATABASE peliharaan;</span>
                            <button class="btn-copy" onclick="copySql()">Salin</button>
                        </div>
                    </li>
                    <li>Impor skrip database dari berkas <code>database.sql</code> ke database yang baru dibuat.</li>
                </ol>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button onclick="window.location.reload();" class="btn-action" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: none;">
                    Coba Hubungkan Kembali
                </button>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    function copySql() {
        const text = document.getElementById('sql-cmd').innerText;
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.btn-copy');
            btn.innerText = 'Tersalin!';
            setTimeout(() => { btn.innerText = 'Salin'; }, 2000);
        }).catch(err => {
            console.error('Gagal menyalin teks: ', err);
        });
    }
</script>
</body>
</html>
