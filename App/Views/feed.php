<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';

// 2. CHẶN LỖI: Nếu chưa đăng nhập, bắt buộc đá về trang login ngay lập tức
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

// 3. Đọc dữ liệu an toàn từ Session sau khi đã chắc chắn user đã đăng nhập
$currentUserId   = $_SESSION['user_id'];
$currentUsername = $_SESSION['username'];
$currentFullName = $_SESSION['user_name'];
$currentAvatar   = $_SESSION['ProfilePictureUrl'] ?? ''; 

require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/FollowController.php';
require_once __DIR__ . '/../Controllers/NotificationController.php';

/** @var \App\Controllers\PostController $postController */
$postController = new \App\Controllers\PostController();
$posts = $postController->index();
$trendingHashtags = $postController->getTrendingHashtags(10);

/** @var \App\Controllers\FollowController $followController */
$followController = new \App\Controllers\FollowController();
$suggestedUsers = $followController->getSuggestedUsers($currentUserId);

$notificationController = new \App\Controllers\NotificationController();
$unreadNotificationCount = $notificationController->countUnreadForCurrentUser();

function assetPath($path, $default = '') {
    $path = trim((string) $path);

    if (empty($path)) {
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

function imagePath($path) {
    return assetPath($path, BASE_URL . "Public/assets/img/default-avatar.jpg");
}

function postMediaPath($path) {
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

    $src = assetPath($cleanPath);
    if ($src === '') {
        return '';
    }

    if (str_starts_with($cleanPath, "Public/")) {
        $localPath = __DIR__ . '/../../' . $cleanPath;
        return is_file($localPath) ? $src : '';
    }

    if (str_starts_with($cleanPath, "uploads/") || str_starts_with($cleanPath, "assets/")) {
        $localPath = __DIR__ . '/../../Public/' . $cleanPath;
        return is_file($localPath) ? $src : '';
    }

    $localPath = __DIR__ . '/../../' . $cleanPath;
    return is_file($localPath) ? $src : '';
}

function postMediaType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    if (in_array($extension, ['mp4', 'mov', 'webm'], true)) {
        return 'video';
    }

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return 'image';
    }

    if (in_array($extension, ['heic', 'heif'], true)) {
        return 'unsupported-image';
    }

    return 'file';
}

function postMediaMimeType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    return match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        default => 'application/octet-stream'
    };
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút";
    if ($diff < 86400) return floor($diff / 3600) . " giờ";

    return date("d/m/Y", $timestamp);
}

function profileUrl($userId) {
    return BASE_URL . "App/Views/profile.php?id=" . urlencode((string) $userId);
}

function hashtagUrl($tag) {
    return BASE_URL . "App/Views/hashtag.php?tag=" . urlencode((string) $tag);
}

function renderPostContentWithHashtags($content) {
    $parts = preg_split('/(#[\p{L}\p{N}_]+)/u', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';

    foreach ($parts as $part) {
        if (preg_match('/^#([\p{L}\p{N}_]+)$/u', $part, $matches)) {
            $tag = $matches[1];
            $html .= '<a class="hashtag-link" href="' . htmlspecialchars(hashtagUrl($tag), ENT_QUOTES, 'UTF-8') . '">#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
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
    <title>Archive - Feed</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body class="feed-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <div class="brand-logo">ARCHIVE</div>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/search.php" class="header-search-btn"><i class="bi bi-search"></i></a>
                    <a href="#" class="header-star-btn"><i class="bi bi-star"></i></a>
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
                <?php
                    $activePage = 'home';
                    include __DIR__ . '/partials/sidebar.php';
                ?>
            </div>
            <div class="col-lg-7 col-md-8">
                <div class="feed-title text-center mb-4">Bảng tin</div>

                <form id="postForm" class="bg-white p-3 p-md-4 mb-4 post-composer" method="POST" enctype="multipart/form-data">
                    <div class="d-flex gap-3 composer-layout">
                       <img
                            src="<?= htmlspecialchars(imagePath($currentAvatar), ENT_QUOTES, 'UTF-8') ?>"
                            class="avatar composer-avatar"
                            alt="avatar"
                            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                        >
                        <div class="flex-grow-1">
                            <textarea 
                                name="content"
                                class="form-control composer-input" 
                                rows="3"
                                placeholder="Bạn đang nghĩ gì?"
                            ></textarea>
                            <div
                                id="hashtagSuggestionBox"
                                class="hashtag-suggestion-box"
                                data-endpoint="<?php echo BASE_URL; ?>App/Controllers/SearchController.php?action=suggestHashtags"
                                hidden
                            ></div>

                            <div class="composer-actions mt-3">
                                <label for="postImages" class="custom-upload-btn">
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

                                <button type="button" class="btn btn-pink px-4 composer-submit-btn" onclick="createPost()">Đăng</button>
                            </div>

                            <div id="preview-container" class="preview-container mt-3"></div>
                        </div>
                    </div>
                </form>

                <div id="posts-list">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <?php $comments = $postController->getComments($post['PostID']); ?>
                            <div class="bg-white post-card mb-3" id="post-<?= (int) $post['PostID'] ?>">
                                <div class="p-3">
                                    <div class="d-flex gap-3">
                                        <a href="<?= profileUrl($post['UserID']) ?>">
                                            <img 
                                            src="<?= htmlspecialchars(imagePath($post['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                                class="avatar post-avatar"
                                                alt="avatar"
                                                onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                                            >
                                        </a>

                                        <div class="flex-grow-1">
                                            <div class="post-meta-line">
                                                <a href="<?= profileUrl($post['UserID']) ?>" class="post-author-link text-decoration-none">
                                                    <?= htmlspecialchars($post['FullName'] ?: $post['Username']) ?>
                                                </a>
                                                <span class="post-time">• <?= timeAgo($post['CreatedAt']) ?></span>
                                            </div>

                                            <p class="post-text">
                                                <?= renderPostContentWithHashtags($post['Content']) ?>
                                            </p>

                                            <?php if (!empty($post['Images'])): ?>
                                                <?php $images = explode(',', $post['Images']); ?>
                                                <?php foreach ($images as $img): ?>
                                                    <?php $postMediaSrc = postMediaPath($img); ?>
                                                    <?php if ($postMediaSrc !== ''): ?>
                                                        <?php $postMediaType = postMediaType($img); ?>
                                                        <?php if ($postMediaType === 'video'): ?>
                                                            <video
                                                                controls
                                                                class="img-fluid rounded-4 mb-3"
                                                                style="max-height: 450px; object-fit: cover;"
                                                            >
                                                                <source src="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars(postMediaMimeType($img), ENT_QUOTES, 'UTF-8') ?>">
                                                                Trình duyệt không hỗ trợ video này.
                                                            </video>
                                                            <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small d-block mb-3">Mở file video</a>
                                                        <?php elseif ($postMediaType === 'image'): ?>
                                                            <img 
                                                                src="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" 
                                                                class="img-fluid rounded-4 mb-3"
                                                                style="max-height: 450px; object-fit: cover;"
                                                                alt="post image"
                                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                            >
                                                            <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small mb-3" style="display:none;">Mở file ảnh</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small d-block mb-3">Mở file ảnh</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="post-actions d-flex gap-4">
                                                <button onclick="toggleLike(this)" data-post-id="<?= $post['PostID'] ?>">
                                                    <i class="bi bi-heart"></i>
                                                    <span class="like-count"><?= $post['LikeCount'] ?? 0 ?></span>
                                                </button>

                                                 <button onclick="toggleCommentBox(this)">
                                                    <i class="bi bi-chat"></i>
                                                    <span class="comment-count">
                                                        <?= $post['CommentCount'] ?? 0 ?>
                                                    </span>
                                                </button>

                                                <button>
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </div>
                                            <div class="comment-box mt-3 d-none">
                                            <div class="d-flex gap-2">
                                                <input 
                                                    type="text" 
                                                    class="form-control comment-input" 
                                                    placeholder="Viết bình luận..."
                                                >

                                                <button 
                                                type="button"
                                                    class="btn btn-pink"
                                                    onclick="sendComment(this)"
                                                    data-post-id="<?= $post['PostID'] ?>"
                                                >
                                                    Gửi
                                                </button>
                                            </div>

                                                <div class="comment-list mt-2">
    <?php if (!empty($comments)): ?>
        <?php foreach ($comments as $comment): ?>
            <div class="small mt-2">
                <strong>
                    <?= htmlspecialchars($comment['FullName'] ?: $comment['Username']) ?>
                </strong>:
                <?= htmlspecialchars($comment['Content']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white post-card mb-3">
                            <div class="p-3 text-center text-muted">
                                Hiện chưa có bài viết nào trong database.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 col-md-4">
                <div class="bg-light login-card p-4 mb-4">
                    <h2 class="login-card-title text-center mb-3">Gợi ý theo dõi</h2>

                    <div class="d-flex flex-column gap-3">
                        <?php if (!empty($suggestedUsers)): ?>
                            <?php foreach ($suggestedUsers as $user): ?>
                                <div class="d-flex align-items-center justify-content-between follower-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <a
                                            href="<?= htmlspecialchars(profileUrl($user['UserID']), ENT_QUOTES, 'UTF-8') ?>"
                                            class="suggested-user-avatar-link"
                                            title="Xem hồ sơ <?= htmlspecialchars($user['FullName'] ?: $user['Username'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <img
                                                src="<?= htmlspecialchars(imagePath($user['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                class="avatar suggested-avatar"
                                                alt="avatar"
                                                onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                                            >
                                        </a>

                                        <div>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($user['FullName'] ?: $user['Username']) ?>
                                            </div>

                                            <small class="text-muted">
                                                @<?= htmlspecialchars($user['Username']) ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="suggested-user-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm <?= $user['IsFollowing'] ? 'btn-secondary' : 'btn-pink' ?>"
                                            onclick="toggleFollow(this)"
                                            data-user-id="<?= $user['UserID'] ?>"
                                        >
                                            <?= $user['IsFollowing'] ? 'Đang theo dõi' : 'Theo dõi' ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">
                                Chưa có người dùng nào để gợi ý.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-4 post-card trending-hashtags-card">
                    <h5>Chủ đề nổi bật</h5>

                    <?php if (!empty($trendingHashtags)): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($trendingHashtags as $hashtag): ?>
                                <?php
                                    $tagName = $hashtag['HashtagName'] ?? '';
                                    $totalPosts = (int) ($hashtag['TotalPosts'] ?? 0);
                                ?>
                                <a href="<?= htmlspecialchars(hashtagUrl($tagName), ENT_QUOTES, 'UTF-8') ?>" class="trending-hashtag-item">
                                    <span>#<?= htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <small><?= $totalPosts ?> bài viết</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Chưa có chủ đề nổi bật.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260520-media-fix"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
