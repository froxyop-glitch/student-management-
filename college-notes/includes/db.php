<?php
/**
 * College Notes Management System
 * Database Connection Wrapper (PDO Singleton)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Get single PDO connection instance
     * 
     * @return PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log detailed error for admin/sysops
                error_log("Database Connection Error: " . $e->getMessage());
                
                // Show clean, secure user-friendly error without leaking credentials
                http_response_code(500);
                include_once __DIR__ . '/../404.php';
                exit;
            }
        }

        return self::$instance;
    }

    /**
     * Prevent cloning of instance
     */
    private function __clone() {}

    /**
     * Prevent unserialize of instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton instance.");
    }
}

/**
 * Global helper function to get DB instance quickly
 * 
 * @return PDO
 */
function getDB(): PDO {
    return Database::getInstance();
}
