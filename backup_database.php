<?php
/**
 * Database Backup Script
 * Script untuk backup database token_gate_db
 * 
 * Cara penggunaan:
 * 1. Manual via browser: http://your-domain.com/token-gate/backup_database.php
 * 2. Via CLI: php backup_database.php
 * 3. Via cron job: 0 2 * * * /usr/bin/php /path/to/backup_database.php
 */

// Load konfigurasi database
require_once 'config.php';

// Konfigurasi backup
$backup_dir = __DIR__ . '/backups';  // Direktori untuk menyimpan backup
$backup_filename = 'token_gate_backup_' . date('Y-m-d_H-i-s') . '.sql';
$backup_path = $backup_dir . '/' . $backup_filename;

// Jumlah maksimal file backup yang disimpan (untuk auto-cleanup)
$max_backups = 10;

/**
 * Fungsi untuk membuat direktori backup jika belum ada
 */
function createBackupDirectory($dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return false;
        }
        // Buat .htaccess untuk proteksi direktori backup
        $htaccess_content = "Order Deny,Allow\nDeny from all";
        file_put_contents($dir . '/.htaccess', $htaccess_content);
    }
    return true;
}

/**
 * Fungsi untuk backup database menggunakan mysqldump
 */
function backupDatabaseMysqldump($host, $user, $pass, $name, $output_file) {
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($name),
        escapeshellarg($output_file)
    );
    
    exec($command, $output, $return_var);
    
    return $return_var === 0;
}

/**
 * Fungsi untuk backup database menggunakan PHP (fallback jika mysqldump tidak tersedia)
 */
function backupDatabasePHP($host, $user, $pass, $name, $output_file) {
    try {
        // Koneksi ke database
        $conn = new mysqli($host, $user, $pass, $name);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        // Buka file untuk menulis
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            throw new Exception("Cannot create backup file");
        }
        
        // Header SQL
        $header = "-- Token Gate Database Backup\n";
        $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Host: $host\n";
        $header .= "-- Database: $name\n\n";
        $header .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $header .= "SET time_zone = \"+00:00\";\n\n";
        fwrite($handle, $header);
        
        // Ambil semua tabel
        $tables_result = $conn->query("SHOW TABLES");
        
        while ($table_row = $tables_result->fetch_array()) {
            $table = $table_row[0];
            
            // DROP TABLE statement
            fwrite($handle, "\n-- Table structure for table `$table`\n");
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            
            // CREATE TABLE statement
            $create_result = $conn->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_result->fetch_array();
            fwrite($handle, $create_row[1] . ";\n\n");
            
            // INSERT statements
            $rows_result = $conn->query("SELECT * FROM `$table`");
            
            if ($rows_result->num_rows > 0) {
                fwrite($handle, "-- Dumping data for table `$table`\n");
                
                while ($row = $rows_result->fetch_assoc()) {
                    $columns = array_keys($row);
                    $values = array_values($row);
                    
                    // Escape values
                    $escaped_values = array_map(function($value) use ($conn) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . $conn->real_escape_string($value) . "'";
                    }, $values);
                    
                    $insert = sprintf(
                        "INSERT INTO `%s` (`%s`) VALUES (%s);\n",
                        $table,
                        implode('`, `', $columns),
                        implode(', ', $escaped_values)
                    );
                    
                    fwrite($handle, $insert);
                }
                
                fwrite($handle, "\n");
            }
        }
        
        fclose($handle);
        $conn->close();
        
        return true;
        
    } catch (Exception $e) {
        if (isset($handle) && $handle) {
            fclose($handle);
        }
        if (isset($conn) && $conn) {
            $conn->close();
        }
        return false;
    }
}

/**
 * Fungsi untuk cleanup backup lama
 */
function cleanupOldBackups($dir, $max_files) {
    $files = glob($dir . '/token_gate_backup_*.sql');
    
    if (count($files) > $max_files) {
        // Sort by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $files_to_delete = array_slice($files, 0, count($files) - $max_files);
        foreach ($files_to_delete as $file) {
            unlink($file);
        }
        
        return count($files_to_delete);
    }
    
    return 0;
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

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    echo "<!DOCTYPE html>\n<html>\n<head>\n";
    echo "<title>Database Backup</title>\n";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
    echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style>\n";
    echo "</head>\n<body>\n";
    echo "<h1>Database Backup Script</h1>\n";
}

// Buat direktori backup
if (!createBackupDirectory($backup_dir)) {
    $error_msg = "ERROR: Tidak dapat membuat direktori backup: $backup_dir";
    if ($is_cli) {
        echo $error_msg . "\n";
    } else {
        echo "<p class='error'>$error_msg</p>\n</body>\n</html>";
    }
    exit(1);
}

// Coba backup menggunakan mysqldump terlebih dahulu
$success = false;
$method = '';

if ($is_cli) {
    echo "Memulai backup database...\n";
    echo "Database: $db_name\n";
    echo "Output: $backup_path\n\n";
} else {
    echo "<p class='info'>Memulai backup database...</p>\n";
    echo "<p>Database: <strong>$db_name</strong></p>\n";
    echo "<p>File: <strong>$backup_filename</strong></p>\n";
}

// Cek apakah mysqldump tersedia
exec('which mysqldump', $output, $return_var);
if ($return_var === 0) {
    if ($is_cli) {
        echo "Menggunakan mysqldump...\n";
    } else {
        echo "<p>Metode: mysqldump</p>\n";
    }
    
    $success = backupDatabaseMysqldump($db_host, $db_user, $db_pass, $db_name, $backup_path);
    $method = 'mysqldump';
} else {
    if ($is_cli) {
        echo "mysqldump tidak tersedia, menggunakan PHP backup...\n";
    } else {
        echo "<p>Metode: PHP backup (mysqldump tidak tersedia)</p>\n";
    }
    
    $success = backupDatabasePHP($db_host, $db_user, $db_pass, $db_name, $backup_path);
    $method = 'PHP';
}

// Cek hasil backup
if ($success && file_exists($backup_path) && filesize($backup_path) > 0) {
    $file_size = formatBytes(filesize($backup_path));
    
    if ($is_cli) {
        echo "\n✓ Backup berhasil!\n";
        echo "Metode: $method\n";
        echo "File: $backup_path\n";
        echo "Ukuran: $file_size\n";
    } else {
        echo "<p class='success'>✓ Backup berhasil!</p>\n";
        echo "<p>Ukuran file: <strong>$file_size</strong></p>\n";
        echo "<p>Lokasi: <code>$backup_path</code></p>\n";
    }
    
    // Cleanup backup lama
    $deleted = cleanupOldBackups($backup_dir, $max_backups);
    if ($deleted > 0) {
        if ($is_cli) {
            echo "\nMenghapus $deleted backup lama (maksimal $max_backups backup disimpan)\n";
        } else {
            echo "<p class='info'>Menghapus $deleted backup lama (maksimal $max_backups backup disimpan)</p>\n";
        }
    }
    
    // List semua backup yang tersedia
    $all_backups = glob($backup_dir . '/token_gate_backup_*.sql');
    if (count($all_backups) > 0) {
        if ($is_cli) {
            echo "\nBackup yang tersedia:\n";
            foreach ($all_backups as $backup_file) {
                $size = formatBytes(filesize($backup_file));
                $date = date('Y-m-d H:i:s', filemtime($backup_file));
                echo "  - " . basename($backup_file) . " ($size) - $date\n";
            }
        } else {
            echo "<h3>Backup yang tersedia:</h3>\n<ul>\n";
            foreach ($all_backups as $backup_file) {
                $size = formatBytes(filesize($backup_file));
                $date = date('Y-m-d H:i:s', filemtime($backup_file));
                echo "<li>" . basename($backup_file) . " ($size) - $date</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
    exit(0);
    
} else {
    $error_msg = "ERROR: Backup gagal!";
    
    if ($is_cli) {
        echo "\n✗ $error_msg\n";
        echo "Metode yang digunakan: $method\n";
        echo "Periksa kredensial database di config.php\n";
    } else {
        echo "<p class='error'>✗ $error_msg</p>\n";
        echo "<p>Metode yang digunakan: $method</p>\n";
        echo "<p>Periksa kredensial database di config.php</p>\n";
        echo "</body>\n</html>";
    }
    
    // Hapus file backup yang gagal
    if (file_exists($backup_path)) {
        unlink($backup_path);
    }
    
    exit(1);
}

if (!$is_cli) {
    echo "</body>\n</html>";
}
?>
