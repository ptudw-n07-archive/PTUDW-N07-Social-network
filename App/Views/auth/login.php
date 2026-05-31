<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/../../Services/CsrfService.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
</head>
<body>

    <div class="login-container">
        <h2>Xin chào!</h2>
        <p class="subtitle">Vui lòng đăng nhập để kết nối với bạn bè</p>
             
        <?php if(isset($_SESSION['error'])): ?>
            <div class="auth-alert-error">
                <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="auth-alert-success">
                <?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=login" method="POST">
            <?= \App\Services\CsrfService::hiddenField() ?>
            <div class="form-group">
                <label for="username"><i class="fa-regular fa-user"></i> Tài khoản</label>
                <input type="text" id="username" name="username" placeholder="Tên đăng nhập hoặc Email" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Mật khẩu của bạn" required>
            </div>

            <button type="submit" class="btn-login">ĐĂNG NHẬP NGAY</button>
        </form>

        <div class="divider">
            <span>HOẶC</span>
        </div>

        <a class="google-login-btn" href="<?php echo app_url('App/Controllers/GoogleLoginController.php'); ?>">
            <span class="google-login-icon">G</span>
            <span>Đăng nhập bằng Google</span>
        </a>

        <div class="auth-footer">
            
            <div class="auth-footer-left">
                <a href="<?php echo app_url('forgot-password'); ?>" class="auth-footer-link">Quên mật khẩu?</a>
            </div>

            <div class="auth-footer-center">
                <a href="<?php echo app_url(''); ?>" class="auth-footer-link-home" title="Về trang chủ">
                    <i class="fa-solid fa-house"></i>
                </a>
            </div>

            <div class="auth-footer-right">
                <span class="auth-footer-muted">Chưa có tài khoản?</span><br>
                <a href="<?php echo app_url('register'); ?>" class="auth-link-accent">Đăng ký</a>
            </div>

        </div> </div> </body>
</html>
