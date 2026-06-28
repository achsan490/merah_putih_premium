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
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - MerahPutih Admin</title>
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
        .card-premium { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; }
        .table thead th { background: #F8F8FF; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9A9AB0; border-bottom: 1px solid #EEEEF8; padding: 14px 16px; }
        .table tbody td { padding: 14px 16px; border-bottom: 1px solid #F5F5FF; vertical-align: middle; font-size: 0.88rem; }
        .table tbody tr:hover td { background: #FAFAFF; }
        .table tbody tr:last-child td { border-bottom: none; }
        .btn-action { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-view { background: #F8F8FF; color: #4A4A6A; border: 1px solid #E8E8F5; }
        .btn-view:hover { background: #EEEEFF; color: #1A1A2E; }
        .btn-approve { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; } .btn-approve:hover { background: #059669; color: white; }
        .btn-reject { background: #FEF0EE; color: #C0392B; border: 1px solid #FECDC8; } .btn-reject:hover { background: #C0392B; color: white; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-pending { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .badge-approved { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-rejected { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .modal-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.15); }
        .modal-header { background: #F8F8FF; border-bottom: 1px solid #EEEEF8; border-radius: 20px 20px 0 0; padding: 20px 24px; }
        .bukti-img { max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>Konfirmasi Pembayaran</h1>
                <p>Verifikasi bukti transfer pembayaran dari pelanggan</p>
            </div>
        </div>

        <div class="content-wrap">
            <div class="card-premium">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pengirim / Customer</th>
                                <th>Bank</th>
                                <th>Jumlah</th>
                                <th>Tanggal Transfer</th>
                                <th>Status</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                            $res = mysqli_query($koneksi, "SELECT pc.*, p.nama_penerima FROM payment_confirmations pc 
                                                           JOIN pesanan p ON pc.id_pesanan = p.id_pesanan 
                                                           WHERE p.id_cabang = '$id_cabang'
                                                           ORDER BY pc.created_at DESC");
                            $confs = [];
                            while($c = mysqli_fetch_assoc($res)): 
                                $confs[] = $c;
                            ?>
                            <tr>
                                <td><code style="font-weight: 700; color: #1A1A2E; background: #F0F0FF; padding: 3px 8px; border-radius: 6px;">#<?php echo $c['id_pesanan']; ?></code></td>
                                <td>
                                    <div style="font-weight: 700; color: #1A1A2E;"><?php echo htmlspecialchars($c['nama_pengirim']); ?></div>
                                    <div style="font-size: 0.78rem; color: #9CA3AF;">Penerima: <?php echo htmlspecialchars($c['nama_penerima']); ?></div>
                                </td>
                                <td><span style="font-weight: 600; color: #4A4A6A;"><?php echo htmlspecialchars($c['bank_pengirim']); ?></span></td>
                                <td><span style="color: #C0392B; font-weight: 700;">Rp <?php echo number_format($c['jumlah_transfer']); ?></span></td>
                                <td style="color: #6A6A8A; font-size: 0.82rem;"><?php echo date('d/m/Y', strtotime($c['tanggal_transfer'])); ?></td>
                                <td>
                                    <?php if($c['status']=='pending'): ?>
                                        <span class="status-badge badge-pending"><i class="bi bi-clock"></i> Pending</span>
                                    <?php elseif($c['status']=='approved'): ?>
                                        <span class="status-badge badge-approved"><i class="bi bi-check-circle"></i> Approved</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                        <button class="btn-action btn-view" data-bs-toggle="modal" data-bs-target="#bukti<?php echo $c['id']; ?>">
                                            <i class="bi bi-image"></i> Bukti
                                        </button>
                                        <?php if($c['status']=='pending'): ?>
                                            <a href="konfirmasi.php?approve=<?php echo $c['id']; ?>" class="btn-action btn-approve" onclick="return confirm('Setujui pembayaran ini?')">
                                                <i class="bi bi-check-lg"></i> Setuju
                                            </a>
                                            <a href="konfirmasi.php?reject=<?php echo $c['id']; ?>" class="btn-action btn-reject" onclick="return confirm('Tolak pembayaran ini?')">
                                                <i class="bi bi-x"></i> Tolak
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if (count($confs) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-receipt-cutoff fs-2 opacity-50 mb-2 d-block"></i>
                                    Belum ada konfirmasi pembayaran masuk.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals outside for clean HTML -->
    <?php foreach($confs as $c): ?>
    <div class="modal fade" id="bukti<?php echo $c['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Transfer #<?php echo $c['id_pesanan']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../assets/bukti_bayar/<?php echo $c['bukti_foto']; ?>" class="bukti-img" onerror="this.src='https://placehold.co/400?text=Bukti+Foto+Tidak+Ditemukan'">
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
