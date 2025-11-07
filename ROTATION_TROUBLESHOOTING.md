# 🔧 Token Rotation Troubleshooting Guide

## Masalah yang Ditemukan dan Diperbaiki

### ❌ **Masalah 1: rotate_token.php tidak memberikan output untuk debugging**
**Penyebab:** Script tidak memberikan feedback apakah rotasi berhasil atau gagal.
**Solusi:**
- Menambahkan JSON response untuk AJAX calls
- Menambahkan plain text output untuk cron job
- Menambahkan error logging yang lebih baik

### ❌ **Masalah 2: Admin panel tidak menerima response yang benar**
**Penyebab:** JavaScript mengharapkan response.text() tapi rotate_token.php tidak mengembalikan format yang benar.
**Solusi:**
- Update rotate_token.php untuk mengembalikan JSON response
- Update JavaScript di admin.php untuk menangani JSON response
- Menambahkan loading state dan error handling yang lebih baik

### ❌ **Masalah 3: Tidak ada deteksi AJAX vs Cron Job**
**Penyebab:** Script yang sama digunakan untuk AJAX calls (admin panel) dan cron job, tapi outputnya berbeda.
**Solusi:**
- Menambahkan deteksi AJAX request
- Memberikan response yang sesuai (JSON untuk AJAX, plain text untuk cron)

## ✅ **Fitur yang Telah Diperbaiki**

### 1. **Manual Rotation (Admin Panel)**
- ✅ Tombol "Rotasi Token" sekarang berfungsi dengan baik
- ✅ Loading state saat proses rotasi
- ✅ Real-time update token tanpa perlu refresh
- ✅ Error handling dan user feedback
- ✅ Prevent multiple clicks (disable button saat proses)

### 2. **Automatic Rotation (Cron Job)**
- ✅ Script sekarang kompatibel dengan cron job
- ✅ Proper exit codes untuk success (0) dan error (1)
- ✅ Error logging ke system log
- ✅ Minimal output untuk cron job

### 3. **Debugging Tools**
- ✅ `test_rotate.php` - Test manual rotation function
- ✅ `test_cron_simulation.php` - Simulasi dan monitoring cron job
- ✅ Error logging yang lebih detail
- ✅ JSON response untuk debugging

## 🚀 **Cara Testing**

### Test Manual Rotation:
1. Buka `http://localhost/token-gateway/admin.php`
2. Login sebagai admin
3. Klik tombol "🔄 Rotasi Token"
4. Seharusnya ada notifikasi success dan token berubah

### Test AJAX Response:
1. Buka `http://localhost/token-gateway/test_rotate.php`
2. Lihat debugging information
3. Test rotasi langsung dari halaman ini

### Test Cron Job:
1. Buka `http://localhost/token-gateway/test_cron_simulation.php`
2. Lihat status rotasi dan waktu berikutnya
3. Gunakan "Force Rotate" untuk testing manual

### Test Actual Cron Job:
```bash
# Test manual execution
php /path/to/rotate_token.php

# Setup cron job
crontab -e

# Tambahkan line ini (setiap 15 menit):
*/15 * * * * /usr/bin/php /path/to/rotate_token.php > /dev/null 2>&1

# Atau untuk testing (setiap 1 menit):
*/1 * * * * /usr/bin/php /path/to/rotate_token.php >> /var/log/token_rotation.log 2>&1
```

## 🔍 **Debugging Steps**

### Jika rotasi manual tidak berfungsi:
1. Buka Developer Tools (F12)
2. Lihat Console tab untuk error JavaScript
3. Lihat Network tab untuk response dari rotate_token.php
4. Buka `test_rotate.php` untuk detailed debugging

### Jika cron job tidak berfungsi:
1. Test manual execution: `php rotate_token.php`
2. Check cron logs: `grep CRON /var/log/syslog`
3. Check error logs: `tail -f /var/log/syslog`
4. Pastikan path PHP dan path file benar

### Jika database error:
1. Buka `test_db_connection.php`
2. Check koneksi database
3. Pastikan tabel `app_config` ada dan memiliki data
4. Run `migration_token_length.php` jika needed

## 📋 **Checklist untuk Production**

- [ ] Database connection tested
- [ ] Token generation works
- [ ] Manual rotation tested in admin panel
- [ ] AJAX responses working
- [ ] Cron job setup dengan interval yang benar (15 menit)
- [ ] Error logging monitored
- [ ] Permissions correct untuk PHP script
- [ ] Path di cron job benar

## 🎯 **Expected Behavior**

### Manual Rotation:
1. User klik "Rotasi Token"
2. Button disabled dan menampilkan "⏳ Proses..."
3. AJAX request ke `rotate_token.php`
4. Database updated dengan token baru
5. UI updated dengan token baru tanpa refresh
6. Countdown timer reset ke 15 menit
7. Success notification ditampilkan

### Automatic Rotation:
1. Cron job menjalankan `rotate_token.php` setiap 15 menit
2. Token baru di-generate
3. Database updated
4. Admin panel akan detect perubahan pada refresh/AJAX call
5. Countdown timer akan menyesuaikan

## 🆘 **Common Issues & Solutions**

### Issue: "Token tidak berubah setelah klik rotasi"
**Solution:** Check browser console untuk JavaScript error, test dengan `test_rotate.php`

### Issue: "Cron job tidak jalan"
**Solution:** Check cron logs, verify path, test manual execution

### Issue: "Database error"
**Solution:** Check koneksi database, run migration script jika needed

### Issue: "Permission denied"
**Solution:** Pastikan PHP script memiliki permission untuk di-execute dan write ke database

---

**Last Updated:** 2025-01-07
**Version:** 1.0