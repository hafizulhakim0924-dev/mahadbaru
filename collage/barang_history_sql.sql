-- ============================================
-- TABEL RIWAYAT PENAMBAHAN BARANG
-- ============================================

-- LANGKAH 1: Pastikan tabel barang sudah ada terlebih dahulu
-- Jika belum ada, jalankan query di bawah ini:

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

-- Tabel untuk menyimpan riwayat penambahan dan perubahan barang
-- Versi tanpa Foreign Key (lebih aman jika tabel barang belum ada)
CREATE TABLE IF NOT EXISTS `barang_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barang_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `action_type` enum('added','updated','deleted','stock_increased','stock_decreased') NOT NULL DEFAULT 'added',
  `created_by` varchar(100) DEFAULT 'dosen' COMMENT 'Siapa yang melakukan aksi (dosen/admin)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `barang_id` (`barang_id`),
  KEY `created_at` (`created_at`),
  KEY `action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambahkan Foreign Key setelah tabel dibuat (opsional, untuk referential integrity)
-- Hapus komentar di bawah ini jika ingin menambahkan Foreign Key constraint
-- Pastikan tabel barang sudah ada dan menggunakan engine InnoDB
/*
ALTER TABLE `barang_history` 
ADD CONSTRAINT `fk_barang_history_barang_id` 
FOREIGN KEY (`barang_id`) 
REFERENCES `barang`(`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;
*/

-- ============================================
-- TRIGGER UNTUK OTOMATIS MENCATAT RIWAYAT
-- ============================================
-- CATATAN: Trigger hanya bisa dibuat jika menggunakan command line MySQL atau phpMyAdmin dengan mode yang mendukung DELIMITER
-- Jika menggunakan phpMyAdmin web interface, buat trigger satu per satu tanpa DELIMITER

-- LANGKAH 2: Buat trigger untuk mencatat ketika barang ditambahkan
-- Hapus trigger jika sudah ada
DROP TRIGGER IF EXISTS `trg_barang_after_insert`;

-- Buat trigger baru
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

-- LANGKAH 3: Buat trigger untuk mencatat ketika barang diupdate
-- Hapus trigger jika sudah ada
DROP TRIGGER IF EXISTS `trg_barang_after_update`;

-- Buat trigger baru (versi sederhana tanpa DELIMITER)
-- Catatan: Trigger ini akan mencatat semua update, termasuk perubahan stok
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
-- INDEX UNTUK OPTIMASI QUERY
-- ============================================

-- Index sudah dibuat di CREATE TABLE di atas
-- Tambahan index jika diperlukan untuk query spesifik

-- ============================================
-- CONTOH QUERY UNTUK MENGAMBIL RIWAYAT
-- ============================================

-- Query untuk melihat semua riwayat penambahan barang
-- SELECT bh.*, b.nama_barang as current_nama, b.harga as current_harga, b.stok as current_stok
-- FROM barang_history bh
-- LEFT JOIN barang b ON bh.barang_id = b.id
-- ORDER BY bh.created_at DESC;

-- Query untuk melihat riwayat barang tertentu
-- SELECT * FROM barang_history 
-- WHERE barang_id = 1 
-- ORDER BY created_at DESC;

-- Query untuk melihat riwayat penambahan hari ini
-- SELECT * FROM barang_history 
-- WHERE DATE(created_at) = CURDATE() 
-- AND action_type = 'added'
-- ORDER BY created_at DESC;

