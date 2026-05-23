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
        }

        require_once __DIR__ . '/../Views/admin/index.php';
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
            $this->jsonResponse(true, $isHidden === 1 ? 'Đã ẩn bài viết.' : 'Đã hiện lại bài viết.', ['post' => $post]);
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
            if (!$this->adminModel->deleteAdminContentPost($postId, $this->currentAdminId())) {
                $this->jsonResponse(false, 'Bài viết không tồn tại.');
                return;
            }
            $this->jsonResponse(true, 'Đã xóa bài viết.', ['PostID' => $postId]);
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
            $this->jsonResponse(true, $isHidden === 1 ? 'Đã ẩn bình luận.' : 'Đã hiện lại bình luận.', ['comment' => $comment]);
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
            $deletedCommentIds = $this->adminModel->deleteAdminContentComment($commentId, $this->currentAdminId());
            if (!$deletedCommentIds) {
                $this->jsonResponse(false, 'Bình luận không tồn tại.');
                return;
            }
            $this->jsonResponse(true, 'Đã xóa bình luận.', [
                'CommentID' => $commentId,
                'DeletedCommentIDs' => $deletedCommentIds
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
        'statisticsRankings' => 'statisticsRankings',
        'statisticsCharts' => 'statisticsCharts',
        'statisticsInsights' => 'statisticsInsights',
        'getReportDetail' => 'getReportDetail',
        'updateUserRole' => 'updateUserRole',
        'toggleUserActive' => 'toggleUserActive',
        'listMembers' => 'listMembers',
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
        'deleteContentHashtag' => 'deleteContentHashtag'
    ];

    $action = $_GET['action'];
    if (isset($routes[$action])) {
        $controller->{$routes[$action]}();
    }
}
?>
