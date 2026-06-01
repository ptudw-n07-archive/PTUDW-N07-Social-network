<?php
namespace App\Models;

use PDO;

class PasswordResetTokenModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->ensureTable();
    }

    public function create(int $userId, string $email, string $tokenHash, int $ttlMinutes = 15): bool {
        $this->invalidateActiveTokensForUser($userId);

        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlMinutes * 60));
        $query = "
            INSERT INTO password_reset_tokens (user_id, email, token_hash, expires_at, created_at)
            VALUES (:userId, :email, :tokenHash, :expiresAt, NOW())
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':tokenHash', $tokenHash);
        $stmt->bindValue(':expiresAt', $expiresAt);

        return $stmt->execute();
    }

    public function findActiveByTokenHash(string $tokenHash): ?array {
        $query = "
            SELECT *
            FROM password_reset_tokens
            WHERE token_hash = :tokenHash
            AND used_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':tokenHash', $tokenHash);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function markUsed(int $id): bool {
        $query = "UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function invalidateActiveTokensForUser(int $userId): bool {
        $query = "
            UPDATE password_reset_tokens
            SET used_at = NOW()
            WHERE user_id = :userId
            AND used_at IS NULL
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function ensureTable(): void {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_token_hash (token_hash),
                INDEX idx_password_reset_user_id (user_id),
                INDEX idx_password_reset_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
?>
