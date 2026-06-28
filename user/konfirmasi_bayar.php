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
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - MerahPutih Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background-color: #F8F9FD; color: #1A1A2E; }

        .card-custom { 
            border-radius: 24px; 
            border: none; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            background: white;
            border: 1px solid #F0F0F8;
            padding: 36px;
        }

        .alert-custom-info { background: #F0F4FF; border: 1px solid #E2EAF8; border-radius: 16px; padding: 16px; color: #1E40AF; font-size: 0.85rem; }
        .alert-custom-warning { background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 16px; padding: 16px; color: #92400E; font-size: 0.85rem; }

        .form-control, .form-select { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.88rem; transition: border-color 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #4A4A6A; margin-bottom: 6px; }

        .btn-mp-primary { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; font-weight: 700; border-radius: 12px; padding: 14px 28px; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; transition: all 0.2s; }
        .btn-mp-primary:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-mp-danger { background: linear-gradient(135deg, #922B21 0%, #E74C3C 100%); color: white; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card card-custom">
                    <div class="text-center mb-4 pb-2">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF0EE; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                            <i class="bi bi-cloud-upload fs-3 text-danger"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Konfirmasi Pembayaran</h3>
                        <p class="text-secondary small">Kirimkan bukti transaksi transfer Anda kepada tim kami</p>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="alert-custom-info">
                                <span class="d-block text-secondary small fw-bold mb-1">DETAIL PESANAN</span>
                                <div>Nomor Nota: <strong>#<?php echo $pesanan['id_pesanan']; ?></strong></div>
                                <div>Total Bayar: <strong class="text-danger">Rp <?php echo number_format($pesanan['total_bayar'], 0, ',', '.'); ?></strong></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="alert-custom-warning">
                                <span class="d-block text-secondary small fw-bold mb-1">REKENING TUJUAN</span>
                                <div>Bank BCA: <strong>1234567890</strong></div>
                                <div>Atas Nama: <strong>MerahPutih Store</strong></div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Nama Pengirim</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama sesuai rekening bank Anda" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bank Pengirim</label>
                            <select name="bank" class="form-select" required>
                                <option value="">Pilih Bank Pengirim</option>
                                <option value="BCA">BCA</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BNI">BNI</option>
                                <option value="BRI">BRI</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Transfer (Rp)</label>
                            <input type="number" name="jumlah" class="form-control" value="<?php echo $pesanan['total_bayar']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Transfer</label>
                            <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Upload Bukti Transfer</label>
                            <input type="file" name="bukti" class="form-control" accept="image/*" required>
                            <div class="form-text small text-secondary mt-1"><i class="bi bi-info-circle me-1"></i>Format file: JPG, JPEG, PNG (Maksimal 2MB)</div>
                        </div>
                        <button type="submit" name="upload" class="btn-mp-primary btn-mp-danger w-100 py-3 shadow">
                            <i class="bi bi-send-check"></i> Kirim Bukti Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
