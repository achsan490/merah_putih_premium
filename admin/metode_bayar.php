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
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - MerahPutih Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background: #F0F4FF; }
        .main-content { margin-left: 268px; padding: 0; }
        .topbar { height: 68px; background: white; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 500; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .topbar h1 { font-size: 1.15rem; font-weight: 700; color: #1A1A2E; margin: 0; }
        .topbar p { font-size: 0.78rem; color: #8A8AA0; margin: 0; }
        .content-wrap { padding: 28px 32px; }
        .card-premium { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 28px; }
        
        .payment-card { 
            border: 2px solid #EAEAF2; 
            border-radius: 18px; 
            padding: 24px; 
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: #F8F9FD;
        }
        .payment-card.active { 
            border-color: #C0392B; 
            background: white; 
            box-shadow: 0 8px 30px rgba(192,57,43,0.04);
        }
        .payment-card:hover { 
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }
        .btn-admin { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 20px; border-radius: 12px; font-size: 0.88rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-danger-solid { background: linear-gradient(135deg, #922B21, #E74C3C); color: white; width: 100%; } .btn-danger-solid:hover { opacity: 0.9; }
        
        .btn-action-outline { background: white; color: #4A4A6A; border: 1.5px solid #E2E2EC; padding: 8px 16px; font-weight: 600; border-radius: 10px; font-size: 0.8rem; }
        .btn-action-outline:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }

        .form-control { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.9rem; transition: border-color 0.2s; }
        .form-control:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #4A4A6A; margin-bottom: 6px; }

        .modal-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.15); }
        .modal-header { background: #F8F8FF; border-bottom: 1px solid #EEEEF8; border-radius: 20px 20px 0 0; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #EEEEF8; padding: 16px 24px; border-radius: 0 0 20px 20px; }
        
        .badge-status { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-active { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-inactive { background: #F3F4F6; color: #6B7280; border: 1px solid #E5E7EB; }

        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>Metode Pembayaran</h1>
                <p>Kelola metode pembayaran yang tersedia untuk pelanggan</p>
            </div>
        </div>

        <div class="content-wrap">
            <div class="card-premium">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-credit-card-2-front text-danger me-2"></i>Saluran Pembayaran Toko</h5>
                
                <div class="row g-4">
                    <?php 
                    $methods = mysqli_query($koneksi, "SELECT * FROM payment_methods ORDER BY id ASC");
                    $metode_list = [];
                    while($m = mysqli_fetch_assoc($methods)): 
                        $metode_list[] = $m;
                    ?>
                    <div class="col-12">
                        <div class="payment-card <?php echo $m['is_active'] ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center">
                                    <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(192,57,43,0.06); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                                        <i class="bi <?php echo $m['icon']; ?> fs-3 text-danger"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.05rem;"><?php echo htmlspecialchars($m['nama_metode']); ?></h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="font-size: 0.78rem; color: #8A8AA0;"><?php echo htmlspecialchars($m['deskripsi']); ?></span>
                                            <?php if($m['is_active']): ?>
                                                <span class="badge-status badge-active">AKTIF</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-inactive">NON-AKTIF</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn-action-outline" data-bs-toggle="modal" data-bs-target="#edit<?php echo $m['id']; ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit Pengaturan
                                </button>
                            </div>
                            
                            <div class="p-3 rounded-3 mt-3" style="background: #F0F4FF; border: 1px solid #E2EAF8; font-size: 0.88rem; color: #4A4A6A;">
                                <strong style="color: #1A1A2E;"><i class="bi bi-info-circle me-1 text-primary"></i>Instruksi / Detail Pembayaran:</strong>
                                <div class="mt-1 font-monospace" style="white-space: pre-line; line-height: 1.5; font-size: 0.82rem;">
                                    <?php echo htmlspecialchars($m['detail_pembayaran']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modals -->
    <?php foreach($metode_list as $m): ?>
    <div class="modal fade" id="edit<?php echo $m['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-dark">Edit <?php echo htmlspecialchars($m['nama_metode']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Metode</label>
                            <input type="text" name="nama_metode" class="form-control" value="<?php echo htmlspecialchars($m['nama_metode']); ?>" required autocomplete="off">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Singkat</label>
                            <input type="text" name="deskripsi" class="form-control" value="<?php echo htmlspecialchars($m['deskripsi']); ?>" required autocomplete="off">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Detail Pembayaran / Instruksi</label>
                            <textarea name="detail_pembayaran" class="form-control" rows="4" required><?php echo htmlspecialchars($m['detail_pembayaran']); ?></textarea>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                <?php if($m['kode'] == 'transfer'): ?>
                                    Tuliskan nomor rekening dan atas nama pemilik bank.
                                <?php elseif($m['kode'] == 'qris'): ?>
                                    Tuliskan link/URL gambar QRIS atau panduan scan.
                                <?php else: ?>
                                    Tuliskan panduan tata cara pembayaran bagi pembeli.
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="form-check form-switch mt-4 p-0 d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-semibold text-dark" style="font-size: 0.88rem;" for="active<?php echo $m['id']; ?>">
                                Aktifkan metode pembayaran ini
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="active<?php echo $m['id']; ?>" <?php echo $m['is_active'] ? 'checked' : ''; ?> style="width: 44px; height: 22px; cursor: pointer;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update" class="btn-admin btn-danger-solid">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
