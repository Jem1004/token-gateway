# 🌍 Timezone Configuration Guide

## 📋 **Overview**
Token Gate sekarang menggunakan konfigurasi waktu yang konsisten dengan timezone server (Asia/Jakarta - WIB, GMT+7) untuk memastikan semua operasi waktu berfungsi dengan benar.

## 🎯 **Timezone yang Digunakan**

### **Primary Timezone**: `Asia/Jakarta` (WIB - GMT+7)
- **Nama Resmi**: Asia/Jakarta
- **Singkatan**: WIB (Waktu Indonesia Barat)
- **GMT Offset**: +7 jam dari UTC
- **Daylight Saving**: Tidak berlaku di Indonesia

## ⚙️ **Konfigurasi yang Telah Diterapkan**

### 1. **config.php** - Pengaturan Utama
```php
// Set default timezone untuk semua fungsi waktu PHP
date_default_timezone_set('Asia/Jakarta');

// Konstanta Waktu
define('APP_TIMEZONE', 'Asia/Jakarta');           // Timezone yang digunakan (WIB - GMT+7)
define('SERVER_TIME_FORMAT', 'Y-m-d H:i:s');         // Format waktu untuk database
define('DISPLAY_TIME_FORMAT', 'd M Y, H:i:s');     // Format waktu untuk tampilan
define('TIME_FORMAT_SHORT', 'H:i');                  // Format waktu pendek
define('SECONDS_PER_MINUTE', 60);                 // Konstanta untuk konversi waktu
```

### 2. **Database Configuration**
```sql
-- Database menggunakan CURRENT_TIMESTAMP yang akan menggunakan timezone server
CURRENT_TIMESTAMP -- Akan menggunakan Asia/Jakarta timezone
```

### 3. **Format Waktu yang Konsisten**

| Konstanta | Format | Contoh | Penggunaan |
|-----------|--------|--------|-----------|
| `SERVER_TIME_FORMAT` | `Y-m-d H:i:s` | `2025-01-07 14:30:45` | Database storage |
| `DISPLAY_TIME_FORMAT` | `d M Y, H:i:s` | `07 Jan 2025, 14:30:45` | UI Display |
| `TIME_FORMAT_SHORT` | `H:i` | `14:30` | Compact display |

## 📁 **File yang Telah Diupdate**

### **Core Configuration Files:**
- ✅ **config.php** - Timezone settings dan konstanta waktu
- ✅ **admin.php** - Display waktu dan session management
- ✅ **rotate_token.php** - Logging dan timestamp
- ✅ **get_new_token.php** - API response dengan timezone info

### **Testing Files:**
- ✅ **test_timezone.php** - Comprehensive timezone testing
- ✅ **debug_database_save.php** - Debugging dengan waktu konsisten
- ✅ **quick_db_test.php** - Quick testing dengan format waktu

## 🧪 **Cara Testing Konfigurasi Timezone**

### **Step 1: Basic Timezone Test**
```bash
# Buka di browser
http://localhost/token-gateway/test_timezone.php
```

**Expected Results:**
- ✅ Default timezone: Asia/Jakarta
- ✅ All time format constants working
- ✅ Server time displayed correctly
- ✅ JavaScript time sync successful
- ✅ Countdown timer accurate

### **Step 2: Admin Panel Test**
```bash
# Buka admin panel
http://localhost/token-gateway/admin.php
```

**Yang harus diperiksa:**
- ✅ Login time menunjukkan waktu server dengan format `H:i (Asia/Jakarta)`
- ✅ "Terakhir Dirotasi" menunjukkan waktu yang benar
- ✅ "Rotasi Berikutnya" menunjukkan waktu yang benar
- ✅ Countdown timer menghitung mundur dengan benar

### **Step 3. Token Rotation Test**
```bash
# Test manual rotation
php rotate_token.php
```

**Expected Output:**
```
Token rotated successfully: ABCDEF at 2025-01-07 14:30:45 [Asia/Jakarta]
```

### **Step 4. Database Time Verification**
```sql
SELECT
    current_token,
    last_rotated,
    DATE_FORMAT(last_rotated, '%Y-%m-%d %H:%i:%s') as formatted_time
FROM app_config
WHERE id = 1;
```

## 🔄 **Time Flow in Application**

### **1. User Login**
```php
$_SESSION['login_time'] = time();                    // Unix timestamp
$_SESSION['login_time_formatted'] = date(SERVER_TIME_FORMAT); // Formatted time
```

### **2. Token Rotation Process**
```php
// Generate token
$new_token = generateRandomToken(TOKEN_LENGTH);

// Update database dengan CURRENT_TIMESTAMP
$stmt = $pdo->prepare("UPDATE app_config SET current_token = :token, last_rotated = CURRENT_TIMESTAMP WHERE id = 1");
$result = $stmt->execute([':token' => $new_token]);

// Log dengan timezone info
error_log("Token rotated to: " . $new_token . " at " . date(SERVER_TIME_FORMAT) . " [" . APP_TIMEZONE . "]");
```

### **3. Admin Panel Display**
```php
// Display waktu login
echo "Login: " . date(TIME_FORMAT_SHORT, $_SESSION['login_time']) . " (" . APP_TIME . ")";

// Display last rotated
echo date(DISPLAY_TIME_FORMAT, strtotime($last_rotated));

// Calculate next rotation
$nextRotation = clone $lastRotated;
$nextRotation->modify('+' . TOKEN_ROTATION_MINUTES . ' minutes');
```

### **4. JavaScript Countdown**
```javascript
// Server side calculation
$sisaDetik = $nextRotation->getTimestamp() - $now->getTimestamp();

// Client side countdown
function updateCountdown() {
    const timerElement = document.getElementById('countdown-timer');
    timerElement.textContent = formatTime(sisaDetik);
    sisaDetik--;
}
```

## 🎯 **Benefits of This Configuration**

### **1. Konsistensi Waktu**
- Semua fungsi waktu menggunakan timezone yang sama
- Tidak ada konflik antara client dan server time
- Countdown timer akurat

### **2. Debugging yang Mudah**
- Log error menyertakan timezone info
- Timestamp konsisten di seluruh aplikasi
- Mudah melacak masalah waktu

### **3. User Experience**
- User melihat waktu yang relevan (WIB)
- Informasi rotasi token jelas dan akurat
- Countdown timer dapat diandalkan

### **4. Cron Job Reliability**
- Cron job menggunakan waktu server yang konsisten
- Rotasi otomatis tepat waktu
- Log yang mudah dipahami

## 🚨 **Troubleshooting Time Issues**

### **Problem: Wrong Time Display**
**Symptoms:**
- Login time tidak sesuai
- Rotasi token salah waktu
- Countdown timer tidak akurat

**Solutions:**
1. Check server timezone: `timedatectl status`
2. Set PHP timezone: `date_default_timezone_set('Asia/Jakarta')`
3. Restart web server
4. Test dengan `test_timezone.php`

### **Problem: Database Time Different**
**Symptoms:**
- Waktu di database berbeda dengan display
- Cron job berjalan pada waktu yang salah

**Solutions:**
1. Check MySQL timezone: `SELECT @@global.time_zone, @@session.time_zone;`
2. Set MySQL timezone: `SET GLOBAL time_zone = 'Asia/Jakarta';`
3. Restart MySQL service

### **Problem: JavaScript Time Sync**
**Symptoms:**
- Countdown timer tidak sinkron
- Refresh halaman menampilkan waktu berbeda

**Solutions:**
1. Test dengan `test_timezone.php`
2. Check browser timezone settings
3. Verify server time accuracy

## 🔧 **Server Configuration Commands**

### **Ubuntu/Debian:**
```bash
# Check current timezone
timedatectl

# Set timezone to Asia/Jakarta
sudo timedatectl set-timezone Asia/Jakarta

# Verify timezone
timedatectl

# Restart services if needed
sudo systemctl restart apache2
# atau
sudo systemctl restart nginx
sudo systemctl restart mysql
```

### **PHP Configuration:**
```bash
# Edit php.ini
sudo nano /etc/php/X.Y/apache2/php.ini

# Set timezone
date.timezone = "Asia/Jakarta"

# Restart Apache
sudo systemctl restart apache2
```

### **MySQL Configuration:**
```sql
-- Check current timezone
SELECT @@global.time_zone, @@session.time_zone;

-- Set timezone globally
SET GLOBAL time_zone = 'Asia/Jakarta';

-- Set timezone for current session
SET time_zone = 'Asia/Jakarta';

-- Verify
SELECT NOW();
```

## 📊 **Monitoring Time Configuration**

### **Regular Checks:**
1. **Daily**: Check admin panel time display
2. **Weekly**: Verify cron job execution time
3. **Monthly**: Review timezone configuration
4. **After Server Changes**: Test with `test_timezone.php`

### **Log Monitoring:**
```bash
# Check PHP error logs
tail -f /var/log/apache2/error.log | grep "Asia/Jakarta"

# Check token rotation logs
tail -f /var/log/syslog | grep "Token rotated"

# Monitor cron jobs
grep CRON /var/log/syslog
```

## ✅ **Checklist Production Deployment**

- [ ] Server timezone set to Asia/Jakarta
- [ ] PHP timezone configured in php.ini
- [ ] MySQL timezone set to Asia/Jakarta
- [ ] Application timezone constants working
- [ ] Admin panel displays correct time
- [ ] Countdown timer accurate
- [ ] Token rotation logs include timezone
- [ ] JavaScript time sync test passed
- [ ] Cron job executes at correct time
- [ ] All time formats consistent

---

**Configuration Version**: 2.0
**Last Updated**: 2025-01-07
**Timezone**: Asia/Jakarta (WIB, GMT+7)