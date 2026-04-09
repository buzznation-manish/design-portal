<?php
/**
 * Session Management
 * Handles secure session initialization and timeout
 */

define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

/**
 * Start secure session with proper settings.
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        // Use Secure flag in production (HTTPS)
        // ini_set('session.cookie_secure', 1);
        session_start();
    }
}

/**
 * Check for session timeout.
 * If the admin has been inactive for SESSION_TIMEOUT seconds, destroy the session.
 */
function checkSessionTimeout(): void {
    if (isset($_SESSION['admin_last_activity'])) {
        if ((time() - $_SESSION['admin_last_activity']) > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: /admin/login.php?timeout=1');
            exit;
        }
    }
    $_SESSION['admin_last_activity'] = time();
}

/**
 * Regenerate session ID to prevent session fixation attacks.
 */
function regenerateSession(): void {
    session_regenerate_id(true);
}
