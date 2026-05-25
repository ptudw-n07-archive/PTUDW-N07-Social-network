<?php


namespace App\Controllers; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nhúng file cấu hình và file Model
require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/UserModel.php';     
require_once __DIR__ . '/../Models/PasswordResetOtpModel.php';
require_once __DIR__ . '/../Services/GmailService.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Models\PasswordResetOtpModel;
use App\Models\UserModel;
use App\Services\GmailService;
use Database;
use PDOException;
use Throwable;

class AuthController {
    private $conn;
    private $userModel;
    private PasswordResetOtpModel $passwordResetOtpModel;
    private GmailService $gmailService;

    // Hàm khởi tạo nhận kết nối DB truyền vào
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->userModel = new UserModel($db_connection);
        $this->passwordResetOtpModel = new PasswordResetOtpModel($db_connection);
        $this->gmailService = new GmailService();
    }

    // Xử lý Đăng ký tài khoản
    public function registerProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           
            $name = trim($_POST['fullname'] ?? $_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($name) || empty($username) || empty($email) || empty($password)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ tất cả các trường.";
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
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $_SESSION['error'] = "Vui lòng điền đầy đủ tài khoản và mật khẩu.";
                header('Location: ' . app_url('App/Views/auth/login.php'));
                exit();
            }

            $user = $this->userModel->login($username, $password);

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
            exit();
        }

        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Vui lòng nhập email hợp lệ.";
            header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
            exit();
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $_SESSION['error'] = "Không tìm thấy tài khoản nào liên kết với email này.";
            header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
            exit();
        }

        $otp = (string) random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $userId = (int) $user['UserID'];

        if (!$this->passwordResetOtpModel->create($userId, $user['Email'], $otpHash)) {
            $_SESSION['error'] = "Không thể tạo mã OTP lúc này.";
            header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
            exit();
        }

        try {
            $this->gmailService->sendOtp($user['Email'], $otp);
        } catch (Throwable $e) {
            $this->passwordResetOtpModel->invalidateActiveOtps($userId);
            $_SESSION['error'] = "Không thể gửi OTP qua Gmail. Vui lòng thử lại.";
            header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
            exit();
        }

        $_SESSION['password_reset_email'] = $user['Email'];
        $_SESSION['success'] = "Mã OTP đã được gửi tới email của bạn. Mã có hiệu lực trong 5 phút.";
        header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
        exit();
    }

    public function forgotPasswordProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? ($_SESSION['password_reset_email'] ?? ''));
            $otp = preg_replace('/\D+/', '', $_POST['otp'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($otp) || empty($new_password)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Email không hợp lệ.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if (!preg_match('/^\d{6}$/', $otp)) {
                $_SESSION['error'] = "Mã OTP phải gồm 6 chữ số.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Mật khẩu mới xác nhận không khớp.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $_SESSION['error'] = "Không tìm thấy tài khoản nào liên kết với email này.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            $otpRecord = $this->passwordResetOtpModel->findLatestActiveByEmail($email);
            if (!$otpRecord) {
                $_SESSION['error'] = "Mã OTP không tồn tại hoặc đã được sử dụng.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if ((int) ($otpRecord['Attempts'] ?? 0) >= 5) {
                $this->passwordResetOtpModel->markUsed((int) $otpRecord['OtpID']);
                $_SESSION['error'] = "Mã OTP đã bị khóa do nhập sai quá nhiều lần. Vui lòng gửi mã mới.";
                unset($_SESSION['password_reset_email']);
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if (strtotime($otpRecord['ExpiresAt']) < time()) {
                $this->passwordResetOtpModel->markUsed((int) $otpRecord['OtpID']);
                $_SESSION['error'] = "Mã OTP đã hết hạn. Vui lòng gửi mã mới.";
                unset($_SESSION['password_reset_email']);
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if (!password_verify($otp, $otpRecord['OtpHash'])) {
                $this->passwordResetOtpModel->incrementAttempts((int) $otpRecord['OtpID']);
                $_SESSION['password_reset_email'] = $email;
                $_SESSION['error'] = "Mã OTP không chính xác.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            $current_db_password = $user['PasswordHash'] ?? $user['Password'] ?? '';
            
            // Kiểm tra xem mật khẩu mới có trùng mật khẩu cũ không (áp dụng cho cả dạng hash và dạng thô)
            if (password_verify($new_password, $current_db_password) || $new_password === $current_db_password) {
                $_SESSION['error'] = "Mật khẩu mới không được trùng với mật khẩu cũ gần nhất.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if ($this->userModel->updatePassword($email, $new_password)) {
                $this->passwordResetOtpModel->markUsed((int) $otpRecord['OtpID']);
                unset($_SESSION['password_reset_email']);
                $_SESSION['success'] = "Đổi mật khẩu thành công! Hãy đăng nhập lại bằng mật khẩu mới.";
                header('Location: ' . app_url('App/Views/auth/login.php'));
                exit();
            } else {
                $_SESSION['error'] = "Không thể cập nhật mật khẩu lúc này.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }
        }
    }
    // Đăng xuất xóa session
    public function logout() {
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
        header('Location: ' . app_url('App/Views/auth/login.php'));
        exit();
    }
}


if (isset($_GET['action'])) {
    $database = new Database();
    $db_connection = $database->connect();
    
    $controller = new AuthController($db_connection);

    if ($_GET['action'] === 'login') {
        $controller->loginProcess();
    } elseif ($_GET['action'] === 'register') {
        $controller->registerProcess();
    } elseif ($_GET['action'] === 'sendResetOtp') {
        $controller->sendResetOtpProcess();
    } elseif ($_GET['action'] === 'resetWithOtp') {
        $controller->forgotPasswordProcess();
    } elseif ($_GET['action'] === 'forgot') {
        $controller->forgotPasswordProcess();
    } elseif ($_GET['action'] === 'logout') {
        $controller->logout();
    }
}
