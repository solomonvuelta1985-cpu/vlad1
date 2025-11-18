<?php
/**
 * TEMPORARY PASSWORD RESET SCRIPT
 * DELETE THIS FILE AFTER USE!
 */

require_once 'includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        $message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        $pdo = getPDO();
        if ($pdo) {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Update password
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
                $stmt->execute([$hash, $username]);

                $message = "Password for '$username' has been reset successfully!";
                $success = true;
            } else {
                $message = "User '$username' not found.";
            }
        } else {
            $message = 'Database connection failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TEMPORARY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 50px 20px;
        }
        .reset-card {
            max-width: 500px;
            margin: 0 auto;
        }
        .warning-banner {
            background: #dc3545;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="warning-banner">
            TEMPORARY RESET - DELETE THIS FILE AFTER USE!
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Reset User Password</h4>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-warning">
                        <strong>IMPORTANT:</strong> Delete this file now!<br>
                        <code>c:\xampp\htdocs\vlad\reset_password.php</code>
                    </div>
                    <a href="public/login.php" class="btn btn-primary">Go to Login</a>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="admin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
