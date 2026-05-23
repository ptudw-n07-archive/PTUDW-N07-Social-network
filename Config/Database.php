<?php
require_once __DIR__ . '/bootstrap.php';

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = env_value(['DB_HOST', 'MYSQLHOST'], '100.76.147.122');
        $this->port = env_value(['DB_PORT', 'MYSQLPORT'], '3306');
        $this->db_name = env_value(['DB_DATABASE', 'MYSQLDATABASE'], 'db_archive');
        $this->username = env_value(['DB_USERNAME', 'MYSQLUSER'], 'root');
        $this->password = env_value(['DB_PASSWORD', 'MYSQLPASSWORD'], '');
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
