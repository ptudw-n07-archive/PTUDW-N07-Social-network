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
    <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="bottom-nav-item<?= bottomNavActiveClass('home', $activePage) ?>">
        <i class="bi bi-house-door"></i>
        <span>Trang chủ</span>
    </a>
    <a href="<?php echo BASE_URL; ?>App/Views/search/search.php" class="bottom-nav-item<?= bottomNavActiveClass('search', $activePage) ?>">
        <i class="bi bi-search"></i>
        <span>Tìm kiếm</span>
    </a>
    <a href="<?php echo BASE_URL; ?>App/Views/post/createpost.php" class="bottom-nav-item<?= bottomNavActiveClass('create', $activePage) ?>">
        <i class="bi bi-plus-square"></i>
        <span>Đăng bài</span>
    </a>
    <a href="<?php echo BASE_URL; ?>App/Views/notifications/notifications.php" class="bottom-nav-item bottom-nav-item-with-badge<?= bottomNavActiveClass('notifications', $activePage) ?>">
        <i class="bi bi-heart"></i>
        <?php if ((int) $unreadNotificationCount > 0): ?>
            <span class="bottom-nav-badge"><?= min((int) $unreadNotificationCount, 99) ?></span>
        <?php endif; ?>
        <span>Thông báo</span>
    </a>
    <a href="<?php echo BASE_URL; ?>App/Views/profile/profile.php" class="bottom-nav-item<?= bottomNavActiveClass('profile', $activePage) ?>">
        <i class="bi bi-person"></i>
        <span>Hồ sơ</span>
    </a>
</nav>
