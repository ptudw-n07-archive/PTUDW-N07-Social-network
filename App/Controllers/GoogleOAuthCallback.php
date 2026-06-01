<?php

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Google\Client;

function google_oauth_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function google_oauth_render(string $title, string $body, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo '<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . google_oauth_escape($title) . '</title>
  <style>
    body { margin: 0; padding: 32px 16px; background: #f5f7fb; color: #152238; font-family: Arial, Helvetica, sans-serif; }
    .card { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #dde3ee; border-radius: 10px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
    h1 { margin: 0 0 14px; font-size: 26px; }
    p { line-height: 1.6; }
    code, textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    code { background: #eef2f7; border-radius: 5px; padding: 2px 6px; }
    textarea { width: 100%; min-height: 120px; padding: 12px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; resize: vertical; }
    .warn { background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; border-radius: 8px; padding: 12px 14px; }
    .ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #064e3b; border-radius: 8px; padding: 12px 14px; }
    .btn { display: inline-block; margin-top: 10px; background: #12213f; color: #fff; text-decoration: none; border-radius: 6px; padding: 11px 16px; font-weight: 700; }
  </style>
</head>
<body>
  <main class="card">
    <h1>' . google_oauth_escape($title) . '</h1>
    ' . $body . '
  </main>
</body>
</html>';
    exit;
}

function google_oauth_missing_env(array $keys): array {
    $missing = [];

    foreach ($keys as $key) {
        if (!app_env($key)) {
            $missing[] = $key;
        }
    }

    return $missing;
}

function google_oauth_client(): Client {
    $client = new Client();
    $client->setClientId(app_env('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(app_env('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(app_env('GOOGLE_REDIRECT_URI'));
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

    if (method_exists($client, 'setIncludeGrantedScopes')) {
        $client->setIncludeGrantedScopes(true);
    }

    return $client;
}

$missing = google_oauth_missing_env([
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET',
    'GOOGLE_REDIRECT_URI'
]);

if (!empty($missing)) {
    google_oauth_render(
        'Thiếu cấu hình Google OAuth',
        '<div class="warn">Missing env: ' . google_oauth_escape(implode(', ', $missing)) . '</div>
        <p>Điền các biến này trong <code>.env</code>, sau đó reload lại trang callback.</p>',
        500
    );
}

try {
    $client = google_oauth_client();

    if (isset($_GET['error'])) {
        google_oauth_render(
            'Google OAuth bị từ chối',
            '<div class="warn">' . google_oauth_escape((string) $_GET['error']) . '</div>
            <p>Hãy kiểm tra OAuth consent screen, test users và Authorized redirect URI trong Google Cloud Console.</p>',
            400
        );
    }

    if (!isset($_GET['code'])) {
        header('Location: ' . $client->createAuthUrl());
        exit;
    }

    $token = $client->fetchAccessTokenWithAuthCode((string) $_GET['code']);

    if (isset($token['error'])) {
        google_oauth_render(
            'Không lấy được OAuth token',
            '<div class="warn">' . google_oauth_escape(json_encode($token, JSON_UNESCAPED_UNICODE)) . '</div>
            <p>Nếu lỗi là <code>invalid_client</code>, hãy kiểm tra lại <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code> và OAuth client có còn tồn tại không.</p>',
            400
        );
    }

    if (!empty($token['refresh_token'])) {
        $refreshToken = (string) $token['refresh_token'];
        google_oauth_render(
            'Đã lấy được refresh token',
            '<div class="ok">Copy giá trị bên dưới vào biến <code>GOOGLE_REFRESH_TOKEN</code> trong <code>.env</code>. Không commit token này lên Git.</div>
            <p><strong>GOOGLE_REFRESH_TOKEN</strong></p>
            <textarea readonly>' . google_oauth_escape($refreshToken) . '</textarea>
            <p>Token này phải đi cùng đúng <code>GOOGLE_CLIENT_ID</code> và <code>GOOGLE_CLIENT_SECRET</code> đã dùng để authorize.</p>'
        );
    }

    google_oauth_render(
        'Google không trả refresh_token',
        '<div class="warn">OAuth thành công nhưng response không có <code>refresh_token</code>.</div>
        <p>Thường là vì tài khoản đã cấp quyền trước đó. Hãy revoke quyền của app trong Google Account, rồi mở lại URL này. File callback hiện đã dùng <code>access_type=offline</code> và <code>prompt=consent</code>.</p>'
    );
} catch (Throwable $e) {
    google_oauth_render(
        'Lỗi Google OAuth',
        '<div class="warn">' . google_oauth_escape($e->getMessage()) . '</div>',
        500
    );
}
