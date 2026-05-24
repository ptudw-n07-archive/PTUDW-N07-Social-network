<?php
namespace App\Models;

use PDO;

class NotificationModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createNotification($typeId, $receiverUserId, $senderUserId, $postId = null, $commentId = null) {
        if ((int) $receiverUserId === (int) $senderUserId) {
            return false;
        }

        $existingId = $this->findExistingNotification($typeId, $receiverUserId, $senderUserId, $postId, $commentId);

        if ($existingId) {
            $sql = "
                UPDATE notifications
                SET IsRead = 0, CreatedAt = NOW()
                WHERE NotificationID = :notificationId
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":notificationId", $existingId, PDO::PARAM_INT);
            return $stmt->execute() ? $existingId : false;
        }

        $sql = "
            INSERT INTO notifications
                (NotificationTypeID, ReceiverUserID, SenderUserID, PostID, CommentID, CreatedAt, IsRead)
            VALUES
                (:typeId, :receiverUserId, :senderUserId, :postId, :commentId, NOW(), 0)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":typeId", (int) $typeId, PDO::PARAM_INT);
        $stmt->bindValue(":receiverUserId", (int) $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(":senderUserId", (int) $senderUserId, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ":postId", $postId);
        $this->bindNullableInt($stmt, ":commentId", $commentId);

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
            SELECT NotificationID, NotificationTypeID, SenderUserID, PostID, CommentID
            FROM notifications
            WHERE NotificationID = :notificationId AND ReceiverUserID = :userId
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
}
?>
