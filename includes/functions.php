<?php
/**
 * General Helper Functions
 * Used across admin panel and client view
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize output to prevent XSS.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate and sanitize a date string (expects Y-m-d).
 */
function sanitizeDate(string $date): string {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        return $date;
    }
    return '';
}

/**
 * Calculate remaining days from today to given end date.
 * Returns negative value if overdue.
 */
function calculateRemainingDays(string $endDate): int {
    $today = new DateTime('today');
    $end   = new DateTime($endDate);
    $diff  = $today->diff($end);
    $days  = (int)$diff->days;
    return $end >= $today ? $days : -$days;
}

/**
 * Get dashboard statistics.
 */
function getDashboardStats(): array {
    $pdo = getDBConnection();
    $total     = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $ongoing   = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Ongoing'")->fetchColumn();
    $completed = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Completed'")->fetchColumn();
    $pending   = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Pending'")->fetchColumn();
    return [
        'total'     => (int)$total,
        'ongoing'   => (int)$ongoing,
        'completed' => (int)$completed,
        'pending'   => (int)$pending,
    ];
}

/**
 * Get paginated, searchable, sortable project list.
 *
 * @param string $search     Search term (client_name or event_name)
 * @param string $sortCol    Column to sort by
 * @param string $sortDir    Sort direction: ASC or DESC
 * @param int    $page       Current page (1-based)
 * @param int    $perPage    Items per page
 * @param bool   $adminView  Whether to include assigned_by column
 * @return array             ['projects' => [...], 'total' => int, 'pages' => int]
 */
function getProjects(
    string $search  = '',
    string $sortCol = 'id',
    string $sortDir = 'DESC',
    int $page       = 1,
    int $perPage    = 10,
    bool $adminView = true
): array {
    $pdo = getDBConnection();

    // Whitelist sortable columns to prevent SQL injection via ORDER BY
    $allowedCols = ['id', 'client_name', 'event_name', 'start_date', 'end_date', 'assigned_by', 'status'];
    if (!in_array($sortCol, $allowedCols, true)) {
        $sortCol = 'id';
    }
    $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $perPage;

    // Build WHERE clause for search
    $where  = '';
    $params = [];
    if ($search !== '') {
        $where    = 'WHERE (p.client_name LIKE ? OR p.event_name LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    // Total count query
    $countSql  = "SELECT COUNT(*) FROM projects p $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = (int)ceil($total / $perPage);

    // Data query — aggregate designers with GROUP_CONCAT
    $sql = "SELECT p.id, p.client_name, p.event_name, p.start_date, p.end_date,
                   p.assigned_by, p.status,
                   GROUP_CONCAT(pd.designer_name ORDER BY pd.id SEPARATOR ', ') AS designers
            FROM projects p
            LEFT JOIN project_designers pd ON pd.project_id = p.id
            $where
            GROUP BY p.id
            ORDER BY $sortCol $sortDir
            LIMIT ? OFFSET ?";

    $dataParams   = $params;
    $dataParams[] = $perPage;
    $dataParams[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dataParams);
    $projects = $stmt->fetchAll();

    // Calculate remaining days for each project
    foreach ($projects as &$project) {
        $project['remaining_days'] = calculateRemainingDays($project['end_date']);
    }
    unset($project);

    return ['projects' => $projects, 'total' => $total, 'pages' => $pages];
}

/**
 * Get a single project by ID with its designers.
 */
function getProjectById(int $id): ?array {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT p.*, GROUP_CONCAT(pd.designer_name ORDER BY pd.id SEPARATOR '|||') AS designers_raw
         FROM projects p
         LEFT JOIN project_designers pd ON pd.project_id = p.id
         WHERE p.id = ?
         GROUP BY p.id"
    );
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    if (!$project) {
        return null;
    }
    $project['designers'] = $project['designers_raw']
        ? array_map('trim', explode('|||', $project['designers_raw']))
        : [];
    return $project;
}

/**
 * Insert a new project with designers.
 * Returns the new project ID or throws on failure.
 */
function createProject(array $data, array $designers): int {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO projects (client_name, event_name, start_date, end_date, assigned_by, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['client_name'],
            $data['event_name'],
            $data['start_date'],
            $data['end_date'],
            $data['assigned_by'],
            $data['status'],
        ]);
        $projectId = (int)$pdo->lastInsertId();

        // Insert designers
        $dStmt = $pdo->prepare("INSERT INTO project_designers (project_id, designer_name) VALUES (?, ?)");
        foreach ($designers as $name) {
            $name = trim($name);
            if ($name !== '') {
                $dStmt->execute([$projectId, $name]);
            }
        }

        $pdo->commit();
        return $projectId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Update an existing project with designers.
 */
function updateProject(int $id, array $data, array $designers): void {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE projects SET client_name=?, event_name=?, start_date=?, end_date=?, assigned_by=?, status=?
             WHERE id=?"
        );
        $stmt->execute([
            $data['client_name'],
            $data['event_name'],
            $data['start_date'],
            $data['end_date'],
            $data['assigned_by'],
            $data['status'],
            $id,
        ]);

        // Remove old designers and re-insert
        $pdo->prepare("DELETE FROM project_designers WHERE project_id = ?")->execute([$id]);
        $dStmt = $pdo->prepare("INSERT INTO project_designers (project_id, designer_name) VALUES (?, ?)");
        foreach ($designers as $name) {
            $name = trim($name);
            if ($name !== '') {
                $dStmt->execute([$id, $name]);
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Delete a project and its designers (CASCADE handles designers).
 */
function deleteProject(int $id): void {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * Validate project form data. Returns array of error messages.
 */
function validateProjectData(array $data): array {
    $errors = [];

    if (empty(trim($data['client_name'] ?? ''))) {
        $errors[] = 'Client name is required.';
    } elseif (strlen($data['client_name']) > 100) {
        $errors[] = 'Client name must be 100 characters or less.';
    }

    if (empty(trim($data['event_name'] ?? ''))) {
        $errors[] = 'Event name is required.';
    } elseif (strlen($data['event_name']) > 150) {
        $errors[] = 'Event name must be 150 characters or less.';
    }

    if (empty($data['start_date']) || !sanitizeDate($data['start_date'])) {
        $errors[] = 'Valid start date is required.';
    }

    if (empty($data['end_date']) || !sanitizeDate($data['end_date'])) {
        $errors[] = 'Valid end date is required.';
    }

    if (!empty($data['start_date']) && !empty($data['end_date'])
        && sanitizeDate($data['start_date']) && sanitizeDate($data['end_date'])) {
        if ($data['end_date'] < $data['start_date']) {
            $errors[] = 'End date must be on or after start date.';
        }
    }

    if (empty(trim($data['assigned_by'] ?? ''))) {
        $errors[] = 'Assigned by is required.';
    }

    $validStatuses = ['Pending', 'Ongoing', 'Completed'];
    if (!in_array($data['status'] ?? '', $validStatuses, true)) {
        $errors[] = 'Invalid project status.';
    }

    return $errors;
}
