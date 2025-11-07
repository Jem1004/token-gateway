<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Ambil current_token terbaru dari database
    $stmt = $pdo->prepare("SELECT current_token, last_rotated FROM app_config WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        echo json_encode(['error' => 'Token tidak ditemukan']);
        exit;
    }

    // Kirim token dan waktu rotasi terakhir
    echo json_encode([
        'token' => $result['current_token'],
        'last_rotated' => $result['last_rotated'],
        'timezone' => APP_TIMEZONE,
        'server_time' => date(SERVER_TIME_FORMAT)
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>