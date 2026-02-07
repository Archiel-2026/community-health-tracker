<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

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

    $params = [];
    $whereClause1 = "1=1";
    $whereClause2 = "p.deleted_at IS NOT NULL";
    
    if (!staff_can_view_all()) {
        $whereClause1 = "d.deleted_by = ?";
        $whereClause2 = "p.added_by = ?";
        $params[] = $_SESSION['user']['id'];
        $params[] = $_SESSION['user']['id'];
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
    
    // Combine both queries
    $combinedQuery = "($hardDeleteQuery) UNION ALL ($softDeleteQuery) ORDER BY archived_date DESC";
    
    $stmt = $pdo->prepare($combinedQuery);
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
        
        /* Button Styles */
        .btn-primary { 
            background-color: white; 
            color: #3498db; 
            border: 2px solid #bae6fd; 
            border-radius: 30px; 
            padding: 12px 24px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            min-height: 55px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            text-decoration: none;
        }
        .btn-primary:hover { 
            background-color: #f0f9ff; 
            border-color: #3498db;
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
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
        }
        .btn-restore:hover { 
            background-color: #42d881; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.15);
        }
        
        /* REMOVED: .btn-delete styling */
        
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
        
        /* Patient ID styling */
        .patient-id { 
            font-weight: bold; 
            color: #3498db; 
        }
        
        /* Custom notification animation */
        .custom-notification { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { 
            from { transform: translateX(100%); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-secondary">Deleted Patients Archive</h1>
        
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
        
        <div class="main-container overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-secondary">Archived Patient Records</h2>
                        <p class="text-sm text-gray-500 mt-1">Patient records that have been moved to archive. Only restoration is allowed.</p>
                    </div>
                    <a href="existing_info_patients.php" class="btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Patients
                    </a>
                </div>
            </div>
            
            <?php if (empty($deletedPatients)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <div class="w-20 h-20 bg-white border-2 border-warmBlue rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-archive text-primary text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Archive is Empty</h3>
                    <p class="mt-1 text-sm text-gray-500">No deleted patient records found in archive.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="patient-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Type</th>
                                <th>Contact</th>
                                <th>Deleted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deletedPatients as $patient): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($patient['full_name']) ?></div>
                                        <div class="text-sm text-gray-500">ID: <?= $patient['original_id'] ?></div>
                                        <?php if (!empty($patient['user_email'])): ?>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($patient['user_email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $patient['age'] ?? 'N/A' ?></td>
                                    <td><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($patient['user_id']) && $patient['is_registered_user']): ?>
                                            <span class="user-badge">Registered User</span>
                                        <?php else: ?>
                                            <span class="text-gray-500">Regular Patient</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($patient['contact'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= date('M j, Y', strtotime($patient['archived_date'])) ?>
                                        <div class="text-sm text-gray-500">
                                            <?= date('g:i A', strtotime($patient['archived_date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex">
                                            <a href="?restore_patient=<?= $patient['original_id'] ?>" 
                                               class="btn-restore" 
                                               onclick="return confirm('Are you sure you want to restore this patient record?')">
                                                <i class="fas fa-undo mr-1"></i>Restore
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var successMessage = document.getElementById('successMessage');
                var errorMessage = document.querySelector('.alert-error');
                
                if (successMessage) {
                    successMessage.style.display = 'none';
                }
                
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            }, 3000);
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