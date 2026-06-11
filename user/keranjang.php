<?php 
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Logika untuk tombol "Beli" (Direct Redirect)
if (isset($_GET['id']) && isset($_GET['aksi']) && $_GET['aksi'] == 'beli') {
    $id_p = $_GET['id'];
    if (isset($_SESSION['keranjang'][$id_p])) { $_SESSION['keranjang'][$id_p] += 1; } 
    else { $_SESSION['keranjang'][$id_p] = 1; }
    header("location:keranjang.php");
    exit;
}

// Logika Tambah Quantity
if (isset($_GET['tambah'])) {
    $id_p = $_GET['tambah'];
    if(isset($_SESSION['keranjang'][$id_p])) {
        $_SESSION['keranjang'][$id_p] += 1;
    }
    header("location:keranjang.php");
    exit;
}

// Logika Kurang Quantity
if (isset($_GET['kurang'])) {
    $id_p = $_GET['kurang'];
    if(isset($_SESSION['keranjang'][$id_p])) {
        $_SESSION['keranjang'][$id_p] -= 1;
        if($_SESSION['keranjang'][$id_p] <= 0) {
            unset($_SESSION['keranjang'][$id_p]);
        }
    }
    header("location:keranjang.php");
    exit;
}

// Logika Hapus
if (isset($_GET['hapus'])) {
    unset($_SESSION['keranjang'][$_GET['hapus']]);
    header("location:keranjang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .bg-grad-merah { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); color: white; }
        .card-keranjang { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-hapus { color: #dc3545; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-hapus:hover { background: #fee2e2; }
        .img-produk { width: 80px; height: 80px; object-fit: cover; border-radius: 15px; }
        .btn-checkout { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); border: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-grad-merah sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-800" href="index.php"><i class="bi bi-arrow-left-short"></i> KERANJANG</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-4">Barang di Keranjang</h5>
                <?php 
                $total_belanja = 0;
                if(!empty($_SESSION['keranjang'])):
                    foreach($_SESSION['keranjang'] as $id_produk => $jumlah):
                        $res = mysqli_query($koneksi, "SELECT * FROM produk WHERE id='$id_produk'");
                        $p = mysqli_fetch_assoc($res);
                        
                        // Calculate price based on quantity (tiered pricing)
                        $harga_satuan = $p['harga'];
                        $is_grosir = false;
                        if($p['harga_grosir'] && $p['harga_grosir'] > 0 && $jumlah >= $p['min_qty_grosir']) {
                            $harga_satuan = $p['harga_grosir'];
                            $is_grosir = true;
                        }
                        
                        $subtotal = $harga_satuan * $jumlah;
                        $total_belanja += $subtotal;
                ?>
                <div class="card card-keranjang p-3 mb-3 bg-white">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '../assets/'.$p['foto']; ?>" class="img-produk me-3">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-2"><?php echo $p['nama_produk']; ?></h6>
                            <p class="small text-muted mb-2">
                                Rp <?php echo number_format($harga_satuan); ?>/unit
                                <?php if($is_grosir): ?>
                                    <span class="badge bg-success ms-1">Harga Grosir</span>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Quantity Controls -->
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <a href="keranjang.php?kurang=<?php echo $id_produk; ?>" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-dash"></i>
                                </a>
                                <span class="fw-bold" style="min-width: 30px; text-align: center;"><?php echo $jumlah; ?></span>
                                <a href="keranjang.php?tambah=<?php echo $id_produk; ?>" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-plus"></i>
                                </a>
                            </div>
                            
                            <span class="fw-bold text-danger">Rp <?php echo number_format($subtotal); ?></span>
                        </div>
                        <a href="keranjang.php?hapus=<?php echo $id_produk; ?>" class="btn btn-hapus border-0">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center py-5 bg-white card-keranjang">
                    <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                    <p class="mt-3">Keranjangmu masih kosong, sayang.</p>
                    <a href="index.php" class="btn btn-danger rounded-pill px-4">Mulai Belanja</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card card-keranjang p-4 bg-white sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Ringkasan</h5>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold">Total Harga</span>
                        <h4 class="fw-800 text-danger">Rp <?php echo number_format($total_belanja); ?></h4>
                    </div>
                    <a href="checkout.php" class="btn btn-checkout text-white w-100 py-3 rounded-pill fw-bold shadow-sm <?php echo (empty($_SESSION['keranjang'])) ? 'disabled' : ''; ?>">
                        Checkout Sekarang <i class="bi bi-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php 
    if(isset($_GET['hapus'])) {
        $id_h = $_GET['hapus'];
        unset($_SESSION['keranjang'][$id_h]);
        echo "<script>window.location='keranjang.php';</script>";
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>