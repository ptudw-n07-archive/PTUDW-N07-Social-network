<?php
require_once __DIR__ . '/../../../../Config/Database.php';

$activePage = $activePage ?? '';
$showMoreMenu = $showMoreMenu ?? true;
$moreButtonTitle = $moreButtonTitle ?? 'Menu';
$moreMenuLabels = array_merge([
    'settings' => 'Cài đặt',
    'liked' => 'Đã thích',
    'archive' => 'Lưu trữ',
    'report' => 'Báo cáo sự cố',
    'logout' => 'Đăng xuất'
], $moreMenuLabels ?? []);
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

    <?php if ($showMoreMenu): ?>
        <div class="more-menu-wrapper">
            <button type="button" class="more-button" id="moreButton" aria-expanded="false" aria-controls="moreDropdown" title="<?= htmlspecialchars($moreButtonTitle, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-list more-icon"></i>
            </button>

            <div class="more-dropdown" id="moreDropdown">
                <button type="button" class="more-dropdown-item"><?= htmlspecialchars($moreMenuLabels['settings'], ENT_QUOTES, 'UTF-8') ?></button>
                <hr>
                <button type="button" class="more-dropdown-item"><?= htmlspecialchars($moreMenuLabels['liked'], ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="more-dropdown-item"><?= htmlspecialchars($moreMenuLabels['archive'], ENT_QUOTES, 'UTF-8') ?></button>
                <hr>
                <button type="button" class="more-dropdown-item"><?= htmlspecialchars($moreMenuLabels['report'], ENT_QUOTES, 'UTF-8') ?></button>
                <a href="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout" class="more-dropdown-item logout-item"><?= htmlspecialchars($moreMenuLabels['logout'], ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    <?php endif; ?>
</aside>
