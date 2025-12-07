<?php
// includes/database.php

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Erreur de connexion à la base de données: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset(DB_CHARSET);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
    
    public function get_row($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetch_assoc() : false;
    }
    
    public function get_all($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = '`' . implode('`, `', $keys) . '`';
        $placeholders = str_repeat('?, ', count($keys) - 1) . '?';
        
        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
        $params = array_values($data);
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        
        return $stmt->insert_id;
    }
    
    public function update($table, $data, $where, $where_params = []) {
        $set_parts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $set_parts[] = "`{$key}` = ?";
            $params[] = $value;
        }
        
        $set_clause = implode(', ', $set_parts);
        $params = array_merge($params, $where_params);
        
        $sql = "UPDATE `{$table}` SET {$set_clause} WHERE {$where}";
        $stmt = $this->connection->prepare($sql);
        
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
}

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}
?>