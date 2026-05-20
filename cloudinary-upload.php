<?php
/**
 * Cloudinary Upload Helper
 * Handles image uploads to Cloudinary for public URL access
 */

require_once __DIR__ . '/db.php';

/**
 * Upload a file to Cloudinary
 * @param string $filePath Local file path
 * @param string $publicId Public ID for the image (e.g., "hadir_12345")
 * @return array|false Array with 'url' on success, false on failure
 */
function uploadToCloudinary($filePath, $publicId = null) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
    $apiKey = getenv('CLOUDINARY_API_KEY') ?: $_ENV['CLOUDINARY_API_KEY'] ?? null;
    $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: $_ENV['CLOUDINARY_API_SECRET'] ?? null;

    if (!$cloudName || !$apiKey || !$apiSecret) {
        return false;
    }

    if (!file_exists($filePath)) {
        return false;
    }

    if (!function_exists('curl_init') || !function_exists('curl_file_create')) {
        error_log("Cloudinary upload skipped: PHP cURL extension is not available");
        return false;
    }

    $apiUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
    
    $timestamp = time();
    $params = [
        'folder' => 'absensi',
        'public_id' => $publicId ?? 'absensi_' . bin2hex(random_bytes(8)),
        'timestamp' => $timestamp,
    ];

    // Generate authentication signature
    ksort($params);
    $signatureParts = [];
    foreach ($params as $key => $value) {
        $signatureParts[] = "{$key}={$value}";
    }
    $paramsStr = implode("&", $signatureParts);
    
    $signature = sha1($paramsStr . $apiSecret);
    $params['api_key'] = $apiKey;
    $params['signature'] = $signature;

    // Prepare multipart form data
    $cFile = curl_file_create($filePath, mime_content_type($filePath));
    $postData = [];
    foreach ($params as $key => $value) {
        $postData[$key] = $value;
    }
    $postData['file'] = $cFile;

    // Make API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        error_log("Cloudinary upload failed: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Cloudinary upload failed: HTTP {$httpCode}");
        return false;
    }

    $result = json_decode($response, true);
    if (!isset($result['secure_url'])) {
        error_log("Cloudinary response missing secure_url: " . $response);
        return false;
    }

    return [
        'url' => $result['secure_url'],
        'public_id' => $result['public_id'],
        'source' => 'cloudinary'
    ];
}

/**
 * Modified saveUpload function that tries Cloudinary first, falls back to local
 * @param string $field Form field name
 * @param string $dir Local directory (fallback)
 * @param string $prefix File prefix for public_id
 * @return string|false Public URL or local path on success, false on failure
 */
function saveUploadWithCloudinary($field, $dir, $prefix) {
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] !== UPLOAD_ERR_OK) {
        return false;
    }

    $file = $_FILES[$field];
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($ext), ["jpg", "jpeg", "png", "gif", "pdf"])) {
        return false;
    }

    // Try Cloudinary first
    $result = uploadToCloudinary(
        $file["tmp_name"],
        $prefix . "_" . time()
    );

    if ($result && isset($result['url'])) {
        return $result['url'];
    }

    // Fallback to local storage
    $baseDir = __DIR__ . "/" . trim($dir, "/");
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }
    
    $filename = $prefix . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $target = $baseDir . "/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $target)) {
        return false;
    }

    return trim($dir, "/") . "/" . $filename;
}
?>
