<?php 
// Tetap simpan logika notifikasi suara yang sudah jalan
$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
$cek_pesanan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending' AND id_cabang = '$id_cabang'");
$data_pesanan = mysqli_fetch_assoc($cek_pesanan);
$jumlah_pending = $data_pesanan['total'];
?>
<style>
    :root {
        --grad-merah: linear-gradient(180deg, #8b0000 0%, #e63946 100%);
        --merah-tua: #8b0000;
    }
    .sidebar {
        width: 260px; height: 100vh; background: var(--grad-merah);
        position: fixed; color: white; z-index: 1000;
        box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    }
    .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .nav-link-side {
        color: rgba(255,255,255,0.8); padding: 15px 25px; display: flex;
        align-items: center; transition: 0.3s; border-radius: 0 50px 50px 0;
        margin: 5px 20px 5px 0; text-decoration: none;
    }
    .nav-link-side:hover, .nav-link-side.active {
        background: white; color: var(--merah-tua) !important; font-weight: 600;
    }
    .nav-link-side i { font-size: 1.2rem; margin-right: 15px; }
    .badge-notif { font-size: 0.7rem; padding: 4px 8px; }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h4 class="fw-800 m-0">ADMIN <span class="opacity-50">MP</span></h4>
        <small class="opacity-75">Premium Control</small>
    </div>
    <div class="mt-4">
        <a href="index.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="produk.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'produk.php') ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i> Produk Kami
        </a>
        <a href="kategori.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'kategori.php') ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Kategori
        </a>
        <a href="pesanan.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'pesanan.php') ? 'active' : ''; ?>">
            <i class="bi bi-receipt"></i> Pesanan 
            <?php if($jumlah_pending > 0): ?>
                <span class="badge rounded-pill bg-danger ms-2 badge-notif"><?php echo $jumlah_pending; ?></span>
            <?php endif; ?>
        </a>
        <a href="konfirmasi.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'konfirmasi.php') ? 'active' : ''; ?>">
            <i class="bi bi-credit-card"></i> Konfirmasi Bayar
        </a>
        <a href="metode_bayar.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'metode_bayar.php') ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Metode Bayar
        </a>
        <a href="../kasir/index.php" target="_blank" class="nav-link-side">
            <i class="bi bi-pc-display-horizontal"></i> Kasir Offline
        </a>
        <a href="laporan.php" class="nav-link-side <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> Laporan Penjualan
        </a>
        <hr class="mx-4 opacity-25">
        <a href="../user/index.php" target="_blank" class="nav-link-side">
            <i class="bi bi-shop"></i> Lihat Toko
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
                alert('Ada pesanan baru masuk, sayang!');
                location.reload();
            }
        });
    }, 10000);
</script>