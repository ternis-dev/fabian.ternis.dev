<?php
// Buffer all output from byte 0 so stray PHP warnings/notices don't corrupt
// JSON API responses. ApiRouter::sendJson() calls ob_end_clean() before writing.
ob_start();

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

// Feed routes
if ($safePath === '/feed/news' || $safePath === '/feed/news/') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /feed/news/xml");
    exit;
} elseif ($safePath === '/feed/news/xml') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo \App\Services\NewsFeedService::renderXmlFeed();
    exit;
} elseif ($safePath === '/feed/news/json') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo \App\Services\NewsFeedService::renderJsonFeed();
    exit;
}

// Robots, Sitemap, and LLMs routes
if ($safePath === '/robots.txt') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/plain; charset=UTF-8');
    echo \App\Services\SeoService::renderRobotsTxt();
    exit;
} elseif ($safePath === '/sitemap.xml') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/xml; charset=UTF-8');
    echo \App\Services\SeoService::renderSitemapXml();
    exit;
} elseif ($safePath === '/stiemap.xml') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /sitemap.xml");
    exit;
} elseif ($safePath === '/llms.txt') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/plain; charset=UTF-8');
    echo \App\Services\SeoService::renderLlmsTxt();
    exit;
} elseif ($safePath === '/llms.text') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /llms.txt");
    exit;
}

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



use App\API\{DomainBox, Turnstile, StoryGrab, TwinsOnIceLink, GitHub, HackClubCDN, ApiRouter, hackAI, Hackatime, /*the_sk_provider,*/ skProvider};
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
$api_['hackatime'] = new Hackatime();
// $api_['sk'] = new the_sk_provider();
// $api_['sk'] = new skProvider();

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

// 404 route handler for unknown page requests
if ($safePath !== '/' && $safePath !== '/index.php' && $safePath !== '/links' && $safePath !== '/links/') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    $errorCode = 404;
    include __DIR__ . '/src/error.php';
    exit;
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

require_once __DIR__ . '/src/uploads.php';
$uploadsData = handle_uploads($s_, $api_, $turnstile);
$uploadResult = $uploadsData['uploadResult'];
$recentUploads = $uploadsData['recentUploads'];

// Handle /links route dynamically
if ($safePath === '/links' || $safePath === '/links/') {
    ob_start();
    include __DIR__ . '/src/index.php';
    $mainHtml = ob_get_clean();

    $extractedLinks = \App\Services\LinkExtractorService::extractLinksFromHtml($mainHtml);

    while (ob_get_level()) {
        ob_end_clean();
    }

    include __DIR__ . '/src/links.php';
    exit;
}

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
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t && t !== 'system') {
                document.documentElement.dataset.theme = t;
            }
        })();
    </script>
    <!-- Whyever this is anotehr unicode ... ? ... -->
    <link rel="stylesheet" href="app.css">
    <link rel="alternate" type="application/rss+xml" title="Fabian Ternis - News (RSS Feed)" href="/feed/news/xml">
    <link rel="alternate" type="application/json" title="Fabian Ternis - News (JSON Feed)" href="/feed/news/json">
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
    <div id="github-star-container" class="dont-use-attibute-color-variables">
        <a class="OTHER-LINK prevent-default" href="https://github.com/ternis-dev/fabian.ternis.dev" target="_blank" rel="noopener noreferrer" title="Star on GitHub">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 139"><path fill="currentColor" d="M98.696 59.312h-43.06a2.015 2.015 0 0 0-2.013 2.014v21.053c0 1.111.902 2.015 2.012 2.015h16.799v26.157s-3.772 1.286-14.2 1.286c-12.303 0-29.49-4.496-29.49-42.288c0-37.8 17.897-42.773 34.698-42.773c14.543 0 20.809 2.56 24.795 3.794c1.253.384 2.412-.863 2.412-1.975l4.803-20.342c0-.52-.176-1.146-.769-1.571C93.064 5.527 83.187 0 58.233 0C29.488 0 0 12.23 0 71.023c0 58.795 33.76 67.556 62.21 67.556c23.555 0 37.844-10.066 37.844-10.066c.59-.325.653-1.148.653-1.526V61.326c0-1.11-.9-2.014-2.01-2.014m221.8-51.953c0-1.12-.888-2.024-1.999-2.024h-24.246a2.016 2.016 0 0 0-2.008 2.024l.006 46.856h-37.792V7.36c0-1.12-.892-2.024-2.001-2.024H228.21a2.014 2.014 0 0 0-2.003 2.024v126.872c0 1.12.9 2.03 2.003 2.03h24.245c1.109 0 2-.91 2-2.03V79.964h37.793l-.066 54.267c0 1.12.9 2.03 2.008 2.03h24.304c1.11 0 1.998-.91 2-2.03zM144.37 24.322c0-8.73-7-15.786-15.635-15.786c-8.627 0-15.632 7.055-15.632 15.786c0 8.72 7.005 15.795 15.632 15.795c8.635 0 15.635-7.075 15.635-15.795m-1.924 83.212V48.97a2.015 2.015 0 0 0-2.006-2.021h-24.169c-1.109 0-2.1 1.144-2.1 2.256v83.905c0 2.466 1.536 3.199 3.525 3.199h21.775c2.39 0 2.975-1.173 2.975-3.239zM413.162 46.95h-24.06c-1.104 0-2.002.909-2.002 2.028v62.21s-6.112 4.472-14.788 4.472s-10.977-3.937-10.977-12.431v-54.25c0-1.12-.897-2.03-2.001-2.03h-24.419c-1.102 0-2.005.91-2.005 2.03v58.358c0 25.23 14.063 31.403 33.408 31.403c15.87 0 28.665-8.767 28.665-8.767s.61 4.62.885 5.168c.276.547.994 1.098 1.77 1.098l15.535-.068c1.102 0 2.005-.911 2.005-2.025l-.008-85.168a2.02 2.02 0 0 0-2.008-2.028m55.435 68.758c-8.345-.254-14.006-4.041-14.006-4.041V71.488s5.585-3.423 12.436-4.035c8.664-.776 17.013 1.841 17.013 22.51c0 21.795-3.768 26.096-15.443 25.744m9.49-71.483c-13.665 0-22.96 6.097-22.96 6.097V7.359a2.01 2.01 0 0 0-2-2.024h-24.315a2.013 2.013 0 0 0-2.004 2.024v126.872c0 1.12.898 2.03 2.007 2.03h16.87c.76 0 1.335-.39 1.76-1.077c.419-.682 1.024-5.85 1.024-5.85s9.942 9.422 28.763 9.422c22.096 0 34.768-11.208 34.768-50.315s-20.238-44.217-33.913-44.217M212.229 46.73h-18.187l-.028-24.027c0-.909-.468-1.364-1.52-1.364H167.71c-.964 0-1.481.424-1.481 1.35v24.83s-12.42 2.998-13.26 3.24a2.01 2.01 0 0 0-1.452 1.934v15.603c0 1.122.896 2.027 2.005 2.027h12.707v37.536c0 27.88 19.556 30.619 32.753 30.619c6.03 0 13.243-1.937 14.434-2.376c.72-.265 1.138-1.01 1.138-1.82l.02-17.164c0-1.119-.945-2.025-2.01-2.025c-1.06 0-3.77.431-6.562.431c-8.933 0-11.96-4.154-11.96-9.53l-.001-35.67h18.188a2.014 2.014 0 0 0 2.006-2.028V48.753c0-1.12-.897-2.022-2.006-2.022"/></svg>
        </a>
    </div>

    <!-- <?php foreach($domains as $domain) { echo(json_encode($domain)); }; ?> -->

    <?php include __DIR__.'/src/index.php'; ?>
    
    <!--code>
        sudo apt install sl -y && sl
    </code-->

    
    <script src="app.js"></script>
    <script src="helpers.js"></script>
    <script src="ai_chat.js" defer></script>
    <script src="stories.js" defer></script>
    <script src="linkshorten.js" defer></script>
</body>
</html>