<?php
date_default_timezone_set('Asia/Jakarta'); // Set timezone ke WIB (GMT+7)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sync online store branch with admin/cashier branch session if logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['id_cabang'])) {
    $_SESSION['customer_cabang_id'] = (int)$_SESSION['id_cabang'];
    $_SESSION['customer_cabang_nama'] = $_SESSION['nama_cabang'] ?? 'Cabang';
}

// Deteksi Otomatis Environment (Lokal vs Production)
$is_local = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false || strpos($host, '.test') !== false) {
        $is_local = true;
    }
}

if ($is_local) {
    // Database lokal Laragon
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "merahputih";
} else {
    // Konfigurasi Database InfinityFree
    $db_host = "sql107.infinityfree.com";
    $db_user = "if0_42167050";
    $db_pass = "d83PkcwBqNUpw6";
    $db_name = "if0_42167050_merahputih";
}

// Pastikan PHP menampilkan error jika ada masalah koneksi (untuk mempermudah debug)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
} catch (Throwable $e) {
    // Jika koneksi online gagal saat dicoba secara lokal, fallback ke lokal
    if (!$is_local) {
        try {
            $koneksi = mysqli_connect("localhost", "root", "", "merahputih");
        } catch (Throwable $ex) {
            die("Koneksi database gagal! Silakan periksa kembali Hostname atau Nama Database di config.php. <br><br>Pesan Error: " . $e->getMessage() . "<br><br>Pesan Error Local Fallback: " . $ex->getMessage());
        }
    } else {
        die("Koneksi database lokal gagal! Pastikan MySQL Laragon Anda aktif. <br><br>Pesan Error: " . $e->getMessage());
    }
}

// Migrasi Database Otomatis untuk Tabel Varian Kemasan & Satuan
try {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS varian_satuan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_satuan VARCHAR(50) NOT NULL UNIQUE
    )");
    
    // Seed default units if empty
    $check_satuan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM varian_satuan");
    $satuan_count = mysqli_fetch_assoc($check_satuan);
    if ($satuan_count && $satuan_count['total'] == 0) {
        $defaults = ['Pcs', 'Slop', 'Renceng', 'Pack', 'Dus', 'Karton', 'Box', 'Sachet', 'Bal', 'Lusin', 'Gelas'];
        foreach ($defaults as $d) {
            mysqli_query($koneksi, "INSERT IGNORE INTO varian_satuan (nama_satuan) VALUES ('$d')");
        }
    }

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS produk_kemasan (
        id_kemasan INT AUTO_INCREMENT PRIMARY KEY,
        id_produk INT NOT NULL,
        nama_satuan VARCHAR(50) NOT NULL,
        faktor_kali INT NOT NULL DEFAULT 1,
        harga INT NOT NULL,
        barcode VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
    )");

    // Cek apakah ada produk lama yang belum punya data varian di produk_kemasan
    $existing_products = mysqli_query($koneksi, "SELECT id, harga, barcode FROM produk");
    if ($existing_products) {
        while ($prod = mysqli_fetch_assoc($existing_products)) {
            $pid = $prod['id'];
            $p_harga = (int)$prod['harga'];
            $p_barcode = !empty($prod['barcode']) ? "'" . mysqli_real_escape_string($koneksi, $prod['barcode']) . "'" : "NULL";
            
            $check_var = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk_kemasan WHERE id_produk = $pid");
            $var_count = mysqli_fetch_assoc($check_var);
            if ($var_count && $var_count['total'] == 0) {
                mysqli_query($koneksi, "INSERT INTO produk_kemasan (id_produk, nama_satuan, faktor_kali, harga, barcode) 
                                        VALUES ($pid, 'Pcs', 1, $p_harga, $p_barcode)");
            }
        }
    }

    // Auto-add id_kemasan column to detail_pesanan table if missing
    $check_dp_kemasan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_pesanan LIKE 'id_kemasan'");
    if(mysqli_num_rows($check_dp_kemasan) == 0) {
        mysqli_query($koneksi, "ALTER TABLE detail_pesanan ADD COLUMN id_kemasan INT DEFAULT NULL");
    }
} catch (Throwable $e) {
    // Abaikan jika database belum terbentuk (saat install awal)
}
?>