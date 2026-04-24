/**
 * Design Portal – Admin Panel JavaScript
 * Handles AJAX project management, search, sort, pagination, modals, toasts, filters
 */

$(function () {

  /* ── State ─────────────────────────────────────────────── */
  let currentPage      = 1;
  let currentSort      = 'id';
  let currentSortDir   = 'DESC';
  let currentSearch    = '';
  let debounceTimer    = null;
  let deleteTargetId   = null;
  let filterClient     = '';
  let filterEvent      = '';
  let filterAssignedBy = '';
  let filterDesigner   = '';
  let filterDays       = '';

  // Cached option lists
  let designerOptions  = [];
  let salesUserOptions = [];

  function csrfToken() {
    return $('#csrfTokenVal').val();
  }

  /* ── Load Filter Options ───────────────────────────────── */
  function loadFilterOptions() {
    $.ajax({
      url: '/ajax/get_filter_options.php',
      method: 'GET',
      dataType: 'json',
      success: function (res) {
        if (!res.success) return;
        designerOptions  = res.designers  || [];
        salesUserOptions = res.sales_users || [];

        // Populate filter selects
        var $fa = $('#filterAssignedBy');
        var $fd = $('#filterDesigner');
        $fa.find('option:not(:first)').remove();
        $fd.find('option:not(:first)').remove();
        salesUserOptions.forEach(function (name) {
          $fa.append('<option value="' + escHtml(name) + '">' + escHtml(name) + '</option>');
        });
        designerOptions.forEach(function (name) {
          $fd.append('<option value="' + escHtml(name) + '">' + escHtml(name) + '</option>');
        });

        // Populate modal selects
        populateModalSelects();
      }
    });
  }

  function populateModalSelects() {
    // Sales user selects in modals
    var selectors = ['#addAssignedBy', '#editAssignedBy'];
    selectors.forEach(function (sel) {
      var $sel = $(sel);
      var cur  = $sel.val();
      $sel.find('option:not(:first)').remove();
      salesUserOptions.forEach(function (name) {
        $sel.append('<option value="' + escHtml(name) + '">' + escHtml(name) + '</option>');
      });
      if (cur) $sel.val(cur);
    });
  }

  /* ── Load Projects ─────────────────────────────────────── */
  function loadProjects(page) {
    currentPage = page || 1;

    $.ajax({
      url: '/admin/ajax/get_projects.php',
      method: 'GET',
      data: {
        search:             currentSearch,
        sort_col:           currentSort,
        sort_dir:           currentSortDir,
        page:               currentPage,
        per_page:           10,
        filter_client:      filterClient,
        filter_event:       filterEvent,
        filter_assigned_by: filterAssignedBy,
        filter_designer:    filterDesigner,
        filter_days:        filterDays
      },
      dataType: 'json',
      success: function (res) {
        if (!res.success) {
          showToast(res.message || 'Failed to load projects.', 'danger');
          return;
        }
        renderTable(res.projects);
        renderPagination(res.total, res.pages, res.current_page);
        updateInfoText(res.total, res.current_page, res.projects.length);
        updateSortIcons();
      },
      error: function () {
        showToast('Network error while loading projects.', 'danger');
      }
    });
  }

  /* ── Render Table ──────────────────────────────────────── */
  function renderTable(projects) {
    const tbody = $('#projectTableBody');
    tbody.empty();

    if (!projects || projects.length === 0) {
      tbody.html(
        '<tr><td colspan="9" class="text-center text-muted py-4">' +
        '<i class="bi bi-inbox fs-3 d-block mb-2"></i>No projects found.</td></tr>'
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

    const tr = $('<tr>')
      .attr('data-id', p.id)
      .toggleClass('row-urgent', isUrgent);

    tr.html(
      '<td class="fw-semibold">' + escHtml(p.client_name) + '</td>' +
      '<td>' + escHtml(p.event_name) + '</td>' +
      '<td style="max-width:180px">' + designers + '</td>' +
      '<td>' + escHtml(p.start_date) + '</td>' +
      '<td>' + escHtml(p.end_date) + '</td>' +
      '<td>' + escHtml(p.assigned_by) + '</td>' +
      '<td><span class="badge-status ' + badgeClass + '">' +
        '<i class="bi ' + badgeIcon + '"></i>' + escHtml(status) +
      '</span></td>' +
      '<td>' + daysHtml + '</td>' +
      '<td class="text-nowrap">' +
        '<button class="btn btn-sm btn-outline-primary me-1 btn-edit" data-id="' + p.id + '" title="Edit">' +
          '<i class="bi bi-pencil"></i>' +
        '</button>' +
        '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + p.id + '" title="Delete">' +
          '<i class="bi bi-trash"></i>' +
        '</button>' +
      '</td>'
    );

    return tr;
  }

  /* ── Pagination ────────────────────────────────────────── */
  function renderPagination(total, pages, cur) {
    const container = $('#paginationContainer');
    container.empty();

    if (pages <= 1) return;

    const ul = $('<ul class="pagination pagination-sm mb-0">');

    ul.append(
      $('<li class="page-item' + (cur === 1 ? ' disabled' : '') + '">')
        .html('<a class="page-link" href="#" data-page="' + (cur - 1) + '"><i class="bi bi-chevron-left"></i></a>')
    );

    let start = Math.max(1, cur - 3);
    let end   = Math.min(pages, start + 6);
    if (end - start < 6) start = Math.max(1, end - 6);

    if (start > 1) {
      ul.append($('<li class="page-item">').html('<a class="page-link" href="#" data-page="1">1</a>'));
      if (start > 2) ul.append('<li class="page-item disabled"><span class="page-link">…</span></li>');
    }

    for (let i = start; i <= end; i++) {
      ul.append(
        $('<li class="page-item' + (i === cur ? ' active' : '') + '">')
          .html('<a class="page-link" href="#" data-page="' + i + '">' + i + '</a>')
      );
    }

    if (end < pages) {
      if (end < pages - 1) ul.append('<li class="page-item disabled"><span class="page-link">…</span></li>');
      ul.append($('<li class="page-item">').html('<a class="page-link" href="#" data-page="' + pages + '">' + pages + '</a>'));
    }

    ul.append(
      $('<li class="page-item' + (cur === pages ? ' disabled' : '') + '">')
        .html('<a class="page-link" href="#" data-page="' + (cur + 1) + '"><i class="bi bi-chevron-right"></i></a>')
    );

    container.append(ul);
  }

  function updateInfoText(total, page, count) {
    const perPage = 10;
    const from    = total === 0 ? 0 : ((page - 1) * perPage) + 1;
    const to      = Math.min(page * perPage, total);
    $('#tableInfoText').text('Showing ' + from + '–' + to + ' of ' + total + ' project' + (total !== 1 ? 's' : ''));
  }

  function updateSortIcons() {
    $('.sortable .sort-icon').removeClass('active').html('<i class="bi bi-arrow-down-up"></i>');
    const th = $('.sortable[data-col="' + currentSort + '"]');
    if (th.length) {
      const icon = currentSortDir === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down';
      th.find('.sort-icon').addClass('active').html('<i class="bi ' + icon + '"></i>');
    }
  }

  /* ── Search ────────────────────────────────────────────── */
  $('#searchInput').on('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      currentSearch = $('#searchInput').val().trim();
      loadProjects(1);
    }, 400);
  });

  /* ── Filter Handlers ───────────────────────────────────── */
  var filterDebounce = null;

  $('#filterClient, #filterEvent').on('input', function () {
    clearTimeout(filterDebounce);
    filterDebounce = setTimeout(function () {
      filterClient = $('#filterClient').val().trim();
      filterEvent  = $('#filterEvent').val().trim();
      loadProjects(1);
    }, 400);
  });

  $('#filterAssignedBy').on('change', function () {
    filterAssignedBy = $(this).val();
    loadProjects(1);
  });

  $('#filterDesigner').on('change', function () {
    filterDesigner = $(this).val();
    loadProjects(1);
  });

  $('#filterDays').on('change', function () {
    filterDays = $(this).val();
    loadProjects(1);
  });

  $('#btnClearFilters').on('click', function () {
    filterClient     = '';
    filterEvent      = '';
    filterAssignedBy = '';
    filterDesigner   = '';
    filterDays       = '';
    $('#filterClient').val('');
    $('#filterEvent').val('');
    $('#filterAssignedBy').val('');
    $('#filterDesigner').val('');
    $('#filterDays').val('');
    loadProjects(1);
  });

  /* ── Sort ──────────────────────────────────────────────── */
  $(document).on('click', '.sortable', function () {
    const col = $(this).data('col');
    if (currentSort === col) {
      currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
    } else {
      currentSort    = col;
      currentSortDir = 'ASC';
    }
    loadProjects(1);
  });

  /* ── Pagination Click ──────────────────────────────────── */
  $(document).on('click', '#paginationContainer .page-link', function (e) {
    e.preventDefault();
    const page = parseInt($(this).data('page'), 10);
    if (page && page !== currentPage) {
      loadProjects(page);
    }
  });

  /* ── Add Project Modal ─────────────────────────────────── */
  function showAddModal() {
    $('#addProjectForm')[0].reset();
    $('#addDesignerList').html(designerRowHtml(''));
    $('#addModalError').addClass('d-none').text('');
    populateModalSelects();
    const modal = new bootstrap.Modal(document.getElementById('addProjectModal'));
    modal.show();
  }

  $('#btnAddProject, #btnAddProjectTop').on('click', showAddModal);

  // Dynamic designer rows (Add form)
  $(document).on('click', '#btnAddDesigner', function () {
    $('#addDesignerList').append(designerRowHtml(''));
  });

  $(document).on('click', '#addDesignerList .btn-remove-designer', function () {
    if ($('#addDesignerList .designer-row').length > 1) {
      $(this).closest('.designer-row').remove();
    }
  });

  // Save Add
  $('#btnSaveAdd').on('click', function () {
    const formData = buildProjectFormData('#addProjectForm', '#addDesignerList', csrfToken());
    const errors   = clientValidate(formData);

    if (errors.length) {
      $('#addModalError').removeClass('d-none').html(errors.join('<br>'));
      return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');

    $.ajax({
      url: '/admin/ajax/add_project.php',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          bootstrap.Modal.getInstance(document.getElementById('addProjectModal')).hide();
          loadProjects(1);
          showToast('Project added successfully!', 'success');
        } else {
          const msg = Array.isArray(res.errors) ? res.errors.join('<br>') : (res.message || 'Failed to add project.');
          $('#addModalError').removeClass('d-none').html(msg);
        }
      },
      error: function () {
        $('#addModalError').removeClass('d-none').text('Network error. Please try again.');
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save Project');
      }
    });
  });

  /* ── Edit Project Modal ────────────────────────────────── */
  $(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $('#editModalError').addClass('d-none').text('');
    populateModalSelects();

    $.ajax({
      url: '/admin/ajax/get_project.php',
      method: 'GET',
      data: { id: id },
      dataType: 'json',
      success: function (res) {
        if (!res.success || !res.project) {
          showToast('Failed to load project data.', 'danger');
          return;
        }
        const p = res.project;
        $('#editProjectId').val(p.id);
        $('#editClientName').val(p.client_name);
        $('#editEventName').val(p.event_name);
        $('#editStartDate').val(p.start_date);
        $('#editEndDate').val(p.end_date);
        $('#editAssignedBy').val(p.assigned_by);
        $('#editStatus').val(p.status);

        // Populate designers
        const dList = $('#editDesignerList').empty();
        const designers = Array.isArray(p.designers) ? p.designers : [];
        if (designers.length === 0) {
          dList.append(designerRowHtml(''));
        } else {
          designers.forEach(function (name) {
            dList.append(designerRowHtml(name));
          });
        }

        const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
        modal.show();
      },
      error: function () {
        showToast('Network error loading project.', 'danger');
      }
    });
  });

  // Dynamic designer rows (Edit form)
  $(document).on('click', '#btnEditDesigner', function () {
    $('#editDesignerList').append(designerRowHtml(''));
  });

  $(document).on('click', '#editDesignerList .btn-remove-designer', function () {
    if ($('#editDesignerList .designer-row').length > 1) {
      $(this).closest('.designer-row').remove();
    }
  });

  // Save Edit
  $('#btnSaveEdit').on('click', function () {
    const formData = buildProjectFormData('#editProjectForm', '#editDesignerList', csrfToken());
    formData.append('id', $('#editProjectId').val());
    const errors = clientValidate(formData);

    if (errors.length) {
      $('#editModalError').removeClass('d-none').html(errors.join('<br>'));
      return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');

    $.ajax({
      url: '/admin/ajax/edit_project.php',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
          loadProjects(currentPage);
          showToast('Project updated successfully!', 'success');
        } else {
          const msg = Array.isArray(res.errors) ? res.errors.join('<br>') : (res.message || 'Failed to update project.');
          $('#editModalError').removeClass('d-none').html(msg);
        }
      },
      error: function () {
        $('#editModalError').removeClass('d-none').text('Network error. Please try again.');
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save Changes');
      }
    });
  });

  /* ── Delete Project ────────────────────────────────────── */
  $(document).on('click', '.btn-delete', function () {
    deleteTargetId = $(this).data('id');
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
  });

  $('#btnConfirmDelete').on('click', function () {
    if (!deleteTargetId) return;

    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting…');

    $.ajax({
      url: '/admin/ajax/delete_project.php',
      method: 'POST',
      data: { id: deleteTargetId, csrf_token: csrfToken() },
      dataType: 'json',
      success: function (res) {
        bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
        deleteTargetId = null;
        if (res.success) {
          loadProjects(currentPage);
          showToast('Project deleted.', 'success');
        } else {
          showToast(res.message || 'Failed to delete project.', 'danger');
        }
      },
      error: function () {
        showToast('Network error. Please try again.', 'danger');
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Delete');
      }
    });
  });

  /* ── Sidebar Mobile Toggle ─────────────────────────────── */
  $('#sidebarToggle').on('click', function () {
    $('.sidebar').toggleClass('open');
    $('.sidebar-overlay').toggleClass('visible');
  });

  $(document).on('click', '.sidebar-overlay', function () {
    $('.sidebar').removeClass('open');
    $('.sidebar-overlay').removeClass('visible');
  });

  /* ── Helpers ───────────────────────────────────────────── */
  function designerRowHtml(selectedValue) {
    var options = '<option value="">— Select Designer —</option>';
    designerOptions.forEach(function (name) {
      var sel = (name === selectedValue) ? ' selected' : '';
      options += '<option value="' + escHtml(name) + '"' + sel + '>' + escHtml(name) + '</option>';
    });
    return '<div class="designer-row d-flex gap-2 mb-2">' +
      '<select class="form-select form-select-sm" name="designers[]">' + options + '</select>' +
      '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-designer flex-shrink-0" title="Remove">' +
        '<i class="bi bi-x-lg"></i>' +
      '</button>' +
    '</div>';
  }

  function buildProjectFormData(formSelector, designerSelector, csrf) {
    const fd = new FormData();
    fd.append('csrf_token',  csrf);
    fd.append('client_name', $(formSelector + ' [name="client_name"]').val() || '');
    fd.append('event_name',  $(formSelector + ' [name="event_name"]').val() || '');
    fd.append('start_date',  $(formSelector + ' [name="start_date"]').val() || '');
    fd.append('end_date',    $(formSelector + ' [name="end_date"]').val() || '');
    fd.append('assigned_by', $(formSelector + ' [name="assigned_by"]').val() || '');
    fd.append('status',      $(formSelector + ' [name="status"]').val() || '');

    $(designerSelector + ' select[name="designers[]"]').each(function () {
      const v = $(this).val();
      if (v && v.trim()) fd.append('designers[]', v.trim());
    });

    return fd;
  }

  function clientValidate(formData) {
    const errors = [];
    if (!formData.get('client_name') || !formData.get('client_name').trim())
      errors.push('Client name is required.');
    if (!formData.get('event_name') || !formData.get('event_name').trim())
      errors.push('Event name is required.');
    if (!formData.get('start_date'))
      errors.push('Start date is required.');
    if (!formData.get('end_date'))
      errors.push('End date is required.');
    if (formData.get('start_date') && formData.get('end_date') &&
        formData.get('end_date') < formData.get('start_date'))
      errors.push('End date must be on or after start date.');
    if (!formData.get('assigned_by') || !formData.get('assigned_by').trim())
      errors.push('Assigned by is required.');
    return errors;
  }

  function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /* ── Toast Notifications ───────────────────────────────── */
  function showToast(message, type) {
    type = type || 'info';
    const icons = {
      success: 'bi-check-circle-fill text-success',
      danger:  'bi-exclamation-circle-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      info:    'bi-info-circle-fill text-info'
    };
    const icon = icons[type] || icons.info;

    const id = 'toast-' + Date.now();
    const html =
      '<div id="' + id + '" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive">' +
        '<div class="d-flex">' +
          '<div class="toast-body d-flex align-items-center gap-2">' +
            '<i class="bi ' + icon + ' fs-5"></i>' +
            '<span>' + escHtml(message) + '</span>' +
          '</div>' +
          '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>' +
        '</div>' +
      '</div>';

    const $toast = $(html).appendTo('#toastContainer');
    const bsToast = new bootstrap.Toast($toast[0], { delay: 4000 });
    bsToast.show();
    $toast[0].addEventListener('hidden.bs.toast', function () { $toast.remove(); });
  }

  /* ── Init ──────────────────────────────────────────────── */
  loadFilterOptions();
  loadProjects(1);

});
