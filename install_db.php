<?php
// Direct connection without database first to check/create it
$koneksi = mysqli_connect("localhost", "root", "");
if (!$koneksi) { die("Koneksi Server Gagal: " . mysqli_connect_error()); }

// Create Database if not exists
$db_selected = mysqli_select_db($koneksi, "merahputih");
if (!$db_selected) {
    echo "Database 'merahputih' not found. Creating...<br>";
    if (mysqli_query($koneksi, "CREATE DATABASE merahputih")) {
        echo "Database created.<br>";
        mysqli_select_db($koneksi, "merahputih");
    } else {
        die("Error creating database: " . mysqli_error($koneksi));
    }
} else {
    echo "Database connected.<br>";
}

// Create admin_users table
$query = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($koneksi, $query)) {
    echo "Table 'admin_users' checked/created.<br>";
} else {
    echo "Error creating table: " . mysqli_error($koneksi) . "<br>";
}

// Check if admin exists
$check = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'admin'");
if (mysqli_num_rows($check) == 0) {
    // Insert default admin
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = "INSERT INTO admin_users (username, password) VALUES ('admin', '$pass')";
    if (mysqli_query($koneksi, $insert)) {
        echo "Default admin account created (User: admin, Pass: admin123).<br>";
    } else {
        echo "Error creating admin: " . mysqli_error($koneksi) . "<br>";
    }
} else {
    echo "Admin account already exists.<br>";
}

// Check/Fix 'pesanan' table column
// First check if table exists
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'pesanan'");
if(mysqli_num_rows($check_table) > 0) {
    $check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'tgl_pesan'");
    if(mysqli_num_rows($check_col) == 0) {
        // If tgl_pesan missing, check if tanggal_pesan exists
        $check_old = mysqli_query($koneksi, "SHOW COLUMNS FROM pesanan LIKE 'tanggal_pesan'");
        if(mysqli_num_rows($check_old) > 0) {
            echo "Note: Table uses 'tanggal_pesan'. We will adapt our code to this or you can rename it.<br>";
             // Rename column to standard 'tgl_pesan' if desirable, BUT
             // The Code in cek_pesanan.php WAS using 'tanggal_pesan' (and apparently broken? or it was just a mismatch).
             // Wait, the PROPOSAL was to change code to 'tgl_pesan'.
             // Let's standardise the DB to 'tgl_pesan' if 'tanggal_pesan' exists to fix future confusion?
             // No, safer to just use what's there. 
             // Actually, let's just REPORT it.
             echo "Column 'tanggal_pesan' found. Code should use 'tanggal_pesan' or we rename it.<br>";
        } else {
             // Create it if neither exists??
             echo "WARNING: Neither 'tgl_pesan' nor 'tanggal_pesan' found in pesanan table!<br>";
        }
    } else {
        echo "Column 'tgl_pesan' confirmed.<br>";
    }
} else {
    echo "Table 'pesanan' does not exist yet. It will probably be created by application usage or another SQL file?<br>";
     // Since we didn't find sql files, we assume the user already has a running DB or we need to CREATE the schema.
    // Let's create the basic schema if missing since we are "Maximizing" it.
    
    $sql_pesanan = "CREATE TABLE IF NOT EXISTS pesanan (
        id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
        nama_penerima VARCHAR(100),
        no_telp VARCHAR(20),
        alamat_penerima TEXT,
        total_bayar INT,
        status VARCHAR(20) DEFAULT 'pending',
        tgl_pesan DATE,
        tanggal_pesan DATE 
    )"; 
    // ^ Making sure we have columns to avoid errors. 
    // Actually, let's not blindly create it without knowing the desired schema.
    // The previous error implied the code was running so tables likely exist.
}

echo "Setup script finished.";
?>
