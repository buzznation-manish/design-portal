/**
 * Design Portal – Client View JavaScript
 * Read-only project table with search, sort, pagination, filters
 */

$(function () {

  /* ── State ─────────────────────────────────────────────── */
  let currentPage      = 1;
  let currentSort      = 'id';
  let currentSortDir   = 'DESC';
  let currentSearch    = '';
  let debounceTimer    = null;
  let filterClient     = '';
  let filterEvent      = '';
  let filterAssignedBy = '';
  let filterDesigner   = '';
  let filterDays       = '';

  /* ── Load Filter Options ───────────────────────────────── */
  function loadFilterOptions() {
    $.ajax({
      url: '/ajax/get_filter_options.php',
      method: 'GET',
      dataType: 'json',
      success: function (res) {
        if (!res.success) return;
        var $fa = $('#filterAssignedBy');
        var $fd = $('#filterDesigner');
        $fa.find('option:not(:first)').remove();
        $fd.find('option:not(:first)').remove();
        (res.sales_users || []).forEach(function (name) {
          $fa.append('<option value="' + escHtml(name) + '">' + escHtml(name) + '</option>');
        });
        (res.designers || []).forEach(function (name) {
          $fd.append('<option value="' + escHtml(name) + '">' + escHtml(name) + '</option>');
        });
      }
    });
  }

  /* ── Load Projects ─────────────────────────────────────── */
  function loadProjects(page) {
    currentPage = page || 1;

    $.ajax({
      url: '/ajax/get_projects.php',
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
          showError(res.message || 'Failed to load projects.');
          return;
        }
        renderTable(res.projects);
        renderPagination(res.total, res.pages, res.current_page);
        updateInfoText(res.total, res.current_page, res.projects.length);
        updateSortIcons();
      },
      error: function () {
        showError('Network error while loading projects.');
      }
    });
  }

  /* ── Render Table ──────────────────────────────────────── */
  function renderTable(projects) {
    const tbody = $('#projectTableBody');
    tbody.empty();

    if (!projects || projects.length === 0) {
      tbody.html(
        '<tr><td colspan="7" class="text-center text-muted py-5">' +
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
      '<td><span class="badge-status ' + badgeClass + '">' +
        '<i class="bi ' + badgeIcon + '"></i> ' + escHtml(status) +
      '</span></td>' +
      '<td>' + daysHtml + '</td>'
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
    if (page && page !== currentPage) loadProjects(page);
  });

  /* ── Helpers ───────────────────────────────────────────── */
  function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function showError(msg) {
    $('#projectTableBody').html(
      '<tr><td colspan="7" class="text-center text-danger py-4">' +
      '<i class="bi bi-exclamation-triangle me-1"></i>' + escHtml(msg) + '</td></tr>'
    );
  }

  /* ── Init ──────────────────────────────────────────────── */
  loadFilterOptions();
  loadProjects(1);

});
