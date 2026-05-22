<?php
namespace App\Models; 

use PDO;

class PostModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllPosts() {
        $sql = "
            SELECT 
                p.PostID,
                p.Content,
                p.CreatedAt,
                u.UserID,
                u.Username,
                u.FullName,
                u.ProfilePictureUrl,
                GROUP_CONCAT(DISTINCT pi.ImageUrl) AS Images,
                COUNT(DISTINCT l.UserID) AS LikeCount,
                COUNT(DISTINCT c.CommentID) AS CommentCount
            FROM posts p
            JOIN users u ON p.UserID = u.UserID
            LEFT JOIN postimages pi ON p.PostID = pi.PostID
            LEFT JOIN likes l ON p.PostID = l.PostID
            LEFT JOIN comments c ON p.PostID = c.PostID
            GROUP BY p.PostID
            ORDER BY p.CreatedAt DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostsByUserId($userId, $viewerId = null) {
        $viewerLikeSelect = $viewerId ? "COUNT(DISTINCT viewer_likes.UserID) AS IsLiked," : "0 AS IsLiked,";
        $viewerLikeJoin = $viewerId ? "LEFT JOIN likes viewer_likes ON p.PostID = viewer_likes.PostID AND viewer_likes.UserID = :viewerId" : "";

        $sql = "
            SELECT
                p.PostID,
                p.Content,
                p.CreatedAt,
                u.UserID,
                u.Username,
                u.FullName,
                u.ProfilePictureUrl,
                GROUP_CONCAT(DISTINCT pi.ImageUrl) AS Images,
                $viewerLikeSelect
                COUNT(DISTINCT l.UserID) AS LikeCount,
                COUNT(DISTINCT c.CommentID) AS CommentCount
            FROM posts p
            JOIN users u ON p.UserID = u.UserID
            LEFT JOIN postimages pi ON p.PostID = pi.PostID
            LEFT JOIN likes l ON p.PostID = l.PostID
            $viewerLikeJoin
            LEFT JOIN comments c ON p.PostID = c.PostID
            WHERE p.UserID = :userId
            GROUP BY p.PostID
            ORDER BY p.CreatedAt DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);

        if ($viewerId) {
            $stmt->bindParam(":viewerId", $viewerId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPostsByUserId($userId) {
        $sql = "SELECT COUNT(*) AS total FROM posts WHERE UserID = :userId";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function createPost($userId, $content) {
    $sql = "INSERT INTO posts (UserID, Content, CreatedAt)
            VALUES (:userId, :content, NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":content", $content);

    if ($stmt->execute()) {
        return $this->conn->lastInsertId();
    }

    return false;
}

public function addPostImage($postId, $imageUrl) {
    $sql = "INSERT INTO postimages (PostID, ImageUrl)
            VALUES (:postId, :imageUrl)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":imageUrl", $imageUrl);

    return $stmt->execute();
}

public function toggleLike($userId, $postId) {
    $checkSql = "SELECT * FROM likes 
                 WHERE UserID = :userId AND PostID = :postId";

    $checkStmt = $this->conn->prepare($checkSql);
    $checkStmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $checkStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $deleteSql = "DELETE FROM likes 
                      WHERE UserID = :userId AND PostID = :postId";

        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bindParam(":userId", $userId, PDO::PARAM_INT);
        $deleteStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $deleteStmt->execute();

        return "unliked";
    } else {
        $insertSql = "INSERT INTO likes (UserID, PostID, CreatedAt)
                      VALUES (:userId, :postId, NOW())";

        $insertStmt = $this->conn->prepare($insertSql);
        $insertStmt->bindParam(":userId", $userId, PDO::PARAM_INT);
        $insertStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $insertStmt->execute();

        return "liked";
    }
}

public function countLikes($postId) {
    $sql = "SELECT COUNT(*) AS total FROM likes WHERE PostID = :postId";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'];
}

public function createComment($userId, $postId, $content) {
    $sql = "INSERT INTO comments (PostID, UserID, Content, CreatedAt)
            VALUES (:postId, :userId, :content, NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":content", $content);

    return $stmt->execute();
}
public function getCommentsByPostId($postId) {
    $sql = "
        SELECT 
            c.CommentID,
            c.Content,
            c.CreatedAt,
            u.UserID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        WHERE c.PostID = :postId
        ORDER BY c.CreatedAt ASC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function syncPostHashtags($postId, array $hashtagNames) {
    if (empty($hashtagNames)) {
        return;
    }

    $hashtagIds = [];

    foreach ($hashtagNames as $name) {
        $hashtagId = $this->findOrCreateHashtag($name);

        if (!$hashtagId) {
            continue;
        }

        $hashtagIds[] = (int) $hashtagId;

        $existsSql = "
            SELECT 1
            FROM posthashtags
            WHERE PostID = :postId AND HashtagID = :hashtagId
            LIMIT 1
        ";
        $existsStmt = $this->conn->prepare($existsSql);
        $existsStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $existsStmt->bindParam(":hashtagId", $hashtagId, PDO::PARAM_INT);
        $existsStmt->execute();

        if ($existsStmt->fetchColumn()) {
            continue;
        }

        $insertSql = "
            INSERT INTO posthashtags (PostID, HashtagID, CreatedAt)
            VALUES (:postId, :hashtagId, NOW())
        ";
        $insertStmt = $this->conn->prepare($insertSql);
        $insertStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $insertStmt->bindParam(":hashtagId", $hashtagId, PDO::PARAM_INT);
        $insertStmt->execute();
    }

    $this->refreshUsageCounts(array_unique($hashtagIds));
}

public function getTrendingHashtags($limit = 10) {
    $recentSql = "
        SELECT 
            h.HashtagID,
            h.HashtagName,
            COUNT(ph.PostID) AS TotalPosts
        FROM hashtags h
        JOIN posthashtags ph ON h.HashtagID = ph.HashtagID
        WHERE ph.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY h.HashtagID, h.HashtagName
        ORDER BY TotalPosts DESC
        LIMIT :limit
    ";

    $recentStmt = $this->conn->prepare($recentSql);
    $recentStmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $recentStmt->execute();
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($recent)) {
        return $recent;
    }

    $fallbackSql = "
        SELECT
            h.HashtagID,
            h.HashtagName,
            h.UsageCount AS TotalPosts
        FROM hashtags h
        WHERE h.UsageCount > 0
        ORDER BY h.UsageCount DESC
        LIMIT :limit
    ";

    $fallbackStmt = $this->conn->prepare($fallbackSql);
    $fallbackStmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $fallbackStmt->execute();

    return $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPostsByHashtag($tag) {
    $sql = "
        SELECT 
            p.PostID,
            p.Content,
            p.CreatedAt,
            u.UserID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl,
            GROUP_CONCAT(DISTINCT pi.ImageUrl) AS Images,
            COUNT(DISTINCT l.UserID) AS LikeCount,
            COUNT(DISTINCT c.CommentID) AS CommentCount
        FROM posts p
        JOIN users u ON p.UserID = u.UserID
        JOIN posthashtags ph ON p.PostID = ph.PostID
        JOIN hashtags h ON ph.HashtagID = h.HashtagID
        LEFT JOIN postimages pi ON p.PostID = pi.PostID
        LEFT JOIN likes l ON p.PostID = l.PostID
        LEFT JOIN comments c ON p.PostID = c.PostID
        WHERE h.HashtagName = :tag
        GROUP BY p.PostID
        ORDER BY p.CreatedAt DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":tag", $tag);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

private function findOrCreateHashtag($name) {
    $selectSql = "
        SELECT HashtagID
        FROM hashtags
        WHERE LOWER(HashtagName) = LOWER(:name)
        LIMIT 1
    ";
    $selectStmt = $this->conn->prepare($selectSql);
    $selectStmt->bindParam(":name", $name);
    $selectStmt->execute();
    $existingId = $selectStmt->fetchColumn();

    if ($existingId) {
        return (int) $existingId;
    }

    $insertSql = "INSERT INTO hashtags (HashtagName, UsageCount) VALUES (:name, 0)";
    $insertStmt = $this->conn->prepare($insertSql);
    $insertStmt->bindParam(":name", $name);

    if (!$insertStmt->execute()) {
        return null;
    }

    return (int) $this->conn->lastInsertId();
}

private function refreshUsageCounts(array $hashtagIds) {
    if (empty($hashtagIds)) {
        return;
    }

    $sql = "
        UPDATE hashtags h
        SET UsageCount = (
            SELECT COUNT(DISTINCT ph.PostID)
            FROM posthashtags ph
            WHERE ph.HashtagID = h.HashtagID
        )
        WHERE h.HashtagID = :hashtagId
    ";

    $stmt = $this->conn->prepare($sql);

    foreach ($hashtagIds as $hashtagId) {
        $stmt->bindValue(":hashtagId", (int) $hashtagId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
}
?>
