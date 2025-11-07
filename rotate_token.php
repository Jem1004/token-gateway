<?php
require_once 'config.php';

// Prevent any output before JSON
if (ob_get_length()) ob_clean();

// Error reporting untuk debugging (disable di production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output
ini_set('log_errors', 1);     // Log error ke file log

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
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_CONTENT_TYPE']) &&
          strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) ||
          (isset($_SERVER['HTTP_ACCEPT']) &&
          strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

try {
    // Generate token baru (6 huruf alphabet)
    $new_token = generateRandomToken(TOKEN_LENGTH);

    // Log untuk debugging
    error_log("Attempting to rotate token to: " . $new_token . " at " . date(SERVER_TIME_FORMAT) . " [" . APP_TIMEZONE . "]");

    // First, check if record exists
    $check_stmt = $pdo->prepare("SELECT id FROM app_config WHERE id = 1");
    $check_stmt->execute();
    $exists = $check_stmt->fetch();

    if (!$exists) {
        // Insert new record if it doesn't exist
        error_log("No record found with id = 1, inserting new record");
        $insert_stmt = $pdo->prepare("INSERT INTO app_config (id, current_token, last_rotated) VALUES (1, :token, CURRENT_TIMESTAMP)");
        $result = $insert_stmt->execute([':token' => $new_token]);
        error_log("Insert result: " . ($result ? 'success' : 'failed'));
    } else {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
        $result = $stmt->execute([':token' => $new_token]);
        $affected_rows = $stmt->rowCount();
        error_log("Update result: " . ($result ? 'success' : 'failed') . ", affected rows: " . $affected_rows);

        // If no rows were affected, it might be a data issue, try to force update
        if ($affected_rows === 0) {
            error_log("No rows affected, trying to diagnose the issue");

            // Get current data
            $current_stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
            $current_stmt->execute();
            $current_data = $current_stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_data) {
                error_log("Current data in database: " . json_encode($current_data));

                // Try a different approach - update regardless of current values
                $force_stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1 LIMIT 1");
                $force_result = $force_stmt->execute([':token' => $new_token]);
                $force_affected = $force_stmt->rowCount();
                error_log("Force update result: " . ($force_result ? 'success' : 'failed') . ", affected rows: " . $force_affected);

                $result = $force_result;
            }
        }
    }

    // Verify the operation
    if ($result) {
        // Verify the token was actually saved
        $verify_stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
        $verify_stmt->execute();
        $saved_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);

        if ($saved_data && $saved_data['current_token'] === $new_token) {
            error_log("Token rotation verified successfully: " . $new_token);

            if ($isAjax) {
                // Return JSON response untuk AJAX calls
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'token' => $new_token,
                    'message' => 'Token berhasil dirotasi',
                    'timestamp' => date(SERVER_TIME_FORMAT),
                    'timezone' => APP_TIMEZONE,
                    'last_rotated' => $saved_data['last_rotated']
                ]);
            } else {
                // For cron job, return plain text (minimal output for cron)
                echo "Token rotated successfully: " . $new_token . " at " . date(SERVER_TIME_FORMAT) . " [" . APP_TIMEZONE . "]";
            }
        } else {
            error_log("Token rotation verification failed! Expected: " . $new_token . ", Found: " . ($saved_data['current_token'] ?? 'NULL'));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token rotation verification failed - token not saved properly'
                ]);
            } else {
                echo "ERROR: Token rotation verification failed";
                exit(1);
            }
        }
    } else {
        error_log("Token rotation failed - database operation unsuccessful");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database operation failed - no rows affected'
            ]);
        } else {
            echo "ERROR: No rows affected during token rotation";
            exit(1);
        }
    }

} catch (PDOException $e) {
    // Log error untuk debugging
    $error_msg = "Token rotation PDO error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
    error_log($error_msg);

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
    $error_msg = "Token rotation general error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    error_log($error_msg);

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