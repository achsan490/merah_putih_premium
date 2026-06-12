<?php
date_default_timezone_set('Asia/Jakarta'); // Set timezone ke WIB (GMT+7)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sync online store branch with admin/cashier branch session if logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['id_cabang'])) {
    $_SESSION['customer_cabang_id'] = (int)$_SESSION['id_cabang'];
    $_SESSION['customer_cabang_nama'] = $_SESSION['nama_cabang'] ?? 'Cabang';
}

// Konfigurasi Database InfinityFree
$db_host = "sql107.infinityfree.com";
$db_user = "if0_42167050";
$db_pass = "d83PkcwBqNUpw6";
$db_name = "if0_42167050_merahputih";

// Pastikan PHP menampilkan error jika ada masalah koneksi (untuk mempermudah debug)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
} catch (Throwable $e) {
    die("Koneksi database gagal! Silakan periksa kembali Hostname atau Nama Database di config.php. <br><br>Pesan Error: " . $e->getMessage());
}
?>