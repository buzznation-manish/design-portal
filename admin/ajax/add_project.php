<?php
/**
 * AJAX: Add Project (Admin)
 * Creates a new project with assigned designers
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

$data = [
    'client_name' => trim($_POST['client_name'] ?? ''),
    'event_name'  => trim($_POST['event_name']  ?? ''),
    'start_date'  => trim($_POST['start_date']  ?? ''),
    'end_date'    => trim($_POST['end_date']     ?? ''),
    'assigned_by' => trim($_POST['assigned_by'] ?? ''),
    'status'      => trim($_POST['status']       ?? 'Pending'),
];

$designers = array_values(array_filter(array_map('trim', (array)($_POST['designers'] ?? []))));

$errors = validateProjectData($data);

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $newId = createProject($data, $designers);
    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Project created successfully.']);
} catch (Exception $e) {
    error_log('Add project error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create project. Please try again.']);
}
