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

    private function receiverIdsFromPayload(array $payload): array {
        $rawIds = $payload['receiverUserIds'] ?? $payload['ReceiverUserIDs'] ?? [];
        if (!is_array($rawIds)) {
            $rawIds = [];
        }

        $legacyId = $this->intPayloadParam($payload, 'receiverUserId') ?? $this->intPayloadParam($payload, 'ReceiverUserID');
        if ($legacyId) {
            $rawIds[] = $legacyId;
        }

        return array_values(array_unique(array_filter(array_map('intval', $rawIds), fn($id) => $id > 0)));
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
        if (!$adminUserId) {
            $this->jsonResponse(false, 'Không xác định được admin đang đăng nhập.');
            return;
        }
        try {
            // Có thể gửi một người, nhiều người hoặc gửi hàng loạt cho các tài khoản đang hoạt động.
            if ($sendAll) {
                $count = $this->adminNotificationModel->createSystemNotificationsForActiveUsers($adminUserId, $message);
                $this->logAdminAction('SendSystemNotification', 'Notification', 0, 'Gửi thông báo hệ thống cho ' . $count . ' thành viên.');
                $this->jsonResponse(true, 'Đã gửi thông báo cho ' . $count . ' người dùng', ['sentCount' => $count]);
                return;
            }

            $receiverUserIds = $this->receiverIdsFromPayload($payload);
            if (empty($receiverUserIds)) {
                $this->jsonResponse(false, 'Vui lòng chọn người nhận.');
                return;
            }

            $activeReceiverIds = $this->adminNotificationModel->getActiveNotificationReceiverIds($receiverUserIds, $adminUserId);
            if (empty($activeReceiverIds)) {
                $this->jsonResponse(false, 'Người nhận không tồn tại, đang bị khóa hoặc không hợp lệ.');
                return;
            }

            $count = $this->adminNotificationModel->createSystemNotificationsForReceivers($activeReceiverIds, $adminUserId, $message);
            if ($count < 1) {
                $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
                return;
            }

            $this->logAdminAction('SendSystemNotification', 'Notification', 0, 'Gửi thông báo hệ thống cho UserID #' . implode(', #', $activeReceiverIds) . '.');
            $this->jsonResponse(true, 'Đã gửi thông báo cho ' . $count . ' người dùng', [
                'sentCount' => $count,
                'receiverUserIds' => $activeReceiverIds
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
        }
    }

}
?>
