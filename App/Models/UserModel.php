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
        
        // TẠM THỜI: Lưu mật khẩu thô trực tiếp giống như database cũ của bạn
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        return $stmt->execute();
    }

    // Tìm người dùng bằng Username hoặc Email để Đăng nhập / Quên mật khẩu
    public function findByCredentials($login_input) {
        // Đổi sang LEFT JOIN để tránh bị mất bản ghi nếu dữ liệu RoleID bị lệch
        $query = "SELECT u.*, r.RoleName FROM " . $this->table . " u 
                  LEFT JOIN Roles r ON u.RoleID = r.RoleID 
                  WHERE u.Username = :input OR u.Email = :input LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':input', $login_input);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật lại mật khẩu mới khi Quên mật khẩu
    public function updatePassword($email, $new_password) {
        $query = "UPDATE " . $this->table . " SET PasswordHash = :password WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        
        // SỬA TẠI ĐÂY: Lưu trực tiếp chuỗi mật khẩu thô, bỏ qua hàm password_hash
        $stmt->bindParam(':password', $new_password);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    /**
     * Xác thực đăng nhập bằng mật khẩu thô trực tiếp
     */
    public function login($username, $password) {
        // Đổi sang LEFT JOIN để an toàn tuyệt đối cho dữ liệu
        $query = "SELECT u.*, r.RoleName FROM " . $this->table . " u 
                  LEFT JOIN Roles r ON u.RoleID = r.RoleID 
                  WHERE u.Username = :input OR u.Email = :input LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':input', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Đối chiếu chuỗi mật khẩu thô trực tiếp
        if ($user && $password === ($user['PasswordHash'] ?? $user['Password'] ?? '')) {
            return $user; 
        }
        
        return false; 
    }
}
?>