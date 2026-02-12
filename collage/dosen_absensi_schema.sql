-- ============================================
-- SCHEMA UNTUK SISTEM ABSENSI DOSEN
-- Database: ypikhair_mahadzubair
-- ============================================
-- File ini berisi tabel yang diperlukan untuk sistem absensi dosen
-- Semua menggunakan IF NOT EXISTS untuk keamanan
-- ============================================

-- ============================================
-- TABEL DOSEN
-- ============================================

CREATE TABLE IF NOT EXISTS `dosen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL ABSENSI
-- ============================================

CREATE TABLE IF NOT EXISTS `absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL,
  `mata_kuliah` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'hadir',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `dosen_id` (`dosen_id`),
  KEY `tanggal` (`tanggal`),
  KEY `mata_kuliah` (`mata_kuliah`),
  KEY `status` (`status`),
  CONSTRAINT `fk_absensi_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_absensi_dosen` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DATA DEFAULT DOSEN (jika belum ada)
-- ============================================

-- Insert dosen default (password: dosen123)
INSERT INTO `dosen` (`nama`, `username`, `password`, `status`) VALUES
('Dosen Default', 'dosen', 'dosen123', 'aktif'),
('Dosen Admin', 'admin_dosen', 'admin123', 'aktif')
ON DUPLICATE KEY UPDATE `nama`=`nama`;

-- ============================================
-- CATATAN PENTING:
-- ============================================
-- 1. Pastikan database 'ypikhair_mahadzubair' sudah dibuat
-- 2. Pastikan tabel 'students' sudah ada sebelum membuat tabel 'absensi'
-- 3. Semua tabel menggunakan IF NOT EXISTS, jadi aman untuk dijalankan berulang kali
-- 4. Data default dosen hanya akan diinsert jika belum ada (menggunakan ON DUPLICATE KEY UPDATE)
-- 5. Password dosen default: dosen123 (bisa diubah setelah login)
-- ============================================

