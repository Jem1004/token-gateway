<?php
/**
 * Test Token Generation and Database Connection
 * This script tests if the token system works correctly
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Token System Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection...</h3>";
try {
    require_once 'config.php';
    $conn = getDbConnection();
    echo "✅ Database connection successful<br>";

    // Test 2: Check if active_token table exists and has data
    echo "<h3>2. Checking active_token table...</h3>";
    $result = $conn->query("SELECT * FROM active_token WHERE id = 1");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Table exists and has data<br>";
        echo "Current token: <strong>" . htmlspecialchars($row['current_token']) . "</strong><br>";
        echo "Updated: " . htmlspecialchars($row['updated_at']) . "<br>";

        // Check if token is 6 characters
        if (strlen($row['current_token']) === 6 && ctype_upper($row['current_token']) && ctype_alpha($row['current_token'])) {
            echo "✅ Token format is correct (6 uppercase letters)<br>";
        } else {
            echo "⚠️ Token format needs update (current: " . strlen($row['current_token']) . " chars)<br>";
        }
    } else {
        echo "❌ No data found in active_token table<br>";
    }

    $conn->close();

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 3: Token Generation Function
echo "<h3>3. Testing Token Generation Function...</h3>";
try {
    require_once 'rotate_token.php';

    // Test multiple token generations
    for ($i = 0; $i < 5; $i++) {
        $token = generateToken(6);
        echo "Generated token " . ($i + 1) . ": <strong>" . htmlspecialchars($token) . "</strong> ";

        // Validate token format
        if (strlen($token) === 6 && ctype_upper($token) && ctype_alpha($token)) {
            echo "✅<br>";
        } else {
            echo "❌ (Invalid format)<br>";
        }
    }

} catch (Exception $e) {
    echo "❌ Error generating token: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 4: Check for required files
echo "<h3>4. Checking Required Files...</h3>";
$requiredFiles = [
    'config.php',
    'rotate_token.php',
    'validate.php',
    'index.php',
    'admin.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 5: Check timer columns
echo "<h3>5. Checking Timer Feature Support...</h3>";
try {
    require_once 'config.php';
    $conn = getDbConnection();

    $checkColumns = $conn->query("SHOW COLUMNS FROM active_token LIKE 'token_rotation_interval'");
    $hasTimerColumns = $checkColumns->num_rows > 0;

    if ($hasTimerColumns) {
        echo "✅ Timer columns exist (database migrated)<br>";

        // Check timer table
        $checkHistory = $conn->query("SHOW TABLES LIKE 'token_history'");
        if ($checkHistory->num_rows > 0) {
            echo "✅ Token history table exists<br>";
        } else {
            echo "⚠️ Token history table missing<br>";
        }
    } else {
        echo "⚠️ Timer columns not found (database not migrated)<br>";
        echo "💡 Run migration: <code>mysql -u root -p token_gate_db < migration_add_token_timer.sql</code><br>";
        echo "📅 Default token rotation: <strong>15 minutes</strong><br>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Error checking timer support: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<h3>6. Recommendations</h3>";
echo "<ul>";
echo "<li>🔧 Run database migration: <code>mysql -u root -p token_gate_db < migration_add_token_timer.sql</code></li>";
echo "<li>🔄 Test token rotation from admin panel</li>";
echo "<li>🧪 Test token validation from student portal</li>";
echo "</ul>";

echo "<hr>";
echo "<small>Test completed at: " . date('Y-m-d H:i:s') . "</small>";
?>