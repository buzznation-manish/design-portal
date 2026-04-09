<?php
/**
 * AJAX: Get filter dropdown options (public)
 * Returns designers and sales_users lists for filter dropdowns.
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $designers  = array_map(fn($d) => htmlspecialchars($d['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), getDesigners());
    $salesUsers = array_map(fn($u) => htmlspecialchars($u['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), getSalesUsers());

    echo json_encode([
        'success'     => true,
        'designers'   => $designers,
        'sales_users' => $salesUsers,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load filter options.']);
}
