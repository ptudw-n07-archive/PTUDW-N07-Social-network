<?php
namespace App\Models;
use PDO; 
class AdminModel {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // 1. Lấy số liệu tổng quan (Thành viên, Báo cáo chờ, Tổng bài viết)
    public function getOverviewStats() {
        // Đếm số thành viên
        $u_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        $total_users = $u_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Đếm báo cáo chờ duyệt
        $r_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM reports WHERE Status = 'Pending'");
        $total_reports = $r_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Đếm tổng bài viết
        $p_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM posts");
        $total_posts = $p_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Tính tỷ lệ hoạt động (Hiệu suất xử lý báo cáo)
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

    // 2. Lấy danh sách báo cáo vi phạm
    public function getReportsList() {
          $query = "SELECT r.ReportID AS id, u.FullName AS user, u.ProfilePictureUrl AS avatar,
                                 CASE
                                     WHEN r.PostID IS NOT NULL THEN 'Bài viết'
                                     WHEN r.CommentID IS NOT NULL THEN 'Bình luận'
                                     ELSE 'Tài khoản'
                                 END AS type,
                                 r.Reason AS reason, r.Details AS details, r.PostID, r.CommentID, r.ReportedUserID, r.CreatedAt AS time,
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

    // 3. Lấy danh sách thành viên mạng xã hội
    public function getMembersList() {
        $query = "SELECT u.FullName AS name, u.ProfilePictureUrl AS avatar, r.RoleName AS role, 
                         DATE_FORMAT(u.CreatedAt, '%d/%m/%Y') AS joined, 
                         CASE 
                            WHEN u.Username = 'nguoi_tinh_mua_dong' THEN 'Đã khóa' 
                            ELSE 'Hoạt động' 
                         END AS status
                  FROM users u
                  JOIN roles r ON u.RoleID = r.RoleID
                  ORDER BY u.CreatedAt DESC";
                  
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt->bindParam(':receiverUserId', $receiverUserId, PDO::PARAM_INT);
        $stmt->bindParam(':senderUserId', $senderUserId, PDO::PARAM_INT);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->bindParam(':notificationTypeId', $notificationTypeId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>