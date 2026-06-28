<?php 
include '../config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_cabang = isset($_SESSION['customer_cabang_id']) ? (int)$_SESSION['customer_cabang_id'] : 1;
$produk = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT p.*, c.nama_kategori, COALESCE(sc.stok, 0) AS stok 
    FROM produk p 
    LEFT JOIN categories c ON p.kategori_id = c.id 
    LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = $id_cabang 
    WHERE p.id = $id
"));

if(!$produk) {
    header("location: index.php");
    exit;
}

// Get reviews
$reviews = mysqli_query($koneksi, "SELECT * FROM product_reviews WHERE id_produk = $id ORDER BY created_at DESC");

// Calculate average rating
$rating_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE id_produk = $id"));
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'];

// Get related products (same category)
$related = mysqli_query($koneksi, "SELECT * FROM produk WHERE kategori_id = {$produk['kategori_id']} AND id != $id LIMIT 4");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produk['nama_produk']); ?> - MerahPutih Marketplace</title>
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
        .btn-cart-custom { background: #FEF0EE; color: #C0392B; border: 1px solid #FECDC8; border-radius: 12px; font-weight: 700; font-size: 0.85rem; padding: 10px 18px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s; }
        .btn-cart-custom:hover { background: #C0392B; color: white; border-color: #C0392B; }

        /* Detail Elements */
        .breadcrumb-item a { color: #8A8AA0; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .breadcrumb-item.active { color: #1A1A2E; font-size: 0.85rem; font-weight: 700; }
        
        .product-image-container { background: white; border-radius: 24px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #F0F0F8; text-align: center; }
        .product-image { width: 100%; max-height: 480px; object-fit: contain; border-radius: 16px; }

        .rating-stars { color: #FFB300; font-size: 0.95rem; }

        .badge-stock { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-available { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-empty { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }

        .price-text { font-size: 2rem; font-weight: 800; color: #C0392B; }

        .grosir-box { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 16px; padding: 16px 20px; color: #065F46; font-size: 0.88rem; display: flex; align-items: center; gap: 12px; }

        .btn-mp-primary { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; font-weight: 700; border-radius: 12px; padding: 14px 28px; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; transition: all 0.2s; }
        .btn-mp-primary:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-mp-danger { background: linear-gradient(135deg, #922B21 0%, #E74C3C 100%); color: white; }
        .btn-mp-outline { background: white; border: 1.5px solid #E2E2EC; color: #4A4A6A; font-weight: 700; border-radius: 12px; padding: 12px 28px; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; transition: all 0.2s; }
        .btn-mp-outline:hover { background: #F8F9FD; color: #1A1A2E; border-color: #C0392B; }

        /* Review Design */
        .review-card { background: white; border-radius: 18px; padding: 20px; border: 1px solid #EAEAF2; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 16px; }
        .review-user { font-weight: 700; color: #1A1A2E; font-size: 0.95rem; }
        .review-comment { font-size: 0.88rem; color: #4A4A6A; line-height: 1.6; margin-top: 8px; }

        .form-control, .form-select { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.88rem; transition: border-color 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }

        /* Product Card */
        .product-card { background: white; border: none; border-radius: 18px; box-shadow: 0 6px 20px rgba(0,0,0,0.03); border: 1px solid #EEEEF5; transition: all 0.25s ease; height: 100%; display: flex; flex-direction: column; overflow: hidden; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(192,57,43,0.08); border-color: #FECDC8; }
        .product-card-img-wrap { height: 180px; background: #F8F9FD; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 16px; }
        .product-card-img { max-height: 100%; max-width: 100%; object-fit: contain; transition: transform 0.3s; }
        .product-card:hover .product-card-img { transform: scale(1.05); }
        .product-card-body { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .product-title { font-size: 0.85rem; font-weight: 700; color: #1A1A2E; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; }
        .product-price { font-size: 0.95rem; font-weight: 800; color: #C0392B; margin-bottom: 12px; }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-mp">
        <div class="container">
            <a class="logo-text" href="index.php">
                <i class="bi bi-shop text-danger"></i> MERAH<span>PUTIH</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="keranjang.php" class="btn-cart-custom">
                    <i class="bi bi-cart3"></i> Keranjang (<?php echo isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0; ?>)
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Detail Content -->
    <div class="container my-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?kategori=<?php echo $produk['kategori_id']; ?>"><?php echo htmlspecialchars($produk['nama_kategori'] ?? 'Katalog'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($produk['nama_produk']); ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="product-image-container">
                    <img src="<?php echo (strpos($produk['foto'], 'http') === 0) ? $produk['foto'] : '../assets/'.$produk['foto']; ?>" class="product-image" alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>">
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="ps-lg-3">
                    <div class="mb-2">
                        <span class="badge bg-secondary rounded-pill font-monospace py-2 px-3" style="font-size:0.7rem; background:rgba(0,0,0,0.06) !important; color:#4A4A6A !important;">
                            <i class="bi bi-bookmark-fill text-danger me-1"></i><?php echo htmlspecialchars($produk['nama_kategori'] ?? 'Kategori'); ?>
                        </span>
                    </div>
                    
                    <h1 class="fw-bold mb-3" style="font-size: 1.85rem; color:#1A1A2E;"><?php echo htmlspecialchars($produk['nama_produk']); ?></h1>
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rating-stars">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= $avg_rating ? '-fill' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-1 fw-bold text-dark" style="font-size:0.85rem;"><?php echo $avg_rating; ?></span>
                        </div>
                        <span style="color:#C0C0D0;">|</span>
                        <span style="font-size: 0.85rem; color: #8A8AA0; font-weight:600;"><i class="bi bi-chat-left-text me-1"></i><?php echo $total_reviews; ?> Ulasan</span>
                    </div>

                    <!-- Varian / Kemasan Dropdown -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark d-block mb-2"><i class="bi bi-box-seam text-danger me-1"></i>Pilih Kemasan / Varian:</label>
                        <select id="select-kemasan" class="form-select w-100" onchange="updatePriceAndStock()" style="border-radius:12px; padding:12px 16px;">
                            <?php
                            $variants = mysqli_query($koneksi, "SELECT * FROM produk_kemasan WHERE id_produk = $id ORDER BY id_kemasan ASC");
                            $first_v = null;
                            while($v = mysqli_fetch_assoc($variants)):
                                if (!$first_v) $first_v = $v;
                                $variant_stock = floor($produk['stok'] / $v['faktor_kali']);
                            ?>
                                <option value="<?php echo $v['id_kemasan']; ?>" 
                                        data-price="<?php echo $v['harga']; ?>" 
                                        data-stock="<?php echo $variant_stock; ?>"
                                        data-nama="<?php echo htmlspecialchars($v['nama_satuan']); ?>">
                                    <?php echo htmlspecialchars($v['nama_satuan']); ?> (Rp <?php echo number_format($v['harga'], 0, ',', '.'); ?>) — Tersedia <?php echo $variant_stock; ?> unit
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <div class="price-text" id="display-price">Rp <?php echo number_format($first_v ? $first_v['harga'] : $produk['harga'], 0, ',', '.'); ?></div>
                        
                        <div class="mt-2" id="display-stock-badge">
                            <!-- Diisi oleh Javascript updatePriceAndStock() -->
                        </div>
                    </div>

                    <?php if($produk['harga_grosir'] && $produk['harga_grosir'] > 0): ?>
                        <div class="grosir-box mb-4" id="grosir-promo-box">
                            <i class="bi bi-gift fs-4"></i>
                            <div>
                                <strong class="d-block" style="font-size:0.9rem;">Promo Grosir</strong>
                                <span>Beli <?php echo $produk['min_qty_grosir']; ?>+ pcs eceran hemat menjadi <strong style="color: #C0392B;">Rp <?php echo number_format($produk['harga_grosir'], 0, ',', '.'); ?></strong> per produk!</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-5">
                        <h6 class="fw-bold text-dark mb-2">Deskripsi Produk</h6>
                        <div class="text-secondary" style="font-size:0.9rem; line-height:1.7;">
                            <?php echo nl2br($produk['deskripsi'] ?? 'Produk berkualitas tinggi yang siap dikirim langsung dari cabang terdekat ke lokasi Anda.'); ?>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-8" id="cart-button-container">
                            <button onclick="addToCart()" id="btn-add-cart-var" class="btn-mp-primary btn-mp-danger" style="padding:16px 28px;">
                                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </div>
                        <div class="col-sm-4">
                            <button class="btn-mp-outline" onclick="window.location='index.php'">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ulasan Section -->
        <div class="row mt-5 pt-4">
            <div class="col-lg-7 mb-4">
                <h4 class="fw-bold text-dark mb-4"><i class="bi bi-chat-square-text text-danger me-2"></i>Ulasan Pembeli (<?php echo $total_reviews; ?>)</h4>
                
                <?php if(mysqli_num_rows($reviews) > 0): ?>
                    <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <span class="review-user"><?php echo htmlspecialchars($review['nama_reviewer']); ?></span>
                                    <div class="rating-stars small mt-1">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span style="font-size: 0.75rem; color:#8A8AA0; font-weight:600;"><i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['komentar'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5 rounded-4 border" style="background:#FDFDFD;">
                        <i class="bi bi-chat-dots fs-1 opacity-25 d-block mb-2"></i>
                        <span class="text-muted small">Belum ada ulasan untuk produk ini.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form Ulasan -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: white;">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pencil-square text-danger me-2"></i>Beri Penilaian</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nama Anda</label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama panggilan Anda" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tingkat Kepuasan</label>
                            <select name="rating" class="form-select" required>
                                <option value="5">⭐⭐⭐⭐⭐ Sangat Puas</option>
                                <option value="4">⭐⭐⭐⭐ Puas</option>
                                <option value="3">⭐⭐⭐ Cukup</option>
                                <option value="2">⭐⭐ Kurang</option>
                                <option value="1">⭐ Sangat Buruk</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Komentar / Ulasan</label>
                            <textarea name="komentar" class="form-control" rows="3" placeholder="Tuliskan pendapat Anda mengenai produk ini..." required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn-mp-primary btn-mp-danger w-100 mt-2">
                            <i class="bi bi-send-fill"></i> Kirim Ulasan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if(mysqli_num_rows($related) > 0): ?>
        <div class="row mt-5 pt-4">
            <div class="col-12">
                <h4 class="fw-bold text-dark mb-4"><i class="bi bi-grid text-danger me-2"></i>Rekomendasi Produk Serupa</h4>
                <div class="row g-3">
                    <?php while($rel = mysqli_fetch_assoc($related)): ?>
                        <div class="col-6 col-md-3">
                            <div class="product-card">
                                <div class="product-card-img-wrap">
                                    <img src="<?php echo (strpos($rel['foto'], 'http') === 0) ? $rel['foto'] : '../assets/'.$rel['foto']; ?>" class="product-card-img" alt="<?php echo htmlspecialchars($rel['nama_produk']); ?>">
                                </div>
                                <div class="product-card-body">
                                    <h6 class="product-title"><?php echo htmlspecialchars($rel['nama_produk']); ?></h6>
                                    <div>
                                        <div class="product-price">Rp <?php echo number_format($rel['harga'], 0, ',', '.'); ?></div>
                                        <a href="detail.php?id=<?php echo $rel['id']; ?>" class="btn-mp-outline py-2" style="font-size:0.78rem; border-radius:10px;">
                                            Lihat Produk
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // Handle review submission
    if(isset($_POST['submit_review'])) {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $rating = (int)$_POST['rating'];
        $komentar = mysqli_real_escape_string($koneksi, $_POST['komentar']);
        
        $insert = mysqli_query($koneksi, "INSERT INTO product_reviews (id_produk, nama_reviewer, rating, komentar) VALUES ($id, '$nama', $rating, '$komentar')");
        if($insert) {
            echo "<script>alert('Terima kasih atas ulasan Anda!'); window.location='detail.php?id=$id';</script>";
        }
    }
    ?>

    <script>
        function updatePriceAndStock() {
            const select = document.getElementById('select-kemasan');
            const selectedOpt = select.options[select.selectedIndex];
            
            if (!selectedOpt) return;
            
            const price = parseInt(selectedOpt.getAttribute('data-price')) || 0;
            const stock = parseInt(selectedOpt.getAttribute('data-stock')) || 0;
            const name = selectedOpt.getAttribute('data-nama');
            
            // Format price to Rupiah
            const formattedPrice = "Rp " + price.toLocaleString('id-ID');
            document.getElementById('display-price').innerText = formattedPrice;
            
            // Update stock badge and buy button
            const badgeContainer = document.getElementById('display-stock-badge');
            const cartBtn = document.getElementById('btn-add-cart-var');
            const promoBox = document.getElementById('grosir-promo-box');
            
            // Hide tiered wholesale promo box for bulk packaging (only show for base unit 'Pcs')
            if (promoBox) {
                if (name === 'Pcs') {
                    promoBox.style.display = 'flex';
                } else {
                    promoBox.style.display = 'none';
                }
            }
            
            if (stock <= 0) {
                badgeContainer.innerHTML = '<span class="badge-stock badge-empty"><i class="bi bi-exclamation-triangle"></i> Stok Varian Habis</span>';
                cartBtn.disabled = true;
                cartBtn.innerHTML = '<i class="bi bi-cart-x"></i> Stok Tidak Tersedia';
                cartBtn.className = 'btn-mp-primary btn-mp-danger opacity-50';
            } else {
                badgeContainer.innerHTML = `<span class="badge-stock badge-available"><i class="bi bi-check-circle"></i> Stok Tersedia (Sisa ${stock} ${name})</span>`;
                cartBtn.disabled = false;
                cartBtn.innerHTML = '<i class="bi bi-cart-plus"></i> Tambah ke Keranjang';
                cartBtn.className = 'btn-mp-primary btn-mp-danger';
            }
        }
        
        function addToCart() {
            const productId = <?php echo $id; ?>;
            const select = document.getElementById('select-kemasan');
            const variantId = select.value;
            window.location.href = `keranjang.php?id=${productId}&kemasan=${variantId}&aksi=beli`;
        }

        // Run on page load
        window.addEventListener('DOMContentLoaded', () => {
            updatePriceAndStock();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
