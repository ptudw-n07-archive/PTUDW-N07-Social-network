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

    // Đăng ký người dùng mới (Mặc định RoleID = 2 cho người dùng thường)
 
    public function register($name, $username, $email, $password) {
        $query = "INSERT INTO " . $this->table . " (FullName, Username, Email, PasswordHash, RoleID, CreatedAt) 
                  VALUES (:name, :username, :email, :password, 2, NOW())";
        $stmt = $this->conn->prepare($query);
        
        // Hash mật khẩu bảo mật bằng BCRYPT
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        return $stmt->execute();
    }

    // Tìm người dùng bằng Username hoặc Email để Đăng nhập / Quên mật khẩu
    public function findByCredentials($login_input) {
        // JOIN với bảng Roles để lấy thông tin phân quyền luôn một thể
        $query = "SELECT u.*, r.RoleName FROM " . $this->table . " u 
                  JOIN Roles r ON u.RoleID = r.RoleID 
                  WHERE u.Username = :input OR u.Email = :input LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':input', $login_input);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật lại mật khẩu mới (Chức năng Quên mật khẩu đơn giản)
    public function updatePassword($email, $new_password) {
        $query = "UPDATE " . $this->table . " SET Password = :password WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }
}
?>