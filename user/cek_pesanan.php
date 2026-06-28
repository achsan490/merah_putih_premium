<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link class="icon" rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - MerahPutih Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, body { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { background-color: #F8F9FD; color: #1A1A2E; }

        .card-premium { background: white; border: none; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #F0F0F8; padding: 28px; }
        
        .form-control { border: 1.5px solid #E8E8F0; border-radius: 12px; padding: 11px 16px; font-size: 0.88rem; transition: border-color 0.2s; }
        .form-control:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.08); }
        .input-group-text { border: 1.5px solid #E8E8F0; background: #F8F9FD; color: #4A4A6A; border-radius: 12px; font-weight: 700; font-size: 0.88rem; }
        
        .btn-mp-primary { background: linear-gradient(135deg, #1A1A2E 0%, #0F3460 100%); color: white; font-weight: 700; border-radius: 12px; padding: 12px 24px; border: none; font-size: 0.88rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: all 0.2s; }
        .btn-mp-primary:hover { opacity: 0.95; color: white; transform: translateY(-1px); }
        .btn-mp-danger { background: linear-gradient(135deg, #922B21 0%, #E74C3C 100%); color: white; }
        .btn-mp-outline { background: white; border: 1.5px solid #E2E2EC; color: #4A4A6A; font-weight: 700; border-radius: 12px; padding: 12px 28px; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; transition: all 0.2s; }
        .btn-mp-outline:hover { background: #F8F9FD; color: #1A1A2E; border-color: #C0392B; }

        /* Timeline Design */
        .timeline-wrapper { position: relative; padding: 24px 0 8px 0; display: flex; justify-content: space-between; align-items: center; }
        .timeline-item { position: relative; width: 33.33%; text-align: center; display: flex; flex-direction: column; align-items: center; }
        
        /* Connector line */
        .timeline-item:before {
            content: ''; position: absolute; top: 20px; left: -50%; width: 100%; height: 3px; background: #EAEAF2; z-index: 1; transition: all 0.3s ease;
        }
        .timeline-item:first-child:before { content: none; }
        
        .timeline-icon {
            width: 44px; height: 44px; border-radius: 50%; background: #F0F0F8; color: #8A8AA0;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem; position: relative; z-index: 2; border: 2px solid white; transition: all 0.3s ease;
        }
        
        .timeline-text { font-size: 0.78rem; font-weight: 700; color: #8A8AA0; margin-top: 8px; transition: all 0.3s ease; }
        
        /* Active States */
        .timeline-item.active .timeline-icon { background: #C0392B; color: white; box-shadow: 0 8px 20px rgba(192,57,43,0.2); }
        .timeline-item.active:before { background: #C0392B; }
        .timeline-item.active .timeline-text { color: #1A1A2E; }

        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-pending { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .badge-dikirim { background: #EFF6FF; color: #6366F1; border: 1px solid #C7D2FE; }
        .badge-selesai { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-paid { background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; }
        .badge-batal { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .badge-default { background: #F8F8FF; color: #6A6A8A; border: 1px solid #E8E8F5; }
    </style>
</head>
<body class="py-5">

    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark mb-2">Lacak Pesanan Anda</h2>
            <p class="text-secondary small">Masukkan nomor nota atau nomor telepon Anda untuk melacak status pengiriman</p>
        </div>

        <div class="row justify-content-center g-4">
            <div class="col-lg-4 col-md-5">
                <div class="card-premium h-100">
                    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-search text-danger me-2"></i>Pencarian Nota</h5>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Nomor Nota / ID Pesanan</label>
                            <div class="input-group">
                                <span class="input-group-text">#</span>
                                <input type="number" name="id_nota" class="form-control" placeholder="Contoh: 12" required>
                                <button type="submit" name="cek_nota" class="btn-mp-primary btn-mp-danger">Cari</button>
                            </div>
                        </div>
                        <div class="text-center text-muted small my-3 font-monospace" style="font-size:0.75rem;">— ATAU —</div>
                        <div>
                            <label class="form-label">Nomor Telepon Pembeli</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="number" name="no_telp" class="form-control" placeholder="Contoh: 0812345" required>
                                <button type="submit" name="cari_nota" class="btn-mp-primary" style="background:#1A1A2E;">Cari</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6 col-md-7">
                <?php 
                // LOGIKA 1: CEK ID NOTA
                if(isset($_POST['cek_nota'])) {
                    $id_nota = mysqli_real_escape_string($koneksi, $_POST['id_nota']);
                    $ambil = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = '$id_nota'");
                    $data = mysqli_fetch_assoc($ambil);

                    if($data) { render_status($data); } 
                    else { echo "<div class='alert alert-warning card-premium text-center border-warning bg-warning bg-opacity-5'><i class='bi bi-exclamation-circle text-warning fs-3 d-block mb-2'></i>Nomor nota <strong>#$id_nota</strong> tidak ditemukan di database kami. Silakan periksa kembali.</div>"; }
                }

                // LOGIKA 2: CARI BY HP
                if(isset($_POST['cari_nota'])) {
                    $telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
                    $cari = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE no_telp LIKE '%$telp%' ORDER BY id_pesanan DESC LIMIT 5");

                    if(mysqli_num_rows($cari) > 0) {
                        while($hasil = mysqli_fetch_assoc($cari)) { render_status($hasil); }
                    } else {
                        echo "<div class='alert alert-danger card-premium text-center border-danger bg-danger bg-opacity-5'><i class='bi bi-exclamation-triangle text-danger fs-3 d-block mb-2'></i>Nomor telepon tidak ditemukan dalam riwayat pesanan kami.</div>";
                    }
                }

                function render_status($d) {
                    $status = $d['status'];
                    $step1 = 'active'; // Pesanan Masuk
                    $step2 = ($status == 'proses' || $status == 'dikirim' || $status == 'selesai') ? 'active' : '';
                    $step3 = ($status == 'dikirim' || $status == 'selesai') ? 'active' : '';
                    
                    $tgl = isset($d['tgl_pesan']) ? $d['tgl_pesan'] : (isset($d['tanggal_pesan']) ? $d['tanggal_pesan'] : 'Unknown');
                    
                    // Badge class & text mapping
                    $badge_class = 'badge-default';
                    $status_text = strtoupper($status);
                    if ($status == 'pending') {
                        $badge_class = 'badge-pending';
                    } elseif ($status == 'batal') {
                        $badge_class = 'badge-batal';
                        $status_text = 'DIBATALKAN';
                    } elseif ($status == 'proses') {
                        $badge_class = 'badge-paid';
                        $status_text = 'DIPROSES';
                    } elseif ($status == 'dikirim') {
                        $badge_class = 'badge-dikirim';
                        $status_text = 'DIKIRIM';
                    } elseif ($status == 'selesai') {
                        $badge_class = 'badge-selesai';
                        $status_text = 'SELESAI';
                    }
                    ?>
                    <div class="card-premium mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark m-0">Nota #<?php echo $d['id_pesanan']; ?></h5>
                                <small class="text-secondary" style="font-size:0.8rem;"><i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y H:i', strtotime($tgl)); ?></small>
                            </div>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <?php if ($status == 'batal'): ?>
                            <div class="alert alert-danger border-0 rounded-3 p-3 mt-4" style="background:#FFF1F2; color:#9F1239;">
                                <div class="d-flex gap-2">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                    <div>
                                        <strong class="d-block mb-1">Pesanan Ini Dibatalkan</strong>
                                        <span class="small" style="line-height:1.5;"><?php echo !empty($d['catatan_batal']) ? htmlspecialchars($d['catatan_batal']) : 'Mohon hubungi CS untuk informasi detail pembatalan.'; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="timeline-wrapper mb-4">
                                <div class="timeline-item <?php echo $step1; ?>">
                                    <div class="timeline-icon"><i class="bi bi-receipt"></i></div>
                                    <div class="timeline-text">Dipesan</div>
                                </div>
                                <div class="timeline-item <?php echo $step2; ?>">
                                    <div class="timeline-icon"><i class="bi bi-box-seam"></i></div>
                                    <div class="timeline-text">Diproses</div>
                                </div>
                                <div class="timeline-item <?php echo $step3; ?>">
                                    <div class="timeline-icon"><i class="bi bi-truck"></i></div>
                                    <div class="timeline-text">Dikirim</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-3 rounded-3" style="background: #F8F9FD; border: 1px solid #EAEAF2; font-size:0.85rem;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Penerima Paket:</span>
                                <strong class="text-dark"><?php echo htmlspecialchars($d['nama_penerima']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Total Tagihan:</span>
                                <strong class="text-danger">Rp <?php echo number_format($d['total_bayar'], 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="index.php" class="btn-mp-outline w-auto d-inline-flex px-4 py-3">
                <i class="bi bi-shop"></i> Kembali ke Toko
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>