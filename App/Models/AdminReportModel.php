<?php
namespace App\Models;

use PDO;

class AdminReportModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // --- Báo cáo và xử lý report ---

    public function getReportsList() {
        $query = "SELECT r.ReportID AS id, u.FullName AS user, u.ProfilePictureUrl AS avatar,
                         reporter.Username AS ReporterUsername,
                         reporter.FullName AS ReporterFullName,
                         CASE
                             WHEN r.PostID IS NOT NULL THEN 'Bài viết'
                             WHEN r.CommentID IS NOT NULL THEN 'Bình luận'
                             ELSE 'Tài khoản'
                         END AS type,
                         r.Reason AS reason, r.Details AS details, r.PostID, r.CommentID, r.ReportedUserID,
                         r.CreatedAt AS time,
                          CASE
                              WHEN r.Status = 'Pending' THEN 'Chờ duyệt'
                              ELSE 'Đã xử lý'
                          END AS status,
                          CASE
                              WHEN r.Status = 'Pending' THEN 'pending'
                              ELSE 'resolved'
                          END AS statusKey
                  FROM reports r
                  LEFT JOIN users u ON r.ReportedUserID = u.UserID
                  LEFT JOIN users reporter ON r.ReporterUserID = reporter.UserID
                  ORDER BY r.CreatedAt DESC";

        $stmt = $this->conn->query($query);
        return array_map(function ($row) {
            $row['reporter'] = $row['ReporterFullName'] ?: ($row['ReporterUsername'] ?: '');
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getReportById($reportId) {
        $sql = "SELECT * FROM reports WHERE ReportID = :reportId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getReportDetailById($reportId) {
        // Lấy đủ người báo cáo, người bị báo cáo và nội dung liên quan để admin xem trong modal.
        $sql = "SELECT
                    r.ReportID,
                    r.PostID AS ReportPostID,
                    r.CommentID,
                    r.ReportedUserID,
                    r.ReporterUserID,
                    r.Reason,
                    r.Details,
                    r.Status,
                    r.CreatedAt AS ReportCreatedAt,
                    reporter.UserID AS ReporterUserIDValue,
                    reporter.Username AS ReporterUsername,
                    reporter.FullName AS ReporterFullName,
                    reporter.Email AS ReporterEmail,
                    reported.UserID AS ReportedUserIDValue,
                    reported.Username AS ReportedUsername,
                    reported.FullName AS ReportedFullName,
                    reported.Email AS ReportedEmail,
                    reported.CreatedAt AS ReportedUserCreatedAt,
                    reported.IsActive AS ReportedUserIsActive,
                    rr.RoleName AS ReportedUserRoleName,
                    p.PostID,
                    p.Content AS PostContent,
                    p.CreatedAt AS PostCreatedAt,
                    post_author.UserID AS PostAuthorID,
                    post_author.Username AS PostAuthorUsername,
                    post_author.FullName AS PostAuthorFullName,
                    c.CommentID AS DetailCommentID,
                    c.Content AS CommentContent,
                    c.CreatedAt AS CommentCreatedAt,
                    comment_author.UserID AS CommentAuthorID,
                    comment_author.Username AS CommentAuthorUsername,
                    comment_author.FullName AS CommentAuthorFullName
                FROM reports r
                LEFT JOIN users reporter ON reporter.UserID = r.ReporterUserID
                LEFT JOIN users reported ON reported.UserID = r.ReportedUserID
                LEFT JOIN roles rr ON rr.RoleID = reported.RoleID
                LEFT JOIN comments c ON c.CommentID = r.CommentID
                LEFT JOIN users comment_author ON comment_author.UserID = c.UserID
                LEFT JOIN posts p ON p.PostID = COALESCE(r.PostID, c.PostID)
                LEFT JOIN users post_author ON post_author.UserID = p.UserID
                WHERE r.ReportID = :reportId
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $reportType = 'user';
        if (!empty($row['CommentID'])) {
            $reportType = 'comment';
        } elseif (!empty($row['ReportPostID'])) {
            $reportType = 'post';
        }

        $postId = !empty($row['PostID']) ? (int)$row['PostID'] : null;

        return [
            'ReportID' => (int)$row['ReportID'],
            'Reason' => $row['Reason'],
            'Details' => $row['Details'],
            'Status' => $row['Status'],
            'CreatedAt' => $row['ReportCreatedAt'],
            'reportType' => $reportType,
            'reporter' => [
                'UserID' => $row['ReporterUserIDValue'] !== null ? (int)$row['ReporterUserIDValue'] : null,
                'Username' => $row['ReporterUsername'],
                'FullName' => $row['ReporterFullName'],
                'Email' => $row['ReporterEmail']
            ],
            'reportedUser' => [
                'UserID' => $row['ReportedUserIDValue'] !== null ? (int)$row['ReportedUserIDValue'] : null,
                'Username' => $row['ReportedUsername'],
                'FullName' => $row['ReportedFullName'],
                'Email' => $row['ReportedEmail'],
                'RoleName' => $row['ReportedUserRoleName'],
                'CreatedAt' => $row['ReportedUserCreatedAt'],
                'IsActive' => $row['ReportedUserIsActive'] !== null ? (int)$row['ReportedUserIsActive'] : null
            ],
            'post' => $postId ? [
                'PostID' => $postId,
                'Content' => $row['PostContent'],
                'CreatedAt' => $row['PostCreatedAt'],
                'author' => [
                    'UserID' => $row['PostAuthorID'] !== null ? (int)$row['PostAuthorID'] : null,
                    'Username' => $row['PostAuthorUsername'],
                    'FullName' => $row['PostAuthorFullName']
                ]
            ] : null,
            'comment' => !empty($row['DetailCommentID']) ? [
                'CommentID' => (int)$row['DetailCommentID'],
                'Content' => $row['CommentContent'],
                'CreatedAt' => $row['CommentCreatedAt'],
                'author' => [
                    'UserID' => $row['CommentAuthorID'] !== null ? (int)$row['CommentAuthorID'] : null,
                    'Username' => $row['CommentAuthorUsername'],
                    'FullName' => $row['CommentAuthorFullName']
                ]
            ] : null,
            'images' => $postId ? $this->getPostImagesByPostId($postId) : []
        ];
    }

    public function getPostImagesByPostId($postId) {
        $sql = "SELECT ImageUrl FROM postimages WHERE PostID = :postId ORDER BY ImageUrl ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function markReportResolved($reportId, $adminNote = null) {
        $sql = "UPDATE reports SET Status = 'Resolved', AdminNote = :note, ResolvedAt = NOW() WHERE ReportID = :reportId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':note', $adminNote);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function resolvePendingReportsByPostId($postId, $adminNote): array {
        return $this->resolvePendingReports('PostID', $postId, $adminNote);
    }

    public function resolvePendingReportsByCommentId($commentId, $adminNote): array {
        return $this->resolvePendingReports('CommentID', $commentId, $adminNote);
    }

    public function resolvePendingReportsByReportedUserId($userId, $adminNote): array {
        return $this->resolvePendingReports('ReportedUserID', $userId, $adminNote);
    }

    public function getPendingReportIdsByPostId($postId): array {
        return $this->getPendingReportIds('PostID', $postId);
    }

    public function getPendingReportIdsByCommentId($commentId): array {
        return $this->getPendingReportIds('CommentID', $commentId);
    }

    public function getPendingReportIdsByReportedUserId($userId): array {
        return $this->getPendingReportIds('ReportedUserID', $userId);
    }

    private function getPendingReportIds($column, $value): array {
        // Chỉ cho phép các cột report đã định nghĩa sẵn để SQL động vẫn an toàn.
        $allowedColumns = ['PostID', 'CommentID', 'ReportedUserID'];
        if (!in_array($column, $allowedColumns, true)) {
            return [];
        }

        $sql = "SELECT ReportID FROM reports WHERE {$column} = :value AND Status = 'Pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':value', $value, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function resolvePendingReports($column, $value, $adminNote): array {
        $reportIds = $this->getPendingReportIds($column, $value);
        if (empty($reportIds)) {
            return [];
        }

        // Dùng transaction để nếu cập nhật report lỗi thì không bị trạng thái nửa chừng.
        $this->conn->beginTransaction();
        try {
            $sql = "UPDATE reports
                    SET Status = 'Resolved',
                        ResolvedAt = NOW(),
                        AdminNote = CASE
                            WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN :adminNote
                            ELSE AdminNote
                        END
                    WHERE {$column} = :value AND Status = 'Pending'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':adminNote', $adminNote, PDO::PARAM_STR);
            $stmt->bindValue(':value', $value, PDO::PARAM_INT);
            $stmt->execute();
            $this->conn->commit();
            return $reportIds;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function hidePostById($postId) {
        $sql = "UPDATE posts SET IsHidden = 1 WHERE PostID = :postId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function hideCommentById($commentId) {
        $sql = "UPDATE comments SET IsHidden = 1 WHERE CommentID = :commentId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
