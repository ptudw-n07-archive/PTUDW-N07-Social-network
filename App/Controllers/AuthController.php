<?php
namespace App\Controllers; // 1. Khai báo họ tên danh phận cho Controller

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nhúng file cấu hình và file Model vào nhà chung
require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/UserModel.php';     

// 2. KHAI BÁO SỬ DỤNG ĐÚNG NAMESPACE
use App\Models\UserModel;
use Database;

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
                header("Location: " . BASE_URL . "App/Views/auth/register.php");
                exit();
            }

            if ($password !== $confirm_password) {
                $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
                header("Location: " . BASE_URL . "App/Views/auth/register.php");
                exit();
            }

            // Kiểm tra trùng lặp qua hàm exists của UserModel
            if ($this->userModel->exists($username, $email)) {
                $_SESSION['error'] = "Tài khoản hoặc Email này đã tồn tại trên hệ thống.";
                header("Location: " . BASE_URL . "App/Views/auth/register.php");
                exit();
            }

            // Tiến hành đăng ký
            if ($this->userModel->register($name, $username, $email, $password)) {
                $_SESSION['success'] = "Đăng ký thành công! Hãy đăng nhập ngay.";
                header("Location: " . BASE_URL . "App/Views/auth/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Đã xảy ra sự cố trong quá trình lưu dữ liệu.";
                header("Location: " . BASE_URL . "App/Views/auth/register.php");
                exit();
            }
        }
    }

    // Xử lý Đăng nhập tài khoản
    public function loginProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login_input = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($login_input) || empty($password)) {
                $_SESSION['error'] = "Vui lòng nhập tài khoản và mật khẩu.";
                header("Location: " . BASE_URL . "App/Views/auth/login.php");
                exit();
            }

            // Tìm user dựa vào tài khoản/email
            $user = $this->userModel->findByCredentials($login_input);

            // Kiểm tra mật khẩu (Giả định team đang lưu chuỗi thuần hoặc đã hash)
            // LƯU Ý: Nếu dùng hash password_hash, hãy đổi dòng dưới thành: if ($user && password_verify($password, $user['PasswordHash']))
            if ($user && password_verify($password, $user['PasswordHash'])) {
                
                // Lưu thông tin vào session để các trang feed, profile nhận diện
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_name'] = $user['FullName'];
                $_SESSION['ProfilePictureUrl'] = $user['ProfilePictureUrl'];
                $_SESSION['role'] = $user['RoleName'];

                // Phân quyền: Nếu là Admin dắt qua trang quản trị, ngược lại qua bảng tin chính
                if ($user['RoleName'] === 'Admin') {
                    header("Location: " . BASE_URL . "App/Views/admin/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "App/Views/feed.php");
                }
                exit();
            } else {
                $_SESSION['error'] = "Tài khoản hoặc mật khẩu không chính xác.";
                header("Location: " . BASE_URL . "App/Views/auth/login.php");
                exit();
            }
        }
    }

    // Xử lý Quên mật khẩu
    public function forgotPasswordProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($new_password)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
                header("Location: " . BASE_URL . "App/Views/auth/forgotpassword.php");
                exit();
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Mật khẩu mới xác nhận không khớp.";
                header("Location: " . BASE_URL . "App/Views/auth/forgotpassword.php");
                exit();
            }

            $user = $this->userModel->findByCredentials($email);
            if (!$user) {
                $_SESSION['error'] = "Không tìm thấy tài khoản nào liên kết với Email này.";
                header("Location: " . BASE_URL . "App/Views/auth/forgotpassword.php");
                exit();
            }

            if ($this->userModel->updatePassword($email, $new_password)) {
                $_SESSION['success'] = "Đổi mật khẩu thành công! Hãy đăng nhập lại bằng mật khẩu mới.";
                header("Location: " . BASE_URL . "App/Views/auth/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Không thể cập nhật mật khẩu lúc này.";
                header("Location: " . BASE_URL . "App/Views/auth/forgotpassword.php");
                exit();
            }
        }
    }

    // Đăng xuất xóa session
    public function logout() {
        session_destroy();
        header("Location: " . BASE_URL . "App/Views/auth/login.php");
        exit();
    }
}