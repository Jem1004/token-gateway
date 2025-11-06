# ⏱️ Token Countdown Timer Feature

## 📋 Overview

Sistem countdown timer untuk admin panel yang menampilkan waktu real-time hingga token berikutnya akan berubah. Fitur ini membantu admin memantau kapan token akan dirotasi secara otomatis.

## ✨ Features

### 🎯 **Core Features:**
- ✅ **Real-time Countdown** - Tampilan countdown langsung (Hari, Jam, Menit, Detik)
- ✅ **Progress Bar** - Visual progress bar dengan animasi shimmer
- ✅ **Status Indicator** - Indikator status (Aktif, Peringatan, Kadaluarsa)
- ✅ **Auto Refresh** - Refresh otomatis setiap 30 detik
- ✅ **Manual Refresh** - Tombol refresh untuk update manual
- ✅ **Responsive Design** - Tampilan optimal di semua device

### 📊 **Information Display:**
- ✅ **Waktu Rotasi Terakhir** - Kapan token terakhir diubah
- ✅ **Waktu Rotasi Berikutnya** - Kapan token akan berubah
- ✅ **Interval Rotasi** - Durasi antara rotasi (default: 60 menit)
- ✅ **Status Auto-Rotation** - Apakah rotasi otomatis aktif

### 🔔 **Smart Features:**
- ✅ **Page Visibility API** - Pause countdown saat tab tidak aktif
- ✅ **Browser Notifications** - Notifikasi saat token kadaluarsa
- ✅ **Error Handling** - Tampil error dengan gracefully
- ✅ **Security Logging** - Log semua aktivitas keamanan

## 🛠️ Setup Instructions

### **1. Database Migration**

Jalankan SQL migration untuk menambahkan fitur timer ke database:

```sql
-- Jalankan file ini di MySQL:
mysql -u root -p token_gate_db < migration_add_token_timer.sql
```

### **2. File Structure**

Pastikan file-file berikut ada di project:

```
token-gateway/
├── admin.php                 # Updated dengan countdown UI
├── api_token_info.php        # API endpoint untuk token info
├── token_countdown.js        # JavaScript countdown engine
├── rotate_token.php          # Updated dengan timing support
├── migration_add_token_timer.sql  # Database migration
├── log_security.php          # Security logging system
├── style.css                 # Updated dengan countdown styles
└── index.php                 # Student portal (existing)
```

### **3. Verification**

1. **Login ke Admin Panel:**
   - Buka `admin.php` di browser
   - Login dengan credentials admin

2. **Cek Countdown Timer:**
   - Timer akan muncul di bagian atas dashboard
   - Status "Aktif" dengan countdown real-time
   - Progress bar animasi

3. **Test Fitur:**
   - Klik tombol "🔄 Refresh Data" untuk refresh manual
   - Perhatikan countdown berubah setiap detik
   - Coba switch tab dan kembali - countdown akan sync

## 🎨 UI Components

### **Countdown Display:**
```
⏱️ Countdown Token Rotation          [AKTIF]

[ 00 ] [ 00 ] [ 58 ] [ 32 ]
 Hari   Jam   Menit  Detik

███████████████████████████████████ 95%
🔄 Rotasi Terakhir:   05 Nov 2024 14:30:15
⏰ Rotasi Berikutnya: 05 Nov 2024 15:30:15
📊 Interval Rotasi:  60 menit
🤖 Auto-Rotation:     Aktif

                    [ 🔄 Refresh Data ]
```

### **Status Colors:**
- 🟢 **Aktif** - Hijau ( countdown > 5 menit )
- 🟡 **Peringatan** - Kuning dengan pulse ( countdown 1-5 menit )
- 🔴 **Kadaluarsa** - Merah ( countdown ≤ 1 menit )

## 📱 Responsive Design

### **Desktop ( > 768px ):**
- 4 column countdown display
- Full info grid
- Large progress bar

### **Tablet ( ≤ 768px ):**
- 2 column countdown display
- Condensed info
- Medium progress bar

### **Mobile ( ≤ 480px ):**
- 1 column countdown display
- Stack layout
- Compact progress bar

## 🔧 Technical Details

### **JavaScript API:**

```javascript
// Initialize countdown
const countdown = new TokenCountdown('countdownContainer', {
    refreshInterval: 1000,        // 1 second updates
    warningThreshold: 300,       // 5 minutes warning
    criticalThreshold: 60,       // 1 minute critical
    apiEndpoint: 'api_token_info.php'
});

// Manual refresh
countdown.refreshTokenData();

// Destroy instance
countdown.destroy();
```

### **API Response Format:**

```json
{
    "success": true,
    "current_token": "ABC123XYZ",
    "time_until_rotation": 3542,
    "next_rotation_time": "2024-11-05 15:30:15",
    "rotation_interval": 60,
    "auto_rotation_enabled": true,
    "last_rotation_time": "2024-11-05 14:30:15",
    "server_timestamp": "2024-11-05 14:35:33"
}
```

### **Database Schema:**

```sql
-- Updated active_token table
active_token (
    id, current_token, created_at, updated_at,
    token_expiry_minutes,        -- NEW
    token_rotation_interval,     -- NEW
    auto_rotation_enabled,       -- NEW
    last_rotation_time,          -- NEW
    next_rotation_time           -- NEW
)

-- NEW: token_history table
token_history (
    id, old_token, new_token, rotation_time,
    rotation_type, rotated_by, ip_address
)
```

## ⚡ Performance

### **Optimizations:**
- ✅ **Efficient Updates** - Refresh hanya saat perlu
- ✅ **Smart Caching** - Cache API responses
- ✅ **Memory Efficient** - Cleanup intervals saat page unload
- ✅ **Network Efficient** - Compressed API responses

### **Monitoring:**
- ✅ **Security Events** - Semua aktivitas di-log
- ✅ **Error Tracking** - Errors ditangkap dengan gracefully
- ✅ **Performance Metrics** - Timing dan response logging

## 🔒 Security

### **Security Features:**
- ✅ **Session Validation** - Hanya admin yang bisa akses
- ✅ **CSRF Protection** - Token validation untuk API calls
- ✅ **Input Sanitization** - All input sanitized
- ✅ **Rate Limiting** - Prevent abuse of refresh calls
- ✅ **Audit Trail** - Complete activity logging

### **Security Headers:**
```http
Content-Type: application/json
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Cache-Control: no-cache, must-revalidate
```

## 🐛 Troubleshooting

### **Common Issues:**

1. **Countdown tidak muncul:**
   - Cek console untuk JavaScript errors
   - Pastikan `token_countdown.js` ter-load
   - Verify API endpoint accessible

2. **API Error:**
   - Cek koneksi database
   - Run migration SQL
   - Verify file permissions

3. **Timer tidak sync:**
   - Refresh browser cache (Ctrl+F5)
   - Cek server timezone settings
   - Verify database connection

### **Debug Mode:**

Aktifkan debug di browser console:
```javascript
// Check countdown instance
console.log(window.tokenCountdown);

// Manual API call
fetch('api_token_info.php').then(r => r.json()).then(console.log);
```

## 📈 Future Enhancements

### **Planned Features:**
- 🔄 **Auto-Rotation** - Fully automatic token rotation
- 📊 **Usage Analytics** - Token usage statistics
- 🎯 **Custom Intervals** - Set custom rotation times
- 📧 **Email Notifications** - Email alerts untuk admin
- 🔄 **Bulk Operations** - Multiple token management

### **API Extensions:**
- `POST /api_token_rotate` - Manual rotation via API
- `GET /api_token_history` - Get rotation history
- `PUT /api_token_settings` - Update timer settings
- `DELETE /api_token_reset` - Reset timer system

## 🎉 Conclusion

Sistem countdown timer memberikan admin visibility penuh atas token rotation schedule dengan:

- 🎯 **Real-time Updates** - Always current countdown
- 🎨 **Modern UI** - Clean, professional design
- 📱 **Responsive** - Works on all devices
- 🔒 **Secure** - Enterprise-grade security
- ⚡ **Fast** - Optimized performance

Admin sekarang dapat dengan mudah memantau kapan token akan berubah dan merencanakan rotasi sesuai kebutuhan! 🚀