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
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];
        $day_names[] = $days_in[$day_name] ?? $day_name;
        
        // Online
        $q_online = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE DATE(tgl_pesan) = '$date' AND tipe_pesanan = 'online' AND status IN ('dikirim', 'selesai') $branch_filter_sql");
        $d_online = mysqli_fetch_assoc($q_online);
        $sales_data_online[6 - $i] = (int)($d_online['total'] ?? 0);
        
        // Offline
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MerahPutih Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --grad-merah: linear-gradient(180deg, #8b0000 0%, #e63946 100%);
            --merah-tua: #8b0000;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; overflow-x: hidden; }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--grad-merah);
            position: fixed;
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transition: 0.3s;
            border-radius: 0 50px 50px 0;
            margin: 5px 0;
            margin-right: 20px;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active {
            background: white;
            color: var(--merah-tua) !important;
            font-weight: 600;
        }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }

        /* Main Content */
        .main-content { margin-left: 260px; padding: 40px; transition: all 0.3s; }
        
        /* Stats Card */
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 25px;
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 0; left: -260px; }
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold">Selamat Datang, Admin!</h2>
                <p class="text-muted">Ini ringkasan tokomu hari ini <span class="badge bg-danger text-white rounded-pill ms-1 fw-bold"><?php echo htmlspecialchars($_SESSION['nama_cabang'] ?? 'Toko Utama (Pusat)'); ?></span></p>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-dark shadow-sm p-3 rounded-pill">
                    <i class="bi bi-calendar3 me-2 text-danger"></i> <?php echo date('d F Y'); ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger ms-2 rounded-pill px-3">Logout</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box"></i>
                    </div>
                    <h6 class="text-muted small text-uppercase fw-bold">Total Produk</h6>
                    <h3 class="fw-800">
                        <?php 
                        $q_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk");
                        $d_p = mysqli_fetch_assoc($q_p);
                        echo $d_p['total'];
                        ?>
                    </h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h6 class="text-muted small text-uppercase fw-bold text-warning">Pending (Online)</h6>
                    <h3 class="fw-800">
                        <?php 
                        $q_o = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending' AND tipe_pesanan='online' $branch_filter_sql");
                        $d_o = mysqli_fetch_assoc($q_o);
                        echo $d_o['total'];
                        ?>
                    </h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <h6 class="text-muted small text-uppercase fw-bold text-success">Omset Online</h6>
                    <h3 class="fw-800">
                        <?php 
                        $q_s = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE tipe_pesanan='online' AND status IN ('dikirim','selesai') $branch_filter_sql");
                        $d_s = mysqli_fetch_assoc($q_s);
                        echo "Rp " . number_format($d_s['total'] ?? 0, 0, ',', '.');
                        ?>
                    </h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-pc-display-horizontal"></i>
                    </div>
                    <h6 class="text-muted small text-uppercase fw-bold text-danger">Omset Kasir</h6>
                    <h3 class="fw-800">
                        <?php 
                        $q_k = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pesanan WHERE tipe_pesanan='offline' $branch_filter_sql");
                        $d_k = mysqli_fetch_assoc($q_k);
                        echo "Rp " . number_format($d_k['total'] ?? 0, 0, ',', '.');
                        ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card p-4 border-0 shadow-sm" style="border-radius: 20px;">
                    <h5 class="fw-bold mb-4">Grafik Penjualan Minggu Ini</h5>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                 <div class="card p-4 border-0 shadow-sm h-100" style="border-radius: 20px;">
                    <h5 class="fw-bold mb-4">Shortcut Cepat</h5>
                    <div class="d-grid gap-3">
                         <a href="../kasir/index.php" target="_blank" class="btn btn-danger py-3 rounded-4 text-start text-white shadow-sm">
                             <i class="bi bi-pc-display-horizontal me-2"></i> Buka Kasir Offline
                         </a>
                         <a href="pesanan.php" class="btn btn-outline-danger py-3 rounded-4 text-start">
                            <i class="bi bi-receipt me-2"></i> Cek Pesanan Baru
                         </a>
                         <a href="produk.php" class="btn btn-outline-dark py-3 rounded-4 text-start">
                             <i class="bi bi-bag me-2"></i> Kelola Produk
                         </a>
                    </div>
                 </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                    backgroundColor: 'rgba(230, 57, 70, 0.7)',
                    borderColor: 'rgba(230, 57, 70, 1)',
                    borderWidth: 1,
                    borderRadius: 10
                },
                {
                    label: 'Kasir Offline (Rp)',
                    data: <?php echo json_encode($sales_data_offline); ?>,
                    backgroundColor: 'rgba(139, 0, 0, 0.7)',
                    borderColor: 'rgba(139, 0, 0, 1)',
                    borderWidth: 1,
                    borderRadius: 10
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    </script>
</body>
</html>