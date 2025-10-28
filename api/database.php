<?php
class Database {
    private $host = "localhost";
    private $db_name = "umak_ecp";
    private $username = "root";
    private $password = "";
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            return $this->conn;
            
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            
            return null;
        }
    }
    
    public function testConnection() {
        $conn = $this->getConnection();
        return $conn !== null;
    }
    
    public function getDatabaseInfo() {
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                return null;
            }
            
            $info = [
                'host' => $this->host,
                'database' => $this->db_name,
                'connected' => true,
                'server_version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                'client_version' => $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)
            ];
            
            return $info;
            
        } catch (Exception $e) {
            error_log("Get Database Info Error: " . $e->getMessage());
            return null;
        }
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}
?>