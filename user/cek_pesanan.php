<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --merah: #d90429; --merah-tua: #8b0000; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-gradasi { background: linear-gradient(135deg, #8b0000 0%, #e63946 100%); color: white; border: none; }
        
        /* Timeline */
        .timeline { position: relative; padding: 20px 0; list-style: none; display: flex; justify-content: space-between; }
        .timeline-item { position: relative; width: 33%; text-align: center; }
        .timeline-item:before {
            content: ''; position: absolute; top: 15px; left: -50%; width: 100%; height: 3px; background: #e9ecef; z-index: 1;
        }
        .timeline-item:first-child:before { content: none; }
        .timeline-icon {
            width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; color: #6c757d;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; position: relative; z-index: 2;
        }
        .timeline-item.active .timeline-icon { background: var(--merah); color: white; box-shadow: 0 0 0 4px rgba(217,4,41,0.2); }
        .timeline-item.active:before { background: var(--merah); }
        
        .timeline-text { font-size: 0.8rem; font-weight: 600; color: #6c757d; }
        .timeline-item.active .timeline-text { color: var(--merah-tua); }
    </style>
</head>
<body class="py-5">

    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-800 display-6">Lacak <span class="text-danger">Paketmu</span></h2>
            <p class="text-muted">Jangan khawatir, paketmu aman bersama kami.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-5 mb-4">
                <div class="card card-custom p-4 bg-white h-100">
                    <h6 class="fw-bold mb-3">Cari Pesanan</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Punya Nomor Nota?</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">#</span>
                                <input type="number" name="id_nota" class="form-control border-start-0 bg-light" placeholder="15">
                                <button name="cek_nota" class="btn btn-gradasi rounded-end">Cek</button>
                            </div>
                        </div>
                        <div class="text-center text-muted small my-2">- ATAU -</div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Cari Pakai Nomor HP</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-whatsapp"></i></span>
                                <input type="number" name="no_telp" class="form-control border-start-0 bg-light" placeholder="08123...">
                                <button name="cari_nota" class="btn btn-outline-dark rounded-end">Cari</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <?php 
                // LOGIKA 1: CEK ID NOTA
                if(isset($_POST['cek_nota'])) {
                    $id_nota = mysqli_real_escape_string($koneksi, $_POST['id_nota']);
                    $ambil = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = '$id_nota'");
                    $data = mysqli_fetch_assoc($ambil);

                    if($data) { render_status($data); } 
                    else { echo "<div class='alert alert-warning card-custom text-center'>Nota #$id_nota tidak ditemukan.</div>"; }
                }

                // LOGIKA 2: CARI BY HP
                if(isset($_POST['cari_nota'])) {
                    $telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
                    // Using tgl_pesan to be safe with NEW columns, but falling back logically if needed.
                    // Actually, let's select * to get whichever column exists.
                    $cari = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE no_telp LIKE '%$telp%' ORDER BY id_pesanan DESC LIMIT 5");

                    if(mysqli_num_rows($cari) > 0) {
                        while($hasil = mysqli_fetch_assoc($cari)) { render_status($hasil); }
                    } else {
                        echo "<div class='alert alert-danger card-custom text-center'>Nomor HP tidak ditemukan di sistem.</div>";
                    }
                }

                function render_status($d) {
                    $status = $d['status'];
                    $step1 = 'active'; // Pesanan Masuk
                    $step2 = ($status == 'proses' || $status == 'dikirim' || $status == 'selesai') ? 'active' : '';
                    $step3 = ($status == 'dikirim' || $status == 'selesai') ? 'active' : '';
                    
                    // Handle variable column name safely
                    $tgl = isset($d['tgl_pesan']) ? $d['tgl_pesan'] : (isset($d['tanggal_pesan']) ? $d['tanggal_pesan'] : 'Unknown');
                    
                    // Badge class & text mapping
                    $badge_class = 'bg-success';
                    $status_text = strtoupper($status);
                    if ($status == 'pending') {
                        $badge_class = 'bg-warning text-dark';
                    } elseif ($status == 'batal') {
                        $badge_class = 'bg-danger';
                        $status_text = 'DIBATALKAN';
                    } elseif ($status == 'proses') {
                        $badge_class = 'bg-info text-dark';
                        $status_text = 'DIPROSES';
                    } elseif ($status == 'dikirim') {
                        $badge_class = 'bg-primary';
                        $status_text = 'DIKIRIM';
                    } elseif ($status == 'selesai') {
                        $badge_class = 'bg-success';
                        $status_text = 'SELESAI';
                    }
                    ?>
                    <div class="card card-custom p-4 mb-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-0">Order #<?php echo $d['id_pesanan']; ?></h5>
                                <small class="text-muted"><?php echo date('d M Y', strtotime($tgl)); ?></small>
                            </div>
                            <span class="badge rounded-pill <?php echo $badge_class; ?> px-3">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <?php if ($status == 'batal'): ?>
                            <div class="alert alert-danger d-flex align-items-start mt-4 border-0 rounded-3 shadow-sm bg-danger-subtle text-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Pesanan Dibatalkan</h6>
                                    <p class="mb-0 small">
                                        <strong>Alasan Pembatalan:</strong><br>
                                        <?php echo !empty($d['catatan_batal']) ? htmlspecialchars($d['catatan_batal']) : 'Tidak ada catatan alasan pembatalan.'; ?>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="timeline mt-4">
                                <li class="timeline-item <?php echo $step1; ?>">
                                    <div class="timeline-icon"><i class="bi bi-cart-check"></i></div>
                                    <div class="timeline-text">Dipesan</div>
                                </li>
                                <li class="timeline-item <?php echo $step2; ?>">
                                    <div class="timeline-icon"><i class="bi bi-box-seam"></i></div>
                                    <div class="timeline-text">Diproses</div>
                                </li>
                                <li class="timeline-item <?php echo $step3; ?>">
                                    <div class="timeline-icon"><i class="bi bi-truck"></i></div>
                                    <div class="timeline-text">Dikirim</div>
                                </li>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 bg-light p-3 rounded-3">
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted">Penerima</span>
                                <span class="fw-bold small"><?php echo $d['nama_penerima']; ?></span>
                            </div>
                             <div class="d-flex justify-content-between mt-1">
                                <span class="small text-muted">Total</span>
                                <span class="fw-bold small text-danger">Rp <?php echo number_format($d['total_bayar']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-light rounded-pill px-4">Kembali Belanja</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>