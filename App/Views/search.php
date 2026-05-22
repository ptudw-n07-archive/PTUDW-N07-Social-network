<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "App/Views/auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Tìm kiếm</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body class="search-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="header-search-btn" title="Trang chủ">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="header-login-btn" title="Hồ sơ">
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
                <aside class="left-sidebar d-flex flex-column align-items-center gap-4">
                    <div class="sidebar-logo">
                        <i class="bi bi-circle-square"></i>
                    </div>

                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="sidebar-icon" title="Trang chủ">
                        <i class="bi bi-house-door-fill"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/search.php" class="sidebar-icon active" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/createpost.php" class="sidebar-icon" title="Đăng bài">
                        <i class="bi bi-plus-square"></i>
                    </a>

                    <a href="#" class="sidebar-icon" title="Thông báo">
                        <i class="bi bi-heart"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="sidebar-icon" title="Hồ sơ">
                        <i class="bi bi-person"></i>
                    </a>

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
                </aside>
            </div>

            <div class="col-lg-8 col-xl-7 mx-auto">
                <div class="feed-title text-center mb-4">Tìm kiếm</div>

                <div class="bg-white search-panel">
                    <form id="searchForm" class="search-form" autocomplete="off">
                        <i class="bi bi-search search-input-icon"></i>
                        <input
                            type="search"
                            id="searchInput"
                            class="search-input"
                            placeholder="Tìm username, họ tên, bài viết hoặc hashtag"
                            aria-label="Tìm kiếm"
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

<script>
    window.SEARCH_CONFIG = {
        baseUrl: "<?php echo BASE_URL; ?>",
        searchUrl: "<?php echo BASE_URL; ?>App/Controllers/SearchController.php",
        followUrl: "<?php echo BASE_URL; ?>App/Controllers/FollowController.php?action=toggle"
    };
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
