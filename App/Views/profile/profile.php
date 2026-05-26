<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Config/Database.php';

require_once __DIR__ . '/../../Controllers/ProfileController.php';
require_once __DIR__ . '/../post/partials/post-menu.php';

use App\Controllers\ProfileController;

$profileController = new ProfileController();
$profileData = $profileController->index();

$profile = $profileData['profile'];
$posts = $profileData['posts'];
$stats = $profileData['stats'];
$currentUserId = $profileData['currentUserId'];
$profileUserId = $profileData['profileUserId'];
$isOwnProfile = $profileData['isOwnProfile'];
$profileNotFound = $profileData['notFound'];
$isFollowingProfile = $profileData['isFollowingProfile'] ?? false;
$followingUsers = $profileData['followingUsers'] ?? [];
$followerUsers = $profileData['followerUsers'] ?? [];

function profileImagePath($path) {
    $path = trim((string) $path);

    if (empty($path)) {
        return BASE_URL . "Public/assets/img/default-avatar.jpg";
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $path = ltrim($path, "/");

    if (str_starts_with($path, "Public/")) {
        return BASE_URL . $path;
    }

    if (str_starts_with($path, "uploads/") || str_starts_with($path, "assets/")) {
        return BASE_URL . "Public/" . $path;
    }

    return BASE_URL . $path;
}

function profilePostMediaPath($path) {
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $cleanPath = ltrim($path, "/");
    $extension = strtolower(pathinfo(parse_url($cleanPath, PHP_URL_PATH) ?: $cleanPath, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'mp4', 'mov', 'webm'], true)) {
        return '';
    }

    if (str_starts_with($cleanPath, "Public/")) {
        $localPath = __DIR__ . '/../../../' . $cleanPath;
        return is_file($localPath) ? BASE_URL . $cleanPath : '';
    }

    if (str_starts_with($cleanPath, "uploads/") || str_starts_with($cleanPath, "assets/")) {
        $localPath = __DIR__ . '/../../../Public/' . $cleanPath;
        return is_file($localPath) ? BASE_URL . "Public/" . $cleanPath : '';
    }

    $localPath = __DIR__ . '/../../' . $cleanPath;
    return is_file($localPath) ? BASE_URL . $cleanPath : '';
}

function profilePostMediaType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    if (in_array($extension, ['mp4', 'mov', 'webm'], true)) {
        return 'video';
    }

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return 'image';
    }

    if (in_array($extension, ['heic', 'heif'], true)) {
        return 'unsupported-image';
    }

    return 'file';
}

function profilePostMediaMimeType($path) {
    $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

    return match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        default => 'application/octet-stream'
    };
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

function profileUrl($userId) {
    return BASE_URL . "App/Views/profile/profile.php?id=" . urlencode((string) $userId);
}

function profileHashtagUrl($tag) {
    return BASE_URL . "App/Views/hashtags/hashtag.php?tag=" . urlencode((string) $tag);
}

function renderProfilePostContentWithHashtags($content) {
    $parts = preg_split('/(#[\p{L}\p{N}_]+)/u', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';

    foreach ($parts as $part) {
        if (preg_match('/^#([\p{L}\p{N}_]+)$/u', $part, $matches)) {
            $tag = $matches[1];
            $html .= '<a class="hashtag-link" href="' . htmlspecialchars(profileHashtagUrl($tag), ENT_QUOTES, 'UTF-8') . '">#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
            continue;
        }

        $html .= nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8'));
    }

    return $html;
}

$profileName = $profile ? ($profile['FullName'] ?: $profile['Username']) : '';
$profileUsername = $profile['Username'] ?? '';
$profileBio = !empty($profile['Bio']) ? $profile['Bio'] : 'Người dùng chưa cập nhật bio.';
$profileAvatar = $profile['ProfilePictureUrl'] ?? '';
$profileEmail = $profile['Email'] ?? '';
$profileRole = $profile['RoleName'] ?? 'Thành viên';
$profileCreatedAt = $profile['CreatedAt'] ?? null;
$profilePhotoItems = [];

foreach ($posts as $post) {
    if (empty($post['Images'])) {
        continue;
    }

    foreach (explode(',', $post['Images']) as $image) {
        $photoSrc = profilePostMediaPath($image);

        if ($photoSrc === '' || profilePostMediaType($image) !== 'image') {
            continue;
        }

        $profilePhotoItems[$photoSrc] = [
            'src' => $photoSrc,
            'alt' => 'Ảnh từ bài viết của ' . $profileName
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Hồ sơ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php include __DIR__ . '/../partials/fonts.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/profile.css">
</head>

<body class="profile-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">
            <div class="col-4 d-flex align-items-center">
                <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="brand-logo text-decoration-none">ARCHIVE</a>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/post/feed.php" class="header-search-btn" title="Trang chủ">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile/profile.php" class="header-login-btn" title="Hồ sơ">
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
                <?php $activePage = 'profile'; include __DIR__ . '/../post/partials/sidebar.php'; ?>
            </div>

            <?php if ($profileNotFound): ?>
                <div class="col-lg-11">
                    <div class="alert alert-light border text-center py-5" role="alert">
                        <i class="bi bi-person-x fs-2 d-block mb-2 text-muted"></i>
                        Không tìm thấy người dùng.
                    </div>
                </div>
            <?php else: ?>
            <div class="col-lg-3">
                <div class="bg-white p-4 profile-card text-center h-100">
                    <img
                        id="profileAvatarPreview"
                        src="<?php echo htmlspecialchars(profileImagePath($profileAvatar), ENT_QUOTES, 'UTF-8'); ?>"
                        class="profile-avatar mb-3"
                        alt="Avatar"
                        onerror="this.src='<?php echo BASE_URL; ?>Public/assets/img/default-avatar.jpg';"
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

                    <?php if ($isOwnProfile): ?>
                        <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
                            <button class="btn btn-pink px-4" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil-square me-1"></i>
                                Chỉnh sửa
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center gap-2 mt-4">
                            <button
                                type="button"
                                id="profileFollowButton"
                                class="btn <?php echo $isFollowingProfile ? 'profile-unfollow-btn' : 'btn-pink profile-follow-btn'; ?> px-4"
                                data-user-id="<?php echo (int) $profileUserId; ?>"
                                data-is-following="<?php echo $isFollowingProfile ? '1' : '0'; ?>"
                            >
                                <i class="bi <?php echo $isFollowingProfile ? 'bi-person-check-fill' : 'bi-person-plus'; ?> me-1"></i>
                                <span><?php echo $isFollowingProfile ? 'Đã theo dõi' : 'Theo dõi'; ?></span>
                            </button>
                            <div id="profileFollowAlert" class="profile-follow-alert" role="status" aria-live="polite"></div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-stats-grid mt-4">
                        <div class="profile-stat-cell">
                            <div class="profile-stat-box">
                                <h5><?php echo profileNumber($stats['posts']); ?></h5>
                                <p>Bài viết</p>
                            </div>
                        </div>

                        <div class="profile-stat-cell">
                            <button
                                type="button"
                                class="profile-stat-box profile-stat-action"
                                data-bs-toggle="modal"
                                data-bs-target="#followingModal"
                            >
                                <h5><?php echo profileNumber($stats['following']); ?></h5>
                                <p>Đang theo dõi</p>
                            </button>
                        </div>

                        <div class="profile-stat-cell">
                            <button
                                type="button"
                                class="profile-stat-box profile-stat-action"
                                data-bs-toggle="modal"
                                data-bs-target="#followersModal"
                            >
                                <h5 id="profileFollowerCount"><?php echo profileNumber($stats['followers']); ?></h5>
                                <p>Người theo dõi</p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-content-tabs mb-3" role="tablist" aria-label="Nội dung hồ sơ">
                    <button
                        type="button"
                        class="profile-content-tab active"
                        id="profile-posts-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#profile-posts-pane"
                        role="tab"
                        aria-controls="profile-posts-pane"
                        aria-selected="true"
                    >
                        <i class="bi bi-grid-3x3-gap me-1"></i>
                        Bài viết
                    </button>
                    <button
                        type="button"
                        class="profile-content-tab"
                        id="profile-photos-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#profile-photos-pane"
                        role="tab"
                        aria-controls="profile-photos-pane"
                        aria-selected="false"
                    >
                        <i class="bi bi-images me-1"></i>
                        Ảnh
                    </button>
                    <button
                        type="button"
                        class="profile-content-tab"
                        id="profile-reposts-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#profile-reposts-pane"
                        role="tab"
                        aria-controls="profile-reposts-pane"
                        aria-selected="false"
                    >
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Đã đăng lại
                    </button>
                </div>

                <div class="tab-content profile-tab-content">
                    <div
                        class="tab-pane fade show active"
                        id="profile-posts-pane"
                        role="tabpanel"
                        aria-labelledby="profile-posts-tab"
                        tabindex="0"
                    >
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-white post-card profile-post-card mb-3" id="post-<?php echo (int) $post['PostID']; ?>" <?php echo archivePostCardAttributes($post, (int) $currentUserId); ?>>
                            <div class="profile-post-body">
                                <div class="d-flex gap-3">
                                    <img
                                        src="<?php echo profileImagePath($post['ProfilePictureUrl'] ?? $profileAvatar); ?>"
                                        class="avatar profile-post-avatar"
                                        alt="Avatar"
                                    >

                                    <div class="flex-grow-1">
                                        <div class="post-card-header profile-post-header mb-2">
                                            <div class="profile-post-meta">
                                                <span class="profile-post-author">
                                                    <?php echo htmlspecialchars($post['FullName'] ?: $post['Username']); ?>
                                                </span>
                                                <span class="profile-post-username">@<?php echo htmlspecialchars($post['Username']); ?></span>
                                                <span class="profile-post-time">• <?php echo profileTimeAgo($post['CreatedAt']); ?></span>
                                            </div>

                                            <?php archiveRenderPostMenu($post, (int) $currentUserId); ?>
                                        </div>

                                        <p class="post-text profile-post-content mb-3">
                                            <?php echo renderProfilePostContentWithHashtags($post['Content']); ?>
                                        </p>

                                        <?php if (!empty($post['Images'])): ?>
                                            <div class="d-flex flex-column gap-3 mb-3">
                                                <?php foreach (explode(',', $post['Images']) as $image): ?>
                                                    <?php $profilePostMediaSrc = profilePostMediaPath($image); ?>
                                                    <?php if ($profilePostMediaSrc !== ''): ?>
                                                        <?php $profilePostMediaType = profilePostMediaType($image); ?>
                                                        <?php if ($profilePostMediaType === 'video'): ?>
                                                            <video controls class="profile-post-image">
                                                                <source src="<?php echo htmlspecialchars($profilePostMediaSrc, ENT_QUOTES, 'UTF-8'); ?>" type="<?php echo htmlspecialchars(profilePostMediaMimeType($image), ENT_QUOTES, 'UTF-8'); ?>">
                                                                Trình duyệt không hỗ trợ video này.
                                                            </video>
                                                            <a href="<?php echo htmlspecialchars($profilePostMediaSrc, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">Mở file video</a>
                                                        <?php elseif ($profilePostMediaType === 'image'): ?>
                                                            <img
                                                                src="<?php echo htmlspecialchars($profilePostMediaSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                                                class="profile-post-image"
                                                                alt="Post image"
                                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                            >
                                                            <a href="<?php echo htmlspecialchars($profilePostMediaSrc, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small" style="display:none;">Mở file ảnh</a>
                                                        <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($profilePostMediaSrc, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">Mở file ảnh</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="post-actions profile-post-actions d-flex gap-4">
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
                    <div class="bg-white profile-empty-state" role="status">
                        <div class="profile-empty-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h4><?php echo $isOwnProfile ? 'Bạn chưa có bài viết nào.' : 'Chưa có bài viết nào.'; ?></h4>
                        <p><?php echo $isOwnProfile ? 'Hãy chia sẻ điều đầu tiên của bạn.' : 'Người dùng này chưa chia sẻ nội dung.'; ?></p>
                    </div>
                <?php endif; ?>
                    </div>

                    <div
                        class="tab-pane fade"
                        id="profile-photos-pane"
                        role="tabpanel"
                        aria-labelledby="profile-photos-tab"
                        tabindex="0"
                    >
                        <?php if (!empty($profilePhotoItems)): ?>
                            <div class="profile-photo-grid">
                                <?php foreach ($profilePhotoItems as $photo): ?>
                                    <a href="<?php echo htmlspecialchars($photo['src'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="profile-photo-item">
                                        <img
                                            src="<?php echo htmlspecialchars($photo['src'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($photo['alt'], ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="lazy"
                                        >
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-white profile-empty-state" role="status">
                                <div class="profile-empty-icon">
                                    <i class="bi bi-images"></i>
                                </div>
                                <h4>Chưa có ảnh để hiển thị.</h4>
                                <p><?php echo $isOwnProfile ? 'Hãy đăng thêm ảnh.' : 'Người dùng này chưa có ảnh để hiển thị.'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div
                        class="tab-pane fade"
                        id="profile-reposts-pane"
                        role="tabpanel"
                        aria-labelledby="profile-reposts-tab"
                        tabindex="0"
                    >
                        <div class="bg-white profile-empty-state" role="status">
                            <div class="profile-empty-icon">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <h4>Chưa có bài đăng lại.</h4>
                            <p>Tính năng đăng lại sẽ được hoàn thiện sau.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!$profileNotFound): ?>
<div class="modal fade follow-modal" id="followingModal" tabindex="-1" aria-labelledby="followingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followingModalLabel">Đang theo dõi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <?php if (!empty($followingUsers)): ?>
                    <div class="follow-user-list">
                        <?php foreach ($followingUsers as $user): ?>
                            <?php
                                $followUserName = $user['FullName'] ?: $user['Username'];
                                $followUserSecondary = !empty($user['Username']) ? '@' . $user['Username'] : ($user['Email'] ?? '');
                            ?>
                            <div class="follow-user-item">
                                <img
                                    src="<?php echo htmlspecialchars(profileImagePath($user['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    class="follow-user-avatar"
                                    alt="Avatar"
                                    onerror="this.src='<?php echo BASE_URL; ?>Public/assets/img/default-avatar.jpg';"
                                >

                                <div class="follow-user-info">
                                    <div class="follow-user-name"><?php echo htmlspecialchars($followUserName, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ($followUserSecondary !== ''): ?>
                                        <div class="follow-user-meta"><?php echo htmlspecialchars($followUserSecondary, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <a
                                    href="<?php echo htmlspecialchars(profileUrl($user['UserID']), ENT_QUOTES, 'UTF-8'); ?>"
                                    class="btn btn-sm profile-outline-btn follow-profile-link"
                                >
                                    Xem hồ sơ
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="follow-empty-message">Chưa theo dõi ai.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade follow-modal" id="followersModal" tabindex="-1" aria-labelledby="followersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followersModalLabel">Người theo dõi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <?php if (!empty($followerUsers)): ?>
                    <div class="follow-user-list">
                        <?php foreach ($followerUsers as $user): ?>
                            <?php
                                $followUserName = $user['FullName'] ?: $user['Username'];
                                $followUserSecondary = !empty($user['Username']) ? '@' . $user['Username'] : ($user['Email'] ?? '');
                            ?>
                            <div class="follow-user-item">
                                <img
                                    src="<?php echo htmlspecialchars(profileImagePath($user['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    class="follow-user-avatar"
                                    alt="Avatar"
                                    onerror="this.src='<?php echo BASE_URL; ?>Public/assets/img/default-avatar.jpg';"
                                >

                                <div class="follow-user-info">
                                    <div class="follow-user-name"><?php echo htmlspecialchars($followUserName, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ($followUserSecondary !== ''): ?>
                                        <div class="follow-user-meta"><?php echo htmlspecialchars($followUserSecondary, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <a
                                    href="<?php echo htmlspecialchars(profileUrl($user['UserID']), ENT_QUOTES, 'UTF-8'); ?>"
                                    class="btn btn-sm profile-outline-btn follow-profile-link"
                                >
                                    Xem hồ sơ
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="follow-empty-message">Chưa có người theo dõi.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isOwnProfile): ?>
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
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<?php if (!$profileNotFound): ?>
<script>
    <?php if ($isOwnProfile): ?>
    window.PROFILE_UPDATE_URL = "<?php echo BASE_URL; ?>App/Controllers/ProfileController.php?action=update";
    <?php else: ?>
    window.PROFILE_FOLLOW_URL = "<?php echo BASE_URL; ?>App/Controllers/FollowController.php?action=toggle";
    <?php endif; ?>
</script>
<?php endif; ?>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js"></script>
<?php if (!$profileNotFound): ?>
<script src="<?php echo BASE_URL; ?>Public/assets/JS/profile.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
