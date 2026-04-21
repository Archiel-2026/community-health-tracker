-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 07:48 AM
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
-- Table structure for table `announcement_analytics_archive`
--

CREATE TABLE `announcement_analytics_archive` (
  `id` int(11) NOT NULL,
  `original_announcement_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `audience_type` enum('landing_page','public','specific') NOT NULL,
  `announcement_status` varchar(32) DEFAULT NULL,
  `post_date` datetime NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `accepted_count` int(11) NOT NULL DEFAULT 0,
  `dismissed_count` int(11) NOT NULL DEFAULT 0,
  `total_count` int(11) NOT NULL DEFAULT 0,
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_by_staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(234, 350, 222, '2026-04-02 12:57:52'),
(235, 351, 222, '2026-04-02 13:49:12'),
(236, 353, 229, '2026-04-03 00:36:31'),
(237, 354, 230, '2026-04-03 00:42:39'),
(238, 355, 229, '2026-04-03 11:49:10'),
(239, 356, 229, '2026-04-03 11:56:19'),
(240, 357, 229, '2026-04-03 11:58:54'),
(241, 358, 232, '2026-04-03 14:41:21'),
(242, 359, 232, '2026-04-03 14:49:55'),
(243, 360, 232, '2026-04-03 15:39:52'),
(244, 361, 222, '2026-04-04 11:06:55'),
(245, 362, 232, '2026-04-04 16:26:56'),
(246, 364, 222, '2026-04-06 03:48:23'),
(247, 365, 222, '2026-04-06 03:49:44'),
(248, 366, 223, '2026-04-06 05:59:12'),
(249, 367, 223, '2026-04-06 06:04:49'),
(250, 368, 235, '2026-04-07 07:19:03'),
(251, 370, 223, '2026-04-07 15:26:29'),
(252, 371, 232, '2026-04-08 08:28:09'),
(253, 372, 222, '2026-04-08 23:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `viewer_token` varchar(128) NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_views`
--

INSERT INTO `announcement_views` (`id`, `announcement_id`, `viewer_token`, `viewed_at`) VALUES
(1, 302, '68b0c39f25b46e06e231b46087950abd', '2026-03-29 00:09:34'),
(9, 302, 'ae27af8da252c04a13c0a46a30750824', '2026-03-29 01:03:38'),
(10, 302, 'fb31d00c271948ac50cb3e673623f8d0', '2026-03-29 01:27:14'),
(13, 302, '9a7717bd6105c310ac2cb43b97c61cad', '2026-03-29 21:07:50'),
(14, 311, 'a1227c3f003841bad62578e3e052530f', '2026-03-29 22:30:17'),
(15, 332, 'fa96df75b2aa63fcfe59deb1ddba1342', '2026-04-01 23:41:28'),
(16, 319, '64d31d4e127eb0f2a992c673c0e30d00', '2026-04-02 15:32:18'),
(17, 363, '5a75bdf9d8c862d3bcb431627e1e8f44', '2026-04-05 01:11:47'),
(18, 369, '6d29e9bf724b98367448de599178f67c', '2026-04-07 16:40:38'),
(20, 373, '28a8b31cf0cc2c16ccca3ce9d8c32421', '2026-04-09 21:28:50'),
(21, 375, 'a792a22563307a9bd23352d939ae9500', '2026-04-11 11:59:29'),
(22, 377, 'e1b65d5acf842b8f8963eb3091ce7f45', '2026-04-12 03:28:20'),
(24, 378, 'cfa06a20db2cced8495316cfa801bef9', '2026-04-14 14:43:12');

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
-- Table structure for table `barangay_sitio_mapping`
--

CREATE TABLE `barangay_sitio_mapping` (
  `id` int(11) NOT NULL,
  `barangay_name` varchar(255) NOT NULL,
  `sitios` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_sitio_mapping`
--

INSERT INTO `barangay_sitio_mapping` (`id`, `barangay_name`, `sitios`, `created_at`, `updated_at`) VALUES
(1, 'Barangay Luz', 'Kalinao,Nangka,Lubi,Sta. Cruz,Regla,Abellana,Sto.niño l,Sto.niño ll,Sto.niño lll,Zapatera,Mabuhay,San Vicente,City Central,San Antonio,San Roque', '2026-04-14 06:35:14', NULL);

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
  `vit_a_1` date DEFAULT NULL,
  `vit_a_2` date DEFAULT NULL,
  `vit_a_3` date DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `city_health_report_logs`
--

CREATE TABLE `city_health_report_logs` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `export_type` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `city_health_report_logs`
--

INSERT INTO `city_health_report_logs` (`id`, `staff_id`, `full_name`, `action_type`, `export_type`, `created_at`) VALUES
(1, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:13:25'),
(2, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:13:34'),
(3, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:13:46'),
(4, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:14:07'),
(5, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:18:03'),
(6, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:22:12'),
(7, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:22:18'),
(8, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:23:14'),
(9, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:24:54'),
(10, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:25:42'),
(11, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:27:39'),
(12, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:28:10'),
(13, 9, 'Leandro T. Labos', 'generate_full_report', 'full_report', '2026-02-22 10:30:37');

-- --------------------------------------------------------

--
-- Table structure for table `consultation_notes`
--

CREATE TABLE `consultation_notes` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `consultation_date` date NOT NULL,
  `consultation_time` time DEFAULT NULL,
  `next_consultation_date` date DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_by_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultation_notes`
--

INSERT INTO `consultation_notes` (`id`, `patient_id`, `note`, `consultation_date`, `consultation_time`, `next_consultation_date`, `doctor_name`, `created_by`, `created_at`, `updated_at`, `status`, `completed_at`, `completed_by`, `completed_by_name`) VALUES
(146, 842, 'Hello Sir', '2026-04-21', NULL, '2026-04-08', 'Dr. Willy Ong', 15, '2026-04-21 02:53:46', NULL, 'pending', NULL, NULL, NULL),
(147, 842, 'Hello Sir', '2026-04-21', NULL, '2026-04-22', 'Dr. Will Ong III', 15, '2026-04-21 02:54:18', NULL, 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `consultation_notes_backup`
--

CREATE TABLE `consultation_notes_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `patient_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `consultation_date` date NOT NULL,
  `consultation_time` time DEFAULT NULL,
  `next_consultation_date` date DEFAULT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `doctor_name` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultation_notes_backup`
--

INSERT INTO `consultation_notes_backup` (`id`, `patient_id`, `note`, `consultation_date`, `consultation_time`, `next_consultation_date`, `status`, `doctor_name`, `created_by`, `created_at`, `updated_at`, `completed_at`, `completed_by`) VALUES
(97, 811, 'test1test1test1', '2026-03-10', NULL, '2026-03-28', 'missed', 'Dr. test1', 12, '2026-03-10 15:30:38', '2026-04-06 14:54:30', NULL, NULL),
(98, 811, 'test2test2test2', '2026-03-10', NULL, '2026-04-03', 'missed', 'Dr. test2', 12, '2026-03-10 15:31:02', '2026-04-06 14:54:30', NULL, NULL),
(99, 811, 'test3test3test3', '2026-03-10', NULL, '2026-03-25', 'missed', 'Dr. test3', 12, '2026-03-10 15:32:33', '2026-04-06 14:54:30', NULL, NULL),
(100, 811, 'test3test3test3', '2026-03-10', NULL, '2026-03-11', 'missed', 'Dr. test4', 12, '2026-03-10 15:32:48', '2026-04-06 14:54:30', NULL, NULL),
(101, 811, 'testr2testr2testr2', '2026-03-10', NULL, '2026-03-19', 'missed', 'Dr. test5', 12, '2026-03-10 15:55:40', '2026-04-06 14:54:30', NULL, NULL),
(102, 814, 'Testing1Testing1Testing1Testing1', '2026-03-10', NULL, '2026-03-20', 'missed', 'Dr. Testing1', 12, '2026-03-10 16:04:30', '2026-04-06 14:54:30', NULL, NULL),
(103, 814, 'Testing2Testing2Testing2Testing2', '2026-03-10', NULL, '2026-03-12', 'missed', 'Dr. Testing2', 12, '2026-03-10 16:04:49', '2026-04-06 14:54:30', NULL, NULL),
(104, 816, 'hello the patient has rabies', '2026-03-11', NULL, '2026-03-24', 'missed', 'Dr. wakwak', 12, '2026-03-11 04:34:01', '2026-04-06 14:54:30', NULL, NULL),
(105, 816, 'salve need to take his medication on time', '2026-03-11', NULL, '2026-03-26', 'missed', 'Dr. leandro labos', 12, '2026-03-11 04:34:41', '2026-04-06 14:54:30', NULL, NULL),
(106, 816, 'arda needs to see a brain doctor', '2026-03-11', NULL, '2026-03-28', 'missed', 'Dr. miras', 12, '2026-03-11 04:35:21', '2026-04-06 14:54:30', NULL, NULL),
(108, 321, 'You have high fever', '2026-03-14', NULL, '2026-03-25', 'missed', 'Dr. Luzviminda Trinidad', 13, '2026-03-14 08:35:05', '2026-04-06 14:54:30', NULL, NULL),
(109, 814, 'Testing for review', '2026-03-14', NULL, '2026-03-21', 'missed', 'Dr. Allan Cayetano', 13, '2026-03-14 10:03:20', '2026-04-06 14:54:30', NULL, NULL),
(111, 323, 'Hello naa kay hubag sa lubot', '2026-03-29', NULL, '2026-03-30', 'missed', 'Dr. Lance Christine Gallardo', 13, '2026-03-29 14:12:46', '2026-04-06 14:54:30', NULL, NULL),
(112, 323, 'Nibalhin sa agtang', '2026-03-29', NULL, '2026-03-31', 'missed', 'Dr. Archiel R. Cabanag', 13, '2026-03-29 14:14:44', '2026-04-06 14:54:30', NULL, NULL),
(113, 817, 'Helllo sir kumustas', '2026-04-03', NULL, '2026-04-04', 'missed', 'Dr. Arlene Okora', 13, '2026-04-03 11:58:07', '2026-04-06 14:54:30', NULL, NULL),
(114, 820, 'Hello sir', '2026-04-03', NULL, '2026-04-04', 'missed', 'Dr. Gina Tan', 13, '2026-04-03 14:35:01', '2026-04-06 14:54:30', NULL, NULL),
(115, 819, 'asdasdsadas', '2026-04-03', NULL, '2026-04-04', 'missed', 'Dr. Willy Ong', 13, '2026-04-03 15:12:56', '2026-04-06 14:54:30', NULL, NULL),
(116, 820, 'asdsad', '2026-04-04', NULL, NULL, 'missed', 'Dr. asdsad', 13, '2026-04-04 07:55:49', '2026-04-06 15:37:58', NULL, NULL),
(119, 815, 'adsasd', '2026-04-07', NULL, '2026-04-08', 'pending', 'Dr. asdsad', 13, '2026-04-07 05:36:38', NULL, NULL, NULL);

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
  `deleted_by` int(11) DEFAULT NULL,
  `blood_pressure_systolic` varchar(10) DEFAULT NULL,
  `blood_pressure_diastolic` varchar(10) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `medical_conditions_other` varchar(500) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `student_last_name` varchar(255) DEFAULT NULL,
  `student_first_name` varchar(255) DEFAULT NULL,
  `student_middle_name` varchar(255) DEFAULT NULL,
  `student_birthdate` date DEFAULT NULL,
  `student_religion` varchar(255) DEFAULT NULL,
  `student_home_address` text DEFAULT NULL,
  `student_occupation` varchar(255) DEFAULT NULL,
  `student_effective_date` date DEFAULT NULL,
  `student_sex` varchar(10) DEFAULT NULL,
  `student_nickname` varchar(255) DEFAULT NULL,
  `student_home_phone` varchar(50) DEFAULT NULL,
  `student_office_phone` varchar(50) DEFAULT NULL,
  `student_fax_number` varchar(50) DEFAULT NULL,
  `student_mobile_number` varchar(50) DEFAULT NULL,
  `student_email_address` varchar(255) DEFAULT NULL,
  `parent_guardian_name` varchar(255) DEFAULT NULL,
  `parent_guardian_occupation` varchar(255) DEFAULT NULL,
  `student_physician_name` varchar(255) DEFAULT NULL,
  `student_physician_specialty` varchar(255) DEFAULT NULL,
  `student_physician_office_address` text DEFAULT NULL,
  `student_physician_office_number` varchar(50) DEFAULT NULL,
  `student_good_health` varchar(10) DEFAULT NULL,
  `student_under_treatment` varchar(10) DEFAULT NULL,
  `student_treatment_condition` text DEFAULT NULL,
  `student_serious_illness_surgery` varchar(10) DEFAULT NULL,
  `student_serious_illness_details` text DEFAULT NULL,
  `student_hospitalized` varchar(10) DEFAULT NULL,
  `student_hospitalization_details` text DEFAULT NULL,
  `student_taking_medication` varchar(10) DEFAULT NULL,
  `student_medication_details` text DEFAULT NULL,
  `student_uses_tobacco` varchar(10) DEFAULT NULL,
  `student_uses_alcohol_drugs` varchar(10) DEFAULT NULL,
  `student_has_allergies` varchar(10) DEFAULT NULL,
  `student_allergy_items` text DEFAULT NULL,
  `student_allergy_other` varchar(500) DEFAULT NULL,
  `student_bleeding_time` varchar(100) DEFAULT NULL,
  `student_is_pregnant` varchar(10) DEFAULT NULL,
  `student_is_nursing` varchar(10) DEFAULT NULL,
  `student_takes_birth_control` varchar(10) DEFAULT NULL,
  `student_menarche` varchar(50) DEFAULT NULL,
  `student_lmp` varchar(50) DEFAULT NULL,
  `student_gravida` varchar(50) DEFAULT NULL,
  `student_para` varchar(50) DEFAULT NULL,
  `student_abortion` varchar(50) DEFAULT NULL,
  `student_conditions` text DEFAULT NULL,
  `student_condition_other` varchar(500) DEFAULT NULL,
  `student_signature_name` varchar(255) DEFAULT NULL,
  `sitio` varchar(255) DEFAULT NULL,
  `civil_status` varchar(100) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `existing_info_patients`
--

CREATE TABLE `existing_info_patients` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `student_last_name` varchar(100) DEFAULT NULL,
  `student_first_name` varchar(100) DEFAULT NULL,
  `student_middle_name` varchar(100) DEFAULT NULL,
  `student_birthdate` date DEFAULT NULL,
  `student_religion` varchar(100) DEFAULT NULL,
  `student_home_address` text DEFAULT NULL,
  `student_occupation` varchar(150) DEFAULT NULL,
  `student_effective_date` date DEFAULT NULL,
  `student_sex` enum('M','F') DEFAULT NULL,
  `student_nickname` varchar(100) DEFAULT NULL,
  `student_home_phone` varchar(50) DEFAULT NULL,
  `student_office_phone` varchar(50) DEFAULT NULL,
  `student_fax_number` varchar(50) DEFAULT NULL,
  `student_mobile_number` varchar(50) DEFAULT NULL,
  `student_email_address` varchar(150) DEFAULT NULL,
  `parent_guardian_name` varchar(150) DEFAULT NULL,
  `parent_guardian_occupation` varchar(150) DEFAULT NULL,
  `student_physician_name` varchar(150) DEFAULT NULL,
  `student_physician_specialty` varchar(150) DEFAULT NULL,
  `student_physician_office_address` text DEFAULT NULL,
  `student_physician_office_number` varchar(50) DEFAULT NULL,
  `student_good_health` enum('yes','no') DEFAULT NULL,
  `student_under_treatment` enum('yes','no') DEFAULT NULL,
  `student_treatment_condition` text DEFAULT NULL,
  `student_serious_illness_surgery` enum('yes','no') DEFAULT NULL,
  `student_serious_illness_details` text DEFAULT NULL,
  `student_hospitalized` enum('yes','no') DEFAULT NULL,
  `student_hospitalization_details` text DEFAULT NULL,
  `student_taking_medication` enum('yes','no') DEFAULT NULL,
  `student_medication_details` text DEFAULT NULL,
  `student_uses_tobacco` enum('yes','no') DEFAULT NULL,
  `student_uses_alcohol_drugs` enum('yes','no') DEFAULT NULL,
  `student_has_allergies` enum('yes','no') DEFAULT NULL,
  `student_allergy_items` text DEFAULT NULL,
  `student_allergy_other` varchar(255) DEFAULT NULL,
  `student_bleeding_time` varchar(100) DEFAULT NULL,
  `student_is_pregnant` enum('yes','no') DEFAULT NULL,
  `student_is_nursing` enum('yes','no') DEFAULT NULL,
  `student_takes_birth_control` enum('yes','no') DEFAULT NULL,
  `student_conditions` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `medical_conditions_other` varchar(500) DEFAULT NULL,
  `student_condition_other` varchar(255) DEFAULT NULL,
  `student_menopause` varchar(100) DEFAULT NULL,
  `student_menarche` varchar(100) DEFAULT NULL,
  `student_lmp` varchar(100) DEFAULT NULL,
  `student_gravida` varchar(100) DEFAULT NULL,
  `student_para` varchar(100) DEFAULT NULL,
  `student_abortion` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `blood_type` varchar(3) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `blood_pressure_systolic` varchar(10) DEFAULT NULL,
  `blood_pressure_diastolic` varchar(10) DEFAULT NULL,
  `immunization_record` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `family_medical_history` text DEFAULT NULL,
  `student_signature_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `existing_info_patients`
--

INSERT INTO `existing_info_patients` (`id`, `patient_id`, `student_last_name`, `student_first_name`, `student_middle_name`, `student_birthdate`, `student_religion`, `student_home_address`, `student_occupation`, `student_effective_date`, `student_sex`, `student_nickname`, `student_home_phone`, `student_office_phone`, `student_fax_number`, `student_mobile_number`, `student_email_address`, `parent_guardian_name`, `parent_guardian_occupation`, `student_physician_name`, `student_physician_specialty`, `student_physician_office_address`, `student_physician_office_number`, `student_good_health`, `student_under_treatment`, `student_treatment_condition`, `student_serious_illness_surgery`, `student_serious_illness_details`, `student_hospitalized`, `student_hospitalization_details`, `student_taking_medication`, `student_medication_details`, `student_uses_tobacco`, `student_uses_alcohol_drugs`, `student_has_allergies`, `student_allergy_items`, `student_allergy_other`, `student_bleeding_time`, `student_is_pregnant`, `student_is_nursing`, `student_takes_birth_control`, `student_conditions`, `medical_conditions`, `medical_conditions_other`, `student_condition_other`, `student_menopause`, `student_menarche`, `student_lmp`, `student_gravida`, `student_para`, `student_abortion`, `gender`, `height`, `weight`, `blood_type`, `allergies`, `medical_history`, `current_medications`, `family_history`, `temperature`, `blood_pressure`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `immunization_record`, `chronic_conditions`, `family_medical_history`, `student_signature_name`, `created_at`, `updated_at`) VALUES
(10, 838, 'Casola', 'Ronie', 'Solo', '1999-06-24', 'Roman Catholic', 'Tisa Cebu City Near Labangon', 'Student Teacher', '2026-04-07', 'M', 'Jaycar', '09312312421', '09816497664', '5234324', '09312312421', 'roniecasola@gmail.com', 'Jociel Studio', 'Jociel Studio', 'Willy Ong', 'Special', 'Tisa Cebu City Near Labangon', '4543534', 'yes', 'yes', 'Testing for review', 'yes', 'Testing for review', 'yes', 'Testing for review', 'yes', 'Testing for review', NULL, 'yes', 'yes', 'Animals, Testing for review', 'Testing for review', 'Note for Testing', NULL, NULL, NULL, NULL, '[\"High Blood Pressure\",\"Testing for review\"]', 'Testing for review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 45.00, 45.00, 'A+', NULL, NULL, NULL, NULL, 45.00, '120/80', '120', '80', NULL, NULL, NULL, 'Note for Testing', '2026-04-20 17:45:07', '2026-04-20 17:45:07'),
(11, 837, 'Otida', 'Jaycar', 'Sulu', '2002-05-26', 'Roman Catholic', 'Tisa Cebu City Near Labangon', 'Student', '2002-06-13', 'M', 'Jaycar', '09312312421', '', '5234324', '09312312421', 'jaycarotida@gmail.com', 'Jociel Studio', 'Jociel Studio', 'Willy Ong III', 'Special', 'Tisa Cebu City Near Labangon', '4543534', 'yes', 'yes', '', 'yes', '', 'yes', '', 'yes', '', 'yes', 'yes', 'yes', NULL, '', 'Note for Testing', '', '', '', NULL, '[\"High Blood Pressure\",\"Testing for review\"]', 'Testing for review', '', NULL, '', '', '', '', '', NULL, 45.00, 45.00, 'A+', NULL, NULL, NULL, NULL, 45.00, '120/80', '120', '80', NULL, NULL, NULL, 'Kristine Bancure', '2026-04-20 17:46:54', '2026-04-20 17:47:00'),
(12, 842, 'Cabanag', 'Archiel', 'Sulu', '2002-05-26', 'Roman Catholic', 'Duljo Fatima, Cebu City', 'Student', '2024-01-25', 'M', 'Archiel', '09312312421', '09816497664', '5234324', '09312312421', 'cabanagarchiel@gmail.com', 'Jociel Studio', 'Jociel Studio', 'Willy Ong III', 'Special Child', 'Tisa Cebu City Near Labangon', '4543534', 'yes', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'yes', 'yes', 'Pollen, Testing for review', 'Testing for review', 'Note for Testing', '', '', '', NULL, '[\"High Blood Pressure\",\"Testing for review\"]', 'Testing for review', '', NULL, '', '', '', '', '', NULL, 45.00, 45.00, 'A+', NULL, NULL, NULL, NULL, 45.00, '120/80', '120', '80', NULL, NULL, NULL, 'Kristine Bancure', '2026-04-21 00:57:07', '2026-04-21 02:52:26'),
(13, 843, 'Labos', 'Leandro', 'Sulu', '1999-05-05', 'Roman Catholic', 'Tisa Cebu City Near Labangon', 'Student', '2026-04-01', 'M', 'Leandro', '09312312421', '09816497664', '5234324', '09312312421', 'leandrolabos822@gmail.com', 'Jociel Studio', 'Jociel Studio', 'Willy Ong III', 'Special Child', 'Tisa Cebu City Near Labangon', '4543534', 'yes', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'None of Above', 'yes', 'yes', 'yes', 'Food, Dust', '', 'Note for Testing', '', '', '', NULL, '[\"High Blood Pressure\",\"Low Blood Pressure\",\"Epilepsy \\/ Convulsions\",\"Sexually Transmitted Disease\",\"Stomach Troubles \\/ Ulcer\",\"Testing for review\"]', 'Testing for review', '', NULL, '', '', '', '', '', NULL, 45.00, 45.00, 'A+', NULL, NULL, NULL, NULL, 45.00, '120/80', '120', '80', NULL, NULL, NULL, 'Kristine Bancure', '2026-04-21 05:33:33', '2026-04-21 05:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `health_campaigns`
--

CREATE TABLE `health_campaigns` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `report_logs`
--

CREATE TABLE `report_logs` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `export_type` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resident_activity_log`
--

CREATE TABLE `resident_activity_log` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_activity_log`
--

INSERT INTO `resident_activity_log` (`id`, `resident_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 232, 'accept_announcement', 359, '{\"announcement_title\":\"sdfds\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-03 23:38:53'),
(2, 222, 'dismiss_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\",\"status\":\"dismissed\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-04 07:08:51'),
(3, 232, 'view_announcement', 362, '{\"announcement_title\":\"Hello Clint\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 00:27:13'),
(4, 232, 'view_announcement', 362, '{\"announcement_title\":\"Hello Clint\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 00:55:12'),
(5, 232, 'accept_announcement', 362, '{\"announcement_title\":\"Hello Clint\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 00:55:14'),
(6, 227, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 08:05:06'),
(7, 227, 'accept_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 08:05:07'),
(8, 227, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-05 08:05:13'),
(9, 222, 'view_announcement', 364, '{\"announcement_title\":\"Hello Test\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:51:26'),
(10, 222, 'accept_announcement', 364, '{\"announcement_title\":\"Hello Test\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:51:41'),
(11, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:53:17'),
(12, 223, 'view_announcement', 366, '{\"announcement_title\":\"Warren Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 13:59:43'),
(13, 223, 'view_announcement', 366, '{\"announcement_title\":\"Warren Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:00:00'),
(14, 223, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:00:30'),
(15, 223, 'view_announcement', 366, '{\"announcement_title\":\"Warren Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:02:12'),
(16, 223, 'accept_announcement', 366, '{\"announcement_title\":\"Warren Miras\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:02:17'),
(17, 223, 'view_announcement', 366, '{\"announcement_title\":\"Warren Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:02:22'),
(18, 223, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:02:39'),
(19, 223, 'dismiss_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\",\"status\":\"dismissed\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:02:44'),
(20, 223, 'view_announcement', 366, '{\"announcement_title\":\"Warren Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:03:01'),
(21, 223, 'view_announcement', 367, '{\"announcement_title\":\"Warren- Specific Resident\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:05:33'),
(22, 235, 'view_announcement', 368, '{\"announcement_title\":\"Jhea Claire Oropesa Announcement\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 15:22:07'),
(23, 235, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 16:38:36'),
(24, 235, 'view_announcement', 368, '{\"announcement_title\":\"Jhea Claire Oropesa Announcement\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 16:38:42'),
(25, 235, 'accept_announcement', 368, '{\"announcement_title\":\"Jhea Claire Oropesa Announcement\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 16:38:44'),
(26, 223, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 23:25:47'),
(27, 223, 'view_announcement', 370, '{\"announcement_title\":\"Warren Announcement\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-07 23:26:47'),
(28, 222, 'view_announcement', 372, '{\"announcement_title\":\"Lab Result\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 07:52:43'),
(29, 222, 'accept_announcement', 372, '{\"announcement_title\":\"Lab Result\",\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 07:53:21'),
(30, 222, 'view_announcement', 372, '{\"announcement_title\":\"Lab Result\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 07:53:34'),
(31, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 07:53:48'),
(32, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 07:54:19'),
(33, 222, 'view_announcement', 374, '{\"announcement_title\":\"For All Resident\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-09 21:32:51'),
(34, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-10 06:50:42'),
(35, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-10 06:52:57'),
(36, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 00:35:42'),
(37, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 00:39:42'),
(38, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-12 00:39:47'),
(39, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-14 14:42:24'),
(40, 222, 'view_announcement', 352, '{\"announcement_title\":\"Follow-Up Consultation Required\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-14 16:47:23');

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
-- Table structure for table `sitio1_activity_log`
--

CREATE TABLE `sitio1_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_activity_log`
--

INSERT INTO `sitio1_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, NULL, 'password_change', 'Changed password for resident: Archiel Cabanag', '::1', '2026-02-18 12:07:25'),
(2, NULL, 'password_change', 'Changed password for staff: Leandro T. Labos', '::1', '2026-02-18 12:16:37'),
(3, NULL, 'password_change', 'Changed password for resident: Archiel Cabanag', '::1', '2026-02-18 12:17:58'),
(4, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-02-18 16:01:29'),
(5, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-02-20 13:26:21'),
(6, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-02-21 08:19:29'),
(7, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-02-21 09:08:38'),
(8, NULL, 'resident_created', 'Created resident account: Jaycar Otida', '::1', '2026-02-21 10:49:27'),
(9, NULL, 'account_linking', 'Linked resident: Rica Mae Java to patient: Archiel R. Cabanag', '::1', '2026-02-21 14:25:22'),
(10, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-02-22 11:31:41'),
(11, NULL, 'account_linking', 'Linked resident: Leandro to patient: Juan Dela Cruz', '::1', '2026-02-22 11:32:12'),
(12, NULL, 'resident_created', 'Created resident account: Archiel Cabanag', '::1', '2026-02-22 21:38:26'),
(13, NULL, 'account_linking', 'Linked resident: Archiel Cabanag to patient: Archiel R. Cabanag', '::1', '2026-02-23 08:12:48'),
(14, NULL, 'unlock_account', 'Unlocked resident account ID: 205', '::1', '2026-02-23 22:29:14'),
(15, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag', '::1', '2026-02-23 22:33:31'),
(16, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag', '::1', '2026-02-23 22:54:52'),
(17, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag', '::1', '2026-03-02 18:45:12'),
(18, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag Account was locked and has been automatically unlocked.', '::1', '2026-03-02 23:55:49'),
(19, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag (Account unlocked automatically)', '::1', '2026-03-03 00:08:31'),
(20, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag (Account unlocked automatically)', '::1', '2026-03-03 00:10:13'),
(21, NULL, 'account_linking', 'Linked resident: Jaycar Otida to patient: Christopher de Leon', '::1', '2026-03-03 00:52:20'),
(22, NULL, 'account_linking', 'Linked resident: Creshiel Manloloyo to patient: Creshiel Manloloyo', '::1', '2026-03-03 00:59:48'),
(23, NULL, 'staff_created', 'Created staff account: Warren Miguel Miras', '::1', '2026-03-04 08:51:34'),
(24, NULL, 'resident_created', 'Created resident account: Melvin Panfilo', '::1', '2026-03-04 08:54:29'),
(25, NULL, 'resident_created', 'Created resident account: Jerecho Latosa', '::1', '2026-03-04 08:57:23'),
(26, NULL, 'resident_created', 'Created resident account: Archiel Cabanag', '::1', '2026-03-04 08:59:57'),
(27, NULL, 'resident_created', 'Created resident account: Archiel Cabanag', '::1', '2026-03-04 09:03:00'),
(28, NULL, 'account_linking', 'Linked resident: Archiel Cabanag to patient: Archiel R. Cabanag', '::1', '2026-03-04 09:04:17'),
(29, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag (Account unlocked automatically)', '::1', '2026-03-04 09:06:00'),
(30, NULL, 'password_reset', 'Reset password for resident: Archiel Cabanag (Account unlocked automatically)', '::1', '2026-03-04 09:07:55'),
(31, NULL, 'resident_created', 'Created resident account: Russel Evan Loquinario', '::1', '2026-03-05 11:03:05'),
(32, NULL, 'password_reset', 'Reset password for resident: Russel Evan Loquinario (Account unlocked automatically)', '::1', '2026-03-05 11:06:46'),
(33, NULL, 'resident_created', 'Created resident account: Warren Miguel Miras', '::1', '2026-03-05 11:20:29'),
(34, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-03-05 11:20:46'),
(35, NULL, 'resident_created', 'Created resident account: Creshiel Manloloyo', '::1', '2026-03-05 11:23:31'),
(36, NULL, 'account_linking', 'Linked resident: Creshiel Manloloyo to patient: Creshiel Manloloyo', '::1', '2026-03-05 11:23:57'),
(37, NULL, 'resident_created', 'Created resident account: Fernando Lopez', '::1', '2026-03-05 11:25:17'),
(38, NULL, 'account_linking', 'Linked resident: Fernando Lopez to patient: Fernando Lopez', '::1', '2026-03-05 11:25:31'),
(39, NULL, 'resident_created', 'Created resident account: Rolando Castro', '::1', '2026-03-05 11:27:37'),
(40, NULL, 'account_linking', 'Linked resident: Rolando Castro to patient: Rolando Castro', '::1', '2026-03-05 11:27:44'),
(41, NULL, 'resident_created', 'Created resident account: evelyn garcia', '::1', '2026-03-05 11:29:50'),
(42, NULL, 'account_linking', 'Linked resident: evelyn garcia to patient: Evelyn Garcia', '::1', '2026-03-05 11:30:02'),
(43, NULL, 'resident_created', 'Created resident account: gregorio', '::1', '2026-03-05 11:31:05'),
(44, NULL, 'account_linking', 'Linked resident: gregorio to patient: Gregorio Velasco', '::1', '2026-03-05 11:31:22'),
(45, NULL, 'resident_created', 'Created resident account: manuel torres', '::1', '2026-03-05 11:32:38'),
(46, NULL, 'resident_created', 'Created resident account: luzviminda Flores', '::1', '2026-03-05 11:33:50'),
(47, NULL, 'account_linking', 'Linked resident: luzviminda Flores to patient: Luzviminda Flores', '::1', '2026-03-05 11:34:02'),
(48, NULL, 'account_linking', 'Linked resident: manuel torres to patient: Manuel Torres', '::1', '2026-03-05 11:34:13'),
(49, NULL, 'resident_created', 'Created resident account: Gloria Araneta', '::1', '2026-03-05 11:35:22'),
(50, NULL, 'account_linking', 'Linked resident: Gloria Araneta to patient: Gloria Araneta', '::1', '2026-03-05 11:35:29'),
(51, NULL, 'resident_created', 'Created resident account: Juzaly VIllaruz', '::1', '2026-03-05 15:26:29'),
(52, NULL, 'password_reset', 'Reset password for resident: Russel Evan Loquinario (Account unlocked automatically)', '::1', '2026-03-06 16:33:30'),
(53, NULL, 'password_reset', 'Reset password for resident: Juzaly VIllaruz (Account unlocked automatically)', '::1', '2026-03-07 18:51:10'),
(54, NULL, 'password_change', 'Changed password for staff: Leandro T. Labos', '::1', '2026-03-10 20:26:08'),
(55, NULL, 'password_change', 'Changed password for staff: Lance Christine Gallardo', '::1', '2026-03-10 20:27:35'),
(56, NULL, 'resident_created', 'Created resident account: Rhea Altomera', '::1', '2026-03-10 20:32:45'),
(57, NULL, 'staff_status_change', 'Deactivated staff: Leandro T. Labos', '::1', '2026-03-10 20:49:09'),
(58, NULL, 'staff_status_change', 'Activated staff: Leandro T. Labos', '::1', '2026-03-10 20:49:13'),
(59, NULL, 'staff_created', 'Created staff account: Russel Evan Loquinario', '::1', '2026-03-10 20:50:05'),
(60, NULL, 'staff_status_change', 'Deactivated staff: Leandro T. Labos', '::1', '2026-03-10 20:55:33'),
(61, NULL, 'staff_status_change', 'Activated staff: Leandro T. Labos', '::1', '2026-03-10 21:11:51'),
(62, NULL, 'account_linking', 'Linked resident: Rhea Altomera to patient: Rodrigo Duterte', '::1', '2026-03-10 21:21:16'),
(63, NULL, 'resident_created', 'Created resident account: Archiel R. Cabanag', '::1', '2026-03-10 22:34:21'),
(64, NULL, 'password_reset', 'Reset password for resident: Archiel R. Cabanag (Account unlocked automatically)', '::1', '2026-03-10 22:43:53'),
(65, NULL, 'staff_status_change', 'Deactivated staff: Leandro T. Labos', '::1', '2026-03-10 22:50:13'),
(66, NULL, 'staff_deleted', 'Deleted staff: Leandro T. Labos and all associated records', '::1', '2026-03-10 23:04:03'),
(67, NULL, 'staff_created', 'Created staff account: Russel Evan Loquinario', '::1', '2026-03-10 23:06:02'),
(68, NULL, 'account_linking', 'Linked resident: Archiel R. Cabanag to patient: Archiel R. Cabanag', '::1', '2026-03-10 23:30:16'),
(69, NULL, 'staff_status_change', 'Deactivated staff: Russel Evan Loquinario', '::1', '2026-03-11 07:29:07'),
(70, NULL, 'staff_status_change', 'Activated staff: Russel Evan Loquinario', '::1', '2026-03-11 07:29:12'),
(71, NULL, 'resident_created', 'Created resident account: Warren Miguel Miras', '::1', '2026-03-11 11:12:46'),
(72, NULL, 'account_linking', 'Linked resident: Warren Miguel Miras to patient: Warren Miguel Miras', '::1', '2026-03-11 11:15:41'),
(73, NULL, 'password_reset', 'Reset password for resident: Archiel R. Cabanag (Account unlocked automatically)', '::1', '2026-03-11 11:18:40'),
(74, NULL, 'staff_status_change', 'Deactivated staff: Russel Evan Loquinario', '::1', '2026-03-11 11:20:46'),
(75, NULL, 'staff_status_change', 'Activated staff: Russel Evan Loquinario', '::1', '2026-03-11 11:57:57'),
(76, NULL, 'resident_created', 'Created resident account: Juzaly VIllaruz', '::1', '2026-03-11 12:39:18'),
(77, NULL, 'password_reset', 'Reset password for resident: Juzaly VIllaruz (Account unlocked automatically)', '::1', '2026-03-11 12:41:11'),
(78, NULL, 'password_reset', 'Reset password for resident: Archiel R. Cabanag (Account unlocked automatically)', '::1', '2026-03-11 13:00:29'),
(79, NULL, 'staff_status_change', 'Deactivated staff: Russel Evan Loquinario', '::1', '2026-03-11 13:06:24'),
(80, NULL, 'staff_status_change', 'Activated staff: Russel Evan Loquinario', '::1', '2026-03-11 13:06:31'),
(81, NULL, 'account_linking', 'Linked resident: Juzaly VIllaruz to patient: Juzaly VIllaruz', '::1', '2026-03-13 09:57:31'),
(82, NULL, 'staff_created', 'Created staff account: Archiel R. Cabanag', '::1', '2026-03-13 12:36:06'),
(83, NULL, 'resident_created', 'Created resident account: Creshiel Manloloyo', '::1', '2026-03-13 12:37:36'),
(84, NULL, 'resident_created', 'Created resident account: Juzaly VIllaruz', '::1', '2026-03-13 21:58:33'),
(85, NULL, 'account_linking', 'Linked resident: Juzaly VIllaruz to patient: Juzaly VIllaruz', '::1', '2026-03-13 21:59:54'),
(86, NULL, 'resident_deleted', 'Deleted resident: Creshiel Manloloyo and all associated patient records', '::1', '2026-03-13 22:40:22'),
(87, NULL, 'resident_created', 'Created resident account: Creshiel Manloloyo', '::1', '2026-03-15 11:35:17'),
(88, NULL, 'staff_status_change', 'Deactivated staff: Archiel R. Cabanag', '::1', '2026-03-15 11:54:32'),
(89, NULL, 'staff_status_change', 'Activated staff: Archiel R. Cabanag', '::1', '2026-03-15 11:54:41'),
(90, NULL, 'resident_created', 'Created resident account: Lance Christine Gallardo', '::1', '2026-03-29 22:00:10'),
(91, NULL, 'staff_status_change', 'Deactivated staff: Archiel R. Cabanag', '::1', '2026-04-01 18:20:38'),
(92, NULL, 'staff_status_change', 'Activated staff: Archiel R. Cabanag', '::1', '2026-04-01 18:22:04'),
(93, NULL, 'staff_status_change', 'Deactivated staff: Archiel R. Cabanag', '::1', '2026-04-01 18:31:08'),
(94, NULL, 'staff_status_change', 'Activated staff: Archiel R. Cabanag', '::1', '2026-04-01 18:31:25'),
(95, NULL, 'resident_deleted', 'Deleted resident: Lance Christine Gallardo and all associated patient records', '::1', '2026-04-01 18:31:57'),
(96, NULL, 'resident_created', 'Created resident account: Lance Christine Gallardo', '::1', '2026-04-02 06:39:04'),
(97, NULL, 'resident_created', 'Created resident account: Roselan T. Cabanag', '::1', '2026-04-03 08:42:12'),
(98, NULL, 'account_linking', 'Linked resident: Lance Christine Gallardo to patient: Lance Christine Gallardo', '::1', '2026-04-03 19:30:23'),
(99, NULL, 'resident_created', 'Created resident account: Jaycar Otida', '::1', '2026-04-03 20:01:16'),
(100, NULL, 'staff_status_change', 'Deactivated staff: Archiel R. Cabanag', '::1', '2026-04-03 20:27:37'),
(101, NULL, 'account_linking', 'Linked resident: Jaycar Otida to patient: Jaycar Otida', '::1', '2026-04-03 21:03:15'),
(102, NULL, 'resident_created', 'Created resident account: Clint Mingo', '::1', '2026-04-03 21:04:10'),
(103, NULL, 'account_linking', 'Linked resident: Clint Mingo to patient: Clint Mingo', '::1', '2026-04-03 21:27:52'),
(104, NULL, 'resident_created', 'Created resident account: Cezar Montano', '::1', '2026-04-03 21:29:23'),
(105, NULL, 'resident_created', 'Created resident account: Christian Figuracion', '::1', '2026-04-03 21:42:30'),
(106, NULL, 'account_linking', 'Linked resident: Christian Figuracion to patient: Christian Figuracion', '::1', '2026-04-03 22:32:16'),
(107, NULL, 'staff_status_change', 'Activated staff: Archiel R. Cabanag', '::1', '2026-04-04 15:47:23'),
(108, NULL, 'account_linking', 'Linked resident: Creshiel Manloloyo to patient: Creshiel Manloloyo', '::1', '2026-04-05 20:58:26'),
(109, NULL, 'resident_created', 'Created resident account: Jhea Claire Oropesa', '::1', '2026-04-07 15:18:26'),
(110, NULL, 'account_linking', 'Linked resident: Jhea Claire Oropesa to patient: Jhea Claire L. Oropesa', '::1', '2026-04-07 15:22:48'),
(111, NULL, 'staff_status_change', 'Deactivated staff: Archiel R. Cabanag', '::1', '2026-04-10 09:09:02'),
(112, NULL, 'staff_status_change', 'Activated staff: Archiel R. Cabanag', '::1', '2026-04-10 09:09:12'),
(113, NULL, 'account_linking', 'Linked resident: Cezar Montano to patient: Jociel Studio', '::1', '2026-04-10 09:11:02'),
(114, NULL, 'staff_created', 'Created staff account: Lance Christine Gallardo', '::1', '2026-04-12 00:45:56'),
(115, NULL, 'staff_created', 'Created staff account: Warren Miguel Miras', '::1', '2026-04-12 00:49:00'),
(116, NULL, 'staff_created', 'Created staff account: Leandro Labos', '::1', '2026-04-12 00:52:09');

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
(348, 13, 'Laboratory Results Available', 'Your laboratory results are now available at the Barangay Health Center. Please visit the facility and present your claim stub or valid ID to receive your results.', 'medium', '', '2026-04-03', '2026-04-02 12:51:36', NULL, 'expired', 'public', 'basic', NULL),
(349, 13, 'Claim Your Lab Test Results', 'Residents who underwent laboratory tests last week may now claim their results. Kindly proceed to the laboratory section during office hours.', 'high', '', '2026-04-03', '2026-04-02 12:53:49', NULL, 'expired', 'public', 'basic', NULL),
(350, 13, 'Confidential Release of Results', 'Laboratory results will only be released to the patient or authorized representative with proper identification to ensure confidentiality.', 'high', '', '2026-04-03', '2026-04-02 12:57:52', NULL, 'expired', 'specific', 'basic', NULL),
(351, 13, 'Delayed Lab Results Notice', 'Due to high volume of tests, the release of some laboratory results may be delayed. We appreciate your patience and understanding.', 'high', 'lab_result', '2026-04-03', '2026-04-02 13:49:12', NULL, 'expired', 'specific', 'basic', NULL),
(352, 13, 'Follow-Up Consultation Required', 'Patients with released laboratory results are advised to consult with the health center physician for proper interpretation and guidance.', 'medium', '', '2026-04-30', '2026-04-02 13:58:14', NULL, 'active', 'public', 'basic', NULL),
(353, 13, 'Lance gwapa gallardo', 'asa naka ali diri laag ta', 'medium', '', '2026-04-04', '2026-04-03 00:36:31', NULL, 'expired', 'specific', 'basic', NULL),
(354, 13, 'Testing for Review', 'Testing for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for ReviewTesting for Re', 'medium', '', '2026-04-04', '2026-04-03 00:42:39', NULL, 'expired', 'specific', 'basic', NULL),
(355, 13, 'Lab Result', 'Lancelot', 'normal', 'lab_result', '2026-04-04', '2026-04-03 11:49:10', NULL, 'expired', 'specific', 'basic', NULL),
(356, 13, 'Hello Lab Result', 'Hello Maam', 'high', 'lab_result', '2026-04-04', '2026-04-03 11:56:19', NULL, 'expired', 'specific', 'basic', NULL),
(357, 13, 'Hello Lab Result', 'Testing', 'medium', 'lab_result', '2026-04-04', '2026-04-03 11:58:54', NULL, 'expired', 'specific', 'basic', NULL),
(358, 13, 'Lab Result', 'Clint Mingo', 'medium', 'lab_result', '2026-04-04', '2026-04-03 14:41:21', NULL, 'expired', 'specific', 'basic', NULL),
(359, 13, 'sdfds', 'sdfds', 'normal', 'lab_result', '2026-04-04', '2026-04-03 14:49:55', NULL, 'expired', 'specific', 'basic', NULL),
(360, 13, 'Mingoyyyyyyyy', 'Mingoyyyyyyyy', 'medium', '', '2026-04-04', '2026-04-03 15:39:52', NULL, 'expired', 'specific', 'basic', NULL),
(361, 13, 'Jacky Brown', 'JackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJackyJacky', 'high', 'lab_result', '2026-04-05', '2026-04-04 11:06:55', NULL, 'expired', 'specific', 'basic', NULL),
(362, 13, 'Hello Clint', 'Lab Result Ni', 'high', 'lab_result', '2026-04-06', '2026-04-04 16:26:56', NULL, 'expired', 'specific', 'basic', NULL),
(363, 13, 'Landing Page', 'Hello Residents', 'high', '', '2026-04-06', '2026-04-04 17:11:22', NULL, 'expired', 'landing_page', 'basic', NULL),
(364, 13, 'Hello Test', 'Testing for Review', 'high', '', '2026-04-07', '2026-04-06 03:48:23', NULL, 'expired', 'specific', 'basic', NULL),
(365, 13, 'Warren Miras', 'Miras Miras Miras Miguel', 'medium', 'lab_result', '2026-04-07', '2026-04-06 03:49:44', NULL, 'expired', 'specific', 'basic', NULL),
(366, 13, 'Warren Miras', 'Warren Warren Warren Warren', 'normal', 'lab_result', '2026-04-07', '2026-04-06 05:59:12', NULL, 'expired', 'specific', 'basic', NULL),
(367, 13, 'Warren- Specific Resident', 'warre', 'normal', '', '2026-04-07', '2026-04-06 06:04:49', NULL, 'expired', 'specific', 'basic', NULL),
(368, 13, 'Jhea Claire Oropesa Announcement', 'Important Announcement', 'medium', '', '2026-04-08', '2026-04-07 07:19:03', NULL, 'expired', 'specific', 'basic', NULL),
(369, 13, 'Hello Landing Page', 'For Announcement', 'high', '', '2026-04-08', '2026-04-07 08:39:56', NULL, 'expired', 'landing_page', 'basic', NULL),
(370, 13, 'Warren Announcement', 'Hello Warren', 'normal', 'lab_result', '2026-04-08', '2026-04-07 15:26:29', NULL, 'expired', 'specific', 'basic', NULL),
(371, 13, 'Announcement', 'Lab Result', 'medium', 'lab_result', '2026-04-09', '2026-04-08 08:28:09', NULL, 'expired', 'specific', 'basic', NULL),
(372, 12, 'Lab Result', 'Lab Result', 'high', 'lab_result', '2026-04-10', '2026-04-08 23:50:39', NULL, 'expired', 'specific', 'basic', NULL),
(373, 12, 'Cebu Eastern College', '4rth Year Oral Defense', 'normal', '', '2026-04-10', '2026-04-09 13:27:51', NULL, 'expired', 'landing_page', 'basic', NULL),
(374, 12, 'For All Resident', 'Oral Defense for all students', 'medium', '', '2026-04-10', '2026-04-09 13:30:35', NULL, 'expired', 'public', 'basic', NULL),
(375, 13, 'Test Announcemnt', 'Test', 'normal', '', '2026-04-11', '2026-04-10 01:04:06', NULL, 'expired', 'landing_page', 'basic', NULL),
(376, 13, 'TEst2 Announcement', 'awdwd', 'medium', '', '2026-04-11', '2026-04-10 01:04:52', NULL, 'expired', 'public', 'basic', NULL),
(377, 15, 'Hello Announcement', 'Hello Announcement', 'normal', '', '2026-04-13', '2026-04-11 19:27:46', NULL, 'expired', 'landing_page', 'basic', NULL),
(378, 15, 'Test Announcement', 'Hello Test', 'high', '', '2026-04-15', '2026-04-14 06:43:03', NULL, 'expired', 'landing_page', 'basic', '/community-health-tracker/uploads/announcements/69dde1f7f1f3f.png'),
(379, 15, 'All Student Announcement', 'All Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student AnnouncementAll Student Announcement', 'medium', '', '2026-04-15', '2026-04-14 06:45:13', NULL, 'expired', 'public', 'basic', NULL);

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
  `student_last_name` varchar(100) DEFAULT NULL,
  `student_first_name` varchar(100) DEFAULT NULL,
  `student_middle_name` varchar(100) DEFAULT NULL,
  `student_nickname` varchar(100) DEFAULT NULL,
  `student_religion` varchar(100) DEFAULT NULL,
  `student_nationality` varchar(100) DEFAULT NULL,
  `student_home_address` text DEFAULT NULL,
  `student_home_no` varchar(50) DEFAULT NULL,
  `student_office_no` varchar(50) DEFAULT NULL,
  `student_fax_no` varchar(50) DEFAULT NULL,
  `student_mobile_no` varchar(50) DEFAULT NULL,
  `student_email` varchar(150) DEFAULT NULL,
  `student_effective_date` date DEFAULT NULL,
  `student_parent_guardian_name` varchar(150) DEFAULT NULL,
  `student_parent_guardian_occupation` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sitio` varchar(255) DEFAULT NULL,
  `disease` varchar(255) DEFAULT NULL,
  `immunization_status` varchar(50) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `last_checkup` date DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consultation_type` varchar(20) DEFAULT 'onsite',
  `consent_given` tinyint(1) DEFAULT 0,
  `consent_date` datetime DEFAULT NULL,
  `patient_record_uid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_patients`
--

INSERT INTO `sitio1_patients` (`id`, `user_id`, `phic_no`, `bhw_assigned`, `family_no`, `fourps_member`, `full_name`, `student_last_name`, `student_first_name`, `student_middle_name`, `student_nickname`, `student_religion`, `student_nationality`, `student_home_address`, `student_home_no`, `student_office_no`, `student_fax_no`, `student_mobile_no`, `student_email`, `student_effective_date`, `student_parent_guardian_name`, `student_parent_guardian_occupation`, `date_of_birth`, `age`, `gender`, `occupation`, `address`, `sitio`, `disease`, `immunization_status`, `contact`, `last_checkup`, `medical_history`, `added_by`, `created_at`, `deleted_at`, `restored_at`, `updated_at`, `consultation_type`, `consent_given`, `consent_date`, `patient_record_uid`) VALUES
(837, NULL, NULL, NULL, NULL, 'No', 'Otida, Jaycar Sulu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2002-05-26', 23, NULL, NULL, 'Tisa Cebu City Near Labangon', NULL, NULL, NULL, '09312312421', '2026-04-20', NULL, 13, '2026-04-20 17:46:54', NULL, '2026-04-20 17:46:54', '2026-04-20 17:47:00', 'onsite', 0, NULL, NULL),
(838, NULL, NULL, NULL, NULL, 'No', 'Casola, Ronie Solo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1999-06-24', 26, 'M', NULL, 'Tisa Cebu City Near Labangon', NULL, NULL, NULL, '09312312421', '2026-04-20', NULL, 13, '2026-04-20 17:45:07', NULL, '2026-04-20 17:45:07', '2026-04-20 17:45:07', 'onsite', 0, NULL, NULL),
(842, NULL, NULL, NULL, NULL, 'No', 'Cabanag, Archiel Sulu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2002-05-26', 23, NULL, NULL, 'Duljo Fatima, Cebu City', NULL, NULL, NULL, '09312312421', '2026-04-21', NULL, 13, '2026-04-21 00:57:07', NULL, NULL, '2026-04-21 02:52:26', 'onsite', 1, '2026-04-21 08:57:07', NULL),
(843, NULL, NULL, NULL, NULL, 'No', 'Labos, Leandro Sulu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1999-05-05', 26, NULL, NULL, 'Tisa Cebu City Near Labangon', NULL, NULL, NULL, '09312312421', '2026-04-21', NULL, 15, '2026-04-21 05:33:33', NULL, NULL, '2026-04-21 05:34:18', 'onsite', 1, '2026-04-21 13:33:33', NULL);

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
  `updated_at` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_active` tinyint(1) DEFAULT 1,
  `work_days` varchar(20) DEFAULT '1111100' COMMENT '7-digit string (1=working, 0=off), Mon-Sun',
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_staff`
--

INSERT INTO `sitio1_staff` (`id`, `username`, `password`, `full_name`, `position`, `created_by`, `created_at`, `updated_at`, `status`, `is_active`, `work_days`, `specialization`, `license_number`) VALUES
(12, 'Russel', '$2y$10$YzVlgLQg7V2zgnLMMOPSdO9NMq6EZQNFrtfPuUr.jd5kc4F8G7qb.', 'Russel Evan Loquinario', 'Nurse', NULL, '2026-03-10 15:06:02', '2026-03-11 13:06:31', 'active', 1, '1111100', 'operation', '53123213'),
(13, 'Archiel', '$2y$10$J47TKGo72XKyzUGOsXdFBuLnZNr9Zsgo1wHCdgrtbV19dyYpUurd2', 'Archiel R. Cabanag', 'Nurse', NULL, '2026-03-13 04:36:06', '2026-04-10 09:09:12', 'active', 1, '1111100', 'operation', '53123213'),
(14, 'Lance', '$2y$10$hQcd7gYmoACPkgU3A74q/u7oRlroS9KVrlwBCsZfcFCIVNa6POrwm', 'Lance Christine Gallardo', 'Nurse', NULL, '2026-04-11 16:45:56', NULL, 'active', 1, '1111100', 'General Medecine', '123456'),
(15, 'Warren', '$2y$10$2bq.nlqZtWXYaO0q3avpXu3e9NDk4GuA1iPNr5fBPKAtLWOx4d.CO', 'Warren Miguel Miras', 'Assistant Doctor', NULL, '2026-04-11 16:49:00', NULL, 'active', 1, '1111100', 'General Medecine', '123456'),
(16, 'Leandro', '$2y$10$Bs.T61dtyw43xR4mEEY2j.daxMWWLY9NEjz6dejEzxdQSRiylEr4i', 'Leandro Labos', 'Working Scholar', NULL, '2026-04-11 16:52:09', NULL, 'active', 1, '1111100', 'General Medecine', '123456');

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
  `patient_record_uid` varchar(50) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `account_locked_until` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitio1_users`
--

INSERT INTO `sitio1_users` (`id`, `username`, `password`, `email`, `full_name`, `gender`, `age`, `date_of_birth`, `address`, `sitio`, `contact`, `civil_status`, `occupation`, `approved`, `approved_by`, `unique_number`, `created_at`, `last_login`, `status`, `role`, `specialization`, `license_number`, `updated_at`, `verification_method`, `id_image_path`, `profile_image`, `verification_notes`, `verification_consent`, `id_verified`, `verified_at`, `account_linked`, `patient_record_id`, `patient_record_uid`, `failed_login_attempts`, `last_failed_login`, `account_locked_until`, `password_reset_token`, `password_reset_token_expires`) VALUES
(222, 'Archiel', '$2y$10$NE.sZ12vOSNrfeHkaJvnEOeHb0Yob3TgWUxt0JzJA6aZECuknbjq2', 'cabanagarchielrosel@gmail.com', 'Archiel R. Cabanag', 'male', 23, '2002-05-26', NULL, 'San Roque', '09816497664', NULL, NULL, 1, NULL, 'RESSAN202603595', '2026-03-10 14:34:21', NULL, 'approved', 'patient', NULL, NULL, '2026-04-08 08:40:44', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-10 14:34:21', 0, NULL, 'PAT-20260310-ARC-8402', 0, NULL, NULL, NULL, NULL),
(223, 'Warren', '$2y$10$dnCiRssAzE9NW/c4GZWhCOiMwD7nO5n/zMxHwmpTKVcccjv.lmnjS', 'warrenmiguel789@gmail.com', 'Warren Miguel Miras', 'male', 23, '2002-03-12', NULL, 'San Vicente', '09206001470', NULL, NULL, 1, NULL, 'RESSAN202603643', '2026-03-11 03:12:46', NULL, 'approved', 'patient', NULL, NULL, '2026-03-28 12:38:59', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-11 03:12:46', 0, NULL, 'PAT-20260311-WAR-1609', 0, NULL, NULL, NULL, NULL),
(226, 'Juzaly', '$2y$10$.RK59/lllAyGjeQpVrGDeeQFmjTqSRsMPn6AMAosyPnEKpxurmqia', 'juzalyvillaruz@gmail.com', 'Juzaly VIllaruz', 'male', 36, '1989-07-06', NULL, 'Mabuhay', '09816497664', NULL, NULL, 1, NULL, 'RESMAB202603425', '2026-03-13 13:58:33', NULL, 'approved', 'patient', NULL, NULL, '2026-03-13 13:59:54', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-13 13:58:33', 0, NULL, 'PAT-20260313-JUZ-8159', 0, NULL, NULL, NULL, NULL),
(227, 'Creshiel', '$2y$10$oe.ciJBuXRXMfxwEvBudEOlvLTR1hzTpd8IJ27Ers4jiBBvRWVeoi', 'creshielmanloloyo@gmail.com', 'Creshiel Manloloyo', 'female', 23, '2002-06-19', NULL, 'Mabuhay', '09816497664', NULL, NULL, 1, NULL, 'RESMAB202603758', '2026-03-15 03:35:17', NULL, 'approved', 'patient', NULL, NULL, '2026-04-05 12:58:26', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-15 03:35:17', 0, NULL, 'PAT-20260405-CRE-3279', 0, NULL, NULL, NULL, NULL),
(229, 'Lance', '$2y$10$rLGv/5jtcLAFWng0gurAJuhIZr88qSvfWU6nEIwfghdM5ya0nroDy', 'lancechristinegallardo2@gmail.com', 'Lance Christine Gallardo', 'female', 25, '2000-09-08', NULL, 'City Central', '09816497664', NULL, NULL, 1, NULL, 'RESCIT202604091', '2026-04-01 22:39:04', NULL, 'approved', 'patient', NULL, NULL, '2026-04-03 11:30:23', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-01 22:39:04', 0, NULL, 'PAT-20260403-LAN-6271', 0, NULL, NULL, NULL, NULL),
(230, 'Roselan', '$2y$10$6QkxpaH5Uo9Ti68faib0AOy0IApdck8hzTOSpH.D4yhI1B9SnqcKu', 'roselancabanag01@gmail.com', 'Roselan T. Cabanag', 'male', 47, '1978-07-28', NULL, 'San. Antonio', '09206001470', NULL, NULL, 1, NULL, 'RESSAN202604595', '2026-04-03 00:42:12', NULL, 'approved', 'patient', NULL, NULL, NULL, 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-03 00:42:12', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(231, 'Jaycar', '$2y$10$R.F76GKqCecENpP3H2OjEOHXQG15y7gi4xAoLaheigJIMOahoo.cC', 'jaycarotida@gmail.com', 'Jaycar Otida', 'male', 23, '2002-06-05', NULL, 'San. Antonio', NULL, NULL, NULL, 1, NULL, 'RESSAN202604555', '2026-04-03 12:01:16', NULL, 'approved', 'patient', NULL, NULL, '2026-04-03 13:03:15', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-03 12:01:16', 0, NULL, 'PAT-20260403-JAY-1838', 0, NULL, NULL, NULL, NULL),
(232, 'Clint', '$2y$10$UEf0rTtRISiRHxAwkom/EOQ3VCLXVAVxQmP8Z1lw5UNDoz26wpKte', 'clintmingo@gmail.com', 'Clint Mingo', 'male', 18, '2007-11-24', NULL, 'Zapatera', '09206001470', NULL, NULL, 1, NULL, 'RESZAP202604833', '2026-04-03 13:04:10', NULL, 'approved', 'patient', NULL, NULL, '2026-04-03 13:27:52', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-03 13:04:10', 0, NULL, 'PAT-20260403-CLI-8785', 0, NULL, NULL, NULL, NULL),
(233, 'Cezar', '$2y$10$etv0XZirSUOA3Ak5/Nds5ubcRZYBzOw4npG6ItmPqJ0WT.L6d4.le', 'cezarmontano@gmail.com', 'Cezar Montano', 'male', 22, '2003-11-21', NULL, 'Sto.niño lll', '09206001470', NULL, NULL, 1, NULL, 'RESSTO202604637', '2026-04-03 13:29:23', NULL, 'approved', 'patient', NULL, NULL, '2026-04-10 01:11:02', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-03 13:29:23', 0, NULL, 'PAT-20260410-JOC-5078', 0, NULL, NULL, NULL, NULL),
(234, 'Christian', '$2y$10$qHEen/i11dGUuE.amZ0CvO608StKQb4wfEO1jOr0vWYoVlLK.hG5W', 'christianfiguracion@gmail.com', 'Christian Figuracion', 'male', 21, '2004-07-17', NULL, 'San. Antonio', '09206001470', NULL, NULL, 1, NULL, 'RESSAN202604500', '2026-04-03 13:42:30', NULL, 'approved', 'patient', NULL, NULL, '2026-04-03 14:32:16', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-03 13:42:30', 0, NULL, 'PAT-20260403-CHR-8236', 0, NULL, NULL, NULL, NULL),
(235, 'Jhea', '$2y$10$akpCpM6i29lGTF2Ba0.i9OFpzJ5JH4njc/G7bstkHUkuTRXcpiwhi', 'clairexxi02@gmail.com', 'Jhea Claire Oropesa', 'female', 23, '2002-06-19', NULL, 'San Roque', '09206001470', NULL, NULL, 1, NULL, 'RESSAN202604289', '2026-04-07 07:18:26', NULL, 'approved', 'patient', NULL, NULL, '2026-04-07 07:22:48', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-04-07 07:18:26', 0, NULL, 'PAT-20260407-JHE-6777', 0, NULL, NULL, NULL, NULL);

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
(126, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:09:39'),
(127, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:09:48'),
(128, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:10:58'),
(129, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:11:11'),
(130, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:16:56'),
(131, 9, 'add_patient', 259, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"259\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 12:43:51'),
(132, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 15:53:11'),
(133, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 17:44:59'),
(134, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 20:01:09'),
(135, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:29:00'),
(136, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 09:33:40'),
(137, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 10:52:13'),
(138, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 10:56:02'),
(139, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 10:59:31'),
(140, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 10:59:34'),
(141, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 10:59:37'),
(142, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 11:05:21'),
(143, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 15:25:10'),
(144, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 19:40:22'),
(145, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 00:56:22'),
(146, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 07:46:25'),
(147, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 08:15:45'),
(148, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 08:15:55'),
(149, 9, 'add_patient', 301, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"301\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 12:19:55'),
(150, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 12:34:24'),
(151, 9, 'add_patient', 302, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"302\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:14:35'),
(152, 9, 'add_patient', 303, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"303\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:20:10'),
(153, 9, 'add_patient', 304, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":\"304\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:21:15'),
(154, 9, 'add_patient', 305, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Russel Evan Loquinario\",\"patient_id\":\"305\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:22:22'),
(155, 9, 'add_patient', 306, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"306\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:23:37'),
(156, 9, 'add_patient', 307, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"307\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:26:10'),
(157, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:48:35'),
(158, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 13:48:52'),
(159, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 16:18:30'),
(160, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 19:16:35'),
(161, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 21:22:37'),
(162, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 21:22:45'),
(163, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-21 00:36:02'),
(164, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-21 07:35:28'),
(165, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 07:41:26'),
(166, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 08:10:55'),
(167, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 08:11:10'),
(168, 9, 'print_patient', 307, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":307}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 08:15:40'),
(169, 9, 'add_patient', 308, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"308\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 08:17:28'),
(170, 9, 'add_patient', 309, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"309\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 08:18:36'),
(171, 9, 'add_patient', 310, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"310\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 09:07:58'),
(172, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 11:36:47'),
(173, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 12:43:41'),
(174, 9, 'add_patient', 311, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Gil Arda\",\"patient_id\":\"311\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 13:22:01'),
(175, 9, 'print_patient', 311, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Gil Arda\",\"patient_id\":311}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 13:23:32'),
(176, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 13:24:27'),
(177, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 13:24:32'),
(178, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 13:24:38'),
(179, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 14:32:55'),
(180, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 14:34:25'),
(181, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 21:29:00'),
(182, 9, 'add_patient', 312, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"312\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 21:40:19'),
(183, 9, 'archive_patient', 312, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"original_id\":\"312\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 00:01:50'),
(184, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:37:18'),
(185, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:45:25'),
(186, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:46:42'),
(187, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:46:46'),
(188, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:46:53'),
(189, 9, 'print_patient', 311, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Gil Arda\",\"patient_id\":311}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:51:20'),
(190, 9, 'print_patient', 311, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Gil Arda\",\"patient_id\":311}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:53:11'),
(191, 9, 'print_patient', 310, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":310}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 09:53:55'),
(192, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:39:54'),
(193, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:41:10'),
(194, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:41:15'),
(195, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:44:17'),
(196, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:44:40'),
(197, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:46:30'),
(198, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:46:41'),
(199, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:48:11'),
(200, 9, 'export_bulk_excel', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:50:14'),
(201, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:51:32'),
(202, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:53:40'),
(203, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:54:38'),
(204, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:54:42'),
(205, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:54:45'),
(206, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:55:07'),
(207, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:55:58'),
(208, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:56:02'),
(209, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:56:38'),
(210, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:57:08'),
(211, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:57:51'),
(212, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 10:59:23'),
(213, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 11:03:57'),
(214, 9, 'add_patient', 361, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"361\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 11:06:57'),
(215, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:59:33'),
(216, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:05:13'),
(217, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:32:07'),
(218, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:35:36'),
(219, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:48:39'),
(220, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 06:24:12'),
(221, 9, 'print_patient', 768, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Diana Hernandez\",\"patient_id\":768}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 06:25:04'),
(222, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 07:54:06'),
(223, 9, 'add_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"811\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 08:11:56'),
(224, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 09:39:42'),
(225, 9, 'add_patient', 812, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"russell\",\"patient_id\":\"812\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 10:04:21'),
(226, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 13:38:13'),
(227, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 13:39:44'),
(228, 9, 'print_patient', 812, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"russell\",\"patient_id\":812}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 13:40:05'),
(229, 9, 'export_bulk_pdf', NULL, '{\"full_name\":\"Leandro T. Labos\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 13:41:18'),
(230, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 13:42:38'),
(231, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 09:27:10'),
(232, 9, 'archive_patient', 812, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"russell\",\"original_id\":\"812\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 09:44:58'),
(233, 9, 'archive_patient', 311, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juan Dela Cruz\",\"original_id\":\"311\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 09:45:00'),
(234, 9, 'archive_patient', 312, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Maria Santos\",\"original_id\":\"312\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 09:45:03'),
(235, 9, 'archive_patient', 313, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Jose Rizal\",\"original_id\":\"313\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 09:45:04'),
(236, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 11:00:21'),
(237, 9, 'archive_patient', 314, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Elena Rodriguez\",\"original_id\":\"314\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 12:17:19'),
(238, 9, 'add_patient', 813, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel Cabanag\",\"patient_id\":\"813\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 13:47:34'),
(239, 9, 'print_patient', 813, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel Cabanag\",\"patient_id\":813}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 13:48:35'),
(240, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 19:17:42'),
(241, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 20:14:47'),
(242, 9, 'print_patient', 813, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel Cabanag\",\"patient_id\":813}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 20:53:14'),
(243, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 23:46:50'),
(244, 9, 'archive_patient', 813, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel Cabanag\",\"original_id\":\"813\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 23:47:41'),
(245, 9, 'archive_patient', 315, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Roberto Mendoza\",\"original_id\":\"315\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 23:47:44'),
(246, 9, 'archive_patient', 316, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Isabel Cruz\",\"original_id\":\"316\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 23:47:46'),
(247, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 16:56:28'),
(248, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 21:50:33'),
(249, 9, 'archive_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"811\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 23:13:18'),
(250, 9, 'restore_patient', 811, '{\"patient_id\":\"811\",\"patient_name\":\"Archiel R. Cabanag\",\"restore_time\":\"2026-02-27 23:15:38\",\"restored_by\":\"Leandro T. Labos\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 23:15:38'),
(251, 9, 'archive_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"811\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 23:15:47'),
(252, 9, 'restore_patient', 811, '{\"patient_id\":\"811\",\"patient_name\":\"Archiel R. Cabanag\",\"restore_time\":\"2026-02-27 23:37:58\",\"restored_by\":\"Leandro T. Labos\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 23:37:58'),
(253, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 05:14:34'),
(254, 9, 'archive_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"811\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 06:07:42'),
(255, 9, 'restore_patient', 811, '{\"patient_id\":\"811\",\"patient_name\":\"Archiel R. Cabanag\",\"restore_time\":\"2026-02-28 06:24:03\",\"restored_by\":\"Leandro T. Labos\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 06:24:03'),
(256, 9, 'archive_patient', 317, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Antonio Fernandez\",\"original_id\":\"317\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 07:27:53'),
(257, 9, 'archive_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"811\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 07:27:56'),
(258, 9, 'archive_patient', 318, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Teresa Villanueva\",\"original_id\":\"318\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 07:28:00'),
(259, 9, 'archive_patient', 319, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Ramon Santos\",\"original_id\":\"319\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 07:28:03'),
(260, 9, 'archive_patient', 320, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Carmen Hernandez\",\"original_id\":\"320\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 07:28:07'),
(261, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 09:51:17'),
(262, 9, 'restore_patient', 811, '{\"patient_id\":\"811\",\"patient_name\":\"Archiel R. Cabanag\",\"restore_time\":\"2026-02-28 10:31:49\",\"restored_by\":\"Leandro T. Labos\",\"restore_type\":\"hard_delete\",\"medical_info_restored\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 10:31:49'),
(263, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 13:29:20'),
(264, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 13:30:52'),
(265, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 16:51:35'),
(266, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 17:57:55'),
(267, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 19:57:14'),
(268, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 19:57:19'),
(269, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 19:57:27'),
(270, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 19:57:50'),
(271, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 20:05:26'),
(272, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-28 20:05:37'),
(273, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-01 13:19:54'),
(274, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 13:59:34'),
(275, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 18:34:12'),
(276, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 21:42:19'),
(277, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:03:19'),
(278, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:05:07'),
(279, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:05:19'),
(280, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:06:41'),
(281, 9, 'print_patient', 324, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Evelyn Garcia\",\"patient_id\":324}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:19:15'),
(282, 9, 'print_patient', 811, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 22:59:59'),
(283, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:00:32'),
(284, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.109.5 Chrome/142.0.7444.265 Electron/39.3.0 Safari/537.36', '2026-03-02 23:21:19'),
(285, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.109.5 Chrome/142.0.7444.265 Electron/39.3.0 Safari/537.36', '2026-03-02 23:25:17'),
(286, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:57:29'),
(287, 9, 'add_patient', 814, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"814\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 00:58:45'),
(288, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 07:21:26'),
(289, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 09:56:40'),
(290, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 16:56:49'),
(291, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 20:31:37'),
(292, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 00:43:52'),
(293, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 00:44:17'),
(294, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 08:52:02'),
(295, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 15:36:47'),
(296, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 15:37:39'),
(297, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 16:45:14'),
(298, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 23:35:19'),
(299, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 10:28:55'),
(300, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 10:56:32'),
(301, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:08:00'),
(302, 9, 'add_patient', 815, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:10:30'),
(303, 9, 'print_patient', 815, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":815}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:10:44'),
(304, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:46:04'),
(305, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 13:35:15'),
(306, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 13:53:46'),
(307, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 14:21:23'),
(308, 9, 'add_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":\"816\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 14:24:12'),
(309, 9, 'print_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 14:28:24'),
(310, 9, 'print_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 15:23:46'),
(311, 9, 'print_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 15:24:29'),
(312, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 17:53:56'),
(313, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 19:50:03'),
(314, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:10:19');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(315, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:13:38'),
(316, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:15:10'),
(317, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:15:20'),
(318, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:16:19'),
(319, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:16:42'),
(320, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:16:51'),
(321, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:17:03'),
(322, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:17:24'),
(323, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:20:27'),
(324, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 20:20:32'),
(325, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 22:52:07'),
(326, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 23:00:03'),
(327, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 14:43:12'),
(328, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 14:43:32'),
(329, 10, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 14:45:48'),
(330, 10, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 15:16:17'),
(331, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 16:36:07'),
(332, 9, 'print_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 16:39:04'),
(333, 9, 'print_patient', 816, '{\"full_name\":\"Leandro T. Labos\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 16:39:24'),
(334, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 16:50:57'),
(335, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 17:47:56'),
(336, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 17:49:06'),
(337, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 17:52:53'),
(338, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 20:00:48'),
(339, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-09 00:07:14'),
(340, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-09 01:57:14'),
(341, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 14:25:38'),
(342, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 20:06:30'),
(343, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 20:26:19'),
(344, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 20:26:39'),
(345, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 20:27:41'),
(346, 8, 'staff_login', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 20:28:02'),
(347, 9, 'staff_login', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 22:45:05'),
(348, 9, 'staff_logout', NULL, '{\"full_name\":\"Leandro T. Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 23:06:20'),
(349, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 23:07:02'),
(350, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 07:30:49'),
(351, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 11:05:24'),
(352, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 11:08:48'),
(353, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:15:12'),
(354, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:17:29'),
(355, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:59:23'),
(356, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 12:59:47'),
(357, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 13:02:08'),
(358, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 08:02:15'),
(359, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 08:03:05'),
(360, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 08:07:59'),
(361, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 08:21:55'),
(362, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 08:22:54'),
(363, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 08:29:06'),
(364, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 12:40:09'),
(365, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 14:16:52'),
(366, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 14:41:02'),
(367, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 14:50:39'),
(368, 12, 'print_patient', 321, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Fernando Lopez\",\"patient_id\":321}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 14:50:51'),
(369, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 19:55:23'),
(370, 12, 'export_bulk_pdf', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 19:55:50'),
(371, 12, 'export_bulk_pdf', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 20:19:15'),
(372, 12, 'print_patient', 814, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":814}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 23:27:13'),
(373, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 11:12:58'),
(374, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 13:11:21'),
(375, 13, 'print_patient', 321, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Fernando Lopez\",\"patient_id\":321}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 16:35:28'),
(376, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 17:30:44'),
(377, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 19:41:36'),
(378, 12, 'print_patient', 811, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 21:07:32'),
(379, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 22:02:01'),
(380, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 22:02:11'),
(381, 13, 'print_patient', 816, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 22:02:21'),
(382, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 11:57:17'),
(383, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 21:24:05'),
(384, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 20:32:35'),
(385, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 20:37:20'),
(386, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 22:36:59'),
(387, 13, 'print_patient', 811, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":811}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 23:40:19'),
(388, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 00:08:20'),
(389, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 00:09:04'),
(390, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 01:03:22'),
(391, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 15:52:33'),
(392, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 15:56:57'),
(393, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 15:57:11'),
(394, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 21:09:47'),
(395, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 21:58:17'),
(396, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 22:01:09'),
(397, 13, 'print_patient', 323, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Rolando Castro\",\"patient_id\":323}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 22:17:19'),
(398, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 22:21:56'),
(399, 13, 'archive_patient', 815, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 22:24:09'),
(400, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":\"\",\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 22:32:34'),
(401, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 08:00:46'),
(402, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 18:10:11'),
(403, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 18:18:47'),
(404, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 18:22:12'),
(405, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 20:49:22'),
(406, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 22:03:12'),
(407, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 22:46:13'),
(408, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 06:29:48'),
(409, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:48:01'),
(410, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 20:29:36'),
(411, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 20:29:43'),
(412, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 23:48:28'),
(413, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 08:33:50'),
(414, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 08:34:09'),
(415, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 08:35:24'),
(416, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 10:57:57'),
(417, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 17:11:07'),
(418, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 18:57:54'),
(419, 13, 'add_patient', 817, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Lance Christine Gallardo\",\"patient_id\":\"817\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 19:30:05'),
(420, 13, 'add_patient', 818, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Jaycar Otida\",\"patient_id\":\"818\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 21:02:58'),
(421, 13, 'add_patient', 819, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Clint Mingo\",\"patient_id\":\"819\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 21:27:44'),
(422, 13, 'add_patient', 820, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Christian Figuracion\",\"patient_id\":\"820\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 22:29:19'),
(423, 13, 'send_announcement', 360, '{\"title\":\"Mingoyyyyyyyy\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"basic\",\"target_users\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 23:40:04'),
(424, 13, 'print_patient', 816, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Juzaly VIllaruz\",\"patient_id\":816}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 23:41:44'),
(425, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-04 06:41:48'),
(426, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-04 15:47:30'),
(427, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-04 18:10:22'),
(428, 13, 'send_announcement', 361, '{\"title\":\"Jacky Brown\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-04 19:07:02'),
(429, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-04 23:24:24'),
(430, 13, 'send_announcement', 362, '{\"title\":\"Hello Clint\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:27:03'),
(431, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:28:59'),
(432, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:29:36'),
(433, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:30:40'),
(434, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:43:13'),
(435, 13, 'view_patient', 818, '{\"patient_id\":818,\"patient_name\":\"Jaycar Otida\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:52:15'),
(436, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:53:29'),
(437, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:54:03'),
(438, 13, 'print_patient', 819, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Clint Mingo\",\"patient_id\":819}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 00:54:07'),
(439, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:01:49'),
(440, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:09:27'),
(441, 13, 'send_announcement', 363, '{\"title\":\"Landing Page\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:11:22'),
(442, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:13:52'),
(443, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:20:40'),
(444, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:29:26'),
(445, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:32:49'),
(446, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 01:37:31'),
(447, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 02:23:30'),
(448, 13, 'add_patient', 821, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"821\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 02:30:47'),
(449, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 02:30:54'),
(450, 13, 'add_patient', 822, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"patient_id\":\"822\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 02:35:17'),
(451, 13, 'view_patient', 822, '{\"patient_id\":822,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 02:35:22'),
(452, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:06:22'),
(453, 12, 'view_patient', 822, '{\"patient_id\":822,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:06:41'),
(454, 12, 'archive_patient', 822, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"822\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:08:05'),
(455, 12, 'add_patient', 823, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Jerecho Latosa\",\"patient_id\":\"823\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:14:53'),
(456, 12, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:15:20'),
(457, 12, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:15:23'),
(458, 12, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:18:21'),
(459, 12, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:18:25'),
(460, 12, 'add_patient', 824, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Jociel Studio\",\"patient_id\":\"824\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:20:09'),
(461, 12, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:21:14'),
(462, 12, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:21:19'),
(463, 12, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:23:31'),
(464, 12, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:23:35'),
(465, 12, 'add_patient', 825, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Allan Khey\",\"patient_id\":\"825\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:24:54'),
(466, 12, 'archive_patient', 825, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Allan Khey\",\"original_id\":\"825\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:02'),
(467, 12, 'restore_patient', 825, '{\"patient_id\":\"825\",\"patient_name\":\"Allan Khey\",\"restore_time\":\"2026-04-05 08:25:16\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:16'),
(468, 12, 'restore_patient', 822, '{\"patient_id\":\"822\",\"patient_name\":\"Archiel R. Cabanag\",\"restore_time\":\"2026-04-05 08:25:27\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":13,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:27'),
(469, 12, 'restore_patient', 815, '{\"patient_id\":\"815\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-04-05 08:25:35\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":223,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:35'),
(470, 12, 'restore_patient', 320, '{\"patient_id\":\"320\",\"patient_name\":\"Carmen Hernandez\",\"restore_time\":\"2026-04-05 08:25:47\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:47'),
(471, 12, 'restore_patient', 319, '{\"patient_id\":\"319\",\"patient_name\":\"Ramon Santos\",\"restore_time\":\"2026-04-05 08:25:53\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:25:53'),
(472, 12, 'restore_patient', 318, '{\"patient_id\":\"318\",\"patient_name\":\"Teresa Villanueva\",\"restore_time\":\"2026-04-05 08:26:00\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:26:00'),
(473, 12, 'restore_patient', 317, '{\"patient_id\":\"317\",\"patient_name\":\"Antonio Fernandez\",\"restore_time\":\"2026-04-05 08:26:09\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:26:09'),
(474, 12, 'restore_patient', 316, '{\"patient_id\":\"316\",\"patient_name\":\"Isabel Cruz\",\"restore_time\":\"2026-04-05 08:27:07\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:27:07'),
(475, 12, 'restore_patient', 315, '{\"patient_id\":\"315\",\"patient_name\":\"Roberto Mendoza\",\"restore_time\":\"2026-04-05 08:27:14\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:27:14'),
(476, 12, 'view_patient', 315, '{\"patient_id\":315,\"patient_name\":\"Roberto Mendoza\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 08:34:37'),
(477, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:21:43'),
(478, 13, 'archive_patient', 315, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Roberto Mendoza\",\"original_id\":\"315\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:22:52'),
(479, 13, 'restore_patient', 315, '{\"patient_id\":\"315\",\"patient_name\":\"Roberto Mendoza\",\"restore_time\":\"2026-04-05 15:23:15\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:23:15'),
(480, 13, 'view_patient', 315, '{\"patient_id\":315,\"patient_name\":\"Roberto Mendoza\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:30:40'),
(481, 13, 'archive_patient', 315, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Roberto Mendoza\",\"original_id\":\"315\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:30:48'),
(482, 13, 'archive_patient', 316, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Isabel Cruz\",\"original_id\":\"316\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:30:52'),
(483, 13, 'view_patient', 317, '{\"patient_id\":317,\"patient_name\":\"Antonio Fernandez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:30:54'),
(484, 13, 'archive_patient', 317, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Antonio Fernandez\",\"original_id\":\"317\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:00'),
(485, 13, 'archive_patient', 318, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Teresa Villanueva\",\"original_id\":\"318\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:03'),
(486, 13, 'archive_patient', 319, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Ramon Santos\",\"original_id\":\"319\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:06'),
(487, 13, 'archive_patient', 320, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Carmen Hernandez\",\"original_id\":\"320\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:09'),
(488, 13, 'archive_patient', 815, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:11'),
(489, 13, 'archive_patient', 822, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Archiel R. Cabanag\",\"original_id\":\"822\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:14'),
(490, 13, 'archive_patient', 825, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Allan Khey\",\"original_id\":\"825\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 15:31:17');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(491, 13, 'restore_patient', 825, '{\"patient_id\":\"825\",\"patient_name\":\"Allan Khey\",\"restore_time\":\"2026-04-05 20:54:54\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:54:54'),
(492, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:55:08'),
(493, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:59:02'),
(494, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:59:24'),
(495, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:59:32'),
(496, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:59:45'),
(497, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:00:10'),
(498, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:00:33'),
(499, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:00:53'),
(500, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:01:16'),
(501, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:02:02'),
(502, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:02:26'),
(503, 13, 'archive_patient', 825, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Allan Khey\",\"original_id\":\"825\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:02:32'),
(504, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:04:54'),
(505, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:07:17'),
(506, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:08:06'),
(507, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:27:40'),
(508, 13, 'restore_patient', 825, '{\"patient_id\":\"825\",\"patient_name\":\"Allan Khey\",\"restore_time\":\"2026-04-06 11:28:06\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"not_applicable\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":null,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:28:06'),
(509, 13, 'archive_patient', 825, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Allan Khey\",\"original_id\":\"825\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:28:13'),
(510, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:37:39'),
(511, 13, 'send_announcement', 364, '{\"title\":\"Hello Test\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"basic\",\"target_users\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:48:33'),
(512, 13, 'send_announcement', 365, '{\"title\":\"Warren Miras\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:49:56'),
(513, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 11:50:35'),
(514, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:54:26'),
(515, 13, 'restore_patient', 815, '{\"patient_id\":\"815\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-04-06 13:54:44\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":223,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:54:44'),
(516, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:54:49'),
(517, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:57:06'),
(518, 13, 'send_announcement', 366, '{\"title\":\"Warren Miras\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:59:18'),
(519, 13, 'send_announcement', 367, '{\"title\":\"Warren- Specific Resident\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"basic\",\"target_users\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 14:04:56'),
(520, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 14:23:47'),
(521, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 14:35:43'),
(522, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 19:17:17'),
(523, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 22:48:03'),
(524, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 22:48:11'),
(525, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 22:51:19'),
(526, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 22:52:13'),
(527, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 22:54:41'),
(528, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:04:36'),
(529, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:21:08'),
(530, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:21:55'),
(531, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:25:46'),
(532, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:31:02'),
(533, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:10:55'),
(534, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:12:48'),
(535, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:15:10'),
(536, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:46:33'),
(537, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:47:30'),
(538, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:49:33'),
(539, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:57:40'),
(540, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:58:39'),
(541, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:01:04'),
(542, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:04:16'),
(543, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:12:36'),
(544, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:13:25'),
(545, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:16:14'),
(546, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:21:25'),
(547, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:22:25'),
(548, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 01:22:28'),
(549, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 12:22:51'),
(550, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 12:26:01'),
(551, 13, 'export_bulk_pdf', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 12:26:48'),
(552, 13, 'archive_patient', 815, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 12:27:30'),
(553, 13, 'restore_patient', 815, '{\"patient_id\":\"815\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-04-07 12:27:36\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":223,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 12:27:36'),
(554, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:14:49'),
(555, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:14:52'),
(556, 13, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:14:54'),
(557, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:14:56'),
(558, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:14:59'),
(559, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:15:02'),
(560, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:15:08'),
(561, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:29:55'),
(562, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:30:03'),
(563, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:36:28'),
(564, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:36:32'),
(565, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:36:54'),
(566, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:42:28'),
(567, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:42:55'),
(568, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:44:33'),
(569, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:45:29'),
(570, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:45:52'),
(571, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 13:46:34'),
(572, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:04:25'),
(573, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:05:10'),
(574, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:12:58'),
(575, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:13:11'),
(576, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:13:49'),
(577, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:15:04'),
(578, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:15:38'),
(579, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:23:20'),
(580, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:24:17'),
(581, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:25:03'),
(582, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:27:18'),
(583, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:27:26'),
(584, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:27:29'),
(585, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:27:37'),
(586, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:29:14'),
(587, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:30:14'),
(588, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:31:16'),
(589, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:31:39'),
(590, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:31:52'),
(591, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:36:00'),
(592, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:37:04'),
(593, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:38:31'),
(594, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:38:56'),
(595, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:39:11'),
(596, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:39:41'),
(597, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:39:46'),
(598, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:26'),
(599, 13, 'view_patient', 816, '{\"patient_id\":816,\"patient_name\":\"Juzaly VIllaruz\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:30'),
(600, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:36'),
(601, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:40'),
(602, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:45'),
(603, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:47'),
(604, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:40:49'),
(605, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:41:05'),
(606, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:41:10'),
(607, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:48:43'),
(608, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:50:05'),
(609, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:51:20'),
(610, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 14:57:01'),
(611, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:00:31'),
(612, 13, 'view_patient', 819, '{\"patient_id\":819,\"patient_name\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:03:09'),
(613, 13, 'add_patient', 826, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Jhea Claire L. Oropesa\",\"patient_id\":\"826\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:05:13'),
(614, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:05:16'),
(615, 13, 'view_patient', 321, '{\"patient_id\":321,\"patient_name\":\"Fernando Lopez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:09:39'),
(616, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:09:44'),
(617, 13, 'send_announcement', 368, '{\"title\":\"Jhea Claire Oropesa Announcement\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"basic\",\"target_users\":\"Jhea Claire Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:19:04'),
(618, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:21:26'),
(619, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:22:34'),
(620, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:23:40'),
(621, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:24:05'),
(622, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:24:24'),
(623, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 15:36:22'),
(624, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:14:48'),
(625, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:14:51'),
(626, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:14:55'),
(627, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:15:16'),
(628, 13, 'send_announcement', 369, '{\"title\":\"Hello Landing Page\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:39:56'),
(629, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:13:28'),
(630, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:15:43'),
(631, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:16:33'),
(632, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:16:37'),
(633, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:16:39'),
(634, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:16:48'),
(635, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:17:16'),
(636, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:20:31'),
(637, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:20:39'),
(638, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:21:32'),
(639, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:22:08'),
(640, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:30:00'),
(641, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:31:02'),
(642, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:31:18'),
(643, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:33:01'),
(644, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:33:22'),
(645, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:33:28'),
(646, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:35:09'),
(647, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:36:02'),
(648, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:36:19'),
(649, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:38:07'),
(650, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:38:45'),
(651, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:38:58'),
(652, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:39:11'),
(653, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:40:15'),
(654, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:41:04'),
(655, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:41:41'),
(656, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:41:56'),
(657, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:42:11'),
(658, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 21:43:10'),
(659, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 22:09:59'),
(660, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 22:10:09'),
(661, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 22:22:46'),
(662, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:24:21'),
(663, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:25:09'),
(664, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:25:20'),
(665, 13, 'send_announcement', 370, '{\"title\":\"Warren Announcement\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:26:37'),
(666, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:36:19'),
(667, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:36:41'),
(668, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 23:37:16'),
(669, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 00:03:58'),
(670, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 00:08:15'),
(671, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 00:08:33'),
(672, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:12:51'),
(673, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:14:00'),
(674, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:16:28'),
(675, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:16:35'),
(676, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:16:40'),
(677, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:19:11'),
(678, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:30:29'),
(679, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:32:10'),
(680, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:32:29'),
(681, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:36:04'),
(682, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:38:19'),
(683, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:38:50'),
(684, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:43:35'),
(685, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:46:58');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(686, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:48:52'),
(687, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:49:49'),
(688, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:49:58'),
(689, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:50:13'),
(690, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:50:29'),
(691, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:55:50'),
(692, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:56:15'),
(693, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:56:32'),
(694, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:56:56'),
(695, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 07:58:16'),
(696, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:05:43'),
(697, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:06:12'),
(698, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:09:24'),
(699, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:09:54'),
(700, 13, 'archive_patient', 826, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Jhea Claire L. Oropesa\",\"original_id\":\"826\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:10:44'),
(701, 13, 'restore_patient', 826, '{\"patient_id\":\"826\",\"patient_name\":\"Jhea Claire L. Oropesa\",\"restore_time\":\"2026-04-08 08:10:54\",\"restored_by\":\"Archiel R. Cabanag\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":235,\"added_by_final\":13,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:10:54'),
(702, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:11:01'),
(703, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:11:48'),
(704, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:12:03'),
(705, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:12:08'),
(706, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:12:22'),
(707, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 08:12:33'),
(708, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:10:56'),
(709, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:11:05'),
(710, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:11:56'),
(711, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:21:13'),
(712, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:27:43'),
(713, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:28:31'),
(714, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:28:40'),
(715, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:35:08'),
(716, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:35:14'),
(717, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:43:36'),
(718, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:47:05'),
(719, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:47:17'),
(720, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:47:31'),
(721, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:48:33'),
(722, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:48:37'),
(723, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 15:49:32'),
(724, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:14:54'),
(725, 13, 'print_patient', 826, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Jhea Claire L. Oropesa\",\"patient_id\":826}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:15:23'),
(726, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:15:38'),
(727, 13, 'view_patient', 826, '{\"patient_id\":826,\"patient_name\":\"Jhea Claire L. Oropesa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:18:17'),
(728, 13, 'archive_patient', 826, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Jhea Claire L. Oropesa\",\"original_id\":\"826\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:18:41'),
(729, 13, 'send_announcement', 371, '{\"title\":\"Announcement\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Clint Mingo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:28:17'),
(730, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:42:27'),
(731, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:42:40'),
(732, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:43:31'),
(733, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:43:41'),
(734, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:47:38'),
(735, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:53:26'),
(736, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:53:37'),
(737, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 16:54:45'),
(738, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 17:29:14'),
(739, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 19:48:43'),
(740, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 19:49:17'),
(741, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 22:05:40'),
(742, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 22:06:02'),
(743, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 22:07:08'),
(744, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 23:36:32'),
(745, 12, 'print_patient', 815, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":815}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 23:36:36'),
(746, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 00:20:43'),
(747, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 00:21:00'),
(748, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 00:23:07'),
(749, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 07:36:48'),
(750, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 07:37:06'),
(751, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 07:37:18'),
(752, 12, 'send_announcement', 372, '{\"title\":\"Lab Result\",\"audience_type\":\"specific\",\"target_count\":1,\"announcement_type\":\"lab_result\",\"target_users\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 07:50:46'),
(753, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 07:54:38'),
(754, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 09:11:21'),
(755, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 13:52:07'),
(756, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 15:50:37'),
(757, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 17:04:46'),
(758, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 17:04:59'),
(759, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 17:07:55'),
(760, 13, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 17:08:03'),
(761, 12, 'staff_login', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:07:13'),
(762, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:07:36'),
(763, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:07:49'),
(764, 12, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:07:51'),
(765, 12, 'view_patient', 816, '{\"patient_id\":816,\"patient_name\":\"Juzaly VIllaruz\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:09:17'),
(766, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:10:02'),
(767, 12, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:10:05'),
(768, 12, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:10:54'),
(769, 12, 'print_patient', 824, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Jociel Studio\",\"patient_id\":824}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:11:07'),
(770, 12, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:12:09'),
(771, 12, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:12:20'),
(772, 12, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:12:25'),
(773, 12, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:16:07'),
(774, 12, 'archive_patient', 815, '{\"full_name\":\"Russel Evan Loquinario\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:16:58'),
(775, 12, 'restore_patient', 815, '{\"patient_id\":\"815\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-04-09 21:18:20\",\"restored_by\":\"Russel Evan Loquinario\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":223,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:18:20'),
(776, 12, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:22:39'),
(777, 12, 'send_announcement', 373, '{\"title\":\"Cebu Eastern College\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:27:51'),
(778, 12, 'send_announcement', 374, '{\"title\":\"For All Resident\",\"audience_type\":\"public\",\"target_count\":11,\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:31:55'),
(779, 12, 'staff_logout', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"username\":\"Russel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 21:45:19'),
(780, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:49:08'),
(781, 13, 'add_patient', 827, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"827\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:53:50'),
(782, 13, 'view_patient', 827, '{\"patient_id\":827,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:54:26'),
(783, 13, 'view_patient', 827, '{\"patient_id\":827,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:54:57'),
(784, 13, 'print_patient', 827, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":827}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:55:05'),
(785, 13, 'archive_patient', 827, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"827\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 08:59:42'),
(786, 13, 'send_announcement', 375, '{\"title\":\"Test Announcemnt\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 09:04:06'),
(787, 13, 'send_announcement', 376, '{\"title\":\"TEst2 Announcement\",\"audience_type\":\"public\",\"target_count\":11,\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 09:05:53'),
(788, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 09:06:00'),
(789, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:27:17'),
(790, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:37:35'),
(791, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:46:02'),
(792, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:47:56'),
(793, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:47:58'),
(794, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:01'),
(795, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:23'),
(796, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:28'),
(797, 13, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:30'),
(798, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:33'),
(799, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:35'),
(800, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:48:39'),
(801, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:08'),
(802, 13, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:11'),
(803, 13, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:13'),
(804, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:19'),
(805, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:28'),
(806, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:44'),
(807, 15, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:47'),
(808, 15, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:50'),
(809, 15, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:53'),
(810, 15, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:49:57'),
(811, 15, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:51:33'),
(812, 15, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:51:35'),
(813, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:52:28'),
(814, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:52:44'),
(815, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:52:52'),
(816, 16, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:52:59'),
(817, 16, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:53:07'),
(818, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:53:55'),
(819, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:53:58'),
(820, 14, 'staff_login', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:54:16'),
(821, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:54:22'),
(822, 14, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:54:25'),
(823, 14, 'view_patient', 820, '{\"patient_id\":820,\"patient_name\":\"Christian Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:54:27'),
(824, 14, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:54:30'),
(825, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:57:11'),
(826, 14, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:57:20'),
(827, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:59:09'),
(828, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:59:36'),
(829, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 00:59:58'),
(830, 14, 'print_patient', 815, '{\"full_name\":\"Lance Christine Gallardo\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":815}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:01'),
(831, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:21'),
(832, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:31'),
(833, 14, 'staff_logout', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:35'),
(834, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:46'),
(835, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:00:53'),
(836, 15, 'archive_patient', 815, '{\"full_name\":\"Warren Miguel Miras\",\"patient_name\":\"Warren Miguel Miras\",\"original_id\":\"815\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:02:37'),
(837, 15, 'restore_patient', 815, '{\"patient_id\":\"815\",\"patient_name\":\"Warren Miguel Miras\",\"restore_time\":\"2026-04-12 01:02:42\",\"restored_by\":\"Warren Miguel Miras\",\"restore_type\":\"hard_delete\",\"user_id_handled\":\"preserved\",\"added_by_handled\":\"preserved\",\"consultation_notes_restored\":0,\"medical_info_restored\":true,\"medical_fields_restored\":[\"gender\"],\"verification\":{\"patient_record\":\"verified\",\"user_id_final\":223,\"added_by_final\":12,\"medical_info\":\"verified\",\"consultation_notes_count\":0,\"consultation_notes\":\"none_found\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:02:42'),
(838, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:07:00'),
(839, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:07:05'),
(840, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:07:11'),
(841, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:07:22'),
(842, 14, 'staff_login', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:18:16'),
(843, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:18:25'),
(844, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:18:41'),
(845, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:18:44'),
(846, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:19:01'),
(847, 14, 'staff_logout', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:19:25'),
(848, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:19:42'),
(849, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:19:49'),
(850, 15, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:19:58'),
(851, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:21:06'),
(852, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:21:25'),
(853, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:21:33'),
(854, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:21:43'),
(855, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:26:20'),
(856, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:27:08'),
(857, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:30:42'),
(858, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:30:51'),
(859, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:30:58'),
(860, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:31:04'),
(861, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:31:35'),
(862, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:31:42'),
(863, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:31:47'),
(864, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:40:01'),
(865, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:50:54'),
(866, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:51:53'),
(867, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 01:53:20'),
(868, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:34:32'),
(869, 16, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:36:09'),
(870, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:36:25'),
(871, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:36:33'),
(872, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:36:49'),
(873, 15, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:36:53'),
(874, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:37:13'),
(875, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:37:27'),
(876, 15, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:37:45'),
(877, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:38:08'),
(878, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:38:34'),
(879, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:38:42'),
(880, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:38:49'),
(881, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:39:21'),
(882, 16, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:40:10');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(883, 16, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:40:19'),
(884, 16, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:40:37'),
(885, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:40:40'),
(886, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:41:53'),
(887, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:42:58'),
(888, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:43:19'),
(889, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:43:31'),
(890, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:43:38'),
(891, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:43:44'),
(892, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:44:31'),
(893, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:44:36'),
(894, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:44:45'),
(895, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:44:52'),
(896, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:46:43'),
(897, 16, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:47:24'),
(898, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:47:33'),
(899, 16, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:47:46'),
(900, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:47:50'),
(901, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:49:14'),
(902, 16, 'add_patient', 828, '{\"full_name\":\"Leandro Labos\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":\"828\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:51:24'),
(903, 16, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:51:27'),
(904, 16, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:52:03'),
(905, 16, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:52:24'),
(906, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:52:30'),
(907, 16, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:54:35'),
(908, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:55:11'),
(909, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:55:27'),
(910, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:55:35'),
(911, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:56:21'),
(912, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:56:40'),
(913, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:56:44'),
(914, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:56:56'),
(915, 16, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:57:58'),
(916, 16, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:08'),
(917, 16, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:11'),
(918, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:14'),
(919, 14, 'staff_login', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:22'),
(920, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:39'),
(921, 14, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:58:42'),
(922, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:59:02'),
(923, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:59:26'),
(924, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 02:59:50'),
(925, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:17:44'),
(926, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:02'),
(927, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:18'),
(928, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:22'),
(929, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:27'),
(930, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:31'),
(931, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:22:41'),
(932, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:23:26'),
(933, 14, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:23:28'),
(934, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:23:40'),
(935, 14, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:23:42'),
(936, 14, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:24:05'),
(937, 14, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:24:22'),
(938, 14, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:24:30'),
(939, 14, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:24:46'),
(940, 14, 'staff_logout', NULL, '{\"full_name\":\"Lance Christine Gallardo\",\"username\":\"Lance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:25:17'),
(941, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:25:43'),
(942, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:26:01'),
(943, 15, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:26:58'),
(944, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:27:08'),
(945, 15, 'send_announcement', 377, '{\"title\":\"Hello Announcement\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:27:46'),
(946, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:37:12'),
(947, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:38:20'),
(948, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 03:38:27'),
(949, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 09:03:35'),
(950, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 09:03:49'),
(951, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 09:05:11'),
(952, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 09:05:18'),
(953, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-12 09:05:37'),
(954, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 12:47:52'),
(955, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 12:48:07'),
(956, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:43:35'),
(957, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:43:52'),
(958, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:48:22'),
(959, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:48:39'),
(960, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:50:49'),
(961, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 13:57:06'),
(962, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:28:29'),
(963, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:36:05'),
(964, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:38:19'),
(965, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:38:47'),
(966, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:39:24'),
(967, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:39:51'),
(968, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:26'),
(969, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:28'),
(970, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:35'),
(971, 13, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:42'),
(972, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:52'),
(973, 13, 'print_patient', 828, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":828}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:40:55'),
(974, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:41:23'),
(975, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:41:33'),
(976, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:41:51'),
(977, 15, 'view_patient', 811, '{\"patient_id\":811,\"patient_name\":\"Archiel R. Cabanag\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:42:03'),
(978, 15, 'send_announcement', 378, '{\"title\":\"Test Announcement\",\"audience_type\":\"landing_page\",\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:43:03'),
(979, 15, 'send_announcement', 379, '{\"title\":\"All Student Announcement\",\"audience_type\":\"public\",\"target_count\":11,\"announcement_type\":\"basic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:46:08'),
(980, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:46:42'),
(981, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 14:46:48'),
(982, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:48:44'),
(983, 15, 'view_patient', 815, '{\"patient_id\":815,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:48:47'),
(984, 15, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 15:48:50'),
(985, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:25:17'),
(986, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:25:43'),
(987, 13, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:25:46'),
(988, 13, 'view_patient', 821, '{\"patient_id\":821,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:25:51'),
(989, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:26:12'),
(990, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:28:35'),
(991, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:33:15'),
(992, 13, 'view_patient', 823, '{\"patient_id\":823,\"patient_name\":\"Jerecho Latosa\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:33:18'),
(993, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-17 12:33:22'),
(994, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:36:57'),
(995, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 12:43:58'),
(996, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:18:41'),
(997, 13, 'add_patient', 829, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":\"829\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:20:43'),
(998, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:20:55'),
(999, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:21:11'),
(1000, 13, 'print_patient', 829, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Warren Miguel Miras\",\"patient_id\":829}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:21:14'),
(1001, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:21:41'),
(1002, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:21:47'),
(1003, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:22:05'),
(1004, 15, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:22:14'),
(1005, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:22:33'),
(1006, 15, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:27:36'),
(1007, 15, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:28:59'),
(1008, 15, 'view_patient', 824, '{\"patient_id\":824,\"patient_name\":\"Jociel Studio\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:29:08'),
(1009, 15, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 14:33:29'),
(1010, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 09:24:56'),
(1011, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 09:25:09'),
(1012, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 09:44:25'),
(1013, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:01:17'),
(1014, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:02:08'),
(1015, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:14:30'),
(1016, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:16:17'),
(1017, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:16:32'),
(1018, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:17:45'),
(1019, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"Creshiel Manloloyo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:18:40'),
(1020, 13, 'update_patient', 828, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Creshiel Manloloyo\",\"patient_id\":828,\"fields\":{\"gender\":\"Male\",\"height\":null,\"weight\":null,\"temperature\":null,\"blood_pressure\":\"120\\/90\",\"blood_type\":\"A+\",\"allergies\":\"Local Anesthetic (ex. Lidocaine), Others: asd\",\"medical_history\":\"Good health: Yes; Under treatment: Yes; Serious illness \\/ surgery: Yes; Uses tobacco: No; Condition being treated: asd; Illness \\/ operation details: asdas; Medication details: asdasd; Bleeding time: asd; Conditions: High Blood Pressure\",\"current_medications\":\"asdasd\",\"family_history\":\"High Blood Pressure\",\"immunization_record\":null,\"chronic_conditions\":\"High Blood Pressure\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:19:33'),
(1021, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"asd asd asd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:19:36'),
(1022, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:19:42'),
(1023, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:33:33'),
(1024, 13, 'view_patient', 828, '{\"patient_id\":828,\"patient_name\":\"asd asd asd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:33:47'),
(1025, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:40:08'),
(1026, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 10:42:17'),
(1027, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 13:25:39'),
(1028, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 13:58:42'),
(1029, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 21:36:55'),
(1030, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 21:37:08'),
(1031, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 23:27:27'),
(1032, 13, 'view_patient', 829, '{\"patient_id\":829,\"patient_name\":\"Warren Miguel Miras\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 23:30:48'),
(1033, 13, 'add_patient', 833, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Cabanag, Archiel Rosel\",\"patient_id\":\"833\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 23:53:42'),
(1034, 13, 'add_patient', 834, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Cabanag, Archiel Rosel\",\"patient_id\":\"834\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-19 23:57:55'),
(1035, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:06:02'),
(1036, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:13:27'),
(1037, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:23:00'),
(1038, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:24:17'),
(1039, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:25:01'),
(1040, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:25:53'),
(1041, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:31:04'),
(1042, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:43:42'),
(1043, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:46:40'),
(1044, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:50:07'),
(1045, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:53:20'),
(1046, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:56:12'),
(1047, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:57:48'),
(1048, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 00:59:40'),
(1049, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:00:38'),
(1050, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:17:03'),
(1051, 13, 'print_patient', 834, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Cabanag, Archiel Rosel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:17:04'),
(1052, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:18:17'),
(1053, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:20:48'),
(1054, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:38:47'),
(1055, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:48:29'),
(1056, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 01:58:11'),
(1057, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:05:29'),
(1058, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:07:07'),
(1059, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:09:57'),
(1060, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:10:21'),
(1061, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:11:23'),
(1062, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:15:23'),
(1063, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:33:42'),
(1064, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:34:25'),
(1065, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:34:32'),
(1066, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:34:43'),
(1067, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:34:51'),
(1068, 13, 'add_patient', 835, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Miras, Warren Miguel\",\"patient_id\":\"835\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:36:20'),
(1069, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:36:22'),
(1070, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:36:34'),
(1071, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:40:38'),
(1072, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:40:46'),
(1073, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:41:14'),
(1074, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:41:24'),
(1075, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:43:51'),
(1076, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:43:56'),
(1077, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:08'),
(1078, 13, 'update_patient', 834, '{\"patient_name\":\"Cabanag, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:11'),
(1079, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:13'),
(1080, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:20');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1081, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:25'),
(1082, 13, 'update_patient', 834, '{\"patient_name\":\"Cabanag, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:32'),
(1083, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:34'),
(1084, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:49'),
(1085, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:53'),
(1086, 13, 'update_patient', 834, '{\"patient_name\":\"Cabanag boi, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:56'),
(1087, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:45:58'),
(1088, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:46:23'),
(1089, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:46:26'),
(1090, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:47:40'),
(1091, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:47:53'),
(1092, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:47:55'),
(1093, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:48:01'),
(1094, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:48:03'),
(1095, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:48:06'),
(1096, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:48:08'),
(1097, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:49:06'),
(1098, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:49:08'),
(1099, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:49:35'),
(1100, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:49:41'),
(1101, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:49:43'),
(1102, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:51:07'),
(1103, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:51:36'),
(1104, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:52:35'),
(1105, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:22'),
(1106, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:24'),
(1107, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:26'),
(1108, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:36'),
(1109, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:38'),
(1110, 13, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:46'),
(1111, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:53:48'),
(1112, 13, 'staff_logout', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:54:42'),
(1113, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:54:59'),
(1114, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:55:06'),
(1115, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:56:13'),
(1116, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:57:46'),
(1117, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:57:55'),
(1118, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:58:03'),
(1119, 15, 'update_patient', 835, '{\"patient_name\":\"Miras, Warren\",\"patient_id\":835}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:58:05'),
(1120, 15, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:58:07'),
(1121, 15, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:58:38'),
(1122, 15, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:59:39'),
(1123, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 02:59:50'),
(1124, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:00:12'),
(1125, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:00:19'),
(1126, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:00:22'),
(1127, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:00:50'),
(1128, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:00:56'),
(1129, 16, 'update_patient', 834, '{\"patient_name\":\"Cabanag boi, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:01:06'),
(1130, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:01:09'),
(1131, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:01:12'),
(1132, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:13:40'),
(1133, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:13:46'),
(1134, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:24:03'),
(1135, 16, 'add_patient', 836, '{\"full_name\":\"Leandro Labos\",\"patient_name\":\"Labos, Leandro Solo\",\"patient_id\":\"836\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:36:34'),
(1136, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:36:38'),
(1137, 16, 'update_patient', 836, '{\"patient_name\":\"Labos, Leandro\",\"patient_id\":836}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:36:46'),
(1138, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:36:48'),
(1139, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:38:17'),
(1140, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:38:26'),
(1141, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:38:36'),
(1142, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:38:41'),
(1143, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:39:21'),
(1144, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:39:30'),
(1145, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:46:22'),
(1146, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:46:39'),
(1147, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 03:46:45'),
(1148, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:05:51'),
(1149, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:15:35'),
(1150, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:17:10'),
(1151, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:23:43'),
(1152, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:23:55'),
(1153, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:27:41'),
(1154, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:27:50'),
(1155, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:31:59'),
(1156, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:35:17'),
(1157, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:39:20'),
(1158, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:39:56'),
(1159, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:40:03'),
(1160, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:44:35'),
(1161, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:44:41'),
(1162, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:44:48'),
(1163, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:44:55'),
(1164, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:45:04'),
(1165, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:47:38'),
(1166, 16, 'update_patient', 836, '{\"patient_name\":\"Labos, Leandro\",\"patient_id\":836}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:47:45'),
(1167, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:47:47'),
(1168, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:49:17'),
(1169, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:49:31'),
(1170, 16, 'update_patient', 834, '{\"patient_name\":\"Cabanag boi, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:49:33'),
(1171, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:49:35'),
(1172, 16, 'update_patient', 834, '{\"patient_name\":\"Cabanag boi, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:49:58'),
(1173, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:50:00'),
(1174, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:57:23'),
(1175, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 11:57:43'),
(1176, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:02:17'),
(1177, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:06:21'),
(1178, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:08:41'),
(1179, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:10:21'),
(1180, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:11:08'),
(1181, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:11:50'),
(1182, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:12:35'),
(1183, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:13:06'),
(1184, 16, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:13:57'),
(1185, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:15:36'),
(1186, 16, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:29:05'),
(1187, 16, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:37:13'),
(1188, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-20 12:38:34'),
(1189, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:22:08'),
(1190, 13, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:28:01'),
(1191, 13, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:53:30'),
(1192, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag boi, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:53:48'),
(1193, 13, 'update_patient', 834, '{\"patient_name\":\"Cabanag, Archiel\",\"patient_id\":834}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:53:53'),
(1194, 13, 'view_patient', 834, '{\"patient_id\":834,\"patient_name\":\"Cabanag, Archiel Rosel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:53:55'),
(1195, 13, 'add_patient', 837, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Otida, Jaycar Sulu\",\"patient_id\":\"837\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:56:48'),
(1196, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:56:52'),
(1197, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:57:04'),
(1198, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:57:55'),
(1199, 13, 'update_patient', 837, '{\"patient_name\":\"Otida, Jaycar\",\"patient_id\":837}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:58:04'),
(1200, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 22:58:06'),
(1201, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:01:03'),
(1202, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:31:03'),
(1203, 13, 'view_patient', 836, '{\"patient_id\":836,\"patient_name\":\"Labos, Leandro Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:35:17'),
(1204, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:36:01'),
(1205, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:36:38'),
(1206, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:37:52'),
(1207, 13, 'update_patient', 837, '{\"patient_name\":\"Otida, Jaycar\",\"patient_id\":837}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:38:11'),
(1208, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:38:14'),
(1209, 13, 'add_patient', 838, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Casola, Ronie Solo\",\"patient_id\":\"838\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:42:39'),
(1210, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:42:41'),
(1211, 13, 'update_patient', 838, '{\"patient_name\":\"Casola, Ronie\",\"patient_id\":838}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:42:46'),
(1212, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:42:48'),
(1213, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:44:51'),
(1214, 13, 'update_patient', 838, '{\"patient_name\":\"Casola, Ronie\",\"patient_id\":838}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:45:14'),
(1215, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:45:16'),
(1216, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:47:37'),
(1217, 13, 'update_patient', 838, '{\"patient_name\":\"Casola, Ronie\",\"patient_id\":838,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:47:42'),
(1218, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:47:44'),
(1219, 13, 'update_patient', 838, '{\"patient_name\":\"Casola, Ronie\",\"patient_id\":838,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:47:52'),
(1220, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:47:54'),
(1221, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:48:03'),
(1222, 13, 'update_patient', 837, '{\"patient_name\":\"Otida, Jaycar\",\"patient_id\":837,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:48:19'),
(1223, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:48:21'),
(1224, 13, 'add_patient', 841, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Torion, Lyvicon Figuracion\",\"patient_id\":\"841\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:58:46'),
(1225, 13, 'view_patient', 841, '{\"patient_id\":841,\"patient_name\":\"Torion, Lyvicon Figuracion\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 23:58:49'),
(1226, 13, 'view_patient', 835, '{\"patient_id\":835,\"patient_name\":\"Miras, Warren Miguel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 00:40:04'),
(1227, 13, 'archive_patient', 841, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Torion, Lyvicon Figuracion\",\"original_id\":\"841\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 00:40:26'),
(1228, 13, 'archive_patient', 834, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Cabanag, Archiel Rosel\",\"original_id\":\"834\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 00:40:42'),
(1229, 13, 'archive_patient', 835, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Miras, Warren Miguel\",\"original_id\":\"835\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 00:45:37'),
(1230, 13, 'archive_patient', 836, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Labos, Leandro Solo\",\"original_id\":\"836\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 00:46:23'),
(1231, 13, 'archive_patient', 837, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Otida, Jaycar Sulu\",\"original_id\":837}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:03:02'),
(1232, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:04:15'),
(1233, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:06:59'),
(1234, 13, 'update_patient', 838, '{\"patient_name\":\"Casola, Ronie\",\"patient_id\":838,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:07:06'),
(1235, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:07:08'),
(1236, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:09:55'),
(1237, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:11:41'),
(1238, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:41:34'),
(1239, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:41:49'),
(1240, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:41:52'),
(1241, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:42:06'),
(1242, 13, 'archive_patient', 837, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Otida, Jaycar Sulu\",\"original_id\":837}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:44:11'),
(1243, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:44:17'),
(1244, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:45:00'),
(1245, 13, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:45:12'),
(1246, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:45:18'),
(1247, 13, 'update_patient', 837, '{\"patient_name\":\"Otida, Jaycar\",\"patient_id\":837,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:46:43'),
(1248, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:46:45'),
(1249, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:46:57'),
(1250, 13, 'update_patient', 837, '{\"patient_name\":\"Otida, Jaycar\",\"patient_id\":837,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:47:00'),
(1251, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:47:02'),
(1252, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 01:48:22'),
(1253, 13, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:35:31'),
(1254, 13, 'staff_login', NULL, '{\"full_name\":\"Archiel R. Cabanag\",\"username\":\"Archiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:36:04'),
(1255, 13, 'add_patient', 842, '{\"full_name\":\"Archiel R. Cabanag\",\"patient_name\":\"Cabanag, Archiel Sulu\",\"patient_id\":\"842\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:57:07'),
(1256, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:51:12'),
(1257, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:51:32'),
(1258, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:51:45'),
(1259, 15, 'staff_logout', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:51:57'),
(1260, 16, 'staff_login', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:05'),
(1261, 16, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:12'),
(1262, 16, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:17'),
(1263, 16, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:20'),
(1264, 16, 'update_patient', 842, '{\"patient_name\":\"Cabanag, Archiel\",\"patient_id\":842,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:26'),
(1265, 16, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:28'),
(1266, 16, 'view_patient', 838, '{\"patient_id\":838,\"patient_name\":\"Casola, Ronie Solo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:33'),
(1267, 16, 'staff_logout', NULL, '{\"full_name\":\"Leandro Labos\",\"username\":\"Leandro\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:37'),
(1268, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:44'),
(1269, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:50'),
(1270, 15, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:52:56'),
(1271, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:53:09'),
(1272, 15, 'view_patient', 837, '{\"patient_id\":837,\"patient_name\":\"Otida, Jaycar Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:53:12'),
(1273, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 10:53:28');
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `action_type`, `related_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1274, 15, 'staff_login', NULL, '{\"full_name\":\"Warren Miguel Miras\",\"username\":\"Warren\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:28:47'),
(1275, 15, 'view_patient', 842, '{\"patient_id\":842,\"patient_name\":\"Cabanag, Archiel Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:29:01'),
(1276, 15, 'add_patient', 843, '{\"full_name\":\"Warren Miguel Miras\",\"patient_name\":\"Labos, Leandro Sulu\",\"patient_id\":\"843\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:33:33'),
(1277, 15, 'view_patient', 843, '{\"patient_id\":843,\"patient_name\":\"Labos, Leandro Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:33:37'),
(1278, 15, 'view_patient', 843, '{\"patient_id\":843,\"patient_name\":\"Labos, Leandro Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:34:12'),
(1279, 15, 'update_patient', 843, '{\"patient_name\":\"Labos, Leandro\",\"patient_id\":843,\"medical_conditions\":\"[\\\"High Blood Pressure\\\",\\\"Low Blood Pressure\\\",\\\"Epilepsy \\\\\\/ Convulsions\\\",\\\"Sexually Transmitted Disease\\\",\\\"Stomach Troubles \\\\\\/ Ulcer\\\",\\\"Testing for review\\\"]\",\"blood_pressure\":\"120\\/80\",\"blood_type\":\"A+\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:34:18'),
(1280, 15, 'view_patient', 843, '{\"patient_id\":843,\"patient_name\":\"Labos, Leandro Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:34:20'),
(1281, 15, 'view_patient', 843, '{\"patient_id\":843,\"patient_name\":\"Labos, Leandro Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:34:33'),
(1282, 15, 'view_patient', 843, '{\"patient_id\":843,\"patient_name\":\"Labos, Leandro Sulu\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 13:36:25');

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
-- Table structure for table `staff_role_permissions`
--

CREATE TABLE `staff_role_permissions` (
  `position` varchar(100) NOT NULL,
  `can_manage_patient_records` tinyint(1) NOT NULL DEFAULT 0,
  `can_access_consultation_notes` tinyint(1) NOT NULL DEFAULT 0,
  `can_create_consultation_notes` tinyint(1) NOT NULL DEFAULT 0,
  `can_export_patient_records` tinyint(1) NOT NULL DEFAULT 0,
  `can_archive_patient_records` tinyint(1) NOT NULL DEFAULT 0,
  `can_print_patient_records` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `can_add_patient_records` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_role_permissions`
--

INSERT INTO `staff_role_permissions` (`position`, `can_manage_patient_records`, `can_access_consultation_notes`, `can_create_consultation_notes`, `can_export_patient_records`, `can_archive_patient_records`, `can_print_patient_records`, `created_at`, `updated_at`, `can_add_patient_records`) VALUES
('Assistant Doctor', 1, 1, 1, 0, 1, 1, '2026-04-11 17:16:46', '2026-04-21 05:34:29', 0),
('Nurse', 1, 0, 0, 1, 1, 1, '2026-04-11 17:16:46', '2026-04-20 16:40:20', 1),
('Working Scholar', 1, 0, 0, 1, 1, 1, '2026-04-11 17:16:46', '2026-04-20 04:33:10', 0);

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
(147, 188, 'login', '2026-02-12 16:55:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(148, 2, 'login', '2026-02-12 16:58:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(149, 1, 'login', '2026-02-12 17:00:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(150, 188, 'login', '2026-02-12 20:43:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(151, 2, 'login', '2026-02-12 20:44:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(152, 188, 'login', '2026-02-12 20:58:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(153, 188, 'login', '2026-02-13 19:52:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(154, 188, 'logout', '2026-02-13 19:52:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(155, 2, 'login', '2026-02-13 19:53:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(156, 2, 'login', '2026-02-13 22:52:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(157, 1, 'login', '2026-02-14 06:23:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(158, 194, 'login', '2026-02-14 06:26:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(159, 2, 'login', '2026-02-14 06:31:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(160, 196, 'login', '2026-02-14 07:37:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(161, 196, 'login', '2026-02-14 12:00:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(162, 196, 'login', '2026-02-14 12:56:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(163, 2, 'login', '2026-02-14 12:57:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(164, 1, 'login', '2026-02-14 13:15:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(165, 196, 'logout', '2026-02-14 13:16:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(166, 197, 'login', '2026-02-14 13:16:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(167, 197, 'login', '2026-02-14 14:18:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(168, 197, 'login', '2026-02-14 14:40:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(169, 197, 'login', '2026-02-14 16:11:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(170, 197, 'login', '2026-02-14 16:41:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(171, 197, 'login', '2026-02-14 17:00:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(172, 197, 'login', '2026-02-14 17:15:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(173, 2, 'login', '2026-02-14 17:20:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(174, 197, 'login', '2026-02-14 17:29:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(175, 197, 'logout', '2026-02-14 17:47:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(176, 2, 'logout', '2026-02-14 17:50:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(177, 8, 'login', '2026-02-14 17:50:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(178, 198, 'login', '2026-02-14 17:51:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(179, 8, 'logout', '2026-02-14 18:22:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(180, 9, 'login', '2026-02-14 18:23:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(181, 198, 'login', '2026-02-14 22:41:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(182, 9, 'login', '2026-02-14 22:55:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(183, 198, 'login', '2026-02-15 20:25:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0'),
(184, 9, 'login', '2026-02-15 20:31:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(185, 203, 'logout', '2026-02-18 12:02:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(186, 203, 'login', '2026-02-18 12:02:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(187, 203, 'logout', '2026-02-18 12:05:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(188, 203, 'login', '2026-02-18 12:05:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(189, 203, 'logout', '2026-02-18 12:06:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(190, 198, 'login', '2026-02-18 12:06:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(191, 198, 'logout', '2026-02-18 12:07:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(192, 198, 'login', '2026-02-18 12:07:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(193, 9, 'login', '2026-02-18 12:09:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(194, 9, 'logout', '2026-02-18 12:09:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(195, 9, 'login', '2026-02-18 12:10:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(196, 9, 'logout', '2026-02-18 12:11:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(197, 9, 'login', '2026-02-18 12:16:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(198, 198, 'logout', '2026-02-18 12:17:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(199, 198, 'login', '2026-02-18 12:18:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(200, 198, 'logout', '2026-02-18 12:42:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(201, 203, 'login', '2026-02-18 12:42:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(202, 9, 'login', '2026-02-18 15:53:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(203, 9, 'login', '2026-02-18 17:44:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(204, 9, 'login', '2026-02-18 20:01:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(205, 201, 'login', '2026-02-19 00:28:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(206, 9, 'login', '2026-02-19 00:29:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(207, 201, 'logout', '2026-02-19 00:29:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(208, 203, 'login', '2026-02-19 00:29:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(209, 1, 'login', '2026-02-19 01:17:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(210, 203, 'logout', '2026-02-19 01:18:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(211, 203, 'login', '2026-02-19 01:48:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(212, 203, 'logout', '2026-02-19 01:48:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(213, 9, 'login', '2026-02-19 09:33:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(214, 203, 'login', '2026-02-19 09:59:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(215, 203, 'logout', '2026-02-19 11:42:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(216, 1, 'logout', '2026-02-19 11:47:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(217, 1, 'login', '2026-02-19 11:47:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(218, 203, 'login', '2026-02-19 12:08:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(219, 9, 'login', '2026-02-19 15:25:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(220, 9, 'login', '2026-02-19 19:40:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(221, 1, 'login', '2026-02-19 20:40:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(222, 9, 'login', '2026-02-20 00:56:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(223, 203, 'logout', '2026-02-20 01:00:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(224, 201, 'login', '2026-02-20 01:00:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(225, 9, 'login', '2026-02-20 07:46:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(226, 201, 'logout', '2026-02-20 13:15:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(227, 203, 'login', '2026-02-20 13:15:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(228, 9, 'login', '2026-02-20 16:18:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(229, 9, 'login', '2026-02-20 19:16:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(230, 9, 'logout', '2026-02-20 21:22:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(231, 9, 'login', '2026-02-20 21:22:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(232, 9, 'login', '2026-02-21 00:36:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(233, 9, 'login', '2026-02-21 07:35:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(234, 203, 'login', '2026-02-21 07:40:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(235, 9, 'login', '2026-02-21 07:41:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(236, 9, 'logout', '2026-02-21 08:10:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(237, 9, 'login', '2026-02-21 08:11:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(238, 1, 'login', '2026-02-21 08:19:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(239, 1, 'login', '2026-02-21 10:48:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(240, 9, 'login', '2026-02-21 11:36:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(241, 9, 'login', '2026-02-21 12:43:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(242, 203, 'logout', '2026-02-21 14:22:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(243, 201, 'login', '2026-02-21 14:23:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(244, 203, 'login', '2026-02-21 21:27:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(245, 9, 'login', '2026-02-21 21:29:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(246, 9, 'login', '2026-02-22 09:37:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(247, 1, 'login', '2026-02-22 11:31:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(248, 9, 'login', '2026-02-22 16:59:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(249, 203, 'login', '2026-02-22 19:03:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(250, 9, 'login', '2026-02-22 19:05:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(251, 9, 'login', '2026-02-22 20:32:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(252, 203, 'logout', '2026-02-22 21:37:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(253, 1, 'login', '2026-02-22 21:37:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(254, 205, 'login', '2026-02-22 21:41:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(255, 205, 'logout', '2026-02-23 00:41:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(256, 205, 'login', '2026-02-23 00:41:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(257, 9, 'login', '2026-02-23 06:24:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(258, 9, 'login', '2026-02-23 07:54:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(259, 205, 'login', '2026-02-23 09:09:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(260, 1, 'login', '2026-02-23 09:37:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(261, 205, 'logout', '2026-02-23 09:55:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(262, 205, 'login', '2026-02-23 09:56:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(263, 1, 'logout', '2026-02-23 10:26:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(264, 9, 'login', '2026-02-23 13:38:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(265, 9, 'login', '2026-02-23 13:39:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(266, 9, 'logout', '2026-02-23 13:42:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(267, 1, 'login', '2026-02-23 13:42:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(268, 1, 'login', '2026-02-23 22:24:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(269, 205, 'login', '2026-02-23 22:30:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(270, 205, 'logout', '2026-02-23 22:30:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(271, 205, 'login', '2026-02-23 22:33:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(272, 205, 'logout', '2026-02-23 22:36:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(273, 205, 'login', '2026-02-23 22:36:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(274, 205, 'logout', '2026-02-23 22:36:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(275, 205, 'login', '2026-02-23 22:58:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(276, 205, 'logout', '2026-02-23 23:05:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(277, 205, 'login', '2026-02-23 23:39:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(278, 205, 'logout', '2026-02-23 23:39:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(279, 205, 'login', '2026-02-24 09:05:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(280, 9, 'login', '2026-02-24 09:27:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(281, 9, 'login', '2026-02-24 11:00:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(282, 205, 'logout', '2026-02-24 12:52:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(283, 205, 'login', '2026-02-24 13:20:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(284, 9, 'login', '2026-02-24 19:17:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(285, 9, 'login', '2026-02-26 20:14:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(286, 205, 'login', '2026-02-26 20:23:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(287, 205, 'logout', '2026-02-26 20:58:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(288, 1, 'login', '2026-02-26 21:00:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(289, 199, 'login', '2026-02-26 21:01:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0');
INSERT INTO `user_activity_log` (`id`, `user_id`, `action_type`, `action_timestamp`, `ip_address`, `user_agent`) VALUES
(290, 199, 'logout', '2026-02-26 21:23:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(291, 205, 'login', '2026-02-26 23:11:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(292, 205, 'logout', '2026-02-26 23:12:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(293, 201, 'login', '2026-02-26 23:22:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(294, 201, 'logout', '2026-02-26 23:22:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(295, 201, 'login', '2026-02-26 23:23:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(296, 201, 'logout', '2026-02-26 23:23:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(297, 201, 'login', '2026-02-26 23:25:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(298, 201, 'logout', '2026-02-26 23:25:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(299, 201, 'login', '2026-02-26 23:27:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(300, 201, 'logout', '2026-02-26 23:27:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(301, 201, 'login', '2026-02-26 23:27:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(302, 201, 'logout', '2026-02-26 23:27:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(303, 201, 'login', '2026-02-26 23:39:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(304, 201, 'logout', '2026-02-26 23:39:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(305, 201, 'login', '2026-02-26 23:39:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(306, 201, 'logout', '2026-02-26 23:39:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(307, 201, 'login', '2026-02-26 23:40:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(308, 201, 'logout', '2026-02-26 23:40:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(309, 201, 'login', '2026-02-26 23:43:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(310, 201, 'logout', '2026-02-26 23:43:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(311, 201, 'login', '2026-02-26 23:43:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(312, 201, 'logout', '2026-02-26 23:44:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(313, 201, 'login', '2026-02-26 23:45:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(314, 201, 'logout', '2026-02-26 23:46:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(315, 9, 'login', '2026-02-26 23:46:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(316, 9, 'login', '2026-02-27 16:56:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(317, 205, 'login', '2026-02-27 17:00:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(318, 1, 'login', '2026-02-27 17:01:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(319, 9, 'login', '2026-02-27 21:50:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(320, 205, 'logout', '2026-02-27 21:51:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(321, 9, 'login', '2026-02-28 05:14:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(322, 205, 'login', '2026-02-28 06:06:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(323, 9, 'login', '2026-02-28 09:51:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(324, 205, 'login', '2026-02-28 10:36:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(325, 1, 'login', '2026-02-28 11:19:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(326, 9, 'login', '2026-02-28 13:29:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(327, 9, 'login', '2026-02-28 16:51:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(328, 9, 'login', '2026-02-28 17:57:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(329, 1, 'logout', '2026-02-28 19:57:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(330, 9, 'login', '2026-02-28 19:57:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(331, 9, 'logout', '2026-02-28 19:57:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(332, 9, 'logout', '2026-02-28 19:57:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(333, 1, 'login', '2026-02-28 19:57:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(334, 9, 'login', '2026-02-28 19:57:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(335, 9, 'logout', '2026-02-28 20:05:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(336, 9, 'login', '2026-02-28 20:05:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(337, 205, 'logout', '2026-02-28 20:06:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(338, 205, 'login', '2026-02-28 20:06:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(339, 205, 'login', '2026-03-01 12:55:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(340, 205, 'logout', '2026-03-01 13:19:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(341, 9, 'login', '2026-03-01 13:19:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(342, 205, 'login', '2026-03-02 13:27:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(343, 9, 'login', '2026-03-02 13:59:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(344, 9, 'login', '2026-03-02 18:34:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(345, 205, 'logout', '2026-03-02 18:41:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(346, 1, 'login', '2026-03-02 18:43:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(347, 204, 'login', '2026-03-02 18:46:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(348, 1, 'logout', '2026-03-02 18:47:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(349, 203, 'login', '2026-03-02 18:48:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(350, 203, 'logout', '2026-03-02 18:54:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(351, 9, 'login', '2026-03-02 21:42:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(352, 9, 'login', '2026-03-02 23:00:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(353, 9, 'login', '2026-03-02 23:21:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.109.5 Chrome/142.0.7444.265 Electron/39.3.0 Safari/537.36'),
(354, 9, 'logout', '2026-03-02 23:25:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.109.5 Chrome/142.0.7444.265 Electron/39.3.0 Safari/537.36'),
(355, 1, 'login', '2026-03-02 23:26:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(356, 204, 'logout', '2026-03-02 23:48:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(357, 205, 'login', '2026-03-02 23:54:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(358, 205, 'logout', '2026-03-02 23:54:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(359, 205, 'login', '2026-03-02 23:56:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(360, 205, 'logout', '2026-03-02 23:56:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(361, 205, 'login', '2026-03-02 23:58:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(362, 205, 'logout', '2026-03-02 23:58:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(363, 205, 'login', '2026-03-02 23:58:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(364, 205, 'logout', '2026-03-02 23:58:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(365, 205, 'login', '2026-03-02 23:58:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(366, 205, 'logout', '2026-03-02 23:59:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(367, 205, 'login', '2026-03-03 00:08:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(368, 205, 'logout', '2026-03-03 00:09:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(369, 205, 'login', '2026-03-03 00:10:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(370, 205, 'logout', '2026-03-03 00:51:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(371, 204, 'login', '2026-03-03 00:51:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(372, 204, 'logout', '2026-03-03 00:52:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(373, 205, 'login', '2026-03-03 00:52:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(374, 205, 'logout', '2026-03-03 00:56:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(375, 202, 'login', '2026-03-03 00:56:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(376, 9, 'login', '2026-03-03 00:57:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(377, 202, 'logout', '2026-03-03 01:01:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(378, 205, 'login', '2026-03-03 01:59:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(379, 205, 'logout', '2026-03-03 01:59:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(380, 9, 'login', '2026-03-03 07:21:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(381, 205, 'login', '2026-03-03 09:01:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(382, 9, 'login', '2026-03-03 09:56:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(383, 9, 'login', '2026-03-03 16:56:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(384, 9, 'login', '2026-03-03 20:31:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(385, 205, 'logout', '2026-03-03 21:52:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(386, 1, 'login', '2026-03-04 00:38:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(387, 1, 'logout', '2026-03-04 00:39:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(388, 1, 'login', '2026-03-04 00:41:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(389, 9, 'login', '2026-03-04 00:43:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(390, 9, 'logout', '2026-03-04 00:44:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(391, 1, 'login', '2026-03-04 00:45:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(392, 1, 'logout', '2026-03-04 00:49:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(393, 10, 'login', '2026-03-04 08:52:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(394, 209, 'login', '2026-03-04 09:03:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(395, 209, 'logout', '2026-03-04 09:06:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(396, 209, 'login', '2026-03-04 09:06:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(397, 209, 'logout', '2026-03-04 09:06:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(398, 209, 'login', '2026-03-04 09:08:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(399, 9, 'login', '2026-03-04 15:36:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(400, 9, 'logout', '2026-03-04 15:37:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(401, 9, 'login', '2026-03-04 16:45:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(402, 209, 'logout', '2026-03-04 16:46:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(403, 209, 'login', '2026-03-04 16:47:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(404, 209, 'logout', '2026-03-04 21:44:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(405, 209, 'login', '2026-03-04 22:18:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(406, 209, 'logout', '2026-03-04 22:18:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(407, 9, 'login', '2026-03-04 23:35:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(408, 9, 'login', '2026-03-05 10:28:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(409, 9, 'logout', '2026-03-05 10:56:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(410, 210, 'login', '2026-03-05 11:03:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(411, 210, 'logout', '2026-03-05 11:04:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(412, 210, 'login', '2026-03-05 11:07:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(413, 210, 'logout', '2026-03-05 11:07:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(414, 9, 'login', '2026-03-05 11:08:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(415, 1, 'logout', '2026-03-05 11:45:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(416, 9, 'logout', '2026-03-05 11:46:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(417, 9, 'login', '2026-03-05 13:35:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(418, 9, 'logout', '2026-03-05 13:53:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(419, 9, 'login', '2026-03-05 14:21:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(420, 209, 'login', '2026-03-05 15:21:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(421, 1, 'login', '2026-03-05 15:25:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(422, 209, 'logout', '2026-03-05 15:26:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(423, 220, 'login', '2026-03-05 15:27:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(424, 1, 'logout', '2026-03-05 15:28:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(425, 209, 'login', '2026-03-05 15:29:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(426, 209, 'logout', '2026-03-05 15:32:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(427, 209, 'login', '2026-03-05 15:35:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(428, 9, 'login', '2026-03-05 17:53:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(429, 9, 'login', '2026-03-05 19:50:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(430, 9, 'logout', '2026-03-05 20:10:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(431, 10, 'login', '2026-03-05 20:13:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(432, 10, 'logout', '2026-03-05 20:15:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(433, 10, 'login', '2026-03-05 20:15:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(434, 10, 'logout', '2026-03-05 20:16:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(435, 10, 'login', '2026-03-05 20:16:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(436, 10, 'logout', '2026-03-05 20:16:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(437, 10, 'login', '2026-03-05 20:17:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(438, 10, 'logout', '2026-03-05 20:17:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(439, 10, 'login', '2026-03-05 20:20:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(440, 10, 'logout', '2026-03-05 20:20:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(441, 10, 'login', '2026-03-05 22:52:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(442, 10, 'logout', '2026-03-05 23:00:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(443, 209, 'login', '2026-03-05 23:28:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.110.0 Chrome/142.0.7444.265 Electron/39.6.0 Safari/537.36'),
(444, 209, 'login', '2026-03-06 14:41:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(445, 10, 'login', '2026-03-06 14:43:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(446, 10, 'logout', '2026-03-06 14:43:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(447, 209, 'logout', '2026-03-06 14:44:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(448, 10, 'login', '2026-03-06 14:45:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(449, 209, 'login', '2026-03-06 15:09:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(450, 209, 'logout', '2026-03-06 15:13:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(451, 10, 'logout', '2026-03-06 15:16:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(452, 1, 'login', '2026-03-06 16:22:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(453, 209, 'login', '2026-03-06 16:28:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(454, 209, 'logout', '2026-03-06 16:28:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(455, 210, 'login', '2026-03-06 16:34:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(456, 1, 'logout', '2026-03-06 16:35:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(457, 9, 'login', '2026-03-06 16:36:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(458, 9, 'logout', '2026-03-06 16:50:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(459, 1, 'login', '2026-03-06 16:51:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(460, 210, 'logout', '2026-03-06 17:45:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(461, 1, 'logout', '2026-03-06 17:46:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(462, 9, 'login', '2026-03-06 17:47:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(463, 9, 'logout', '2026-03-06 17:49:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(464, 1, 'login', '2026-03-06 17:49:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(465, 1, 'logout', '2026-03-06 17:52:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(466, 9, 'login', '2026-03-06 17:52:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(467, 209, 'login', '2026-03-06 17:53:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(468, 209, 'logout', '2026-03-06 17:55:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(469, 209, 'login', '2026-03-06 19:59:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(470, 9, 'login', '2026-03-06 20:00:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(471, 209, 'logout', '2026-03-07 08:37:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(472, 1, 'login', '2026-03-07 18:46:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(473, 220, 'login', '2026-03-07 18:51:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(474, 9, 'login', '2026-03-09 00:07:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(475, 9, 'logout', '2026-03-09 01:57:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(476, 209, 'login', '2026-03-10 14:24:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(477, 9, 'login', '2026-03-10 14:25:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(478, 209, 'logout', '2026-03-10 14:26:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(479, 9, 'login', '2026-03-10 20:06:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(480, 1, 'login', '2026-03-10 20:17:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(481, 211, 'login', '2026-03-10 20:18:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(482, 9, 'logout', '2026-03-10 20:26:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(483, 9, 'login', '2026-03-10 20:26:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(484, 9, 'logout', '2026-03-10 20:27:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(485, 8, 'login', '2026-03-10 20:28:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(486, 211, 'logout', '2026-03-10 22:34:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(487, 222, 'login', '2026-03-10 22:41:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(488, 222, 'logout', '2026-03-10 22:43:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(489, 222, 'login', '2026-03-10 22:44:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(490, 9, 'login', '2026-03-10 22:45:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(491, 9, 'logout', '2026-03-10 23:06:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(492, 12, 'login', '2026-03-10 23:07:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(493, 12, 'login', '2026-03-11 07:30:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(494, 12, 'login', '2026-03-11 11:05:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(495, 12, 'logout', '2026-03-11 11:08:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(496, 1, 'login', '2026-03-11 11:10:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(497, 223, 'login', '2026-03-11 11:13:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(498, 12, 'login', '2026-03-11 11:15:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(499, 223, 'login', '2026-03-11 11:17:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(500, 12, 'logout', '2026-03-11 11:17:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(501, 222, 'login', '2026-03-11 11:18:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(502, 1, 'logout', '2026-03-11 11:56:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(503, 1, 'login', '2026-03-11 11:57:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(504, 222, 'logout', '2026-03-11 11:58:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(505, 12, 'login', '2026-03-11 11:59:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(506, 224, 'login', '2026-03-11 12:41:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(507, 1, 'logout', '2026-03-11 12:58:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(508, 12, 'logout', '2026-03-11 12:59:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(509, 1, 'login', '2026-03-11 12:59:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(510, 222, 'login', '2026-03-11 13:00:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(511, 1, 'logout', '2026-03-11 13:01:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(512, 12, 'login', '2026-03-11 13:02:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(513, 1, 'login', '2026-03-11 13:05:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(514, 222, 'logout', '2026-03-11 13:09:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(515, 223, 'login', '2026-03-11 13:10:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(516, 12, 'login', '2026-03-13 08:02:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(517, 12, 'logout', '2026-03-13 08:03:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(518, 222, 'login', '2026-03-13 08:07:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(519, 12, 'login', '2026-03-13 08:07:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(520, 12, 'logout', '2026-03-13 08:21:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(521, 12, 'login', '2026-03-13 08:22:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(522, 12, 'logout', '2026-03-13 08:29:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(523, 1, 'login', '2026-03-13 08:31:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(524, 1, 'logout', '2026-03-13 12:39:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(525, 12, 'login', '2026-03-13 12:40:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(526, 12, 'login', '2026-03-13 14:16:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(527, 12, 'logout', '2026-03-13 14:41:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(528, 1, 'login', '2026-03-13 14:41:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(529, 12, 'login', '2026-03-13 14:50:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(530, 12, 'login', '2026-03-13 19:55:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(531, 222, 'logout', '2026-03-13 20:59:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(532, 226, 'login', '2026-03-13 21:58:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(533, 226, 'logout', '2026-03-13 23:29:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(534, 223, 'login', '2026-03-13 23:29:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(535, 223, 'logout', '2026-03-14 00:21:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(536, 13, 'login', '2026-03-14 11:12:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(537, 13, 'login', '2026-03-14 13:11:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(538, 223, 'login', '2026-03-14 17:29:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(539, 223, 'logout', '2026-03-14 18:17:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(540, 12, 'login', '2026-03-14 19:41:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(541, 12, 'logout', '2026-03-14 22:02:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(542, 13, 'login', '2026-03-14 22:02:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(543, 223, 'login', '2026-03-15 09:22:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(544, 13, 'login', '2026-03-15 11:57:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(545, 13, 'login', '2026-03-15 21:24:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(546, 223, 'logout', '2026-03-15 22:33:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(547, 222, 'login', '2026-03-15 22:34:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'),
(548, 13, 'login', '2026-03-28 20:32:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(549, 13, 'logout', '2026-03-28 20:37:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(550, 223, 'login', '2026-03-28 20:38:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(551, 223, 'logout', '2026-03-28 20:41:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(552, 13, 'login', '2026-03-28 22:36:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(553, 13, 'logout', '2026-03-29 00:08:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(554, 13, 'login', '2026-03-29 00:09:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(555, 223, 'login', '2026-03-29 00:49:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(556, 13, 'logout', '2026-03-29 01:03:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(557, 223, 'logout', '2026-03-29 01:03:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(558, 223, 'login', '2026-03-29 01:03:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(559, 223, 'logout', '2026-03-29 01:04:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(560, 13, 'login', '2026-03-29 15:52:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(561, 13, 'login', '2026-03-29 15:56:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(562, 13, 'logout', '2026-03-29 15:57:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(563, 223, 'login', '2026-03-29 15:57:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(564, 13, 'login', '2026-03-29 21:09:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(565, 222, 'login', '2026-03-29 21:42:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(566, 13, 'logout', '2026-03-29 21:58:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(567, 1, 'login', '2026-03-29 21:58:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(568, 222, 'logout', '2026-03-29 22:00:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(569, 228, 'login', '2026-03-29 22:00:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(570, 1, 'logout', '2026-03-29 22:00:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(571, 13, 'login', '2026-03-29 22:01:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(572, 228, 'logout', '2026-03-29 22:29:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(573, 13, 'login', '2026-03-30 08:00:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(574, 222, 'login', '2026-04-01 15:16:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(575, 222, 'logout', '2026-04-01 18:09:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(576, 13, 'login', '2026-04-01 18:10:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(577, 13, 'logout', '2026-04-01 18:18:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(578, 1, 'login', '2026-04-01 18:19:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(579, 1, 'logout', '2026-04-01 18:21:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(580, 1, 'login', '2026-04-01 18:21:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36');
INSERT INTO `user_activity_log` (`id`, `user_id`, `action_type`, `action_timestamp`, `ip_address`, `user_agent`) VALUES
(581, 13, 'login', '2026-04-01 18:22:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(582, 13, 'login', '2026-04-01 20:49:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(583, 13, 'logout', '2026-04-01 22:03:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(584, 1, 'logout', '2026-04-01 22:45:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(585, 13, 'login', '2026-04-01 22:46:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(586, 13, 'login', '2026-04-02 06:29:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(587, 1, 'login', '2026-04-02 06:37:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(588, 229, 'login', '2026-04-02 06:39:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(589, 222, 'login', '2026-04-02 15:32:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(590, 13, 'login', '2026-04-02 15:48:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(591, 13, 'logout', '2026-04-02 20:29:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(592, 13, 'login', '2026-04-02 20:29:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(593, 222, 'logout', '2026-04-02 23:06:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(594, 222, 'login', '2026-04-02 23:08:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(595, 222, 'logout', '2026-04-02 23:27:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(596, 222, 'login', '2026-04-02 23:42:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(597, 13, 'login', '2026-04-02 23:48:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(598, 222, 'logout', '2026-04-02 23:57:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(599, 13, 'login', '2026-04-03 08:33:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(600, 13, 'logout', '2026-04-03 08:34:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(601, 13, 'login', '2026-04-03 08:35:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(602, 229, 'login', '2026-04-03 08:37:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(603, 1, 'login', '2026-04-03 08:39:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(604, 13, 'login', '2026-04-03 10:57:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(605, 13, 'login', '2026-04-03 17:11:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(606, 13, 'login', '2026-04-03 18:57:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(607, 229, 'logout', '2026-04-03 19:55:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(608, 229, 'login', '2026-04-03 19:55:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(609, 229, 'logout', '2026-04-03 20:01:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(610, 231, 'login', '2026-04-03 20:01:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(611, 231, 'logout', '2026-04-03 21:04:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(612, 232, 'login', '2026-04-03 21:04:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(613, 232, 'logout', '2026-04-03 21:13:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(614, 232, 'login', '2026-04-03 21:14:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(615, 232, 'logout', '2026-04-03 21:29:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(616, 233, 'login', '2026-04-03 21:29:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(617, 233, 'logout', '2026-04-03 21:42:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(618, 234, 'login', '2026-04-03 21:42:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(619, 234, 'logout', '2026-04-03 22:35:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(620, 232, 'login', '2026-04-03 22:35:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(621, 232, 'accept_announcement', '2026-04-03 23:38:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(622, 222, 'login', '2026-04-04 06:41:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(623, 12, 'login', '2026-04-04 06:41:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(624, 222, 'dismiss_announcement', '2026-04-04 07:08:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(625, 13, 'login', '2026-04-04 15:47:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(626, 13, 'login', '2026-04-04 18:10:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(627, 222, 'logout', '2026-04-04 18:15:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(628, 222, 'login', '2026-04-04 19:06:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(629, 13, 'login', '2026-04-04 23:24:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(630, 1, 'login', '2026-04-04 23:25:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(631, 1, 'login', '2026-04-04 23:53:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(632, 232, 'login', '2026-04-05 00:13:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(633, 232, 'view_announcement', '2026-04-05 00:27:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(634, 232, 'view_announcement', '2026-04-05 00:55:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(635, 232, 'accept_announcement', '2026-04-05 00:55:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(636, 232, 'logout', '2026-04-05 01:11:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(637, 13, 'login', '2026-04-05 01:29:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(638, 227, 'login', '2026-04-05 08:04:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(639, 227, 'view_announcement', '2026-04-05 08:05:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(640, 227, 'accept_announcement', '2026-04-05 08:05:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(641, 227, 'view_announcement', '2026-04-05 08:05:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(642, 12, 'login', '2026-04-05 08:06:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(643, 13, 'login', '2026-04-05 15:21:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(644, 13, 'login', '2026-04-05 20:55:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(645, 227, 'logout', '2026-04-05 21:11:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(646, 233, 'login', '2026-04-05 21:11:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(647, 13, 'login', '2026-04-06 11:27:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(648, 1, 'login', '2026-04-06 11:47:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(649, 13, 'logout', '2026-04-06 11:50:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(650, 222, 'login', '2026-04-06 11:51:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(651, 222, 'view_announcement', '2026-04-06 11:51:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(652, 222, 'accept_announcement', '2026-04-06 11:51:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(653, 222, 'view_announcement', '2026-04-06 11:53:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(654, 1, 'logout', '2026-04-06 13:37:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(655, 222, 'logout', '2026-04-06 13:46:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(656, 223, 'login', '2026-04-06 13:53:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(657, 13, 'login', '2026-04-06 13:54:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(658, 223, 'view_announcement', '2026-04-06 13:59:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(659, 223, 'view_announcement', '2026-04-06 14:00:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(660, 223, 'view_announcement', '2026-04-06 14:00:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(661, 223, 'view_announcement', '2026-04-06 14:02:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(662, 223, 'accept_announcement', '2026-04-06 14:02:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(663, 223, 'view_announcement', '2026-04-06 14:02:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(664, 223, 'view_announcement', '2026-04-06 14:02:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(665, 223, 'dismiss_announcement', '2026-04-06 14:02:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(666, 223, 'view_announcement', '2026-04-06 14:03:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(667, 223, 'view_announcement', '2026-04-06 14:05:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(668, 223, 'logout', '2026-04-06 14:08:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(669, 223, 'login', '2026-04-06 14:09:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(670, 223, 'logout', '2026-04-06 14:14:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(671, 223, 'login', '2026-04-06 14:18:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(672, 13, 'logout', '2026-04-06 14:23:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(673, 1, 'login', '2026-04-06 14:24:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(674, 1, 'logout', '2026-04-06 14:31:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(675, 1, 'login', '2026-04-06 14:32:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(676, 1, 'logout', '2026-04-06 14:35:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(677, 13, 'login', '2026-04-06 14:35:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(678, 13, 'login', '2026-04-06 19:17:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(679, 13, 'login', '2026-04-06 22:48:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(680, 13, 'login', '2026-04-07 12:22:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(681, 223, 'logout', '2026-04-07 12:50:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(682, 222, 'login', '2026-04-07 13:03:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(683, 222, 'logout', '2026-04-07 14:56:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(684, 223, 'login', '2026-04-07 14:56:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(685, 223, 'logout', '2026-04-07 14:56:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(686, 232, 'login', '2026-04-07 14:56:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(687, 1, 'login', '2026-04-07 15:07:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(688, 1, 'login', '2026-04-07 15:11:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(689, 232, 'logout', '2026-04-07 15:19:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(690, 223, 'login', '2026-04-07 15:20:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(691, 223, 'logout', '2026-04-07 15:21:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(692, 235, 'login', '2026-04-07 15:21:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(693, 235, 'view_announcement', '2026-04-07 15:22:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(694, 235, 'view_announcement', '2026-04-07 16:38:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(695, 235, 'view_announcement', '2026-04-07 16:38:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(696, 235, 'accept_announcement', '2026-04-07 16:38:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(697, 235, 'logout', '2026-04-07 16:40:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(698, 222, 'login', '2026-04-07 17:10:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(699, 222, 'logout', '2026-04-07 17:10:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(700, 13, 'login', '2026-04-07 21:20:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(701, 222, 'login', '2026-04-07 21:21:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(702, 222, 'logout', '2026-04-07 22:23:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(703, 223, 'login', '2026-04-07 22:23:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(704, 223, 'view_announcement', '2026-04-07 23:25:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(705, 223, 'view_announcement', '2026-04-07 23:26:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(706, 13, 'login', '2026-04-08 07:12:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(707, 223, 'logout', '2026-04-08 07:50:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(708, 235, 'login', '2026-04-08 07:51:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(709, 235, 'logout', '2026-04-08 08:06:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(710, 223, 'login', '2026-04-08 08:06:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(711, 223, 'logout', '2026-04-08 08:09:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(712, 227, 'login', '2026-04-08 08:09:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(713, 222, 'login', '2026-04-08 13:06:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(714, 13, 'login', '2026-04-08 13:10:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(715, 222, 'logout', '2026-04-08 13:11:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(716, 235, 'login', '2026-04-08 13:11:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(717, 13, 'login', '2026-04-08 15:49:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(718, 222, 'login', '2026-04-08 16:40:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(719, 222, 'logout', '2026-04-08 16:48:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(720, 223, 'login', '2026-04-08 16:48:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(721, 223, 'logout', '2026-04-08 17:29:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(722, 13, 'login', '2026-04-08 19:48:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(723, 222, 'login', '2026-04-08 22:05:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(724, 12, 'login', '2026-04-08 22:05:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(725, 222, 'logout', '2026-04-08 23:36:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(726, 12, 'login', '2026-04-09 07:36:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(727, 222, 'login', '2026-04-09 07:44:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(728, 222, 'view_announcement', '2026-04-09 07:52:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(729, 222, 'accept_announcement', '2026-04-09 07:53:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(730, 222, 'view_announcement', '2026-04-09 07:53:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(731, 222, 'view_announcement', '2026-04-09 07:53:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(732, 222, 'view_announcement', '2026-04-09 07:54:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(733, 12, 'logout', '2026-04-09 07:54:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(734, 222, 'logout', '2026-04-09 09:09:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(735, 13, 'login', '2026-04-09 09:11:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(736, 12, 'login', '2026-04-09 13:52:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(737, 13, 'login', '2026-04-09 15:50:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(738, 222, 'login', '2026-04-09 17:04:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(739, 13, 'login', '2026-04-09 17:04:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(740, 12, 'login', '2026-04-09 21:07:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(741, 222, 'logout', '2026-04-09 21:28:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(742, 222, 'login', '2026-04-09 21:32:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(743, 222, 'view_announcement', '2026-04-09 21:32:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(744, 12, 'logout', '2026-04-09 21:45:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(745, 1, 'login', '2026-04-09 21:45:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(746, 222, 'login', '2026-04-10 06:43:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(747, 222, 'view_announcement', '2026-04-10 06:50:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(748, 222, 'view_announcement', '2026-04-10 06:52:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(749, 222, 'logout', '2026-04-10 07:21:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(750, 222, 'login', '2026-04-10 08:46:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(751, 13, 'login', '2026-04-10 08:49:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(752, 222, 'logout', '2026-04-10 09:04:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(753, 13, 'logout', '2026-04-10 09:06:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(754, 1, 'login', '2026-04-10 09:06:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(755, 222, 'login', '2026-04-11 18:39:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(756, 222, 'logout', '2026-04-11 19:08:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(757, 222, 'login', '2026-04-11 22:46:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(758, 222, 'logout', '2026-04-11 22:55:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(759, 222, 'login', '2026-04-11 22:56:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(760, 222, 'logout', '2026-04-11 22:56:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(761, 222, 'login', '2026-04-11 22:56:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(762, 222, 'logout', '2026-04-11 22:56:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(763, 222, 'login', '2026-04-11 22:57:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(764, 222, 'logout', '2026-04-11 22:57:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(765, 222, 'login', '2026-04-11 23:34:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(766, 222, 'logout', '2026-04-11 23:34:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(767, 13, 'login', '2026-04-12 00:27:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(768, 1, 'login', '2026-04-12 00:35:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(769, 222, 'login', '2026-04-12 00:35:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(770, 222, 'view_announcement', '2026-04-12 00:35:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(771, 222, 'view_announcement', '2026-04-12 00:39:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(772, 222, 'view_announcement', '2026-04-12 00:39:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(773, 13, 'logout', '2026-04-12 00:49:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(774, 15, 'login', '2026-04-12 00:49:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(775, 15, 'logout', '2026-04-12 00:52:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(776, 16, 'login', '2026-04-12 00:52:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(777, 16, 'logout', '2026-04-12 00:53:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(778, 14, 'login', '2026-04-12 00:54:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(779, 14, 'logout', '2026-04-12 01:00:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(780, 15, 'login', '2026-04-12 01:00:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(781, 15, 'logout', '2026-04-12 01:07:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(782, 14, 'login', '2026-04-12 01:18:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(783, 14, 'logout', '2026-04-12 01:19:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(784, 15, 'login', '2026-04-12 01:19:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(785, 15, 'logout', '2026-04-12 01:21:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(786, 16, 'login', '2026-04-12 01:21:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(787, 16, 'logout', '2026-04-12 01:30:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(788, 15, 'login', '2026-04-12 01:30:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(789, 15, 'logout', '2026-04-12 01:31:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(790, 16, 'login', '2026-04-12 01:31:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(791, 16, 'logout', '2026-04-12 02:36:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(792, 15, 'login', '2026-04-12 02:36:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(793, 15, 'logout', '2026-04-12 02:38:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(794, 16, 'login', '2026-04-12 02:38:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(795, 16, 'logout', '2026-04-12 02:43:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(796, 15, 'login', '2026-04-12 02:43:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(797, 15, 'logout', '2026-04-12 02:44:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(798, 16, 'login', '2026-04-12 02:44:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(799, 16, 'logout', '2026-04-12 02:58:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(800, 14, 'login', '2026-04-12 02:58:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(801, 14, 'logout', '2026-04-12 03:25:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(802, 15, 'login', '2026-04-12 03:25:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(803, 222, 'logout', '2026-04-12 03:28:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(804, 15, 'logout', '2026-04-12 03:37:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(805, 13, 'login', '2026-04-12 03:38:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(806, 13, 'logout', '2026-04-12 03:38:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(807, 222, 'login', '2026-04-12 03:41:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(808, 222, 'logout', '2026-04-12 03:41:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(809, 222, 'login', '2026-04-12 03:41:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(810, 222, 'logout', '2026-04-12 03:41:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(811, 222, 'login', '2026-04-12 03:41:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(812, 222, 'logout', '2026-04-12 03:41:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(813, 222, 'login', '2026-04-12 03:42:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(814, 222, 'logout', '2026-04-12 03:42:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(815, 222, 'login', '2026-04-12 03:43:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(816, 222, 'logout', '2026-04-12 03:43:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(817, 13, 'login', '2026-04-12 09:03:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(818, 222, 'login', '2026-04-14 12:47:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(819, 13, 'login', '2026-04-14 12:47:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(820, 1, 'login', '2026-04-14 12:49:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(821, 222, 'logout', '2026-04-14 12:51:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(822, 222, 'login', '2026-04-14 14:08:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(823, 222, 'logout', '2026-04-14 14:15:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(824, 13, 'logout', '2026-04-14 14:41:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(825, 15, 'login', '2026-04-14 14:41:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(826, 222, 'login', '2026-04-14 14:42:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(827, 222, 'view_announcement', '2026-04-14 14:42:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(828, 222, 'logout', '2026-04-14 14:42:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(829, 223, 'login', '2026-04-14 14:47:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(830, 223, 'logout', '2026-04-14 14:47:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(831, 222, 'login', '2026-04-14 14:47:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(832, 222, 'logout', '2026-04-14 15:12:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(833, 222, 'login', '2026-04-14 15:27:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(834, 222, 'logout', '2026-04-14 15:28:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(835, 222, 'login', '2026-04-14 16:34:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(836, 222, 'logout', '2026-04-14 16:45:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(837, 222, 'login', '2026-04-14 16:46:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(838, 222, 'view_announcement', '2026-04-14 16:47:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(839, 222, 'logout', '2026-04-14 16:48:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0'),
(840, 13, 'login', '2026-04-17 12:25:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(841, 13, 'logout', '2026-04-17 12:26:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(842, 13, 'login', '2026-04-17 12:28:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(843, 13, 'logout', '2026-04-17 12:33:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(844, 222, 'login', '2026-04-17 12:34:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(845, 13, 'login', '2026-04-17 12:36:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(846, 222, 'logout', '2026-04-17 12:37:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(847, 222, 'login', '2026-04-17 12:37:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(848, 13, 'login', '2026-04-17 14:18:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(849, 13, 'logout', '2026-04-17 14:21:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(850, 15, 'login', '2026-04-17 14:22:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(851, 222, 'logout', '2026-04-17 14:26:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(852, 222, 'login', '2026-04-19 09:22:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(853, 13, 'login', '2026-04-19 09:24:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(854, 13, 'login', '2026-04-19 10:33:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(855, 222, 'logout', '2026-04-19 10:40:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(856, 13, 'login', '2026-04-19 13:25:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(857, 13, 'login', '2026-04-19 21:36:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(858, 13, 'login', '2026-04-19 23:27:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(859, 13, 'logout', '2026-04-20 02:54:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(860, 15, 'login', '2026-04-20 02:54:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(861, 1, 'login', '2026-04-20 02:57:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(862, 15, 'logout', '2026-04-20 02:59:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(863, 16, 'login', '2026-04-20 03:00:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(864, 16, 'login', '2026-04-20 03:13:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(865, 16, 'login', '2026-04-20 11:05:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0');
INSERT INTO `user_activity_log` (`id`, `user_id`, `action_type`, `action_timestamp`, `ip_address`, `user_agent`) VALUES
(866, 16, 'logout', '2026-04-20 12:38:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(867, 222, 'login', '2026-04-20 12:38:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(868, 1, 'logout', '2026-04-20 22:21:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(869, 13, 'login', '2026-04-20 22:22:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(870, 1, 'login', '2026-04-20 22:22:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(871, 13, 'login', '2026-04-21 08:36:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(872, 15, 'login', '2026-04-21 10:51:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(873, 15, 'logout', '2026-04-21 10:51:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(874, 16, 'login', '2026-04-21 10:52:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(875, 16, 'logout', '2026-04-21 10:52:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(876, 15, 'login', '2026-04-21 10:52:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(877, 15, 'login', '2026-04-21 13:28:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(878, 1, 'login', '2026-04-21 13:30:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(879, 222, 'logout', '2026-04-21 13:35:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0'),
(880, 222, 'login', '2026-04-21 13:36:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0');

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
(190, 222, 348, 'accepted', '2026-04-02 13:15:54', '2026-04-02 13:15:54'),
(191, 229, 355, 'accepted', '2026-04-03 11:55:40', '2026-04-03 11:55:40'),
(192, 229, 353, 'accepted', '2026-04-03 11:56:54', '2026-04-03 11:56:54'),
(193, 229, 352, 'accepted', '2026-04-03 11:56:57', '2026-04-03 11:56:57'),
(194, 229, 356, 'accepted', '2026-04-03 11:57:06', '2026-04-03 11:57:06'),
(195, 232, 352, 'accepted', '2026-04-03 14:40:33', '2026-04-03 14:40:33'),
(196, 232, 359, 'accepted', '2026-04-03 15:38:53', '2026-04-03 15:38:53'),
(197, 222, 352, 'dismissed', '2026-04-03 23:08:51', '2026-04-03 23:08:51'),
(198, 232, 362, 'accepted', '2026-04-04 16:55:14', '2026-04-04 16:55:14'),
(199, 227, 352, 'accepted', '2026-04-05 00:05:07', '2026-04-05 00:05:07'),
(200, 222, 364, 'accepted', '2026-04-06 03:51:41', '2026-04-06 03:51:41'),
(201, 223, 366, 'accepted', '2026-04-06 06:02:17', '2026-04-06 06:02:17'),
(202, 223, 352, 'dismissed', '2026-04-06 06:02:44', '2026-04-06 06:02:44'),
(203, 235, 368, 'accepted', '2026-04-07 08:38:44', '2026-04-07 08:38:44'),
(204, 222, 372, 'accepted', '2026-04-08 23:53:21', '2026-04-08 23:53:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_appointments`
--

CREATE TABLE `user_appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `status` enum('pending','approved','completed','cancelled','rejected','rescheduled','missed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rescheduled_from` int(11) DEFAULT NULL,
  `rescheduled_at` datetime DEFAULT NULL,
  `rescheduled_count` int(11) DEFAULT 0,
  `invoice_number` varchar(50) DEFAULT NULL,
  `priority_number` varchar(50) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `invoice_generated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by_user` tinyint(1) DEFAULT 0,
  `appointment_ticket` longtext DEFAULT NULL
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
-- Indexes for table `announcement_analytics_archive`
--
ALTER TABLE `announcement_analytics_archive`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_original_announcement_id` (`original_announcement_id`),
  ADD KEY `idx_archive_post_date` (`post_date`),
  ADD KEY `idx_archive_audience_type` (`audience_type`);

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
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_announcement_view` (`announcement_id`,`viewer_token`),
  ADD KEY `idx_announcement_id` (`announcement_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barangay_sitio_mapping`
--
ALTER TABLE `barangay_sitio_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barangay_name` (`barangay_name`);

--
-- Indexes for table `child_health_records`
--
ALTER TABLE `child_health_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `city_health_report_logs`
--
ALTER TABLE `city_health_report_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_consultation_date` (`consultation_date`);

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
-- Indexes for table `health_campaigns`
--
ALTER TABLE `health_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `resident_activity_log`
--
ALTER TABLE `resident_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_linked_by` (`linked_by`),
  ADD KEY `patient_record_id` (`patient_record_id`);

--
-- Indexes for table `sitio1_activity_log`
--
ALTER TABLE `sitio1_activity_log`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `staff_role_permissions`
--
ALTER TABLE `staff_role_permissions`
  ADD PRIMARY KEY (`position`);

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
-- AUTO_INCREMENT for table `announcement_analytics_archive`
--
ALTER TABLE `announcement_analytics_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_messages`
--
ALTER TABLE `announcement_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_targets`
--
ALTER TABLE `announcement_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barangay_sitio_mapping`
--
ALTER TABLE `barangay_sitio_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `child_health_records`
--
ALTER TABLE `child_health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `city_health_report_logs`
--
ALTER TABLE `city_health_report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `deleted_patients`
--
ALTER TABLE `deleted_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `existing_info_patients`
--
ALTER TABLE `existing_info_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `health_campaigns`
--
ALTER TABLE `health_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_logs`
--
ALTER TABLE `report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resident_activity_log`
--
ALTER TABLE `resident_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_activity_log`
--
ALTER TABLE `sitio1_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `sitio1_announcements`
--
ALTER TABLE `sitio1_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=380;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=844;

--
-- AUTO_INCREMENT for table `sitio1_staff`
--
ALTER TABLE `sitio1_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sitio1_users`
--
ALTER TABLE `sitio1_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1283;

--
-- AUTO_INCREMENT for table `staff_documents`
--
ALTER TABLE `staff_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=881;

--
-- AUTO_INCREMENT for table `user_announcements`
--
ALTER TABLE `user_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

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
-- Constraints for table `resident_activity_log`
--
ALTER TABLE `resident_activity_log`
  ADD CONSTRAINT `resident_activity_log_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `sitio1_users` (`id`) ON DELETE CASCADE;

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
