<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminContentModel;
use App\Models\AdminReportModel;
use Exception;

class AdminContentController {
    private AdminController $main;
    private AdminContentModel $adminContentModel;
    private AdminReportModel $adminReportModel;

    public function __construct(AdminController $main, AdminContentModel $adminContentModel, AdminReportModel $adminReportModel) {
        $this->main = $main;
        $this->adminContentModel = $adminContentModel;
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

    private function intParam(string $name, string $method = 'GET'): ?int {
        return $this->main->intParam($name, $method);
    }

    private function intPayloadParam(array $payload, string $name): ?int {
        return $this->main->intPayloadParam($payload, $name);
    }

    private function currentAdminId(): ?int {
        return $this->main->currentAdminId();
    }

    private function logAdminAction(string $action, string $targetType, int $targetId, string $description): void {
        $this->main->logAdminAction($action, $targetType, $targetId, $description);
    }

    public function listContentPosts(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $posts = $this->adminContentModel->getAdminContentPosts($_GET['keyword'] ?? '', $_GET['status'] ?? '', $_GET['privacy'] ?? '');
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
            $post = $this->adminContentModel->getAdminContentPostDetail($postId);
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
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
            $post = $this->adminContentModel->updatePostHiddenStatus($postId, $isHidden, $this->currentAdminId());
            if (!$post) {
                $this->jsonResponse(false, 'Bài viết không tồn tại.');
                return;
            }
            $updatedReports = [];
            if ($isHidden === 1) {
                // Ẩn bài viết từ tab nội dung cũng đồng bộ trạng thái các report liên quan.
                $updatedReports = $this->adminReportModel->resolvePendingReportsByPostId($postId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $postId = $this->intPayloadParam($payload, 'PostID');
        if (!$postId) {
            $this->jsonResponse(false, 'PostID không hợp lệ.');
            return;
        }

        try {
            // Lưu lại các report pending trước khi xóa để frontend biết dòng nào cần cập nhật.
            $updatedReports = $this->adminReportModel->getPendingReportIdsByPostId($postId);
            if (!$this->adminContentModel->deleteAdminContentPost($postId, $this->currentAdminId())) {
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
            $comments = $this->adminContentModel->getAdminContentComments($_GET['keyword'] ?? '', $_GET['status'] ?? '');
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
            $comment = $this->adminContentModel->getAdminContentCommentDetail($commentId);
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
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
            $comment = $this->adminContentModel->updateCommentHiddenStatus($commentId, $isHidden, $this->currentAdminId());
            if (!$comment) {
                $this->jsonResponse(false, 'Bình luận không tồn tại.');
                return;
            }
            $updatedReports = [];
            if ($isHidden === 1) {
                // Ẩn bình luận cũng tự hoàn tất các report đang chờ của bình luận đó.
                $updatedReports = $this->adminReportModel->resolvePendingReportsByCommentId($commentId, 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.');
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $commentId = $this->intPayloadParam($payload, 'CommentID');
        if (!$commentId) {
            $this->jsonResponse(false, 'CommentID không hợp lệ.');
            return;
        }

        try {
            // Comment có thể có nhiều comment con, nên model trả về danh sách ID đã xóa.
            $updatedReports = $this->adminReportModel->getPendingReportIdsByCommentId($commentId);
            $deletedCommentIds = $this->adminContentModel->deleteAdminContentComment($commentId, $this->currentAdminId());
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
            $hashtags = $this->adminContentModel->getAdminContentHashtags($_GET['keyword'] ?? '', $_GET['status'] ?? '');
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
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
            $hashtag = $this->adminContentModel->updateHashtagHiddenStatus($hashtagId, $isHidden);
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

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->jsonResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        $payload = $this->jsonPayload();
        $hashtagId = $this->intPayloadParam($payload, 'HashtagID');
        if (!$hashtagId) {
            $this->jsonResponse(false, 'HashtagID không hợp lệ.');
            return;
        }

        try {
            if (!$this->adminContentModel->deleteAdminContentHashtag($hashtagId)) {
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
?>
