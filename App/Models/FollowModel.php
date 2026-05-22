<?php
namespace App\Models; 
use PDO; 
class FollowModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getSuggestedUsers($currentUserId) {
    $sql = "
        SELECT 
            u.UserID,
            u.Username,
            u.FullName,
            u.ProfilePictureUrl,

            CASE 
                WHEN f.FollowerID IS NOT NULL THEN 1
                ELSE 0
            END AS IsFollowing

        FROM users u

        LEFT JOIN follows f 
            ON f.FollowedID = u.UserID 
            AND f.FollowerID = :currentUserId

        WHERE u.UserID != :currentUserId

        ORDER BY u.UserID DESC

        LIMIT 5
    ";

    $stmt = $this->conn->prepare($sql);

    $stmt->bindParam(":currentUserId", $currentUserId, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function toggleFollow($followerId, $followedId) {

    $checkSql = "
        SELECT * FROM follows
        WHERE FollowerID = :followerId
        AND FollowedID = :followedId
    ";

    $checkStmt = $this->conn->prepare($checkSql);

    $checkStmt->bindParam(":followerId", $followerId, PDO::PARAM_INT);

    $checkStmt->bindParam(":followedId", $followedId, PDO::PARAM_INT);

    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {

        $deleteSql = "
            DELETE FROM follows
            WHERE FollowerID = :followerId
            AND FollowedID = :followedId
        ";

        $deleteStmt = $this->conn->prepare($deleteSql);

        $deleteStmt->bindParam(":followerId", $followerId, PDO::PARAM_INT);

        $deleteStmt->bindParam(":followedId", $followedId, PDO::PARAM_INT);

        $deleteStmt->execute();

        return "unfollowed";

    } else {

        $insertSql = "
            INSERT INTO follows(FollowerID, FollowedID, CreatedAt)
            VALUES(:followerId, :followedId, NOW())
        ";

        $insertStmt = $this->conn->prepare($insertSql);

        $insertStmt->bindParam(":followerId", $followerId, PDO::PARAM_INT);

        $insertStmt->bindParam(":followedId", $followedId, PDO::PARAM_INT);

        $insertStmt->execute();

        return "followed";
    }
}
}
?>