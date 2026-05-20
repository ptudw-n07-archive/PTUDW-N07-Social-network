<?php
// 1. Khởi động Session hệ thống nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Nhúng file PostController nằm TRONG folder App (Thêm /../App/ vào đường dẫn)
require_once __DIR__ . '/../App/Controllers/PostController.php';

// ✨ KHAI BÁO SỬ DỤNG CLASS CÓ NAMESPACE ĐỂ XÓA VỆT VÀNG CỦA VS CODE
use App\Controllers\PostController;

// 3. Khai báo BASE_URL nếu chưa định nghĩa
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

// Chỉnh lại link dẫn vào Views vì nó đã chui vào trong App/Views/
$loginUrl    = BASE_URL . "App/Views/auth/login.php";
$registerUrl = BASE_URL . "App/Views/auth/register.php";
$homeUrl     = BASE_URL . "Public/index.php";

// 4. Khởi tạo đối tượng Controller
$postController = new PostController();

// 5. Chạy hàm lấy dữ liệu để đổ ra giao diện Archive
$result = $postController->index();

if (isset($result['posts'])) {
    $posts         = $result['posts'];
    $totalPosts    = $result['totalPosts'];
    $totalUsers    = $result['totalUsers'];
    $totalComments = $result['totalComments'];
} else {
    $posts         = $result;
    $totalPosts    = count($posts);
    $totalUsers    = count(array_unique(array_column($posts, 'UserID')));
    $totalComments = array_sum(array_column($posts, 'CommentCount'));
}

// 6. CÁC HÀM HELPER ĐỊNH DẠNG GIAO DIỆN (Giữ nguyên bên dưới...)
function imagePath($path) {
    if (empty($path)) {
        return "assets/img/default-avatar.jpg";
    }
    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }
    return str_replace("Public/", "", $path);
}

function timeAgo($datetime) {
    if (empty($datetime)) return "Không rõ thời gian";
    
    $time = strtotime($datetime);
    $now  = time();
    $diff = $now - $time;
    
    if ($diff < 60) return "vừa xong";
    
    $intervals = [
        31536000 => 'năm',
        2592000  => 'tháng',
        604800   => 'tuần',
        86400    => 'ngày',
        3600     => 'giờ',
        60       => 'phút'
    ];
    
    foreach ($intervals as $secs => $str) {
        $d = $diff / $secs;
        if ($d >= 1) {
            return round($d) . ' ' . $str . ' trước';
        }
    }
    return $datetime;
}

function formatNumber($number) {
    return number_format((float)$number, 0, '.', ',');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/Public/assets/CSS/style.css">
</head>

<body>
<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">

            <div class="col-4 d-flex align-items-center">
                <a href="<?= $homeUrl ?>" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center align-items-center">
                <a href="<?= $loginUrl ?>" class="header-badge text-decoration-none">
                    <i class="bi bi-stars"></i>
                </a>
            </div>

            <div class="col-4 d-flex justify-content-end align-items-center">
                <div class="header-actions">
                    <a href="<?= $loginUrl ?>" class="header-search-btn" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="header-star-btn" title="About us">
                        <i class="bi bi-star"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="header-login-btn" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                        <span>Đăng nhập</span>
                    </a>

                    <a href="<?= $registerUrl ?>" class="header-register-btn" title="Đăng ký">
                        <i class="bi bi-plus-lg"></i>
                        <span>Đăng ký</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</header>

<section class="hero-section d-flex align-items-center">
    <div class="container">
        <div class="hero-wrap position-relative">
            <div class="floating-shape shape-left-top"></div>
            <div class="floating-shape shape-right-top"></div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="text-center hero-content">
                        <div class="hero-pill d-inline-flex align-items-center gap-2 mb-4">
                            <span class="mini-star"><i class="bi bi-stars"></i></span>
                            <span>MỘT NƠI ĐỂ NÓI KHẼ</span>
                        </div>

                        <h1 class="hero-title">ARCHIVE</h1>

                        <p class="hero-subtitle mx-auto">
                            Viết vài dòng ngắn, giữ lại vài cảm xúc nhỏ và lướt trong một không gian gọn, nhẹ, mềm.
                        </p>

                        <div class="hero-divider mx-auto mb-4"></div>

                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mb-5">
                            <button class="btn hero-main-btn" onclick="scrollToFeed()">Bắt đầu</button>
                            <a href="<?= $loginUrl ?>" class="btn hero-outline-btn">Đăng nhập</a>
                        </div>

                        <div class="hero-stats mx-auto">
                            <div class="row g-0">
                                <div class="col-4">
                                    <div class="stat-box">
                                        <h4><?= formatNumber($totalUsers) ?>+</h4>
                                        <p>người dùng</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <h4><?= formatNumber($totalPosts) ?>+</h4>
                                        <p>bài viết</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <h4><?= formatNumber($totalComments) ?>+</h4>
                                        <p>bình luận</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="scroll-down mt-5" onclick="scrollToFeed()">
                            <span>Xem bài đăng</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section id="feed-section" class="feed-section py-5">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4">

            <div class="col-lg-1 d-none d-lg-block">
                <aside class="left-sidebar d-flex flex-column align-items-center gap-4">
                    <div class="sidebar-logo">
                        <i class="bi bi-circle-square"></i>
                    </div>

                    <a href="<?= $homeUrl ?>" class="sidebar-icon active" title="Trang chủ">
                        <i class="bi bi-house-door-fill"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="sidebar-icon" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="sidebar-icon" title="Tạo bài viết">
                        <i class="bi bi-plus-square"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="sidebar-icon" title="Thông báo">
                        <i class="bi bi-heart"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="sidebar-icon" title="Trang cá nhân">
                        <i class="bi bi-person"></i>
                    </a>

                    <a href="<?= $loginUrl ?>" class="sidebar-icon mt-auto" title="About us">
                        <i class="bi bi-pin-angle"></i>
                    </a>
                </aside>
            </div>

            <div class="col-lg-7 col-md-8">
                <div class="feed-title text-center mb-4">Trang chủ</div>

                <form action="<?= $loginUrl ?>" method="GET" class="bg-white p-3 p-md-4 mb-4 post-composer">
                    <div class="d-flex gap-3 align-items-start">
                        <img src="assets/img/default-avatar.jpg" class="avatar" alt="avatar">

                        <div class="flex-grow-1">
                            <h6 class="mb-2 fw-semibold">Bạn đang nghĩ gì?</h6>

                            <textarea 
                                name="content"
                                class="form-control composer-input" 
                                rows="3" 
                                placeholder="Viết vài dòng cho hôm nay..."
                            ></textarea>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">Đăng nhập để chia sẻ bài viết.</small>
                                <button type="submit" class="btn btn-pink px-4">Đăng</button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-white post-card mb-3">
                            <div class="p-3 p-md-4">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex gap-3">
                                        <a href="<?= $loginUrl ?>">
                                            <img 
                                                src="<?= imagePath($post['ProfilePictureUrl'] ?? '') ?>" 
                                                class="avatar" 
                                                alt="avatar"
                                            >
                                        </a>

                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <a 
                                                    href="<?= $loginUrl ?>" 
                                                    class="fw-semibold text-decoration-none text-dark"
                                                >
                                                    <?= htmlspecialchars($post['FullName'] ?: $post['Username']) ?>
                                                </a>

                                                <span class="text-muted small">
                                                    <?= timeAgo($post['CreatedAt']) ?>
                                                </span>
                                            </div>

                                            <p class="post-text mb-2">
                                                <?= nl2br(htmlspecialchars($post['Content'])) ?>
                                            </p>

                                            <?php if (!empty($post['Images'])): ?>
                                                <?php $images = explode(',', $post['Images']); ?>

                                                <?php foreach ($images as $img): ?>
                                                    <a href="<?= $loginUrl ?>">
                                                        <img 
                                                            src="<?= imagePath(trim($img)) ?>" 
                                                            class="img-fluid rounded-4 mb-3"
                                                            style="max-height: 450px; object-fit: cover;"
                                                            alt="post image"
                                                        >
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="post-actions d-flex gap-4">
                                                <a href="<?= $loginUrl ?>" class="text-decoration-none">
                                                    <i class="bi bi-heart"></i> <?= $post['LikeCount'] ?? 0 ?>
                                                </a>

                                                <a href="<?= $loginUrl ?>" class="text-decoration-none">
                                                    <i class="bi bi-chat"></i> <?= $post['CommentCount'] ?? 0 ?>
                                                </a>

                                                <a href="<?= $loginUrl ?>" class="text-decoration-none">
                                                    <i class="bi bi-arrow-repeat"></i> 0
                                                </a>

                                                <a href="<?= $loginUrl ?>" class="text-decoration-none">
                                                    <i class="bi bi-send"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="<?= $loginUrl ?>" class="more-btn">
                                        <i class="bi bi-three-dots"></i>
                                    </a>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white post-card mb-3">
                        <div class="p-3 p-md-4 text-center text-muted">
                            Hiện chưa có bài viết nào trong database.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4 col-md-4 mt-4 mt-md-0">
                <div class="bg-light login-card p-4">
                    <h2 class="login-card-title text-center mb-3">
                        Đăng nhập hoặc đăng ký Archive
                    </h2>

                    <p class="text-center login-card-text mb-4">
                        Xem mọi người đang lưu lại điều gì và tham gia vào những cuộc trò chuyện nhỏ.
                    </p>

                    <a 
                        href="<?= $loginUrl ?>" 
                        class="username-login-btn w-100 text-center text-decoration-none d-block"
                    >
                        Đăng nhập bằng tên người dùng
                    </a>
                </div>

                <div class="feed-footer text-center mt-4">
                    <small>
                        © 2026 Archive · 
                        <a href="<?= $loginUrl ?>" class="text-decoration-none text-muted">Điều khoản</a> · 
                        <a href="<?= $loginUrl ?>" class="text-decoration-none text-muted">Chính sách riêng tư</a> · 
                        <a href="<?= $loginUrl ?>" class="text-decoration-none text-muted">Chính sách cookie</a>
                    </small>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    function scrollToFeed() {
        document.getElementById("feed-section").scrollIntoView({
            behavior: "smooth"
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>