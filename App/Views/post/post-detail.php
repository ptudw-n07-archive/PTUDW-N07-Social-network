<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('login'));
    exit();
}

require_once __DIR__ . '/../../Controllers/PostController.php';
require_once __DIR__ . '/partials/post-menu.php';
require_once __DIR__ . '/partials/post-render-helpers.php';

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

function detailImagePath($path) {
    return archiveImagePath($path);
}

function detailTimeAgo($datetime) {
    return archiveTimeAgo($datetime, true);
}

function detailProfileUrl($userId) {
    return archiveProfileUrl($userId);
}

function detailHashtagUrl($tag) {
    return archiveHashtagUrl($tag);
}

function detailRenderPrivacyBadge($privacy): string {
    return archiveRenderPrivacyBadge($privacy);
}

function renderDetailPostContent($content) {
    return archiveRenderPostContentWithHashtags($content);
}

function detailParseRepostContent($content): ?array {
    return archiveParseRepostContent($content);
}

function detailRenderPostMediaList($images, string $wrapperClass = 'post-media-list'): string {
    return archiveRenderPostMediaList($images, $wrapperClass);
}

function detailRenderRepostEmbed(array $post): string {
    return archiveRenderRepostEmbed($post);
}

function renderDetailComment(array $comment, array $post, int $currentUserId, int $highlightCommentId = 0, bool $isReply = false): void {
    archiveRenderComment($comment, $post, $currentUserId, $isReply, $highlightCommentId, true);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Bài viết</title>

    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>Public/assets/img/favicon-48x48.png">

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
                <a href="<?php echo app_url('feed'); ?>" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-chat-square-text"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo app_url('search'); ?>" class="header-search-btn"><i class="bi bi-search"></i></a>
                    <a href="<?php echo app_url('profile'); ?>" class="header-login-btn">
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
                    <button type="button" class="post-detail-back" onclick="history.length > 1 ? history.back() : window.location.href='<?php echo app_url('feed'); ?>';" aria-label="Quay lại">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="feed-title mb-0">Bài viết</div>
                </div>

                <?php if (!$post): ?>
                    <div class="bg-white post-card p-4 text-center text-muted">
                        Không tìm thấy bài viết.
                    </div>
                <?php else: ?>
                    <?php $isRepost = detailParseRepostContent($post['Content'] ?? '') !== null || !empty($post['OriginalPostID']); ?>
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
                                        <button
                                            type="button"
                                            onclick="toggleLike(this)"
                                            data-post-id="<?= (int) $post['PostID'] ?>"
                                            class="feed-like-btn no-post-nav <?= (int) ($post['IsLiked'] ?? 0) ? 'liked' : '' ?>"
                                            aria-pressed="<?= (int) ($post['IsLiked'] ?? 0) ? 'true' : 'false' ?>"
                                        >
                                            <i class="bi <?= (int) ($post['IsLiked'] ?? 0) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            <span class="like-count" data-like-count><?= (int) ($post['LikeCount'] ?? 0) ?></span>
                                        </button>

                                        <button type="button" onclick="toggleCommentBox(this)">
                                            <i class="bi bi-chat"></i>
                                            <span class="comment-count" data-comment-count><?= (int) ($post['CommentCount'] ?? 0) ?></span>
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

<div class="modal fade post-detail-photo-modal" id="postDetailPhotoModal" tabindex="-1" aria-labelledby="postDetailPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postDetailPhotoModalLabel">Ảnh bài viết</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="post-detail-photo-nav post-detail-photo-prev" data-post-detail-photo-prev aria-label="Ảnh trước">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <img id="postDetailPhotoModalImage" src="" alt="Ảnh bài viết">
                <button type="button" class="post-detail-photo-nav post-detail-photo-next" data-post-detail-photo-next aria-label="Ảnh tiếp theo">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

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
    window.APP_BASE_URL = "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>";
    window.FEED_CSRF_TOKEN = "<?= htmlspecialchars(\App\Services\CsrfService::getToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/hashtag-suggestions.js?v=20260528-contenteditable"></script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js?v=20260528-contenteditable"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/bottom-nav.php'; ?>
</body>
</html>
