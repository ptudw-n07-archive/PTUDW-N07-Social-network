<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/AdminStatsModel.php';
require_once __DIR__ . '/../Models/AdminReportModel.php';
require_once __DIR__ . '/../Models/AdminMemberModel.php';
require_once __DIR__ . '/../Models/AdminNotificationModel.php';
require_once __DIR__ . '/../Models/AdminContentModel.php';
require_once __DIR__ . '/../Models/AdminProfileModel.php';
require_once __DIR__ . '/Admin/AdminStatsController.php';
require_once __DIR__ . '/Admin/AdminReportController.php';
require_once __DIR__ . '/Admin/AdminMemberController.php';
require_once __DIR__ . '/Admin/AdminContentController.php';
require_once __DIR__ . '/Admin/AdminNotificationController.php';
require_once __DIR__ . '/Admin/AdminProfileController.php';

use App\Models\AdminStatsModel;
use App\Models\AdminReportModel;
use App\Models\AdminMemberModel;
use App\Models\AdminContentModel;
use App\Models\AdminNotificationModel;
use App\Models\AdminProfileModel;
use App\Controllers\Admin\AdminStatsController;
use App\Controllers\Admin\AdminReportController;
use App\Controllers\Admin\AdminMemberController;
use App\Controllers\Admin\AdminContentController;
use App\Controllers\Admin\AdminNotificationController;
use App\Controllers\Admin\AdminProfileController;
use Database;
use Exception;

class AdminController {
    private AdminStatsModel $adminStatsModel;
    private AdminReportModel $adminReportModel;
    private AdminMemberModel $adminMemberModel;
    private AdminContentModel $adminContentModel;
    private AdminNotificationModel $adminNotificationModel;
    private AdminProfileModel $adminProfileModel;
    private AdminStatsController $adminStatsController;
    private AdminReportController $adminReportController;
    private AdminMemberController $adminMemberController;
    private AdminContentController $adminContentController;
    private AdminNotificationController $adminNotificationController;
    private AdminProfileController $adminProfileController;

    public function __construct() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $database = new Database();
        $db = $database->connect();
        $db->exec("SET time_zone = '+07:00'");
        $this->adminStatsModel = new AdminStatsModel($db);
        $this->adminReportModel = new AdminReportModel($db);
        $this->adminMemberModel = new AdminMemberModel($db);
        $this->adminNotificationModel = new AdminNotificationModel($db);
        $this->adminContentModel = new AdminContentModel($db, $this->adminNotificationModel);
        $this->adminProfileModel = new AdminProfileModel($db);
        $this->adminStatsController = new AdminStatsController($this, $this->adminStatsModel);
        $this->adminReportController = new AdminReportController($this, $this->adminReportModel, $this->adminNotificationModel, $this->adminMemberModel);
        $this->adminMemberController = new AdminMemberController($this, $this->adminMemberModel, $this->adminNotificationModel, $this->adminReportModel);
        $this->adminContentController = new AdminContentController($this, $this->adminContentModel, $this->adminReportModel);
        $this->adminNotificationController = new AdminNotificationController($this, $this->adminNotificationModel);
        $this->adminProfileController = new AdminProfileController($this, $this->adminProfileModel);
    }

    // --- Helper dùng chung cho các API admin ---

    public function jsonResponse(bool $success, string $message, $data = null): void {
        // Format response JSON thống nhất cho các request AJAX.
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
            if (is_array($data) && array_key_exists('updatedReports', $data)) {
                $response['updatedReports'] = $data['updatedReports'];
            }
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function jsonPayload(): array {
        // Đọc body JSON từ frontend, nếu không phải JSON thì trả mảng rỗng.
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            return [];
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }

    public function currentAdminId(): ?int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function intParam(string $name, string $method = 'GET'): ?int {
        $source = $method === 'POST' ? $_POST : $_GET;
        if (!isset($source[$name]) || !filter_var($source[$name], FILTER_VALIDATE_INT)) {
            return null;
        }

        $value = (int)$source[$name];
        return $value > 0 ? $value : null;
    }

    public function intPayloadParam(array $payload, string $name): ?int {
        if (!isset($payload[$name]) || !filter_var($payload[$name], FILTER_VALIDATE_INT)) {
            return null;
        }

        $value = (int)$payload[$name];
        return $value > 0 ? $value : null;
    }

    public function isAdmin(): bool {
        $currentAdminId = $this->currentAdminId();
        if (!$currentAdminId) {
            return false;
        }

        // Kiểm tra lại trong DB để tránh session cũ vẫn được dùng làm admin.
        $user = $this->adminMemberModel->getUserById($currentAdminId);
        if ($user && (int)$user['RoleID'] === 1 && (int)$user['IsActive'] === 1) {
            $_SESSION['role_id'] = 1;
            $_SESSION['role'] = $user['RoleName'];
            return true;
        }

        return false;
    }

    public function adminProfilePayload(array $admin): array {
        return [
            'UserID' => (int)$admin['UserID'],
            'FullName' => $admin['FullName'] ?? '',
            'Username' => $admin['Username'] ?? '',
            'Email' => $admin['Email'] ?? '',
            'ProfilePictureUrl' => $admin['ProfilePictureUrl'] ?? '',
            'Bio' => $admin['Bio'] ?? '',
            'RoleName' => $admin['RoleName'] ?? '',
            'CreatedAt' => $admin['CreatedAt'] ?? '',
            'IsActive' => isset($admin['IsActive']) ? (int)$admin['IsActive'] : 0
        ];
    }

    public function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $adminUserId = $this->currentAdminId();
        if (!$adminUserId) {
            return;
        }

        try {
            $this->adminProfileModel->addAdminLog($adminUserId, $action, $targetType, $targetId, $description);
        } catch (Exception $e) {
            // Logging must not break the admin action itself.
        }
    }

    public function mergeReportIds(array ...$sets): array {
        // Gộp các ReportID bị ảnh hưởng để frontend chỉ cập nhật đúng các dòng cần đổi.
        $merged = [];
        foreach ($sets as $set) {
            foreach ($set as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    $merged[$id] = $id;
                }
            }
        }
        return array_values($merged);
    }

    public function saveAdminAvatar(int $adminUserId): string {
        // Kiểm tra file upload kỹ hơn vì avatar là dữ liệu người dùng gửi lên server.
        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('Vui lòng chọn ảnh avatar.');
        }

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload avatar thất bại.');
        }

        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Avatar không được vượt quá 5MB.');
        }

        $extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Avatar chỉ hỗ trợ jpg, jpeg, png hoặc webp.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['avatar']['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new Exception('File avatar không đúng định dạng ảnh hợp lệ.');
        }

        $uploadDir = app_uploads_root('avatars/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'admin_' . $adminUserId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetFile = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            throw new Exception('Không thể lưu avatar lên server.');
        }

        return 'Public/uploads/avatars/' . $fileName;
    }

    // --- Trang dashboard và admin profile ---

    public function index() {
        if (!$this->isAdmin()) {
            header('Location: ' . app_url('login'));
            exit();
        }

        try {
            $stats = $this->adminStatsModel->getOverviewStats();
            $reports = $this->adminReportModel->getReportsList();
            $members = $this->adminMemberModel->getMembersList();
            $roles = $this->adminMemberModel->getAllRoles();
            $currentAdminId = $this->currentAdminId();
            $currentAdmin = $this->adminProfileModel->getAdminProfileById($currentAdminId);
        } catch (Exception $e) {
            $stats = [
                'users' => 0,
                'reports' => 0,
                'posts' => 0,
                'activity' => '0%',
                'totalUsers' => 0,
                'activeUsers' => 0,
                'lockedUsers' => 0,
                'totalPosts' => 0,
                'visiblePosts' => 0,
                'hiddenPosts' => 0,
                'totalComments' => 0,
                'hiddenComments' => 0,
                'pendingReports' => 0,
                'totalHashtags' => 0,
                'lastUpdated' => ''
            ];
            $reports = [];
            $members = [];
            $roles = [];
            $currentAdminId = $this->currentAdminId();
            $currentAdmin = null;
        }

        require_once __DIR__ . '/../Views/admin/index.php';
    }

    public function profile(): void {
        if (!$this->isAdmin()) {
            header('Location: ' . app_url('login'));
            exit();
        }

        $currentAdminId = $this->currentAdminId();
        $admin = $this->adminProfileModel->getAdminProfileById($currentAdminId);
        if (!$admin) {
            header('Location: ' . app_url('login'));
            exit();
        }

        $logActions = $this->adminProfileModel->getAdminLogActions($currentAdminId);
        require_once __DIR__ . '/../Views/admin/admin-profile.php';
    }

    // --- API cập nhật thông tin admin ---

    public function getAdminProfile(): void {
        $this->adminProfileController->getAdminProfile();
    }
    public function updateAdminFullName(): void {
        $this->adminProfileController->updateAdminFullName();
    }
    public function updateAdminBio(): void {
        $this->adminProfileController->updateAdminBio();
    }
    public function updateAdminAvatar(): void {
        $this->adminProfileController->updateAdminAvatar();
    }
    public function changeAdminPassword(): void {
        $this->adminProfileController->changeAdminPassword();
    }
    // --- Admin logs và thống kê dashboard ---

    public function adminLogs(): void {
        $this->adminProfileController->adminLogs();
    }
    public function overviewStats(): void {
        $this->adminStatsController->overviewStats();
    }
    public function overviewDetail(): void {
        $this->adminStatsController->overviewDetail();
    }
    public function statisticsRankings(): void {
        $this->adminStatsController->statisticsRankings();
    }
    public function statisticsCharts(): void {
        $this->adminStatsController->statisticsCharts();
    }
    public function statisticsInsights(): void {
        $this->adminStatsController->statisticsInsights();
    }
    // --- Quản lý thành viên ---

    public function listMembers(): void {
        $this->adminMemberController->listMembers();
    }
    // --- Quản lý thông báo ---

    public function listNotifications(): void {
        $this->adminNotificationController->listNotifications();
    }
    public function getNotificationDetail(): void {
        $this->adminNotificationController->getNotificationDetail();
    }
    public function deleteNotification(): void {
        $this->adminNotificationController->deleteNotification();
    }
    public function searchNotificationReceivers(): void {
        $this->adminNotificationController->searchNotificationReceivers();
    }
    public function sendSystemNotification(): void {
        $this->adminNotificationController->sendSystemNotification();
    }
    // --- Kiểm duyệt báo cáo ---

    public function processReport(): void {
        $this->adminReportController->processReport();
    }
    public function getReportDetail(): void {
        $this->adminReportController->getReportDetail();
    }
    public function updateUserRole(): void {
        $this->adminMemberController->updateUserRole();
    }
    public function toggleUserActive(): void {
        $this->adminMemberController->toggleUserActive();
    }
    // --- Quản lý nội dung: bài viết, bình luận, hashtag ---

    public function listContentPosts(): void {
        $this->adminContentController->listContentPosts();
    }
    public function getContentPostDetail(): void {
        $this->adminContentController->getContentPostDetail();
    }
    public function toggleContentPostHidden(): void {
        $this->adminContentController->toggleContentPostHidden();
    }
    public function deleteContentPost(): void {
        $this->adminContentController->deleteContentPost();
    }
    public function listContentComments(): void {
        $this->adminContentController->listContentComments();
    }
    public function getContentCommentDetail(): void {
        $this->adminContentController->getContentCommentDetail();
    }
    public function toggleContentCommentHidden(): void {
        $this->adminContentController->toggleContentCommentHidden();
    }
    public function deleteContentComment(): void {
        $this->adminContentController->deleteContentComment();
    }
    public function listContentHashtags(): void {
        $this->adminContentController->listContentHashtags();
    }
    public function toggleContentHashtagHidden(): void {
        $this->adminContentController->toggleContentHashtagHidden();
    }
    public function deleteContentHashtag(): void {
        $this->adminContentController->deleteContentHashtag();
    }
}

// Router nhỏ cho các action AJAX của trang admin.
if (isset($_GET['action'])) {
    $controller = new AdminController();

    $routes = [
        'processReport' => 'processReport',
        'overviewStats' => 'overviewStats',
        'overviewDetail' => 'overviewDetail',
        'statisticsRankings' => 'statisticsRankings',
        'statisticsCharts' => 'statisticsCharts',
        'statisticsInsights' => 'statisticsInsights',
        'getReportDetail' => 'getReportDetail',
        'updateUserRole' => 'updateUserRole',
        'toggleUserActive' => 'toggleUserActive',
        'listMembers' => 'listMembers',
        'listNotifications' => 'listNotifications',
        'getNotificationDetail' => 'getNotificationDetail',
        'deleteNotification' => 'deleteNotification',
        'searchNotificationReceivers' => 'searchNotificationReceivers',
        'sendSystemNotification' => 'sendSystemNotification',
        'listContentPosts' => 'listContentPosts',
        'getContentPostDetail' => 'getContentPostDetail',
        'toggleContentPostHidden' => 'toggleContentPostHidden',
        'deleteContentPost' => 'deleteContentPost',
        'listContentComments' => 'listContentComments',
        'getContentCommentDetail' => 'getContentCommentDetail',
        'toggleContentCommentHidden' => 'toggleContentCommentHidden',
        'deleteContentComment' => 'deleteContentComment',
        'listContentHashtags' => 'listContentHashtags',
        'toggleContentHashtagHidden' => 'toggleContentHashtagHidden',
        'deleteContentHashtag' => 'deleteContentHashtag',
        'getAdminProfile' => 'getAdminProfile',
        'updateAdminFullName' => 'updateAdminFullName',
        'updateAdminBio' => 'updateAdminBio',
        'updateAdminAvatar' => 'updateAdminAvatar',
        'changeAdminPassword' => 'changeAdminPassword',
        'adminLogs' => 'adminLogs'
    ];

    $action = $_GET['action'];
    if (isset($routes[$action])) {
        $controller->{$routes[$action]}();
    }
}
?>
