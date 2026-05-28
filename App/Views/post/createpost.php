<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/PostController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

$currentUsername = $_SESSION['username'] ?? '';
$currentFullName = !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : '@' . $currentUsername;
$postController = new \App\Controllers\PostController();
$currentUser = $postController->getCurrentUser((int) $_SESSION['user_id']);
$currentUsername = $currentUser['Username'] ?? $currentUsername;
$currentFullName = !empty($currentUser['FullName'])
    ? $currentUser['FullName']
    : (!empty($currentFullName) ? $currentFullName : '@' . $currentUsername);
$currentAvatar = $currentUser['ProfilePictureUrl']
    ?? $_SESSION['ProfilePictureUrl']
    ?? $_SESSION['avatar']
    ?? $_SESSION['user_avatar']
    ?? '';

if ($currentAvatar !== '') {
    $_SESSION['ProfilePictureUrl'] = $currentAvatar;
}

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

    if (!str_contains($path, "/")) {
        return BASE_URL . "Public/uploads/avatars/" . basename($path);
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

    <?php include __DIR__ . '/../partials/fonts.php'; ?>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body>

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="brand-logo text-decoration-none">
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
                    <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="header-search-btn" title="Về bảng tin">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile/profile.php" class="header-login-btn">
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
                <?php $activePage = 'create'; include __DIR__ . '/partials/sidebar.php'; ?>
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
                        <?= \App\Services\CsrfService::hiddenField() ?>
                        <label for="composerEditor" class="visually-hidden">Nội dung bài viết</label>
                        <textarea name="content" id="composerTextarea" hidden></textarea>
                        <div
                            id="composerEditor"
                            class="form-control composer-input post-content-editor mb-3"
                            contenteditable="true"
                            role="textbox"
                            aria-multiline="true"
                            data-content-target="composerTextarea"
                            data-placeholder="Viết vài dòng cho hôm nay..."
                        ></div>

                        <label for="postImages" class="custom-upload-btn mb-3">
                            <i class="bi bi-image"></i>
                            <span>Thêm ảnh</span>
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
                                href="<?php echo BASE_URL; ?>App/Views/post/feed.php" 
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

<script>
    window.APP_BASE_URL = "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/hashtag-suggestions.js?v=20260528-contenteditable"></script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/create-post.js?v=20260528-contenteditable"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/partials/bottom-nav.php'; ?>
</body>
</html>
