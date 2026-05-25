<?php
namespace App\Models;

use PDO;

class AdminNotificationModel {
    private PDO $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function getNotificationTypeIdByName($typeName) {
        $sql = "SELECT NotificationTypeID FROM notificationtypes WHERE TypeName = :typeName LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':typeName', $typeName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function createNotification($receiverUserId, $senderUserId, $postId, $commentId, $notificationTypeId) {
        if (!$receiverUserId || !$senderUserId || !$notificationTypeId) {
            return false;
        }

        $sql = "INSERT INTO notifications (ReceiverUserID, SenderUserID, PostID, CommentID, NotificationTypeID, Message, IsRead, CreatedAt)
                VALUES (:receiverUserId, :senderUserId, :postId, :commentId, :notificationTypeId, NULL, 0, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':receiverUserId', $receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(':senderUserId', $senderUserId, PDO::PARAM_INT);
        $stmt->bindValue(':postId', $postId, $postId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':commentId', $commentId, $commentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':notificationTypeId', $notificationTypeId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function createNotificationByType($receiverUserId, $senderUserId, $typeName) {
        $typeId = $this->getNotificationTypeIdByName($typeName);
        if (!$typeId) {
            return false;
        }

        return $this->createNotification($receiverUserId, $senderUserId, null, null, (int)$typeId);
    }

    private function notificationSelectSql(): string {
        return "SELECT n.NotificationID, n.NotificationTypeID, nt.TypeName,
                       n.ReceiverUserID, receiver.Username AS ReceiverUsername,
                       receiver.FullName AS ReceiverFullName, receiver.Email AS ReceiverEmail,
                       n.SenderUserID, sender.Username AS SenderUsername,
                       sender.FullName AS SenderFullName,
                       n.PostID, n.CommentID, n.Message, n.IsRead, n.CreatedAt
                FROM notifications n
                JOIN notificationtypes nt ON nt.NotificationTypeID = n.NotificationTypeID
                JOIN users receiver ON receiver.UserID = n.ReceiverUserID
                LEFT JOIN users sender ON sender.UserID = n.SenderUserID";
    }

    public function getAdminNotifications($keyword = '', $typeName = '', $isRead = ''): array {
        $conditions = [];
        $params = [];
        $allowedTypes = ['Like', 'Comment', 'Follow', 'ReportWarning', 'ContentHidden', 'RoleChanged', 'AccountLocked', 'AccountUnlocked', 'System'];

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $conditions[] = "(receiver.Username LIKE :keyword OR receiver.FullName LIKE :keyword OR receiver.Email LIKE :keyword OR n.Message LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($typeName !== '' && in_array($typeName, $allowedTypes, true)) {
            $conditions[] = "nt.TypeName = :typeName";
            $params[':typeName'] = $typeName;
        }

        if ($isRead !== '' && in_array((string)$isRead, ['0', '1'], true)) {
            $conditions[] = "n.IsRead = :isRead";
            $params[':isRead'] = (int)$isRead;
        }

        $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $sql = $this->notificationSelectSql() . $whereSql . " ORDER BY n.CreatedAt DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':isRead' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminNotificationDetail($notificationId) {
        $sql = $this->notificationSelectSql() . " WHERE n.NotificationID = :notificationId LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':notificationId', $notificationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteAdminNotification($notificationId): bool {
        $stmt = $this->conn->prepare("DELETE FROM notifications WHERE NotificationID = :notificationId");
        $stmt->bindValue(':notificationId', $notificationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function searchNotificationReceivers($keyword = '', $limit = 20): array {
        $keyword = trim((string)$keyword);
        $conditions = ["IsActive = 1"];
        $params = [];
        if ($keyword !== '') {
            $conditions[] = "(Username LIKE :keyword OR FullName LIKE :keyword OR Email LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $sql = "SELECT UserID, Username, FullName, Email, ProfilePictureUrl
                FROM users
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY FullName ASC, Username ASC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveNotificationReceiverById($userId) {
        $sql = "SELECT UserID FROM users WHERE UserID = :userId AND IsActive = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createSystemNotification($receiverUserId, $senderUserId, $message): bool {
        $typeId = $this->getNotificationTypeIdByName('System');
        if (!$typeId) {
            return false;
        }

        $sql = "INSERT INTO notifications (NotificationTypeID, ReceiverUserID, SenderUserID, PostID, CommentID, Message, CreatedAt, IsRead)
                VALUES (:typeId, :receiverUserId, :senderUserId, NULL, NULL, :message, NOW(), 0)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':typeId', (int)$typeId, PDO::PARAM_INT);
        $stmt->bindValue(':receiverUserId', (int)$receiverUserId, PDO::PARAM_INT);
        $stmt->bindValue(':senderUserId', (int)$senderUserId, PDO::PARAM_INT);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function createSystemNotificationsForActiveUsers($senderUserId, $message): int {
        $typeId = $this->getNotificationTypeIdByName('System');
        if (!$typeId) {
            return 0;
        }

        try {
            // Gửi hàng loạt nên bọc transaction để đếm và rollback dễ hơn nếu có lỗi.
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT UserID FROM users WHERE IsActive = 1 AND RoleID <> 1 AND UserID <> :senderUserId");
            $stmt->bindValue(':senderUserId', (int)$senderUserId, PDO::PARAM_INT);
            $stmt->execute();
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $insert = $this->conn->prepare("INSERT INTO notifications (NotificationTypeID, ReceiverUserID, SenderUserID, PostID, CommentID, Message, CreatedAt, IsRead)
                                            VALUES (:typeId, :receiverUserId, :senderUserId, NULL, NULL, :message, NOW(), 0)");
            $count = 0;
            foreach ($userIds as $userId) {
                $insert->bindValue(':typeId', (int)$typeId, PDO::PARAM_INT);
                $insert->bindValue(':receiverUserId', (int)$userId, PDO::PARAM_INT);
                $insert->bindValue(':senderUserId', (int)$senderUserId, PDO::PARAM_INT);
                $insert->bindValue(':message', $message, PDO::PARAM_STR);
                $insert->execute();
                $count++;
            }

            $this->conn->commit();
            return $count;
        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
?>
