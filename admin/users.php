<?php
/**
 * User Management Page
 * Admins can create, edit, delete, and manage user accounts
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin authentication
require_admin();

$page_title = "User Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Traffic Citation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-size: 16px;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background-color: #f8f9fa;
            font-size: 1.1rem;
        }

        .main-content h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .main-content h5 {
            font-size: 1.3rem;
        }

        /* Make table text larger */
        .table {
            font-size: 1rem;
        }

        .table thead th {
            font-size: 1.1rem;
            padding: 1rem 0.75rem;
            font-weight: 600;
        }

        .table tbody td {
            font-size: 1rem;
            padding: 0.9rem 0.75rem;
            vertical-align: middle;
        }

        /* Form elements */
        .form-label {
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            font-size: 1rem;
            padding: 0.6rem 0.75rem;
            height: auto;
        }

        .form-control small, .text-muted {
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn {
            font-size: 1rem;
            padding: 0.6rem 1.2rem;
        }

        .btn-sm {
            font-size: 0.95rem;
            padding: 0.5rem 0.8rem;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn-sm {
            min-width: 38px;
            min-height: 38px;
        }

        /* Badges */
        .status-badge {
            font-size: 0.95rem;
            padding: 6px 12px;
            font-weight: 500;
        }

        .role-badge {
            font-size: 0.95rem;
            padding: 6px 12px;
            font-weight: 500;
        }

        /* Alerts */
        .alert {
            font-size: 1.05rem;
            padding: 1rem 1.25rem;
        }

        /* Card headers */
        .card-header {
            padding: 1rem 1.25rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Modal */
        .modal-body {
            font-size: 1.05rem;
        }

        .modal-title {
            font-size: 1.4rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../public/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid" style="padding: 2rem;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> <?php echo $page_title; ?></h2>
                    <button class="btn btn-primary" onclick="showCreateForm()">
                        <i class="fas fa-plus"></i> Create New User
                    </button>
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                        <?php
                        echo $_SESSION['flash_message'];
                        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Create/Edit User Form (Initially Hidden) -->
                <div id="userFormCard" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 id="formTitle">Create New User</h5>
                    </div>
                    <div class="card-body">
                        <form id="userForm">
                            <input type="hidden" id="user_id" name="user_id">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           pattern="[a-zA-Z0-9_\-]{3,20}" required
                                           title="3-20 characters: letters, numbers, underscore, dash only">
                                    <small class="text-muted">3-20 characters (letters, numbers, _, -)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <span id="passwordRequired">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password"
                                           minlength="8">
                                    <small class="text-muted">Min 8 characters, must include letters and numbers</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="admin">Admin - Full Access</option>
                                        <option value="enforcer">Enforcer - Creates Citations</option>
                                        <option value="cashier">Cashier - Processes Payments</option>
                                        <option value="user">User - View Only</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3" id="statusField" style="display: none;">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save User
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="cancelForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search username, name, or email...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="roleFilter">
                                    <option value="">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="enforcer">Enforcer</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" onclick="loadUsers()">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User List Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="userTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Loading users...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="resetPasswordForm">
                    <div class="modal-body">
                        <input type="hidden" id="reset_user_id" name="user_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <p>Reset password for: <strong id="reset_username"></strong></p>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="new_password"
                                   name="new_password" minlength="8" required>
                            <small class="text-muted">Min 8 characters, must include letters and numbers</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/vlad/assets/js/user-management.js"></script>
</body>
</html>
