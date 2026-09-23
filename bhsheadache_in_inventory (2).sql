-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 23, 2026 at 05:33 AM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bhsheadache_in_inventory`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`bhsheadache`@`localhost` PROCEDURE `GetItemTypesByCategory` (IN `p_category_id` INT)   BEGIN
    SELECT 
        it.id,
        it.name,
        it.category_id,
        it.sub_category_id,
        it.description,
        it.icon,
        (SELECT COUNT(*) FROM items i WHERE i.type_id = it.id) as usage_count
    FROM item_types it
    WHERE it.is_active = 1 
        AND (p_category_id IS NULL OR it.category_id = p_category_id)
    ORDER BY it.sort_order ASC, it.name ASC;
END$$

CREATE DEFINER=`bhsheadache`@`localhost` PROCEDURE `GetItemTypesBySubCategory` (IN `p_sub_category_id` INT)   BEGIN
    SELECT 
        it.id,
        it.name,
        it.category_id,
        c.name as category_name,
        it.sub_category_id,
        sc.name as sub_category_name,
        it.description,
        it.icon
    FROM item_types it
    LEFT JOIN categories c ON it.category_id = c.id
    LEFT JOIN categories sc ON it.sub_category_id = sc.id
    WHERE it.is_active = 1 
        AND (p_sub_category_id IS NULL OR it.sub_category_id = p_sub_category_id)
    ORDER BY it.sort_order ASC, it.name ASC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accessories_requests`
--

CREATE TABLE `accessories_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `accessory_type` varchar(50) NOT NULL,
  `accessory_name` varchar(200) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `brand_preference` varchar(100) DEFAULT NULL,
  `color_preference` varchar(50) DEFAULT NULL,
  `urgency` enum('normal','urgent','emergency') DEFAULT 'normal',
  `reason` text NOT NULL,
  `preferred_model` varchar(200) DEFAULT NULL,
  `budget_approval` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allocated_item_id` int(11) DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `delivery_status` enum('pending','approved','delivered','cancelled') DEFAULT 'pending',
  `allocated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `assignment_no` varchar(50) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `status` enum('assigned','returned','damaged','replaced') DEFAULT 'assigned',
  `notes` text DEFAULT NULL,
  `source` varchar(20) DEFAULT 'admin',
  `barcode_path` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `return_request_id` int(11) DEFAULT NULL,
  `return_status` enum('active','return_requested','returned','rejected') DEFAULT 'active',
  `returned_date` date DEFAULT NULL,
  `return_approved_by` int(11) DEFAULT NULL,
  `stock_updated` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `assignment_no`, `employee_id`, `item_id`, `quantity`, `assigned_date`, `expected_return_date`, `status`, `notes`, `source`, `barcode_path`, `assigned_by`, `created_at`, `updated_at`, `return_request_id`, `return_status`, `returned_date`, `return_approved_by`, `stock_updated`) VALUES
(64, 'ASN-000001', 767, 126, 1, '2026-08-24', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000001.png', 1, '2026-08-23 11:40:46', '2026-08-24 03:29:45', NULL, 'active', NULL, NULL, 0),
(65, 'ASN-000002', 288, 124, 1, '2026-08-24', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000002.png', 6, '2026-08-24 03:27:18', '2026-08-24 03:27:18', NULL, 'active', NULL, NULL, 0),
(66, 'ASN-000003', 288, 125, 1, '2026-08-24', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000003.png', 6, '2026-08-24 03:29:19', '2026-08-24 03:29:19', NULL, 'active', NULL, NULL, 0),
(67, 'ASN-000004', 3377, 127, 1, '2026-08-24', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000004.png', 6, '2026-08-24 04:24:06', '2026-08-24 04:24:06', NULL, 'active', NULL, NULL, 0),
(68, 'ASN-000005', 3378, 128, 1, '2026-08-25', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000005.png', 6, '2026-08-30 04:13:52', '2026-08-30 04:13:52', NULL, 'active', NULL, NULL, 0),
(69, 'ASN-000006', 415, 129, 1, '2026-08-30', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000006.png', 6, '2026-08-30 07:10:03', '2026-08-30 07:10:03', NULL, 'active', NULL, NULL, 0),
(70, 'ASN-000007', 3138, 130, 1, '2026-09-17', NULL, 'assigned', '', 'admin', 'uploads/barcodes/barcode_ASN-000007.png', 6, '2026-09-17 03:52:22', '2026-09-17 03:52:22', NULL, 'active', NULL, NULL, 0),
(71, 'ASN-20260921-9196', 110, 131, 1, '2026-09-21', NULL, 'returned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:29:56', '2026-09-21 03:37:28', 19, 'returned', '2026-09-21', 4, 1),
(72, 'ASN-20260921-5314', 490, 132, 1, '2026-09-21', NULL, 'assigned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:43:45', NULL, NULL, 'active', NULL, NULL, 0),
(73, 'ASN-20260921-2879', 490, 133, 1, '2023-01-01', NULL, 'assigned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:44:05', NULL, NULL, 'active', NULL, NULL, 0),
(74, 'ASN-20260921-2587', 490, 134, 1, '2026-09-21', NULL, 'assigned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:44:35', NULL, NULL, 'active', NULL, NULL, 0),
(75, 'ASN-20260921-5719', 490, 135, 1, '2026-09-21', NULL, 'assigned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:45:03', NULL, NULL, 'active', NULL, NULL, 0),
(76, 'ASN-20260921-1036', 490, 136, 1, '2026-09-21', NULL, 'assigned', 'Device added from unlisted submission. NEW item created.', 'admin', NULL, 4, '2026-09-21 03:45:21', NULL, NULL, 'active', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `assign_device_requests`
--

CREATE TABLE `assign_device_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `required_specifications` text DEFAULT NULL,
  `reason` text NOT NULL,
  `project_duration` varchar(50) DEFAULT NULL,
  `supervisor_approval` tinyint(1) DEFAULT 0,
  `urgent_requirement` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `stock_in_id` int(11) DEFAULT NULL,
  `bill_no` varchar(50) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','paid','partial') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_mode` enum('cash','cheque','bank_transfer') DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `cash_register_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bill_attachment` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `payment_acknowledgement_no` varchar(50) DEFAULT NULL,
  `paid_slip_path` varchar(255) DEFAULT NULL,
  `payment_acknowledgement_date` datetime DEFAULT NULL,
  `bill_received_date` date DEFAULT NULL,
  `stock_items_count` int(11) DEFAULT 0,
  `payment_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_payments`
--

CREATE TABLE `bill_payments` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` enum('cash','cheque','bank_transfer') DEFAULT 'cash',
  `cheque_no` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Dell', '', NULL, 1, '2026-05-14 15:09:43', NULL),
(2, 'HP', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(3, 'Lenovo', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(4, 'Apple', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(5, 'Microsoft', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(6, 'Samsung', '', NULL, 1, '2026-05-14 15:09:43', '2026-05-25 17:56:24'),
(7, 'LG', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(8, 'Sony', '', NULL, 1, '2026-05-14 15:09:43', '2026-05-25 17:56:12'),
(9, 'Acer', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(10, 'Asus', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(11, 'Cisco', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(12, 'Netgear', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(13, 'TP-Link', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(14, 'Brother', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(15, 'Canon', NULL, NULL, 1, '2026-05-14 15:09:43', NULL),
(17, 'Test 001', 'Test 001', NULL, 1, '2026-05-25 17:55:34', '2026-05-25 17:55:51'),
(18, 'Esonic', '', NULL, 1, '2026-06-15 05:02:06', NULL),
(19, 'Yealink', '', NULL, 1, '2026-06-16 03:07:19', NULL),
(20, 'Checking 777', 'Checking 7777', NULL, 1, '2026-08-04 07:49:14', NULL),
(21, 'Apolle', '', NULL, 1, '2026-08-11 04:29:27', NULL),
(22, 'A4tech', '', NULL, 1, '2026-08-13 10:59:28', NULL),
(23, 'DINSTAR', '', NULL, 1, '2026-08-17 04:04:14', NULL),
(24, 'Dahua', 'CCTV Camera', NULL, 1, '2026-08-19 04:16:20', NULL),
(25, 'Logitech', '', NULL, 1, '2026-08-23 10:53:17', NULL),
(26, 'Toshiba', '', NULL, 1, '2026-08-24 03:52:02', NULL),
(27, 'Clone PC', '', NULL, 1, '2026-08-30 03:42:15', NULL),
(28, 'Epson', '', NULL, 1, '2026-09-17 03:46:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cash_register`
--

CREATE TABLE `cash_register` (
  `id` int(11) NOT NULL,
  `month_year` date NOT NULL,
  `opening_balance` decimal(12,2) DEFAULT 0.00,
  `monthly_budget` decimal(12,2) DEFAULT 0.00,
  `total_cash_in` decimal(12,2) DEFAULT 0.00,
  `total_cash_out` decimal(12,2) DEFAULT 0.00,
  `total_purchases` decimal(12,2) DEFAULT 0.00,
  `closing_balance` decimal(12,2) DEFAULT 0.00,
  `remaining_balance` decimal(12,2) DEFAULT 0.00,
  `is_closed` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `closed_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lock_reason` varchar(255) DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closing_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_transactions`
--

CREATE TABLE `cash_register_transactions` (
  `id` int(11) NOT NULL,
  `cash_register_id` int(11) NOT NULL,
  `transaction_type` enum('cash_in','cash_out','purchase','bill_payment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `is_active`, `created_at`) VALUES
(55, 'CCTV & Surveillance', NULL, 1, '2026-08-19 04:35:33'),
(56, 'Security & Access Control', 55, 1, '2026-08-19 04:36:05'),
(57, 'Headphone/Earphone/Speaker', 59, 1, '2026-08-23 08:33:58'),
(58, 'Computer Accessories', NULL, 1, '2026-08-23 10:55:15'),
(59, 'Multimedia Items', NULL, 1, '2026-08-30 07:19:58'),
(60, 'Printer', NULL, 1, '2026-09-16 10:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `damaged_items`
--

CREATE TABLE `damaged_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `damage_date` date NOT NULL,
  `damage_type` enum('physical','technical','other') DEFAULT 'physical',
  `damage_description` text DEFAULT NULL,
  `status` enum('pending','repaired','disposed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `damages`
--

CREATE TABLE `damages` (
  `id` int(11) NOT NULL,
  `damage_no` varchar(50) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `damage_type` enum('physical','functional','liquid','electrical','other') DEFAULT 'physical',
  `damage_severity` enum('minor','moderate','severe','critical') DEFAULT 'minor',
  `damage_description` text NOT NULL,
  `estimated_cost` decimal(12,2) DEFAULT 0.00,
  `actual_cost` decimal(12,2) DEFAULT 0.00,
  `damage_date` date NOT NULL,
  `reported_date` date NOT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','repaired','replaced','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `repair_notes` text DEFAULT NULL,
  `replacement_item_id` int(11) DEFAULT NULL,
  `repaired_by` varchar(100) DEFAULT NULL,
  `repaired_date` date DEFAULT NULL,
  `attachment_file` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_logs`
--

CREATE TABLE `delivery_logs` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_logs`
--

INSERT INTO `delivery_logs` (`id`, `transfer_id`, `action`, `status_from`, `status_to`, `notes`, `performed_by`, `created_at`) VALUES
(18, 10, 'created', NULL, 'pending', NULL, 6, '2026-09-17 03:57:11');

-- --------------------------------------------------------

--
-- Table structure for table `device_assignment_items`
--

CREATE TABLE `device_assignment_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `device_type` varchar(50) NOT NULL,
  `device_name` varchar(200) DEFAULT NULL,
  `custom_device_name` varchar(200) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `specification` text DEFAULT NULL,
  `urgency` enum('normal','urgent','emergency') DEFAULT 'normal',
  `reason` text DEFAULT NULL,
  `status` enum('pending','allocated','delivered','cancelled') DEFAULT 'pending',
  `allocated_item_id` int(11) DEFAULT NULL,
  `allocation_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_assignment_requests`
--

CREATE TABLE `device_assignment_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assignment_date` date NOT NULL,
  `purpose` text DEFAULT NULL,
  `supervisor_approved` tinyint(1) DEFAULT 0,
  `supervisor_name` varchar(100) DEFAULT NULL,
  `supervisor_sign_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','under_observation') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_ownership_verifications`
--

CREATE TABLE `device_ownership_verifications` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `employee_confirmation` tinyint(1) DEFAULT 0,
  `confirmation_date` datetime DEFAULT NULL,
  `it_verification_status` enum('pending','verified','disputed') DEFAULT 'pending',
  `it_verification_notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_transfers`
--

CREATE TABLE `device_transfers` (
  `id` int(11) NOT NULL,
  `transfer_no` varchar(50) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_location` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `from_department` varchar(100) DEFAULT NULL,
  `from_address` text DEFAULT NULL,
  `to_location` varchar(100) NOT NULL,
  `to_department` varchar(100) DEFAULT NULL,
  `to_attn` varchar(100) DEFAULT NULL,
  `to_address` text DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `employee_name` varchar(200) DEFAULT NULL,
  `employee_pf_no` varchar(50) DEFAULT NULL,
  `employee_designation` varchar(200) DEFAULT NULL,
  `employee_department` varchar(200) DEFAULT NULL,
  `employee_phone` varchar(50) DEFAULT NULL,
  `employee_email` varchar(100) DEFAULT NULL,
  `delivery_location` varchar(200) DEFAULT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `serial_numbers` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('draft','pending','approved','dispatched','delivered','cancelled') DEFAULT 'draft',
  `delivery_status` enum('pending','processing','dispatched','delivered','cancelled') DEFAULT 'pending',
  `tracking_no` varchar(100) DEFAULT NULL,
  `dispatched_by` varchar(100) DEFAULT NULL,
  `dispatched_date` date DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `handled_by` varchar(100) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachment_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_transfers`
--

INSERT INTO `device_transfers` (`id`, `transfer_no`, `transfer_date`, `from_location`, `department`, `from_department`, `from_address`, `to_location`, `to_department`, `to_attn`, `to_address`, `product_name`, `item_id`, `assignment_id`, `employee_id`, `employee_name`, `employee_pf_no`, `employee_designation`, `employee_department`, `employee_phone`, `employee_email`, `delivery_location`, `product_type`, `quantity`, `serial_numbers`, `description`, `reason`, `status`, `delivery_status`, `tracking_no`, `dispatched_by`, `dispatched_date`, `delivered_date`, `handled_by`, `received_by`, `received_date`, `notes`, `attachment_count`, `created_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(10, 'DLV-20260917-0676', '2026-09-17', 'IT Department', NULL, 'Information Technology', '660 Washpur, PO: Shyamlapur, PS: Hazaribagh, Dhaka 1310', 'Engineering', '', 'Sheikh Abid Hasan', 'Engineering, FU5, Factory', 'Printer', 130, 70, 3138, 'Sheikh Abid Hasan', '360021', 'Deputy Manager, Engineering', 'Engineering', '01989993973', 'abid.engg10@gmail.com', NULL, '', 1, 'XB4P017969', 'Epson Ecotank L11050 A3 Color Printer', '', 'pending', 'pending', 'TRK-20260917-4850', NULL, NULL, NULL, 'Musabbir zami', NULL, NULL, '', 0, 6, NULL, '2026-09-17 03:57:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_types`
--

CREATE TABLE `device_types` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_types`
--

INSERT INTO `device_types` (`id`, `category`, `name`, `is_active`, `sort_order`) VALUES
(1, 'computing', 'Laptop', 1, 1),
(2, 'computing', 'Desktop', 1, 2),
(3, 'computing', 'All-in-One PC', 1, 3),
(4, 'computing', 'Tablet', 1, 4),
(5, 'computing', 'Chromebook', 1, 5),
(6, 'printing', 'Printer', 1, 10),
(7, 'printing', 'Scanner', 1, 11),
(8, 'printing', 'Multifunction Printer', 1, 12),
(9, 'printing', 'Label Printer', 1, 13),
(10, 'printing', 'Plotter', 1, 14),
(11, 'networking', 'Router', 1, 20),
(12, 'networking', 'Switch', 1, 21),
(13, 'networking', 'Modem', 1, 22),
(14, 'networking', 'Access Point', 1, 23),
(15, 'networking', 'Firewall', 1, 24),
(16, 'telecom', 'IP Phone', 1, 30),
(17, 'telecom', 'Conference Phone', 1, 31),
(18, 'telecom', 'Headset', 1, 32),
(19, 'peripheral', 'Monitor', 1, 40),
(20, 'peripheral', 'Keyboard', 1, 41),
(21, 'peripheral', 'Mouse', 1, 42),
(22, 'peripheral', 'Docking Station', 1, 43),
(23, 'peripheral', 'Webcam', 1, 44),
(24, 'peripheral', 'Speaker', 1, 45),
(25, 'power', 'UPS', 1, 50),
(26, 'power', 'Power Adapter', 1, 51),
(27, 'power', 'Extension Cord', 1, 52),
(28, 'cable', 'HDMI Cable', 1, 60),
(29, 'cable', 'USB Cable', 1, 61),
(30, 'cable', 'Network Cable', 1, 62),
(31, 'cable', 'DisplayPort Cable', 1, 63),
(32, 'other', 'Other', 1, 999);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_purchases`
--

CREATE TABLE `doctor_purchases` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `doctor_specialization` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `support_contact` varchar(100) DEFAULT NULL,
  `warranty_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `pf_no` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `job_location` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(1, '100003', 'Md. Moidul Islam', 'General Manager, CDD', 'Corporate Office', 'UniMed Limited', 'Core Diagnostic Division', '01929993029', 'moidul.islam@unigroup-bd.com', '1996-10-01', 1, '2026-05-24 18:00:00'),
(2, '100006', 'Md. Akramul Kabir', 'Director, Medical Devices', 'Corporate Office', 'UniMed Limited', 'Diagnostic', '01929993030', 'akramul.kabir@unigroup-bd.com', '1996-08-01', 1, '2026-05-24 18:00:00'),
(3, '101433', 'Md. Mohiuddin', 'Protocol Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993047', 'mohi.uddin@unigroup-bd.com', '2005-10-22', 1, '2026-05-24 18:00:00'),
(4, '102516', 'Md. Enamul Haque', 'Assistant General Manager, Service', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993039', 'enamul.haque2@unigroup-bd.com', '2009-01-27', 1, '2026-05-24 18:00:00'),
(5, '102550', 'Syed Tanveer Ahmed', 'Assistant General Manager, Sales', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993346', 'tanver.ahmed@unigroup-bd.com', '2010-09-20', 1, '2026-05-24 18:00:00'),
(6, '102570', 'Shekh Shakirul Islam', 'Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993310', 'shakirul.islam@unigroup-bd.com', '2011-04-02', 1, '2026-05-24 18:00:00'),
(7, '102572', 'Md. Zulfiker Jahirye', 'Assistant General Manager, Sales & Service', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993484', 'zulfiker.jahirye@unigroup-bd.com', '2011-04-01', 1, '2026-05-24 18:00:00'),
(8, '102587', 'Md. Bashir Uddin', 'Deputy Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993359', 'basir.ahmed@unigroup-bd.com', '2011-12-20', 1, '2026-05-24 18:00:00'),
(9, '102599', 'Md. Arafat Hossain', 'Senior Sales Representative', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993429', 'arafathossain1996@gmail.com', '2012-05-24', 1, '2026-05-24 18:00:00'),
(10, '102604', 'Md. Anuwar Ullah Chowdhury', 'Assistant General Manager, Sales', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993036', 'anuwarullah.chowdhury@unigroup-bd.com', '2012-09-12', 1, '2026-05-24 18:00:00'),
(11, '102640', 'Md. Tanzil Hossen', 'Assistant Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993114', 'tanzil.hossin@unigroup-bd.com', '2013-09-01', 1, '2026-05-24 18:00:00'),
(12, '102670', 'Bapy Kumar Sikdar', 'Deputy Manager, Application', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994243', 'bapy.kumar@unigroup-bd.com', '2015-09-01', 1, '2026-05-24 18:00:00'),
(13, '102671', 'Md. Osman Gani', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994241', 'osman.unimed@gmail.com', '2015-09-01', 1, '2026-05-24 18:00:00'),
(14, '102692', 'Md. Yamin Molla', 'Senior Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994231', 'mdyaminm@gmail.com', '2015-08-18', 1, '2026-05-24 18:00:00'),
(15, '102693', 'Mirza Asaduzzaman', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994232', 'mirzashuvo84@yahoo.com', '2015-08-18', 1, '2026-05-24 18:00:00'),
(16, '102696', 'Md. Asif Karim', 'Senior Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994235', 'asif.karim@unigroup-bd.com', '2015-08-18', 1, '2026-05-24 18:00:00'),
(17, '102706', 'Ronadhi Biswas', 'Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993470', 'ronadhi.biswas@unigroup-bd.com', '2015-10-29', 1, '2026-05-24 18:00:00'),
(18, '102720', 'Md. Amdadshah Fakir', 'Area Sales Executive', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994234', 'amdadshah87@gmail.com', '2015-12-01', 1, '2026-05-24 18:00:00'),
(19, '102767', 'Abu Sayed Talukder', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993043', 'sayed.talukder08@gmail.com', '2016-05-08', 1, '2026-05-24 18:00:00'),
(20, '102772', 'Imtiaz Noor Rahman Chowdhury', 'Deputy Manager, Sales & Service', 'Chattogram', 'UniMed Limited', 'Core Diagnostic Division', '01929993042', 'imtiaz.noor@unigroup-bd.com', '2016-05-02', 1, '2026-05-24 18:00:00'),
(21, '102773', 'Md. Maruf Hossan', 'Deputy Manager, Service', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993244', 'maruf.hossan@unigroup-bd.com', '2016-05-02', 1, '2026-05-24 18:00:00'),
(22, '102789', 'Hossain Ahmed  Kabir', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994536', 'kabir.unimed@gmail.com', '2016-10-04', 1, '2026-05-24 18:00:00'),
(23, '102793', 'Md. Jhahid Ikbul', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994533', 'jhahid.unimed@gmail.com', '2016-09-20', 1, '2026-05-24 18:00:00'),
(24, '102795', 'Muhammad Ershad Hossain', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994537', 'ershadunimed2001@gmail.com', '2016-10-04', 1, '2026-05-24 18:00:00'),
(25, '102797', 'Md. Mehdi Hasan Bappy', 'Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994529', 'mehdibappy9@gmail.com', '2016-10-16', 1, '2026-05-24 18:00:00'),
(26, '102869', 'Md. Abu Saeed', 'Senior Distribution Officer', 'Jashore Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989993966', 'abu.saeed@unigroup-bd.com', '2017-09-23', 1, '2026-05-24 18:00:00'),
(27, '102890', 'Md. Nasim Babu', 'Senior Area Sales Manager', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994846', 'nasim.unimed@gmail.com', '2017-12-13', 1, '2026-05-24 18:00:00'),
(28, '102902', 'Md. Fahimur Rahman', 'Assistant Manager, Application', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994952', 'fahim.rahman@unigroup-bd.com', '2018-02-10', 1, '2026-05-24 18:00:00'),
(29, '102931', 'Md. Minhazur Rahman', 'Assistant Manager, Sales & Application', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994177', 'minhaz.rahman@unigroup-bd.com', '2018-08-06', 1, '2026-05-24 18:00:00'),
(30, '102955', 'Md. Mahedi Hasan', 'Assistant Manager, Sales & Application', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994844', 'mahedi.hasan@unigroup-bd.com', '2018-12-15', 1, '2026-05-24 18:00:00'),
(31, '102983', 'Md. Mijanur Rahaman', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989995219', 'mizanunimed19@gmail.com', '2019-03-06', 1, '2026-05-24 18:00:00'),
(32, '102992', 'Md. Rakibul Hasan Bappy', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994332', 'rakibul.hasan@unigroup-bd.com', '2019-07-01', 1, '2026-05-24 18:00:00'),
(33, '102994', 'Md. Irfan Hossain', 'Assistant Manager, Application', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994436', 'Irfan.hossain@unigroup-bd.com', '2019-07-11', 1, '2026-05-24 18:00:00'),
(34, '102999', 'Dipak Bhakta', 'Area Sales Manager', 'Chattogram', 'UniMed Limited', 'Diagnostic', '01929993362', 'dipak.unimed@gmail.com', '2019-09-01', 1, '2026-05-24 18:00:00'),
(35, '103010', 'Mohammad Jamal Uddin', 'Executive Director, Diagnostic & Medical Devices', 'Corporate Office', 'UniMed Limited', 'Diagnostic', '01929993027', 'jamal.uddin@unigroup-bd.com', '1996-04-03', 1, '2026-05-24 18:00:00'),
(36, '103102', 'Md. Ariful Islam', 'Senior Sales & Service Engineer', 'Bogura', 'UniMed Limited', 'Core Diagnostic Division', '01989996745', 'ariful.islam@unigroup-bd.com', '2019-12-12', 1, '2026-05-24 18:00:00'),
(37, '103115', 'Md. Imran Talukder', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994571', 'imrantalukder.unimed@gmail.com', '2019-03-01', 1, '2026-05-24 18:00:00'),
(38, '103116', 'Md. Jahid Iqbal', 'Senior Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993121', 'iqbaljahid.unimed@gmail.com', '2021-09-15', 1, '2026-05-24 18:00:00'),
(39, '103117', 'Yeasin Arafat', 'Senior Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993133', 'yeasin.unimed@gmail.com', '2021-09-15', 1, '2026-05-24 18:00:00'),
(40, '103118', 'Md. Mirhan Uddin Rabbani', 'Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993171', 'mirhan.2013@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(41, '103119', 'Md. Golam Rosul', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993474', 'rosul1992unimed@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(42, '103121', 'Md. Mahmudul Hasan', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993398', 'mahmuduljasanhiron@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(43, '103122', 'Md. Hasan Ahmmed', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993385', 'hridoyhasan2291@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(44, '103124', 'Md. Rubel Islam', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993453', 'islamtgcrubel@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(45, '103125', 'Md. Abu Rayhan', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993202', 'abu26rayhan@gmail.com', '2021-09-21', 1, '2026-05-24 18:00:00'),
(46, '103130', 'Md. Asaduzzaman', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994767', 'asad.unimed@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(47, '103131', 'Md. Monowar Hossain', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996343', 'monowar.unimed@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(48, '103132', 'Md. Ashraful Islam', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994501', 'ehriash@gmail.com', '2022-01-02', 1, '2026-05-24 18:00:00'),
(49, '103138', 'Md. Sal-Sabbir Khan Sazib', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993150', 'sabbir.khan@unigroup-bd.com', '2022-08-18', 1, '2026-05-24 18:00:00'),
(50, '103141', 'Biswazit Biswas', 'Senior Sales & Service Engineer', 'Sylhet', 'UniMed Limited', 'Core Diagnostic Division', '01989994091', 'biswazit.biswas@unigroup-bd.com', '2022-10-08', 1, '2026-05-24 18:00:00'),
(51, '103142', 'Md. Tanvir Hasan', 'Senior Sales Administration Officer', 'Corporate Office', 'UniMed Limited', 'Diagnostic', '01929993378', 'tanvir.hasan@unigroup-bd.com', '2022-09-19', 1, '2026-05-24 18:00:00'),
(52, '103143', 'Md. Ahsan Habib', 'Senior Sales & Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989996988', 'ahsan.habib@unigroup-bd.com', '2022-11-01', 1, '2026-05-24 18:00:00'),
(53, '103150', 'Md. Sadman Habib Shuvo', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989997122', 'shshuvo50@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(54, '103151', 'Md. Jahid Hasan', 'Senior Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994398', 'md.jahidhasan.unimed@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(55, '103153', 'Md. Anisur Rahman', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996778', 'anisur.unimed@gmail.com', '2023-08-02', 1, '2026-05-24 18:00:00'),
(56, '103154', 'Hasebul Islam Anny', 'Sales Representative', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996859', 'annyhf07@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(57, '103156', 'Md. Farid Uddin', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994759', 'mdfariduddin2810@gmail.com', '2023-09-09', 1, '2026-05-24 18:00:00'),
(58, '103159', 'Md. Shahid Miah', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01993319020', 'shahid.unimed69@gmail.com', '2023-10-10', 1, '2026-05-24 18:00:00'),
(59, '103160', 'Kakon Mia', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01993319021', 'unimed.kakon@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(60, '103162', 'Md. Jahidul Islam', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01993319019', 'jahid.unimem62@gmail.com', '2023-10-10', 1, '2026-05-24 18:00:00'),
(61, '104544', 'Md. Shamsul Hoque', 'Senior Sales Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993046', 'shoque262@gmail.com', '2005-08-02', 1, '2026-05-24 18:00:00'),
(62, '104545', 'Md. Noor Hossain Nasim', 'Sales & Service Engineer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989997062', 'noorhossainnasim24@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(63, '104546', 'Partho Saha', 'Senior Service Engineer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994224', 'partho.unimed@gmail.com', '2023-12-17', 1, '2026-05-24 18:00:00'),
(64, '104549', 'Ridoy Hosain', 'Area Sales Executive', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993050', 'ridayhosain08@gmail.com', '2024-02-12', 1, '2026-05-24 18:00:00'),
(65, '104552', 'Md. Habibur Rahman', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319009', 'habiburdpi67@gmail.com', '2024-02-15', 1, '2026-05-24 18:00:00'),
(66, '104553', 'Shifat Ul Islam', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319010', 'saifatulislam629@gmail.com', '2024-02-15', 1, '2026-05-24 18:00:00'),
(67, '104555', 'Pangkaj Kumar Roy', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319024', 'pangkajroy1122@gmail.com', '2024-03-02', 1, '2026-05-24 18:00:00'),
(68, '104556', 'Muzahedul Islam Sojib', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01993319018', 'sojib944916@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(69, '104557', 'Md. Shakib Sharier Rasel', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319025', 'mdsharierrasel@gmail.com', '2024-03-20', 1, '2026-05-24 18:00:00'),
(70, '104559', 'Md. Abu Torab', 'Assistant Service Engineer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319007', 'torab9092@gmail.com', '2024-04-30', 1, '2026-05-24 18:00:00'),
(71, '104560', 'Md. Ariful Islam', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989993553', 'crarif171@gmail.com', '2024-06-09', 1, '2026-05-24 18:00:00'),
(72, '104561', 'Md. Moniruzzaman', 'Sales & Service Engineer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994404', 'moniruzzamansourav2@gmail.com', '2024-07-27', 1, '2026-05-24 18:00:00'),
(73, '104564', 'Md. Akram Hossen', 'Assistant Sales Manager', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01929993038', 'ruakram33@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(74, '104566', 'Md. Salman Khan Tanvir', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01993319005', 'salmankhantanvir@gmail.com', '2024-12-01', 1, '2026-05-24 18:00:00'),
(75, '104567', 'Md. Arif Reza Chowdhury', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Ortho Clinical Diagnostic', '01993319004', 'rezaarif986@gmail.com', '2024-12-01', 1, '2026-05-24 18:00:00'),
(76, '104568', 'Tanjil Ahmed Tonmoy', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Ortho Clinical Diagnostic', '01993319003', 'tanjil.tonmoy04@gmail.com', '2024-12-01', 1, '2026-05-24 18:00:00'),
(77, '104570', 'Shakil Ahammed', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996919', 'shakilahmmed005@gmail.com', '2025-01-05', 1, '2026-05-24 18:00:00'),
(78, '104571', 'Md. Fariduzzaman', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996909', 'foridchowdhury1@gmail.com', '2025-01-06', 1, '2026-05-24 18:00:00'),
(79, '104573', 'Mustofa Amir Faisal', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994728', 'mdfaisal1058@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(80, '104577', 'Mohammad Omar Faruk', 'Technical Services Officer', 'Chattogram', 'UniMed Limited', 'Diagnostic', '01993319023', 'omarfaruk.cps@gmail.com', '2025-03-03', 1, '2026-05-24 18:00:00'),
(81, '104578', 'Md. Rahim Badsha', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994127', 'rahimrubmb@gmail.com', '2025-03-19', 1, '2026-05-24 18:00:00'),
(82, '104579', 'Md. Humayun Kabir', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993387', 'himel3330@gmail.com', '2025-03-19', 1, '2026-05-24 18:00:00'),
(83, '104582', 'Md. Robiul Islam', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993058', 'robiulislam2805@gmai.com', '2025-12-23', 1, '2026-05-24 18:00:00'),
(84, '104583', 'Jibon Kumar Paul', 'Application Specialist', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994421', 'jibonpail034@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(85, '104584', 'Md. Murad Hossain', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01993319006', 'muradhossainruacbd@gmail.com', '2026-01-13', 1, '2026-05-24 18:00:00'),
(86, '104586', 'Md. Sadequl Islam', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989997229', 'sadequlislam2059@gmail.com', '2026-01-19', 1, '2026-05-24 18:00:00'),
(87, '104587', 'Md. Arafat Rabby', 'Technical Services Officer', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994067', 'mdarafatrabbyy@gmail.com', '2026-01-20', 1, '2026-05-24 18:00:00'),
(88, '200045', 'Dipankar Biswas', 'General Manager, DePuy', 'Smith & Nephew(AWM)', 'UniHealth Limited', 'Dental & Ortho', '01929993247', 'dipankar.biswas@unigroup-bd.com', '1999-11-13', 1, '2026-05-24 18:00:00'),
(89, '201429', 'Md. Mokhlesur Rahman Khondokar', 'Assistant General Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993234', 'mokhles.rahman@unigroup-bd.com', '2005-09-01', 1, '2026-05-24 18:00:00'),
(90, '201473', 'Mohammad Anowar Hossain Chowdhury', 'Assistant General Manager, OCD', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993229', 'anowar.hossain@unigroup-bd.com', '2006-12-18', 1, '2026-05-24 18:00:00'),
(91, '202503', 'Ataur Rahman', 'Deputy Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993235', 'ataur.rahman@unigroup-bd.com', '2008-07-01', 1, '2026-05-24 18:00:00'),
(92, '202504', 'Md. Rajwanul Haque', 'Assistant General Manager, Sales', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993228', 'rajwan.haque@gmail.com', '2008-07-01', 1, '2026-05-24 18:00:00'),
(93, '202515', 'Md. Zuhurul Islam', 'Deputy Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993245', 'zuhurul.islam@unigroup-bd.com', '2008-01-28', 1, '2026-05-24 18:00:00'),
(94, '202536', 'A H M Parvez Chowdhury', 'Deputy Manager, Administration', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01929993303', 'parvez.chowdhury@unigroup-bd.com', '2010-01-25', 1, '2026-05-24 18:00:00'),
(95, '202548', 'Md. Hasibur Rahman Abu', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993230', 'hasib.rahman@unigroup-bd.com', '2010-08-24', 1, '2026-05-24 18:00:00'),
(96, '202597', 'Bipul Das', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993393', 'bipul.das@unigroup-bd.com', '2012-04-03', 1, '2026-05-24 18:00:00'),
(97, '202630', 'Md. Jahangir Hossain', 'Senior Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993382', 'jhossain.unimed@gmail.com', '2013-05-02', 1, '2026-05-24 18:00:00'),
(98, '202636', 'Mohammad Shahed Noman', 'Manager, Product & Application', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993227', 'shahed.noman@unigroup-bd.com', '2013-07-18', 1, '2026-05-24 18:00:00'),
(99, '202658', 'Manik Chandra Shutradhar', 'Senior Sales & Service Engineer', 'Bogura', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989993917', 'manik.chandra@unigroup-bd.com', '2014-06-15', 1, '2026-05-24 18:00:00'),
(100, '202662', 'Rashed Ahmed', 'Assistant Manager, Sales Administration', 'Corporate Office', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989993968', 'rashed.ahmed@unigroup-bd.com', '2014-08-26', 1, '2026-05-24 18:00:00'),
(101, '202672', 'Md. Mamun', 'Senior Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993236', 'mamunrahman1909@gmail.com', '2015-08-22', 1, '2026-05-24 18:00:00'),
(102, '202676', 'Md. Robel', 'Senior Lab Technician', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994206', 'mdrobel9024@gmail.com', '2013-09-22', 1, '2026-05-24 18:00:00'),
(103, '202688', 'Md. Zahirul Islam', 'Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994202', 'zahirul5050@gmail.com', '2015-03-23', 1, '2026-05-24 18:00:00'),
(104, '202717', 'Md. Razib Hossain', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994266', 'razib.hossain@unigroup-bd.com', '2015-11-01', 1, '2026-05-24 18:00:00'),
(105, '202748', 'Md. Saddam Mallik', 'Senior Area Sales Manager', 'Chattogram', 'UniHealth Limited', 'Dental & Ortho', '01989994405', 'saddam.unimed@gmail.com', '2016-05-12', 1, '2026-05-24 18:00:00'),
(106, '202751', 'Md. Abdullah Al Mamun', 'Key Accounts Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994387', 'mamun.unihealth@gmail.com', '2016-03-01', 1, '2026-05-24 18:00:00'),
(107, '202753', 'Avijit Dutta', 'Deputy Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994386', 'avijit.unihealth@gmail.com', '2016-03-01', 1, '2026-05-24 18:00:00'),
(108, '202760', 'Abdul Gani', 'Deputy Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994412', 'abdul.gani@unigroup-bd.com', '2016-03-09', 1, '2026-05-24 18:00:00'),
(109, '202765', 'Md. Jaber Bin Sayeed', 'Assistant Manager, Service', 'Dhaka', 'UniMed Limited', 'Core Diagnostic Division', '01989994422', 'jaberbin.sayeed@unigroup-bd.com', '2016-04-09', 1, '2026-05-24 18:00:00'),
(110, '202771', 'Mohammad Zesun Ahmed', 'Key Accounts Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994388', 'zesun609@gmail.com', '2016-05-02', 1, '2026-05-24 18:00:00'),
(111, '202798', 'Suman Kanti Majumder', 'Area Sales Executive', 'Chattogram', 'UniHealth Limited', 'Dental & Ortho', '01989994519', 'majumdersuman77@gmail.com', '2016-09-01', 1, '2026-05-24 18:00:00'),
(112, '202803', 'Saumitro Kumar', 'Area Sales Executive', '', 'UniHealth Limited', 'Dental & Ortho', '01989994576', 'saumitrobiswas15@gmail.com', '2016-11-01', 1, '2026-05-24 18:00:00'),
(113, '202812', 'Syad Rakibur Hossain', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994198', 'rakib.syad38@gmail.com', '2016-11-01', 1, '2026-05-24 18:00:00'),
(114, '202840', 'Joydev Mandal', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994670', '92joydevmandal@gmail.com', '2017-04-02', 1, '2026-05-24 18:00:00'),
(115, '202848', 'Md. Selim Hossen', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994672', 'selimhossen1989@gmail.com', '2018-04-13', 1, '2026-05-24 18:00:00'),
(116, '202854', 'Azgoer Al Azad', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994682', 'maaazadmizan@gmail.com', '2017-04-04', 1, '2026-05-24 18:00:00'),
(117, '202855', 'Md. Ashraful Alam', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994681', 'ashrafulalamnafi1@gmail.com', '2017-04-13', 1, '2026-05-24 18:00:00'),
(118, '202860', 'Md. Rasel Gazi', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989994265', 'rasel.gazi@unigroup-bd.com', '2017-06-07', 1, '2026-05-24 18:00:00'),
(119, '202865', 'Md. Masudur Rahman', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994731', 'masud.rahman@unigroup-bd.com', '2017-09-14', 1, '2026-05-24 18:00:00'),
(120, '202867', 'Md. Jweal Jomaddar', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994407', 'jwealrana45@gmail.com', '2017-09-17', 1, '2026-05-24 18:00:00'),
(121, '202868', 'Palash Kumar Das', 'Area Sales Executive', 'Chattogram', 'UniHealth Limited', 'Dental & Ortho', '01989994742', 'palashkumardas48@gmail.com', '2017-09-01', 1, '2026-05-24 18:00:00'),
(122, '202870', 'Md. Shovon Miah', 'Assistant Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993380', 'md.shovon1988@gmail.com', '2017-09-01', 1, '2026-05-24 18:00:00'),
(123, '202878', 'Md. Jinnat Hossain', 'Senior Sales Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994771', 'hossainmdjinnat@gmail.com', '2017-10-01', 1, '2026-05-24 18:00:00'),
(124, '202882', 'Md. Esmat Doha Rony', 'Senior Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994766', 'esmat.doha@unigroup-bd.com', '2017-11-04', 1, '2026-05-24 18:00:00'),
(125, '202885', 'Gonash Chandra Paul', 'Area Sales Executive', 'Chattogram', 'UniHealth Limited', 'Dental & Ortho', '01989994770', 'gonashkumar246@gmail.com', '2017-11-01', 1, '2026-05-24 18:00:00'),
(126, '202886', 'Mehedy Alam', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994779', 'mdm558946@gmail.com', '2017-11-01', 1, '2026-05-24 18:00:00'),
(127, '202887', 'Mir Tarun', 'Area Sales Executive', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994772', 'tarun21mir@gmail.com', '2017-11-01', 1, '2026-05-24 18:00:00'),
(128, '202937', 'Md. Rubel Ahammed', 'Senior Sales Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994423', 'rubelrajus99@gmail.com', '2018-08-01', 1, '2026-05-24 18:00:00'),
(129, '202938', 'Mohsin Ali', 'Senior Sales Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994535', 'mohsinunimed2022@gmail.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(130, '202939', 'Md. Uzir Ali', 'Senior Sales Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993424', 'uzirali920@gmail.com', '2018-08-05', 1, '2026-05-24 18:00:00'),
(131, '202940', 'Ibne Firoz Shahriar Kabir', 'Key Accounts Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993425', 'firoz.x94@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(132, '202944', 'Md. Abu Bakkar Siddique', 'Senior Sales & Service Engineer', 'Sylhet', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994426', 'abubakar.siddique@unigroup-bd.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(133, '202948', 'Md. Saikat Paul', 'Area Sales Executive', 'Chattogram', 'UniHealth Limited', 'Dental & Ortho', '01989994534', 'saikatpal11@gmail.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(134, '202998', 'Md. Firojur Rahman', 'Senior Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994860', 'firoz.ahmedvp@gmail.com', '2019-07-08', 1, '2026-05-24 18:00:00'),
(135, '203101', 'A J M Mostofa Kamal', 'Assistant General Manager, Sales', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01989996751', 'mostafa.kamal@unigroup-bd.com', '2020-01-01', 1, '2026-05-24 18:00:00'),
(136, '203103', 'Prattay Paul Etho', 'Senior Sales & Service Engineer', 'Sylhet', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994435', 'prattay.paul@unigroup-bd.com', '2020-12-05', 1, '2026-05-24 18:00:00'),
(137, '203107', 'Md. Arif Khan', 'Senior Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993231', 'arif.khan@unigroup-bd.com', '2021-09-25', 1, '2026-05-24 18:00:00'),
(138, '203108', 'Kh. Rahim Reza', 'Senior Application Specialist', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994730', 'rahim.reza@unigroup-bd.com', '2021-12-01', 1, '2026-05-24 18:00:00'),
(139, '203110', 'Md. Shakil Al Monsur', 'Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989993918', 'shakil.monsur@unigroup-bd.com', '2021-12-12', 1, '2026-05-24 18:00:00'),
(140, '203112', 'Sanjib Kumar Biswas', 'Sales & Marketing Manager', 'Ophthalmic Division', 'UniHealth Limited', 'Sales', '01989994020', 'sanjib.kumar@unigroup-bd.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(141, '203113', 'Md. Ariful Islam', 'Assistant Sales Manager', 'Dhaka-A', 'UniHealth Limited', 'Sales', '01989994021', 'mdariful.islam@unigroup-bd.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(142, '203115', 'Anisur Rahman', 'Senior Key Accounts Manager', 'Islamia, Panthopath', 'UniHealth Limited', 'Sales', '01989994068', 'anisurrahman0704@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(143, '203116', 'Md. Emdaduhalla Shimul', 'Key Accounts Manager', 'Khulna 2', 'UniHealth Limited', 'Sales', '01989994069', 'shimul1229gsk@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(144, '203117', 'Md. Saifuzzaman', 'Assistant Manager, Sales & Service', 'Dhaka-B', 'UniHealth Limited', 'Sales', '01989994064', 'saifuz.zaman@unigroup-bd.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(145, '203120', 'Md. Matiur Rahaman', 'Senior Technical Services Officer', 'Dhanmondi-1', 'UniHealth Limited', 'Sales', '01989994018', 'matiur1991@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(146, '203122', 'Ashadul Islam Patowary', 'Sales & Service Engineer', 'Chattogram', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994668', 'asad.islam@unigroup-bd.com', '2022-04-16', 1, '2026-05-24 18:00:00'),
(147, '203123', 'Sumon Khan', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994376', 'khansumon3333@gmail.com', '2022-04-13', 1, '2026-05-24 18:00:00'),
(148, '203124', 'Md. Shajahan Seraz', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993420', 'ssirazbru01@gmail.com', '2022-04-24', 1, '2026-05-24 18:00:00'),
(149, '203125', 'Md. Anisur Rahman', 'Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993225', 'anisurr969@gmail.com', '2022-04-24', 1, '2026-05-24 18:00:00'),
(150, '203126', 'Md. Leyakat Ali', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993130', 'leyakatali321@gmail.com', '2022-04-24', 1, '2026-05-24 18:00:00'),
(151, '203129', 'Atikul Islam', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994026', 'unihealth.atikul@gmail.com', '2022-08-24', 1, '2026-05-24 18:00:00'),
(152, '203130', 'Sahanur Islam', 'Sales & Service Engineer', 'Jashore', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993343', 'sahanur.islam@unigroup-bd.com', '2022-09-10', 1, '2026-05-24 18:00:00'),
(153, '203131', 'Md. Ataur Rahman', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989996743', 'zamanjoy675@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(154, '203132', 'Md. Nadiruzzaman', 'Senior Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994070', 'zaman.unihealth@gmail.com', '2022-09-11', 1, '2026-05-24 18:00:00'),
(155, '203133', 'Md. Tamzid Hasan', 'Senior Application Specialist', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989993894', 'mdtamzidhasan19@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(156, '203134', 'Rafsan Al Saba Siam', 'Key Accounts Manager', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994022', 'rafsan.saba@unigroup-bd.com', '2022-10-06', 1, '2026-05-24 18:00:00'),
(157, '203135', 'Md. Faisal Alam Biswas', 'Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993445', 'faisal.alam@unigroup-bd.com', '2022-11-06', 1, '2026-05-24 18:00:00'),
(158, '203136', 'Shafin Raj', 'Marketing Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989993921', 'shafin.raj@unigroup-bd.com', '2023-02-26', 1, '2026-05-24 18:00:00'),
(159, '209339', 'Md. Shahinur Islam', 'Assistant Sales Manager', '', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994377', 'shahinur.islam@unigroup-bd.com', '2019-06-01', 1, '2026-05-24 18:00:00'),
(160, '209340', 'Md. Mashiur Rahman', 'Senior Sales Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994019', 'mdmashiurrahman1260@gmail.com', '2024-05-05', 1, '2026-05-24 18:00:00'),
(161, '209341', 'Mahmud Hasan', 'Assistant Manager, Sales & Service', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994062', 'hasanmahmud001@gmail.com', '2024-07-06', 1, '2026-05-24 18:00:00'),
(162, '209342', 'SM Omar Sharif', 'Assistant Manager, Training & Education', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01989994385', 'shaonshaon1608@yahoo.com', '2024-08-01', 1, '2026-05-24 18:00:00'),
(163, '209343', 'Mohammed Rakibul Hassan', 'Senior Technical Services Officer', 'Mymensingh', 'UniHealth Limited', 'Sales', '01989993593', 'rakibul.spth@gmail.com', '2024-08-12', 1, '2026-05-24 18:00:00'),
(164, '209345', 'Md. Wahid Yeamaney', 'Sales Administration Officer', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01989994577', 'wahid.yeamaney@unigroup-bd.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(165, '209346', 'Anup Tarafder', 'Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989997230', 'anuptarafder02@gmail.com', '2024-11-10', 1, '2026-05-24 18:00:00'),
(166, '209347', 'Md. Abdur Rakib', 'Assistant Manager, Business Development', 'Corporate Office', 'UniHealth Limited', 'IT', '01989997272', 'rakibkhan.rk556@gmail.com', '2025-01-01', 1, '2026-05-24 18:00:00'),
(167, '209349', 'Shakhawt', 'Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989996775', 'shakhawthossen60@gmail.com', '2025-01-22', 1, '2026-05-24 18:00:00'),
(168, '209350', 'Md. Saidul Islam', 'Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994025', 'saidulkakon23@gmail.com', '2025-01-22', 1, '2026-05-24 18:00:00'),
(169, '209351', 'Md. Sahed Rahman', 'Technical Services Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994024', 'sahedrahman1994@gmail.com', '2025-01-22', 1, '2026-05-24 18:00:00'),
(170, '209352', 'Md. Abul Foysal', 'Technical Services Officer', 'Uttara', 'UniHealth Limited', 'Sales', '01989994741', 'mdfoysalboss@gmail.com', '2025-06-01', 1, '2026-05-24 18:00:00'),
(171, '209354', 'Md. Mahady Hasan', 'Application Specialist', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01989994865', 'mahadyyhasan@gmail.com', '2025-08-07', 1, '2026-05-24 18:00:00'),
(172, '209355', 'Robiul Islam', 'Assistant Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01993319022', 'mrobiul6391@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(173, '209356', 'Sunit Sarker', 'Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993189', 'sunitsarker100@gmail.com', '2025-09-02', 1, '2026-05-24 18:00:00'),
(174, '209357', 'Apurba Kumar Sarker', 'Senior Area Sales Manager', 'Dhaka-A', 'UniHealth Limited', 'Sales', '01989994886', 'apurbo777@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(175, '209358', 'Md. Masud Rana ', 'Senior Area Sales Manager', 'Rajshahi', 'UniHealth Limited', 'Sales', '01993319117', 'mrana6733@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(176, '209359', 'Md. Mizanur Rahman', 'Senior Area Sales Manager', 'Dhaka-B', 'UniHealth Limited', 'Sales', '01993319116', 'mizanurrahman15121985@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(177, '209360', 'Md. Niamul Islam', 'Area Sales Manager', 'Sylhet', 'UniHealth Limited', 'Sales', '01989997251', 'niamulsquare@gmail.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(178, '209361', 'Md. Golam Kibria', 'Senior Product Officer', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01989994268', 'kibriapau21@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(179, '209362', 'Muhammad Abdul Rabbi', 'Sales & Service Engineer', 'Dhaka', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01993319107', 'arabbi.eee.nbiu25@gmail.com', '2025-10-12', 1, '2026-05-24 18:00:00'),
(180, '209363', 'Mynul Hasan', 'Senior Product Manager', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01929993392', 'mynul.hasan1412@gmail.com', '2025-11-02', 1, '2026-05-24 18:00:00'),
(181, '209364', 'Suman Halder', 'Assistant Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993246', 'suman.unihealth@gmail.com', '2025-11-01', 1, '2026-05-24 18:00:00'),
(182, '209366', 'Md. Shohel Rana', 'Medical Promotion Officer', 'Bogra', 'UniHealth Limited', 'Sales', '01989996870', 'shohelrana9175@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(183, '209368', 'Md. Abu Sharif Khalifa', 'Medical Promotion Officer', 'Barisal-A', 'UniHealth Limited', 'Sales', '01929993321', 'khaifabinsharif7@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(184, '209369', 'Md.Touhidul Islam', 'Senior Medical Promotion Officer', 'Dinajpur', 'UniHealth Limited', 'Sales', '01993319121', 'touhidulislam9641@gmail.com', '2026-01-11', 1, '2026-05-24 18:00:00'),
(185, '209370', 'Majharul Islam', 'Senior Medical Promotion Officer', 'Mirpur', 'UniHealth Limited', 'Sales', '01993319122', 'majharulislam620@gmail.com', '2026-01-10', 1, '2026-05-24 18:00:00'),
(186, '209371', 'Golap Rabbani', 'Senior Medical Promotion Officer', 'Rangpur', 'UniHealth Limited', 'Sales', '01993319123', 'gv.golap@gmail.com', '2026-01-11', 1, '2026-05-24 18:00:00'),
(187, '209372', 'Md. Azadul Islam', 'Senior Medical Promotion Officer', 'Pabna', 'UniHealth Limited', 'Sales', '01993319124', 'azadul1991@gmail.com', '2026-01-11', 1, '2026-05-24 18:00:00'),
(188, '209373', 'Moniruzzaman', 'Senior Medical Promotion Officer', 'Lalmatia', 'UniHealth Limited', 'Sales', '01993319125', 'salimbd827@gmail.com', '2026-01-10', 1, '2026-05-24 18:00:00'),
(189, '209374', 'Md. Masum Billah', 'Medical Promotion Officer', 'Khulna 1', 'UniHealth Limited', 'Sales', '01993319126', 'masumbillahjsr94@gmail.com', '2026-01-10', 1, '2026-05-24 18:00:00'),
(190, '209375', 'Md. Maruf Billah', 'Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01929993237', 'marufbillah990@gmail.com', '2026-02-08', 1, '2026-05-24 18:00:00'),
(191, '209376', 'Md. Arifur Rahman', 'Senior Area Sales Manager', 'Khulna+Barisal', 'UniHealth Limited', 'Sales', '01993319002', 'arifurrahmangpl13858@gmail.com', '2026-02-15', 1, '2026-05-24 18:00:00'),
(192, '209377', 'Md. Manir Hossain', 'Medical Promotion Officer', 'Jessore', 'UniHealth Limited', 'Sales', '01993319108', 'monir.hossain1697@gmail.com', '2026-03-24', 1, '2026-05-24 18:00:00'),
(193, '209378', 'Md. Anarul Islam', 'Medical Promotion Officer', 'Mymensingh-B', 'UniHealth Limited', 'Sales', '01989997286', 'anarulislam2988@gmail.com', '2026-03-28', 1, '2026-05-24 18:00:00'),
(194, '209379', 'Md. Janam Ali', 'Medical Promotion Officer', 'Islamia, Panthopath-B', 'UniHealth Limited', 'Sales', '01989997285', 'kmjanam5@gmail.com', '2026-03-24', 1, '2026-05-24 18:00:00'),
(195, '209380', 'Delowar Hossain', 'Medical Promotion Officer', 'Panchlaish', 'UniHealth Limited', 'Sales', '01993319030', 'delowar20590@gmail.com', '2026-04-28', 1, '2026-05-24 18:00:00'),
(196, '300000', 'M Mosaddek Hossain', 'Chairman & Managing Director', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'General  Administration', '01929993001', 'mosaddek.hossain@unigroup-bd.com', '1995-01-01', 1, '2026-05-24 18:00:00'),
(197, '300001', 'M A Mannan', 'Assistant Manager, Distribution', 'Moulvibazar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996726', 'mannan.unigroup@gmail.com', '1997-10-01', 1, '2026-05-24 18:00:00'),
(198, '300008', 'Md. Saimul Islam', 'Director, Distribution & Herbal Division', 'Dhaka', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01929993049', 'saimul.islam@unigroup-bd.com', '1997-07-19', 1, '2026-05-24 18:00:00'),
(199, '300016', 'Zakir Hossain', 'Assistant General Manager, Sales', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Institution', '01929993069', 'zakir.hossain@unigroup-bd.com', '1997-09-14', 1, '2026-05-24 18:00:00'),
(200, '300031', 'Kazi Saiful Islam', 'Senior Area Sales Manager', 'Khulna-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993064', 'ksaifulunigroup@gmail.com', '1998-10-27', 1, '2026-05-24 18:00:00'),
(201, '300032', 'Mia Mohd Eskander', 'Director, Dental & Ortho', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01929993048', 'mm.eskander@unigroup-bd.com', '1998-11-01', 1, '2026-05-24 18:00:00'),
(202, '300035', 'Md. Mongur Khan', 'Executive Director, OCD', 'Corporate Office', 'UniHealth Limited', 'Ortho Clinical Diagnostic', '01929993226', 'monjur.khan@unigroup-bd.com', '1996-10-01', 1, '2026-05-24 18:00:00'),
(203, '300069', 'Saifuddin Manik', 'Senior Area Sales Manager', 'Gouripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993806', 'saifuddinmanik.unigroup@gmail.com', '2000-06-13', 1, '2026-05-24 18:00:00'),
(204, '300112', 'Nurul Hoque Sarker', 'Area Sales Executive', 'Uttara-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993083', 'nhaque.unigroup@gmail.com', '2000-10-12', 1, '2026-05-24 18:00:00'),
(205, '300141', 'Md. Ashraful Alam', 'Senior Area Sales Manager', 'Dhaka-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993105', 'ashrafulalamunimed@gmail.com', '2001-01-25', 1, '2026-05-24 18:00:00'),
(206, '300148', 'Md. Shakawit Hossain', 'Senior Area Sales Manager', 'Uttara-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993159', 'shakawit.unigroup@gmail.com', '2001-02-05', 1, '2026-05-24 18:00:00'),
(207, '300159', 'Md. Humayun Kabir', 'Sales Manager', 'Barisal East', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993063', 'humayun.kabir@unigroup-bd.com', '2001-01-02', 1, '2026-05-24 18:00:00'),
(208, '300160', 'Khairul Basher', 'Deputy Sales Manager', 'Sylhet-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993169', 'khairul.basher@unigroup-bd.com', '2001-03-02', 1, '2026-05-24 18:00:00'),
(209, '300202', 'Md. Nashir Uddin Khan ', 'Senior Area Sales Manager', 'Mymensingh-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993353', 'nasir.unigroup807@gmail.com', '2001-08-04', 1, '2026-05-24 18:00:00'),
(210, '300220', 'Hasan Sultan Ahmed', 'Area Sales Executive', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993324', 'hasansultan@gmail.com', '1998-06-10', 1, '2026-05-24 18:00:00'),
(211, '300264', 'S M Rashedil Khaleque', 'Deputy Sales Manager', 'Mirpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993201', 'rashedil.khaleque@unigroup-bd.com', '2002-02-01', 1, '2026-05-24 18:00:00'),
(212, '300265', 'Md. Jamil Akhter', 'Assistant Sales Manager', 'Dhaka-B', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01929993101', 'jamil.akther@unigroup-bd.com', '2002-01-01', 1, '2026-05-24 18:00:00'),
(213, '300295', 'Md. Nazmul Huda', 'Assistant Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993100', 'nazmul.unimed@gmail.com', '2002-03-28', 1, '2026-05-24 18:00:00'),
(214, '300304', 'Munshi Masud Hossain', 'Director, Finance', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993008', 'masud.hossain@unigroup-bd.com', '1999-08-01', 1, '2026-05-24 18:00:00'),
(215, '300318', 'Md. Jahangir Alam', 'Senior Area Sales Manager', 'Savar-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993763', 'jahangiruhr3@gmail.com', '2002-08-05', 1, '2026-05-24 18:00:00'),
(216, '300319', 'Md. Abdus Salam', 'Assistant Sales Manager', 'Rangpur South', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993091', 'mdabdus.salam@unigroup-bd.com', '2002-04-06', 1, '2026-05-24 18:00:00'),
(217, '300330', 'Mohammad Abdullah', 'Deputy Sales Manager', 'Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993072', 'md.abdullah@unigroup-bd.com', '2002-05-21', 1, '2026-05-24 18:00:00'),
(218, '300333', 'A.H.M. Aktheruzzaman', 'Assistant General Manager, Distribution', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993252', 'akther.zaman@unigroup-bd.com', '2001-04-01', 1, '2026-05-24 18:00:00'),
(219, '300344', 'Choudhuwy Kamrul Islam', 'Sales Officer', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '', '', '2002-07-13', 1, '2026-05-24 18:00:00'),
(220, '300345', 'Md. Azahar Ali Mollah', 'Area Sales Executive', 'Fultola-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993717', 'medulmolla2020@gmail.com', '2002-02-02', 1, '2026-05-24 18:00:00'),
(221, '300372', 'Gautom Kumar Debgupta', 'Deputy Sales Manager', 'Rajshahi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993075', 'gautam.kumar@unigroup-bd.com', '2002-09-01', 1, '2026-05-24 18:00:00'),
(222, '300384', 'Dipak Kumar Das', 'Deputy Sales Manager', 'Mymensingh-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993059', 'dipak.kumar@unigroup-bd.com', '2002-09-01', 1, '2026-05-24 18:00:00'),
(223, '300404', 'Md. Nazrul Islam', 'Deputy Sales Manager', 'Comilla South', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993055', 'nazrul.islam@unigroup-bd.com', '2002-11-28', 1, '2026-05-24 18:00:00'),
(224, '300447', 'Rashed Md Jahangir', 'Senior Deputy Sales Manager', 'Khulna East', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993095', 'rashed.jahangir@unigroup-bd.com', '2003-01-01', 1, '2026-05-24 18:00:00'),
(225, '300471', 'Bipul Chandra Das', 'Senior Area Sales Manager', 'Feni-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993317', 'bipuldas3720@gmail.com', '2003-02-01', 1, '2026-05-24 18:00:00'),
(226, '300475', 'Md. Mizanur Rahman', 'Senior Area Sales Manager', 'Gopalganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993827', 'mizan.unigroup@gmail.com', '2003-06-05', 1, '2026-05-24 18:00:00'),
(227, '300479', 'Nobodip Kumar Roy', 'Senior Area Sales Manager', 'Comilla City-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993322', 'nobodip22@gmail.com', '2003-01-05', 1, '2026-05-24 18:00:00'),
(228, '300513', 'Md. Rafiqul Islam', 'Senior Medical Promotion Officer', 'Bhulta-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993191', 'jrafiq701@gmail.com', '2003-07-01', 1, '2026-05-24 18:00:00'),
(229, '300520', 'AKM Ruhul Hasan Mithu', 'Deputy Sales Manager', 'Madaripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993079', 'ruhul.hasan@unigroup-bd.com', '2003-07-19', 1, '2026-05-24 18:00:00'),
(230, '300521', 'Abdur Razzak', 'Senior Area Sales Manager', 'Dinajpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993065', 'razzak.unigroup@gmail.com', '2003-07-17', 1, '2026-05-24 18:00:00'),
(231, '300526', 'G. M. Khairul Islam', 'Deputy General Manager, Sales', 'West Cumilla Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993162', 'khairul.islam@unigroup-bd.com', '2003-05-19', 1, '2026-05-24 18:00:00'),
(232, '300535', 'Md. Yelius Howlader', 'Deputy Sales Manager', 'NICVD', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993209', 'yelius.howlader@unigroup-bd.com', '2003-07-26', 1, '2026-05-24 18:00:00'),
(233, '300700', 'Mohammad Shahid Ullah Kaiser', 'Assistant General Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Fine Chemicals Limited', 'Production', '01929993491', 'shahid.ullah@unigroup-bd.com', '2005-10-03', 1, '2026-05-24 18:00:00'),
(234, '300709', 'Kabir Hossain', 'Manager, Warehouse & VAT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993318', 'kabir.hossain@unigroup-bd.com', '2000-11-02', 1, '2026-05-24 18:00:00'),
(235, '300731', 'Md. Abul Hasnat Masud', 'Deputy Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989993998', 'ahm.biochem923@gmail.com', '2009-02-01', 1, '2026-05-24 18:00:00'),
(236, '300732', 'Muhammad Shariful Islam', 'Assistant Manager, Warehouse', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01922702375', 'muhammadsharifulislam47@gmail.com', '2010-01-17', 1, '2026-05-24 18:00:00'),
(237, '300735', 'Md. Ikbal Hossain Khan', 'Assistant General Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01929993289', 'iqbal.hossain@unigroup-bd.com', '2010-05-02', 1, '2026-05-24 18:00:00'),
(238, '300738', 'Ishrat Zaman Chowdhury', 'Deputy Manager, Procurement', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989994432', 'ishrat.zaman@unigroup-bd.com', '2010-05-02', 1, '2026-05-24 18:00:00'),
(239, '300739', 'SM Aktaruzzaman', 'Assistant General Manager, Quality Control ', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01929993483', 'aktar.zaman@unigroup-bd.com', '2010-12-08', 1, '2026-05-24 18:00:00'),
(240, '300743', 'Kazi Abu Rasel', 'Area Distribution Manager', 'Satkhira Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993131', 'rasel.unigroup1@gmail.com', '2008-07-01', 1, '2026-05-24 18:00:00'),
(241, '300745', 'Md. Enamul Hoque', 'Deputy Manager, Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01929993291', 'enamul.haque3@unigroup-bd.com', '2011-03-02', 1, '2026-05-24 18:00:00'),
(242, '300752', 'Md. Sadiqe Ullah', 'Deputy Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989993984', 'sadique.ullah@unigroup-bd.com', '2011-12-18', 1, '2026-05-24 18:00:00'),
(243, '300753', 'Md. Al Amin', 'Assistant Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989997216', 'amin.unigroup@gmail.com', '2011-06-08', 1, '2026-05-24 18:00:00'),
(244, '300754', 'Md. Golam Nabi Shaikh', 'Deputy Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989994001', 'raselshaikh.rs@gmail.com', '2012-02-09', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(245, '300759', 'Md. Zakir Hossain', 'Assistant Manager, Civil Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01929993356', 'zakir.unigroup@gmail.com', '2012-09-01', 1, '2026-05-24 18:00:00'),
(246, '300764', 'Md. Kamruzzaman', 'Deputy Manager, Production', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'International Business', '01989993990', 'zaman.unigroup@gmail.com', '2012-07-03', 1, '2026-05-24 18:00:00'),
(247, '300765', 'Md. Ali Mortuza', 'Production Manager', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01929993192', 'ali.mortuza@unigroup-bd.com', '2013-07-17', 1, '2026-05-24 18:00:00'),
(248, '300766', 'Dipankar Kumar Mandol', 'Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989993991', 'dipankar.unigroup@gmail.com', '2013-11-19', 1, '2026-05-24 18:00:00'),
(249, '300768', 'Md. Shamim Hossain', 'Senior Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01745058862', 'shamimgok@gmail.com', '2013-04-23', 1, '2026-05-24 18:00:00'),
(250, '300772', 'Md. Mahedi Hasan', 'Assistant Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989994016', 'mahadihasan3187@gmail.com', '2013-05-14', 1, '2026-05-24 18:00:00'),
(251, '300778', 'Md. Azad Hossain', 'Deputy Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989993983', 'azad.unigroup@gmail.com', '2013-08-01', 1, '2026-05-24 18:00:00'),
(252, '300781', 'Shahadat Sepai', 'Assistant General Manager, Distribution', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994434', 'shahadat.hossain@unigroup-bd.com', '2016-04-10', 1, '2026-05-24 18:00:00'),
(253, '300783', 'Md. Ritabul Islam', 'Assistant Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989994087', 'ritabul.unigroup@gmail.com', '2015-02-01', 1, '2026-05-24 18:00:00'),
(254, '300784', 'Ganash Chandra Suttra Dhar', 'Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989993992', 'ganash.unigroup@gmail.com', '2014-02-18', 1, '2026-05-24 18:00:00'),
(255, '300786', 'Sumon Reza', 'Deputy Manager, Microbiology', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989993986', 'sumonreza.mc.unigroup@gmail.com', '2015-02-01', 1, '2026-05-24 18:00:00'),
(256, '300790', 'Md. Moniruzzaman Shakil', 'Senior Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01736891366', 'md.shakil9uv@gmail.com', '2015-10-08', 1, '2026-05-24 18:00:00'),
(257, '300796', 'Md. Shahjahan Ali', 'Deputy Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989997295', 'shahjahan.ali@unigroup-bd.com', '2016-02-02', 1, '2026-05-24 18:00:00'),
(258, '300798', 'Md. Sultan Mahmud', 'Deputy Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989997103', 'sultan.unigroup@gmail.com', '2015-02-01', 1, '2026-05-24 18:00:00'),
(259, '300802', 'Kamrujjaman', 'Assistant Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989994090', 'kamruz.zaman2@uniggroup-bd.com', '2004-04-02', 1, '2026-05-24 18:00:00'),
(260, '300803', 'Md. Babul Patwari', 'Junior Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01911234986', '', '2000-01-01', 1, '2026-05-24 18:00:00'),
(261, '300804', 'Shahidul Islam', 'Executive, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01818779546', 'shahidul.unigroup@gmail.com', '1999-07-01', 1, '2026-05-24 18:00:00'),
(262, '300862', 'Shakil Hossain', 'Senior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996913', 'shakil1989unihealth@gmail.com', '2007-01-06', 1, '2026-05-24 18:00:00'),
(263, '301079', 'SM Toriqul Islam', 'Junior Purchase Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989996860', 'tariqul.unigroupo@gmail.com', '2014-08-01', 1, '2026-05-24 18:00:00'),
(264, '301100', 'Abu Sayem Mithu ', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989994219', '', '2015-04-01', 1, '2026-05-24 18:00:00'),
(265, '301103', 'Suravi Biswas', 'Assistant Manager, Business Development', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'International Business', '01989994006', 'suravi.biswas@unigroup-bd.com', '2016-09-08', 1, '2026-05-24 18:00:00'),
(266, '301104', 'Md. Mozammel Hoque', 'Deputy Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989994002', 'mozammelhaque.unigroup@gmail.com', '2016-08-09', 1, '2026-05-24 18:00:00'),
(267, '301106', 'Sk Masuduzzaman', 'Deputy Manager, Product Development', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01929993288', 'shaikhzzaman2020@gmail.com', '2016-09-02', 1, '2026-05-24 18:00:00'),
(268, '301117', 'Smritimoy Datta', 'Assistant Manager, Microbiology', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989997196', 'smritimoy.datta.133@gmail.com', '2016-09-22', 1, '2026-05-24 18:00:00'),
(269, '301118', 'A.S.M. Mujahid Uddin', 'Senior Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01911285254', 'mujahiduudinbdmd@gmail.com', '2015-08-06', 1, '2026-05-24 18:00:00'),
(270, '301121', 'Md. Rubel Bhuiyan', 'Assistant Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989997215', 'bhuiyan.net@gmail.com', '2016-09-04', 1, '2026-05-24 18:00:00'),
(271, '301127', 'A.B.M. Miron', 'Assistant General Manager, Quality Control ', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989994565', 'abm.miron@unigroup-bd.com', '2016-10-16', 1, '2026-05-24 18:00:00'),
(272, '301128', 'Amit Kumar Mondal', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01912823575', 'amit.2013@outlook.com', '2016-03-16', 1, '2026-05-24 18:00:00'),
(273, '301130', 'Muhammad Tariqul Islam', 'Assistant Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989997193', 'tariqul.islam@unigroup-bd.com', '2016-03-23', 1, '2026-05-24 18:00:00'),
(274, '301133', 'Ramjan Ali', 'Senior Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01735771548', 'ramjan01735@gmail.com', '2017-05-26', 1, '2026-05-24 18:00:00'),
(275, '301134', 'Md. Shahinur Haque', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01683062864', 'shahinur.unigroup@gmail.com', '2017-06-01', 1, '2026-05-24 18:00:00'),
(276, '301137', 'Oliul Islam', 'Senior Officer, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989994732', 'oliulislam9259@gmail.com', '2017-08-26', 1, '2026-05-24 18:00:00'),
(277, '301140', 'Firoz Ahmed', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01913866297', 'ahmedfiroz1504@gmail.com', '2017-09-09', 1, '2026-05-24 18:00:00'),
(278, '301147', 'S.M. Alamgir Ahmed', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01617848478', 'alamgir.fu6@gmail.com', '2017-09-09', 1, '2026-05-24 18:00:00'),
(279, '301153', 'Md. Gazi Shalauddin Noman', 'Assistant Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01816815963', 'nomanrounok72@gmail.com', '2017-12-03', 1, '2026-05-24 18:00:00'),
(280, '301161', 'Lutfar Rahman', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01922312391', 'rlutfar4488@gmail.com', '2018-01-10', 1, '2026-05-24 18:00:00'),
(281, '301164', 'Md. Mizanur Rahman', 'Assistant Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989997195', 'mrahman42000@gmail.com', '2018-02-17', 1, '2026-05-24 18:00:00'),
(282, '301165', 'Md. Shawkat Momen Masum Mia', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01929993311', 'momenmasum@gmail.com', '2018-05-16', 1, '2026-05-24 18:00:00'),
(283, '301170', 'S. M. Saiful Islam', 'Assistant Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989993975', 'smsaifuluupl@gmail.com', '2018-04-28', 1, '2026-05-24 18:00:00'),
(284, '301171', 'Md. Inzamum -ul- Haque', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01675502037', 'inzamum79@gmail.com', '2018-05-20', 1, '2026-05-24 18:00:00'),
(285, '301175', 'Saddam Hossin', 'Senior Documentation Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01571771248', 'mdsaddamhossain220@gmail.com', '2018-05-12', 1, '2026-05-24 18:00:00'),
(286, '301176', 'Uttam Kumar Adhikary', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01822447443', 'uttam22122@gmail.com', '2018-05-12', 1, '2026-05-24 18:00:00'),
(287, '301180', 'Fazlur Rahim', 'Production Manager', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01929993052', 'fazlur.rahim@unigroup-bd.com', '2018-08-06', 1, '2026-05-24 18:00:00'),
(288, '301182', 'Md. Khairul Islam', 'Deputy Manager, Product Development', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01989993993', 'mdkhairul.islam@unigroup-bd.com', '2018-08-01', 1, '2026-05-24 18:00:00'),
(289, '301184', 'Md. Abdullah Al Rubel', 'Senior Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989993977', 'mdabdullahalrubel220@gmail.com', '2018-11-01', 1, '2026-05-24 18:00:00'),
(290, '301185', 'Hayat Ali', 'Senior Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989993978', 'info.alihayat@gmail.com', '2018-11-01', 1, '2026-05-24 18:00:00'),
(291, '301192', 'Furkan Ali', 'Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01725233655', 'furkantalukder593@gmail.com', '2019-02-20', 1, '2026-05-24 18:00:00'),
(292, '301193', 'Mehedi Hasan', 'Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01774290858', 'mehedihasan323333@gmail.com', '2019-02-20', 1, '2026-05-24 18:00:00'),
(293, '301195', 'Alomgir Hosen', 'Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01571281367', 'alomgirhosen536@gmail.com', '2019-03-04', 1, '2026-05-24 18:00:00'),
(294, '301199', 'Md. Sheikh Soaib', 'Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01785302614', 'sheikhsoaib.unigroup@gmail.com', '2019-04-01', 1, '2026-05-24 18:00:00'),
(295, '301401', 'Mohammad Zainul Abedin', 'Director, UniMed Pharma', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993014', 'zainul.abedin@unigroup-bd.com', '2004-07-05', 1, '2026-05-24 18:00:00'),
(296, '301416', 'Md. Mahbub Alam', 'Director, HR & Administration', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993007', 'mahbub.alam@unigroup-bd.com', '2005-01-02', 1, '2026-05-24 18:00:00'),
(297, '301422', 'Arifur Rahman Khan', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993302', 'arifurrahmankhan1219@gmail.com', '2005-01-01', 1, '2026-05-24 18:00:00'),
(298, '301423', 'Md. Minul Hasan Akbari', 'Assistant General Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993300', 'minul.hasan@unigroup-bd.com', '2005-05-21', 1, '2026-05-24 18:00:00'),
(299, '301432', 'Md. Abu Royhan Chowdhury', 'Assistant General Manager, Administration', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993009', 'royhan.chowdhury@unigroup-bd.com', '2005-10-01', 1, '2026-05-24 18:00:00'),
(300, '301437', 'Md. Firoz Ahmed', 'Assistant Manager, Administration', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993274', 'firoz.ahmed@unigroup-bd.com', '2005-10-27', 1, '2026-05-24 18:00:00'),
(301, '301441', 'Atiar Rahman', 'Deputy Manager, Sales Administration', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993026', 'atiar.rahman@unigroup-bd.com', '2000-10-01', 1, '2026-05-24 18:00:00'),
(302, '301445', 'Md. Omar Faruk', 'Junior Sales Administration Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994761', 'farukunigroup@gmail.com', '2001-04-15', 1, '2026-05-24 18:00:00'),
(303, '301455', 'Mohammad Harun Ur Rashid Sikder', 'Deputy Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993301', 'haroon.sikder@unigroup-bd.com', '2002-03-06', 1, '2026-05-24 18:00:00'),
(304, '301458', 'Shaikh Md. Enamul Haque', 'Corporate Affairs Manager', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993011', 'shaikh.enamul@unigroup-bd.com', '2006-04-15', 1, '2026-05-24 18:00:00'),
(305, '301467', 'Safayat Mahmud', 'Director, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993016', 'safayat.mahmud@unigroup-bd.com', '2006-08-20', 1, '2026-05-24 18:00:00'),
(306, '301472', 'Md. Muhibbullah', 'Assistant Manager, Distribution', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993257', 'md.mohibbullah@unigroup-bd.com', '2007-01-06', 1, '2026-05-24 18:00:00'),
(307, '301497', 'Md. Kamruzzaman', 'Regulatory Affairs Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993294', 'kamruz.zaman@unigroup-bd.com', '2008-05-04', 1, '2026-05-24 18:00:00'),
(308, '301557', 'Dinendra Nath Roy', 'Senior Area Sales Manager', 'Rangpur-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993764', 'dinen1841@gmail.com', '2004-02-19', 1, '2026-05-24 18:00:00'),
(309, '301573', 'Biplob Kanti Mozumder', 'Senior Medical Promotion Officer', 'Feni-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993681', 'feni2082.unigroup@gmail.com', '2004-04-21', 1, '2026-05-24 18:00:00'),
(310, '301578', 'Md. Delower Hossain', 'Area Sales Executive', 'BSMMU/Green Life-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993142', 'delower.unigroup@gmail.com', '2026-02-10', 1, '2026-05-24 18:00:00'),
(311, '301584', 'Md. Anwar Sadat ', 'Senior Area Sales Manager', 'DMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993177', 'asadat1976@gmail.com', '2004-05-16', 1, '2026-05-24 18:00:00'),
(312, '301605', 'Md. Shohel Chowdhury', 'Deputy Sales Manager', 'Dhaka', 'UniMed Limited', 'Diagnostic', '01929993144', 'shohelchowdhury144@gmail.com', '2004-02-08', 1, '2026-05-24 18:00:00'),
(313, '301607', 'Md. Delower Hossain', 'Area Sales Executive', 'Gouripur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993690', 'delowarhossain9313@gmail.com', '2004-08-24', 1, '2026-05-24 18:00:00'),
(314, '301617', 'Md. Monjur Kader', 'Area Distribution Manager', 'Mirpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996910', 'monjurkader503@gmail.com', '2004-09-19', 1, '2026-05-24 18:00:00'),
(315, '301629', 'Dipok Roy', 'Senior Area Sales Manager', 'Gopalganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993720', 'dipokr36@gmail.com', '2004-11-20', 1, '2026-05-24 18:00:00'),
(316, '301632', 'Md. Abdul Kader', 'Area Sales Executive', 'Kallyanpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993129', 'ak1121976@gmail.com', '2004-12-11', 1, '2026-05-24 18:00:00'),
(317, '301638', 'Mohammad Ahasan Ullah', 'Deputy Sales Manager', 'Chandpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993375', 'ahsan.ullah@unigroup-bd.com', '2004-12-11', 1, '2026-05-24 18:00:00'),
(318, '301652', 'Motaleb Sikder', 'Senior Area Sales Manager', 'Daulatpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993084', 'motalebsikderuh@gmail.com', '2005-02-02', 1, '2026-05-24 18:00:00'),
(319, '301663', 'Md. Khalilur Rahman', 'Senior Medical Promotion Officer', 'Fulbaria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993758', 'khalilurunigroup34@gmail.com', '2005-02-01', 1, '2026-05-24 18:00:00'),
(320, '301675', 'Md. Nurnabi Khan', 'Area Sales Executive', 'NICRH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993128', 'nurnabikhan821@gmail.com', '2005-03-01', 1, '2026-05-24 18:00:00'),
(321, '301683', 'Sheikh Md. Azizul Haque', 'Assistant General Manager, Sales', 'North Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993089', 'aziz.haque@unigroup-bd.com', '2005-02-19', 1, '2026-05-24 18:00:00'),
(322, '301690', 'Md. Aurongojeb Elite', 'Senior Area Sales Manager', 'Bogra-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993475', 'ajebetila@gmail.com', '2005-04-05', 1, '2026-05-24 18:00:00'),
(323, '301707', 'Md. Nazmul Islam', 'Senior Area Sales Manager', 'Halishahar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993053', 'nazmuluht.unigroup@gmail.com', '2005-04-24', 1, '2026-05-24 18:00:00'),
(324, '301728', 'Md. Shahin Miah', 'Senior Area Sales Manager', 'Sreemangal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993695', 'shahingo98@gmail.com', '2005-05-25', 1, '2026-05-24 18:00:00'),
(325, '301735', 'Mohammad Nasir Uddin', 'Deputy General Manager, Sales', 'West Chattogram Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993155', 'nasir.uddin@unigroup-bd.com', '2005-06-18', 1, '2026-05-24 18:00:00'),
(326, '301745', 'Mirza Menon Morshed', 'Senior Area Sales Manager', 'BSMMU-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993166', 'menonmorshed1973@gmail.com', '2005-07-02', 1, '2026-05-24 18:00:00'),
(327, '301768', 'Atiqur Rahman', 'Senior Area Sales Manager', 'Mitford-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993143', 'bdatiqur123@gmail.com', '2005-07-31', 1, '2026-05-24 18:00:00'),
(328, '301770', 'Md. Fazlul Haque', 'Deputy Sales Manager', 'Uttara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993068', 'fazlul.haque@unigroup-bd.com', '2005-08-05', 1, '2026-05-24 18:00:00'),
(329, '301790', 'Md. Abdul Momin', 'Assistant Sales Manager', 'Mymensingh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993066', 'abdul.momin@unigroup-bd.com', '2005-09-01', 1, '2026-05-24 18:00:00'),
(330, '301791', 'Muhammad Saiful Islam', 'Senior Medical Promotion Officer', 'Gazipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993737', 'saifulgazipur0877@gmail.com', '2005-08-27', 1, '2026-05-24 18:00:00'),
(331, '301793', 'Md. Alamgir Kabir', 'Senior Area Sales Manager', 'NIKDU', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993157', 'akabirunigroup@gmail.com', '2005-09-15', 1, '2026-05-24 18:00:00'),
(332, '301830', 'M M Sharifuzzaman', 'Senior Area Sales Manager', 'Thakurgaon-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993621', 'zamansharif861@gmail.com', '2005-11-08', 1, '2026-05-24 18:00:00'),
(333, '301838', 'Syed Mahbubur Rahman', 'Senior Area Sales Manager', 'Mugda-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993686', 'syedrahman.unigroup@gmail.com', '2005-11-05', 1, '2026-05-24 18:00:00'),
(334, '301852', 'Md. Mosharraf Hossain', 'Senior Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993760', 'md.mosharraf714@gmail.com', '2005-12-22', 1, '2026-05-24 18:00:00'),
(335, '301859', 'Md. Rashadul Hasan', 'Senior Medical Promotion Officer', 'BIRDEM-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993647', 'rashedump@gmail.com', '2005-12-17', 1, '2026-05-24 18:00:00'),
(336, '301870', 'Md. Abdul Hannan', 'Area Sales Executive', 'Jassore-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993714', 'ah2023593@gmail.com', '2005-12-21', 1, '2026-05-24 18:00:00'),
(337, '301871', 'Sujit Pal', 'Senior Area Sales Manager', 'B.Baria-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993565', 'sujitunigroup@gmail.com', '2006-02-02', 1, '2026-05-24 18:00:00'),
(338, '301874', 'Shyamal Chandra Datta', 'Area Sales Executive', 'Narayanganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993750', 'shyamal.datta001@gmail.com', '2006-02-01', 1, '2026-05-24 18:00:00'),
(339, '301885', 'Md. Abdul Mannan', 'Area Sales Executive', 'Chandina-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993793', 'amannan9890@gmail.com', '2006-02-01', 1, '2026-05-24 18:00:00'),
(340, '301887', 'Abdur Rahim', 'Senior Area Sales Manager', 'Narsingdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993726', 'abdurrahimunigroup@gmail.com', '2006-02-01', 1, '2026-05-24 18:00:00'),
(341, '301888', 'Md. Sakander Ali', 'Senior Area Sales Manager', 'Tongi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993912', 'pbn1351@gmail.com', '2006-02-04', 1, '2026-05-24 18:00:00'),
(342, '301890', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'BSMMU/Rampura-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993146', 'mizanuht@gmail.com', '2006-03-01', 1, '2026-05-24 18:00:00'),
(343, '301891', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Kushtia-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993608', 'arif.unigroup59@gmail.com', '2006-02-01', 1, '2026-05-24 18:00:00'),
(344, '301911', 'Mahfuzur Rahman', 'Deputy Sales Manager', 'B.Baria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993164', 'mahfuzur.rahman@unigroup-bd.com', '2006-04-26', 1, '2026-05-24 18:00:00'),
(345, '301922', 'Mohammad Akterojjaman', 'Assistant Sales Manager', 'Rajshahi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993076', 'aktero.jaman@unigroup-bd.com', '2006-04-26', 1, '2026-05-24 18:00:00'),
(346, '301927', 'Uzzal Kanti Roy', 'Assistant Sales Manager', 'Chittagong', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993067', 'uzzal.kanti@unigroup-bd.com', '2006-05-17', 1, '2026-05-24 18:00:00'),
(347, '301931', 'Abu Zafor Masud', 'Senior Area Sales Manager', 'SWMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993195', 'abuzaformasud@gmail.com', '2006-06-13', 1, '2026-05-24 18:00:00'),
(348, '301944', 'Sonjib Kumar Saha', 'Senior Area Sales Manager', 'Lakshmipur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993640', 'sonjib.unimed2018@gmail.com', '2006-08-08', 1, '2026-05-24 18:00:00'),
(349, '301947', 'M M Mahmud Hossen', 'Senior Area Sales Manager', 'Satkhira-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993873', 'mahmud.unigroup@gmail.com', '2006-09-02', 1, '2026-05-24 18:00:00'),
(350, '301963', 'Kuddus Miah', 'Senior Area Sales Manager', 'Chandpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993152', 'kuddus7961@gmail.com', '2007-10-10', 1, '2026-05-24 18:00:00'),
(351, '301993', 'Mosharraf Hossain', 'Senior Medical Promotion Officer', 'Sharankhola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993704', 'mosharrafhossain1911@gmail.com', '2007-06-10', 1, '2026-05-24 18:00:00'),
(352, '302001', 'Md. Hasnat Zaman', 'Senior Medical Promotion Officer', 'Kishoreganj-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993730', 'tapanntk@gmail.com', '2007-06-17', 1, '2026-05-24 18:00:00'),
(353, '302003', 'Abdullah', 'Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01768279421', 'abdullahmpi862323@gmail.com', '2019-04-16', 1, '2026-05-24 18:00:00'),
(354, '302005', 'Md. Ahasan Habib', 'Senior Medical Promotion Officer', 'Mugda-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993170', 'ahasanhabib.unimed@gmail.com', '2007-06-17', 1, '2026-05-24 18:00:00'),
(355, '302011', 'Md. Baized Rahman', 'Senior Area Sales Manager', 'Barisal-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993863', 'baizedrahman8@gmail.com', '2007-07-19', 1, '2026-05-24 18:00:00'),
(356, '302022', 'Mohammad Eleus Miah', 'Senior Area Sales Manager', 'Maijdee-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993682', 'eleus.uni@gmail.com', '2007-08-27', 1, '2026-05-24 18:00:00'),
(357, '302024', 'Mustafa Majid Ibrahim Khan', 'Director, Quality Control ', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01929993284', 'ibrahim.khan@unigroup-bd.com', '2005-02-01', 1, '2026-05-24 18:00:00'),
(358, '302025', 'Md. Mostafizur Rahman Rumon', 'Director, Commercial and PPIC', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01929993285', 'mostafiz.rahman@unigroup-bd.com', '2009-07-01', 1, '2026-05-24 18:00:00'),
(359, '302026', 'Md. Zakaria Hossain', 'Executive Director, Operations', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'General  Administration', '01929993283', 'zakaria.hossain@unigroup-bd.com', '2009-09-01', 1, '2026-05-24 18:00:00'),
(360, '302029', 'Shyamol Chandra Sarker', 'Senior Area Sales Manager', 'Debidwar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993799', 'shyamolsarkar.unigroup@gmail.com', '2007-08-28', 1, '2026-05-24 18:00:00'),
(361, '302041', 'Liton Roy Sarkar', 'Senior Area Sales Manager', 'Patiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993862', 'litonsarkar.unigroup@gmail.com', '2007-09-17', 1, '2026-05-24 18:00:00'),
(362, '302042', 'Md. Golam Rabbani', 'Senior Sales Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01939994888', 'gmuzzalmukta@gmail.com', '1997-05-10', 1, '2026-05-24 18:00:00'),
(363, '302044', 'Naba Kumar Kundu', 'Assistant Manager, Distribution', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993080', 'naba.kumar@unigroup-bd.com', '2007-09-29', 1, '2026-05-24 18:00:00'),
(364, '302066', 'Nahid Mostarin', 'Manager, Packaging', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989994088', 'nahid.mostarin@unigroup-bd.com', '2007-10-17', 1, '2026-05-24 18:00:00'),
(365, '302067', 'Sukhendu Chandra Dhar', 'Senior Area Sales Manager', 'Kasba\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993727', 'sukhendu.unigroup@gmail.com', '2007-12-01', 1, '2026-05-24 18:00:00'),
(366, '302111', 'Golam Sarwar Mazumzer', 'Senior Medical Promotion Officer', 'Shantinagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993147', 'gmsarwar1976@gmail.com', '2008-05-25', 1, '2026-05-24 18:00:00'),
(367, '302112', 'Md. Abdul Karim', 'Senior Medical Promotion Officer', 'Kishoreganj-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993745', 'mdkarim.unigroup@gmail.com', '2008-06-13', 1, '2026-05-24 18:00:00'),
(368, '302115', 'Md. Anisur Rahman', 'Senior Area Sales Manager', 'Barisal-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993911', 'anis.unigroup@gmail.com', '2008-06-05', 1, '2026-05-24 18:00:00'),
(369, '302126', 'Md. Zahangir Alam', 'Senior Area Sales Manager', 'Rangpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993887', 'unizalam@gmail.com', '2008-06-26', 1, '2026-05-24 18:00:00'),
(370, '302132', 'Md. Mozammal Hossain', 'Area Sales Manager', 'Chakaria-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993678', 'mdmozammalam@gmail.com', '2008-06-28', 1, '2026-05-24 18:00:00'),
(371, '302135', 'Fakhrul Islam', 'Senior Area Sales Manager', 'Maijdee-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993656', 'fakhrul.islamunigroup@gmail.com', '2007-07-29', 1, '2026-05-24 18:00:00'),
(372, '302146', 'Nurul Absar', 'Senior Area Sales Manager', 'Chowmuhani-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993879', 'nurulabsar.unigroup@gmail.com', '2007-07-01', 1, '2026-05-24 18:00:00'),
(373, '302166', 'Shukhdeb Shamaddar', 'Senior Area Sales Manager', 'DMCH-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993160', 'sshamaddar@gmail.com', '2008-11-01', 1, '2026-05-24 18:00:00'),
(374, '302173', 'Md. Mofizur Rahman', 'Senior Medical Promotion Officer', 'Satkhira-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993844', 'mdmofizurrahman60@gmail.com', '2008-11-02', 1, '2026-05-24 18:00:00'),
(375, '302179', 'M M Jahangir Alam', 'Area Sales Manager', 'Gopalganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993540', 'khljahangir@gmail.com', '2008-11-06', 1, '2026-05-24 18:00:00'),
(376, '302188', 'Abul Kashem Mollah', 'Senior Area Sales Manager', 'Chatkhil', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993711', 'mdabulkashemuth@gmail.com', '2008-12-01', 1, '2026-05-24 18:00:00'),
(377, '302189', 'Monowar Hossain', 'Senior Area Sales Manager', 'Jassore-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993703', 'monowar551unigroup@gmail.com', '2008-12-01', 1, '2026-05-24 18:00:00'),
(378, '302207', 'Md. Abul Khaer Mondal', 'Senior Area Sales Manager', 'Maijdee-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993878', 'khaer.unigroup@gmail.com', '2009-02-01', 1, '2026-05-24 18:00:00'),
(379, '302209', 'Md. Enamul Haque', 'Senior Medical Promotion Officer', 'Amirabad-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993874', 'ehaq5659@gmail.com', '2009-03-01', 1, '2026-05-24 18:00:00'),
(380, '302233', 'Md. Altaf Hossain Akand', 'Area Sales Executive', 'Lakshmipur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996996', 'mdaltafhossainakond@gmail.com', '2009-03-13', 1, '2026-05-24 18:00:00'),
(381, '302235', 'Md. Habibur Rahman', 'Senior Area Sales Manager', 'Sharsha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993841', 'habibunigroupkh@gmail.com', '2009-04-25', 1, '2026-05-24 18:00:00'),
(382, '302238', 'Sumon Chandra Saha', 'Senior Area Sales Manager', 'SOMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993683', 'unihealthsumon@gmail.com', '2009-04-30', 1, '2026-05-24 18:00:00'),
(383, '302251', 'Belayet Hossain', 'Senior Area Sales Manager', 'Tangail-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993754', 'belayethossain.unigroup@gmail.com', '2009-05-17', 1, '2026-05-24 18:00:00'),
(384, '302252', 'Shyamal Kumar Biswas', 'Area Sales Executive', 'Doulatpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993833', 'Shyamalbiswas.unigroup@gmail.com', '2009-05-16', 1, '2026-05-24 18:00:00'),
(385, '302254', 'Riaz Mahmud Mamun', 'Senior Area Sales Manager', 'Faridpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993832', 'riazmamun.unigroup@gmail.com', '2009-05-16', 1, '2026-05-24 18:00:00'),
(386, '302267', 'Md. Kamal Hossain', 'Senior Area Sales Manager', 'Manikganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993684', 'kamal175.unigroup@gmail.com', '2009-06-17', 1, '2026-05-24 18:00:00'),
(387, '302268', 'Jibon Chandra Sarker', 'Area Sales Executive', 'Kulaura-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993782', 'jibonchandra35@gmail.com', '2009-06-16', 1, '2026-05-24 18:00:00'),
(388, '302274', 'Wahedul Islam', 'Senior Area Sales Manager', 'Kushtia-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993814', 'wahedulislam10192@gmail.com', '2009-07-11', 1, '2026-05-24 18:00:00'),
(389, '302278', 'Md. Sabuj Mia', 'Senior Area Sales Manager', 'Madaripur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993762', 'sabujkhan.unigroup@gmail.com', '2009-07-08', 1, '2026-05-24 18:00:00'),
(390, '302285', 'Md. Toufiqul Alam', 'Senior Area Sales Manager', 'Pabna-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993889', 'toufiqueuht@gmail.com', '2009-01-08', 1, '2026-05-24 18:00:00'),
(391, '302293', 'Md. Kamruzzaman', 'Senior Area Sales Manager', 'Dupchanchia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993858', 'jaman.unithe@gmail.com', '2009-08-31', 1, '2026-05-24 18:00:00'),
(392, '302322', 'Ruhul Amin', 'Senior Area Sales Manager', 'Uttara-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993196', 'ruhul.unigroup@gmail.com', '2009-11-14', 1, '2026-05-24 18:00:00'),
(393, '302338', 'Golam Rasel Beg', 'Senior Medical Promotion Officer', 'Faridpur-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993721', 'golamraselbeg@gmail.com', '2009-12-02', 1, '2026-05-24 18:00:00'),
(394, '302339', 'Md. Rashedul Islam', 'Deputy Sales Manager', 'BSMMU', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993112', 'rashedul.islam@unigroup-bd.com', '2009-12-12', 1, '2026-05-24 18:00:00'),
(395, '302340', 'Shaikh Qudrat Ali', 'Senior Area Sales Manager', 'Bhaluka', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993538', 'monnask603@gmail.com', '2009-12-12', 1, '2026-05-24 18:00:00'),
(396, '302342', 'Md. Saidul Haque', 'Senior Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989993539', 'mdsaidulhaque1976@gmail.com', '2010-01-02', 1, '2026-05-24 18:00:00'),
(397, '302349', 'Javed Parvej Howlader', 'Senior Area Sales Manager', 'Mirzapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993753', 'javedparvej.unigroup@gmail.com', '2010-01-03', 1, '2026-05-24 18:00:00'),
(398, '302353', 'Sirajul Islam', 'Senior Area Sales Manager', 'Lakshmipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993956', 'sirajuluhp@gmail.com', '2010-01-03', 1, '2026-05-24 18:00:00'),
(399, '302379', 'Mohammad Nurul Amin', 'Senior Medical Promotion Officer', 'Sherpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993733', 'nurul.amin0619@gmail.com', '2010-01-07', 1, '2026-05-24 18:00:00'),
(400, '302384', 'Golam Kibria', 'Senior Area Sales Manager', 'Mirpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993161', 'gkibria.unihealth@gmail.com', '2010-01-09', 1, '2026-05-24 18:00:00'),
(401, '302385', 'Manik Sarker', 'Senior Area Sales Manager', 'Magura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993819', 'maniksarker12031981@gmail.com', '2010-01-07', 1, '2026-05-24 18:00:00'),
(402, '302406', 'Mohammad Shahid Ullah', 'Senior Medical Promotion Officer', 'Nawabganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993562', 'shahidullahliton280@gmail.com', '2010-01-01', 1, '2026-05-24 18:00:00'),
(403, '302414', 'Nadim Hussain', 'Senior Area Sales Manager', 'Bhanga', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993560', 'nadimunigroup@gmail.com', '2010-01-17', 1, '2026-05-24 18:00:00'),
(404, '302415', 'Md. Pikul Hoshen', 'Senior Area Sales Manager', 'Bogra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993585', 'pikulhoshenunimed@gmail.com', '2010-01-18', 1, '2026-05-24 18:00:00'),
(405, '302428', 'Neel Ratan Mandal', 'Senior Medical Promotion Officer', 'Barisal-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993623', 'ratanmandal8538@gmail.com', '2010-01-27', 1, '2026-05-24 18:00:00'),
(406, '302435', 'Syed Newamul Kabir', 'Area Sales Manager', 'Sirajganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993597', 'newamulunigroup@gmail.com', '2010-01-30', 1, '2026-05-24 18:00:00'),
(407, '302447', 'Md. Rubel Siddique', 'Senior Area Sales Manager', 'Bogra-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993595', 'rsiddique1983@gmail.com', '2010-02-20', 1, '2026-05-24 18:00:00'),
(408, '302453', 'Pabitra Majumder', 'Senior Medical Promotion Officer', 'Feni-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993882', 'pabitramajumder75@gmail.com', '2010-03-05', 1, '2026-05-24 18:00:00'),
(409, '302459', 'Mustafizur Rahman', 'Senior Area Sales Manager', 'Sreenagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993156', 'mustafiz3156@gmail.com', '2010-02-27', 1, '2026-05-24 18:00:00'),
(410, '302464', 'Md. Rasenur Al Mahmud ', 'Senior Area Sales Manager', 'Mehedibag', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993592', 'mahmud0411@yahoo.com', '2010-02-26', 1, '2026-05-24 18:00:00'),
(411, '302468', 'Sk Abul Kashem', 'Senior Area Sales Manager', 'Khulna-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993838', 'rashemunigroup@gmail.com', '2010-03-27', 1, '2026-05-24 18:00:00'),
(412, '302511', 'Mohammad Zabir Hossain', 'Senior Medical Promotion Officer', 'DMCH-D1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993167', 'zabirunigroup@gmail.com', '2008-09-30', 1, '2026-05-24 18:00:00'),
(413, '302527', 'Md. Mahfuzul Huq', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993024', 'mahfuz.huq@unigroup-bd.com', '2009-05-13', 1, '2026-05-24 18:00:00'),
(414, '302528', 'Md. Serajul Islam', 'Director, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993021', 'seraj.islam@unigroup-bd.com', '2017-08-01', 1, '2026-05-24 18:00:00'),
(415, '302533', 'Md. Abul Khair', 'Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993360', 'abul.khair@unigroup-bd.com', '2009-09-05', 1, '2026-05-24 18:00:00'),
(416, '302535', 'Md. Forkan Hossain', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01921555444', 'forkan.uht@gmail.com', '2010-01-01', 1, '2026-05-24 18:00:00'),
(417, '302566', 'Rafiqul Islam', 'DTP Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993025', 'rafiqul.islam@unigroup-bd.com', '2011-01-01', 1, '2026-05-24 18:00:00'),
(418, '302568', 'Sharat Halder', 'Senior Transport Supervisor', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993277', '', '2011-01-01', 1, '2026-05-24 18:00:00'),
(419, '302578', 'Md. Fazlul Haque', 'Senior Graphic Designer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993406', 'fazlu.unihealth@gmail.com', '2011-01-15', 1, '2026-05-24 18:00:00'),
(420, '302591', 'Md. Sabbir Hossain', 'Marketing Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993366', 'sabbir.hossain@unigroup-bd.com', '2012-01-16', 1, '2026-05-24 18:00:00'),
(421, '302617', 'Md. Salah Uddin', 'Senior Graphic Designer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994197', 'salahbd2018@gmail.com', '2013-01-01', 1, '2026-05-24 18:00:00'),
(422, '302622', 'Md. Anisur Rahman', 'Deputy General Manager, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993456', 'anisur.rahman@unigroup-bd.com', '2013-02-02', 1, '2026-05-24 18:00:00'),
(423, '302623', 'Rezaur Rahman Siddiqui', 'Deputy General Manager, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993454', 'reza.rahman@unigroup-bd.com', '2013-02-09', 1, '2026-05-24 18:00:00'),
(424, '302624', 'Md. Akramul Kabir', 'Deputy General Manager, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993459', 'akram.kabir@unigroup-bd.com', '2013-02-26', 1, '2026-05-24 18:00:00'),
(425, '302643', 'Mansur Ahmed', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993357', 'mansur.ahmed@unigroup-bd.com', '2013-08-19', 1, '2026-05-24 18:00:00'),
(426, '302663', 'Md. Abul Kalam', 'Assistant Manager, VAT & Accounts', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01989993971', 'abkalam01989@gmail.com', '2014-10-20', 1, '2026-05-24 18:00:00'),
(427, '302666', 'Md. Mahmudun Nabi Nayan', 'Marketing Manager', 'Janssen', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989993938', 'mahmud.nabi@unigroup-bd.com', '2014-12-08', 1, '2026-05-24 18:00:00'),
(428, '302673', 'Naseef Ahsan Chowdhury', 'Assistant Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994184', 'naseef.ahsan@unigroup-bd.com', '2015-09-05', 1, '2026-05-24 18:00:00'),
(429, '302675', 'Abul Kawsar', 'Assistant Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994194', 'abul.kawsar@unigroup-bd.com', '2015-01-13', 1, '2026-05-24 18:00:00'),
(430, '302679', 'Mohammad Washim Khan', 'Marketing Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994077', 'washim.khan@unigroup-bd.com', '2015-03-22', 1, '2026-05-24 18:00:00'),
(431, '302681', 'Shahidul Alam', 'Assistant Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994285', 'shahidul.alam@unigroup-bd.com', '2015-04-11', 1, '2026-05-24 18:00:00'),
(432, '302713', 'Md. Moniruzzaman', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993242', 'monir.zaman@unigroup-bd.com', '2015-11-09', 1, '2026-05-24 18:00:00'),
(433, '302723', 'Md. Hosniwat Khadem', 'Marketing Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993492', 'hosniwat.khadem@unigroup-bd.com', '2015-12-17', 1, '2026-05-24 18:00:00'),
(434, '302729', 'Srijon Ibn Amin', 'Assistant Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994416', 'srijon.amin@unigroup-bd.com', '2016-01-02', 1, '2026-05-24 18:00:00'),
(435, '302759', 'Sajib Kumar Paul', 'Training Manager', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994417', 'sajib.kumar@unigroup-bd.com', '2016-03-22', 1, '2026-05-24 18:00:00'),
(436, '302769', 'Subrata Sarker', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994463', 'subrata.sarker@unigroup-bd.com', '2016-05-03', 1, '2026-05-24 18:00:00'),
(437, '302781', 'Md. Farhad Khan', 'Deputy Manager, Training', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994514', 'farhad.khan@unigroup-bd.com', '2016-07-04', 1, '2026-05-24 18:00:00'),
(438, '302784', 'Mohammad Solaiman Kabir', 'Director, Training', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994464', 'solaiman.kabir@unigroup-bd.com', '2016-05-07', 1, '2026-05-24 18:00:00'),
(439, '302786', 'B M Kaif Saadman Likhon', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993015', 'kaif.saadman@unigroup-bd.com', '2016-09-01', 1, '2026-05-24 18:00:00'),
(440, '302801', 'Md. Kamrul Islam', 'Assistant Manager, Sales Administration', 'Corporate Office', 'UniMed Limited', 'Core Diagnostic Division', '01989994540', 'kamrul.islam@unigroup-bd.com', '2016-10-30', 1, '2026-05-24 18:00:00'),
(441, '302814', 'Khandoker Md. Rezwan Kabir', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989993789', 'rezwan.kabir@unigroup-bd.com', '2017-02-01', 1, '2026-05-24 18:00:00'),
(442, '302815', 'Kazi Abu Zafor Emran', 'Marketing Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994593', 'zafor.emran@unigroup-bd.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(443, '302835', 'Marzuk Sabir', 'Deputy General Manager, Dental', 'Corporate Office', 'UniHealth Limited', 'Dental & Ortho', '01929993339', 'marzuk.sabir@unigroup-bd.com', '2016-01-01', 1, '2026-05-24 18:00:00'),
(444, '302844', 'Md. Abdus Salam', 'Assistant Manager, Sales Administration', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994677', 'abdus.salam@unigroup-bd.com', '2017-04-09', 1, '2026-05-24 18:00:00'),
(445, '302856', 'Nayan Chandra Das', 'Assistant Manager, Regulatory Affairs', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994702', 'nayan.chandra@unigroup-bd.com', '2017-05-28', 1, '2026-05-24 18:00:00'),
(446, '302880', 'Khandkar Reaz Ahammad', 'Senior Area Distribution Manager', 'Lalmonirhat Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993199', 'rifarinku@gmail.com', '2017-10-01', 1, '2026-05-24 18:00:00'),
(447, '302914', 'Arafat Rahman', 'Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994654', 'arafat.rahman@unigroup-bd.com', '2018-05-03', 1, '2026-05-24 18:00:00'),
(448, '302927', 'Mohammad Morshed Masum', 'Deputy Manager, IT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01929993468', 'morshed.masum@unigroup-bd.com', '2018-08-01', 1, '2026-05-24 18:00:00'),
(449, '302941', 'Md. Mahbubul islam', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996930', 'mahbubul.islam@unigroup-bd.com', '2021-07-31', 1, '2026-05-24 18:00:00'),
(450, '302942', 'Md. Habibur Rahman', 'Senior Graphic Designer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989996929', 'habib.designer.unimed@gmail.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(451, '302943', 'Md. Rokonuzzaman Chowdhury', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989996877', 'rokonuz.zaman@unigroup-bd.com', '2018-09-25', 1, '2026-05-24 18:00:00'),
(452, '302951', 'Monoj Kanti Ghoshal', 'Administration Executive', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993345', 'monoj.unigroup@gmail.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(453, '302956', 'Md. Abu Zafor Sadek', 'Deputy General Manager, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993488', 'zafor.sadek@unigroup-bd.com', '2018-12-10', 1, '2026-05-24 18:00:00'),
(454, '302957', 'Shahed Ali', 'Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993033', 'shahed.ali@unigroup-bd.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(455, '302958', 'Sadia Islam', 'Deputy Manager, HR & Administration', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989994315', 'sadia.islam@unigroup-bd.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(456, '302978', 'Jony Kumar Das', 'Senior Sales Administration Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995405', '', '2019-02-01', 1, '2026-05-24 18:00:00'),
(457, '302979', 'Masud Rana', 'Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994613', 'masud.rana@unigroup-bd.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(458, '302980', 'Prosenjit Chowdhury', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989995396', 'prosenjit.chowdhury@unigroup-bd.com', '2019-02-07', 1, '2026-05-24 18:00:00'),
(459, '302982', 'Md. Shakil Faysal Chowdhury', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989995433', 'shakil.faysal@unigroup-bd.com', '2019-02-24', 1, '2026-05-24 18:00:00'),
(460, '302986', 'Abdullah Alam Ibnu Raihan Mahmood Ratul', 'Senior IT Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989993541', 'abdullah.alam@unigroup-bd.com', '2019-03-16', 1, '2026-05-24 18:00:00'),
(461, '302987', 'Asif Ahmed', 'Manager, HR & Administration', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993010', 'asif.ahmed@unigroup-bd.com', '2019-05-02', 1, '2026-05-24 18:00:00'),
(462, '302997', 'Md. Iftekharul Hakim Ifti', 'Senior IT Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989995366', 'iftekharul.hakim@unigroup-bd.com', '2018-11-04', 1, '2026-05-24 18:00:00'),
(463, '303000', 'Nazmul Hossain', 'Managing Director, Pharmaceuticals', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'General  Administration', '01929993002', 'nazmul.hossain@unigroup-bd.com', '1997-06-01', 1, '2026-05-24 18:00:00'),
(464, '303004', 'Muhammad Shamim Alam Khan', 'Executive Director, UniHealth Pharma', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993012', 'shamim.alam@unigroup-bd.com', '2000-01-12', 1, '2026-05-24 18:00:00'),
(465, '303018', 'Anowarul Mamun', 'General Manager, Sales', 'Central South Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993482', 'anowar.mamun@unigroup-bd.com', '2011-01-02', 1, '2026-05-24 18:00:00'),
(466, '303102', 'Abdullah Al Ahsan', 'Assistant Manager, IT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996712', 'abdullah.ahsan@unigroup-bd.com', '2019-09-17', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(467, '303105', 'Mahamudur Rahman', 'Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994575', 'mahamudur.rahman@unigroup-bd.com', '2019-10-03', 1, '2026-05-24 18:00:00'),
(468, '303107', 'Raysul Islam Ripon', 'Senior IT Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01929993233', 'raysul.islam@unigroup-bd.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(469, '303108', 'Md. Moniruzzaman', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996754', 'moniruz.zaman@unigroup-bd.com', '2019-12-21', 1, '2026-05-24 18:00:00'),
(470, '303109', 'Shuva Datta', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994778', 'shuva.datta@unigroup-bd.com', '2020-01-01', 1, '2026-05-24 18:00:00'),
(471, '303110', 'Md. Ibrar Hossain', 'Commercial Manager', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989994185', '', '2016-05-03', 1, '2026-05-24 18:00:00'),
(472, '303111', 'S M Murad Hossain', 'Director, Procurement and New Projects', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01929993331', 'murad.hossain@unigroup-bd.com', '2022-07-01', 1, '2026-05-24 18:00:00'),
(473, '303116', 'Md. Abdullah Zaid Miazi', 'Senior Commercial Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989996770', 'abdullah.zaid@unigroup-bd.com', '2020-02-02', 1, '2026-05-24 18:00:00'),
(474, '303120', 'Md. Mustafa Arafat', 'Assistant Manager, IT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996887', 'mustafa.arafat@unigroup-bd.com', '2020-02-08', 1, '2026-05-24 18:00:00'),
(475, '303122', 'Md. Rasheduzzaman', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996880', 'rasheduz.zaman@unigroup-bd.com', '2020-06-01', 1, '2026-05-24 18:00:00'),
(476, '303123', 'Kaniz Fatema', 'Assistant Manager, Medical Services', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996866', 'kaniz.fatema@unigroup-bd.com', '2020-03-01', 1, '2026-05-24 18:00:00'),
(477, '303125', 'Sarder Istiaque Ahmed', 'Assistant Manager, Training', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994982', 'istiaque.ahmed@unigroup-bd.com', '2020-09-07', 1, '2026-05-24 18:00:00'),
(478, '303128', 'Shauket Hossain', 'General Manager, Marketing', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989993937', 'shauket.hossain@unigroup-bd.com', '2020-12-01', 1, '2026-05-24 18:00:00'),
(479, '303130', 'A. K. M. Shamsur Rahman', 'Group Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993023', 'shamsur.rahman@unigroup-bd.com', '2021-01-03', 1, '2026-05-24 18:00:00'),
(480, '303132', 'Md. Naim Faisal', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996896', 'naim.faisal@unigroup-bd.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(481, '303134', 'Md. Shazzad Bappy', 'Senior Digital Marketing Executive', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996898', 'shazzad.bappy@unigroup-bd.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(482, '303148', 'Ridwanul Alam', 'Assistant General Manager, IT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996951', 'ridwanul.alam@unigroup-bd.com', '2021-03-08', 1, '2026-05-24 18:00:00'),
(483, '303150', 'Kazi Mashiul Alam', 'Purchase Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989994072', 'kmashiul1974@gmail.com', '2021-06-16', 1, '2026-05-24 18:00:00'),
(484, '303151', 'Joly Pervin', 'Senior HR Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989996990', 'joly.pervin@unigroup-bd.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(485, '303152', 'Md. Akib Sorwar', 'Senior Officer, Protocol & Liaison', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996955', 'akib.sorwar@unigroup-bd.com', '2021-07-05', 1, '2026-05-24 18:00:00'),
(486, '303153', 'Tapash Howlader', 'Assistant Manager, Regulatory Affairs', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993243', 'tapash.unigroup@gmail.com', '2021-09-01', 1, '2026-05-24 18:00:00'),
(487, '303154', 'Mohammad Mahedi Hasan', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01557044614', 'mehedi.hasan.7615@gmail.com', '2021-09-01', 1, '2026-05-24 18:00:00'),
(488, '303155', 'Abdullah Ar Rafee', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994183', 'rafeeabdullahar@gmail.com', '2021-12-01', 1, '2026-05-24 18:00:00'),
(489, '303157', 'Musavvir Al Jami', 'Senior IT Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996989', 'musavvir.jami@unigroup-bd.com', '2021-12-01', 1, '2026-05-24 18:00:00'),
(490, '303158', 'Md. Shihab Uddin', 'Senior IT Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989996713', 'shihab.uddin@unigroup-bd.com', '2021-12-01', 1, '2026-05-24 18:00:00'),
(491, '303159', 'Arefin Ahmed Soyeb', 'Senior Commercial Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989996959', 'arefin.ahmed@unigroup-bd.com', '2021-12-04', 1, '2026-05-24 18:00:00'),
(492, '303160', 'GKM Jakir Hossain', 'Assistant General Manager, Export', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'International Business', '01929993249', 'jakir.hossain@unigroup-bd.com', '2022-01-11', 1, '2026-05-24 18:00:00'),
(493, '303161', 'Md. Ibrahim Khalil', 'Commercial Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989996958', 'ibrahimkhalil9319@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(494, '303168', 'Md. Humayun Kabir', 'Junior Purchase Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989996912', 'humaun206@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(495, '303170', 'Zahid Kamal', 'Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993390', 'zahid.kamal@unigroup-bd.com', '2021-03-01', 1, '2026-05-24 18:00:00'),
(496, '303171', 'Md. Kamrul Hassan', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989994262', 'kamrul199589@gmail.com', '2022-05-21', 1, '2026-05-24 18:00:00'),
(497, '303174', 'Md. Walid Bin Omar', 'Assistant Manager, Procurement', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989996984', 'walid.omar@unigroup-bd.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(498, '303175', 'Md. Habibur Rahman', 'Deputy Manager, HR & Administration', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989996895', 'habibursumon1@gmail.com', '2022-06-29', 1, '2026-05-24 18:00:00'),
(499, '303176', 'Md. Rakibul Hassan', 'Senior Officer, VAT & Accounts', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01716597015', 'rakib.unigroup97@gmail.com', '2022-09-24', 1, '2026-05-24 18:00:00'),
(500, '303179', 'Mohammed Shaheen Mazumder', 'Assistant Sales Manager', 'Comilla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997007', 'shaheen.mazumder@unigroup-bd.com', '2023-01-01', 1, '2026-05-24 18:00:00'),
(501, '303189', 'Shamal Chandra Devnath', 'Assistant Manager, Communication & Media', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997003', 'shamol.nath@unigroup-bd.com', '2023-01-01', 1, '2026-05-24 18:00:00'),
(502, '303191', 'Md. Obaidur Reza Choudhury', 'Director, Medical Services', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994990', 'obaidur.choudhury@unigroup-bd.com', '2023-07-01', 1, '2026-05-24 18:00:00'),
(503, '303192', 'Md. Al-Wasee', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989997254', 'alwasee77@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(504, '303194', 'Ashgar Hatem (Romel)', 'Deputy Manager, Procurement', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989997030', '', '2018-10-01', 1, '2026-05-24 18:00:00'),
(505, '304003', 'Shafiqul Islam', 'Senior Medical Promotion Officer', 'NICRH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996345', 'shafiqul.mojno@gmail.com', '2021-01-05', 1, '2026-05-24 18:00:00'),
(506, '304009', 'Md. Suman Hossain', 'Senior Medical Promotion Officer', 'Mirzapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994636', 'hmsumonhossain1991@gmail.com', '2021-01-14', 1, '2026-05-24 18:00:00'),
(507, '304010', 'Md. Jahidul Islam', 'Senior Medical Promotion Officer', 'Madhabdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996812', 'jahidulislam.nds@gmail.com', '2021-01-12', 1, '2026-05-24 18:00:00'),
(508, '304011', 'Arshad Hosen', 'Medical Promotion Officer', 'Palash', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993204', 'arshadunimed@gmail.com', '2021-01-13', 1, '2026-05-24 18:00:00'),
(509, '304012', 'Md. Mohibul Hasan', 'Medical Promotion Officer', 'Sreenagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993187', 'mohibul.hasan005@gmail.com', '2021-01-10', 1, '2026-05-24 18:00:00'),
(510, '304019', 'Md. Mozanur Rahman Labu', 'Senior Medical Promotion Officer', 'Natore-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994991', 'parveslabu@gmail.com', '2021-03-31', 1, '2026-05-24 18:00:00'),
(511, '304020', 'Md. Khayrul Basar', 'Senior Medical Promotion Officer', 'Kawkhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995596', 'khayrulsirius90@gmail.com', '2021-04-01', 1, '2026-05-24 18:00:00'),
(512, '304021', 'Md. Zahangir Alam', 'Senior Medical Promotion Officer', 'Rajnagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994880', 'mdzahangiralamm18@gmail.com', '2021-03-27', 1, '2026-05-24 18:00:00'),
(513, '304022', 'Md. Monjurul Islam', 'Senior Medical Promotion Officer', 'Badhaghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994773', 'monjurul90.unigroup@gmail.com', '2021-03-27', 1, '2026-05-24 18:00:00'),
(514, '304025', 'Md. Abdul Alim', 'Senior Medical Promotion Officer', 'Rangamati-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995229', 'aralim@gmail.com', '2021-04-02', 1, '2026-05-24 18:00:00'),
(515, '304026', 'Masum Miah', 'Senior Medical Promotion Officer', 'EWMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996323', 'masummiah9191@gmail.com', '2021-04-27', 1, '2026-05-24 18:00:00'),
(516, '304031', 'Md. Moktarul Alam', 'Medical Promotion Officer', 'Bogra-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996349', 'moktarulalam123@gmail.com', '2021-04-03', 1, '2026-05-24 18:00:00'),
(517, '304032', 'Abdus Salam', 'Senior Medical Promotion Officer', 'ShewraPara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996855', 'abdussalambid9@gmail.com', '2021-04-01', 1, '2026-05-24 18:00:00'),
(518, '304037', 'Joni Ahmed', 'Senior Medical Promotion Officer', 'Mymensingh-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996320', 'joniahmed18695@gmail.com', '2021-04-05', 1, '2026-05-24 18:00:00'),
(519, '304038', 'Md. Mesbaul Islam', 'Senior Medical Promotion Officer', 'Rajshahi-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996814', 'mesbaul.noyon@gmail.com', '2021-04-05', 1, '2026-05-24 18:00:00'),
(520, '304041', 'Bablu Ram Barman', 'Senior Medical Promotion Officer', 'Sherpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994854', 'bablurambarman@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(521, '304042', 'Mahbubul Alam Khan', 'Senior Medical Promotion Officer', 'Barura-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995443', 'mahbubul206@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(522, '304043', 'Debbroto Roy', 'Senior Medical Promotion Officer', 'Fenchuganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993694', 'debobrotoroy22@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(523, '304045', 'Md. Atikujjaman', 'Senior Medical Promotion Officer', 'Debidwar-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994756', 'atikjaman121@gmail.com', '2021-06-27', 1, '2026-05-24 18:00:00'),
(524, '304047', 'Md. Zakir Hossain', 'Senior Medical Promotion Officer', 'Sharsha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995556', 'zakirhossain5040@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(525, '304048', 'Md. Ariful Islam', 'Medical Promotion Officer', 'Badargonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993702', 'mdarifulislam81991@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(526, '304050', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Rangpur-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995164', 'rashedulislam6066@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(527, '304051', 'Md. Forhad Ali', 'Senior Medical Promotion Officer', 'Pirgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996861', 'mdforhad1994@gmail.com', '2021-03-15', 1, '2026-05-24 18:00:00'),
(528, '304054', 'Md. Ashraful Hoque Khan', 'Senior Medical Promotion Officer', 'Gulshan-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994135', 'ashrafulhkhan93@gmail.com', '2021-06-28', 1, '2026-05-24 18:00:00'),
(529, '304058', 'Md. Nazmul Haque', 'Senior Medical Promotion Officer', 'Nilphamari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995545', 'nazmulhaque2106@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(530, '304063', 'SB Dwan Chandra', 'Medical Promotion Officer', 'Fatikchhari-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995439', 'sbdwanchandra@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(531, '304064', 'Md. Mosaddekul Islam', 'Senior Medical Promotion Officer', 'Faridganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994915', 'mosaddekulislam25@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(532, '304066', 'Md. Wahidul Islam', 'Senior Medical Promotion Officer', 'Rangpur-13', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993588', 'suvroakas88@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(533, '304067', 'Md. Shohel Mia', 'Senior Medical Promotion Officer', 'Chatmohar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994951', 'mdshohelahamed103@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(534, '304068', 'Riajul', 'Senior Medical Promotion Officer', 'Faridgonj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996863', 'ireazual089@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(535, '304069', 'Md. Joynal Abedin', 'Senior Medical Promotion Officer', 'Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996928', 'joynal061989@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(536, '304070', 'Md. Saju Miah', 'Senior Medical Promotion Officer', 'Shahjadpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995283', 'sajuahmed08071994@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(537, '304071', 'Md. Shohag Mia', 'Senior Medical Promotion Officer', 'Nawabpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994913', 'shohag565651@gmail.com', '2021-07-01', 1, '2026-05-24 18:00:00'),
(538, '304073', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'NMC-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993137', 'masudranagsc@gmail.com', '2021-07-26', 1, '2026-05-24 18:00:00'),
(539, '304076', 'Md. Mahfuz Ul Islam', 'Senior Medical Promotion Officer', 'BSMMU-B6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994043', 'mahfuzngn@gmail.com', '2021-07-26', 1, '2026-05-24 18:00:00'),
(540, '304079', 'Sohrab Uddin', 'Medical Promotion Officer', 'Gazipur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996923', 'sohrabuddin1503@gmail.com', '2021-07-26', 1, '2026-05-24 18:00:00'),
(541, '304088', 'Md. Shamim Miah', 'Senior Medical Promotion Officer', 'DMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994449', 'sm5506492@gmail.com', '2021-09-22', 1, '2026-05-24 18:00:00'),
(542, '304091', 'Md. Momenur Islam', 'Senior Medical Promotion Officer', 'Lalpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993859', 'momenurislameer@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(543, '304092', 'Md. Shahinur Alam', 'Medical Promotion Officer', 'Rangpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993933', 'suzzaman1992@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(544, '304095', 'Md. Nazmul Haque', 'Senior Medical Promotion Officer', 'Palashbari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996722', 'haquenazmul.chapai.123@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(545, '304098', 'Md. Abdul Khaleque', 'Senior Medical Promotion Officer', 'Moulvibazar-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994801', 'abdulkhaleque497@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(546, '304100', 'Md. Abdul Manik', 'Senior Medical Promotion Officer', 'Derai-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995106', 'mdmanik2571992@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(547, '304103', 'Md. Shamimul Islam', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994039', 'shamimul.islam@unigroup-bd.com', '2021-09-20', 1, '2026-05-24 18:00:00'),
(548, '304106', 'Md. Saydur Rahman', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994037', '', '2021-09-20', 1, '2026-05-24 18:00:00'),
(549, '304108', 'Md. Polas Hossain', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994038', 'polash.hossain@unigroup-bd.com', '2021-09-20', 1, '2026-05-24 18:00:00'),
(550, '304117', 'Prana Nath Paul', 'Senior Medical Promotion Officer', 'Patiya-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995228', 'aradhopaul@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(551, '304118', 'Hamidur Rahman', 'Senior Medical Promotion Officer', 'Halishahar-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995392', 'sojibrahaman2014@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(552, '304131', 'Md. Ashraful Alam', 'Senior Medical Promotion Officer', 'IBN SINA-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995483', 'alamashraful118@gmail.com', '2021-09-02', 1, '2026-05-24 18:00:00'),
(553, '304132', 'Shakawath Hossain', 'Senior Medical Promotion Officer', 'SRNGLIH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995481', 'shakawathrony@gmail.com', '2021-10-01', 1, '2026-05-24 18:00:00'),
(554, '304134', 'Md. Shamim Hossain', 'Senior Medical Promotion Officer', 'DNMCH/Sumona', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993951', 'shamimhossain9464@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(555, '304138', 'Md. Lion Mahamud', 'Senior Medical Promotion Officer', 'Bogra-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994303', 'lionmahamud2016@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(556, '304140', 'Md. Monisul Haque', 'Senior Medical Promotion Officer', 'Dhamoirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995196', 'monisulhaque14@gmail.com', '2021-10-07', 1, '2026-05-24 18:00:00'),
(557, '304141', 'Md. Atikur Rahman', 'Senior Medical Promotion Officer', 'Lalmohan-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995526', 'atikur800@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(558, '304143', 'Meraj Ali', 'Senior Medical Promotion Officer', 'BIRDEM-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994126', 'merajunigroup@gmail.com', '2021-10-24', 1, '2026-05-24 18:00:00'),
(559, '304144', 'Md. Nur Alam', 'Senior Medical Promotion Officer', 'Joypurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994158', 'nuralam4707@gmail.com', '2021-10-19', 1, '2026-05-24 18:00:00'),
(560, '304145', 'Ibrahim Hossain', 'Senior Medical Promotion Officer', 'Jassore-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996364', 'ibrahimtc2013@gmail.com', '2021-10-24', 1, '2026-05-24 18:00:00'),
(561, '304146', 'Sohel Rana', 'Senior Medical Promotion Officer', 'USTC-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995102', 'sohelbabu382@gmail.com', '2021-10-24', 1, '2026-05-24 18:00:00'),
(562, '304149', 'Md. Jahidul Islam', 'Senior Medical Promotion Officer', 'Gopalganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994711', 'jahiduln242@gmail.com', '2021-10-27', 1, '2026-05-24 18:00:00'),
(563, '304150', 'Md. Ashikuzzaman', 'Senior Medical Promotion Officer', 'Banshkhali-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994780', 'ashikurjammanmoniraj@gmail.com', '2021-11-01', 1, '2026-05-24 18:00:00'),
(564, '304151', 'Md. Shahidul Islam', 'Senior Medical Promotion Officer', 'CMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994263', 'shahid30.09.1993@gmail.com', '2021-10-24', 1, '2026-05-24 18:00:00'),
(565, '304160', 'Subroto Kumar Sarker', 'Senior Medical Promotion Officer', 'Shibaloy', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995280', 'subrotos838@gmail.com', '2021-11-01', 1, '2026-05-24 18:00:00'),
(566, '304162', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Chittagong Road-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994361', 'rashed405010@gmail.com', '2021-11-01', 1, '2026-05-24 18:00:00'),
(567, '304172', 'Md. Ziaur Rahman', 'Senior Medical Promotion Officer', 'Shailkupa', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995368', 'md.ziaur.rahman7664@gmail.com', '2021-12-04', 1, '2026-05-24 18:00:00'),
(568, '304174', 'Md. Obaidullah', 'Senior Medical Promotion Officer', 'Mymensingh-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995188', 'mdobaidull@gmail.com', '2021-12-02', 1, '2026-05-24 18:00:00'),
(569, '304178', 'Khandakar Shahidul Islam', 'Senior Medical Promotion Officer', 'Khaja Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993668', 'shahidulkhandakar859@gmail.com', '2021-12-06', 1, '2026-05-24 18:00:00'),
(570, '304183', 'Md. Mostafizur Rahman', 'Senior Training Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994030', 'mostafizur.rahman@unigroup-bd.com', '2021-11-27', 1, '2026-05-24 18:00:00'),
(571, '304184', 'Md. Nazrul Islam', 'Senior Medical Promotion Officer', 'Mirpur-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994545', 'islamnazrul.9245@gmail.com', '2021-12-05', 1, '2026-05-24 18:00:00'),
(572, '304185', 'Md. Laibur Rahman', 'Senior Medical Promotion Officer', 'Mirpur-C5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993379', 'rahmanlaibur@gmail.com', '2021-12-05', 1, '2026-05-24 18:00:00'),
(573, '304187', 'Dulal Chandra Mahto ', 'Medical Promotion Officer', 'Mirpur-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995611', 'unimedunihealth7l@gmail.com', '2021-12-04', 1, '2026-05-24 18:00:00'),
(574, '304190', 'Md. Abir Hossain', 'Senior Medical Promotion Officer', 'DMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993070', 'mdabirho52@gmail.com', '2021-12-07', 1, '2026-05-24 18:00:00'),
(575, '304195', 'Md. Moktaruzzaman', 'Medical Promotion Officer', 'Rajshahi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996794', 'mdmoktaruzzamanmahin@gmail.com', '2022-01-05', 1, '2026-05-24 18:00:00'),
(576, '304196', 'Md. Arif Hossain', 'Senior Medical Promotion Officer', 'Rangpur-14', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995418', 'arifhossain48612@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(577, '304197', 'Md. Rasel Mahmud', 'Senior Medical Promotion Officer', 'Rajshahi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994806', 'rmahmud515@gmail.com', '2022-01-02', 1, '2026-05-24 18:00:00'),
(578, '304199', 'Manik Das', 'Medical Promotion Officer', 'Kashinathpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994931', 'dasm38155@gmail.com', '2022-01-02', 1, '2026-05-24 18:00:00'),
(579, '304201', 'Md. Osman Shah', 'Senior Medical Promotion Officer', 'Barisal-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993924', 'mithubba@gmail.com', '2022-01-02', 1, '2026-05-24 18:00:00'),
(580, '304204', 'Ashadul Habib', 'Senior Medical Promotion Officer', 'Bayezid', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993890', 'habibashadul1994@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(581, '304205', 'Md. Nurul Islam', 'Senior Medical Promotion Officer', 'Ramganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994987', 'nurulislam2824@gmail.com', '2022-01-02', 1, '2026-05-24 18:00:00'),
(582, '304208', 'Md. Saydur Rahman', 'Senior Medical Promotion Officer', 'Satkhira-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994381', 'saydurrahman257@yahoo.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(583, '304209', 'Nirmol Kumar', 'Senior Medical Promotion Officer', 'Bauphal-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995104', 'nirmoljessbd@gmail.com', '2022-01-04', 1, '2026-05-24 18:00:00'),
(584, '304210', 'Md. Anwar Hossen', 'Medical Promotion Officer', 'Barisal-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994531', 'anwar017675@gmail.com', '2022-12-04', 1, '2026-05-24 18:00:00'),
(585, '304216', 'Md. Nakir Uddin', 'Senior Medical Promotion Officer', 'Eliotgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995272', 'nikonakir@gmail.com', '2022-01-17', 1, '2026-05-24 18:00:00'),
(586, '304218', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Banani-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995321', 'mizanurrahman26873@gmail.com', '2022-01-20', 1, '2026-05-24 18:00:00'),
(587, '304221', 'Asit Chakraboarti', 'Senior Medical Promotion Officer', 'Narail-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994108', 'asitchakraboarti@gmail.com', '2022-02-03', 1, '2026-05-24 18:00:00'),
(588, '304222', 'Md. Melon Sheikh', 'Senior Medical Promotion Officer', 'Digholia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993627', 'mmsheikho121@gmail.com', '2022-02-05', 1, '2026-05-24 18:00:00'),
(589, '304223', 'Biplab Baisnab', 'Senior Medical Promotion Officer', 'Charfesson-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995525', 'biplab.baisnab@gmail.com', '2022-02-03', 1, '2026-05-24 18:00:00'),
(590, '304225', 'Md. Faridul Islam', 'Senior Medical Promotion Officer', 'Board Bazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996819', 'almaktum92@gmail.com', '2022-02-05', 1, '2026-05-24 18:00:00'),
(591, '304226', 'Md. Faruk Hossain Bossunia', 'Senior Medical Promotion Officer', 'Banasree-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996346', 'farukbosunia996@gmail.com', '2022-02-02', 1, '2026-05-24 18:00:00'),
(592, '304227', 'Md. Leton Mia', 'Senior Medical Promotion Officer', 'Bauphal-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994471', 'leton768665@gmail.com', '2022-02-05', 1, '2026-05-24 18:00:00'),
(593, '304230', 'Md. Rafiqul Islam', 'Senior Medical Promotion Officer', 'Rangunia-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994856', 'ibrahimrafiq880@gmail.com', '2022-02-01', 1, '2026-05-24 18:00:00'),
(594, '304241', 'Arif Hossain', 'Senior Medical Promotion Officer', 'Gulshan-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994976', 'hossainarif4142@gmail.com', '2022-03-02', 1, '2026-05-24 18:00:00'),
(595, '304243', 'Md. Shahidul Islam', 'Senior Medical Promotion Officer', 'Rangpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995129', 'md.shahidul1986m@gmail.com', '2022-02-26', 1, '2026-05-24 18:00:00'),
(596, '304246', 'Md. Al- Amin Miah', 'Senior Medical Promotion Officer', 'Rangpur-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995411', 'alaminmiah1230@gmail.com', '2022-03-15', 1, '2026-05-24 18:00:00'),
(597, '304247', 'Hasibul Islam Tusar', 'Senior Medical Promotion Officer', 'Maijdee-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996818', 'hasib1771@gmail.com', '2022-03-20', 1, '2026-05-24 18:00:00'),
(598, '304248', 'Md. Faysal Hossain', 'Senior Medical Promotion Officer', 'Gulshan-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993506', 'mdfaysalh058@gmail.com', '2022-03-20', 1, '2026-05-24 18:00:00'),
(599, '304254', 'Md. Firoj Kabir', 'Senior Medical Promotion Officer', 'Narayanganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996964', 'firojkabir2017@gmail.com', '2022-04-01', 1, '2026-05-24 18:00:00'),
(600, '304256', 'Md. Najiur Rahman', 'Senior Medical Promotion Officer', 'Dinajpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996963', 'najiur.unimed@gmail.com', '2022-04-05', 1, '2026-05-24 18:00:00'),
(601, '304257', 'Md. Sajjad Hossain', 'Senior Medical Promotion Officer', 'Maa O Shishu-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995225', 'mdrubelctg52@gmail.com', '2022-03-31', 1, '2026-05-24 18:00:00'),
(602, '304258', 'Md. Ali Alam Sarker', 'Senior Medical Promotion Officer', 'Santirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994774', 'alialamsarker2017@gmail.com', '2022-03-31', 1, '2026-05-24 18:00:00'),
(603, '304262', 'Shamim Miah', 'Senior Medical Promotion Officer', 'Rajshahi-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993327', 'sm3095538@gmail.com', '2022-04-14', 1, '2026-05-24 18:00:00'),
(604, '304264', 'Maghnath Chandra Paul', 'Senior Medical Promotion Officer', 'Baraiyarhat-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995203', 'maghnathpaul1994bd@gmail.com', '2022-04-12', 1, '2026-05-24 18:00:00'),
(605, '304266', 'Taposh Sarkar', 'Senior Medical Promotion Officer', 'Satkhira-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993571', 'taposhanjolyseli@gmail.com', '2022-04-16', 1, '2026-05-24 18:00:00'),
(606, '304269', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Dhaka Oncology-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995603', '0123masudrana@gmail.com', '2022-04-11', 1, '2026-05-24 18:00:00'),
(607, '304270', 'Sujoy Kumer', 'Senior Medical Promotion Officer', 'Mujibnagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995612', 'sujoysujon@gmail.com', '2022-04-19', 1, '2026-05-24 18:00:00'),
(608, '304273', 'Golam Mahiuddin', 'Senior Medical Promotion Officer', 'Lalmai/Bagmara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995205', 'mahiuddin.eco@gmail.com', '2022-04-21', 1, '2026-05-24 18:00:00'),
(609, '304274', 'Ziko Mazumder', 'Senior Medical Promotion Officer', 'Kachua-1/Sachar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994543', 'zikomazumder1997@gmail.com', '2022-04-20', 1, '2026-05-24 18:00:00'),
(610, '304275', 'Md. Momenul Islam', 'Senior Medical Promotion Officer', 'Nachol', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993600', 'wmomenul@gmail.com', '2022-04-21', 1, '2026-05-24 18:00:00'),
(611, '304276', 'Md. Mamun Miah', 'Senior Medical Promotion Officer', 'PMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993761', 'mamunmiah0092@gmail.com', '2022-04-20', 1, '2026-05-24 18:00:00'),
(612, '304279', 'Md. Rubel Ahmmed', 'Senior Medical Promotion Officer', 'Mirpur-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996899', 'mdrubelahmmedrubel@gmail.com', '2022-04-23', 1, '2026-05-24 18:00:00'),
(613, '304280', 'Sree Krishna Chandra Kha', 'Senior Medical Promotion Officer', 'Narayanganj-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994118', 'krishna.apl001@gmail.com', '2022-04-20', 1, '2026-05-24 18:00:00'),
(614, '304284', 'Atiqur Rahman Sumon', 'Senior Medical Promotion Officer', 'Barisal-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994489', 'atiqurrahman1928@gmail.com', '2022-04-29', 1, '2026-05-24 18:00:00'),
(615, '304285', 'Md. Owasim Akram', 'Senior Medical Promotion Officer', 'Singair-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994410', 'sobuzhossain203@gmail.com', '2022-05-15', 1, '2026-05-24 18:00:00'),
(616, '304286', 'Md. Imran Hossain', 'Senior Medical Promotion Officer', 'Barisal-D3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996383', 'imranunigroup60@gmail.com', '2022-05-16', 1, '2026-05-24 18:00:00'),
(617, '304287', 'Md. Mekail Hossen', 'Senior Medical Promotion Officer', 'Shariatpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996375', 'mikailhossen91@gmail.com', '2022-05-15', 1, '2026-05-24 18:00:00'),
(618, '304288', 'Md. Samrat', 'Senior Medical Promotion Officer', 'Charfesson-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994906', 'samrathowladar977@gmail.com', '2022-05-17', 1, '2026-05-24 18:00:00'),
(619, '304289', 'Sujit Madhu', 'Senior Medical Promotion Officer', 'Lohagara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993651', 'sujitmadhu1990@gmail.com', '2022-05-16', 1, '2026-05-24 18:00:00'),
(620, '304292', 'Md. Roshidul Islam', 'Senior Medical Promotion Officer', 'Popular KS-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996931', 'roshidul01031994@gmail.com', '2022-05-16', 1, '2026-05-24 18:00:00'),
(621, '304293', 'Md. Dulal Hossain', 'Senior Medical Promotion Officer', 'Beanibazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996907', 'mddulalhossan1746@gmail.com', '2022-05-14', 1, '2026-05-24 18:00:00'),
(622, '304294', 'Sazzed Hossain Bhuiya', 'Senior Medical Promotion Officer', 'Sreemangal-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994453', 'sazzadbhuiya007@gmail.com', '2022-05-19', 1, '2026-05-24 18:00:00'),
(623, '304296', 'Md. Jobayer Hossain', 'Senior Medical Promotion Officer', 'Bahubal-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996946', 'jobayerhossain7860@gmail.com', '2022-05-16', 1, '2026-05-24 18:00:00'),
(624, '304298', 'Md. Muksedur Rahman', 'Senior Medical Promotion Officer', 'DMCH-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994911', 'muksedur91@gmail.com', '2022-05-21', 1, '2026-05-24 18:00:00'),
(625, '304299', 'Md. Shariful Islam', 'Senior Medical Promotion Officer', 'Purobi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993742', 'imdshariful430@gmail.com', '2022-05-11', 1, '2026-05-24 18:00:00'),
(626, '304300', 'Al Amin', 'Senior Medical Promotion Officer', 'Araihazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996314', 'kmalamin1994@gmail.com', '2022-05-21', 1, '2026-05-24 18:00:00'),
(627, '304301', 'Md. Aiub Ali', 'Senior Medical Promotion Officer', 'Gazipur-2/Bordbazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995168', 'mmdaiubali@gmail.com', '2022-05-22', 1, '2026-05-24 18:00:00'),
(628, '304304', 'Md. Rafiqul Islam', 'Senior Medical Promotion Officer', 'Urology Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994337', 'rafikul759@gmail.com', '2022-06-04', 1, '2026-05-24 18:00:00'),
(629, '304305', 'Golam Azam', 'Senior Medical Promotion Officer', 'MuMC/Rampura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993657', 'gazam6@gmail.com', '2022-06-05', 1, '2026-05-24 18:00:00'),
(630, '304306', 'Md. Mirajul Islam', 'Senior Medical Promotion Officer', 'Chuadanga-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993842', 'mirajhossainal2022@gmail.com', '2022-06-06', 1, '2026-05-24 18:00:00'),
(631, '304309', 'Md. Aorangajeb Chowdhury', 'Senior Medical Promotion Officer', 'Faridpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994966', 'mdaorangajebchowdhury@gmail.com', '2022-06-08', 1, '2026-05-24 18:00:00'),
(632, '304310', 'Md. Obaydur Rahman', 'Senior Medical Promotion Officer', 'Mirpur-A6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995447', 'mdobaydurr57@gmail.com', '2022-06-08', 1, '2026-05-24 18:00:00'),
(633, '304311', 'Md. Rakibul Islam', 'Senior Medical Promotion Officer', 'Insaf Barakah', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993139', 'rakibulislam2018833@gmail.com', '2022-06-07', 1, '2026-05-24 18:00:00'),
(634, '304312', 'Md. Koushik Ahmed Khan', 'Senior Medical Promotion Officer', 'Gazipur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994744', 'koushik.inc77@gmail.com', '2022-06-08', 1, '2026-05-24 18:00:00'),
(635, '304315', 'Md. Nobirul Islam', 'Senior Medical Promotion Officer', 'Mymensingh-D4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993732', 'nobirulunigroup@gmail.com', '2022-08-16', 1, '2026-05-24 18:00:00'),
(636, '304317', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'Jashore-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996893', 'saifulislamy152@gmail.com', '2022-06-04', 1, '2026-05-24 18:00:00'),
(637, '304318', 'Md. Ashraful Islam', 'Senior Medical Promotion Officer', 'Mirpur-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994109', 'ashrafulislam3602@gmail.com', '2022-06-14', 1, '2026-05-24 18:00:00'),
(638, '304320', 'Md. Alamgir Hossain', 'Senior Medical Promotion Officer', 'Gorjoniya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996394', 'alamgir08160@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(639, '304321', 'Md. Zamirul Islam', 'Senior Medical Promotion Officer', 'Nazipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993632', 'zamil.unigroup@gmail.com', '2022-06-18', 1, '2026-05-24 18:00:00'),
(640, '304324', 'Md. Quamruzzaman', 'Senior Medical Promotion Officer', 'Raninagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995299', 'zamanalvi1989@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(641, '304325', 'Rezaul Karim', 'Senior Medical Promotion Officer', 'Naogaon-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994549', 'rezaul061@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(642, '304326', 'Z-E-A M Salekin', 'Senior Medical Promotion Officer', 'Natore-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993604', 'salekin251291@gmail.com', '2022-06-14', 1, '2026-05-24 18:00:00'),
(643, '304328', 'Md. Moslem Uddin', 'Area Sales Manager', 'Bogra-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993706', 'moslemuddin.unigroup@gmail.com', '2022-10-03', 1, '2026-05-24 18:00:00'),
(644, '304329', 'Oli-Ul-Islam', 'Senior Medical Promotion Officer', 'Dashmina', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995532', 'oliulislam99.m@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(645, '304330', 'Abu Jafor Chowdhury', 'Senior Medical Promotion Officer', 'Gournadi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994964', 'fajlarabby91@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(646, '304331', 'Md. Shohag Korrany', 'Senior Medical Promotion Officer', 'Mehendiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995530', 'shohagkorrany95@gmail.com', '2022-06-14', 1, '2026-05-24 18:00:00'),
(647, '304333', 'Biplob Mistry', 'Senior Medical Promotion Officer', 'Galachipa-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995625', 'biplobmistry292@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(648, '304335', 'Mohasin Sheikh', 'Senior Medical Promotion Officer', 'Chakaria-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993880', 'hridoys5769@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(649, '304337', 'Md. Akteruzzaman', 'Senior Medical Promotion Officer', 'Boalkhali-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993802', 'akter.milon01@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(650, '304338', 'Mithun Chandra Barman', 'Senior Medical Promotion Officer', 'AK.Khan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995223', 'mithunbarman932022@gmail.com', '2022-06-18', 1, '2026-05-24 18:00:00'),
(651, '304340', 'Md. Arshik Hossain', 'Medical Promotion Officer', 'ShSMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994556', 'jituarsik@gmail.com', '2022-06-24', 1, '2026-05-24 18:00:00'),
(652, '304341', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Kamrangirchar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996325', 'mamunaminulislam005@gmail.com', '2022-06-28', 1, '2026-05-24 18:00:00'),
(653, '304342', 'Md. Murshalin', 'Senior Medical Promotion Officer', 'Chowrasta', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993325', 'mdmurshalin600@gmail.com', '2022-06-21', 1, '2026-05-24 18:00:00'),
(654, '304343', 'Khaja Alam', 'Senior Medical Promotion Officer', 'Madhupur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996815', 'siddikurrahman2329@gmail.com', '2022-06-28', 1, '2026-05-24 18:00:00'),
(655, '304344', 'Md. Wahid Parvez', 'Senior Medical Promotion Officer', 'Malibagh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996763', 'aitso.parvez@gmail.com', '2022-06-28', 1, '2026-05-24 18:00:00'),
(656, '304345', 'Md. Shakibul Islam', 'Senior Medical Promotion Officer', 'Mugda-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994041', 'shakibmili30@gmail.com', '2022-06-19', 1, '2026-05-24 18:00:00'),
(657, '304346', 'Dabbrota Sutradhar', 'Senior Medical Promotion Officer', 'Mirpur-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994812', 'ddebbrotasutradhar87@gmail.com', '2022-05-19', 1, '2026-05-24 18:00:00'),
(658, '304350', 'Shariful Islam', 'Senior Medical Promotion Officer', 'Comilla City-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994704', 'rasel331990@gmail.com', '2022-07-01', 1, '2026-05-24 18:00:00'),
(659, '304351', 'Shofikul Alom Ansary', 'Senior Medical Promotion Officer', 'Ibn Sina-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996339', 'shofikansary@gmail.com', '2022-07-02', 1, '2026-05-24 18:00:00'),
(660, '304352', 'Rubel Kazi', 'Senior Medical Promotion Officer', 'Mohakhali-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996905', 'rubelkazi1000@gmail.com', '2022-07-04', 1, '2026-05-24 18:00:00'),
(661, '304353', 'Ibrahim Ali', 'Medical Promotion Officer', 'Tongi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994172', 'rahanpur1navana21119@gmail.com', '2022-07-04', 1, '2026-05-24 18:00:00'),
(662, '304354', 'Md. Alamin Khan', 'Senior Medical Promotion Officer', 'Jassore-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996342', 'shohag.khan2513@gmail.com', '2022-07-06', 1, '2026-05-24 18:00:00'),
(663, '304355', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Mymensingh-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994259', 'sohelrana001260@gmail.com', '2022-10-15', 1, '2026-05-24 18:00:00'),
(664, '304358', 'Md. Khairul Bari', 'Senior Medical Promotion Officer', 'Munshiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993561', 'kbmilon76@gmail.com', '2022-07-05', 1, '2026-05-24 18:00:00'),
(665, '304360', 'Md. Moniruzzaman', 'Medical Promotion Officer', 'Bogra-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995408', 'shapon.monir93@gmail.com', '2022-07-23', 1, '2026-05-24 18:00:00'),
(666, '304361', 'Shamim Hossain', 'Senior Medical Promotion Officer', 'Sunamganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994270', 'sh440323@gmail.com', '2022-06-16', 1, '2026-05-24 18:00:00'),
(667, '304363', 'Md. Amirul Islam', 'Senior Medical Promotion Officer', 'ShSMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996807', 'amirulislam1st@gmail.com', '2022-11-05', 1, '2026-05-24 18:00:00'),
(668, '304365', 'Ahsan Habib Alvi', 'Medical Promotion Officer', 'Mohakhali-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994124', 'marijuan404@gmail.com', '2022-07-17', 1, '2026-05-24 18:00:00'),
(669, '304366', 'Salauddin Molla', 'Medical Promotion Officer', 'Gulshan-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996979', 'mollasalauddin810@gmail.com', '2022-07-18', 1, '2026-05-24 18:00:00'),
(670, '304375', 'Rajib Ghosh', 'Senior Medical Promotion Officer', 'Shantinagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996773', 'rajibghosh9372@gmail.com', '2022-07-30', 1, '2026-05-24 18:00:00'),
(671, '304377', 'Md. Atoar Hossain', 'Senior Medical Promotion Officer', 'Sylhet-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994462', 'atoarhossain.sap@gmail.com', '2022-07-31', 1, '2026-05-24 18:00:00'),
(672, '304378', 'Humaun Kabir', 'Senior Medical Promotion Officer', 'Bagerhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994587', 'hrd.humayun@gmail.com', '2022-08-03', 1, '2026-05-24 18:00:00'),
(673, '304379', 'Maruf Hasan', 'Senior Medical Promotion Officer', 'Gaibandha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996379', 'marufhasan116595@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(674, '304380', 'Md. Abdul Mazid', 'Senior Medical Promotion Officer', 'Ullapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993774', 'mazid.uniderma@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(675, '304381', 'Md. Ilious Uddin', 'Senior Medical Promotion Officer', 'Khulna-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994368', 'shozib.mother@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(676, '304382', 'Md. Menhaj Uddin', 'Senior Medical Promotion Officer', 'BIRDEM-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996733', 'menhajuddin90@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(677, '304383', 'Md. Golam Rabbani', 'Senior Medical Promotion Officer', 'Kurigram-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994305', 'golamrabbani3419@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(678, '304384', 'Md. Jelhaque Ali', 'Senior Medical Promotion Officer', 'Muradnagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993829', 'mdjelhaqueali123@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(679, '304388', 'Md. Sahabul Haque', 'Medical Promotion Officer', 'Shibganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995407', 'shahabulhaque63@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(680, '304389', 'Md. Rustam Ali', 'Medical Promotion Officer', 'Nondigram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995493', 'rustamlali.ru@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(681, '304390', 'Md. Abdul Hannan', 'Senior Medical Promotion Officer', 'Odarhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994394', 'riajahmed742@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(682, '304391', 'Sabuj Miah', 'Senior Medical Promotion Officer', 'Hathazari-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995441', 'sabujmiah906@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(683, '304394', 'Md. Shohel Rana', 'Senior Medical Promotion Officer', 'Comilla City-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995343', 'mdshohelsarkar111@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(684, '304395', 'Sharif Hossen Munna', 'Senior Medical Promotion Officer', 'Daudkandi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993672', 'shorifhossen090@gmail.com', '2022-08-02', 1, '2026-05-24 18:00:00'),
(685, '304399', 'Md. Elias', 'Senior Medical Promotion Officer', 'Madunaghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994648', 'mdelias87627@gmail.com', '2022-07-28', 1, '2026-05-24 18:00:00'),
(686, '304401', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'Shahrasti-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995217', 'saifuleve@outlook.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(687, '304402', 'Md. Abdullah Al Mamun', 'Senior Medical Promotion Officer', 'National Hospital-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994707', 'mdabdullahalmamun871@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(688, '304407', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'B.Baria-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994574', 'ranamdsohel6970@gmail.com', '2022-08-04', 1, '2026-05-24 18:00:00'),
(689, '304409', 'Md. Hasibur Rahman', 'Medical Promotion Officer', 'Kishoreganj-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994487', 'hasibur4424@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(690, '304410', 'Md. Shamim Hossain', 'Senior Medical Promotion Officer', 'Khulna-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994354', '0963shamim@gmail.com', '2022-07-31', 1, '2026-05-24 18:00:00'),
(691, '304412', 'Tutul Ghosh', 'Senior Medical Promotion Officer', 'Faridpur-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995528', 'tutulghosh12345@gmail.com', '2022-08-03', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(692, '304414', 'Md. Arifur Rahman', 'Medical Promotion Officer', 'Joypurhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995359', 'arifur9676@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(693, '304416', 'Md. Abdulla Al Rali', 'Senior Medical Promotion Officer', 'Nilphamari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995123', 'ranarali03189@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(694, '304419', 'Md. Zohurul Islam', 'Senior Medical Promotion Officer', 'Jessore-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994644', 'zohurulraj19870@gmail.com', '2022-08-07', 1, '2026-05-24 18:00:00'),
(695, '304420', 'Md. Ashraful Alam Ashraf', 'Medical Promotion Officer', 'Kushtia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996312', 'mdasraful41447@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(696, '304421', 'Md. Hazrat Ali', 'Senior Medical Promotion Officer', 'Madhabpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996847', 'hazratpopular@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(697, '304423', 'Raihan Hossain', 'Medical Promotion Officer', 'Rajbari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995163', 'raihanhossain1818@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(698, '304425', 'Md. Masiur Rahman', 'Senior Medical Promotion Officer', 'Syedpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994748', 'mosiur2581987@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(699, '304426', 'Md. Ataur Rahman', 'Senior Medical Promotion Officer', 'Chakaria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994653', 'ataurrahman25986@gmail.com', '2022-08-07', 1, '2026-05-24 18:00:00'),
(700, '304427', 'Md. Abdur Rahman', 'Medical Promotion Officer', 'Jhenaidah-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993840', 'abdurrahman.evh@gmail.com', '2022-08-10', 1, '2026-05-24 18:00:00'),
(701, '304430', 'Habibur Rahman', 'Medical Promotion Officer', 'Satkania', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993663', 'mdhabiburm1989@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(702, '304432', 'Md. Jahedul Islam', 'Medical Promotion Officer', 'Chandpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996828', 'jahedulislamjahid27@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(703, '304433', 'Md. Waheduszaman', 'Medical Promotion Officer', 'Bandartila', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994528', 'waheduzamanwahed@gmail.com', '2022-08-12', 1, '2026-05-24 18:00:00'),
(704, '304434', 'Uzzal Chandra Dev Sharma', 'Senior Medical Promotion Officer', 'Debidwar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994912', 'uzzalsharma258@gmail.com', '2022-08-13', 1, '2026-05-24 18:00:00'),
(705, '304435', 'Md. Nobin Miah', 'Medical Promotion Officer', 'Sreemongal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994323', 'mdnabin1994@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(706, '304436', 'Md. Hamidul Islam', 'Senior Medical Promotion Officer', 'Rangpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994165', 'hamid2017.unigroup@gmail.com', '2022-08-28', 1, '2026-05-24 18:00:00'),
(707, '304437', 'Md. Ruman Howlader', 'Medical Promotion Officer', 'DMCH-C5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995402', 'mdrumanislam08@gmail.com', '2022-08-24', 1, '2026-05-24 18:00:00'),
(708, '304438', 'Md. Mahbub Alom', 'Medical Promotion Officer', 'BSMMU-A6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995403', 'mahbubalom37422@gmail.com', '2022-08-23', 1, '2026-05-24 18:00:00'),
(709, '304439', 'Md. Anis Miah', 'Medical Promotion Officer', 'DMCH-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993176', 'aa6790146@gmail.com', '2022-08-20', 1, '2026-05-24 18:00:00'),
(710, '304444', 'Md. Gulam Mowla Tipu', 'Senior Medical Promotion Officer', 'Mount Adora MT-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995204', 'gulammowla5960@gmail.com', '2022-08-24', 1, '2026-05-24 18:00:00'),
(711, '304448', 'Md. Mamunur Rashid', 'Senior Medical Promotion Officer', 'Malibagh-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993140', 'mamun.shejuti@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(712, '304449', 'Md. Masumul Haque', 'Medical Promotion Officer', 'SIMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993439', 'masum82mm@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(713, '304450', 'Eaqube Mia', 'Senior Medical Promotion Officer', 'Lakshmipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994371', 'eakube29@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(714, '304452', 'Md. Rajib Uddin', 'Senior Medical Promotion Officer', 'Patuakhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995355', 'rajibuddin11086@gmail.com', '2022-08-27', 1, '2026-05-24 18:00:00'),
(715, '304453', 'Md. Pekul Hossain', 'Senior Medical Promotion Officer', 'BSMMU-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994605', 'pekul.hossain12345@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(716, '304455', 'Pappu Kumar', 'Medical Promotion Officer', 'Sylhet-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994325', 'pappumondal780@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(717, '304458', 'Jaynal Abedin', 'Medical Promotion Officer', 'Beanibazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995561', 'jaynal786s@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(718, '304459', 'Md. Jelal Hosain', 'Senior Medical Promotion Officer', 'USTC-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996938', 'jelalhosain@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(719, '304462', 'Md. Shibli Hossain', 'Senior Medical Promotion Officer', 'Sirajganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994662', 'mdshiblihossain@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(720, '304464', 'Md. Ziaur Rahman', 'Medical Promotion Officer', 'Kurigram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994397', 'ziaurrahmanm1990@gmail.com', '2022-08-27', 1, '2026-05-24 18:00:00'),
(721, '304466', 'Md. Shohel Rana', 'Senior Medical Promotion Officer', 'DNMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994889', 'shohelranabd111994@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(722, '304468', 'Md. Kamruzzaman', 'Senior Medical Promotion Officer', 'Mirpur-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996804', 'kr6212992@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(723, '304471', 'Al Amin', 'Senior Medical Promotion Officer', 'Chittagong-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995132', 'alamin351224@gmail.com', '2022-08-28', 1, '2026-05-24 18:00:00'),
(724, '304474', 'Md. Toslim Uddin', 'Senior Medical Promotion Officer', 'Chatkhil', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995184', 'toslimuddin067@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(725, '304475', 'Probir Kumar Ray', 'Senior Medical Promotion Officer', 'Rangpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996365', 'probirray1992@gmail.com', '2022-08-27', 1, '2026-05-24 18:00:00'),
(726, '304476', 'Tipu Shultan', 'Senior Medical Promotion Officer', 'Chowmuhani', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994149', 'shultanshultan68@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(727, '304479', 'Md. Lokman Shaikh', 'Medical Promotion Officer', 'Barisal-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994798', 'mdlokmanshaikhrfl129214@gmail.com', '2022-08-25', 1, '2026-05-24 18:00:00'),
(728, '304480', 'Md. Mohiuddin', 'Senior Medical Promotion Officer', 'Moulvibazar-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994438', 'mmd857092@gmail.com', '2022-08-31', 1, '2026-05-24 18:00:00'),
(729, '304483', 'Md. Abul Kalam Azad', 'Senior Medical Promotion Officer', 'Mymensingh-D2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995263', 'akazadkhanr2@gmail.com', '2022-08-29', 1, '2026-05-24 18:00:00'),
(730, '304486', 'Abdur Rahman', 'Senior Medical Promotion Officer', 'Chauddagram-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996393', 'abdur.rahman1619@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(731, '304490', 'Md. Emran Ali', 'Senior Medical Promotion Officer', 'Coxsbazar-V', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996784', 'emranff@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(732, '304492', 'Md. Raziul Islam', 'Senior Medical Promotion Officer', 'Gobindaganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994130', 'raziulislam654451@gmail.com', '2022-08-31', 1, '2026-05-24 18:00:00'),
(733, '304493', 'Md. Bashidul Islam', 'Senior Medical Promotion Officer', 'Ajmirigonj\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996721', 'bashidul.islam1994@gmail.com', '2022-08-31', 1, '2026-05-24 18:00:00'),
(734, '304495', 'Jakir Hossain', 'Senior Medical Promotion Officer', 'Mirsharai-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993822', 'jakirhossainbd37@gmail.com', '2022-09-03', 1, '2026-05-24 18:00:00'),
(735, '304496', 'Md. Babul Mia', 'Senior Medical Promotion Officer', 'Comilla City-13', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994215', 'amedbabul858@gmail.com', '2022-08-31', 1, '2026-05-24 18:00:00'),
(736, '304497', 'Md. Shemul Ali', 'Senior Medical Promotion Officer', 'Mymensingh-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996868', 'shemulali86@gmail.com', '2022-08-31', 1, '2026-05-24 18:00:00'),
(737, '304498', 'Muha. Abul Bashar Dewan', 'Medical Promotion Officer', 'Pangsa-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996918', 'bashar3@gmail.com', '2022-08-29', 1, '2026-05-24 18:00:00'),
(738, '304503', 'Md. Mobarak Hossain', 'Deputy Manager, Distribution', 'Central Depot, Cash', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993275', 'mobarak.hossain@unigroup-bd.com', '2000-08-10', 1, '2026-05-24 18:00:00'),
(739, '304504', 'Md. Abdul Salam Bhuiyan', 'Senior Area Distribution Manager', 'B.Baria Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994497', 'masalam1979@gmail.com', '2000-03-01', 1, '2026-05-24 18:00:00'),
(740, '304507', 'S. D. H. M. Wahiduzzaman', 'Senior Area Distribution Manager', 'Bogura Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996727', 'waheduz.zaman@unigroup-bd.com', '2001-02-15', 1, '2026-05-24 18:00:00'),
(741, '304509', 'Md. Moazzem Hossain', 'Senior Area Distribution Manager', 'Dinajpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993270', 'mdmoazzemh506@gmail.com', '1999-10-01', 1, '2026-05-24 18:00:00'),
(742, '304511', 'Md. Jakir Hossain', 'Senior Area Distribution Manager', 'Central Depot, Cash', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993502', 'jakirhossain2953@gmail.com', '2000-10-14', 1, '2026-05-24 18:00:00'),
(743, '304512', 'Tarek Mainul Islam', 'Senior Area Audit Manager', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01929993507', 'tarek.islam@unigroup-bd.com', '2003-05-05', 1, '2026-05-24 18:00:00'),
(744, '304514', 'Md. Abul Hasem', 'Senior Area Distribution Manager', 'Uttara Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993299', 'ahashem.unigroup@gmail.com', '1999-12-19', 1, '2026-05-24 18:00:00'),
(745, '304515', 'Shybl Barua', 'Senior Area Distribution Manager', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996728', 'shaibalbarua@unigroup-bd.com', '2000-01-21', 1, '2026-05-24 18:00:00'),
(746, '304517', 'Md. Ayub Hossain', 'Assistant Manager, Distribution', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993344', 'mdayub.hossain1978@gmail.com', '2025-03-16', 1, '2026-05-24 18:00:00'),
(747, '304519', 'Md. Zabaidur Rahman', 'Deputy Manager, Distribution', 'Rangpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993262', 'zabaidur.rahman@unigroup-bd.com', '1999-10-01', 1, '2026-05-24 18:00:00'),
(748, '304520', 'Md. Rafiqul Islam', 'Senior Area Distribution Manager', 'Sylhet Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996729', 'rafiq.rajshahi@gmail.com', '2000-08-16', 1, '2026-05-24 18:00:00'),
(749, '304521', 'Munshi Shamim Hossain', 'Area Distribution Manager', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993265', '', '2005-05-04', 1, '2026-05-24 18:00:00'),
(750, '304531', 'Khandakar Kaium', 'Junior Distribution Officer', 'Savar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01931191466', 'kaium2824@gmail.com', '2002-08-11', 1, '2026-05-24 18:00:00'),
(751, '304534', 'Md. Chunnu Mia', 'Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997074', '', '2001-04-14', 1, '2026-05-24 18:00:00'),
(752, '304536', 'S.M. Mazharul Islam', 'Senior Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996357', '', '2000-11-13', 1, '2026-05-24 18:00:00'),
(753, '304537', 'Md. Enamul Haque Mallik', 'Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997073', 'mdanamulhaquemallick@gmail.com', '2000-04-09', 1, '2026-05-24 18:00:00'),
(754, '304540', 'Md. Nazim Uddin', 'Junior Distribution Officer', 'Narsingdi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01721076245', '', '2003-08-26', 1, '2026-05-24 18:00:00'),
(755, '304546', 'Md. Habibur Rahman Khan', 'Store Officer', 'Thakurgaon Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01721616132', 'hrahman.unigroup@gmail.com', '2003-03-12', 1, '2026-05-24 18:00:00'),
(756, '304553', 'Tobarak Hossain Bhuiyan', 'Sales Representative', 'Narsingdi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01756141530', '', '2003-08-03', 1, '2026-05-24 18:00:00'),
(757, '304566', 'Md. Nazim Uddin', 'Distribution Officer', 'Rajbari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01723423102', 'nazimuddinjr62@gmail.com', '2002-06-17', 1, '2026-05-24 18:00:00'),
(758, '304579', 'Md. Anwar Hossain', 'Senior Distribution Officer', 'Pabna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993272', 'setukhan60217800@gmail.com', '2000-02-10', 1, '2026-05-24 18:00:00'),
(759, '304581', 'Md. Abdul Latif', 'Senior Administration Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989994300', 'abdul.latif@unigroup-bd.com', '2006-01-01', 1, '2026-05-24 18:00:00'),
(760, '304585', 'Md. Zahangir', 'Distribution Officer', 'Rajshahi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01721767388', '', '2000-12-01', 1, '2026-05-24 18:00:00'),
(761, '304597', 'Jamsed Alam Sarker', 'Senior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01716084057', 'jaramoni554@gmail.com', '2000-01-01', 1, '2026-05-24 18:00:00'),
(762, '304599', 'Milton Barua', 'Senior Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993253', 'milton.unihealth@gmail.com', '2000-01-05', 1, '2026-05-24 18:00:00'),
(763, '304603', 'Syed Wahid-Un-Nabi', 'Deputy Manager, Distribution', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993255', 'wahidun.nabi@unigroup-bd.com', '2004-04-08', 1, '2026-05-24 18:00:00'),
(764, '304608', 'Md. Mokhlesur Rahman Molla', 'Junior Distribution Officer', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01952600321', 'mdmokhlesurrahamanmolla@gmail.com', '2000-06-10', 1, '2026-05-24 18:00:00'),
(765, '304619', 'Md. Eir Momin', 'Distribution Officer', 'Bogura Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01718881868', '', '2001-05-01', 1, '2026-05-24 18:00:00'),
(766, '304620', 'Md. Zoynul Abedin Mollah', 'Assistant Manager, Distribution', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993462', 'zoynul.distribution@gmail.com', '2002-08-03', 1, '2026-05-24 18:00:00'),
(767, '304624', 'Alauddin Howlader', 'Sales Administration Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989994631', 'alauddin.unigroup@gmail.com', '2007-04-24', 1, '2026-05-24 18:00:00'),
(768, '304660', 'Jaman Mia', 'Assistant Manager, Warehouse', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01989994217', 'jaman.m@outlook.com', '2005-08-11', 1, '2026-05-24 18:00:00'),
(769, '304663', 'Md. Rabiul Islam', 'Distribution Officer', 'Pirojpur Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995151', 'pirojpur.sc@unigroup-bd.com', '2006-06-04', 1, '2026-05-24 18:00:00'),
(770, '304664', 'Md. Nazrul Islam', 'Distribution Officer', 'Jashore Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01715859734', 'mdnazrulislam171977@gmail.com', '2003-09-11', 1, '2026-05-24 18:00:00'),
(771, '304666', 'Pavel Kanti Sushill', 'Senior Distribution Officer', 'Coxs bazar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993271', 'coxs bazarsc@unigroup-bd.com', '2003-03-01', 1, '2026-05-24 18:00:00'),
(772, '304668', 'Md. Maakher Hossain', 'Distribution Officer', 'Rangpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01718367960', '', '2002-04-16', 1, '2026-05-24 18:00:00'),
(773, '304673', 'Md. Abdur Rashid', 'Distribution Officer', 'Bogura Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01718199569', 'abdurrashid461656@gmail.com', '2001-01-01', 1, '2026-05-24 18:00:00'),
(774, '304675', 'Md. Masuduzzaman', 'Junior Distribution Officer', 'Satkhira Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01741540882', 'mmdmasud026@gmail.com', '2005-10-03', 1, '2026-05-24 18:00:00'),
(775, '304676', 'Md. Saidul Islam', 'Senior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994892', 'saidulislam201@gmail.com', '2005-11-13', 1, '2026-05-24 18:00:00'),
(776, '304681', 'Shohag Sikder', 'Sales Representative', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01724517534', 'sohaghshikder9@gmail.com', '2000-07-25', 1, '2026-05-24 18:00:00'),
(777, '304682', 'Md. Taifur Rahman', 'Senior Distribution Officer', 'Central Depot, Cash', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01720337094', 'taifurtumpa@gmail.com', '2004-01-01', 1, '2026-05-24 18:00:00'),
(778, '304683', 'Md. Miraj Khan', 'Distribution Officer', 'Bhola Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993364', '', '2003-12-01', 1, '2026-05-24 18:00:00'),
(779, '304684', 'Md. Kamal Hossain', 'Senior Distribution Officer', 'Natore Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995145', 'kamalhossainnatore31@gmail.com', '2002-06-02', 1, '2026-05-24 18:00:00'),
(780, '304687', 'Md. Nurun Nabi', 'Distribution Officer', 'Naogaon Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01721254649', 'naogoan.sc@unigroup-bd.com', '2005-12-27', 1, '2026-05-24 18:00:00'),
(781, '304688', 'Md. Ruhul Amin', 'Area Distribution Manager', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01916284540', 'ruhulaminmolla007@gmail.com', '2004-10-15', 1, '2026-05-24 18:00:00'),
(782, '304689', 'Md. Zobaidur Rahman', 'Junior Distribution Officer', 'Naogaon Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01770166496', 'zobaidurrahmanmd45@gmail.com', '2004-07-21', 1, '2026-05-24 18:00:00'),
(783, '304691', 'Masudur Rahman Mollah', 'Deputy Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993361', 'masud.mollah@unigroup-bd.com', '2008-12-02', 1, '2026-05-24 18:00:00'),
(784, '304692', 'Md. Kamruzzaman Mollah', 'Audit Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989997223', 'kamruzzamanmollah027@gmail.com', '2008-08-23', 1, '2026-05-24 18:00:00'),
(785, '304696', 'Md. Kowsar Uddin Mollah', 'Junior Distribution Officer', 'Uttara Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01732034398', '', '2008-11-01', 1, '2026-05-24 18:00:00'),
(786, '304697', 'Mrinal Kanti Mojumder', 'Senior Distribution Officer', 'Gopalganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994708', 'gopalgonj.sc@unigroup-bd.com', '2008-01-01', 1, '2026-05-24 18:00:00'),
(787, '304701', 'Md. Jewel Howlader', 'Junior Distribution Officer', 'Bhola Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01732590151', 'jewelhowlader63@gmail.com', '2005-07-16', 1, '2026-05-24 18:00:00'),
(788, '304703', 'Milon Mahmud', 'Senior Distribution Officer', 'Dinajpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01719346516', 'bappi4650@gmail.com', '2005-09-17', 1, '2026-05-24 18:00:00'),
(789, '304706', 'Md. Jahangir Alom', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994204', 'mdjahangiralombd0000@gmail.com', '2007-05-01', 1, '2026-05-24 18:00:00'),
(790, '304707', 'Md. Faizur Rahman', 'Manager, Accounts', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993352', 'faizur.rahman@unigroup-bd.com', '2009-05-02', 1, '2026-05-24 18:00:00'),
(791, '304709', 'Md. Ibrahim', 'Junior Distribution Officer', 'Narayanganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01927275311', '', '2005-06-01', 1, '2026-05-24 18:00:00'),
(792, '304710', 'Ahsan Habib', 'Distribution Officer', 'Pabna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01731483844', 'mdhabibislam06@gmail.com', '2009-03-01', 1, '2026-05-24 18:00:00'),
(793, '304713', 'Md. Abdul Karim', 'Senior Distribution Officer', 'Gaibandha Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01790691102', 'akarim1982@gmail.com', '2008-07-10', 1, '2026-05-24 18:00:00'),
(794, '304722', 'Md. Mizanur Rahman', 'Junior Distribution Officer', 'Jhalokati Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01815240624', '', '2009-05-01', 1, '2026-05-24 18:00:00'),
(795, '304725', 'Md. Azizul Haque Napti', 'Distribution Officer', 'Shariatpur Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01736959401', 'mdarafat01012323@gmail.com', '2009-06-01', 1, '2026-05-24 18:00:00'),
(796, '304731', 'Md. Kamruzzaman', 'Distribution Officer', 'Gazipur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994709', 'mdkamruzzamanbhuyan7@gmail.com', '2009-04-16', 1, '2026-05-24 18:00:00'),
(797, '304732', 'Pipas Saha', 'Junior Distribution Officer', 'Feni Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01924367435', 'pipashsaha@gmail.com', '2009-09-10', 1, '2026-05-24 18:00:00'),
(798, '304734', 'Md. Kamal Uddin Mollah', 'Junior Distribution Officer', 'Cumilla Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01715055973', 'kamaluddinmollah6@gmail.com', '2009-07-25', 1, '2026-05-24 18:00:00'),
(799, '304736', 'Mohammad Shahadat Hossain', 'Junior Distribution Officer', 'Patiya Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01825130073', '', '2007-01-02', 1, '2026-05-24 18:00:00'),
(800, '304737', 'Md. Anisur Rahman', 'Senior Distribution Officer', 'Narail Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993479', 'narail.sc@unigroup-bd.com', '2008-10-27', 1, '2026-05-24 18:00:00'),
(801, '304739', 'Md. Assaduzzaman', 'Distribution Officer', 'Madaripur Sales Centre', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01937274458', 'nurasaduzzaman49@gmail.com', '2009-04-25', 1, '2026-05-24 18:00:00'),
(802, '304741', 'Bimal Kanti Sushil', 'Junior Distribution Officer', 'Chakaria Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01817450275', 'bimalkanti3077@gmail.com', '2008-03-01', 1, '2026-05-24 18:00:00'),
(803, '304746', 'Md. Ripon Howlader', 'Junior Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01917283438', 'howladerripon110@gmail.com', '2009-11-01', 1, '2026-05-24 18:00:00'),
(804, '304749', 'Samiul Islam', 'Junior Distribution Officer', 'Gazipur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01921949139', '', '2008-06-07', 1, '2026-05-24 18:00:00'),
(805, '304752', 'Md. Habibur Rahman', 'Distribution Officer', 'Sirajganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01745964535', 'rahmanhabibhr877@gmail.com', '2007-09-20', 1, '2026-05-24 18:00:00'),
(806, '304754', 'Jasim Uddin', 'Junior Distribution Officer', 'Kushtia Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01746013170', 'uddinmdjasim5274@gmail.com', '2008-03-18', 1, '2026-05-24 18:00:00'),
(807, '304758', 'Rajan Chandra Karmakar', 'Distribution Officer', 'Sunamganj Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994977', 'rajan20157@gmail.com', '2007-09-19', 1, '2026-05-24 18:00:00'),
(808, '304762', 'Md. Emdadul Haque Khan ', 'Junior Distribution Officer', 'Savar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01719008080', 'emdadulhaque8181@gmail.com', '2005-09-01', 1, '2026-05-24 18:00:00'),
(809, '304763', 'Abdul Mannan', 'Junior Distribution Officer', 'Mymensingh Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01980978273', 'mdmannan01979@gmail.com', '2006-07-20', 1, '2026-05-24 18:00:00'),
(810, '304767', 'Md. Lutfar Rahman', 'Distribution Officer', 'Manikganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993515', 'luthforr807@gmail.com', '2007-06-03', 1, '2026-05-24 18:00:00'),
(811, '304768', 'A K M Faridul Alam', 'Senior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996353', 'akmfaridulaam@gmail.com', '2010-05-14', 1, '2026-05-24 18:00:00'),
(812, '304770', 'Redhwan Ahmed', 'Senior Area Distribution Manager', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994841', 'redwan.unigroup@gmail.com', '2010-01-02', 1, '2026-05-24 18:00:00'),
(813, '304771', 'Md. Zakir Hossain', 'Junior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01954271772', '', '2010-04-08', 1, '2026-05-24 18:00:00'),
(814, '304773', 'Md. Biplob Hossain', 'Senior Distribution Officer', 'Sherpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01688904516', 'biplob.hossain111984@gmail.com', '2010-04-06', 1, '2026-05-24 18:00:00'),
(815, '304775', 'Md. Manik Hossain', 'Senior Distribution Officer', 'Sirajganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993342', 'hossainmanik0330@gmail.com', '2009-02-01', 1, '2026-05-24 18:00:00'),
(816, '304776', 'Habibur Rahman', 'Junior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01724583671', 'mshakhawat734@gmail.com', '2010-06-01', 1, '2026-05-24 18:00:00'),
(817, '304783', 'Fardous Ahmed', 'Junior Distribution Officer', 'Kishoreganj Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01711045921', 'fardousahmed576@gmail.com', '2011-01-01', 1, '2026-05-24 18:00:00'),
(818, '304784', 'Md. Rahedul Islam', 'Junior Distribution Officer', 'Lalmonirhat Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01768288465', '', '2012-11-01', 1, '2026-05-24 18:00:00'),
(819, '304791', 'Md. Al Amin Molla', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01703963931', 'all-mullah340@gmail.com', '2012-06-10', 1, '2026-05-24 18:00:00'),
(820, '304795', 'Md. Sabuj Mira', 'Junior Distribution Officer', 'Patuakhali Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01742599391', '', '2012-08-05', 1, '2026-05-24 18:00:00'),
(821, '304797', 'Md. Billal Hossain', 'Senior Distribution Officer', 'Maijdee Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993260', 'shamimcgc1996@gmail.com', '2012-09-13', 1, '2026-05-24 18:00:00'),
(822, '304799', 'Md. Alamgir', 'Project Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989994693', 'hossainal404@gmail.com', '2012-10-02', 1, '2026-05-24 18:00:00'),
(823, '304800', 'Saddam Hossain', 'Senior Distribution Officer', 'Feni Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01631750556', 'saddamhossain932777@gmail.com', '2012-11-02', 1, '2026-05-24 18:00:00'),
(824, '304802', 'Milton Mazumder', 'Junior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01838261989', '', '2012-11-01', 1, '2026-05-24 18:00:00'),
(825, '304806', 'Md. Masudur Rahman', 'Distribution Officer', 'Tangail Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01933432987', 'masudur105@gmail.com', '2012-09-01', 1, '2026-05-24 18:00:00'),
(826, '304808', 'Sujib Barua', 'Junior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01813873471', '', '2012-03-03', 1, '2026-05-24 18:00:00'),
(827, '304809', 'Rupak Barua', 'Junior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01824952470', '', '2012-03-01', 1, '2026-05-24 18:00:00'),
(828, '304811', 'Abu Bakker Siddique', 'Junior Distribution Officer', 'Naogaon Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01828001584', 'mdabubakkarsiddik081@gmail.com', '2010-10-03', 1, '2026-05-24 18:00:00'),
(829, '304812', 'Md. Abdul Alim', 'Junior Distribution Officer', 'Mirpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01717700800', 'md1185599@gmail.com', '2010-12-10', 1, '2026-05-24 18:00:00'),
(830, '304813', 'Md. Golam Mostafa', 'Distribution Officer', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993254', 'gmostofa.unigroup@gmail.com', '2010-10-10', 1, '2026-05-24 18:00:00'),
(831, '304814', 'Amrita Sarma', 'Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01812995895', 'amritouht@gmail.com', '2009-12-01', 1, '2026-05-24 18:00:00'),
(832, '304825', 'Shafiqul Islam', 'Junior Distribution Officer', 'Natore Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01731437480', '', '2010-08-20', 1, '2026-05-24 18:00:00'),
(833, '304827', 'Md. Robel Mollah', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01986342538', '', '2011-10-06', 1, '2026-05-24 18:00:00'),
(834, '304830', 'Md. Hafizur Rahman', 'Senior Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996911', 'hafejur212@gmail.com', '2011-12-10', 1, '2026-05-24 18:00:00'),
(835, '304831', 'Habibur Rahman', 'Distribution Officer', 'Meherpur Sales Centre', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01318494521', '', '2011-12-24', 1, '2026-05-24 18:00:00'),
(836, '304834', 'Abdul Khaleque khan', 'Junior Distribution Officer', 'Bagerhat Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01743827097', '', '2011-11-16', 1, '2026-05-24 18:00:00'),
(837, '304841', 'Md. Aminul Islam', 'Junior Distribution Officer', 'Magura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01737408259', 'saminul@gmail.com', '2011-03-25', 1, '2026-05-24 18:00:00'),
(838, '304844', 'Md. Abdullah Al Farabi', 'Area Distribution Manager', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994874', 'farabiador955@gmail.com', '2011-11-11', 1, '2026-05-24 18:00:00'),
(839, '304846', 'Md. Humayn Kabir', 'Senior Distribution Officer', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996358', 'khumaun767@gmail.com', '2011-01-20', 1, '2026-05-24 18:00:00'),
(840, '304850', 'Md. Faruk Hossain', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994972', 'faruksorder970@gmail.com', '2013-03-01', 1, '2026-05-24 18:00:00'),
(841, '304854', 'Kamol Chandra Biswas', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01953564025', 'kamolbiswas81@gmail.com', '2013-03-01', 1, '2026-05-24 18:00:00'),
(842, '304855', 'Md. Shahidul Islam', 'Junior Distribution Officer', 'Manikganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01617308794', 'mdshahidulislam1991bd@gmail.com', '2013-02-24', 1, '2026-05-24 18:00:00'),
(843, '304861', 'Mohammad Abdus Sobahan', 'Junior Distribution Officer', 'Rampura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994343', '', '2013-05-07', 1, '2026-05-24 18:00:00'),
(844, '304875', 'Md. Sagor Ali', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01920022820', 'sagorali@gamil.com', '2013-12-05', 1, '2026-05-24 18:00:00'),
(845, '304877', 'Saiful Islam Khan', 'Junior Distribution Officer', 'Bagerhat Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01739539638', '', '2014-04-01', 1, '2026-05-24 18:00:00'),
(846, '304881', 'Ariful Islam', 'Junior Distribution Officer', 'Rajshahi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01760971190', 'mdsalmanfarasi.111@gmail.com', '2014-02-01', 1, '2026-05-24 18:00:00'),
(847, '304885', 'Ali Ahmed Earshad', 'Junior Distribution Officer', 'Uttara Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01715580414', 'aliershad32@gmail.com', '2014-04-01', 1, '2026-05-24 18:00:00'),
(848, '304886', 'Md. Shaju Mia', 'Distribution Officer', 'Laksam Sales Centre', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01967989670', 'shaju.unigroup@gmail.com', '2014-03-01', 1, '2026-05-24 18:00:00'),
(849, '304890', 'Md. Safikul Islam', 'Junior Distribution Officer', 'Bhola Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01744501201', 'safikulislamm296@gmail.com', '2014-02-01', 1, '2026-05-24 18:00:00'),
(850, '304896', 'Md. Tanvir Hossain', 'Area Distribution Manager', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01954964699', 'tanviramin79@gmail.com', '2014-11-10', 1, '2026-05-24 18:00:00'),
(851, '304897', 'Md. Nurul Afsar', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01929993415', 'afsar.unigroup@gmail.com', '2013-09-01', 1, '2026-05-24 18:00:00'),
(852, '304898', 'Sushil Chandra Kar', 'Assistant Manager, Distribution', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993092', 'sushil197308@gmail.com', '2015-07-15', 1, '2026-05-24 18:00:00'),
(853, '304901', 'Rajon Chakarborty', 'Distribution Officer', 'Halishahar Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995143', 'rajanchakraborty839@gmail.com', '2013-12-11', 1, '2026-05-24 18:00:00'),
(854, '304902', 'H.M. Zahidul Islam', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989996731', 'zahid.islam@unigroup-bd.com', '2015-08-22', 1, '2026-05-24 18:00:00'),
(855, '304904', 'Shukkur Ali', 'Senior Area Distribution Manager', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996355', 'shukkur.ali@unigroup-bd.com', '2015-11-14', 1, '2026-05-24 18:00:00'),
(856, '304905', 'Mohammad Mahfujul Alam', 'Senior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994875', 'mohammadmahfajulalam@gmail.com', '2016-09-22', 1, '2026-05-24 18:00:00'),
(857, '304908', 'Md. Shohanur Rahman', 'Senior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993273', 'leoshohan@gmail.com', '2016-08-02', 1, '2026-05-24 18:00:00'),
(858, '304914', 'Iqbal Molla', 'Junior Distribution Officer', 'Uttara Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01671070502', 'iqbalmollah304913@gmail.com', '2014-10-21', 1, '2026-05-24 18:00:00'),
(859, '304917', 'Md. Rubel Khan', 'Distribution Officer', 'Barguna Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01938607544', 'rubel.khan01753@gmail.com', '2014-09-13', 1, '2026-05-24 18:00:00'),
(860, '304921', 'Md. Abu Sayed', 'Distribution Officer', 'Habiganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01817016597', 'sayedsk1217@gmail.com', '2014-11-09', 1, '2026-05-24 18:00:00'),
(861, '304922', 'Md. Hafizur Rahman', 'Distribution Officer', 'Hathazari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01950255296', 'hafizurrahman7069@gmail.com', '2014-08-14', 1, '2026-05-24 18:00:00'),
(862, '304923', 'Md. Aslam Hossain Sardar', 'Junior Distribution Officer', 'Gouripur Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01648012001', '', '2014-08-17', 1, '2026-05-24 18:00:00'),
(863, '304926', 'Md. Tajul Islam', 'Senior Distribution Officer', 'Panchagarh Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01744415625', 'ti7169769@gmail.com', '2014-07-01', 1, '2026-05-24 18:00:00'),
(864, '304930', 'Md. Nazrul Islam', 'Junior Distribution Officer', 'Tangail Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01728784850', 'baizidbasar@gmail.com', '2015-05-01', 1, '2026-05-24 18:00:00'),
(865, '304938', 'Md. Saddam Hossain', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01719315664', 'saddamhossain.unigroup@gmail.com', '2014-06-10', 1, '2026-05-24 18:00:00'),
(866, '304949', 'Md. Abdus Salam', 'Junior Distribution Officer', 'Bogura Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01965137764', '', '2015-12-01', 1, '2026-05-24 18:00:00'),
(867, '304951', 'Md. Ahasanul Haque', 'Junior Distribution Officer', 'Joypurhat Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01922969411', '', '2016-01-02', 1, '2026-05-24 18:00:00'),
(868, '304956', 'Md. Shoriful Islam', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01995391804', '', '2015-12-01', 1, '2026-05-24 18:00:00'),
(869, '304958', 'Md. Rasel Hossain', 'Junior Distribution Officer', 'Mirpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01944744718', 'raselbdpop@gmail.com', '2016-11-01', 1, '2026-05-24 18:00:00'),
(870, '304959', 'Md. Ashraful Haque', 'Junior Distribution Officer', 'Mirpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01728917111', 'bakkar3g247@gmail.com', '2016-10-10', 1, '2026-05-24 18:00:00'),
(871, '304960', 'Md. Mukter Islam', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01982372126', 'mdmuktar13428@gmail.com', '2016-02-27', 1, '2026-05-24 18:00:00'),
(872, '304971', 'Md. Milon Hossain', 'Audit Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989996980', 'milon.hossain@unigroup-bd.com', '2015-03-12', 1, '2026-05-24 18:00:00'),
(873, '304973', 'Ikramul Islam', 'Junior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01940902594', '', '2015-10-06', 1, '2026-05-24 18:00:00'),
(874, '304974', 'Md. Taizul Islam', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01600007884', 'taizulm74@gmail.com', '2015-06-09', 1, '2026-05-24 18:00:00'),
(875, '304976', 'Md. Muktar Hosen', 'Junior Distribution Officer', 'Pabna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01774028498', 'muktarhossain@gmail.com', '2015-07-12', 1, '2026-05-24 18:00:00'),
(876, '304982', 'Rasel Majhee', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01747725371', 'md.7831975@gmail.com', '2015-10-02', 1, '2026-05-24 18:00:00'),
(877, '304983', 'Md. Mokarom ', 'Junior Distribution Officer', 'Rampura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01727895565', '', '2016-03-05', 1, '2026-05-24 18:00:00'),
(878, '304987', 'Md. Sohel', 'Junior Distribution Officer', 'Lakshmipur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01837946880', 'shohelranava1254@gmail.com', '2016-11-01', 1, '2026-05-24 18:00:00'),
(879, '304988', 'Mamun Reza', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01729436384', 'mamunreza267@gmail.com', '2016-07-01', 1, '2026-05-24 18:00:00'),
(880, '304992', 'Syed Raisul Islam', 'Junior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01777185069', 'raisulislam562078@gmail.com', '2016-04-12', 1, '2026-05-24 18:00:00'),
(881, '305000', 'Khondker Anisuzzaman', 'Senior Medical Promotion Officer', 'BIRDEM-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993172', 'anisuzzaman.unigroup@gmail.com', '2010-04-10', 1, '2026-05-24 18:00:00'),
(882, '305002', 'Md. Ibrahim Kabir', 'Senior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01940398390', 'ibrahimsarker040@gmail.com', '2017-06-01', 1, '2026-05-24 18:00:00'),
(883, '305004', 'Nripen Chandra Das', 'Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01711867983', 'nripendas12@gmail.com', '2017-08-01', 1, '2026-05-24 18:00:00'),
(884, '305005', 'Md. Asaduzzaman ', 'Area Distribution Manager', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996356', 'ripon.khandoker@gmail.com', '2017-12-13', 1, '2026-05-24 18:00:00'),
(885, '305006', 'Saiduzzaman', 'Area Distribution Manager', 'Kushtia Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993516', 'saiduzzamanrubel171@gmail.com', '2017-10-12', 1, '2026-05-24 18:00:00'),
(886, '305007', 'Mohammad Mamun Khan', 'Senior Medical Promotion Officer', 'Uttara-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993119', 'mdmamunkhanofficialbd@gmail.com', '2010-06-01', 1, '2026-05-24 18:00:00'),
(887, '305008', 'Shaikh Saif Uddin Ahmed', 'Distribution Officer', 'Jhalokati Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01911884995', 'jhalokati.sc@unigroup-bd.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(888, '305016', 'Asifur Rahman', 'Distribution Officer', 'Chuadanga Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994370', 'asifur2018@gmail.com', '2018-09-23', 1, '2026-05-24 18:00:00'),
(889, '305017', 'Md. Monjerul Hasan', 'Senior Medical Promotion Officer', 'Jhenaidah-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993848', 'rifatsifat2026@gmail.com', '2010-06-01', 1, '2026-05-24 18:00:00'),
(890, '305019', 'Simson Hazra', 'Senior Distribution Officer', 'Jhenaidah Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994248', 'sh.shimu21@gmail.com', '2018-09-20', 1, '2026-05-24 18:00:00'),
(891, '305020', 'Komol Halder', 'Distribution Officer', 'Mymensingh Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01718952846', 'lotuskomol846@gmail.com', '2018-10-01', 1, '2026-05-24 18:00:00'),
(892, '305026', 'Md. Asaduzzaman', 'Senior Medical Promotion Officer', 'Manikdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993158', 'asadunigrup123@gmail.com', '2010-06-02', 1, '2026-05-24 18:00:00'),
(893, '305027', 'Md. Bulbul Ahmad', 'Senior Distribution Officer', 'Madaripur Sales Centre', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993513', 'bulbulahmad664@gmail.com', '2018-09-27', 1, '2026-05-24 18:00:00'),
(894, '305028', 'Md. Eianur Rahman', 'Senior Distribution Officer', 'Chapainawabganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995161', 'jdojony@gmail.com', '2018-09-25', 1, '2026-05-24 18:00:00'),
(895, '305029', 'Mohammad Anisur Rahman Molla', 'Senior Medical Promotion Officer', 'Narayanganj-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993669', 'anis.305029@gmail.com', '2010-06-27', 1, '2026-05-24 18:00:00'),
(896, '305031', 'Md. Salahuddin', 'Senior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997076', 'sabbirsalah2016@gmail.com', '2018-10-02', 1, '2026-05-24 18:00:00'),
(897, '305032', 'Md. Nazmul Huda', 'Senior Distribution Officer', 'Rajbari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993512', 'nazmulhudamaster99@gmail.com', '2018-10-04', 1, '2026-05-24 18:00:00'),
(898, '305034', 'Md. Kesmot Ali', 'Senior Area Sales Manager', 'Rajbari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993636', 'kesmot.unigroup@gmail.com', '2007-07-22', 1, '2026-05-24 18:00:00'),
(899, '305035', 'Md. Shajedul Islam', 'Senior Medical Promotion Officer', 'Coxsbazar-VI', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993660', 'shajedulmd26652@gmail.com', '2010-07-27', 1, '2026-05-24 18:00:00'),
(900, '305038', 'Mohammad Ifthekharul Islam Sajib', 'Senior Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01914472202', 'Ifthekharsajib@gmail.com', '2018-10-10', 1, '2026-05-24 18:00:00'),
(901, '305040', 'Habib Miah', 'Senior Distribution Officer', 'Mymensingh Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995139', 'netrokona.sc@unigroup-bd.com', '2018-10-21', 1, '2026-05-24 18:00:00'),
(902, '305041', 'Raihan Uddin', 'Senior Distribution Officer', 'Sitakunda Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995159', 'raihanuddin913@gmail.com', '2018-11-01', 1, '2026-05-24 18:00:00'),
(903, '305044', 'A.Z.M. Mohiuddin Sarkar', 'Senior Area Sales Manager', 'Comilla-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993550', 'azmmohiuddin@gmail.com', '2010-07-18', 1, '2026-05-24 18:00:00'),
(904, '305046', 'Md. Prince Mahmud', 'Senior Distribution Officer', 'Munshiganj sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993514', 'princemahmudc420@gmail.com', '2018-11-11', 1, '2026-05-24 18:00:00'),
(905, '305048', 'Zakir Hossain', 'Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01923164431', 'moonligh202@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(906, '305051', 'Amtiazuddin Ahmed', 'Senior Distribution Officer', 'Uttara Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01872532224', 'amtiazahmed@yahoo.com', '2019-02-19', 1, '2026-05-24 18:00:00'),
(907, '305052', 'Md. Ballal Hossain', 'Senior Distribution Officer', 'Barishal Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993268', 'barisal.sc@unigrouo-bd.com', '2019-03-03', 1, '2026-05-24 18:00:00'),
(908, '305053', 'Md. Atikur Rahman', 'Senior Area Sales Manager', 'Mymensingh-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993527', 'atikurrahman.uniderma@gmail.com', '2010-09-02', 1, '2026-05-24 18:00:00'),
(909, '305055', 'Md. Manjur Hossen', 'Senior Medical Promotion Officer', 'Mitford-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993182', 'manjurhossen1984@gmail.com', '2010-08-25', 1, '2026-05-24 18:00:00'),
(910, '305061', 'Mantu Chandra Roy', 'Senior Area Sales Manager', 'B.Baria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993529', 'mroy.unigroup@gmail.com', '2010-10-11', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(911, '305085', 'Mohammed Kamrul Hasan', 'Senior Medical Promotion Officer', 'BMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993478', 'kfiewndy3424@gmail.com', '2010-11-27', 1, '2026-05-24 18:00:00'),
(912, '305095', 'Md. Jahidul Islam', 'Senior Medical Promotion Officer', 'Joypurhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993598', 'jahidulislam24782@gmail.com', '2011-01-03', 1, '2026-05-24 18:00:00'),
(913, '305100', 'Md. Arif Haydar', 'Area Distribution Manager', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996914', 'arif.haydar0875@gmail.com', '2019-09-05', 1, '2026-05-24 18:00:00'),
(914, '305101', 'Md. Sohag Hossain Sharif', 'Senior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997077', 'sohaghosan736@gmail.com', '2019-10-01', 1, '2026-05-24 18:00:00'),
(915, '305102', 'Mohammad Anjel Mollah', 'Senior Distribution Officer', 'Rajshahi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993481', 'anjelmollahunimed@gmail.com', '2020-03-01', 1, '2026-05-24 18:00:00'),
(916, '305104', 'Md. Fahad Bin Shafiq', 'Senior Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996354', 'fahad.islam055@gmail.com', '2019-11-09', 1, '2026-05-24 18:00:00'),
(917, '305106', 'Md. TaIjul Islam', 'Area Distribution Manager', 'Savar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01673316394', 'taijulhsm24@gmail.com', '2020-09-01', 1, '2026-05-24 18:00:00'),
(918, '305107', 'Md. Golam Kibria', 'Distribution Officer', 'Rampura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01850122241', 'hafezkibria90@gmail.com', '2020-10-11', 1, '2026-05-24 18:00:00'),
(919, '305108', 'Sheikh Shahiduzzaman', 'Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01718248024', 'shalkhkallol@gmail.ocm', '2021-07-13', 1, '2026-05-24 18:00:00'),
(920, '305111', 'Md. Soyeb Hossan', 'Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01918098706', 'soyebhossain8706@gmail.com', '2021-07-11', 1, '2026-05-24 18:00:00'),
(921, '305112', 'Md. Ariful Islam', 'Accounts Officer, Distribution', 'Central Depot, Cash', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997072', 'arifulislamovi0088@gmail.com', '2021-08-30', 1, '2026-05-24 18:00:00'),
(922, '305114', 'Akram', 'Distribution Officer', 'Central Depot, Bosila, Mohammadpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989996850', 'akram1.unigroup@gmail.com', '2021-09-19', 1, '2026-05-24 18:00:00'),
(923, '305115', 'Md. Aminul Islam', 'Junior Distribution Officer', 'Pabna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01677124922', 'aminul.hridoy777@gmail.com', '2023-01-07', 1, '2026-05-24 18:00:00'),
(924, '305116', 'Md. Ibrahim Hossain', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01781397793', 'ibrahimhossain68@gmail.com', '2023-01-17', 1, '2026-05-24 18:00:00'),
(925, '305117', 'Minhaz Bin Amin', 'Senior Distribution Officer', 'Narayanganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01685669035', 'minhazbinamin@gmail.com', '2023-01-15', 1, '2026-05-24 18:00:00'),
(926, '305118', 'Md. Mainul Hasan', 'Junior Distribution Officer (Cash)', 'Cumilla Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01753978798', 'mainul978798@gmail.com', '2023-01-14', 1, '2026-05-24 18:00:00'),
(927, '305120', 'Md. Salim Jahangir', 'Junior Distribution Officer', 'Patuakhali Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994890', 'mdsalimjahangir0000@gmail.com', '2023-09-10', 1, '2026-05-24 18:00:00'),
(928, '305123', 'Golam Maola', 'Distribution Officer', 'Mirpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994345', 'golammaola37@gmail.com', '2023-10-08', 1, '2026-05-24 18:00:00'),
(929, '305124', 'Md. Habibur Rahman', 'Junior Distribution Officer', 'Chandpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01773995528', 'habibmondol528@gmail.com', '2023-10-22', 1, '2026-05-24 18:00:00'),
(930, '305125', 'Md. Rakibul Hasan', 'Junior Distribution Officer', 'Kurigram Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01755432955', '', '2023-11-02', 1, '2026-05-24 18:00:00'),
(931, '305302', 'Md. Nazim Uddin', 'Senior Area Sales Manager', 'Barisal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993776', 'nazimbabu2010@gmail.com', '2022-09-04', 1, '2026-05-24 18:00:00'),
(932, '305402', 'Md. Saidur Rahman', 'Senior Medical Promotion Officer', 'Bhola-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994138', 'saidurrahman76888@gmail.com', '2022-08-29', 1, '2026-05-24 18:00:00'),
(933, '305404', 'Karuna Kanta Talukdar', 'Senior Medical Promotion Officer', 'Charfesson-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996940', 'redoytalukdar@gmail.com', '2022-08-30', 1, '2026-05-24 18:00:00'),
(934, '305405', 'Md. Hazrat Ali', 'Senior Medical Promotion Officer', 'B.Baria-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993930', 'sagorhozratrobe@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(935, '305406', 'Md. Shakhil Mondol', 'Senior Medical Promotion Officer', 'Hathazari-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995201', 'shakilahamed01742866456@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(936, '305408', 'Sayed Ahmad Khan', 'Medical Promotion Officer', 'Barguna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996307', 'sayedahmedkhan7@gmail.com', '2022-08-29', 1, '2026-05-24 18:00:00'),
(937, '305413', 'Md. Al Amin', 'Senior Medical Promotion Officer', 'DMCH-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994589', 'md.alamin.info@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(938, '305415', 'Md. Saikat Hasan', 'Senior Medical Promotion Officer', 'Tongi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994478', 'saikat.hasan2014@gmail.com', '2022-09-03', 1, '2026-05-24 18:00:00'),
(939, '305416', 'Md. Kabir Hossain', 'Senior Medical Promotion Officer', 'BSMMU-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994375', 'kabir192939@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(940, '305418', 'Md. Jahir Uddin', 'Senior Medical Promotion Officer', 'WMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994047', 'jahirsalma12@gmail.com', '2022-09-01', 1, '2026-05-24 18:00:00'),
(941, '305421', 'Pankaj Sushil', 'Senior Medical Promotion Officer', 'Coxsbazar-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993617', 'pankajshil2010@gmail.com', '2022-09-05', 1, '2026-05-24 18:00:00'),
(942, '305422', 'Md. Shahin Hossain', 'Senior Medical Promotion Officer', 'Kabirhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993530', 'hmshahin314@gmail.com', '2022-09-03', 1, '2026-05-24 18:00:00'),
(943, '305426', 'Md. Bablu Miah', 'Medical Promotion Officer', 'Birganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995303', 'mabablu42@gmail.com', '2022-09-05', 1, '2026-05-24 18:00:00'),
(944, '305427', 'Riten Karmoker', 'Medical Promotion Officer', 'Kansat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995317', 'ritenkarmoker345@gmail.com', '2022-09-05', 1, '2026-05-24 18:00:00'),
(945, '305428', 'Md. Kamruzzaman', 'Medical Promotion Officer', 'Dimla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996783', 'priozamanmugdho@gmail.com', '2022-09-05', 1, '2026-05-24 18:00:00'),
(946, '305429', 'Rintu Kirttania', 'Senior Medical Promotion Officer', 'Tekerhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994310', 'rintukirttania6@gmail.com', '2022-09-05', 1, '2026-05-24 18:00:00'),
(947, '305431', 'Md. Musabbirul Haque', 'Senior Medical Promotion Officer', 'Dhanmondi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993395', 'mddin1062@gmail.com', '2022-09-15', 1, '2026-05-24 18:00:00'),
(948, '305433', 'Md. Rezaul Karim', 'Senior Medical Promotion Officer', 'Savar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994123', 'rezaul9521@gmail.com', '2022-09-24', 1, '2026-05-24 18:00:00'),
(949, '305434', 'Md. Rabiul Islam', 'Senior Medical Promotion Officer', 'Savar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996790', 'irabiul175@gmail.com', '2022-09-24', 1, '2026-05-24 18:00:00'),
(950, '305439', 'Md. Kamrul Islam', 'Medical Promotion Officer', 'Uttara-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996978', 'kamrul245725@gmail.com', '2022-09-26', 1, '2026-05-24 18:00:00'),
(951, '305441', 'Md. Sumon Mia', 'Senior Medical Promotion Officer', 'Dhaka Immuno Psychiatry', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994384', 'mdsumonahmed1992@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(952, '305442', 'Liton Sarkar', 'Senior Medical Promotion Officer', 'Khulna-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996862', 'liton77713@gmail.com', '2022-09-27', 1, '2026-05-24 18:00:00'),
(953, '305444', 'Md. Anisuzzaman', 'Senior Medical Promotion Officer', 'Khalispur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993900', 'mdanisuzzaman01971@gmail.com', '2022-09-28', 1, '2026-05-24 18:00:00'),
(954, '305445', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Keshabpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993542', 'armanmdsohel80@gmail.com', '2022-09-29', 1, '2026-05-24 18:00:00'),
(955, '305446', 'Md. Ripon Mia', 'Senior Medical Promotion Officer', 'Brahmanbaria-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993778', 'rmia86384@gmail.com', '2022-09-29', 1, '2026-05-24 18:00:00'),
(956, '305447', 'Ramani Kanta Roy', 'Senior Medical Promotion Officer', 'Jaintiapur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993572', 'ramonikanto89@gmail.com', '2022-10-08', 1, '2026-05-24 18:00:00'),
(957, '305451', 'Md. Samrat Hossain', 'Senior Medical Promotion Officer', 'Mount Adora MT-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996316', 'mdsamratahmed4424@gmail.com', '2022-09-29', 1, '2026-05-24 18:00:00'),
(958, '305453', 'Md. Mosharrof Hussain', 'Senior Medical Promotion Officer', 'Mymensingh-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993240', 'mosharrof291295@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(959, '305454', 'Robiul Ahmmed', 'Senior Medical Promotion Officer', 'Boalkhali-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995432', 'robiulahmmed7008@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(960, '305455', 'Md. Atiqul Islam', 'Senior Medical Promotion Officer', 'Keranirhat-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993849', '180mdatiqulislam@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(961, '305456', 'Md. Milon Ali', 'Senior Medical Promotion Officer', 'Chowmuhani-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994277', 'milonalidgc@gmail.com', '2022-09-28', 1, '2026-05-24 18:00:00'),
(962, '305457', 'Md. Sarwer Hossain', 'Medical Promotion Officer', 'Raipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994867', 'sarwerhossain0@gmail.com', '2022-09-28', 1, '2026-05-24 18:00:00'),
(963, '305460', 'Dalim Kumar Ray', 'Senior Medical Promotion Officer', 'Feni-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993594', 'dalimkumerroy1993@gmail.com', '2022-10-06', 1, '2026-05-24 18:00:00'),
(964, '305461', 'Diganta Adhikary', 'Senior Medical Promotion Officer', 'EPZ+KPZ', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995318', 'digantaadhikary2014@gmail.com', '2022-10-02', 1, '2026-05-24 18:00:00'),
(965, '305463', 'Oshim Kumar Pal', 'Senior Medical Promotion Officer', 'Mohakhali-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993438', 'oshimpal@gmail.com', '2022-10-02', 1, '2026-05-24 18:00:00'),
(966, '305464', 'Md. Arif Hossain Rijon', 'Senior Medical Promotion Officer', 'Siddheswari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993122', 'arifhossainrijon97@gmail.com', '2022-10-15', 1, '2026-05-24 18:00:00'),
(967, '305468', 'Md. Mamun Mia', 'Senior Medical Promotion Officer', 'Mugda-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993207', 'mamun.mia.3m@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(968, '305469', 'Md. Meherul Islam', 'Senior Medical Promotion Officer', 'Sylhet-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993860', 'meherul.live@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(969, '305474', 'Md. Mosharaf Hossain', 'Senior Medical Promotion Officer', 'Mirpur-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994448', 'mosharafsm95@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(970, '305475', 'Md. Mahmudul Hassan', 'Senior Medical Promotion Officer', 'Faridpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994504', 'mahmudul4october@gmail.com', '2022-10-01', 1, '2026-05-24 18:00:00'),
(971, '305478', 'Md. Jahid Khan', 'Medical Promotion Officer', 'Moulvibazar-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993861', 'm69897382@gmail.com', '2022-10-03', 1, '2026-05-24 18:00:00'),
(972, '305479', 'Meher Chandra Bhowmik', 'Medical Promotion Officer', 'Bhairab-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996714', 'meherbhowmik607@gmail.com', '2022-10-03', 1, '2026-05-24 18:00:00'),
(973, '305482', 'Mithun Chandra Mallick', 'Senior Medical Promotion Officer', 'Feni-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996347', 'mithunmallick0914@gmail.com', '2022-10-06', 1, '2026-05-24 18:00:00'),
(974, '305483', 'Md. Rubel Husain', 'Senior Medical Promotion Officer', 'Ramchandrapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993215', 'rubelhusain300@gmail.com', '2022-10-04', 1, '2026-05-24 18:00:00'),
(975, '305486', 'Md. Shahin Hosen', 'Senior Medical Promotion Officer', 'Dinajpur-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993691', 'hosensahin956@gmail.com', '2022-10-03', 1, '2026-05-24 18:00:00'),
(976, '305487', 'Md. Nazrul Islam', 'Medical Promotion Officer', 'Dinajpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995640', 'nazrul201988@gmail.com', '2022-10-04', 1, '2026-05-24 18:00:00'),
(977, '305488', 'Milon Datta', 'Medical Promotion Officer', 'Morrelganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995348', 'kumarmilon24@gmail.com', '2022-10-06', 1, '2026-05-24 18:00:00'),
(978, '305489', 'Md. Moktarul Alom', 'Senior Medical Promotion Officer', 'BSMMU-A7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993102', 'mimokter@gmail.com', '2022-10-15', 1, '2026-05-24 18:00:00'),
(979, '305491', 'Shapon Miah', 'Medical Promotion Officer', 'Bashundhara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996715', 'shaponmiah606@gmail.com', '2022-10-16', 1, '2026-05-24 18:00:00'),
(980, '305493', 'Pusporanjon Ray', 'Medical Promotion Officer', 'Bogra-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995186', 'pusporay88@gmail.com', '2022-10-22', 1, '2026-05-24 18:00:00'),
(981, '305495', 'Md. Mehedi Hasan', 'Medical Promotion Officer', 'Gangachara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995413', 'mehedihasan475869@gmail.com', '2022-10-20', 1, '2026-05-24 18:00:00'),
(982, '305497', 'Md. Aminul Islam', 'Medical Promotion Officer', 'Rajshahi-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996388', 'amirul375037@gmail.com', '2022-10-20', 1, '2026-05-24 18:00:00'),
(983, '305503', 'Md. Shohel Shekh', 'Medical Promotion Officer', 'Mohonpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996390', 'sshekh4267@gmail.com', '2022-11-06', 1, '2026-05-24 18:00:00'),
(984, '305509', 'Ferdausur Rahman', 'Senior Medical Promotion Officer', 'Mirzaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995534', 'frsohel988@gmail.com', '2022-11-01', 1, '2026-05-24 18:00:00'),
(985, '305510', 'Md. Selim Hossain', 'Medical Promotion Officer', 'ShSMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994508', 'hossainmdselim56@gmail.com', '2022-11-01', 1, '2026-05-24 18:00:00'),
(986, '305517', 'Mahmudul Hasan', 'Senior Medical Promotion Officer', 'BMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994743', 'mh395889@gmail.com', '2022-11-26', 1, '2026-05-24 18:00:00'),
(987, '305519', 'Md. Raysul Islam', 'Senior Medical Promotion Officer', 'Jessore-C5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997084', 'raysul.kst017@gmail.com', '2022-11-12', 1, '2026-05-24 18:00:00'),
(988, '305520', 'Tarique Hosain', 'Senior Medical Promotion Officer', 'Chhagalnaiya-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995397', 'tariquehossain1994@gamil.com', '2022-11-17', 1, '2026-05-24 18:00:00'),
(989, '305521', 'Md. Monirul Islam', 'Medical Promotion Officer', 'Chhagalnaiya-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996826', 'monirulislam3220@gmail.com', '2022-11-19', 1, '2026-05-24 18:00:00'),
(990, '305522', 'Md. Israfil Alam', 'Senior Medical Promotion Officer', 'Monohargonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995195', 'Israfil.mathcakla@gmail.com', '2022-11-17', 1, '2026-05-24 18:00:00'),
(991, '305524', 'Mukta Kumar Sutradhar', 'Medical Promotion Officer', 'Rajshahi-13', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996389', 'mkroy.net@gmail.com', '2022-11-13', 1, '2026-05-24 18:00:00'),
(992, '305525', 'Md. Arman Ali', 'Medical Promotion Officer', 'Rajshahi-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993736', 'armanahmed4442@gmail.com', '2022-11-17', 1, '2026-05-24 18:00:00'),
(993, '305526', 'Md. Atiqur Rahman', 'Senior Medical Promotion Officer', 'Eidgah-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994993', 'atiq338011@gmail.com', '2022-11-15', 1, '2026-05-24 18:00:00'),
(994, '305527', 'Md. Salim Uddin', 'Senior Medical Promotion Officer', 'Gulshan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994226', 'salimgp901@gmail.com', '2022-12-01', 1, '2026-05-24 18:00:00'),
(995, '305536', 'Kazal Kumar Paul', 'Medical Promotion Officer', 'Puthia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993707', 'kkpaulak47@gmail.com', '2022-12-12', 1, '2026-05-24 18:00:00'),
(996, '305537', 'Md. Shalal Hasan', 'Senior Medical Promotion Officer', 'Sirajganj-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993367', 'shanasmo187@gmail.com', '2022-12-12', 1, '2026-05-24 18:00:00'),
(997, '305538', 'Md. Arshad Hossain', 'Medical Promotion Officer', 'Rangpur-17', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995410', 'arshadhossa33@gmail.com', '2022-12-12', 1, '2026-05-24 18:00:00'),
(998, '305539', 'Md. Monirul Islam', 'Medical Promotion Officer', 'Rajshahi-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997079', 'monirulislampbn@gmail.com', '2022-12-12', 1, '2026-05-24 18:00:00'),
(999, '305541', 'Md. Al Amin Hossain', 'Medical Promotion Officer', 'Gobindaganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995615', 'alaminalamin11229@gmail.com', '2022-12-14', 1, '2026-05-24 18:00:00'),
(1000, '305542', 'Nilkomal', 'Medical Promotion Officer', 'Santhia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994791', 'nilkomal520@gmail.com', '2022-12-14', 1, '2026-05-24 18:00:00'),
(1001, '305543', 'Md. Razikul Islam', 'Medical Promotion Officer', 'Chhagalnaiya-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994555', 'razikulrasel@gmail.com', '2022-12-10', 1, '2026-05-24 18:00:00'),
(1002, '305545', 'Md. Rakib Hossain', 'Senior Medical Promotion Officer', 'Homna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997009', 'rakib09812@gmail.com', '2022-12-14', 1, '2026-05-24 18:00:00'),
(1003, '305546', 'Md. Rashel Mia', 'Senior Medical Promotion Officer', 'Chauddagram-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993796', 'renatarashel@gmail.com', '2022-12-13', 1, '2026-05-24 18:00:00'),
(1004, '305547', 'S.M. Atikur Rahaman', 'Senior Medical Promotion Officer', 'Dhaka Dakshin-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993777', 'syeedsohag1@gmail.com', '2022-12-24', 1, '2026-05-24 18:00:00'),
(1005, '305549', 'Md. Mahamudul Hasan', 'Senior Medical Promotion Officer', 'Senbug-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995176', 'mahamudulhasan9026@gmail.com', '2022-12-26', 1, '2026-05-24 18:00:00'),
(1006, '305550', 'Raton Hossain', 'Senior Medical Promotion Officer', 'Pekua-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995169', 'ratonh@gmail.com', '2022-12-26', 1, '2026-05-24 18:00:00'),
(1007, '305554', 'Suresh Chandra Ray', 'Senior Medical Promotion Officer', 'Sherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997093', 'schandraray123@gamil.com', '2022-12-22', 1, '2026-05-24 18:00:00'),
(1008, '305556', 'Md. Ershad Hossain', 'Senior Medical Promotion Officer', 'Savar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993941', 'ershadhossain059@gmail.com', '2023-01-18', 1, '2026-05-24 18:00:00'),
(1009, '305557', 'Md. Golam Rabbani', 'Senior Medical Promotion Officer', 'Kaunia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996943', 'golamrabbani20041992@gmail.com', '2023-01-14', 1, '2026-05-24 18:00:00'),
(1010, '305558', 'Md. Masiur Rahaman', 'Senior Medical Promotion Officer', 'Mymensingh-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995322', 'mdmosiurr14@gmail.com', '2023-01-12', 1, '2026-05-24 18:00:00'),
(1011, '305559', 'Saiful Islam', 'Medical Promotion Officer', 'Narayanganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996816', 'ulab.islam@gmail.com', '2023-01-18', 1, '2026-05-24 18:00:00'),
(1012, '305561', 'Md. Iqbal Hossen', 'Senior Medical Promotion Officer', 'Senbug-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994309', 'hosseniqbal894@gmail.com', '2023-01-24', 1, '2026-05-24 18:00:00'),
(1013, '305563', 'Tanmoy Gain', 'Medical Promotion Officer', 'Bamna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994609', 'tonmoygain@gmail.com', '2023-01-14', 1, '2026-05-24 18:00:00'),
(1014, '305568', 'Md. Aktarul Islam', 'Senior Medical Promotion Officer', 'Uttara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995446', 'aktarulshahin2@gmail.com', '2023-02-01', 1, '2026-05-24 18:00:00'),
(1015, '305569', 'Gour Chandra Sarker', 'Senior Medical Promotion Officer', 'Dinajpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997011', 'goursarker39@gmail.com', '2023-01-30', 1, '2026-05-24 18:00:00'),
(1016, '305571', 'Reja Elizabed', 'Medical Promotion Officer', 'Golapganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997018', 'rejaunimed@gmail.com', '2023-01-30', 1, '2026-05-24 18:00:00'),
(1017, '305573', 'Md. Jahangir Alam', 'Medical Promotion Officer', 'Lakshmipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997008', 'jahangiralam061@gmail.com', '2023-01-30', 1, '2026-05-24 18:00:00'),
(1018, '305580', 'Md. Mostofa Kamal', 'Senior Medical Promotion Officer', 'Faridpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997022', 'mostofamona@gmail.com', '2023-02-18', 1, '2026-05-24 18:00:00'),
(1019, '305581', 'Md. Ashraful Islam', 'Medical Promotion Officer', 'Chakaria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997027', 'babul992islam@gmail.com', '2023-02-18', 1, '2026-05-24 18:00:00'),
(1020, '305585', 'Md. Janarul Islam', 'Senior Medical Promotion Officer', 'Naogaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997021', 'jenarul660@gmail.com', '2023-02-18', 1, '2026-05-24 18:00:00'),
(1021, '305590', 'Aseak-E-Alahi', 'Medical Promotion Officer', 'Comilla-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997024', 'aseakraju154@gmail.com', '2023-02-18', 1, '2026-05-24 18:00:00'),
(1022, '305597', 'Md. Mijanur Rahman', 'Senior Medical Promotion Officer', 'Bhola-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995553', 'mdshimulmijan1188@gmail.com', '2023-02-22', 1, '2026-05-24 18:00:00'),
(1023, '305598', 'Md. Arman Ahmed Akhon', 'Medical Promotion Officer', 'Mitford/Jatrabari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995364', 'armanccro3@gmail.com', '2023-02-22', 1, '2026-05-24 18:00:00'),
(1024, '305603', 'Sabbir Hosen', 'Medical Promotion Officer', 'Comilla-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997034', 'hosensabbir1998@gmail.com', '2023-03-05', 1, '2026-05-24 18:00:00'),
(1025, '305604', 'Methun Bakchi', 'Medical Promotion Officer', 'BIRDEM-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994712', 'methunbakchi888@gmail.com', '2023-03-05', 1, '2026-05-24 18:00:00'),
(1026, '305605', 'Md. Rasel Hossen', 'Senior Medical Promotion Officer', 'Sylhet-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997032', 'rasel.hrt@gmail.com', '2023-03-04', 1, '2026-05-24 18:00:00'),
(1027, '305606', 'Md. Mesbahul Islam', 'Senior Medical Promotion Officer', 'Syedpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996361', 'mesbahuli853@gmail.com', '2023-03-04', 1, '2026-05-24 18:00:00'),
(1028, '305608', 'Md. Iqbal Hossain', 'Senior Medical Promotion Officer', 'Chuadanga', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997033', 'iqbalmahmudniloy4@gmail.com', '2023-03-04', 1, '2026-05-24 18:00:00'),
(1029, '305609', 'Sadananda Mondal', 'Senior Medical Promotion Officer', 'Jessore-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997101', 'sadanandamondal8677@gmail.com', '2023-03-04', 1, '2026-05-24 18:00:00'),
(1030, '305611', 'Md. Hasan Ali Talukder', 'Medical Promotion Officer', 'Mirpur-C6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994515', 'htalukder3232@gmail.com', '2023-03-04', 1, '2026-05-24 18:00:00'),
(1031, '305612', 'Monirul Islam', 'Medical Promotion Officer', 'Khulna-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997085', 'monirulislam01988040223@gmail.com', '2023-03-05', 1, '2026-05-24 18:00:00'),
(1032, '305614', 'Shapan Kumar', 'Senior Medical Promotion Officer', 'Bogra-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993117', 'shapankumar.phulbari@gmail.com', '2023-03-02', 1, '2026-05-24 18:00:00'),
(1033, '305615', 'Md. Robel Miah', 'Senior Medical Promotion Officer', 'Maijdee-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996823', 'robel2788@gmail.com', '2023-03-27', 1, '2026-05-24 18:00:00'),
(1034, '305618', 'Md. Alinur Rahman', 'Senior Medical Promotion Officer', 'Panchagarh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997083', 'alinurrahman4567@gmail.com', '2023-05-30', 1, '2026-05-24 18:00:00'),
(1035, '305619', 'Md. Zulhas Ali', 'Senior Medical Promotion Officer', 'Mymensingh-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997038', 'mdzulhayali3@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1036, '305624', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Jhenaidah-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997039', 'aminulislam7909@gmail.com', '2023-03-20', 1, '2026-05-24 18:00:00'),
(1037, '305626', 'Atin Kumar Saha', 'Senior Medical Promotion Officer', 'Fakirhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996788', 'atinksaha@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1038, '305628', 'Md. Zahidul Islam', 'Medical Promotion Officer', 'Bogra-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996399', 'jahid6760@gmail.com', '2023-03-20', 1, '2026-05-24 18:00:00'),
(1039, '305629', 'Md. Najmul Hossan', 'Medical Promotion Officer', 'Pabna-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996336', 'nazmulnpl2020@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1040, '305632', 'Md. Imamul Hossen', 'Senior Medical Promotion Officer', 'Chuadanga-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997036', 'emamulema.mul192@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1041, '305633', 'Taufiqul Islam', 'Senior Medical Promotion Officer', 'Dohar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996385', 'taufiqulislam01728435454@gmail.com', '2023-03-24', 1, '2026-05-24 18:00:00'),
(1042, '305636', 'Md. Mehedi Hasan', 'Medical Promotion Officer', 'Ishurdi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993430', 'mehedisuraya1@gmail.com', '2023-03-25', 1, '2026-05-24 18:00:00'),
(1043, '305639', 'Md. Ali Hossain', 'Senior Medical Promotion Officer', 'Sarishabari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994833', 'alihossainkhan612@gmail.com', '2023-03-25', 1, '2026-05-24 18:00:00'),
(1044, '305641', 'Rakib Ahasan', 'Senior Medical Promotion Officer', 'Sylhet-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993472', 'ahasanrakib2@gamil.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1045, '305642', 'Sanjoy Bairagi', 'Senior Medical Promotion Officer', 'Barisal-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996757', 'sanjoybairagi4@gmail.com', '2023-03-18', 1, '2026-05-24 18:00:00'),
(1046, '305643', 'Milon Halder', 'Senior Medical Promotion Officer', 'Faridpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997037', 'milonhalder3769@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1047, '305644', 'Shagor Hossain', 'Senior Medical Promotion Officer', 'Barisal-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994304', 'sagorhossain2190@gmail.com', '2023-03-19', 1, '2026-05-24 18:00:00'),
(1048, '305647', 'Toyan Saha', 'Senior Medical Promotion Officer', 'Nandail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995326', 'toyan.saha1710@gmail.com', '2023-03-13', 1, '2026-05-24 18:00:00'),
(1049, '305648', 'Md. Ziaur Rahman', 'Senior Area Sales Manager', 'Faridpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997054', 'ziakbdk@gmail.com', '2023-04-10', 1, '2026-05-24 18:00:00'),
(1050, '305654', 'Nipu Chandra Debnath', 'Area Sales Manager', 'Shariatpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997010', 'nipunath7803@gmail.com', '2023-05-01', 1, '2026-05-24 18:00:00'),
(1051, '305657', 'Md. Rakib Hossain', 'Senior Medical Promotion Officer', 'Uttara-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994153', 'rakibmed69@gmail.com', '2023-03-25', 1, '2026-05-24 18:00:00'),
(1052, '305658', 'Hasibul Islam', 'Senior Medical Promotion Officer', 'Manirampur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997043', 'htuhin1301@gmail.com', '2023-04-01', 1, '2026-05-24 18:00:00'),
(1053, '305661', 'Manash Debnath', 'Medical Promotion Officer', 'Comilla-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997047', 'manashdebnathtonal@gmail.com', '2023-04-08', 1, '2026-05-24 18:00:00'),
(1054, '305668', 'Md. Sohel Hossain', 'Senior Medical Promotion Officer', 'Kashinathpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995289', 'sohelhossain3830bd@gmail.com', '2023-04-08', 1, '2026-05-24 18:00:00'),
(1055, '305670', 'Md. Shahjalal', 'Senior Medical Promotion Officer', 'Gaibandha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997050', 'smd47086@gmail.com', '2023-04-13', 1, '2026-05-24 18:00:00'),
(1056, '305672', 'Subash Ali', 'Medical Promotion Officer', 'Rangpur-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997081', 'hasanj966@gmail.com', '2023-04-13', 1, '2026-05-24 18:00:00'),
(1057, '305675', 'Md. Mostafizur Rahman', 'Medical Promotion Officer', 'Daudkandi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997049', 'mdmostafiz9503@gmail.com', '2023-04-14', 1, '2026-05-24 18:00:00'),
(1058, '305678', 'Kazi Ataur Rahman', 'Senior Area Sales Manager', 'Comilla-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997052', 'nazimuddinre@gmail.com', '2023-04-10', 1, '2026-05-24 18:00:00'),
(1059, '305681', 'Jakir Khan', 'Area Sales Manager', 'SSMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994970', 'jakirkhan385@gmail.com', '2023-04-12', 1, '2026-05-24 18:00:00'),
(1060, '305682', 'Shariful Islam', 'Area Sales Manager', 'SHSMCH-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993198', 'sharifulislam06091981@gmail.com', '2023-04-27', 1, '2026-05-24 18:00:00'),
(1061, '305683', 'Juel Ahmed', 'Senior Medical Promotion Officer', 'DMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994023', 'juel8404@gmail.com', '2023-04-17', 1, '2026-05-24 18:00:00'),
(1062, '305684', 'Md. Abdul Kader', 'Medical Promotion Officer', 'Comilla-B6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996858', 'pavelhasan2999@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1063, '305686', 'Md. Shohag Mia', 'Medical Promotion Officer', 'Feni-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997095', 'md.shohag132260@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1064, '305687', 'Md. Shibgatullah', 'Medical Promotion Officer', 'Comilla-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993097', 'mdk587532@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1065, '305688', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Bogra-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996359', 'mdaminulislam19890@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1066, '305689', 'Al Amin', 'Medical Promotion Officer', 'Lakshmipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997094', 'alaminkhankha1111@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1067, '305692', 'Anisur Rahman', 'Senior Medical Promotion Officer', 'Pabna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997057', 'anisurr1988@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1068, '305694', 'Masud Rana', 'Medical Promotion Officer', 'Titas', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996741', 'masudmohon04@gmail.com', '2023-04-25', 1, '2026-05-24 18:00:00'),
(1069, '305697', 'Abdur-Razzak', 'Medical Promotion Officer', 'Comilla-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994792', 'abdurrazzak1802@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1070, '305700', 'Md. Mehedi Hasan', 'Senior Medical Promotion Officer', 'Lalmohan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993601', 'mhasanraj46@gmail.com', '2023-04-24', 1, '2026-05-24 18:00:00'),
(1071, '305702', 'Pizush Kanti Mazumder', 'Senior Medical Promotion Officer', 'Charfesson', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996301', 'itpizush@gmail.com', '2023-04-18', 1, '2026-05-24 18:00:00'),
(1072, '305703', 'Md. Robiul Islam', 'Area Sales Manager', 'Bagerhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993902', 'robiulskf@gmail.com', '2023-04-27', 1, '2026-05-24 18:00:00'),
(1073, '305704', 'Md. Imam Hossain', 'Senior Area Sales Manager', 'Barisal-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997088', 'babluahmed41981@gmail.com', '2023-04-27', 1, '2026-05-24 18:00:00'),
(1074, '305705', 'Md. Rubel Sarker', 'Area Sales Manager', 'Ishurdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994379', 'rubel.silon@gmail.com', '2023-04-26', 1, '2026-05-24 18:00:00'),
(1075, '305706', 'Kamol Chandra Mudi', 'Senior Area Sales Manager', 'Jaintiapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997060', 'komolmudi1981@gmail.com', '2023-04-27', 1, '2026-05-24 18:00:00'),
(1076, '305709', 'Kartick Chandra Roy', 'Senior Medical Promotion Officer', 'Bogra-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994984', 'kartickroy12342@gmail.com', '2023-05-06', 1, '2026-05-24 18:00:00'),
(1077, '305713', 'Tazul Islam Md. Fazle Rabbi', 'Senior Medical Promotion Officer', 'NIKDU-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993216', 'fazle.satota@gmail.com', '2023-05-14', 1, '2026-05-24 18:00:00'),
(1078, '305718', 'Rafiul Karim', 'Medical Promotion Officer', 'Bogra-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994765', 'rkbrinku@gmail.com', '2023-05-15', 1, '2026-05-24 18:00:00'),
(1079, '305719', 'Md. Mahbul Alom', 'Area Sales Manager', 'BMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993654', 'amahbub229@gmail.com', '2023-05-21', 1, '2026-05-24 18:00:00'),
(1080, '305722', 'Md. Billal Hossain', 'Senior Medical Promotion Officer', 'Poradaha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995619', 'bh19879@gmail.com', '2023-05-28', 1, '2026-05-24 18:00:00'),
(1081, '305723', 'Md. Nahidur Rahman', 'Senior Medical Promotion Officer', 'Ishurdi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997098', 'nahidurrahman303@gmail.com', '2023-05-28', 1, '2026-05-24 18:00:00'),
(1082, '305725', 'Golam Sobhan Sohag', 'Medical Promotion Officer', 'Akkelpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996785', 'sobhansohag@gmail.com', '2023-05-28', 1, '2026-05-24 18:00:00'),
(1083, '305726', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Magura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997117', 'mdarifuli288@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1084, '305727', 'Md. Shaheen Howlader', 'Senior Medical Promotion Officer', 'Barisal-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997107', 'shaheen00391@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1085, '305731', 'Md. Zakir Hossain', 'Senior Medical Promotion Officer', 'Khulna-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997115', '783zakir@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1086, '305732', 'Md. Nazmul Hossain Molla', 'Senior Medical Promotion Officer', 'Khulna-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997116', 'nazmul.naz09@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1087, '305734', 'Md. Hamidul Islam', 'Medical Promotion Officer', 'BSMMU-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994794', 'hamidulislam14041995@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1088, '305736', 'Juwel Miah', 'Senior Medical Promotion Officer', 'BSMMU-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997108', 'juwel.miah3780@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(1089, '305737', 'Md. Tara Mia', 'Medical Promotion Officer', 'Rangpur-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997120', 'golamrafi344@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(1090, '305738', 'Md. Azizul Hoque', 'Medical Promotion Officer', 'Mymensingh-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997114', 'haqueazizul270@gmail.com', '2023-06-03', 1, '2026-05-24 18:00:00'),
(1091, '305740', 'Lovelu Mia Ansary', 'Medical Promotion Officer', 'Mugda-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997119', 'ansaryansary728@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(1092, '305741', 'Md. Raihanul Islam', 'Medical Promotion Officer', 'Uttara-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997113', 'raharaihan40@gmail.com', '2023-06-01', 1, '2026-05-24 18:00:00'),
(1093, '305744', 'Md. Babul Miah', 'Medical Promotion Officer', 'BSMMU-B7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997109', 'mdbabulmiah337@gmail.com', '2023-06-10', 1, '2026-05-24 18:00:00'),
(1094, '305746', 'Md. Aminur Islam Sumon', 'Medical Promotion Officer', 'Rangpur-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997130', 'aminurislamsumon442@gmail.com', '2023-07-10', 1, '2026-05-24 18:00:00'),
(1095, '305747', 'Ajit Kumar Roy', 'Medical Promotion Officer', 'Maijdee-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997121', 'ajit64819@gmail.com', '2023-06-10', 1, '2026-05-24 18:00:00'),
(1096, '305749', 'Md. Abdul Motaleb Mia', 'Medical Promotion Officer', 'Maijdee-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997004', 'marufpramanik92@gmail.com', '2023-06-10', 1, '2026-05-24 18:00:00'),
(1097, '305751', 'Md. Istiak Mahmud', 'Senior Medical Promotion Officer', 'Kamolganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997123', 'istiakarif2007@gmail.com', '2023-06-13', 1, '2026-05-24 18:00:00'),
(1098, '305752', 'Md. Habibur Rahman', 'Senior Medical Promotion Officer', 'Charghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994726', 'mhrhabib89@gmail.com', '2023-06-14', 1, '2026-05-24 18:00:00'),
(1099, '305753', 'Rubel Haque', 'Medical Promotion Officer', 'Parbatipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995501', 'haquerubel683@gmail.com', '2023-06-14', 1, '2026-05-24 18:00:00'),
(1100, '305754', 'Abdullah Faruk Parvez', 'Senior Medical Promotion Officer', 'Laksam-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997124', 'farukparbez10@gmail.com', '2023-06-14', 1, '2026-05-24 18:00:00'),
(1101, '305756', 'Md. Mahmudul Hasan ', 'Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994646', 'rajputro3267@gmail.com', '2023-06-15', 1, '2026-05-24 18:00:00'),
(1102, '305757', 'Md. Abdul Azad', 'Senior Medical Promotion Officer', 'Bandartila-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995226', 'abdulazad01923@gmail.com', '2023-06-14', 1, '2026-05-24 18:00:00'),
(1103, '305758', 'Md Nurul Islam', 'Medical Promotion Officer', 'Naogaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997125', 'nuralamislam3779@gmail.com', '2023-06-14', 1, '2026-05-24 18:00:00'),
(1104, '305760', 'Md. Zillur Rahaman', 'Senior Medical Promotion Officer', 'Sirajganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997137', 'mahmudashath170@gmail.com', '2023-06-18', 1, '2026-05-24 18:00:00'),
(1105, '305762', 'Md. Asaduzzaman Asad', 'Medical Promotion Officer', 'Khulna-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997141', 'asadkalaroa1992@gmail.com', '2023-06-18', 1, '2026-05-24 18:00:00'),
(1106, '305763', 'Md. Saheen', 'Senior Medical Promotion Officer', 'Bera', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997139', 'saheen9973@gmail.com', '2023-06-17', 1, '2026-05-24 18:00:00'),
(1107, '305764', 'Moksedur Rahman', 'Medical Promotion Officer', 'Rangpur-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997138', 'mdmoksedali908@gmail.com', '2023-06-18', 1, '2026-05-24 18:00:00'),
(1108, '305767', 'Shiblu Ahmmad', 'Senior Medical Promotion Officer', 'Natore-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997134', 'ashiblu243@gmail.com', '2023-06-18', 1, '2026-05-24 18:00:00'),
(1109, '305771', 'Md. Habibur Rahman', 'Medical Promotion Officer', 'Gulshan-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994128', 'habiburrahmanmd7159@gmail.com', '2023-06-18', 1, '2026-05-24 18:00:00'),
(1110, '305772', 'Jashim Uddin', 'Area Sales Manager', 'Mymensingh-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997144', 'jashimuddin027@gmail.com', '2023-06-10', 1, '2026-05-24 18:00:00'),
(1111, '305774', 'Mamun Sirajun Nabi', 'Area Sales Manager', 'Dhaka-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997142', 'sirajunmoon@gmail.com', '2023-06-11', 1, '2026-05-24 18:00:00'),
(1112, '305778', 'Anik Chandra Somadder', 'Medical Promotion Officer', 'Barguna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997145', 'anik96nov@gmail.com', '2023-06-22', 1, '2026-05-24 18:00:00'),
(1113, '305780', 'Md. Redoan Hossain', 'Medical Promotion Officer', 'Birampur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997147', 'redoanhossain96@gmail.com', '2023-06-24', 1, '2026-05-24 18:00:00'),
(1114, '305782', 'Md. Nazir Hossain', 'Senior Medical Promotion Officer', 'Natore-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997149', 'hossainmdnazil016@gamil.com', '2023-07-02', 1, '2026-05-24 18:00:00'),
(1115, '305784', 'Md. Shamim Islam', 'Medical Promotion Officer', 'Kaliakair', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995637', 'samimislam7351@gmail.com', '2023-07-03', 1, '2026-05-24 18:00:00'),
(1116, '305787', 'Md. Asaduzzaman', 'Senior Medical Promotion Officer', 'Nageshwari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994521', 'masaduzzaman.rasel4189@gmail.com', '2023-07-02', 1, '2026-05-24 18:00:00'),
(1117, '305790', 'Md. Kabirul Islam', 'Senior Medical Promotion Officer', 'Narsingdi-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996820', 'kabirul2545@gmail.com', '2023-07-01', 1, '2026-05-24 18:00:00'),
(1118, '305791', 'Md. Abdullah Al Mamun', 'Senior Medical Promotion Officer', 'Faridpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993926', 'abdullahallmamun514@gmail.com', '2023-07-01', 1, '2026-05-24 18:00:00'),
(1119, '305792', 'Md. Al Mamun', 'Medical Promotion Officer', 'Bogra-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997153', 'abdullahallmamun4795@gmail.com', '2023-07-01', 1, '2026-05-24 18:00:00'),
(1120, '305794', 'Md. Shamimur Rahman', 'Senior Medical Promotion Officer', 'Raipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997154', 'shamimraiyan1987@gmail.com', '2023-07-06', 1, '2026-05-24 18:00:00'),
(1121, '305795', 'Mithun Kumar Dey', 'Senior Medical Promotion Officer', 'Bagerhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997164', 'mithunkumardey125@gmail.com', '2023-07-06', 1, '2026-05-24 18:00:00'),
(1122, '305796', 'Amit Roy', 'Senior Medical Promotion Officer', 'Chandpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993547', 'amitroy0171505@gmail.com', '2023-07-06', 1, '2026-05-24 18:00:00'),
(1123, '305797', 'Md. Emran Hossain', 'Senior Medical Promotion Officer', 'General Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997155', 'emranh251087@gmail.com', '2023-07-12', 1, '2026-05-24 18:00:00'),
(1124, '305798', 'Md. Monir Hossen', 'Senior Medical Promotion Officer', 'CMOCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996853', 'monirhossenm692@gmail.com', '2023-07-09', 1, '2026-05-24 18:00:00'),
(1125, '305799', 'Nuruzzaman', 'Senior Medical Promotion Officer', 'Coxsbazar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997162', 'nuruzzaman082@gmail.com', '2023-07-08', 1, '2026-05-24 18:00:00'),
(1126, '305801', 'Mintu Kumar Shil', 'Medical Promotion Officer', 'CMCH-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997159', 'mintshil428@gmail.com', '2023-07-09', 1, '2026-05-24 18:00:00'),
(1127, '305803', 'Shimul Roy', 'Medical Promotion Officer', 'Dobashi Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997156', 'rshimul181@gmail.com', '2023-07-09', 1, '2026-05-24 18:00:00'),
(1128, '305804', 'Md. Rokibul Hasan', 'Medical Promotion Officer', 'Sitakunda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997160', 'rh8627463@gmail.com', '2023-07-06', 1, '2026-05-24 18:00:00'),
(1129, '305808', 'Md. Ariful Islam', 'Medical Promotion Officer', 'Gulshan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994295', 'arifulislam2020@gmail.com', '2023-06-15', 1, '2026-05-24 18:00:00'),
(1130, '305810', 'Md. Neyamat Fakir', 'Senior Medical Promotion Officer', 'Nabinagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997169', 'neyamatfakir99@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1131, '305815', 'Saiful Islam', 'Medical Promotion Officer', 'Laksam-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997178', 'imdsaif574@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1132, '305816', 'Md. Abul Khaer', 'Medical Promotion Officer', 'Feni-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997173', 'tuta8334@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1133, '305818', 'Md. Sohel Rana', 'Medical Promotion Officer', 'Chatkhil', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997174', 'sohelzoology916@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1134, '305819', 'Md. Shahabuddin', 'Medical Promotion Officer', 'Jessore-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997177', 'shahabuddin1794@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1135, '305820', 'Kabir', 'Medical Promotion Officer', 'Coxsbazar-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997176', 'mmdkabirhossain9228@gmail.com', '2023-07-16', 1, '2026-05-24 18:00:00'),
(1136, '305822', 'Mohammad Shariful Alam', 'Senior Area Sales Manager', 'Chittagong-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993168', '305822.unigroup@gmail.com', '2023-07-15', 1, '2026-05-24 18:00:00'),
(1137, '305823', 'Md. Mahirul Islam', 'Area Sales Manager', 'SOMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994777', 'mahirulru929@gmail.com', '2023-08-28', 1, '2026-05-24 18:00:00'),
(1138, '305824', 'Md. Humayun Kabir', 'Area Sales Manager', 'Sylhet-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996922', 'humayun.poet@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(1139, '305827', 'Md. Tuhin Hossain', 'Senior Medical Promotion Officer', 'Comilla City-20', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994173', 'tuhinurrahmanbd@gmail.com', '2023-07-29', 1, '2026-05-24 18:00:00'),
(1140, '305829', 'Md. Mahedi Hasan', 'Senior Medical Promotion Officer', 'Evercare-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996351', 'mdmaheihasan5@gmail.com', '2023-07-29', 1, '2026-05-24 18:00:00'),
(1141, '305830', 'Sohag Kumar Das', 'Senior Medical Promotion Officer', 'Barisal-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997017', 'sohagkumardas.skd@gamil.com', '2023-07-30', 1, '2026-05-24 18:00:00'),
(1142, '305832', 'Md. Rajibul Islam', 'Medical Promotion Officer', 'Noapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997181', 'rajibulislam9983@gamil.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(1143, '305833', 'Saddam Hossen', 'Medical Promotion Officer', 'Gulshan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994134', 'saddam244954@gamil.com', '2023-08-02', 1, '2026-05-24 18:00:00'),
(1144, '305834', 'Md. Khairul Islam', 'Medical Promotion Officer', 'Mirpur-B6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994133', 'mdkhairulislamkhokon69@gmail.com', '2023-08-02', 1, '2026-05-24 18:00:00'),
(1145, '305835', 'Md. Shahidul Islam', 'Medical Promotion Officer', 'Sylhet-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997016', 'mdshahiduli003@gmail.com', '2023-08-05', 1, '2026-05-24 18:00:00'),
(1146, '305836', 'Md. Jahid Hossain', 'Senior Medical Promotion Officer', 'NICVD-A7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996787', 'jahid24jsr@gmail.com', '2023-08-05', 1, '2026-05-24 18:00:00'),
(1147, '305845', 'Md. Mohidur Mia', 'Senior Medical Promotion Officer', 'Jassore-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994155', 'mohidurrahaman4@gmail.com', '2023-08-17', 1, '2026-05-24 18:00:00'),
(1148, '305846', 'Abu Bakkar Siddik', 'Medical Promotion Officer', 'Mongla-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993821', 'absiddik602ksk@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1149, '305847', 'Md. Abdullah-Al-Mamun', 'Senior Medical Promotion Officer', 'DNMCH/Postogola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994679', 'almamun899737@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1150, '305848', 'Md. Anisur Rahman', 'Senior Medical Promotion Officer', 'Lakshmipur-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997184', 'ahsumon43@gmail.com', '2023-08-17', 1, '2026-05-24 18:00:00'),
(1151, '305849', 'Md. Shamim Islam', 'Medical Promotion Officer', 'Dinajpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997183', 'islamshamim@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1152, '305850', 'Md. Sadekul Islam', 'Senior Medical Promotion Officer', 'Lalmonirhat-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995256', 'mdsadekuli94@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1153, '305851', 'Rubel Islam', 'Medical Promotion Officer', 'Kalapara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993779', 'rubelshekdar@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1154, '305857', 'Md. Abdul Kader', 'Medical Promotion Officer', 'Narayanganj-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994956', 'abdulkaderk999@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1155, '305858', 'Md. Zakaria Islam', 'Medical Promotion Officer', 'Mymensingh-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997188', 'ihridoy638@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1156, '305859', 'Md. Mokhlesur Rahman', 'Medical Promotion Officer', 'Narsingdi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994119', 'mokhlesur0603@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1157, '305860', 'Akbar Hossain', 'Medical Promotion Officer', 'CMOCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997190', 'akbarhossain058@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1158, '305867', 'Md. Ali Akbar', 'Medical Promotion Officer', 'Sylhet-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997046', 'mail.alibd24@gmail.com', '2023-08-16', 1, '2026-05-24 18:00:00'),
(1159, '305869', 'Md. Joynal Abedin', 'Medical Promotion Officer', 'Beanibazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997189', 'ump.joynalabedin@gmail.com', '2023-08-17', 1, '2026-05-24 18:00:00'),
(1160, '305872', 'Md. Dulal Hossen', 'Medical Promotion Officer', 'Mymensingh-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997198', 'dulal1371994@gamil.com', '2023-08-31', 1, '2026-05-24 18:00:00'),
(1161, '305873', 'Md. Mahady Hassan', 'Medical Promotion Officer', 'Gulshan-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993211', 'mahady88hassan@gamil.com', '2023-09-01', 1, '2026-05-24 18:00:00'),
(1162, '305874', 'Mahbubur Rahman', 'Medical Promotion Officer', 'Bogra-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993934', 'mahbuburrahmanm44@gmail.com', '2023-09-01', 1, '2026-05-24 18:00:00'),
(1163, '305875', 'Md. Sohel Rana', 'Medical Promotion Officer', 'Pirojpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997197', 'borhanmd596@gmail.com', '2023-08-30', 1, '2026-05-24 18:00:00'),
(1164, '305876', 'Mamunur Rashid', 'Senior Medical Promotion Officer', 'Sadarpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995120', 'mamunur1047@gmail.com', '2023-08-27', 1, '2026-05-24 18:00:00'),
(1165, '305877', 'Md. Habibullah', 'Senior Medical Promotion Officer', 'Khalispur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994156', 'habibullahsharif@gmail.com', '2023-08-27', 1, '2026-05-24 18:00:00'),
(1166, '305879', 'Md. Rabiul Islam', 'Medical Promotion Officer', 'Magura-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993885', 'rabiulshahin1111@gmail.com', '2023-08-28', 1, '2026-05-24 18:00:00'),
(1167, '305881', 'Md. Abu Rasel', 'Medical Promotion Officer', 'Alfadanga-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994150', 'aburasel.unigroup@gmail.com', '2023-08-27', 1, '2026-05-24 18:00:00'),
(1168, '305883', 'Mirza Md. Jowel', 'Senior Medical Promotion Officer', 'Islampur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995338', 'mirzajowel2244@gmail.com', '2023-08-26', 1, '2026-05-24 18:00:00'),
(1169, '305884', 'Md. Nazir Hossain', 'Senior Medical Promotion Officer', 'Beanibazar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993785', 'mnazirhossain88@gmail.com', '2023-08-27', 1, '2026-05-24 18:00:00'),
(1170, '305885', 'Md. Shahin Alom', 'Medical Promotion Officer', 'MUMC/Sipahibag', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993323', 'shahinalomrajman@gmail.com', '2023-08-31', 1, '2026-05-24 18:00:00'),
(1171, '305887', 'Md. Abdul Momin', 'Senior Area Sales Manager', 'Maijdee', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997055', 'abdulmomin1130@gmail.com', '2023-08-26', 1, '2026-05-24 18:00:00'),
(1172, '305889', 'Md. Mamun Rana', 'Area Sales Manager', 'Mitford', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996950', 'mamunranapk@gmail.com', '2023-09-04', 1, '2026-05-24 18:00:00'),
(1173, '305890', 'Gazi Rabiul', 'Area Sales Manager', 'PMCH+NEMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993525', 'onerobi1985@gmail.com', '2023-10-10', 1, '2026-05-24 18:00:00'),
(1174, '305892', 'Md. Ashikur Rahman', 'Senior Medical Promotion Officer', 'Satkhira-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994658', 'asikurrahmanhpl@gmail.com', '2023-09-13', 1, '2026-05-24 18:00:00'),
(1175, '305893', 'Md. Mainul Haque', 'Senior Medical Promotion Officer', 'Dhamrai-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997209', 'mainulhaque474@gmail.com', '2023-09-11', 1, '2026-05-24 18:00:00'),
(1176, '305894', 'Subrata Biswas', 'Senior Medical Promotion Officer', 'Bhanga-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994374', 'yepskb@gmail.com', '2023-09-12', 1, '2026-05-24 18:00:00'),
(1177, '305899', 'Abdullah Al Mamun', 'Senior Medical Promotion Officer', 'Chougachha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994818', 'almamun.kotchandpur@gmail.com', '2023-09-14', 1, '2026-05-24 18:00:00'),
(1178, '305901', 'Md. Litan Rana', 'Medical Promotion Officer', 'Panchbibi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995466', '1rlitanrana@gmail.com', '2023-09-16', 1, '2026-05-24 18:00:00'),
(1179, '305902', 'Md. Maznu Bari', 'Medical Promotion Officer', 'DNMCH/Dholaipar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995372', 'mdmaznubari@gmail.com', '2023-09-11', 1, '2026-05-24 18:00:00'),
(1180, '305903', 'Shahinur Islam', 'Senior Medical Promotion Officer', 'Chowmuhani-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997207', 'shahinurpmc@gmail.com', '2023-09-16', 1, '2026-05-24 18:00:00'),
(1181, '305905', 'Md. Atikur Rahman Khan', 'Senior Medical Promotion Officer', 'Mathbaria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997206', 'atikurrahman8046@gmail.com', '2023-09-14', 1, '2026-05-24 18:00:00'),
(1182, '305906', 'Zobaer Ibne Sayed', 'Medical Promotion Officer', 'BSMMU-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994935', 'roni360585@gmail.com', '2023-09-16', 1, '2026-05-24 18:00:00'),
(1183, '305907', 'Arun Chandra Roy', 'Medical Promotion Officer', 'NICVD-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993371', 'aroy89132@gmail.com', '2023-09-14', 1, '2026-05-24 18:00:00'),
(1184, '305909', 'Md. Abdul Mannan', 'Senior Medical Promotion Officer', 'B.Baria-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997225', 'mmdabdul884@gmail.com', '2023-09-28', 1, '2026-05-24 18:00:00'),
(1185, '305910', 'Khagendranathray', 'Medical Promotion Officer', 'B.Baria-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997226', 'khagendra351595@gmail.com', '2023-09-27', 1, '2026-05-24 18:00:00'),
(1186, '305911', 'Mohammad Ali Kha', 'Senior Medical Promotion Officer', 'Netrokona-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996329', 'sobuzkha18@gmail.com', '2023-08-24', 1, '2026-05-24 18:00:00'),
(1187, '305914', 'Md. Akhtaruzzaman Rusho', 'Medical Promotion Officer', 'MuMC/Khidmah-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997224', 'rushoahmed108@gmail.com', '2023-09-25', 1, '2026-05-24 18:00:00'),
(1188, '305916', 'Pranab Kanti Sarker', 'Senior Medical Promotion Officer', 'Golapganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997214', 'pranabsarker62@gmail.com', '2023-09-24', 1, '2026-05-24 18:00:00'),
(1189, '305917', 'Paritosh Chandra Roy', 'Medical Promotion Officer', 'Charbhadrasan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997210', 'paritoshr866@gmail.com', '2023-09-25', 1, '2026-05-24 18:00:00'),
(1190, '305919', 'Md. Abdul Majid', 'Senior Medical Promotion Officer', 'Rupganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997212', 'abdulmajidsmc@gmail.com', '2023-09-24', 1, '2026-05-24 18:00:00'),
(1191, '305920', 'Md. Mazharul Islam', 'Medical Promotion Officer', 'Kotalipara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996306', 'mazharulislam123214@gmail.com', '2023-10-24', 1, '2026-05-24 18:00:00'),
(1192, '305921', 'Md. Shakibul Hasan', 'Senior Medical Promotion Officer', 'Savar-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995305', 'shakibul25@gmail.com', '2023-09-26', 1, '2026-05-24 18:00:00'),
(1193, '305922', 'Diponker Chandra Gharami', 'Medical Promotion Officer', 'Charfesson-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997211', 'diponkerdipu9461@gmail.com', '2023-09-23', 1, '2026-05-24 18:00:00'),
(1194, '305925', 'Md. Sujan Ali Mondal', 'Senior Medical Promotion Officer', 'Kurmitola-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996324', 'sujanalimondal6@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(1195, '305927', 'Sumontokumar', 'Senior Medical Promotion Officer', 'Noapara-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993493', 'sumontokumar958@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1196, '305928', 'Md. Mezanur Rahman', 'Senior Medical Promotion Officer', 'Kalmakanda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994752', 'mezanbadal1@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(1197, '305929', 'Md. Alauddin', 'Medical Promotion Officer', 'Natore-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994848', 'pmazad1989@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(1198, '305932', 'Md. Ziaur Rahman', 'Medical Promotion Officer', 'Ranisankail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994996', 'ziaur.rahman500200@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(1199, '305935', 'Azahar Ali', 'Medical Promotion Officer', 'Chowmuhani-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997234', 'azaharali1050@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1200, '305936', 'Md. Rakib Hassan', 'Medical Promotion Officer', 'Kaliganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997235', 'rakibjubaye61@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1201, '305937', 'Syed Al Amin', 'Medical Promotion Officer', 'Barisal-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997236', 'syedalaminnn@gmail.com', '2023-10-09', 1, '2026-05-24 18:00:00'),
(1202, '305939', 'Md. A B Kazol', 'Medical Promotion Officer', 'Bhaluka', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997044', 'abkazol76@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1203, '305940', 'Md. Abdullah Al Noman', 'Medical Promotion Officer', 'Uttara-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996817', 'rahanpur1navana21119@gmail.com', '2023-10-10', 1, '2026-05-24 18:00:00'),
(1204, '305941', 'Mohammad Jahirul Alam', 'Senior Medical Promotion Officer', 'DMCH-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994044', 'mhjisan1232@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1205, '305943', 'Md. Ruhul Amin', 'Medical Promotion Officer', 'Charfasson', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997237', 'ruhulsumoya@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1206, '305944', 'Md. Quamruzzaman', 'Medical Promotion Officer', 'Madaripur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997161', 'kamruzzaman.mcj@gmail.com', '2023-10-11', 1, '2026-05-24 18:00:00'),
(1207, '305946', 'Halal Uddin', 'Medical Promotion Officer', 'Comilla City-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994228', 'helal494277r@gmail.com', '2023-10-25', 1, '2026-05-24 18:00:00'),
(1208, '305948', 'Md. Rabiul Alom Khan', 'Medical Promotion Officer', 'Cantonment', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996716', 'rabiul016788@gmail.com', '2023-10-25', 1, '2026-05-24 18:00:00'),
(1209, '305950', 'Md. Tuhin Islam', 'Senior Medical Promotion Officer', 'Bauphal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997146', 'tuhinbadal@gmail.com', '2023-10-25', 1, '2026-05-24 18:00:00'),
(1210, '305952', 'Md. Rayhan Babu', 'Medical Promotion Officer', 'Comilla-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997240', 'arkobir567@gmail.com', '2023-10-26', 1, '2026-05-24 18:00:00'),
(1211, '305954', 'Md. Habibur Rahman', 'Medical Promotion Officer', 'Comilla-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997242', 'shamimhabib129@gmail.com', '2023-10-26', 1, '2026-05-24 18:00:00'),
(1212, '305955', 'Uthshas Kumer Sarker', 'Medical Promotion Officer', 'Comilla-C5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997243', 'sarkeruthshas@gamil.com', '2023-10-26', 1, '2026-05-24 18:00:00'),
(1213, '305958', 'Md. Rubel Rana', 'Medical Promotion Officer', 'Sylhet-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994725', 'mdrubelrana042@gmail.com', '2023-11-04', 1, '2026-05-24 18:00:00'),
(1214, '305959', 'Mohammad Ibrahim', 'Senior Medical Promotion Officer', 'Jamal Khan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996838', 'mdibrahim1692@gmail.com', '2023-11-04', 1, '2026-05-24 18:00:00'),
(1215, '305960', 'Md. Mamun Sarker', 'Medical Promotion Officer', 'Chandina', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993494', 'mamunsarker5251@gmail.com', '2023-11-04', 1, '2026-05-24 18:00:00'),
(1216, '305961', 'Md. Jashim Uddin', 'Area Sales Manager', 'Chhatak', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997118', 'mjashim777@gmail.com', '2023-10-22', 1, '2026-05-24 18:00:00'),
(1217, '305962', 'Manik Chandra Das', 'Senior Area Sales Manager', 'Chittagong', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994056', 'manikchittagong.bump@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1218, '305964', 'Md. Rotonuzzaman', 'Senior Area Sales Manager', 'Feni', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994048', 'mr.zaman4@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1219, '305967', 'Masud Al Kabir Rajan', 'Area Sales Manager', 'Coxsbazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997045', 'mahbub13775@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1220, '305968', 'Md. Nur Alam', 'Area Sales Manager', 'Maijdee', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994557', 'nuralamsquare@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1221, '305969', 'Md. Munnaf Ali', 'Area Sales Manager', 'Rangpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994333', 'munnaf101282@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1222, '305970', 'Mohammad Safikul Islam', 'Area Sales Manager', 'Moulvibazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993293', 'sislamrenata@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1223, '305972', 'Md. Arif Hossain', 'Senior Area Sales Manager', 'Nazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993904', 'arif.skf2015@gmail.com', '2023-11-01', 1, '2026-05-24 18:00:00'),
(1224, '305975', 'Md. Mizanur Rahman', 'Senior Area Sales Manager', 'Rangpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994031', 'mizanraj38@gmail.com', '2023-11-04', 1, '2026-05-24 18:00:00'),
(1225, '305997', 'Md. Rakib Hossain', 'Senior Medical Promotion Officer', 'SOMCH-1B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994329', 'rakibhossainka@gmail.com', '2023-10-21', 1, '2026-05-24 18:00:00'),
(1226, '306005', 'Md. Mehedi Hasan', 'Senior Area Sales Manager', 'Sirajganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993609', 'mehedisujon81@gmail.com', '2011-01-17', 1, '2026-05-24 18:00:00'),
(1227, '306026', 'Mohammad Faisal Tareq', 'Deputy Sales Manager', 'UniHealth NAOS', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993206', 'faisal.tareq@unigroup-bd.com', '2011-02-18', 1, '2026-05-24 18:00:00'),
(1228, '306028', 'Md. Rafiqul Islam', 'Senior Area Sales Manager', 'Narayanganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993648', 'rafiqueunigroup1970@gmail.com', '2011-02-26', 1, '2026-05-24 18:00:00'),
(1229, '306032', 'Md. Khabiruzzaman', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989993653', 'khabiruzzaman1979@gmail.com', '2011-02-27', 1, '2026-05-24 18:00:00'),
(1230, '306033', 'Pradip Kumar Nath', 'Senior Area Sales Manager', 'Chandpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993854', 'prodipnt@gmail.com', '2011-02-03', 1, '2026-05-24 18:00:00'),
(1231, '306034', 'Md. Jahid Hossain', 'Senior Area Sales Manager', 'Madaripur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993798', 'jahidhossain.unigroup@gmail.com', '2011-03-06', 1, '2026-05-24 18:00:00'),
(1232, '306047', 'Md. Samiul Haque Basunia', 'Senior Area Sales Manager', 'Gaibandha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993307', 'samiul31289@gmail.com', '2011-03-27', 1, '2026-05-24 18:00:00'),
(1233, '306059', 'Arif Ahammed Bhuiyan', 'Senior Area Sales Manager', 'Narayanganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993749', 'arifahammedbhuiyan@gmail.com', '2011-04-02', 1, '2026-05-24 18:00:00'),
(1234, '306079', 'Md. Anamul Haque', 'Senior Medical Promotion Officer', 'UAMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993314', 'anamuljunior85@gmail.com', '2011-05-03', 1, '2026-05-24 18:00:00'),
(1235, '306081', 'Md. Sazzadul Afroz', 'General Manager, Sales', 'UniDerma National', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993316', 'sazzadul.afroz@unigroup-bd.com', '2011-05-22', 1, '2026-05-24 18:00:00'),
(1236, '306086', 'Md. Mamun Ali', 'Senior Area Sales Manager', 'Kashinathpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993537', 'mdmamunali507@gmail.com', '2011-05-07', 1, '2026-05-24 18:00:00'),
(1237, '306087', 'Md. Atikur Rahman', 'Senior Medical Promotion Officer', 'Magura-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993850', 'tipusultan7600@gmail.com', '2011-05-02', 1, '2026-05-24 18:00:00'),
(1238, '306088', 'Md. Tanvir Hossain', 'Senior Area Sales Manager', 'Gazipur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993693', 'tanvir.306088@gmail.com', '2011-05-03', 1, '2026-05-24 18:00:00'),
(1239, '306094', 'Md. Ashrafuzzaman', 'Senior Area Sales Manager', 'Jessore-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996920', 'ashrafuz.unigroup@gmail.com', '2011-05-29', 1, '2026-05-24 18:00:00'),
(1240, '306117', 'Md. Mazidul Islam', 'Senior Area Sales Manager', 'Munshiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993752', 'masudmasud5445@gmail.com', '2011-06-20', 1, '2026-05-24 18:00:00'),
(1241, '306122', 'G. M. Anisur Rahman', 'Area Sales Executive', 'Square-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993132', 'gmasabuj@gmail.com', '2011-06-20', 1, '2026-05-24 18:00:00'),
(1242, '306136', 'Mihir Chakraborty', 'Senior Area Sales Manager', 'Kishoreganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993725', 'mihir.unigroup@gmail.com', '2011-07-19', 1, '2026-05-24 18:00:00'),
(1243, '306141', 'Md. Shahin Uddin PK', 'Senior Area Sales Manager', 'Moulvibazar-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993781', 'pk.unigroup@gmail.com', '2011-08-15', 1, '2026-05-24 18:00:00'),
(1244, '306142', 'Mustafizur Rahman', 'Senior Medical Promotion Officer', 'Shyamnagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993713', 'mustafizurshyamngor1981@gmail.com', '2011-08-11', 1, '2026-05-24 18:00:00'),
(1245, '306150', 'Mohammad Azharul Islam', 'Senior Area Sales Manager', 'Kishoreganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993787', 'mdazharulislam912@gmail.com', '2011-08-13', 1, '2026-05-24 18:00:00'),
(1246, '306172', 'Md. Abu Shoayeb', 'Senior Area Sales Manager', 'Chittagong-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993326', 'abushoayeb@gmail.com', '2011-09-14', 1, '2026-05-24 18:00:00'),
(1247, '306177', 'Tapan Chandra Das', 'Deputy Sales Manager', 'Mymensingh-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993056', 'tapan.chandra@unigroup-bd.com', '2011-09-01', 1, '2026-05-24 18:00:00'),
(1248, '306186', 'Md. Abdul Kader Sheikh', 'Senior Area Sales Manager', 'Khalishpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993625', 'kader.unigroup@gmail.com', '2011-10-13', 1, '2026-05-24 18:00:00'),
(1249, '306194', 'Md. Quamrul Hasan', 'Senior Area Sales Manager', 'Narsingdi-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993673', 'quamrulunihealth1@gmail.com', '2011-10-18', 1, '2026-05-24 18:00:00'),
(1250, '306209', 'Badiuzzaman Howlader', 'Senior Area Sales Manager', 'Satkhira-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993674', 'b.zamanhowlader@gmail.com', '2011-12-20', 1, '2026-05-24 18:00:00'),
(1251, '306214', 'Md. Arif Hossain', 'Senior Area Sales Manager', 'Bhola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993817', 'harif2693@gmail.com', '2011-12-22', 1, '2026-05-24 18:00:00'),
(1252, '306225', 'Md. Abul Hossain', 'Area Sales Manager', 'Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993655', 'isdrana82@gmail.com', '2012-01-10', 1, '2026-05-24 18:00:00'),
(1253, '306229', 'Masud Sarder', 'Senior Medical Promotion Officer', 'Khagrachhari-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993868', 'wasudsarder3868@gamil.com', '2012-01-04', 1, '2026-05-24 18:00:00'),
(1254, '306230', 'Md. Zakir Hossain', 'Senior Medical Promotion Officer', 'Nazirhat-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993869', 'mduromax@gmail.com', '2012-01-04', 1, '2026-05-24 18:00:00'),
(1255, '306234', 'Mamun Karrany', 'Assistant Sales Manager', 'Bogra South', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993676', 'mamun.karrany@unigroup-bd.com', '2012-01-01', 1, '2026-05-24 18:00:00'),
(1256, '306251', 'Altab Hossain', 'Senior Medical Promotion Officer', 'Munshiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993564', 'altabhossain012@gmail.com', '2012-02-13', 1, '2026-05-24 18:00:00'),
(1257, '306253', 'Mohammad Khurshid Alam', 'Assistant Sales Manager', 'Barisal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993193', 'khurshid.alam@unigroup-bd.com', '2012-02-01', 1, '2026-05-24 18:00:00'),
(1258, '306260', 'Md. Hafijul Islam', 'Senior Medical Promotion Officer', 'Pabna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993376', 'hafizulunispt12@gmail.com', '2012-03-03', 1, '2026-05-24 18:00:00'),
(1259, '306267', 'Md. Rawshan Alam', 'Deputy Sales Manager', 'SSMC', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993377', 'rawshan.alam@unigroup-bd.com', '2012-02-13', 1, '2026-05-24 18:00:00'),
(1260, '306274', 'Md. Jafar', 'Senior Medical Promotion Officer', 'Feni-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993552', 'jafarmd707@gmail.com', '2012-03-18', 1, '2026-05-24 18:00:00'),
(1261, '306283', 'Md. Hafizul Haq', 'Senior Area Sales Manager', 'Kushtia-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993748', 'hafiz.unigroup@gmail.com', '2012-05-06', 1, '2026-05-24 18:00:00'),
(1262, '306289', 'Md. Ziaur Rahman', 'Area Sales Manager', 'Mymensingh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993174', 'ziaur.unigroup@gmail.com', '2012-05-22', 1, '2026-05-24 18:00:00'),
(1263, '306290', 'Md. Abu Mottaleb', 'Senior Area Sales Manager', 'Dhaka-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993110', 'abumottaleb.unihealth@gmail.com', '2012-04-22', 1, '2026-05-24 18:00:00'),
(1264, '306301', 'Mizanur Rahman Shipon', 'Senior Medical Promotion Officer', 'Pabna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993744', 'shiponrahman303@gmail.com', '2012-04-06', 1, '2026-05-24 18:00:00'),
(1265, '306307', 'Md. Towfiq Ur Rahman', 'Area Sales Manager', 'Tangail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993646', 'towfiqunigroup@gmail.com', '2012-06-10', 1, '2026-05-24 18:00:00'),
(1266, '306328', 'Ajadur Rahman', 'Senior Medical Promotion Officer', 'Nagarpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993809', 'ajad14516@gmail.com', '2012-06-30', 1, '2026-05-24 18:00:00'),
(1267, '306330', 'Mohammed Nayamat Ulla', 'Area Sales Executive', 'Comilla City-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993795', 'nayamatulla715@gmail.com', '2012-06-30', 1, '2026-05-24 18:00:00'),
(1268, '306332', 'Md. Shah Jalal', 'Senior Area Sales Manager', 'Patuakhali-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993825', 'jalalkazi802@gmail.com', '2012-07-04', 1, '2026-05-24 18:00:00'),
(1269, '306339', 'Mohammad Shamsul Haque', 'Deputy Sales Manager', 'Narayanganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993432', 'shamsul.haque@unigroup-bd.com', '2012-07-05', 1, '2026-05-24 18:00:00'),
(1270, '306340', 'Anjan Kumar Dey', 'Sales Manager', 'DMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993407', 'anjan.kumar@unigroup-bd.com', '2012-06-27', 1, '2026-05-24 18:00:00'),
(1271, '306342', 'Saydul Islam', 'Senior Medical Promotion Officer', 'Bhairab-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993581', 'saidulislamunigroup@gmail.com', '2012-07-30', 1, '2026-05-24 18:00:00'),
(1272, '306348', 'Md. Enamul Hoque Bhiyan', 'Senior Area Sales Manager', 'Chowmuhani-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993154', 'enemulunimed9@gmail.com', '2012-07-30', 1, '2026-05-24 18:00:00'),
(1273, '306356', 'S. M. Masum Siddique', 'Senior Medical Promotion Officer', 'Dupchanchia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993605', 'masumsiddique485@gmail.com', '2012-08-23', 1, '2026-05-24 18:00:00'),
(1274, '306359', 'Md. Munsur Ali', 'Senior Medical Promotion Officer', 'Singra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993743', 'mdmunsur27061987@gmail.com', '2012-09-08', 1, '2026-05-24 18:00:00'),
(1275, '306381', 'Md. Mortuz Ali', 'Senior Area Sales Manager', 'CHANDPUR', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993645', 'mortuzali761@gmail.com', '2012-10-07', 1, '2026-05-24 18:00:00'),
(1276, '306392', 'Md. Saiful Islam', 'Senior Area Sales Manager', 'Habiganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993786', 'saiful1079@gmail.com', '2012-10-10', 1, '2026-05-24 18:00:00'),
(1277, '306399', 'Md. Abdur Rashid', 'Assistant Sales Manager', 'Dinajpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993500', 'abdur.rashid@unigroup-bd.com', '2012-09-16', 1, '2026-05-24 18:00:00'),
(1278, '306406', 'Shafiul Alam', 'Senior Area Sales Manager', 'Laksam-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993700', 'shafiulalam846@gmail.com', '2013-01-06', 1, '2026-05-24 18:00:00'),
(1279, '306407', 'Muhammad Bajlur Rashid', 'Senior Area Sales Manager', 'Rajshahi-A\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993440', 'mdbajlurrashid570@gmail.com', '2012-12-25', 1, '2026-05-24 18:00:00'),
(1280, '306416', 'Md. Ashaduzzaman', 'Senior Medical Promotion Officer', 'Mitford-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993178', 'mra61881@gmail.com', '2013-01-05', 1, '2026-05-24 18:00:00'),
(1281, '306419', 'Mohammad Manik Hossain', 'Senior Medical Promotion Officer', 'Bhola-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993624', 'manikhossainbho84@gmail.com', '2013-01-01', 1, '2026-05-24 18:00:00'),
(1282, '306423', 'Md. Abdul Hannan', 'Senior Medical Promotion Officer', 'Manikganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993586', 'maniksarker5290@gmail.com', '2013-01-02', 1, '2026-05-24 18:00:00'),
(1283, '306439', 'Md. Harun Or Rashid', 'Senior Area Sales Manager', 'Charfesson', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993450', 'harunrashid557@gmail.com', '2013-01-21', 1, '2026-05-24 18:00:00'),
(1284, '306445', 'Md. Nazrul Islam', 'Senior Area Sales Manager', 'Kurigram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993590', 'mdnazrulislam120@gmail.com', '2013-02-19', 1, '2026-05-24 18:00:00'),
(1285, '306451', 'Md. Riad Hossen', 'Senior Medical Promotion Officer', 'Kazipara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993807', 'riadhossen984@gmail.com', '2013-03-07', 1, '2026-05-24 18:00:00'),
(1286, '306456', 'Md. Iqbal Hossain Mandal', 'Senior Medical Promotion Officer', 'Kushtia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993546', 'iqbalmandal8118@gmail.com', '2013-02-18', 1, '2026-05-24 18:00:00'),
(1287, '306468', 'Jubawer Ahmed', 'Senior Area Sales Manager', 'Kulaura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993466', 'jubawerunigroup@gmail.com', '2013-03-16', 1, '2026-05-24 18:00:00'),
(1288, '306472', 'Md. Firoj Kabir', 'Senior Medical Promotion Officer', 'Mirpur-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993465', 'fkabir897@gmail.com', '2013-03-19', 1, '2026-05-24 18:00:00'),
(1289, '306473', 'Md. Mahmud Hasan', 'Senior Deputy Sales Manager', 'Kushtia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993461', 'mahmud.hasan@unigroup-bd.com', '2013-03-06', 1, '2026-05-24 18:00:00'),
(1290, '306474', 'Md. Manir Hossain', 'Senior Medical Promotion Officer', 'Chandpur-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993665', 'monirhossain1980md@gmail.com', '2013-03-18', 1, '2026-05-24 18:00:00'),
(1291, '306475', 'Md. Mosiur Rahman', 'Senior Area Sales Manager', 'Patuakhali-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993622', 'mosiurjkl7@gmail.com', '2013-03-18', 1, '2026-05-24 18:00:00'),
(1292, '306493', 'Md. Abdul Motaleb', 'Assistant Sales Manager', 'Mitford', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993794', 'mdabdulmotalebb@gmail.com', '2013-04-30', 1, '2026-05-24 18:00:00'),
(1293, '306509', 'Md. Aminul Islam', 'Senior Area Sales Manager', 'Companiganj/Basurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993892', 'md.aminul.islam.akash789@gmail.com', '2013-05-25', 1, '2026-05-24 18:00:00'),
(1294, '306518', 'Md. Kamrul Hasan', 'Senior Medical Promotion Officer', 'Feni-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993641', 'fni3652.unigroup@gmail.com', '2013-05-18', 1, '2026-05-24 18:00:00'),
(1295, '306524', 'Md. Monir Ahmed', 'Senior Area Sales Manager', 'Ukhiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993524', 'sumonicm@gmail.com', '2013-05-19', 1, '2026-05-24 18:00:00'),
(1296, '306535', 'Md. Moniruzzaman', 'Senior Medical Promotion Officer', 'SMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993637', 'mdmonir130706@gmail.com', '2013-07-17', 1, '2026-05-24 18:00:00'),
(1297, '306545', 'Md. Shoriful Islam', 'Senior Area Sales Manager', 'Uttara-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993135', 'shorifulislam47514@gmail.com', '2013-07-15', 1, '2026-05-24 18:00:00'),
(1298, '306557', 'Kubbat Ali Shaikh', 'Area Sales Manager', 'Jhalakathi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993831', 'kubbatali953@gmail.com', '2013-08-06', 1, '2026-05-24 18:00:00'),
(1299, '306561', 'Shahin Khan', 'Senior Medical Promotion Officer', 'Savar-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993584', 'shahinkhan.unigroup@gmail.com', '2013-04-09', 1, '2026-05-24 18:00:00'),
(1300, '306568', 'Hafizur Rahman', 'Senior Area Sales Manager', 'NIDCH-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993724', 'hafizur.rahman.uul@gmail.com', '2013-09-21', 1, '2026-05-24 18:00:00'),
(1301, '306573', 'Md. Badruzzaman', 'Senior Medical Promotion Officer', 'Bhaluka-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993741', 'uta1255.unigroup@gmail.com', '2013-09-28', 1, '2026-05-24 18:00:00'),
(1302, '306590', 'Boni Amin Molla', 'Senior Area Sales Manager', 'Faridpur-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993857', 'boniaminunimed@gmail.com', '2013-09-30', 1, '2026-05-24 18:00:00'),
(1303, '306610', 'Md. Ershad Ali', 'Senior Medical Promotion Officer', 'Naogaon-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993800', 'ershadaliunigroup86@gmail.com', '2013-11-01', 1, '2026-05-24 18:00:00'),
(1304, '306620', 'Kabirul Islam', 'Senior Medical Promotion Officer', 'Kushtia-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993913', 'alomnahid180@gmail.com', '2013-11-02', 1, '2026-05-24 18:00:00'),
(1305, '306630', 'Md. Mostafa Kamal', 'Senior Medical Promotion Officer', 'Chandpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993804', 'mostafaunigroup32@gmail.com', '2013-11-02', 1, '2026-05-24 18:00:00'),
(1306, '306634', 'Md. Ismail Hossan', 'Senior Medical Promotion Officer', 'Board Bazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993738', 'mdismailhassan6@gmail.com', '2013-11-03', 1, '2026-05-24 18:00:00'),
(1307, '306638', 'Md. Abdus Salam', 'Senior Area Sales Manager', 'Sylhet-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993555', 'salam01724754793@gmail.com', '2013-10-27', 1, '2026-05-24 18:00:00'),
(1308, '306639', 'Manik Chandra Karmokar', 'Assistant Sales Manager', 'Sylhet-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993057', 'manikchkarmaker3@gmail.com', '2013-11-21', 1, '2026-05-24 18:00:00'),
(1309, '306644', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Mitford/Doyaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993151', 'mizan011223@gmail.com', '2013-10-30', 1, '2026-05-24 18:00:00'),
(1310, '306650', 'Md. Mosaddequr Rahman', 'Senior Area Sales Manager', 'Hajiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993710', 'mosaddequrrahman80@gmail.com', '2013-12-14', 1, '2026-05-24 18:00:00'),
(1311, '306654', 'Muhammad Rakibul Alam Khan', 'Deputy Sales Manager', 'SHSMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993041', 'rakibul.alam@unigroup-bd.com', '2013-12-07', 1, '2026-05-24 18:00:00'),
(1312, '306656', 'Md. Abdullah Al Bakib', 'Assistant Sales Manager', 'Maijdee', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993315', 'abdullah.bakib@unigroup-bd.com', '2013-12-07', 1, '2026-05-24 18:00:00'),
(1313, '306664', 'Bulbul Ahmed', 'Senior Medical Promotion Officer', 'Mirpur-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993180', 'princedhk3563@gmail.com', '2014-01-25', 1, '2026-05-24 18:00:00'),
(1314, '306674', 'Mohammad Saifuddin', 'Senior Area Sales Manager', 'GEC', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993054', 'saifuddinparve37@gmail.com', '2014-01-19', 1, '2026-05-24 18:00:00'),
(1315, '306677', 'Md. Khairul Islam', 'Senior Area Sales Manager', 'BSMMU-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993224', 'khairul.islam87@gmail.com', '2014-02-16', 1, '2026-05-24 18:00:00'),
(1316, '306681', 'Md. Humayun Kabir', 'Senior Medical Promotion Officer', 'Malibagh-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993136', 'humayunkabir8111@gmail.com', '2014-02-20', 1, '2026-05-24 18:00:00'),
(1317, '306686', 'Sumon Chandra Mitra', 'Senior Medical Promotion Officer', 'Munshiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993670', 'mitra3075@gmail.com', '2014-02-22', 1, '2026-05-24 18:00:00'),
(1318, '306702', 'Md. Faruk Hossain', 'Senior Medical Promotion Officer', 'Gazipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993661', 'mfhossain202@gmail.com', '2014-02-23', 1, '2026-05-24 18:00:00'),
(1319, '306705', 'Md. Ibrahim Khalil Ullah', 'Senior Medical Promotion Officer', 'Patuakhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993548', 'ibrahimkhalilullah2u@gmail.com', '2014-03-01', 1, '2026-05-24 18:00:00'),
(1320, '306706', 'Md. Mahamud Riaj', 'Senior Medical Promotion Officer', 'R.K Mission', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993138', 'mahamudriaj999@gmail.com', '2014-02-20', 1, '2026-05-24 18:00:00'),
(1321, '306710', 'Md. Muttaleb Hossain', 'Senior Medical Promotion Officer', 'BMCH-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993205', 'muttaleb083@gmail.com', '2014-03-06', 1, '2026-05-24 18:00:00'),
(1322, '306728', 'Md. Nur Alam Hossain', 'Senior Area Sales Manager', 'Pabna-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994092', 'nuralom.unigroup@gmail.com', '2014-04-09', 1, '2026-05-24 18:00:00'),
(1323, '306733', 'Md. Munzur Hossain', 'Senior Area Sales Manager', 'Kurmitola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993372', 'mzn375@gmail.com', '2014-03-04', 1, '2026-05-24 18:00:00'),
(1324, '306743', 'Md. Anisur Rahman', 'Senior Area Sales Manager', 'Lakshmipur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993805', 'anisur.unigroup9724@gmail.com', '2014-05-05', 1, '2026-05-24 18:00:00'),
(1325, '306754', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'BSMMU/Square-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993452', 'saiful962468@gmail.com', '2014-03-05', 1, '2026-05-24 18:00:00'),
(1326, '306760', 'Khaled Nur Md. Masud', 'Senior Area Sales Manager', 'NIDCH-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993368', 'knmmasud11@gmail.com', '2014-05-22', 1, '2026-05-24 18:00:00'),
(1327, '306765', 'Md. Iran Hossain Sikder', 'Senior Medical Promotion Officer', 'Chevron-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993866', 'iransikder94@gmail.com', '2014-06-07', 1, '2026-05-24 18:00:00'),
(1328, '306766', 'Md. Farhad Uddin', 'Senior Medical Promotion Officer', 'Kamrangirchar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993477', 'farhad2023uddin@gmail.com', '2014-06-18', 1, '2026-05-24 18:00:00'),
(1329, '306767', 'Md. Ripon Ali', 'Senior Medical Promotion Officer', 'Rangamati-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993855', 'ripon6849@gmail.com', '2014-06-07', 1, '2026-05-24 18:00:00'),
(1330, '306799', 'Biplab Chandra Paul', 'Senior Medical Promotion Officer', 'UAMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993455', 'bpaul10.unigroup@gamil.com', '2014-09-10', 1, '2026-05-24 18:00:00'),
(1331, '306804', 'Md. Abdus Salam', 'Senior Medical Promotion Officer', 'Hathazari-IV', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993659', 'abdus3664salam@gmail.com', '2014-09-22', 1, '2026-05-24 18:00:00'),
(1332, '306805', 'Md. Mainul Islam', 'Sales Manager', 'Comilla-North', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993919', 'mainul.islam@unigroup-bd.com', '2014-08-23', 1, '2026-05-24 18:00:00'),
(1333, '306807', 'Md. Golam Azam', 'Area Sales Manager', 'NICRH/Gulshan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993596', 'gazam8957@gmail.com', '2014-09-09', 1, '2026-05-24 18:00:00'),
(1334, '306816', 'Md. Dayum Hossain', 'Senior Medical Promotion Officer', 'Maijdee-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993671', 'dayumhossain1987@gmail.com', '2014-09-14', 1, '2026-05-24 18:00:00'),
(1335, '306822', 'Md. Kabir Uddin', 'Senior Medical Promotion Officer', 'Tangail-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993756', 'kabirbeauty@gmail.com', '2014-09-13', 1, '2026-05-24 18:00:00'),
(1336, '306825', 'Md. Eliash Miah', 'Senior Medical Promotion Officer', 'Dinajpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993111', 'mdeliashmiah5979@gmail.com', '2014-09-08', 1, '2026-05-24 18:00:00'),
(1337, '306842', 'Md. Rafiqul Islam', 'Senior Area Sales Manager', 'Chapainawabganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993784', 'rafiqueislamunigroup@gmail.com', '2014-10-01', 1, '2026-05-24 18:00:00'),
(1338, '306843', 'Shekh Mohammad Ashraful Islam', 'Senior Area Sales Manager', 'Barisal-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993675', 'ashraf890unigroup@gmail.com', '2014-09-08', 1, '2026-05-24 18:00:00'),
(1339, '306849', 'Mohammad Ali Hosen Miah', 'Senior Medical Promotion Officer', 'UAMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993942', 'alihosen8938@gmail.com', '2014-10-01', 1, '2026-05-24 18:00:00'),
(1340, '306853', 'Md. Razu Kamal', 'Senior Medical Promotion Officer', 'Narayanganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993766', 'razukamal0047@gmail.com', '2014-10-01', 1, '2026-05-24 18:00:00'),
(1341, '306855', 'Md. Kamal Hossain', 'Senior Medical Promotion Officer', 'Jamalpur-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994505', 'kamalhossain.unigroup110@gmail.com', '2014-10-01', 1, '2026-05-24 18:00:00'),
(1342, '306856', 'Md. Tazammul Islam Kajol', 'Assistant Sales Manager', 'Khulna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993871', 'tazammul.unigroup@gmail.com', '2014-09-30', 1, '2026-05-24 18:00:00'),
(1343, '306864', 'Safayat Hossain', 'Area Sales Manager', 'Chhagalnaiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993643', 'safayation100@gmail.com', '2014-10-18', 1, '2026-05-24 18:00:00'),
(1344, '306869', 'Md. Abdul Wadud', 'Senior Area Sales Manager', 'Khulna-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993489', 'wadud.raju@gmail.com', '2014-10-28', 1, '2026-05-24 18:00:00'),
(1345, '306872', 'Anup Tarafder', 'Senior Area Sales Manager', 'Faridpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993728', 'anup.unigroup@gmail.com', '2014-10-20', 1, '2026-05-24 18:00:00'),
(1346, '306873', 'Md. Abdur Rahim Shek', 'Senior Medical Promotion Officer', 'Coxsbazar-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994192', 'abdurrahimshek69@gmail.com', '2014-10-19', 1, '2026-05-24 18:00:00'),
(1347, '306874', 'Md. Jannatun Naim', 'Senior Medical Promotion Officer', 'Trust', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993955', 'm.j.naimjigar@gmail.com', '2014-10-19', 1, '2026-05-24 18:00:00'),
(1348, '306876', 'Murad Hossain', 'Senior Medical Promotion Officer', 'Chatkhil-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993922', 'muradhossain439951@gmail.com', '2014-10-18', 1, '2026-05-24 18:00:00'),
(1349, '306880', 'Mohammad Shamsul Haque', 'Sales Manager', 'Dhaka Central', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993073', 'shamsul.haque2@unigroup-bd.com', '2014-11-02', 1, '2026-05-24 18:00:00'),
(1350, '306882', 'Md. Sakil Hossain Lasker', 'Assistant Sales Manager', 'Pabna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993106', 'sakil.hossain@unigroup-bd.com', '2014-11-15', 1, '2026-05-24 18:00:00'),
(1351, '306891', 'Mithun Kumer Sarker', 'Senior Medical Promotion Officer', '60 Feet', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993442', 'mithusarker88@gmail.com', '2014-11-22', 1, '2026-05-24 18:00:00'),
(1352, '306903', 'Md. Abu Nayem', 'Area Sales Manager', 'Bhairab', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994100', 'anayem1985@gamil.com', '2014-11-20', 1, '2026-05-24 18:00:00'),
(1353, '306904', 'Md. Shariful Islam', 'Senior Medical Promotion Officer', 'Chittagong Road-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993948', 'sharifswapon1985@gmail.com', '2014-11-20', 1, '2026-05-24 18:00:00'),
(1354, '306908', 'Md. Nazmul Hussain', 'Area Sales Manager', 'Chandpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993449', 'nazmul.uniderma@gmail.com', '2014-11-15', 1, '2026-05-24 18:00:00'),
(1355, '306914', 'Md. Abdur Rahim', 'Senior Medical Promotion Officer', 'Babuganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993945', 'mdabdurr009@gmail.com', '2014-12-04', 1, '2026-05-24 18:00:00'),
(1356, '306915', 'Nuron Nabi', 'Senior Medical Promotion Officer', 'Mitford/Rayerbagh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993946', 'nurnabi12345689@gmail.com', '2014-12-03', 1, '2026-05-24 18:00:00'),
(1357, '306927', 'Md. Sultan Ahmed', 'Senior Area Sales Manager', 'Khulna-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993197', 'sultandinj@gmail.com', '2014-12-08', 1, '2026-05-24 18:00:00'),
(1358, '306931', 'Md. Idrish Ali', 'Senior Medical Promotion Officer', 'Patuakhali-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993620', 'idris.unimed@gmail.com', '2014-10-23', 1, '2026-05-24 18:00:00'),
(1359, '306936', 'Abdur Rahman', 'Senior Medical Promotion Officer', 'Narsingdi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994086', 'sumoncvc9@gmail.com', '2014-12-23', 1, '2026-05-24 18:00:00'),
(1360, '306946', 'ASM Sayem', 'Senior Key Accounts Manager', 'Immunology&Psychiatry', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993950', 'sayem79n@gmail.com', '2014-12-09', 1, '2026-05-24 18:00:00'),
(1361, '306949', 'Milon Chandra Roy', 'Senior Medical Promotion Officer', 'Bagerhat-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994483', 'milonc788@gmail.com', '2015-01-10', 1, '2026-05-24 18:00:00'),
(1362, '306950', 'Hiren Chandra Mondal', 'Senior Medical Promotion Officer', 'Sirajganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994486', 'hirenmodad.2020@gmail.com', '2015-01-20', 1, '2026-05-24 18:00:00'),
(1363, '306957', 'Rafiqul Islam', 'Senior Medical Promotion Officer', 'Jassore-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994493', 'mdrafiqulislam402@gmail.com', '2015-01-25', 1, '2026-05-24 18:00:00'),
(1364, '306967', 'Md. Jwel Ali', 'Senior Medical Promotion Officer', 'Tangail-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994075', 'jwelali198801@gmail.com', '2015-01-18', 1, '2026-05-24 18:00:00'),
(1365, '306968', 'Suman Chowdhury', 'Senior Medical Promotion Officer', 'Barguna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993712', 'chowdhurysumon973@gmail.com', '2015-02-14', 1, '2026-05-24 18:00:00'),
(1366, '306969', 'Md. Sahabul Alam', 'Senior Medical Promotion Officer', 'Nilphamari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994191', 'mdshabulalam8@gmail.com', '2015-01-18', 1, '2026-05-24 18:00:00'),
(1367, '306972', 'Md. Shahin Uddin Talukder', 'Senior Medical Promotion Officer', 'Naogaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994104', 'shahintalukder87@gmail.com', '2015-02-16', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(1368, '306977', 'Md. Majnu Elahi', 'Senior Area Sales Manager', 'Natore-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993486', 'majnelahi007@gmail.com', '2015-02-01', 1, '2026-05-24 18:00:00'),
(1369, '306978', 'Musfiqur Rahman Siddique', 'Senior Medical Promotion Officer', 'Ibn Sina-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994099', 'paragmusfiq1@gmail.com', '2015-02-02', 1, '2026-05-24 18:00:00'),
(1370, '306982', 'Md. Shofiqul Islam Sorker', 'Senior Area Sales Manager', 'Tangail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993773', 'mdshofi20104@gmail.com', '2015-01-17', 1, '2026-05-24 18:00:00'),
(1371, '306990', 'Mirza Ruhul Amin', 'Senior Medical Promotion Officer', 'Dinajpur-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993875', 'mirza1988ruhul8@gmail.com', '2015-02-04', 1, '2026-05-24 18:00:00'),
(1372, '306992', 'Parimal Kumar Roy', 'Senior Area Sales Manager', 'Rajshahi-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993830', 'parimal.kumar958@gmail.com', '2015-02-09', 1, '2026-05-24 18:00:00'),
(1373, '306995', 'Md. Mafidul Islam', 'Senior Area Sales Manager', 'Jessore-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994103', 'mofidulislam6651@gmail.com', '2015-01-07', 1, '2026-05-24 18:00:00'),
(1374, '306997', 'Md. A. Khaleque', 'Senior Medical Promotion Officer', 'Feni-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994081', 'akhaleque27@gmail.com', '2015-02-04', 1, '2026-05-24 18:00:00'),
(1375, '306999', 'Md. Mominur Rahman', 'Senior Medical Promotion Officer', 'Sirajdikhan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993134', 'mominurrahman6242@gmail.com', '2015-02-04', 1, '2026-05-24 18:00:00'),
(1376, '307002', 'Matinur Rahman', 'Senior Medical Promotion Officer', 'Narayanpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993221', 'texmatinur@gmail.com', '2015-01-29', 1, '2026-05-24 18:00:00'),
(1377, '307004', 'Md. Shohel Ahammed', 'Senior Medical Promotion Officer', 'Sreenagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993967', 'sohelahammed1234567890@gmail.com', '2015-02-17', 1, '2026-05-24 18:00:00'),
(1378, '307007', 'Md. Omir Uddin Pk', 'Senior Medical Promotion Officer', 'Faridpur-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994082', 'far3736.unigroup@gmail.com', '2015-03-07', 1, '2026-05-24 18:00:00'),
(1379, '307021', 'Sadaruddin Ahmed', 'Senior Medical Promotion Officer', 'Jessore-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994083', 'sadaruddinahmed77@gmail.com', '2015-03-05', 1, '2026-05-24 18:00:00'),
(1380, '307030', 'Md. Emdadul Hoque', 'Senior Medical Promotion Officer', 'Badda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993220', 'kaziriponmahmud91@gmail.com', '2015-02-28', 1, '2026-05-24 18:00:00'),
(1381, '307033', 'Shaiful Islam', 'Senior Medical Promotion Officer', 'Narsingdi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994223', 'shaiful.unigroup11@gmail.com', '2015-03-03', 1, '2026-05-24 18:00:00'),
(1382, '307035', 'Mafuzur Rahman', 'Senior Medical Promotion Officer', 'Motijheel-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994175', 'mafuzurrahaman994@gamil.com', '2015-03-05', 1, '2026-05-24 18:00:00'),
(1383, '307041', 'Md. Sharifujjaman', 'Senior Area Sales Manager', 'Bishwanath', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994113', 'sharifent2223@gmail.com', '2015-03-06', 1, '2026-05-24 18:00:00'),
(1384, '307044', 'Md. Ruhul Amin', 'Senior Medical Promotion Officer', 'Tongi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994102', 'rukulparbotipur007@gamil.com', '2015-03-02', 1, '2026-05-24 18:00:00'),
(1385, '307053', 'Sk Nurmohammad', 'Senior Area Sales Manager', 'Sherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993436', 'nurunimed02@gmail.com', '2015-04-15', 1, '2026-05-24 18:00:00'),
(1386, '307064', 'Md. Alomgir Hossain', 'Senior Medical Promotion Officer', 'BSMMU/Green Life-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993463', 'alomrang2017@gmail.com', '2015-05-09', 1, '2026-05-24 18:00:00'),
(1387, '307068', 'Sabuj Mallick', 'Area Sales Manager', 'Mirpur-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993447', 'sabujmallick007@gmail.com', '2015-05-02', 1, '2026-05-24 18:00:00'),
(1388, '307075', 'Md. Rakibul Hasan', 'Senior Medical Promotion Officer', 'Metropolitan-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993662', 'rh453466@gmail.com', '2015-05-15', 1, '2026-05-24 18:00:00'),
(1389, '307077', 'Md. Abu Horayra', 'Senior Area Sales Manager', 'Dinajpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994193', 'ahorayrasw@gmail.com', '2015-05-23', 1, '2026-05-24 18:00:00'),
(1390, '307092', 'Md. Shahidul Islam', 'Senior Medical Promotion Officer', 'Noapara\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993569', 'khokon.ahmmad1987@gmail.com', '2015-06-02', 1, '2026-05-24 18:00:00'),
(1391, '307095', 'Ripan Roy', 'Area Sales Manager', 'Mohakhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994190', 'royripon1989@gmail.com', '2015-05-28', 1, '2026-05-24 18:00:00'),
(1392, '307102', 'Md. Mostafizur Rahman', 'Senior Medical Promotion Officer', 'Rajarhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993896', 'rahmanmostafiz7114@gmail.com', '2015-06-13', 1, '2026-05-24 18:00:00'),
(1393, '307103', 'Muhammad Zahidul Islam', 'Senior Medical Promotion Officer', 'Kasba-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994189', 'mzahidulislam406@gmail.com', '2015-06-14', 1, '2026-05-24 18:00:00'),
(1394, '307105', 'Md. Simoon Islam', 'Senior Medical Promotion Officer', 'Gazipur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994246', 'simoonislam2282@gmail.com', '2015-07-04', 1, '2026-05-24 18:00:00'),
(1395, '307110', 'Md. Obaydul Islam', 'Area Sales Manager', 'Feni-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994229', 'obaydulislam165@gmail.com', '2015-05-28', 1, '2026-05-24 18:00:00'),
(1396, '307111', 'Md. Ramjan Ali', 'Senior Medical Promotion Officer', 'Faridpur-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993723', 'ramjana507@gmail.com', '2015-07-05', 1, '2026-05-24 18:00:00'),
(1397, '307113', 'Md. Nasir Uddin', 'Senior Area Sales Manager', 'Tangail-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993960', 'nasirunihealth@gmail.com', '2015-06-16', 1, '2026-05-24 18:00:00'),
(1398, '307117', 'M. A. K. Azad', 'Senior Medical Promotion Officer', 'Khulna-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994213', 'makazad481@gmail.com', '2015-07-04', 1, '2026-05-24 18:00:00'),
(1399, '307118', 'Mohammad Masudur Rahman', 'Senior Medical Promotion Officer', 'Khulna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994214', 'mdmasudurunigroup@gmail.com', '2015-07-02', 1, '2026-05-24 18:00:00'),
(1400, '307119', 'K. M. Mokammel Haque', 'Senior Medical Promotion Officer', 'Faridpur-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994491', 'mokammelhoque72@gmail.com', '2015-07-05', 1, '2026-05-24 18:00:00'),
(1401, '307125', 'Zakir Hassan', 'Senior Medical Promotion Officer', 'Biswanath-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994244', 'zakirhassan1010@gmail.com', '2015-08-02', 1, '2026-05-24 18:00:00'),
(1402, '307130', 'Md. Shahibul Islam', 'Senior Area Sales Manager', 'Bauphal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994488', 'shahibulislam060@gmail.com', '2015-08-02', 1, '2026-05-24 18:00:00'),
(1403, '307131', 'Md. Nur Jamal', 'Senior Area Sales Manager', 'Lalmonirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994216', 'nur.jamal2011@gmail.com', '2015-08-01', 1, '2026-05-24 18:00:00'),
(1404, '307135', 'A.K.M Rezaul Karim', 'Assistant Sales Manager', 'Jassore', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994236', 'rezaul.korim@unigroup-bd.com', '2015-08-09', 1, '2026-05-24 18:00:00'),
(1405, '307136', 'Bepul Kumar', 'Senior Area Sales Manager', 'Kushtia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994230', 'bepulsamen@gmail.com', '2015-08-09', 1, '2026-05-24 18:00:00'),
(1406, '307139', 'Md. Nazrul Islam Suman', 'Senior Medical Promotion Officer', 'B.Baria-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994618', 'nazrulsuman18@gmail.com', '2015-09-11', 1, '2026-05-24 18:00:00'),
(1407, '307143', 'Md. Asaduzzaman', 'Senior Medical Promotion Officer', 'Pakerhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993810', 'asadmilon86@gmail.com', '2015-08-17', 1, '2026-05-24 18:00:00'),
(1408, '307149', 'Poritosh Kumar', 'Senior Medical Promotion Officer', 'Madhupur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993757', 'mk8982907@gmail.com', '2015-09-17', 1, '2026-05-24 18:00:00'),
(1409, '307150', 'Khandakar Zahidul Hasan Roni', 'Senior Medical Promotion Officer', 'Labaid-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994178', 'zahidhasanun1984@gmail.com', '2015-09-16', 1, '2026-05-24 18:00:00'),
(1410, '307158', 'Shajib Chakrabarty', 'Assistant Sales Manager', 'Faridpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994240', 'shajib.chakrabarty@unigroup-bd.com', '2015-09-01', 1, '2026-05-24 18:00:00'),
(1411, '307162', 'Md. Motahar Hossain', 'Senior Medical Promotion Officer', 'Keranirhat-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993759', 'motaherhossain160486@gmail.com', '2015-09-16', 1, '2026-05-24 18:00:00'),
(1412, '307169', 'Md. Kharizul Islam', 'Senior Medical Promotion Officer', 'Maijdee-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993698', 'akashmd95@gmail.com', '2015-10-15', 1, '2026-05-24 18:00:00'),
(1413, '307170', 'Md. Shamim Bhuiyan', 'Senior Medical Promotion Officer', 'Comilla City-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994255', 'shamimbhuiyan1985uni@gmail.com', '2015-10-15', 1, '2026-05-24 18:00:00'),
(1414, '307171', 'Ali Akkas', 'Area Sales Manager', 'Naogaon-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994256', 'aliakkas745@gmail.com', '2015-10-15', 1, '2026-05-24 18:00:00'),
(1415, '307184', 'Md. Noor E-Alam', 'Deputy Sales Manager', 'BSMMU-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994260', 'noor.alam@unigroup-bd.com', '2015-10-14', 1, '2026-05-24 18:00:00'),
(1416, '307186', 'Syed Anisur Rahman', 'Senior Medical Promotion Officer', 'Mitford-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993579', 'anisur.unigroup91115@gmail.com', '2015-11-09', 1, '2026-05-24 18:00:00'),
(1417, '307195', 'Md. Abdus Sobhan', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989993602', 'abdussobhan1981@gmail.com', '2015-11-09', 1, '2026-05-24 18:00:00'),
(1418, '307199', 'Khandoker Azizul Haque', 'Senior Medical Promotion Officer', 'BMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993480', 'shakilrrry@gmail.com', '2015-11-10', 1, '2026-05-24 18:00:00'),
(1419, '307200', 'Md. Kabir Hossain', 'Senior Area Sales Manager', 'Raipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993790', 'kabir7081@gmail.com', '2015-11-11', 1, '2026-05-24 18:00:00'),
(1420, '307201', 'Md. Shohel Rana Lebu', 'Senior Area Sales Manager', 'Rajshahi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993908', 'sohelranalabu28@gmail.com', '2015-11-10', 1, '2026-05-24 18:00:00'),
(1421, '307204', 'Md. Fakhlur Rahman', 'Senior Area Sales Manager', 'Chittagong Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994297', 'fakhlurrahman@gmail.com', '2015-11-29', 1, '2026-05-24 18:00:00'),
(1422, '307207', 'Md. Raihan Ali', 'Senior Medical Promotion Officer', 'BSMMU-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994274', 'raihanali890890@gmail.com', '2015-11-29', 1, '2026-05-24 18:00:00'),
(1423, '307209', 'Md. Mamun', 'Senior Medical Promotion Officer', 'Baraiyarhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994327', 'unimedandunihealth.mamun@gmail.com', '2015-12-01', 1, '2026-05-24 18:00:00'),
(1424, '307219', 'Shuhag Chandra Dey', 'Senior Medical Promotion Officer', 'Savar-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994101', 'shuhag83@gmail.com', '2015-12-05', 1, '2026-05-24 18:00:00'),
(1425, '307225', 'Ajit Chandra Barmon', 'Senior Area Sales Manager', 'Joypurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994311', 'ajitchandra.unigroup@gmail.com', '2015-12-05', 1, '2026-05-24 18:00:00'),
(1426, '307228', 'Momin Ali', 'Senior Area Sales Manager', 'Panchagarh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994331', 'mominali017@gmail.com', '2015-12-05', 1, '2026-05-24 18:00:00'),
(1427, '307233', 'Md. Kamrul Hasan', 'Senior Medical Promotion Officer', 'Chakaria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993213', 'mdkamrulh210@gmail.com', '2015-12-07', 1, '2026-05-24 18:00:00'),
(1428, '307237', 'Md. Wasim Uddin', 'Senior Medical Promotion Officer', 'Kuti', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994182', 'wasimuddin9475@gmail.com', '2015-12-07', 1, '2026-05-24 18:00:00'),
(1429, '307242', 'Md. Jamil Uddin', 'Senior Area Sales Manager', 'Pabna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994286', 'jamiluddin8273@gmail.com', '2015-12-17', 1, '2026-05-24 18:00:00'),
(1430, '307254', 'Kazi Mahamudul Hachan', 'Senior Medical Promotion Officer', 'Nagarkanda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993644', 'kazimahmudulhasan555@gmail.com', '2015-12-22', 1, '2026-05-24 18:00:00'),
(1431, '307259', 'Md. Hamidul Islam', 'Senior Medical Promotion Officer', 'Rajshahi-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994573', 'hamidul.unihealth@gmail.com', '2015-12-22', 1, '2026-05-24 18:00:00'),
(1432, '307265', 'Md. Firoz Kabir ', 'Senior Area Sales Manager', 'Narayanganj-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994290', 'kabir.firoz14@gmail.com', '2016-01-01', 1, '2026-05-24 18:00:00'),
(1433, '307274', 'Lelin Ahammed', 'Senior Medical Promotion Officer', 'Feni-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994350', 'lelin.liha2023@gmail.com', '2016-01-09', 1, '2026-05-24 18:00:00'),
(1434, '307280', 'Md. Abdul Halim', 'Senior Medical Promotion Officer', 'Bogra-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994347', 'abdulhalim8358@gmail.com', '2016-01-11', 1, '2026-05-24 18:00:00'),
(1435, '307282', 'Md. Sohel Rana Sardar', 'Senior Medical Promotion Officer', 'Mount Adora-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994367', 'sr7383319@gmail.com', '2016-01-11', 1, '2026-05-24 18:00:00'),
(1436, '307288', 'Md. Moniruzzaman', 'Senior Medical Promotion Officer', 'Kalaroa-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994319', 'mdmoniruzzaman318@gmail.com', '2016-01-11', 1, '2026-05-24 18:00:00'),
(1437, '307289', 'Md. Afsar Uddin', 'Senior Medical Promotion Officer', 'Sonaimuri-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994338', 'afsarahamed429@gmail.com', '2016-01-09', 1, '2026-05-24 18:00:00'),
(1438, '307290', 'Kalyan Ashis Roy', 'Senior Medical Promotion Officer', 'Jashore-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994402', 'ashisr039@gmail.com', '2016-01-09', 1, '2026-05-24 18:00:00'),
(1439, '307294', 'Md. Jasim Uddin', 'Senior Area Sales Manager', 'Natore-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994363', 'mhjasim1976@gmail.com', '2016-01-06', 1, '2026-05-24 18:00:00'),
(1440, '307298', 'Md. Raziqul Islam', 'Senior Medical Promotion Officer', 'Narail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994401', 'rafiqul697@gmail.com', '2016-01-09', 1, '2026-05-24 18:00:00'),
(1441, '307302', 'Md. Abdur Razzak', 'Area Sales Manager', 'Savar-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994362', 'razzakabdur7436@gmail.com', '2016-01-12', 1, '2026-05-24 18:00:00'),
(1442, '307310', 'Md. Miraz Hossain', 'Senior Medical Promotion Officer', 'Baraiyarhat-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994365', 'mirazhossain129@gmail.com', '2016-01-14', 1, '2026-05-24 18:00:00'),
(1443, '307316', 'M. Shoriful Islam', 'Senior Area Sales Manager', 'Comilla City-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994352', 'mshaorifulislam4353@gmail.com', '2016-01-22', 1, '2026-05-24 18:00:00'),
(1444, '307317', 'Md. Sazzad Hossain', 'Area Sales Manager', 'Syedpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994447', 'mdsazzadhossain968@gmail.com', '2016-01-20', 1, '2026-05-24 18:00:00'),
(1445, '307320', 'Khan Arif Billah', 'Senior Medical Promotion Officer', 'Dohar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994390', 'arifkhanbillah01@gmail.com', '2016-01-20', 1, '2026-05-24 18:00:00'),
(1446, '307321', 'Md. Sirajul Islam', 'Senior Medical Promotion Officer', 'Habiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994511', 'si600044@gmail.com', '2016-01-16', 1, '2026-05-24 18:00:00'),
(1447, '307326', 'Md. Zakir Hossain', 'Deputy Sales Manager', 'Chittagong-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994293', 'zakir.hossain2@unigroup-bd.com', '2015-12-31', 1, '2026-05-24 18:00:00'),
(1448, '307331', 'Prodip Kumar Biswas', 'Area Sales Manager', 'Barguna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994473', 'prodibkumarbgd@gmail.com', '2016-02-25', 1, '2026-05-24 18:00:00'),
(1449, '307335', 'Md. Ishaque Ali', 'Area Sales Manager', 'Rajshahi-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994408', 'mdrajuahamed488@gmail.com', '2016-02-22', 1, '2026-05-24 18:00:00'),
(1450, '307336', 'Netiy Chandra Chakraborty', 'Senior Medical Promotion Officer', 'Epic-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994445', 'chakraborty10netal@gmail.com', '2016-02-23', 1, '2026-05-24 18:00:00'),
(1451, '307339', 'Md. Nazimul Haque', 'Senior Medical Promotion Officer', 'Rangpur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994424', 'nazmul.uni786@gmail.com', '2016-04-13', 1, '2026-05-24 18:00:00'),
(1452, '307341', 'Md. Abdur Razzaque', 'Senior Medical Promotion Officer', 'Moulvibazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994461', 'abdurrazzaque9222@gmail.com', '2016-02-22', 1, '2026-05-24 18:00:00'),
(1453, '307348', 'Mohammad Emran Chowdhury', 'Area Sales Manager', 'CIMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993957', 'emranchy01@gmail.com', '2016-02-22', 1, '2026-05-24 18:00:00'),
(1454, '307350', 'Md. Abdur Rouf', 'Senior Medical Promotion Officer', 'Labaid-1/Elephant Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994340', 'abdurrouf2191982@gmail.com', '2016-02-22', 1, '2026-05-24 18:00:00'),
(1455, '307351', 'Md. Razaul Karim', 'Assistant Sales Manager', 'Uttara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994418', 'razaulunigroup2@gmail.com', '2016-02-22', 1, '2026-05-24 18:00:00'),
(1456, '307361', 'Hari Pada Roy', 'Senior Medical Promotion Officer', 'Sherpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994411', 'baburoy.djp@gmail.com', '2016-02-08', 1, '2026-05-24 18:00:00'),
(1457, '307365', 'Md. Rabiul Alam', 'Senior Medical Promotion Officer', 'Pabna-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993901', 'raishamoni258@gmail.com', '2016-03-13', 1, '2026-05-24 18:00:00'),
(1458, '307366', 'Md. Mehedi Hasan', 'Senior Medical Promotion Officer', 'Nabiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994396', 'mehedi967734g@gmail.com', '2016-03-13', 1, '2026-05-24 18:00:00'),
(1459, '307376', 'Md. Saddam Hossain', 'Senior Medical Promotion Officer', 'Saturia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994450', 'mdsaddamhossain0111992@gmail.com', '2016-03-13', 1, '2026-05-24 18:00:00'),
(1460, '307387', 'Md. Feroz Hossain', 'Senior Medical Promotion Officer', 'Moulvibazar-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994469', 'abdullahferoz535@gmail.com', '2016-04-05', 1, '2026-05-24 18:00:00'),
(1461, '307391', 'Md. Moslem Uddin ', 'Senior Medical Promotion Officer', 'Kushtia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994328', 'moslemuddin401@gmail.com', '2016-04-01', 1, '2026-05-24 18:00:00'),
(1462, '307396', 'Mahmudul Hasan', 'Senior Area Sales Manager', 'B.Baria-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994430', 'mahmud.uht@gmail.com', '2016-04-04', 1, '2026-05-24 18:00:00'),
(1463, '307402', 'Md. Nagirul Islam', 'Senior Medical Promotion Officer', 'Lakshmipur-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994429', 'nazirulislam1987bi@gmail.com', '2016-04-10', 1, '2026-05-24 18:00:00'),
(1464, '307408', 'Md. Kaosar Alom', 'Senior Medical Promotion Officer', 'Rajshahi-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993905', 'kaosaralom01737@gmail.com', '2016-04-26', 1, '2026-05-24 18:00:00'),
(1465, '307415', 'Md. Samsuzzoha Kazi', 'Area Sales Manager', 'Gazipur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994457', 'mdsamsuzzohakazi@gmail.com', '2016-04-23', 1, '2026-05-24 18:00:00'),
(1466, '307420', 'Sheikh Masud Ali', 'Senior Training Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994458', 'masud3153@gmail.com', '2016-04-30', 1, '2026-05-24 18:00:00'),
(1467, '307421', 'Md. Mijanur Rahman', 'Senior Medical Promotion Officer', 'Bogra-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994482', 'mijan.rasu@gmail.com', '2016-05-02', 1, '2026-05-24 18:00:00'),
(1468, '307424', 'Md. Yousuf Ali', 'Area Sales Manager', 'Comilla-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994451', 'yousufali12061991@gmail.com', '2016-04-30', 1, '2026-05-24 18:00:00'),
(1469, '307426', 'Rabiul Islam', 'Senior Medical Promotion Officer', 'Companiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994496', 'rabio9450945@gmail.com', '2016-05-01', 1, '2026-05-24 18:00:00'),
(1470, '307429', 'Md. Nasim Sarkar', 'Area Sales Manager', 'B.Baria\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994446', 'nasimiubat@gmail.com', '2016-04-30', 1, '2026-05-24 18:00:00'),
(1471, '307437', 'Md. Alamgir Hossain', 'Senior Area Sales Manager', 'Coxsbazar-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994567', 'alamgirhossain.unigroup@gmail.com', '2016-05-15', 1, '2026-05-24 18:00:00'),
(1472, '307449', 'Md. Ramjan Ali', 'Senior Medical Promotion Officer', 'Mirpur-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994481', 'ramjan.uhr@gmail.com', '2016-05-25', 1, '2026-05-24 18:00:00'),
(1473, '307450', 'Md. Mosharraf Hossen', 'Senior Medical Promotion Officer', 'Central-1/Module Hosp', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993954', 'mdmosharraf752@gmail.com', '2016-05-28', 1, '2026-05-24 18:00:00'),
(1474, '307451', 'Md. Rabiul Awal', 'Senior Medical Promotion Officer', 'Rangpur-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994207', 'rabiul.unigroup554@gmail.com', '2016-05-28', 1, '2026-05-24 18:00:00'),
(1475, '307459', 'Mohammad Ruhul Amin', 'Senior Medical Promotion Officer', 'Gazipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994498', 'ruhulramisha14@gmail.com', '2016-06-21', 1, '2026-05-24 18:00:00'),
(1476, '307468', 'Md. Mozammal Hoque', 'Senior Medical Promotion Officer', 'Agrabad-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994441', 'mozammalhoque786@gmail.com', '2016-07-09', 1, '2026-05-24 18:00:00'),
(1477, '307471', 'Md. Delwar Hossain', 'Area Sales Manager', 'Beanibazar\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994237', 'mddelular9010@gmail.com', '2016-07-09', 1, '2026-05-24 18:00:00'),
(1478, '307476', 'Muzahidul Hasan ', 'Senior Medical Promotion Officer', 'Mymensingh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993394', 'muzahidulhassan1987@gmail.com', '2016-07-09', 1, '2026-05-24 18:00:00'),
(1479, '307481', 'Mowdud Ahmed', 'Senior Area Sales Manager', 'Mymensingh-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994553', 'mowdudunigroup@gmail.com', '2016-07-17', 1, '2026-05-24 18:00:00'),
(1480, '307482', 'Md. Sohidul Islam', 'Senior Medical Promotion Officer', 'Joypurhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994538', 'sohid.che@gmail.com', '2016-07-20', 1, '2026-05-24 18:00:00'),
(1481, '307488', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Dagonbhuiyan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994554', 'mdmasud.rana3373@gmail.com', '2016-08-29', 1, '2026-05-24 18:00:00'),
(1482, '307493', 'Md. Abul Hayat', 'Senior Area Sales Manager', 'Sugondha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993631', 'abdulhayatunigroup@gmail.com', '2016-08-28', 1, '2026-05-24 18:00:00'),
(1483, '307500', 'Ziaur Rahman', 'Area Sales Manager', 'Kishoreganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994275', 'ziaur.0610@gmail.com', '2016-08-27', 1, '2026-05-24 18:00:00'),
(1484, '307503', 'Md. Monayem Hosain', 'Senior Medical Promotion Officer', 'Bogra-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994546', 'mdmonayem933@gmail.com', '2016-08-24', 1, '2026-05-24 18:00:00'),
(1485, '307512', 'Alamgir Biswas', 'Senior Medical Promotion Officer', 'Sunamganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993165', 'alamgirbiswas3@gmail.com', '2016-08-27', 1, '2026-05-24 18:00:00'),
(1486, '307513', 'Muhammad Imran Hossain ', 'Senior Medical Promotion Officer', 'Suagazi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994513', 'imrannazirarchive@gmail.com', '2016-08-27', 1, '2026-05-24 18:00:00'),
(1487, '307522', 'Md. Hafizul Islam', 'Senior Medical Promotion Officer', 'Comilla-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994392', 'hafiz.unigroup.gf@gmail.com', '2016-08-23', 1, '2026-05-24 18:00:00'),
(1488, '307535', 'Md. Akramul kabir', 'Senior Medical Promotion Officer', 'Pabna-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993611', 'akramulkabir1984@gmail.com', '2016-09-18', 1, '2026-05-24 18:00:00'),
(1489, '307539', 'Md. Nazmul Haque', 'Senior Area Sales Manager', 'Chuadanga', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993846', 'nazmuluhp@gmail.com', '2016-10-08', 1, '2026-05-24 18:00:00'),
(1490, '307540', 'Md. Shahinur Rahman', 'Area Sales Manager', 'Moulvibazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994720', 'mdshahinurrahman894@gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(1491, '307541', 'Md. Mokter Alam', 'Senior Medical Promotion Officer', 'Gopalganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994294', 'mokter.unigoup@gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(1492, '307543', 'Md. Yousup Nabi', 'Senior Medical Promotion Officer', 'CMCH-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994360', 'yousupnabi1992@gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(1493, '307553', 'Md. Abul Hassan ', 'Senior Medical Promotion Officer', 'Bogra-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994835', 'mdhassanmahmood272@gmail.com', '2016-11-09', 1, '2026-05-24 18:00:00'),
(1494, '307554', 'Md. Abdul Hannan', 'Senior Medical Promotion Officer', 'Mirpur-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994542', 'hannansarker561987@gmail.com', '2016-11-01', 1, '2026-05-24 18:00:00'),
(1495, '307561', 'Main Uddin', 'Senior Medical Promotion Officer', 'Chawkbazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993884', 'umain6824@gmail.com', '2016-11-02', 1, '2026-05-24 18:00:00'),
(1496, '307571', 'Rajib Debnath', 'Senior Area Sales Manager', 'Comilla City-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993040', 'rajibinc80@gmail.com', '2016-11-10', 1, '2026-05-24 18:00:00'),
(1497, '307574', 'Md. Rafikul Islam', 'Senior Medical Promotion Officer', 'BSMMU/Somorita Hosp', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994564', 'rafikunigroup@gmail.com', '2016-12-05', 1, '2026-05-24 18:00:00'),
(1498, '307575', 'Md. Al Amin Miah', 'Senior Medical Promotion Officer', 'DMCH-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994563', 'alamin896472@gmail.com', '2016-11-24', 1, '2026-05-24 18:00:00'),
(1499, '307578', 'Md. Arshad Ali', 'Senior Medical Promotion Officer', 'MuMC/Mugda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994322', 'mdarshadali7722@gmail.com', '2016-12-03', 1, '2026-05-24 18:00:00'),
(1500, '307580', 'Md. Suzan Mahmud', 'Senior Medical Promotion Officer', 'IBN SINA-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994580', 'suzonmahmud6025@gmail.com', '2016-12-04', 1, '2026-05-24 18:00:00'),
(1501, '307581', 'Md. Taslim Uddin', 'Senior Medical Promotion Officer', 'Banani-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994581', 'taslim12389@gmail.com', '2016-12-04', 1, '2026-05-24 18:00:00'),
(1502, '307583', 'Md. Amdad Hossain', 'Senior Medical Promotion Officer', 'DMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994570', 'hossainamdad96@gmail.com', '2016-12-03', 1, '2026-05-24 18:00:00'),
(1503, '307587', 'Mohammad Ismail Hossain', 'Area Sales Manager', 'Fatikchari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994095', 'ismailunigroup11@gmail.com', '2016-11-06', 1, '2026-05-24 18:00:00'),
(1504, '307590', 'Md. Anowar Hossian', 'Senior Medical Promotion Officer', 'Godagari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994568', 'anowarunigroup@gmail.com', '2016-12-06', 1, '2026-05-24 18:00:00'),
(1505, '307603', 'Md. Ferdous Rahman', 'Senior Medical Promotion Officer', 'SOMCH-2B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994585', 'rferdous433@gmail.com', '2016-12-21', 1, '2026-05-24 18:00:00'),
(1506, '307604', 'Forhad Ali', 'Senior Medical Promotion Officer', 'Ranirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994661', 'farhadali59601@gmail.com', '2016-12-20', 1, '2026-05-24 18:00:00'),
(1507, '307605', 'Suzal Kumar Kundu', 'Senior Medical Promotion Officer', 'Pabna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994659', 'suzalkumarkundu@gmail.com', '2016-12-24', 1, '2026-05-24 18:00:00'),
(1508, '307606', 'Md. Shahinur Rahman', 'Senior Medical Promotion Officer', 'Mitford/Jatrabari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994582', 'shahinur19rahman86@gmail.com', '2016-12-21', 1, '2026-05-24 18:00:00'),
(1509, '307612', 'Md. Sahidul Islam', 'Senior Medical Promotion Officer', 'Mirpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994647', 'sahidulislambd11@gmail.com', '2017-01-15', 1, '2026-05-24 18:00:00'),
(1510, '307614', 'Wahiduzzaman', 'Area Sales Manager', 'Dinajpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994623', 'wahidnatore30@gmail.com', '2017-01-03', 1, '2026-05-24 18:00:00'),
(1511, '307617', 'Md. Sohrab Hossain', 'Senior Medical Promotion Officer', 'Fatikchhari-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994591', 'ahsan01723237892@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1512, '307618', 'Md. Atabur Rahman', 'Senior Medical Promotion Officer', 'Noapara-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994602', 'kmbulbul77@gmail.com', '2017-01-05', 1, '2026-05-24 18:00:00'),
(1513, '307622', 'Sheikh Asaduzzaman', 'Senior Medical Promotion Officer', 'Pirojpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994676', 'ajamansheikh5@gmail.com', '2017-01-05', 1, '2026-05-24 18:00:00'),
(1514, '307623', 'Md. Sayful Islam', 'Senior Medical Promotion Officer', 'Tangail-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993612', 'sayfuluni17@gmail.com', '2017-01-06', 1, '2026-05-24 18:00:00'),
(1515, '307624', 'Md. Rezaul Karim ', 'Senior Medical Promotion Officer', 'Thakurgaon-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994626', 'rkjewelx@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1516, '307625', 'Md. Rasel Rana', 'Senior Medical Promotion Officer', 'Uttara-B6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994594', 'raselr923@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1517, '307626', 'Rezaul Karim ', 'Senior Medical Promotion Officer', 'Dinajpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994627', 'md.rezaulkarim.hridoy1990@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1518, '307627', 'Md. Sanaulla Hossan ', 'Senior Medical Promotion Officer', 'Kotwali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993664', 'mshossan1989@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1519, '307628', 'Md. Farhad Hossen ', 'Senior Medical Promotion Officer', 'Epic-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993667', 'farhadhossain9321@gmail.com', '2017-01-09', 1, '2026-05-24 18:00:00'),
(1520, '307629', 'Ripon Kumer Shen', 'Senior Area Sales Manager', 'Comilla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994607', 'riponshen1122@gmail.com', '2017-01-07', 1, '2026-05-24 18:00:00'),
(1521, '307632', 'Md. Ali Akbar ', 'Senior Medical Promotion Officer', 'Rajshahi-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994624', 'aliakbar307632@gmail.com', '2017-01-14', 1, '2026-05-24 18:00:00'),
(1522, '307635', 'Satinath Chandra Roy', 'Senior Medical Promotion Officer', 'Sujanagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994656', 'satirathroy35@gmail.com', '2017-01-14', 1, '2026-05-24 18:00:00'),
(1523, '307636', 'Md. Nazmul Huda', 'Senior Medical Promotion Officer', 'Kurmitola-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994604', 'nazmulhuda2277@gmail.com', '2017-01-14', 1, '2026-05-24 18:00:00'),
(1524, '307646', 'Mohammad Ali', 'Senior Medical Promotion Officer', 'Panchagarh-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994657', 'mali79288@gmail.com', '2017-01-30', 1, '2026-05-24 18:00:00'),
(1525, '307647', 'Md. Zakir Hossain', 'Senior Medical Promotion Officer', 'United Hospital-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994610', 'hzakir12389@gmail.com', '2017-01-20', 1, '2026-05-24 18:00:00'),
(1526, '307649', 'Md. Abdul Motaleb ', 'Senior Medical Promotion Officer', 'Mithapukur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993746', 'abdulmotaleb323@gmail.com', '2017-01-29', 1, '2026-05-24 18:00:00'),
(1527, '307651', 'Md. Asad Ali', 'Area Sales Manager', 'Sylhet-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994687', 'asad7090@gmail.com', '2017-01-28', 1, '2026-05-24 18:00:00'),
(1528, '307653', 'Md. Nurul Islam', 'Senior Medical Promotion Officer', 'BMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993583', 'bcnurulislam09@gmail.com', '2017-01-28', 1, '2026-05-24 18:00:00'),
(1529, '307657', 'Rahmat Ali', 'Senior Medical Promotion Officer', 'Madhupur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994637', 'mohar2000@gmail.com', '2017-01-28', 1, '2026-05-24 18:00:00'),
(1530, '307659', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Dinajpur-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994615', 'masudrm91@gmail.com', '2017-02-06', 1, '2026-05-24 18:00:00'),
(1531, '307661', 'Md. Jillur Rahman', 'Senior Area Sales Manager', 'IBN Sina', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994643', 'jillur.unigroup@gmail.com', '2017-02-08', 1, '2026-05-24 18:00:00'),
(1532, '307664', 'Md. Motaleb Ali', 'Senior Medical Promotion Officer', 'Akhaura-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994675', 'motalebali5320@gmail.com', '2017-02-11', 1, '2026-05-24 18:00:00'),
(1533, '307671', 'Md. Raisul Islam Chowdhury', 'Area Sales Manager', 'Rangpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994630', 'raisulislamripon786@gmail.com', '2017-02-16', 1, '2026-05-24 18:00:00'),
(1534, '307676', 'Md. Kamruzzaman', 'Senior Area Sales Manager', 'Rangpur-C\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994629', 'kamruzzamankamrul98@gmail.com', '2017-02-16', 1, '2026-05-24 18:00:00'),
(1535, '307680', 'Md. Shaharul Islam', 'Senior Medical Promotion Officer', 'Ashulia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993864', 'shaharulislam662@gmail.com', '2017-02-15', 1, '2026-05-24 18:00:00'),
(1536, '307681', 'Md. Rabiul Islam', 'Senior Medical Promotion Officer', 'Chandpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994460', 'rabiulislam3111989@gmail.com', '2017-02-14', 1, '2026-05-24 18:00:00'),
(1537, '307683', 'Md. Shabuz Mia', 'Senior Medical Promotion Officer', 'Birampur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994651', 'shitaj56@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1538, '307684', 'Md. Abubakar Siddique', 'Senior Area Sales Manager', 'Rangpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994645', 'siddique.unigroup@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1539, '307685', 'M. Maruf Billah ', 'Senior Medical Promotion Officer', 'Bagachra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994634', 'mbillahunimed@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1540, '307686', 'Md. Amirul Islam', 'Senior Medical Promotion Officer', 'Narayanganj-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994642', 'amirulunimed@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1541, '307690', 'Md. Tobibar Rahman', 'Senior Medical Promotion Officer', 'Barisal-D4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994502', 'tobibarrahmanuhp88@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1542, '307692', 'Dulal Chandra Barmon', 'Senior Medical Promotion Officer', 'Dhaka Dakshin-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994649', 'dulalroy09091989@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(1543, '307693', 'Md. Maydul Islam', 'Senior Medical Promotion Officer', 'Kurigram-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994705', 'maydulunigroup@gmail.com', '2017-02-15', 1, '2026-05-24 18:00:00'),
(1544, '307694', 'Supan Chandra Sarkar', 'Senior Medical Promotion Officer', 'Gazipur-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994674', 'supand71@gmail.com', '2017-03-14', 1, '2026-05-24 18:00:00'),
(1545, '307696', 'Md. Shahadat Fakir ', 'Senior Medical Promotion Officer', 'NICVD-A6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994667', 'shahadatstatis87@gmail.com', '2017-03-18', 1, '2026-05-24 18:00:00'),
(1546, '307699', 'Md. Robiul Islam', 'Senior Medical Promotion Officer', 'Court Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994737', 'robiulislam.ent743@gmail.com', '2017-03-06', 1, '2026-05-24 18:00:00'),
(1547, '307700', 'Md. Saklain Hossain', 'Senior Medical Promotion Officer', 'Shariatpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994652', 'saklainhossain@gamil.com', '2017-03-04', 1, '2026-05-24 18:00:00'),
(1548, '307701', 'Mohammad Azizul Islam', 'Senior Medical Promotion Officer', 'BSMMU-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993467', 'azizulunimed@gmail.com', '2017-03-18', 1, '2026-05-24 18:00:00'),
(1549, '307702', 'Anupam Bhadra', 'Senior Medical Promotion Officer', 'Pirojpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994718', 'banikanu07@gmail.com', '2017-03-18', 1, '2026-05-24 18:00:00'),
(1550, '307705', 'Md. Jahid Hasan', 'Senior Medical Promotion Officer', 'Doulatpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994484', 'mdjahid4957@gmail.com', '2017-03-12', 1, '2026-05-24 18:00:00'),
(1551, '307708', 'Tapan Kumar Biswas', 'Senior Medical Promotion Officer', 'Maijdee-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993558', 'tapanbiswas723@gmail.com', '2017-03-12', 1, '2026-05-24 18:00:00'),
(1552, '307709', 'S. M. Faruk Hossain', 'Senior Medical Promotion Officer', 'Gaibandha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993920', 'smfarukahc@gmail.com', '2017-03-18', 1, '2026-05-24 18:00:00'),
(1553, '307714', 'Aftabuzzaman', 'Area Sales Manager', 'Netrokona-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993775', 'aftabzaman905@gmail.com', '2017-03-26', 1, '2026-05-24 18:00:00'),
(1554, '307721', 'Morshadul Anam', 'Area Sales Manager', 'Sylhet-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994688', 'anammorshad1991@gmail.com', '2017-04-04', 1, '2026-05-24 18:00:00'),
(1555, '307723', 'Md. Noorujjaman', 'Area Sales Manager', 'Nageshwari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994686', 'nooruhr1984@gmail.com', '2017-04-28', 1, '2026-05-24 18:00:00'),
(1556, '307725', 'Azim Uddin ', 'Senior Medical Promotion Officer', 'ShSMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994684', 'azimuddin472@gmail.com', '2017-04-20', 1, '2026-05-24 18:00:00'),
(1557, '307726', 'Prokash Biswas', 'Senior Medical Promotion Officer', 'Rupnagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994611', 'prokashbiswas047@gmail.com', '2017-04-20', 1, '2026-05-24 18:00:00'),
(1558, '307731', 'Dhananjoy Mozumder', 'Senior Medical Promotion Officer', 'Sadarghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994685', 'dhananjoy10ctg@gmail.com', '2017-04-09', 1, '2026-05-24 18:00:00'),
(1559, '307739', 'Md. Khairul Islam', 'Senior Medical Promotion Officer', 'Comilla City-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993649', 'ronybiswma@gmail.com', '2017-05-11', 1, '2026-05-24 18:00:00'),
(1560, '307741', 'Sena Mia', 'Senior Area Sales Manager', 'Faridpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994695', 'senamia.unigroup@gmail.com', '2017-05-09', 1, '2026-05-24 18:00:00'),
(1561, '307745', 'S M Zahidul Haque', 'Deputy Sales Manager', 'Barisal South', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994692', 'zahidul.haque@unigroup-bd.com', '2017-05-06', 1, '2026-05-24 18:00:00'),
(1562, '307746', 'Leton Chandra Roy', 'Senior Medical Promotion Officer', 'Mawna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994334', 'letonroy90@gmail.com', '2017-06-09', 1, '2026-05-24 18:00:00'),
(1563, '307747', 'Md. Mistar Mia', 'Senior Medical Promotion Officer', 'Lakshmipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993813', 'mistarmd799@gmail.com', '2017-05-02', 1, '2026-05-24 18:00:00'),
(1564, '307755', 'Md. Zuel Islam', 'Senior Medical Promotion Officer', 'Nazirhat-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994710', 'jewelrana127@gamil.com', '2017-06-05', 1, '2026-05-24 18:00:00'),
(1565, '307761', 'Md. Osman Goni', 'Senior Medical Promotion Officer', 'Habiganj-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994096', 'osman101290@gmail.com', '2017-06-07', 1, '2026-05-24 18:00:00'),
(1566, '307762', 'Md. Mazedul Islam', 'Senior Medical Promotion Officer', 'Shyamoli', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994339', 'joy89bd@gmail.com', '2017-06-07', 1, '2026-05-24 18:00:00'),
(1567, '307763', 'Md. Lemon Islam Khan', 'Senior Medical Promotion Officer', 'Katiadi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994437', 'lemon18khan@gmail.com', '2017-06-06', 1, '2026-05-24 18:00:00'),
(1568, '307764', 'Md. Sakib Hossen ', 'Senior Medical Promotion Officer', 'Pabna-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994076', 'sakibhossen981@gmail.com', '2017-06-06', 1, '2026-05-24 18:00:00'),
(1569, '307770', 'Md. Sariful Islam', 'Senior Medical Promotion Officer', 'Jhenaidah-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993716', 'mdsarifulislamsariful95117@gmail.com', '2017-05-30', 1, '2026-05-24 18:00:00'),
(1570, '307771', 'Md. Abdus Sattar', 'Senior Medical Promotion Officer', 'Narayanganj-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994621', 'sattarabdus2005@gmail.com', '2017-05-27', 1, '2026-05-24 18:00:00'),
(1571, '307784', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Rangpur-16', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994713', 'amirulbashanta@gmail.com', '2017-06-29', 1, '2026-05-24 18:00:00'),
(1572, '307789', 'Md. Shahnur Rahman', 'Assistant Sales Manager', 'Sylhet', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993148', 'shahnur.rahman@unigroup-bd.com', '2017-07-28', 1, '2026-05-24 18:00:00'),
(1573, '307791', 'Md. Atikul Islam', 'Area Sales Manager', 'Sunamganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994188', 'atikhasan16885@gmail.com', '2017-08-17', 1, '2026-05-24 18:00:00'),
(1574, '307792', 'Md. Jasim Uddin', 'Senior Medical Promotion Officer', 'UAMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993916', 'mduddin515@gmail.com', '2017-08-16', 1, '2026-05-24 18:00:00'),
(1575, '307793', 'Md. Habibur Rahman', 'Senior Medical Promotion Officer', 'Phulbari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993886', 'habiburuht@gmail.com', '2017-08-16', 1, '2026-05-24 18:00:00'),
(1576, '307795', 'Shahikul Islam', 'Senior Medical Promotion Officer', 'BMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993163', 'sihasu88@gmail.com', '2017-08-14', 1, '2026-05-24 18:00:00'),
(1577, '307797', 'Sultan Ahammed', 'Senior Medical Promotion Officer', 'Mymensingh-D1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993734', 'sultanahammed19777@gmail.com', '2017-08-16', 1, '2026-05-24 18:00:00'),
(1578, '307803', 'Bodrul Alom', 'Senior Medical Promotion Officer', 'Beanibazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994550', 'alombodrul150@gmail.com', '2017-08-12', 1, '2026-05-24 18:00:00'),
(1579, '307806', 'Polash Chandra Roy', 'Senior Medical Promotion Officer', 'Manikganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993577', 'palashray220@gmail.com', '2017-08-13', 1, '2026-05-24 18:00:00'),
(1580, '307809', 'Shereekanta', 'Senior Medical Promotion Officer', 'Jhikargacha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994485', 'santoibaru@gmail.com', '2017-09-10', 1, '2026-05-24 18:00:00'),
(1581, '307813', 'Md. Robiul Alam', 'Senior Medical Promotion Officer', 'Rangpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994729', 'unigrouprobiul@gmail.com', '2017-09-09', 1, '2026-05-24 18:00:00'),
(1582, '307816', 'Michiel Ratan Mardi', 'Senior Medical Promotion Officer', 'Sapahar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994558', 'ratanmardi@gmail.com', '2017-09-05', 1, '2026-05-24 18:00:00'),
(1583, '307820', 'Kamal Kanta', 'Senior Medical Promotion Officer', 'BIRDEM-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993175', 'kamaluni1996@gmail.com', '2017-09-04', 1, '2026-05-24 18:00:00'),
(1584, '307821', 'Md. Sahin Alom', 'Senior Medical Promotion Officer', 'Shahparan-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994321', 'sahin017531402@gmail.com', '2017-09-07', 1, '2026-05-24 18:00:00'),
(1585, '307824', 'Md. Ful Chand', 'Medical Promotion Officer', 'Narsingdi-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994739', 'fulchandahmed747@gmail.com', '2017-09-04', 1, '2026-05-24 18:00:00'),
(1586, '307828', 'Mohammad Mahabub Alam', 'Senior Medical Promotion Officer', 'Comilla City-18', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994907', 'alam2869@gmail.com', '2017-09-16', 1, '2026-05-24 18:00:00'),
(1587, '307831', 'Md. Fazibur Rahman', 'Senior Medical Promotion Officer', 'Munshiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994746', 'fazib.rahman1@gmail.com', '2017-09-16', 1, '2026-05-24 18:00:00'),
(1588, '307832', 'Md. Rezaul Karim', 'Senior Medical Promotion Officer', 'Khulna-D1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994851', 'rezaul363564@gmail.com', '2017-09-18', 1, '2026-05-24 18:00:00'),
(1589, '307834', 'Md. Abdul Aziz', 'Senior Medical Promotion Officer', 'SOMCH-2A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994716', 'aziz691774@gmail.com', '2017-09-14', 1, '2026-05-24 18:00:00'),
(1590, '307836', 'Md. Rezaul Karim', 'Senior Medical Promotion Officer', 'Mandari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994740', 'rezaul1988@gmail.com', '2017-09-19', 1, '2026-05-24 18:00:00'),
(1591, '307838', 'Md. Hafizur Rahman', 'Senior Medical Promotion Officer', 'Mymensingh-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994750', 'hafizurrahmanh5@gmail.com', '2017-09-16', 1, '2026-05-24 18:00:00'),
(1592, '307839', 'Md. Imran Ali', 'Senior Medical Promotion Officer', 'Netrokona-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994808', 'miali01234@gmail.com', '2017-09-15', 1, '2026-05-24 18:00:00'),
(1593, '307840', 'Md. Woliul Islam', 'Senior Medical Promotion Officer', 'Mirpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994751', 'woliulislam48@gmail.com', '2017-09-16', 1, '2026-05-24 18:00:00'),
(1594, '307841', 'Md. Israil Hossen', 'Senior Medical Promotion Officer', 'Bhatiari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994902', 'israilhossen1990@gmail.com', '2017-09-14', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(1595, '307842', 'Hasan', 'Senior Medical Promotion Officer', 'Comilla-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994757', 'hasanrajshahi001@gmail.com', '2017-09-16', 1, '2026-05-24 18:00:00'),
(1596, '307843', 'Md. Abdur Rahman', 'Senior Medical Promotion Officer', 'Bagmara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994753', 'abdurrahman0000088888@gmail.com', '2017-10-02', 1, '2026-05-24 18:00:00'),
(1597, '307855', 'Md. Siddiqul Islam', 'Senior Medical Promotion Officer', 'Rajshahi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994797', 'siddiqulislams01@gmail.com', '2017-10-03', 1, '2026-05-24 18:00:00'),
(1598, '307856', 'Md. Fazlar Rahman', 'Senior Medical Promotion Officer', 'Rangpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994755', 'mdfazlarrahman7314@gmail.com', '2017-10-02', 1, '2026-05-24 18:00:00'),
(1599, '307859', 'Md. Ashadul Islam', 'Senior Medical Promotion Officer', 'Phulbari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994837', 'asadsheikh934@gmail.com', '2017-10-21', 1, '2026-05-24 18:00:00'),
(1600, '307860', 'Md. Abu Ahsan', 'Area Sales Manager', 'Manikganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994908', 'rajon4528@gmail.com', '2017-10-21', 1, '2026-05-24 18:00:00'),
(1601, '307861', 'Mahfuz Akter', 'Senior Medical Promotion Officer', 'Homna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994909', 'mahfuzakter672@gmail.com', '2017-10-19', 1, '2026-05-24 18:00:00'),
(1602, '307866', 'Md. Sanoyar Hossain Metu', 'Area Sales Manager', 'Sitakunda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994796', 'mithuprodhan88@gmail.com', '2017-10-17', 1, '2026-05-24 18:00:00'),
(1603, '307869', 'Md. Reyazul Islam', 'Senior Medical Promotion Officer', 'Bhurungamari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994764', 'reyajulislam281090@gmail.com', '2017-10-22', 1, '2026-05-24 18:00:00'),
(1604, '307874', 'Md. Shahazahan Ali', 'Senior Medical Promotion Officer', 'Haimchar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994914', 'shahazahanali70@gmail.com', '2017-10-19', 1, '2026-05-24 18:00:00'),
(1605, '307876', 'Abdur Rahman', 'Senior Medical Promotion Officer', 'Fultola-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994852', 'abdurrahman53a3@gmail.com', '2017-10-24', 1, '2026-05-24 18:00:00'),
(1606, '307881', 'Md. Hasan Mahmud', 'Senior Medical Promotion Officer', 'Kushtia-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994763', 'mahmudhasanbu930@gmail.com', '2017-10-18', 1, '2026-05-24 18:00:00'),
(1607, '307896', 'Md. Abul Kalam', 'Senior Medical Promotion Officer', 'Dohar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994769', 'kalam251989@gmail.com', '2017-10-17', 1, '2026-05-24 18:00:00'),
(1608, '307897', 'Md. Al Mamun', 'Senior Medical Promotion Officer', 'Mymensingh-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994694', 'mamun.unimed@gmail.com', '2017-10-28', 1, '2026-05-24 18:00:00'),
(1609, '307898', 'Md. Selim Sarker', 'Senior Medical Promotion Officer', 'Bhaluka-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994788', 'selim630676@gmail.com', '2017-10-25', 1, '2026-05-24 18:00:00'),
(1610, '307899', 'Md. Serajul Islam', 'Senior Medical Promotion Officer', 'Kachua-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994831', 'serajulislam3950@gmail.com', '2017-10-10', 1, '2026-05-24 18:00:00'),
(1611, '307909', 'Md. Abu Sayed Saju', 'Senior Medical Promotion Officer', 'Kallyanpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993770', 'abusayed307909@gmail.com', '2017-10-25', 1, '2026-05-24 18:00:00'),
(1612, '307911', 'Dibakar Kumar Dey', 'Senior Medical Promotion Officer', 'Jhikargacha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993173', 'deydibakar365@gmail.com', '2017-10-26', 1, '2026-05-24 18:00:00'),
(1613, '307913', 'Md. Abdul Al Momin', 'Senior Medical Promotion Officer', 'BSMMU/Green Super Market', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994242', 'abdulalmomin96@gmail.com', '2017-10-25', 1, '2026-05-24 18:00:00'),
(1614, '307914', 'Md. Mahbub Alam', 'Senior Medical Promotion Officer', 'AhMMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994776', 'mahabubalam4147@gmail.com', '2017-10-25', 1, '2026-05-24 18:00:00'),
(1615, '307921', 'Mozaharul Islam Chy', 'Senior Medical Promotion Officer', 'Pahartali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994813', 'chymozahad@gmail.com', '2017-11-09', 1, '2026-05-24 18:00:00'),
(1616, '307922', 'Golam Mostafa', 'Senior Medical Promotion Officer', 'Munshir Hat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994849', 'golammostafa2777@gmail.com', '2017-11-09', 1, '2026-05-24 18:00:00'),
(1617, '307923', 'Md. Ahasan Habib', 'Senior Medical Promotion Officer', 'BSMMU/Modern-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994783', 'ahasanhabib307923@gmail.com', '2017-11-06', 1, '2026-05-24 18:00:00'),
(1618, '307924', 'Md. Habibur Rahman', 'Senior Medical Promotion Officer', 'Madaripur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994823', 'adv.habib1042@gmail.com', '2017-11-12', 1, '2026-05-24 18:00:00'),
(1619, '307932', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Sunamganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994873', 'masudlimon482@gmail.com', '2017-11-09', 1, '2026-05-24 18:00:00'),
(1620, '307933', 'Md. Monirul Islam', 'Senior Medical Promotion Officer', 'Dohar/Nababgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994810', 'moniruln400@gmail.com', '2017-11-10', 1, '2026-05-24 18:00:00'),
(1621, '307934', 'S.M. Salauddin Firoz', 'Senior Medical Promotion Officer', 'Jagannathpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994872', 'smsalauddinfiroz@gmail.com', '2017-11-11', 1, '2026-05-24 18:00:00'),
(1622, '307935', 'Omar Faruque', 'Senior Medical Promotion Officer', 'Narsingdi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994876', 'fchowhury868@gmail.com', '2017-11-09', 1, '2026-05-24 18:00:00'),
(1623, '307938', 'Kamrul Islam', 'Senior Medical Promotion Officer', 'Badarkhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994279', 'kamrul4237@gemail.com', '2017-11-14', 1, '2026-05-24 18:00:00'),
(1624, '307946', 'Md. Rafiqul Islam', 'Senior Medical Promotion Officer', 'Meherpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994826', 'rafiq.unimed@gmail.com', '2017-11-13', 1, '2026-05-24 18:00:00'),
(1625, '307947', 'Md. Alamgir Islam', 'Senior Medical Promotion Officer', 'Bagerhat-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994820', 'alamgirislam352@gmail.com', '2017-11-14', 1, '2026-05-24 18:00:00'),
(1626, '307948', 'Md. Anowar Hossen', 'Senior Medical Promotion Officer', 'Noapara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994819', 'mdanowar.5069@gmail.com', '2017-11-14', 1, '2026-05-24 18:00:00'),
(1627, '307951', 'Prodip Chandra Dey', 'Senior Area Sales Manager', 'Chauddagram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994782', 'prodipd90@gmail.com', '2017-11-15', 1, '2026-05-24 18:00:00'),
(1628, '307952', 'Sheikh Al Mamun', 'Senior Area Sales Manager', 'DMCH-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994793', 'skmamun913@gmail.com', '2017-11-13', 1, '2026-05-24 18:00:00'),
(1629, '307953', 'Md. Moinul Islam', 'Senior Medical Promotion Officer', 'Bhairab-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994824', 'mdmoinulislamroni@gmail.com', '2017-11-25', 1, '2026-05-24 18:00:00'),
(1630, '307956', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Tongi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994877', 'arifulislamarif01723@gmail.com', '2017-11-22', 1, '2026-05-24 18:00:00'),
(1631, '307958', 'Md. Mokhlesur Rahman', 'Senior Medical Promotion Officer', 'Kaliakair-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994878', 'moklesurrahman21221@gmail.com', '2017-11-21', 1, '2026-05-24 18:00:00'),
(1632, '307959', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Savar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994830', 'masudrana49bd@gmail.com', '2017-11-21', 1, '2026-05-24 18:00:00'),
(1633, '307977', 'Md. Mikail Sikdar', 'Area Sales Manager', 'Comilla-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994832', 'smdmikail@gmail.com', '2017-11-20', 1, '2026-05-24 18:00:00'),
(1634, '307980', 'Md. Jasad Alam', 'Senior Medical Promotion Officer', 'Meherpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994863', 'mdjasadalam@gmail.com', '2017-12-04', 1, '2026-05-24 18:00:00'),
(1635, '307986', 'Md. Nur Nabi', 'Senior Medical Promotion Officer', 'Jaldhaka', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994928', 'nurnabiuni@gmail.com', '2017-12-04', 1, '2026-05-24 18:00:00'),
(1636, '307987', 'Md. Razu Sheikh', 'Senior Medical Promotion Officer', 'Mitford-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994842', 'rs8972052@gamil.com', '2017-12-03', 1, '2026-05-24 18:00:00'),
(1637, '307989', 'Md. Humaon Kabir', 'Senior Medical Promotion Officer', 'Sherpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994857', 'humaonkabir1987@gmail.com', '2017-12-07', 1, '2026-05-24 18:00:00'),
(1638, '307992', 'Abul Kashem', 'Senior Medical Promotion Officer', 'Rupganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994968', 'abulkasem1924@gmail.com', '2017-12-06', 1, '2026-05-24 18:00:00'),
(1639, '307994', 'Md. Mahbubar Rahman', 'Senior Medical Promotion Officer', 'Metropolitan-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994353', 'mahbub5yn@gmail.com', '2017-12-14', 1, '2026-05-24 18:00:00'),
(1640, '308000', 'Md. Zohurul Haque', 'Senior Medical Promotion Officer', 'Chapainawabganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994754', 'ctg3877@gmail.com', '2017-12-13', 1, '2026-05-24 18:00:00'),
(1641, '308003', 'Md. Arafat Ullah', 'Senior Medical Promotion Officer', 'Barlekha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993630', 'ullaharafat68@gmail', '2018-05-28', 1, '2026-05-24 18:00:00'),
(1642, '308007', 'Md. Kawsar Hossain', 'Senior Medical Promotion Officer', 'Chowmuhani-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993877', 'kawsarunihealth79@gmail.com', '2017-12-09', 1, '2026-05-24 18:00:00'),
(1643, '308010', 'Md. Monjurul Islam', 'Senior Medical Promotion Officer', 'Feni-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994866', 'unidarma66@gmail.com', '2017-12-25', 1, '2026-05-24 18:00:00'),
(1644, '308011', 'Md. Mominul Haq', 'Area Sales Manager', 'Manikganj\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994930', 'mominulhaq709@gmail.com', '2017-12-18', 1, '2026-05-24 18:00:00'),
(1645, '308012', 'Sajib Aich', 'Senior Medical Promotion Officer', 'Banaripara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994933', 'sajibaich8@gmail.com', '2017-12-17', 1, '2026-05-24 18:00:00'),
(1646, '308015', 'Jogodish Chandra Gayali', 'Senior Medical Promotion Officer', 'Faridpur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994689', 'jogodish.chandra1234@gmail.com', '2017-12-17', 1, '2026-05-24 18:00:00'),
(1647, '308017', 'Md. Alamin Bhuiyan', 'Medical Promotion Officer', 'Chandpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994870', 'ronyalamin22@gmail.com', '2017-12-19', 1, '2026-05-24 18:00:00'),
(1648, '308018', 'Md. Abbas Ali', 'Senior Medical Promotion Officer', 'Feni-13', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994859', 'abbasuni2017@gmail.com', '2017-12-17', 1, '2026-05-24 18:00:00'),
(1649, '308020', 'Sonjoy Kumar Biswas', 'Senior Medical Promotion Officer', 'Kishoreganj-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994929', 'sonjoybiswassonjoy73@gmail.com', '2017-12-18', 1, '2026-05-24 18:00:00'),
(1650, '308024', 'Md. Jahangir Alam', 'Senior Medical Promotion Officer', 'Tangail-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994696', 'mdjalam035@gmail.com', '2017-12-25', 1, '2026-05-24 18:00:00'),
(1651, '308030', 'Shafiqul Islam', 'Senior Medical Promotion Officer', 'Jassore-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993847', 'mdsofiqulislamrip52@gmail.com', '2017-12-26', 1, '2026-05-24 18:00:00'),
(1652, '308032', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Manikganj-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994936', 'masudsaeef@gmail.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(1653, '308033', 'Md. Juel Rana', 'Senior Medical Promotion Officer', 'Madhabpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994869', 'mail2juyelrana@gmail.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(1654, '308044', 'Md. Jahingir Alom', 'Senior Medical Promotion Officer', 'Halishahar-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994896', 'zhangiralam210@gmail.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(1655, '308046', 'Md. Rezaul Karim', 'Senior Medical Promotion Officer', 'Bakerganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994934', 'mdrezaulkarim1200@gmail.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(1656, '308047', 'Tamal Barua', 'Senior Medical Promotion Officer', 'Patiya-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994871', 'baruatamal89@gmail.com', '2018-01-03', 1, '2026-05-24 18:00:00'),
(1657, '308050', 'Md. Parvej Akhter', 'Area Sales Manager', 'ShSMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994882', 'parvejakter23565400@gmail.com', '2018-01-10', 1, '2026-05-24 18:00:00'),
(1658, '308051', 'Kazal Parvej', 'Senior Medical Promotion Officer', 'NICVD-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994884', 'kazalparveez@gmail.com', '2018-01-10', 1, '2026-05-24 18:00:00'),
(1659, '308053', 'Md. Monayem Prodhan', 'Senior Medical Promotion Officer', 'NICVD-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994881', 'raselmonayem@gmail.com', '2018-01-11', 1, '2026-05-24 18:00:00'),
(1660, '308057', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'DMCH-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994885', 'saifulislam3829@gmail.com', '2018-01-09', 1, '2026-05-24 18:00:00'),
(1661, '308062', 'Md. Shabuj Mia', 'Area Sales Manager', 'Rangamati', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994919', 'mdshabujmiah90@gmail.com', '2018-01-09', 1, '2026-05-24 18:00:00'),
(1662, '308064', 'Rabiul Islam', 'Senior Medical Promotion Officer', 'Ullapara-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994879', 'rsurabiulislam@gmail.com', '2018-01-15', 1, '2026-05-24 18:00:00'),
(1663, '308075', 'Md. Bashir Ahammed', 'Senior Medical Promotion Officer', 'Sonaimuri-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994920', 'bashirunigroup8@gmail.com', '2018-01-24', 1, '2026-05-24 18:00:00'),
(1664, '308076', 'Emran Khan', 'Area Sales Manager', 'Feni-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994949', 'unimed2114@gmail.com', '2018-01-23', 1, '2026-05-24 18:00:00'),
(1665, '308082', 'Md. Faruk Uzzaman', 'Senior Medical Promotion Officer', 'Mohakhali-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994640', 'a.faruk180085@gmail.com', '2018-01-23', 1, '2026-05-24 18:00:00'),
(1666, '308087', 'Dilip Chandra Pal', 'Senior Medical Promotion Officer', 'Chuadanga-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994415', 'paldilip@gmail.com', '2018-01-22', 1, '2026-05-24 18:00:00'),
(1667, '308089', 'Z.M. Saifuzzaman', 'Senior Medical Promotion Officer', 'Faridpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993578', 'zmsaifuzzaman@gmail.com', '2018-01-21', 1, '2026-05-24 18:00:00'),
(1668, '308090', 'Noor Mohammad', 'Deputy General Manager, Sales', 'South Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994518', 'noor.mohammad@unigroup-bd.com', '2018-01-16', 1, '2026-05-24 18:00:00'),
(1669, '308091', 'Hossain Mohammad Sohel Rana', 'Assistant Sales Manager', 'Bogra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993499', 'sohel.rana@unigroup-bd.com', '2018-01-16', 1, '2026-05-24 18:00:00'),
(1670, '308092', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'MuMC/Basaboo', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994925', 'mnmizan95@gmail.com', '2018-01-29', 1, '2026-05-24 18:00:00'),
(1671, '308094', 'Md. Anwar Hosen', 'Area Sales Manager', 'Gulshan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994655', 'anwarunigroupm22@gmail.com', '2018-01-24', 1, '2026-05-24 18:00:00'),
(1672, '308095', 'Md. Mostafizur Rahman', 'Senior Medical Promotion Officer', 'Mymensingh-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994922', 'mostafizur1202198@gmail.com', '2018-02-05', 1, '2026-05-24 18:00:00'),
(1673, '308096', 'Saddam Hossin', 'Area Sales Manager', 'Uttara-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994941', 'saddam.unigroup@gmail.com', '2018-02-05', 1, '2026-05-24 18:00:00'),
(1674, '308098', 'Md. Shahalom', 'Senior Medical Promotion Officer', 'Rajshahi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994957', 'alombd10@gmail.com', '2018-01-20', 1, '2026-05-24 18:00:00'),
(1675, '308102', 'Areful Islam', 'Senior Medical Promotion Officer', 'Hathazari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994946', 'arefulislam61@gmail.com', '2018-02-04', 1, '2026-05-24 18:00:00'),
(1676, '308109', 'Md. Salauddin', 'Senior Medical Promotion Officer', 'Feni-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994924', 'salauddin86bd@gmail.com', '2018-02-01', 1, '2026-05-24 18:00:00'),
(1677, '308110', 'Najrul Islam', 'Senior Medical Promotion Officer', 'Narayanganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994905', 'najrulislam308110@gmail.com', '2018-02-01', 1, '2026-05-24 18:00:00'),
(1678, '308111', 'Md. Abu Hanif', 'Area Sales Manager', 'Moulvibazar-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994494', 'hanif8387@gmail.com', '2018-02-03', 1, '2026-05-24 18:00:00'),
(1679, '308113', 'Md. Gaulam Rubbani', 'Senior Medical Promotion Officer', 'Barisal-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994962', 'rabbaniunigroup31@gmail.com', '2018-02-03', 1, '2026-05-24 18:00:00'),
(1680, '308114', 'Muhammad Easin Arafat', 'Senior Medical Promotion Officer', 'Fazilpur-Lemua', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994954', 'misty.arafat@gmail.com', '2018-02-14', 1, '2026-05-24 18:00:00'),
(1681, '308119', 'Md. Hasibur Rahman', 'Senior Medical Promotion Officer', 'Kachukhet-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994299', 'rahmanhasibur22@gmail.com', '2018-02-15', 1, '2026-05-24 18:00:00'),
(1682, '308121', 'Liton Ahammed', 'Senior Medical Promotion Officer', 'Vashantek', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994306', 'litontota@gmail.com', '2018-02-18', 1, '2026-05-24 18:00:00'),
(1683, '308122', 'Md. Riajul Islam', 'Area Sales Manager', 'Muradnagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994967', 'khlmdriaj1987@gmail.com', '2018-02-17', 1, '2026-05-24 18:00:00'),
(1684, '308125', 'Md. Monirul Islam', 'Senior Medical Promotion Officer', 'Sreemangal-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993783', 'monirulislam81256@gmail.com', '2018-02-21', 1, '2026-05-24 18:00:00'),
(1685, '308128', 'Sabuj Biswas', 'Senior Medical Promotion Officer', 'Khulna-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994939', 'sabuj.biswass@gmail.com', '2018-02-19', 1, '2026-05-24 18:00:00'),
(1686, '308138', 'Mohammad Saiful Islam', 'Senior Medical Promotion Officer', 'Parkview-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994442', 'saifulfeni3217@gmail.com', '2018-04-03', 1, '2026-05-24 18:00:00'),
(1687, '308142', 'Md. Mehedi Hasan', 'Area Sales Manager', 'Mugda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994180', 'mehedi.glorious@gmail.com', '2018-03-06', 1, '2026-05-24 18:00:00'),
(1688, '308143', 'Md. Shohel Rana', 'Senior Medical Promotion Officer', 'Jamalpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994282', 'mdshohelrana0092@gmail.com', '2018-03-11', 1, '2026-05-24 18:00:00'),
(1689, '308146', 'Md. Sumon Hossain', 'Senior Medical Promotion Officer', 'Bogra-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993903', 'sh355154@gmail.com', '2018-03-06', 1, '2026-05-24 18:00:00'),
(1690, '308147', 'Nasiruddin', 'Senior Medical Promotion Officer', 'Chapainawabganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993970', 'nasiruddin33131@gmail.com', '2018-03-06', 1, '2026-05-24 18:00:00'),
(1691, '308151', 'Mohammad Ali', 'Senior Medical Promotion Officer', 'Savar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994296', 'mohammadali95956280@gmail.com', '2018-03-18', 1, '2026-05-24 18:00:00'),
(1692, '308152', 'Joydeb Chandra Shom', 'Senior Medical Promotion Officer', 'BSMMU-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993363', 'joydebshomo183@gmail.com', '2018-03-13', 1, '2026-05-24 18:00:00'),
(1693, '308153', 'Md. Golap Hossen', 'Senior Medical Promotion Officer', 'Gouripur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994281', 'golaphossen20@gmail.com', '2018-03-19', 1, '2026-05-24 18:00:00'),
(1694, '308154', 'Md. Amirul Islam', 'Senior Medical Promotion Officer', 'Uttara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994205', 'md.amirulislam@gmail.com', '2018-03-15', 1, '2026-05-24 18:00:00'),
(1695, '308156', 'Md. Nazrul Islam', 'Senior Area Sales Manager', 'Noapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994963', 'nazrul82.unigroup@gmail.com', '2018-03-01', 1, '2026-05-24 18:00:00'),
(1696, '308157', 'Md. Shahidul Islam', 'Senior Area Sales Manager', 'Narayanganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994961', 'shahid78.unigroup@gmail.com', '2018-03-10', 1, '2026-05-24 18:00:00'),
(1697, '308158', 'Md. Parvez Ahmmed', 'Assistant Sales Manager', 'Khulna West', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994959', 'parvez.ahmmed@unigroup-bd.com', '2018-03-06', 1, '2026-05-24 18:00:00'),
(1698, '308159', 'Md. Shohel Rana Bhuiyan', 'Assistant Sales Manager', 'Dhaka-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993093', 'shohel.rana@unigroup-bd.com', '2018-03-08', 1, '2026-05-24 18:00:00'),
(1699, '308160', 'Muhammad Sazzad Hussain', 'Senior Area Sales Manager', 'Amirabad', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993526', 'sazzadhossainctg1980@gmail.com', '2018-03-13', 1, '2026-05-24 18:00:00'),
(1700, '308162', 'Md. Mohiul Islam', 'Assistant Manager, Distribution', 'Bogura Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993374', 'mohiul.islam@unigroup-bd.com', '2018-03-15', 1, '2026-05-24 18:00:00'),
(1701, '308163', 'Shib Sankar Roy', 'Senior Area Sales Manager', 'BSMMU', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993123', 'ssankarray.unigroup@gmail.com', '2018-04-11', 1, '2026-05-24 18:00:00'),
(1702, '308171', 'Bayezid Bostami', 'Senior Medical Promotion Officer', 'Chandpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994393', 'bayezid.bostami1988@gmail.com', '2018-03-27', 1, '2026-05-24 18:00:00'),
(1703, '308172', 'Md. Humayun Kabir', 'Senior Medical Promotion Officer', 'Moulvibazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994330', 'humayankabir525@gmail.com', '2018-03-27', 1, '2026-05-24 18:00:00'),
(1704, '308174', 'Md. Firoj Zaman', 'Senior Medical Promotion Officer', 'B.Baria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993532', 'firojzaman1991@gmail.com', '2018-03-27', 1, '2026-05-24 18:00:00'),
(1705, '308176', 'Md. Ataur Rahman PK.', 'Senior Medical Promotion Officer', 'Hathazari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994278', 'sadik39175@gmail.com', '2018-03-27', 1, '2026-05-24 18:00:00'),
(1706, '308177', 'Betu Kumar', 'Senior Medical Promotion Officer', 'Chittagong-A4(Bahaddarhat)', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994326', 'bitukumarmondal05@gmail.com', '2018-03-25', 1, '2026-05-24 18:00:00'),
(1707, '308178', 'Md. Borhen Ullah', 'Senior Medical Promotion Officer', 'CMOCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994406', 'borhanullah187@gmail.com', '2018-04-01', 1, '2026-05-24 18:00:00'),
(1708, '308180', 'Md. Rashadul Islam', 'Senior Medical Promotion Officer', 'CMCH-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994186', 'mdrashad11@gmail.com', '2018-04-01', 1, '2026-05-24 18:00:00'),
(1709, '308181', 'Md. Ayub Ali', 'Senior Medical Promotion Officer', 'Mirpur-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993210', 'ayubalitt@gmail.com', '2018-04-01', 1, '2026-05-24 18:00:00'),
(1710, '308182', 'Md. Jahedul Islam', 'Senior Medical Promotion Officer', 'Tangail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994116', 'jahedulm637@gmail.com', '2018-04-01', 1, '2026-05-24 18:00:00'),
(1711, '308184', 'Md. Nazirul Islam', 'Senior Area Sales Manager', 'Mirpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993214', 'nazirulislam889@gmail.com', '2018-03-30', 1, '2026-05-24 18:00:00'),
(1712, '308186', 'Abu Taleb', 'Senior Medical Promotion Officer', 'Sreemangal-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994971', 'abutaleb.unigroup@gmail.com', '2018-02-04', 1, '2026-05-24 18:00:00'),
(1713, '308188', 'Md. Mamunur Rashid', 'Senior Medical Promotion Officer', 'Mirpur-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994509', 'mamunurrashid32315@gmail.com', '2018-04-02', 1, '2026-05-24 18:00:00'),
(1714, '308193', 'Buddha Dev Kumar Mondal', 'Senior Medical Promotion Officer', 'Khulna-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994355', 'buddhadevmondal477@gmail.com', '2018-03-27', 1, '2026-05-24 18:00:00'),
(1715, '308195', 'Mohammad Rejaul Karim', 'Area Sales Manager', 'B.Baria-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994901', 'karimejaul9805@gmail.com', '2018-03-01', 1, '2026-05-24 18:00:00'),
(1716, '308198', 'Abdul Mottaleb', 'Senior Medical Promotion Officer', 'Kushtia-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993914', 'abdullah876750@gmail.com', '2018-04-08', 1, '2026-05-24 18:00:00'),
(1717, '308200', 'Mohammed Aminul Islam ', 'Assistant Sales Manager', 'Chittagong-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993347', 'aminul.islam@unigroup-bd.com', '2018-03-24', 1, '2026-05-24 18:00:00'),
(1718, '308203', 'Md. Abdul Mazid', 'Senior Medical Promotion Officer', 'Mirpur-14', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993290', 'mazidmdabdul8@gmail.com', '2018-04-10', 1, '2026-05-24 18:00:00'),
(1719, '308205', 'Md. Faruk Hossain', 'Senior Medical Promotion Officer', 'Feni-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994926', 'farukhosen0185@gmail.com', '2018-04-08', 1, '2026-05-24 18:00:00'),
(1720, '308216', 'Khaled Hasan Dipu', 'Senior Medical Promotion Officer', 'Eidgah-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994588', 'khaleddipu9@gmail.com', '2018-04-23', 1, '2026-05-24 18:00:00'),
(1721, '308224', 'Md. Mostahidur Rahman', 'Senior Medical Promotion Officer', 'Comilla City-17', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994828', 'mrmintu600@gmail.com', '2018-03-05', 1, '2026-05-24 18:00:00'),
(1722, '308227', 'Md. Abdur Rahman', 'Senior Medical Promotion Officer', 'Chandina-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994974', 'abdurrahman308227@gmail.com', '2018-04-25', 1, '2026-05-24 18:00:00'),
(1723, '308233', 'Md. Hojrat Ali', 'Senior Medical Promotion Officer', 'NICVD-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994899', 'mrhajrat1991@gmail.com', '2018-05-05', 1, '2026-05-24 18:00:00'),
(1724, '308235', 'Md. Abdus Samad', 'Senior Medical Promotion Officer', 'Heart Foundation', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994187', 'masamad1738@gmail.com', '2018-05-12', 1, '2026-05-24 18:00:00'),
(1725, '308237', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Laksam-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993557', 'arifuni9353@gmail.com', '2018-05-07', 1, '2026-05-24 18:00:00'),
(1726, '308240', 'Md. Rafiqul Islam', 'Senior Medical Promotion Officer', 'Jessore-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994633', 'mdrofiqulislam610@gmail.com', '2018-09-05', 1, '2026-05-24 18:00:00'),
(1727, '308247', 'Md. Monirujjaman', 'Senior Medical Promotion Officer', 'Bera-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993740', 'monirujjaman806@gmail.com', '2018-05-16', 1, '2026-05-24 18:00:00'),
(1728, '308253', 'Md. Sarure Jahan', 'Senior Medical Promotion Officer', 'DNMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993496', 'shamimmia9090@gmail.com', '2018-03-05', 1, '2026-05-24 18:00:00'),
(1729, '308266', 'Ibrahim Hossain', 'Senior Medical Promotion Officer', 'Sreenagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994598', 'ibrahimentbd@gmail.com', '2018-05-20', 1, '2026-05-24 18:00:00'),
(1730, '308272', 'Md. Khoybor Rahman', 'Senior Medical Promotion Officer', 'Pabna-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993606', 'khoyborrahmanmd@gmail.com', '2018-06-19', 1, '2026-05-24 18:00:00'),
(1731, '308279', 'Md. Golam Sarwar', 'Area Sales Manager', 'Mirpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993495', 'ssarowarr@gmail.com', '2018-06-19', 1, '2026-05-24 18:00:00'),
(1732, '308289', 'Sanjit Kumar Barai', 'Senior Medical Promotion Officer', 'Kachukhet-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994195', 'sanjit41666.sk@gmail.com', '2018-06-20', 1, '2026-05-24 18:00:00'),
(1733, '308292', 'Md. Shahadat Hosen', 'Area Sales Manager', 'Coxsbazar-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993679', 'princeshahadat2017@gmail.com', '2018-09-05', 1, '2026-05-24 18:00:00'),
(1734, '308295', 'Mohammed Jahirul Haq', 'Assistant Sales Manager', 'Coxsbazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993457', 'jahirul.haq@unigroup-bd.com', '2018-07-05', 1, '2026-05-24 18:00:00'),
(1735, '308301', 'Md. Atiqur Rahman', 'Senior Medical Promotion Officer', 'Shahporan-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993772', '01736938639atik@gmail.com', '2018-09-04', 1, '2026-05-24 18:00:00'),
(1736, '308302', 'Razu Ahmed', 'Senior Medical Promotion Officer', 'BMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994898', 'rajuahmedpanna1991@gmail.com', '2018-08-02', 1, '2026-05-24 18:00:00'),
(1737, '308303', 'Razu Miah', 'Senior Medical Promotion Officer', 'Kulaura-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994541', 'razumia13@gmail.com', '2018-09-06', 1, '2026-05-24 18:00:00'),
(1738, '308305', 'Md. Faridul Islam', 'Senior Medical Promotion Officer', 'Jhalakathi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994078', 'farid.unigroup1@gmail.com', '2018-09-04', 1, '2026-05-24 18:00:00'),
(1739, '308306', 'Md. Motiur Rahman', 'Senior Medical Promotion Officer', 'Faridganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994115', 'motiur.raj88@gmail.com', '2018-09-04', 1, '2026-05-24 18:00:00'),
(1740, '308317', 'Md. Sohel Rana', 'Senior Area Sales Manager', 'Nilphamari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993909', 'sohel.me12@gmail.com', '2018-08-04', 1, '2026-05-24 18:00:00'),
(1741, '308319', 'Shariful Islam', 'Senior Medical Promotion Officer', 'Ibrahimpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993503', 'sohan281337@gmail.com', '2018-08-04', 1, '2026-05-24 18:00:00'),
(1742, '308321', 'Golam Muhammad Masum', 'Senior Medical Promotion Officer', 'Faridpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994474', 'golammdmasum@gmail.com', '2018-06-02', 1, '2026-05-24 18:00:00'),
(1743, '308322', 'Md. Muktar Hosen', 'Senior Medical Promotion Officer', 'Ulipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993881', 'muktareconomist@gmail.com', '2018-08-04', 1, '2026-05-24 18:00:00'),
(1744, '308326', 'Md. Miraj Kazi', 'Senior Medical Promotion Officer', 'Hajiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994785', 'mdmirazkazi1@gmail.com', '2018-07-31', 1, '2026-05-24 18:00:00'),
(1745, '308333', 'Nitya Karmakar', 'Senior Medical Promotion Officer', 'Narail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994641', 'nkarmakar692@gmail.com', '2018-08-21', 1, '2026-05-24 18:00:00'),
(1746, '308340', 'Md. Shahinur Rahman', 'Senior Medical Promotion Officer', 'DMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994261', 'shahin.rng.bd@gmail.com', '2018-07-31', 1, '2026-05-24 18:00:00'),
(1747, '308341', 'Narayan Chandra Adhikary', 'Senior Medical Promotion Officer', 'Kishoreganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994517', 'narayanunigroup@gmail.com', '2018-08-04', 1, '2026-05-24 18:00:00'),
(1748, '308342', 'Tajmul Hossain', 'Senior Medical Promotion Officer', 'Mirpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993940', 'endwisetaj@gmail.com', '2018-08-05', 1, '2026-05-24 18:00:00'),
(1749, '308344', 'Mozammel Haque', 'Senior Medical Promotion Officer', 'Manikganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993811', 'mozammelhaque308340@gmail.com', '2018-07-31', 1, '2026-05-24 18:00:00'),
(1750, '308347', 'Sheikh Md. Arman Hossain', 'Senior Medical Promotion Officer', 'Khulna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993771', 'mdarmanhoss647@gmail.com', '2018-08-01', 1, '2026-05-24 18:00:00'),
(1751, '308351', 'Md. Jahangir Alam', 'Senior Medical Promotion Officer', 'Dupchanchia-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993925', 'jahangiralamjack@gmail.com', '2018-07-29', 1, '2026-05-24 18:00:00'),
(1752, '308355', 'Md. Monjurul Islam', 'Senior Medical Promotion Officer', 'B.Baria-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993328', 'monjurulbba92@gmail.com', '2018-08-26', 1, '2026-05-24 18:00:00'),
(1753, '308357', 'Akramul haque', 'Senior Medical Promotion Officer', 'Golapganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993485', 'akramulfb43@gmail.com', '2018-08-26', 1, '2026-05-24 18:00:00'),
(1754, '308358', 'Al Amin Rubel', 'Senior Medical Promotion Officer', 'Habiganj-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993570', 'ar22amin@gmail.com', '2018-08-26', 1, '2026-05-24 18:00:00'),
(1755, '308361', 'Md. Abdul Mannan', 'Senior Medical Promotion Officer', 'Tangail-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994937', 'abdulmannan808208@gmail.com', '2018-07-25', 1, '2026-05-24 18:00:00'),
(1756, '308362', 'Md. Abdul Hamid', 'Senior Medical Promotion Officer', 'Amirabad-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993677', 'uniabhamid@gmail.com', '2018-08-18', 1, '2026-05-24 18:00:00'),
(1757, '308370', 'Md. Sirajul Islam', 'Senior Medical Promotion Officer', 'Jessore-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993603', 'mdsirajulislamsanzu@gmail.com', '2018-08-25', 1, '2026-05-24 18:00:00'),
(1758, '308380', 'Md. Jahirul Islam', 'Senior Medical Promotion Officer', 'Ramu-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993563', 'jahir690@gmail.com', '2018-08-18', 1, '2026-05-24 18:00:00'),
(1759, '308381', 'Md. Anam Hossain', 'Senior Medical Promotion Officer', 'Khilgaon', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993399', 'anamehbd@gmail.com', '2018-08-25', 1, '2026-05-24 18:00:00'),
(1760, '308382', 'Jewel Chandra Shill', 'Senior Medical Promotion Officer', 'UnMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993113', 'jewelshill09@gmail.com', '2018-08-19', 1, '2026-05-24 18:00:00'),
(1761, '308383', 'Md. Fazlul Karim', 'Senior Medical Promotion Officer', 'Dinajpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993296', 'fazlufmmoni@gmail.com', '2018-08-25', 1, '2026-05-24 18:00:00'),
(1762, '308385', 'Md. Ismail Hossen', 'Senior Medical Promotion Officer', 'Chowdhury hat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994152', 'ismailjjsa2021@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(1763, '308387', 'Md. Habib Ullah', 'Senior Medical Promotion Officer', 'Medical Center-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994084', 'hu04759@gmail.com', '2018-09-15', 1, '2026-05-24 18:00:00'),
(1764, '308388', 'Md. Razarul Islam', 'Senior Medical Promotion Officer', 'Lalmonirhat-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993397', 'razarulislam25121991@gmail.com', '2018-09-15', 1, '2026-05-24 18:00:00'),
(1765, '308389', 'Moklasur', 'Senior Medical Promotion Officer', 'Kalaroa-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993797', 'moklasursr@gmail.com', '2018-09-15', 1, '2026-05-24 18:00:00'),
(1766, '308390', 'Md. Safiqul Islam', 'Senior Medical Promotion Officer', 'Munshiganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993828', 'safiq.unigroup@gmail.com', '2018-07-25', 1, '2026-05-24 18:00:00'),
(1767, '308392', 'Md. Shahalam', 'Senior Medical Promotion Officer', 'Ramganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994459', 'shahalamtagc1993@gmail.com', '2018-09-17', 1, '2026-05-24 18:00:00'),
(1768, '308393', 'Syed Kawsar', 'Senior Medical Promotion Officer', 'Mathbaria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993419', 'kawsarsyed616@gmail.com', '2018-09-03', 1, '2026-05-24 18:00:00'),
(1769, '308400', 'Md. Shahidul Islam', 'Senior Medical Promotion Officer', 'Maijdee-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993687', 'shahidul81095@gmail.com', '2018-09-10', 1, '2026-05-24 18:00:00'),
(1770, '308402', 'Md. Sumon Ali', 'Senior Medical Promotion Officer', 'Lakshmipur-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994590', 'sumonali8874@gmail.com', '2018-09-09', 1, '2026-05-24 18:00:00'),
(1771, '308404', 'Md. Azizur Rahman', 'Deputy Sales Manager', 'Dhaka-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993312', 'aziz.rahman@unigroup-bd.com', '2018-08-25', 1, '2026-05-24 18:00:00'),
(1772, '308406', 'Rahmat E Alam', 'Senior Medical Promotion Officer', 'Chhatak-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993964', 'ealamrahman@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(1773, '308407', 'Rana Chakraborty', 'Senior Medical Promotion Officer', 'DMCH-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994597', 'ranachakraboety@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(1774, '308409', 'Md. Sumon Miah', 'Senior Medical Promotion Officer', 'Sirajganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993471', 'mdsumonmia2708@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(1775, '308411', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Gopalganj-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994492', 'mdsohelrana15290@gmail.com', '2018-09-16', 1, '2026-05-24 18:00:00'),
(1776, '308413', 'Md. Meherab Ali', 'Senior Medical Promotion Officer', 'Jamalpur-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994904', 'meherabali10@gmail.com', '2018-09-23', 1, '2026-05-24 18:00:00'),
(1777, '308416', 'Liton Mandal', 'Senior Medical Promotion Officer', 'Khulna-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993626', 'litonmandal421@gmail.com', '2018-09-22', 1, '2026-05-24 18:00:00'),
(1778, '308420', 'Md. Abdur Rouf', 'Medical Promotion Officer', 'Manikganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994619', 'realrouf0@gmail.com', '2018-09-22', 1, '2026-05-24 18:00:00'),
(1779, '308422', 'Md. Alamgir Hossain', 'Senior Medical Promotion Officer', 'Kazipara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993476', 'alamgirvai007@gmail.com', '2018-09-20', 1, '2026-05-24 18:00:00'),
(1780, '308423', 'Rejaul Karim', 'Senior Medical Promotion Officer', 'Madaripur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995103', 'rezaulibais@gmail.com', '2018-09-18', 1, '2026-05-24 18:00:00'),
(1781, '308431', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Maijdee-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994349', 'rashed08011992@gmail.com', '2018-10-17', 1, '2026-05-24 18:00:00'),
(1782, '308436', 'Inul Bari', 'Senior Medical Promotion Officer', 'Maa O Shishu-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994789', 'sharminbari01738036699@gmail.com', '2018-10-14', 1, '2026-05-24 18:00:00'),
(1783, '308440', 'Jotimoy Roy', 'Senior Medical Promotion Officer', 'Savar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994980', 'jotimoyroy66@gmail.com', '2018-10-18', 1, '2026-05-24 18:00:00'),
(1784, '308441', 'Md. Mainul Islam', 'Senior Medical Promotion Officer', 'Tangail-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993118', 'mainuluni4@gmail.com', '2018-10-17', 1, '2026-05-24 18:00:00'),
(1785, '308448', 'Md. Masudur Rahman', 'Senior Medical Promotion Officer', 'SRNGLIH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993701', 'masud8969@gmail.com', '2018-10-17', 1, '2026-05-24 18:00:00'),
(1786, '308451', 'Mohammad Fahad Uddin', 'Senior Medical Promotion Officer', 'Hatia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994994', 'fahadimly96@gmail.com', '2018-10-14', 1, '2026-05-24 18:00:00'),
(1787, '308452', 'S.M.Tanvir Hossain', 'Senior Medical Promotion Officer', 'Muksudpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994989', 'taniktahsan@gmail.com', '2018-10-17', 1, '2026-05-24 18:00:00'),
(1788, '308461', 'Nayan kanti Saha', 'Senior Medical Promotion Officer', 'B.Baria-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994995', 'nayan1994saha@gmail.com', '2018-10-09', 1, '2026-05-24 18:00:00'),
(1789, '308465', 'Md. Mainul Islam', 'Senior Medical Promotion Officer', 'Bauphal-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994992', 'mainulislam3939@gmail.com', '2018-10-23', 1, '2026-05-24 18:00:00'),
(1790, '308472', 'Md. Sahab Uddin', 'Senior Medical Promotion Officer', 'Sandwip', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994112', 'sahabuddin.nu@gmail.com', '2018-10-24', 1, '2026-05-24 18:00:00'),
(1791, '308473', 'Md. Golam Rabbani Islam', 'Senior Medical Promotion Officer', 'Baniachong', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995113', 'golamrabbani686985@gmail.com', '2018-10-24', 1, '2026-05-24 18:00:00'),
(1792, '308475', 'Prosanto Mahanto', 'Area Sales Manager', 'Dhaka-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994212', 'prosantomahanto88@gmail.com', '2018-10-25', 1, '2026-05-24 18:00:00'),
(1793, '308478', 'Moynul Haque', 'Senior Medical Promotion Officer', 'Dinajpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993556', 'hoquemoynul746@gmail.com', '2018-11-03', 1, '2026-05-24 18:00:00'),
(1794, '308481', 'Md. Wahidur Rahman', 'Senior Medical Promotion Officer', 'Laksam-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994106', 'wahidurrose@gmail.com', '2018-11-11', 1, '2026-05-24 18:00:00'),
(1795, '308483', 'Md. Samiul Alam', 'Senior Medical Promotion Officer', 'Narsingdi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993928', 'mdsamiula39@gmail.com', '2018-10-29', 1, '2026-05-24 18:00:00'),
(1796, '308494', 'Md. Habibur Rahman Habib', 'Senior Medical Promotion Officer', 'Konapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993504', 'habibruhi786@gmail.com', '2018-10-28', 1, '2026-05-24 18:00:00'),
(1797, '308496', 'Sumon Ahmed', 'Senior Medical Promotion Officer', 'NEMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994098', '71.e.sumon@gmail.com', '2018-10-27', 1, '2026-05-24 18:00:00'),
(1798, '308499', 'Md. Soyful Islam', 'Senior Medical Promotion Officer', 'Monohargonj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995116', 'soyfulislam751@gmail.com', '2018-11-21', 1, '2026-05-24 18:00:00'),
(1799, '308500', 'Md. Wasim Miah', 'Senior Medical Promotion Officer', 'Burichang', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995341', 'mowasimmia49@gmail.com', '2018-11-01', 1, '2026-05-24 18:00:00'),
(1800, '308503', 'Hemal Ahamed Rasel', 'Senior Medical Promotion Officer', 'Jawa Kaitak-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995257', 'himel2008unigroup@gmail.com', '2018-11-01', 1, '2026-05-24 18:00:00'),
(1801, '308506', 'Farukul Islam', 'Senior Medical Promotion Officer', 'CIMCH-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995398', 'farukulislam316@gmail.com', '2018-11-14', 1, '2026-05-24 18:00:00'),
(1802, '308507', 'Mofazzal Hossain', 'Senior Medical Promotion Officer', 'Muradnagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995314', 'tuhin12509@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1803, '308508', 'Jobayer Ahmed Pial', 'Senior Medical Promotion Officer', 'Brahmanpara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995342', 'japial92@gmail.com', '2018-11-14', 1, '2026-05-24 18:00:00'),
(1804, '308509', 'Md. Jobayar Rafi', 'Area Sales Manager', 'Habiganj-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995533', 'jobayarrafi95@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1805, '308511', 'Ziaur Rahman', 'Senior Medical Promotion Officer', 'Mirsharai-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995227', 'mdziaur0102@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1806, '308512', 'Md. Majharul Hasan', 'Area Sales Manager', 'Chakaria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995206', 'majharulhasan99@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1807, '308516', 'Alak Paul', 'Senior Medical Promotion Officer', 'Mudafargonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995245', 'alakpaul660@gmail.com', '2018-11-14', 1, '2026-05-24 18:00:00'),
(1808, '308518', 'Ashish Kumar Majumder', 'Senior Medical Promotion Officer', 'Titas', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995273', 'mitumajumder95@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1809, '308520', 'Md. Al Amin', 'Senior Medical Promotion Officer', 'BSMMU/Comfort', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995174', 'jibon00098@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1810, '308521', 'Md. Raqibul Islam', 'Senior Medical Promotion Officer', 'Bancharampur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995274', 'md.rajibulislam123@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1811, '308525', 'Md. Robiul Islam', 'Senior Medical Promotion Officer', 'Kabirhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995177', 'rabiulislamunimed@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1812, '308526', 'Md. Ibrahim PK', 'Senior Medical Promotion Officer', 'Kanaighat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994419', 'ipk883969@gmail.com', '2018-11-14', 1, '2026-05-24 18:00:00'),
(1813, '308528', 'Joy Goswami', 'Senior Medical Promotion Officer', 'Kushtia-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994335', 'joy99013@gmail.com', '2018-12-23', 1, '2026-05-24 18:00:00'),
(1814, '308529', 'Washim Miah', 'Senior Medical Promotion Officer', 'Sarail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995290', 'washim2019@gmail.com', '2018-11-15', 1, '2026-05-24 18:00:00'),
(1815, '308532', 'Md. Abdul Alim ', 'Senior Medical Promotion Officer', 'Jagannathpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995260', 'mdabdulalim6671@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1816, '308536', 'Mukter Hossain ', 'Senior Medical Promotion Officer', 'Barlekha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995565', 'mukterhossin44@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1817, '308539', 'Md. Zashim Uddin ', 'Senior Medical Promotion Officer', 'Juri', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995564', 'zosimuddinraj@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1818, '308541', 'Aslam Hossain', 'Senior Medical Promotion Officer', 'Sakhipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995264', 'aslamhossain1259@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1819, '308543', 'Samrat Hossain ', 'Senior Medical Promotion Officer', 'Chowmuhani-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995175', 'samrathossainsc@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1820, '308545', 'Abu Kashem ', 'Senior Medical Promotion Officer', 'Rangunia-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995234', 'abulkashem2000@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(1821, '308546', 'Md. Mukul Hossain ', 'Senior Medical Promotion Officer', 'Gior', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995279', 'mukulhossainmeherpur@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1822, '308548', 'Md. Arif Hossain ', 'Senior Medical Promotion Officer', 'Sonargaon', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995650', 'arifhossain854777@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1823, '308549', 'Md. Dipu Sultan ', 'Senior Medical Promotion Officer', 'Dhamrai-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995307', 'dipusultan519@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1824, '308552', 'Md. Mamun Ar Rashid ', 'Senior Medical Promotion Officer', 'Fatullah', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996300', 'mamunarrashid2244@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1825, '308555', 'Md. Ariful Haque', 'Senior Medical Promotion Officer', 'Phulpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995329', 'arif.unihealth71@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1826, '308557', 'Md. Shamim Hossain', 'Senior Medical Promotion Officer', 'Mirzapur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995266', 'shamimhossan6912@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1827, '308558', 'Abdullah Al Sajib ', 'Senior Medical Promotion Officer', 'Cantonment-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995399', 'sajibaub1995@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1828, '308561', 'Md. Alamgir Hossain ', 'Senior Medical Promotion Officer', 'Shahrasti-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995218', 'skalamgirhossain89@gmail.com', '2018-11-23', 1, '2026-05-24 18:00:00'),
(1829, '308562', 'Md. Masum Billah ', 'Senior Medical Promotion Officer', 'Subarnachar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995178', 'masumbillahganti38@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1830, '308563', 'Raju Ahamed ', 'Senior Medical Promotion Officer', 'Bhuapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995296', 'raju0172295@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1831, '308566', 'Md. Akramul Haque ', 'Senior Medical Promotion Officer', 'Konabari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995309', 'mdakramulhaque1990@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1832, '308567', 'Md. Abdul Momin Sheikh ', 'Senior Medical Promotion Officer', 'Sirajdikhan-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995190', 'mdabdulmominsheikh101@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1833, '308568', 'Md. Abul Kalam Azad ', 'Senior Medical Promotion Officer', 'Kapasia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996703', 'abul03483@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1834, '308570', 'Md. Atikur Rahman ', 'Senior Medical Promotion Officer', 'Bahubal-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995562', 'atik19069@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1835, '308575', 'Md. Ali Aktar', 'Senior Medical Promotion Officer', 'Ashulia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994760', 'mdaliaktar860@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1836, '308578', 'Md. Abdul Alim Rabin ', 'Senior Medical Promotion Officer', 'Zakiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995456', 'rabinabdulalim@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1837, '308579', 'Mohammad Yousouf Ali ', 'Senior Medical Promotion Officer', 'Comilla City-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995645', 'fahimkhan002@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1838, '308582', 'Md. Nasir Uddin ', 'Senior Medical Promotion Officer', 'Iswarganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995325', 'nasiruddinn2083@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1839, '308584', 'Jahirul Islam ', 'Senior Medical Promotion Officer', 'Raipura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996369', 'jahirul2060@gamil.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1840, '308585', 'Md. Delwar Hossain ', 'Senior Medical Promotion Officer', 'Anwara-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995230', 'delwarhossainunihealth@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1841, '308586', 'Md. Arifur Rahaman Khandakar ', 'Senior Medical Promotion Officer', 'Fulbaria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995319', 'arifur2625@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1842, '308587', 'Md. Mahmud Hasan ', 'Senior Medical Promotion Officer', 'Matlab-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995214', 'remihasan1989@gmail.com', '2018-11-23', 1, '2026-05-24 18:00:00'),
(1843, '308589', 'Md. Mehadi Hasan ', 'Senior Medical Promotion Officer', 'Fenchuganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995455', 'smsajol22@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1844, '308590', 'Md. Ripon Ahmed ', 'Senior Medical Promotion Officer', 'Companiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995454', 'riponahmednatore@gmail.com', '2018-11-26', 1, '2026-05-24 18:00:00'),
(1845, '308592', 'Md. Jahid Hasan ', 'Senior Medical Promotion Officer', 'Melandah', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995339', 'jahed01750768347@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1846, '308593', 'Md. Bipul Islam ', 'Senior Medical Promotion Officer', 'Madan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995253', 'bipulislambbs@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1847, '308596', 'Selim Reza ', 'Senior Medical Promotion Officer', 'Paikgasa', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996368', 'selimreza96a@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1848, '308597', 'Asaduzzaman ', 'Medical Promotion Officer', 'Nokla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995249', 'asaduzzaman8001996@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1849, '308598', 'Pashan Ali ', 'Senior Medical Promotion Officer', 'Muktagacha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995323', 'kamalpasha1122ok@gmail.Com', '2018-11-25', 1, '2026-05-24 18:00:00'),
(1850, '308599', 'Md. Alamgir Hossain ', 'Senior Medical Promotion Officer', 'Ghatail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995295', 'alamgirhossain4b1993@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1851, '308601', 'Md. Abdul Kafi ', 'Senior Medical Promotion Officer', 'Sarishabari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995336', 'abdulkafi.tasmin@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1852, '308602', 'Md. Shabuj Hossain ', 'Senior Medical Promotion Officer', 'Gafargaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995328', 'shabuj1989.unigroup@gmail.com', '2018-11-25', 1, '2026-05-24 18:00:00'),
(1853, '308603', 'Abdul Rahim Howlader ', 'Senior Medical Promotion Officer', 'Kalihati', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995294', 'abdurrahimmd223@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1854, '308605', 'Md. Sourav Hossain ', 'Senior Medical Promotion Officer', 'Goalabazar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994592', 'hossainsourav035@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1855, '308614', 'Ariful Islam', 'Senior Medical Promotion Officer', 'Gobindaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995114', 'arifulislamtutul1993@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1856, '308618', 'Md. Badsha Alamgir', 'Senior Medical Promotion Officer', 'SSMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995118', 'badshaalamgir291@gmail.com', '2018-11-27', 1, '2026-05-24 18:00:00'),
(1857, '308621', 'Md. Moniruzzaman', 'Senior Medical Promotion Officer', 'Sonapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995127', 'mmzaman1999@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1858, '308622', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Munshiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995130', 'mizanuruniderma@gmail.com', '2018-11-22', 1, '2026-05-24 18:00:00'),
(1859, '308625', 'Biplab Biswas', 'Senior Medical Promotion Officer', 'Madaripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995170', 'biplab.unigroup@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1860, '308627', 'Shohidul Islam', 'Senior Medical Promotion Officer', 'Raipur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995128', 'shohidulislam679@gmail.com', '2018-11-19', 1, '2026-05-24 18:00:00'),
(1861, '308631', 'Abdur Rahman', 'Senior Medical Promotion Officer', 'Keraniganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995119', 'abdurrahmanasadul590@gmail.com', '2018-11-24', 1, '2026-05-24 18:00:00'),
(1862, '308633', 'Md. Harunar Rashid', 'Senior Medical Promotion Officer', 'Mirzapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995166', 'smhjihad988@gmail.com', '2018-11-22', 1, '2026-05-24 18:00:00'),
(1863, '308634', 'Md. Faruque Hossain', 'Senior Area Sales Manager', 'BIRDEM-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995293', 'faruk240021@gmail.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(1864, '308635', 'Jakaria Khan', 'Senior Medical Promotion Officer', 'Manikganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995277', 'jakariarayhan80@gmail.com', '2018-11-28', 1, '2026-05-24 18:00:00'),
(1865, '308636', 'Md. Raju Miah', 'Senior Medical Promotion Officer', 'Thakurgaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995267', 'mdrajumia54321@gmail.com', '2018-11-28', 1, '2026-05-24 18:00:00'),
(1866, '308637', 'Md. Munnajal Haque', 'Senior Medical Promotion Officer', 'Boda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995268', 'mhmunna2728@gmail.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(1867, '308648', 'Muhammad Jahidul Islam', 'Senior Medical Promotion Officer', 'Moulvibazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995183', 'muhammadjahidul0172@gmail.com', '2018-12-06', 1, '2026-05-24 18:00:00'),
(1868, '308654', 'Md. Nur Jamal', 'Senior Medical Promotion Officer', 'DMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993473', 'nurjamalshohag@gmail.com', '2018-12-14', 1, '2026-05-24 18:00:00'),
(1869, '308657', 'Md. Asraful Sarker', 'Senior Medical Promotion Officer', 'Basurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995126', 'asrafulsarker06@gmail.com', '2018-12-09', 1, '2026-05-24 18:00:00'),
(1870, '308663', 'Ranju Ahmmed', 'Senior Medical Promotion Officer', 'Ranisankail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995269', 'ranjuahmmed8687@gmail.Com', '2018-12-10', 1, '2026-05-24 18:00:00'),
(1871, '308665', 'Md. Saidur Rahman', 'Senior Medical Promotion Officer', 'Brahmanpara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995244', 'saidursumon279@gmail.com', '2018-12-09', 1, '2026-05-24 18:00:00'),
(1872, '308668', 'Mrinal Halder', 'Senior Medical Promotion Officer', 'Bhola-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995523', 'm.84210008@gmail.com', '2018-12-09', 1, '2026-05-24 18:00:00'),
(1873, '308670', 'Md. Somon Mia', 'Senior Medical Promotion Officer', 'Netrokona-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995262', 'khaledsumon1995@gmail.com', '2018-12-08', 1, '2026-05-24 18:00:00'),
(1874, '308671', 'Md. Russel Shah', 'Senior Medical Promotion Officer', 'Bandartila-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994714', 'mdraselshah580@gmail.com', '2018-12-12', 1, '2026-05-24 18:00:00'),
(1875, '308674', 'Md. Abdur Rob', 'Senior Medical Promotion Officer', 'Labaid-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995438', 'abdurrob0009@gmail.com', '2018-12-09', 1, '2026-05-24 18:00:00'),
(1876, '308680', 'Md. Abu Hassan', 'Senior Medical Promotion Officer', 'Mia Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995246', 'hassansova.info@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1877, '308683', 'Md. Rezaul Hasan', 'Senior Medical Promotion Officer', 'Subarnachar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995194', 'rezaulhasan43@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1878, '308684', 'Md. Shahiduzzaman Khan', 'Senior Medical Promotion Officer', 'Debidwar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995313', 'shahiduzzamankhan1992@gmail.com', '2018-12-20', 1, '2026-05-24 18:00:00'),
(1879, '308688', 'Noor Alam', 'Medical Promotion Officer', 'Dhobaura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995331', 'skhnooralam@gmail.com', '2018-12-20', 1, '2026-05-24 18:00:00'),
(1880, '308690', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'Itna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996377', 'saifulakon55@gmail.com', '2018-12-21', 1, '2026-05-24 18:00:00'),
(1881, '308692', 'Akidul islam', 'Senior Medical Promotion Officer', 'Ashuganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995291', 'akidulislam3344@gmail.com', '2018-12-21', 1, '2026-05-24 18:00:00'),
(1882, '308693', 'Md. Sorowar Jahan', 'Senior Medical Promotion Officer', 'Comilla City-15', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993791', 'sjsajib553@gmail.com', '2018-12-23', 1, '2026-05-24 18:00:00'),
(1883, '308695', 'Sayef Amin', 'Senior Medical Promotion Officer', 'Bajitpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996362', 'sayef.amin@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1884, '308696', 'Md. Abul Kalam Azad ', 'Senior Medical Promotion Officer', 'Hossainpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996381', 'mdazad5257@gmail.com', '2018-12-21', 1, '2026-05-24 18:00:00'),
(1885, '308699', 'Mehedi Hasan Rasel', 'Senior Medical Promotion Officer', 'Hajiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995216', 'mehedihasan31079@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1886, '308700', 'Md. Rubel Ahmed', 'Senior Medical Promotion Officer', 'Jamalganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995261', 'rubelahmedsylhet0821@gmail.com', '2018-12-19', 1, '2026-05-24 18:00:00'),
(1887, '308701', 'Md. Johirul Islam', 'Senior Medical Promotion Officer', 'Bokshiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995251', 'johirulislamuhum@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1888, '308702', 'Ishak Ali', 'Senior Medical Promotion Officer', 'Banshkhali-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995232', 'mdishak2015@gmail.com', '2018-12-24', 1, '2026-05-24 18:00:00'),
(1889, '308703', 'Md. Abdul Monnaf', 'Senior Medical Promotion Officer', 'Moheshkhali-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995235', 'monnafahmed50@gmail.com', '2018-12-23', 1, '2026-05-24 18:00:00'),
(1890, '308705', 'Md. Abdur Rauf', 'Senior Medical Promotion Officer', 'Purbadhala', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995254', 'theabdurrauf@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1891, '308708', 'M. M. Zahidul Islam', 'Medical Promotion Officer', 'Gopalganj-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995192', 'zhasan316@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1892, '308709', 'Md. Ayub Ali   ', 'Senior Medical Promotion Officer', 'Savar-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995306', 'amdayub992@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1893, '308710', 'Md. Motasim Billah', 'Senior Medical Promotion Officer', 'Madhabdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996372', 'motasim772@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1894, '308711', 'Md. Shahajada Kalince', 'Senior Medical Promotion Officer', 'Rampal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995358', 'shahajadakalince32@gmail.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(1895, '308713', 'Md. Rabbi Sarker', 'Senior Medical Promotion Officer', 'Banshkhali-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995231', 'muhammadrabbi241@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1896, '308714', 'Md. Abdur Rahim', 'Senior Medical Promotion Officer', 'Ukhiya-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995236', 'rahimunigroup91@gmail.com', '2018-12-23', 1, '2026-05-24 18:00:00'),
(1897, '308716', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Gurudaspur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993851', 'rashedislam7378@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1898, '308717', 'Md. Murad Hasan', 'Senior Medical Promotion Officer', 'Wazirpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995576', 'mh8207193@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1899, '308718', 'Md. Mahmudul Hasan', 'Senior Medical Promotion Officer', 'Bagatipara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993843', 'mhomi99@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1900, '308720', 'Obeidul Islam', 'Senior Medical Promotion Officer', 'Raumari/Rajibpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995250', 'a.s.mobeidulislam93@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1901, '308723', 'Samiul Hossain', 'Senior Medical Promotion Officer', 'Ullapara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995209', 'samiulhossain308723@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1902, '308724', 'Md. Sadir Hossain', 'Senior Medical Promotion Officer', 'Durgapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995255', 'sadirhossain1994@gmail.com', '2018-12-22', 1, '2026-05-24 18:00:00'),
(1903, '308727', 'Md. Mosarraf Hossain', 'Senior Medical Promotion Officer', 'Sonagazi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995275', 'mosharraf.rasel@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1904, '308731', 'Md. Yousuf Ali', 'Senior Medical Promotion Officer', 'Sundarganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995302', 'yousufalipur1988@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1905, '308738', 'Md. Sukur Ali', 'Senior Medical Promotion Officer', 'Gopalpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995297', 'm2675014@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1906, '308740', 'Md. Kawser Alam', 'Senior Medical Promotion Officer', 'Tazumuddin', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995527', 'kawsaralam5860@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1907, '308743', 'Md. Golam Rabbani', 'Senior Medical Promotion Officer', 'Domar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995243', 'nrabbani007@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1908, '308747', 'Md. Rejowan Hossain', 'Senior Medical Promotion Officer', 'Borhanuddin', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995569', 'hossainmdrejowan@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1909, '308748', 'Md. Shahidullah Al Mahbub', 'Senior Medical Promotion Officer', 'Birganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995200', 'mahbubrony71@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1910, '308749', 'Md. Sohag Hossain', 'Senior Medical Promotion Officer', 'Barisal-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995628', 'moheuddinsohaghossain@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1911, '308750', 'Md. Nazimul Haque', 'Senior Medical Promotion Officer', 'Setabganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995198', 'mdnazimulislamchad@gmail.com', '2019-01-30', 1, '2026-05-24 18:00:00'),
(1912, '308752', 'Md. Aiyob Ali', 'Senior Medical Promotion Officer', 'Chowmuhani-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995282', 'aiyob001@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1913, '308753', 'Prodip Kumar Ray', 'Senior Medical Promotion Officer', 'Dinajpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995197', 'prodipkumarray87@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1914, '308754', 'Sadi Muhammad Setu', 'Senior Medical Promotion Officer', 'Barisal-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995346', 'sadi.setu@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1915, '308756', 'Hriday Ahamed', 'Senior Medical Promotion Officer', 'Mirpur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995213', 'hridoyliton@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1916, '308757', 'Md. Mehedi Hasan', 'Senior Medical Promotion Officer', 'Jhenaidah-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993923', 'mh5522671@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1917, '308758', 'Biplob Kumar Biswas', 'Senior Medical Promotion Officer', 'Faridpur-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995220', 'biswasbiplob403@gmail.com', '2019-01-12', 1, '2026-05-24 18:00:00'),
(1918, '308760', 'Md. Farhad Hossain', 'Senior Medical Promotion Officer', 'Rangpur-15', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995187', 'farhadhosssain199014@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1919, '308766', 'Md. Omar Faruq', 'Senior Medical Promotion Officer', 'Bashundhara-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994559', 'faruqomar622@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1920, '308768', 'Razib Kumar', 'Senior Medical Promotion Officer', 'Shialbari/Pallabi Ext', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994308', 'razibkumar28@gmail.com', '2019-01-02', 1, '2026-05-24 18:00:00'),
(1921, '308769', 'Md. Mamun -Or-Rashid', 'Senior Medical Promotion Officer', 'Shibchar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993567', 'mamunorrashid191285@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1922, '308776', 'Md. Nahid Rahman', 'Senior Medical Promotion Officer', 'Mymensingh-D5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995171', 'nahidrahman61195@gmail.com', '2018-12-26', 1, '2026-05-24 18:00:00'),
(1923, '308777', 'Md. Ayen Uddin', 'Senior Medical Promotion Officer', 'ShSMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994827', 'ayenuddin14@gmail.com', '2018-12-24', 1, '2026-05-24 18:00:00'),
(1924, '308779', 'Md. Kabir Hossain', 'Senior Medical Promotion Officer', 'Jessore-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995172', 'kabir.hossain5877@gmail.com', '2018-12-27', 1, '2026-05-24 18:00:00'),
(1925, '308781', 'Nur Mohammad Ariful Islam Khan', 'Medical Promotion Officer', 'Karimgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995221', 'arifula1992@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1926, '308782', 'Rasel Rana', 'Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989995199', 'raselrana30102020@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1927, '308783', 'Rakibul Hasan', 'Senior Medical Promotion Officer', 'Keraniganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995222', 'rakibulhasanbabul207@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1928, '308784', 'Rabiul Haque', 'Senior Medical Promotion Officer', 'Shibganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995287', 'robiul0175089@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1929, '308787', 'Md. Azizul Haque', 'Senior Medical Promotion Officer', 'Nikli/Sararchar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996378', 'azizulwd24@gmail.com', '2019-01-06', 1, '2026-05-24 18:00:00'),
(1930, '308788', 'Md. Shamiul Islam', 'Medical Promotion Officer', 'Kahaloo', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995286', 'mdshamiulislam247@gmail.com', '2019-01-03', 1, '2026-05-24 18:00:00'),
(1931, '308789', 'Mominul Islam', 'Senior Medical Promotion Officer', 'Sonatala', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995285', 'mominulislam82177@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1932, '308792', 'Zillur Rahman', 'Senior Medical Promotion Officer', 'Gabtali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995284', 'zr99164@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1933, '308793', 'Md. Ismail Hossen', 'Senior Medical Promotion Officer', 'Faridpur-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995629', 'mdismail308793@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1934, '308794', 'Firoz Kabir', 'Senior Medical Promotion Officer', 'Joypurhat-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995242', 'fkabir374@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1935, '308795', 'Md. Khairul Basar', 'Senior Medical Promotion Officer', 'Lalmonirhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995241', 'khairul03basar@gmail.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1936, '308797', 'Md. Rubel Fakir', 'Senior Medical Promotion Officer', 'Chitalmari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995345', 'rubelunimed@gmail.com', '2019-01-03', 1, '2026-05-24 18:00:00'),
(1937, '308798', 'Md. Abdul Mozid', 'Senior Medical Promotion Officer', 'Kaliganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996702', 'mozidshikha444@gmail.com', '2018-11-08', 1, '2026-05-24 18:00:00'),
(1938, '308806', 'Md. Milon Hossain', 'Senior Medical Promotion Officer', 'Adamdighi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995288', 'milonhossain97769@gmail.com', '2019-01-04', 1, '2026-05-24 18:00:00'),
(1939, '308807', 'Md. Mosaref Hossain', 'Senior Medical Promotion Officer', 'Muladi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995531', 'mdmosarefhossain72@gmail.com', '2019-01-05', 1, '2026-05-24 18:00:00'),
(1940, '308808', 'Md. Mirazur Rahman', 'Assistant Sales Manager', 'Rangpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995202', 'mirazur.rahman@unigroup-bd.com', '2019-01-01', 1, '2026-05-24 18:00:00'),
(1941, '308809', 'Saikh Md. Hasibullah', 'Senior Area Sales Manager', 'Noapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995211', 'hasibullah575@gmail.com', '2018-12-24', 1, '2026-05-24 18:00:00'),
(1942, '308810', 'Md. Moniruzzaman', 'Senior Area Sales Manager', 'DMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993635', 'monir10.uniderma@gmail.com', '2019-01-07', 1, '2026-05-24 18:00:00'),
(1943, '308812', 'Md. Liakat Ali', 'Senior Area Sales Manager', 'Bogra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995212', 'liakatali265@gmail.com', '2018-12-26', 1, '2026-05-24 18:00:00'),
(1944, '308818', 'Kazi Samsuzzaman', 'Senior Medical Promotion Officer', 'Evercare-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994847', 'ksshamim1122@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1945, '308819', 'Monorongon Ray', 'Senior Medical Promotion Officer', 'SHSMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994181', 'monoroy612@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1946, '308822', 'Fuad Miah', 'Senior Medical Promotion Officer', 'Satkania-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994958', 'mdfuad9891@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1947, '308823', 'Mithun Suter', 'Senior Medical Promotion Officer', 'EPZ-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995247', 'mithun.suter@gmail.com', '2019-01-18', 1, '2026-05-24 18:00:00'),
(1948, '308824', 'Md. Tofazzal Hossain', 'Senior Medical Promotion Officer', 'Lakshmipur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995311', 'tofazzalhossain3050@gmail.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1949, '308825', 'Reyazul Haque', 'Senior Medical Promotion Officer', 'Chapainawabganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994868', 'reyazul001@gmail.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1950, '308826', 'Sajal Chandra Mondol', 'Senior Medical Promotion Officer', 'Khulna-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995112', 'sajalmondol43@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1951, '308827', 'Kazi Maruf Hasan', 'Senior Medical Promotion Officer', 'Mymensingh-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994800', 'marufunigroup@gmail.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1952, '308828', 'Mithun Ali', 'Senior Medical Promotion Officer', 'Mirzapur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995344', 'mithubluesky6@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1953, '308829', 'Pabitra Chandra Gharami', 'Senior Medical Promotion Officer', 'Banaripara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995374', 'pabitragharam17@gmail.com', '2019-01-20', 1, '2026-05-24 18:00:00'),
(1954, '308832', 'Muhammad Sajjad Hossain', 'Senior Medical Promotion Officer', 'Agrabad-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995377', 'sajjadsagar50@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1955, '308833', 'Md. Lal Meea', 'Senior Medical Promotion Officer', 'Jhenaigati', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995462', 'lalmeea99@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1956, '308837', 'Md. Shabuj Al Mamun', 'Medical Promotion Officer', 'Singair', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994455', 'shabujalmamunovi@gmail.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1957, '308838', 'Sanzib Sarker', 'Senior Medical Promotion Officer', 'Agailjhara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995578', 'sanzibsarker19@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1958, '308839', 'Md. Robiul Islam', 'Senior Medical Promotion Officer', 'DMCH-D5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995387', 'robiulislamjoy445@gmail.com', '2018-12-24', 1, '2026-05-24 18:00:00'),
(1959, '308841', 'Abdullah Harun', 'Senior Medical Promotion Officer', 'Mitford-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995363', 'abdullahharun238@gmail.com', '2019-01-28', 1, '2026-05-24 18:00:00'),
(1960, '308842', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Rangpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995415', 'rashedulislamrony7383@gamil.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1961, '308843', 'Mahedi Hasan', 'Senior Medical Promotion Officer', 'Kathalia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995580', 'mahedihasan5898@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1962, '308844', 'Md. Masud Rana', 'Senior Medical Promotion Officer', 'Satkhira-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995353', 'masudranauht@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1963, '308848', 'Md. Osman Hossain', 'Senior Medical Promotion Officer', 'Kaliganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995357', 'osmanhossain81@gmail.com', '2019-01-28', 1, '2026-05-24 18:00:00'),
(1964, '308850', 'Md. Nahid Hasan', 'Senior Medical Promotion Officer', 'NICRH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994284', 'nahid9378@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1965, '308851', 'Hasanuzzaman', 'Senior Medical Promotion Officer', 'Botiaghata', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995350', 'hasanuzzaman550430@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1966, '308856', 'Afjal Hossain', 'Senior Medical Promotion Officer', 'Rohanpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995334', 'afjalhossain736@gmail.com', '2019-01-10', 1, '2026-05-24 18:00:00'),
(1967, '308857', 'Md. Mahamud Hasan', 'Senior Medical Promotion Officer', 'Pirgacha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995417', 'hmahamud347@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1968, '308865', 'Md. Fazlul Haque', 'Senior Medical Promotion Officer', 'DMCH-D3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995388', 'fazlulhaque085@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1969, '308868', 'Arshat Ali', 'Senior Medical Promotion Officer', 'Taragonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995412', 'arshat086@gmail.com', '2019-01-28', 1, '2026-05-24 18:00:00'),
(1970, '308871', 'Arman Uddin Ahmed', 'Medical Promotion Officer', 'Rajnagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995609', 'armanuddina2@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1971, '308872', 'Md. Rubel', 'Senior Medical Promotion Officer', 'Gopalganj-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996302', 'rubel.khulna200@gmail.com', '2019-02-23', 1, '2026-05-24 18:00:00'),
(1972, '308873', 'Md. Farid Hossain Sarker', 'Senior Medical Promotion Officer', 'Fultola-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995351', 'mdfaridhossainnil540@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1973, '308874', 'Md. Sohag Babu', 'Senior Medical Promotion Officer', 'Kaliakair-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995308', 'sohagbabu60@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1974, '308876', 'Sumon Ali', 'Senior Medical Promotion Officer', 'Rajshahi-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995414', 'sumonali19917@gmail.com', '2019-01-28', 1, '2026-05-24 18:00:00'),
(1975, '308877', 'Md. Kausar Uddin', 'Senior Medical Promotion Officer', 'Kurmitola-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994317', 'shujonshokhi22@gmail.com', '2019-01-26', 1, '2026-05-24 18:00:00'),
(1976, '308878', 'Md. Faruk Molla', 'Senior Medical Promotion Officer', 'Saltha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995365', 'mdfarukmolla467@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1977, '308881', 'Jakirul Islam', 'Senior Medical Promotion Officer', 'Austagram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996370', 'jakiruli018@gmail.com', '2019-01-29', 1, '2026-05-24 18:00:00'),
(1978, '308882', 'Md. Nazmul Hossain', 'Senior Medical Promotion Officer', 'Chuknagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995349', 'nazmul.hossain2875@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(1979, '308884', 'Md. Shihab Sheikh', 'Senior Medical Promotion Officer', 'Dumuria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995354', 'sheikhshihab852@gmail.com', '2019-01-27', 1, '2026-05-24 18:00:00'),
(1980, '308885', 'Md. Meherul Hoque', 'Senior Area Sales Manager', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989994110', 'meherul.hoque@unigroup-bd.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1981, '308886', 'Saiful Islam', 'Senior Area Sales Manager', 'Banshkhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993185', 'si1197536@gmail.com', '2019-01-21', 1, '2026-05-24 18:00:00'),
(1982, '308887', 'Abu Sadat Md. Sayem', 'Senior Area Sales Manager', 'Maa O Shishu', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993126', 'sayem.mdabusadat@gmail.com', '2019-01-21', 1, '2026-05-24 18:00:00'),
(1983, '308888', 'Tanvir Ahmed', 'Senior Area Sales Manager', 'Meherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995360', 'tanvirgap@gmail.com', '2019-01-19', 1, '2026-05-24 18:00:00'),
(1984, '308889', 'Prakash Barua', 'Senior Area Sales Manager', 'CEPZ', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993498', 'prakashbarua79@gmail.com', '2019-01-24', 1, '2026-05-24 18:00:00'),
(1985, '308891', 'A.S.M.Touhiduzzaman', 'Senior Area Sales Manager', 'Jamalpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995391', 'touhiduzzaman.unigroup@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(1986, '308893', 'Mohammad Zahidur Rahman', 'Senior Area Sales Manager', 'Khulna-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995400', 'mdzahidur1979@gmail.com', '2019-01-21', 1, '2026-05-24 18:00:00'),
(1987, '308896', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Universal Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995401', 'mrahaman1868@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1988, '308902', 'Md. Sohag Khan', 'Senior Medical Promotion Officer', 'Baburhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995376', 'mdsohagkhan0607@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1989, '308904', 'Md. Omor Faruq', 'Senior Medical Promotion Officer', 'Gobindaganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995361', 'omorfaruq10@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(1990, '308909', 'Omrit Kumar Sutradhar', 'Medical Promotion Officer', 'Maijdee-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996701', 'amritokumarsutradhar@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(1991, '308911', 'Solayman', 'Senior Medical Promotion Officer', 'Naya paltan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995370', 'solayman15121992@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1992, '308912', 'Md. Belal Hossain', 'Senior Medical Promotion Officer', 'UAMCH-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994579', 'belalhossain28111993@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(1993, '308914', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Bagha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994269', 'ariful001br@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(1994, '308917', 'Md. Rokonuzzaman', 'Senior Medical Promotion Officer', 'Shariatpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994341', 'rokonuzzamanmd822@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1995, '308922', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'Pabna-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995395', 'mdsaifulislamraj01797@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(1996, '308923', 'Mohammad Mintu Shaik', 'Senior Medical Promotion Officer', 'Mirpur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995445', 'mintusheikh1344@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1997, '308924', 'Md. Sohel Hossain', 'Senior Medical Promotion Officer', 'Zajira', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993803', 'hossainsohel1996@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1998, '308926', 'Mohammad Hafizur Rahman', 'Senior Medical Promotion Officer', 'Gabtoli', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995444', 'smhafiz212@gamil.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(1999, '308929', 'Alamgir Hossain', 'Senior Medical Promotion Officer', 'Shahjadpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995210', 'alamgirapon90@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(2000, '308931', 'Md. Azizul Haque', 'Senior Medical Promotion Officer', 'Rajshahi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996387', 'hoque0011@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(2001, '308934', ' Md. Hasanur Rahman', 'Senior Medical Promotion Officer', 'DMCH-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995371', 'rahmanhasanur604@gmail.com', '2019-02-05', 1, '2026-05-24 18:00:00'),
(2002, '308937', 'Md. Ataur Rahman', 'Senior Medical Promotion Officer', 'Sujanagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995393', 'ataurrahamanpbn@gmail.com', '2019-02-04', 1, '2026-05-24 18:00:00'),
(2003, '308938', 'Md. Shahinur islam', 'Senior Medical Promotion Officer', 'Nandina', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995337', 'miashahin4851@gmail.com', '2019-02-24', 1, '2026-05-24 18:00:00'),
(2004, '308939', 'Bidhan Chandra Sutrodhar', 'Senior Medical Promotion Officer', 'Kashiani-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996305', 'bidhanchandrasuttrodhar@gmail.com', '2019-02-11', 1, '2026-05-24 18:00:00'),
(2005, '308941', 'Md. Shahjahan Ali', 'Senior Medical Promotion Officer', 'Thakurgaon-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995173', 'jahanali7843@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2006, '308942', 'Md. Faruk Uzzaman', 'Senior Medical Promotion Officer', 'Panchagarh-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995419', 'faruk01722133490@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2007, '308946', 'Rezaul Karim', 'Senior Medical Promotion Officer', 'Sadarpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994836', 'rezauni19@gmail.com', '2019-02-11', 1, '2026-05-24 18:00:00'),
(2008, '308947', 'Moloy Kumar Sarder', 'Senior Medical Promotion Officer', 'Kotalipara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995570', 'moloysarder@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2009, '308948', 'Md. Aynul Haque', 'Senior Medical Promotion Officer', 'Bheramara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994440', 'mdaynulhaque27@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2010, '308949', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Barisal-D2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994706', 'mizan505484@gmail.com', '2019-02-07', 1, '2026-05-24 18:00:00'),
(2011, '308950', 'Mohammad Sadek Hosen', 'Senior Medical Promotion Officer', 'MuMC/Malibagh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995423', 'shdulan@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2012, '308953', 'Tawfiqul Islam', 'Senior Medical Promotion Officer', 'Jamal Khan-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995442', 'islammdtawfia@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(2013, '308955', 'Md. Emran Ali', 'Medical Promotion Officer', 'Naogaon-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995435', 'emranuniquegroup@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2014, '308956', 'Md. Abdullah Biswas', 'Senior Medical Promotion Officer', 'Bangla Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994276', 'abdullahob146@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(2015, '308957', 'Md. Azizul Haque', 'Senior Medical Promotion Officer', 'Manda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995437', 'azizulhaq480@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2016, '308960', 'Md. Sazzadul Islam', 'Senior Medical Promotion Officer', 'NICRH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995482', '994sazzad@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2017, '308962', 'Md. Shariful Islam', 'Senior Medical Promotion Officer', 'Atrai', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995436', 'shariful25031989@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2018, '308963', 'Md. Rubayet Hasan Rubel', 'Senior Medical Promotion Officer', 'Basail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995420', 'rubayet6200@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2019, '308964', 'Md. Rezaul Karim', 'Senior Medical Promotion Officer', 'Bogra-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995406', 'rezaulkarimrazukarim@gmail.com', '2019-02-12', 1, '2026-05-24 18:00:00'),
(2020, '308965', 'Mohammad Mienul Islam', 'Deputy Sales Manager', 'BSMMU-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993120', 'mienul.islam@unigroup-bd.com', '2019-01-14', 1, '2026-05-24 18:00:00'),
(2021, '308966', 'Md. Abdul Khalek', 'Senior Medical Promotion Officer', 'Ishurdi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994861', 'm.a.khalek.mithu@gmail.com', '2019-02-17', 1, '2026-05-24 18:00:00'),
(2022, '308969', 'Madan Mohan Haldar Hridoy', 'Senior Medical Promotion Officer', 'Mitford-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993194', 'hridoychowdhury254@gmail.com', '2019-02-16', 1, '2026-05-24 18:00:00'),
(2023, '308970', 'Mohammad Alamgir Hossain', 'Senior Medical Promotion Officer', 'Khulna-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995426', 'halamgir540@gmail.com', '2019-02-16', 1, '2026-05-24 18:00:00'),
(2024, '308971', 'Md. Tanvirul Islam', 'Senior Medical Promotion Officer', 'Gaibandha-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994616', 'tanvirtanu02@gmail.com', '2019-02-17', 1, '2026-05-24 18:00:00'),
(2025, '308975', 'Mafizur Rahman', 'Senior Medical Promotion Officer', 'Nabiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995425', 'mafizurrahman3401@gmail.com', '2019-02-17', 1, '2026-05-24 18:00:00'),
(2026, '308976', 'Nazrul Islam', 'Senior Medical Promotion Officer', 'Amirabad-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994979', 'nazrul.islam3089uni@gmail.com', '2019-02-16', 1, '2026-05-24 18:00:00'),
(2027, '308990', 'Liman Talukder', 'Senior Medical Promotion Officer', 'Shorupkathi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995582', 'talukderanu20@gmail.com', '2019-03-01', 1, '2026-05-24 18:00:00'),
(2028, '308991', 'Utpol kumar', 'Senior Medical Promotion Officer', 'Muktagacha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995511', 'utpol1000@gmail.com', '2019-02-28', 1, '2026-05-24 18:00:00'),
(2029, '308996', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Tarash', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995491', 'arifkbs95@gmail.com', '2019-03-14', 1, '2026-05-24 18:00:00'),
(2030, '308997', 'Md. Safiqul Islam', 'Senior Medical Promotion Officer', 'Kumarkhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995458', 'islamsafiqul44029@gmail.com', '2019-03-08', 1, '2026-05-24 18:00:00'),
(2031, '308998', 'Md. Billal Hossain', 'Senior Medical Promotion Officer', 'Bhandaria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995597', 'hmbillal500@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2032, '309000', 'Md. Sadequl Islam', 'Medical Promotion Officer', 'Ullapara-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995502', 'sadequl36@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2033, '309001', 'Md. Sakur Ali', 'Senior Medical Promotion Officer', 'Patkelghata', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995632', 'shakur.sky2018@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2034, '309002', 'Md. Abdullah Al Mushud', 'Senior Medical Promotion Officer', 'Nazipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995497', 'masudmunna992@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2035, '309003', 'Prodip Kumar Sarkar', 'Senior Medical Promotion Officer', 'Raigonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995492', 'prodip.eco11@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2036, '309004', 'Harun Or Rashid', 'Senior Medical Promotion Officer', 'Goalanda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995484', 'mdharunorroshid321@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2037, '309005', 'Md. Abdul Alim', 'Senior Medical Promotion Officer', 'Mirpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995459', 'alimkcu@gmail.com', '2019-03-08', 1, '2026-05-24 18:00:00'),
(2038, '309006', 'Mahfuzur Rahman', 'Senior Medical Promotion Officer', 'Kaliganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995460', 'mahfujar.rahman894@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2039, '309009', 'Md. Jonab Ali', 'Senior Medical Promotion Officer', 'Lohagara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995616', 'mdjonabali91@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2040, '309010', 'Md. Mojapser Hoshen', 'Senior Medical Promotion Officer', 'Baliakandi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995485', 'mojapser123@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2041, '309011', 'Md. Robiul Islam', 'Senior Medical Promotion Officer', 'Bandar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995649', 'mdrobiulislam57588@gmail.com', '2019-03-11', 1, '2026-05-24 18:00:00'),
(2042, '309012', 'Md. Mithu Mondol', 'Senior Medical Promotion Officer', 'Bagherpara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995617', 'mithu.unigroup@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2043, '309015', 'Md. Fysal Alam', 'Senior Medical Promotion Officer', 'Nanupur/Azadibazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995467', 'fysalalamss@gmail.com', '2019-03-07', 1, '2026-05-24 18:00:00'),
(2044, '309016', 'M. M. Mahady Hassan ', 'Senior Medical Promotion Officer', 'Boalmari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995487', 'mahadihassan21@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2045, '309017', 'Md. Abu Hanif', 'Senior Medical Promotion Officer', 'Kotchandpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995461', 'hanif.unigroup@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(2046, '309018', 'Md. Badsha Alamgir', 'Senior Medical Promotion Officer', 'Akkelpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995464', 'badshaalamgir3142@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2047, '309022', 'Faruk Hasan Lipon', 'Senior Medical Promotion Officer', 'Kaliganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995500', 'plfaruk7662@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2048, '309023', 'Raju Ahammad', 'Senior Medical Promotion Officer', 'Sadullapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995496', 'rajuahammad775@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2049, '309024', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Kalai', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995465', 'sohelranatkg2012@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2050, '309026', 'Md. Selim Reza', 'Senior Medical Promotion Officer', 'Dhunot', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995494', 'arifngn1@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2051, '309027', 'Md. Mazedur Rahman', 'Senior Medical Promotion Officer', 'Tanore', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996391', 'mazedurr178@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2052, '309031', 'Suman Kumar Saha', 'Senior Medical Promotion Officer', 'Shyamnagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993610', 'sksaha3610@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2053, '309032', 'Md. Saiful Islam Pavel', 'Senior Medical Promotion Officer', 'Zianagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995598', 'ahmedarnob2@gmail.com', '2019-03-10', 1, '2026-05-24 18:00:00'),
(2054, '309036', 'Sujit Kumar Sarker', 'Senior Medical Promotion Officer', 'Sirajganj-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994525', 'sujit.s12347@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2055, '309042', 'Dhandeb Adhikari', 'Senior Medical Promotion Officer', 'Natore-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994775', 'dhandebadhikari20@gmail.com', '2019-03-06', 1, '2026-05-24 18:00:00'),
(2056, '309045', 'Md. Nurmohammad', 'Senior Medical Promotion Officer', 'Khulna-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995452', 'nurshobuz93@gmail.com', '2019-03-07', 1, '2026-05-24 18:00:00'),
(2057, '309052', 'Abinash Kumar Roy', 'Senior Medical Promotion Officer', 'Char Muguria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994953', 'abinashroy784@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2058, '309055', 'Md. Raki Monoal', 'Senior Medical Promotion Officer', 'Rangpur-18', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995509', 'rokonmondal1991@gmail.com', '2019-03-09', 1, '2026-05-24 18:00:00'),
(2059, '309060', 'Md. Kamrul Hasan', 'Senior Area Sales Manager', 'DMCH-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993839', 'kamrul.mukter@gmail.com', '2019-03-03', 1, '2026-05-24 18:00:00'),
(2060, '309061', 'Md. Jahangir Kabir Sarker', 'Senior Area Sales Manager', 'Gobindaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995490', 'jahangirsarker2002@gmail.com', '2019-03-02', 1, '2026-05-24 18:00:00'),
(2061, '309063', 'Md. Rubeluzzaman Sheikh', 'Senior Medical Promotion Officer', 'Tekerhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995508', 'rubeluzzaman786.bd@gmail.com', '2019-03-07', 1, '2026-05-24 18:00:00'),
(2062, '309064', 'Md. Babon Ali', 'Senior Medical Promotion Officer', 'Mathbaria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995529', 'mdbabon1990@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2063, '309066', 'Sajib Ali', 'Senior Medical Promotion Officer', 'Gangni-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995621', 'sojibali101195@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2064, '309068', 'Md. Sadequl Islam', 'Senior Medical Promotion Officer', 'Kushtia-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995475', 'sadequlislamsumon2210@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2065, '309069', 'Prosanta Kumar Mahanta', 'Senior Medical Promotion Officer', 'Jibannagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995471', 'prashanta.unigroup@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2066, '309070', 'Md. Ajijur Rahman', 'Senior Medical Promotion Officer', 'BMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995514', 'ajijurrahman210@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2067, '309073', 'Md. Anowar Hossain', 'Senior Medical Promotion Officer', 'Bhedarganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995473', 'anowarhossain5400@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2068, '309074', 'Md. Shahinur Islam', 'Senior Medical Promotion Officer', 'Farazy Hospital-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995510', 'rtrshahin6@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2069, '309076', 'Asaduz Zaman', 'Senior Medical Promotion Officer', 'Damurhuda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995470', 'assaduzzamana447@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2070, '309079', 'Ashfak Ahmed', 'Senior Medical Promotion Officer', 'BMCH-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995513', 'ove65522@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2071, '309081', 'Bikash Mondol', 'Senior Medical Promotion Officer', 'Madaripur-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995479', 'mondolbikash707@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2072, '309082', 'Md. Faglur Rahaman', 'Senior Medical Promotion Officer', 'Gangni-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995622', 'fazlurrahman7956@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2073, '309083', 'Md. Sweet Hossain', 'Medical Promotion Officer', 'MuMC/Manda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995516', 'sweethossain50@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2074, '309084', 'Md. Saiful Islam', 'Medical Promotion Officer', 'Ghoraghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995504', 'mdsaifulislam@gmail.com', '2019-03-18', 1, '2026-05-24 18:00:00'),
(2075, '309085', 'Md. Zahurul Haque', 'Senior Medical Promotion Officer', 'DMCH-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995517', 'johurulislamjahi@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2076, '309088', 'Md. Al-Shahriar', 'Senior Medical Promotion Officer', 'SPRC', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994480', 'alshahriar631@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2077, '309089', 'Sujhit Kumar Ray', 'Senior Medical Promotion Officer', 'Haluaghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995330', 'sujhit.ray1990@gmail.com', '2019-03-18', 1, '2026-05-24 18:00:00'),
(2078, '309090', 'Faruque Hossain', 'Senior Medical Promotion Officer', 'Naria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995476', 'mfhmurad2@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2079, '309091', 'Md. Ershad Ali', 'Senior Medical Promotion Officer', 'Ulipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995499', 'mdershadali9019@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2080, '309094', 'Md. Abdur Nur', 'Medical Promotion Officer', 'Niamatpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995505', 'abdurnur.uup@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2081, '309095', 'Md. Arif Uddin Chowdhury', 'Senior Medical Promotion Officer', 'Moheshpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995618', 'mdarif8460@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2082, '309097', 'Md. Raju Miah', 'Senior Medical Promotion Officer', 'Damudya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995474', 'razuahamed9309@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2083, '309099', 'Pravas Chandra Roy', 'Senior Medical Promotion Officer', 'Nageshwari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995498', 'pravasroy1991@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2084, '309102', 'Md. Robiul Islam', 'Senior Medical Promotion Officer', 'Kalkini-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995480', 'islammdrobiul995@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2085, '309103', 'Md. Alamgir Hossain ', 'Senior Medical Promotion Officer', 'Mirpur-13', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996344', 'alamgir.unihealth@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2086, '309108', 'Md. Milon Babu', 'Senior Medical Promotion Officer', 'Biswanath-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994735', 'milonbabu23@gmail.com', '2019-03-19', 1, '2026-05-24 18:00:00'),
(2087, '309119', 'Tushar Kanti Roy', 'Senior Medical Promotion Officer', 'Lalmonirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995522', 'roytushar505@gmail.com', '2019-03-21', 1, '2026-05-24 18:00:00'),
(2088, '309120', 'Noor Alam', 'Senior Medical Promotion Officer', 'SWMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996318', 'alamnor2018@gmail.com', '2019-03-23', 1, '2026-05-24 18:00:00'),
(2089, '309126', 'Anowarul Islam', 'Senior Medical Promotion Officer', 'Patiya-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993658', 'aislam4917@gmail.com', '2019-03-16', 1, '2026-05-24 18:00:00'),
(2090, '309128', 'Md. Munjurul Islam', 'Senior Medical Promotion Officer', 'Rangpur-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995594', 'munjurul923@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2091, '309130', 'Md. Jahangir Alam', 'Senior Medical Promotion Officer', 'Jamalpur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995633', 'jahamgir.hirom@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2092, '309131', 'Madhusudan Chandra Roy', 'Senior Medical Promotion Officer', 'Tetulia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995584', 'madhusudanroy778@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2093, '309133', 'Md. Nazrul Islam', 'Senior Medical Promotion Officer', 'Faridgonj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995639', 'nazrul625292@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2094, '309134', 'Md. Kayum Bahadur', 'Senior Medical Promotion Officer', 'Panchar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996396', 'mdranab9@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2095, '309136', 'Md. Rafat Khan', 'Senior Medical Promotion Officer', 'Maijdee-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995536', 'rafakhanrobin888@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2096, '309137', 'Md. Atikur Rahman', 'Senior Medical Promotion Officer', 'Monirampur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995558', 'atikleon27@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2097, '309138', 'Md. Monir Hosen', 'Medical Promotion Officer', 'Harinakundu', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995551', 'monirhosen@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2098, '309139', 'Md. Jahar Ali', 'Medical Promotion Officer', 'Sirajganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995585', 'mdjaharali1994@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2099, '309141', 'Abdullah-Al-Saeed', 'Senior Medical Promotion Officer', 'Habiganj-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995563', 'aumi.saeed@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2100, '309144', 'Abdul Malak', 'Medical Promotion Officer', 'Syedpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995587', 'abdulmalak520@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2101, '309146', 'Abdur Razzak', 'Senior Medical Promotion Officer', 'Chilmari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995566', 'razzakabdur9077@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2102, '309147', 'Md. Maminul Islam', 'Senior Medical Promotion Officer', 'Baliadangi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995586', 'maiminulislam211@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2103, '309149', 'Md. Motiur Rahman', 'Senior Medical Promotion Officer', 'Keshabpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995557', 'motiur89@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2104, '309150', 'Md. Enamul Haqu', 'Senior Medical Promotion Officer', 'Chougachha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995554', 'menamul@156gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2105, '309152', 'Rabiul dewan', 'Senior Medical Promotion Officer', 'Shalikha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995552', 'rabiuldewan000@gmail.com', '2019-03-30', 1, '2026-05-24 18:00:00'),
(2106, '309157', 'Md. Suyel Rana', 'Senior Medical Promotion Officer', 'Mohadevpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995434', 'suyelrana8455@gmail.com', '2019-04-07', 1, '2026-05-24 18:00:00'),
(2107, '309158', 'Aktarul Islam', 'Senior Medical Promotion Officer', 'Khagrachhari-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995634', 'aktarulislam251092@gmail.com', '2019-04-14', 1, '2026-05-24 18:00:00'),
(2108, '309159', 'Mohammad Habib Ullah', 'Senior Area Sales Manager', 'Mirpur-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993501', 'habibullah.gsk@gmail.com', '2019-03-23', 1, '2026-05-24 18:00:00'),
(2109, '309160', 'Md. Kamruzzaman', 'Senior Area Sales Manager', 'BSMMU-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995512', 'kzaman.md@gmail.com', '2019-03-16', 1, '2026-05-24 18:00:00'),
(2110, '309171', 'Rubel Chandra Nath', 'Senior Medical Promotion Officer', 'Begumganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995571', 'rubelnath345@gmail.com', '2019-04-09', 1, '2026-05-24 18:00:00'),
(2111, '309173', 'Md. Uzzal Hossein', 'Senior Medical Promotion Officer', 'Tangail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994351', 'uzzal190892@gmail.com', '2019-04-15', 1, '2026-05-24 18:00:00'),
(2112, '309175', 'Md. Zillur Rahman', 'Senior Medical Promotion Officer', 'Labaid-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993443', 'rahmanzillurbd77@gmail.com', '2019-04-22', 1, '2026-05-24 18:00:00'),
(2113, '309177', 'A.K.M. Mosharaf Hossain', 'Senior Medical Promotion Officer', 'LabAid', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994821', 'akmhossain67@gmail.com', '2019-04-23', 1, '2026-05-24 18:00:00'),
(2114, '309186', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Rangpur-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995544', 'rashedislam.unigroup@gmail.com', '2019-04-04', 1, '2026-05-24 18:00:00'),
(2115, '309187', 'Md. Rakimul Islam Hemel ', 'Senior Medical Promotion Officer', 'Madarganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995610', 'hemel096@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2116, '309188', 'Suzan Kumar Kuthal', 'Senior Medical Promotion Officer', 'Patgram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995543', 'suzankumarkuthal@gmail.com', '2019-04-04', 1, '2026-05-24 18:00:00'),
(2117, '309189', 'Md. Amirul Islam', 'Senior Medical Promotion Officer', 'Tangail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993306', 'mdmirulislam93bd@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2118, '309190', 'Md. Mariful Islam', 'Senior Medical Promotion Officer', 'Pirganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996315', 'marufphy12@gmail.com', '2019-04-04', 1, '2026-05-24 18:00:00'),
(2119, '309191', 'Md. Muktar Hossain', 'Senior Medical Promotion Officer', 'Raipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995607', 'bdmuktar330@gmail.com', '2019-04-04', 1, '2026-05-24 18:00:00'),
(2120, '309201', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Kalisuri', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995604', 'mdarifulislamunigroup@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2121, '309204', 'Md. Abdur Razzak', 'Senior Medical Promotion Officer', 'Bhulta-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995539', 'razzak.1091@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2122, '309207', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Kurigram-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995548', 'ranaunimed@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2123, '309210', 'Md. Mahabube Rabbani', 'Senior Medical Promotion Officer', 'Magura-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995602', 'rm7138922@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2124, '309211', 'Md. Ziyaul Haque', 'Senior Medical Promotion Officer', 'Jhenaidah-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995600', 'ziyaulhaque.unimed@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2125, '309213', 'Md. Ahsan Habib', 'Senior Medical Promotion Officer', 'Bogra-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995589', 'ahsanhabib8659@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2126, '309216', 'Md. Kajol Khandoker', 'Senior Medical Promotion Officer', 'Narayanganj-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995540', 'kajol21206@gmail.com', '2019-04-06', 1, '2026-05-24 18:00:00'),
(2127, '309224', 'Md. Rokun Uzzaman', 'Senior Medical Promotion Officer', 'Shorupkathi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996338', 'mdrokon631@gmail.com', '2019-04-29', 1, '2026-05-24 18:00:00'),
(2128, '309225', 'Md. Mazaharul Islam', 'Senior Medical Promotion Officer', 'Shyamgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996327', 'mazaharuil@gmail.com', '2019-04-29', 1, '2026-05-24 18:00:00'),
(2129, '309230', 'Md. Ashikur Rahman', 'Medical Promotion Officer', 'Manikganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995647', 'ashiksarkarraj597@gmail.com', '2019-04-28', 1, '2026-05-24 18:00:00'),
(2130, '309231', 'Md. Mamun Mia', 'Senior Medical Promotion Officer', 'Iswarganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996322', 'mmia38595@gmail.com', '2019-04-28', 1, '2026-05-24 18:00:00'),
(2131, '309233', 'Md. Shabuj Mia ', 'Senior Medical Promotion Officer', 'Kalkini-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995644', 'sahbuj24.8ang@gmail.com', '2019-04-29', 1, '2026-05-24 18:00:00'),
(2132, '309239', 'Md. Mehedi Hasan', 'Senior Medical Promotion Officer', 'Patuakhali-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996330', 'mehedihassan21397@gmail.com', '2019-04-27', 1, '2026-05-24 18:00:00'),
(2133, '309240', 'Rony Al Sohel', 'Senior Medical Promotion Officer', 'Mymensingh-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996321', 'ronyalsohel@gmail.com', '2019-04-28', 1, '2026-05-24 18:00:00'),
(2134, '309241', 'Md. Tarikul Islam', 'Senior Medical Promotion Officer', 'Moheshpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995643', 'mdiarikull481@gmail.com', '2019-04-27', 1, '2026-05-24 18:00:00'),
(2135, '309244', 'Md. Sadekul Islam', 'Senior Medical Promotion Officer', 'Pabna-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996313', 'sanational77@gmail.com', '2019-04-28', 1, '2026-05-24 18:00:00'),
(2136, '309253', 'Md. Rubel Hossain', 'Senior Medical Promotion Officer', 'Faridpur-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996310', 'mdrubelhossainump@gmail.com', '2019-04-28', 1, '2026-05-24 18:00:00'),
(2137, '309254', 'Md. Mostafizur Rahman', 'Senior Medical Promotion Officer', 'Narayanganj-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995636', 'thsmostafizur94@gmail.com', '2019-04-29', 1, '2026-05-24 18:00:00'),
(2138, '309255', 'Haripada Chandra Shil', 'Assistant Manager, Distribution', 'Jashore Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993031', 'harpada.shil@unigroup-bd.com', '2019-04-01', 1, '2026-05-24 18:00:00'),
(2139, '309256', 'Mohammad Sultan Mamun Bhuiya', 'Assistant Sales Manager', 'Feni', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995592', 'sultan.mamun@unigroup-bd.com', '2019-04-11', 1, '2026-05-24 18:00:00'),
(2140, '309257', 'Manik Chandra Saha Chowdhury', 'Senior Area Sales Manager', 'DMCH-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995573', 'manikchy77@gmail.com', '2019-04-01', 1, '2026-05-24 18:00:00'),
(2141, '309258', 'Zillur Rahman', 'Assistant Sales Manager', 'Khulna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995572', 'zillur.rahman@unigroup-bd.com', '2019-04-01', 1, '2026-05-24 18:00:00'),
(2142, '309259', 'Majidul Islam Khan', 'Senior Area Sales Manager', 'Mathbaria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995574', 'abrarmajid2015@gmail.com', '2019-04-09', 1, '2026-05-24 18:00:00'),
(2143, '309266', 'Tajbidul Islam', 'Senior Medical Promotion Officer', 'SHSMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994840', 'tajtajbidul@gmail.com', '2019-05-02', 1, '2026-05-24 18:00:00'),
(2144, '309267', 'Shipon Sarkar', 'Senior Medical Promotion Officer', 'Paikpara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994747', 'shiponsarkar36unigroup@gmail.com', '2019-05-21', 1, '2026-05-24 18:00:00'),
(2145, '309268', 'Eliss Sarker', 'Senior Medical Promotion Officer', 'Rangpur-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995646', 'sarkar6102@gmail.com', '2019-05-02', 1, '2026-05-24 18:00:00'),
(2146, '309272', 'Md. Mizanur Rahman', 'Senior Medical Promotion Officer', 'Madhabpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993747', 'mdmizanurrahman3412@gmail.com', '2019-04-29', 1, '2026-05-24 18:00:00'),
(2147, '309291', 'Md. Rasel Rana', 'Senior Medical Promotion Officer', 'Banasree-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993383', 'robinahmedrasel8@gmail.com', '2019-05-09', 1, '2026-05-24 18:00:00'),
(2148, '309292', 'Md. Anwarul Islam', 'Senior Medical Promotion Officer', 'Halishahar-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996341', 'anwarulislam997@gmail.com', '2019-05-21', 1, '2026-05-24 18:00:00'),
(2149, '309304', 'Nazmul Islam', 'Senior Medical Promotion Officer', 'Pagla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994239', 'nazmul050394@gmail.com', '2019-05-25', 1, '2026-05-24 18:00:00'),
(2150, '309307', 'Md. Mujharul Islam', 'Senior Medical Promotion Officer', 'Narayanganj-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996309', 'mujharul94@gmail.com', '2019-05-25', 1, '2026-05-24 18:00:00'),
(2151, '309310', 'Krishno Kumar Roy', 'Senior Medical Promotion Officer', 'Charkhai', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996710', 'roykumar01797@gmail.com', '2019-05-23', 1, '2026-05-24 18:00:00'),
(2152, '309321', 'Md. Emon Ali Sarder', 'Senior Medical Promotion Officer', 'Brahmanbaria-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995292', 'emon20506@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2153, '309326', 'Abdur Rahim', 'Senior Medical Promotion Officer', 'Mirpur-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996382', 'abdurrahimccy1@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2154, '309328', 'Md. Jahangir Alam', 'Senior Medical Promotion Officer', 'Meherpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996395', 'jahangiralam6433@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2155, '309329', 'Obaidur Rahman', 'Senior Medical Promotion Officer', 'Rangpur-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996367', 'joyhasan1717@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2156, '309330', 'Md. Abu Shamim', 'Senior Medical Promotion Officer', 'B.Baria-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993788', 'ashreza50@gmail.com', '2019-06-09', 1, '2026-05-24 18:00:00'),
(2157, '309341', 'Md. Manirul Islam', 'Senior Medical Promotion Officer', 'Koyra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996348', 'mdmanirulel74@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2158, '309343', 'Md. Rafiqul Islam', 'Medical Promotion Officer', 'Nalitabari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995248', 'rafiqulislam297591@gmail.com', '2019-06-10', 1, '2026-05-24 18:00:00'),
(2159, '309346', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Demra/Staff Quarter/Sarulia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993535', 'aminulmahim49@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2160, '309350', 'Md. Mahmudul Hasan', 'Senior Medical Promotion Officer', 'Alexander-Ramgati', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995550', 'mahamuduha681@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2161, '309351', 'Md. Monirul Islam', 'Senior Medical Promotion Officer', 'Sreepur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995472', 'mdmonirulislam261993@gmail.com', '2019-06-08', 1, '2026-05-24 18:00:00'),
(2162, '309353', 'Mohammad Shafiqul Islam', 'Senior Key Accounts Manager', 'Oncology', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993963', 'shafijanssen@gmail.com', '2019-06-13', 1, '2026-05-24 18:00:00'),
(2163, '309354', 'Daben Sarkar', 'Senior Medical Promotion Officer', 'Rajshahi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996392', 'dabensarkar3433@gmail.com', '2019-07-02', 1, '2026-05-24 18:00:00'),
(2164, '309359', 'Md. Ajanur Rahman', 'Senior Medical Promotion Officer', 'Kallyanpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994551', 'mdajanurrahman@gmail.com', '2019-06-29', 1, '2026-05-24 18:00:00'),
(2165, '309365', 'Md. Abdul Latif', 'Senior Medical Promotion Officer', 'Gohira', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994220', 'malatif.bd1990@gmail.com', '2019-06-29', 1, '2026-05-24 18:00:00'),
(2166, '309366', 'Meer Shahidul Islam', 'Senior Medical Promotion Officer', 'Central-2/Padma D/C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994787', 'meershahidul33@gmail.com', '2019-06-26', 1, '2026-05-24 18:00:00'),
(2167, '309372', 'Md. Ashrafuzzaman', 'Senior Medical Promotion Officer', 'Tangail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994635', 'arifb8311@gmail.com', '2019-07-27', 1, '2026-05-24 18:00:00'),
(2168, '309379', 'Md. Sharful Islam', 'Senior Medical Promotion Officer', 'Sirajdikhan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995538', 'sharfulunigroup1989@gmail.com', '2019-07-24', 1, '2026-05-24 18:00:00'),
(2169, '309380', 'Dibakar Kumar Kundu', 'Senior Medical Promotion Officer', 'Savar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994516', 'lonelydibakar@gmail.com', '2019-07-27', 1, '2026-05-24 18:00:00'),
(2170, '309383', 'Shamim Ahmed', 'Senior Medical Promotion Officer', 'Kachua-2/Rahimanagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994985', 'ahmed.shamim.009uni@gmail.com', '2019-07-25', 1, '2026-05-24 18:00:00'),
(2171, '309384', 'Md. Atikur Rahman', 'Senior Medical Promotion Officer', 'Comilla City-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993944', 'atikurrahmannu1991@gmail.com', '2019-07-27', 1, '2026-05-24 18:00:00'),
(2172, '309387', 'Md Abdus Samad Azad', 'Senior Medical Promotion Officer', 'Madhukhali-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994312', 'mdsamadabdus30@gmail.com', '2019-07-30', 1, '2026-05-24 18:00:00'),
(2173, '309389', 'Md. Mehedul Islam', 'Senior Medical Promotion Officer', 'Popular SG-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996769', 'mehedi09121995@gmail.com', '2019-07-31', 1, '2026-05-24 18:00:00'),
(2174, '309390', 'Md. Saddam Hossen', 'Senior Medical Promotion Officer', 'Debidwar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995312', 'shossen06@gmail.com', '2019-07-29', 1, '2026-05-24 18:00:00'),
(2175, '309391', 'Md. Sahin Ali', 'Senior Medical Promotion Officer', 'Tungipara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996304', 'sahin701835@gmail.com', '2019-08-01', 1, '2026-05-24 18:00:00'),
(2176, '309395', 'Md. Golam Kibria', 'Senior Medical Promotion Officer', 'Jafargonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993767', 'skill58963@gmail.com', '2019-07-22', 1, '2026-05-24 18:00:00'),
(2177, '309397', 'Md. Shaju Rahaman', 'Senior Medical Promotion Officer', 'Daulatpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994660', 'shajurahman111@gmail.com', '2019-07-23', 1, '2026-05-24 18:00:00'),
(2178, '309400', 'Md. Mehedi Hasan', 'Senior Medical Promotion Officer', 'WMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996709', 'mehedisobuj0@gmail.com', '2019-07-23', 1, '2026-05-24 18:00:00'),
(2179, '309413', 'Md. Abu Saeid ', 'Senior Medical Promotion Officer', 'SSMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994986', 'abusobuz77@gmail.com', '2019-07-22', 1, '2026-05-24 18:00:00'),
(2180, '309415', 'Md. Badal Mia', 'Senior Medical Promotion Officer', 'Dinajpur-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995448', 'mdbadalon70@gmail.com', '2019-08-01', 1, '2026-05-24 18:00:00'),
(2181, '309417', 'Shahriyar Khan', 'Senior Medical Promotion Officer', 'Jessore-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996303', 'shahriyar88@gmail.com', '2019-08-01', 1, '2026-05-24 18:00:00'),
(2182, '309418', 'Kalachand Roy', 'Senior Medical Promotion Officer', 'DMCH-D4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995389', 'kcroy790@gmail.com', '2019-08-01', 1, '2026-05-24 18:00:00'),
(2183, '309424', 'Md. Mokhlesur Rahman', 'Senior Area Sales Manager', 'Sylhet-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996704', 'mokhlesurrahman658@gmail.com', '2019-07-10', 1, '2026-05-24 18:00:00'),
(2184, '309426', 'Md. Nuruzzaman ', 'Senior Area Sales Manager', 'Jassore-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994495', 'nzaman.shiplu@gmail.com', '2019-07-10', 1, '2026-05-24 18:00:00'),
(2185, '309427', 'Md. Motiar Rahman', 'Senior Area Sales Manager', 'Mirpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996706', 'motiarunihealth@gmail.com', '2019-07-10', 1, '2026-05-24 18:00:00'),
(2186, '309428', 'Md. Shah Jahan', 'Senior Area Sales Manager', 'Panchlaish', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993051', 'mohammadshahjahancox@gmail.com', '2019-07-10', 1, '2026-05-24 18:00:00'),
(2187, '309429', 'Mohammad Shahidul Islam ', 'Senior Area Sales Manager', 'Birampur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994403', 'mdshahid.unigroup@gmail.com', '2019-07-10', 1, '2026-05-24 18:00:00'),
(2188, '309431', 'Abdul Halim Sheikh', 'Senior Area Sales Manager', 'Rajshahi-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996705', 'skhalim0@gmail.com', '2019-07-14', 1, '2026-05-24 18:00:00'),
(2189, '309432', 'Md. Juwel Mollah Raju', 'Medical Promotion Officer', 'Feni-9', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996786', 'juwel.sir@gmail.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2190, '309435', 'Md. Ebrahim', 'Senior Medical Promotion Officer', 'Gournadi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995332', 'ebrahim41.bd@yahoo.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2191, '309436', 'Md. Aminul Islam', 'Senior Medical Promotion Officer', 'Pathorghata', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993932', 'rajumia7826@gmail.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2192, '309438', 'Monir Hossain', 'Senior Medical Promotion Officer', 'Ramganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994888', 'monirhossain08090@gmail.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2193, '309439', 'Md. Rifatul Islam Bhuyan', 'Senior Medical Promotion Officer', 'Faridpur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994596', 'brifat50@gmail.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2194, '309440', 'Swapon Kumar Ray', 'Senior Medical Promotion Officer', 'Kulaura-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994114', 'swaponroy014@gmail.com', '2019-08-17', 1, '2026-05-24 18:00:00'),
(2195, '309441', 'Md. Rasel Mahmud', 'Senior Medical Promotion Officer', 'Belabo', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996883', 'raselmahmud4200@gmail.com', '2019-09-01', 1, '2026-05-24 18:00:00'),
(2196, '309442', 'Md. Bianul Islam', 'Senior Medical Promotion Officer', 'Tongibari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995191', 'bianulislam900@gmail.com', '2019-09-01', 1, '2026-05-24 18:00:00'),
(2197, '309443', 'Md. Miragul Islam', 'Senior Medical Promotion Officer', 'Chakaria-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996717', 'miragul4016@gmail.com', '2019-09-01', 1, '2026-05-24 18:00:00'),
(2198, '309444', 'Md. Tofazzel Hossain', 'Senior Medical Promotion Officer', 'Chalna,Dakop', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995352', 'tofazzel.hossain@gmail.com', '2019-09-01', 1, '2026-05-24 18:00:00'),
(2199, '309458', 'Uttom Kumar', 'Senior Medical Promotion Officer', 'Jamidar Hat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994932', 'kumarnirob329@gmail.com', '2019-09-18', 1, '2026-05-24 18:00:00'),
(2200, '309459', 'Md. Shahin Alam', 'Senior Medical Promotion Officer', 'Dinajpur-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993699', 'shahinalamshahin663@gmail.com', '2019-09-18', 1, '2026-05-24 18:00:00'),
(2201, '309460', 'Md. Omar Faruk', 'Senior Medical Promotion Officer', 'Sharsha-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996737', 'omarfaruk1810993@gmail.com', '2019-09-18', 1, '2026-05-24 18:00:00'),
(2202, '309465', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'Boalmari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994562', 'mdarifulislam909573@gmail.com', '2019-09-21', 1, '2026-05-24 18:00:00'),
(2203, '309466', 'Md. Amirul Islam', 'Senior Medical Promotion Officer', 'Khalispur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993628', 'amirulislam2006dakhil@gmail.com', '2019-09-21', 1, '2026-05-24 18:00:00'),
(2204, '309470', 'Md. Rashedul Islam', 'Senior Medical Promotion Officer', 'Benapole', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993638', 'rashedulislamunigroup@gmail.com', '2019-09-21', 1, '2026-05-24 18:00:00'),
(2205, '309484', 'Md. Bakhtiar Uddin', 'Senior Medical Promotion Officer', 'Cantonment-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994811', 'saddammdbakhtiaruddin@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2206, '309485', 'Md. Mamunur Rashid', 'Medical Promotion Officer', 'Santahar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993650', 'mamunroshid309485@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2207, '309492', 'Joydeb Kumar Roy', 'Senior Medical Promotion Officer', 'Mohadevpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996719', 'joydebkumar1994@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2208, '309496', 'Md. Sahanul Alam', 'Senior Medical Promotion Officer', 'Naldanga', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996739', 'sahanul.unigroup@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2209, '309502', 'Md. Sagar Ali', 'Senior Medical Promotion Officer', 'Satkhira', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995375', 'mdsagara954@gmail.com', '2019-10-13', 1, '2026-05-24 18:00:00'),
(2210, '309503', 'Md. Aynal Haque', 'Senior Medical Promotion Officer', 'Ullapara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994414', 'aynal2u@gmail.com', '2019-10-12', 1, '2026-05-24 18:00:00'),
(2211, '309505', 'Md. Hasan Reza', 'Medical Promotion Officer', 'Badalgachi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995463', 'rmdhasan781@gmail.com', '2019-10-13', 1, '2026-05-24 18:00:00'),
(2212, '309506', 'Sumir Kumar Sharkar', 'Senior Medical Promotion Officer', 'Chhatak-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995258', 'sumirsharkar1989@gmail.com', '2019-10-12', 1, '2026-05-24 18:00:00'),
(2213, '309507', 'Md. Morsalin Islam', 'Senior Medical Promotion Officer', 'Syedpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995373', 'morsalinislam49@gmail.com', '2019-10-13', 1, '2026-05-24 18:00:00'),
(2214, '309510', 'Md. Jweel Hasan', 'Senior Medical Promotion Officer', 'Hizla', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993816', 'jweelhasan2013@gmai.com', '2019-10-12', 1, '2026-05-24 18:00:00'),
(2215, '309511', 'Md. Nazmul Hossain', 'Senior Medical Promotion Officer', 'Satkhira-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995449', 'nazmulhossain9791@gmail.com', '2019-10-12', 1, '2026-05-24 18:00:00'),
(2216, '309513', 'Dipu Chandra', 'Senior Medical Promotion Officer', 'Akhaura-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993952', 'diptobiswas387@gmail.com', '2019-10-10', 1, '2026-05-24 18:00:00'),
(2217, '309518', 'Md. Mohobbot Hosen', 'Senior Medical Promotion Officer', 'Tangail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995165', 'mohobbotbg@gmail.com', '2019-10-13', 1, '2026-05-24 18:00:00'),
(2218, '309520', 'Md. Saiful Islam', 'Senior Medical Promotion Officer', 'CMOCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995131', 'saiful19943112@mail.com', '2019-10-10', 1, '2026-05-24 18:00:00'),
(2219, '309529', 'Md. Shafiqul Islam', 'Area Sales Manager', 'Naogaon-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994671', 'shafiqul11686867@gmail.com', '2019-10-16', 1, '2026-05-24 18:00:00'),
(2220, '309530', 'Kazi Alimuzzaman', 'Adviser', 'Corporate Office', 'UniMed UniHealth Fine Chemicals Limited', 'General  Administration', '01929993061', 'alimuz.zaman@unigroup-bd.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2221, '309531', 'Md. Kamruzzaman', 'Senior Medical Promotion Officer', 'Terokhada', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993534', 'kamruzzamansamim7@gmail.com', '2019-10-12', 1, '2026-05-24 18:00:00'),
(2222, '309537', 'Ahsan-Uz-Zaman', 'Senior Area Sales Manager', 'BSMMU-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994288', 'ahsanuzzaman76@gmail.com', '2019-09-10', 1, '2026-05-24 18:00:00'),
(2223, '309541', 'Shyed Mehedi Hashan', 'Senior Area Sales Manager', 'Mugda-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996720', 'mehedi.adhora@gmail.com', '2019-09-25', 1, '2026-05-24 18:00:00'),
(2224, '309542', 'Md. Zoadul Karim Khan', 'Assistant Sales Manager', 'Sylhet', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996732', 'zoadul.karim@unigroup-bd.com', '2019-10-22', 1, '2026-05-24 18:00:00'),
(2225, '309544', 'Atish Chandra Mahapatra', 'Senior Area Sales Manager', 'Sherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996744', 'atishmahapatra.1978@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2226, '309545', 'Razibul Haque', 'Senior Area Sales Manager', 'Narail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993203', 'razibulh02@gmail.com', '2019-10-06', 1, '2026-05-24 18:00:00'),
(2227, '309546', 'Md. Asraful Alam', 'Senior Area Sales Manager', 'BIRDEM-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996730', 'asrafulalammd@gmail.com', '2019-10-05', 1, '2026-05-24 18:00:00'),
(2228, '309547', 'Mohd. Monowarul Islam Bhuiyan', 'General Manager, Sales', 'Central North Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993145', 'monowarul.islam@unigroup-bd.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(2229, '309549', 'Abu Khaled Mohammed Rahim Uddin', 'Assistant General Manager, Sales', 'East Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996755', 'rahim.uddin@unigroup-bd.com', '2019-11-06', 1, '2026-05-24 18:00:00'),
(2230, '309550', 'Md. Taharul Islam', 'Deputy Sales Manager', 'Chittagong-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993125', 'taharul.islam@unigroup-bd.com', '2019-12-26', 1, '2026-05-24 18:00:00'),
(2231, '309553', 'Khan Md. Shafiullah', 'Senior Area Sales Manager', 'BMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995546', 'sp.shafiullah25@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2232, '309554', 'Md. Shaifuddin', 'Senior Area Sales Manager', 'Chakaria-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996830', 'saifbeacon79@gmail.com', '2020-02-05', 1, '2026-05-24 18:00:00'),
(2233, '309556', 'Rajib Rudra', 'Senior Area Sales Manager', 'Keranirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996766', 'rajib737445862@gmail.com', '2020-02-04', 1, '2026-05-24 18:00:00'),
(2234, '309559', 'Dipankar Datta', 'Senior Area Sales Manager', 'Uttara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993320', 'dipdatta2012@gmail.com', '2020-02-23', 1, '2026-05-24 18:00:00'),
(2235, '309562', 'Md. Badal Mia', 'Senior Area Sales Manager', 'SHSMCH-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996800', 'badolmia97@gmail.com', '2020-02-25', 1, '2026-05-24 18:00:00'),
(2236, '309564', 'Md. Yasir Arafat Akhonda', 'Senior Area Sales Manager', 'CSCR', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993587', 'yasirarafatfx@gmail.com', '2020-03-01', 1, '2026-05-24 18:00:00'),
(2237, '309566', 'Md. Anisur Rahman Khan', 'Assistant Sales Manager', 'Moulvibazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996937', 'anisur.khan@unigroup-bd.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2238, '309567', 'Biswajit Barua', 'Senior Area Sales Manager', 'Jamal Khan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996933', 'biswojitbarua@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2239, '309571', 'Md. Sahadat Hossain', 'Senior Area Sales Manager', 'Comilla City-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996942', 'shahadat78.d16@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2240, '309577', 'Md. Zahidul Islam', 'Assistant Sales Manager', 'Bogra North', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996949', 'zahidul.d16@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2241, '309582', 'Muhammad Jahurul Islam', 'Senior Area Sales Manager', 'Mitford-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994042', 'jahirge66@gmail.com', '2021-08-10', 1, '2026-05-24 18:00:00'),
(2242, '309585', 'Kawser Ahmmad', 'Senior Area Sales Manager', 'DNMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993619', 'kawserahmad1980@gmail.com', '2021-09-11', 1, '2026-05-24 18:00:00'),
(2243, '309586', 'Md. Abul Bashar', 'Senior Area Sales Manager', 'Chittagong-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993536', 'bashar79skfbd@gmail.com', '2021-09-12', 1, '2026-05-24 18:00:00'),
(2244, '309588', 'Kowser Hayat', 'Senior Area Sales Manager', 'Uttara-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993490', 'kowserhayat77@gmail.com', '2021-09-18', 1, '2026-05-24 18:00:00'),
(2245, '309591', 'Md. Aminur Rahman', 'Senior Area Sales Manager', 'Jamalpur-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993573', 'aminur.rahman309591@gmail.com', '2021-10-02', 1, '2026-05-24 18:00:00'),
(2246, '309593', 'Belaet Ali', 'Senior Area Sales Manager', 'Chittagong-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993088', 'belaet.ali@gmail.com', '2021-10-09', 1, '2026-05-24 18:00:00'),
(2247, '309596', 'Md. Faiqul Alam', 'Senior Area Sales Manager', 'NICVD', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994638', 'faiqulalam36@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(2248, '309599', 'Md. Aminur Rahman', 'Senior Area Sales Manager', 'Jessore', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993533', 'arbiswashs@gmail.com', '2022-08-17', 1, '2026-05-24 18:00:00'),
(2249, '309609', 'Shafiqul Islam', 'Senior Medical Promotion Officer', 'Laksam-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996842', 'sobujkhan6370@gmail.com', '2019-11-05', 1, '2026-05-24 18:00:00'),
(2250, '309614', 'Nayon Kumar Karmokar', 'Senior Medical Promotion Officer', 'Mymensingh-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996840', 'nayonkarmokar066@gmail.com', '2019-10-27', 1, '2026-05-24 18:00:00'),
(2251, '309615', 'Md. Abu Tahar', 'Senior Medical Promotion Officer', 'UAMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993939', 'abutaher1827@gmail.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(2252, '309618', 'Sohel Rana', 'Senior Medical Promotion Officer', 'Faridpur-D4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995489', 'sohelru1581@gmail.com', '2019-11-06', 1, '2026-05-24 18:00:00'),
(2253, '309619', 'Md. Jahirul Islam', 'Senior Medical Promotion Officer', 'KBFH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994622', 'jahirul1541720@gmail.com', '2019-11-07', 1, '2026-05-24 18:00:00'),
(2254, '309626', 'Md. Almas Ali', 'Senior Medical Promotion Officer', 'Kakrail-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993426', 'mdalmasalil187@gmai.com', '2019-11-09', 1, '2026-05-24 18:00:00'),
(2255, '309629', 'Md. Wobaydur Rahman', 'Senior Medical Promotion Officer', 'Khulna-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993837', 'wabaydurrahman@gmail.com', '2019-11-07', 1, '2026-05-24 18:00:00'),
(2256, '309630', 'Md. Salman Mehedi', 'Senior Medical Promotion Officer', 'MuMC/Khidmah-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993423', 'salmanmehedi16@gmail.com', '2019-11-07', 1, '2026-05-24 18:00:00'),
(2257, '309632', 'Md. Raihan Khan', 'Senior Medical Promotion Officer', 'Anwara-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993633', 'raihankhank97@gmail.com', '2019-10-29', 1, '2026-05-24 18:00:00'),
(2258, '309637', 'Md. Sohel - Rana', 'Senior Medical Promotion Officer', 'DMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995404', 'mds04239@gmail.com', '2019-10-26', 1, '2026-05-24 18:00:00'),
(2259, '309639', 'Md. Imran Hossen', 'Senior Medical Promotion Officer', 'SHSMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995537', 'imran78153@gmail.com', '2019-11-09', 1, '2026-05-24 18:00:00'),
(2260, '309644', 'Md. Liakat Ali', 'Senior Medical Promotion Officer', 'Bhanga-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993715', 'liakatalimpo@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2261, '309645', 'Md. Almahmud', 'Senior Medical Promotion Officer', 'Gournadi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996748', 'almahmudsheikh9930@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2262, '309646', 'Sanjit Kumar Sarker', 'Senior Medical Promotion Officer', 'Gopalganj-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996742', 'sanjitsarker749@gmail.com', '2019-11-17', 1, '2026-05-24 18:00:00'),
(2263, '309648', 'Md. Habibur Rahman', 'Senior Medical Promotion Officer', 'Laksam-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995394', 'farabirahman6666@gmail.com', '2019-11-17', 1, '2026-05-24 18:00:00'),
(2264, '309653', 'Md. Moniruzzaman', 'Senior Medical Promotion Officer', 'Mirpur DOHS', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996736', 'moniruzzaman9890@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2265, '309654', 'Md. Rafsan Jamil', 'Senior Medical Promotion Officer', 'Palash', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996796', 'rafsane13@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2266, '309657', 'Morsadul Islam', 'Senior Medical Promotion Officer', 'Comilla City-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996746', 'morsadulislam56@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2267, '309660', 'Md. Abdur Rahim', 'Senior Medical Promotion Officer', 'Kishoreganj-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994324', 'abdurrahim.kg92@gmail.com', '2019-11-16', 1, '2026-05-24 18:00:00'),
(2268, '309662', 'Md. Abu Musa', 'Senior Medical Promotion Officer', 'Bonarpara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995304', 'musamath01@gmail.com', '2019-11-28', 1, '2026-05-24 18:00:00'),
(2269, '309663', 'Md. Abdul Helim', 'Senior Medical Promotion Officer', 'Bandura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996764', 'abdulhelim69@gmail', '2019-11-25', 1, '2026-05-24 18:00:00'),
(2270, '309664', 'Md. Tahidul Islam', 'Area Sales Manager', 'Rajshahi-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996921', 'tahiduht177@yahoo.com', '2020-01-01', 1, '2026-05-24 18:00:00'),
(2271, '309666', 'Md. Sohrab Hossain', 'Senior Medical Promotion Officer', 'NMC-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996735', 'sobuj4136@gmail.com', '2019-11-19', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(2272, '309669', 'Md. Abdur Rauf Mia', 'Senior Medical Promotion Officer', 'B.Baria-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994358', 'abdurrauf@gmail.com', '2019-12-01', 1, '2026-05-24 18:00:00'),
(2273, '309671', 'Md. Lokman Hasan', 'Senior Medical Promotion Officer', 'Narayanpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996319', 'lokman7933@gmail.com', '2019-12-02', 1, '2026-05-24 18:00:00'),
(2274, '309673', 'Md. Ezab Uddin', 'Senior Medical Promotion Officer', 'GEC', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994391', 'md.ezabuddin@gmail.com', '2019-11-25', 1, '2026-05-24 18:00:00'),
(2275, '309676', 'Gopal Chandro Roy', 'Senior Medical Promotion Officer', 'Bheramara-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994413', 'gopalchandro93@gmail.com', '2019-12-02', 1, '2026-05-24 18:00:00'),
(2276, '309682', 'Md. Roushan Habib', 'Senior Medical Promotion Officer', 'Mirpur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993212', 'roushanhabib03@gmail.com', '2019-12-02', 1, '2026-05-24 18:00:00'),
(2277, '309684', 'Md. Almamun', 'Senior Medical Promotion Officer', 'Tangail-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996802', 'almamun.tgc20@gmail.com', '2019-12-28', 1, '2026-05-24 18:00:00'),
(2278, '309685', 'Md. Atier Rahman', 'Senior Medical Promotion Officer', 'Natore-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996935', 'atierrahman184@gmail.com', '2019-12-28', 1, '2026-05-24 18:00:00'),
(2279, '309687', 'Md. Milon Hossen', 'Senior Medical Promotion Officer', 'Birdem-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996805', 'milonhossenmr1995@gmail.com', '2019-12-28', 1, '2026-05-24 18:00:00'),
(2280, '309702', 'Md. Mahbubul Haque', 'Senior Medical Promotion Officer', 'Gulshan-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996762', 'rmrockaya255@gmail.com', '2019-12-28', 1, '2026-05-24 18:00:00'),
(2281, '309704', 'Md. Monirul Islam', 'Senior Medical Promotion Officer', 'Khulna-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996822', 'monirulislam.jhd@gmail.com', '2020-01-01', 1, '2026-05-24 18:00:00'),
(2282, '309705', 'Md. Al-Amin Akondo', 'Senior Medical Promotion Officer', 'Monohardi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996857', 'alaminbd.digital@gmail.com', '2020-01-03', 1, '2026-05-24 18:00:00'),
(2283, '309707', 'Md. Mostak Hossain', 'Senior Medical Promotion Officer', 'Rajbari-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993458', 'mostak.rangs@gmail.com', '2019-12-26', 1, '2026-05-24 18:00:00'),
(2284, '309709', 'Md. Abu Jahid', 'Senior Medical Promotion Officer', 'Agrabad', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994727', 'abuyahid687@gmail.com', '2019-12-23', 1, '2026-05-24 18:00:00'),
(2285, '309710', 'Md. Touhidul Islam', 'Medical Promotion Officer', 'Narayanganj-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993812', 'md.touhid4353@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2286, '309711', 'Debasish Barman', 'Senior Medical Promotion Officer', 'Noapara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995356', 'debasishmulia@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2287, '309712', 'Md. Khademul Islam', 'Medical Promotion Officer', 'Naogaon-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993544', 'rsumon35@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2288, '309716', 'Md. Solayman', 'Senior Medical Promotion Officer', 'Tongi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994603', 'solayman.honey@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2289, '309718', 'Md. Jillur Rahman', 'Senior Medical Promotion Officer', 'Munshiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993801', 'jillurjillur94@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2290, '309720', 'Md. Firoz Ahmed', 'Senior Medical Promotion Officer', 'Noapara-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996337', 'ahmedfiroj96@gmail.com', '2020-01-04', 1, '2026-05-24 18:00:00'),
(2291, '309721', 'Somar Kumar', 'Senior Medical Promotion Officer', 'Popular-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996782', 'as.somar.216@gmail.com', '2020-01-03', 1, '2026-05-24 18:00:00'),
(2292, '309723', 'Md. Thuin Munshi', 'Senior Medical Promotion Officer', 'Shibchar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996948', 'tuhinislam445@gmail.com', '2019-12-26', 1, '2026-05-24 18:00:00'),
(2293, '309724', 'Md. Hasanur Rahman', 'Medical Promotion Officer', 'Barisal-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996771', 'hasanur0923@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2294, '309726', 'Md. Sahaz Uddin', 'Senior Medical Promotion Officer', 'Fulbarigate', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996795', 'smnloysahaz@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2295, '309727', 'Ruhul Amin', 'Senior Medical Promotion Officer', 'Jawa Kaitak-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996761', 'ruhulak4@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2296, '309730', 'Md. Altab Hosen', 'Senior Medical Promotion Officer', 'SHSMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996779', 'hridayshahriar123@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2297, '309731', 'Md. Shukur Ali', 'Medical Promotion Officer', 'Taherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995233', 'sukurali1763@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2298, '309732', 'Md. Ilias', 'Senior Medical Promotion Officer', 'Companiganj-1/Basurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996768', 'hawladerelias90@gmail.com', '2020-01-24', 1, '2026-05-24 18:00:00'),
(2299, '309733', 'Md. Dipu Hossain', 'Medical Promotion Officer', 'MuMC/Kadamtola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995521', 'dipuhasan366@gmail.com', '2020-01-21', 1, '2026-05-24 18:00:00'),
(2300, '309735', 'Khadimul Islam', 'Senior Medical Promotion Officer', 'Pabna-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996765', 'khadimul374@gmail.com', '2020-01-22', 1, '2026-05-24 18:00:00'),
(2301, '309741', 'Pronab Kumar Saha', 'Senior Medical Promotion Officer', 'Dhaka Psychiatry', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996749', 'pronab.saha1990@gmail.com', '2020-01-13', 1, '2026-05-24 18:00:00'),
(2302, '309754', 'Md. Shamiul Alam', 'Senior Medical Promotion Officer', 'Chandpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996831', 'shamiulalam1993@gmail.com', '2020-01-26', 1, '2026-05-24 18:00:00'),
(2303, '309758', 'Juel Rana', 'Senior Medical Promotion Officer', 'Comilla-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994359', 'juelrana4391@gmail.com', '2020-01-26', 1, '2026-05-24 18:00:00'),
(2304, '309763', 'Abdur Rahim', 'Senior Medical Promotion Officer', 'Kamal Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994803', 'abdurrahimrubel681@gmail.com', '2020-01-25', 1, '2026-05-24 18:00:00'),
(2305, '309765', 'Md. Faysal', 'Area Sales Manager', 'Jhenaidah-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994965', 'faysalhossain531@gmail.com', '2020-01-25', 1, '2026-05-24 18:00:00'),
(2306, '309767', 'Md. Sayed Hasan', 'Senior Medical Promotion Officer', 'Monohargonj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996924', 'sayedhasan1106@gmail.com', '2020-02-04', 1, '2026-05-24 18:00:00'),
(2307, '309768', 'Md. Shafiqul Islam', 'Senior Medical Promotion Officer', 'JRRMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996892', 'swapanbabu073@gmail.com', '2020-02-05', 1, '2026-05-24 18:00:00'),
(2308, '309769', 'Dab Kuma Paul', 'Senior Medical Promotion Officer', 'Ramu-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995238', 'dev.unigroup@gmail.com', '2020-02-03', 1, '2026-05-24 18:00:00'),
(2309, '309771', 'Md. Manik Hossain', 'Senior Medical Promotion Officer', 'Pangsa-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996827', 'manikhasan020194@gmail.com', '2020-02-04', 1, '2026-05-24 18:00:00'),
(2310, '309774', 'Md. Ebrahim Ali', 'Senior Medical Promotion Officer', 'Hemayetpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996799', 'momrafi31@gmail.com', '2020-02-05', 1, '2026-05-24 18:00:00'),
(2311, '309775', 'Md. Abdur Razzak', 'Senior Medical Promotion Officer', 'Ashulia-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996803', 'mdabdurazzak0821@gmail.com', '2020-02-05', 1, '2026-05-24 18:00:00'),
(2312, '309777', 'Mohammad Jahid Hasan', 'Senior Medical Promotion Officer', 'Kutubdia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996792', 'zahiidhasan2850@gmail.com', '2020-02-05', 1, '2026-05-24 18:00:00'),
(2313, '309780', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'SMAMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995593', 'sohel.com15@gmail.com', '2020-02-02', 1, '2026-05-24 18:00:00'),
(2314, '309783', 'Md. Selim Hasan', 'Senior Medical Promotion Officer', 'Kashiani-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996941', 'selimhasan2225@gmail.com', '2020-02-20', 1, '2026-05-24 18:00:00'),
(2315, '309788', 'Md. Firoz Miah', 'Senior Medical Promotion Officer', 'Nangalkot-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996832', 'firoz.shazpz@gmail.com', '2020-02-19', 1, '2026-05-24 18:00:00'),
(2316, '309789', 'Md. Sarwar Hossain', 'Senior Medical Promotion Officer', 'Bhangura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996839', 'shsvshs2255@gmail.com', '2020-02-17', 1, '2026-05-24 18:00:00'),
(2317, '309790', 'Md. Bablu Hossain Khan', 'Senior Medical Promotion Officer', 'SWMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996944', 'sultankhan091992@gmail.com', '2020-02-19', 1, '2026-05-24 18:00:00'),
(2318, '309794', 'Abul Kalam', 'Senior Medical Promotion Officer', 'Dhamrai', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996836', 'abulkalam31210@gmail.com', '2020-02-18', 1, '2026-05-24 18:00:00'),
(2319, '309798', 'Md. Golam Mustafa', 'Senior Medical Promotion Officer', 'Fulgazi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996825', 'mdstafa867242@gmail.com', '2020-02-20', 1, '2026-05-24 18:00:00'),
(2320, '309800', 'Md. Nurul Islam', 'Senior Medical Promotion Officer', 'Bhaluka-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996947', 'mdnurulislambashir@gmail.com', '2020-02-20', 1, '2026-05-24 18:00:00'),
(2321, '309803', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'IBN SINA-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993688', 'sohelflc1@gmail.com', '2020-02-20', 1, '2026-05-24 18:00:00'),
(2322, '309807', 'Sakibul Hasan', 'Senior Medical Promotion Officer', 'Shamshernagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994583', 'sakibulh.648@gmail.com', '2020-02-27', 1, '2026-05-24 18:00:00'),
(2323, '309810', 'Md. Torikul Islam', 'Senior Medical Promotion Officer', 'BSMMU/Health & Hope', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994608', 'torikul3699@gmail.com', '2020-02-25', 1, '2026-05-24 18:00:00'),
(2324, '309811', 'Md. Tuhin Alom', 'Senior Medical Promotion Officer', 'Maijdee-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996867', 'mdtuhinalom777@gmail.com', '2020-02-26', 1, '2026-05-24 18:00:00'),
(2325, '309812', 'Md. Monjirul Islam', 'Senior Medical Promotion Officer', 'Devhata', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996834', 'islammonjirul671@gmail.com', '2020-02-27', 1, '2026-05-24 18:00:00'),
(2326, '309814', 'Md. Ziaur Rahman', 'Senior Medical Promotion Officer', 'Mitford-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994998', 'ziaislam848@gmail.com', '2020-02-26', 1, '2026-05-24 18:00:00'),
(2327, '309815', 'Md. Emarul Islam', 'Senior Medical Promotion Officer', 'Dhaka Oncology-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996789', 'memarul1357@gmail.com', '2020-02-16', 1, '2026-05-24 18:00:00'),
(2328, '309816', 'Md. Nasim', 'Senior Medical Promotion Officer', 'Maijdee-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996902', 'caretake83@gmail.com', '2020-02-29', 1, '2026-05-24 18:00:00'),
(2329, '309819', 'Md. Rasidul Islam', 'Senior Medical Promotion Officer', 'Savar-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996854', 'rasedulislambappy@gmail.com', '2020-03-11', 1, '2026-05-24 18:00:00'),
(2330, '309828', 'Md. Sohel Rana', 'Senior Medical Promotion Officer', 'Alamdanga-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995599', 'ranasohel@gmail.com', '2020-03-14', 1, '2026-05-24 18:00:00'),
(2331, '309835', 'Md. Nazirul Islam', 'Senior Medical Promotion Officer', 'Mitford/Sonirakhra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994526', 'nazirulislam2012@gmail.com', '2020-03-11', 1, '2026-05-24 18:00:00'),
(2332, '309836', 'Md. Razu Babu', 'Senior Medical Promotion Officer', 'Sirajganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996308', 'razudiubba@gmail.com', '2020-03-12', 1, '2026-05-24 18:00:00'),
(2333, '309838', 'Md. Rabbul Hossen', 'Senior Medical Promotion Officer', 'Chakaria-IV', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994719', 'hossenrabbul@gmail.com', '2020-03-14', 1, '2026-05-24 18:00:00'),
(2334, '309843', 'Shaikat Kumar', 'Senior Medical Promotion Officer', 'Lalmonirhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995547', 'shaikatkumar.999@gmail.com', '2020-03-11', 1, '2026-05-24 18:00:00'),
(2335, '309845', 'Mirza Mamon', 'Senior Medical Promotion Officer', 'Teknaf-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996876', 'mirzamamonunimed@gmail.com', '2020-03-15', 1, '2026-05-24 18:00:00'),
(2336, '309846', 'Md. Abdullah Almamun', 'Senior Medical Promotion Officer', 'Chhatarpaia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996872', 'hmmamun480@gmail.com', '2020-03-13', 1, '2026-05-24 18:00:00'),
(2337, '309848', 'Syed Shamim Hossain', 'Senior Medical Promotion Officer', 'Dumki', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995535', 'syedshamimhossainbd@gmail.com', '2020-03-21', 1, '2026-05-24 18:00:00'),
(2338, '309850', 'Md. Emdad Hossain', 'Medical Promotion Officer', 'Barisal-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996926', 'emdadmune@gmail.com', '2020-03-16', 1, '2026-05-24 18:00:00'),
(2339, '309851', 'Md. Shahin Sarkar', 'Senior Medical Promotion Officer', 'Bijoynagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996845', 'shahirsarkar3551@gmail.com', '2020-03-15', 1, '2026-05-24 18:00:00'),
(2340, '309852', 'Sheikh Juel Hossain', 'Senior Medical Promotion Officer', 'Madhabpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996851', 'juelhossain309@gmail.com', '2020-03-16', 1, '2026-05-24 18:00:00'),
(2341, '309854', 'Md. Azizul Munshi', 'Senior Medical Promotion Officer', 'Jassore-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995623', 'mdazizulmulmunshi@gmail.com', '2020-03-15', 1, '2026-05-24 18:00:00'),
(2342, '309855', 'Md. Najmul Hossain', 'Senior Medical Promotion Officer', 'Nasirnagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996846', 'najmulraj@gmail.com', '2020-03-15', 1, '2026-05-24 18:00:00'),
(2343, '309856', 'Achinta Kumar', 'Senior Medical Promotion Officer', 'Robir Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996848', 'sarkarchoncol@gmai.com', '2020-03-16', 1, '2026-05-24 18:00:00'),
(2344, '309858', 'Md. Alamin', 'Senior Medical Promotion Officer', 'Sreemangal-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996849', 'alaminislam1291@gmail.com', '2020-03-15', 1, '2026-05-24 18:00:00'),
(2345, '309862', 'Md. Syful Islam', 'Senior Medical Promotion Officer', 'Mehendiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994472', 'saifuih2666@gmail.com', '2020-02-08', 1, '2026-05-24 18:00:00'),
(2346, '309865', 'Md. Shahinur Islam', 'Senior Medical Promotion Officer', 'Sonagazi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996874', 'shahinurislam3307@gmail.com', '2020-03-16', 1, '2026-05-24 18:00:00'),
(2347, '309867', 'Proshanta Kumar Paul', 'Senior Medical Promotion Officer', 'Natore-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996875', 'proshantapaul49@gmail.com', '2020-03-13', 1, '2026-05-24 18:00:00'),
(2348, '309874', 'Md. Golam Morsed Sumon', 'Senior Medical Promotion Officer', 'Narayanganj-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995503', 'golammorshed5503@gmail.com', '2020-03-24', 1, '2026-05-24 18:00:00'),
(2349, '309875', 'Babu Hossan', 'Senior Medical Promotion Officer', 'Darshana', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994862', 'babuhossan2018@gmail.com', '2020-03-22', 1, '2026-05-24 18:00:00'),
(2350, '309876', 'Md. Asadujaman Mridha', 'Senior Medical Promotion Officer', 'Labaid-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995507', 'asadujaman525@gmail.com', '2020-03-22', 1, '2026-05-24 18:00:00'),
(2351, '309877', 'Md. Shahin Alam', 'Senior Medical Promotion Officer', 'Mitford/Jatrabari-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996856', 'mdshahin93alam@gmail.com', '2020-03-22', 1, '2026-05-24 18:00:00'),
(2352, '309879', 'Iffamul Alam', 'Senior Medical Promotion Officer', 'Chakaria-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996904', 'iffialam9@gmail.com', '2020-03-23', 1, '2026-05-24 18:00:00'),
(2353, '309882', 'Md. Rejwanullah', 'Senior Medical Promotion Officer', 'Madaripur-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996878', 'rejwanullah91@gmail.com', '2020-06-02', 1, '2026-05-24 18:00:00'),
(2354, '309884', 'Khoshi Chandro Sarker', 'Senior Medical Promotion Officer', 'Tangail-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996908', 'khoshisarker1997@gmail.com', '2020-03-22', 1, '2026-05-24 18:00:00'),
(2355, '309886', 'Md. Habibur Rahman', 'Senior Medical Promotion Officer', 'Kapasia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996871', 'mdhabibur2659@gmail.com', '2020-03-22', 1, '2026-05-24 18:00:00'),
(2356, '309889', 'Md. Mehedi Alam Molla', 'Senior Medical Promotion Officer', 'Uttara-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994500', 'mdmehedimolla33@gmail.com', '2020-09-05', 1, '2026-05-24 18:00:00'),
(2357, '309891', 'Md. Jewel Rana', 'Senior Medical Promotion Officer', 'Uttara-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996894', 'jewelranam329@gmail.com', '2020-09-05', 1, '2026-05-24 18:00:00'),
(2358, '309893', 'Md. Kamrul Islam', 'Medical Promotion Officer', 'DMCH-A6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993181', 'rulkam962@gmail.com', '2020-09-05', 1, '2026-05-24 18:00:00'),
(2359, '309895', 'Md. Ivion', 'Medical Promotion Officer', 'Mawna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995638', 'ivionmd788@gmail.com', '2020-09-05', 1, '2026-05-24 18:00:00'),
(2360, '309896', 'Dadhi Nath Ray', 'Senior Medical Promotion Officer', 'Mohakhali-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996724', 'dadhinathray@gmail.com', '2020-09-05', 1, '2026-05-24 18:00:00'),
(2361, '309901', 'Pratap Rangan Dhar', 'Senior Medical Promotion Officer', 'Kumira', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996903', 'pratapkumardhar109@gmail.com', '2020-11-16', 1, '2026-05-24 18:00:00'),
(2362, '309904', 'Md. Lablu Hossain', 'Senior Medical Promotion Officer', 'Khoksa', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994085', 'lablu02hossain@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2363, '309906', 'Md. Tariqul Islam', 'Senior Medical Promotion Officer', 'Nilphamari-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994547', 'tarikasif50@gmail.ccom', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2364, '309910', 'Md. Kamruzzaman  Majumder', 'Senior Medical Promotion Officer', 'Bandarban', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996793', 'mdk6858@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2365, '309911', 'Md. Al-Amin Hossen', 'Senior Medical Promotion Officer', 'Wazirpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994179', 'alamin17115@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2366, '309916', 'Md. Shofikul Islam', 'Senior Medical Promotion Officer', 'Barisal-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994822', 'shofikul.Islam9075@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2367, '309917', 'Md. Kawsar Hanif', 'Senior Medical Promotion Officer', 'Satkania-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995237', 'kawarhanif520@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2368, '309919', 'Md. Tosiqul Islam', 'Senior Medical Promotion Officer', 'Gaibandha-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995549', 'tosiq93@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2369, '309923', 'Md. Shohrab Hossain', 'Senior Medical Promotion Officer', 'Gaibandha-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996311', 'shohrabunigroup@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2370, '309925', 'Md. Alinur Islam', 'Senior Medical Promotion Officer', 'Gaibandha-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993589', 'alinurunigroup@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2371, '309926', 'Md. Shahadat Hossain', 'Senior Medical Promotion Officer', 'Hatibandha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996350', 'smshahadat.387819@gmail.com', '2020-12-27', 1, '2026-05-24 18:00:00'),
(2372, '309927', 'Md. Mostak Ahmed', 'Senior Medical Promotion Officer', 'Shibganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994838', 'amostak480@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2373, '309932', 'Md. Majedul Islam', 'Senior Medical Promotion Officer', 'Bhurungamari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994864', 'majedulislamccr@gmail.com', '2020-12-26', 1, '2026-05-24 18:00:00'),
(2374, '309933', 'Md. Moniruzzaman', 'Senior Medical Promotion Officer', 'Khulna-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994157', 'moniruzzaman.d16@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2375, '309934', 'Ruhul Amin', 'Senior Medical Promotion Officer', 'Sylhet-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994168', 'ruhuluni66@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2376, '309935', 'Mohammad Ziaul Hoque', 'Senior Medical Promotion Officer', 'Mymensingh-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996917', 'ratanmym@yahoo.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2377, '309941', 'Md. Ariful Islam', 'Senior Medical Promotion Officer', 'KMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994122', 'mdarif1285@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2378, '309942', 'Abdul Bari Azad', 'Senior Medical Promotion Officer', 'Moulvibazar-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994159', 'mamuna3ad.16@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2379, '309943', 'Khandoker Abdullah All Mamun', 'Senior Medical Promotion Officer', 'Barura-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994151', 'mamun0072@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2380, '309945', 'Titon Chandra Das', 'Area Sales Manager', 'JRRMCH+Mount Adora Akhalia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994167', 'toton.d16@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2381, '309946', 'Md. Harun-Ar-Rashid', 'Senior Medical Promotion Officer', 'Netrokona-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994161', 'md.harun16@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2382, '309948', 'Md. Arifur Rahman', 'Senior Medical Promotion Officer', 'Rangpur-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994164', 'arifunigroup1980@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2383, '309953', 'Md. Ajaharul Islam', 'Senior Medical Promotion Officer', 'Chittagong-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994140', 'ajharul.d16@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2384, '309958', 'Md. Hasan Ul Aman Robel', 'Senior Medical Promotion Officer', 'SWMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994169', 'hasanul.d16@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2385, '309961', 'Sipan Barua', 'Senior Medical Promotion Officer', 'Katghar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994141', 'shiponbaruad16@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2386, '309967', 'Md. Asaduzzaman', 'Senior Medical Promotion Officer', 'Jhawtola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994145', 'asad.badol1984@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2387, '309972', 'Md. Homayun Kabir', 'Senior Medical Promotion Officer', 'Lalkhan Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994146', 'homayun777@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2388, '309974', 'Md. Sahed Uddin Dafader', 'Area Sales Manager', 'Bashundhara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994132', 'soheluddin3@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2389, '309975', 'Rubel Mia', 'Senior Medical Promotion Officer', 'NICVD-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994121', 'rmia36288@gmail.com', '2019-02-02', 1, '2026-05-24 18:00:00'),
(2390, '309980', 'Jaidev Chandra Bharman', 'Senior Medical Promotion Officer', 'Popular-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994142', 'jaidevbarman186@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2391, '309984', 'Md. Jakaria Kabir', 'Senior Medical Promotion Officer', 'Bashundhara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994129', 'jakariya.kabir@gmail.com', '2021-01-02', 1, '2026-05-24 18:00:00'),
(2392, '309988', 'Md. Akramul Islam', 'Area Sales Manager', 'Hathazari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994143', 'aisqua4@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(2393, '315009', 'Md. Ala Uddin', 'Senior Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989996718', 'alauddin.uup.92@gmail.com', '2017-03-01', 1, '2026-05-24 18:00:00'),
(2394, '315026', 'Nur Hossain', 'Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997078', 'nur.gazi94@gmail.com', '2018-09-27', 1, '2026-05-24 18:00:00'),
(2395, '315035', 'Hasibur Rahman', 'Area Distribution Manager', 'Rampura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01831070998', 'hasib6984@gmail.com', '2018-09-29', 1, '2026-05-24 18:00:00'),
(2396, '315044', 'Hayderuddin', 'Senior Distribution Officer', 'Rampura Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01743909045', 'hayder.unigroup@gmail.com', '2018-11-06', 1, '2026-05-24 18:00:00'),
(2397, '320001', 'Kaniz Farzana Supti', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996897', 'kaniz.farzana@unigroup-bd.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2398, '320002', 'Sultan Mahmud Shakib', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993094', 'sultan.mahmud@unigroup-bd.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2399, '320003', 'Md. Iftakher Alam', 'Assistant Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997248', 'iftakher.alam@unigroup-bd.com', '2023-12-03', 1, '2026-05-24 18:00:00'),
(2400, '320005', 'Md. Mirazul Islam', 'Graphic Designer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996758', 'mirazghl09@gmail.com', '2023-11-23', 1, '2026-05-24 18:00:00'),
(2401, '320006', 'Md. Roknuzzaman', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993391', 'rokon.zaman@unigroup-bd.com', '2023-12-23', 1, '2026-05-24 18:00:00'),
(2402, '320007', 'Mohammad Shamim Miah', 'Senior Graphic Designer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997255', 'shamimmahbub.90@gmail.com', '2023-12-19', 1, '2026-05-24 18:00:00'),
(2403, '320010', 'Khan Istiak Bin Kabir', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993071', 'khan.istiak@unigroup-bd.com', '2024-01-16', 1, '2026-05-24 18:00:00'),
(2404, '320023', 'Mostakim Billah Asif', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989996711', 'mostakim.billah@unigroup-bd.com', '2024-02-01', 1, '2026-05-24 18:00:00'),
(2405, '320027', 'Abdullah Md. Sazid Chowdhury', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994036', 'sazid.chowdhury@unigroup-bd.com', '2024-02-15', 1, '2026-05-24 18:00:00'),
(2406, '320028', 'Md. Sakran Nakib Apurba', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994052', 'sakran.nakib@unigroup-bd.com', '2024-03-02', 1, '2026-05-24 18:00:00'),
(2407, '320031', 'Nuruzzaman Bablu', 'HR Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989997270', 'nuruzzaman.unigroup@gmail.com', '2024-04-01', 1, '2026-05-24 18:00:00'),
(2408, '320036', 'Jobaida Binte Sahadat', 'HR Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989997271', 'srabonihossain2228@gmail.com', '2025-01-01', 1, '2026-05-24 18:00:00'),
(2409, '320037', 'Syed Raiyan Nuri Reza', 'Software Developer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989997266', 'syedraiyannurireza@gmail.com', '2025-01-01', 1, '2026-05-24 18:00:00'),
(2410, '320039', 'Md. Shaheb Ali', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01929993218', 'shaheb.ali@unigroup-bd.com', '2025-02-19', 1, '2026-05-24 18:00:00'),
(2411, '320043', 'Shashi Das', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997249', 'shashidas312001@gmail.com', '2025-03-06', 1, '2026-05-24 18:00:00'),
(2412, '320044', 'Md. Sawgatul Hoque Sabbir', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994566', 'sawgat.ph@gmail.com', '2025-04-10', 1, '2026-05-24 18:00:00'),
(2413, '320045', 'A.S.M. Tohidul Islam', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994399', 'asmddilswad@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(2414, '320049', 'Arafat Islam', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994033', 'arafatislam.ustc.11@gmail.com', '2025-05-06', 1, '2026-05-24 18:00:00'),
(2415, '320050', 'Semanto Ray', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994074', 'semantoroysemu@gmail.com', '2025-06-01', 1, '2026-05-24 18:00:00'),
(2416, '320051', 'Md. Nahid Hasan', 'IT Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'IT', '01989994983', 'nahid.hasan@unigroup-bd.com', '2025-05-19', 1, '2026-05-24 18:00:00'),
(2417, '320052', 'Md. Shoaib Hasan', 'Commercial Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989996960', 'Shoaib.rph.hasan@gmail.com', '2025-06-01', 1, '2026-05-24 18:00:00'),
(2418, '320054', 'Kazi Mehedi Hasan', 'Sales Administration Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994703', 'kazimahadi80@gmail.com', '2025-07-15', 1, '2026-05-24 18:00:00'),
(2419, '320055', 'Biplab Uddin', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01874979413', 'biplabuddin1997@gmail.com', '2025-09-01', 1, '2026-05-24 18:00:00'),
(2420, '320056', 'Md. Musfiqur Razzaque Jimi', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994316', 'mushfiqur.razzaque@gmail.com', '2025-09-01', 1, '2026-05-24 18:00:00'),
(2421, '320058', 'Sajal Mahmud', 'Sales Administration Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994678', 'sajalmahmud374@gmail.com', '2025-09-11', 1, '2026-05-24 18:00:00'),
(2422, '320059', 'Safia Akter', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997280', 'sarah_safia1990@yahoo.com', '2025-10-12', 1, '2026-05-24 18:00:00'),
(2423, '320060', 'Mukter Hossain', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994032', 'mukterfaisal@gmail.com', '2025-09-17', 1, '2026-05-24 18:00:00'),
(2424, '320066', 'Enayet Hossain Bhuian', 'Senior Commercial Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989994372', 'hossainbhuian89@gmail.com', '2026-01-20', 1, '2026-05-24 18:00:00'),
(2425, '320067', 'Sabbir Ahmed', 'Senior Product Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997283', 'sabbirpharmacy26@gmail.com', '2025-11-16', 1, '2026-05-24 18:00:00'),
(2426, '320068', 'Salahuddin Anowar Zidan', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994053', 'syedzidan93@gmail.com', '2025-10-19', 1, '2026-05-24 18:00:00'),
(2427, '320069', 'Mir Jobayer Hossain', 'Senior Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997281', 'mirjoyayeremon002@gmail.com', '2025-11-02', 1, '2026-05-24 18:00:00'),
(2428, '320070', 'Riajul Jannat Zubayer', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997282', 'riajuljubayer@gmail.com', '2025-11-02', 1, '2026-05-24 18:00:00'),
(2429, '320071', 'Md. Al Mahmud Hasan', 'Accounts Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01730634541', 'alhasann1622@gmail.com', '2025-11-02', 1, '2026-05-24 18:00:00'),
(2430, '320074', 'Mst Ferdousi Akther Shikha', 'Officer, Packaging', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01335113806', 'ferdousishikha@gmail.com', '2026-01-18', 1, '2026-05-24 18:00:00'),
(2431, '320075', 'Sheikh Sharfuddin Rajib', 'Marketing Manager', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989997300', 'rajib086@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(2432, '320076', 'Tausif Mahmud', 'Senior Officer, Protocol & Liaison', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Marketing', '01989994503', 'tausif.mahmud17@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(2433, '320084', 'Md. Maruf Hossain', 'Assistant Manager, VAT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01335113881', 'skmaruf1989@gmail.com', '2026-03-01', 1, '2026-05-24 18:00:00'),
(2434, '320089', 'Rezwanur Rahman', 'Trainee Officer, VAT', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01335113878', 'rezawantcc@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(2435, '320090', 'Mithun Dutta Chowdhury', 'Trainee Officer, VAT', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01335113879', 'mithunduttachowdhury97@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(2436, '332004', 'Mithun Chakrobarti', 'Senior Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989997165', 'mchakrobartieee@gmail.com', '2019-05-02', 1, '2026-05-24 18:00:00'),
(2437, '332011', 'Sheikh Zamir Uddin', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01750202212', 'zomir89@gmail.com', '2019-06-29', 1, '2026-05-24 18:00:00'),
(2438, '332017', 'Md. Saiful Islam', 'Senior HR & Administration Officer', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'HR & Administration', '01989994009', 'saiful.islam2@unigroup-bd.com', '2019-07-08', 1, '2026-05-24 18:00:00'),
(2439, '332021', 'Provat Kumer Shil', 'Assistant Manager, Training', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Training', '01989994059', 'provat.shil@gmail.com', '2019-10-03', 1, '2026-05-24 18:00:00'),
(2440, '332023', 'Md. Asaduszaman Sogir', 'Assistant Manager, Quality Control', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01989997194', 'mdasaduzzamankhan78@gmail.com', '2019-08-26', 1, '2026-05-24 18:00:00'),
(2441, '332024', 'Ariful Islam', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01516111816', 'arifulislam9010@gmail.com', '2019-10-17', 1, '2026-05-24 18:00:00'),
(2442, '332025', 'Md. Raziur Rahman', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01781069947', 'razudiuo@gmail.com', '2019-10-15', 1, '2026-05-24 18:00:00'),
(2443, '332026', 'Md. Towhidul Islam Khan', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01676792389', 'towhidulislamkhanunigroup@gmail.com', '2019-10-07', 1, '2026-05-24 18:00:00'),
(2444, '332027', 'Md. Feroz Alam', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01714314498', 'saifmiu592@gmail.com', '2019-10-28', 1, '2026-05-24 18:00:00'),
(2445, '332028', 'Md. Ashikur Rahman Masud', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01723456591', 'ashikur4565@gmail.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(2446, '332029', 'Bishajit Paul', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01714500680', 'bishajitpaul6@gmail.com', '2019-10-14', 1, '2026-05-24 18:00:00'),
(2447, '332032', 'Md. Shahin Alam', 'Assistant Manager, Civil Engineering', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Procurement', '01989996750', 'shahin.alam@unigroup-bd.com', '2019-12-01', 1, '2026-05-24 18:00:00'),
(2448, '332036', 'Md. Ariful Islam', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01758215004', 'milon@gmail.com', '2019-12-14', 1, '2026-05-24 18:00:00'),
(2449, '332038', 'Tanzil Ahmmed  Tarake', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01914541827', 'tanzilahmmed70@gmail.com', '2020-02-22', 1, '2026-05-24 18:00:00'),
(2450, '332040', 'Md. Anik Mahmud', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01600104980', 'anik.unigroup@gmail.com', '2020-02-22', 1, '2026-05-24 18:00:00'),
(2451, '332044', 'Azizul Hoq', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01737969495', 'azizulhoq135@gmail.com', '2020-03-08', 1, '2026-05-24 18:00:00'),
(2452, '332047', 'Md. Shahin Alam', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01951432238', 'shahinalam332047@gmail.com', '2020-03-09', 1, '2026-05-24 18:00:00'),
(2453, '332048', 'Razzak Mandal', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01760788154', 'razzak.city2@gmail.com', '2020-03-08', 1, '2026-05-24 18:00:00'),
(2454, '332050', 'Md. Suzan Mian', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01768204367', 'smsuzanm@gmail.com', '2020-03-08', 1, '2026-05-24 18:00:00'),
(2455, '332051', 'Saiful Islam', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01862734368', 'bdsaifulr74@gmail.com', '2020-03-10', 1, '2026-05-24 18:00:00'),
(2456, '332052', 'Md. Mehedi Hasan', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01762954454', 'mehedihasanrpi20@gmail.com', '2020-03-18', 1, '2026-05-24 18:00:00'),
(2457, '332059', 'Tariqul Islam Shihab', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01986126093', 'shihabmd44567@gmail.com', '2020-09-21', 1, '2026-05-24 18:00:00'),
(2458, '332063', 'Zia Sarder', 'Assistant Manager, EHS (Environment, Health & Safety)', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989994011', 'zia.sarder@unigroup-bd.com', '2020-12-01', 1, '2026-05-24 18:00:00'),
(2459, '332064', 'Jahid Khan', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01609298656', 'jahid.led@gmail.com', '2020-11-01', 1, '2026-05-24 18:00:00'),
(2460, '332065', 'Md. Mamun Hosen', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01672325290', 'mdmamunhossen.unigroup@gmail.com', '2020-12-03', 1, '2026-05-24 18:00:00'),
(2461, '332067', 'Md. Saiful Islam', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01687733745', 'saifulnihal3@gmail.com', '2020-12-10', 1, '2026-05-24 18:00:00'),
(2462, '332068', 'Md. Baizid Islam', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01784860666', 'sajibislam350@gmail.com', '2020-12-14', 1, '2026-05-24 18:00:00'),
(2463, '332073', 'Md. Motaleb Hossain', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01515268732', 'motaleb559@diu.edu.bd', '2021-03-24', 1, '2026-05-24 18:00:00'),
(2464, '332075', 'Md. Abir Rahman', 'Senior Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01682000103', 'abir.rahman.unigroup@gmail.com', '2021-05-03', 1, '2026-05-24 18:00:00'),
(2465, '332078', 'Md. Shahariar Shakib', 'Senior Civil Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989994014', 'm.s.shakib61@gmail.com', '2021-07-05', 1, '2026-05-24 18:00:00'),
(2466, '332079', 'Md. Faysal Khan Shuvo', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01797588482', 'faysalshuvo1425@gmail.com', '2021-08-22', 1, '2026-05-24 18:00:00'),
(2467, '332080', 'Hasanuzzaman Hasan', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01929831364', 'mdh3742@gmail.com', '2021-08-28', 1, '2026-05-24 18:00:00'),
(2468, '332084', 'Md. Mazadul Hasan', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01762706665', 'mazedulhj94@gmail.com', '2021-09-01', 1, '2026-05-24 18:00:00'),
(2469, '332085', 'Md. Israfil', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01741471771', 'israfil8994@gmail.com', '2021-09-02', 1, '2026-05-24 18:00:00'),
(2470, '332089', 'Md. Tazbiul Islam', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01785067999', 'tazbiulislamunigroup@gmail.com', '2021-09-11', 1, '2026-05-24 18:00:00'),
(2471, '332090', 'Md. Shafiul Islam Khan', 'Senior Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01988010984', 'shafiulkhan032@gmail.com', '2021-10-03', 1, '2026-05-24 18:00:00'),
(2472, '332099', 'Shishir Biswas Subrata', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01746057649', 'shishir.drug@gmail.com', '2021-12-12', 1, '2026-05-24 18:00:00'),
(2473, '332100', 'Md. Roknuzzaman', 'Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01768749994', 'ronirokunuzzaman123@gmail.com', '2021-12-11', 1, '2026-05-24 18:00:00'),
(2474, '332103', 'Md. Rifat Shah', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01765991985', 'rifatshah011@gmail.com', '2022-01-01', 1, '2026-05-24 18:00:00'),
(2475, '332120', 'Md. Mostafizur Rahman', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01926160979', 'bristilikhon92@gmail.com', '2022-04-02', 1, '2026-05-24 18:00:00'),
(2476, '332121', 'Md. Amirul Islam', 'Senior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01634001010', 'mdamirulislam972@gmail.com', '2022-04-07', 1, '2026-05-24 18:00:00'),
(2477, '332124', 'Md. Ziaur Rahman', 'Medical Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01745730869', '', '2022-04-05', 1, '2026-05-24 18:00:00'),
(2478, '332130', 'Md. Shahajan Hossen', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01717899216', 'shahajanmme13@gmail.com', '2022-06-12', 1, '2026-05-24 18:00:00'),
(2479, '332131', 'Md. Milon Ali', 'Junior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01571780666', 'mdmilonali01761@gmail.com', '2022-06-14', 1, '2026-05-24 18:00:00'),
(2480, '332132', 'Bizan Biswas', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989993997', 'bizanbiswas@gmail.com', '2022-06-12', 1, '2026-05-24 18:00:00'),
(2481, '332133', 'Md. Faisal', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01736544811', 'mdfoysal7532@gmail.com', '2022-06-27', 1, '2026-05-24 18:00:00'),
(2482, '332136', 'Md. Nazrul Islam', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01724045471', 'nazrulislam0392@gmail.com', '2022-07-16', 1, '2026-05-24 18:00:00'),
(2483, '332137', 'Md. Nazim Uddin', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01682858876', 'nazim5222@gmail.com', '2022-07-16', 1, '2026-05-24 18:00:00'),
(2484, '332139', 'Md. Istiak Kabir', 'Senior Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01746205523', 'md.istiakkabir2041@gmail.com', '2022-07-02', 1, '2026-05-24 18:00:00'),
(2485, '332140', 'Md. Hafiz Uddin', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01892497831', 'h141219934@gmail.com', '2022-07-17', 1, '2026-05-24 18:00:00'),
(2486, '332144', 'Tarequl Islam', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01682407498', 'taraqulinslam98@gmail.com', '2022-08-14', 1, '2026-05-24 18:00:00'),
(2487, '332145', 'Md. Aktaruzzman', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01716587853', 'aktarmmc@yahoo.com', '2022-08-14', 1, '2026-05-24 18:00:00'),
(2488, '332147', 'Nazmos Sakib Chowdhury', 'Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01864155594', 'nazmossakib786@gmail.com', '2022-08-18', 1, '2026-05-24 18:00:00'),
(2489, '332148', 'Md. Tipu Sultan', 'Junior Officer, VAT', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01957637211', 'tipur27@gmail.com', '2022-09-11', 1, '2026-05-24 18:00:00'),
(2490, '332149', 'Md. Emamul Hasan', 'Junior Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01710081748', 'mdenamulhasan29@gmail.com', '2022-11-01', 1, '2026-05-24 18:00:00'),
(2491, '332150', 'Abu Sayeed', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01745868887', 'sayeed.uupl@gmail.com', '2022-11-13', 1, '2026-05-24 18:00:00'),
(2492, '332151', 'Md. Shabbir Hossain', 'Senior Validation Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989993987', 'shabbir.shawon50@gmail.com', '2022-11-15', 1, '2026-05-24 18:00:00'),
(2493, '332153', 'Sanjib Kumar Singha', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01757484003', 'sanjib1213sinha@gmail.com', '2022-11-15', 1, '2026-05-24 18:00:00'),
(2494, '332154', 'M. Monayem Hossain', 'Assistant Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01790040156', 'mdtitas14@gmail.com', '2022-11-15', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(2495, '332155', 'Shahidur Rahman', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01756067006', 'shahidurrahman4448@gmail.com', '2022-11-23', 1, '2026-05-24 18:00:00'),
(2496, '332156', 'Md. Saiful Islam', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01818242957', 'saifuluoda34@gmail.com', '2022-11-23', 1, '2026-05-24 18:00:00'),
(2497, '332157', 'M.A. Munim Masud', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01734128686', 'muni.masud25@gmail.com', '2022-12-13', 1, '2026-05-24 18:00:00'),
(2498, '332158', 'Md. Ashikul Hossain', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01643651981', 'ashikul.hossain2011@gmail.com', '2022-12-04', 1, '2026-05-24 18:00:00'),
(2499, '332159', 'Md. Shakil Khan', 'Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01731293469', 'shakilkhan5bd@gmail.com', '2022-12-01', 1, '2026-05-24 18:00:00'),
(2500, '332160', 'Arpan Debnath', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01751652318', 'arpan.d007@gmail.com', '2022-12-11', 1, '2026-05-24 18:00:00'),
(2501, '332163', 'Md. Rayhan Sarder', 'Receptionist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989997200', 'rayhansarder1010@gmail.com', '2023-01-04', 1, '2026-05-24 18:00:00'),
(2502, '332164', 'Md. Shfiqul Islam', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01740448553', 'imshfiqul25223unigroup@gmail.com', '2023-02-15', 1, '2026-05-24 18:00:00'),
(2503, '332166', 'Md. Abdul Alim', 'Senior Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01644954505', 'mdabdulalimsr33@mail.com', '2023-03-01', 1, '2026-05-24 18:00:00'),
(2504, '332171', 'Md. Mainul Hasan', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01646175099', 'mainul.hasan31@yahoo.com', '2023-04-04', 1, '2026-05-24 18:00:00'),
(2505, '332172', 'Md. Monarul Islam', 'Process Improvement Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Finance & Accounts', '01989997250', 'monarul.ag@gmail.com', '2023-04-05', 1, '2026-05-24 18:00:00'),
(2506, '332175', 'Partha Bairagi', 'Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01926198609', 'parthabairagia7323@gmail.com', '2023-07-06', 1, '2026-05-24 18:00:00'),
(2507, '332178', 'Emon Sheikh', 'Assistant Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01728771684', 'emon.shak2221@gmail.com', '2023-07-09', 1, '2026-05-24 18:00:00'),
(2508, '332179', 'Md. Tahsin Billah', 'Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01775894525', 'tahsinbillah1997@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(2509, '332180', 'Kamal Hossen', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01643563926', 'hujaifa3817@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(2510, '332182', 'Shanto Kumer Saha', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01737658252', 'sahashanto9433@gmail.com', '2023-08-10', 1, '2026-05-24 18:00:00'),
(2511, '332184', 'Rasel Hossen', 'Assistant Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01718012725', 'raselhossen465@gmail.com', '2023-07-18', 1, '2026-05-24 18:00:00'),
(2512, '332185', 'Md. Abdur Rashed Rana', 'Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01717561205', 'rashedrana519@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(2513, '332186', 'Malay Halder', 'Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01713921270', 'malayhalder44@gmail.com', '2023-08-01', 1, '2026-05-24 18:00:00'),
(2514, '332187', 'Md. Razu Ahmed', 'Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01701053519', 'rajushimul1990@gmail.com', '2023-08-06', 1, '2026-05-24 18:00:00'),
(2515, '332190', 'Md. Bazlur Rahman', 'Quality Assurance Manager', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989997219', 'bazlur.rahman@gmail.com', '2023-10-10', 1, '2026-05-24 18:00:00'),
(2516, '332191', 'Saiful Islam', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01799858589', 'sksaiful861@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(2517, '332192', 'Md. Abdullah Al Mamun', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01755181137', 'abdullahmamun1755@gmail.com', '2023-10-14', 1, '2026-05-24 18:00:00'),
(2518, '334897', 'Md. Jiaur Rahman', 'Junior Distribution Officer', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01913944599', 'mdjiaurrahman1301986@gmail.com', '2013-12-14', 1, '2026-05-24 18:00:00'),
(2519, '339204', 'Krishna Kanta Nandi', 'Project Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989997166', 'nandikrishna157@gmail.com', '2014-08-04', 1, '2026-05-24 18:00:00'),
(2520, '340001', 'Syedul-Islam', 'Medical Promotion Officer', 'Mirpur-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994945', 'syedulislam6145@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2521, '340006', 'Md. Ali Imam', 'Senior Medical Promotion Officer', 'Mirpur-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994431', 'imamali3300@gmail.com', '2023-11-14', 1, '2026-05-24 18:00:00'),
(2522, '340008', 'Rokibul Islam', 'Medical Promotion Officer', 'BIRDEM-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996954', 'rokib10015@gmail.com', '2023-11-12', 1, '2026-05-24 18:00:00'),
(2523, '340009', 'Md. Shuvo Mia', 'Medical Promotion Officer', 'BSMMU-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994612', 'soriotullamrd@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2524, '340011', 'Md. Ruhul Amin Kazi', 'Medical Promotion Officer', 'Khulna-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994650', 'rulaminkazi@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2525, '340015', 'Md. Abujar Hosain', 'Medical Promotion Officer', 'Khulna-D3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994520', 'sagarhossin9655@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2526, '340016', 'Md. Nurnobi Islam', 'Medical Promotion Officer', 'Joypurhat-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319038', 'imdnurnobi316@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2527, '340018', 'Md. Abdul Ohabe', 'Medical Promotion Officer', 'Mithapukur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994468', 'mr.ohab47@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(2528, '340019', 'Shaikat Farash', 'Medical Promotion Officer', 'Kamalnagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995193', 'shaikatfarash1@gmail.com', '2023-11-12', 1, '2026-05-24 18:00:00'),
(2529, '340021', 'Md. Safiul Islam', 'Medical Promotion Officer', 'Sylhet-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996317', 'saifulsujon303@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2530, '340025', 'Jobail Lorin Sarkar', 'Medical Promotion Officer', 'Fakirhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994139', 'lorinsarkar007@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2531, '340026', 'Md. Shofikul Islam', 'Medical Promotion Officer', 'Gosairhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995185', 'shofiqulislambd5185@gmail.com', '2023-12-01', 1, '2026-05-24 18:00:00'),
(2532, '340027', 'Md. Ariful Islam', 'Medical Promotion Officer', 'Pirganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995270', 'adorablearifulislam1999@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2533, '340028', 'Deloar Hosan', 'Medical Promotion Officer', 'Bashundhara-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319039', 'deloar28121997@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2534, '340029', 'Md. Razu Ahammed', 'Medical Promotion Officer', 'Chunarughat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319061', 'youknowrazu.1992@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2535, '340031', 'Md. Mehadul Islam', 'Medical Promotion Officer', 'Chhagalnaiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319043', 'koafymehedul@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2536, '340033', 'Md. Bulbul Ahmed', 'Medical Promotion Officer', 'Habiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319084', 'mdbulbul.ahamed760@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2537, '340035', 'Md. Mahede Hasan', 'Medical Promotion Officer', 'Madhabpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319059', 'mahedehasanmonel15@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2538, '340036', 'Md. Maruf Mahamud Roman Sarker', 'Medical Promotion Officer', 'Daganbhuiyan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319044', 'md.roman1493@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2539, '340037', 'Md. Faruk Hossain', 'Medical Promotion Officer', 'Trishal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995429', 'mdfarukhossain47@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2540, '340038', 'Nazmul Hossen', 'Medical Promotion Officer', 'Comilla-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997239', 'hossenn870@gmail.com', '2023-12-01', 1, '2026-05-24 18:00:00'),
(2541, '340039', 'Md. Chanchal Mia', 'Medical Promotion Officer', 'Savar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994166', 'miachanchal276@gmail.com', '2023-11-30', 1, '2026-05-24 18:00:00'),
(2542, '340040', 'Md. Jakaria Shourob', 'Medical Promotion Officer', 'Netrokona-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319064', 'jakariashourob990@gmail.com', '2023-11-30', 1, '2026-05-24 18:00:00'),
(2543, '340042', 'Md. Jakir Hasan', 'Medical Promotion Officer', 'Sarail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319063', 'hasenjakir9@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2544, '340043', 'Md. Shamim Hasan', 'Medical Promotion Officer', 'Satkhira-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993652', 'shamim20053208@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2545, '340044', 'Md. Nuruzzaman Mia', 'Medical Promotion Officer', 'Mitford-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993179', 'nuruzamanunimed@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2546, '340045', 'Md. Anamul Haque', 'Medical Promotion Officer', 'Kishoreganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319048', 'anamulhaquebejoy26@gmail.com', '2023-12-07', 1, '2026-05-24 18:00:00'),
(2547, '340047', 'Md. Mamunur Rashid', 'Medical Promotion Officer', 'Sunamganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319053', 'mdmamunur855@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2548, '340049', 'Md. Rubel Biswas', 'Medical Promotion Officer', 'Kasba', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319062', 'biswasrubel635@gmail.com', '2023-11-30', 1, '2026-05-24 18:00:00'),
(2549, '340051', 'Md. Fazley Rabbi', 'Medical Promotion Officer', 'Chandpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319041', 'mfrabbi7@gmail.com', '2023-12-01', 1, '2026-05-24 18:00:00'),
(2550, '340054', 'Anowar Hossen', 'Medical Promotion Officer', 'Matlab', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319042', 'anowarhaossen292@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2551, '340056', 'Md. Mofidul Islam', 'Medical Promotion Officer', 'Moulvibazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319054', 'mofidul.islam.6700@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2552, '340057', 'Md. Ekramul Haque', 'Medical Promotion Officer', 'Basurhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319045', 'ekramhr1212@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2553, '340060', 'Md. Zahangir Alom', 'Medical Promotion Officer', 'Sherpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319065', 'zahangiralom560@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2554, '340062', 'Md. Abdul Awal', 'Medical Promotion Officer', 'Comilla-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997082', 'mdaawal86@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2555, '340064', 'Md. Aklasur Rahman', 'Medical Promotion Officer', 'Nabiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319060', 'mdaklas629363@gmail.com', '2023-12-01', 1, '2026-05-24 18:00:00'),
(2556, '340066', 'Md. Rabiul Islam', 'Medical Promotion Officer', 'Bhairab-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319050', 'md.rabiul.rudro01749168@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2557, '340067', 'Md. Nadim Hosain', 'Medical Promotion Officer', 'ShSMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319055', 'nadimmahmud121212@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(2558, '340070', 'Md. Shihab', 'Medical Promotion Officer', 'Madhupur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994839', 'sathimd439@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(2559, '340071', 'Nur Alom ', 'Medical Promotion Officer', 'Chandpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319080', 'nuralom05041997@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(2560, '340072', 'Hasan Ali', 'Medical Promotion Officer', 'B.Baria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997208', 'mahasan19962003@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(2561, '340073', 'Md. Khairul Hasan', 'Medical Promotion Officer', 'Rangpur-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994107', 'sujonkhondokar67@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(2562, '340075', 'Md. Moseur Rahman', 'Medical Promotion Officer', 'Sreemangal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319081', 'mdmoseurrahman488@gmail.com', '2023-12-14', 1, '2026-05-24 18:00:00'),
(2563, '340076', 'Ammrita Kumar Roy', 'Senior Medical Promotion Officer', 'Bajitpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319071', 'dr716978@gmail.com', '2023-12-17', 1, '2026-05-24 18:00:00'),
(2564, '340078', 'Sushanta Roy', 'Medical Promotion Officer', 'Patuakhali-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997105', 'sushantaroy4119@gmail.com', '2023-12-10', 1, '2026-05-24 18:00:00'),
(2565, '340082', 'Md. Firoz Hossain', 'Senior Medical Promotion Officer', 'ChIttagong Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993929', 'firozhossain282@gmail.com', '2024-01-11', 1, '2026-05-24 18:00:00'),
(2566, '340084', 'Md. Jabaydur Rahman', 'Senior Medical Promotion Officer', 'BSMMU-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994383', 'jabaydur330@gmail.com', '2024-01-08', 1, '2026-05-24 18:00:00'),
(2567, '340086', 'Biplab Shil', 'Medical Promotion Officer', 'Gournadi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319072', 'redoybiplab2222@gmail.com', '2024-01-04', 1, '2026-05-24 18:00:00'),
(2568, '340088', 'Md. Al Amin', 'Medical Promotion Officer', 'Moulvibazar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319076', 'md.alamin210208bissas@gmail.com', '2024-01-09', 1, '2026-05-24 18:00:00'),
(2569, '340089', 'Md. Shahinur Islam', 'Medical Promotion Officer', 'Beanibazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319069', 'shahinur01882@gmail.com', '2024-01-09', 1, '2026-05-24 18:00:00'),
(2570, '340091', 'Md. Latibur Rahman', 'Senior Medical Promotion Officer', 'BSMMU/Modern-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994669', 'latibur90@gmail.com', '2024-01-04', 1, '2026-05-24 18:00:00'),
(2571, '340093', 'Md. Tarikul Islam', 'Medical Promotion Officer', 'Narsingdi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993808', 'atifaslamtorik@gmail.com', '2024-01-09', 1, '2026-05-24 18:00:00'),
(2572, '340097', 'Md. Morshed Rigen', 'Senior Medical Promotion Officer', 'B.Baria-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994245', 'mdmorsed887.2b@gmail.com', '2024-01-14', 1, '2026-05-24 18:00:00'),
(2573, '340098', 'Md. Mominur Rahman', 'Medical Promotion Officer', 'Chatmohar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995427', 'mominurrahman01866@gmail.com', '2024-01-13', 1, '2026-05-24 18:00:00'),
(2574, '340099', 'Md. Faruk Hossen', 'Medical Promotion Officer', 'Safipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996813', 'farukhossenripon207@gmail.com', '2024-01-14', 1, '2026-05-24 18:00:00'),
(2575, '340100', 'Md. Shahin Hossain', 'Senior Medical Promotion Officer', 'PMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993768', 'shahinhossain52057@gmail.com', '2024-01-11', 1, '2026-05-24 18:00:00'),
(2576, '340102', 'Farhad Hossain', 'Medical Promotion Officer', 'Hnila', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993531', 'saiyedfarhad1234@gmail.com', '2024-01-13', 1, '2026-05-24 18:00:00'),
(2577, '340103', 'Md. Shakawat Ullah Bhuyan', 'Medical Promotion Officer', 'Nazirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319086', 'tuhin5508@gmail.com', '2024-01-12', 1, '2026-05-24 18:00:00'),
(2578, '340104', 'Farhad Uddin Ahamed', 'Area Sales Manager', 'Mirpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993435', 'forhadnk414@gmail.com', '2024-01-09', 1, '2026-05-24 18:00:00'),
(2579, '340105', 'Sk Nazmus Sadat', 'Area Sales Manager', 'Jhenaidah-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994940', 'tonmoy16283@gmail.com', '2024-01-13', 1, '2026-05-24 18:00:00'),
(2580, '340106', 'Md. Touhidul Islam', 'Medical Promotion Officer', 'Kakrail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993961', 'touhid2575@gmail.com', '2024-01-24', 1, '2026-05-24 18:00:00'),
(2581, '340108', 'Md. Shakhawat Hossain', 'Medical Promotion Officer', 'Motijheel-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319087', 'shrshakhawat2020@gmail.com', '2024-01-25', 1, '2026-05-24 18:00:00'),
(2582, '340109', 'Akramul Haque', 'Medical Promotion Officer', 'DMCH-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994307', 'akramulsarkar1993@gmail.com', '2024-01-24', 1, '2026-05-24 18:00:00'),
(2583, '340111', 'Palash Kumar Roy', 'Senior Medical Promotion Officer', 'Sylhet-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993414', 'roy169225@gmail.com', '2024-01-27', 1, '2026-05-24 18:00:00'),
(2584, '340112', 'Md. Syful Islam', 'Area Sales Manager', 'Mymensingh-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994051', 'syfulislam@gmail.com', '2024-01-16', 1, '2026-05-24 18:00:00'),
(2585, '340113', 'Md. Liton Hossain', 'Senior Medical Promotion Officer', 'Jhalakathi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994825', 'litonhossenbpl123@gmail.com', '2024-01-28', 1, '2026-05-24 18:00:00'),
(2586, '340115', 'Rubel Chandra Das', 'Medical Promotion Officer', 'Hatia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994887', 'dasrubel472@gmail.com', '2024-01-28', 1, '2026-05-24 18:00:00'),
(2587, '340116', 'Md. Ruyel Hossain', 'Senior Medical Promotion Officer', 'Ibn Sina-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996927', 'ruyel74@gmail.com', '2024-01-28', 1, '2026-05-24 18:00:00'),
(2588, '340117', 'Md. Hasan', 'Medical Promotion Officer', 'BMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994855', 'sardarhasan074@gmail.com', '2024-01-31', 1, '2026-05-24 18:00:00'),
(2589, '340118', 'Md. Anwar Hossain', 'Medical Promotion Officer', 'Jamalpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319051', 'anwar806391@gmail.com', '2024-01-31', 1, '2026-05-24 18:00:00'),
(2590, '340120', 'Prosenjit Saha', 'Medical Promotion Officer', 'Jhalakathi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997106', 'psaha4366@gmail.com', '2024-01-30', 1, '2026-05-24 18:00:00'),
(2591, '340124', 'Mohammad Muzahidul Islam', 'Senior Area Sales Manager', 'Mymensingh-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319075', 'mizahid314@gmail.com', '2024-01-30', 1, '2026-05-24 18:00:00'),
(2592, '340125', 'Md. Al- Amin', 'Senior Medical Promotion Officer', 'Chunarughat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994395', 'alaminp11349@gmail.com', '2024-02-16', 1, '2026-05-24 18:00:00'),
(2593, '340126', 'Abdul Koddus', 'Senior Medical Promotion Officer', 'Gunagori', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994147', 'abkudd1120@gmail.com', '2024-02-17', 1, '2026-05-24 18:00:00'),
(2594, '340128', 'Md. Rashidul Islam', 'Medical Promotion Officer', 'Chapainawabganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995316', 'rashidul.treading@gmail.com', '2024-02-20', 1, '2026-05-24 18:00:00'),
(2595, '340132', 'Amir Hamja', 'Medical Promotion Officer', 'Kasba-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995124', 'hk501386@gmail.com', '2024-02-21', 1, '2026-05-24 18:00:00'),
(2596, '340133', 'Mohiuddin Manik', 'Senior Medical Promotion Officer', 'Chevron-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319074', 'mohiuddinmanik222@gmail.com', '2024-02-19', 1, '2026-05-24 18:00:00'),
(2597, '340135', 'Md. Mokter Hossain', 'Senior Medical Promotion Officer', 'Board Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993241', 'mdmokter5712@gmail.com', '2024-02-15', 1, '2026-05-24 18:00:00'),
(2598, '340139', 'Samor Das', 'Medical Promotion Officer', 'Sylhet-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994221', 'samordas8085@gmail.com', '2024-02-15', 1, '2026-05-24 18:00:00'),
(2599, '340141', 'Md. Mahmudur Rahman', 'Senior Medical Promotion Officer', 'Sylhet-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995180', 'mahmudurkafi@gmail.com', '2024-03-11', 1, '2026-05-24 18:00:00'),
(2600, '340142', 'Salauddin', 'Medical Promotion Officer', 'Chapainawabganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997140', 'salauddin266241@gmail.com', '2024-03-09', 1, '2026-05-24 18:00:00'),
(2601, '340143', 'Nondolal Basak', 'Senior Medical Promotion Officer', 'Lakhai\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994539', 'nondolalbasak50@gmail.com', '2024-03-10', 1, '2026-05-24 18:00:00'),
(2602, '340146', 'Litan Sen', 'Medical Promotion Officer', 'Raozan-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994804', 'senlitan418@gmail.com', '2024-03-09', 1, '2026-05-24 18:00:00'),
(2603, '340148', 'Md. Mahsin', 'Medical Promotion Officer', 'Amishapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995179', 'mdmohsin8155@gmail.com', '2024-03-09', 1, '2026-05-24 18:00:00'),
(2604, '340149', 'Md. Ahsan Habib', 'Medical Promotion Officer', 'Sherpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994105', 'ahsanhabibaccr@gmail.com', '2024-03-09', 1, '2026-05-24 18:00:00'),
(2605, '340153', 'Md. Shamiul Islam', 'Medical Promotion Officer', 'Sylhet-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993865', 'shamiulislam912@gmail.com', '2024-03-14', 1, '2026-05-24 18:00:00'),
(2606, '340155', 'Pradip Kumer Biswas', 'Medical Promotion Officer', 'Meherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319034', 'prodipbiswas5050@gmail.com', '2024-03-14', 1, '2026-05-24 18:00:00'),
(2607, '340159', 'Biplob Biswas', 'Senior Medical Promotion Officer', 'Bakerganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995579', 'Sushbiplop@gmai.com', '2024-03-20', 1, '2026-05-24 18:00:00'),
(2608, '340161', 'Md. Naim Hossain', 'Medical Promotion Officer', 'Chowmuhani-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995648', 'naimhossain255@gmail.com', '2024-03-20', 1, '2026-05-24 18:00:00'),
(2609, '340162', 'Abu Hasan', 'Senior Medical Promotion Officer', 'Jassore-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994997', 'hasan.abu942@gmail.com', '2024-03-19', 1, '2026-05-24 18:00:00'),
(2610, '340163', 'Mustakin Sagar', 'Medical Promotion Officer', 'DMCH-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994578', 'mdsagorhossain89@gmail.com', '2024-03-20', 1, '2026-05-24 18:00:00'),
(2611, '340164', 'Husne Mubarak', 'Medical Promotion Officer', 'Gajaria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994548', 'riazahammad74@gmail.com', '2024-03-20', 1, '2026-05-24 18:00:00'),
(2612, '340167', 'Md. Shafiqul Islam', 'Senior Medical Promotion Officer', 'Gazipur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319092', 'sarkerfaruk726@gmail.com', '2024-03-24', 1, '2026-05-24 18:00:00'),
(2613, '340168', 'Md. Azmul Hossain', 'Medical Promotion Officer', 'SSMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319089', 'azmulhossain1625@gmail.com', '2024-03-24', 1, '2026-05-24 18:00:00'),
(2614, '340170', 'Md. Suhal Rana', 'Medical Promotion Officer', 'Sonaimuri-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993906', 'sohelranak045@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2615, '340171', 'Md. Shadekur Rahman', 'Medical Promotion Officer', 'Mohakhali-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993852', 'swapon1001@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2616, '340172', 'Md. Ashraful Islam', 'Medical Promotion Officer', 'Alamdanga-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996833', 'ashrafulashraful5243@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2617, '340174', 'Md. Sahidul Islam', 'Medical Promotion Officer', 'Barguna-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994079', 'sahidul005566@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2618, '340175', 'Md. Khalid Imran', 'Medical Promotion Officer', 'Mawna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994817', 'khalidimranjiko@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2619, '340176', 'Subrta Kumar Howlader', 'Medical Promotion Officer', 'Companiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994632', 'subrtakumar825@gmail.com', '2024-03-26', 1, '2026-05-24 18:00:00'),
(2620, '340177', 'Md. Golam Rabbi', 'Medical Promotion Officer', 'Kalapara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995626', 'arafatrabbi341@gmail.com', '2024-03-28', 1, '2026-05-24 18:00:00'),
(2621, '340178', 'Md. Mostafizur Rahman', 'Medical Promotion Officer', 'Evercare-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995635', 'rah1994mn@gmail.com', '2024-03-27', 1, '2026-05-24 18:00:00'),
(2622, '340183', 'Md. Sumun Bhuiyan', 'Medical Promotion Officer', 'Hathazari-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994320', 'sumun5558800@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2623, '340184', 'Md. Golam Kabir', 'Medical Promotion Officer', 'DNMCH/Jurain', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996386', 'mdgolamkabir7874@gmail.com', '2024-03-27', 1, '2026-05-24 18:00:00'),
(2624, '340185', 'Md. Lajibur Rahaman Laju', 'Medical Promotion Officer', 'Delduar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993735', 'lasiburrahaman2172@gmail.com', '2024-03-30', 1, '2026-05-24 18:00:00'),
(2625, '340188', 'Jaynto Kumar', 'Medical Promotion Officer', 'Mugda-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994117', 'jaynto39@gmail.com', '2024-04-01', 1, '2026-05-24 18:00:00'),
(2626, '340190', 'Mizanur Rahman', 'Medical Promotion Officer', 'Khulna-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993232', 'mizanurrahman166@gmail.com', '2024-04-01', 1, '2026-05-24 18:00:00'),
(2627, '340193', 'Khurshed Ali', 'Medical Promotion Officer', 'Sylhet-C3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995105', 'smkhurshedalam708943@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2628, '340194', 'Md. Mosfekur Rahman', 'Medical Promotion Officer', 'Sirajganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319094', 'md.mosfekurrahman@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2629, '340195', 'Md. Alamin Mia', 'Medical Promotion Officer', 'Valuka', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319066', 'alamin08021994@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2630, '340197', 'Md. Salamat Ullah Pramanik', 'Medical Promotion Officer', 'Pabna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994523', 'salamatullahlimon@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2631, '340198', 'Md. Arif Billah', 'Senior Medical Promotion Officer', 'Dorbesherhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996873', 'billah01754@gmail.com', '2024-04-06', 1, '2026-05-24 18:00:00'),
(2632, '340199', 'Md. Abdullah Al Mamun', 'Senior Medical Promotion Officer', 'Gouripur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993792', 'smmamun18@gmail.com', '2024-04-05', 1, '2026-05-24 18:00:00'),
(2633, '340200', 'Md. Abdullah', 'Senior Medical Promotion Officer', 'Kasba-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995315', 'alm631568@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2634, '340201', 'Md. Rowshon Mia', 'Medical Promotion Officer', 'Habiganj-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993568', 'mdnowshon1993@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2635, '340202', 'Md. Sanowarul Islam', 'Medical Promotion Officer', 'Jamalpur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994439', 'asanowar96@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2636, '340203', 'Md. Sujan Ali', 'Senior Medical Promotion Officer', 'Dohazari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997172', 'sujon542n@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2637, '340204', 'Mohammad Liton Miah', 'Medical Promotion Officer', 'SHSMCH-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996734', 'liton.com16@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2638, '340207', 'Md. Ripon Miah', 'Medical Promotion Officer', 'Kazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995271', 'riponmiahmd83@gmail.com', '2024-04-08', 1, '2026-05-24 18:00:00'),
(2639, '340208', 'Md. Toyob Ali Sarkar', 'Senior Medical Promotion Officer', 'Netrakona-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994125', '7732.ayldnj@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2640, '340212', 'Md. Farhad Hossain', 'Medical Promotion Officer', 'Sylhet-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319096', 'farhaddgc@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2641, '340213', 'Md. Rakib Sikdar', 'Medical Promotion Officer', 'Narayanganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994287', 'sikdarrakib229@gmail.com', '2024-04-15', 1, '2026-05-24 18:00:00'),
(2642, '340216', 'Md. Rashedul Islam', 'Medical Promotion Officer', 'B.Baria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995125', 'postbox6760@gmail.com', '2024-05-12', 1, '2026-05-24 18:00:00'),
(2643, '340217', 'Md. Atikur Rahman', 'Senior Medical Promotion Officer', 'Sylhet-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995390', 'atikurrahmanatik085@gmail.com', '2024-05-11', 1, '2026-05-24 18:00:00'),
(2644, '340218', 'Amdadul Haque', 'Senior Medical Promotion Officer', 'Jhenaidah-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319035', 'amdadulhaque447@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2645, '340220', 'Md. Torikul Islam Mondal', 'Medical Promotion Officer', 'Uttara-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994289', 'torikulislamgb5@gmail.com', '2024-05-12', 1, '2026-05-24 18:00:00'),
(2646, '340222', 'Gopal Chan Kundu', 'Medical Promotion Officer', 'Jessore-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997023', 'gopalkundu9918@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2647, '340223', 'Md. Rabiul Islam', 'Medical Promotion Officer', 'Sonargaon', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995541', 'mdr789182@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2648, '340224', 'Jony Devdas', 'Medical Promotion Officer', 'CSCR-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996806', 'devdasjony8@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2649, '340225', 'Juyel Mahmud', 'Medical Promotion Officer', 'Chauddagram-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994247', 'juyelmahmud18@gmail.com', '2024-05-01', 1, '2026-05-24 18:00:00'),
(2650, '340228', 'Md. Sabuj Ahmed', 'Medical Promotion Officer', 'DNMCH/Asgar Ali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994298', 'sabujahmed757460@gmail.com', '2024-04-29', 1, '2026-05-24 18:00:00'),
(2651, '340230', 'Md. Ariful Hasan', 'Medical Promotion Officer', 'Mohakhali-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996376', 'arifulhasan8321@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(2652, '340232', 'Md. Tanvir Anjum', 'Medical Promotion Officer', 'Pekua-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995428', 'tanvir.anzum91@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2653, '340233', 'Md. Mohidul Islam', 'Medical Promotion Officer', 'Mirpur-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993953', 'imdmohidul624@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(2654, '340235', 'Md. Jasim Uddin', 'Medical Promotion Officer', 'DMCH-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994606', 'deltajasim764@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(2655, '340236', 'Md. Zinnatur Rahman', 'Medical Promotion Officer', 'Ibrahimpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996772', 'zinnaturrahman9@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(2656, '340237', 'Redoy Das', 'Medical Promotion Officer', 'Port Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995224', 'madyhridoy95@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2657, '340238', 'Md. Nurmomin', 'Medical Promotion Officer', 'Bhairab-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996371', 'nurmomin6@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2658, '340240', 'Hazrat Ali', 'Medical Promotion Officer', 'Jemison', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995542', 'hazratali115492@gmail.com', '2024-05-04', 1, '2026-05-24 18:00:00'),
(2659, '340243', 'Md. Mahfuzul Islam', 'Senior Medical Promotion Officer', 'Rajshahi-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996398', 'mahfuj.islam.2011@gmail.com', '2024-06-13', 1, '2026-05-24 18:00:00'),
(2660, '340244', 'Md. Shohidul Islam', 'Medical Promotion Officer', 'Birampur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995495', 'balyshohed@gmail.com', '2024-06-20', 1, '2026-05-24 18:00:00'),
(2661, '340246', 'Md. Chand Miah', 'Senior Medical Promotion Officer', 'Thakurgaon-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994628', 'chandmiah259@gmail.com', '2024-06-11', 1, '2026-05-24 18:00:00'),
(2662, '340247', 'Tarun Chandra Sarkar', 'Medical Promotion Officer', 'Bera-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994745', 'tarunchandrasarkar088@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2663, '340248', 'Md. Kudrat Ali', 'Medical Promotion Officer', 'Sirajganj-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995591', '007kudrat@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2664, '340249', 'Md. Sujon Ali', 'Medical Promotion Officer', 'Reazuddin Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995431', 'mdsujan68sujon@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2665, '340251', 'Choyta Kumar Barman', 'Medical Promotion Officer', 'Comilla-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997041', 'choyta93@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2666, '340252', 'Md. Sadekur Rahman', 'Medical Promotion Officer', 'Sylhet-C5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319052', 'srsobuj6975@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2667, '340253', 'Chakro Sharmma', 'Medical Promotion Officer', 'DMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993184', 'chakrosharmma2540@gmail.com', '2024-06-19', 1, '2026-05-24 18:00:00'),
(2668, '340254', 'Md. Nazmul Hassan', 'Medical Promotion Officer', 'CMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997158', 'nazmulh032@gmail.com', '2024-06-22', 1, '2026-05-24 18:00:00'),
(2669, '340256', 'Md. Ashraful Islam', 'Senior Area Sales Manager', 'Khulna-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993835', 'ashajsr09@gmail.com', '2024-06-05', 1, '2026-05-24 18:00:00'),
(2670, '340260', 'Md. Samim Reza', 'Medical Promotion Officer', 'Bogra-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993731', 'samimrezabaub@gmail.com', '2024-07-01', 1, '2026-05-24 18:00:00'),
(2671, '340261', 'Md. Abdul Alim', 'Medical Promotion Officer', 'Chandpur-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995239', 'pabnaalim@gmail.com', '2024-06-29', 1, '2026-05-24 18:00:00'),
(2672, '340263', 'Md. Redoy Islam', 'Medical Promotion Officer', 'Maijdee-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993935', 'mdredoykhan8090@gmail.com', '2024-06-29', 1, '2026-05-24 18:00:00'),
(2673, '340264', 'Md. Monir Hossen', 'Medical Promotion Officer', 'Feni-10', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994666', 'monirkhanmonir4567@gmail.com', '2024-06-29', 1, '2026-05-24 18:00:00'),
(2674, '340267', 'Abdullah Al Mahabub', 'Senior Medical Promotion Officer', 'Dhaka', 'UniHealth Limited', 'Dental & Ortho', '01989993893', 'almahbub90@yahoo.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2675, '340268', 'Md. Anowar Hossen', 'Senior Medical Promotion Officer', 'Atghoria', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993739', 'mdanwarhossen050@gmail.com', '2024-07-15', 1, '2026-05-24 18:00:00'),
(2676, '340270', 'Md. Milon Sakider', 'Medical Promotion Officer', 'Zakiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997246', 'milonsakidar7@gmail.com', '2024-07-16', 1, '2026-05-24 18:00:00'),
(2677, '340271', 'Md. Nurnoby', 'Medical Promotion Officer', 'Chatkhil-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997001', 'mdnurnoby005@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2678, '340272', 'Md. Mostafijur Rahman', 'Medical Promotion Officer', 'DMCH-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993969', 'umuhmostafiz1@gmail.com', '2024-07-11', 1, '2026-05-24 18:00:00'),
(2679, '340274', 'Md. Ramzan Ali', 'Senior Medical Promotion Officer', 'Shahjahanpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995613', 'ramzanali15800@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2680, '340275', 'Methun Chandro', 'Medical Promotion Officer', 'SHSMCH-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995515', 'methunroy1212@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2681, '340276', 'Md. Reaz Morshed', 'Medical Promotion Officer', 'Muksudpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995409', 'reazmorshed1120@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2682, '340277', 'Mahbur Rahman', 'Medical Promotion Officer', 'Ibn Sina', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995588', 'mahbur.cu12@gmail.com', '2024-07-15', 1, '2026-05-24 18:00:00'),
(2683, '340279', 'Pulok Chandro', 'Medical Promotion Officer', 'Habiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997247', 'pulokchandro1998@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2684, '340280', 'Md. Mijanur Rahaman', 'Senior Medical Promotion Officer', 'Rangpur-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994045', 'mijanur2022.bd@gmail.com', '2024-07-14', 1, '2026-05-24 18:00:00'),
(2685, '340283', 'Swarna Shuvra Sarkar', 'Medical Promotion Officer', 'Barguna-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995627', 'swarnashuvrasarkar@gmail.com', '2024-08-08', 1, '2026-05-24 18:00:00'),
(2686, '340284', 'Md. Toyubur Rahaman', 'Senior Medical Promotion Officer', 'National Hospital-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994257', 'tayub.official@gmail.com', '2024-08-06', 1, '2026-05-24 18:00:00'),
(2687, '340285', 'Md. Miraj Hossain', 'Senior Medical Promotion Officer', 'Doulatkhan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995524', 'mirajhossain24@gmail.com', '2024-08-05', 1, '2026-05-24 18:00:00'),
(2688, '340286', 'Md. Kazi Nazrul Islam', 'Medical Promotion Officer', 'Bashundhara-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997220', 'nazrulislam15021992@gmail.com', '2024-08-10', 1, '2026-05-24 18:00:00'),
(2689, '340288', 'Omar Faruk', 'Senior Medical Promotion Officer', 'Sylhet-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319056', 'faruk01031995@gmail.com', '2024-08-10', 1, '2026-05-24 18:00:00'),
(2690, '340291', 'Md. Sayed Ahammed', 'Senior Medical Promotion Officer', 'Hajiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319040', 'sayedshakil1993@gmail.com', '2024-08-10', 1, '2026-05-24 18:00:00'),
(2691, '340292', 'Md. Al Mamun', 'Senior Medical Promotion Officer', 'Bhandaria-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995630', 'md.almamun.c.a@gmail.com', '2024-08-12', 1, '2026-05-24 18:00:00'),
(2692, '340293', 'Arifur Rahman', 'Senior Medical Promotion Officer', 'Mymensingh-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994802', 'r.arifur1996@gmail.com', '2024-08-12', 1, '2026-05-24 18:00:00'),
(2693, '340294', 'Md. Mayeen Uddin', 'Senior Medical Promotion Officer', 'DMCH-D2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993183', 'mayeenuddin243@gmail.com', '2024-08-12', 1, '2026-05-24 18:00:00'),
(2694, '340295', 'Shobrata Sarker', 'Medical Promotion Officer', 'Manikganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993765', 'shobrata75@gmail.com', '2024-08-10', 1, '2026-05-24 18:00:00'),
(2695, '340296', 'Md. Enamul Haque', 'Medical Promotion Officer', 'SHSMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994895', 'enamulputhia74@gmail.com', '2024-08-12', 1, '2026-05-24 18:00:00'),
(2696, '340297', 'Md. Abdul Mazid', 'Medical Promotion Officer', 'Netrokona-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997213', 'mdabdulmazid469@gmail.com', '2024-08-10', 1, '2026-05-24 18:00:00'),
(2697, '340298', 'Anowar Hossain', 'Senior Medical Promotion Officer', 'Sirajganj-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993915', 'hossainanowar8463@gmail.com', '2024-09-01', 1, '2026-05-24 18:00:00'),
(2698, '340300', 'Abdul Motaleb', 'Senior Medical Promotion Officer', 'Narayanganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319114', 'abdulmotaleb6021@gmail.com', '2024-09-19', 1, '2026-05-24 18:00:00'),
(2699, '340301', 'Swapan Kumar Roy', 'Senior Medical Promotion Officer', 'Manikganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996891', 'swapankumarroy497@gmail.com', '2024-09-17', 1, '2026-05-24 18:00:00'),
(2700, '340303', 'Md. Sujon Hasan', 'Medical Promotion Officer', 'Narayanganj-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994209', 'sujonkhan62039@gmail.com', '2024-09-15', 1, '2026-05-24 18:00:00'),
(2701, '340304', 'Md. Hussain Reza', 'Medical Promotion Officer', 'Savar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996810', 'mdhussainreza523@gmail.com', '2024-09-28', 1, '2026-05-24 18:00:00'),
(2702, '340305', 'Md. Masud Rana', 'Medical Promotion Officer', 'Lalmonirhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994225', 'masudrana404916@gmail.com', '2024-09-21', 1, '2026-05-24 18:00:00'),
(2703, '340306', 'Md. Torikul Islam Shohag', 'Medical Promotion Officer', 'Sylhet-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319067', 'islamtorikuls1995@gmail.com', '2024-09-21', 1, '2026-05-24 18:00:00'),
(2704, '340307', 'Md. Abu Taher', 'Medical Promotion Officer', 'CMCH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997148', 'abutahertrishal193@gmail.com', '2024-09-21', 1, '2026-05-24 18:00:00'),
(2705, '340309', 'Md. Nazrul Islam', 'Medical Promotion Officer', 'Gopalganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997096', 'md.nazrulislamea88@gmail.com', '2024-09-18', 1, '2026-05-24 18:00:00'),
(2706, '340310', 'Md. Jahid Hasan', 'Medical Promotion Officer', 'Nasirnagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997238', 'jahid.nobin000@gmail.com', '2024-09-15', 1, '2026-05-24 18:00:00'),
(2707, '340311', 'Md. Razib Hossan', 'Medical Promotion Officer', 'Rajapur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996707', 'rhossan1994@gmail.com', '2024-09-14', 1, '2026-05-24 18:00:00'),
(2708, '340313', 'Mohammad Ibrahim Khalil', 'Medical Promotion Officer', 'Companiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994400', 'ibrahimkhalil581514@gmail.com', '2024-09-23', 1, '2026-05-24 18:00:00'),
(2709, '340314', 'Tonmoy Dutta', 'Medical Promotion Officer', 'Coxsbazar-IV', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996945', 'tonmoyemo@gmail.com', '2024-09-14', 1, '2026-05-24 18:00:00'),
(2710, '340317', 'K.M. Shapon', 'Senior Medical Promotion Officer', 'Mugda Medical', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994342', 'saponmd1996@gmail.com', '2024-10-27', 1, '2026-05-24 18:00:00'),
(2711, '340318', 'Md. Mamun Ali', 'Medical Promotion Officer', 'Thakurgaon', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993685', 'mamunali1994bu@gmail.com', '2024-10-30', 1, '2026-05-24 18:00:00'),
(2712, '340319', 'Rajib Kundu', 'Medical Promotion Officer', 'Magura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995265', 'rajibkundu6695@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2713, '340320', 'Md. Mostafa Amir Faisal', 'Medical Promotion Officer', 'Sylhet-C6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319098', 'amirfaisal959@gmail.com', '2024-10-03', 1, '2026-05-24 18:00:00'),
(2714, '340322', 'Noor Nabi', 'Medical Promotion Officer', 'Dinajpur-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996837', 'baburehan301@gmail.com', '2024-09-29', 1, '2026-05-24 18:00:00'),
(2715, '340324', 'Moniruz Zaman', 'Medical Promotion Officer', 'Narsingdi-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995641', 'moniruzzaman1341672@gmail.com', '2024-09-29', 1, '2026-05-24 18:00:00'),
(2716, '340325', 'Md. Zobaer Ibne Kashem', 'Medical Promotion Officer', 'Madhukhali-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993820', 'zobaeramih175@gmail.com', '2024-09-30', 1, '2026-05-24 18:00:00'),
(2717, '340326', 'Md. Mahbubul Alam', 'Senior Medical Promotion Officer', 'Netrokona-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996776', 'mahbubulalam5591@gmail.com', '2024-10-16', 1, '2026-05-24 18:00:00'),
(2718, '340327', 'Md. Aowal Hossain', 'Medical Promotion Officer', 'Gopalganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994280', 'aowalo19@gmail.com', '2024-10-17', 1, '2026-05-24 18:00:00'),
(2719, '340328', 'Md. Jahirul Islam', 'Medical Promotion Officer', 'Betagi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997031', 'jahirulislam1480@gmail.com', '2024-09-29', 1, '2026-05-24 18:00:00'),
(2720, '340329', 'Tufail', 'Medical Promotion Officer', 'Pakundia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996380', 'tufailahomed1@gmail.com', '2024-09-29', 1, '2026-05-24 18:00:00'),
(2721, '340330', 'Faisal Jahid', 'Senior Medical Promotion Officer', 'Barisal-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994853', 'jahid.raju004@gmail.com', '2024-09-28', 1, '2026-05-24 18:00:00'),
(2722, '340331', 'Md. Zillar Rahman', 'Medical Promotion Officer', 'Chuadanga-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995207', 'zillarr699@gmail.com', '2024-09-29', 1, '2026-05-24 18:00:00'),
(2723, '340332', 'Training MPO (IT)', 'Medical Promotion Officer', 'IT Test - MPO', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989990000', 'testmpo@gmail.com', '2024-09-01', 1, '2026-05-24 18:00:00'),
(2724, '340333', 'Training ASM (IT)', 'Area Sales Manager', 'IT Test - ASM', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989990101', 'testasm@gmail.com', '2024-09-28', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(2725, '340334', 'Training SM (IT)', 'Sales Manager', 'IT Test - SM', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989991010', 'testsm@gmail.com', '2024-09-28', 1, '2026-05-24 18:00:00'),
(2726, '340335', 'Md. Abdullah Al Rahman Ovy', 'Medical Promotion Officer', 'USTC-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319047', 'mdabdullahovy120@gmail.com', '2024-10-16', 1, '2026-05-24 18:00:00'),
(2727, '340336', 'Md. Shahadat Hossain Nazmul', 'Senior Medical Promotion Officer', 'Kuliarchar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996363', 'sh.nazmul2020@gmail.com', '2024-10-13', 1, '2026-05-24 18:00:00'),
(2728, '340337', 'Mohammad Ashfaqur Rahman', 'Medical Promotion Officer', 'Tangail-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994829', 'ashfaqurrahmanadnan@gmail.com', '2024-10-14', 1, '2026-05-24 18:00:00'),
(2729, '340338', 'Md. Mofidul Islam', 'Medical Promotion Officer', 'Harirumpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995468', 'mofidul.stat@gmail.com', '2024-10-14', 1, '2026-05-24 18:00:00'),
(2730, '340340', 'Md. Tipu Sultan', 'Medical Promotion Officer', 'Kamarkhand', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996760', 'salmanahmedtipus@gmail.com', '2024-10-14', 1, '2026-05-24 18:00:00'),
(2731, '340342', 'Md. Jahedul Islam', 'Senior Medical Promotion Officer', 'Popular KS-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994910', 'zahidmetro24@gmail.com', '2024-10-28', 1, '2026-05-24 18:00:00'),
(2732, '340343', 'Md. Saber Ali', 'Medical Promotion Officer', 'Thakurgaon-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994586', 'mdsaberali437@gmail.com', '2024-10-28', 1, '2026-05-24 18:00:00'),
(2733, '340344', 'Md. Ismail Hossen', 'Medical Promotion Officer', 'IBN SINA-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993505', 'ismailimtiaz007@gmail.com', '2024-10-30', 1, '2026-05-24 18:00:00'),
(2734, '340345', 'Md. Abu Bkkar Shiddik', 'Senior Medical Promotion Officer', 'Thakurgaon-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996987', 'aabubkkarshiddik@gmail.com', '2024-10-27', 1, '2026-05-24 18:00:00'),
(2735, '340346', 'Rashed Khan Milon', 'Medical Promotion Officer', 'Mohakhali-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993186', 'rashed.info25@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2736, '340347', 'Md. Aslam Hossain', 'Medical Promotion Officer', 'Maijdee-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997012', 'aslamhossain4519@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2737, '340348', 'Liton Kumar', 'Medical Promotion Officer', 'Shariatpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319037', 'dasliton1632@gmail.com', '2024-10-31', 1, '2026-05-24 18:00:00'),
(2738, '340349', 'Md. Foridul Islam', 'Senior Medical Promotion Officer', 'Kurigram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996962', 'faridulislam.raj007@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2739, '340350', 'Pranab Sikder', 'Senior Medical Promotion Officer', 'Bashundhara-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997143', 'pranabsikder27@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2740, '340351', 'Md. Dulal', 'Medical Promotion Officer', 'Sirajganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997040', 'dulal29793@gmail.com', '2024-11-02', 1, '2026-05-24 18:00:00'),
(2741, '340352', 'Md. Atoar Rahman', 'Senior Medical Promotion Officer', 'SSMCH-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996809', 'atoarrahman01767@gmail.com', '2024-11-03', 1, '2026-05-24 18:00:00'),
(2742, '340356', 'Md. Hazrat Belal', 'Medical Promotion Officer', 'Thakurgaon-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997028', 'hazratbelal701@gmail.com', '2024-11-14', 1, '2026-05-24 18:00:00'),
(2743, '340358', 'Md. Imran Hossain', 'Medical Promotion Officer', 'Mongla-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993451', 'imranmaruf284marufimran334@gmail.com', '2024-11-13', 1, '2026-05-24 18:00:00'),
(2744, '340360', 'Md. Hasanur Islam', 'Medical Promotion Officer', 'Bagerhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994470', 'mdhasanurislam808@gmail.com', '2024-11-15', 1, '2026-05-24 18:00:00'),
(2745, '340361', 'Md. Opu Mina', 'Medical Promotion Officer', 'Senpara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993386', 'opuhossen65@gmail.com', '2024-11-11', 1, '2026-05-24 18:00:00'),
(2746, '340363', 'Golam Kibria Royal', 'Medical Promotion Officer', 'Faridpur-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993883', 'golamkibriabd144@gmail.com', '2024-11-12', 1, '2026-05-24 18:00:00'),
(2747, '340365', 'Imran Sheikh', 'Medical Promotion Officer', 'Bhola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993708', 'imransheikh1322i@gmail.com', '2024-11-27', 1, '2026-05-24 18:00:00'),
(2748, '340367', 'Md. Fazla Rabby', 'Medical Promotion Officer', 'Baraiyarhat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997163', 'rabbydipu5568@gmail.com', '2024-11-30', 1, '2026-05-24 18:00:00'),
(2749, '340369', 'Md. Robiul Islam', 'Medical Promotion Officer', 'Ramganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997245', 'robiul04747@gmail.com', '2024-12-01', 1, '2026-05-24 18:00:00'),
(2750, '340370', 'Md. Dulal Hossain', 'Medical Promotion Officer', 'CMCH-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993580', 'm.dulalhossain39@gmail.com', '2024-11-30', 1, '2026-05-24 18:00:00'),
(2751, '340371', 'Muhin Ahmed', 'Medical Promotion Officer', 'Balaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994897', 'moyeentalukder@gmail.com', '2024-11-24', 1, '2026-05-24 18:00:00'),
(2752, '340374', 'Manindro Chandro Roy', 'Medical Promotion Officer', 'Debidwar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993824', 'manindro145@gmail.com', '2024-11-26', 1, '2026-05-24 18:00:00'),
(2753, '340375', 'Asif Ahammod', 'Senior Medical Promotion Officer', 'Kaliganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994938', 'pias.ahmed20@gmail.com', '2024-12-07', 1, '2026-05-24 18:00:00'),
(2754, '340376', 'Md. Rasel Sarkar', 'Senior Medical Promotion Officer', 'Pabna-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995182', 'mdraselsarkar869@gmail.com', '2024-12-07', 1, '2026-05-24 18:00:00'),
(2755, '340377', 'H.M. Shakil Mahamud', 'Senior Medical Promotion Officer', 'Mirpur-6/7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995362', 'shakilmahmud8596@gmail.com', '2024-12-08', 1, '2026-05-24 18:00:00'),
(2756, '340379', 'Md. Mirja Galib', 'Medical Promotion Officer', 'Kendua', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995560', 'galibhasan225702@gmail.com', '2024-12-08', 1, '2026-05-24 18:00:00'),
(2757, '340380', 'Shwapon Islam', 'Medical Promotion Officer', 'Debiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994749', 'shwaponislam29@gmail.com', '2024-12-08', 1, '2026-05-24 18:00:00'),
(2758, '340381', 'Md. Nazmul Hossain', 'Medical Promotion Officer', 'Gopalganj-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993815', 'mdnajmulhossain7083@gmail.com', '2024-12-08', 1, '2026-05-24 18:00:00'),
(2759, '340382', 'Nirmal Chandra Roy', 'Medical Promotion Officer', 'Rangpur-19', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995453', 'nirmalkumarroy475@gmail.com', '2024-12-09', 1, '2026-05-24 18:00:00'),
(2760, '340383', 'Md. Arafat Hossain', 'Senior Medical Promotion Officer', 'HFRCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993309', 'ahshuvo707@gmail.com', '2024-12-28', 1, '2026-05-24 18:00:00'),
(2761, '340384', 'Md. Alamin Hossain', 'Medical Promotion Officer', 'Raozan', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997157', 'alamintpwl@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2762, '340385', 'Ziaur Rahman', 'Senior Medical Promotion Officer', 'Rajshahi-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997058', 'mdziarahman76@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2763, '340386', 'Jagoth Kumar Kundu', 'Medical Promotion Officer', 'Narail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994120', 'jagotkunduump@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2764, '340387', 'Md. Shakhawat Hossen', 'Medical Promotion Officer', 'Sunamganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319095', 'shakhawatmoon1@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2765, '340390', 'Azizul Haque', 'Medical Promotion Officer', 'Uttara-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994382', 'likhonsarkar88@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2766, '340391', 'Rubel Miah', 'Medical Promotion Officer', 'Laksam-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993899', 'rubelmiah0696@gmail.com', '2024-12-26', 1, '2026-05-24 18:00:00'),
(2767, '340392', 'Md. Atiquzzaman Khan', 'Senior Officer, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989993891', 'atiqabrarkhan@gmail.com', '2024-12-18', 1, '2026-05-24 18:00:00'),
(2768, '340394', 'Md. Maminul Islam', 'Senior Medical Promotion Officer', 'Bogra-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994054', 'mdmaminulislam562@gmail.com', '2025-01-06', 1, '2026-05-24 18:00:00'),
(2769, '340395', 'Md. Mazedur Rahman', 'Medical Promotion Officer', 'Pabna-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994163', 'majedurmazed@gmail.com', '2025-01-07', 1, '2026-05-24 18:00:00'),
(2770, '340397', 'Md. Rahidul Islam', 'Senior Medical Promotion Officer', 'Max Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994738', 'rahidulislamrohid42@gmail.com', '2025-01-11', 1, '2026-05-24 18:00:00'),
(2771, '340398', 'Biplab Paul', 'Senior Medical Promotion Officer', 'Kishoreganj-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995121', 'biplabpaul01716@gmail.com', '2025-01-08', 1, '2026-05-24 18:00:00'),
(2772, '340399', 'Md. Nasir Uddin', 'Medical Promotion Officer', 'DMCH-D6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994834', 'mdnasiru912@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(2773, '340400', 'Md. Abdul Kuddus', 'Medical Promotion Officer', 'Rangpur-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996374', 'riponbabu120706@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(2774, '340401', 'Md. Alamin Hossain', 'Medical Promotion Officer', 'Sreenagar-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994691', 'alaminniloy599@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(2775, '340402', 'Md. Liton Farazi', 'Medical Promotion Officer', 'NIENT-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996759', 'ahasanlita493@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(2776, '340403', 'Ontor Kumar Sarkar', 'Medical Promotion Officer', 'Tuker Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995278', 'ontorsarkar18@gmail.com', '2025-01-09', 1, '2026-05-24 18:00:00'),
(2777, '340404', 'Md. Abdus Satter', 'Senior Medical Promotion Officer', 'Dhanmondi-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993124', 'sattersq11479@gmail.com', '2025-01-18', 1, '2026-05-24 18:00:00'),
(2778, '340405', 'Rostom Ali', 'Senior Medical Promotion Officer', 'Silonia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993870', 'rostom.ali6044@gmail.com', '2025-01-18', 1, '2026-05-24 18:00:00'),
(2779, '340406', 'Md. Nuruzzaman Miah', 'Area Sales Manager', 'Jamalpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994058', 'nuruzzaman1000@gmail.com', '2025-01-01', 1, '2026-05-24 18:00:00'),
(2780, '340407', 'Mohammad Helal Uddin', 'Area Sales Manager', 'BSMMU-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997110', 'mdhelalu735@gmail.com', '2025-01-07', 1, '2026-05-24 18:00:00'),
(2781, '340409', 'Chandan Kumar', 'Area Sales Manager', 'Madaripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993634', 'chandankumar.math34@gmail.com', '2025-01-07', 1, '2026-05-24 18:00:00'),
(2782, '340411', 'Arifuzzaman', 'Medical Promotion Officer', 'Amtali-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994264', 'arifuzzamanarif481@gmail.com', '2025-01-18', 1, '2026-05-24 18:00:00'),
(2783, '340413', 'G.M. Noor Islam', 'Medical Promotion Officer', 'Green Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994199', 'jasimgmnoorislam@gmail.com', '2025-02-01', 1, '2026-05-24 18:00:00'),
(2784, '340415', 'Md. Ebrahim', 'Medical Promotion Officer', 'Sylhet', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319093', 'ebrahim87885@gmail.com', '2025-02-05', 1, '2026-05-24 18:00:00'),
(2785, '340417', 'Md. Abdul Quddus', 'Area Sales Manager', 'Chandpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994040', 'quddusbpl@gmail.com', '2025-01-26', 1, '2026-05-24 18:00:00'),
(2786, '340418', 'Jasim Khan', 'Senior Medical Promotion Officer', 'Rajbari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319036', 'jasimkhanbd95@gmail.com', '2025-02-16', 1, '2026-05-24 18:00:00'),
(2787, '340420', 'Md. Shawkoth Ali', 'Medical Promotion Officer', 'Mount Adora-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993696', 'shawkots1993@gmail.com', '2025-02-15', 1, '2026-05-24 18:00:00'),
(2788, '340423', 'Md. Serajul Islam', 'Medical Promotion Officer', 'Comilla City-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993718', 'mdserajulislam664@gmail.com', '2025-02-15', 1, '2026-05-24 18:00:00'),
(2789, '340424', 'Md. Razaul Haque', 'Senior Medical Promotion Officer', 'Mirpur-7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994969', 'razaulhaque925@gmail.com', '2025-03-10', 1, '2026-05-24 18:00:00'),
(2790, '340426', 'Nur Hosen', 'Medical Promotion Officer', 'Habiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994781', 'nurhosen762@gmail.com', '2025-03-22', 1, '2026-05-24 18:00:00'),
(2791, '340427', 'Md. Sarowar Hossain', 'Medical Promotion Officer', 'Coxs Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993692', 'sarwarhossain1820@gmail.com', '2025-03-10', 1, '2026-05-24 18:00:00'),
(2792, '340429', 'Chancal Roy', 'Medical Promotion Officer', 'Mawna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995167', 'rchancal3@gmail.com', '2025-03-18', 1, '2026-05-24 18:00:00'),
(2793, '340430', 'Md. Mahmudul Hassan', 'Medical Promotion Officer', 'Narail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993959', 'mitashujon1995@gmail.com', '2025-03-10', 1, '2026-05-24 18:00:00'),
(2794, '340431', 'Md. Rejoan Hossain', 'Medical Promotion Officer', 'Bogra', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993566', 'rejoanhossain6349@gmail.com', '2025-03-16', 1, '2026-05-24 18:00:00'),
(2795, '340432', 'Mohammad Emran Miah', 'Medical Promotion Officer', 'Rampura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994845', 'mdemran.officials@gmail.com', '2025-03-16', 1, '2026-05-24 18:00:00'),
(2796, '340434', 'Md. Monarul Hossan', 'Senior Medical Promotion Officer', 'Gowainghat', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995642', 'monarulipl8373@gmail.com', '2025-03-10', 1, '2026-05-24 18:00:00'),
(2797, '340436', 'Md. Asaduzzaman', 'Medical Promotion Officer', 'CMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997150', 'asad.bp197@gmail.com', '2025-04-20', 1, '2026-05-24 18:00:00'),
(2798, '340438', 'Md. Al-Amin', 'Medical Promotion Officer', 'ShSMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319070', 'alamin325sujon@gmail.com', '2025-04-20', 1, '2026-05-24 18:00:00'),
(2799, '340439', 'Md. Tarikul Islam', 'Medical Promotion Officer', 'Maijdee-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994768', 'tarikulislamti2363575@gmail.com', '2025-04-20', 1, '2026-05-24 18:00:00'),
(2800, '340441', 'Bojlur Rahman', 'Medical Promotion Officer', 'Lohagara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319046', 'abiplab151@gmail.com', '2025-04-19', 1, '2026-05-24 18:00:00'),
(2801, '340442', 'Esahaque Ali', 'Medical Promotion Officer', 'Nilphamari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996915', 'esahaqueali625@gmail.com', '2025-04-20', 1, '2026-05-24 18:00:00'),
(2802, '340444', 'Abu Taleb', 'Medical Promotion Officer', 'Habiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319058', 'abutalebhosen31@gmail.com', '2025-04-22', 1, '2026-05-24 18:00:00'),
(2803, '340445', 'Rana Ahmed', 'Medical Promotion Officer', 'Narsingdi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996797', 'ranaahmedg43@gmail.com', '2025-04-14', 1, '2026-05-24 18:00:00'),
(2804, '340447', 'Md. Atikur Rahman', 'Medical Promotion Officer', 'Mitford-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995421', 'atikatikur357@gmail.com', '2025-04-15', 1, '2026-05-24 18:00:00'),
(2805, '340448', 'Md. Sirajul Islam', 'Medical Promotion Officer', 'Konabari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994807', 'sirajul1996md@gmail.com', '2025-04-28', 1, '2026-05-24 18:00:00'),
(2806, '340450', 'Md. Sohel Rana', 'Medical Promotion Officer', 'HajiGanj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993078', 'sohelranataj98@gmail.com', '2025-06-11', 1, '2026-05-24 18:00:00'),
(2807, '340451', 'Md. Abdul Kuddus', 'Medical Promotion Officer', 'Jamalpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996326', 'abdulkuddus1521@gmail.com', '2025-05-31', 1, '2026-05-24 18:00:00'),
(2808, '340453', 'Md. Sarwar-E-Alam', 'Medical Promotion Officer', 'Rajshahi-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993551', 'sohagbabu.88@gmail.com', '2025-05-31', 1, '2026-05-24 18:00:00'),
(2809, '340455', 'Md. Solaiman', 'Medical Promotion Officer', 'Mugda-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994283', 'mdsolaimanhaque9@gmail.com', '2025-05-31', 1, '2026-05-24 18:00:00'),
(2810, '340456', 'Shamal Chandra Roy', 'Medical Promotion Officer', 'Gazipur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997099', 'shamalray61@gmail.com', '2025-05-30', 1, '2026-05-24 18:00:00'),
(2811, '340457', 'Md. Iqbal Hossen', 'Medical Promotion Officer', 'Hajiganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997097', 'hosseniqbal2580@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2812, '340458', 'Md. Apel Mia', 'Medical Promotion Officer', 'DMCH-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994973', 'apelahmed70@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2813, '340459', 'Muhammad Oduduzzaman', 'Medical Promotion Officer', 'Satkhira-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993554', 'oduduzzaman@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2814, '340460', 'Md. Shafiul Alam', 'Medical Promotion Officer', 'DMCH-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994258', 'mdshafiulalam013@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2815, '340461', 'F.M. Al-Mahmud', 'Senior Medical Promotion Officer', 'Chandpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319017', 'mahmudfam@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2816, '340463', 'Md. Abu Sufian Lizar', 'Medical Promotion Officer', 'Bhangura', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994336', 'lizarlizu911@gmail.com', '2025-05-26', 1, '2026-05-24 18:00:00'),
(2817, '340465', 'Md. Ashadulla Shakil', 'Medical Promotion Officer', 'Chittagong', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995450', 'shakilparvej0@gmail.com', '2025-06-14', 1, '2026-05-24 18:00:00'),
(2818, '340466', 'Md. Eamin Hossain', 'Medical Promotion Officer', 'NIKDU-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997227', 'mk.firoz1999@gmail.com', '2025-06-22', 1, '2026-05-24 18:00:00'),
(2819, '340467', 'Md. Minhajul Islam', 'Medical Promotion Officer', 'Shariatpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993845', 'Minihajulislam4613@gmail.com', '2025-06-19', 1, '2026-05-24 18:00:00'),
(2820, '340469', 'Jafrul Islam Shourov', 'Medical Promotion Officer', 'Barisal-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319083', 'hmsourov97@gmail.com', '2025-06-19', 1, '2026-05-24 18:00:00'),
(2821, '340470', 'Md. Rashidul Hasan', 'Medical Promotion Officer', 'Galachipa-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994170', 'rashidulhera55@gmail.com', '2025-06-18', 1, '2026-05-24 18:00:00'),
(2822, '340471', 'Biplab Chandra Roy', 'Medical Promotion Officer', 'Kawkhali', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993313', 'biplabchandraroy2023@gmail.com', '2025-06-21', 1, '2026-05-24 18:00:00'),
(2823, '340472', 'Md. Mahmudul Hassan', 'Medical Promotion Officer', 'Barisal-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994456', 'manikmahmud9@gmail.com', '2025-06-16', 1, '2026-05-24 18:00:00'),
(2824, '340473', 'Sajal Biswas', 'Medical Promotion Officer', 'Khulna-D2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994137', 'sajal01828@gmail.com', '2025-06-18', 1, '2026-05-24 18:00:00'),
(2825, '340474', 'Md. Rubel Ali', 'Medical Promotion Officer', 'Barisal-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995568', 'rubeles201@gmail.com', '2025-06-22', 1, '2026-05-24 18:00:00'),
(2826, '340475', 'Md. Taifurul Islam Al Azad', 'Assistant General Manager, Sales', 'East Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993086', 'taifurul.70@gmail.com', '2025-06-22', 1, '2026-05-24 18:00:00'),
(2827, '340476', 'Md. Tuhin Hosen', 'Medical Promotion Officer', 'Patuakhali-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994786', '017764752st@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2828, '340477', 'Md Shaksadi', 'Medical Promotion Officer', 'BSMMU/Green Life-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995422', 'shaksadirahman108@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2829, '340478', 'Robiul Islam', 'Medical Promotion Officer', 'Kushtia-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994988', 'robiulislamnrb@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2830, '340479', 'Md. Malek Hossen', 'Senior Medical Promotion Officer', 'Natore-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995101', 'mmmalek.0022@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2831, '340480', 'Md. Imran Hossain', 'Medical Promotion Officer', 'Khulna-D4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994816', 'shakiljh2@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2832, '340481', 'Chayon Kumar Kundu', 'Senior Medical Promotion Officer', 'Gazipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994409', 'chayonpinku@gmail.com', '2025-06-29', 1, '2026-05-24 18:00:00'),
(2833, '340482', 'Nayan Chandra Roy', 'Medical Promotion Officer', 'Gaibandha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319115', 'nayanroy7878@gmail.com', '2025-07-03', 1, '2026-05-24 18:00:00'),
(2834, '340484', 'Sohanur Rahman', 'Medical Promotion Officer', 'Comilla-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994049', 'sohanrahman5597@gmail.com', '2025-07-14', 1, '2026-05-24 18:00:00'),
(2835, '340485', 'Tapon Biswas', 'Senior Medical Promotion Officer', 'SOMCH-1A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993616', 'biswastapon96@gmail.com', '2025-07-03', 1, '2026-05-24 18:00:00'),
(2836, '340486', 'Md. Rayhan Ali', 'Medical Promotion Officer', 'Manikganj-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994507', 'rayhankm93@gmail.com', '2025-07-22', 1, '2026-05-24 18:00:00'),
(2837, '340487', 'Usuf Ali', 'Medical Promotion Officer', 'Enayetpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995208', 'www.sagor47@gmail.com', '2025-07-19', 1, '2026-05-24 18:00:00'),
(2838, '340488', 'Abdullah Al Mamun', 'Medical Promotion Officer', 'Mitford-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995567', 'abdullahalmamun9959@gmail.com', '2025-07-19', 1, '2026-05-24 18:00:00'),
(2839, '340489', 'Abdul Awal', 'Senior Medical Promotion Officer', 'Chapainawabganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997080', 'abdulawalkhandokar.as@gmail.com', '2025-07-19', 1, '2026-05-24 18:00:00'),
(2840, '340490', 'Rayhan Khan', 'Medical Promotion Officer', 'Chhatak-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993599', 'mdrayhankhankhan190@gmail.com', '2025-07-22', 1, '2026-05-24 18:00:00'),
(2841, '340491', 'Md. Razowan Hossain', 'Medical Promotion Officer', 'NIENT-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994784', 'mdrazowanhossain@gmail.com', '2025-07-20', 1, '2026-05-24 18:00:00'),
(2842, '340493', 'Md Forkan Uddin', 'Senior Medical Promotion Officer', 'Maa O Shishu-III', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994814', 'mdforkanrafi@gmail.com', '2025-08-10', 1, '2026-05-24 18:00:00'),
(2843, '340494', 'Nobar Tripura', 'Senior Medical Promotion Officer', 'Central MCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994271', 'nobartripira0@gmail.com', '2025-08-10', 1, '2026-05-24 18:00:00'),
(2844, '340495', 'Md. Alomgir Hossain', 'Senior Medical Promotion Officer', 'Konabari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994291', 'alomgirkabir606153@gmail.com', '2025-08-10', 1, '2026-05-24 18:00:00'),
(2845, '340497', 'Sujoy Barua', 'Medical Promotion Officer', 'Teknaf-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996708', 'sujoybaruacox13@gmail.com', '2025-08-09', 1, '2026-05-24 18:00:00'),
(2846, '340498', 'Sorjo Sharma', 'Medical Promotion Officer', 'Sherpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319068', 'surjosharma159@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2847, '340499', 'Md. Moynul Islam', 'Medical Promotion Officer', 'Lakshmipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996791', 'moynularmina@gmail.com', '2025-08-16', 1, '2026-05-24 18:00:00'),
(2848, '340501', 'Md. Nayem', 'Medical Promotion Officer', 'Chandpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997014', 'nayem48t@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2849, '340502', 'Md. Nurullah Basar', 'Medical Promotion Officer', 'Rajbari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997075', 'nurullah0nhb@gmail.com', '2025-08-16', 1, '2026-05-24 18:00:00'),
(2850, '340503', 'Md. Anwar Hossain', 'Medical Promotion Officer', 'Mugda-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997185', 'anowar.ponir05@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2851, '340504', 'Istiyak Ahmed', 'Medical Promotion Officer', 'Mymensingh-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997029', 'istuyakahmed20050@gmail.com', '2025-08-14', 1, '2026-05-24 18:00:00'),
(2852, '340505', 'Md. Shahjahan Shiraj', 'Medical Promotion Officer', 'Kishoreganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319049', 'sshiraj022@gmail.com', '2025-08-14', 1, '2026-05-24 18:00:00'),
(2853, '340506', 'Md. Robiul Islam', 'Medical Promotion Officer', 'Barisal-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996332', 'robiul790922@gmail.com', '2025-08-16', 1, '2026-05-24 18:00:00'),
(2854, '340511', 'Md. Arifur Rahman', 'Medical Promotion Officer', 'Sylhet-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994927', 'mdarifurr292@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2855, '340513', 'Md. Murad Hassan', 'Medical Promotion Officer', 'BMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994639', 'muradmeer91@gmail.com', '2025-08-30', 1, '2026-05-24 18:00:00'),
(2856, '340515', 'Mizanur Rahman', 'Medical Promotion Officer', 'Derai-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995259', 'mizanurrahman6032@gmail.com', '2025-08-25', 1, '2026-05-24 18:00:00'),
(2857, '340516', 'Kiran Chakrabarti', 'Medical Promotion Officer', 'Bagerhat-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996774', 'chakrabartikiran910@gmail.com', '2025-08-27', 1, '2026-05-24 18:00:00'),
(2858, '340517', 'Md. Habibul Bashar', 'Medical Promotion Officer', 'MuMC/Goran', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993441', '', '2025-08-25', 1, '2026-05-24 18:00:00'),
(2859, '340518', 'Ahosan Habib', 'Medical Promotion Officer', 'Barisal-D1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993629', 'ahosan.h.soc@gmail.com', '2025-08-24', 1, '2026-05-24 18:00:00'),
(2860, '340519', 'Md. Jakir Hossen', 'Medical Promotion Officer', 'Comilla City-19', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993826', 'jakirj168@gmail.com', '2025-08-24', 1, '2026-05-24 18:00:00'),
(2861, '340520', 'Azizur Rahman Sheikh', 'Medical Promotion Officer', 'Rajbari-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994097', 'sheikhazizurrahman18@gmail.com', '2025-08-24', 1, '2026-05-24 18:00:00'),
(2862, '340522', 'Md. Roni', 'Medical Promotion Officer', 'Madhabpur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994799', 'mdronyislam3939@gmail.com', '2025-08-24', 1, '2026-05-24 18:00:00'),
(2863, '340523', 'Md. Saddam Hossain', 'Medical Promotion Officer', 'Goalabazar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994900', 'saddamkhangp1@gmail.com', '2025-08-25', 1, '2026-05-24 18:00:00'),
(2864, '340524', 'Md. Mahabub Hossain Jibun', 'Medical Promotion Officer', 'Farazy Hospital-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995310', 'mahabubjibon335@gmail.com', '2025-08-24', 1, '2026-05-24 18:00:00'),
(2865, '340525', 'Sajib Shaike', 'Senior Medical Promotion Officer', 'Bhanga-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994944', 'sajibsalcha@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2866, '340526', 'Md. Afzal Hasan', 'Medical Promotion Officer', 'Parkview-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993719', 'afzalhasan111@gmail.com', '2025-09-11', 1, '2026-05-24 18:00:00'),
(2867, '340527', 'Uday Chandra Roy', 'Medical Promotion Officer', 'Louhajanj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995189', 'royuday236@gmail.com', '2025-09-08', 1, '2026-05-24 18:00:00'),
(2868, '340528', 'Tanvir Rahman', 'Medical Promotion Officer', 'Syedpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994357', 'tanvirrahman635@gmail.com', '2025-09-08', 1, '2026-05-24 18:00:00'),
(2869, '340531', 'Md. Delwar Hossain', 'Medical Promotion Officer', 'Laxmipur-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319079', 'dhrabby583@gmail.com', '2025-09-06', 1, '2026-05-24 18:00:00'),
(2870, '340532', 'Md. Tamim Iqbal', 'Medical Promotion Officer', 'NMC-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993373', 'iqbaltanisha77@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2871, '340534', 'Md. Nasim Uddin Babu', 'Medical Promotion Officer', 'Chandanaish', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319078', 'nasimsgc@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2872, '340535', 'Milon Mondal', 'Medical Promotion Officer', 'Jessore-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997132', 'milonmondal3272@gmail.com', '2025-09-07', 1, '2026-05-24 18:00:00'),
(2873, '340537', 'Sajal Karmakar', 'Senior Medical Promotion Officer', 'BSMMU/Modern-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993910', 'sajalkarmakar951@gmail.com', '2025-08-28', 1, '2026-05-24 18:00:00'),
(2874, '340538', 'Md. Sujan Howlader', 'Medical Promotion Officer', 'BSMMU-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996811', 'sujanvam@gmail.com', '2025-09-13', 1, '2026-05-24 18:00:00'),
(2875, '340540', 'Md. Shafiqul Islam', 'Medical Promotion Officer', 'ShSMCH-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994162', 'shafiqulshi2017@gmail.com', '2025-09-13', 1, '2026-05-24 18:00:00'),
(2876, '340541', 'Anisur Rahaman', 'Medical Promotion Officer', 'Chhagalnaiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995486', 'akon42647@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2877, '340542', 'Sayem Hassan Sagor', 'Medical Promotion Officer', 'Pirojpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994055', 'sayemhassansagor@gmail.com', '2025-09-13', 1, '2026-05-24 18:00:00'),
(2878, '340543', 'Md. Sagor Sarder', 'Medical Promotion Officer', 'Shariatpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994532', 'sagor017576@gmail.com', '2025-09-17', 1, '2026-05-24 18:00:00'),
(2879, '340544', 'Md. Waheduzzaman', 'Medical Promotion Officer', 'Chauddagram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994736', 'waheduzzamansumon01091992@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2880, '340545', 'Md. Miraz Howlader', 'Medical Promotion Officer', 'Feni-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995477', 'mirazhowlader5@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2881, '340546', 'Md. Masud Rana', 'Medical Promotion Officer', 'Sonaimuri', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995440', 'masudrana52st@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2882, '340547', 'Md. Samrat', 'Medical Promotion Officer', 'Mymensingh-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993639', 'samratbabor8264@gmail.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2883, '340548', 'Md. Ibrahim Hossain', 'Medical Promotion Officer', 'Rajshahi-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996700', 'ibrahimhossain31121998@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2884, '340549', 'Md. Apple Hossain', 'Medical Promotion Officer', 'Fulpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993769', 'mdapplehossain1998@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2885, '340552', 'Md. Abdullah', 'Medical Promotion Officer', 'Mirpur-8', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994174', 'abdullahalmamun97221@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2886, '340555', 'Md. Shaiful Islam', 'Medical Promotion Officer', 'Meherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997087', 'si7820001@gmail.com', '2025-09-17', 1, '2026-05-24 18:00:00'),
(2887, '340556', 'Md. Mimlon Hosen', 'Medical Promotion Officer', 'Muktagacha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993836', 'mimlonhosen@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2888, '340557', 'Jewel Rana', 'Senior Medical Promotion Officer', 'Chandanaish', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996373', 'maranajewel1990@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2889, '340558', 'Md. Al-Amin', 'Medical Promotion Officer', 'Nazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995631', 'alaminr24@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2890, '340559', 'Md. Fazle Rabby', 'Medical Promotion Officer', 'Jamalpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993780', 'rabbysujor97@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2891, '340560', 'Md. Rajib Hossain', 'Medical Promotion Officer', 'Pabna-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996366', 'rajibhossainbu@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2892, '340561', 'Md. Belal Hossain', 'Medical Promotion Officer', 'Muradnagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994843', 'mdballal0094@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2893, '340562', 'Md Sajibur Rahman', 'Medical Promotion Officer', 'Kushtia-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996869', 'sksajiburrahman@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2894, '340563', 'Md. Rasel Rana', 'Medical Promotion Officer', 'Tangail-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994176', 'raselrana93305@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2895, '340564', 'Md. Sohag Howlader', 'Medical Promotion Officer', 'Comilla-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994955', 'sohagahmed01409@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2896, '340566', 'Md. Mafijur Rahman', 'Medical Promotion Officer', 'Rajshahi-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997026', '', '2025-09-21', 1, '2026-05-24 18:00:00'),
(2897, '340567', 'Md. Enamul Haque', 'Medical Promotion Officer', 'Rangpur-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994111', 'sujonshimu685@gmail.com', '2025-09-21', 1, '2026-05-24 18:00:00'),
(2898, '340568', 'Md. Shamim Hossain', 'Medical Promotion Officer', 'Narsingdi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994917', 'shamims.1919@gmail.com', '2025-09-23', 1, '2026-05-24 18:00:00'),
(2899, '340569', 'Md. Zahid Hassan', 'Medical Promotion Officer', 'Jamalpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997100', 'infozahidhassanbd@gmail.com', '2025-09-21', 1, '2026-05-24 18:00:00'),
(2900, '340570', 'Md. Fahim Hossain', 'Medical Promotion Officer', 'BSMMU-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993370', 'fahim104497@gmail.com', '2025-09-21', 1, '2026-05-24 18:00:00'),
(2901, '340572', 'Md. Shabique Tahmid', 'Medical Promotion Officer', 'Phulbari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995100', 'sabiktahmid99@gmail.com', '2025-09-20', 1, '2026-05-24 18:00:00'),
(2902, '340573', 'Md. Razaul Haque Mithun', 'Senior Medical Promotion Officer', 'Bellkuchi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993907', 'rezaraj24@gmail.com', '2025-09-22', 1, '2026-05-24 18:00:00'),
(2903, '340574', 'Md. Shofiqul Islam', 'Medical Promotion Officer', 'Manikganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994805', 'shofiqulislamatrai123@gmail.com', '2025-09-20', 1, '2026-05-24 18:00:00'),
(2904, '340576', 'Md. Rubel Rana', 'Medical Promotion Officer', 'Nawabganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994809', 'mdranarubel13579@gmail.com', '2025-09-22', 1, '2026-05-24 18:00:00'),
(2905, '340577', 'Md Jomir Uddin', 'Medical Promotion Officer', 'Patuakhali-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993823', 'md.jomiruddin0189@gmail.com', '2025-09-20', 1, '2026-05-24 18:00:00'),
(2906, '340578', 'Md. Raihan Ali', 'Medical Promotion Officer', 'Kishoreganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995240', 'maraihanali12580@gmail.com', '2025-09-20', 1, '2026-05-24 18:00:00'),
(2907, '340579', 'Md. Golam Azam', 'Medical Promotion Officer', 'Rajapur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993818', 'agolam423@gmail.com', '2025-09-21', 1, '2026-05-24 18:00:00'),
(2908, '340580', 'Md. Alamin Hossain', 'Medical Promotion Officer', 'MuMC/Maniknagar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994733', 'alaminhossain111307@gmail.com', '2025-09-20', 1, '2026-05-24 18:00:00'),
(2909, '340582', 'Md. Raihan Kabir', 'Area Sales Manager', 'Bogra-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994673', 'raihan.inceptapharma@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2910, '340583', 'Md. Arifur Rahman', 'Area Sales Manager', 'Narsingdi\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993642', 'arif01717851595@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2911, '340584', 'Md. Rezaul Karim Shuvo', 'Area Sales Manager', 'Dinajpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993115', 'bdshuvo1971@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2912, '340585', 'Md. Suman', 'Area Sales Manager', 'Mymensingh-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993943', 'mdsuman2207@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2913, '340586', 'Md. Feroz Khan', 'Senior Area Sales Manager', 'Rangpur-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993705', 'khanferoz28nov@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2914, '340587', 'Md. Mostayn Islam', 'Senior Area Sales Manager', 'BIRDEM', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994057', 'mostayn489@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2915, '340588', 'Md. Abul Kashem', 'Area Sales Manager', 'Barisal-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993077', 'kashembpl34@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2916, '340589', 'Sharifuzzaman', 'Senior Area Sales Manager', 'Chittagong-D', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996328', 'sharifzaman1985@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2917, '340590', 'Md. Masud Rana', 'Area Sales Manager', 'Mugda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994063', 'masud1890rana@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2918, '340591', 'Md. Hyatur Rahman', 'Senior Area Sales Manager', 'Savar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993936', 'hyaturrahman01@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2919, '340593', 'Md. Jahurul Islam', 'Area Sales Manager', 'Barisal-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997051', 'jahurulislam2025@gmail.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2920, '340594', 'Md. Mehadi Hasan', 'Area Sales Manager', 'NMC', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994160', 'mehadihasan81818@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2921, '340595', 'Md. Anwar Hossan', 'Area Sales Manager', 'Rangpur-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319077', 'anwarhossan.ipl@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2922, '340596', 'Mohammed Faisal', 'Senior Area Sales Manager', 'Chittagong-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994942', 'mahammedfaisal404@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2923, '340597', 'Md. Feroz Hosain', 'Senior Area Sales Manager', 'Nawabganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994366', 'feroz.hossaintf@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2924, '340598', 'Md. Mirazul Islam', 'Area Sales Manager', 'Mymensingh-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993223', 'mirazul57@yahoo.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2925, '340599', 'Md. Shamsul Alam', 'Senior Area Sales Manager', 'Laksam-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993722', 'md.shamsul0alam@gmail.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2926, '340600', 'Delawar Hosain Syedi ', 'Senior Area Sales Manager', 'Madhabpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993697', 'hossaindelawarhossain@gmail.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2927, '340601', 'KH. Sakib Hossain', 'Area Sales Manager', 'Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993208', 'khandakarsakib@yahoo.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2928, '340602', 'Md. Jahangir Hossen', 'Senior Area Sales Manager', 'Khulna', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996936', 'hossen11875@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2929, '340603', 'Alamgir Hossain', 'Area Sales Manager', 'Feni', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994614', 'alamgir.aci2015@gmail.com', '2025-09-15', 1, '2026-05-24 18:00:00'),
(2930, '340604', 'Md. Rashadul Hasan', 'Area Sales Manager', 'Savar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994734', 'babusorkar746@gmail.com', '2025-09-16', 1, '2026-05-24 18:00:00'),
(2931, '340605', 'Md. Nahid Siddique', 'Senior Area Sales Manager', 'Kushtia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994273', 'nahidsiddiquearup@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2932, '340606', 'Md. Emdadul Haque Ezarder', 'Area Sales Manager', 'Gopalganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993615', 'realeram949@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2933, '340607', 'Md. Tariqul Islam ', 'Senior Area Sales Manager', 'ShSMCH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993349', 'tareq98.islam@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2934, '340608', 'Md. Elias ', 'Senior Area Sales Manager', 'Narshingdi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993222', 'eliassagar017@gmal.com', '2025-09-18', 1, '2026-05-24 18:00:00'),
(2935, '340609', 'Md. Rashedul Hassan', 'Area Sales Manager', 'Naogaon', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993897', 'rasedukhasan32659@gmail.com', '2025-10-16', 1, '2026-05-24 18:00:00'),
(2936, '340610', 'Md. Aslam Hosain', 'Senior Medical Promotion Officer', 'Barisal-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995624', 'md.aslamhosain@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2937, '340611', 'Md. Nizam Uddin', 'Medical Promotion Officer', 'Mirpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994034', 'nizamuddinamc@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2938, '340612', 'Md. Alamin', 'Medical Promotion Officer', 'Kishoreganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994425', 'alamina12101997@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2939, '340613', 'Nazibullah', 'Medical Promotion Officer', 'Kachua', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995276', 'mdnazibullah@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2940, '340614', 'Md. Shariful Islam', 'Medical Promotion Officer', 'Panchagarh', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995369', 'Sorifsharifulislam226@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2941, '340615', 'Md. Shamim Hossain', 'Medical Promotion Officer', 'Gopalganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993365', 'shamimmagura80@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2942, '340616', 'Md. Hasanul Banna Saim', 'Medical Promotion Officer', 'Kasba/Akhaura/Kuti', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994522', 'hasanulbannasaim@gmail.com', '2025-10-06', 1, '2026-05-24 18:00:00'),
(2943, '340617', 'Md. Samimul Islam', 'Medical Promotion Officer', 'Jhenaidah-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996824', 's14w32@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2944, '340618', 'Md. Sohag Hossain', 'Medical Promotion Officer', 'Narsingdi-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994313', 'hossainsohag566@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2945, '340619', 'Mohammed Solaiman Hossain', 'Medical Promotion Officer', 'Amirabad', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995327', 'solaimanhossain2275@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2946, '340620', 'Md. Jahangir Alam', 'Medical Promotion Officer', 'Noapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996808', 'majahangir.rj@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2947, '340621', 'Abdullah Almamun', 'Medical Promotion Officer', 'Gouripur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995115', 'abdullah-al.mamunsheikh726@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2948, '340622', 'Suzan Kumar Sharma', 'Medical Promotion Officer', 'Faridpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997233', 'sujonshrma09@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(2949, '340623', 'Rayhan Hossain', 'Medical Promotion Officer', 'Sarail/Nasirnagar/Orail', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994690', 'rayhan1796@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(2950, '340624', 'Jishu Kumar Nath', 'Senior Medical Promotion Officer', 'Kalamia Bazar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994348', 'jishukumarn@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2951, '340625', 'Redoy Sarker', 'Senior Medical Promotion Officer', 'Chatkhil-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993523', 'ridoysarkar393@gmail.com', '2025-10-15', 1, '2026-05-24 18:00:00'),
(2952, '340626', 'Md. Shahin Reza', 'Medical Promotion Officer', 'CSCR-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994443', 'shahinreza220@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2953, '340627', 'Nazmul Islam Naim', 'Medical Promotion Officer', 'Sreenagar-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993751', 'nazmuli1997@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2954, '340628', 'Sumen Ray', 'Medical Promotion Officer', 'Popular SG-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993034', 'sumonray976@gmail.com', '2025-10-15', 1, '2026-05-24 18:00:00'),
(2955, '340629', 'Sharon Chandra Borman', 'Medical Promotion Officer', 'BSMMU-B4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994302', 'sharoncb19@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2956, '340631', 'Md. Rezaul Houqe', 'Medical Promotion Officer', 'Tongi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997089', 'rezaulhoque1990@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(2957, '340632', 'Sazzad Hossain', 'Medical Promotion Officer', 'Mitford-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993582', 'sazzadnjmi@gmail.com', '2025-10-13', 1, '2026-05-24 18:00:00'),
(2958, '340634', 'Alauddin', 'Medical Promotion Officer', 'Nabiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994222', 'ahmedalauddin274@gmail.com', '2025-10-25', 1, '2026-05-24 18:00:00'),
(2959, '340635', 'Md. Yousuf Mahmud', 'Medical Promotion Officer', 'Gazipur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993947', 'mahmudyusuf217@gmail.com', '2025-10-25', 1, '2026-05-24 18:00:00'),
(2960, '340636', 'Md. Imran Hossen', 'Medical Promotion Officer', 'Bhanga', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993087', 'imran70246@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2961, '340637', 'Md. Anamul Hassan', 'Medical Promotion Officer', 'B.Baria-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994466', 'mdaenamulhasan@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2962, '340638', 'Emtiaj Bulbul', 'Medical Promotion Officer', 'Uttara-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993188', 'emtiaj111@gmail.com', '2025-10-25', 1, '2026-05-24 18:00:00'),
(2963, '340640', 'Md. Ariful Islam', 'Medical Promotion Officer', 'Birampur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995620', 'mdariful1994@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2964, '340642', 'Nikhil Chandra Roy', 'Medical Promotion Officer', 'Rangpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993931', 'roynikhil798@gmail.com', '2025-10-28', 1, '2026-05-24 18:00:00'),
(2965, '340644', 'Md. Rubel Hossen', 'Senior Medical Promotion Officer', 'Sirajganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996331', 'rubelhossen82@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2966, '340645', 'Yunus Gazi', 'Senior Medical Promotion Officer', 'Kopilmuni,Tala Para', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997170', 'gmyunus8191@gmail.com', '2025-10-25', 1, '2026-05-24 18:00:00'),
(2967, '340646', 'Md. Balaet Hossain', 'Medical Promotion Officer', 'CIMCH-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994144', 'mbhb11111@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2968, '340647', 'Md. Alamin Hossain', 'Medical Promotion Officer', 'Goonabati', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994364', 'www.alaminhossain.duc@gmail.com', '2025-10-24', 1, '2026-05-24 18:00:00'),
(2969, '340648', 'Md. Asif Ali', 'Medical Promotion Officer', 'Alfadanga-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996939', 'ashifali121.bd@gmail.com', '2025-10-28', 1, '2026-05-24 18:00:00'),
(2970, '340649', 'Md. Sarwar Zahan', 'Medical Promotion Officer', 'Saturia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995281', 'sarwarzahan3910@gmail.com', '2025-10-25', 1, '2026-05-24 18:00:00'),
(2971, '340650', 'Md. Golam Mizan', 'Medical Promotion Officer', 'Matlab-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994916', 'golammizan11@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2972, '340651', 'Md. Bulbul Ahmed', 'Medical Promotion Officer', 'Barisal-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993949', 'mdbulbulahmed26@yahoo.com', '2025-10-26', 1, '2026-05-24 18:00:00'),
(2973, '340652', 'Md. Ainul Haque', 'Medical Promotion Officer', 'Nazirpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995347', '', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2974, '340653', 'Jahidul Islam', 'Medical Promotion Officer', 'Pirojpur-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993834', 'dicianjahidul1996@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2975, '340655', 'Shohag Alam', 'Medical Promotion Officer', 'Habiganj-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993103', 'shohagalam@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2976, '340656', 'Md. Kamal Hossain', 'Medical Promotion Officer', 'Delta', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996934', 'khassem568@gmail.com', '2025-10-26', 1, '2026-05-24 18:00:00'),
(2977, '340657', 'Md Muhimul Islam', 'Medical Promotion Officer', 'Raozan-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995378', 'muhimulislam93@gmail.com', '2025-10-26', 1, '2026-05-24 18:00:00'),
(2978, '340658', 'Pavel Chandra Nath', 'Medical Promotion Officer', 'General Hospital', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995340', 'pcnath2024@gmail.com', '2025-10-28', 1, '2026-05-24 18:00:00'),
(2979, '340659', 'Biplab Kumar', 'Medical Promotion Officer', 'Gulshan-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994903', 'bm2.kumar2@gmail.com', '2025-10-27', 1, '2026-05-24 18:00:00'),
(2980, '340660', 'Md. Tasim Khandakar', 'Medical Promotion Officer', 'Syedpur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995608', 'tasimkhandakar@gmail.com', '2025-11-08', 1, '2026-05-24 18:00:00'),
(2981, '340662', 'Md. Jasim Uddin', 'Medical Promotion Officer', 'Jhalakathi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994211', 'jasimkabir14@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2982, '340663', 'Md. Tariqul Islam', 'Medical Promotion Officer', 'Khulna-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994254', 'tariqul.jbc@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2983, '340664', 'Tanvir Ahmed', 'Senior Medical Promotion Officer', 'Khulna-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993591', 'tanvirppl1993@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2984, '340665', 'Sirajul Islam', 'Senior Medical Promotion Officer', 'Bhola', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997056', 'sirajislam234@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2985, '340666', 'Md. Zahid Hasan', 'Medical Promotion Officer', 'DMCH-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994452', 'jh717589@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2986, '340667', 'Md. Nurul Islam', 'Medical Promotion Officer', 'Nawabganj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994918', 'ni526769@gmail.com', '2025-11-23', 1, '2026-05-24 18:00:00'),
(2987, '340668', 'Md. Raziur Rahman', 'Medical Promotion Officer', 'NMC-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994815', 'mdraziurrahman402@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2988, '340669', 'Md. Atahar Ali', 'Medical Promotion Officer', 'Rajshahi-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994943', 'ataharalidany6@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2989, '340671', 'Mizanur Rahman', 'Medical Promotion Officer', 'Mymensingh-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994131', 'mizanurrahmanmizan060@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2990, '340672', 'Md. Anwar Hosain', 'Medical Promotion Officer', 'Munshiganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997013', 'anwar.7543@gmail.com', '2025-11-15', 1, '2026-05-24 18:00:00'),
(2991, '340673', 'Md. Emon Ali', 'Medical Promotion Officer', 'Manikganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994544', 'emonsha47@gmail.com', '2025-11-16', 1, '2026-05-24 18:00:00'),
(2992, '340674', 'Md. Sarowar Hossain', 'Senior Medical Promotion Officer', 'Jessore-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997135', 'sarowarhossainbpl@gmail.com', '2025-11-29', 1, '2026-05-24 18:00:00'),
(2993, '340675', 'Tasnim Alom', 'Medical Promotion Officer', 'Mirpur-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993417', 'alombubt@gmail.com', '2025-11-29', 1, '2026-05-24 18:00:00'),
(2994, '340676', 'Md. Al Mamun', 'Senior Medical Promotion Officer', 'Parshuram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993888', 'almamunsharker99@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(2995, '340678', 'Md. Moneruzzaman Bhuin', 'Medical Promotion Officer', 'Comilla-B7', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997187', 'monirhossain.zpl@gmail.com', '2025-12-13', 1, '2026-05-24 18:00:00'),
(2996, '340679', 'Bangka Roy', 'Medical Promotion Officer', 'Barlekha', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319085', 'bangkaroyhplbd@gmail.com', '2025-12-14', 1, '2026-05-24 18:00:00'),
(2997, '340680', 'Joyanta Kumar Adhikary', 'Medical Promotion Officer', 'Nangalkot-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997228', 'zakir22121989@gmail.com', '2025-12-03', 1, '2026-05-24 18:00:00'),
(2998, '340681', 'Kajal Kumar', 'Medical Promotion Officer', 'Chandrogonj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995416', 'kajalkumar017614@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(2999, '340682', 'Habibul Bashar', 'Medical Promotion Officer', 'Moheshkhali-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993872', 'habibulbashar2015@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(3000, '340683', 'Abdul Kader', 'Medical Promotion Officer', 'Nawabganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995575', 'ak2461888@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(3001, '340684', 'Md. Faruq Hossain Talokder', 'Sales Manager', 'South Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997252', 'faruqnandinaf@gmail.com', '2025-12-24', 1, '2026-05-24 18:00:00'),
(3002, '340685', 'S. M. Delwar Hossain', 'Assistant Sales Manager', 'Rangpur North', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994238', 'delwar196kh@gmail.com', '2025-12-09', 1, '2026-05-24 18:00:00'),
(3003, '340687', 'Mohammed Mosarrof Hosen', 'Medical Promotion Officer', 'Keranirhat-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996829', 'mosharrofctg010@gmail.com', '2025-12-27', 1, '2026-05-24 18:00:00'),
(3004, '340688', 'Bikash Chandra Halder', 'Medical Promotion Officer', 'Atwari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995590', 'bikashchandra771@gmail.com', '2025-12-24', 1, '2026-05-24 18:00:00'),
(3005, '340689', 'Kanchan Gowala', 'Medical Promotion Officer', 'Domar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993680', 'kanchangowala@gmail.com', '2025-12-22', 1, '2026-05-24 18:00:00'),
(3006, '340690', 'Md. Rasel Mia', 'Medical Promotion Officer', 'Rangpur-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997112', 'mdraselmia99344@gmail.com', '2025-12-27', 1, '2026-05-24 18:00:00'),
(3007, '340691', 'Md. Rezaul Karim', 'Medical Promotion Officer', 'Thakurgaon-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994454', 'rezaulca1998@gmail.com', '2025-12-27', 1, '2026-05-24 18:00:00'),
(3008, '340692', 'Md. Yousuf Ali', 'Medical Promotion Officer', 'Mirpur-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997104', 'yousuf0192591026@gmai.com', '2025-12-27', 1, '2026-05-24 18:00:00'),
(3009, '340693', 'Muhammad Shahriyer Iqbal', 'Sales Manager', 'North Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113800', 'shahriyer.iqbal80@gmail.com', '2025-12-17', 1, '2026-05-24 18:00:00'),
(3010, '340696', 'Mohammad Faruk Hossain', 'Sales Manager', 'East Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997253', 'mfhbpl@gmail.com', '2025-12-24', 1, '2026-05-24 18:00:00'),
(3011, '340697', 'Mohammad Majedul Hoq', 'Sales Manager', 'West Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997257', 'majedul1001@gmail.com', '2025-12-23', 1, '2026-05-24 18:00:00'),
(3012, '340698', 'Md. Delower Hossain', 'Sales Manager', 'Central Division', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997258', 'hossaindelwer063@gmail.com', '2025-12-24', 1, '2026-05-24 18:00:00'),
(3013, '340699', 'Sheikh Abdul Gafur', 'Senior Medical Promotion Officer', 'Nabinagar-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994758', 'sheikjgafur2022@gmail.com', '2026-01-05', 1, '2026-05-24 18:00:00'),
(3014, '340700', 'Md. Nur Alam Mia', 'Medical Promotion Officer', 'Feni-12', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997199', 'nuralamfarazi923@gmail.com', '2026-01-05', 1, '2026-05-24 18:00:00'),
(3015, '340701', 'Al-Mamun', 'Medical Promotion Officer', 'Coxsbazar-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995367', 'alm380272@gmail.com', '2026-01-05', 1, '2026-05-24 18:00:00'),
(3016, '340702', 'Md. Rahmatullah', 'Medical Promotion Officer', 'Shariakandi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994267', 'mdrahamatullag06@gamail.com', '2026-01-05', 1, '2026-05-24 18:00:00'),
(3017, '340704', 'Jahid Hasan', 'Medical Promotion Officer', 'Chapainawabganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996801', 'jahid192949@gmailcom', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3018, '340705', 'Md. Shohel Rana', 'Medical Promotion Officer', 'Joypurhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995605', 'shohelrana69301@gmail.com', '2026-01-31', 1, '2026-05-24 18:00:00'),
(3019, '340706', 'Md. Asharaful Alam', 'Medical Promotion Officer', 'Mymensingh-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996966', 'a76702051@gmail.com', '2026-01-31', 1, '2026-05-24 18:00:00'),
(3020, '340708', 'Pijush Kumar Roy', 'Medical Promotion Officer', 'Naogaon-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995601', 'pijush12248@gmail.com', '2026-01-31', 1, '2026-05-24 18:00:00'),
(3021, '340710', 'Md. Abdul Khalek', 'Medical Promotion Officer', 'Comilla-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997071', 'abdulkhalekrajbogha@gmail.com', '2026-02-07', 1, '2026-05-24 18:00:00'),
(3022, '340711', 'Sujan Kumar Biswas', 'Medical Promotion Officer', 'Chuadanga-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996956', 'sujankb.m@gmail.com', '2026-02-15', 1, '2026-05-24 18:00:00'),
(3023, '340712', 'Md. Abul Basar', 'Senior Medical Promotion Officer', 'Jamalpur-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993256', 'mabashar1989@gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3024, '340713', 'Ruman Chandra Das', 'Medical Promotion Officer', 'Sitakunda-I', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993856', 'rumandashat@gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3025, '340714', 'Md. Samim Hossen', 'Medical Promotion Officer', 'Lakshmipur-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993613', 'shamimmeazi63@gmail.com', '2026-02-02', 1, '2026-05-24 18:00:00'),
(3026, '340715', 'Md. Arman Ali', 'Medical Promotion Officer', 'MuMC/Nandipara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993464', 'mr.arman7725@gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3027, '340716', 'Md. Abdullah Al Masud', 'Senior Medical Promotion Officer', 'Rajbari-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995488', 'clickthemark2000@gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3028, '340717', 'Joarder Shahinur Rahman', 'Senior Medical Promotion Officer', 'Kalia', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995614', 'joardershahine45@gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3029, '340718', 'Md. Farhad Hossain', 'Medical Promotion Officer', 'KBFH-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995469', 'forhadhossain955@gmail.com', '2026-02-07', 1, '2026-05-24 18:00:00'),
(3030, '340719', 'Md. Abdur Razzak', 'Medical Promotion Officer', 'Padua', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993965', 'abdurra264@gmail.com', '2026-02-14', 1, '2026-05-24 18:00:00'),
(3031, '340720', 'Sobal Chandra Sutradhar', 'Medical Promotion Officer', 'Gouripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995324', 'subolsutradharsuba1421410@gmail.com', '2026-02-08', 1, '2026-05-24 18:00:00'),
(3032, '340721', 'Md. Rabeul Islam', 'Medical Promotion Officer', 'Sherpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995519', 'rabeulislamraffan@gmail.com', '2026-02-09', 1, '2026-05-24 18:00:00'),
(3033, '340722', 'Prodipan Mondal', 'Medical Promotion Officer', 'Shayestaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993276', 'dipanmondal62@gmail.com', '2026-02-09', 1, '2026-05-24 18:00:00'),
(3034, '340724', 'Md. Byazid Bostami', 'Senior Area Sales Manager', 'Munshiganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994093', 'byazidbostami83@gmail.com', '2026-02-02', 1, '2026-05-24 18:00:00'),
(3035, '340725', 'Md. Abdullah Al Mamun', 'Area Sales Manager', 'Ullapara', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993689', 'abdullahalmamununge@gmail.com', '2026-02-15', 1, '2026-05-24 18:00:00'),
(3036, '340726', 'Md. Tobibur Rahman', 'Senior Area Sales Manager', 'Barisal-C', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997053', 'tobibur.bd71@gmail.com', '2026-02-17', 1, '2026-05-24 18:00:00'),
(3037, '340728', 'Md. Mofazzel Alom', 'Senior Area Sales Manager', 'BSMMU-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994510', 'm.rana.8720@gmail.com', '2026-02-15', 1, '2026-05-24 18:00:00'),
(3038, '340729', 'Salahuddin', 'Senior Area Sales Manager', 'NICRH', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993400', 'salauddinkhan2268@gmail.com', '2026-02-16', 1, '2026-05-24 18:00:00'),
(3039, '340730', 'Sahadath Hossain', 'Medical Promotion Officer', 'Narayanganj-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994883', 'shahadat.rasel@gmail.com', '2026-02-15', 1, '2026-05-24 18:00:00'),
(3040, '340731', 'Md. Sanowar Hossain', 'Senior Medical Promotion Officer', 'Dupchanchia-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997152', 'sanowar2503@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3041, '340732', 'Md. Arif Hossain Shohag', 'Medical Promotion Officer', 'Haripur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996879', 'rs.arif421@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3042, '340733', 'Md. Obaydullah Alsumon', 'Medical Promotion Officer', 'Gasbari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994479', 'obaydullahalsumon@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3043, '340734', 'Md. Milon Hossain', 'Medical Promotion Officer', 'Narayanganj-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994625', 'milonmoynulislam@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3044, '340735', 'Parvez Ali', 'Medical Promotion Officer', 'Trishal', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995320', 'parvez.bpl.net@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3045, '340736', 'Hasibul Islam', 'Medical Promotion Officer', 'Mirpur Parish Road', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996738', 'hasibulkhan52@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3046, '340737', 'Md. Asaduzzaman Suzon', 'Medical Promotion Officer', 'DNMCH/Sutrapur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993153', 'susonasaduzzaman216@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3047, '340738', 'Sadequl Islam', 'Medical Promotion Officer', 'Narsingdi-4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993549', 'sadequl506@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3048, '340740', 'Md. Mosarraf Hossain', 'Medical Promotion Officer', 'Rajshahi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993348', 'mosarrafsp@gmail.com', '2026-03-02', 1, '2026-05-24 18:00:00'),
(3049, '340741', 'Md. Mahfuzar Rahman', 'Area Sales Manager', 'Bogra-B', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993108', 'mahfuzarrahmanmunna2@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3050, '340742', 'Md. Asaduzzaman', 'Area Sales Manager', 'Gournadi', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994420', '4769aplbd4@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3051, '340743', 'Md. Saheb Ali', 'Area Sales Manager', 'Satkhira', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113802', 'sahebali828@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3052, '340744', 'Md. Abu Hanif', 'Area Sales Manager', 'Hathazari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113803', 'apu7787@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3053, '340745', 'Md. Feroz Alam', 'Area Sales Manager', 'Jessore-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113804', 'feroz29021984@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3054, '340747', 'Md. Arafat Hossain', 'Medical Promotion Officer', 'Motijheel', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993755', 'ahmedarafat755@gmail.com', '2026-03-14', 1, '2026-05-24 18:00:00'),
(3055, '340749', 'Sajal Roy', 'Senior Medical Promotion Officer', 'Kushtia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994524', 'rsajal494@gmail.com', '2026-03-07', 1, '2026-05-24 18:00:00'),
(3056, '340750', 'Rana Parvej', 'Medical Promotion Officer', 'Madanpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994981', 'ranaparvej1738@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3057, '340751', 'Md. Nayan Ali', 'Medical Promotion Officer', 'Rajshahi-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997048', '', '2026-03-07', 1, '2026-05-24 18:00:00'),
(3058, '340752', 'S.M. Sazzad Bin-Islam', 'Medical Promotion Officer', 'Rajshahi-A1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994948', 'sazzaddbb@gmail.com', '2026-03-07', 1, '2026-05-24 18:00:00'),
(3059, '340753', 'Md. Rubal Islam', 'Medical Promotion Officer', 'Narinda', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994356', 'strongrubel345@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(3060, '340754', 'Md. Ripon Hossen', 'Medical Promotion Officer', 'Shibpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995520', 'riponhossen127@gmail.com', '2026-05-05', 1, '2026-05-24 18:00:00'),
(3061, '340755', 'Md. Al-Amin Islam', 'Medical Promotion Officer', 'Sunamganj-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994850', 'amin621658@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(3062, '340756', 'Md. Raihan Hossain', 'Medical Promotion Officer', 'Baraigram', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995451', 'emon950raj@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(3063, '340757', 'Md. Al-Amin', 'Medical Promotion Officer', 'BSMMU/Rampura-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993381', '10150.aplbd@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(3064, '340758', 'Dipu Chandra Mondal', 'Medical Promotion Officer', 'Chandragonj-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993927', 'dipayonm968@gmail.com', '2026-03-03', 1, '2026-05-24 18:00:00'),
(3065, '340759', 'Md. Asad Mia', 'Area Sales Manager', 'Golapgonj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993576', '7186.api1@gmailcom', '2026-04-01', 1, '2026-05-24 18:00:00'),
(3066, '340760', 'Md. Omar Faroque Chy.', 'Senior Medical Promotion Officer', 'Medical Center-II', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994467', 'omar85ripon@gmail.com', '2026-03-07', 1, '2026-05-24 18:00:00'),
(3067, '340761', 'Nurul Amin', 'Senior Area Sales Manager', 'Pirojpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993437', 'nurulamin457@gmail.com', '2026-03-11', 1, '2026-05-24 18:00:00'),
(3068, '340762', 'Md. Shahadot Hossen', 'Medical Promotion Officer', 'Jatrabari', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319082', '', '2026-03-31', 1, '2026-05-24 18:00:00'),
(3069, '340763', 'Md.  Abdur Rahman Rahad', 'Medical Promotion Officer', 'USTC-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995430', 'abdurrahmandict@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3070, '340764', 'Abu Helal', 'Medical Promotion Officer', 'Laksam', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997151', 'abuhelal917@gmail.com', '2026-04-05', 1, '2026-05-24 18:00:00'),
(3071, '340765', 'Rasel Akando', 'Medical Promotion Officer', 'Sylhet-B5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995181', 'raselakando515127@gmail.com', '2026-04-05', 1, '2026-05-24 18:00:00'),
(3072, '340766', 'Md. Amzad Hosen', 'Medical Promotion Officer', 'Halishahar', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995298', 'amzadhosenakash@gmail.com', '2026-04-05', 1, '2026-05-24 18:00:00'),
(3073, '340767', 'Md. Tanvir Molla', 'Medical Promotion Officer', 'Chittagong-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994617', 'tanvirmolla3137@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3074, '340768', 'Md. Shazzad Hossen', 'Senior Medical Promotion Officer', 'Natore-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993149', 'mdshazzad410@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3075, '340769', 'Md. Jabed Omar', 'Medical Promotion Officer', 'Patiya', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997092', 'ojabed21@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3076, '340770', 'Md. Sohel Rana', 'Medical Promotion Officer', 'Gobindaganj', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995506', 'sohelk4725@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3077, '340771', 'Md. Monirujjaman', 'Medical Promotion Officer', 'Uttara-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996965', 'monirujjaman949@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3078, '340772', 'Rayhan Chowdhury', 'Medical Promotion Officer', 'Uttara-C1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113853', 'crayhan47@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3079, '340773', 'Md. Masum Parvez', 'Senior Medical Promotion Officer', 'Tangail-6', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01993319088', 'masum533596@gmail.com', '2026-04-02', 1, '2026-05-24 18:00:00'),
(3080, '340774', 'Taj Uddin Ahmed', 'Medical Promotion Officer', 'Naogaon-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997133', 'ahmedhridoy2468@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3081, '340775', 'Md. Abu Taher', 'Medical Promotion Officer', 'Bogra-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993543', 'abu717135@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3082, '340776', 'Md. Israfil Hossen', 'Medical Promotion Officer', 'Uttara-A4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113851', 'israfilhossen1990@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3083, '340777', 'Md. Anowar Hossen', 'Medical Promotion Officer', 'Uttara-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113854', 'anowarhossen01764913202@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3084, '340778', 'Md. Suman Uddin', 'Senior Medical Promotion Officer', 'Nalchity', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995581', 'www.sumanuddin319@gmail.com', '2026-04-06', 1, '2026-05-24 18:00:00'),
(3085, '340779', 'Roman Uddin', 'Medical Promotion Officer', 'Maijdee-11', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994858', 'romanuddin167@gmailcom', '2026-04-06', 1, '2026-05-24 18:00:00'),
(3086, '340780', 'Md. Khairul Alam', 'Senior Medical Promotion Officer', 'Fakirapool', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993958', 'anik35185@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3087, '340781', 'Md. Aminul Islam', 'Medical Promotion Officer', 'Moulvibazar-B2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994253', 'mdaminulislam1947@gmail.com', '2026-04-06', 1, '2026-05-24 18:00:00'),
(3088, '340784', 'Nayon Chowdhury', 'Medical Promotion Officer', 'Daulatpur-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994584', '', '2026-04-06', 1, '2026-05-24 18:00:00'),
(3089, '340785', 'Biplob Roy', 'Medical Promotion Officer', 'B.Baria-C2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994208', 'broy58107@gmail.com', '2026-04-06', 1, '2026-05-24 18:00:00'),
(3090, '340786', 'Yasin Alam', 'Medical Promotion Officer', 'Feni-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997090', 'saikotrahman120@gmail.com', '2026-04-12', 1, '2026-05-24 18:00:00'),
(3091, '340787', 'Md. Firoz Mahmud', 'Area Sales Manager', 'Thakurgaon-A', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993081', 'mfiroz.unigroup@gmail.com', '2026-04-11', 1, '2026-05-24 18:00:00'),
(3092, '340788', 'Md. Sahin Alom', 'Senior Medical Promotion Officer', 'Uttara-A5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113852', 'sahinalam8120@gmail.com', '2026-04-28', 1, '2026-05-24 18:00:00'),
(3093, '340789', 'Md. Hasibul Islam', 'Medical Promotion Officer', 'Tongi-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113856', 'm38774647@gmail.com', '2026-05-23', 1, '2026-05-24 18:00:00'),
(3094, '340790', 'Md. Sumon', 'Medical Promotion Officer', 'Moksedpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113873', 'mdsumon01768421168@gmail.com', '2026-05-10', 1, '2026-05-24 18:00:00'),
(3095, '340791', 'Md. Jahidul Islam', 'Medical Promotion Officer', 'Kashiani', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113871', 'jmd882299@gmail.com', '2026-05-02', 1, '2026-05-24 18:00:00'),
(3096, '340792', 'Md. Abdur Rahim', 'Medical Promotion Officer', 'Mirpur', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994210', 'abdurrahim8341@gmail.com', '2026-05-19', 1, '2026-05-24 18:00:00'),
(3097, '340793', 'Shibu Chandra Das', 'Senior Medical Promotion Officer', 'Chandpur-5', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995583', 'shibudas521300@gmail.com', '2026-04-27', 1, '2026-05-24 18:00:00'),
(3098, '340794', 'Md. Rezaul Islam', 'Senior Medical Promotion Officer', 'Singair-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995117', 'islammdrezanu1055@gmail.com', '2026-05-21', 1, '2026-05-24 18:00:00'),
(3099, '340795', 'Asgar Kabir', 'Medical Promotion Officer', 'Stadium', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996843', 'asgarkabir2415@gmail.com', '2026-04-27', 1, '2026-05-24 18:00:00'),
(3100, '340796', 'Md. Rasel', 'Medical Promotion Officer', 'Amtali-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993559', 'rasel265603@gmail.com', '2026-04-26', 1, '2026-05-24 18:00:00'),
(3101, '340797', 'Nuruj Jamman', 'Medical Promotion Officer', 'B.Baria-C4', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994601', 'nurutge961@gmail.com', '2026-04-26', 1, '2026-05-24 18:00:00'),
(3102, '340798', 'Md. Shawon Hosen', 'Medical Promotion Officer', 'Matlab-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995215', 'srnasim9@gmail.com', '2026-04-27', 1, '2026-05-24 18:00:00'),
(3103, '340799', 'Md. Shamim Reza', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993087', '1998rezashamim@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3104, '340800', 'Md. Al-Mamun', 'Senior Medical Promotion Officer', 'Bogra-B1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993239', 'mamun118649@gmail.com', '2026-05-21', 1, '2026-05-24 18:00:00'),
(3105, '340801', 'Md. Sabbir Hossain', 'Medical Promotion Officer', 'Mitford-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989995518', 'sabbir900952@gmail.com', '2026-05-07', 1, '2026-05-24 18:00:00'),
(3106, '340802', 'Md. Rubel Ahmed', 'Medical Promotion Officer', 'Habiganj-A3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994154', '', '2026-05-07', 1, '2026-05-24 18:00:00'),
(3107, '340803', 'Snehashis Roy', 'Medical Promotion Officer', 'United Hospital-1', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993200', 'roysnehashis361@gmail.com', '2026-05-02', 1, '2026-05-24 18:00:00'),
(3108, '340804', 'Iqbal Khan', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '', 'mdkhaniqbal@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3109, '340805', 'Imranul Hasan Arman', 'Medical Promotion Officer', 'Kishoreganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01929993219', '', '2026-05-20', 1, '2026-05-24 18:00:00'),
(3110, '340806', 'Tareque Rahman', 'Medical Promotion Officer', 'Mymensingh-B3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989997136', 'tarequer@gmail.com', '2026-05-20', 1, '2026-05-24 18:00:00'),
(3111, '340807', 'Md. Shofiqul Islam', 'Senior Medical Promotion Officer', 'Jaintia-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996986', 'shofiqulislam20195@gmail.com', '2026-05-19', 1, '2026-05-24 18:00:00'),
(3112, '340808', 'Hanif Sikder', 'Senior Medical Promotion Officer', 'Fakirhat-2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996788', 'hanif361990@gmail.com', '2026-05-20', 1, '2026-05-24 18:00:00'),
(3113, '340809', 'Md. Jaynul Abedin', 'Senior Medical Promotion Officer', 'Lancet', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993895', 'mdjaynul24@gmail.com', '2026-05-19', 1, '2026-05-24 18:00:00'),
(3114, '340810', 'Sabuz Ali Sheikh', 'Medical Promotion Officer', 'Moulvibazar-A2', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994595', 'sabuz9030@gmail.com', '2026-05-20', 1, '2026-05-24 18:00:00'),
(3115, '340811', 'Manoj Mistry', 'Medical Promotion Officer', 'Nabiganj-3', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989996844', 'trenavnlitro95@gmail.com', '2026-05-19', 1, '2026-05-24 18:00:00'),
(3116, '340812', 'Md. Sabbir Hossain', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', 'Replace ', 'sabbirhossain56822@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3117, '340813', 'Mohammed Sala Uddin', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993853', 'salahu322@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3118, '340814', 'Md. Zahidul Islam', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994171', 'mdzahidul.info@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3119, '340815', 'Md. Gawsul Azom', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '', 'gawsulazom11@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3120, '340816', 'Md. Rana Gazi', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01335113857', 'ranagazi912@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3121, '340817', 'Md. Shamim Hossain', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989994947', 'shamimhossainsh1234@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3122, '340818', 'Md. Abdul Khalek', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Sales', '01989993666', 'khalekma023@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3123, '350135', 'Md. Miraj', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01961411026', '', '2024-02-04', 1, '2026-05-24 18:00:00'),
(3124, '350413', 'Md. Kawsar Korrani', 'Junior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01776309114', 'mdmunna309114@gmail.com', '2025-11-13', 1, '2026-05-24 18:00:00'),
(3125, '360001', 'Md. Saleh Uddin Shihab', 'Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01750260781', 'salehuddinshisab@gmail.com', '2023-11-08', 1, '2026-05-24 18:00:00'),
(3126, '360002', 'Aminur Islam', 'Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01704600705', 'aminurislam840276@gmail.com', '2023-11-08', 1, '2026-05-24 18:00:00'),
(3127, '360003', 'Md. Mehedi Hasan Munna', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01722202850', 'mehedi18687@gmail.com', '2023-11-11', 1, '2026-05-24 18:00:00'),
(3128, '360005', 'Imtiaj Ahmed', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01717475963', 'imtiajahmed00007@gmail.com', '2023-11-16', 1, '2026-05-24 18:00:00'),
(3129, '360006', 'Md. Naimur Rahman Naim Samrat', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01767025925', 'naimur.sub@gmail.com', '2023-11-16', 1, '2026-05-24 18:00:00'),
(3130, '360007', 'Joy Saha', 'Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01686264726', 'joysaha11793@gmail.com', '2023-12-02', 1, '2026-05-24 18:00:00'),
(3131, '360011', 'Al Amin', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01644921433', 'aaminmiu6796@gmail.com', '2024-01-18', 1, '2026-05-24 18:00:00'),
(3132, '360012', 'Md. Nurul Islam', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01757323296', 'nurul57323296@gmail.com', '2024-01-24', 1, '2026-05-24 18:00:00'),
(3133, '360013', 'S M Mahfuzur Rahman', 'Director, R & D', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01989994301', 'smmahfuzur.rahman@unigroup-bd.com', '2018-09-01', 1, '2026-05-24 18:00:00'),
(3134, '360015', 'Arif Hossain', 'Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01686204480', 'arifjfn@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(3135, '360016', 'Md. Al-Amin Hossain', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01860447617', 'alamin168998@gmail.com', '2024-05-26', 1, '2026-05-24 18:00:00'),
(3136, '360018', 'Zahid Hasan', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01521332375', 'Zahidadnan0@gmail.com', '2024-06-01', 1, '2026-05-24 18:00:00'),
(3137, '360020', 'Mir Tariqul Islam', 'Junior Warehouse Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01775904496', 'mirtariqul1996@gmail.com', '2024-06-01', 1, '2026-05-24 18:00:00'),
(3138, '360021', 'Sheikh Abid Hasan', 'Deputy Manager, Engineering', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989993973', 'abid.engg10@gmail.com', '2024-07-06', 1, '2026-05-24 18:00:00'),
(3139, '360023', 'Md. Ashik Ul Islam', 'Manager, Commercial', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited', 'Commercial', '01989997292', 'ashik.islam@unigroup-bd.com', '2024-09-08', 1, '2026-05-24 18:00:00'),
(3140, '360024', 'Md. Shahinur Alam', 'Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01993319032', 'shanupharma@gmail.com', '2024-12-21', 1, '2026-05-24 18:00:00'),
(3141, '360025', 'Md. Asaduzzaman', 'Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01993319033', 'asaduzzaman236@gmail.com', '2025-01-04', 1, '2026-05-24 18:00:00'),
(3142, '360026', 'Md. Pias Uddin', 'ETP Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01756500020', 'piasuddin00020@gmail.com', '2024-12-21', 1, '2026-05-24 18:00:00'),
(3143, '360027', 'Md. Rafiat Khan Chowdhury', 'Officer, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989997294', 'rafiatkhanchowdhury@gmail.com', '2024-12-19', 1, '2026-05-24 18:00:00'),
(3144, '360029', 'Jubawel Islam', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989994013', 'jubawelislamjesun@gmail.com', '2025-02-01', 1, '2026-05-24 18:00:00'),
(3145, '360031', 'Md. Siddikur Rahman', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01935940557', 'siddikurrahman613@gmail.com', '2025-03-20', 1, '2026-05-24 18:00:00'),
(3146, '360032', 'Russel Mia', 'Officer, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989997297', 'russel14138@gmail.com', '2025-03-12', 1, '2026-05-24 18:00:00'),
(3147, '360033', 'Md. Ratan Ali', 'Junior Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01811315311', 'monirulislam.ratan16@gmail.com', '2025-04-06', 1, '2026-05-24 18:00:00'),
(3148, '360034', 'Md. Rasheduzzaman', 'Analyst', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01745711473', 'rasheduzzamanmd10@gmail.com', '2025-04-19', 1, '2026-05-24 18:00:00'),
(3149, '360035', 'Rakib Uddin Rasel', 'Senior Officer, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989997293', 'rasel240@gmail.com', '2025-04-27', 1, '2026-05-24 18:00:00'),
(3150, '360036', 'Md. Mizanur Rahhman', 'Manager, Quality Assurance', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01989993982', 'mzrahman.pharma@gmail.com', '2025-04-27', 1, '2026-05-24 18:00:00'),
(3151, '360037', 'Md. Hasibul Hosen Shanto', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01865999459', 'mdshanto132k@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3152, '360039', 'Saleh Mahmud Shakil', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01894091208', 'sk4691859@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3153, '360040', 'Shamim Ahmmed', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01781198164', 'shamimahmmed2876@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3154, '360041', 'Md. Rakibul', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01816991776', 'fariarkhanr@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3155, '360042', 'Md. Ali Hasan Mujahid', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01704942360', 'mdmojahidislam968@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3156, '360044', 'Md. Asadul Islam', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01758677666', 'mdasadulislam5035@gmail.com', '2025-04-09', 1, '2026-05-24 18:00:00'),
(3157, '360046', 'Md. Aminul Islam', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01799238998', 'aminulislam59213@gmail.com', '2025-05-05', 1, '2026-05-24 18:00:00'),
(3158, '360047', 'Md. Nayeem', 'Assistant Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01686814410', 'md.nayeem.649348@gmail.com', '2025-05-12', 1, '2026-05-24 18:00:00'),
(3159, '360050', 'Md. Khalekuzzaman', 'General Manager, Welfare & Administration', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Welfare & Administration', '01989997291', 'azad74@gmail.com', '2025-06-01', 1, '2026-05-24 18:00:00'),
(3160, '360051', 'Md. Toki Shahryar Chowdhury', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01721828465', '', '2025-06-24', 1, '2026-05-24 18:00:00'),
(3161, '360052', 'Md. Abdul Kader', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01792958533', 'mdabdulkader8533@gmail.com', '2025-06-24', 1, '2026-05-24 18:00:00'),
(3162, '360053', 'Md. Aowrangozeb', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01786083217', 'mdaowrangozeb.info@gmail.com', '2025-06-24', 1, '2026-05-24 18:00:00'),
(3163, '360054', 'Md. Mohorom Ali Sourov', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01950646425', 'ysourov151@gmail.com', '2025-06-24', 1, '2026-05-24 18:00:00'),
(3164, '360055', 'Nobel Khan Anik', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01747602406', 'nobelnobel225@gmail.com', '2025-06-24', 1, '2026-05-24 18:00:00'),
(3165, '360056', 'Golam Rabby Siam', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01717808985', 'golamrabby.diu@gmail.com', '2025-07-09', 1, '2026-05-24 18:00:00'),
(3166, '360057', 'Ekramul Shafi', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01714576111', 'ekramulshafi@gmail.com', '2025-07-12', 1, '2026-05-24 18:00:00'),
(3167, '360058', 'Ahsan Habib', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01796680756', 'ahsanhabibsub@gmail.com', '2025-08-02', 1, '2026-05-24 18:00:00'),
(3168, '360059', 'Md. Yousufali', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01756426153', 'youshuf6153@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3169, '360060', 'Atikujjaman Sykat', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01571320232', 'sykatss2020@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3170, '360061', 'Md. Nirob Mia', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01316062575', 'nirobmiyan608@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3171, '360062', 'Seyam Ahmed', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01679374091', 'asmseyamo9@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3172, '360063', 'Md. Lutfar Rahman', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01304128076', 'smlutfor5676@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3173, '360064', 'Parvez Rana', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01715875309', 'par413370@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3174, '360065', 'Salmanur Rahman', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01806569218', 'salman288419@gmail.com', '2025-07-26', 1, '2026-05-24 18:00:00'),
(3175, '360066', 'Rashedul Islam', 'Microbiologist', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Control', '01601173187', 'rashed.microbiology@gmail.com', '2025-09-13', 1, '2026-05-24 18:00:00'),
(3176, '360067', 'Tauhid Hosen Rijbhi', 'Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01643651266', 'tawheidhossein901@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(3177, '360068', 'Md. Raihan Parvez', 'Senior Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01909454243', 'raihanparvez207@gmail.com', '2025-09-03', 1, '2026-05-24 18:00:00'),
(3178, '360070', 'Md. Mahbubur Rahman', 'Senior Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01744714388', 'mahbubqcom@gmail.com', '2025-10-04', 1, '2026-05-24 18:00:00'),
(3179, '360071', 'Muhammad Rifat-Al-Islam', 'Assistant Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01570251811', 'rifatalislam7398@gmail.com', '2025-10-05', 1, '2026-05-24 18:00:00'),
(3180, '360072', 'Mamunur Roshid Rony', 'Manager, Warehouse', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Warehouse', '01989997289', 'ronyma2603@gmail.com', '2025-11-01', 1, '2026-05-24 18:00:00'),
(3181, '360073', 'Md. Abdullah - Al - Mamun', 'Production Manager', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01989997288', 'nov0782@gmail.com', '2025-11-01', 1, '2026-05-24 18:00:00'),
(3182, '360074', 'Al Ahsan Ringkon', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01836943490', 'alahsan1272@gmail.com', '2026-03-01', 1, '2026-05-24 18:00:00'),
(3183, '360075', 'Md. Al Amin Badhon', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01776801938', 'alaminbadhon184@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(3184, '360076', 'Rabiul Islam Shuvo', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01768207842', 'rabiulislamshuvo82@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(3185, '360077', 'Md. Polash Miya', 'Sub Assistant Production Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01612296695', 'mdpolashmiya682@gmail.com', '2025-12-06', 1, '2026-05-24 18:00:00'),
(3186, '360079', 'Milon Mondol', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01892448633', 'milon18phr032@gmail.com', '2026-01-12', 1, '2026-05-24 18:00:00');
INSERT INTO `employees` (`id`, `pf_no`, `full_name`, `designation`, `job_location`, `company`, `department`, `phone`, `email`, `joining_date`, `is_active`, `created_at`) VALUES
(3187, '360080', 'Md. Shakil', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01728347944', 'mdshakil151215@gmail.com', '2026-01-21', 1, '2026-05-24 18:00:00'),
(3188, '360081', 'Md. Nahid Hossen', 'Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01715550705', 'nahid.hossen@outlook.com', '2026-03-01', 1, '2026-05-24 18:00:00'),
(3189, '360082', 'Prodiptho Chakraborty', 'Senior Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01709228366', 'prodipthochakraborty@gmail.com', '2026-04-01', 1, '2026-05-24 18:00:00'),
(3190, '360083', 'Md. Mahafujur Rahaman', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01987102738', 'mahafujur14035312@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3191, '360084', 'Md. Shohan', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01712441945', 'mdshohan2646@gmail.com', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3192, '360085', 'Harasit Ojha', 'Production Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01757749877', 'harasitojha77@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3193, '360086', 'Rashedul Hasan Limon', 'Junior Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01755359203', 'limon101999@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3194, '360087', 'Md. Raju Hossain', 'Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01993235294', 'mdrajuhossain723@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3195, '360088', 'Jahidul Hassan Ratul', 'Junior Product Development Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Product Development', '01726848495', 'zahidulratul27@gmail.com', '2026-03-08', 1, '2026-05-24 18:00:00'),
(3196, '360089', 'Saurav Sarker', 'Quality Assurance Officer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Quality Assurance', '01765181586', 'saurav29-193@diu.edu.bd', '2026-04-04', 1, '2026-05-24 18:00:00'),
(3197, '360090', 'Md. Mostafizur Rahman', 'Senior Maintenance Engineer', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Engineering', '01989996995', 'mostafiz.unigroupbd@gmail.com', '2026-04-22', 1, '2026-05-24 18:00:00'),
(3198, '500002', 'Md. Shoumik Hassan', 'Analyst', 'Factory,Gazaria', 'UniMed UniHealth Fine Chemicals Limited', 'Research & Development', '01911808578', 'shoumikh1@gmail.com', '2021-09-09', 1, '2026-05-24 18:00:00'),
(3199, '500004', 'Md. Shakhawat Hossain', 'Deputy Manager', 'Factory,Gazaria', 'UniMed UniHealth Fine Chemicals Limited', 'Research & Development', '01989996961', 'mdshakhawat.hossain@unigroup-bd.com', '2022-09-03', 1, '2026-05-24 18:00:00'),
(3200, '600002', 'Khaleda Akther', 'Front Desk Manager', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01989994499', 'khalada.biomed@gmail.com', '2018-03-01', 1, '2026-05-24 18:00:00'),
(3201, '600003', 'Md. Shihab Uddin Shipon', 'Phlebotomist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01712822880', 'mrshipon.khan@gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(3202, '600006', 'Md. Zony Ahmed', 'Assistant Engineer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01989994227', 'zonyahmed21@gmail.com', '2015-09-11', 1, '2026-05-24 18:00:00'),
(3203, '600007', 'Ganesh Chandra Mondal', 'Phlebotomist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01732575589', 'goneshchandromondal@gmail.com', '2014-04-11', 1, '2026-05-24 18:00:00'),
(3204, '600009', 'Md. Atikur Rahman', 'Pharmacist (A Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01929993350', 'atikurra97@gmail.com', '2016-01-21', 1, '2026-05-24 18:00:00'),
(3205, '600014', 'Mohammad Kamruzzaman Lavin Talukder', 'Pharmacist (A Grade)', 'BioMed Online Sales', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989996740', 'lavinpharmacydiu@gmail.com', '2022-08-01', 1, '2026-05-24 18:00:00'),
(3206, '600030', 'Tanema Sultana', 'Officer, Admin & Accounts', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01748838484', '', '2019-09-24', 1, '2026-05-24 18:00:00'),
(3207, '600039', 'G M Jahangir Alam', 'Senior Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01745855555', 'jahangir.alam1987@gmail.com', '2010-12-01', 1, '2026-05-24 18:00:00'),
(3208, '600040', 'Sirdhartho Biswas', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01533059156', 'shiddertho@gmail.com', '2019-07-15', 1, '2026-05-24 18:00:00'),
(3209, '600041', 'Nasima Zaman', 'Assistant Manager, Front Desk', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01989995559', '', '2019-04-15', 1, '2026-05-24 18:00:00'),
(3210, '600043', 'Md. Moniruzzaman Asad', 'Junior Scientific Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01776847278', 'moniruzzamanasad532@gmail.com', '2020-06-01', 1, '2026-05-24 18:00:00'),
(3211, '600044', 'Abu Sayed Reza Robin', 'Administration Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01929993251', 'robinreza7707@gmail.com', '2021-01-06', 1, '2026-05-24 18:00:00'),
(3212, '600046', 'Md. Salman Sayeed', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01670449144', 'salmansayeed@ymail.com', '2021-07-18', 1, '2026-05-24 18:00:00'),
(3213, '600048', 'Shila Sultana', 'Customer Service Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01305632751', 'saila9200@gmail.com', '2022-01-06', 1, '2026-05-24 18:00:00'),
(3214, '600049', 'Mosafizur Rahman', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01750758437', 'contact.masud99@gmail.com', '2023-12-10', 1, '2026-05-24 18:00:00'),
(3215, '600050', 'Raihan Molla', 'Front Desk Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01779833468', 'mollaraihan3468@gmail.com', '2022-07-31', 1, '2026-05-24 18:00:00'),
(3216, '600051', 'Karima Akter', 'Phlebotomist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01304443599', 'karimasheikh273@gmail.com', '2023-01-01', 1, '2026-05-24 18:00:00'),
(3217, '600054', 'Muktadir Hossain', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01609852490', '', '2023-09-02', 1, '2026-05-24 18:00:00'),
(3218, '600055', 'Rifa Rafiya', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01637140538', 'mtlraka20@gmail.com', '2023-10-09', 1, '2026-05-24 18:00:00'),
(3219, '600058', 'Hasi Rani Saha', 'Scientist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01989997042', 'hasirani85@gmail.com', '2019-07-01', 1, '2026-05-24 18:00:00'),
(3220, '600064', 'Mst. Salma Khan', 'Pharmacist (A Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989997260', 'mstsalmakhan85@gmail.com', '2024-07-06', 1, '2026-05-24 18:00:00'),
(3221, '600065', 'Md. Sultan Mahmud', 'Pharmacist (B Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989997202', 'smsultan049@gmail.com', '2024-07-22', 1, '2026-05-24 18:00:00'),
(3222, '600066', 'Shakib Lohani', 'Director, Business Development', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989996890', 'shakib.lohani@unigroup-bd.com', '2019-04-01', 1, '2026-05-24 18:00:00'),
(3223, '600068', 'Md. Mostafizar Rahman', 'Pharmacist (B Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989997262', '', '2024-09-30', 1, '2026-05-24 18:00:00'),
(3224, '600074', 'Sadia Jahan', 'Pharmacist (A Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989997259', 'sadiajahan7179@gmail.com', '2025-02-13', 1, '2026-05-24 18:00:00'),
(3225, '600076', 'Md. Shafiqul Islam', 'Medical Promotion Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01993319013', 'Shafiqulislambsl7@gmail.com', '2025-03-03', 1, '2026-05-24 18:00:00'),
(3226, '600081', 'Md. Ehsanul Haque Arafat', 'Area Sales Executive', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01989997256', 'ehsanchowshury1996@gmail.com', '2025-04-05', 1, '2026-05-24 18:00:00'),
(3227, '600082', 'Md. Al-Amin Khan', 'Pharmacist (B Grade)', 'Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01989997263', 'kanalamin1270@gmail.com', '2025-07-09', 1, '2026-05-24 18:00:00'),
(3228, '600083', 'Md. Sayeam Khandaker', 'Scientific Officer', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01775437611', '', '2025-04-05', 1, '2026-05-24 18:00:00'),
(3229, '600088', 'Joyanto Joseph Rozario', 'Phlebotomist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01608848705', '', '2026-01-15', 1, '2026-05-24 18:00:00'),
(3230, '600089', 'Md. Nahid Hassan', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01903813506', '', '2026-01-15', 1, '2026-05-24 18:00:00'),
(3231, '600090', 'Mily Dey', 'Consultant', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01779485448', '', '2025-12-10', 1, '2026-05-24 18:00:00'),
(3232, '600094', 'Muhammad Rezaul Karim', 'Business Development Manager', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01993319012', 'rezaulipl@gmail.com', '2026-03-07', 1, '2026-05-24 18:00:00'),
(3233, '600096', 'Md. Sajibul Hoque', 'Medical Technologist', 'Dhaka', 'BioMed Diagnostic Limited', 'BioMed Diagnostic', '01717547567', 'sajiblab20@gmail.com', '2026-03-09', 1, '2026-05-24 18:00:00'),
(3234, '600097', 'Md. Nafiz Uddin', 'Pharmacist (B Grade)', 'Panthapath, Dhaka', 'BioMed Pharmacy Limited', 'BioMed Pharmacy', '01760526352', 'shuvok087@gmail.com', '2026-05-03', 1, '2026-05-24 18:00:00'),
(3235, '700001', 'Shamsuddoha Rafee', 'Manager, Production', 'Factory,Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Production', '01993319100', 'shamsuddoharafee@gmail.com', '2024-05-02', 1, '2026-05-24 18:00:00'),
(3236, '700002', 'Sujit Kumar Biswas', 'Deputy Manager, Marketing', 'Corporate Office', 'UniAgrovet Limited', 'Marketing', '01847022932', 'sujitghl07@gmail.com', '2024-08-01', 1, '2026-05-24 18:00:00'),
(3237, '700003', 'Sazzadul Bari', 'Director, Quality Assurance', 'Factory,Gazipur', 'UniAgrovet Limited', 'Quality Assurance', '01989997290', 'sazzadulbari@gmail.com', '2024-08-01', 1, '2026-05-24 18:00:00'),
(3238, '720001', 'ATM Kamrul Ahsan ', 'Adviser', 'Corporate Office', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Commercial', '01335119401', 'kamrulatm1970@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3239, '720002', 'Ajoy Kumar Biswas ', 'Deputy Sales Manager', 'Dhaka-A', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119402', 'akbiswas@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3240, '720003', 'Kanta Dutta ', 'Product Officer', 'Marketing Office', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Marketing', '01335119403', 'kantadutta2021@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3241, '720004', 'Suman Kumar Hazra ', 'Area Sales Manager', 'Jessore', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119404', 'skhazra151076@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3242, '720006', 'Md. Nezam Uddin ', 'Senior Medical Promotion Officer', 'Chittagong-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119406', 'nejam.neoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3243, '720007', 'Sajal Das', 'Senior Medical Promotion Officer', 'Chittagong-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119407', 'dassajal1010@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3244, '720009', 'Samar Kumar Roy ', 'Senior Medical Promotion Officer', 'Barisal-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119409', 'samarroy616@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3245, '720010', 'Shambo Nath Saha ', 'Senior Medical Promotion Officer', 'Kishoreganj\r\n', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119410', 'shambonathsaha@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3246, '720011', 'Md. Ashiqul Islam ', 'Senior Medical Promotion Officer', 'B.Baria', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119411', 'ashiqulneoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3247, '720012', 'Md. Ohidul Islam ', 'Senior Medical Promotion Officer', 'Patuakhali', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119412', 'ohidul05702@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3248, '720013', 'Sajib Kumar Das', 'Senior Medical Promotion Officer', 'Chittagong-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119413', 'sajibkumardas212@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3249, '720018', 'Md. Shariful Islam ', 'Senior Medical Promotion Officer', 'Bogra-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119418', 'sharifneoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3250, '720021', 'Ranjit Kumar Bepary ', 'Senior Medical Promotion Officer', 'Barisal-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119421', 'ranjitkumarbepary@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3251, '720022', 'Md. Masud Rana ', 'Senior Medical Promotion Officer', 'Rajshahi-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119422', 'unimedunihealth309021@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3252, '720023', 'Md. Abul Hossain ', 'Senior Medical Promotion Officer', 'Kushtia', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119423', 'mdabulhussain1986@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3253, '720025', 'Samiron Kumar Biswas ', 'Senior Medical Promotion Officer', 'Jassore-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119425', '71somironneoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3254, '720026', 'Md. Abdur Rouf ', 'Senior Medical Promotion Officer', 'Dhanmondi-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119426', 'rouf.sub023@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3255, '720029', 'Showmen Mittra ', 'Medical Promotion Officer', 'Jassore-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119429', 'showmen.neoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3256, '720030', 'Md. Adam Safiullah ', 'Medical Promotion Officer', 'Jessore-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119430', 'adam25safiullahjess@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3257, '720031', 'Balai Chandra Biswas ', 'Medical Promotion Officer', 'Savar', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119431', 'neolivabalai@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3258, '720032', 'Md. Anamul Haque ', 'Medical Promotion Officer', 'Mirpur-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119432', 'anamuldarsana@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3259, '720033', 'Mohammad Shariful Islam ', 'Senior Medical Promotion Officer', 'Comilla-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119433', 'shariful.neoliva@gmail.com', '2025-07-01', 1, '2026-05-24 18:00:00'),
(3260, '720034', 'Tofayal Hossain', 'Medical Promotion Officer', 'Sylhet-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113889', 'thaukantofayal4@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3261, '720035', 'Mobinur Rahman', 'Medical Promotion Officer', 'Faridpur-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119419', 'mobinurahman29@gmail.com', '2025-11-04', 1, '2026-05-24 18:00:00'),
(3262, '720036', 'Md. Mamun Hossain', 'Medical Promotion Officer', 'Mymensingh-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113893', 'md88mamun88@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3263, '720037', 'Md. Rokibuzzaman', 'Medical Promotion Officer', 'Maijdee', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119444', 'mdrokibuzzaman5@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3264, '720038', 'Md. Rabiul Islam', 'Medical Promotion Officer', 'Dhanmondi-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119434', 'mdrabiul8522@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3265, '720039', 'Md. Mahmudul Hasan', 'Medical Promotion Officer', 'Gulshan-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119415', 'mahmudul241998@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3266, '720041', 'Md. Humaiun Kabir', 'Medical Promotion Officer', 'Bogra-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119420', 'humaiun425@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3267, '720042', 'Md. Zakir Hossain', 'Medical Promotion Officer', 'Rangpur-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119445', 'mmdzakirhossainraju@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3268, '720043', 'Md. Burhan Uddin', 'Medical Promotion Officer', 'Sylhet-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113888', 'burhanuddinshohag2@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3269, '720044', 'Topu Sarker', 'Medical Promotion Officer', 'Uttara-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119448', 'topusarker44@gmail.com', '2025-11-03', 1, '2026-05-24 18:00:00'),
(3270, '720045', 'Md. Alamin', 'Medical Promotion Officer', 'Mymensingh-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113894', 'mdalaminhossain11223311@gmail.com', '2025-11-05', 1, '2026-05-24 18:00:00'),
(3271, '720046', 'Md. Zahed Ali', 'Medical Promotion Officer', 'Rajshahi-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119424', 'jahidraj55557@gmailcom', '2025-11-16', 1, '2026-05-24 18:00:00'),
(3272, '720047', 'Md. Shakil Hossain', 'Medical Promotion Officer', 'Gazipur', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119416', 'shakilhasan11000033@gmail.com', '2025-11-16', 1, '2026-05-24 18:00:00'),
(3273, '720049', 'Md. Jamil Uddin', 'Medical Promotion Officer', 'Dhanmondi-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119435', 'shorkerjamil@gmail.com', '2025-11-16', 1, '2026-05-24 18:00:00'),
(3274, '720052', 'Azizul Hoque', 'Medical Promotion Officer', 'Narsindhi', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119428', 'azizulhoque483362@gmail.com', '2025-11-16', 1, '2026-05-24 18:00:00'),
(3275, '720053', 'Md. Masud Rana', 'Medical Promotion Officer', 'Thakurgaon', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113899', 'mr0755606@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(3276, '720054', 'Narottam Talukder', 'Medical Promotion Officer', 'Tangail', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '1335113896', 'narottomtalukder12@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(3277, '720055', 'Md. Naiemul Islam', 'Medical Promotion Officer', 'Comilla-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119443', 'islammdnaiem284@gmail.com', '2025-12-04', 1, '2026-05-24 18:00:00'),
(3278, '720056', 'Md. Rabiul Alam', 'Medical Promotion Officer', 'Jatrabari', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '1335119414', 'durabiulalam213@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(3279, '720057', 'Md. Repon', 'Medical Promotion Officer', 'Rangpur-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '1335119441', 'repon995jkw@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(3280, '720058', 'Md. Swet Rana', 'Medical Promotion Officer', 'Pabna', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '1335119427', 'sweetrana.sk.bd@gmail.com', '2025-12-01', 1, '2026-05-24 18:00:00'),
(3281, '720060', 'Abu Hasan', 'Medical Promotion Officer', 'Coxsbazar', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119442', 'abuhasan017566@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3282, '720061', 'Md. Tuhin Ali', 'Medical Promotion Officer', 'Rangpur-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119437', 'afrozakhusi1915@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3283, '720062', 'Md. Juel Rana', 'Medical Promotion Officer', 'Gulshan-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119447', 'mdjuwelrana01761@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3284, '720063', 'Md. Rasel Ahmmed', 'Medical Promotion Officer', 'Faridpur-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119438', 'raselgb.96@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3285, '720064', 'Md. Deloyar Hosen', 'Medical Promotion Officer', 'Mymensingh-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113895', 'mddeloyar6438@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3286, '720065', 'Sree Parimol Kumar', 'Medical Promotion Officer', 'Dinajpur-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113898', 'jajabor1125@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3287, '720066', 'Md. Sirazul Islam', 'Medical Promotion Officer', 'Barisal-3', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119439', 'si01701006186@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3288, '720067', 'Md. Abul Kalam Azad', 'Senior Area Sales Manager', 'Barisal', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119405', 'akazasgoog@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3289, '720068', 'Jowel Rana', 'Area Sales Manager', 'Sylhet', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113890', 'jewelranapgh@gmail.com', '2026-01-01', 1, '2026-05-24 18:00:00'),
(3290, '720069', 'Jibok Kumar Roy', 'Medical Promotion Officer', 'Mirpur-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119446', 'jibokkumarroy0712996@Gmail.com', '2026-02-01', 1, '2026-05-24 18:00:00'),
(3291, '720072', 'Md. Monarul Islam', 'Medical Promotion Officer', 'Dinajpur-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113897', 'monarul3447@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3292, '720073', 'Md. Mabud Box', 'Medical Promotion Officer', '', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', 'Replace', 'mdmabudsanjida@gmail.com', '0000-00-00', 1, '2026-05-24 18:00:00'),
(3293, '720074', 'Md. Jafur Iqbal', 'Medical Promotion Officer', 'Sylhet-4\r\n', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113891', 'abdulmalake2022@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3294, '720075', 'Asadul Islam', 'Medical Promotion Officer', 'Rajshahi-1', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119422', 'asadulislam571995@gmail.com', '2026-05-12', 1, '2026-05-24 18:00:00'),
(3295, '720076', 'Md. Asadur Rahman', 'Medical Promotion Officer', 'Khulna-2', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119440', 'asadur.rahman0042@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3296, '720077', 'Md. Hazrat Ali', 'Medical Promotion Officer', 'Shyamoli', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '1335119436', 'mdhazartali58585@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3297, '720078', 'Md. Amin Hossen', 'Medical Promotion Officer', 'Khulna-1\r\n', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335119408', 'aminhassen2400@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3298, '720079', 'Arifur Rahman Rasel', 'Medical Promotion Officer', 'Habiganj', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113892', 'arrassel32@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3299, '720080', 'Mukhlesur Rahman', 'Medical Promotion Officer', 'Sylhet-3\r\n', 'UniMed UniHealth Pharmaceuticals Limited (Herbal Division)', 'Sales', '01335113887', 'rmokhlesur827@gmail.com', '2026-05-16', 1, '2026-05-24 18:00:00'),
(3300, '901222', 'Khondakar Md.   Jubaer ', 'Junior Distribution Officer', 'Bhairab Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01783979775', 'mdalot215@gmail.com', '2016-03-16', 1, '2026-05-24 18:00:00'),
(3301, '901226', 'Chayan Paul', 'Junior Distribution Officer', 'Thakurgaon Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01635302319', 'choyanpaul@850gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(3302, '901239', 'Md. Imran Hossain', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01680436056', 'bochilahighschool@gmail.com', '2016-01-02', 1, '2026-05-24 18:00:00'),
(3303, '901244', 'Zahidul Islam', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01925858994', 'zahid.sirajgonj@gmail.com', '2015-08-14', 1, '2026-05-24 18:00:00'),
(3304, '901246', 'Md. Abdus Salam', 'Junior Distribution Officer', 'Savar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01741713642', 'salammollah1280@gmail.com', '2016-11-13', 1, '2026-05-24 18:00:00'),
(3305, '901247', 'Rubel Haque', 'Junior Distribution Officer', 'Naogaon Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01774705736', 'haquerubel1996@gmail.com', '2016-10-01', 1, '2026-05-24 18:00:00'),
(3306, '901260', 'Naiyum Molla', 'Junior Distribution Officer', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01951259812', 'mdn50444@gmail.com', '2017-02-25', 1, '2026-05-24 18:00:00'),
(3307, '901262', 'Md. Sahadot Sheikh', 'Junior Distribution Officer', 'Jhenaidah Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01925751508', 'md.shadotshakh12500@gmail.com', '2017-01-01', 1, '2026-05-24 18:00:00'),
(3308, '901268', 'Md. Abdul Mazed', 'Junior Distribution Officer', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01838339384', 'mmazed84@gmail.com', '2014-08-11', 1, '2026-05-24 18:00:00'),
(3309, '901270', 'Md. Manik Hossain', 'Distribution Officer', 'Beanibazar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01935661481', 'manikhossen19961@gmail.com', '2016-07-20', 1, '2026-05-24 18:00:00'),
(3310, '901278', 'Md. Mizanur Rahman', 'Junior Distribution Officer', 'Bhola Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01791124807', 'mizanurrahmangoods@gmail.com', '2016-06-01', 1, '2026-05-24 18:00:00'),
(3311, '901280', 'Shipon Chandra', 'Junior Distribution Officer', 'Barishal Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01746305088', 'shipon30508@gmail.com', '2016-04-01', 1, '2026-05-24 18:00:00'),
(3312, '901283', 'Md. Shahadat Hossain', 'Junior Distribution Officer', 'Chattogram Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01850301880', 'shahadatovi4@gmail.com', '2017-04-08', 1, '2026-05-24 18:00:00'),
(3313, '901285', 'Rabindra Nath Roy', 'Junior Distribution Officer', 'Dinajpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01735479276', 'rabindranathroybd@gmail.com', '2017-04-15', 1, '2026-05-24 18:00:00'),
(3314, '901288', 'Imam Hossain', 'Junior Distribution Officer', 'Kishoreganj Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01725646125', 'imam.hosien@gmail.com', '2017-05-20', 1, '2026-05-24 18:00:00'),
(3315, '901290', 'Md. Abul Hashem', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01771397385', 'zoynul.distribution@gmail.com', '2017-07-01', 1, '2026-05-24 18:00:00'),
(3316, '901291', 'Md. A. Motaleb Hossain', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01603501195', 'mottalibh32@gmail.com', '2015-07-16', 1, '2026-05-24 18:00:00'),
(3317, '901302', 'Md. Monir Uddin', 'Distribution Officer', 'Chandpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01939605865', '', '2017-03-11', 1, '2026-05-24 18:00:00'),
(3318, '901308', 'Md. Nazmul Hasan', 'Senior Distribution Officer', 'Gouripur Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995155', 'nazmulhasanrajib45@gmail.com', '2017-07-01', 1, '2026-05-24 18:00:00'),
(3319, '901322', 'Gopal Sarder', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01905613587', 'gopal01742@gmail.com', '2017-07-31', 1, '2026-05-24 18:00:00'),
(3320, '901324', 'Md. Abdul Ahad', 'Junior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01734240982', 'mdabdulahad292@gmail.com', '2017-06-20', 1, '2026-05-24 18:00:00'),
(3321, '901325', 'Md. Ayub Ali', 'Junior Distribution Officer', 'Khulna Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01722622121', 'md.ayubunigroup@gmail.com', '2017-03-09', 1, '2026-05-24 18:00:00'),
(3322, '901327', 'Md. Rimon Ali', 'Junior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01795037341', 'sr.rimon5841@gmail.com', '2017-04-08', 1, '2026-05-24 18:00:00'),
(3323, '901328', 'Md. Mehedi Hasan', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01964801246', 'zoynul.distribution@gmail.com', '2017-05-17', 1, '2026-05-24 18:00:00'),
(3324, '901331', 'Prodip Kumar Karmokar', 'Junior Distribution Officer', 'Moulvibazar Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01763694520', 'royaritro21@gmail.com', '2017-04-25', 1, '2026-05-24 18:00:00'),
(3325, '901345', 'Md. Sohel Rana', 'Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01710566690', 'armanmrsohel@gmail.com', '2017-08-05', 1, '2026-05-24 18:00:00'),
(3326, '901347', 'Mahmudullah', 'Junior Distribution Officer', 'Munshiganj sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01726348335', '', '2017-12-01', 1, '2026-05-24 18:00:00'),
(3327, '901352', 'Md. Karimul Islam', 'Junior Distribution Officer', 'Gouripur Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01764860097', 'karimul.280.net@gmail.com', '2017-07-01', 1, '2026-05-24 18:00:00'),
(3328, '901353', 'Md. Josim Uddin', 'Senior Distribution Officer', 'Bhairab Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989994530', 'josimuddinunigroup@gmail.com', '2016-08-15', 1, '2026-05-24 18:00:00'),
(3329, '901354', 'Md. Fazle Rabbi', 'Junior Distribution Officer', 'Cumilla Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993259', 'mdfazlerabbiu@gmail.com', '2018-01-01', 1, '2026-05-24 18:00:00'),
(3330, '901367', 'Rasel Nath', 'Junior Distribution Officer', 'Chakaria Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989995137', 'raselnath8877@gmail.com', '2018-03-01', 1, '2026-05-24 18:00:00'),
(3331, '901384', 'Md. Abu Saied', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01628298841', 'zoynul.distribution@gmail.com', '2018-05-01', 1, '2026-05-24 18:00:00'),
(3332, '901387', 'Kazi Kawsar Alam', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01720021841', 'kawsharkazi@gmail.com', '2017-09-20', 1, '2026-05-24 18:00:00'),
(3333, '901388', 'Md. Monjurul Islam', 'Distribution Officer', 'Nilphamari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01755471442', 'monjurulislam181095@gmail.com', '2017-07-15', 1, '2026-05-24 18:00:00'),
(3334, '901408', 'Abul Hasan', 'Distribution Officer', 'Bhairab Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01989997128', 'rangamati.sc@unigroup-bd.com', '2017-12-25', 1, '2026-05-24 18:00:00'),
(3335, '901422', 'Shafiqul Islam', 'Junior Distribution Officer', 'Patiya Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01846256568', 'shafiqulislam4625@gmail.com', '2017-09-05', 1, '2026-05-24 18:00:00'),
(3336, '901431', 'Abu Taher', 'Junior Distribution Officer', 'Sylhet Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01759970599', 'aburaher@gmail.com', '2017-03-21', 1, '2026-05-24 18:00:00'),
(3337, '901435', 'Mitul Chandra Deb', 'Junior Distribution Officer', 'Habiganj Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01742872626', 'mituldeb86@gmail.com', '2018-03-01', 1, '2026-05-24 18:00:00'),
(3338, '901447', 'Asiful Hoque', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01864833938', 'asifulhoque682@gmail.com', '2017-12-13', 1, '2026-05-24 18:00:00'),
(3339, '901454', 'Md. Rakibul Hasan', 'Junior Distribution Officer', 'Jamalpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01712434825', '', '2018-04-15', 1, '2026-05-24 18:00:00'),
(3340, '901470', 'Khairul Islam', 'Junior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01997245709', 'khairulislam22000@gmail.com', '2018-06-11', 1, '2026-05-24 18:00:00'),
(3341, '901488', 'Tiklo Rudra', 'Junior Distribution Officer', 'Rangamati Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01874834809', 'tiklorudra2486@gmail.com', '2018-12-05', 1, '2026-05-24 18:00:00'),
(3342, '901506', 'Mahbub', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01616782299', '', '2019-03-05', 1, '2026-05-24 18:00:00'),
(3343, '901538', 'Uttam Chakraborty', 'Junior Distribution Officer', 'Hathazari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01869555797', 'chakrabortyuttam642@gmail.com', '2018-12-26', 1, '2026-05-24 18:00:00'),
(3344, '901540', 'Touhidul Islam', 'Junior Distribution Officer', 'Chakaria Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01825185181', 'touhid.bd5181@gmail.com', '2018-12-28', 1, '2026-05-24 18:00:00'),
(3345, '901554', 'Md. Sujon Ali', 'Junior Distribution Officer', 'Barishal Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01981205651', '', '2019-09-01', 1, '2026-05-24 18:00:00'),
(3346, '901562', 'Emran Hossain', 'Junior Distribution Officer', 'Rajbari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01745839393', 'sheikhemran283@gmail.com', '2018-10-20', 1, '2026-05-24 18:00:00'),
(3347, '901563', 'Habibulla kaiser', 'Junior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01730627191', 'mdhabibu960@gmail.com', '2018-10-20', 1, '2026-05-24 18:00:00'),
(3348, '901577', 'Sharif Ahmed', 'Junior Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01642580002', 'sharifmahmud0002@gmail.com', '2018-05-03', 1, '2026-05-24 18:00:00'),
(3349, '901593', 'Md. Rakibul Islam', 'Junior Distribution Officer', 'Kushtia Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01740255082', 'rakibislam10011988@gmail.com', '2018-10-05', 1, '2026-05-24 18:00:00'),
(3350, '901638', 'Md. Bulbul Ahamed', 'Junior Distribution Officer', 'Rangpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01760921950', '', '2018-12-01', 1, '2026-05-24 18:00:00'),
(3351, '901641', 'Sagor Mia', 'Junior Distribution Officer', 'Nilphamari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01751498187', 'sagorsnmia@gmail.com', '2019-07-01', 1, '2026-05-24 18:00:00'),
(3352, '901647', 'Mirza Saiful Islam', 'Junior Distribution Officer', 'Satkhira Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01968683841', 'smirzasaiful@gmail.com', '2019-03-01', 1, '2026-05-24 18:00:00'),
(3353, '901657', 'Md. Mostafa Kamal', 'Junior Distribution Officer', 'Dinajpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01751768780', 'mdmostofa56788765@gmail.com', '2018-11-21', 1, '2026-05-24 18:00:00'),
(3354, '901659', 'Md. Mostafa Mia', 'Junior Distribution Officer', 'Tangail Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01632388552', 'mdmostafamia1315@gmail.om', '2018-08-11', 1, '2026-05-24 18:00:00'),
(3355, '901666', 'Md. Razzak Miah', 'Junior Distribution Officer', 'Sylhet Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01715897711', 'aburrazzakrazz74@gmail.com', '2018-10-18', 1, '2026-05-24 18:00:00'),
(3356, '901681', 'Md. Rafikul Islam', 'Junior Distribution Officer', 'Lakshmipur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01316671689', 'roofiq@gmail.com', '2018-10-13', 1, '2026-05-24 18:00:00'),
(3357, '901692', 'Md. Khirul Islam', 'Junior Distribution Officer', 'Netrokona Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01864708859', 'mdkhiruli735@gmail.com', '2019-08-08', 1, '2026-05-24 18:00:00'),
(3358, '901698', 'Amin Hossen', 'Junior Distribution Officer', 'Barguna Sales Center\r\n', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01754270483', 'smamirhossain952@gmail.com', '2018-12-01', 1, '2026-05-24 18:00:00'),
(3359, '901717', 'Fazlul Karim', 'Junior Distribution Officer', 'Narsingdi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01949819480', 'fk7244165@gmail.com', '2019-11-03', 1, '2026-05-24 18:00:00'),
(3360, '901718', 'Md. Shamim Ahmmed', 'Junior Distribution Officer', 'Jamalpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01883915960', 'shamimvelabary1997@gmail.com', '2021-01-01', 1, '2026-05-24 18:00:00'),
(3361, '901721', 'Amanur Rashid', 'Junior Distribution Officer', 'Faridpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01903024362', 'amanurrashide@gmail.com', '2019-12-26', 1, '2026-05-24 18:00:00'),
(3362, '901723', 'Samim Molla', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01739277305', '', '2019-02-10', 1, '2026-05-24 18:00:00'),
(3363, '901724', 'Md. Hira Sardar', 'Junior Distribution Officer', 'Mohammadpur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01773967766', 'smhera39@gmail.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(3364, '901777', 'Muhammad Ayatullah', 'Junior Distribution Officer', 'Patiya Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01838119827', 'ayatullah@gmail.com', '2019-09-18', 1, '2026-05-24 18:00:00'),
(3365, '901800', 'Bulbul Ahammed', 'Junior Distribution Officer', 'Bhairab Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01928018481', 'bulbulahamed481@gmail.com', '2019-11-02', 1, '2026-05-24 18:00:00'),
(3366, '901808', 'Md. Israfil', 'Junior Distribution Officer', 'Central Depot, Import', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01863829155', 'israfilmd65@gmail.com', '2018-05-03', 1, '2026-05-24 18:00:00'),
(3367, '901814', 'Md. Mozibur Rahman Sarker', 'Junior Distribution Officer', 'Lakshmipur Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01822350670', 'sarkerismail332@gmail.com', '2020-10-10', 1, '2026-05-24 18:00:00'),
(3368, '901827', 'Md. Jamirul Islam', 'Junior Distribution Officer', 'Central Depot, Sample', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01727955255', 'md.jamirulislam471@gmail.com', '2019-10-13', 1, '2026-05-24 18:00:00'),
(3369, '901861', 'Md. Arif Hossain', 'Junior Distribution Officer', 'Kurigram Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01722056523', 'arifhossain@gmail.com', '2021-01-29', 1, '2026-05-24 18:00:00'),
(3370, '901914', 'Somir Uddin', 'Junior Distribution Officer', 'Central Depot, Gazipur', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01955460353', 'somirislam97@gmail.com', '2021-06-08', 1, '2026-05-24 18:00:00'),
(3371, '901931', 'Md. Ibrahim Hossain', 'Junior Distribution Officer', 'Kishoreganj Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01903362769', 'ebrahimhossan0710@gmail.com', '2015-05-25', 1, '2026-05-24 18:00:00'),
(3372, '901947', 'Md. Sojib Howlader', 'Junior Distribution Officer', 'Kishoreganj Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01728198758', 'rajsajib479@gmail.com', '2021-08-29', 1, '2026-05-24 18:00:00'),
(3373, '901959', 'Hamim Haolader', 'Junior Distribution Officer', 'Narsingdi Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01719929453', 'hamim.howlader4g@gmail.com', '2021-06-01', 1, '2026-05-24 18:00:00'),
(3374, '901968', ' Anjan Nath Tuhin', 'Junior Distribution Officer', 'Maijdee Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01841652582', 'anjannathtuhin@gmail.com', '2021-09-27', 1, '2026-05-24 18:00:00'),
(3375, '902005', 'Md. Rabbi Mollah', 'Junior Distribution Officer', 'Jatrabari Sales Center', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01600120463', 'rabbymolla8920@gmail.com', '2021-10-10', 1, '2026-05-24 18:00:00'),
(3376, '320095', 'Pranta Das', '', 'Marketing Office', '', '', '0198999496', 'yourmail@unigroup-bd.com', '2026-07-15', 1, '2026-08-11 04:39:42'),
(3377, '1001', 'IT, FACTORY', '', 'B.K BARI, RAJENDRAPUR, GAZIPUR', 'UniMed UniHealth Pharmaceuticals Limited', '', '', 'it.info@unigroup-bd.com', NULL, 1, '2026-08-19 04:57:11'),
(3378, '2001', 'Mohammadpur Depot', '', '', 'UniMed UniHealth Pharmaceuticals Limited', 'Distribution', '01929993462', 'mohammadpur.sc@unigroup-bd.com', NULL, 1, '2026-08-30 04:12:30');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `specification` text DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `revision_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `warranty_period` int(11) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `regular_price` decimal(12,2) DEFAULT NULL,
  `current_qty` int(11) DEFAULT 0,
  `min_qty` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warranty_status` enum('in_warranty','out_of_warranty','expiring_soon') DEFAULT 'in_warranty',
  `warranty_end_date` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_period_months` int(11) DEFAULT 12,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `total_assigned` int(11) DEFAULT 0,
  `total_returned` int(11) DEFAULT 0,
  `available_qty` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_code`, `name`, `specification`, `type_id`, `category_id`, `sub_category_id`, `serial_number`, `model_number`, `version`, `revision_count`, `created_by`, `brand`, `brand_id`, `warranty_period`, `price`, `regular_price`, `current_qty`, `min_qty`, `is_active`, `created_at`, `warranty_status`, `warranty_end_date`, `purchase_date`, `warranty_period_months`, `assigned_to`, `assigned_date`, `total_assigned`, `total_returned`, `available_qty`) VALUES
(124, 'ITM-000001', 'Headphone', '40 mm speaker unit, Microphone, 4pin extension Adaptor, Port 2*3.5 mm stereo jack', 11, 57, NULL, NULL, NULL, NULL, 0, 1, NULL, 22, 0, 1200.00, 1200.00, 0, 1, 1, '2026-08-23 10:52:43', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 1),
(125, 'ITM-000002', 'Webcam', '', 12, 58, NULL, NULL, NULL, NULL, 0, 1, NULL, 25, 0, 2400.00, 2400.00, 0, 1, 1, '2026-08-23 10:57:16', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 1),
(126, 'ITM-000003', 'Mouse', '', 6, 58, NULL, NULL, NULL, NULL, 0, 1, NULL, 22, 0, 450.00, 450.00, 0, 1, 1, '2026-08-23 11:37:00', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 1),
(127, 'ITM-000004', 'HDD', '4 TB nvr HDD', 30, 55, NULL, NULL, NULL, NULL, 0, 6, NULL, 26, NULL, 13500.00, 13500.00, 0, -4, 1, '2026-08-24 03:59:12', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 1),
(128, 'ITM-000005', 'CPU', '', 2, 58, NULL, NULL, NULL, NULL, 0, 6, NULL, 27, 12, 60425.00, 60425.00, 1, 1, 1, '2026-08-30 03:58:22', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 0),
(129, 'ITM-000006', 'Monitor', '', 4, 58, NULL, NULL, NULL, NULL, 0, 6, NULL, 1, 12, 12700.00, 12700.00, 0, 1, 1, '2026-08-30 07:01:35', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 0),
(130, 'ITM-000007', 'Printer', 'Epson Ecotank L11050 A3 Color Printer', 3, 60, NULL, NULL, NULL, NULL, 0, 6, NULL, 28, 12, 60000.00, 60000.00, 0, 1, 1, '2026-09-17 03:51:24', 'in_warranty', NULL, NULL, 12, NULL, NULL, 0, 0, 0),
(131, 'ITM-000008', 'Laptop', 'i3,G4,8GB,1TB', 1, 58, NULL, '5CG553289N', '14-ac130TU', NULL, 0, 4, NULL, 2, 12, 25000.00, 25000.00, 1, 1, 1, '2026-09-21 03:29:56', 'in_warranty', '2027-09-21', '2026-09-21', 12, NULL, NULL, 0, 1, 1),
(132, 'ITM-000009', 'Keyboard', 'wire keyboard', 5, NULL, NULL, '23SHI00', 'KRS-82', NULL, 0, 4, NULL, NULL, 6, 600.00, 600.00, 1, 1, 1, '2026-09-21 03:43:45', 'in_warranty', '2027-03-21', '2026-09-21', 12, NULL, NULL, 1, 0, 0),
(133, 'ITM-000010', 'Monitor', '19 INCH', 4, NULL, NULL, '109NTNH2U063', '22MK430H', NULL, 0, 4, NULL, NULL, 12, 10500.00, 10500.00, 1, 1, 1, '2026-09-21 03:44:05', 'in_warranty', '2024-01-01', '2023-01-01', 12, NULL, NULL, 1, 0, 0),
(134, 'ITM-000011', 'IPT', 'IPT', 33, NULL, NULL, '2121118070D3892', 'T21PE2', NULL, 0, 4, NULL, NULL, 6, 4500.00, 4500.00, 1, 1, 1, '2026-09-21 03:44:35', 'in_warranty', '2027-03-21', '2026-09-21', 12, NULL, NULL, 1, 0, 0),
(135, 'ITM-000012', 'Clone PC', 'i3, G7, 16GB, 512GB, 1TB', 33, NULL, NULL, '', '', NULL, 0, 4, NULL, NULL, 6, 25000.00, 25000.00, 1, 1, 1, '2026-09-21 03:45:03', 'in_warranty', '2027-03-21', '2026-09-21', 12, NULL, NULL, 1, 0, 0),
(136, 'ITM-000013', 'Mouse', 'Wire mouse', 6, NULL, NULL, 'BG2010117435', 'OP-730D', NULL, 0, 4, NULL, NULL, 6, 350.00, 350.00, 1, 1, 1, '2026-09-21 03:45:21', 'in_warranty', '2027-03-21', '2026-09-21', 12, NULL, NULL, 1, 0, 0);

--
-- Triggers `items`
--
DELIMITER $$
CREATE TRIGGER `after_item_insert_update_type_count` AFTER INSERT ON `items` FOR EACH ROW BEGIN
    -- This trigger can be used to update a count field if needed
    -- Currently just a placeholder for future use
    IF NEW.type_id IS NOT NULL THEN
        -- You can add logging or update a counter here
        INSERT INTO `system_logs` (`action`, `table_name`, `record_id`, `message`, `created_at`)
        VALUES ('ITEM_ADDED', 'items', NEW.id, CONCAT('Item type: ', NEW.type_id), NOW())
        ON DUPLICATE KEY UPDATE message = message;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `items_backup`
--

CREATE TABLE `items_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `item_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `specification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `revision_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `warranty_period` int(11) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `regular_price` decimal(12,2) DEFAULT NULL,
  `current_qty` int(11) DEFAULT 0,
  `min_qty` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warranty_status` enum('in_warranty','out_of_warranty','expiring_soon') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'in_warranty',
  `warranty_end_date` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_period_months` int(11) DEFAULT 12,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `total_assigned` int(11) DEFAULT 0,
  `total_returned` int(11) DEFAULT 0,
  `available_qty` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_price_history`
--

CREATE TABLE `item_price_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `old_price` decimal(12,2) DEFAULT NULL,
  `new_price` decimal(12,2) DEFAULT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `revision_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_serial_numbers`
--

CREATE TABLE `item_serial_numbers` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `is_assigned` tinyint(1) DEFAULT 0,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assignment_id` int(11) DEFAULT NULL,
  `damage_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_serial_numbers`
--

INSERT INTO `item_serial_numbers` (`id`, `item_id`, `serial_number`, `model_number`, `version`, `is_assigned`, `assigned_to`, `assigned_date`, `created_at`, `assignment_id`, `damage_id`) VALUES
(254, 124, '4711421848039', 'HS-19', '', 1, 288, '2026-08-24', '2026-08-23 11:22:48', 65, NULL),
(255, 125, '2507APF4C2A9', 'C270 HD', '', 1, 288, '2026-08-24', '2026-08-23 11:29:19', 66, NULL),
(257, 126, '4711421699495', 'OP-720', '', 1, 767, '2026-08-24', '2026-08-23 11:37:52', 64, NULL),
(259, 127, 'P669D22M4Y6', 'Toshiba Surveilence S300', '', 1, 3377, '2026-08-24', '2026-08-24 04:18:01', 67, NULL),
(260, 128, '#8837R5', '', '', 0, NULL, NULL, '2026-08-30 03:58:22', NULL, NULL),
(261, 128, '#612FVC', '', '', 1, 3378, '2026-08-25', '2026-08-30 03:58:22', 68, NULL),
(262, 129, 'CN-0G97T6-BOZ00-63Q-30SE', 'Dell 22 Monitor', '', 1, 415, '2026-08-30', '2026-08-30 07:01:35', 69, NULL),
(263, 130, 'XB4P017969', 'ECOTANK L11050', '', 1, 3138, '2026-09-17', '2026-09-17 03:51:24', 70, NULL),
(264, 131, '5CG553289N', '14-ac130TU', '1.0', 1, 110, '2026-09-21', '2026-09-21 03:29:56', NULL, NULL),
(265, 132, '23SHI00', 'KRS-82', '1.0', 1, 490, '2026-09-21', '2026-09-21 03:43:45', NULL, NULL),
(266, 133, '109NTNH2U063', '22MK430H', '1.0', 1, 490, '2026-09-21', '2026-09-21 03:44:05', NULL, NULL),
(267, 134, '2121118070D3892', 'T21PE2', '1.0', 1, 490, '2026-09-21', '2026-09-21 03:44:35', NULL, NULL),
(268, 136, 'BG2010117435', 'OP-730D', '1.0', 1, 490, '2026-09-21', '2026-09-21 03:45:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item_types`
--

CREATE TABLE `item_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `default_category_id` int(11) DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_types`
--

INSERT INTO `item_types` (`id`, `name`, `category_id`, `sub_category_id`, `is_active`, `description`, `default_category_id`, `is_system`, `icon`, `sort_order`) VALUES
(1, 'Laptop', 1, 9, 1, 'Portable computer for mobile work', NULL, 1, 'fa-laptop', 1),
(2, 'Desktop', 1, 10, 1, 'Fixed workstation computer', NULL, 1, 'fa-desktop', 2),
(3, 'Printer', 4, 11, 1, 'Printing device for documents', NULL, 1, 'fa-print', 20),
(4, 'Monitor', 1, 38, 1, 'Display screen for computers', NULL, 1, 'fa-tv', 6),
(5, 'Keyboard', 4, 27, 1, 'Input device for typing', NULL, 1, 'fa-keyboard', 10),
(6, 'Mouse', 4, 27, 1, 'Pointing device for navigation', NULL, 1, 'fa-mouse', 11),
(7, 'Router', 33, 12, 1, 'Network routing device', NULL, 1, 'fa-wifi', 30),
(8, 'Switch', 33, 19, 1, 'Network switching device', NULL, 1, 'fa-exchange-alt', 31),
(9, 'UPS', 1, 39, 1, 'Uninterruptible Power Supply', NULL, 1, 'fa-battery-full', 7),
(10, 'Scanner', 4, 26, 1, 'Document scanning device', NULL, 1, 'fa-scanner', 12),
(11, 'Headset', 4, 27, 1, 'Audio headset for communication', NULL, 1, 'fa-headphones', 13),
(12, 'Webcam', 4, 27, 1, 'Video camera for meetings', NULL, 1, 'fa-camera', 14),
(13, 'Access Point', 33, 21, 1, 'Wireless access point', NULL, 1, 'fa-signal', 32),
(14, 'Firewall', 33, 22, 1, 'Network security device', NULL, 1, 'fa-shield-alt', 33),
(15, 'Server', 1, 37, 1, 'Server hardware for data center', NULL, 1, 'fa-server', 3),
(16, 'Tablet', 1, 9, 1, 'Tablet computer for mobility', NULL, 1, 'fa-tablet-alt', 4),
(17, 'Mobile Phone', 1, NULL, 1, 'Smartphone device for communication', NULL, 1, 'fa-mobile-alt', 5),
(18, 'Projector', 35, 47, 1, 'Display projection device', NULL, 1, 'fa-video', 40),
(19, 'CCTV Camera', 35, 45, 1, 'Surveillance camera', NULL, 1, 'fa-camera', 41),
(20, 'Biometric Device', 35, 44, 1, 'Attendance/Fingerprint device', NULL, 1, 'fa-fingerprint', 42),
(21, 'Multifunction Printer', 4, 11, 1, 'Printer with scan/copy/fax', NULL, 1, 'fa-print', 21),
(22, 'Label Printer', 4, 11, 1, 'Label and barcode printer', NULL, 1, 'fa-tag', 22),
(23, 'Media Converter', 33, 23, 1, 'Fiber/Copper media converter', NULL, 1, 'fa-exchange-alt', 34),
(24, 'NVR/DVR', 35, 46, 1, 'Network Video Recorder', NULL, 1, 'fa-hdd', 43),
(25, 'Operating System', 34, 40, 1, 'Windows, Linux, macOS', NULL, 1, 'fa-window-maximize', 50),
(26, 'Antivirus', 34, 41, 1, 'Endpoint security software', NULL, 1, 'fa-shield-virus', 51),
(27, 'Application Software', 34, 42, 1, 'Business application software', NULL, 1, 'fa-app-store', 52),
(28, 'Server License', 34, 43, 1, 'Server software licenses', NULL, 1, 'fa-server', 53),
(29, 'RAM', 36, 48, 1, 'Memory modules', NULL, 1, 'fa-microchip', 60),
(30, 'Storage (SSD/HDD)', 36, 49, 1, 'Storage drives', NULL, 1, 'fa-database', 61),
(31, 'Printer Toner/Cartridge', 36, 50, 1, 'Printer consumables', NULL, 1, 'fa-print', 62),
(32, 'Network Cable', 36, 51, 1, 'Patch cords, ethernet cables', NULL, 1, 'fa-plug', 63),
(33, 'Other Device', NULL, NULL, 1, 'Uncategorized device', NULL, 0, 'fa-question-circle', 999);

-- --------------------------------------------------------

--
-- Table structure for table `item_vendors`
--

CREATE TABLE `item_vendors` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `last_purchase_price` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_vendors`
--

INSERT INTO `item_vendors` (`id`, `item_id`, `vendor_id`, `purchase_price`, `is_primary`, `last_purchase_price`, `created_at`) VALUES
(131, 124, 14, 0.00, 1, NULL, '2026-08-23 11:39:09'),
(132, 125, 14, 2400.00, 1, NULL, '2026-08-23 11:39:28'),
(133, 126, 14, 0.00, 1, NULL, '2026-08-23 11:39:38'),
(134, 127, 14, 0.00, 1, NULL, '2026-08-24 03:59:12');

-- --------------------------------------------------------

--
-- Table structure for table `licenses`
--

CREATE TABLE `licenses` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `license_key` varchar(100) DEFAULT NULL,
  `software_name` varchar(200) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `license_type` enum('perpetual','subscription','trial') DEFAULT 'perpetual',
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `seats` int(11) DEFAULT 1,
  `cost` decimal(12,2) DEFAULT 0.00,
  `used_seats` int(11) DEFAULT 0,
  `status` enum('active','expired','expiring_soon') DEFAULT 'active',
  `vendor` varchar(200) DEFAULT NULL,
  `license_file` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `documentation_file` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `vendor_name` varchar(200) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `purchased_from` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `support_contact` varchar(100) DEFAULT NULL,
  `support_email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_assignments`
--

CREATE TABLE `license_assignments` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `device_name` varchar(200) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','revoked','expired') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license_history`
--

CREATE TABLE `license_history` (
  `id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_purchase_details`
--

CREATE TABLE `monthly_purchase_details` (
  `id` int(11) NOT NULL,
  `cash_register_id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `purchase_amount` decimal(12,2) NOT NULL,
  `payment_status` enum('paid','pending','partial') DEFAULT 'pending',
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_acknowledgements`
--

CREATE TABLE `payment_acknowledgements` (
  `id` int(11) NOT NULL,
  `acknowledgement_no` varchar(50) NOT NULL,
  `payment_slip_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `acknowledgement_date` datetime DEFAULT current_timestamp(),
  `pdf_path` varchar(255) DEFAULT NULL,
  `is_printed` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_slips`
--

CREATE TABLE `payment_slips` (
  `id` int(11) NOT NULL,
  `slip_no` varchar(50) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `payment_amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` enum('cash','cheque','bank_transfer','online') DEFAULT 'cash',
  `cheque_no` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `slip_document` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `permission` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `role_id`, `module`, `permission`) VALUES
(0, 'it_staff', 2, 'dashboard', 'view'),
(0, 'it_staff', 2, 'users', 'view'),
(0, 'it_staff', 2, 'items', 'view'),
(0, 'it_staff', 2, 'items', 'create'),
(0, 'it_staff', 2, 'items', 'edit'),
(0, 'it_staff', 2, 'items', 'delete'),
(0, 'it_staff', 2, 'brands', 'view'),
(0, 'it_staff', 2, 'brands', 'create'),
(0, 'it_staff', 2, 'brands', 'edit'),
(0, 'it_staff', 2, 'brands', 'delete'),
(0, 'it_staff', 2, 'vendors', 'view'),
(0, 'it_staff', 2, 'vendors', 'create'),
(0, 'it_staff', 2, 'vendors', 'edit'),
(0, 'it_staff', 2, 'vendors', 'delete'),
(0, 'it_staff', 2, 'employees', 'view'),
(0, 'it_staff', 2, 'employees', 'create'),
(0, 'it_staff', 2, 'employees', 'edit'),
(0, 'it_staff', 2, 'employees', 'delete'),
(0, 'it_staff', 2, 'categories', 'view'),
(0, 'it_staff', 2, 'categories', 'create'),
(0, 'it_staff', 2, 'categories', 'edit'),
(0, 'it_staff', 2, 'categories', 'delete'),
(0, 'it_staff', 2, 'stock', 'view'),
(0, 'it_staff', 2, 'stock', 'create'),
(0, 'it_staff', 2, 'stock', 'edit'),
(0, 'it_staff', 2, 'bills', 'view'),
(0, 'it_staff', 2, 'bills', 'create'),
(0, 'it_staff', 2, 'bills', 'edit'),
(0, 'it_staff', 2, 'assignments', 'view'),
(0, 'it_staff', 2, 'assignments', 'create'),
(0, 'it_staff', 2, 'assignments', 'edit'),
(0, 'it_staff', 2, 'assignments', 'delete'),
(0, 'it_staff', 2, 'assignments', 'print'),
(0, 'it_staff', 2, 'assignments', 'barcode'),
(0, 'it_staff', 2, 'requests', 'view'),
(0, 'it_staff', 2, 'requests', 'process'),
(0, 'it_staff', 2, 'returns', 'view'),
(0, 'it_staff', 2, 'returns', 'process'),
(0, 'it_staff', 2, 'returns', 'approve'),
(0, 'it_staff', 2, 'damages', 'view'),
(0, 'it_staff', 2, 'damages', 'create'),
(0, 'it_staff', 2, 'damages', 'edit'),
(0, 'it_staff', 2, 'damages', 'delete'),
(0, 'it_staff', 2, 'damages', 'report'),
(0, 'it_staff', 2, 'unlisted_devices', 'view'),
(0, 'it_staff', 2, 'unlisted_devices', 'process'),
(0, 'it_staff', 2, 'license_warranty', 'view'),
(0, 'it_staff', 2, 'license_warranty', 'create'),
(0, 'it_staff', 2, 'license_warranty', 'edit'),
(0, 'it_staff', 2, 'license_warranty', 'delete'),
(0, 'it_staff', 2, 'reports', 'view'),
(0, 'it_staff', 2, 'cash_register', 'view'),
(0, 'it_staff', 2, 'transfers', 'view'),
(0, 'it_staff', 2, 'transfers', 'create'),
(0, 'it_staff', 2, 'transfers', 'edit'),
(0, 'it_staff', 2, 'transfers', 'process'),
(0, 'it_staff', 2, 'transfers', 'print'),
(0, 'admin', 1, 'dashboard', 'view'),
(0, 'admin', 1, 'users', 'view'),
(0, 'admin', 1, 'users', 'create'),
(0, 'admin', 1, 'users', 'edit'),
(0, 'admin', 1, 'users', 'delete'),
(0, 'admin', 1, 'permissions', 'view'),
(0, 'admin', 1, 'roles', 'view'),
(0, 'admin', 1, 'roles', 'create'),
(0, 'admin', 1, 'roles', 'edit'),
(0, 'admin', 1, 'roles', 'delete'),
(0, 'admin', 1, 'items', 'view'),
(0, 'admin', 1, 'items', 'create'),
(0, 'admin', 1, 'items', 'edit'),
(0, 'admin', 1, 'items', 'delete'),
(0, 'admin', 1, 'brands', 'view'),
(0, 'admin', 1, 'brands', 'create'),
(0, 'admin', 1, 'brands', 'edit'),
(0, 'admin', 1, 'brands', 'delete'),
(0, 'admin', 1, 'vendors', 'view'),
(0, 'admin', 1, 'vendors', 'create'),
(0, 'admin', 1, 'vendors', 'edit'),
(0, 'admin', 1, 'vendors', 'delete'),
(0, 'admin', 1, 'employees', 'view'),
(0, 'admin', 1, 'employees', 'create'),
(0, 'admin', 1, 'employees', 'edit'),
(0, 'admin', 1, 'employees', 'delete'),
(0, 'admin', 1, 'categories', 'view'),
(0, 'admin', 1, 'categories', 'create'),
(0, 'admin', 1, 'categories', 'edit'),
(0, 'admin', 1, 'categories', 'delete'),
(0, 'admin', 1, 'stock', 'view'),
(0, 'admin', 1, 'stock', 'create'),
(0, 'admin', 1, 'stock', 'edit'),
(0, 'admin', 1, 'stock', 'delete'),
(0, 'admin', 1, 'bills', 'view'),
(0, 'admin', 1, 'bills', 'create'),
(0, 'admin', 1, 'bills', 'edit'),
(0, 'admin', 1, 'bills', 'delete'),
(0, 'admin', 1, 'assignments', 'view'),
(0, 'admin', 1, 'assignments', 'create'),
(0, 'admin', 1, 'assignments', 'edit'),
(0, 'admin', 1, 'assignments', 'delete'),
(0, 'admin', 1, 'assignments', 'print'),
(0, 'admin', 1, 'assignments', 'barcode'),
(0, 'admin', 1, 'requests', 'view'),
(0, 'admin', 1, 'requests', 'create'),
(0, 'admin', 1, 'requests', 'edit'),
(0, 'admin', 1, 'requests', 'process'),
(0, 'admin', 1, 'requests', 'delete'),
(0, 'admin', 1, 'returns', 'view'),
(0, 'admin', 1, 'returns', 'create'),
(0, 'admin', 1, 'returns', 'process'),
(0, 'admin', 1, 'returns', 'approve'),
(0, 'admin', 1, 'damages', 'view'),
(0, 'admin', 1, 'damages', 'create'),
(0, 'admin', 1, 'damages', 'edit'),
(0, 'admin', 1, 'damages', 'delete'),
(0, 'admin', 1, 'damages', 'report'),
(0, 'admin', 1, 'unlisted_devices', 'view'),
(0, 'admin', 1, 'unlisted_devices', 'create'),
(0, 'admin', 1, 'unlisted_devices', 'edit'),
(0, 'admin', 1, 'unlisted_devices', 'process'),
(0, 'admin', 1, 'unlisted_devices', 'delete'),
(0, 'admin', 1, 'license_warranty', 'view'),
(0, 'admin', 1, 'license_warranty', 'create'),
(0, 'admin', 1, 'license_warranty', 'edit'),
(0, 'admin', 1, 'license_warranty', 'delete'),
(0, 'admin', 1, 'reports', 'view'),
(0, 'admin', 1, 'reports', 'export'),
(0, 'admin', 1, 'cash_register', 'view'),
(0, 'admin', 1, 'cash_register', 'create'),
(0, 'admin', 1, 'cash_register', 'edit');

-- --------------------------------------------------------

--
-- Table structure for table `replacement_requests`
--

CREATE TABLE `replacement_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `faulty_device_id` int(11) NOT NULL,
  `replacement_device_id` int(11) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `replacement_needed` text NOT NULL,
  `fault_type` varchar(50) DEFAULT NULL,
  `under_warranty` tinyint(1) DEFAULT 0,
  `invoice_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `request_type` varchar(50) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `status` enum('pending','under_observation','processing','completed','rejected') DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `requested_date` datetime NOT NULL,
  `accepted_date` datetime DEFAULT NULL,
  `accepted_by` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estimated_completion_date` date DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `urgency_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `location` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `assigned_to_team` varchar(100) DEFAULT NULL,
  `escalation_level` enum('level1','level2','level3') DEFAULT 'level1',
  `time_spent_minutes` int(11) DEFAULT NULL,
  `resolution_type` enum('fixed','workaround','rejected','escalated','pending_parts') DEFAULT NULL,
  `parts_required` text DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `request_no`, `employee_id`, `item_id`, `request_type`, `description`, `request_data`, `status`, `priority`, `requested_date`, `accepted_date`, `accepted_by`, `processed_by`, `notes`, `created_at`, `estimated_completion_date`, `resolution_notes`, `urgency_level`, `location`, `department`, `resolved_date`, `resolved_by`, `assigned_to_team`, `escalation_level`, `time_spent_minutes`, `resolution_type`, `parts_required`, `follow_up_required`, `follow_up_date`) VALUES
(73, 'RET-20260921033338236', 110, NULL, 'return_device', 'Device Return Request\nRequest No: RET-20260921033338236\nTotal Devices: 1\nReturn Reason: resignation', '{\"devices\":[{\"assignment_id\":\"71\",\"assignment_no\":\"ASN-20260921-9196\",\"item_id\":\"131\",\"item_name\":\"Laptop\",\"item_code\":\"ITM-000008\",\"serial_number\":\"5CG553289N\",\"model_number\":\"14-ac130TU\",\"specification\":\"i3,G4,8GB,1TB\",\"brand\":\"N\\/A\",\"device_condition\":\"not_working\",\"damage_description\":\"Too Much slow.Keyboard not working.\",\"accessories_returned\":\"with charger\"}],\"return_reason\":\"resignation\"}', 'completed', 'medium', '2026-09-21 03:33:38', NULL, NULL, NULL, NULL, '2026-09-21 03:33:38', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, NULL, 'level1', NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request_assignments`
--

CREATE TABLE `request_assignments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('pending','allocated','delivered','cancelled') DEFAULT 'pending',
  `allocation_notes` text DEFAULT NULL,
  `allocated_by` int(11) DEFAULT NULL,
  `allocated_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_attachments`
--

CREATE TABLE `request_attachments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_comments`
--

CREATE TABLE `request_comments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `commented_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_documents`
--

CREATE TABLE `request_documents` (
  `id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `document_type` enum('handover','upgrade','repair','assign') NOT NULL,
  `generated_date` datetime DEFAULT NULL,
  `signed_by_employee` tinyint(1) DEFAULT 0,
  `signed_by_it` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_approvals`
--

CREATE TABLE `return_approvals` (
  `id` int(11) NOT NULL,
  `return_request_id` int(11) NOT NULL,
  `action` enum('approved','rejected','pending_review','completed') NOT NULL DEFAULT 'pending_review',
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `stock_updated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_approvals`
--

INSERT INTO `return_approvals` (`id`, `return_request_id`, `action`, `processed_by`, `notes`, `stock_updated`, `created_at`) VALUES
(13, 19, 'approved', 4, '', 0, '2026-09-21 03:35:27'),
(14, 19, 'approved', 4, '', 0, '2026-09-21 03:35:33');

-- --------------------------------------------------------

--
-- Table structure for table `return_device_items`
--

CREATE TABLE `return_device_items` (
  `id` int(11) NOT NULL,
  `return_request_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `device_condition` enum('good','minor_damage','major_damage','not_working') DEFAULT 'good',
  `damage_description` text DEFAULT NULL,
  `accessories_returned` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processing_status` enum('pending','approved','rejected','added_to_stock','skipped') DEFAULT 'pending',
  `stock_added_date` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_device_items`
--

INSERT INTO `return_device_items` (`id`, `return_request_id`, `assignment_id`, `device_condition`, `damage_description`, `accessories_returned`, `created_at`, `processing_status`, `stock_added_date`, `processed_by`) VALUES
(19, 19, 71, 'major_damage', '', '', '2026-09-21 03:33:38', 'added_to_stock', '2026-09-21 03:37:28', 4);

-- --------------------------------------------------------

--
-- Table structure for table `return_device_requests`
--

CREATE TABLE `return_device_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) DEFAULT NULL,
  `request_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `device_condition` enum('good','minor_damage','major_damage','not_working') DEFAULT 'good',
  `return_reason` varchar(50) NOT NULL,
  `reason_details` text DEFAULT NULL,
  `accessories_returned` text DEFAULT NULL,
  `damage_description` text DEFAULT NULL,
  `cleaning_done` tinyint(1) DEFAULT 0,
  `data_backup_confirmed` tinyint(1) DEFAULT 0,
  `exit_clearance` tinyint(1) DEFAULT 0,
  `total_devices` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_device_requests`
--

INSERT INTO `return_device_requests` (`id`, `request_no`, `request_id`, `assignment_id`, `device_condition`, `return_reason`, `reason_details`, `accessories_returned`, `damage_description`, `cleaning_done`, `data_backup_confirmed`, `exit_clearance`, `total_devices`, `created_at`) VALUES
(19, 'RET-20260921033338236', 73, 71, 'not_working', 'resignation', 'Separated Employee', 'with charger', 'Too Much slow.Keyboard not working.', 1, 1, 1, 1, '2026-09-21 03:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `return_stock_history`
--

CREATE TABLE `return_stock_history` (
  `id` int(11) NOT NULL,
  `return_device_item_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_stock_history`
--

INSERT INTO `return_stock_history` (`id`, `return_device_item_id`, `item_id`, `quantity_added`, `previous_stock`, `new_stock`, `processed_by`, `notes`, `created_at`) VALUES
(15, 19, 131, 1, 0, 1, 4, 'Device returned. Item Code: ITM-000008. Condition: major_damage. Now available for reassignment.', '2026-09-21 03:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system`, `created_at`) VALUES
(1, 'admin', 'Administrator', 'Full system access - can manage everything', 1, '2026-06-01 11:57:51'),
(2, 'it_staff', 'IT Staff', 'Limited access - can manage inventory and assignments', 1, '2026-06-01 11:57:51');

-- --------------------------------------------------------

--
-- Table structure for table `software_access_requests`
--

CREATE TABLE `software_access_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `access_type` varchar(50) NOT NULL,
  `software_name` varchar(200) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `duration_needed` varchar(50) DEFAULT NULL,
  `justification` text NOT NULL,
  `authorization_file` varchar(255) DEFAULT NULL,
  `existing_access` tinyint(1) DEFAULT 0,
  `supervisor_name` varchar(100) DEFAULT NULL,
  `supervisor_email` varchar(100) DEFAULT NULL,
  `required_from` date DEFAULT NULL,
  `required_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `license_id` int(11) DEFAULT NULL,
  `access_granted_date` datetime DEFAULT NULL,
  `access_revoked_date` datetime DEFAULT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `installation_status` enum('pending','installed','failed','not_applicable') DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `user_trained` tinyint(1) DEFAULT 0,
  `access_revoked` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `software_updates`
--

CREATE TABLE `software_updates` (
  `id` int(11) NOT NULL,
  `license_id` int(11) DEFAULT NULL,
  `software_name` varchar(200) NOT NULL,
  `current_version` varchar(50) DEFAULT NULL,
  `available_version` varchar(50) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `update_type` enum('security','feature','bug_fix','major') DEFAULT 'feature',
  `download_url` varchar(500) DEFAULT NULL,
  `release_notes` text DEFAULT NULL,
  `is_applied` tinyint(1) DEFAULT 0,
  `applied_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `bill_status` enum('paid','pending','partial') DEFAULT 'pending',
  `payment_status` enum('paid','pending','partial') DEFAULT 'pending',
  `payment_mode` enum('cash','cheque','bank_transfer') DEFAULT 'cash',
  `cheque_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bill_document` varchar(255) DEFAULT NULL,
  `document_uploaded_at` datetime DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `acknowledgement_no` varchar(50) DEFAULT NULL,
  `acknowledgement_generated` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_in`
--

INSERT INTO `stock_in` (`id`, `bill_id`, `invoice_no`, `invoice_date`, `vendor_id`, `item_id`, `quantity`, `unit_price`, `total_amount`, `purchase_date`, `bill_status`, `payment_status`, `payment_mode`, `cheque_no`, `notes`, `created_by`, `created_at`, `bill_document`, `document_uploaded_at`, `payment_date`, `payment_reference`, `acknowledgement_no`, `acknowledgement_generated`) VALUES
(19, NULL, 'INV-000001', '2026-08-23', 14, NULL, 0, NULL, 1200.00, '2026-08-23', 'pending', 'pending', 'cash', '', '', 1, '2026-08-23 11:22:48', 'uploads/bills/BILL_1787484168_8823.pdf', NULL, NULL, '', NULL, 0),
(20, NULL, 'INV-000002', '2026-08-23', 14, NULL, 0, NULL, 2400.00, '2026-08-23', 'pending', 'pending', 'cash', '', '', 1, '2026-08-23 11:29:19', 'uploads/bills/BILL_1787484559_1678.pdf', NULL, NULL, '', NULL, 0),
(21, NULL, 'INV-000003', '2026-08-23', 14, NULL, 0, NULL, 450.00, '2026-08-23', 'pending', 'pending', 'cash', '', '', 1, '2026-08-23 11:37:52', NULL, NULL, NULL, '', NULL, 0),
(22, NULL, 'INV-000004', '2026-08-24', 14, NULL, 0, NULL, 13500.00, '2026-08-24', 'pending', 'pending', 'cash', '', '', 6, '2026-08-24 04:18:01', NULL, NULL, NULL, '', NULL, 0),
(23, NULL, '18082026/1037', '2026-08-18', 5, NULL, 0, NULL, 120850.00, '2026-08-18', 'pending', 'pending', 'cash', '', 'i5, 10 G, 32 GB RAM, 512 GB SSD, ', 6, '2026-08-30 03:58:22', 'uploads/bills/BILL_1788062302_9104.pdf', NULL, NULL, '', NULL, 0),
(24, NULL, 'INV-000005', '2026-08-30', 14, NULL, 0, NULL, 12700.00, '2026-08-30', 'pending', 'pending', 'cash', '', '', 6, '2026-08-30 07:01:35', 'uploads/bills/BILL_1788073295_1194.pdf', NULL, NULL, '', NULL, 0),
(25, NULL, '14092026/1075', '2026-09-17', 5, NULL, 0, NULL, 60000.00, '2026-09-17', 'pending', 'pending', 'cash', '', '', 6, '2026-09-17 03:51:24', 'uploads/bills/BILL_1789617084_1213.pdf', NULL, NULL, '', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_in_items`
--

CREATE TABLE `stock_in_items` (
  `id` int(11) NOT NULL,
  `stock_in_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_in_items`
--

INSERT INTO `stock_in_items` (`id`, `stock_in_id`, `item_id`, `quantity`, `unit_price`, `total_price`, `notes`, `created_at`) VALUES
(23, 19, 124, 1, 1200.00, 1200.00, NULL, '2026-08-23 11:22:48'),
(24, 20, 125, 1, 2400.00, 2400.00, NULL, '2026-08-23 11:29:19'),
(25, 21, 126, 1, 450.00, 450.00, NULL, '2026-08-23 11:37:52'),
(26, 22, 127, 1, 13500.00, 13500.00, NULL, '2026-08-24 04:18:01'),
(27, 23, 128, 2, 60425.00, 120850.00, NULL, '2026-08-30 03:58:22'),
(28, 24, 129, 1, 12700.00, 12700.00, NULL, '2026-08-30 07:01:35'),
(29, 25, 130, 1, 60000.00, 60000.00, NULL, '2026-09-17 03:51:24');

-- --------------------------------------------------------

--
-- Table structure for table `stock_replacements`
--

CREATE TABLE `stock_replacements` (
  `id` int(11) NOT NULL,
  `old_item_id` int(11) DEFAULT NULL,
  `new_item_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `replacement_date` date NOT NULL,
  `payment_required` tinyint(1) DEFAULT 0,
  `payment_amount` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_returns`
--

CREATE TABLE `stock_returns` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `condition_status` enum('good','damaged','repairable') DEFAULT 'good',
  `damage_details` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('stock_in','stock_out','assigned','returned','damaged','return_approved') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `transaction_type`, `item_id`, `quantity`, `reference_id`, `reference_type`, `notes`, `created_by`, `created_at`) VALUES
(36, 'stock_in', 124, 1, 19, 'stock_in', NULL, 1, '2026-08-23 11:22:48'),
(37, 'stock_in', 125, 1, 20, 'stock_in', NULL, 1, '2026-08-23 11:29:19'),
(38, 'stock_in', 126, 1, 21, 'stock_in', NULL, 1, '2026-08-23 11:37:52'),
(39, 'stock_in', 127, 1, 22, 'stock_in', NULL, 6, '2026-08-24 04:18:01'),
(40, 'stock_in', 128, 2, 23, 'stock_in', NULL, 6, '2026-08-30 03:58:22'),
(41, 'stock_in', 129, 1, 24, 'stock_in', NULL, 6, '2026-08-30 07:01:35'),
(42, 'stock_in', 130, 1, 25, 'stock_in', NULL, 6, '2026-09-17 03:51:24');

-- --------------------------------------------------------

--
-- Table structure for table `submission_attachments`
--

CREATE TABLE `submission_attachments` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submission_devices`
--

CREATE TABLE `submission_devices` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `item_type_id` int(11) DEFAULT NULL,
  `device_name` varchar(200) NOT NULL,
  `brand_name` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `assigned_by` varchar(100) DEFAULT NULL,
  `current_condition` enum('good','minor_damage','major_damage','not_working') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','added_to_stock') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `new_item_id` int(11) DEFAULT NULL,
  `final_category_id` int(11) DEFAULT NULL,
  `final_sub_category_id` int(11) DEFAULT NULL,
  `final_brand_id` int(11) DEFAULT NULL,
  `final_warranty_months` int(11) DEFAULT 12,
  `final_price` decimal(12,2) DEFAULT NULL,
  `final_quantity` int(11) DEFAULT 1,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_devices`
--

INSERT INTO `submission_devices` (`id`, `submission_id`, `item_type_id`, `device_name`, `brand_name`, `model_number`, `serial_number`, `specification`, `purchase_date`, `assigned_date`, `assigned_by`, `current_condition`, `notes`, `supporting_document`, `status`, `admin_notes`, `new_item_id`, `final_category_id`, `final_sub_category_id`, `final_brand_id`, `final_warranty_months`, `final_price`, `final_quantity`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(49, 36, 6, 'Mouse', '', 'OP-730D', 'BG2010117435', 'Wire mouse', NULL, NULL, '', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:45:12\nProcessed on 2026-09-21 03:45:21 | NEW ITEM: ITM-000013 | Qty: 1 | Assignment: ASN-20260921-1036', 136, NULL, NULL, NULL, 6, 350.00, 1, 4, '2026-09-21 03:45:21', '2026-08-23 06:08:17', '2026-09-21 03:45:21'),
(50, 37, 4, 'Monitor', 'LG', '22MK430H', '109NTNH2U063', '19 INCH', '2023-01-01', '2023-01-01', 'IT Department', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:43:54\nProcessed on 2026-09-21 03:44:05 | NEW ITEM: ITM-000010 | Qty: 1 | Assignment: ASN-20260921-2879', 133, NULL, NULL, NULL, 12, 10500.00, 1, 4, '2026-09-21 03:44:05', '2026-08-31 03:02:15', '2026-09-21 03:44:05'),
(51, 37, 33, 'IPT', '', 'T21PE2', '2121118070D3892', 'IPT', NULL, NULL, '', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:44:21\nProcessed on 2026-09-21 03:44:35 | NEW ITEM: ITM-000011 | Qty: 1 | Assignment: ASN-20260921-2587', 134, NULL, NULL, NULL, 6, 4500.00, 1, 4, '2026-09-21 03:44:35', '2026-08-31 03:02:15', '2026-09-21 03:44:35'),
(52, 37, 33, 'Clone PC', '', '', '', 'i3, G7, 16GB, 512GB, 1TB', NULL, NULL, '', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:44:47\nProcessed on 2026-09-21 03:45:03 | NEW ITEM: ITM-000012 | Qty: 1 | Assignment: ASN-20260921-5719', 135, NULL, NULL, NULL, 6, 25000.00, 1, 4, '2026-09-21 03:45:03', '2026-08-31 03:02:15', '2026-09-21 03:45:03'),
(53, 38, 5, 'Keyboard', '', 'KRS-82', '23SHI00', 'wire keyboard', NULL, NULL, '', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:42:07\nProcessed on 2026-09-21 03:43:45 | NEW ITEM: ITM-000009 | Qty: 1 | Assignment: ASN-20260921-5314', 132, NULL, NULL, NULL, 6, 600.00, 1, 4, '2026-09-21 03:43:45', '2026-08-31 03:09:05', '2026-09-21 03:43:45'),
(54, 39, 1, 'Laptop', '', '14-ac130TU', '5CG553289N', 'i3,G4,8GB,1TB', NULL, NULL, '', 'good', '', NULL, 'added_to_stock', '\nApproved on 2026-09-21 03:26:07\nApproved on 2026-09-21 03:27:46\nProcessed on 2026-09-21 03:29:56 | NEW ITEM: ITM-000008 | Qty: 1 | Assignment: ASN-20260921-9196', 131, 58, NULL, 2, 12, 25000.00, 1, 4, '2026-09-21 03:29:56', '2026-09-21 03:24:57', '2026-09-21 03:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

CREATE TABLE `sub_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `message`, `table_name`, `record_id`, `old_data`, `new_data`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(104, NULL, 'ITEM_ADDED', 'Item type: 19', 'items', 123, NULL, NULL, NULL, NULL, NULL, '2026-08-19 04:45:31'),
(105, NULL, 'ITEM_ADDED', 'Item type: 11', 'items', 124, NULL, NULL, NULL, NULL, NULL, '2026-08-23 10:52:43'),
(106, NULL, 'ITEM_ADDED', 'Item type: 12', 'items', 125, NULL, NULL, NULL, NULL, NULL, '2026-08-23 10:57:16'),
(107, NULL, 'ITEM_ADDED', 'Item type: 6', 'items', 126, NULL, NULL, NULL, NULL, NULL, '2026-08-23 11:37:00'),
(108, NULL, 'ITEM_ADDED', 'Item type: 30', 'items', 127, NULL, NULL, NULL, NULL, NULL, '2026-08-24 03:59:12'),
(109, NULL, 'ITEM_ADDED', 'Item type: 2', 'items', 128, NULL, NULL, NULL, NULL, NULL, '2026-08-30 03:58:22'),
(110, NULL, 'ITEM_ADDED', 'Item type: 4', 'items', 129, NULL, NULL, NULL, NULL, NULL, '2026-08-30 07:01:35'),
(111, NULL, 'ITEM_ADDED', 'Item type: 3', 'items', 130, NULL, NULL, NULL, NULL, NULL, '2026-09-17 03:51:24'),
(112, NULL, 'ITEM_ADDED', 'Item type: 1', 'items', 131, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:29:56'),
(113, NULL, 'ITEM_ADDED', 'Item type: 5', 'items', 132, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:43:45'),
(114, NULL, 'ITEM_ADDED', 'Item type: 4', 'items', 133, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:44:05'),
(115, NULL, 'ITEM_ADDED', 'Item type: 33', 'items', 134, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:44:35'),
(116, NULL, 'ITEM_ADDED', 'Item type: 33', 'items', 135, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:45:03'),
(117, NULL, 'ITEM_ADDED', 'Item type: 6', 'items', 136, NULL, NULL, NULL, NULL, NULL, '2026-09-21 03:45:21');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `task_description` text DEFAULT NULL,
  `task_type` enum('current','pending','complete') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `attachment_file` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technical_support_requests`
--

CREATE TABLE `technical_support_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `issue_type` varchar(50) NOT NULL,
  `issue_title` varchar(200) NOT NULL,
  `urgency_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `affected_device` varchar(200) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `steps_to_reproduce` text DEFAULT NULL,
  `expected_behavior` text DEFAULT NULL,
  `actual_behavior` text DEFAULT NULL,
  `browser_info` varchar(200) DEFAULT NULL,
  `os_info` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `solution_provided` text DEFAULT NULL,
  `resolution_steps` text DEFAULT NULL,
  `customer_confirmation` tinyint(1) DEFAULT 0,
  `confirmation_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_attachments`
--

CREATE TABLE `transfer_attachments` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_items`
--

CREATE TABLE `transfer_items` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `serial_number` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `condition_status` enum('good','minor_damage','major_damage','repairable') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_locations`
--

CREATE TABLE `transfer_locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('from','to','both') DEFAULT 'both',
  `department` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transfer_locations`
--

INSERT INTO `transfer_locations` (`id`, `name`, `type`, `department`, `address`, `contact_person`, `contact_phone`, `is_active`, `created_at`) VALUES
(1, 'IT Department', 'from', 'IT Department', '660 Washpur, PO: Shyamlapur, PS: Hazaribagh, Dhaka 1310', 'IT Admin', NULL, 1, '2026-06-16 04:35:05'),
(2, 'Factory', 'to', 'Production', 'Gazipur Factory', 'Factory Manager', NULL, 1, '2026-06-16 04:35:05'),
(3, 'Natore Depot', 'to', 'Distribution', 'Natore, Bangladesh', 'Depot Manager', NULL, 1, '2026-06-16 04:35:05'),
(4, 'Corporate Office', 'both', 'Administration', 'Corporate Office, Dhaka', 'Admin', NULL, 1, '2026-06-16 04:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `unlisted_device_submissions`
--

CREATE TABLE `unlisted_device_submissions` (
  `id` int(11) NOT NULL,
  `submission_no` varchar(50) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `device_name` varchar(200) NOT NULL,
  `device_type` varchar(100) DEFAULT NULL,
  `brand_name` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `assigned_by` varchar(100) DEFAULT NULL,
  `current_condition` enum('good','minor_damage','major_damage','not_working') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','added_to_stock') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `new_item_id` int(11) DEFAULT NULL,
  `submitted_by_ip` varchar(45) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `total_devices` int(11) DEFAULT 0,
  `submission_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unlisted_device_submissions`
--

INSERT INTO `unlisted_device_submissions` (`id`, `submission_no`, `employee_id`, `device_name`, `device_type`, `brand_name`, `model_number`, `serial_number`, `specification`, `purchase_date`, `assigned_date`, `assigned_by`, `current_condition`, `notes`, `supporting_document`, `status`, `admin_notes`, `new_item_id`, `submitted_by_ip`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`, `total_devices`, `submission_notes`) VALUES
(36, 'UNL-20260823-6147', 490, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', NULL, NULL, 'added_to_stock', NULL, NULL, '27.147.137.55', NULL, NULL, '2026-08-23 06:08:17', '2026-09-21 03:45:21', 1, ''),
(37, 'UNL-20260831-3232', 490, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', NULL, NULL, 'added_to_stock', NULL, NULL, '27.147.137.55', NULL, NULL, '2026-08-31 03:02:15', '2026-09-21 03:45:03', 3, ''),
(38, 'UNL-20260831-6595', 490, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', NULL, NULL, 'added_to_stock', NULL, NULL, '27.147.137.55', NULL, NULL, '2026-08-31 03:09:05', '2026-09-21 03:43:45', 1, ''),
(39, 'UNL-20260921-3138', 110, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', NULL, NULL, 'added_to_stock', NULL, NULL, '27.147.137.55', NULL, NULL, '2026-09-21 03:24:57', '2026-09-21 03:29:56', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `update_device_requests`
--

CREATE TABLE `update_device_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `update_type` varchar(50) NOT NULL,
  `current_value` text DEFAULT NULL,
  `requested_value` text DEFAULT NULL,
  `reason` text NOT NULL,
  `supporting_doc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upgrade_requests`
--

CREATE TABLE `upgrade_requests` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `current_specs` text DEFAULT NULL,
  `required_upgrade` text NOT NULL,
  `reason` text NOT NULL,
  `device_condition` varchar(50) DEFAULT NULL,
  `estimated_cost` decimal(12,2) DEFAULT NULL,
  `vendor_recommendation` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `new_item_id` int(11) DEFAULT NULL,
  `upgrade_completed_date` date DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `post_upgrade_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','it_staff') DEFAULT 'it_staff',
  `role_id` int(11) DEFAULT NULL,
  `role_name` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `role_id`, `role_name`, `is_active`, `last_login`, `created_at`, `last_ip`) VALUES
(1, 'admin', '$2y$12$5EoCXP/phZtl8JPdJBL/xuKaO04nMAmvsCNR1CQWngugPjsE/FCnu', 'Administrator', '', 'admin', 1, 'admin', 1, '2026-05-29 00:23:46', '2026-05-14 03:53:22', NULL),
(4, 'shihab', '$2y$12$gQY0BxjmadikozzDmO7b/O4e3XLiM.uzlZd/fmfSUTh8Putd2MRrO', 'Shihab Uddin', 'shihab.uddin@unigroup-bd.com', 'admin', 1, 'admin', 1, NULL, '2026-06-07 04:15:44', NULL),
(5, 'masum', '$2y$12$sOjvOnQj3cmHW9F4As/AD.tfxAv9bi7ZWibGzTz5UnLgLpZN3FMXK', 'Md. Morshed Masum', 'morshed.masum@unigroup-bd.com', 'admin', 1, 'admin', 1, NULL, '2026-06-07 04:17:26', NULL),
(6, 'zami', '$2y$12$UZxRLM/Uu4IvYfvpObgbzOr2q9p4gwjfp2.AfYbwVmZ/xhhkffOUy', 'Musabbir zami', 'musavvir.jami@unigroup-bd.com', 'admin', 1, 'admin', 1, NULL, '2026-06-07 05:52:53', NULL),
(7, 'Bappy', '$2y$12$0OcuYGpPxnEfrE/zVy13euvvsWRW7MmDo4g5MyLZcRF.pNZVviH7u', 'Md. Shazzad Bappy', 'shazzad.bappy@unigroup-bd.com', 'admin', 1, 'admin', 1, NULL, '2026-06-15 03:28:50', NULL),
(8, 'rakib', '$2y$12$etJgLmxM4RX6yVk8lecYX.6ydLWbLuIxOTfI6gYxp2gTe12zGPl8C', 'Md. Abdur Rakib', 'abdur.rakib@unigroup-bd.com', 'admin', 1, 'admin', 1, NULL, '2026-06-15 03:35:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(5, 1, 'user_created', 'New user: shihab (Role: IT Staff)', '27.147.137.55', '2026-06-07 04:15:44'),
(6, 1, 'user_created', 'New user: masum (Role: Administrator)', '27.147.137.55', '2026-06-07 04:17:26'),
(7, 1, 'user_created', 'New user: zami (Role: Administrator)', '27.147.137.55', '2026-06-07 05:52:53'),
(8, 1, 'user_updated', 'User ID: 4', '27.147.137.55', '2026-06-07 05:53:02'),
(9, 1, 'user_created', 'New user: Bappy (Role: Administrator)', '27.147.137.55', '2026-06-15 03:28:50'),
(10, 1, 'user_updated', 'User ID: 4', '27.147.137.55', '2026-06-15 03:30:11'),
(11, 1, 'user_created', 'New user: rakib (Role: Administrator)', '27.147.137.55', '2026-06-15 03:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_tasks`
--

CREATE TABLE `user_tasks` (
  `id` int(11) NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `task_description` text DEFAULT NULL,
  `task_type` enum('license_renewal','warranty_renewal','software_update','maintenance','custom') DEFAULT 'custom',
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `reminder_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to_employee_id` int(11) DEFAULT NULL,
  `assigned_by_employee_id` int(11) DEFAULT NULL,
  `related_license_id` int(11) DEFAULT NULL,
  `related_warranty_id` int(11) DEFAULT NULL,
  `parent_task_id` int(11) DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `vendor_name` varchar(200) NOT NULL COMMENT 'Vendor/Supplier Name',
  `company_name` varchar(200) DEFAULT NULL COMMENT 'Legal Company Name',
  `office_address` text DEFAULT NULL COMMENT 'Full Office Address',
  `office_phone` varchar(20) DEFAULT NULL COMMENT 'Office Contact Number',
  `office_email` varchar(100) DEFAULT NULL COMMENT 'Office Email Address',
  `tin_no` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
  `bin_no` varchar(50) DEFAULT NULL COMMENT 'Business Identification Number',
  `trade_license_no` varchar(100) DEFAULT NULL COMMENT 'Trade License Number',
  `gst_no` varchar(50) DEFAULT NULL COMMENT 'GST Number (15 characters)',
  `contact_person` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Person Name',
  `contact_designation` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Designation',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT 'Primary Contact Phone',
  `contact_email` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Email',
  `contact_address` text DEFAULT NULL COMMENT 'Primary Contact Address',
  `secondary_contact_person` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Name',
  `secondary_contact_designation` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Designation',
  `secondary_contact_phone` varchar(20) DEFAULT NULL COMMENT 'Secondary Contact Phone',
  `secondary_contact_email` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Email',
  `website` varchar(200) DEFAULT NULL COMMENT 'Company Website URL',
  `notes` text DEFAULT NULL COMMENT 'Additional Notes/Remarks',
  `attached_document` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded document',
  `name` varchar(100) DEFAULT NULL COMMENT 'Legacy: Vendor Name',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Legacy: Phone Number',
  `email` varchar(100) DEFAULT NULL COMMENT 'Legacy: Email Address',
  `address` text DEFAULT NULL COMMENT 'Legacy: Address',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Vendor Status (1=Active, 0=Inactive)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Vendors/Suppliers Master Table';

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `vendor_name`, `company_name`, `office_address`, `office_phone`, `office_email`, `tin_no`, `bin_no`, `trade_license_no`, `gst_no`, `contact_person`, `contact_designation`, `contact_phone`, `contact_email`, `contact_address`, `secondary_contact_person`, `secondary_contact_designation`, `secondary_contact_phone`, `secondary_contact_email`, `website`, `notes`, `attached_document`, `name`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'MicroMarks', 'MicroMarks', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:12:16', NULL),
(5, 'R4 Technology & Network', 'R4 Technology & Network', 'Dhaka, Bangladesh', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:18:59', NULL),
(6, 'Global Brand Limited', 'Global Brand Limited', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:19:38', NULL),
(7, 'A.R. Trade International', 'A.R. Trade International', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:20:14', NULL),
(8, 'Unified IT', 'Unified IT', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:22:11', NULL),
(9, 'Excel Technology', 'Excel Technology', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:23:09', NULL),
(10, 'Eastern IT', 'Eastern IT', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:23:42', NULL),
(11, 'North Vision Ltd.', 'North Vision Ltd.', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:24:18', NULL),
(12, 'Comtech Solutions Ltd.', 'Comtech Solutions Ltd.', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:25:14', NULL),
(13, 'L.R Corporation', 'L.R Corporation', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:25:59', NULL),
(14, 'Optical Computer', 'Optical Computer', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:26:26', NULL),
(15, 'Rain Technology', 'Rain Technology', 'Dhaka, Bangladesh.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-05-31 07:27:22', NULL),
(16, 'Smart', 'Smart Technologies', 'Jahir Smart Tower, 205/1 & 205/1/A, West Kafrul, Begum Rokeya Sharani, Taltola, Dhaka-1207', '09678100500', 'info@smartbd.com', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-11 04:31:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendors_backup_2026_05_30`
--

CREATE TABLE `vendors_backup_2026_05_30` (
  `id` int(11) NOT NULL,
  `vendor_name` varchar(200) NOT NULL COMMENT 'Vendor/Supplier Name',
  `company_name` varchar(200) DEFAULT NULL COMMENT 'Legal Company Name',
  `office_address` text DEFAULT NULL COMMENT 'Full Office Address',
  `office_phone` varchar(20) DEFAULT NULL COMMENT 'Office Contact Number',
  `office_email` varchar(100) DEFAULT NULL COMMENT 'Office Email Address',
  `tin_no` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
  `bin_no` varchar(50) DEFAULT NULL COMMENT 'Business Identification Number',
  `trade_license_no` varchar(100) DEFAULT NULL COMMENT 'Trade License Number',
  `gst_no` varchar(50) DEFAULT NULL COMMENT 'GST Number (15 characters)',
  `contact_person` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Person Name',
  `contact_designation` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Designation',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT 'Primary Contact Phone',
  `contact_email` varchar(100) DEFAULT NULL COMMENT 'Primary Contact Email',
  `contact_address` text DEFAULT NULL COMMENT 'Primary Contact Address',
  `secondary_contact_person` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Name',
  `secondary_contact_designation` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Designation',
  `secondary_contact_phone` varchar(20) DEFAULT NULL COMMENT 'Secondary Contact Phone',
  `secondary_contact_email` varchar(100) DEFAULT NULL COMMENT 'Secondary Contact Email',
  `website` varchar(200) DEFAULT NULL COMMENT 'Company Website URL',
  `notes` text DEFAULT NULL COMMENT 'Additional Notes/Remarks',
  `attached_document` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded document',
  `name` varchar(100) DEFAULT NULL COMMENT 'Legacy: Vendor Name',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Legacy: Phone Number',
  `email` varchar(100) DEFAULT NULL COMMENT 'Legacy: Email Address',
  `address` text DEFAULT NULL COMMENT 'Legacy: Address',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Vendor Status (1=Active, 0=Inactive)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Vendors/Suppliers Master Table';

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_item_types_full`
-- (See below for the actual view)
--
CREATE TABLE `vw_item_types_full` (
`id` int(11)
,`name` varchar(100)
,`category_id` int(11)
,`category_name` varchar(100)
,`sub_category_id` int(11)
,`sub_category_name` varchar(100)
,`description` text
,`icon` varchar(50)
,`sort_order` int(11)
,`is_active` tinyint(1)
,`is_system` tinyint(1)
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_transfers_with_items`
-- (See below for the actual view)
--
CREATE TABLE `vw_transfers_with_items` (
`id` int(11)
,`transfer_no` varchar(50)
,`transfer_date` date
,`from_location` varchar(100)
,`department` varchar(100)
,`from_department` varchar(100)
,`from_address` text
,`to_location` varchar(100)
,`to_department` varchar(100)
,`to_attn` varchar(100)
,`to_address` text
,`product_name` varchar(200)
,`item_id` int(11)
,`product_type` varchar(100)
,`quantity` int(11)
,`serial_numbers` text
,`description` text
,`reason` text
,`status` enum('draft','pending','approved','dispatched','delivered','cancelled')
,`dispatched_date` date
,`delivered_date` date
,`handled_by` varchar(100)
,`received_by` varchar(100)
,`notes` text
,`attachment_count` int(11)
,`created_by` int(11)
,`approved_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`item_code` varchar(50)
,`item_full_name` varchar(200)
,`item_specification` text
,`brand_id` int(11)
,`brand_name` varchar(100)
,`item_price` decimal(12,2)
,`item_stock` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

CREATE TABLE `warranties` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `warranty_type` enum('manufacturer','extended','service') DEFAULT 'manufacturer',
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `warranty_provider` varchar(200) DEFAULT NULL,
  `provider_phone` varchar(50) DEFAULT NULL,
  `provider_email` varchar(100) DEFAULT NULL,
  `warranty_document` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `documentation_file` varchar(500) DEFAULT NULL,
  `status` enum('active','expired','expiring_soon') DEFAULT 'active',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `vendor_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `claim_phone` varchar(50) DEFAULT NULL,
  `claim_email` varchar(100) DEFAULT NULL,
  `claim_website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warranty_claims`
--

CREATE TABLE `warranty_claims` (
  `id` int(11) NOT NULL,
  `warranty_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `issue_description` text NOT NULL,
  `claim_status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warranty_history`
--

CREATE TABLE `warranty_history` (
  `id` int(11) NOT NULL,
  `warranty_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accessories_requests`
--
ALTER TABLE `accessories_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `allocated_item_id` (`allocated_item_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assignment_no` (`assignment_no`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_return_status` (`return_status`),
  ADD KEY `idx_return_request_id` (`return_request_id`);

--
-- Indexes for table `assign_device_requests`
--
ALTER TABLE `assign_device_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_no` (`bill_no`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `cash_register_id` (`cash_register_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `stock_in_id` (`stock_in_id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `bill_payments`
--
ALTER TABLE `bill_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `cash_register`
--
ALTER TABLE `cash_register`
  ADD PRIMARY KEY (`id`),
  ADD KEY `closed_by` (`closed_by`);

--
-- Indexes for table `cash_register_transactions`
--
ALTER TABLE `cash_register_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_register_id` (`cash_register_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `damaged_items`
--
ALTER TABLE `damaged_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `damages`
--
ALTER TABLE `damages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `damage_no` (`damage_no`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `replacement_item_id` (`replacement_item_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_damage_date` (`damage_date`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `delivery_logs`
--
ALTER TABLE `delivery_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`);

--
-- Indexes for table `device_assignment_items`
--
ALTER TABLE `device_assignment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `allocated_item_id` (`allocated_item_id`);

--
-- Indexes for table `device_assignment_requests`
--
ALTER TABLE `device_assignment_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `device_ownership_verifications`
--
ALTER TABLE `device_ownership_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `device_transfers`
--
ALTER TABLE `device_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_no` (`transfer_no`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_assignment_id` (`assignment_id`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `device_types`
--
ALTER TABLE `device_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_purchases`
--
ALTER TABLE `doctor_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pf_no` (`pf_no`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `sub_category_id` (`sub_category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type_id` (`type_id`),
  ADD KEY `idx_brand_id` (`brand_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_sub_category_id` (`sub_category_id`);

--
-- Indexes for table `item_price_history`
--
ALTER TABLE `item_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `item_serial_numbers`
--
ALTER TABLE `item_serial_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `idx_damage_id` (`damage_id`);

--
-- Indexes for table `item_types`
--
ALTER TABLE `item_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_sub_category_id` (`sub_category_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_system` (`is_system`);

--
-- Indexes for table `item_vendors`
--
ALTER TABLE `item_vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `licenses`
--
ALTER TABLE `licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `license_assignments`
--
ALTER TABLE `license_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `license_history`
--
ALTER TABLE `license_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `monthly_purchase_details`
--
ALTER TABLE `monthly_purchase_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_register_id` (`cash_register_id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `payment_acknowledgements`
--
ALTER TABLE `payment_acknowledgements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `acknowledgement_no` (`acknowledgement_no`),
  ADD KEY `payment_slip_id` (`payment_slip_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payment_slips`
--
ALTER TABLE `payment_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slip_no` (`slip_no`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `replacement_requests`
--
ALTER TABLE `replacement_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `faulty_device_id` (`faulty_device_id`),
  ADD KEY `replacement_device_id` (`replacement_device_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `accepted_by` (`accepted_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_request_type` (`request_type`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_requested_date` (`requested_date`),
  ADD KEY `fk_requests_resolved_by` (`resolved_by`);

--
-- Indexes for table `request_assignments`
--
ALTER TABLE `request_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `allocated_by` (`allocated_by`);

--
-- Indexes for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `request_comments`
--
ALTER TABLE `request_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `commented_by` (`commented_by`);

--
-- Indexes for table `request_documents`
--
ALTER TABLE `request_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `return_approvals`
--
ALTER TABLE `return_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_request_id` (`return_request_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `return_device_items`
--
ALTER TABLE `return_device_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_request_id` (`return_request_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `idx_processing_status` (`processing_status`);

--
-- Indexes for table `return_device_requests`
--
ALTER TABLE `return_device_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `return_stock_history`
--
ALTER TABLE `return_stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_device_item_id` (`return_device_item_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `software_access_requests`
--
ALTER TABLE `software_access_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `license_id` (`license_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `software_updates`
--
ALTER TABLE `software_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_id` (`license_id`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_in_id` (`stock_in_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_replacements`
--
ALTER TABLE `stock_replacements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `old_item_id` (`old_item_id`),
  ADD KEY `new_item_id` (`new_item_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_returns`
--
ALTER TABLE `stock_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `submission_devices`
--
ALTER TABLE `submission_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission_device` (`submission_id`,`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `item_type_id` (`item_type_id`),
  ADD KEY `new_item_id` (`new_item_id`),
  ADD KEY `final_category_id` (`final_category_id`),
  ADD KEY `final_sub_category_id` (`final_sub_category_id`),
  ADD KEY `final_brand_id` (`final_brand_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status_new_item` (`status`,`new_item_id`),
  ADD KEY `idx_new_item_id` (`new_item_id`);

--
-- Indexes for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `technical_support_requests`
--
ALTER TABLE `technical_support_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `transfer_attachments`
--
ALTER TABLE `transfer_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`);

--
-- Indexes for table `transfer_items`
--
ALTER TABLE `transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `transfer_locations`
--
ALTER TABLE `transfer_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unlisted_device_submissions`
--
ALTER TABLE `unlisted_device_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `submission_no` (`submission_no`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `new_item_id` (`new_item_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `update_device_requests`
--
ALTER TABLE `update_device_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `new_item_id` (`new_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_tasks`
--
ALTER TABLE `user_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`),
  ADD KEY `task_type` (`task_type`),
  ADD KEY `assigned_to_employee_id` (`assigned_to_employee_id`),
  ADD KEY `assigned_by_employee_id` (`assigned_by_employee_id`),
  ADD KEY `related_license_id` (`related_license_id`),
  ADD KEY `related_warranty_id` (`related_warranty_id`),
  ADD KEY `parent_task_id` (`parent_task_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_name_unique` (`vendor_name`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_contact_phone` (`contact_phone`),
  ADD KEY `idx_office_phone` (`office_phone`),
  ADD KEY `idx_vendor_name` (`vendor_name`),
  ADD KEY `idx_company_name` (`company_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `vendors_backup_2026_05_30`
--
ALTER TABLE `vendors_backup_2026_05_30`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_name_unique` (`vendor_name`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_contact_phone` (`contact_phone`),
  ADD KEY `idx_office_phone` (`office_phone`);

--
-- Indexes for table `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warranty_id` (`warranty_id`);

--
-- Indexes for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warranty_id` (`warranty_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accessories_requests`
--
ALTER TABLE `accessories_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `assign_device_requests`
--
ALTER TABLE `assign_device_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bill_payments`
--
ALTER TABLE `bill_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `cash_register`
--
ALTER TABLE `cash_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cash_register_transactions`
--
ALTER TABLE `cash_register_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `damaged_items`
--
ALTER TABLE `damaged_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `damages`
--
ALTER TABLE `damages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `delivery_logs`
--
ALTER TABLE `delivery_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `device_assignment_items`
--
ALTER TABLE `device_assignment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_assignment_requests`
--
ALTER TABLE `device_assignment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_ownership_verifications`
--
ALTER TABLE `device_ownership_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_transfers`
--
ALTER TABLE `device_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `device_types`
--
ALTER TABLE `device_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `doctor_purchases`
--
ALTER TABLE `doctor_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3379;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `item_price_history`
--
ALTER TABLE `item_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `item_serial_numbers`
--
ALTER TABLE `item_serial_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;

--
-- AUTO_INCREMENT for table `item_types`
--
ALTER TABLE `item_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `item_vendors`
--
ALTER TABLE `item_vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `licenses`
--
ALTER TABLE `licenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `license_assignments`
--
ALTER TABLE `license_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `license_history`
--
ALTER TABLE `license_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `monthly_purchase_details`
--
ALTER TABLE `monthly_purchase_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_acknowledgements`
--
ALTER TABLE `payment_acknowledgements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_slips`
--
ALTER TABLE `payment_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `replacement_requests`
--
ALTER TABLE `replacement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `request_assignments`
--
ALTER TABLE `request_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `request_comments`
--
ALTER TABLE `request_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `request_documents`
--
ALTER TABLE `request_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_approvals`
--
ALTER TABLE `return_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `return_device_items`
--
ALTER TABLE `return_device_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `return_device_requests`
--
ALTER TABLE `return_device_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `return_stock_history`
--
ALTER TABLE `return_stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `software_access_requests`
--
ALTER TABLE `software_access_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `software_updates`
--
ALTER TABLE `software_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `stock_replacements`
--
ALTER TABLE `stock_replacements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_returns`
--
ALTER TABLE `stock_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submission_devices`
--
ALTER TABLE `submission_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technical_support_requests`
--
ALTER TABLE `technical_support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `transfer_attachments`
--
ALTER TABLE `transfer_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transfer_items`
--
ALTER TABLE `transfer_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfer_locations`
--
ALTER TABLE `transfer_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unlisted_device_submissions`
--
ALTER TABLE `unlisted_device_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `update_device_requests`
--
ALTER TABLE `update_device_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_tasks`
--
ALTER TABLE `user_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `vendors_backup_2026_05_30`
--
ALTER TABLE `vendors_backup_2026_05_30`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warranties`
--
ALTER TABLE `warranties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `warranty_history`
--
ALTER TABLE `warranty_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `vw_item_types_full`
--
DROP TABLE IF EXISTS `vw_item_types_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`bhsheadache`@`localhost` SQL SECURITY DEFINER VIEW `vw_item_types_full`  AS SELECT `it`.`id` AS `id`, `it`.`name` AS `name`, `it`.`category_id` AS `category_id`, `c`.`name` AS `category_name`, `it`.`sub_category_id` AS `sub_category_id`, `sc`.`name` AS `sub_category_name`, `it`.`description` AS `description`, `it`.`icon` AS `icon`, `it`.`sort_order` AS `sort_order`, `it`.`is_active` AS `is_active`, `it`.`is_system` AS `is_system`, (select count(0) from `items` `i` where `i`.`type_id` = `it`.`id`) AS `item_count` FROM ((`item_types` `it` left join `categories` `c` on(`it`.`category_id` = `c`.`id` and `c`.`is_active` = 1)) left join `categories` `sc` on(`it`.`sub_category_id` = `sc`.`id` and `sc`.`is_active` = 1)) WHERE `it`.`is_active` = 1 ORDER BY `it`.`sort_order` ASC, `it`.`name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_transfers_with_items`
--
DROP TABLE IF EXISTS `vw_transfers_with_items`;

CREATE ALGORITHM=UNDEFINED DEFINER=`bhsheadache`@`localhost` SQL SECURITY DEFINER VIEW `vw_transfers_with_items`  AS SELECT `dt`.`id` AS `id`, `dt`.`transfer_no` AS `transfer_no`, `dt`.`transfer_date` AS `transfer_date`, `dt`.`from_location` AS `from_location`, `dt`.`department` AS `department`, `dt`.`from_department` AS `from_department`, `dt`.`from_address` AS `from_address`, `dt`.`to_location` AS `to_location`, `dt`.`to_department` AS `to_department`, `dt`.`to_attn` AS `to_attn`, `dt`.`to_address` AS `to_address`, `dt`.`product_name` AS `product_name`, `dt`.`item_id` AS `item_id`, `dt`.`product_type` AS `product_type`, `dt`.`quantity` AS `quantity`, `dt`.`serial_numbers` AS `serial_numbers`, `dt`.`description` AS `description`, `dt`.`reason` AS `reason`, `dt`.`status` AS `status`, `dt`.`dispatched_date` AS `dispatched_date`, `dt`.`delivered_date` AS `delivered_date`, `dt`.`handled_by` AS `handled_by`, `dt`.`received_by` AS `received_by`, `dt`.`notes` AS `notes`, `dt`.`attachment_count` AS `attachment_count`, `dt`.`created_by` AS `created_by`, `dt`.`approved_by` AS `approved_by`, `dt`.`created_at` AS `created_at`, `dt`.`updated_at` AS `updated_at`, `i`.`item_code` AS `item_code`, `i`.`name` AS `item_full_name`, `i`.`specification` AS `item_specification`, `i`.`brand_id` AS `brand_id`, `b`.`name` AS `brand_name`, `i`.`price` AS `item_price`, `i`.`current_qty` AS `item_stock` FROM ((`device_transfers` `dt` left join `items` `i` on(`dt`.`item_id` = `i`.`id`)) left join `brands` `b` on(`i`.`brand_id` = `b`.`id`)) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accessories_requests`
--
ALTER TABLE `accessories_requests`
  ADD CONSTRAINT `accessories_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_accessories_allocated_item` FOREIGN KEY (`allocated_item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_assignments_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assignments_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assign_device_requests`
--
ALTER TABLE `assign_device_requests`
  ADD CONSTRAINT `assign_device_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assign_device_requests_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  ADD CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_register` (`id`),
  ADD CONSTRAINT `bills_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bills_ibfk_4` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in` (`id`);

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`),
  ADD CONSTRAINT `bill_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `bill_payments`
--
ALTER TABLE `bill_payments`
  ADD CONSTRAINT `bill_payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`),
  ADD CONSTRAINT `bill_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `cash_register`
--
ALTER TABLE `cash_register`
  ADD CONSTRAINT `cash_register_ibfk_1` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `cash_register_transactions`
--
ALTER TABLE `cash_register_transactions`
  ADD CONSTRAINT `cash_register_transactions_ibfk_1` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_register` (`id`),
  ADD CONSTRAINT `cash_register_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `damaged_items`
--
ALTER TABLE `damaged_items`
  ADD CONSTRAINT `damaged_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `damaged_items_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `damaged_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `damages`
--
ALTER TABLE `damages`
  ADD CONSTRAINT `damages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damages_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damages_ibfk_3` FOREIGN KEY (`replacement_item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damages_ibfk_4` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damages_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damages_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `device_assignment_items`
--
ALTER TABLE `device_assignment_items`
  ADD CONSTRAINT `device_assignment_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `device_assignment_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_assignment_items_ibfk_2` FOREIGN KEY (`allocated_item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `device_assignment_requests`
--
ALTER TABLE `device_assignment_requests`
  ADD CONSTRAINT `device_assignment_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `device_assignment_requests_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `device_assignment_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `device_ownership_verifications`
--
ALTER TABLE `device_ownership_verifications`
  ADD CONSTRAINT `device_ownership_verifications_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `unlisted_device_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_ownership_verifications_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctor_purchases`
--
ALTER TABLE `doctor_purchases`
  ADD CONSTRAINT `doctor_purchases_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `doctor_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `item_types` (`id`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `items_ibfk_3` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `items_ibfk_4` FOREIGN KEY (`sub_category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `items_ibfk_5` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `items_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `item_price_history`
--
ALTER TABLE `item_price_history`
  ADD CONSTRAINT `item_price_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_price_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `item_serial_numbers`
--
ALTER TABLE `item_serial_numbers`
  ADD CONSTRAINT `fk_item_serial_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `item_types`
--
ALTER TABLE `item_types`
  ADD CONSTRAINT `fk_item_types_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `item_types_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `item_vendors`
--
ALTER TABLE `item_vendors`
  ADD CONSTRAINT `item_vendors_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `item_vendors_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `licenses`
--
ALTER TABLE `licenses`
  ADD CONSTRAINT `licenses_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `license_history`
--
ALTER TABLE `license_history`
  ADD CONSTRAINT `license_history_ibfk_1` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_purchase_details`
--
ALTER TABLE `monthly_purchase_details`
  ADD CONSTRAINT `monthly_purchase_details_ibfk_1` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_register` (`id`),
  ADD CONSTRAINT `monthly_purchase_details_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`),
  ADD CONSTRAINT `monthly_purchase_details_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `payment_acknowledgements`
--
ALTER TABLE `payment_acknowledgements`
  ADD CONSTRAINT `payment_acknowledgements_ibfk_1` FOREIGN KEY (`payment_slip_id`) REFERENCES `payment_slips` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_acknowledgements_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  ADD CONSTRAINT `payment_acknowledgements_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payment_slips`
--
ALTER TABLE `payment_slips`
  ADD CONSTRAINT `payment_slips_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_slips_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  ADD CONSTRAINT `payment_slips_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `replacement_requests`
--
ALTER TABLE `replacement_requests`
  ADD CONSTRAINT `replacement_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `replacement_requests_ibfk_2` FOREIGN KEY (`faulty_device_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `replacement_requests_ibfk_3` FOREIGN KEY (`replacement_device_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_requests_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_assignments`
--
ALTER TABLE `request_assignments`
  ADD CONSTRAINT `fk_request_assignments_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_request_assignments_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `fk_request_assignments_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_request_assignments_user` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD CONSTRAINT `request_attachments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_comments`
--
ALTER TABLE `request_comments`
  ADD CONSTRAINT `request_comments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_comments_ibfk_2` FOREIGN KEY (`commented_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_documents`
--
ALTER TABLE `request_documents`
  ADD CONSTRAINT `request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`);

--
-- Constraints for table `return_approvals`
--
ALTER TABLE `return_approvals`
  ADD CONSTRAINT `fk_return_approvals_request` FOREIGN KEY (`return_request_id`) REFERENCES `return_device_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_approvals_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `return_device_requests`
--
ALTER TABLE `return_device_requests`
  ADD CONSTRAINT `return_device_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_device_requests_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`);

--
-- Constraints for table `return_stock_history`
--
ALTER TABLE `return_stock_history`
  ADD CONSTRAINT `fk_return_stock_history_item` FOREIGN KEY (`return_device_item_id`) REFERENCES `return_device_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_stock_history_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `software_access_requests`
--
ALTER TABLE `software_access_requests`
  ADD CONSTRAINT `fk_software_license` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`),
  ADD CONSTRAINT `software_access_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD CONSTRAINT `stock_in_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  ADD CONSTRAINT `stock_in_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_in_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_in_ibfk_4` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`);

--
-- Constraints for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  ADD CONSTRAINT `stock_in_items_ibfk_1` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_in_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `stock_replacements`
--
ALTER TABLE `stock_replacements`
  ADD CONSTRAINT `stock_replacements_ibfk_1` FOREIGN KEY (`old_item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_replacements_ibfk_2` FOREIGN KEY (`new_item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_replacements_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  ADD CONSTRAINT `stock_replacements_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_returns`
--
ALTER TABLE `stock_returns`
  ADD CONSTRAINT `stock_returns_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `stock_returns_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `stock_returns_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_returns_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  ADD CONSTRAINT `submission_attachments_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `unlisted_device_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_attachments_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `submission_devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_attachments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `submission_devices`
--
ALTER TABLE `submission_devices`
  ADD CONSTRAINT `fk_submission_devices_new_item` FOREIGN KEY (`new_item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `submission_devices_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `unlisted_device_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_devices_ibfk_2` FOREIGN KEY (`item_type_id`) REFERENCES `item_types` (`id`),
  ADD CONSTRAINT `submission_devices_ibfk_3` FOREIGN KEY (`new_item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `submission_devices_ibfk_4` FOREIGN KEY (`final_category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `submission_devices_ibfk_5` FOREIGN KEY (`final_sub_category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `submission_devices_ibfk_6` FOREIGN KEY (`final_brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `submission_devices_ibfk_7` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `user_tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `technical_support_requests`
--
ALTER TABLE `technical_support_requests`
  ADD CONSTRAINT `fk_tech_support_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `technical_support_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unlisted_device_submissions`
--
ALTER TABLE `unlisted_device_submissions`
  ADD CONSTRAINT `unlisted_device_submissions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `unlisted_device_submissions_ibfk_2` FOREIGN KEY (`new_item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `unlisted_device_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `update_device_requests`
--
ALTER TABLE `update_device_requests`
  ADD CONSTRAINT `update_device_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `update_device_requests_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD CONSTRAINT `fk_upgrade_new_item` FOREIGN KEY (`new_item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `upgrade_requests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `upgrade_requests_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`);

--
-- Constraints for table `user_tasks`
--
ALTER TABLE `user_tasks`
  ADD CONSTRAINT `user_tasks_ibfk_1` FOREIGN KEY (`assigned_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_tasks_ibfk_2` FOREIGN KEY (`assigned_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_tasks_ibfk_3` FOREIGN KEY (`related_license_id`) REFERENCES `licenses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_tasks_ibfk_4` FOREIGN KEY (`related_warranty_id`) REFERENCES `warranties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_tasks_ibfk_5` FOREIGN KEY (`parent_task_id`) REFERENCES `user_tasks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `warranties`
--
ALTER TABLE `warranties`
  ADD CONSTRAINT `warranties_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD CONSTRAINT `warranty_claims_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD CONSTRAINT `warranty_history_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
