-- ============================================
-- INSTALASI TABEL BARANG HISTORY
-- Jalankan query ini satu per satu di phpMyAdmin
-- ============================================

-- LANGKAH 1: Buat tabel barang (jika belum ada)
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LANGKAH 2: Buat tabel barang_history
CREATE TABLE IF NOT EXISTS `barang_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barang_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `action_type` enum('added','updated','deleted','stock_increased','stock_decreased') NOT NULL DEFAULT 'added',
  `created_by` varchar(100) DEFAULT 'dosen',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `barang_id` (`barang_id`),
  KEY `created_at` (`created_at`),
  KEY `action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LANGKAH 3: Buat trigger untuk INSERT (jalankan query ini)
DROP TRIGGER IF EXISTS `trg_barang_after_insert`;

CREATE TRIGGER `trg_barang_after_insert` 
AFTER INSERT ON `barang`
FOR EACH ROW
INSERT INTO `barang_history` (
    `barang_id`, 
    `nama_barang`, 
    `harga`, 
    `stok`, 
    `action_type`, 
    `created_by`
) VALUES (
    NEW.id,
    NEW.nama_barang,
    NEW.harga,
    NEW.stok,
    'added',
    'system'
);

-- LANGKAH 4: Buat trigger untuk UPDATE (jalankan query ini)
DROP TRIGGER IF EXISTS `trg_barang_after_update`;

CREATE TRIGGER `trg_barang_after_update` 
AFTER UPDATE ON `barang`
FOR EACH ROW
INSERT INTO `barang_history` (
    `barang_id`, 
    `nama_barang`, 
    `harga`, 
    `stok`, 
    `action_type`, 
    `created_by`
) VALUES (
    NEW.id,
    NEW.nama_barang,
    NEW.harga,
    NEW.stok,
    CASE 
        WHEN OLD.stok < NEW.stok THEN 'stock_increased'
        WHEN OLD.stok > NEW.stok THEN 'stock_decreased'
        ELSE 'updated'
    END,
    'system'
);

-- ============================================
-- OPSIONAL: Tambahkan Foreign Key (jika diperlukan)
-- ============================================
-- ALTER TABLE `barang_history` 
-- ADD CONSTRAINT `fk_barang_history_barang_id` 
-- FOREIGN KEY (`barang_id`) 
-- REFERENCES `barang`(`id`) 
-- ON DELETE CASCADE 
-- ON UPDATE CASCADE;

