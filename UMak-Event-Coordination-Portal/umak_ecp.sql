-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 03:05 AM
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
  `department` enum('UFMO','CSOA','CCDNB','ADC','CCA','Other') NOT NULL,
  `contact_number` char(11) NOT NULL,
  `role` enum('Super Admin','Event Coordinator','Moderator') DEFAULT 'Event Coordinator',
  `can_scan_qr` tinyint(1) DEFAULT 1 COMMENT 'Permission to scan QR codes at events',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `employee_number`, `lastname`, `firstname`, `middlename`, `password`, `email`, `gender`, `position`, `department`, `contact_number`, `role`, `can_scan_qr`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'E001', 'Pingol', 'Katrisha Faye', 'G', 'qwerty123', 'katrishafaye.pingol@umak.edu.ph', 'Female', 'Director', 'CSOA', '09987654321', 'Super Admin', 1, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 09:21:02');

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

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`log_id`, `registration_id`, `student_id`, `event_id`, `scanned_at`, `scanned_by_admin`, `scan_location`, `device_info`, `ip_address`) VALUES
(1, 4, 2, 1, '2025-10-24 10:30:00', 1, 'Main Entrance', 'Chrome/Desktop', '192.168.1.100');

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
  `status` enum('Draft','Published','Registration Open','Registration Closed','Ongoing','Completed','Cancelled') DEFAULT 'Draft',
  `requires_evaluation` tinyint(1) DEFAULT 1 COMMENT 'If true, students must evaluate after attending',
  `banner_image` varchar(255) DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL COMMENT 'Admin who created event',
  `created_by_org` int(11) DEFAULT NULL COMMENT 'Organization who created event',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `description`, `event_date`, `start_time`, `end_time`, `venue_id`, `attendees_capacity`, `current_attendees`, `target_college`, `target_year_level`, `event_prioritization`, `event_type`, `status`, `requires_evaluation`, `banner_image`, `created_by_admin`, `created_by_org`, `created_at`, `updated_at`) VALUES
(1, 'Web Development Workshop', 'Learn modern web development techniques and frameworks', '2025-11-15', '09:00:00', '16:00:00', 2, 100, 0, 'CCIS', 'All', 'Mandatory', 'Workshop', 'Published', 1, NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(2, 'Student General Assembly', 'Quarterly meeting for all students', '2025-11-20', '13:00:00', '15:00:00', 4, 200, 0, 'All', 'All', 'Mandatory', 'Academic', 'Registration Open', 1, NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(3, 'Tech Innovation Conference', 'Latest trends and innovations in technology', '2025-11-25', '08:00:00', '17:00:00', 5, 80, 0, 'CCIS', '3-4', 'Mandatory', 'Seminar', 'Published', 1, NULL, 1, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(4, 'Sports Festival Opening', 'Grand opening ceremony for the annual sports festival', '2025-12-01', '07:00:00', '12:00:00', 3, 500, 0, 'All', 'All', 'Mandatory', 'Sports', 'Draft', 0, NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(5, 'Career Fair 2025', 'Meet potential employers and explore career opportunities', '2025-12-10', '09:00:00', '16:00:00', 1, 300, 0, 'All', '3-4', 'Mandatory', 'Academic', 'Published', 1, NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 08:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `event_evaluations`
--

CREATE TABLE `event_evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating_overall` int(1) NOT NULL COMMENT 'Overall event rating (1-5)',
  `rating_content` int(1) NOT NULL COMMENT 'Content quality (1-5)',
  `rating_speaker` int(1) DEFAULT NULL COMMENT 'Speaker/facilitator (1-5)',
  `rating_venue` int(1) NOT NULL COMMENT 'Venue suitability (1-5)',
  `rating_organization` int(1) NOT NULL COMMENT 'Event organization (1-5)',
  `liked_most` text DEFAULT NULL COMMENT 'What did you like most?',
  `improvements` text DEFAULT NULL COMMENT 'Suggestions for improvement',
  `additional_comments` text DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT 1 COMMENT 'Would recommend to others?',
  `attended_entire_event` tinyint(1) DEFAULT 1,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_anonymous` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 1, '2025-10-24 08:37:50', 'QR-A12139502-E1-1730000001', 'Registered', NULL, NULL, 0, NULL),
(2, 2, 1, '2025-10-24 08:37:50', 'QR-A12139502-E2-1730000002', 'Registered', NULL, NULL, 0, NULL),
(3, 3, 2, '2025-10-24 08:37:50', 'QR-A12345279-E3-1730000003', 'Registered', NULL, NULL, 0, NULL),
(4, 1, 2, '2025-10-24 08:37:50', 'QR-A12345279-E1-1730000004', 'Attended', '2025-10-24 10:30:00', 1, 0, 'Scanned at entrance');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`org_id`, `college`, `org_name`, `org_email`, `description`, `password`, `logo_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CCIS', 'College of Computing and Information Sciences Student Council', 'umakccissc@umak.edu.ph', 'The College of Computing and Information Sciences Student Council (CCIS SC) embodies all of the Computer Science and Information Technology students who are having their tertiary education in University of Makati (UMak).', '$2y$10$E7xQ5tX0GzsVZWvYqEtSCejUOvJa3RJmQlX4wfRgN1bNYPJxj8KVW', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50');

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
  `gender` enum('Male','Female','Other') NOT NULL,
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
(1, 'A12139502', 'Framil', 'Jerecho', 'G', 'qwerty123', 'jframil.a12139502@umak.edu.ph', 'Male', 3, 'AINS', 'CCIS', 'BSIT', '09563179622', NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 09:20:43'),
(2, 'A12345279', 'Espiritu', 'Jairus', 'A', 'asdfgh123', 'jespiritu.a12345279@umak.edu.ph', 'Male', 3, 'AINS', 'CCIS', 'BSIT', '09123456789', NULL, 1, NULL, '2025-10-24 08:37:50', '2025-10-24 09:20:52');

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
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `venue_name`, `capacity`, `location`, `amenities`, `image_url`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'UPAT', 1000, 'Upper Campus', 'Audio system, Projector, Stage, Air conditioning', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(2, 'Auditorium', 500, 'Main Building', 'Audio system, Lighting, Air conditioning, Stage', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(3, 'Oval', 2000, 'Central Campus', 'Open area, Sound system, Lighting', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(4, 'Gymnasium', 800, 'Sports Complex', 'Basketball court, Sound system, Air conditioning', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(5, 'Conference Hall', 100, 'Administration Building', 'Projector, Air conditioning, Tables, Chairs', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50'),
(6, 'Library Hall', 80, 'Academic Building', 'Quiet environment, Projector, Air conditioning', NULL, 1, '2025-10-24 08:37:50', '2025-10-24 08:37:50');

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
-- Indexes for table `event_evaluations`
--
ALTER TABLE `event_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `unique_registration_eval` (`registration_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_event_ratings` (`event_id`,`rating_overall`);

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
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`org_id`),
  ADD UNIQUE KEY `org_email` (`org_email`),
  ADD UNIQUE KEY `unique_org_name` (`org_name`),
  ADD KEY `idx_college` (`college`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_evaluations`
--
ALTER TABLE `event_evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `org_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
