<?php
/**
 * Admin Login Page
 * Handles authentication for admin panel access
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

startSecureSession();

// Already logged in → redirect to admin dashboard
if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);
$logout  = isset($_GET['logout']);

// Handle POST login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } elseif (attemptLogin($username, $password)) {
            header('Location: /admin/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – Design Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

<div class="login-wrapper">
    <div class="card shadow-lg border-0" style="max-width:420px;width:100%;">
        <div class="card-header bg-dark text-white text-center py-4">
            <i class="bi bi-palette fs-1 d-block mb-2"></i>
            <h4 class="mb-0 fw-bold">Design Portal</h4>
            <small class="opacity-75">Admin Access</small>
        </div>
        <div class="card-body p-4">

            <?php if ($timeout): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-clock me-1"></i>Your session expired due to inactivity. Please log in again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($logout): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>You have been logged out successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/login.php" novalidate id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Enter your username" required autocomplete="username"
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePass">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

        </div>
        <div class="card-footer text-center text-muted small py-3">
            <a href="/" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back to Client View
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.getElementById('togglePass').addEventListener('click', function () {
    const input  = document.getElementById('password');
    const icon   = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
</body>
</html>
