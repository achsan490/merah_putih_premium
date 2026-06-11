<?php 
session_start();
include '../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("location: login.php");
    exit;
}

// Auto-create payment_confirmations table if missing
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'payment_confirmations'");
if(mysqli_num_rows($check_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS payment_confirmations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_pesanan INT NOT NULL,
        nama_pengirim VARCHAR(100),
        bank_pengirim VARCHAR(50),
        jumlah_transfer INT,
        tanggal_transfer DATE,
        bukti_foto VARCHAR(255),
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Handle approval
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conf = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM payment_confirmations WHERE id = $id"));
    $pesanan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = {$conf['id_pesanan']}"));
    
    // Auto-expand status column if too small
    $check_status_col = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'status'");
    if($check_status_col && mysqli_num_rows($check_status_col) > 0) {
        $col_info = mysqli_fetch_assoc($check_status_col);
        if(strpos($col_info['Type'], 'varchar(10)') !== false) {
            mysqli_query($koneksi, "ALTER TABLE pesanan MODIFY COLUMN status VARCHAR(20)");
        }
    }
    
    mysqli_query($koneksi, "UPDATE payment_confirmations SET status='approved' WHERE id=$id");
    mysqli_query($koneksi, "UPDATE pesanan SET status='paid' WHERE id_pesanan={$conf['id_pesanan']}");
    
    $wa = (substr($pesanan['no_telp'], 0, 1) == '0') ? '62'.substr($pesanan['no_telp'], 1) : $pesanan['no_telp'];
    $link = "https://api.whatsapp.com/send?phone=$wa&text=".urlencode("Halo {$pesanan['nama_penerima']}, pembayaran Anda untuk pesanan #{$conf['id_pesanan']} telah dikonfirmasi! Pesanan sedang diproses.");
    
    echo "<script>alert('Pembayaran disetujui!'); window.open('$link', '_blank'); window.location='konfirmasi.php';</script>";
}

// Handle rejection
if(isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($koneksi, "UPDATE payment_confirmations SET status='rejected' WHERE id=$id");
    echo "<script>alert('Pembayaran ditolak!'); window.location='konfirmasi.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Konfirmasi Pembayaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 260px; padding: 40px; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .bukti-img { max-width: 100%; height: auto; border-radius: 10px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="card card-premium p-4 bg-white">
            <h4 class="fw-bold mb-4">Konfirmasi Pembayaran</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Pesanan</th><th>Pengirim</th><th>Bank</th><th>Jumlah</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                        $res = mysqli_query($koneksi, "SELECT pc.*, p.nama_penerima FROM payment_confirmations pc 
                                                       JOIN pesanan p ON pc.id_pesanan = p.id_pesanan 
                                                       WHERE p.id_cabang = '$id_cabang'
                                                       ORDER BY pc.created_at DESC");
                        while($c = mysqli_fetch_assoc($res)): 
                        ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?php echo $c['id_pesanan']; ?></td>
                            <td><?php echo $c['nama_pengirim']; ?><br><small class="text-muted"><?php echo $c['nama_penerima']; ?></small></td>
                            <td><?php echo $c['bank_pengirim']; ?></td>
                            <td class="fw-bold text-danger">Rp <?php echo number_format($c['jumlah_transfer']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['tanggal_transfer'])); ?></td>
                            <td>
                                <?php if($c['status']=='pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($c['status']=='approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info text-white rounded-pill" data-bs-toggle="modal" data-bs-target="#bukti<?php echo $c['id']; ?>">
                                    <i class="bi bi-image"></i> Lihat
                                </button>
                                <?php if($c['status']=='pending'): ?>
                                    <a href="konfirmasi.php?approve=<?php echo $c['id']; ?>" class="btn btn-sm btn-success rounded-pill" onclick="return confirm('Setujui pembayaran ini?')">
                                        <i class="bi bi-check-circle"></i> Setuju
                                    </a>
                                    <a href="konfirmasi.php?reject=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Tolak pembayaran ini?')">
                                        <i class="bi bi-x-circle"></i> Tolak
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Bukti -->
                        <div class="modal fade" id="bukti<?php echo $c['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5>Bukti Transfer #<?php echo $c['id_pesanan']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="../assets/bukti_bayar/<?php echo $c['bukti_foto']; ?>" class="bukti-img">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
