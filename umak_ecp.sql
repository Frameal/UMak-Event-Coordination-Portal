-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2025 at 08:26 PM
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
-- Database: `umak_ecp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `employee_number` varchar(20) NOT NULL COMMENT 'e.g., E001',
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hashed password using bcrypt',
  `email` varchar(100) NOT NULL COMMENT 'firstnamelastname@umak.edu.ph or similar',
  `gender` enum('Male','Female','Other') NOT NULL,
  `position` varchar(100) NOT NULL COMMENT 'Director, Coordinator, etc.',
  `department` enum('UFMO','CSOA','CCDNB','ADC','CCA','GSO','OHSO','SPMO','CIT','Accounting','Other') NOT NULL,
  `contact_number` char(11) NOT NULL,
  `role` enum('Super Admin','Event Coordinator','Moderator') DEFAULT 'Event Coordinator',
  `can_scan_qr` tinyint(1) DEFAULT 1 COMMENT 'Permission to scan QR codes at events',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `employee_number`, `lastname`, `firstname`, `middlename`, `password`, `email`, `gender`, `position`, `department`, `contact_number`, `role`, `can_scan_qr`, `is_active`, `last_login`, `created_at`, `updated_at`, `approval_status`) VALUES
(7, 'test', 'Admin', 'Test', NULL, 'test', 'test.admin@umak.edu.ph', 'Male', 'Tester', 'CSOA', '09123456789', 'Event Coordinator', 1, 1, NULL, '2025-11-27 03:45:48', '2025-11-27 04:07:07', 'Approved'),
(21, 'E001', 'Pingol', 'Katrisha Faye', 'G', '$2y$10$WboeJEbq2T3ixZPmTwHvuuqVV0w1rsp.OmMjnZTUJko2Qwtkselte', 'katrishafaye.pingol@umak.edu.ph', 'Male', 'Director', 'CSOA', '09598745625', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:13:26', '2025-11-27 07:19:26', 'Approved'),
(22, 'E002', 'Adarna', 'Roque III', 'C', '$2y$10$D9cmD12rj2tdSMdnc5A4DugCG6TOqtoeGSxbWq8.EXkmpw3ZytSYO', 'roqueiii.adarna@umak.edu.ph', 'Male', 'Director', 'UFMO', '09875698544', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:14:05', '2025-11-27 07:18:16', 'Approved'),
(23, 'E003', 'Benedicto', 'Orlando', 'P', '$2y$10$mlrMqLydrBeDDNwzwDx23uh7ZqqqHyK6AfWZyboSIwDYA8H13VRV.', 'orlando.benedicto@umak.edu.ph', 'Male', 'Director', 'OHSO', '09745125877', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:14:37', '2025-11-27 07:18:19', 'Approved'),
(24, 'E004', 'Santiago', 'Eleazar', 'G', '$2y$10$H7kvB9KZVaqC2s9w9BqR5ORzdnAmPFKd.uw5cjqcnq9d/3aU39Mmi', 'eleazar.santiago@umak.edu.ph', 'Male', 'Office Head', 'GSO', '09712654844', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:15:17', '2025-11-27 12:35:37', 'Approved'),
(25, 'E005', 'Canapi', 'Jonathan', 'B', '$2y$10$RERaKBHOugSGiBy.C0QreeXAyGnsmS0co0zv1YOj1QDeRoxci.Nm.', 'jonathan.canapi@umak.edu.ph', 'Male', 'Director', 'CIT', '09578412688', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:16:58', '2025-11-27 12:35:41', 'Approved'),
(26, 'E006', 'Odina', 'Jennifer', 'A', '$2y$10$J6LLTt0yQmM64qgPtm1M7ePGuWK/yMwPTv2GY96j8etItA.WrLfgW', 'jennifer.odina@umak.edu.ph', 'Female', 'Office Head', 'SPMO', '09568741255', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:17:33', '2025-11-27 12:35:44', 'Approved'),
(28, 'test21231', 'test', 'Test', 't', '$2y$10$W5LLfTdztodMggLSbgMEReWq3L3jAtv1hXhO.22Gq9aAQrY74wX5K', 'test.test@umak.edu.ph', 'Male', 'test', 'CSOA', 'test', 'Event Coordinator', 1, 1, NULL, '2025-11-27 07:42:39', '2025-11-27 07:43:10', 'Approved'),
(29, 'E007', 'test2', 'test2', '', '$2y$10$SMxz4PKOovw43ax/lUHscOhhLWPSdla8ESPZCREG8NATTo3XnJiZ6', 'test2.test2@umak.edu.ph', 'Male', '123', 'CSOA', '123', 'Event Coordinator', 1, 1, NULL, '2025-11-27 18:33:22', '2025-11-27 18:33:35', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `log_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scanned_by_admin` int(11) NOT NULL,
  `scan_location` varchar(100) DEFAULT NULL COMMENT 'e.g., Main Entrance, Exit',
  `device_info` varchar(255) DEFAULT NULL COMMENT 'Device used for scanning',
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_contacts`
--

CREATE TABLE `email_contacts` (
  `contact_id` int(11) NOT NULL,
  `sender_type` enum('Student','Admin','Guest') NOT NULL,
  `sender_id` int(11) DEFAULT NULL COMMENT 'student_id or admin_id if logged in',
  `sender_name` varchar(100) NOT NULL,
  `sender_email` varchar(100) NOT NULL,
  `recipient_type` enum('Admin','Organization','General') NOT NULL DEFAULT 'General',
  `recipient_id` int(11) DEFAULT NULL COMMENT 'admin_id or org_id',
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `category` enum('General Inquiry','Event Question','Technical Support','Complaint','Suggestion','Other') DEFAULT 'General Inquiry',
  `status` enum('Unread','Read','Replied','Resolved','Archived') DEFAULT 'Unread',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `replied_by_admin` int(11) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue_id` int(11) NOT NULL,
  `attendees_capacity` int(11) NOT NULL,
  `current_attendees` int(11) DEFAULT 0,
  `target_college` enum('All','CLAS','CHK','CBFS','CCIS','CITE','CITE-HSU','CGPP','CCSE','CET','CTHM','CCAPS','SOL','IAD','IOA','IOP','ION','IIHS','ITEST','ISDNB','IOPsy','ISW','IDEM') DEFAULT 'All' COMMENT 'Which college can register',
  `target_year_level` varchar(20) DEFAULT 'All' COMMENT 'e.g., All, 1-2, 3-4, or specific years',
  `event_prioritization` enum('Mandatory','Optional') NOT NULL,
  `event_type` enum('Academic','Social','Sports','Cultural','Workshop','Seminar','Competition','Other') DEFAULT 'Other',
  `status` enum('Draft','Pending Approval','Published','Registration Open','Registration Closed','Ongoing','Completed','Cancelled') DEFAULT 'Draft',
  `requires_evaluation` tinyint(1) DEFAULT 1 COMMENT 'If true, students must evaluate after attending',
  `banner_image` varchar(255) DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL COMMENT 'Admin who created event',
  `created_by_org` int(11) DEFAULT NULL COMMENT 'Organization who created event',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rental_fee` decimal(10,2) DEFAULT NULL COMMENT 'For Accounting use',
  `setup_date` datetime DEFAULT NULL COMMENT 'For GSO/Ingress',
  `teardown_date` datetime DEFAULT NULL COMMENT 'For GSO/Egress',
  `approval_step` int(11) DEFAULT 1 COMMENT '1=CSOA, 2=UFMO, 3=Specialized',
  `registration_start` datetime DEFAULT NULL,
  `registration_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `description`, `event_date`, `start_time`, `end_time`, `venue_id`, `attendees_capacity`, `current_attendees`, `target_college`, `target_year_level`, `event_prioritization`, `event_type`, `status`, `requires_evaluation`, `banner_image`, `created_by_admin`, `created_by_org`, `created_at`, `updated_at`, `rental_fee`, `setup_date`, `teardown_date`, `approval_step`, `registration_start`, `registration_end`) VALUES
(7, 'IT Christmas Party', 'First ever Christmas party of IT students', '2025-12-10', '10:00:00', '17:00:00', 16, 250, 1, 'CCIS', 'All', 'Mandatory', 'Social', 'Completed', 1, '../../images/events/evt_69286589b1cea.jpg', NULL, 1, '2025-11-27 14:51:53', '2025-11-27 17:02:29', NULL, NULL, NULL, 1, '2025-11-28 00:00:00', '2025-11-28 02:00:00'),
(8, 'Test event', 'dasda', '2025-11-28', '00:15:00', '00:20:00', 10, 200, 0, 'All', 'All', 'Mandatory', 'Academic', 'Published', 1, '../../images/events/evt_6928780ea6104.jpg', NULL, 1, '2025-11-27 16:10:54', '2025-11-27 16:11:18', NULL, NULL, NULL, 3, '2025-11-28 00:10:00', '2025-11-28 00:14:00'),
(9, 'Defense', '123', '2025-11-28', '02:50:00', '02:55:00', 4, 50, 0, 'CCIS', 'All', 'Mandatory', 'Academic', 'Completed', 1, '../../images/events/evt_69289c979ba9a.jpg', NULL, 2, '2025-11-27 18:46:47', '2025-11-27 18:55:31', NULL, NULL, NULL, 3, '2025-11-28 02:48:00', '2025-11-28 02:49:00'),
(10, '123', '123', '2025-11-28', '02:55:00', '03:00:00', 10, 50, 1, 'All', 'All', 'Mandatory', 'Academic', 'Completed', 1, '../../images/events/evt_69289dc8bcc08.jpg', NULL, 2, '2025-11-27 18:51:52', '2025-11-27 19:00:03', NULL, NULL, NULL, 3, '2025-11-28 02:51:00', '2025-11-28 02:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_approvals`
--

CREATE TABLE `event_approvals` (
  `approval_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `department` enum('CSOA','UFMO','GSO','OHSO','SPMO','CIT','Accounting','ADC','CCDNB','CCA') NOT NULL,
  `status` enum('Pending','Approved','Rejected','N/A') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_approvals`
--

INSERT INTO `event_approvals` (`approval_id`, `event_id`, `department`, `status`, `remarks`, `approved_by`, `updated_at`) VALUES
(22, 7, 'CSOA', 'Approved', '', NULL, '2025-11-27 14:52:21'),
(23, 7, 'UFMO', 'Approved', '', NULL, '2025-11-27 14:52:38'),
(24, 7, 'CIT', 'Approved', '', NULL, '2025-11-27 14:53:02'),
(25, 7, 'GSO', 'Approved', '', NULL, '2025-11-27 14:53:17'),
(26, 7, 'CSOA', 'Pending', NULL, NULL, '2025-11-27 16:07:34'),
(27, 7, 'UFMO', 'Pending', NULL, NULL, '2025-11-27 16:07:34'),
(28, 7, 'CIT', 'Pending', NULL, NULL, '2025-11-27 16:07:34'),
(29, 7, 'GSO', 'Pending', NULL, NULL, '2025-11-27 16:07:34'),
(30, 8, 'CSOA', 'Approved', '', NULL, '2025-11-27 16:11:08'),
(31, 8, 'UFMO', 'Approved', '', NULL, '2025-11-27 16:11:18'),
(32, 7, 'CSOA', 'Pending', NULL, NULL, '2025-11-27 16:29:25'),
(33, 7, 'UFMO', 'Pending', NULL, NULL, '2025-11-27 16:29:25'),
(34, 7, 'CIT', 'Pending', NULL, NULL, '2025-11-27 16:29:25'),
(35, 7, 'GSO', 'Pending', NULL, NULL, '2025-11-27 16:29:25'),
(36, 9, 'CSOA', 'Approved', '', NULL, '2025-11-27 18:47:27'),
(37, 9, 'UFMO', 'Approved', '', NULL, '2025-11-27 18:47:47'),
(38, 9, 'CIT', 'Approved', '', NULL, '2025-11-27 18:48:03'),
(39, 9, 'GSO', 'Approved', '', NULL, '2025-11-27 18:48:42'),
(40, 10, 'CSOA', 'Approved', '', NULL, '2025-11-27 18:52:03'),
(41, 10, 'UFMO', 'Approved', '', NULL, '2025-11-27 18:52:12');

-- --------------------------------------------------------

--
-- Table structure for table `event_evaluations`
--

CREATE TABLE `event_evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `obj_clarity` int(1) NOT NULL,
  `obj_relevance` int(1) NOT NULL,
  `cond_flow` int(1) NOT NULL,
  `cond_facilitators` int(1) NOT NULL,
  `cond_activities` int(1) NOT NULL,
  `res_mastery` int(1) NOT NULL,
  `res_presentation` int(1) NOT NULL,
  `res_participation` int(1) NOT NULL,
  `tech_venue` int(1) NOT NULL,
  `tech_schedule` int(1) NOT NULL,
  `tech_accommodation` int(1) NOT NULL,
  `tech_sounds` int(1) NOT NULL,
  `comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_evaluations`
--

INSERT INTO `event_evaluations` (`evaluation_id`, `registration_id`, `event_id`, `student_id`, `obj_clarity`, `obj_relevance`, `cond_flow`, `cond_facilitators`, `cond_activities`, `res_mastery`, `res_presentation`, `res_participation`, `tech_venue`, `tech_schedule`, `tech_accommodation`, `tech_sounds`, `comments`, `submitted_at`) VALUES
(1, 7, 7, 7, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, '', '2025-11-27 17:01:03'),
(2, 8, 10, 9, 4, 5, 5, 4, 4, 5, 4, 3, 4, 5, 3, 4, '1231312', '2025-11-27 19:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_code` varchar(100) NOT NULL COMMENT 'Unique QR code for attendance',
  `status` enum('Registered','Attended','Cancelled','No Show') DEFAULT 'Registered',
  `attended_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when QR was scanned',
  `scanned_by_admin` int(11) DEFAULT NULL COMMENT 'Admin who scanned the QR',
  `has_evaluated` tinyint(1) DEFAULT 0 COMMENT 'Has student submitted evaluation?',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `student_id`, `registration_date`, `qr_code`, `status`, `attended_at`, `scanned_by_admin`, `has_evaluated`, `notes`) VALUES
(7, 7, 7, '2025-11-27 16:28:51', 'QR-test-E7-1764260931', 'Attended', '2025-11-27 16:59:41', NULL, 1, NULL),
(8, 10, 9, '2025-11-27 18:53:01', 'QR-K12148194-E10-1764269581', 'Attended', '2025-11-27 18:59:13', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_requirements`
--

CREATE TABLE `event_requirements` (
  `req_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `needs_sound_system` tinyint(1) DEFAULT 0,
  `needs_projector` tinyint(1) DEFAULT 0,
  `needs_chairs` int(11) DEFAULT 0,
  `needs_tables` int(11) DEFAULT 0,
  `needs_internet` tinyint(1) DEFAULT 0,
  `needs_parking` tinyint(1) DEFAULT 0,
  `needs_medical` tinyint(1) DEFAULT 0,
  `needs_cleaning` tinyint(1) DEFAULT 1,
  `has_food` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_requirements`
--

INSERT INTO `event_requirements` (`req_id`, `event_id`, `needs_sound_system`, `needs_projector`, `needs_chairs`, `needs_tables`, `needs_internet`, `needs_parking`, `needs_medical`, `needs_cleaning`, `has_food`, `remarks`) VALUES
(2, 7, 1, 1, 0, 0, 0, 0, 0, 1, 0, ''),
(3, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, ''),
(4, 9, 0, 0, 0, 0, 1, 0, 0, 1, 0, ''),
(5, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_type` enum('admin','organization','student') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_type` enum('admin','organization','student') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `category` enum('General','Report','Suggestion','Inquiry') DEFAULT 'General',
  `subject` varchar(255) DEFAULT NULL,
  `message_body` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by_sender` tinyint(1) DEFAULT 0,
  `deleted_by_receiver` tinyint(1) DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_type`, `sender_id`, `receiver_type`, `receiver_id`, `category`, `subject`, `message_body`, `is_read`, `created_at`, `deleted_by_sender`, `deleted_by_receiver`, `attachment_path`, `attachment_name`, `attachment_type`) VALUES
(22, 'student', 7, 'admin', 1, 'General', 'Destruction', '123', 0, '2025-11-27 18:25:36', 0, 0, NULL, NULL, NULL),
(23, 'student', 7, 'admin', 1, 'General', '123', '123123', 0, '2025-11-27 18:35:07', 0, 0, NULL, NULL, NULL),
(24, 'student', 9, 'organization', 1, 'General', '123', '123', 0, '2025-11-27 19:01:52', 0, 0, NULL, NULL, NULL),
(25, 'student', 7, 'admin', 1, 'General', NULL, '', 0, '2025-11-27 19:11:06', 0, 0, '../../uploads/messages/msg_6928a24aae5fd.png', 'Ticket_123.png', 'png');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `org_id` int(11) NOT NULL,
  `college` enum('CLAS','CHK','CBFS','CCIS','CITE','CITE-HSU','CGPP','CCSE','CET','CTHM','CCAPS','SOL','IAD','IOA','IOP','ION','IIHS','ITEST','ISDNB','IOPsy','ISW','IDEM','University-Wide') NOT NULL,
  `org_name` varchar(200) NOT NULL,
  `org_email` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hashed password - orgs can create events',
  `logo_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`org_id`, `college`, `org_name`, `org_email`, `description`, `password`, `logo_url`, `is_active`, `created_at`, `updated_at`, `approval_status`) VALUES
(1, 'CCIS', 'College of Computing and Information Sciences Student Council', 'umakccissc@umak.edu.ph', 'The College of Computing and Information Sciences Student Council (CCIS SC) embodies all of the Computer Science and Information Technology students who are having their tertiary education in University of Makati (UMak).', 'qwerty123', NULL, 1, '2025-10-24 08:37:50', '2025-11-27 03:45:08', 'Approved'),
(2, 'CCIS', 'Test Org', 'test', NULL, 'test', NULL, 1, '2025-11-27 03:45:48', '2025-11-27 04:07:28', 'Approved'),
(4, 'CCIS', 'UMak Computer Society ', 'umak.comsoc@umak.edu.ph', 'UMak Computer Society (ComSoc) is the sole local student organization of the College of Computer Science of University of Makati. ComSoc aims to gather CCS students, build and promote IT-related activities, and extend IT-based endeavors to other communities.\n\nVISION\nThe organization envisions an entrenched community in the College of Computer Science that will promote and inspire innovative ideas, giving an exciting supplementary learning atmosphere for future IT professionals, complementing the needs of the IT industry.\n\nMISSION\nThe University of Makati Computer Society endeavors to form exceptional enthusiasts and young IT professionals by providing the student with relevant opportunities, training and educational exposure in the field of Information and Communications Technology; thereby providing them the skills to effectively and efficiently render services beneficial to the society.', '$2y$10$lwPSqGmDrC3yG3TB9OudKek9aMRU4zcuNRtcr3V8IMybdO4KXWKYq', NULL, 1, '2025-11-27 12:49:40', '2025-11-27 12:50:05', 'Approved'),
(5, 'CBFS', 'GEMS', 'gems@umak.edu.ph', '123123', '$2y$10$EXlOaq9OQrK0.EkOny3YfeBUMR.J5FMyNbqbwjr30cxPapZeefia6', NULL, 1, '2025-11-27 18:32:25', '2025-11-27 18:33:56', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_type` enum('admin','student','organization') NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `user_type`, `expires_at`) VALUES
(1, 'jframil.a12139502@umak.edu.ph', '4e7198bfb6d282c9b281f753a4ff059174f7af8659a796ddb45a2374df4bbf61', 'student', '2025-11-27 08:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` char(9) NOT NULL COMMENT '9 digits exactly (e.g., A12139502)',
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hashed password using bcrypt',
  `email` varchar(100) NOT NULL COMMENT 'Format: firstinitiallastname.studentnumber@umak.edu.ph',
  `gender` enum('Male','Female','Non-binary') NOT NULL,
  `year_level` int(11) NOT NULL COMMENT '1-4',
  `section` varchar(10) NOT NULL COMMENT 'A, B, C, D, AINS, etc.',
  `college` enum('CLAS','CHK','CBFS','CCIS','CITE','CITE-HSU','CGPP','CCSE','CET','CTHM','CCAPS','SOL','IAD','IOA','IOP','ION','IIHS','ITEST','ISDNB','IOPsy','ISW','IDEM') NOT NULL,
  `course` varchar(100) NOT NULL COMMENT 'e.g., BSIT, BSCS',
  `contact_number` char(11) NOT NULL COMMENT '11 digits (09xxxxxxxxx)',
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `lastname`, `firstname`, `middlename`, `password`, `email`, `gender`, `year_level`, `section`, `college`, `course`, `contact_number`, `profile_picture`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(7, 'test', 'Student', 'Test', NULL, 'test', 'test.student@umak.edu.ph', 'Male', 1, 'A', 'CCIS', 'BSIT', '09123456789', NULL, 1, NULL, '2025-11-27 03:45:48', '2025-11-27 04:07:22'),
(9, 'K12148194', 'Laririt', 'Theodore', '', '$2y$10$BkmW6GkWQ/D7RdS3kCkvUusej9q4mc9TIHcnvrSmRlLi7eYwPbPHW', 'tlaririt.k12148194@umak.edu.ph', 'Male', 3, 'ains', 'CCIS', 'BSIT', '09687459422', NULL, 1, NULL, '2025-11-27 06:20:21', '2025-11-27 06:20:21'),
(10, 'a12345279', 'Espiritu', 'Jairus', '', '$2y$10$AfwPMlThMM/bf6Al7XGF1OcN8P7MAcAD4fyflzXATHcQ.AH9Y9hNS', 'jespiritu.a12345279@umak.edu.ph', 'Male', 2, 'AAE', 'CBFS', 'Associate in Entrepreneurship', '09568745122', NULL, 1, NULL, '2025-11-27 10:58:38', '2025-11-27 10:58:38'),
(11, 'a12345356', 'Lawena', 'Jefferson', '', '$2y$10$jXlmefJqzLjUDq191H08Cu9Fr2pW9XOFu/sK9bsy7yOTp5V6b5U8S', 'jlawena.a12345356@umak.edu.ph', 'Male', 4, 'A', 'CHK', 'Sports Science', '09687459244', NULL, 1, NULL, '2025-11-27 11:06:01', '2025-11-27 11:06:01'),
(12, 'a12345247', 'Vallido', 'Maria Loulynn', 'V', '$2y$10$jFCWruJfiaPLpBMjp6wcyuvUp1JmoZ2s21rW.FHWZByy.G3kiLvl6', 'mvallido.a12345247@umak.edu.ph', 'Female', 3, 'AINS', 'CCIS', 'BSIT', '09874516985', NULL, 1, NULL, '2025-11-27 11:22:15', '2025-11-27 11:22:15'),
(13, 'A12345278', 'Rosas', 'Ma. Stephannie', 'E', '$2y$10$kfIekMjzGvXEkzPnkmUHee92cFH2fozOemLT6ZaSeLjHEblgSJ6D2', 'mrosas.a12345278@umak.edu.ph', 'Female', 1, 'A', 'CGPP', 'BS Paralegal Studies', '09562398745', NULL, 1, NULL, '2025-11-27 11:30:29', '2025-11-27 11:30:29'),
(14, 'A12139502', 'Framil', 'Jerecho', 'G', '$2y$10$hCVBV9CzCWENYmDhBQ9wjON8OOYY/NZ/2SKldQdN8QRqZp5d9hy4m', 'jframil.a12139502@umak.edu.ph', 'Male', 3, 'AINS', 'CCIS', 'BSIT', '09563179622', NULL, 1, NULL, '2025-11-27 18:30:39', '2025-11-27 18:30:39');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL,
  `venue_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` varchar(200) NOT NULL,
  `amenities` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `venue_name`, `capacity`, `location`, `amenities`, `description`, `is_available`, `created_at`, `updated_at`, `image_url`) VALUES
(2, 'Aero & Dance Studio', 60, 'HPSB 11th Floor', 'Dance Floor, Mirrors, Sound System', 'The Aero & Dance Studio is the perfect place to move, dance, and have fun! With plenty of space for everyone, \r\n\r\nit\'s a safe and clean environment where you can enjoy your activity.', 1, '2025-10-24 08:37:50', '2025-11-26 10:58:57', '../../images/Venues/VENUE_6926dd710cb34.jpeg'),
(4, 'Multimedia and Skills Laboratory', 120, 'Health and Physical Science Building (HPSB)', 'Projector, Computers, Modern Equipment', 'The Multimedia and Skills Laboratory is a creative space where students can explore, learn, and build new skills.\r\n\r\n Equipped with modern tools and technology, it\'s designed to help you bring ideas to life, whether you\'re working on media projects, hands-on activities, or group learning sessions.', 1, '2025-10-24 08:37:50', '2025-11-26 10:59:45', '../../images/Venues/VENUE_6926dda141774.png'),
(6, 'Audio Visual Room', 120, 'Academic Building', 'Projector, Sound System, Air Conditioning, Seating', 'The Audio Visual Room is a comfortable and well-equipped space designed for presentations, lectures, film showings, and group activities. \r\n\r\nWith its clear sound system, projector, and cozy seating, it\'s the perfect place for interactive learning and multimedia events.', 1, '2025-10-24 08:37:50', '2025-11-26 10:59:18', '../../images/Venues/VENUE_6926dd86f02ce.jpeg'),
(9, 'HPSB Cafeteria', 175, 'HPSB 11th Floor', 'Dining Tables and Chairs, Food Service Counters, Air Conditioning, Handwashing Stations, Scenic View', 'Perched on the 11th floor, the HPSB Cafeteria is a warm and welcoming space. \r\n\r\nIt offers an air-conditioned environment and is currently not in use for dining purposes but only for event bookings.', 1, '2025-11-26 10:37:11', '2025-11-26 10:59:37', '../../images/Venues/VENUE_6926dd99905ae.png'),
(10, 'Admin Canteen', 276, 'Basement - Admin Bldg', 'Food Stalls, Dining Tables, Ventilation Fans, Wash Area', 'Conveniently located in the basement of the Admin Building, the Admin Canteen serves as a quick and accessible dining hub for staff and students on the go. \r\n\r\nIt provides a practical setting for grabbing meals and snacks without leaving the main building.', 1, '2025-11-26 10:41:14', '2025-11-26 10:59:07', '../../images/Venues/VENUE_6926dd7b0f2a1.jpg'),
(11, 'Track Oval (Whole)', 3136, 'Outdoor Sports Facility', 'Running Track, Football Turf, Full Grandstand and Bleacher Access, Floodlights', 'The University of Makati Track and Oval is a premier outdoor sports facility designed for large-scale athletic events. \r\n\r\nWith a total capacity of 3,136, it encompasses the entire track, field, and all seating zones, providing an expansive and energetic venue for university-wide sports fests, parades, and major competitions.', 1, '2025-11-26 10:42:32', '2025-11-26 11:02:56', '../../images/Venues/VENUE_6926ddba6ee24.jpeg'),
(12, 'Track Oval (Area C)', 1460, 'Far end from the entrance', 'Open Bleacher Seating, Direct Field View, Open-air environment', 'Area C offers the largest dedicated seating section within the oval. \r\n\r\nPerfect for cheering squads and large student crowds, this area provides an excellent wide-angle view of both track races and football matches.', 1, '2025-11-26 10:46:52', '2025-11-26 10:59:55', '../../images/Venues/VENUE_6926ddab2bd09.jpeg'),
(13, 'Track Oval (Area D)', 930, 'Near the entrance', 'Open Bleacher Seating, Direct Field View, Open-air environment', 'Situated along the curve of the track, Area D provides ample seating for spectators. It is a great spot for witnessing the exciting turns of track events and offers a unique perspective of the field activities.', 1, '2025-11-26 10:48:11', '2025-11-26 10:59:59', '../../images/Venues/VENUE_6926ddaff0372.jpeg'),
(14, 'Track Oval (Grandstand)', 518, 'Middle side of the Oval', 'Covered Seating, Elevated View, VIP Section Potential', 'The Grandstand offers the most comfortable viewing experience at the Oval. \r\n\r\nFeaturing covered seating to protect against the elements, it provides a commanding view of the finish line and center field, ideal for guests, officials, and VIPs.', 1, '2025-11-26 10:49:46', '2025-11-26 11:00:05', '../../images/Venues/VENUE_6926ddb5117e9.jpeg'),
(15, 'Volleyball Court', 200, 'HPSB 11th Floor', 'Professional Volleyball Net System, Rubberized Flooring, Industrial Fans/AC, Scoreboard', 'The Volleyball Court is a dedicated active space for training, PE classes, and competitive matches.\r\n\r\nEquipped with professional-grade flooring and net systems, it ensures a safe and high-performance environment for players to spike, set, and serve.', 1, '2025-11-26 10:50:42', '2025-11-26 11:03:03', '../../images/Venues/VENUE_6926ddc096397.png'),
(16, 'Basketball Court', 300, 'HPSB 11th Floor', 'Standard Hoops and Backboards, Court Markings, Industrial Fans/AC, Bleachers', 'A fun and versatile space, the Basketball Court is the heart of indoor sports at HPSB. \r\n\r\nWhether for varsity practice, PE activities, or friendly games, the court offers a well-maintained facility where students can hone their skills and enjoy team sports.', 1, '2025-11-26 10:51:20', '2025-11-26 10:59:31', '../../images/Venues/VENUE_6926dd93207cb.png'),
(17, 'Auditorium', 315, 'Admin 1st floor', 'Theater-style Seating, Stage, Projector and Screen, Sound System, Air Conditioning', 'The UMak Auditorium is a sophisticated venue designed for seminars, intimate performances, and official ceremonies. \r\n\r\nWith comfortable theater seating and excellent acoustics, it provides a focused and professional atmosphere for academic and cultural gatherings.', 1, '2025-11-26 10:52:09', '2025-11-26 10:59:24', '../../images/Venues/VENUE_6926dd8ce019a.png'),
(18, 'UMak Performing Arts Theater (UPAT)', 1184, 'Admin Bldg - 5th floor', 'Grand Stage, Professional Lighting and Sound Rig, Dressing Rooms, Multi-level Seating, Air Conditioning', 'The UPAT is the university\'s crown jewel for the arts. \r\n\r\nA spacious and inspiring venue capable of seating over a thousand guests, it is fully equipped to host grand productions, concerts, and university-wide assemblies, ensuring every performance is delivered with impact.', 1, '2025-11-26 11:02:32', '2025-11-26 11:02:32', '../../images/Venues/VENUE_6926de48de820.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_employee_number` (`employee_number`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `scanned_by_admin` (`scanned_by_admin`),
  ADD KEY `idx_scan_time` (`scanned_at`),
  ADD KEY `idx_event_attendance` (`event_id`,`scanned_at`);

--
-- Indexes for table `email_contacts`
--
ALTER TABLE `email_contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_sent_date` (`sent_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `created_by_admin` (`created_by_admin`),
  ADD KEY `created_by_org` (`created_by_org`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_target_college` (`target_college`);

--
-- Indexes for table `event_approvals`
--
ALTER TABLE `event_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_evaluations`
--
ALTER TABLE `event_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `unique_reg_eval` (`registration_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD UNIQUE KEY `unique_event_student` (`event_id`,`student_id`),
  ADD KEY `scanned_by_admin` (`scanned_by_admin`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_student_events` (`student_id`,`event_id`);

--
-- Indexes for table `event_requirements`
--
ALTER TABLE `event_requirements`
  ADD PRIMARY KEY (`req_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`org_id`),
  ADD UNIQUE KEY `org_email` (`org_email`),
  ADD UNIQUE KEY `unique_org_name` (`org_name`),
  ADD KEY `idx_college` (`college`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_college` (`college`),
  ADD KEY `idx_year_section` (`year_level`,`section`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`),
  ADD KEY `idx_availability` (`is_available`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_contacts`
--
ALTER TABLE `email_contacts`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_approvals`
--
ALTER TABLE `event_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `event_evaluations`
--
ALTER TABLE `event_evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_requirements`
--
ALTER TABLE `event_requirements`
  MODIFY `req_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `org_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`registration_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_ibfk_3` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_ibfk_4` FOREIGN KEY (`scanned_by_admin`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by_admin`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`created_by_org`) REFERENCES `organizations` (`org_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_approvals`
--
ALTER TABLE `event_approvals`
  ADD CONSTRAINT `event_approvals_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_evaluations`
--
ALTER TABLE `event_evaluations`
  ADD CONSTRAINT `event_evaluations_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`registration_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_evaluations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_evaluations_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_3` FOREIGN KEY (`scanned_by_admin`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_requirements`
--
ALTER TABLE `event_requirements`
  ADD CONSTRAINT `event_requirements_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
