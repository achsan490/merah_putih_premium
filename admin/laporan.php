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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --grad-merah: linear-gradient(180deg, #8b0000 0%, #e63946 100%);
            --merah-tua: #8b0000;
            --merah-terang: #e63946;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; overflow-x: hidden; }

        .main-content { margin-left: 260px; padding: 40px; transition: all 0.3s; }
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
        
        /* Stats card styling */
        .stat-box {
            border: none;
            border-radius: 15px;
            padding: 20px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            height: 100%;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        /* Printable stylesheet */
        @media print {
            body { background: white; color: black; }
            .sidebar, .navbar, .card-filter, .btn-print, .btn-action, .btn-close, .modal-header, .modal-footer { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .card-premium { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .table-responsive { overflow: visible !important; }
            .table { width: 100% !important; border: 1px solid #ddd !important; }
            .stat-box { border: 1px solid #ddd !important; box-shadow: none !important; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
        }

        .print-header { display: none; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
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

        <div class="d-flex justify-content-between align-items-center mb-4 btn-print">
            <div>
                <h2 class="fw-bold m-0">Laporan Penjualan</h2>
                <p class="text-muted m-0">Rekapitulasi keuangan bisnis online & offline Anda.</p>
            </div>
            <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-printer me-2"></i> Cetak Laporan
            </button>
        </div>

        <!-- Tipe Transaksi Tabs -->
        <div class="mb-4 btn-print">
            <div class="nav nav-pills justify-content-center bg-white p-2 shadow-sm rounded-pill" style="max-width: 450px; margin: 0 auto;">
                <a class="nav-link rounded-pill px-4 fw-semibold <?php echo ($tipe == 'all') ? 'active bg-danger text-white' : 'text-danger'; ?>" 
                   href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=all&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                    <i class="bi bi-border-all me-1"></i> Semua
                </a>
                <a class="nav-link rounded-pill px-4 fw-semibold <?php echo ($tipe == 'online') ? 'active bg-danger text-white' : 'text-danger'; ?>" 
                   href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=online&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                    <i class="bi bi-globe2 me-1"></i> Online
                </a>
                <a class="nav-link rounded-pill px-4 fw-semibold <?php echo ($tipe == 'offline') ? 'active bg-danger text-white' : 'text-danger'; ?>" 
                   href="laporan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_selesai=<?php echo $tgl_selesai; ?>&tipe=offline&status=<?php echo $status; ?>&id_cabang=<?php echo $cabang_filter; ?>">
                    <i class="bi bi-pc-display-horizontal me-1"></i> Offline (Kasir)
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card card-premium bg-white p-4 mb-4 card-filter">
            <h5 class="fw-bold mb-3"><i class="bi bi-funnel-fill text-danger me-2"></i>Filter Laporan</h5>
            <form method="GET" action="laporan.php">
                <input type="hidden" name="tipe" value="<?php echo $tipe; ?>">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?php echo $tgl_mulai; ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" class="form-control" value="<?php echo $tgl_selesai; ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted">Cabang</label>
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
                    <div class="col-md-1 col-sm-6">
                        <label class="form-label small fw-bold text-muted">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>Semua</option>
                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="dikirim" <?php echo ($status == 'dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                            <option value="selesai" <?php echo ($status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100 rounded-3 py-2 fw-semibold">
                            Cari Data <i class="bi bi-search ms-1 small"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Metrics Row -->
        <div class="row g-3 mb-4">
            <!-- Omset -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Total Omset</small>
                        <h4 class="fw-bold m-0 text-dark">Rp <?php echo number_format($total_omset, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <!-- Total Trx -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Total Transaksi</small>
                        <h4 class="fw-bold m-0 text-dark"><?php echo $total_trx; ?> Nota</h4>
                    </div>
                </div>
            </div>
            <!-- Total Qty -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Barang Terjual</small>
                        <h4 class="fw-bold m-0 text-dark"><?php echo $total_barang; ?> Pcs</h4>
                    </div>
                </div>
            </div>
            <!-- Average Sale -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Rata-rata Nota</small>
                        <h4 class="fw-bold m-0 text-dark">Rp <?php echo number_format($avg_transaksi, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Data Table -->
        <div class="card card-premium bg-white p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-table text-danger me-2"></i>Rincian Nota Transaksi</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Nota</th>
                            <th>Tipe / Cabang</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total Bayar</th>
                            <th>Status</th>
                            <th class="text-center btn-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($notas) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-folder-x fs-1 opacity-25"></i>
                                    <p class="mt-2">Tidak ada transaksi ditemukan pada periode filter ini.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notas as $o): ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?php echo $o['id_pesanan']; ?></td>
                                <td>
                                    <?php if(($o['tipe_pesanan'] ?? 'online') == 'offline'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Offline (Kasir)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Online (Web)</span>
                                    <?php endif; ?>
                                    <div class="small text-secondary mt-1 font-monospace" style="font-size: 0.78rem;"><i class="bi bi-geo-alt-fill me-1"></i><?php echo htmlspecialchars($o['nama_cabang'] ?: 'Toko Utama (Pusat)'); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($o['nama_penerima']); ?></strong>
                                    <?php if($o['no_telp'] && $o['no_telp'] != '-'): ?>
                                        <br><small class="text-muted font-monospace"><?php echo htmlspecialchars($o['no_telp']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></small></td>
                                <td class="fw-bold text-danger">Rp <?php echo number_format($o['total_bayar'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = 'bg-secondary';
                                    $status_text = strtoupper($o['status']);
                                    if ($o['status'] == 'pending') {
                                        $badge_class = 'bg-warning text-dark';
                                    } elseif ($o['status'] == 'dikirim') {
                                        $badge_class = 'bg-primary';
                                    } elseif ($o['status'] == 'selesai') {
                                        $badge_class = 'bg-success';
                                    }
                                    ?>
                                    <span class="badge rounded-pill <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td class="text-center btn-print">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-action" data-bs-toggle="modal" data-bs-target="#nota<?php echo $o['id_pesanan']; ?>">
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

    <!-- Modals for Order Details -->
    <?php foreach ($notas as $o): ?>
    <div class="modal fade" id="nota<?php echo $o['id_pesanan']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold text-danger">Detail Nota #<?php echo $o['id_pesanan']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 mb-3 small">
                        <div class="row g-2">
                            <div class="col-6"><strong>Tipe:</strong> <?php echo ($o['tipe_pesanan'] ?? 'online') == 'offline' ? 'Offline (Kasir)' : 'Online (Website)'; ?></div>
                            <div class="col-6"><strong>Status:</strong> <?php echo strtoupper($o['status']); ?></div>
                            <div class="col-6"><strong>Metode:</strong> <?php echo strtoupper($o['metode_bayar']); ?></div>
                            <div class="col-6"><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></div>
                            <div class="col-12"><strong>Cabang:</strong> <?php echo htmlspecialchars($o['nama_cabang'] ?: 'Toko Utama (Pusat / Online)'); ?></div>
                            <div class="col-12 mt-1"><strong>Alamat/Ket:</strong> <?php echo htmlspecialchars($o['alamat_penerima']); ?></div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-2">Daftar Barang Belanja</h6>
                    <table class="table table-sm align-middle font-monospace" style="font-size:0.85rem;">
                        <thead>
                            <tr class="table-light">
                                <th>Barang</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $det = mysqli_query($koneksi, "SELECT detail_pesanan.*, produk.nama_produk FROM detail_pesanan JOIN produk ON detail_pesanan.id_produk = produk.id WHERE id_pesanan='".$o['id_pesanan']."'");
                            while($d = mysqli_fetch_assoc($det)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['nama_produk']); ?></td>
                                <td class="text-center"><?php echo $d['jumlah']; ?></td>
                                <td class="text-end">Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="table-light fw-bold">
                                <td colspan="2">Total Akhir:</td>
                                <td class="text-end text-danger">Rp <?php echo number_format($o['total_bayar'], 0, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
