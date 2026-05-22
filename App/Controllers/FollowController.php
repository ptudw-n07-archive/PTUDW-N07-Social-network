<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/FollowModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';

use App\Models\FollowModel;
use App\Models\NotificationModel;
use Database;

class FollowController {
    private $followModel;
    private $notificationModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->followModel = new FollowModel($db);
        $this->notificationModel = new NotificationModel($db);
    }

    public function getSuggestedUsers($currentUserId) {
        return $this->followModel->getSuggestedUsers($currentUserId);
    }

    public function toggle() {
        header('Content-Type: application/json; charset=utf-8');

        $followerId = $_SESSION['user_id'] ?? null;
        $followingId = $_POST['userId'] ?? null;

        if (!$followerId) {
            echo json_encode([
                "success" => false,
                "message" => "Bạn chưa đăng nhập."
            ]);
            return;
        }

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

        if ($status === "followed") {
            $this->notificationModel->createNotification(3, $followingId, $followerId);
        }

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
