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
require_once __DIR__ . '/partials/post-render-helpers.php';

/** @var \App\Controllers\PostController $postController */
$postController = new \App\Controllers\PostController();
$currentUser = $postController->getCurrentUser($currentUserId);
$currentUsername = $currentUser['Username'] ?? $currentUsername;
$currentFullName = $currentUser['FullName'] ?? $currentFullName;
$currentAvatar = $currentUser['ProfilePictureUrl']
    ?? $_SESSION['ProfilePictureUrl']
    ?? $_SESSION['avatar']
    ?? $_SESSION['user_avatar']
    ?? '';

if ($currentAvatar !== '') {
    $_SESSION['ProfilePictureUrl'] = $currentAvatar;
}

$posts = $postController->getFeedPosts($currentUserId);
$trendingHashtags = $postController->getTrendingHashtags(10);

/** @var \App\Controllers\FollowController $followController */
$followController = new \App\Controllers\FollowController();
$suggestedUsers = $followController->getSuggestedUsers($currentUserId);

$notificationController = new \App\Controllers\NotificationController();
$unreadNotificationCount = $notificationController->countBadgeForCurrentUser();

function imagePath($path) {
    return archiveImagePath($path);
}

function timeAgo($datetime) {
    return archiveTimeAgo($datetime);
}

function profileUrl($userId) {
    return archiveProfileUrl($userId);
}

function postDetailUrl($postId) {
    return archivePostDetailUrl($postId);
}

function hashtagUrl($tag) {
    return archiveHashtagUrl($tag);
}

function renderPrivacyBadge($privacy): string {
    return archiveRenderPrivacyBadge($privacy);
}

function renderPostContentWithHashtags($content) {
    return archiveRenderPostContentWithHashtags($content);
}

function parseRepostContent($content): ?array {
    return archiveParseRepostContent($content);
}

function renderPostMediaList($images, string $wrapperClass = 'post-media-list'): string {
    return archiveRenderPostMediaList($images, $wrapperClass);
}

function renderRepostEmbed(array $post): string {
    return archiveRenderRepostEmbed($post);
}

function renderFeedComment(array $comment, array $post, int $currentUserId, bool $isReply = false): void {
    archiveRenderComment($comment, $post, $currentUserId, $isReply);
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
                            <?php $isRepost = parseRepostContent($post['Content'] ?? '') !== null || !empty($post['OriginalPostID']); ?>
                            <div
                                class="bg-white post-card mb-3<?= $isRepost ? ' repost-card' : '' ?>"
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
                                                <?php if ($isRepost): ?>
                                                    <?= renderRepostEmbed($post) ?>
                                                <?php else: ?>
                                                    <p class="post-text">
                                                        <?= renderPostContentWithHashtags($post['Content']) ?>
                                                    </p>
                                                    <?= renderPostMediaList($post['Images'] ?? '', 'post-media-list') ?>
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
                                                    <span class="like-count" data-like-count><?= (int) ($post['LikeCount'] ?? 0) ?></span>
                                                </button>

                                                 <button type="button" class="no-post-nav" onclick="toggleCommentBox(this)">
                                                    <i class="bi bi-chat"></i>
                                                    <span class="comment-count" data-comment-count>
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

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
    window.APP_BASE_URL = "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>";
    window.FEED_CSRF_TOKEN = "<?= htmlspecialchars(\App\Services\CsrfService::getToken(), ENT_QUOTES, 'UTF-8') ?>";
    window.FEED_CREATE_POST_URL = "<?php echo BASE_URL; ?>App/Views/post/createpost.php";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/hashtag-suggestions.js?v=20260528-contenteditable"></script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260528-contenteditable"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/partials/bottom-nav.php'; ?>
</body>
</html>
