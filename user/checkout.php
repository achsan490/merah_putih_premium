<?php 
include '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(empty($_SESSION['keranjang'])) { 
    header("location:index.php"); 
    exit;
}

$total_akhir = 0;
foreach($_SESSION['keranjang'] as $id_kemasan => $jml) {
    $res = mysqli_query($koneksi, "
        SELECT pk.*, p.harga_grosir, p.min_qty_grosir 
        FROM produk_kemasan pk 
        JOIN produk p ON pk.id_produk = p.id 
        WHERE pk.id_kemasan='$id_kemasan'
    ");
    if($p = mysqli_fetch_assoc($res)) {
        $harga_satuan = $p['harga'];
        // Apply wholesale price if unit is 'Pcs' and quantity meets minimum
        if($p['nama_satuan'] == 'Pcs' && $p['harga_grosir'] && $p['harga_grosir'] > 0 && $jml >= $p['min_qty_grosir']) {
            $harga_satuan = $p['harga_grosir'];
        }
        $total_akhir += ($harga_satuan * $jml);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MerahPutih Marketplace</title>
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

        .card-premium { background: white; border: none; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); border: 1px solid #F0F0F8; padding: 36px; }
        
        .form-control, .form-select { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 12px 16px; font-size: 0.88rem; transition: border-color 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #4A4A6A; margin-bottom: 6px; }

        .pm-card { border: 2px solid #EAEAF2; border-radius: 16px; padding: 16px 20px; transition: all 0.25s ease; cursor: pointer; background: #F8F9FD; display: flex; align-items: center; margin-bottom: 12px; }
        .pm-card:hover { border-color: #FECDC8; transform: translateY(-1px); }
        .pm-card.active { border-color: #C0392B; background: white; box-shadow: 0 6px 20px rgba(192,57,43,0.04); }

        .btn-mp-primary { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; font-weight: 700; border-radius: 12px; padding: 14px 28px; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; transition: all 0.2s; }
        .btn-mp-primary:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-mp-danger { background: linear-gradient(135deg, #922B21 0%, #E74C3C 100%); color: white; }

        .summary-total { background: #F0F4FF; border: 1px solid #E2EAF8; border-radius: 16px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
        .summary-total .total-label { font-weight: 700; color: #4A4A6A; font-size: 0.9rem; }
        .summary-total .total-val { font-size: 1.3rem; font-weight: 800; color: #C0392B; }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-mp">
        <div class="container">
            <a class="logo-text" href="index.php">
                <i class="bi bi-shop text-danger"></i> MERAH<span>PUTIH</span>
            </a>
            <a href="keranjang.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card-premium">
                    <div class="text-center mb-4 pb-2">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF0EE; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                            <i class="bi bi-geo-alt fs-3 text-danger"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Informasi Pengiriman</h3>
                        <p class="text-secondary small">Lengkapi data di bawah ini untuk memproses pesanan Anda</p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" name="nama" class="form-control" placeholder="Tuliskan nama lengkap Anda" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="number" name="hp" class="form-control" placeholder="Contoh: 08123456789" required autocomplete="off">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Alamat Lengkap Pengiriman</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Tuliskan alamat lengkap beserta detail (RT/RW, No. Rumah)" required></textarea>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark d-block mb-3">Pilih Metode Pembayaran</label>
                            <?php 
                            // Auto-create payment_methods table if missing
                            $check_pm = mysqli_query($koneksi, "SHOW TABLES LIKE 'payment_methods'");
                            if(mysqli_num_rows($check_pm) == 0) {
                                mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS payment_methods (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    nama_metode VARCHAR(50) NOT NULL,
                                    kode VARCHAR(20) NOT NULL,
                                    deskripsi TEXT,
                                    detail_pembayaran TEXT,
                                    icon VARCHAR(50),
                                    is_active TINYINT(1) DEFAULT 1,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                )");
                                mysqli_query($koneksi, "INSERT INTO payment_methods (nama_metode, kode, deskripsi, detail_pembayaran, icon) VALUES 
                                    ('Cash on Delivery (COD)', 'cod', 'Bayar saat barang diterima', 'Pembayaran dilakukan saat barang sampai di tangan Anda.', 'bi-cash-coin'),
                                    ('Transfer Bank', 'transfer', 'Transfer ke rekening bank', 'Bank BCA: 1234567890 a.n. MerahPutih Store', 'bi-bank'),
                                    ('QRIS', 'qris', 'Scan QR Code untuk bayar', 'https://via.placeholder.com/300x300?text=QRIS+Code', 'bi-qr-code')");
                            }
                            
                            $methods = mysqli_query($koneksi, "SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id ASC");
                            $first = true;
                            while($pm = mysqli_fetch_assoc($methods)): 
                            ?>
                            <label class="pm-card w-100 <?php echo $first ? 'active' : ''; ?>" for="pm<?php echo $pm['id']; ?>">
                                <input class="form-check-input me-3" style="cursor:pointer;" type="radio" name="metode_bayar" id="pm<?php echo $pm['id']; ?>" value="<?php echo $pm['kode']; ?>" <?php echo $first ? 'checked' : ''; ?> required onclick="selectPaymentCard(this)">
                                <div class="d-flex align-items-center">
                                    <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(192,57,43,0.06); display: flex; align-items: center; justify-content: center; margin-right: 14px;">
                                        <i class="bi <?php echo $pm['icon']; ?> fs-4 text-danger"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark" style="font-size:0.9rem;"><?php echo htmlspecialchars($pm['nama_metode']); ?></strong>
                                        <span style="font-size:0.75rem; color:#8A8AA0;"><?php echo htmlspecialchars($pm['deskripsi']); ?></span>
                                    </div>
                                </div>
                            </label>
                            <?php 
                            $first = false;
                            endwhile; 
                            ?>
                        </div>

                        <div class="summary-total mb-4">
                            <span class="total-label">Total Tagihan:</span>
                            <span class="total-val">Rp <?php echo number_format($total_akhir, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" name="proses" class="btn-mp-primary btn-mp-danger w-100 py-3 shadow">
                            <i class="bi bi-wallet2"></i> Buat Pesanan Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectPaymentCard(radio) {
            // Remove active class from all cards
            document.querySelectorAll('.pm-card').forEach(card => {
                card.classList.remove('active');
            });
            // Add active class to selected card
            radio.closest('.pm-card').classList.add('active');
        }
    </script>

    <?php 
    if(isset($_POST['proses'])) {
        // Auto-add metode_bayar column if missing
        $check_col_mb = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'metode_bayar'");
        if(mysqli_num_rows($check_col_mb) == 0) {
            mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN metode_bayar VARCHAR(20) DEFAULT 'cod'");
        }
        
        // Get selected customer branch
        $customer_cabang_id = isset($_SESSION['customer_cabang_id']) ? (int)$_SESSION['customer_cabang_id'] : 1;

        // Validate stock first based on UoM factors
        $stock_ok = true;
        $insufficient_items = [];
        foreach($_SESSION['keranjang'] as $id_kemasan => $jumlah) {
            $p_check = mysqli_fetch_assoc(mysqli_query($koneksi, "
                SELECT pk.nama_satuan, pk.faktor_kali, p.nama_produk, COALESCE(sc.stok, 0) AS stok 
                FROM produk_kemasan pk
                JOIN produk p ON pk.id_produk = p.id
                LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = '$customer_cabang_id'
                WHERE pk.id_kemasan='$id_kemasan'
            "));
            if($p_check) {
                $total_pcs_required = $jumlah * $p_check['faktor_kali'];
                if($p_check['stok'] < $total_pcs_required) {
                    $stock_ok = false;
                    $insufficient_items[] = $p_check['nama_produk'] . " (" . $p_check['nama_satuan'] . ", sisa stok: " . floor($p_check['stok'] / $p_check['faktor_kali']) . ")";
                }
            }
        }

        if(!$stock_ok) {
            $msg = "Maaf, pesanan gagal diproses karena sisa stok tidak mencukupi:\\n" . implode("\\n", $insufficient_items);
            echo "<script>alert('$msg'); window.location='keranjang.php';</script>";
            exit;
        }

        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $hp = mysqli_real_escape_string($koneksi, $_POST['hp']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $metode = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
        $tgl = date("Y-m-d H:i:s");

        $q = mysqli_query($koneksi, "INSERT INTO pesanan (nama_penerima, no_telp, alamat_penerima, total_bayar, metode_bayar, status, tgl_pesan, tipe_pesanan, id_cabang) 
                                     VALUES ('$nama', '$hp', '$alamat', '$total_akhir', '$metode', 'pending', '$tgl', 'online', '$customer_cabang_id')");
        
        if($q) {
            $id_p = mysqli_insert_id($koneksi);
            foreach($_SESSION['keranjang'] as $id_kemasan => $jumlah) {
                $p_data = mysqli_fetch_assoc(mysqli_query($koneksi, "
                    SELECT pk.*, p.harga_grosir, p.min_qty_grosir, p.id AS id_produk 
                    FROM produk_kemasan pk 
                    JOIN produk p ON pk.id_produk = p.id 
                    WHERE pk.id_kemasan='$id_kemasan'
                "));
                $id_produk = $p_data['id_produk'];
                $faktor_kali = $p_data['faktor_kali'];
                $total_pcs = $jumlah * $faktor_kali;
                
                $harga_satuan = $p_data['harga'];
                if($p_data['nama_satuan'] == 'Pcs' && $p_data['harga_grosir'] && $p_data['harga_grosir'] > 0 && $jumlah >= $p_data['min_qty_grosir']) {
                    $harga_satuan = $p_data['harga_grosir'];
                }
                $sub = $harga_satuan * $jumlah;
                mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, subtotal, id_kemasan) VALUES ('$id_p', '$id_produk', '$jumlah', '$sub', '$id_kemasan')");
                
                // Deduct stock for the specific branch (in base unit pcs)
                mysqli_query($koneksi, "
                    INSERT INTO stok_cabang (id_produk, id_cabang, stok) 
                    VALUES ('$id_produk', '$customer_cabang_id', 0)
                    ON DUPLICATE KEY UPDATE stok = stok - $total_pcs
                ");
                
                // If customer selected branch is Pusat (ID 1), also deduct from the main produk table (in base unit pcs)
                if ($customer_cabang_id == 1) {
                    mysqli_query($koneksi, "UPDATE produk SET stok = stok - $total_pcs WHERE id = '$id_produk'");
                }
            }
            unset($_SESSION['keranjang']);
            echo "<script>window.location='sukses.php?id=$id_p';</script>";
        }
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>