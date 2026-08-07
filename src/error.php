<?php
$errorCode = $errorCode ?? http_response_code() ?: 404;
if ($errorCode === 200 || !$errorCode) {
    $errorCode = 404;
}

// ToDo: use a php-array to assign all that stuff in a dedicated file ...

$statusTitles = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Access Forbidden',
    404 => 'Page Not Found',
    405 => 'Method Not Allowed',
    500 => 'Internal Server Error',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
];

$title = $errorMessage ?? ($statusTitles[$errorCode] ?? 'An Error Occurred');
$description = $errorDetails ?? match($errorCode) {
    404 => "The page or resource you are looking for doesn't exist, was removed, or had its name changed.",
    403 => "You don't have permission to access this resource.",
    500 => "Something went wrong on our server. We are working to fix it.",
    default => "An unexpected error occurred while processing your request."
};

http_response_code($errorCode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($errorCode) ?> - <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/assets/css/error.css">
    <link rel="stylesheet" href="/assets/css/404.css">
</head>
<body class="dont-use-attibut-color-variables">
    <main class="error-container dont-use-attibut-color-variables">
        <h1 class="error-code dont-use-attibut-color-variables"><?= htmlspecialchars($errorCode) ?></h1>
        <h2 class="error-title dont-use-attibut-color-variables"><?= htmlspecialchars($title) ?></h2>
        <p class="error-message dont-use-attibut-color-variables"><?= htmlspecialchars($description) ?></p>
        <div class="error-actions dont-use-attibut-color-variables">
            <a href="/" class="btn-home dont-use-attibut-color-variables">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Return to Home
            </a>
        </div>
    </main>
</body>
</html>
