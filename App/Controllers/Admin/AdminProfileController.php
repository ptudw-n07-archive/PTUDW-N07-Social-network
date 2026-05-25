<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminProfileModel;
use Exception;

class AdminProfileController {
    private AdminController $main;
    private AdminProfileModel $adminProfileModel;

    public function __construct(AdminController $main, AdminProfileModel $adminProfileModel) {
        $this->main = $main;
        $this->adminProfileModel = $adminProfileModel;
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

    private function adminProfilePayload(array $admin): array {
        return $this->main->adminProfilePayload($admin);
    }

    private function saveAdminAvatar(int $adminUserId): string {
        return $this->main->saveAdminAvatar($adminUserId);
    }

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $this->main->logAdminAction($action, $targetType, $targetId, $description);
    }

    public function getAdminProfile(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $admin = $this->adminProfileModel->getAdminProfileById($this->currentAdminId());
        if (!$admin) {
            $this->jsonResponse(false, 'Không tìm thấy hồ sơ admin.');
            return;
        }

        $this->jsonResponse(true, 'Lấy hồ sơ admin thành công', ['profile' => $this->adminProfilePayload($admin)]);
    }

    public function updateAdminFullName(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $fullName = trim((string)($payload['FullName'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) > 100) {
            $this->jsonResponse(false, 'FullName không được rỗng và tối đa 100 ký tự.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        try {
            if (!$this->adminProfileModel->updateAdminFullName($adminUserId, $fullName)) {
                $this->jsonResponse(false, 'Không thể cập nhật FullName.');
                return;
            }

            $_SESSION['user_name'] = $fullName;
            $admin = $this->adminProfileModel->getAdminProfileById($adminUserId);
            $this->logAdminAction('UpdateProfile', 'AdminProfile', $adminUserId, 'Cập nhật FullName hồ sơ admin.');
            $this->jsonResponse(true, 'Cập nhật FullName thành công.', ['profile' => $this->adminProfilePayload($admin)]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi cập nhật FullName.');
        }
    }

    public function updateAdminBio(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $bio = trim((string)($payload['Bio'] ?? ''));
        if (mb_strlen($bio) > 500) {
            $this->jsonResponse(false, 'Bio tối đa 500 ký tự.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        try {
            if (!$this->adminProfileModel->updateAdminBio($adminUserId, $bio)) {
                $this->jsonResponse(false, 'Không thể cập nhật bio.');
                return;
            }

            $this->logAdminAction('UpdateBio', 'AdminProfile', $adminUserId, 'Cập nhật bio quản trị viên.');
            $this->jsonResponse(true, 'Cập nhật bio thành công', ['Bio' => $bio]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi cập nhật bio.');
        }
    }

    public function updateAdminAvatar(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        try {
            $avatarPath = $this->saveAdminAvatar($adminUserId);
            if (!$this->adminProfileModel->updateAdminAvatar($adminUserId, $avatarPath)) {
                $this->jsonResponse(false, 'Không thể cập nhật avatar.');
                return;
            }

            $_SESSION['avatar'] = $avatarPath;
            $_SESSION['ProfilePictureUrl'] = $avatarPath;
            $admin = $this->adminProfileModel->getAdminProfileById($adminUserId);
            $this->logAdminAction('UpdateAvatar', 'AdminProfile', $adminUserId, 'Cập nhật avatar hồ sơ admin.');
            $this->jsonResponse(true, 'Cập nhật avatar thành công.', ['profile' => $this->adminProfilePayload($admin)]);
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    public function changeAdminPassword(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $currentPassword = (string)($payload['CurrentPassword'] ?? '');
        $newPassword = (string)($payload['NewPassword'] ?? '');
        $confirmPassword = (string)($payload['ConfirmPassword'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->jsonResponse(false, 'Vui lòng nhập đầy đủ thông tin mật khẩu.');
            return;
        }

        // Đổi mật khẩu chỉ cho phép khi mật khẩu hiện tại khớp với hash trong DB.
        if (strlen($newPassword) < 8) {
            $this->jsonResponse(false, 'Mật khẩu mới phải có ít nhất 8 ký tự.');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(false, 'Mật khẩu mới và xác nhận không khớp.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        $currentHash = $this->adminProfileModel->getUserPasswordHash($adminUserId);
        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            $this->jsonResponse(false, 'Mật khẩu hiện tại không đúng.');
            return;
        }

        try {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if (!$this->adminProfileModel->updateAdminPassword($adminUserId, $newHash)) {
                $this->jsonResponse(false, 'Không thể đổi mật khẩu.');
                return;
            }

            $this->logAdminAction('ChangePassword', 'AdminProfile', $adminUserId, 'Đổi mật khẩu hồ sơ admin.');
            $this->jsonResponse(true, 'Đổi mật khẩu thành công.');
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi đổi mật khẩu.');
        }
    }

    public function adminLogs(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $adminUserId = $this->currentAdminId();
            $logs = $this->adminProfileModel->getAdminLogs($adminUserId, $_GET['keyword'] ?? '', $_GET['actionFilter'] ?? '', 50);
            $actions = $this->adminProfileModel->getAdminLogActions($adminUserId);
            $this->jsonResponse(true, 'Lấy admin logs thành công', [
                'logs' => $logs,
                'actions' => $actions
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy admin logs.');
        }
    }

}
?>
