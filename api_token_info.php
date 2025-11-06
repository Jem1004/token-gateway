<?php
/**
 * Token Information API
 *
 * REST API endpoint for retrieving token information
 * Used by countdown timer and other client-side features
 */

// Include required files
require_once 'config.php';
require_once 'rotate_token.php';

// Set JSON response header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get token information
try {
    // Check if database connection works
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $tokenInfo = getTokenInfo();

    if ($tokenInfo['success']) {
        // Format dates for better readability
        if ($tokenInfo['last_rotation_time']) {
            $tokenInfo['last_rotation_time_formatted'] = date('d M Y H:i:s', strtotime($tokenInfo['last_rotation_time']));
        }
        if ($tokenInfo['next_rotation_time']) {
            $tokenInfo['next_rotation_time_formatted'] = date('d M Y H:i:s', strtotime($tokenInfo['next_rotation_time']));
        }

        // Add server timestamp
        $tokenInfo['server_timestamp'] = date('Y-m-d H:i:s');
        $tokenInfo['server_timezone'] = date_default_timezone_get();

        echo json_encode($tokenInfo);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $tokenInfo['message'] ?? 'Unknown error'
        ]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>