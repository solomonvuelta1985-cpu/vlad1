<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
require_admin();
check_session_timeout();

// Fetch dashboard statistics
$stats = [];
try {
    // Total citations
    $stmt = db_query("SELECT COUNT(*) as total FROM citations");
    $stats['total_citations'] = $stmt->fetch()['total'] ?? 0;

    // Today's citations
    $stmt = db_query("SELECT COUNT(*) as today FROM citations WHERE DATE(created_at) = CURDATE()");
    $stats['today_citations'] = $stmt->fetch()['today'] ?? 0;

    // Total drivers
    $stmt = db_query("SELECT COUNT(*) as total FROM drivers");
    $stats['total_drivers'] = $stmt->fetch()['total'] ?? 0;

    // Total users
    $stmt = db_query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'] ?? 0;

    // Pending citations
    $stmt = db_query("SELECT COUNT(*) as pending FROM citations WHERE status = 'pending'");
    $stats['pending_citations'] = $stmt->fetch()['pending'] ?? 0;

    // Total fines collected
    $stmt = db_query("SELECT COALESCE(SUM(total_fine), 0) as total FROM citations WHERE status = 'paid'");
    $stats['total_fines'] = $stmt->fetch()['total'] ?? 0;

    // Recent citations
    $stmt = db_query(
        "SELECT ticket_number, CONCAT(last_name, ', ', first_name) as driver_name,
                apprehension_datetime, status, total_fine, created_at
         FROM citations ORDER BY created_at DESC LIMIT 10"
    );
    $recent_citations = $stmt->fetchAll();

    // Top violations
    $stmt = db_query(
        "SELECT vt.violation_type, COUNT(*) as count
         FROM violations v
         JOIN violation_types vt ON v.violation_type_id = vt.violation_type_id
         GROUP BY vt.violation_type_id
         ORDER BY count DESC LIMIT 5"
    );
    $top_violations = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = array_fill_keys(['total_citations', 'today_citations', 'total_drivers', 'total_users', 'pending_citations', 'total_fines'], 0);
    $recent_citations = [];
    $top_violations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Traffic Citation System</title>
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
        .page-header h3 {
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .page-header p {
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
            color: var(--text-muted);
        }

        .page-header {
            margin-bottom: 30px;
        }

        /* Statistics Cards - Flat Design with Left Border */
        .stat-card {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: clamp(15px, 3vw, 20px);
            position: relative;
            transition: box-shadow 0.2s;
            height: 100%;
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
        .stat-card.green::before { background: var(--success-green); }
        .stat-card.red::before { background: var(--danger-red); }
        .stat-card.yellow::before { background: var(--warning-yellow); }
        .stat-card.purple::before { background: var(--purple); }
        .stat-card.cyan::before { background: var(--info-cyan); }

        .stat-value {
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

        /* Dashboard Cards */
        .dashboard-card {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            overflow: hidden;
        }

        .dashboard-card .card-header {
            background: var(--light-gray);
            border-bottom: 2px solid var(--border-gray);
            font-weight: 600;
            color: var(--text-label);
            padding: 15px 20px;
            font-size: clamp(1.1rem, 3vw, 1.25rem);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
        }

        .table th {
            background: var(--light-gray);
            font-weight: 600;
            color: var(--text-label);
            border-top: none;
            border-bottom: 2px solid var(--border-gray);
            padding: 12px 15px;
            font-size: 0.875rem;
        }

        .table td {
            padding: 10px 15px;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-gray);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: var(--light-gray);
            transition: background 0.2s;
        }

        /* Badges - Flat Design */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: clamp(0.7rem, 2vw, 0.75rem);
            font-weight: 500;
            border: 1px solid transparent;
        }

        .badge.bg-success {
            background: #d1e7dd !important;
            color: #0a3622 !important;
            border-color: #a3cfbb;
        }

        .badge.bg-warning {
            background: #fff3cd !important;
            color: #664d03 !important;
            border-color: #ffe69c;
        }

        .badge.bg-info {
            background: #cff4fc !important;
            color: #055160 !important;
            border-color: #9eeaf9;
        }

        .badge.bg-secondary {
            background: #e2e3e5 !important;
            color: #41464b !important;
            border-color: #c4c8cb;
        }

        .badge.bg-primary {
            background: #cfe2ff !important;
            color: #084298 !important;
            border-color: #b6d4fe;
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
        }

        .btn-outline-primary {
            color: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
        }

        /* Focus indicators for accessibility */
        .btn:focus {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <?php include '../public/sidebar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h3><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h3>
                    <p class="text-muted mb-0">Overview of traffic citation system</p>
                </div>
                <div>
                    <span class="text-muted">
                        <i class="fas fa-calendar me-1"></i><?php echo date('F d, Y'); ?>
                    </span>
                </div>
            </div>

            <?php echo show_flash(); ?>

            <!-- Statistics Cards - Flat Design -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-2">
                    <div class="stat-card blue">
                        <div class="stat-value"><?php echo number_format($stats['total_citations']); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-file-alt"></i>
                            <span>Total Citations</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <div class="stat-card green">
                        <div class="stat-value"><?php echo number_format($stats['today_citations']); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-calendar-day"></i>
                            <span>Today's Citations</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <div class="stat-card red">
                        <div class="stat-value"><?php echo number_format($stats['pending_citations']); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-clock"></i>
                            <span>Pending</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <div class="stat-card purple">
                        <div class="stat-value"><?php echo number_format($stats['total_drivers']); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-users"></i>
                            <span>Drivers</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <div class="stat-card yellow">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-user-shield"></i>
                            <span>System Users</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <div class="stat-card cyan">
                        <div class="stat-value">₱<?php echo number_format($stats['total_fines'], 0); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-peso-sign"></i>
                            <span>Fines Collected</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Citations -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-history me-2"></i>Recent Citations</span>
                            <a href="../public/citations.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Driver</th>
                                            <th>Date</th>
                                            <th>Fine</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_citations)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                    No citations found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_citations as $citation): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($citation['ticket_number']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($citation['driver_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($citation['apprehension_datetime'])); ?></td>
                                                    <td>₱<?php echo number_format($citation['total_fine'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = match($citation['status']) {
                                                            'paid' => 'bg-success',
                                                            'pending' => 'bg-warning text-dark',
                                                            'contested' => 'bg-info',
                                                            'dismissed' => 'bg-secondary',
                                                            default => 'bg-light text-dark'
                                                        };
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst($citation['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Violations -->
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle me-2"></i>Top Violations
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_violations)): ?>
                                <p class="text-center text-muted py-4">
                                    <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                                    No violation data
                                </p>
                            <?php else: ?>
                                <?php foreach ($top_violations as $index => $violation): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                            <?php echo htmlspecialchars($violation['violation_type']); ?>
                                        </div>
                                        <span class="badge bg-secondary"><?php echo $violation['count']; ?></span>
                                    </div>
                                    <?php if ($index < count($top_violations) - 1): ?>
                                        <hr class="my-2">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
