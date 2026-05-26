<?php
namespace App\Controllers;

use App\Models\PasswordResetTokenModel;
use App\Models\UserModel;
use App\Services\CsrfService;
use App\Services\GmailService;
use Database;
use PDOException;
use Throwable;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/PasswordResetTokenModel.php';
require_once __DIR__ . '/../Services/CsrfService.php';
require_once __DIR__ . '/../Services/GmailService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class AuthController {
    private const VERIFY_TOKEN_TTL_HOURS = 24;
    private const RESET_TOKEN_TTL_MINUTES = 15;

    private $conn;
    private UserModel $userModel;
    private PasswordResetTokenModel $passwordResetTokenModel;
    private GmailService $gmailService;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->userModel = new UserModel($db_connection);
        $this->passwordResetTokenModel = new PasswordResetTokenModel($db_connection);
        $this->gmailService = new GmailService();
    }

<<<<<<< Updated upstream
    public function registerProcess(): void {
        $this->register();
    }

    public function register(): void {
=======
    // Xử lý Đăng ký tài khoản
    public function registerProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           
            $name = trim($_POST['fullname'] ?? $_POST['name'] ?? '');
            $username = UserModel::normalizeUsername($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($name) || empty($username) || empty($email) || empty($password)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ tất cả các trường.";
                header('Location: ' . app_url('App/Views/auth/register.php'));
                exit();
            }

            if (!UserModel::isValidUsername($username)) {
                $_SESSION['error'] = "Tên đăng nhập chỉ được gồm 3-50 ký tự, chữ thường, số, dấu gạch dưới hoặc dấu chấm. Không dùng dấu, khoảng trắng hoặc chữ hoa.";
                header('Location: ' . app_url('App/Views/auth/register.php'));
                exit();
            }

            if ($password !== $confirm_password) {
                $_SESSION['error'] = "Mật khẩu xác nhận không trùng khớp.";
                header('Location: ' . app_url('App/Views/auth/register.php'));
                exit();
            }

            if ($this->userModel->usernameExists($username)) {
                $_SESSION['error'] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
                header('Location: ' . app_url('App/Views/auth/register.php'));
                exit();
            }

            if ($this->userModel->emailExists($email)) {
                $_SESSION['error'] = "Email đã được sử dụng. Vui lòng dùng email khác.";
                header('Location: ' . app_url('App/Views/auth/register.php'));
                exit();
            }

            try {
                $registered = $this->userModel->register($name, $username, $email, $password);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    $_SESSION['error'] = "Không thể đăng ký tài khoản lúc này. Vui lòng thử lại.";
                    header('Location: ' . app_url('App/Views/auth/register.php'));
                    exit();
                }

                $registered = false;

                if ($this->userModel->usernameExists($username)) {
                    $_SESSION['error'] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
                } elseif ($this->userModel->emailExists($email)) {
                    $_SESSION['error'] = "Email đã được sử dụng. Vui lòng dùng email khác.";
                } else {
                    $_SESSION['error'] = "Không thể đăng ký tài khoản lúc này. Vui lòng thử lại.";
                }
            }

            if ($registered) {
                $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
                header('Location: ' . app_url('App/Views/auth/login.php'));
                exit();
            }

            $_SESSION['error'] = $_SESSION['error'] ?? "Không thể đăng ký tài khoản lúc này. Vui lòng thử lại.";
            header('Location: ' . app_url('App/Views/auth/register.php'));
            exit();
        }
    }

    // Xử lý Đăng nhập
    public function loginProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawLoginInput = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $loginInput = filter_var($rawLoginInput, FILTER_VALIDATE_EMAIL)
                ? $rawLoginInput
                : UserModel::normalizeUsername($rawLoginInput);

            if (empty($rawLoginInput) || empty($password)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ tài khoản và mật khẩu.";
                header('Location: ' . app_url('App/Views/auth/login.php'));
                exit();
            }

            $user = $this->userModel->login($loginInput, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_name'] = $user['FullName'];
                $_SESSION['role'] = $user['RoleName']; 
                $_SESSION['role_id'] = $user['RoleID'];

                if (isset($user['IsActive']) && (int)$user['IsActive'] === 0) {
                    header('Location: ' . app_url('App/Views/auth/account_locked.php'));
                    exit();
                }

                if ($user['RoleName'] === 'Admin') {
                    header('Location: ' . app_url('App/Views/admin/dashboard.php'));
                } else {
                    header('Location: ' . app_url('App/Views/post/feed.php'));
                }
                exit();
            } else {
                $_SESSION['error'] = "Tài khoản hoặc mật khẩu không chính xác.";
                header('Location: ' . app_url('App/Views/auth/login.php'));
                exit();
            }
        }
    }

    public function sendResetOtpProcess() {
>>>>>>> Stashed changes
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('App/Views/auth/register.php');
        }

        if (!CsrfService::validateRequest()) {
            $_SESSION['error'] = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/register.php');
        }

        $name = trim($_POST['fullname'] ?? $_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name === '' || $username === '' || $email === '' || $password === '') {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ tất cả các trường.';
            $this->redirect('App/Views/auth/register.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email không hợp lệ.';
            $this->redirect('App/Views/auth/register.php');
        }

        if ($password !== $confirmPassword) {
            $_SESSION['error'] = 'Mật khẩu xác nhận không trùng khớp.';
            $this->redirect('App/Views/auth/register.php');
        }

        if ($this->userModel->usernameExists($username)) {
            $_SESSION['error'] = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.';
            $this->redirect('App/Views/auth/register.php');
        }

        if ($this->userModel->emailExists($email)) {
            $_SESSION['error'] = 'Email đã được sử dụng. Vui lòng dùng email khác.';
            $this->redirect('App/Views/auth/register.php');
        }

        $verificationToken = $this->createSecureToken();
        $verificationTokenHash = $this->hashToken($verificationToken);
        $verificationExpiresAt = date('Y-m-d H:i:s', time() + (self::VERIFY_TOKEN_TTL_HOURS * 3600));
        $verifyLink = app_url('App/Controllers/AuthController.php?action=verify_email&token=' . urlencode($verificationToken));

        try {
            $this->conn->beginTransaction();
            $userId = $this->userModel->register($name, $username, $email, $password, $verificationTokenHash, $verificationExpiresAt);

            if (!$userId) {
                throw new \RuntimeException('Could not create pending user.');
            }

            $this->gmailService->sendVerificationEmail($email, $username, $verifyLink);
            $this->conn->commit();

            $_SESSION['success'] = 'Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản trước khi đăng nhập.';
            $this->redirect('App/Views/auth/login.php');
        } catch (PDOException $e) {
            $this->rollBackIfNeeded();
            $this->handleRegisterDatabaseError($e, $username, $email);
        } catch (Throwable $e) {
            $this->rollBackIfNeeded();
            error_log('[AuthRegister] Verification email failed for email_hash=' . hash('sha256', $email) . ': ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể gửi email kích hoạt lúc này. Vui lòng thử lại sau.';
            $this->redirect('App/Views/auth/register.php');
        }
    }

    public function verifyEmail(): void {
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '') {
            $_SESSION['error'] = 'Liên kết kích hoạt không hợp lệ.';
            $this->redirect('App/Views/auth/login.php');
        }

        $user = $this->userModel->findByVerificationTokenHash($this->hashToken($token));

        if (!$user) {
            $_SESSION['error'] = 'Liên kết kích hoạt không hợp lệ hoặc đã được sử dụng.';
            $this->redirect('App/Views/auth/login.php');
        }

        $userId = (int) $user['UserID'];

        if (!empty($user['verification_expires_at']) && strtotime($user['verification_expires_at']) < time()) {
            $this->userModel->clearVerificationToken($userId);
            $_SESSION['error'] = 'Liên kết kích hoạt đã hết hạn. Vui lòng đăng ký lại hoặc liên hệ quản trị viên.';
            $this->redirect('App/Views/auth/login.php');
        }

        if (!$this->userModel->markEmailVerified($userId)) {
            $_SESSION['error'] = 'Không thể kích hoạt tài khoản lúc này. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/login.php');
        }

        $_SESSION['success'] = 'Kích hoạt tài khoản thành công! Bạn có thể đăng nhập ngay.';
        $this->redirect('App/Views/auth/login.php');
    }

    public function loginProcess(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('App/Views/auth/login.php');
        }

        if (!CsrfService::validateRequest()) {
            $_SESSION['error'] = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/login.php');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ tài khoản và mật khẩu.';
            $this->redirect('App/Views/auth/login.php');
        }

        $user = $this->userModel->login($username, $password);

        if (!$user) {
            $_SESSION['error'] = 'Tài khoản hoặc mật khẩu không chính xác.';
            $this->redirect('App/Views/auth/login.php');
        }

        if (isset($user['IsActive']) && (int) $user['IsActive'] === 0) {
            $this->redirect('App/Views/auth/account_locked.php');
        }

        if (isset($user['is_verified']) && (int) $user['is_verified'] !== 1) {
            $_SESSION['error'] = 'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để kích hoạt tài khoản.';
            $this->redirect('App/Views/auth/login.php');
        }

        CsrfService::regenerate();
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['user_name'] = $user['FullName'];
        $_SESSION['role'] = $user['RoleName'];
        $_SESSION['role_id'] = $user['RoleID'];

        if ($user['RoleName'] === 'Admin') {
            $this->redirect('App/Views/admin/dashboard.php');
        }

        $this->redirect('App/Views/post/feed.php');
    }

    public function forgotPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        if (!CsrfService::validateRequest()) {
            $_SESSION['error'] = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Vui lòng nhập email hợp lệ.';
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        $user = $this->userModel->findByEmail($email);

        if ($user) {
            $token = $this->createSecureToken();
            $tokenHash = $this->hashToken($token);
            $userId = (int) $user['UserID'];
            $resetLink = app_url('App/Controllers/AuthController.php?action=reset_password&token=' . urlencode($token));

            try {
                if (!$this->passwordResetTokenModel->create($userId, $user['Email'], $tokenHash, self::RESET_TOKEN_TTL_MINUTES)) {
                    throw new \RuntimeException('Could not create password reset token.');
                }

                $displayName = $user['Username'] ?: ($user['FullName'] ?: $user['Email']);
                $this->gmailService->sendPasswordResetEmail($user['Email'], $displayName, $resetLink);
            } catch (Throwable $e) {
                $this->passwordResetTokenModel->invalidateActiveTokensForUser($userId);
                error_log('[PasswordReset] Email failed for user_id=' . $userId . ' email_hash=' . hash('sha256', (string) $user['Email']) . ': ' . $e->getMessage());
            }
        }

        $_SESSION['success'] = 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu.';
        $this->redirect('App/Views/auth/forgot-password.php');
    }

    public function showResetPasswordForm(): void {
        $token = trim((string) ($_GET['token'] ?? ''));
        $record = $this->getValidResetRecord($token);

        if (!$record) {
            $_SESSION['error'] = 'Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn hoặc đã được sử dụng.';
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        $resetToken = $token;
        require __DIR__ . '/../Views/auth/reset-password.php';
        exit();
    }

    public function resetPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showResetPasswordForm();
        }

        $token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));

        if (!CsrfService::validateRequest()) {
            $_SESSION['error'] = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
            $this->redirectToResetForm($token);
        }

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $record = $this->getValidResetRecord($token);

        if (!$record) {
            $_SESSION['error'] = 'Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn hoặc đã được sử dụng.';
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            $this->redirectToResetForm($token);
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Mật khẩu xác nhận không khớp.';
            $this->redirectToResetForm($token);
        }

        $user = $this->userModel->findByEmail((string) $record['email']);

        if (!$user || (int) $user['UserID'] !== (int) $record['user_id']) {
            $this->passwordResetTokenModel->markUsed((int) $record['id']);
            $_SESSION['error'] = 'Liên kết đặt lại mật khẩu không hợp lệ.';
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        $currentHash = $user['PasswordHash'] ?? $user['Password'] ?? '';
        if ($currentHash !== '' && (password_verify($newPassword, $currentHash) || hash_equals($currentHash, $newPassword))) {
            $_SESSION['error'] = 'Mật khẩu mới không được trùng với mật khẩu cũ.';
            $this->redirectToResetForm($token);
        }

        if (!$this->userModel->updatePasswordById((int) $record['user_id'], $newPassword)) {
            $_SESSION['error'] = 'Không thể cập nhật mật khẩu lúc này.';
            $this->redirectToResetForm($token);
        }

        $this->passwordResetTokenModel->markUsed((int) $record['id']);
        $_SESSION['success'] = 'Đổi mật khẩu thành công! Hãy đăng nhập bằng mật khẩu mới.';
        $this->redirect('App/Views/auth/login.php');
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_unset();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('App/Views/auth/login.php');
    }

    private function getValidResetRecord(string $token): ?array {
        if ($token === '') {
            return null;
        }

        $record = $this->passwordResetTokenModel->findActiveByTokenHash($this->hashToken($token));

        if (!$record) {
            return null;
        }

        if (strtotime($record['expires_at']) < time()) {
            $this->passwordResetTokenModel->markUsed((int) $record['id']);
            return null;
        }

        return $record;
    }

    private function createSecureToken(): string {
        return bin2hex(random_bytes(32));
    }

    private function hashToken(string $token): string {
        return hash('sha256', $token);
    }

    private function redirect(string $path): void {
        header('Location: ' . app_url($path));
        exit();
    }

    private function redirectToResetForm(string $token): void {
        if ($token === '') {
            $this->redirect('App/Views/auth/forgot-password.php');
        }

        $this->redirect('App/Controllers/AuthController.php?action=reset_password&token=' . urlencode($token));
    }

    private function rollBackIfNeeded(): void {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
    }

    private function handleRegisterDatabaseError(PDOException $e, string $username, string $email): void {
        if ($e->getCode() !== '23000') {
            error_log('[AuthRegister] Database error: ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể đăng ký tài khoản lúc này. Vui lòng thử lại.';
            $this->redirect('App/Views/auth/register.php');
        }

        if ($this->userModel->usernameExists($username)) {
            $_SESSION['error'] = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.';
        } elseif ($this->userModel->emailExists($email)) {
            $_SESSION['error'] = 'Email đã được sử dụng. Vui lòng dùng email khác.';
        } else {
            $_SESSION['error'] = 'Không thể đăng ký tài khoản lúc này. Vui lòng thử lại.';
        }

        $this->redirect('App/Views/auth/register.php');
    }
}

if (isset($_GET['action'])) {
    $database = new Database();
    $db_connection = $database->connect();
    $controller = new AuthController($db_connection);
    $action = $_GET['action'];

    if ($action === 'login') {
        $controller->loginProcess();
    } elseif ($action === 'register') {
        $controller->register();
    } elseif ($action === 'verify_email') {
        $controller->verifyEmail();
    } elseif ($action === 'forgot_password' || $action === 'sendResetOtp') {
        $controller->forgotPassword();
    } elseif ($action === 'reset_password') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->resetPassword();
        }

        $controller->showResetPasswordForm();
    } elseif ($action === 'forgot') {
        $controller->forgotPassword();
    } elseif ($action === 'resetWithOtp') {
        $controller->resetPassword();
    } elseif ($action === 'logout') {
        $controller->logout();
    }
}
