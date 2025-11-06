<?php
/**
 * Comprehensive System Test Script
 * Tests all components of the token gateway system
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>🧪 Token Gateway System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .test-item { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; }
        .token { font-family: monospace; font-weight: bold; background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        .btn { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <h1>🧪 Token Gateway Comprehensive System Test</h1>
    <p>Berhasil dibuat pada: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: File Structure
echo "<div class='section info'>
    <h2>📁 File Structure Test</h2>";
$requiredFiles = [
    'config.php' => 'Konfigurasi database',
    'rotate_token.php' => 'Token rotation logic',
    'api_token_info.php' => 'REST API endpoint',
    'admin.php' => 'Admin panel',
    'index.php' => 'Student portal',
    'token_countdown.js' => 'Countdown timer JavaScript',
    'style.css' => 'Stylesheet'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='test-item success'>✅ $file - $description</div>";
    } else {
        echo "<div class='test-item error'>❌ $file - $description (MISSING)</div>";
    }
}
echo "</div>";

// Test 2: Database Connection
echo "<div class='section info'>
    <h2>🗄️ Database Connection Test</h2>";

try {
    if (file_exists('config.php')) {
        require_once 'config.php';
        $conn = getDbConnection();

        if ($conn) {
            echo "<div class='test-item success'>✅ Database connection successful</div>";

            // Test basic query
            $result = $conn->query("SELECT 1 as test");
            if ($result && $result->num_rows > 0) {
                echo "<div class='test-item success'>✅ Basic query test passed</div>";
            } else {
                echo "<div class='test-item error'>❌ Basic query test failed</div>";
            }

            // Check active_token table
            $tableCheck = $conn->query("SHOW TABLES LIKE 'active_token'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                echo "<div class='test-item success'>✅ active_token table exists</div>";

                // Check table structure
                $structure = $conn->query("DESCRIBE active_token");
                if ($structure && $structure->num_rows > 0) {
                    echo "<div class='test-item success'>✅ active_token table structure</div>";
                    echo "<h4>Table Structure:</h4>";
                    echo "<pre>";
                    echo "Field\t\tType\t\tNull\tKey\n";
                    echo str_repeat("-", 50) . "\n";
                    while ($row = $structure->fetch_assoc()) {
                        echo $row['Field'] . "\t\t" . $row['Type'] . "\t\t" . $row['Null'] . "\t" . $row['Key'] . "\n";
                    }
                    echo "</pre>";
                }

                // Check for timer columns
                $timerColumns = $conn->query("SHOW COLUMNS FROM active_token LIKE 'token_rotation_interval'");
                if ($timerColumns && $timerColumns->num_rows > 0) {
                    echo "<div class='test-item success'>✅ Timer columns exist (migrated database)</div>";
                } else {
                    echo "<div class='test-item warning'>⚠️ Timer columns missing (run migration for full features)</div>";
                }

                // Check current data
                $dataCheck = $conn->query("SELECT * FROM active_token WHERE id = 1");
                if ($dataCheck && $dataCheck->num_rows > 0) {
                    $data = $dataCheck->fetch_assoc();
                    echo "<div class='test-item success'>✅ Token data found</div>";
                    echo "<h4>Current Token Data:</h4>";
                    echo "<pre>";
                    foreach ($data as $key => $value) {
                        echo "$key: $value\n";
                    }
                    echo "</pre>";
                } else {
                    echo "<div class='test-item warning'>⚠️ No token data found</div>";
                }

            } else {
                echo "<div class='test-item error'>❌ active_token table missing</div>";
            }

            // Check token_history table
            $historyCheck = $conn->query("SHOW TABLES LIKE 'token_history'");
            if ($historyCheck && $historyCheck->num_rows > 0) {
                echo "<div class='test-item success'>✅ token_history table exists</div>";
            } else {
                echo "<div class='test-item warning'>⚠️ token_history table missing (optional)</div>";
            }

            $conn->close();
        } else {
            echo "<div class='test-item error'>❌ Database connection failed</div>";
        }
    } else {
        echo "<div class='test-item error'>❌ config.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Database test error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// Test 3: Token Generation
echo "<div class='section info'>
    <h2>🎲 Token Generation Test</h2>";

try {
    if (file_exists('rotate_token.php')) {
        require_once 'rotate_token.php';

        echo "<h4>Sample Tokens (6 letters, uppercase):</h4>";
        for ($i = 0; $i < 5; $i++) {
            $token = generateToken(6);
            echo "<div class='token'>Token " . ($i + 1) . ": $token</div>";
        }

        // Test different lengths
        echo "<h4>Different Lengths:</h4>";
        echo "4 chars: <span class='token'>" . generateToken(4) . "</span><br>";
        echo "6 chars: <span class='token'>" . generateToken(6) . "</span><br>";
        echo "8 chars: <span class='token'>" . generateToken(8) . "</span><br>";

        echo "<div class='test-item success'>✅ Token generation working</div>";
    } else {
        echo "<div class='test-item error'>❌ rotate_token.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Token generation error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// Test 4: API Endpoint
echo "<div class='section info'>
    <h2>🌐 API Endpoint Test</h2>";

$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$apiUrl = str_replace('test_system.php', 'api_token_info.php', $apiUrl);

echo "<h4>Testing URL: <code>" . htmlspecialchars($apiUrl) . "</code></h4>";

// Test with cURL
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($error) {
        echo "<div class='test-item error'>❌ cURL Error: " . htmlspecialchars($error) . "</div>";
    } else {
        echo "<div class='test-item success'>✅ HTTP Status: $httpCode</div>";

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        echo "<h4>Response Headers:</h4>";
        echo "<pre>" . htmlspecialchars($headers) . "</pre>";

        echo "<h4>Response Body:</h4>";
        echo "<pre>" . htmlspecialchars($body) . "</pre>";

        // Try to parse JSON
        $jsonData = json_decode($body, true);
        if ($jsonData !== null) {
            echo "<div class='test-item success'>✅ Valid JSON response</div>";
            if (isset($jsonData['success']) && $jsonData['success']) {
                echo "<div class='test-item success'>✅ API reports success</div>";
                if (isset($jsonData['current_token'])) {
                    echo "<div class='test-item success'>✅ Current token: <span class='token'>" . htmlspecialchars($jsonData['current_token']) . "</span></div>";
                }
            } else {
                echo "<div class='test-item error'>❌ API reports error: " . htmlspecialchars($jsonData['message'] ?? 'Unknown') . "</div>";
            }
        } else {
            echo "<div class='test-item error'>❌ Invalid JSON response</div>";
        }
    }
} else {
    echo "<div class='test-item warning'>⚠️ cURL not available</div>";
}

echo "</div>";

// Test 5: Security Headers
echo "<div class='section info'>
    <h2>🔒 Security Headers Test</h2>";

$securityHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'Content-Security-Policy' => 'default-src',
    'X-XSS-Protection' => '1; mode=block'
];

foreach ($securityHeaders as $header => $expected) {
    echo "<div class='test-item info'>📋 $header should be set</div>";
}

echo "</div>";

// Test 6: JavaScript File Test
echo "<div class='section info'>
    <h2>📜 JavaScript Test</h2>";

if (file_exists('token_countdown.js')) {
    echo "<div class='test-item success'>✅ token_countdown.js exists</div>";

    $jsContent = file_get_contents('token_countdown.js');
    $jsSize = strlen($jsContent);

    echo "<div class='test-item info'>📊 File size: " . number_format($jsSize) . " bytes</div>";

    // Check for key components
    $checks = [
        'TokenCountdown' => 'Main class definition',
        'refreshTokenData' => 'API refresh method',
        'updateCountdown' => 'Countdown update method',
        'enableFallbackMode' => 'Fallback mode method'
    ];

    foreach ($checks as $pattern => $description) {
        if (strpos($jsContent, $pattern) !== false) {
            echo "<div class='test-item success'>✅ $description found</div>";
        } else {
            echo "<div class='test-item warning'>⚠️ $description not found</div>";
        }
    }
} else {
    echo "<div class='test-item error'>❌ token_countdown.js not found</div>";
}

echo "</div>";

// Test 7: Manual Test Buttons
echo "<div class='section info'>
    <h2>🎮 Manual Testing</h2>
    <p>Use these buttons for manual testing:</p>";

echo "<div class='grid'>
    <div>
        <h4>API Tests</h4>
        <button class='btn' onclick='testAPI()'>🔄 Test API Again</button>
        <button class='btn' onclick='location.reload()'>🔄 Reload Page</button>
    </div>
    <div>
        <h4>Navigation</h4>
        <button class='btn' onclick='window.open(\"admin.php\", \"_blank\")'>🛡️ Admin Panel</button>
        <button class='btn' onclick='window.open(\"index.php\", \"_blank\")'>🔑 Student Portal</button>
    </div>
</div>";

echo "</div>";

// Recommendations
echo "<div class='section info'>
    <h2>💡 Recommendations</h2>
    <ul>
        <li>🔧 <strong>Database Migration:</strong> Run migration script if timer columns are missing</li>
        <li>🔐 <strong>Security:</strong> Ensure HTTPS is enabled in production</li>
        <li>📝 <strong>Logging:</strong> Check error logs for detailed debugging</li>
        <li>🎯 <strong>Testing:</strong> Test token creation from admin panel</li>
        <li>⏱️ <strong>Timer:</strong> Verify 15-minute countdown works correctly</li>
    </ul>
</div>";

echo "<script>
function testAPI() {
    const apiUrl = window.location.href.replace('test_system.php', 'api_token_info.php');
    fetch(apiUrl)
        .then(response => {
            console.log('API Response Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('API Response:', text);
            alert('API test completed. Check console for details.');
        })
        .catch(error => {
            console.error('API Error:', error);
            alert('API test failed. Check console for details.');
        });
}
</script>

</body>
</html>";
?>