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

// Count total products for stats
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk");
$d_total = mysqli_fetch_assoc($q_total);
$total_produk = $d_total['total'];

// Get categories with count
$categories_list = [];
$cat_res = mysqli_query($koneksi, "SELECT c.*, COUNT(p.id) as jml FROM categories c LEFT JOIN produk p ON p.kategori_id = c.id GROUP BY c.id ORDER BY c.id ASC");
while($cat = mysqli_fetch_assoc($cat_res)) {
    $categories_list[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="MerahPutih - Marketplace premium dengan produk berkualitas pilihan terbaik dengan harga terjangkau.">
    <title>MerahPutih – Premium Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #C0392B;
            --primary-dark: #922B21;
            --primary-light: #E74C3C;
            --navy: #1A1A2E;
            --accent: #FF6B35;
            --bg: #F8F9FF;
            --white: #FFFFFF;
            --text: #1E1E2E;
            --text-sub: #6B7280;
            --border: #E5E7EB;
            --card-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ── NAVBAR ── */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 0 5%;
            height: 68px;
            display: flex; align-items: center; justify-content: space-between;
            transition: box-shadow 0.3s;
        }
        .navbar.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,0.1); }

        .nav-brand {
            font-size: 1.5rem; font-weight: 800;
            letter-spacing: -0.5px; color: var(--navy);
            text-decoration: none;
            display: flex; align-items: center; gap: 3px;
        }
        .nav-brand .dot { color: var(--primary); }

        .nav-actions { display: flex; align-items: center; gap: 10px; }

        .nav-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.83rem; font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .nav-btn.outline { border: 1.5px solid var(--border); color: var(--text); background: transparent; }
        .nav-btn.outline:hover { border-color: var(--primary); color: var(--primary); background: #FEF0EE; }
        .nav-btn.solid { background: var(--primary); color: white; border: 1.5px solid var(--primary); }
        .nav-btn.solid:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .nav-btn.dark { background: var(--navy); color: white; border: 1.5px solid var(--navy); }
        .nav-btn.dark:hover { background: #0F3460; }

        /* Dropdown */
        .nav-dropdown { position: relative; }
        .dropdown-menu-custom {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: white; border: 1px solid var(--border);
            border-radius: 16px; padding: 8px;
            min-width: 220px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            display: none; z-index: 100;
        }
        .nav-dropdown:hover .dropdown-menu-custom { display: block; }
        .dropdown-menu-custom .dd-header {
            padding: 8px 12px 6px;
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: #9CA3AF;
        }
        .dropdown-menu-custom a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            font-size: 0.85rem; font-weight: 500;
            color: var(--text); text-decoration: none;
            transition: all 0.15s;
        }
        .dropdown-menu-custom a:hover { background: #F8F8FF; color: var(--primary); }
        .dropdown-menu-custom a.active { background: #FEF0EE; color: var(--primary); font-weight: 600; }

        /* ── HERO ── */
        .hero {
            margin-top: 68px;
            min-height: 520px;
            background: linear-gradient(135deg, #1A1A2E 0%, #16213E 40%, #922B21 100%);
            display: flex; align-items: center;
            padding: 80px 5% 120px;
            position: relative; overflow: hidden;
        }
        .hero-bg-shape {
            position: absolute;
            border-radius: 50%;
            animation: floatShape 10s ease-in-out infinite;
        }
        .hero-bg-shape.s1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(192,57,43,0.3), transparent);
            top: -150px; right: -100px;
        }
        .hero-bg-shape.s2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,107,53,0.2), transparent);
            bottom: -80px; left: 30%;
            animation-delay: 3s;
        }
        @keyframes floatShape {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(20px, -20px) scale(1.05); }
        }

        .hero-content { position: relative; z-index: 1; max-width: 600px; }
        .hero-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 6px 16px;
            font-size: 0.78rem; font-weight: 600;
            color: rgba(255,255,255,0.85);
            letter-spacing: 1px; text-transform: uppercase;
            margin-bottom: 24px;
        }
        .hero-tag .tag-dot { width: 7px; height: 7px; background: var(--accent); border-radius: 50%; }

        .hero h1 {
            font-size: 3.2rem; font-weight: 800;
            color: white; line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 18px;
        }
        .hero h1 .highlight {
            color: transparent;
            background: linear-gradient(90deg, #FF6B35, #FFB347);
            -webkit-background-clip: text;
            background-clip: text;
        }
        .hero p {
            color: rgba(255,255,255,0.7);
            font-size: 1.05rem; font-weight: 400;
            line-height: 1.7; margin-bottom: 32px;
        }
        .hero-cta { display: flex; gap: 12px; flex-wrap: wrap; }
        .hero-cta .cta-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 14px 28px; border-radius: 50px;
            font-size: 0.92rem; font-weight: 700;
            text-decoration: none; transition: all 0.3s;
        }
        .hero-cta .cta-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; border: none;
            box-shadow: 0 8px 25px rgba(192,57,43,0.4);
        }
        .hero-cta .cta-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 35px rgba(192,57,43,0.5); }
        .hero-cta .cta-secondary {
            background: rgba(255,255,255,0.12);
            color: white; border: 1.5px solid rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
        }
        .hero-cta .cta-secondary:hover { background: rgba(255,255,255,0.2); }

        /* Hero stats */
        .hero-stats {
            position: absolute; bottom: 0; right: 5%;
            display: flex; gap: 2px;
            transform: translateY(50%);
            z-index: 10;
        }
        .stat-pill {
            background: white;
            border-radius: 16px; padding: 16px 22px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            text-align: center; min-width: 130px;
        }
        .stat-pill .stat-num { font-size: 1.5rem; font-weight: 800; color: var(--navy); }
        .stat-pill .stat-label { font-size: 0.72rem; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        /* ── SEARCH BAR ── */
        .search-section {
            padding: 80px 5% 30px;
            display: flex; align-items: center; justify-content: center;
        }
        .search-box {
            width: 100%; max-width: 640px;
            background: white;
            border-radius: 60px;
            padding: 8px 8px 8px 24px;
            display: flex; align-items: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 8px 40px rgba(192,57,43,0.15);
        }
        .search-box i { color: #9CA3AF; font-size: 1.1rem; flex-shrink: 0; }
        .search-box input {
            flex: 1; border: none; outline: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem; color: var(--text);
            background: transparent; padding: 8px 12px;
        }
        .search-box input::placeholder { color: #9CA3AF; }
        .search-btn {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: white; border: none;
            border-radius: 50px; padding: 12px 24px;
            font-size: 0.88rem; font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .search-btn:hover { transform: scale(1.03); box-shadow: 0 4px 15px rgba(192,57,43,0.3); }

        /* ── CATEGORY FILTER ── */
        .category-section { padding: 0 5% 24px; }
        .cat-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
        .cat-scroll::-webkit-scrollbar { display: none; }
        .cat-chip {
            display: flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 50px;
            font-size: 0.82rem; font-weight: 600;
            white-space: nowrap; text-decoration: none;
            transition: all 0.2s; flex-shrink: 0;
        }
        .cat-chip.inactive {
            background: white; color: var(--text-sub);
            border: 1.5px solid var(--border);
        }
        .cat-chip.inactive:hover { border-color: var(--primary); color: var(--primary); background: #FEF0EE; }
        .cat-chip.active {
            background: var(--primary); color: white;
            border: 1.5px solid var(--primary);
            box-shadow: 0 4px 15px rgba(192,57,43,0.3);
        }

        .sort-select {
            padding: 9px 16px; border-radius: 10px;
            border: 1.5px solid var(--border);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.82rem; font-weight: 600; color: var(--text);
            background: white; cursor: pointer; outline: none;
            margin-left: auto; flex-shrink: 0;
        }
        .sort-select:focus { border-color: var(--primary); }

        /* ── PRODUCT GRID ── */
        .products-section { padding: 0 5% 80px; }
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-header h2 { font-size: 1.25rem; font-weight: 800; color: var(--navy); }
        .section-header span { font-size: 0.8rem; color: #9CA3AF; font-weight: 500; }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: var(--card-shadow);
            position: relative;
            cursor: pointer;
        }
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
        .product-card:hover .prod-img { transform: scale(1.07); }

        .prod-img-wrapper {
            height: 200px; overflow: hidden;
            background: #F8F8FF;
            position: relative;
        }
        .prod-img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.5s ease;
        }
        .prod-img-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: #D1D5DB; font-size: 2.5rem;
            background: linear-gradient(135deg, #F8F8FF, #F0F0FF);
        }

        .prod-badge {
            position: absolute; top: 10px; left: 10px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 700;
            letter-spacing: 0.3px;
        }
        .prod-badge.habis { background: #EF4444; color: white; }
        .prod-badge.sisa { background: #F59E0B; color: white; }
        .prod-badge.baru { background: var(--navy); color: white; }

        .prod-info { padding: 16px; }
        .prod-cat {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--primary); margin-bottom: 6px;
        }
        .prod-name {
            font-size: 0.92rem; font-weight: 700;
            color: var(--text); line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
            height: 2.6em; margin-bottom: 12px;
        }
        .prod-footer {
            display: flex; align-items: center; justify-content: space-between;
        }
        .prod-price {
            font-size: 1.05rem; font-weight: 800;
            color: var(--primary);
        }
        .btn-add-cart {
            width: 36px; height: 36px; border-radius: 50%;
            background: #FEF0EE; color: var(--primary);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; transition: all 0.25s;
        }
        .btn-add-cart:hover { background: var(--primary); color: white; transform: scale(1.1); }
        .btn-add-cart:disabled { background: #F3F4F6; color: #9CA3AF; cursor: not-allowed; }
        .btn-add-cart:disabled:hover { transform: none; }

        /* ── FLOAT CART ── */
        .cart-float {
            position: fixed; bottom: 28px; right: 28px;
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; text-decoration: none; z-index: 500;
            box-shadow: 0 8px 30px rgba(192,57,43,0.4);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .cart-float:hover { transform: scale(1.1) rotate(-8deg); box-shadow: 0 12px 40px rgba(192,57,43,0.5); color: white; }
        .cart-float .cart-count {
            position: absolute; top: -4px; right: -4px;
            background: white; color: var(--primary);
            width: 22px; height: 22px; border-radius: 50%;
            font-size: 0.7rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--primary);
        }

        /* ── FOOTER ── */
        .site-footer {
            background: var(--navy);
            color: rgba(255,255,255,0.7);
            padding: 48px 5% 28px;
        }
        .footer-brand { font-size: 1.4rem; font-weight: 800; color: white; margin-bottom: 8px; }
        .footer-brand span { color: var(--accent); }
        .footer-desc { font-size: 0.85rem; line-height: 1.7; max-width: 280px; }
        .footer-links h5 { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4); margin-bottom: 14px; }
        .footer-links a { display: block; color: rgba(255,255,255,0.65); font-size: 0.85rem; text-decoration: none; margin-bottom: 10px; transition: color 0.2s; }
        .footer-links a:hover { color: white; }
        .footer-bottom { margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.8rem; text-align: center; }

        /* ── EMPTY STATE ── */
        .empty-state { grid-column: 1/-1; text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: #D1D5DB; display: block; margin-bottom: 16px; }
        .empty-state p { color: #9CA3AF; font-size: 0.95rem; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .hero-stats { display: none; }
            .hero { padding-bottom: 60px; }
            .search-section { padding-top: 40px; }
            .products-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .nav-actions .nav-btn:nth-child(-n+2) { display: none; }
        }
        @media (max-width: 480px) {
            .products-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar" id="mainNavbar">
        <a href="index.php" class="nav-brand">MERAH<span class="dot">·</span>PUTIH</a>
        <div class="nav-actions">
            <!-- Branch Selector -->
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <div class="nav-btn outline" style="cursor:default;">
                    <i class="bi bi-lock-fill" style="color: var(--primary); font-size: 0.8rem;"></i>
                    <?php echo htmlspecialchars($customer_cabang_nama); ?>
                </div>
            <?php else: ?>
                <div class="nav-dropdown">
                    <button class="nav-btn outline" style="cursor: pointer; background: none; font-family: inherit; border: 1.5px solid #E5E7EB; border-radius: 50px;">
                        <i class="bi bi-geo-alt-fill" style="color: var(--primary); font-size: 0.8rem;"></i>
                        <?php echo htmlspecialchars(mb_strimwidth($customer_cabang_nama, 0, 22, '...')); ?>
                        <i class="bi bi-chevron-down" style="font-size: 0.65rem;"></i>
                    </button>
                    <div class="dropdown-menu-custom">
                        <div class="dd-header">Pilih Cabang Belanja</div>
                        <?php
                        $q_all_cabang = mysqli_query($koneksi, "SELECT * FROM cabang ORDER BY id_cabang ASC");
                        while($cab = mysqli_fetch_assoc($q_all_cabang)):
                        ?>
                            <a href="index.php?set_cabang=<?php echo $cab['id_cabang']; ?><?php echo isset($_GET['kategori']) ? '&kategori='.(int)$_GET['kategori'] : ''; ?>"
                               class="<?php echo ($customer_cabang_id == $cab['id_cabang']) ? 'active' : ''; ?>">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo htmlspecialchars($cab['nama_cabang']); ?>
                                <?php if($customer_cabang_id == $cab['id_cabang']): ?>
                                    <i class="bi bi-check-circle-fill" style="margin-left: auto; color: var(--primary);"></i>
                                <?php endif; ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="cek_pesanan.php" class="nav-btn outline">
                <i class="bi bi-search" style="font-size: 0.85rem;"></i>
                Lacak Pesanan
            </a>

            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <a href="../admin/index.php" class="nav-btn dark">
                    <i class="bi bi-speedometer2" style="font-size: 0.85rem;"></i>
                    Dashboard
                </a>
                <a href="../admin/logout.php" class="nav-btn outline">
                    <i class="bi bi-box-arrow-right" style="font-size: 0.85rem;"></i>
                    Logout
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="hero">
        <div class="hero-bg-shape s1"></div>
        <div class="hero-bg-shape s2"></div>
        <div class="hero-content">
            <div class="hero-tag">
                <span class="tag-dot"></span>
                Marketplace Premium Indonesia
            </div>
            <h1>Belanja <span class="highlight">Lebih Cerdas</span> Setiap Hari</h1>
            <p>Temukan ribuan produk berkualitas tinggi dari berbagai kategori. Pengiriman cepat ke seluruh Indonesia.</p>
            <div class="hero-cta">
                <a href="#products" class="cta-btn cta-primary" onclick="document.getElementById('products').scrollIntoView({behavior:'smooth'}); return false;">
                    <i class="bi bi-bag-fill"></i>
                    Mulai Belanja
                </a>
                <a href="cek_pesanan.php" class="cta-btn cta-secondary">
                    <i class="bi bi-search"></i>
                    Lacak Pesanan
                </a>
            </div>
        </div>

        <!-- Stats Pills -->
        <div class="hero-stats">
            <div class="stat-pill">
                <div class="stat-num"><?php echo $total_produk; ?>+</div>
                <div class="stat-label">Produk</div>
            </div>
            <div class="stat-pill" style="border-radius: 16px 0 0 16px;">
                <div class="stat-num">3</div>
                <div class="stat-label">Cabang</div>
            </div>
            <div class="stat-pill" style="border-radius: 0 16px 16px 0;">
                <div class="stat-num">100%</div>
                <div class="stat-label">Original</div>
            </div>
        </div>
    </section>

    <!-- SEARCH -->
    <section class="search-section">
        <form action="index.php" method="GET" style="width: 100%; max-width: 640px;">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="cari" placeholder="Cari produk yang kamu inginkan..."
                    value="<?php echo htmlspecialchars($keyword); ?>">
                <button class="search-btn" type="submit">
                    Cari <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </section>

    <!-- CATEGORIES -->
    <section class="category-section">
        <div class="cat-scroll">
            <a href="index.php<?php echo $keyword ? '?cari='.urlencode($keyword) : ''; ?>"
               class="cat-chip <?php echo !isset($_GET['kategori']) ? 'active' : 'inactive'; ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i> Semua
            </a>
            <?php foreach($categories_list as $cat): ?>
            <a href="index.php?kategori=<?php echo $cat['id']; ?><?php echo $keyword ? '&cari='.urlencode($keyword) : ''; ?>"
               class="cat-chip <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == $cat['id']) ? 'active' : 'inactive'; ?>">
                <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                <span style="font-size: 0.7rem; opacity: 0.7;">(<?php echo $cat['jml']; ?>)</span>
            </a>
            <?php endforeach; ?>
            <select class="sort-select" onchange="window.location.href='index.php?sort=' + this.value + '<?php echo isset($_GET['kategori']) ? '&kategori='.$_GET['kategori'] : ''; ?><?php echo $keyword ? '&cari='.urlencode($keyword) : ''; ?>'">
                <option value="">⇅ Urutkan</option>
                <option value="terbaru" <?php echo (isset($_GET['sort']) && $_GET['sort']=='terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                <option value="termurah" <?php echo (isset($_GET['sort']) && $_GET['sort']=='termurah') ? 'selected' : ''; ?>>Harga Termurah</option>
                <option value="termahal" <?php echo (isset($_GET['sort']) && $_GET['sort']=='termahal') ? 'selected' : ''; ?>>Harga Termahal</option>
            </select>
        </div>
    </section>

    <!-- PRODUCTS -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2>
                <?php echo $keyword ? "Hasil Pencarian: \"" . htmlspecialchars($keyword) . "\"" : "Produk Pilihan"; ?>
            </h2>
            <span>Diperbarui hari ini</span>
        </div>
        <div class="products-grid">
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
                SELECT produk.*, COALESCE(sc.stok, 0) AS stok, c.nama_kategori
                FROM produk 
                LEFT JOIN stok_cabang sc ON produk.id = sc.id_produk AND sc.id_cabang = '$customer_cabang_id'
                LEFT JOIN categories c ON produk.kategori_id = c.id
                $where $order
            ";
            $ambil = mysqli_query($koneksi, $query);
            $count = 0;
            while($p = mysqli_fetch_assoc($ambil)): 
                $is_out = ($p['stok'] !== null && $p['stok'] <= 0);
                $is_low = (!$is_out && $p['stok'] !== null && $p['stok'] <= 3);
                $count++;
            ?>
            <div class="product-card <?php echo $is_out ? 'opacity-50' : ''; ?>">
                <a href="detail.php?id=<?php echo $p['id']; ?>" style="text-decoration: none;">
                    <div class="prod-img-wrapper">
                        <?php if($p['foto']): ?>
                            <img src="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '../assets/'.$p['foto']; ?>" 
                                 alt="<?php echo htmlspecialchars($p['nama_produk']); ?>"
                                 class="prod-img"
                                 onerror="this.parentNode.innerHTML='<div class=\'prod-img-placeholder\'><i class=\'bi bi-image\'></i></div>'">
                        <?php else: ?>
                            <div class="prod-img-placeholder"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                        
                        <?php if($is_out): ?>
                            <span class="prod-badge habis">Stok Habis</span>
                        <?php elseif($is_low): ?>
                            <span class="prod-badge sisa">Sisa <?php echo $p['stok']; ?></span>
                        <?php elseif($count <= 4): ?>
                            <span class="prod-badge baru">Baru</span>
                        <?php endif; ?>
                    </div>
                    <div class="prod-info">
                        <?php if($p['nama_kategori']): ?>
                        <div class="prod-cat"><?php echo htmlspecialchars($p['nama_kategori']); ?></div>
                        <?php endif; ?>
                        <div class="prod-name"><?php echo htmlspecialchars($p['nama_produk']); ?></div>
                        <div class="prod-footer">
                            <div class="prod-price">Rp<?php echo number_format($p['harga'], 0, ',', '.'); ?></div>
                            <?php if($is_out): ?>
                                <button class="btn-add-cart" disabled onclick="event.preventDefault()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-add-cart btn-ajax" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); event.stopPropagation();">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
            
            <?php if($count == 0): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <p>Tidak ada produk ditemukan.<br>Coba kata kunci lain atau pilih kategori berbeda.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include '../components/footer.php'; ?>

    <!-- FLOAT CART -->
    <a href="keranjang.php" class="cart-float" id="cartFloat">
        <i class="bi bi-bag-heart-fill"></i>
        <div class="cart-count" id="badge-keranjang">
            <?php echo isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0; ?>
        </div>
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNavbar');
        if (window.scrollY > 50) { nav.classList.add('scrolled'); }
        else { nav.classList.remove('scrolled'); }
    });

    // Add to cart AJAX
    $(document).ready(function(){
        $('.btn-ajax').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var idProduk = $(this).data('id');
            var btn = $(this);
            
            btn.css('transform', 'scale(1.3)');
            btn.html('<i class="bi bi-check2"></i>');
            setTimeout(() => {
                btn.css('transform', 'scale(1)');
                btn.html('<i class="bi bi-plus-lg"></i>');
            }, 500);

            $.ajax({
                url: 'tambah_ajax.php',
                type: 'POST',
                data: { id: idProduk },
                success: function(response){
                    const badge = $('#badge-keranjang');
                    badge.text(response);
                    badge.css({ transform: 'scale(1.5)', background: '#FF6B35' });
                    setTimeout(() => {
                        badge.css({ transform: 'scale(1)', background: '' });
                    }, 300);
                }
            });
        });
    });
    </script>
</body>
</html>