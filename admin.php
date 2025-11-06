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
define('ADMIN_PASSWORD', 'indonesia2025');

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
    try {
        require_once 'rotate_token.php';

        // Verify database connection first
        $conn = getDbConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        $conn->close();

        $result = rotateToken('manual', $_SESSION['admin_username']);

        // Store message in session for display after redirect
        if ($result['success']) {
        $nextRotation = isset($result['next_rotation']) ?
            date('d M Y H:i:s', strtotime($result['next_rotation'])) :
            'Tidak diketahui';

        $_SESSION['rotation_message'] = sprintf(
            "Token berhasil diperbarui. Token baru: <strong>%s</strong><br>Rotasi berikutnya: <strong>%s</strong>",
            htmlspecialchars($result['token'], ENT_QUOTES, 'UTF-8'),
            $nextRotation
        );
    } else {
        // Don't expose detailed error messages to prevent information disclosure
        $_SESSION['rotation_error'] = "Gagal memperbarui token. Silakan coba lagi.";
        // Log detailed error for debugging
        error_log("Token rotation failed: " . ($result['message'] ?? 'Unknown error'));
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="token_countdown.js" defer></script>
</head>
<body>
    <div class="container container--admin">
        <?php if (!$is_authenticated): ?>
            <!-- Login Form -->
            <div class="text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem; color: var(--green-600);">🔐</div>
                <h1>Admin Panel</h1>
                <p class="text-muted">Silakan login untuk mengakses panel administrasi</p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="message message-error">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label for="username">
                        <span style="margin-right: 0.5rem;">👤</span>
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        placeholder="Masukkan username admin"
                        autocomplete="username"
                        maxlength="50"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <span style="margin-right: 0.5rem;">🔒</span>
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Masukkan password admin"
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn">
                    <span style="margin-right: 0.5rem;">🚀</span>
                    Login ke Dashboard
                </button>
            </form>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="admin-header">
                <div>
                    <h1>🛡️ Admin Dashboard</h1>
                    <div class="user-info">
                        Selamat datang kembali, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>!
                    </div>
                </div>
                <a href="admin.php?action=logout" class="logout-btn">Keluar</a>
            </div>

            <!-- Dashboard Statistics -->
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-value">🔑</div>
                    <div class="stat-label">Token Management</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">✅</div>
                    <div class="stat-label">System Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">📊</div>
                    <div class="stat-label">Real-time Status</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">🔒</div>
                    <div class="stat-label">Security Enabled</div>
                </div>
            </div>

            <?php if ($rotation_message): ?>
                <div class="message message-success success-animation">
                    <?php echo $rotation_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($rotation_error): ?>
                <div class="message message-error">
                    <?php echo $rotation_error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($db_error)): ?>
                <div class="message message-error">
                    <?php echo $db_error; ?>
                </div>
            <?php endif; ?>

            <!-- Countdown Timer Section -->
            <div class="countdown-container" id="countdownContainer">
                <div class="countdown-header">
                    <div class="countdown-title">
                        <span style="font-size: 1.5rem;">⏱️</span>
                        Countdown Token Rotation
                    </div>
                    <div class="countdown-status countdown-status--active" id="countdownStatus">
                        Aktif
                    </div>
                </div>

                <div class="countdown-display">
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-days">00</div>
                        <div class="countdown-label">Hari</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-hours">00</div>
                        <div class="countdown-label">Jam</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-minutes">00</div>
                        <div class="countdown-label">Menit</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-value" id="countdown-seconds">00</div>
                        <div class="countdown-label">Detik</div>
                    </div>
                </div>

                <div class="countdown-progress">
                    <div class="countdown-progress-bar" id="countdownProgress" style="width: 100%;"></div>
                </div>

                <div class="countdown-info">
                    <div class="countdown-info-item">
                        <span class="countdown-info-label">🔄 Rotasi Terakhir:</span>
                        <span class="countdown-info-value" id="lastRotationTime">Memuat...</span>
                    </div>
                    <div class="countdown-info-item">
                        <span class="countdown-info-label">⏰ Rotasi Berikutnya:</span>
                        <span class="countdown-info-value" id="nextRotationTime">Memuat...</span>
                    </div>
                    <div class="countdown-info-item">
                        <span class="countdown-info-label">📊 Interval Rotasi:</span>
                        <span class="countdown-info-value" id="rotationInterval">Memuat...</span>
                    </div>
                    <div class="countdown-info-item">
                        <span class="countdown-info-label">🤖 Auto-Rotation:</span>
                        <span class="countdown-info-value" id="autoRotationStatus">Memuat...</span>
                    </div>
                </div>

                <div class="message message-error" id="countdownError" style="display: none;"></div>

                <div style="text-align: center; margin-top: 1rem;">
                    <button type="button" id="refreshTokenBtn" class="btn btn--secondary btn--sm">
                        🔄 Refresh Data
                    </button>
                </div>
            </div>

            <div class="admin-section token-display">
                <h2>
                    <span style="margin-right: 0.5rem;">🔑</span>
                    Token Aktif Saat Ini
                </h2>
                <?php if ($current_token): ?>
                    <div class="current-token" id="currentToken">
                        <?php echo htmlspecialchars($current_token); ?>
                    </div>
                    <p class="text-muted" style="margin-top: 1rem; font-size: 0.875rem;">
                        ⚠️ Token ini sedang aktif dan dapat digunakan oleh siswa untuk mengakses ujian.
                    </p>
                <?php else: ?>
                    <div class="message message-error">
                        Token tidak ditemukan dalam database. Silakan buat token baru.
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-section token-actions">
                <h2>
                    <span style="margin-right: 0.5rem;">⚙️</span>
                    Manajemen Token
                </h2>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="rotate_token">
                    <button type="submit" class="btn btn-rotate btn--lg">
                        Buat Token Baru (Manual)
                    </button>
                </form>
                <p class="text-muted" style="margin-top: 1rem; font-size: 0.875rem;">
                    💡 Membuat token baru akan secara otomatis menonaktifkan token sebelumnya.
                </p>
            </div>

            <div class="admin-section">
                <h2>
                    <span style="margin-right: 0.5rem;">📋</span>
                    Quick Actions
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <button class="btn btn--secondary btn--sm" disabled>
                        📈 View Statistics
                    </button>
                    <button class="btn btn--secondary btn--sm" disabled>
                        🕐 View History
                    </button>
                    <button class="btn btn--secondary btn--sm" disabled>
                        ⚙️ Settings
                    </button>
                </div>
                <p class="text-muted" style="margin-top: 1rem; font-size: 0.875rem;">
                    Fitur tambahan akan segera hadir untuk monitoring dan analisis yang lebih lengkap.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
