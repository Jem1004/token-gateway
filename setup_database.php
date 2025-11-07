<?php
// File setup database otomatis untuk Token Gate
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "    <title>Setup Database - Token Gate</title>";
echo "    <link rel='stylesheet' href='style.css'>";
echo "    <style>";
echo "        .setup-container { max-width: 800px; margin: 0 auto; padding: 20px; }";
echo "        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }";
echo "        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }";
echo "        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }";
echo "        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }";
echo "        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }";
echo "        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }";
echo "    </style>";
echo "</head>";
echo "<body>";
echo "<div class='setup-container'>";

echo "<h1>🚀 Setup Database Token Gate</h1>";
echo "<p>Halaman ini akan membantu Anda setup database untuk aplikasi Token Gate.</p>";

// Step 1: Test koneksi database
echo "<h2>Step 1: Test Koneksi Database</h2>";

try {
    // Test koneksi ke MySQL server tanpa database
    $pdo_server = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='success'>✓ Koneksi ke MySQL server berhasil!</div>";

    // Test koneksi ke database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='success'>✓ Koneksi ke database '" . DB_NAME . "' berhasil!</div>";

} catch (PDOException $e) {
    echo "<div class='error'>✗ Error koneksi: " . $e->getMessage() . "</div>";

    // Jika database tidak ada
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<div class='warning'>";
        echo "<strong>Database '" . DB_NAME . "' tidak ada.</strong><br>";
        echo "Silakan buat database terlebih dahulu:<br>";
        echo "<pre>CREATE DATABASE " . DB_NAME . ";</pre>";
        echo "</div>";
    }

    echo "<div class='info'>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Pastikan MySQL server sedang berjalan<br>";
    echo "2. Pastikan user '" . DB_USER . "' memiliki hak akses<br>";
    echo "3. Periksa password dan username<br>";
    echo "4. Pastikan database '" . DB_NAME . "' sudah dibuat";
    echo "</div>";

    echo "<p><a href='test_db_connection.php'>Test Koneksi Database →</a></p>";
    echo "</div></body></html>";
    exit;
}

// Step 2: Cek/buat tabel
echo "<h2>Step 2: Setup Tabel app_config</h2>";

try {
    // Cek apakah tabel sudah ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_config'");
    $table_exists = $stmt->rowCount() > 0;

    if ($table_exists) {
        echo "<div class='warning'>⚠ Tabel 'app_config' sudah ada!</div>";

        // Cek data
        $stmt = $pdo->query("SELECT * FROM app_config WHERE id = 1");
        $data = $stmt->fetch();

        if ($data) {
            echo "<div class='success'>✓ Data token ditemukan:</div>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Current Token</th><th>Last Rotated</th></tr>";
            echo "<tr>";
            echo "<td>" . htmlspecialchars($data['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($data['current_token']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($data['last_rotated']) . "</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠ Tabel ada tapi kosong!</div>";
        }
    } else {
        echo "<div class='info'>📝 Membuat tabel 'app_config'...</div>";

        // Buat tabel
        $sql = "CREATE TABLE app_config (
            id INT PRIMARY KEY DEFAULT 1,
            current_token VARCHAR(20) NOT NULL,
            last_rotated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);
        echo "<div class='success'>✓ Tabel 'app_config' berhasil dibuat!</div>";

        // Insert data awal
        $sql = "INSERT INTO app_config (id, current_token) VALUES (1, 'START123')";
        $pdo->exec($sql);
        echo "<div class='success'>✓ Data awal berhasil dimasukkan!</div>";
    }

} catch (PDOException $e) {
    echo "<div class='error'>✗ Error membuat tabel: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

// Step 3: Test fungsi aplikasi
echo "<h2>Step 3: Test Fungsi Aplikasi</h2>";

try {
    // Test generate token
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
    echo "<div class='info'>🔑 Test generate token: <strong>" . $test_token . "</strong></div>";

    // Test update token
    $stmt = $pdo->prepare("UPDATE app_config SET current_token = :token WHERE id = 1");
    $stmt->execute([':token' => $test_token]);
    echo "<div class='success'>✓ Test update token berhasil!</div>";

    // Test select token
    $stmt = $pdo->prepare("SELECT current_token FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && $result['current_token'] === $test_token) {
        echo "<div class='success'>✓ Test select token berhasil!</div>";
    } else {
        echo "<div class='error'>✗ Test select token gagal!</div>";
    }

} catch (PDOException $e) {
    echo "<div class='error'>✗ Error testing fungsi: " . $e->getMessage() . "</div>";
}

// Step 4: Konfigurasi Cron Job
echo "<h2>Step 4: Konfigurasi Cron Job</h2>";
echo "<div class='info'>";
echo "<strong>Untuk rotasi token otomatis, setup cron job:</strong><br>";
echo "<pre>";
echo "# Buka crontab:\n";
echo "crontab -e\n\n";
echo "# Tambahkan baris ini (jalankan setiap 10 menit):\n";
echo "*/10 * * * * /usr/bin/php " . __DIR__ . "/rotate_token.php > /dev/null 2>&1";
echo "</pre>";
echo "</div>";

// Summary
echo "<h2>✅ Setup Selesai!</h2>";
echo "<div class='success'>";
echo "<strong>Database Token Gate siap digunakan!</strong><br><br>";
echo "📊 Token saat ini: <strong>" . $test_token . "</strong><br>";
echo "🔗 URL Login Siswa: <a href='index.php' target='_blank'>index.php</a><br>";
echo "⚙️  URL Admin Panel: <a href='admin.php' target='_blank'>admin.php</a><br><br>";
echo "<strong>Login Admin:</strong><br>";
echo "Username: <code>admin</code><br>";
echo "Password: <code>admin123</code>";
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='index.php' class='action-btn' style='margin: 5px;'>Halaman Utama</a>";
echo "<a href='admin.php' class='action-btn' style='margin: 5px;'>Panel Admin</a>";
echo "<a href='test_db_connection.php' class='action-btn' style='margin: 5px;'>Test Koneksi</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>