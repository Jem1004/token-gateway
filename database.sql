-- Token Gate Database Schema
-- Aplikasi Portal Token untuk Ujian

-- Membuat database jika belum ada
CREATE DATABASE IF NOT EXISTS token_gate_db;
USE token_gate_db;

-- Tabel konfigurasi aplikasi
CREATE TABLE app_config (
    id INT PRIMARY KEY DEFAULT 1,
    current_token VARCHAR(6) NOT NULL,
    last_rotated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Memasukkan data awal agar aplikasi tidak error
INSERT INTO app_config (id, current_token) VALUES (1, 'START');

-- Struktur tabel jika perlu melihat data nanti
SELECT * FROM app_config;