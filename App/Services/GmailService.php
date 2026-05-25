<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/Database.php';

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    public function sendOtp(string $to, string $otp): void
    {
        $clientId = app_env('GOOGLE_CLIENT_ID');
        $clientSecret = app_env('GOOGLE_CLIENT_SECRET');
        $refreshToken = app_env('GOOGLE_REFRESH_TOKEN');
        $fromEmail = app_env('GMAIL_SENDER_EMAIL');
        $fromName = app_env('GMAIL_SENDER_NAME', 'Archive');

        if (!$clientId || !$clientSecret || !$refreshToken || !$fromEmail) {
            throw new Exception('Missing Gmail API environment variables.');
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($accessToken['error'])) {
            throw new Exception('Gmail token error: ' . json_encode($accessToken));
        }

        $client->setAccessToken($accessToken);

        $service = new Gmail($client);

        $subject = 'Ma OTP xac nhan Archive';

        $raw = "From: {$fromName} <{$fromEmail}>\r\n";
        $raw .= "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $raw .= "Ma OTP cua ban la: {$otp}\n";
        $raw .= "Ma co hieu luc trong 5 phut.\n";
        $raw .= "Neu ban khong yeu cau, vui long bo qua email nay.";

        $message = new Message();
        $message->setRaw($this->base64UrlEncode($raw));

        $service->users_messages->send('me', $message);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
