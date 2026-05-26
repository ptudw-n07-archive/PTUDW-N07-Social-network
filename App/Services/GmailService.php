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
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

        $html = $this->buildArchiveEmailLayout(
            'Chỉ còn một bước nữa thôi!',
            'Archive đang đợi bạn ghé vào',
            'Xác nhận tài khoản',
            $safeLink,
            'Archive — nơi lưu lại những kết nối nhỏ xinh của bạn.',
            '
                <p style="margin:0 0 16px;color:#3f1d2e;font-size:18px;line-height:1.65;">Hi <strong style="color:#db2777;">' . $safeUsername . '</strong>,</p>
                <p style="margin:0;color:#3f1d2e;font-size:17px;line-height:1.7;">Cảm ơn bạn đã tạo tài khoản tại <strong>Archive</strong>. Để bắt đầu lưu giữ khoảnh khắc, kết nối bạn bè và khám phá những bài viết mới, hãy xác nhận email của bạn bằng nút bên dưới.</p>
            ',
            '
                <p style="margin:0 0 10px;color:#6b7280;font-size:14px;line-height:1.55;">Nếu nút không hoạt động, bạn có thể sao chép liên kết bên dưới và mở trong trình duyệt.</p>
                <a href="' . $safeLink . '" style="color:#db2777;word-break:break-all;font-size:14px;text-decoration:underline;">' . $safeLink . '</a>
            '
        );

        return $this->sendEmail($to, $subject, $html, 'text/html');
    }

    public function sendPasswordResetEmail(string $to, string $username, string $resetLink): bool
    {
        if ($this->isDebugMode()) {
            return $this->writeDebugMail('password_reset', $to, $username, $resetLink);
        }

        $subject = 'Archive gửi bạn liên kết đặt lại mật khẩu 🔐';
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $html = $this->buildArchiveEmailLayout(
            'Mình giúp bạn lấy lại quyền truy cập nhé',
            'Một chiếc link an toàn từ Archive',
            'Tạo mật khẩu mới',
            $safeLink,
            'Archive — giữ tài khoản của bạn an toàn và dễ thương.',
            '
                <p style="margin:0 0 16px;color:#3f1d2e;font-size:18px;line-height:1.65;">Hi <strong style="color:#db2777;">' . $safeUsername . '</strong>,</p>
                <p style="margin:0;color:#3f1d2e;font-size:17px;line-height:1.7;"><strong>Archive</strong> vừa nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhấn vào nút bên dưới để tạo mật khẩu mới. Liên kết này chỉ có hiệu lực trong <strong style="color:#db2777;">15 phút</strong> để bảo vệ tài khoản của bạn.</p>
            ',
            '
                <p style="margin:0 0 10px;color:#6b7280;font-size:14px;line-height:1.55;">Nếu nút không hoạt động, bạn có thể sao chép liên kết bên dưới và mở trong trình duyệt.</p>
                <a href="' . $safeLink . '" style="color:#db2777;word-break:break-all;font-size:14px;text-decoration:underline;">' . $safeLink . '</a>
                <p style="margin:18px 0 0;color:#6b7280;font-size:14px;line-height:1.55;">Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email. Tài khoản của bạn vẫn an toàn.</p>
            '
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

    private function buildArchiveEmailLayout(string $title, string $badgeText, string $buttonText, string $buttonUrl, string $footerText, string $contentHtml, string $extraHtml): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeBadge = htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8');
        $safeButtonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
        $safeFooterText = htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8');

        return '
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $safeTitle . '</title>
</head>
<body style="margin:0;padding:0;background:#fff1f7;font-family:Arial,Helvetica,sans-serif;color:#3f1d2e;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff1f7;background-image:linear-gradient(135deg,#fff7fb 0%,#ffe4ef 48%,#fff1f7 100%);padding:34px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #fbcfe8;border-radius:28px;overflow:hidden;box-shadow:0 18px 45px rgba(219,39,119,0.16);">
          <tr>
            <td style="background:#fdf2f8;background-image:linear-gradient(135deg,#fdf2f8 0%,#fff7fb 100%);padding:22px 30px 0;text-align:center;">
              <div style="display:inline-block;background:#ffffff;border:1px solid #fbcfe8;border-radius:999px;padding:8px 16px;color:#db2777;font-size:13px;font-weight:700;letter-spacing:.2px;">' . $safeBadge . '</div>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 34px 30px;">
              <h1 style="margin:0 0 18px;text-align:center;color:#db2777;font-size:30px;line-height:1.25;font-weight:800;">' . $safeTitle . '</h1>
              <div style="width:72px;height:6px;margin:0 auto 28px;background:#f9a8d4;border-radius:999px;line-height:6px;font-size:0;">&nbsp;</div>
              <div style="background:#fff7fb;border:1px solid #fce7f3;border-radius:22px;padding:24px 24px 22px;">
                ' . $contentHtml . '
              </div>
              <div style="text-align:center;margin:30px 0 28px;">
                <a href="' . $buttonUrl . '" style="display:inline-block;background:#ec4899;background-image:linear-gradient(135deg,#f472b6 0%,#db2777 100%);color:#ffffff;text-decoration:none;font-size:17px;font-weight:800;border-radius:999px;padding:15px 34px;box-shadow:0 12px 24px rgba(219,39,119,0.24);">' . $safeButtonText . '</a>
              </div>
              <div style="background:#ffffff;border:1px dashed #f9a8d4;border-radius:18px;padding:18px 20px;font-size:14px;line-height:1.55;color:#6b7280;">' . $extraHtml . '</div>
              <div style="height:1px;background:#fbcfe8;margin:28px 0 18px;line-height:1px;font-size:0;">&nbsp;</div>
              <p style="margin:0 0 6px;text-align:center;color:#db2777;font-size:15px;font-weight:700;">Archive</p>
              <p style="margin:0;text-align:center;color:#6b7280;font-size:13px;line-height:1.5;">' . $safeFooterText . '</p>
              <p style="margin:10px 0 0;text-align:center;color:#9ca3af;font-size:12px;">&copy; 2026 Archive — PTUDW-N07</p>
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
