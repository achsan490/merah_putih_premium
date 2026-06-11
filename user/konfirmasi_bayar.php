<?php 
include '../config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesanan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = $id_pesanan"));

if(!$pesanan) {
    header("location: index.php");
    exit;
}

if(isset($_POST['upload'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $bank = mysqli_real_escape_string($koneksi, $_POST['bank']);
    $jumlah = (int)$_POST['jumlah'];
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    
    $foto = $_FILES['bukti']['name'];
    $tmp = $_FILES['bukti']['tmp_name'];
    $folder = "../assets/bukti_bayar/";
    
    if(!is_dir($folder)) mkdir($folder, 0777, true);
    
    $new_name = time() . "_" . $foto;
    move_uploaded_file($tmp, $folder . $new_name);
    
    mysqli_query($koneksi, "INSERT INTO payment_confirmations (id_pesanan, nama_pengirim, bank_pengirim, jumlah_transfer, tanggal_transfer, bukti_foto) 
                            VALUES ($id_pesanan, '$nama', '$bank', $jumlah, '$tanggal', '$new_name')");
    
    echo "<script>alert('Bukti pembayaran berhasil dikirim! Mohon tunggu konfirmasi admin.'); window.location='cek_pesanan.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-custom p-4 bg-white">
                    <h4 class="fw-bold mb-4 text-center text-danger">Upload Bukti Pembayaran</h4>
                    
                    <div class="alert alert-info">
                        <h6 class="fw-bold">Informasi Pesanan</h6>
                        <p class="mb-1">Nomor Pesanan: <strong>#<?php echo $pesanan['id_pesanan']; ?></strong></p>
                        <p class="mb-0">Total: <strong class="text-danger">Rp <?php echo number_format($pesanan['total_bayar']); ?></strong></p>
                    </div>

                    <div class="alert alert-warning">
                        <h6 class="fw-bold"><i class="bi bi-bank me-2"></i>Transfer ke Rekening:</h6>
                        <p class="mb-1">Bank BCA: <strong>1234567890</strong></p>
                        <p class="mb-0">a.n. <strong>MerahPutih Store</strong></p>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Pengirim</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bank Pengirim</label>
                            <select name="bank" class="form-select" required>
                                <option value="">Pilih Bank</option>
                                <option value="BCA">BCA</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BNI">BNI</option>
                                <option value="BRI">BRI</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Transfer</label>
                            <input type="number" name="jumlah" class="form-control" value="<?php echo $pesanan['total_bayar']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Transfer</label>
                            <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Upload Bukti Transfer</label>
                            <input type="file" name="bukti" class="form-control" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG (Max 2MB)</small>
                        </div>
                        <button type="submit" name="upload" class="btn btn-danger w-100 rounded-pill py-3 fw-bold">
                            <i class="bi bi-upload me-2"></i> Kirim Bukti Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
