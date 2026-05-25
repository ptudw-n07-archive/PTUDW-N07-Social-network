<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/Database.php';

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

function env_value(string $key, ?string $default = null): ?string
{
    if (function_exists('app_env')) {
        $value = app_env($key, $default);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }

    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

$to = 'nguyengiahan2202@gmail.com';
$otp = (string) random_int(100000, 999999);

try {
    $clientId = env_value('GOOGLE_CLIENT_ID');
    $clientSecret = env_value('GOOGLE_CLIENT_SECRET');
    $refreshToken = env_value('GOOGLE_REFRESH_TOKEN');
    $fromEmail = env_value('GMAIL_SENDER_EMAIL');
    $fromName = env_value('GMAIL_SENDER_NAME', 'Archive');

    if (!$clientId || !$clientSecret || !$refreshToken || !$fromEmail) {
        throw new Exception('Missing env. Check GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REFRESH_TOKEN, GMAIL_SENDER_EMAIL.');
    }

    $client = new Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setAccessType('offline');
    $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

    $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

    if (isset($accessToken['error'])) {
        throw new Exception('Token error: ' . json_encode($accessToken, JSON_PRETTY_PRINT));
    }

    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {
        throw new Exception('Access token expired after refresh.');
    }

    $service = new Gmail($client);

    $raw = "From: {$fromName} <{$fromEmail}>\r\n";
    $raw .= "To: {$to}\r\n";
    $raw .= "Subject: Test OTP Archive\r\n";
    $raw .= "MIME-Version: 1.0\r\n";
    $raw .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $raw .= "Ma OTP test cua ban la: {$otp}";

    $message = new Message();
    $message->setRaw(rtrim(strtr(base64_encode($raw), '+/', '-_'), '='));

    $service->users_messages->send('me', $message);

    echo "Sent OTP successfully: " . htmlspecialchars($otp);
} catch (Throwable $e) {
    echo "<pre>";
    echo "Send failed:\n";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
