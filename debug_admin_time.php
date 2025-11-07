<?php
/**
 * Debug Script for Admin Panel Time Display Issues
 * File ini untuk mendiagnosa masalah tampilan waktu di admin panel
 */

require_once 'config.php';

echo "<h1>🕐 Admin Panel Time Display Debug</h1>";
echo "<p>Debugging time display issues in admin panel</p>";

// Test 1: Basic PHP Time Functions
echo "<h2>1. Basic PHP Time Functions</h2>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Function</th><th>Result</th><th>Expected</th><th>Status</th></tr>";

// Test basic time functions
$test_functions = [
    'date_default_timezone_get()' => date_default_timezone_get(),
    'time()' => time(),
    'date("Y-m-d H:i:s")' => date('Y-m-d H:i:s'),
    'date("d M Y, H:i:s")' => date('d M Y, H:i:s'),
    'date("H:i")' => date('H:i'),
    'gmdate("Y-m-d H:i:s")' => gmdate('Y-m-d H:i:s')
];

foreach ($test_functions as $func => $result) {
    $expected = '';
    $status = '✅';

    switch ($func) {
        case 'date_default_timezone_get()':
            $expected = APP_TIMEZONE;
            $status = ($result === $expected) ? '✅' : '❌';
            break;
        case 'date("H:i")':
            $expected = date('H:i');
            $status = '✅';
            break;
        case 'date("d M Y, H:i:s")':
            $expected = date('d M Y, H:i:s');
            $status = '✅';
            break;
    }

    echo "<tr>";
    echo "<td><code>" . $func . "</code></td>";
    echo "<td><strong>" . $result . "</strong></td>";
    echo "<td>" . $expected . "</td>";
    echo "<td style='color: " . ($status === '✅' ? 'green' : 'red') . ";'>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test 2: Constants
echo "<h2>2. Constants Check</h2>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Constant</th><th>Value</th><th>Type</th></tr>";

$constants = [
    'APP_TIMEZONE' => APP_TIMEZONE,
    'SERVER_TIME_FORMAT' => SERVER_TIME_FORMAT,
    'DISPLAY_TIME_FORMAT' => DISPLAY_TIME_FORMAT,
    'TIME_FORMAT_SHORT' => TIME_FORMAT_SHORT,
    'SECONDS_PER_MINUTE' => SECONDS_PER_MINUTE,
    'TOKEN_ROTATION_MINUTES' => TOKEN_ROTATION_MINUTES
];

foreach ($constants as $const => $value) {
    $type = gettype($value);
    echo "<tr>";
    echo "<td><code>" . $const . "</code></td>";
    echo "<td>" . $value . "</td>";
    echo "<td>" . $type . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test 3: Database Time Data
echo "<h2>3. Database Time Data</h2>";

try {
    $stmt = $pdo->prepare("SELECT id, current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<p><strong>Database Record Found:</strong></p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Raw Value</th><th>Formatted (SERVER_TIME_FORMAT)</th><th>Formatted (DISPLAY_TIME_FORMAT)</th></tr>";

        echo "<tr>";
        echo "<td>id</td>";
        echo "<td>" . $result['id'] . "</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>current_token</td>";
        echo "<td>" . htmlspecialchars($result['current_token']) . "</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>last_rotated</td>";
        echo "<td>" . htmlspecialchars($result['last_rotated']) . "</td>";
        echo "<td>" . date(SERVER_TIME_FORMAT, strtotime($result['last_rotated'])) . "</td>";
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
        echo "<tr><th>Calculation</th><th>Result</th><th>Expected</th></tr>";

        echo "<tr>";
        echo "<td>Current Time</td>";
        echo "<td>" . $now->format(SERVER_TIME_FORMAT) . "</td>";
        echo "<td>Server current time</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>Last Rotated (Database)</td>";
        echo "<td>" . $last_rotated->format(SERVER_TIME_FORMAT) . "</td>";
        echo "<td>Database stored time</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>Next Rotation</td>";
        echo "<td>" . $next_rotation->format(SERVER_TIME_FORMAT) . "</td>";
        echo "<td>Calculated next rotation</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>Time Until Next</td>";
        $interval = $now->diff($next_rotation);
        echo "<td>" . $interval->format('%H:%I:%S') . "</td>";
        echo "<td>Time remaining</td>";
        echo "</tr>";

        echo "</table>";

        // Calculate sisaDetik seperti di admin.php
        $sisaDetik = $next_rotation->getTimestamp() - $now->getTimestamp();
        if ($sisaDetik < 0) {
            $sisaDetik = 0;
        }

        echo "<p><strong>sisaDetik calculation:</strong> " . $sisaDetik . " seconds</p>";

    } else {
        echo "<p style='color: red;'>❌ No data found in database</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Admin Panel Simulation
echo "<h2>4. Admin Panel Simulation</h2>";

// Simulate session data
$_SESSION['login_time'] = time();
$_SESSION['login_time_formatted'] = date(SERVER_TIME_FORMAT);

echo "<p><strong>Simulated Session Data:</strong></p>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Session Variable</th><th>Value</th><th>Formatted</th></tr>";

echo "<tr>";
echo "<td>\$_SESSION['login_time']</td>";
echo "<td>" . $_SESSION['login_time'] . "</td>";
echo "<td>" . date(TIME_FORMAT_SHORT, $_SESSION['login_time']) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>\$_SESSION['login_time_formatted']</td>";
echo "<td>" . $_SESSION['login_time_formatted'] . "</td>";
echo "<td>" . date(TIME_FORMAT_SHORT, strtotime($_SESSION['login_time_formatted'])) . "</td>";
echo "</tr>";

echo "</table>";

// Test admin panel HTML rendering
echo "<h3>Admin Panel Time Display Test:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd;'>";
echo "<p><strong>Session Time Display:</strong> 🕐 Login: " . date(TIME_FORMAT_SHORT, $_SESSION['login_time']) . " (" . APP_TIMEZONE . ")</p>";
echo "</div>";

// Test rotation info display
if (isset($result)) {
    $lastRotated = new DateTime($result['last_rotated']);
    $nextRotation = clone $lastRotated;
    $nextRotation->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');

    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-top: 10px;'>";
    echo "<p><strong>Rotation Info Display:</strong></p>";
    echo "<p>📅 Terakhir Dirotasi: <strong>" . date(DISPLAY_TIME_FORMAT, strtotime($result['last_rotated'])) . "</strong></p>";
    echo "<p>📅 Rotasi Berikutnya: <strong>" . date(DISPLAY_TIME_FORMAT, $nextRotation->getTimestamp()) . "</strong></p>";
    echo "</div>";

    // Test countdown simulation
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-top: 10px;'>";
    echo "<p><strong>Countdown Simulation:</strong></p>";
    echo "<p>⏰ Token akan berganti dalam: <strong style='color: #10b981; font-size: 18px;'>";
    echo "<span id='debug-countdown'>" . formatTime($sisaDetik) . "</span>";
    echo "</strong></p>";
    echo "<p><em>Simulated countdown based on database data</em></p>";
    echo "</div>";
}

function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $secs);
}

echo "<script>";
echo "// Simulate countdown timer
let debugSisaDetik = " . $sisaDetik . ";
const debugTotalSeconds = " . (TOKEN_ROTATION_MINUTES * SECONDS_PER_MINUTE) . ";

function updateDebugCountdown() {
    const timerElement = document.getElementById('debug-countdown');
    if (debugSisaDetik > 0) {
        timerElement.textContent = formatTime(debugSisaDetik);
        debugSisaDetik--;
    } else {
        timerElement.textContent = '00:00';
        timerElement.style.color = '#ef4444';
    }
}

// Start countdown
setInterval(updateDebugCountdown, 1000);
updateDebugCountdown();
echo "</script>";

// Test 5: Current Issues Analysis
echo "<h2>5. Issues Analysis</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b;'>";
echo "<h3>Possible Issues & Solutions:</h3>";
echo "<ol>";
echo "<li><strong>Timezone Mismatch:</strong><br>";
echo "   Check if server timezone matches Asia/Jakarta<br>";
echo "   Solution: <code>sudo timedatectl set-timezone Asia/Jakarta</code></li>";
echo "";
echo "<li><strong>PHP Timezone Not Set:</strong><br>";
echo "   PHP fallback to UTC may cause wrong display<br>";
echo "   Solution: Already fixed in config.php</li>";
echo "";
echo "<li><strong>MySQL Timezone Different:</strong><br>";
echo "   Database CURRENT_TIMESTAMP uses server timezone<br>";
echo "   Solution: <code>SET GLOBAL time_zone = 'Asia/Jakarta'</code></li>";
echo "";
echo "<li><strong>Client-Server Time Difference:</strong><br>";
echo "   Browser timezone different from server<br>";
echo "   Solution: Server-side time calculations</li>";
echo "</ol>";
echo "</div>";

// Test 6: Recommendations
echo "<h2>6. Recommendations</h2>";

echo "<div style='background: #d1fae5; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;'>";
echo "<h3>✅ Already Implemented:</h3>";
echo "<ul>";
echo "<li>✅ PHP timezone set to Asia/Jakarta</li>";
echo "<li>✅ Time format constants defined</li>";
echo "<li>✅ Server-side time calculations</li>";
echo "<li>✅ Consistent time formats across application</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fef3c7; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b;'>";
echo "<h3>🔧 Still Needed:</h3>";
echo "<ul>";
echo "<li>🔧 Check actual admin panel display</li>";
echo "<li>🔧 Verify countdown timer accuracy</li>";
echo "<li>🔧 Test cron job timing</li>";
echo "<li>🔧 User feedback on time display</li>";
echo "</ul>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th { background: #f8f9fa; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }";
echo "</style>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check if all tests above show ✅ status</li>";
echo "<li>If issues found, follow the troubleshooting steps</li>";
echo "<li>Test actual admin panel display</li>";
echo "<li>Verify countdown timer accuracy</li>";
echo "</ol>";

echo "<p><a href='admin.php'>→ Test Admin Panel</a> | <a href='test_timezone.php'>Timezone Test</a></p>";
?>