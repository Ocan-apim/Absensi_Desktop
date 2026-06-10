<?php

function cloudinaryValue($key) {
    if (defined($key)) {
        return constant($key);
    }
    $value = getenv($key);
    return $value === false ? "" : trim($value);
}

function cloudinaryConfigured() {
    return cloudinaryValue("CLOUDINARY_CLOUD_NAME") !== ""
        && cloudinaryValue("CLOUDINARY_API_KEY") !== ""
        && cloudinaryValue("CLOUDINARY_API_SECRET") !== "";
}

function uploadTempFileToCloudinary($tmpName, $originalName, $folder, $publicIdPrefix, $resourceType = "auto") {
    if (!cloudinaryConfigured() || !is_file($tmpName) || !function_exists("curl_init") || !function_exists("curl_file_create")) {
        return null;
    }

    $cloudName = cloudinaryValue("CLOUDINARY_CLOUD_NAME");
    $apiKey = cloudinaryValue("CLOUDINARY_API_KEY");
    $apiSecret = cloudinaryValue("CLOUDINARY_API_SECRET");
    $timestamp = time();
    $safePrefix = preg_replace('/[^a-zA-Z0-9_\-\/]/', "_", $publicIdPrefix);
    $publicId = $safePrefix . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4));

    $params = [
        "folder" => trim($folder, "/"),
        "public_id" => $publicId,
        "timestamp" => $timestamp,
    ];
    ksort($params);
    $signatureBase = [];
    foreach ($params as $key => $value) {
        $signatureBase[] = $key . "=" . $value;
    }
    $signature = sha1(implode("&", $signatureBase) . $apiSecret);

    $mime = function_exists("mime_content_type") ? mime_content_type($tmpName) : null;
    $post = [
        "file" => curl_file_create($tmpName, $mime ?: "application/octet-stream", basename($originalName)),
        "api_key" => $apiKey,
        "folder" => $params["folder"],
        "public_id" => $params["public_id"],
        "timestamp" => $timestamp,
        "signature" => $signature,
    ];

    $url = "https://api.cloudinary.com/v1_1/" . rawurlencode($cloudName) . "/" . rawurlencode($resourceType) . "/upload";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) && !empty($data["secure_url"]) ? $data["secure_url"] : null;
}

function saveUploadWithCloudinary($field, $dir, $prefix, $folder = null) {
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] !== UPLOAD_ERR_OK) {
        return null;
    }

    $cloudUrl = uploadTempFileToCloudinary(
        $_FILES[$field]["tmp_name"],
        $_FILES[$field]["name"],
        $folder ?: $dir,
        $prefix
    );
    if ($cloudUrl) {
        return $cloudUrl;
    }

    $baseDir = __DIR__ . "/" . $dir;
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $original = basename($_FILES[$field]["name"]);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if ($ext === "") {
        $ext = "jpg";
    }

    $name = $prefix . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $target = $baseDir . "/" . $name;

    if (!move_uploaded_file($_FILES[$field]["tmp_name"], $target)) {
        return null;
    }

    return $dir . "/" . $name;
}
