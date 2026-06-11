<?php 
include '../config.php'; 

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .card-sukses { 
            border-radius: 25px; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 50px;
        }
        .nota-box {
            background-color: #fff5f5;
            border: 2px dashed #d90429;
            border-radius: 15px;
        }
        .icon-check { font-size: 80px; color: #198754; }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card card-sukses p-5 bg-white">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill icon-check"></i>
                    </div>
                    <h2 class="fw-bold">Pesanan Diterima!</h2>
                    <p class="text-muted">Terima kasih sudah berbelanja di MerahPutih Shop, sayang. Pesananmu sedang kami siapkan.</p>
                    
                    <div class="nota-box p-4 my-4">
                        <p class="mb-1 small text-uppercase fw-bold text-muted">Nomor Nota Kamu:</p>
                        <h1 class="fw-bold text-danger display-4 mb-0">#<?php echo $id_nota; ?></h1>
                    </div>

                    <div class="alert alert-info border-0 text-start mb-4">
                        <h6 class="fw-bold"><i class="bi bi-bank me-2"></i>Silakan Transfer ke:</h6>
                        <p class="mb-1">Bank BCA: <strong>1234567890</strong></p>
                        <p class="mb-0">a.n. <strong>MerahPutih Store</strong></p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="konfirmasi_bayar.php?id=<?php echo $id_nota; ?>" class="btn btn-success btn-lg fw-bold rounded-pill shadow">
                            <i class="bi bi-upload me-2"></i>Upload Bukti Pembayaran
                        </a>
                        <a href="cek_pesanan.php" class="btn btn-danger btn-lg fw-bold rounded-pill shadow">Lacak Status Pesanan</a>
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill">Kembali ke Katalog</a>
                    </div>
                </div>
                <p class="mt-4 text-muted small">&copy; 2025 MerahPutih Marketplace</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>