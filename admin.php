<?php
/**
 * Token Gate Application - Admin Panel
 * 
 * This script provides an administrative interface for managing access tokens.
 * Features include:
 * - Session-based authentication with hardcoded credentials
 * - Display current active token
 * - Manual token rotation capability
 * - Logout functionality
 */

// Start session for authentication management
session_start();

// Include configuration file
require_once 'config.php';

// Hardcoded admin credentials (as per requirements)
// In production, these should be hashed and stored securely
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Handle logout action
if (isset($_GET['action']) && htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') === 'logout') {
    // Destroy session and redirect to login
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // Sanitize input
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = trim($_POST['password'] ?? '');
    
    // Validate that username and password are not empty
    if (empty($username) || empty($password)) {
        $login_error = "Username dan password harus diisi";
    } else {
        // Verify credentials against hardcoded values
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            
            // Set session variable on successful authentication
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $username;
            
            // Redirect to admin panel to prevent form resubmission
            header("Location: admin.php");
            exit();
        } else {
            // Set error message for invalid credentials (generic message to prevent user enumeration)
            $login_error = "Username atau password salah";
        }
    }
}

// Handle manual token rotation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && htmlspecialchars($_POST['action'], ENT_QUOTES, 'UTF-8') === 'rotate_token') {
    // Check authentication before allowing token rotation
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        header("Location: admin.php");
        exit();
    }
    
    // Execute token rotation logic
    require_once 'rotate_token.php';
    $result = rotateToken();
    
    // Store message in session for display after redirect
    if ($result['success']) {
        $_SESSION['rotation_message'] = "Token berhasil diperbarui. Token baru: " . htmlspecialchars($result['token'], ENT_QUOTES, 'UTF-8');
    } else {
        // Don't expose detailed error messages to prevent information disclosure
        $_SESSION['rotation_error'] = "Gagal memperbarui token. Silakan coba lagi.";
        // Log detailed error for debugging
        error_log("Token rotation failed: " . $result['message']);
    }
    
    // Redirect to prevent form resubmission
    header("Location: admin.php");
    exit();
}

// Check if user is authenticated
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// Retrieve session messages and clear them
$rotation_message = null;
$rotation_error = null;
if (isset($_SESSION['rotation_message'])) {
    $rotation_message = $_SESSION['rotation_message'];
    unset($_SESSION['rotation_message']);
}
if (isset($_SESSION['rotation_error'])) {
    $rotation_error = $_SESSION['rotation_error'];
    unset($_SESSION['rotation_error']);
}

// If authenticated, fetch current token from database
$current_token = null;
if ($is_authenticated) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT current_token FROM active_token WHERE id = 1");
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $current_token = $row['current_token'];
            }
            
            $stmt->close();
        }
        
        $conn->close();
    } catch (Exception $e) {
        error_log("Error fetching token in admin panel: " . $e->getMessage());
        $db_error = "Gagal mengambil token dari database";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Token Gate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (!$is_authenticated): ?>
            <!-- Login Form -->
            <h1>Admin Panel</h1>
            <p>Silakan login untuk mengakses panel administrasi</p>
            
            <?php if (isset($login_error)): ?>
                <div class="error-message">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>
                
                <button type="submit">Login</button>
            </form>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="admin-header">
                <h1>Admin Panel - Token Management</h1>
                <a href="admin.php?action=logout" class="logout-btn">Logout</a>
            </div>
            
            <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            
            <?php if ($rotation_message): ?>
                <div class="success-message">
                    <?php echo $rotation_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($rotation_error): ?>
                <div class="error-message">
                    <?php echo $rotation_error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($db_error)): ?>
                <div class="error-message">
                    <?php echo $db_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="token-display">
                <h2>Token Aktif Saat Ini</h2>
                <?php if ($current_token): ?>
                    <div class="current-token">
                        <?php echo htmlspecialchars($current_token); ?>
                    </div>
                <?php else: ?>
                    <p class="error-message">Token tidak ditemukan</p>
                <?php endif; ?>
            </div>
            
            <div class="token-actions">
                <h2>Manajemen Token</h2>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="rotate_token">
                    <button type="submit" class="rotate-btn">Buat Token Baru (Manual)</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
