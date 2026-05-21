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

        $reportId = $_POST['reportId'] ?? null;
        $status = $_POST['status'] ?? null;

        if (!$reportId || !$status) {
            echo json_encode(["success" => false, "message" => "Thiếu thông tin ReportID hoặc Status."]);
            return;
        }

        $result = $this->adminModel->updateReportStatus($reportId, $status);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Xử lý báo cáo vi phạm thành công!",
                "reportId" => $reportId,
                "status" => $status
            ]);
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "Lỗi cập nhật cơ sở dữ liệu."]);
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'processReport') {
    $controller = new AdminController();
    $controller->processReport();
}