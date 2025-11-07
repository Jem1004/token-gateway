<?php
require_once 'config.php';

// Hanya menerima metode POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php?error=1");
    exit;
}

// Ambil token dari form
$submitted_token = trim($_POST['token'] ?? '');

// Validasi input
if (empty($submitted_token)) {
    header("Location: index.php?error=1");
    exit;
}

try {
    // Ambil current_token dari database menggunakan prepared statement
    $stmt = $pdo->prepare("SELECT current_token FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        // Jika tidak ada data di database
        header("Location: index.php?error=1");
        exit;
    }

    $current_token = $result['current_token'];

    // Bandingkan token (case sensitive)
    if ($submitted_token === $current_token) {
        // Token cocok, redirect ke URL ujian
        header("Location: " . EXAM_URL);
        exit;
    } else {
        // Token tidak cocok
        header("Location: index.php?error=1");
        exit;
    }

} catch (PDOException $e) {
    // Jika terjadi error database
    header("Location: index.php?error=1");
    exit;
}
?>