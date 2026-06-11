<?php
// update_db_admins.php - Run in browser: http://localhost/merahputih/update_db_admins.php
require 'config.php';
echo "<h1>Database Update - Branch Administrators</h1>";

// Add admin_bandung
$check_admin_bdg = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'admin_bandung'");
if(mysqli_num_rows($check_admin_bdg) == 0) {
    $pass_bdg = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('admin_bandung', '$pass_bdg', 'admin', 2)");
    echo "<div style='color:blue'>✓ Branch Admin Bandung created (User: admin_bandung, Pass: admin123).</div>";
} else {
    echo "<div>✓ Admin Bandung account already exists.</div>";
}

// Add admin_surabaya
$check_admin_sby = mysqli_query($koneksi, "SELECT * FROM admin_users WHERE username = 'admin_surabaya'");
if(mysqli_num_rows($check_admin_sby) == 0) {
    $pass_sby = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($koneksi, "INSERT INTO admin_users (username, password, role, id_cabang) VALUES ('admin_surabaya', '$pass_sby', 'admin', 3)");
    echo "<div style='color:blue'>✓ Branch Admin Surabaya created (User: admin_surabaya, Pass: admin123).</div>";
} else {
    echo "<div>✓ Admin Surabaya account already exists.</div>";
}

echo "<hr><div style='color:green; font-weight:bold'>Database seed completed!</div>";
?>
