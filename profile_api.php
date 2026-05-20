<?php
require_once __DIR__ . "/db.php";

$conn = db();
ensureStudentProfileColumns($conn);

function profileByUsername($conn, $username) {
    $stmt = $conn->prepare("
        SELECT id_siswa, nama_lengkap, nama_tampilan, nis, email, kelas, jurusan, rombel
        FROM siswa
        WHERE nis = ? OR email = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonResponse(["error" => "Profil siswa tidak ditemukan"], 404);
    }

    return [
        "id" => $row["id_siswa"],
        "username" => $row["nis"],
        "email" => $row["email"],
        "fullName" => $row["nama_lengkap"],
        "displayName" => $row["nama_tampilan"] ?: $row["nama_lengkap"],
        "kelas" => $row["kelas"],
        "jurusan" => $row["jurusan"],
        "rombel" => $row["rombel"]
    ];
}

$method = $_SERVER["REQUEST_METHOD"];
$username = trim($_REQUEST["username"] ?? "");

if ($username === "") {
    jsonResponse(["error" => "Username/NIS wajib dikirim"], 422);
}

if ($method === "GET") {
    jsonResponse(["profile" => profileByUsername($conn, $username)]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$displayName = trim($_POST["nama_tampilan"] ?? "");
if ($displayName === "") {
    jsonResponse(["error" => "Nama tampilan wajib diisi"], 422);
}
$displayNameLength = function_exists("mb_strlen") ? mb_strlen($displayName) : strlen($displayName);
if ($displayNameLength > 150) {
    jsonResponse(["error" => "Nama tampilan maksimal 150 karakter"], 422);
}

$stmt = $conn->prepare("UPDATE siswa SET nama_tampilan = ? WHERE nis = ? OR email = ?");
$stmt->bind_param("sss", $displayName, $username, $username);
if (!$stmt->execute()) {
    jsonResponse(["error" => "Gagal menyimpan profil"], 500);
}
$stmt->close();

jsonResponse(["ok" => true, "profile" => profileByUsername($conn, $username)]);
