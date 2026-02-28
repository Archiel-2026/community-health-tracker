-- ============================================
-- CLEANUP - REMOVE UNUSED TABLES
-- ============================================
-- WARNING: This script removes duplicate and unused tables
-- BACKUP YOUR DATABASE BEFORE RUNNING THIS!
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- 1. REMOVE DUPLICATE TABLE
-- ============================================
-- account_linking_history is a duplicate of sitio1_account_linking_history
-- Keep: sitio1_account_linking_history (actively used)
-- Remove: account_linking_history (redundant)

DROP TABLE IF EXISTS `account_linking_history`;

-- ============================================
-- 2. REMOVE UNUSED APPOINTMENT SYSTEM
-- ============================================
-- These tables are not used in the current system
-- No code references found for appointment booking functionality

-- ...existing code...

-- ============================================
-- 3. REMOVE UNUSED CONSULTATION SYSTEM (Optional)
-- ============================================
-- sitio1_consultations is separate from consultation_notes
-- consultation_notes is actively used for doctor consultations
-- sitio1_consultations appears to be for Q&A (unused)
--
-- UNCOMMENT ONLY IF YOU CONFIRM THIS TABLE IS NOT USED:
-- DROP TABLE IF EXISTS `sitio1_consultations`;

-- ============================================
-- SUMMARY OF CHANGES
-- ============================================
/*

REMOVED TABLES:
---------------
1. account_linking_history 
   - Reason: Duplicate of sitio1_account_linking_history
   - Status: REMOVED

2. user_appointments
   - Reason: Unused appointment system (dependent on sitio1_appointments)
   - Status: REMOVED

3. sitio1_appointments
   - Reason: Unused appointment booking system
   - Status: REMOVED

POTENTIALLY REMOVABLE (NOT REMOVED - UNCOMMENT IF NEEDED):
----------------------------------------------------------
1. sitio1_consultations
   - Reason: Unused Q&A consultation system
   - Note: Different from consultation_notes (which is actively used)
   - Action: Review code usage before removing

RETAINED ACTIVE TABLES:
-----------------------
- sitio1_account_linking_history (user-patient linking history)
- consultation_notes (doctor consultations - actively used)
- patient_visits (visit records - actively used)
- All other core tables

*/

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@COLLATION_CONNECTION */;
