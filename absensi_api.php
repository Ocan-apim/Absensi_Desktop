<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/cloudinary-upload.php";

$conn = db();
ensureStudentProfileColumns($conn);
$method = $_SERVER["REQUEST_METHOD"];
$today = date("Y-m-d");
$nowTime = date("H:i:s");


function findSiswa($conn, $username) {
    $stmt = $conn->prepare("SELECT id_siswa, nama_lengkap, nis, email FROM siswa WHERE nis = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $siswa = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $siswa;
}

function saveUpload($field, $dir, $prefix) {
    return saveUploadWithCloudinary($field, $dir, $prefix, $dir);
}

function fetchStudentsByClass($conn, $kelas, $jurusan, $rombel = "") {
    $sql = "
        SELECT id_siswa, nama_lengkap, nis, kelas, jurusan, rombel
        FROM siswa
        WHERE kelas = ? AND LOWER(jurusan) = LOWER(?)
    ";
    if ($rombel !== "") {
        $sql .= " AND rombel = ?";
    }
    $sql .= "
        ORDER BY nama_lengkap ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($rombel !== "") {
        $stmt->bind_param("sss", $kelas, $jurusan, $rombel);
    } else {
        $stmt->bind_param("ss", $kelas, $jurusan);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function fetchRangeRecordsByClass($conn, $startDate, $endDate, $kelas, $jurusan, $rombel = "") {
    $rombelSql = $rombel !== "" ? " AND s.rombel = ?" : "";
    $sql = "
        SELECT h.id_hadir AS id, 'hadir' AS mode, s.id_siswa, s.nama_lengkap AS name, s.nis AS user,
               h.tanggal AS date, TIME_FORMAT(h.jam_hadir, '%H:%i') AS time,
               h.keterangan, h.selfie_hadir AS hadirSelfie,
               TIME_FORMAT(h.jam_pulang, '%H:%i') AS jamPulang,
               h.selfie_pulang AS pulangSelfie, NULL AS dokumen
        FROM hadir h
        JOIN siswa s ON s.id_siswa = h.id_siswa
        WHERE h.tanggal BETWEEN ? AND ? AND s.kelas = ? AND LOWER(s.jurusan) = LOWER(?)$rombelSql
        UNION ALL
        SELECT sakit.id_sakit AS id, 'sakit' AS mode, s.id_siswa, s.nama_lengkap AS name, s.nis AS user,
               sakit.tanggal AS date, TIME_FORMAT(sakit.jam_kirim, '%H:%i') AS time,
               sakit.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, sakit.dokumen
        FROM sakit JOIN siswa s ON s.id_siswa = sakit.id_siswa
        WHERE sakit.tanggal BETWEEN ? AND ? AND s.kelas = ? AND LOWER(s.jurusan) = LOWER(?)$rombelSql
        UNION ALL
        SELECT izin.id_izin AS id, 'izin' AS mode, s.id_siswa, s.nama_lengkap AS name, s.nis AS user,
               izin.tanggal AS date, TIME_FORMAT(izin.jam_kirim, '%H:%i') AS time,
               izin.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, izin.dokumen
        FROM izin JOIN siswa s ON s.id_siswa = izin.id_siswa
        WHERE izin.tanggal BETWEEN ? AND ? AND s.kelas = ? AND LOWER(s.jurusan) = LOWER(?)$rombelSql
        UNION ALL
        SELECT dispen.id_dispen AS id, 'dispen' AS mode, s.id_siswa, s.nama_lengkap AS name, s.nis AS user,
               dispen.tanggal AS date, TIME_FORMAT(dispen.jam_kirim, '%H:%i') AS time,
               dispen.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, dispen.dokumen
        FROM dispen JOIN siswa s ON s.id_siswa = dispen.id_siswa
        WHERE dispen.tanggal BETWEEN ? AND ? AND s.kelas = ? AND LOWER(s.jurusan) = LOWER(?)$rombelSql
    ";

    $stmt = $conn->prepare($sql);
    if ($rombel !== "") {
        $stmt->bind_param(
            "ssssssssssssssssssss",
            $startDate, $endDate, $kelas, $jurusan, $rombel,
            $startDate, $endDate, $kelas, $jurusan, $rombel,
            $startDate, $endDate, $kelas, $jurusan, $rombel,
            $startDate, $endDate, $kelas, $jurusan, $rombel
        );
    } else {
        $stmt->bind_param(
            "ssssssssssssssss",
            $startDate, $endDate, $kelas, $jurusan,
            $startDate, $endDate, $kelas, $jurusan,
            $startDate, $endDate, $kelas, $jurusan,
            $startDate, $endDate, $kelas, $jurusan
        );
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row["status"] = ucfirst($row["mode"]);
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function listAbsensi($conn, $tanggal) {
    $hadirSql = "
        SELECT h.id_hadir AS id, 'hadir' AS mode, s.nama_lengkap AS name, s.nis AS user,
               h.tanggal AS date, TIME_FORMAT(h.jam_hadir, '%H:%i') AS time,
               h.keterangan, h.selfie_hadir AS hadirSelfie,
               TIME_FORMAT(h.jam_pulang, '%H:%i') AS jamPulang,
               h.selfie_pulang AS pulangSelfie, NULL AS dokumen
        FROM hadir h
        JOIN siswa s ON s.id_siswa = h.id_siswa
        WHERE h.tanggal = ?
    ";

    $nonHadirSql = "
        SELECT sakit.id_sakit AS id, 'sakit' AS mode, siswa.nama_lengkap AS name, siswa.nis AS user,
               sakit.tanggal AS date, TIME_FORMAT(sakit.jam_kirim, '%H:%i') AS time,
               sakit.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, sakit.dokumen
        FROM sakit JOIN siswa ON siswa.id_siswa = sakit.id_siswa WHERE sakit.tanggal = ?
        UNION ALL
        SELECT izin.id_izin AS id, 'izin' AS mode, siswa.nama_lengkap AS name, siswa.nis AS user,
               izin.tanggal AS date, TIME_FORMAT(izin.jam_kirim, '%H:%i') AS time,
               izin.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, izin.dokumen
        FROM izin JOIN siswa ON siswa.id_siswa = izin.id_siswa WHERE izin.tanggal = ?
        UNION ALL
        SELECT dispen.id_dispen AS id, 'dispen' AS mode, siswa.nama_lengkap AS name, siswa.nis AS user,
               dispen.tanggal AS date, TIME_FORMAT(dispen.jam_kirim, '%H:%i') AS time,
               dispen.keterangan, NULL AS hadirSelfie, NULL AS jamPulang, NULL AS pulangSelfie, dispen.dokumen
        FROM dispen JOIN siswa ON siswa.id_siswa = dispen.id_siswa WHERE dispen.tanggal = ?
    ";

    $records = [];

    $stmt = $conn->prepare($hadirSql);
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row["status"] = "Hadir";
        $records[] = $row;
        if (!empty($row["pulangSelfie"])) {
            $pulang = $row;
            $pulang["mode"] = "pulang";
            $pulang["status"] = "Pulang";
            $pulang["time"] = $row["jamPulang"];
            $records[] = $pulang;
        }
    }
    $stmt->close();

    $stmt = $conn->prepare($nonHadirSql);
    $stmt->bind_param("sss", $tanggal, $tanggal, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row["status"] = ucfirst($row["mode"]);
        $records[] = $row;
    }
    $stmt->close();

    usort($records, function ($a, $b) {
        return strcmp(($a["time"] ?? ""), ($b["time"] ?? ""));
    });

    return $records;
}

function historyAbsensi($conn, $username) {
    $siswa = findSiswa($conn, $username);
    if (!$siswa) {
        jsonResponse(["error" => "Siswa tidak ditemukan"], 404);
    }

    $stmt = $conn->prepare("
        SELECT h.id_hadir AS id, 'hadir' AS mode, s.nama_lengkap AS name, s.nis AS user,
               h.tanggal AS date, TIME_FORMAT(h.jam_hadir, '%H:%i') AS time,
               h.keterangan, h.selfie_hadir AS hadirSelfie,
               TIME_FORMAT(h.jam_pulang, '%H:%i') AS jamPulang,
               h.selfie_pulang AS pulangSelfie
        FROM hadir h
        JOIN siswa s ON s.id_siswa = h.id_siswa
        WHERE h.id_siswa = ?
        ORDER BY h.tanggal DESC, h.jam_hadir DESC
    ");
    $stmt->bind_param("i", $siswa["id_siswa"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

if ($method === "GET") {
    $action = $_GET["action"] ?? "list";
    $tanggal = $_GET["tanggal"] ?? $today;
    error_log("Absensi GET: action=$action, tanggal=$tanggal");

    // Rekap mingguan untuk peran walas: Senin - Jumat, kode: H,S,I,A
    if ($action === "rekap_weekly") {
        $start = $_GET["start"] ?? $today; // expected Monday YYYY-MM-DD
        $kelas = trim($_GET["kelas"] ?? "11");
        $jurusan = trim($_GET["jurusan"] ?? "pplg");
        $rombel = trim($_GET["rombel"] ?? "");
        $username = trim($_GET["username"] ?? ""); // optional walas npsn

        // If username provided and matches a walas, prefer their kelas/jurusan/rombel
        if ($username !== "") {
            $stmt = $conn->prepare("SELECT kelas, jurusan, rombel FROM walas WHERE npsn = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $w = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($w) {
                    $kelas = $w["kelas"] ?? $kelas;
                    $jurusan = $w["jurusan"] ?? $jurusan;
                    $rombel = $w["rombel"] ?? $rombel;
                }
            }
        }

        $monday = DateTime::createFromFormat('Y-m-d', $start);
        if (!$monday) jsonResponse(["error" => "start harus YYYY-MM-DD"], 422);
        // ensure monday is the week's Monday (no strong enforcement, we take start..+4days)
        $endObj = clone $monday;
        $endObj->modify('+4 days');
        $end = $endObj->format('Y-m-d');

        $students = fetchStudentsByClass($conn, $kelas, $jurusan, $rombel);
        $records = fetchRangeRecordsByClass($conn, $monday->format('Y-m-d'), $end, $kelas, $jurusan, $rombel);

        // build lookup: map[id_siswa][date] => code
        $map = [];
        foreach ($records as $r) {
            $sid = $r['id_siswa'];
            $date = $r['date'];
            $mode = $r['mode'];
            $code = 'A';
            if ($mode === 'hadir') $code = 'H';
            elseif ($mode === 'sakit') $code = 'S';
            elseif ($mode === 'izin' || $mode === 'dispen') $code = 'I';

            // prefer H over others when multiple records exist
            if (!isset($map[$sid][$date]) || $map[$sid][$date] !== 'H') {
                $map[$sid][$date] = $code;
            }
        }

        // prepare days labels and dates for Monday..Friday
        $days = [];
        $d = clone $monday;
        for ($i = 0; $i < 5; $i++) {
            $days[] = $d->format('Y-m-d');
            $d->modify('+1 day');
        }

        $out = [];
        $no = 1;
        foreach ($students as $s) {
            $row = [
                'no' => $no++,
                'id_siswa' => $s['id_siswa'],
                'nis' => $s['nis'],
                'nama' => $s['nama_lengkap']
            ];
            foreach ($days as $idx => $dt) {
                $col = ['sen','sel','rab','kam','jum'][$idx];
                $row[$col] = $map[$s['id_siswa']][$dt] ?? 'A';
            }
            $out[] = $row;
        }

        jsonResponse([
            'meta' => ['kelas' => $kelas, 'jurusan' => $jurusan, 'rombel' => $rombel, 'start' => $monday->format('Y-m-d'), 'end' => $end],
            'columns' => ['no','id_siswa','nis','nama','sen','sel','rab','kam','jum'],
            'rows' => $out
        ]);
    }

    // Rekap bulanan: tulis setiap hari pada bulan itu (tidak di-skip)
    if ($action === "rekap_monthly") {
        $month = trim($_GET['month'] ?? ''); // expected YYYY-MM
        $kelas = trim($_GET["kelas"] ?? "11");
        $jurusan = trim($_GET["jurusan"] ?? "pplg");
        $rombel = trim($_GET["rombel"] ?? "");
        $username = trim($_GET["username"] ?? "");

        if ($month === '') jsonResponse(["error" => "month parameter wajib (YYYY-MM)"], 422);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) jsonResponse(["error" => "month harus YYYY-MM"], 422);

        if ($username !== "") {
            $stmt = $conn->prepare("SELECT kelas, jurusan, rombel FROM walas WHERE npsn = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $w = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($w) {
                    $kelas = $w["kelas"] ?? $kelas;
                    $jurusan = $w["jurusan"] ?? $jurusan;
                    $rombel = $w["rombel"] ?? $rombel;
                }
            }
        }

        [$y, $m] = array_map('intval', explode('-', $month, 2));
        $start = sprintf('%04d-%02d-01', $y, $m);
        $startObj = DateTime::createFromFormat('Y-m-d', $start);
        $endObj = (new DateTimeImmutable($startObj->format('Y-m-d')))->modify('last day of this month');
        $end = $endObj->format('Y-m-d');
        $daysInMonth = (int) $endObj->format('d');

        $students = fetchStudentsByClass($conn, $kelas, $jurusan, $rombel);
        $records = fetchRangeRecordsByClass($conn, $start, $end, $kelas, $jurusan, $rombel);

        $map = [];
        foreach ($records as $r) {
            $sid = $r['id_siswa'];
            $date = $r['date'];
            $mode = $r['mode'];
            $code = 'A';
            if ($mode === 'hadir') $code = 'H';
            elseif ($mode === 'sakit') $code = 'S';
            elseif ($mode === 'izin' || $mode === 'dispen') $code = 'I';
            if (!isset($map[$sid][$date]) || $map[$sid][$date] !== 'H') {
                $map[$sid][$date] = $code;
            }
        }

        $out = [];
        $no = 1;
        foreach ($students as $s) {
            $row = [
                'no' => $no++,
                'id_siswa' => $s['id_siswa'],
                'nis' => $s['nis'],
                'nama' => $s['nama_lengkap']
            ];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
                $row[(string)$d] = $map[$s['id_siswa']][$date] ?? 'A';
            }
            $out[] = $row;
        }

        $columns = array_merge(['no','id_siswa','nis','nama'], array_map('strval', range(1, $daysInMonth)));
        jsonResponse([
            'meta' => ['kelas' => $kelas, 'jurusan' => $jurusan, 'rombel' => $rombel, 'month' => $month],
            'columns' => $columns,
            'rows' => $out
        ]);
    }

    if ($action === "range") {
        $start = $_GET["start"] ?? $today;
        $end = $_GET["end"] ?? $today;
        $kelas = trim($_GET["kelas"] ?? "11");
        $jurusan = trim($_GET["jurusan"] ?? "pplg");
        $rombel = trim($_GET["rombel"] ?? "");

        jsonResponse([
            "students" => fetchStudentsByClass($conn, $kelas, $jurusan, $rombel),
            "records" => fetchRangeRecordsByClass($conn, $start, $end, $kelas, $jurusan, $rombel),
            "range" => ["start" => $start, "end" => $end, "kelas" => $kelas, "jurusan" => $jurusan, "rombel" => $rombel]
        ]);
    }

    if ($action === "status") {
        $username = trim($_GET["username"] ?? "");
        $siswa = findSiswa($conn, $username);
        if (!$siswa) {
            jsonResponse(["error" => "Siswa tidak ditemukan"], 404);
        }

        $stmt = $conn->prepare("SELECT jam_hadir, jam_pulang FROM hadir WHERE id_siswa = ? AND tanggal = ? LIMIT 1");
        $stmt->bind_param("is", $siswa["id_siswa"], $tanggal);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        jsonResponse([
            "hasHadir" => (bool) $row,
            "hasPulang" => !empty($row["jam_pulang"]),
            "tanggal" => $tanggal
        ]);
    }

    if ($action === "history") {
        $username = trim($_GET["username"] ?? "");
        jsonResponse(["records" => historyAbsensi($conn, $username)]);
    }

    jsonResponse(["records" => listAbsensi($conn, $tanggal)]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$mode = $_POST["mode"] ?? "";
$username = trim($_POST["username"] ?? "");
$keterangan = trim($_POST["keterangan"] ?? "");
error_log("Absensi POST: mode=$mode, username=$username");
$siswa = findSiswa($conn, $username);

if (!$siswa) {
    jsonResponse(["error" => "Siswa tidak ditemukan"], 404);
}

$idSiswa = (int) $siswa["id_siswa"];

if ($mode === "hadir") {
    if (date("H:i") < "05:00" || date("H:i") > "09:00") {
        jsonResponse(["error" => "Absensi hadir hanya bisa dikirim pukul 05:00 sampai 09:00"], 422);
    }

    $stmt = $conn->prepare("SELECT id_hadir FROM hadir WHERE id_siswa = ? AND tanggal = ? LIMIT 1");
    $stmt->bind_param("is", $idSiswa, $today);
    $stmt->execute();
    $existingHadir = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existingHadir) {
        jsonResponse(["error" => "Absensi hadir hari ini sudah terkirim. Silakan lanjut ke absensi pulang."], 409);
    }

    $selfie = saveUpload("selfie", "uploads/absensi", "hadir_" . $siswa["nis"]);
    if (!$selfie) {
        jsonResponse(["error" => "Selfie hadir wajib diunggah"], 422);
    }

    $stmt = $conn->prepare("
        INSERT INTO hadir (id_siswa, tanggal, jam_hadir, selfie_hadir, keterangan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $idSiswa, $today, $nowTime, $selfie, $keterangan);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true, "mode" => "hadir", "selfie" => $selfie]);
}

if ($mode === "pulang") {
    if ((int) date("H") < 14) {
        jsonResponse(["error" => "Absensi pulang baru bisa dikirim mulai pukul 14:00"], 422);
    }

    $selfie = saveUpload("selfie", "uploads/absensi", "pulang_" . $siswa["nis"]);
    if (!$selfie) {
        jsonResponse(["error" => "Selfie pulang wajib diunggah"], 422);
    }

    $stmt = $conn->prepare("UPDATE hadir SET jam_pulang = ?, selfie_pulang = ? WHERE id_siswa = ? AND tanggal = ?");
    $stmt->bind_param("ssis", $nowTime, $selfie, $idSiswa, $today);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) {
        jsonResponse(["error" => "Isi absensi hadir terlebih dahulu sebelum pulang"], 422);
    }

    jsonResponse(["ok" => true, "mode" => "pulang", "selfie" => $selfie]);
}

if (in_array($mode, ["sakit", "izin", "dispen"], true)) {
    if (in_array($mode, ["sakit", "izin"], true) && date("H:i") > "12:00") {
        jsonResponse(["error" => "Pengajuan sakit atau izin hanya bisa dikirim sampai pukul 12:00"], 422);
    }
    if ($mode === "dispen" && date("H:i") > "14:00") {
        jsonResponse(["error" => "Pengajuan dispen hanya bisa dikirim sampai pukul 14:00"], 422);
    }

    if (strlen($keterangan) < 10) {
        jsonResponse(["error" => "Keterangan minimal 10 karakter"], 422);
    }

    if ($mode === "dispen") {
        $jamMulai = trim($_POST["jam_mulai"] ?? "");
        $jamSelesai = trim($_POST["jam_selesai"] ?? "");
        if (!preg_match('/^\d{2}:\d{2}$/', $jamMulai) || !preg_match('/^\d{2}:\d{2}$/', $jamSelesai) || $jamSelesai <= $jamMulai) {
            jsonResponse(["error" => "Jam dispen tidak valid"], 422);
        }
        $keterangan = "Waktu dispen: " . $jamMulai . "-" . $jamSelesai . ". " . $keterangan;
    }

    $dokumen = saveUpload("dokumen", "uploads/dokumen", $mode . "_" . $siswa["nis"]);
    if (!$dokumen) {
        jsonResponse(["error" => "Dokumen wajib diunggah"], 422);
    }

    $sql = "
        INSERT INTO `$mode` (id_siswa, tanggal, jam_kirim, dokumen, keterangan)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE jam_kirim = VALUES(jam_kirim), dokumen = VALUES(dokumen), keterangan = VALUES(keterangan)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $idSiswa, $today, $nowTime, $dokumen, $keterangan);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true, "mode" => $mode]);
}

jsonResponse(["error" => "Mode absensi tidak valid"], 422);
