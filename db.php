<?php
date_default_timezone_set("Asia/Jakarta");

// Load environment variables from .env.php if it exists
if (file_exists(__DIR__ . "/.env.php")) {
    require_once __DIR__ . "/.env.php";
}

function db() {
    $host = getenv("MYSQLHOST") ?: getenv("DB_HOST") ?: (defined("DB_HOST") ? DB_HOST : "127.0.0.1");
    $user = getenv("MYSQLUSER") ?: getenv("DB_USER") ?: (defined("DB_USER") ? DB_USER : "root");
    $password = getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD") ?: (defined("DB_PASS") ? DB_PASS : "");
    $database = getenv("MYSQLDATABASE") ?: getenv("DB_NAME") ?: (defined("DB_NAME") ? DB_NAME : "absensi");
    $port = (int) (getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: (defined("DB_PORT") ? DB_PORT : 3306));

    $conn = new mysqli($host, $user, $password, $database, $port);
     if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Koneksi database gagal"]);
        exit;
    }

    $conn->set_charset("utf8mb4");
    ensureWalasEmailColumn($conn);
    ensureBkEmailColumn($conn);
    return $conn;
}

function dbColumnExists($conn, $table, $column) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result ? (bool) $result->fetch_assoc() : false;
}

function dbTryAlter($conn, $sql) {
    try {
        $conn->query($sql);
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() !== 1060) {
            throw $e;
        }
    }
}

function ensureStudentProfileColumns($conn) {
    if (!dbColumnExists($conn, "siswa", "nama_tampilan")) {
        dbTryAlter($conn, "ALTER TABLE siswa ADD COLUMN nama_tampilan varchar(150) DEFAULT NULL AFTER nama_lengkap");
    }
    if (!dbColumnExists($conn, "siswa", "rombel")) {
        dbTryAlter($conn, "ALTER TABLE siswa ADD COLUMN rombel varchar(10) DEFAULT NULL AFTER jurusan");
    }

    $conn->query("
        UPDATE siswa
        SET rombel = '2'
        WHERE nis = '00916'
          AND (rombel IS NULL OR rombel = '')
          AND kelas = '11'
          AND LOWER(jurusan) = 'pplg'
    ");
}

function ensureWalasRombelColumn($conn) {
    if (!dbColumnExists($conn, "walas", "rombel")) {
        dbTryAlter($conn, "ALTER TABLE walas ADD COLUMN rombel varchar(10) DEFAULT NULL AFTER jurusan");
    }

    $result = $conn->query("SELECT id_walas, nama_lengkap, kelas, jurusan, rombel FROM walas ORDER BY kelas ASC, jurusan ASC, id_walas ASC");
    if (!$result) {
        return;
    }

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row["kelas"] . "|" . strtolower(trim($row["jurusan"]));
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $row;
    }

    foreach ($groups as $group) {
        $has1 = false;
        $has2 = false;
        $blanks = [];
        foreach ($group as $row) {
            $rombel = trim((string) $row["rombel"]);
            if ($rombel === "1") {
                $has1 = true;
            } elseif ($rombel === "2") {
                $has2 = true;
            } else {
                $blanks[] = $row;
            }
        }

        if (!$has1 && count($blanks) > 0) {
            $row = array_shift($blanks);
            $stmt = $conn->prepare("UPDATE walas SET rombel = '1' WHERE id_walas = ?");
            $stmt->bind_param("i", $row["id_walas"]);
            $stmt->execute();
            $stmt->close();
            $has1 = true;
        }

        if (!$has2 && count($blanks) > 0) {
            $row = array_shift($blanks);
            $stmt = $conn->prepare("UPDATE walas SET rombel = '2' WHERE id_walas = ?");
            $stmt->bind_param("i", $row["id_walas"]);
            $stmt->execute();
            $stmt->close();
            $has2 = true;
        }
    }

    $conn->query("DELETE FROM walas WHERE nama_lengkap LIKE '% (Rombel 2)'");
}

function ensureWalasEmailColumn($conn) {
    if (!dbColumnExists($conn, "walas", "email")) {
        dbTryAlter($conn, "ALTER TABLE walas ADD COLUMN email varchar(100) DEFAULT NULL UNIQUE AFTER password");
    }
}

function ensureBkEmailColumn($conn) {
    if (!dbColumnExists($conn, "bk", "email")) {
        dbTryAlter($conn, "ALTER TABLE bk ADD COLUMN email varchar(100) DEFAULT NULL UNIQUE AFTER password");
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}
