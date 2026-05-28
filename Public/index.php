<?php

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (is_string($requestPath) && $requestPath !== '/') {
        $projectRoot = realpath(__DIR__ . '/..');
        $currentFile = realpath(__FILE__);
        $requestedFile = realpath(($projectRoot ?: (__DIR__ . '/..')) . $requestPath);

        if (
            $projectRoot !== false
            && $currentFile !== false
            && $requestedFile !== false
            && !str_contains($requestPath, '..')
            && $requestedFile !== $currentFile
            && is_file($requestedFile)
        ) {
            return false;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Config/Database.php';

$route = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
$route = preg_replace('#/+#', '/', $route);

if (str_starts_with($route, 'Public/')) {
    $route = trim(substr($route, strlen('Public/')), '/');
}

$routes = [
    '' => __DIR__ . '/home.php',
    'index.php' => __DIR__ . '/home.php',
    'home' => __DIR__ . '/home.php',
    'home.php' => __DIR__ . '/home.php',
    'login' => __DIR__ . '/../App/Views/auth/login.php',
    'register' => __DIR__ . '/../App/Views/auth/register.php',
    'forgot-password' => __DIR__ . '/../App/Views/auth/forgot-password.php',
    'reset-password' => __DIR__ . '/../App/Views/auth/reset-password.php',
    'account-locked' => __DIR__ . '/../App/Views/auth/account_locked.php',
    'feed' => __DIR__ . '/../App/Views/post/feed.php',
    'create-post' => __DIR__ . '/../App/Views/post/createpost.php',
    'post' => __DIR__ . '/../App/Views/post/post-detail.php',
    'post-detail' => __DIR__ . '/../App/Views/post/post-detail.php',
    'profile' => __DIR__ . '/../App/Views/profile/profile.php',
    'notifications' => __DIR__ . '/../App/Views/notifications/notifications.php',
    'notification-detail' => __DIR__ . '/../App/Views/notifications/detail.php',
    'search' => __DIR__ . '/../App/Views/search/search.php',
    'hashtag' => __DIR__ . '/../App/Views/hashtags/hashtag.php',
    'admin' => __DIR__ . '/../App/Views/admin/dashboard.php',
    'admin/profile' => __DIR__ . '/../App/Views/admin/profile.php',
];

if (isset($routes[$route]) && is_file($routes[$route])) {
    require $routes[$route];
    exit;
}

http_response_code(404);
require __DIR__ . '/home.php';
