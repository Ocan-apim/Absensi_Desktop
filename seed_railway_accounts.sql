-- Run this once after importing database.sql into Railway MySQL.
-- These are temporary starter accounts for deployment testing.
-- Change the passwords after confirming login works.

INSERT INTO admin (username, email, password)
VALUES ('admin', 'admin@citranegara.sch.id', 'admin123')
ON DUPLICATE KEY UPDATE
  email = VALUES(email),
  password = VALUES(password);

INSERT INTO siswa (nama_lengkap, nama_tampilan, nis, email, password, kelas, jurusan, rombel)
VALUES ('Siswa Demo', 'Siswa Demo', '00916', 'siswa@citranegara.sch.id', 'siswa123', '11', 'PPLG', '2')
ON DUPLICATE KEY UPDATE
  nama_lengkap = VALUES(nama_lengkap),
  nama_tampilan = VALUES(nama_tampilan),
  email = VALUES(email),
  password = VALUES(password),
  kelas = VALUES(kelas),
  jurusan = VALUES(jurusan),
  rombel = VALUES(rombel);

INSERT INTO walas (nama_lengkap, npsn, password, email, kelas, jurusan, rombel)
VALUES ('Walas Demo', 'WALAS001', 'walas123', 'walas@citranegara.sch.id', '11', 'PPLG', '2')
ON DUPLICATE KEY UPDATE
  nama_lengkap = VALUES(nama_lengkap),
  password = VALUES(password),
  email = VALUES(email),
  kelas = VALUES(kelas),
  jurusan = VALUES(jurusan),
  rombel = VALUES(rombel);

INSERT INTO bk (nama_lengkap, npsn, password, email)
VALUES ('BK Demo', 'BK001', 'bk123', 'bk@citranegara.sch.id')
ON DUPLICATE KEY UPDATE
  nama_lengkap = VALUES(nama_lengkap),
  password = VALUES(password),
  email = VALUES(email);
