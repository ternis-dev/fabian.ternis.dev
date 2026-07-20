<?php

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
} else {
    $http_code = 404;
}

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
</head>
<body>
    <div class="theme-select-container">
        <select name="theme" id="theme-select"></select>
    </div>
    <?= readfile(__DIR__.'/src/index.php') ?>
    
    <script src="app.js"></script>
</body>
</html>