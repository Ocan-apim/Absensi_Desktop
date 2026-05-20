-- Admin dashboard: kehadiran guru (walas) per hari.
-- Siswa sudah tercatat di tabel `hadir`. Tanpa tabel ini, grafik guru tidak punya sumber data.
-- Jalankan sekali di database `absensi` (phpMyAdmin / mysql CLI).

USE `absensi`;

CREATE TABLE IF NOT EXISTS `walas_hadir` (
  `id_walas_hadir` int NOT NULL AUTO_INCREMENT,
  `id_walas` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_hadir` time NOT NULL,
  `keterangan` varchar(500) DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_walas_hadir`),
  UNIQUE KEY `uniq_walas_hadir_tanggal` (`id_walas`,`tanggal`),
  KEY `idx_walas_hadir_tanggal` (`tanggal`),
  CONSTRAINT `fk_walas_hadir_walas` FOREIGN KEY (`id_walas`) REFERENCES `walas` (`id_walas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
