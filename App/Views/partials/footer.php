<?php
$archiveFooterUrl = static function (string $path = ''): string {
    $path = ltrim($path, '/');

    if (function_exists('app_url')) {
        return app_url($path);
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $publicPosition = strpos($scriptName, '/Public/');

    if ($publicPosition !== false) {
        $basePath = substr($scriptName, 0, $publicPosition + 1);
        return $basePath . $path;
    }

    return '/' . $path;
};

$archiveFooterCurrentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$archiveFooterIndexPath = 'Public/index.php';
$archiveFooterIsIndex = substr($archiveFooterCurrentScript, -strlen($archiveFooterIndexPath)) === $archiveFooterIndexPath;

$archiveFooterLinks = [
    ['label' => 'Trang chủ', 'url' => $archiveFooterUrl('Public/index.php')],
    ['label' => 'Đăng nhập', 'url' => $archiveFooterUrl('App/Views/auth/login.php')],
    ['label' => 'Đăng ký', 'url' => $archiveFooterUrl('App/Views/auth/register.php')],
    ['label' => 'Giới thiệu đồ án', 'url' => $archiveFooterIsIndex ? '#project-section' : $archiveFooterUrl('Public/index.php') . '#project-section'],
];

$archiveFooterMembers = [
    'Nguyễn Du Mỹ Kỳ',
    'Nguyễn Gia Hân',
    'Trần Hồng Mai',
    'Trịnh Nguyễn Thanh Tuyền',
];
unset($archiveFooterIndexPath);
?>

<footer class="archive-footer mt-5" aria-labelledby="archive-footer-heading">
    <div class="container px-3 px-lg-4">
        <div class="archive-footer-shell">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-4 col-md-6">
                    <h2 id="archive-footer-heading" class="archive-footer-brand">ARCHIVE</h2>
                    <p class="archive-footer-text mb-0">
                        Không gian nhỏ để lưu giữ cảm xúc, khoảnh khắc và những cuộc trò chuyện nhẹ nhàng.
                    </p>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h3 class="archive-footer-title">Điều hướng</h3>
                    <ul class="archive-footer-list">
                        <?php foreach ($archiveFooterLinks as $link): ?>
                            <li>
                                <a class="archive-footer-link" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h3 class="archive-footer-title">Đồ án</h3>
                    <ul class="archive-footer-list archive-footer-meta">
                        <li>Môn: Phát triển ứng dụng Web</li>
                        <li>Nhóm 7</li>
                        <li>UEH</li>
                        <li>PHP MVC / MySQL / Bootstrap</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h3 class="archive-footer-title">Thành viên</h3>
                    <ul class="archive-footer-list archive-footer-meta">
                        <?php foreach ($archiveFooterMembers as $member): ?>
                            <li><?= htmlspecialchars($member, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="archive-footer-bottom">
                <span class="archive-footer-bottom-icon" aria-hidden="true">
                    <i class="bi bi-stars"></i>
                </span>
                <span>&copy; 2026 Archive. Developed by Group 7.</span>
            </div>
        </div>
    </div>
</footer>
