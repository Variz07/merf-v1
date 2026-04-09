<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projek_merfv1');

// Site Configuration
define('SITE_NAME', 'MERF Marketplace');
define('SITE_URL', 'http://localhost/merf-v1/');
define('SITE_DESC', 'Platform E-Commerce untuk Mahasiswa President University');
define('CURRENCY', 'Rp ');
define('UPLOAD_PATH', 'assets/images/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Error Reporting - MATIKAN DISPLAY ERRORS untuk produksi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Session Configuration - INI SUDAH CUKUP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Maaf, terjadi kesalahan koneksi database. Silakan coba lagi nanti.");
}

// HANYA INCLUDE functions.php - HAPUS session.php
require_once __DIR__ . '/includes/functions.php';
// require_once __DIR__ . '/includes/session.php'; // <-- HAPUS BARIS INI!
?>