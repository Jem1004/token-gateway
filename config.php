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
define('EXAM_URL', 'http://www.xxxxx.sch.id');
define('TOKEN_ROTATION_MINUTES', 10); // Interval rotasi dalam menit

// Konfigurasi Admin (Username dan Password)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');
?>