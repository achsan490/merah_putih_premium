<?php
include '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['id'])) {
    $id_produk = (int)$_POST['id'];

    // Cari varian default 'Pcs' (atau varian dengan ID terkecil untuk produk ini)
    $q_var = mysqli_query($koneksi, "SELECT id_kemasan FROM produk_kemasan WHERE id_produk = $id_produk ORDER BY id_kemasan ASC LIMIT 1");
    $var_data = mysqli_fetch_assoc($q_var);

    if ($var_data) {
        $id_kemasan = $var_data['id_kemasan'];

        // Tambah ke session keranjang dengan kunci id_kemasan
        if (isset($_SESSION['keranjang'][$id_kemasan])) {
            $_SESSION['keranjang'][$id_kemasan] += 1;
        } else {
            $_SESSION['keranjang'][$id_kemasan] = 1;
        }

        // Kembalikan jumlah total item unik di keranjang
        echo count($_SESSION['keranjang']);
    } else {
        echo 0;
    }
}
?>