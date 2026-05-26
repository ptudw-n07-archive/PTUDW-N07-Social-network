<?php

namespace App\Services;

/**
 * CSRF Protection Service
 * 
 * Provides token generation and validation for CSRF protection.
 * Uses session-based tokens with per-request validation.
 */
class CsrfService
{
    /**
     * Get or generate the CSRF token stored in session.
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Render a hidden input field containing the CSRF token.
     * Usage: <?= CsrfService::hiddenField() ?>
     */
    public static function hiddenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate a CSRF token against the session token.
     * Uses hash_equals to prevent timing attacks.
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Validate the CSRF token from the current request.
     * Checks: POST body, JSON payload (php://input), and X-CSRF-Token header.
     */
    public static function validateRequest(): bool
    {
        $token = null;

        // Check POST body
        if (!empty($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }

        // Check JSON payload (admin endpoints)
        if ($token === null) {
            $rawBody = file_get_contents('php://input');
            if (!empty($rawBody)) {
                $payload = json_decode($rawBody, true);
                if (is_array($payload) && !empty($payload['csrf_token'])) {
                    $token = $payload['csrf_token'];
                }
            }
        }

        // Check X-CSRF-Token header
        if ($token === null) {
            $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? '';
            if (!empty($headerToken)) {
                $token = $headerToken;
            }
        }

        return self::validateToken($token);
    }

    /**
     * Regenerate the CSRF token (call after login/logout for security).
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
