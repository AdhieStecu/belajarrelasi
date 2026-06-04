-- SQL Migration & Seeding untuk Database Peliharaan
-- Menyesuaikan dengan tabel jenis, ras, dan hewan yang sudah ada.

USE `peliharaan`;

-- 1. Mengubah nama_hewan dari INT(100) menjadi VARCHAR(100) agar bisa menampung nama hewan berupa teks
ALTER TABLE `hewan` MODIFY `nama_hewan` VARCHAR(100) NOT NULL;

-- 2. Mengosongkan data lama agar bersih untuk data contoh
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `hewan`;
TRUNCATE TABLE `ras`;
TRUNCATE TABLE `jenis`;
SET FOREIGN_KEY_CHECKS = 1;

-- 3. Mengisi data contoh untuk tabel 'jenis' (Kategori Utama)
INSERT INTO `jenis` (`id_jenis`, `nama_jenis`) VALUES
(1, 'Kucing'),
(2, 'Anjing'),
(3, 'Kelinci');

-- 4. Mengisi data contoh untuk tabel 'ras' yang berelasi dengan tabel 'jenis'
INSERT INTO `ras` (`id_ras`, `nama_ras`, `id_jenis`) VALUES
(1, 'Persia', 1),
(2, 'Anggora', 1),
(3, 'Golden Retriever', 2),
(4, 'Bulldog', 2),
(5, 'Holland Lop', 3);

-- 5. Mengisi data contoh untuk tabel 'hewan' yang berelasi dengan tabel 'ras'
INSERT INTO `hewan` (`nama_hewan`, `umur`, `id_ras`) VALUES
('Milo', 12, 1),      -- Milo, Kucing Persia, umur 12 bulan
('Bleki', 24, 3),     -- Bleki, Anjing Golden Retriever, umur 24 bulan
('Ciko', 8, 5)       -- Ciko, Kelinci Holland Lop, umur 8 bulan
ON DUPLICATE KEY UPDATE `nama_hewan` = VALUES(`nama_hewan`);

-- 6. Membuat tabel 'users' untuk autentikasi login
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Mengisi data user administrator default (username: admin, password: admin123)
INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$2VgAYfipk/GUohTjwi7WY.ajYvXYAdubZvIRY0bmlYBEVEq1IYmKK', 'Administrator')
ON DUPLICATE KEY UPDATE 
  `username` = VALUES(`username`), 
  `password` = VALUES(`password`), 
  `nama_lengkap` = VALUES(`nama_lengkap`);

