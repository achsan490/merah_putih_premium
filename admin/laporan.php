<?php
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: login.php");
    exit;
}

// Get filter inputs or set defaults
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$cabang_filter = isset($_GET['id_cabang']) ? $_GET['id_cabang'] : 'all';

$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
// Force branch limit if not superadmin (Pusat / id_cabang == 1)
if ($id_cabang > 1) {
    $cabang_filter = $id_cabang;
}

// Build SQL where clause
$where = "WHERE DATE(pesanan.tgl_pesan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";

if ($tipe !== 'all') {
    $where .= " AND pesanan.tipe_pesanan = '$tipe'";
}

if ($status !== 'all') {
    $where .= " AND pesanan.status = '$status'";
}

if ($cabang_filter !== 'all') {
    $cabang_filter_int = (int)$cabang_filter;
    $where .= " AND pesanan.id_cabang = '$cabang_filter_int'";
}

// 1. Total Omset (Revenue)
$q_omset = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan $where");
$d_omset = mysqli_fetch_assoc($q_omset);
$total_omset = $d_omset['total'] ?? 0;

// 2. Total Transaksi
$q_trx = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan $where");
$d_trx = mysqli_fetch_assoc($q_trx);
$total_trx = $d_trx['total'] ?? 0;

// 3. Total Barang Terjual
$q_barang = mysqli_query($koneksi, "SELECT SUM(detail_pesanan.jumlah) as total FROM detail_pesanan JOIN pesanan ON detail_pesanan.id_pesanan = pesanan.id_pesanan $where");
$d_barang = mysqli_fetch_assoc($q_barang);
$total_barang = $d_barang['total'] ?? 0;

// 4. Rata-rata Belanja per Transaksi
$avg_transaksi = $total_trx > 0 ? round($total_omset / $total_trx) : 0;

// Fetch filtered transactions (join with cabang for names)
$res = mysqli_query($koneksi, "
    SELECT pesanan.*, cabang.nama_cabang 
    FROM pesanan 
    LEFT JOIN cabang ON pesanan.id_cabang = cabang.id_cabang 
    $where 
    ORDER BY pesanan.id_pesanan DESC
");
$notas = [];
while ($row = mysqli_fetch_assoc($res)) {
    $notas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - MerahPutih Admin</title>
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
        
        .card-premium { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 28px; overflow: hidden; }
        .stat-card { background: white; border: none; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px; }
        .stat-card-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        
        .nav-pills-custom { background: #EAEAFF; padding: 6px; border-radius: 14px; display: inline-flex; gap: 4px; }
        .nav-pills-custom .nav-link { border-radius: 10px; font-size: 0.85rem; font-weight: 700; color: #6A6A8A; padding: 10px 20px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .nav-pills-custom .nav-link.active { background: #1A1A2E; color: white; }

        .table thead th { background: #F8F8FF; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9A9AB0; border-bottom: 1px solid #EEEEF8; padding: 14px 16px; }
        .table tbody td { padding: 14px 16px; border-bottom: 1px solid #F5F5FF; vertical-align: middle; font-size: 0.88rem; }
        .table tbody tr:hover td { background: #FAFAFF; }
        .table tbody tr:last-child td { border-bottom: none; }

        .btn-admin { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 20px; border-radius: 12px; font-size: 0.88rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-danger-solid { background: linear-gradient(135deg, #922B21, #E74C3C); color: white; } .btn-danger-solid:hover { opacity: 0.9; }
        .btn-action-outline { background: white; color: #4A4A6A; border: 1.5px solid #E2E2EC; padding: 8px 16px; font-weight: 600; border-radius: 10px; font-size: 0.8rem; }
        .btn-action-outline:hover { background: #FEF0EE; color: #C0392B; border-color: #FECDC8; }

        .form-control, .form-select { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.88rem; transition: border-color 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #4A4A6A; margin-bottom: 6px; }

        .modal-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.15); }
        .modal-header { background: #F8F8FF; border-bottom: 1px solid #EEEEF8; border-radius: 20px 20px 0 0; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #EEEEF8; padding: 16px 24px; border-radius: 0 0 20px 20px; }

        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-pending { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .badge-dikirim { background: #EFF6FF; color: #6366F1; border: 1px solid #C7D2FE; }
        .badge-selesai { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-paid { background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; }
        .badge-batal { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .badge-default { background: #F8F8FF; color: #6A6A8A; border: 1px solid #E8E8F5; }

        /* Printable stylesheet */
        @media print {
            body { background: white !important; color: black !important; }
            .sidebar, .navbar, .card-filter, .btn-print, .btn-close, .modal-header, .modal-footer, .topbar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .content-wrap { padding: 0 !important; }
            .card-premium { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .table-responsive { overflow: visible !important; }
            .table { width: 100% !important; border: 1px solid #ddd !important; }
            .stat-card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
        }
        .print-header { display: none; }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Menu -->
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        
        <!-- Header for print documents -->
        <div class="print-header">
            <h2 class="fw-bold">LAPORAN PENJUALAN MERAH PUTIH</h2>
            <p class="text-muted">Periode: <?php echo date('d/m/Y', strtotime($tgl_mulai)); ?> s.d. <?php echo date('d/m/Y', strtotime($tgl_selesai)); ?></p>
            <p class="small text-muted" style="margin-top:-10px;">Filter: Tipe = <?php echo strtoupper($tipe); ?> | Status = <?php echo strtoupper($status); ?></p>
            <hr>
        </div>

        <div class="topbar">
            <div>
                <h1>Laporan Penjualan</h1>
                <p>Rekapitulasi keuangan bisnis online & offline Anda</p>
            </div>
            <div class="btn-print">
                <button onclick="window.print()" class="btn-admin btn-danger-solid">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </button>
            </div>
        </div>

        <div class="content-wrap">
            <!-- Tipe Transaksi Tabs -->
            <div class="mb-4 text-center btn-print">
                <div class="nav-pills-custom">
                    <a class="nav-link <?php echo ($tipe == 'all') ? 'active' : ''; ?>" 
                       href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=all&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                        <i class="bi bi-border-all"></i> Semua
                    </a>
                    <a class="nav-link <?php echo ($tipe == 'online') ? 'active' : ''; ?>" 
                       href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=online&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                        <i class="bi bi-globe2"></i> Online
                    </a>
                    <a class="nav-link <?php echo ($tipe == 'offline') ? 'active' : ''; ?>" 
                       href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=offline&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                        <i class="bi bi-pc-display-horizontal"></i> Offline (Kasir)
                    </a>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card-premium mb-4 card-filter">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-funnel text-danger me-2"></i>Filter Laporan</h5>
                <form method="GET" action="laporan.php">
                    <input type="hidden" name="tipe" value="<?php echo $tipe; ?>">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" value="<?php echo $tgl_mulai; ?>">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" value="<?php echo $tgl_selesai; ?>">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label">Cabang</label>
                            <?php if ($id_cabang > 1): ?>
                                <!-- Branch Admin: Locked to their branch -->
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['nama_cabang']); ?>" readonly>
                                <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
                            <?php else: ?>
                                <!-- Super Admin: Can filter any branch -->
                                <select name="id_cabang" class="form-select">
                                    <option value="all" <?php echo ($cabang_filter == 'all') ? 'selected' : ''; ?>>Semua Cabang</option>
                                    <?php
                                    $q_c = mysqli_query($koneksi, "SELECT * FROM cabang ORDER BY id_cabang ASC");
                                    while($cb = mysqli_fetch_assoc($q_c)):
                                    ?>
                                        <option value="<?php echo $cb['id_cabang']; ?>" <?php echo ($cabang_filter == $cb['id_cabang']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cb['nama_cabang']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>Semua</option>
                                <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Dibayar</option>
                                <option value="dikirim" <?php echo ($status == 'dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="selesai" <?php echo ($status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                <option value="batal" <?php echo ($status == 'batal') ? 'selected' : ''; ?>>Batal</option>
                            </select>
                        </div>
                        <div class="col-md-1 col-sm-6 d-flex align-items-end">
                            <button type="submit" class="btn-admin btn-danger-solid w-100 py-2">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Metrics Row -->
            <div class="row g-4 mb-4">
                <!-- Omset -->
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold" style="font-size:0.7rem; color:#8A8AA0; letter-spacing:0.5px;">Total Omset</div>
                            <h4 class="fw-bold m-0 text-dark" style="font-size: 1.25rem;">Rp <?php echo number_format($total_omset, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
                <!-- Total Trx -->
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold" style="font-size:0.7rem; color:#8A8AA0; letter-spacing:0.5px;">Total Transaksi</div>
                            <h4 class="fw-bold m-0 text-dark" style="font-size: 1.25rem;"><?php echo $total_trx; ?> Nota</h4>
                        </div>
                    </div>
                </div>
                <!-- Total Qty -->
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold" style="font-size:0.7rem; color:#8A8AA0; letter-spacing:0.5px;">Barang Terjual</div>
                            <h4 class="fw-bold m-0 text-dark" style="font-size: 1.25rem;"><?php echo $total_barang; ?> Pcs</h4>
                        </div>
                    </div>
                </div>
                <!-- Average Sale -->
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold" style="font-size:0.7rem; color:#8A8AA0; letter-spacing:0.5px;">Rata-rata Nota</div>
                            <h4 class="fw-bold m-0 text-dark" style="font-size: 1.25rem;">Rp <?php echo number_format($avg_transaksi, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Data Table -->
            <div class="card-premium">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-table text-danger me-2"></i>Rincian Nota Transaksi</h5>
                
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>No. Nota</th>
                                <th>Tipe / Cabang</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                                <th style="text-align:center;" class="btn-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($notas) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-folder-x fs-2 opacity-50 mb-2 d-block"></i>
                                        Tidak ada transaksi ditemukan pada periode filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notas as $o): ?>
                                <tr>
                                    <td><code style="font-weight: 700; color: #1A1A2E; background: #F0F0FF; padding: 3px 8px; border-radius: 6px;">#<?php echo $o['id_pesanan']; ?></code></td>
                                    <td>
                                        <?php if(($o['tipe_pesanan'] ?? 'online') == 'offline'): ?>
                                            <span class="status-badge" style="background: #1A1A2E; color: white;"><i class="bi bi-pc-display-horizontal"></i> Kasir</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE;"><i class="bi bi-globe2"></i> Online</span>
                                        <?php endif; ?>
                                        <div class="small text-secondary mt-1" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($o['nama_cabang'] ?: 'Toko Utama (Pusat)'); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #1A1A2E;"><?php echo htmlspecialchars($o['nama_penerima']); ?></div>
                                        <?php if($o['no_telp'] && $o['no_telp'] != '-'): ?>
                                            <div style="font-size: 0.78rem; color: #9CA3AF;"><?php echo htmlspecialchars($o['no_telp']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #6A6A8A; font-size: 0.82rem;"><?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></td>
                                    <td><span style="color: #C0392B; font-weight: 700;">Rp <?php echo number_format($o['total_bayar'], 0, ',', '.'); ?></span></td>
                                    <td>
                                        <?php 
                                        $badge_class = 'badge-default';
                                        $status_text = strtoupper($o['status']);
                                        $status_icon = 'bi-circle';
                                        if($o['status'] == 'pending') { $badge_class = 'badge-pending'; $status_icon = 'bi-clock'; }
                                        elseif($o['status'] == 'dikirim') { $badge_class = 'badge-dikirim'; $status_icon = 'bi-truck'; }
                                        elseif($o['status'] == 'selesai') { $badge_class = 'badge-selesai'; $status_icon = 'bi-check-circle'; $status_text = 'SELESAI'; }
                                        elseif($o['status'] == 'paid') { $badge_class = 'badge-paid'; $status_icon = 'bi-wallet2'; $status_text = 'DIBAYAR'; }
                                        elseif($o['status'] == 'batal') { $badge_class = 'badge-batal'; $status_icon = 'bi-x-circle'; $status_text = 'BATAL'; }
                                        ?>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <i class="bi <?php echo $status_icon; ?>"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;" class="btn-print">
                                        <button class="btn-action-outline" data-bs-toggle="modal" data-bs-target="#nota<?php echo $o['id_pesanan']; ?>">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Order Details -->
    <?php foreach ($notas as $o): ?>
    <div class="modal fade" id="nota<?php echo $o['id_pesanan']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">Detail Nota #<?php echo $o['id_pesanan']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 rounded-3 mb-4 small" style="background: #F8F9FD; border: 1px solid #EAEAF2; color: #4A4A6A;">
                        <div class="row g-2">
                            <div class="col-6"><strong>Tipe:</strong> <?php echo ($o['tipe_pesanan'] ?? 'online') == 'offline' ? 'Offline (Kasir)' : 'Online (Website)'; ?></div>
                            <div class="col-6"><strong>Status:</strong> <?php echo strtoupper($o['status']); ?></div>
                            <div class="col-6"><strong>Metode:</strong> <?php echo strtoupper($o['metode_bayar']); ?></div>
                            <div class="col-6"><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></div>
                            <div class="col-12 mt-1"><strong>Cabang:</strong> <?php echo htmlspecialchars($o['nama_cabang'] ?: 'Toko Utama (Pusat / Online)'); ?></div>
                            <div class="col-12 mt-1"><strong>Alamat/Keterangan:</strong> <?php echo htmlspecialchars($o['alamat_penerima']); ?></div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-box-seam me-1 text-danger"></i>Daftar Belanja</h6>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle" style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                 $det = mysqli_query($koneksi, "
                                     SELECT detail_pesanan.*, produk.nama_produk, pk.nama_satuan 
                                     FROM detail_pesanan 
                                     JOIN produk ON detail_pesanan.id_produk = produk.id 
                                     LEFT JOIN produk_kemasan pk ON detail_pesanan.id_kemasan = pk.id_kemasan 
                                     WHERE id_pesanan='".$o['id_pesanan']."'
                                 ");
                                 while($d = mysqli_fetch_assoc($det)): ?>
                                 <tr>
                                     <td class="fw-semibold text-dark"><?php echo htmlspecialchars($d['nama_produk']) . ($d['nama_satuan'] ? ' (' . htmlspecialchars($d['nama_satuan']) . ')' : ''); ?></td>
                                     <td style="text-align:center;"><span class="badge bg-light text-dark border px-2"><?php echo $d['jumlah']; ?></span></td>
                                     <td style="text-align:right; font-weight: 700; color: #C0392B;">Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></td>
                                 </tr>
                                 <?php endwhile; ?>
                                <tr style="background: #F8F8FF;">
                                    <td colspan="2" class="fw-bold text-dark">Total Akhir:</td>
                                    <td style="text-align:right; font-weight: 800; color: #C0392B; font-size: 1rem;">Rp <?php echo number_format($o['total_bayar'], 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
