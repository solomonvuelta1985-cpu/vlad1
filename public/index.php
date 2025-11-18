<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Get dashboard statistics
$stats = [
    'today_citations' => 0,
    'pending_citations' => 0
];

$pdo = getPDO();
if ($pdo) {
    // Today's citations
    $stmt = db_query("SELECT COUNT(*) as count FROM citations WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $stats['today_citations'] = $result['count'] ?? 0;

    // Pending citations
    $stmt = db_query("SELECT COUNT(*) as count FROM citations WHERE status = 'pending'");
    $result = $stmt->fetch();
    $stats['pending_citations'] = $result['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Citation System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --success-green: #198754;
            --info-cyan: #0dcaf0;
            --warning-yellow: #ffc107;
            --danger-red: #dc3545;
            --secondary-gray: #6c757d;
            --purple: #6f42c1;
            --white: #ffffff;
            --off-white: #f5f5f5;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --border-gray: #dee2e6;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --text-label: #495057;
        }

        body {
            background: var(--off-white);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: clamp(0.875rem, 2.3vw, 0.9rem);
            line-height: 1.5;
            color: var(--text-dark);
        }

        .content {
            padding: clamp(15px, 3vw, 20px);
        }

        /* Page Title */
        .page-title {
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: clamp(12px, 2vw, 15px);
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: clamp(15px, 3vw, 20px);
            position: relative;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 6px 0 0 6px;
        }

        .stat-card.blue::before { background: var(--primary-blue); }
        .stat-card.red::before { background: var(--danger-red); }
        .stat-card.green::before { background: var(--success-green); }
        .stat-card.yellow::before { background: var(--warning-yellow); }
        .stat-card.purple::before { background: var(--purple); }

        .stat-number {
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Section Title */
        .section-title {
            font-size: clamp(1.1rem, 3vw, 1.25rem);
            font-weight: 600;
            color: var(--text-label);
            background: var(--light-gray);
            padding: 10px 15px;
            border-left: 4px solid var(--primary-blue);
            border-radius: 4px;
            margin-bottom: 20px;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(12px, 2vw, 15px);
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: clamp(20px, 4vw, 30px);
            text-align: center;
            transition: box-shadow 0.2s;
        }

        .action-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .action-icon.blue { color: var(--primary-blue); }
        .action-icon.green { color: var(--success-green); }
        .action-icon.cyan { color: var(--info-cyan); }
        .action-icon.yellow { color: var(--warning-yellow); }

        .action-title {
            font-size: clamp(0.95rem, 2.7vw, 1rem);
            font-weight: 500;
            color: var(--text-label);
            margin-bottom: 10px;
        }

        .action-description {
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            padding: 8px 15px;
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 44px;
        }

        .btn-primary {
            background: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }

        .btn-primary:hover {
            background: #0b5ed7;
            border-color: #0b5ed7;
        }

        .btn-outline-primary {
            color: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        /* System Info */
        .info-card {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: clamp(20px, 4vw, 30px);
        }

        .info-title {
            font-size: clamp(1.1rem, 3vw, 1.25rem);
            font-weight: 600;
            color: var(--text-label);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list i.success {
            color: var(--success-green);
        }

        .info-list i.primary {
            color: var(--primary-blue);
        }

        /* Welcome Alert */
        .welcome-alert {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Focus indicators for accessibility */
        .btn:focus,
        .action-card:focus {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <?php echo show_flash(); ?>

            <!-- Page Title -->
            <h1 class="page-title">
                <i class="fas fa-traffic-light me-2"></i>Traffic Citation System
            </h1>

            <!-- Welcome Message -->
            <div class="welcome-alert">
                <i class="fas fa-user-circle me-2"></i>
                Welcome<?php echo is_logged_in() ? ', ' . htmlspecialchars($_SESSION['full_name'] ?? 'User') : ''; ?>! 
                Manage traffic violations, issue citations, and track enforcement activities.
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card blue">
                    <div class="stat-number"><?php echo $stats['today_citations']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-file-alt"></i>
                        <span>Today's Citations</span>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-number"><?php echo $stats['pending_citations']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-clock"></i>
                        <span>Pending Citations</span>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-number">42</div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i>
                        <span>Resolved This Week</span>
                    </div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-number">18</div>
                    <div class="stat-label">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Overdue Citations</span>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-number">7</div>
                    <div class="stat-label">
                        <i class="fas fa-user-shield"></i>
                        <span>Active Officers</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h2 class="section-title">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h2>
            
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon blue">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h5 class="action-title">New Citation</h5>
                    <p class="action-description">Issue a new traffic citation ticket</p>
                    <a href="index2.php" class="btn btn-primary">
                        <i class="fas fa-file-alt me-2"></i>Create
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon cyan">
                        <i class="fas fa-search"></i>
                    </div>
                    <h5 class="action-title">Search Records</h5>
                    <p class="action-description">Find citations by ticket number or driver</p>
                    <a href="search.php" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon green">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <h5 class="action-title">View Citations</h5>
                    <p class="action-description">Browse all issued citations</p>
                    <a href="citations.php" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>View All
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon yellow">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h5 class="action-title">Reports</h5>
                    <p class="action-description">Generate statistics and reports</p>
                    <a href="reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-chart-line me-2"></i>Reports
                    </a>
                </div>
            </div>

            <!-- System Information -->
            <div class="info-card mt-5">
                <h5 class="info-title">
                    <i class="fas fa-info-circle"></i>System Information
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="info-list">
                            <li><i class="fas fa-check success"></i>CSRF Protection: Active</li>
                            <li><i class="fas fa-check success"></i>Rate Limiting: Active</li>
                            <li><i class="fas fa-check success"></i>Input Sanitization: Active</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="info-list">
                            <li><i class="fas fa-check success"></i>PDO Prepared Statements: Active</li>
                            <li><i class="fas fa-check success"></i>Security Headers: Active</li>
                            <li><i class="fas fa-database primary"></i>Database: traffic_system</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>