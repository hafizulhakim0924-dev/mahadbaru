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
-- Tambah kolom 'jumlah' ke tabel pembayaran jika belum ada
ALTER TABLE `pembayaran`
ADD COLUMN IF NOT EXISTS `jumlah` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `nama_tagihan`;

-- Tambah kolom 'metode' jika belum ada
ALTER TABLE `pembayaran`
ADD COLUMN IF NOT EXISTS `metode` VARCHAR(50) NOT NULL DEFAULT 'Tunai' AFTER `jumlah`;

-- Tambah kolom 'keterangan' jika belum ada
ALTER TABLE `pembayaran`
ADD COLUMN IF NOT EXISTS `keterangan` TEXT NULL AFTER `metode`;

-- Tambah kolom 'keuangan_id' jika belum ada
ALTER TABLE `pembayaran`
ADD COLUMN IF NOT EXISTS `keuangan_id` INT(11) NULL AFTER `keterangan`;

-- Tambah kolom 'tanggal' jika belum ada
ALTER TABLE `pembayaran`
ADD COLUMN IF NOT EXISTS `tanggal` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `keuangan_id`;

-- Set semua pembayaran manual menjadi 'teller'
UPDATE `pembayaran` SET `sumber` = 'teller' WHERE `sumber` IS NULL OR `sumber` = '';

-- Set semua pesanan_belanja yang sudah ada menjadi 'aplikasi'
UPDATE `pesanan_belanja` SET `sumber` = 'aplikasi' WHERE `sumber` IS NULL OR `sumber` = '';

