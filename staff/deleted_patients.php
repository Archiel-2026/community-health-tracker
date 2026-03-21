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

// Handle patient restoration - FIXED foreign key constraint issues
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

                    // FIX: Check if user_id exists in sitio1_users before restoring
                    $originalUserId = $archivedPatient['user_id'] ?? null;
                    $validUserId = null;
                    
                    if (!empty($originalUserId)) {
                        $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE id = ?");
                        $stmt->execute([$originalUserId]);
                        $userExists = $stmt->fetch();
                        
                        if ($userExists) {
                            $validUserId = $originalUserId;
                        } else {
                            // User doesn't exist, set to NULL (as per foreign key constraint ON DELETE SET NULL)
                            $validUserId = null;
                            error_log("User ID {$originalUserId} not found during patient restoration. Setting to NULL.");
                        }
                    }

                    // FIX: Check if added_by exists in sitio1_staff before restoring
                    $originalAddedBy = $archivedPatient['added_by'] ?? null;
                    $validAddedBy = null;

                    if (!empty($originalAddedBy)) {
                        $stmt = $pdo->prepare("SELECT id FROM sitio1_staff WHERE id = ?");
                        $stmt->execute([$originalAddedBy]);
                        $staffExists = $stmt->fetch();
                        
                        if ($staffExists) {
                            $validAddedBy = $originalAddedBy;
                        } else {
                            // Staff doesn't exist, set to current logged-in staff
                            $validAddedBy = $_SESSION['user']['id'] ?? null;
                            error_log("Staff ID {$originalAddedBy} not found during patient restoration. Using current staff ID: {$validAddedBy}");
                        }
                    } else {
                        // No original added_by, use current staff
                        $validAddedBy = $_SESSION['user']['id'] ?? null;
                    }

                    if ($existingSoftDeleted) {
                        // Update the soft-deleted record instead of inserting
                        $updateColumns = [];
                        $updateValues = [];
                        foreach ($archivedPatient as $column => $value) {
                            if (!in_array($column, $mainTableColumns))
                                continue;
                            if (in_array($column, ['deleted_by', 'deleted_at', 'id', 'created_at', 'restored_at', 'user_id', 'added_by']))
                                continue;
                            if ($column === 'original_id')
                                continue;
                            $updateColumns[] = "$column = ?";
                            $updateValues[] = $value;
                        }
                        
                        // Add user_id separately with validated value
                        $updateColumns[] = "user_id = ?";
                        $updateValues[] = $validUserId;
                        
                        // Add added_by separately with validated value
                        $updateColumns[] = "added_by = ?";
                        $updateValues[] = $validAddedBy;
                        
                        $updateColumns[] = "restored_at = ?";
                        $updateValues[] = date('Y-m-d H:i:s');
                        $updateColumns[] = "deleted_at = NULL";
                        
                        $updateQuery = "UPDATE sitio1_patients SET " . implode(", ", $updateColumns) . " WHERE id = ?";
                        $updateValues[] = $patientId;
                        
                        $stmt = $pdo->prepare($updateQuery);
                        $stmt->execute($updateValues);
                    } else {
                        // Prepare data for restoration - with foreign key handling
                        $columns = [];
                        $placeholders = [];
                        $values = [];
                        $addedColumns = [];
                        
                        // Ensure all required columns are present
                        $requiredColumns = [
                            'id', 'unique_id', 'qr_code', 'qr_verified', 'verification_date',
                            'full_name', 'date_of_birth', 'age', 'gender', 'address', 
                            'sitio', 'civil_status', 'occupation', 'contact', 'last_checkup',
                            'medical_history_summary', 'status', 'added_by', 
                            'created_at', 'updated_at', 'restored_at'
                        ];
                        
                        foreach ($mainTableColumns as $col) {
                            if (in_array($col, $addedColumns))
                                continue;
                            
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
                                $addedColumns[] = 'deleted_at';
                                continue;
                            }
                            
                            // Handle user_id separately with validation
                            if ($col === 'user_id') {
                                $columns[] = 'user_id';
                                $placeholders[] = '?';
                                $values[] = $validUserId;
                                $addedColumns[] = 'user_id';
                                continue;
                            }
                            
                            // Handle added_by separately with validation
                            if ($col === 'added_by') {
                                $columns[] = 'added_by';
                                $placeholders[] = '?';
                                $values[] = $validAddedBy;
                                $addedColumns[] = 'added_by';
                                continue;
                            }
                            
                            // Check if value exists in archived patient data
                            if (isset($archivedPatient[$col]) && $archivedPatient[$col] !== '' && !in_array($col, $addedColumns)) {
                                $columns[] = $col;
                                $placeholders[] = '?';
                                $values[] = $archivedPatient[$col];
                                $addedColumns[] = $col;
                            } 
                            // Set default for required columns if missing
                            elseif (in_array($col, $requiredColumns) && !in_array($col, $addedColumns)) {
                                $defaultValue = '';
                                if ($col === 'created_at' || $col === 'restored_at' || $col === 'updated_at') {
                                    $defaultValue = date('Y-m-d H:i:s');
                                } elseif ($col === 'status') {
                                    $defaultValue = 'active';
                                } elseif ($col === 'unique_id') {
                                    $defaultValue = 'RESTORED-' . uniqid();
                                } elseif ($col === 'qr_code') {
                                    $defaultValue = 'QR-' . uniqid();
                                } elseif ($col === 'qr_verified') {
                                    $defaultValue = 0;
                                }
                                
                                $columns[] = $col;
                                $placeholders[] = '?';
                                $values[] = $defaultValue;
                                $addedColumns[] = $col;
                            }
                        }
                        
                        // Add restored timestamp if not already added
                        if (!in_array('restored_at', $addedColumns)) {
                            $columns[] = 'restored_at';
                            $placeholders[] = '?';
                            $values[] = date('Y-m-d H:i:s');
                        }

                        // Add updated_at timestamp
                        if (!in_array('updated_at', $addedColumns)) {
                            $columns[] = 'updated_at';
                            $placeholders[] = '?';
                            $values[] = date('Y-m-d H:i:s');
                        }

                        $insertQuery = "INSERT INTO sitio1_patients (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
                        $stmt = $pdo->prepare($insertQuery);
                        $stmt->execute($values);
                    }
                    
                    // Verify patient was restored to sitio1_patients
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
                    'restored_by' => $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? 'Unknown',
                    'restore_type' => $isFromSoftDelete ? 'soft_delete' : 'hard_delete',
                    'user_id_handled' => isset($validUserId) ? ($validUserId ? 'preserved' : 'set_to_null') : 'not_applicable',
                    'added_by_handled' => isset($validAddedBy) ? ($validAddedBy ? 'preserved' : 'set_to_current') : 'set_to_current'
                ];

                // Check consultation notes
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as note_count FROM consultation_notes WHERE patient_id = ?");
                    $stmt->execute([$patientId]);
                    $noteCount = $stmt->fetch(PDO::FETCH_ASSOC)['note_count'];
                    $restorationDetails['consultation_notes_restored'] = (int)$noteCount;
                } catch (Exception $e) {
                    error_log('Error checking consultation notes during restoration: ' . $e->getMessage());
                    $restorationDetails['consultation_notes_restored'] = 0;
                }

                // Restore medical info if it exists in archive (only for hard-deleted records)
                if (!$isFromSoftDelete) {
                    // First check if medical info exists in existing_info_patients for this patient
                    $stmt = $pdo->prepare("SELECT * FROM existing_info_patients WHERE patient_id = ?");
                    $stmt->execute([$patientId]);
                    $existingMedicalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existingMedicalInfo) {
                        // No medical info exists, restore from archived patient data
                        $medicalFields = [
                            'gender', 'height', 'weight', 'temperature', 'blood_pressure', 
                            'blood_type', 'allergies', 'medical_history', 'current_medications', 
                            'family_history', 'immunization_record', 'chronic_conditions'
                        ];
                        
                        $hasMedicalData = false;
                        $medicalData = [];
                        
                        foreach ($medicalFields as $field) {
                            if (!empty($archivedPatient[$field])) {
                                $hasMedicalData = true;
                                $medicalData[$field] = $archivedPatient[$field];
                            } else {
                                $medicalData[$field] = null;
                            }
                        }

                        if ($hasMedicalData) {
                            // Use gender from archived patient if available, otherwise from main patient data
                            $gender = $medicalData['gender'] ?? $archivedPatient['gender'] ?? null;
                            
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
                                $gender,
                                $medicalData['height'],
                                $medicalData['weight'],
                                $medicalData['temperature'],
                                $medicalData['blood_pressure'],
                                $medicalData['blood_type'],
                                $medicalData['allergies'],
                                $medicalData['medical_history'],
                                $medicalData['current_medications'],
                                $medicalData['family_history'],
                                $medicalData['immunization_record'],
                                $medicalData['chronic_conditions']
                            ]);

                            $restorationDetails['medical_info_restored'] = true;
                            $restorationDetails['medical_fields_restored'] = array_keys(array_filter($medicalData));
                        } else {
                            $restorationDetails['medical_info_restored'] = false;
                            $restorationDetails['medical_fields_restored'] = [];
                        }
                    } else {
                        // Medical info already exists in existing_info_patients
                        $restorationDetails['medical_info_restored'] = true;
                        $restorationDetails['medical_info_source'] = 'existing_info_patients';
                    }

                    // Delete from archive after successful restoration
                    $stmt = $pdo->prepare("DELETE FROM deleted_patients WHERE original_id = ?");
                    $stmt->execute([$patientId]);
                } else {
                    // For soft-deleted records, check medical info in existing_info_patients
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM existing_info_patients WHERE patient_id = ?");
                    $stmt->execute([$patientId]);
                    $medicalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    $restorationDetails['medical_info_restored'] = ($medicalCount > 0);
                    
                    // Check consultation notes for soft-deleted records
                    $stmt = $pdo->prepare("SELECT COUNT(*) as note_count FROM consultation_notes WHERE patient_id = ?");
                    $stmt->execute([$patientId]);
                    $noteCount = $stmt->fetch(PDO::FETCH_ASSOC)['note_count'];
                    $restorationDetails['consultation_notes_restored'] = (int)$noteCount;
                }

                // Final verification
                $verificationDetails = [];
                
                // Verify patient record
                if (staff_can_view_all()) {
                    $stmt = $pdo->prepare("SELECT id, full_name, deleted_at, user_id, added_by FROM sitio1_patients WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT id, full_name, deleted_at, user_id, added_by FROM sitio1_patients WHERE id = ? AND added_by = ?");
                    $stmt->execute([$patientId, $_SESSION['user']['id']]);
                }
                $stmt->execute([$patientId]);
                $restoredPatient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$restoredPatient || $restoredPatient['deleted_at'] !== null) {
                    throw new Exception('Restoration verification failed - patient not found in active records or still marked as deleted');
                }
                $verificationDetails['patient_record'] = 'verified';
                $verificationDetails['user_id_final'] = $restoredPatient['user_id'];
                $verificationDetails['added_by_final'] = $restoredPatient['added_by'];
                
                // Verify medical info
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM existing_info_patients WHERE patient_id = ?");
                $stmt->execute([$patientId]);
                $hasMedicalInfo = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                $verificationDetails['medical_info'] = $hasMedicalInfo ? 'verified' : 'not_found';
                
                // Verify consultation notes
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM consultation_notes WHERE patient_id = ?");
                $stmt->execute([$patientId]);
                $noteCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $verificationDetails['consultation_notes_count'] = $noteCount;
                $verificationDetails['consultation_notes'] = $noteCount > 0 ? 'verified' : 'none_found';
                
                $restorationDetails['verification'] = $verificationDetails;

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

                // Prepare detailed success message
                $successMessage = 'Patient record "' . $archivedPatient['full_name'] . '" restored successfully! ';
                $successMessage .= 'Personal information recovered. ';
                $successMessage .= $verificationDetails['medical_info'] === 'verified' ? 'Medical history recovered. ' : 'No medical history found. ';
                $successMessage .= $noteCount > 0 ? $noteCount . ' consultation note(s) recovered.' : 'No consultation notes found.';
                
                $_SESSION['success_message'] = $successMessage;
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
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
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
    background-color: rgba(60, 150, 225, 0.2); /* 0.2 = 20% opacity */
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
    <!-- Restore Confirmation Modal - Updated with detailed restore information -->
    <div id="restoreModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-icon">
                    <svg width="87" height="87" viewBox="0 0 87 87" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2587_13510)">
<path d="M74.9448 23.8565L26.5607 10.0608C25.3971 9.72898 24.189 10.401 23.8572 11.5646L10.0615 59.9487C9.72972 61.1123 10.4018 62.3204 11.5653 62.6522L59.9495 76.4479C61.1131 76.7797 62.3212 76.1077 62.653 74.9441L76.4487 26.5599C76.7805 25.3964 76.1084 24.1882 74.9448 23.8565ZM58.6695 70.9649L15.5445 58.6688L27.8407 15.5438L70.9657 27.8399L58.6695 70.9649ZM44.0397 35.5167L56.1357 38.9656C56.4249 39.0481 56.7291 38.8789 56.8116 38.5896L57.7113 35.4341C57.7938 35.1449 57.6246 34.8407 57.3353 34.7583L45.2393 31.3093C44.95 31.2269 44.6459 31.396 44.5634 31.6853L43.6637 34.8408C43.5812 35.13 43.7504 35.4342 44.0397 35.5167ZM41.3405 44.9831L53.4365 48.4321C53.7258 48.5145 54.0299 48.3453 54.1124 48.0561L55.0121 44.9006C55.0946 44.6113 54.9254 44.3072 54.6362 44.2247L42.5401 40.7758C42.2509 40.6933 41.9467 40.8625 41.8642 41.1518L40.9645 44.3073C40.8821 44.5965 41.0512 44.9006 41.3405 44.9831ZM38.6413 54.4496L50.7374 57.8985C51.0266 57.981 51.3308 57.8118 51.4132 57.5226L52.3129 54.3671C52.3954 54.0778 52.2262 53.7737 51.937 53.6912L39.841 50.2423C39.5517 50.1598 39.2476 50.329 39.1651 50.6182L38.2654 53.7737C38.1829 54.063 38.3521 54.3671 38.6413 54.4496ZM34.6471 30.5639C34.4482 31.2613 34.5346 32.0091 34.8871 32.6429C35.2396 33.2766 35.8295 33.7444 36.5269 33.9432C37.2243 34.1421 37.9721 34.0557 38.6059 33.7032C39.2396 33.3507 39.7074 32.7608 39.9062 32.0634C40.1051 31.366 40.0187 30.6182 39.6662 29.9844C39.3137 29.3507 38.7238 28.8829 38.0264 28.6841C37.329 28.4852 36.5812 28.5716 35.9474 28.9241C35.3137 29.2766 34.8459 29.8665 34.6471 30.5639ZM31.9479 40.0303C31.7491 40.7278 31.8354 41.4756 32.1879 42.1093C32.5405 42.7431 33.1303 43.2108 33.8277 43.4097C34.5251 43.6085 35.273 43.5222 35.9067 43.1697C36.5405 42.8171 37.0082 42.2273 37.2071 41.5299C37.4059 40.8325 37.3196 40.0846 36.9671 39.4509C36.6145 38.8171 36.0247 38.3494 35.3273 38.1505C34.6299 37.9517 33.882 38.038 33.2483 38.3906C32.6145 38.7431 32.1468 39.3329 31.9479 40.0303ZM29.2488 49.4968C29.0499 50.1942 29.1362 50.9421 29.4888 51.5758C29.8413 52.2096 30.4312 52.6773 31.1286 52.8761C31.826 53.075 32.5738 52.9887 33.2076 52.6361C33.8413 52.2836 34.3091 51.6937 34.5079 50.9963C34.7068 50.2989 34.6204 49.5511 34.2679 48.9174C33.9154 48.2836 33.3255 47.8159 32.6281 47.617C31.9307 47.4182 31.1829 47.5045 30.5491 47.857C29.9154 48.2096 29.4476 48.7994 29.2488 49.4968Z" fill="#3C96E1"/>
</g>
<defs>
<clipPath id="clip0_2587_13510">
<rect width="70" height="70" fill="white" transform="translate(19.1953) rotate(15.9144)"/>
</clipPath>
</defs>
</svg>





                </div>
                <h3 class="modal-title">Restore Patient Record</h3>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to restore this record?</p>
                <div class="restore-details-list">
                    <ul class="space-y-2 text-sm">
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.125 16.875C13.125 17.0975 13.059 17.315 12.9354 17.5C12.8118 17.685 12.6361 17.8292 12.4305 17.9144C12.225 17.9995 11.9988 18.0218 11.7805 17.9784C11.5623 17.935 11.3618 17.8278 11.2045 17.6705C11.0472 17.5132 10.94 17.3127 10.8966 17.0945C10.8532 16.8762 10.8755 16.65 10.9606 16.4445C11.0458 16.2389 11.19 16.0632 11.375 15.9396C11.56 15.816 11.7775 15.75 12 15.75C12.2984 15.75 12.5845 15.8685 12.7955 16.0795C13.0065 16.2905 13.125 16.5766 13.125 16.875ZM12 6.75C9.93188 6.75 8.25 8.26406 8.25 10.125V10.5C8.25 10.6989 8.32902 10.8897 8.46967 11.0303C8.61033 11.171 8.80109 11.25 9 11.25C9.19892 11.25 9.38968 11.171 9.53033 11.0303C9.67099 10.8897 9.75 10.6989 9.75 10.5V10.125C9.75 9.09375 10.7597 8.25 12 8.25C13.2403 8.25 14.25 9.09375 14.25 10.125C14.25 11.1562 13.2403 12 12 12C11.8011 12 11.6103 12.079 11.4697 12.2197C11.329 12.3603 11.25 12.5511 11.25 12.75V13.5C11.25 13.6989 11.329 13.8897 11.4697 14.0303C11.6103 14.171 11.8011 14.25 12 14.25C12.1989 14.25 12.3897 14.171 12.5303 14.0303C12.671 13.8897 12.75 13.6989 12.75 13.5V13.4325C14.46 13.1184 15.75 11.7544 15.75 10.125C15.75 8.26406 14.0681 6.75 12 6.75ZM21.75 12C21.75 13.9284 21.1782 15.8134 20.1068 17.4168C19.0355 19.0202 17.5127 20.2699 15.7312 21.0078C13.9496 21.7458 11.9892 21.9389 10.0979 21.5627C8.20656 21.1865 6.46928 20.2579 5.10571 18.8943C3.74215 17.5307 2.81355 15.7934 2.43735 13.9021C2.06114 12.0108 2.25422 10.0504 2.99218 8.26884C3.73013 6.48726 4.97982 4.96452 6.58319 3.89317C8.18657 2.82183 10.0716 2.25 12 2.25C14.585 2.25273 17.0634 3.28084 18.8913 5.10872C20.7192 6.93661 21.7473 9.41498 21.75 12ZM20.25 12C20.25 10.3683 19.7662 8.77325 18.8596 7.41655C17.9531 6.05984 16.6646 5.00242 15.1571 4.37799C13.6497 3.75357 11.9909 3.59019 10.3905 3.90852C8.79017 4.22685 7.32016 5.01259 6.16637 6.16637C5.01259 7.32015 4.22685 8.79016 3.90853 10.3905C3.5902 11.9908 3.75358 13.6496 4.378 15.1571C5.00242 16.6646 6.05984 17.9531 7.41655 18.8596C8.77326 19.7661 10.3683 20.25 12 20.25C14.1873 20.2475 16.2843 19.3775 17.8309 17.8309C19.3775 16.2843 20.2475 14.1873 20.25 12Z" fill="#3C96E1"/>
</svg>
                            <span>Personal information will be recovered</span>
                        </li>
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M17.25 3H6.75C6.35218 3 5.97064 3.15804 5.68934 3.43934C5.40804 3.72064 5.25 4.10218 5.25 4.5V21C5.25007 21.1338 5.28595 21.2652 5.35393 21.3805C5.42191 21.4958 5.5195 21.5908 5.63659 21.6557C5.75367 21.7206 5.88598 21.7529 6.01978 21.7494C6.15358 21.7458 6.284 21.7066 6.3975 21.6356L12 18.1341L17.6034 21.6356C17.7169 21.7063 17.8472 21.7454 17.9809 21.7488C18.1146 21.7522 18.2467 21.7198 18.3636 21.655C18.4806 21.5902 18.5781 21.4953 18.646 21.3801C18.7139 21.2649 18.7498 21.1337 18.75 21V4.5C18.75 4.10218 18.592 3.72064 18.3107 3.43934C18.0294 3.15804 17.6478 3 17.25 3ZM17.25 4.5V15.1472L12.3966 12.1144C12.2774 12.0399 12.1396 12.0004 11.9991 12.0004C11.8585 12.0004 11.7208 12.0399 11.6016 12.1144L6.75 15.1462V4.5H17.25ZM12.3966 16.6144C12.2774 16.5399 12.1396 16.5004 11.9991 16.5004C11.8585 16.5004 11.7208 16.5399 11.6016 16.6144L6.75 19.6472V16.9153L12 13.6341L17.25 16.9153V19.6472L12.3966 16.6144Z" fill="#3C96E1"/>
</svg>
                            <span>Medical history will be recovered</span>
                        </li>
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8.25 9C8.25 8.80109 8.32902 8.61032 8.46967 8.46967C8.61032 8.32902 8.80109 8.25 9 8.25H15C15.1989 8.25 15.3897 8.32902 15.5303 8.46967C15.671 8.61032 15.75 8.80109 15.75 9C15.75 9.19891 15.671 9.38968 15.5303 9.53033C15.3897 9.67098 15.1989 9.75 15 9.75H9C8.80109 9.75 8.61032 9.67098 8.46967 9.53033C8.32902 9.38968 8.25 9.19891 8.25 9ZM9 12.75H15C15.1989 12.75 15.3897 12.671 15.5303 12.5303C15.671 12.3897 15.75 12.1989 15.75 12C15.75 11.8011 15.671 11.6103 15.5303 11.4697C15.3897 11.329 15.1989 11.25 15 11.25H9C8.80109 11.25 8.61032 11.329 8.46967 11.4697C8.32902 11.6103 8.25 11.8011 8.25 12C8.25 12.1989 8.32902 12.3897 8.46967 12.5303C8.61032 12.671 8.80109 12.75 9 12.75ZM12 14.25H9C8.80109 14.25 8.61032 14.329 8.46967 14.4697C8.32902 14.6103 8.25 14.8011 8.25 15C8.25 15.1989 8.32902 15.3897 8.46967 15.5303C8.61032 15.671 8.80109 15.75 9 15.75H12C12.1989 15.75 12.3897 15.671 12.5303 15.5303C12.671 15.3897 12.75 15.1989 12.75 15C12.75 14.8011 12.671 14.6103 12.5303 14.4697C12.3897 14.329 12.1989 14.25 12 14.25ZM21 4.5V14.6897C21.0006 14.8867 20.9621 15.082 20.8866 15.264C20.8111 15.446 20.7002 15.6112 20.5603 15.75L15.75 20.5603C15.6112 20.7002 15.446 20.8111 15.264 20.8866C15.082 20.9621 14.8867 21.0006 14.6897 21H4.5C4.10218 21 3.72064 20.842 3.43934 20.5607C3.15804 20.2794 3 19.8978 3 19.5V4.5C3 4.10218 3.15804 3.72064 3.43934 3.43934C3.72064 3.15804 4.10218 3 4.5 3H19.5C19.8978 3 20.2794 3.15804 20.5607 3.43934C20.842 3.72064 21 4.10218 21 4.5ZM4.5 19.5H14.25V15C14.25 14.8011 14.329 14.6103 14.4697 14.4697C14.6103 14.329 14.8011 14.25 15 14.25H19.5V4.5H4.5V19.5ZM15.75 15.75V18.4406L18.4397 15.75H15.75Z" fill="#3C96E1"/>
</svg>
                            <span>Consultation notes will be recovered</span>
                        </li>
                    </ul>
                </div>
                <div class="mt-4">
                    <span class="font-medium text-gray-700">Resident : </span>
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
                <p class="text-gray-500 text-lg">Patient records that have been moved to archive. Only restoration is
                    allowed.</p>
            </div>
            <a href="existing_info_patients.php"
                class="flex items-center gap-3 bg-[#3C96E1] hover:bg-blue-600 text-white font-medium px-7 py-4 rounded-full shadow transition mt-4 md:mt-0">
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
        <div class="mb-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-600">Search Archived Records</h2>
            </div>
            <form method="get"
                class="flex justify-between border-b-2 border-gray-100 pb-6 flex-wrap items-center gap-3">
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
                        <select name="sort"
                            class="border border-blue-500 rounded-md py-3 px-6 text-gray-700 pr-10 w-full" style="appearance: none;">
                            <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort - Date</option>
                            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date (Oldest)</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        </select>
                        <span style="position:absolute;right:16px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <!-- Chevron Down SVG Icon -->
                            <svg width="20" height="20" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 8l4 4 4-4" stroke="#3C96E1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    <button type="submit"
                        class="bg-[#3C96E1] hover:bg-blue-600 text-white font-normal text-base px-6 py-3 rounded-md transition flex items-center gap-2">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21.6197 4.64346C21.5043 4.37675 21.313 4.14986 21.0696 3.99101C20.8263 3.83216 20.5416 3.74836 20.251 3.75002H3.75095C3.46064 3.7506 3.17673 3.8354 2.93366 3.99416C2.6906 4.15291 2.49883 4.37879 2.38161 4.64438C2.26439 4.90998 2.22677 5.20389 2.2733 5.49045C2.31984 5.77701 2.44853 6.04391 2.64376 6.25877L2.65126 6.26721L9.00095 13.0472V20.25C9.00089 20.5215 9.0745 20.7879 9.21395 21.0208C9.35339 21.2538 9.55344 21.4445 9.79275 21.5727C10.0321 21.7008 10.3017 21.7617 10.5729 21.7486C10.844 21.7356 11.1066 21.6493 11.3325 21.4988L14.3325 19.4981C14.5382 19.3612 14.7068 19.1755 14.8234 18.9576C14.94 18.7398 15.001 18.4965 15.001 18.2494V13.0472L21.3516 6.26721L21.3591 6.25877C21.5564 6.04489 21.6863 5.77764 21.7327 5.49037C21.779 5.2031 21.7397 4.90854 21.6197 4.64346ZM13.7053 12.2419C13.5756 12.3795 13.5026 12.5609 13.501 12.75V18.2494L10.501 20.25V12.75C10.501 12.5596 10.4286 12.3762 10.2985 12.2372L3.75095 5.25002H20.251L13.7053 12.2419Z"
                                fill="white" />
                        </svg>
                        Filter</button>
                </div>
            </form>
        </div>

        <!-- Main Content Card with Consistent Padding -->
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
                            <path
                                d="M65 0H5C3.67392 0 2.40215 0.526784 1.46447 1.46447C0.526784 2.40215 0 3.67392 0 5V12.5C0 13.8261 0.526784 15.0979 1.46447 16.0355C2.40215 16.9732 3.67392 17.5 5 17.5V45C5 46.3261 5.52678 47.5979 6.46447 48.5355C7.40215 49.4732 8.67392 50 10 50H60C61.3261 50 62.5979 49.4732 63.5355 48.5355C64.4732 47.5979 65 46.3261 65 45V17.5C66.3261 17.5 67.5979 16.9732 68.5355 16.0355C69.4732 15.0979 70 13.8261 70 12.5V5C70 3.67392 69.4732 2.40215 68.5355 1.46447C67.5979 0.526784 66.3261 0 65 0ZM60 45H10V17.5H60V45ZM65 12.5H5V5H65V12.5ZM25 27.5C25 26.837 25.2634 26.2011 25.7322 25.7322C26.2011 25.2634 26.837 25 27.5 25H42.5C43.163 25 43.7989 25.2634 44.2678 25.7322C44.7366 26.2011 45 26.837 45 27.5C45 28.163 44.7366 28.7989 44.2678 29.2678C43.7989 29.7366 43.163 30 42.5 30H27.5C26.837 30 26.2011 29.7366 25.7322 29.2678C25.2634 28.7989 25 28.163 25 27.5Z"
                                fill="black" fill-opacity="0.3" />
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
                    <a href="deleted_patients.php"
                        class="mt-8 bg-blue-500 hover:bg-blue-600 text-white text-base font-normal px-6 py-3 rounded-lg flex items-center gap-2 transition">
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

                <!-- Improved Pagination with Circular Buttons and Centered -->
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
                            // Calculate page range to display (show up to 5 pages)
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

        <!-- Footer Information -->
        <!-- <div class="text-center text-sm text-gray-500 mt-6">
            <i class="fas fa-shield-alt text-primary mr-1"></i>
            Archived patient records are retained for up to 60 months (5 years) for data recovery purposes. After this
            period, records will be automatically and permanently deleted. Restoring a patient will recover all
            associated consultation notes and medical information, if still within the retention period.
        </div> -->
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
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeRestoreModal() {
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRestoreModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeRestoreModal();
            }
        });

        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                var successMessages = document.querySelectorAll('#successMessage');
                var errorMessages = document.querySelectorAll('.alert-error');

                successMessages.forEach(function (message) {
                    message.style.transition = 'opacity 0.5s ease';
                    message.style.opacity = '0';
                    setTimeout(function () {
                        message.style.display = 'none';
                    }, 500);
                });

                errorMessages.forEach(function (message) {
                    message.style.transition = 'opacity 0.5s ease';
                    message.style.opacity = '0';
                    setTimeout(function () {
                        message.style.display = 'none';
                    }, 500);
                });
            }, 3000);
        });

        // Rotating chevron icon for sort dropdown - FIXED toggle behavior
        document.addEventListener('DOMContentLoaded', function () {
            const sortSelect = document.getElementById('sortSelect');
            const wrapper = document.getElementById('sortSelectWrapper');

            if (sortSelect && wrapper) {
                let isOpen = false;

                // Toggle dropdown when select is clicked
                sortSelect.addEventListener('click', function (e) {
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
                sortSelect.addEventListener('change', function () {
                    wrapper.classList.remove('sort-select-open');
                    isOpen = false;
                });

                // When select loses focus
                sortSelect.addEventListener('blur', function () {
                    wrapper.classList.remove('sort-select-open');
                    isOpen = false;
                });

                // Handle click outside to close
                document.addEventListener('click', function (event) {
                    if (!wrapper.contains(event.target) && isOpen) {
                        wrapper.classList.remove('sort-select-open');
                        isOpen = false;
                    }
                });

                // Handle escape key
                sortSelect.addEventListener('keydown', function (e) {
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

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
    </script>
</body>

</html>