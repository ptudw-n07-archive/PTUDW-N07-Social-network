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
    private NotificationModel $notificationModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->notificationModel = new NotificationModel($db);
    }

    public function index() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header('Location: ' . app_url('login'));
            exit();
        }

        $notifications = $this->notificationModel->getNotificationsByUser($userId);
        $this->acknowledgeBadge($userId, $notifications);

        return [
            "notifications" => $notifications,
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ];
    }

    public function countUnreadForCurrentUser() {
        return $this->countBadgeForCurrentUser();
    }

    public function countBadgeForCurrentUser() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return 0;
        }

        $marker = $_SESSION['notification_badge_marker'][(int) $userId] ?? null;

        if (is_array($marker) && !empty($marker['createdAt'])) {
            return $this->notificationModel->countAfterMarker($userId, $marker['createdAt'], $marker['notificationId'] ?? 0);
        }

        return $this->notificationModel->countUnread($userId);
    }

    public function markAsRead() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $notificationId = filter_var($_POST['notificationId'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId || !$notificationId) {
            $this->json(false, "Thieu thong tin thong bao.");
            return;
        }

        $this->json($this->notificationModel->markAsRead($notificationId, (int) $userId), "", [
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ]);
    }

    public function markAllAsRead() {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->json(false, "Ban chua dang nhap.");
            return;
        }

        $success = $this->notificationModel->markAllAsRead($userId);

        if ($success) {
            $this->acknowledgeBadge($userId, $this->notificationModel->getNotificationsByUser($userId));
        }

        $this->json($success, "", [
            "unreadCount" => $this->notificationModel->countUnread($userId)
        ]);
    }

    public function unreadCount() {
        header('Content-Type: application/json; charset=utf-8');

        $userId = $_SESSION['user_id'] ?? null;

        $this->json((bool) $userId, "", [
            "unreadCount" => $userId ? $this->notificationModel->countUnread($userId) : 0
        ]);
    }

    public function open() {
        $userId = $_SESSION['user_id'] ?? null;
        $notificationId = $_GET['id'] ?? null;

        if (!$userId) {
            header('Location: ' . app_url('login'));
            exit();
        }

        if (!$notificationId) {
            header('Location: ' . app_url('notifications'));
            exit();
        }

        $notification = $this->notificationModel->getNotificationByUser($notificationId, $userId);

        if (!$notification) {
            header('Location: ' . app_url('notifications'));
            exit();
        }

        if ($this->isDetailNotification($notification)) {
            header('Location: ' . app_url('App/Controllers/NotificationController.php?action=detail&id=' . urlencode((string) $notificationId)));
            exit();
        }

        $this->notificationModel->markAsRead($notificationId, $userId);

        if (!empty($notification['PostID'])) {
            if ((int) ($notification['PostIsHidden'] ?? 0) === 1) {
                header('Location: ' . app_url('notifications'));
                exit();
            }

            $url = app_url('post-detail?id=' . urlencode((string) $notification['PostID']));

            if (!empty($notification['CommentID'])) {
                $url .= "&comment_id=" . urlencode((string) $notification['CommentID']) . "#comment-" . urlencode((string) $notification['CommentID']);
            }

            header('Location: ' . $url);
            exit();
        }

        if (!empty($notification['SenderUserID'])) {
            header('Location: ' . app_url('profile?id=' . urlencode((string) $notification['SenderUserID'])));
            exit();
        }

        header('Location: ' . app_url('notifications'));
        exit();
    }

    public function detail() {
        $userId = $_SESSION['user_id'] ?? null;
        $notificationId = $_GET['id'] ?? null;

        if (!$userId) {
            header('Location: ' . app_url('login'));
            exit();
        }

        if (!$notificationId) {
            header('Location: ' . app_url('notifications'));
            exit();
        }

        $notification = $this->notificationModel->getModerationNotificationDetailByUser($notificationId, $userId);

        if (!$notification) {
            header('Location: ' . app_url('notifications'));
            exit();
        }

        $this->notificationModel->markAsRead($notificationId, $userId);
        $unreadNotificationCount = $this->countBadgeForCurrentUser();

        require __DIR__ . '/../Views/notifications/detail.php';
    }

    private function isDetailNotification(array $notification): bool {
        return in_array((string) ($notification['TypeName'] ?? ''), [
            'ReportWarning',
            'ContentHidden',
            'AccountLocked',
            'AccountUnlocked',
            'RoleChanged',
            'System'
        ], true);
    }

    private function acknowledgeBadge($userId, array $notifications): void {
        if (empty($notifications)) {
            return;
        }

        $newest = $notifications[0];

        $_SESSION['notification_badge_marker'][(int) $userId] = [
            'createdAt' => (string) ($newest['CreatedAt'] ?? ''),
            'notificationId' => (int) ($newest['NotificationID'] ?? 0)
        ];
    }

    private function json(bool $success, string $message = "", array $extra = []): void {
        echo json_encode(array_merge([
            "success" => $success,
            "message" => $message
        ], $extra), JSON_UNESCAPED_UNICODE);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    $controller = new NotificationController();

    match ((string) $_GET['action']) {
        'markAsRead' => $controller->markAsRead(),
        'markAllAsRead' => $controller->markAllAsRead(),
        'unreadCount' => $controller->unreadCount(),
        'open' => $controller->open(),
        'detail' => $controller->detail(),
        default => (function () {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["success" => false, "message" => "Action khong hop le."], JSON_UNESCAPED_UNICODE);
        })()
    };

    exit;
}
?>
