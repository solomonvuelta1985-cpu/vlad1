<?php
// Input Sanitization
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Safe Database Query Execution
function db_query($sql, $params = []) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage() . " - Query: " . $sql);
        throw new Exception("Database operation failed");
    }
}

// CSRF Token Management
function generate_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash Message System
function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function show_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
    }
    return '';
}

// Rate Limiting
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    $current_time = time();
    $rate_key = "rate_limit_{$key}";
    
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        return true;
    }
    
    $rate_data = $_SESSION[$rate_key];
    
    if ($current_time - $rate_data['first_attempt'] > $time_window) {
        // Reset counter if time window has passed
        $_SESSION[$rate_key] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        return true;
    }
    
    if ($rate_data['attempts'] >= $max_attempts) {
        return false;
    }
    
    $rate_data['attempts']++;
    $_SESSION[$rate_key] = $rate_data;
    return true;
}

// Skeleton Loader HTML
function skeleton_loader($type = 'card', $count = 1) {
    $html = '';
    for ($i = 0; $i < $count; $i++) {
        if ($type === 'card') {
            $html .= '<div class="card skeleton-loader">
                        <div class="card-body">
                            <div class="skeleton-line" style="width: 70%"></div>
                            <div class="skeleton-line" style="width: 90%"></div>
                            <div class="skeleton-line" style="width: 60%"></div>
                        </div>
                    </div>';
        } elseif ($type === 'table') {
            $html .= '<div class="skeleton-loader">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line" style="width: 80%"></div>
                        <div class="skeleton-line" style="width: 60%"></div>
                    </div>';
        }
    }
    return $html;
}
?>