<?php
// --- BACKEND: API LOGIC ---
if (isset($_GET['action']) && $_GET['action'] == 'scan') {
    header('Content-Type: application/json');
    $target = $_POST['url'] ?? '';
    
    if (empty($target)) {
        echo json_encode(['error' => 'No URL provided. Please enter a valid target.']);
        exit;
    }

    $target = trim($target);
    if (!preg_match("~^(?:f|ht)tps?://~i", $target)) {
        $target = "http://" . $target;
    }
    $parsed = parse_url($target);
    $base_url = $parsed['scheme'] . "://" . $parsed['host'] . (isset($parsed['port']) ? ":" . $parsed['port'] : "");

    $paths = [
        "c/portal.php",
        "portal.php",
        "server/load.php",
        "stalker_portal/server/load.php",
        "stalker_portal/c/portal.php",
        "c/server/load.php",
        "mag/c/portal.php"
    ];

    $multi_handle = curl_multi_init();
    $curl_handles = [];

    foreach ($paths as $path) {
        $full_url = $base_url . "/" . $path;
        $ch = curl_init($full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp');
        
        curl_multi_add_handle($multi_handle, $ch);
        $curl_handles[$path] = $ch;
    }

    $active = null;
    do {
        $status = curl_multi_exec($multi_handle, $active);
        if ($active) {
            curl_multi_select($multi_handle);
        }
    } while ($active && $status == CURLM_OK);

    $found = [];
    foreach ($curl_handles as $path => $ch) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content = curl_multi_getcontent($ch);
        $full_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        if ($http_code == 200 || strpos($content, 'JsHttpRequest') !== false) {
            $found[] = [
                'path' => $path,
                'url' => $full_url,
                'status' => $http_code
            ];
        }
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($multi_handle);
    echo json_encode(['base' => $base_url, 'results' => $found]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ＲＫ | ＩＰＴＶ🌈™ - Advanced Portal Scanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #050505;
            --card-bg: #111111;
            --primary: #00f2fe;
            --primary-glow: rgba(0, 242, 254, 0.4);
            --secondary: #4facfe;
            --success: #00e676;
            --success-bg: rgba(0, 230, 118, 0.1);
            --error: #ff1744;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border-color: #333333;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            background-image: radial-gradient(circle at 50% 0%, #1a1a2e 0%, transparent 50%);
        }

        .container {
            width: 100%;
            max-width: 700px;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .title {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
        }

        .subtitle {
            color: var(--text-muted);
            margin-top: 10px;
            font-size: 1rem;
        }

        .subtitle a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .subtitle a:hover {
            text-decoration: underline;
        }

        .search-box {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }

        .input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            flex: 1;
            padding: 16px 20px;
            background: #000;
            border: 2px solid var(--border-color);
            color: var(--text-main);
            font-family: 'Fira Code', monospace;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="text"]::placeholder {
            color: #555;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        button {
            padding: 0 30px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-transform: uppercase;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--primary-glow);
        }

        button:disabled {
            background: #333;
            color: #777;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .results-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .status-message {
            text-align: center;
            font-family: 'Fira Code', monospace;
            color: var(--text-muted);
            margin-top: 20px;
        }

        .result-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--success);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .path-url {
            font-family: 'Fira Code', monospace;
            font-size: 0.95rem;
            word-break: break-all;
            color: var(--success);
        }

        .badge {
            background: var(--success-bg);
            color: var(--success);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            border: 1px solid rgba(0, 230, 118, 0.3);
        }

        .suggestion {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px 15px;
            border-radius: 6px;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            color: #bc8cff;
            display: inline-block;
            margin-top: 5px;
        }

        .error-card {
            border-left-color: var(--error);
        }
        .error-card .path-url {
            color: var(--error);
        }

        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1 class="title">ＲＫ | ＩＰＴＶ🌈™</h1>
        <div class="subtitle">Advanced Portal Scanner • <a href="https://t.me/iptvm3y" target="_blank">Join Channel</a></div>
    </div>

    <div class="search-box">
        <form id="scanForm">
            <div class="input-wrapper">
                <label for="targetUrl">Target Host / IP</label>
                <div class="input-group">
                    <input type="text" id="targetUrl" placeholder="http://example.com:8080" required autocomplete="off">
                    <button type="submit" id="scanBtn">Scan</button>
                </div>
            </div>
        </form>
    </div>

    <div id="resultsBox" class="results-container">
        </div>
</div>

<script>
document.getElementById('scanForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('scanBtn');
    const urlInput = document.getElementById('targetUrl').value;
    const resultsBox = document.getElementById('resultsBox');
    
    // UI Loading State
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div>';
    resultsBox.innerHTML = `<div class="status-message">Scanning targets on <strong>${urlInput}</strong>... Please wait.</div>`;

    try {
        const formData = new FormData();
        formData.append('url', urlInput);

        const response = await fetch('?action=scan', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        resultsBox.innerHTML = ''; // Clear status message
        
        if (data.error) {
            resultsBox.innerHTML = `
                <div class="result-card error-card">
                    <div class="path-url">Error: ${data.error}</div>
                </div>`;
            return;
        }

        if (data.results.length > 0) {
            let html = `<div class="status-message" style="margin-bottom: 20px; color: #a0a0a0;">Scan complete for: <span style="color: #fff;">${data.base}</span></div>`;
            
            data.results.forEach(res => {
                let suggestionHtml = '';
                if (res.path.includes('stalker_portal') || res.path.includes('portal.php')) {
                    suggestionHtml = `<div class="suggestion">👉 Extracted Path: <strong>/${res.path}</strong></div>`;
                }

                html += `
                <div class="result-card">
                    <div class="result-header">
                        <div class="path-url">${res.url}</div>
                        <div class="badge">HTTP ${res.status}</div>
                    </div>
                    ${suggestionHtml}
                </div>`;
            });
            resultsBox.innerHTML = html;
        } else {
            resultsBox.innerHTML = `
                <div class="result-card error-card">
                    <div class="path-url">No valid Stalker/Xtream portals found on this host.</div>
                </div>`;
        }

    } catch (err) {
        resultsBox.innerHTML = `
            <div class="result-card error-card">
                <div class="path-url">Network Error: Could not reach the scanning backend.</div>
            </div>`;
    } finally {
        btn.disabled = false;
        btn.innerText = "SCAN";
    }
});
</script>

</body>
</html>
