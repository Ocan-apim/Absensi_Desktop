<?php
require_once __DIR__ . "/db.php";

$conn = db();
ensureStudentProfileColumns($conn);
ensureWalasRombelColumn($conn);

$username = trim($_POST['username'] ?? ''); // NIS/NPSN/email/username
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(["error" => "Isi identitas dan kata sandi."]);
    exit;
}

function passwordMatches($plainPassword, $storedPassword) {
    if ($storedPassword === null || $storedPassword === '') {
        return false;
    }

    return password_verify($plainPassword, $storedPassword) || hash_equals($storedPassword, $plainPassword);
}

function findUser($conn, $role, $username) {
    switch ($role) {
        case 'admin':
            $sql = "SELECT id_admin AS id, username, email, password, 'admin' AS auth_role, 'admin' AS table_role
                    FROM admin
                    WHERE username = ? OR email = ?
                    LIMIT 1";
            break;
        case 'user':
        case 'siswa':
            $sql = "SELECT id_siswa AS id, nama_lengkap, nama_tampilan, nis, email, password, kelas, jurusan, rombel, 'user' AS auth_role, 'siswa' AS table_role
                    FROM siswa
                    WHERE nis = ? OR email = ?
                    LIMIT 1";
            break;
        case 'teacher':
            $sql = "SELECT id_walas AS id, nama_lengkap, npsn, password, kelas, jurusan, rombel, 'teacher' AS auth_role, 'walas' AS table_role
                    FROM walas
                    WHERE npsn = ?
                    LIMIT 1";
            break;
        case 'walas':
            $sql = "SELECT id_walas AS id, nama_lengkap, npsn, password, kelas, jurusan, rombel, 'teacher' AS auth_role, 'walas' AS table_role
                    FROM walas
                    WHERE npsn = ?
                    LIMIT 1";
            break;
        case 'bk':
            $sql = "SELECT id_bk AS id, nama_lengkap, npsn, password, 'bk' AS auth_role, 'bk' AS table_role
                    FROM bk
                    WHERE npsn = ?
                    LIMIT 1";
            break;
        default:
            return null;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($role === 'admin' || $role === 'user' || $role === 'siswa') {
        $stmt->bind_param("ss", $username, $username);
    } else {
        $stmt->bind_param("s", $username);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

$rolesToCheck = $role !== '' ? [$role] : ['admin', 'user', 'walas', 'bk'];
$validRoles = ['admin', 'user', 'siswa', 'teacher', 'walas', 'bk'];

if ($role !== '' && !in_array($role, $validRoles, true)) {
    echo json_encode(["error" => "Role tidak valid"]);
    exit;
}

$matchedUser = null;

foreach ($rolesToCheck as $candidateRole) {
    $candidate = findUser($conn, $candidateRole, $username);
    if ($candidate && passwordMatches($password, $candidate['password'] ?? null)) {
        $matchedUser = $candidate;
        break;
    }
}

if (!$matchedUser) {
    echo json_encode(["error" => "Identitas atau kata sandi salah"]);
    exit;
}

$identifier = $matchedUser['nis'] ?? $matchedUser['npsn'] ?? $matchedUser['username'] ?? $matchedUser['email'];

echo json_encode([
    "id" => $matchedUser['id'] ?? null,
    "username" => $identifier,
    "name" => ($matchedUser['nama_tampilan'] ?? '') ?: ($matchedUser['nama_lengkap'] ?? $matchedUser['username'] ?? ''),
    "fullName" => $matchedUser['nama_lengkap'] ?? '',
    "displayName" => $matchedUser['nama_tampilan'] ?? '',
    "role" => $matchedUser['auth_role'],
    "tableRole" => $matchedUser['table_role'],
    "kelas" => $matchedUser['kelas'] ?? null,
    "jurusan" => $matchedUser['jurusan'] ?? null,
    "rombel" => $matchedUser['rombel'] ?? null
]);
