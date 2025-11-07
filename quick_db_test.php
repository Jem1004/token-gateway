<?php
/**
 * Quick Database Test - Simple test to verify database operations
 */

require_once 'config.php';

echo "<h1>⚡ Quick Database Test</h1>";

try {
    // Test 1: Connection
    echo "<h2>1. Database Connection</h2>";
    $test_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connected successfully</p>";

    // Test 2: Current data
    echo "<h2>2. Current Token Data</h2>";
    $stmt = $test_pdo->prepare("SELECT id, current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><td>" . $current['id'] . "</td></tr>";
        echo "<tr><th>Current Token</th><td style='color: blue; font-weight: bold;'>" . htmlspecialchars($current['current_token']) . "</td></tr>";
        echo "<tr><th>Last Rotated</th><td>" . $current['last_rotated'] . "</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No data found. Will insert initial data.</p>";
    }

    // Test 3: Generate new token
    echo "<h2>3. Generate New Token</h2>";
    function generateRandomToken($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    $new_token = generateRandomToken(6);
    echo "<p>New token generated: <strong style='color: green;'>" . $new_token . "</strong></p>";

    // Test 4: Update database
    echo "<h2>4. Update Database</h2>";

    if ($current) {
        // Update existing record
        $update_stmt = $test_pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
        $result = $update_stmt->execute([':token' => $new_token]);
        $affected = $update_stmt->rowCount();

        echo "<p>Update executed: " . ($result ? '✅ Success' : '❌ Failed') . "</p>";
        echo "<p>Affected rows: " . $affected . "</p>";

        if ($affected === 0) {
            echo "<p style='color: orange;'>⚠️ No rows affected. This might indicate:</p>";
            echo "<ul>";
            echo "<li>The token was already the same value</li>";
            echo "<li>No record with id = 1 exists</li>";
            echo "<li>Permission issues</li>";
            echo "</ul>";
        }
    } else {
        // Insert new record
        echo "<p>No existing record, inserting new data...</p>";
        $insert_stmt = $test_pdo->prepare("INSERT INTO app_config (id, current_token, last_rotated) VALUES (1, :token, CURRENT_TIMESTAMP)");
        $result = $insert_stmt->execute([':token' => $new_token]);
        echo "<p>Insert executed: " . ($result ? '✅ Success' : '❌ Failed') . "</p>";
    }

    // Test 5: Verify update
    echo "<h2>5. Verify Update</h2>";
    $verify_stmt = $test_pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $verify_stmt->execute();
    $verified = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if ($verified) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>Updated Token</th><td style='color: green; font-weight: bold;'>" . htmlspecialchars($verified['current_token']) . "</td></tr>";
        echo "<tr><th>New Timestamp</th><td>" . $verified['last_rotated'] . "</td></tr>";
        echo "</table>";

        if ($verified['current_token'] === $new_token) {
            echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: Token saved correctly!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ FAILED: Token not saved correctly!</p>";
            echo "<p>Expected: " . $new_token . "</p>";
            echo "<p>Found: " . $verified['current_token'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not verify - no data found</p>";
    }

    // Test 6: Test actual rotate_token.php
    echo "<h2>6. Test rotate_token.php Script</h2>";
    echo "<p>Running the actual rotate_token.php script...</p>";

    // Backup current token
    $backup_token = $verified['current_token'] ?? null;

    // Run the script
    ob_start();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // Simulate AJAX
    include 'rotate_token.php';
    $output = ob_get_clean();

    echo "<p>Script output:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";

    // Parse JSON output
    $json_data = json_decode($output, true);
    if ($json_data) {
        echo "<p>Parsed JSON response:</p>";
        echo "<ul>";
        echo "<li>Success: " . ($json_data['success'] ? '✅' : '❌') . "</li>";
        if (isset($json_data['token'])) {
            echo "<li>Token: " . htmlspecialchars($json_data['token']) . "</li>";
        }
        if (isset($json_data['error'])) {
            echo "<li>Error: " . htmlspecialchars($json_data['error']) . "</li>";
        }
        echo "</ul>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ DATABASE ERROR</p>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";

    echo "<h3>Common Solutions:</h3>";
    echo "<ol>";
    echo "<li>Check if MySQL server is running</li>";
    echo "<li>Verify database name exists: <code>" . DB_NAME . "</code></li>";
    echo "<li>Check username/password: <code>" . DB_USER . "</code></li>";
    echo "<li>Ensure user has privileges on the database</li>";
    echo "<li>Check if app_config table exists</li>";
    echo "</ol>";
}

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th { background: #f0f0f0; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "pre { font-family: monospace; font-size: 12px; }";
echo "</style>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all tests pass above - the issue is likely in the frontend JavaScript</li>";
echo "<li>If database tests fail - check database connection and permissions</li>";
echo "<li>If rotate_token.php test fails - there's an issue with the script logic</li>";
echo "</ol>";

echo "<p><a href='debug_database_save.php'>← Full Debug Analysis</a> | <a href='admin.php'>Admin Panel</a></p>";
?>