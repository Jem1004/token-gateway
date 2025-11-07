<?php
/**
 * Debug script untuk mengecek error di admin.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Admin.php Dependencies</h1>";

// Test 1: Check config.php
echo "<h2>1. Testing config.php</h2>";
if (file_exists('config.php')) {
    echo "✓ config.php exists<br>";
    require_once 'config.php';
    echo "✓ config.php loaded successfully<br>";
    
    // Test database connection
    try {
        $conn = getDbConnection();
        echo "✓ Database connection successful<br>";
        $conn->close();
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ config.php not found<br>";
}

// Test 2: Check rotate_token.php
echo "<h2>2. Testing rotate_token.php</h2>";
if (file_exists('rotate_token.php')) {
    echo "✓ rotate_token.php exists<br>";
    require_once 'rotate_token.php';
    echo "✓ rotate_token.php loaded successfully<br>";
    
    // Check if functions exist
    if (function_exists('rotateToken')) {
        echo "✓ rotateToken() function exists<br>";
    } else {
        echo "✗ rotateToken() function not found<br>";
    }
    
    if (function_exists('getTokenInfo')) {
        echo "✓ getTokenInfo() function exists<br>";
    } else {
        echo "✗ getTokenInfo() function not found<br>";
    }
} else {
    echo "✗ rotate_token.php not found<br>";
}

// Test 3: Check style.css
echo "<h2>3. Testing style.css</h2>";
if (file_exists('style.css')) {
    echo "✓ style.css exists<br>";
} else {
    echo "✗ style.css not found<br>";
}

// Test 4: Check token_countdown.js
echo "<h2>4. Testing token_countdown.js</h2>";
if (file_exists('token_countdown.js')) {
    echo "✓ token_countdown.js exists<br>";
} else {
    echo "✗ token_countdown.js not found<br>";
}

// Test 5: Check api_token_info.php
echo "<h2>5. Testing api_token_info.php</h2>";
if (file_exists('api_token_info.php')) {
    echo "✓ api_token_info.php exists<br>";
} else {
    echo "✗ api_token_info.php not found<br>";
}

// Test 6: Check session
echo "<h2>6. Testing Session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    if (session_start()) {
        echo "✓ Session started successfully<br>";
        echo "Session ID: " . session_id() . "<br>";
    } else {
        echo "✗ Failed to start session<br>";
    }
} else {
    echo "✓ Session already active<br>";
}

// Test 7: Check database table
echo "<h2>7. Testing Database Table</h2>";
try {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM active_token WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✓ active_token table accessible<br>";
        echo "Current token: " . htmlspecialchars($row['current_token']) . "<br>";
    } else {
        echo "✗ No token found in database<br>";
    }
    $conn->close();
} catch (Exception $e) {
    echo "✗ Database query failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "<p>If all tests pass, admin.php should work. Check the errors above to identify issues.</p>";
echo "<p><a href='admin.php'>Try accessing admin.php</a></p>";
?>
