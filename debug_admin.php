<?php
// Debug script untuk admin.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Admin Panel<br>";

// Test 1: Check PHP version and basic functions
echo "1. PHP Version: " . phpversion() . "<br>";

// Test 2: Check session
echo "2. Session Status: " . session_status() . "<br>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Starting session...<br>";
    session_start();
    echo "Session started. Status: " . session_status() . "<br>";
}

// Test 3: Check config.php
echo "3. Checking config.php...<br>";
if (file_exists('config.php')) {
    echo "config.php exists<br>";
    try {
        require_once 'config.php';
        echo "config.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "Error loading config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "config.php NOT found<br>";
}

// Test 4: Check database connection function
echo "4. Checking getDbConnection function...<br>";
if (function_exists('getDbConnection')) {
    echo "getDbConnection function exists<br>";
    try {
        $conn = getDbConnection();
        if ($conn) {
            echo "Database connection successful<br>";
            $conn->close();
        } else {
            echo "Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "Database connection error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "getDbConnection function NOT found<br>";
}

// Test 5: Check rotate_token.php
echo "5. Checking rotate_token.php...<br>";
if (file_exists('rotate_token.php')) {
    echo "rotate_token.php exists<br>";
    try {
        require_once 'rotate_token.php';
        echo "rotate_token.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "Error loading rotate_token.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "rotate_token.php NOT found<br>";
}

// Test 6: Check rotateToken function
echo "6. Checking rotateToken function...<br>";
if (function_exists('rotateToken')) {
    echo "rotateToken function exists<br>";
} else {
    echo "rotateToken function NOT found<br>";
}

echo "<br>Debug complete. Check individual test results above.<br>";
?>