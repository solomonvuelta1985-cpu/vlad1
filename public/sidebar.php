<?php
// Sidebar Navigation Component
// Requires auth.php to be loaded for user info
?>
<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-header-content">
        <div>
            <h4><i class="fas fa-traffic-light"></i> Traffic System</h4>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <!-- User Profile Dropdown (Mobile) -->
            <div class="user-profile-dropdown">
                <button class="user-profile-btn" id="mobileUserProfileBtn" type="button">
                    <div class="user-avatar">
                        <?php
                        $full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
                        $initials = '';
                        $name_parts = explode(' ', $full_name);
                        if (count($name_parts) >= 2) {
                            $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($full_name, 0, 2));
                        }
                        echo htmlspecialchars($initials);
                        ?>
                    </div>
                </button>

                <div class="user-dropdown-menu" id="mobileUserDropdownMenu">
                    <div class="dropdown-header">
                        <div class="dropdown-user-info">
                            <div class="user-avatar large">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                            <div>
                                <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
                                <div class="dropdown-user-email"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                                <span class="dropdown-user-badge"><?php echo htmlspecialchars(strtoupper($_SESSION['user_role'] ?? 'USER')); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/vlad/public/logout.php" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            <button type="button" id="mobileSidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-traffic-light"></i> <span>Traffic System</span></h4>
    </div>

    <ul class="sidebar-menu">
        <!-- Main Section -->
        <li class="sidebar-heading">Overview</li>
        <li>
            <a href="/vlad/public/index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>" title="Dashboard">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>

        <!-- Citations Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Citation Management</li>
        <?php if (function_exists('can_create_citation') && can_create_citation()): ?>
        <li>
            <a href="/vlad/public/index2.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index2.php') ? 'active' : ''; ?>" title="Create Citation">
                <i class="fas fa-plus-circle"></i> <span>Create Citation</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="/vlad/public/citations.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'citations.php') ? 'active' : ''; ?>" title="All Citations">
                <i class="fas fa-list-alt"></i> <span>All Citations</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/search.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'search.php') ? 'active' : ''; ?>" title="Search Citations">
                <i class="fas fa-search"></i> <span>Search</span>
            </a>
        </li>

        <!-- Payments Section -->
        <?php if (function_exists('can_process_payment') && can_process_payment()): ?>
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Payment Processing</li>
        <li>
            <a href="/vlad/public/process_payment.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'process_payment.php') ? 'active' : ''; ?>" title="Process Payment">
                <i class="fas fa-cash-register"></i> <span>Process Payment</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/payments.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'payments.php') ? 'active' : ''; ?>" title="Payment History">
                <i class="fas fa-history"></i> <span>History</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/pending_print_payments.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'pending_print_payments.php') ? 'active' : ''; ?>" title="Print Queue">
                <i class="fas fa-clock"></i> <span>Print Queue</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Management Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Team</li>
        <?php if (function_exists('has_role') && has_role(['admin', 'enforcer'])): ?>
        <li>
            <a href="/vlad/public/officers.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'officers.php') ? 'active' : ''; ?>" title="Officers">
                <i class="fas fa-user-shield"></i> <span>Officers</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (function_exists('is_admin') && is_admin()): ?>
        <!-- Admin Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">System Administration</li>
        <li>
            <a href="/vlad/admin/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : ''; ?>" title="Admin Overview">
                <i class="fas fa-chart-line"></i> <span>Overview</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/violations.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'violations.php') ? 'active' : ''; ?>" title="Violations">
                <i class="fas fa-exclamation-triangle"></i> <span>Violations</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'users.php') ? 'active' : ''; ?>" title="User Management">
                <i class="fas fa-users-cog"></i> <span>Users</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'active' : ''; ?>" title="Reports & Analytics">
                <i class="fas fa-chart-bar"></i> <span>Reports</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/driver_duplicates.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'driver_duplicates.php') ? 'active' : ''; ?>" title="Duplicate Drivers">
                <i class="fas fa-user-friends"></i> <span>Duplicates</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/database_diagnostics.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'database_diagnostics.php') ? 'active' : ''; ?>" title="Database Diagnostics">
                <i class="fas fa-database"></i> <span>Diagnostics</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/audit_log.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'audit_log.php') ? 'active' : ''; ?>" title="Audit Log">
                <i class="fas fa-clipboard-list"></i> <span>Audit Log</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

</nav>

<!-- Top Navigation Bar (Desktop) -->
<div class="top-navbar" id="topNavbar">
    <button type="button" id="sidebarCollapse" title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="top-navbar-info">
        <span class="welcome-text">
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
        </span>
    </div>

    <!-- User Profile Dropdown -->
    <div class="user-profile-dropdown">
        <button class="user-profile-btn" id="userProfileBtn" type="button">
            <div class="user-avatar">
                <?php
                $full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
                $initials = '';
                $name_parts = explode(' ', $full_name);
                if (count($name_parts) >= 2) {
                    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($full_name, 0, 2));
                }
                echo htmlspecialchars($initials);
                ?>
            </div>
            <div class="user-profile-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <span class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'User')); ?></span>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
        </button>

        <div class="user-dropdown-menu" id="userDropdownMenu">
            <div class="dropdown-header">
                <div class="dropdown-user-info">
                    <div class="user-avatar large">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <div>
                        <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
                        <div class="dropdown-user-email"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                        <span class="dropdown-user-badge"><?php echo htmlspecialchars(strtoupper($_SESSION['user_role'] ?? 'USER')); ?></span>
                    </div>
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="/vlad/public/logout.php" class="dropdown-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<style>
:root {
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 72px;
    --primary-color: #1e40af;
    --primary-hover: #1e3a8a;
    --sidebar-bg: #0f172a;
    --sidebar-item-hover: #1e293b;
    --sidebar-item-active: #334155;
    --text-primary: #ffffff;
    --text-secondary: #94a3b8;
    --accent-color: #3b82f6;
    --border-color: #1e293b;
}

/* Mobile Header */
.mobile-header {
    display: none;
    background: var(--sidebar-bg);
    color: var(--text-primary);
    padding: 16px 20px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1100;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid var(--border-color);
}

.mobile-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    letter-spacing: -0.01em;
}

.mobile-header h4 i {
    color: var(--accent-color);
    margin-right: 10px;
}

#mobileSidebarToggle {
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background 0.2s ease;
}

#mobileSidebarToggle:hover {
    background: var(--sidebar-item-hover);
}

/* Sidebar Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
    backdrop-filter: blur(2px);
}

.sidebar-overlay.active {
    display: block;
}

/* Top Navigation Bar */
.top-navbar {
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    height: 64px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    padding: 0 24px;
    z-index: 100;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed .top-navbar {
    left: var(--sidebar-collapsed-width);
}

#sidebarCollapse {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #374151;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

#sidebarCollapse:hover {
    background: #f3f4f6;
    color: var(--primary-color);
}

.top-navbar-info {
    margin-left: 16px;
}

.welcome-text {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

/* User Profile Dropdown */
.user-profile-dropdown {
    margin-left: auto;
    position: relative;
}

.user-profile-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 12px 6px 6px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
}

.user-profile-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-profile-btn:active,
.user-profile-btn.active {
    background: #f3f4f6;
    border-color: var(--primary-color);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.user-avatar.large {
    width: 48px;
    height: 48px;
    font-size: 16px;
}

.user-profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    min-width: 0;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.user-role {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.3;
    text-transform: capitalize;
}

.dropdown-arrow {
    color: #9ca3af;
    font-size: 12px;
    transition: transform 0.2s ease;
}

.user-profile-btn.active .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 280px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
}

.user-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 20px;
}

.dropdown-user-info {
    display: flex;
    gap: 12px;
    align-items: center;
}

.dropdown-user-name {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}

.dropdown-user-email {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
}

.dropdown-user-badge {
    display: inline-block;
    padding: 4px 10px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    letter-spacing: 0.5px;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.15s ease;
    border-radius: 0 0 12px 12px;
}

.dropdown-item:hover {
    background: #f9fafb;
    color: #111827;
}

.dropdown-item.logout-item {
    color: #dc2626;
}

.dropdown-item.logout-item:hover {
    background: #fef2f2;
    color: #b91c1c;
}

.dropdown-item i {
    width: 18px;
    text-align: center;
    font-size: 16px;
}

/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--text-primary);
    padding: 0;
    z-index: 1000;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.sidebar-collapsed .sidebar {
    width: var(--sidebar-collapsed-width);
}

.sidebar-header {
    padding: 20px;
    background: var(--sidebar-bg);
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
    overflow: hidden;
    min-height: 64px;
    display: flex;
    align-items: center;
}

.sidebar-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    letter-spacing: -0.01em;
}

.sidebar-header h4 i {
    min-width: 32px;
    font-size: 1.25rem;
    color: var(--accent-color);
}

.sidebar-collapsed .sidebar-header h4 span {
    display: none;
}

.sidebar-menu {
    list-style: none;
    padding: 12px 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Custom Scrollbar */
.sidebar-menu::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: #334155;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 11px 16px;
    margin: 2px 12px;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.2s ease;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    white-space: nowrap;
    position: relative;
}

.sidebar-menu li a:hover {
    background: var(--sidebar-item-hover);
    color: var(--text-primary);
}

.sidebar-menu li a.active {
    background: var(--sidebar-item-active);
    color: var(--text-primary);
    font-weight: 600;
}

.sidebar-menu li a.active::before {
    content: '';
    position: absolute;
    left: -12px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 24px;
    background: var(--accent-color);
    border-radius: 0 3px 3px 0;
}

.sidebar-menu li a i {
    min-width: 32px;
    font-size: 18px;
    text-align: center;
    color: inherit;
}

.sidebar-menu li a span {
    font-size: 14px;
    transition: opacity 0.3s ease;
}

.sidebar-collapsed .sidebar-menu li a {
    justify-content: center;
    padding: 14px 10px;
    margin: 4px 10px;
}

.sidebar-collapsed .sidebar-menu li a span {
    display: none;
}

.sidebar-collapsed .sidebar-menu li a i {
    margin: 0;
    font-size: 20px;
}

.sidebar-collapsed .sidebar-menu li a.active::before {
    left: -10px;
}

.sidebar-divider {
    border-top: 1px solid var(--border-color);
    margin: 12px 16px;
}

.sidebar-heading {
    padding: 16px 20px 8px;
    font-size: 11px;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 700;
    letter-spacing: 0.8px;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-collapsed .sidebar-heading {
    text-indent: -9999px;
    padding: 8px 0;
    margin: 0;
}

.sidebar-collapsed .sidebar-divider {
    margin: 8px 16px;
}

/* Main Content */
.content {
    margin-left: var(--sidebar-width);
    padding: 24px;
    padding-top: 88px;
    min-height: 100vh;
    background: #f8fafc;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed .content {
    margin-left: var(--sidebar-collapsed-width);
}

/* Tooltips for Collapsed Sidebar */
.sidebar-collapsed .sidebar-menu li {
    position: relative;
}

.sidebar-collapsed .sidebar-menu li a::after {
    content: attr(title);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: #1e293b;
    color: var(--text-primary);
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 1001;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    margin-left: 8px;
}

.sidebar-collapsed .sidebar-menu li a:hover::after {
    opacity: 1;
    visibility: visible;
}

/* Responsive */
@media (max-width: 768px) {
    .mobile-header {
        display: block;
    }

    .top-navbar {
        display: none;
    }

    .sidebar {
        transform: translateX(-100%);
        width: 280px;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .sidebar-collapsed .sidebar {
        width: 280px;
    }

    .content {
        margin-left: 0;
        padding-top: 80px;
    }

    .sidebar-collapsed .content {
        margin-left: 0;
    }

    /* Disable tooltips on mobile */
    .sidebar-collapsed .sidebar-menu li a::after {
        display: none;
    }

    /* Show text on mobile even in collapsed mode */
    .sidebar-collapsed .sidebar-menu li a span,
    .sidebar-collapsed .sidebar-header h4 span,
    .sidebar-collapsed .sidebar-heading {
        display: inline;
        font-size: inherit;
        text-indent: 0;
    }

    .sidebar-collapsed .sidebar-menu li a {
        justify-content: flex-start;
        padding: 11px 16px;
        margin: 2px 12px;
    }

    .sidebar-collapsed .sidebar-menu li a i {
        font-size: 18px;
        margin-right: 0;
        min-width: 32px;
    }

    /* User Profile Dropdown - Mobile adjustments */
    .user-profile-info {
        display: none;
    }

    .dropdown-arrow {
        display: none;
    }

    .user-dropdown-menu {
        min-width: 260px;
        right: -8px;
    }
}

@media print {
    .sidebar,
    .top-navbar,
    .mobile-header,
    .sidebar-overlay {
        display: none !important;
    }

    .content {
        margin-left: 0 !important;
        padding-top: 20px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const body = document.body;

    // Desktop: Toggle sidebar collapse/expand
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');

            // Save state to localStorage
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // Mobile: Toggle sidebar visibility
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            body.classList.toggle('sidebar-open');
        });
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            body.classList.remove('sidebar-open');
        });
    }

    // Close mobile sidebar when clicking a menu link
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                body.classList.remove('sidebar-open');
            }
        });
    });

    // Restore sidebar state on page load
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && window.innerWidth > 768) {
        body.classList.add('sidebar-collapsed');
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Close mobile overlay on desktop
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            body.classList.remove('sidebar-open');
        }
    });

    // User Profile Dropdown Functionality
    const userProfileBtn = document.getElementById('userProfileBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const mobileUserProfileBtn = document.getElementById('mobileUserProfileBtn');
    const mobileUserDropdownMenu = document.getElementById('mobileUserDropdownMenu');

    // Desktop dropdown
    if (userProfileBtn && userDropdownMenu) {
        userProfileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userProfileBtn.classList.toggle('active');
            userDropdownMenu.classList.toggle('show');

            // Close mobile dropdown if open
            if (mobileUserProfileBtn && mobileUserDropdownMenu) {
                mobileUserProfileBtn.classList.remove('active');
                mobileUserDropdownMenu.classList.remove('show');
            }
        });
    }

    // Mobile dropdown
    if (mobileUserProfileBtn && mobileUserDropdownMenu) {
        mobileUserProfileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileUserProfileBtn.classList.toggle('active');
            mobileUserDropdownMenu.classList.toggle('show');

            // Close desktop dropdown if open
            if (userProfileBtn && userDropdownMenu) {
                userProfileBtn.classList.remove('active');
                userDropdownMenu.classList.remove('show');
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        // Check if click is outside both desktop and mobile dropdowns
        if (userProfileBtn && userDropdownMenu) {
            if (!userProfileBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userProfileBtn.classList.remove('active');
                userDropdownMenu.classList.remove('show');
            }
        }

        if (mobileUserProfileBtn && mobileUserDropdownMenu) {
            if (!mobileUserProfileBtn.contains(e.target) && !mobileUserDropdownMenu.contains(e.target)) {
                mobileUserProfileBtn.classList.remove('active');
                mobileUserDropdownMenu.classList.remove('show');
            }
        }
    });

    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (userProfileBtn && userDropdownMenu) {
                userProfileBtn.classList.remove('active');
                userDropdownMenu.classList.remove('show');
            }
            if (mobileUserProfileBtn && mobileUserDropdownMenu) {
                mobileUserProfileBtn.classList.remove('active');
                mobileUserDropdownMenu.classList.remove('show');
            }
        }
    });
});
</script>
