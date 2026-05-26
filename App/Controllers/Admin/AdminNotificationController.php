<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminNotificationModel;
use Exception;

class AdminNotificationController {
    private AdminController $main;
    private AdminNotificationModel $adminNotificationModel;

    public function __construct(AdminController $main, AdminNotificationModel $adminNotificationModel) {
        $this->main = $main;
        $this->adminNotificationModel = $adminNotificationModel;
    }

    private function isAdmin(): bool {
        return $this->main->isAdmin();
    }

    private function jsonResponse(bool $success, string $message, $data = null): void {
        $this->main->jsonResponse($success, $message, $data);
    }

    private function jsonPayload(): array {
        return $this->main->jsonPayload();
    }

    private function intParam(string $name, string $method = 'GET'): ?int {
        return $this->main->intParam($name, $method);
    }

    private function intPayloadParam(array $payload, string $name): ?int {
        return $this->main->intPayloadParam($payload, $name);
    }

    private function currentAdminId(): ?int {
        return $this->main->currentAdminId();
    }

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $this->main->logAdminAction($action, $targetType, $targetId, $description);
    }

    public function listNotifications(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $notifications = $this->adminNotificationModel->getAdminNotifications(
                $_GET['keyword'] ?? '',
                $_GET['typeName'] ?? '',
                $_GET['isRead'] ?? ''
            );
            $this->jsonResponse(true, 'Lấy danh sách thông báo thành công', ['notifications' => $notifications]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách thông báo.');
        }
    }

    public function getNotificationDetail(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $notificationId = $this->intParam('notificationId');
        if (!$notificationId) {
            $this->jsonResponse(false, 'NotificationID không hợp lệ.');
            return;
        }

        try {
            $notification = $this->adminNotificationModel->getAdminNotificationDetail($notificationId);
            if (!$notification) {
                $this->jsonResponse(false, 'Không tìm thấy thông báo.');
                return;
            }
            $this->jsonResponse(true, 'Lấy chi tiết thông báo thành công', ['notification' => $notification]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy chi tiết thông báo.');
        }
    }

    public function deleteNotification(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $notificationId = $this->intPayloadParam($payload, 'NotificationID');
        if (!$notificationId) {
            $this->jsonResponse(false, 'NotificationID không hợp lệ.');
            return;
        }

        try {
            if (!$this->adminNotificationModel->deleteAdminNotification($notificationId)) {
                $this->jsonResponse(false, 'Thông báo không tồn tại.');
                return;
            }
            $this->logAdminAction('DeleteNotification', 'Notification', $notificationId, 'Xóa thông báo #' . $notificationId . '.');
            $this->jsonResponse(true, 'Xóa thông báo thành công', ['NotificationID' => $notificationId]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể xóa thông báo.');
        }
    }

    public function searchNotificationReceivers(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $keyword = trim((string)($_GET['keyword'] ?? ''));
            if (mb_strlen($keyword) < 2) {
                $this->jsonResponse(true, 'Tìm người nhận thành công', []);
                return;
            }

            $users = $this->adminNotificationModel->searchNotificationReceivers($keyword, 20);
            $this->jsonResponse(true, 'Tìm người nhận thành công', $users);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể tìm người nhận.');
        }
    }

    public function sendSystemNotification(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $message = trim((string)($payload['message'] ?? $payload['Message'] ?? ''));
        $sendAll = !empty($payload['sendToAll']) || !empty($payload['SendAll']);

        if ($message === '' || mb_strlen($message) > 1000) {
            $this->jsonResponse(false, 'Message không được rỗng và tối đa 1000 ký tự.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        try {
            // Có thể gửi một người hoặc gửi hàng loạt cho các tài khoản đang hoạt động.
            if ($sendAll) {
                $count = $this->adminNotificationModel->createSystemNotificationsForActiveUsers($adminUserId, $message);
                $this->logAdminAction('SendSystemNotification', 'Notification', 0, 'Gửi thông báo hệ thống cho ' . $count . ' thành viên.');
                $this->jsonResponse(true, 'Gửi thông báo hệ thống thành công', ['sentCount' => $count]);
                return;
            }

            $receiverUserId = $this->intPayloadParam($payload, 'receiverUserId') ?? $this->intPayloadParam($payload, 'ReceiverUserID');
            if (!$receiverUserId) {
                $this->jsonResponse(false, 'Vui lòng chọn người nhận.');
                return;
            }

            if (!$this->adminNotificationModel->getActiveNotificationReceiverById($receiverUserId)) {
                $this->jsonResponse(false, 'Người nhận không tồn tại hoặc đang bị khóa.');
                return;
            }

            if (!$this->adminNotificationModel->createSystemNotification($receiverUserId, $adminUserId, $message)) {
                $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
                return;
            }

            $this->logAdminAction('SendSystemNotification', 'Notification', $receiverUserId, 'Gửi thông báo hệ thống cho UserID #' . $receiverUserId . '.');
            $this->jsonResponse(true, 'Gửi thông báo hệ thống thành công', ['sentCount' => 1]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
        }
    }

}
?>
