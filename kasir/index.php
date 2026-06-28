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

    $query = "INSERT INTO pesanan (nama_penerima, no_telp, alamat_penerima, total_bayar, metode_bayar, status, tgl_pesan, tipe_pesanan, id_cabang) 
              VALUES ('$nama', '$hp', 'Pembelian Toko Offline', '$total', '$metode', 'selesai', '$tgl', 'offline', '$id_cabang')";
    $exec = mysqli_query($koneksi, $query);

    if ($exec) {
        $id_pesanan = mysqli_insert_id($koneksi);
        $items = json_decode($_POST['items_json'], true);

        foreach ($items as $item) {
            $id_produk = (int)$item['id_produk'];
            $id_kemasan = (int)$item['id_kemasan'];
            $qty = (int)$item['qty'];
            $faktor_kali = (int)$item['faktor_kali'];
            $total_pcs = $qty * $faktor_kali;
            $sub = (int)$item['subtotal'];

            mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, subtotal, id_kemasan) VALUES ('$id_pesanan', '$id_produk', '$qty', '$sub', '$id_kemasan')");
            mysqli_query($koneksi, "
                INSERT INTO stok_cabang (id_produk, id_cabang, stok) 
                VALUES ('$id_produk', '$id_cabang', 0)
                ON DUPLICATE KEY UPDATE stok = stok - $total_pcs
            ");
            if ($id_cabang == 1) {
                mysqli_query($koneksi, "UPDATE produk SET stok = stok - $total_pcs WHERE id = '$id_produk'");
            }
        }

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

// Fetch products & variants for JS inventory (with branch-specific stock levels)
$prods = [];
$res_prods = mysqli_query($koneksi, "
    SELECT 
        pk.id_kemasan, pk.id_produk, pk.nama_satuan, pk.faktor_kali, pk.harga, pk.barcode AS barcode_var,
        p.nama_produk, p.foto, p.harga_grosir, p.min_qty_grosir, p.barcode AS barcode_parent,
        COALESCE(sc.stok, 0) AS stok_master
    FROM produk_kemasan pk
    JOIN produk p ON pk.id_produk = p.id
    LEFT JOIN stok_cabang sc ON p.id = sc.id_produk AND sc.id_cabang = '$id_cabang'
    ORDER BY p.nama_produk ASC, pk.faktor_kali ASC
");
while ($p = mysqli_fetch_assoc($res_prods)) {
    $prods[] = $p;
}

// Fetch print details if redirected after success
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
        $q_items = mysqli_query($koneksi, "
            SELECT detail_pesanan.*, produk.nama_produk, pk.nama_satuan 
            FROM detail_pesanan 
            JOIN produk ON detail_pesanan.id_produk = produk.id 
            LEFT JOIN produk_kemasan pk ON detail_pesanan.id_kemasan = pk.id_kemasan 
            WHERE id_pesanan = '$print_id'
        ");
        while ($item = mysqli_fetch_assoc($q_items)) {
            $print_items[] = $item;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../assets/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir POS – MerahPutih</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #C0392B;
            --primary-dark: #922B21;
            --navy: #1A1A2E;
            --navy-light: #16213E;
            --accent: #FF6B35;
            --bg: #0D1117;
            --surface: #161B22;
            --surface-2: #21262D;
            --border: rgba(255,255,255,0.07);
            --text: #E6EDF3;
            --text-sub: #7D8590;
            --green: #3FB950;
            --yellow: #D29922;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── HEADER ── */
        .pos-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 20px;
            height: 56px;
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .pos-brand {
            display: flex; align-items: center; gap: 12px;
        }
        .pos-brand .brand-text { font-size: 1.1rem; font-weight: 800; color: var(--text); }
        .pos-brand .brand-text span { color: var(--accent); }
        .pos-brand .branch-pill {
            display: flex; align-items: center; gap: 5px;
            background: rgba(192,57,43,0.2);
            border: 1px solid rgba(192,57,43,0.35);
            color: #FF9999; font-size: 0.72rem; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }
        .pos-clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem; color: var(--text-sub);
            background: var(--surface-2);
            border: 1px solid var(--border);
            padding: 4px 12px; border-radius: 8px;
        }
        .pos-nav-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px;
            font-size: 0.78rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            color: var(--text-sub); border: 1px solid var(--border);
            background: transparent;
        }
        .pos-nav-btn:hover { background: var(--surface-2); color: var(--text); }
        .pos-nav-btn.danger { border-color: rgba(192,57,43,0.4); color: #FF9999; }
        .pos-nav-btn.danger:hover { background: rgba(192,57,43,0.15); color: #FF6B6B; }

        /* ── MAIN LAYOUT ── */
        .pos-body {
            flex: 1; display: grid;
            grid-template-columns: 1fr 380px;
            overflow: hidden;
            gap: 0;
        }

        /* ── LEFT PANEL ── */
        .pos-left {
            display: flex; flex-direction: column;
            padding: 16px; gap: 12px;
            overflow: hidden;
        }

        /* Scanner */
        .scanner-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex; align-items: center; gap: 16px;
            flex-shrink: 0;
            transition: border-color 0.3s;
        }
        .scanner-card.flash { border-color: var(--green); box-shadow: 0 0 0 3px rgba(63,185,80,0.15); }
        .scanner-display {
            flex: 1;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
        }
        .scanner-display-label {
            font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--text-sub); margin-bottom: 4px;
        }
        .scanner-total {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem; font-weight: 600;
            color: var(--green);
        }
        .scanner-input-wrap {
            display: flex; flex-direction: column; gap: 6px;
        }
        .scanner-input-label { font-size: 0.72rem; color: var(--text-sub); }
        .scanner-input {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 8px; padding: 10px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem; color: var(--text);
            width: 240px; outline: none;
            transition: border-color 0.2s;
        }
        .scanner-input:focus { border-color: var(--primary); }
        .scanner-input::placeholder { color: var(--text-sub); }

        /* Cart Table */
        .cart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            flex: 1; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .cart-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 8px;
            font-size: 0.85rem; font-weight: 700; flex-shrink: 0;
        }
        .cart-card-header i { color: var(--primary); }
        .cart-table-wrap {
            flex: 1; overflow-y: auto;
        }
        .cart-table-wrap::-webkit-scrollbar { width: 5px; }
        .cart-table-wrap::-webkit-scrollbar-thumb { background: var(--surface-2); border-radius: 3px; }

        table.pos-tbl { width: 100%; border-collapse: collapse; }
        table.pos-tbl thead th {
            background: var(--surface-2);
            padding: 10px 14px;
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: var(--text-sub);
            text-align: left; position: sticky; top: 0;
        }
        table.pos-tbl thead th:last-child { text-align: center; width: 50px; }
        table.pos-tbl tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }
        table.pos-tbl tbody tr:hover { background: rgba(255,255,255,0.02); }
        table.pos-tbl tbody tr:last-child td { border-bottom: none; }

        .qty-input {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 6px; color: var(--text);
            width: 52px; text-align: center; padding: 5px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem; outline: none;
        }
        .qty-input:focus { border-color: var(--primary); }

        .btn-remove {
            background: none; border: none; cursor: pointer;
            color: var(--text-sub); font-size: 0.9rem;
            padding: 4px 8px; border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-remove:hover { color: #FF6B6B; background: rgba(239,68,68,0.1); }

        .empty-cart {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 60px 20px; color: var(--text-sub);
        }
        .empty-cart i { font-size: 3rem; margin-bottom: 12px; opacity: 0.3; }
        .empty-cart p { font-size: 0.85rem; text-align: center; line-height: 1.6; }

        /* ── RIGHT PANEL ── */
        .pos-right {
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex; flex-direction: column;
            padding: 16px; gap: 14px;
            overflow-y: auto;
        }
        .pos-right::-webkit-scrollbar { width: 5px; }
        .pos-right::-webkit-scrollbar-thumb { background: var(--surface-2); border-radius: 3px; }

        /* Summary Panel */
        .summary-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; font-size: 0.85rem;
        }
        .summary-row.total {
            border-top: 1px solid var(--border);
            margin-top: 6px; padding-top: 14px;
        }
        .summary-row.total .label { font-size: 0.88rem; font-weight: 700; }
        .summary-row.total .value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem; font-weight: 700; color: var(--green);
        }
        .summary-row .label { color: var(--text-sub); }
        .summary-row .value { font-family: 'JetBrains Mono', monospace; font-weight: 600; }

        .btn-pay {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white; border: none; border-radius: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem; font-weight: 800;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            letter-spacing: 0.5px;
        }
        .btn-pay:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(192,57,43,0.4); }
        .btn-pay:disabled { opacity: 0.35; cursor: not-allowed; }

        /* Search Panel */
        .search-panel {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            flex: 1; display: flex; flex-direction: column;
            overflow: hidden; min-height: 0;
        }
        .search-panel-header { padding: 12px 14px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .search-panel-header label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-sub); }
        .search-inner { padding: 10px 14px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .pos-search-input {
            width: 100%; background: var(--surface-2);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 9px 14px; color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.85rem; outline: none;
            transition: border-color 0.2s;
        }
        .pos-search-input:focus { border-color: var(--primary); }
        .pos-search-input::placeholder { color: var(--text-sub); }
        .lookup-wrap { flex: 1; overflow-y: auto; }
        .lookup-wrap::-webkit-scrollbar { width: 4px; }
        .lookup-wrap::-webkit-scrollbar-thumb { background: var(--surface-2); }

        table.lookup-tbl { width: 100%; border-collapse: collapse; }
        table.lookup-tbl td { padding: 9px 14px; font-size: 0.8rem; border-bottom: 1px solid var(--border); }
        table.lookup-tbl tr:hover { background: rgba(255,255,255,0.03); }
        .btn-add-item {
            background: rgba(192,57,43,0.15); border: 1px solid rgba(192,57,43,0.3);
            color: #FF9999; border-radius: 6px; padding: 3px 10px;
            font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .btn-add-item:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-add-item:disabled { opacity: 0.3; cursor: not-allowed; }

        .shortcut-row {
            display: flex; gap: 6px; padding: 12px 14px;
            flex-wrap: wrap; flex-shrink: 0;
        }
        .kbd {
            background: var(--surface-2); border: 1px solid var(--border);
            color: var(--text-sub); font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem; padding: 3px 8px; border-radius: 5px;
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
            z-index: 1000; display: none;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px; padding: 28px;
            width: 100%; max-width: 440px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
        }
        .modal-box h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: 20px; }
        .modal-group { margin-bottom: 16px; }
        .modal-label { font-size: 0.78rem; color: var(--text-sub); margin-bottom: 6px; display: block; }
        .modal-input {
            width: 100%; background: var(--surface-2);
            border: 1px solid var(--border); border-radius: 10px;
            padding: 11px 16px; color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem; outline: none; transition: border-color 0.2s;
        }
        .modal-input:focus { border-color: var(--primary); }

        .pay-methods { display: flex; gap: 8px; }
        .pay-method-btn {
            flex: 1; padding: 10px 8px;
            background: var(--surface-2); border: 2px solid var(--border);
            border-radius: 10px; color: var(--text-sub);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.78rem; font-weight: 600;
            cursor: pointer; text-align: center; transition: all 0.2s;
        }
        .pay-method-btn.selected { border-color: var(--primary); color: white; background: rgba(192,57,43,0.2); }

        .modal-summary {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px; padding: 16px;
            margin-bottom: 16px;
        }
        .modal-summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.85rem; }
        .modal-summary-row.total { padding-top: 12px; border-top: 1px solid var(--border); }
        .modal-summary-row.total .val { font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 700; color: var(--green); }
        .modal-kembalian { font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 700; }

        /* ── PRINT AREA ── */
        .print-area { display: none; }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
        }

        /* ── BADGES ── */
        .badge-mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem; padding: 2px 6px;
            border-radius: 4px; background: var(--surface-2);
            color: var(--text-sub); border: 1px solid var(--border);
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="pos-header">
        <div class="pos-brand">
            <div class="brand-text">MERAH<span>PUTIH</span></div>
            <div class="branch-pill">
                <i class="bi bi-geo-alt-fill"></i>
                <?php echo htmlspecialchars($nama_cabang); ?>
            </div>
            <div class="pos-clock" id="posClock">00:00:00</div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span style="color: var(--text-sub); font-size: 0.75rem; margin-right: 4px;">
                <span class="badge-mono">F4</span> Scan &nbsp;
                <span class="badge-mono">F2</span> Cari &nbsp;
                <span class="badge-mono">F8</span> Bayar
            </span>
            <a href="../admin/index.php" class="pos-nav-btn">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="../admin/produk.php" class="pos-nav-btn">
                <i class="bi bi-box-seam"></i> Produk
            </a>
            <a href="../admin/logout.php" class="pos-nav-btn danger">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </header>

    <!-- BODY -->
    <div class="pos-body">

        <!-- LEFT: Cart + Scanner -->
        <div class="pos-left">

            <!-- Scanner -->
            <div class="scanner-card" id="scannerCard">
                <div class="scanner-display">
                    <div class="scanner-display-label">Total Belanja</div>
                    <div class="scanner-total" id="lblNeonTotal">Rp 0</div>
                </div>
                <div class="scanner-input-wrap">
                    <div class="scanner-input-label">
                        <span class="badge-mono">F4</span> Scan Barcode / Kode Produk
                    </div>
                    <input type="text" id="posBarcodeScanner" class="scanner-input"
                        placeholder="Scan atau ketik kode..." autofocus
                        onkeypress="handleBarcodeScan(event)">
                </div>
            </div>

            <!-- Cart Table -->
            <div class="cart-card">
                <div class="cart-card-header">
                    <i class="bi bi-receipt-cutoff"></i>
                    Daftar Item Transaksi
                </div>
                <div class="cart-table-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th style="width: 32px;">#</th>
                                <th>Barang</th>
                                <th style="width: 90px;">Harga</th>
                                <th style="width: 60px; text-align: center;">Qty</th>
                                <th style="width: 100px; text-align: right;">Subtotal</th>
                                <th style="width: 40px;">✕</th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <tr id="emptyCartRow">
                                <td colspan="6">
                                    <div class="empty-cart">
                                        <i class="bi bi-upc-scan"></i>
                                        <p>Scan barcode barang atau gunakan<br>pencarian untuk memulai transaksi.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT: Payment + Search -->
        <div class="pos-right">

            <!-- Bill Summary -->
            <div class="summary-card">
                <div class="summary-row">
                    <span class="label">Subtotal</span>
                    <span class="value" id="lblSubtotal">Rp 0</span>
                </div>
                <div class="summary-row total">
                    <span class="label">TOTAL BAYAR</span>
                    <span class="value" id="lblTotal">Rp 0</span>
                </div>
            </div>

            <button class="btn-pay" id="btnCheckout" onclick="showCheckoutModal()" disabled>
                <i class="bi bi-cash-coin" style="font-size: 1.1rem;"></i>
                BAYAR &amp; SELESAI
                <span class="badge-mono" style="font-size: 0.65rem;">F8</span>
            </button>

            <!-- Product Search -->
            <div class="search-panel">
                <div class="search-panel-header">
                    <label><span class="badge-mono">F2</span> &nbsp;Cari Barang Manual</label>
                </div>
                <div class="search-inner">
                    <input type="text" id="posSearch" class="pos-search-input"
                        placeholder="Ketik nama / barcode..."
                        onkeyup="filterProducts()">
                </div>
                <div class="lookup-wrap">
                    <table class="lookup-tbl">
                        <tbody id="lookupTableBody"></tbody>
                    </table>
                </div>
                <div class="shortcut-row" style="border-top: 1px solid var(--border);">
                    <span style="font-size: 0.72rem; color: var(--text-sub);">Shortcut:</span>
                    <span class="kbd">F4</span><span style="font-size: 0.72rem; color: var(--text-sub);">Scan</span>
                    <span class="kbd">F2</span><span style="font-size: 0.72rem; color: var(--text-sub);">Cari</span>
                    <span class="kbd">F8</span><span style="font-size: 0.72rem; color: var(--text-sub);">Bayar</span>
                </div>
            </div>

        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div class="modal-overlay" id="checkoutOverlay">
        <div class="modal-box">
            <form method="POST" id="posCheckoutForm">
                <h3><i class="bi bi-cash-coin" style="color: var(--primary); margin-right: 8px;"></i>Pembayaran Transaksi</h3>

                <div class="modal-group">
                    <label class="modal-label">Nama Pelanggan (Opsional)</label>
                    <input type="text" name="nama_penerima" class="modal-input" placeholder="Pelanggan Umum" value="Pelanggan Umum">
                </div>
                <div class="modal-group">
                    <label class="modal-label">No. WhatsApp (Opsional)</label>
                    <input type="number" name="no_telp" class="modal-input" placeholder="08xxxx">
                </div>
                <div class="modal-group">
                    <label class="modal-label">Metode Pembayaran</label>
                    <div class="pay-methods">
                        <input type="radio" name="metode_bayar" id="pay-tunai" value="tunai" checked class="d-none" onchange="toggleCashInput(true)">
                        <label for="pay-tunai" class="pay-method-btn selected" onclick="selectPayMethod(this)">
                            <i class="bi bi-cash d-block mb-1" style="font-size: 1.2rem;"></i>Tunai
                        </label>

                        <input type="radio" name="metode_bayar" id="pay-qris" value="qris_offline" class="d-none" onchange="toggleCashInput(false)">
                        <label for="pay-qris" class="pay-method-btn" onclick="selectPayMethod(this)">
                            <i class="bi bi-qr-code d-block mb-1" style="font-size: 1.2rem;"></i>QRIS
                        </label>

                        <input type="radio" name="metode_bayar" id="pay-debit" value="debit" class="d-none" onchange="toggleCashInput(false)">
                        <label for="pay-debit" class="pay-method-btn" onclick="selectPayMethod(this)">
                            <i class="bi bi-credit-card-2-back d-block mb-1" style="font-size: 1.2rem;"></i>Debit
                        </label>
                    </div>
                </div>

                <div class="modal-summary">
                    <div class="modal-summary-row">
                        <span style="color: var(--text-sub);">Tagihan:</span>
                        <span id="modalTotalTagihan">Rp 0</span>
                    </div>
                    <div id="cashSection">
                        <div class="modal-summary-row">
                            <span style="color: var(--text-sub);">Uang Diterima:</span>
                            <input type="number" name="uang_bayar" id="txtBayar" class="modal-input"
                                style="width: 160px; text-align: right; padding: 6px 10px; font-family: 'JetBrains Mono', monospace; font-size: 0.95rem;"
                                placeholder="0" onkeyup="calculateChange()" required>
                        </div>
                        <div class="modal-summary-row total">
                            <span class="label" style="font-weight: 700;">Kembalian:</span>
                            <span class="val modal-kembalian" id="lblKembalian">Rp 0</span>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="total_bayar" id="hiddenTotal">
                <input type="hidden" name="uang_kembali" id="hiddenKembali" value="0">
                <input type="hidden" name="items_json" id="hiddenItemsJson">
                <input type="hidden" name="proses_transaksi" value="1">

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-pay" style="flex: 0 0 44px; border-radius: 10px; padding: 12px; background: var(--surface-2); color: var(--text-sub);" onclick="closeCheckoutModal()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <button type="submit" class="btn-pay" id="btnSubmitCheckout" style="flex: 1; border-radius: 10px;">
                        <i class="bi bi-printer"></i> SELESAI &amp; CETAK STRUK
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRINT RECEIPT AREA -->
    <?php if ($print_pesanan): ?>
    <div class="print-area" id="printArea">
        <div style="text-align: center; font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; padding: 10px; color: #000; font-size: 12px; line-height: 1.4;">
            <h3 style="margin: 0; font-size: 15px;">MERAH PUTIH STORE</h3>
            <p style="margin: 4px 0 10px; font-size: 9px; color: #333;">
                <?php echo htmlspecialchars($print_pesanan['nama_cabang']); ?><br>
                <?php echo htmlspecialchars($print_pesanan['alamat_cabang'] ?: 'Premium Offline Store'); ?><br>
                Telp: <?php echo htmlspecialchars($print_pesanan['telp_cabang'] ?: '-'); ?>
            </p>
            <div style="text-align: left; font-size: 10px; margin-bottom: 6px;">
                No. Nota : #<?php echo $print_pesanan['id_pesanan']; ?><br>
                Tgl/Jam  : <?php echo date('d/m/Y H:i', strtotime($print_pesanan['tgl_pesan'])); ?><br>
                Customer : <?php echo htmlspecialchars($print_pesanan['nama_penerima']); ?><br>
                Metode   : <?php echo strtoupper($print_pesanan['metode_bayar']); ?>
            </div>
            <p style="font-size: 10px;">================================</p>
            <table style="width: 100%; font-size: 10px; border-collapse: collapse; text-align: left;">
                <?php foreach ($print_items as $item): ?>
                <tr><td colspan="3" style="font-weight: bold; padding-bottom: 2px;"><?php echo htmlspecialchars($item['nama_produk']); ?></td></tr>
                <tr>
                    <td style="width: 50%; padding-bottom: 5px;"><?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['subtotal'] / $item['jumlah'], 0, ',', '.'); ?></td>
                    <td></td>
                    <td style="width: 40%; text-align: right; padding-bottom: 5px;">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p style="font-size: 10px; margin: 6px 0;">================================</p>
            <table style="width: 100%; font-size: 10px;">
                <tr><td>Total:</td><td style="text-align: right; font-weight: bold;">Rp <?php echo number_format($print_pesanan['total_bayar'], 0, ',', '.'); ?></td></tr>
                <?php if ($print_pesanan['metode_bayar'] == 'tunai'): ?>
                <tr><td>Bayar:</td><td style="text-align: right;">Rp <?php echo number_format((int)$_GET['bayar'], 0, ',', '.'); ?></td></tr>
                <tr><td>Kembali:</td><td style="text-align: right; font-weight: bold;">Rp <?php echo number_format((int)$_GET['kembali'], 0, ',', '.'); ?></td></tr>
                <?php endif; ?>
            </table>
            <p style="font-size: 10px; margin: 14px 0 5px;">================================</p>
            <p style="font-size: 10px; font-style: italic;">Terima kasih atas kunjungan Anda!<br>Semoga harimu menyenangkan ❤️</p>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.print();
            setTimeout(() => { window.location.href = 'index.php'; }, 1000);
        });
    </script>
    <?php endif; ?>

    <script>
    const productsList = <?php echo json_encode($prods); ?>;
    let currentCart = {};

    document.addEventListener('DOMContentLoaded', function() {
        renderLookupTable();
        document.getElementById('posBarcodeScanner').focus();

        setInterval(() => {
            const now = new Date();
            document.getElementById('posClock').textContent = now.toTimeString().split(' ')[0];
        }, 1000);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'F4') { e.preventDefault(); document.getElementById('posBarcodeScanner').focus(); document.getElementById('posBarcodeScanner').select(); }
            if (e.key === 'F2') { e.preventDefault(); document.getElementById('posSearch').focus(); document.getElementById('posSearch').select(); }
            if (e.key === 'F8' || e.key === 'End') { e.preventDefault(); if (Object.keys(currentCart).length > 0) showCheckoutModal(); }
        });

        document.getElementById('txtBayar').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !document.getElementById('btnSubmitCheckout').disabled) {
                document.getElementById('posCheckoutForm').submit();
            }
        });
    });

    function formatRupiah(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    }

    function renderLookupTable() {
        const tbody = document.getElementById('lookupTableBody');
        tbody.innerHTML = '';
        const query = document.getElementById('posSearch').value.toLowerCase();
        let filtered = productsList;
        if (query.trim() !== '') {
            filtered = filtered.filter(p => 
                p.nama_produk.toLowerCase().includes(query) || 
                p.nama_satuan.toLowerCase().includes(query) || 
                (p.barcode_var && p.barcode_var.toLowerCase().includes(query)) ||
                (p.barcode_parent && p.barcode_parent.toLowerCase().includes(query))
            );
        } else {
            filtered = filtered.slice(0, 8);
        }
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--text-sub); padding: 20px; font-size: 0.8rem;">Tidak ditemukan</td></tr>';
            return;
        }
        filtered.forEach(p => {
            const availQty = Math.floor(p.stok_master / p.faktor_kali);
            const oos = availQty <= 0;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="font-family: 'JetBrains Mono', monospace; color: var(--text-sub); font-size: 0.72rem;">${p.barcode_var || p.barcode_parent || '-'}</td>
                <td style="font-weight: 600;">${p.nama_produk} <span class="text-warning">(${p.nama_satuan})</span></td>
                <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">${formatRupiah(p.harga)}</td>
                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; color: ${availQty > 0 ? 'var(--green)' : '#FF6B6B'};">${availQty}</td>
                <td style="text-align: center;">
                    <button class="btn-add-item" ${oos ? 'disabled' : ''} onclick="addToCart(${p.id_kemasan})">+</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function filterProducts() { renderLookupTable(); }

    function handleBarcodeScan(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = document.getElementById('posBarcodeScanner').value.trim();
            if (val === '') {
                if (Object.keys(currentCart).length > 0) showCheckoutModal();
                return;
            }
            
            // Prioritize matching specific variant barcode first
            let variant = productsList.find(p => p.barcode_var === val);
            if (!variant) {
                // Otherwise fall back to parent barcode, picking the 'Pcs' variant (factor = 1) if available
                const matches = productsList.filter(p => p.barcode_parent === val);
                if (matches.length > 0) {
                    variant = matches.find(p => p.faktor_kali === 1) || matches[0];
                }
            }
            if (!variant) {
                // Try finding by ID
                variant = productsList.find(p => p.id_kemasan == val || p.id_produk == val);
            }
            
            if (variant) {
                const availQty = Math.floor(variant.stok_master / variant.faktor_kali);
                if (availQty <= 0) {
                    alert(`Stok "${variant.nama_produk} (${variant.nama_satuan})" habis!`);
                } else {
                    addToCart(variant.id_kemasan);
                    flashScanner();
                }
            } else {
                alert(`Kode "${val}" tidak ditemukan!`);
            }
            document.getElementById('posBarcodeScanner').value = '';
            document.getElementById('posBarcodeScanner').focus();
        }
    }

    function flashScanner() {
        const card = document.getElementById('scannerCard');
        card.classList.add('flash');
        setTimeout(() => card.classList.remove('flash'), 600);
    }

    function addToCart(idKemasan) {
        const itemVar = productsList.find(p => p.id_kemasan == idKemasan);
        if (!itemVar) return;
        
        const availQty = Math.floor(itemVar.stok_master / itemVar.faktor_kali);
        const currentQty = currentCart[idKemasan] ? currentCart[idKemasan].qty : 0;
        if (currentQty >= availQty) {
            alert(`Stok "${itemVar.nama_produk} (${itemVar.nama_satuan})" terbatas (${availQty} ${itemVar.nama_satuan}).`);
            return;
        }
        if (currentCart[idKemasan]) {
            currentCart[idKemasan].qty++;
        } else {
            currentCart[idKemasan] = {
                id_kemasan: itemVar.id_kemasan,
                id_produk: itemVar.id_produk,
                nama: itemVar.nama_produk + ' (' + itemVar.nama_satuan + ')',
                nama_satuan: itemVar.nama_satuan,
                faktor_kali: parseInt(itemVar.faktor_kali),
                barcode: itemVar.barcode_var || itemVar.barcode_parent,
                harga: parseInt(itemVar.harga),
                harga_grosir: itemVar.nama_satuan === 'Pcs' && itemVar.harga_grosir ? parseInt(itemVar.harga_grosir) : null,
                min_qty_grosir: itemVar.nama_satuan === 'Pcs' && itemVar.min_qty_grosir ? parseInt(itemVar.min_qty_grosir) : null,
                stok_master: parseInt(itemVar.stok_master),
                qty: 1
            };
        }
        updateCartUI();
        document.getElementById('posBarcodeScanner').focus();
    }

    function changeQty(idKemasan, inputEl) {
        const qty = parseInt(inputEl.value) || 0;
        const variant = productsList.find(p => p.id_kemasan == idKemasan);
        const availQty = Math.floor(variant.stok_master / variant.faktor_kali);
        if (qty <= 0) {
            delete currentCart[idKemasan];
        } else if (qty > availQty) {
            alert(`Stok terbatas: ${availQty} ${variant.nama_satuan}.`);
            inputEl.value = currentCart[idKemasan].qty;
            return;
        } else {
            currentCart[idKemasan].qty = qty;
        }
        updateCartUI();
        document.getElementById('posBarcodeScanner').focus();
    }

    function removeItem(idKemasan) {
        delete currentCart[idKemasan];
        updateCartUI();
        document.getElementById('posBarcodeScanner').focus();
    }

    function updateCartUI() {
        const tbody = document.getElementById('cartTableBody');
        const emptyRow = document.getElementById('emptyCartRow');
        const keys = Object.keys(currentCart);

        // Remove old cart rows
        document.querySelectorAll('.cart-row').forEach(r => r.remove());

        if (keys.length === 0) {
            emptyRow.style.display = '';
            document.getElementById('lblSubtotal').textContent = 'Rp 0';
            document.getElementById('lblTotal').textContent = 'Rp 0';
            document.getElementById('lblNeonTotal').textContent = 'Rp 0';
            document.getElementById('btnCheckout').disabled = true;
            return;
        }

        emptyRow.style.display = 'none';
        document.getElementById('btnCheckout').disabled = false;
        let total = 0;
        let idx = 1;

        keys.forEach(key => {
            const item = currentCart[key];
            let price = item.harga;
            let isWholesale = false;
            if (item.harga_grosir && item.qty >= item.min_qty_grosir) {
                price = item.harga_grosir; isWholesale = true;
            }
            const sub = price * item.qty;
            item.subtotal = sub;
            total += sub;

            const tr = document.createElement('tr');
            tr.className = 'cart-row';
            tr.innerHTML = `
                <td style="color: var(--text-sub); font-size: 0.75rem;">${idx++}</td>
                <td>
                    <span style="font-weight: 600; font-size: 0.88rem;">${item.nama}</span>
                    ${isWholesale ? '<span style="background: rgba(192,57,43,0.2); color: #FF9999; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">Grosir</span>' : ''}
                </td>
                <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">${formatRupiah(price)}</td>
                <td style="text-align: center;">
                    <input type="number" class="qty-input" value="${item.qty}" onchange="changeQty(${item.id_kemasan}, this)">
                </td>
                <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.85rem;">${formatRupiah(sub)}</td>
                <td style="text-align: center;">
                    <button class="btn-remove" onclick="removeItem(${item.id_kemasan})"><i class="bi bi-trash3"></i></button>
                </td>
            `;
            tbody.insertBefore(tr, emptyRow);
        });

        document.getElementById('lblSubtotal').textContent = formatRupiah(total);
        document.getElementById('lblTotal').textContent = formatRupiah(total);
        document.getElementById('lblNeonTotal').textContent = formatRupiah(total);
    }

    function showCheckoutModal() {
        let total = 0;
        const items = [];
        Object.keys(currentCart).forEach(k => {
            const item = currentCart[k];
            total += item.subtotal;
            items.push(item);
        });
        document.getElementById('modalTotalTagihan').textContent = formatRupiah(total);
        document.getElementById('hiddenTotal').value = total;
        document.getElementById('hiddenItemsJson').value = JSON.stringify(items);
        document.getElementById('txtBayar').value = total;
        document.getElementById('hiddenKembali').value = 0;
        document.getElementById('lblKembalian').textContent = 'Rp 0';
        document.getElementById('checkoutOverlay').classList.add('open');
        const isCash = document.querySelector('input[name="metode_bayar"]:checked').value === 'tunai';
        toggleCashInput(isCash);
        setTimeout(() => { if (isCash) { document.getElementById('txtBayar').focus(); document.getElementById('txtBayar').select(); } }, 300);
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutOverlay').classList.remove('open');
        document.getElementById('posBarcodeScanner').focus();
    }

    document.getElementById('checkoutOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeCheckoutModal();
    });

    function selectPayMethod(el) {
        document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('selected'));
        el.classList.add('selected');
        const isCash = el.getAttribute('for') === 'pay-tunai';
        toggleCashInput(isCash);
    }

    function toggleCashInput(isCash) {
        const section = document.getElementById('cashSection');
        section.style.display = isCash ? '' : 'none';
        if (!isCash) {
            const total = parseInt(document.getElementById('hiddenTotal').value);
            document.getElementById('txtBayar').value = total;
            document.getElementById('hiddenKembali').value = 0;
            document.getElementById('btnSubmitCheckout').disabled = false;
        } else {
            calculateChange();
        }
    }

    function calculateChange() {
        const total = parseInt(document.getElementById('hiddenTotal').value) || 0;
        const bayar = parseInt(document.getElementById('txtBayar').value) || 0;
        const kembalian = bayar - total;
        const lblK = document.getElementById('lblKembalian');
        if (kembalian >= 0) {
            lblK.textContent = formatRupiah(kembalian);
            lblK.style.color = 'var(--green)';
            document.getElementById('hiddenKembali').value = kembalian;
            document.getElementById('btnSubmitCheckout').disabled = false;
        } else {
            lblK.textContent = 'Uang Kurang!';
            lblK.style.color = '#FF6B6B';
            document.getElementById('btnSubmitCheckout').disabled = true;
        }
    }
    </script>
</body>
</html>
