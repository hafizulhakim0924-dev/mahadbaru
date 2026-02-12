-- ============================================
-- UPDATE SCHEMA UNTUK ADMIN DASHBOARD
-- ============================================
-- 
-- INSTRUKSI:
-- 1. Jalankan script ini di phpMyAdmin atau MySQL client
-- 2. Jika error "Duplicate column name", berarti kolom sudah ada, lanjutkan saja
-- 3. Script ini akan menambahkan kolom 'sumber' untuk tracking sumber transaksi
--

-- ============================================
-- TAMBAH KOLOM 'sumber' KE TABEL pembayaran
-- ============================================
-- Cek apakah kolom sudah ada, jika belum tambahkan
-- Jika kolom keuangan_id ada, tambahkan setelah keuangan_id
-- Jika tidak ada, tambahkan setelah tanggal

-- Cara 1: Jika kolom keuangan_id ADA di tabel pembayaran
-- Uncomment baris di bawah ini jika kolom keuangan_id ada:
-- ALTER TABLE `pembayaran` ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'teller' AFTER `keuangan_id`;

-- Cara 2: Jika kolom keuangan_id TIDAK ADA di tabel pembayaran
-- Uncomment baris di bawah ini jika kolom keuangan_id tidak ada:
ALTER TABLE `pembayaran` ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'teller' AFTER `tanggal`;

-- Jika error "Duplicate column name 'sumber'", berarti kolom sudah ada, lanjutkan ke bagian berikutnya

-- ============================================
-- TAMBAH KOLOM 'sumber' KE TABEL pesanan_belanja
-- ============================================
-- Cek apakah kolom method_name ada, jika ada tambahkan setelah method_name
-- Jika tidak ada, tambahkan setelah status

-- Cara 1: Jika kolom method_name ADA
-- Uncomment baris di bawah ini jika kolom method_name ada:
ALTER TABLE `pesanan_belanja` ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'aplikasi' AFTER `method_name`;

-- Cara 2: Jika kolom method_name TIDAK ADA
-- Uncomment baris di bawah ini jika kolom method_name tidak ada:
-- ALTER TABLE `pesanan_belanja` ADD COLUMN `sumber` ENUM('teller', 'aplikasi') DEFAULT 'aplikasi' AFTER `status`;

-- Jika error "Duplicate column name 'sumber'", berarti kolom sudah ada, lanjutkan ke bagian berikutnya

-- ============================================
-- UPDATE DATA EXISTING
-- ============================================
-- Set semua pembayaran manual menjadi 'teller'
UPDATE `pembayaran` SET `sumber` = 'teller' WHERE `sumber` IS NULL;

-- Set semua pesanan_belanja yang sudah ada menjadi 'aplikasi'
UPDATE `pesanan_belanja` SET `sumber` = 'aplikasi' WHERE `sumber` IS NULL;

