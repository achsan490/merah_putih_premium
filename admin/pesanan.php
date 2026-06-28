<?php 
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <title>Pesanan Masuk - MerahPutih Admin</title>
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
        .table tbody td { padding: 13px 16px; border-bottom: 1px solid #F5F5FF; vertical-align: middle; font-size: 0.87rem; }
        .table tbody tr:hover td { background: #FAFAFF; }
        .table tbody tr:last-child td { border-bottom: none; }
        .btn-action { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-detail { background: #F8F8FF; color: #4A4A6A; border: 1px solid #E8E8F5; }
        .btn-detail:hover { background: #EEEEFF; color: #1A1A2E; }
        .btn-send { background: #1A1A2E; color: white; } .btn-send:hover { background: #0F3460; color: white; }
        .btn-done { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; } .btn-done:hover { background: #059669; color: white; }
        .btn-cancel { background: #FEF0EE; color: #C0392B; border: 1px solid #FECDC8; } .btn-cancel:hover { background: #C0392B; color: white; }
        .btn-delete { background: #F8F8FF; color: #9CA3AF; border: 1px solid #E8E8F5; } .btn-delete:hover { background: #EF4444; color: white; border-color: #EF4444; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-pending { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .badge-paid { background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; }
        .badge-dikirim { background: #EFF6FF; color: #6366F1; border: 1px solid #C7D2FE; }
        .badge-selesai { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-batal { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .badge-default { background: #F8F8FF; color: #6A6A8A; border: 1px solid #E8E8F5; }
        .modal-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.15); }
        .modal-header { background: #F8F8FF; border-bottom: 1px solid #EEEEF8; border-radius: 20px 20px 0 0; padding: 20px 24px; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>Pesanan Online</h1>
                <p>Kelola dan proses pesanan dari pelanggan</p>
            </div>
        </div>

        <div class="content-wrap">
        <div class="card-premium">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>No. Nota</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Tipe</th><th>Status</th><th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
                        $res = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_cabang = '$id_cabang' ORDER BY id_pesanan DESC");
                        $notas = [];
                        while($o = mysqli_fetch_assoc($res)): $notas[] = $o;
                        ?>
                        <tr>
                            <td><code style="font-weight: 700; color: #1A1A2E; background: #F0F0FF; padding: 3px 8px; border-radius: 6px;">#<?php echo $o['id_pesanan']; ?></code></td>
                            <td>
                                <div style="font-weight: 700; color: #1A1A2E;"><?php echo htmlspecialchars($o['nama_penerima']); ?></div>
                                <div style="font-size: 0.78rem; color: #9CA3AF;"><?php echo $o['no_telp']; ?></div>
                            </td>
                            <td style="color: #9CA3AF; font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></td>
                            <td><span style="color: #C0392B; font-weight: 700;">Rp <?php echo number_format($o['total_bayar']); ?></span></td>
                            <td>
                                <?php if(isset($o['tipe_pesanan']) && $o['tipe_pesanan'] == 'offline'): ?>
                                    <span class="status-badge" style="background: #1A1A2E; color: white;"><i class="bi bi-pc-display-horizontal"></i> Kasir</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE;"><i class="bi bi-globe2"></i> Online</span>
                                <?php endif; ?>
                            </td>
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
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                    <button class="btn-action btn-detail" data-bs-toggle="modal" data-bs-target="#nota<?php echo $o['id_pesanan']; ?>">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                    <?php if($o['status'] == 'pending' || $o['status'] == 'paid'): ?>
                                        <a href="pesanan.php?kirim=<?php echo $o['id_pesanan']; ?>" class="btn-action btn-send">
                                            <i class="bi bi-send"></i> Kirim
                                        </a>
                                        <a href="#" class="btn-action btn-cancel" onclick="return batalkanPesanan(<?php echo $o['id_pesanan']; ?>)">
                                            <i class="bi bi-x-circle"></i> Batal
                                        </a>
                                    <?php endif; ?>
                                    <?php if($o['status'] == 'dikirim'): ?>
                                        <a href="pesanan.php?selesai=<?php echo $o['id_pesanan']; ?>" class="btn-action btn-done" onclick="return confirm('Tandai pesanan sebagai selesai?')">
                                            <i class="bi bi-check-circle"></i> Selesai
                                        </a>
                                    <?php endif; ?>
                                    <a href="pesanan.php?hapus=<?php echo $o['id_pesanan']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus pesanan ini?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    <?php foreach($notas as $o): ?>
    <div class="modal fade" id="nota<?php echo $o['id_pesanan']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5>Isi Nota #<?php echo $o['id_pesanan']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small bg-light p-2 rounded"><strong>Alamat:</strong> <?php echo $o['alamat_penerima']; ?></p>
                <?php if ($o['status'] == 'batal' && !empty($o['catatan_batal'])): ?>
                <div class="alert alert-danger p-2 small mb-3">
                    <strong>Alasan Batal:</strong> <?php echo htmlspecialchars($o['catatan_batal']); ?>
                </div>
                <?php endif; ?>
                <table class="table table-sm">
                    <thead><tr><th>Barang</th><th>Qty</th><th>Subtotal</th></tr></thead>
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
                        <tr><td><?php echo $d['nama_produk'] . ($d['nama_satuan'] ? ' (' . $d['nama_satuan'] . ')' : ''); ?></td><td><?php echo $d['jumlah']; ?></td><td>Rp <?php echo number_format($d['subtotal']); ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
    <?php endforeach; ?>

    <?php 
    if(isset($_GET['kirim'])) {
        $id = $_GET['kirim']; $d = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan='$id'"));
        $wa = (substr($d['no_telp'], 0, 1) == '0') ? '62'.substr($d['no_telp'], 1) : $d['no_telp'];
        mysqli_query($koneksi, "UPDATE pesanan SET status='dikirim' WHERE id_pesanan='$id'");
        $link = "https://api.whatsapp.com/send?phone=$wa&text=".urlencode("Halo ".$d['nama_penerima'].", pesanan #$id sudah dikirim ya sayang!");
        echo "<script>alert('Status Updated!'); window.open('$link', '_blank'); window.location='pesanan.php';</script>";
    }
    
    if(isset($_GET['selesai'])) {
        $id = (int)$_GET['selesai'];
        mysqli_query($koneksi, "UPDATE pesanan SET status='selesai' WHERE id_pesanan='$id'");
        echo "<script>alert('Pesanan ditandai sebagai selesai!'); window.location='pesanan.php';</script>";
    }
    
    if(isset($_GET['batal'])) {
        $id = (int)$_GET['batal'];
        $alasan = isset($_GET['alasan']) ? mysqli_real_escape_string($koneksi, trim($_GET['alasan'])) : 'Dibatalkan oleh admin';
        $d = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan='$id'"));
        $wa = (substr($d['no_telp'], 0, 1) == '0') ? '62'.substr($d['no_telp'], 1) : $d['no_telp'];
        mysqli_query($koneksi, "UPDATE pesanan SET status='batal', catatan_batal='$alasan' WHERE id_pesanan='$id'");
        mysqli_query($koneksi, "UPDATE payment_confirmations SET status='rejected' WHERE id_pesanan='$id'");
        
        $link = "https://api.whatsapp.com/send?phone=$wa&text=".urlencode("Halo ".$d['nama_penerima'].", mohon maaf pesanan #$id terpaksa kami batalkan dengan alasan: $alasan. Silakan hubungi kami jika ada pertanyaan.");
        echo "<script>alert('Pesanan berhasil dibatalkan!'); window.open('$link', '_blank'); window.location='pesanan.php';</script>";
    }
    
    if(isset($_GET['hapus'])) {
        $id = (int)$_GET['hapus'];
        mysqli_query($koneksi, "DELETE FROM detail_pesanan WHERE id_pesanan='$id'");
        mysqli_query($koneksi, "DELETE FROM pesanan WHERE id_pesanan='$id'");
        echo "<script>alert('Pesanan berhasil dihapus!'); window.location='pesanan.php';</script>";
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function batalkanPesanan(id) {
        var alasan = prompt("Masukkan alasan pembatalan pesanan:");
        if (alasan === null) {
            return false;
        }
        if (alasan.trim() === "") {
            alert("Alasan pembatalan harus diisi!");
            return false;
        }
        window.location.href = "pesanan.php?batal=" + id + "&alasan=" + encodeURIComponent(alasan);
        return false;
    }
    </script>
</body>
</html>