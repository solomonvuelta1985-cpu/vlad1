<?php
session_start();

// Define root path and fix require paths
define('ROOT_PATH', dirname(__DIR__));

// Updated require path - adjust based on your actual file structure
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

// Require login
require_login();

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the highest ticket number
    $stmt = $conn->query("SELECT ticket_number FROM citations ORDER BY CAST(ticket_number AS UNSIGNED) DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['ticket_number'])) {
        $max_ticket = (int)preg_replace('/[^0-9]/', '', $row['ticket_number']);
    } else {
        $max_ticket = 6100; // Default starting ticket number
    }
    $next_ticket = sprintf("%05d", $max_ticket + 1);

    // Ensure unique ticket number
    $stmt = $conn->prepare("SELECT COUNT(*) FROM citations WHERE ticket_number = :ticket_number");
    $stmt->execute([':ticket_number' => $next_ticket]);
    while ($stmt->fetchColumn() > 0) {
        $max_ticket++;
        $next_ticket = sprintf("%05d", $max_ticket + 1);
        $stmt->execute([':ticket_number' => $next_ticket]);
    }

    // Pre-fill driver info if driver_id is provided
    $driver_data = [];
    $offense_counts = [];
    if (isset($_GET['driver_id'])) {
        $driver_id = (int)$_GET['driver_id'];
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_id = :driver_id");
        $stmt->execute([':driver_id' => $driver_id]);
        $driver_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch offense counts for this driver
        $stmt = $conn->prepare("
            SELECT vt.violation_type_id, MAX(v.offense_count) AS offense_count
            FROM violations v
            JOIN violation_types vt ON v.violation_type_id = vt.violation_type_id
            JOIN citations c ON v.citation_id = c.citation_id
            WHERE c.driver_id = :driver_id
            GROUP BY vt.violation_type_id
        ");
        $stmt->execute([':driver_id' => $driver_id]);
        $offense_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Cache violation types (only active ones)
    if (!isset($_SESSION['violation_types'])) {
        $stmt = $conn->query("SELECT violation_type_id, violation_type, fine_amount_1, fine_amount_2, fine_amount_3 FROM violation_types WHERE is_active = 1 ORDER BY violation_type");
        $_SESSION['violation_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $violation_types = $_SESSION['violation_types'];

    // Fetch active apprehending officers
    $stmt = $conn->query("SELECT officer_id, officer_name, badge_number, position FROM apprehending_officers WHERE is_active = 1 ORDER BY officer_name");
    $apprehending_officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $next_ticket = "06101";
    $driver_data = [];
    $violation_types = [];
    $apprehending_officers = [];
    error_log("PDOException in index2.php: " . $e->getMessage());
}
$conn = null;

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
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0b5ed7;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --secondary: #6c757d;
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
            background-color: var(--off-white);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            font-size: 16px;
        }

        .content {
            flex: 1;
            margin-left: 260px;
            padding: clamp(15px, 3vw, 20px);
            overflow-y: auto;
            height: 100vh;
            transition: margin-left 0.3s ease-in-out;
        }

        .content.collapsed {
            margin-left: 80px;
        }

        .ticket-container {
            background-color: var(--white);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 6px;
            border: 1px solid var(--border-gray);
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
            width: 100%;
        }

        .header {
            background-color: var(--white);
            color: var(--text-dark);
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            border: 1px solid var(--border-gray);
            border-left: 4px solid var(--primary);
        }

        .header h4 {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .header h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 600;
            letter-spacing: 0.02em;
            margin: 0;
            color: var(--text-dark);
        }

        .ticket-number {
            position: absolute;
            top: 20px;
            right: 20px;
            font-weight: 600;
            background: var(--light-gray);
            padding: 10px 18px;
            border: 1px solid var(--border-gray);
            border-radius: 4px;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            color: var(--text-dark);
        }

        .section {
            background-color: var(--white);
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid var(--border-gray);
        }

        .section h5 {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-label);
            font-size: clamp(1.2rem, 3vw, 1.4rem);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-gray);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-label);
            margin-bottom: 8px;
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
        }

        .form-control, .form-select {
            border-radius: 4px;
            border: 1px solid var(--border-gray);
            padding: 10px 14px;
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger);
            background-color: #f8d7da;
        }

        .accordion-button {
            font-weight: 600;
            color: var(--text-label);
            background-color: var(--light-gray);
            border: none;
            border-radius: 4px !important;
            padding: 14px 18px;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            transition: all 0.2s ease;
        }

        .accordion-button:not(.collapsed) {
            color: var(--text-dark);
            background-color: var(--medium-gray);
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .violation-list .form-check {
            margin-bottom: 10px;
            padding-left: 28px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.15em;
        }

        .remarks textarea {
            resize: vertical;
            min-height: 100px;
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
        }

        .footer {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: var(--text-muted);
            padding: 15px 0;
            border-top: 1px solid var(--border-gray);
            text-align: justify;
            line-height: 1.6;
        }

        .btn-custom {
            background-color: var(--primary);
            color: var(--white);
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 600;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            border: 1px solid var(--primary);
            transition: all 0.2s ease;
        }

        .btn-custom:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-outline-secondary, .btn-outline-danger {
            border-radius: 4px;
            padding: 8px 14px;
            font-size: clamp(0.95rem, 2.5vw, 1rem);
            transition: all 0.2s ease;
        }

        #otherViolationInput, #otherVehicleInput, #otherBarangayInput {
            display: none;
            margin-top: 8px;
            border-radius: 4px;
        }

        .form-check {
            margin-bottom: 10px;
        }

        .form-check-label {
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
            color: var(--text-dark);
        }

        .input-group .btn {
            border-radius: 4px;
            font-size: clamp(0.8rem, 2vw, 0.85rem);
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 260px;
            }

            .content.collapsed {
                margin-left: 80px;
            }

            .ticket-container {
                padding: 15px;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            .section {
                padding: 15px;
            }

            .ticket-number {
                position: static;
                display: inline-block;
                margin-top: 10px;
            }
        }

        @media (max-width: 576px) {
            .content {
                margin-left: 200px;
            }

            .content.collapsed {
                margin-left: 60px;
            }

            .form-label {
                font-size: 0.8rem;
            }

            .form-control, .form-select {
                font-size: 0.8rem;
                padding: 6px 10px;
            }

            .btn-custom {
                padding: 6px 12px;
                font-size: 0.8rem;
            }

            .header {
                padding: 15px;
            }
        }

        @media print {
            .sidebar, .ticket-number, .btn-custom, .btn-outline-secondary, .btn-outline-danger {
                display: none;
            }

            .content {
                margin-left: 0;
            }

            .ticket-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 10px;
                width: 100%;
                height: auto;
            }

            .section {
                border: none;
                padding: 10px;
            }

            .accordion-button::after {
                display: none;
            }

            .accordion-collapse {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <form id="citationForm" action="../api/insert_citation.php" method="POST">
            <div class="ticket-container">
                <div class="header">
                    <h4>REPUBLIC OF THE PHILIPPINES</h4>
                    <h4>PROVINCE OF CAGAYAN • MUNICIPALITY OF BAGGAO</h4>
                    <h1>TRAFFIC CITATION TICKET</h1>
                    <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($next_ticket); ?>">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo generate_token(); ?>">
                    <div class="ticket-number"><?php echo htmlspecialchars($next_ticket); ?></div>
                </div>

                <!-- Driver Info -->
                <div class="section">
                    <h5><i class="fas fa-id-card me-2"></i>Driver Information</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Enter last name" value="<?php echo htmlspecialchars($driver_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Enter first name" value="<?php echo htmlspecialchars($driver_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">M.I.</label>
                            <input type="text" name="middle_initial" class="form-control" placeholder="M.I." value="<?php echo htmlspecialchars($driver_data['middle_initial'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control" placeholder="e.g., Jr." value="<?php echo htmlspecialchars($driver_data['suffix'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" id="dateOfBirth" value="<?php echo htmlspecialchars($driver_data['date_of_birth'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" id="ageField" placeholder="Auto" value="<?php echo htmlspecialchars($driver_data['age'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Zone</label>
                            <input type="text" name="zone" class="form-control" placeholder="Enter zone" value="<?php echo htmlspecialchars($driver_data['zone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Barangay *</label>
                            <select name="barangay" class="form-select" id="barangaySelect" required>
                                <option value="" disabled <?php echo (!isset($driver_data['barangay']) || $driver_data['barangay'] == '') ? 'selected' : ''; ?>>Select Barangay</option>
                                <option value="Adag" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Adag') ? 'selected' : ''; ?>>Adag</option>
                                <option value="Agaman" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman') ? 'selected' : ''; ?>>Agaman</option>
                                <option value="Agaman Norte" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman Norte') ? 'selected' : ''; ?>>Agaman Norte</option>
                                <option value="Agaman Sur" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman Sur') ? 'selected' : ''; ?>>Agaman Sur</option>
                                <option value="Alaguia" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Alaguia') ? 'selected' : ''; ?>>Alaguia</option>
                                <option value="Alba" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Alba') ? 'selected' : ''; ?>>Alba</option>
                                <option value="Annayatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Annayatan') ? 'selected' : ''; ?>>Annayatan</option>
                                <option value="Asassi" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Asassi') ? 'selected' : ''; ?>>Asassi</option>
                                <option value="Asinga-Via" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Asinga-Via') ? 'selected' : ''; ?>>Asinga-Via</option>
                                <option value="Awallan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Awallan') ? 'selected' : ''; ?>>Awallan</option>
                                <option value="Bacagan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bacagan') ? 'selected' : ''; ?>>Bacagan</option>
                                <option value="Bagunot" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bagunot') ? 'selected' : ''; ?>>Bagunot</option>
                                <option value="Barsat East" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Barsat East') ? 'selected' : ''; ?>>Barsat East</option>
                                <option value="Barsat West" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Barsat West') ? 'selected' : ''; ?>>Barsat West</option>
                                <option value="Bitag Grande" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bitag Grande') ? 'selected' : ''; ?>>Bitag Grande</option>
                                <option value="Bitag Pequeño" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bitag Pequeño') ? 'selected' : ''; ?>>Bitag Pequeño</option>
                                <option value="Bungel" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bungel') ? 'selected' : ''; ?>>Bungel</option>
                                <option value="Canagatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Canagatan') ? 'selected' : ''; ?>>Canagatan</option>
                                <option value="Carupian" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Carupian') ? 'selected' : ''; ?>>Carupian</option>
                                <option value="Catayauan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Catayauan') ? 'selected' : ''; ?>>Catayauan</option>
                                <option value="Dabburab" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dabburab') ? 'selected' : ''; ?>>Dabburab</option>
                                <option value="Dalin" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dalin') ? 'selected' : ''; ?>>Dalin</option>
                                <option value="Dallang" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dallang') ? 'selected' : ''; ?>>Dallang</option>
                                <option value="Furagui" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Furagui') ? 'selected' : ''; ?>>Furagui</option>
                                <option value="Hacienda Intal" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Hacienda Intal') ? 'selected' : ''; ?>>Hacienda Intal</option>
                                <option value="Immurung" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Immurung') ? 'selected' : ''; ?>>Immurung</option>
                                <option value="Jomlo" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Jomlo') ? 'selected' : ''; ?>>Jomlo</option>
                                <option value="Mabangguc" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mabangguc') ? 'selected' : ''; ?>>Mabangguc</option>
                                <option value="Masical" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Masical') ? 'selected' : ''; ?>>Masical</option>
                                <option value="Mission" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mission') ? 'selected' : ''; ?>>Mission</option>
                                <option value="Mocag" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mocag') ? 'selected' : ''; ?>>Mocag</option>
                                <option value="Nangalinan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Nangalinan') ? 'selected' : ''; ?>>Nangalinan</option>
                                <option value="Pallagao" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Pallagao') ? 'selected' : ''; ?>>Pallagao</option>
                                <option value="Paragat" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Paragat') ? 'selected' : ''; ?>>Paragat</option>
                                <option value="Piggatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Piggatan') ? 'selected' : ''; ?>>Piggatan</option>
                                <option value="Poblacion" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Poblacion') ? 'selected' : ''; ?>>Poblacion</option>
                                <option value="Remus" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Remus') ? 'selected' : ''; ?>>Remus</option>
                                <option value="San Antonio" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Antonio') ? 'selected' : ''; ?>>San Antonio</option>
                                <option value="San Francisco" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Francisco') ? 'selected' : ''; ?>>San Francisco</option>
                                <option value="San Isidro" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Isidro') ? 'selected' : ''; ?>>San Isidro</option>
                                <option value="San Jose" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Jose') ? 'selected' : ''; ?>>San Jose</option>
                                <option value="San Vicente" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Vicente') ? 'selected' : ''; ?>>San Vicente</option>
                                <option value="Santa Margarita" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Santa Margarita') ? 'selected' : ''; ?>>Santa Margarita</option>
                                <option value="Santor" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Santor') ? 'selected' : ''; ?>>Santor</option>
                                <option value="Taguing" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taguing') ? 'selected' : ''; ?>>Taguing</option>
                                <option value="Taguntungan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taguntungan') ? 'selected' : ''; ?>>Taguntungan</option>
                                <option value="Tallang" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Tallang') ? 'selected' : ''; ?>>Tallang</option>
                                <option value="Taytay" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taytay') ? 'selected' : ''; ?>>Taytay</option>
                                <option value="Other" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <input type="text" name="other_barangay" class="form-control" id="otherBarangayInput" placeholder="Enter other barangay" value="<?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Other') ? htmlspecialchars($driver_data['barangay']) : ''; ?>">
                        </div>
                        <div class="col-md-3" id="municipalityDiv" style="display: <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] != 'Other' && $driver_data['barangay'] != '') ? 'block' : 'none'; ?>;">
                            <label class="form-label">Municipality</label>
                            <input type="text" name="municipality" class="form-control" id="municipalityInput" value="<?php echo htmlspecialchars($driver_data['municipality'] ?? 'Baggao'); ?>" readonly>
                        </div>
                        <div class="col-md-3" id="provinceDiv" style="display: <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] != 'Other' && $driver_data['barangay'] != '') ? 'block' : 'none'; ?>;">
                            <label class="form-label">Province</label>
                            <input type="text" name="province" class="form-control" id="provinceInput" value="<?php echo htmlspecialchars($driver_data['province'] ?? 'Cagayan'); ?>" readonly>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="has_license" id="hasLicense" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="hasLicense">Has License</label>
                            </div>
                        </div>
                        <div class="col-md-4 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                            <label class="form-label">License Number *</label>
                            <input type="text" name="license_number" class="form-control" placeholder="Enter license number" value="<?php echo htmlspecialchars($driver_data['license_number'] ?? ''); ?>" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                        </div>
                        <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                            <label class="form-label d-block">License Type *</label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="license_type" value="nonProf" id="nonProf" <?php echo (!isset($driver_data['license_type']) || $driver_data['license_type'] == 'Non-Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                                <label class="form-check-label" for="nonProf">Non-Prof</label>
                            </div>
                        </div>
                        <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                            <label class="form-label d-block"> </label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="license_type" value="prof" id="prof" <?php echo (isset($driver_data['license_type']) && $driver_data['license_type'] == 'Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                                <label class="form-check-label" for="prof">Prof</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="section">
                    <h5><i class="fas fa-car me-2"></i>Vehicle Information</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Plate / MV File / Engine / Chassis No. *</label>
                            <input type="text" name="plate_mv_engine_chassis_no" class="form-control" placeholder="Enter plate or other number" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vehicle Type *</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Motorcycle" id="motorcycle" required onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="motorcycle">Motorcycle</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Tricycle" id="tricycle" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="tricycle">Tricycle</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="SUV" id="suv" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="suv">SUV</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Van" id="van" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="van">Van</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Jeep" id="jeep" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="jeep">Jeep</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Truck" id="truck" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="truck">Truck</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Kulong Kulong" id="kulong" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="kulong">Kulong Kulong</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="vehicle_type" value="Other" id="othersVehicle" onchange="toggleOtherVehicle(this.value)">
                                    <label class="form-check-label" for="othersVehicle">Others</label>
                                </div>
                            </div>
                            <input type="text" name="other_vehicle_input" class="form-control mt-2" id="otherVehicleInput" placeholder="Specify other vehicle type" style="display: none;">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vehicle Description</label>
                            <input type="text" name="vehicle_description" class="form-control" placeholder="Brand, Model, CC, Color, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Apprehension Date & Time *</label>
                            <div class="input-group">
                                <input type="datetime-local" name="apprehension_datetime" class="form-control" id="apprehensionDateTime" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleDateTime" title="Set/Clear"><i class="fas fa-calendar-alt"></i></button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Place of Apprehension *</label>
                            <input type="text" name="place_of_apprehension" class="form-control" placeholder="Enter place of apprehension" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Apprehension Officer *</label>
                            <select name="apprehension_officer" class="form-select" id="apprehensionOfficer" required>
                                <option value="" disabled selected>Select Apprehension Officer</option>
                                <?php if (!empty($apprehending_officers)): ?>
                                    <?php foreach ($apprehending_officers as $officer): ?>
                                        <option value="<?php echo htmlspecialchars($officer['officer_name']); ?>">
                                            <?php echo htmlspecialchars($officer['officer_name']); ?>
                                            <?php if (!empty($officer['badge_number'])): ?>
                                                (<?php echo htmlspecialchars($officer['badge_number']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Violations (Accordion) -->
                <div class="section">
                    <h5 class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Violation(s) *</h5>
                    <div class="accordion violation-list" id="violationsAccordion">
                        <?php
                        // Display all violations from database, grouped by keyword matching
                        $categories = [
                            'Helmet Violations' => ['HELMET'],
                            'License / Registration' => ['LICENSE', 'REGISTRATION', 'OPLAN VISA', 'E-OV MATCH'],
                            'Vehicle Condition' => ['DEFECTIVE', 'MUFFLER', 'MODIFICATION', 'PARTS'],
                            'Reckless / Improper Driving' => ['RECKLESS', 'DRAG RACING', 'DRUNK', 'DRIVING IN SHORT', 'ARROGANT'],
                            'Traffic Rules' => ['TRAFFIC SIGN', 'PARKING', 'OBSTRUCTION', 'PEDESTRIAN', 'LOADING', 'PASSENGER ON TOP'],
                            'Miscellaneous' => ['COLORUM', 'TRASHBIN', 'OVERLOADED', 'CHARGING', 'REFUSAL']
                        ];

                        // Track which violations have been displayed
                        $displayed_violations = [];

                        foreach ($categories as $category => $keywords) {
                            $category_id = htmlspecialchars(strtolower(str_replace([' ', '/', '(', ')'], '', $category)));
                            echo "<div class='accordion-item'>";
                            echo "<h2 class='accordion-header' id='heading-$category_id'>";
                            echo "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-$category_id' aria-expanded='false' aria-controls='collapse-$category_id'>$category</button>";
                            echo "</h2>";
                            echo "<div id='collapse-$category_id' class='accordion-collapse collapse' aria-labelledby='heading-$category_id' data-bs-parent='#violationsAccordion'>";
                            echo "<div class='accordion-body p-3'>";
                            $violations_found = false;
                            foreach ($violation_types as $v) {
                                // Check if violation matches any keyword in this category
                                $matches_category = false;
                                foreach ($keywords as $keyword) {
                                    if (stripos($v['violation_type'], $keyword) !== false) {
                                        $matches_category = true;
                                        break;
                                    }
                                }
                                if ($matches_category && !in_array($v['violation_type_id'], $displayed_violations)) {
                                    $violations_found = true;
                                    $displayed_violations[] = $v['violation_type_id'];
                                    $offense_count = isset($offense_counts[$v['violation_type_id']]) ? min((int)$offense_counts[$v['violation_type_id']] + 1, 3) : 1;
                                    $fine_key = "fine_amount_$offense_count";
                                    $offense_suffix = $offense_count == 1 ? 'st' : ($offense_count == 2 ? 'nd' : 'rd');
                                    $label = $v['violation_type'] . " - {$offense_count}{$offense_suffix} Offense (₱" . number_format($v[$fine_key], 2) . ")";
                                    $input_id = 'violation_' . $v['violation_type_id'];
                                    echo "<div class='form-check mb-2'>";
                                    echo "<input type='checkbox' class='form-check-input violation-checkbox' name='violations[]' value='" . (int)$v['violation_type_id'] . "' id='$input_id' data-offense='$offense_count'>";
                                    echo "<label class='form-check-label' for='$input_id'>" . htmlspecialchars($label) . "</label>";
                                    echo "</div>";
                                }
                            }
                            if (!$violations_found) {
                                echo "<p class='text-muted'>No violations available in this category.</p>";
                            }
                            echo "</div></div></div>";
                        }

                        // Display any remaining violations not yet categorized
                        $uncategorized = [];
                        foreach ($violation_types as $v) {
                            if (!in_array($v['violation_type_id'], $displayed_violations)) {
                                $uncategorized[] = $v;
                            }
                        }

                        if (!empty($uncategorized)) {
                            echo "<div class='accordion-item'>";
                            echo "<h2 class='accordion-header' id='heading-uncategorized'>";
                            echo "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-uncategorized' aria-expanded='false' aria-controls='collapse-uncategorized'>Other Violations</button>";
                            echo "</h2>";
                            echo "<div id='collapse-uncategorized' class='accordion-collapse collapse' aria-labelledby='heading-uncategorized' data-bs-parent='#violationsAccordion'>";
                            echo "<div class='accordion-body p-3'>";
                            foreach ($uncategorized as $v) {
                                $offense_count = isset($offense_counts[$v['violation_type_id']]) ? min((int)$offense_counts[$v['violation_type_id']] + 1, 3) : 1;
                                $fine_key = "fine_amount_$offense_count";
                                $offense_suffix = $offense_count == 1 ? 'st' : ($offense_count == 2 ? 'nd' : 'rd');
                                $label = $v['violation_type'] . " - {$offense_count}{$offense_suffix} Offense (₱" . number_format($v[$fine_key], 2) . ")";
                                $input_id = 'violation_' . $v['violation_type_id'];
                                echo "<div class='form-check mb-2'>";
                                echo "<input type='checkbox' class='form-check-input violation-checkbox' name='violations[]' value='" . (int)$v['violation_type_id'] . "' id='$input_id' data-offense='$offense_count'>";
                                echo "<label class='form-check-label' for='$input_id'>" . htmlspecialchars($label) . "</label>";
                                echo "</div>";
                            }
                            echo "</div></div></div>";
                        }
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-other">
                                <button class="accordion-button collapsed" type='button' data-bs-toggle='collapse' data-bs-target='#collapse-other' aria-expanded='false' aria-controls='collapse-other'>
                                    Other
                                </button>
                            </h2>
                            <div id="collapse-other" class="accordion-collapse collapse" aria-labelledby="heading-other" data-bs-parent="#violationsAccordion">
                                <div class="accordion-body p-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="other_violation" id="other_violation">
                                        <label class="form-check-label" for="other_violation">Other Violation</label>
                                    </div>
                                    <input type="text" name="other_violation_input" class="form-control" id="otherViolationInput" placeholder="Specify other violation">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 remarks">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Enter additional remarks"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <p>
                        All apprehensions are deemed admitted unless contested by filing a written contest at the Traffic Management Office within five (5) working days from date of issuance.
                        Failure to pay the corresponding penalty at the Municipal Treasury Office within fifteen (15) days from date of apprehension, shall be the ground for filing a formal complaint against you.
                        Likewise, a copy of this ticket shall be forwarded to concerned agencies for proper action/disposition.
                    </p>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-custom mt-3"><i class="fas fa-paper-plane me-2"></i>Submit Citation</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    // Global function for toggling Other Vehicle input
    function toggleOtherVehicle(value) {
        const otherInput = document.getElementById('otherVehicleInput');
        if (value === 'Other') {
            otherInput.style.display = 'block';
            otherInput.required = true;
            otherInput.focus();
        } else {
            otherInput.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const csrfTokenInput = document.getElementById('csrfToken');
        const otherViolationCheckbox = document.getElementById('other_violation');
        const otherViolationInput = document.getElementById('otherViolationInput');
        const vehicleTypeRadios = document.querySelectorAll('input[name="vehicle_type"]');
        const otherVehicleRadio = document.getElementById('othersVehicle');
        const otherVehicleInput = document.getElementById('otherVehicleInput');
        const hasLicenseCheckbox = document.getElementById('hasLicense');
        const licenseFields = document.querySelectorAll('.license-field');
        const barangaySelect = document.getElementById('barangaySelect');
        const otherBarangayInput = document.getElementById('otherBarangayInput');
        const municipalityDiv = document.getElementById('municipalityDiv');
        const provinceDiv = document.getElementById('provinceDiv');
        const dateOfBirthInput = document.getElementById('dateOfBirth');
        const ageField = document.getElementById('ageField');

        // === AUTOMATIC AGE CALCULATION ===
        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        // Helper function to find violation checkbox by label text
        function findViolationCheckboxByText(searchText) {
            const labels = document.querySelectorAll('.violation-checkbox + label');
            for (let label of labels) {
                if (label.textContent.includes(searchText)) {
                    return document.getElementById(label.getAttribute('for'));
                }
            }
            return null;
        }

        dateOfBirthInput.addEventListener('change', () => {
            if (dateOfBirthInput.value) {
                const age = calculateAge(dateOfBirthInput.value);
                if (age >= 0 && age <= 120) {
                    ageField.value = age;

                    // Auto-check minor violation if under 18
                    const noLicenseMinorCheckbox = findViolationCheckboxByText("NO DRIVER'S LICENSE / MINOR");
                    if (age < 18 && noLicenseMinorCheckbox) {
                        noLicenseMinorCheckbox.checked = true;
                    }
                } else {
                    ageField.value = '';
                    alert('Please enter a valid date of birth.');
                }
            } else {
                ageField.value = '';
            }
        });

        // Calculate age on page load if DOB exists
        if (dateOfBirthInput.value) {
            const age = calculateAge(dateOfBirthInput.value);
            if (age >= 0 && age <= 120) {
                ageField.value = age;
            }
        }

        // === AUTO-CHECK NO LICENSE VIOLATION ===
        const noLicenseViolationCheckbox = findViolationCheckboxByText("NO DRIVER'S LICENSE / MINOR");
        let licenseValidationPrompted = false;

        hasLicenseCheckbox.addEventListener('change', () => {
            const isChecked = hasLicenseCheckbox.checked;
            licenseFields.forEach(field => {
                field.style.display = isChecked ? 'block' : 'none';
                const inputs = field.querySelectorAll('input');
                inputs.forEach(input => {
                    input.required = isChecked;
                    if (!isChecked) {
                        input.value = '';
                        if (input.type === 'radio') input.checked = false;
                    }
                });
            });

            // Reset validation flag when license status changes
            licenseValidationPrompted = false;
        });

        // Prompt when user selects a Vehicle Type
        vehicleTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Check for no license violation after selecting vehicle type (with 3 second delay)
                if (!hasLicenseCheckbox.checked && !licenseValidationPrompted && noLicenseViolationCheckbox && !noLicenseViolationCheckbox.checked) {
                    licenseValidationPrompted = true;
                    setTimeout(() => {
                        const confirmAdd = confirm('The "Has License" checkbox is unchecked.\n\nDo you want to automatically add "NO DRIVER\'S LICENSE / MINOR" violation?');
                        if (confirmAdd) {
                            noLicenseViolationCheckbox.checked = true;
                            // Expand the accordion section containing this violation
                            const accordionBody = noLicenseViolationCheckbox.closest('.accordion-collapse');
                            if (accordionBody && !accordionBody.classList.contains('show')) {
                                new bootstrap.Collapse(accordionBody, { show: true });
                            }
                        }
                    }, 3000); // 3 second delay
                }
            });
        });

        // Auto-populate Municipality and Province
        barangaySelect.addEventListener('change', () => {
            const isOther = barangaySelect.value === 'Other';
            if (isOther) {
                otherBarangayInput.style.cssText = 'display: block !important; margin-top: 8px;';
                otherBarangayInput.required = true;
                otherBarangayInput.focus();
                municipalityDiv.style.display = 'block';
                provinceDiv.style.display = 'block';
                municipalityDiv.querySelector('input').value = '';
                provinceDiv.querySelector('input').value = '';
                municipalityDiv.querySelector('input').removeAttribute('readonly');
                provinceDiv.querySelector('input').removeAttribute('readonly');
            } else {
                otherBarangayInput.style.cssText = 'display: none !important; margin-top: 8px;';
                otherBarangayInput.required = false;
                otherBarangayInput.value = '';
                if (barangaySelect.value) {
                    municipalityDiv.style.display = 'block';
                    provinceDiv.style.display = 'block';
                    municipalityDiv.querySelector('input').value = 'Baggao';
                    provinceDiv.querySelector('input').value = 'Cagayan';
                    municipalityDiv.querySelector('input').setAttribute('readonly', true);
                    provinceDiv.querySelector('input').setAttribute('readonly', true);
                } else {
                    municipalityDiv.style.display = 'none';
                    provinceDiv.style.display = 'none';
                    municipalityDiv.querySelector('input').value = '';
                    provinceDiv.querySelector('input').value = '';
                }
            }
        });

        // Toggle DateTime button
        const toggleBtn = document.getElementById('toggleDateTime');
        const dateTimeInput = document.getElementById('apprehensionDateTime');
        let isAutoFilled = false;
        toggleBtn.addEventListener('click', () => {
            if (!isAutoFilled) {
                const now = new Date();
                const offset = now.getTimezoneOffset();
                now.setMinutes(now.getMinutes() - offset);
                dateTimeInput.value = now.toISOString().slice(0, 16);
                isAutoFilled = true;
                toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
                toggleBtn.classList.remove('btn-outline-secondary');
                toggleBtn.classList.add('btn-outline-danger');
            } else {
                dateTimeInput.value = '';
                isAutoFilled = false;
                toggleBtn.innerHTML = '<i class="fas fa-calendar-alt"></i>';
                toggleBtn.classList.remove('btn-outline-danger');
                toggleBtn.classList.add('btn-outline-secondary');
            }
        });

        // Show/hide Other Violation input
        if (otherViolationCheckbox && otherViolationInput) {
            otherViolationCheckbox.addEventListener('change', function() {
                console.log('Other Violation checkbox changed:', this.checked);
                if (this.checked) {
                    otherViolationInput.style.cssText = 'display: block !important; margin-top: 8px;';
                    otherViolationInput.required = true;
                    otherViolationInput.focus();
                } else {
                    otherViolationInput.style.cssText = 'display: none !important; margin-top: 8px;';
                    otherViolationInput.required = false;
                    otherViolationInput.value = '';
                }
            });
        } else {
            console.error('Other Violation elements not found');
        }

        // Show/hide Other Vehicle input (for radio buttons)
        console.log('Found vehicle type radios:', vehicleTypeRadios.length);
        console.log('Other vehicle input element:', otherVehicleInput);

        if (vehicleTypeRadios.length > 0 && otherVehicleInput) {
            vehicleTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    console.log('Vehicle type changed:', this.value);
                    if (this.value === 'Other') {
                        console.log('Other selected - showing input');
                        otherVehicleInput.style.cssText = 'display: block !important; margin-top: 8px;';
                        otherVehicleInput.required = true;
                        otherVehicleInput.focus();
                    } else {
                        console.log('Other NOT selected - hiding input');
                        otherVehicleInput.style.cssText = 'display: none !important; margin-top: 8px;';
                        otherVehicleInput.required = false;
                        otherVehicleInput.value = '';
                    }
                });
            });
        } else {
            console.error('Vehicle type radio buttons not found. Count:', vehicleTypeRadios.length, 'Input:', otherVehicleInput);
        }

        // Ensure only one license type
        const nonProfCheckbox = document.getElementById('nonProf');
        const profCheckbox = document.getElementById('prof');
        nonProfCheckbox.addEventListener('change', () => {
            if (nonProfCheckbox.checked) profCheckbox.checked = false;
        });
        profCheckbox.addEventListener('change', () => {
            if (profCheckbox.checked) nonProfCheckbox.checked = false;
        });

        // Form validation and submission
        const violationCheckboxes = document.querySelectorAll('input[name="violations[]"], input[name="other_violation"]');
        document.getElementById('citationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate vehicle type (radio buttons - required attribute handles this)
            const selectedVehicleType = document.querySelector('input[name="vehicle_type"]:checked');
            if (!selectedVehicleType) {
                alert('Please select a vehicle type.');
                return;
            }

            // If "Other" is selected, make sure the input field is filled
            if (selectedVehicleType.value === 'Other' && !otherVehicleInput.value.trim()) {
                alert('Please specify the other vehicle type.');
                otherVehicleInput.focus();
                return;
            }

            // Validate violations
            let violationSelected = false;
            const selectedViolations = [];
            violationCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    violationSelected = true;
                    if (checkbox.name === 'violations[]') {
                        selectedViolations.push(checkbox.value);
                    } else if (checkbox.name === 'other_violation' && otherViolationInput.value.trim()) {
                        selectedViolations.push(otherViolationInput.value.trim());
                    }
                }
            });
            if (!violationSelected) {
                alert('Please select at least one violation. If no violations are available, please contact the system administrator.');
                return;
            }

            const formData = new FormData(this);
            formData.append('csrf_token', csrfTokenInput.value);

            fetch('../api/insert_citation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    document.getElementById('citationForm').reset();
                    municipalityDiv.querySelector('input').value = 'Baggao';
                    provinceDiv.querySelector('input').value = 'Cagayan';
                    // Hide "Others" inputs
                    otherViolationInput.style.cssText = 'display: none !important; margin-top: 8px;';
                    otherVehicleInput.style.cssText = 'display: none !important; margin-top: 8px;';
                    otherBarangayInput.style.cssText = 'display: none !important; margin-top: 8px;';
                    otherViolationInput.required = false;
                    otherVehicleInput.required = false;
                    otherBarangayInput.required = false;
                    otherViolationInput.value = '';
                    otherVehicleInput.value = '';
                    otherBarangayInput.value = '';
                    hasLicenseCheckbox.checked = false;
                    licenseFields.forEach(field => {
                        field.style.display = 'none';
                        field.querySelectorAll('input').forEach(input => {
                            input.value = '';
                            if (input.type === 'radio') input.checked = false;
                            input.required = false;
                        });
                    });
                    isAutoFilled = false;
                    toggleBtn.innerHTML = '<i class="fas fa-calendar-alt"></i>';
                    toggleBtn.classList.remove('btn-outline-danger');
                    toggleBtn.classList.add('btn-outline-secondary');
                    // Reset age field
                    ageField.value = '';
                    if (data.new_csrf_token) {
                        csrfTokenInput.value = data.new_csrf_token;
                    }
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Error submitting form: ' + error.message);
            });
        });

        // Real-time form validation
        const requiredInputs = document.querySelectorAll('input[required], select[required]');
        requiredInputs.forEach(input => {
            input.addEventListener('input', () => {
                if (input.value.trim() === '') {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
        });
    });
</script>

</body>
</html>