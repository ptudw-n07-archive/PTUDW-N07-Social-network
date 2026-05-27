<?php

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (is_string($requestPath) && $requestPath !== '/') {
        $projectRoot = realpath(__DIR__ . '/..');
        $currentFile = realpath(__FILE__);
        $requestedFile = realpath(($projectRoot ?: (__DIR__ . '/..')) . $requestPath);

        if (
            $projectRoot !== false
            && $currentFile !== false
            && $requestedFile !== false
            && !str_contains($requestPath, '..')
            && $requestedFile !== $currentFile
            && is_file($requestedFile)
        ) {
            return false;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../Config/Database.php';
} catch (Throwable $e) {
    error_log('[PublicIndex] Could not load app config: ' . $e->getMessage());
}

if (!function_exists('homepageUrl')) {
    function homepageUrl(string $path = ''): string {
        if (function_exists('app_url')) {
            return app_url($path);
        }

        return '/' . ltrim($path, '/');
    }
}

$loginUrl    = homepageUrl('App/Views/auth/login.php');
$registerUrl = homepageUrl('App/Views/auth/register.php');
$homeUrl     = homepageUrl('Public/index.php');

$projectStats = [
    ['value' => 'MVC', 'label' => 'kiến trúc'],
    ['value' => '5+', 'label' => 'tính năng'],
    ['value' => 'Nhóm 7', 'label' => 'thực hiện']
];
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
                <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center align-items-center">
                <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-badge text-decoration-none">
                    <i class="bi bi-stars"></i>
                </a>
            </div>

            <div class="col-4 d-flex justify-content-end align-items-center">
                <div class="header-actions">
                    <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-search-btn" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="#project-section" class="header-star-btn" title="About us">
                        <i class="bi bi-star"></i>
                    </a>

                    <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-login-btn" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                        <span>Đăng nhập</span>
                    </a>

                    <a href="<?= htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-register-btn" title="Đăng ký">
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
                            <button class="btn hero-main-btn" type="button" onclick="scrollToProject()">Bắt đầu</button>
                            <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn hero-outline-btn">Đăng nhập</a>
                        </div>

                        <div class="hero-stats mx-auto">
                            <div class="row g-0">
                                <?php foreach ($projectStats as $stat): ?>
                                    <div class="col-4">
                                        <div class="stat-box">
                                            <h4><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></h4>
                                            <p><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="scroll-down mt-5" onclick="scrollToProject()">
                            <span>Xem đồ án</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<main id="project-section" class="feed-section py-5">
    <div class="container px-3 px-lg-4">
        <div class="text-center mb-5">
            <div class="hero-pill d-inline-flex align-items-center gap-2 mb-3">
                <span class="mini-star"><i class="bi bi-journal-code"></i></span>
                <span>ĐỒ ÁN MÔN PHÁT TRIỂN ỨNG DỤNG WEB</span>
            </div>
            <h2 class="feed-title mb-3">Archive - mạng xã hội mini</h2>
            <p class="hero-subtitle mx-auto mb-0">
                Archive giúp người dùng đăng tải trạng thái, chia sẻ khoảnh khắc, lưu giữ cảm xúc và kết nối với bạn bè
                trong một không gian nhẹ nhàng, tối giản.
            </p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="bg-white post-card h-100 p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="header-badge">
                            <i class="bi bi-bullseye"></i>
                        </span>
                        <h3 class="login-card-title mb-0">Mục tiêu đồ án</h3>
                    </div>
                    <p class="login-card-text mb-0">
                        Xây dựng prototype mạng xã hội theo mô hình MVC, có các chức năng đăng nhập, đăng ký, đăng bài,
                        xem feed, like, comment, follow và quản lý hồ sơ cá nhân.
                    </p>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="bg-white post-card h-100 p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="header-badge">
                            <i class="bi bi-code-slash"></i>
                        </span>
                        <h3 class="login-card-title mb-0">Công nghệ sử dụng</h3>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill text-bg-light px-3 py-2">PHP</span>
                        <span class="badge rounded-pill text-bg-light px-3 py-2">MySQL</span>
                        <span class="badge rounded-pill text-bg-light px-3 py-2">HTML</span>
                        <span class="badge rounded-pill text-bg-light px-3 py-2">CSS</span>
                        <span class="badge rounded-pill text-bg-light px-3 py-2">JavaScript</span>
                        <span class="badge rounded-pill text-bg-light px-3 py-2">Bootstrap 5</span>
                    </div>
                </div>
            </div>
        </div>

        <section class="py-2 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="header-badge">
                    <i class="bi bi-stars"></i>
                </span>
                <h3 class="login-card-title mb-0">Tính năng nổi bật</h3>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-pencil-square fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Đăng bài</h4>
                        <p class="text-muted mb-0">Chia sẻ trạng thái ngắn gọn và những suy nghĩ trong ngày.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-image fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Upload ảnh</h4>
                        <p class="text-muted mb-0">Lưu lại khoảnh khắc bằng hình ảnh và media gắn với bài viết.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-heart fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Like và comment</h4>
                        <p class="text-muted mb-0">Tương tác với bài viết qua thích và bình luận.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-person-plus fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Follow người dùng</h4>
                        <p class="text-muted mb-0">Kết nối bạn bè và theo dõi những tài khoản quan tâm.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-person-circle fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Hồ sơ cá nhân</h4>
                        <p class="text-muted mb-0">Quản lý thông tin, avatar và các bài viết của riêng mình.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="bg-light login-card h-100 p-4">
                        <i class="bi bi-layout-text-window fs-3 mb-3 d-inline-block"></i>
                        <h4 class="h6 fw-semibold">Giao diện feed</h4>
                        <p class="text-muted mb-0">Trải nghiệm bảng tin gọn gàng, dễ đọc và thân thiện.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <div class="bg-white post-card h-100 p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="header-badge">
                            <i class="bi bi-people"></i>
                        </span>
                        <h3 class="login-card-title mb-0">Thông tin nhóm</h3>
                    </div>
                    <p class="login-card-text mb-4">
                        Đồ án môn Phát triển ứng dụng Web - Nhóm 7.
                    </p>
                    <div class="row g-3">
                        <?php foreach (['Nguyễn Du Mỹ Kỳ', 'Nguyễn Gia Hân', 'Trần Hồng Mai', 'Trịnh Nguyễn Thanh Tuyền'] as $member): ?>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-3 bg-light rounded-4 p-3">
                                    <span class="mini-star"><i class="bi bi-person"></i></span>
                                    <span class="fw-semibold"><?= htmlspecialchars($member, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-light login-card h-100 p-4 p-md-5 d-flex flex-column justify-content-center text-center">
                    <h3 class="login-card-title mb-3">Sẵn sàng trải nghiệm Archive?</h3>
                    <p class="login-card-text mb-4">
                        Đăng nhập hoặc tạo tài khoản để vào feed đầy đủ và thử các chức năng của prototype.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn hero-main-btn">Đăng nhập</a>
                        <a href="<?= htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn hero-outline-btn">Đăng ký</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="feed-footer text-center mt-5">
            <small>
                &copy; 2026 Archive &middot;
                <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">Điều khoản</a> &middot;
                <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">Chính sách riêng tư</a>
            </small>
        </div>
    </div>
</main>

<script>
    function scrollToProject() {
        document.getElementById("project-section").scrollIntoView({
            behavior: "smooth"
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
