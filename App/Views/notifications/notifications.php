<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

require_once __DIR__ . '/../../Controllers/NotificationController.php';

$notificationController = new \App\Controllers\NotificationController();
$activityData = $notificationController->index();
$notifications = $activityData['notifications'];
$unreadCount = $activityData['unreadCount'];

function notificationAssetPath($path, $default = '') {
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

function notificationAvatar($path) {
    return notificationAssetPath($path, BASE_URL . "Public/assets/img/default-avatar.jpg");
}

function notificationTimeAgo($datetime) {
    if (empty($datetime)) {
        return "";
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút trước";
    if ($diff < 86400) return floor($diff / 3600) . " giờ trước";
    if ($diff < 604800) return floor($diff / 86400) . " ngày trước";

    return date("d/m/Y H:i", $timestamp);
}

function notificationShortText($text, $limit = 90) {
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }

    if (!function_exists('mb_strlen') && strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }

    return $text;
}

function notificationMessage($notification) {
    $comment = notificationShortText($notification['CommentContent'] ?? '', 70);
    $storedMessage = trim((string) ($notification['NotificationMessage'] ?? ''));
    $typeName = (string) ($notification['TypeName'] ?? '');

    if ($typeName === 'System') {
        return $storedMessage !== '' ? $storedMessage : 'Bạn có một thông báo hệ thống mới.';
    }

    if ($storedMessage !== '' && str_contains($storedMessage, 'đã trả lời bình luận của bạn')) {
        return 'đã trả lời bình luận của bạn';
    }

    return match ($typeName) {
        'Like' => 'đã thích bài viết của bạn',
        'Comment' => 'đã bình luận về bài viết của bạn' . ($comment !== '' ? ': "' . $comment . '"' : ''),
        'ReplyComment',
        'CommentReply' => 'đã trả lời bình luận của bạn' . ($comment !== '' ? ': "' . $comment . '"' : ''),
        'Follow' => 'đã bắt đầu theo dõi bạn',
        'ReportWarning' => 'Bài viết của bạn đã nhận cảnh báo báo cáo',
        'ContentHidden' => 'Nội dung của bạn đã bị ẩn',
        default => 'có hoạt động mới liên quan đến bạn'
    };
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Activity</title>

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
                    <i class="bi bi-heart"></i>
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
                <?php $activePage = 'notifications'; include __DIR__ . '/../post/partials/sidebar.php'; ?>
            </div>

            <div class="col-lg-7 col-md-10 mx-auto">
                <div class="activity-header">
                    <div>
                        <div class="feed-title mb-1">Activity</div>
                        <div class="activity-subtitle"><?= (int) $unreadCount ?> thông báo chưa đọc</div>
                    </div>

                    <?php if ($unreadCount > 0): ?>
                        <button type="button" class="activity-read-all-btn" id="markAllReadBtn">
                            <i class="bi bi-check2-all"></i>
                            <span>Đánh dấu đã đọc</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="activity-list bg-white">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                                $isModerationNotification = in_array((int) ($notification['NotificationTypeID'] ?? 0), [4, 5], true)
                                    || in_array((string) ($notification['TypeName'] ?? ''), ['ReportWarning', 'ContentHidden'], true);
                                $isSystemNotification = (string) ($notification['TypeName'] ?? '') === 'System';
                                $isDetailNotification = $isModerationNotification || $isSystemNotification;
                                $senderName = $isSystemNotification
                                    ? 'Tổng cục kiểm duyệt'
                                    : ($isModerationNotification
                                        ? 'Hệ thống'
                                        : ($notification['SenderName'] ?: (!empty($notification['SenderUsername']) ? '@' . $notification['SenderUsername'] : 'Người dùng')));
                                $isUnread = (int) ($notification['IsRead'] ?? 0) === 0;
                                $openUrl = BASE_URL . "App/Controllers/NotificationController.php?action=open&id=" . urlencode((string) $notification['NotificationID']);
                                $profileUrl = BASE_URL . "App/Views/profile/profile.php?id=" . urlencode((string) $notification['SenderUserID']);
                                $avatarUrl = $isDetailNotification ? $openUrl : $profileUrl;
                            ?>
                            <div class="activity-item <?= $isUnread ? 'unread' : '' ?>" data-notification-id="<?= (int) $notification['NotificationID'] ?>">
                                <a href="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" class="activity-avatar-link">
                                    <img
                                        src="<?= htmlspecialchars(notificationAvatar($notification['SenderAvatar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="avatar"
                                        alt="avatar"
                                        onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
                                    >
                                </a>

                                <a href="<?= htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') ?>" class="activity-content-link">
                                    <div class="activity-line">
                                        <strong><?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars(notificationMessage($notification), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <?php if (!empty($notification['PostContent'])): ?>
                                        <div class="activity-post-snippet">
                                            <?= htmlspecialchars(notificationShortText($notification['PostContent'], 110), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="activity-time">
                                        <?= htmlspecialchars(notificationTimeAgo($notification['CreatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </a>

                                <?php if (!empty($notification['PostThumbnail'])): ?>
                                    <a href="<?= htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') ?>" class="activity-thumb-link">
                                        <img
                                            src="<?= htmlspecialchars(notificationAssetPath($notification['PostThumbnail']), ENT_QUOTES, 'UTF-8') ?>"
                                            class="activity-thumb"
                                            alt="post thumbnail"
                                            onerror="this.style.display='none';"
                                        >
                                    </a>
                                <?php endif; ?>

                                <?php if ($isUnread): ?>
                                    <button type="button" class="activity-unread-dot" title="Đánh dấu đã đọc" aria-label="Đánh dấu đã đọc"></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-empty">
                            <i class="bi bi-heart"></i>
                            <p>Chưa có thông báo nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.NOTIFICATION_CSRF_TOKEN = "<?= htmlspecialchars(\App\Services\CsrfService::getToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/notifications.js?v=20260527-read-csrf"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../post/partials/bottom-nav.php'; ?>
</body>
</html>
