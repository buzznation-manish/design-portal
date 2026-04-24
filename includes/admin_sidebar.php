<?php
// $_activePage must be set before including this file
$_activePage = $_activePage ?? 'index.php';
$_adminUsername = e($_SESSION['admin_username'] ?? 'A');
$_adminFullName = e($_SESSION['admin_full_name'] ?? 'Admin');
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-palette2 fs-3" style="color:#818cf8"></i>
        <div>
            <div class="sidebar-brand-text">Design Portal</div>
            <div class="sidebar-brand-sub">Project Management</div>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li><span class="sidebar-section-label">Main</span></li>
        <li>
            <a href="/admin/index.php" class="sidebar-link <?= $_activePage === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/admin/designers.php" class="sidebar-link <?= $_activePage === 'designers.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Designers
            </a>
        </li>
        <li>
            <a href="/admin/sales_users.php" class="sidebar-link <?= $_activePage === 'sales_users.php' ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i> Sales Users
            </a>
        </li>
        <li>
            <a href="/admin/reports.php" class="sidebar-link <?= $_activePage === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>
        </li>
        <li><span class="sidebar-section-label">Navigation</span></li>
        <li>
            <a href="/" class="sidebar-link" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-globe"></i> Client View
                <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.7rem;opacity:.5"></i>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="sidebar-username"><?= $_adminFullName ?></div>
                <div style="font-size:.7rem;color:rgba(255,255,255,.4)">@<?= $_adminUsername ?></div>
            </div>
        </div>
        <a href="/admin/logout.php" class="btn btn-sm btn-outline-light w-100"
           onclick="return confirm('Log out?')">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
    </div>
</nav>
