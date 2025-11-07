# 🔐 Token Gate - Portal Token Ujian

Aplikasi web sederhana untuk mengamankan URL ujian dengan sistem token. Siswa harus memasukkan token yang valid sebelum dapat diarahkan ke halaman ujian sebenarnya.

## 🚀 Fitur Utama

- **Sistem Token Aman**: Token 8 karakter alfanumerik yang berubah otomatis
- **Rotasi Otomatis**: Token berubah setiap 10 menit (dapat dikonfigurasi)
- **Panel Admin**: Monitoring token real-time dengan countdown timer
- **Security**: PDO prepared statements, XSS prevention
- **Responsive Design**: Mobile-friendly UI/UX
- **Cron Job Integration**: Rotasi token otomatis via server scheduler

## 📋 Persyaratan Sistem

- PHP 7.0+ dengan ekstensi PDO MySQL
- MySQL 5.6+ atau MariaDB 10.0+
- Web server (Apache/Nginx)
- Akses ke Cron Job (untuk rotasi otomatis)

## 🛠️ Instalasi

### 1. Upload Files
Upload semua file ke direktori web server Anda.

### 2. Database Setup
Buka browser dan akses: `http://domain-anda/setup_database.php`

Halaman ini akan:
- ✅ Test koneksi database
- ✅ Membuat tabel otomatis
- ✅ Memasukkan data awal
- ✅ Test fungsi aplikasi

**Atau Manual via SQL:**
```sql
CREATE DATABASE token_gate_db;
USE token_gate_db;

CREATE TABLE app_config (
    id INT PRIMARY KEY DEFAULT 1,
    current_token VARCHAR(20) NOT NULL,
    last_rotated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO app_config (id, current_token) VALUES (1, 'START123');
```

### 3. Konfigurasi Database
Edit file `config.php` sesuai kredensial database Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password_anda');
define('DB_NAME', 'token_gate_db');
```

### 4. Setup Cron Job (Opsional)
Untuk rotasi token otomatis:
```bash
# Buka crontab
crontab -e

# Tambahkan baris ini (jalankan setiap 10 menit)
*/10 * * * * /usr/bin/php /var/www/html/path/ke/rotate_token.php > /dev/null 2>&1
```

## 🔑 Login Default

### Admin Panel
- **URL**: `http://domain-anda/admin.php`
- **Username**: `admin`
- **Password**: `admin123`

## 📁 Struktur File

```
token-gateway/
├── config.php              # Konfigurasi database & aplikasi
├── index.php               # Halaman login token untuk siswa
├── validate.php            # Validasi token (backend)
├── admin.php               # Panel admin dengan countdown
├── rotate_token.php        # Skrip Cron Job rotasi token
├── get_new_token.php       # Endpoint AJAX untuk admin
├── style.css               # Styling aplikasi
├── database.sql            # Skema database manual
├── setup_database.php      # Setup otomatis database
├── test_db_connection.php  # Testing koneksi database
└── README.md               # Dokumentasi ini
```

## ⚙️ Konfigurasi

### Interval Rotasi Token
Edit di `config.php`:
```php
define('TOKEN_ROTATION_MINUTES', 10); // Ubah ke nilai yang diinginkan
```

### URL Ujian
Edit di `config.php`:
```php
define('EXAM_URL', 'http://www.contoh.sch.id'); // URL ujian sebenarnya
```

### Login Admin
Edit di `config.php`:
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password_baru');
```

## 🔧 Troubleshooting

### Error: "Tidak bisa terhubung ke database"
1. Pastikan MySQL server sedang berjalan
2. Periksa kredensial database di `config.php`
3. Pastikan database `token_gate_db` sudah dibuat
4. Test koneksi via `test_db_connection.php`

### Token tidak berubah otomatis
1. Pastikan Cron Job sudah di-setup dengan benar
2. Cek log Cron: `grep CRON /var/log/syslog`
3. Test manual: `php rotate_token.php`

### Halaman admin error
1. Test koneksi database via `test_db_connection.php`
2. Pastikan tabel `app_config` ada dan memiliki data
3. Run setup otomatis via `setup_database.php`

## 🎯 Cara Penggunaan

### Untuk Siswa:
1. Buka `http://domain-anda/`
2. Masukkan token yang diberikan oleh guru/admin
3. Klik "Akses Ujian"
4. Akan diarahkan ke halaman ujian jika token benar

### Untuk Admin:
1. Login ke panel admin via `admin.php`
2. Monitor token aktif dan countdown timer
3. Salin token untuk dibagikan ke siswa
4. Rotasi token manual jika diperlukan

## 🔒 Keamanan

- ✅ PDO prepared statements mencegah SQL Injection
- ✅ htmlspecialchars() mencegah XSS attacks
- ✅ Session management untuk admin login
- ✅ Server-side validation untuk redirect
- ✅ No JavaScript dependency untuk security-critical functions

## 📱 Screenshots

*(Anda dapat menambahkan screenshots aplikasi di sini)*

## 🤝 Support

Jika mengalami masalah:
1. Cek file `test_db_connection.php` untuk diagnosa database
2. Jalankan `setup_database.php` untuk reset database
3. Pastikan semua file memiliki permission yang benar (755/644)

## 📄 License

Aplikasi ini bersifat open-source. Bebas digunakan dan dimodifikasi sesuai kebutuhan.

---

**Token Gate v1.0** - Simple & Secure Token Authentication System