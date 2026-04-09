<?php
/**
 * Client View – Public Dashboard
 * Read-only view of design projects. No login required.
 */

require_once __DIR__ . '/includes/functions.php';

$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Portal – Project Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light">

<!-- ── Navbar ─────────────────────────────────────────────── -->
<nav class="client-navbar">
    <a href="/" class="client-navbar-brand">
        <i class="bi bi-palette2"></i>
        <span>Design Portal</span>
    </a>
    <a href="/admin/login.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-shield-lock me-1"></i>Admin
    </a>
</nav>

<!-- ── Hero Header ───────────────────────────────────────── -->
<div class="client-header">
    <div class="container-xl">
        <i class="bi bi-palette2 d-block fs-1 mb-3 opacity-75"></i>
        <h1>Design Portal</h1>
        <p>Project Management Dashboard — Live Status &amp; Progress Tracker</p>
    </div>
</div>

<!-- ── Main Content ──────────────────────────────────────── -->
<div class="container-xl py-4">

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                <div>
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-ongoing">
                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                <div>
                    <div class="stat-number"><?= $stats['ongoing'] ?></div>
                    <div class="stat-label">Ongoing</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-completed">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-number"><?= $stats['completed'] ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-pending">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table Card -->
    <div class="client-table-card">
        <div class="client-table-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i>
                <h5 class="mb-0 fw-bold">Current Projects</h5>
            </div>
            <div class="input-group input-group-sm" style="width:220px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control"
                       placeholder="Search client or event…">
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="row g-2 px-3 pt-2 pb-3 border-bottom" id="filterBar">
            <div class="col-sm-auto">
                <input type="text" class="form-control form-control-sm" id="filterClient" placeholder="Client name…">
            </div>
            <div class="col-sm-auto">
                <input type="text" class="form-control form-control-sm" id="filterEvent" placeholder="Event name…">
            </div>
            <div class="col-sm-auto">
                <select class="form-select form-select-sm" id="filterAssignedBy" style="min-width:160px">
                    <option value="">All Sales Users</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <select class="form-select form-select-sm" id="filterDesigner" style="min-width:160px">
                    <option value="">All Designers</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <select class="form-select form-select-sm" id="filterDays">
                    <option value="">All Deadlines</option>
                    <option value="overdue">Overdue</option>
                    <option value="urgent">Urgent (≤ 2 days)</option>
                    <option value="week">This Week (≤ 6 days)</option>
                    <option value="month">This Month (≤ 29 days)</option>
                </select>
            </div>
            <div class="col-sm-auto">
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
                        <th class="sortable" data-col="status">
                            Status <span class="sort-icon"><i class="bi bi-arrow-down-up"></i></span>
                        </th>
                        <th>Remaining Days</th>
                    </tr>
                </thead>
                <tbody id="projectTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table footer -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-top">
            <small class="text-muted" id="tableInfoText">Loading…</small>
            <nav id="paginationContainer"></nav>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex align-items-center flex-wrap gap-3 mt-3 mb-4">
        <small class="text-muted fw-semibold">Legend:</small>
        <span class="badge-status badge-status-pending"><i class="bi bi-hourglass me-1"></i>Pending</span>
        <span class="badge-status badge-status-ongoing"><i class="bi bi-play-circle me-1"></i>Ongoing</span>
        <span class="badge-status badge-status-completed"><i class="bi bi-check-circle me-1"></i>Completed</span>
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded px-2 py-1" style="font-size:.78rem">
            <i class="bi bi-alarm me-1"></i>Red row = deadline within 3 days
        </span>
    </div>

</div><!-- /container -->

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer class="border-top py-3 mt-auto">
    <div class="container-xl text-center text-muted" style="font-size:.82rem">
        &copy; <?= date('Y') ?> Design Portal &mdash; Project Management System
        &nbsp;|&nbsp;
        <a href="/admin/login.php" class="text-muted text-decoration-none">
            <i class="bi bi-shield-lock me-1"></i>Admin Panel
        </a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/assets/js/client.js"></script>
</body>
</html>
