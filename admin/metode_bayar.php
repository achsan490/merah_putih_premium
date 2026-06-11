<?php 
session_start();
include '../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("location: login.php");
    exit;
}

// Auto-create payment_methods table
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'payment_methods'");
if(mysqli_num_rows($check_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_metode VARCHAR(50) NOT NULL,
        kode VARCHAR(20) NOT NULL,
        deskripsi TEXT,
        detail_pembayaran TEXT,
        icon VARCHAR(50),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default payment methods
    mysqli_query($koneksi, "INSERT INTO payment_methods (nama_metode, kode, deskripsi, detail_pembayaran, icon) VALUES 
        ('Cash on Delivery (COD)', 'cod', 'Bayar saat barang diterima', 'Pembayaran dilakukan saat barang sampai di tangan Anda.', 'bi-cash-coin'),
        ('Transfer Bank', 'transfer', 'Transfer ke rekening bank', 'Bank BCA: 1234567890 a.n. MerahPutih Store', 'bi-bank'),
        ('QRIS', 'qris', 'Scan QR Code untuk bayar', 'https://via.placeholder.com/300x300?text=QRIS+Code', 'bi-qr-code')");
}

// Handle Update
if(isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_metode']);
    $desk = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $detail = mysqli_real_escape_string($koneksi, $_POST['detail_pembayaran']);
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    mysqli_query($koneksi, "UPDATE payment_methods SET nama_metode='$nama', deskripsi='$desk', detail_pembayaran='$detail', is_active=$active WHERE id=$id");
    echo "<script>alert('Metode pembayaran berhasil diupdate!'); window.location='metode_bayar.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Metode Pembayaran - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 260px; padding: 40px; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .payment-card { 
            border: 2px solid #e9ecef; 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .payment-card.active { border-color: #8b0000; background: #fff5f5; }
        .payment-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #8b0000; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="card card-premium p-4 bg-white">
            <h4 class="fw-bold mb-4">Pengaturan Metode Pembayaran</h4>
            <p class="text-muted mb-4">Kelola metode pembayaran yang tersedia untuk pelanggan</p>

            <?php 
            $methods = mysqli_query($koneksi, "SELECT * FROM payment_methods ORDER BY id ASC");
            while($m = mysqli_fetch_assoc($methods)): 
            ?>
            <div class="payment-card <?php echo $m['is_active'] ? 'active' : ''; ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center">
                        <i class="<?php echo $m['icon']; ?> fs-2 me-3 text-danger"></i>
                        <div>
                            <h5 class="mb-1 fw-bold"><?php echo $m['nama_metode']; ?></h5>
                            <small class="text-muted"><?php echo $m['deskripsi']; ?></small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#edit<?php echo $m['id']; ?>">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                </div>
                
                <div class="alert alert-light mb-0">
                    <strong>Detail:</strong><br>
                    <?php echo nl2br($m['detail_pembayaran']); ?>
                </div>
            </div>

            <!-- Modal Edit -->
            <div class="modal fade" id="edit<?php echo $m['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit <?php echo $m['nama_metode']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Metode</label>
                                    <input type="text" name="nama_metode" class="form-control" value="<?php echo $m['nama_metode']; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Deskripsi Singkat</label>
                                    <input type="text" name="deskripsi" class="form-control" value="<?php echo $m['deskripsi']; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Detail Pembayaran</label>
                                    <textarea name="detail_pembayaran" class="form-control" rows="4" required><?php echo $m['detail_pembayaran']; ?></textarea>
                                    <small class="text-muted">
                                        <?php if($m['kode'] == 'transfer'): ?>
                                            Contoh: Bank BCA: 1234567890 a.n. Nama Toko
                                        <?php elseif($m['kode'] == 'qris'): ?>
                                            Paste URL gambar QRIS (https://...)
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="active<?php echo $m['id']; ?>" <?php echo $m['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="active<?php echo $m['id']; ?>">
                                        Aktifkan metode pembayaran ini
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update" class="btn w-100 rounded-pill" style="background: #8b0000; color: white;">
                                    <i class="bi bi-check-circle me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
