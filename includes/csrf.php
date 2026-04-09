<?php
/**
 * CSRF Protection
 * Generates and validates CSRF tokens for all forms
 */

/**
 * Generate a CSRF token and store it in session.
 * Returns the token string.
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the stored session token.
 * Uses hash_equals to prevent timing attacks.
 */
function validateCSRFToken(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF input field for use in HTML forms.
 */
function csrfInput(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request; terminate with error if invalid.
 */
function requireCSRF(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']));
    }
}
