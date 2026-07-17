<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$safePath = str_replace(['..', '//'], '', $requestPath);

// if(str_ends_with($_SERVER['REQUEST_URI'], '.css') && file_exists(__DIR__.'/assets/css/'.$_SERVER['uri'])) {
if (str_ends_with($safePath, '.css') && file_exists(__DIR__.'/assets/css'.$safePath)) {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__.'/assets/css'.$safePath);
    exit;
} else {
    $http_ocde = 404;
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