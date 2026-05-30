<?php
namespace App\Models;

use PDO;

class SearchModel {
    private PDO $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function searchUsers(int $currentUserId, string $keyword, int $limit = 12): array {
        $keywordLike = '%' . $keyword . '%';

        $sql = "
            SELECT
                u.UserID,
                u.Username,
                u.FullName,
                u.ProfilePictureUrl,
                u.Bio,
                CASE WHEN f.FollowerID IS NULL THEN 0 ELSE 1 END AS IsFollowing
            FROM users u
            LEFT JOIN follows f
                ON f.FollowedID = u.UserID
                AND f.FollowerID = :followViewerId
            WHERE u.UserID != :excludeUserId
                AND (
                    u.Username LIKE :usernameKeyword
                    OR u.FullName LIKE :nameKeyword
                    OR u.Email LIKE :emailKeyword
                )
            ORDER BY
                CASE
                    WHEN u.Username LIKE :usernamePrefix THEN 0
                    WHEN u.FullName LIKE :namePrefix THEN 1
                    ELSE 2
                END,
                u.FullName ASC,
                u.Username ASC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        $prefixKeyword = $keyword . '%';
        $stmt->bindValue(':followViewerId', $currentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':excludeUserId', $currentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':usernameKeyword', $keywordLike);
        $stmt->bindValue(':nameKeyword', $keywordLike);
        $stmt->bindValue(':emailKeyword', $keywordLike);
        $stmt->bindValue(':usernamePrefix', $prefixKeyword);
        $stmt->bindValue(':namePrefix', $prefixKeyword);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchPosts(string $keyword, ?int $viewerId = null, int $limit = 5): array {
        $keywordLike = '%' . $keyword . '%';

        $sql = "
            SELECT
                p.PostID,
                p.Content,
                p.CreatedAt,
                u.UserID,
                u.Username,
                u.FullName,
                u.ProfilePictureUrl,
                (SELECT COUNT(DISTINCT l.UserID) FROM likes l WHERE l.PostID = p.PostID) AS LikeCount,
                (
                    SELECT COUNT(c.CommentID)
                    FROM comments c
                    WHERE c.PostID = p.PostID
                    AND c.IsHidden = 0
                ) AS CommentCount,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM likes viewer_likes
                        WHERE viewer_likes.PostID = p.PostID
                        AND viewer_likes.UserID = :viewerId
                    ) THEN 1
                    ELSE 0
                END AS IsLiked
            FROM posts p
            JOIN users u ON p.UserID = u.UserID
            WHERE p.IsHidden = 0
            AND p.Content LIKE :keyword
            ORDER BY p.CreatedAt DESC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':keyword', $keywordLike);
        $stmt->bindValue(':viewerId', (int) ($viewerId ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function suggestHashtags(string $keyword, int $limit = 8): array {
        $keywordPrefix = $keyword . '%';
        $keywordContains = '%' . $keyword . '%';

        $sql = "
            SELECT 
                HashtagID,
                HashtagName,
                UsageCount
            FROM hashtags
            WHERE (IsHidden = 0 OR IsHidden IS NULL)
              AND (
                  HashtagName LIKE :keywordPrefix
                  OR HashtagName LIKE :keywordContains
              )
            ORDER BY
                CASE WHEN HashtagName LIKE :keywordPrefixOrder THEN 0 ELSE 1 END,
                UsageCount DESC,
                HashtagName ASC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':keywordPrefix', $keywordPrefix);
        $stmt->bindValue(':keywordContains', $keywordContains);
        $stmt->bindValue(':keywordPrefixOrder', $keywordPrefix);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function ($row) {
            return [
                'HashtagID' => (int) ($row['HashtagID'] ?? 0),
                'HashtagName' => $row['HashtagName'] ?? '',
                'UsageCount' => (int) ($row['UsageCount'] ?? 0)
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function saveHistory(int $userId, string $keyword): bool {
        $keyword = $this->normalizeKeyword($keyword);

        if ($keyword === '') {
            return false;
        }

        $deleteSql = "DELETE FROM search_history WHERE UserID = :userId AND Keyword = :keyword";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $deleteStmt->bindValue(':keyword', $keyword);
        $deleteStmt->execute();

        $insertSql = "INSERT INTO search_history (UserID, Keyword, CreatedAt) VALUES (:userId, :keyword, NOW())";
        $insertStmt = $this->conn->prepare($insertSql);
        $insertStmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':keyword', $keyword);

        return $insertStmt->execute();
    }

    public function getHistory(int $userId, int $limit = 5): array {
        $sql = "
            SELECT SearchID, Keyword, CreatedAt
            FROM search_history
            WHERE UserID = :userId
            ORDER BY CreatedAt DESC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteHistoryItem(int $userId, int $searchId): bool {
        $sql = "DELETE FROM search_history WHERE UserID = :userId AND SearchID = :searchId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':searchId', $searchId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function clearHistory(int $userId): bool {
        $sql = "DELETE FROM search_history WHERE UserID = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function normalizeKeyword(string $keyword): string {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');

        if (function_exists('mb_substr')) {
            return mb_substr($keyword, 0, 255);
        }

        return substr($keyword, 0, 255);
    }
}
?>
