<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Gate - Portal Siswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Portal Akses Ujian</h1>
        <p>Masukkan token akses Anda untuk melanjutkan ke ujian</p>
        
        <?php 
        // Sanitize error parameter to prevent XSS
        if (isset($_GET['error']) && htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') === '1'): 
        ?>
            <div class="error-message">
                Token tidak valid
            </div>
        <?php endif; ?>
        
        <form method="POST" action="validate.php">
            <div class="form-group">
                <label for="token">Token Akses:</label>
                <input type="text" id="token" name="token" required placeholder="Masukkan token">
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
