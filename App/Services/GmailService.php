<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/Database.php';

use Exception;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    public function sendOtp(string $to, string $otp): bool
    {
        if ($this->isDebugMode()) {
            return $this->writeDebugMail('otp', $to, '-', 'OTP=' . $otp);
        }

        $subject = 'Ma OTP xac nhan Archive';
        $body = "Ma OTP cua ban la: {$otp}\n";
        $body .= "Ma co hieu luc trong 5 phut.\n";
        $body .= "Neu ban khong yeu cau, vui long bo qua email nay.";

        return $this->sendEmail($to, $subject, $body, 'text/plain');
    }

    public function sendVerificationEmail(string $to, string $username, string $verifyLink): bool
    {
        if ($this->isDebugMode()) {
            return $this->writeDebugMail('verification', $to, $username, $verifyLink);
        }

        $subject = 'Archive chờ bạn xác nhận tài khoản 💗';
        $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

        $html = $this->buildMinimalEmailLayout(
            'Chỉ còn một bước nhỏ nữa thôi',
            'Archive · Một nơi để nói khẽ',
            '
                <p style="margin:0 0 14px;color:#46332c;font-size:16px;line-height:1.7;">Chào Archiver,</p>
                <p style="margin:0 0 14px;color:#46332c;font-size:16px;line-height:1.7;">Cảm ơn bạn đã ghé <strong>Archive</strong> — Một nơi để nói khẽ.</p>
                <p style="margin:0 0 14px;color:#46332c;font-size:16px;line-height:1.7;">Archive là website thuộc dự án môn học UEH: <strong>Phát triển ứng dụng Web</strong>, được thực hiện bởi <strong>Nhóm 7 — Better Together</strong>.</p>
                <p style="margin:0;color:#46332c;font-size:16px;line-height:1.7;">Hãy xác nhận tài khoản để bắt đầu lưu lại những khoảnh khắc, kết nối với bạn bè và chia sẻ câu chuyện của riêng bạn.</p>
            ',
            'Kích hoạt tài khoản',
            $safeLink,
            'Archive — Một nơi để nói khẽ'
        );

        return $this->sendEmail($to, $subject, $html, 'text/html');
    }

    public function sendPasswordResetEmail(string $to, string $username, string $resetLink): bool
    {
        if ($this->isDebugMode()) {
            return $this->writeDebugMail('password_reset', $to, $username, $resetLink);
        }

        $subject = 'Archive gửi bạn liên kết đặt lại mật khẩu 🔐';
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $html = $this->buildMinimalEmailLayout(
            'Tạo lại mật khẩu mới',
            'Archive · Khôi phục tài khoản',
            '
                <p style="margin:0 0 14px;color:#46332c;font-size:16px;line-height:1.7;">Chào Archiver,</p>
                <p style="margin:0 0 14px;color:#46332c;font-size:16px;line-height:1.7;"><strong>Archive</strong> vừa nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn.</p>
                <p style="margin:0;color:#46332c;font-size:16px;line-height:1.7;">Liên kết này chỉ có hiệu lực trong <strong style="color:#d69096;">15 phút</strong>, nên hãy hoàn tất sớm để tiếp tục quay lại Archive nhé.</p>
            ',
            'Đặt lại mật khẩu',
            $safeLink,
            'Archive — Một nơi để nói khẽ'
        );

        return $this->sendEmail($to, $subject, $html, 'text/html');
    }

    private function sendEmail(string $to, string $subject, string $body, string $contentType): bool
    {
        $client = $this->createClient();
        $service = new Gmail($client);
        $fromEmail = app_env('GMAIL_SENDER_EMAIL');
        $fromName = app_env('GMAIL_SENDER_NAME', 'Archive');

        $raw = 'From: ' . $this->formatAddress($fromName, $fromEmail) . "\r\n";
        $raw .= 'To: ' . $to . "\r\n";
        $raw .= 'Subject: ' . $this->encodeHeader($subject) . "\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= 'Content-Type: ' . $contentType . "; charset=UTF-8\r\n\r\n";
        $raw .= $body;

        $message = new Message();
        $message->setRaw($this->base64UrlEncode($raw));

        $service->users_messages->send('me', $message);
        return true;
    }

    private function createClient(): Client
    {
        $clientId = app_env('GOOGLE_CLIENT_ID');
        $clientSecret = app_env('GOOGLE_CLIENT_SECRET');
        $refreshToken = app_env('GOOGLE_REFRESH_TOKEN');
        $fromEmail = app_env('GMAIL_SENDER_EMAIL');

        $missingKeys = [];
        foreach ([
            'GOOGLE_CLIENT_ID' => $clientId,
            'GOOGLE_CLIENT_SECRET' => $clientSecret,
            'GOOGLE_REFRESH_TOKEN' => $refreshToken,
            'GMAIL_SENDER_EMAIL' => $fromEmail
        ] as $key => $value) {
            if (!$value) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new Exception('Missing env: ' . implode(', ', $missingKeys));
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($accessToken['error'])) {
            $errorPayload = json_encode($accessToken, JSON_UNESCAPED_UNICODE);
            error_log('[GmailService] OAuth token error: ' . $errorPayload);
            throw new Exception('Gmail token error: ' . $errorPayload);
        }

        $client->setAccessToken($accessToken);

        return $client;
    }

    private function buildMinimalEmailLayout(string $title, string $eyebrow, string $bodyHtml, string $buttonText, string $buttonUrl, string $footerTitle): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeEyebrow = htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8');
        $safeButtonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
        $safeFooterTitle = htmlspecialchars($footerTitle, ENT_QUOTES, 'UTF-8');

        return '
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $safeTitle . '</title>
</head>
<body style="margin:0;padding:0;background:#f5eee9;font-family:Inter,Arial,sans-serif;color:#46332c;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5eee9;background-image:linear-gradient(135deg,#f5eee9 0%,#e8dfd8 100%);padding:36px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fffaf7;border:1px solid rgba(121,91,74,0.15);border-radius:30px;overflow:hidden;box-shadow:0 20px 60px rgba(70,51,44,0.10);">
          <tr>
            <td style="padding:38px 34px 32px;">
              <div style="text-align:center;margin:0 0 22px;">
                <span style="display:inline-block;background:#fffdfa;border:1px solid rgba(121,91,74,0.15);border-radius:999px;padding:7px 15px;color:#8c7b75;font-size:12px;font-weight:700;letter-spacing:.4px;">' . $safeEyebrow . '</span>
              </div>
              <h1 style="margin:0 0 20px;text-align:center;color:#46332c;font-family:\'Playfair Display\',Georgia,serif;font-size:32px;line-height:1.18;font-weight:700;">' . $safeTitle . '</h1>
              <div style="width:54px;height:3px;margin:0 auto 28px;background:#d69096;border-radius:999px;line-height:3px;font-size:0;">&nbsp;</div>
              <div style="background:#fffdfa;border:1px solid rgba(121,91,74,0.12);border-radius:24px;padding:25px 26px;">
                ' . $bodyHtml . '
              </div>
              <div style="text-align:center;margin:28px 0 26px;">
                <a href="' . $buttonUrl . '" style="display:inline-block;background:#d69096;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;border-radius:15px;padding:15px 30px;box-shadow:0 8px 20px rgba(214,144,150,0.30);">' . $safeButtonText . '</a>
              </div>
              <div style="height:1px;background:rgba(121,91,74,0.12);margin:28px 0 18px;line-height:1px;font-size:0;">&nbsp;</div>
              <p style="margin:0 0 4px;text-align:center;color:#46332c;font-size:13px;font-weight:800;">' . $safeFooterTitle . '</p>
              <p style="margin:0;text-align:center;color:#8c7b75;font-size:12px;line-height:1.6;">Nhóm 7 — Better Together | UEH</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }

    private function formatAddress(string $name, string $email): string
    {
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function isDebugMode(): bool
    {
        return strtolower((string) app_env('MAIL_DEBUG_MODE', 'false')) === 'true';
    }

    private function writeDebugMail(string $type, string $to, string $username, string $link): bool
    {
        $logDir = app_path('storage/logs');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] TYPE=%s TO=%s USER=%s LINK=%s\n",
            date('Y-m-d H:i:s'),
            $type,
            $to,
            $username,
            $link
        );

        file_put_contents($logDir . '/mail_debug.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }
}
