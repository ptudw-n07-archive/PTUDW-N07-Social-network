<?php
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}
// Định nghĩa lớp điều khiển AdminController để quản lý phân hệ quản trị
class AuthController {
    // Biến nội bộ dùng để lưu trữ cổng kết nối Cơ sở dữ liệu PDO
    private $conn;

    /**
     * Hàm khởi tạo (Constructor)
     * Nhận đối tượng kết nối Cơ sở dữ liệu từ bên ngoài truyền vào khi khởi tạo lớp
     */
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành viên | Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
    <style>
        /* Tinh chỉnh thêm một chút cho trang đăng ký vì form dài hơn */
        .login-container {
            max-width: 460px; /* Cho rộng hơn một tí để dàn hàng icon đẹp hơn */
            margin: 20px;
        }
        .register-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            text-align: left;
        }
        .full-width {
            grid-column: span 2;
        }
    </style>
</head>
<body>
<div class="login-container">
        <h2>Tham gia cùng chúng mình!</h2>
        <p class="subtitle">Tạo tài khoản để kết nối và chia sẻ ngay</p>
                <?php if(isset($_SESSION['error'])): ?>
            <div style="color: #dc3545; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(220, 53, 69, 0.08); border-radius: 4px;">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div style="color: #198754; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(25, 135, 84, 0.08); border-radius: 4px;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <form action="<?php echo BASE_URL; ?>App/Views/auth/process-register.php" method="POST">
            <div class="register-grid">
                <div class="form-group full-width">
                    <label for="fullname">Họ và Tên</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Nguyễn Văn A" required>
                </div>

                <div class="form-group">
                    <label for="username">Tài khoản</label>
                    <input type="text" id="username" name="username" placeholder="user123" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="abc@gmail.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-login" style="margin-top: 10px;">TẠO TÀI KHOẢN</button>
        </form>

        <div class="divider">
            <span>HOẶC</span>
        </div>

        <div class="extra-links" style="justify-content: center;">
            <p>Đã có tài khoản? <a href="<?php echo BASE_URL; ?>App/Views/auth/login.php" style="color: var(--primary-color); margin-left: 5px;">Đăng nhập ngay</a></p>
        </div>

        <a href="<?php echo BASE_URL; ?>Public/index.php" class="back-home"><i class="fa-solid fa-house"></i> Về trang chủ</a>
    </div>

</body>
</html>