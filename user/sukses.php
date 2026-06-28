<?php 
include '../config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Jika tidak ada ID di URL, balikkan ke index
if(!isset($_GET['id'])) { 
    header('location: index.php'); 
    exit;
}

$id_nota = $_GET['id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - MerahPutih Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background-color: #F8F9FD; color: #1A1A2E; }

        .card-sukses { 
            border-radius: 24px; 
            border: none; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            margin-top: 60px;
            padding: 48px;
            background: white;
            border: 1px solid #F0F0F8;
        }
        
        .nota-box {
            background-color: #FEF0EE;
            border: 2px dashed #C0392B;
            border-radius: 18px;
            padding: 24px;
        }

        .icon-check-container {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #ECFDF5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px auto;
            color: #059669;
            box-shadow: 0 8px 24px rgba(5,150,105,0.12);
        }

        .btn-mp-primary { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; font-weight: 700; border-radius: 12px; padding: 14px 28px; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; transition: all 0.2s; }
        .btn-mp-primary:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-mp-danger { background: linear-gradient(135deg, #922B21 0%, #E74C3C 100%); color: white; }
        .btn-mp-success { background: linear-gradient(135deg, #059669 0%, #10B981 100%); color: white; }
        .btn-mp-outline { background: white; border: 1.5px solid #E2E2EC; color: #4A4A6A; font-weight: 700; border-radius: 12px; padding: 12px 28px; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; transition: all 0.2s; }
        .btn-mp-outline:hover { background: #F8F9FD; color: #1A1A2E; border-color: #C0392B; }

        .bank-info-box { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 16px; padding: 18px; color: #1E40AF; text-align: left; }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 text-center">
                <div class="card card-sukses">
                    <div class="icon-check-container">
                        <i class="bi bi-check-lg fs-1"></i>
                    </div>
                    
                    <h3 class="fw-bold text-dark mb-2">Pesanan Berhasil Dibuat!</h3>
                    <p class="text-secondary small mb-4" style="line-height:1.6;">Terima kasih atas kepercayaan Anda belanja di MerahPutih Marketplace. Pesanan Anda telah terdaftar dan siap diproses.</p>
                    
                    <div class="nota-box mb-4">
                        <span class="text-uppercase fw-semibold text-secondary d-block mb-1" style="font-size:0.75rem; letter-spacing:0.5px;">Nomor Nota Pembelian</span>
                        <h2 class="fw-bold text-danger m-0" style="font-size: 2.2rem; letter-spacing: 0.5px;">#<?php echo $id_nota; ?></h2>
                    </div>

                    <div class="bank-info-box mb-4">
                        <strong class="d-block mb-2" style="font-size:0.88rem;"><i class="bi bi-bank me-2"></i>Informasi Rekening Pembayaran</strong>
                        <div style="font-size:0.85rem; line-height:1.6;">
                            Transfer ke Bank BCA:<br>
                            Nomor Rekening: <strong>1234567890</strong><br>
                            Atas Nama: <strong>MerahPutih Store</strong>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <a href="konfirmasi_bayar.php?id=<?php echo $id_nota; ?>" class="btn-mp-primary btn-mp-success py-3 shadow-sm">
                            <i class="bi bi-cloud-upload"></i> Upload Bukti Transfer
                        </a>
                        <a href="cek_pesanan.php" class="btn-mp-primary btn-mp-danger py-3 shadow-sm">
                            <i class="bi bi-search"></i> Lacak Status Pesanan
                        </a>
                        <a href="index.php" class="btn-mp-outline py-3">
                            <i class="bi bi-shop"></i> Kembali Belanja
                        </a>
                    </div>
                </div>
                
                <p class="mt-4 text-muted small">&copy; 2026 MerahPutih Marketplace. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>