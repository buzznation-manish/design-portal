<?php
/**
 * AJAX: Delete Project (Admin)
 * Deletes a project and its associated designers (CASCADE)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireCSRF();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID.']);
    exit;
}

// Verify project exists before attempting delete
$existing = getProjectById($id);
if (!$existing) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found.']);
    exit;
}

try {
    deleteProject($id);
    echo json_encode(['success' => true, 'message' => 'Project deleted successfully.']);
} catch (Exception $e) {
    error_log('Delete project error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete project. Please try again.']);
}
