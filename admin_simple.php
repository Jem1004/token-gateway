<?php
/**
 * Simplified Admin Panel for Debugging
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
session_start();

// Include configuration
require_once 'config.php';

// Admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'indonesia2025');

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin_simple.php");
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin_simple.php");
        exit();
    } else {
        $login_error = "Username atau password salah";
    }
}

// Handle token rotation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rotate_token') {
    if (!isset($_SESSION['admin_authenticated'])) {
        header("Location: admin_simple.php");
        exit();
    }
    
    require_once 'rotate_token.php';
    $result = rotateToken('manual', $_SESSION['admin_username']);
    
    if ($result['success']) {
        $_SESSION['rotation_message'] = "Token berhasil diperbarui. Token baru: " . $result['token'];
    } else {
        $_SESSION['rotation_error'] = "Gagal memperbarui token: " . $result['message'];
    }
    
    header("Location: admin_simple.php");
    exit();
}

$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// Get messages
$rotation_message = $_SESSION['rotation_message'] ?? null;
$rotation_error = $_SESSION['rotation_error'] ?? null;
unset($_SESSION['rotation_message'], $_SESSION['rotation_error']);

// Get current token
$current_token = null;
if ($is_authenticated) {
    try {
        $conn = getDbConnection();
        $result = $conn->query("SELECT current_token FROM active_token WHERE id = 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_token = $row['current_token'];
        }
        $conn->close();
    } catch (Exception $e) {
        $db_error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel (Simple)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (!$is_authenticated): ?>
            <h1>Admin Login</h1>
            
            <?php if (isset($login_error)): ?>
                <div style="background: #fee; border: 1px solid #fcc; padding: 10px; margin: 10px 0; border-radius: 4px;">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div style="margin-bottom: 15px;">
                    <label>Username:</label><br>
                    <input type="text" name="username" required style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Password:</label><br>
                    <input type="password" name="password" required style="width: 100%; padding: 8px;">
                </div>
                
                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Login
                </button>
            </form>
            
        <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Admin Dashboard</h1>
                <a href="?action=logout" style="padding: 8px 16px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;">
                    Logout
                </a>
            </div>
            
            <?php if ($rotation_message): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px; color: #155724;">
                    <?php echo htmlspecialchars($rotation_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($rotation_error): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; color: #721c24;">
                    <?php echo htmlspecialchars($rotation_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($db_error)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px; color: #856404;">
                    <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Token Aktif Saat Ini</h2>
                <?php if ($current_token): ?>
                    <div style="font-size: 2rem; font-weight: bold; color: #28a745; text-align: center; padding: 20px; background: white; border-radius: 4px; margin: 10px 0;">
                        <?php echo htmlspecialchars($current_token); ?>
                    </div>
                <?php else: ?>
                    <p style="color: #dc3545;">Token tidak ditemukan</p>
                <?php endif; ?>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h2>Manajemen Token</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="rotate_token">
                    <button type="submit" style="padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                        🔄 Buat Token Baru
                    </button>
                </form>
                <p style="margin-top: 10px; color: #6c757d; font-size: 14px;">
                    Membuat token baru akan menonaktifkan token sebelumnya.
                </p>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">
                <strong>Info:</strong> Ini adalah versi sederhana admin panel untuk debugging.
                <br>Jika ini berfungsi, masalah ada di versi lengkap admin.php
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>
