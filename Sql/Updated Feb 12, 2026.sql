-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 09:57 AM
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
-- Database: `healthpatient`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_linking_history`
--

CREATE TABLE `account_linking_history` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `performed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$ZnTO45cy54Fi30sQ.04qPehw./Z7YAxrirQWR.qE3b/RLcpivKaTm', 'System Administrator', '2025-05-01 21:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_messages`
--

CREATE TABLE `announcement_messages` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_targets`
--

CREATE TABLE `announcement_targets` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_targets`
--

INSERT INTO `announcement_targets` (`id`, `announcement_id`, `user_id`, `created_at`) VALUES
(103, 156, 188, '2026-02-12 05:07:23'),
(104, 157, 188, '2026-02-12 05:44:43'),
(105, 158, 188, '2026-02-12 05:46:58'),
(106, 159, 188, '2026-02-12 05:47:35');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','staff','user') NOT NULL,
  `action` varchar(255) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_type`, `action`, `table_affected`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'staff', 'create patient', 'sitio1_patients', 10, NULL, NULL, '::1', NULL, '2025-05-04 01:40:46');

-- --------------------------------------------------------

--
-- Table structure for table `child_health_records`
--

CREATE TABLE `child_health_records` (
  `id` int(11) NOT NULL,
  `family_no` varchar(50) NOT NULL,
  `ufc_no` varchar(50) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `dob` date NOT NULL,
  `birth_order` varchar(20) DEFAULT NULL,
  `place_of_delivery` varchar(50) DEFAULT NULL,
  `mother` varchar(100) DEFAULT NULL,
  `mother_age` int(11) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father` varchar(100) DEFAULT NULL,
  `father_age` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `type_of_feeding` varchar(100) DEFAULT NULL,
  `date_referred_newborn` date DEFAULT NULL,
  `bf1` date DEFAULT NULL,
  `bf2` date DEFAULT NULL,
  `bf3` date DEFAULT NULL,
  `bf4` date DEFAULT NULL,
  `bf5` date DEFAULT NULL,
  `child_protected_at_birth` varchar(100) DEFAULT NULL,
  `date_assessed` date DEFAULT NULL,
  `tt_status_mother` varchar(100) DEFAULT NULL,
  `anemic_children_seen` varchar(100) DEFAULT NULL,
  `anemic_children_iron` varchar(100) DEFAULT NULL,
  `birthwt` varchar(50) DEFAULT NULL,
  `low_birthwt_seen` varchar(100) DEFAULT NULL,
  `low_birthwt_iron` varchar(100) DEFAULT NULL,
  `date_iron_started` date DEFAULT NULL,
  `vit_a_1` varchar(50) DEFAULT NULL,
  `vit_a_2` varchar(50) DEFAULT NULL,
  `vit_a_3` varchar(50) DEFAULT NULL,
  `completed` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_health_results`
--

CREATE TABLE `child_health_results` (
  `id` int(11) NOT NULL,
  `child_health_record_id` int(11) DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `age` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `findings` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_immunizations`
--

CREATE TABLE `child_immunizations` (
  `id` int(11) NOT NULL,
  `child_health_record_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `within_24hrs` tinyint(1) DEFAULT NULL,
  `first` tinyint(1) DEFAULT NULL,
  `second` tinyint(1) DEFAULT NULL,
  `third` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_notes`
--

CREATE TABLE `consultation_notes` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `consultation_date` date NOT NULL,
  `next_consultation_date` date DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultation_notes`
--

INSERT INTO `consultation_notes` (`id`, `patient_id`, `note`, `consultation_date`, `next_consultation_date`, `doctor_name`, `created_by`, `created_at`, `updated_at`) VALUES
(58, 218, 'Announcements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse Stats', '2026-02-02', '2026-03-21', 'Dr. John Paul R. Llena', 7, '2026-02-02 16:07:17', NULL),
(59, 230, 'Hello sir and Maam', '2026-02-03', '2026-02-27', 'Dr. Doc Willy Ong III', 7, '2026-02-03 04:30:51', NULL),
(60, 230, 'dsfdshjfgsdfsdafdsafda', '2026-02-03', '2026-02-27', 'Dr. Dr. Doc Willy Ong III', 7, '2026-02-03 04:31:16', NULL),
(61, 230, 'stgdefhjfdsgasdfsadf', '2026-02-03', '2026-03-03', 'Dr. Dr. Doc Willy Ong III', 7, '2026-02-03 04:31:23', NULL),
(62, 230, 'asdagasdasdasd', '2026-02-03', '2026-03-12', 'Dr. Dr. Doc Willy Ong III', 7, '2026-02-03 04:31:31', NULL),
(63, 218, 'DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.DEBUG: No lab result announcements found for this user.', '2026-02-04', '2026-03-14', 'Dr. DEBUG: No lab result announcements found for this user.', 7, '2026-02-04 02:46:38', NULL),
(64, 229, 'at this announcement process change the display of sucess and error message into a prompt message like the prompt message success at the existing info patients process such as adding new patient sucess and \"Save All Information\" success and error message display, follow the color and design base on the image provided', '2026-02-04', '2026-03-10', 'Dr. Doc Willy Ong', 7, '2026-02-04 04:50:04', NULL),
(65, 231, 'make sure the image display at the email notification will be successfully displaymake sure the image display at the email notification will be successfully displaymake sure the image display at the email notification will be successfully display', '2026-02-04', '2026-03-12', 'Dr. Doc Willy Ong', 7, '2026-02-04 06:18:47', NULL),
(67, 231, 'Good news! TESDA will hold a free livelihood training on food processing starting February 20. Interested residents may register at the barangay office.', '2026-02-04', '2026-03-06', 'Dr. Rica Java', 7, '2026-02-04 14:24:16', NULL),
(68, 231, '\"Good news! TESDA will hold a free livelihood training on food processing starting February 20. Interested residents may register at the barangay office.', '2026-02-04', '2026-03-12', 'Dr. Creshiel Manloloyo', 7, '2026-02-04 14:26:05', NULL),
(69, 233, 'manloloyocresiel@gmail.commanloloyocresiel@gmail.commanloloyocresiel@gmail.commanloloyocresiel@gmail.com', '2026-02-04', '2026-03-14', 'Dr. Rica Java', 7, '2026-02-04 14:40:37', NULL),
(70, 235, 'Hello Boss Jerecho', '2026-02-06', '2026-04-09', 'Dr. Doc Michael', 2, '2026-02-06 15:19:53', NULL),
(71, 231, 'Hello Sir', '2026-02-07', '2026-02-17', 'Dr. Doc Will Ong', 2, '2026-02-07 08:05:00', NULL),
(73, 235, 'high blodd', '2026-02-07', '2026-02-13', 'Dr. labos', 2, '2026-02-07 10:28:37', NULL),
(74, 239, 'hahaha', '2026-02-07', '2026-02-12', 'Dr. evan', 2, '2026-02-07 10:40:42', NULL),
(75, 235, 'Hello', '2026-02-09', '2026-02-10', 'Dr. Jaycar Otida', 2, '2026-02-09 08:13:44', NULL),
(76, 231, 'asdasd', '2026-02-09', '2026-02-26', 'Dr. adasd', 2, '2026-02-09 15:16:03', NULL),
(77, 231, 'dasd', '2026-02-10', '2026-03-03', 'Dr. sadsad', 2, '2026-02-10 04:02:05', NULL),
(78, 240, 'paayu', '2026-02-11', '2026-02-12', 'Dr. labos', 2, '2026-02-11 07:43:40', NULL),
(79, 241, 'hHello', '2026-02-11', '2026-03-14', 'Dr. Jacky Fuck', 2, '2026-02-11 08:41:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deleted_patients`
--

CREATE TABLE `deleted_patients` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `last_checkup` date DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_patients`
--

INSERT INTO `deleted_patients` (`id`, `original_id`, `full_name`, `date_of_birth`, `age`, `gender`, `address`, `contact`, `last_checkup`, `added_by`, `user_id`, `deleted_at`, `deleted_by`) VALUES
(111, 236, 'Archiel  Rosel Cabanag', '2002-05-26', 23, 'Male', 'asdas', 'asdsa', '2026-02-24', 2, NULL, '2026-02-06 10:35:18', 2),
(112, 237, 'michael labos', '2002-05-16', 23, 'Male', 'Pahina San Nicolas Cebu City', '09193341279', '2028-01-07', 2, NULL, '2026-02-11 08:58:45', 2);

-- --------------------------------------------------------

--
-- Table structure for table `existing_info_patients`
--

CREATE TABLE `existing_info_patients` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `blood_type` varchar(3) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `temperature` decimal(4,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `immunization_record` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `family_medical_history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `existing_info_patients`
--

INSERT INTO `existing_info_patients` (`id`, `patient_id`, `gender`, `height`, `weight`, `blood_type`, `allergies`, `medical_history`, `current_medications`, `family_history`, `updated_at`, `temperature`, `blood_pressure`, `immunization_record`, `chronic_conditions`, `family_medical_history`) VALUES
(192, 218, 'Male', 45.00, 45.00, 'A+', 'AnnouncementsAnnouncements\r\n\r\nResponse Stats\r\n\r\nResponse Stats', 'Announcements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse Stats', 'Announcements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse Stats', 'Announcements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse Stats', '2026-02-02 16:05:58', 45.00, '120/80', 'Announcements\r\n\r\nResponse StatsAnnouncements\r\n\r\nResponse Stats', 'AnnouncementsAnnouncements\r\n\r\nResponse Stats\r\n\r\nResponse Stats', NULL),
(193, 229, 'Male', 45.00, 45.00, 'A+', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', '2026-02-04 04:21:57', 45.00, '120/80', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', 'at the profile image added each resident account i want to add that image on their patient health records display each put the image beside the Add New Note button at the right side of the consultation Note container', NULL),
(194, 230, 'Male', 45.00, 45.00, 'A+', 'yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'Sorry sir sir sorry', 'yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', '2026-02-03 04:34:34', 45.00, '120/80', 'yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', NULL),
(195, 231, 'Male', 45.00, 45.00, 'A+', 'None of the Above', 'Hello Maam', 'None of the Above', 'brruhhh', '2026-02-11 07:12:42', 45.00, '120/80', 'None of the Above', 'None of the Above', NULL),
(198, 233, 'Female', 45.00, 45.00, 'O+', 'manloloyocresiel@gmail.com', 'hello', 'manloloyocresiel@gmail.com', 'manloloyocresiel@gmail.com', '2026-02-04 17:52:10', 45.00, '120/80', 'manloloyocresiel@gmail.com', 'manloloyocresiel@gmail.com', NULL),
(201, 235, 'Male', 45.00, 45.00, 'A+', 'None of Above', 'None of Above', 'None of Above', 'None of Above', '2026-02-06 15:18:47', 45.00, '120/80', 'None of Above', 'None of Above', NULL),
(204, 238, 'Male', 45.00, 34.00, 'A-', 'shrimp', 'high blood', 'tb', 'high blood', NULL, 30.00, '120/80', 'astra', 'diabeties', NULL),
(205, 239, 'Male', 45.00, 34.00, 'A+', 'shrimp', 'chronic', 'tb', 'parents', '2026-02-09 15:18:07', 30.00, '120/80', 'astra', 'asthma', NULL),
(206, 240, 'Male', 45.00, 45.00, 'A+', 'ahrimp', 'tb', 'tb', 'high blood', NULL, 45.00, '120/80', 'ASTRA', 'diabetis', NULL),
(207, 241, 'Female', 155.00, 43.00, 'O+', 'sjdjhjyajs', 'sjahdji', 'sbhesahbsuay', 'dksudjkdus', NULL, 37.00, '180/200', 'sajshjaba', 'sjadyjsx', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patient_visits`
--

CREATE TABLE `patient_visits` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `visit_date` datetime NOT NULL,
  `visit_type` varchar(100) NOT NULL,
  `symptoms` text DEFAULT NULL,
  `vital_signs` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `referral_info` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `next_visit_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `present_pregnant_records`
--

CREATE TABLE `present_pregnant_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `birth_plan` varchar(255) DEFAULT NULL,
  `nutrition_breastfeeding` varchar(255) DEFAULT NULL,
  `family_planning` varchar(255) DEFAULT NULL,
  `tt_vaccination` varchar(100) DEFAULT NULL,
  `iron_folic` varchar(100) DEFAULT NULL,
  `vitamin_a` varchar(100) DEFAULT NULL,
  `prenatal_schedule` varchar(255) DEFAULT NULL,
  `visit_notes` text DEFAULT NULL,
  `referrals` varchar(255) DEFAULT NULL,
  `gravidity` int(11) DEFAULT NULL,
  `parity` int(11) DEFAULT NULL,
  `prev_outcomes` text DEFAULT NULL,
  `lmp` date DEFAULT NULL,
  `cycle_regularity` varchar(100) DEFAULT NULL,
  `contraceptive_history` text DEFAULT NULL,
  `past_illnesses` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `edd` date DEFAULT NULL,
  `gestational_age` varchar(50) DEFAULT NULL,
  `risk_assessment` varchar(100) DEFAULT NULL,
  `danger_signs` text DEFAULT NULL,
  `bp` varchar(50) DEFAULT NULL,
  `hr` varchar(50) DEFAULT NULL,
  `rr` varchar(50) DEFAULT NULL,
  `temperature` varchar(50) DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `height` varchar(50) DEFAULT NULL,
  `fundal_height` varchar(50) DEFAULT NULL,
  `fetal_heart_tones` varchar(50) DEFAULT NULL,
  `edema` varchar(100) DEFAULT NULL,
  `hemoglobin` varchar(50) DEFAULT NULL,
  `urinalysis` varchar(100) DEFAULT NULL,
  `blood_typing` varchar(50) DEFAULT NULL,
  `syphilis_test` varchar(50) DEFAULT NULL,
  `hiv_test` varchar(50) DEFAULT NULL,
  `hepatitis_b` varchar(50) DEFAULT NULL,
  `fbs` varchar(50) DEFAULT NULL,
  `emergency_prep` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `present_pregnant_records`
--

INSERT INTO `present_pregnant_records` (`id`, `patient_id`, `birth_plan`, `nutrition_breastfeeding`, `family_planning`, `tt_vaccination`, `iron_folic`, `vitamin_a`, `prenatal_schedule`, `visit_notes`, `referrals`, `gravidity`, `parity`, `prev_outcomes`, `lmp`, `cycle_regularity`, `contraceptive_history`, `past_illnesses`, `allergies`, `family_history`, `edd`, `gestational_age`, `risk_assessment`, `danger_signs`, `bp`, `hr`, `rr`, `temperature`, `weight`, `height`, `fundal_height`, `fetal_heart_tones`, `edema`, `hemoglobin`, `urinalysis`, `blood_typing`, `syphilis_test`, `hiv_test`, `hepatitis_b`, `fbs`, `emergency_prep`, `created_at`) VALUES
(1, NULL, 'asdas', 'dasd', 'asd', 'asdasd', 'asdasd', 'asdasd', 'asda', 'sdas', 'dasd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-07 02:07:23'),
(2, NULL, 'asd', 'asd', 'asd', 'asd', 'asd', 'asd', 'asd', 'asd', 'asd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-07 02:19:06');

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_account_linking_history`
--

CREATE TABLE `sitio1_account_linking_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_record_id` int(11) DEFAULT NULL,
  `linked_by` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'link, unlink, create_and_link',
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_announcements`
--

CREATE TABLE `sitio1_announcements` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('normal','medium','high') DEFAULT 'normal',
  `announcement_type` enum('simple','lab_result') DEFAULT 'simple',
  `expiry_date` date DEFAULT NULL,
  `post_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','archived','expired') DEFAULT 'active',
  `audience_type` enum('public','specific','landing_page') NOT NULL DEFAULT 'public',
  `announcement_category` enum('basic','lab_result') NOT NULL DEFAULT 'basic',
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_announcements`
--

INSERT INTO `sitio1_announcements` (`id`, `staff_id`, `title`, `message`, `priority`, `announcement_type`, `expiry_date`, `post_date`, `updated_at`, `status`, `audience_type`, `announcement_category`, `image_path`) VALUES
(156, 2, 'Temporary Clinic Closure', 'Please be informed that the Health Center will be closed on February 25, 2026 due to facility maintenance. Regular services will resume the next day.', 'medium', 'lab_result', '2026-02-13', '2026-02-12 05:07:23', NULL, 'active', 'specific', 'basic', NULL),
(157, 2, 'Free Blood Pressure Monitoring', 'Free blood pressure monitoring is available daily at the Health Center. Residents, especially senior citizens, are encouraged to have their BP checked regularly.', 'high', 'lab_result', '2026-02-13', '2026-02-12 05:44:43', NULL, 'active', 'specific', 'basic', NULL),
(158, 2, 'Dengue Prevention Advisory', 'Dengue cases are increasing. Residents are advised to clean their surroundings, remove stagnant water, and seek medical attention if symptoms such as fever and body pain appear.', 'normal', '', '2026-02-13', '2026-02-12 05:46:58', NULL, 'active', 'specific', 'basic', NULL),
(159, 2, 'Family Planning Seminar', 'A Family Planning Seminar will be held on March 5, 2026, at 10:00 AM at the Health Center. All couples and interested residents are welcome to attend.', 'medium', '', '2026-02-13', '2026-02-12 05:47:35', NULL, 'active', 'specific', 'basic', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_appointments`
--

CREATE TABLE `sitio1_appointments` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_slots` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_consultations`
--

CREATE TABLE `sitio1_consultations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `is_custom` tinyint(1) DEFAULT 0,
  `status` enum('pending','responded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_patients`
--

CREATE TABLE `sitio1_patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phic_no` varchar(50) DEFAULT NULL,
  `bhw_assigned` varchar(100) DEFAULT NULL,
  `family_no` varchar(50) DEFAULT NULL,
  `fourps_member` enum('Yes','No') DEFAULT 'No',
  `full_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sitio` varchar(255) DEFAULT NULL,
  `disease` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `last_checkup` date DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consultation_type` varchar(20) DEFAULT 'onsite',
  `civil_status` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 0,
  `consent_date` datetime DEFAULT NULL,
  `patient_record_uid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_patients`
--

INSERT INTO `sitio1_patients` (`id`, `user_id`, `phic_no`, `bhw_assigned`, `family_no`, `fourps_member`, `full_name`, `date_of_birth`, `age`, `address`, `sitio`, `disease`, `contact`, `last_checkup`, `medical_history`, `added_by`, `created_at`, `deleted_at`, `restored_at`, `gender`, `updated_at`, `consultation_type`, `civil_status`, `occupation`, `consent_given`, `consent_date`, `patient_record_uid`) VALUES
(218, 187, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Warren Miguel Miras', '1999-12-25', 26, 'Barangay Luz, Cebu City', 'Panganiban', NULL, '09816497664', '2025-11-27', NULL, 7, '2026-02-02 15:56:25', NULL, '2026-02-02 15:56:25', 'Male', '2026-02-02 16:05:58', 'onsite', 'Separated', 'Student Teacher', 0, NULL, NULL),
(229, 185, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Jaycar Otida', '1999-01-01', 27, 'Barangay Luz, Cebu City', 'Carbon', NULL, '09816497664', '2026-01-06', NULL, 7, '2026-02-03 00:15:23', NULL, NULL, 'Male', '2026-02-04 04:21:57', 'onsite', 'Separated', 'Student Teacher', 1, '2026-02-03 08:15:23', 'PAT-20260203-JAY-4864'),
(230, 186, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Russel Evan Loquinario', '2025-02-01', 1, 'Barangay Luz, Cebu City', 'Panganiban', NULL, '09816497664', '2026-02-22', NULL, 7, '2026-02-03 00:37:00', NULL, NULL, 'Male', '2026-02-03 04:34:34', 'onsite', 'Married', 'Student Teacher', 1, '2026-02-03 08:37:00', 'PAT-20260203-RUS-5944'),
(231, 188, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Archiel R. Cabanag', '2002-05-26', 23, 'Barangay Luz, Cebu City', 'Panganiban', NULL, '09816497664', '2026-01-20', NULL, 7, '2026-02-03 01:07:53', NULL, NULL, 'Male', '2026-02-11 07:12:42', 'onsite', 'Divorced', 'Student Teacher', 1, '2026-02-03 09:07:53', 'PAT-20260203-ARC-5860'),
(233, 190, '524323', 'Archiel R. Cabanag', '09206001470', 'Yes', 'Creshiel Manloloyo', '2002-05-26', 23, 'Barangay Luz, Cebu City Philippines', 'Proper Luz', NULL, '09816497664', '2026-01-11', NULL, 7, '2026-02-04 14:38:21', NULL, NULL, 'Female', '2026-02-04 17:52:10', 'onsite', 'Married', 'Student Teacher', 1, '2026-02-04 22:38:21', 'PAT-20260204-CRE-5878'),
(235, 189, '524323', 'Archiel R. Cabanag', '09206001470', 'Yes', 'Jerecho Latosa', '2002-04-19', 23, 'Duljo Fatima, Cebu City', 'Carbon', NULL, '09206001470', '2025-11-23', NULL, 2, '2026-02-05 04:47:47', NULL, NULL, 'Male', '2026-02-06 15:18:47', 'onsite', 'Single', 'Student Teacher', 1, '2026-02-05 12:47:47', 'PAT-20260205-JER-7728'),
(238, NULL, '1123', 'jinky farad', '12', 'Yes', 'michael', '2002-05-03', 23, 'Pahina San Nicolas Cebu', 'Lower Luz', NULL, '09312312421', '2026-02-13', NULL, 2, '2026-02-07 10:26:34', NULL, NULL, 'Male', '2026-02-07 10:26:34', 'onsite', 'Single', 'vendor', 1, '2026-02-07 18:26:34', NULL),
(239, NULL, '1123', 'jinky farad', '12', 'No', 'evan', '2026-02-04', 0, 'Pahina San Nicolas Cebu', 'Proper Luz', NULL, '09312312421', '2026-02-07', NULL, 2, '2026-02-07 10:40:06', NULL, NULL, 'Male', '2026-02-09 15:18:07', 'onsite', 'Single', 'vendor', 1, '2026-02-07 18:40:06', NULL),
(240, NULL, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Russel Evan Loquinario', '1998-08-03', 27, 'Labangon Cebu City', 'Luz Proper', NULL, '09206001470', '2026-02-10', NULL, 2, '2026-02-11 07:41:32', NULL, NULL, 'Male', '2026-02-11 07:41:32', 'onsite', 'Single', 'Student', 1, '2026-02-11 15:41:32', NULL),
(241, 192, '0950420', 'Jinky Figuracion', '09504206113', 'Yes', 'Shiel Manloloyo', '2001-11-22', 24, 'Barangay Luz, Cebu City Philippines', 'Luz Heights', NULL, '09504206113', '2026-02-11', NULL, 2, '2026-02-11 08:34:04', NULL, NULL, 'Female', '2026-02-11 08:39:17', 'onsite', 'Single', 'Student', 1, '2026-02-11 16:34:04', 'PAT-20260211-SHI-8712');

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_staff`
--

CREATE TABLE `sitio1_staff` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `is_active` tinyint(1) DEFAULT 1,
  `work_days` varchar(20) DEFAULT '1111100' COMMENT '7-digit string (1=working, 0=off), Mon-Sun',
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_staff`
--

INSERT INTO `sitio1_staff` (`id`, `username`, `password`, `full_name`, `position`, `created_by`, `created_at`, `status`, `is_active`, `work_days`, `specialization`, `license_number`) VALUES
(1, 'Lance', '$2y$10$61keyKT9UVY4PQIN1jD2TOLwqv2i5C0cpE82vyi4lBeajqmJg8EbS', 'Lance Christine Gallardo', 'Nurse', 1, '2025-05-01 21:25:31', 'inactive', 0, '1111100', NULL, '1'),
(2, 'Archiel', '$2y$10$6JkB04nXJ2E14yUo5einmusdXo1hIJdLSLgrim2w51DMh8f7T7en.', 'Archiel  Rosel Cabanag', 'Health Worker', 1, '2025-05-01 23:05:06', 'active', 1, '1111100', NULL, '1'),
(7, 'Jerecho', '$2y$10$18YWoUQ8DvBld1dd8WC7uOCDOgamtPaMyE/W.jr3O3rlqCyiyFsPW', 'Jerecho Latosa', 'Medical Technologist', NULL, '2026-01-31 14:16:10', 'inactive', 0, '1111100', 'General Medecine', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `sitio1_users`
--

CREATE TABLE `sitio1_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sitio` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `unique_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','declined') DEFAULT 'pending',
  `role` varchar(20) DEFAULT 'patient',
  `specialization` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `verification_method` enum('manual_verification','id_upload') DEFAULT 'manual_verification',
  `id_image_path` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `verification_consent` tinyint(1) DEFAULT 0,
  `id_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `account_linked` tinyint(1) DEFAULT 0,
  `patient_record_id` int(11) DEFAULT NULL,
  `patient_record_uid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_users`
--

INSERT INTO `sitio1_users` (`id`, `username`, `password`, `email`, `full_name`, `gender`, `age`, `date_of_birth`, `address`, `sitio`, `contact`, `civil_status`, `occupation`, `approved`, `approved_by`, `unique_number`, `created_at`, `last_login`, `status`, `role`, `specialization`, `license_number`, `updated_at`, `verification_method`, `id_image_path`, `profile_image`, `verification_notes`, `verification_consent`, `id_verified`, `verified_at`, `account_linked`, `patient_record_id`, `patient_record_uid`) VALUES
(185, 'Jaycar', '$2y$10$dodwYp9EUxNnTU3YWTG30uzFNa9rUHLo3rDkVTv.P3/LBZ6Bz3jam', 'jaycarotida@gmail.com', 'Jaycar Otida', 'male', 0, '2025-12-28', NULL, 'Panganiban', '09206001470', NULL, NULL, 1, NULL, 'RESPAN202601613', '2026-01-29 03:45:00', NULL, 'approved', 'patient', NULL, NULL, '2026-02-03 00:16:19', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-01-29 03:45:00', 0, NULL, 'PAT-20260203-JAY-4864'),
(186, 'Russel', '$2y$10$z3Ih.DKwsdxVg5hbEsixLO/9pKRjYDurh5WVrwcGM0iyT0nKcEnQW', 'russelloki@gmai.com', 'Russel Evan Loquinario', 'male', 0, '2026-01-22', NULL, 'Panganiban', '09816497664', NULL, NULL, 1, NULL, 'RESPAN202601519', '2026-01-31 07:58:04', NULL, 'approved', 'patient', NULL, NULL, '2026-02-03 00:37:16', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-01-31 07:58:04', 0, NULL, 'PAT-20260203-RUS-5944'),
(187, 'Warren', '$2y$10$HcmJys/6X0ZknuLCBQlNa.pXbnbDbXD9LUem2Pf3hNrRU0DcK8H82', 'warrenmiguel@gmail.com', 'Warren Miras', 'male', 0, '2026-02-02', NULL, 'Panganiban', '09206001470', NULL, NULL, 1, NULL, 'RESPAN202602394', '2026-02-02 08:41:23', NULL, 'approved', 'patient', NULL, NULL, '2026-02-02 14:49:44', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-02 08:41:23', 0, NULL, 'PAT-20260202-WAR-6678'),
(188, 'Archiel', '$2y$10$g8TwFQcs2kbxTJGpcEziQOD4Bi3OE.Z9sRXsOZ04eqeNVEfHtFRnq', 'cabanagarchielrosel@gmail.com', 'Archiel R. Cabanag', 'male', 23, '2002-05-26', NULL, 'Panganiban', '09816497664', NULL, NULL, 1, NULL, 'RESPAN202602648', '2026-02-03 00:38:57', NULL, 'approved', 'patient', NULL, NULL, '2026-02-03 01:08:14', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-03 00:38:57', 0, NULL, 'PAT-20260203-ARC-5860'),
(189, 'Jerecho', '$2y$10$cYNvUqFWzNCJN67Jc97AMebjUHqBK/.63Jff3jf7qVtzY86vVTKpO', 'jerecholatosa@gmail.com', 'Jerecho Latosa', 'male', 0, '2026-02-02', NULL, 'Panganiban', '09816497664', NULL, NULL, 1, NULL, 'RESPAN202602578', '2026-02-04 09:47:48', NULL, 'approved', 'patient', NULL, NULL, '2026-02-05 04:48:02', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-04 09:47:48', 0, NULL, 'PAT-20260205-JER-7728'),
(190, 'Creshiel', '$2y$10$msJZlUh/VyyfSwGepUmTV.X.Hu9OVEJqmByI/E2bp9VyydwLdZSc6', 'manloloyocresiel@gmail.com', 'Creshiel Manloloyo', 'female', 2, '2024-01-17', NULL, 'Panganiban', '09206001470', NULL, NULL, 1, NULL, 'RESPAN202602805', '2026-02-04 14:34:22', NULL, 'approved', 'patient', NULL, NULL, '2026-02-04 14:39:21', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-04 14:34:22', 0, NULL, 'PAT-20260204-CRE-5878'),
(191, 'Rica', '$2y$10$Dv/bQXjr7b1kVUlCaHR/meHM2z1/852rymaxQ9c8phZLM87L7g7Ke', 'ricamaejava@gmail.com', 'Rica Mae Java', 'male', 0, '2026-02-01', NULL, 'Panganiban', '09206001470', NULL, NULL, 1, NULL, 'RESPAN202602993', '2026-02-04 17:10:31', NULL, 'approved', 'patient', NULL, NULL, NULL, 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-04 17:10:31', 0, NULL, NULL),
(192, 'Joshua', '$2y$10$NuQDrkZcdDW.jK.eJZeZguvAcj5yzZSbiPaCeVn.6x/kc0F8Rnt3u', 'joshuacaballes@gmail.com', 'Joshua Caballes', 'male', 0, '2026-02-02', NULL, 'Panganiban', '09816497664', NULL, NULL, 1, NULL, 'RESPAN202602364', '2026-02-04 17:14:33', NULL, 'approved', 'patient', NULL, NULL, '2026-02-11 08:39:17', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-04 17:14:33', 0, NULL, 'PAT-20260211-SHI-8712'),
(193, 'Lance', '$2y$10$UwYBTmIGKKYUeGhtlJYQ2uUeHZK/be/a.vhSJnGq0X5D3rzJHA2o.', 'lancechristinegallardo2@gmail.com', 'Lance Christine Gallardo', 'female', 0, '2026-02-01', NULL, 'Panganiban', '09206001470', NULL, NULL, 1, NULL, 'RESPAN202602578', '2026-02-11 22:04:09', NULL, 'approved', 'patient', NULL, NULL, NULL, 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-02-11 22:04:09', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

CREATE TABLE `staff_activity_log` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_activity_log`
--

INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'export_bulk_pdf', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"record_count\":5,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:06:08'),
(2, 1, 'export_bulk_pdf', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"record_count\":5,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:07:41'),
(3, 1, 'export_bulk_pdf', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"record_count\":5,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:09:06'),
(4, 1, 'staff_logout', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:09:47'),
(5, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:09:53'),
(6, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.108.2 Chrome/142.0.7444.235 Electron/39.2.7 Safari/537.36', '2026-02-02 00:36:59'),
(7, 2, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 08:10:28'),
(8, 2, 'print_patient', 210, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":210}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 13:09:41'),
(9, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 14:27:17'),
(10, 2, 'staff_logout', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 14:27:28'),
(11, 2, 'print_patient', 216, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":216}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 15:35:37'),
(12, 2, 'staff_logout', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 15:57:00'),
(13, 7, 'staff_login', NULL, '{\"full_name\":\"Jerecho Latosa\",\"username\":\"Jerecho\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 15:57:29'),
(14, 7, 'add_patient', 217, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"217\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 21:58:59'),
(15, 7, 'archive_patient', 217, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"217\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 22:02:32'),
(16, 7, 'add_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"218\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 22:49:23'),
(17, 7, 'print_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":218}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 22:56:20'),
(18, 7, 'archive_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"218\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:13:24'),
(19, 7, 'restore_patient', 218, '{\"patient_id\":\"218\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-02-02 23:39:17\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:39:17'),
(20, 7, 'restore_patient', 217, '{\"patient_id\":\"217\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-02-02 23:39:31\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:39:31'),
(21, 7, 'archive_patient', 217, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"217\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:46:55'),
(22, 7, 'restore_patient', 217, '{\"patient_id\":\"217\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-02-02 23:47:00\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:47:00'),
(23, 7, 'archive_patient', 217, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"217\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:47:17'),
(24, 7, 'print_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":218}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:52:33'),
(25, 7, 'archive_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"218\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:53:19'),
(26, 7, 'restore_patient', 218, '{\"patient_id\":\"218\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-02-02 23:53:23\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:53:23'),
(27, 7, 'archive_patient', 218, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"218\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:56:21'),
(28, 7, 'restore_patient', 218, '{\"patient_id\":\"218\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-02-02 23:56:25\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 23:56:25'),
(29, 7, 'staff_logout', NULL, '{\"full_name\":\"Jerecho Latosa\",\"username\":\"Jerecho\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 07:35:21'),
(30, 7, 'staff_login', NULL, '{\"full_name\":\"Jerecho Latosa\",\"username\":\"Jerecho\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 07:36:31'),
(31, 7, 'add_patient', 229, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Jaycar Otida\",\"patient_id\":\"229\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 08:15:23'),
(32, 7, 'add_patient', 230, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":\"230\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 08:37:00'),
(33, 7, 'add_patient', 231, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"231\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 09:07:53'),
(34, 7, 'print_patient', 230, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":230}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 12:32:39'),
(35, 7, 'print_patient', 230, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":230}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 12:34:38'),
(36, 7, 'print_patient', 231, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 14:24:36'),
(37, 7, 'print_patient', 231, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:44:44'),
(38, 7, 'print_patient', 231, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:45:15'),
(39, 7, 'add_patient', 232, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":\"232\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:49:51'),
(40, 7, 'print_patient', 231, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 22:26:53'),
(41, 7, 'archive_patient', 232, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Jerecho Latosa\",\"original_id\":\"232\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 22:28:00'),
(42, 7, 'restore_patient', 232, '{\"patient_id\":\"232\",\"patient_name\":\"Jerecho Latosa\",\"restore_time\":\"2026-02-04 22:29:14\",\"restored_by\":\"Jerecho Latosa\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 22:29:14'),
(43, 7, 'add_patient', 233, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"233\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 22:38:21'),
(44, 7, 'print_patient', 233, '{\"full_name\":\"Jerecho Latosa\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":233}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:51:17'),
(45, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 11:44:21'),
(46, 2, 'add_patient', 234, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":\"234\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:36:07'),
(47, 2, 'archive_patient', 234, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"original_id\":\"234\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:39:06'),
(48, 2, 'restore_patient', 234, '{\"patient_id\":\"234\",\"patient_name\":\"Jerecho Latosa\",\"restore_time\":\"2026-02-05 12:39:20\",\"restored_by\":\"Archiel  Rosel Cabanag\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:39:20'),
(49, 2, 'add_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":\"235\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:47:47'),
(50, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 15:38:30'),
(51, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 15:38:39'),
(52, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 16:38:43'),
(53, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 19:29:10'),
(54, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 00:56:32'),
(55, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-06 17:38:54'),
(56, 2, 'add_patient', 236, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel  Rosel Cabanag\",\"patient_id\":\"236\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-06 18:35:12'),
(57, 2, 'archive_patient', 236, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel  Rosel Cabanag\",\"original_id\":\"236\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-06 18:35:18'),
(58, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 12:07:40'),
(59, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:01:11'),
(60, 2, 'print_patient', 231, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:03:59'),
(61, 2, 'staff_logout', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:11:07'),
(62, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:14:23'),
(63, 2, 'staff_logout', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:38:30'),
(64, 2, 'staff_logout', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 16:38:30'),
(65, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 17:48:46'),
(66, 2, 'print_patient', 231, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 17:50:47'),
(67, 2, 'add_patient', 237, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"michael labos\",\"patient_id\":\"237\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 17:57:11'),
(68, 2, 'print_patient', 237, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"michael labos\",\"patient_id\":237}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 18:00:32'),
(69, 2, 'add_patient', 238, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"michael\",\"patient_id\":\"238\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 18:26:34'),
(70, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 18:28:56'),
(71, 2, 'add_patient', 239, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"evan\",\"patient_id\":\"239\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 18:40:06'),
(72, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-07 18:41:59'),
(73, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-08 11:37:25'),
(74, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-09 15:58:27'),
(75, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:14:34'),
(76, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 01:20:06'),
(77, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 01:56:40'),
(78, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 10:08:25'),
(79, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 11:46:11'),
(80, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 02:09:20'),
(81, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:00:46'),
(82, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 11:51:31'),
(83, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:41:57'),
(84, 2, 'print_patient', 231, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:11:53'),
(85, 2, 'add_patient', 240, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":\"240\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:41:32'),
(86, 2, 'print_patient', 240, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":240}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:44:34'),
(87, 2, 'print_patient', 235, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":235}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:50:41'),
(88, 2, 'print_patient', 240, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":240}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 16:04:53'),
(89, 2, 'add_patient', 241, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"Shiel Manloloyo\",\"patient_id\":\"241\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 16:34:04'),
(90, 2, 'archive_patient', 237, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"patient_name\":\"michael labos\",\"original_id\":\"237\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 16:58:45'),
(91, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 20:38:11'),
(92, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 04:58:31'),
(93, 2, 'staff_login', NULL, '{\"full_name\":\"Archiel  Rosel Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 10:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `staff_documents`
--

CREATE TABLE `staff_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL COMMENT 'in bytes',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_documents`
--

INSERT INTO `staff_documents` (`id`, `title`, `description`, `file_name`, `file_type`, `file_size`, `uploaded_by`, `uploaded_at`, `is_public`, `created_at`, `updated_at`) VALUES
(7, 'Health Records - 2025-2026', 'For Keeps', '1746553255_consultations_report_2025-05-01_to_2025-05-31.csv', '', 0, 1, '2025-05-06 17:40:55', 0, '2025-05-06 17:40:55', '2025-05-06 17:40:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `action_timestamp` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action_type`, `action_timestamp`, `ip_address`, `user_agent`) VALUES
(1, 186, 'logout', '2026-01-31 23:10:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(2, 185, 'login', '2026-01-31 23:10:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(3, 185, 'logout', '2026-02-01 00:07:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(4, 2, 'logout', '2026-02-01 01:10:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(5, 1, 'login', '2026-02-01 01:11:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(6, 186, 'login', '2026-02-01 20:27:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(7, 186, 'logout', '2026-02-01 21:25:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(8, 186, 'login', '2026-02-01 21:26:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(9, 1, 'logout', '2026-02-01 22:09:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(10, 2, 'login', '2026-02-01 22:09:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(11, 2, 'login', '2026-02-02 00:36:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.108.2 Chrome/142.0.7444.235 Electron/39.2.7 Safari/537.36'),
(12, 186, 'logout', '2026-02-02 09:00:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(13, 186, 'login', '2026-02-02 09:18:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(14, 186, 'logout', '2026-02-02 10:53:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(15, 185, 'login', '2026-02-02 10:54:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(16, 185, 'logout', '2026-02-02 14:19:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(17, 186, 'login', '2026-02-02 14:22:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(18, 1, 'logout', '2026-02-02 14:27:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(19, 2, 'login', '2026-02-02 14:27:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(20, 2, 'logout', '2026-02-02 14:27:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(21, 1, 'login', '2026-02-02 14:27:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(22, 186, 'logout', '2026-02-02 14:46:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(23, 185, 'login', '2026-02-02 14:46:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(24, 2, 'logout', '2026-02-02 15:57:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(25, 7, 'login', '2026-02-02 15:57:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(26, 185, 'logout', '2026-02-02 16:41:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(27, 187, 'login', '2026-02-02 16:42:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(28, 187, 'logout', '2026-02-02 16:48:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(29, 187, 'login', '2026-02-02 16:48:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(30, 187, 'logout', '2026-02-03 00:16:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(31, 185, 'login', '2026-02-03 00:17:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(32, 185, 'logout', '2026-02-03 00:17:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(33, 186, 'login', '2026-02-03 00:17:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(34, 186, 'logout', '2026-02-03 00:17:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(35, 187, 'login', '2026-02-03 00:17:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(36, 187, 'logout', '2026-02-03 00:18:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(37, 185, 'login', '2026-02-03 00:19:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(38, 7, 'logout', '2026-02-03 07:35:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(39, 7, 'login', '2026-02-03 07:36:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(40, 185, 'logout', '2026-02-03 07:36:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(41, 185, 'login', '2026-02-03 07:37:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(42, 185, 'logout', '2026-02-03 07:41:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(43, 187, 'login', '2026-02-03 07:41:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(44, 187, 'logout', '2026-02-03 08:15:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(45, 185, 'login', '2026-02-03 08:15:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(46, 1, 'login', '2026-02-03 08:15:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(47, 185, 'logout', '2026-02-03 08:16:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(48, 186, 'login', '2026-02-03 08:20:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(49, 186, 'logout', '2026-02-03 08:39:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(50, 188, 'login', '2026-02-03 08:39:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(51, 187, 'logout', '2026-02-04 12:32:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(52, 186, 'login', '2026-02-04 12:32:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(53, 186, 'logout', '2026-02-04 12:40:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(54, 185, 'login', '2026-02-04 12:40:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(55, 185, 'logout', '2026-02-04 13:18:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(56, 188, 'login', '2026-02-04 13:18:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(57, 1, 'login', '2026-02-04 14:29:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(58, 188, 'logout', '2026-02-04 17:36:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(59, 187, 'login', '2026-02-04 17:39:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(60, 187, 'logout', '2026-02-04 17:52:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(61, 189, 'login', '2026-02-04 17:53:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(62, 189, 'logout', '2026-02-04 22:06:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(63, 187, 'login', '2026-02-04 22:06:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(64, 187, 'logout', '2026-02-04 22:19:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(65, 188, 'login', '2026-02-04 22:19:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(66, 188, 'logout', '2026-02-04 22:34:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(67, 190, 'login', '2026-02-04 22:34:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(68, 190, 'logout', '2026-02-04 22:53:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(69, 190, 'login', '2026-02-04 22:59:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(70, 188, 'login', '2026-02-05 11:43:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(71, 2, 'login', '2026-02-05 11:44:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(72, 188, 'logout', '2026-02-05 11:44:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(73, 189, 'login', '2026-02-05 11:44:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(74, 1, 'login', '2026-02-05 12:34:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(75, 2, 'login', '2026-02-05 15:38:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(76, 188, 'login', '2026-02-05 15:56:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(77, 188, 'login', '2026-02-05 17:40:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(78, 2, 'login', '2026-02-05 19:29:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(79, 2, 'login', '2026-02-06 00:56:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(80, 2, 'login', '2026-02-06 17:38:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(81, 189, 'login', '2026-02-06 23:18:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(82, 2, 'login', '2026-02-07 12:07:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(83, 188, 'login', '2026-02-07 13:07:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(84, 2, 'login', '2026-02-07 16:01:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(85, 188, 'login', '2026-02-07 16:05:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(86, 2, 'logout', '2026-02-07 16:11:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(87, 1, 'login', '2026-02-07 16:11:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(88, 1, 'logout', '2026-02-07 16:14:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(89, 2, 'login', '2026-02-07 16:14:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(90, 2, 'logout', '2026-02-07 16:38:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(91, 2, 'logout', '2026-02-07 16:38:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(92, 1, 'login', '2026-02-07 16:38:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(93, 188, 'login', '2026-02-07 17:47:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(94, 1, 'logout', '2026-02-07 17:48:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(95, 2, 'login', '2026-02-07 17:48:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(96, 188, 'login', '2026-02-08 10:50:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(97, 1, 'login', '2026-02-08 11:09:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(98, 2, 'login', '2026-02-08 11:37:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(99, 188, 'login', '2026-02-08 19:54:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(100, 2, 'login', '2026-02-09 15:58:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(101, 188, 'login', '2026-02-09 16:03:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(102, 188, 'login', '2026-02-09 23:02:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(103, 2, 'login', '2026-02-09 23:14:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(104, 1, 'login', '2026-02-09 23:20:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(105, 188, 'login', '2026-02-10 00:47:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(106, 188, 'logout', '2026-02-10 00:49:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(107, 188, 'login', '2026-02-10 01:11:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(108, 188, 'logout', '2026-02-10 01:11:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(109, 188, 'login', '2026-02-10 01:17:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(110, 2, 'login', '2026-02-10 01:20:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(111, 1, 'login', '2026-02-10 01:54:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(112, 188, 'logout', '2026-02-10 01:57:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(113, 188, 'login', '2026-02-10 01:58:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(114, 2, 'login', '2026-02-10 10:08:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(115, 188, 'login', '2026-02-10 11:09:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(116, 2, 'login', '2026-02-10 11:46:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(117, 188, 'login', '2026-02-11 00:26:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(118, 2, 'login', '2026-02-11 02:09:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(119, 188, 'login', '2026-02-11 07:58:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(120, 2, 'login', '2026-02-11 08:00:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(121, 188, 'logout', '2026-02-11 08:33:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(122, 189, 'login', '2026-02-11 08:33:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(123, 189, 'logout', '2026-02-11 09:16:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(124, 188, 'login', '2026-02-11 09:16:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(125, 2, 'login', '2026-02-11 11:51:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(126, 188, 'login', '2026-02-11 14:40:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(127, 2, 'login', '2026-02-11 14:41:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(128, 1, 'login', '2026-02-11 16:35:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(129, 188, 'logout', '2026-02-11 16:37:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(130, 192, 'login', '2026-02-11 16:37:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(131, 188, 'login', '2026-02-11 20:36:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(132, 2, 'login', '2026-02-11 20:38:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(133, 188, 'login', '2026-02-11 21:39:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(134, 188, 'logout', '2026-02-11 23:12:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(135, 185, 'login', '2026-02-11 23:13:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(136, 185, 'logout', '2026-02-11 23:30:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(137, 188, 'login', '2026-02-11 23:30:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(138, 188, 'login', '2026-02-12 04:43:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(139, 2, 'login', '2026-02-12 04:58:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(140, 188, 'logout', '2026-02-12 06:04:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(141, 193, 'login', '2026-02-12 06:04:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(142, 2, 'login', '2026-02-12 10:43:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(143, 193, 'logout', '2026-02-12 11:47:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(144, 188, 'login', '2026-02-12 11:47:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(145, 188, 'login', '2026-02-12 15:23:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(146, 188, 'login', '2026-02-12 16:27:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(147, 188, 'login', '2026-02-12 16:55:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `user_announcements`
--

CREATE TABLE `user_announcements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `status` enum('accepted','dismissed') NOT NULL,
  `response_date` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_announcements`
--

INSERT INTO `user_announcements` (`id`, `user_id`, `announcement_id`, `status`, `response_date`, `updated_at`) VALUES
(96, 188, 156, 'accepted', '2026-02-12 05:45:25', '2026-02-12 05:45:25'),
(97, 188, 157, 'accepted', '2026-02-12 07:10:56', '2026-02-12 07:10:56'),
(98, 188, 159, 'accepted', '2026-02-12 07:11:14', '2026-02-12 07:11:14'),
(99, 188, 158, 'accepted', '2026-02-12 07:14:21', '2026-02-12 07:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_appointments`
--

CREATE TABLE `user_appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_linking_history`
--
ALTER TABLE `account_linking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident` (`resident_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcement_messages`
--
ALTER TABLE `announcement_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_announcement` (`announcement_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `announcement_targets`
--
ALTER TABLE `announcement_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `child_health_records`
--
ALTER TABLE `child_health_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `child_health_results`
--
ALTER TABLE `child_health_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_health_record_id` (`child_health_record_id`);

--
-- Indexes for table `child_immunizations`
--
ALTER TABLE `child_immunizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_health_record_id` (`child_health_record_id`);

--
-- Indexes for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `deleted_patients`
--
ALTER TABLE `deleted_patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `existing_info_patients`
--
ALTER TABLE `existing_info_patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `present_pregnant_records`
--
ALTER TABLE `present_pregnant_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_linked_by` (`linked_by`),
  ADD KEY `patient_record_id` (`patient_record_id`);

--
-- Indexes for table `sitio1_announcements`
--
ALTER TABLE `sitio1_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `sitio1_appointments`
--
ALTER TABLE `sitio1_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `sitio1_consultations`
--
ALTER TABLE `sitio1_consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `sitio1_patients`
--
ALTER TABLE `sitio1_patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_patients_user_id` (`user_id`);

--
-- Indexes for table `sitio1_staff`
--
ALTER TABLE `sitio1_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sitio1_users`
--
ALTER TABLE `sitio1_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_users_patient_id` (`patient_record_id`);

--
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_announcements`
--
ALTER TABLE `user_announcements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_announcement` (`user_id`,`announcement_id`),
  ADD KEY `user_announcements_ibfk_2` (`announcement_id`);

--
-- Indexes for table `user_appointments`
--
ALTER TABLE `user_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_linking_history`
--
ALTER TABLE `account_linking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcement_messages`
--
ALTER TABLE `announcement_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_targets`
--
ALTER TABLE `announcement_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `child_health_records`
--
ALTER TABLE `child_health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `child_health_results`
--
ALTER TABLE `child_health_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `child_immunizations`
--
ALTER TABLE `child_immunizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `deleted_patients`
--
ALTER TABLE `deleted_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `existing_info_patients`
--
ALTER TABLE `existing_info_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `present_pregnant_records`
--
ALTER TABLE `present_pregnant_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_announcements`
--
ALTER TABLE `sitio1_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `sitio1_appointments`
--
ALTER TABLE `sitio1_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_consultations`
--
ALTER TABLE `sitio1_consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_patients`
--
ALTER TABLE `sitio1_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `sitio1_staff`
--
ALTER TABLE `sitio1_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sitio1_users`
--
ALTER TABLE `sitio1_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `staff_documents`
--
ALTER TABLE `staff_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `user_announcements`
--
ALTER TABLE `user_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `user_appointments`
--
ALTER TABLE `user_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement_messages`
--
ALTER TABLE `announcement_messages`
  ADD CONSTRAINT `announcement_messages_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `sitio1_announcements` (`id`),
  ADD CONSTRAINT `announcement_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `sitio1_users` (`id`);

--
-- Constraints for table `announcement_targets`
--
ALTER TABLE `announcement_targets`
  ADD CONSTRAINT `announcement_targets_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `sitio1_announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_targets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `child_health_results`
--
ALTER TABLE `child_health_results`
  ADD CONSTRAINT `child_health_results_ibfk_1` FOREIGN KEY (`child_health_record_id`) REFERENCES `child_health_records` (`id`);

--
-- Constraints for table `child_immunizations`
--
ALTER TABLE `child_immunizations`
  ADD CONSTRAINT `child_immunizations_ibfk_1` FOREIGN KEY (`child_health_record_id`) REFERENCES `child_health_records` (`id`);

--
-- Constraints for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  ADD CONSTRAINT `consultation_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `sitio1_patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_consultation_notes_created_by_staff` FOREIGN KEY (`created_by`) REFERENCES `sitio1_staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `existing_info_patients`
--
ALTER TABLE `existing_info_patients`
  ADD CONSTRAINT `existing_info_patients_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `sitio1_patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD CONSTRAINT `patient_visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `sitio1_patients` (`id`),
  ADD CONSTRAINT `patient_visits_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  ADD CONSTRAINT `sitio1_account_linking_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sitio1_account_linking_history_ibfk_2` FOREIGN KEY (`patient_record_id`) REFERENCES `sitio1_patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sitio1_account_linking_history_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `sitio1_staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sitio1_announcements`
--
ALTER TABLE `sitio1_announcements`
  ADD CONSTRAINT `sitio1_announcements_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `sitio1_appointments`
--
ALTER TABLE `sitio1_appointments`
  ADD CONSTRAINT `sitio1_appointments_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `sitio1_consultations`
--
ALTER TABLE `sitio1_consultations`
  ADD CONSTRAINT `sitio1_consultations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`),
  ADD CONSTRAINT `sitio1_consultations_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `sitio1_patients`
--
ALTER TABLE `sitio1_patients`
  ADD CONSTRAINT `fk_patient_user` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sitio1_patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`),
  ADD CONSTRAINT `sitio1_patients_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `sitio1_staff`
--
ALTER TABLE `sitio1_staff`
  ADD CONSTRAINT `sitio1_staff_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `sitio1_users`
--
ALTER TABLE `sitio1_users`
  ADD CONSTRAINT `sitio1_users_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `sitio1_staff` (`id`);

--
-- Constraints for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD CONSTRAINT `staff_documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_announcements`
--
ALTER TABLE `user_announcements`
  ADD CONSTRAINT `user_announcements_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `sitio1_announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_appointments`
--
ALTER TABLE `user_appointments`
  ADD CONSTRAINT `user_appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sitio1_users` (`id`),
  ADD CONSTRAINT `user_appointments_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `sitio1_appointments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
