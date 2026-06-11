<?php
date_default_timezone_set('Asia/Jakarta'); // Set timezone ke WIB (GMT+7)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sync online store branch with admin/cashier branch session if logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['id_cabang'])) {
    $_SESSION['customer_cabang_id'] = (int)$_SESSION['id_cabang'];
    $_SESSION['customer_cabang_nama'] = $_SESSION['nama_cabang'] ?? 'Cabang';
}

$koneksi = mysqli_connect("localhost", "root", "", "merahputih");
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }
?>