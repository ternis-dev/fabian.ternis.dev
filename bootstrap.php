<?php

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

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$safePath = str_replace(['..', '//'], '', $requestPath);

// if(str_ends_with($_SERVER['REQUEST_URI'], '.css') && file_exists(__DIR__.'/assets/css/'.$_SERVER['uri'])) {
if (str_ends_with($safePath, '.css') && file_exists(__DIR__.'/assets/css'.$safePath)) {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__.'/assets/css'.$safePath);
    exit;
} elseif (str_ends_with($safePath, '.js') && file_exists(__DIR__.'/assets/js'.$safePath)) {
    header('Content-Type: text/js; charset=UTF-8');
    readfile(__DIR__.'/assets/js'.$safePath);
    exit;
} elseif (str_ends_with($safePath, '.ttf') && file_exists(__DIR__.'/assets/fonts'.$safePath)) {
    header('Content-Type: font/ttf');
    readfile(__DIR__.'/assets/fonts'.$safePath);
    exit;
} elseif ((str_ends_with($safePath, '.jpg') || str_ends_with($safePath, '.png') || str_ends_with($safePath, '.jpeg')) && file_exists(__DIR__.'/assets/img'.$safePath)) {
    $content_type = (str_ends_with($safePath, '.png') ? 'image/png' : 'image/jpeg');
    header('Content-Type: '.$content_type);
    readfile(__DIR__.'/assets/img'.$safePath);
    exit;
} elseif (str_ends_with($safePath, '.unknown.image.mime')) {
    $basePath = substr($safePath, 0, -strlen('.unknown.image.mime'));
    $extensions = ['webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
    foreach ($extensions as $ext => $mime) {
        if (file_exists(__DIR__.'/assets/img'.$basePath.'.'.$ext)) {
            header('Content-Type: '.$mime);
            readfile(__DIR__.'/assets/img'.$basePath.'.'.$ext);
            exit;
        }
    }
    $http_code = 404;
    // ToDO: Add .unknown.mime (support for more than just images ...)
} else {
    $http_code = 404;
}


use App\API\DomainBox;

$dnbx = new DomainBox();

$domains = $dnbx->getMyDomain(['status' => 'active', 'limit' => 999])['data'] ?? []; // the ['data] at the end IMPORTANT ... (not to make this mistake again ...)
// $domains = []; // Internet "problem" (was my fault with DHCP and co.)
$devices = config('devices', []);
$hi = "Hello World!";

// usort($domains, function($a, $b) {
//     return strtotime($a['expires_at']) <=> strtotime($b['expires_at']);
// });

// Todo: generalize this and just use array ... ('suffix', 'path', 'mime')
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabian Ternis – Personal Website</title>
    <link rel="stylesheet" href="app.css">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=7">  ???-->
    <meta name="keywords" content="Fabian Ternis, ternis.dev, Web developer, StoryGrab, twins-on-ice Website, twinsonice website, ternis.net, Ternis HomeLab">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
</head>
<body>
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
    

    <code>
        sudo apt install sl -y && sl
    </code>
    <script src="app.js"></script>
</body>
</html>