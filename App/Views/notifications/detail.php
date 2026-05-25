<?php
if (!isset($notification) || !is_array($notification)) {
    require_once __DIR__ . '/../../../Config/Database.php';

    $notificationId = (int) ($_GET['id'] ?? 0);

    if ($notificationId > 0) {
        header('Location: ' . app_url('App/Controllers/NotificationController.php?action=detail&id=' . urlencode((string) $notificationId)));
    } else {
        header('Location: ' . app_url('App/Views/notifications/notifications.php'));
    }

    exit();
}

/** @var array $notification Detail row loaded from the notifications table by NotificationController. */
$isContentHidden = (int) ($notification['NotificationTypeID'] ?? 0) === 5
    || (string) ($notification['TypeName'] ?? '') === 'ContentHidden';
$title = $isContentHidden ? 'Nội dung của bạn đã bị ẩn' : 'Cảnh báo bài viết';
$description = $isContentHidden
    ? 'Bài viết này không còn hiển thị công khai do vi phạm tiêu chuẩn cộng đồng hoặc đã được quản trị viên xử lý.'
    : 'Bài viết của bạn đã nhận cảnh báo do bị báo cáo.';
$postId = (int) ($notification['RelatedPostID'] ?? 0);
$commentId = (int) ($notification['RelatedCommentID'] ?? 0);
$hasPost = $postId > 0 && ($notification['PostIsHidden'] ?? null) !== null;
$hasComment = $commentId > 0;
$isPostHidden = $hasPost && (int) $notification['PostIsHidden'] === 1;
$status = $isContentHidden
    ? 'Đã bị ẩn'
    : ($hasPost
        ? ($isPostHidden ? 'Bài viết này đã bị ẩn.' : 'Bài viết hiện vẫn đang hiển thị.')
        : ($hasComment ? 'Bình luận liên quan đã được xử lý.' : 'Không tìm thấy nội dung liên quan đến cảnh báo này.'));

function moderationDetailAssetPath($path) {
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'Public/')) {
        return BASE_URL . $path;
    }

    if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'assets/')) {
        return BASE_URL . 'Public/' . $path;
    }

    return BASE_URL . $path;
}

function moderationDetailShortText($text, $limit = 220) {
    $text = trim((string) $text);

    if ($text === '') {
        return 'Bài viết không có nội dung chữ.';
    }

    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }

    if (!function_exists('mb_strlen') && strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }

    return $text;
}

$thumbnail = moderationDetailAssetPath($notification['PostThumbnail'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

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
                <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>
            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge"><i class="bi bi-heart"></i></div>
            </div>
            <div class="col-4 d-flex justify-content-end">
                <a href="<?php echo BASE_URL; ?>App/Views/notifications/notifications.php" class="header-search-btn" title="Thông báo">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<section class="feed-section py-5">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4">
            <div class="col-lg-1 d-none d-lg-block">
                <?php $activePage = 'notifications'; include __DIR__ . '/../post/partials/sidebar.php'; ?>
            </div>

            <div class="col-lg-7 col-md-10 mx-auto">
                <article class="notification-detail-card bg-white">
                    <div class="notification-detail-icon <?= $isContentHidden ? 'hidden' : '' ?>">
                        <i class="bi <?= $isContentHidden ? 'bi-eye-slash' : 'bi-exclamation-triangle' ?>"></i>
                    </div>

                    <h1 class="notification-detail-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="notification-detail-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>

                    <?php if (trim((string) ($notification['NotificationMessage'] ?? '')) !== ''): ?>
                        <div class="notification-detail-message">
                            <?= nl2br(htmlspecialchars((string) $notification['NotificationMessage'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($notification['ReportID'])): ?>
                        <div class="notification-detail-message">
                            <div class="notification-post-preview-heading">Thông tin báo cáo</div>
                            <div class="mb-2"><strong>Lý do báo cáo:</strong> <?= htmlspecialchars((string) ($notification['ReportReason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>

                            <?php if (trim((string) ($notification['ReportDetails'] ?? '')) !== ''): ?>
                                <div class="mb-2"><strong>Chi tiết báo cáo:</strong> <?= nl2br(htmlspecialchars((string) $notification['ReportDetails'], ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>

                            <?php if (trim((string) ($notification['ReportAdminNote'] ?? '')) !== ''): ?>
                                <div class="mb-2"><strong>Ghi chú admin:</strong> <?= nl2br(htmlspecialchars((string) $notification['ReportAdminNote'], ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>

                            <div class="mb-2"><strong>Trạng thái xử lý:</strong> <?= htmlspecialchars((string) ($notification['ReportStatus'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>

                            <?php if (!empty($notification['ReportResolvedAt'])): ?>
                                <div><strong>Thời gian xử lý:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['ReportResolvedAt'])), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasPost): ?>
                        <div class="notification-post-preview">
                            <div class="notification-post-preview-heading">Bài viết liên quan</div>
                            <p><?= htmlspecialchars(moderationDetailShortText($notification['PostContent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

                            <?php if ($thumbnail !== ''): ?>
                                <img src="<?= htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh bài viết" class="notification-post-preview-image" onerror="this.style.display='none';">
                            <?php endif; ?>

                            <?php if (!empty($notification['PostCreatedAt'])): ?>
                                <div class="notification-post-preview-time">
                                    Đăng lúc <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['PostCreatedAt'])), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasComment): ?>
                        <div class="notification-post-preview">
                            <div class="notification-post-preview-heading">Bình luận liên quan</div>
                            <p><?= htmlspecialchars(moderationDetailShortText($notification['CommentContent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

                            <?php if (!empty($notification['CommentCreatedAt'])): ?>
                                <div class="notification-post-preview-time">
                                    Đăng lúc <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['CommentCreatedAt'])), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="notification-detail-status <?= ($isContentHidden || $isPostHidden) ? 'hidden' : '' ?>">
                        <i class="bi <?= ($isContentHidden || $isPostHidden) ? 'bi-eye-slash' : ($hasPost ? 'bi-check-circle' : 'bi-info-circle') ?>"></i>
                        <span><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="notification-detail-actions">
                        <a href="<?php echo BASE_URL; ?>App/Views/notifications/notifications.php" class="notification-detail-back">
                            <i class="bi bi-arrow-left"></i> Quay lại thông báo
                        </a>

                        <?php if (!$isContentHidden && $hasPost && !$isPostHidden): ?>
                            <a href="<?php echo BASE_URL; ?>App/Views/post/post-detail.php?id=<?= urlencode((string) $postId) ?>" class="btn btn-pink notification-detail-view-post">
                                Xem bài viết
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
</body>
</html>
