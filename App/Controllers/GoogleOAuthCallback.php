<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/Database.php';

use Google\Client;

$client = new Client();
$client->setClientId(app_env('GOOGLE_CLIENT_ID'));
$client->setClientSecret(app_env('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(app_env('GOOGLE_REDIRECT_URI'));
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->setScopes([
    'https://www.googleapis.com/auth/gmail.send'
]);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

echo '<pre>';
print_r($token);
echo '</pre>';