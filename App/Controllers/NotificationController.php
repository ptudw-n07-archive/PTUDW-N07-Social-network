<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/NotificationModel.php';

use App\Models\NotificationModel;
use Database;

class NotificationController {
    private $notificationModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->notificationModel = new NotificationModel($db);
    }

    public function index() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header('Location: ' . app_url('App/Views/auth/login.php'));
            exit();
        }

        return [
            "notifications" => $this->notificationModel->getNotificationsByUser($userId),
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ];
    }

    public function countUnreadForCurrentUser() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return 0;
        }

        return $this->notificationModel->countUnread($userId);
    }

    public function markAsRead() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;
        $notificationId = $_POST['notificationId'] ?? null;

        if (!$userId || !$notificationId) {
            echo json_encode(["success" => false, "message" => "Thieu thong tin thong bao."]);
            return;
        }

        echo json_encode([
            "success" => $this->notificationModel->markAsRead($notificationId, $userId),
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ]);
    }

    public function markAllAsRead() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            echo json_encode(["success" => false, "message" => "Ban chua dang nhap."]);
            return;
        }

        $success = $this->notificationModel->markAllAsRead($userId);

        echo json_encode([
            "success" => $success,
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ]);
    }

    public function unreadCount() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;

        echo json_encode([
            "success" => (bool) $userId,
            "unreadCount" => $userId ? $this->notificationModel->countUnread($userId) : 0
        ]);
    }

    public function open() {
        $userId = $_SESSION['user_id'] ?? null;
        $notificationId = $_GET['id'] ?? null;

        if (!$userId) {
            header('Location: ' . app_url('App/Views/auth/login.php'));
            exit();
        }

        if (!$notificationId) {
            header('Location: ' . app_url('App/Views/notifications/notifications.php'));
            exit();
        }

        $notification = $this->notificationModel->getNotificationByUser($notificationId, $userId);

        if (!$notification) {
            header('Location: ' . app_url('App/Views/notifications/notifications.php'));
            exit();
        }

        $this->notificationModel->markAsRead($notificationId, $userId);

        if (!empty($notification['PostID'])) {
            $url = app_url('App/Views/post/post-detail.php?id=' . urlencode((string) $notification['PostID']));

            if (!empty($notification['CommentID'])) {
                $url .= "&comment=" . urlencode((string) $notification['CommentID']) . "#comment-" . urlencode((string) $notification['CommentID']);
            }

            header('Location: ' . $url);
            exit();
        }

        if (!empty($notification['SenderUserID'])) {
            header('Location: ' . app_url('App/Views/profile/profile.php?id=' . urlencode((string) $notification['SenderUserID'])));
            exit();
        }

        header('Location: ' . app_url('App/Views/notifications/notifications.php'));
        exit();
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    $controller = new NotificationController();

    if ($_GET['action'] === 'markAsRead') {
        $controller->markAsRead();
    } elseif ($_GET['action'] === 'markAllAsRead') {
        $controller->markAllAsRead();
    } elseif ($_GET['action'] === 'unreadCount') {
        $controller->unreadCount();
    } elseif ($_GET['action'] === 'open') {
        $controller->open();
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["success" => false, "message" => "Action khong hop le."]);
    }

    exit;
}
?>
