<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$safePath = str_replace(['..', '//'], '', $requestPath);

// if(str_ends_with($_SERVER['REQUEST_URI'], '.css') && file_exists(__DIR__.'/assets/css/'.$_SERVER['uri'])) {
if (str_ends_with($safePath, '.css') && file_exists(__DIR__.'/assets/css'.$safePath)) {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__.'/assets/css'.$safePath);
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
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabian Ternis</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <?= readfile(__DIR__.'/src/index.php') ?>
</body>
</html>