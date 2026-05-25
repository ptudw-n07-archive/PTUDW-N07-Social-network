<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/Database.php';

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

$to = 'nguyengiahan2202@gmail.com';
$otp = (string) random_int(100000, 999999);

try {
    $client = new Client();
    $client->setClientId(app_env('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(app_env('GOOGLE_CLIENT_SECRET'));
    $client->setAccessType('offline');
    $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
    $client->refreshToken(app_env('GOOGLE_REFRESH_TOKEN'));

    $service = new Gmail($client);

    $fromEmail = app_env('GMAIL_SENDER_EMAIL');
    $fromName = app_env('GMAIL_SENDER_NAME', 'Archive');

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
    echo $e->getMessage();
    echo "</pre>";
}
