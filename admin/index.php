<?php
/**
 * Admin Dashboard
 * Main admin panel page with project management
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminAuth();

$stats     = getDashboardStats();
$csrfToken = generateCSRFToken();
$username  = e($_SESSION['admin_username'] ?? 'Admin');
$fullName  = e($_SESSION['admin_full_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Design Portal Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">

<?php $_activePage = 'index.php'; require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- ── Main Content ─────────────────────────────────────── -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="topbar-title mb-0">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
            </h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge">
                <i class="bi bi-person-circle"></i><?= $fullName ?>
            </span>
            <a href="/admin/logout.php" class="btn btn-sm btn-outline-secondary"
               onclick="return confirm('Log out?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">

        <!-- ── Stat Cards ──────────────────────────────────── -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card stat-total">
                    <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                    <div>
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card stat-ongoing">
                    <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                    <div>
                        <div class="stat-number"><?= $stats['ongoing'] ?></div>
                        <div class="stat-label">Ongoing</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card stat-completed">
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-number"><?= $stats['completed'] ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card stat-pending">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Projects Table Card ─────────────────────────── -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-table text-primary"></i>
                    <h5 class="mb-0 fw-bold">Projects</h5>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="width:220px">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="Search client or event…">
                    </div>
                    <button class="btn btn-sm btn-primary" id="btnAddProject">
                        <i class="bi bi-plus-lg me-1"></i>Add Project
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="row g-2 mb-0 px-3 pt-3 pb-2 border-bottom" id="filterBar">
                <div class="col-md-auto">
                    <input type="text" class="form-control form-control-sm" id="filterClient" placeholder="Client name…">
                </div>
                <div class="col-md-auto">
                    <input type="text" class="form-control form-control-sm" id="filterEvent" placeholder="Event name…">
                </div>
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" id="filterAssignedBy" style="min-width:160px">
                        <option value="">All Sales Users</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" id="filterDesigner" style="min-width:160px">
                        <option value="">All Designers</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" id="filterDays">
                        <option value="">All Deadlines</option>
                        <option value="overdue">Overdue</option>
                        <option value="urgent">Urgent (≤ 2 days)</option>
                        <option value="week">This Week (≤ 6 days)</option>
                        <option value="month">This Month (≤ 29 days)</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-outline-secondary" id="btnClearFilters">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-col="client_name">
                                Client Name <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th class="sortable" data-col="event_name">
                                Event Name <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th>Designers</th>
                            <th class="sortable" data-col="start_date">
                                Start Date <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th class="sortable" data-col="end_date">
                                End Date <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th class="sortable" data-col="assigned_by">
                                Assigned By <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th class="sortable" data-col="status">
                                Status <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                            </th>
                            <th>Remaining Days</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="projectTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table footer: info + pagination -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-top">
                <small class="text-muted" id="tableInfoText">Loading…</small>
                <nav id="paginationContainer"></nav>
            </div>
        </div>

    </div><!-- /content-area -->
</div><!-- /main-content -->

<!-- Hidden CSRF token for JavaScript -->
<input type="hidden" id="csrfTokenVal" value="<?= e($csrfToken) ?>">

<!-- Toast Container -->
<div id="toastContainer"></div>

<!-- ── Add Project Modal ──────────────────────────────────── -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Add New Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="addModalError"></div>
                <form id="addProjectForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="client_name" maxlength="100"
                                   placeholder="e.g. ABC Corporation" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Event Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_name" maxlength="150"
                                   placeholder="e.g. Annual Gala 2026" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned By <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_by" id="addAssignedBy" required><option value="">— Select Sales User —</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Designers
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="btnAddDesigner">
                                    <i class="bi bi-plus me-1"></i>Add Designer
                                </button>
                            </label>
                            <div id="addDesignerList"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveAdd">
                    <i class="bi bi-check-lg me-1"></i>Save Project
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Project Modal ─────────────────────────────────── -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bi bi-pencil me-2 text-warning"></i>Edit Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="editModalError"></div>
                <form id="editProjectForm" novalidate>
                    <input type="hidden" id="editProjectId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editClientName" name="client_name"
                                   maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Event Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEventName" name="event_name"
                                   maxlength="150" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editStartDate" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editEndDate" name="end_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned By <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAssignedBy" name="assigned_by" required><option value="">— Select Sales User —</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Designers
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="btnEditDesigner">
                                    <i class="bi bi-plus me-1"></i>Add Designer
                                </button>
                            </label>
                            <div id="editDesignerList"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="btnSaveEdit">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Delete Confirmation Modal ──────────────────────────── -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="bi bi-trash me-2"></i>Delete Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete this project? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
