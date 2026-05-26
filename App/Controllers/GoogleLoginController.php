<?php

use App\Models\UserModel;
use App\Services\CsrfService;
use Google\Client;
use Google\Service\Oauth2;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Services/CsrfService.php';

final class GoogleLoginController
{
    private UserModel $userModel;

    public function __construct($db_connection)
    {
        $this->userModel = new UserModel($db_connection);
    }

    public function handle(): void
    {
        try {
            $client = $this->createClient();

            if (isset($_GET['error'])) {
                throw new RuntimeException('Google login was cancelled or denied.');
            }

            if (!isset($_GET['code'])) {
                $this->redirectToGoogle($client);
            }

            $this->handleCallback($client);
        } catch (Throwable $e) {
            error_log('[GoogleLogin] ' . $e->getMessage());
            $_SESSION['error'] = $this->isMissingEnvError($e->getMessage())
                ? $e->getMessage()
                : 'Không thể đăng nhập bằng Google lúc này. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/login.php');
        }
    }

    private function createClient(): Client
    {
        $clientId = app_env('GOOGLE_LOGIN_CLIENT_ID');
        $clientSecret = app_env('GOOGLE_LOGIN_CLIENT_SECRET');
        $redirectUri = app_env('GOOGLE_LOGIN_REDIRECT_URI');

        foreach ([
            'GOOGLE_LOGIN_CLIENT_ID' => $clientId,
            'GOOGLE_LOGIN_CLIENT_SECRET' => $clientSecret,
            'GOOGLE_LOGIN_REDIRECT_URI' => $redirectUri
        ] as $key => $value) {
            if (!$value) {
                throw new RuntimeException('Missing env: ' . $key);
            }
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);

        return $client;
    }

    private function redirectToGoogle(Client $client): void
    {
        $state = bin2hex(random_bytes(32));
        $_SESSION['google_login_state'] = $state;

        $client->setState($state);
        header('Location: ' . $client->createAuthUrl());
        exit();
    }

    private function handleCallback(Client $client): void
    {
        $state = (string) ($_GET['state'] ?? '');
        $sessionState = (string) ($_SESSION['google_login_state'] ?? '');
        unset($_SESSION['google_login_state']);

        if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
            throw new RuntimeException('Google OAuth state is invalid.');
        }

        $token = $client->fetchAccessTokenWithAuthCode((string) $_GET['code']);

        if (isset($token['error'])) {
            throw new RuntimeException('Google token error: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        $client->setAccessToken($token);
        $oauth = new Oauth2($client);
        $profile = $oauth->userinfo->get();

        $googleId = trim((string) $profile->getId());
        $email = trim((string) $profile->getEmail());
        $name = trim((string) $profile->getName());
        $picture = trim((string) $profile->getPicture());

        if ($googleId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Google profile does not contain a valid email.');
        }

        $user = $this->userModel->findByGoogleId($googleId);

        if (!$user) {
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                $this->userModel->linkGoogleAccount((int) $user['UserID'], $googleId, $picture ?: null);
                $user = $this->userModel->findById((int) $user['UserID']);
            }
        }

        if (!$user) {
            $fullName = $name !== '' ? $name : strstr($email, '@', true);
            $username = $this->userModel->generateUniqueUsernameFromGoogle($email);
            $userId = $this->userModel->createGoogleUser($fullName, $username, $email, $googleId, $picture ?: null);

            if (!$userId) {
                throw new RuntimeException('Could not create Google user.');
            }

            $user = $this->userModel->findById((int) $userId);
        }

        if (!$user) {
            throw new RuntimeException('Could not load Google user.');
        }

        if (isset($user['IsActive']) && (int) $user['IsActive'] === 0) {
            $this->redirect('App/Views/auth/account_locked.php');
        }

        $this->startUserSession($user);

        if (($user['RoleName'] ?? '') === 'Admin') {
            $this->redirect('App/Views/admin/dashboard.php');
        }

        $this->redirect('App/Views/post/feed.php');
    }

    private function startUserSession(array $user): void
    {
        CsrfService::regenerate();

        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['user_name'] = $user['FullName'];
        $_SESSION['role'] = $user['RoleName'];
        $_SESSION['role_id'] = $user['RoleID'];

        if (!empty($user['ProfilePictureUrl'])) {
            $_SESSION['ProfilePictureUrl'] = $user['ProfilePictureUrl'];
        }
    }

    private function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
        exit();
    }

    private function isMissingEnvError(string $message): bool
    {
        return str_starts_with($message, 'Missing env: GOOGLE_LOGIN_');
    }
}

$database = new Database();
$controller = new GoogleLoginController($database->connect());
$controller->handle();
