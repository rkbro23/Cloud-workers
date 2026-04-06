<?php
// ==========================
// 🔐 CORS HEADERS
// ==========================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==========================
// ⚙️ CONFIG
// ==========================
$SOURCE_URL = "https://servertvhub.site/api/channels.json";
$CACHE_FILE = __DIR__ . "/cache.json";
$CACHE_TIME = 60; // seconds

// Optional headers
$REFERER = "https://www.jiotv.com/";
$ORIGIN  = "https://www.jiotv.com/";

// ==========================
// ⚡ FUNCTION: SEND JSON
// ==========================
function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================
// ⚡ FUNCTION: LOAD CACHE
// ==========================
function load_cache($file, $maxAge) {
    if (file_exists($file) && (time() - filemtime($file) < $maxAge)) {
        return file_get_contents($file);
    }
    return false;
}

// ==========================
// ⚡ FUNCTION: SAVE CACHE
// ==========================
function save_cache($file, $data) {
    @file_put_contents($file, $data);
}

// ==========================
// ⚡ STEP 1: TRY CACHE FIRST
// ==========================
$cached = load_cache($CACHE_FILE, $CACHE_TIME);
if ($cached !== false) {
    echo $cached;
    exit;
}

// ==========================
// 🌐 STEP 2: FETCH VIA CURL
// ==========================
$ch = curl_init($SOURCE_URL);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,

    // SSL bypass (if needed)
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,

    // Headers
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "User-Agent: Mozilla/5.0",
        "Referer: $REFERER",
        "Origin: $ORIGIN"
    ]
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// ==========================
// ❌ STEP 3: ERROR HANDLING
// ==========================
if ($error) {
    // fallback to cache if exists
    if (file_exists($CACHE_FILE)) {
        echo file_get_contents($CACHE_FILE);
        exit;
    }
    send_json([
        "error" => "cURL Error",
        "details" => $error
    ], 500);
}

// Only allow 2xx responses
if ($httpCode < 200 || $httpCode >= 300) {
    if (file_exists($CACHE_FILE)) {
        echo file_get_contents($CACHE_FILE);
        exit;
    }
    send_json([
        "error" => "HTTP Error",
        "status" => $httpCode
    ], $httpCode);
}

// ==========================
// 📦 STEP 4: JSON PROCESSING
// ==========================
$data = json_decode($response, true);

if (!is_array($data)) {
    if (file_exists($CACHE_FILE)) {
        echo file_get_contents($CACHE_FILE);
        exit;
    }
    send_json([
        "error" => "Invalid JSON response"
    ], 500);
}

// Filter valid channels (must have mpd)
$channels = array_values(array_filter($data, function($item) {
    return is_array($item) && isset($item['mpd']);
}));

// ==========================
// ⚡ STEP 5: FINAL OUTPUT
// ==========================
$output = json_encode($channels, JSON_UNESCAPED_SLASHES);

// Save cache
save_cache($CACHE_FILE, $output);

// Return clean JSON
echo $output;
?>
