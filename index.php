<?php
$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
$file = __DIR__ . DIRECTORY_SEPARATOR . ltrim($path, "/");

if ($path !== "/" && is_file($file)) {
    return false;
}

if ($path === "/" || $path === "/index.php") {
    readfile(__DIR__ . "/companyprofile.html");
    return true;
}

http_response_code(404);
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan</title>
</head>
<body>
    <p>Halaman tidak ditemukan. Kembali ke <a href="/">beranda</a>.</p>
</body>
</html>
