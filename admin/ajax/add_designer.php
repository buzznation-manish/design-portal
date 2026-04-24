<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
requireAdminAuth();
requireCSRF();
$name = trim($_POST['name'] ?? '');
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Designer name is required.']);
    exit;
}
if (strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Name must be 100 characters or less.']);
    exit;
}
try {
    $id = createDesigner($name);
    echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
} catch (PDOException $e) {
    $msg = ($e->getCode() === '23000') ? 'A designer with that name already exists.' : 'Failed to add designer.';
    echo json_encode(['success' => false, 'message' => $msg]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add designer.']);
}
