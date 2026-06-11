<?php
include '../config.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['id'])) {
    $id_produk = $_POST['id'];

    // Tambah ke session keranjang
    if (isset($_SESSION['keranjang'][$id_produk])) {
        $_SESSION['keranjang'][$id_produk] += 1;
    } else {
        $_SESSION['keranjang'][$id_produk] = 1;
    }

    // Balikkan jumlah total item unik untuk diupdate ke navbar
    echo count($_SESSION['keranjang']);
}
?>