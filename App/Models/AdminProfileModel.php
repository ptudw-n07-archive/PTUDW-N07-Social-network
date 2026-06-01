<?php
namespace App\Models;

use PDO;

class AdminProfileModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // --- Admin profile và logs ---

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

    public function updateAdminBio($userId, $bio): bool {
        $sql = "UPDATE users SET Bio = :bio WHERE UserID = :userId AND RoleID = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':bio', $bio, PDO::PARAM_STR);
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
}
?>
