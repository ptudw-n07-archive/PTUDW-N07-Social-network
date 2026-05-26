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

        $html = $this->buildMinimalEmailLayout(
            'Chỉ còn một bước nhỏ nữa thôi',
            'Archive · Một nơi để nói khẽ',
            '
                <p style="margin:0 0 14px;color:#3f1d2e;font-size:17px;line-height:1.65;">Chào Archiver,</p>
                <p style="margin:0 0 14px;color:#3f1d2e;font-size:16px;line-height:1.7;">Cảm ơn bạn đã đăng ký tài khoản tại <strong>Archive</strong> — một nơi để nói khẽ. Đây là website thuộc dự án môn học UEH: <strong>Phát triển ứng dụng Web</strong>, được thực hiện bởi <strong>Nhóm 7 — Better Together</strong>.</p>
                <p style="margin:0;color:#3f1d2e;font-size:16px;line-height:1.7;">Để bắt đầu lưu giữ khoảnh khắc, kết nối bạn bè và khám phá những câu chuyện mới, hãy xác nhận email của bạn bằng nút bên dưới.</p>
                <p style="margin:18px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">Tài khoản: <strong style="color:#db2777;">' . $safeUsername . '</strong></p>
            ',
            'Kích hoạt tài khoản',
            $safeLink,
            '
                <p style="margin:0 0 9px;color:#6b7280;font-size:13px;line-height:1.55;">Nếu nút không hoạt động, bạn có thể sao chép liên kết này và mở trong trình duyệt:</p>
                <a href="' . $safeLink . '" style="color:#db2777;word-break:break-all;font-size:13px;line-height:1.55;text-decoration:underline;">' . $safeLink . '</a>
            ',
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
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $html = $this->buildMinimalEmailLayout(
            'Mình giúp bạn lấy lại quyền truy cập nhé',
            'Archive · Bảo mật nhẹ nhàng',
            '
                <p style="margin:0 0 14px;color:#3f1d2e;font-size:17px;line-height:1.65;">Chào Archiver,</p>
                <p style="margin:0 0 14px;color:#3f1d2e;font-size:16px;line-height:1.7;"><strong>Archive</strong> vừa nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                <p style="margin:0;color:#3f1d2e;font-size:16px;line-height:1.7;">Nhấn vào nút bên dưới để tạo mật khẩu mới. Liên kết này chỉ có hiệu lực trong <strong style="color:#db2777;">15 phút</strong> để giữ tài khoản của bạn an toàn.</p>
                <p style="margin:18px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">Tài khoản: <strong style="color:#db2777;">' . $safeUsername . '</strong></p>
            ',
            'Tạo mật khẩu mới',
            $safeLink,
            '
                <p style="margin:0 0 9px;color:#6b7280;font-size:13px;line-height:1.55;">Nếu nút không hoạt động, bạn có thể sao chép liên kết này và mở trong trình duyệt:</p>
                <a href="' . $safeLink . '" style="color:#db2777;word-break:break-all;font-size:13px;line-height:1.55;text-decoration:underline;">' . $safeLink . '</a>
                <p style="margin:16px 0 0;color:#6b7280;font-size:13px;line-height:1.55;">Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email. Tài khoản của bạn vẫn được giữ an toàn.</p>
            ',
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

    private function buildMinimalEmailLayout(string $title, string $eyebrow, string $bodyHtml, string $buttonText, string $buttonUrl, string $noteHtml, string $footerTitle): string
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
<body style="margin:0;padding:0;background:#fff1f7;font-family:Arial,Helvetica,sans-serif;color:#3f1d2e;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff1f7;padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #fce7f3;border-radius:30px;overflow:hidden;box-shadow:0 16px 38px rgba(219,39,119,0.12);">
          <tr>
            <td style="padding:34px 32px 30px;">
              <div style="text-align:center;margin:0 0 22px;">
                <span style="display:inline-block;background:#fff7fb;border:1px solid #fbcfe8;border-radius:999px;padding:7px 14px;color:#db2777;font-size:12px;font-weight:700;letter-spacing:.2px;">' . $safeEyebrow . '</span>
              </div>
              <h1 style="margin:0 0 20px;text-align:center;color:#3f1d2e;font-size:28px;line-height:1.25;font-weight:800;">' . $safeTitle . '</h1>
              <div style="width:58px;height:4px;margin:0 auto 28px;background:#ec4899;border-radius:999px;line-height:4px;font-size:0;">&nbsp;</div>
              <div style="background:#fff7fb;border:1px solid #fce7f3;border-radius:24px;padding:24px;">
                ' . $bodyHtml . '
              </div>
              <div style="text-align:center;margin:28px 0 26px;">
                <a href="' . $buttonUrl . '" style="display:inline-block;background:#ec4899;background-image:linear-gradient(135deg,#f472b6 0%,#db2777 100%);color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;border-radius:999px;padding:14px 28px;box-shadow:0 10px 20px rgba(219,39,119,0.20);">' . $safeButtonText . '</a>
              </div>
              <div style="border-top:1px solid #fce7f3;border-bottom:1px solid #fce7f3;padding:16px 0;font-size:13px;line-height:1.55;color:#6b7280;">' . $noteHtml . '</div>
              <p style="margin:26px 0 4px;text-align:center;color:#db2777;font-size:14px;font-weight:800;">' . $safeFooterTitle . '</p>
              <p style="margin:0;text-align:center;color:#6b7280;font-size:12px;line-height:1.6;">Dự án môn học UEH: Phát triển ứng dụng Web</p>
              <p style="margin:0;text-align:center;color:#6b7280;font-size:12px;line-height:1.6;">Nhóm 7 — Better Together | UEH</p>
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
