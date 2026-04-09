<?php
/**
 * Admin Logout
 * Destroys session and redirects to login page
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
logoutAdmin();
