<?php
/**
 * Starts a secure session with timeout and regeneration
 *
 * Initializes PHP session with security features:
 * - Session ID regeneration every 30 minutes
 * - Automatic session expiration after SESSION_LIFETIME
 * - Activity tracking for timeout management
 *
 * @return bool Returns true if session is valid, false if expired
 * @since 1.0.0
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            $oldSessionId = session_id();
            session_regenerate_id(true);
            $newSessionId = session_id();
            
            if (isset($_SESSION['user_id']) && function_exists('getUserById')) {
                $userId = $_SESSION['user_id'];
                $user = getUserById($userId);
                if ($user && $user['status'] === 'active') {
                    $query = "UPDATE sessions SET id = ? WHERE id = ?";
                    executeUpdate($query, "ss", [$newSessionId, $oldSessionId]);
                } else {
                    if (function_exists('deleteSessionFromDatabase')) {
                        deleteSessionFromDatabase($oldSessionId);
                    }
                    session_unset();
                    session_destroy();
                    return false;
                }
            }
            
            $_SESSION['created'] = time();
        }
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            if (isset($_SESSION['user_id']) && function_exists('deleteSessionFromDatabase')) {
                deleteSessionFromDatabase(session_id());
            }
            
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
        
        if (isset($_SESSION['user_id']) && function_exists('saveSessionToDatabase') && function_exists('getUserById')) {
            $userId = $_SESSION['user_id'];
            $user = getUserById($userId);
            if ($user && $user['status'] === 'active') {
                saveSessionToDatabase($userId);
            } else {
                if (function_exists('deleteSessionFromDatabase')) {
                    deleteSessionFromDatabase(session_id());
                }
                session_unset();
                session_destroy();
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Generates or retrieves a CSRF protection token
 *
 * Creates a cryptographically secure random token for CSRF protection.
 * Token is stored in session and reused until session expires.
 *
 * @return string Returns a 64-character hexadecimal CSRF token
 * @since 1.0.0
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a CSRF token against the session token
 *
 * Uses timing-safe comparison (hash_equals) to prevent timing attacks.
 *
 * @param string $token The token to verify
 * @return bool Returns true if token is valid, false otherwise
 * @since 1.0.0
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizes user input to prevent XSS attacks
 *
 * Recursively sanitizes strings and arrays by:
 * - Trimming whitespace
 * - Removing slashes
 * - Converting special characters to HTML entities
 *
 * @param string|array $data The data to sanitize
 * @return string|array Returns sanitized data with the same structure
 * @since 1.0.0
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validates an email address format
 *
 * Uses PHP's filter_var with FILTER_VALIDATE_EMAIL for validation.
 *
 * @param string $email The email address to validate
 * @return bool Returns true if email format is valid, false otherwise
 * @since 1.0.0
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validates password strength requirements
 *
 * Ensures password meets security requirements:
 * - Minimum 8 characters
 * - At least one lowercase letter
 * - At least one uppercase letter
 * - At least one digit
 * - At least one special character (@$!%*?&)
 *
 * @param string $password The password to validate
 * @return bool Returns true if password meets requirements, false otherwise
 * @since 1.0.0
 */
function validatePassword($password) {
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}

/**
 * Hashes a password using bcrypt algorithm
 *
 * Uses PHP's password_hash with bcrypt and cost factor of 12.
 *
 * @param string $password The plain text password to hash
 * @return string Returns the hashed password (60 characters)
 * @since 1.0.0
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifies a password against a hash
 *
 * Uses PHP's password_verify for secure password comparison.
 *
 * @param string $password The plain text password to verify
 * @param string $hash The hashed password to compare against
 * @return bool Returns true if password matches hash, false otherwise
 * @since 1.0.0
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Checks if a user is currently logged in
 *
 * Verifies that both user_id and user_role are set in the session.
 *
 * @return bool Returns true if user is logged in, false otherwise
 * @since 1.0.0
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Checks if the current user is an admin
 *
 * @return bool Returns true if user is logged in and has admin role, false otherwise
 * @since 1.0.0
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Checks if the current user is a student
 *
 * @return bool Returns true if user is logged in and has student role, false otherwise
 * @since 1.0.0
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['user_role'] === 'student';
}

/**
 * Requires user to be logged in, redirects if not
 *
 * Checks if user is logged in. If not, redirects to login page and exits.
 * Used as middleware for protected pages.
 *
 * @return void Exits script execution if user is not logged in
 * @since 1.0.0
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Requires user to be logged in as admin, redirects if not
 *
 * First checks if user is logged in, then verifies admin role.
 * Redirects to home page if not admin. Used as middleware for admin pages.
 *
 * @return void Exits script execution if user is not an admin
 * @since 1.0.0
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Requires user to be logged in as student, redirects if not
 *
 * First checks if user is logged in, then verifies student role.
 * Redirects to home page if not student. Used as middleware for student pages.
 *
 * @return void Exits script execution if user is not a student
 * @since 1.0.0
 */
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Gets the ID of the currently logged-in user
 *
 * @return int|null Returns the user ID from session, or null if not logged in
 * @since 1.0.0
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Gets the role of the currently logged-in user
 *
 * @return string|null Returns 'admin' or 'student' from session, or null if not logged in
 * @since 1.0.0
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Gets complete user information for the currently logged-in user
 *
 * Fetches user data from database, excluding password hash.
 * Only returns data if user is logged in and account is active.
 *
 * @return array|null Returns user data array or null if not logged in or inactive
 * @since 1.0.0
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = getCurrentUserId();
    $query = "SELECT id, username, email, full_name, role, status, created_at, last_login FROM users WHERE id = ? AND status = 'active'";
    return fetchOne($query, "s", [$userId]);
}

/**
 * Logs an activity to the audit log
 *
 * Records user actions for security and auditing purposes.
 * Captures user ID, action type, entity information, details, and IP address.
 *
 * @param string $action The action being logged (e.g., 'user_login', 'exam_created')
 * @param string|null $entity_type The type of entity involved (e.g., 'user', 'exam', 'question')
 * @param int|null $entity_id The ID of the entity involved
 * @param string|null $details Additional details about the action
 * @return void
 * @since 1.0.0
 */
function logActivity($action, $entity_type = null, $entity_id = null, $details = null) {
    $userId = getCurrentUserId();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $logId = generateUUID();
    $query = "INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, details, ip_address) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    executeInsert($query, "sssssss", [
        $logId,
        $userId,
        $action,
        $entity_type,
        $entity_id,
        $details,
        $ipAddress
    ]);
}

/**
 * Checks if login attempts for a username are within allowed limits
 *
 * Implements rate limiting to prevent brute force attacks.
 * Tracks attempts in session with timeout period (LOGIN_TIMEOUT).
 *
 * @param string $username The username to check attempts for
 * @return bool Returns true if login is allowed, false if too many attempts
 * @since 1.0.0
 */
function checkLoginAttempts($username) {
    $cacheKey = 'login_attempts_' . $username;
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    
    $attempts = $_SESSION[$cacheKey];
    
    if (time() - $attempts['first_attempt'] > LOGIN_TIMEOUT) {
        $_SESSION[$cacheKey] = [
            'count' => 0,
            'first_attempt' => time()
        ];
        return true;
    }
    
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        return false;
    }
    
    return true;
}

/**
 * Increments the failed login attempt counter for a username
 *
 * @param string $username The username that failed to login
 * @return void
 * @since 1.0.0
 */
function incrementLoginAttempts($username) {
    $cacheKey = 'login_attempts_' . $username;
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    
    $_SESSION[$cacheKey]['count']++;
}

/**
 * Resets the login attempt counter for a username
 *
 * Called after successful login to clear failed attempt tracking.
 *
 * @param string $username The username to reset attempts for
 * @return void
 * @since 1.0.0
 */
function resetLoginAttempts($username) {
    $cacheKey = 'login_attempts_' . $username;
    unset($_SESSION[$cacheKey]);
}

/**
 * Prevents SQL injection by escaping string input
 *
 * @param string $input The input string to escape
 * @return string Returns the escaped string
 * @deprecated Use prepared statements instead. This function is kept for backward compatibility.
 * @since 1.0.0
 */
function preventSQLInjection($input) {
    return escapeString($input);
}

/**
 * Generates a cryptographically secure random token
 *
 * @param int $length The length of the token in bytes (default: 32)
 * @return string Returns a hexadecimal string token (length * 2 characters)
 * @since 1.0.0
 */
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validates if a value is an integer
 *
 * @param mixed $value The value to validate
 * @return bool Returns true if value is a valid integer, false otherwise
 * @since 1.0.0
 */
function validateInteger($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validates if a value is a positive integer (greater than 0)
 *
 * @param mixed $value The value to validate
 * @return bool Returns true if value is a positive integer, false otherwise
 * @since 1.0.0
 */
function validatePositiveInteger($value) {
    return validateInteger($value) && $value > 0;
}

/**
 * Sanitizes a filename by removing dangerous characters
 *
 * Replaces any character that is not alphanumeric, period, underscore, or dash
 * with an underscore to prevent directory traversal and other file system attacks.
 *
 * @param string $filename The filename to sanitize
 * @return string Returns a sanitized filename safe for file system operations
 * @since 1.0.0
 */
function cleanFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

/**
 * Checks if the current request is an AJAX request
 *
 * Verifies the X-Requested-With header to determine if request is AJAX.
 *
 * @return bool Returns true if request is AJAX, false otherwise
 * @since 1.0.0
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Sends a JSON response and exits
 *
 * Sets appropriate HTTP status code and Content-Type header,
 * encodes data as JSON, and terminates script execution.
 *
 * @param array $data The data to encode as JSON
 * @param int $statusCode The HTTP status code (default: 200)
 * @return void Exits script execution after sending response
 * @since 1.0.0
 */
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Handles errors with appropriate response format
 *
 * Sends JSON response for AJAX requests, or sets session flash message
 * and redirects for regular requests.
 *
 * @param string $message The error message to display
 * @param int $code The HTTP status code (default: 400)
 * @return void Exits script execution after handling error
 * @since 1.0.0
 */
function handleError($message, $code = 400) {
    if (isAjaxRequest()) {
        sendJSONResponse(['success' => false, 'message' => $message], $code);
    } else {
        $_SESSION['error'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL);
        exit();
    }
}

/**
 * Handles success responses with appropriate format
 *
 * Sends JSON response for AJAX requests, or sets session flash message
 * and redirects for regular requests.
 *
 * @param string $message The success message to display
 * @param string|null $redirect Optional redirect URL (defaults to referrer or BASE_URL)
 * @return void Exits script execution after handling success
 * @since 1.0.0
 */
function handleSuccess($message, $redirect = null) {
    if (isAjaxRequest()) {
        sendJSONResponse(['success' => true, 'message' => $message]);
    } else {
        $_SESSION['success'] = $message;
        header('Location: ' . ($redirect ?? $_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit();
    }
}

/**
 * Retrieves and clears a flash message from session
 *
 * Flash messages are one-time messages that are displayed once and then removed.
 * Used for success/error notifications after redirects.
 *
 * @param string $type The type of flash message: 'success' or 'error' (default: 'success')
 * @return string|null Returns the flash message or null if none exists
 * @since 1.0.0
 */
function getFlashMessage($type = 'success') {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}
?>


