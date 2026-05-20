USE `absensi`;

ALTER TABLE `laporan_balasan`
  DROP FOREIGN KEY `fk_balasan_kesiswaan`;

ALTER TABLE `laporan`
  DROP FOREIGN KEY `fk_laporan_kesiswaan`;

ALTER TABLE `laporan_balasan`
  DROP COLUMN `id_kesiswaan`,
  MODIFY `pengirim_role` enum('bk') NOT NULL;

ALTER TABLE `laporan`
  DROP COLUMN `id_kesiswaan`,
  MODIFY `tujuan_role` enum('bk') NOT NULL;

DROP TABLE IF EXISTS `kesiswaan`;
