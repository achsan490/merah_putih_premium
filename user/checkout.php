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
foreach($_SESSION['keranjang'] as $id => $jml) {
    $res = mysqli_query($koneksi, "SELECT harga, harga_grosir, min_qty_grosir FROM produk WHERE id='$id'");
    if($p = mysqli_fetch_assoc($res)) {
        $harga_satuan = $p['harga'];
        // Apply wholesale price if quantity meets minimum
        if($p['harga_grosir'] && $p['harga_grosir'] > 0 && $jml >= $p['min_qty_grosir']) {
            $harga_satuan = $p['harga_grosir'];
        }
        $total_akhir += ($harga_satuan * $jml);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .bg-grad-merah { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); color: white; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-control { border-radius: 12px; padding: 12px 20px; border: 1px solid #eee; }
        .form-control:focus { box-shadow: none; border-color: #e63946; }
        .btn-pesan { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); border: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-grad-merah sticky-top">
        <div class="container">
            <a class="navbar-brand fw-800" href="keranjang.php"><i class="bi bi-arrow-left-short"></i> KONFIRMASI</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card card-premium bg-white p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h3 class="fw-800 text-danger">Data Pengiriman</h3>
                        <p class="text-muted small">Isi data dengan benar ya, sayang.</p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small mb-2">Nama Penerima</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small mb-2">Nomor WhatsApp</label>
                            <input type="number" name="hp" class="form-control" placeholder="08xxxx" required>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold small mb-2">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat pengiriman..." required></textarea>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <label class="fw-bold mb-3">Pilih Metode Pembayaran</label>
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
                            <div class="form-check mb-3 p-3 border rounded-3" style="cursor: pointer;">
                                <input class="form-check-input" type="radio" name="metode_bayar" id="pm<?php echo $pm['id']; ?>" value="<?php echo $pm['kode']; ?>" <?php echo $first ? 'checked' : ''; ?> required>
                                <label class="form-check-label w-100" for="pm<?php echo $pm['id']; ?>" style="cursor: pointer;">
                                    <div class="d-flex align-items-center">
                                        <i class="<?php echo $pm['icon']; ?> fs-4 me-3 text-danger"></i>
                                        <div>
                                            <strong><?php echo $pm['nama_metode']; ?></strong>
                                            <br><small class="text-muted"><?php echo $pm['deskripsi']; ?></small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php 
                            $first = false;
                            endwhile; 
                            ?>
                        </div>

                        <div class="bg-light p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold">Total Bayar:</span>
                            <span class="h5 fw-800 text-danger mb-0">Rp <?php echo number_format($total_akhir); ?></span>
                        </div>

                        <button name="proses" class="btn btn-pesan text-white w-100 py-3 rounded-pill fw-bold shadow">
                            PESAN SEKARANG <i class="bi bi-send-fill ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php 
    if(isset($_POST['proses'])) {
        // Auto-add metode_bayar column if missing
        $check_col_mb = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'metode_bayar'");
        if(mysqli_num_rows($check_col_mb) == 0) {
            mysqli_query($koneksi, "ALTER TABLE pesanan ADD COLUMN metode_bayar VARCHAR(20) DEFAULT 'cod'");
        }
        
        // Get selected customer branch
        $customer_cabang_id = isset($_SESSION['customer_cabang_id']) ? (int)$_SESSION['customer_cabang_id'] : 1;

        // Validate stock first
        $stock_ok = true;
        $insufficient_items = [];
        foreach($_SESSION['keranjang'] as $id_produk => $jumlah) {
            $p_check = mysqli_fetch_assoc(mysqli_query($koneksi, "
                SELECT p.nama_produk, COALESCE(sc.stok, 0) AS stok 
                FROM produk p
                LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = '$customer_cabang_id'
                WHERE p.id='$id_produk'
            "));
            if($p_check) {
                if($p_check['stok'] < $jumlah) {
                    $stock_ok = false;
                    $insufficient_items[] = $p_check['nama_produk'] . " (Sisa stok: " . $p_check['stok'] . ")";
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
            foreach($_SESSION['keranjang'] as $id_produk => $jumlah) {
                $p_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga, harga_grosir, min_qty_grosir FROM produk WHERE id='$id_produk'"));
                $harga_satuan = $p_data['harga'];
                if($p_data['harga_grosir'] && $p_data['harga_grosir'] > 0 && $jumlah >= $p_data['min_qty_grosir']) {
                    $harga_satuan = $p_data['harga_grosir'];
                }
                $sub = $harga_satuan * $jumlah;
                mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, subtotal) VALUES ('$id_p', '$id_produk', '$jumlah', '$sub')");
                
                // Deduct stock for the specific branch
                mysqli_query($koneksi, "
                    INSERT INTO stok_cabang (id_produk, id_cabang, stok) 
                    VALUES ('$id_produk', '$customer_cabang_id', 0)
                    ON DUPLICATE KEY UPDATE stok = stok - $jumlah
                ");
                
                // If customer selected branch is Pusat (ID 1), also deduct from the main produk table
                if ($customer_cabang_id == 1) {
                    mysqli_query($koneksi, "UPDATE produk SET stok = stok - $jumlah WHERE id = '$id_produk'");
                }
            }
            unset($_SESSION['keranjang']);
            echo "<script>window.location='sukses.php?id=$id_p';</script>";
        }
    }
    ?>
</body>
</html>