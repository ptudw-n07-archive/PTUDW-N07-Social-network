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
        return $this->postModel->getAllPosts();
    }

    public function create() {
        header('Content-Type: application/json; charset=utf-8');

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
            $uploadDir = __DIR__ . '/../../Public/uploads/posts/';
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
                    "CommentCount" => 0
                ],
                "uploadErrors" => $uploadErrors
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Không thể tạo bài viết."]);
        }
    }

    public function like() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

        $postId = $_POST['postId'] ?? null;

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
            "likeCount" => $likeCount
        ]);
    }

    public function comment() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

        $postId = $_POST['postId'] ?? null;
        $content = trim($_POST['content'] ?? '');

        if (!$postId || $content === '') {
            echo json_encode([
                "success" => false,
                "message" => "Thiếu nội dung bình luận."
            ]);
            return;
        }

        $commentId = $this->postModel->createComment($userId, $postId, $content);

        if ($commentId) {
            $receiverUserId = $this->postModel->getPostOwnerId($postId);

            if ($receiverUserId && (int) $receiverUserId !== (int) $userId) {
                $this->notificationModel->createNotification(2, $receiverUserId, $userId, $postId, $commentId);
            }
        }

        echo json_encode([
            "success" => (bool) $commentId,
            "comment" => [
                "commentId" => $commentId,
                "content" => $content,
                "fullName" => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Bạn'
            ]
        ]);
    }

    public function getComments($postId) {
        return $this->postModel->getCommentsByPostId($postId);
    }

    public function detail($postId, $viewerId = null) {
        return $this->postModel->getPostById($postId, $viewerId);
    }

    public function getTrendingHashtags($limit = 10) {
        return $this->postModel->getTrendingHashtags($limit);
    }

    public function getPostsByHashtag($tag) {
        return $this->postModel->getPostsByHashtag($tag);
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
}
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    $controller = new PostController();

    if ($_GET['action'] === 'create') {
        $controller->create();
    } elseif ($_GET['action'] === 'like') {
        $controller->like();
    } elseif ($_GET['action'] === 'comment') {
        $controller->comment();
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
