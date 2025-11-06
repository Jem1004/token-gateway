<?php
/**
 * Database Restore Script
 * Script untuk restore database dari file backup
 * 
 * Cara penggunaan:
 * 1. Via CLI: php restore_database.php backup_file.sql
 * 2. Via browser: http://your-domain.com/token-gate/restore_database.php
 */

// Load konfigurasi database
require_once 'config.php';

$backup_dir = __DIR__ . '/backups';
$is_cli = (php_sapi_name() === 'cli');

/**
 * Fungsi untuk restore database menggunakan mysql command
 */
function restoreDatabaseMysql($host, $user, $pass, $name, $input_file) {
    $command = sprintf(
        'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($name),
        escapeshellarg($input_file)
    );
    
    exec($command, $output, $return_var);
    
    return $return_var === 0;
}

/**
 * Fungsi untuk restore database menggunakan PHP
 */
function restoreDatabasePHP($host, $user, $pass, $name, $input_file) {
    try {
        // Koneksi ke database
        $conn = new mysqli($host, $user, $pass, $name);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        // Baca file SQL
        $sql_content = file_get_contents($input_file);
        if ($sql_content === false) {
            throw new Exception("Cannot read backup file");
        }
        
        // Split SQL statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!$conn->query($statement)) {
                    throw new Exception("Error executing statement: " . $conn->error);
                }
            }
        }
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            $conn->close();
        }
        return false;
    }
}

/**
 * Fungsi untuk format ukuran file
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (!$is_cli) {
    echo "<!DOCTYPE html>\n<html>\n<head>\n";
    echo "<title>Database Restore</title>\n";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
    echo ".success{color:green;}.error{color:red;}.warning{color:orange;}.info{color:blue;}";
    echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
    echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
    echo "th{background-color:#f2f2f2;}";
    echo ".btn{display:inline-block;padding:8px 16px;margin:5px;background:#007bff;color:white;text-decoration:none;border-radius:4px;}";
    echo ".btn:hover{background:#0056b3;}</style>\n";
    echo "</head>\n<body>\n";
    echo "<h1>Database Restore Script</h1>\n";
}

// Handle restore request
if (isset($_POST['restore_file']) || ($is_cli && $argc > 1)) {
    
    // Tentukan file yang akan di-restore
    if ($is_cli) {
        $restore_file = $argv[1];
        // Jika path relatif, tambahkan backup_dir
        if (!file_exists($restore_file)) {
            $restore_file = $backup_dir . '/' . basename($restore_file);
        }
    } else {
        $restore_file = $backup_dir . '/' . basename($_POST['restore_file']);
    }
    
    // Validasi file
    if (!file_exists($restore_file)) {
        $error_msg = "ERROR: File backup tidak ditemukan: $restore_file";
        if ($is_cli) {
            echo $error_msg . "\n";
        } else {
            echo "<p class='error'>$error_msg</p>\n";
            echo "<p><a href='restore_database.php' class='btn'>Kembali</a></p>\n";
            echo "</body>\n</html>";
        }
        exit(1);
    }
    
    if ($is_cli) {
        echo "Memulai restore database...\n";
        echo "Database: $db_name\n";
        echo "File: $restore_file\n";
        echo "Ukuran: " . formatBytes(filesize($restore_file)) . "\n\n";
        echo "PERINGATAN: Ini akan menghapus semua data yang ada!\n";
        echo "Lanjutkan? (y/n): ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 'y') {
            echo "Restore dibatalkan.\n";
            exit(0);
        }
        fclose($handle);
        echo "\n";
    } else {
        echo "<p class='warning'>⚠ PERINGATAN: Restore akan menghapus semua data yang ada!</p>\n";
        echo "<p class='info'>Memulai restore database...</p>\n";
        echo "<p>Database: <strong>$db_name</strong></p>\n";
        echo "<p>File: <strong>" . basename($restore_file) . "</strong></p>\n";
        echo "<p>Ukuran: <strong>" . formatBytes(filesize($restore_file)) . "</strong></p>\n";
    }
    
    // Coba restore menggunakan mysql command terlebih dahulu
    $success = false;
    $method = '';
    
    exec('which mysql', $output, $return_var);
    if ($return_var === 0) {
        if ($is_cli) {
            echo "Menggunakan mysql command...\n";
        } else {
            echo "<p>Metode: mysql command</p>\n";
        }
        
        $success = restoreDatabaseMysql($db_host, $db_user, $db_pass, $db_name, $restore_file);
        $method = 'mysql';
    } else {
        if ($is_cli) {
            echo "mysql command tidak tersedia, menggunakan PHP restore...\n";
        } else {
            echo "<p>Metode: PHP restore (mysql command tidak tersedia)</p>\n";
        }
        
        $success = restoreDatabasePHP($db_host, $db_user, $db_pass, $db_name, $restore_file);
        $method = 'PHP';
    }
    
    // Cek hasil restore
    if ($success) {
        if ($is_cli) {
            echo "\n✓ Restore berhasil!\n";
            echo "Metode: $method\n";
            echo "Database telah dikembalikan ke kondisi backup.\n";
        } else {
            echo "<p class='success'>✓ Restore berhasil!</p>\n";
            echo "<p>Database telah dikembalikan ke kondisi backup.</p>\n";
            echo "<p><a href='admin.php' class='btn'>Ke Admin Panel</a> ";
            echo "<a href='restore_database.php' class='btn'>Restore Lagi</a></p>\n";
            echo "</body>\n</html>";
        }
        exit(0);
    } else {
        $error_msg = "ERROR: Restore gagal!";
        if ($is_cli) {
            echo "\n✗ $error_msg\n";
            echo "Metode yang digunakan: $method\n";
            echo "Periksa kredensial database di config.php\n";
        } else {
            echo "<p class='error'>✗ $error_msg</p>\n";
            echo "<p>Metode yang digunakan: $method</p>\n";
            echo "<p>Periksa kredensial database di config.php</p>\n";
            echo "<p><a href='restore_database.php' class='btn'>Kembali</a></p>\n";
            echo "</body>\n</html>";
        }
        exit(1);
    }
}

// Tampilkan daftar backup yang tersedia (hanya untuk web interface)
if (!$is_cli) {
    $backups = glob($backup_dir . '/token_gate_backup_*.sql');
    
    if (count($backups) > 0) {
        // Sort by modification time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "<p class='info'>Pilih file backup untuk di-restore:</p>\n";
        echo "<form method='post' onsubmit='return confirm(\"Apakah Anda yakin ingin restore database? Semua data saat ini akan dihapus!\");'>\n";
        echo "<table>\n";
        echo "<tr><th>File</th><th>Tanggal</th><th>Ukuran</th><th>Aksi</th></tr>\n";
        
        foreach ($backups as $backup) {
            $filename = basename($backup);
            $date = date('Y-m-d H:i:s', filemtime($backup));
            $size = formatBytes(filesize($backup));
            
            echo "<tr>\n";
            echo "<td><code>$filename</code></td>\n";
            echo "<td>$date</td>\n";
            echo "<td>$size</td>\n";
            echo "<td><button type='submit' name='restore_file' value='$filename' class='btn'>Restore</button></td>\n";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
        echo "</form>\n";
        
        echo "<p><a href='backup_database.php' class='btn'>Buat Backup Baru</a> ";
        echo "<a href='admin.php' class='btn'>Ke Admin Panel</a></p>\n";
        
    } else {
        echo "<p class='warning'>Tidak ada file backup yang tersedia.</p>\n";
        echo "<p>Buat backup terlebih dahulu menggunakan <a href='backup_database.php'>backup_database.php</a></p>\n";
    }
    
    echo "</body>\n</html>";
} else {
    // CLI usage
    if ($argc < 2) {
        echo "Usage: php restore_database.php <backup_file.sql>\n\n";
        echo "Available backups:\n";
        
        $backups = glob($backup_dir . '/token_gate_backup_*.sql');
        if (count($backups) > 0) {
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            foreach ($backups as $backup) {
                $size = formatBytes(filesize($backup));
                $date = date('Y-m-d H:i:s', filemtime($backup));
                echo "  - " . basename($backup) . " ($size) - $date\n";
            }
        } else {
            echo "  No backups found in $backup_dir\n";
        }
        
        exit(1);
    }
}
?>
