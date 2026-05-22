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
}
?>