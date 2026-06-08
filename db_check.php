<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

$expectedToken = getenv("DEBUG_TOKEN") ?: "";
$givenToken = $_GET["token"] ?? "";

if ($expectedToken !== "" && !hash_equals($expectedToken, $givenToken)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    exit;
}

$conn = db();

$tables = ["admin", "siswa", "walas", "bk"];
$counts = [];

foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    $counts[$table] = $result ? (int) $result->fetch_assoc()["total"] : null;
}

echo json_encode([
    "ok" => true,
    "database" => $conn->query("SELECT DATABASE() AS db")->fetch_assoc()["db"] ?? null,
    "host_info" => $conn->host_info,
    "tables" => $counts
]);
