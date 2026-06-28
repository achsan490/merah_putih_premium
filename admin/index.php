<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config.php'; 

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: login.php");
    exit;
}

$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
$branch_filter_sql = "";
if ($id_cabang > 1) {
    $branch_filter_sql = " AND id_cabang = '$id_cabang'";
}

try {
    // Query weekly sales (last 7 days)
    $sales_data_online = [0, 0, 0, 0, 0, 0, 0];
    $sales_data_offline = [0, 0, 0, 0, 0, 0, 0];
    $day_names = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('D', strtotime("-$i days"));
        
        $days_in = [
            'Mon' => 'Sen',
            'Tue' => 'Sel',
            'Wed' => 'Rab',
            'Thu' => 'Kam',
            'Fri' => 'Jum',
            'Sat' => 'Sab',
            'Sun' => 'Min'
        ];
        $day_names[] = $days_in[$day_name] ?? $day_name;
        
        $q_online = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE DATE(tgl_pesan) = '$date' AND tipe_pesanan = 'online' AND status IN ('dikirim', 'selesai') $branch_filter_sql");
        $d_online = mysqli_fetch_assoc($q_online);
        $sales_data_online[6 - $i] = (int)($d_online['total'] ?? 0);
        
        $q_offline = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE DATE(tgl_pesan) = '$date' AND tipe_pesanan = 'offline' $branch_filter_sql");
        $d_offline = mysqli_fetch_assoc($q_offline);
        $sales_data_offline[6 - $i] = (int)($d_offline['total'] ?? 0);
    }
} catch (Throwable $e) {
    die("<h3>Dashboard Error: Gagal memuat data penjualan</h3><p>Pesan Error: " . $e->getMessage() . " di baris " . $e->getLine() . "</p>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MerahPutih</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F0F4FF; overflow-x: hidden; }

        /* ── TOPBAR ── */
        .topbar {
            height: 68px;
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .topbar-title h1 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1A1A2E;
        }
        .topbar-title p {
            font-size: 0.78rem;
            color: #8A8AA0;
            margin-top: 1px;
        }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .date-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #F8F8FF;
            border: 1px solid #E8E8F5;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #4A4A6A;
        }
        .date-badge i { color: #C0392B; }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #FEF0EE;
            border: 1px solid #FECDC8;
            color: #C0392B;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-logout:hover { background: #C0392B; color: white; border-color: #C0392B; }

        /* ── KPI CARDS ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .kpi-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); }
        .kpi-card::before {
            content: '';
            position: absolute;
            width: 120px; height: 120px;
            border-radius: 50%;
            top: -30px; right: -30px;
            opacity: 0.12;
        }
        .kpi-card.blue::before { background: #3B82F6; }
        .kpi-card.amber::before { background: #F59E0B; }
        .kpi-card.green::before { background: #10B981; }
        .kpi-card.red::before { background: #EF4444; }

        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }
        .kpi-card.blue .kpi-icon { background: #EFF6FF; color: #3B82F6; }
        .kpi-card.amber .kpi-icon { background: #FFFBEB; color: #F59E0B; }
        .kpi-card.green .kpi-icon { background: #ECFDF5; color: #10B981; }
        .kpi-card.red .kpi-icon { background: #FEF2F2; color: #EF4444; }

        .kpi-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9A9AB0;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1A1A2E;
            line-height: 1;
        }
        .kpi-trend {
            margin-top: 10px;
            font-size: 0.78rem;
            color: #10B981;
            font-weight: 600;
        }
        .kpi-trend.warn { color: #F59E0B; }

        /* ── BOTTOM ROW ── */
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .chart-card h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1A1A2E;
            margin-bottom: 4px;
        }
        .chart-card p {
            font-size: 0.78rem;
            color: #9A9AB0;
            margin-bottom: 24px;
        }

        .shortcuts-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .shortcuts-card h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1A1A2E;
            margin-bottom: 20px;
        }

        .shortcut-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.25s;
            margin-bottom: 10px;
        }
        .shortcut-btn:last-child { margin-bottom: 0; }
        .shortcut-btn .sc-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .shortcut-btn.kasir { background: #1A1A2E; color: white; }
        .shortcut-btn.kasir .sc-icon { background: rgba(255,255,255,0.15); color: white; }
        .shortcut-btn.kasir:hover { background: #0F3460; }

        .shortcut-btn.orders { background: #FEF2F2; color: #C0392B; border: 1px solid #FECDC8; }
        .shortcut-btn.orders .sc-icon { background: #C0392B; color: white; }
        .shortcut-btn.orders:hover { background: #C0392B; color: white; border-color: #C0392B; }
        .shortcut-btn.orders:hover .sc-icon { background: rgba(255,255,255,0.2); }

        .shortcut-btn.products { background: #F8F8FF; color: #4A4A6A; border: 1px solid #E8E8F5; }
        .shortcut-btn.products .sc-icon { background: #6366F1; color: white; }
        .shortcut-btn.products:hover { background: #6366F1; color: white; border-color: #6366F1; }
        .shortcut-btn.products:hover .sc-icon { background: rgba(255,255,255,0.2); }

        .shortcut-btn .sc-arrow { margin-left: auto; opacity: 0.5; }

        /* ── CONTENT WRAPPER ── */
        .content-wrapper {
            padding: 28px 32px;
        }

        /* ── CHART LEGEND ── */
        .chart-legend {
            display: flex; gap: 20px; margin-bottom: 16px;
        }
        .legend-item {
            display: flex; align-items: center; gap: 7px;
            font-size: 0.8rem; font-weight: 600; color: #6A6A8A;
        }
        .legend-dot { width: 10px; height: 10px; border-radius: 3px; }

        @media (max-width: 1200px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .bottom-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .content-wrapper { padding: 16px; }
            .topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">
                <h1>Dashboard Overview</h1>
                <p>Selamat datang kembali — pantau performa toko Anda</p>
            </div>
            <div class="topbar-actions">
                <div class="date-badge">
                    <i class="bi bi-calendar3"></i>
                    <?php echo date('d F Y'); ?>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card blue">
                    <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="kpi-label">Total Produk</div>
                    <div class="kpi-value">
                        <?php 
                        $q_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk");
                        $d_p = mysqli_fetch_assoc($q_p);
                        echo $d_p['total'];
                        ?>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-box me-1"></i> Item aktif di katalog</div>
                </div>

                <div class="kpi-card amber">
                    <div class="kpi-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="kpi-label">Pesanan Pending</div>
                    <div class="kpi-value">
                        <?php 
                        $q_o = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending' AND tipe_pesanan='online' $branch_filter_sql");
                        $d_o = mysqli_fetch_assoc($q_o);
                        echo $d_o['total'];
                        ?>
                    </div>
                    <div class="kpi-trend warn"><i class="bi bi-exclamation-triangle me-1"></i> Perlu dikonfirmasi</div>
                </div>

                <div class="kpi-card green">
                    <div class="kpi-icon"><i class="bi bi-globe2"></i></div>
                    <div class="kpi-label">Omset Online</div>
                    <div class="kpi-value" style="font-size: 1.1rem;">
                        <?php 
                        $q_s = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE tipe_pesanan='online' AND status IN ('dikirim','selesai') $branch_filter_sql");
                        $d_s = mysqli_fetch_assoc($q_s);
                        echo "Rp " . number_format($d_s['total'] ?? 0, 0, ',', '.');
                        ?>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-arrow-up me-1"></i> Total penjualan online</div>
                </div>

                <div class="kpi-card red">
                    <div class="kpi-icon"><i class="bi bi-pc-display-horizontal"></i></div>
                    <div class="kpi-label">Omset Kasir</div>
                    <div class="kpi-value" style="font-size: 1.1rem;">
                        <?php 
                        $q_k = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE tipe_pesanan='offline' $branch_filter_sql");
                        $d_k = mysqli_fetch_assoc($q_k);
                        echo "Rp " . number_format($d_k['total'] ?? 0, 0, ',', '.');
                        ?>
                    </div>
                    <div class="kpi-trend" style="color: #EF4444;"><i class="bi bi-shop me-1"></i> Total penjualan offline</div>
                </div>
            </div>

            <!-- Bottom Row: Chart + Shortcuts -->
            <div class="bottom-row">
                <!-- Sales Chart -->
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2>Grafik Penjualan</h2>
                            <p>7 hari terakhir – online vs kasir offline</p>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #C0392B;"></div>
                                Online
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #1A1A2E;"></div>
                                Kasir
                            </div>
                        </div>
                    </div>
                    <canvas id="salesChart" style="max-height: 280px;"></canvas>
                </div>

                <!-- Quick Shortcuts -->
                <div class="shortcuts-card">
                    <h2>Aksi Cepat</h2>
                    <a href="../kasir/index.php" target="_blank" class="shortcut-btn kasir">
                        <div class="sc-icon"><i class="bi bi-pc-display-horizontal"></i></div>
                        <div>
                            <div>Buka Kasir Offline</div>
                            <div style="font-weight: 400; font-size: 0.75rem; opacity: 0.7; margin-top: 1px;">POS System</div>
                        </div>
                        <i class="bi bi-box-arrow-up-right sc-arrow"></i>
                    </a>
                    <a href="pesanan.php" class="shortcut-btn orders">
                        <div class="sc-icon"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div>Pesanan Baru</div>
                            <div style="font-weight: 400; font-size: 0.75rem; margin-top: 1px;">
                                <?php echo $d_o['total']; ?> pending
                            </div>
                        </div>
                        <i class="bi bi-arrow-right sc-arrow"></i>
                    </a>
                    <a href="produk.php" class="shortcut-btn products">
                        <div class="sc-icon"><i class="bi bi-bag-plus"></i></div>
                        <div>
                            <div>Kelola Produk</div>
                            <div style="font-weight: 400; font-size: 0.75rem; margin-top: 1px;">
                                <?php echo $d_p['total']; ?> produk terdaftar
                            </div>
                        </div>
                        <i class="bi bi-arrow-right sc-arrow"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($day_names); ?>,
            datasets: [
                {
                    label: 'Online (Rp)',
                    data: <?php echo json_encode($sales_data_online); ?>,
                    backgroundColor: 'rgba(192, 57, 43, 0.85)',
                    borderColor: 'rgba(192, 57, 43, 1)',
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false,
                },
                {
                    label: 'Kasir Offline (Rp)',
                    data: <?php echo json_encode($sales_data_offline); ?>,
                    backgroundColor: 'rgba(26, 26, 46, 0.8)',
                    borderColor: 'rgba(26, 26, 46, 1)',
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'white',
                    titleColor: '#1A1A2E',
                    bodyColor: '#6A6A8A',
                    borderColor: '#E8E8F5',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ' Rp ' + ctx.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                    ticks: {
                        font: { size: 11, family: 'Plus Jakarta Sans' },
                        color: '#9A9AB0',
                        callback: function(v) {
                            if (v >= 1000000) return 'Rp ' + (v/1000000).toFixed(1) + 'Jt';
                            if (v >= 1000) return 'Rp ' + (v/1000).toFixed(0) + 'Rb';
                            return 'Rp ' + v;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, family: 'Plus Jakarta Sans' }, color: '#9A9AB0' }
                }
            }
        }
    });
    </script>
</body>
</html>