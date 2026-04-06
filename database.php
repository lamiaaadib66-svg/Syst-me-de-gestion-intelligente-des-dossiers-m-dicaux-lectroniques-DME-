<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Erreur de connexion à la base de données: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            handle_error($e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Méthode d'exécution de requête sécurisée
    public function query($sql, $params = [], $types = "") {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur d'exécution: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } else {
            $affectedRows = $stmt->affected_rows;
            $lastId = $stmt->insert_id;
            $stmt->close();
            return [
                'affected_rows' => $affectedRows,
                'insert_id' => $lastId
            ];
        }
    }
    
    // Méthode pour obtenir une seule ligne
    public function queryOne($sql, $params = [], $types = "") {
        $result = $this->query($sql, $params, $types);
        return $result ? $result[0] : null;
    }
    
    // Échapper les chaînes
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Démarrer une transaction
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    // Valider une transaction
    public function commit() {
        $this->connection->commit();
    }
    
    // Annuler une transaction
    public function rollback() {
        $this->connection->rollback();
    }
}
?>