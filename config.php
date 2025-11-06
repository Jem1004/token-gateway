<?php
/**
 * Token Gate Application - Configuration File
 * 
 * This file contains database connection credentials and exam URL configuration.
 * For production deployment, consider moving this file outside the web root
 * and using environment variables for sensitive data.
 */

// Database Configuration
$db_host = 'localhost';
$db_name = 'token_gate_db';
$db_user = 'root';
$db_pass = '';

// Exam URL Configuration
// This is the actual exam URL that students will be redirected to after successful token validation
$exam_url = 'http://www.xxxxx.sch.id';

/**
 * Get Database Connection
 * 
 * Creates and returns a MySQLi connection with proper error handling and UTF-8 charset.
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDbConnection(): mysqli {
    global $db_host, $db_name, $db_user, $db_pass;
    
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error for debugging (in production, log to file instead of displaying)
        // Do NOT include specific error details in the log that could expose sensitive information
        error_log("Database connection failed: Connection error occurred");
        // Generic error message to prevent information disclosure
        die("Connection failed. Please try again later.");
    }
    
    // Set charset to UTF-8 to prevent encoding issues
    if (!$conn->set_charset("utf8mb4")) {
        // Log generic error without exposing database details
        error_log("Error setting charset utf8mb4");
        die("Database configuration error.");
    }
    
    return $conn;
}
