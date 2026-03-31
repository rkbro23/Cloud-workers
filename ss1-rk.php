<?php
/**
 * HLS Reverse Proxy – Bulletproof Version (With Domain Whitelist)
 * * - Fetches and rewrites .m3u8 playlists (no‑cache)
 * - Proxies .ts segments with long‑term caching
 * - Uses cURL for reliable HTTP handling, with fallback to file_get_contents
 * - Handles relative/absolute URLs, CORS, and Cloudflare User‑Agent spoofing
 * - Restricted to specific domain (Whitelist)
 */

// -------------------------------------------------------------------
// 1. Basic environment tuning
// -------------------------------------------------------------------
@set_time_limit(0);          // for segment downloads, but we rely on timeouts below
@ignore_user_abort(false);   // let the client abort stop the script
while (ob_get_level()) ob_end_clean();

// -------------------------------------------------------------------
// 2. Configuration – CHANGE THESE
// -------------------------------------------------------------------
$SOURCE = "https://usb-pos-learning-looks.trycloudflare.com";   // your tunnel URL (no trailing slash)
$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36";
$PLAYLIST_FILE = "live.m3u8";                                  // main playlist relative to SOURCE

// -------------------------------------------------------------------
// 3. Domain Whitelist & Headers
// -------------------------------------------------------------------
$ALLOWED_ORIGINS = [
    "http://rkbro23.github.io",
    "https://rkbro23.github.io"
];

// Determine where the request is coming from (Origin or Referer)
$request_origin = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $request_origin = rtrim($_SERVER['HTTP_ORIGIN'], '/');
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
    $request_origin = ($parsed_url['scheme'] ?? 'http') . '://' . ($parsed_url['host'] ?? '');
}

// Check if the origin is in our whitelist
if (!in_array($request_origin, $ALLOWED_ORIGINS)) {
    http_response_code(403);
    die("Access Denied: Proxy is not authorized for this domain, If u Wanted to Add Your Website In Whitelist Contact @Nubcarder on telegram. ");
}

// Send restrictive CORS headers to allow only the whitelisted origin
header("Access-Control-Allow-Origin: " . $request_origin);
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Connection: close");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") exit;

// -------------------------------------------------------------------
// 4. Helper: Build absolute URL from base and relative link
// -------------------------------------------------------------------
function abs_url($base, $rel) {
    if (parse_url($rel, PHP_URL_SCHEME)) return $rel;            // already absolute
    if (substr($rel, 0, 1) == "/") return rtrim($base, '/') . $rel;
    return rtrim($base, '/') . '/' . $rel;
}

// -------------------------------------------------------------------
// 5. Helper: Fetch remote content with cURL (preferred) or file_get_contents
// -------------------------------------------------------------------
function fetch_remote($url, $timeout = 10, $as_stream = false) {
    $options = [
        'http' => [
            'method'        => 'GET',
            'header'        => "User-Agent: " . $GLOBALS['UA'] . "\r\nAccept: */*\r\nConnection: close\r\n",
            'timeout'       => $timeout,
            'follow_location'=> 1,
            'max_redirects' => 5,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ];
    $context = stream_context_create($options);
    
    if ($as_stream) {
        $fp = @fopen($url, 'rb', false, $context);
        return $fp;   // return handle for chunked reading
    } else {
        return @file_get_contents($url, false, $context);
    }
}

// -------------------------------------------------------------------
// 6. Self URL (for rewriting segment links)
// -------------------------------------------------------------------
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https://" : "http://";
$self = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

// -------------------------------------------------------------------
// 7. Route: segment request (?u=...)
// -------------------------------------------------------------------
if (isset($_GET['u'])) {
    $segment_url = $_GET['u'];
    $segment_url = abs_url($SOURCE, $segment_url);
    
    // Determine MIME type – only .ts needs special header
    $ext = strtolower(pathinfo(parse_url($segment_url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if ($ext == 'ts') {
        header("Content-Type: video/mp2t");
        header("Cache-Control: public, max-age=86400");  // cache 1 day
    } else {
        // fallback (shouldn't happen, but safe)
        header("Content-Type: application/octet-stream");
        header("Cache-Control: public, max-age=86400");
    }
    
    // Open stream to source segment
    $fp = fetch_remote($segment_url, 15, true);  // timeout 15s, stream mode
    if (!$fp) {
        http_response_code(502);
        die("Segment fetch failed");
    }
    
    // Stream chunk by chunk, flush immediately
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
    exit;
}

// -------------------------------------------------------------------
// 8. Default: playlist request – fetch, rewrite, serve
// -------------------------------------------------------------------
$playlist_url = rtrim($SOURCE, '/') . '/' . ltrim($PLAYLIST_FILE, '/');
$m3u8_content = fetch_remote($playlist_url, 8);   // shorter timeout for playlist

if (!$m3u8_content) {
    http_response_code(502);
    die("Playlist fetch failed");
}

// Set playlist headers – NO CACHE
header("Content-Type: application/vnd.apple.mpegurl");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Rewrite each line: keep tags, replace segment URLs with proxy links
$lines = explode("\n", $m3u8_content);
foreach ($lines as $line) {
    $line = rtrim($line);
    if ($line === '') continue;
    
    // If it's a comment/tag, output unchanged
    if ($line[0] == '#') {
        echo $line . "\n";
        continue;
    }
    
    // It's a segment URI – rewrite it
    // Remove any preceding source path if present (to keep relative)
    $line = str_replace($SOURCE, '', $line);
    $line = ltrim($line, '/');
    
    // Build proxy URL
    $proxy_url = $self . '?u=' . urlencode($line);
    echo $proxy_url . "\n";
}
exit;
?>
