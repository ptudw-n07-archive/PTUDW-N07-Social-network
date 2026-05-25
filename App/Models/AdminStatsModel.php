<?php
namespace App\Models;

use PDO;

class AdminStatsModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getOverviewStats() {
        // Gom các số liệu chính cho dashboard admin.
        $totalUsers = $this->countScalar("SELECT COUNT(UserID) FROM users");
        $activeUsers = $this->countScalar("SELECT COUNT(UserID) FROM users WHERE IsActive = 1");
        $lockedUsers = $this->countScalar("SELECT COUNT(UserID) FROM users WHERE IsActive = 0");
        $totalPosts = $this->countScalar("SELECT COUNT(PostID) FROM posts");
        $visiblePosts = $this->countScalar("SELECT COUNT(PostID) FROM posts WHERE IsHidden = 0");
        $hiddenPosts = $this->countScalar("SELECT COUNT(PostID) FROM posts WHERE IsHidden = 1");
        $totalComments = $this->countScalar("SELECT COUNT(CommentID) FROM comments");
        $hiddenComments = $this->countScalar("SELECT COUNT(CommentID) FROM comments WHERE IsHidden = 1");
        $pendingReports = $this->countScalar("SELECT COUNT(ReportID) FROM reports WHERE Status = 'Pending'");
        $totalHashtags = $this->countScalar("SELECT COUNT(HashtagID) FROM hashtags");
        $allReports = $this->countScalar("SELECT COUNT(ReportID) FROM reports");
        $resolvedReports = $this->countScalar("SELECT COUNT(ReportID) FROM reports WHERE Status = 'Resolved'");
        $activityRate = $allReports > 0 ? round(($resolvedReports / $allReports) * 100, 1) . '%' : '100%';
        $usersToday = $this->countScalar("SELECT COUNT(UserID) FROM users WHERE DATE(CreatedAt) = CURDATE()");
        $usersThisWeek = $this->countScalar("SELECT COUNT(UserID) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $postsToday = $this->countScalar("SELECT COUNT(PostID) FROM posts WHERE DATE(CreatedAt) = CURDATE()");
        $postsThisWeek = $this->countScalar("SELECT COUNT(PostID) FROM posts WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $commentsToday = $this->countScalar("SELECT COUNT(CommentID) FROM comments WHERE DATE(CreatedAt) = CURDATE()");
        $commentsThisWeek = $this->countScalar("SELECT COUNT(CommentID) FROM comments WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $reportsToday = $this->countScalar("SELECT COUNT(ReportID) FROM reports WHERE DATE(CreatedAt) = CURDATE()");

        return [
            'users' => number_format($totalUsers),
            'reports' => $pendingReports,
            'posts' => number_format($totalPosts),
            'activity' => $activityRate,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'lockedUsers' => $lockedUsers,
            'totalPosts' => $totalPosts,
            'visiblePosts' => $visiblePosts,
            'hiddenPosts' => $hiddenPosts,
            'totalComments' => $totalComments,
            'hiddenComments' => $hiddenComments,
            'pendingReports' => $pendingReports,
            'totalHashtags' => $totalHashtags,
            'kpi' => [
                'totalUsers' => '+' . $usersToday . ' hôm nay · +' . $usersThisWeek . ' 7 ngày',
                'activeUsers' => $activeUsers . ' đang hoạt động',
                'lockedUsers' => $lockedUsers . ' bị khóa',
                'totalPosts' => '+' . $postsToday . ' hôm nay · +' . $postsThisWeek . ' 7 ngày',
                'visiblePosts' => $visiblePosts . ' đang hiển thị',
                'hiddenPosts' => $hiddenPosts . ' đã ẩn',
                'totalComments' => '+' . $commentsToday . ' hôm nay · +' . $commentsThisWeek . ' 7 ngày',
                'hiddenComments' => $hiddenComments . ' đã ẩn',
                'pendingReports' => $pendingReports . ' đang chờ · +' . $reportsToday . ' hôm nay',
                'totalHashtags' => $totalHashtags . ' hashtag đang có'
            ],
            'lastUpdated' => date('d/m/Y H:i:s')
        ];
    }

    public function getOverviewDetail(string $metric): ?array {
        // Whitelist các metric được phép xem chi tiết, tránh truyền tên bảng/cột trực tiếp từ request.
        $configs = [
            'totalUsers' => ['title' => 'Tổng thành viên', 'type' => 'users', 'where' => ''],
            'activeUsers' => ['title' => 'Tài khoản đang hoạt động', 'type' => 'users', 'where' => 'WHERE u.IsActive = 1'],
            'lockedUsers' => ['title' => 'Tài khoản bị khóa', 'type' => 'users', 'where' => 'WHERE u.IsActive = 0'],
            'totalPosts' => ['title' => 'Tổng bài viết', 'type' => 'posts', 'where' => ''],
            'visiblePosts' => ['title' => 'Bài viết đang hiển thị', 'type' => 'posts', 'where' => 'WHERE p.IsHidden = 0'],
            'hiddenPosts' => ['title' => 'Bài viết đã ẩn', 'type' => 'posts', 'where' => 'WHERE p.IsHidden = 1'],
            'totalComments' => ['title' => 'Tổng bình luận', 'type' => 'comments', 'where' => ''],
            'hiddenComments' => ['title' => 'Bình luận đã ẩn', 'type' => 'comments', 'where' => 'WHERE c.IsHidden = 1'],
            'pendingReports' => ['title' => 'Report chờ duyệt', 'type' => 'reports', 'where' => "WHERE r.Status = 'Pending'"],
            'totalHashtags' => ['title' => 'Tổng hashtag', 'type' => 'hashtags', 'where' => '']
        ];

        if (!isset($configs[$metric])) {
            return null;
        }

        $config = $configs[$metric];
        $rows = [];
        $columns = [];

        switch ($config['type']) {
            case 'users':
                // Mỗi loại metric có bộ cột riêng để frontend render bảng chi tiết.
                $columns = ['UserID', 'Username', 'FullName', 'Email', 'Vai trò', 'Trạng thái', 'Ngày tạo'];
                $sql = "SELECT u.UserID, u.Username, u.FullName, u.Email, r.RoleName,
                               CASE WHEN u.IsActive = 1 THEN 'Hoạt động' ELSE 'Bị khóa' END AS StatusText,
                               u.CreatedAt
                        FROM users u
                        LEFT JOIN roles r ON r.RoleID = u.RoleID
                        {$config['where']}
                        ORDER BY u.CreatedAt DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'UserID' => $row['UserID'],
                        'Username' => $row['Username'],
                        'FullName' => $row['FullName'],
                        'Email' => $row['Email'],
                        'Vai trò' => $row['RoleName'],
                        'Trạng thái' => $row['StatusText'],
                        'Ngày tạo' => $row['CreatedAt']
                    ];
                }
                break;

            case 'posts':
                $columns = ['PostID', 'Tác giả', 'Content', 'Trạng thái', 'CreatedAt'];
                $sql = "SELECT p.PostID, p.Content, p.IsHidden, p.CreatedAt,
                               u.Username, u.FullName
                        FROM posts p
                        LEFT JOIN users u ON u.UserID = p.UserID
                        {$config['where']}
                        ORDER BY p.CreatedAt DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'PostID' => $row['PostID'],
                        'Tác giả' => $row['FullName'] ?: $row['Username'],
                        'Content' => $row['Content'],
                        'Trạng thái' => (int)$row['IsHidden'] === 1 ? 'Đã ẩn' : 'Đang hiển thị',
                        'CreatedAt' => $row['CreatedAt']
                    ];
                }
                break;

            case 'comments':
                $columns = ['CommentID', 'Tác giả', 'Content', 'PostID', 'Trạng thái', 'CreatedAt'];
                $sql = "SELECT c.CommentID, c.Content, c.PostID, c.IsHidden, c.CreatedAt,
                               u.Username, u.FullName
                        FROM comments c
                        LEFT JOIN users u ON u.UserID = c.UserID
                        {$config['where']}
                        ORDER BY c.CreatedAt DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'CommentID' => $row['CommentID'],
                        'Tác giả' => $row['FullName'] ?: $row['Username'],
                        'Content' => $row['Content'],
                        'PostID' => $row['PostID'],
                        'Trạng thái' => (int)$row['IsHidden'] === 1 ? 'Đã ẩn' : 'Đang hiển thị',
                        'CreatedAt' => $row['CreatedAt']
                    ];
                }
                break;

            case 'reports':
                $columns = ['ReportID', 'Reporter', 'ReportedUser', 'Reason', 'Status', 'CreatedAt'];
                $sql = "SELECT r.ReportID, r.Reason, r.Status, r.CreatedAt,
                               reporter.Username AS ReporterUsername, reporter.FullName AS ReporterFullName,
                               reported.Username AS ReportedUsername, reported.FullName AS ReportedFullName
                        FROM reports r
                        LEFT JOIN users reporter ON reporter.UserID = r.ReporterUserID
                        LEFT JOIN users reported ON reported.UserID = r.ReportedUserID
                        {$config['where']}
                        ORDER BY r.CreatedAt DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'ReportID' => $row['ReportID'],
                        'Reporter' => $row['ReporterFullName'] ?: $row['ReporterUsername'],
                        'ReportedUser' => $row['ReportedFullName'] ?: $row['ReportedUsername'],
                        'Reason' => $row['Reason'],
                        'Status' => $row['Status'],
                        'CreatedAt' => $row['CreatedAt']
                    ];
                }
                break;

            case 'hashtags':
                $columns = ['HashtagID', 'HashtagName', 'UsageCount', 'Trạng thái', 'CreatedAt'];
                $sql = "SELECT HashtagID, HashtagName, UsageCount, IsHidden, CreatedAt
                        FROM hashtags
                        ORDER BY UsageCount DESC, CreatedAt DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'HashtagID' => $row['HashtagID'],
                        'HashtagName' => $row['HashtagName'],
                        'UsageCount' => $row['UsageCount'],
                        'Trạng thái' => (int)$row['IsHidden'] === 1 ? 'Đã ẩn' : 'Đang hiển thị',
                        'CreatedAt' => $row['CreatedAt']
                    ];
                }
                break;
        }

        return [
            'title' => $config['title'],
            'columns' => $columns,
            'data' => $rows
        ];
    }

    // --- Thống kê nâng cao ---

    public function getStatisticsTopRankings($limit = 5) {
        $limit = in_array((int)$limit, [5, 10, 15, 20], true) ? (int)$limit : 5;

        return [
            'topPostsByLikes' => $this->getTopPostsByLikes($limit),
            'topUsersByFollowers' => $this->getTopUsersByFollowers($limit),
            'topHashtags' => $this->getTopHashtags($limit),
            'topReportedUsers' => $this->getTopReportedUsers($limit)
        ];
    }

    public function getStatisticsChartData() {
        return [
            'postsByDay' => $this->getDailyCounts('posts', 'PostID'),
            'usersByDay' => $this->getDailyCounts('users', 'UserID'),
            'reportStatus' => $this->getReportStatusDistribution(),
            'postVisibility' => $this->getPostVisibilityDistribution()
        ];
    }

    public function getStatisticsActivityInsights() {
        return [
            'mostActiveUsers' => $this->getMostActiveUsers(5),
            'peakPostHour' => $this->getPeakPostHour(),
            'recentHotHashtags' => $this->getRecentHotHashtags(5),
            'latestReports' => $this->getLatestReports(5)
        ];
    }

    private function getTopPostsByLikes($limit) {
        $sql = "SELECT p.PostID, p.Content, p.CreatedAt, p.IsHidden,
                       u.Username, u.FullName,
                       (
                           SELECT pi.ImageUrl
                           FROM postimages pi
                           WHERE pi.PostID = p.PostID
                           ORDER BY pi.PostImageID ASC
                           LIMIT 1
                       ) AS ThumbnailUrl,
                       COALESCE(lc.LikeCount, 0) AS LikeCount,
                       COALESCE(cc.CommentCount, 0) AS CommentCount
                FROM posts p
                JOIN users u ON u.UserID = p.UserID
                LEFT JOIN (
                    SELECT PostID, COUNT(*) AS LikeCount
                    FROM likes
                    GROUP BY PostID
                ) lc ON lc.PostID = p.PostID
                LEFT JOIN (
                    SELECT PostID, COUNT(*) AS CommentCount
                    FROM comments
                    GROUP BY PostID
                ) cc ON cc.PostID = p.PostID
                ORDER BY LikeCount DESC, CommentCount DESC, p.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTopUsersByFollowers($limit) {
        $sql = "SELECT u.UserID, u.Username, u.FullName, u.ProfilePictureUrl, u.IsActive,
                       COALESCE(fc.FollowerCount, 0) AS FollowerCount,
                       COALESCE(pc.PostCount, 0) AS PostCount
                FROM users u
                LEFT JOIN (
                    SELECT FollowedID, COUNT(FollowerID) AS FollowerCount
                    FROM follows
                    GROUP BY FollowedID
                ) fc ON fc.FollowedID = u.UserID
                LEFT JOIN (
                    SELECT UserID, COUNT(PostID) AS PostCount
                    FROM posts
                    GROUP BY UserID
                ) pc ON pc.UserID = u.UserID
                ORDER BY FollowerCount DESC, PostCount DESC, u.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTopHashtags($limit) {
        $sql = "SELECT h.HashtagID, h.HashtagName, h.UsageCount, h.IsHidden,
                       COUNT(DISTINCT ph.PostID) AS PostCount
                FROM hashtags h
                LEFT JOIN posthashtags ph ON ph.HashtagID = h.HashtagID
                GROUP BY h.HashtagID, h.HashtagName, h.UsageCount, h.IsHidden
                ORDER BY PostCount DESC, h.UsageCount DESC, h.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTopReportedUsers($limit) {
        $sql = "SELECT u.UserID, u.Username, u.FullName, u.ProfilePictureUrl, u.IsActive, r.RoleName,
                       COUNT(rep.ReportID) AS ReportCount
                FROM users u
                JOIN reports rep ON rep.ReportedUserID = u.UserID
                LEFT JOIN roles r ON r.RoleID = u.RoleID
                GROUP BY u.UserID, u.Username, u.FullName, u.ProfilePictureUrl, u.IsActive, r.RoleName
                ORDER BY ReportCount DESC, u.UserID DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDailyCounts($table, $idColumn) {
        $allowedTables = ['posts' => 'PostID', 'users' => 'UserID'];
        if (!isset($allowedTables[$table]) || $allowedTables[$table] !== $idColumn) {
            return ['labels' => [], 'values' => []];
        }

        $currentDate = $this->conn->query("SELECT CURDATE()")->fetchColumn();
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime($currentDate . " -{$i} days"));
            $labels[$date] = date('d/m', strtotime($date));
            $values[$date] = 0;
        }

        $sql = "SELECT DATE(CreatedAt) AS StatDate, COUNT($idColumn) AS Total
                FROM $table
                WHERE CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(CreatedAt)
                ORDER BY StatDate ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($values[$row['StatDate']])) {
                $values[$row['StatDate']] = (int)$row['Total'];
            }
        }

        return [
            'labels' => array_values($labels),
            'values' => array_values($values)
        ];
    }

    private function getReportStatusDistribution() {
        $sql = "SELECT Status, COUNT(ReportID) AS Total FROM reports GROUP BY Status ORDER BY Status ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $labels = [];
        $values = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $labels[] = $row['Status'] ?: 'Unknown';
            $values[] = (int)$row['Total'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function getPostVisibilityDistribution() {
        $sql = "SELECT IsHidden, COUNT(PostID) AS Total FROM posts GROUP BY IsHidden ORDER BY IsHidden ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $map = [0 => 'Hiển thị', 1 => 'Đã ẩn'];
        $labels = [];
        $values = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (int)$row['IsHidden'];
            $labels[] = $map[$key] ?? 'Khác';
            $values[] = (int)$row['Total'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function getMostActiveUsers($limit) {
        $sql = "SELECT u.UserID, u.Username, u.FullName, u.ProfilePictureUrl, u.IsActive,
                       COALESCE(pc.PostCount, 0) AS PostCount,
                       COALESCE(cc.CommentCount, 0) AS CommentCount,
                       COALESCE(lc.LikeCount, 0) AS LikeCount,
                       (COALESCE(pc.PostCount, 0) + COALESCE(cc.CommentCount, 0) + COALESCE(lc.LikeCount, 0)) AS ActivityScore
                FROM users u
                LEFT JOIN (
                    SELECT UserID, COUNT(PostID) AS PostCount
                    FROM posts
                    GROUP BY UserID
                ) pc ON pc.UserID = u.UserID
                LEFT JOIN (
                    SELECT UserID, COUNT(CommentID) AS CommentCount
                    FROM comments
                    GROUP BY UserID
                ) cc ON cc.UserID = u.UserID
                LEFT JOIN (
                    SELECT UserID, COUNT(PostID) AS LikeCount
                    FROM likes
                    GROUP BY UserID
                ) lc ON lc.UserID = u.UserID
                ORDER BY ActivityScore DESC, u.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPeakPostHour() {
        $sql = "SELECT HOUR(CreatedAt) AS PostHour, COUNT(PostID) AS PostCount
                FROM posts
                GROUP BY HOUR(CreatedAt)
                ORDER BY PostCount DESC, PostHour ASC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? [
            'hour' => (int)$row['PostHour'],
            'label' => sprintf('%02d:00 - %02d:59', (int)$row['PostHour'], (int)$row['PostHour']),
            'postCount' => (int)$row['PostCount']
        ] : null;
    }

    private function getRecentHotHashtags($limit) {
        $sql = "SELECT h.HashtagID, h.HashtagName, h.UsageCount, h.IsHidden,
                       COUNT(DISTINCT ph.PostID) AS RecentPostCount
                FROM hashtags h
                LEFT JOIN posthashtags ph ON ph.HashtagID = h.HashtagID
                    AND ph.CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY h.HashtagID, h.HashtagName, h.UsageCount, h.IsHidden
                ORDER BY RecentPostCount DESC, h.UsageCount DESC, h.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLatestReports($limit) {
        $sql = "SELECT r.ReportID, r.Reason, r.Status, r.CreatedAt,
                       reporter.Username AS ReporterUsername, reporter.FullName AS ReporterFullName,
                       reported.Username AS ReportedUsername, reported.FullName AS ReportedFullName
                FROM reports r
                LEFT JOIN users reporter ON reporter.UserID = r.ReporterUserID
                LEFT JOIN users reported ON reported.UserID = r.ReportedUserID
                ORDER BY r.CreatedAt DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countScalar($sql) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
?>
