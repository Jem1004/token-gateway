<?php
// File untuk testing koneksi database
require_once 'config.php';

echo "<h1>Testing Koneksi Database Token Gate</h1>";
echo "<h2>Konfigurasi Database:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
echo "<li><strong>User:</strong> " . DB_USER . "</li>";
echo "<li><strong>Password:</strong> " . (empty(DB_PASS) ? "(kosong)" : str_repeat("*", strlen(DB_PASS))) . "</li>";
echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
echo "</ul>";

echo "<h2>Testing Koneksi:</h2>";

try {
    // Test koneksi
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "<p style='color: green; font-weight: bold;'>✓ Koneksi database berhasil!</p>";

    // Test query ke tabel app_config
    echo "<h3>Testing Query Tabel app_config:</h3>";
    $stmt = $pdo->query("SELECT * FROM app_config WHERE id = 1");
    $result = $stmt->fetch();

    if ($result) {
        echo "<p style='color: green;'>✓ Tabel app_config ditemukan</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Current Token</th><th>Last Rotated</th></tr>";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($result['id']) . "</td>";
        echo "<td>" . htmlspecialchars($result['current_token']) . "</td>";
        echo "<td>" . htmlspecialchars($result['last_rotated']) . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ Tabel app_config tidak ada atau kosong</p>";
        echo "<p>Silakan import database.sql terlebih dahulu:</p>";
        echo "<pre>mysql -u " . DB_USER . " -p " . DB_NAME . " < database.sql</pre>";
    }

    // Test membuat token baru
    echo "<h3>Testing Generate Token:</h3>";
    function generateRandomToken($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    $test_token = generateRandomToken();
    echo "<p style='color: blue;'>Token generated: <strong>" . $test_token . "</strong></p>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error koneksi database:</p>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>" . $e->getMessage() . "</p>";

    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Pastikan MySQL server sedang berjalan</li>";
    echo "<li>Pastikan user '" . DB_USER . "' memiliki hak akses ke database '" . DB_NAME . "'</li>";
    echo "<li>Pastikan database '" . DB_NAME . "' sudah dibuat</li>";
    echo "<li>Periksa apakah password benar</li>";
    echo "</ul>";

    echo "<h3>Cara Membuat Database:</h3>";
    echo "<pre>";
    echo "# Login ke MySQL\n";
    echo "mysql -u root -p\n\n";
    echo "# Buat database\n";
    echo "CREATE DATABASE token_gate_db;\n\n";
    echo "# Import struktur tabel\n";
    echo "USE token_gate_db;\n";
    echo "SOURCE /path/to/database.sql;\n";
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Kembali ke Halaman Utama</a></p>";
?>