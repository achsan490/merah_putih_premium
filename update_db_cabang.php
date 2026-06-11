<?php
// update_db_cabang.php - Run in browser: http://localhost/merahputih/update_db_cabang.php
require 'config.php';

$logs = [];
$success = true;

// 1. Create cabang table
$sql_cabang = "CREATE TABLE IF NOT EXISTS cabang (
    id_cabang INT AUTO_INCREMENT PRIMARY KEY,
    nama_cabang VARCHAR(100) NOT NULL,
    alamat TEXT NULL,
    no_telp VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($koneksi, $sql_cabang)) {
    $logs[] = ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'message' => "Tabel 'cabang' berhasil diverifikasi/dibuat."];
} else {
    $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal membuat tabel cabang: " . mysqli_error($koneksi)];
    $success = false;
}

// Seed default branches
$check_cabang = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cabang");
$cabang_count = mysqli_fetch_assoc($check_cabang);
if($cabang_count['total'] == 0) {
    if(mysqli_query($koneksi, "INSERT INTO cabang (id_cabang, nama_cabang, alamat, no_telp) VALUES 
        (1, 'Toko Utama (Pusat / Online)', 'Jl. Sudirman No. 1, Jakarta', '021-5550123'),
        (2, 'Cabang Bandung', 'Jl. Asia Afrika No. 45, Bandung', '022-7770456'),
        (3, 'Cabang Surabaya', 'Jl. Tunjungan No. 88, Surabaya', '031-8880789')
    ")) {
        $logs[] = ['type' => 'primary', 'icon' => 'bi-info-circle-fill', 'message' => "Data cabang default (Pusat, Bandung, Surabaya) berhasil ditambahkan."];
    } else {
        $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal menambahkan data cabang default: " . mysqli_error($koneksi)];
        $success = false;
    }
}

// 2. Create stok_cabang table
$sql_stok_cabang = "CREATE TABLE IF NOT EXISTS stok_cabang (
    id_produk INT NOT NULL,
    id_cabang INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_produk, id_cabang),
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang) ON DELETE CASCADE
)";
if(mysqli_query($koneksi, $sql_stok_cabang)) {
    $logs[] = ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'message' => "Tabel 'stok_cabang' berhasil diverifikasi/dibuat."];
} else {
    $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal membuat tabel stok_cabang: " . mysqli_error($koneksi)];
    $success = false;
}

// 3. Add columns to admin_users table
$check_role = mysqli_query($koneksi, "SHOW COLUMNS FROM admin_users LIKE 'role'");
if(mysqli_num_rows($check_role) == 0) {
    if(mysqli_query($koneksi, "ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'admin'")) {
        $logs[] = ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'message' => "Kolom 'role' berhasil ditambahkan ke tabel admin_users."];
    } else {
        $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal menambahkan kolom 'role': " . mysqli_error($koneksi)];
        $success = false;
    }
} else {
    $logs[] = ['type' => 'secondary', 'icon' => 'bi-info-circle', 'message' => "Kolom 'role' sudah ada di tabel admin_users."];
}

$check_user_cabang = mysqli_query($koneksi, "SHOW COLUMNS FROM admin_users LIKE 'id_cabang'");
if(mysqli_num_rows($check_user_cabang) == 0) {
    if(mysqli_query($koneksi, "ALTER TABLE admin_users ADD COLUMN id_cabang INT DEFAULT 1")) {
        $logs[] = ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'message' => "Kolom 'id_cabang' berhasil ditambahkan ke tabel admin_users."];
    } else {
        $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal menambahkan kolom 'id_cabang': " . mysqli_error($koneksi)];
        $success = false;
    }
} else {
    $logs[] = ['type' => 'secondary', 'icon' => 'bi-info-circle', 'message' => "Kolom 'id_cabang' sudah ada di tabel admin_users."];
}

// Update existing admin account to be Admin role at Pusat
mysqli_query($koneksi, "UPDATE admin_users SET role = 'admin', id_cabang = 1 WHERE username = 'admin'");

// Seed cashier accounts
$check_kasir_bdg = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_bandung'");
if(mysqli_num_rows($check_kasir_bdg) == 0) {
    $pass_bdg = password_hash('kasir123', PASSWORD_DEFAULT);
    if(mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_bandung', '$pass_bdg', 'kasir', 2)")) {
        $logs[] = ['type' => 'primary', 'icon' => 'bi-person-badge-fill', 'message' => "Akun demo Kasir Bandung berhasil dibuat (User: kasir_bandung, Pass: kasir123)."];
    }
}

$check_kasir_sby = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'kasir_surabaya'");
if(mysqli_num_rows($check_kasir_sby) == 0) {
    $pass_sby = password_hash('kasir123', PASSWORD_DEFAULT);
    if(mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('kasir_surabaya', '$pass_sby', 'kasir', 3)")) {
        $logs[] = ['type' => 'primary', 'icon' => 'bi-person-badge-fill', 'message' => "Akun demo Kasir Surabaya berhasil dibuat (User: kasir_surabaya, Pass: kasir123)."];
    }
}

// 4. Add id_cabang to pesanan table
$check_order_cabang = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'id_cabang'");
if(mysqli_num_rows($check_order_cabang) == 0) {
    if(mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN id_cabang INT DEFAULT 1")) {
        $logs[] = ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'message' => "Kolom 'id_cabang' berhasil ditambahkan ke tabel pesanan."];
    } else {
        $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal menambahkan kolom 'id_cabang' ke pesanan: " . mysqli_error($koneksi)];
        $success = false;
    }
} else {
    $logs[] = ['type' => 'secondary', 'icon' => 'bi-info-circle', 'message' => "Kolom 'id_cabang' sudah ada di tabel pesanan."];
}

// 5. Seed stock records for existing products
$res_prods = mysqli_query($koneksi, "SELECT id, stok FROM produk");
$seeded_stocks = 0;
while($p = mysqli_fetch_assoc($res_prods)) {
    $pid = $p['id'];
    $main_stok = $p['stok'] !== null ? (int)$p['stok'] : 10;
    
    // Seed Pusat (Cabang 1) with existing stock
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 1, '$main_stok')");
    
    // Seed Bandung (Cabang 2) with 0 stock
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 2, 0)");
    
    // Seed Surabaya (Cabang 3) with 0 stock
    mysqli_query($koneksi, "INSERT IGNORE INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$pid', 3, 0)");
    
    $seeded_stocks++;
}
// Force update all other branch stocks (excluding Pusat) to 0
if(mysqli_query($koneksi, "UPDATE stok_cabang SET stok = 0 WHERE id_cabang > 1")) {
    $logs[] = ['type' => 'success', 'icon' => 'bi-arrow-repeat', 'message' => "Stok awal cabang Bandung & Surabaya dikosongkan (0) untuk $seeded_stocks produk."];
} else {
    $logs[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "Gagal mereset/mengosongkan stok cabang: " . mysqli_error($koneksi)];
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembaruan Database - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #8b0000 0%, #e63946 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .update-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .card-header-premium {
            background: #8b0000;
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        .log-item {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }
        .log-success { background: #e8f5e9; color: #2e7d32; border-left-color: #2e7d32; }
        .log-danger { background: #ffebee; color: #c62828; border-left-color: #c62828; }
        .log-primary { background: #e3f2fd; color: #1565c0; border-left-color: #1565c0; }
        .log-secondary { background: #f5f5f5; color: #616161; border-left-color: #9e9e9e; }
        
        .btn-action {
            background: #8b0000;
            color: white;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            border: none;
            transition: 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-action:hover {
            background: #a40000;
            color: white;
            box-shadow: 0 8px 20px rgba(139,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="update-card">
        <div class="card-header-premium">
            <i class="bi bi-database-fill-gear fs-1 text-warning mb-2 d-inline-block"></i>
            <h4 class="fw-800 m-0 text-uppercase tracking-wider">Database Migrator</h4>
            <p class="small opacity-75 m-0 mt-1">Multi-Branch Stock Synchronization</p>
        </div>
        
        <div class="p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-task me-2 text-danger"></i>Proses Migrasi:</h6>
            
            <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                <?php foreach($logs as $log): ?>
                    <div class="log-item log-<?php echo $log['type']; ?>">
                        <i class="bi <?php echo $log['icon']; ?> me-3 fs-5"></i>
                        <div><?php echo $log['message']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 pt-3 border-top">
                <?php if($success): ?>
                    <div class="alert alert-success py-3 px-4 rounded-4 mb-4 text-center">
                        <i class="bi bi-shield-fill-check fs-4 me-2 align-middle"></i>
                        <span class="fw-bold align-middle">Database berhasil diperbarui dengan sukses!</span>
                    </div>
                    <a href="admin/login.php" class="btn-action shadow">MASUK KE DASHBOARD ADMIN <i class="bi bi-arrow-right ms-1"></i></a>
                <?php else: ?>
                    <div class="alert alert-danger py-3 px-4 rounded-4 mb-4 text-center">
                        <i class="bi bi-shield-fill-x fs-4 me-2 align-middle"></i>
                        <span class="fw-bold align-middle">Terjadi kesalahan saat memproses pembaruan.</span>
                    </div>
                    <a href="update_db_cabang.php" class="btn-action shadow">COBA LAGI <i class="bi bi-arrow-clockwise ms-1"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
