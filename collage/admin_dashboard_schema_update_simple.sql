-- ============================================
-- UPDATE SCHEMA UNTUK ADMIN DASHBOARD (VERSI SEDERHANA)
-- ============================================
-- 
-- INSTRUKSI:
-- 1. Jalankan script ini di phpMyAdmin atau MySQL client
-- 2. Jika error "Duplicate column name", berarti kolom sudah ada, abaikan error tersebut
-- 3. Script ini akan menambahkan kolom 'sumber' untuk tracking sumber transaksi
--

-- ============================================
-- TAMBAH KOLOM 'sumber' KE TABEL pembayaran
-- ============================================
-- Tambahkan setelah kolom 'tanggal' (kolom terakhir yang pasti ada)
ALTER TABLE `pembayaran` 
ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'teller' AFTER `tanggal`;

-- Jika error "Duplicate column name 'sumber'", berarti kolom sudah ada, lanjutkan saja

-- ============================================
-- TAMBAH KOLOM 'sumber' KE TABEL pesanan_belanja
-- ============================================
-- Tambahkan setelah kolom 'status' (kolom yang pasti ada)
ALTER TABLE `pesanan_belanja` 
ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'aplikasi' AFTER `status`;

-- Jika error "Duplicate column name 'sumber'", berarti kolom sudah ada, lanjutkan saja

-- ============================================
-- UPDATE DATA EXISTING
-- ============================================
-- Set semua pembayaran manual menjadi 'teller'
UPDATE `pembayaran` SET `sumber` = 'teller' WHERE `sumber` IS NULL;

-- Set semua pesanan_belanja yang sudah ada menjadi 'aplikasi'
UPDATE `pesanan_belanja` SET `sumber` = 'aplikasi' WHERE `sumber` IS NULL;

