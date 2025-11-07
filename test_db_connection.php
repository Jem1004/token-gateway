<?php
/**
 * Test Database Connection
 *
 * This script tests the database connection independently
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test config.php loading
echo "<h3>1. Testing config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php found<br>";
    try {
        require_once 'config.php';
        echo "✅ config.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error loading config.php: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "❌ config.php NOT found<br>";
    exit;
}

// Test getDbConnection function
echo "<h3>2. Testing getDbConnection function</h3>";
if (function_exists('getDbConnection')) {
    echo "✅ getDbConnection function exists<br>";

    try {
        $conn = getDbConnection();
        if ($conn) {
            echo "✅ Database connection successful<br>";
            echo "Host info: " . $conn->host_info . "<br>";

            // Test basic query
            $result = $conn->query("SELECT 1 as test");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "✅ Basic query test: " . $row['test'] . "<br>";
            } else {
                echo "❌ Basic query failed<br>";
            }

            // Test active_token table
            echo "<h3>3. Testing active_token table</h3>";
            $stmt = $conn->prepare("SELECT * FROM active_token WHERE id = 1");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "✅ active_token table accessible<br>";
                    $row = $result->fetch_assoc();
                    echo "Current token: " . htmlspecialchars($row['current_token']) . "<br>";
                    echo "Last updated: " . $row['updated_at'] . "<br>";
                } else {
                    echo "❌ No data found in active_token table<br>";
                }
                $stmt->close();
            } else {
                echo "❌ Failed to prepare statement for active_token<br>";
            }

            $conn->close();
            echo "✅ Connection closed<br>";

        } else {
            echo "❌ Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database connection error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getDbConnection function NOT found<br>";
}

echo "<h3>4. Test Complete</h3>";
echo "<p>If all tests pass ✅, the database connection is working properly.</p>";
echo "<p>If you see ❌ errors, check the database configuration in config.php.</p>";

// Show current config (without password)
echo "<h3>5. Current Configuration</h3>";
echo "Database Host: " . ($db_host ?? 'Not set') . "<br>";
echo "Database Name: " . ($db_name ?? 'Not set') . "<br>";
echo "Database User: " . ($db_user ?? 'Not set') . "<br>";
echo "Database Password: " . (empty($db_pass) ? 'Empty' : 'Set') . "<br>";
echo "Exam URL: " . ($exam_url ?? 'Not set') . "<br>";
?>