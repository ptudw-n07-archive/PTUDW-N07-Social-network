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
            if (is_array($data) && array_key_exists('updatedReports', $data)) {
                $response['updatedReports'] = $data['updatedReports'];
            }
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

    private function intParam(string $name, string $method = 'GET'): ?int {
        $source = $method === 'POST' ? $_POST : $_GET;
        if (!isset($source[$name]) || !filter_var($source[$name], FILTER_VALIDATE_INT)) {
            return null;
        }

        $value = (int)$source[$name];
        return $value > 0 ? $value : null;
    }

    private function intPayloadParam(array $payload, string $name): ?int {
        if (!isset($payload[$name]) || !filter_var($payload[$name], FILTER_VALIDATE_INT)) {
            return null;
        }

        $value = (int)$payload[$name];
        return $value > 0 ? $value : null;
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

    private function adminProfilePayload(array $admin): array {
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

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $adminUserId = $this->currentAdminId();
        if (!$adminUserId) {
            return;
        }

        try {
            $this->adminModel->addAdminLog($adminUserId, $action, $targetType, $targetId, $description);
        } catch (Exception $e) {
            // Logging must not break the admin action itself.
        }
    }

    private function mergeReportIds(array ...$sets): array {
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

    private function saveAdminAvatar(int $adminUserId): string {
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

    public function index() {
        if (!$this->isAdmin()) {
            header('Location: ' . app_url('App/Views/auth/login.php'));
            exit();
        }

        try {
            $stats = $this->adminModel->getOverviewStats();
            $reports = $this->adminModel->getReportsList();
            $members = $this->adminModel->getMembersList();
            $roles = $this->adminModel->getAllRoles();
            $currentAdminId = $this->currentAdminId();
            $currentAdmin = $this->adminModel->getAdminProfileById($currentAdminId);
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
            header('Location: ' . app_url('App/Views/auth/login.php'));
            exit();
        }

        $currentAdminId = $this->currentAdminId();
        $admin = $this->adminModel->getAdminProfileById($currentAdminId);
        if (!$admin) {
            header('Location: ' . app_url('App/Views/auth/login.php'));
            exit();
        }

        $logActions = $this->adminModel->getAdminLogActions($currentAdminId);
        require_once __DIR__ . '/../Views/admin/admin-profile.php';
    }

    public function getAdminProfile(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $admin = $this->adminModel->getAdminProfileById($this->currentAdminId());
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
            if (!$this->adminModel->updateAdminFullName($adminUserId, $fullName)) {
                $this->jsonResponse(false, 'Không thể cập nhật FullName.');
                return;
            }

            $_SESSION['user_name'] = $fullName;
            $admin = $this->adminModel->getAdminProfileById($adminUserId);
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
            if (!$this->adminModel->updateAdminBio($adminUserId, $bio)) {
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
            if (!$this->adminModel->updateAdminAvatar($adminUserId, $avatarPath)) {
                $this->jsonResponse(false, 'Không thể cập nhật avatar.');
                return;
            }

            $_SESSION['avatar'] = $avatarPath;
            $_SESSION['ProfilePictureUrl'] = $avatarPath;
            $admin = $this->adminModel->getAdminProfileById($adminUserId);
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

        if (strlen($newPassword) < 8) {
            $this->jsonResponse(false, 'Mật khẩu mới phải có ít nhất 8 ký tự.');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(false, 'Mật khẩu mới và xác nhận không khớp.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        $currentHash = $this->adminModel->getUserPasswordHash($adminUserId);
        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            $this->jsonResponse(false, 'Mật khẩu hiện tại không đúng.');
            return;
        }

        try {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if (!$this->adminModel->updateAdminPassword($adminUserId, $newHash)) {
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
            $logs = $this->adminModel->getAdminLogs($adminUserId, $_GET['keyword'] ?? '', $_GET['actionFilter'] ?? '', 50);
            $actions = $this->adminModel->getAdminLogActions($adminUserId);
            $this->jsonResponse(true, 'Lấy admin logs thành công', [
                'logs' => $logs,
                'actions' => $actions
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy admin logs.');
        }
    }

    public function overviewStats(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy thống kê tổng quan thành công', $this->adminModel->getOverviewStats());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy thống kê tổng quan.');
        }
    }

    public function overviewDetail(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị viên.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $metric = isset($_GET['metric']) ? trim((string)$_GET['metric']) : '';
        try {
            $detail = $this->adminModel->getOverviewDetail($metric);
            if ($detail === null) {
                echo json_encode(['success' => false, 'message' => 'Metric không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'success' => true,
                'title' => $detail['title'],
                'columns' => $detail['columns'],
                'data' => $detail['data']
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Không thể lấy chi tiết tổng quan.'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function statisticsRankings(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            if (!in_array($limit, [5, 10, 15, 20], true)) {
                $limit = 5;
            }

            $this->jsonResponse(true, 'Lấy top ranking thành công', $this->adminModel->getStatisticsTopRankings($limit));
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy top ranking.');
        }
    }

    public function statisticsCharts(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy dữ liệu biểu đồ thành công', $this->adminModel->getStatisticsChartData());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy dữ liệu biểu đồ.');
        }
    }

    public function statisticsInsights(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy activity insights thành công', $this->adminModel->getStatisticsActivityInsights());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy activity insights.');
        }
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

    public function listNotifications(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $notifications = $this->adminModel->getAdminNotifications(
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
            $notification = $this->adminModel->getAdminNotificationDetail($notificationId);
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

        $payload = $this->jsonPayload();
        $notificationId = $this->intPayloadParam($payload, 'NotificationID');
        if (!$notificationId) {
            $this->jsonResponse(false, 'NotificationID không hợp lệ.');
            return;
        }

        try {
            if (!$this->adminModel->deleteAdminNotification($notificationId)) {
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

            $users = $this->adminModel->searchNotificationReceivers($keyword, 20);
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

        $payload = $this->jsonPayload();
        $message = trim((string)($payload['message'] ?? $payload['Message'] ?? ''));
        $sendAll = !empty($payload['sendToAll']) || !empty($payload['SendAll']);

        if ($message === '' || mb_strlen($message) > 1000) {
            $this->jsonResponse(false, 'Message không được rỗng và tối đa 1000 ký tự.');
            return;
        }

        $adminUserId = $this->currentAdminId();
        try {
            if ($sendAll) {
                $count = $this->adminModel->createSystemNotificationsForActiveUsers($adminUserId, $message);
                $this->logAdminAction('SendSystemNotification', 'Notification', 0, 'Gửi thông báo hệ thống cho ' . $count . ' thành viên.');
                $this->jsonResponse(true, 'Gửi thông báo hệ thống thành công', ['sentCount' => $count]);
                return;
            }

            $receiverUserId = $this->intPayloadParam($payload, 'receiverUserId') ?? $this->intPayloadParam($payload, 'ReceiverUserID');
            if (!$receiverUserId) {
                $this->jsonResponse(false, 'Vui lòng chọn người nhận.');
                return;
            }

            if (!$this->adminModel->getActiveNotificationReceiverById($receiverUserId)) {
                $this->jsonResponse(false, 'Người nhận không tồn tại hoặc đang bị khóa.');
                return;
            }

            if (!$this->adminModel->createSystemNotification($receiverUserId, $adminUserId, $message)) {
                $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
                return;
            }

            $this->logAdminAction('SendSystemNotification', 'Notification', $receiverUserId, 'Gửi thông báo hệ thống cho UserID #' . $receiverUserId . '.');
            $this->jsonResponse(true, 'Gửi thông báo hệ thống thành công', ['sentCount' => 1]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể gửi thông báo hệ thống.');
        }
    }

    public function processReport(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $reportId = isset($_POST['reportId']) ? (int)$_POST['reportId'] : null;
        $action = $_POST['action'] ?? null;
        $adminNote = trim((string)($_POST['adminNote'] ?? ''));

        if (!$reportId || !$action) {
            $this->jsonResponse(false, 'Thiếu thông tin ReportID hoặc hành động.');
            return;
        }

        $allowed = ['ignore', 'hide', 'warn'];
        if (!in_array($action, $allowed, true)) {
            $this->jsonResponse(false, 'Hành động không hợp lệ.');
            return;
        }

        if (in_array($action, ['hide', 'warn'], true) && $adminNote === '') {
            $this->jsonResponse(false, 'Vui lòng nhập ghi chú xử lý.');
            return;
        }

        $report = $this->adminModel->getReportById($reportId);
        if (!$report) {
            $this->jsonResponse(false, 'Báo cáo không tồn tại.');
            return;
        }

        try {
            $adminUserId = $this->currentAdminId();
            $updatedReports = [];
            if ($action === 'ignore') {
                $this->adminModel->markReportResolved($reportId, $adminNote);
                $updatedReports = [$reportId];
                $msg = 'Báo cáo đã bị bỏ qua.';
            } elseif ($action === 'hide') {
                $hidden = false;
                if (!empty($report['PostID'])) {
                    $this->adminModel->hidePostById((int)$report['PostID']);
                    $updatedReports = $this->mergeReportIds(
                        $updatedReports,
                        $this->adminModel->resolvePendingReportsByPostId((int)$report['PostID'], 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.')
                    );
                    $hidden = true;
                }
                if (!empty($report['CommentID'])) {
                    $this->adminModel->hideCommentById((int)$report['CommentID']);
                    $updatedReports = $this->mergeReportIds(
                        $updatedReports,
                        $this->adminModel->resolvePendingReportsByCommentId((int)$report['CommentID'], 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.')
                    );
                    $hidden = true;
                }
                $this->adminModel->markReportResolved($reportId, $adminNote);
                $updatedReports = $this->mergeReportIds($updatedReports, [$reportId]);
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ContentHidden');
                }
                $msg = $hidden ? 'Nội dung đã được ẩn và báo cáo được đánh dấu hoàn tất.' : 'Không có nội dung để ẩn; báo cáo đã được đánh dấu hoàn tất.';
            } else {
                $this->adminModel->markReportResolved($reportId, $adminNote);
                $updatedReports = [$reportId];
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ReportWarning');
                }
                $msg = 'Người dùng đã được cảnh cáo; báo cáo đã xử lý.';
            }

            $this->logAdminAction('ProcessReport', 'Report', $reportId, 'Xử lý report #' . $reportId . ' với action ' . $action . '.');
            if ($action === 'hide' && !empty($updatedReports)) {
                $targetType = !empty($report['CommentID']) ? 'Comment' : (!empty($report['PostID']) ? 'Post' : 'Report');
                $targetId = !empty($report['CommentID']) ? (int)$report['CommentID'] : (!empty($report['PostID']) ? (int)$report['PostID'] : $reportId);
                $this->logAdminAction('ModerateReport', $targetType, $targetId, 'Ẩn nội dung và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, $msg, [
                'reportId' => $reportId,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi xử lý báo cáo.');
        }
    }

    public function getReportDetail(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $reportId = isset($_GET['reportId']) ? (int)$_GET['reportId'] : null;
        if (!$reportId) {
            $this->jsonResponse(false, 'Thiếu ReportID.');
            return;
        }

        try {
            $detail = $this->adminModel->getReportDetailById($reportId);
            if (!$detail) {
                $this->jsonResponse(false, 'Không tìm thấy báo cáo');
                return;
            }

            $this->jsonResponse(true, 'Lấy chi tiết báo cáo thành công', $detail);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy chi tiết báo cáo.');
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
            $updatedReports = [];
            $result = $this->adminModel->updateUserActiveStatus($userId, $isActive);
            if (!$result) {
                $this->jsonResponse(false, 'Cập nhật trạng thái tài khoản thất bại.');
                return;
            }

            if ($currentAdminId) {
                $typeName = $isActive === 1 ? 'AccountUnlocked' : 'AccountLocked';
                $this->adminModel->createNotificationByType($userId, $currentAdminId, $typeName);
            }

            if ($isActive === 0) {
                $updatedReports = $this->adminModel->resolvePendingReportsByReportedUserId($userId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
            }

            $updatedUser = $this->adminModel->getUserById($userId);
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

    public function listContentPosts(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $posts = $this->adminModel->getAdminContentPosts($_GET['keyword'] ?? '', $_GET['status'] ?? '', $_GET['privacy'] ?? '');
            $this->jsonResponse(true, 'Lấy danh sách bài viết thành công', ['posts' => $posts]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách bài viết.');
        }
    }

    public function getContentPostDetail(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $postId = $this->intParam('postId');
        if (!$postId) {
            $this->jsonResponse(false, 'PostID không hợp lệ.');
            return;
        }

        try {
            $post = $this->adminModel->getAdminContentPostDetail($postId);
            if (!$post) {
                $this->jsonResponse(false, 'Bài viết không tồn tại.');
                return;
            }
            $this->jsonResponse(true, 'Lấy chi tiết bài viết thành công', $post);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy chi tiết bài viết.');
        }
    }

    public function toggleContentPostHidden(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $postId = $this->intPayloadParam($payload, 'PostID');
        $isHidden = isset($payload['IsHidden']) ? (int)$payload['IsHidden'] : null;
        if (!$postId || !in_array($isHidden, [0, 1], true)) {
            $this->jsonResponse(false, 'Thiếu PostID hoặc trạng thái ẩn/hiện.');
            return;
        }

        try {
            $post = $this->adminModel->updatePostHiddenStatus($postId, $isHidden, $this->currentAdminId());
            if (!$post) {
                $this->jsonResponse(false, 'Bài viết không tồn tại.');
                return;
            }
            $updatedReports = [];
            if ($isHidden === 1) {
                $updatedReports = $this->adminModel->resolvePendingReportsByPostId($postId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
            }
            $this->logAdminAction($isHidden === 1 ? 'HidePost' : 'ShowPost', 'Post', $postId, ($isHidden === 1 ? 'Ẩn' : 'Hiện') . ' bài viết #' . $postId . '.');
            if ($isHidden === 1 && !empty($updatedReports)) {
                $this->logAdminAction('AutoResolveReports', 'Post', $postId, 'Ẩn bài viết và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, $isHidden === 1 ? 'Đã ẩn bài viết.' : 'Đã hiện lại bài viết.', [
                'post' => $post,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể cập nhật trạng thái bài viết.');
        }
    }

    public function deleteContentPost(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $postId = $this->intPayloadParam($payload, 'PostID');
        if (!$postId) {
            $this->jsonResponse(false, 'PostID không hợp lệ.');
            return;
        }

        try {
            $updatedReports = $this->adminModel->getPendingReportIdsByPostId($postId);
            if (!$this->adminModel->deleteAdminContentPost($postId, $this->currentAdminId())) {
                $this->jsonResponse(false, 'Bài viết không tồn tại.');
                return;
            }
            $this->logAdminAction('DeletePost', 'Post', $postId, 'Xóa bài viết #' . $postId . '.');
            if (!empty($updatedReports)) {
                $this->logAdminAction('AutoResolveReports', 'Post', $postId, 'Xóa bài viết và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, 'Đã xóa bài viết.', [
                'PostID' => $postId,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể xóa bài viết. Transaction đã rollback.');
        }
    }

    public function listContentComments(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $comments = $this->adminModel->getAdminContentComments($_GET['keyword'] ?? '', $_GET['status'] ?? '');
            $this->jsonResponse(true, 'Lấy danh sách bình luận thành công', ['comments' => $comments]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách bình luận.');
        }
    }

    public function getContentCommentDetail(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $commentId = $this->intParam('commentId');
        if (!$commentId) {
            $this->jsonResponse(false, 'CommentID không hợp lệ.');
            return;
        }

        try {
            $comment = $this->adminModel->getAdminContentCommentDetail($commentId);
            if (!$comment) {
                $this->jsonResponse(false, 'Bình luận không tồn tại.');
                return;
            }
            $this->jsonResponse(true, 'Lấy chi tiết bình luận thành công', $comment);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy chi tiết bình luận.');
        }
    }

    public function toggleContentCommentHidden(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $commentId = $this->intPayloadParam($payload, 'CommentID');
        $isHidden = isset($payload['IsHidden']) ? (int)$payload['IsHidden'] : null;
        if (!$commentId || !in_array($isHidden, [0, 1], true)) {
            $this->jsonResponse(false, 'Thiếu CommentID hoặc trạng thái ẩn/hiện.');
            return;
        }

        try {
            $comment = $this->adminModel->updateCommentHiddenStatus($commentId, $isHidden, $this->currentAdminId());
            if (!$comment) {
                $this->jsonResponse(false, 'Bình luận không tồn tại.');
                return;
            }
            $updatedReports = [];
            if ($isHidden === 1) {
                $updatedReports = $this->adminModel->resolvePendingReportsByCommentId($commentId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
            }
            $this->logAdminAction($isHidden === 1 ? 'HideComment' : 'ShowComment', 'Comment', $commentId, ($isHidden === 1 ? 'Ẩn' : 'Hiện') . ' bình luận #' . $commentId . '.');
            if ($isHidden === 1 && !empty($updatedReports)) {
                $this->logAdminAction('AutoResolveReports', 'Comment', $commentId, 'Ẩn bình luận và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, $isHidden === 1 ? 'Đã ẩn bình luận.' : 'Đã hiện lại bình luận.', [
                'comment' => $comment,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể cập nhật trạng thái bình luận.');
        }
    }

    public function deleteContentComment(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $commentId = $this->intPayloadParam($payload, 'CommentID');
        if (!$commentId) {
            $this->jsonResponse(false, 'CommentID không hợp lệ.');
            return;
        }

        try {
            $updatedReports = $this->adminModel->getPendingReportIdsByCommentId($commentId);
            $deletedCommentIds = $this->adminModel->deleteAdminContentComment($commentId, $this->currentAdminId());
            if (!$deletedCommentIds) {
                $this->jsonResponse(false, 'Bình luận không tồn tại.');
                return;
            }
            $this->logAdminAction('DeleteComment', 'Comment', $commentId, 'Xóa bình luận #' . $commentId . '.');
            if (!empty($updatedReports)) {
                $this->logAdminAction('AutoResolveReports', 'Comment', $commentId, 'Xóa bình luận và tự động resolve các report liên quan.');
            }
            $this->jsonResponse(true, 'Đã xóa bình luận.', [
                'CommentID' => $commentId,
                'DeletedCommentIDs' => $deletedCommentIds,
                'updatedReports' => $updatedReports
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể xóa bình luận. Transaction đã rollback.');
        }
    }

    public function listContentHashtags(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $hashtags = $this->adminModel->getAdminContentHashtags($_GET['keyword'] ?? '', $_GET['status'] ?? '');
            $this->jsonResponse(true, 'Lấy danh sách hashtag thành công', ['hashtags' => $hashtags]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy danh sách hashtag.');
        }
    }

    public function toggleContentHashtagHidden(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $hashtagId = $this->intPayloadParam($payload, 'HashtagID');
        $isHidden = isset($payload['IsHidden']) ? (int)$payload['IsHidden'] : null;
        if (!$hashtagId || !in_array($isHidden, [0, 1], true)) {
            $this->jsonResponse(false, 'Thiếu HashtagID hoặc trạng thái ẩn/hiện.');
            return;
        }

        try {
            $hashtag = $this->adminModel->updateHashtagHiddenStatus($hashtagId, $isHidden);
            if (!$hashtag) {
                $this->jsonResponse(false, 'Hashtag không tồn tại.');
                return;
            }
            $this->logAdminAction($isHidden === 1 ? 'HideHashtag' : 'ShowHashtag', 'Hashtag', $hashtagId, ($isHidden === 1 ? 'Ẩn' : 'Hiện') . ' hashtag #' . $hashtagId . '.');
            $this->jsonResponse(true, $isHidden === 1 ? 'Đã ẩn hashtag.' : 'Đã hiện lại hashtag.', ['hashtag' => $hashtag]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể cập nhật trạng thái hashtag.');
        }
    }

    public function deleteContentHashtag(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        $payload = $this->jsonPayload();
        $hashtagId = $this->intPayloadParam($payload, 'HashtagID');
        if (!$hashtagId) {
            $this->jsonResponse(false, 'HashtagID không hợp lệ.');
            return;
        }

        try {
            if (!$this->adminModel->deleteAdminContentHashtag($hashtagId)) {
                $this->jsonResponse(false, 'Hashtag không tồn tại.');
                return;
            }
            $this->logAdminAction('DeleteHashtag', 'Hashtag', $hashtagId, 'Xóa hashtag #' . $hashtagId . '.');
            $this->jsonResponse(true, 'Đã xóa hashtag.', ['HashtagID' => $hashtagId]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể xóa hashtag. Transaction đã rollback.');
        }
    }
}

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
