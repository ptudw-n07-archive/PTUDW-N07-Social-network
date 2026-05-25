<?php
require_once __DIR__ . '/../../../Config/Database.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$resetEmail = $_SESSION['password_reset_email'] ?? '';
$showResetForm = $resetEmail !== '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu | Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
</head>
<body>

    <div class="login-container">
        <h2>Quên mật khẩu?</h2>
        <p class="subtitle">Nhập email đăng ký để nhận mã OTP đặt lại mật khẩu</p>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div style="color: #dc3545; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(220, 53, 69, 0.1); border-radius: 4px;">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div style="color: #198754; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(25, 135, 84, 0.1); border-radius: 4px;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=sendResetOtp" method="POST">
            <div class="form-group">
                <label for="email"><i class="fa-regular fa-envelope"></i> Email đã đăng ký</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Nhập địa chỉ email của bạn"
                    value="<?php echo htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <button type="submit" class="btn-login"><?php echo $showResetForm ? 'GỬI LẠI MÃ OTP' : 'GỬI MÃ OTP'; ?></button>
        </form>

        <?php if ($showResetForm): ?>
            <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=resetWithOtp" method="POST" style="margin-top: 18px;">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="otp"><i class="fa-solid fa-key"></i> Mã OTP</label>
                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        placeholder="Nhập mã OTP 6 chữ số"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="new_password"><i class="fa-solid fa-lock"></i> Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fa-solid fa-shield-halved"></i> Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
                </div>

                <button type="submit" class="btn-login">XÁC NHẬN ĐỔI MẬT KHẨU</button>
            </form>
        <?php endif; ?>

        <div class="extra-links" style="justify-content: center; gap: 15px;">
            <a href="<?php echo BASE_URL; ?>App/Views/auth/login.php">Quay lại Đăng nhập</a>
            <span style="color: #ccc;">|</span>
            <a href="<?php echo BASE_URL; ?>App/Views/auth/register.php">Đăng ký tài khoản</a>
        </div>
    </div>

</body>
</html>
