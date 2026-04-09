<?php
/**
 * AJAX: Get Projects (Public / Client View)
 * Read-only endpoint — no authentication required.
 * Returns paginated, searchable, sortable project list as JSON.
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$search  = trim($_GET['search']   ?? '');
$sortCol = trim($_GET['sort_col'] ?? 'id');
$sortDir = trim($_GET['sort_dir'] ?? 'DESC');
$page    = max(1, (int)($_GET['page']     ?? 1));
$perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));

$result = getProjects($search, $sortCol, $sortDir, $page, $perPage, false);

// Escape string output
$escaped = array_map(function (array $p): array {
    return [
        'id'             => (int)$p['id'],
        'client_name'    => htmlspecialchars($p['client_name'],  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'event_name'     => htmlspecialchars($p['event_name'],   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'designers'      => htmlspecialchars($p['designers'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'start_date'     => htmlspecialchars($p['start_date'],   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'end_date'       => htmlspecialchars($p['end_date'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'status'         => htmlspecialchars($p['status'],       ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'remaining_days' => (int)$p['remaining_days'],
    ];
}, $result['projects']);

echo json_encode([
    'success'      => true,
    'projects'     => $escaped,
    'total'        => $result['total'],
    'pages'        => $result['pages'],
    'current_page' => $page,
]);
