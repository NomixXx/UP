<?php
// Конфигурация базы данных для UpTaxi Portal

class DatabaseConfig {
    // Настройки подключения к базе данных
    private static $host = 'localhost';
    private static $dbname = 'uptaxi_portal';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    
    private static $pdo = null;
    
    /**
     * Получение подключения к базе данных
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                self::$pdo = new PDO($dsn, self::$username, self::$password, $options);
                
            } catch (PDOException $e) {
                // Логирование ошибки
                error_log("Database connection error: " . $e->getMessage());
                throw new Exception("Ошибка подключения к базе данных");
            }
        }
        
        return self::$pdo;
    }
    
    /**
     * Проверка подключения к базе данных
     */
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Настройка конфигурации базы данных
     */
    public static function setConfig($host, $dbname, $username, $password) {
        self::$host = $host;
        self::$dbname = $dbname;
        self::$username = $username;
        self::$password = $password;
        self::$pdo = null; // Сброс подключения
    }
    
    /**
     * Получение текущей конфигурации
     */
    public static function getConfig() {
        return [
            'host' => self::$host,
            'dbname' => self::$dbname,
            'username' => self::$username,
            'password' => '***' // Скрываем пароль
        ];
    }
}

/**
 * Класс для работы с базой данных
 */
class DatabaseManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
    }
    
    /**
     * Выполнение запроса SELECT
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database select error: " . $e->getMessage());
            throw new Exception("Ошибка выполнения запроса");
        }
    }
    
    /**
     * Выполнение запроса INSERT
     */
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            throw new Exception("Ошибка добавления записи");
        }
    }
    
    /**
     * Выполнение запроса UPDATE
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $setClause = [];
            foreach (array_keys($data) as $key) {
                $setClause[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setClause);
            
            $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->pdo->prepare($query);
            
            $params = array_merge($data, $whereParams);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            throw new Exception("Ошибка обновления записи");
        }
    }
    
    /**
     * Выполнение запроса DELETE
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage());
            throw new Exception("Ошибка удаления записи");
        }
    }
    
    /**
     * Получение одной записи
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database selectOne error: " . $e->getMessage());
            throw new Exception("Ошибка выполнения запроса");
        }
    }
    
    /**
     * Начало транзакции
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Подтверждение транзакции
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Откат транзакции
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Логирование активности пользователя
     */
    public function logActivity($userId, $action, $entityType = null, $entityId = null, $details = null) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $this->insert('activity_logs', $data);
    }
}
?>