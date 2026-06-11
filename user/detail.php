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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $produk['nama_produk']; ?> - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --merah: #e63946; --merah-tua: #8b0000; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .product-image { width: 100%; height: 500px; object-fit: cover; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .breadcrumb { background: transparent; padding: 0; margin-bottom: 20px; }
        .breadcrumb-item a { color: var(--merah); text-decoration: none; }
        .rating-stars { color: #ffc107; }
        .btn-add-cart { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); color: white; border: none; padding: 15px 40px; border-radius: 50px; font-weight: 600; }
        .review-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .related-card { border: none; border-radius: 15px; transition: 0.3s; }
        .related-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-800 fs-3" href="index.php" style="color: var(--merah-tua);">MERAH<span class="text-secondary">PUTIH</span></a>
            <a href="keranjang.php" class="btn btn-outline-danger rounded-pill">
                <i class="bi bi-bag-heart"></i> Keranjang (<?php echo isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0; ?>)
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?kategori=<?php echo $produk['kategori_id']; ?>"><?php echo $produk['nama_kategori'] ?? 'Produk'; ?></a></li>
                <li class="breadcrumb-item active"><?php echo $produk['nama_produk']; ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-6 mb-4">
                <img src="<?php echo (strpos($produk['foto'], 'http') === 0) ? $produk['foto'] : '../assets/'.$produk['foto']; ?>" class="product-image" alt="<?php echo $produk['nama_produk']; ?>">
            </div>
            <div class="col-md-6">
                <h1 class="fw-bold mb-3"><?php echo $produk['nama_produk']; ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="rating-stars me-2">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="bi bi-star<?php echo $i <= $avg_rating ? '-fill' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> ulasan)</span>
                </div>

                <h2 class="text-danger fw-800 mb-2">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></h2>
                <div class="mb-3">
                    <?php if ($produk['stok'] !== null && $produk['stok'] <= 0): ?>
                        <span class="badge bg-danger p-2" style="font-size: 0.9rem;">Stok Habis</span>
                    <?php elseif ($produk['stok'] !== null): ?>
                        <span class="badge bg-success p-2" style="font-size: 0.9rem;">Tersedia (Sisa <?php echo $produk['stok']; ?> pcs)</span>
                    <?php endif; ?>
                </div>
                <?php if($produk['harga_grosir'] && $produk['harga_grosir'] > 0): ?>
                    <div class="alert alert-success py-2 mb-4">
                        <i class="bi bi-gift me-2"></i>
                        <strong>Harga Grosir:</strong> Beli <?php echo $produk['min_qty_grosir']; ?>+ pcs = Rp <?php echo number_format($produk['harga_grosir'], 0, ',', '.'); ?>/pcs
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <h5 class="fw-bold">Deskripsi Produk</h5>
                    <p class="text-muted"><?php echo nl2br($produk['deskripsi'] ?? 'Produk berkualitas premium dengan harga terjangkau.'); ?></p>
                </div>

                <div class="mb-4">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-tag me-1"></i> <?php echo $produk['nama_kategori'] ?? 'Lainnya'; ?>
                    </span>
                </div>

                <div class="d-grid gap-2">
                    <?php if ($produk['stok'] !== null && $produk['stok'] <= 0): ?>
                        <button class="btn btn-secondary py-3 rounded-pill fw-bold" disabled>
                            <i class="bi bi-cart-x me-2"></i> Stok Habis
                        </button>
                    <?php else: ?>
                        <a href="keranjang.php?id=<?php echo $produk['id']; ?>&aksi=beli" class="btn btn-add-cart">
                            <i class="bi bi-cart-plus me-2"></i> Tambah ke Keranjang
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-outline-dark rounded-pill" onclick="window.history.back()">
                        <i class="bi bi-arrow-left me-2"></i> Kembali Belanja
                    </button>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="fw-bold mb-4">Ulasan Pelanggan</h4>
                
                <?php if(mysqli_num_rows($reviews) > 0): ?>
                    <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo $review['nama_reviewer']; ?></h6>
                                    <div class="rating-stars small">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                            </div>
                            <p class="mb-0 text-muted"><?php echo $review['komentar']; ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-light text-center">Belum ada ulasan. Jadilah yang pertama!</div>
                <?php endif; ?>

                <!-- Add Review Form -->
                <div class="card border-0 shadow-sm mt-4 p-4" style="border-radius: 15px;">
                    <h5 class="fw-bold mb-3">Tulis Ulasan Anda</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" name="nama" class="form-control" placeholder="Nama Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <select name="rating" class="form-select" required>
                                <option value="5">⭐⭐⭐⭐⭐ Sangat Puas</option>
                                <option value="4">⭐⭐⭐⭐ Puas</option>
                                <option value="3">⭐⭐⭐ Cukup</option>
                                <option value="2">⭐⭐ Kurang</option>
                                <option value="1">⭐ Sangat Kurang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <textarea name="komentar" class="form-control" rows="3" placeholder="Ceritakan pengalaman Anda..." required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-danger rounded-pill px-4">Kirim Ulasan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if(mysqli_num_rows($related) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="fw-bold mb-4">Produk Serupa</h4>
                <div class="row g-3">
                    <?php while($rel = mysqli_fetch_assoc($related)): ?>
                        <div class="col-6 col-md-3">
                            <div class="card related-card">
                                <img src="<?php echo (strpos($rel['foto'], 'http') === 0) ? $rel['foto'] : '../assets/'.$rel['foto']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h6 class="card-title" style="height: 40px; overflow: hidden;"><?php echo $rel['nama_produk']; ?></h6>
                                    <p class="text-danger fw-bold">Rp <?php echo number_format($rel['harga']); ?></p>
                                    <a href="detail.php?id=<?php echo $rel['id']; ?>" class="btn btn-sm btn-outline-danger w-100 rounded-pill">Lihat</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
