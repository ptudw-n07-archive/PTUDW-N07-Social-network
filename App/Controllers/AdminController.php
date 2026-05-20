<?php
namespace App\Controllers; // 1. Khai báo namespace cho Controller

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp file kết nối và file Model vào
require_once __DIR__ . '/../../Config/Database.php'; 
require_once __DIR__ . '/../Models/AdminModel.php';     

// 2. Khai báo sử dụng đúng họ hàng Namespace
use App\Models\AdminModel;
use Database;
use Exception;

class AdminController {
    private AdminModel $adminModel;

    /**
     * Tự tạo kết nối Database bên trong giống y chang PostController
     */
    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->adminModel = new AdminModel($db);
    }

    /**
     * Hàm hành động chính: Giờ chỉ lo bắt request, gọi Model lấy data, ném ra View
     */
    public function index() {
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
}