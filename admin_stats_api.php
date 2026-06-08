<?php
/**
 * Statistik dashboard admin: siswa hadir vs siswa tidak hadir, distribusi jurusan.
 *
 * GET params:
 *   username (wajib) — harus cocok dengan baris di tabel `admin` (username atau email).
 *   chart_month (opsional) — YYYY-MM, default bulan dari week_start / hari ini.
 *   week_start (opsional) — YYYY-MM-DD (tanggal apa pun dalam minggu yang dipilih); default Sen minggu ini.
 *
 */
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Max-Age: 86400");
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$username = trim($_GET["username"] ?? "");
if ($username === "") {
    jsonResponse(["error" => "Parameter username wajib"], 422);
}

$conn = db();

$stmt = $conn->prepare("SELECT 1 FROM admin WHERE username = ? OR email = ? LIMIT 1");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$ok = (bool) $stmt->get_result()->fetch_row();
$stmt->close();

if (!$ok) {
    jsonResponse(["error" => "Akses ditolak"], 403);
}

$tz = new DateTimeZone("Asia/Jakarta");
$today = new DateTime("now", $tz);

$weekStartRaw = trim($_GET["week_start"] ?? "");
if ($weekStartRaw !== "") {
    $weekAnchor = DateTime::createFromFormat("Y-m-d", $weekStartRaw, $tz);
    if (!$weekAnchor) {
        jsonResponse(["error" => "week_start harus YYYY-MM-DD"], 422);
    }
} else {
    $weekAnchor = clone $today;
}

$dow = (int) $weekAnchor->format("w");
$diffToMonday = $dow === 0 ? -6 : 1 - $dow;
$monday = clone $weekAnchor;
$monday->modify((string) $diffToMonday . " days");
$monday->setTime(0, 0, 0);
$sunday = clone $monday;
$sunday->modify("+6 days");

$chartMonth = trim($_GET["chart_month"] ?? "");
if ($chartMonth === "") {
    $chartMonth = $monday->format("Y-m");
}
if (!preg_match('/^\d{4}-\d{2}$/', $chartMonth)) {
    jsonResponse(["error" => "chart_month harus YYYY-MM"], 422);
}

[$y, $m] = array_map("intval", explode("-", $chartMonth, 2));
$monthStart = (new DateTimeImmutable(sprintf("%04d-%02d-01", $y, $m), $tz))->format("Y-m-d");
$monthEndObj = (new DateTimeImmutable($monthStart, $tz))->modify("last day of this month");
$monthEnd = $monthEndObj->format("Y-m-d");
$daysInMonth = (int) $monthEndObj->format("d");

$weekFrom = $monday->format("Y-m-d");
$weekTo = $sunday->format("Y-m-d");
$jurusanFilter = strtolower(trim($_GET["jurusan"] ?? ""));
$jurusanSql = $jurusanFilter !== "" ? " AND LOWER(TRIM(s.jurusan)) = ?" : "";

function countStudentsInScope($conn, $jurusanFilter) {
    $sql = "SELECT COUNT(*) AS cnt FROM siswa s WHERE 1=1";
    if ($jurusanFilter !== "") {
        $sql .= " AND LOWER(TRIM(s.jurusan)) = ?";
    }
    $stmt = $conn->prepare($sql);
    if ($jurusanFilter !== "") {
        $stmt->bind_param("s", $jurusanFilter);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row["cnt"] ?? 0);
}

$totalStudentsInScope = countStudentsInScope($conn, $jurusanFilter);

function fillDaysMap($conn, $sql, $types, $params, $daysInMonth) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $byDay = [];
    while ($row = $res->fetch_assoc()) {
        $byDay[(int) $row["d"]] = (int) $row["cnt"];
    }
    $stmt->close();
    $out = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $out[] = ["day" => $d, "count" => $byDay[$d] ?? 0];
    }
    return $out;
}

$siswaByDay = fillDaysMap(
    $conn,
    "SELECT DAY(tanggal) AS d, COUNT(*) AS cnt
     FROM hadir h
     JOIN siswa s ON s.id_siswa = h.id_siswa
     WHERE tanggal BETWEEN ? AND ?" . $jurusanSql . "
     GROUP BY DAY(tanggal)",
    $jurusanFilter !== "" ? "sss" : "ss",
    $jurusanFilter !== "" ? [$monthStart, $monthEnd, $jurusanFilter] : [$monthStart, $monthEnd],
    $daysInMonth
);

$areaByDay = [];
for ($i = 0; $i < count($siswaByDay); $i++) {
    $hadirCount = $siswaByDay[$i]["count"];
    $tidakHadirCount = max(0, $totalStudentsInScope - $hadirCount);
    $areaByDay[] = [
        "day" => $siswaByDay[$i]["day"],
        "guru" => $tidakHadirCount,
        "tidak_hadir" => $tidakHadirCount,
        "siswa" => $hadirCount,
        "hadir" => $hadirCount,
    ];
}

$labels = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];

function weekdayBars($conn, $table, $weekFrom, $weekTo, $jurusanFilter = "") {
    global $labels;
    $jurusanSql = $jurusanFilter !== "" ? " AND LOWER(TRIM(s.jurusan)) = ?" : "";
    $sql = "SELECT WEEKDAY(tanggal) AS wd, COUNT(*) AS cnt
            FROM `$table` t
            JOIN siswa s ON s.id_siswa = t.id_siswa
            WHERE tanggal BETWEEN ? AND ?" . $jurusanSql . "
            GROUP BY WEEKDAY(tanggal)";
    $stmt = $conn->prepare($sql);
    if ($jurusanFilter !== "") {
        $stmt->bind_param("sss", $weekFrom, $weekTo, $jurusanFilter);
    } else {
        $stmt->bind_param("ss", $weekFrom, $weekTo);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $map = array_fill(0, 7, 0);
    while ($row = $res->fetch_assoc()) {
        $map[(int) $row["wd"]] = (int) $row["cnt"];
    }
    $stmt->close();
    $out = [];
    for ($i = 0; $i < 7; $i++) {
        $out[] = ["label" => $labels[$i], "count" => $map[$i]];
    }
    return $out;
}

$weekBarsSiswa = weekdayBars($conn, "hadir", $weekFrom, $weekTo, $jurusanFilter);
$weekBarsTidakHadir = array_map(function ($row) use ($totalStudentsInScope) {
    return [
        "label" => $row["label"],
        "count" => max(0, $totalStudentsInScope - (int) $row["count"]),
    ];
}, $weekBarsSiswa);

$canonical = ["PM", "DKV", "MPLB", "TJKT", "PPLG", "PH"];
$aliases = [
    "pemrograman" => "PPLG",
    "pplg" => "PPLG",
    "tjkt" => "TJKT",
    "tav" => "TJKT",
    "dkv" => "DKV",
    "desain" => "DKV",
    "mplb" => "MPLB",
    "pm" => "PM",
    "ph" => "PH",
    "perhotelan" => "PH",
];

$res = $conn->query("SELECT LOWER(TRIM(jurusan)) AS j, COUNT(*) AS c FROM siswa GROUP BY LOWER(TRIM(jurusan))");
$bucket = array_fill_keys($canonical, 0);
$unmapped = 0;
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $key = $row["j"] ?? "";
        $c = (int) $row["c"];
        if ($key === "") {
            $unmapped += $c;
            continue;
        }
        $norm = strtoupper($key);
        if (in_array($norm, $canonical, true)) {
            $bucket[$norm] += $c;
            continue;
        }
        if (isset($aliases[$key])) {
            $bucket[$aliases[$key]] += $c;
            continue;
        }
        $unmapped += $c;
    }
}

$totalSiswa = array_sum($bucket) + $unmapped;
$jurusan = [];
foreach ($canonical as $code) {
    $cnt = $bucket[$code];
    $pct = $totalSiswa > 0 ? round($cnt * 100 / $totalSiswa, 1) : 0;
    $jurusan[] = [
        "name" => $code,
        "count" => $cnt,
        "value" => $pct,
    ];
}

jsonResponse([
    "meta" => [
        "chart_month" => $chartMonth,
        "month_range" => ["start" => $monthStart, "end" => $monthEnd],
        "week_range" => ["start" => $weekFrom, "end" => $weekTo],
        "total_siswa" => $totalSiswa,
        "siswa_tanpa_jurusan_terpetakan" => $unmapped,
    ],
    "areaByDay" => $areaByDay,
    "weekBars" => [
        "guru" => $weekBarsTidakHadir,
        "tidak_hadir" => $weekBarsTidakHadir,
        "siswa" => $weekBarsSiswa,
        "hadir" => $weekBarsSiswa,
    ],
    "jurusan" => $jurusan,
]);
