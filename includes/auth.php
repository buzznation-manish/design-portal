<?php
/**
 * Authentication Helpers
 * Admin login/logout and route protection
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

/**
 * Check if admin is logged in. Redirect to login page if not.
 */
function requireAdminAuth(): void {
    startSecureSession();
    checkSessionTimeout();
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Attempt admin login with username and password.
 * Returns true on success (sets session), false on failure.
 */
function attemptLogin(string $username, string $password): bool {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT id, username, password, full_name FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID to prevent session fixation
        regenerateSession();
        $_SESSION['admin_id']        = $admin['id'];
        $_SESSION['admin_username']  = $admin['username'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    return false;
}

/**
 * Destroy admin session and redirect to login.
 */
function logoutAdmin(): void {
    session_unset();
    session_destroy();
    header('Location: /admin/login.php?logout=1');
    exit;
}

/**
 * Check if admin is currently logged in (non-redirecting).
 */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}
