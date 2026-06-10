<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/cloudinary-upload.php";

$conn = db();
$method = $_SERVER["REQUEST_METHOD"];

if (!function_exists("jsonResponse")) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data);
        exit;
    }
}

function tableExists($conn, $table) {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$safe'");
    return $res && $res->num_rows > 0;
}

function replyTable($conn) {
    if (tableExists($conn, "laporan_balasan")) return "laporan_balasan";
    if (tableExists($conn, "balasan_laporan")) return "balasan_laporan";
    return "laporan_balasan";
}

function ensureLaporanSchema($conn) {
    if (tableExists($conn, "laporan")) {
        @$conn->query("ALTER TABLE laporan MODIFY status enum('dikirim','dibalas','diselesaikan') NOT NULL DEFAULT 'dikirim'");
        $hasLampiran = $conn->query("SHOW COLUMNS FROM laporan LIKE 'lampiran'");
        if ($hasLampiran && $hasLampiran->num_rows < 1) {
            @$conn->query("ALTER TABLE laporan ADD COLUMN lampiran TEXT NULL AFTER isi_laporan");
        } else {
            @$conn->query("ALTER TABLE laporan MODIFY lampiran TEXT NULL");
        }
    }

    $replyTable = replyTable($conn);
    if (tableExists($conn, $replyTable)) {
        @$conn->query("ALTER TABLE `$replyTable` MODIFY pengirim_role enum('bk','walas') NOT NULL");
    }
}

ensureLaporanSchema($conn);
$replyTable = replyTable($conn);

function allowedLampiranExtension($tmpName, $originalName) {
    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "application/pdf" => "pdf",
        "application/msword" => "doc",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
    ];
    $mime = mime_content_type($tmpName);
    if (isset($allowed[$mime])) {
        return $allowed[$mime];
    }
    $ext = strtolower(pathinfo($originalName ?? "", PATHINFO_EXTENSION));
    $extMap = ["jpg" => "jpg", "jpeg" => "jpg", "png" => "png", "webp" => "webp", "pdf" => "pdf", "doc" => "doc", "docx" => "docx"];
    if (!isset($extMap[$ext])) {
        jsonResponse(["error" => "Lampiran harus berupa PNG, JPG, WEBP, PDF, DOC, atau DOCX"], 422);
    }
    return $extMap[$ext];
}

function normalizeLampiranFiles($field) {
    if (empty($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (is_array($file["name"] ?? null)) {
        $items = [];
        foreach ($file["name"] as $index => $name) {
            $error = $file["error"][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            $items[] = [
                "name" => $name,
                "tmp_name" => $file["tmp_name"][$index] ?? "",
                "size" => (int) ($file["size"][$index] ?? 0),
                "error" => $error,
            ];
        }
        return $items;
    }
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    return [[
        "name" => $file["name"] ?? "",
        "tmp_name" => $file["tmp_name"] ?? "",
        "size" => (int) ($file["size"] ?? 0),
        "error" => $file["error"] ?? UPLOAD_ERR_OK,
    ]];
}

function saveLaporanLampiran($field) {
    $files = normalizeLampiranFiles($field);
    if (!$files) return null;
    if (count($files) > 5) {
        jsonResponse(["error" => "Lampiran maksimal 5 file"], 422);
    }
    $totalSize = array_sum(array_map(function ($file) {
        return (int) ($file["size"] ?? 0);
    }, $files));
    if ($totalSize > 10 * 1024 * 1024) {
        jsonResponse(["error" => "Total lampiran maksimal 10MB"], 422);
    }

    $dir = __DIR__ . "/uploads/laporan";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $paths = [];
    foreach ($files as $file) {
        if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            jsonResponse(["error" => "Gagal mengunggah lampiran. Pastikan total ukuran file maksimal 10MB."], 422);
        }
        if (!is_uploaded_file($file["tmp_name"] ?? "")) {
            jsonResponse(["error" => "Lampiran tidak valid"], 422);
        }
        $ext = allowedLampiranExtension($file["tmp_name"], $file["name"]);
        $name = "laporan_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = $dir . "/" . $name;
        $cloudUrl = uploadTempFileToCloudinary($file["tmp_name"], $file["name"], "uploads/laporan", pathinfo($name, PATHINFO_FILENAME));
        if ($cloudUrl) {
            $paths[] = $cloudUrl;
            continue;
        }
        if (!move_uploaded_file($file["tmp_name"], $target)) {
            jsonResponse(["error" => "Gagal mengunggah lampiran"], 500);
        }
        $paths[] = "uploads/laporan/" . $name;
    }
    return count($paths) === 1 ? $paths[0] : json_encode($paths);
}

function lampiranList($value) {
    if (!$value) return [];
    $decoded = json_decode($value, true);
    if (is_array($decoded)) {
        return array_values(array_filter($decoded, function ($item) {
            return is_string($item) && trim($item) !== "";
        }));
    }
    return [trim((string) $value)];
}

function findWalas($conn, $username) {
    $stmt = $conn->prepare("SELECT id_walas, nama_lengkap, npsn FROM walas WHERE npsn = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function collectLaporanItems($result) {
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $id = (int) $row["id_laporan"];
        if (!isset($items[$id])) {
            $items[$id] = [
                "id_laporan" => $id,
                "tujuan_role" => $row["tujuan_role"],
                "subjek" => $row["subjek"],
                "isi_laporan" => $row["isi_laporan"],
                "lampiran" => $row["lampiran"] ?? null,
                "lampiran_list" => lampiranList($row["lampiran"] ?? null),
                "status" => $row["status"],
                "created_at" => $row["created_at"],
                "walas_nama" => $row["walas_nama"] ?? null,
                "walas_npsn" => $row["walas_npsn"] ?? null,
                "id_bk" => isset($row["id_bk"]) ? (int) $row["id_bk"] : null,
                "bk_nama" => $row["bk_nama"] ?? null,
                "bk_npsn" => $row["bk_npsn"] ?? null,
                "latest_reply_role" => null,
                "balasan" => [],
            ];
        }
        if (!empty($row["isi_balasan"])) {
            $items[$id]["latest_reply_role"] = $row["pengirim_role"];
            $items[$id]["balasan"][] = [
                "pengirim_role" => $row["pengirim_role"],
                "isi_balasan" => $row["isi_balasan"],
                "created_at" => $row["dibalas_at"],
            ];
        }
    }
    return array_values($items);
}

function laporanSelectSql($replyTable, $whereSql) {
    return "
        SELECT l.id_laporan, l.tujuan_role, l.id_bk, l.subjek, l.isi_laporan, l.lampiran, l.status,
               DATE_FORMAT(l.created_at, '%Y-%m-%d %H:%i') AS created_at,
               w.nama_lengkap AS walas_nama, w.npsn AS walas_npsn,
               bk_target.nama_lengkap AS bk_nama, bk_target.npsn AS bk_npsn,
               b.isi_balasan,
               DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') AS dibalas_at,
               b.pengirim_role
        FROM laporan l
        LEFT JOIN walas w ON w.id_walas = l.id_walas
        LEFT JOIN bk bk_target ON bk_target.id_bk = l.id_bk
        LEFT JOIN `$replyTable` b ON b.id_laporan = l.id_laporan
        $whereSql
        ORDER BY l.created_at DESC, b.created_at ASC
    ";
}

if ($method === "GET") {
    if (($_GET["action"] ?? "") === "admin_bk" || ($_GET["role"] ?? "") === "bk") {
        $stmt = $conn->prepare(laporanSelectSql($replyTable, "WHERE l.tujuan_role = 'bk'"));
        $stmt->execute();
        $result = $stmt->get_result();
        $items = collectLaporanItems($result);
        jsonResponse(["laporan" => $items, "reports" => $items]);
    }

    $username = trim($_GET["username"] ?? "");
    $walas = findWalas($conn, $username);
    if (!$walas) {
        jsonResponse(["laporan" => []]);
    }

    $stmt = $conn->prepare(laporanSelectSql($replyTable, "WHERE l.id_walas = ?"));
    $stmt->bind_param("i", $walas["id_walas"]);
    $stmt->execute();
    $result = $stmt->get_result();
    jsonResponse(["laporan" => collectLaporanItems($result)]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$action = $_POST["action"] ?? "create";

if ($action === "create") {
    $username = trim($_POST["username"] ?? "");
    $idBk = (int) ($_POST["id_bk"] ?? 0);
    $subjek = trim($_POST["subjek"] ?? "");
    $isi = trim($_POST["isi_laporan"] ?? "");
    $walas = findWalas($conn, $username);

    if (!$walas) jsonResponse(["error" => "Walas tidak ditemukan"], 404);
    if ($idBk < 1) jsonResponse(["error" => "Tujuan laporan (petugas BK) harus dipilih"], 422);
    if ($subjek === "") jsonResponse(["error" => "Subjek laporan wajib diisi"], 422);
    if ($isi === "") jsonResponse(["error" => "Isi laporan wajib diisi"], 422);

    $checkBk = $conn->prepare("SELECT id_bk FROM bk WHERE id_bk = ? LIMIT 1");
    $checkBk->bind_param("i", $idBk);
    $checkBk->execute();
    if (!$checkBk->get_result()->fetch_assoc()) {
        $checkBk->close();
        jsonResponse(["error" => "Petugas BK tidak ditemukan"], 404);
    }
    $checkBk->close();

    $lampiran = saveLaporanLampiran("lampiran");
    $tujuanRole = "bk";
    $stmt = $conn->prepare("
        INSERT INTO laporan (id_walas, tujuan_role, id_bk, subjek, isi_laporan, lampiran)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isisss", $walas["id_walas"], $tujuanRole, $idBk, $subjek, $isi, $lampiran);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true]);
}

if ($action === "reply") {
    $idLaporan = (int) ($_POST["id_laporan"] ?? 0);
    $pengirimRole = $_POST["pengirim_role"] ?? "";
    $isiBalasan = trim($_POST["isi_balasan"] ?? "");
    $idBk = (int) ($_POST["id_bk"] ?? 0);

    if ($idLaporan < 1 || !in_array($pengirimRole, ["bk", "walas"], true)) {
        jsonResponse(["error" => "Data balasan tidak valid"], 422);
    }
    if (strlen($isiBalasan) < 2) {
        jsonResponse(["error" => "Balasan wajib diisi"], 422);
    }

    $authorId = $pengirimRole === "bk" ? $idBk : null;
    $stmt = $conn->prepare("
        INSERT INTO `$replyTable` (id_laporan, pengirim_role, id_bk, isi_balasan)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isis", $idLaporan, $pengirimRole, $authorId, $isiBalasan);
    $stmt->execute();
    $stmt->close();

    $nextStatus = "dibalas";
    $stmt = $conn->prepare("UPDATE laporan SET status = ? WHERE id_laporan = ?");
    $stmt->bind_param("si", $nextStatus, $idLaporan);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true]);
}

if ($action === "approve") {
    $idLaporan = (int) ($_POST["id_laporan"] ?? 0);
    $pengirimRole = $_POST["pengirim_role"] ?? "";
    if ($pengirimRole !== "walas" || $idLaporan < 1) {
        jsonResponse(["error" => "Data approval tidak valid"], 422);
    }

    $checkReply = $conn->prepare("SELECT COUNT(*) AS count FROM `$replyTable` WHERE id_laporan = ? AND pengirim_role = 'bk'");
    $checkReply->bind_param("i", $idLaporan);
    $checkReply->execute();
    $replyRow = $checkReply->get_result()->fetch_assoc();
    $checkReply->close();
    if ((int) ($replyRow["count"] ?? 0) < 1) {
        jsonResponse(["error" => "BK harus memberikan balasan terlebih dahulu sebelum menyelesaikan laporan"], 422);
    }

    $stmt = $conn->prepare("UPDATE laporan SET status = 'diselesaikan' WHERE id_laporan = ?");
    $stmt->bind_param("i", $idLaporan);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true]);
}

jsonResponse(["error" => "Action tidak valid"], 422);
