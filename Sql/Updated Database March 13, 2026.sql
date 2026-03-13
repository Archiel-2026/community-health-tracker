-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 03:43 PM
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
(198, 289, 222, '2026-03-13 00:09:48'),
(199, 291, 222, '2026-03-13 00:17:36'),
(200, 294, 222, '2026-03-13 06:26:27');

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
(97, 811, 'test1test1test1', '2026-03-10', '2026-03-28', 'Dr. test1', 12, '2026-03-10 15:30:38', NULL),
(98, 811, 'test2test2test2', '2026-03-10', '2026-04-03', 'Dr. test2', 12, '2026-03-10 15:31:02', NULL),
(99, 811, 'test3test3test3', '2026-03-10', '2026-03-25', 'Dr. test3', 12, '2026-03-10 15:32:33', NULL),
(100, 811, 'test3test3test3', '2026-03-10', '2026-03-11', 'Dr. test4', 12, '2026-03-10 15:32:48', NULL),
(101, 811, 'testr2testr2testr2', '2026-03-10', '2026-03-19', 'Dr. test5', 12, '2026-03-10 15:55:40', NULL),
(102, 814, 'Testing1Testing1Testing1Testing1', '2026-03-10', '2026-03-20', 'Dr. Testing1', 12, '2026-03-10 16:04:30', NULL),
(103, 814, 'Testing2Testing2Testing2Testing2', '2026-03-10', '2026-03-12', 'Dr. Testing2', 12, '2026-03-10 16:04:49', NULL),
(104, 816, 'hello the patient has rabies', '2026-03-11', '2026-03-24', 'Dr. wakwak', 12, '2026-03-11 04:34:01', NULL),
(105, 816, 'salve need to take his medication on time', '2026-03-11', '2026-03-26', 'Dr. leandro labos', 12, '2026-03-11 04:34:41', NULL),
(106, 816, 'arda needs to see a brain doctor', '2026-03-11', '2026-03-28', 'Dr. miras', 12, '2026-03-11 04:35:21', NULL),
(107, 815, 'SInusitis', '2026-03-11', '2026-03-12', 'Dr. Doc Willy Ong', 12, '2026-03-11 05:11:07', NULL);

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
(133, 813, 'Archiel Cabanag', '2002-05-26', 23, 'Male', 'Toong Cebu City', '09206001470', '2026-02-25', 9, NULL, '2026-02-26 15:47:41', 9),
(134, 315, 'Roberto Mendoza', '1995-05-25', 30, 'Male', '321 Quezon Blvd.', '09567890123', '2026-02-18', 9, NULL, '2026-02-26 15:47:44', 9),
(135, 316, 'Isabel Cruz', '1988-12-12', 37, 'Female', '654 MacArthur Hwy', '09678901234', '2026-02-01', 9, NULL, '2026-02-26 15:47:46', 9),
(139, 317, 'Antonio Fernandez', '1975-04-03', 50, 'Male', '987 Luna St.', '09789012345', '2026-01-15', 9, NULL, '2026-02-27 23:27:53', 9),
(141, 318, 'Teresa Villanueva', '1992-08-08', 33, 'Female', '147 Osmena St.', '09890123456', '2026-02-12', 9, NULL, '2026-02-27 23:28:00', 9),
(142, 319, 'Ramon Santos', '1983-06-21', 42, 'Male', '258 Panganiban St.', '09901234567', '2026-01-20', 9, NULL, '2026-02-27 23:28:03', 9),
(143, 320, 'Carmen Hernandez', '1987-10-05', 38, 'Female', '369 Roxas Blvd.', '09012345678', '2026-02-08', 9, NULL, '2026-02-27 23:28:07', 9);

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
(291, 321, 'Male', 166.00, 73.00, 'B+', 'Iodine', 'Post-heart attack 2020', 'Aspirin, Metoprolol', 'Strong heart disease history', NULL, 36.30, '140/90', 'Cardiac rehab', 'Heart Disease', NULL),
(292, 322, 'Female', 163.00, 57.00, 'O+', 'Cats', 'Allergic rhinitis', 'Cetirizine PRN', 'Allergies in family', NULL, 36.70, '110/68', 'Complete', 'Allergies', NULL),
(293, 323, 'Male', 171.00, 76.00, 'AB+', 'None', 'Healthy', 'None', 'No issues', NULL, 36.50, '120/78', 'Up to date', 'None', NULL),
(294, 324, 'Female', 159.00, 63.00, 'A-', 'None', 'Hypothyroidism', 'Levothyroxine', 'Mother thyroid issues', NULL, 36.60, '122/75', 'Complete', 'Thyroid Disorder', NULL),
(295, 325, 'Male', 167.00, 82.00, 'B-', 'Alcohol', 'Gout', 'Allopurinol', 'Father had gout', NULL, 36.80, '130/84', 'Up to date', 'Gout', NULL),
(296, 326, 'Male', 178.00, 70.00, 'O-', 'None', 'No issues', 'None', 'Healthy family', NULL, 36.40, '115/70', 'Complete', 'None', NULL),
(297, 327, 'Female', 156.00, 65.00, 'A+', 'Sulfa', 'Type 2 diabetes', 'Metformin', 'Both parents diabetic', NULL, 36.70, '132/86', 'Diabetes education', 'Diabetes', NULL),
(298, 328, 'Male', 173.00, 79.00, 'AB-', 'None', 'Hypertension', 'Amlodipine', 'Father hypertensive', NULL, 36.50, '138/88', 'Up to date', 'Hypertension', NULL),
(299, 329, 'Female', 161.00, 59.00, 'B+', 'Dust mites', 'Mild asthma', 'Fluticasone inhaler', 'Sister asthmatic', NULL, 36.60, '118/74', 'Asthma action plan', 'Asthma', NULL),
(300, 330, 'Male', 164.00, 68.00, 'O+', 'NSAIDs', 'Osteoarthritis', 'Acetaminophen', 'Mother had arthritis', NULL, 36.80, '125/80', 'Complete', 'Arthritis', NULL),
(301, 331, 'Female', 166.00, 61.00, 'A-', 'None', 'Healthy', 'None', 'No history', NULL, 36.40, '112/71', 'Complete', 'None', NULL),
(302, 332, 'Male', 176.00, 74.00, 'AB+', 'Chocolate', 'Chronic headaches', 'Ibuprofen PRN', 'Father had migraines', NULL, 36.70, '118/76', 'Up to date', 'Migraine', NULL),
(303, 333, 'Female', 157.00, 66.00, 'B-', 'None', 'High cholesterol', 'Rosuvastatin', 'Mother high cholesterol', NULL, 36.50, '124/78', 'Complete', 'Hyperlipidemia', NULL),
(304, 334, 'Male', 169.00, 71.00, 'O-', 'Latex', 'None', 'None', 'No conditions', NULL, 36.60, '116/72', 'Complete', 'None', NULL),
(305, 335, 'Female', 162.00, 53.00, 'A+', 'Penicillin', 'Mild anemia', 'Iron supplements', 'Mother anemic', NULL, 36.90, '114/73', 'Complete', 'Anemia', NULL),
(306, 336, 'Male', 165.00, 85.00, 'B+', 'None', 'Diabetes with neuropathy', 'Metformin, Gabapentin', 'Family history diabetes', NULL, 36.30, '142/92', 'Diabetic care plan', 'Diabetes', NULL),
(307, 337, 'Female', 160.00, 58.00, 'AB-', 'None', 'Hyperthyroidism', 'Methimazole', 'Aunt thyroid issues', NULL, 36.70, '128/82', 'Complete', 'Thyroid Disorder', NULL),
(308, 338, 'Male', 177.00, 73.00, 'O+', 'None', 'Healthy', 'None', 'No issues', NULL, 36.50, '117/74', 'Complete', 'None', NULL),
(309, 339, 'Male', 168.00, 77.00, 'A-', 'Codeine', 'High blood pressure', 'Hydrochlorothiazide', 'Both parents hypertensive', NULL, 36.60, '136/88', 'Up to date', 'Hypertension', NULL),
(310, 340, 'Female', 159.00, 56.00, 'B-', 'Multiple', 'Allergies', 'Antihistamines', 'Allergic family', NULL, 36.80, '115/72', 'Allergy shots', 'Allergies', NULL),
(311, 341, 'Male', 170.00, 81.00, 'AB+', 'None', 'Heart bypass 2021', 'Multiple cardiac meds', 'Strong heart history', NULL, 36.40, '135/85', 'Cardiac rehab', 'Heart Disease', NULL),
(312, 342, 'Female', 164.00, 59.00, 'O-', 'None', 'No known conditions', 'None', 'No family history', NULL, 36.70, '113/70', 'Complete', 'None', NULL),
(313, 343, 'Male', 166.00, 84.00, 'A+', 'Seafood', 'Gout attacks', 'Colchicine PRN', 'Father had gout', NULL, 36.50, '129/83', 'Complete', 'Gout', NULL),
(314, 344, 'Female', 158.00, 64.00, 'B+', 'Dust', 'Moderate asthma', 'Advair diskus', 'Sister asthmatic', NULL, 36.60, '121/76', 'Asthma plan', 'Asthma', NULL),
(315, 345, 'Male', 172.00, 78.00, 'AB-', 'None', 'Cholesterol issues', 'Simvastatin', 'Father high cholesterol', NULL, 36.70, '126/80', 'Complete', 'Hyperlipidemia', NULL),
(316, 346, 'Female', 163.00, 55.00, 'O+', 'None', 'Frequent migraines', 'Rizatriptan', 'Mother migraines', NULL, 36.40, '111/69', 'Complete', 'Migraine', NULL),
(317, 347, 'Male', 169.00, 79.00, 'A-', 'Penicillin', 'Diabetes Type 2', 'Glipizide', 'Both parents diabetic', NULL, 36.80, '134/86', 'Diabetes care', 'Diabetes', NULL),
(318, 348, 'Female', 161.00, 60.00, 'B-', 'None', 'Healthy', 'None', 'No issues', NULL, 36.50, '116/73', 'Complete', 'None', NULL),
(319, 349, 'Male', 167.00, 73.00, 'AB+', 'None', 'Joint pain', 'Celecoxib', 'Mother arthritis', NULL, 36.60, '127/81', 'Complete', 'Arthritis', NULL),
(320, 350, 'Female', 160.00, 51.00, 'O-', 'None', 'Low iron', 'Iron supplements', 'Sister anemic', NULL, 36.90, '114/71', 'Complete', 'Anemia', NULL),
(321, 351, 'Male', 171.00, 82.00, 'A+', 'Sulfa', 'High BP', 'Lisinopril', 'Father hypertensive', NULL, 36.70, '139/89', 'Up to date', 'Hypertension', NULL),
(322, 352, 'Female', 165.00, 62.00, 'B+', 'Pollen', 'No issues', 'None', 'No history', NULL, 36.50, '112/70', 'Complete', 'None', NULL),
(323, 353, 'Male', 173.00, 76.00, 'AB-', 'Latex', 'Skin allergies', 'Topical steroids', 'Allergic family', NULL, 36.60, '119/75', 'Complete', 'Allergies', NULL),
(324, 354, 'Female', 162.00, 57.00, 'O+', 'None', 'Thyroid condition', 'Levothyroxine', 'Mother thyroid issues', NULL, 36.80, '123/77', 'Complete', 'Thyroid Disorder', NULL),
(325, 355, 'Male', 168.00, 80.00, 'A-', 'Alcohol', 'Gout', 'Febuxostat', 'Uncle had gout', NULL, 36.40, '131/84', 'Complete', 'Gout', NULL),
(326, 356, 'Female', 164.00, 58.00, 'B-', 'None', 'Healthy', 'None', 'No issues', NULL, 36.70, '115/72', 'Complete', 'None', NULL),
(327, 357, 'Male', 175.00, 77.00, 'AB+', 'Dust', 'Mild asthma', 'Albuterol PRN', 'Father asthmatic', NULL, 36.50, '120/76', 'Asthma plan', 'Asthma', NULL),
(328, 358, 'Female', 159.00, 63.00, 'O-', 'None', 'Headaches', 'Naproxen PRN', 'Mother migraines', NULL, 36.60, '117/73', 'Complete', 'Migraine', NULL),
(329, 359, 'Male', 166.00, 74.00, 'A+', 'None', 'Heart condition', 'Multiple cardiac meds', 'Family heart disease', NULL, 36.30, '137/87', 'Cardiac care', 'Heart Disease', NULL),
(330, 360, 'Female', 160.00, 66.00, 'B+', 'Penicillin', 'Diabetes', 'Metformin', 'Both parents diabetic', NULL, 36.80, '133/85', 'Diabetes education', 'Diabetes', NULL),
(337, 811, 'Male', 45.00, 45.00, 'A+', 'Quality Testing', 'Quality Testing', 'Quality Testingiiiiiiiiiiiiiiiii', 'Quality Testing', '2026-03-11 05:08:22', 45.00, '120/80', 'Quality Testing', 'Quality Testing', NULL),
(338, 814, 'Male', 45.00, 55.00, 'A+', 'Hello Everyone this is only a testing to test the final testing', 'Hello Everyone this is only a testing to test the final testing', 'Hello Everyone this is only a testing to test the final testing', 'Perfect Test', '2026-03-02 17:01:31', 45.00, '120/80', 'Hello Everyone this is only a testing to test the final testing', 'Hello Everyone this is only a testing to test the final testing', NULL),
(339, 815, 'Male', 57.00, 67.00, 'A+', 'shrimp', 'fever', 'fever', 'fever', '2026-03-13 06:29:28', 55.00, '120/80', 'ASTRA', 'fever', NULL),
(340, 816, 'Female', 152.00, 54.00, 'AB+', 'none', 'test', 'amlodipine', 'test', '2026-03-11 04:46:18', 36.00, '120/80', 'test', 'testtest', NULL);

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
(86, NULL, 'resident_deleted', 'Deleted resident: Creshiel Manloloyo and all associated patient records', '::1', '2026-03-13 22:40:22');

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
(289, 12, 'Announcement for Specific User', 'Announcement for Specific UserAnnouncement for Specific User', 'normal', '', '2026-03-17', '2026-03-13 00:09:48', NULL, 'active', 'specific', 'basic', NULL),
(290, 12, 'Announcement for All User', 'Announcement for All UserAnnouncement for All UserAnnouncement for All User', 'medium', '', '2026-03-13', '2026-03-13 00:10:41', NULL, 'active', 'public', 'basic', NULL),
(291, 12, 'Announcement to Specific User', 'Announcement to Specific UserAnnouncement to Specific User', 'high', 'lab_result', '2026-03-14', '2026-03-13 00:17:36', NULL, 'active', 'specific', 'basic', NULL),
(292, 12, 'asdsa', 'asdasd', 'normal', '', '2026-03-14', '2026-03-13 06:25:48', NULL, 'active', 'landing_page', 'basic', NULL),
(293, 12, 'asd', 'asd', 'normal', '', '2026-03-14', '2026-03-13 06:25:57', NULL, 'active', 'public', 'basic', NULL),
(294, 12, 'faddfas', 'asdas', 'medium', '', '2026-03-14', '2026-03-13 06:26:27', NULL, 'active', 'specific', 'basic', NULL);

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
  `immunization_status` varchar(50) DEFAULT NULL,
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

INSERT INTO `sitio1_patients` (`id`, `user_id`, `phic_no`, `bhw_assigned`, `family_no`, `fourps_member`, `full_name`, `date_of_birth`, `age`, `address`, `sitio`, `disease`, `immunization_status`, `contact`, `last_checkup`, `medical_history`, `added_by`, `created_at`, `deleted_at`, `restored_at`, `gender`, `updated_at`, `consultation_type`, `civil_status`, `occupation`, `consent_given`, `consent_date`, `patient_record_uid`) VALUES
(321, NULL, '112233445566', 'Nestor Javier', 'FAM011', 'Yes', 'Fernando Lopez', '1972-01-17', 54, '741 Del Pilar St.', 'San Roque', 'Heart Disease', NULL, '09112233445', '2026-01-10', 'Post-heart attack 2020', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Retired', 1, '2026-02-23 08:09:33', 'PAT-20260305-FER-3255'),
(322, NULL, '223344556677', 'Virginia Cruz', 'FAM012', 'No', 'Luzviminda Flores', '1991-06-30', 34, '852 P. Burgos St.', 'City Central', 'Allergic Rhinitis', NULL, '09223344556', '2026-02-14', 'Seasonal allergies', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'Nurse', 1, '2026-02-23 08:09:33', 'PAT-20260305-LUZ-5738'),
(323, NULL, '334455667788', 'Rogelio Dizon', 'FAM013', 'Yes', 'Rolando Castro', '1980-12-02', 45, '963 M.H. del Pilar', 'Sta. Cruz', 'None', NULL, '09334455667', '2026-01-25', 'Healthy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Mechanic', 1, '2026-02-23 08:09:33', 'PAT-20260305-ROL-4031'),
(324, NULL, '445566778899', 'Corazon Aquino', 'FAM014', 'No', 'Evelyn Garcia', '1984-09-14', 41, '159 Mabini Extension', 'Nangka', 'Thyroid Disorder', NULL, '09445566778', '2026-02-03', 'Hypothyroidism', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Bank Teller', 1, '2026-02-23 08:09:33', 'PAT-20260305-EVE-2916'),
(325, NULL, '556677889900', 'Benigno Santiago', 'FAM015', 'Yes', 'Gregorio Velasco', '1977-07-07', 48, '753 Taft Ave.', 'Zapatera', 'Gout', NULL, '09556677889', '2026-01-18', 'Gout attacks', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Tricycle Driver', 1, '2026-02-23 08:09:33', 'PAT-20260305-GRE-8315'),
(326, NULL, '667788990011', 'Salvador Laurel', 'FAM016', 'No', 'Manuel Torres', '1993-03-28', 32, '951 Rizal Street', 'San Roque', 'None', NULL, '09667788990', '2026-02-17', 'No medical issues', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'online', 'Single', 'Barista', 1, '2026-02-23 08:09:33', 'PAT-20260305-MAN-7045'),
(327, NULL, '778899001122', 'Jovita Salonga', 'FAM017', 'Yes', 'Gloria Araneta', '1981-11-11', 44, '357 Legaspi St.', 'City Central', 'Diabetes', NULL, '09778899001', '2026-01-05', 'Type 2 diabetes', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Divorced', 'Store Owner', 1, '2026-02-23 08:09:33', 'PAT-20260305-GLO-9836'),
(328, NULL, '889900112233', 'Rene Espina', 'FAM018', 'No', 'Rodrigo Duterte', '1979-05-19', 46, '624 Magallanes St.', 'Sta. Cruz', 'Hypertension', NULL, '09889900112', '2026-02-09', 'High BP since 2018', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Government Employee', 1, '2026-02-23 08:09:33', 'PAT-20260310-ROD-9327'),
(329, NULL, '990011223344', 'Leticia Ramos', 'FAM019', 'Yes', 'Nenita Pimentel', '1986-08-23', 39, '813 Burgos Ave.', 'Nangka', 'Asthma', NULL, '09990011223', '2026-01-30', 'Mild asthma', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Teacher', 1, '2026-02-23 08:09:33', 'PAT-20260130-NEN-9900'),
(330, NULL, '101112131415', 'Emmanuel Pelaez', 'FAM020', 'No', 'Victor Recto', '1974-02-14', 52, '246 Kalayaan St.', 'Zapatera', 'Arthritis', NULL, '09001122334', '2026-02-20', 'Osteoarthritis', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Farmer', 1, '2026-02-23 08:09:33', 'PAT-20260220-VIC-1011'),
(331, NULL, '121314151617', 'Helena Benitez', 'FAM021', 'Yes', 'Rosario Cruz', '1994-04-09', 31, '528 Katipunan St.', 'San Roque', 'None', NULL, '09122334455', '2026-02-16', 'Healthy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'Graphic Designer', 1, '2026-02-23 08:09:33', 'PAT-20260216-ROS-1213'),
(332, NULL, '131415161718', 'Raul Manglapus', 'FAM022', 'No', 'Alfredo Lim', '1989-10-31', 36, '735 Lacson St.', 'City Central', 'Migraine', NULL, '09233445566', '2026-01-22', 'Chronic headaches', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Single', 'IT Specialist', 1, '2026-02-23 08:09:33', 'PAT-20260122-ALF-1314'),
(333, NULL, '141516171819', 'Eva Estrada', 'FAM023', 'Yes', 'Lourdes Tan', '1976-12-25', 49, '942 Arnaiz St.', 'Sta. Cruz', 'High Cholesterol', NULL, '09344556677', '2026-02-07', 'High cholesterol', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Accountant', 1, '2026-02-23 08:09:33', 'PAT-20260207-LOU-1415'),
(334, NULL, '151617181920', 'Gaudencio Rosales', 'FAM024', 'No', 'Ricardo Cuevas', '1982-06-06', 43, '159 P. Gomez St.', 'Nangka', 'None', NULL, '09455667788', '2026-01-12', 'No conditions', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'online', 'Married', 'Electrician', 1, '2026-02-23 08:09:33', 'PAT-20260112-RIC-1516'),
(335, NULL, '161718192021', 'Teresita Diaz', 'FAM025', 'Yes', 'Fe Mercado', '1987-09-19', 38, '268 Vito Cruz St.', 'Zapatera', 'Anemia', NULL, '09566778899', '2026-02-11', 'Mild anemia', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Single', 'Call Center Agent', 1, '2026-02-23 08:09:33', 'PAT-20260211-FE-1617'),
(336, NULL, '171819202122', 'Luis Villafuerte', 'FAM026', 'No', 'Rogelio Roxas', '1973-03-03', 52, '377 Escolta St.', 'San Roque', 'Diabetes', NULL, '09677889900', '2026-01-08', 'Diabetes with neuropathy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Janitor', 1, '2026-02-23 08:09:33', 'PAT-20260108-ROG-1718'),
(337, NULL, '181920212223', 'Consuelo Madrigal', 'FAM027', 'Yes', 'Milagros Reyes', '1984-07-15', 41, '486 A. Flores St.', 'City Central', 'Thyroid Disorder', NULL, '09788990011', '2026-02-13', 'Hyperthyroidism', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Dental Assistant', 1, '2026-02-23 08:09:33', 'PAT-20260213-MIL-1819'),
(338, NULL, '192021222324', 'Ramon Mitra', 'FAM028', 'No', 'Romeo David', '1990-01-01', 36, '595 Padre Faura St.', 'Sta. Cruz', 'None', NULL, '09899001122', '2026-02-19', 'Healthy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'online', 'Single', 'Photographer', 1, '2026-02-23 08:09:33', 'PAT-20260219-ROM-1920'),
(339, NULL, '202122232425', 'Hilario Davide', 'FAM029', 'Yes', 'Francisco Tatad', '1978-08-08', 47, '704 Pedro Gil St.', 'Nangka', 'Hypertension', NULL, '09900112233', '2026-01-27', 'High blood pressure', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Carpenter', 1, '2026-02-23 08:09:33', 'PAT-20260127-FRA-2021'),
(340, NULL, '212223242526', 'Linda Amorsolo', 'FAM030', 'No', 'Susan Fernandez', '1985-02-28', 40, '813 Herrera St.', 'Zapatera', 'Allergies', NULL, '09011223344', '2026-02-04', 'Multiple allergies', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Waitress', 1, '2026-02-23 08:09:33', 'PAT-20260204-SUS-2122'),
(341, NULL, '222324252627', 'Arturo Tolentino', 'FAM031', 'Yes', 'Jose Cojuangco', '1971-10-10', 54, '922 Victoria St.', 'San Roque', 'Heart Disease', NULL, '09122334455', '2026-01-17', 'Heart bypass 2021', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Businessman', 1, '2026-02-23 08:09:33', 'PAT-20260117-JOS-2223'),
(342, NULL, '232425262728', 'Socorro Ramos', 'FAM032', 'No', 'Luz Bautista', '1988-04-04', 37, '231 Junquera St.', 'City Central', 'None', NULL, '09233445566', '2026-02-06', 'No known conditions', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'HR Staff', 1, '2026-02-23 08:09:33', 'PAT-20260206-LUZ-2324'),
(343, NULL, '242526272829', 'Rafael Alunan', 'FAM033', 'Yes', 'Eduardo Cojuangco', '1976-05-20', 49, '342 Colon St.', 'Sta. Cruz', 'Gout', NULL, '09344556677', '2026-01-23', 'Gout attacks', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Truck Driver', 1, '2026-02-23 08:09:33', 'PAT-20260123-EDU-2425'),
(344, NULL, '252627282930', 'Nelia Sancho', 'FAM034', 'No', 'Charito Planas', '1983-12-12', 42, '453 Pelaez St.', 'Nangka', 'Asthma', NULL, '09455667788', '2026-02-02', 'Moderate asthma', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Office Clerk', 1, '2026-02-23 08:09:33', 'PAT-20260202-CHA-2526'),
(345, NULL, '262728293031', 'Isagani Cruz', 'FAM035', 'Yes', 'Reynaldo Puno', '1980-09-09', 45, '564 D. Jakosalem St.', 'Zapatera', 'High Cholesterol', NULL, '09566778899', '2026-01-19', 'Cholesterol issues', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Police Officer', 1, '2026-02-23 08:09:33', 'PAT-20260119-REY-2627'),
(346, NULL, '272829303132', 'Lorna Verano', 'FAM036', 'No', 'Imelda Cruz', '1992-07-07', 33, '675 B. Rodriguez St.', 'San Roque', 'Migraine', NULL, '09677889900', '2026-02-15', 'Frequent migraines', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'Student', 1, '2026-02-23 08:09:33', 'PAT-20260215-IME-2728'),
(347, NULL, '282930313233', 'Bernardo Bernardo', 'FAM037', 'Yes', 'Leo Martinez', '1974-11-11', 51, '786 V. Rama St.', 'City Central', 'Diabetes', NULL, '09788990011', '2026-01-13', 'Diabetes Type 2', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Actor', 1, '2026-02-23 08:09:33', 'PAT-20260113-LEO-2829'),
(348, NULL, '293031323334', 'Mely Tagasa', 'FAM038', 'No', 'Nova Villa', '1981-02-14', 44, '897 F. Ramos St.', 'Sta. Cruz', 'None', NULL, '09899001122', '2026-02-09', 'Healthy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Married', 'Actress', 1, '2026-02-23 08:09:33', 'PAT-20260209-NOV-2930'),
(349, NULL, '303132333435', 'Subas Herrero', 'FAM039', 'Yes', 'Joel Torre', '1977-06-18', 48, '108 M. Velez St.', 'Nangka', 'Arthritis', NULL, '09900112233', '2026-01-26', 'Joint pain', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Actor', 1, '2026-02-23 08:09:33', 'PAT-20260126-JOE-3031'),
(350, NULL, '313233343536', 'Chichay Daluz', 'FAM040', 'No', 'Caridad Sanchez', '1986-03-25', 39, '219 Sikatuna St.', 'Zapatera', 'Anemia', NULL, '09011223344', '2026-02-10', 'Low iron', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Single', 'Teacher', 1, '2026-02-23 08:09:33', 'PAT-20260210-CAR-3132'),
(351, NULL, '323334353637', 'Rolly Quizon', 'FAM041', 'Yes', 'Dolphy Jr', '1972-08-30', 53, '320 Katipunan St.', 'San Roque', 'Hypertension', NULL, '09122334455', '2026-01-07', 'High BP', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Comedian', 1, '2026-02-23 08:09:33', 'PAT-20260107-DOL-3233'),
(352, NULL, '333435363738', 'Zsa Zsa Padilla', 'FAM042', 'No', 'Kuh Ledesma', '1989-01-15', 37, '431 Quezon St.', 'City Central', 'None', NULL, '09233445566', '2026-02-12', 'No issues', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'Singer', 1, '2026-02-23 08:09:33', 'PAT-20260212-KUH-3334'),
(353, NULL, '343536373839', 'Martin Nievera', 'FAM043', 'Yes', 'Gary Valenciano', '1983-10-05', 42, '542 R. Landon St.', 'Sta. Cruz', 'Allergies', NULL, '09344556677', '2026-01-28', 'Skin allergies', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Singer', 1, '2026-02-23 08:09:33', 'PAT-20260128-GAR-3435'),
(354, NULL, '353637383940', 'Regine Velasquez', 'FAM044', 'No', 'Sarah Geronimo', '1994-05-22', 31, '653 Gen. Maxilom Ave.', 'Nangka', 'Thyroid', NULL, '09455667788', '2026-02-17', 'Thyroid condition', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Single', 'Singer', 1, '2026-02-23 08:09:33', 'PAT-20260217-SAR-3536'),
(355, NULL, '363738394041', 'Ogie Alcasid', 'FAM045', 'Yes', 'Rico J Puno', '1975-12-09', 50, '764 M. Cuenco St.', 'Zapatera', 'Gout', NULL, '09566778899', '2026-01-14', 'Gout', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Musician', 1, '2026-02-23 08:09:33', 'PAT-20260114-RIC-3637'),
(356, NULL, '373839404142', 'Sharon Cuneta', 'FAM046', 'No', 'Judy Ann Santos', '1988-04-28', 37, '875 Gorordo Ave.', 'San Roque', 'None', NULL, '09677889900', '2026-02-05', 'Healthy', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'online', 'Married', 'Actress', 1, '2026-02-23 08:09:33', 'PAT-20260205-JUD-3738'),
(357, NULL, '383940414243', 'Aga Muhlach', 'FAM047', 'Yes', 'Richard Gomez', '1979-07-16', 46, '986 Escario St.', 'City Central', 'Asthma', NULL, '09788990011', '2026-01-21', 'Mild asthma', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Actor', 1, '2026-02-23 08:09:33', 'PAT-20260121-RIC-3839'),
(358, NULL, '394041424344', 'Dawn Zulueta', 'FAM048', 'No', 'Maricel Soriano', '1982-09-02', 43, '197 Salinas Dr.', 'Sta. Cruz', 'Migraine', NULL, '09899001122', '2026-02-14', 'Headaches', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Married', 'Actress', 1, '2026-02-23 08:09:33', 'PAT-20260214-MAR-3940'),
(359, NULL, '404142434445', 'Cesar Montano', 'FAM049', 'Yes', 'Christopher de Leon', '1970-06-12', 55, '308 F. Cabahug St.', 'Nangka', 'Heart Disease', NULL, '09900112233', '2026-01-03', 'Heart condition', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Married', 'Director', 1, '2026-02-23 08:09:33', 'PAT-20260303-CHR-7352'),
(360, NULL, '414243444546', 'Nora Aunor', 'FAM050', 'No', 'Vilma Santos', '1985-11-20', 40, '419 Juana Osmena St.', 'Zapatera', 'Diabetes', NULL, '09011223344', '2026-02-19', 'Diabetes', NULL, '2026-02-23 00:09:33', NULL, NULL, 'Female', '2026-03-10 15:04:03', 'onsite', 'Single', 'Actress', 1, '2026-02-23 08:09:33', 'PAT-20260219-VIL-4142'),
(811, 222, '0000000', 'Archiel R. Cabanag', '09206001470', 'Yes', 'Archiel R. Cabanag', '2002-05-26', 23, 'Labangon Cebu City', 'San Vicente', NULL, NULL, '09816497664', '2026-02-09', NULL, NULL, '2026-02-28 02:31:49', NULL, '2026-02-28 02:31:49', 'Male', '2026-03-11 05:08:22', 'onsite', 'Single', 'Student Teacher', 0, NULL, 'PAT-20260310-ARC-8402'),
(814, NULL, '524323', 'Jinky Figuracion', '09206001470', 'Yes', 'Creshiel Manloloyo', '2002-07-03', 23, 'Barangay Luz, Cebu City', 'Sto.niño lll', NULL, NULL, '09816497664', '2026-03-03', NULL, NULL, '2026-03-02 16:58:45', NULL, NULL, 'Male', '2026-03-10 15:04:03', 'onsite', 'Single', 'Student Teacher', 1, '2026-03-03 00:58:45', 'PAT-20260305-CRE-4178'),
(815, 223, '123', 'Jinky Figuracion', '09206001470', 'Yes', 'Warren Miguel Miras', '1998-02-03', 28, 'tisa cebu', 'City Central', NULL, NULL, '09504206113', '2026-03-12', NULL, NULL, '2026-03-05 03:10:30', NULL, NULL, 'Male', '2026-03-13 06:29:28', 'onsite', 'Single', 'Student', 1, '2026-03-05 11:10:30', 'PAT-20260311-WAR-1609'),
(816, 226, '09087656n', NULL, '12345', 'Yes', 'Juzaly VIllaruz', '1989-07-06', 36, '440 Kalinao St Bo luz Cebu City', 'Kalinao', NULL, NULL, '09290880524', '2026-03-05', NULL, NULL, '2026-03-05 06:24:12', NULL, NULL, 'Female', '2026-03-13 13:59:54', 'onsite', 'Married', 'Teacher', 1, '2026-03-05 14:24:12', 'PAT-20260313-JUZ-8159');

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
(13, 'Archiel', '$2y$10$J47TKGo72XKyzUGOsXdFBuLnZNr9Zsgo1wHCdgrtbV19dyYpUurd2', 'Archiel R. Cabanag', 'Nurse', NULL, '2026-03-13 04:36:06', NULL, 'active', 1, '1111100', 'operation', '53123213');

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
(222, 'Archiel', '$2y$10$NE.sZ12vOSNrfeHkaJvnEOeHb0Yob3TgWUxt0JzJA6aZECuknbjq2', 'cabanagarchielrosel@gmail.com', 'Archiel R. Cabanag', 'male', 23, '2002-05-26', NULL, 'San Roque', '09816497664', NULL, NULL, 1, NULL, 'RESSAN202603595', '2026-03-10 14:34:21', NULL, 'approved', 'patient', NULL, NULL, '2026-03-11 05:00:29', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-10 14:34:21', 0, NULL, 'PAT-20260310-ARC-8402', 0, NULL, NULL, NULL, NULL),
(223, 'Warren', '$2y$10$dnCiRssAzE9NW/c4GZWhCOiMwD7nO5n/zMxHwmpTKVcccjv.lmnjS', 'warrenmiguel789@gmail.com', 'Warren Miguel Miras', 'male', 23, '2002-03-12', NULL, 'San Vicente', '09206001470', NULL, NULL, 1, NULL, 'RESSAN202603643', '2026-03-11 03:12:46', NULL, 'approved', 'patient', NULL, NULL, '2026-03-11 05:10:02', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-11 03:12:46', 0, NULL, 'PAT-20260311-WAR-1609', 0, NULL, NULL, NULL, NULL),
(226, 'Juzaly', '$2y$10$.RK59/lllAyGjeQpVrGDeeQFmjTqSRsMPn6AMAosyPnEKpxurmqia', 'juzalyvillaruz@gmail.com', 'Juzaly VIllaruz', 'male', 36, '1989-07-06', NULL, 'Mabuhay', '09816497664', NULL, NULL, 1, NULL, 'RESMAB202603425', '2026-03-13 13:58:33', NULL, 'approved', 'patient', NULL, NULL, '2026-03-13 13:59:54', 'manual_verification', NULL, NULL, NULL, 0, 1, '2026-03-13 13:58:33', 0, NULL, 'PAT-20260313-JUZ-8159', 0, NULL, NULL, NULL, NULL);

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
(371, 12, 'export_bulk_pdf', NULL, '{\"full_name\":\"Russel Evan Loquinario\",\"record_count\":1,\"export_type\":\"bulk_patient_records\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 20:19:15');

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
(532, 226, 'login', '2026-03-13 21:58:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0');

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
(172, 222, 291, 'dismissed', '2026-03-13 06:39:16', '2026-03-13 06:39:16');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

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
-- AUTO_INCREMENT for table `city_health_report_logs`
--
ALTER TABLE `city_health_report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `consultation_notes`
--
ALTER TABLE `consultation_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `deleted_patients`
--
ALTER TABLE `deleted_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `existing_info_patients`
--
ALTER TABLE `existing_info_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=341;

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
-- AUTO_INCREMENT for table `sitio1_account_linking_history`
--
ALTER TABLE `sitio1_account_linking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_activity_log`
--
ALTER TABLE `sitio1_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `sitio1_announcements`
--
ALTER TABLE `sitio1_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `sitio1_consultations`
--
ALTER TABLE `sitio1_consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitio1_patients`
--
ALTER TABLE `sitio1_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=817;

--
-- AUTO_INCREMENT for table `sitio1_staff`
--
ALTER TABLE `sitio1_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sitio1_users`
--
ALTER TABLE `sitio1_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372;

--
-- AUTO_INCREMENT for table `staff_documents`
--
ALTER TABLE `staff_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=533;

--
-- AUTO_INCREMENT for table `user_announcements`
--
ALTER TABLE `user_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
