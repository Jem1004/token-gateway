<?php
/**
 * Simple Test File untuk Admin Rahasia
 * Tidak menggunakan shell_exec() yang diblokir server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Simple Admin Rahasia Test</h1>";

// Test 1: File check
echo "<h2>1. File Status</h2>";
if (file_exists('admin-rahasia.php')) {
    echo "✅ admin-rahasia.php ditemukan<br>";
    echo "Ukuran file: " . filesize('admin-rahasia.php') . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime('admin-rahasia.php')) . "<br>";
} else {
    echo "❌ admin-rahasia.php tidak ditemukan<br>";
}

// Test 2: Permission check
echo "<h2>2. File Permission</h2>";
clearstatcache();
$perms = fileperms('admin-rahasia.php');
echo "Permission: " . substr(sprintf('%o', fileperms('admin-rahasia.php')), -4) . "<br>";

if (is_readable('admin-rahasia.php')) {
    echo "✅ File dapat dibaca<br>";
} else {
    echo "❌ File tidak dapat dibaca<br>";
}

// Test 3: Session test
echo "<h2>3. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    if (session_start()) {
        echo "✅ Session dimulai<br>";
    } else {
        echo "❌ Session gagal dimulai<br>";
    }
} else {
    echo "✅ Session sudah aktif<br>";
}

// Test 4: Config loading
echo "<h2>4. Config Loading</h2>";
if (file_exists('config.php')) {
    try {
        require_once 'config.php';
        echo "✅ config.php berhasil di-load<br>";
    } catch (ParseError $e) {
        echo "❌ Parse error di config.php: " . htmlspecialchars($e->getMessage()) . "<br>";
    } catch (Exception $e) {
        echo "❌ Error di config.php: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "❌ config.php tidak ditemukan<br>";
}

// Test 5: Function check
echo "<h2>5. Function Availability</h2>";
if (function_exists('getDbConnection')) {
    echo "✅ getDbConnection function tersedia<br>";
} else {
    echo "❌ getDbConnection function tidak tersedia<br>";
}

// Test 6: Direct access test
echo "<h2>6. Direct Access Test</h2>";
echo "Coba akses langsung: <a href='admin-rahasia.php' target='_blank'>🔐 Buka Admin Rahasia</a><br>";

echo "<h2>🎯 Quick Access</h2>";
echo "<p style='background: #f0f9ff; padding: 1rem; border-radius: 6px; border-left: 4px solid #0ea5e9;'>";
echo "<strong>Login Credentials:</strong><br>";
echo "🔑 Username: <code>admin</code><br>";
echo "🔒 Password: <code>indonesia2025</code><br>";
echo "</p>";

echo "<h2>📝 Next Steps</h2>";
echo "<ol>";
echo "<li>Klik link 'Buka Admin Rahasia' di atas</li>";
echo "<li>Login dengan credentials yang tersedia</li>";
echo "<li>Periksa apakah dashboard muncul dengan benar</li>";
echo "<li>Test fitur token rotation</li>";
echo "</ol>";

echo "<div style='margin-top: 2rem; padding: 1rem; background: #fef2f2; border-radius: 6px; border-left: 4px solid #ef4444;'>";
echo "<strong>⚠️ Catatan:</strong> Jika ada error saat akses admin-rahasia.php, ";
echo "periksa error log server atau coba akses langsung tanpa melalui test file ini.";
echo "</div>";
?>