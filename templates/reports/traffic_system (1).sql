-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 09:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `traffic_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `apprehending_officers`
--

CREATE TABLE `apprehending_officers` (
  `officer_id` int(11) NOT NULL,
  `officer_name` varchar(255) NOT NULL,
  `badge_number` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `apprehending_officers`
--

INSERT INTO `apprehending_officers` (`officer_id`, `officer_name`, `badge_number`, `position`, `is_active`, `created_at`) VALUES
(1, 'RICHMOND', NULL, 'ENFORCER', 1, '2025-11-17 10:19:12'),
(2, 'PNP TALLANG', NULL, 'SGT', 1, '2025-11-24 01:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `citations`
--

CREATE TABLE `citations` (
  `citation_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL DEFAULT 'Baggao',
  `province` varchar(100) NOT NULL DEFAULT 'Cagayan',
  `license_number` varchar(50) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT NULL,
  `plate_mv_engine_chassis_no` varchar(100) NOT NULL,
  `vehicle_description` text DEFAULT NULL,
  `apprehension_datetime` datetime NOT NULL,
  `place_of_apprehension` varchar(255) NOT NULL,
  `apprehension_officer` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','paid','contested','dismissed','void') NOT NULL DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `total_fine` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citations`
--

INSERT INTO `citations` (`citation_id`, `ticket_number`, `driver_id`, `last_name`, `first_name`, `middle_initial`, `suffix`, `date_of_birth`, `age`, `zone`, `barangay`, `municipality`, `province`, `license_number`, `license_type`, `plate_mv_engine_chassis_no`, `vehicle_description`, `apprehension_datetime`, `place_of_apprehension`, `apprehension_officer`, `remarks`, `status`, `payment_date`, `total_fine`, `created_at`, `updated_at`, `created_by`) VALUES
(10, '06102', 2, 'ROSETE', 'RICHMOND', '', '', NULL, NULL, '', 'Agaman Sur', 'Baggao', 'Cagayan', '', 'nonProf', 'UYTR', 'YELLOW', '2025-11-18 15:27:00', 'YTF', 'RICHMOND', '', 'pending', NULL, 500.00, '2025-11-18 15:27:14', '2025-11-25 14:39:04', NULL),
(11, '06103', 4, 'Mercado', 'Rogelio', 'G', NULL, '2025-11-25', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL, '5JK567', 'RIDER 150', '2025-11-25 09:03:00', 'SAN JOSE', 'PNP TALLANG', NULL, 'pending', NULL, 3000.00, '2025-11-25 09:06:33', '2025-11-25 09:06:33', NULL),
(12, '06104', 4, 'Mercado', 'Rogelio', 'G', NULL, '2025-11-25', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, 'nonProf', '5JK567', 'RIDER 150', '2025-11-25 14:49:00', 'SAN JOSE', 'PNP TALLANG', NULL, 'pending', NULL, 1000.00, '2025-11-25 14:49:12', '2025-11-25 14:49:12', NULL),
(13, '06105', 5, 'rosete', 'richmond', 'R', NULL, '1999-10-17', 26, '1', 'Asassi', 'Baggao', 'Cagayan', NULL, 'nonProf', '5JK567', 'RIDER 150', '2025-11-25 15:16:00', 'SAN JOSE', 'PNP TALLANG', NULL, 'pending', NULL, 500.00, '2025-11-25 15:16:32', '2025-11-25 15:16:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `citation_vehicles`
--

CREATE TABLE `citation_vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `citation_id` int(11) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citation_vehicles`
--

INSERT INTO `citation_vehicles` (`vehicle_id`, `citation_id`, `vehicle_type`, `created_at`) VALUES
(18, 10, 'Motorcycle', '2025-11-18 15:27:14'),
(19, 11, 'Motorcycle', '2025-11-25 09:06:33'),
(20, 12, 'Motorcycle', '2025-11-25 14:49:12'),
(21, 13, 'Motorcycle', '2025-11-25 15:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL DEFAULT 'Baggao',
  `province` varchar(100) NOT NULL DEFAULT 'Cagayan',
  `license_number` varchar(50) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `last_name`, `first_name`, `middle_initial`, `suffix`, `date_of_birth`, `age`, `zone`, `barangay`, `municipality`, `province`, `license_number`, `license_type`, `created_at`, `updated_at`) VALUES
(2, 'ROSETE', 'RICHMOND', '', '', NULL, NULL, '', 'Agaman Sur', 'Baggao', 'Cagayan', NULL, 'nonProf', '2025-11-18 15:26:38', '2025-11-25 09:06:22'),
(4, 'Mercado', 'Rogelio', 'G', NULL, '2025-11-25', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, 'nonProf', '2025-11-25 09:06:33', '2025-11-25 14:49:12'),
(5, 'rosete', 'richmond', 'R', NULL, '1999-10-17', 26, '1', 'Asassi', 'Baggao', 'Cagayan', NULL, 'nonProf', '2025-11-25 15:16:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `citation_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','check','online','gcash','paymaya','bank_transfer','money_order') NOT NULL DEFAULT 'cash',
  `payment_date` datetime NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL COMMENT 'Check number, transaction ID, etc.',
  `receipt_number` varchar(50) NOT NULL COMMENT 'Official Receipt number',
  `collected_by` int(11) NOT NULL COMMENT 'User ID of cashier/collector',
  `check_number` varchar(50) DEFAULT NULL,
  `check_bank` varchar(100) DEFAULT NULL,
  `check_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('completed','pending','failed','refunded','cancelled') DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores payment transactions for traffic citations';

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `citation_id`, `amount_paid`, `payment_method`, `payment_date`, `reference_number`, `receipt_number`, `collected_by`, `check_number`, `check_bank`, `check_date`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 10, 500.00, 'cash', '2025-11-25 02:22:13', NULL, 'OR-2025-000001', 1, NULL, NULL, NULL, 'Test payment created via create_test_payment.php', 'completed', '2025-11-25 09:22:13', '2025-11-25 09:22:13'),
(2, 11, 3000.00, 'cash', '2025-11-25 07:33:44', NULL, 'OR-2025-000002', 1, NULL, NULL, NULL, '', 'completed', '2025-11-25 14:33:44', '2025-11-25 14:33:44'),
(3, 12, 1000.00, 'cash', '2025-11-25 07:49:30', NULL, 'OR-2025-000003', 1, NULL, NULL, NULL, '', 'completed', '2025-11-25 14:49:30', '2025-11-25 14:49:30'),
(4, 13, 500.00, 'cash', '2025-11-25 08:17:06', NULL, 'OR-2025-000004', 1, NULL, NULL, NULL, '', 'completed', '2025-11-25 15:17:06', '2025-11-25 15:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_audit`
--

CREATE TABLE `payment_audit` (
  `audit_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `action` enum('created','updated','refunded','cancelled','voided') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `performed_by` int(11) NOT NULL,
  `performed_at` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for payment transactions';

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `receipt_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `generated_by` int(11) NOT NULL COMMENT 'User ID who generated the receipt',
  `printed_at` datetime DEFAULT NULL,
  `print_count` int(11) DEFAULT 0,
  `last_printed_by` int(11) DEFAULT NULL,
  `last_printed_at` datetime DEFAULT NULL,
  `status` enum('active','cancelled','void') DEFAULT 'active',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks receipt generation, printing, and cancellation';

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`receipt_id`, `payment_id`, `receipt_number`, `generated_at`, `generated_by`, `printed_at`, `print_count`, `last_printed_by`, `last_printed_at`, `status`, `cancellation_reason`, `cancelled_by`, `cancelled_at`) VALUES
(1, 1, 'OR-2025-000001', '2025-11-25 09:22:13', 1, NULL, 10, 1, '2025-11-25 14:14:21', 'active', NULL, NULL, NULL),
(2, 2, 'OR-2025-000002', '2025-11-25 14:33:44', 1, NULL, 0, NULL, NULL, 'active', NULL, NULL, NULL),
(3, 3, 'OR-2025-000003', '2025-11-25 14:49:30', 1, NULL, 0, NULL, NULL, 'active', NULL, NULL, NULL),
(4, 4, 'OR-2025-000004', '2025-11-25 15:17:06', 1, NULL, 4, 1, '2025-11-25 15:20:28', 'active', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receipt_sequence`
--

CREATE TABLE `receipt_sequence` (
  `id` int(11) NOT NULL DEFAULT 1,
  `current_year` int(11) NOT NULL,
  `current_number` int(11) NOT NULL DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `receipt_sequence`
--

INSERT INTO `receipt_sequence` (`id`, `current_year`, `current_number`, `last_updated`) VALUES
(1, 2025, 4, '2025-11-25 15:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin','enforcer','cashier') NOT NULL DEFAULT 'user' COMMENT 'User role: user=read-only, enforcer=field officer, cashier=payment processor, admin=full access',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'admin', '$2y$10$mmjBnDB0cU4krnO/uPuwF.Qs8Cja0Md.lHAcf2pGqFx3K0k/4nz8.', 'System Administrator', 'admin@traffic.gov', 'admin', 'active', '2025-11-25 16:29:41', '2025-11-17 13:23:47', '2025-11-25 16:29:41', NULL),
(2, 'rich', '$2y$10$t4YFwv7NpVvZcH7jlFNI5uYble6KlFP2Wx8vBw3wq7YcKMVe0q7Rq', 'richmond', 'richmondrosete19@gmail.com', 'cashier', 'active', '2025-11-25 16:30:09', '2025-11-25 14:12:51', '2025-11-25 16:30:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `violation_id` int(11) NOT NULL,
  `citation_id` int(11) NOT NULL,
  `violation_type_id` int(11) NOT NULL,
  `offense_count` int(11) NOT NULL DEFAULT 1,
  `fine_amount` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violations`
--

INSERT INTO `violations` (`violation_id`, `citation_id`, `violation_type_id`, `offense_count`, `fine_amount`, `created_at`) VALUES
(21, 10, 29, 2, 500.00, '2025-11-18 15:27:14'),
(22, 11, 5, 1, 2500.00, '2025-11-25 09:06:33'),
(23, 11, 28, 1, 500.00, '2025-11-25 09:06:33'),
(24, 12, 29, 1, 500.00, '2025-11-25 14:49:12'),
(25, 12, 28, 2, 500.00, '2025-11-25 14:49:12'),
(26, 13, 29, 1, 500.00, '2025-11-25 15:16:32');

--
-- Triggers `violations`
--
DELIMITER $$
CREATE TRIGGER `after_violation_insert_update_total` AFTER INSERT ON `violations` FOR EACH ROW BEGIN
    -- Update total fine on citation (different table, so this is OK)
    UPDATE citations
    SET total_fine = (SELECT COALESCE(SUM(fine_amount), 0) FROM violations WHERE citation_id = NEW.citation_id)
    WHERE citation_id = NEW.citation_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_violation_insert` BEFORE INSERT ON `violations` FOR EACH ROW BEGIN
    DECLARE fine DECIMAL(10,2);

    -- Get the appropriate fine based on offense count
    SELECT
        CASE
            WHEN NEW.offense_count = 1 THEN fine_amount_1
            WHEN NEW.offense_count = 2 THEN fine_amount_2
            ELSE fine_amount_3
        END INTO fine
    FROM violation_types
    WHERE violation_type_id = NEW.violation_type_id;

    -- Set the fine amount before insert (no UPDATE needed)
    SET NEW.fine_amount = fine;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `violation_types`
--

CREATE TABLE `violation_types` (
  `violation_type_id` int(11) NOT NULL,
  `violation_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fine_amount_1` decimal(10,2) NOT NULL DEFAULT 500.00,
  `fine_amount_2` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `fine_amount_3` decimal(10,2) NOT NULL DEFAULT 1500.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violation_types`
--

INSERT INTO `violation_types` (`violation_type_id`, `violation_type`, `description`, `fine_amount_1`, `fine_amount_2`, `fine_amount_3`, `is_active`, `created_at`) VALUES
(2, 'NO HELMET (DRIVER)', NULL, 150.00, 150.00, 150.00, 1, '2025-11-17 18:07:43'),
(3, 'NO HELMET (BACKRIDER)', NULL, 150.00, 150.00, 150.00, 1, '2025-11-17 18:07:43'),
(5, 'NO / EXPIRED VEHICLE REGISTRATION', NULL, 2500.00, 2500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(6, 'NO / DEFECTIVE PARTS & ACCESSORIES', NULL, 500.00, 500.00, 500.00, 1, '2025-11-17 18:07:43'),
(7, 'RECKLESS / ARROGANT DRIVING', NULL, 500.00, 750.00, 1000.00, 1, '2025-11-17 18:07:43'),
(8, 'DISREGARDING TRAFFIC SIGN', NULL, 150.00, 150.00, 150.00, 1, '2025-11-17 18:07:43'),
(9, 'ILLEGAL MODIFICATION', NULL, 500.00, 500.00, 500.00, 1, '2025-11-17 18:07:43'),
(10, 'PASSENGER ON TOP OF THE VEHICLE', NULL, 150.00, 150.00, 150.00, 1, '2025-11-17 18:07:43'),
(11, 'NOISY MUFFLER (98DB ABOVE)', NULL, 2500.00, 2500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(12, 'NO MUFFLER ATTACHED', NULL, 2500.00, 2500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(13, 'ILLEGAL PARKING', NULL, 200.00, 500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(14, 'ROAD OBSTRUCTION', NULL, 200.00, 500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(15, 'BLOCKING PEDESTRIAN LANE', NULL, 200.00, 500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(16, 'LOADING/UNLOADING IN PROHIBITED ZONE', NULL, 200.00, 500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(17, 'DOUBLE PARKING', NULL, 200.00, 500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(18, 'DRUNK DRIVING', NULL, 500.00, 1000.00, 1500.00, 1, '2025-11-17 18:07:43'),
(19, 'COLORUM OPERATION', NULL, 2500.00, 3000.00, 3000.00, 1, '2025-11-17 18:07:43'),
(20, 'NO TRASHBIN', NULL, 1000.00, 2000.00, 2500.00, 1, '2025-11-17 18:07:43'),
(21, 'DRIVING IN SHORT / SANDO', NULL, 200.00, 500.00, 1000.00, 1, '2025-11-17 18:07:43'),
(22, 'OVERLOADED PASSENGER', NULL, 500.00, 750.00, 1000.00, 1, '2025-11-17 18:07:43'),
(23, 'OVER CHARGING / UNDER CHARGING', NULL, 500.00, 750.00, 1000.00, 1, '2025-11-17 18:07:43'),
(24, 'REFUSAL TO CONVEY PASSENGER/S', NULL, 500.00, 750.00, 1000.00, 1, '2025-11-17 18:07:43'),
(25, 'DRAG RACING', NULL, 1000.00, 1500.00, 2500.00, 1, '2025-11-17 18:07:43'),
(26, 'NO ENHANCED OPLAN VISA STICKER', NULL, 300.00, 300.00, 300.00, 1, '2025-11-17 18:07:43'),
(27, 'FAILURE TO PRESENT E-OV MATCH CARD', NULL, 200.00, 200.00, 200.00, 1, '2025-11-17 18:07:43'),
(28, 'MINOR', '', 500.00, 500.00, 500.00, 1, '2025-11-18 08:22:51'),
(29, 'NO DRIVER\'S LICENSE', '', 500.00, 500.00, 500.00, 1, '2025-11-18 14:49:05');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_citation_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_citation_summary` (
`citation_id` int(11)
,`ticket_number` varchar(50)
,`driver_name` varchar(213)
,`license_number` varchar(50)
,`plate_mv_engine_chassis_no` varchar(100)
,`apprehension_datetime` datetime
,`place_of_apprehension` varchar(255)
,`status` enum('pending','paid','contested','dismissed','void')
,`total_fine` decimal(10,2)
,`violation_count` bigint(21)
,`created_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_driver_offenses`
-- (See below for the actual view)
--
CREATE TABLE `vw_driver_offenses` (
`driver_id` int(11)
,`driver_name` varchar(202)
,`license_number` varchar(50)
,`total_citations` bigint(21)
,`total_violations` bigint(21)
,`total_fines` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_citation_summary`
--
DROP TABLE IF EXISTS `vw_citation_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_citation_summary`  AS SELECT `c`.`citation_id` AS `citation_id`, `c`.`ticket_number` AS `ticket_number`, concat(`c`.`last_name`,', ',`c`.`first_name`,' ',coalesce(`c`.`middle_initial`,'')) AS `driver_name`, `c`.`license_number` AS `license_number`, `c`.`plate_mv_engine_chassis_no` AS `plate_mv_engine_chassis_no`, `c`.`apprehension_datetime` AS `apprehension_datetime`, `c`.`place_of_apprehension` AS `place_of_apprehension`, `c`.`status` AS `status`, `c`.`total_fine` AS `total_fine`, count(`v`.`violation_id`) AS `violation_count`, `c`.`created_at` AS `created_at` FROM (`citations` `c` left join `violations` `v` on(`c`.`citation_id` = `v`.`citation_id`)) GROUP BY `c`.`citation_id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_driver_offenses`
--
DROP TABLE IF EXISTS `vw_driver_offenses`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_driver_offenses`  AS SELECT `d`.`driver_id` AS `driver_id`, concat(`d`.`last_name`,', ',`d`.`first_name`) AS `driver_name`, `d`.`license_number` AS `license_number`, count(distinct `c`.`citation_id`) AS `total_citations`, count(`v`.`violation_id`) AS `total_violations`, sum(`c`.`total_fine`) AS `total_fines` FROM ((`drivers` `d` left join `citations` `c` on(`d`.`driver_id` = `c`.`driver_id`)) left join `violations` `v` on(`c`.`citation_id` = `v`.`citation_id`)) GROUP BY `d`.`driver_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apprehending_officers`
--
ALTER TABLE `apprehending_officers`
  ADD PRIMARY KEY (`officer_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `citations`
--
ALTER TABLE `citations`
  ADD PRIMARY KEY (`citation_id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_ticket` (`ticket_number`),
  ADD KEY `idx_driver` (`driver_id`),
  ADD KEY `idx_datetime` (`apprehension_datetime`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_date_status` (`apprehension_datetime`,`status`);

--
-- Indexes for table `citation_vehicles`
--
ALTER TABLE `citation_vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD KEY `idx_citation` (`citation_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_license` (`license_number`),
  ADD KEY `idx_name` (`last_name`,`first_name`),
  ADD KEY `idx_barangay` (`barangay`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_citation` (`citation_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_collected_by` (`collected_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- Indexes for table `payment_audit`
--
ALTER TABLE `payment_audit`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_performed_at` (`performed_at`),
  ADD KEY `idx_performed_by` (`performed_by`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `last_printed_by` (`last_printed_by`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_generated_at` (`generated_at`);

--
-- Indexes for table `receipt_sequence`
--
ALTER TABLE `receipt_sequence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`violation_id`),
  ADD UNIQUE KEY `unique_citation_violation` (`citation_id`,`violation_type_id`),
  ADD KEY `idx_citation` (`citation_id`),
  ADD KEY `idx_violation_type` (`violation_type_id`),
  ADD KEY `idx_offense_count` (`offense_count`);

--
-- Indexes for table `violation_types`
--
ALTER TABLE `violation_types`
  ADD PRIMARY KEY (`violation_type_id`),
  ADD UNIQUE KEY `violation_type` (`violation_type`),
  ADD KEY `idx_violation_type` (`violation_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apprehending_officers`
--
ALTER TABLE `apprehending_officers`
  MODIFY `officer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `citations`
--
ALTER TABLE `citations`
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `citation_vehicles`
--
ALTER TABLE `citation_vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_audit`
--
ALTER TABLE `payment_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `violation_types`
--
ALTER TABLE `violation_types`
  MODIFY `violation_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `citations`
--
ALTER TABLE `citations`
  ADD CONSTRAINT `citations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `citations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `citation_vehicles`
--
ALTER TABLE `citation_vehicles`
  ADD CONSTRAINT `citation_vehicles_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_audit`
--
ALTER TABLE `payment_audit`
  ADD CONSTRAINT `payment_audit_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`),
  ADD CONSTRAINT `payment_audit_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`),
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `receipts_ibfk_3` FOREIGN KEY (`last_printed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `receipts_ibfk_4` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `violations`
--
ALTER TABLE `violations`
  ADD CONSTRAINT `violations_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`violation_type_id`) REFERENCES `violation_types` (`violation_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
