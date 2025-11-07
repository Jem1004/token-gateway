<?php
/**
 * Comprehensive Database Debugging Script
 * Gunakan script ini untuk mendiagnosis masalah penyimpanan token di database
 */

require_once 'config.php';

echo "<h1>🔍 Database Save Debugging - Step by Step Analysis</h1>";

// Step 1: Test basic database connection
echo "<h2>Step 1: Database Connection Test</h2>";

try {
    echo "<p>Testing connection to MySQL server...</p>";
    $testConn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    echo "<p style='color: green;'>✅ Connected to MySQL server successfully</p>";

    echo "<p>Testing connection to database: " . DB_NAME . "</p>";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Connected to database successfully</p>";

    echo "<p><strong>Connection Details:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "<li>Password: " . (empty(DB_PASS) ? "(empty)" : str_repeat("*", strlen(DB_PASS))) . "</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Possible Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Check MySQL server is running</li>";
    echo "<li>Verify database name exists</li>";
    echo "<li>Check username and password</li>";
    echo "<li>Verify user has privileges on database</li>";
    echo "</ul>";
    exit;
}

// Step 2: Check table structure
echo "<h2>Step 2: Table Structure Analysis</h2>";

try {
    echo "<p>Checking app_config table structure...</p>";

    $stmt = $pdo->query("DESCRIBE app_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check specifically the current_token column
    foreach ($columns as $column) {
        if ($column['Field'] === 'current_token') {
            echo "<p><strong>Current Token Column:</strong> " . htmlspecialchars($column['Type']) . "</p>";
            if (strpos($column['Type'], 'varchar') !== false) {
                preg_match('/varchar\((\d+)\)/', $column['Type'], $matches);
                $max_length = isset($matches[1]) ? $matches[1] : 'unknown';
                echo "<p style='color: " . ($max_length >= 6 ? 'green' : 'orange') . ";'>";
                echo "Max length: " . $max_length . " characters " . ($max_length >= 6 ? "✅" : "⚠️ Should be at least 6");
                echo "</p>";
            }
        }
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
}

// Step 3: Check current data
echo "<h2>Step 3: Current Data Analysis</h2>";

try {
    echo "<p>Checking current data in app_config table...</p>";

    $stmt = $pdo->query("SELECT * FROM app_config");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Found " . count($rows) . " row(s) in app_config table</p>";

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><td>" . htmlspecialchars($row['id']) . "</td></tr>";
            echo "<tr><th>Current Token</th><td><strong style='color: blue;'>" . htmlspecialchars($row['current_token']) . "</strong></td></tr>";
            echo "<tr><th>Last Rotated</th><td>" . htmlspecialchars($row['last_rotated']) . "</td></tr>";
            echo "</table>";

            // Validate current token
            $token = $row['current_token'];
            echo "<p><strong>Token Validation:</strong></p>";
            echo "<ul>";
            echo "<li>Length: " . strlen($token) . " characters " . (strlen($token) === 6 ? "✅" : "❌ Should be 6") . "</li>";
            echo "<li>Format: " . (preg_match('/^[A-Z]{6}$/', $token) ? "✅ Valid (6 uppercase letters)" : "❌ Invalid format") . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No data found in app_config table</p>";
        echo "<p>Table exists but is empty. Need to insert initial data.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error reading data: " . $e->getMessage() . "</p>";
}

// Step 4: Test SELECT query
echo "<h2>Step 4: Test SELECT Query</h2>";

try {
    echo "<p>Testing SELECT query for id = 1...</p>";

    $stmt = $pdo->prepare("SELECT id, current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<p style='color: green;'>✅ SELECT query successful</p>";
        echo "<p>Found record: ID=" . $result['id'] . ", Token=" . htmlspecialchars($result['current_token']) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No record found with id = 1</p>";
        echo "<p>This could be the problem! Let's try to insert data...</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ SELECT query failed: " . $e->getMessage() . "</p>";
}

// Step 5: Test UPDATE query with detailed logging
echo "<h2>Step 5: Test UPDATE Query (Most Important)</h2>";

try {
    echo "<p>Testing UPDATE query with new token...</p>";

    // Generate test token
    function generateRandomToken($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    $test_token = generateRandomToken(6);
    echo "<p>Generated test token: <strong style='color: blue;'>" . $test_token . "</strong></p>";

    // Test the exact same UPDATE query used in rotate_token.php
    echo "<p>Executing: UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1</p>";

    $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");

    echo "<p>Prepared statement created successfully</p>";

    $params = [':token' => $test_token];
    echo "<p>Parameters: " . json_encode($params) . "</p>";

    $result = $stmt->execute($params);

    echo "<p>Execute result: " . ($result ? 'true' : 'false') . "</p>";

    // Check affected rows
    $affected_rows = $stmt->rowCount();
    echo "<p>Affected rows: " . $affected_rows . "</p>";

    if ($affected_rows > 0) {
        echo "<p style='color: green;'>✅ UPDATE query successful! " . $affected_rows . " row(s) affected</p>";

        // Verify the update
        echo "<p>Verifying update...</p>";
        $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
        $stmt->execute();
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($updated && $updated['current_token'] === $test_token) {
            echo "<p style='color: green;'>✅ Update verified! Token saved successfully.</p>";
            echo "<p>New token in database: <strong style='color: green;'>" . htmlspecialchars($updated['current_token']) . "</strong></p>";
            echo "<p>New timestamp: " . htmlspecialchars($updated['last_rotated']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Update verification failed!</p>";
        }

    } else {
        echo "<p style='color: red;'>❌ UPDATE query failed! No rows affected.</p>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>No record with id = 1 exists</li>";
        echo "<li>Database user doesn't have UPDATE privileges</li>";
        echo "<li>Table is locked or read-only</li>";
        echo "<li>Constraint violation</li>";
        echo "</ul>";

        // Let's try to check if there's a record with id = 1
        echo "<p>Checking if record with id = 1 exists...</p>";
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM app_config WHERE id = 1");
        $check_stmt->execute();
        $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo "<p>Records with id = 1: " . $count . "</p>";

        if ($count == 0) {
            echo "<p style='color: orange;'>⚠️ No record with id = 1 found! This is the problem.</p>";
            echo "<p>Trying to insert initial data...</p>";

            try {
                $insert_stmt = $pdo->prepare("INSERT INTO app_config (id, current_token) VALUES (1, :token)");
                $insert_result = $insert_stmt->execute([':token' => $test_token]);

                if ($insert_result) {
                    echo "<p style='color: green;'>✅ Initial data inserted successfully!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to insert initial data</p>";
                }
            } catch (PDOException $insert_e) {
                echo "<p style='color: red;'>❌ Insert failed: " . $insert_e->getMessage() . "</p>";
            }
        }
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ UPDATE query failed with exception: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong></p>";
    echo "<ul>";
    echo "<li>Error Code: " . $e->getCode() . "</li>";
    echo "<li>Error Message: " . $e->getMessage() . "</li>";
    echo "<li>File: " . $e->getFile() . ":" . $e->getLine() . "</li>";
    echo "</ul>";
}

// Step 6: Test permissions
echo "<h2>Step 6: Database Permissions Test</h2>";

try {
    echo "<p>Testing database user permissions...</p>";

    // Test SELECT permission
    $stmt = $pdo->query("SELECT COUNT(*) FROM app_config");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✅ SELECT permission: OK (found $count records)</p>";

    // Test INSERT permission (on a test table if exists, otherwise skip)
    echo "<p>Testing INSERT permission...</p>";
    try {
        $test_insert = $pdo->prepare("INSERT INTO app_config (id, current_token) VALUES (999, 'TEST') ON DUPLICATE KEY UPDATE current_token = 'TEST'");
        $test_insert->execute();
        echo "<p style='color: green;'>✅ INSERT permission: OK</p>";

        // Clean up test data
        $cleanup = $pdo->prepare("DELETE FROM app_config WHERE id = 999");
        $cleanup->execute();

    } catch (PDOException $insert_e) {
        echo "<p style='color: orange;'>⚠️ INSERT permission test failed: " . $insert_e->getMessage() . "</p>";
    }

    // Test UPDATE permission
    echo "<p>Testing UPDATE permission...</p>";
    $test_update = $pdo->prepare("UPDATE app_config SET current_token = current_token WHERE id = 1");
    $test_update->execute();
    echo "<p style='color: green;'>✅ UPDATE permission: OK</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Permission test failed: " . $e->getMessage() . "</p>";
}

// Step 7: Test the actual rotate_token.php
echo "<h2>Step 7: Test Actual rotate_token.php Script</h2>";

echo "<p>Testing the actual rotate_token.php script...</p>";

// Capture output and include the script
ob_start();
include 'rotate_token.php';
$output = ob_get_clean();

echo "<p><strong>Output from rotate_token.php:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #ddd;'>";
echo htmlspecialchars($output);
echo "</pre>";

// Check final state
echo "<p>Checking final database state after running rotate_token.php...</p>";
try {
    $final_stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $final_stmt->execute();
    $final_result = $final_stmt->fetch(PDO::FETCH_ASSOC);

    if ($final_result) {
        echo "<p><strong>Final token:</strong> " . htmlspecialchars($final_result['current_token']) . "</p>";
        echo "<p><strong>Final timestamp:</strong> " . htmlspecialchars($final_result['last_rotated']) . "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error checking final state: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎯 Summary & Recommendations</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;'>";
echo "<p><strong>Based on the analysis above:</strong></p>";
echo "<ol>";
echo "<li>If UPDATE query in Step 5 succeeded - the issue is likely in the rotate_token.php script</li>";
echo "<li>If no record with id = 1 exists - need to insert initial data</li>";
echo "<li>If permissions are missing - need to grant proper privileges to database user</li>";
echo "<li>If table structure is wrong - need to run migration script</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Review the output above carefully</li>";
echo "<li>Fix any identified issues</li>";
echo "<li>Test the fix by running rotate_token.php again</li>";
echo "<li>Verify the fix in the admin panel</li>";
echo "</ol>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th { background: #f8f9fa; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "pre { font-family: monospace; }";
echo "</style>";

echo "<p><a href='admin.php'>← Back to Admin Panel</a> | <a href='test_rotate.php'>Test Rotate Function</a></p>";
?>