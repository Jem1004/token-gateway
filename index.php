<?php
// Cek apakah ada parameter error
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = 'Token tidak valid atau sudah kedaluwarsa.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Gate - Portal Ujian</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="header">
                <h1>Portal Token Ujian</h1>
                <p>Masukkan token yang diberikan untuk mengakses halaman ujian</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="validate.php" method="POST" class="token-form">
                <div class="form-group">
                    <label for="token">Token Akses:</label>
                    <input
                        type="text"
                        id="token"
                        name="token"
                        placeholder="Masukkan token di sini"
                        required
                        maxlength="20"
                        autocomplete="off"
                        autofocus
                    >
                </div>
                <button type="submit" class="submit-btn">
                    Akses Ujian
                </button>
            </form>

            <div class="footer">
                <p>Token bersifat rahasia dan hanya berlaku untuk satu sesi</p>
            </div>
        </div>
    </div>
</body>
</html>