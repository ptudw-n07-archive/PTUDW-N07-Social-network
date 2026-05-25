<?php
namespace App\Models;

require_once __DIR__ . '/AdminNotificationModel.php';

use PDO;

class AdminContentModel {
    private PDO $conn;
    private AdminNotificationModel $notificationModel;

    public function __construct($db_connection, ?AdminNotificationModel $notificationModel = null) {
        $this->conn = $db_connection;
        $this->notificationModel = $notificationModel ?? new AdminNotificationModel($db_connection);
    }

    public function getAdminContentPosts($keyword = '', $status = '', $privacy = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(u.Username LIKE :keyword OR u.FullName LIKE :keyword OR u.Email LIKE :keyword OR p.Content LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "p.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        if ($privacy !== '' && in_array($privacy, ['public', 'private'], true)) {
            $conditions[] = "p.Privacy = :privacy";
            $params[':privacy'] = $privacy;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                       u.Username, u.FullName, u.Email, u.ProfilePictureUrl,
                       (
                           SELECT pi.ImageUrl
                           FROM postimages pi
                           WHERE pi.PostID = p.PostID
                           ORDER BY pi.PostImageID ASC
                           LIMIT 1
                       ) AS ThumbnailUrl,
                       COUNT(DISTINCT l.UserID) AS LikeCount,
                       COUNT(DISTINCT c.CommentID) AS CommentCount
                FROM posts p
                JOIN users u ON u.UserID = p.UserID
                LEFT JOIN likes l ON l.PostID = p.PostID
                LEFT JOIN comments c ON c.PostID = p.PostID
                $whereSql
                GROUP BY p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                         u.Username, u.FullName, u.Email, u.ProfilePictureUrl
                ORDER BY p.CreatedAt DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminContentPostDetail($postId) {
        $sql = "SELECT p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                       u.Username, u.FullName, u.Email, u.ProfilePictureUrl,
                       COUNT(DISTINCT l.UserID) AS LikeCount,
                       COUNT(DISTINCT c.CommentID) AS CommentCount
                FROM posts p
                JOIN users u ON u.UserID = p.UserID
                LEFT JOIN likes l ON l.PostID = p.PostID
                LEFT JOIN comments c ON c.PostID = p.PostID
                WHERE p.PostID = :postId
                GROUP BY p.PostID, p.UserID, p.Content, p.CreatedAt, p.Privacy, p.IsHidden,
                         u.Username, u.FullName, u.Email, u.ProfilePictureUrl
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            return null;
        }

        $post['images'] = $this->getPostImagesByPostId($postId);
        return $post;
    }

    public function getPostOwnerAndStatus($postId) {
        $sql = "SELECT PostID, UserID, IsHidden FROM posts WHERE PostID = :postId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePostHiddenStatus($postId, $isHidden, $adminUserId = null) {
        $post = $this->getPostOwnerAndStatus($postId);
        if (!$post) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE posts SET IsHidden = :isHidden WHERE PostID = :postId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$isHidden === 1 && $adminUserId) {
            $typeId = $this->notificationModel->getNotificationTypeIdByName('ContentHidden');
            if ($typeId) {
                $this->notificationModel->createNotification((int)$post['UserID'], (int)$adminUserId, $postId, null, (int)$typeId);
            }
        }

        return $this->getAdminContentPostDetail($postId);
    }

    public function deleteAdminContentPost($postId, $adminUserId = null) {
        $post = $this->getPostOwnerAndStatus($postId);
        if (!$post) {
            return false;
        }

        try {
            // Xóa bài viết kéo theo nhiều bảng liên quan, nên cần transaction.
            $this->conn->beginTransaction();

            if ($adminUserId) {
                $typeId = $this->notificationModel->getNotificationTypeIdByName('ContentHidden');
                if ($typeId) {
                    $this->notificationModel->createNotification((int)$post['UserID'], (int)$adminUserId, null, null, (int)$typeId);
                }
            }

            $commentIds = $this->getCommentIdsByPostId($postId);
            if (!empty($commentIds)) {
                $placeholders = $this->placeholders($commentIds);

                $stmt = $this->conn->prepare("DELETE FROM notifications WHERE PostID = ? OR CommentID IN ($placeholders)");
                $stmt->execute(array_merge([$postId], $commentIds));

                $stmt = $this->conn->prepare("UPDATE reports
                    SET Status = 'Resolved',
                        ResolvedAt = COALESCE(ResolvedAt, NOW()),
                        AdminNote = CASE
                            WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                            ELSE AdminNote
                        END,
                        PostID = NULL,
                        CommentID = NULL
                    WHERE PostID = ? OR CommentID IN ($placeholders)");
                $stmt->execute(array_merge([$postId], $commentIds));
            } else {
                $stmt = $this->conn->prepare("DELETE FROM notifications WHERE PostID = ?");
                $stmt->execute([$postId]);

                $stmt = $this->conn->prepare("UPDATE reports
                    SET Status = 'Resolved',
                        ResolvedAt = COALESCE(ResolvedAt, NOW()),
                        AdminNote = CASE
                            WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                            ELSE AdminNote
                        END,
                        PostID = NULL
                    WHERE PostID = ?");
                $stmt->execute([$postId]);
            }

            $stmt = $this->conn->prepare("DELETE FROM likes WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM posthashtags WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM postimages WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM comments WHERE PostID = ?");
            $stmt->execute([$postId]);

            $stmt = $this->conn->prepare("DELETE FROM posts WHERE PostID = ?");
            $stmt->execute([$postId]);

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getAdminContentComments($keyword = '', $status = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(cu.Username LIKE :keyword OR cu.FullName LIKE :keyword OR cu.Email LIKE :keyword OR c.Content LIKE :keyword OR p.Content LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "c.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT c.CommentID, c.PostID, c.UserID, c.Content, c.CreatedAt, c.ParentCommentID, c.IsHidden,
                       cu.Username, cu.FullName, cu.Email, cu.ProfilePictureUrl,
                       p.Content AS PostContent,
                       pu.UserID AS PostAuthorID, pu.Username AS PostAuthorUsername, pu.FullName AS PostAuthorFullName
                FROM comments c
                JOIN users cu ON cu.UserID = c.UserID
                JOIN posts p ON p.PostID = c.PostID
                JOIN users pu ON pu.UserID = p.UserID
                $whereSql
                ORDER BY c.CreatedAt DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminContentCommentDetail($commentId) {
        $sql = "SELECT c.CommentID, c.PostID, c.UserID, c.Content, c.CreatedAt, c.ParentCommentID, c.IsHidden,
                       cu.Username, cu.FullName, cu.Email, cu.ProfilePictureUrl,
                       p.Content AS PostContent, p.CreatedAt AS PostCreatedAt,
                       pu.UserID AS PostAuthorID, pu.Username AS PostAuthorUsername, pu.FullName AS PostAuthorFullName,
                       pc.Content AS ParentContent, pcu.Username AS ParentUsername, pcu.FullName AS ParentFullName
                FROM comments c
                JOIN users cu ON cu.UserID = c.UserID
                JOIN posts p ON p.PostID = c.PostID
                JOIN users pu ON pu.UserID = p.UserID
                LEFT JOIN comments pc ON pc.CommentID = c.ParentCommentID
                LEFT JOIN users pcu ON pcu.UserID = pc.UserID
                WHERE c.CommentID = :commentId
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCommentOwnerAndStatus($commentId) {
        $sql = "SELECT CommentID, PostID, UserID, IsHidden FROM comments WHERE CommentID = :commentId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCommentHiddenStatus($commentId, $isHidden, $adminUserId = null) {
        $comment = $this->getCommentOwnerAndStatus($commentId);
        if (!$comment) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE comments SET IsHidden = :isHidden WHERE CommentID = :commentId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':commentId', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$isHidden === 1 && $adminUserId) {
            $typeId = $this->notificationModel->getNotificationTypeIdByName('ContentHidden');
            if ($typeId) {
                $this->notificationModel->createNotification((int)$comment['UserID'], (int)$adminUserId, (int)$comment['PostID'], $commentId, (int)$typeId);
            }
        }

        return $this->getAdminContentCommentDetail($commentId);
    }

    public function deleteAdminContentComment($commentId, $adminUserId = null) {
        $comment = $this->getCommentOwnerAndStatus($commentId);
        if (!$comment) {
            return false;
        }

        $commentIds = $this->getDescendantCommentIds($commentId);

        try {
            // Xóa comment cha thì xóa luôn các comment con để không còn dữ liệu mồ côi.
            $this->conn->beginTransaction();

            if ($adminUserId) {
                $typeId = $this->notificationModel->getNotificationTypeIdByName('ContentHidden');
                if ($typeId) {
                    $this->notificationModel->createNotification((int)$comment['UserID'], (int)$adminUserId, (int)$comment['PostID'], null, (int)$typeId);
                }
            }

            $placeholders = $this->placeholders($commentIds);

            $stmt = $this->conn->prepare("DELETE FROM notifications WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $stmt = $this->conn->prepare("UPDATE reports
                SET Status = 'Resolved',
                    ResolvedAt = COALESCE(ResolvedAt, NOW()),
                    AdminNote = CASE
                        WHEN AdminNote IS NULL OR TRIM(AdminNote) = '' THEN 'Tự động hoàn tất vì nội dung/tài khoản đã được xử lý ở báo cáo khác.'
                        ELSE AdminNote
                    END,
                    CommentID = NULL
                WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $stmt = $this->conn->prepare("DELETE FROM comments WHERE CommentID IN ($placeholders)");
            $stmt->execute($commentIds);

            $this->conn->commit();
            return $commentIds;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getAdminContentHashtags($keyword = '', $status = '') {
        $conditions = [];
        $params = [];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "h.HashtagName LIKE :keyword";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status !== '' && in_array((string)$status, ['0', '1'], true)) {
            $conditions[] = "h.IsHidden = :status";
            $params[':status'] = (int)$status;
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT h.HashtagID, h.HashtagName, h.UsageCount, h.CreatedAt, h.IsHidden,
                       COUNT(DISTINCT ph.PostID) AS PostCount
                FROM hashtags h
                LEFT JOIN posthashtags ph ON ph.HashtagID = h.HashtagID
                $whereSql
                GROUP BY h.HashtagID, h.HashtagName, h.UsageCount, h.CreatedAt, h.IsHidden
                ORDER BY h.CreatedAt DESC, h.HashtagID DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':status' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHashtagById($hashtagId) {
        $sql = "SELECT HashtagID, HashtagName, UsageCount, CreatedAt, IsHidden FROM hashtags WHERE HashtagID = :hashtagId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':hashtagId', $hashtagId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateHashtagHiddenStatus($hashtagId, $isHidden) {
        if (!$this->getHashtagById($hashtagId)) {
            return null;
        }

        $stmt = $this->conn->prepare("UPDATE hashtags SET IsHidden = :isHidden WHERE HashtagID = :hashtagId");
        $stmt->bindValue(':isHidden', $isHidden, PDO::PARAM_INT);
        $stmt->bindValue(':hashtagId', $hashtagId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->getHashtagById($hashtagId);
    }

    public function deleteAdminContentHashtag($hashtagId) {
        if (!$this->getHashtagById($hashtagId)) {
            return false;
        }

        try {
            // Hashtag chỉ liên kết qua bảng trung gian nên xóa liên kết trước, rồi xóa hashtag.
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM posthashtags WHERE HashtagID = ?");
            $stmt->execute([$hashtagId]);

            $stmt = $this->conn->prepare("DELETE FROM hashtags WHERE HashtagID = ?");
            $stmt->execute([$hashtagId]);

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function getCommentIdsByPostId($postId) {
        $stmt = $this->conn->prepare("SELECT CommentID FROM comments WHERE PostID = :postId");
        $stmt->bindValue(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getDescendantCommentIds($commentId) {
        // Duyệt cây comment theo hàng đợi để lấy cả comment con nhiều cấp.
        $ids = [(int)$commentId];
        $queue = [(int)$commentId];

        while (!empty($queue)) {
            $parentId = array_shift($queue);
            $stmt = $this->conn->prepare("SELECT CommentID FROM comments WHERE ParentCommentID = :parentId");
            $stmt->bindValue(':parentId', $parentId, PDO::PARAM_INT);
            $stmt->execute();
            $children = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            foreach ($children as $childId) {
                if (!in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $ids;
    }

    private function getPostImagesByPostId($postId) {
        $sql = "SELECT ImageUrl FROM postimages WHERE PostID = :postId ORDER BY ImageUrl ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function placeholders(array $items) {
        return implode(',', array_fill(0, count($items), '?'));
    }
}
?>
