<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/AdminModel.php';

use App\Models\AdminModel;
use Database;
use Exception;

class AdminController {
    private AdminModel $adminModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->adminModel = new AdminModel($db);
    }

    private function jsonResponse(bool $success, string $message, $data = null): void {
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private function jsonPayload(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            return [];
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }

    private function currentAdminId(): ?int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    private function isAdmin(): bool {
        $currentAdminId = $this->currentAdminId();
        if (!$currentAdminId) {
            return false;
        }

        $user = $this->adminModel->getUserById($currentAdminId);
        if ($user && (int)$user['RoleID'] === 1 && (int)$user['IsActive'] === 1) {
            $_SESSION['role_id'] = 1;
            $_SESSION['role'] = $user['RoleName'];
            return true;
        }

        return false;
    }

    public function index() {
        if (!$this->isAdmin()) {
            header("Location: " . BASE_URL . "App/Views/auth/login.php");
            exit();
        }

        try {
            $stats = $this->adminModel->getOverviewStats();
            $reports = $this->adminModel->getReportsList();
            $members = $this->adminModel->getMembersList();
            $roles = $this->adminModel->getAllRoles();
            $currentAdminId = $this->currentAdminId();
        } catch (Exception $e) {
            $stats = ['users' => 0, 'reports' => 0, 'posts' => 0, 'activity' => '0%'];
            $reports = [];
            $members = [];
            $roles = [];
            $currentAdminId = $this->currentAdminId();
        }

        require_once __DIR__ . '/../Views/admin/index.php';
    }

    public function listMembers(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $keyword = $_GET['keyword'] ?? '';
        $roleId = $_GET['roleId'] ?? '';

        try {
            $members = $this->adminModel->getMembersList($keyword, $roleId);
            $this->jsonResponse(true, 'Lấy danh sách thành viên thành công', [
                'members' => $members,
                'currentAdminId' => $this->currentAdminId()
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách thành viên.');
        }
    }

    public function processReport(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $reportId = isset($_POST['reportId']) ? (int)$_POST['reportId'] : null;
        $action = $_POST['action'] ?? null;
        $adminNote = $_POST['adminNote'] ?? null;

        if (!$reportId || !$action) {
            $this->jsonResponse(false, 'Thiếu thông tin ReportID hoặc hành động.');
            return;
        }

        $allowed = ['ignore', 'hide', 'warn'];
        if (!in_array($action, $allowed, true)) {
            $this->jsonResponse(false, 'Hành động không hợp lệ.');
            return;
        }

        $report = $this->adminModel->getReportById($reportId);
        if (!$report) {
            $this->jsonResponse(false, 'Báo cáo không tồn tại.');
            return;
        }

        try {
            $adminUserId = $this->currentAdminId();
            if ($action === 'ignore') {
                $this->adminModel->markReportResolved($reportId, $adminNote);
                $msg = 'Báo cáo đã bị bỏ qua.';
            } elseif ($action === 'hide') {
                $hidden = false;
                if (!empty($report['PostID'])) {
                    $this->adminModel->hidePostById((int)$report['PostID']);
                    $hidden = true;
                }
                if (!empty($report['CommentID'])) {
                    $this->adminModel->hideCommentById((int)$report['CommentID']);
                    $hidden = true;
                }
                $this->adminModel->markReportResolved($reportId, $adminNote);
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ContentHidden');
                }
                $msg = $hidden ? 'Nội dung đã được ẩn và báo cáo được đánh dấu hoàn tất.' : 'Không có nội dung để ẩn; báo cáo đã được đánh dấu hoàn tất.';
            } else {
                $this->adminModel->markReportResolved($reportId, $adminNote);
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ReportWarning');
                }
                $msg = 'Người dùng đã được cảnh cáo; báo cáo đã xử lý.';
            }

            $this->jsonResponse(true, $msg, ['reportId' => $reportId]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi xử lý báo cáo.');
        }
    }

    public function updateUserRole(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'roles') {
            if (!$this->isAdmin()) {
                $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
                return;
            }

            try {
                $roles = $this->adminModel->getAllRoles();
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

        $payload = $this->jsonPayload();
        $userId = isset($payload['UserID']) ? (int)$payload['UserID'] : (isset($_POST['userId']) ? (int)$_POST['userId'] : null);
        $roleId = isset($payload['RoleID']) ? (int)$payload['RoleID'] : (isset($_POST['roleId']) ? (int)$_POST['roleId'] : null);
        $currentAdminId = $this->currentAdminId();

        if (!$userId || !$roleId) {
            $this->jsonResponse(false, 'Thiếu thông tin UserID hoặc RoleID.');
            return;
        }

        if ($userId === $currentAdminId) {
            $this->jsonResponse(false, 'Bạn không thể thay đổi quyền hoặc khóa chính tài khoản đang đăng nhập.');
            return;
        }

        $user = $this->adminModel->getUserById($userId);
        if (!$user) {
            $this->jsonResponse(false, 'Người dùng không tồn tại.');
            return;
        }

        $role = $this->adminModel->getRoleById($roleId);
        if (!$role) {
            $this->jsonResponse(false, 'Vai trò không hợp lệ.');
            return;
        }

        try {
            $result = $this->adminModel->updateUserRole($userId, $roleId);
            if (!$result) {
                $this->jsonResponse(false, 'Cập nhật thất bại.');
                return;
            }

            if ($currentAdminId) {
                $this->adminModel->createNotificationByType($userId, $currentAdminId, 'RoleChanged');
            }

            $updatedUser = $this->adminModel->getUserById($userId);
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

        $user = $this->adminModel->getUserById($userId);
        if (!$user) {
            $this->jsonResponse(false, 'Người dùng không tồn tại.');
            return;
        }

        try {
            $result = $this->adminModel->updateUserActiveStatus($userId, $isActive);
            if (!$result) {
                $this->jsonResponse(false, 'Cập nhật trạng thái tài khoản thất bại.');
                return;
            }

            if ($currentAdminId) {
                $typeName = $isActive === 1 ? 'AccountUnlocked' : 'AccountLocked';
                $this->adminModel->createNotificationByType($userId, $currentAdminId, $typeName);
            }

            $updatedUser = $this->adminModel->getUserById($userId);
            $this->jsonResponse(true, $isActive === 1 ? 'Mở khóa tài khoản thành công' : 'Khóa tài khoản thành công', [
                'UserID' => $userId,
                'IsActive' => $isActive,
                'StatusText' => $isActive === 1 ? 'Hoạt động' : 'Bị khóa',
                'member' => $updatedUser
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi cơ sở dữ liệu.');
        }
    }
}

if (isset($_GET['action'])) {
    $controller = new AdminController();

    if ($_GET['action'] === 'processReport') {
        $controller->processReport();
    } elseif ($_GET['action'] === 'updateUserRole') {
        $controller->updateUserRole();
    } elseif ($_GET['action'] === 'toggleUserActive') {
        $controller->toggleUserActive();
    } elseif ($_GET['action'] === 'listMembers') {
        $controller->listMembers();
    }
}
?>
