<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}
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
            <div style="color: #dc3545; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(220, 53, 69, 0.08); border-radius: 4px;">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div style="color: #198754; padding: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; background: rgba(25, 135, 84, 0.08); border-radius: 4px;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

       <form action="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=login" method="POST">
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

        <div class="extra-links" style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; font-size: 14px;">
            
            <div style="text-align: left; flex: 1;">
                <a href="<?php echo BASE_URL; ?>App/Views/auth/forgotpassword.php" style="color: #666; text-decoration: none;">Quên mật khẩu?</a>
            </div>

            <div style="text-align: center; flex: 0 0 auto; padding: 0 15px;">
                <a href="<?php echo BASE_URL; ?>Public/index.php" style="color: #888; text-decoration: none; font-size: 16px;" title="Về trang chủ">
                    <i class="fa-solid fa-house"></i>
                </a>
            </div>

            <div style="text-align: right; flex: 1; line-height: 1.5;">
                <span style="color: #666;">Chưa có tài khoản?</span><br>
                <a href="<?php echo BASE_URL; ?>App/Views/auth/register.php" style="color: var(--primary-color); font-weight: bold; text-decoration: none;">Đăng ký</a>
            </div>

        </div> </div> </body>
</html>