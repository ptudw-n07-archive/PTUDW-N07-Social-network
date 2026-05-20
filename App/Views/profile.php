<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

require_once __DIR__ . '/../Controllers/ProfileController.php';

use App\Controllers\ProfileController;

$profileController = new ProfileController();
$profileData = $profileController->getCurrentProfileData();

$profile = $profileData['profile'];
$posts = $profileData['posts'];
$stats = $profileData['stats'];

function profileImagePath($path) {
    if (empty($path)) {
        return BASE_URL . "Public/assets/img/default-avatar.jpg";
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $cleanPath = str_replace("Public/", "", $path);
    return BASE_URL . "Public/" . ltrim($cleanPath, "/");
}

function profileTimeAgo($datetime) {
    if (empty($datetime)) {
        return "Không rõ thời gian";
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút trước";
    if ($diff < 86400) return floor($diff / 3600) . " giờ trước";
    if ($diff < 604800) return floor($diff / 86400) . " ngày trước";

    return date("d/m/Y H:i", $timestamp);
}

function profileFormatDate($datetime) {
    if (empty($datetime)) {
        return "Chưa rõ";
    }

    return date("d/m/Y", strtotime($datetime));
}

function profileNumber($number) {
    return number_format((int) $number, 0, '.', ',');
}

$profileName = $profile['FullName'] ?: $profile['Username'];
$profileUsername = $profile['Username'] ?? '';
$profileBio = $profile['Bio'] ?: 'Người dùng chưa cập nhật bio.';
$profileAvatar = $profile['ProfilePictureUrl'] ?? '';
$profileEmail = $profile['Email'] ?? '';
$profileRole = $profile['RoleName'] ?? 'Thành viên';
$profileCreatedAt = $profile['CreatedAt'] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Hồ sơ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/profile.css">
</head>

<body class="profile-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
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

<section class="profile-section py-5">
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

                    <a href="#" class="sidebar-icon" title="Tìm kiếm">
                        <i class="bi bi-search"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="sidebar-icon" title="Đăng bài">
                        <i class="bi bi-plus-square"></i>
                    </a>

                    <a href="#" class="sidebar-icon" title="Thông báo">
                        <i class="bi bi-heart"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="sidebar-icon active" title="Hồ sơ">
                        <i class="bi bi-person"></i>
                    </a>
                </aside>
            </div>

            <div class="col-lg-3">
                <div class="bg-white p-4 profile-card text-center h-100">
                    <img
                        id="profileAvatarPreview"
                        src="<?php echo profileImagePath($profileAvatar); ?>"
                        class="profile-avatar mb-3"
                        alt="Avatar"
                    >

                    <h2 id="profileNameText" class="profile-name"><?php echo htmlspecialchars($profileName); ?></h2>
                    <p id="profileUsernameText" class="profile-username">@<?php echo htmlspecialchars($profileUsername); ?></p>

                    <p id="profileBioText" class="profile-bio">
                        <?php echo htmlspecialchars($profileBio); ?>
                    </p>

                    <div class="list-group list-group-flush text-start mt-4 profile-meta">
                        <div class="list-group-item bg-transparent px-0 d-flex gap-2">
                            <i class="bi bi-envelope text-muted"></i>
                            <span id="profileEmailText"><?php echo htmlspecialchars($profileEmail); ?></span>
                        </div>
                        <div class="list-group-item bg-transparent px-0 d-flex gap-2">
                            <i class="bi bi-person-badge text-muted"></i>
                            <span><?php echo htmlspecialchars($profileRole); ?></span>
                        </div>
                        <div class="list-group-item bg-transparent px-0 d-flex gap-2">
                            <i class="bi bi-calendar3 text-muted"></i>
                            <span>Tham gia <?php echo profileFormatDate($profileCreatedAt); ?></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
                        <button class="btn btn-pink px-4" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil-square me-1"></i>
                            Chỉnh sửa
                        </button>
                    </div>

                    <div class="row text-center mt-4 g-3">
                        <div class="col-4">
                            <div class="profile-stat-box">
                                <h5><?php echo profileNumber($stats['posts']); ?></h5>
                                <p>Bài viết</p>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="profile-stat-box">
                                <h5><?php echo profileNumber($stats['following']); ?></h5>
                                <p>Theo dõi</p>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="profile-stat-box">
                                <h5><?php echo profileNumber($stats['followers']); ?></h5>
                                <p>Follower</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="bg-light p-4 profile-intro-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h3 class="profile-section-title">Bài viết của bạn</h3>
                            <p class="text-muted mb-0">Dữ liệu lấy trực tiếp từ bảng posts, postimages, likes và comments.</p>
                        </div>

                        <span class="badge rounded-pill text-bg-light border px-3 py-2">
                            <?php echo profileNumber($stats['posts']); ?> bài viết
                        </span>
                    </div>
                </div>

                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-white post-card mb-3">
                            <div class="p-3 p-md-4">
                                <div class="d-flex gap-3">
                                    <img
                                        src="<?php echo profileImagePath($post['ProfilePictureUrl'] ?? $profileAvatar); ?>"
                                        class="avatar"
                                        alt="Avatar"
                                    >

                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                            <span class="fw-semibold">
                                                <?php echo htmlspecialchars($post['FullName'] ?: $post['Username']); ?>
                                            </span>
                                            <span class="text-muted small">@<?php echo htmlspecialchars($post['Username']); ?></span>
                                            <span class="text-muted small">• <?php echo profileTimeAgo($post['CreatedAt']); ?></span>
                                        </div>

                                        <p class="post-text mb-3">
                                            <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                                        </p>

                                        <?php if (!empty($post['Images'])): ?>
                                            <div class="d-flex flex-column gap-3 mb-3">
                                                <?php foreach (explode(',', $post['Images']) as $image): ?>
                                                    <img
                                                        src="<?php echo profileImagePath(trim($image)); ?>"
                                                        class="profile-post-image"
                                                        alt="Post image"
                                                    >
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="post-actions d-flex gap-4">
                                            <button type="button" onclick="toggleLike(this)" data-post-id="<?php echo (int) $post['PostID']; ?>">
                                                <i class="bi bi-heart"></i>
                                                <span class="like-count"><?php echo (int) ($post['LikeCount'] ?? 0); ?></span>
                                            </button>

                                            <span class="d-inline-flex align-items-center gap-2 text-muted">
                                                <i class="bi bi-chat"></i>
                                                <?php echo (int) ($post['CommentCount'] ?? 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-light border text-center py-5" role="alert">
                        <i class="bi bi-file-earmark-text fs-2 d-block mb-2 text-muted"></i>
                        Người dùng chưa có bài viết nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="updateProfileForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Chỉnh sửa hồ sơ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div id="profileUpdateAlert" class="alert d-none" role="alert"></div>

                    <div class="text-center mb-3">
                        <img
                            id="avatarModalPreview"
                            src="<?php echo profileImagePath($profileAvatar); ?>"
                            class="profile-avatar mb-3"
                            alt="Avatar preview"
                        >

                        <input
                            type="file"
                            name="avatar"
                            id="avatarInput"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <small class="text-muted d-block mt-2">Chỉ hỗ trợ jpg, jpeg, png, webp. Tối đa 5MB.</small>
                    </div>

                    <div class="mb-3">
                        <label for="fullname" class="form-label">Họ tên</label>
                        <input
                            type="text"
                            class="form-control"
                            id="fullname"
                            name="fullname"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($profile['FullName'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            maxlength="50"
                            value="<?php echo htmlspecialchars($profileUsername); ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($profileEmail); ?>"
                            required
                        >
                    </div>

                    <div class="mb-0">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea
                            class="form-control"
                            id="bio"
                            name="bio"
                            rows="4"
                            maxlength="500"
                        ><?php echo htmlspecialchars($profile['Bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn profile-outline-btn px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-pink px-4">
                        <i class="bi bi-check2 me-1"></i>
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.PROFILE_UPDATE_URL = "<?php echo BASE_URL; ?>App/Controllers/ProfileController.php?action=update";
</script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js"></script>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/profile.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
