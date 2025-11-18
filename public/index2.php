<?php
session_start();

// Define root path and fix require paths
define('ROOT_PATH', dirname(__DIR__));

// Updated require path - adjust based on your actual file structure
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/services/CitationService.php';

// Require login
require_login();

// Initialize CitationService
$citationService = new CitationService();

// Generate next ticket number
$next_ticket = $citationService->generateNextTicketNumber();

// Pre-fill driver info if driver_id is provided
$driver_data = [];
$offense_counts = [];
if (isset($_GET['driver_id'])) {
    $driver_id = (int)$_GET['driver_id'];
    $driver_data = $citationService->getDriverById($driver_id);
    if ($driver_data) {
        $offense_counts = $citationService->getOffenseCountsByDriverId($driver_id);
    }
}

// Cache violation types (only active ones)
if (!isset($_SESSION['violation_types'])) {
    $_SESSION['violation_types'] = $citationService->getActiveViolationTypes();
}
$violation_types = $_SESSION['violation_types'];

// Fetch active apprehending officers
$apprehending_officers = $citationService->getActiveApprehendingOfficers();

// Close connection
$citationService->closeConnection();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Traffic Citation Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/citation-form.css">
    <style>
        .swal-wide {
            width: 500px !important;
        }
        .swal2-popup {
            font-size: 1rem !important;
        }
        .swal2-title {
            font-size: 1.75rem !important;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <?php include ROOT_PATH . '/templates/citation-form.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/citation-form.js"></script>
    <script src="../assets/js/duplicate-detection.js"></script>

</body>
</html>