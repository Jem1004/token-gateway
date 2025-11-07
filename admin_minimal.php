<?php
// Minimal admin panel for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Minimal Admin Panel Test</h1>";

// Test session
echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Starting session... ";
    if (session_start()) {
        echo "✅ Session started<br>";
    } else {
        echo "❌ Session failed<br>";
    }
} else {
    echo "✅ Session already active<br>";
}

// Test config
echo "<h2>Config Test</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    try {
        require_once 'config.php';
        echo "✅ config.php loaded<br>";
    } catch (ParseError $e) {
        echo "❌ Parse Error: " . $e->getMessage() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    } catch (Error $e) {
        echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    }
} else {
    echo "❌ config.php not found<br>";
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
if (function_exists('getDbConnection')) {
    echo "✅ getDbConnection function exists<br>";
    try {
        $conn = getDbConnection();
        if ($conn) {
            echo "✅ Database connected<br>";
            $conn->close();
        } else {
            echo "❌ Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getDbConnection function not found<br>";
}

// Simple login form
echo "<h2>Login Form</h2>";
?>
<form method="POST">
    <input type="hidden" name="test" value="1">
    Username: <input type="text" name="username" value="admin"><br><br>
    Password: <input type="password" name="password" value="indonesia2025"><br><br>
    <input type="submit" value="Login">
</form>

<?php
if ($_POST['test'] == 1) {
    echo "<h2>Login Test</h2>";
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    echo "Username: " . htmlspecialchars($username) . "<br>";
    echo "Password: " . (empty($password) ? 'empty' : 'provided') . "<br>";

    if ($username === 'admin' && $password === 'indonesia2025') {
        echo "✅ Login successful!<br>";
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $username;
    } else {
        echo "❌ Login failed<br>";
    }
}

if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated']) {
    echo "<h2>Authenticated Section</h2>";
    echo "Welcome, " . htmlspecialchars($_SESSION['admin_username']) . "!<br>";
    echo "<a href='?logout=1'>Logout</a><br>";

    if (isset($_GET['logout'])) {
        session_destroy();
        echo "Logged out!";
    }
}
?>