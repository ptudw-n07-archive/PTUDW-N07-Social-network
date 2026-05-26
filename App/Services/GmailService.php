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

        $subject = 'Kích hoạt tài khoản thành viên của bạn — Archive';
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

        $html = $this->emailLayout(
            'Chào mừng bạn đến với Archive',
            '#12213f',
            '#f4c430',
            'KÍCH HOẠT TÀI KHOẢN',
            $safeLink,
            '
                <p>Xin chào <strong>' . $safeUsername . '</strong>,</p>
                <p>Cảm ơn bạn đã đăng ký tài khoản thành viên tại hệ thống của chúng tôi. Để hoàn tất quy trình và kích hoạt tài khoản, vui lòng nhấn vào nút bên dưới:</p>
            ',
            '
                <p style="margin:0 0 8px;color:#777;font-size:14px;line-height:1.5;">Nếu nút trên không hoạt động, bạn có thể sao chép liên kết này dán vào trình duyệt:</p>
                <a href="' . $safeLink . '" style="color:#2f6fb7;word-break:break-all;font-size:14px;">' . $safeLink . '</a>
            '
        );

        return $this->sendEmail($to, $subject, $html, 'text/html');
    }

    public function sendPasswordResetEmail(string $to, string $username, string $resetLink): bool
    {
        if ($this->isDebugMode()) {
            return $this->writeDebugMail('password_reset', $to, $username, $resetLink);
        }

        $subject = 'Yêu cầu khôi phục mật khẩu — Archive';
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $html = $this->emailLayout(
            'Yêu cầu đặt lại mật khẩu',
            '#c8102e',
            '#ffffff',
            'ĐẶT LẠI MẬT KHẨU',
            $safeLink,
            '
                <p>Xin chào <strong>' . $safeUsername . '</strong>,</p>
                <p>Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Liên kết khôi phục này chỉ có hiệu lực sử dụng trong vòng <strong>15 phút</strong>. Nhấn vào nút bên dưới để đổi mật khẩu:</p>
            ',
            '
                <p style="margin:0 0 8px;color:#777;font-size:14px;line-height:1.5;">Nếu nút trên không hoạt động, bạn có thể sao chép liên kết này dán vào trình duyệt:</p>
                <a href="' . $safeLink . '" style="color:#2f6fb7;word-break:break-all;font-size:14px;">' . $safeLink . '</a>
                <p style="margin:18px 0 0;color:#777;font-size:14px;line-height:1.5;">Nếu bạn không yêu cầu hành động này, vui lòng bỏ qua email bảo mật này một cách an toàn.</p>
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

    private function emailLayout(string $title, string $primaryColor, string $buttonTextColor, string $buttonText, string $buttonUrl, string $contentHtml, string $extraHtml): string
    {
        return '
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#111;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f5;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dedede;border-radius:12px;overflow:hidden;">
          <tr>
            <td style="padding:38px 34px 28px;">
              <h1 style="margin:0 0 28px;text-align:center;color:' . $primaryColor . ';font-size:30px;line-height:1.2;font-weight:800;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
              <div style="font-size:19px;line-height:1.55;color:#111;">' . $contentHtml . '</div>
              <div style="text-align:center;margin:34px 0 32px;">
                <a href="' . $buttonUrl . '" style="display:inline-block;background:' . $primaryColor . ';color:' . $buttonTextColor . ';text-decoration:none;font-size:18px;font-weight:800;letter-spacing:.4px;border-radius:6px;padding:16px 42px;">' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '</a>
              </div>
              <div style="font-size:14px;line-height:1.5;color:#777;">' . $extraHtml . '</div>
              <hr style="border:0;border-top:1px solid #e5e5e5;margin:30px 0 22px;">
              <p style="margin:0;text-align:center;color:#888;font-size:14px;">&copy; 2026 Archive — Dự án môn Lập trình Web UEH</p>
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
