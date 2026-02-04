-- ============================================
-- TABEL UNTUK SISTEM KEUANGAN
-- ============================================

-- Tabel untuk menyimpan data keuangan
CREATE TABLE IF NOT EXISTS `keuangan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan tagihan siswa
CREATE TABLE IF NOT EXISTS `tagihan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `nama_tagihan` varchar(255) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `nama_tagihan` (`nama_tagihan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan pembayaran manual (tunai/transfer langsung)
CREATE TABLE IF NOT EXISTS `pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `nama_tagihan` varchar(255) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `metode` varchar(50) NOT NULL DEFAULT 'Tunai',
  `keterangan` text DEFAULT NULL,
  `keuangan_id` int(11) DEFAULT NULL COMMENT 'ID keuangan yang mencatat',
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `keuangan_id` (`keuangan_id`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DATA DEFAULT
-- ============================================

-- Insert keuangan default (password: keuangan123)
INSERT INTO `keuangan` (`nama`, `username`, `password`, `email`) VALUES
('Staff Keuangan', 'keuangan', 'keuangan123', 'keuangan@example.com')
ON DUPLICATE KEY UPDATE `nama`=`nama`;

