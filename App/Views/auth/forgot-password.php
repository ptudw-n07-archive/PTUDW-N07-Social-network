<?php
require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/../../Services/CsrfService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu | Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
</head>
<body>
    <div class="login-container">
        <h2>Quên mật khẩu?</h2>
        <p class="subtitle">Nhập email đăng ký để nhận liên kết đặt lại mật khẩu</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="color:#dc3545;padding:8px;margin-bottom:15px;font-size:14px;text-align:center;background:rgba(220,53,69,.1);border-radius:4px;">
                <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="color:#198754;padding:8px;margin-bottom:15px;font-size:14px;text-align:center;background:rgba(25,135,84,.1);border-radius:4px;">
                <?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=forgot_password" method="POST">
            <?= \App\Services\CsrfService::hiddenField() ?>
            <div class="form-group">
                <label for="email"><i class="fa-regular fa-envelope"></i> Email đã đăng ký</label>
                <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email của bạn" required>
            </div>

            <button type="submit" class="btn-login">GỬI LIÊN KẾT KHÔI PHỤC</button>
        </form>

        <div class="extra-links" style="justify-content:center;gap:15px;">
            <a href="<?php echo app_url('login'); ?>">Quay lại Đăng nhập</a>
            <span style="color:#ccc;">|</span>
            <a href="<?php echo app_url('register'); ?>">Đăng ký tài khoản</a>
        </div>
    </div>
</body>
</html>
