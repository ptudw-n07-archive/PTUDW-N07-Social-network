<?php
require __DIR__ . '/Config/Database.php';
require __DIR__ . '/App/Models/NotificationModel.php';

use App\Models\NotificationModel;

function pass(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }

    echo 'PASS: ' . $label . PHP_EOL;
}

$db = (new Database())->connect();
$model = new NotificationModel($db);
$typeId = $model->getTypeIdByName('System');
pass((int) $typeId > 0, 'System type exists');

$users = $db->query('SELECT UserID FROM users ORDER BY UserID LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
pass(count($users) === 2, 'two fixture users exist');
$receiverId = (int) $users[0];
$senderId = (int) $users[1];
$message = 'Hệ thống sẽ bảo trì lúc 22:00 hôm nay.';

$db->beginTransaction();

try {
    $insert = $db->prepare(
        'INSERT INTO notifications
            (NotificationTypeID, ReceiverUserID, SenderUserID, PostID, CommentID, Message, CreatedAt, IsRead)
         VALUES (:typeId, :receiverId, :senderId, NULL, NULL, :message, NOW(), 0)'
    );
    $insert->execute([
        'typeId' => $typeId,
        'receiverId' => $receiverId,
        'senderId' => $senderId,
        'message' => $message
    ]);
    $messageId = (int) $db->lastInsertId();
    $insert->execute([
        'typeId' => $typeId,
        'receiverId' => $receiverId,
        'senderId' => $senderId,
        'message' => null
    ]);
    $blankId = (int) $db->lastInsertId();

    $rows = $model->getNotificationsByUser($receiverId);
    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['NotificationID']] = $row;
    }
    pass(($byId[$messageId]['TypeName'] ?? '') === 'System', 'list returns System TypeName');
    pass(($byId[$messageId]['NotificationMessage'] ?? '') === $message, 'list returns admin Message');
    pass(array_key_exists('NotificationMessage', $byId[$blankId]), 'list returns nullable Message');

    $detail = $model->getModerationNotificationDetailByUser($messageId, $receiverId);
    pass($detail && $detail['TypeName'] === 'System', 'System is accepted by detail query');
    pass(($detail['NotificationMessage'] ?? '') === $message, 'detail returns full System Message');

    $notification = $detail;
    $unreadNotificationCount = 0;
    $_SESSION['user_id'] = $receiverId;
    ob_start();
    require __DIR__ . '/App/Views/notifications/detail.php';
    $html = (string) ob_get_clean();
    pass(str_contains($html, 'Thông báo hệ thống'), 'detail renders System title');
    pass(str_contains($html, $message), 'detail renders admin Message');
    pass(!str_contains($html, 'Cảnh báo bài viết'), 'detail does not render moderation title for System');

    pass($model->markAsRead($messageId, $receiverId), 'mark selected System notification read');
    $states = $db->prepare('SELECT NotificationID, IsRead FROM notifications WHERE NotificationID IN (:messageId, :blankId)');
    $states->execute(['messageId' => $messageId, 'blankId' => $blankId]);
    $readById = [];
    foreach ($states->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $readById[(int) $row['NotificationID']] = (int) $row['IsRead'];
    }
    pass(($readById[$messageId] ?? 0) === 1, 'clicked System row becomes read');
    pass(($readById[$blankId] ?? 1) === 0, 'other System row remains unread');

    $db->rollBack();
    echo 'ROLLBACK: System smoke data not persisted' . PHP_EOL;
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
