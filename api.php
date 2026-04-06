<?php
// ===== HEADERS =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// Preflight (important for some browsers)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== CONFIG =====
$url = "https://servertvhub.site/api/channels.json";
$cacheFile = __DIR__ . "/cache.json";
$cacheTime = 30; // seconds (fast + fresh)

// ===== CACHE CHECK =====
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    readfile($cacheFile);
    exit;
}

// ===== FETCH =====
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        "User-Agent: Mozilla/5.0",
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ===== ERROR HANDLE =====
if ($httpCode !== 200 || !$response) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch source"]);
    exit;
}

// ===== CLEAN JSON =====
$data = json_decode($response, true);

// Remove non-channel entries
$channels = array_values(array_filter($data, function($c) {
    return isset($c['mpd']);
}));

$output = json_encode($channels);

// ===== SAVE CACHE =====
file_put_contents($cacheFile, $output);

// ===== OUTPUT =====
echo $output;
?>
