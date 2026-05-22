<?php
namespace App\Models;

use PDO;

class AdminModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getOverviewStats() {
        $u_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        $total_users = $u_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $r_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM reports WHERE Status = 'Pending'");
        $total_reports = $r_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $p_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM posts");
        $total_posts = $p_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_report_total = $this->conn->query("SELECT COUNT(*) AS total FROM reports");
        $all_reports = $stmt_report_total->fetch(PDO::FETCH_ASSOC)['total'];

        if ($all_reports > 0) {
            $stmt_report_resolved = $this->conn->query("SELECT COUNT(*) AS total FROM reports WHERE Status = 'Resolved'");
            $resolved_reports = $stmt_report_resolved->fetch(PDO::FETCH_ASSOC)['total'];
            $activity_rate = round(($resolved_reports / $all_reports) * 100, 1) . '%';
        } else {
            $activity_rate = '100%';
        }

        return [
            'users' => number_format($total_users),
            'reports' => $total_reports,
            'posts' => number_format($total_posts),
            'activity' => $activity_rate
        ];
    }

    public function getReportsList() {
        $query = "SELECT r.ReportID AS id, u.FullName AS user, u.ProfilePictureUrl AS avatar,
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
                         END AS status
                  FROM reports r
                  LEFT JOIN users u ON r.ReportedUserID = u.UserID
                  ORDER BY r.CreatedAt DESC";

        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMembersList($keyword = '', $roleId = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(u.Username LIKE :keyword OR u.FullName LIKE :keyword OR u.Email LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($roleId !== '' && $roleId !== null) {
            $conditions[] = "u.RoleID = :roleId";
            $params[':roleId'] = (int)$roleId;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $query = "SELECT u.UserID, u.FullName, u.Username, u.Email,
                         COALESCE(NULLIF(u.FullName, ''), u.Username) AS name,
                         u.ProfilePictureUrl AS avatar, u.RoleID, u.IsActive, u.CreatedAt,
                         DATE_FORMAT(u.CreatedAt, '%d/%m/%Y') AS joined,
                         r.RoleName, r.RoleName AS role,
                         COALESCE(pc.PostCount, 0) AS PostCount,
                         COALESCE(rc.ReportCount, 0) AS ReportCount,
                         CASE WHEN u.IsActive = 1 THEN 'Hoạt động' ELSE 'Bị khóa' END AS status
                  FROM users u
                  JOIN roles r ON u.RoleID = r.RoleID
                  LEFT JOIN (
                      SELECT UserID, COUNT(PostID) AS PostCount
                      FROM posts
                      GROUP BY UserID
                  ) pc ON pc.UserID = u.UserID
                  LEFT JOIN (
                      SELECT ReportedUserID, COUNT(ReportID) AS ReportCount
                      FROM reports
                      GROUP BY ReportedUserID
                  ) rc ON rc.ReportedUserID = u.UserID
                  $whereSql
                  ORDER BY u.CreatedAt DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':roleId' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRoles() {
        $sql = "SELECT RoleID, RoleName FROM roles ORDER BY RoleID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUserRole($userId, $roleId) {
        $sql = "UPDATE users SET RoleID = :roleId WHERE UserID = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateUserActiveStatus($userId, $isActive) {
        $sql = "UPDATE users SET IsActive = :isActive WHERE UserID = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':isActive', $isActive, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getUserById($userId) {
        $sql = "SELECT u.UserID, u.FullName, u.Username, u.Email, u.RoleID, u.IsActive, u.CreatedAt,
                       r.RoleName,
                       COALESCE(pc.PostCount, 0) AS PostCount,
                       COALESCE(rc.ReportCount, 0) AS ReportCount
                FROM users u
                JOIN roles r ON u.RoleID = r.RoleID
                LEFT JOIN (
                    SELECT UserID, COUNT(PostID) AS PostCount
                    FROM posts
                    GROUP BY UserID
                ) pc ON pc.UserID = u.UserID
                LEFT JOIN (
                    SELECT ReportedUserID, COUNT(ReportID) AS ReportCount
                    FROM reports
                    GROUP BY ReportedUserID
                ) rc ON rc.ReportedUserID = u.UserID
                WHERE u.UserID = :userId
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRoleById($roleId) {
        $sql = "SELECT RoleID, RoleName FROM roles WHERE RoleID = :roleId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateReportStatus($reportId, $status) {
        $sql = "UPDATE reports SET Status = :status WHERE ReportID = :reportId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getReportById($reportId) {
        $sql = "SELECT * FROM reports WHERE ReportID = :reportId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markReportResolved($reportId, $adminNote = null) {
        $sql = "UPDATE reports SET Status = 'Resolved', AdminNote = :note, ResolvedAt = NOW() WHERE ReportID = :reportId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':note', $adminNote);
        $stmt->bindParam(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
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

    public function getNotificationTypeIdByName($typeName) {
        $sql = "SELECT NotificationTypeID FROM notificationtypes WHERE TypeName = :typeName LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':typeName', $typeName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function createNotification($receiverUserId, $senderUserId, $postId, $commentId, $notificationTypeId) {
        if (!$receiverUserId || !$senderUserId || !$notificationTypeId) {
            return false;
        }

        $sql = "INSERT INTO notifications (ReceiverUserID, SenderUserID, PostID, CommentID, NotificationTypeID, IsRead, CreatedAt)
                VALUES (:receiverUserId, :senderUserId, :postId, :commentId, :notificationTypeId, 0, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':receiverUserId', $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(':senderUserId', $senderUserId, PDO::PARAM_INT);
        $stmt->bindValue(':postId', $postId, $postId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':commentId', $commentId, $commentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':notificationTypeId', $notificationTypeId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function createNotificationByType($receiverUserId, $senderUserId, $typeName) {
        $typeId = $this->getNotificationTypeIdByName($typeName);
        if (!$typeId) {
            return false;
        }

        return $this->createNotification($receiverUserId, $senderUserId, null, null, (int)$typeId);
    }
}
?>
