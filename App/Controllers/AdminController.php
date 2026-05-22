<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/AdminModel.php';     

// 2. Khai báo sử dụng đúng họ hàng Namespace
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

    private function isAdmin(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (isset($_SESSION['role_id']) && (int) $_SESSION['role_id'] === 1) {
            return true;
        }

        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
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
            // Controller ra lệnh cho Model
            $stats   = $this->adminModel->getOverviewStats();
            $reports = $this->adminModel->getReportsList();
            $members = $this->adminModel->getMembersList();
            
        } catch (Exception $e) {
            $stats   = ['users' => 0, 'reports' => 0, 'posts' => 0, 'activity' => '0%'];
            $reports = [];
            $members = [];
        }

        require_once __DIR__ . '/../Views/admin/index.php';
    }
    public function processReport() {
        header('Content-Type: application/json; charset=utf-8');

        // CHECK QUYỀN: Kiểm tra xem UserID có tồn tại và là Admin
        if (!$this->isAdmin()) {
            echo json_encode(["success" => false, "message" => "Bạn không có quyền quản trị viên."]);
            return;
        }
        $reportId = isset($_POST['reportId']) ? (int) $_POST['reportId'] : null;
        $action = $_POST['action'] ?? null; // allowed: ignore, hide, warn
        $adminNote = $_POST['adminNote'] ?? null;

        if (!$reportId || !$action) {
            echo json_encode(["success" => false, "message" => "Thiếu thông tin ReportID hoặc hành động."]);
            return;
        }

        $allowed = ['ignore', 'hide', 'warn'];
        if (!in_array($action, $allowed, true)) {
            echo json_encode(["success" => false, "message" => "Hành động không hợp lệ."]);
            return;
        }

        // Lấy thông tin báo cáo
        $report = $this->adminModel->getReportById($reportId);
        if (!$report) {
            echo json_encode(["success" => false, "message" => "Báo cáo không tồn tại."]);
            return;
        }

        try {
            $adminUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            // Hành động cụ thể
            if ($action === 'ignore') {
                // Chỉ đánh dấu Resolved và lưu ghi chú
                $this->adminModel->markReportResolved($reportId, $adminNote);
                $msg = 'Báo cáo đã bị bỏ qua.';
            } elseif ($action === 'hide') {
                // Ẩn nội dung nếu có PostID hoặc CommentID
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
                    $typeId = $this->adminModel->getNotificationTypeIdByName('ContentHidden');
                    if ($typeId) {
                        $this->adminModel->createNotification(
                            (int)$report['ReportedUserID'],
                            $adminUserId,
                            !empty($report['PostID']) ? (int)$report['PostID'] : null,
                            !empty($report['CommentID']) ? (int)$report['CommentID'] : null,
                            (int)$typeId
                        );
                    }
                }
                $msg = $hidden ? 'Nội dung đã được ẩn và báo cáo được đánh dấu hoàn tất.' : 'Không có nội dung để ẩn; báo cáo đã được đánh dấu hoàn tất.';
            } else { // warn
                $this->adminModel->markReportResolved($reportId, $adminNote);
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $typeId = $this->adminModel->getNotificationTypeIdByName('ReportWarning');
                    if ($typeId) {
                        $this->adminModel->createNotification(
                            (int)$report['ReportedUserID'],
                            $adminUserId,
                            !empty($report['PostID']) ? (int)$report['PostID'] : null,
                            !empty($report['CommentID']) ? (int)$report['CommentID'] : null,
                            (int)$typeId
                        );
                    }
                }
                $msg = 'Người dùng đã được cảnh cáo; báo cáo đã xử lý.';
            }

            echo json_encode(["success" => true, "message" => $msg, "reportId" => $reportId]);
            return;
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Lỗi khi xử lý báo cáo."]);
            return;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'processReport') {
    $controller = new AdminController();
    $controller->processReport();
}