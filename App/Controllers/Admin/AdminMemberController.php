<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminMemberModel;
use App\Models\AdminNotificationModel;
use App\Models\AdminReportModel;
use Exception;

class AdminMemberController {
    private AdminController $main;
    private AdminMemberModel $adminMemberModel;
    private AdminNotificationModel $adminNotificationModel;
    private AdminReportModel $adminReportModel;

    public function __construct(AdminController $main, AdminMemberModel $adminMemberModel, AdminNotificationModel $adminNotificationModel, AdminReportModel $adminReportModel) {
        $this->main = $main;
        $this->adminMemberModel = $adminMemberModel;
        $this->adminNotificationModel = $adminNotificationModel;
        $this->adminReportModel = $adminReportModel;
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

    private function currentAdminId(): ?int {
        return $this->main->currentAdminId();
    }

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $this->main->logAdminAction($action, $targetType, $targetId, $description);
    }

    public function listMembers(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $keyword = $_GET['keyword'] ?? '';
        $roleId = $_GET['roleId'] ?? '';

        try {
            $members = $this->adminMemberModel->getMembersList($keyword, $roleId);
            $this->jsonResponse(true, 'Lấy danh sách thành viên thành công', [
                'members' => $members,
                'currentAdminId' => $this->currentAdminId()
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách thành viên.');
        }
    }

    public function updateUserRole(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'roles') {
            if (!$this->isAdmin()) {
                $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
                return;
            }

            try {
                $roles = $this->adminMemberModel->getAllRoles();
                $this->jsonResponse(true, 'Lấy danh sách vai trò thành công', ['roles' => $roles]);
            } catch (Exception $e) {
                $this->jsonResponse(false, 'Lỗi khi lấy danh sách vai trò.');
            }
            return;
        }

        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $userId = isset($payload['UserID']) ? (int)$payload['UserID'] : (isset($_POST['userId']) ? (int)$_POST['userId'] : null);
        $roleId = isset($payload['RoleID']) ? (int)$payload['RoleID'] : (isset($_POST['roleId']) ? (int)$_POST['roleId'] : null);
        $currentAdminId = $this->currentAdminId();

        if (!$userId || !$roleId) {
            $this->jsonResponse(false, 'Thiếu thông tin UserID hoặc RoleID.');
            return;
        }

        // Không cho admin tự đổi quyền của chính mình để tránh tự khóa quyền quản trị.
        if ($userId === $currentAdminId) {
            $this->jsonResponse(false, 'Bạn không thể thay đổi quyền hoặc khóa chính tài khoản đang đăng nhập.');
            return;
        }

        $user = $this->adminMemberModel->getUserById($userId);
        if (!$user) {
            $this->jsonResponse(false, 'Người dùng không tồn tại.');
            return;
        }

        $role = $this->adminMemberModel->getRoleById($roleId);
        if (!$role) {
            $this->jsonResponse(false, 'Vai trò không hợp lệ.');
            return;
        }

        try {
            $result = $this->adminMemberModel->updateUserRole($userId, $roleId);
            if (!$result) {
                $this->jsonResponse(false, 'Cập nhật thất bại.');
                return;
            }

            if ($currentAdminId) {
                $this->adminNotificationModel->createNotificationByType($userId, $currentAdminId, 'RoleChanged');
            }

            $updatedUser = $this->adminMemberModel->getUserById($userId);
            $this->logAdminAction('UpdateUserRole', 'User', $userId, 'Cập nhật vai trò user #' . $userId . ' thành ' . $role['RoleName'] . '.');
            $this->jsonResponse(true, 'Cập nhật vai trò thành công', [
                'UserID' => $userId,
                'RoleID' => $roleId,
                'RoleName' => $role['RoleName'],
                'member' => $updatedUser
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi cơ sở dữ liệu.');
        }
    }

    public function toggleUserActive(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $userId = isset($payload['UserID']) ? (int)$payload['UserID'] : null;
        $isActive = isset($payload['IsActive']) ? (int)$payload['IsActive'] : null;
        $currentAdminId = $this->currentAdminId();

        if (!$userId || !in_array($isActive, [0, 1], true)) {
            $this->jsonResponse(false, 'Thiếu thông tin UserID hoặc trạng thái tài khoản.');
            return;
        }

        if ($userId === $currentAdminId) {
            $this->jsonResponse(false, 'Bạn không thể thay đổi quyền hoặc khóa chính tài khoản đang đăng nhập.');
            return;
        }

        $user = $this->adminMemberModel->getUserById($userId);
        if (!$user) {
            $this->jsonResponse(false, 'Người dùng không tồn tại.');
            return;
        }

        try {
            $updatedReports = [];
            $result = $this->adminMemberModel->updateUserActiveStatus($userId, $isActive);
            if (!$result) {
                $this->jsonResponse(false, 'Cập nhật trạng thái tài khoản thất bại.');
                return;
            }

            if ($currentAdminId) {
                $typeName = $isActive === 1 ? 'AccountUnlocked' : 'AccountLocked';
                $this->adminNotificationModel->createNotificationByType($userId, $currentAdminId, $typeName);
            }

            if ($isActive === 0) {
                // Khóa tài khoản xong thì các report pending về user này không cần xử lý lại.
                $updatedReports = $this->adminReportModel->resolvePendingReportsByReportedUserId($userId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
            }

            $updatedUser = $this->adminMemberModel->getUserById($userId);
            $this->logAdminAction($isActive === 1 ? 'UnlockUser' : 'LockUser', 'User', $userId, ($isActive === 1 ? 'Mở khóa' : 'Khóa') . ' user #' . $userId . '.');
            if ($isActive === 0 && !empty($updatedReports)) {
                $this->logAdminAction('AutoResolveReports', 'User', $userId, 'Khóa tài khoản và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, $isActive === 1 ? 'Mở khóa tài khoản thành công' : 'Khóa tài khoản thành công', [
                'UserID' => $userId,
                'IsActive' => $isActive,
                'StatusText' => $isActive === 1 ? 'Hoạt động' : 'Bị khóa',
                'member' => $updatedUser,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi cơ sở dữ liệu.');
        }
    }

}
?>
