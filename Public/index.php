<?php
require_once __DIR__ . '/../Controllers/PostController.php';

<<<<<<< Updated upstream
$postController = new PostController();
$posts = $postController->index();

$totalPosts = count($posts);
$totalUsers = count(array_unique(array_column($posts, 'UserID')));
$totalComments = array_sum(array_column($posts, 'CommentCount'));

if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}


function imagePath($path) {
    if (empty($path)) {
        return "assets/img/default-avatar.jpg";
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    return str_replace("Public/", "", $path);
=======
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $extension = strtolower(pathinfo($requestPath ?? '', PATHINFO_EXTENSION));

    $staticExtensions = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
        'ico', 'woff', 'woff2', 'ttf', 'map'
    ];

    if (
        is_string($requestPath)
        && $requestPath !== '/'
        && in_array($extension, $staticExtensions, true)
    ) {
        $projectRoot = realpath(__DIR__ . '/..');
        $requestedFile = realpath(($projectRoot ?: (__DIR__ . '/..')) . $requestPath);

        if (
            $projectRoot !== false
            && $requestedFile !== false
            && strpos($requestPath, '..') === false
            && is_file($requestedFile)
        ) {
            return false;
        }
    }
}

// 1. Khởi động Session hệ thống nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../Config/Database.php';
} catch (Throwable $e) {
    error_log('Public index require Database error: ' . $e->getMessage());
}

if (!function_exists('app_url')) {
    function app_url($path = '') {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $base = $base === '/Public' ? dirname($base) : $base;
        $base = $base === '/' || $base === '\\' ? '' : $base;
        return $base . '/' . ltrim((string) $path, '/');
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', app_url('Public/index.php'));
}

// 2. Nhúng file PostController nằm TRONG folder App (Thêm /../App/ vào đường dẫn)
try {
    require_once __DIR__ . '/../App/Controllers/PostController.php';
} catch (Throwable $e) {
    error_log('Public index require PostController error: ' . $e->getMessage());
}

// ✨ KHAI BÁO SỬ DỤNG CLASS CÓ NAMESPACE ĐỂ XÓA VỆT VÀNG CỦA VS CODE
use App\Controllers\PostController;

// Chỉnh lại link dẫn vào Views vì nó đã chui vào trong App/Views/
$loginUrl    = app_url('App/Views/auth/login.php');
$registerUrl = app_url('App/Views/auth/register.php');
$homeUrl     = BASE_URL;

// 4. Khởi tạo đối tượng Controller
$result = [];

if (class_exists(PostController::class) && homepageCanConnectDatabase()) {
    try {
        $postController = new PostController();

        // 5. Chạy hàm lấy dữ liệu để đổ ra giao diện Archive
        $result = $postController->index();
    } catch (Throwable $e) {
        $result = [];
        error_log('Public index error: ' . $e->getMessage());
    }
}

if (!is_array($result)) {
    $result = [];
}

if (isset($result['posts'])) {
    $posts         = is_array($result['posts']) ? $result['posts'] : [];
    $totalPosts    = $result['totalPosts'] ?? count($posts);
    $totalUsers    = $result['totalUsers'] ?? count(array_unique(array_column($posts, 'UserID')));
    $totalComments = $result['totalComments'] ?? array_sum(array_column($posts, 'CommentCount'));
} else {
    $posts         = $result;
    $totalPosts    = count($posts);
    $totalUsers    = count(array_unique(array_column($posts, 'UserID')));
    $totalComments = array_sum(array_column($posts, 'CommentCount'));
}

// 6. CÁC HÀM HELPER ĐỊNH DẠNG GIAO DIỆN 
function homepageStartsWith($haystack, $needle) {
    return $needle === '' || strpos((string) $haystack, (string) $needle) === 0;
}

function homepageCanConnectDatabase() {
    if (!class_exists('PDO')) {
        error_log('Public index database preflight skipped: PDO is unavailable.');
        return false;
    }

    $host = function_exists('app_env') ? (app_env('DB_HOST', '100.76.147.122') ?? '100.76.147.122') : '100.76.147.122';
    $dbName = function_exists('app_env') ? (app_env('DB_NAME', 'db_archive') ?? 'db_archive') : 'db_archive';
    $username = function_exists('app_env') ? (app_env('DB_USER', 'root') ?? 'root') : 'root';
    $password = function_exists('app_env') ? (app_env('DB_PASSWORD', '') ?? '') : '';
    $port = function_exists('app_env') ? app_env('DB_PORT') : null;

    $dsn = 'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=utf8mb4';
    if ($port !== null && $port !== '') {
        $dsn .= ';port=' . $port;
    }

    try {
        new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (Throwable $e) {
        error_log('Public index database preflight error: ' . $e->getMessage());
        return false;
    }

    return true;
}

function homepageImagePath($path) {
    $default = app_url('Public/assets/img/default-avatar.jpg');

    if (empty($path)) {
        return $default;
    }

    $path = trim((string) $path);

    if ($path === '') {
        return $default;
    }

    if (homepageStartsWith($path, "http://") || homepageStartsWith($path, "https://")) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    if (homepageStartsWith($path, 'Public/')) {
        return app_url($path);
    }

    if (homepageStartsWith($path, 'assets/')) {
        return app_url('Public/' . $path);
    }

    if (homepageStartsWith($path, 'uploads/')) {
        return app_url('Public/' . $path);
    }

    return app_url('Public/assets/img/posts/' . basename($path));
>>>>>>> Stashed changes
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return "vừa xong";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " phút";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " giờ";
    } else {
        return date("d/m/Y", $timestamp);
    }
}

function formatNumber($number) {
    if ($number >= 1000) {
        return number_format($number, 0, ',', '.');
    }

    return $number;
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

    <link rel="stylesheet" href="<?= app_url('Public/assets/CSS/style.css') ?>">
</head>

<body>
<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">

            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>Public/index.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center align-items-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end align-items-center">
                <div class="header-actions">
                    <a href="#" class="header-search-btn" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="#" class="header-star-btn" title="About us">
                        <i class="bi bi-star"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>Views/auth/login.php" class="header-login-btn" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                        <span>Đăng nhập</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>Views/auth/register.php" class="header-register-btn" title="Đăng ký">
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
                            <a href="<?php echo BASE_URL; ?>Views/auth/login.php" class="btn hero-outline-btn">Đăng nhập</a>
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

                    <a href="<?php echo BASE_URL; ?>Public/index.php" class="sidebar-icon active" title="Trang chủ">
                        <i class="bi bi-house-door-fill"></i>
                    </a>

                    <a href="#" class="sidebar-icon" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="#" class="sidebar-icon" title="Tạo bài viết">
                        <i class="bi bi-plus-square"></i>
                    </a>

                    <a href="#" class="sidebar-icon" title="Thông báo">
                        <i class="bi bi-heart"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>Views/profile.php" class="sidebar-icon" title="Trang cá nhân">
                        <i class="bi bi-person"></i>
                    </a>

                    <a href="#" class="sidebar-icon mt-auto" title="About us">
                        <i class="bi bi-pin-angle"></i>
                    </a>
                </aside>
            </div>

            <div class="col-lg-7 col-md-8">
                <div class="feed-title text-center mb-4">Trang chủ</div>

                <form action="#" method="POST" class="bg-white p-3 p-md-4 mb-4 post-composer">
                    <div class="d-flex gap-3 align-items-start">
                        <img src="<?= app_url('Public/assets/img/default-avatar.jpg') ?>" class="avatar" alt="avatar">

                        <div class="flex-grow-1">
                            <h6 class="mb-2 fw-semibold">Bạn đang nghĩ gì?</h6>

                            <textarea 
                                name="content"
                                class="form-control composer-input" 
                                rows="3" 
                                placeholder="Viết vài dòng cho hôm nay..."
                            ></textarea>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">Chia sẻ một điều ngắn thôi cũng được.</small>
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
                                        <a href="<?php echo BASE_URL; ?>Views/profile.php?id=<?= $post['UserID'] ?>">
                                            <img 
                                                src="<?= imagePath($post['ProfilePictureUrl'] ?? '') ?>" 
                                                class="avatar" 
                                                alt="avatar"
                                            >
                                        </a>

                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <a 
                                                    href="<?php echo BASE_URL; ?>Views/profile.php?id=<?= $post['UserID'] ?>" 
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
                                                    <img 
                                                        src="<?= imagePath(trim($img)) ?>" 
                                                        class="img-fluid rounded-4 mb-3"
                                                        style="max-height: 450px; object-fit: cover;"
                                                        alt="post image"
                                                    >
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="post-actions d-flex gap-4">
                                                <button type="button">
                                                    <i class="bi bi-heart"></i> <?= $post['LikeCount'] ?? 0 ?>
                                                </button>

                                                <button type="button">
                                                    <i class="bi bi-chat"></i> <?= $post['CommentCount'] ?? 0 ?>
                                                </button>

                                                <button type="button">
                                                    <i class="bi bi-arrow-repeat"></i> 0
                                                </button>

                                                <button type="button">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="more-btn">
                                        <i class="bi bi-three-dots"></i>
                                    </button>

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
                        href="<?php echo BASE_URL; ?>Views/auth/login.php" 
                        class="username-login-btn w-100 text-center text-decoration-none d-block"
                    >
                        Đăng nhập bằng tên người dùng
                    </a>
                </div>
<<<<<<< Updated upstream

                <div class="feed-footer text-center mt-4">
                    <small>
                        © 2026 Archive · 
                        <a href="#" class="text-decoration-none text-muted">Điều khoản</a> · 
                        <a href="#" class="text-decoration-none text-muted">Chính sách riêng tư</a> · 
                        <a href="#" class="text-decoration-none text-muted">Chính sách cookie</a>
                    </small>
                </div>
=======
>>>>>>> Stashed changes
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