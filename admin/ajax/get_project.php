<?php
/**
 * AJAX: Get Single Project (Admin)
 * Returns full project data including designers array
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireAdminAuth();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID.']);
    exit;
}

$project = getProjectById($id);

if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found.']);
    exit;
}

// Designers are already an array from getProjectById
echo json_encode([
    'success' => true,
    'project' => [
        'id'          => (int)$project['id'],
        'client_name' => $project['client_name'],
        'event_name'  => $project['event_name'],
        'start_date'  => $project['start_date'],
        'end_date'    => $project['end_date'],
        'assigned_by' => $project['assigned_by'],
        'status'      => $project['status'],
        'designers'   => $project['designers'],
    ],
]);
