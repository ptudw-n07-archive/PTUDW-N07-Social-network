<?php
namespace App\Models; 

use PDO;

class PostModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
        $this->ensurePostInteractionSchema();
    }

    public function getAllPosts($viewerId = null) {
        $viewerLikeSelect = $viewerId ? "COUNT(DISTINCT viewer_likes.UserID) AS IsLiked," : "0 AS IsLiked,";
        $viewerLikeJoin = $viewerId ? "LEFT JOIN likes viewer_likes ON p.PostID = viewer_likes.PostID AND viewer_likes.UserID = :viewerId" : "";

        $sql = "
            SELECT 
                p.PostID,
                p.Content,
                p.CreatedAt,
                COALESCE(p.Privacy, 'public') AS Privacy,
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
            LEFT JOIN comments c ON p.PostID = c.PostID AND c.IsHidden = 0
            WHERE p.IsHidden = 0
            AND " . $this->visibilitySql($viewerId) . "
            GROUP BY p.PostID
            ORDER BY p.CreatedAt DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if ($viewerId) {
            $stmt->bindValue(":viewerId", (int) $viewerId, PDO::PARAM_INT);
        }
        $this->bindViewerParams($stmt, $viewerId);
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
                COALESCE(p.Privacy, 'public') AS Privacy,
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
            LEFT JOIN comments c ON p.PostID = c.PostID AND c.IsHidden = 0
            WHERE p.UserID = :userId
            AND " . $this->visibilitySql($viewerId) . "
            GROUP BY p.PostID
            ORDER BY p.CreatedAt DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);

        if ($viewerId) {
            $stmt->bindParam(":viewerId", $viewerId, PDO::PARAM_INT);
            $this->bindViewerParams($stmt, $viewerId);
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

    public function getUserById($userId) {
        $sql = "
            SELECT UserID, Username, FullName, ProfilePictureUrl
            FROM users
            WHERE UserID = :userId
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPostById($postId, $viewerId = null) {
        $viewerLikeSelect = $viewerId ? "COUNT(DISTINCT viewer_likes.UserID) AS IsLiked," : "0 AS IsLiked,";
        $viewerLikeJoin = $viewerId ? "LEFT JOIN likes viewer_likes ON p.PostID = viewer_likes.PostID AND viewer_likes.UserID = :viewerId" : "";

        $sql = "
            SELECT
                p.PostID,
                p.Content,
                p.CreatedAt,
                COALESCE(p.Privacy, 'public') AS Privacy,
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
            LEFT JOIN comments c ON p.PostID = c.PostID AND c.IsHidden = 0
            WHERE p.PostID = :postId
            AND p.IsHidden = 0
            AND " . $this->visibilitySql($viewerId) . "
            GROUP BY p.PostID
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);

        if ($viewerId) {
            $stmt->bindParam(":viewerId", $viewerId, PDO::PARAM_INT);
            $this->bindViewerParams($stmt, $viewerId);
        }

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
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

public function createRepost($currentUserId, $originalPostId) {
    $originalPost = $this->getPostById($originalPostId, $currentUserId);

    if (!$originalPost) {
        return false;
    }

    if ((int) $originalPost['UserID'] === (int) $currentUserId) {
        return false;
    }

    $sourceUsername = trim((string) ($originalPost['Username'] ?? ''));
    $sourceName = $sourceUsername !== '' ? '@' . $sourceUsername : 'người dùng';
    $sourceContent = trim((string) ($originalPost['Content'] ?? ''));
    $repostContent = "Đăng lại từ {$sourceName}:";

    if ($sourceContent !== '') {
        $repostContent .= "\n\n" . $sourceContent;
    }

    try {
        $this->conn->beginTransaction();

        $postId = $this->createPost($currentUserId, $repostContent);
        if (!$postId) {
            $this->conn->rollBack();
            return false;
        }

        $imageUrls = array_values(array_filter(array_map('trim', explode(',', (string) ($originalPost['Images'] ?? '')))));

        foreach ($imageUrls as $imageUrl) {
            $this->addPostImage($postId, $imageUrl);
        }

        $this->conn->commit();
        return (int) $postId;
    } catch (\Throwable $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }

        error_log('[PostModel] createRepost failed: ' . $e->getMessage());
        return false;
    }
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
    $sql = "SELECT COUNT(DISTINCT UserID) AS total FROM likes WHERE PostID = :postId";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'];
}

public function createComment($userId, $postId, $content, $parentCommentId = null) {
    $parentCommentId = $parentCommentId ? (int) $parentCommentId : null;

    if ($parentCommentId !== null) {
        $parentSql = "
            SELECT CommentID, ParentCommentID
            FROM comments
            WHERE CommentID = :parentCommentId
            AND PostID = :postId
            AND IsHidden = 0
            LIMIT 1
        ";
        $parentStmt = $this->conn->prepare($parentSql);
        $parentStmt->bindParam(":parentCommentId", $parentCommentId, PDO::PARAM_INT);
        $parentStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $parentStmt->execute();
        $parentComment = $parentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$parentComment) {
            return false;
        }

        $parentCommentId = !empty($parentComment['ParentCommentID'])
            ? (int) $parentComment['ParentCommentID']
            : (int) $parentComment['CommentID'];
    }

    $sql = "INSERT INTO comments (PostID, UserID, Content, ParentCommentID, CreatedAt)
            VALUES (:postId, :userId, :content, :parentCommentId, NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":content", $content);
    $stmt->bindValue(":parentCommentId", $parentCommentId, $parentCommentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

    if ($stmt->execute()) {
        return $this->conn->lastInsertId();
    }

    return false;
}

public function getPostOwnerId($postId) {
    $sql = "SELECT UserID FROM posts WHERE PostID = :postId LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    $ownerId = $stmt->fetchColumn();
    return $ownerId ? (int) $ownerId : null;
}
public function getCommentsByPostId($postId) {
    $sql = "
        SELECT 
            c.CommentID,
            c.PostID,
            c.UserID,
            c.Content,
            c.CreatedAt,
            c.ParentCommentID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        WHERE c.PostID = :postId
        AND c.IsHidden = 0
        ORDER BY c.CreatedAt ASC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getCommentsByPostIds(array $postIds): array {
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));

    if (empty($postIds)) {
        return [];
    }

    $placeholders = [];
    foreach ($postIds as $index => $postId) {
        $placeholders[] = ":postId{$index}";
    }

    $sql = "
        SELECT 
            c.CommentID,
            c.PostID,
            c.UserID,
            c.Content,
            c.CreatedAt,
            c.ParentCommentID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        WHERE c.PostID IN (" . implode(',', $placeholders) . ")
        AND c.IsHidden = 0
        ORDER BY c.CreatedAt ASC
    ";

    $stmt = $this->conn->prepare($sql);

    foreach ($postIds as $index => $postId) {
        $stmt->bindValue(":postId{$index}", $postId, PDO::PARAM_INT);
    }

    $stmt->execute();
    $commentsByPostId = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $commentsByPostId[(int) $comment['PostID']][] = $comment;
    }

    return $commentsByPostId;
}

public function getCommentById($commentId) {
    $sql = "
        SELECT 
            c.CommentID,
            c.PostID,
            c.UserID,
            c.Content,
            c.CreatedAt,
            c.ParentCommentID,
            c.IsHidden,
            p.UserID AS PostOwnerID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN posts p ON p.PostID = c.PostID
        JOIN users u ON u.UserID = c.UserID
        WHERE c.CommentID = :commentId
        LIMIT 1
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateComment($commentId, $userId, $content): bool {
    $sql = "
        UPDATE comments
        SET Content = :content
        WHERE CommentID = :commentId
        AND UserID = :userId
        AND IsHidden = 0
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":content", $content);
    $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}

public function hideComment($commentId, $currentUserId): bool {
    $comment = $this->getCommentById($commentId);

    if (!$comment || (int) ($comment['IsHidden'] ?? 0) === 1) {
        return false;
    }

    $isCommentOwner = (int) $comment['UserID'] === (int) $currentUserId;
    $isPostOwner = (int) $comment['PostOwnerID'] === (int) $currentUserId;

    if (!$isCommentOwner && !$isPostOwner) {
        return false;
    }

    $sql = "UPDATE comments SET IsHidden = 1 WHERE CommentID = :commentId OR ParentCommentID = :commentId";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}

public function createCommentReport($reporterUserId, $commentId, string $reason, string $details = ''): bool {
    $comment = $this->getCommentById($commentId);

    if (!$comment || (int) ($comment['IsHidden'] ?? 0) === 1) {
        return false;
    }

    if ((int) $comment['UserID'] === (int) $reporterUserId) {
        return false;
    }

    if ($details === '') {
        $details = $reason;
    }

    $sql = "
        INSERT INTO reports
            (ReporterUserID, ReportedUserID, PostID, CommentID, Reason, Details, CreatedAt, Status, AdminNote, ResolvedAt)
        VALUES
            (:reporterUserId, :reportedUserId, :postId, :commentId, :reason, :details, NOW(), 'Pending', NULL, NULL)
    ";

    $stmt = $this->conn->prepare($sql);
    $reportedUserId = (int) $comment['UserID'];
    $postId = (int) $comment['PostID'];
    $stmt->bindParam(":reporterUserId", $reporterUserId, PDO::PARAM_INT);
    $stmt->bindParam(":reportedUserId", $reportedUserId, PDO::PARAM_INT);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
    $stmt->bindParam(":reason", $reason);
    $stmt->bindParam(":details", $details);

    return $stmt->execute();
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
    $sql = "
        SELECT
            h.HashtagID,
            h.HashtagName,
            COUNT(DISTINCT ph.PostID) AS TotalPosts
        FROM hashtags h
        JOIN posthashtags ph ON h.HashtagID = ph.HashtagID
        JOIN posts p ON p.PostID = ph.PostID
        WHERE p.IsHidden = 0
        AND (h.IsHidden = 0 OR h.IsHidden IS NULL)
        GROUP BY h.HashtagID, h.HashtagName
        ORDER BY TotalPosts DESC, h.HashtagName ASC
        LIMIT :limit
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":limit", (int) $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPostsByHashtag($tag, $viewerId = null) {
    $sql = "
        SELECT 
            p.PostID,
            p.Content,
            p.CreatedAt,
            COALESCE(p.Privacy, 'public') AS Privacy,
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
        LEFT JOIN comments c ON p.PostID = c.PostID AND c.IsHidden = 0
        WHERE h.HashtagName = :tag
        AND p.IsHidden = 0
        AND " . $this->visibilitySql($viewerId) . "
        GROUP BY p.PostID
        ORDER BY p.CreatedAt DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":tag", $tag);
    $this->bindViewerParams($stmt, $viewerId);
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

public function updatePostContent($postId, $userId, $content): bool {
    $sql = "UPDATE posts SET Content = :content WHERE PostID = :postId AND UserID = :userId";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":content", $content);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

public function removePostImages($postId, array $imageUrls): void {
    if (empty($imageUrls)) {
        return;
    }

    $sql = "DELETE FROM postimages WHERE PostID = :postId AND ImageUrl = :imageUrl";
    $stmt = $this->conn->prepare($sql);

    foreach ($imageUrls as $imageUrl) {
        $stmt->bindValue(":postId", (int) $postId, PDO::PARAM_INT);
        $stmt->bindValue(":imageUrl", (string) $imageUrl);
        $stmt->execute();
    }
}

public function replacePostHashtags($postId, array $hashtagNames): void {
    $oldSql = "SELECT HashtagID FROM posthashtags WHERE PostID = :postId";
    $oldStmt = $this->conn->prepare($oldSql);
    $oldStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $oldStmt->execute();
    $oldIds = array_map('intval', $oldStmt->fetchAll(PDO::FETCH_COLUMN));

    $deleteSql = "DELETE FROM posthashtags WHERE PostID = :postId";
    $deleteStmt = $this->conn->prepare($deleteSql);
    $deleteStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $deleteStmt->execute();

    if (!empty($hashtagNames)) {
        $this->syncPostHashtags($postId, $hashtagNames);
    }

    $this->refreshUsageCounts($oldIds);
}

public function deletePost($postId, $userId): bool {
    if ((int) $this->getPostOwnerId($postId) !== (int) $userId) {
        return false;
    }

    $this->conn->beginTransaction();

    try {
        foreach (['notifications', 'likes', 'comments', 'postimages', 'posthashtags', 'postpreferences', 'reports'] as $table) {
            $sql = "DELETE FROM {$table} WHERE PostID = :postId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt->execute();
        }

        $stmt = $this->conn->prepare("DELETE FROM posts WHERE PostID = :postId AND UserID = :userId");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
        $stmt->execute();

        $this->conn->commit();
        return $stmt->rowCount() > 0;
    } catch (\Throwable $e) {
        $this->conn->rollBack();
        return false;
    }
}

public function updatePostPrivacy($postId, $userId, string $privacy): bool {
    $allowed = ['public', 'private', 'followers'];
    if (!in_array($privacy, $allowed, true)) {
        return false;
    }

    $sql = "UPDATE posts SET Privacy = :privacy WHERE PostID = :postId AND UserID = :userId";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":privacy", $privacy);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

public function createReport($reporterUserId, $postId, string $reason, string $details): bool {
    $post = $this->getPostById($postId, $reporterUserId);
    if (!$post || (int) $post['UserID'] === (int) $reporterUserId) {
        return false;
    }

    $duplicateSql = "
        SELECT 1
        FROM reports
        WHERE ReporterUserID = :reporterUserId
        AND PostID = :postId
        AND Reason = :reason
        AND Details = :details
        AND CreatedAt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        LIMIT 1
    ";
    $duplicateStmt = $this->conn->prepare($duplicateSql);
    $duplicateStmt->bindParam(":reporterUserId", $reporterUserId, PDO::PARAM_INT);
    $duplicateStmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $duplicateStmt->bindParam(":reason", $reason);
    $duplicateStmt->bindParam(":details", $details);
    $duplicateStmt->execute();

    if ($duplicateStmt->fetchColumn()) {
        return true;
    }

    $sql = "
        INSERT INTO reports
            (ReporterUserID, ReportedUserID, PostID, CommentID, Reason, Details, CreatedAt, Status, AdminNote, ResolvedAt)
        VALUES
            (:reporterUserId, :reportedUserId, :postId, NULL, :reason, :details, NOW(), 'Pending', NULL, NULL)
    ";
    $stmt = $this->conn->prepare($sql);
    $reportedUserId = (int) $post['UserID'];
    $stmt->bindParam(":reporterUserId", $reporterUserId, PDO::PARAM_INT);
    $stmt->bindParam(":reportedUserId", $reportedUserId, PDO::PARAM_INT);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->bindParam(":reason", $reason);
    $stmt->bindParam(":details", $details);
    return $stmt->execute();
}

public function blockUser($blockerUserId, $blockedUserId): bool {
    if ((int) $blockerUserId === (int) $blockedUserId) {
        return false;
    }

    $checkSql = "
        SELECT 1
        FROM userblocks
        WHERE BlockerUserID = :blockerUserId
        AND BlockedUserID = :blockedUserId
        LIMIT 1
    ";
    $checkStmt = $this->conn->prepare($checkSql);
    $checkStmt->bindParam(":blockerUserId", $blockerUserId, PDO::PARAM_INT);
    $checkStmt->bindParam(":blockedUserId", $blockedUserId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->fetchColumn()) {
        return true;
    }

    $sql = "
        INSERT INTO userblocks (BlockerUserID, BlockedUserID, CreatedAt)
        VALUES (:blockerUserId, :blockedUserId, NOW())
    ";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":blockerUserId", $blockerUserId, PDO::PARAM_INT);
    $stmt->bindParam(":blockedUserId", $blockedUserId, PDO::PARAM_INT);
    return $stmt->execute();
}

public function markNotInterested($userId, $postId): bool {
    $sql = "
        INSERT IGNORE INTO postpreferences (UserID, PostID, PreferenceType, CreatedAt)
        VALUES (:userId, :postId, 'not_interested', NOW())
    ";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    return $stmt->execute();
}

private function visibilitySql($viewerId = null): string {
    if (!$viewerId) {
        return "COALESCE(p.Privacy, 'public') = 'public'";
    }

    return "
        (
            COALESCE(p.Privacy, 'public') = 'public'
            OR p.UserID = :privacyViewerId
            OR (
                p.Privacy = 'followers'
                AND EXISTS (
                    SELECT 1 FROM follows f
                    WHERE f.FollowerID = :followViewerId
                    AND f.FollowedID = p.UserID
                )
            )
        )
        AND NOT EXISTS (
            SELECT 1 FROM userblocks ub
            WHERE ub.BlockerUserID = :blockerUserId
            AND ub.BlockedUserID = p.UserID
        )
        AND NOT EXISTS (
            SELECT 1 FROM postpreferences pp
            WHERE pp.UserID = :preferenceUserId
            AND pp.PostID = p.PostID
            AND pp.PreferenceType = 'not_interested'
        )
    ";
}

private function bindViewerParams($stmt, $viewerId = null): void {
    if (!$viewerId) {
        return;
    }

    $stmt->bindValue(":privacyViewerId", (int) $viewerId, PDO::PARAM_INT);
    $stmt->bindValue(":followViewerId", (int) $viewerId, PDO::PARAM_INT);
    $stmt->bindValue(":blockerUserId", (int) $viewerId, PDO::PARAM_INT);
    $stmt->bindValue(":preferenceUserId", (int) $viewerId, PDO::PARAM_INT);
}

private function ensurePostInteractionSchema(): void {
    try {
        $this->conn->exec("ALTER TABLE posts ADD COLUMN Privacy VARCHAR(20) NOT NULL DEFAULT 'public'");
    } catch (\Throwable $e) {
    }

    $this->conn->exec("
        CREATE TABLE IF NOT EXISTS postpreferences (
            PreferenceID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            PostID INT NOT NULL,
            PreferenceType VARCHAR(50) NOT NULL,
            CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_post_preference (UserID, PostID, PreferenceType)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
}
?>
