<?php
/**
 * Test file untuk mensimulasikan cron job rotasi token
 * Gunakan file ini untuk testing rotasi otomatis tanpa harus menunggu 15 menit
 */

require_once 'config.php';

echo "<h1>🕐 Cron Job Simulation Test</h1>";
echo "<p>File ini mensimulasikan rotasi token otomatis setiap 15 menit</p>";

// Check if rotation should run
function shouldRotateToken($lastRotated, $intervalMinutes) {
    $now = new DateTime();
    $lastRotatedTime = new DateTime($lastRotated);
    $nextRotation = clone $lastRotatedTime;
    $nextRotation->modify('+' . $intervalMinutes . ' minutes');

    return $now >= $nextRotation;
}

// Get current token info
echo "<h2>Current Token Status</h2>";

try {
    $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        echo "<p style='color: red;'>❌ No token data found</p>";
        exit;
    }

    $currentToken = $result['current_token'];
    $lastRotated = $result['last_rotated'];

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Current Token</td><td><strong>" . htmlspecialchars($currentToken) . "</strong></td></tr>";
    echo "<tr><td>Last Rotated</td><td>" . htmlspecialchars($lastRotated) . "</td></tr>";
    echo "<tr><td>Rotation Interval</td><td>" . TOKEN_ROTATION_MINUTES . " minutes</td></tr>";

    $now = new DateTime();
    $lastRot = new DateTime($lastRotated);
    $nextRot = clone $lastRot;
    $nextRot->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');

    echo "<tr><td>Next Rotation</td><td>" . $nextRot->format('Y-m-d H:i:s') . "</td></tr>";
    echo "<tr><td>Current Time</td><td>" . $now->format('Y-m-d H:i:s') . "</td></tr>";

    $timeDiff = $nextRot->getTimestamp() - $now->getTimestamp();
    $minutesUntil = floor($timeDiff / 60);
    $secondsUntil = $timeDiff % 60;

    echo "<tr><td>Time Until Rotation</td><td><strong>{$minutesUntil}m {$secondsUntil}s</strong></td></tr>";
    echo "</table>";

    // Check if rotation is needed
    $shouldRotate = shouldRotateToken($lastRotated, TOKEN_ROTATION_MINUTES);

    echo "<h2>Rotation Check</h2>";

    if ($shouldRotate) {
        echo "<p style='color: orange;'>⏰ Token should be rotated now!</p>";

        // Perform rotation
        include 'rotate_token.php';

        echo "<p style='color: green;'>✅ Rotation executed</p>";

        // Get new token
        $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
        $stmt->execute();
        $newResult = $stmt->fetch();

        echo "<p><strong>New Token:</strong> " . htmlspecialchars($newResult['current_token']) . "</p>";
        echo "<p><strong>New Rotation Time:</strong> " . htmlspecialchars($newResult['last_rotated']) . "</p>";

    } else {
        echo "<p style='color: blue;'>ℹ️ Token rotation not needed yet</p>";
        echo "<p>Next rotation will be at: <strong>" . $nextRot->format('Y-m-d H:i:s') . "</strong></p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Force rotation option
echo "<h2>Force Rotation (Test)</h2>";
echo "<form method='POST'>";
echo "<input type='hidden' name='force_rotate' value='1'>";
echo "<button type='submit' style='background: #f59e0b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🔄 Force Rotate Now";
echo "</button>";
echo "</form>";

if (isset($_POST['force_rotate'])) {
    echo "<h3>Force Rotation Result:</h3>";

    try {
        include 'rotate_token.php';

        // Get updated token
        $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch();

        echo "<p style='color: green;'>✅ Force rotation successful!</p>";
        echo "<p><strong>Updated Token:</strong> " . htmlspecialchars($result['current_token']) . "</p>";
        echo "<p><strong>Updated At:</strong> " . htmlspecialchars($result['last_rotated']) . "</p>";

        // Refresh page after 3 seconds
        echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Force rotation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Cron job setup instructions
echo "<h2>Cron Job Setup</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;'>";
echo "<h3>Setting up Cron Job on Ubuntu Server:</h3>";
echo "<ol>";
echo "<li>Open crontab: <code>crontab -e</code></li>";
echo "<li>Add this line for 15-minute rotation:<br>";
echo "<code>*/15 * * * * /usr/bin/php " . __DIR__ . "/rotate_token.php > /dev/null 2>&1</code></li>";
echo "<li>Save and exit (Ctrl+X, Y, Enter in nano)</li>";
echo "</ol>";
echo "<p><strong>For testing (1-minute interval):</strong><br>";
echo "<code>*/1 * * * * /usr/bin/php " . __DIR__ . "/rotate_token.php > /dev/null 2>&1</code></p>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }";
echo "table { width: 100%; }";
echo "th { background: #f8f9fa; text-align: left; padding: 8px; }";
echo "td { padding: 8px; }";
echo "code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }";
echo "</style>";

echo "<p><a href='admin.php'>← Back to Admin Panel</a> | <a href='test_rotate.php'>Test Rotate Function</a></p>";
?>