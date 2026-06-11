<?php 
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Produk - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 260px; padding: 40px; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="card card-premium p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">Manajemen Produk</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahStok">
                        <i class="bi bi-box-arrow-in-down"></i> Tambah Stok (Persediaan)
                    </button>
                    <button class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#tambah">
                        <i class="bi bi-plus-lg"></i> Produk Baru
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th><th>Nama Produk</th><th>Barcode</th><th>Harga</th><th>Stok</th><th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY id DESC");
                        while($p = mysqli_fetch_assoc($res)): 
                        ?>
                        <tr>
                            <td><img src="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '../assets/'.$p['foto']; ?>" width="60" class="rounded shadow-sm"></td>
                            <td class="fw-semibold"><?php echo $p['nama_produk']; ?></td>
                            <td class="font-monospace small"><?php echo $p['barcode'] ?: '-'; ?></td>
                            <td class="text-danger fw-bold">Rp <?php echo number_format($p['harga']); ?></td>
                            <td>
                                <?php
                                $pid = $p['id'];
                                $id_cabang_user = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                                $branch_filter_sql = "";
                                if ($id_cabang_user > 1) {
                                    $branch_filter_sql = " AND c.id_cabang = '$id_cabang_user'";
                                }
                                $q_stok = mysqli_query($koneksi, "
                                    SELECT c.nama_cabang, COALESCE(sc.stok, 0) AS stok 
                                    FROM cabang c 
                                    LEFT JOIN stok_cabang sc ON c.id_cabang = sc.id_cabang AND sc.id_produk = '$pid'
                                    WHERE 1=1 $branch_filter_sql
                                    ORDER BY c.id_cabang ASC
                                ");
                                while($s = mysqli_fetch_assoc($q_stok)) {
                                    $s_color = $s['stok'] > 0 ? 'text-dark' : 'text-danger';
                                    echo "<div class='small' style='font-size: 0.8rem;'><strong class='text-secondary'>" . htmlspecialchars($s['nama_cabang']) . ":</strong> <span class='fw-bold $s_color'>" . $s['stok'] . "</span> pcs</div>";
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#edit<?php echo $p['id']; ?>">Edit</button>
                                <a href="produk.php?hapus=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-1" onclick="return confirm('Hapus produk ini?')">Hapus</a>
                            </td>
                        </tr>

                        <div class="modal fade" id="edit<?php echo $p['id']; ?>" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="modal-header"><h5>Edit Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id_p" value="<?php echo $p['id']; ?>">
                                        <div class="row mb-2">
                                            <div class="col-7">
                                                <input type="text" name="nama" class="form-control" value="<?php echo $p['nama_produk']; ?>" required>
                                                <small class="text-muted">Nama Produk</small>
                                            </div>
                                            <div class="col-5">
                                                <input type="text" name="barcode" class="form-control" value="<?php echo $p['barcode']; ?>" placeholder="Barcode">
                                                <small class="text-muted">Barcode (Opsional)</small>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <input type="number" name="harga" class="form-control" value="<?php echo $p['harga']; ?>" placeholder="Harga Normal" required>
                                                <small class="text-muted">Harga Normal</small>
                                            </div>
                                        </div>
                                        <div class="card p-3 bg-light mb-3">
                                            <h6 class="fw-bold text-danger mb-2 small"><i class="bi bi-geo-alt-fill"></i> Atur Stok per Cabang</h6>
                                            <?php
                                            $pid = $p['id'];
                                            $id_cabang_user = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                                            $branch_edit_filter = "";
                                            if ($id_cabang_user > 1) {
                                                $branch_edit_filter = " AND c.id_cabang = '$id_cabang_user'";
                                            }
                                            $q_stok_edit = mysqli_query($koneksi, "
                                                SELECT c.id_cabang, c.nama_cabang, COALESCE(sc.stok, 0) AS stok 
                                                FROM cabang c 
                                                LEFT JOIN stok_cabang sc ON c.id_cabang = sc.id_cabang AND sc.id_produk = '$pid'
                                                WHERE 1=1 $branch_edit_filter
                                                ORDER BY c.id_cabang ASC
                                            ");
                                            while($se = mysqli_fetch_assoc($q_stok_edit)):
                                            ?>
                                                <div class="row mb-2 align-items-center">
                                                    <div class="col-7">
                                                        <small class="fw-semibold text-dark" style="font-size:0.78rem;"><?php echo htmlspecialchars($se['nama_cabang']); ?></small>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="number" name="stok_cabang[<?php echo $se['id_cabang']; ?>]" class="form-control form-control-sm font-monospace text-end" value="<?php echo $se['stok']; ?>" required>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <input type="number" name="harga_grosir" class="form-control" placeholder="Harga Grosir" value="<?php echo $p['harga_grosir']; ?>">
                                                <small class="text-muted">Kosongkan jika tidak ada</small>
                                            </div>
                                            <div class="col-6">
                                                <input type="number" name="min_qty_grosir" class="form-control" placeholder="Min. Qty" value="<?php echo $p['min_qty_grosir'] ?? 2; ?>">
                                                <small class="text-muted">Minimal beli</small>
                                            </div>
                                        </div>
                                        <select name="kategori" class="form-select mb-2" required>
                                            <?php
                                            $cats2 = mysqli_query($koneksi, "SELECT * FROM categories");
                                            while($c2 = mysqli_fetch_assoc($cats2)):
                                            ?>
                                                <option value="<?php echo $c2['id']; ?>" <?php echo ($p['kategori_id'] == $c2['id']) ? 'selected' : ''; ?>><?php echo $c2['nama_kategori']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <textarea name="desk" class="form-control mb-2"><?php echo $p['deskripsi']; ?></textarea>
                                        <label class="form-label fw-bold small">Gambar Produk</label>
                                        <input type="file" name="foto" class="form-control mb-2" accept="image/*">
                                        <div class="text-center my-2 small text-muted">- ATAU -</div>
                                        <input type="url" name="foto_url" class="form-control" placeholder="https://example.com/gambar.jpg" value="<?php echo (strpos($p['foto'], 'http') === 0) ? $p['foto'] : ''; ?>">
                                        <small class="text-muted">Paste link gambar dari internet</small>
                                    </div>
                                    <div class="modal-footer"><button name="update" class="btn btn-danger w-100 rounded-pill">Simpan Perubahan</button></div>
                                </form>
                            </div></div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tambah" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header"><h5>Produk Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-7">
                            <input type="text" name="nama" class="form-control" placeholder="Nama Produk" required>
                            <small class="text-muted">Nama Produk</small>
                        </div>
                        <div class="col-5">
                            <input type="text" name="barcode" class="form-control" placeholder="Barcode (Opsional)">
                            <small class="text-muted">Barcode (Opsional)</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <input type="number" name="harga" class="form-control" placeholder="Harga Normal (per pcs)" required>
                            <small class="text-muted">Harga Normal</small>
                        </div>
                    </div>
                    <div class="card p-3 bg-light mb-3">
                        <h6 class="fw-bold text-danger mb-2 small"><i class="bi bi-geo-alt-fill"></i> Input Stok Awal per Cabang</h6>
                        <?php
                        $id_cabang_user = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                        $branch_add_filter = "";
                        if ($id_cabang_user > 1) {
                            $branch_add_filter = " WHERE id_cabang = '$id_cabang_user'";
                        }
                        $q_branches = mysqli_query($koneksi, "SELECT * FROM cabang $branch_add_filter ORDER BY id_cabang ASC");
                        while($b = mysqli_fetch_assoc($q_branches)):
                        ?>
                            <div class="row mb-2 align-items-center">
                                <div class="col-7">
                                    <small class="fw-semibold text-dark" style="font-size:0.78rem;"><?php echo htmlspecialchars($b['nama_cabang']); ?></small>
                                </div>
                                <div class="col-5">
                                    <input type="number" name="stok_cabang[<?php echo $b['id_cabang']; ?>]" class="form-control form-control-sm font-monospace text-end" value="<?php echo ($b['id_cabang'] == 1) ? '10' : '0'; ?>" required>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <input type="number" name="harga_grosir" class="form-control" placeholder="Harga Grosir (opsional)">
                            <small class="text-muted">Kosongkan jika tidak ada</small>
                        </div>
                        <div class="col-6">
                            <input type="number" name="min_qty_grosir" class="form-control" placeholder="Min. Qty" value="2">
                            <small class="text-muted">Minimal beli untuk harga grosir</small>
                        </div>
                    </div>
                    <select name="kategori" class="form-select mb-2" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                        $cats = mysqli_query($koneksi, "SELECT * FROM categories");
                        while($c = mysqli_fetch_assoc($cats)):
                        ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['nama_kategori']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <textarea name="desk" class="form-control mb-2" placeholder="Deskripsi"></textarea>
                    <label class="form-label fw-bold small">Gambar Produk</label>
                    <input type="file" name="foto" class="form-control mb-2" accept="image/*">
                    <div class="text-center my-2 small text-muted">- ATAU -</div>
                    <input type="url" name="foto_url" class="form-control" placeholder="https://example.com/gambar.jpg">
                    <small class="text-muted">Paste link gambar dari internet (opsional)</small>
                </div>
                <div class="modal-footer"><button name="simpan" class="btn btn-danger w-100 rounded-pill">Pasang Produk</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Tambah Stok (Persediaan) -->
    <div class="modal fade" id="tambahStok" tabindex="-1" aria-labelledby="tambahStokLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <form method="POST">
                    <div class="modal-header border-0 bg-light rounded-top-4 py-3">
                        <h5 class="modal-title fw-bold text-danger" id="tambahStokLabel"><i class="bi bi-box-arrow-in-down me-2"></i>Tambah Stok / Persediaan (Massal)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Step 1: Scan / Search -->
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold small text-muted">Scan Barcode / Cari Nama Barang</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-danger"><i class="bi bi-search"></i></span>
                                <input type="text" id="cari_produk_stok" class="form-control border-start-0 py-2" placeholder="Scan barcode atau ketik nama barang untuk ditambahkan..." autocomplete="off">
                            </div>
                            <div id="hasil_cari_stok" class="list-group mt-2 shadow-sm border rounded-3" style="max-height: 200px; overflow-y: auto; display: none; position: absolute; z-index: 1050; width: 100%;">
                                <!-- Hasil pencarian JS -->
                            </div>
                        </div>

                        <!-- Step 2: Pending Addition Quantity Input -->
                        <div id="pending_stok_box" class="card p-3 bg-light border-0 rounded-3 mb-3 text-start" style="display: none; border-left: 4px solid #e63946 !important;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" id="pending_nama">Nama Barang</h6>
                                    <span class="small text-muted font-monospace">Barcode: <span id="pending_barcode">-</span></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Stok Skrg: <span id="pending_stok_sekarang">0</span></span>
                                </div>
                            </div>
                            <div class="row mt-3 align-items-center">
                                <div class="col-7">
                                    <label class="form-label fw-bold small text-muted mb-0">Masukkan Jumlah Tambah Stok (Tekan Enter):</label>
                                </div>
                                <div class="col-5">
                                    <input type="number" id="temp_qty_tambah" class="form-control form-control-sm fw-bold font-monospace text-end" value="1" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Item Persediaan (Bulk List Table) -->
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-check text-danger me-2"></i>Daftar Barang yang Ditambahkan:</h6>
                        <div class="table-responsive border rounded-3" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th class="text-center" style="width: 20%;">Stok Sekarang</th>
                                        <th class="text-center" style="width: 25%;">Jumlah Tambah</th>
                                        <th class="text-center" style="width: 15%;">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody id="bulk_stock_table_body">
                                    <tr id="bulk_empty_row">
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="bi bi-upc-scan fs-3 opacity-50 mb-2 d-block"></i>
                                            Belum ada barang dipilih. Scan barcode atau cari nama barang di atas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <input type="hidden" name="bulk_items_json" id="bulk_items_json">
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" name="simpan_stok_tambah" id="btn_submit_bulk_stock" class="btn btn-danger w-100 py-3 rounded-pill fw-bold shadow-sm" disabled>
                            SIMPAN PERSEDIAAN <i class="bi bi-check-lg ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
    // Handle Bulk Stock Replenishment
    if (isset($_POST['simpan_stok_tambah'])) {
        $items_json = $_POST['bulk_items_json'];
        $id_cabang_user = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
        
        if (!empty($items_json)) {
            $items = json_decode($items_json, true);
            if (is_array($items) && count($items) > 0) {
                $success_count = 0;
                foreach ($items as $item) {
                    $id_produk = (int)$item['id'];
                    $qty_tambah = (int)$item['qty_tambah'];
                    
                    if ($id_produk > 0 && $qty_tambah > 0) {
                        $check_stok = mysqli_query($koneksi, "SELECT * FROM stok_cabang WHERE id_produk = '$id_produk' AND id_cabang = '$id_cabang_user'");
                        if (mysqli_num_rows($check_stok) > 0) {
                            mysqli_query($koneksi, "UPDATE stok_cabang SET stok = stok + $qty_tambah WHERE id_produk = '$id_produk' AND id_cabang = '$id_cabang_user'");
                        } else {
                            mysqli_query($koneksi, "INSERT INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$id_produk', '$id_cabang_user', '$qty_tambah')");
                        }
                        
                        if ($id_cabang_user == 1) {
                            mysqli_query($koneksi, "UPDATE produk SET stok = stok + $qty_tambah WHERE id = '$id_produk'");
                        }
                        $success_count++;
                    }
                }
                echo "<script>alert('Stok berhasil ditambahkan untuk $success_count barang!'); window.location='produk.php';</script>";
                exit;
            }
        }
    }

    if(isset($_POST['simpan'])) {
        $kat = (int)$_POST['kategori'];
        $harga_grosir = !empty($_POST['harga_grosir']) ? (int)$_POST['harga_grosir'] : 'NULL';
        $min_qty = !empty($_POST['min_qty_grosir']) ? (int)$_POST['min_qty_grosir'] : 2;
        
        // Handle image: URL or Upload
        if(!empty($_POST['foto_url'])) {
            $f = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
        } else {
            // Create organized folder structure
            $upload_dir = "../assets/produk/" . date('Y') . "/" . date('m') . "/";
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(!in_array($ext, $allowed)) {
                echo "<script>alert('Format file tidak didukung! Gunakan JPG, PNG, atau GIF'); window.location='produk.php';</script>";
                exit;
            }
            
            // Check file size (max 2MB)
            if($_FILES['foto']['size'] > 2097152) {
                echo "<script>alert('Ukuran file maksimal 2MB!'); window.location='produk.php';</script>";
                exit;
            }
            
            $unique_name = 'prod_' . uniqid() . '_' . time() . '.' . $ext;
            $f = "produk/" . date('Y') . "/" . date('m') . "/" . $unique_name;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $unique_name);
        }
        
        $stok_pusat = isset($_POST['stok_cabang'][1]) ? (int)$_POST['stok_cabang'][1] : 0;
        $barcode = !empty($_POST['barcode']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['barcode']) . "'" : "NULL";
        mysqli_query($koneksi, "INSERT INTO produk (nama_produk, barcode, harga, harga_grosir, min_qty_grosir, deskripsi, foto, kategori_id, stok) VALUES ('$_POST[nama]',$barcode,'$_POST[harga]',$harga_grosir,$min_qty,'$_POST[desk]','$f', $kat, $stok_pusat)");
        
        $new_id = mysqli_insert_id($koneksi);
        if ($new_id && isset($_POST['stok_cabang'])) {
            foreach ($_POST['stok_cabang'] as $id_cabang => $stok_qty) {
                $id_cabang = (int)$id_cabang;
                $stok_qty = (int)$stok_qty;
                mysqli_query($koneksi, "INSERT INTO stok_cabang (id_produk, id_cabang, stok) VALUES ('$new_id', '$id_cabang', '$stok_qty')");
            }
        }
        echo "<script>window.location='produk.php';</script>";
    }
    if(isset($_POST['update'])) {
        $id = $_POST['id_p'];
        $kat = (int)$_POST['kategori'];
        $harga_grosir = !empty($_POST['harga_grosir']) ? (int)$_POST['harga_grosir'] : 'NULL';
        $min_qty = !empty($_POST['min_qty_grosir']) ? (int)$_POST['min_qty_grosir'] : 2;
        
        // Handle image: URL or Upload
        $barcode = !empty($_POST['barcode']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['barcode']) . "'" : "NULL";
        if(!empty($_POST['foto_url'])) {
            $f = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
            mysqli_query($koneksi, "UPDATE produk SET nama_produk='$_POST[nama]', barcode=$barcode, harga='$_POST[harga]', harga_grosir=$harga_grosir, min_qty_grosir=$min_qty, deskripsi='$_POST[desk]', foto='$f', kategori_id=$kat WHERE id='$id'");
        } elseif($_FILES['foto']['name'] != "") {
            // Create organized folder structure
            $upload_dir = "../assets/produk/" . date('Y') . "/" . date('m') . "/";
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(!in_array($ext, $allowed)) {
                echo "<script>alert('Format file tidak didukung!'); window.location='produk.php';</script>";
                exit;
            }
            
            if($_FILES['foto']['size'] > 2097152) {
                echo "<script>alert('Ukuran file maksimal 2MB!'); window.location='produk.php';</script>";
                exit;
            }
            
            $unique_name = 'prod_' . uniqid() . '_' . time() . '.' . $ext;
            $f = "produk/" . date('Y') . "/" . date('m') . "/" . $unique_name;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $unique_name);
            
            mysqli_query($koneksi, "UPDATE produk SET nama_produk='$_POST[nama]', barcode=$barcode, harga='$_POST[harga]', harga_grosir=$harga_grosir, min_qty_grosir=$min_qty, deskripsi='$_POST[desk]', foto='$f', kategori_id=$kat WHERE id='$id'");
        } else {
            mysqli_query($koneksi, "UPDATE produk SET nama_produk='$_POST[nama]', barcode=$barcode, harga='$_POST[harga]', harga_grosir=$harga_grosir, min_qty_grosir=$min_qty, deskripsi='$_POST[desk]', kategori_id=$kat WHERE id='$id'");
        }

        // Update branch specific stock levels
        if (isset($_POST['stok_cabang'])) {
            foreach ($_POST['stok_cabang'] as $id_cabang => $stok_qty) {
                $id_cabang = (int)$id_cabang;
                $stok_qty = (int)$stok_qty;
                mysqli_query($koneksi, "
                    INSERT INTO stok_cabang (id_produk, id_cabang, stok) 
                    VALUES ('$id', '$id_cabang', '$stok_qty')
                    ON DUPLICATE KEY UPDATE stok = '$stok_qty'
                ");
                
                // Keep branch 1 (Pusat) in sync with main products table for online store compatibility
                if ($id_cabang == 1) {
                    mysqli_query($koneksi, "UPDATE produk SET stok = '$stok_qty' WHERE id = '$id'");
                }
            }
        }
        echo "<script>window.location='produk.php';</script>";
    }
    if(isset($_GET['hapus'])) {
        mysqli_query($koneksi, "DELETE FROM produk WHERE id='$_GET[hapus]'");
        echo "<script>window.location='produk.php';</script>";
    }
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Fetch products for JS stock autocomplete list (based on current branch)
    $id_cabang_user = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
    $res_prods_js = mysqli_query($koneksi, "
        SELECT p.id, p.nama_produk, p.barcode, COALESCE(sc.stok, 0) AS stok 
        FROM produk p 
        LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = '$id_cabang_user'
        ORDER BY p.nama_produk ASC
    ");
    $prods_js = [];
    while ($p_js = mysqli_fetch_assoc($res_prods_js)) {
        $prods_js[] = $p_js;
    }
    ?>
    <script>
        const productsJSList = <?php echo json_encode($prods_js); ?>;
        let bulkStockItems = {};
        let selectedTempProduct = null;
        let focusedIndex = -1;
        
        // Filter list based on search query
        function filterStockSearch() {
            const query = $('#cari_produk_stok').val().toLowerCase().trim();
            const resultDiv = $('#hasil_cari_stok');
            
            if (query === '' || selectedTempProduct !== null) {
                resultDiv.hide().empty();
                focusedIndex = -1;
                return;
            }
            
            const filtered = productsJSList.filter(p => 
                p.nama_produk.toLowerCase().includes(query) || 
                (p.barcode && p.barcode.toLowerCase().includes(query))
            );
            
            resultDiv.empty();
            focusedIndex = -1;
            
            if (filtered.length === 0) {
                resultDiv.append('<div class="list-group-item text-muted small py-2">Barang tidak terdaftar</div>').show();
                return;
            }
            
            filtered.slice(0, 5).forEach((p, idx) => {
                const itemHTML = `
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 border-bottom search-result-item" data-id="${p.id}" onclick="selectProductForStock(${p.id})">
                        <div class="fw-semibold small">${p.nama_produk}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Barcode: ${p.barcode || '-'} | Stok Sekarang: <strong>${p.stok}</strong> pcs</div>
                    </a>
                `;
                resultDiv.append(itemHTML);
            });
            resultDiv.show();
        }
        
        // Select product and open/focus the temporary quantity input
        function selectProductForStock(id) {
            const p = productsJSList.find(prod => prod.id == id);
            if (!p) return;
            
            selectedTempProduct = p;
            
            // Fill pending info card
            $('#pending_nama').text(p.nama_produk);
            $('#pending_barcode').text(p.barcode || '-');
            $('#pending_stok_sekarang').text(p.stok);
            
            // Default quantity is 1 and auto-selected for immediate replacement on typing
            $('#temp_qty_tambah').val('1');
            
            // Hide search results and clear search input
            $('#cari_produk_stok').val('');
            $('#hasil_cari_stok').hide().empty();
            focusedIndex = -1;
            
            // Show pending box and focus the qty input
            $('#pending_stok_box').show();
            $('#temp_qty_tambah').focus().select();
        }
        
        // Handle pressing Enter inside the temporary quantity input
        function handleQtyInputEnter(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!selectedTempProduct) return;
                
                const qtyVal = parseInt($('#temp_qty_tambah').val()) || 1;
                const id = selectedTempProduct.id;
                
                // Add to bulkStockItems list
                if (bulkStockItems[id]) {
                    bulkStockItems[id].qty_tambah += qtyVal;
                } else {
                    bulkStockItems[id] = {
                        id: selectedTempProduct.id,
                        nama: selectedTempProduct.nama_produk,
                        barcode: selectedTempProduct.barcode,
                        stok_sekarang: parseInt(selectedTempProduct.stok),
                        qty_tambah: qtyVal
                    };
                }
                
                // Hide pending area and reset selected temp product
                $('#pending_stok_box').hide();
                selectedTempProduct = null;
                
                // Re-render table and refocus back to search input for next scan
                renderBulkStockTable();
                $('#cari_produk_stok').focus().select();
            }
        }
        
        // Render bulk table inside the modal
        function renderBulkStockTable() {
            const tbody = $('#bulk_stock_table_body');
            tbody.find('.bulk-item-row').remove();
            
            const keys = Object.keys(bulkStockItems);
            
            if (keys.length === 0) {
                $('#bulk_empty_row').show();
                $('#btn_submit_bulk_stock').prop('disabled', true);
                return;
            }
            
            $('#bulk_empty_row').hide();
            $('#btn_submit_bulk_stock').prop('disabled', false);
            
            keys.forEach(key => {
                const item = bulkStockItems[key];
                const rowHTML = `
                    <tr class="bulk-item-row" id="bulk-row-${item.id}">
                        <td>
                            <div class="fw-semibold">${item.nama}</div>
                            <small class="text-muted font-monospace">${item.barcode || '-'}</small>
                        </td>
                        <td class="text-center font-monospace">${item.stok_sekarang}</td>
                        <td class="text-center">
                            <input type="number" class="form-control form-control-sm font-monospace text-center py-1" value="${item.qty_tambah}" min="1" style="width: 80px; margin: 0 auto;" onchange="updateBulkItemQty(${item.id}, this)">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-danger border-0 p-0" onclick="removeBulkItem(${item.id})">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(rowHTML);
            });
            
            // Sync with hidden JSON field
            $('#bulk_items_json').val(JSON.stringify(Object.values(bulkStockItems)));
        }
        
        // Update input quantity in array
        function updateBulkItemQty(id, inputElement) {
            const val = parseInt($(inputElement).val()) || 1;
            if (val < 1) {
                $(inputElement).val(1);
                bulkStockItems[id].qty_tambah = 1;
            } else {
                bulkStockItems[id].qty_tambah = val;
            }
            // Sync hidden JSON
            $('#bulk_items_json').val(JSON.stringify(Object.values(bulkStockItems)));
        }
        
        // Remove item from bulk list
        function removeBulkItem(id) {
            if (bulkStockItems[id]) {
                delete bulkStockItems[id];
                renderBulkStockTable();
            }
        }
        
        // Helper to highlight active item in search dropdown
        function highlightListItem(listItems) {
            listItems.removeClass('active bg-danger text-white');
            if (focusedIndex >= 0 && focusedIndex < listItems.length) {
                const activeItem = listItems.eq(focusedIndex);
                activeItem.addClass('active bg-danger text-white');
                
                // Keep the active item visible in the scrolled dropdown
                const container = $('#hasil_cari_stok');
                const itemTop = activeItem.position().top;
                if (itemTop < 0 || itemTop > container.height() - activeItem.height()) {
                    container.scrollTop(container.scrollTop() + itemTop);
                }
            }
        }
        
        // Initialize event listeners when DOM is ready
        $(document).ready(function() {
            // Bind filterStockSearch to keyup on the search box
            $('#cari_produk_stok').on('keyup', function(e) {
                // Ignore navigation keys
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                    return;
                }
                filterStockSearch();
            });

            // Bind keydown to search box for keyboard navigation and scanning
            $('#cari_produk_stok').on('keydown', function(e) {
                const resultDiv = $('#hasil_cari_stok');
                const listItems = resultDiv.find('.search-result-item');
                
                if (e.key === 'ArrowDown') {
                    if (listItems.length > 0) {
                        e.preventDefault();
                        focusedIndex = (focusedIndex + 1) % listItems.length;
                        highlightListItem(listItems);
                    }
                } else if (e.key === 'ArrowUp') {
                    if (listItems.length > 0) {
                        e.preventDefault();
                        focusedIndex = (focusedIndex - 1 + listItems.length) % listItems.length;
                        highlightListItem(listItems);
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // If an item in search results is highlighted, select it
                    if (focusedIndex >= 0 && focusedIndex < listItems.length) {
                        const selectedId = listItems.eq(focusedIndex).data('id');
                        selectProductForStock(selectedId);
                        return;
                    }
                    
                    const query = $('#cari_produk_stok').val().trim();
                    if (query === '') return;
                    
                    // First search for exact match on barcode or ID
                    let p = productsJSList.find(prod => prod.barcode === query || prod.id == query);
                    
                    if (p) {
                        selectProductForStock(p.id);
                    } else {
                        // Check if we have search results, pick the first one
                        const filtered = productsJSList.filter(prod => 
                            prod.nama_produk.toLowerCase().includes(query.toLowerCase()) || 
                            (prod.barcode && prod.barcode.toLowerCase().includes(query.toLowerCase()))
                        );
                        if (filtered.length > 0) {
                            selectProductForStock(filtered[0].id);
                        } else {
                            alert('Barang tidak ditemukan!');
                            $('#cari_produk_stok').val('').focus();
                        }
                    }
                }
            });

            // Bind keydown to quantity field to capture Enter keypress
            $('#temp_qty_tambah').on('keydown', function(e) {
                handleQtyInputEnter(e);
            });

            // Auto-focus search input when modal is shown
            $('#tambahStok').on('shown.bs.modal', function () {
                $('#cari_produk_stok').focus();
            });

            // Reset modal states when closed
            $('#tambahStok').on('hidden.bs.modal', function () {
                $('#cari_produk_stok').val('');
                $('#hasil_cari_stok').hide().empty();
                $('#pending_stok_box').hide();
                selectedTempProduct = null;
                focusedIndex = -1;
                bulkStockItems = {};
                renderBulkStockTable();
                $('#bulk_items_json').val('');
            });
        });
    </script>
</body>
</html>