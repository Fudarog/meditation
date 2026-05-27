<?php
// Функция подключения к БД
if (!function_exists('connect')) {
    function connect() {
        $conn = mysqli_connect('localhost', 'root', 'root', 'meditation');
        if (!$conn) {
            die('Ошибка подключения: ' . mysqli_connect_error());
        }
        mysqli_set_charset($conn, 'utf8');
        return $conn;
    }
}

// Защита от XSS
function safe_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Заголовки
function security_headers() {
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' data:; ...");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=()");
}

// Защита от DDoS
function check_rate_limit($action = 'general', $max_requests = 10, $window = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = __DIR__ . '/../tmp/limits/' . md5($ip . $action) . '.txt';
    $dir = dirname($file);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $time = time();
    $data = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
    $valid_requests = array_filter($data, fn($d) => $time - (int)$d < $window);

    if (count($valid_requests) >= $max_requests) {
        return false; // Блокируем
    }

    file_put_contents($file, $time . PHP_EOL, FILE_APPEND | LOCK_EX);
    return true;
}

// SMTP-параметры
define('SMTP_HOST', 'smtp.yourhost.com');
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_PORT', 587);

// Логирование попыток входа
function log_auth_attempt($conn, $email, $success, $user_id = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (ip_address, user_id, email, success, attempt_at, user_agent) 
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    if ($stmt) {
        $stmt->bind_param("sisis", $ip, $user_id, $email, $success, $user_agent);
        return $stmt->execute();
    }
    return false;
}

// Проверка блокировки
function is_blocked($conn, $email = null, $ip = null, $max_attempts = 5, $block_minutes = 15) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $query = "
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE (ip_address = ? OR email = ?) 
          AND success = 0 
          AND attempt_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $ip, $email, $block_minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return ($row['attempts'] ?? 0) >= $max_attempts;
}

// Очистка старых логов
function cleanup_old_logs($conn, $days = 30) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    return $stmt->execute();
}

// Получение неудачных попыток
function get_failed_attempts($conn, $email) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE email = ? 
          AND success = 0 
          AND attempt_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['attempts'] ?? 0;
}

// Очистка Rate limit
function clear_rate_limit($action, $ip = null) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = __DIR__ . '/../tmp/limits/' . md5($ip . $action) . '.txt';
    if (file_exists($file)) {
        unlink($file);
    }
}

// Проверка времени заполнения формы 
function check_form_timing($timestamp_post, $min_seconds = 2) {
    if (!isset($timestamp_post)) {
        return false;
    }
    $time_to_fill = time() - (int)$timestamp_post;
    return $time_to_fill >= $min_seconds;
}

// CSRF-токен
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}