<?php
/**
 * Token Gate Application - Token Validator
 * 
 * This script handles POST requests from the student portal to validate access tokens.
 * On successful validation, students are redirected to the exam URL via server-side redirect.
 * On failure, students are redirected back to the login page with an error parameter.
 */

// Include configuration file
require_once 'config.php';

// Check if request is POST and token parameter exists
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token'])) {
    // Redirect to index with error if not a valid POST request
    header("Location: index.php?error=1");
    exit();
}

// Sanitize input token using htmlspecialchars and trim
$submitted_token = htmlspecialchars(trim($_POST['token']), ENT_QUOTES, 'UTF-8');

// Validate that token is not empty after sanitization
if (empty($submitted_token)) {
    header("Location: index.php?error=1");
    exit();
}

// Additional validation: Check token format (8 alphanumeric uppercase characters)
// This prevents unnecessary database queries for obviously invalid tokens
if (!preg_match('/^[A-Z0-9]{8}$/', $submitted_token)) {
    header("Location: index.php?error=1");
    exit();
}

// Get database connection
try {
    $conn = getDbConnection();
} catch (Exception $e) {
    // Log error and redirect to index with error
    error_log("Database connection error in validate.php: " . $e->getMessage());
    header("Location: index.php?error=1");
    exit();
}

// Fetch current active token from database using prepared statement
$stmt = $conn->prepare("SELECT current_token FROM active_token WHERE id = 1");

if (!$stmt) {
    error_log("Prepare statement failed: " . $conn->error);
    $conn->close();
    header("Location: index.php?error=1");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

// Check if token exists in database
if ($result->num_rows === 0) {
    error_log("No active token found in database");
    $stmt->close();
    $conn->close();
    header("Location: index.php?error=1");
    exit();
}

// Fetch the current token
$row = $result->fetch_assoc();
$current_token = $row['current_token'];

// Close statement and connection
$stmt->close();
$conn->close();

// Compare submitted token with current token (case-sensitive)
if ($submitted_token === $current_token) {
    // Token is valid - redirect to exam URL using server-side redirect
    // SECURITY: Exam URL is never exposed to client-side code
    // The redirect happens entirely on the server using HTTP Location header
    header("Location: " . $exam_url);
    exit();
} else {
    // Token is invalid - redirect back to index with error parameter
    header("Location: index.php?error=1");
    exit();
}
