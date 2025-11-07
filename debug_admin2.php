<?php
// Debug Admin Panel 2 - Test after fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Admin Panel v2<br>";

// Test 1: Session
echo "1. Session Test<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session OK<br>";

// Test 2: Config
echo "2. Config Test<br>";
require_once 'config.php';
echo "Config OK<br>";

// Test 3: rotate_token.php inclusion
echo "3. rotate_token.php inclusion test<br>";
if (file_exists('rotate_token.php')) {
    echo "File exists<br>";
    try {
        require_once 'rotate_token.php';
        echo "rotate_token.php included successfully<br>";

        if (function_exists('rotateToken')) {
            echo "rotateToken function available<br>";
        } else {
            echo "rotateToken function NOT available<br>";
        }

        if (function_exists('getTokenInfo')) {
            echo "getTokenInfo function available<br>";
        } else {
            echo "getTokenInfo function NOT available<br>";
        }

    } catch (Exception $e) {
        echo "Error including rotate_token.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "rotate_token.php NOT found<br>";
}

// Test 4: Database
echo "4. Database Test<br>";
try {
    $conn = getDbConnection();
    if ($conn) {
        echo "Database connection OK<br>";
        $conn->close();
    } else {
        echo "Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<br>Debug complete. If all tests pass, admin.php should work now.<br>";
?>