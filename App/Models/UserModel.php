<?php
namespace App\Models;

use PDO;

class UserModel {
    private PDO $conn;
    private string $table = "users";

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->ensureEmailVerificationSchema();
    }

    public static function normalizeUsername(string $username): string {
        return strtolower(trim($username));
    }

    public static function isValidUsername(string $username): bool {
        return preg_match('/^[a-z0-9_.]{3,50}$/', $username) === 1;
    }

    public function exists($username, $email): bool {
        $query = "SELECT UserID FROM " . $this->table . " WHERE Username = :username OR Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function usernameExists(string $username): bool {
        $username = self::normalizeUsername($username);

        $query = "SELECT 1 FROM " . $this->table . " WHERE Username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email): bool {
        $query = "SELECT 1 FROM " . $this->table . " WHERE Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function register($name, $username, $email, $password, ?string $verificationTokenHash = null, ?string $verificationExpiresAt = null) {
        $username = self::normalizeUsername((string) $username);

        if (!self::isValidUsername($username)) {
            return false;
        }

        $isVerified = $verificationTokenHash === null ? 1 : 0;
        $emailVerifiedAt = $verificationTokenHash === null ? date('Y-m-d H:i:s') : null;

        $query = "INSERT INTO " . $this->table . "
                    (FullName, Username, Email, PasswordHash, RoleID, IsActive, CreatedAt, is_verified, email_verified_at, verification_token, verification_expires_at)
                  VALUES
                    (:name, :username, :email, :password, 2, 1, NOW(), :isVerified, :emailVerifiedAt, :verificationToken, :verificationExpiresAt)";
        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindValue(':isVerified', $isVerified, PDO::PARAM_INT);
        $stmt->bindValue(':emailVerifiedAt', $emailVerifiedAt, $emailVerifiedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':verificationToken', $verificationTokenHash, $verificationTokenHash === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':verificationExpiresAt', $verificationExpiresAt, $verificationExpiresAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }

    public function findByCredentials($loginInput) {
        $emailInput = trim((string) $loginInput);
        $usernameInput = self::normalizeUsername((string) $loginInput);
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.Username = :username OR u.Email = :email
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $usernameInput);
        $stmt->bindValue(':email', $emailInput);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail(string $email) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.Email = :email
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByVerificationTokenHash(string $tokenHash) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.verification_token = :tokenHash
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':tokenHash', $tokenHash);
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
        $username = self::normalizeUsername($username);

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
        $username = self::normalizeUsername((string) $username);
        if (!self::isValidUsername($username)) {
            return false;
        }

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

    public function updatePasswordById(int $userId, string $newPassword): bool {
        $query = "UPDATE " . $this->table . " SET PasswordHash = :password WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function markEmailVerified(int $userId): bool {
        $query = "UPDATE " . $this->table . "
                  SET is_verified = 1,
                      email_verified_at = NOW(),
                      verification_token = NULL,
                      verification_expires_at = NULL
                  WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function clearVerificationToken(int $userId): bool {
        $query = "UPDATE " . $this->table . "
                  SET verification_token = NULL,
                      verification_expires_at = NULL
                  WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

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

    private function ensureEmailVerificationSchema(): void {
        $addedVerificationFlag = false;

        if (!$this->columnExists('is_verified')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0");
            $addedVerificationFlag = true;
        }

        if (!$this->columnExists('email_verified_at')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN email_verified_at DATETIME NULL");
        }

        if (!$this->columnExists('verification_token')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN verification_token VARCHAR(255) NULL");
        }

        if (!$this->columnExists('verification_expires_at')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN verification_expires_at DATETIME NULL");
        }

        if ($addedVerificationFlag) {
            $this->conn->exec("
                UPDATE " . $this->table . "
                SET is_verified = 1,
                    email_verified_at = COALESCE(email_verified_at, CreatedAt, NOW())
                WHERE verification_token IS NULL
            ");
        }
    }

    private function columnExists(string $column): bool {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND COLUMN_NAME = :column
        ");
        $stmt->bindValue(':table', $this->table);
        $stmt->bindValue(':column', $column);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }
}
?>
