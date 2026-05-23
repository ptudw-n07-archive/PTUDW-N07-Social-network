<?php
require_once __DIR__ . '/../Config/Database.php';

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'ok' => true,
    'service' => 'archive',
    'timestamp' => gmdate(DATE_ATOM),
    'uploadsRoot' => app_uploads_root()
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
