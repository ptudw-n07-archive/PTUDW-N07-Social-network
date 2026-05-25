<?php
namespace App\Models;

use PDO;

class PasswordResetOtpModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->ensureTable();
    }

    public function create(int $userId, string $email, string $otpHash, int $ttlMinutes = 5): bool {
        $this->invalidateActiveOtps($userId);

        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlMinutes * 60));
        $query = "
            INSERT INTO password_reset_otps (UserID, Email, OtpHash, ExpiresAt, CreatedAt)
            VALUES (:userId, :email, :otpHash, :expiresAt, NOW())
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':otpHash', $otpHash);
        $stmt->bindValue(':expiresAt', $expiresAt);

        return $stmt->execute();
    }

    public function findLatestActiveByEmail(string $email): ?array {
        $query = "
            SELECT *
            FROM password_reset_otps
            WHERE Email = :email
            AND UsedAt IS NULL
            ORDER BY CreatedAt DESC, OtpID DESC
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function incrementAttempts(int $otpId): bool {
        $query = "UPDATE password_reset_otps SET Attempts = Attempts + 1 WHERE OtpID = :otpId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':otpId', $otpId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function markUsed(int $otpId): bool {
        $query = "UPDATE password_reset_otps SET UsedAt = NOW() WHERE OtpID = :otpId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':otpId', $otpId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function invalidateActiveOtps(int $userId): bool {
        $query = "
            UPDATE password_reset_otps
            SET UsedAt = NOW()
            WHERE UserID = :userId
            AND UsedAt IS NULL
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function ensureTable(): void {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS password_reset_otps (
                OtpID INT AUTO_INCREMENT PRIMARY KEY,
                UserID INT NOT NULL,
                Email VARCHAR(255) NOT NULL,
                OtpHash VARCHAR(255) NOT NULL,
                ExpiresAt DATETIME NOT NULL,
                UsedAt DATETIME NULL,
                Attempts INT NOT NULL DEFAULT 0,
                CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_email (Email),
                INDEX idx_password_reset_user (UserID),
                INDEX idx_password_reset_expires (ExpiresAt)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
?>
