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
 * Generates a new token and updates it in the database.
 * 
 * @return array Status array with 'success' boolean and 'message' string
 */
function rotateToken(): array {
    try {
        // Get database connection
        $conn = getDbConnection();
        
        // Generate new token
        $newToken = generateToken(8);
        
        // Prepare UPDATE statement using prepared statement to prevent SQL injection
        $stmt = $conn->prepare("UPDATE active_token SET current_token = ? WHERE id = 1");
        
        if (!$stmt) {
            // Don't expose database error details
            error_log("Failed to prepare statement in rotate_token.php");
            throw new Exception("Failed to prepare statement");
        }
        
        // Bind parameter
        $stmt->bind_param("s", $newToken);
        
        // Execute the update
        if (!$stmt->execute()) {
            // Don't expose database error details
            error_log("Failed to execute update in rotate_token.php");
            throw new Exception("Failed to execute update");
        }
        
        // Check if any row was affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated. Database may not be initialized.");
        }
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
        
        return [
            'success' => true,
            'message' => "Token rotated successfully. New token: $newToken",
            'token' => $newToken
        ];
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Token rotation error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => "Token rotation failed: " . $e->getMessage(),
            'token' => null
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
