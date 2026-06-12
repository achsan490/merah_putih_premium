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
    <title>Pesanan Masuk - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style> 
        .main-content { margin-left: 260px; padding: 40px; } 
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-action { 
            padding: 6px 16px; 
            font-weight: 500; 
            font-size: 13px; 
            transition: all 0.3s ease;
            border: none;
        }
        .btn-action:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }
        .btn-detail { 
            background: #f8f9fa; 
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .btn-detail:hover { 
            background: #e9ecef; 
            color: #212529;
            border-color: #adb5bd;
        }
        .btn-send { 
            background: #8b0000; 
            color: white;
        }
        .btn-send:hover { 
            background: #a00000; 
            color: white;
        }
        .btn-delete { 
            background: white; 
            color: #6c757d; 
            border: 1px solid #dee2e6;
        }
        .btn-delete:hover { 
            background: #dc3545; 
            color: white; 
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="card card-premium p-4 bg-white">
            <h4 class="fw-bold mb-4">Daftar Nota Pesanan</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Nota</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Status</th><th class="text-center">Aksi</th>
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
                            <td class="fw-bold text-primary">#<?php echo $o['id_pesanan']; ?></td>
                            <td><strong><?php echo $o['nama_penerima']; ?></strong><br><small class="text-muted"><?php echo $o['no_telp']; ?></small></td>
                            <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($o['tgl_pesan'])); ?></small></td>
                            <td class="fw-bold text-danger">Rp <?php echo number_format($o['total_bayar']); ?></td>
                            <td>
                                <?php 
                                $badge_class = 'bg-secondary';
                                $status_text = strtoupper($o['status']);
                                if($o['status'] == 'pending') {
                                    $badge_class = 'bg-warning text-dark';
                                } elseif($o['status'] == 'dikirim') {
                                    $badge_class = 'bg-primary';
                                } elseif($o['status'] == 'selesai') {
                                    $badge_class = 'bg-success';
                                    $status_text = 'SELESAI';
                                } elseif($o['status'] == 'paid') {
                                    $badge_class = 'bg-info text-dark';
                                    $status_text = 'DIBAYAR';
                                } elseif($o['status'] == 'batal') {
                                    $badge_class = 'bg-danger';
                                    $status_text = 'BATAL';
                                }
                                ?>
                                <span class="badge rounded-pill <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <button class="btn btn-action btn-detail rounded-pill" data-bs-toggle="modal" data-bs-target="#nota<?php echo $o['id_pesanan']; ?>">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </button>
                                    <?php if($o['status'] == 'pending' || $o['status'] == 'paid'): ?>
                                         <a href="pesanan.php?kirim=<?php echo $o['id_pesanan']; ?>" class="btn btn-action btn-send rounded-pill">
                                             <i class="bi bi-send me-1"></i> Kirim
                                         </a>
                                         <a href="pesanan.php?batal=<?php echo $o['id_pesanan']; ?>" class="btn btn-action btn-danger rounded-pill" onclick="return confirm('Tolak/batalkan pesanan ini?')">
                                             <i class="bi bi-x-circle me-1"></i> Batalkan
                                         </a>
                                    <?php endif; ?>
                                    <?php if($o['status'] == 'dikirim'): ?>
                                        <a href="pesanan.php?selesai=<?php echo $o['id_pesanan']; ?>" class="btn btn-sm btn-success rounded-pill" onclick="return confirm('Tandai pesanan ini sebagai selesai?')">
                                            <i class="bi bi-check-circle me-1"></i> Selesai
                                        </a>
                                    <?php endif; ?>
                                    <a href="pesanan.php?hapus=<?php echo $o['id_pesanan']; ?>" class="btn btn-action btn-delete rounded-pill" onclick="return confirm('Yakin hapus pesanan ini?')">
                                        <i class="bi bi-trash"></i>
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

    <?php foreach($notas as $o): ?>
    <div class="modal fade" id="nota<?php echo $o['id_pesanan']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5>Isi Nota #<?php echo $o['id_pesanan']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small bg-light p-2 rounded"><strong>Alamat:</strong> <?php echo $o['alamat_penerima']; ?></p>
                <table class="table table-sm">
                    <thead><tr><th>Barang</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        <?php 
                        $det = mysqli_query($koneksi, "SELECT detail_pesanan.*, produk.nama_produk FROM detail_pesanan JOIN produk ON detail_pesanan.id_produk = produk.id WHERE id_pesanan='".$o['id_pesanan']."'");
                        while($d = mysqli_fetch_assoc($det)): ?>
                        <tr><td><?php echo $d['nama_produk']; ?></td><td><?php echo $d['jumlah']; ?></td><td>Rp <?php echo number_format($d['subtotal']); ?></td></tr>
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
        $d = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan='$id'"));
        $wa = (substr($d['no_telp'], 0, 1) == '0') ? '62'.substr($d['no_telp'], 1) : $d['no_telp'];
        mysqli_query($koneksi, "UPDATE pesanan SET status='batal' WHERE id_pesanan='$id'");
        mysqli_query($koneksi, "UPDATE payment_confirmations SET status='rejected' WHERE id_pesanan='$id'");
        
        $link = "https://api.whatsapp.com/send?phone=$wa&text=".urlencode("Halo ".$d['nama_penerima'].", mohon maaf pesanan #$id terpaksa kami batalkan karena alasan tertentu. Silakan hubungi kami jika ada pertanyaan.");
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
</body>
</html>