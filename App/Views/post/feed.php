<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

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

require_once __DIR__ . '/../../Controllers/PostController.php';
require_once __DIR__ . '/../../Controllers/FollowController.php';
require_once __DIR__ . '/../../Controllers/NotificationController.php';
require_once __DIR__ . '/partials/post-menu.php';

/** @var \App\Controllers\PostController $postController */
$postController = new \App\Controllers\PostController();
$posts = $postController->getFeedPosts($currentUserId);
$trendingHashtags = $postController->getTrendingHashtags(10);

/** @var \App\Controllers\FollowController $followController */
$followController = new \App\Controllers\FollowController();
$suggestedUsers = $followController->getSuggestedUsers($currentUserId);

$notificationController = new \App\Controllers\NotificationController();
$unreadNotificationCount = $notificationController->countBadgeForCurrentUser();

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

function publicLocalPath($path) {
    return __DIR__ . '/../../../' . ltrim((string) $path, '/');
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

    if (str_starts_with($cleanPath, "Public/")) {
        $localPath = publicLocalPath($cleanPath);
        return is_file($localPath) ? app_url($cleanPath) : '';
    }

    if (str_starts_with($cleanPath, "uploads/") || str_starts_with($cleanPath, "assets/")) {
        $publicPath = 'Public/' . $cleanPath;
        $localPath = publicLocalPath($publicPath);
        return is_file($localPath) ? app_url($publicPath) : '';
    }

    $publicPath = 'Public/uploads/posts/' . basename($cleanPath);
    $localPath = publicLocalPath($publicPath);
    return is_file($localPath) ? app_url($publicPath) : '';
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
    return BASE_URL . "App/Views/profile/profile.php?id=" . urlencode((string) $userId);
}

function postDetailUrl($postId) {
    return BASE_URL . "App/Views/post/post-detail.php?id=" . urlencode((string) $postId);
}

function hashtagUrl($tag) {
    return BASE_URL . "App/Views/hashtags/hashtag.php?tag=" . urlencode((string) $tag);
}

function privacyLabel($privacy) {
    return match ($privacy) {
        'followers' => 'Người theo dõi',
        'private' => 'Riêng tư',
        default => 'Công khai'
    };
}

function privacyIcon($privacy) {
    return match ($privacy) {
        'followers' => 'bi-people',
        'private' => 'bi-lock',
        default => 'bi-globe2'
    };
}

function renderPrivacyBadge($privacy): string {
    $privacy = in_array($privacy, ['public', 'followers', 'private'], true) ? $privacy : 'public';

    return '<span class="post-privacy-badge post-privacy-' . htmlspecialchars($privacy, ENT_QUOTES, 'UTF-8') . '" data-privacy-badge>'
        . '<i class="bi ' . htmlspecialchars(privacyIcon($privacy), ENT_QUOTES, 'UTF-8') . '"></i>'
        . '<span>' . htmlspecialchars(privacyLabel($privacy), ENT_QUOTES, 'UTF-8') . '</span>'
        . '</span>';
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

function renderFeedComment(array $comment, array $post, int $currentUserId, bool $isReply = false): void {
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
        class="comment-item<?= $isReply ? ' comment-reply' : '' ?>"
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
            src="<?= htmlspecialchars(imagePath($comment['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            class="comment-avatar"
            alt="avatar"
            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
        >
        <div class="comment-body">
            <div class="comment-bubble">
                <div class="comment-meta">
                    <strong class="comment-author"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="comment-time">• <?= htmlspecialchars(timeAgo($comment['CreatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
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
    <title>Archive - Feed</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php include __DIR__ . '/../partials/fonts.php'; ?>
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
                    <a href="<?php echo BASE_URL; ?>App/Views/search/search.php" class="header-search-btn"><i class="bi bi-search"></i></a>
                    <a href="#" class="header-star-btn"><i class="bi bi-star"></i></a>
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
                <?php
                    $activePage = 'home';
                    include __DIR__ . '/partials/sidebar.php';
                ?>
            </div>
            <div class="col-lg-7 col-md-8">
                <div class="feed-title text-center mb-4">Bảng tin</div>

                <div
                    class="bg-white p-3 p-md-4 mb-4 post-composer feed-create-entry"
                    role="link"
                    tabindex="0"
                    data-create-post-url="<?php echo BASE_URL; ?>App/Views/post/createpost.php"
                    aria-label="Tạo bài viết mới"
                >
                    <div class="d-flex gap-3 composer-layout align-items-center">
                        <img
                            src="<?= htmlspecialchars(imagePath($currentAvatar), ENT_QUOTES, 'UTF-8') ?>"
                            class="avatar composer-avatar"
                            alt="avatar"
                            onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                        >
                        <div class="feed-create-input flex-grow-1">
                            <span>Bạn đang nghĩ gì?</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </div>
                </div>

                <div id="posts-list">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <?php $comments = $post['Comments'] ?? []; ?>
                            <?php $isLiked = (int) ($post['IsLiked'] ?? 0) > 0; ?>
                            <div
                                class="bg-white post-card mb-3"
                                id="post-<?= (int) $post['PostID'] ?>"
                                <?= archivePostCardAttributes($post, (int) $currentUserId) ?>
                            >
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
                                            <div class="post-card-header">
                                                <div class="post-meta-line">
                                                    <a href="<?= profileUrl($post['UserID']) ?>" class="post-author-link text-decoration-none">
                                                        <?= htmlspecialchars($post['FullName'] ?: '@' . $post['Username']) ?>
                                                    </a>
                                                    <span class="post-time">• <?= timeAgo($post['CreatedAt']) ?></span>
                                                    <?= renderPrivacyBadge($post['Privacy'] ?? 'public') ?>
                                                </div>

                                                <?php archiveRenderPostMenu($post, (int) $currentUserId); ?>
                                            </div>

                                            <div
                                                class="post-clickable"
                                                role="link"
                                                tabindex="0"
                                                data-post-url="<?= htmlspecialchars(postDetailUrl($post['PostID']), ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openPostDetail(this, event)"
                                                onkeydown="handlePostClickableKeydown(this, event)"
                                            >
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
                                                                    class="img-fluid rounded-4 mb-3 no-post-nav"
                                                                    style="max-height: 450px; object-fit: cover;"
                                                                >
                                                                    <source src="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars(postMediaMimeType($img), ENT_QUOTES, 'UTF-8') ?>">
                                                                    Trình duyệt không hỗ trợ video này.
                                                                </video>
                                                                <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small d-block mb-3 no-post-nav">Mở file video</a>
                                                            <?php elseif ($postMediaType === 'image'): ?>
                                                                <img
                                                                    src="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>"
                                                                    class="img-fluid rounded-4 mb-3"
                                                                    style="max-height: 450px; object-fit: cover;"
                                                                    alt="post image"
                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                                >
                                                                <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small mb-3 no-post-nav" style="display:none;">Mở file ảnh</a>
                                                            <?php else: ?>
                                                                <a href="<?= htmlspecialchars($postMediaSrc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small d-block mb-3 no-post-nav">Mở file ảnh</a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <div class="post-actions d-flex gap-4">
                                                <button
                                                    type="button"
                                                    class="feed-like-btn no-post-nav <?= $isLiked ? 'liked' : '' ?>"
                                                    onclick="toggleLike(this)"
                                                    data-post-id="<?= (int) $post['PostID'] ?>"
                                                    aria-pressed="<?= $isLiked ? 'true' : 'false' ?>"
                                                >
                                                    <i class="bi <?= $isLiked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                                    <span class="like-count"><?= (int) ($post['LikeCount'] ?? 0) ?></span>
                                                </button>

                                                 <button type="button" class="no-post-nav" onclick="toggleCommentBox(this)">
                                                    <i class="bi bi-chat"></i>
                                                    <span class="comment-count">
                                                        <?= $post['CommentCount'] ?? 0 ?>
                                                    </span>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="no-post-nav repost-btn"
                                                    onclick="repostPost(this)"
                                                    data-post-id="<?= (int) $post['PostID'] ?>"
                                                    title="Đăng lại bài viết"
                                                >
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </div>
                                            <div class="comment-box mt-3 d-none no-post-nav">
                                            <div class="comment-form d-flex gap-2">
                                                <?php $commentInputId = 'feedCommentInput-' . (int) $post['PostID']; ?>
                                                <label for="<?= htmlspecialchars($commentInputId, ENT_QUOTES, 'UTF-8') ?>" class="visually-hidden">Viết bình luận</label>
                                                <textarea
                                                    id="<?= htmlspecialchars($commentInputId, ENT_QUOTES, 'UTF-8') ?>"
                                                    class="form-control comment-input" 
                                                    placeholder="Viết bình luận..."
                                                    rows="1"
                                                ></textarea>

                                                <button 
                                                type="button"
                                                    class="btn btn-pink comment-submit"
                                                    onclick="sendComment(this)"
                                                    data-post-id="<?= $post['PostID'] ?>"
                                                >
                                                    Gửi
                                                </button>
                                            </div>

                                                <div class="comment-list">
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
                                                            renderFeedComment($comment, $post, (int) $currentUserId);
                                                        ?>
                                                        <?php foreach ($commentsByParent[$commentId] ?? [] as $reply): ?>
                                                            <?php renderFeedComment($reply, $post, (int) $currentUserId, true); ?>
                                                        <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white post-card mb-3 text-center py-5" role="status">
                            <div class="py-4">
                                <i class="bi bi-file-text" style="font-size: 3rem; color: #d69096; opacity: 0.5;"></i>
                                <h5 class="mt-3 text-muted">Chưa có bài viết nào</h5>
                                <p class="text-muted mb-0">Hãy là người đầu tiên đăng bài! Bắt đầu bằng cách nhấn vào ô "Bạn đang nghĩ gì?" ở trên.</p>
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
                                            title="Xem hồ sơ <?= htmlspecialchars($user['FullName'] ?: '@' . $user['Username'], ENT_QUOTES, 'UTF-8') ?>"
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
                                                <?= htmlspecialchars($user['FullName'] ?: '@' . $user['Username']) ?>
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
    window.FEED_CREATE_POST_URL = "<?php echo BASE_URL; ?>App/Views/post/createpost.php";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260527-feed-create-link"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/partials/bottom-nav.php'; ?>
</body>
</html>
