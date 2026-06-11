<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .hero-about { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); color: white; padding: 80px 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-800 fs-3 text-danger" href="index.php">MERAH<span class="text-dark">PUTIH</span></a>
            <a href="index.php" class="btn btn-outline-danger rounded-pill">Kembali ke Toko</a>
        </div>
    </nav>

    <div class="hero-about text-center">
        <div class="container">
            <h1 class="fw-bold display-4">Tentang MerahPutih</h1>
            <p class="lead">Toko Online Terpercaya Sejak 2020</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h3 class="fw-bold mb-4">Siapa Kami?</h3>
                <p>MerahPutih adalah toko online yang menyediakan berbagai produk berkualitas premium dengan harga yang terjangkau. Kami berkomitmen untuk memberikan pengalaman belanja online terbaik bagi pelanggan kami.</p>
                
                <h4 class="fw-bold mt-5 mb-3">Visi Kami</h4>
                <p>Menjadi platform e-commerce terdepan yang menghadirkan produk berkualitas tinggi dengan layanan pelanggan yang luar biasa.</p>
                
                <h4 class="fw-bold mt-5 mb-3">Misi Kami</h4>
                <ul>
                    <li>Menyediakan produk berkualitas dengan harga kompetitif</li>
                    <li>Memberikan layanan pelanggan yang responsif dan profesional</li>
                    <li>Memastikan pengiriman cepat dan aman</li>
                    <li>Membangun kepercayaan jangka panjang dengan pelanggan</li>
                </ul>

                <h4 class="fw-bold mt-5 mb-3">Mengapa Memilih Kami?</h4>
                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <i class="bi bi-shield-check text-success fs-1 mb-3"></i>
                            <h5 class="fw-bold">Terpercaya</h5>
                            <p class="text-muted">Ribuan pelanggan puas telah berbelanja di toko kami.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <i class="bi bi-truck text-primary fs-1 mb-3"></i>
                            <h5 class="fw-bold">Pengiriman Cepat</h5>
                            <p class="text-muted">Kami memastikan pesanan Anda sampai dengan cepat dan aman.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <i class="bi bi-star-fill text-warning fs-1 mb-3"></i>
                            <h5 class="fw-bold">Kualitas Premium</h5>
                            <p class="text-muted">Semua produk kami dipilih dengan standar kualitas tinggi.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <i class="bi bi-headset text-danger fs-1 mb-3"></i>
                            <h5 class="fw-bold">Layanan 24/7</h5>
                            <p class="text-muted">Tim customer service kami siap membantu Anda kapan saja.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
</body>
</html>
