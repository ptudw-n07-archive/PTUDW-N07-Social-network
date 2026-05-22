<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/PostModel.php';
require_once __DIR__ . '/../Models/FollowModel.php';

use App\Models\FollowModel;
use App\Models\PostModel;
use App\Models\UserModel;
use Database;
use Exception;

class ProfileController {
    private UserModel $userModel;
    private PostModel $postModel;
    private FollowModel $followModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();

        $this->userModel = new UserModel($db);
        $this->postModel = new PostModel($db);
        $this->followModel = new FollowModel($db);
    }

    public function index(): array {
        $currentUserId = $this->requireLogin();
        $requestedId = $_GET['id'] ?? null;
        $profileUserId = $requestedId === null || $requestedId === ''
            ? $currentUserId
            : filter_var($requestedId, FILTER_VALIDATE_INT);

        if (!$profileUserId || $profileUserId < 1) {
            return $this->notFoundData($currentUserId, 0);
        }

        $profile = $this->userModel->getUserProfileById($profileUserId);

        if (!$profile) {
            return $this->notFoundData($currentUserId, (int) $profileUserId);
        }

        $isOwnProfile = (int) $profileUserId === (int) $currentUserId;
        $posts = $this->postModel->getPostsByUserId($profileUserId, $currentUserId);

        return [
            'profile' => $profile,
            'posts' => $posts,
            'currentUserId' => $currentUserId,
            'profileUserId' => (int) $profileUserId,
            'isOwnProfile' => $isOwnProfile,
            'notFound' => false,
            'followingUsers' => $this->followModel->getFollowingByUserId($profileUserId),
            'followerUsers' => $this->followModel->getFollowersByUserId($profileUserId),
            'stats' => [
                'posts' => $this->postModel->countPostsByUserId($profileUserId),
                'following' => $this->userModel->countFollowing($profileUserId),
                'followers' => $this->userModel->countFollowers($profileUserId)
            ]
        ];
    }

    public function getCurrentProfileData(): array {
        return $this->index();
    }

    public function update() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->requireLoginJson();

            $fullName = trim($_POST['fullname'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if ($fullName === '' || $username === '' || $email === '') {
                $this->json(false, "Họ tên, username và email không được để trống.");
                return;
            }

            if (!preg_match('/^[A-Za-z0-9_\\.]{3,50}$/', $username)) {
                $this->json(false, "Username phải có 3-50 ký tự, chỉ gồm chữ, số, dấu gạch dưới hoặc dấu chấm.");
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json(false, "Email không hợp lệ.");
                return;
            }

            if (mb_strlen($fullName) > 100 || mb_strlen($bio) > 500) {
                $this->json(false, "Họ tên tối đa 100 ký tự và bio tối đa 500 ký tự.");
                return;
            }

            if ($this->userModel->isUsernameTaken($username, $userId)) {
                $this->json(false, "Username này đã được sử dụng.");
                return;
            }

            if ($this->userModel->isEmailTaken($email, $userId)) {
                $this->json(false, "Email này đã được sử dụng.");
                return;
            }

            $avatarPath = $this->handleAvatarUpload();

            if (!$this->userModel->updateProfile($userId, $fullName, $username, $email, $bio, $avatarPath)) {
                $this->json(false, "Không thể cập nhật hồ sơ.");
                return;
            }

            $profile = $this->userModel->getUserProfileById($userId);
            $_SESSION['username'] = $profile['Username'];
            $_SESSION['user_name'] = $profile['FullName'];
            $_SESSION['ProfilePictureUrl'] = $profile['ProfilePictureUrl'];
            $_SESSION['role'] = $profile['RoleName'];

            $this->json(true, "Cập nhật hồ sơ thành công.", [
                'profile' => [
                    'FullName' => $profile['FullName'],
                    'Username' => $profile['Username'],
                    'Email' => $profile['Email'],
                    'Bio' => $profile['Bio'],
                    'ProfilePictureUrl' => $profile['ProfilePictureUrl'],
                    'RoleName' => $profile['RoleName'],
                    'CreatedAt' => $profile['CreatedAt']
                ]
            ]);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    private function requireLogin(): int {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header("Location: " . BASE_URL . "App/Views/auth/login.php");
            exit();
        }

        return (int) $userId;
    }

    private function requireLoginJson(): int {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            throw new Exception("Bạn chưa đăng nhập.");
        }

        return (int) $userId;
    }

    private function handleAvatarUpload(): ?string {
        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload avatar thất bại.");
        }

        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Avatar không được vượt quá 5MB.");
        }

        $originalName = $_FILES['avatar']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception("Avatar chỉ hỗ trợ jpg, jpeg, png hoặc webp.");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['avatar']['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new Exception("File avatar không đúng định dạng ảnh hợp lệ.");
        }

        $uploadDir = __DIR__ . '/../../Public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('avatar_', true) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            throw new Exception("Không thể lưu avatar lên server.");
        }

        return 'Public/uploads/avatars/' . $fileName;
    }

    private function notFoundData(int $currentUserId, int $profileUserId): array {
        return [
            'profile' => null,
            'posts' => [],
            'currentUserId' => $currentUserId,
            'profileUserId' => $profileUserId,
            'isOwnProfile' => false,
            'notFound' => true,
            'followingUsers' => [],
            'followerUsers' => [],
            'stats' => [
                'posts' => 0,
                'following' => 0,
                'followers' => 0
            ]
        ];
    }

    private function json(bool $success, string $message, array $extra = []): void {
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extra), JSON_UNESCAPED_UNICODE);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    $controller = new ProfileController();

    if ($_GET['action'] === 'update') {
        $controller->update();
    }
}
?>
