<?php 
session_start();
include '../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("location: login.php");
    exit;
}

// Auto-create categories table if missing
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'categories'");
if(mysqli_num_rows($check_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Handle Add Category
if(isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    mysqli_query($koneksi, "INSERT INTO categories (nama_kategori) VALUES ('$nama')");
    echo "<script>alert('Kategori berhasil ditambahkan!'); window.location='kategori.php';</script>";
}

// Handle Add Satuan
if(isset($_POST['tambah_satuan'])) {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_satuan']));
    if (!empty($nama)) {
        mysqli_query($koneksi, "INSERT IGNORE INTO varian_satuan (nama_satuan) VALUES ('$nama')");
        echo "<script>alert('Satuan berhasil ditambahkan!'); window.location='kategori.php';</script>";
    }
}

// Handle Edit Category
if(isset($_POST['edit'])) {
    $id = (int)$_POST['id_kategori'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    mysqli_query($koneksi, "UPDATE categories SET nama_kategori='$nama' WHERE id=$id");
    echo "<script>alert('Kategori berhasil diupdate!'); window.location='kategori.php';</script>";
}

// Handle Edit Satuan
if(isset($_POST['edit_satuan'])) {
    $id = (int)$_POST['id_satuan'];
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_satuan']));
    
    $old_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_satuan FROM varian_satuan WHERE id=$id"));
    if ($old_data && $old_data['nama_satuan'] !== 'Pcs' && !empty($nama)) {
        mysqli_query($koneksi, "UPDATE varian_satuan SET nama_satuan='$nama' WHERE id=$id");
        $old_name = mysqli_real_escape_string($koneksi, $old_data['nama_satuan']);
        mysqli_query($koneksi, "UPDATE produk_kemasan SET nama_satuan='$nama' WHERE nama_satuan='$old_name'");
        echo "<script>alert('Satuan berhasil diupdate!'); window.location='kategori.php';</script>";
    }
}

// Handle Delete Category
if(isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Set products with this category to "Lainnya" (ID 5) before deleting
    mysqli_query($koneksi, "UPDATE produk SET kategori_id = 5 WHERE kategori_id = $id");
    mysqli_query($koneksi, "DELETE FROM categories WHERE id = $id");
    echo "<script>alert('Kategori berhasil dihapus!'); window.location='kategori.php';</script>";
}

// Handle Delete Satuan
if(isset($_GET['hapus_satuan'])) {
    $id = (int)$_GET['hapus_satuan'];
    $sat_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_satuan FROM varian_satuan WHERE id=$id"));
    if ($sat_data && $sat_data['nama_satuan'] !== 'Pcs') {
        mysqli_query($koneksi, "DELETE FROM varian_satuan WHERE id = $id");
        echo "<script>alert('Satuan berhasil dihapus!'); window.location='kategori.php';</script>";
    } else {
        echo "<script>alert('Satuan Pcs (default) tidak boleh dihapus!'); window.location='kategori.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - MerahPutih Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background: #F0F4FF; }
        .main-content { margin-left: 268px; padding: 0; }
        .topbar { height: 68px; background: white; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 500; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .topbar h1 { font-size: 1.15rem; font-weight: 700; color: #1A1A2E; margin: 0; }
        .topbar p { font-size: 0.78rem; color: #8A8AA0; margin: 0; }
        .content-wrap { padding: 28px 32px; }
        .card-premium { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 28px; }
        .form-control { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.9rem; transition: border-color 0.2s; }
        .form-control:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #4A4A6A; margin-bottom: 6px; }
        .btn-admin { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 24px; border-radius: 12px; font-size: 0.88rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; width: 100%; }
        .btn-danger-solid { background: linear-gradient(135deg, #922B21, #E74C3C); color: white; } .btn-danger-solid:hover { opacity: 0.9; }
        .category-item { padding: 20px; background: #F8F9FD; border-radius: 16px; border: 1.5px solid #EAEAF2; transition: all 0.25s ease; display: flex; align-items: center; justify-content: space-between; }
        .category-item:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); border-color: #C0392B; }
        .category-item h6 { font-size: 0.95rem; font-weight: 700; color: #1A1A2E; margin: 0 0 4px 0; }
        .category-item small { font-size: 0.78rem; font-weight: 600; color: #8A8AA0; }
        .btn-icon-action { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #E2E2EC; background: white; color: #4A4A6A; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; cursor: pointer; }
        .btn-icon-action:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }
        .modal-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.15); }
        .modal-header { background: #F8F8FF; border-bottom: 1px solid #EEEEF8; border-radius: 20px 20px 0 0; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #EEEEF8; padding: 16px 24px; border-radius: 0 0 20px 20px; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>Kategori Produk</h1>
                <p>Kelola kategori untuk klasifikasi produk Anda</p>
            </div>
        </div>

        <div class="content-wrap">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card-premium mb-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-folder-plus text-danger me-2"></i>Tambah Kategori</h5>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Nama Kategori</label>
                                <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Elektronik, Fashion" required autocomplete="off">
                            </div>
                            <button type="submit" name="tambah" class="btn-admin btn-danger-solid">
                                <i class="bi bi-plus-circle"></i> Tambah Kategori
                            </button>
                        </form>
                    </div>

                    <div class="card-premium">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-box-seam text-danger me-2"></i>Tambah Satuan Kemasan</h5>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Nama Satuan</label>
                                <input type="text" name="nama_satuan" class="form-control" placeholder="Contoh: Slop, Renceng, Dus" required autocomplete="off">
                            </div>
                            <button type="submit" name="tambah_satuan" class="btn-admin btn-danger-solid">
                                <i class="bi bi-plus-circle"></i> Tambah Satuan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card-premium mb-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-folder2-open text-danger me-2"></i>Daftar Kategori Terdaftar</h5>
                        <div class="row g-3">
                            <?php 
                            $categories = mysqli_query($koneksi, "SELECT c.*, COUNT(p.id) as total_produk FROM categories c LEFT JOIN produk p ON c.id = p.kategori_id GROUP BY c.id ORDER BY c.nama_kategori ASC");
                            $cat_list = [];
                            while($cat = mysqli_fetch_assoc($categories)): 
                                $cat_list[] = $cat;
                            ?>
                            <div class="col-md-6">
                                <div class="category-item">
                                    <div>
                                        <h6><?php echo htmlspecialchars($cat['nama_kategori']); ?></h6>
                                        <small><i class="bi bi-box-seam me-1"></i><?php echo $cat['total_produk']; ?> produk</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if($cat['id'] != 5): // Don't allow editing/deleting "Lainnya" ?>
                                            <button class="btn-icon-action" data-bs-toggle="modal" data-bs-target="#edit<?php echo $cat['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="kategori.php?hapus=<?php echo $cat['id']; ?>" class="btn-icon-action" onclick="return confirm('Yakin hapus kategori ini? Produk akan dipindah ke Lainnya.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill font-monospace" style="font-size: 0.65rem;">DEFAULT</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="card-premium">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-tags text-danger me-2"></i>Daftar Satuan Kemasan Terdaftar</h5>
                        <div class="row g-3">
                            <?php 
                            $satuan_q = mysqli_query($koneksi, "
                                SELECT vs.*, COUNT(pk.id_kemasan) as total_produk 
                                FROM varian_satuan vs 
                                LEFT JOIN produk_kemasan pk ON vs.nama_satuan = pk.nama_satuan 
                                GROUP BY vs.id 
                                ORDER BY vs.nama_satuan ASC
                            ");
                            while($sat = mysqli_fetch_assoc($satuan_q)): 
                            ?>
                            <div class="col-md-4">
                                <div class="category-item py-2 px-3" style="border-radius:12px;">
                                    <div>
                                        <h6 class="mb-0" style="font-size:0.88rem;"><?php echo htmlspecialchars($sat['nama_satuan']); ?></h6>
                                        <small class="text-muted" style="font-size:0.7rem;"><i class="bi bi-box-seam me-1"></i><?php echo $sat['total_produk']; ?> produk</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if($sat['nama_satuan'] != 'Pcs'): ?>
                                            <button class="btn-icon-action" style="width:28px; height:28px; border-radius:6px; font-size:0.78rem;" data-bs-toggle="modal" data-bs-target="#editSatuan<?php echo $sat['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="kategori.php?hapus_satuan=<?php echo $sat['id']; ?>" class="btn-icon-action" style="width:28px; height:28px; border-radius:6px; font-size:0.78rem;" onclick="return confirm('Yakin hapus satuan ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill font-monospace" style="font-size: 0.6rem; padding: 3px 6px;">BASE</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modals -->
    <?php foreach($cat_list as $cat): ?>
        <?php if($cat['id'] != 5): ?>
        <div class="modal fade" id="edit<?php echo $cat['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_kategori" value="<?php echo $cat['id']; ?>">
                            <div class="mb-2">
                                <label class="form-label">Nama Kategori</label>
                                <input type="text" name="nama_kategori" class="form-control" value="<?php echo htmlspecialchars($cat['nama_kategori']); ?>" required autocomplete="off">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="edit" class="btn-admin btn-danger-solid">
                                <i class="bi bi-check-circle"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Edit Satuan Modals -->
    <?php 
    $satuan_edit_list_q = mysqli_query($koneksi, "SELECT * FROM varian_satuan WHERE nama_satuan != 'Pcs' ORDER BY nama_satuan ASC");
    while($sat = mysqli_fetch_assoc($satuan_edit_list_q)): 
    ?>
        <div class="modal fade" id="editSatuan<?php echo $sat['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Satuan Kemasan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_satuan" value="<?php echo $sat['id']; ?>">
                            <div class="mb-2">
                                <label class="form-label">Nama Satuan</label>
                                <input type="text" name="nama_satuan" class="form-control" value="<?php echo htmlspecialchars($sat['nama_satuan']); ?>" required autocomplete="off">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="edit_satuan" class="btn-admin btn-danger-solid">
                                <i class="bi bi-check-circle"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
