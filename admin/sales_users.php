<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminAuth();

$csrfToken   = generateCSRFToken();
$salesUsers  = getSalesUsers();
$username    = e($_SESSION['admin_username'] ?? 'Admin');
$fullName    = e($_SESSION['admin_full_name'] ?? 'Admin');
$_activePage = 'sales_users.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Users – Design Portal Admin</title>
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
            <h1 class="topbar-title mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Sales Users</h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge"><i class="bi bi-person-circle"></i><?= $fullName ?></span>
            <a href="/admin/logout.php" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Log out?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="content-area">
        <div class="row g-4">
            <!-- Add Sales User Form -->
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="table-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus text-primary"></i>
                            <h5 class="mb-0 fw-bold">Add Sales User</h5>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="alert alert-danger d-none" id="addSalesUserError"></div>
                        <div class="alert alert-success d-none" id="addSalesUserSuccess"></div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sales User Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newSalesUserName" maxlength="100" placeholder="e.g. Sarah Johnson">
                        </div>
                        <button type="button" class="btn btn-primary w-100" id="btnAddSalesUser">
                            <i class="bi bi-plus-lg me-1"></i>Add Sales User
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sales Users List -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge text-primary"></i>
                            <h5 class="mb-0 fw-bold">All Sales Users</h5>
                        </div>
                        <span class="badge bg-primary" id="salesUserCount"><?= count($salesUsers) ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="salesUserTableBody">
                                <?php if (empty($salesUsers)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No sales users yet.</td></tr>
                                <?php else: foreach ($salesUsers as $i => $u): ?>
                                <tr data-id="<?= (int)$u['id'] ?>">
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td class="fw-semibold"><?= e($u['name']) ?></td>
                                    <td class="text-muted small"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-sales-user" data-id="<?= (int)$u['id'] ?>" data-name="<?= e($u['name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrfTokenVal" value="<?= e($csrfToken) ?>">
<div id="toastContainer"></div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSalesUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Sales User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteSalesUserName"></strong>? This will not affect existing projects.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteSalesUser">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
    let deleteTargetId = null;
    let rowCount = parseInt($('#salesUserCount').text(), 10) || 0;

    function csrfToken() { return $('#csrfTokenVal').val(); }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function showToast(message, type) {
        const icons = { success:'bi-check-circle-fill text-success', danger:'bi-exclamation-circle-fill text-danger' };
        const icon = icons[type] || icons.danger;
        const id = 'toast-' + Date.now();
        const html = '<div id="' + id + '" class="toast align-items-center border-0 shadow" role="alert">' +
            '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">' +
            '<i class="bi ' + icon + ' fs-5"></i><span>' + escHtml(message) + '</span></div>' +
            '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
        const $t = $(html).appendTo('#toastContainer');
        new bootstrap.Toast($t[0], { delay: 4000 }).show();
        $t[0].addEventListener('hidden.bs.toast', function () { $t.remove(); });
    }

    $('#btnAddSalesUser').on('click', function () {
        const name = $('#newSalesUserName').val().trim();
        $('#addSalesUserError').addClass('d-none').text('');
        $('#addSalesUserSuccess').addClass('d-none').text('');
        if (!name) {
            $('#addSalesUserError').removeClass('d-none').text('Sales user name is required.');
            return;
        }
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Adding…');
        $.post('/admin/ajax/add_sales_user.php', { name: name, csrf_token: csrfToken() }, function (res) {
            if (res.success) {
                rowCount++;
                $('#salesUserCount').text(rowCount);
                const today = new Date().toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
                const tbody = $('#salesUserTableBody');
                if (tbody.find('td[colspan]').length) tbody.empty();
                tbody.append(
                    '<tr data-id="' + res.id + '">' +
                    '<td class="text-muted">' + rowCount + '</td>' +
                    '<td class="fw-semibold">' + escHtml(res.name) + '</td>' +
                    '<td class="text-muted small">' + today + '</td>' +
                    '<td><button class="btn btn-sm btn-outline-danger btn-delete-sales-user" data-id="' + res.id + '" data-name="' + escHtml(res.name) + '">' +
                    '<i class="bi bi-trash"></i></button></td></tr>'
                );
                $('#newSalesUserName').val('');
                $('#addSalesUserSuccess').removeClass('d-none').text('Sales user "' + res.name + '" added successfully!');
                showToast('Sales user added!', 'success');
            } else {
                $('#addSalesUserError').removeClass('d-none').text(res.message || 'Failed to add sales user.');
            }
        }, 'json').fail(function () {
            $('#addSalesUserError').removeClass('d-none').text('Network error. Please try again.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i>Add Sales User');
        });
    });

    $('#newSalesUserName').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btnAddSalesUser').trigger('click');
    });

    $(document).on('click', '.btn-delete-sales-user', function () {
        deleteTargetId = $(this).data('id');
        $('#deleteSalesUserName').text($(this).data('name'));
        new bootstrap.Modal(document.getElementById('deleteSalesUserModal')).show();
    });

    $('#btnConfirmDeleteSalesUser').on('click', function () {
        if (!deleteTargetId) return;
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting…');
        $.post('/admin/ajax/delete_sales_user.php', { id: deleteTargetId, csrf_token: csrfToken() }, function (res) {
            bootstrap.Modal.getInstance(document.getElementById('deleteSalesUserModal')).hide();
            if (res.success) {
                $('tr[data-id="' + deleteTargetId + '"]').remove();
                rowCount = Math.max(0, rowCount - 1);
                $('#salesUserCount').text(rowCount);
                if ($('#salesUserTableBody tr').length === 0) {
                    $('#salesUserTableBody').html('<tr><td colspan="4" class="text-center text-muted py-4">No sales users yet.</td></tr>');
                }
                showToast('Sales user deleted.', 'success');
            } else {
                showToast(res.message || 'Failed to delete.', 'danger');
            }
            deleteTargetId = null;
        }, 'json').fail(function () {
            showToast('Network error.', 'danger');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Delete');
        });
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
