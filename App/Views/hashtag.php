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

require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/partials/post-menu.php';

use App\Controllers\PostController;

$rawTag = trim((string) ($_GET['tag'] ?? ''));
$tag = preg_replace('/[^\p{L}\p{N}_]/u', '', ltrim($rawTag, '#')) ?? '';

if (function_exists('mb_substr')) {
    $tag = mb_substr($tag, 0, 80);
} else {
    $tag = substr($tag, 0, 80);
}

$postController = new PostController();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$posts = $tag === '' ? [] : $postController->getPostsByHashtag($tag);

function hashtagAssetPath($path, $default = '') {
    $path = trim((string) $path);

    if ($path === '') {
        return $default;
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

function hashtagImagePath($path) {
    return hashtagAssetPath($path, BASE_URL . "Public/assets/img/default-avatar.jpg");
}

function hashtagPostMediaPath($path) {
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $cleanPath = ltrim($path, "/");
    $extension = strtolower(pathinfo(parse_url($cleanPath, PHP_URL_PATH) ?: $cleanPath, PATHINFO_EXTENSION));

    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'mp4', 'mov', 'webm'], true)) {
        return '';
    }

    return hashtagAssetPath($cleanPath);
}

function hashtagPostMediaType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    if (in_array($extension, ['mp4', 'mov', 'webm'], true)) {
        return 'video';
    }

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return 'image';
    }

    return 'file';
}

function hashtagPostMediaMimeType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    return match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        default => 'application/octet-stream'
    };
}

function hashtagTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút";
    if ($diff < 86400) return floor($diff / 3600) . " giờ";

    return date("d/m/Y", $timestamp);
}

function hashtagProfileUrl($userId) {
    return BASE_URL . "App/Views/profile.php?id=" . urlencode((string) $userId);
}

function hashtagUrl($tag) {
    return BASE_URL . "App/Views/hashtag.php?tag=" . urlencode((string) $tag);
}

function renderHashtagPostContent($content) {
    $parts = preg_split('/(#[\p{L}\p{N}_]+)/u', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';

    foreach ($parts as $part) {
        if (preg_match('/^#([\p{L}\p{N}_]+)$/u', $part, $matches)) {
            $tagName = $matches[1];
            $html .= '<a class="hashtag-link" href="' . htmlspecialchars(hashtagUrl($tagName), ENT_QUOTES, 'UTF-8') . '">#' . htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8') . '</a>';
            continue;
        }

        $html .= nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8'));
    }

    return $html;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - #<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></title>

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
                <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="header-search-btn" title="Bảng tin">
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
                <?php $activePage = 'hashtag'; include __DIR__ . '/partials/sidebar.php'; ?>
            </div>

            <div class="col-lg-8 col-xl-7 mx-auto">
                <div class="hashtag-page-title text-center mb-4">
                    Bài viết với #<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <?php if ($tag === '' || empty($posts)): ?>
                    <div class="bg-white post-card p-4 text-center text-muted">
                        Chưa có bài viết nào với hashtag này.
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div
                            class="bg-white post-card mb-3 hashtag-post-card"
                            id="post-<?= (int) $post['PostID'] ?>"
                            role="link"
                            tabindex="0"
                            data-detail-url="<?= BASE_URL ?>App/Views/post-detail.php?id=<?= (int) $post['PostID'] ?>"
                            <?= archivePostCardAttributes($post, (int) $currentUserId) ?>
                        >
                            <div class="p-3">
                                <div class="d-flex gap-3">
                                    <a href="<?= hashtagProfileUrl($post['UserID']) ?>">
                                        <img
                                            src="<?= htmlspecialchars(hashtagImagePath($post['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            class="avatar"
                                            alt="avatar"
                                            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                                        >
                                    </a>

                                    <div class="flex-grow-1">
                                        <div class="post-card-header">
                                        <div class="fw-semibold">
                                            <a href="<?= hashtagProfileUrl($post['UserID']) ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($post['FullName'] ?: $post['Username'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            • <?= hashtagTimeAgo($post['CreatedAt']) ?>
                                        </div>

                                        <?php archiveRenderPostMenu($post, (int) $currentUserId); ?>
                                        </div>

                                        <p class="post-text">
                                            <?= renderHashtagPostContent($post['Content']) ?>
                                        </p>

                                        <?php if (!empty($post['Images'])): ?>
                                            <?php foreach (explode(',', $post['Images']) as $img): ?>
                                                <?php $mediaSrc = hashtagPostMediaPath($img); ?>
                                                <?php if ($mediaSrc !== ''): ?>
                                                    <?php if (hashtagPostMediaType($img) === 'video'): ?>
                                                        <video controls class="img-fluid rounded-4 mb-3" style="max-height: 450px; object-fit: cover;">
                                                            <source src="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars(hashtagPostMediaMimeType($img), ENT_QUOTES, 'UTF-8') ?>">
                                                        </video>
                                                    <?php elseif (hashtagPostMediaType($img) === 'image'): ?>
                                                        <img
                                                            src="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>"
                                                            class="img-fluid rounded-4 mb-3"
                                                            style="max-height: 450px; object-fit: cover;"
                                                            alt="post image"
                                                        >
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <div class="post-actions d-flex gap-4">
                                            <span>
                                                <i class="bi bi-heart"></i>
                                                <?= (int) ($post['LikeCount'] ?? 0) ?>
                                            </span>

                                            <span>
                                                <i class="bi bi-chat"></i>
                                                <?= (int) ($post['CommentCount'] ?? 0) ?>
                                            </span>

                                            <span>
                                                <i class="bi bi-arrow-repeat"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260522-post-menu"></script>
<script>
document.querySelectorAll(".hashtag-post-card").forEach(function (card) {
    function openPost(event) {
        if (event.target.closest("a, button, video")) {
            return;
        }

        const detailUrl = card.dataset.detailUrl;
        if (detailUrl) {
            window.location.href = detailUrl;
        }
    }

    card.addEventListener("click", openPost);
    card.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        event.preventDefault();
        openPost(event);
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
