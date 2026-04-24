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
    $pdo  = getDBConnection();
    // Single query using conditional aggregation to avoid multiple round-trips
    $row = $pdo->query(
        "SELECT
            COUNT(*)                                     AS total,
            SUM(status = 'Ongoing')                      AS ongoing,
            SUM(status = 'Completed')                    AS completed,
            SUM(status = 'Pending')                      AS pending
         FROM projects"
    )->fetch();
    return [
        'total'     => (int)($row['total']     ?? 0),
        'ongoing'   => (int)($row['ongoing']   ?? 0),
        'completed' => (int)($row['completed'] ?? 0),
        'pending'   => (int)($row['pending']   ?? 0),
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
    bool $adminView = true,
    array $filters  = []
): array {
    $pdo = getDBConnection();

    // Whitelist sortable columns to prevent SQL injection via ORDER BY
    $allowedCols = ['id', 'client_name', 'event_name', 'start_date', 'end_date', 'assigned_by', 'status'];
    if (!in_array($sortCol, $allowedCols, true)) {
        $sortCol = 'id';
    }
    $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $perPage;

    // Build WHERE conditions
    $conditions = [];
    $params     = [];

    if ($search !== '') {
        $conditions[] = '(p.client_name LIKE ? OR p.event_name LIKE ?)';
        $params[]     = '%' . $search . '%';
        $params[]     = '%' . $search . '%';
    }

    if (!empty($filters['client_name'])) {
        $conditions[] = 'p.client_name LIKE ?';
        $params[]     = '%' . $filters['client_name'] . '%';
    }

    if (!empty($filters['event_name'])) {
        $conditions[] = 'p.event_name LIKE ?';
        $params[]     = '%' . $filters['event_name'] . '%';
    }

    if (!empty($filters['assigned_by'])) {
        $conditions[] = 'p.assigned_by = ?';
        $params[]     = $filters['assigned_by'];
    }

    if (!empty($filters['designer'])) {
        $conditions[] = 'EXISTS (SELECT 1 FROM project_designers pd2 WHERE pd2.project_id = p.id AND pd2.designer_name = ?)';
        $params[]     = $filters['designer'];
    }

    if (!empty($filters['remaining_days'])) {
        switch ($filters['remaining_days']) {
            case 'overdue':
                $conditions[] = 'DATEDIFF(p.end_date, CURDATE()) < 0 AND p.status != \'Completed\'';
                break;
            case 'urgent':
                $conditions[] = 'DATEDIFF(p.end_date, CURDATE()) BETWEEN 0 AND 2 AND p.status != \'Completed\'';
                break;
            case 'week':
                $conditions[] = 'DATEDIFF(p.end_date, CURDATE()) BETWEEN 0 AND 6 AND p.status != \'Completed\'';
                break;
            case 'month':
                $conditions[] = 'DATEDIFF(p.end_date, CURDATE()) BETWEEN 0 AND 29 AND p.status != \'Completed\'';
                break;
        }
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

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

function getDesigners(): array {
    $pdo = getDBConnection();
    return $pdo->query("SELECT id, name, created_at FROM designers ORDER BY name")->fetchAll();
}

function createDesigner(string $name): int {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO designers (name) VALUES (?)");
    $stmt->execute([trim($name)]);
    return (int)$pdo->lastInsertId();
}

function deleteDesigner(int $id): void {
    $pdo = getDBConnection();
    $pdo->prepare("DELETE FROM designers WHERE id = ?")->execute([$id]);
}

function getDesignerReport(): array {
    $pdo = getDBConnection();
    $stmt = $pdo->query(
        "SELECT d.name AS designer,
                COUNT(pd.project_id) AS total,
                SUM(p.status = 'Ongoing') AS ongoing,
                SUM(p.status = 'Pending') AS pending,
                SUM(p.status = 'Completed') AS completed
         FROM designers d
         LEFT JOIN project_designers pd ON pd.designer_name = d.name
         LEFT JOIN projects p ON p.id = pd.project_id
         GROUP BY d.name
         ORDER BY d.name"
    );
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $row['total']     = (int)$row['total'];
        $row['ongoing']   = (int)$row['ongoing'];
        $row['pending']   = (int)$row['pending'];
        $row['completed'] = (int)$row['completed'];
        $ps = $pdo->prepare(
            "SELECT p.id, p.client_name, p.event_name, p.status, p.end_date
             FROM projects p
             INNER JOIN project_designers pd ON pd.project_id = p.id
             WHERE pd.designer_name = ?
             ORDER BY p.end_date"
        );
        $ps->execute([$row['designer']]);
        $row['projects'] = $ps->fetchAll();
        $result[] = $row;
    }
    return $result;
}

function getSalesUsers(): array {
    $pdo = getDBConnection();
    return $pdo->query("SELECT id, name, created_at FROM sales_users ORDER BY name")->fetchAll();
}

function createSalesUser(string $name): int {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO sales_users (name) VALUES (?)");
    $stmt->execute([trim($name)]);
    return (int)$pdo->lastInsertId();
}

function deleteSalesUser(int $id): void {
    $pdo = getDBConnection();
    $pdo->prepare("DELETE FROM sales_users WHERE id = ?")->execute([$id]);
}

function getSalesUserReport(): array {
    $pdo = getDBConnection();
    $stmt = $pdo->query(
        "SELECT su.name AS sales_user,
                COUNT(p.id) AS total,
                SUM(p.status = 'Ongoing') AS ongoing,
                SUM(p.status = 'Pending') AS pending,
                SUM(p.status = 'Completed') AS completed
         FROM sales_users su
         LEFT JOIN projects p ON p.assigned_by = su.name
         GROUP BY su.name
         ORDER BY su.name"
    );
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $row['total']     = (int)$row['total'];
        $row['ongoing']   = (int)$row['ongoing'];
        $row['pending']   = (int)$row['pending'];
        $row['completed'] = (int)$row['completed'];
        $ps = $pdo->prepare(
            "SELECT id, client_name, event_name, status, end_date
             FROM projects WHERE assigned_by = ? ORDER BY end_date"
        );
        $ps->execute([$row['sales_user']]);
        $row['projects'] = $ps->fetchAll();
        $result[] = $row;
    }
    return $result;
}
