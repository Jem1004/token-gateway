<?php
/**
 * Token Gate Application - Secret Admin Panel
 *
 * This script provides an administrative interface for managing access tokens.
 * This is a hidden/secret version of the admin panel for additional security.
 * Features include:
 * - Session-based authentication with hardcoded credentials
 * - Display current active token
 * - Manual token rotation capability
 * - Logout functionality
 * - Hidden access for security
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep display_errors off for security
ini_set('log_errors', 1);

// Start session for authentication management
if (session_status() === PHP_SESSION_NONE) {
    if (!session_start()) {
        error_log("Failed to start session in admin-rahasia.php");
        die("Session error: Unable to start session");
    }
}

// Include configuration file
try {
    if (!file_exists('config.php')) {
        throw new Exception("config.php file not found");
    }
    require_once 'config.php';
} catch (Exception $e) {
    error_log("Configuration error in admin-rahasia.php: " . $e->getMessage());
    die("Configuration error: System configuration issue detected");
}

// Check if required functions exist
if (!function_exists('getDbConnection')) {
    error_log("getDbConnection function not available in admin-rahasia.php");
    die("System error: Database function not available");
}

// Hardcoded admin credentials (as per requirements)
// In production, these should be hashed and stored securely
define('SECRET_ADMIN_USERNAME', 'admin');
define('SECRET_ADMIN_PASSWORD', 'indonesia2025');

// Handle logout action
if (isset($_GET['action']) && htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') === 'logout') {
    // Destroy session and redirect to login
    session_unset();
    session_destroy();
    header("Location: admin-rahasia.php");
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
        if ($username === SECRET_ADMIN_USERNAME && $password === SECRET_ADMIN_PASSWORD) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Set session variable on successful authentication
            $_SESSION['secret_admin_authenticated'] = true;
            $_SESSION['secret_admin_username'] = $username;

            // Redirect to admin panel to prevent form resubmission
            header("Location: admin-rahasia.php");
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
    if (!isset($_SESSION['secret_admin_authenticated']) || $_SESSION['secret_admin_authenticated'] !== true) {
        header("Location: admin-rahasia.php");
        exit();
    }

    // Execute token rotation logic
    try {
        // Include rotate_token.php without executing it directly
        if (!file_exists('rotate_token.php')) {
            throw new Exception("rotate_token.php file not found");
        }
        require_once 'rotate_token.php';

        // Check if rotateToken function exists
        if (!function_exists('rotateToken')) {
            throw new Exception("Token rotation function not available");
        }

        // Verify database connection first
        if (!function_exists('getDbConnection')) {
            throw new Exception("Database connection function not available");
        }

        $conn = getDbConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        $conn->close();

        $result = rotateToken('manual', $_SESSION['secret_admin_username']);

        // Store message in session for display after redirect
        if ($result['success']) {
            $nextRotation = isset($result['next_rotation']) ?
                date('d M Y H:i:s', strtotime($result['next_rotation'])) :
                'Tidak diketahui';

        $_SESSION['secret_rotation_message'] = sprintf(
            "Token berhasil diperbarui. Token baru: <strong>%s</strong><br>Rotasi berikutnya: <strong>%s</strong>",
            htmlspecialchars($result['token'], ENT_QUOTES, 'UTF-8'),
            $nextRotation
        );
    } else {
        // Don't expose detailed error messages to prevent information disclosure
        $_SESSION['secret_rotation_error'] = "Gagal memperbarui token. Silakan coba lagi.";
        // Log detailed error for debugging
        error_log("Token rotation failed: " . ($result['message'] ?? 'Unknown error'));
    }

    // Redirect to prevent form resubmission
    header("Location: admin-rahasia.php");
    exit();
}

// Check if user is authenticated
$is_authenticated = isset($_SESSION['secret_admin_authenticated']) && $_SESSION['secret_admin_authenticated'] === true;

// Retrieve session messages and clear them
$rotation_message = null;
$rotation_error = null;
if (isset($_SESSION['secret_rotation_message'])) {
    $rotation_message = $_SESSION['secret_rotation_message'];
    unset($_SESSION['secret_rotation_message']);
}
if (isset($_SESSION['secret_rotation_error'])) {
    $rotation_error = $_SESSION['secret_rotation_error'];
    unset($_SESSION['secret_rotation_error']);
}

// If authenticated, fetch current token from database
$current_token = null;
if ($is_authenticated) {
    try {
        // Check if function exists before calling it
        if (!function_exists('getDbConnection')) {
            throw new Exception("Database connection function not available");
        }

        $conn = getDbConnection();
        if (!$conn) {
            throw new Exception("Failed to connect to database");
        }

        $stmt = $conn->prepare("SELECT current_token FROM active_token WHERE id = 1");

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $current_token = $row['current_token'];
            }

            $stmt->close();
        } else {
            throw new Exception("Failed to prepare database statement");
        }

        $conn->close();
    } catch (Exception $e) {
        error_log("Error fetching token in secret admin panel: " . $e->getMessage());
        $db_error = "Gagal mengambil token dari database. Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Secret Admin Panel - Token Gate</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="token_countdown.js" defer></script>
    <style>
        .secret-header {
            background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
            color: white;
            padding: 1rem;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 8px;
        }
        .secret-badge {
            display: inline-block;
            background: var(--red-500);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .access-warning {
            background: #fef2f2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .admin-header h1 {
            color: var(--gray-800);
        }
    </style>
</head>
<body>
    <div class="container container--admin">
        <div class="secret-header">
            <div class="secret-badge">🔒 SECRET ACCESS</div>
            <h1>Secret Admin Panel</h1>
            <p style="margin: 0; opacity: 0.8;">Hidden Administration Interface</p>
        </div>

        <?php if (!$is_authenticated): ?>
            <!-- Login Form -->
            <div class="access-warning">
                <strong>⚠️ Restricted Access</strong><br>
                This is a secret admin panel. Unauthorized access will be logged and reported.
            </div>

            <div class="text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem; color: var(--gray-600);">🔐</div>
                <h1>Secret Admin Login</h1>
                <p class="text-muted">Silakan login untuk mengakses panel administrasi rahasia</p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="message message-error">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin-rahasia.php">
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

                <button type="submit" class="btn" style="background: var(--gray-700);">
                    <span style="margin-right: 0.5rem;">🚀</span>
                    Login ke Secret Dashboard
                </button>
            </form>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="admin-header">
                <div>
                    <h1>🛡️ Secret Admin Dashboard</h1>
                    <div class="user-info">
                        Selamat datang kembali, <strong><?php echo htmlspecialchars($_SESSION['secret_admin_username']); ?></strong>!
                        <span class="secret-badge" style="margin-left: 1rem;">SECRET ACCESS</span>
                    </div>
                </div>
                <a href="admin-rahasia.php?action=logout" class="logout-btn">Keluar</a>
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
                <form method="POST" action="admin-rahasia.php">
                    <input type="hidden" name="action" value="rotate_token">
                    <button type="submit" class="btn btn-rotate btn--lg" style="background: var(--gray-700);">
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

            <!-- Security Info Section -->
            <div class="admin-section" style="background: #f9fafb; border: 1px solid #e5e7eb;">
                <h2>
                    <span style="margin-right: 0.5rem;">🔒</span>
                    Security Information
                </h2>
                <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <p style="margin: 0 0 0.5rem 0;"><strong>Panel Type:</strong> Secret Admin Panel</p>
                    <p style="margin: 0 0 0.5rem 0;"><strong>Access Level:</strong> Full Administrative Access</p>
                    <p style="margin: 0 0 0.5rem 0;"><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    <p style="margin: 0 0 0.5rem 0;"><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p style="margin: 0;"><strong>Access IP:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>