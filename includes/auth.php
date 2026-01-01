<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/security.php';

/**
 * Registers a new user in the system
 *
 * Validates input, checks for duplicate username/email, validates password strength,
 * hashes the password, and creates a new user account. Logs the registration activity.
 *
 * @param string $username The desired username (must be unique)
 * @param string $email The user's email address (must be unique and valid format)
 * @param string $password The user's password (must meet strength requirements)
 * @param string $fullName The user's full name
 * @param string $role The user's role: 'admin' or 'student' (default: 'student')
 * @return array Returns an array with:
 *               - 'success' (bool): Whether registration succeeded
 *               - 'message' (string): Status message
 *               - 'user_id' (int): The ID of the created user (on success)
 * @since 1.0.0
 */
function registerUser($username, $email, $password, $fullName, $role = 'student') {
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (!validatePassword($password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number, and special character'];
    }
    
    $query = "SELECT id FROM users WHERE username = ?";
    $existing = fetchOne($query, "s", [$username]);
    if ($existing) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    $query = "SELECT id FROM users WHERE email = ?";
    $existing = fetchOne($query, "s", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    $passwordHash = hashPassword($password);
    
    $userId = generateUUID();
    $query = "INSERT INTO users (id, username, email, password_hash, full_name, role, status) 
              VALUES (?, ?, ?, ?, ?, ?, 'active')";
    
    $result = executeInsert($query, "ssssss", [$userId, $username, $email, $passwordHash, $fullName, $role]);
    
    if ($userId) {
        logActivity('user_registered', 'user', $userId, "New user registered: $username");
        
        return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Registration failed. Please try again'];
}

/**
 * Authenticates a user and creates a session
 *
 * Validates credentials, checks account status, enforces login attempt limits,
 * verifies password, and creates a secure session. Updates last_login timestamp.
 *
 * @param string $username The username to authenticate
 * @param string $password The password to verify
 * @return array Returns an array with:
 *               - 'success' (bool): Whether login succeeded
 *               - 'message' (string): Status message
 *               - 'role' (string): User's role ('admin' or 'student') on success
 * @since 1.0.0
 */
function loginUser($username, $password) {
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required'];
    }
    
    $username = trim($username);
    
    if (!checkLoginAttempts($username)) {
        return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes'];
    }
    
    $query = "SELECT id, username, email, password_hash, full_name, role, status FROM users WHERE BINARY username = ?";
    $user = fetchOne($query, "s", [$username]);
    
    if (!$user) {
        incrementLoginAttempts($username);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Account is inactive or suspended'];
    }
    
    if (!verifyPassword($password, $user['password_hash'])) {
        incrementLoginAttempts($username);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    resetLoginAttempts($username);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    
    saveSessionToDatabase($user['id']);
    
    $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
    executeUpdate($query, "s", [$user['id']]);
    
    logActivity('user_login', 'user', $user['id'], "User logged in: $username");
    
    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}

/**
 * Logs out the current user and destroys the session
 *
 * Logs the logout activity, clears all session data, and destroys the session.
 *
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function logoutUser() {
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $username = $_SESSION['username'] ?? 'Unknown';
        $sessionId = session_id();
        
        deleteSessionFromDatabase($sessionId);
        
        logActivity('user_logout', 'user', $userId, "User logged out: $username");
        
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    return ['success' => false, 'message' => 'Not logged in'];
}

/**
 * Changes a user's password
 *
 * Verifies the current password, validates the new password strength,
 * hashes the new password, and updates it in the database.
 *
 * @param int $userId The ID of the user changing their password
 * @param string $currentPassword The user's current password for verification
 * @param string $newPassword The new password (must meet strength requirements)
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function changePassword($userId, $currentPassword, $newPassword) {
    if (empty($currentPassword) || empty($newPassword)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!validatePassword($newPassword)) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters with uppercase, lowercase, number, and special character'];
    }
    
    $query = "SELECT password_hash FROM users WHERE id = ?";
    $user = fetchOne($query, "s", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if (!verifyPassword($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $newPasswordHash = hashPassword($newPassword);
    
    $query = "UPDATE users SET password_hash = ? WHERE id = ?";
    $result = executeUpdate($query, "ss", [$newPasswordHash, $userId]);
    
    if ($result) {
        logActivity('password_changed', 'user', $userId, "Password changed");
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to change password'];
}

/**
 * Retrieves a user by their ID
 *
 * Fetches user information excluding sensitive data like password hash.
 *
 * @param int $userId The ID of the user to retrieve
 * @return array|null Returns an associative array with user data, or null if not found
 * @since 1.0.0
 */
function getUserById($userId) {
    $query = "SELECT id, username, email, full_name, role, status, created_at, last_login 
              FROM users WHERE id = ?";
    return fetchOne($query, "s", [$userId]);
}

/**
 * Retrieves all users with optional filtering and pagination
 *
 * Fetches a paginated list of users, optionally filtered by role.
 * Excludes sensitive password information.
 *
 * @param string|null $role Optional role filter ('admin' or 'student')
 * @param int $page The page number for pagination (default: 1)
 * @param int $limit The number of items per page (default: ITEMS_PER_PAGE)
 * @return array Returns an array of user records
 * @since 1.0.0
 */
function getAllUsers($role = null, $page = 1, $limit = ITEMS_PER_PAGE) {
    $offset = ($page - 1) * $limit;
    
    if ($role) {
        $query = "SELECT id, username, email, full_name, role, status, created_at, last_login 
                  FROM users WHERE role = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return fetchAll($query, "sii", [$role, $limit, $offset]);
    } else {
        $query = "SELECT id, username, email, full_name, role, status, created_at, last_login 
                  FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return fetchAll($query, "ii", [$limit, $offset]);
    }
}

/**
 * Counts the total number of users, optionally filtered by role
 *
 * @param string|null $role Optional role filter to count only users with that role
 * @return int The total count of users matching the criteria
 * @since 1.0.0
 */
function countUsers($role = null) {
    if ($role) {
        $query = "SELECT COUNT(*) as total FROM users WHERE role = ?";
        $result = fetchOne($query, "s", [$role]);
    } else {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = fetchOne($query);
    }
    
    return $result['total'] ?? 0;
}

/**
 * Updates a user's account status
 *
 * Changes user status to 'active', 'inactive', or 'suspended'.
 * Validates the status value before updating.
 *
 * @param int $userId The ID of the user to update
 * @param string $status The new status: 'active', 'inactive', or 'suspended'
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function updateUserStatus($userId, $status) {
    $validStatuses = ['active', 'inactive', 'suspended'];
    
    if (!in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    
    $query = "UPDATE users SET status = ? WHERE id = ?";
    $result = executeUpdate($query, "ss", [$status, $userId]);
    
    if ($result) {
        logActivity('user_status_updated', 'user', $userId, "Status changed to: $status");
        return ['success' => true, 'message' => 'User status updated'];
    }
    
    return ['success' => false, 'message' => 'Failed to update user status'];
}

/**
 * Deletes a user from the system
 *
 * Permanently deletes a user account. Prevents users from deleting their own account.
 * Associated data is deleted via CASCADE constraints.
 *
 * @param int $userId The ID of the user to delete
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function deleteUser($userId) {
    if ($userId == getCurrentUserId()) {
        return ['success' => false, 'message' => 'Cannot delete your own account'];
    }
    
    $query = "DELETE FROM users WHERE id = ?";
    $result = executeUpdate($query, "s", [$userId]);
    
    if ($result) {
        logActivity('user_deleted', 'user', $userId, "User deleted");
        return ['success' => true, 'message' => 'User deleted successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to delete user'];
}

/**
 * Saves a user session to the database
 *
 * Stores session information in the sessions table for tracking and security purposes.
 * Includes session ID, user ID, session data, IP address, user agent, and expiration time.
 *
 * @param int $userId The ID of the user
 * @return bool Returns true on success, false on failure
 * @since 1.0.0
 */
function saveSessionToDatabase($userId) {
    $sessionId = session_id();
    if (empty($sessionId)) {
        return false;
    }
    
    $sessionData = json_encode($_SESSION);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    $deleteQuery = "DELETE FROM sessions WHERE user_id = ?";
    executeUpdate($deleteQuery, "s", [$userId]);
    
    $query = "INSERT INTO sessions (id, user_id, session_data, ip_address, user_agent, expires_at) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    return executeInsert($query, "ssssss", [
        $sessionId,
        $userId,
        $sessionData,
        $ipAddress,
        $userAgent,
        $expiresAt
    ]) !== false;
}

/**
 * Deletes a session from the database
 *
 * Removes a session record when user logs out or session is destroyed.
 *
 * @param string $sessionId The session ID to delete
 * @return bool Returns true on success, false on failure
 * @since 1.0.0
 */
function deleteSessionFromDatabase($sessionId) {
    if (empty($sessionId)) {
        return false;
    }
    
    $query = "DELETE FROM sessions WHERE id = ?";
    return executeUpdate($query, "s", [$sessionId]) !== false;
}

/**
 * Cleans up expired sessions from the database
 *
 * Removes all sessions that have passed their expiration time.
 * Should be called periodically (e.g., via cron job) or on login/logout.
 *
 * @return int Returns the number of deleted sessions
 * @since 1.0.0
 */
function cleanupExpiredSessions() {
    $query = "DELETE FROM sessions WHERE expires_at < NOW()";
    $conn = getDBConnection();
    $conn->query($query);
    return $conn->affected_rows;
}
?>


