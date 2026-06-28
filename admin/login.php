<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config.php';

if (!isset($koneksi) || !$koneksi) {
    die("<h3>Koneksi database gagal!</h3><p>Pastikan file <b>config.php</b> berada di folder utama htdocs dan konfigurasi database Anda sudah benar.</p>");
}

try {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS cabang (
        id_cabang INT AUTO_INCREMENT PRIMARY KEY,
        nama_cabang VARCHAR(100) NOT NULL,
        alamat TEXT NULL,
        no_telp VARCHAR(20) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $check_cabang = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cabang");
    $cabang_count = mysqli_fetch_assoc($check_cabang);
    if($cabang_count['total'] == 0) {
        mysqli_query($koneksi, "INSERT INTO cabang (id_cabang, nama_cabang, alamat, no_telp) VALUES 
            (1, 'Toko Utama (Pusat / Online)', 'Jl. Sudirman No. 1, Jakarta', '021-5550123'),
            (2, 'Cabang Bandung', 'Jl. Asia Afrika No. 45, Bandung', '022-7770456'),
            (3, 'Cabang Surabaya', 'Jl. Tunjungan No. 88, Surabaya', '031-8880789')
        ");
    }

    $check_barcode = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'barcode'");
    if(mysqli_num_rows($check_barcode) == 0) {
        mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN barcode VARCHAR(100) DEFAULT NULL");
    }
    $check_stok_col = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'stok'");
    if(mysqli_num_rows($check_stok_col) == 0) {
        mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN stok INT DEFAULT 0");
    }
    $check_tipe_pesanan = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'tipe_pesanan'");
    if(mysqli_num_rows($check_tipe_pesanan) == 0) {
        mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN tipe_pesanan VARCHAR(20) DEFAULT 'online'");
    }
    $check_catatan_batal = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'catatan_batal'");
    if(mysqli_num_rows($check_catatan_batal) == 0) {
        mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN catatan_batal VARCHAR(255) DEFAULT NULL");
    }

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $check_role = mysqli_query($koneksi, "SHOW COLUMNS FROM admin_users LIKE 'role'");
    if(mysqli_num_rows($check_role) == 0) {
        mysqli_query($koneksi, "ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'admin'");
    }
    $check_user_cabang = mysqli_query($koneksi, "SHOW COLUMNS FROM admin_users LIKE 'id_cabang'");
    if(mysqli_num_rows($check_user_cabang) == 0) {
        mysqli_query($koneksi, "ALTER TABLE admin_users ADD COLUMN id_cabang INT DEFAULT 1");
    }

    $pass_default = password_hash('admin123', PASSWORD_DEFAULT);
    $check_admin = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'admin'");
    if (mysqli_num_rows($check_admin) == 0) {
        mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('admin', '$pass_default', 'admin', 1)");
    } else {
        mysqli_query($koneksi, "UPDATE admin_users SET role = 'admin', id_cabang = 1, password = '$pass_default' WHERE username = 'admin'");
    }

    $check_kasir_bdg = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_bandung'");
    $pass_kasir_default = password_hash('kasir123', PASSWORD_DEFAULT);
    if(mysqli_num_rows($check_kasir_bdg) == 0) {
        mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_bandung', '$pass_kasir_default', 'kasir', 2)");
    } else {
        mysqli_query($koneksi, "UPDATE admin_users SET password = '$pass_kasir_default', role = 'kasir', id_cabang = 2 WHERE username = 'kasir_bandung'");
    }
    
    $check_kasir_sby = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_surabaya'");
    if(mysqli_num_rows($check_kasir_sby) == 0) {
        mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_surabaya', '$pass_kasir_default', 'kasir', 3)");
    } else {
        mysqli_query($koneksi, "UPDATE admin_users SET password = '$pass_kasir_default', role = 'kasir', id_cabang = 3 WHERE username = 'kasir_surabaya'");
    }

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS stok_cabang (
        id_produk INT NOT NULL,
        id_cabang INT NOT NULL,
        stok INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id_produk, id_cabang)
    )");

    $check_order_cabang = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'id_cabang'");
    if(mysqli_num_rows($check_order_cabang) == 0) {
        mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN id_cabang INT DEFAULT 1");
    }

    $res_prods = mysqli_query($koneksi, "SELECT id, stok FROM produk");
    while($p = mysqli_fetch_assoc($res_prods)) {
        $pid = $p['id'];
        $main_stok = $p['stok'] !== null ? (int)$p['stok'] : 10;
        mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 1, '$main_stok')");
        mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 2, 0)");
        mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 3, 0)");
    }
} catch (Throwable $e) {
    die("Database Migration Error: " . $e->getMessage() . " di baris " . $e->getLine() . "<br><br>Silakan periksa koneksi atau izin database hosting Anda.");
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'kasir') {
        header("location: ../kasir/index.php");
    } else {
        header("location: index.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "
        SELECT au.*, c.nama_cabang 
        FROM admin_users au 
        LEFT JOIN cabang c ON au.id_cabang = c.id_cabang 
        WHERE au.username = '$username'
    ");
    $user = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['id_cabang'] = $user['id_cabang'];
        $_SESSION['nama_cabang'] = $user['nama_cabang'] ?: 'Toko Utama (Pusat)';

        if ($user['role'] === 'kasir') {
            header("location: ../kasir/index.php");
        } else {
            header("location: index.php");
        }
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MerahPutih Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #C0392B;
            --primary-dark: #922B21;
            --primary-light: #E74C3C;
            --navy: #1A1A2E;
            --accent: #FF6B35;
            --bg: #F8F4F0;
            --white: #FFFFFF;
            --text: #2D2D2D;
            --text-muted: #7A7A8C;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
            background: var(--bg);
        }

        /* LEFT PANEL - Brand */
        .brand-panel {
            flex: 1.2;
            background: linear-gradient(145deg, #1A1A2E 0%, #16213E 50%, #0F3460 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(192,57,43,0.25) 0%, transparent 70%);
            top: -100px; right: -100px;
            border-radius: 50%;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,107,53,0.15) 0%, transparent 70%);
            bottom: -50px; left: -50px;
            border-radius: 50%;
        }

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }
        .brand-logo span { color: var(--accent); }

        .brand-tagline {
            color: rgba(255,255,255,0.6);
            font-size: 0.95rem;
            font-weight: 400;
            margin-top: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }

        .brand-divider {
            width: 60px; height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
            margin: 30px 0;
            position: relative; z-index: 1;
        }

        .brand-features {
            list-style: none;
            position: relative; z-index: 1;
        }
        .brand-features li {
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .brand-features li:last-child { border-bottom: none; }
        .brand-features .feat-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        /* Floating orbs animation */
        .orb {
            position: absolute;
            border-radius: 50%;
            animation: floatOrb 8s infinite ease-in-out;
            z-index: 0;
        }
        .orb-1 { width: 80px; height: 80px; background: rgba(192,57,43,0.2); top: 20%; left: 10%; animation-delay: 0s; }
        .orb-2 { width: 50px; height: 50px; background: rgba(255,107,53,0.15); top: 60%; right: 15%; animation-delay: 2s; }
        .orb-3 { width: 120px; height: 120px; background: rgba(15,52,96,0.5); bottom: 20%; left: 20%; animation-delay: 4s; }

        @keyframes floatOrb {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* RIGHT PANEL - Form */
        .form-panel {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 70px;
            position: relative;
        }

        .form-header { margin-bottom: 40px; }
        .form-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.5px;
        }
        .form-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 6px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            border: 2px solid #E8E8F0;
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: #FAFAFA;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.08);
        }
        .form-input:focus + .input-icon { display: none; }
        .input-wrapper:focus-within .input-icon { color: var(--primary); }

        .toggle-pw {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
            padding: 4px;
            transition: color 0.3s;
        }
        .toggle-pw:hover { color: var(--primary); }

        .error-alert {
            background: #FEF0EE;
            border: 1px solid #FECDC8;
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--primary-dark);
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }
        .btn-login:hover::before { left: 100%; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(192,57,43,0.35); }
        .btn-login:active { transform: translateY(0); }

        .form-footer {
            margin-top: 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.87rem;
        }
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .form-footer a:hover { opacity: 0.75; }

        .divider-text {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #D0D0E0;
            font-size: 0.8rem;
            margin: 16px 0;
        }
        .divider-text::before, .divider-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E8E8F0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .brand-panel { display: none; }
            .form-panel { padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <!-- LEFT: Brand Panel -->
    <div class="brand-panel">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <div class="brand-logo">MERAH<span>PUTIH</span></div>
        <p class="brand-tagline">Admin Control Portal</p>
        <div class="brand-divider"></div>

        <ul class="brand-features">
            <li>
                <div class="feat-icon" style="background: rgba(192,57,43,0.2);">
                    <i class="bi bi-graph-up-arrow" style="color: #FF6B6B;"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: white;">Dashboard Real-time</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Pantau penjualan setiap saat</div>
                </div>
            </li>
            <li>
                <div class="feat-icon" style="background: rgba(255,107,53,0.2);">
                    <i class="bi bi-pc-display-horizontal" style="color: var(--accent);"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: white;">Kasir POS Offline</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Sistem kasir untuk toko fisik</div>
                </div>
            </li>
            <li>
                <div class="feat-icon" style="background: rgba(39,174,96,0.2);">
                    <i class="bi bi-shop" style="color: #2ECC71;"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: white;">Multi-Cabang</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Kelola banyak cabang sekaligus</div>
                </div>
            </li>
            <li>
                <div class="feat-icon" style="background: rgba(52,152,219,0.2);">
                    <i class="bi bi-file-earmark-bar-graph" style="color: #3498DB;"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: white;">Laporan Lengkap</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Export PDF & analitik detail</div>
                </div>
            </li>
        </ul>
    </div>

    <!-- RIGHT: Login Form Panel -->
    <div class="form-panel">
        <div class="form-header">
            <h2>Selamat Datang 👋</h2>
            <p>Masuk ke panel admin untuk mengelola toko Anda.</p>
        </div>

        <?php if($error): ?>
        <div class="error-alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label class="form-label" for="loginUsername">Username</label>
                <div class="input-wrapper">
                    <i class="bi bi-person-fill input-icon"></i>
                    <input type="text" id="loginUsername" name="username" class="form-input"
                        placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" id="loginPassword" name="password" class="form-input"
                        placeholder="Masukkan password" required>
                    <button type="button" class="toggle-pw" onclick="togglePassword()" id="togglePwBtn">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login" id="loginBtn">
                <span id="btnText"><i class="bi bi-shield-lock me-2"></i> Masuk ke Dashboard</span>
            </button>
        </form>

        <div class="divider-text">atau</div>

        <div class="form-footer">
            <a href="../user/index.php"><i class="bi bi-shop me-1"></i> Kembali ke Toko</a>
            &nbsp;·&nbsp;
            <span style="color: #B0B0C0;">MerahPutih © <?php echo date('Y'); ?></span>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pw = document.getElementById('loginPassword');
            const icon = document.getElementById('pwIcon');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pw.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Button loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-2" style="animation: spin 1s linear infinite;"></i> Memverifikasi...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
