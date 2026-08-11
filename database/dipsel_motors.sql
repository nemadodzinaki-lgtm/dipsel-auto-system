-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2026 at 03:09 PM
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
-- Database: `dipsel_motors`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `admin_number` varchar(30) NOT NULL,
  `admin_level` enum('SuperAdmin','Admin') NOT NULL DEFAULT 'Admin',
  `can_manage_users` tinyint(1) DEFAULT 1,
  `can_manage_vehicles` tinyint(1) DEFAULT 1,
  `can_manage_workshop` tinyint(1) DEFAULT 1,
  `can_manage_inventory` tinyint(1) DEFAULT 1,
  `can_manage_finance` tinyint(1) DEFAULT 1,
  `can_manage_reports` tinyint(1) DEFAULT 1,
  `can_manage_settings` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `person_id`, `admin_number`, `admin_level`, `can_manage_users`, `can_manage_vehicles`, `can_manage_workshop`, `can_manage_inventory`, `can_manage_finance`, `can_manage_reports`, `can_manage_settings`, `created_at`, `updated_at`) VALUES
(1, 1, 'ADM000001', 'SuperAdmin', 1, 1, 1, 1, 1, 1, 1, '2026-07-13 13:07:12', '2026-07-13 13:08:19');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `posted_by_employee_id` int(11) DEFAULT NULL,
  `audience` enum('All','Employees','Customers','Admins') DEFAULT 'All',
  `publish_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `appointment_type` enum('Vehicle Viewing','Test Drive','Workshop','Sales Consultation','General') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled','No Show') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `employee_id`, `vehicle_id`, `appointment_type`, `appointment_date`, `appointment_time`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 2, 'Vehicle Viewing', '2026-07-24', '16:03:00', 'Confirmed', '', '2026-07-23 13:59:53', '2026-07-28 09:30:32');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent','Leave') DEFAULT 'Present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_log_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `customer_number` varchar(30) NOT NULL,
  `driver_license` varchar(30) DEFAULT NULL,
  `preferred_contact` enum('Phone','Email','WhatsApp') DEFAULT 'Phone',
  `marketing_consent` tinyint(1) DEFAULT 1,
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `person_id`, `customer_number`, `driver_license`, `preferred_contact`, `marketing_consent`, `loyalty_points`, `created_at`, `updated_at`) VALUES
(1, 2, 'CUS000001', 'code 10', 'Email', 0, 0, '2026-07-13 13:02:35', '2026-07-13 13:02:35');

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `feedback_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `service_booking_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `feedback` text DEFAULT NULL,
  `feedback_type` enum('Vehicle','Workshop','Sales','Employee','General') DEFAULT 'General',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_vehicle_submissions`
--

CREATE TABLE `customer_vehicle_submissions` (
  `submission_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `make` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `variant` varchar(100) DEFAULT NULL,
  `manufacture_year` year(4) NOT NULL,
  `mileage` int(11) DEFAULT 0,
  `colour` varchar(50) DEFAULT NULL,
  `body_type` enum('Sedan','Hatchback','SUV','Coupe','Convertible','Wagon','Van','Pickup','Truck','Other') NOT NULL,
  `fuel_type` enum('Petrol','Diesel','Hybrid','Electric','Plug-in Hybrid','Other') NOT NULL,
  `transmission` enum('Manual','Automatic','CVT','Semi-Automatic','Other') NOT NULL,
  `drivetrain` enum('FWD','RWD','AWD','4WD') DEFAULT 'FWD',
  `engine_size` varchar(20) DEFAULT NULL,
  `engine_number` varchar(50) DEFAULT NULL,
  `doors` tinyint(4) DEFAULT 4,
  `seats` tinyint(4) DEFAULT 5,
  `condition_type` enum('New','Used','Demo') NOT NULL,
  `service_history` enum('Full','Partial','None') DEFAULT 'None',
  `accident_history` enum('No','Yes') DEFAULT 'No',
  `damage_status` enum('None','Minor','Moderate','Major','Write-off') DEFAULT 'None',
  `damage_description` text DEFAULT NULL,
  `roadworthy` enum('Yes','No') DEFAULT 'Yes',
  `warranty` enum('Yes','No') DEFAULT 'No',
  `description` text DEFAULT NULL,
  `vin` varchar(50) DEFAULT NULL,
  `registration_number` varchar(30) DEFAULT NULL,
  `asking_price` decimal(12,2) NOT NULL,
  `inspection_type` enum('dealership','on_site') NOT NULL DEFAULT 'dealership',
  `inspection_scheduled_date` datetime DEFAULT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `inspection_notes` text DEFAULT NULL,
  `offer_price` decimal(12,2) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','under_review','inspection_scheduled','inspection_completed','offer_made','accepted','rejected','purchased') NOT NULL DEFAULT 'pending',
  `decision_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_vehicle_submissions`
--

INSERT INTO `customer_vehicle_submissions` (`submission_id`, `customer_id`, `submission_date`, `make`, `model`, `variant`, `manufacture_year`, `mileage`, `colour`, `body_type`, `fuel_type`, `transmission`, `drivetrain`, `engine_size`, `engine_number`, `doors`, `seats`, `condition_type`, `service_history`, `accident_history`, `damage_status`, `damage_description`, `roadworthy`, `warranty`, `description`, `vin`, `registration_number`, `asking_price`, `inspection_type`, `inspection_scheduled_date`, `inspector_id`, `inspection_notes`, `offer_price`, `purchase_price`, `status`, `decision_date`, `notes`, `vehicle_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-07-22 05:43:29', 'Maza', 'CX5', NULL, '2023', 130, 'Gray', 'Sedan', 'Petrol', 'Manual', 'FWD', NULL, NULL, 4, 5, 'Used', 'Full', 'No', 'None', 'none', 'Yes', 'No', NULL, '345trd', NULL, 0.00, 'dealership', '2026-07-24 10:46:00', NULL, NULL, 80000.00, NULL, 'accepted', '2026-07-29 08:22:29', NULL, NULL, '2026-07-22 05:43:29', '2026-07-29 08:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_employee_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `employee_number` varchar(30) NOT NULL,
  `department` enum('Administration','Sales','Workshop','Marketing','Customer Support','Management') NOT NULL,
  `position` varchar(100) NOT NULL,
  `employment_type` enum('Permanent','Contract','Temporary','Intern') DEFAULT 'Permanent',
  `hire_date` date NOT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `employee_status` enum('Active','On Leave','Suspended','Resigned','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `person_id`, `employee_number`, `department`, `position`, `employment_type`, `hire_date`, `salary`, `supervisor_id`, `employee_status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, 'EMP2026-0001', 'Workshop', 'Manager', 'Permanent', '2026-07-01', 15000.00, NULL, 'Active', '2026-07-13 14:34:33', '2026-07-21 12:58:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_name` varchar(150) NOT NULL,
  `document_type` enum('ID Copy','Passport','CV','Employment Contract','Qualification','Driver License','Medical Certificate','Other') DEFAULT 'Other',
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `recorded_by_employee_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `payment_method` enum('Cash','Card','EFT','Bank Transfer') DEFAULT 'EFT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homepage_sliders`
--

CREATE TABLE `homepage_sliders` (
  `slider_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT 'View Vehicles',
  `button_link` varchar(255) DEFAULT '#',
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 1,
  `is_active` enum('Yes','No') DEFAULT 'Yes',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage_sliders`
--

INSERT INTO `homepage_sliders` (`slider_id`, `title`, `subtitle`, `button_text`, `button_link`, `image_path`, `display_order`, `is_active`, `start_date`, `end_date`, `created_by`, `created_at`, `updated_at`) VALUES
(9, 'Home', '', 'Home', 'index.php', 'uploads/sliders/slider_1784648282_6a5f925a35a72.jpg', 0, 'Yes', NULL, NULL, 1, '2026-07-21 15:38:02', '2026-07-21 15:45:24'),
(10, 'Log In', '', 'Log In', 'login.php', 'uploads/sliders/slider_1784648321_6a5f928180233.jpg', 0, 'Yes', NULL, NULL, 1, '2026-07-21 15:38:41', '2026-07-21 15:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_claims`
--

CREATE TABLE `insurance_claims` (
  `claim_id` int(11) NOT NULL,
  `insurance_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `claim_number` varchar(100) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `claim_amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `claim_status` enum('Submitted','Under Review','Approved','Rejected','Paid') DEFAULT 'Submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_providers`
--

CREATE TABLE `insurance_providers` (
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_providers`
--

INSERT INTO `insurance_providers` (`provider_id`, `provider_name`, `contact_person`, `phone`, `email`, `address`, `website`, `status`) VALUES
(1, 'DipselInsuarance', 'Nakisani', '0714479706', 'demo@mzansi.co.za', '1276 Marula Drive, Noordgesig, Soweto, South Africa', 'dipselproperties.co.za', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `part_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `part_number` varchar(50) NOT NULL,
  `part_name` varchar(200) NOT NULL,
  `category` enum('Engine','Transmission','Brake','Suspension','Electrical','Battery','Tyre','Body','Oil','Filter','Accessory','Other') DEFAULT 'Other',
  `description` text DEFAULT NULL,
  `quantity_in_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 5,
  `purchase_price` decimal(12,2) DEFAULT 0.00,
  `selling_price` decimal(12,2) DEFAULT 0.00,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('Available','Out of Stock','Discontinued') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_cards`
--

CREATE TABLE `job_cards` (
  `job_card_id` int(11) NOT NULL,
  `job_card_number` varchar(30) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `mechanic_employee_id` int(11) DEFAULT NULL,
  `service_advisor_employee_id` int(11) DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `fuel_level` enum('Empty','1/4','1/2','3/4','Full') DEFAULT '1/2',
  `vehicle_condition` text DEFAULT NULL,
  `customer_complaint` text NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `work_performed` text DEFAULT NULL,
  `labour_hours` decimal(5,2) DEFAULT 0.00,
  `parts_cost` decimal(12,2) DEFAULT 0.00,
  `labour_cost` decimal(12,2) DEFAULT 0.00,
  `total_cost` decimal(12,2) DEFAULT 0.00,
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `status` enum('Open','Diagnosing','Waiting for Parts','Repairing','Quality Check','Completed','Collected','Cancelled') DEFAULT 'Open',
  `opened_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `customer_signature` varchar(255) DEFAULT NULL,
  `advisor_notes` text DEFAULT NULL,
  `mechanic_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_cards`
--

INSERT INTO `job_cards` (`job_card_id`, `job_card_number`, `booking_id`, `vehicle_id`, `customer_id`, `mechanic_employee_id`, `service_advisor_employee_id`, `odometer_reading`, `fuel_level`, `vehicle_condition`, `customer_complaint`, `diagnosis`, `work_performed`, `labour_hours`, `parts_cost`, `labour_cost`, `total_cost`, `priority`, `status`, `opened_at`, `completed_at`, `customer_signature`, `advisor_notes`, `mechanic_notes`, `created_at`, `updated_at`) VALUES
(1, 'JC20260721-8501', 1, NULL, 1, 1, 1, 0, '1/2', 'good', 'Nothing', NULL, NULL, 0.00, 0.00, 0.00, 0.00, 'Normal', 'Open', '2026-07-21 12:45:06', NULL, NULL, 'nothing', 'nothing', '2026-07-21 10:45:06', '2026-07-21 10:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `job_card_parts`
--

CREATE TABLE `job_card_parts` (
  `job_card_part_id` int(11) NOT NULL,
  `job_card_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `installed_by_employee_id` int(11) DEFAULT NULL,
  `installed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `balance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Annual','Sick','Family Responsibility','Maternity','Paternity','Other') NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `used_days` decimal(5,2) DEFAULT 0.00,
  `remaining_days` decimal(5,2) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED,
  `year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `request_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Annual','Sick','Family Responsibility','Maternity','Paternity') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`request_id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `status`, `reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'Annual', '2026-07-31', '2026-08-07', 'Approved', 'Resting', '2026-07-21 09:44:35', '2026-07-21 12:54:28');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_person_id` int(11) NOT NULL,
  `receiver_person_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` enum('Yes','No') DEFAULT 'No',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_person_id`, `receiver_person_id`, `vehicle_id`, `subject`, `message`, `is_read`, `sent_at`) VALUES
(1, 1, 2, 1, 'The Avanza', 'This one is a nice car we can give u a discount Mr Nemadodzi', 'Yes', '2026-07-17 07:48:54'),
(2, 2, 1, NULL, 'Re: The Avanza', 'Thanks', 'Yes', '2026-07-17 08:25:18'),
(3, 1, 2, NULL, '', 'hy morning', 'Yes', '2026-07-22 06:30:55');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('System','Vehicle','Booking','Workshop','Sales','Reminder') DEFAULT 'System',
  `is_read` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Card','EFT','Bank Transfer','Finance') DEFAULT 'EFT',
  `payment_status` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `receipt_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `issued_by_employee_id` int(11) DEFAULT NULL,
  `issued_date` datetime DEFAULT current_timestamp(),
  `receipt_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `person_id` int(11) NOT NULL,
  `uuid` char(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `passport_number` varchar(30) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'South Africa',
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `role` enum('Customer','Employee','Admin','SuperAdmin') NOT NULL DEFAULT 'Customer',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `persons`
--

INSERT INTO `persons` (`person_id`, `uuid`, `first_name`, `middle_name`, `last_name`, `gender`, `date_of_birth`, `id_number`, `passport_number`, `phone`, `email`, `password`, `profile_photo`, `country`, `province`, `city`, `address`, `postal_code`, `role`, `status`, `email_verified`, `last_login`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'ed632b46-7eba-11f1-b264-d8541d948f12', 'Malakza', '', 'Madodzi', 'Male', '1990-01-01', '9001015009087', NULL, '0712345678', 'admin@dipselmotors.co.za', '$2y$10$fXJEgdxGQxQuEo7hkbgx9eYkGGCJMjcEWydwtZuhdtjdczoLDSL3e', 'default.png', 'South Africa', 'Gauteng', 'Johannesburg', 'Head Office', '2000', 'Admin', 'Active', 1, '2026-07-29 14:00:01', '2026-07-13 13:01:13', '2026-07-29 12:00:01', NULL, NULL),
(2, '23cae367-ef81-4756-bdd7-bd444ebdb1fb', 'Nakisani', '', 'Nemadodzi', 'Male', '2003-06-27', '0306276059087', '', '0714479706', 'nemadodzinaki@gmail.com', '$2y$10$fgjVw2K0UpvZOmYC6f7xeuvOBXIDnSdB53OAzdxo32N2KPfcjNCLC', '6a54e1eb21b94.png', 'South Africa', 'Eastern Cape', 'Johannesburg', '1276 Marula Drive, Noordgesig, Soweto, South Africa', '1804', 'Customer', 'Active', 0, '2026-07-29 10:49:23', '2026-07-13 13:02:35', '2026-07-29 08:49:23', NULL, NULL),
(4, 'a099863169529382fd19d7644e036d04', 'Rikhudengae', '', 'Nethamba', 'Male', '2005-02-05', '0206276059087', '', '0714479706', 'nemadodzinaki+1@gmail.com', '$2y$10$BhRo0E/dO2ZxiaOJW1YUt.AjFaAyymY/mgqFQuF43V.5lVlMuzIpS', NULL, 'South Africa', 'Eastern cape', 'Eastlondon', '1276 Marula Drive, Noordgesig, Soweto, South Africa', '1804', 'Employee', 'Active', 0, '2026-07-29 10:23:36', '2026-07-13 14:34:33', '2026-07-29 08:23:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `purchase_order_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `ordered_by_employee_id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('Pending','Ordered','Received','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `item_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_vehicles`
--

CREATE TABLE `saved_vehicles` (
  `saved_vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_vehicles`
--

INSERT INTO `saved_vehicles` (`saved_vehicle_id`, `customer_id`, `vehicle_id`, `saved_at`) VALUES
(7, 1, 2, '2026-07-22 06:14:00'),
(8, 1, 1, '2026-07-22 06:14:07');

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(30) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `workshop_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `assigned_employee_id` int(11) DEFAULT NULL,
  `vehicle_name` varchar(150) NOT NULL,
  `vehicle_model` varchar(150) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `vehicle_mileage` int(11) DEFAULT NULL,
  `customer_complaint` text DEFAULT NULL,
  `estimated_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `booking_status` enum('Pending','Confirmed','In Progress','Waiting for Parts','Completed','Cancelled') DEFAULT 'Pending',
  `total_estimated_cost` decimal(12,2) DEFAULT 0.00,
  `total_actual_cost` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`booking_id`, `booking_reference`, `customer_id`, `vehicle_id`, `workshop_id`, `service_id`, `assigned_employee_id`, `vehicle_name`, `vehicle_model`, `registration_number`, `booking_date`, `booking_time`, `vehicle_mileage`, `customer_complaint`, `estimated_completion_date`, `actual_completion_date`, `booking_status`, `total_estimated_cost`, `total_actual_cost`, `created_at`, `updated_at`) VALUES
(1, 'BK20260720F2F3C9', 1, NULL, 4, 1, 1, 'Toyota', 'Starlet', 'TRT 21 GP', '2026-07-20', '12:00:00', 15105, 'Nothing', NULL, NULL, 'Completed', 150.00, 0.00, '2026-07-20 09:19:59', '2026-07-21 10:58:53');

-- --------------------------------------------------------

--
-- Table structure for table `service_updates`
--

CREATE TABLE `service_updates` (
  `update_id` int(11) NOT NULL,
  `job_card_id` int(11) NOT NULL,
  `updated_by_employee_id` int(11) NOT NULL,
  `update_status` enum('Vehicle Received','Inspection','Diagnosis Complete','Waiting for Approval','Waiting for Parts','Repair Started','Repair Completed','Quality Check','Ready for Collection') NOT NULL,
  `comments` text DEFAULT NULL,
  `notify_customer` enum('Yes','No') DEFAULT 'Yes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_updates`
--

INSERT INTO `service_updates` (`update_id`, `job_card_id`, `updated_by_employee_id`, `update_status`, `comments`, `notify_customer`, `created_at`) VALUES
(1, 1, 1, '', 'We are done', 'Yes', '2026-07-21 10:53:48'),
(2, 1, 1, '', '', 'Yes', '2026-07-21 11:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'company_name', 'Dipsel Auto'),
(2, 'logo_path', 'uploads/logos/logo_1785326419.png'),
(3, 'footer_about', 'Your trusted partner in buying and selling quality used cars.'),
(4, 'footer_email', 'Dipselmotors@outlook.com'),
(5, 'footer_phone', '+27 73 913 1020'),
(6, 'footer_address', '123 Main Street, Johannesburg, South Africa'),
(7, 'facebook_url', '#'),
(8, 'twitter_url', '#'),
(9, 'instagram_url', '#'),
(10, 'tiktok_url', '#'),
(11, 'company_email', ''),
(12, 'company_phone', ''),
(13, 'company_address', ''),
(14, 'currency', 'ZAR'),
(15, 'timezone', 'Africa/Johannesburg'),
(16, 'maintenance_mode', '0'),
(17, 'allow_registration', '1'),
(18, 'vat_percentage', '15'),
(19, 'theme', 'light'),
(20, 'language', 'English'),
(21, 'copyright_text', '© 2026 AutoMarket. All rights reserved.');

-- --------------------------------------------------------

--
-- Table structure for table `submission_images`
--

CREATE TABLE `submission_images` (
  `image_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_title` varchar(100) DEFAULT NULL,
  `image_type` enum('Front','Rear','Left Side','Right Side','Interior','Engine','Boot','Roof','Other') DEFAULT 'Other',
  `is_primary` enum('Yes','No') DEFAULT 'No',
  `display_order` int(11) DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_images`
--

INSERT INTO `submission_images` (`image_id`, `submission_id`, `image_path`, `image_title`, `image_type`, `is_primary`, `display_order`, `uploaded_at`) VALUES
(1, 1, 'uploads/submissions/sub_1784699009_15695cbcfbdabe96.jpeg', NULL, 'Other', 'Yes', 1, '2026-07-22 05:43:29'),
(2, 1, 'uploads/submissions/sub_1784699009_868ade2b77ef8990.jpeg', NULL, 'Other', 'No', 2, '2026-07-22 05:43:29'),
(3, 1, 'uploads/submissions/sub_1784699009_3fb2df1b988171f9.jpeg', NULL, 'Other', 'No', 3, '2026-07-22 05:43:29'),
(4, 1, 'uploads/submissions/sub_1784699009_c7b15c1c8d34c8ab.jpeg', NULL, 'Other', 'No', 4, '2026-07-22 05:43:29'),
(5, 1, 'uploads/submissions/sub_1784699009_d62654f6ae350d70.jpeg', NULL, 'Other', 'No', 5, '2026-07-22 05:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `city`, `website`, `tax_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Molemo', '0712315464', '0714479706', 'demo@mzansi.co.za', '1276 Marula Drive, Noordgesig, Soweto, South Africa', 'Johannesburg', 'https://www.dipselproperties.co.za/', '5567890', 'Active', '2026-07-13 15:11:36', '2026-07-14 15:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `seller_person_id` int(11) NOT NULL,
  `vehicle_source` enum('Dealership','Customer','Trade-In','Consignment') NOT NULL DEFAULT 'Dealership',
  `current_owner_person_id` int(11) DEFAULT NULL,
  `current_location` enum('Showroom','Workshop','Storage','Delivered','Customer') NOT NULL DEFAULT 'Showroom',
  `advertised` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `available_for_sale` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `acquisition_type` enum('Purchased','Trade-In','Consignment','Customer Vehicle') DEFAULT 'Purchased',
  `stock_number` varchar(30) NOT NULL,
  `vin` varchar(50) NOT NULL,
  `registration_number` varchar(30) DEFAULT NULL,
  `make` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `variant` varchar(100) DEFAULT NULL,
  `manufacture_year` year(4) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `mileage` int(11) DEFAULT 0,
  `colour` varchar(50) DEFAULT NULL,
  `body_type` enum('Sedan','Hatchback','SUV','Coupe','Convertible','Wagon','Single Cab','Double Cab','Van','Truck','Bus','Other') NOT NULL,
  `fuel_type` enum('Petrol','Diesel','Hybrid','Electric','Plug-in Hybrid','Other') NOT NULL,
  `transmission` enum('Manual','Automatic','CVT','Semi-Automatic') NOT NULL,
  `drivetrain` enum('FWD','RWD','AWD','4WD') DEFAULT 'FWD',
  `engine_size` varchar(20) DEFAULT NULL,
  `engine_number` varchar(50) DEFAULT NULL,
  `doors` tinyint(4) DEFAULT 4,
  `seats` tinyint(4) DEFAULT 5,
  `condition_type` enum('New','Used','Demo') NOT NULL,
  `service_history` enum('Full','Partial','None') DEFAULT 'None',
  `accident_history` enum('No','Yes') DEFAULT 'No',
  `damage_status` enum('None','Minor','Moderate','Major','Write-Off') DEFAULT 'None',
  `damage_description` text DEFAULT NULL,
  `roadworthy` enum('Yes','No') DEFAULT 'Yes',
  `warranty` enum('Yes','No') DEFAULT 'No',
  `description` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `featured` enum('Yes','No') DEFAULT 'No',
  `status` enum('Available','Reserved','Sold','Hidden') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `seller_person_id`, `vehicle_source`, `current_owner_person_id`, `current_location`, `advertised`, `available_for_sale`, `purchase_date`, `purchase_price`, `acquisition_type`, `stock_number`, `vin`, `registration_number`, `make`, `model`, `variant`, `manufacture_year`, `price`, `mileage`, `colour`, `body_type`, `fuel_type`, `transmission`, `drivetrain`, `engine_size`, `engine_number`, `doors`, `seats`, `condition_type`, `service_history`, `accident_history`, `damage_status`, `damage_description`, `roadworthy`, `warranty`, `description`, `views`, `featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dealership', 1, 'Showroom', 'Yes', 'Yes', NULL, NULL, 'Purchased', 'STK2026-0001', '543434', '', 'Toyota', 'Avanza', '', '2019', 120000.00, 10, 'blue', 'Sedan', 'Petrol', 'Automatic', 'FWD', '2.0L', '234fee3', 4, 7, '', 'Full', 'No', 'None', 'none', 'Yes', 'No', '', 0, 'Yes', 'Available', '2026-07-13 13:49:54', '2026-07-28 09:19:53'),
(2, 1, 'Dealership', 1, 'Showroom', '', 'Yes', NULL, NULL, '', 'STK2026-0002', '521', 'ARW 34 EC', 'VW', 'Citi Golf', '', '2011', 35000.00, 150000, 'black', 'Sedan', 'Petrol', 'Manual', 'FWD', '1.5L', '', 4, 4, '', 'Full', '', 'None', 'no damage', 'Yes', '', 'Black Citi golf in good condition', 0, 'No', 'Available', '2026-07-20 19:57:37', '2026-07-20 19:57:37'),
(4, 1, 'Dealership', 1, 'Showroom', 'Yes', 'Yes', NULL, NULL, 'Purchased', 'STK2026-0003', '8421', 'MN 27 GP', 'VW', 'Polo', '', '2019', 127000.00, 110000, 'Orange', 'Sedan', 'Petrol', 'Manual', 'FWD', '1.8L', '23012', 4, 5, '', 'Full', 'No', 'None', '', 'No', '', '', 0, 'Yes', 'Available', '2026-07-28 08:43:05', '2026-07-28 09:20:20');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `document_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `document_type` enum('Registration Certificate','Roadworthy Certificate','Service History','Warranty','Insurance','Finance Clearance','Police Clearance','License Disc','Purchase Invoice','Other') DEFAULT 'Other',
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Valid','Expired','Pending') DEFAULT 'Valid',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_documents`
--

INSERT INTO `vehicle_documents` (`document_id`, `vehicle_id`, `document_name`, `document_type`, `file_path`, `expiry_date`, `status`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, ',mnbvc', 'Registration Certificate', 'uploads/documents/doc_1783951986_7dbf9a47.pdf', '2026-07-14', 'Valid', 1, '2026-07-13 14:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_enquiries`
--

CREATE TABLE `vehicle_enquiries` (
  `enquiry_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `assigned_employee_id` int(11) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `enquiry_status` enum('New','In Progress','Responded','Closed') DEFAULT 'New',
  `response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_images`
--

CREATE TABLE `vehicle_images` (
  `image_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_title` varchar(100) DEFAULT NULL,
  `image_type` enum('Front','Rear','Left Side','Right Side','Interior','Dashboard','Engine','Boot','Tyres','Damage','Other') DEFAULT 'Other',
  `is_primary` enum('Yes','No') DEFAULT 'No',
  `display_order` int(11) DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_images`
--

INSERT INTO `vehicle_images` (`image_id`, `vehicle_id`, `image_path`, `image_title`, `image_type`, `is_primary`, `display_order`, `uploaded_at`) VALUES
(4, 2, 'uploads/vehicles/vehicle_1784577457_6d852a34db3c02ad.jpeg', NULL, 'Other', 'Yes', 0, '2026-07-20 19:57:37'),
(5, 2, 'uploads/vehicles/vehicle_1784577483_663382e77b73859f.jpeg', '', 'Front', 'No', 1, '2026-07-20 19:58:03'),
(6, 2, 'uploads/vehicles/vehicle_1784577497_9719c1041a4d09d4.jpeg', '', 'Front', 'No', 1, '2026-07-20 19:58:17'),
(7, 2, 'uploads/vehicles/vehicle_1784577512_bb855abab61e1379.jpeg', '', 'Front', 'No', 1, '2026-07-20 19:58:32'),
(8, 2, 'uploads/vehicles/vehicle_1784577543_eebff70b05bccaf2.jpeg', '', 'Front', 'No', 5, '2026-07-20 19:59:03'),
(9, 2, 'uploads/vehicles/vehicle_1784577563_a7bc2327851b8e4e.jpeg', '', 'Front', 'No', 5, '2026-07-20 19:59:23'),
(10, 2, 'uploads/vehicles/vehicle_1784577576_392e5698c81d74a6.jpeg', '', 'Front', 'No', 5, '2026-07-20 19:59:36'),
(11, 1, 'uploads/vehicles/vehicle_1784577990_0351e01797ba87d4.jpg', NULL, 'Other', 'Yes', 0, '2026-07-20 20:06:30'),
(12, 1, 'uploads/vehicles/vehicle_1784578018_48b7853b1c754243.jpg', '', 'Other', 'No', 1, '2026-07-20 20:06:58'),
(13, 1, 'uploads/vehicles/vehicle_1784578033_f6c8757334af301e.jpg', '', 'Other', 'No', 1, '2026-07-20 20:07:13'),
(14, 1, 'uploads/vehicles/vehicle_1784578044_3fc85dc0be135f76.jpg', '', 'Other', 'No', 1, '2026-07-20 20:07:24'),
(15, 4, 'uploads/vehicles/vehicle_1785228185_f7bd307a17944b8b.jpeg', NULL, 'Other', 'Yes', 0, '2026-07-28 08:43:05'),
(16, 4, 'uploads/vehicles/vehicle_1785228290_807246cf713422d8.jpeg', '', 'Other', 'No', 1, '2026-07-28 08:44:50'),
(17, 4, 'uploads/vehicles/vehicle_1785228308_f1fd68aeb730daa6.jpeg', '', 'Other', 'No', 1, '2026-07-28 08:45:08'),
(18, 4, 'uploads/vehicles/vehicle_1785228328_d244be0d486e3c26.jpeg', '', 'Front', 'No', 1, '2026-07-28 08:45:28'),
(20, 4, 'uploads/vehicles/vehicle_1785228378_83ff89cc22f5770d.jpeg', '', 'Front', 'No', 1, '2026-07-28 08:46:18'),
(24, 4, 'uploads/vehicles/vehicle_1785228438_7366446e2e56cc65.jpeg', '', 'Front', 'No', 1, '2026-07-28 08:47:18');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_inspections`
--

CREATE TABLE `vehicle_inspections` (
  `inspection_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `inspected_by_employee_id` int(11) DEFAULT NULL,
  `inspection_date` date NOT NULL,
  `inspection_type` enum('Pre-Sale','Roadworthy','Workshop','Insurance','General') DEFAULT 'General',
  `odometer_reading` int(11) DEFAULT NULL,
  `overall_condition` enum('Excellent','Good','Fair','Poor') DEFAULT 'Good',
  `brakes` enum('Pass','Fail') DEFAULT 'Pass',
  `tyres` enum('Pass','Fail') DEFAULT 'Pass',
  `suspension` enum('Pass','Fail') DEFAULT 'Pass',
  `engine` enum('Pass','Fail') DEFAULT 'Pass',
  `transmission` enum('Pass','Fail') DEFAULT 'Pass',
  `electrical` enum('Pass','Fail') DEFAULT 'Pass',
  `notes` text DEFAULT NULL,
  `next_inspection_due` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_inspections`
--

INSERT INTO `vehicle_inspections` (`inspection_id`, `vehicle_id`, `inspected_by_employee_id`, `inspection_date`, `inspection_type`, `odometer_reading`, `overall_condition`, `brakes`, `tyres`, `suspension`, `engine`, `transmission`, `electrical`, `notes`, `next_inspection_due`, `created_at`) VALUES
(1, 1, NULL, '2026-07-23', 'Pre-Sale', 0, 'Excellent', 'Pass', 'Pass', 'Pass', 'Pass', 'Pass', 'Pass', '', '2026-07-30', '2026-07-23 08:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_insurance`
--

CREATE TABLE `vehicle_insurance` (
  `insurance_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `policy_number` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `premium_amount` decimal(12,2) DEFAULT NULL,
  `coverage_type` enum('Comprehensive','Third Party','Third Party Fire & Theft') DEFAULT 'Comprehensive',
  `status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_maintenance_history`
--

CREATE TABLE `vehicle_maintenance_history` (
  `maintenance_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `job_card_id` int(11) DEFAULT NULL,
  `service_booking_id` int(11) DEFAULT NULL,
  `serviced_by_employee_id` int(11) DEFAULT NULL,
  `maintenance_date` date NOT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `maintenance_type` enum('Minor Service','Major Service','Repair','Inspection','Oil Change','Tyre Replacement','Brake Service','Battery Replacement','Other') DEFAULT 'Other',
  `description` text DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT 0.00,
  `next_service_due` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_owners`
--

CREATE TABLE `vehicle_owners` (
  `owner_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `ownership_type` enum('Current','Previous') DEFAULT 'Current',
  `purchase_date` date DEFAULT NULL,
  `selling_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `ownership_status` enum('Active','Transferred') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_registrations`
--

CREATE TABLE `vehicle_registrations` (
  `registration_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `licence_disc_number` varchar(50) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `registering_authority` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `status` enum('Active','Expired','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_sales`
--

CREATE TABLE `vehicle_sales` (
  `sale_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sale_reference` varchar(30) NOT NULL,
  `sale_price` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('Cash','EFT','Bank Transfer','Card','Finance','Other') DEFAULT 'EFT',
  `payment_status` enum('Pending','Partial','Paid','Cancelled') DEFAULT 'Pending',
  `sale_status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `sale_date` datetime NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_status` enum('Pending','Ready','Delivered') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_test_drives`
--

CREATE TABLE `vehicle_test_drives` (
  `test_drive_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `viewing_id` int(11) DEFAULT NULL,
  `test_drive_date` date NOT NULL,
  `test_drive_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `drivers_license_verified` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `status` enum('Pending','Approved','Completed','Cancelled','No Show') DEFAULT 'Pending',
  `customer_notes` text DEFAULT NULL,
  `employee_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_viewings`
--

CREATE TABLE `vehicle_viewings` (
  `viewing_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `viewing_date` date NOT NULL,
  `viewing_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled','No Show') DEFAULT 'Pending',
  `customer_notes` text DEFAULT NULL,
  `employee_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_viewings`
--

INSERT INTO `vehicle_viewings` (`viewing_id`, `vehicle_id`, `customer_id`, `employee_id`, `viewing_date`, `viewing_time`, `location`, `status`, `customer_notes`, `employee_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, '2026-07-16', '18:29:32', 'Online', 'Completed', NULL, NULL, '2026-07-16 16:29:32', '2026-07-16 16:29:32'),
(2, 1, 1, NULL, '2026-07-20', '14:32:20', 'Online', 'Completed', NULL, NULL, '2026-07-20 12:32:20', '2026-07-20 12:32:20'),
(3, 1, 1, NULL, '2026-07-20', '14:44:15', 'Online', 'Completed', NULL, NULL, '2026-07-20 12:44:15', '2026-07-20 12:44:15'),
(4, 1, 1, NULL, '2026-07-20', '14:46:03', 'Online', 'Completed', NULL, NULL, '2026-07-20 12:46:03', '2026-07-20 12:46:03'),
(5, 1, 1, NULL, '2026-07-20', '14:49:53', 'Online', 'Completed', NULL, NULL, '2026-07-20 12:49:53', '2026-07-20 12:49:53'),
(6, 1, 1, NULL, '2026-07-20', '14:55:28', 'Online', 'Completed', NULL, NULL, '2026-07-20 12:55:28', '2026-07-20 12:55:28'),
(7, 1, 1, NULL, '2026-07-20', '15:26:29', 'Online', 'Completed', NULL, NULL, '2026-07-20 13:26:29', '2026-07-20 13:26:29'),
(8, 1, 1, NULL, '2026-07-20', '15:43:09', 'Online', 'Completed', NULL, NULL, '2026-07-20 13:43:09', '2026-07-20 13:43:09'),
(9, 1, 1, NULL, '2026-07-20', '15:46:46', 'Online', 'Completed', NULL, NULL, '2026-07-20 13:46:46', '2026-07-20 13:46:46'),
(10, 1, 1, NULL, '2026-07-20', '15:57:22', 'Online', 'Completed', NULL, NULL, '2026-07-20 13:57:22', '2026-07-20 13:57:22'),
(11, 1, 1, NULL, '2026-07-20', '15:57:52', 'Online', 'Completed', NULL, NULL, '2026-07-20 13:57:52', '2026-07-20 13:57:52'),
(12, 1, 1, NULL, '2026-07-20', '17:09:35', 'Online', 'Completed', NULL, NULL, '2026-07-20 15:09:35', '2026-07-20 15:09:35'),
(13, 1, 1, NULL, '2026-07-20', '18:17:45', 'Online', 'Completed', NULL, NULL, '2026-07-20 16:17:45', '2026-07-20 16:17:45'),
(14, 2, 1, NULL, '2026-07-20', '22:01:19', 'Online', 'Completed', NULL, NULL, '2026-07-20 20:01:19', '2026-07-20 20:01:19'),
(15, 1, 1, NULL, '2026-07-20', '22:02:17', 'Online', 'Completed', NULL, NULL, '2026-07-20 20:02:17', '2026-07-20 20:02:17'),
(16, 2, 1, NULL, '2026-07-20', '22:14:01', 'Online', 'Completed', NULL, NULL, '2026-07-20 20:14:01', '2026-07-20 20:14:01'),
(17, 2, 1, NULL, '2026-07-22', '08:15:32', 'Online', 'Completed', NULL, NULL, '2026-07-22 06:15:32', '2026-07-22 06:15:32'),
(18, 2, 1, NULL, '2026-07-23', '13:06:11', 'Online', 'Completed', NULL, NULL, '2026-07-23 11:06:11', '2026-07-23 11:06:11'),
(19, 2, 1, NULL, '2026-07-23', '13:13:20', 'Online', 'Completed', NULL, NULL, '2026-07-23 11:13:20', '2026-07-23 11:13:20'),
(20, 2, 1, NULL, '2026-07-23', '15:44:12', 'Online', 'Completed', NULL, NULL, '2026-07-23 13:44:12', '2026-07-23 13:44:12'),
(21, 2, 1, NULL, '2026-07-23', '15:48:49', 'Online', 'Completed', NULL, NULL, '2026-07-23 13:48:49', '2026-07-23 13:48:49'),
(22, 2, 1, NULL, '2026-07-23', '15:59:28', 'Online', 'Completed', NULL, NULL, '2026-07-23 13:59:28', '2026-07-23 13:59:28'),
(23, 1, 1, NULL, '2026-07-23', '16:20:35', 'Online', 'Completed', NULL, NULL, '2026-07-23 14:20:35', '2026-07-23 14:20:35'),
(24, 2, 1, NULL, '2026-07-23', '16:27:23', 'Online', 'Completed', NULL, NULL, '2026-07-23 14:27:23', '2026-07-23 14:27:23'),
(25, 1, 1, NULL, '2026-07-23', '16:27:36', 'Online', 'Completed', NULL, NULL, '2026-07-23 14:27:36', '2026-07-23 14:27:36'),
(26, 1, 1, NULL, '2026-07-27', '10:01:01', 'Online', 'Completed', NULL, NULL, '2026-07-27 08:01:01', '2026-07-27 08:01:01'),
(27, 2, 1, NULL, '2026-07-27', '10:01:48', 'Online', 'Completed', NULL, NULL, '2026-07-27 08:01:48', '2026-07-27 08:01:48'),
(28, 2, 1, NULL, '2026-07-28', '09:57:14', 'Online', 'Completed', NULL, NULL, '2026-07-28 07:57:14', '2026-07-28 07:57:14'),
(29, 4, 1, NULL, '2026-07-28', '10:49:47', 'Online', 'Completed', NULL, NULL, '2026-07-28 08:49:47', '2026-07-28 08:49:47');

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `workshop_id` int(11) NOT NULL,
  `workshop_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `operating_hours` varchar(100) DEFAULT NULL,
  `status` enum('Open','Closed','Maintenance') DEFAULT 'Open',
  `manager_employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshops`
--

INSERT INTO `workshops` (`workshop_id`, `workshop_name`, `phone`, `email`, `address`, `city`, `province`, `operating_hours`, `status`, `manager_employee_id`, `created_at`, `updated_at`) VALUES
(4, 'MN', '0714479706', 'nemadodzinaki@gmail.com', '1276 Marula Drive, Noordgesig, Soweto, South Africa', 'Eastlondon', 'Eastern cape', 'Mon - Fridaty 08:00 - 17:30', 'Open', 1, '2026-07-13 14:45:03', '2026-07-13 14:45:03');

-- --------------------------------------------------------

--
-- Table structure for table `workshop_services`
--

CREATE TABLE `workshop_services` (
  `service_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `category` enum('General Service','Major Service','Engine','Transmission','Brakes','Suspension','Electrical','Tyres','Air Conditioning','Diagnostics','Body Repair','Painting','Battery','Other') DEFAULT 'General Service',
  `description` text DEFAULT NULL,
  `estimated_duration_hours` decimal(4,2) DEFAULT 1.00,
  `base_price` decimal(12,2) NOT NULL,
  `status` enum('Available','Unavailable') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshop_services`
--

INSERT INTO `workshop_services` (`service_id`, `workshop_id`, `service_name`, `category`, `description`, `estimated_duration_hours`, `base_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'Tire change', '', 'jhgfds', 3.00, 150.00, 'Available', '2026-07-13 14:45:47', '2026-07-13 14:45:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_number` (`admin_number`),
  ADD KEY `fk_admin_person` (`person_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_announcement_employee` (`posted_by_employee_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `fk_appointment_customer` (`customer_id`),
  ADD KEY `fk_appointment_employee` (`employee_id`),
  ADD KEY `fk_appointment_vehicle` (`vehicle_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`,`attendance_date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_log_id`),
  ADD KEY `fk_audit_person` (`person_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `person_id` (`person_id`),
  ADD UNIQUE KEY `customer_number` (`customer_number`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedback_customer` (`customer_id`),
  ADD KEY `fk_feedback_vehicle` (`vehicle_id`),
  ADD KEY `fk_feedback_booking` (`service_booking_id`);

--
-- Indexes for table `customer_vehicle_submissions`
--
ALTER TABLE `customer_vehicle_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_vehicle` (`vehicle_id`),
  ADD KEY `idx_inspector` (`inspector_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD KEY `fk_department_manager` (`manager_employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `person_id` (`person_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `fk_employee_supervisor` (`supervisor_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fk_employee_document` (`employee_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `fk_expense_category` (`category_id`),
  ADD KEY `fk_expense_employee` (`recorded_by_employee_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `homepage_sliders`
--
ALTER TABLE `homepage_sliders`
  ADD PRIMARY KEY (`slider_id`),
  ADD KEY `fk_slider_person` (`created_by`);

--
-- Indexes for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD UNIQUE KEY `claim_number` (`claim_number`),
  ADD KEY `fk_claim_insurance` (`insurance_id`),
  ADD KEY `fk_claim_customer` (`customer_id`);

--
-- Indexes for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  ADD PRIMARY KEY (`provider_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`part_id`),
  ADD UNIQUE KEY `part_number` (`part_number`),
  ADD KEY `fk_inventory_supplier` (`supplier_id`);

--
-- Indexes for table `job_cards`
--
ALTER TABLE `job_cards`
  ADD PRIMARY KEY (`job_card_id`),
  ADD UNIQUE KEY `job_card_number` (`job_card_number`),
  ADD KEY `fk_jobcard_booking` (`booking_id`),
  ADD KEY `fk_jobcard_vehicle` (`vehicle_id`),
  ADD KEY `fk_jobcard_customer` (`customer_id`),
  ADD KEY `fk_jobcard_mechanic` (`mechanic_employee_id`),
  ADD KEY `fk_jobcard_advisor` (`service_advisor_employee_id`);

--
-- Indexes for table `job_card_parts`
--
ALTER TABLE `job_card_parts`
  ADD PRIMARY KEY (`job_card_part_id`),
  ADD KEY `fk_jcp_jobcard` (`job_card_id`),
  ADD KEY `fk_jcp_part` (`part_id`),
  ADD KEY `fk_jcp_employee` (`installed_by_employee_id`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_sender` (`sender_person_id`),
  ADD KEY `fk_message_receiver` (`receiver_person_id`),
  ADD KEY `fk_message_vehicle` (`vehicle_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_person` (`person_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_sale` (`sale_id`),
  ADD KEY `fk_payment_booking` (`booking_id`),
  ADD KEY `fk_payment_customer` (`customer_id`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `fk_receipt_payment` (`payment_id`),
  ADD KEY `fk_receipt_employee` (`issued_by_employee_id`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`person_id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`purchase_order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_po_supplier` (`supplier_id`),
  ADD KEY `fk_po_employee` (`ordered_by_employee_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_poi_order` (`purchase_order_id`),
  ADD KEY `fk_poi_part` (`part_id`);

--
-- Indexes for table `saved_vehicles`
--
ALTER TABLE `saved_vehicles`
  ADD PRIMARY KEY (`saved_vehicle_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`vehicle_id`),
  ADD KEY `fk_saved_vehicle` (`vehicle_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `fk_service_booking_customer` (`customer_id`),
  ADD KEY `fk_service_booking_vehicle` (`vehicle_id`),
  ADD KEY `fk_service_booking_workshop` (`workshop_id`),
  ADD KEY `fk_service_booking_service` (`service_id`),
  ADD KEY `fk_service_booking_employee` (`assigned_employee_id`);

--
-- Indexes for table `service_updates`
--
ALTER TABLE `service_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `fk_service_update_jobcard` (`job_card_id`),
  ADD KEY `fk_service_update_employee` (`updated_by_employee_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `submission_images`
--
ALTER TABLE `submission_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_submission` (`submission_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `stock_number` (`stock_number`),
  ADD UNIQUE KEY `vin` (`vin`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `fk_vehicle_seller` (`seller_person_id`),
  ADD KEY `fk_vehicle_owner` (`current_owner_person_id`);

--
-- Indexes for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fk_vehicle_documents_vehicle` (`vehicle_id`),
  ADD KEY `fk_vehicle_documents_person` (`uploaded_by`);

--
-- Indexes for table `vehicle_enquiries`
--
ALTER TABLE `vehicle_enquiries`
  ADD PRIMARY KEY (`enquiry_id`),
  ADD KEY `fk_enquiry_vehicle` (`vehicle_id`),
  ADD KEY `fk_enquiry_customer` (`customer_id`),
  ADD KEY `fk_enquiry_employee` (`assigned_employee_id`);

--
-- Indexes for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_vehicle_images_vehicle` (`vehicle_id`);

--
-- Indexes for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  ADD PRIMARY KEY (`inspection_id`),
  ADD KEY `fk_inspection_vehicle` (`vehicle_id`),
  ADD KEY `fk_inspection_employee` (`inspected_by_employee_id`);

--
-- Indexes for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  ADD PRIMARY KEY (`insurance_id`),
  ADD UNIQUE KEY `policy_number` (`policy_number`),
  ADD KEY `fk_vehicle_insurance_vehicle` (`vehicle_id`),
  ADD KEY `fk_vehicle_insurance_provider` (`provider_id`);

--
-- Indexes for table `vehicle_maintenance_history`
--
ALTER TABLE `vehicle_maintenance_history`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `fk_history_vehicle` (`vehicle_id`),
  ADD KEY `fk_history_jobcard` (`job_card_id`),
  ADD KEY `fk_history_booking` (`service_booking_id`),
  ADD KEY `fk_history_employee` (`serviced_by_employee_id`);

--
-- Indexes for table `vehicle_owners`
--
ALTER TABLE `vehicle_owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD KEY `fk_owner_vehicle` (`vehicle_id`),
  ADD KEY `fk_owner_person` (`person_id`);

--
-- Indexes for table `vehicle_registrations`
--
ALTER TABLE `vehicle_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `fk_registration_vehicle` (`vehicle_id`);

--
-- Indexes for table `vehicle_sales`
--
ALTER TABLE `vehicle_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD UNIQUE KEY `sale_reference` (`sale_reference`),
  ADD KEY `fk_sales_vehicle` (`vehicle_id`),
  ADD KEY `fk_sales_customer` (`customer_id`),
  ADD KEY `fk_sales_employee` (`employee_id`);

--
-- Indexes for table `vehicle_test_drives`
--
ALTER TABLE `vehicle_test_drives`
  ADD PRIMARY KEY (`test_drive_id`),
  ADD KEY `fk_testdrive_vehicle` (`vehicle_id`),
  ADD KEY `fk_testdrive_customer` (`customer_id`),
  ADD KEY `fk_testdrive_employee` (`employee_id`),
  ADD KEY `fk_testdrive_viewing` (`viewing_id`);

--
-- Indexes for table `vehicle_viewings`
--
ALTER TABLE `vehicle_viewings`
  ADD PRIMARY KEY (`viewing_id`),
  ADD KEY `fk_viewing_vehicle` (`vehicle_id`),
  ADD KEY `fk_viewing_customer` (`customer_id`),
  ADD KEY `fk_viewing_employee` (`employee_id`);

--
-- Indexes for table `workshops`
--
ALTER TABLE `workshops`
  ADD PRIMARY KEY (`workshop_id`),
  ADD KEY `fk_workshop_manager` (`manager_employee_id`);

--
-- Indexes for table `workshop_services`
--
ALTER TABLE `workshop_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `fk_services_workshop` (`workshop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_vehicle_submissions`
--
ALTER TABLE `customer_vehicle_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homepage_sliders`
--
ALTER TABLE `homepage_sliders`
  MODIFY `slider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_cards`
--
ALTER TABLE `job_cards`
  MODIFY `job_card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_card_parts`
--
ALTER TABLE `job_card_parts`
  MODIFY `job_card_part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `person_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_vehicles`
--
ALTER TABLE `saved_vehicles`
  MODIFY `saved_vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_updates`
--
ALTER TABLE `service_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `submission_images`
--
ALTER TABLE `submission_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_enquiries`
--
ALTER TABLE `vehicle_enquiries`
  MODIFY `enquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  MODIFY `insurance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_maintenance_history`
--
ALTER TABLE `vehicle_maintenance_history`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_owners`
--
ALTER TABLE `vehicle_owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_registrations`
--
ALTER TABLE `vehicle_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_sales`
--
ALTER TABLE `vehicle_sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_test_drives`
--
ALTER TABLE `vehicle_test_drives`
  MODIFY `test_drive_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_viewings`
--
ALTER TABLE `vehicle_viewings`
  MODIFY `viewing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `workshops`
--
ALTER TABLE `workshops`
  MODIFY `workshop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workshop_services`
--
ALTER TABLE `workshop_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admin_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcement_employee` FOREIGN KEY (`posted_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointment_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointment_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`service_booking_id`) REFERENCES `service_bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feedback_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_vehicle_submissions`
--
ALTER TABLE `customer_vehicle_submissions`
  ADD CONSTRAINT `fk_submission_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_submission_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_department_manager` FOREIGN KEY (`manager_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employee_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `fk_employee_document` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`),
  ADD CONSTRAINT `fk_expense_employee` FOREIGN KEY (`recorded_by_employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `homepage_sliders`
--
ALTER TABLE `homepage_sliders`
  ADD CONSTRAINT `fk_slider_person` FOREIGN KEY (`created_by`) REFERENCES `persons` (`person_id`);

--
-- Constraints for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD CONSTRAINT `fk_claim_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_claim_insurance` FOREIGN KEY (`insurance_id`) REFERENCES `vehicle_insurance` (`insurance_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `job_cards`
--
ALTER TABLE `job_cards`
  ADD CONSTRAINT `fk_jobcard_advisor` FOREIGN KEY (`service_advisor_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jobcard_booking` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`booking_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jobcard_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jobcard_mechanic` FOREIGN KEY (`mechanic_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jobcard_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE;

--
-- Constraints for table `job_card_parts`
--
ALTER TABLE `job_card_parts`
  ADD CONSTRAINT `fk_jcp_employee` FOREIGN KEY (`installed_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jcp_jobcard` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`job_card_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jcp_part` FOREIGN KEY (`part_id`) REFERENCES `inventory` (`part_id`);

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiver_person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_sale` FOREIGN KEY (`sale_id`) REFERENCES `vehicle_sales` (`sale_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `fk_receipt_employee` FOREIGN KEY (`issued_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_receipt_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_employee` FOREIGN KEY (`ordered_by_employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_poi_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_poi_part` FOREIGN KEY (`part_id`) REFERENCES `inventory` (`part_id`);

--
-- Constraints for table `saved_vehicles`
--
ALTER TABLE `saved_vehicles`
  ADD CONSTRAINT `fk_saved_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_saved_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_service_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_service_booking_employee` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_service_booking_service` FOREIGN KEY (`service_id`) REFERENCES `workshop_services` (`service_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_service_booking_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON UPDATE CASCADE;

--
-- Constraints for table `service_updates`
--
ALTER TABLE `service_updates`
  ADD CONSTRAINT `fk_service_update_employee` FOREIGN KEY (`updated_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_service_update_jobcard` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`job_card_id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_images`
--
ALTER TABLE `submission_images`
  ADD CONSTRAINT `fk_submission_image` FOREIGN KEY (`submission_id`) REFERENCES `customer_vehicle_submissions` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_owner` FOREIGN KEY (`current_owner_person_id`) REFERENCES `persons` (`person_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicle_seller` FOREIGN KEY (`seller_person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `fk_vehicle_documents_person` FOREIGN KEY (`uploaded_by`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicle_documents_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_enquiries`
--
ALTER TABLE `vehicle_enquiries`
  ADD CONSTRAINT `fk_enquiry_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enquiry_employee` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enquiry_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD CONSTRAINT `fk_vehicle_images_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  ADD CONSTRAINT `fk_inspection_employee` FOREIGN KEY (`inspected_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inspection_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  ADD CONSTRAINT `fk_vehicle_insurance_provider` FOREIGN KEY (`provider_id`) REFERENCES `insurance_providers` (`provider_id`),
  ADD CONSTRAINT `fk_vehicle_insurance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_maintenance_history`
--
ALTER TABLE `vehicle_maintenance_history`
  ADD CONSTRAINT `fk_history_booking` FOREIGN KEY (`service_booking_id`) REFERENCES `service_bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_history_employee` FOREIGN KEY (`serviced_by_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_history_jobcard` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`job_card_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_history_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_owners`
--
ALTER TABLE `vehicle_owners`
  ADD CONSTRAINT `fk_owner_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_owner_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_registrations`
--
ALTER TABLE `vehicle_registrations`
  ADD CONSTRAINT `fk_registration_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_sales`
--
ALTER TABLE `vehicle_sales`
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_test_drives`
--
ALTER TABLE `vehicle_test_drives`
  ADD CONSTRAINT `fk_testdrive_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_testdrive_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_testdrive_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_testdrive_viewing` FOREIGN KEY (`viewing_id`) REFERENCES `vehicle_viewings` (`viewing_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_viewings`
--
ALTER TABLE `vehicle_viewings`
  ADD CONSTRAINT `fk_viewing_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_viewing_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_viewing_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `workshops`
--
ALTER TABLE `workshops`
  ADD CONSTRAINT `fk_workshop_manager` FOREIGN KEY (`manager_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `workshop_services`
--
ALTER TABLE `workshop_services`
  ADD CONSTRAINT `fk_services_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
