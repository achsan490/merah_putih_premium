<?php 
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Logika untuk tombol "Beli" (Direct Redirect dengan Varian Kemasan)
if (isset($_GET['id']) && isset($_GET['aksi']) && $_GET['aksi'] == 'beli') {
    $id_p = (int)$_GET['id'];
    
    // Jika kemasan dikirimkan dari detail.php, gunakan itu.
    // Jika tidak (misal dari index.php yang direct redirect), cari default 'Pcs'.
    if (isset($_GET['kemasan'])) {
        $id_kemasan = (int)$_GET['kemasan'];
    } else {
        $q_var = mysqli_query($koneksi, "SELECT id_kemasan FROM produk_kemasan WHERE id_produk = $id_p ORDER BY id_kemasan ASC LIMIT 1");
        $var_data = mysqli_fetch_assoc($q_var);
        $id_kemasan = $var_data ? $var_data['id_kemasan'] : 0;
    }
    
    if ($id_kemasan > 0) {
        if (isset($_SESSION['keranjang'][$id_kemasan])) { 
            $_SESSION['keranjang'][$id_kemasan] += 1; 
        } else { 
            $_SESSION['keranjang'][$id_kemasan] = 1; 
        }
    }
    header("location:keranjang.php");
    exit;
}

// Logika Tambah Quantity
if (isset($_GET['tambah'])) {
    $id_kemasan = (int)$_GET['tambah'];
    if(isset($_SESSION['keranjang'][$id_kemasan])) {
        $_SESSION['keranjang'][$id_kemasan] += 1;
    }
    header("location:keranjang.php");
    exit;
}

// Logika Kurang Quantity
if (isset($_GET['kurang'])) {
    $id_kemasan = (int)$_GET['kurang'];
    if(isset($_SESSION['keranjang'][$id_kemasan])) {
        $_SESSION['keranjang'][$id_kemasan] -= 1;
        if($_SESSION['keranjang'][$id_kemasan] <= 0) {
            unset($_SESSION['keranjang'][$id_kemasan]);
        }
    }
    header("location:keranjang.php");
    exit;
}

// Logika Hapus
if (isset($_GET['hapus'])) {
    $id_kemasan = (int)$_GET['hapus'];
    unset($_SESSION['keranjang'][$id_kemasan]);
    header("location:keranjang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - MerahPutih Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background-color: #F8F9FD; color: #1A1A2E; }

        /* Custom Header Navbar */
        .navbar-mp { background: white; border-bottom: 1px solid rgba(0,0,0,0.06); padding: 18px 0; }
        .logo-text { font-size: 1.5rem; font-weight: 800; color: #1A1A2E; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logo-text span { color: #C0392B; }
        .btn-back { background: #F8F8FF; color: #4A4A6A; border: 1px solid #E2E2EC; border-radius: 12px; font-weight: 700; font-size: 0.85rem; padding: 10px 18px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s; }
        .btn-back:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }

        .card-premium { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #F0F0F8; padding: 24px; }
        
        .cart-item { display: flex; align-items: center; justify-content: space-between; padding: 20px 0; border-bottom: 1px solid #F0F0F8; }
        .cart-item:last-child { border-bottom: none; }
        
        .img-produk { width: 88px; height: 88px; object-fit: contain; border-radius: 14px; background: #F8F9FD; padding: 8px; border: 1px solid #EAEAF2; }
        
        .quantity-control { display: inline-flex; align-items: center; border: 1.5px solid #E2E2EC; border-radius: 10px; background: white; padding: 2px; }
        .quantity-btn { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; background: transparent; color: #4A4A6A; font-weight: 700; border-radius: 8px; text-decoration: none; transition: all 0.2s; }
        .quantity-btn:hover { background: #F0F0F8; color: #1A1A2E; }
        
        .btn-hapus { color: #8A8AA0; border-radius: 10px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border: 1px solid #E2E2EC; text-decoration: none; background: white; }
        .btn-hapus:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }

        .btn-checkout { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); border: none; color: white; font-weight: 700; border-radius: 12px; padding: 14px 24px; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; width: 100%; transition: all 0.2s; }
        .btn-checkout:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-checkout.disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .price-label { font-size: 0.82rem; color: #8A8AA0; font-weight: 600; }
        .price-val { font-size: 1rem; font-weight: 700; color: #1A1A2E; }
        .subtotal-val { font-size: 1.1rem; font-weight: 800; color: #C0392B; }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-mp">
        <div class="container">
            <a class="logo-text" href="index.php">
                <i class="bi bi-shop text-danger"></i> MERAH<span>PUTIH</span>
            </a>
            <a href="index.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Kembali Belanja
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-premium">
                    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-bag text-danger me-2"></i>Keranjang Belanja Anda</h5>
                    
                    <?php 
                    $total_belanja = 0;
                    if(!empty($_SESSION['keranjang'])):
                    ?>
                        <div class="d-flex flex-column">
                            <?php
                            foreach($_SESSION['keranjang'] as $id_kemasan => $jumlah):
                                $res = mysqli_query($koneksi, "
                                    SELECT pk.*, p.nama_produk, p.foto, p.harga_grosir, p.min_qty_grosir 
                                    FROM produk_kemasan pk 
                                    JOIN produk p ON pk.id_produk = p.id 
                                    WHERE pk.id_kemasan = '$id_kemasan'
                                ");
                                $p = mysqli_fetch_assoc($res);
                                if(!$p) continue;
                                
                                // Calculate price based on quantity (tiered pricing only for base unit 'Pcs')
                                $harga_satuan = $p['harga'];
                                $is_grosir = false;
                                if($p['nama_satuan'] == 'Pcs' && $p['harga_grosir'] && $p['harga_grosir'] > 0 && $jumlah >= $p['min_qty_grosir']) {
                                    $harga_satuan = $p['harga_grosir'];
                                    $is_grosir = true;
                                }
                                
                                $subtotal = $harga_satuan * $jumlah;
                                $total_belanja += $subtotal;
                            ?>
                            <div class="cart-item flex-wrap flex-md-nowrap gap-3">
                                <div class="d-flex align-items-center gap-3 flex-grow-1">
                                    <img src="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '../assets/'.$p['foto']; ?>" class="img-produk" alt="<?php echo htmlspecialchars($p['nama_produk']); ?>">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($p['nama_produk']); ?> <span class="text-danger">(<?php echo htmlspecialchars($p['nama_satuan']); ?>)</span></h6>
                                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                            <span class="price-label">Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?>/unit</span>
                                            <?php if($is_grosir): ?>
                                                <span class="badge bg-success rounded-pill font-monospace" style="font-size: 0.65rem;">GROSIR</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Quantity Control -->
                                        <div class="quantity-control">
                                            <a href="keranjang.php?kurang=<?php echo $id_kemasan; ?>" class="quantity-btn">
                                                <i class="bi bi-dash"></i>
                                            </a>
                                            <span class="fw-bold px-2" style="font-size: 0.85rem; color:#1A1A2E;"><?php echo $jumlah; ?></span>
                                            <a href="keranjang.php?tambah=<?php echo $id_kemasan; ?>" class="quantity-btn">
                                                <i class="bi bi-plus"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-4 w-100 w-md-auto">
                                    <div class="text-md-end">
                                        <div style="font-size:0.75rem; color:#8A8AA0; font-weight:600;">Subtotal</div>
                                        <span class="subtotal-val">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                                    </div>
                                    <a href="keranjang.php?hapus=<?php echo $id_kemasan; ?>" class="btn-hapus" onclick="return confirm('Hapus item ini?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x fs-1 opacity-25 d-block mb-3"></i>
                            <p class="text-secondary small mb-4">Keranjang belanja Anda kosong.</p>
                            <a href="index.php" class="btn-checkout py-3 px-4 d-inline-flex w-auto">
                                <i class="bi bi-shop"></i> Belanja Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-premium sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-dark mb-4">Ringkasan Belanja</h5>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-semibold text-secondary" style="font-size:0.9rem;">Total Tagihan</span>
                        <span class="subtotal-val" style="font-size:1.35rem;">Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn-checkout py-3 <?php echo (empty($_SESSION['keranjang'])) ? 'disabled' : ''; ?>">
                        Checkout Sekarang <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>