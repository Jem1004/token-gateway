<?php
require_once 'config.php';

/**
 * Generate random alphanumeric token
 * @param int $length Token length
 * @return string Generated token
 */
function generateRandomToken($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

try {
    // Generate token baru
    $new_token = generateRandomToken(8);

    // Update token di database
    $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
    $stmt->execute([':token' => $new_token]);

    // Log untuk debugging (opsional)
    // error_log("Token rotated to: " . $new_token . " at " . date('Y-m-d H:i:s'));

} catch (PDOException $e) {
    // Log error untuk debugging
    error_log("Token rotation failed: " . $e->getMessage());
}

/*
 * Konfigurasi Cron Job untuk Ubuntu Server:
 *
 * Buka crontab dengan perintah: crontab -e
 * Tambahkan baris ini untuk menjalankan script setiap 10 menit:
 *
 * */10 * * * * /usr/bin/php /var/www/html/path/ke/project/rotate_token.php > /dev/null 2>&1
 *
 * Pastikan path PHP dan path file sudah benar sesuai konfigurasi server Anda.
 * Untuk testing, bisa gunakan interval 1 menit: */1 * * * *
 *
 * Untuk melihat log cron: grep CRON /var/log/syslog
 *
 */
?>