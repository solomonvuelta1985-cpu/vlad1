<?php
/**
 * Authentication System
 * Handles user login, logout, and access control
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Check if user is logged in
 * Redirects to login page if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('Please log in to access this page', 'warning');
        header('Location: /vlad/public/login.php');
        exit;
    }
}

/**
 * Check if user is currently logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is an admin
 * @return bool
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require admin privileges
 * Redirects if user is not an admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('Access denied. Administrator privileges required.', 'danger');
        header('Location: /vlad/public/index.php');
        exit;
    }
}

/**
 * Authenticate user with username and password
 * @param string $username
 * @param string $password
 * @return bool|array Returns user data on success, false on failure
 */
function authenticate($username, $password) {
    try {
        $stmt = db_query(
            "SELECT user_id, username, password_hash, full_name, email, role, status
             FROM users WHERE username = ? AND status = 'active' LIMIT 1",
            [$username]
        );
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            db_query(
                "UPDATE users SET last_login = NOW() WHERE user_id = ?",
                [$user['user_id']]
            );
            return $user;
        }
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
    }
    return false;
}

/**
 * Create user session after successful login
 * @param array $user User data from database
 */
function create_session($user) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Generate new CSRF token for this session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Destroy user session (logout)
 */
function destroy_session() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Check session timeout (30 minutes of inactivity)
 * @param int $timeout Timeout in seconds (default 1800 = 30 minutes)
 */
function check_session_timeout($timeout = 1800) {
    if (is_logged_in()) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            destroy_session();
            set_flash('Your session has expired. Please log in again.', 'warning');
            header('Location: /vlad/public/login.php');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Get current user info
 * @param string $key Optional specific key to retrieve
 * @return mixed User data or specific value
 */
if (!function_exists('get_current_user')) {
    function get_current_user($key = null) {
        if (!is_logged_in()) {
            return null;
        }

        if ($key) {
            return $_SESSION[$key] ?? null;
        }

        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['user_role']
        ];
    }
}

/**
 * Create a new user account
 * @param array $userData User information
 * @return int|false User ID on success, false on failure
 */
function create_user($userData) {
    try {
        // Check if username already exists
        $existing = db_query(
            "SELECT user_id FROM users WHERE username = ?",
            [$userData['username']]
        )->fetch();

        if ($existing) {
            return false;
        }

        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

        $stmt = db_query(
            "INSERT INTO users (username, password_hash, full_name, email, role, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW())",
            [
                $userData['username'],
                $passwordHash,
                $userData['full_name'],
                $userData['email'],
                $userData['role'] ?? 'user'
            ]
        );

        return $stmt->rowCount() > 0 ? getPDO()->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user password
 * @param int $userId
 * @param string $newPassword
 * @return bool
 */
function update_password($userId, $newPassword) {
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = db_query(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?",
            [$passwordHash, $userId]
        );
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Password update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Redirect back to previous page or default location
 * @param string $default Default URL if no referer
 */
function redirect_back($default = '/vlad/public/index.php') {
    $referer = $_SERVER['HTTP_REFERER'] ?? $default;
    header("Location: $referer");
    exit;
}

/**
 * Redirect to specific URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
