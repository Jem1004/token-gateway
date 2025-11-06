# Requirements Document

## Introduction

Token Gate adalah aplikasi web berbasis PHP yang berfungsi sebagai portal akses untuk memvalidasi siswa menggunakan token sebelum mereka diarahkan ke URL ujian yang sebenarnya. Tujuan utama aplikasi ini adalah menyembunyikan URL ujian asli dari siswa dan memastikan hanya siswa dengan token valid yang dapat mengakses ujian.

## Glossary

- **Token Gate System**: Sistem aplikasi web yang memvalidasi token akses siswa
- **Student Portal**: Halaman login (index.php) tempat siswa memasukkan token
- **Admin Panel**: Interface web untuk administrator mengelola token
- **Token Validator**: Komponen backend yang memvalidasi token siswa
- **Token Rotator**: Skrip otomatis yang mengganti token secara berkala
- **Exam URL**: URL ujian asli yang disembunyikan dari siswa
- **Active Token**: Token yang saat ini valid untuk akses
- **Server-Side Redirect**: Pengalihan yang dilakukan di backend menggunakan HTTP header

## Requirements

### Requirement 1

**User Story:** Sebagai siswa, saya ingin memasukkan token akses di halaman login, sehingga saya dapat mengakses ujian jika token saya valid

#### Acceptance Criteria

1. WHEN siswa mengakses aplikasi, THE Student Portal SHALL menampilkan form dengan satu input field untuk token dan satu tombol submit
2. WHEN siswa mengirimkan form, THE Student Portal SHALL mengirim data token menggunakan metode POST ke Token Validator
3. IF token tidak valid atau kedaluwarsa, THEN THE Token Validator SHALL mengarahkan siswa kembali ke Student Portal dengan parameter error
4. WHEN Student Portal menerima parameter error, THE Student Portal SHALL menampilkan pesan "Token tidak valid" kepada siswa
5. IF token valid, THEN THE Token Validator SHALL melakukan Server-Side Redirect menggunakan header HTTP ke Exam URL

### Requirement 2

**User Story:** Sebagai administrator, saya ingin sistem menyembunyikan URL ujian asli dari siswa, sehingga siswa tidak dapat mengakses ujian tanpa token valid

#### Acceptance Criteria

1. THE Token Gate System SHALL menyimpan Exam URL dalam file konfigurasi backend yang tidak dapat diakses dari browser
2. THE Token Validator SHALL melakukan redirect ke Exam URL hanya menggunakan Server-Side Redirect dengan fungsi header PHP
3. THE Token Gate System SHALL NOT menggunakan redirect sisi klien seperti JavaScript window.location.href untuk mengarahkan ke Exam URL
4. THE Student Portal SHALL NOT menampilkan atau mengekspos Exam URL dalam source code HTML atau JavaScript

### Requirement 3

**User Story:** Sebagai administrator, saya ingin mengelola token akses melalui panel admin, sehingga saya dapat melihat token aktif dan membuat token baru secara manual

#### Acceptance Criteria

1. THE Admin Panel SHALL meminta username dan password sebelum memberikan akses ke fitur administrasi
2. WHEN administrator login dengan kredensial valid, THE Admin Panel SHALL menampilkan Active Token yang saat ini berlaku
3. WHEN administrator menekan tombol "Buat Token Baru (Manual)", THE Admin Panel SHALL menjalankan Token Rotator dan memperbarui tampilan dengan token baru
4. THE Admin Panel SHALL menggunakan autentikasi hardcoded dengan username dan password yang didefinisikan dalam kode

### Requirement 4

**User Story:** Sebagai administrator, saya ingin token dirotasi secara otomatis setiap periode waktu tertentu, sehingga keamanan akses tetap terjaga tanpa intervensi manual

#### Acceptance Criteria

1. THE Token Rotator SHALL dapat dieksekusi melalui Cron Job pada interval waktu yang ditentukan
2. WHEN Token Rotator dijalankan, THE Token Rotator SHALL membuat string acak alfanumerik uppercase dengan panjang 8 karakter
3. WHEN token baru dibuat, THE Token Rotator SHALL memperbarui Active Token di database dengan mengganti token lama
4. THE Token Rotator SHALL dapat dipanggil baik secara otomatis melalui Cron Job maupun manual melalui Admin Panel

### Requirement 5

**User Story:** Sebagai administrator, saya ingin sistem menyimpan hanya satu token aktif di database, sehingga pengelolaan token sederhana dan efisien

#### Acceptance Criteria

1. THE Token Gate System SHALL menggunakan tabel database dengan nama active_token yang berisi satu baris data
2. THE active_token table SHALL memiliki kolom id sebagai Primary Key dengan tipe INT
3. THE active_token table SHALL memiliki kolom current_token dengan tipe VARCHAR untuk menyimpan Active Token
4. WHEN Token Validator memvalidasi token, THE Token Validator SHALL membandingkan input siswa dengan current_token dari database

### Requirement 6

**User Story:** Sebagai administrator, saya ingin sistem terlindungi dari serangan keamanan umum, sehingga data dan akses tetap aman

#### Acceptance Criteria

1. THE Token Gate System SHALL menggunakan prepared statements untuk semua query database guna mencegah SQL Injection
2. WHEN menerima input dari pengguna, THE Token Gate System SHALL memfilter input menggunakan fungsi htmlspecialchars
3. THE Token Gate System SHALL menyimpan kredensial database dalam file konfigurasi terpisah
4. THE Token Validator SHALL melakukan validasi token di backend sebelum memberikan akses ke Exam URL

### Requirement 7

**User Story:** Sebagai pengguna sistem, saya ingin interface yang sederhana dan mudah digunakan, sehingga saya dapat menggunakan aplikasi tanpa kesulitan

#### Acceptance Criteria

1. THE Student Portal SHALL memiliki tampilan sederhana dengan styling CSS yang terpisah dari HTML
2. THE Student Portal SHALL menampilkan pesan error menggunakan JavaScript hanya untuk tujuan presentasi, bukan untuk redirect
3. THE Admin Panel SHALL memiliki interface yang jelas untuk menampilkan token aktif dan tombol aksi
4. THE Token Gate System SHALL menggunakan struktur file yang terorganisir dengan pemisahan concern (konfigurasi, logika, tampilan)
