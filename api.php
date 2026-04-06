<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Cache file
$cacheFile = "cache.json";
$cacheTime = 60; // seconds

// Use cache if fresh
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
    echo file_get_contents($cacheFile);
    exit;
}

// Fetch from API
$url = "https://servertvhub.site/api/channels.json";
$data = file_get_contents($url);

// Save cache
file_put_contents($cacheFile, $data);

echo $data;
?>
