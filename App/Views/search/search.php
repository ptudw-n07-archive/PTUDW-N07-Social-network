<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('login'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Tìm kiếm</title>

    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>Public/assets/img/favicon-48x48.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php include __DIR__ . '/../partials/fonts.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body class="search-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo app_url('feed'); ?>" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo app_url('feed'); ?>" class="header-search-btn" title="Trang chủ">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="<?php echo app_url('profile'); ?>" class="header-login-btn" title="Hồ sơ">
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
                <?php $activePage = 'search'; include __DIR__ . '/../post/partials/sidebar.php'; ?>
            </div>

            <div class="col-lg-8 col-xl-7 mx-auto">
                <div class="feed-title text-center mb-4">Tìm kiếm</div>

                <div class="bg-white search-panel">
                    <form id="searchForm" class="search-form" autocomplete="off">
                        <i class="bi bi-search search-input-icon"></i>
                        <label for="searchInput" class="visually-hidden">Tìm kiếm</label>
                        <input
                            type="search"
                            id="searchInput"
                            class="search-input"
                            placeholder="Tìm username, họ tên, bài viết hoặc hashtag"
                        >
                        <button type="submit" class="search-submit-btn" aria-label="Tìm kiếm">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <div id="searchStatus" class="search-status">
                        Nhập ít nhất 2 ký tự để bắt đầu tìm kiếm.
                    </div>

                    <section id="historySection" class="search-section d-none">
                        <div class="search-section-heading">
                            <h5>Gần đây</h5>
                            <button type="button" id="clearHistoryBtn" class="search-clear-btn d-none">Xóa tất cả</button>
                        </div>
                        <div id="historyList" class="search-list"></div>
                    </section>

                    <section id="userSection" class="search-section d-none">
                        <div class="search-section-heading">
                            <h5>Tài khoản</h5>
                        </div>
                        <div id="userResults" class="search-list"></div>
                    </section>

                    <section id="hashtagSection" class="search-section d-none">
                        <div class="search-section-heading">
                            <h5>Hashtag</h5>
                        </div>
                        <div id="hashtagResults" class="search-list"></div>
                    </section>

                    <section id="postSection" class="search-section d-none">
                        <div class="search-section-heading">
                            <h5>Bài viết liên quan</h5>
                        </div>
                        <div id="postResults" class="search-list"></div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
    window.SEARCH_CONFIG = {
        baseUrl: "<?php echo BASE_URL; ?>",
        searchUrl: "<?php echo BASE_URL; ?>App/Controllers/SearchController.php",
        followUrl: "<?php echo BASE_URL; ?>App/Controllers/FollowController.php?action=toggle",
        csrfToken: "<?php echo htmlspecialchars(\App\Services\CsrfService::getToken(), ENT_QUOTES, 'UTF-8'); ?>"
    };
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../post/partials/bottom-nav.php'; ?>
</body>
</html>
