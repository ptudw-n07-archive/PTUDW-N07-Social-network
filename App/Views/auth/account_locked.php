<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản bị khóa | Archive</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>Public/assets/img/favicon-48x48.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/login-style.css">
    <style>
        .locked-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .locked-outline-btn {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 999px;
            padding: 10px 18px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .locked-support {
            display: none;
            margin-top: 18px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(217, 140, 163, 0.12);
            color: #663d4b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Tài khoản của bạn đã bị khóa</h2>
        <p class="subtitle">Bạn vẫn đăng nhập thành công, nhưng tài khoản hiện không thể tiếp tục sử dụng Archive.</p>

        <div class="locked-actions">
            <a class="locked-outline-btn" href="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout">Quay về đăng nhập</a>
            <button type="button" class="locked-outline-btn" id="supportBtn">Liên hệ hỗ trợ</button>
        </div>

        <div class="locked-support" id="supportInfo">
            Vui lòng gửi email hỗ trợ tới hotro@archive.vn
        </div>
    </div>

    <script>
        document.getElementById('supportBtn').addEventListener('click', function() {
            document.getElementById('supportInfo').style.display = 'block';
        });
    </script>
</body>
</html>
