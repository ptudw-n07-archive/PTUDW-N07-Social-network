<?php
namespace App\Models; // ✨ Thêm dòng này vào đầu file AdminModel.php
use PDO; 
class AdminModel {
    private $conn;

    // Nhận kết nối PDO từ Database truyền vào
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // 1. Lấy số liệu tổng quan (Thành viên, Báo cáo chờ, Tổng bài viết)
    public function getOverviewStats() {
        // Đếm số thành viên
        $u_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM Users");
        $total_users = $u_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Đếm báo cáo chờ duyệt
        $r_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM Reports WHERE Status = 'Pending'");
        $total_reports = $r_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Đếm tổng bài viết
        $p_stmt = $this->conn->query("SELECT COUNT(*) AS total FROM Posts");
        $total_posts = $p_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Tính tỷ lệ hoạt động (Hiệu suất xử lý báo cáo)
        $stmt_report_total = $this->conn->query("SELECT COUNT(*) AS total FROM Reports");
        $all_reports = $stmt_report_total->fetch(PDO::FETCH_ASSOC)['total'];

        if ($all_reports > 0) {
            $stmt_report_resolved = $this->conn->query("SELECT COUNT(*) AS total FROM Reports WHERE Status = 'Resolved'");
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
                         r.Reason AS reason, r.CreatedAt AS time, 
                         CASE 
                            WHEN r.Status = 'Pending' THEN 'Chờ duyệt' 
                            ELSE 'Đã xử lý' 
                         END AS status
                  FROM Reports r
                  LEFT JOIN Users u ON r.ReportedUserID = u.UserID
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
                  FROM Users u
                  JOIN Roles r ON u.RoleID = r.RoleID
                  ORDER BY u.CreatedAt DESC";
                  
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>