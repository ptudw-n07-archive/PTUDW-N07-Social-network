<?php
namespace App\Controllers; // Thêm namespace cho Controller
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php'; // Đi lùi 2 cấp ra hẳn ngoài gốc để tìm Config
require_once __DIR__ . '/../Models/FollowModel.php';     // Chỉ cần lùi 1 cấp là thấy anh bạn hàng xóm Models rồi

use App\Models\FollowModel;
use Database;

class FollowController {
    private $followModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->followModel = new FollowModel($db);
    }

    public function getSuggestedUsers($currentUserId) {
        return $this->followModel->getSuggestedUsers($currentUserId);
    }

    public function toggle() {
        header('Content-Type: application/json; charset=utf-8');

        $followerId = $_SESSION['user_id'] ?? null;
        $followingId = filter_var($_POST['userId'] ?? null, FILTER_VALIDATE_INT);

        if (!$followerId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

        if (!$followingId || $followingId < 1) {
            echo json_encode([
                "success" => false,
                "message" => "Thiếu UserID."
            ]);
            return;
        }

        if ($followerId == $followingId) {
            echo json_encode([
                "success" => false,
                "message" => "Không thể tự theo dõi chính mình."
            ]);
            return;
        }

        $status = $this->followModel->toggleFollow((int) $followerId, (int) $followingId);
        $isFollowing = $status === "followed";
        $followerCount = $this->followModel->countFollowers((int) $followingId);

        echo json_encode([
            "success" => true,
            "status" => $status,
            "isFollowing" => $isFollowing,
            "followerCount" => $followerCount,
            "message" => $isFollowing ? "Đã theo dõi." : "Đã bỏ theo dõi."
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (isset($_GET['action'])) {
    $controller = new FollowController();

    if ($_GET['action'] === 'toggle') {
        $controller->toggle();
    }
}
?>
