<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'wCvTiLwLrN6QefFvKHghIivSyma');
define('DB_NAME', 'token_gate_db');

// Koneksi PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Tidak bisa terhubung. " . $e->getMessage());
}

// Konfigurasi Aplikasi
define('EXAM_URL', 'https://pribadi.smpn3ppu.sch.id/heisiswasmpn3kamuharusjujurdalamujian/public/login/index.php');
define('TOKEN_ROTATION_MINUTES', 15); // Interval rotasi dalam menit
define('TOKEN_LENGTH', 6); // Panjang token (6 huruf alphabet)
define('TOKEN_PATTERN', '[A-Z]{6}'); // Pattern untuk validation

// Konfigurasi Admin (Username dan Password)
define('ADMIN_USERNAME', 'ADMIN');
define('ADMIN_PASSWORD', 'ADMIN12345');
?>