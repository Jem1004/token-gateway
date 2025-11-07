<?php
// Test 1: Basic PHP functionality
echo "Test 1: Basic PHP - OK<br>";

// Test 2: Session functionality
echo "Test 2: Starting session... ";
if (session_start()) {
    echo "OK<br>";
} else {
    echo "FAILED<br>";
}

// Test 3: Simple database test
echo "Test 3: Database test... ";
try {
    // Direct database connection without config.php
    $conn = new mysqli('localhost', 'root', 'wCvTiLwLrN6QefFvKHghIivSyma', 'token_gate_db');
    if ($conn->connect_error) {
        echo "DB CONNECTION FAILED: " . $conn->connect_error . "<br>";
    } else {
        echo "OK<br>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "<br>";
}

// Test 4: File inclusion
echo "Test 4: Including config.php... ";
if (file_exists('config.php')) {
    try {
        include 'config.php';
        echo "OK<br>";
    } catch (ParseError $e) {
        echo "PARSE ERROR: " . $e->getMessage() . "<br>";
    } catch (Error $e) {
        echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "<br>";
    }
} else {
    echo "FILE NOT FOUND<br>";
}

// Test 5: Check if we can access getDbConnection
echo "Test 5: getDbConnection function... ";
if (function_exists('getDbConnection')) {
    echo "OK<br>";
} else {
    echo "NOT AVAILABLE<br>";
}

echo "All tests completed.<br>";
?>