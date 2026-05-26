<?php
require_once __DIR__ . '/../../../../Config/Database.php';

$activePage = $activePage ?? '';
$unreadNotificationCount = $unreadNotificationCount ?? null;

if ($unreadNotificationCount === null && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../Controllers/NotificationController.php';
    $sidebarNotificationController = new \App\Controllers\NotificationController();
    $unreadNotificationCount = $sidebarNotificationController->countBadgeForCurrentUser();
}

function sidebarActiveClass($page, $activePage) {
    return $page === $activePage ? ' active' : '';
}
?>

<aside class="left-sidebar d-flex flex-column align-items-center gap-4">
    <div class="sidebar-logo">
        <i class="bi bi-circle-square"></i>
    </div>

    <a
        href="<?php echo BASE_URL; ?>App/Views/post/feed.php"
        class="sidebar-icon<?= sidebarActiveClass('home', $activePage) ?>"
        title="Trang chủ"
    >
        <i class="bi bi-house-door"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/search/search.php"
        class="sidebar-icon<?= sidebarActiveClass('search', $activePage) ?>"
        title="Tìm kiếm"
    >
        <i class="bi bi-search"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/post/createpost.php"
        class="sidebar-icon<?= sidebarActiveClass('create', $activePage) ?>"
        title="Đăng bài"
    >
        <i class="bi bi-plus-square"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/notifications/notifications.php"
        class="sidebar-icon sidebar-icon-with-badge<?= sidebarActiveClass('notifications', $activePage) ?>"
        title="Thông báo"
    >
        <i class="bi bi-heart"></i>
        <?php if ((int) $unreadNotificationCount > 0): ?>
            <span class="notification-badge"><?= min((int) $unreadNotificationCount, 99) ?></span>
        <?php endif; ?>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/profile/profile.php"
        class="sidebar-icon<?= sidebarActiveClass('profile', $activePage) ?>"
        title="Hồ sơ"
    >
        <i class="bi bi-person"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout"
        class="sidebar-icon sidebar-logout mt-auto"
        title="Đăng xuất"
    >
        <i class="bi bi-box-arrow-right"></i>
    </a>
</aside>
