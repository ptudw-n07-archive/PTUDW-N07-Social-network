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

        $followerId = $_SESSION['UserID'] ?? 1;
        $followingId = $_POST['userId'] ?? null;

        if (!$followingId) {
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

        $status = $this->followModel->toggleFollow($followerId, $followingId);

        echo json_encode([
            "success" => true,
            "status" => $status
        ]);
    }
}

if (isset($_GET['action'])) {
    $controller = new FollowController();

    if ($_GET['action'] === 'toggle') {
        $controller->toggle();
    }
}
?>