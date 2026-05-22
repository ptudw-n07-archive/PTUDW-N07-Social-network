<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Định nghĩa hằng số đường dẫn gốc hệ thống nếu chưa có
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

// 2. CHẶN LỖI: Nếu chưa đăng nhập, bắt buộc đá về trang login ngay lập tức
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "App/Views/auth/login.php");
    exit();
}

// 3. Đọc dữ liệu an toàn từ Session sau khi đã chắc chắn user đã đăng nhập
$currentUserId   = $_SESSION['user_id'];
$currentUsername = $_SESSION['username'];
$currentFullName = $_SESSION['user_name'];
$currentAvatar   = $_SESSION['ProfilePictureUrl'] ?? ''; 

require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/FollowController.php';

// KHAI BÁO SỬ DỤNG NAMESPACE ĐỂ KHÔNG BỊ VS CODE CHỬI VÀNG KHÈ KHỞI TẠO CONTROLLER
use App\Controllers\PostController;
use App\Controllers\FollowController;

$postController = new PostController();
$posts = $postController->index();

$followController = new FollowController();
$suggestedUsers = $followController->getSuggestedUsers($currentUserId);

// FIX PATH AVATAR: Sửa lại hàm xử lý đường dẫn ảnh tuyệt đối dựa trên hằng số BASE_URL
function imagePath($path) {
    if (empty($path)) {
        return BASE_URL . "Public/assets/img/default-avatar.jpg";
    }

    if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
        return $path;
    }

    $cleanPath = str_replace("Public/", "", $path);
    return BASE_URL . "Public/" . ltrim($cleanPath, "/");
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút";
    if ($diff < 86400) return floor($diff / 3600) . " giờ";

    return date("d/m/Y", $timestamp);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Feed</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
</head>

<body>

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
                    <a href="#" class="header-search-btn"><i class="bi bi-search"></i></a>
                    <a href="#" class="header-star-btn"><i class="bi bi-star"></i></a>
                    <a href="<?php echo BASE_URL; ?>App/Views/profile.php" class="header-login-btn">
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

            <div class="sidebar-logo"> <i class="bi bi-circle-square"></i> </div>

            <a 
                href="<?php echo BASE_URL; ?>App/Views/feed.php"
                class="sidebar-icon active"
                id="nav-home"
                title="Trang chủ"
            > <i class="bi bi-house-door-fill"></i> </a>

            <a 
                href="#"
                class="sidebar-icon"
                id="nav-search"
                title="Tìm kiếm"
            >
                <i class="bi bi-search"></i>
            </a>

            <a 
                href="#"
                class="sidebar-icon"
                id="nav-create-post"
                title="Đăng bài"
            >
                <i class="bi bi-plus-square"></i>
            </a>

            <a 
                href="#"
                class="sidebar-icon"
                id="nav-notifications"
                title="Thông báo"
            >
                <i class="bi bi-heart"></i>
            </a>

            <a 
                href="<?php echo BASE_URL; ?>App/Views/profile.php"
                class="sidebar-icon"
                id="nav-profile"
                title="Hồ sơ"
            >
                <i class="bi bi-person"></i>
            </a>

        </aside>

    </div>

            <div class="col-lg-7 col-md-8">
                <div class="feed-title text-center mb-4">Bảng tin</div>

                <form id="postForm" class="bg-white p-3 p-md-4 mb-4 post-composer" enctype="multipart/form-data">
                    <div class="d-flex gap-3">
                       <img src="<?= imagePath($currentAvatar) ?>" class="avatar" alt="avatar">
                        <div class="flex-grow-1">
                            <textarea 
                                name="content"
                                class="form-control composer-input" 
                                rows="3"
                                placeholder="Bạn đang nghĩ gì?"
                            ></textarea>

                            <div class="mt-3">

                                <label for="postImages" class="custom-upload-btn">
                                    <i class="bi bi-image"></i>
                                    <span>Thêm ảnh</span>
                                </label>

                                <input 
                                    type="file" 
                                    name="images[]" 
                                    id="postImages"
                                    accept="image/*"
                                    multiple
                                    hidden
                                >

                                <div id="preview-container" class="preview-container mt-3"></div>

                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-pink px-4" onclick="createPost()">Đăng</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="posts-list">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <?php $comments = $postController->getComments($post['PostID']); ?>
                            <div class="bg-white post-card mb-3">
                                <div class="p-3">
                                    <div class="d-flex gap-3">
                                        <img 
                                            src="<?= imagePath($post['ProfilePictureUrl'] ?? '') ?>" 
                                            class="avatar" 
                                            alt="avatar"
                                        >

                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($post['FullName'] ?: $post['Username']) ?>
                                                • <?= timeAgo($post['CreatedAt']) ?>
                                            </div>

                                            <p class="post-text">
                                                <?= nl2br(htmlspecialchars($post['Content'])) ?>
                                            </p>

                                            <?php if (!empty($post['Images'])): ?>
                                                <?php $images = explode(',', $post['Images']); ?>
                                                <?php foreach ($images as $img): ?>
                                                    <img 
                                                        src="<?= imagePath(trim($img)) ?>" 
                                                        class="img-fluid rounded-4 mb-3"
                                                        style="max-height: 450px; object-fit: cover;"
                                                        alt="post image"
                                                    >
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="post-actions d-flex gap-4">
                                                <button onclick="toggleLike(this)" data-post-id="<?= $post['PostID'] ?>">
                                                    <i class="bi bi-heart"></i>
                                                    <span class="like-count"><?= $post['LikeCount'] ?? 0 ?></span>
                                                </button>

                                                 <button onclick="toggleCommentBox(this)">
                                                    <i class="bi bi-chat"></i>
                                                    <span class="comment-count">
                                                        <?= $post['CommentCount'] ?? 0 ?>
                                                    </span>
                                                </button>

                                                <button>
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </div>
                                            <div class="comment-box mt-3 d-none">
                                            <div class="d-flex gap-2">
                                                <input 
                                                    type="text" 
                                                    class="form-control comment-input" 
                                                    placeholder="Viết bình luận..."
                                                >

                                                <button 
                                                type="button"
                                                    class="btn btn-pink"
                                                    onclick="sendComment(this)"
                                                    data-post-id="<?= $post['PostID'] ?>"
                                                >
                                                    Gửi
                                                </button>
                                            </div>

                                                <div class="comment-list mt-2">
    <?php if (!empty($comments)): ?>
        <?php foreach ($comments as $comment): ?>
            <div class="small mt-2">
                <strong>
                    <?= htmlspecialchars($comment['FullName'] ?: $comment['Username']) ?>
                </strong>:
                <?= htmlspecialchars($comment['Content']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white post-card mb-3">
                            <div class="p-3 text-center text-muted">
                                Hiện chưa có bài viết nào trong database.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 col-md-4">
                <div class="bg-light login-card p-4 mb-4">
                    <h2 class="login-card-title text-center mb-3">Gợi ý theo dõi</h2>

                    <div class="d-flex flex-column gap-3">
                        <?php if (!empty($suggestedUsers)): ?>
                            <?php foreach ($suggestedUsers as $user): ?>
                                <div class="d-flex align-items-center justify-content-between follower-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <img 
                                            src="<?= imagePath($user['ProfilePictureUrl'] ?? '') ?>" 
                                            class="avatar" 
                                            alt="avatar"
                                        >

                                        <div>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($user['FullName'] ?: $user['Username']) ?>
                                            </div>

                                            <small class="text-muted">
                                                @<?= htmlspecialchars($user['Username']) ?>
                                            </small>
                                        </div>
                                    </div>

                                    <button 
                                        type="button"
                                        class="btn btn-sm <?= $user['IsFollowing'] ? 'btn-secondary' : 'btn-pink' ?>"
                                        onclick="toggleFollow(this)"
                                        data-user-id="<?= $user['UserID'] ?>"
                                    >
                                        <?= $user['IsFollowing'] ? 'Đang theo dõi' : 'Theo dõi' ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">
                                Chưa có người dùng nào để gợi ý.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-4 post-card">
                    <h5>Chủ đề nổi bật</h5>
                    <p>#MoodToday</p>
                    <p>#DailyThought</p>
                    <p>#MinimalUI</p>
                </div>
            </div>

        </div>
    </div>
</section>

<script src="<?php echo BASE_URL; ?>Public/assets/JS/feed.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>