<?php
if (!defined('BASE_URL')) {
    define("BASE_URL", "http://localhost:3000/");
}

class Database {
    private $host = "100.76.147.122";
    private $db_name = "db_archive";
    private $username = "root";
    private $password = "";
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            // Có thể override bằng biến môi trường DB_HOST, DB_NAME, DB_USER, DB_PASS nếu đổi tài khoản MySQL.
            $host = getenv('DB_HOST') ?: $this->host;
            $dbName = getenv('DB_NAME') ?: $this->db_name;
            $username = getenv('DB_USER') ?: $this->username;
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $this->password;

            $this->conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . $dbName . ";charset=utf8mb4",
                $username,
                $password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
