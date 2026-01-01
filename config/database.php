<?php
require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_NAME', env('DB_NAME'));
define('DB_CHARSET', env('DB_CHARSET'));
define('DB_PORT', (int)env('DB_PORT'));

/**
 * Handles database errors gracefully by redirecting to login or returning JSON error
 *
 * @param Exception $e The exception that occurred
 * @return void Exits script execution after handling error
 * @since 1.0.0
 */
function handleDatabaseError($e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please refresh the page and try again.'
        ]);
        exit();
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['error'] = 'A database error occurred. Please log in again.';
    session_write_close();
    
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/login.php';
    header('Location: ' . $loginUrl);
    exit();
}

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                error_log("Attempted: " . DB_HOST . ":" . DB_PORT . " with user " . DB_USER);
                throw new Exception("Database connection failed. Please try again later.");
            }
            
            if (!$conn->set_charset(DB_CHARSET)) {
                error_log("Error setting charset: " . $conn->error);
            }
            
            $conn->query("SET time_zone = '+00:00'");
            
        } catch (Exception $e) {
            handleDatabaseError($e);
        }
    }
    
    return $conn;
}

function closeDBConnection() {
    $conn = getDBConnection();
    if ($conn) {
        $conn->close();
    }
}

function executeQuery($query, $types = "", $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($query);
        
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        return $stmt;
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}

function fetchOne($query, $types = "", $params = []) {
    try {
        $stmt = executeQuery($query, $types, $params);
        if (!$stmt) return null;
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}

function fetchAll($query, $types = "", $params = []) {
    try {
        $stmt = executeQuery($query, $types, $params);
        if (!$stmt) return [];
        
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        
        return $rows;
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}

function executeUpdate($query, $types = "", $params = []) {
    try {
        $stmt = executeQuery($query, $types, $params);
        if (!$stmt) return false;
        
        $affected_rows = $stmt->affected_rows;
        $insert_id = $stmt->insert_id;
        $stmt->close();
        
        return $insert_id > 0 ? $insert_id : $affected_rows;
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}

function escapeString($string) {
    try {
        $conn = getDBConnection();
        return $conn->real_escape_string($string);
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}

/**
 * Generates a UUID v4 (random UUID)
 * 
 * @return string UUID in format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
 * @since 1.0.0
 */
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return sprintf(
        '%08s-%04s-%04s-%04s-%12s',
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6))
    );
}

/**
 * Executes an INSERT query and returns the generated UUID
 * For UUID-based tables, the UUID must be provided in the params
 * 
 * @param string $query SQL query with id placeholder
 * @param string $types Parameter types (must include 's' for UUID)
 * @param array $params Parameters array (UUID should be first if it's the id)
 * @return string|false Returns the UUID on success, false on failure
 * @since 1.0.0
 */
function executeInsert($query, $types = "", $params = []) {
    try {
        $stmt = executeQuery($query, $types, $params);
        if (!$stmt) return false;
        
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows > 0 && !empty($params) && is_string($params[0]) && strlen($params[0]) === 36) {
            return $params[0];
        }
        
        return $affected_rows > 0;
    } catch (Exception $e) {
        handleDatabaseError($e);
    }
}
