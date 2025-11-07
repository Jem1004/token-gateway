<?php
/**
 * Test rotate_token.php functionality
 * File ini untuk testing rotasi token tanpa UI
 */

require_once 'config.php';

echo "<h1>🧪 Test Rotasi Token</h1>";

// Simulate AJAX request
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

echo "<p>Testing rotate_token.php...</p>";

// Capture output
ob_start();
include 'rotate_token.php';
$output = ob_get_clean();

echo "<h2>Output dari rotate_token.php:</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($output);
echo "</pre>";

// Parse JSON response
$json_data = json_decode($output, true);

if ($json_data !== null) {
    echo "<h2>✅ JSON Response Valid:</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";

    foreach ($json_data as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    if ($json_data['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ Rotasi token berhasil!</p>";
        echo "<p>Token baru: <strong>" . htmlspecialchars($json_data['token']) . "</strong></p>";
        echo "<p>Timestamp: " . htmlspecialchars($json_data['timestamp']) . "</p>";
        echo "<p>Timezone: " . htmlspecialchars($json_data['timezone']) . "</p>";

        // Verify di database
        try {
            $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['current_token'] === $json_data['token']) {
                echo "<p style='color: green;'>✅ Token berhasil disimpan di database!</p>";
            } else {
                echo "<p style='color: red;'>❌ Token tidak cocok di database!</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error verifikasi database: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Rotasi token gagal!</p>";
        echo "<p>Error: " . htmlspecialchars($json_data['error'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Output bukan JSON yang valid!</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
}

echo "<h2>🔧 Info Debugging:</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Output Length</td><td>" . strlen($output) . " characters</td></tr>";
echo "<tr><td>JSON Error</td><td>" . json_last_error_msg() . "</td></tr>";
echo "<tr><td>App Timezone</td><td>" . APP_TIMEZONE . "</td></tr>";
echo "<tr><td>Server Time</td><td>" . date(SERVER_TIME_FORMAT) . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><a href='admin.php'>← Kembali ke Admin Panel</a></p>";
echo "<p><a href='test_db_connection.php'>Test Koneksi Database</a></p>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th { background: #f8f9fa; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "pre { font-family: monospace; font-size: 12px; overflow-x: auto; }";
echo "</style>";
?>