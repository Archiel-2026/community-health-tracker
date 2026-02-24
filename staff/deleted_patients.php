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

// Handle patient restoration - UPDATED to properly restore with all original data AND preserve consultation notes
if (isset($_GET['restore_patient'])) {
    $patientId = $_GET['restore_patient'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get archived patient data including ALL medical info
        require_once __DIR__ . '/../includes/functions.php';
        
        // First try to find in deleted_patients table (hard delete)
        $query = "
            SELECT dp.*
            FROM deleted_patients dp
            WHERE dp.original_id = ?
        ";
        
        $params = [$patientId];
        
        // If staff cannot view all, only allow restoring their own deleted patients
        if (!staff_can_view_all()) {
            $query .= " AND dp.deleted_by = ?";
            $params[] = $_SESSION['user']['id'];
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $archivedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found in deleted_patients, try soft-deleted in sitio1_patients
        if (!$archivedPatient) {
            $query = "
                SELECT p.*, ei.height, ei.weight, ei.temperature, ei.blood_pressure,
                       ei.blood_type, ei.allergies, ei.medical_history, ei.current_medications,
                       ei.family_history, ei.immunization_record, ei.chronic_conditions
                FROM sitio1_patients p
                LEFT JOIN existing_info_patients ei ON p.id = ei.patient_id
                WHERE p.id = ? AND p.deleted_at IS NOT NULL
            ";
            
            $params = [$patientId];
            
            // If staff cannot view all, only allow restoring their own deleted patients
            if (!staff_can_view_all()) {
                $query .= " AND p.added_by = ?";
                $params[] = $_SESSION['user']['id'];
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $archivedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($archivedPatient) {
            // Check if patient already exists in main table (respect shared-mode)
            if (staff_can_view_all()) {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$patientId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND added_by = ? AND deleted_at IS NULL");
                $stmt->execute([$patientId, $_SESSION['user']['id']]);
            }
            $existingPatient = $stmt->fetch();

            if ($existingPatient) {
                $error = 'This patient already exists in the active records!';
            } else {
                // Check if this is from soft-deleted records or hard-deleted archive
                $isFromSoftDelete = !empty($archivedPatient['created_at']) && empty($archivedPatient['deleted_by']);
                
                if ($isFromSoftDelete) {
                    // This is a soft-deleted record - just clear the deleted_at flag
                    $stmt = $pdo->prepare("UPDATE sitio1_patients SET deleted_at = NULL, restored_at = NOW() WHERE id = ?");
                    $stmt->execute([$patientId]);
                } else {
                    // This is a hard-deleted record in deleted_patients table
                    // Get column information from sitio1_patients table
                    $stmt = $pdo->prepare("SHOW COLUMNS FROM sitio1_patients");
                    $stmt->execute();
                    $mainTableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Check if a soft-deleted record with this ID already exists
                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ?");
                    $stmt->execute([$patientId]);
                    $existingSoftDeleted = $stmt->fetch();

                    if ($existingSoftDeleted) {
                        // Update the soft-deleted record instead of inserting
                        $updateColumns = [];
                        $updateValues = [];
                        foreach ($archivedPatient as $column => $value) {
                            if (!in_array($column, $mainTableColumns)) continue;
                            if (in_array($column, ['deleted_by', 'deleted_at', 'id', 'created_at', 'restored_at'])) continue;
                            if ($column === 'original_id') continue;
                            $updateColumns[] = "$column = ?";
                            $updateValues[] = $value;
                        }
                        $updateColumns[] = "restored_at = ?";
                        $updateValues[] = date('Y-m-d H:i:s');
                        $updateColumns[] = "deleted_at = NULL";
                        $updateQuery = "UPDATE sitio1_patients SET ".implode(", ", $updateColumns)." WHERE id = ?";
                        $updateValues[] = $patientId;
                        $stmt = $pdo->prepare($updateQuery);
                        $stmt->execute($updateValues);
                    } else {
                        // Prepare data for restoration
                        $columns = [];
                        $placeholders = [];
                        $values = [];
                        $addedColumns = [];
                        // Ensure all required columns are present
                        $requiredColumns = ['id', 'full_name', 'date_of_birth', 'age', 'gender', 'address', 'contact', 'added_by', 'created_at'];
                        $deletedAtAdded = false;
                        foreach ($mainTableColumns as $col) {
                            if (in_array($col, $addedColumns)) continue;
                            if ($col === 'id') {
                                $columns[] = 'id';
                                $placeholders[] = '?';
                                $values[] = $archivedPatient['original_id'] ?? $patientId;
                                $addedColumns[] = 'id';
                                continue;
                            }
                            if ($col === 'deleted_at') {
                                // Always set deleted_at to NULL for restoration
                                $columns[] = 'deleted_at';
                                $placeholders[] = '?';
                                $values[] = null;
                                $deletedAtAdded = true;
                                $addedColumns[] = 'deleted_at';
                                continue;
                            }
                            if (isset($archivedPatient[$col]) && !in_array($col, $addedColumns)) {
                                $columns[] = $col;
                                $placeholders[] = '?';
                                $values[] = $archivedPatient[$col];
                                $addedColumns[] = $col;
                            } elseif (in_array($col, $requiredColumns) && !in_array($col, $addedColumns)) {
                                // Set default for required columns if missing
                                $columns[] = $col;
                                $placeholders[] = '?';
                                $values[] = ($col === 'created_at') ? date('Y-m-d H:i:s') : '';
                                $addedColumns[] = $col;
                            }
                        }
                        // Add restored timestamp
                        if (!in_array('restored_at', $addedColumns)) {
                            $columns[] = 'restored_at';
                            $placeholders[] = '?';
                            $values[] = date('Y-m-d H:i:s');
                        }
                        $insertQuery = "INSERT INTO sitio1_patients (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
                        $stmt = $pdo->prepare($insertQuery);
                        $stmt->execute($values);
                    }
                    // Check if patient was actually restored
                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ?");
                    $stmt->execute([$patientId]);
                    $restoredPatientRow = $stmt->fetch();
                    if (!$restoredPatientRow) {
                        throw new Exception('Failed to restore patient to sitio1_patients. Cannot proceed with medical info restoration.');
                    }
                }

                $restorationSuccess = true;
                $restorationDetails = [
                    'patient_id' => $patientId,
                    'patient_name' => $archivedPatient['full_name'],
                    'restore_time' => date('Y-m-d H:i:s'),
                    'restored_by' => $_SESSION['user']['full_name'],
                    'restore_type' => $isFromSoftDelete ? 'soft_delete' : 'hard_delete'
                ];

                // IMPORTANT: Consultation notes are automatically preserved because:
                // 1. They were never deleted when patient was archived
                // 2. They remain linked by patient_id
                // 3. When patient is restored with same ID, notes are automatically accessible again

                // Restore medical info if it exists in archive (only for hard-deleted records)
                if (!$isFromSoftDelete) {
                    $medicalFields = ['gender', 'height', 'weight', 'temperature', 'blood_pressure', 'blood_type', 'allergies', 'medical_history', 'current_medications', 'family_history', 'immunization_record', 'chronic_conditions'];
                    $hasMedicalData = false;
                    
                    foreach ($medicalFields as $field) {
                        if (!empty($archivedPatient[$field])) {
                            $hasMedicalData = true;
                            break;
                        }
                    }
                    
                    if ($hasMedicalData) {
                        $stmt = $pdo->prepare("INSERT INTO existing_info_patients 
                            (patient_id, gender, height, weight, temperature, blood_pressure, 
                             blood_type, allergies, medical_history, current_medications, 
                             family_history, immunization_record, chronic_conditions) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                                gender = VALUES(gender),
                                height = VALUES(height),
                                weight = VALUES(weight),
                                temperature = VALUES(temperature),
                                blood_pressure = VALUES(blood_pressure),
                                blood_type = VALUES(blood_type),
                                allergies = VALUES(allergies),
                                medical_history = VALUES(medical_history),
                                current_medications = VALUES(current_medications),
                                family_history = VALUES(family_history),
                                immunization_record = VALUES(immunization_record),
                                chronic_conditions = VALUES(chronic_conditions)");

                        $stmt->execute([
                            $patientId,
                            $archivedPatient['gender'] ?? null,
                            $archivedPatient['height'] ?? null,
                            $archivedPatient['weight'] ?? null,
                            $archivedPatient['temperature'] ?? null,
                            $archivedPatient['blood_pressure'] ?? null,
                            $archivedPatient['blood_type'] ?? null,
                            $archivedPatient['allergies'] ?? null,
                            $archivedPatient['medical_history'] ?? null,
                            $archivedPatient['current_medications'] ?? null,
                            $archivedPatient['family_history'] ?? null,
                            $archivedPatient['immunization_record'] ?? null,
                            $archivedPatient['chronic_conditions'] ?? null
                        ]);

                        $restorationDetails['medical_info_restored'] = true;
                    } else {
                        $restorationDetails['medical_info_restored'] = false;
                    }

                    // Delete from archive
                    $stmt = $pdo->prepare("DELETE FROM deleted_patients WHERE original_id = ?");
                    $stmt->execute([$patientId]);
                }

                // Verify restoration was successful
                if (staff_can_view_all()) {
                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND deleted_at IS NULL");
                    $stmt->execute([$patientId]);
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND added_by = ? AND deleted_at IS NULL");
                    $stmt->execute([$patientId, $_SESSION['user']['id']]);
                }
                $restoredCheck = $stmt->fetch();

                if (!$restoredCheck) {
                    throw new Exception('Restoration verification failed - patient not found in active records');
                }

                $pdo->commit();

                // Log restoration activity
                try {
                    $staff_id = $_SESSION['user']['id'] ?? null;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    
                    $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'restore_patient', ?, ?, ?, ?, NOW())");
                    $stmtLog->execute([$staff_id, $patientId, json_encode($restorationDetails), $ip, $ua]);
                } catch (Exception $e) {
                    error_log('Staff activity log error (restore_patient): ' . $e->getMessage());
                }

                $_SESSION['success_message'] = 'Patient record "' . $archivedPatient['full_name'] . '" restored successfully! All personal details, medical information, and consultation notes have been recovered.';
                $_SESSION['restore_details'] = $restorationDetails;
                header('Location: deleted_patients.php');
                exit();
            }
        } else {
            $error = 'Archived patient not found!';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error restoring patient record: ' . $e->getMessage();
        error_log('Restoration Error: ' . $e->getMessage());
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Restoration failed: ' . $e->getMessage();
        error_log('Restoration Exception: ' . $e->getMessage());
    }
}

// REMOVED: Permanent deletion functionality

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

    // Query for hard-deleted records from deleted_patients table
    $hardDeleteQuery = "SELECT 
                          'hard_delete' as delete_type,
                          d.original_id,
                          d.full_name,
                          d.date_of_birth,
                          d.age,
                          d.gender,
                          d.address,
                          d.contact,
                          d.last_checkup,
                          d.added_by,
                          d.user_id,
                          d.deleted_by,
                          d.deleted_at as archived_date,
                          NULL as sitio,
                          NULL as civil_status,
                          NULL as occupation,
                          NULL as unique_number,
                          NULL as user_email,
                          CASE WHEN d.user_id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user
                      FROM deleted_patients d
                      WHERE $whereClause1";
    
    // Query for soft-deleted records from sitio1_patients table
    $softDeleteQuery = "SELECT 
                          'soft_delete' as delete_type,
                          p.id as original_id,
                          p.full_name,
                          p.date_of_birth,
                          p.age,
                          p.gender,
                          p.address,
                          p.contact,
                          p.last_checkup,
                          p.added_by,
                          p.user_id,
                          p.added_by as deleted_by,
                          p.deleted_at as archived_date,
                          p.sitio,
                          p.civil_status,
                          p.occupation,
                          u.unique_number,
                          u.email as user_email,
                          CASE WHEN p.user_id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user
                      FROM sitio1_patients p
                      LEFT JOIN sitio1_users u ON p.user_id = u.id
                      WHERE $whereClause2";
    
    // Combine both queries (no ORDER BY here)
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

    $perPage = 5;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM (($hardDeleteQuery) UNION ALL ($softDeleteQuery)) as all_patients";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $perPage);

    // Add ORDER BY, LIMIT, and OFFSET outside the UNION ALL
    $paginatedQuery = $combinedQuery . $orderBy . " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($paginatedQuery);
    $stmt->execute($params);
    $deletedPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching deleted patients: " . $e->getMessage();
    error_log("Deleted patients query error: " . $e->getMessage());
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
            background-color: #e0e7ff;
            color: #3730a3;
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
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
        
        /* Table styling */
        .patient-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .patient-table th, .patient-table td { 
            padding: 12px 15px; 
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
        .custom-notification { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { 
            from { transform: translateX(100%); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
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
            
            .flex.items-center.gap-2 > .sort-select-wrapper {
                flex: 1;
            }
            
            .search-section .flex-wrap > div {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header with Title -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1">Archived Patient Records</h1>
                <p class="text-gray-500 text-base">Patient records that have been moved to archive. Only restoration is allowed.</p>
            </div>
            <a href="existing_info_patients.php" class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-medium px-5 py-2 rounded-lg shadow transition mt-4 md:mt-0">
                <i class="fas fa-arrow-left"></i> Back to Patient
            </a>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div id="successMessage" class="alert-success px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div id="successMessage" class="alert-success px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <!-- SEARCH AND FILTER SECTION -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <form method="get" class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search record by name" class="w-full border border-gray-300 rounded-lg py-2 px-4 pr-10 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                        <?php if (!empty($search)): ?>
                            <a href="deleted_patients.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500" title="Clear search"><i class="fas fa-times-circle"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-5 py-2 rounded-lg transition flex items-center gap-2"><i class="fas fa-search"></i>Search</button>
                <div class="flex items-center gap-2 ml-auto">
                    <select name="sort" class="border border-gray-300 rounded-lg py-2 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort - Date</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date (Oldest)</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    </select>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-5 py-2 rounded-lg transition flex items-center gap-2"><i class="fas fa-filter"></i>Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Main Content Card -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800">Archived Records</h2>
                    <span class="inline-flex items-center justify-center ml-2 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold"> <?= $totalRows ?? 0 ?> </span>
                </div>
            </div>
            <?php if (empty($deletedPatients)): ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="w-20 h-20 flex items-center justify-center rounded-full border-2 border-blue-100 bg-white mb-4">
                        <i class="fas fa-archive text-blue-400 text-4xl"></i>
                    </div>
                    <div class="text-xl font-bold text-gray-700 mb-1">Your Archive is Empty</div>
                    <div class="text-gray-500 text-base text-center">
                        <?php if (!empty($search)): ?>
                            No archived patients found matching <span class="font-bold">"<?= htmlspecialchars($search) ?>"</span>.
                        <?php else: ?>
                            No deleted patient records found in archive.
                        <?php endif; ?>
                    </div>
                    <a href="deleted_patients.php" class="mt-8 bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg flex items-center gap-2 transition"><i class="fas fa-search"></i>Click Search</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Age</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Gender</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Type of Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Deleted On</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($deletedPatients as $patient): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium"> <?= htmlspecialchars($patient['full_name']) ?> </td>
                                    <td class="px-6 py-4 whitespace-nowrap"> <?= $patient['age'] ?? 'N/A' ?> </td>
                                    <td class="px-6 py-4 whitespace-nowrap"> <?= htmlspecialchars($patient['gender'] ?? 'N/A') ?> </td>
                                    <td class="px-6 py-4 whitespace-nowrap">Regular</td>
                                    <td class="px-6 py-4 whitespace-nowrap"> <?= htmlspecialchars($patient['contact'] ?? 'N/A') ?> </td>
                                    <td class="px-6 py-4 whitespace-nowrap"> <?= date('M d, Y', strtotime($patient['archived_date'])) ?> </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="?restore_patient=<?= $patient['original_id'] ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>&page=<?= $page ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold text-base py-3 px-6 rounded-full inline-flex items-center gap-2 transition shadow-md" style="width:auto;min-width:0;" onclick="return confirm('Are you sure you want to restore this patient record?\n\nThis will recover all personal information, medical history, and consultation notes for <?= htmlspecialchars(addslashes($patient['full_name'])) ?>.')">
                                            <i class="fas fa-undo text-lg"></i>Restore
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm text-gray-600">
                        Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRows ?> records)
                    </div>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 text-sm transition"><i class="fas fa-chevron-left"></i>Previous</a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sort ?? '') ?>" 
                               class="btn-primary px-4 py-2 text-sm">
                                <span>Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Footer Information -->
        <div class="text-center text-sm text-gray-500 mt-6">
            <i class="fas fa-shield-alt text-primary mr-1"></i>
            Archived patient records are retained for up to 60 months (5 years) for data recovery purposes. After this period, records will be automatically and permanently deleted. Restoring a patient will recover all associated consultation notes and medical information, if still within the retention period.
        </div>
    </div>

    <script>
        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var successMessages = document.querySelectorAll('#successMessage');
                var errorMessages = document.querySelectorAll('.alert-error');
                
                successMessages.forEach(function(message) {
                    message.style.transition = 'opacity 0.5s ease';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 500);
                });
                
                errorMessages.forEach(function(message) {
                    message.style.transition = 'opacity 0.5s ease';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 500);
                });
            }, 3000);
        });
        
        // Rotating chevron icon for sort dropdown - FIXED toggle behavior
        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('sortSelect');
            const wrapper = document.getElementById('sortSelectWrapper');
            
            if (sortSelect && wrapper) {
                let isOpen = false;
                
                // Toggle dropdown when select is clicked
                sortSelect.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    if (isOpen) {
                        // If open, close it - chevron points down
                        wrapper.classList.remove('sort-select-open');
                        this.blur(); // Remove focus
                        isOpen = false;
                    } else {
                        // If closed, open it - chevron points up
                        wrapper.classList.add('sort-select-open');
                        isOpen = true;
                    }
                });
                
                // When an option is selected
                sortSelect.addEventListener('change', function() {
                    wrapper.classList.remove('sort-select-open');
                    isOpen = false;
                });
                
                // When select loses focus
                sortSelect.addEventListener('blur', function() {
                    wrapper.classList.remove('sort-select-open');
                    isOpen = false;
                });
                
                // Handle click outside to close
                document.addEventListener('click', function(event) {
                    if (!wrapper.contains(event.target) && isOpen) {
                        wrapper.classList.remove('sort-select-open');
                        isOpen = false;
                    }
                });
                
                // Handle escape key
                sortSelect.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        wrapper.classList.remove('sort-select-open');
                        isOpen = false;
                    }
                });
            }
        });
        
        // Show notification function
        function showNotification(type, message) {
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());
            
            const notification = document.createElement('div');
            notification.className = `custom-notification fixed top-6 right-6 z-50 px-6 py-4 rounded-xl shadow-lg border-2 ${
                type === 'error' ? 'alert-error' :
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
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
    </script>
</body>
</html>