<?php
/**
 * Report Project Detail Page
 * Shows projects for a specific designer or sales user with search, sort, and pagination.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminAuth();

$fullName = e($_SESSION['admin_full_name'] ?? 'Admin');

$type = trim($_GET['type'] ?? '');   // 'designer' or 'sales'
$name = trim($_GET['name'] ?? '');

if (!in_array($type, ['designer', 'sales'], true) || $name === '') {
    header('Location: /admin/reports.php');
    exit;
}

$typeLabel  = $type === 'designer' ? 'Designer' : 'Sales User';
$pageTitle  = e($name) . ' — Projects';
$filterKey  = $type === 'designer' ? 'filter_designer' : 'filter_assigned_by';
$filterVal  = e($name);
$_activePage = 'reports.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> – Design Portal Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h1 class="topbar-title mb-0">
                <i class="bi bi-bar-chart-line me-2 text-primary"></i>
                <?= $typeLabel ?>: <?= e($name) ?>
            </h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge"><i class="bi bi-person-circle"></i><?= $fullName ?></span>
            <a href="/admin/logout.php" class="btn btn-sm btn-outline-secondary"
               onclick="return confirm('Log out?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="content-area">

        <div class="mb-3">
            <a href="/admin/reports.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Reports
            </a>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-table text-primary"></i>
                    <h5 class="mb-0 fw-bold">Projects for <?= e($name) ?></h5>
                </div>
                <div class="input-group input-group-sm" style="width:220px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control"
                           placeholder="Search client or event…">
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="row g-2 mb-0 px-3 pt-3 pb-2 border-bottom">
                <div class="col-md-auto">
                    <input type="text" class="form-control form-control-sm" id="filterClient" placeholder="Client name…">
                </div>
                <div class="col-md-auto">
                    <input type="text" class="form-control form-control-sm" id="filterEvent" placeholder="Event name…">
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
                        </tr>
                    </thead>
                    <tbody id="projectTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-top">
                <small class="text-muted" id="tableInfoText">Loading…</small>
                <nav id="paginationContainer"></nav>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
    /* ── Config injected from PHP ─────────────────────── */
    const FILTER_KEY = <?= json_encode($filterKey) ?>;
    const FILTER_VAL = <?= json_encode($name) ?>;

    /* ── State ────────────────────────────────────────── */
    let search        = '';
    let sortCol       = 'end_date';
    let sortDir       = 'ASC';
    let currentPage   = 1;
    const perPage     = 10;
    let filterClient  = '';
    let filterEvent   = '';
    let filterDays    = '';
    let debounceTimer = null;

    /* ── Initial load ─────────────────────────────────── */
    loadProjects();

    /* ── Search ───────────────────────────────────────── */
    $('#searchInput').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            search      = $('#searchInput').val().trim();
            currentPage = 1;
            loadProjects();
        }, 350);
    });

    /* ── Filters ──────────────────────────────────────── */
    $('#filterClient, #filterEvent').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            filterClient = $('#filterClient').val().trim();
            filterEvent  = $('#filterEvent').val().trim();
            currentPage  = 1;
            loadProjects();
        }, 350);
    });

    $('#filterDays').on('change', function () {
        filterDays  = $(this).val();
        currentPage = 1;
        loadProjects();
    });

    $('#btnClearFilters').on('click', function () {
        search = filterClient = filterEvent = filterDays = '';
        currentPage = 1;
        $('#searchInput').val('');
        $('#filterClient').val('');
        $('#filterEvent').val('');
        $('#filterDays').val('');
        loadProjects();
    });

    /* ── Sort ─────────────────────────────────────────── */
    $(document).on('click', '.sortable', function () {
        const col = $(this).data('col');
        if (sortCol === col) {
            sortDir = sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            sortCol = col;
            sortDir = 'ASC';
        }
        currentPage = 1;
        updateSortIcons();
        loadProjects();
    });

    function updateSortIcons() {
        $('.sortable').each(function () {
            const col = $(this).data('col');
            const icon = $(this).find('.sort-icon i');
            if (col === sortCol) {
                icon.removeClass('bi-arrow-down-up')
                    .addClass(sortDir === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down');
            } else {
                icon.removeClass('bi-arrow-up bi-arrow-down')
                    .addClass('bi-arrow-down-up');
            }
        });
    }

    /* ── AJAX load ────────────────────────────────────── */
    function loadProjects() {
        const params = {
            search:           search,
            sort_col:         sortCol,
            sort_dir:         sortDir,
            page:             currentPage,
            per_page:         perPage,
            filter_client:    filterClient,
            filter_event:     filterEvent,
            filter_days:      filterDays
        };
        params[FILTER_KEY] = FILTER_VAL;

        $.get('/admin/ajax/get_projects.php', params)
            .done(function (res) {
                if (!res.success) { showError('Failed to load projects.'); return; }
                renderRows(res.projects);
                renderPagination(res.total, res.pages, res.current_page);
            })
            .fail(function () { showError('Network error — please try again.'); });
    }

    /* ── Render rows ──────────────────────────────────── */
    function renderRows(projects) {
        const tbody = $('#projectTableBody');
        tbody.empty();

        if (!projects || projects.length === 0) {
            tbody.html(
                '<tr><td colspan="8" class="text-center text-muted py-5">' +
                '<i class="bi bi-inbox fs-2 d-block mb-2"></i>No projects found.</td></tr>'
            );
            return;
        }

        projects.forEach(function (p) {
            tbody.append(renderRow(p));
        });
    }

    function renderRow(p) {
        const days   = parseInt(p.remaining_days, 10);
        const status = p.status;
        let daysHtml;

        if (status === 'Completed') {
            daysHtml = '<span class="text-muted">—</span>';
        } else if (days < 0) {
            daysHtml = '<span class="days-overdue"><i class="bi bi-exclamation-circle me-1"></i>' + Math.abs(days) + ' overdue</span>';
        } else if (days < 3) {
            daysHtml = '<span class="days-urgent"><i class="bi bi-alarm me-1"></i>' + days + ' day' + (days === 1 ? '' : 's') + '</span>';
        } else {
            daysHtml = '<span>' + days + ' day' + (days === 1 ? '' : 's') + '</span>';
        }

        const badgeClass = {
            'Pending':   'badge-status-pending',
            'Ongoing':   'badge-status-ongoing',
            'Completed': 'badge-status-completed'
        }[status] || 'badge-status-pending';

        const badgeIcon = {
            'Pending':   'bi-hourglass',
            'Ongoing':   'bi-play-circle',
            'Completed': 'bi-check-circle'
        }[status] || 'bi-hourglass';

        const isUrgent = (status !== 'Completed') && (days < 3);

        const designers = p.designers
            ? p.designers.split(',').map(function (d) {
                return '<span class="badge bg-secondary me-1 fw-normal">' + escHtml(d.trim()) + '</span>';
              }).join('')
            : '<span class="text-muted fst-italic small">None</span>';

        const tr = $('<tr>').toggleClass('row-urgent', isUrgent);
        tr.html(
            '<td class="fw-semibold">' + escHtml(p.client_name) + '</td>' +
            '<td>' + escHtml(p.event_name) + '</td>' +
            '<td>' + designers + '</td>' +
            '<td>' + escHtml(p.start_date) + '</td>' +
            '<td>' + escHtml(p.end_date) + '</td>' +
            '<td>' + escHtml(p.assigned_by || '—') + '</td>' +
            '<td><span class="badge-status ' + badgeClass + '"><i class="bi ' + badgeIcon + '"></i> ' + escHtml(status) + '</span></td>' +
            '<td>' + daysHtml + '</td>'
        );
        return tr;
    }

    /* ── Pagination ───────────────────────────────────── */
    function renderPagination(total, pages, cur) {
        const container = $('#paginationContainer');
        const infoText  = $('#tableInfoText');
        const from      = total === 0 ? 0 : (cur - 1) * perPage + 1;
        const to        = Math.min(cur * perPage, total);

        infoText.text(
            total === 0
                ? 'No projects found'
                : 'Showing ' + from + '–' + to + ' of ' + total + ' project' + (total !== 1 ? 's' : '')
        );

        if (pages <= 1) { container.empty(); return; }

        const ul = $('<ul class="pagination pagination-sm mb-0">');

        ul.append(
            $('<li class="page-item' + (cur === 1 ? ' disabled' : '') + '">').append(
                $('<a class="page-link" href="#">').html('&laquo;').on('click', function (e) {
                    e.preventDefault();
                    if (cur > 1) { currentPage = cur - 1; loadProjects(); }
                })
            )
        );

        const start = Math.max(1, cur - 2);
        const end   = Math.min(pages, cur + 2);

        for (let i = start; i <= end; i++) {
            const li = $('<li class="page-item' + (i === cur ? ' active' : '') + '">').append(
                $('<a class="page-link" href="#">').text(i).on('click', (function (pg) {
                    return function (e) { e.preventDefault(); currentPage = pg; loadProjects(); };
                })(i))
            );
            ul.append(li);
        }

        ul.append(
            $('<li class="page-item' + (cur === pages ? ' disabled' : '') + '">').append(
                $('<a class="page-link" href="#">').html('&raquo;').on('click', function (e) {
                    e.preventDefault();
                    if (cur < pages) { currentPage = cur + 1; loadProjects(); }
                })
            )
        );

        container.html(ul);
    }

    /* ── Helpers ──────────────────────────────────────── */
    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showError(msg) {
        $('#projectTableBody').html(
            '<tr><td colspan="8" class="text-center text-danger py-4">' +
            '<i class="bi bi-exclamation-triangle me-1"></i>' + escHtml(msg) + '</td></tr>'
        );
    }

    /* ── Sidebar toggle ───────────────────────────────── */
    $('#sidebarToggle').on('click', function () {
        $('.sidebar').toggleClass('open');
        $('.sidebar-overlay').toggleClass('visible');
    });
    $(document).on('click', '.sidebar-overlay', function () {
        $('.sidebar').removeClass('open');
        $('.sidebar-overlay').removeClass('visible');
    });
});
</script>
</body>
</html>
