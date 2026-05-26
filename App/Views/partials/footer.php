<?php
if (!function_exists('archive_footer_url')) {
    function archive_footer_url($path = '') {
        $path = ltrim((string) $path, '/');

        if (function_exists('app_url')) {
            return app_url($path);
        }

        return '/' . $path;
    }
}

$archiveFooterHomeUrl = archive_footer_url('Public/index.php');
$archiveFooterLoginUrl = archive_footer_url('App/Views/auth/login.php');
$archiveFooterRegisterUrl = archive_footer_url('App/Views/auth/register.php');
?>

<footer class="archive-footer">
    <div class="container-fluid px-3 px-lg-5">
        <div class="archive-footer-inner">
            <div class="row gy-4 align-items-start">
                <div class="col-lg-5 col-md-6">
                    <a href="<?= htmlspecialchars($archiveFooterHomeUrl) ?>" class="footer-brand text-decoration-none">ARCHIVE</a>
                    <p class="footer-tagline mb-2">A soft place to keep small thoughts.</p>
                    <p class="footer-description mb-0">
                        Một không gian nhẹ để lưu lại cảm xúc, bài viết và những khoảnh khắc nhỏ.
                    </p>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="footer-heading">Khám phá</div>
                    <nav class="footer-links" aria-label="Footer navigation">
                        <a href="<?= htmlspecialchars($archiveFooterHomeUrl) ?>" class="footer-link">Trang chủ</a>
                        <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-link">Đăng nhập</a>
                        <a href="<?= htmlspecialchars($archiveFooterRegisterUrl) ?>" class="footer-link">Đăng ký</a>
                    </nav>
                </div>

                <div class="col-lg-3 col-md-12">
                    <div class="footer-heading">Kết nối</div>
                    <div class="footer-social">
                        <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-social-link" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-social-link" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-social-link" aria-label="Github">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© 2026 Archive - Dự án môn thiết kê Web - UEH.</span>
                <div class="footer-bottom-links">
                    <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-bottom-link">Điều khoản</a>
                    <span>·</span>
                    <a href="<?= htmlspecialchars($archiveFooterLoginUrl) ?>" class="footer-bottom-link">Chính sách riêng tư</a>
                </div>
            </div>
        </div>
    </div>
</footer>
