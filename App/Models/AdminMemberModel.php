<?php
namespace App\Models;

use PDO;

class AdminMemberModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // --- Quản lý thành viên ---

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
                         DATE_FORMAT(u.CreatedAt, '%d/%m/%Y %H:%i:%s') AS joined,
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

    public function getRoleById($roleId) {
        $sql = "SELECT RoleID, RoleName FROM roles WHERE RoleID = :roleId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
