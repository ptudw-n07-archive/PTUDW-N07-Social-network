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
    
    // Cập nhật lại mật khẩu mới khi Quên mật khẩu - ĐÃ UPDATE PASSWORD_HASH
    public function updatePassword($email, $new_password) {
        $query = "UPDATE " . $this->table . " SET PasswordHash = :password WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        
        // Băm mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    // Xác thực đăng nhập hỗ trợ cả tài khoản thô (cũ) và tài khoản băm (mới)
    public function login($username, $password) {
        // Tìm user dựa trên cả Username hoặc Email
        $user = $this->findByCredentials($username);

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
        }
        
        return false; 
    }
}
?>