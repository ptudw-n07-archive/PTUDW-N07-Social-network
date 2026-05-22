<?php
namespace App\Models;

use PDO;

class UserModel {
    private PDO $conn;
    private string $table = "users";

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function exists($username, $email): bool {
        $query = "SELECT UserID FROM " . $this->table . " WHERE Username = :username OR Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function register($name, $username, $email, $password): bool {
        $query = "INSERT INTO " . $this->table . " (FullName, Username, Email, PasswordHash, RoleID, IsActive, CreatedAt)
                  VALUES (:name, :username, :email, :password, 2, 1, NOW())";
        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);

        return $stmt->execute();
    }

    public function findByCredentials($loginInput) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.Username = :input OR u.Email = :input
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':input', $loginInput);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($userId) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :userId
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserProfileById($userId) {
        $query = "SELECT
                    u.UserID,
                    u.RoleID,
                    u.Username,
                    u.Email,
                    u.FullName,
                    u.Bio,
                    u.ProfilePictureUrl,
                    u.CreatedAt,
                    r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :userId
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isUsernameTaken($username, $excludeUserId): bool {
        $query = "SELECT UserID
                  FROM " . $this->table . "
                  WHERE Username = :username AND UserID != :userId
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':userId', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function isEmailTaken($email, $excludeUserId): bool {
        $query = "SELECT UserID
                  FROM " . $this->table . "
                  WHERE Email = :email AND UserID != :userId
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':userId', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function updateProfile($userId, $fullName, $username, $email, $bio, $avatarPath = null): bool {
        $fields = [
            "FullName = :fullName",
            "Username = :username",
            "Email = :email",
            "Bio = :bio"
        ];

        if ($avatarPath !== null) {
            $fields[] = "ProfilePictureUrl = :avatarPath";
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        if ($avatarPath !== null) {
            $stmt->bindParam(':avatarPath', $avatarPath);
        }

        return $stmt->execute();
    }

    public function countFollowing($userId): int {
        $query = "SELECT COUNT(*) FROM follows WHERE FollowerID = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countFollowers($userId): int {
        $query = "SELECT COUNT(*) FROM follows WHERE FollowedID = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function updatePassword($email, $newPassword): bool {
        $query = "UPDATE " . $this->table . " SET PasswordHash = :password WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    public function login($username, $password) {
        $user = $this->findByCredentials($username);

        if (!$user) {
            return false;
        }

        $storedPassword = $user['PasswordHash'] ?? $user['Password'] ?? '';

        if (!empty($storedPassword) && password_verify($password, $storedPassword)) {
            return $user;
        }

        if (!empty($storedPassword) && hash_equals($storedPassword, $password)) {
            return $user;
        }

        return false;
    }
}
?>
