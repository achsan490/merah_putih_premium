<?php 
// Tetap simpan logika notifikasi suara yang sudah jalan
$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
$cek_pesanan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending' AND id_cabang = '$id_cabang'");
$data_pesanan = mysqli_fetch_assoc($cek_pesanan);
$jumlah_pending = $data_pesanan['total'];
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: #C0392B;
        --primary-dark: #922B21;
        --primary-light: #E74C3C;
        --navy: #1A1A2E;
        --navy-light: #16213E;
        --accent: #FF6B35;
        --sidebar-w: 268px;
    }

    * { font-family: 'Plus Jakarta Sans', sans-serif !important; }

    .sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        background: linear-gradient(180deg, #1A1A2E 0%, #16213E 60%, #0F3460 100%);
        position: fixed;
        left: 0; top: 0;
        color: white;
        z-index: 1000;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 30px rgba(0,0,0,0.25);
    }
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

    .sidebar-brand {
        padding: 28px 24px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        flex-shrink: 0;
    }
    .sidebar-brand .logo-text {
        font-size: 1.4rem;
        font-weight: 800;
        color: white;
        letter-spacing: -0.5px;
    }
    .sidebar-brand .logo-text span { color: var(--accent); }
    .sidebar-brand .logo-sub {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: rgba(255,255,255,0.4);
        margin-top: 2px;
    }
    .sidebar-brand .branch-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(192,57,43,0.25);
        border: 1px solid rgba(192,57,43,0.4);
        color: #FF9999;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        margin-top: 10px;
    }

    .sidebar-section-label {
        padding: 20px 24px 8px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: rgba(255,255,255,0.25);
    }

    .nav-link-side {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 24px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.25s ease;
        margin: 2px 12px;
        border-radius: 12px;
        position: relative;
    }
    .nav-link-side:hover {
        background: rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.9);
    }
    .nav-link-side.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(192,57,43,0.35);
    }
    .nav-link-side .nav-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        transition: all 0.25s;
    }
    .nav-link-side:not(.active) .nav-icon {
        background: rgba(255,255,255,0.07);
    }
    .nav-link-side.active .nav-icon {
        background: rgba(255,255,255,0.2);
    }
    .nav-link-side .badge-notif {
        margin-left: auto;
        background: var(--accent);
        color: white;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
        animation: pulseBadge 2s infinite;
    }
    @keyframes pulseBadge {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 16px 12px;
        border-top: 1px solid rgba(255,255,255,0.07);
        flex-shrink: 0;
    }
    .sidebar-footer a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        color: rgba(255,255,255,0.5);
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .sidebar-footer a:hover { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85); }

    .main-content {
        margin-left: var(--sidebar-w);
        min-height: 100vh;
        background: #F0F4FF;
        transition: margin-left 0.3s;
    }

    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .main-content { margin-left: 0; }
    }
</style>

<div class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="logo-text">MERAH<span>PUTIH</span></div>
        <div class="logo-sub">Admin Panel</div>
        <div class="branch-badge">
            <i class="bi bi-geo-alt-fill"></i>
            <?php echo htmlspecialchars($_SESSION['nama_cabang'] ?? 'Toko Utama'); ?>
        </div>
    </div>

    <div class="sidebar-section-label">Menu Utama</div>

    <a href="index.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-speedometer2"></i></div>
        Dashboard
    </a>
    <a href="produk.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'produk.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-box-seam"></i></div>
        Produk Kami
    </a>
    <a href="kategori.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'kategori.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-tags"></i></div>
        Kategori
    </a>

    <div class="sidebar-section-label">Penjualan</div>

    <a href="pesanan.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'pesanan.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-receipt"></i></div>
        Pesanan Online
        <?php if($jumlah_pending > 0): ?>
            <span class="badge-notif"><?php echo $jumlah_pending; ?></span>
        <?php endif; ?>
    </a>
    <a href="konfirmasi.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'konfirmasi.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-credit-card-2-back"></i></div>
        Konfirmasi Bayar
    </a>
    <a href="metode_bayar.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'metode_bayar.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-wallet2"></i></div>
        Metode Bayar
    </a>

    <div class="sidebar-section-label">Laporan & Tools</div>

    <a href="laporan.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
        Laporan Penjualan
    </a>
    <a href="../kasir/index.php" target="_blank" class="nav-link-side">
        <div class="nav-icon"><i class="bi bi-pc-display-horizontal"></i></div>
        Kasir Offline
        <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.7rem; opacity: 0.5;"></i>
    </a>

    <div class="sidebar-footer">
        <a href="../user/index.php" target="_blank">
            <i class="bi bi-shop" style="color: #4ECDC4;"></i>
            Lihat Toko Online
        </a>
        <a href="logout.php" style="color: rgba(255,107,107,0.7);">
            <i class="bi bi-box-arrow-right" style="color: #FF6B6B;"></i>
            Logout
        </a>
    </div>
</div>

<audio id="notifAudio"><source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg"></audio>

<script>
    var jumlahLama = <?php echo $jumlah_pending; ?>;
    setInterval(function(){
        fetch('cek_notif.php').then(r => r.json()).then(d => {
            if(d.jumlah > jumlahLama) {
                document.getElementById('notifAudio').play();
                alert('Ada pesanan baru masuk!');
                location.reload();
            }
        });
    }, 10000);
</script>