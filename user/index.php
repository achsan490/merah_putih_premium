<?php 
include '../config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Auto-create categories table if missing
$check_cat_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'categories'");
if(mysqli_num_rows($check_cat_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    mysqli_query($koneksi, "INSERT INTO categories (nama_kategori) VALUES ('Fashion'), ('Elektronik'), ('Aksesoris'), ('Makanan & Minuman'), ('Lainnya')");
}

// Auto-add kategori_id column if missing
$check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'kategori_id'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN kategori_id INT DEFAULT 5");
}

// Auto-create product_reviews table if missing
$check_reviews = mysqli_query($koneksi, "SHOW TABLES LIKE 'product_reviews'");
if(mysqli_num_rows($check_reviews) == 0) {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_produk INT NOT NULL,
        nama_reviewer VARCHAR(100),
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        komentar TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Auto-add tiered pricing columns if missing
$check_grosir = mysqli_query($koneksi, "SHOW COLUMNS FROM produk LIKE 'harga_grosir'");
if(mysqli_num_rows($check_grosir) == 0) {
    mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN harga_grosir INT DEFAULT NULL");
    mysqli_query($koneksi, "ALTER TABLE produk ADD COLUMN min_qty_grosir INT DEFAULT 2");
}

// Handle customer branch selection
if (isset($_GET['set_cabang'])) {
    $sel_cabang_id = (int)$_GET['set_cabang'];
    $q_cabang_sel = mysqli_query($koneksi, "SELECT nama_cabang FROM cabang WHERE id_cabang = '$sel_cabang_id'");
    if ($c_data = mysqli_fetch_assoc($q_cabang_sel)) {
        $_SESSION['customer_cabang_id'] = $sel_cabang_id;
        $_SESSION['customer_cabang_nama'] = $c_data['nama_cabang'];
    }
    // Redirect to clean URL
    header("Location: index.php" . (isset($_GET['kategori']) ? "?kategori=" . (int)$_GET['kategori'] : ""));
    exit;
}

// Default branch to Pusat (ID 1) if not set
if (!isset($_SESSION['customer_cabang_id'])) {
    $_SESSION['customer_cabang_id'] = 1;
    $_SESSION['customer_cabang_nama'] = 'Toko Utama (Pusat / Online)';
}
$customer_cabang_id = $_SESSION['customer_cabang_id'];
$customer_cabang_nama = $_SESSION['customer_cabang_nama'];

$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MerahPutih - Premium Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { 
            --grad-merah: linear-gradient(135deg, #8b0000 0%, #e63946 100%); 
            --merah-terang: #e63946; 
            --merah-tua: #8b0000;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #fdfdfd; color: #333; overflow-x: hidden; }
        
        /* Navbar */
        .navbar { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px); 
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 0;
        }
        .navbar-brand { font-weight: 800; font-size: 1.5rem !important; color: var(--merah-tua) !important; }
        
        /* Hero Section */
        .hero-section { 
            background: var(--grad-merah); 
            color: white; 
            padding: 100px 0 160px 0; 
            border-radius: 0 0 50px 50px; 
            position: relative;
            overflow: hidden;
        }
        .hero-circle {
            position: absolute; width: 400px; height: 400px;
            background: rgba(255,255,255,0.1); border-radius: 50%;
            top: -100px; right: -100px;
        }
        .hero-section h1 { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; }
        .hero-section p { font-size: 1rem; opacity: 0.9; font-weight: 300; }
        
        /* Search Box Floating */
        .search-wrapper { margin-top: -40px; }
        .search-box { 
            border-radius: 50px; 
            padding: 20px 30px; 
            background: white;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); 
            display: flex;
            align-items: center;
        }
        .form-control-search { border: none; font-size: 1rem; width: 100%; outline: none; }
        .form-control-search:focus { box-shadow: none; }

        /* Product Cards */
        .card-produk { 
            border: none; 
            border-radius: 20px; 
            background: #fff; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        .card-produk:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(230, 57, 70, 0.15); }
        .img-wrapper { 
            height: 200px; overflow: hidden; background: #f8f9fa; 
            display: flex; align-items: center; justify-content: center;
        }
        .img-wrapper img { transition: 0.5s; width: 100%; height: 100%; object-fit: cover; }
        .card-produk:hover .img-wrapper img { transform: scale(1.1); }
        
        .card-body { padding: 20px !important; }
        .product-title { 
            font-size: 1rem; font-weight: 600; margin-bottom: 5px; color: #222;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 48px;
        }
        .product-price { font-size: 1.1rem; color: var(--merah-terang); font-weight: 800; }
        
        .btn-add { 
            width: 40px; height: 40px; border-radius: 50%; 
            background: #ffe5e7; color: var(--merah-tua); 
            display: flex; align-items: center; justify-content: center;
            border: none; transition: 0.3s;
        }
        .btn-add:hover { background: var(--merah-tua); color: white; }

        /* Badge Keranjang */
        .cart-float {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px;
            background: var(--grad-merah); color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 30px rgba(139, 0, 0, 0.4);
            font-size: 1.5rem;
            z-index: 100;
            transition: 0.3s;
            text-decoration: none;
        }
        .cart-float:hover { transform: scale(1.1) rotate(-10deg); color: white; }
        .badge-count {
            position: absolute; top: 0; right: 0;
            background: #fff; color: var(--merah-tua);
            border: 2px solid var(--merah-tua);
            width: 25px; height: 25px; border-radius: 50%;
            font-size: 12px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }

        @media (min-width: 768px) {
            .hero-section { padding: 120px 0 180px 0; border-radius: 0 0 50% 50% / 100px; }
            .hero-section h1 { font-size: 4rem; }
            .search-wrapper { margin-top: -50px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="index.php">
                MERAH<span class="text-secondary fw-light">PUTIH</span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Location Branch Selector -->
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <button class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold me-2" type="button" disabled>
                        <i class="bi bi-lock-fill me-1"></i> <?php echo htmlspecialchars($customer_cabang_nama); ?> (Locked)
                    </button>
                <?php else: ?>
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-danger btn-sm dropdown-toggle rounded-pill px-3 fw-semibold" type="button" id="branchDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($customer_cabang_nama); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2" aria-labelledby="branchDropdown">
                            <li><h6 class="dropdown-header text-muted">Pilih Cabang Belanja</h6></li>
                            <?php
                            $q_all_cabang = mysqli_query($koneksi, "SELECT * FROM cabang ORDER BY id_cabang ASC");
                            while($cab = mysqli_fetch_assoc($q_all_cabang)):
                            ?>
                                <li>
                                    <a class="dropdown-item py-2 <?php echo ($customer_cabang_id == $cab['id_cabang']) ? 'active bg-danger text-white' : ''; ?>" 
                                       href="index.php?set_cabang=<?php echo $cab['id_cabang']; ?><?php echo isset($_GET['kategori']) ? '&kategori='.(int)$_GET['kategori'] : ''; ?>">
                                        <i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($cab['nama_cabang']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <a href="cek_pesanan.php" class="btn btn-outline-dark rounded-pill px-3 btn-sm fw-bold">Lacak Pesanan</a>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="../admin/index.php" class="btn btn-danger rounded-pill px-3 btn-sm fw-bold">Dashboard</a>
                    <a href="../admin/logout.php" class="btn btn-outline-danger rounded-pill px-3 btn-sm fw-bold" title="Logout"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                <?php else: ?>
                    <a href="../admin/login.php" class="btn btn-outline-danger rounded-pill px-3 btn-sm fw-bold"><i class="bi bi-person-fill me-1"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="hero-section text-center">
        <div class="hero-circle"></div>
        <div class="container px-4 position-relative">
            <h1 class="mb-3">Kualitas <span style="color: #ffcccc;">Premium</span></h1>
            <p>Temukan produk terbaik dengan harga yang bersahabat.</p>
        </div>
    </header>

    <div class="container search-wrapper mb-5 position-relative">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <form action="index.php" method="GET">
                    <div class="search-box">
                        <i class="bi bi-search text-muted me-3"></i>
                        <input type="text" name="cari" class="form-control-search" placeholder="Mau cari apa hari ini?" value="<?php echo htmlspecialchars($keyword); ?>">
                        <button class="btn btn-danger rounded-circle p-2 ms-2" style="width: 40px; height: 40px;"><i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Category Filter -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="index.php" class="btn btn-sm <?php echo !isset($_GET['kategori']) ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill">Semua</a>
                    <?php
                    $categories = mysqli_query($koneksi, "SELECT * FROM categories");
                    while($cat = mysqli_fetch_assoc($categories)):
                    ?>
                        <a href="index.php?kategori=<?php echo $cat['id']; ?>" class="btn btn-sm <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == $cat['id']) ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill">
                            <?php echo $cat['nama_kategori']; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
                <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='index.php?sort=' + this.value + '<?php echo isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : ''; ?>'">
                    <option value="">Urutkan</option>
                    <option value="terbaru" <?php echo (isset($_GET['sort']) && $_GET['sort']=='terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="termurah" <?php echo (isset($_GET['sort']) && $_GET['sort']=='termurah') ? 'selected' : ''; ?>>Termurah</option>
                    <option value="termahal" <?php echo (isset($_GET['sort']) && $_GET['sort']=='termahal') ? 'selected' : ''; ?>>Termahal</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h4 class="fw-bold">Produk Pilihan</h4>
            <span class="text-muted small">Updated Today</span>
        </div>

        <div class="row g-3 g-md-4">
            <?php 
            $where = "WHERE nama_produk LIKE '%$keyword%'";
            if(isset($_GET['kategori']) && $_GET['kategori'] != '') {
                $kat_id = (int)$_GET['kategori'];
                $where .= " AND kategori_id = $kat_id";
            }
            
            $order = "ORDER BY id DESC";
            if(isset($_GET['sort'])) {
                if($_GET['sort'] == 'termurah') $order = "ORDER BY harga ASC";
                elseif($_GET['sort'] == 'termahal') $order = "ORDER BY harga DESC";
                elseif($_GET['sort'] == 'terbaru') $order = "ORDER BY id DESC";
            }
            
            $query = "
                SELECT produk.*, COALESCE(sc.stok, 0) AS stok 
                FROM produk 
                LEFT JOIN stok_cabang sc ON produk.id = sc.id_produk AND sc.id_cabang = '$customer_cabang_id'
                $where $order
            ";
            $ambil = mysqli_query($koneksi, $query);
            while($p = mysqli_fetch_assoc($ambil)): 
                $is_out_of_stock = ($p['stok'] !== null && $p['stok'] <= 0);
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card card-produk <?php echo $is_out_of_stock ? 'opacity-75' : ''; ?>">
                    <a href="detail.php?id=<?php echo $p['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="img-wrapper position-relative">
                            <img src="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '../assets/'.$p['foto']; ?>" alt="product">
                            <?php if ($is_out_of_stock): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">Stok Habis</span>
                            <?php elseif ($p['stok'] !== null && $p['stok'] <= 3): ?>
                                <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">Sisa <?php echo $p['stok']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="product-title"><?php echo $p['nama_produk']; ?></div>
                            <div class="d-flex justify-content-between align-items-end mt-2">
                                <div class="product-price">Rp<?php echo number_format($p['harga'], 0, ',', '.'); ?></div>
                                <?php if ($is_out_of_stock): ?>
                                    <button class="btn-add bg-secondary text-white" disabled onclick="event.preventDefault(); event.stopPropagation();">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-add btn-ajax" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); event.stopPropagation();">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>


    <!-- Floating Cart -->
    <a href="keranjang.php" class="cart-float">
        <i class="bi bi-bag-heart-fill"></i>
        <div id="badge-keranjang" class="badge-count">
            <?php echo isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0; ?>
        </div>
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function(){
        $('.btn-ajax').click(function(e){
            e.preventDefault();
            var idProduk = $(this).data('id');
            var btn = $(this);
            
            // Animation effect
            btn.css('transform', 'scale(1.2) rotate(45deg)');
            setTimeout(() => { btn.css('transform', 'scale(1) rotate(0)'); }, 300);

            $.ajax({
                url: 'tambah_ajax.php',
                type: 'POST',
                data: { id: idProduk },
                success: function(response){
                    $('#badge-keranjang').text(response).addClass('animate-bounce');
                }
            });
        });
    });
    </script>
</body>
</html>