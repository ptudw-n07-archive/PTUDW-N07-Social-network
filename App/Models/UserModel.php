<?php
namespace App\Models;

use PDO;

class UserModel {
    private PDO $conn;
    private string $table = "users";

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->ensureEmailVerificationSchema();
        $this->ensureGoogleLoginSchema();
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

    public function findByGoogleId(string $googleId) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.google_id = :googleId
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':googleId', $googleId);
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

    public function createUserReport(int $reporterUserId, int $reportedUserId, string $reason, string $details = ''): bool {
        if ($reporterUserId === $reportedUserId || !$this->findById($reportedUserId)) {
            return false;
        }

        if ($details === '') {
            $details = $reason;
        }

        $duplicateQuery = "
            SELECT 1
            FROM reports
            WHERE ReporterUserID = :reporterUserId
            AND ReportedUserID = :reportedUserId
            AND PostID IS NULL
            AND CommentID IS NULL
            AND Reason = :reason
            AND Details = :details
            AND CreatedAt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            LIMIT 1
        ";
        $duplicateStmt = $this->conn->prepare($duplicateQuery);
        $duplicateStmt->bindParam(':reporterUserId', $reporterUserId, PDO::PARAM_INT);
        $duplicateStmt->bindParam(':reportedUserId', $reportedUserId, PDO::PARAM_INT);
        $duplicateStmt->bindParam(':reason', $reason);
        $duplicateStmt->bindParam(':details', $details);
        $duplicateStmt->execute();

        if ($duplicateStmt->fetchColumn()) {
            return true;
        }

        $query = "
            INSERT INTO reports
                (ReporterUserID, ReportedUserID, PostID, CommentID, Reason, Details, CreatedAt, Status, AdminNote, ResolvedAt)
            VALUES
                (:reporterUserId, :reportedUserId, NULL, NULL, :reason, :details, NOW(), 'Pending', NULL, NULL)
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':reporterUserId', $reporterUserId, PDO::PARAM_INT);
        $stmt->bindParam(':reportedUserId', $reportedUserId, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':details', $details);

        return $stmt->execute();
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

    public function linkGoogleAccount(int $userId, string $googleId, ?string $avatarUrl = null): bool {
        $query = "UPDATE " . $this->table . "
                  SET google_id = :googleId,
                      avatar_url = :avatarUrl,
                      ProfilePictureUrl = COALESCE(:profilePictureUrl, ProfilePictureUrl),
                      auth_provider = 'google',
                      is_verified = 1,
                      email_verified_at = COALESCE(email_verified_at, NOW()),
                      verification_token = NULL,
                      verification_expires_at = NULL
                  WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':googleId', $googleId);
        $stmt->bindValue(':avatarUrl', $avatarUrl, $avatarUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':profilePictureUrl', $avatarUrl, $avatarUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function createGoogleUser(string $fullName, string $username, string $email, string $googleId, ?string $avatarUrl = null) {
        $username = self::normalizeUsername($username);
        if (!self::isValidUsername($username)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . "
                    (FullName, Username, Email, PasswordHash, RoleID, IsActive, CreatedAt, is_verified, email_verified_at, verification_token, verification_expires_at, google_id, avatar_url, ProfilePictureUrl, auth_provider)
                  VALUES
                    (:fullName, :username, :email, :passwordHash, 2, 1, NOW(), 1, NOW(), NULL, NULL, :googleId, :avatarUrl, :profilePictureUrl, 'google')";
        $stmt = $this->conn->prepare($query);
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        $stmt->bindValue(':fullName', $fullName);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':passwordHash', $passwordHash);
        $stmt->bindValue(':googleId', $googleId);
        $stmt->bindValue(':avatarUrl', $avatarUrl, $avatarUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':profilePictureUrl', $avatarUrl, $avatarUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }

    public function generateUniqueUsernameFromGoogle(string $emailOrName): string {
        $source = trim($emailOrName);

        if (filter_var($source, FILTER_VALIDATE_EMAIL)) {
            $source = strstr($source, '@', true) ?: $source;
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $source);
        $base = strtolower($ascii === false ? $source : $ascii);
        $base = preg_replace('/[^a-z0-9_.]+/', '.', $base) ?? '';
        $base = trim($base, '._');
        $base = preg_replace('/[._]{2,}/', '.', $base) ?? '';

        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }

        $base = substr($base, 0, 42);
        if (self::isValidUsername($base) && !$this->usernameExists($base)) {
            return $base;
        }

        for ($i = 0; $i < 20; $i++) {
            $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
            $candidate = substr($base, 0, 43) . '.' . $suffix;

            if (self::isValidUsername($candidate) && !$this->usernameExists($candidate)) {
                return $candidate;
            }
        }

        return 'user.' . time() . random_int(100, 999);
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

    private function ensureGoogleLoginSchema(): void {
        if (!$this->columnExists('google_id')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN google_id VARCHAR(255) NULL");
        }

        if (!$this->columnExists('avatar_url')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN avatar_url TEXT NULL");
        }

        if (!$this->columnExists('auth_provider')) {
            $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN auth_provider VARCHAR(50) NOT NULL DEFAULT 'local'");
        }

        if (!$this->indexExists('idx_users_google_id')) {
            $this->conn->exec("CREATE INDEX idx_users_google_id ON " . $this->table . " (google_id)");
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

    private function indexExists(string $index): bool {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND INDEX_NAME = :indexName
        ");
        $stmt->bindValue(':table', $this->table);
        $stmt->bindValue(':indexName', $index);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }
}
?>
