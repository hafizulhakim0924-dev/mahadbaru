-- ============================================
-- TABEL RIWAYAT PENAMBAHAN BARANG
-- ============================================

-- Tabel untuk menyimpan riwayat penambahan dan perubahan barang
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
  KEY `action_type` (`action_type`),
  FOREIGN KEY (`barang_id`) REFERENCES `barang`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TRIGGER UNTUK OTOMATIS MENCATAT RIWAYAT
-- ============================================

-- Trigger untuk mencatat ketika barang ditambahkan
DELIMITER $$
CREATE TRIGGER `trg_barang_after_insert` 
AFTER INSERT ON `barang`
FOR EACH ROW
BEGIN
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
END$$
DELIMITER ;

-- Trigger untuk mencatat ketika barang diupdate
DELIMITER $$
CREATE TRIGGER `trg_barang_after_update` 
AFTER UPDATE ON `barang`
FOR EACH ROW
BEGIN
    -- Cek apakah ada perubahan
    IF OLD.nama_barang != NEW.nama_barang 
       OR OLD.harga != NEW.harga 
       OR OLD.stok != NEW.stok 
       OR OLD.status != NEW.status THEN
        
        -- Tentukan action type berdasarkan perubahan
        SET @action_type = 'updated';
        
        IF OLD.stok < NEW.stok THEN
            SET @action_type = 'stock_increased';
        ELSEIF OLD.stok > NEW.stok THEN
            SET @action_type = 'stock_decreased';
        END IF;
        
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
            @action_type,
            'system'
        );
    END IF;
END$$
DELIMITER ;

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

