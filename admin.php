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
        $_SESSION['login_time'] = time();
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
    </head>
    <body>
        <div class="container">
            <div class="login-card admin-login">
                <div class="header">
                    <h1>Admin Panel</h1>
                    <p>Login untuk mengakses panel admin</p>
                </div>

                <?php if (isset($login_error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="token-form">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn">
                        Login
                    </button>
                </form>
            </div>
        </div>
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
</head>
<body>
    <div class="container">
        <div class="admin-panel">
            <div class="admin-header">
                <h1>Admin Panel - Token Gate</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>

            <?php if (isset($db_error) && $db_error): ?>
                <div class="error-message" style="margin-bottom: 20px;">
                    <?php echo $error_message; ?>
                </div>
                <div style="text-align: center; margin: 20px 0;">
                    <a href="test_db_connection.php" target="_blank" class="action-btn" style="display: inline-block; text-decoration: none;">
                        Test Koneksi Database
                    </a>
                </div>
            <?php endif; ?>

            <div class="token-info">
                <h2>Token Aktif Saat Ini:</h2>
                <h1 id="active-token"><?php echo $current_token; ?></h1>

                <h3>Token akan berganti dalam:</h3>
                <h2 id="countdown-timer">--:--</h2>

                <div class="rotation-info">
                    <p><strong>Interval Rotasi:</strong> <?php echo TOKEN_ROTATION_MINUTES; ?> menit</p>
                    <p><strong>Terakhir Dirotasi:</strong> <?php echo date('d-m-Y H:i:s', strtotime($last_rotated)); ?></p>
                </div>
            </div>

            <div class="admin-actions">
                <button onclick="rotateTokenNow()" class="action-btn">Rotasi Token Sekarang</button>
                <button onclick="copyToken()" class="action-btn copy-btn">Salin Token</button>
            </div>

            <div id="notification" class="notification"></div>
        </div>
    </div>

    <script>
        // Variabel dari PHP
        let sisaDetik = <?php echo $sisaDetik; ?>;
        const rotationMinutes = <?php echo TOKEN_ROTATION_MINUTES; ?>;

        // Fungsi format waktu MM:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Update countdown timer
        function updateCountdown() {
            const timerElement = document.getElementById('countdown-timer');

            if (sisaDetik > 0) {
                timerElement.textContent = formatTime(sisaDetik);
                sisaDetik--;
            } else {
                timerElement.textContent = '00:00';
                fetchNewToken();
                // Reset timer ke periode berikutnya
                sisaDetik = (rotationMinutes * 60) - 1;
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
                        showNotification('Token berhasil diperbarui!', 'success');

                        // Update info waktu rotasi terakhir
                        const lastRotatedElement = document.querySelector('.rotation-info p:last-child');
                        const rotatedTime = new Date(data.last_rotated);
                        lastRotatedElement.innerHTML = `<strong>Terakhir Dirotasi:</strong> ${rotatedTime.toLocaleString('id-ID')}`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Gagal mengambil token baru', 'error');
                });
        }

        // Rotasi token manual
        function rotateTokenNow() {
            if (confirm('Apakah Anda yakin ingin merotasi token sekarang?')) {
                // Panggil rotate_token.php via AJAX
                fetch('rotate_token.php')
                    .then(response => response.text())
                    .then(() => {
                        fetchNewToken();
                        // Reset timer
                        sisaDetik = (rotationMinutes * 60) - 1;
                        showNotification('Token berhasil dirotasi manual!', 'success');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Gagal merotasi token', 'error');
                    });
            }
        }

        // Copy token to clipboard
        function copyToken() {
            const token = document.getElementById('active-token').textContent;
            navigator.clipboard.writeText(token).then(() => {
                showNotification('Token berhasil disalin!', 'success');
            }).catch(err => {
                console.error('Error copying token:', err);
                showNotification('Gagal menyalin token', 'error');
            });
        }

        // Show notification
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Start countdown
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
    </script>
</body>
</html>