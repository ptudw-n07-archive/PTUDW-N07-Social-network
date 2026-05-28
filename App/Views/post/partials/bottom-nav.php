<?php
$activePage = $activePage ?? '';
$unreadNotificationCount = $unreadNotificationCount ?? null;

if ($unreadNotificationCount === null && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../Controllers/NotificationController.php';
    $navNotificationController = new \App\Controllers\NotificationController();
    $unreadNotificationCount = $navNotificationController->countBadgeForCurrentUser();
}

function bottomNavActiveClass($page, $activePage) {
    return $page === $activePage ? ' active' : '';
}
?>
<nav class="bottom-nav d-lg-none">
    <a href="<?php echo app_url('feed'); ?>" class="bottom-nav-item<?= bottomNavActiveClass('home', $activePage) ?>">
        <i class="bi bi-house-door"></i>
        <span>Trang chủ</span>
    </a>
    <a href="<?php echo app_url('search'); ?>" class="bottom-nav-item<?= bottomNavActiveClass('search', $activePage) ?>">
        <i class="bi bi-search"></i>
        <span>Tìm kiếm</span>
    </a>
    <a href="<?php echo app_url('create-post'); ?>" class="bottom-nav-item<?= bottomNavActiveClass('create', $activePage) ?>">
        <i class="bi bi-plus-square"></i>
        <span>Đăng bài</span>
    </a>
    <a href="<?php echo app_url('notifications'); ?>" class="bottom-nav-item bottom-nav-item-with-badge<?= bottomNavActiveClass('notifications', $activePage) ?>">
        <i class="bi bi-heart"></i>
        <?php if ((int) $unreadNotificationCount > 0): ?>
            <span class="bottom-nav-badge"><?= min((int) $unreadNotificationCount, 99) ?></span>
        <?php endif; ?>
        <span>Thông báo</span>
    </a>
    <a href="<?php echo app_url('profile'); ?>" class="bottom-nav-item<?= bottomNavActiveClass('profile', $activePage) ?>">
        <i class="bi bi-person"></i>
        <span>Hồ sơ</span>
    </a>
</nav>
