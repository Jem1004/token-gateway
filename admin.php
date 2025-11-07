<?php
require_once 'config.php';

// Session untuk login
session_start();

// Proses logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time(); // Unix timestamp
        $_SESSION['login_time_formatted'] = date(SERVER_TIME_FORMAT); // Formatted server time
    } else {
        $login_error = 'Username atau password salah!';
    }
}

// Cek apakah sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Tampilkan halaman login
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Token Gate</title>
        <link rel="stylesheet" href="style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <meta name="theme-color" content="#10b981">
    </head>
    <body>
        <div class="container">
            <div class="login-card admin-login">
                <!-- Logo Section -->
                <div class="logo-section">
                    <div class="logo-icon">
                        ⚙️
                    </div>
                </div>

                <!-- Header Section -->
                <div class="header">
                    <h1>Admin Panel</h1>
                    <p>Login untuk mengakses panel administrasi Token Gate</p>
                </div>

                <?php if (isset($login_error)): ?>
                    <div class="error-message" role="alert" aria-live="polite">
                        <strong>❌ Login Gagal</strong><br>
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="token-form" novalidate>
                    <div class="form-group">
                        <label for="username">
                            Username
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username"
                            required
                            autocomplete="username"
                            autofocus
                        >
                    </div>
                    <div class="form-group">
                        <label for="password">
                            Password
                            <span class="required">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    <button type="submit" class="submit-btn">
                        <span>🔐 Login Admin</span>
                    </button>
                </form>

                <div class="footer">
                    <p>
                        <strong>🛡️ Area Terbatas:</strong> Hanya administrator yang diizinkan
                    </p>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('.token-form');
                const usernameInput = document.getElementById('username');
                const passwordInput = document.getElementById('password');
                const submitBtn = document.querySelector('.submit-btn');

                // Auto-focus first empty field
                if (!usernameInput.value) {
                    usernameInput.focus();
                } else {
                    passwordInput.focus();
                }

                // Form validation
                form.addEventListener('submit', function(e) {
                    if (!usernameInput.value.trim() || !passwordInput.value.trim()) {
                        e.preventDefault();
                        showFormError('Username dan password harus diisi!');
                        return;
                    }

                    // Show loading state
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span>⏳ Proses Login...</span>';
                });

                function showFormError(message) {
                    const existingError = document.querySelector('.error-message');
                    if (existingError) {
                        existingError.remove();
                    }

                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.setAttribute('role', 'alert');
                    errorDiv.setAttribute('aria-live', 'polite');
                    errorDiv.innerHTML = `<strong>❌ Error</strong><br>${message}`;

                    form.parentNode.insertBefore(errorDiv, form);
                    setTimeout(() => errorDiv.remove(), 5000);
                }

                // Enter key to move between fields
                usernameInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        passwordInput.focus();
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Jika sudah login, tampilkan panel admin
try {
    // Ambil data token dari database
    $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        $db_error = true;
        $error_message = "Data token tidak ditemukan. Silakan import database.sql terlebih dahulu.";
        $current_token = 'ERROR';
        $last_rotated = 'N/A';
        $sisaDetik = 0;
    } else {
        $current_token = htmlspecialchars($result['current_token']);
        $last_rotated = $result['last_rotated'];

        // Hitung sisa detik hingga rotasi berikutnya
        $lastRotated = new DateTime($last_rotated);
        $nextRotation = clone $lastRotated;
        $nextRotation->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');

        $now = new DateTime();
        $sisaDetik = $nextRotation->getTimestamp() - $now->getTimestamp();

        if ($sisaDetik < 0) {
            $sisaDetik = 0; // Jika cron terlambat
        }

        $db_error = false;
    }

} catch (PDOException $e) {
    $db_error = true;
    $error_message = "Error database: " . $e->getMessage() . "<br><br><strong>Solusi:</strong> <a href='test_db_connection.php' target='_blank'>Test koneksi database</a>";
    $current_token = 'DB ERROR';
    $last_rotated = 'N/A';
    $sisaDetik = 0;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#10b981">
    <meta name="description" content="Panel Admin Token Gate - Manajemen token dan monitoring">
</head>
<body>
    <div class="container">
        <div class="admin-panel">
            <!-- Header Section -->
            <div class="admin-header">
                <div class="header-title">
                    <div class="logo-icon" style="width: 48px; height: 48px; font-size: 20px; margin-bottom: 8px;">
                        ⚙️
                    </div>
                    <h1>Admin Panel</h1>
                </div>
                <div class="header-actions">
                    <span class="session-time">
                        🕐 Login: <?php echo date(TIME_FORMAT_SHORT, $_SESSION['login_time']); ?> (<?php echo APP_TIMEZONE; ?>)
                    </span>
                    <a href="?logout=1" class="logout-btn">
                        🚪 Logout
                    </a>
                </div>
            </div>

            <?php if (isset($db_error) && $db_error): ?>
                <div class="error-message" role="alert" aria-live="polite">
                    <strong>⚠️ Database Error</strong><br>
                    <?php echo $error_message; ?>
                </div>
                <div style="text-align: center; margin: 24px 0;">
                    <a href="test_db_connection.php" target="_blank" class="action-btn" style="display: inline-block; text-decoration: none;">
                        🔧 Test Koneksi Database
                    </a>
                </div>
            <?php endif; ?>

            <!-- Token Display Section -->
            <div class="token-info">
                <div class="token-header">
                    <h2>🔑 Token Aktif Saat Ini</h2>
                    <div class="token-status <?php echo $db_error ? 'error' : 'active'; ?>">
                        <?php echo $db_error ? '❌ Error' : '✅ Aktif'; ?>
                    </div>
                </div>

                <div class="token-display-container">
                    <h1 id="active-token" class="<?php echo $db_error ? 'token-error' : 'token-success'; ?>">
                        <?php echo $current_token; ?>
                    </h1>
                    <?php if (!$db_error): ?>
                        <button onclick="copyToken()" class="copy-token-btn" title="Salin token">
                            📋
                        </button>
                    <?php endif; ?>
                </div>

                <div class="countdown-section">
                    <h3>⏰ Token akan berganti dalam:</h3>
                    <div class="countdown-display">
                        <h2 id="countdown-timer">--:--</h2>
                        <div class="countdown-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$db_error): ?>
                    <div class="rotation-info">
                        <div class="info-item">
                            <span class="info-label">🔄 Interval Rotasi</span>
                            <span class="info-value"><?php echo TOKEN_ROTATION_MINUTES; ?> menit</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📅 Terakhir Dirotasi</span>
                            <span class="info-value"><?php echo date(DISPLAY_TIME_FORMAT, strtotime($last_rotated)); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📅 Rotasi Berikutnya</span>
                            <span class="info-value" id="next-rotation">
                                <?php
                                $nextRot = new DateTime($last_rotated);
                                $nextRot->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');
                                echo $nextRot->format(DISPLAY_TIME_FORMAT);
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$db_error): ?>
                <!-- Admin Actions Section -->
                <div class="admin-actions-section">
                    <h3>⚡ Aksi Cepat</h3>
                    <div class="admin-actions">
                        <button onclick="rotateTokenNow()" class="action-btn rotate-btn">
                            🔄 Rotasi Token
                        </button>
                        <button onclick="copyToken()" class="action-btn copy-btn">
                            📋 Salin Token
                        </button>
                        <button onclick="refreshToken()" class="action-btn refresh-btn">
                            🔄 Refresh
                        </button>
                        <a href="test_db_connection.php" target="_blank" class="action-btn test-btn">
                            🔧 Test DB
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <div class="statistics-section">
                <h3>📊 Informasi Sistem</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🌐</div>
                        <div class="stat-info">
                            <div class="stat-label">Server Status</div>
                            <div class="stat-value">Online</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💾</div>
                        <div class="stat-info">
                            <div class="stat-label">Database</div>
                            <div class="stat-value <?php echo $db_error ? 'stat-error' : 'stat-success'; ?>">
                                <?php echo $db_error ? 'Error' : 'Connected'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-info">
                            <div class="stat-label">Uptime</div>
                            <div class="stat-value" id="uptime">Loading...</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔐</div>
                        <div class="stat-info">
                            <div class="stat-label">Security</div>
                            <div class="stat-value stat-success">Active</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification -->
            <div id="notification" class="notification"></div>
        </div>
    </div>

    <script>
        // Variabel dari PHP
        let sisaDetik = <?php echo $sisaDetik; ?>;
        const rotationMinutes = <?php echo TOKEN_ROTATION_MINUTES; ?>;
        const totalSeconds = rotationMinutes * <?php echo SECONDS_PER_MINUTE; ?>;
        const serverTimezone = '<?php echo APP_TIMEZONE; ?>';

        // Fungsi format waktu MM:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Update countdown timer dengan progress bar
        function updateCountdown() {
            const timerElement = document.getElementById('countdown-timer');
            const progressFill = document.getElementById('progress-fill');

            if (sisaDetik > 0) {
                timerElement.textContent = formatTime(sisaDetik);

                // Update progress bar
                const progress = ((totalSeconds - sisaDetik) / totalSeconds) * 100;
                progressFill.style.width = progress + '%';

                // Change color based on time remaining
                if (sisaDetik <= 60) {
                    timerElement.style.color = '#ef4444';
                    progressFill.style.background = '#ef4444';
                } else if (sisaDetik <= 180) {
                    timerElement.style.color = '#f59e0b';
                    progressFill.style.background = '#f59e0b';
                } else {
                    timerElement.style.color = '#10b981';
                    progressFill.style.background = '#10b981';
                }

                sisaDetik--;
            } else {
                timerElement.textContent = '00:00';
                progressFill.style.width = '100%';
                fetchNewToken();
                sisaDetik = totalSeconds - 1;
            }
        }

        // Fetch token baru via AJAX
        function fetchNewToken() {
            fetch('get_new_token.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Error: ' + data.error, 'error');
                    } else {
                        const tokenElement = document.getElementById('active-token');
                        tokenElement.textContent = data.token;
                        tokenElement.classList.add('success-animation');
                        setTimeout(() => tokenElement.classList.remove('success-animation'), 600);

                        showNotification('🔄 Token berhasil diperbarui!', 'success');

                        // Update next rotation time
                        const nextRotationElement = document.getElementById('next-rotation');
                        const rotatedTime = new Date(data.last_rotated);
                        rotatedTime.setMinutes(rotatedTime.getMinutes() + rotationMinutes);
                        nextRotationElement.textContent = rotatedTime.toLocaleString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // Update rotation info
                        const lastRotatedElement = document.querySelector('.info-item:nth-child(2) .info-value');
                        lastRotatedElement.textContent = new Date(data.last_rotated).toLocaleString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('❌ Gagal mengambil token baru', 'error');
                });
        }

        // Rotasi token manual
        function rotateTokenNow() {
            if (confirm('🔄 Apakah Anda yakin ingin merotasi token sekarang?')) {
                showNotification('⏳ Sedang merotasi token...', 'info');

                // Disable button to prevent multiple clicks
                const rotateBtn = document.querySelector('.rotate-btn');
                if (rotateBtn) {
                    rotateBtn.disabled = true;
                    rotateBtn.textContent = '⏳ Proses...';
                }

                fetch('rotate_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update token directly from response
                        const tokenElement = document.getElementById('active-token');
                        const oldToken = tokenElement.textContent;
                        tokenElement.textContent = data.token;
                        tokenElement.classList.add('success-animation');
                        setTimeout(() => tokenElement.classList.remove('success-animation'), 600);

                        // Update rotation info
                        const now = new Date();
                        const nextRot = new Date(now.getTime() + (rotationMinutes * 60 * 1000));

                        const lastRotatedElement = document.querySelector('.info-item:nth-child(2) .info-value');
                        lastRotatedElement.textContent = now.toLocaleString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const nextRotationElement = document.getElementById('next-rotation');
                        nextRotationElement.textContent = nextRot.toLocaleString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // Reset countdown
                        sisaDetik = totalSeconds - 1;

                        // Show detailed success message
                        console.log('Token rotation successful:', {
                            oldToken: oldToken,
                            newToken: data.token,
                            timestamp: data.timestamp,
                            last_rotated: data.last_rotated
                        });

                        showNotification('✅ Token berhasil dirotasi: ' + data.token, 'success');

                        // Verify the token actually changed
                        if (oldToken !== data.token) {
                            console.log('✅ Token verified: Changed from ' + oldToken + ' to ' + data.token);
                        } else {
                            console.warn('⚠️ Token appears unchanged, this might indicate a database issue');
                            showNotification('⚠️ Token dirotasi tapi nilai sama, periksa database', 'warning');
                        }

                    } else {
                        // Enhanced error reporting
                        console.error('Token rotation failed:', data);
                        showNotification('❌ Gagal merotasi token: ' + (data.error || 'Unknown error'), 'error');

                        // Try to refresh token from server anyway
                        fetchNewToken();
                    }
                })
                .catch(error => {
                    console.error('Network error:', error);
                    showNotification('❌ Gagal merotasi token: Error jaringan', 'error');
                })
                .finally(() => {
                    // Re-enable button
                    if (rotateBtn) {
                        rotateBtn.disabled = false;
                        rotateBtn.textContent = '🔄 Rotasi Token';
                    }
                });
            }
        }

        // Copy token to clipboard
        function copyToken() {
            const token = document.getElementById('active-token').textContent;

            if (token === 'ERROR' || token === 'DB ERROR') {
                showNotification('❌ Tidak ada token valid untuk disalin', 'error');
                return;
            }

            navigator.clipboard.writeText(token).then(() => {
                showNotification('📋 Token berhasil disalin ke clipboard!', 'success');

                // Visual feedback
                const copyBtn = document.querySelector('.copy-token-btn');
                if (copyBtn) {
                    copyBtn.textContent = '✅';
                    setTimeout(() => copyBtn.textContent = '📋', 2000);
                }
            }).catch(err => {
                console.error('Error copying token:', err);
                showNotification('❌ Gagal menyalin token', 'error');
            });
        }

        // Refresh token
        function refreshToken() {
            fetchNewToken();
            showNotification('🔄 Data token diperbarui', 'info');
        }

        // Show notification
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.innerHTML = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Update uptime
        function updateUptime() {
            const uptimeElement = document.getElementById('uptime');
            const loginTime = <?php echo $_SESSION['login_time']; ?> * 1000;
            const now = Date.now();
            const uptime = Math.floor((now - loginTime) / 1000);

            const hours = Math.floor(uptime / 3600);
            const minutes = Math.floor((uptime % 3600) / 60);

            if (hours > 0) {
                uptimeElement.textContent = `${hours}j ${minutes}m`;
            } else {
                uptimeElement.textContent = `${minutes}m`;
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Start countdown
            setInterval(updateCountdown, 1000);
            updateCountdown();

            // Update uptime every 30 seconds
            updateUptime();
            setInterval(updateUptime, 30000);

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + R: Rotate token
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    rotateTokenNow();
                }
                // Ctrl/Cmd + C: Copy token
                if ((e.ctrlKey || e.metaKey) && e.key === 'c' && !window.getSelection().toString()) {
                    e.preventDefault();
                    copyToken();
                }
                // Ctrl/Cmd + F: Refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !window.getSelection().toString()) {
                    e.preventDefault();
                    refreshToken();
                }
            });

            // Auto-refresh token setiap 30 seconds
            setInterval(() => {
                if (sisaDetik > 30) { // Only if not near rotation
                    fetch('get_new_token.php')
                        .then(response => response.json())
                        .then(data => {
                            if (!data.error && data.token !== document.getElementById('active-token').textContent) {
                                const tokenElement = document.getElementById('active-token');
                                tokenElement.textContent = data.token;
                                showNotification('🔄 Token diperbarui otomatis', 'info');
                            }
                        })
                        .catch(console.error);
                }
            }, 30000);
        });

        // Add custom styles
        const customStyles = document.createElement('style');
        customStyles.textContent = `
            .header-title {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
            }

            .session-time {
                font-size: 12px;
                color: #6b7280;
                font-weight: 500;
            }

            .token-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 16px;
            }

            .token-status {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .token-status.active {
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                color: #065f46;
            }

            .token-status.error {
                background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                color: #991b1b;
            }

            .token-display-container {
                position: relative;
                display: inline-block;
            }

            .copy-token-btn {
                position: absolute;
                top: 8px;
                right: 8px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                border: none;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                transition: all 0.3s ease;
                box-shadow: var(--shadow-sm);
            }

            .copy-token-btn:hover {
                transform: scale(1.1);
                box-shadow: var(--shadow-md);
            }

            .countdown-section {
                margin: 24px 0;
            }

            .countdown-display {
                position: relative;
            }

            .countdown-progress {
                margin-top: 12px;
            }

            .progress-bar {
                width: 100%;
                height: 8px;
                background: #e5e7eb;
                border-radius: 4px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #10b981 0%, #059669 100%);
                transition: width 1s linear, background 0.3s ease;
            }

            .info-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #f3f4f6;
            }

            .info-item:last-child {
                border-bottom: none;
            }

            .info-label {
                font-size: 14px;
                color: #6b7280;
                font-weight: 500;
            }

            .info-value {
                font-size: 14px;
                color: #374151;
                font-weight: 600;
            }

            .admin-actions-section {
                margin: 40px 0;
                text-align: center;
            }

            .admin-actions-section h3 {
                color: #374151;
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 20px;
            }

            .action-btn.rotate-btn { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
            .action-btn.refresh-btn { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
            .action-btn.test-btn { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

            .action-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
                box-shadow: var(--shadow-sm) !important;
            }

            .action-btn:disabled:hover {
                transform: none !important;
                box-shadow: var(--shadow-sm) !important;
            }

            .statistics-section {
                margin-top: 40px;
            }

            .statistics-section h3 {
                color: #374151;
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 20px;
                text-align: center;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 16px;
            }

            .stat-card {
                background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: all 0.3s ease;
            }

            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }

            .stat-icon {
                font-size: 24px;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                border-radius: 8px;
            }

            .stat-info {
                flex: 1;
            }

            .stat-label {
                font-size: 11px;
                color: #6b7280;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                line-height: 1.2;
            }

            .stat-value {
                font-size: 14px;
                color: #374151;
                font-weight: 600;
                margin-top: 2px;
            }

            .stat-success { color: #10b981 !important; }
            .stat-error { color: #ef4444 !important; }

            .token-success { color: #10b981 !important; }
            .token-error { color: #ef4444 !important; }

            .notification.warning {
                background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
                color: #d97706;
                border: 1px solid #fbbf24;
            }

            @media (max-width: 640px) {
                .header-actions {
                    align-items: center;
                    gap: 6px;
                }

                .session-time {
                    font-size: 11px;
                }

                .token-header {
                    flex-direction: column;
                    align-items: center;
                    gap: 12px;
                }

                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                }

                .stat-card {
                    padding: 12px;
                }

                .stat-icon {
                    width: 32px;
                    height: 32px;
                    font-size: 20px;
                }

                .info-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 4px;
                    padding: 12px 0;
                }

                .copy-token-btn {
                    width: 32px;
                    height: 32px;
                    font-size: 14px;
                    top: 6px;
                    right: 6px;
                }
            }
        `;
        document.head.appendChild(customStyles);
    </script>
</body>
</html>