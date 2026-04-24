<?php
/**
 * Delivered Projects Page - Project Dashboard
 * Displays all completed projects (not segregated by manager)
 * Features: Month selector, Search, Sort, Pagination
 * Uses PDO for secure database operations
 */

// Get database connection using PDO
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die('Unable to connect to database. Please try again later.');
}

// Get current month and year
$currentYear = date('Y');
$currentMonth = date('m');
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
$selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;

// Validate month and year
$selectedMonth = (int)$selectedMonth;
$selectedYear = (int)$selectedYear;

if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = $currentMonth;
}

// Get search, sort, and page parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'end_date';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Get all available years with completed projects
try {
    $yearsQuery = "SELECT DISTINCT YEAR(end_date) as year FROM projects 
                   WHERE status = 'Completed' 
                   ORDER BY year DESC";
    $yearsStmt = $pdo->query($yearsQuery);
    $years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('Error fetching years: ' . $e->getMessage());
    $years = [$currentYear];
}

// Build query with filters
$query = "SELECT * FROM projects 
          WHERE status = 'Completed' 
          AND YEAR(end_date) = ? 
          AND MONTH(end_date) = ?";

$params = [$selectedYear, $selectedMonth];

// Add search filter
if (!empty($search)) {
    $query .= " AND (client_name LIKE ? OR event_name LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Count total projects
$countQuery = "SELECT COUNT(*) as total FROM projects 
               WHERE status = 'Completed' 
               AND YEAR(end_date) = ? 
               AND MONTH(end_date) = ?";
$countParams = [$selectedYear, $selectedMonth];

if (!empty($search)) {
    $countQuery .= " AND (client_name LIKE ? OR event_name LIKE ?)";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalProjects = $countResult['total'] ?? 0;
$totalPages = ceil($totalProjects / $itemsPerPage);

// Validate sort parameter
$allowedSorts = ['client_name', 'event_name', 'assigned_by', 'end_date', 'status'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'end_date';

// Fetch projects
$query .= " ORDER BY " . $sort . " ASC LIMIT ? OFFSET ?";
$params[] = (int)$itemsPerPage;
$params[] = (int)$offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Get overall delivered stats (all months)
try {
    $allStatsQuery = "SELECT COUNT(*) as total_delivered FROM projects WHERE status = 'Completed'";
    $allStatsStmt = $pdo->query($allStatsQuery);
    $allStats = $allStatsStmt->fetch(PDO::FETCH_ASSOC);
    $totalDelivered = $allStats['total_delivered'] ?? 0;
} catch (PDOException $e) {
    $totalDelivered = 0;
}

// Format selected month and year
$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivered Projects - Design Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tempusdominus-bootstrap-4@5.39.0/build/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #27ae60;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .stat-content h6 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-content h3 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
            color: #2c3e50;
        }

        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .btn-search {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
            height: fit-content;
        }

        .btn-search:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
            height: fit-content;
        }

        .btn-reset:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .projects-table {
            margin: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .projects-table thead {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .projects-table th {
            border: none;
            padding: 15px;
            vertical-align: middle;
        }

        .projects-table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .projects-table tbody tr {
            transition: background-color 0.3s ease;
        }

        .projects-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #81ecec;
            color: #00b894;
        }

        .designers-badge {
            display: inline-block;
            background: #ecf0f1;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        .pagination-info {
            padding: 15px 20px;
            background: #f8f9fa;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9rem;
            border-bottom: 1px solid #e9ecef;
        }

        .pagination {
            margin: 0;
            padding: 15px 20px;
            background: white;
            justify-content: center;
            gap: 5px;
            border-radius: 0 0 12px 12px;
        }

        .pagination .page-link {
            color: #3498db;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin: 0 2px;
        }

        .pagination .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }

        .pagination .page-link:hover {
            color: #2980b9;
            background-color: #ecf0f1;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .flatpickr-calendar {
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .header h1 { font-size: 1.8rem; }
            .stats-container { grid-template-columns: 1fr; }
            .filter-row { flex-direction: column; }
            .filter-group { min-width: 100%; }
            .btn-search, .btn-reset { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="text-center mb-3">
                <i class="fas fa-box-open" style="font-size: 2.5rem;"></i>
            </div>
            <h1 class="text-center">Projects Delivered</h1>
            <p class="text-center">View All Completed Projects by Month</p>
        </div>
    </div>

    <div class="container">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Statistics Section -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h6>Total Delivered</h6>
                    <h3><?php echo $totalDelivered; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h6><?php echo $monthName . ' ' . $selectedYear; ?></h6>
                    <h3><?php echo $totalProjects; ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-filter"></i> Filters</h5>
            <form method="get" id="filter-form">
                <div class="filter-row">
                    <!-- Month & Year Picker -->
                    <div class="filter-group">
                        <label for="month-year">Select Month & Year</label>
                        <input 
                            type="text" 
                            id="month-year" 
                            name="month_year" 
                            class="form-control" 
                            placeholder="Click to select month"
                            data-month="<?php echo $selectedMonth; ?>"
                            data-year="<?php echo $selectedYear; ?>"
                            readonly
                        >
                    </div>

                    <!-- Search Input -->
                    <div class="filter-group">
                        <label for="search">Search Client or Event</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="Search..." 
                            value="<?php echo escape($search); ?>"
                        >
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="end_date" <?php echo $sort === 'end_date' ? 'selected' : ''; ?>>Completion Date</option>
                            <option value="client_name" <?php echo $sort === 'client_name' ? 'selected' : ''; ?>>Client Name</option>
                            <option value="event_name" <?php echo $sort === 'event_name' ? 'selected' : ''; ?>>Event Name</option>
                            <option value="assigned_by" <?php echo $sort === 'assigned_by' ? 'selected' : ''; ?>>Assigned By</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>

                    <!-- Reset Button -->
                    <a href="delivered.php" class="btn-reset">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>

                <!-- Hidden inputs for month and year -->
                <input type="hidden" id="month-input" name="month" value="<?php echo $selectedMonth; ?>">
                <input type="hidden" id="year-input" name="year" value="<?php echo $selectedYear; ?>">
            </form>
        </div>

        <!-- Projects Table -->
        <?php if (!empty($projects)): ?>
            <div style="overflow-x: auto;">
                <table class="table projects-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Event Name</th>
                            <th>Designers</th>
                            <th>Assigned By</th>
                            <th>Completion Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <?php
                            // Get designers for this project
                            $designers = getDesignersForProject($pdo, $project['id']);
                            ?>
                            <tr>
                                <td><strong><?php echo escape($project['client_name']); ?></strong></td>
                                <td><?php echo escape($project['event_name']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($designers)) {
                                        foreach ($designers as $designer) {
                                            echo '<span class="designers-badge">' . escape($designer) . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color: #999; font-style: italic;">No designers</span>';
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo escape($project['assigned_by']); ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($project['end_date'])); ?></td>
                                <td>
                                    <span class="status-badge">
                                        <i class="fas fa-check"></i> Completed
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Info -->
            <div class="pagination-info">
                Showing <?php echo $offset + 1; ?> – <?php echo min($offset + $itemsPerPage, $totalProjects); ?> of <?php echo $totalProjects; ?> projects
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-inbox"></i>
                <h4>No Projects Delivered</h4>
                <p>No completed projects found for <?php echo $monthName . ' ' . $selectedYear; ?></p>
            </div>
        <?php endif; ?>

        <div style="height: 40px;"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script>
        // Initialize month/year picker
        const monthYearInput = document.getElementById('month-year');
        const monthInput = document.getElementById('month-input');
        const yearInput = document.getElementById('year-input');
        const currentMonth = parseInt(monthYearInput.dataset.month);
        const currentYear = parseInt(monthYearInput.dataset.year);

        // Set initial display value
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];
        monthYearInput.value = monthNames[currentMonth - 1] + ' ' + currentYear;

        // Initialize Flatpickr with month and year picker
        flatpickr(monthYearInput, {
            mode: 'single',
            plugins: [
                new flatpickr.plugins.monthSelect({
                    shorthand: false,
                    dateFormat: 'F Y'
                })
            ],
            dateFormat: 'F Y',
            defaultDate: new Date(currentYear, currentMonth - 1, 1),
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    const date = selectedDates[0];
                    monthInput.value = String(date.getMonth() + 1).padStart(2, '0');
                    yearInput.value = date.getFullYear();
                }
            }
        });
    </script>
</body>
</html>
