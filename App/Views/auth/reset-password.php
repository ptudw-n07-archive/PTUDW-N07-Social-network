<?php
require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/../../Services/CsrfService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$resetToken = $resetToken ?? ($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu | Social Network</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>Public/assets/img/favicon-48x48.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
</head>
<body>
    <div class="login-container">
        <h2>Đặt lại mật khẩu</h2>
        <p class="subtitle">Nhập mật khẩu mới cho tài khoản của bạn</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="auth-alert-error">
                <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=reset_password" method="POST">
            <?= \App\Services\CsrfService::hiddenField() ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="new_password"><i class="fa-solid fa-lock"></i> Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fa-solid fa-shield-halved"></i> Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" minlength="6" required>
            </div>

            <button type="submit" class="btn-login">CẬP NHẬT MẬT KHẨU</button>
        </form>

        <div class="extra-links extra-links-centered-single">
            <a href="<?php echo app_url('login'); ?>">Quay lại Đăng nhập</a>
        </div>
    </div>
</body>
</html>
