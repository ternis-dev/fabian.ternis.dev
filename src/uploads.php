<?php

/**
 * Handle uploads processing logic: table initialization, file upload handling, and fetching recent uploads.
 *
 * @param array $s_ Application services array containing 'db'
 * @param array $api_ Application API services array containing 'hackclub_cdn'
 * @param \App\API\Turnstile $turnstile Turnstile API handler instance
 * @return array Array containing 'uploadResult' and 'recentUploads'
 */
function handle_uploads(array $s_, array $api_, \App\API\Turnstile $turnstile): array
{
    // Ensure uploads table exists
    try {
        $s_['db']->execute("
            CREATE TABLE IF NOT EXISTS uploads (
                id VARCHAR(255) PRIMARY KEY,
                filename VARCHAR(255),
                url TEXT NOT NULL,
                size INT,
                content_type VARCHAR(100),
                username VARCHAR(255),
                description TEXT,
                created_at DATETIME,
                ip_address VARCHAR(45)
            )
        ");
    } catch (\Throwable $e) {
        // Log or handle schema creation exception if needed
    }

    $uploadResult = null;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['upload_submit']) && isset($_FILES['image']) && $api_['hackclub_cdn']->isConfigured()) {
        // Basic Turnstile guard — re-use the already-verified $turnstileResult if present,
        // otherwise verify the token from this specific form submission.
        $uploadTurnstileOk = false;
        if (isset($_POST['cf-turnstile-response'])) {
            $uploadTurnstileVerify = $turnstile->verify($_POST['cf-turnstile-response'], $_SERVER['REMOTE_ADDR'] ?? null);
            $uploadTurnstileOk = $uploadTurnstileVerify['success'] ?? false;
        }

        if ($uploadTurnstileOk) {
            $uploadResult = $api_['hackclub_cdn']->uploadFromFileEntry($_FILES['image']);

            if (!isset($uploadResult['error']) && !empty($uploadResult['url'])) {
                $uploadId = $uploadResult['id'] ?? uniqid('up_');
                $username = !empty($_POST['username']) ? trim($_POST['username']) : 'Anonymous';
                $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
                $createdAt = date('Y-m-d H:i:s');
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

                try {
                    $s_['db']->execute(
                        "INSERT INTO uploads (id, filename, url, size, content_type, username, description, created_at, ip_address) 
                         VALUES (:id, :filename, :url, :size, :content_type, :username, :description, :created_at, :ip_address)",
                        [
                            'id'           => $uploadId,
                            'filename'     => $uploadResult['filename'] ?? ($_FILES['image']['name'] ?? 'image'),
                            'url'          => $uploadResult['url'],
                            'size'         => $uploadResult['size'] ?? ($_FILES['image']['size'] ?? 0),
                            'content_type' => $uploadResult['content_type'] ?? ($_FILES['image']['type'] ?? null),
                            'username'     => $username,
                            'description'  => $description,
                            'created_at'   => $createdAt,
                            'ip_address'   => $ipAddress,
                        ]
                    );
                } catch (\Throwable $e) {
                    // DB insertion failure logged if needed
                }
            }
        } else {
            $uploadResult = ['error' => 'Turnstile verification failed. Please complete the captcha.'];
        }
    }

    // Retrieve uploaded images stored in DB
    $recentUploads = [];
    try {
        $recentUploads = $s_['db']->fetchAll("SELECT * FROM uploads ORDER BY created_at DESC LIMIT 30");
    } catch (\Throwable $e) {
        $recentUploads = [];
    }

    return [
        'uploadResult'  => $uploadResult,
        'recentUploads' => $recentUploads,
    ];
}
