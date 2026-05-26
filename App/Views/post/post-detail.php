<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

require_once __DIR__ . '/../../Controllers/PostController.php';
require_once __DIR__ . '/partials/post-menu.php';

$postId = (int) ($_GET['id'] ?? 0);
$highlightCommentId = (int) ($_GET['comment'] ?? 0);
$currentUserId = (int) $_SESSION['user_id'];

$postController = new \App\Controllers\PostController();
$post = $postId > 0 ? $postController->detail($postId, $currentUserId) : null;
$comments = $post ? $postController->getComments($postId) : [];

function detailAssetPath($path, $default = '') {
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

function detailImagePath($path) {
    return detailAssetPath($path, BASE_URL . "Public/assets/img/default-avatar.jpg");
}

function detailPostMediaPath($path) {
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

    if (str_starts_with($cleanPath, "Public/")) {
        $localPath = __DIR__ . '/../../../' . $cleanPath;
        return is_file($localPath) ? BASE_URL . $cleanPath : '';
    }

    if (str_starts_with($cleanPath, "uploads/") || str_starts_with($cleanPath, "assets/")) {
        $localPath = __DIR__ . '/../../../Public/' . $cleanPath;
        return is_file($localPath) ? BASE_URL . "Public/" . $cleanPath : '';
    }

    $localPath = __DIR__ . '/../../' . $cleanPath;
    return is_file($localPath) ? BASE_URL . $cleanPath : '';
}

function detailPostMediaType($path) {
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

function detailPostMediaMimeType($path) {
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

function detailTimeAgo($datetime) {
    if (empty($datetime)) {
        return '';
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút trước";
    if ($diff < 86400) return floor($diff / 3600) . " giờ trước";
    if ($diff < 604800) return floor($diff / 86400) . " ngày trước";

    return date("d/m/Y H:i", $timestamp);
}

function detailProfileUrl($userId) {
    return BASE_URL . "App/Views/profile/profile.php?id=" . urlencode((string) $userId);
}

function detailHashtagUrl($tag) {
    return BASE_URL . "App/Views/hashtags/hashtag.php?tag=" . urlencode((string) $tag);
}

function renderDetailPostContent($content) {
    $parts = preg_split('/(#[\p{L}\p{N}_]+)/u', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';

    foreach ($parts as $part) {
        if (preg_match('/^#([\p{L}\p{N}_]+)$/u', $part, $matches)) {
            $tag = $matches[1];
            $html .= '<a class="hashtag-link" href="' . htmlspecialchars(detailHashtagUrl($tag), ENT_QUOTES, 'UTF-8') . '">#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
            continue;
        }

        $html .= nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8'));
    }

    return $html;
}

function renderDetailCommentItem(array $comment, int $currentUserId, int $postOwnerId, bool $isReply = false, array $replies = []): void {
    global $highlightCommentId;
    $commentId = (int) ($comment['CommentID'] ?? 0);
    $commentOwnerId = (int) ($comment['UserID'] ?? 0);
    $parentCommentId = (int) ($comment['ParentCommentID'] ?? 0);
    $isOwnComment = $commentOwnerId === $currentUserId;
    $canDelete = $isOwnComment || $postOwnerId === $currentUserId;
    $canReport = !$isOwnComment;
    ?>
    <div
        class="comment-item post-detail-comment <?= $isReply ? 'comment-reply' : '' ?> <?= $commentId === (int) $highlightCommentId ? 'highlight' : '' ?>"
        id="comment-<?= $commentId ?>"
        data-comment-id="<?= $commentId ?>"
        data-comment-owner-id="<?= $commentOwnerId ?>"
        data-parent-comment-id="<?= $parentCommentId ?: '' ?>"
    >
        <img
            src="<?= htmlspecialchars(detailImagePath($comment['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            class="comment-avatar avatar"
            alt="avatar"
            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
        >
        <div class="comment-body">
            <div class="comment-bubble">
                <div class="comment-meta">
                    <strong class="comment-author">
                        <?= htmlspecialchars($comment['FullName'] ?: $comment['Username'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    <span class="comment-time">• <?= htmlspecialchars(detailTimeAgo($comment['CreatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="comment-content"><?= nl2br(htmlspecialchars($comment['Content'] ?? '', ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
            <div class="comment-actions">
                <?php if (!$isReply): ?>
                    <button type="button" class="comment-action-btn" onclick="showReplyForm(this)">Trả lời</button>
                <?php endif; ?>
                <?php if ($isOwnComment): ?>
                    <button type="button" class="comment-action-btn" onclick="showEditCommentForm(this)">Sửa</button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                    <button type="button" class="comment-action-btn text-danger" onclick="deleteComment(this)">Xóa</button>
                <?php endif; ?>
                <?php if ($canReport): ?>
                    <button type="button" class="comment-action-btn" onclick="showReportCommentForm(this)">Báo cáo</button>
                <?php endif; ?>
            </div>
            <?php if (!$isReply): ?>
                <div class="comment-children">
                    <?php foreach ($replies as $reply): ?>
                        <?php renderDetailCommentItem($reply, $currentUserId, $postOwnerId, true); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function renderDetailComments(array $comments, int $currentUserId, int $postOwnerId): void {
    $roots = [];
    $repliesByParent = [];

    foreach ($comments as $comment) {
        $parentId = (int) ($comment['ParentCommentID'] ?? 0);

        if ($parentId > 0) {
            $repliesByParent[$parentId][] = $comment;
            continue;
        }

        $roots[] = $comment;
    }

    foreach ($roots as $comment) {
        $commentId = (int) ($comment['CommentID'] ?? 0);
        renderDetailCommentItem($comment, $currentUserId, $postOwnerId, false, $repliesByParent[$commentId] ?? []);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Bài viết</title>

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
                <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-chat-square-text"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/search/search.php" class="header-search-btn"><i class="bi bi-search"></i></a>
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
                <?php $activePage = 'post'; include __DIR__ . '/partials/sidebar.php'; ?>
            </div>

            <div class="col-lg-7 col-md-10 mx-auto">
                <div class="post-detail-topbar">
                    <button type="button" class="post-detail-back" onclick="history.length > 1 ? history.back() : window.location.href='<?php echo BASE_URL; ?>App/Views/post/feed.php';" aria-label="Quay lại">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="feed-title mb-0">Bài viết</div>
                </div>

                <?php if (!$post): ?>
                    <div class="bg-white post-card p-4 text-center text-muted">
                        Không tìm thấy bài viết.
                    </div>
                <?php else: ?>
                    <article class="bg-white post-card post-detail-card mb-3" id="post-<?= (int) $post['PostID'] ?>" <?= archivePostCardAttributes($post, (int) $currentUserId) ?>>
                        <div class="p-3 p-md-4">
                            <div class="d-flex gap-3">
                                <a href="<?= detailProfileUrl($post['UserID']) ?>">
                                    <img
                                        src="<?= htmlspecialchars(detailImagePath($post['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="avatar"
                                        alt="avatar"
                                        onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                                    >
                                </a>

                                <div class="flex-grow-1">
                                    <div class="post-card-header">
                                    <div class="fw-semibold">
                                        <a href="<?= detailProfileUrl($post['UserID']) ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($post['FullName'] ?: $post['Username'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <span class="text-muted">• <?= htmlspecialchars(detailTimeAgo($post['CreatedAt']), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php archiveRenderPrivacyBadge($post['Privacy'] ?? 'public'); ?>
                                    </div>

                                    <?php archiveRenderPostMenu($post, (int) $currentUserId); ?>
                                    </div>

                                    <p class="post-text post-detail-text">
                                        <?= renderDetailPostContent($post['Content']) ?>
                                    </p>

                                    <?php if (!empty($post['Images'])): ?>
                                        <div class="post-detail-media">
                                            <?php foreach (explode(',', $post['Images']) as $img): ?>
                                                <?php $mediaSrc = detailPostMediaPath($img); ?>
                                                <?php if ($mediaSrc !== ''): ?>
                                                    <?php $mediaType = detailPostMediaType($img); ?>
                                                    <?php if ($mediaType === 'video'): ?>
                                                        <video controls class="img-fluid rounded-4 mb-3">
                                                            <source src="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars(detailPostMediaMimeType($img), ENT_QUOTES, 'UTF-8') ?>">
                                                            Trình duyệt không hỗ trợ video này.
                                                        </video>
                                                    <?php elseif ($mediaType === 'image'): ?>
                                                        <img
                                                            src="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>"
                                                            class="img-fluid rounded-4 mb-3"
                                                            style="max-height: 450px; object-fit: contain;"
                                                            alt="post image"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                        >
                                                        <a href="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small mb-3" style="display:none;">Mở file ảnh</a>
                                                    <?php else: ?>
                                                        <a href="<?= htmlspecialchars($mediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small d-block mb-3">Mở file ảnh</a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="post-actions d-flex gap-4">
                                        <button onclick="toggleLike(this)" data-post-id="<?= (int) $post['PostID'] ?>" class="<?= (int) ($post['IsLiked'] ?? 0) ? 'liked' : '' ?>" style="<?= (int) ($post['IsLiked'] ?? 0) ? 'color:red;' : '' ?>">
                                            <i class="bi <?= (int) ($post['IsLiked'] ?? 0) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            <span class="like-count"><?= (int) ($post['LikeCount'] ?? 0) ?></span>
                                        </button>

                                        <button type="button" onclick="toggleCommentBox(this)">
                                            <i class="bi bi-chat"></i>
                                            <span class="comment-count"><?= (int) ($post['CommentCount'] ?? 0) ?></span>
                                        </button>

                                        <button
                                            type="button"
                                            class="repost-btn"
                                            onclick="repostPost(this)"
                                            data-post-id="<?= (int) $post['PostID'] ?>"
                                        >
                                            <i class="bi bi-arrow-repeat"></i>
                                            <span class="visually-hidden">Đăng lại</span>
                                        </button>

                                    </div>

                                    <div class="comment-box mt-3">
                                        <div class="comment-form d-flex gap-2">
                                            <label for="detailCommentInput" class="visually-hidden">Viết bình luận</label>
                                            <input
                                                type="text"
                                                id="detailCommentInput"
                                                class="form-control comment-input"
                                                placeholder="Viết bình luận..."
                                            >

                                            <button
                                                type="button"
                                                class="btn btn-pink"
                                                onclick="sendComment(this)"
                                                data-post-id="<?= (int) $post['PostID'] ?>"
                                            >
                                                Gửi
                                            </button>
                                        </div>

                                        <div class="comment-list mt-3">
                                            <?php if (!empty($comments)): ?>
                                                <?php renderDetailComments($comments, (int) $currentUserId, (int) $post['UserID']); ?>
                                            <?php else: ?>
                                                <div class="post-detail-empty-comments">Chưa có bình luận nào.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
    window.APP_BASE_URL = "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260526-comments-mvc"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
