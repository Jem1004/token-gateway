<?php
/**
 * Token Gate Application - Token Rotation Script
 * 
 * This script generates a new 8-character alphanumeric uppercase token
 * and updates the database. Can be executed via:
 * - Cron job for automated rotation
 * - Manual trigger from admin panel
 * - Direct CLI execution: php rotate_token.php
 */

// Include configuration file
require_once 'config.php';

/**
 * Generate Random Token
 * 
 * Generates a cryptographically secure random token consisting of
 * uppercase letters (A-Z) and numbers (0-9).
 * 
 * @param int $length Length of the token (default: 8)
 * @return string Generated token
 */
function generateToken($length = 8): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $token = '';
    
    // Use random_int for cryptographically secure random generation
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $token;
}

/**
 * Rotate Token
 *
 * Generates a new token and updates it in the database with timing information.
 *
 * @param string $rotationType 'manual' or 'auto'
 * @param string $rotatedBy Username who initiated rotation (for manual rotations)
 * @return array Status array with 'success' boolean and 'message' string
 */
function rotateToken($rotationType = 'manual', $rotatedBy = 'system'): array {
    try {
        // Get database connection
        $conn = getDbConnection();

        // Begin transaction
        $conn->begin_transaction();

        // Get current token for history
        $stmt = $conn->prepare("SELECT current_token, token_rotation_interval FROM active_token WHERE id = 1");
        if (!$stmt) {
            throw new Exception("Failed to prepare select statement");
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No token record found");
        }

        $currentData = $result->fetch_assoc();
        $oldToken = $currentData['current_token'];
        $rotationInterval = $currentData['token_rotation_interval'] ?? 60;
        $stmt->close();

        // Generate new token
        $newToken = generateToken(8);
        $now = new DateTime();
        $nextRotationTime = clone $now;
        $nextRotationTime->add(new DateInterval("PT{$rotationInterval}M"));

        // Update active token with timer information
        $stmt = $conn->prepare("
            UPDATE active_token SET
                current_token = ?,
                last_rotation_time = NOW(),
                next_rotation_time = ?,
                updated_at = NOW()
            WHERE id = 1
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare update statement");
        }

        $nextRotationStr = $nextRotationTime->format('Y-m-d H:i:s');
        $stmt->bind_param("ss", $newToken, $nextRotationStr);

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute update");
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated");
        }

        $stmt->close();

        // Add to history table
        $stmt = $conn->prepare("
            INSERT INTO token_history
            (old_token, new_token, rotation_type, rotated_by, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare history insert");
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $stmt->bind_param("sssss", $oldToken, $newToken, $rotationType, $rotatedBy, $ipAddress);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert history record");
        }

        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Log security event
        if (file_exists(__DIR__ . '/log_security.php')) {
            require_once __DIR__ . '/log_security.php';
            logSecurityEvent('token_rotated', [
                'rotation_type' => $rotationType,
                'rotated_by' => $rotatedBy,
                'old_token' => $oldToken,
                'new_token' => $newToken,
                'next_rotation' => $nextRotationStr
            ]);
        }

        $conn->close();

        return [
            'success' => true,
            'message' => "Token rotated successfully. New token: $newToken",
            'token' => $newToken,
            'next_rotation' => $nextRotationStr,
            'rotation_interval' => $rotationInterval,
            'rotation_type' => $rotationType
        ];

    } catch (Exception $e) {
        // Rollback transaction if started
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
            $conn->close();
        }

        // Log error for debugging
        error_log("Token rotation error: " . $e->getMessage());

        return [
            'success' => false,
            'message' => "Token rotation failed: " . $e->getMessage(),
            'token' => null
        ];
    }
}

/**
 * Get Token Information
 *
 * Retrieves current token and timing information.
 *
 * @return array Token information
 */
function getTokenInfo(): array {
    try {
        $conn = getDbConnection();

        $stmt = $conn->prepare("
            SELECT
                current_token,
                token_expiry_minutes,
                token_rotation_interval,
                auto_rotation_enabled,
                last_rotation_time,
                next_rotation_time,
                updated_at
            FROM active_token
            WHERE id = 1
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No token record found");
        }

        $data = $result->fetch_assoc();
        $stmt->close();

        // Calculate time until next rotation
        $now = new DateTime();
        $nextRotation = new DateTime($data['next_rotation_time']);
        $timeUntilRotation = $now < $nextRotation ? $nextRotation->getTimestamp() - $now->getTimestamp() : 0;

        $conn->close();

        return [
            'success' => true,
            'current_token' => $data['current_token'],
            'expiry_minutes' => (int)$data['token_expiry_minutes'],
            'rotation_interval' => (int)$data['token_rotation_interval'],
            'auto_rotation_enabled' => (bool)$data['auto_rotation_enabled'],
            'last_rotation_time' => $data['last_rotation_time'],
            'next_rotation_time' => $data['next_rotation_time'],
            'time_until_rotation' => $timeUntilRotation,
            'updated_at' => $data['updated_at']
        ];

    } catch (Exception $e) {
        error_log("Get token info error: " . $e->getMessage());

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Execute token rotation when script is run directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    // CLI execution
    $result = rotateToken();
    echo $result['message'] . "\n";
    exit($result['success'] ? 0 : 1);
} else {
    // Web execution (called from admin panel)
    $result = rotateToken();
    
    // Output JSON for AJAX requests or plain text for direct access
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        echo $result['message'];
    }
}
