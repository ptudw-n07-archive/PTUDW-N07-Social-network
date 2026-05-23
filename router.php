<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$blockedPrefixes = [
    '/Config/',
    '/.git/',
    '/.sisyphus/',
    '/Diagrams/',
];

foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($requestPath, $prefix)) {
        http_response_code(404);
        exit('Not Found');
    }
}

$resolvedPath = __DIR__ . $requestPath;
if ($requestPath !== '/' && is_file($resolvedPath)) {
    return false;
}

require __DIR__ . '/index.php';
