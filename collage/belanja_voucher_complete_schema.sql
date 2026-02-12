-- ============================================
-- SCHEMA LENGKAP UNTUK SISTEM BELANJA & VOUCHER
-- Database: ypikhair_mahadzubair
-- ============================================
-- File ini berisi semua tabel yang diperlukan untuk sistem belanja dan voucher
-- Semua menggunakan IF NOT EXISTS untuk keamanan
-- Dijalankan sekali saja, aman untuk dijalankan berulang kali
-- ============================================

-- ============================================
-- TABEL UNTUK SISTEM BELANJA
-- ============================================

-- Tabel untuk menyimpan data barang yang dijual
CREATE TABLE IF NOT EXISTS `barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan data pesanan belanja
CREATE TABLE IF NOT EXISTS `pesanan_belanja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status` enum('pending','berhasil','gagal','expired','cancelled') NOT NULL DEFAULT 'pending',
  `tripay_ref` varchar(255) DEFAULT NULL,
  `pay_code` varchar(255) DEFAULT NULL,
  `pay_url` text DEFAULT NULL,
  `qr_string` text DEFAULT NULL,
  `method_code` varchar(50) DEFAULT NULL,
  `method_name` varchar(255) DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan detail item dalam pesanan
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pesanan_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pesanan_id` (`pesanan_id`),
  KEY `barang_id` (`barang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan voucher pembayaran (untuk ditukarkan offline)
CREATE TABLE IF NOT EXISTS `voucher_pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `voucher_code` varchar(100) NOT NULL,
  `bukti_pembayaran` text DEFAULT NULL,
  `status` enum('pending','redeemed','expired') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `redeemed_by` int(11) DEFAULT NULL COMMENT 'ID admin yang redeem',
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_code` (`voucher_code`),
  KEY `student_id` (`student_id`),
  KEY `pesanan_id` (`pesanan_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CATATAN PENTING:
-- ============================================
-- 1. Pastikan database 'ypikhair_mahadzubair' sudah dibuat
-- 2. Pastikan user database memiliki hak akses yang cukup
-- 3. Semua tabel menggunakan IF NOT EXISTS, jadi aman untuk dijalankan berulang kali
-- 4. Voucher akan otomatis dibuat oleh callback.php setelah pembayaran berhasil
-- 5. Pastikan callback URL di Tripay mengarah ke: https://ypi-khairaummah.sch.id/callback.php
-- ============================================
-- 
-- CARA MENJALANKAN:
-- 1. Login ke phpMyAdmin atau MySQL client
-- 2. Pilih database 'ypikhair_mahadzubair'
-- 3. Copy-paste semua script di atas
-- 4. Klik Execute/Run
-- 5. Pastikan tidak ada error
-- ============================================

