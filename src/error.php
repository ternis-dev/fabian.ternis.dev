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
    <title><?= htmlspecialchars((string)$errorCode) ?> - <?= htmlspecialchars($title) ?></title>
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t && t !== 'system') {
                document.documentElement.dataset.theme = t;
            }
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/error.css">
</head>
<body>
    <div class="theme-select-container">
        <select name="theme" id="theme-select" aria-label="Select color theme">
        </select>
    </div>

    <div class="bg-error-code" aria-hidden="true"><?= htmlspecialchars((string)$errorCode) ?></div>
    <main class="error-container">
        <h1 class="error-code"><?= htmlspecialchars((string)$errorCode) ?></h1>
        <h2 class="error-title"><?= htmlspecialchars($title) ?></h2>
        <p class="error-message"><?= htmlspecialchars($description) ?></p>
        <div class="error-actions">
            <a href="/" class="btn-home">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Return to Home
            </a>
            <a href="/links" class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Links Directory
            </a>
            <a href="/docs" class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                API Docs
            </a>
        </div>
    </main>

    <script>
        (function() {
            const legacyThemeMap = {
                'catpucchino': 'catppuccin',
                'one-light': 'light',
                'alabaster': 'light',
                'tokyo-night-day': 'nord-light',
                'cupcake': 'rose-pine-dawn',
                'horizon-light': 'rose-pine-dawn',
                'garden': 'gruvbox-light',
                'winter': 'tokyo-night',
                'forest': 'gruvbox-dark',
                'emerald': 'gruvbox-dark',
                'coffee': 'gruvbox-dark',
                'sunset': 'synthwave',
                'monokai': 'dracula',
                'cyberpunk': 'synthwave',
                'neon': 'synthwave',
                'horizon-dark': 'rose-pine',
                'vampire': 'catppuccin'
            };

            let savedTheme = localStorage.getItem('theme') || 'system';
            if (legacyThemeMap[savedTheme]) {
                savedTheme = legacyThemeMap[savedTheme];
                localStorage.setItem('theme', savedTheme);
            }

            const themeInput = document.getElementById('theme-select');
            const theme_groups = [
                {
                    label: 'System & Defaults',
                    themes: [
                        { id: 'system', name: 'System (Auto)' },
                        { id: 'dark', name: 'Dark Mode' },
                        { id: 'light', name: 'Light Mode' }
                    ]
                },
                {
                    label: 'Light Themes',
                    themes: [
                        { id: 'catppuccin-latte', name: 'Catppuccin Latte' },
                        { id: 'rose-pine-dawn', name: 'Rosé Pine Dawn' },
                        { id: 'nord-light', name: 'Nord Light' },
                        { id: 'gruvbox-light', name: 'Gruvbox Light' },
                        { id: 'solarized-light', name: 'Solarized Light' },
                        { id: 'paper-sepia', name: 'Paper / Sepia' }
                    ]
                },
                {
                    label: 'Dark Themes',
                    themes: [
                        { id: 'catppuccin', name: 'Catppuccin Mocha' },
                        { id: 'dracula', name: 'Dracula' },
                        { id: 'tokyo-night', name: 'Tokyo Night' },
                        { id: 'nord', name: 'Nord' },
                        { id: 'rose-pine', name: 'Rosé Pine' },
                        { id: 'gruvbox-dark', name: 'Gruvbox Dark' },
                        { id: 'solarized-dark', name: 'Solarized Dark' },
                        { id: 'synthwave', name: 'Synthwave \'84' }
                    ]
                }
            ];

            if (themeInput) {
                themeInput.innerHTML = theme_groups.map(group => {
                    const options = group.themes.map(t => {
                        const isSelected = (savedTheme === t.id) ? ' selected' : '';
                        return `<option value="${t.id}"${isSelected}>${t.name}</option>`;
                    }).join('');
                    return `<optgroup label="${group.label}">${options}</optgroup>`;
                }).join('');

                themeInput.addEventListener('change', updateTheme);
            }

            function updateTheme() {
                let currentTheme = themeInput ? themeInput.value : savedTheme;
                localStorage.setItem('theme', currentTheme);

                if (currentTheme !== 'system') {
                    document.documentElement.dataset.theme = currentTheme;
                    if (document.body) document.body.dataset.theme = currentTheme;
                } else {
                    delete document.documentElement.dataset.theme;
                    if (document.body) delete document.body.dataset.theme;
                }
            }

            updateTheme();

            window.addEventListener('storage', (e) => {
                if (e.key === 'theme') {
                    savedTheme = e.newValue || 'system';
                    if (themeInput) {
                        themeInput.value = savedTheme;
                    }
                    updateTheme();
                }
            });
        })();
    </script>
</body>
</html>
