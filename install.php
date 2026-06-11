<?php
/**
 * Database Setup Script for Production
 * Run this ONCE on production server, then DELETE this file
 */

include 'config.php';

echo "<h2>MerahPutih - Database Setup</h2>";

// Create all necessary tables
$tables_created = 0;

// 1. Admin Users Table
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'admin_users' created<br>";
    $tables_created++;
    
    // Insert default admin (CHANGE PASSWORD IMMEDIATELY!)
    $default_pass = password_hash('admin123', PASSWORD_BCRYPT);
    mysqli_query($koneksi, "INSERT IGNORE INTO admin_users (username, password) VALUES ('admin', '$default_pass')");
}

// 2. Categories Table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'categories' created<br>";
    $tables_created++;
    
    // Insert default categories
    mysqli_query($koneksi, "INSERT IGNORE INTO categories (id, nama_kategori) VALUES 
        (1, 'Fashion'),
        (2, 'Elektronik'),
        (3, 'Makanan & Minuman'),
        (4, 'Kesehatan & Kecantikan'),
        (5, 'Lainnya')");
}

// 3. Products Table
$sql = "CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(255) NOT NULL,
    harga INT NOT NULL,
    harga_grosir INT DEFAULT NULL,
    min_qty_grosir INT DEFAULT 2,
    deskripsi TEXT,
    foto VARCHAR(255),
    kategori_id INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES categories(id) ON DELETE SET NULL
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'produk' created<br>";
    $tables_created++;
}

// 4. Orders Table
$sql = "CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
    nama_penerima VARCHAR(100) NOT NULL,
    no_telp VARCHAR(20) NOT NULL,
    alamat_penerima TEXT NOT NULL,
    total_bayar INT NOT NULL,
    metode_bayar VARCHAR(20) DEFAULT 'cod',
    status VARCHAR(20) DEFAULT 'pending',
    tgl_pesan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'pesanan' created<br>";
    $tables_created++;
}

// 5. Order Details Table
$sql = "CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'detail_pesanan' created<br>";
    $tables_created++;
}

// 6. Product Reviews Table
$sql = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    nama_reviewer VARCHAR(100),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'product_reviews' created<br>";
    $tables_created++;
}

// 7. Payment Confirmations Table
$sql = "CREATE TABLE IF NOT EXISTS payment_confirmations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    nama_pengirim VARCHAR(100),
    bank_pengirim VARCHAR(50),
    jumlah_transfer INT,
    tanggal_transfer DATE,
    bukti_foto VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'payment_confirmations' created<br>";
    $tables_created++;
}

// 8. Payment Methods Table
$sql = "CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_metode VARCHAR(50) NOT NULL,
    kode VARCHAR(20) NOT NULL,
    deskripsi TEXT,
    detail_pembayaran TEXT,
    icon VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($koneksi, $sql)) {
    echo "✅ Table 'payment_methods' created<br>";
    $tables_created++;
    
    // Insert default payment methods
    mysqli_query($koneksi, "INSERT IGNORE INTO payment_methods (nama_metode, kode, deskripsi, detail_pembayaran, icon) VALUES 
        ('Cash on Delivery (COD)', 'cod', 'Bayar saat barang diterima', 'Pembayaran dilakukan saat barang sampai di tangan Anda.', 'bi-cash-coin'),
        ('Transfer Bank', 'transfer', 'Transfer ke rekening bank', 'Bank BCA: 1234567890 a.n. MerahPutih Store', 'bi-bank'),
        ('QRIS', 'qris', 'Scan QR Code untuk bayar', 'https://via.placeholder.com/300x300?text=QRIS+Code', 'bi-qr-code')");
}

// Create necessary folders
$folders = [
    '../assets/produk',
    '../assets/bukti_bayar'
];

foreach($folders as $folder) {
    if(!file_exists($folder)) {
        mkdir($folder, 0755, true);
        echo "✅ Folder '$folder' created<br>";
    }
}

echo "<hr>";
echo "<h3>Setup Complete! ✅</h3>";
echo "<p><strong>$tables_created tables</strong> created successfully.</p>";
echo "<p style='color: red;'><strong>IMPORTANT:</strong></p>";
echo "<ul>";
echo "<li>❗ Change admin password immediately via database</li>";
echo "<li>❗ Update payment method details in admin panel</li>";
echo "<li>❗ DELETE this file (install.php) for security</li>";
echo "<li>❗ Update config.php with production database credentials</li>";
echo "</ul>";
?>
