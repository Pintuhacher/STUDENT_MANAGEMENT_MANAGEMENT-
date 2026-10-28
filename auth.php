<?php
/**
 * Authentication and Role-based Access Middleware
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        die('<div style="margin:50px auto; max-width:600px; font-family:sans-serif; text-align:center;">
                <h2 style="color:#e11d48;">Access Denied (403)</h2>
                <p>You do not have the required permissions to access this control panel.</p>
                <a href="' . BASE_URL . '/logout.php" style="padding:8px 16px; background:#4f46e5; color:white; text-decoration:none; border-radius:4px;">Back to Login</a>
             </div>');
    }
}

function get_current_user_profile($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
