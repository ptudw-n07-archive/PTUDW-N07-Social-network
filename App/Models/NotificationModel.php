<?php
namespace App\Models;

use PDO;

class NotificationModel {
    private $conn;
    private $hasMessageColumn = null;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTypeIdByName($typeName) {
        $sql = "SELECT NotificationTypeID FROM notificationtypes WHERE TypeName = :typeName LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":typeName", (string) $typeName, PDO::PARAM_STR);
        $stmt->execute();

        $typeId = $stmt->fetchColumn();
        return $typeId ? (int) $typeId : null;
    }

    public function createNotification($typeId, $receiverUserId, $senderUserId, $postId = null, $commentId = null, $message = null) {
        if ((int) $receiverUserId === (int) $senderUserId) {
            return false;
        }

        $existingId = $this->findExistingNotification($typeId, $receiverUserId, $senderUserId, $postId, $commentId);

        if ($existingId) {
            $sql = "
                UPDATE notifications
                SET Message = :message, IsRead = 0, CreatedAt = NOW()
                WHERE NotificationID = :notificationId
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":message", $message, $message === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(":notificationId", $existingId, PDO::PARAM_INT);
            return $stmt->execute() ? $existingId : false;
        }

        $sql = "
            INSERT INTO notifications
                (NotificationTypeID, ReceiverUserID, SenderUserID, PostID, CommentID, Message, CreatedAt, IsRead)
            VALUES
                (:typeId, :receiverUserId, :senderUserId, :postId, :commentId, :message, NOW(), 0)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":typeId", (int) $typeId, PDO::PARAM_INT);
        $stmt->bindValue(":receiverUserId", (int) $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(":senderUserId", (int) $senderUserId, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ":postId", $postId);
        $this->bindNullableInt($stmt, ":commentId", $commentId);
        $stmt->bindValue(":message", $message, $message === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        return $this->conn->lastInsertId();
    }

    public function getNotificationsByUser($userId) {
        $sql = "
            SELECT
                n.NotificationID,
                n.NotificationTypeID,
                nt.TypeName,
                n.ReceiverUserID,
                n.SenderUserID,
                sender.Username AS SenderUsername,
                sender.FullName AS SenderName,
                sender.ProfilePictureUrl AS SenderAvatar,
                n.PostID,
                n.CommentID,
                n.Message AS NotificationMessage,
                c.Content AS CommentContent,
                p.Content AS PostContent,
                (
                    SELECT MIN(pi.ImageUrl)
                    FROM postimages pi
                    WHERE pi.PostID = n.PostID
                ) AS PostThumbnail,
                n.CreatedAt,
                n.IsRead
            FROM notifications n
            JOIN notificationtypes nt ON nt.NotificationTypeID = n.NotificationTypeID
            LEFT JOIN users sender ON sender.UserID = n.SenderUserID
            LEFT JOIN posts p ON p.PostID = n.PostID
            LEFT JOIN comments c ON c.CommentID = n.CommentID
            WHERE n.ReceiverUserID = :userId
            ORDER BY n.CreatedAt DESC, n.NotificationID DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationByUser($notificationId, $userId) {
        $sql = "
            SELECT
                n.NotificationID,
                n.NotificationTypeID,
                nt.TypeName,
                n.SenderUserID,
                n.PostID,
                n.CommentID,
                n.Message AS NotificationMessage,
                p.IsHidden AS PostIsHidden
            FROM notifications n
            JOIN notificationtypes nt ON nt.NotificationTypeID = n.NotificationTypeID
            LEFT JOIN posts p ON p.PostID = n.PostID
            WHERE n.NotificationID = :notificationId AND n.ReceiverUserID = :userId
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":notificationId", (int) $notificationId, PDO::PARAM_INT);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getModerationNotificationDetailByUser($notificationId, $userId) {
        $messageSelect = $this->hasNotificationMessageColumn()
            ? ", n.Message AS NotificationMessage"
            : ", NULL AS NotificationMessage";

        $sql = "
            SELECT
                n.NotificationID,
                n.NotificationTypeID,
                nt.TypeName,
                n.ReceiverUserID,
                n.SenderUserID,
                n.PostID,
                n.CommentID,
                n.CreatedAt,
                n.IsRead,
                r.ReportID,
                r.Reason AS ReportReason,
                r.Details AS ReportDetails,
                r.AdminNote AS ReportAdminNote,
                r.Status AS ReportStatus,
                r.ResolvedAt AS ReportResolvedAt,
                r.CreatedAt AS ReportCreatedAt,
                r.PostID AS ReportPostID,
                r.CommentID AS ReportCommentID,
                p.PostID AS RelatedPostID,
                p.Content AS PostContent,
                p.CreatedAt AS PostCreatedAt,
                p.IsHidden AS PostIsHidden,
                c.CommentID AS RelatedCommentID,
                c.Content AS CommentContent,
                c.CreatedAt AS CommentCreatedAt,
                c.IsHidden AS CommentIsHidden,
                (
                    SELECT MIN(pi.ImageUrl)
                    FROM postimages pi
                    WHERE pi.PostID = p.PostID
                ) AS PostThumbnail
                $messageSelect
            FROM notifications n
            JOIN notificationtypes nt ON nt.NotificationTypeID = n.NotificationTypeID
            LEFT JOIN reports r ON r.ReportID = (
                SELECT r2.ReportID
                FROM reports r2
                WHERE r2.ReportedUserID = n.ReceiverUserID
                  AND r2.Status = 'Resolved'
                  AND (
                      (n.PostID IS NOT NULL AND r2.PostID = n.PostID)
                      OR (n.PostID IS NULL AND n.CommentID IS NOT NULL AND r2.CommentID = n.CommentID)
                      OR (n.PostID IS NULL AND n.CommentID IS NULL)
                  )
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, COALESCE(r2.ResolvedAt, r2.CreatedAt), n.CreatedAt)) ASC,
                         r2.ReportID DESC
                LIMIT 1
            )
            LEFT JOIN comments c ON c.CommentID = COALESCE(n.CommentID, r.CommentID)
            LEFT JOIN posts p ON p.PostID = COALESCE(n.PostID, r.PostID, c.PostID)
            WHERE n.NotificationID = :notificationId
              AND n.ReceiverUserID = :userId
              AND (n.NotificationTypeID IN (4, 5) OR nt.TypeName IN ('ReportWarning', 'ContentHidden'))
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":notificationId", (int) $notificationId, PDO::PARAM_INT);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId, $userId) {
        $sql = "
            UPDATE notifications
            SET IsRead = 1
            WHERE NotificationID = :notificationId AND ReceiverUserID = :userId
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":notificationId", (int) $notificationId, PDO::PARAM_INT);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications SET IsRead = 1 WHERE ReceiverUserID = :userId AND IsRead = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function countUnread($userId) {
        $sql = "
            SELECT COUNT(*)
            FROM notifications
            WHERE ReceiverUserID = :userId AND IsRead = 0
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countAfterMarker($userId, $createdAt, $notificationId) {
        $sql = "
            SELECT COUNT(*)
            FROM notifications
            WHERE ReceiverUserID = :userId
              AND (
                  CreatedAt > :createdAt
                  OR (CreatedAt = :createdAt AND NotificationID > :notificationId)
              )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userId", (int) $userId, PDO::PARAM_INT);
        $stmt->bindValue(":createdAt", (string) $createdAt, PDO::PARAM_STR);
        $stmt->bindValue(":notificationId", (int) $notificationId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function deleteNotification($typeId, $receiverUserId, $senderUserId, $postId = null, $commentId = null) {
        $sql = "
            DELETE FROM notifications
            WHERE NotificationTypeID = :typeId
              AND ReceiverUserID = :receiverUserId
              AND SenderUserID = :senderUserId
              AND " . ($postId === null ? "PostID IS NULL" : "PostID = :postId") . "
              AND " . ($commentId === null ? "CommentID IS NULL" : "CommentID = :commentId") . "
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":typeId", (int) $typeId, PDO::PARAM_INT);
        $stmt->bindValue(":receiverUserId", (int) $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(":senderUserId", (int) $senderUserId, PDO::PARAM_INT);

        if ($postId !== null) {
            $stmt->bindValue(":postId", (int) $postId, PDO::PARAM_INT);
        }

        if ($commentId !== null) {
            $stmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    private function findExistingNotification($typeId, $receiverUserId, $senderUserId, $postId = null, $commentId = null) {
        $sql = "
            SELECT NotificationID
            FROM notifications
            WHERE NotificationTypeID = :typeId
              AND ReceiverUserID = :receiverUserId
              AND SenderUserID = :senderUserId
              AND " . ($postId === null ? "PostID IS NULL" : "PostID = :postId") . "
              AND " . ($commentId === null ? "CommentID IS NULL" : "CommentID = :commentId") . "
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":typeId", (int) $typeId, PDO::PARAM_INT);
        $stmt->bindValue(":receiverUserId", (int) $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(":senderUserId", (int) $senderUserId, PDO::PARAM_INT);

        if ($postId !== null) {
            $stmt->bindValue(":postId", (int) $postId, PDO::PARAM_INT);
        }

        if ($commentId !== null) {
            $stmt->bindValue(":commentId", (int) $commentId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchColumn();
    }

    private function bindNullableInt($stmt, $name, $value) {
        if ($value === null || $value === '') {
            $stmt->bindValue($name, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($name, (int) $value, PDO::PARAM_INT);
    }

    private function hasNotificationMessageColumn() {
        if ($this->hasMessageColumn !== null) {
            return $this->hasMessageColumn;
        }

        $stmt = $this->conn->prepare("SHOW COLUMNS FROM notifications LIKE 'Message'");
        $stmt->execute();
        $this->hasMessageColumn = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->hasMessageColumn;
    }
}
?>
