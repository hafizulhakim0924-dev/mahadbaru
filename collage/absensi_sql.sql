-- SQL untuk membuat tabel absensi
-- Tabel ini terkoneksi dengan profile.php untuk menampilkan data absensi siswa

CREATE TABLE IF NOT EXISTS `absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `mata_kuliah` varchar(100) NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'hadir',
  `keterangan` text DEFAULT NULL,
  `dosen_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `dosen_id` (`dosen_id`),
  KEY `tanggal` (`tanggal`),
  KEY `student_tanggal_mata_kuliah` (`student_id`, `tanggal`, `mata_kuliah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk performa query
-- Index ini membantu query di profile.php untuk menampilkan absensi siswa dengan cepat

-- Catatan:
-- 1. student_id: ID siswa dari tabel students
-- 2. tanggal: Tanggal absensi
-- 3. mata_kuliah: Nama mata kuliah/pelajaran
-- 4. status: hadir, izin, sakit, atau alpha
-- 5. keterangan: Keterangan tambahan (opsional)
-- 6. dosen_id: ID dosen yang menginput (opsional, bisa NULL)
-- 7. created_at: Waktu data dibuat
-- 8. updated_at: Waktu data terakhir diupdate

-- Jika tabel dosen belum ada, bisa dibuat dengan:
-- CREATE TABLE IF NOT EXISTS `dosen` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `nama` varchar(100) NOT NULL,
--   `email` varchar(100) DEFAULT NULL,
--   PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

