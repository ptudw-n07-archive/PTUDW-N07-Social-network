<?php
require_once __DIR__ . '/../../../Config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

function adminAssetPath($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return BASE_URL . 'Public/assets/img/default-avatar.jpg';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $path = ltrim($path, '/');
    if (str_starts_with($path, 'Public/')) {
        return BASE_URL . $path;
    }

    return BASE_URL . 'Public/' . $path;
}

/** @var array $stats */
/** @var array $reports */
/** @var array $members */
/** @var array $roles */
/** @var int|null $currentAdminId */
/** @var array|null $currentAdmin */
$adminProfileUrl = BASE_URL . 'App/Views/admin/profile.php';
$adminAvatarUrl = adminAssetPath($currentAdmin['ProfilePictureUrl'] ?? ($_SESSION['avatar'] ?? $_SESSION['ProfilePictureUrl'] ?? ''));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Management Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link class="router-css" rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
    <link class="router-css" rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/admin-style.css">
</head>

<body class="admin-body">
    <header class="archive-header">
        <div class="container-fluid px-4 px-lg-5">
            <div class="row align-items-center py-3">
                <div class="col-4 d-flex align-items-center">
                    <a href="<?php echo BASE_URL; ?>App/Views/admin/dashboard.php#overview" class="brand-logo text-decoration-none admin-dashboard-logo">ARCHIVE</a>
                </div>
                <div class="col-4 d-flex justify-content-center align-items-center">
                    <div class="header-badge"><i class="bi bi-stars"></i></div>
                </div>
                <div class="col-4 d-flex justify-content-end align-items-center gap-3">
                    <a href="<?php echo htmlspecialchars($adminProfileUrl, ENT_QUOTES); ?>" class="admin-profile-link d-flex align-items-center gap-2 me-2" title="Admin Profile">
                        <span class="text-muted small fw-bold d-none d-md-inline" id="adminHeaderName">Quản trị viên</span>
                        <img id="adminHeaderAvatar" class="admin-profile-avatar" src="<?php echo htmlspecialchars($adminAvatarUrl, ENT_QUOTES); ?>" alt="Admin avatar">
                    </a>
                    <button id="logoutBtn" class="header-logout-btn" type="button" data-logout-url="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout">
                        <i class="bi bi-box-arrow-right"></i> <span>Đăng xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid admin-main py-5">
        <div class="text-center mb-5">
            <h1 class="management-title">Trung tâm điều khiển</h1>
            <p class="management-subtitle">Nơi điều phối và lưu giữ những khoảnh khắc của Archive</p>
            <div class="admin-live-clock"><i class="bi bi-clock-history"></i><span data-admin-clock>--:--:-- · --/--/----</span></div>
        </div>

        <div class="d-flex justify-content-center mb-5">
            <ul class="nav nav-pills custom-admin-tabs" id="adminTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><i class="bi bi-grid-1x2 me-2"></i>Tổng quan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab"><i class="bi bi-bar-chart-line me-2"></i>Thống kê</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab"><i class="bi bi-shield-check me-2"></i>Kiểm duyệt</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab"><i class="bi bi-collection me-2"></i>Nội dung</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab"><i class="bi bi-bell me-2"></i>Thông báo</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab"><i class="bi bi-person-badge me-2"></i>Thành viên</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <?php require __DIR__ . '/partials/overview.php'; ?>
            <?php require __DIR__ . '/partials/statistics.php'; ?>
            <?php require __DIR__ . '/partials/moderation.php'; ?>
            <?php require __DIR__ . '/partials/content.php'; ?>
            <?php require __DIR__ . '/partials/notifications.php'; ?>
            <?php require __DIR__ . '/partials/members.php'; ?>
        </div>
    </main>

    <div id="adminToastContainer" class="admin-toast-container" aria-live="polite" aria-atomic="true"></div>

    <?php require __DIR__ . '/partials/shared-modals.php'; ?>

    <script>
        // Gom các endpoint admin cho file JS dùng chung.
        window.ADMIN_PROCESS_REPORT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=processReport";
        window.ADMIN_OVERVIEW_STATS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=overviewStats";
        window.ADMIN_OVERVIEW_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=overviewDetail";
        window.ADMIN_STATISTICS_RANKINGS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsRankings";
        window.ADMIN_STATISTICS_CHARTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsCharts";
        window.ADMIN_STATISTICS_INSIGHTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsInsights";
        window.ADMIN_REPORT_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getReportDetail";
        window.ADMIN_UPDATE_USER_ROLE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateUserRole";
        window.ADMIN_TOGGLE_USER_ACTIVE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleUserActive";
        window.ADMIN_LIST_MEMBERS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listMembers";
        window.ADMIN_LIST_NOTIFICATIONS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listNotifications";
        window.ADMIN_NOTIFICATION_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getNotificationDetail";
        window.ADMIN_DELETE_NOTIFICATION_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteNotification";
        window.ADMIN_SEARCH_NOTIFICATION_RECEIVERS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=searchNotificationReceivers";
        window.ADMIN_SEND_SYSTEM_NOTIFICATION_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=sendSystemNotification";
        window.ADMIN_LIST_CONTENT_POSTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentPosts";
        window.ADMIN_CONTENT_POST_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getContentPostDetail";
        window.ADMIN_TOGGLE_CONTENT_POST_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentPostHidden";
        window.ADMIN_DELETE_CONTENT_POST_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentPost";
        window.ADMIN_LIST_CONTENT_COMMENTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentComments";
        window.ADMIN_CONTENT_COMMENT_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getContentCommentDetail";
        window.ADMIN_TOGGLE_CONTENT_COMMENT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentCommentHidden";
        window.ADMIN_DELETE_CONTENT_COMMENT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentComment";
        window.ADMIN_LIST_CONTENT_HASHTAGS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentHashtags";
        window.ADMIN_TOGGLE_CONTENT_HASHTAG_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentHashtagHidden";
        window.ADMIN_DELETE_CONTENT_HASHTAG_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentHashtag";
        window.ADMIN_CURRENT_USER_ID = <?php echo (int)($currentAdminId ?? 0); ?>;
        window.ADMIN_BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-core.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-script.js"></script>
</body>
</html>
