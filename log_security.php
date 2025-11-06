<?php
/**
 * Security Logging System
 *
 * This script logs security-related events for monitoring and analysis.
 * It helps track suspicious activities and maintain system integrity.
 */

// Prevent direct access
if (!defined('SECURITY_LOG')) {
    // Allow only POST requests with valid JSON
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(403);
        exit('Access denied');
    }

    // Get JSON data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Validate required fields
    if (!$data || !isset($data['type']) || !isset($data['timestamp'])) {
        http_response_code(400);
        exit('Invalid data');
    }

    // Sanitize input
    $event_type = htmlspecialchars($data['type'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
    $user_agent = htmlspecialchars($data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $timestamp = $data['timestamp'] ?? date('c');
    $session_id = htmlspecialchars($data['session_id'] ?? session_id() ?? 'Unknown', ENT_QUOTES, 'UTF-8');

    // Create log entry
    $log_entry = [
        'timestamp' => $timestamp,
        'ip_address' => $ip_address,
        'event_type' => $event_type,
        'user_agent' => $user_agent,
        'session_id' => $session_id,
        'server_data' => [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'Unknown',
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
        ]
    ];

    // Log to file
    $log_file = __DIR__ . '/security_logs.json';
    $logs = [];

    // Read existing logs
    if (file_exists($log_file)) {
        $json_content = file_get_contents($log_file);
        if ($json_content !== false) {
            $logs = json_decode($json_content, true) ?: [];
        }
    }

    // Add new entry
    $logs[] = $log_entry;

    // Keep only last 1000 entries to prevent file from growing too large
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }

    // Write logs back to file
    $result = file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Set appropriate headers
    header('Content-Type: application/json');

    if ($result !== false) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Security event logged']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to log event']);
    }
    exit;
}

/**
 * Function to manually log security events
 */
function logSecurityEvent($event_type, $details = []) {
    $log_entry = [
        'timestamp' => date('c'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'event_type' => htmlspecialchars($event_type, ENT_QUOTES, 'UTF-8'),
        'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
        'session_id' => session_id() ?? 'Unknown',
        'details' => $details
    ];

    $log_file = __DIR__ . '/security_logs.json';
    $logs = [];

    // Read existing logs
    if (file_exists($log_file)) {
        $json_content = file_get_contents($log_file);
        if ($json_content !== false) {
            $logs = json_decode($json_content, true) ?: [];
        }
    }

    // Add new entry
    $logs[] = $log_entry;

    // Keep only last 1000 entries
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }

    // Write logs back to file
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Function to get recent security events
 */
function getSecurityEvents($limit = 50) {
    $log_file = __DIR__ . '/security_logs.json';

    if (!file_exists($log_file)) {
        return [];
    }

    $json_content = file_get_contents($log_file);
    if ($json_content === false) {
        return [];
    }

    $logs = json_decode($json_content, true) ?: [];

    // Return most recent events first
    return array_slice(array_reverse($logs), 0, $limit);
}

/**
 * Function to check for suspicious activity patterns
 */
function checkSuspiciousActivity($ip_address, $time_window_minutes = 10) {
    $log_file = __DIR__ . '/security_logs.json';

    if (!file_exists($log_file)) {
        return false;
    }

    $json_content = file_get_contents($log_file);
    if ($json_content === false) {
        return false;
    }

    $logs = json_decode($json_content, true) ?: [];
    $time_threshold = time() - ($time_window_minutes * 60);
    $suspicious_count = 0;

    foreach ($logs as $log) {
        $log_time = strtotime($log['timestamp'] ?? '');

        // Check if event is within time window and from same IP
        if ($log_time > $time_threshold && ($log['ip_address'] ?? '') === $ip_address) {
            $suspicious_count++;

            // If more than 5 security events in 10 minutes, flag as suspicious
            if ($suspicious_count >= 5) {
                return true;
            }
        }
    }

    return false;
}

// Define constant to allow inclusion
define('SECURITY_LOG', true);
?>