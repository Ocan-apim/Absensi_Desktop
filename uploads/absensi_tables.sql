-- Tambahan tabel untuk database `absensi`
-- Import file ini setelah uploads/absensiNEW.sql jika database lama sudah terlanjur dibuat.

USE `absensi`;

CREATE TABLE IF NOT EXISTS `hadir` (
  `id_hadir` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_hadir` time NOT NULL,
  `selfie_hadir` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `jam_pulang` time DEFAULT NULL,
  `selfie_pulang` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hadir`),
  UNIQUE KEY `uniq_hadir_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_hadir_tanggal` (`tanggal`),
  CONSTRAINT `fk_hadir_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sakit` (
  `id_sakit` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_kirim` time NOT NULL,
  `dokumen` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sakit`),
  UNIQUE KEY `uniq_sakit_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_sakit_tanggal` (`tanggal`),
  CONSTRAINT `fk_sakit_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `izin` (
  `id_izin` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_kirim` time NOT NULL,
  `dokumen` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_izin`),
  UNIQUE KEY `uniq_izin_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_izin_tanggal` (`tanggal`),
  CONSTRAINT `fk_izin_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `dispen` (
  `id_dispen` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_kirim` time NOT NULL,
  `dokumen` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dispen`),
  UNIQUE KEY `uniq_dispen_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_dispen_tanggal` (`tanggal`),
  CONSTRAINT `fk_dispen_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `laporan` (
  `id_laporan` int NOT NULL AUTO_INCREMENT,
  `id_walas` int NOT NULL,
  `tujuan_role` enum('bk') NOT NULL,
  `id_bk` int DEFAULT NULL,
  `subjek` varchar(150) NOT NULL,
  `isi_laporan` text NOT NULL,
  `status` enum('dikirim','dibalas','ditutup') NOT NULL DEFAULT 'dikirim',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_laporan`),
  KEY `idx_laporan_walas` (`id_walas`),
  KEY `idx_laporan_tujuan` (`tujuan_role`,`status`),
  CONSTRAINT `fk_laporan_walas` FOREIGN KEY (`id_walas`) REFERENCES `walas` (`id_walas`) ON DELETE CASCADE,
  CONSTRAINT `fk_laporan_bk` FOREIGN KEY (`id_bk`) REFERENCES `bk` (`id_bk`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `laporan_balasan` (
  `id_balasan` int NOT NULL AUTO_INCREMENT,
  `id_laporan` int NOT NULL,
  `pengirim_role` enum('bk') NOT NULL,
  `id_bk` int DEFAULT NULL,
  `isi_balasan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_balasan`),
  KEY `idx_balasan_laporan` (`id_laporan`),
  CONSTRAINT `fk_balasan_laporan` FOREIGN KEY (`id_laporan`) REFERENCES `laporan` (`id_laporan`) ON DELETE CASCADE,
  CONSTRAINT `fk_balasan_bk` FOREIGN KEY (`id_bk`) REFERENCES `bk` (`id_bk`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Kehadiran wali kelas / guru (walas), untuk statistik admin vs absensi siswa di `hadir`.
-- Detail: uploads/migration_admin_dashboard_walas_hadir.sql

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
