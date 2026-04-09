<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminAuth();

$designerReport  = getDesignerReport();
$salesUserReport = getSalesUserReport();
$username        = e($_SESSION['admin_username'] ?? 'Admin');
$fullName        = e($_SESSION['admin_full_name'] ?? 'Admin');
$_activePage     = 'reports.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports – Design Portal Admin</title>
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
            <h1 class="topbar-title mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Reports</h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge"><i class="bi bi-person-circle"></i><?= $fullName ?></span>
            <a href="/admin/logout.php" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Log out?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="content-area">

        <!-- Designer Workload Report -->
        <div class="table-card mb-4">
            <div class="table-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people text-primary"></i>
                    <h5 class="mb-0 fw-bold">Designer Workload</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Designer</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Ongoing</th>
                            <th class="text-center">Pending</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($designerReport)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No data available.</td></tr>
                        <?php else: foreach ($designerReport as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['designer']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $row['total'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-ongoing"><?= $row['ongoing'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-pending"><?= $row['pending'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-completed"><?= $row['completed'] ?></span></td>
                            <td class="text-center">
                                <?php if ($row['total'] > 0): ?>
                                <button class="btn btn-sm btn-outline-primary btn-toggle-projects"
                                        data-projects="<?= e(json_encode($row['projects'])) ?>"
                                        data-target="dr-<?= e($row['designer']) ?>">
                                    <i class="bi bi-chevron-down me-1"></i>Show
                                </button>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="project-detail-row d-none" id="dr-<?= e($row['designer']) ?>">
                            <td colspan="6" class="p-0">
                                <div class="p-3 bg-light border-top">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Client</th>
                                                <th>Event</th>
                                                <th>Status</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($row['projects'] as $proj): ?>
                                            <tr>
                                                <td><?= e($proj['client_name']) ?></td>
                                                <td><?= e($proj['event_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $sc = ['Pending'=>'badge-status-pending','Ongoing'=>'badge-status-ongoing','Completed'=>'badge-status-completed'][$proj['status']] ?? 'badge-status-pending';
                                                    ?>
                                                    <span class="badge-status <?= $sc ?>"><?= e($proj['status']) ?></span>
                                                </td>
                                                <td><?= e($proj['end_date']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sales User Report -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge text-primary"></i>
                    <h5 class="mb-0 fw-bold">Sales User Report</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sales User</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Ongoing</th>
                            <th class="text-center">Pending</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salesUserReport)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No data available.</td></tr>
                        <?php else: foreach ($salesUserReport as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['sales_user']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $row['total'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-ongoing"><?= $row['ongoing'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-pending"><?= $row['pending'] ?></span></td>
                            <td class="text-center"><span class="badge-status badge-status-completed"><?= $row['completed'] ?></span></td>
                            <td class="text-center">
                                <?php if ($row['total'] > 0): ?>
                                <button class="btn btn-sm btn-outline-primary btn-toggle-projects"
                                        data-projects="<?= e(json_encode($row['projects'])) ?>"
                                        data-target="sr-<?= e($row['sales_user']) ?>">
                                    <i class="bi bi-chevron-down me-1"></i>Show
                                </button>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="project-detail-row d-none" id="sr-<?= e($row['sales_user']) ?>">
                            <td colspan="6" class="p-0">
                                <div class="p-3 bg-light border-top">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Client</th>
                                                <th>Event</th>
                                                <th>Status</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($row['projects'] as $proj): ?>
                                            <tr>
                                                <td><?= e($proj['client_name']) ?></td>
                                                <td><?= e($proj['event_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $sc = ['Pending'=>'badge-status-pending','Ongoing'=>'badge-status-ongoing','Completed'=>'badge-status-completed'][$proj['status']] ?? 'badge-status-pending';
                                                    ?>
                                                    <span class="badge-status <?= $sc ?>"><?= e($proj['status']) ?></span>
                                                </td>
                                                <td><?= e($proj['end_date']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
    $(document).on('click', '.btn-toggle-projects', function () {
        const rowId = $(this).data('target');
        const $row = $('#' + CSS.escape(rowId));
        const isVisible = !$row.hasClass('d-none');
        $row.toggleClass('d-none', isVisible);
        $(this).html(isVisible
            ? '<i class="bi bi-chevron-down me-1"></i>Show'
            : '<i class="bi bi-chevron-up me-1"></i>Hide'
        );
    });

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
