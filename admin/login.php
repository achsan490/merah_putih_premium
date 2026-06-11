<?php
session_start();
include '../config.php';

// Auto-run Database Migrations on page load to prevent errors
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

$check_admin = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'admin'");
if (mysqli_num_rows($check_admin) == 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('admin', '$pass', 'admin', 1)");
} else {
    mysqli_query($koneksi, "UPDATE admin_users SET role = 'admin', id_cabang = 1 WHERE username = 'admin'");
}

$check_kasir_bdg = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_bandung'");
if(mysqli_num_rows($check_kasir_bdg) == 0) {
    $pass_bdg = password_hash('kasir123', PASSWORD_DEFAULT);
    mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_bandung', '$pass_bdg', 'kasir', 2)");
}
$check_kasir_sby = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_surabaya'");
if(mysqli_num_rows($check_kasir_sby) == 0) {
    $pass_sby = password_hash('kasir123', PASSWORD_DEFAULT);
    mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_surabaya', '$pass_sby', 'kasir', 3)");
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

// Seed stock records for existing products
$res_prods = mysqli_query($koneksi, "SELECT id, stok FROM produk");
while($p = mysqli_fetch_assoc($res_prods)) {
    $pid = $p['id'];
    $main_stok = $p['stok'] !== null ? (int)$p['stok'] : 10;
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 1, '$main_stok')");
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 2, 0)");
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 3, 0)");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #8b0000 0%, #e63946 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #8b0000;
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
        }
        .btn-login {
            background: #8b0000;
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            border: none;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #a40000;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-800 text-danger">ADMIN LOGIN</h3>
            <p class="text-muted small">Silakan masuk untuk mengelola toko.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small text-center rounded-3 mb-3"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-login w-100 shadow">MASUK SEKARANG</button>
        </form>
        <div class="text-center mt-3">
            <a href="../user/index.php" class="text-decoration-none small text-muted">Kembali ke Toko</a>
        </div>
    </div>
</body>
</html>
