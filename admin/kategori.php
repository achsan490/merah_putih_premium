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

// Handle Edit Category
if(isset($_POST['edit'])) {
    $id = (int)$_POST['id_kategori'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    mysqli_query($koneksi, "UPDATE categories SET nama_kategori='$nama' WHERE id=$id");
    echo "<script>alert('Kategori berhasil diupdate!'); window.location='kategori.php';</script>";
}

// Handle Delete Category
if(isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Set products with this category to "Lainnya" (ID 5) before deleting
    mysqli_query($koneksi, "UPDATE produk SET kategori_id = 5 WHERE kategori_id = $id");
    mysqli_query($koneksi, "DELETE FROM categories WHERE id = $id");
    echo "<script>alert('Kategori berhasil dihapus!'); window.location='kategori.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Kategori - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 260px; padding: 40px; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .category-item { 
            padding: 15px 20px; 
            background: white; 
            border-radius: 12px; 
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
            transition: 0.3s;
        }
        .category-item:hover { 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-color: #8b0000;
        }
        .btn-delete-cat {
            background: white;
            color: #6c757d;
            border: 1px solid #dee2e6;
            padding: 6px 12px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .btn-delete-cat:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-premium p-4 bg-white">
                    <h5 class="fw-bold mb-4">Tambah Kategori Baru</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Elektronik" required>
                        </div>
                        <button type="submit" name="tambah" class="btn w-100 rounded-pill" style="background: #8b0000; color: white;">
                            <i class="bi bi-plus-circle me-2"></i> Tambah Kategori
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-premium p-4 bg-white">
                    <h5 class="fw-bold mb-4">Daftar Kategori</h5>
                    <div class="row g-3">
                        <?php 
                        $categories = mysqli_query($koneksi, "SELECT c.*, COUNT(p.id) as total_produk FROM categories c LEFT JOIN produk p ON c.id = p.kategori_id GROUP BY c.id ORDER BY c.nama_kategori ASC");
                        while($cat = mysqli_fetch_assoc($categories)): 
                        ?>
                        <div class="col-md-6">
                            <div class="category-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo $cat['nama_kategori']; ?></h6>
                                    <small class="text-muted"><?php echo $cat['total_produk']; ?> produk</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if($cat['id'] != 5): // Don't allow editing/deleting "Lainnya" ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#edit<?php echo $cat['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="kategori.php?hapus=<?php echo $cat['id']; ?>" class="btn-delete-cat" onclick="return confirm('Yakin hapus kategori ini? Produk akan dipindah ke Lainnya.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Edit -->
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
                                            <label class="form-label">Nama Kategori</label>
                                            <input type="text" name="nama_kategori" class="form-control" value="<?php echo $cat['nama_kategori']; ?>" required>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="edit" class="btn w-100 rounded-pill" style="background: #8b0000; color: white;">
                                                <i class="bi bi-check-circle me-2"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
