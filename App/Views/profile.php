<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Định nghĩa hằng số đường dẫn gốc hệ thống nếu chưa có để dứt điểm lỗi Fatal Error
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

// 2. NHẬN DỮ LIỆU ĐỘNG TỪ CONTROLLER TRUYỀN QUA (NẾU CÓ), NẾU CHƯA CÓ LẤY DỮ LIỆU CŨ LÀM MẶC ĐỊNH
$profileName     = $profile['FullName'] ?? $_SESSION['user_name'] ?? $profileName ?? "Thanh Tuyen";
$profileUsername = $profile['Username'] ?? $_SESSION['username'] ?? $profileUsername ?? "@thanhtuyen";
$profileBio      = $profile['Bio'] ?? $profileBio ?? "Mình thích giao diện tối giản, nhẹ nhàng và lưu lại những suy nghĩ nhỏ mỗi ngày.";
$profileAvatar   = $profile['ProfilePictureUrl'] ?? $profileAvatar ?? "https://i.pravatar.cc/140?img=12";

$totalPosts      = $stats['posts'] ?? $totalPosts ?? 24;
$totalFollowing  = $stats['following'] ?? $totalFollowing ?? 842;
$totalFollowers  = $stats['followers'] ?? $totalFollowers ?? "1.2K";

// Nếu Controller có truyền mảng bài viết thật qua thì lấy, không thì chạy mảng mẫu bên dưới
if (!isset($posts) || empty($posts)) {
    $posts = [
        [
            "name" => "Thanh Tuyen",
            "time" => "2 giờ",
            "avatar" => "https://i.pravatar.cc/60?img=12",
            "content" => "Có những ngày chỉ cần một góc nhỏ đủ yên để viết vài dòng là thấy nhẹ hơn.",
            "image" => "",
            "likes" => 18,
            "comments" => 5,
            "shares" => 2
        ],
        [
            "name" => "Thanh Tuyen",
            "time" => "1 ngày",
            "avatar" => "https://i.pravatar.cc/60?img=12",
            "content" => "Mình đang thử thiết kế một giao diện mạng xã hội tối giản.",
            "image" => "https://images.unsplash.com/photo-1516321318423-f06f85e504b3",
            "likes" => 26,
            "comments" => 8,
            "shares" => 3
        ]
    ];
}

// Hàm helper để giải quyết vấn đề link ảnh Avatar tuyệt đối động
if (!function_exists('profileImagePath')) {
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body class="profile-page">

<header class="archive-header">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center py-3">

            <div class="col-4 d-flex align-items-center">
                <div class="brand-logo">ARCHIVE</div>
            </div>

            <div class="col-4 d-flex justify-content-center">
                <div class="header-badge">
                    <i class="bi bi-stars"></i>
                </div>
            </div>

            <div class="col-4 d-flex justify-content-end">
                <div class="header-actions">
                    <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="header-search-btn">
                        <i class="bi bi-house-door"></i>
                    </a>

                    <a href="#" class="header-star-btn">
                        <i class="bi bi-star"></i>
                    </a>

                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="header-login-btn">
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

            <a href="<?php echo BASE_URL; ?>App/Views/feed.php" class="sidebar-icon">
                <i class="bi bi-house-door-fill"></i>
            </a>

            <a href="#" class="sidebar-icon">
                <i class="bi bi-search"></i>
            </a>

            <a href="#" class="sidebar-icon">
                <i class="bi bi-plus-square"></i>
            </a>

            <a href="#" class="sidebar-icon">
                <i class="bi bi-heart"></i>
            </a>

            <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="sidebar-icon active">
                <i class="bi bi-person"></i>
            </a>

            <a href="#" class="sidebar-icon mt-auto">
                <i class="bi bi-pin-angle"></i>
            </a>
        </aside>
    </div>

    <div class="col-lg-3">
        <div class="bg-white p-4 profile-card text-center h-100">

            <img src="<?php echo profileImagePath($profileAvatar); ?>" class="profile-avatar mb-3" alt="Avatar">

            <h2 class="profile-name"><?php echo htmlspecialchars($profileName); ?></h2>
            <p class="profile-username"><?php echo htmlspecialchars($profileUsername); ?></p>

            <p class="profile-bio">
                <?php echo htmlspecialchars($profileBio); ?>
            </p>

            <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                <button class="btn btn-pink px-4">Chỉnh sửa trang cá nhân</button>
            </div>

            <div class="row text-center mt-4 g-3">
                <div class="col-4">
                    <div class="profile-stat-box">
                        <h5><?php echo $totalPosts; ?></h5>
                        <p>Bài viết</p>
                    </div>
                </div>

                <div class="col-4">
                    <div class="profile-stat-box">
                        <h5><?php echo $totalFollowing; ?></h5>
                        <p>Theo dõi</p>
                    </div>
                </div>

                <div class="col-4">
                    <div class="profile-stat-box">
                        <h5><?php echo $totalFollowers; ?></h5>
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
                    <h3 class="profile-section-title">Bài viết gần đây</h3>
                    <p class="text-muted mb-0">Những điều bạn đã lưu lại.</p>
                </div>

                <div class="d-flex gap-2">
                    <button class="profile-tab active">Tất cả</button>
                    <button class="profile-tab">Ảnh</button>
                    <button class="profile-tab">Yêu thích</button>
                </div>
            </div>
        </div>

        <?php foreach ($posts as $post): ?>
            <div class="bg-white post-card mb-3">
                <div class="p-3">
                    <div class="d-flex gap-3">
                        <img src="<?php echo profileImagePath($post['avatar']); ?>" class="avatar" alt="Avatar">

                        <div>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars($post['name']); ?> • <?php echo $post['time']; ?>
                            </div>

                            <p class="post-text">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </p>

                            <?php if (!empty($post['image'])): ?>
                                <img 
                                    src="<?php echo profileImagePath($post['image']); ?>" 
                                    class="profile-post-image mt-2" 
                                    style="max-width: 100%; height: auto; max-height: 400px; object-fit: cover; border-radius: 8px;" 
                                    alt="Post image"
                                >
                            <?php endif; ?>

                        
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>