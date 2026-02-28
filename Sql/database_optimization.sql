-- ============================================
-- DATABASE OPTIMIZATION & SCHEMA UPDATES
-- healthpatient Database - Community Health Tracker
-- ============================================
-- This script updates the database schema to:
-- 1. Add missing columns to deleted_patients for medical data preservation
-- 2. Add missing metadata columns needed by restoration logic
-- 3. Add missing feature columns (visit_purpose, archived_date tracking)
-- 4. Add indexes for performance optimization
-- ============================================

-- Set strict mode for data integrity
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- 1. ENHANCE deleted_patients TABLE
-- ============================================
-- Add missing medical data columns for preservation during archiving
ALTER TABLE `deleted_patients`
ADD COLUMN `height` DECIMAL(5,2) NULL AFTER `contact`,
ADD COLUMN `weight` DECIMAL(5,2) NULL AFTER `height`,
ADD COLUMN `temperature` DECIMAL(4,2) NULL AFTER `weight`,
ADD COLUMN `blood_pressure` VARCHAR(20) NULL AFTER `temperature`,
ADD COLUMN `blood_type` VARCHAR(3) NULL AFTER `blood_pressure`,
ADD COLUMN `allergies` TEXT NULL AFTER `blood_type`,
ADD COLUMN `medical_history` TEXT NULL AFTER `allergies`,
ADD COLUMN `current_medications` TEXT NULL AFTER `medical_history`,
ADD COLUMN `family_history` TEXT NULL AFTER `current_medications`,
ADD COLUMN `immunization_record` TEXT NULL AFTER `family_history`,
ADD COLUMN `chronic_conditions` TEXT NULL AFTER `immunization_record`,
ADD COLUMN `family_medical_history` TEXT NULL AFTER `chronic_conditions`;

-- Add missing metadata columns for archive tracking and restoration logic
ALTER TABLE `deleted_patients`
ADD COLUMN `sitio` VARCHAR(255) NULL AFTER `family_medical_history`,
ADD COLUMN `civil_status` VARCHAR(20) NULL AFTER `sitio`,
ADD COLUMN `occupation` VARCHAR(100) NULL AFTER `civil_status`,
ADD COLUMN `unique_number` VARCHAR(20) NULL AFTER `occupation`,
ADD COLUMN `user_email` VARCHAR(100) NULL AFTER `unique_number`,
ADD COLUMN `is_registered_user` TINYINT(1) DEFAULT 0 AFTER `user_email`,
ADD COLUMN `delete_type` VARCHAR(20) DEFAULT 'manual' AFTER `is_registered_user`,
ADD COLUMN `archived_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `delete_type`;

-- Update archived_date to match deleted_at if not set
UPDATE `deleted_patients` SET `archived_date` = `deleted_at` WHERE `archived_date` IS NULL;

-- Add index on archived_date for faster queries
ALTER TABLE `deleted_patients`
ADD INDEX `idx_archived_date` (`archived_date` DESC),
ADD INDEX `idx_original_id` (`original_id`),
ADD INDEX `idx_deleted_by` (`deleted_by`);

-- ============================================
-- 2. ENHANCE patient_visits TABLE
-- ============================================
-- Add missing column for capturing visit purpose
ALTER TABLE `patient_visits`
ADD COLUMN `visit_purpose` VARCHAR(255) NULL AFTER `visit_type`;

-- Add indexes for better query performance
ALTER TABLE `patient_visits`
ADD INDEX `idx_visit_date` (`visit_date` DESC),
ADD INDEX `idx_patient_visit_date` (`patient_id`, `visit_date` DESC);

-- ============================================
-- 3. ENHANCE existing_info_patients TABLE
-- ============================================
-- Ensure all required columns exist with proper indexing
ALTER TABLE `existing_info_patients`
ADD INDEX `idx_updated_at` (`updated_at` DESC);

-- ============================================
-- 4. ENHANCE consultation_notes TABLE
-- ============================================
-- Add index for faster lookups by patient and date
ALTER TABLE `consultation_notes`
ADD INDEX `idx_consultation_date` (`consultation_date` DESC),
ADD INDEX `idx_patient_consultation_date` (`patient_id`, `consultation_date` DESC);

-- ============================================
-- 5. ENHANCE sitio1_patients TABLE
-- ============================================
-- Add index for deleted_at queries (soft-delete filtering)
ALTER TABLE `sitio1_patients`
ADD INDEX `idx_deleted_at` (`deleted_at`),
ADD INDEX `idx_updated_at` (`updated_at` DESC),
ADD INDEX `idx_user_deleted` (`user_id`, `deleted_at`);

-- ============================================
-- 6. ENHANCE audit_logs TABLE
-- ============================================
-- Add indexes for audit trail queries
ALTER TABLE `audit_logs`
ADD INDEX `idx_user_action` (`user_id`, `created_at` DESC),
ADD INDEX `idx_table_record` (`table_affected`, `record_id`),
ADD INDEX `idx_created_at` (`created_at` DESC);

-- ============================================
-- 7. ENHANCE staff_activity_log TABLE
-- ============================================
-- Add indexes for staff activity tracking
ALTER TABLE `staff_activity_log`
ADD INDEX `idx_staff_action` (`staff_id`, `action_type`),
ADD INDEX `idx_created` (`created_at` DESC),
ADD INDEX `idx_related` (`related_id`);

-- ============================================
-- 8. ENHANCE user_activity_log TABLE
-- ============================================
-- Add indexes for user activity tracking
ALTER TABLE `user_activity_log`
ADD INDEX `idx_user_action` (`user_id`, `action_type`),
ADD INDEX `idx_timestamp` (`action_timestamp` DESC);

-- ============================================
-- 9. VERIFY CRITICAL FOREIGN KEYS
-- ============================================
-- These are already properly defined but listed for reference:
-- - deleted_patients.original_id (references sitio1_patients conceptually)
-- - deleted_patients.added_by (references sitio1_staff.id)
-- - deleted_patients.deleted_by (references sitio1_staff.id)
-- - deleted_patients.user_id (references sitio1_users.id)

-- ============================================
-- SCHEMA NOTES & RECOMMENDATIONS
-- ============================================
/*

ACTIVE TABLES (Currently Used):
-------------------------------
1. sitio1_users          - User accounts and registration
2. sitio1_patients       - Patient records (main table)
3. sitio1_staff          - Staff/healthcare worker accounts
4. admin                 - Administrator accounts
5. existing_info_patients - Medical information for patients
6. consultation_notes    - Doctor's consultation notes (preserved on archive)
7. patient_visits        - Patient visit history
8. deleted_patients      - Archive of deleted patient records
9. sitio1_announcements  - System announcements
10. announcement_targets - Targeting for specific announcements
11. announcement_messages - Discussion messages on announcements
12. user_announcements   - User responses to announcements
13. staff_documents      - Uploaded documents by staff
14. staff_activity_log   - Audit trail for staff actions
15. user_activity_log    - Audit trail for user actions
16. audit_logs           - General audit logging
17. sitio1_account_linking_history - User-patient account linking history

TABLES WITH LIMITED/NO USE (Candidates for Cleanup):
---------------------------------------------------
1. account_linking_history - DUPLICATE of sitio1_account_linking_history
   - Recommendation: REMOVE (redundant, use sitio1_account_linking_history instead)
   
2. sitio1_consultations - UNUSED (different from consultation_notes)
   - Recommendation: REMOVE or clarify purpose (appears to be Q&A, not doctor consultations)
   
-- ...existing code...

COLUMNS NEWLY ADDED TO deleted_patients:
-----------------------------------------
Medical Data (for preservation during archiving):
- height, weight, temperature, blood_pressure, blood_type
- allergies, medical_history, current_medications, family_history
- immunization_record, chronic_conditions, family_medical_history

Metadata (for restoration and tracking):
- sitio, civil_status, occupation, unique_number, user_email
- is_registered_user, delete_type, archived_date

PERFORMANCE OPTIMIZATIONS:
--------------------------
- Added indexes on frequently queried columns (deleted_at, updated_at, dates)
- Added composite indexes for common query patterns
- Added archived_date index for archive listing performance

*/

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@COLLATION_CONNECTION */;
