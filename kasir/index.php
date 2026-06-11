<?php
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Authentication Check: Redirect to admin login if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("location: ../admin/login.php");
    exit;
}

$id_cabang = isset($_SESSION['id_cabang']) ? (int)$_SESSION['id_cabang'] : 1;
$nama_cabang = isset($_SESSION['nama_cabang']) ? $_SESSION['nama_cabang'] : 'Toko Utama (Pusat / Online)';

// Handle checkout POS transaction
if (isset($_POST['proses_transaksi'])) {
    $nama = mysqli_real_escape_string($koneksi, !empty($_POST['nama_penerima']) ? $_POST['nama_penerima'] : 'Pelanggan Umum');
    $hp = mysqli_real_escape_string($koneksi, !empty($_POST['no_telp']) ? $_POST['no_telp'] : '-');
    $metode = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
    $total = (int)$_POST['total_bayar'];
    $uang_bayar = (int)$_POST['uang_bayar'];
    $uang_kembali = (int)$_POST['uang_kembali'];
    $tgl = date("Y-m-d H:i:s");

    // Insert into pesanan table with id_cabang
    $query = "INSERT INTO pesanan (nama_penerima, no_telp, alamat_penerima, total_bayar, metode_bayar, status, tgl_pesan, tipe_pesanan, id_cabang) 
              VALUES ('$nama', '$hp', 'Pembelian Toko Offline', '$total', '$metode', 'selesai', '$tgl', 'offline', '$id_cabang')";
    $exec = mysqli_query($koneksi, $query);

    if ($exec) {
        $id_pesanan = mysqli_insert_id($koneksi);
        $items = json_decode($_POST['items_json'], true);

        foreach ($items as $item) {
            $id_produk = (int)$item['id'];
            $qty = (int)$item['qty'];
            $sub = (int)$item['subtotal'];

            // Insert details
            mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, subtotal) VALUES ('$id_pesanan', '$id_produk', '$qty', '$sub')");

            // Deduct stock for the specific branch
            mysqli_query($koneksi, "
                INSERT INTO stok_cabang (id_produk, id_cabang, stok) 
                VALUES ('$id_produk', '$id_cabang', 0)
                ON DUPLICATE KEY UPDATE stok = stok - $qty
            ");
            
            // If main branch (ID 1), also deduct from the main produk table to keep online stock in sync
            if ($id_cabang == 1) {
                mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id = '$id_produk'");
            }
        }

        // Redirect to trigger print dialog
        echo "<script>window.location='index.php?sukses=1&id_pesanan=$id_pesanan&bayar=$uang_bayar&kembali=$uang_kembali';</script>";
        exit;
    }
}

// Fetch categories for filter
$cats = [];
$res_cats = mysqli_query($koneksi, "SELECT * FROM categories");
while ($c = mysqli_fetch_assoc($res_cats)) {
    $cats[] = $c;
}

// Fetch products for JS inventory (with branch-specific stock levels)
$prods = [];
$res_prods = mysqli_query($koneksi, "
    SELECT p.*, COALESCE(sc.stok, 0) AS stok 
    FROM produk p 
    LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = '$id_cabang'
    ORDER BY p.nama_produk ASC
");
while ($p = mysqli_fetch_assoc($res_prods)) {
    $prods[] = $p;
}

// Fetch print details if redirected after success (join with cabang table for receipt details)
$print_pesanan = null;
$print_items = [];
if (isset($_GET['sukses']) && isset($_GET['id_pesanan'])) {
    $print_id = (int)$_GET['id_pesanan'];
    $q_pesanan = mysqli_query($koneksi, "
        SELECT p.*, c.nama_cabang, c.alamat AS alamat_cabang, c.no_telp AS telp_cabang 
        FROM pesanan p 
        LEFT JOIN cabang c ON p.id_cabang = c.id_cabang 
        WHERE p.id_pesanan = '$print_id'
    ");
    $print_pesanan = mysqli_fetch_assoc($q_pesanan);

    if ($print_pesanan) {
        $q_items = mysqli_query($koneksi, "SELECT detail_pesanan.*, produk.nama_produk FROM detail_pesanan JOIN produk ON detail_pesanan.id_produk = produk.id WHERE id_pesanan = '$print_id'");
        while ($item = mysqli_fetch_assoc($q_items)) {
            $print_items[] = $item;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kasir POS - MerahPutih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --grad-merah: linear-gradient(135deg, #8b0000 0%, #e63946 100%);
            --merah-tua: #8b0000;
            --merah-terang: #e63946;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f1f3f5; overflow-x: hidden; }

        /* Top Header POS */
        .pos-header {
            background: var(--grad-merah);
            color: white;
            padding: 12px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .card-premium { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        
        /* Digital Neon LED Display */
        .neon-display {
            background-color: #1a1a1a;
            border: 3px solid #333;
            border-radius: 12px;
            padding: 15px 25px;
            color: #39FF14;
            font-family: 'Share Tech Mono', monospace;
            text-align: right;
            text-shadow: 0 0 10px rgba(57,255,20,0.4);
            box-shadow: inset 0 0 10px rgba(0,0,0,0.8);
        }

        /* POS Table styling */
        .pos-table th {
            background-color: #343a40;
            color: white;
            font-size: 0.85rem;
            padding: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .pos-table td {
            font-size: 0.88rem;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .pos-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .qty-input {
            width: 55px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Compact Lookup styling */
        .lookup-table td {
            font-size: 0.82rem;
            padding: 6px 8px;
        }

        .shortcut-badge {
            font-family: 'Share Tech Mono', monospace;
            background: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            border: 1px solid #ced4da;
            margin-right: 5px;
            display: inline-block;
        }

        /* Success scanner card flash */
        @keyframes flashSuccess {
            0% { background-color: rgba(25, 135, 84, 0.25); }
            100% { background-color: rgba(220, 53, 69, 0.05); }
        }
        .scanner-flash {
            animation: flashSuccess 0.4s ease-out;
        }

        /* Printing elements */
        .print-area { display: none; }

        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .pos-header, .container-fluid { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Top Header POS -->
    <header class="pos-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h5 class="fw-800 m-0 text-white"><i class="bi bi-calculator-fill me-2"></i>MERAH<span class="fw-light text-warning">PUTIH KASIR</span></h5>
            <span class="badge bg-warning text-dark ms-2 rounded-pill fw-semibold" style="font-size: 0.8rem;"><i class="bi bi-geo-alt-fill me-1"></i><?php echo htmlspecialchars($nama_cabang); ?></span>
            <span class="badge bg-white text-dark ms-3 rounded-pill font-monospace" id="posClock">00:00:00</span>
        </div>
        <div class="d-flex gap-2">
            <span class="text-white opacity-75 small align-self-center me-3">
                <span class="shortcut-badge">F4</span>Scan Barcode | 
                <span class="shortcut-badge">F2</span>Cari Barang | 
                <span class="shortcut-badge">F8 / End / Enter</span>Bayar
            </span>
            <a href="../admin/index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="../admin/produk.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-box-seam me-1"></i> Produk</a>
            <a href="../admin/logout.php" class="btn btn-sm btn-danger rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
        </div>
    </header>

    <div class="container-fluid my-3 px-4">
        <div class="row g-3">
            
            <!-- Left Side: Barcode Scanner & Active Bills Table -->
            <div class="col-lg-8 col-md-12">
                
                <!-- Neon Pole Display & Barcode Scan Card -->
                <div class="card card-premium bg-white p-3 mb-3">
                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <div class="neon-display">
                                <div style="font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase; color: #888; font-family: 'Poppins', sans-serif;" class="text-start mb-1">Total Belanja</div>
                                <div class="h2 m-0" id="lblNeonTotal">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-danger bg-opacity-5 border border-danger border-opacity-10 rounded-3 p-3" id="scannerCard">
                                <label class="form-label fw-bold small text-danger m-0 mb-1"><span class="shortcut-badge">F4</span>Scan Barcode / Kode Produk (Enter)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-danger"><i class="bi bi-qr-code-scan"></i></span>
                                    <input type="text" id="posBarcodeScanner" class="form-control border-start-0 font-monospace py-2" placeholder="Scan barcode barang di sini..." autofocus onkeypress="handleBarcodeScan(event)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Transaction Item Table -->
                <div class="card card-premium bg-white p-3" style="min-height: 58vh;">
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-receipt-cutoff text-danger me-2"></i>Daftar Belanja Aktif</h6>
                    
                    <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                        <table class="table pos-table align-middle text-nowrap">
                            <thead>
                                <tr>
                                    <th style="width: 5%">No</th>
                                    <th>Barcode</th>
                                    <th>Nama Barang</th>
                                    <th class="text-end" style="width: 15%">Harga</th>
                                    <th class="text-center" style="width: 12%">Jumlah</th>
                                    <th class="text-end" style="width: 15%">Subtotal</th>
                                    <th class="text-center" style="width: 5%">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <!-- JS items row inside here -->
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5" id="tableEmptyPlaceholder">
                                        <i class="bi bi-upc-scan fs-1 opacity-25"></i>
                                        <p class="mt-2 small">Scan barcode barang atau gunakan pencarian untuk memulai transaksi.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Payment summary & Compact Product Lookup -->
            <div class="col-lg-4 col-md-12">
                
                <!-- Bill Summary -->
                <div class="card card-premium bg-white p-3 mb-3">
                    <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-credit-card-2-back-fill text-danger me-2"></i>Ringkasan & Pembayaran</h6>
                    
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="fw-semibold small font-monospace" id="lblSubtotal">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-top pt-2">
                            <span class="fw-bold">Total Akhir:</span>
                            <span class="h4 fw-800 text-danger mb-0 font-monospace" id="lblTotal">Rp 0</span>
                        </div>
                        
                        <button class="btn btn-danger w-100 py-3 rounded-pill fw-bold shadow-sm" id="btnCheckout" onclick="showCheckoutModal()" disabled>
                            BAYAR & SELESAI <span class="shortcut-badge ms-2">F8</span>
                        </button>
                    </div>
                </div>

                <!-- Text-based Product Lookup (Compact Minimarket Style) -->
                <div class="card card-premium bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-search text-danger me-2"></i><span class="shortcut-badge">F2</span>Cari Barang Manual</h6>
                    </div>
                    
                    <input type="text" id="posSearch" class="form-control form-control-sm mb-2" placeholder="Ketik nama / barcode..." onkeyup="filterProducts()">
                    
                    <div class="table-responsive" style="max-height: 28vh; overflow-y: auto;">
                        <table class="table table-sm lookup-table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Nama Barang</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Pilih</th>
                                </tr>
                            </thead>
                            <tbody id="lookupTableBody">
                                <!-- JS compact product rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form method="POST" id="posCheckoutForm">
                    <div class="modal-header border-0 bg-light rounded-top-4 py-3">
                        <h5 class="modal-title fw-bold text-danger" id="checkoutModalLabel"><i class="bi bi-cash-coin me-2"></i>Pembayaran Transaksi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <!-- Customer Name -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Nama Pelanggan (Opsional)</label>
                            <input type="text" name="nama_penerima" class="form-control py-2 rounded-3" placeholder="Contoh: Pelanggan Umum" value="Pelanggan Umum">
                        </div>

                        <!-- Customer Phone -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Nomor WhatsApp (Opsional)</label>
                            <input type="number" name="no_telp" class="form-control py-2 rounded-3" placeholder="08xxxx">
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted d-block">Metode Pembayaran</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="metode_bayar" id="pay-tunai" value="tunai" checked onchange="toggleCashInput(true)">
                                <label class="btn btn-outline-danger flex-fill py-2 rounded-3" for="pay-tunai"><i class="bi bi-cash me-1"></i> Tunai</label>

                                <input type="radio" class="btn-check" name="metode_bayar" id="pay-qris" value="qris_offline" onchange="toggleCashInput(false)">
                                <label class="btn btn-outline-danger flex-fill py-2 rounded-3" for="pay-qris"><i class="bi bi-qr-code me-1"></i> QRIS</label>

                                <input type="radio" class="btn-check" name="metode_bayar" id="pay-debit" value="debit" onchange="toggleCashInput(false)">
                                <label class="btn btn-outline-danger flex-fill py-2 rounded-3" for="pay-debit"><i class="bi bi-credit-card-2-back me-1"></i> Debit</label>
                            </div>
                        </div>

                        <!-- Payment Summary Box -->
                        <div class="bg-light p-3 rounded-4 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tagihan Belanja:</span>
                                <span class="fw-bold text-danger text-end font-monospace" id="modalTotalTagihan">Rp 0</span>
                            </div>
                            
                            <!-- Cash calculator input -->
                            <div id="cashCalculatorSection">
                                <div class="mb-2">
                                    <label class="form-label fw-bold small text-muted m-0">Uang Diterima:</label>
                                    <div class="input-group mt-1">
                                        <span class="input-group-text bg-white border-end-0">Rp</span>
                                        <input type="number" name="uang_bayar" id="txtBayar" class="form-control border-start-0 fw-bold font-monospace fs-5" placeholder="0" onkeyup="calculateChange()" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-3 border-top pt-2">
                                    <span>Kembalian Uang:</span>
                                    <span class="fw-extrabold text-success h4 mb-0 font-monospace" id="lblKembalian">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden Payload Fields -->
                    <input type="hidden" name="total_bayar" id="hiddenTotal">
                    <input type="hidden" name="uang_kembali" id="hiddenKembali" value="0">
                    <input type="hidden" name="items_json" id="hiddenItemsJson">
                    <input type="hidden" name="proses_transaksi" value="1">

                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-danger w-100 py-3 rounded-pill fw-bold shadow-sm" id="btnSubmitCheckout">
                            SELESAIKAN & CETAK STRUK <i class="bi bi-printer ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Receipt Area (Thermal printer emulation) -->
    <?php if ($print_pesanan): ?>
        <div class="print-area" id="printArea">
            <div style="text-align: center; font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 10px; color: #000; font-size: 12px; line-height: 1.3;">
                <h3 style="margin: 0; font-weight: bold; font-size: 15px; text-transform: uppercase;">MERAH PUTIH STORE</h3>
                <p style="margin: 3px 0 10px 0; font-size: 9px; color: #333;">
                    <?php echo htmlspecialchars($print_pesanan['nama_cabang']); ?><br>
                    <?php echo htmlspecialchars($print_pesanan['alamat_cabang'] ?: 'Premium Offline Boutique'); ?><br>
                    Telp: <?php echo htmlspecialchars($print_pesanan['telp_cabang'] ?: '-'); ?>
                </p>
                <div style="text-align: left; font-size: 10px; margin-bottom: 5px;">
                    No. Nota: #<?php echo $print_pesanan['id_pesanan']; ?><br>
                    Tgl/Jam : <?php echo date('d/m/Y H:i', strtotime($print_pesanan['tgl_pesan'])); ?><br>
                    Customer: <?php echo htmlspecialchars($print_pesanan['nama_penerima']); ?><br>
                    Metode  : <?php echo strtoupper($print_pesanan['metode_bayar']); ?>
                </div>
                <p style="margin: 5px 0; font-size: 10px;">================================</p>
                
                <table style="width: 100%; font-size: 10px; font-family: 'Courier New', Courier, monospace; border-collapse: collapse; text-align: left;">
                    <?php foreach ($print_items as $item): ?>
                        <tr>
                            <td colspan="3" style="font-weight: bold;"><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding-bottom: 4px;"><?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['subtotal'] / $item['jumlah'], 0, ',', '.'); ?></td>
                            <td style="width: 10%; padding-bottom: 4px;"></td>
                            <td style="width: 40%; text-align: right; padding-bottom: 4px;">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <p style="margin: 5px 0; font-size: 10px;">================================</p>
                
                <table style="width: 100%; font-size: 10px; font-family: 'Courier New', Courier, monospace;">
                    <tr>
                        <td style="text-align: left;">Total Tagihan:</td>
                        <td style="text-align: right; font-weight: bold;">Rp <?php echo number_format($print_pesanan['total_bayar'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php if ($print_pesanan['metode_bayar'] == 'tunai'): ?>
                        <tr>
                            <td style="text-align: left;">Bayar:</td>
                            <td style="text-align: right;">Rp <?php echo number_format((int)$_GET['bayar'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">Kembali:</td>
                            <td style="text-align: right; font-weight: bold;">Rp <?php echo number_format((int)$_GET['kembali'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
                
                <p style="margin: 15px 0 5px 0; font-size: 10px;">================================</p>
                <p style="margin: 5px 0; font-size: 10px; font-style: italic;">Terima kasih atas kunjungan Anda!<br>Semoga harimu menyenangkan, sayang! ❤️</p>
            </div>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.print();
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            });
        </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load the array of products from PHP database query
        const productsList = <?php echo json_encode($prods); ?>;
        let currentCart = {};

        // Render products & setup clock on document load
        $(document).ready(function() {
            renderLookupTable();
            
            // Focus barcode input immediately
            $('#posBarcodeScanner').focus();

            // Setup real-time header clock
            setInterval(() => {
                const now = new Date();
                $('#posClock').text(now.toTimeString().split(' ')[0]);
            }, 1000);

            // Refocus barcode input on checkout modal close
            $('#checkoutModal').on('hidden.bs.modal', function () {
                $('#posBarcodeScanner').focus();
            });

            // Set up Keyboard Shortcuts (Minimarket Cashier standard)
            $(window).keydown(function(e) {
                // F4 to focus scanner input
                if (e.key === 'F4') {
                    e.preventDefault();
                    $('#posBarcodeScanner').focus().select();
                }
                // F2 to focus manual search
                if (e.key === 'F2') {
                    e.preventDefault();
                    $('#posSearch').focus().select();
                }
                // F8 or End to trigger checkout button if cart is not empty
                if (e.key === 'F8' || e.key === 'End') {
                    e.preventDefault();
                    if (Object.keys(currentCart).length > 0) {
                        showCheckoutModal();
                    }
                }
            });

            // Submit checkout form on Enter key inside cash input if valid
            $('#txtBayar').keydown(function(e) {
                if (e.key === 'Enter') {
                    if ($('#btnSubmitCheckout').prop('disabled') === false) {
                        $('#posCheckoutForm').submit();
                    } else {
                        e.preventDefault();
                    }
                }
            });
        });

        // Helper to format currency
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number).replace("IDR", "Rp");
        }

        // Render text-only compact product lookup table
        function renderLookupTable() {
            const tbody = $('#lookupTableBody');
            tbody.empty();

            const query = $('#posSearch').val().toLowerCase();
            let filtered = productsList;

            // Apply search query
            if (query.trim() !== '') {
                filtered = filtered.filter(p => 
                    p.nama_produk.toLowerCase().includes(query) || 
                    (p.barcode && p.barcode.toLowerCase().includes(query))
                );
            } else {
                // Display first 10 products if search is empty to keep list clean
                filtered = filtered.slice(0, 8);
            }

            if (filtered.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center text-muted small py-3">Tidak ditemukan</td></tr>');
                return;
            }

            filtered.forEach(p => {
                const isOutOfStock = p.stok <= 0;
                const rowHTML = `
                    <tr class="${isOutOfStock ? 'table-danger text-decoration-line-through opacity-70' : ''}">
                        <td class="font-monospace text-muted" style="font-size:0.75rem;">${p.barcode || '-'}</td>
                        <td class="fw-semibold">${p.nama_produk}</td>
                        <td class="text-end font-monospace">${formatRupiah(p.harga)}</td>
                        <td class="text-center font-monospace">${p.stok}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-danger py-0 px-2 rounded" ${isOutOfStock ? 'disabled' : `onclick="addToCart(${p.id})"`}>
                                <i class="bi bi-plus"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(rowHTML);
            });
        }

        // Filter products manual on typing
        function filterProducts() {
            renderLookupTable();
        }

        // Handle physical/manual barcode scanner entry
        function handleBarcodeScan(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcodeVal = $('#posBarcodeScanner').val().trim();
                if (barcodeVal === '') {
                    if (Object.keys(currentCart).length > 0) {
                        showCheckoutModal();
                    }
                    return;
                }

                // Find product by barcode
                const product = productsList.find(p => p.barcode === barcodeVal);
                if (product) {
                    if (product.stok <= 0) {
                        alert(`Barang "${product.nama_produk}" tidak bisa di-scan karena STOK HABIS!`);
                    } else {
                        addToCart(product.id);
                        flashScannerSuccess();
                    }
                } else {
                    // Fallback to find by ID if barcode is a numeric ID
                    const productById = productsList.find(p => p.id == barcodeVal);
                    if (productById) {
                        if (productById.stok <= 0) {
                            alert(`Barang "${productById.nama_produk}" tidak bisa di-scan karena STOK HABIS!`);
                        } else {
                            addToCart(productById.id);
                            flashScannerSuccess();
                        }
                    } else {
                        alert(`Barcode / Kode "${barcodeVal}" tidak terdaftar di database!`);
                    }
                }
                
                // Clear and re-focus scanner input
                $('#posBarcodeScanner').val('').focus();
            }
        }

        // Flash visual scanner alert on card
        function flashScannerSuccess() {
            const card = $('#scannerCard');
            card.addClass('scanner-flash');
            setTimeout(() => { card.removeClass('scanner-flash'); }, 400);
        }

        // Add item to POS Cart
        function addToCart(productId) {
            const product = productsList.find(p => p.id == productId);
            if (!product) return;

            // Check if stock allows
            const currentQty = currentCart[productId] ? currentCart[productId].qty : 0;
            if (currentQty >= product.stok) {
                alert(`Gagal menambah. Stok "${product.nama_produk}" terbatas hanya ${product.stok} pcs.`);
                return;
            }

            if (currentCart[productId]) {
                currentCart[productId].qty++;
            } else {
                currentCart[productId] = {
                    id: product.id,
                    nama: product.nama_produk,
                    barcode: product.barcode,
                    harga: parseInt(product.harga),
                    harga_grosir: product.harga_grosir ? parseInt(product.harga_grosir) : null,
                    min_qty_grosir: product.min_qty_grosir ? parseInt(product.min_qty_grosir) : null,
                    stok: parseInt(product.stok),
                    qty: 1
                };
            }

            updateCartUI();
            $('#posBarcodeScanner').focus(); // Automatically return focus
        }

        // Change quantity manually
        function changeQty(productId, inputElement) {
            const qtyVal = parseInt($(inputElement).val()) || 0;
            const product = productsList.find(p => p.id == productId);
            if (!product) return;

            if (qtyVal <= 0) {
                delete currentCart[productId];
            } else if (qtyVal > product.stok) {
                alert(`Gagal menyimpan. Stok "${product.nama_produk}" terbatas hanya ${product.stok} pcs.`);
                $(inputElement).val(currentCart[productId].qty);
                return;
            } else {
                currentCart[productId].qty = qtyVal;
            }

            updateCartUI();
            $('#posBarcodeScanner').focus();
        }

        // Remove item from POS Cart
        function removeItem(productId) {
            if (currentCart[productId]) {
                delete currentCart[productId];
                updateCartUI();
                $('#posBarcodeScanner').focus();
            }
        }

        // Update Cart rendering & numbers
        function updateCartUI() {
            const tbody = $('#cartTableBody');
            const placeholder = $('#tableEmptyPlaceholder');
            
            // Clear previous rows but keep placeholder
            tbody.find('.cart-row').remove();

            const keys = Object.keys(currentCart);

            if (keys.length === 0) {
                placeholder.show();
                $('#lblSubtotal').text('Rp 0');
                $('#lblTotal').text('Rp 0');
                $('#lblNeonTotal').text('Rp 0');
                $('#btnCheckout').prop('disabled', true);
                return;
            }

            placeholder.hide();
            $('#btnCheckout').prop('disabled', false);

            let subtotalTotal = 0;
            let countIndex = 1;

            keys.forEach(key => {
                const item = currentCart[key];
                
                // Determine wholesale pricing
                let activePrice = item.harga;
                let isWholesale = false;
                if (item.harga_grosir && item.harga_grosir > 0 && item.qty >= item.min_qty_grosir) {
                    activePrice = item.harga_grosir;
                    isWholesale = true;
                }

                const subtotal = activePrice * item.qty;
                item.subtotal = subtotal; // Save current subtotal in item payload
                subtotalTotal += subtotal;

                const rowHTML = `
                    <tr class="cart-row" id="cart-row-${item.id}">
                        <td class="font-monospace text-muted text-center" style="font-size:0.8rem;">${countIndex++}</td>
                        <td class="font-monospace text-muted" style="font-size:0.8rem;">${item.barcode || '-'}</td>
                        <td>
                            <span class="fw-semibold">${item.nama}</span>
                            ${isWholesale ? '<span class="badge bg-danger rounded-pill ms-2" style="font-size:0.6rem;">Grosir</span>' : ''}
                        </td>
                        <td class="text-end font-monospace">${formatRupiah(activePrice)}</td>
                        <td class="text-center">
                            <input type="number" class="qty-input" value="${item.qty}" onchange="changeQty(${item.id}, this)">
                        </td>
                        <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(subtotal)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-secondary border-0 p-0 text-danger" onclick="removeItem(${item.id})">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(rowHTML);
            });

            $('#lblSubtotal').text(formatRupiah(subtotalTotal));
            $('#lblTotal').text(formatRupiah(subtotalTotal));
            $('#lblNeonTotal').text(formatRupiah(subtotalTotal));
        }

        // Show payment checkout modal
        function showCheckoutModal() {
            let total = 0;
            const itemsArray = [];
            
            Object.keys(currentCart).forEach(key => {
                const item = currentCart[key];
                total += item.subtotal;
                itemsArray.push(item);
            });

            // Set text & values in modal
            $('#modalTotalTagihan').text(formatRupiah(total));
            $('#hiddenTotal').val(total);
            $('#hiddenItemsJson').val(JSON.stringify(itemsArray));

            // Default cash input to tagihan total to speed up workflow
            $('#txtBayar').val(total);
            $('#hiddenKembali').val(0);
            $('#lblKembalian').text('Rp 0');

            // Toggle UI based on payment method
            const isCash = $('input[name="metode_bayar"]:checked').val() === 'tunai';
            toggleCashInput(isCash);

            // Show bootstrap modal
            const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            checkoutModal.show();
            
            // Focus cash input if Cash method is active
            setTimeout(() => {
                if (isCash) {
                    $('#txtBayar').focus().select();
                } else {
                    $('#btnSubmitCheckout').focus();
                }
            }, 500);
        }

        // Toggle cash inputs based on PAYMENT TYPE (QRIS/Debit doesn't need cash change calculator)
        function toggleCashInput(isCash) {
            if (isCash) {
                $('#cashCalculatorSection').show();
                $('#txtBayar').prop('required', true);
                calculateChange();
            } else {
                $('#cashCalculatorSection').hide();
                $('#txtBayar').prop('required', false);
                // Set default for non-cash payment
                const total = parseInt($('#hiddenTotal').val());
                $('#txtBayar').val(total);
                $('#hiddenKembali').val(0);
                $('#btnSubmitCheckout').prop('disabled', false);
            }
        }

        // Live calculation of change
        function calculateChange() {
            const total = parseInt($('#hiddenTotal').val()) || 0;
            const bayar = parseInt($('#txtBayar').val()) || 0;
            const kembali = bayar - total;

            if (kembali >= 0) {
                $('#lblKembalian').text(formatRupiah(kembali)).removeClass('text-danger').addClass('text-success');
                $('#hiddenKembali').val(kembali);
                $('#btnSubmitCheckout').prop('disabled', false);
            } else {
                $('#lblKembalian').text('Uang Kurang!').removeClass('text-success').addClass('text-danger');
                $('#hiddenKembali').val(0);
                $('#btnSubmitCheckout').prop('disabled', true); // Disable checkout if money is insufficient
            }
        }
    </script>
</body>
</html>
