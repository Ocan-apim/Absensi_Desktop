<?php
require_once __DIR__ . "/db.php";

$conn = db();
$method = $_SERVER["REQUEST_METHOD"];
ensureStudentProfileColumns($conn);
ensureWalasRombelColumn($conn);

function ensureAdminLogs($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id_log int NOT NULL AUTO_INCREMENT,
            admin_username varchar(100) DEFAULT NULL,
            action varchar(40) NOT NULL,
            target_type varchar(40) NOT NULL,
            target_id int DEFAULT NULL,
            target_label varchar(150) DEFAULT NULL,
            details text DEFAULT NULL,
            created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_log),
            KEY idx_admin_logs_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function addLog($conn, $admin, $action, $targetType, $targetId, $targetLabel, $details) {
    ensureAdminLogs($conn);
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_logs (admin_username, action, target_type, target_id, target_label, details)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssiss", $admin, $action, $targetType, $targetId, $targetLabel, $details);
    if (!$stmt->execute()) {
        jsonResponse(["error" => "Gagal menghapus data: " . $conn->error], 500);
    }
    $stmt->close();
}

function tableMeta($type) {
    // Accept both 'murid' and legacy/client alias 'siswa'
    if ($type === "murid" || $type === "siswa") {
        return ["table" => "siswa", "id" => "id_siswa", "label" => "nama_lengkap"];
    }
    if ($type === "walas") {
        return ["table" => "walas", "id" => "id_walas", "label" => "nama_lengkap"];
    }
    if ($type === "bk") {
        return ["table" => "bk", "id" => "id_bk", "label" => "nama_lengkap"];
    }
    jsonResponse(["error" => "Jenis data tidak valid: field 'type' tidak dikenali", "received_type" => $type], 422);
}

function clean($key) {
    return trim($_POST[$key] ?? "");
}

function validJurusan($jurusan) {
    $allowed = ["pplg", "mplb", "dkv", "tjkt", "pm", "ph"];
    return in_array(strtolower(trim($jurusan)), $allowed, true);
}

function normalizeJurusan($jurusan) {
    return strtolower(trim($jurusan));
}

function validKelas($kelas) {
    return in_array(trim($kelas), ["10", "11", "12"], true);
}

function validRombel($rombel) {
    return in_array(trim($rombel), ["", "1", "2"], true);
}

function makeUniqueNpsn($conn, $table, $base) {
    $base = preg_replace('/\D+/', '', $base);
    if ($base === "") {
        $base = date("ymdHis");
    }

    $candidate = $base;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT 1 FROM " . $table . " WHERE npsn = ? LIMIT 1");
        $stmt->bind_param("s", $candidate);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) return $candidate;
        $candidate = $base . str_pad((string) $counter, 2, "0", STR_PAD_LEFT);
        $counter++;
    }
}

ensureAdminLogs($conn);

if ($method === "GET") {
    $action = $_GET["action"] ?? "list";

    if ($action === "logs") {
        $result = $conn->query("
            SELECT id_log, admin_username, action, target_type, target_id, target_label, details,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
            FROM admin_activity_logs
            ORDER BY created_at DESC, id_log DESC
            LIMIT 100
        ");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        jsonResponse(["logs" => $rows]);
    }

    $type = $_GET["type"] ?? "murid";
    $meta = tableMeta($type);
    $id = (int) ($_GET["id"] ?? 0);

    if ($id > 0) {
        if ($type === "murid") {
            $stmt = $conn->prepare("SELECT id_siswa, nama_lengkap, nama_tampilan, nis, email, password, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir, created_at FROM siswa WHERE id_siswa = ? LIMIT 1");
        } elseif ($type === "walas") {
            $stmt = $conn->prepare("SELECT id_walas, nama_lengkap, npsn, email, password, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir, created_at FROM walas WHERE id_walas = ? LIMIT 1");
        } else {
            $stmt = $conn->prepare("SELECT id_bk, nama_lengkap, npsn, email, password, tempat_lahir, tanggal_lahir, created_at FROM bk WHERE id_bk = ? LIMIT 1");
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) jsonResponse(["error" => "Detail tidak ditemukan"], 404);
        jsonResponse(["record" => $row]);
    }

    if ($type === "murid") {
        $kelas = trim($_GET["kelas"] ?? "");
        $jurusan = trim($_GET["jurusan"] ?? "");
        $sql = "SELECT id_siswa, nama_lengkap, nama_tampilan, nis, email, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir, created_at FROM siswa WHERE 1=1";
        $params = [];
        $types = "";
        if ($kelas !== "") {
            if (!validKelas($kelas)) {
                jsonResponse(["error" => "Kelas tidak valid"], 422);
            }
            $sql .= " AND kelas = ?";
            $params[] = $kelas;
            $types .= "s";
        }
        if ($jurusan !== "") {
            if (!validJurusan($jurusan)) {
                jsonResponse(["error" => "Jurusan tidak valid"], 422);
            }
            $sql .= " AND LOWER(jurusan) = LOWER(?)";
            $params[] = $jurusan;
            $types .= "s";
        }
        $rombel = trim($_GET["rombel"] ?? "");
        if ($rombel !== "") {
            if (!validRombel($rombel)) {
                jsonResponse(["error" => "Rombel tidak valid"], 422);
            }
            $sql .= " AND rombel = ?";
            $params[] = $rombel;
            $types .= "s";
        }
        $sql .= " ORDER BY kelas ASC, jurusan ASC, nama_lengkap ASC";
        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else if ($type === "walas") {
        $result = $conn->query("SELECT id_walas, nama_lengkap, npsn, email, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir FROM walas ORDER BY kelas ASC, jurusan ASC, rombel ASC, nama_lengkap ASC");
    } else {
        $result = $conn->query("SELECT " . $meta["id"] . ", nama_lengkap, npsn, email, tempat_lahir, tanggal_lahir FROM " . $meta["table"] . " ORDER BY nama_lengkap ASC");
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if (isset($stmt)) $stmt->close();
    jsonResponse(["records" => $rows]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$action = $_POST["action"] ?? "";
$type = $_POST["type"] ?? "";
$admin = clean("admin_username");
$meta = tableMeta($type);
$id = (int) ($_POST["id"] ?? 0);

if ($action === "delete") {
    if ($id < 1) jsonResponse(["error" => "ID tidak valid"], 422);

    $stmt = $conn->prepare("SELECT " . $meta["label"] . " AS label FROM " . $meta["table"] . " WHERE " . $meta["id"] . " = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) jsonResponse(["error" => "Data tidak ditemukan"], 404);

    $stmt = $conn->prepare("DELETE FROM " . $meta["table"] . " WHERE " . $meta["id"] . " = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        jsonResponse(["error" => "Gagal menyimpan perubahan. Pastikan NIS/NPSN/email tidak duplikat."], 500);
    }
    $stmt->close();

    addLog($conn, $admin, "deleted", $type, $id, $row["label"], "Akun dihapus oleh admin.");
    jsonResponse(["ok" => true]);
}

if ($action === "create") {
    if ($type === "murid" || $type === "siswa") {
        $nama = clean("nama_lengkap");
        $namaTampilan = clean("nama_tampilan");
        $nis = clean("nis");
        $email = clean("email");
        $kelas = clean("kelas");
        $jurusan = clean("jurusan");
        $rombel = clean("rombel");
        $tempat = clean("tempat_lahir");
        $tanggal = clean("tanggal_lahir");
        $password = $_POST["password"] ?? "";
        $missing = [];
        if ($nama === "") $missing[] = "nama_lengkap";
        if ($nis === "") $missing[] = "nis";
        if ($email === "") $missing[] = "email";
        if ($kelas === "") $missing[] = "kelas";
        if ($jurusan === "") $missing[] = "jurusan";
        if ($tanggal === "") $missing[] = "tanggal_lahir";
        if ($password === "") $missing[] = "password";
        if ($missing) {
            jsonResponse(["error" => "Field wajib belum diisi", "missing_fields" => $missing], 422);
        }
        if (!validKelas($kelas)) {
            jsonResponse(["error" => "Kelas tidak valid", "field" => "kelas", "allowed" => ["10","11","12"]], 422);
        }
        if (!validJurusan($jurusan)) {
            jsonResponse(["error" => "Jurusan tidak valid", "field" => "jurusan", "allowed" => ["pplg","mplb","dkv","tjkt","pm","ph"]], 422);
        }
        if (!validRombel($rombel)) {
            jsonResponse(["error" => "Rombel tidak valid", "field" => "rombel", "allowed" => ["1","2"]], 422);
        }
        $jurusan = normalizeJurusan($jurusan);
        $stmt = $conn->prepare("INSERT INTO siswa (nama_lengkap, nis, email, password, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $nama, $nis, $email, $password, $kelas, $jurusan, $rombel, $tempat, $tanggal);
        if (!$stmt->execute()) {
            jsonResponse(["error" => "Gagal menyimpan. Pastikan NIS/email tidak duplikat.", "db_error" => $conn->error], 500);
        }
        $insertId = $stmt->insert_id;
        $stmt->close();
        addLog($conn, $admin, "created", $type, $insertId, $nama, "Akun dibuat oleh admin.");
        jsonResponse(["ok" => true, "id" => $insertId]);
    } else {
        $nama = clean("nama_lengkap");
        $npsn = clean("npsn");
        $tempat = clean("tempat_lahir");
        $tanggal = clean("tanggal_lahir");
        $password = $_POST["password"] ?? "";
        $kelas = clean("kelas");
        $jurusan = clean("jurusan");
        $rombel = clean("rombel");
        if ($nama === "" || $npsn === "" || $password === "" || $tanggal === "") {
            jsonResponse(["error" => "Nama, NPSN, password, dan tanggal lahir wajib diisi"], 422);
        }
        $npsn = makeUniqueNpsn($conn, $meta["table"], $npsn);
        if ($type === "walas") {
            if ($kelas === "" || $jurusan === "") {
                jsonResponse(["error" => "Kelas dan jurusan untuk walas wajib diisi"], 422);
            }
            if (!validKelas($kelas)) {
                jsonResponse(["error" => "Kelas walas tidak valid", "field" => "kelas", "allowed" => ["10","11","12"]], 422);
            }
            if (!validJurusan($jurusan)) {
                jsonResponse(["error" => "Jurusan walas tidak valid", "field" => "jurusan", "allowed" => ["pplg","mplb","dkv","tjkt","pm","ph"]], 422);
            }
            if (!validRombel($rombel)) {
                jsonResponse(["error" => "Rombel walas tidak valid", "field" => "rombel", "allowed" => ["1","2"]], 422);
            }
            $jurusan = normalizeJurusan($jurusan);
            $stmt = $conn->prepare("INSERT INTO " . $meta["table"] . " (nama_lengkap, npsn, password, kelas, jurusan, rombel, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $nama, $npsn, $password, $kelas, $jurusan, $rombel, $tempat, $tanggal);
        } else {
            $stmt = $conn->prepare("INSERT INTO " . $meta["table"] . " (nama_lengkap, npsn, password, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $npsn, $password, $tempat, $tanggal);
        }
        if (!$stmt->execute()) {
            jsonResponse(["error" => "Gagal menyimpan. Pastikan NPSN tidak duplikat.", "db_error" => $conn->error], 500);
        }
        $insertId = $stmt->insert_id;
        $stmt->close();
        addLog($conn, $admin, "created", $type, $insertId, $nama, "Akun dibuat oleh admin.");
        jsonResponse(["ok" => true, "id" => $insertId]);
    }
}

if ($action === "update") {
    if ($id < 1) jsonResponse(["error" => "ID tidak valid"], 422);

    if ($type === "murid") {
        $nama = clean("nama_lengkap");
        $nis = clean("nis");
        $email = clean("email");
        $kelas = clean("kelas");
        $jurusan = clean("jurusan");
        $rombel = clean("rombel");
        $tempat = clean("tempat_lahir");
        $tanggal = clean("tanggal_lahir");
        $password = $_POST["password"] ?? "";
        if ($nama === "" || $nis === "" || $email === "" || $kelas === "" || $jurusan === "") {
            jsonResponse(["error" => "Nama, NIS, email, kelas, dan jurusan wajib diisi"], 422);
        }
        if (!validKelas($kelas)) {
            jsonResponse(["error" => "Kelas tidak valid"], 422);
        }
        if (!validJurusan($jurusan)) {
            jsonResponse(["error" => "Jurusan tidak valid"], 422);
        }
        if (!validRombel($rombel)) {
            jsonResponse(["error" => "Rombel tidak valid"], 422);
        }
        $jurusan = normalizeJurusan($jurusan);
        $stmt = $conn->prepare("UPDATE siswa SET nama_lengkap=?, nama_tampilan=?, nis=?, email=?, password=?, kelas=?, jurusan=?, rombel=?, tempat_lahir=?, tanggal_lahir=? WHERE id_siswa=?");
        $stmt->bind_param("ssssssssssi", $nama, $namaTampilan, $nis, $email, $password, $kelas, $jurusan, $rombel, $tempat, $tanggal, $id);
        $label = $nama;
    } else {
        $nama = clean("nama_lengkap");
        $npsn = clean("npsn");
        $email = clean("email");
        $tempat = clean("tempat_lahir");
        $tanggal = clean("tanggal_lahir");
        $password = $_POST["password"] ?? "";
        $kelas = clean("kelas");
        $jurusan = clean("jurusan");
        $rombel = clean("rombel");
        if ($nama === "" || $npsn === "" || $email === "" || $password === "") {
            jsonResponse(["error" => "Nama, NPSN, email, dan password wajib diisi"], 422);
        }
        if ($type === "walas") {
            if ($kelas === "" || $jurusan === "") {
                jsonResponse(["error" => "Kelas dan jurusan untuk walas wajib diisi"], 422);
            }
            if (!validKelas($kelas)) {
                jsonResponse(["error" => "Kelas walas tidak valid", "field" => "kelas", "allowed" => ["10","11","12"]], 422);
            }
            if (!validJurusan($jurusan)) {
                jsonResponse(["error" => "Jurusan walas tidak valid", "field" => "jurusan", "allowed" => ["pplg","mplb","dkv","tjkt","pm","ph"]], 422);
            }
            if (!validRombel($rombel)) {
                jsonResponse(["error" => "Rombel walas tidak valid", "field" => "rombel", "allowed" => ["1","2"]], 422);
            }
            $jurusan = normalizeJurusan($jurusan);
            $stmt = $conn->prepare("UPDATE " . $meta["table"] . " SET nama_lengkap=?, npsn=?, email=?, password=?, kelas=?, jurusan=?, rombel=?, tempat_lahir=?, tanggal_lahir=? WHERE " . $meta["id"] . "=?");
            $stmt->bind_param("sssssssssi", $nama, $npsn, $email, $password, $kelas, $jurusan, $rombel, $tempat, $tanggal, $id);
        } else {
            $stmt = $conn->prepare("UPDATE " . $meta["table"] . " SET nama_lengkap=?, npsn=?, email=?, password=?, tempat_lahir=?, tanggal_lahir=? WHERE " . $meta["id"] . "=?");
            $stmt->bind_param("ssssssi", $nama, $npsn, $email, $password, $tempat, $tanggal, $id);
        }
        $label = $nama;
    }

    $stmt->execute();
    $stmt->close();
    addLog($conn, $admin, "edited", $type, $id, $label, "Akun diedit. Klik untuk melihat detail data terbaru.");
    jsonResponse(["ok" => true]);
}

jsonResponse(["error" => "Aksi tidak valid"], 422);
