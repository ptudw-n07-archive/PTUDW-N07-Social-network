<?php
namespace App\Controllers; // ✨ 1. Thêm namespace cho Controller

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/PostModel.php';     
require_once __DIR__ . '/../Models/NotificationModel.php';

// ✨ 2. Khai báo sử dụng lớp PostModel từ bên thư mục Models và lớp Database từ gốc
use App\Models\PostModel;
use App\Models\NotificationModel;
use Database;

class PostController {
    private $postModel;
    private $notificationModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->postModel = new PostModel($db);
        $this->notificationModel = new NotificationModel($db);
    }

    public function index() {
        return $this->postModel->getAllPosts($_SESSION['user_id'] ?? null);
    }

    public function getCurrentUser($userId = null): array {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);

        if (!$userId) {
            return [];
        }

        $user = $this->postModel->getUserById((int) $userId);
        return is_array($user) ? $user : [];
    }

    public function getFeedPosts($viewerId = null): array {
        $viewerId = $viewerId ?? ($_SESSION['user_id'] ?? null);
        $posts = $this->postModel->getAllPosts($viewerId);
        $commentsByPostId = $this->postModel->getCommentsByPostIds(array_column($posts, 'PostID'));

        foreach ($posts as &$post) {
            $postId = (int) ($post['PostID'] ?? 0);
            $post['Comments'] = $commentsByPostId[$postId] ?? [];
        }
        unset($post);

        return $posts;
    }

    public function create() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode(["success" => false, "message" => "Bạn chưa đăng nhập."]);
            return;
        }

        $content = trim($_POST['content'] ?? '');
        $validImages = [];
        $uploadErrors = [];

        if (!empty($_FILES['images']['name'][0])) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'mp4', 'mov', 'webm'];
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/heic',
                'image/heif',
                'image/heic-sequence',
                'image/heif-sequence',
                'video/mp4',
                'video/quicktime',
                'video/webm',
                'application/octet-stream'
            ];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                    $uploadErrors[] = "Upload ảnh thất bại.";
                    continue;
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    $uploadErrors[] = "Chỉ cho phép jpg, jpeg, png, webp, gif, heic, heif, mp4, mov hoặc webm.";
                    continue;
                }

                $mimeType = $finfo->file($_FILES['images']['tmp_name'][$key]);
                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    $uploadErrors[] = "File upload không đúng định dạng ảnh/video.";
                    continue;
                }

                $validImages[] = [
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'extension' => $extension
                ];
            }
        }

        if ($content === '' && empty($validImages)) {
            echo json_encode(["success" => false, "message" => "Nội dung không được để trống."]);
            return;
        }

        // 1. Tạo bài viết trong Database lấy ra PostID mới nhất
        $postId = $this->postModel->createPost($userId, $content);
        $hashtags = $this->extractHashtags($content);
        
        // Mảng dùng để lưu các đường dẫn ảnh gửi về cho Front-end render tại chỗ
        $savedImages = [];

        if ($postId && !empty($validImages)) {
            $uploadDir = app_path('Public/uploads/posts/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($validImages as $image) {
                $saveResult = $this->saveUploadedPostMedia($image['tmp_name'], $image['extension'], $uploadDir);

                if (!$saveResult['success']) {
                    $uploadErrors[] = $saveResult['message'];
                    continue;
                }

                $dbPath = 'Public/uploads/posts/' . $saveResult['fileName'];
                $this->postModel->addPostImage($postId, $dbPath);
                $savedImages[] = $dbPath;

                if (!empty($saveResult['message'])) {
                    $uploadErrors[] = $saveResult['message'];
                }
            }
        }

        if ($postId && !empty($hashtags)) {
            $this->postModel->syncPostHashtags($postId, $hashtags);
        }

        if ($postId) {
            // 🎯 PHẢN HỒI JSON CHUẨN AJAX: Gửi toàn bộ thông tin bài viết mới tạo về cho JavaScript vẽ giao diện
            echo json_encode([
                "success" => true,
                "post" => [
                    "PostID" => $postId,
                    "Content" => $content,
                    "CreatedAt" => "Vừa xong",
                    "UserID" => $userId,
                    "Username" => $_SESSION['username'] ?? '',
                    "FullName" => $_SESSION['user_name'] ?? '',
                    "ProfilePictureUrl" => $_SESSION['avatar'] ?? $_SESSION['ProfilePictureUrl'] ?? $_SESSION['user_avatar'] ?? 'Public/assets/img/default-avatar.jpg',
                    "Images" => $savedImages,
                    "Hashtags" => $hashtags,
                    "LikeCount" => 0,
                    "CommentCount" => 0,
                    "Privacy" => "public"
                ],
                "uploadErrors" => $uploadErrors
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Không thể tạo bài viết."]);
        }
    }

    public function like() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $postId = filter_var($_POST['postId'] ?? $_POST['post_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

        if (!$postId) {
            echo json_encode([
                "success" => false,
                "message" => "Thiếu PostID."
            ]);
            return;
        }

        $status = $this->postModel->toggleLike($userId, $postId);
        $likeCount = $this->postModel->countLikes($postId);

        if ($status === "liked") {
            $receiverUserId = $this->postModel->getPostOwnerId($postId);

            if ($receiverUserId && (int) $receiverUserId !== (int) $userId) {
                $this->notificationModel->createNotification(1, $receiverUserId, $userId, $postId);
            }
        }

        echo json_encode([
            "success" => true,
            "status" => $status,
            "liked" => $status === "liked",
            "isLiked" => $status === "liked",
            "likeCount" => (int) $likeCount,
            "message" => $status === "liked" ? "Đã thích bài viết." : "Đã bỏ thích bài viết."
        ], JSON_UNESCAPED_UNICODE);
    }

    public function repost() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $postId = filter_var($_POST['postId'] ?? $_POST['post_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            $this->json(false, "Bạn chưa đăng nhập.");
            return;
        }

        if (!$postId) {
            $this->json(false, "Thiếu thông tin bài viết.");
            return;
        }

        $newPostId = $this->postModel->createRepost($userId, (int) $postId);

        if (!$newPostId) {
            $this->json(false, "Không thể đăng lại bài viết này.");
            return;
        }

        $post = $this->postModel->getPostById((int) $newPostId, $userId);
        if ($post && isset($post['Images']) && !is_array($post['Images'])) {
            $post['Images'] = array_values(array_filter(array_map('trim', explode(',', (string) $post['Images']))));
        }

        $this->json(true, "Đã đăng lại bài viết.", [
            "post" => $post
        ]);
    }

    public function comment() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $postId = filter_var($_POST['postId'] ?? $_POST['post_id'] ?? $_GET['post_id'] ?? null, FILTER_VALIDATE_INT);
        $content = trim($_POST['content'] ?? $_POST['comment'] ?? '');
        $parentCommentId = filter_var($_POST['parentCommentId'] ?? $_POST['parent_comment_id'] ?? null, FILTER_VALIDATE_INT);
        $parentCommentId = $parentCommentId ?: null;

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

        if (!$postId || $content === '') {
            echo json_encode([
                "success" => false,
                "message" => "Thiếu nội dung bình luận."
            ]);
            return;
        }

        $commentId = $this->postModel->createComment($userId, $postId, $content, $parentCommentId);

        if ($commentId) {
            $receiverUserId = $this->postModel->getPostOwnerId($postId);

            if ($receiverUserId && (int) $receiverUserId !== (int) $userId) {
                $this->notificationModel->createNotification(2, $receiverUserId, $userId, $postId, $commentId);
            }
        }

        echo json_encode([
            "success" => (bool) $commentId,
            "comment" => $this->formatCommentForResponse($this->postModel->getCommentById((int) $commentId), (int) $userId)
        ], JSON_UNESCAPED_UNICODE);
    }

    public function getComments($postId) {
        return $this->postModel->getCommentsByPostId($postId);
    }

    public function updateComment() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $commentId = filter_var($_POST['commentId'] ?? $_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
        $content = trim($_POST['content'] ?? '');

        if (!$userId || !$commentId || $content === '') {
            $this->json(false, "Thiếu nội dung bình luận.");
            return;
        }

        $success = $this->postModel->updateComment((int) $commentId, $userId, $content);
        $comment = $success ? $this->postModel->getCommentById((int) $commentId) : null;

        $this->json($success, $success ? "Đã cập nhật bình luận." : "Bạn không có quyền sửa bình luận này.", [
            "comment" => $this->formatCommentForResponse($comment, $userId)
        ]);
    }

    public function deleteComment() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $commentId = filter_var($_POST['commentId'] ?? $_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId || !$commentId) {
            $this->json(false, "Thiếu thông tin bình luận.");
            return;
        }

        $success = $this->postModel->hideComment((int) $commentId, $userId);
        $this->json($success, $success ? "Đã xóa bình luận." : "Bạn không có quyền xóa bình luận này.", [
            "commentId" => (int) $commentId
        ]);
    }

    public function reportComment() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $reporterId = (int) ($_SESSION['user_id'] ?? 0);
        $commentId = filter_var($_POST['commentId'] ?? $_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim($_POST['reason'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if (!$reporterId || !$commentId || $reason === '') {
            $this->json(false, "Thiếu thông tin báo cáo.");
            return;
        }

        $success = $this->postModel->createCommentReport($reporterId, (int) $commentId, $reason, $details);
        $this->json($success, $success ? "Đã gửi báo cáo bình luận." : "Không thể báo cáo bình luận này.");
    }

    public function detail($postId, $viewerId = null) {
        return $this->postModel->getPostById($postId, $viewerId);
    }

    public function getTrendingHashtags($limit = 10) {
        return $this->postModel->getTrendingHashtags($limit);
    }

    public function trendingHashtags() {
        header('Content-Type: application/json; charset=utf-8');

        $limit = (int) ($_GET['limit'] ?? 10);
        if ($limit < 1) {
            $limit = 10;
        }

        $limit = min($limit, 20);
        $hashtags = array_map(function ($hashtag) {
            return [
                "tag" => $hashtag['HashtagName'] ?? '',
                "post_count" => (int) ($hashtag['TotalPosts'] ?? 0)
            ];
        }, $this->getTrendingHashtags($limit));

        echo json_encode([
            "success" => true,
            "hashtags" => $hashtags
        ], JSON_UNESCAPED_UNICODE);
    }

    public function getPostsByHashtag($tag) {
        return $this->postModel->getPostsByHashtag($tag, $_SESSION['user_id'] ?? null);
    }

    public function updatePost() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            $postId = (int) ($_POST['postId'] ?? 0);
            $content = (string) ($_POST['content'] ?? '');
            $removeImages = $_POST['removeImages'] ?? [];

            if (!$userId || !$postId) {
                $this->json(false, "Thiếu thông tin bài viết.");
                return;
            }

            if ($this->postModel->getPostOwnerId($postId) !== (int) $userId) {
                $this->json(false, "Bạn không có quyền chỉnh sửa bài viết này.");
                return;
            }

            if (is_string($removeImages)) {
                $decodedImages = json_decode($removeImages, true);
                $removeImages = is_array($decodedImages) ? $decodedImages : [];
            }

            $validImages = [];
            $uploadErrors = [];

            if (!empty($_FILES['images']['name'][0])) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'mp4', 'mov', 'webm'];
                $allowedMimeTypes = [
                    'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif',
                    'image/heic-sequence', 'image/heif-sequence', 'video/mp4', 'video/quicktime',
                    'video/webm', 'application/octet-stream'
                ];
                $finfo = new \finfo(FILEINFO_MIME_TYPE);

                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                        $uploadErrors[] = "Upload ảnh thất bại.";
                        continue;
                    }

                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $mimeType = $finfo->file($_FILES['images']['tmp_name'][$key]);

                    if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
                        $uploadErrors[] = "File upload không đúng định dạng ảnh/video.";
                        continue;
                    }

                    $validImages[] = [
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'extension' => $extension
                    ];
                }
            }

            if (trim($content) === '' && empty($validImages)) {
                $this->json(false, "Nội dung không được để trống nếu không thêm ảnh.");
                return;
            }

            $updated = $this->postModel->updatePostContent($postId, $userId, $content);
            if (!$updated) {
                $this->json(false, "Không thể cập nhật bài viết.");
                return;
            }

            $this->postModel->removePostImages($postId, array_map('strval', $removeImages));

            $savedImages = [];
            if (!empty($validImages)) {
                $uploadDir = app_path('Public/uploads/posts/');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($validImages as $image) {
                    $saveResult = $this->saveUploadedPostMedia($image['tmp_name'], $image['extension'], $uploadDir);
                    if (!$saveResult['success']) {
                        $uploadErrors[] = $saveResult['message'];
                        continue;
                    }

                    $dbPath = 'Public/uploads/posts/' . $saveResult['fileName'];
                    $this->postModel->addPostImage($postId, $dbPath);
                    $savedImages[] = $dbPath;
                }
            }

            $this->postModel->replacePostHashtags($postId, $this->extractHashtags($content));
            $post = $this->postModel->getPostById($postId, $userId);

            $this->json(true, "Đã cập nhật bài viết.", [
                "post" => $post,
                "data" => [
                    "post" => $post,
                    "uploadErrors" => $uploadErrors,
                    "savedImages" => $savedImages
                ],
                "uploadErrors" => $uploadErrors,
                "savedImages" => $savedImages
            ]);
        } catch (\Throwable $e) {
            $this->json(false, "Không thể cập nhật bài viết. Vui lòng thử lại.");
        }
    }

    public function deletePost() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $postId = (int) ($_POST['postId'] ?? 0);

        if (!$userId || !$postId) {
            $this->json(false, "Thiếu thông tin bài viết.");
            return;
        }

        $this->json($this->postModel->deletePost($postId, $userId), "Đã xóa bài viết.");
    }

    public function updatePostPrivacy() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $postId = (int) ($_POST['postId'] ?? 0);
        $privacy = $_POST['privacy'] ?? 'public';

        if (!$userId || !$postId) {
            $this->json(false, "Thiếu thông tin bài viết.");
            return;
        }

        $success = $this->postModel->updatePostPrivacy($postId, $userId, $privacy);
        $this->json($success, $success ? "Đã cập nhật quyền riêng tư." : "Không thể cập nhật quyền riêng tư.", [
            "data" => ["privacy" => $privacy]
        ]);
    }

    public function createReport() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $reporterId = (int) ($_SESSION['user_id'] ?? 0);
        $postId = filter_var($_POST['postId'] ?? $_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim($_POST['reason'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if (!$reporterId || !$postId || $reason === '') {
            $this->json(false, "Thiếu thông tin báo cáo.");
            return;
        }

        if ($details === '') {
            $details = $reason;
        }

        $success = $this->postModel->createReport($reporterId, (int) $postId, $reason, $details);
        $this->json($success, $success ? "Đã gửi báo cáo." : "Không thể gửi báo cáo.");
    }

    public function blockUser() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;
        $blockedUserId = (int) ($_POST['userId'] ?? 0);

        if (!$userId || !$blockedUserId) {
            $this->json(false, "Thiếu thông tin người dùng.");
            return;
        }

        $success = $this->postModel->blockUser((int) $userId, $blockedUserId);
        $this->json($success, $success ? "Đã chặn người dùng." : "Không thể chặn người dùng.");
    }

    public function markNotInterested() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;
        $postId = (int) ($_POST['postId'] ?? 0);

        if (!$userId || !$postId) {
            $this->json(false, "Thiếu thông tin bài viết.");
            return;
        }

        $success = $this->postModel->markNotInterested((int) $userId, $postId);
        $this->json($success, $success ? "Đã ẩn bài viết." : "Không thể ẩn bài viết.");
    }

    private function extractHashtags(string $content): array {
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $content, $matches);
        $hashtags = [];

        foreach ($matches[1] ?? [] as $tag) {
            $tag = trim($tag);

            if ($tag === '') {
                continue;
            }

            if (function_exists('mb_substr')) {
                $tag = mb_substr($tag, 0, 80);
                $key = mb_strtolower($tag);
            } else {
                $tag = substr($tag, 0, 80);
                $key = strtolower($tag);
            }

            if (!isset($hashtags[$key])) {
                $hashtags[$key] = $tag;
            }
        }

        return array_values($hashtags);
    }

    private function saveUploadedPostMedia(string $tmpName, string $extension, string $uploadDir): array {
        $extension = strtolower($extension);

        if (in_array($extension, ['heic', 'heif'], true)) {
            return $this->saveHeicAsJpegWhenSupported($tmpName, $extension, $uploadDir);
        }

        $fileName = uniqid('post_', true) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;

        if (!move_uploaded_file($tmpName, $targetFile)) {
            return [
                'success' => false,
                'fileName' => null,
                'message' => 'Không thể lưu file lên server.'
            ];
        }

        return [
            'success' => true,
            'fileName' => $fileName,
            'message' => ''
        ];
    }

    private function saveHeicAsJpegWhenSupported(string $tmpName, string $extension, string $uploadDir): array {
        $originalName = uniqid('post_', true) . '.' . $extension;
        $originalPath = $uploadDir . $originalName;

        if (!move_uploaded_file($tmpName, $originalPath)) {
            return [
                'success' => false,
                'fileName' => null,
                'message' => 'Không thể lưu file HEIC/HEIF lên server.'
            ];
        }

        if (!$this->canConvertHeic()) {
            return [
                'success' => true,
                'fileName' => $originalName,
                'message' => 'Server chưa hỗ trợ chuyển đổi HEIC/HEIF. File đã được tải lên, nhưng trình duyệt có thể chỉ hiển thị link mở file.'
            ];
        }

        $convertedName = uniqid('post_', true) . '.jpg';
        $convertedPath = $uploadDir . $convertedName;

        try {
            $imagick = new \Imagick($originalPath);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            $imagick->writeImage($convertedPath);
            $imagick->clear();
            $imagick->destroy();

            if (is_file($convertedPath)) {
                @unlink($originalPath);

                return [
                    'success' => true,
                    'fileName' => $convertedName,
                    'message' => ''
                ];
            }
        } catch (\Throwable $e) {
            if (isset($imagick) && $imagick instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
            }
        }

        return [
            'success' => true,
            'fileName' => $originalName,
            'message' => 'Server chưa chuyển đổi được HEIC/HEIF. File đã được tải lên, nhưng trình duyệt có thể chỉ hiển thị link mở file.'
        ];
    }

    private function canConvertHeic(): bool {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            return false;
        }

        try {
            $formats = array_map('strtoupper', array_merge(
                \Imagick::queryFormats('HEIC'),
                \Imagick::queryFormats('HEIF')
            ));
        } catch (\Throwable $e) {
            return false;
        }

        return in_array('HEIC', $formats, true) || in_array('HEIF', $formats, true);
    }

    private function formatCommentForResponse($comment, int $currentUserId): array {
        if (!$comment) {
            return [];
        }

        $ownerId = (int) ($comment['UserID'] ?? 0);
        $postOwnerId = (int) ($comment['PostOwnerID'] ?? 0);

        return [
            "commentId" => (int) ($comment['CommentID'] ?? 0),
            "postId" => (int) ($comment['PostID'] ?? 0),
            "userId" => $ownerId,
            "parentCommentId" => !empty($comment['ParentCommentID']) ? (int) $comment['ParentCommentID'] : null,
            "content" => (string) ($comment['Content'] ?? ''),
            "createdAt" => (string) ($comment['CreatedAt'] ?? ''),
            "username" => (string) ($comment['Username'] ?? ''),
            "fullName" => !empty($comment['FullName'])
                ? (string) $comment['FullName']
                : (!empty($comment['Username']) ? '@' . $comment['Username'] : 'Bạn'),
            "profilePictureUrl" => $comment['ProfilePictureUrl'] ?? 'Public/assets/img/default-avatar.jpg',
            "canEdit" => $ownerId === $currentUserId,
            "canDelete" => $ownerId === $currentUserId || $postOwnerId === $currentUserId,
            "canReport" => $ownerId !== $currentUserId
        ];
    }

    private function json(bool $success, string $message, array $extra = []): void {
        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode(array_merge([
            "success" => $success,
            "message" => $message,
            "data" => []
        ], $extra), JSON_UNESCAPED_UNICODE);
    }
}
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    ini_set('display_errors', '0');
    ob_start();
    set_exception_handler(function (\Throwable $e) {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "success" => false,
            "message" => "Có lỗi xảy ra khi xử lý yêu cầu.",
            "data" => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    });

    $controller = new PostController();

    if ($_GET['action'] === 'create') {
        $controller->create();
    } elseif ($_GET['action'] === 'like') {
        $controller->like();
    } elseif ($_GET['action'] === 'repost') {
        $controller->repost();
    } elseif ($_GET['action'] === 'comment') {
        $controller->comment();
    } elseif ($_GET['action'] === 'updateComment') {
        $controller->updateComment();
    } elseif ($_GET['action'] === 'deleteComment') {
        $controller->deleteComment();
    } elseif ($_GET['action'] === 'reportComment') {
        $controller->reportComment();
    } elseif ($_GET['action'] === 'trendingHashtags') {
        $controller->trendingHashtags();
    } elseif ($_GET['action'] === 'updatePost') {
        $controller->updatePost();
    } elseif ($_GET['action'] === 'deletePost') {
        $controller->deletePost();
    } elseif ($_GET['action'] === 'updatePostPrivacy') {
        $controller->updatePostPrivacy();
    } elseif ($_GET['action'] === 'createReport') {
        $controller->createReport();
    } elseif ($_GET['action'] === 'blockUser') {
        $controller->blockUser();
    } elseif ($_GET['action'] === 'markNotInterested') {
        $controller->markNotInterested();
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "success" => false,
            "message" => "Action khong hop le."
        ]);
    }

    exit;
}
?>
