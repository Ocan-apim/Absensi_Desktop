<?php
require_once __DIR__ . "/db.php";

$conn = db();
ensureStudentProfileColumns($conn);
ensureWalasEmailColumn($conn);
ensureBkEmailColumn($conn);
ensureAdminLogs($conn);

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

function addProfileLog($conn, $actor, $targetType, $targetId, $targetLabel, $details) {
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_logs (admin_username, action, target_type, target_id, target_label, details)
        VALUES (?, 'profile_updated', ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiss", $actor, $targetType, $targetId, $targetLabel, $details);
    $stmt->execute();
    $stmt->close();
}

function normalizeRole($role) {
    $role = strtolower(trim($role));
    if ($role === "user" || $role === "siswa") return "siswa";
    if ($role === "teacher" || $role === "walas") return "walas";
    if ($role === "bk") return "bk";
    if ($role === "admin") return "admin";
    jsonResponse(["error" => "Role tidak valid"], 422);
}

function profileMeta($role) {
    if ($role === "admin") return ["table" => "admin", "id" => "id_admin", "identifier" => "username", "identifier_label" => "Username"];
    if ($role === "siswa") return ["table" => "siswa", "id" => "id_siswa", "identifier" => "nis", "identifier_label" => "NIS"];
    if ($role === "walas") return ["table" => "walas", "id" => "id_walas", "identifier" => "npsn", "identifier_label" => "NPSN"];
    return ["table" => "bk", "id" => "id_bk", "identifier" => "npsn", "identifier_label" => "NPSN"];
}

function cleanPost($key) {
    return trim($_POST[$key] ?? "");
}

function findProfile($conn, $role, $username) {
    $meta = profileMeta($role);
    if ($role === "admin") {
        $stmt = $conn->prepare("SELECT id_admin AS id, username AS identifier, email, password FROM admin WHERE username = ? OR email = ? LIMIT 1");
    } elseif ($role === "siswa") {
        $stmt = $conn->prepare("SELECT id_siswa AS id, nama_lengkap, nis AS identifier, email, password, kelas, jurusan, rombel FROM siswa WHERE nis = ? OR email = ? LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT " . $meta["id"] . " AS id, nama_lengkap, npsn AS identifier, email, password FROM " . $meta["table"] . " WHERE npsn = ? OR email = ? LIMIT 1");
    }
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonResponse(["error" => "Profil tidak ditemukan"], 404);
    }

    $fullName = $role === "admin" ? "Admin" : trim((string) ($row["nama_lengkap"] ?? ""));
    if ($fullName === "") $fullName = $row["identifier"];

    return [
        "id" => (int) $row["id"],
        "role" => $role === "siswa" ? "user" : ($role === "walas" ? "teacher" : $role),
        "tableRole" => $role,
        "nama_lengkap" => $fullName,
        "fullName" => $fullName,
        "name" => $fullName,
        "identifier" => $row["identifier"],
        "identifierLabel" => $meta["identifier_label"],
        "username" => $row["identifier"],
        "email" => $row["email"] ?? "",
        "password" => $row["password"] ?? "",
        "kelas" => $row["kelas"] ?? null,
        "jurusan" => $row["jurusan"] ?? null,
        "rombel" => $row["rombel"] ?? null,
    ];
}

$method = $_SERVER["REQUEST_METHOD"];
$role = normalizeRole($_REQUEST["role"] ?? "");
$username = trim($_REQUEST["username"] ?? "");

if ($username === "") {
    jsonResponse(["error" => "Username/NIS/NPSN wajib dikirim"], 422);
}

if ($method === "GET") {
    jsonResponse(["profile" => findProfile($conn, $role, $username)]);
}

if ($method !== "POST") {
    jsonResponse(["error" => "Method tidak didukung"], 405);
}

$current = findProfile($conn, $role, $username);
$meta = profileMeta($role);
$id = (int) $current["id"];
$nama = cleanPost("nama_lengkap");
$identifier = cleanPost("identifier");
$email = cleanPost("email");
$password = $_POST["password"] ?? "";

if ($role !== "admin" && $nama === "") {
    jsonResponse(["error" => "Nama lengkap wajib diisi"], 422);
}
if ($identifier === "" || $email === "" || $password === "") {
    $required = $role === "admin" ? $meta["identifier_label"] . ", email, dan password wajib diisi" : "Nama lengkap, " . $meta["identifier_label"] . ", email, dan password wajib diisi";
    jsonResponse(["error" => $required], 422);
}

$changed = [];
if ($role !== "admin" && $nama !== $current["nama_lengkap"]) $changed[] = "nama_lengkap";
if ($identifier !== $current["identifier"]) $changed[] = $meta["identifier"];
if ($email !== $current["email"]) $changed[] = "email";
if ($password !== $current["password"]) $changed[] = "password";

if ($role === "admin") {
    $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ?, password = ? WHERE id_admin = ?");
    $stmt->bind_param("sssi", $identifier, $email, $password, $id);
} elseif ($role === "siswa") {
    $stmt = $conn->prepare("UPDATE siswa SET nama_lengkap = ?, nis = ?, email = ?, password = ? WHERE id_siswa = ?");
    $stmt->bind_param("ssssi", $nama, $identifier, $email, $password, $id);
} else {
    $stmt = $conn->prepare("UPDATE " . $meta["table"] . " SET nama_lengkap = ?, npsn = ?, email = ?, password = ? WHERE " . $meta["id"] . " = ?");
    $stmt->bind_param("ssssi", $nama, $identifier, $email, $password, $id);
}
if (!$stmt->execute()) {
    jsonResponse(["error" => "Gagal menyimpan profil. Pastikan NIS/NPSN/email tidak duplikat."], 500);
}
$stmt->close();

if ($changed) {
    addProfileLog(
        $conn,
        $current["username"],
        $role . "_profile",
        $id,
        $nama,
        "Akun mengubah profil sendiri: " . implode(", ", $changed) . "."
    );
}

$profile = findProfile($conn, $role, $identifier);
jsonResponse(["ok" => true, "profile" => $profile]);
