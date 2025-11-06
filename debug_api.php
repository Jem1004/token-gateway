<?php
/**
 * Debug API Test
 * Test script untuk debugging API endpoint issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Debug API Endpoint</h2>";

// Test 1: Check if config.php loads
echo "<h3>1. Testing Configuration...</h3>";
try {
    require_once 'config.php';
    echo "✅ config.php loaded<br>";
} catch (Exception $e) {
    echo "❌ config.php error: " . htmlspecialchars($e->getMessage()) . "<br>";
    exit;
}

// Test 2: Test database connection
echo "<h3>2. Testing Database Connection...</h3>";
try {
    $conn = getDbConnection();
    echo "✅ Database connected<br>";

    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'active_token'");
    if ($tableCheck->num_rows > 0) {
        echo "✅ active_token table exists<br>";
    } else {
        echo "❌ active_token table missing<br>";
    }

    // Check table structure
    $structure = $conn->query("DESCRIBE active_token");
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check current data
    $dataCheck = $conn->query("SELECT * FROM active_token WHERE id = 1");
    if ($dataCheck->num_rows > 0) {
        $row = $dataCheck->fetch_assoc();
        echo "<h4>Current Data:</h4>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p>No data found. Creating initial token...</p>";
        $conn->query("INSERT INTO active_token (id, current_token) VALUES (1, 'ABCXYZ') ON DUPLICATE KEY UPDATE current_token = current_token");
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 3: Test generateToken function
echo "<h3>3. Testing Token Generation...</h3>";
try {
    require_once 'rotate_token.php';

    echo "<strong>Generated Tokens:</strong><br>";
    for ($i = 0; $i < 3; $i++) {
        $token = generateToken(6);
        echo "Token " . ($i + 1) . ": <code style='background: #f0fdf4; padding: 2px; border: 1px solid #86efac; font-family: monospace; font-weight: bold;'>"
             . htmlspecialchars($token) . "</code><br>";
    }

    // Test with different lengths
    echo "<br><strong>Test Different Lengths:</strong><br>";
    echo "4 chars: <code>" . generateToken(4) . "</code><br>";
    echo "8 chars: <code>" . generateToken(8) . "</code><br>";
    echo "10 chars: <code>" . generateToken(10) . "</code><br>";

} catch (Exception $e) {
    echo "❌ Token generation error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 4: Test getTokenInfo function
echo "<h3>4. Testing getTokenInfo...</h3>";
try {
    require_once 'rotate_token.php';

    $tokenInfo = getTokenInfo();

    echo "<strong>Response:</strong><br>";
    echo "<pre style='background: #f9fafb; padding: 10px; border: 1px solid #d1d5db;'>";
    echo json_encode($tokenInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "</pre>";

    if ($tokenInfo['success']) {
        echo "✅ getTokenInfo works<br>";
    } else {
        echo "❌ getTokenInfo failed: " . htmlspecialchars($tokenInfo['message']) . "<br>";
    }

} catch (Exception $e) {
    echo "❌ getTokenInfo error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 5: Test rotateToken function
echo "<h3>5. Testing rotateToken...</h3>";
try {
    require_once 'rotate_token.php';

    $result = rotateToken('test', 'debug');

    echo "<strong>Response:</strong><br>";
    echo "<pre style='background: #f9fafb; padding: 10px; border: 1px solid #d1d5db;'>";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "</pre>";

    if ($result['success']) {
        echo "✅ rotateToken works<br>";
        echo "New token: <code style='background: #dcfce7; padding: 2px; border: 1px solid #86efac;'>" . htmlspecialchars($result['token']) . "</code><br>";
    } else {
        echo "❌ rotateToken failed: " . htmlspecialchars($result['message']) . "<br>";
    }

} catch (Exception $e) {
    echo "❌ rotateToken error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 6: Test API endpoint directly
echo "<h3>6. Testing API Endpoint...</h3>";
try {
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api_token_info.php';

    echo "Testing URL: <code>" . htmlspecialchars($apiUrl) . "</code><br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        echo "❌ CURL Error: " . htmlspecialchars($error) . "<br>";
    } else {
        echo "HTTP Status: <strong>" . $httpCode . "</strong><br>";
        echo "<strong>Response:</strong><br>";
        echo "<pre style='background: #f9fafb; padding: 10px; border: 1px solid #d1d5db; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";

        // Try to parse JSON
        $jsonData = json_decode($response, true);
        if ($jsonData !== null) {
            echo "✅ Valid JSON response<br>";
            if (isset($jsonData['success']) && $jsonData['success']) {
                echo "✅ API successful<br>";
                echo "Token: <code>" . htmlspecialchars($jsonData['current_token'] ?? 'N/A') . "</code><br>";
                echo "Time Until: " . ($jsonData['time_until_rotation'] ?? 'N/A') . " seconds<br>";
            } else {
                echo "❌ API returned error: " . htmlspecialchars($jsonData['message'] ?? 'Unknown') . "<br>";
            }
        } else {
            echo "❌ Invalid JSON response<br>";
        }
    }

} catch (Exception $e) {
    echo "❌ API test error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";
echo "<small>Debug completed at: " . date('Y-m-d H:i:s') . "</small>";
?>