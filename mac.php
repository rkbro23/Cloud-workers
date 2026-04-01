<?php
$portal = 'http://alpha-2ott.me/';
$mac = '00:1A:79:60:0F:BD';

$self_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}";
$channel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

function curl_get($url, $headers, $cookies = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (!empty($cookies)) {
        $cookie_str = '';
        foreach ($cookies as $k => $v) $cookie_str .= "$k=$v; ";
        curl_setopt($ch, CURLOPT_COOKIE, trim($cookie_str));
    }
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    if (curl_errno($ch)) return null;
    curl_close($ch);
    return $response;
}

function get_genres($portal, $headers, $cookies, $mac) {
    $url = "$portal/portal.php?type=itv&action=get_genres&JsHttpRequest=1-xml&mac=$mac";
    $response = curl_get($url, $headers, $cookies);
    if (!$response) return [];

    $json = json_decode($response, true);
    $genres_data = $json['js'] ?? [];

    $genres = [];
    foreach ($genres_data as $genre) {
        if (isset($genre['id']) && isset($genre['title'])) {
            $genres[$genre['id']] = $genre['title'];
        }
    }
    return $genres;
}

// Step 1: Get token
$handshake_url = "$portal/portal.php?type=stb&action=handshake&JsHttpRequest=1-xml&mac=$mac";
$cookies = [
    'timezone' => 'GMT',
    'stb_lang' => 'en',
    'mac' => $mac,
];
$headers = [
    'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'
];
$response = curl_get($handshake_url, $headers, $cookies);
if (!$response) exit("Failed to get token.");
$json = json_decode($response, true);
$token = $json['js']['token'] ?? null;
if (!$token) exit("No token found.");

$cookies['token'] = $token;
$headers[] = "Authorization: Bearer $token";

// Step 2: If channel is requested, get its stream
if ($channel_id > 0) {
    $cmd = urlencode("ffmpeg http://localhost/ch/{$channel_id}_");
    $link_url = "$portal/portal.php?type=itv&action=create_link&cmd=$cmd&JsHttpRequest=1-xml&mac=$mac";
    $response = curl_get($link_url, $headers, $cookies);
    if (!$response) exit("Failed to get stream URL.");
    $json = json_decode($response, true);
    $full_cmd = $json['js']['cmd'] ?? null;
    if (!$full_cmd) exit("Stream link not found.");

    $stream_url = preg_replace('/^ffmpeg\s+/', '', $full_cmd);
    $stream_url = preg_replace('/ts\b/i', 'm3u8', $stream_url);

    header("Content-Type: text/plain");
    header("Location: $stream_url");
    exit;
}

// Step 3: Get genres (categories)
$genres = get_genres($portal, $headers, $cookies, $mac);

// Step 4: Get channel list
$channel_list_url = "$portal/portal.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml&mac=$mac";
$response = curl_get($channel_list_url, $headers, $cookies);
if (!$response) exit("Failed to get channel list.");

$json = json_decode($response, true);
$channels = $json['js']['data'] ?? [];

header("Content-Type: application/x-mpegurl");
echo "#EXTM3U\n";

foreach ($channels as $ch) {
    $name = $ch['name'] ?? 'Unknown';
    $id = $ch['id'] ?? 0;
    $logo = $ch['logo'] ?? '';
    $genre_id = $ch['tv_genre_id'] ?? null;
    $group = $genres[$genre_id] ?? 'Live';

    if ($id > 0) {
        echo "#EXTINF:-1 tvg-id=\"$id\" tvg-logo=\"$logo\" group-title=\"$group\",$name\n";
        echo "$self_url?id=$id\n";
    }
}
?>
