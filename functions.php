<?php
/**
 * Global Helper Functions & Security Library
 */

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            die('<div style="padding:20px; font-family:sans-serif; color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:4px;"><strong>Security Validation Failed:</strong> CSRF token mismatch or expired request. Please go back, refresh the page, and try again.</div>');
        }
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show shadow-sm" role="alert">
                ' . $flash['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

function log_activity($pdo, $user_id, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently fail to avoid crashing main execution on log failure
    }
}

function get_setting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function calculate_grade($marks, $max_marks = 100) {
    if ($max_marks <= 0) return 'F';
    $percentage = ($marks / $max_marks) * 100;
    if ($percentage >= 90) return 'O'; // Outstanding
    if ($percentage >= 80) return 'A+';
    if ($percentage >= 70) return 'A';
    if ($percentage >= 60) return 'B+';
    if ($percentage >= 50) return 'B';
    if ($percentage >= 40) return 'C';
    if ($percentage >= 35) return 'P';
    return 'F';
}

function format_date($date_str, $format = 'd M Y') {
    if (!$date_str || $date_str == '0000-00-00') return '-';
    return date($format, strtotime($date_str));
}
