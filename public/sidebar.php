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
        <button type="button" id="mobileSidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
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
        <li class="sidebar-heading">Main</li>
        <li>
            <a href="/vlad/public/index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>" title="Dashboard">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>

        <!-- Citations Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Citations</li>
        <li>
            <a href="/vlad/public/index2.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index2.php') ? 'active' : ''; ?>" title="New Citation">
                <i class="fas fa-plus-circle"></i> <span>New Citation</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/citations.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'citations.php') ? 'active' : ''; ?>" title="View All">
                <i class="fas fa-list-alt"></i> <span>View All</span>
            </a>
        </li>
        <li>
            <a href="/vlad/public/search.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'search.php') ? 'active' : ''; ?>" title="Search">
                <i class="fas fa-search"></i> <span>Search</span>
            </a>
        </li>

        <!-- Management Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Management</li>
        <li>
            <a href="/vlad/public/officers.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'officers.php') ? 'active' : ''; ?>" title="Officers">
                <i class="fas fa-user-shield"></i> <span>Officers</span>
            </a>
        </li>

        <?php if (function_exists('is_admin') && is_admin()): ?>
        <!-- Admin Section -->
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Administration</li>
        <li>
            <a href="/vlad/admin/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : ''; ?>" title="Admin Dashboard">
                <i class="fas fa-chart-line"></i> <span>Admin Dashboard</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/violations.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'violations.php') ? 'active' : ''; ?>" title="Violation Types">
                <i class="fas fa-exclamation-triangle"></i> <span>Violation Types</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'users.php') ? 'active' : ''; ?>" title="Manage Users">
                <i class="fas fa-users-cog"></i> <span>Manage Users</span>
            </a>
        </li>
        <li>
            <a href="/vlad/admin/reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'active' : ''; ?>" title="Reports">
                <i class="fas fa-chart-bar"></i> <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
        <div class="user-info">
            <small>
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
            </small>
        </div>
        <a href="/vlad/public/logout.php" class="btn btn-sm btn-outline-light w-100 mt-2" title="Logout">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
        <?php else: ?>
        <a href="/vlad/public/login.php" class="btn btn-sm btn-outline-light w-100" title="Login">
            <i class="fas fa-sign-in-alt"></i> <span>Login</span>
        </a>
        <?php endif; ?>
    </div>
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
</div>

<style>
:root {
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 70px;
}

/* Mobile Header */
.mobile-header {
    display: none;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 15px 20px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.mobile-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-header h4 {
    margin: 0;
    font-size: 1.2rem;
}

#mobileSidebarToggle {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 5px;
}

/* Sidebar Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
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
    height: 60px;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    padding: 0 20px;
    z-index: 100;
    transition: left 0.3s ease;
}

.sidebar-collapsed .top-navbar {
    left: var(--sidebar-collapsed-width);
}

#sidebarCollapse {
    background: none;
    border: none;
    font-size: 1.3rem;
    color: #1e3c72;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

#sidebarCollapse:hover {
    background: #f0f0f0;
    transform: rotate(90deg);
}

.top-navbar-info {
    margin-left: 15px;
}

.welcome-text {
    color: #666;
    font-size: 14px;
}

/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 0;
    z-index: 1000;
    box-shadow: 3px 0 10px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    overflow: hidden;
}

.sidebar-collapsed .sidebar {
    width: var(--sidebar-collapsed-width);
}

.sidebar-header {
    padding: 20px;
    background: rgba(0,0,0,0.2);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-header h4 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.sidebar-header h4 i {
    min-width: 30px;
}

.sidebar-collapsed .sidebar-header h4 span {
    display: none;
}

.sidebar-menu {
    list-style: none;
    padding: 10px 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.sidebar-menu::-webkit-scrollbar {
    display: none;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: background 0.3s ease, border-left-color 0.3s ease;
    border-left: 3px solid transparent;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.4;
    white-space: nowrap;
}

.sidebar-menu li a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: #ffd700;
}

.sidebar-menu li a.active {
    background: rgba(255,255,255,0.15);
    color: white;
    border-left-color: #ffd700;
    font-weight: 500;
}

.sidebar-menu li a i {
    min-width: 30px;
    font-size: 16px;
    text-align: center;
}

.sidebar-menu li a span {
    font-size: 14px;
    transition: opacity 0.3s ease;
}

.sidebar-collapsed .sidebar-menu li a {
    justify-content: center;
    padding: 15px 10px;
}

.sidebar-collapsed .sidebar-menu li a span {
    display: none;
}

.sidebar-collapsed .sidebar-menu li a i {
    margin: 0;
    font-size: 18px;
}

.sidebar-divider {
    border-top: 1px solid rgba(255,255,255,0.2);
    margin: 10px 0;
}

.sidebar-heading {
    padding: 10px 20px 5px;
    font-size: 11px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-collapsed .sidebar-heading {
    font-size: 0;
    padding: 5px 0;
}

.sidebar-footer {
    padding: 15px 20px;
    background: rgba(0,0,0,0.2);
    border-top: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-footer .user-info {
    color: rgba(255,255,255,0.7);
    margin-bottom: 5px;
}

.sidebar-footer .user-info i {
    min-width: 20px;
}

.sidebar-collapsed .sidebar-footer .user-info span,
.sidebar-collapsed .sidebar-footer .btn span {
    display: none;
}

.sidebar-collapsed .sidebar-footer .btn {
    padding: 8px;
}

/* Main Content */
.content {
    margin-left: var(--sidebar-width);
    padding: 20px;
    padding-top: 80px;
    min-height: 100vh;
    background: #f8f9fa;
    transition: margin-left 0.3s ease;
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
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 1001;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.sidebar-collapsed .sidebar-menu li a:hover::after {
    opacity: 1;
    visibility: visible;
    left: calc(100% + 10px);
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
    .sidebar-collapsed .sidebar-footer .user-info span,
    .sidebar-collapsed .sidebar-footer .btn span,
    .sidebar-collapsed .sidebar-heading {
        display: inline;
        font-size: inherit;
    }

    .sidebar-collapsed .sidebar-menu li a {
        justify-content: flex-start;
        padding: 12px 20px;
    }

    .sidebar-collapsed .sidebar-menu li a i {
        font-size: 16px;
        margin-right: 10px;
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
});
</script>
