-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 11, 2026 at 02:28 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bk`
--

CREATE TABLE `bk` (
  `id_bk` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `npsn` varchar(20) NOT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nama_tampilan` varchar(150) DEFAULT NULL,
  `nis` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `jurusan` varchar(50) NOT NULL,
  `rombel` varchar(10) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama_lengkap`, `nama_tampilan`, `nis`, `email`, `password`, `kelas`, `jurusan`, `rombel`, `tempat_lahir`, `tanggal_lahir`, `created_at`) VALUES
(2704217, 'Hosanna Serafim', NULL, '00916', 'hosanna@gmail.com', 'ocancan', '11', 'pplg', '2', 'jakarta', '2009-06-29', '2024-05-11 07:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `walas`
--

CREATE TABLE `walas` (
  `id_walas` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `npsn` varchar(20) NOT NULL,
  `kelas` varchar(2) DEFAULT NULL,
  `jurusan` varchar(10) DEFAULT NULL,
  `rombel` varchar(10) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bk`
--
ALTER TABLE `bk`
  ADD PRIMARY KEY (`id_bk`),
  ADD UNIQUE KEY `npsn` (`npsn`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `walas`
--
ALTER TABLE `walas`
  ADD PRIMARY KEY (`id_walas`),
  ADD UNIQUE KEY `npsn` (`npsn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bk`
--
ALTER TABLE `bk`
  MODIFY `id_bk` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2704218;

--
-- AUTO_INCREMENT for table `walas`
--
ALTER TABLE `walas`
  MODIFY `id_walas` int NOT NULL AUTO_INCREMENT;
-- --------------------------------------------------------

--
-- Additional attendance and report tables
--

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
