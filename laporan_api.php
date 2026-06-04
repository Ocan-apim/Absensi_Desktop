<?php
require_once __DIR__ . "/db.php";

$conn = db();
$method = $_SERVER["REQUEST_METHOD"];

function ensureLaporanSchema($conn) {
    $conn->query("ALTER TABLE laporan MODIFY status enum('dikirim','dibalas','diselesaikan') NOT NULL DEFAULT 'dikirim'");
    $conn->query("ALTER TABLE laporan_balasan MODIFY pengirim_role enum('bk','walas') NOT NULL");
}

ensureLaporanSchema($conn);

function findWalas($conn, $username) {
    $stmt = $conn->prepare("SELECT id_walas, nama_lengkap, npsn FROM walas WHERE npsn = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function firstTargetId($conn, $role) {
    $result = $conn->query("SELECT id_bk AS id FROM bk ORDER BY id_bk ASC LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? (int) $row["id"] : null;
}

function collectLaporanItems($result, $role) {
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row["id_laporan"];
        if (!isset($items[$id])) {
            $items[$id] = [
                "id_laporan" => $id,
                "tujuan_role" => $row["tujuan_role"],
                "subjek" => $row["subjek"],
                "isi_laporan" => $row["isi_laporan"],
                "status" => $row["status"],
                "created_at" => $row["created_at"],
                "walas_nama" => $row["walas_nama"] ?? null,
                "walas_npsn" => $row["walas_npsn"] ?? null,
                "balasan" => [],
                "latest_reply_role" => null,
                "latest_reply_at" => null,
                "has_unread" => false
            ];
        }
        if ($row["isi_balasan"] !== null) {
            $items[$id]["balasan"][] = [
                "pengirim_role" => $row["pengirim_role"],
                "isi_balasan" => $row["isi_balasan"],
                "created_at" => $row["dibalas_at"]
            ];
            $items[$id]["latest_reply_role"] = $row["pengirim_role"];
            $items[$id]["latest_reply_at"] = $row["dibalas_at"];
        }
    }

    foreach ($items as &$item) {
        $item["has_unread"] = $role === "bk" ? ($item["status"] === "dikirim") : ($item["status"] === "dibalas");
    }
    unset($item);

    return array_values($items);
}

if ($method === "GET") {
    if (($_GET["role"] ?? "") === "bk") {
        $stmt = $conn->prepare("
            SELECT l.id_laporan, l.tujuan_role, l.subjek, l.isi_laporan, l.status,
                   DATE_FORMAT(l.created_at, '%Y-%m-%d %H:%i') AS created_at,
                   w.nama_lengkap AS walas_nama, w.npsn AS walas_npsn,
                   b.isi_balasan,
                   DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') AS dibalas_at,
                   b.pengirim_role
            FROM laporan l
            JOIN walas w ON w.id_walas = l.id_walas
            LEFT JOIN laporan_balasan b ON b.id_laporan = l.id_laporan
            WHERE l.tujuan_role = 'bk'
            ORDER BY l.created_at DESC, b.created_at ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        jsonResponse(["laporan" => collectLaporanItems($result, "bk")]);
    }

    $username = trim($_GET["username"] ?? "");
    $walas = findWalas($conn, $username);
    if (!$walas) {
        jsonResponse(["error" => "Walas tidak ditemukan"], 404);
    }

    $stmt = $conn->prepare("
        SELECT l.id_laporan, l.tujuan_role, l.subjek, l.isi_laporan, l.status,
               DATE_FORMAT(l.created_at, '%Y-%m-%d %H:%i') AS created_at,
               w.nama_lengkap AS walas_nama, w.npsn AS walas_npsn,
               b.isi_balasan,
               DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') AS dibalas_at,
               b.pengirim_role
        FROM laporan l
        JOIN walas w ON w.id_walas = l.id_walas
        LEFT JOIN laporan_balasan b ON b.id_laporan = l.id_laporan
        WHERE l.id_walas = ?
        ORDER BY l.created_at DESC, b.created_at ASC
    ");
    $stmt->bind_param("i", $walas["id_walas"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    jsonResponse(["laporan" => collectLaporanItems($result, "walas")]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$action = $_POST["action"] ?? "create";

if ($action === "create") {
    $username = trim($_POST["username"] ?? "");
    $tujuan = $_POST["tujuan_role"] ?? "";
    $subjek = trim($_POST["subjek"] ?? "");
    $isi = trim($_POST["isi_laporan"] ?? "");
    $walas = findWalas($conn, $username);

    if (!$walas) {
        jsonResponse(["error" => "Walas tidak ditemukan"], 404);
    }
    if ($tujuan !== "bk") {
        jsonResponse(["error" => "Tujuan laporan tidak valid"], 422);
    }
    if ($subjek === "" || strlen($isi) < 10) {
        jsonResponse(["error" => "Subjek dan isi laporan wajib diisi"], 422);
    }

    $idBk = $tujuan === "bk" ? firstTargetId($conn, "bk") : null;

    $stmt = $conn->prepare("
        INSERT INTO laporan (id_walas, tujuan_role, id_bk, subjek, isi_laporan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isiss", $walas["id_walas"], $tujuan, $idBk, $subjek, $isi);
    $stmt->execute();
    $stmt->close();

    jsonResponse(["ok" => true]);
}

if ($action === "reply") {
    $idLaporan = (int) ($_POST["id_laporan"] ?? 0);
    $pengirimRole = $_POST["pengirim_role"] ?? "";
    $isiBalasan = trim($_POST["isi_balasan"] ?? "");
    $idBk = (int) ($_POST["id_bk"] ?? 0);
    $idWalas = (int) ($_POST["id_walas"] ?? 0);

    if (!in_array($pengirimRole, ["bk", "walas"], true) || $idLaporan < 1 || strlen($isiBalasan) < 2) {
        jsonResponse(["error" => "Data balasan tidak valid"], 422);
    }

    $stmt = $conn->prepare("SELECT status FROM laporan WHERE id_laporan = ? LIMIT 1");
    $stmt->bind_param("i", $idLaporan);
    $stmt->execute();
    $laporanRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$laporanRow) {
        jsonResponse(["error" => "Laporan tidak ditemukan"], 404);
    }

    // Allow BK to reply regardless of status (enable back-and-forth conversation)

    $stmt = $conn->prepare("
        INSERT INTO laporan_balasan (id_laporan, pengirim_role, id_bk, isi_balasan)
        VALUES (?, ?, ?, ?)
    ");
    $authorId = $pengirimRole === "bk" ? $idBk : null;
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

    $stmt = $conn->prepare("UPDATE laporan SET status = 'diselesaikan' WHERE id_laporan = ?");
    $stmt->bind_param("i", $idLaporan);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) {
        jsonResponse(["error" => "Laporan tidak ditemukan"], 404);
    }

    jsonResponse(["ok" => true]);
}

jsonResponse(["error" => "Aksi laporan tidak valid"], 422);
