<?php
/**
 * Test file untuk debugging rotate_token.php
 */

require_once 'config.php';

echo "<h1>Test Rotate Token Function</h1>";

// Test database connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $result = $stmt->fetch();

    if ($result) {
        echo "<p style='color: green;'>✅ Database connected successfully</p>";
        echo "<p>Current token: <strong>" . htmlspecialchars($result['current_token']) . "</strong></p>";
        echo "<p>Last rotated: <strong>" . htmlspecialchars($result['last_rotated']) . "</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ No data found in app_config table</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test token generation
echo "<h2>2. Testing Token Generation</h2>";

function generateRandomToken($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

$test_token = generateRandomToken(TOKEN_LENGTH);
echo "<p>Generated token: <strong>" . htmlspecialchars($test_token) . "</strong></p>";

// Test token rotation
echo "<h2>3. Testing Token Rotation</h2>";

try {
    $new_token = generateRandomToken(TOKEN_LENGTH);
    echo "<p>New token to insert: <strong>" . htmlspecialchars($new_token) . "</strong></p>";

    $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
    $result = $stmt->execute([':token' => $new_token]);

    if ($result) {
        echo "<p style='color: green;'>✅ Token rotation successful!</p>";

        // Verify the update
        $stmt = $pdo->query("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
        $updated = $stmt->fetch();

        echo "<p>Updated token: <strong>" . htmlspecialchars($updated['current_token']) . "</strong></p>";
        echo "<p>Updated time: <strong>" . htmlspecialchars($updated['last_rotated']) . "</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Token rotation failed - no rows affected</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Token rotation error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>SQL: UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1</p>";
    echo "<p>Token value: " . htmlspecialchars($new_token) . "</p>";
}

echo "<h2>4. Testing Original rotate_token.php</h2>";

// Test actual rotate_token.php by including it
echo "<p>Running original rotate_token.php...</p>";

// Capture output
ob_start();
include 'rotate_token.php';
$output = ob_get_clean();

echo "<p>Output from rotate_token.php: <code>" . htmlspecialchars($output) . "</code></p>";

// Check if token changed after running rotate_token.php
$stmt = $pdo->query("SELECT current_token FROM app_config WHERE id = 1");
$final_token = $stmt->fetchColumn();

echo "<p>Final token after rotate_token.php: <strong>" . htmlspecialchars($final_token) . "</strong></p>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 5px 0; }
    h1, h2 { color: #333; }
    code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>";

echo "<p><a href='admin.php'>← Back to Admin Panel</a></p>";
?>