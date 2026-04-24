<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminAuth();

$csrfToken  = generateCSRFToken();
$designers  = getDesigners();
$username   = e($_SESSION['admin_username'] ?? 'Admin');
$fullName   = e($_SESSION['admin_full_name'] ?? 'Admin');
$_activePage = 'designers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designers – Design Portal Admin</title>
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
            <h1 class="topbar-title mb-0"><i class="bi bi-people me-2 text-primary"></i>Designers</h1>
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
            <!-- Add Designer Form -->
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="table-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus text-primary"></i>
                            <h5 class="mb-0 fw-bold">Add Designer</h5>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="alert alert-danger d-none" id="addDesignerError"></div>
                        <div class="alert alert-success d-none" id="addDesignerSuccess"></div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Designer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newDesignerName" maxlength="100" placeholder="e.g. Alice Wong">
                        </div>
                        <button type="button" class="btn btn-primary w-100" id="btnAddDesigner">
                            <i class="bi bi-plus-lg me-1"></i>Add Designer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Designers List -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people text-primary"></i>
                            <h5 class="mb-0 fw-bold">All Designers</h5>
                        </div>
                        <span class="badge bg-primary" id="designerCount"><?= count($designers) ?></span>
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
                            <tbody id="designerTableBody">
                                <?php if (empty($designers)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No designers yet.</td></tr>
                                <?php else: foreach ($designers as $i => $d): ?>
                                <tr data-id="<?= (int)$d['id'] ?>">
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td class="fw-semibold"><?= e($d['name']) ?></td>
                                    <td class="text-muted small"><?= e(date('M j, Y', strtotime($d['created_at']))) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-designer" data-id="<?= (int)$d['id'] ?>" data-name="<?= e($d['name']) ?>">
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
<div class="modal fade" id="deleteDesignerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Designer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteDesignerName"></strong>? This will not affect existing projects.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteDesigner">
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
    let rowCount = parseInt($('#designerCount').text(), 10) || 0;

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

    $('#btnAddDesigner').on('click', function () {
        const name = $('#newDesignerName').val().trim();
        $('#addDesignerError').addClass('d-none').text('');
        $('#addDesignerSuccess').addClass('d-none').text('');
        if (!name) {
            $('#addDesignerError').removeClass('d-none').text('Designer name is required.');
            return;
        }
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Adding…');
        $.post('/admin/ajax/add_designer.php', { name: name, csrf_token: csrfToken() }, function (res) {
            if (res.success) {
                rowCount++;
                $('#designerCount').text(rowCount);
                const today = new Date().toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
                const tbody = $('#designerTableBody');
                if (tbody.find('td[colspan]').length) tbody.empty();
                tbody.append(
                    '<tr data-id="' + res.id + '">' +
                    '<td class="text-muted">' + rowCount + '</td>' +
                    '<td class="fw-semibold">' + escHtml(res.name) + '</td>' +
                    '<td class="text-muted small">' + today + '</td>' +
                    '<td><button class="btn btn-sm btn-outline-danger btn-delete-designer" data-id="' + res.id + '" data-name="' + escHtml(res.name) + '">' +
                    '<i class="bi bi-trash"></i></button></td></tr>'
                );
                $('#newDesignerName').val('');
                $('#addDesignerSuccess').removeClass('d-none').text('Designer "' + res.name + '" added successfully!');
                showToast('Designer added!', 'success');
            } else {
                $('#addDesignerError').removeClass('d-none').text(res.message || 'Failed to add designer.');
            }
        }, 'json').fail(function () {
            $('#addDesignerError').removeClass('d-none').text('Network error. Please try again.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i>Add Designer');
        });
    });

    $('#newDesignerName').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btnAddDesigner').trigger('click');
    });

    $(document).on('click', '.btn-delete-designer', function () {
        deleteTargetId = $(this).data('id');
        $('#deleteDesignerName').text($(this).data('name'));
        new bootstrap.Modal(document.getElementById('deleteDesignerModal')).show();
    });

    $('#btnConfirmDeleteDesigner').on('click', function () {
        if (!deleteTargetId) return;
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting…');
        $.post('/admin/ajax/delete_designer.php', { id: deleteTargetId, csrf_token: csrfToken() }, function (res) {
            bootstrap.Modal.getInstance(document.getElementById('deleteDesignerModal')).hide();
            if (res.success) {
                $('tr[data-id="' + deleteTargetId + '"]').remove();
                rowCount = Math.max(0, rowCount - 1);
                $('#designerCount').text(rowCount);
                if ($('#designerTableBody tr').length === 0) {
                    $('#designerTableBody').html('<tr><td colspan="4" class="text-center text-muted py-4">No designers yet.</td></tr>');
                }
                showToast('Designer deleted.', 'success');
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
