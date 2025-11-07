<?php
/**
 * Test script for admin-rahasia.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Admin Rahasia</h1>";

// Test 1: File existence
echo "<h2>1. File Existence Test</h2>";
if (file_exists('admin-rahasia.php')) {
    echo "✅ admin-rahasia.php exists<br>";
} else {
    echo "❌ admin-rahasia.php NOT found<br>";
}

// Test 2: Basic PHP syntax
echo "<h2>2. PHP Syntax Test</h2>";
$output = shell_exec('php -l admin-rahasia.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ PHP syntax OK<br>";
} else {
    echo "❌ PHP syntax error: " . htmlspecialchars($output) . "<br>";
}

// Test 3: Session functionality
echo "<h2>3. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "✅ Session started<br>";

// Test 4: Config loading
echo "<h2>4. Config Test</h2>";
try {
    require_once 'config.php';
    echo "✅ Config loaded<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 5: Database connection
echo "<h2>5. Database Connection Test</h2>";
if (function_exists('getDbConnection')) {
    try {
        $conn = getDbConnection();
        if ($conn) {
            echo "✅ Database connected<br>";
            $conn->close();
        } else {
            echo "❌ Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getDbConnection function not available<br>";
}

// Test 6: Constants check
echo "<h2>6. Constants Test</h2>";
echo "SECRET_ADMIN_USERNAME: " . (defined('SECRET_ADMIN_USERNAME') ? SECRET_ADMIN_USERNAME : 'Not defined') . "<br>";
echo "SECRET_ADMIN_PASSWORD: " . (defined('SECRET_ADMIN_PASSWORD') ? 'Set' : 'Not defined') . "<br>";

echo "<h2>Test Complete</h2>";
echo "<p><a href='admin-rahasia.php' target='_blank'>🔐 Access Secret Admin Panel</a></p>";
echo "<p><strong>Login Credentials:</strong><br>";
echo "Username: admin<br>";
echo "Password: indonesia2025</p>";
?>