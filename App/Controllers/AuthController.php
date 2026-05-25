<?php


namespace App\Controllers; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nhúng file cấu hình và file Model
require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/UserModel.php';     
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Models\UserModel;
use Database;
use PDOException;

class AuthController {
    private $conn;
    private $userModel;

    // Hàm khởi tạo nhận kết nối DB truyền vào
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->userModel = new UserModel($db_connection);
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

    // Xử lý Quên/Đổi mật khẩu (Đã thêm tính năng chặn trùng mật khẩu cũ)
    // Xử lý Quên/Đổi mật khẩu (Đã tối ưu hóa kiểm tra mật khẩu cũ chuẩn bảo mật)
    public function forgotPasswordProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($new_password)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Mật khẩu mới xác nhận không khớp.";
                header('Location: ' . app_url('App/Views/auth/forgotpassword.php'));
                exit();
            }

            $user = $this->userModel->findByCredentials($email);
            if (!$user) {
                $_SESSION['error'] = "Không tìm thấy tài khoản nào liên kết với Email này.";
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
    } elseif ($_GET['action'] === 'forgot') {
        $controller->forgotPasswordProcess();
    } elseif ($_GET['action'] === 'logout') {
        $controller->logout();
    }
}
