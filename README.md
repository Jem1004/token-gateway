# Token Gate Application

Token Gate adalah aplikasi web berbasis PHP yang berfungsi sebagai portal akses untuk memvalidasi siswa menggunakan token sebelum mereka diarahkan ke URL ujian. Aplikasi ini menyembunyikan URL ujian asli dari siswa dan memastikan hanya siswa dengan token valid yang dapat mengakses ujian.

## Fitur Utama

- **Portal Siswa**: Interface sederhana untuk siswa memasukkan token akses
- **Validasi Token**: Validasi server-side yang aman untuk autentikasi
- **Panel Admin**: Interface untuk mengelola dan melihat token aktif
- **Rotasi Token Otomatis**: Token dapat dirotasi secara otomatis menggunakan cron job
- **Rotasi Token Manual**: Admin dapat membuat token baru secara manual
- **Keamanan**: Menggunakan prepared statements dan sanitasi input untuk mencegah serangan

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx web server dengan PHP support
- Akses ke cron job (untuk rotasi token otomatis)

## Struktur File

```
/token-gate/
├── index.php              # Portal siswa (halaman login)
├── validate.php           # Backend validasi token
├── admin.php             # Panel admin
├── rotate_token.php      # Script rotasi token
├── backup_database.php   # Script backup database
├── restore_database.php  # Script restore database
├── config.php            # Konfigurasi database dan exam URL
├── style.css             # Styling CSS
├── database.sql          # Schema database
├── backups/              # Direktori untuk menyimpan backup (auto-created)
└── README.md             # Dokumentasi ini
```

## Instalasi dan Setup

### 1. Setup Database

#### Langkah 1: Buat Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE token_gate_db;
USE token_gate_db;
```

#### Langkah 2: Import Schema

Jalankan file `database.sql` untuk membuat tabel dan data awal:

```bash
mysql -u root -p token_gate_db < database.sql
```

Atau copy-paste isi file `database.sql` ke MySQL console:

```sql
CREATE TABLE active_token (
    id INT PRIMARY KEY AUTO_INCREMENT,
    current_token VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO active_token (id, current_token) VALUES (1, 'INITIAL00');
```

#### Langkah 3: Verifikasi Database

```sql
SELECT * FROM active_token;
```

Anda harus melihat satu baris dengan token 'INITIAL00'.

### 2. Konfigurasi Aplikasi

Edit file `config.php` dan sesuaikan dengan environment Anda:

```php
<?php
// Database Configuration
$db_host = 'localhost';        // Host database Anda
$db_name = 'token_gate_db';    // Nama database
$db_user = 'root';             // Username database
$db_pass = '';                 // Password database

// Exam URL Configuration
$exam_url = 'http://www.xxxxx.sch.id';  // URL ujian asli
```

**Penting**: Ganti `$exam_url` dengan URL ujian yang sebenarnya.

### 3. Upload File ke Server

Upload semua file ke direktori web server Anda:

```bash
# Contoh menggunakan SCP
scp -r * user@server:/var/www/html/token-gate/

# Atau menggunakan FTP client seperti FileZilla
```

### 4. Set Permission File

```bash
cd /var/www/html/token-gate/
chmod 644 *.php
chmod 644 *.css
chmod 600 config.php  # Lebih ketat untuk file konfigurasi
```

### 5. Test Aplikasi

1. Akses `http://your-domain.com/token-gate/index.php`
2. Masukkan token: `INITIAL00`
3. Anda harus diarahkan ke exam URL

## Konfigurasi Admin

### Kredensial Admin Default

**Username**: `admin`  
**Password**: `admin123`

### Cara Mengubah Kredensial Admin

Edit file `admin.php` dan cari bagian berikut:

```php
// Hardcoded admin credentials
$admin_username = 'admin';
$admin_password = 'admin123';
```

Ganti dengan kredensial yang Anda inginkan:

```php
// Hardcoded admin credentials
$admin_username = 'admin_baru';
$admin_password = 'password_kuat_anda';
```

**Rekomendasi Keamanan**: Untuk production, gunakan password yang kuat dengan kombinasi:
- Minimal 12 karakter
- Huruf besar dan kecil
- Angka
- Karakter spesial

### Mengakses Panel Admin

1. Buka `http://your-domain.com/token-gate/admin.php`
2. Login dengan kredensial admin
3. Anda akan melihat token aktif saat ini
4. Klik "Buat Token Baru (Manual)" untuk membuat token baru

## Konfigurasi Cron Job (Rotasi Token Otomatis)

### Mengapa Menggunakan Cron Job?

Cron job memungkinkan token dirotasi secara otomatis pada interval waktu tertentu, meningkatkan keamanan tanpa intervensi manual.

### Setup Cron Job

#### Langkah 1: Buka Crontab Editor

```bash
crontab -e
```

#### Langkah 2: Tambahkan Cron Entry

Pilih salah satu konfigurasi berikut sesuai kebutuhan:

**Rotasi Setiap 5 Menit**:
```bash
*/5 * * * * /usr/bin/php /var/www/html/token-gate/rotate_token.php
```

**Rotasi Setiap 10 Menit** (Rekomendasi):
```bash
*/10 * * * * /usr/bin/php /var/www/html/token-gate/rotate_token.php
```

**Rotasi Setiap 30 Menit**:
```bash
*/30 * * * * /usr/bin/php /var/www/html/token-gate/rotate_token.php
```

**Rotasi Setiap Jam**:
```bash
0 * * * * /usr/bin/php /var/www/html/token-gate/rotate_token.php
```

**Rotasi Setiap Hari pada Jam 8 Pagi**:
```bash
0 8 * * * /usr/bin/php /var/www/html/token-gate/rotate_token.php
```

#### Langkah 3: Verifikasi Cron Job

Lihat daftar cron job yang aktif:

```bash
crontab -l
```

#### Langkah 4: Monitor Log Cron

Untuk melihat apakah cron job berjalan dengan baik:

```bash
# Ubuntu/Debian
tail -f /var/log/syslog | grep CRON

# CentOS/RHEL
tail -f /var/log/cron
```

### Menjalankan Rotasi Token Manual via CLI

Anda juga dapat menjalankan rotasi token secara manual dari command line:

```bash
php /var/www/html/token-gate/rotate_token.php
```

### Troubleshooting Cron Job

**Masalah**: Cron job tidak berjalan

**Solusi**:
1. Pastikan path PHP benar: `which php`
2. Pastikan path file rotate_token.php benar
3. Pastikan file memiliki permission yang tepat
4. Cek log cron untuk error messages

**Masalah**: Token tidak berubah

**Solusi**:
1. Cek koneksi database di config.php
2. Jalankan script manual untuk melihat error: `php rotate_token.php`
3. Cek permission database user

## Backup dan Restore Database

### Backup Database

Aplikasi ini menyediakan script `backup_database.php` untuk memudahkan backup database.

#### Cara Menggunakan Backup Script

**1. Via Web Browser**:
- Akses: `http://your-domain.com/token-gate/backup_database.php`
- Script akan otomatis membuat backup dan menampilkan hasilnya
- File backup disimpan di direktori `backups/`

**2. Via Command Line**:
```bash
cd /var/www/html/token-gate/
php backup_database.php
```

**3. Backup Otomatis dengan Cron Job**:

Edit crontab:
```bash
crontab -e
```

Tambahkan salah satu konfigurasi berikut:

```bash
# Backup setiap hari jam 2 pagi
0 2 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php

# Backup setiap 6 jam
0 */6 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php

# Backup setiap 12 jam
0 */12 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php
```

#### Fitur Backup Script

- **Auto-create Directory**: Otomatis membuat folder `backups/` jika belum ada
- **Security Protection**: Menambahkan `.htaccess` untuk proteksi direktori backup
- **Auto Cleanup**: Menyimpan maksimal 10 backup terakhir, backup lama otomatis dihapus
- **Dual Method**: Menggunakan `mysqldump` jika tersedia, fallback ke PHP backup
- **Timestamped Files**: Format nama: `token_gate_backup_YYYY-MM-DD_HH-MM-SS.sql`
- **File Size Display**: Menampilkan ukuran file backup
- **Backup List**: Menampilkan semua backup yang tersedia

### Restore Database

Script `restore_database.php` memungkinkan Anda mengembalikan database ke kondisi backup sebelumnya.

#### Cara Menggunakan Restore Script

**1. Via Web Browser**:
- Akses: `http://your-domain.com/token-gate/restore_database.php`
- Pilih file backup dari daftar yang tersedia
- Klik tombol "Restore"
- Konfirmasi restore (akan menghapus data saat ini)

**2. Via Command Line**:

List backup yang tersedia:
```bash
php restore_database.php
```

Restore backup tertentu:
```bash
php restore_database.php token_gate_backup_2024-11-06_14-30-00.sql
```

Atau dengan full path:
```bash
php restore_database.php backups/token_gate_backup_2024-11-06_14-30-00.sql
```

#### Peringatan Restore

⚠️ **PENTING**: Restore akan menghapus semua data yang ada di database dan menggantinya dengan data dari backup!

- Pastikan Anda memilih file backup yang benar
- Disarankan membuat backup terlebih dahulu sebelum restore
- Restore tidak bisa di-undo

#### Contoh Workflow Backup & Restore

**Scenario 1: Backup Sebelum Update**
```bash
# 1. Backup database saat ini
php backup_database.php

# 2. Lakukan perubahan/update
# ...

# 3. Jika ada masalah, restore ke backup sebelumnya
php restore_database.php token_gate_backup_2024-11-06_14-30-00.sql
```

**Scenario 2: Migrasi ke Server Baru**
```bash
# Di server lama
php backup_database.php

# Copy file backup ke server baru
scp backups/token_gate_backup_*.sql user@new-server:/path/to/token-gate/backups/

# Di server baru
php restore_database.php token_gate_backup_2024-11-06_14-30-00.sql
```

**Scenario 3: Backup Berkala Otomatis**
```bash
# Setup cron untuk backup otomatis setiap hari
crontab -e

# Tambahkan:
0 2 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php

# Backup akan tersimpan otomatis, maksimal 10 file terakhir
```

### Lokasi File Backup

- Direktori: `backups/` (di dalam folder aplikasi)
- Format nama: `token_gate_backup_YYYY-MM-DD_HH-MM-SS.sql`
- Proteksi: Direktori dilindungi dengan `.htaccess` (tidak bisa diakses via browser)
- Cleanup: Otomatis menghapus backup lama jika lebih dari 10 file

### Troubleshooting Backup/Restore

**Masalah: "Cannot create backup directory"**

Solusi:
```bash
# Buat direktori manual dan set permission
mkdir backups
chmod 755 backups
chown www-data:www-data backups
```

**Masalah: "mysqldump not found"**

Solusi: Script akan otomatis menggunakan PHP backup sebagai fallback. Tidak perlu action khusus.

**Masalah: "Backup file too large"**

Solusi:
```bash
# Compress backup file
gzip backups/token_gate_backup_*.sql

# Untuk restore, uncompress dulu
gunzip backups/token_gate_backup_*.sql.gz
```

**Masalah: "Permission denied" saat backup via cron**

Solusi:
```bash
# Pastikan direktori writable oleh user yang menjalankan cron
chmod 755 /var/www/html/token-gate/backups/
```

## Penggunaan Aplikasi

### Untuk Siswa

1. Akses URL portal: `http://your-domain.com/token-gate/`
2. Masukkan token yang diberikan oleh guru/admin
3. Klik tombol Submit
4. Jika token valid, Anda akan diarahkan ke halaman ujian
5. Jika token tidak valid, akan muncul pesan error

### Untuk Admin

1. Akses panel admin: `http://your-domain.com/token-gate/admin.php`
2. Login dengan kredensial admin
3. Lihat token aktif saat ini
4. Untuk membuat token baru:
   - Klik tombol "Buat Token Baru (Manual)"
   - Token baru akan ditampilkan
   - Bagikan token baru kepada siswa
5. Untuk logout, klik tombol "Logout"

## Rekomendasi Keamanan untuk Production

### 1. Gunakan HTTPS

**Sangat Penting**: Selalu gunakan HTTPS untuk melindungi data yang dikirim antara browser dan server.

```bash
# Install Let's Encrypt SSL Certificate (gratis)
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

### 2. Pindahkan config.php ke Luar Web Root

Untuk keamanan maksimal, pindahkan `config.php` ke luar direktori web root:

```bash
# Pindahkan config.php
mv /var/www/html/token-gate/config.php /var/www/config.php

# Update semua file yang menggunakan config.php
# Ganti: require_once 'config.php';
# Dengan: require_once '/var/www/config.php';
```

### 3. Gunakan Environment Variables

Untuk production, gunakan environment variables untuk kredensial sensitif:

```php
// config.php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'token_gate_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$exam_url = getenv('EXAM_URL') ?: 'http://default-url.com';
```

### 4. Nonaktifkan Error Display

Edit `php.ini` atau tambahkan di awal setiap file PHP:

```php
// Untuk production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php-errors.log');
```

### 5. Implementasi Rate Limiting

Tambahkan rate limiting untuk mencegah brute force attacks pada login admin dan validasi token.

### 6. Gunakan Password Hashing untuk Admin

Ganti hardcoded password dengan hashed password:

```php
// Generate hash (jalankan sekali)
echo password_hash('your_password', PASSWORD_DEFAULT);

// Di admin.php, ganti validasi dengan:
if ($username === $admin_username && password_verify($password, $admin_password_hash)) {
    // Login success
}
```

### 7. Implementasi CSRF Protection

Tambahkan CSRF token untuk form admin:

```php
// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Di form
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validasi
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

### 8. Set Proper File Permissions

```bash
# File PHP
chmod 644 *.php

# Config file (lebih ketat)
chmod 600 config.php

# Direktori
chmod 755 /var/www/html/token-gate/

# Owner
chown -R www-data:www-data /var/www/html/token-gate/
```

### 9. Backup Database Secara Berkala

Aplikasi ini dilengkapi dengan script backup otomatis (`backup_database.php`).

**Cara Backup Manual**:

Via Browser:
```
http://your-domain.com/token-gate/backup_database.php
```

Via CLI:
```bash
php backup_database.php
```

**Backup Otomatis dengan Cron**:
```bash
# Backup setiap hari jam 2 pagi
0 2 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php

# Backup setiap 6 jam
0 */6 * * * /usr/bin/php /var/www/html/token-gate/backup_database.php
```

**Fitur Backup Script**:
- Otomatis membuat direktori `backups/` dengan proteksi .htaccess
- Menyimpan maksimal 10 backup terakhir (auto-cleanup)
- Mendukung mysqldump dan PHP backup (fallback)
- Format nama file: `token_gate_backup_YYYY-MM-DD_HH-MM-SS.sql`

**Cara Restore Database**:

Via Browser:
```
http://your-domain.com/token-gate/restore_database.php
```

Via CLI:
```bash
# List available backups
php restore_database.php

# Restore specific backup
php restore_database.php token_gate_backup_2024-11-06_14-30-00.sql
```

### 10. Monitor dan Logging

Implementasikan logging untuk aktivitas penting:

```php
// Fungsi logging sederhana
function logActivity($message) {
    $log_file = '/var/log/token-gate.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Contoh penggunaan
logActivity("Token validated successfully for IP: " . $_SERVER['REMOTE_ADDR']);
logActivity("Admin login from IP: " . $_SERVER['REMOTE_ADDR']);
logActivity("Token rotated - New token generated");
```

### 11. Batasi Akses Admin Panel

Gunakan `.htaccess` untuk membatasi akses admin panel berdasarkan IP:

```apache
# .htaccess untuk admin.php
<Files "admin.php">
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.100  # IP admin
    Allow from 10.0.0.50      # IP sekolah
</Files>
```

### 12. Update PHP dan MySQL Secara Berkala

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get upgrade php mysql-server

# CentOS/RHEL
sudo yum update php mysql-server
```

## Troubleshooting

### Masalah: "Connection failed" Error

**Penyebab**: Koneksi database gagal

**Solusi**:
1. Cek kredensial database di `config.php`
2. Pastikan MySQL service berjalan: `sudo service mysql status`
3. Cek apakah database user memiliki permission yang tepat

### Masalah: Token Valid Tapi Tidak Redirect

**Penyebab**: Output sebelum header redirect

**Solusi**:
1. Pastikan tidak ada spasi atau output sebelum `<?php` di `validate.php`
2. Cek apakah ada `echo` atau `print` sebelum `header()`
3. Pastikan file disimpan tanpa BOM (Byte Order Mark)

### Masalah: "Token tidak valid" Meskipun Token Benar

**Penyebab**: Case sensitivity atau whitespace

**Solusi**:
1. Token bersifat case-sensitive (huruf besar/kecil harus sama)
2. Pastikan tidak ada spasi di awal atau akhir token
3. Cek token di database: `SELECT current_token FROM active_token WHERE id = 1;`

### Masalah: Admin Panel Tidak Bisa Login

**Penyebab**: Kredensial salah atau session issue

**Solusi**:
1. Verifikasi username dan password di `admin.php`
2. Cek apakah session berfungsi: `php -i | grep session`
3. Pastikan direktori session writable: `ls -la /var/lib/php/sessions/`

### Masalah: Cron Job Tidak Berjalan

**Penyebab**: Path salah atau permission issue

**Solusi**:
1. Cek path PHP: `which php`
2. Test script manual: `php /path/to/rotate_token.php`
3. Cek cron log: `tail -f /var/log/syslog | grep CRON`
4. Pastikan cron service berjalan: `sudo service cron status`

## FAQ

**Q: Berapa lama token valid?**  
A: Token valid sampai dirotasi (manual atau otomatis via cron job). Tidak ada expiration time built-in.

**Q: Apakah bisa menggunakan multiple token sekaligus?**  
A: Tidak, sistem ini dirancang untuk satu token aktif pada satu waktu untuk semua siswa.

**Q: Bagaimana cara melihat history token?**  
A: Sistem ini tidak menyimpan history token. Hanya token aktif saat ini yang disimpan.

**Q: Apakah siswa bisa mengakses exam URL langsung?**  
A: Tidak, exam URL disembunyikan dan hanya diakses via server-side redirect. Namun, jika siswa sudah pernah mengakses, URL bisa tersimpan di browser history.

**Q: Berapa panjang token yang dihasilkan?**  
A: Token memiliki panjang 8 karakter dengan kombinasi huruf besar (A-Z) dan angka (0-9).

**Q: Apakah bisa mengintegrasikan dengan sistem lain?**  
A: Ya, Anda bisa memodifikasi `validate.php` untuk mengirim data ke API lain atau logging ke sistem eksternal.

## Lisensi

Aplikasi ini dibuat untuk keperluan pendidikan dan dapat digunakan secara bebas.

## Support

Untuk pertanyaan atau masalah, silakan hubungi administrator sistem Anda.

---

**Versi**: 1.0  
**Terakhir Diupdate**: 2024
