<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminReportModel;
use App\Models\AdminNotificationModel;
use App\Models\AdminMemberModel;
use Exception;

class AdminReportController {
    private AdminController $main;
    private AdminReportModel $adminReportModel;
    private AdminNotificationModel $adminNotificationModel;
    private AdminMemberModel $adminMemberModel;

    public function __construct(AdminController $main, AdminReportModel $adminReportModel, AdminNotificationModel $adminNotificationModel, AdminMemberModel $adminMemberModel) {
        $this->main = $main;
        $this->adminReportModel = $adminReportModel;
        $this->adminNotificationModel = $adminNotificationModel;
        $this->adminMemberModel = $adminMemberModel;
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

    private function intParam(string $name, string $method = 'GET'): ?int {
        return $this->main->intParam($name, $method);
    }

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $this->main->logAdminAction($action, $targetType, $targetId, $description);
    }

    private function mergeReportIds(array ...$sets): array {
        return $this->main->mergeReportIds(...$sets);
    }

    public function processReport(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
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

        $report = $this->adminReportModel->getReportById($reportId);
        if (!$report) {
            $this->jsonResponse(false, 'Báo cáo không tồn tại.');
            return;
        }

        try {
            $adminUserId = $this->currentAdminId();
            $updatedReports = [];
            // Mỗi action report có cách xử lý riêng nhưng đều trả ReportID để frontend cập nhật UI.
            if ($action === 'ignore') {
                $this->adminReportModel->markReportResolved($reportId, $adminNote);
                $updatedReports = [$reportId];
                $msg = 'Báo cáo đã bị bỏ qua.';
            } elseif ($action === 'hide') {
                $hidden = false;
                if (!empty($report['PostID'])) {
                    $this->adminReportModel->hidePostById((int)$report['PostID']);
                    // Khi một nội dung đã bị ẩn, các report pending liên quan cũng được hoàn tất.
                    $updatedReports = $this->mergeReportIds(
                        $updatedReports,
                        $this->adminReportModel->resolvePendingReportsByPostId((int)$report['PostID'], 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.')
                    );
                    $hidden = true;
                }
                if (!empty($report['CommentID'])) {
                    $this->adminReportModel->hideCommentById((int)$report['CommentID']);
                    $updatedReports = $this->mergeReportIds(
                        $updatedReports,
                        $this->adminReportModel->resolvePendingReportsByCommentId((int)$report['CommentID'], 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.')
                    );
                    $hidden = true;
                }
                $this->adminReportModel->markReportResolved($reportId, $adminNote);
                $updatedReports = $this->mergeReportIds($updatedReports, [$reportId]);
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminNotificationModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ContentHidden');
                }
                $msg = $hidden ? 'Nội dung đã được ẩn và báo cáo được đánh dấu hoàn tất.' : 'Không có nội dung để ẩn; báo cáo đã được đánh dấu hoàn tất.';
            } else {
                $this->adminReportModel->markReportResolved($reportId, $adminNote);
                $updatedReports = [$reportId];
                if (!empty($report['ReportedUserID']) && $adminUserId) {
                    $this->adminNotificationModel->createNotificationByType((int)$report['ReportedUserID'], $adminUserId, 'ReportWarning');
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
            $detail = $this->adminReportModel->getReportDetailById($reportId);
            if (!$detail) {
                $this->jsonResponse(false, 'Không tìm thấy báo cáo');
                return;
            }

            $this->jsonResponse(true, 'Lấy chi tiết báo cáo thành công', $detail);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy chi tiết báo cáo.');
        }
    }

}
?>
