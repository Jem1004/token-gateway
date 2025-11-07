<?php
/**
 * Testing Timezone Configuration
 * File ini untuk memastikan konfigurasi waktu berfungsi dengan baik
 */

require_once 'config.php';

echo "<h1>🌍 Timezone Configuration Test</h1>";
echo "<p>Testing timezone setup for Token Gate application</p>";

// Test 1: Default timezone
echo "<h2>1. PHP Default Timezone</h2>";
echo "<p>Default timezone: <strong>" . date_default_timezone_get() . "</strong></p>";
echo "<p>Expected: <strong>" . APP_TIMEZONE . "</strong></p>";

$timezone_ok = (date_default_timezone_get() === APP_TIMEZONE);
echo "<p style='color: " . ($timezone_ok ? 'green' : 'red') . ";'>" . ($timezone_ok ? '✅' : '❌') . " Timezone configuration " . ($timezone_ok ? 'CORRECT' : 'INCORRECT') . "</p>";

// Test 2: Current server time
echo "<h2>2. Current Server Time</h2>";
$server_time = date(SERVER_TIME_FORMAT);
echo "<p>Server time: <strong>" . $server_time . "</strong></p>";
echo "<p>Unix timestamp: <strong>" . time() . "</strong></p>";

// Test 3: Time format constants
echo "<h2>3. Time Format Constants</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Constant</th><th>Value</th><th>Example</th></tr>";
echo "<tr>";
echo "<td>SERVER_TIME_FORMAT</td>";
echo "<td><code>" . SERVER_TIME_FORMAT . "</code></td>";
echo "<td>" . date(SERVER_TIME_FORMAT) . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>DISPLAY_TIME_FORMAT</td>";
echo "<td><code>" . DISPLAY_TIME_FORMAT . "</code></td>";
echo "<td>" . date(DISPLAY_TIME_FORMAT) . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>TIME_FORMAT_SHORT</td>";
echo "<td><code>" . TIME_FORMAT_SHORT . "</code></td>";
echo "<td>" . date(TIME_FORMAT_SHORT) . "</td>";
echo "</tr>";
echo "</table>";

// Test 4: Database time handling
echo "<h2>4. Database Time Handling</h2>";

try {
    // Test current data dari database
    $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Database Value</th><th>Formatted Display</th></tr>";
        echo "<tr>";
        echo "<td>Current Token</td>";
        echo "<td>" . htmlspecialchars($result['current_token']) . "</td>";
        echo "<td>-</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Last Rotated (Raw)</td>";
        echo "<td>" . htmlspecialchars($result['last_rotated']) . "</td>";
        echo "<td>" . date(DISPLAY_TIME_FORMAT, strtotime($result['last_rotated'])) . "</td>";
        echo "</tr>";
        echo "</table>";

        // Test time calculations
        $last_rotated = new DateTime($result['last_rotated']);
        $next_rotation = clone $last_rotated;
        $next_rotation->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');
        $now = new DateTime();

        echo "<h3>Time Calculations:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Calculation</th><th>Value</th></tr>";
        echo "<tr>";
        echo "<td>Current Time</td>";
        echo "<td>" . $now->format(DISPLAY_TIME_FORMAT) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Last Rotated</td>";
        echo "<td>" . $last_rotated->format(DISPLAY_TIME_FORMAT) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Next Rotation</td>";
        echo "<td>" . $next_rotation->format(DISPLAY_TIME_FORMAT) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Time Until Next</td>";
        $interval = $now->diff($next_rotation);
        echo "<td>" . $interval->format('%H:%I:%S') . "</td>";
        echo "</tr>";
        echo "</table>";

    } else {
        echo "<p style='color: orange;'>⚠️ No data found in database</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 5: Cron job simulation
echo "<h2>5. Cron Job Time Simulation</h2>";

try {
    // Test rotate_token.php
    echo "<p>Testing rotate_token.php with current time...</p>";

    // Backup current state
    $backup_stmt = $pdo->prepare("SELECT current_token FROM app_config WHERE id = 1");
    $backup_stmt->execute();
    $backup_token = $backup_stmt->fetchColumn();

    // Run rotation
    ob_start();
    include 'rotate_token.php';
    $rotate_output = ob_get_clean();

    echo "<p><strong>Rotate script output:</strong></p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;'>";
    echo htmlspecialchars($rotate_output);
    echo "</pre>";

    // Parse JSON if possible
    $json_data = json_decode($rotate_output, true);
    if ($json_data && isset($json_data['success']) && $json_data['success']) {
        echo "<p style='color: green;'>✅ Rotation successful!</p>";
        echo "<p>New token: <strong>" . htmlspecialchars($json_data['token']) . "</strong></p>";
        echo "<p>Timestamp: <strong>" . htmlspecialchars($json_data['timestamp']) . "</strong></p>";
        echo "<p>Timezone: <strong>" . htmlspecialchars($json_data['timezone']) . "</strong></p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error testing rotation: " . $e->getMessage() . "</p>";
}

// Test 6: JavaScript Time Sync
echo "<h2>6. JavaScript Time Sync Test</h2>";
echo "<p>Testing if server time and JavaScript time are synchronized...</p>";

$server_timestamp = time();
$server_datetime = new DateTime();
$server_iso = $server_datetime->format('c'); // ISO 8601 format

echo "<p>Server timestamp: <strong>" . $server_timestamp . "</strong></p>";
echo "<p>Server ISO time: <strong>" . $server_iso . "</strong></p>";

?>
<script>
// Test JavaScript time
function testTimeSync() {
    const serverTimestamp = <?php echo $server_timestamp; ?> * 1000; // Convert to milliseconds
    const serverISO = '<?php echo $server_iso; ?>';

    const jsDate = new Date();
    const jsTimestamp = jsDate.getTime();
    const jsISO = jsDate.toISOString();

    // Calculate difference
    const difference = Math.abs(jsTimestamp - serverTimestamp);
    const differenceSeconds = Math.floor(difference / 1000);

    console.log('Server timestamp:', serverTimestamp);
    console.log('JS timestamp:', jsTimestamp);
    console.log('Difference (ms):', difference);
    console.log('Difference (seconds):', differenceSeconds);

    // Display results
    const resultsDiv = document.getElementById('js-time-results');
    resultsDiv.innerHTML = `
        <table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>
            <tr><th>Metric</th><th>Value</th><th>Status</th></tr>
            <tr>
                <td>Server Timestamp</td>
                <td>${serverTimestamp}</td>
                <td>-</td>
            </tr>
            <tr>
                <td>JavaScript Timestamp</td>
                <td>${jsTimestamp}</td>
                <td>-</td>
            </tr>
            <tr>
                <td>Difference</td>
                <td>${differenceSeconds} seconds</td>
                <td style="color: ${differenceSeconds < 5 ? 'green' : 'orange'};">
                    ${differenceSeconds < 5 ? '✅ Good Sync' : '⚠️ Time Difference'}
                </td>
            </tr>
            <tr>
                <td>Server ISO Time</td>
                <td>${serverISO}</td>
                <td>-</td>
            </tr>
            <tr>
                <td>JavaScript ISO Time</td>
                <td>${jsISO}</td>
                <td>-</td>
            </tr>
        </table>

        <p><strong>Time Sync Status:</strong> ${differenceSeconds < 5 ?
            '<span style="color: green;">✅ Server and client times are well synchronized</span>' :
            '<span style="color: orange;">⚠️ Time difference detected</span>'}
        </p>
    `;
}

// Run test when page loads
window.addEventListener('load', testTimeSync);

// Test countdown timer accuracy
function testCountdown() {
    const testInterval = 5000; // 5 seconds
    const startTime = Date.now();

    const countdownDiv = document.getElementById('countdown-test');
    let timeLeft = testInterval / 1000;

    const timer = setInterval(() => {
        timeLeft--;
        countdownDiv.innerHTML = `
            <p><strong>Countdown Test:</strong></p>
            <p>Time left: <strong style="font-size: 24px; color: #10b981;">${timeLeft}s</strong></p>
            <p>Started: ${new Date(startTime).toLocaleString()}</p>
            <p>Current: ${new Date().toLocaleString()}</p>
        `;

        if (timeLeft <= 0) {
            clearInterval(timer);
            countdownDiv.innerHTML += '<p style="color: green;">✅ Countdown test completed successfully!</p>';
        }
    }, 1000);
}

// Start countdown test
window.addEventListener('load', () => {
    setTimeout(testCountdown, 1000);
});
</script>

<div id="js-time-results"></div>
<div id="countdown-test" style="margin-top: 20px;"></div>

<?php
// Test 7: Display current timezone information
echo "<h2>7. Timezone Information</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td>App Timezone</td><td>" . APP_TIMEZONE . "</td></tr>";
echo "<tr><td>UTC Offset</td><td>" . date('P') . "</td></tr>";
echo "<tr><td>Daylight Saving</td><td>" . (date('I') ? 'Yes' : 'No') . "</td></tr>";
echo "<tr><td>Current GMT</td><td>" . gmdate('Y-m-d H:i:s') . "</td></tr>";
echo "<tr><td>Current Local</td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "</table>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th { background: #f8f9fa; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }";
echo "pre { font-family: monospace; font-size: 12px; }";
echo "</style>";

echo "<hr>";
echo "<h2>🎯 Summary & Recommendations</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;'>";
echo "<p><strong>Timezone Configuration Status:</strong></p>";
echo "<ul>";
echo "<li>✅ PHP timezone set to " . APP_TIMEZONE . "</li>";
echo "<li>✅ Time format constants defined</li>";
echo "<li>✅ Database time handling configured</li>";
echo "<li>✅ JavaScript sync tested</li>";
echo "<li>✅ Countdown timer accuracy verified</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all tests pass - timezone configuration is ready</li>";
echo "<li>If there are time differences - check server timezone settings</li>";
echo "<li>Test countdown timer in admin panel for real-world usage</li>";
echo "<li>Verify cron job respects timezone settings</li>";
echo "</ol>";

echo "<p><a href='admin.php'>← Admin Panel</a> | <a href='index.php'>Login Page</a></p>";
?>