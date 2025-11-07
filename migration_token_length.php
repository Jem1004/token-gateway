<?php
/**
 * Migration Script - Update Token Length to 6 Characters
 * Run this script once to update existing database structure
 */

require_once 'config.php';

echo "<h1>Migration: Update Token Length to 6 Characters</h1>";

try {
    // Update column length for current_token
    echo "<h2>Step 1: Update column length</h2>";
    $sql = "ALTER TABLE app_config MODIFY COLUMN current_token VARCHAR(6) NOT NULL";
    $pdo->exec($sql);
    echo "<div class='success'>✓ Column current_token updated to VARCHAR(6)</div>";

    // Update existing token if it's longer than 6 characters
    echo "<h2>Step 2: Update existing token data</h2>";
    $stmt = $pdo->query("SELECT current_token FROM app_config WHERE id = 1");
    $currentToken = $stmt->fetchColumn();

    if (strlen($currentToken) > 6) {
        // Truncate existing token to 6 characters
        $newToken = substr($currentToken, 0, 6);
        $updateStmt = $pdo->prepare("UPDATE app_config SET current_token = :token WHERE id = 1");
        $updateStmt->execute([':token' => $newToken]);
        echo "<div class='success'>✓ Existing token truncated: '$currentToken' → '$newToken'</div>";
    } else {
        echo "<div class='info'>ℹ️ Current token is already 6 characters or less: '$currentToken'</div>";
    }

    // Generate new 6-character token
    function generateRandomToken($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    $newToken = generateRandomToken(6);
    $updateStmt = $pdo->prepare("UPDATE app_config SET current_token = :token WHERE id = 1");
    $updateStmt->execute([':token' => $newToken]);
    echo "<div class='success'>✓ New 6-character token generated: <strong>$newToken</strong></div>";

    // Verify the changes
    echo "<h2>Step 3: Verification</h2>";
    $stmt = $pdo->query("DESCRIBE app_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if ($column['Field'] === 'current_token') {
            echo "<div class='info'>ℹ️ Column info: {$column['Field']} - {$column['Type']}</div>";
            break;
        }
    }

    $stmt = $pdo->query("SELECT current_token FROM app_config WHERE id = 1");
    $finalToken = $stmt->fetchColumn();
    echo "<div class='success'>✓ Final token: <strong>$finalToken</strong></div>";

    echo "<div class='success' style='margin-top: 20px;'>";
    echo "<strong>✅ Migration completed successfully!</strong><br>";
    echo "Token system is now configured for 6-character alphabet tokens.";
    echo "</div>";

    echo "<p><a href='admin.php'>→ Go to Admin Panel</a></p>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Migration failed:</strong><br>";
    echo $e->getMessage();
    echo "</div>";

    echo "<div class='info'>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Make sure you have ALTER TABLE permissions<br>";
    echo "2. Check if the table already has VARCHAR(6) column<br>";
    echo "3. Verify database connection in config.php";
    echo "</div>";
}

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 5px; margin: 10px 0; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>";
?>