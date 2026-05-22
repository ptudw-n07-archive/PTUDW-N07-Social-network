<?php
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

$activePage = $activePage ?? '';
$showMoreMenu = $showMoreMenu ?? true;
$unreadNotificationCount = $unreadNotificationCount ?? null;

if ($unreadNotificationCount === null && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../Controllers/NotificationController.php';
    $sidebarNotificationController = new \App\Controllers\NotificationController();
    $unreadNotificationCount = $sidebarNotificationController->countUnreadForCurrentUser();
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
        href="<?php echo BASE_URL; ?>App/Views/feed.php"
        class="sidebar-icon<?= sidebarActiveClass('home', $activePage) ?>"
        title="Trang chủ"
    >
        <i class="bi bi-house-door"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/search.php"
        class="sidebar-icon<?= sidebarActiveClass('search', $activePage) ?>"
        title="Tìm kiếm"
    >
        <i class="bi bi-search"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/createpost.php"
        class="sidebar-icon<?= sidebarActiveClass('create', $activePage) ?>"
        title="Đăng bài"
    >
        <i class="bi bi-plus-square"></i>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/notifications.php"
        class="sidebar-icon sidebar-icon-with-badge<?= sidebarActiveClass('notifications', $activePage) ?>"
        title="Thông báo"
    >
        <i class="bi bi-heart"></i>
        <?php if ((int) $unreadNotificationCount > 0): ?>
            <span class="notification-badge"><?= min((int) $unreadNotificationCount, 99) ?></span>
        <?php endif; ?>
    </a>

    <a
        href="<?php echo BASE_URL; ?>App/Views/profile.php"
        class="sidebar-icon<?= sidebarActiveClass('profile', $activePage) ?>"
        title="Hồ sơ"
    >
        <i class="bi bi-person"></i>
    </a>

    <?php if ($showMoreMenu): ?>
        <div class="more-menu-wrapper">
            <button type="button" class="more-button" id="moreButton" aria-expanded="false" aria-controls="moreDropdown">
                <i class="bi bi-list more-icon"></i>
                <span>More</span>
            </button>

            <div class="more-dropdown" id="moreDropdown">
                <button type="button" class="more-dropdown-item">Appearance</button>
                <button type="button" class="more-dropdown-item">Settings</button>
                <hr>
                <button type="button" class="more-dropdown-item">Liked</button>
                <button type="button" class="more-dropdown-item">Archive</button>
                <hr>
                <button type="button" class="more-dropdown-item">Report a problem</button>
                <a href="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout" class="more-dropdown-item logout-item">Log out</a>
            </div>
        </div>
    <?php endif; ?>
</aside>
