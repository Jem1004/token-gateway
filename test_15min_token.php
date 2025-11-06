<?php
/**
 * Test 15-Minute Token Rotation System
 * Test script to verify 15-minute auto-rotation functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>⏱️ 15-Minute Token Rotation Test</h2>";

// Test 1: Show current time
$now = new DateTime();
echo "<h3>🕐 Current Time</h3>";
echo "Now: " . $now->format('Y-m-d H:i:s') . "<br>";

// Calculate next rotation time (15 minutes from now)
$nextRotation = clone $now;
$nextRotation->add(new DateInterval('PT15M'));
echo "Next Rotation (15min): " . $nextRotation->format('Y-m-d H:i:s') . "<br>";

// Calculate time difference
$timeUntil = $nextRotation->getTimestamp() - $now->getTimestamp();
echo "Time Until Rotation: " . ($timeUntil / 60) . " minutes<br>";

// Test 2: Token Generation Test
echo "<h3>🔤 Token Generation Test</h3>";
require_once 'rotate_token.php';

echo "Generating 5 sample tokens (6 letters, uppercase):<br>";
for ($i = 0; $i < 5; $i++) {
    $token = generateToken(6);
    echo "Token " . ($i + 1) . ": <strong style='font-family: monospace; font-size: 1.2em; color: #22c55e;'>"
         . htmlspecialchars($token) . "</strong><br>";
}

// Test 3: Countdown Simulation
echo "<h3>⏳ Countdown Simulation</h3>";
echo "<div style='background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #22c55e;'>";
echo "<strong>15-Minute Token Rotation Schedule:</strong><br><br>";

$start = clone $now;
for ($i = 0; $i <= 3; $i++) {
    $rotationTime = clone $start;
    $rotationTime->add(new DateInterval('PT' . ($i * 15) . 'M'));

    echo "🔄 Rotation " . ($i + 1) . ": " . $rotationTime->format('H:i:s');
    if ($i === 0) {
        echo " (Next)";
    } elseif ($i === 3) {
        echo " (45 minutes from now)";
    }
    echo "<br>";
}

echo "</div>";

// Test 4: Database Check
echo "<h3>🗄️ Database Configuration Check</h3>";
try {
    require_once 'config.php';
    $conn = getDbConnection();

    echo "✅ Database connected successfully<br>";

    // Check current token in database
    $result = $conn->query("SELECT * FROM active_token WHERE id = 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Current DB token: <strong>" . htmlspecialchars($row['current_token']) . "</strong><br>";

        // Check if timer columns exist
        $checkColumns = $conn->query("SHOW COLUMNS FROM active_token LIKE 'token_rotation_interval'");
        if ($checkColumns->num_rows > 0) {
            echo "✅ Timer columns exist<br>";

            // Show current timer settings
            $timerResult = $conn->query("SELECT token_rotation_interval FROM active_token WHERE id = 1");
            if ($timerResult->num_rows > 0) {
                $timerRow = $timerResult->fetch_assoc();
                echo "📊 Current rotation interval: <strong>" . $timerRow['token_rotation_interval'] . " minutes</strong><br>";
            }
        } else {
            echo "⚠️ Timer columns not found (run migration)<br>";
        }
    } else {
        echo "❌ No token record found in database<br>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 5: JavaScript Countdown Preview
echo "<h3>📱 Countdown Display Preview</h3>";
echo "<div style='background: linear-gradient(135deg, #dcfce7 0%, #ffffff 100%); padding: 1.5rem; border-radius: 1rem; border: 2px solid #86efac;'>";
echo "<h4 style='color: #14532d; margin: 0 0 1rem 0;'>⏱️ Live Countdown Preview</h4>";
echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; text-align: center;'>";
echo "<div style='background: white; padding: 0.75rem; border-radius: 0.5rem;'>";
echo "<div style='font-size: 1.5rem; font-weight: bold; color: #22c55e; font-family: monospace;'>00</div>";
echo "<div style='font-size: 0.75rem; color: #6b7280;'>HARI</div>";
echo "</div>";
echo "<div style='background: white; padding: 0.75rem; border-radius: 0.5rem;'>";
echo "<div style='font-size: 1.5rem; font-weight: bold; color: #22c55e; font-family: monospace;'>00</div>";
echo "<div style='font-size: 0.75rem; color: #6b7280;'>JAM</div>";
echo "</div>";
echo "<div style='background: white; padding: 0.75rem; border-radius: 0.5rem;'>";
echo "<div style='font-size: 1.5rem; font-weight: bold; color: #22c55e; font-family: monospace;'>14</div>";
echo "<div style='font-size: 0.75rem; color: #6b7280;'>MENIT</div>";
echo "</div>";
echo "<div style='background: white; padding: 0.75rem; border-radius: 0.5rem;'>";
echo "<div style='font-size: 1.5rem; font-weight: bold; color: #22c55e; font-family: monospace;'>59</div>";
echo "<div style='font-size: 0.75rem; color: #6b7280;'>DETIK</div>";
echo "</div>";
echo "</div>";
echo "<div style='margin-top: 1rem; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;'>";
echo "<div style='height: 100%; width: 16.4%; background: linear-gradient(90deg, #4ade80, #22c55e); animation: shimmer 2s infinite;'></div>";
echo "</div>";
echo "</div>";

// Add shimmer animation
echo "<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>";

// Recommendations
echo "<h3>💡 Recommendations</h3>";
echo "<div style='background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #22c55e;'>";
echo "<ul style='margin: 0; padding-left: 1.5rem;'>";
echo "<li>🔄 <strong>Auto-Rotation:</strong> Token otomatis berubah setiap 15 menit</li>";
echo "<li>🔐 <strong>Security:</strong> 26^6 = 308,915,776 kombinasi unik</li>";
echo "<li>📱 <strong>User Experience:</strong> Countdown real-time di admin panel</li>";
echo "<li>🗄️ <strong>Database:</strong> Run migration untuk timer features: <code>mysql -u root -p token_gate_db < migration_add_token_timer.sql</code></li>";
echo "<li>🎯 <strong>Testing:</strong> Test token dari <strong>test_token.php</strong></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<small>Test completed at: " . date('Y-m-d H:i:s') . " | Token Rotation: 15 minutes</small>";
?>