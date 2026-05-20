<?php
namespace App\Controllers; // ✨ 1. Thêm namespace cho Controller

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/PostModel.php';     

// ✨ 2. Khai báo sử dụng lớp PostModel từ bên thư mục Models và lớp Database từ gốc
use App\Models\PostModel;
use Database;

class PostController {
    private $postModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->postModel = new PostModel($db);
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
        if ($content === '' && empty($_FILES['images']['name'][0])) {
            echo json_encode(["success" => false, "message" => "Nội dung không được để trống."]);
            return;
        }

        // 1. Tạo bài viết trong Database lấy ra PostID mới nhất
        $postId = $this->postModel->createPost($userId, $content);
        
        // Mảng dùng để lưu các đường dẫn ảnh gửi về cho Front-end render tại chỗ
        $savedImages = [];

        // 2. Xử lý Upload nhiều ảnh (Nếu có)
        if ($postId && !empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . '/../../Public/assets/img/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $fileName = uniqid('post_', true) . '.' . $ext;
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
                        // Đường dẫn tương đối chuẩn để lưu DB và hiển thị trên giao diện
                        $dbPath = 'Public/assets/img/posts/' . $fileName;
                        $this->postModel->addPostImage($postId, $dbPath);
                        $savedImages[] = BASE_URL . $dbPath; // Đắp full URL để Front-end hiển thị ăn chắc 100%
                    }
                }
            }
        }

        if ($postId) {
            // 🎯 PHẢN HỒI JSON CHUẨN AJAX: Gửi toàn bộ thông tin bài viết mới tạo về cho JavaScript vẽ giao diện
            echo json_encode([
                "success" => true,
                "post" => [
                    "PostID" => $postId,
                    "Content" => htmlspecialchars($content),
                    "CreatedAt" => "Vừa xong",
                    "UserID" => $userId,
                    "Username" => $_SESSION['username'] ?? '',
                    "FullName" => $_SESSION['user_name'] ?? '',
                    "ProfilePictureUrl" => $_SESSION['avatar'] ?? $_SESSION['ProfilePictureUrl'] ?? $_SESSION['user_avatar'] ?? 'Public/assets/img/default-avatar.png',
                    "Images" => $savedImages, // Trả về mảng ảnh xịn vừa upload
                    "LikeCount" => 0,
                    "CommentCount" => 0
                ]
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

        $result = $this->postModel->createComment($userId, $postId, $content);

        echo json_encode([
            "success" => $result,
            "comment" => [
                "content" => $content,
                "fullName" => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Bạn'
            ]
        ]);
    }

    public function getComments($postId) {
        return $this->postModel->getCommentsByPostId($postId);
    }
}
if (isset($_GET['action'])) {
    $controller = new PostController();

    if ($_GET['action'] === 'create') {
        $controller->create();
    } elseif ($_GET['action'] === 'like') {
        $controller->like();
    } elseif ($_GET['action'] === 'comment') {
        $controller->comment();
    }
}
?>