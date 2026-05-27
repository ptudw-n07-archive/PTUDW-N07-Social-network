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
$focusCommentId = (int) ($_GET['comment_id'] ?? $_GET['comment'] ?? 0);
$currentUserId = (int) $_SESSION['user_id'];

$postController = new \App\Controllers\PostController();
$post = $postId > 0 ? $postController->detail($postId, $currentUserId) : null;
$comments = $post ? $postController->getComments($postId) : [];
$focusedCommentAvailable = $focusCommentId === 0;

foreach ($comments as $comment) {
    if ((int) ($comment['CommentID'] ?? 0) === $focusCommentId) {
        $focusedCommentAvailable = true;
        break;
    }
}

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

function detailPublicLocalPath($path) {
    return __DIR__ . '/../../../' . ltrim((string) $path, '/');
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
        $localPath = detailPublicLocalPath($cleanPath);
        return is_file($localPath) ? app_url($cleanPath) : '';
    }

    if (str_starts_with($cleanPath, "uploads/") || str_starts_with($cleanPath, "assets/")) {
        $publicPath = 'Public/' . $cleanPath;
        $localPath = detailPublicLocalPath($publicPath);
        return is_file($localPath) ? app_url($publicPath) : '';
    }

    $publicPath = 'Public/uploads/posts/' . basename($cleanPath);
    $localPath = detailPublicLocalPath($publicPath);
    return is_file($localPath) ? app_url($publicPath) : '';
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

function detailPrivacyLabel($privacy) {
    return match ($privacy) {
        'followers' => 'Người theo dõi',
        'private' => 'Riêng tư',
        default => 'Công khai'
    };
}

function detailPrivacyIcon($privacy) {
    return match ($privacy) {
        'followers' => 'bi-people',
        'private' => 'bi-lock',
        default => 'bi-globe2'
    };
}

function detailRenderPrivacyBadge($privacy): string {
    $privacy = in_array($privacy, ['public', 'followers', 'private'], true) ? $privacy : 'public';

    return '<span class="post-privacy-badge post-privacy-' . htmlspecialchars($privacy, ENT_QUOTES, 'UTF-8') . '" data-privacy-badge>'
        . '<i class="bi ' . htmlspecialchars(detailPrivacyIcon($privacy), ENT_QUOTES, 'UTF-8') . '"></i>'
        . '<span>' . htmlspecialchars(detailPrivacyLabel($privacy), ENT_QUOTES, 'UTF-8') . '</span>'
        . '</span>';
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

function detailParseRepostContent($content): ?array {
    if (!preg_match('/^Đăng lại từ\s+(@[^\s:]+):\s*(.*)$/su', (string) $content, $matches)) {
        return null;
    }

    return [
        'source' => trim($matches[1]),
        'content' => ltrim((string) ($matches[2] ?? ''))
    ];
}

function detailRenderPostMediaList($images, string $wrapperClass = 'post-media-list'): string {
    $imageItems = array_values(array_filter(array_map('trim', explode(',', (string) $images))));

    if (empty($imageItems)) {
        return '';
    }

    $mediaItems = [];

    foreach ($imageItems as $img) {
        $mediaSrc = detailPostMediaPath($img);
        if ($mediaSrc === '') {
            continue;
        }

        $mediaItems[] = [
            'path' => $img,
            'src' => $mediaSrc,
            'type' => detailPostMediaType($img)
        ];
    }

    if (empty($mediaItems)) {
        return '';
    }

    $totalItems = count($mediaItems);
    $isCarousel = $totalItems > 1;
    $classes = trim($wrapperClass . ' post-media-scroll media-count-' . min($totalItems, 4) . ' media-total-' . $totalItems . ($isCarousel ? ' has-multiple-media' : ' has-single-media'));
    $html = '<div class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">';

    if ($isCarousel) {
        $html .= '<button type="button" class="post-media-nav post-media-prev no-post-nav" onclick="scrollPostMedia(this, -1)" aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button>';
    }

    $html .= '<div class="post-media-track">';

    foreach ($mediaItems as $index => $media) {
        $html .= '<div class="post-media-slide">';

        $mediaType = $media['type'];

        if ($mediaType === 'video') {
            $html .= '<video controls class="post-media-video no-post-nav">'
                . '<source src="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" type="' . htmlspecialchars(detailPostMediaMimeType($media['path']), ENT_QUOTES, 'UTF-8') . '">'
                . 'Trình duyệt không hỗ trợ video này.'
                . '</video>';
            $html .= '</div>';
            continue;
        }

        if ($mediaType === 'image') {
            $html .= '<img src="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" class="post-media-image" alt="post image" loading="lazy" onerror="this.style.display=\'none\';">';
            $html .= '</div>';
            continue;
        }

        $html .= '<a href="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" target="_blank" class="post-media-file no-post-nav">Mở file ảnh</a>';
        $html .= '</div>';
    }

    $html .= '</div>';

    if ($isCarousel) {
        $html .= '<button type="button" class="post-media-nav post-media-next no-post-nav" onclick="scrollPostMedia(this, 1)" aria-label="Ảnh tiếp theo"><i class="bi bi-chevron-right"></i></button>';
        $html .= '<span class="post-media-counter">' . $totalItems . ' ảnh</span>';
    }

    return $html . '</div>';
}

function detailRenderRepostEmbed(array $post): string {
    $repost = detailParseRepostContent($post['Content'] ?? '');

    if (!$repost) {
        return '';
    }

    $contentHtml = trim($repost['content']) !== ''
        ? renderDetailPostContent($repost['content'])
        : '<span class="text-muted">Bài viết gốc không có nội dung văn bản.</span>';

    return '<div class="repost-source-label"><i class="bi bi-arrow-repeat"></i><span>Đăng lại bài viết</span></div>'
        . '<div class="repost-embed no-post-nav">'
        . '<div class="repost-embed-header">'
        . '<div class="repost-embed-author"><span class="repost-embed-avatar"><i class="bi bi-person"></i></span><span>' . htmlspecialchars($repost['source'], ENT_QUOTES, 'UTF-8') . '</span></div>'
        . '<div class="repost-embed-meta">Bài viết gốc</div>'
        . '</div>'
        . '<div class="repost-embed-content post-text">' . $contentHtml . '</div>'
        . detailRenderPostMediaList($post['Images'] ?? '', 'repost-embed-media')
        . '</div>';
}

function renderDetailComment(array $comment, array $post, int $currentUserId, int $highlightCommentId = 0, bool $isReply = false): void {
    $commentId = (int) ($comment['CommentID'] ?? 0);
    $commentOwnerId = (int) ($comment['UserID'] ?? 0);
    $postOwnerId = (int) ($post['UserID'] ?? 0);
    $parentCommentId = !empty($comment['ParentCommentID']) ? (int) $comment['ParentCommentID'] : 0;
    $rootCommentId = $isReply && $parentCommentId > 0 ? $parentCommentId : $commentId;
    $canEdit = $commentOwnerId === $currentUserId;
    $canDelete = $canEdit || $postOwnerId === $currentUserId;
    $canReport = $commentOwnerId !== $currentUserId;
    $displayName = !empty($comment['FullName']) ? $comment['FullName'] : '@' . ($comment['Username'] ?? '');
    ?>
    <div
        class="comment-item<?= $isReply ? ' comment-reply' : '' ?><?= $commentId === $highlightCommentId ? ' highlight' : '' ?>"
        id="comment-<?= $commentId ?>"
        data-comment-id="<?= $commentId ?>"
        data-post-id="<?= (int) ($post['PostID'] ?? 0) ?>"
        data-owner-id="<?= $commentOwnerId ?>"
        data-parent-comment-id="<?= $parentCommentId ?>"
        data-root-comment-id="<?= $rootCommentId ?>"
        data-can-edit="<?= $canEdit ? '1' : '0' ?>"
        data-can-delete="<?= $canDelete ? '1' : '0' ?>"
        data-can-report="<?= $canReport ? '1' : '0' ?>"
    >
        <img
            src="<?= htmlspecialchars(detailImagePath($comment['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            class="comment-avatar"
            alt="avatar"
            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
        >
        <div class="comment-body">
            <div class="comment-bubble">
                <div class="comment-meta">
                    <strong class="comment-author"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="comment-time">• <?= htmlspecialchars(detailTimeAgo($comment['CreatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="comment-content"><?= htmlspecialchars($comment['Content'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="comment-actions">
                <button type="button" class="comment-action-btn" onclick="showReplyForm(this)">Trả lời</button>
                <?php if ($canEdit): ?>
                    <button type="button" class="comment-action-btn" onclick="showEditCommentForm(this)">Sửa</button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                    <button type="button" class="comment-action-btn text-danger" onclick="deleteComment(this)">Xóa</button>
                <?php endif; ?>
                <?php if ($canReport): ?>
                    <button type="button" class="comment-action-btn" onclick="showReportCommentForm(this)">Báo cáo</button>
                <?php endif; ?>
            </div>
            <div class="comment-inline-form"></div>
        </div>
    </div>
    <?php
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

<body class="feed-page post-detail-page">
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
                    <?php $isRepost = detailParseRepostContent($post['Content'] ?? '') !== null; ?>
                    <article class="bg-white post-card post-detail-card mb-3<?= $isRepost ? ' repost-card' : '' ?>" id="post-<?= (int) $post['PostID'] ?>" <?= archivePostCardAttributes($post, (int) $currentUserId) ?>>
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
                                            <?= htmlspecialchars($post['FullName'] ?: '@' . $post['Username'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <span class="text-muted">• <?= htmlspecialchars(detailTimeAgo($post['CreatedAt']), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?= detailRenderPrivacyBadge($post['Privacy'] ?? 'public') ?>
                                    </div>

                                    <?php archiveRenderPostMenu($post, (int) $currentUserId); ?>
                                    </div>

                                    <?php if ($isRepost): ?>
                                        <?= detailRenderRepostEmbed($post) ?>
                                    <?php else: ?>
                                        <p class="post-text post-detail-text">
                                            <?= renderDetailPostContent($post['Content']) ?>
                                        </p>
                                        <?= detailRenderPostMediaList($post['Images'] ?? '', 'post-detail-media') ?>
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
                                            class="repost-btn no-post-nav"
                                            onclick="repostPost(this)"
                                            data-post-id="<?= (int) $post['PostID'] ?>"
                                            title="Đăng lại bài viết"
                                        >
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>

                                    </div>

                                    <div class="comment-box mt-3">
                                        <div class="comment-form d-flex gap-2">
                                            <label for="detailCommentInput" class="visually-hidden">Viết bình luận</label>
                                            <textarea
                                                id="detailCommentInput"
                                                class="form-control comment-input"
                                                placeholder="Viết bình luận..."
                                                rows="1"
                                            ></textarea>

                                            <button
                                                type="button"
                                                class="btn btn-pink comment-submit"
                                                onclick="sendComment(this)"
                                                data-post-id="<?= (int) $post['PostID'] ?>"
                                            >
                                                Gửi
                                            </button>
                                        </div>

                                        <?php if ($focusCommentId > 0 && !$focusedCommentAvailable): ?>
                                            <div class="post-detail-comment-unavailable" role="status">
                                                Bình luận này hiện không khả dụng.
                                            </div>
                                        <?php endif; ?>

                                        <div class="comment-list mt-3">
                                            <?php if (!empty($comments)): ?>
                                                <?php
                                                    $commentsByParent = [];
                                                    foreach ($comments as $comment) {
                                                        $parentId = !empty($comment['ParentCommentID']) ? (int) $comment['ParentCommentID'] : 0;
                                                        $commentsByParent[$parentId][] = $comment;
                                                    }
                                                ?>
                                                <?php foreach ($commentsByParent[0] ?? [] as $comment): ?>
                                                    <?php
                                                        $commentId = (int) ($comment['CommentID'] ?? 0);
                                                        renderDetailComment($comment, $post, $currentUserId, $focusCommentId);
                                                    ?>
                                                    <?php foreach ($commentsByParent[$commentId] ?? [] as $reply): ?>
                                                        <?php renderDetailComment($reply, $post, $currentUserId, $focusCommentId, true); ?>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
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

<div class="modal fade archive-comment-delete-modal" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCommentModalLabel">Xóa bình luận?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                Bình luận này sẽ được ẩn khỏi bài viết. Bạn vẫn muốn tiếp tục?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn comment-delete-cancel-btn" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn comment-delete-confirm-btn" data-confirm-delete-comment>Xóa</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.FEED_CSRF_TOKEN = "<?= htmlspecialchars(\App\Services\CsrfService::getToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260527-comment-focus"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/bottom-nav.php'; ?>
</body>
</html>
