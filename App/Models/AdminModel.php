<?php
namespace App\Models;

use PDO;

class AdminModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getOverviewStats() {
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
            'lastUpdated' => date('d/m/Y H:i:s')
        ];
    }

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
        $sql = "SELECT u.UserID, u.FullName, u.Username, u.Email, u.ProfilePictureUrl, u.Bio, u.RoleID, u.IsActive, u.CreatedAt,
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

    public function getAdminProfileById($userId) {
        $sql = "SELECT u.UserID, u.FullName, u.Username, u.Email, u.ProfilePictureUrl, u.Bio,
                       u.RoleID, u.IsActive, u.CreatedAt, r.RoleName
                FROM users u
                JOIN roles r ON u.RoleID = r.RoleID
                WHERE u.UserID = :userId AND u.RoleID = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserPasswordHash($userId): ?string {
        $sql = "SELECT PasswordHash FROM users WHERE UserID = :userId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $hash = $stmt->fetchColumn();
        return $hash !== false ? (string)$hash : null;
    }

    public function updateAdminFullName($userId, $fullName): bool {
        $sql = "UPDATE users SET FullName = :fullName WHERE UserID = :userId AND RoleID = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fullName', $fullName, PDO::PARAM_STR);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateAdminAvatar($userId, $avatarPath): bool {
        $sql = "UPDATE users SET ProfilePictureUrl = :avatarPath WHERE UserID = :userId AND RoleID = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':avatarPath', $avatarPath, PDO::PARAM_STR);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateAdminPassword($userId, $passwordHash): bool {
        $sql = "UPDATE users SET PasswordHash = :passwordHash WHERE UserID = :userId AND RoleID = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':passwordHash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function addAdminLog($adminUserId, $action, $targetType, $targetId, $description): bool {
        $sql = "INSERT INTO admin_logs (AdminUserID, Action, TargetType, TargetID, Description, CreatedAt)
                VALUES (:adminUserId, :action, :targetType, :targetId, :description, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':adminUserId', $adminUserId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':targetType', $targetType, PDO::PARAM_STR);
        $stmt->bindValue(':targetId', $targetId, PDO::PARAM_INT);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getAdminLogs($adminUserId, $keyword = '', $action = '', $limit = 30): array {
        $conditions = ['AdminUserID = :adminUserId'];
        $params = [':adminUserId' => (int)$adminUserId];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(Action LIKE :keyword OR Description LIKE :keyword OR TargetType LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $action = trim((string)$action);
        if ($action !== '') {
            $conditions[] = "Action = :action";
            $params[':action'] = $action;
        }

        $limit = max(1, min((int)$limit, 100));
        $sql = "SELECT LogID, AdminUserID, Action, TargetType, TargetID, Description, CreatedAt
                FROM admin_logs
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CreatedAt DESC, LogID DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':adminUserId' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminLogActions($adminUserId): array {
        $sql = "SELECT DISTINCT Action
                FROM admin_logs
                WHERE AdminUserID = :adminUserId
                ORDER BY Action ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':adminUserId', $adminUserId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    public function getReportDetailById($reportId) {
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

    public function getAdminContentPosts($keyword = '', $status = '', $privacy = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(u.Username LIKE :keyword OR u.FullName LIKE :keyword OR u.Email LIKE :keyword OR p.Content LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "p.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        if ($privacy !== '' && in_array($privacy, ['public', 'private'], true)) {
            $conditions[] = "p.Privacy = :privacy";
            $params[':privacy'] = $privacy;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                       u.Username, u.FullName, u.Email, u.ProfilePictureUrl,
                       (
                           SELECT pi.ImageUrl
                           FROM postimages pi
                           WHERE pi.PostID = p.PostID
                           ORDER BY pi.PostImageID ASC
                           LIMIT 1
                       ) AS ThumbnailUrl,
                       COUNT(DISTINCT l.UserID) AS LikeCount,
                       COUNT(DISTINCT c.CommentID) AS CommentCount
                FROM posts p
                JOIN users u ON u.UserID = p.UserID
                LEFT JOIN likes l ON l.PostID = p.PostID
                LEFT JOIN comments c ON c.PostID = p.PostID
                $whereSql
                GROUP BY p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                         u.Username, u.FullName, u.Email, u.ProfilePictureUrl
                ORDER BY p.CreatedAt DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminContentPostDetail($postId) {
        $sql = "SELECT p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                       u.Username, u.FullName, u.Email, u.ProfilePictureUrl,
                       COUNT(DISTINCT l.UserID) AS LikeCount,
                       COUNT(DISTINCT c.CommentID) AS CommentCount
                FROM posts p
                JOIN users u ON u.UserID = p.UserID
                LEFT JOIN likes l ON l.PostID = p.PostID
                LEFT JOIN comments c ON c.PostID = p.PostID
                WHERE p.PostID = :postId
                GROUP BY p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                         u.Username, u.FullName, u.Email, u.ProfilePictureUrl
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            return null;
        }

        $post['images'] = $this->getPostImagesByPostId($postId);
        return $post;
    }

    public function getPostOwnerAndStatus($postId) {
        $sql = "SELECT PostID, UserID, IsHidden FROM posts WHERE PostID = :postId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePostHiddenStatus($postId, $isHidden, $adminUserId = null) {
        $post = $this->getPostOwnerAndStatus($postId);
        if (!$post) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE posts SET IsHidden = :isHidden WHERE PostID = :postId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$isHidden === 1 && $adminUserId) {
            $typeId = $this->getNotificationTypeIdByName('ContentHidden');
            if ($typeId) {
                $this->createNotification((int)$post['UserID'], (int)$adminUserId, $postId, null, (int)$typeId);
            }
        }

        return $this->getAdminContentPostDetail($postId);
    }

    public function deleteAdminContentPost($postId, $adminUserId = null) {
        $post = $this->getPostOwnerAndStatus($postId);
        if (!$post) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            if ($adminUserId) {
                $typeId = $this->getNotificationTypeIdByName('ContentHidden');
                if ($typeId) {
                    $this->createNotification((int)$post['UserID'], (int)$adminUserId, null, null, (int)$typeId);
                }
            }

            $commentIds = $this->getCommentIdsByPostId($postId);
            if (!empty($commentIds)) {
                $placeholders = $this->placeholders($commentIds);

                $stmt = $this->conn->prepare("DELETE FROM notifications WHERE PostID = ? OR CommentID IN ($placeholders)");
                $stmt->execute(array_merge([$postId], $commentIds));

                $stmt = $this->conn->prepare("UPDATE reports
                    SET Status = 'Resolved',
                        ResolvedAt = COALESCE(ResolvedAt, NOW()),
                        AdminNote = CASE
                            WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                            ELSE AdminNote
                        END,
                        PostID = NULL,
                        CommentID = NULL
                    WHERE PostID = ? OR CommentID IN ($placeholders)");
                $stmt->execute(array_merge([$postId], $commentIds));
            } else {
                $stmt = $this->conn->prepare("DELETE FROM notifications WHERE PostID = ?");
                $stmt->execute([$postId]);

                $stmt = $this->conn->prepare("UPDATE reports
                    SET Status = 'Resolved',
                        ResolvedAt = COALESCE(ResolvedAt, NOW()),
                        AdminNote = CASE
                            WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                            ELSE AdminNote
                        END,
                        PostID = NULL
                    WHERE PostID = ?");
                $stmt->execute([$postId]);
            }

            $stmt = $this->conn->prepare("DELETE FROM likes WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM posthashtags WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM postimages WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM comments WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM posts WHERE PostID = ?");
            $stmt->execute([$postId]);

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getAdminContentComments($keyword = '', $status = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(cu.Username LIKE :keyword OR cu.FullName LIKE :keyword OR cu.Email LIKE :keyword OR c.Content LIKE :keyword OR p.Content LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "c.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT c.CommentID, c.PostID, c.UserID, c.Content, c.CreatedAt, c.ParentCommentID, c.IsHidden,
                       cu.Username, cu.FullName, cu.Email, cu.ProfilePictureUrl,
                       p.Content AS PostContent,
                       pu.UserID AS PostAuthorID, pu.Username AS PostAuthorUsername, pu.FullName AS PostAuthorFullName
                FROM comments c
                JOIN users cu ON cu.UserID = c.UserID
                JOIN posts p ON p.PostID = c.PostID
                JOIN users pu ON pu.UserID = p.UserID
                $whereSql
                ORDER BY c.CreatedAt DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminContentCommentDetail($commentId) {
        $sql = "SELECT c.CommentID, c.PostID, c.UserID, c.Content, c.CreatedAt, c.ParentCommentID, c.IsHidden,
                       cu.Username, cu.FullName, cu.Email, cu.ProfilePictureUrl,
                       p.Content AS PostContent, p.CreatedAt AS PostCreatedAt,
                       pu.UserID AS PostAuthorID, pu.Username AS PostAuthorUsername, pu.FullName AS PostAuthorFullName,
                       pc.Content AS ParentContent, pcu.Username AS ParentUsername, pcu.FullName AS ParentFullName
                FROM comments c
                JOIN users cu ON cu.UserID = c.UserID
                JOIN posts p ON p.PostID = c.PostID
                JOIN users pu ON pu.UserID = p.UserID
                LEFT JOIN comments pc ON pc.CommentID = c.ParentCommentID
                LEFT JOIN users pcu ON pcu.UserID = pc.UserID
                WHERE c.CommentID = :commentId
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCommentOwnerAndStatus($commentId) {
        $sql = "SELECT CommentID, PostID, UserID, IsHidden FROM comments WHERE CommentID = :commentId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCommentHiddenStatus($commentId, $isHidden, $adminUserId = null) {
        $comment = $this->getCommentOwnerAndStatus($commentId);
        if (!$comment) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE comments SET IsHidden = :isHidden WHERE CommentID = :commentId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$isHidden === 1 && $adminUserId) {
            $typeId = $this->getNotificationTypeIdByName('ContentHidden');
            if ($typeId) {
                $this->createNotification((int)$comment['UserID'], (int)$adminUserId, (int)$comment['PostID'], $commentId, (int)$typeId);
            }
        }

        return $this->getAdminContentCommentDetail($commentId);
    }

    public function deleteAdminContentComment($commentId, $adminUserId = null) {
        $comment = $this->getCommentOwnerAndStatus($commentId);
        if (!$comment) {
            return false;
        }

        $commentIds = $this->getDescendantCommentIds($commentId);

        try {
            $this->conn->beginTransaction();

            if ($adminUserId) {
                $typeId = $this->getNotificationTypeIdByName('ContentHidden');
                if ($typeId) {
                    $this->createNotification((int)$comment['UserID'], (int)$adminUserId, (int)$comment['PostID'], null, (int)$typeId);
                }
            }

            $placeholders = $this->placeholders($commentIds);

            $stmt = $this->conn->prepare("DELETE FROM notifications WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $stmt = $this->conn->prepare("UPDATE reports
                SET Status = 'Resolved',
                    ResolvedAt = COALESCE(ResolvedAt, NOW()),
                    AdminNote = CASE
                        WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                        ELSE AdminNote
                    END,
                    CommentID = NULL
                WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $stmt = $this->conn->prepare("DELETE FROM comments WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $this->conn->commit();
            return $commentIds;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getAdminContentHashtags($keyword = '', $status = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "h.HashtagName LIKE :keyword";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "h.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT h.HashtagID, h.HashtagName, h.UsageCount, h.CreatedAt, h.IsHidden,
                       COUNT(DISTINCT ph.PostID) AS PostCount
                FROM hashtags h
                LEFT JOIN posthashtags ph ON ph.HashtagID = h.HashtagID
                $whereSql
                GROUP BY h.HashtagID, h.HashtagName, h.UsageCount, h.CreatedAt, h.IsHidden
                ORDER BY h.CreatedAt DESC, h.HashtagID DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHashtagById($hashtagId) {
        $sql = "SELECT HashtagID, HashtagName, UsageCount, CreatedAt, IsHidden FROM hashtags WHERE HashtagID = :hashtagId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':hashtagId', $hashtagId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateHashtagHiddenStatus($hashtagId, $isHidden) {
        if (!$this->getHashtagById($hashtagId)) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE hashtags SET IsHidden = :isHidden WHERE HashtagID = :hashtagId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':hashtagId', $hashtagId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->getHashtagById($hashtagId);
    }

    public function deleteAdminContentHashtag($hashtagId) {
        if (!$this->getHashtagById($hashtagId)) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM posthashtags WHERE HashtagID = ?");
            $stmt->execute([$hashtagId]);

            $stmt = $this->conn->prepare("DELETE FROM hashtags WHERE HashtagID = ?");
            $stmt->execute([$hashtagId]);

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function getCommentIdsByPostId($postId) {
        $stmt = $this->conn->prepare("SELECT CommentID FROM comments WHERE PostID = :postId");
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getDescendantCommentIds($commentId) {
        $ids = [(int)$commentId];
        $queue = [(int)$commentId];

        while (!empty($queue)) {
            $parentId = array_shift($queue);
            $stmt = $this->conn->prepare("SELECT CommentID FROM comments WHERE ParentCommentID = :parentId");
            $stmt->bindValue(':parentId', $parentId, PDO::PARAM_INT);
            $stmt->execute();
            $children = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            foreach ($children as $childId) {
                if (!in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $ids;
    }

    private function countScalar($sql) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function placeholders(array $items) {
        return implode(',', array_fill(0, count($items), '?'));
    }
}
?>
