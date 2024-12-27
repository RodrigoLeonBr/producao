<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'producao';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch(PDOException $e) {
            die('Erro na conexão com o banco de dados: ' . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
