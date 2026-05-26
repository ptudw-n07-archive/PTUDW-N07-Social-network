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

    public function createPost($userId, $content, $privacy = 'public') {
    $allowedPrivacy = ['public', 'followers', 'private'];
    $privacy = in_array($privacy, $allowedPrivacy, true) ? $privacy : 'public';

    $sql = "INSERT INTO posts (UserID, Content, CreatedAt, Privacy)
            VALUES (:userId, :content, NOW(), :privacy)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":content", $content);
    $stmt->bindParam(":privacy", $privacy);

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

public function repostPost($currentUserId, $originalPostId) {
    $originalPost = $this->getPostById((int) $originalPostId, (int) $currentUserId);

    if (!$originalPost || (int) $originalPost['UserID'] === (int) $currentUserId) {
        return false;
    }

    $username = trim((string) ($originalPost['Username'] ?? ''));
    $sourceName = $username !== '' ? '@' . $username : ($originalPost['FullName'] ?? 'người dùng');
    $content = "Đăng lại từ {$sourceName}:\n\n" . trim((string) ($originalPost['Content'] ?? ''));
    $images = array_values(array_filter(array_map('trim', explode(',', (string) ($originalPost['Images'] ?? '')))));

    $this->conn->beginTransaction();

    try {
        $postId = $this->createPost((int) $currentUserId, $content, (string) ($originalPost['Privacy'] ?? 'public'));

        if (!$postId) {
            $this->conn->rollBack();
            return false;
        }

        foreach ($images as $imageUrl) {
            if (!$this->addPostImage((int) $postId, $imageUrl)) {
                throw new \RuntimeException('Cannot copy repost image.');
            }
        }

        $this->conn->commit();

        return $this->getPostById((int) $postId, (int) $currentUserId);
    } catch (\Throwable $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }

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
            SELECT CommentID
            FROM comments
            WHERE CommentID = :parentCommentId
            AND PostID = :postId
            AND IsHidden = 0
            AND ParentCommentID IS NULL
            LIMIT 1
        ";
        $parentStmt = $this->conn->prepare($parentSql);
        $parentStmt->bindValue(":parentCommentId", $parentCommentId, PDO::PARAM_INT);
        $parentStmt->bindValue(":postId", (int) $postId, PDO::PARAM_INT);
        $parentStmt->execute();

        if (!$parentStmt->fetchColumn()) {
            return false;
        }
    }

    $sql = "INSERT INTO comments (PostID, UserID, Content, CreatedAt, ParentCommentID)
            VALUES (:postId, :userId, :content, NOW(), :parentCommentId)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":postId", (int) $postId, PDO::PARAM_INT);
    $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
    $stmt->bindValue(":content", $content);
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

public function getUserProfilePictureUrl($userId): string {
    $sql = "SELECT ProfilePictureUrl FROM users WHERE UserID = :userId LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
    $stmt->execute();

    return (string) ($stmt->fetchColumn() ?: '');
}

public function getCommentsByPostId($postId) {
    $sql = "
        SELECT 
            c.CommentID,
            c.PostID,
            c.Content,
            c.CreatedAt,
            c.ParentCommentID,
            u.UserID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        WHERE c.PostID = :postId
        AND c.IsHidden = 0
        ORDER BY
            COALESCE(c.ParentCommentID, c.CommentID) ASC,
            CASE WHEN c.ParentCommentID IS NULL THEN 0 ELSE 1 END ASC,
            c.CreatedAt ASC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getCommentsByPostIds(array $postIds): array {
    $normalizedPostIds = [];

    foreach ($postIds as $postId) {
        $postId = (int) $postId;

        if ($postId > 0) {
            $normalizedPostIds[$postId] = $postId;
        }
    }

    if (empty($normalizedPostIds)) {
        return [];
    }

    $commentsByPostId = [];
    $placeholders = [];

    foreach (array_values($normalizedPostIds) as $index => $postId) {
        $placeholder = ':postId' . $index;
        $placeholders[] = $placeholder;
        $commentsByPostId[$postId] = [];
    }

    $sql = "
        SELECT
            c.PostID,
            c.CommentID,
            c.Content,
            c.CreatedAt,
            c.ParentCommentID,
            u.UserID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        WHERE c.PostID IN (" . implode(', ', $placeholders) . ")
        AND c.IsHidden = 0
        ORDER BY
            c.PostID ASC,
            COALESCE(c.ParentCommentID, c.CommentID) ASC,
            CASE WHEN c.ParentCommentID IS NULL THEN 0 ELSE 1 END ASC,
            c.CreatedAt ASC
    ";

    $stmt = $this->conn->prepare($sql);

    foreach (array_values($normalizedPostIds) as $index => $postId) {
        $stmt->bindValue(':postId' . $index, $postId, PDO::PARAM_INT);
    }

    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $postId = (int) ($comment['PostID'] ?? 0);

        if (!isset($commentsByPostId[$postId])) {
            $commentsByPostId[$postId] = [];
        }

        $commentsByPostId[$postId][] = $comment;
    }

    return $commentsByPostId;
}

public function updateComment($commentId, $userId, $content): bool {
    $ownerSql = "
        SELECT 1
        FROM comments
        WHERE CommentID = :commentId
        AND UserID = :userId
        AND IsHidden = 0
        LIMIT 1
    ";
    $ownerStmt = $this->conn->prepare($ownerSql);
    $ownerStmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $ownerStmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
    $ownerStmt->execute();

    if (!$ownerStmt->fetchColumn()) {
        return false;
    }

    $sql = "
        UPDATE comments
        SET Content = :content
        WHERE CommentID = :commentId
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":content", $content);
    $stmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);

    return $stmt->execute();
}

public function hideComment($commentId, $currentUserId) {
    $sql = "
        SELECT
            c.CommentID,
            c.PostID,
            c.UserID AS CommentUserID,
            p.UserID AS PostOwnerID
        FROM comments c
        JOIN posts p ON p.PostID = c.PostID
        WHERE c.CommentID = :commentId
        AND c.IsHidden = 0
        LIMIT 1
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $stmt->execute();
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        return false;
    }

    $currentUserId = (int) $currentUserId;
    $isCommentOwner = (int) $comment['CommentUserID'] === $currentUserId;
    $isPostOwner = (int) $comment['PostOwnerID'] === $currentUserId;

    if (!$isCommentOwner && !$isPostOwner) {
        return false;
    }

    $replySql = "
        SELECT CommentID
        FROM comments
        WHERE ParentCommentID = :commentId
        AND IsHidden = 0
    ";
    $replyStmt = $this->conn->prepare($replySql);
    $replyStmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $replyStmt->execute();

    $hiddenCommentIds = [(int) $commentId];
    foreach ($replyStmt->fetchAll(PDO::FETCH_COLUMN) as $replyId) {
        $hiddenCommentIds[] = (int) $replyId;
    }

    $updateSql = "
        UPDATE comments
        SET IsHidden = 1
        WHERE CommentID = :commentId
        OR ParentCommentID = :commentId
    ";
    $updateStmt = $this->conn->prepare($updateSql);
    $updateStmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);

    if (!$updateStmt->execute()) {
        return false;
    }

    return [
        'success' => true,
        'hiddenCommentIds' => array_values(array_unique($hiddenCommentIds))
    ];
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
        JOIN posts p ON p.PostID = ph.PostID
        WHERE ph.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND p.IsHidden = 0
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
            COUNT(DISTINCT ph.PostID) AS TotalPosts
        FROM hashtags h
        JOIN posthashtags ph ON h.HashtagID = ph.HashtagID
        JOIN posts p ON p.PostID = ph.PostID AND p.IsHidden = 0
        GROUP BY h.HashtagID, h.HashtagName
        HAVING COUNT(DISTINCT ph.PostID) > 0
        ORDER BY TotalPosts DESC, h.HashtagName ASC
        LIMIT :limit
    ";

    $fallbackStmt = $this->conn->prepare($fallbackSql);
    $fallbackStmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $fallbackStmt->execute();

    return $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
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

public function createCommentReport($reporterUserId, $commentId, $reason, $details = ''): bool {
    $sql = "
        SELECT
            c.CommentID,
            c.PostID,
            c.UserID AS ReportedUserID
        FROM comments c
        JOIN posts p ON p.PostID = c.PostID
        WHERE c.CommentID = :commentId
        AND c.IsHidden = 0
        AND p.IsHidden = 0
        LIMIT 1
    ";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $stmt->execute();
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment || (int) $comment['ReportedUserID'] === (int) $reporterUserId) {
        return false;
    }

    $details = (string) $details;
    $duplicateSql = "
        SELECT 1
        FROM reports
        WHERE ReporterUserID = :reporterUserId
        AND CommentID = :commentId
        AND Reason = :reason
        AND Details = :details
        AND CreatedAt >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        LIMIT 1
    ";
    $duplicateStmt = $this->conn->prepare($duplicateSql);
    $duplicateStmt->bindValue(":reporterUserId", (int) $reporterUserId, PDO::PARAM_INT);
    $duplicateStmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $duplicateStmt->bindValue(":reason", (string) $reason);
    $duplicateStmt->bindValue(":details", $details);
    $duplicateStmt->execute();

    if ($duplicateStmt->fetchColumn()) {
        return true;
    }

    $insertSql = "
        INSERT INTO reports
            (ReporterUserID, ReportedUserID, PostID, CommentID, Reason, Details, CreatedAt, Status, AdminNote, ResolvedAt)
        VALUES
            (:reporterUserId, :reportedUserId, :postId, :commentId, :reason, :details, NOW(), 'Pending', NULL, NULL)
    ";
    $insertStmt = $this->conn->prepare($insertSql);
    $insertStmt->bindValue(":reporterUserId", (int) $reporterUserId, PDO::PARAM_INT);
    $insertStmt->bindValue(":reportedUserId", (int) $comment['ReportedUserID'], PDO::PARAM_INT);
    $insertStmt->bindValue(":postId", (int) $comment['PostID'], PDO::PARAM_INT);
    $insertStmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
    $insertStmt->bindValue(":reason", (string) $reason);
    $insertStmt->bindValue(":details", $details);

    return $insertStmt->execute();
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
