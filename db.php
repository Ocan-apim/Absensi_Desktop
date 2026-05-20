<?php
date_default_timezone_set("Asia/Jakarta");

// Load environment variables from .env.php if it exists
if (file_exists(__DIR__ . "/.env.php")) {
    require_once __DIR__ . "/.env.php";
}

function db() {
    $conn = new mysqli("127.0.0.1", "root", "", "absensi");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Koneksi database gagal"]);
        exit;
    }
    $conn->set_charset("utf8mb4");
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
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}
