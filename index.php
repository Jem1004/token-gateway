<?php
// Generate random nonce for CSP
$nonce = bin2hex(random_bytes(16));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Ujian - SMPN3</title>

    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'nonce-<?php echo $nonce; ?>'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

    <!-- Additional Security -->
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <style nonce="<?php echo $nonce; ?>">
        /* Prevent text selection */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }

        /* Prevent right click on sensitive elements */
        .no-context-menu {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Hide scrollbar but keep functionality */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Console detection prevention */
        .console-warning {
            display: none;
        }
    </style>
</head>
<body class="no-select hide-scrollbar">
    <!-- Security Warning for Console -->
    <div class="console-warning">⚠️ Akses tidak sah terdeteksi. Aktivitas Anda akan dicatat.</div>

    <div class="container no-context-menu">
        <div class="text-center">
            <img src="smpn3.png" alt="Logo SMPN3" class="school-logo no-context-menu" oncontextmenu="return false;" ondragstart="return false;">
            <h1 class="no-select">Portal Ujian</h1>
            <p class="text-muted no-select" style="margin-bottom: 2rem;">SMP Negeri 3 Penajam Paser Utara</p>
        </div>

        <?php
        // Sanitize error parameter to prevent XSS
        if (isset($_GET['error']) && htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') === '1'):
        ?>
            <div class="message message-error" style="margin-bottom: 2rem;">
                Token tidak valid. Hubungi pengawas ujian.
            </div>
        <?php endif; ?>

        <form method="POST" action="validate.php" id="examForm">
            <div class="form-group">
                <label for="token" class="no-select">
                    Token Akses
                </label>
                <input
                    type="text"
                    id="token"
                    name="token"
                    required
                    placeholder="Masukkan token akses"
                    autocomplete="one-time-code"
                    maxlength="50"
                    pattern="[A-Za-z0-9\-_]+"
                    title="Token hanya boleh mengandung huruf, angka, strip, dan garis bawah"
                    class="no-context-menu"
                    oncopy="return false;"
                    onpaste="return false;"
                    oncut="return false;"
                    oncontextmenu="return false;"
                >
            </div>
            <button type="submit" class="btn" id="submitBtn">
                Masuk
            </button>
        </form>

        
      </div>

    <!-- Security Scripts with nonce -->
    <script nonce="<?php echo $nonce; ?>">
        // Disable right-click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable text selection
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable drag
        document.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' ||
                (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J')) ||
                (e.ctrlKey && e.key === 'U') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Console detection
        let devtools = {
            open: false,
            orientation: null
        };
        const threshold = 160;

        setInterval(() => {
            if (
                window.outerHeight - window.innerHeight > threshold ||
                window.outerWidth - window.innerWidth > threshold
            ) {
                if (!devtools.open) {
                    devtools.open = true;
                    console.clear();
                    console.log('%c⚠️ Peringatan Keamanan!', 'color: #ff0000; font-size: 20px; font-weight: bold;');
                    console.log('%cAkses tidak sah terdeteksi. Aktivitas Anda sedang dipantau.', 'color: #ff0000; font-size: 14px;');
                    console.log('%cSistem akan mencatat semua aktivitas mencurigakan.', 'color: #ff0000; font-size: 14px;');

                    // Log attempt (in real implementation, this would send to server)
                    navigator.sendBeacon ?
                        navigator.sendBeacon('log_security.php', JSON.stringify({
                            type: 'devtools_detected',
                            user_agent: navigator.userAgent,
                            timestamp: new Date().toISOString(),
                            session_id: '<?php echo session_id(); ?>'
                        })) : null;
                }
            } else {
                devtools.open = false;
            }
        }, 500);

        // Form submission protection
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const token = document.getElementById('token').value;
            if (token.length < 5) {
                e.preventDefault();
                alert('Token terlalu pendek. Pastikan token yang Anda masukkan benar.');
                return false;
            }

            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="margin-right: 0.5rem;">⏳</span>Memproses...';

            return true;
        });

        // Page visibility API
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // User switched tabs - in real implementation, log this
                console.log('User switched away from exam page');
            }
        });

        // Prevent opening in new window/tab
        document.addEventListener('mousedown', function(e) {
            if (e.button === 1 || e.ctrlKey || e.metaKey) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
