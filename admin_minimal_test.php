<?php
/**
 * Minimal Admin Test untuk debugging
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Minimal Admin Test</h1>";

// Test basic functionality
echo "<h2>Basic Tests</h2>";

// Test session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "✅ Session OK<br>";

// Test config
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ Config loaded<br>";
} else {
    echo "❌ Config not found<br>";
}

// Test database
if (function_exists('getDbConnection')) {
    try {
        $conn = getDbConnection();
        if ($conn) {
            echo "✅ Database connected<br>";

            // Get current token
            $stmt = $conn->prepare("SELECT current_token FROM active_token WHERE id = 1");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo "✅ Current token: " . htmlspecialchars($row['current_token']) . "<br>";
                } else {
                    echo "❌ No token found<br>";
                }
                $stmt->close();
            } else {
                echo "❌ Failed to prepare statement<br>";
            }
            $conn->close();
        } else {
            echo "❌ Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getDbConnection not available<br>";
}

echo "<h2>Login Form</h2>";
if ($_POST['test_login'] ?? false) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'indonesia2025') {
        $_SESSION['test_admin'] = true;
        echo "<div style='color: green;'>✅ Login successful!</div>";
    } else {
        echo "<div style='color: red;'>❌ Login failed</div>";
    }
}

if (!isset($_SESSION['test_admin'])) {
    ?>
    <form method="post">
        <input type="hidden" name="test_login" value="1">
        Username: <input type="text" name="username" value="admin"><br><br>
        Password: <input type="password" name="password" value="indonesia2025"><br><br>
        <input type="submit" value="Login">
    </form>
    <?php
} else {
    echo "<div style='background: #f0fdf4; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
    echo "<strong>✅ Logged in as admin</strong><br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "<a href='?logout=1'>Logout</a>";
    echo "</div>";

    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: admin_minimal_test.php");
        exit();
    }

    echo "<h2>Token Rotation Test</h2>";
    echo "<a href='admin-rahasia.php' target='_blank'>🔐 Go to Secret Admin Panel</a>";
}

echo "<h2>Direct Links</h2>";
echo "<ul>";
echo "<li><a href='admin.php' target='_blank'>🔓 Regular Admin Panel</a></li>";
echo "<li><a href='admin-rahasia.php' target='_blank'>🔐 Secret Admin Panel</a></li>";
echo "<li><a href='simple_test.php' target='_blank'>🧪 Simple Test</a></li>";
echo "</ul>";
?>