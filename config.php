<?php
/**
 * Token Gate Configuration
 *
 * Time Configuration:
 * - Using Asia/Jakarta timezone (WIB - GMT+7)
 * - All time operations will use server time with consistent timezone
 */

// Set default timezone untuk semua fungsi waktu PHP
date_default_timezone_set('Asia/Jakarta');

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

// Konfigurasi Waktu
define('APP_TIMEZONE', 'Asia/Jakarta'); // Timezone yang digunakan (WIB - GMT+7)
define('SERVER_TIME_FORMAT', 'Y-m-d H:i:s'); // Format waktu untuk database
define('DISPLAY_TIME_FORMAT', 'd M Y, H:i:s'); // Format waktu untuk tampilan
define('TIME_FORMAT_SHORT', 'H:i'); // Format waktu pendek
define('SECONDS_PER_MINUTE', 60); // Konstanta untuk konversi waktu

// Konfigurasi Admin (Username dan Password)
define('ADMIN_USERNAME', 'ADMIN');
define('ADMIN_PASSWORD', 'ADMIN12345');
?>