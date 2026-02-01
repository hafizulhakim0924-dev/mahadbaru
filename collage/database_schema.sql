-- ============================================
-- TABEL UNTUK SISTEM SISWA
-- ============================================

-- Tabel untuk menyimpan data siswa
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `class` varchar(100) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `phone_no` (`phone_no`),
  KEY `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL UNTUK SISTEM ABSENSI
-- ============================================

-- Tabel untuk menyimpan data absensi siswa
CREATE TABLE IF NOT EXISTS `absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `mata_kuliah` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'alpha',
  `keterangan` text DEFAULT NULL,
  `dosen_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `dosen_id` (`dosen_id`),
  KEY `tanggal` (`tanggal`),
  KEY `mata_kuliah` (`mata_kuliah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan data dosen
CREATE TABLE IF NOT EXISTS `dosen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`)
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

-- Tabel untuk menyimpan data admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','superadmin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DATA DEFAULT
-- ============================================

-- Insert admin default (password: admin123)
INSERT INTO `admin` (`nama`, `username`, `password`, `role`) VALUES
('Administrator', 'admin', 'admin123', 'superadmin')
ON DUPLICATE KEY UPDATE `nama`=`nama`;

-- Insert dosen default (password: dosen123)
INSERT INTO `dosen` (`nama`, `username`, `password`) VALUES
('Dr. Budi Santoso, M.Kom', 'dosen1', 'dosen123'),
('Prof. Dr. Siti Aminah, M.T', 'dosen2', 'dosen123'),
('Ahmad Fauzi, S.Kom, M.Cs', 'dosen3', 'dosen123')
ON DUPLICATE KEY UPDATE `nama`=`nama`;

-- Insert contoh barang
INSERT INTO `barang` (`nama_barang`, `deskripsi`, `harga`, `stok`, `status`) VALUES
('Buku Tulis', 'Buku tulis 38 lembar', 5000.00, 100, 'aktif'),
('Pensil 2B', 'Pensil 2B Faber Castell', 3000.00, 150, 'aktif'),
('Penghapus', 'Penghapus Faber Castell', 2000.00, 200, 'aktif'),
('Penggaris', 'Penggaris 30 cm', 5000.00, 80, 'aktif'),
('Tas Sekolah', 'Tas sekolah ransel', 150000.00, 20, 'aktif')
ON DUPLICATE KEY UPDATE `nama_barang`=`nama_barang`;

