<?php
namespace App\Models; 

use PDO; 
class UserModel {
    private $conn;
    private $table = "Users";

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // Kiểm tra xem Username hoặc Email đã tồn tại chưa
    public function exists($username, $email) {
        $query = "SELECT UserID FROM " . $this->table . " WHERE Username = :username OR Email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Đăng ký người dùng mới - ĐÃ BẢO MẬT BẰNG PASSWORD_HASH
    public function register($name, $username, $email, $password) {
        $query = "INSERT INTO " . $this->table . " (FullName, Username, Email, PasswordHash, RoleID, CreatedAt) 
                  VALUES (:name, :username, :email, :password, 2, NOW())";
        $stmt = $this->conn->prepare($query);
        

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        return $stmt->execute();
    }

    // Tìm người dùng bằng Username hoặc Email
    public function findByCredentials($login_input) {
       
        $query = "SELECT u.*, r.RoleName FROM " . $this->table . " u 
                  LEFT JOIN Roles r ON u.RoleID = r.RoleID 
                  WHERE u.Username = :input OR u.Email = :input LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':input', $login_input);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
<<<<<<< HEAD
    
    // Cập nhật lại mật khẩu mới khi Quên mật khẩu - ĐÃ UPDATE PASSWORD_HASH
=======

    public function findById($userId) {
        $query = "SELECT u.*, r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN Roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :userId
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserProfileById($userId) {
        $query = "SELECT
                    u.UserID,
                    u.RoleID,
                    u.Username,
                    u.Email,
                    u.FullName,
                    u.Bio,
                    u.ProfilePictureUrl,
                    u.CreatedAt,
                    r.RoleName
                  FROM " . $this->table . " u
                  LEFT JOIN Roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :userId
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isUsernameTaken($username, $excludeUserId) {
        $query = "SELECT UserID
                  FROM " . $this->table . "
                  WHERE Username = :username AND UserID != :userId
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':userId', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function isEmailTaken($email, $excludeUserId) {
        $query = "SELECT UserID
                  FROM " . $this->table . "
                  WHERE Email = :email AND UserID != :userId
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':userId', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function updateProfile($userId, $fullName, $username, $email, $bio, $avatarPath = null) {
        $fields = [
            "FullName = :fullName",
            "Username = :username",
            "Email = :email",
            "Bio = :bio"
        ];

        if ($avatarPath !== null) {
            $fields[] = "ProfilePictureUrl = :avatarPath";
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE UserID = :userId";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        if ($avatarPath !== null) {
            $stmt->bindParam(':avatarPath', $avatarPath);
        }

        return $stmt->execute();
    }

    public function countFollowing($userId) {
        $query = "SELECT COUNT(*) FROM follows WHERE FollowerID = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countFollowers($userId) {
        $query = "SELECT COUNT(*) FROM follows WHERE FollowedID = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    // Cập nhật lại mật khẩu mới khi Quên mật khẩu
>>>>>>> c6609eafb5f6c2feebe63fdd7af78326c540b9a8
    public function updatePassword($email, $new_password) {
        $query = "UPDATE " . $this->table . " SET PasswordHash = :password WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        
        // Băm mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

<<<<<<< HEAD
    // Xác thực đăng nhập hỗ trợ cả tài khoản thô (cũ) và tài khoản băm (mới)
=======
    /**
     * Xác thực đăng nhập bằng password_hash; giữ fallback plain text cho dữ liệu cũ.
     */
>>>>>>> c6609eafb5f6c2feebe63fdd7af78326c540b9a8
    public function login($username, $password) {
        // Tìm user dựa trên cả Username hoặc Email
        $user = $this->findByCredentials($username);

<<<<<<< HEAD
        if ($user) {
            $db_password = $user['PasswordHash'] ?? $user['Password'] ?? '';

            // Trường hợp 1: Mật khẩu trong DB đã được băm bằng password_hash (Khuyên dùng)
            if (password_verify($password, $db_password)) {
                return $user;
            }

            // Trường hợp 2: Hỗ trợ các tài khoản cũ lưu mật khẩu thô chưa kịp đổi
            if ($password === $db_password) {
                return $user;
            }
=======
        if (!$user) {
            return false;
        }

        $storedPassword = $user['PasswordHash'] ?? $user['Password'] ?? '';

        if (!empty($storedPassword) && password_verify($password, $storedPassword)) {
            return $user; 
>>>>>>> c6609eafb5f6c2feebe63fdd7af78326c540b9a8
        }

        // Fallback tạm thời cho dữ liệu cũ nếu còn tài khoản lưu plain text.
        if (!empty($storedPassword) && hash_equals($storedPassword, $password)) {
            return $user;
        }
        
        return false; 
    }
}
?>