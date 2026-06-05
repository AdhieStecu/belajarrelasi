-- SQL Migration & Seeding untuk Database Peliharaan
-- Membuat database, tabel-tabel, dan mengisi data contoh.

CREATE DATABASE IF NOT EXISTS `peliharaan` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `peliharaan`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `jadwal_perawatan`;
DROP TABLE IF EXISTS `hewan`;
DROP TABLE IF EXISTS `ras`;
DROP TABLE IF EXISTS `jenis`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Membuat tabel 'jenis' (Kategori Induk)
CREATE TABLE `jenis` (
  `id_jenis` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(100) NOT NULL,
  PRIMARY KEY (`id_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Membuat tabel 'ras' dengan metadata
CREATE TABLE `ras` (
  `id_ras` int(11) NOT NULL AUTO_INCREMENT,
  `nama_ras` varchar(100) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `sifat` varchar(255) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `perawatan_bulu` varchar(50) DEFAULT NULL,
  `cocok_apartemen` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_ras`),
  KEY `id_jenis` (`id_jenis`),
  CONSTRAINT `ras_ibfk_1` FOREIGN KEY (`id_jenis`) REFERENCES `jenis` (`id_jenis`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Membuat tabel 'hewan' dengan berat_badan
CREATE TABLE `hewan` (
  `id_hewan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_hewan` varchar(100) NOT NULL,
  `umur` int(11) NOT NULL,
  `id_ras` int(11) NOT NULL,
  `berat_badan` float NOT NULL DEFAULT 4.0,
  PRIMARY KEY (`id_hewan`),
  KEY `id_ras` (`id_ras`),
  CONSTRAINT `hewan_ibfk_1` FOREIGN KEY (`id_ras`) REFERENCES `ras` (`id_ras`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Membuat tabel 'jadwal_perawatan'
CREATE TABLE `jadwal_perawatan` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_hewan` int(11) NOT NULL,
  `kegiatan` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Belum Selesai',
  PRIMARY KEY (`id_jadwal`),
  KEY `id_hewan` (`id_hewan`),
  CONSTRAINT `jadwal_perawatan_ibfk_1` FOREIGN KEY (`id_hewan`) REFERENCES `hewan` (`id_hewan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Membuat tabel 'users' untuk autentikasi login
CREATE TABLE `users` (
  `id_user` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SEED DATA
-- ==========================================

-- Mengisi Kategori Utama
INSERT INTO `jenis` (`id_jenis`, `nama_jenis`) VALUES
(1, 'Kucing'),
(2, 'Anjing'),
(3, 'Kelinci');

-- Mengisi Ras Peliharaan dengan Metadata Lengkap
INSERT INTO `ras` (`id_ras`, `nama_ras`, `id_jenis`, `sifat`, `ukuran`, `perawatan_bulu`, `cocok_apartemen`) VALUES
(1, 'Persia', 1, 'Manja, Tenang, Lembut', 'Sedang', 'Tinggi', 1),
(2, 'Anggora', 1, 'Aktif, Cerdas, Bersahabat', 'Sedang', 'Sedang', 1),
(3, 'Golden Retriever', 2, 'Sangat Ramah, Cerdas, Aktif', 'Besar', 'Tinggi', 0),
(4, 'Bulldog', 2, 'Keras Kepala, Tenang, Ramah Anak', 'Sedang', 'Rendah', 1),
(5, 'Holland Lop', 3, 'Jinak, Menggemaskan, Pendiam', 'Kecil', 'Sedang', 1);

-- Mengisi Profil Hewan
INSERT INTO `hewan` (`id_hewan`, `nama_hewan`, `umur`, `id_ras`, `berat_badan`) VALUES
(1, 'Milo', 12, 1, 4.5),      -- Milo, Kucing Persia, 12 bulan, 4.5kg
(2, 'Bleki', 24, 3, 28.2),     -- Bleki, Anjing Golden Retriever, 24 bulan, 28.2kg
(3, 'Ciko', 8, 5, 1.8);        -- Ciko, Kelinci Holland Lop, 8 bulan, 1.8kg

-- Mengisi Jadwal Perawatan Awal
INSERT INTO `jadwal_perawatan` (`id_hewan`, `kegiatan`, `tanggal`, `status`) VALUES
(1, 'Vaksinasi Tahunan', '2026-06-15', 'Belum Selesai'),
(1, 'Grooming Bulu Tebal', '2026-06-10', 'Belum Selesai'),
(2, 'Obat Cacing Rutin', '2026-06-08', 'Belum Selesai'),
(3, 'Potong Kuku Kelinci', '2026-06-06', 'Selesai');

-- Mengisi User default (username: admin, password: admin123)
INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$2VgAYfipk/GUohTjwi7WY.ajYvXYAdubZvIRY0bmlYBEVEq1IYmKK', 'Administrator');
