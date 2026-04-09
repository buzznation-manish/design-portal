<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
requireAdminAuth();
requireCSRF();
$name = trim($_POST['name'] ?? '');
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Sales user name is required.']);
    exit;
}
if (strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Name must be 100 characters or less.']);
    exit;
}
try {
    $id = createSalesUser($name);
    echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
} catch (Exception $e) {
    $msg = str_contains($e->getMessage(), 'Duplicate') ? 'A sales user with that name already exists.' : 'Failed to add sales user.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
