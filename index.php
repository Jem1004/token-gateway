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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Portal Token Ujian - Masukkan token untuk mengakses halaman ujian">
    <meta name="theme-color" content="#10b981">
</head>
<body>
    <div class="container">
        <div class="login-card">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo-icon">
                    🔐
                </div>
            </div>

            <!-- Header Section -->
            <div class="header">
                <h1>Portal Token Ujian</h1>
                <p>Masukkan token yang diberikan untuk mengakses halaman ujian</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message" role="alert" aria-live="polite">
                    <strong>⚠️ Gagal Akses</strong><br>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Form Section -->
            <form action="validate.php" method="POST" class="token-form" novalidate>
                <div class="form-group">
                    <label for="token">
                        Token Akses
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="token"
                        name="token"
                        placeholder="Masukkan token 6 huruf"
                        required
                        maxlength="6"
                        minlength="6"
                        pattern="[A-Z]{6}"
                        autocomplete="off"
                        autofocus
                        aria-describedby="token-help"
                        title="Token harus 6 huruf besar (A-Z)"
                    >
                    <small id="token-help" class="form-help">
                        Token berupa 6 huruf besar (A-Z)
                    </small>
                </div>
                <button type="submit" class="submit-btn">
                    <span>🚀 Akses Ujian</span>
                </button>
            </form>

            <!-- Footer Section -->
            <div class="footer">
                <p>
                    <strong>🔒 Keamanan:</strong> Token bersifat rahasia dan hanya berlaku untuk satu sesi ujian
                </p>
                <div class="footer-links">
                    <a href="#" onclick="alert('Hubungi admin untuk mendapatkan token'); return false;" class="help-link">
                        Butuh bantuan?
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.token-form');
            const tokenInput = document.getElementById('token');
            const submitBtn = document.querySelector('.submit-btn');

            // Auto-uppercase input
            tokenInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');

                // Visual feedback
                if (e.target.value.length === 6) {
                    e.target.style.borderColor = '#10b981';
                    e.target.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
                } else {
                    e.target.style.borderColor = '';
                    e.target.style.boxShadow = '';
                }
            });

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                const token = tokenInput.value.trim();

                if (token.length !== 6) {
                    e.preventDefault();
                    showError('Token harus 6 karakter!');
                    return;
                }

                if (!/^[A-Z]{6}$/.test(token)) {
                    e.preventDefault();
                    showError('Token hanya boleh mengandung huruf besar (A-Z)!');
                    return;
                }

                // Show loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>⏳ Memverifikasi...</span>';
            });

            function showError(message) {
                // Remove existing error
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }

                // Create new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.setAttribute('role', 'alert');
                errorDiv.setAttribute('aria-live', 'polite');
                errorDiv.innerHTML = `<strong>⚠️ Error</strong><br>${message}`;

                form.parentNode.insertBefore(errorDiv, form);

                // Remove error after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);

                // Shake animation
                tokenInput.classList.add('shake');
                setTimeout(() => tokenInput.classList.remove('shake'), 600);
            }

            // Auto-focus on load
            tokenInput.focus();

            // Prevent paste with invalid characters
            tokenInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').toUpperCase().replace(/[^A-Z]/g, '');
                this.value = pastedData.substring(0, 6);

                // Trigger input event for validation
                this.dispatchEvent(new Event('input'));
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + Enter to submit
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    form.submit();
                }
                // Escape to clear
                if (e.key === 'Escape') {
                    tokenInput.value = '';
                    tokenInput.focus();
                }
            });

            // Add visual feedback for input length
            tokenInput.addEventListener('input', function() {
                const remaining = 6 - this.value.length;
                const helpText = document.getElementById('token-help');

                if (remaining > 0) {
                    helpText.textContent = `Token berupa 6 huruf (tersisa ${remaining})`;
                    helpText.style.color = remaining <= 2 ? '#ef4444' : '#6b7280';
                } else {
                    helpText.textContent = '✓ Token lengkap';
                    helpText.style.color = '#10b981';
                }
            });
        });

        // Add shake animation to CSS if not exists
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .shake {
                animation: shake 0.5s ease-in-out;
            }
            .form-help {
                font-size: 12px;
                color: #6b7280;
                margin-top: 4px;
                display: block;
                font-weight: 400;
            }
            .required {
                color: #ef4444;
                margin-left: 2px;
            }
            .footer-links {
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #e5e7eb;
            }
            .help-link {
                color: #10b981;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
                transition: color 0.3s ease;
            }
            .help-link:hover {
                color: #059669;
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>