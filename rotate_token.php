<?php
require_once 'config.php';

/**
 * Generate random alphabet token (6 huruf besar)
 * @param int $length Token length
 * @return string Generated token
 */
function generateRandomToken($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

// Detect if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ||
          isset($_SERVER['HTTP_CONTENT_TYPE']) &&
          strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false;

try {
    // Generate token baru (6 huruf alphabet)
    $new_token = generateRandomToken(TOKEN_LENGTH);

    // Update token di database
    $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
    $result = $stmt->execute([':token' => $new_token]);

    // Log untuk debugging
    error_log("Token rotated to: " . $new_token . " at " . date('Y-m-d H:i:s'));

    if ($result) {
        if ($isAjax) {
            // Return JSON response untuk AJAX calls
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'token' => $new_token,
                'message' => 'Token berhasil dirotasi',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            // For cron job, return plain text (minimal output for cron)
            echo "Token rotated successfully: " . $new_token . " at " . date('Y-m-d H:i:s');
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Tidak ada baris yang terpengaruh saat update token'
            ]);
        } else {
            echo "ERROR: No rows affected during token rotation";
            exit(1);
        }
    }

} catch (PDOException $e) {
    // Log error untuk debugging
    error_log("Token rotation failed: " . $e->getMessage());

    if ($isAjax) {
        // Return JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    } else {
        echo "ERROR: Database error - " . $e->getMessage();
        exit(1);
    }
} catch (Exception $e) {
    // Log general error
    error_log("Token rotation general error: " . $e->getMessage());

    if ($isAjax) {
        // Return JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'General error: ' . $e->getMessage()
        ]);
    } else {
        echo "ERROR: General error - " . $e->getMessage();
        exit(1);
    }
}

/*
 * Konfigurasi Cron Job untuk Ubuntu Server:
 *
 * Buka crontab dengan perintah: crontab -e
 * Tambahkan baris ini untuk menjalankan script setiap 15 menit:
 *
 * */15 * * * * /usr/bin/php /var/www/html/path/ke/project/rotate_token.php > /dev/null 2>&1
 *
 * Pastikan path PHP dan path file sudah benar sesuai konfigurasi server Anda.
 * Untuk testing, bisa gunakan interval 1 menit: */1 * * * *
 *
 * Untuk melihat log cron: grep CRON /var/log/syslog
 *
 * Script ini juga support AJAX calls dari admin panel.
 *
 */
?>