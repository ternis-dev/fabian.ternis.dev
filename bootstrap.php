<?php 
date_default_timezone_set('Europe/Berlin');

if (($_SERVER['REQUEST_URI'] ?? '') === '/todo') { die('Seems like you found a part of this website that is not working (yet)'); }
elseif (($_SERVER['REQUEST_URI'] ?? '') === '/wow') { die('Wow – you found a secret page'); }

require_once __DIR__.'/src/helpers.php';
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}

// Load environment variables if Dotenv is installed and .env exists
if (class_exists(\Dotenv\Dotenv::class) && file_exists(__DIR__.'/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}
require_once __DIR__.'/src/API/base.php';
require_once __DIR__.'/src/API/turnstile.php';
require_once __DIR__.'/src/API/cloudflare.php';
require_once __DIR__.'/src/API/twinsonicelink.php'; // should i do this ... ill keep it for now
require_once __DIR__.'/src/API/github.php';
require_once __DIR__.'/src/API/hackclubcdn.php'; // still wondering ...

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$safePath = str_replace(['..', '//'], '', $requestPath);

$getMimeType = function($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match($ext) {
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'text/javascript; charset=UTF-8',
        'ttf'  => 'font/ttf',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        default => 'application/octet-stream',
    };
};

$serveFile = function($filePath, $mimeType) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
};

// 1. Direct asset routing (css, js, fonts, img)
$assetDirs = ['css', 'js', 'fonts', 'img'];
$cleanAssetPath = preg_replace('#^/assets/(css|js|fonts|img)?#', '', $safePath);

foreach ($assetDirs as $dir) {
    $targetFile = __DIR__ . '/assets/' . $dir . $cleanAssetPath;
    if (file_exists($targetFile) && is_file($targetFile)) {
        $serveFile($targetFile, $getMimeType($targetFile));
    }
}

// 2. Dynamic image extension resolver (.unknown.image.mime)
if (str_ends_with($safePath, '.unknown.image.mime')) {
    $basePath = substr($cleanAssetPath, 0, -strlen('.unknown.image.mime'));
    $basePath = preg_replace('#^/img#', '', $basePath);
    $extensions = ['webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml'];

    foreach ($extensions as $ext => $mime) {
        $candidate = __DIR__ . '/assets/img' . $basePath . '.' . $ext;
        if (file_exists($candidate) && is_file($candidate)) {
            $serveFile($candidate, $mime);
        }
    }

    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Image not found';
    exit;
}

// 3. Static asset 404 handling
$assetExtensions = ['css', 'js', 'ttf', 'woff', 'woff2', 'jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'ico'];
$ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
if (in_array($ext, $assetExtensions, true)) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Asset not found';
    exit;
}



use App\API\{DomainBox, Turnstile, StoryGrab, TwinsOnIceLink, GitHub, HackClubCDN, ApiRouter, hackAI};
use App\Docs\DocsController;
use App\Services\{CacheService, DatabaseService};

// from now on using $api_ for better access ...
$dnbx = new DomainBox();
$turnstile = new Turnstile();
$turnstileResult = null;
// // // S:Service, API:self
$s_['cache'] = cache();
$s_['db'] = db();
$api_['cache'] = $s_['cache'];
$api_['db'] = $s_['db'];
$api_['icelink'] = new TwinsOnIceLink();
$api_['github'] = new GitHub();
$api_['hackclub_cdn'] = new HackClubCDN();
$api_['dnbx'] = $dnbx ?? new DomainBox();
$api_['hackclub_ai'] = new hackAI();

// Handle API requests (production host api.fabian.ternis.dev or dev path /api)
if (ApiRouter::isApiRequest()) {
    $apiRouter = new ApiRouter($s_['cache'], $s_['db']);
    $apiRouter->dispatch();
}

// Handle Documentation requests (/docs)
if (DocsController::isDocsRequest()) {
    $docsController = new DocsController($s_['cache']);
    $docsController->handle($safePath);
}


if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cf-turnstile-response'])) {
    $token = $_POST['cf-turnstile-response'] ?? '';
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
    $turnstileResult = $turnstile->verify($token, $remoteIp);

    // If request is AJAX/API, return JSON
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
        header('Content-Type: application/json');
        echo json_encode($turnstileResult);
        exit;
    }
}

// Cache active domains for 10 minutes (600s)
$domains = cache()->remember('dnbx_active_domains', 600, function() use ($dnbx) {
    $res = $dnbx->getMyDomain(['status' => 'active', 'limit' => 999]);
    return is_array($res) ? ($res['data'] ?? []) : [];
}) ?? [];

// Cache latest registered domain for 10 minutes (600s)
$latest_domain = cache()->remember('dnbx_latest_domain', 600, function() use ($api_) {
    return $api_['dnbx']->getLatestDomain();
}) ?? [];

$devices = config('devices', []);
$hi = "Hello World!";

// Cache StoryGrab stories for 5 minutes (300s)
$storygrab_api = new StoryGrab(env('STORYGRAB_API_TOKEN'));
$stories = cache()->remember('storygrab_latest_stories', 300, function() use ($storygrab_api) {
    $res = $storygrab_api->getLatestStoriesFromProfile('ternisfabian', 999);
    return is_array($res) ? ($res['data'] ?? []) : [];
}) ?? [];

// Cache latest GitHub commit for 5 minutes (300s)
$latest_commit = cache()->remember('github_latest_user_commit', 300, function() use ($api_) {
    return $api_['github']->getLastUserCommit('fabianternis');
}) ?? [];

// usort($domains, function($a, $b) {
//     return strtotime($a['expires_at']) <=> strtotime($b['expires_at']);
// });

// Todo: generalize this and just use array ... ('suffix', 'path', 'mime')
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabian Ternis - Personal Website</title>
    <!-- Whyever this is anotehr unicode ... ? ... -->
    <link rel="stylesheet" href="app.css">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=7">  ???-->
    <meta name="keywords" content="Fabian Ternis, ternis.dev, Web developer, StoryGrab, twins-on-ice Website, twinsonice website, ternis.net, Ternis HomeLab">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
</head>
<body>

    <!-- <a rel="me" href="https://chaos.social/@ternis">Mastodon</a> -->

    <div class="theme-select-container">
        <select name="theme" id="theme-select">
        </select>
    </div>
    <div id="live-time-container">
        <span class="location-indicator">Europe/Berlin</span>:
        <span id="live-time-display"></span>
        <span id="live-time-emoji"></span>
    </div>
    <div id="github-star-container">
        <div id="github-star-action">Todo: CSS, JS</div>
    </div>

    <!-- <?php foreach($domains as $domain) { echo(json_encode($domain)); }; ?> -->

    <?php include __DIR__.'/src/index.php'; ?>
    
    <!--code>
        sudo apt install sl -y && sl
    </code-->

    
    <script src="app.js"></script>
    <script src="ai_chat.js" defer></script>
    <script src="stories.js" defer></script>
    <script src="linkshorten.js" defer></script>
</body>
</html>