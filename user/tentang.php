<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - MerahPutih Marketplace</title>
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
        
        .btn-mp-outline { background: white; border: 1.5px solid #E2E2EC; color: #4A4A6A; font-weight: 700; border-radius: 12px; padding: 10px 20px; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-mp-outline:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }

        .hero-banner { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; padding: 64px 0; text-align: center; }
        .card-premium { background: white; border: none; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #F0F0F8; padding: 36px; }

        .about-section h3, .about-section h4 { font-weight: 700; color: #1A1A2E; }
        .about-section p { font-size: 0.92rem; color: #4A4A6A; line-height: 1.7; }
        .about-section ul { font-size: 0.92rem; color: #4A4A6A; line-height: 1.7; padding-left: 20px; }
        .about-section ul li { margin-bottom: 8px; }

        .info-card { background: white; border: none; border-radius: 20px; box-shadow: 0 6px 20px rgba(0,0,0,0.02); border: 1px solid #EAEAF2; padding: 24px; text-align: center; height: 100%; transition: all 0.2s; }
        .info-card:hover { transform: translateY(-3px); border-color: #FECDC8; box-shadow: 0 10px 30px rgba(192,57,43,0.05); }
        .info-card-icon { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; font-size: 1.5rem; }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-mp">
        <div class="container">
            <a class="logo-text" href="index.php">
                <i class="bi bi-shop text-danger"></i> MERAH<span>PUTIH</span>
            </a>
            <a href="index.php" class="btn-mp-outline">
                <i class="bi bi-arrow-left"></i> Kembali ke Toko
            </a>
        </div>
    </nav>

    <div class="hero-banner">
        <div class="container">
            <h2 class="fw-bold m-0" style="font-size: 2.25rem;">Tentang MerahPutih</h2>
            <p class="text-white-50 small mt-2 mb-0">Platform marketplace andalan dengan produk premium terpercaya sejak 2020</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card-premium about-section">
                    <h3 class="fw-bold mb-3"><i class="bi bi-info-circle text-danger me-2"></i>Siapa Kami?</h3>
                    <p>MerahPutih Marketplace adalah jaringan retail dan toko belanja online modern yang menyediakan produk-produk berkualitas premium dengan jaminan keaslian serta harga bersaing. Hadir sejak tahun 2020, kami fokus melayani kebutuhan jutaan keluarga Indonesia secara omnichannel baik offline melalui cabang fisik kami maupun online melalui web portal resmi ini.</p>
                    
                    <h4 class="fw-bold mt-5 mb-3"><i class="bi bi-eye text-danger me-2"></i>Visi Kami</h4>
                    <p>Menjadi ekosistem marketplace lokal paling terpercaya di Indonesia yang menghubungkan setiap kebutuhan masyarakat dengan distribusi barang berkualitas secara cepat, aman, dan efisien.</p>
                    
                    <h4 class="fw-bold mt-5 mb-3"><i class="bi bi-award text-danger me-2"></i>Misi Utama</h4>
                    <ul>
                        <li>Menyediakan pasokan produk-produk orisinil dengan kontrol kualitas standar tinggi.</li>
                        <li>Mengembangkan kemudahan layanan pelanggan dengan respon cepat dan tanggap.</li>
                        <li>Menyediakan pengiriman logistik andalan yang cepat dan terintegrasi dari cabang terdekat.</li>
                        <li>Membina kemitraan jangka panjang yang transparan dengan seluruh pelanggan.</li>
                    </ul>

                    <h4 class="fw-bold mt-5 mb-4 text-center">Kenapa Belanja di MerahPutih?</h4>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="info-card">
                                <div class="info-card-icon" style="background:#ECFDF5; color:#059669;"><i class="bi bi-shield-check"></i></div>
                                <h6 class="fw-bold text-dark mb-2">100% Produk Asli</h6>
                                <p class="text-secondary small m-0" style="line-height:1.5;">Seluruh produk yang kami tawarkan dipilih langsung dari distributor resmi dengan jaminan kualitas.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-card">
                                <div class="info-card-icon" style="background:#EFF6FF; color:#3B82F6;"><i class="bi bi-truck"></i></div>
                                <h6 class="fw-bold text-dark mb-2">Pengiriman Cabang</h6>
                                <p class="text-secondary small m-0" style="line-height:1.5;">Pengiriman diprioritaskan dari cabang terdekat di kota Anda untuk meminimalkan waktu dan biaya kirim.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-card">
                                <div class="info-card-icon" style="background:#FEF3C7; color:#D97706;"><i class="bi bi-chat-heart"></i></div>
                                <h6 class="fw-bold text-dark mb-2">Layanan Responsif</h6>
                                <p class="text-secondary small m-0" style="line-height:1.5;">Tim admin & customer support siap siaga membantu menyelesaikan kendala belanja Anda.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-card">
                                <div class="info-card-icon" style="background:#FEF2F2; color:#DC2626;"><i class="bi bi-cash-coin"></i></div>
                                <h6 class="fw-bold text-dark mb-2">Pilihan Pembayaran</h6>
                                <p class="text-secondary small m-0" style="line-height:1.5;">Mendukung transaksi COD (Cash on Delivery), Transfer Bank, dan sistem pembayaran digital aman.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
</body>
</html>
