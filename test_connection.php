<?php
// Include the database configuration
require_once 'config.php';

$is_success = isset($pdo) && !isset($connection_error);
$error_msg = isset($connection_error) ? $connection_error : '';
$db_created = true;

// Detect if the error is specifically because the database doesn't exist
if (!$is_success && strpos(strtolower($error_msg), "unknown database") !== false) {
    $db_created = false;
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

        <?php if ($is_success): ?>
            <div class="status-container">
                <div class="status-icon">✓</div>
                <div class="status-title">Koneksi Berhasil!</div>
                <div class="status-desc">PHP berhasil terhubung ke database <strong><?php echo DB_NAME; ?></strong> menggunakan driver PDO.</div>
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

        <?php if (!$is_success): ?>
            <?php if (!$db_created): ?>
                <div class="guide-box">
                    <div class="guide-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Database "peliharaan" belum dibuat
                    </div>
                    <ol class="guide-steps">
                        <li>Buka phpMyAdmin di browser Anda: <a href="http://localhost/phpmyadmin" target="_blank" style="color: #60a5fa; text-decoration: underline;">localhost/phpmyadmin</a></li>
                        <li>Klik menu <strong>SQL</strong> atau pilih tab <strong>Databases</strong>.</li>
                        <li>Jalankan perintah SQL berikut untuk membuat database:
                            <div class="code-block">
                                <span id="sql-cmd">CREATE DATABASE peliharaan;</span>
                                <button class="btn-copy" onclick="copySql()">Salin</button>
                            </div>
                        </li>
                        <li>Setelah database berhasil dibuat, refresh halaman ini untuk mencoba kembali.</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="guide-box" style="background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2);">
                    <div class="guide-title" style="color: #fca5a5;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        Langkah Troubleshooting
                    </div>
                    <ol class="guide-steps">
                        <li>Pastikan aplikasi <strong>XAMPP</strong> Anda sudah aktif, khususnya modul <strong>Apache</strong> dan <strong>MySQL</strong>.</li>
                        <li>Periksa kembali username dan password MySQL Anda di dalam berkas <a href="config.php" style="color: #60a5fa; text-decoration: underline;">config.php</a>.</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem;">
                <button onclick="window.location.reload();" class="btn-action">Coba Hubungkan Kembali</button>
            </div>
        <?php else: ?>
            <div class="guide-box" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2);">
                <div class="guide-title" style="color: #a7f3d0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Cara Menggunakan
                </div>
                <div style="font-size: 0.875rem; line-height: 1.6; color: #d1d5db;">
                    Anda dapat menggunakan variable koneksi <code style="font-family: monospace; color: #38bdf8; background: rgba(0,0,0,0.2); padding: 0.1rem 0.3rem; border-radius: 4px;">$pdo</code> di file PHP lainnya dengan melakukan import:
                    <div class="code-block" style="color: #a7f3d0;">
                        <span>require_once 'config.php';</span>
                    </div>
                </div>
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
