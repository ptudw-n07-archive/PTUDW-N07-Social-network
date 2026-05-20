<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "App/Views/auth/login.php");
    exit();
}

$currentUsername = $_SESSION['username'] ?? '';
$currentFullName = $_SESSION['user_name'] ?? $currentUsername;
$currentAvatar   = $_SESSION['ProfilePictureUrl'] ?? '';

function imagePath($path) {
    $path = trim((string) $path);

    if ($path === '') {
        return BASE_URL . "Public/assets/img/default-avatar.jpg";
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $path = ltrim($path, "/");

    if (str_starts_with($path, "Public/")) {
        return BASE_URL . $path;
    }

    if (str_starts_with($path, "uploads/") || str_starts_with($path, "assets/")) {
        return BASE_URL . "Public/" . $path;
    }

    return BASE_URL . $path;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Đăng bài</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body>

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="brand-logo text-decoration-none">
                    ARCHIVE
                </a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="header-search-btn" title="Về bảng tin">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="header-login-btn">
                        <i class="bi bi-person-circle"></i>
                        <span>Hồ sơ</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="feed-section py-5">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4">

            <div class="col-lg-1 d-none d-lg-block">
                <aside class="left-sidebar d-flex flex-column align-items-center gap-4">

                    <div class="sidebar-logo">
                        <i class="bi bi-circle-square"></i>
                    </div>

                    <a 
                        href="<?php echo BASE_URL; ?>App/Views/feed.php"
                        class="sidebar-icon"
                        title="Trang chủ"
                    >
                        <i class="bi bi-house-door-fill"></i>
                    </a>

                    <a 
                        href="<?php echo BASE_URL; ?>App/Views/search.php"
                        class="sidebar-icon"
                        title="Tìm kiếm"
                    >
                        <i class="bi bi-search"></i>
                    </a>

                    <a 
                        href="<?php echo BASE_URL; ?>App/Views/createpost.php"
                        class="sidebar-icon active"
                        title="Đăng bài"
                    >
                        <i class="bi bi-plus-square"></i>
                    </a>

                    <a 
                        href="#"
                        class="sidebar-icon"
                        title="Thông báo"
                    >
                        <i class="bi bi-heart"></i>
                    </a>

                    <a 
                        href="<?php echo BASE_URL; ?>App/Views/profile.php"
                        class="sidebar-icon"
                        title="Hồ sơ"
                    >
                        <i class="bi bi-person"></i>
                    </a>

                    <div class="more-menu-wrapper">
                        <button type="button" class="more-button" id="moreButton" aria-expanded="false" aria-controls="moreDropdown">
                            <i class="bi bi-list more-icon"></i>
                            <span>More</span>
                        </button>

                        <div class="more-dropdown" id="moreDropdown">
                            <button type="button" class="more-dropdown-item">Appearance</button>
                            <button type="button" class="more-dropdown-item">Settings</button>
                            <hr>
                            <button type="button" class="more-dropdown-item">Liked</button>
                            <button type="button" class="more-dropdown-item">Archive</button>
                            <hr>
                            <button type="button" class="more-dropdown-item">Report a problem</button>
                            <a href="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout" class="more-dropdown-item logout-item">Log out</a>
                        </div>
                    </div>

                </aside>
            </div>

            <div class="col-lg-7 col-md-8 mx-auto">
                <div class="feed-title text-center mb-4">Tạo bài viết</div>

                <div class="bg-white p-3 p-md-4 mb-4 post-composer">
                    <div class="d-flex gap-3 align-items-center mb-4">
                        <img
                            src="<?= htmlspecialchars(imagePath($currentAvatar), ENT_QUOTES, 'UTF-8') ?>"
                            class="avatar"
                            alt="avatar"
                            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                        >

                        <div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($currentFullName) ?>
                            </div>
                            <small class="text-muted">
                                @<?= htmlspecialchars($currentUsername) ?>
                            </small>
                        </div>
                    </div>

                    <form id="postForm" method="POST" enctype="multipart/form-data">
                        <textarea 
                            name="content"
                            class="form-control composer-input mb-3" 
                            rows="7"
                            placeholder="Viết vài dòng cho hôm nay..."
                        ></textarea>

                        <label for="postImages" class="custom-upload-btn mb-3">
                            <i class="bi bi-plus-square"></i>
                            <span>+ Thêm ảnh/video</span>
                        </label>

                        <input 
                            type="file" 
                            name="images[]" 
                            id="postImages"
                            accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.heif,.mp4,.mov,.webm,image/*,video/*,image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/quicktime,video/webm"
                            multiple
                            hidden
                        >

                        <div id="preview-container" class="preview-container mt-2 mb-4"></div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a 
                                href="<?php echo BASE_URL; ?>App/Views/feed.php" 
                                class="btn btn-light px-4"
                            >
                                Hủy
                            </a>

                            <button 
                                type="button" 
                                class="btn btn-pink px-4"
                                onclick="createPost()"
                            >
                                Đăng bài
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white p-4 post-card">
                    <h6 class="fw-semibold mb-2">
                        <i class="bi bi-lightbulb"></i>
                        Gợi ý
                    </h6>
                    <p class="text-muted mb-0">
                        Bạn có thể viết một suy nghĩ ngắn, chia sẻ cảm xúc hôm nay hoặc đăng kèm ảnh/video.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

<script src="<?php echo BASE_URL; ?>Public/assets/JS/create-post.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
