<?php

// --- AUTO DELETE OLD ARCHIVED/SOFT-DELETED RECORDS (older than 5 years/60 months) ---
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

// Only run auto-delete if staff is logged in (avoid running on every include)
if (isset($_SESSION['user']) && isStaff()) {
    $now = date('Y-m-d H:i:s');
    $fiveYearsAgo = date('Y-m-d H:i:s', strtotime('-5 years'));
    try {
        // 1. Hard delete from deleted_patients (archived records)
        $stmt = $pdo->prepare("DELETE FROM deleted_patients WHERE deleted_at IS NOT NULL AND deleted_at < ?");
        $stmt->execute([$fiveYearsAgo]);

        // 2. Hard delete from sitio1_patients (soft-deleted records)
        $stmt = $pdo->prepare("DELETE FROM sitio1_patients WHERE deleted_at IS NOT NULL AND deleted_at < ?");
        $stmt->execute([$fiveYearsAgo]);
    } catch (Exception $e) {
        error_log('Auto hard-delete error (5 years): ' . $e->getMessage());
    }
}

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

$message = '';
$error = '';
$notificationType = '';
$notificationMessage = '';
$notificationDuration = 5000;

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $notificationType = 'success';
    $notificationMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle patient restoration - COMPLETE VERSION (restores ALL fields)
if (isset($_GET['restore_patient'])) {
    $patientId = $_GET['restore_patient'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // First try to find in deleted_patients table (hard delete)
        $stmt = $pdo->prepare("SELECT * FROM deleted_patients WHERE original_id = ?");
        $stmt->execute([$patientId]);
        $archivedPatient = $stmt->fetch(PDO::FETCH_ASSOC);

        // If not found in deleted_patients, try soft-deleted in sitio1_patients
        if (!$archivedPatient) {
            $stmt = $pdo->prepare("SELECT * FROM sitio1_patients WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$patientId]);
            $archivedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($archivedPatient) {
                // This is a soft-deleted record - just clear the deleted_at flag
                $stmt = $pdo->prepare("UPDATE sitio1_patients SET deleted_at = NULL, restored_at = NOW() WHERE id = ?");
                $stmt->execute([$patientId]);
                $pdo->commit();
                $_SESSION['success_message'] = 'Patient record restored successfully!';
                header('Location: deleted_patients.php');
                exit();
            }
        }

        if ($archivedPatient) {
            // Check if patient already exists in main table
            $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$patientId]);
            
            if ($stmt->fetch()) {
                $error = 'This patient already exists in the active records!';
                $notificationType = 'error';
                $notificationMessage = $error;
            } else {
                // ========== RESTORE TO sitio1_patients - ALL FIELDS ==========
                // First, get all columns from sitio1_patients
                $stmt = $pdo->prepare("SHOW COLUMNS FROM sitio1_patients");
                $stmt->execute();
                $mainTableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Build dynamic INSERT for sitio1_patients
                $mainColumns = [];
                $mainPlaceholders = [];
                $mainValues = [];
                
                // Map archived fields to main table columns
                $fieldMapping = [
                    'id' => 'original_id',
                    'full_name' => 'full_name',
                    'date_of_birth' => 'date_of_birth',
                    'age' => 'age',
                    'gender' => 'gender',
                    'address' => 'address',
                    'sitio' => 'sitio',
                    'civil_status' => 'civil_status',
                    'occupation' => 'occupation',
                    'contact' => 'contact',
                    'last_checkup' => 'last_checkup',
                    'added_by' => 'added_by',
                    'user_id' => 'user_id',
                    'medical_history_summary' => 'medical_history_summary',
                    'status' => 'status',
                    'unique_id' => 'unique_id',
                    'qr_code' => 'qr_code',
                    'qr_verified' => 'qr_verified',
                    'verification_date' => 'verification_date'
                ];
                
                foreach ($fieldMapping as $mainCol => $archiveCol) {
                    if (in_array($mainCol, $mainTableColumns) && isset($archivedPatient[$archiveCol])) {
                        $mainColumns[] = $mainCol;
                        $mainPlaceholders[] = '?';
                        $mainValues[] = $archivedPatient[$archiveCol];
                    }
                }
                
                // Add restored_at and set deleted_at to NULL
                if (in_array('restored_at', $mainTableColumns)) {
                    $mainColumns[] = 'restored_at';
                    $mainPlaceholders[] = 'NOW()';
                }
                if (in_array('deleted_at', $mainTableColumns)) {
                    $mainColumns[] = 'deleted_at';
                    $mainPlaceholders[] = 'NULL';
                }
                if (in_array('updated_at', $mainTableColumns)) {
                    $mainColumns[] = 'updated_at';
                    $mainPlaceholders[] = 'NOW()';
                }
                
                // Build and execute main table insert
                $mainInsertQuery = "INSERT INTO sitio1_patients (" . implode(", ", $mainColumns) . ") 
                                    VALUES (" . implode(", ", $mainPlaceholders) . ")";
                error_log("Main restore query: " . $mainInsertQuery);
                $stmt = $pdo->prepare($mainInsertQuery);
                $stmt->execute($mainValues);
                
                // ========== RESTORE TO existing_info_patients - ALL MEDICAL FIELDS ==========
                // First, check if we have medical data in the archive
                $medicalDataExists = false;
                
                // Get all columns from existing_info_patients
                $stmt = $pdo->prepare("SHOW COLUMNS FROM existing_info_patients");
                $stmt->execute();
                $medicalTableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // All possible medical fields that could be in the archive
                $medicalFields = [
                    // Basic medical info
                    'blood_type', 'height', 'weight', 'temperature', 'blood_pressure',
                    'blood_pressure_systolic', 'blood_pressure_diastolic',
                    'allergies', 'medical_history', 'current_medications',
                    'family_history', 'immunization_record', 'chronic_conditions',
                    'medical_conditions', 'medical_conditions_other',
                    // Personal info (from existing_info_patients)
                    'student_last_name', 'student_first_name', 'student_middle_name',
                    'student_birthdate', 'student_religion', 'student_home_address', 'student_occupation',
                    'student_effective_date', 'student_sex', 'student_nickname',
                    'student_home_phone', 'student_office_phone', 'student_fax_number',
                    'student_mobile_number', 'student_email_address',
                    'parent_guardian_name', 'parent_guardian_occupation',
                    // Physician info
                    'student_physician_name', 'student_physician_specialty',
                    'student_physician_office_address', 'student_physician_office_number',
                    // Health questionnaire
                    'student_good_health', 'student_under_treatment', 'student_treatment_condition',
                    'student_serious_illness_surgery', 'student_serious_illness_details',
                    'student_hospitalized', 'student_hospitalization_details',
                    'student_taking_medication', 'student_medication_details',
                    'student_uses_tobacco', 'student_uses_alcohol_drugs',
                    'student_has_allergies', 'student_allergy_items', 'student_allergy_other',
                    'student_bleeding_time',
                    // Women's health
                    'student_is_pregnant', 'student_is_nursing', 'student_takes_birth_control',
                    'student_conditions', 'student_condition_other',
                    'student_menarche', 'student_lmp', 'student_gravida', 'student_para', 'student_abortion',
                    // Signature
                    'student_signature_name'
                ];
                
                $medicalColumns = [];
                $medicalPlaceholders = [];
                $medicalValues = [];
                
                foreach ($medicalFields as $field) {
                    if (in_array($field, $medicalTableColumns) && 
                        isset($archivedPatient[$field]) && 
                        $archivedPatient[$field] !== null && 
                        $archivedPatient[$field] !== '') {
                        $medicalColumns[] = $field;
                        $medicalPlaceholders[] = '?';
                        $medicalValues[] = $archivedPatient[$field];
                        $medicalDataExists = true;
                    }
                }
                
                // Always add patient_id
                if (in_array('patient_id', $medicalTableColumns)) {
                    $medicalColumns[] = 'patient_id';
                    $medicalPlaceholders[] = '?';
                    $medicalValues[] = $patientId;
                }
                
                // Add created_at if not restoring from archive
                if (in_array('created_at', $medicalTableColumns) && !in_array('created_at', $medicalColumns)) {
                    $medicalColumns[] = 'created_at';
                    $medicalPlaceholders[] = 'NOW()';
                }
                
                // Add updated_at
                if (in_array('updated_at', $medicalTableColumns)) {
                    $medicalColumns[] = 'updated_at';
                    $medicalPlaceholders[] = 'NOW()';
                }
                
                // Only insert if we have medical data
                if ($medicalDataExists && !empty($medicalColumns)) {
                    // Check if medical record already exists for this patient
                    $checkMedical = $pdo->prepare("SELECT id FROM existing_info_patients WHERE patient_id = ?");
                    $checkMedical->execute([$patientId]);
                    
                    if (!$checkMedical->fetch()) {
                        $medicalInsertQuery = "INSERT INTO existing_info_patients (" . implode(", ", $medicalColumns) . ") 
                                               VALUES (" . implode(", ", $medicalPlaceholders) . ")";
                        error_log("Medical restore query: " . $medicalInsertQuery);
                        $stmtMedical = $pdo->prepare($medicalInsertQuery);
                        $stmtMedical->execute($medicalValues);
                    } else {
                        // Update existing medical record
                        $updateSets = [];
                        for ($i = 0; $i < count($medicalColumns); $i++) {
                            if ($medicalColumns[$i] != 'patient_id' && $medicalColumns[$i] != 'created_at') {
                                $updateSets[] = $medicalColumns[$i] . " = ?";
                            }
                        }
                        $medicalUpdateQuery = "UPDATE existing_info_patients SET " . implode(", ", $updateSets) . " 
                                               WHERE patient_id = ?";
                        $updateValues = array_filter($medicalValues, function($key) use ($medicalColumns) {
                            return $medicalColumns[$key] != 'patient_id' && $medicalColumns[$key] != 'created_at';
                        }, ARRAY_FILTER_USE_KEY);
                        $updateValues[] = $patientId;
                        $stmtMedical = $pdo->prepare($medicalUpdateQuery);
                        $stmtMedical->execute($updateValues);
                    }
                }
                
                // ========== RESTORE CONSULTATION NOTES ==========
                // Check if consultation notes exist in archive (they would be in separate table)
                // Note: Consultation notes are typically not stored in deleted_patients,
                // they remain in consultation_notes table with patient_id reference
                // So we just need to ensure they're linked to the restored patient
                
                // ========== DELETE FROM ARCHIVE AFTER SUCCESSFUL RESTORATION ==========
                $stmt = $pdo->prepare("DELETE FROM deleted_patients WHERE original_id = ?");
                $stmt->execute([$patientId]);
                
                $pdo->commit();
                
                $_SESSION['success_message'] = 'Patient record "' . $archivedPatient['full_name'] . '" restored successfully with all medical information!';
                header('Location: deleted_patients.php');
                exit();
            }
        } else {
            $error = 'Archived patient not found!';
            $notificationType = 'error';
            $notificationMessage = $error;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error restoring patient record: ' . $e->getMessage();
        $notificationType = 'error';
        $notificationMessage = $error;
        error_log('Restoration Error: ' . $e->getMessage());
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Restoration failed: ' . $e->getMessage();
        $notificationType = 'error';
        $notificationMessage = $error;
        error_log('Restoration Exception: ' . $e->getMessage());
    }
}

// Get all deleted patients with user information (both hard-deleted and soft-deleted)
try {
    require_once __DIR__ . '/../includes/functions.php';

    // Search logic
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $params = [];
    $whereClause1 = "1=1";
    $whereClause2 = "p.deleted_at IS NOT NULL";
    
    if (!staff_can_view_all()) {
        $whereClause1 = "d.deleted_by = ?";
        $whereClause2 = "p.added_by = ?";
        $params[] = $_SESSION['user']['id'];
        $params[] = $_SESSION['user']['id'];
    }
    if ($search !== '') {
        $whereClause1 .= " AND d.full_name LIKE ?";
        $whereClause2 .= " AND p.full_name LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Query for hard-deleted records - only use columns that definitely exist
    $hardDeleteQuery = "SELECT 
                          'hard_delete' as delete_type,
                          d.original_id,
                          d.full_name,
                          d.date_of_birth,
                          d.age,
                          d.address,
                          d.contact,
                          d.last_checkup,
                          d.added_by,
                          d.user_id,
                          d.deleted_by,
                          d.deleted_at as archived_date,
                          NULL as gender,
                          NULL as sitio,
                          NULL as civil_status,
                          NULL as occupation,
                          NULL as blood_type,
                          NULL as unique_number,
                          NULL as user_email,
                          CASE WHEN d.user_id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user
                      FROM deleted_patients d
                      WHERE $whereClause1";

    // Query for soft-deleted records - ONLY use columns that definitely exist in sitio1_patients
    $softDeleteQuery = "SELECT 
                          'soft_delete' as delete_type,
                          p.id as original_id,
                          p.full_name,
                          p.date_of_birth,
                          p.age,
                          p.address,
                          p.contact,
                          p.last_checkup,
                          p.added_by,
                          p.user_id,
                          p.added_by as deleted_by,
                          p.deleted_at as archived_date,
                          NULL as gender,
                          NULL as sitio,
                          NULL as civil_status,
                          NULL as occupation,
                          NULL as blood_type,
                          u.unique_number,
                          u.email as user_email,
                          CASE WHEN p.user_id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user
                      FROM sitio1_patients p
                      LEFT JOIN sitio1_users u ON p.user_id = u.id
                      WHERE $whereClause2";

    // Combine both queries
    $combinedQuery = "($hardDeleteQuery) UNION ALL ($softDeleteQuery)";

    // Sorting logic
    $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
    $orderBy = '';
    if ($sort === 'name_asc') {
        $orderBy = ' ORDER BY full_name ASC';
    } elseif ($sort === 'name_desc') {
        $orderBy = ' ORDER BY full_name DESC';
    } elseif ($sort === 'date_asc') {
        $orderBy = ' ORDER BY archived_date ASC';
    } else {
        $orderBy = ' ORDER BY archived_date DESC';
    }

    $perPage = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM (($hardDeleteQuery) UNION ALL ($softDeleteQuery)) as all_patients";
    $stmt = $pdo->prepare($countQuery);
    
    // Bind parameters for count query
    $countParams = [];
    if (!staff_can_view_all()) {
        $countParams[] = $_SESSION['user']['id'];
        $countParams[] = $_SESSION['user']['id'];
    }
    if ($search !== '') {
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    
    if (!empty($countParams)) {
        $stmt->execute($countParams);
    } else {
        $stmt->execute();
    }
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $perPage);

    // Add ORDER BY, LIMIT, and OFFSET
    $paginatedQuery = $combinedQuery . $orderBy . " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($paginatedQuery);
    
    // Bind parameters for paginated query
    $paginateParams = [];
    if (!staff_can_view_all()) {
        $paginateParams[] = $_SESSION['user']['id'];
        $paginateParams[] = $_SESSION['user']['id'];
    }
    if ($search !== '') {
        $paginateParams[] = "%$search%";
        $paginateParams[] = "%$search%";
    }
    
    if (!empty($paginateParams)) {
        $stmt->execute($paginateParams);
    } else {
        $stmt->execute();
    }
    $deletedPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Deleted patients found: " . count($deletedPatients));
    
} catch (PDOException $e) {
    $error = "Error fetching deleted patients: " . $e->getMessage();
    $notificationType = 'error';
    $notificationMessage = $error;
    error_log("Deleted patients query error: " . $e->getMessage());
    $deletedPatients = [];
    $totalRows = 0;
    $totalPages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Patients Archive - Barangay Luz Health Center</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3498db',
                        secondary: '#2c3e50',
                        success: '#2ecc71',
                        danger: '#e74c3c',
                        warning: '#f39c12',
                        info: '#17a2b8',
                        warmRed: '#fef2f2',
                        warmBlue: '#f0f9ff'
                    }
                }
            }
        }
    </script>
    <style>
        .user-badge {
            background: #e9d8fd;
            color: #6d28d9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            box-shadow: none;
        }
        .regular-badge {
            background: #bbf7d0;
            color: #059669;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            box-shadow: none;
        }
        .timeleft-badge {
            background: #fee2e2;
            color: #b91c1c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            box-shadow: none;
        }

        /* Main container styling */
        .main-container {
            background-color: white;
            border: 1px solid #f0f9ff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
        }

        /* Section backgrounds */
        .section-bg {
            background-color: white;
            border: 1px solid #f0f9ff;
            border-radius: 12px;
        }

        /* Success/Error message styling */
        .alert-success {
            background-color: #f0fdf4;
            border: 2px solid #bbf7d0;
            color: #065f46;
            border-radius: 8px;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 2px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
        }

        /* Button Styles - Consistent across all buttons */
        .btn-primary,
        .btn-search,
        .btn-filter,
        .btn-clear {
            background-color: white;
            color: #3498db;
            border: 2px solid #bae6fd;
            border-radius: 30px;
            padding: 12px 24px;
            transition: all 0.3s ease;
            font-weight: 500;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            text-decoration: none;
            gap: 8px;
        }

        .btn-primary:hover,
        .btn-search:hover,
        .btn-filter:hover {
            background-color: #f0f9ff;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        }

        /* Clear button variant */
        .btn-clear {
            border-color: #fecaca;
            color: #e74c3c;
        }

        .btn-clear:hover {
            background-color: #fef2f2;
            border-color: #e74c3c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.15);
        }

        .btn-restore {
            background-color: #2ecc71;
            color: #ffffff;
            border-radius: 30px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 2px solid transparent;
            gap: 8px;
        }

        .btn-restore:hover {
            background-color: #42d881;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.15);
        }

        /* Search and Filter Section */
        .search-section {
            background-color: white;
            border: 1px solid #f0f9ff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        /* Input field styling */
        .search-input {
            width: 100%;
            border: 2px solid #f0f9ff;
            border-radius: 30px;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Clear icon inside input - properly positioned */
        .clear-search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: transparent;
        }

        .clear-search-icon:hover {
            color: #e74c3c;
            background-color: #fef2f2;
        }

        /* Select dropdown styling - with integrated chevron that rotates */
        .sort-select-wrapper {
            position: relative;
            display: inline-block;
        }

        .sort-select {
            border: 2px solid #f0f9ff;
            border-radius: 30px;
            padding: 0.75rem 2.5rem 0.75rem 1.25rem;
            font-size: 0.95rem;
            color: #2c3e50;
            background-color: white;
            appearance: none;
            cursor: pointer;
            min-width: 200px;
            width: 100%;
            line-height: 1.5;
        }

        .sort-select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Integrated chevron icon inside select */
        .sort-select-chevron {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            padding-left: 4px;
            transition: transform 0.3s ease;
        }

        /* Rotated state when select is open - chevron points up */
        .sort-select-wrapper.sort-select-open .sort-select-chevron {
            transform: translateY(-50%) rotate(180deg);
        }

        /* Table styling - IMPROVED for consistent padding */
        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-table th,
        .patient-table td {
            padding: 16px 24px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .patient-table th {
            background-color: #f0f9ff;
            color: #2c3e50;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            font-size: 14px;
        }

        .patient-table tr:hover {
            background-color: #f8fafc;
        }

        /* Custom notification animation */
        .custom-notification {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Pagination styling - UPDATED for circular buttons and centered */
        .pagination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .pagination-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background-color: white;
            border: 2px solid #e2e8f0;
            border-radius: 9999px;
            color: #4a5568;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .pagination-link:hover:not(.pagination-link-active):not(.pagination-link-disabled) {
            border-color: #3498db;
            color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }

        .pagination-link-active {
            background-color: #3498db;
            border-color: #3498db;
            color: white;
        }

        .pagination-link-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-arrow {
            width: auto;
            padding: 0 1rem;
            border-radius: 9999px;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: auto;
            max-width: 400px;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
        }

        .modal-icon i {
            font-size: 24px;
            color: #3498db;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #1a202c;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .modal-body {
            padding: 0 1.5rem 1.5rem 1.5rem;
            text-align: center;
            color: #4a5568;
            line-height: 1.6;
        }

        .modal-footer {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .modal-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .modal-btn-cancel {
            background-color: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .modal-btn-cancel:hover {
            background-color: #f7fafc;
            border-color: #cbd5e0;
            transform: translateY(-1px);
        }

        .modal-btn-confirm {
            background-color: #2ecc71;
            color: white;
            border: 2px solid #2ecc71;
        }

        .modal-btn-confirm:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2);
        }

        .patient-name-highlight {
            font-weight: 500;
            color: #3C96E1;
            background-color: rgba(60, 150, 225, 0.2);
            padding: 0.25rem 1rem;
            border-radius: 6px;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .restore-details-list {
            text-align: left;
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8fafc;
            border-radius: 8px;
        }

        .restore-details-list li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .restore-details-list i {
            width: 20px;
            color: #3498db;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .btn-primary,
            .btn-search,
            .btn-filter,
            .btn-clear {
                width: 100%;
                padding: 10px 20px;
                min-height: 44px;
            }

            .sort-select-wrapper {
                width: 100%;
            }

            .sort-select {
                width: 100%;
                min-width: unset;
            }

            .flex.items-center.gap-2 {
                width: 100%;
                margin-left: 0 !important;
            }

            .flex.items-center.gap-2>.sort-select-wrapper {
                flex: 1;
            }

            .search-section .flex-wrap>div {
                width: 100%;
            }

            .patient-table th,
            .patient-table td {
                padding: 12px 16px;
            }

            .pagination-link {
                width: 2.2rem;
                height: 2.2rem;
                font-size: 0.8rem;
            }

            .pagination-arrow {
                width: auto;
                padding: 0 0.75rem;
            }

            .modal-container {
                width: 95%;
                margin: 1rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-icon bg-blue-100 p-4">
                    <svg width="60" height="60" viewBox="0 0 85 85" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M69.9866 13.0335L26.7396 5.39682C25.3522 5.15268 23.9247 5.46955 22.7709 6.27775C21.6171 7.08595 20.8316 8.3193 20.587 9.70659L10.7091 65.8199C10.5883 66.5073 10.6041 67.2118 10.7557 67.8931C10.9072 68.5744 11.1916 69.2192 11.5924 69.7905C11.9933 70.3619 12.5028 70.8487 13.0919 71.2231C13.6809 71.5974 14.338 71.8521 15.0255 71.9724L58.2726 79.6091C58.9603 79.7304 59.6651 79.715 60.3468 79.5636C61.0285 79.4123 61.6736 79.128 62.2454 78.7271C62.8171 78.3262 63.3042 77.8166 63.6788 77.2272C64.0534 76.6379 64.3081 75.9806 64.4284 75.2927L74.3064 19.1794C74.5484 17.7916 74.2292 16.3645 73.4192 15.212C72.6091 14.0595 71.3744 13.2759 69.9866 13.0335ZM59.189 74.3763L15.9386 66.7396L25.8165 10.6263L69.0636 18.263L59.189 74.3763ZM29.6648 19.3986C29.7878 18.7052 30.1811 18.0891 30.7583 17.6856C31.3355 17.2821 32.0493 17.1244 32.7427 17.247L60.3013 22.1113C60.9563 22.226 61.5444 22.5822 61.9494 23.1095C62.3545 23.6368 62.5471 24.2969 62.4891 24.9593C62.4311 25.6217 62.1268 26.2383 61.6363 26.6872C61.1458 27.1361 60.5047 27.3847 59.8398 27.3839C59.684 27.3837 59.5285 27.3704 59.3749 27.3441L31.8163 22.4765C31.123 22.3535 30.5068 21.9601 30.1034 21.383C29.6999 20.8058 29.5421 20.092 29.6648 19.3986ZM27.8253 29.8642C27.8859 29.5206 28.0135 29.1922 28.201 28.898C28.3884 28.6037 28.632 28.3492 28.9179 28.1491C29.2037 27.949 29.5261 27.8072 29.8668 27.7317C30.2075 27.6562 30.5596 27.6486 30.9032 27.7093L58.4618 32.5769C59.1214 32.6872 59.7151 33.0422 60.1244 33.5711C60.5336 34.1 60.7284 34.7637 60.6697 35.4299C60.611 36.096 60.3032 36.7155 59.8077 37.1647C59.3123 37.6138 58.6657 37.8596 57.997 37.8529C57.8399 37.8532 57.6832 37.8387 57.5288 37.8097L29.9702 32.9455C29.2774 32.8209 28.6623 32.4264 28.2602 31.8487C27.858 31.2709 27.7016 30.5572 27.8253 29.8642ZM25.9825 40.3265C26.1079 39.635 26.5022 39.0213 27.0791 38.6199C27.656 38.2185 28.3685 38.0621 29.0605 38.1849L42.8331 40.6054C43.4878 40.7201 44.0757 41.0761 44.4808 41.603C44.8858 42.13 45.0786 42.7896 45.0211 43.4518C44.9635 44.1139 44.6598 44.7305 44.1699 45.1796C43.6801 45.6288 43.0396 45.878 42.3749 45.8781C42.2191 45.878 42.0636 45.8647 41.9101 45.8382L28.1308 43.4044C27.438 43.2806 26.8227 42.887 26.4199 42.3099C26.0172 41.7328 25.8598 41.0195 25.9825 40.3265Z" fill="#0078DD"/>
                    </svg>
                </div>
                <h3 class="modal-title">Restore Patient Record</h3>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to restore this record?</p>
                <div class="restore-details-list">
                    <ul class="space-y-2 text-sm">
                        <li><i class="fas fa-user"></i> Personal information will be recovered</li>
                        <li><i class="fas fa-notes-medical"></i> Medical history will be recovered</li>
                        <li><i class="fas fa-stethoscope"></i> Consultation notes will be recovered</li>
                    </ul>
                </div>
                <div class="mt-4">
                    <span class="font-medium text-gray-700">Patient : </span>
                    <span id="modalPatientName" class="patient-name-highlight"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeRestoreModal()" class="modal-btn modal-btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmRestoreBtn" class="modal-btn modal-btn-confirm">
                    <i class="fas fa-check"></i> Restore
                </a>
            </div>
        </div>
    </div>

    <div class="w-full px-8 py-10 lg:px-8">
        <!-- Header with Title -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-700 mb-2">Archived Patient Records</h1>
                <p class="text-gray-500 text-lg">Patient records that have been moved to archive. Only restoration is allowed.</p>
            </div>
            <a href="existing_info_patients.php"
                class="flex items-center gap-3 bg-[#3C96E1] hover:bg-blue-600 text-white font-medium px-7 py-4 rounded-full shadow transition mt-4 md:mt-0">
                <i class="fas fa-arrow-left"></i> Back to Patient
            </a>
        </div>

        <!-- SEARCH AND FILTER SECTION -->
        <div class="mb-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-600">Search Archived Records</h2>
            </div>
            <form method="get" class="flex justify-between border-b-2 border-gray-100 pb-6 flex-wrap items-center gap-3">
                <div class="flex gap-6">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-7 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none z-10"></i>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                            placeholder="Search record by name"
                            class="pl-10 py-3 px-16 text-gray-700 rounded-md focus:outline-none border border-[#3C96E1] focus:ring-2 focus:ring-blue-400 focus:border-blue-500" />
                        <?php if (!empty($search)): ?>
                            <a href="deleted_patients.php"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500"
                                title="Clear search"><i class="fas fa-times-circle"></i></a>
                        <?php endif; ?>
                    </div>
                    <button type="submit"
                        class="bg-[#3C96E1] hover:bg-blue-600 text-white font-lg px-6 py-3 rounded-md transition flex items-center gap-2">
                        Search
                    </button>
                </div>

                <div class="flex items-center gap-6 ml-auto">
                    <div style="position:relative;display:inline-block;width:200px;">
                        <select name="sort" class="border border-blue-500 rounded-md py-3 px-6 text-gray-700 pr-10 w-full" style="appearance: none;">
                            <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort - Date</option>
                            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date (Oldest)</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        </select>
                        <span style="position:absolute;right:16px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 8l4 4 4-4" stroke="#3C96E1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    <button type="submit"
                        class="bg-[#3C96E1] hover:bg-blue-600 text-white font-normal text-base px-6 py-3 rounded-md transition flex items-center gap-2">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.6197 4.64346C21.5043 4.37675 21.313 4.14986 21.0696 3.99101C20.8263 3.83216 20.5416 3.74836 20.251 3.75002H3.75095C3.46064 3.7506 3.17673 3.8354 2.93366 3.99416C2.6906 4.15291 2.49883 4.37879 2.38161 4.64438C2.26439 4.90998 2.22677 5.20389 2.2733 5.49045C2.31984 5.77701 2.44853 6.04391 2.64376 6.25877L2.65126 6.26721L9.00095 13.0472V20.25C9.00089 20.5215 9.0745 20.7879 9.21395 21.0208C9.35339 21.2538 9.55344 21.4445 9.79275 21.5727C10.0321 21.7008 10.3017 21.7617 10.5729 21.7486C10.844 21.7356 11.1066 21.6493 11.3325 21.4988L14.3325 19.4981C14.5382 19.3612 14.7068 19.1755 14.8234 18.9576C14.94 18.7398 15.001 18.4965 15.001 18.2494V13.0472L21.3516 6.26721L21.3591 6.25877C21.5564 6.04489 21.6863 5.77764 21.7327 5.49037C21.779 5.2031 21.7397 4.90854 21.6197 4.64346ZM13.7053 12.2419C13.5756 12.3795 13.5026 12.5609 13.501 12.75V18.2494L10.501 20.25V12.75C10.501 12.5596 10.4286 12.3762 10.2985 12.2372L3.75095 5.25002H20.251L13.7053 12.2419Z" fill="white" />
                        </svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <h2 class="text-xl font-semibold text-gray-700">Archived Records</h2>
                    <div class="flex items-center">
                        <span class="text-gray-600 mr-2">Total archived records :</span>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 bg-blue-100 text-blue-600 font-semibold">
                            <?= $totalRows ?? 0 ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($deletedPatients)): ?>
                <div class="flex flex-col items-center justify-center py-16 px-8">
                    <div class="w-20 h-20 flex items-center justify-center mb-4">
                        <svg width="70" height="50" viewBox="0 0 70 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M65 0H5C3.67392 0 2.40215 0.526784 1.46447 1.46447C0.526784 2.40215 0 3.67392 0 5V12.5C0 13.8261 0.526784 15.0979 1.46447 16.0355C2.40215 16.9732 3.67392 17.5 5 17.5V45C5 46.3261 5.52678 47.5979 6.46447 48.5355C7.40215 49.4732 8.67392 50 10 50H60C61.3261 50 62.5979 49.4732 63.5355 48.5355C64.4732 47.5979 65 46.3261 65 45V17.5C66.3261 17.5 67.5979 16.9732 68.5355 16.0355C69.4732 15.0979 70 13.8261 70 12.5V5C70 3.67392 69.4732 2.40215 68.5355 1.46447C67.5979 0.526784 66.3261 0 65 0ZM60 45H10V17.5H60V45ZM65 12.5H5V5H65V12.5ZM25 27.5C25 26.837 25.2634 26.2011 25.7322 25.7322C26.2011 25.2634 26.837 25 27.5 25H42.5C43.163 25 43.7989 25.2634 44.2678 25.7322C44.7366 26.2011 45 26.837 45 27.5C45 28.163 44.7366 28.7989 44.2678 29.2678C43.7989 29.7366 43.163 30 42.5 30H27.5C26.837 30 26.2011 29.7366 25.7322 29.2678C25.2634 28.7989 25 28.163 25 27.5Z" fill="black" fill-opacity="0.3" />
                        </svg>
                    </div>
                    <div class="text-xl font-semibold text-gray-500 mb-6">Your Archive is Empty</div>
                    <div class="text-gray-500 text-base text-lg text-center max-w-md">
                        <?php if (!empty($search)): ?>
                            No archived patients found matching <span class="font-bold">"<?= htmlspecialchars($search) ?>"</span>.
                        <?php else: ?>
                            No deleted patient records found in archive.
                        <?php endif; ?>
                    </div>
                    <a href="deleted_patients.php" class="mt-8 bg-blue-500 hover:bg-blue-600 text-white text-base font-normal px-6 py-3 rounded-lg flex items-center gap-2 transition">
                        <i class="fas fa-search"></i>Clear Search
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Age</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Gender</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Archived On</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Days/Months Left</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($deletedPatients as $patient): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-8 py-4 whitespace-nowrap text-gray-700">
                                        <?= htmlspecialchars($patient['full_name']) ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap text-gray-700">
                                        <?= $patient['age'] ?? 'N/A' ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap text-gray-700">
                                        <?= htmlspecialchars($patient['gender'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap">
                                        <?php
                                            if (!empty($patient['is_registered_user']) && $patient['is_registered_user']) {
                                                echo '<span class="user-badge">Account Access</span>';
                                            } else {
                                                echo '<span class="regular-badge">Regular Patient</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap text-gray-700">
                                        <?= htmlspecialchars($patient['contact'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap text-gray-700">
                                        <?= date('M d, Y', strtotime($patient['archived_date'])) ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap">
                                        <?php
                                            $deletedAt = strtotime($patient['archived_date']);
                                            $autoDeleteAt = strtotime('+5 years', $deletedAt);
                                            $now = time();
                                            if ($now < $autoDeleteAt) {
                                                $diff = $autoDeleteAt - $now;
                                                $monthsLeft = floor($diff / (30 * 24 * 60 * 60));
                                                $daysLeft = floor(($diff % (30 * 24 * 60 * 60)) / (24 * 60 * 60));
                                                echo '<span class="timeleft-badge">' . $monthsLeft . ' months, ' . $daysLeft . ' days left</span>';
                                            } else {
                                                echo '<span class="timeleft-badge">Pending Deletion</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-8 py-4 whitespace-nowrap">
                                        <button onclick="showRestoreModal('<?= htmlspecialchars(addslashes($patient['full_name'])) ?>', '?restore_patient=<?= $patient['original_id'] ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>&page=<?= $page ?>')"
                                            class="bg-green-500 hover:bg-green-600 text-white font-normal text-md py-3 px-6 rounded-full inline-flex items-center gap-2 transition shadow-sm">
                                            <i class="fas fa-undo"></i>Restore
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalRows ?> total records)
                        </div>
                        <div class="pagination-links">
                            <?php if ($page > 1): ?>
                                <a href="?page=1&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>"
                                   class="pagination-link" title="First page">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>"
                                   class="pagination-link pagination-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-link pagination-link-disabled">
                                    <i class="fas fa-angle-double-left"></i>
                                </span>
                                <span class="pagination-link pagination-link-disabled">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, min($page - 2, $totalPages - 4));
                            $endPage = min($totalPages, max($page + 2, 5));
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="pagination-link pagination-link-active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>"
                                       class="pagination-link"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>"
                                   class="pagination-link pagination-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>"
                                   class="pagination-link" title="Last page">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-link pagination-link-disabled">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                                <span class="pagination-link pagination-link-disabled">
                                    <i class="fas fa-angle-double-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('restoreModal');
        const modalPatientName = document.getElementById('modalPatientName');
        const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');

        function showRestoreModal(patientName, restoreUrl) {
            modalPatientName.textContent = patientName;
            confirmRestoreBtn.href = restoreUrl;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRestoreModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRestoreModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeRestoreModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const initialNotificationType = <?= json_encode($notificationType) ?>;
            const initialNotificationMessage = <?= json_encode($notificationMessage) ?>;
            const initialNotificationDuration = <?= (int) $notificationDuration ?>;

            if (initialNotificationType && initialNotificationMessage) {
                showNotification(initialNotificationType, initialNotificationMessage, initialNotificationDuration);
            }
        });

        function showNotification(type, message, duration = 5000) {
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `custom-notification fixed top-6 right-6 z-50 px-6 py-4 rounded-xl shadow-lg border-2 ${type === 'error' ? 'alert-error' :
                type === 'success' ? 'alert-success' :
                    'bg-blue-100 text-blue-800 border-blue-200'
                }`;

            const icon = type === 'error' ? 'fa-exclamation-circle' :
                type === 'success' ? 'fa-check-circle' : 'fa-info-circle';

            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-3 text-xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            const timeoutId = setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, duration);

            notification.style.cursor = 'pointer';
            notification.addEventListener('click', () => {
                clearTimeout(timeoutId);
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            });
        }
    </script>
</body>

</html>