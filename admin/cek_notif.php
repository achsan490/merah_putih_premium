<?php
include '../config.php';
$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
$cek = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending' AND id_cabang = '$id_cabang'");
$data = mysqli_fetch_assoc($cek);
echo json_encode(['jumlah' => $data['total']]);
?>