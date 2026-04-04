<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../vendor/autoload.php';
// --- Auto-logout for resident users after 10 minutes of inactivity ---
if (isUser()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 600) { // 10 minutes = 600 seconds
            // Destroy session and redirect to resident landing page
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/index-admin-staff.php');
            exit();
        } else {
            $_SESSION['last_action'] = $now;
        }
    }
}

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

// Initialize variables
$stats = [
    'total_active_staff' => 0,
    'total_inactive_staff' => 0,
    'total_approved_residents' => 0,
    'total_pending_residents' => 0,
    'total_declined_residents' => 0,
    'total_unlinked_residents' => 0,
    'total_unlinked_patients' => 0,
    'total_patients' => 0,
    'linked_accounts_count' => 0,
    'recent_patients' => []
];

$error_message = null;

// Handle patient deletion
if (isset($_GET['delete_patient']) && isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    $patientId = $_GET['delete_patient'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get patient data before deletion for archive - ONLY from sitio1_patients
        $stmt = $pdo->prepare("SELECT * FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patientData) {
            // Check if deleted_patients table exists, if not create it
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'deleted_patients'");
                if ($tableCheck->rowCount() == 0) {
                    // Create deleted_patients table that matches sitio1_patients structure but adds deleted_by
                    $pdo->exec("
                        CREATE TABLE deleted_patients (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            original_id INT NOT NULL,
                            user_id INT DEFAULT NULL,
                            bhw_assigned VARCHAR(100) DEFAULT NULL,
                            family_no VARCHAR(50) DEFAULT NULL,
                            fourps_member ENUM('Yes','No') DEFAULT 'No',
                            full_name VARCHAR(100) NOT NULL,
                            date_of_birth DATE DEFAULT NULL,
                            age INT DEFAULT NULL,
                            address TEXT DEFAULT NULL,
                            sitio VARCHAR(255) DEFAULT NULL,
                            disease VARCHAR(255) DEFAULT NULL,
                            contact VARCHAR(20) DEFAULT NULL,
                            last_checkup DATE DEFAULT NULL,
                            medical_history TEXT DEFAULT NULL,
                            added_by INT DEFAULT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            restored_at TIMESTAMP NULL DEFAULT NULL,
                            gender VARCHAR(10) DEFAULT NULL,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            consultation_type VARCHAR(20) DEFAULT 'onsite',
                            civil_status VARCHAR(20) DEFAULT NULL,
                            occupation VARCHAR(100) DEFAULT NULL,
                            consent_given TINYINT(1) DEFAULT 0,
                            consent_date DATETIME DEFAULT NULL,
                            patient_record_uid VARCHAR(50) DEFAULT NULL,
                            deleted_by INT NOT NULL
                        )
                    ");
                }

                // Check the actual structure of deleted_patients table
                $stmt = $pdo->query("DESCRIBE deleted_patients");
                $deletedColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                // Prepare data for insertion - only include columns that exist in both tables
                $columns = ['original_id'];
                $placeholders = ['?'];
                $values = [$patientData['id']];

                // Map columns from patientData to deleted_patients table
                $columnMappings = [
                    'user_id' => 'user_id',
                    'bhw_assigned' => 'bhw_assigned',
                    'family_no' => 'family_no',
                    'fourps_member' => 'fourps_member',
                    'full_name' => 'full_name',
                    'date_of_birth' => 'date_of_birth',
                    'age' => 'age',
                    'address' => 'address',
                    'sitio' => 'sitio',
                    'disease' => 'disease',
                    'contact' => 'contact',
                    'last_checkup' => 'last_checkup',
                    'medical_history' => 'medical_history',
                    'added_by' => 'added_by',
                    'gender' => 'gender',
                    'civil_status' => 'civil_status',
                    'occupation' => 'occupation',
                    'consent_given' => 'consent_given',
                    'consent_date' => 'consent_date',
                    'patient_record_uid' => 'patient_record_uid'
                ];

                foreach ($columnMappings as $sourceCol => $destCol) {
                    if (isset($patientData[$sourceCol]) && in_array($destCol, $deletedColumns)) {
                        $columns[] = $destCol;
                        $placeholders[] = "?";
                        $values[] = $patientData[$sourceCol];
                    }
                }

                // Add deleted_by
                $columns[] = 'deleted_by';
                $placeholders[] = "?";
                $values[] = $_SESSION['user']['id'];

                // Build and execute insert query
                $insertQuery = "INSERT INTO deleted_patients (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute($values);

            } catch (Exception $e) {
                // If table creation fails, create a simpler version
                error_log("Table error: " . $e->getMessage());

                // Try to create a simpler table
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS deleted_patients (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            original_id INT NOT NULL,
                            full_name VARCHAR(100) NOT NULL,
                            age INT DEFAULT NULL,
                            gender VARCHAR(10) DEFAULT NULL,
                            address TEXT DEFAULT NULL,
                            sitio VARCHAR(255) DEFAULT NULL,
                            contact VARCHAR(20) DEFAULT NULL,
                            added_by INT DEFAULT NULL,
                            deleted_by INT NOT NULL,
                            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");

                    // Insert basic patient data
                    $stmt = $pdo->prepare("
                        INSERT INTO deleted_patients 
                        (original_id, full_name, age, gender, address, sitio, contact, added_by, deleted_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $patientData['id'],
                        $patientData['full_name'] ?? '',
                        $patientData['age'] ?? null,
                        $patientData['gender'] ?? null,
                        $patientData['address'] ?? null,
                        $patientData['sitio'] ?? null,
                        $patientData['contact'] ?? null,
                        $patientData['added_by'] ?? null,
                        $_SESSION['user']['id']
                    ]);
                } catch (Exception $simpleError) {
                    error_log("Simple table creation error: " . $simpleError->getMessage());
                    // If even simple creation fails, just do soft delete without archiving
                }
            }

            // Soft delete from main table
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$patientId]);

            $pdo->commit();
            $_SESSION['success_message'] = 'Patient record has been moved to archive successfully!';
            header('Location: admin_dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = 'Error deleting patient record: ' . $e->getMessage();
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Handle permanent deletion (skip archive)
if (isset($_GET['permanent_delete']) && isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    $patientId = $_GET['permanent_delete'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Delete from existing_info_patients if table exists
        try {
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'existing_info_patients'");
            if ($tableCheck->rowCount() > 0) {
                $stmt = $pdo->prepare("DELETE FROM existing_info_patients WHERE patient_id = ?");
                $stmt->execute([$patientId]);
            }
        } catch (Exception $e) {
            error_log("Error deleting from existing_info_patients: " . $e->getMessage());
        }

        // Delete from main patients table
        $stmt = $pdo->prepare("DELETE FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);

        $pdo->commit();
        $_SESSION['success_message'] = 'Patient record permanently deleted!';
        header('Location: admin_dashboard.php');
        exit();
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = 'Error permanently deleting patient record: ' . $e->getMessage();
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Handle patient update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_patient') {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    try {
        $patientId = $_POST['patient_id'] ?? null;
        if (!$patientId) {
            throw new Exception("Patient ID is required");
        }

        // Validate required fields
        $requiredFields = ['full_name', 'age', 'gender', 'sitio', 'contact'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $response['errors'][$field] = "This field is required";
            }
        }

        if (!empty($response['errors'])) {
            $response['message'] = 'Please fill in all required fields';
            echo json_encode($response);
            exit;
        }

        // Prepare update data
        $updateFields = [
            'full_name' => $_POST['full_name'],
            'age' => $_POST['age'],
            'gender' => $_POST['gender'],
            'sitio' => $_POST['sitio'],
            'contact' => $_POST['contact'],
            'address' => $_POST['address'] ?? null,
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'civil_status' => $_POST['civil_status'] ?? null,
            'occupation' => $_POST['occupation'] ?? null,
            'disease' => $_POST['disease'] ?? null,
            'last_checkup' => !empty($_POST['last_checkup']) ? $_POST['last_checkup'] : null,
            'medical_history' => $_POST['medical_history'] ?? null,
            'bhw_assigned' => $_POST['bhw_assigned'] ?? null,
            'family_no' => $_POST['family_no'] ?? null,
            'fourps_member' => $_POST['fourps_member'] ?? 'No',
            'consent_given' => isset($_POST['consent_given']) ? 1 : 0,
            'consent_date' => !empty($_POST['consent_date']) ? $_POST['consent_date'] : null
        ];

        // Build the update query
        $setClauses = [];
        $params = [];

        foreach ($updateFields as $field => $value) {
            $setClauses[] = "$field = ?";
            $params[] = $value;
        }

        $params[] = $patientId;

        $updateQuery = "UPDATE sitio1_patients SET " . implode(", ", $setClauses) . " WHERE id = ?";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);

        $response['success'] = true;
        $response['message'] = 'Patient record updated successfully!';

    } catch (Exception $e) {
        $response['message'] = 'Error updating patient: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Get patient details for viewing/editing
if (isset($_GET['get_patient']) && is_numeric($_GET['get_patient'])) {
    try {
        header('Content-Type: application/json; charset=utf-8');
        $patientId = $_GET['get_patient'];
        $stmt = $pdo->prepare("SELECT * FROM sitio1_patients WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            echo json_encode(['success' => true, 'patient' => $patient]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
        }
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Error fetching patient data']);
        exit();
    }
}

// Export Activity Logs (Excel)
if (isset($_GET['export_activity_logs'])) {
    try {
        // Determine which log type to export
        $log_type = isset($_POST['log_type']) ? $_POST['log_type'] : 'resident';
        $filename = 'activity-logs-' . $log_type . '-' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Create a new spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Activity Logs');
        
        // Get logs based on type
        if ($log_type === 'resident') {
            $stmt = $pdo->query("SELECT user_id, action_type, action_timestamp, ip_address, user_agent FROM user_activity_log ORDER BY action_timestamp DESC");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Set headers
            $sheet->setCellValue('A1', 'Time Log');
            $sheet->setCellValue('B1', 'Resident');
            $sheet->setCellValue('C1', 'Action Performed');
            $sheet->setCellValue('D1', 'IP Address');
            
            // Add data
            $row = 2;
            foreach ($logs as $log) {
                $sheet->setCellValue('A' . $row, date('M j, Y g:i A', strtotime($log['action_timestamp'])));
                $sheet->setCellValue('B' . $row, 'User #' . $log['user_id']);
                $sheet->setCellValue('C' . $row, formatActionType(strtolower($log['action_type']))['label']);
                $sheet->setCellValue('D' . $row, $log['ip_address']);
                $row++;
            }
        } else {
            // Staff logs
            $stmt = $pdo->query("SELECT staff_id, action_type, related_id, details, created_at, ip_address FROM staff_activity_log ORDER BY created_at DESC");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Set headers
            $sheet->setCellValue('A1', 'Time Log');
            $sheet->setCellValue('B1', 'Staff Name');
            $sheet->setCellValue('C1', 'Action Performed');
            $sheet->setCellValue('D1', 'Details');
            $sheet->setCellValue('E1', 'IP Address');
            
            // Add data
            $row = 2;
            foreach ($logs as $log) {
                // Get staff name
                $staffStmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                $staffStmt->execute([$log['staff_id']]);
                $staffName = $staffStmt->fetchColumn() ?: 'Unknown Staff';
                
                $sheet->setCellValue('A' . $row, date('M j, Y g:i A', strtotime($log['created_at'])));
                $sheet->setCellValue('B' . $row, $staffName);
                $sheet->setCellValue('C' . $row, formatActionType(strtolower($log['action_type']))['label']);
                $sheet->setCellValue('D' . $row, $log['details'] ?: '');
                $sheet->setCellValue('E' . $row, $log['ip_address']);
                $row++;
            }
        }
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3C96E1']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];
        
        $sheet->getStyle('A1:' . ($log_type === 'resident' ? 'D' : 'E') . '1')->applyFromArray($headerStyle);
        
        // Auto-size columns
        foreach (range('A', $log_type === 'resident' ? 'D' : 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Output Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    } catch (Exception $e) {
        error_log('Export activity logs error: ' . $e->getMessage());
        header('Location: ?');
        exit();
    }
}

// List patients (AJAX, paginated)
if (isset($_GET['list_patients'])) {
    header('Content-Type: application/json; charset=utf-8');
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    try {
        $where = '';
        if ($q !== '') {
            $where = "AND (p.full_name LIKE :q OR p.sitio LIKE :q)";
        }

        // Total count with optional search
        if ($q !== '') {
            $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM sitio1_patients p WHERE p.deleted_at IS NULL " . $where);
            $countStmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT p.*, u.email as user_email, s.full_name as added_by_name, CASE WHEN p.user_id IS NOT NULL THEN 'Linked' ELSE 'Unlinked' END as linking_status FROM sitio1_patients p LEFT JOIN sitio1_users u ON p.user_id = u.id LEFT JOIN sitio1_staff s ON p.added_by = s.id WHERE p.deleted_at IS NULL " . $where . " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM sitio1_patients p WHERE p.deleted_at IS NULL");
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT p.*, u.email as user_email, s.full_name as added_by_name, CASE WHEN p.user_id IS NOT NULL THEN 'Linked' ELSE 'Unlinked' END as linking_status FROM sitio1_patients p LEFT JOIN sitio1_users u ON p.user_id = u.id LEFT JOIN sitio1_staff s ON p.added_by = s.id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
        }

        $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'patients' => $patients,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error listing patients']);
    }
    exit();
}

// Get stats for dashboard with error handling
try {
    // Check if tables exist before querying
    $tables = ['sitio1_staff', 'sitio1_users', 'sitio1_patients'];

    foreach ($tables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) {
            throw new Exception("Table '$table' does not exist in the database.");
        }
    }

    // Check if existing_info_patients table exists
    $check = $pdo->query("SHOW TABLES LIKE 'existing_info_patients'");
    $has_existing_info_table = $check->rowCount() > 0;

    // Check sitio1_staff table structure
    $staffColumns = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_staff");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $staffColumns[] = $column['Field'];
        }
    } catch (Exception $e) {
        error_log("Error checking staff table structure: " . $e->getMessage());
    }

    // ACTIVE STAFF - Fixed query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_staff WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_active_staff'] = $result ? $result['count'] : 0;

    // INACTIVE STAFF
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_staff WHERE is_active = 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_inactive_staff'] = $result ? $result['count'] : 0;

    // RESIDENT ACCOUNTS (ALL CREATED)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_users WHERE role = 'patient'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_approved_residents'] = $result ? $result['count'] : 0;

    // PENDING RESIDENTS
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_users WHERE role = 'patient' AND status = 'pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_pending_residents'] = $result ? $result['count'] : 0;

    // DECLINED RESIDENTS
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_users WHERE role = 'patient' AND status = 'declined'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_declined_residents'] = $result ? $result['count'] : 0;

    // UNLINKED RESIDENTS
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM sitio1_users u
        LEFT JOIN sitio1_patients p ON u.id = p.user_id
        WHERE u.role = 'patient' 
        AND u.status = 'approved'
        AND p.id IS NULL
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_unlinked_residents'] = $result ? $result['count'] : 0;

    // TOTAL PATIENTS
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_patients WHERE deleted_at IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_patients'] = $result ? $result['count'] : 0;

    // UNLINKED PATIENTS
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sitio1_patients WHERE user_id IS NULL AND deleted_at IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_unlinked_patients'] = $result ? $result['count'] : 0;

    // LINKED ACCOUNTS COUNT
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT u.id) as count
        FROM sitio1_users u
        INNER JOIN sitio1_patients p ON u.id = p.user_id
        WHERE u.role = 'patient' 
        AND u.status = 'approved'
        AND p.deleted_at IS NULL
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['linked_accounts_count'] = $result ? $result['count'] : 0;

    // Get recent patients (last 20)
    // First, let's check what columns exist in sitio1_staff
    $staffNameField = 'username'; // Default assumption
    if (in_array('name', $staffColumns)) {
        $staffNameField = 'name';
    } elseif (in_array('full_name', $staffColumns)) {
        $staffNameField = 'full_name';
    } elseif (in_array('first_name', $staffColumns)) {
        $staffNameField = 'first_name';
    }

    // Build the query based on available columns
    $recentPatientsQuery = "
        SELECT 
            p.*,
            u.email as user_email,
            s.$staffNameField as added_by_name,
            CASE 
                WHEN p.user_id IS NOT NULL THEN 'Linked'
                ELSE 'Unlinked'
            END as linking_status
        FROM sitio1_patients p
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        LEFT JOIN sitio1_staff s ON p.added_by = s.id
        WHERE p.deleted_at IS NULL
        ORDER BY p.created_at DESC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($recentPatientsQuery);
    $stmt->execute();
    $stats['recent_patients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If the query failed with the first assumption, try a simpler query
    if ($stmt->errorCode() != '00000') {
        $recentPatientsQuery = "
            SELECT 
                p.*,
                u.email as user_email,
                s.id as added_by_id,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Linked'
                    ELSE 'Unlinked'
                END as linking_status
            FROM sitio1_patients p
            LEFT JOIN sitio1_users u ON p.user_id = u.id
            LEFT JOIN sitio1_staff s ON p.added_by = s.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at DESC
            LIMIT 20
        ";

        $stmt = $pdo->prepare($recentPatientsQuery);
        $stmt->execute();
        $stats['recent_patients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Dashboard database error: " . $e->getMessage());
    $error_message = "Database error: " . $e->getMessage();
    $_SESSION['error_message'] = "Unable to fetch dashboard statistics. Please check database connection.";
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = $e->getMessage();
    $_SESSION['error_message'] = "System configuration error: " . $e->getMessage();
}

// Prepare monthly trend data for overview chart (last 12 months)
$months = [];
$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = $m;
    $monthLabels[] = date('M Y', strtotime("-$i months"));
}

$staffCounts = array_fill(0, 12, 0);
$residentCounts = array_fill(0, 12, 0);
$patientCounts = array_fill(0, 12, 0);

try {
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_staff WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY month");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $idx = array_search($r['month'], $months);
        if ($idx !== false)
            $staffCounts[$idx] = (int) $r['cnt'];
    }

    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_users WHERE role = 'patient' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY month");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $idx = array_search($r['month'], $months);
        if ($idx !== false)
            $residentCounts[$idx] = (int) $r['cnt'];
    }

    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_patients WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND deleted_at IS NULL GROUP BY month");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $idx = array_search($r['month'], $months);
        if ($idx !== false)
            $patientCounts[$idx] = (int) $r['cnt'];
    }
} catch (Exception $e) {
    // ignore, use defaults
}

$chart_month_labels = json_encode($monthLabels);
$chart_staff_json = json_encode($staffCounts);
$chart_resident_json = json_encode($residentCounts);
$chart_patient_json = json_encode($patientCounts);

// Build separated logs: resident (login/logout) and staff (actions)
$resident_logs = [];
$staff_logs = [];
$logFile = __DIR__ . '/../logs/save_actions.log';

// Helper caches
$userNames = [];
$staffNames = [];

if (file_exists($logFile)) {
    $lines = array_filter(array_map('trim', array_slice(file($logFile), -500)));
    foreach ($lines as $line) {
        $decoded = @json_decode($line, true);
        if (!$decoded)
            continue;

        // Normalize time
        if (isset($decoded['timestamp']))
            $decoded['created_at'] = $decoded['timestamp'];

        // Resident login/logout
        if (!empty($decoded['type']) && in_array($decoded['type'], ['login', 'logout'])) {
            // enrich name if possible
            $uid = $decoded['user_id'] ?? null;
            $decoded['display_name'] = 'User #' . ($uid ?? '');
            if ($uid && !isset($userNames[$uid])) {
                try {
                    $stmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                    $stmt->execute([$uid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['full_name'])) {
                        $userNames[$uid] = $row['full_name'];
                    }
                } catch (Exception $e) {
                }
            }
            if ($uid && !empty($userNames[$uid]))
                $decoded['display_name'] = $userNames[$uid];

            $resident_logs[] = $decoded;
            continue;
        }

        // Staff action entries
        if (!empty($decoded['staff_id']) || (!empty($decoded['type']) && strpos($decoded['type'], 'staff') !== false) || !empty($decoded['staff'])) {
            $sid = $decoded['staff_id'] ?? $decoded['staff'] ?? null;
            $decoded['display_name'] = 'Staff #' . ($sid ?? '');
            if ($sid && !isset($staffNames[$sid])) {
                try {
                    $stmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                    $stmt->execute([$sid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['full_name'])) {
                        $staffNames[$sid] = $row['full_name'];
                    }
                } catch (Exception $e) {
                }
            }
            if ($sid && !empty($staffNames[$sid]))
                $decoded['display_name'] = $staffNames[$sid];

            $staff_logs[] = $decoded;
            continue;
        }

        // Fallbacks: if contains note_id or patient_id without staff_id, treat as staff log if created_by present
        if (!empty($decoded['note_id']) || !empty($decoded['patient_id'])) {
            $staff_logs[] = $decoded;
        }
    }
}

// Also pull from user_activity_log DB (resident actions including login/logout)
try {
    $stmt = $pdo->query("SELECT user_id, action_type, action_timestamp, ip_address, user_agent FROM user_activity_log ORDER BY action_timestamp DESC LIMIT 500");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $r['type'] = $r['action_type'];
        $r['created_at'] = $r['action_timestamp'];
        $uid = $r['user_id'];
        $r['display_name'] = 'User #' . $uid;
        if ($uid && !isset($userNames[$uid])) {
            try {
                $stmt2 = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                $stmt2->execute([$uid]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['full_name']))
                    $userNames[$uid] = $row['full_name'];
            } catch (Exception $e) {
            }
        }
        if ($uid && !empty($userNames[$uid]))
            $r['display_name'] = $userNames[$uid];
        $resident_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

// Also pull resident announcement actions from resident_activity_log DB
try {
    $stmt = $pdo->query("SELECT resident_id, action_type, related_id, details, created_at, ip_address FROM resident_activity_log ORDER BY created_at DESC LIMIT 500");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $r['type'] = $r['action_type'];
        $r['user_id'] = $r['resident_id'];
        $r['created_at'] = $r['created_at'];
        $uid = $r['resident_id'];
        $r['display_name'] = 'User #' . $uid;
        if ($uid && !isset($userNames[$uid])) {
            try {
                $stmt2 = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                $stmt2->execute([$uid]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['full_name']))
                    $userNames[$uid] = $row['full_name'];
            } catch (Exception $e) {
            }
        }
        if ($uid && !empty($userNames[$uid]))
            $r['display_name'] = $userNames[$uid];
        $resident_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

// Also pull from staff_activity_log DB
try {
    $stmt = $pdo->query("SELECT staff_id, action_type, related_id, details, created_at, ip_address FROM staff_activity_log ORDER BY created_at DESC LIMIT 500");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sid = $r['staff_id'];
        $r['type'] = $r['action_type'];
        $r['created_at'] = $r['created_at'];
        $r['display_name'] = 'Staff #' . $sid;

        // First try to extract name from details JSON (for login/logout which stores the name)
        if (!empty($r['details'])) {
            $detailsData = json_decode($r['details'], true);
            if (isset($detailsData['full_name']) && !empty($detailsData['full_name'])) {
                $staffNames[$sid] = $detailsData['full_name'];
            }
        }

        // If not found in details, look up from database
        if ($sid && !isset($staffNames[$sid])) {
            try {
                // First try sitio1_staff table
                $stmt2 = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                $stmt2->execute([$sid]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['full_name'])) {
                    $staffNames[$sid] = $row['full_name'];
                } else {
                    // Fallback to sitio1_users table for staff role
                    $stmt3 = $pdo->prepare("SELECT full_name, username FROM sitio1_users WHERE id = ?");
                    $stmt3->execute([$sid]);
                    $row2 = $stmt3->fetch(PDO::FETCH_ASSOC);
                    if ($row2 && !empty($row2['full_name'])) {
                        $staffNames[$sid] = $row2['full_name'];
                    } elseif ($row2 && !empty($row2['username'])) {
                        $staffNames[$sid] = $row2['username'];
                    }
                }
            } catch (Exception $e) {
            }
        }
        if ($sid && !empty($staffNames[$sid]))
            $r['display_name'] = $staffNames[$sid];
        $staff_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

// Sort both logs desc
usort($resident_logs, function ($a, $b) {
    $ta = strtotime($a['created_at'] ?? 0);
    $tb = strtotime($b['created_at'] ?? 0);
    return $tb <=> $ta;
});
usort($staff_logs, function ($a, $b) {
    $ta = strtotime($a['created_at'] ?? 0);
    $tb = strtotime($b['created_at'] ?? 0);
    return $tb <=> $ta;
});

// Pagination
$perPage = 10;
$page_resident = isset($_GET['page_resident']) ? max(1, intval($_GET['page_resident'])) : 1;
$page_staff = isset($_GET['page_staff']) ? max(1, intval($_GET['page_staff'])) : 1;

$resident_total = count($resident_logs);
$staff_total = count($staff_logs);

$resident_total_pages = max(1, ceil($resident_total / $perPage));
$staff_total_pages = max(1, ceil($staff_total / $perPage));

$resident_logs_page = array_slice($resident_logs, ($page_resident - 1) * $perPage, $perPage);
$staff_logs_page = array_slice($staff_logs, ($page_staff - 1) * $perPage, $perPage);


require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay Luz Health Center</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/Superadmin-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

</head>

<body class="bg-gray-50">

    <div class="w-full px-8 py-10 lg:px-8">
        <div class="items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-700 flex items-center">Super Admin Dashboard</h1>
        </div>
        <!-- Top Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <!-- ACTIVE ADMIN -->
            <div
                class="rounded-xl bg-white border border-gray-100 shadow-sm flex flex-col items-start py-6 px-10  gap-2">
                <div class="flex justify-between w-full items-center gap-3 mb-8">
                    <span class="text-3xl font-bold text-gray-900"><?= intval($stats['total_active_staff']) ?></span>
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-blue-50">
                        <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#2563EB" fill-opacity="0.3" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M24.666 40.75C26.7343 40.75 28.7824 40.3426 30.6933 39.5511C32.6042 38.7596 34.3404 37.5995 35.8029 36.1369C37.2655 34.6744 38.4256 32.9381 39.2171 31.0273C40.0086 29.1164 40.416 27.0683 40.416 25C40.416 22.9317 40.0086 20.8836 39.2171 18.9727C38.4256 17.0619 37.2655 15.3256 35.8029 13.8631C34.3404 12.4005 32.6042 11.2404 30.6933 10.4489C28.7824 9.65739 26.7343 9.25 24.666 9.25C20.4889 9.25 16.4828 10.9094 13.5291 13.8631C10.5754 16.8168 8.91602 20.8228 8.91602 25C8.91602 29.1772 10.5754 33.1832 13.5291 36.1369C16.4828 39.0906 20.4889 40.75 24.666 40.75ZM24.26 31.37L33.01 20.87L30.322 18.63L22.797 27.6583L18.9033 23.7628L16.4288 26.2372L21.6788 31.4872L23.0333 32.8418L24.26 31.37Z"
                                fill="#3C96E1" />
                        </svg>
                    </span>
                </div>
                <div class="text-gray-500 text-xl">Active Admin</div>
            </div>

            <!-- RESIDENT ACCOUNTS -->
            <div
                class="rounded-xl bg-white border border-gray-100 shadow-sm flex flex-col items-start py-6 px-10 gap-2">
                <div class="flex justify-between w-full items-center gap-3 mb-8">
                    <span
                        class="text-3xl font-bold text-gray-900"><?= intval($stats['total_approved_residents']) ?></span>
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-orange-50">
                        <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#D97706" fill-opacity="0.3" />
                            <path
                                d="M28.6032 18.4375C29.6416 18.4375 30.6566 18.1296 31.52 17.5527C32.3833 16.9758 33.0562 16.1559 33.4536 15.1966C33.851 14.2373 33.9549 13.1817 33.7524 12.1633C33.5498 11.1449 33.0498 10.2094 32.3155 9.47519C31.5813 8.74097 30.6459 8.24095 29.6275 8.03838C28.6091 7.83581 27.5535 7.93977 26.5941 8.33713C25.6348 8.73449 24.8149 9.4074 24.238 10.2708C23.6611 11.1341 23.3532 12.1492 23.3532 13.1875C23.3532 14.5799 23.9064 15.9152 24.8909 16.8998C25.8755 17.8844 27.2108 18.4375 28.6032 18.4375ZM28.6032 10.5625C29.1224 10.5625 29.6299 10.7165 30.0616 11.0049C30.4933 11.2933 30.8297 11.7033 31.0284 12.183C31.2271 12.6626 31.2791 13.1904 31.1778 13.6996C31.0765 14.2088 30.8265 14.6765 30.4594 15.0437C30.0923 15.4108 29.6245 15.6608 29.1153 15.7621C28.6061 15.8634 28.0783 15.8114 27.5987 15.6127C27.119 15.414 26.7091 15.0776 26.4206 14.6459C26.1322 14.2142 25.9782 13.7067 25.9782 13.1875C25.9782 12.4913 26.2548 11.8236 26.7471 11.3313C27.2394 10.8391 27.907 10.5625 28.6032 10.5625ZM39.6463 27.0803C39.5462 27.1263 38.4174 27.6184 36.4192 27.6184C34.1469 27.6184 30.7508 26.9819 26.4622 24.3372C25.8095 26.1902 24.962 27.9688 23.934 29.643C25.7804 30.2114 27.5173 31.0884 29.0708 32.2368C32.1995 34.6223 33.8532 38.0184 33.8532 42.0625C33.8532 42.4106 33.715 42.7444 33.4688 42.9906C33.2227 43.2367 32.8888 43.375 32.5407 43.375C32.1926 43.375 31.8588 43.2367 31.6127 42.9906C31.3665 42.7444 31.2282 42.4106 31.2282 42.0625C31.2282 35.2211 25.5369 32.7585 22.3459 31.9152C22.2557 32.0301 22.1621 32.1466 22.0686 32.2598C18.8464 36.1645 14.8089 38.1955 10.3168 38.1955C9.80518 38.1979 9.29374 38.1744 8.78448 38.125C8.43638 38.0902 8.11637 37.9185 7.89484 37.6478C7.67332 37.377 7.56842 37.0293 7.60323 36.6813C7.63804 36.3332 7.8097 36.0131 8.08046 35.7916C8.35122 35.5701 8.69888 35.4652 9.04698 35.5C13.2995 35.9233 16.9991 34.2712 20.0392 30.5781C22.0883 28.0942 23.4845 25.064 24.1817 22.8672C17.7964 19.1512 13.7178 22.3143 13.6735 22.3488C13.5398 22.4629 13.3846 22.5491 13.2169 22.6021C13.0493 22.6551 12.8727 22.6739 12.6977 22.6573C12.5227 22.6408 12.3528 22.5892 12.198 22.5057C12.0433 22.4223 11.9069 22.3085 11.797 22.1713C11.6871 22.0341 11.6058 21.8763 11.5581 21.7071C11.5103 21.5379 11.4971 21.3608 11.5191 21.1864C11.5411 21.0119 11.5979 20.8437 11.6862 20.6917C11.7744 20.5396 11.8924 20.4068 12.0329 20.3013C12.279 20.1044 18.1393 15.5434 26.7182 21.3791C34.1781 26.4503 38.5192 24.7113 38.5602 24.6916C38.7174 24.6173 38.8879 24.575 39.0616 24.5672C39.2353 24.5594 39.4088 24.5862 39.5721 24.646C39.7354 24.7058 39.8852 24.7974 40.0127 24.9156C40.1403 25.0338 40.2431 25.1762 40.3152 25.3345C40.3872 25.4927 40.4271 25.6637 40.4325 25.8375C40.4379 26.0113 40.4088 26.1845 40.3467 26.3469C40.2846 26.5094 40.1909 26.6579 40.0709 26.7838C39.9509 26.9097 39.8072 27.0105 39.6479 27.0803H39.6463Z"
                                fill="#D97706" />
                        </svg>
                    </span>
                </div>
                <div class="text-gray-500 text-xl">Resident Accounts</div>
            </div>

            <!-- PATIENT RECORDS -->
            <div
                class="rounded-xl bg-white border border-gray-100 shadow-sm flex flex-col items-start py-6 px-10 gap-2">
                <div class="flex justify-between w-full items-center gap-3 mb-8">
                    <span class="text-3xl font-bold text-gray-900"><?= intval($stats['total_patients']) ?></span>
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-purple-50">
                        <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#9333EA" fill-opacity="0.3" />
                            <path
                                d="M18.1035 19.75C18.1035 19.4019 18.2418 19.0681 18.4879 18.8219C18.7341 18.5758 19.0679 18.4375 19.416 18.4375H29.916C30.2641 18.4375 30.598 18.5758 30.8441 18.8219C31.0902 19.0681 31.2285 19.4019 31.2285 19.75C31.2285 20.0981 31.0902 20.4319 30.8441 20.6781C30.598 20.9242 30.2641 21.0625 29.916 21.0625H19.416C19.0679 21.0625 18.7341 20.9242 18.4879 20.6781C18.2418 20.4319 18.1035 20.0981 18.1035 19.75ZM19.416 26.3125H29.916C30.2641 26.3125 30.598 26.1742 30.8441 25.9281C31.0902 25.6819 31.2285 25.3481 31.2285 25C31.2285 24.6519 31.0902 24.3181 30.8441 24.0719C30.598 23.8258 30.2641 23.6875 29.916 23.6875H19.416C19.0679 23.6875 18.7341 23.8258 18.4879 24.0719C18.2418 24.3181 18.1035 24.6519 18.1035 25C18.1035 25.3481 18.2418 25.6819 18.4879 25.9281C18.7341 26.1742 19.0679 26.3125 19.416 26.3125ZM24.666 28.9375H19.416C19.0679 28.9375 18.7341 29.0758 18.4879 29.3219C18.2418 29.5681 18.1035 29.9019 18.1035 30.25C18.1035 30.5981 18.2418 30.9319 18.4879 31.1781C18.7341 31.4242 19.0679 31.5625 19.416 31.5625H24.666C25.0141 31.5625 25.348 31.4242 25.5941 31.1781C25.8402 30.9319 25.9785 30.5981 25.9785 30.25C25.9785 29.9019 25.8402 29.5681 25.5941 29.3219C25.348 29.0758 25.0141 28.9375 24.666 28.9375ZM40.416 11.875V29.707C40.4171 30.0518 40.3497 30.3934 40.2176 30.712C40.0855 31.0305 39.8914 31.3196 39.6466 31.5625L31.2285 39.9805C30.9856 40.2254 30.6965 40.4195 30.378 40.5516C30.0594 40.6836 29.7178 40.7511 29.373 40.75H11.541C10.8448 40.75 10.1771 40.4734 9.68486 39.9812C9.19258 39.4889 8.91602 38.8212 8.91602 38.125V11.875C8.91602 11.1788 9.19258 10.5111 9.68486 10.0188C10.1771 9.52656 10.8448 9.25 11.541 9.25H37.791C38.4872 9.25 39.1549 9.52656 39.6472 10.0188C40.1395 10.5111 40.416 11.1788 40.416 11.875ZM11.541 38.125H28.6035V30.25C28.6035 29.9019 28.7418 29.5681 28.9879 29.3219C29.2341 29.0758 29.5679 28.9375 29.916 28.9375H37.791V11.875H11.541V38.125ZM31.2285 31.5625V36.2711L35.9355 31.5625H31.2285Z"
                                fill="#9333EA" />
                        </svg>
                    </span>
                </div>
                <div class="text-gray-500 text-xl">Patient Records</div>
            </div>

            <!-- LINKED ACCOUNTS -->
            <div
                class="rounded-xl bg-white border border-gray-100 shadow-sm flex flex-col items-start py-6 px-10 gap-2">
                <div class="flex justify-between w-full items-center gap-3 mb-8">
                    <span class="text-3xl font-bold text-gray-900"><?= intval($stats['linked_accounts_count']) ?></span>
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-amber-50">
                        <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_2003_11737)">
                                <path
                                    d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                    fill="#D97706" fill-opacity="0.3" />
                                <g clip-path="url(#clip1_2003_11737)">
                                    <path
                                        d="M18.7095 28.6428L30.7747 18.5189C31.0413 18.2951 31.386 18.1865 31.7327 18.2168C32.0795 18.2472 32.4 18.414 32.6238 18.6807C32.8475 18.9473 32.9562 19.2919 32.9259 19.6387C32.8955 19.9855 32.7287 20.306 32.462 20.5298L20.3968 30.6537C20.1301 30.8774 19.7855 30.9861 19.4388 30.9557C19.092 30.9254 18.7715 30.7585 18.5477 30.4919C18.324 30.2252 18.2153 29.8806 18.2456 29.5338C18.276 29.1871 18.4428 28.8665 18.7095 28.6428ZM26.7877 32.1444L21.7606 36.3627C20.4273 37.4815 18.7042 38.0248 16.9703 37.8731C15.2365 37.7214 13.6339 36.8871 12.5151 35.5538C11.3963 34.2206 10.8531 32.4974 11.0047 30.7636C11.1564 29.0297 11.9907 27.4271 13.324 26.3084L18.3512 22.0901C18.6178 21.8663 18.7847 21.5458 18.815 21.199C18.8453 20.8523 18.7367 20.5077 18.5129 20.241C18.2892 19.9743 17.9687 19.8075 17.6219 19.7771C17.2751 19.7468 16.9305 19.8555 16.6638 20.0792L11.6367 24.2975C9.77006 25.8638 8.60211 28.1074 8.38974 30.5348C8.17737 32.9622 8.93798 35.3746 10.5042 37.2412C12.0705 39.1078 14.3141 40.2757 16.7415 40.4881C19.1689 40.7005 21.5813 39.9398 23.4479 38.3736L28.4751 34.1553C28.7417 33.9315 28.9086 33.611 28.9389 33.2642C28.9692 32.9175 28.8606 32.5729 28.6368 32.3062C28.4131 32.0395 28.0926 31.8727 27.7458 31.8423C27.399 31.812 27.0544 31.9207 26.7877 32.1444ZM27.7236 10.799L22.6964 15.0173C22.4298 15.241 22.2629 15.5615 22.2326 15.9083C22.2022 16.2551 22.3109 16.5997 22.5347 16.8664C22.7584 17.133 23.0789 17.2999 23.4257 17.3302C23.7725 17.3605 24.1171 17.2519 24.3838 17.0281L29.4109 12.8098C30.7442 11.6911 32.4673 11.1478 34.2012 11.2995C35.935 11.4512 37.5376 12.2854 38.6564 13.6187C39.7751 14.952 40.3184 16.6751 40.1667 18.409C40.015 20.1428 39.1808 21.7454 37.8475 22.8642L32.8203 27.0825C32.5537 27.3062 32.3868 27.6267 32.3565 27.9735C32.3262 28.3203 32.4348 28.6649 32.6586 28.9316C32.8823 29.1982 33.2028 29.3651 33.5496 29.3954C33.8964 29.4257 34.241 29.3171 34.5077 29.0933L39.5348 24.875C41.4014 23.3088 42.5694 21.0652 42.7817 18.6377C42.9941 16.2103 42.2335 13.798 40.6672 11.9314C39.101 10.0648 36.8574 8.89684 34.43 8.68447C32.0025 8.4721 29.5902 9.23271 27.7236 10.799Z"
                                        fill="#D97706" />
                                </g>
                            </g>
                            <defs>
                                <clipPath id="clip0_2003_11737">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                        fill="white" />
                                </clipPath>
                                <clipPath id="clip1_2003_11737">
                                    <rect width="42" height="42" fill="white"
                                        transform="translate(-4 21.9961) rotate(-40)" />
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                </div>
                <div class="text-gray-500 text-xl">Linked Accounts</div>
            </div>
        </div>

        <!-- System Overview and Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- System Overview Chart -->
            <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-6 flex flex-col min-h-[320px]">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-1">System Overview</h2>
                    <p class="text-gray-500 text-base">Monthly registration trends (Last 12 months)</p>
                </div>
                <div class="flex-1 flex items-center justify-center min-h-[220px]">
                    <canvas id="overviewChart" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-6 flex flex-col ">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-1">Quick Actions</h2>
                    <p class="text-gray-500 text-base">Access key features</p>
                </div>
                <div class="flex flex-col gap-3">
                    <a href="viewpatients.php"
                        class="flex items-center justify-between p-4 rounded-lg border border-purple-100 shadow-md bg-purple-50 hover:bg-blue-50 transition group"
                        title="View All Patients">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#9333EA" />
                                    <path
                                        d="M25.7578 12.9873C26.3658 12.7356 27.0353 12.6695 27.6807 12.7979C28.3261 12.9263 28.9194 13.2436 29.3848 13.709C29.8501 14.1744 30.1665 14.7676 30.2949 15.4131C30.4233 16.0587 30.3573 16.7278 30.1055 17.3359C29.8536 17.9441 29.4272 18.4644 28.8799 18.8301C28.3327 19.1956 27.6893 19.3906 27.0312 19.3906C26.1486 19.3906 25.3019 19.0401 24.6777 18.416C24.0536 17.7919 23.7031 16.9452 23.7031 16.0625C23.7031 15.4044 23.8981 14.7611 24.2637 14.2139C24.6294 13.6666 25.1497 13.2392 25.7578 12.9873ZM27.0312 14.3281C26.5713 14.3281 26.1299 14.5108 25.8047 14.8359C25.4794 15.1612 25.2969 15.6025 25.2969 16.0625C25.2969 16.4055 25.3983 16.7412 25.5889 17.0264C25.7794 17.3115 26.0503 17.5338 26.3672 17.665C26.684 17.7963 27.0328 17.8305 27.3691 17.7637C27.7056 17.6968 28.0153 17.5316 28.2578 17.2891C28.5003 17.0465 28.6655 16.7368 28.7324 16.4004C28.7992 16.0641 28.765 15.7152 28.6338 15.3984C28.5025 15.0816 28.2793 14.8107 27.9941 14.6201C27.7091 14.4298 27.374 14.3282 27.0312 14.3281ZM26.2344 29.9727L26.2148 29.959L22.9375 27.6172L22.8906 27.584L22.8672 27.6367L19.3242 35.7861C19.2623 35.9286 19.1602 36.0506 19.0303 36.1357C18.9006 36.2206 18.7488 36.2656 18.5938 36.2656C18.4846 36.2659 18.3762 36.2433 18.2764 36.1992H18.2754C18.0819 36.115 17.9301 35.957 17.8525 35.7607C17.7751 35.5644 17.7784 35.3451 17.8623 35.1514L23.5664 22.0342L23.5898 21.9795L23.5312 21.9688C22.5964 21.8031 21.4516 22.0621 20.1113 22.7383L19.8408 22.8789C18.6786 23.52 17.5933 24.2929 16.6074 25.1816C16.4525 25.3202 16.2496 25.3927 16.042 25.3838C15.8341 25.3748 15.6376 25.2845 15.4951 25.1328C15.3529 24.9812 15.2753 24.7801 15.2793 24.5723C15.2834 24.3645 15.3687 24.1664 15.5166 24.0205C15.6485 23.8966 17.2707 22.3915 19.3457 21.3408C21.4245 20.2882 23.9359 19.7019 25.8652 21.377C26.2676 21.7257 26.6511 22.1126 27.0225 22.4893H27.0234C27.7582 23.2309 28.4771 23.9573 29.3916 24.4971C30.3078 25.0378 31.4191 25.3906 32.9375 25.3906C33.1487 25.3907 33.3516 25.4746 33.501 25.624C33.6503 25.7735 33.7344 25.9762 33.7344 26.1875C33.7344 26.3988 33.6503 26.6015 33.501 26.751C33.3516 26.9004 33.1487 26.9843 32.9375 26.9844C29.2336 26.9844 27.3849 25.1184 25.8916 23.6104C25.6027 23.3183 25.3252 23.0403 25.0459 22.7822L24.998 22.7373L24.9717 22.7979L23.5547 26.0547L23.54 26.0898L23.5703 26.1113L27.4941 28.9141C27.5974 28.9878 27.6812 29.0855 27.7393 29.1982C27.7972 29.311 27.8281 29.4357 27.8281 29.5625V35.4688C27.8281 35.68 27.7441 35.8828 27.5947 36.0322C27.4454 36.1816 27.2425 36.2656 27.0312 36.2656C26.82 36.2656 26.6172 36.1816 26.4678 36.0322C26.3183 35.8828 26.2344 35.6801 26.2344 35.4688V29.9727Z"
                                        fill="white" stroke="#3C96E1" stroke-width="0.09375" />
                                </svg>
                            </span>
                            <div>
                                <div class="font-semibold text-purple-700 text-lg ">View All Patients</div>
                                <div class="text-base text-gray-500">Browse Patient Records</div>
                            </div>
                        </div>
                        <div>
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M5.46937 17.469L16.1897 6.74958H8.25C8.05109 6.74958 7.86032 6.67057 7.71967 6.52991C7.57902 6.38926 7.5 6.19849 7.5 5.99958C7.5 5.80067 7.57902 5.6099 7.71967 5.46925C7.86032 5.3286 8.05109 5.24958 8.25 5.24958H18C18.1989 5.24958 18.3897 5.3286 18.5303 5.46925C18.671 5.6099 18.75 5.80067 18.75 5.99958V15.7496C18.75 15.9485 18.671 16.1393 18.5303 16.2799C18.3897 16.4206 18.1989 16.4996 18 16.4996C17.8011 16.4996 17.6103 16.4206 17.4697 16.2799C17.329 16.1393 17.25 15.9485 17.25 15.7496V7.8099L6.53063 18.5302C6.46094 18.5999 6.37822 18.6552 6.28717 18.6929C6.19613 18.7306 6.09855 18.75 6 18.75C5.90145 18.75 5.80387 18.7306 5.71283 18.6929C5.62178 18.6552 5.53906 18.5999 5.46937 18.5302C5.39969 18.4605 5.34442 18.3778 5.30671 18.2868C5.26899 18.1957 5.24958 18.0981 5.24958 17.9996C5.24958 17.901 5.26899 17.8035 5.30671 17.7124C5.34442 17.6214 5.39969 17.5386 5.46937 17.469Z"
                                    fill="#3C96E1" />
                            </svg>
                        </div>
                    </a>
                    <a href="staffrecords.php"
                        class="flex items-center justify-between p-4 rounded-lg border border-cyan-100 shadow-md hover:bg-blue-50 transition group"
                        title="Admin Records">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center">
                                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#0891B2" />
                                    <path
                                        d="M25.4901 18.8944L30.8609 20.3243M24.3404 23.1649L27.0246 23.8804M24.4743 31.212L25.5475 31.4989C28.585 32.3089 30.1038 32.7128 31.3008 32.0254C32.4966 31.3391 32.9039 29.8283 33.7173 26.8088L34.8681 22.5371C35.6826 19.5165 36.0888 18.0068 35.398 16.8165C34.7073 15.6263 33.1896 15.2224 30.151 14.4135L29.0778 14.1266C26.0403 13.3166 24.5215 12.9128 23.3256 13.6001C22.1286 14.2864 21.7214 15.7973 20.9069 18.8168L19.7571 23.0884C18.9426 26.109 18.5354 27.6188 19.2273 28.809C19.918 29.9981 21.4368 30.4031 24.4743 31.212Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    <path
                                        d="M24.5 34.5644L23.429 34.8569C20.3983 35.6816 18.884 36.0944 17.6893 35.3936C16.4968 34.6938 16.0895 33.1537 15.2784 30.0746L14.1298 25.7186C13.3175 22.6394 12.9114 21.0993 13.601 19.8866C14.1973 18.8369 15.5 18.8752 17.1875 18.8752"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div>
                                <div class="font-semibold text-lg" style="color: #0891B2;">Admin Records</div>
                                <div class="text-base text-gray-500">Manage Staff Accounts</div>
                            </div>
                        </div>
                        <div>
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M5.46937 17.469L16.1897 6.74958H8.25C8.05109 6.74958 7.86032 6.67057 7.71967 6.52991C7.57902 6.38926 7.5 6.19849 7.5 5.99958C7.5 5.80067 7.57902 5.6099 7.71967 5.46925C7.86032 5.3286 8.05109 5.24958 8.25 5.24958H18C18.1989 5.24958 18.3897 5.3286 18.5303 5.46925C18.671 5.6099 18.75 5.80067 18.75 5.99958V15.7496C18.75 15.9485 18.671 16.1393 18.5303 16.2799C18.3897 16.4206 18.1989 16.4996 18 16.4996C17.8011 16.4996 17.6103 16.4206 17.4697 16.2799C17.329 16.1393 17.25 15.9485 17.25 15.7496V7.8099L6.53063 18.5302C6.46094 18.5999 6.37822 18.6552 6.28717 18.6929C6.19613 18.7306 6.09855 18.75 6 18.75C5.90145 18.75 5.80387 18.7306 5.71283 18.6929C5.62178 18.6552 5.53906 18.5999 5.46937 18.5302C5.39969 18.4605 5.34442 18.3778 5.30671 18.2868C5.26899 18.1957 5.24958 18.0981 5.24958 17.9996C5.24958 17.901 5.26899 17.8035 5.30671 17.7124C5.34442 17.6214 5.39969 17.5386 5.46937 17.469Z"
                                    fill="#3C96E1" />
                            </svg>
                        </div>
                    </a>
                    <a id="activityLogsBtn"
                        class="flex items-center justify-between p-4 rounded-lg cursor-pointer border border-cyan-100 shadow-md bg-cyan-50 hover:bg-blue-50 transition group"
                        title="Activity Logs">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center">
                                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#4F46E5" />
                                    <path
                                        d="M15.8577 33.1422C14.375 31.6595 14.375 29.2723 14.375 24.5C14.375 19.7277 14.375 17.3405 15.8577 15.8577C17.3405 14.375 19.7277 14.375 24.5 14.375C29.2723 14.375 31.6595 14.375 33.1422 15.8577C34.625 17.3405 34.625 19.7277 34.625 24.5C34.625 29.2723 34.625 31.6595 33.1422 33.1422C31.6595 34.625 29.2723 34.625 24.5 34.625C19.7277 34.625 17.3405 34.625 15.8577 33.1422Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M18.875 26.75L22.0171 23.6079C22.2281 23.397 22.5142 23.2785 22.8125 23.2785C23.1108 23.2785 23.3969 23.397 23.6079 23.6079L25.3921 25.3921C25.6031 25.603 25.8892 25.7215 26.1875 25.7215C26.4858 25.7215 26.7719 25.603 26.9829 25.3921L30.125 22.25"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <div class="font-semibold text-indigo-700 text-lg">Activity Logs</div>
                                <div class="text-base text-gray-500">System activity and user actions</div>
                            </div>
                        </div>
                        <div>
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M5.46937 17.469L16.1897 6.74958H8.25C8.05109 6.74958 7.86032 6.67057 7.71967 6.52991C7.57902 6.38926 7.5 6.19849 7.5 5.99958C7.5 5.80067 7.57902 5.6099 7.71967 5.46925C7.86032 5.3286 8.05109 5.24958 8.25 5.24958H18C18.1989 5.24958 18.3897 5.3286 18.5303 5.46925C18.671 5.6099 18.75 5.80067 18.75 5.99958V15.7496C18.75 15.9485 18.671 16.1393 18.5303 16.2799C18.3897 16.4206 18.1989 16.4996 18 16.4996C17.8011 16.4996 17.6103 16.4206 17.4697 16.2799C17.329 16.1393 17.25 15.9485 17.25 15.7496V7.8099L6.53063 18.5302C6.46094 18.5999 6.37822 18.6552 6.28717 18.6929C6.19613 18.7306 6.09855 18.75 6 18.75C5.90145 18.75 5.80387 18.7306 5.71283 18.6929C5.62178 18.6552 5.53906 18.5999 5.46937 18.5302C5.39969 18.4605 5.34442 18.3778 5.30671 18.2868C5.26899 18.1957 5.24958 18.0981 5.24958 17.9996C5.24958 17.901 5.26899 17.8035 5.30671 17.7124C5.34442 17.6214 5.39969 17.5386 5.46937 17.469Z"
                                    fill="#3C96E1" />
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        
            <!-- Account Status -->
            <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-6 flex flex-col">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-1">Account Status</h2>
                    <p class="text-gray-500 text-base">Linking overview</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <!-- LINKED ACCOUNTS -->
                    <div class="rounded-xl border border-gray-200 shadow-md flex flex-col p-6">
                        <div class="flex flex-col md:flex-row sm:flex-row justify-between w-full items-center mb-4">
                            <div>
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#D97706" />
                                    <path
                                        d="M28.4723 20.5285C28.5507 20.6068 28.6129 20.6999 28.6554 20.8023C28.6979 20.9048 28.7197 21.0145 28.7197 21.1254C28.7197 21.2363 28.6979 21.3461 28.6554 21.4485C28.6129 21.551 28.5507 21.644 28.4723 21.7224L21.7223 28.4724C21.6439 28.5508 21.5508 28.613 21.4484 28.6554C21.3459 28.6978 21.2362 28.7196 21.1253 28.7196C21.0144 28.7196 20.9047 28.6978 20.8022 28.6554C20.6998 28.613 20.6067 28.5508 20.5283 28.4724C20.45 28.394 20.3878 28.3009 20.3453 28.1985C20.3029 28.0961 20.2811 27.9863 20.2811 27.8754C20.2811 27.7646 20.3029 27.6548 20.3453 27.5524C20.3878 27.4499 20.45 27.3569 20.5283 27.2785L27.2783 20.5285C27.3567 20.45 27.4498 20.3878 27.5522 20.3453C27.6546 20.3029 27.7644 20.281 27.8753 20.281C27.9862 20.281 28.096 20.3029 28.1984 20.3453C28.3008 20.3878 28.3939 20.45 28.4723 20.5285ZM33.7394 15.2614C33.1909 14.7129 32.5398 14.2777 31.8232 13.9809C31.1065 13.684 30.3385 13.5312 29.5628 13.5312C28.7871 13.5312 28.0191 13.684 27.3024 13.9809C26.5858 14.2777 25.9347 14.7129 25.3862 15.2614L22.2158 18.4307C22.0575 18.589 21.9686 18.8038 21.9686 19.0277C21.9686 19.2516 22.0575 19.4663 22.2158 19.6246C22.3742 19.7829 22.5889 19.8719 22.8128 19.8719C23.0367 19.8719 23.2514 19.7829 23.4098 19.6246L26.5801 16.4605C27.3742 15.6839 28.4425 15.2518 29.5532 15.2579C30.6639 15.264 31.7273 15.7079 32.5128 16.4932C33.2982 17.2785 33.7423 18.3419 33.7486 19.4526C33.7549 20.5633 33.323 21.6317 32.5465 22.4259L29.3751 25.5962C29.2167 25.7544 29.1277 25.969 29.1276 26.1928C29.1275 26.4166 29.2164 26.6313 29.3745 26.7896C29.5327 26.9479 29.7473 27.0369 29.9711 27.037C30.1949 27.0371 30.4096 26.9483 30.5679 26.7902L33.7394 23.6145C34.2879 23.066 34.723 22.4149 35.0198 21.6983C35.3167 20.9817 35.4695 20.2136 35.4695 19.4379C35.4695 18.6623 35.3167 17.8942 35.0198 17.1776C34.723 16.4609 34.2879 15.8098 33.7394 15.2614ZM25.5908 29.3752L22.4205 32.5456C22.0303 32.9445 21.5649 33.262 21.0512 33.4799C20.5375 33.6977 19.9856 33.8114 19.4276 33.8145C18.8697 33.8175 18.3166 33.7099 17.8005 33.4978C17.2844 33.2856 16.8156 32.9732 16.421 32.5786C16.0265 32.184 15.7142 31.7151 15.5022 31.199C15.2901 30.6828 15.1826 30.1298 15.1857 29.5718C15.1889 29.0138 15.3027 28.462 15.5206 27.9483C15.7385 27.4347 16.0562 26.9693 16.4551 26.5792L19.6245 23.4099C19.7828 23.2516 19.8717 23.0368 19.8717 22.8129C19.8717 22.589 19.7828 22.3743 19.6245 22.216C19.4662 22.0577 19.2514 21.9687 19.0275 21.9687C18.8036 21.9687 18.5889 22.0577 18.4306 22.216L15.2612 25.3864C14.1535 26.4941 13.5313 27.9964 13.5312 29.5629C13.5313 31.1294 14.1535 32.6318 15.2612 33.7395C16.3689 34.8472 17.8713 35.4695 19.4378 35.4695C21.0043 35.4695 22.5067 34.8472 23.6144 33.7395L26.7848 30.568C26.9429 30.4097 27.0317 30.195 27.0316 29.9712C27.0315 29.7474 26.9425 29.5328 26.7842 29.3747C26.6259 29.2165 26.4112 29.1277 26.1874 29.1278C25.9636 29.1279 25.749 29.2169 25.5908 29.3752Z"
                                        fill="white" />
                                </svg>
                            </div>
                            <div class="text-2xl font-bold">0<?= $stats['linked_accounts_count'] ?></div>
                        </div>
                        <div class="text-lg font-medium text-gray-500">Linked Accounts</div>
                    </div>

                    <!-- UNLINKED ACCOUNTS  -->
                    <div class="rounded-xl border border-gray-200 shadow-md flex flex-col p-6">
                        <div class="flex flex-col md:flex-row sm:flex-row justify-between w-full items-center mb-4">
                            <div>
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#9333EA" />
                                    <path
                                        d="M35.4691 19.4379C35.471 20.2139 35.3192 20.9825 35.0223 21.6993C34.7254 22.4162 34.2893 23.0672 33.7394 23.6145L30.5679 26.7849C30.4096 26.9431 30.1949 27.0319 29.9711 27.0318C29.7473 27.0317 29.5327 26.9427 29.3745 26.7844C29.2164 26.626 29.1275 26.4114 29.1276 26.1876C29.1277 25.9638 29.2167 25.7492 29.3751 25.591L32.5465 22.4206C32.9454 22.0304 33.263 21.565 33.4808 21.0513C33.6986 20.5376 33.8123 19.9858 33.8154 19.4278C33.8185 18.8698 33.7108 18.3168 33.4987 17.8007C33.2866 17.2846 32.9741 16.8157 32.5796 16.4212C32.185 16.0267 31.716 15.7143 31.1999 15.5023C30.6838 15.2902 30.1307 15.1827 29.5727 15.1859C29.0148 15.189 28.4629 15.3029 27.9493 15.5208C27.4356 15.7387 26.9702 16.0563 26.5801 16.4553L23.4098 19.6246C23.2514 19.7829 23.0367 19.8719 22.8128 19.8719C22.5889 19.8719 22.3742 19.7829 22.2158 19.6246C22.0575 19.4663 21.9686 19.2516 21.9686 19.0277C21.9686 18.8038 22.0575 18.589 22.2158 18.4307L25.3862 15.2614C26.2122 14.4353 27.2647 13.8727 28.4104 13.6448C29.5562 13.4168 30.7438 13.5338 31.8231 13.9809C32.9024 14.4279 33.8248 15.185 34.4738 16.1564C35.1228 17.1277 35.4691 18.2697 35.4691 19.4379ZM25.5908 29.3752L22.4205 32.5456C22.0303 32.9445 21.5649 33.262 21.0512 33.4799C20.5375 33.6977 19.9856 33.8114 19.4276 33.8145C18.8697 33.8175 18.3166 33.7099 17.8005 33.4978C17.2844 33.2856 16.8156 32.9732 16.421 32.5786C16.0265 32.184 15.7142 31.7151 15.5022 31.199C15.2901 30.6828 15.1826 30.1298 15.1857 29.5718C15.1889 29.0138 15.3027 28.462 15.5206 27.9483C15.7385 27.4347 16.0562 26.9693 16.4551 26.5792L19.6245 23.4099C19.7828 23.2516 19.8717 23.0368 19.8717 22.8129C19.8717 22.589 19.7828 22.3743 19.6245 22.216C19.4662 22.0577 19.2514 21.9687 19.0275 21.9687C18.8036 21.9687 18.5889 22.0577 18.4306 22.216L15.2612 25.3864C14.1535 26.4941 13.5313 27.9964 13.5312 29.5629C13.5313 31.1294 14.1535 32.6318 15.2612 33.7395C16.3689 34.8472 17.8713 35.4695 19.4378 35.4695C21.0043 35.4695 22.5067 34.8472 23.6144 33.7395L26.7848 30.568C26.9429 30.4097 27.0317 30.195 27.0316 29.9712C27.0315 29.7474 26.9425 29.5328 26.7842 29.3747C26.6259 29.2165 26.4112 29.1277 26.1874 29.1278C25.9636 29.1279 25.749 29.2169 25.5908 29.3752Z"
                                        fill="white" />
                                </svg>
                            </div>
                            <div class="text-2xl font-bold">0<?= $stats['total_unlinked_residents'] ?></div>
                        </div>
                        <div class="text-lg font-medium text-gray-500">Unlinked Accounts</div>
                    </div>

                    <!-- UNLINKED PATIENTS -->
                    <div class="rounded-xl border border-gray-200 shadow-md flex flex-col p-6">
                        <div class="flex flex-col md:flex-row sm:flex-row justify-between w-full items-center mb-4">
                            <div>
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#3C96E1" />
                                    <path
                                        d="M35.4691 19.4379C35.471 20.2139 35.3192 20.9825 35.0223 21.6993C34.7254 22.4162 34.2893 23.0672 33.7394 23.6145L30.5679 26.7849C30.4096 26.9431 30.1949 27.0319 29.9711 27.0318C29.7473 27.0317 29.5327 26.9427 29.3745 26.7844C29.2164 26.626 29.1275 26.4114 29.1276 26.1876C29.1277 25.9638 29.2167 25.7492 29.3751 25.591L32.5465 22.4206C32.9454 22.0304 33.263 21.565 33.4808 21.0513C33.6986 20.5376 33.8123 19.9858 33.8154 19.4278C33.8185 18.8698 33.7108 18.3168 33.4987 17.8007C33.2866 17.2846 32.9741 16.8157 32.5796 16.4212C32.185 16.0267 31.716 15.7143 31.1999 15.5023C30.6838 15.2902 30.1307 15.1827 29.5727 15.1859C29.0148 15.189 28.4629 15.3029 27.9493 15.5208C27.4356 15.7387 26.9702 16.0563 26.5801 16.4553L23.4098 19.6246C23.2514 19.7829 23.0367 19.8719 22.8128 19.8719C22.5889 19.8719 22.3742 19.7829 22.2158 19.6246C22.0575 19.4663 21.9686 19.2516 21.9686 19.0277C21.9686 18.8038 22.0575 18.589 22.2158 18.4307L25.3862 15.2614C26.2122 14.4353 27.2647 13.8727 28.4104 13.6448C29.5562 13.4168 30.7438 13.5338 31.8231 13.9809C32.9024 14.4279 33.8248 15.185 34.4738 16.1564C35.1228 17.1277 35.4691 18.2697 35.4691 19.4379ZM25.5908 29.3752L22.4205 32.5456C22.0303 32.9445 21.5649 33.262 21.0512 33.4799C20.5375 33.6977 19.9856 33.8114 19.4276 33.8145C18.8697 33.8175 18.3166 33.7099 17.8005 33.4978C17.2844 33.2856 16.8156 32.9732 16.421 32.5786C16.0265 32.184 15.7142 31.7151 15.5022 31.199C15.2901 30.6828 15.1826 30.1298 15.1857 29.5718C15.1889 29.0138 15.3027 28.462 15.5206 27.9483C15.7385 27.4347 16.0562 26.9693 16.4551 26.5792L19.6245 23.4099C19.7828 23.2516 19.8717 23.0368 19.8717 22.8129C19.8717 22.589 19.7828 22.3743 19.6245 22.216C19.4662 22.0577 19.2514 21.9687 19.0275 21.9687C18.8036 21.9687 18.5889 22.0577 18.4306 22.216L15.2612 25.3864C14.1535 26.4941 13.5313 27.9964 13.5312 29.5629C13.5313 31.1294 14.1535 32.6318 15.2612 33.7395C16.3689 34.8472 17.8713 35.4695 19.4378 35.4695C21.0043 35.4695 22.5067 34.8472 23.6144 33.7395L26.7848 30.568C26.9429 30.4097 27.0317 30.195 27.0316 29.9712C27.0315 29.7474 26.9425 29.5328 26.7842 29.3747C26.6259 29.2165 26.4112 29.1277 26.1874 29.1278C25.9636 29.1279 25.749 29.2169 25.5908 29.3752Z"
                                        fill="white" />
                                </svg>
                            </div>
                            <div class="text-2xl font-bold">0<?= $stats['total_unlinked_patients'] ?>
                            </div>
                        </div>
                        <div class="text-lg font-medium text-gray-500">Unlinked Patients</div>
                    </div>

                    <!-- INACTIVE STAFF -->
                    <div class="rounded-xl border border-gray-200 shadow-md flex flex-col p-6">
                        <div class="flex flex-col md:flex-row sm:flex-row justify-between w-full items-center mb-4">
                            <div>
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0 4C0 1.79086 1.79086 0 4 0H44C46.2091 0 48 1.79086 48 4V44C48 46.2091 46.2091 48 44 48H4C1.79086 48 0 46.2091 0 44V4Z"
                                        fill="#22C55E" />
                                    <path
                                        d="M25.7578 12.9873C26.3658 12.7356 27.0353 12.6695 27.6807 12.7979C28.3261 12.9263 28.9194 13.2436 29.3848 13.709C29.8501 14.1744 30.1665 14.7676 30.2949 15.4131C30.4233 16.0587 30.3573 16.7278 30.1055 17.3359C29.8536 17.9441 29.4272 18.4644 28.8799 18.8301C28.3327 19.1956 27.6893 19.3906 27.0312 19.3906C26.1486 19.3906 25.3019 19.0401 24.6777 18.416C24.0536 17.7919 23.7031 16.9452 23.7031 16.0625C23.7031 15.4044 23.8981 14.7611 24.2637 14.2139C24.6294 13.6666 25.1497 13.2392 25.7578 12.9873ZM27.0312 14.3281C26.5713 14.3281 26.1299 14.5108 25.8047 14.8359C25.4794 15.1612 25.2969 15.6025 25.2969 16.0625C25.2969 16.4055 25.3983 16.7412 25.5889 17.0264C25.7794 17.3115 26.0503 17.5338 26.3672 17.665C26.684 17.7963 27.0328 17.8305 27.3691 17.7637C27.7056 17.6968 28.0153 17.5316 28.2578 17.2891C28.5003 17.0465 28.6655 16.7368 28.7324 16.4004C28.7992 16.0641 28.765 15.7152 28.6338 15.3984C28.5025 15.0816 28.2793 14.8107 27.9941 14.6201C27.7091 14.4298 27.374 14.3282 27.0312 14.3281ZM26.2344 29.9727L26.2148 29.959L22.9375 27.6172L22.8906 27.584L22.8672 27.6367L19.3242 35.7861C19.2623 35.9286 19.1602 36.0506 19.0303 36.1357C18.9006 36.2206 18.7488 36.2656 18.5938 36.2656C18.4846 36.2659 18.3762 36.2433 18.2764 36.1992H18.2754C18.0819 36.115 17.9301 35.957 17.8525 35.7607C17.7751 35.5644 17.7784 35.3451 17.8623 35.1514L23.5664 22.0342L23.5898 21.9795L23.5312 21.9688C22.5964 21.8031 21.4516 22.0621 20.1113 22.7383L19.8408 22.8789C18.6786 23.52 17.5933 24.2929 16.6074 25.1816C16.4525 25.3202 16.2496 25.3927 16.042 25.3838C15.8341 25.3748 15.6376 25.2845 15.4951 25.1328C15.3529 24.9812 15.2753 24.7801 15.2793 24.5723C15.2834 24.3645 15.3687 24.1664 15.5166 24.0205C15.6485 23.8966 17.2707 22.3915 19.3457 21.3408C21.4245 20.2882 23.9359 19.7019 25.8652 21.377C26.2676 21.7257 26.6511 22.1126 27.0225 22.4893H27.0234C27.7582 23.2309 28.4771 23.9573 29.3916 24.4971C30.3078 25.0378 31.4191 25.3906 32.9375 25.3906C33.1487 25.3907 33.3516 25.4746 33.501 25.624C33.6503 25.7735 33.7344 25.9762 33.7344 26.1875C33.7344 26.3988 33.6503 26.6015 33.501 26.751C33.3516 26.9004 33.1487 26.9843 32.9375 26.9844C29.2336 26.9844 27.3849 25.1184 25.8916 23.6104C25.6027 23.3183 25.3252 23.0403 25.0459 22.7822L24.998 22.7373L24.9717 22.7979L23.5547 26.0547L23.54 26.0898L23.5703 26.1113L27.4941 28.9141C27.5974 28.9878 27.6812 29.0855 27.7393 29.1982C27.7972 29.311 27.8281 29.4357 27.8281 29.5625V35.4688C27.8281 35.68 27.7441 35.8828 27.5947 36.0322C27.4454 36.1816 27.2425 36.2656 27.0312 36.2656C26.82 36.2656 26.6172 36.1816 26.4678 36.0322C26.3183 35.8828 26.2344 35.6801 26.2344 35.4688V29.9727Z"
                                        fill="white" stroke="#3C96E1" stroke-width="0.09375" />
                                </svg>
                            </div>
                            <div class="text-2xl font-bold">0<?= $stats['total_inactive_staff'] ?></div>
                        </div>
                        <div class="text-lg font-medium text-gray-500">Inactive Staff</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Logs Modal -->
        <div id="activityLogsModal" class="modal-overlay" style="display:none;">
            <div class="modal-container" style="max-width:900px; padding: 2rem 2.5rem;"
                onclick="event.stopPropagation()">
                <div class="flex flex-row items-center justify-between mb-6 gap-4">
                    <div class="flex items-center">
                        <!-- <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                            <i class="fas fa-history text-indigo-600"></i>
                        </div> -->
                        <div>
                            <h2 class="text-xl font-semibold text-secondary mb-1">Activity Logs</h2>
                            <p class="text-gray-500 text-base">System activity and user actions</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="hideActivityLogsModal()" class="text-gray-500 hover:text-gray-700 ml-2"
                            style="font-size:1.5rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Log Tabs and Export Button -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex gap-2" id="activityLogsTabs">
                        <button class="log-tab log-tab-active" id="residentTabBtn" type="button">Resident Log</button>
                        <button class="log-tab" id="staffTabBtn" type="button">Admin Log</button>
                    </div>
                    <button id="exportLogsBtn" class="log-tab log-tab-active" title="Export">
                        <i class="fas fa-download"></i>Export
                    </button>
                </div>

                <div class="activity-logs-loading" id="activityLogsLoading" aria-live="polite">
                    <div class="activity-logs-spinner" aria-hidden="true"></div>
                    <div class="activity-logs-loading-text">Loading records...</div>
                </div>

                <div id="residentLogsSection">
                    <div class="overflow-x-auto mb-4 activity-logs-wrapper rounded-xl">
                        <table class="patient-table">
                            <thead>
                                <tr>
                                    <th>Time Log</th>
                                    <th>Resident</th>
                                    <th>Action Performed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resident_logs_page as $log): ?>
                                        <tr>
                                            <td class="text-gray-600">
                                                <?= !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A' ?>
                                            </td>
                                            <td class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($log['display_name'] ?? (isset($log['user_id']) && $log['user_id'] ? 'User #' . $log['user_id'] : 'System')) ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $actionType = strtolower($log['type'] ?? $log['action_type'] ?? '');
                                                    $formatted = formatActionType($actionType);
                                                    $colorMap = [
                                                        'blue' => 'rgba(29, 78, 216, 0.3)', 'blue-text' => '#1D4ED8',
                                                        'green' => 'rgba(54, 128, 61, 0.3)', 'green-text' => '#36803D',
                                                        'red' => 'rgba(239, 68, 68, 0.3)', 'red-text' => '#EF4444',
                                                        'gray' => 'rgba(107, 114, 128, 0.3)', 'gray-text' => '#6B7280',
                                                        'cyan' => 'rgba(34, 197, 94, 0.3)', 'cyan-text' => '#22C55E',
                                                        'orange' => 'rgba(245, 158, 11, 0.3)', 'orange-text' => '#F59E0B',
                                                        'purple' => 'rgba(168, 85, 247, 0.3)', 'purple-text' => '#A855F7',
                                                    ];
                                                    $color = $formatted['color'] ?? 'gray';
                                                    $bgColor = $colorMap[$color] ?? $colorMap['gray'];
                                                    $textColor = $colorMap[$color . '-text'] ?? $colorMap['gray-text'];
                                                ?>
                                                <span class="px-6 py-2 rounded-md text-sm font-medium" 
                                                      style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;">
                                                    <i class="fas <?= $formatted['icon'] ?> mr-1"></i><?= $formatted['label'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-between items-center text-sm activity-logs-pagination">
                        <div class="text-gray-500">Page <?= $page_resident ?> of <?= $resident_total_pages ?></div>
                        <div class="flex gap-2">
                            <?php if ($page_resident > 1): ?>
                                    <a class="btn-action"
                                        href="?logs_tab=resident&page_resident=<?= $page_resident - 1 ?>#activity-logs"><i
                                            class="fas fa-chevron-left mr-1"></i>Prev</a>
                            <?php endif; ?>
                            <?php if ($page_resident < $resident_total_pages): ?>
                                    <a class="btn-action"
                                        href="?logs_tab=resident&page_resident=<?= $page_resident + 1 ?>#activity-logs">Next<i
                                            class="fas fa-chevron-right ml-1"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


                
                <!-- Replace the staffLogsSection div with this simplified version -->

<div id="staffLogsSection" style="display:none;">
    <div class="overflow-x-auto mb-4 activity-logs-wrapper">
        <table class="patient-table">
            <thead>
                <tr>
                    <th>Time Log</th>
                    <th>Staff Name</th>
                    <th>Action Performed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff_logs_page as $log): ?>
                    <tr>
                        <td class="text-gray-600">
                            <?= !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A' ?>
                        </td>
                        <td>
                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($log['display_name'] ?? 'Unknown Staff') ?></span>
                        </td>
                        <td>
                            <?php 
                            $actionType = strtolower($log['type'] ?? $log['action_type'] ?? '');
                            $formatted = formatActionType($actionType);
                            $colorMap = [
                                'blue' => 'rgba(29, 78, 216, 0.3)', 'blue-text' => '#1D4ED8',
                                'green' => 'rgba(54, 128, 61, 0.3)', 'green-text' => '#36803D',
                                'red' => 'rgba(239, 68, 68, 0.3)', 'red-text' => '#EF4444',
                                'gray' => 'rgba(107, 114, 128, 0.3)', 'gray-text' => '#6B7280',
                                'cyan' => 'rgba(34, 197, 94, 0.3)', 'cyan-text' => '#22C55E',
                                'orange' => 'rgba(245, 158, 11, 0.3)', 'orange-text' => '#F59E0B',
                                'purple' => 'rgba(168, 85, 247, 0.3)', 'purple-text' => '#A855F7',
                                'indigo' => 'rgba(99, 102, 241, 0.3)', 'indigo-text' => '#6366F1',
                            ];
                            $color = $formatted['color'] ?? 'gray';
                            $bgColor = $colorMap[$color] ?? $colorMap['gray'];
                            $textColor = $colorMap[$color . '-text'] ?? $colorMap['gray-text'];
                            ?>
                            <span class="px-6 py-2 rounded-md text-sm font-medium" 
                                  style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;">
                                <i class="fas <?= $formatted['icon'] ?> mr-1"></i><?= $formatted['label'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="flex justify-between items-center text-sm activity-logs-pagination">
        <div class="text-gray-500">Page <?= $page_staff ?> of <?= $staff_total_pages ?></div>
        <div class="flex gap-2">
            <?php if ($page_staff > 1): ?>
                <a class="btn-action"
                    href="?logs_tab=staff&page_staff=<?= $page_staff - 1 ?>#activity-logs"><i
                        class="fas fa-chevron-left mr-1"></i>Prev</a>
            <?php endif; ?>
            <?php if ($page_staff < $staff_total_pages): ?>
                <a class="btn-action"
                    href="?logs_tab=staff&page_staff=<?= $page_staff + 1 ?>#activity-logs">Next<i
                        class="fas fa-chevron-right ml-1"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>
            <script>
                // Modal logic for Activity Logs
                const activityLogsBtn = document.getElementById('activityLogsBtn');
                const activityLogsModal = document.getElementById('activityLogsModal');
                const residentTabBtn = document.getElementById('residentTabBtn');
                const staffTabBtn = document.getElementById('staffTabBtn');
                const residentLogsSection = document.getElementById('residentLogsSection');
                const staffLogsSection = document.getElementById('staffLogsSection');
                const exportLogsBtn = document.getElementById('exportLogsBtn');

                function showActivityLogsModal() {
                    activityLogsModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }
                function hideActivityLogsModal() {
                    activityLogsModal.style.display = 'none';
                    document.body.style.overflow = '';
                }
                
                function exportActivityLogs() {
                    // Determine which tab is active
                    const isResidentTabActive = residentTabBtn.classList.contains('log-tab-active');
                    const logType = isResidentTabActive ? 'resident' : 'staff';
                    
                    // Create a form and submit it via POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '?export_activity_logs=1';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'log_type';
                    input.value = logType;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                }
                
                if (activityLogsBtn) {
                    activityLogsBtn.addEventListener('click', showActivityLogsModal);
                }
                
                if (exportLogsBtn) {
                    exportLogsBtn.addEventListener('click', exportActivityLogs);
                }
                
                if (residentTabBtn && staffTabBtn && residentLogsSection && staffLogsSection) {
                    residentTabBtn.addEventListener('click', function () {
                        residentTabBtn.classList.add('log-tab-active');
                        staffTabBtn.classList.remove('log-tab-active');
                        residentLogsSection.style.display = '';
                        staffLogsSection.style.display = 'none';
                    });
                    staffTabBtn.addEventListener('click', function () {
                        staffTabBtn.classList.add('log-tab-active');
                        residentTabBtn.classList.remove('log-tab-active');
                        staffLogsSection.style.display = '';
                        residentLogsSection.style.display = 'none';
                    });
                }
                // Prevent modal from closing except via close button
                activityLogsModal.addEventListener('click', function (e) {
                    if (e.target === activityLogsModal) {
                        // Do nothing, only close via button
                    }
                });
            </script>

        </div>

        <!-- Records Modal -->
        <div id="recordsModal" class="modal-overlay" onclick="hideRecordsModal()">
            <div class="modal-container" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center mr-4">
                                <!-- <i class="fa-solid fa-list"></i> -->
                                <svg width="46" height="46" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_1282_7686)">
                                        <path
                                            d="M14.2494 7.49965C14.2494 7.30074 14.3284 7.10998 14.469 6.96932C14.6097 6.82867 14.8004 6.74965 14.9994 6.74965H23.2494C23.4483 6.74965 23.639 6.82867 23.7797 6.96932C23.9203 7.10998 23.9994 7.30074 23.9994 7.49965C23.9994 7.69857 23.9203 7.88933 23.7797 8.02998C23.639 8.17064 23.4483 8.24965 23.2494 8.24965H14.9994C14.8004 8.24965 14.6097 8.17064 14.469 8.02998C14.3284 7.88933 14.2494 7.69857 14.2494 7.49965ZM23.2494 11.2497H14.9994C14.8004 11.2497 14.6097 11.3287 14.469 11.4693C14.3284 11.61 14.2494 11.8007 14.2494 11.9997C14.2494 12.1986 14.3284 12.3893 14.469 12.53C14.6097 12.6706 14.8004 12.7497 14.9994 12.7497H23.2494C23.4483 12.7497 23.639 12.6706 23.7797 12.53C23.9203 12.3893 23.9994 12.1986 23.9994 11.9997C23.9994 11.8007 23.9203 11.61 23.7797 11.4693C23.639 11.3287 23.4483 11.2497 23.2494 11.2497ZM23.2494 15.7497H17.2494C17.0504 15.7497 16.8597 15.8287 16.719 15.9693C16.5784 16.11 16.4994 16.3007 16.4994 16.4997C16.4994 16.6986 16.5784 16.8893 16.719 17.03C16.8597 17.1706 17.0504 17.2497 17.2494 17.2497H23.2494C23.4483 17.2497 23.639 17.1706 23.7797 17.03C23.9203 16.8893 23.9994 16.6986 23.9994 16.4997C23.9994 16.3007 23.9203 16.11 23.7797 15.9693C23.639 15.8287 23.4483 15.7497 23.2494 15.7497ZM14.2259 17.8122C14.2504 17.9076 14.2559 18.0069 14.242 18.1044C14.2282 18.2019 14.1952 18.2958 14.1451 18.3806C14.0949 18.4654 14.0286 18.5395 13.9498 18.5986C13.871 18.6578 13.7813 18.7008 13.6859 18.7253C13.6248 18.7418 13.5617 18.75 13.4984 18.7497C13.3321 18.7497 13.1704 18.6945 13.0389 18.5927C12.9074 18.4909 12.8134 18.3482 12.7719 18.1872C12.1944 15.9428 9.92748 14.2497 7.49842 14.2497C5.06935 14.2497 2.80248 15.9418 2.22498 18.1872C2.17525 18.3799 2.05101 18.5449 1.87959 18.646C1.70817 18.7471 1.50361 18.7759 1.31092 18.7262C1.11822 18.6765 0.953169 18.5522 0.852075 18.3808C0.750981 18.2094 0.722125 18.0049 0.771853 17.8122C1.29592 15.7768 2.81935 14.1287 4.75248 13.3122C4.00813 12.7388 3.4619 11.9469 3.1904 11.0475C2.9189 10.148 2.93573 9.18611 3.23855 8.29671C3.54137 7.4073 4.11498 6.63498 4.87893 6.08806C5.64289 5.54115 6.55887 5.24707 7.49842 5.24707C8.43796 5.24707 9.35394 5.54115 10.1179 6.08806C10.8819 6.63498 11.4555 7.4073 11.7583 8.29671C12.0611 9.18611 12.0779 10.148 11.8064 11.0475C11.5349 11.9469 10.9887 12.7388 10.2444 13.3122C12.1784 14.1287 13.7019 15.7768 14.2259 17.8122ZM7.49935 12.7497C8.0927 12.7497 8.67272 12.5737 9.16606 12.2441C9.65941 11.9144 10.0439 11.4459 10.271 10.8977C10.4981 10.3495 10.5575 9.74633 10.4417 9.16438C10.326 8.58244 10.0402 8.04789 9.62067 7.62833C9.20112 7.20878 8.66657 6.92305 8.08462 6.8073C7.50268 6.69154 6.89948 6.75095 6.3513 6.97802C5.80312 7.20508 5.33459 7.5896 5.00494 8.08294C4.6753 8.57629 4.49935 9.15631 4.49935 9.74965C4.49935 10.5453 4.81542 11.3084 5.37803 11.871C5.94064 12.4336 6.7037 12.7497 7.49935 12.7497Z"
                                            fill="#3C96E1" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_1282_7686">
                                            <rect width="24" height="24" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-secondary">Patient Records</h3>
                                <p class="text-gray-600">Browse and manage patient records</p>
                            </div>
                        </div>
                        <button onclick="hideRecordsModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="mb-4">
                        <input type="text" id="recordsSearch" placeholder="Search by name or sitio..."
                            class="form-control" onkeyup="debouncedLoadPatients(1)">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="patient-table" id="recordsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <!-- <th>Age</th> -->
                                    <!-- <th>Gender</th> -->
                                    <th>Status</th>
                                    <th>Added By</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recordsTableBody">
                                <!-- AJAX rows -->
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center mt-4" id="recordsPagination">
                        <!-- Pagination controls injected here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- View Patient Modal -->
        <div id="viewModal" class="modal-overlay" onclick="hideViewModal()">
            <div class="modal-container" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4"
                                id="viewProfileImgContainer"></div>
                            <div>
                                <h3 class="text-xl font-bold text-secondary">Patient Details</h3>
                                <p class="text-gray-600" id="viewPatientName"></p>
                            </div>
                        </div>
                        <button onclick="hideViewModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Personal Information -->
                        <div class="flex flex-wrap -mx-2">
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value" id="viewFullName"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Age</div>
                                    <div class="info-value" id="viewAge"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Gender</div>
                                    <div class="info-value" id="viewGender"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Date of Birth</div>
                                    <div class="info-value" id="viewDateOfBirth"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Civil Status</div>
                                    <div class="info-value" id="viewCivilStatus"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Occupation</div>
                                    <div class="info-value" id="viewOccupation"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <h4 class="font-semibold text-gray-700 mb-3">Contact Information</h4>
                        <div class="flex flex-wrap -mx-2">
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Address</div>
                                    <div class="info-value" id="viewAddress"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Sitio</div>
                                    <div class="info-value" id="viewSitio"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value" id="viewContact"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Information -->
                        <h4 class="font-semibold text-gray-700 mb-3">Medical Information</h4>
                        <div class="flex flex-wrap -mx-2">
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Disease/Condition</div>
                                    <div class="info-value" id="viewDisease"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Last Checkup</div>
                                    <div class="info-value" id="viewLastCheckup"></div>
                                </div>
                            </div>

                            <div class="w-full px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Medical History</div>
                                    <div class="info-value" id="viewMedicalHistory"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <h4 class="font-semibold text-gray-700 mb-3">Additional Information</h4>
                        <div class="flex flex-wrap -mx-2">
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">BHW Assigned</div>
                                    <div class="info-value" id="viewBhwAssigned"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Family Number</div>
                                    <div class="info-value" id="viewFamilyNo"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">4Ps Member</div>
                                    <div class="info-value" id="viewFourpsMember"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Consent Given</div>
                                    <div class="info-value" id="viewConsentGiven"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Consent Date</div>
                                    <div class="info-value" id="viewConsentDate"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <h4 class="font-semibold text-gray-700 mb-3">Record Information</h4>
                        <div class="flex flex-wrap -mx-2">
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Record Created</div>
                                    <div class="info-value" id="viewCreatedAt"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="info-card">
                                    <div class="info-label">Last Updated</div>
                                    <div class="info-value" id="viewUpdatedAt"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-6 border-t">
                        <button onclick="hideViewModal()" class="btn-action">
                            Close
                        </button>
                        <button onclick="viewToEdit()" class="btn-edit">
                            <i class="fas fa-edit mr-2"></i>Edit Record
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Patient Modal -->
        <div id="editModal" class="modal-overlay" onclick="hideEditModal()">
            <div class="modal-container" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4"
                                id="editProfileImgContainer"></div>
                            <div>
                                <h3 class="text-xl font-bold text-secondary">Edit Patient Record</h3>
                                <p class="text-gray-600" id="editModalPatientName">Update patient information</p>
                            </div>
                        </div>
                        <button onclick="hideEditModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form id="editPatientForm" class="space-y-4">
                        <input type="hidden" id="editPatientId" name="patient_id">
                        <input type="hidden" name="action" value="update_patient">

                        <div class="flex flex-wrap -mx-2">
                            <!-- Personal Information -->
                            <div class="w-full px-2 mb-4">
                                <h4 class="font-semibold text-gray-700 mb-3 border-b pb-2">Personal Information</h4>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editFullName">Full Name *</label>
                                    <input type="text" id="editFullName" name="full_name" class="form-control" required>
                                    <div class="error-message" id="errorFullName"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editAge">Age *</label>
                                    <input type="number" id="editAge" name="age" class="form-control" min="0" max="120"
                                        required>
                                    <div class="error-message" id="errorAge"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editGender">Gender *</label>
                                    <select id="editGender" name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="error-message" id="errorGender"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editDateOfBirth">Date of Birth</label>
                                    <input type="date" id="editDateOfBirth" name="date_of_birth" class="form-control">
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editCivilStatus">Civil Status</label>
                                    <select id="editCivilStatus" name="civil_status" class="form-control">
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editOccupation">Occupation</label>
                                    <input type="text" id="editOccupation" name="occupation" class="form-control">
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="w-full px-2 mt-4 mb-4">
                                <h4 class="font-semibold text-gray-700 mb-3 border-b pb-2">Contact Information</h4>
                            </div>

                            <div class="w-full px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editAddress">Address</label>
                                    <textarea id="editAddress" name="address"
                                        class="form-control form-textarea"></textarea>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editSitio">Sitio *</label>
                                    <input type="text" id="editSitio" name="sitio" class="form-control" required>
                                    <div class="error-message" id="errorSitio"></div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editContact">Contact Number *</label>
                                    <input type="tel" id="editContact" name="contact" class="form-control" required>
                                    <div class="error-message" id="errorContact"></div>
                                </div>
                            </div>

                            <!-- Medical Information -->
                            <div class="w-full px-2 mt-4 mb-4">
                                <h4 class="font-semibold text-gray-700 mb-3 border-b pb-2">Medical Information</h4>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editDisease">Disease/Condition</label>
                                    <input type="text" id="editDisease" name="disease" class="form-control">
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editLastCheckup">Last Checkup Date</label>
                                    <input type="date" id="editLastCheckup" name="last_checkup" class="form-control">
                                </div>
                            </div>

                            <div class="w-full px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editMedicalHistory">Medical History</label>
                                    <textarea id="editMedicalHistory" name="medical_history"
                                        class="form-control form-textarea"></textarea>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="w-full px-2 mt-4 mb-4">
                                <h4 class="font-semibold text-gray-700 mb-3 border-b pb-2">Additional Information</h4>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editBhwAssigned">BHW Assigned</label>
                                    <input type="text" id="editBhwAssigned" name="bhw_assigned" class="form-control">
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editFamilyNo">Family Number</label>
                                    <input type="text" id="editFamilyNo" name="family_no" class="form-control">
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editFourpsMember">4Ps Member</label>
                                    <select id="editFourpsMember" name="fourps_member" class="form-control">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>

                            <div class="w-full px-2 mb-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="editConsentGiven" name="consent_given"
                                        class="w-4 h-4 text-primary rounded">
                                    <label for="editConsentGiven" class="ml-2 text-gray-700">Consent Given</label>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="editConsentDate">Consent Date</label>
                                    <input type="datetime-local" id="editConsentDate" name="consent_date"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6 pt-6 border-t">
                            <button type="button" onclick="hideEditModal()" class="btn-action">
                                Cancel
                            </button>
                            <button type="submit" class="btn-edit">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal-overlay" onclick="hideDeleteModal()">
            <div class="modal-container" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-secondary">Delete Patient Record</h3>
                            <p class="text-gray-600">This action cannot be undone</p>
                        </div>
                    </div>

                    <div class="bg-warmRed border-2 border-red-200 rounded-lg p-4 mb-6">
                        <p class="text-red-700" id="patientNameDisplay"></p>
                        <p class="text-sm text-red-600 mt-2">All associated data will be removed from the system.</p>
                    </div>

                    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-800 mb-2">Consider Archiving Instead</h4>
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Archiving moves the record to the deleted patients archive where it can be restored later if
                            needed.
                        </p>
                    </div>

                    <div class="flex flex-wrap -mx-2">
                        <div class="w-full md:w-1/2 px-2 mb-4">
                            <button onclick="archivePatient()"
                                class="btn-action w-full justify-center bg-yellow-100 border-yellow-300 text-yellow-800 hover:bg-yellow-200">
                                <i class="fas fa-archive mr-2"></i>Archive (Move to Trash)
                            </button>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-4">
                            <button onclick="permanentDelete()" class="btn-delete w-full justify-center">
                                <i class="fas fa-trash-alt mr-2"></i>Permanent Delete
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button onclick="hideDeleteModal()" class="text-gray-600 hover:text-gray-800 font-medium">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Loading Overlay -->
        <div id="loadingOverlay">
            <div class="overlay-content">
                <div class="overlay-spinner">
                    <svg class="overlay-spin" width="64" height="64" viewBox="0 0 48 48" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="24" cy="24" r="20" stroke="#3498db" stroke-width="6" opacity="0.18" />
                        <path fill="#3498db" d="M24 4a20 20 0 0 1 20 20h-6a14 14 0 0 0-14-14V4z">
                            <animateTransform attributeName='transform' type='rotate' from='0 24 24' to='360 24 24'
                                dur='1.2s' repeatCount='indefinite' />
                        </path>
                    </svg>
                    <svg class="pulse" width="48" height="48" viewBox="0 0 48 48" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="24" cy="24" r="18" stroke="#b6d0f7" stroke-width="4" />
                    </svg>
                </div>
                <div class="overlay-message" id="loadingOverlayMessage">Processing, please wait...</div>
                <div class="overlay-desc">Do not close or refresh this page.</div>
            </div>
        </div>

        <script>
            // Global variables for modals
            let currentPatientId = null;
            let currentPatientName = '';
            let currentPatientData = null;

            // Show notification function
            function showNotification(type, message, duration = 5000) {
                const notificationArea = document.getElementById('notificationArea');

                const notification = document.createElement('div');
                notification.className = `custom-notification alert-${type} px-4 py-3 rounded mb-3 flex items-center`;

                const icon = type === 'error' ? 'fa-exclamation-circle' :
                    type === 'success' ? 'fa-check-circle' : 'fa-info-circle';

                notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

                notificationArea.appendChild(notification);

                // Auto-remove after duration
                setTimeout(() => {
                    notification.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, duration);
            }

            // Show view patient modal
            function showViewModal(patientId, patientName) {
                currentPatientId = patientId;
                currentPatientName = patientName;
                // Show loading
                showLoading();
                // Fetch patient data
                fetch(`?get_patient=${patientId}`)
                    .then(response => {
                        const ct = response.headers.get('content-type') || '';
                        if (!ct.includes('application/json')) {
                            return response.text().then(text => { throw new Error(text.substring(0, 500)); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        hideLoading();
                        if (data.success && data.patient) {
                            currentPatientData = data.patient;
                            const patient = data.patient;
                            // Profile image logic
                            let profileImgHtml = '';
                            if (patient.user_id) {
                                const allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                                let found = false;
                                for (let ext of allowedExts) {
                                    let url = `/community-health-tracker/uploads/profiles/profile_${patient.user_id}.${ext}`;
                                    let xhr = new XMLHttpRequest();
                                    xhr.open('HEAD', url, false);
                                    xhr.send();
                                    if (xhr.status === 200) {
                                        profileImgHtml = `<img src="${url}" alt="Profile" class="w-12 h-12 rounded-full object-cover border border-gray-300" style="background:none;" />`;
                                        found = true;
                                        break;
                                    }
                                }
                                if (!found) {
                                    profileImgHtml = `<span style='display:inline-block;width:48px;height:48px;line-height:48px;text-align:center;font-weight:600;font-size:1.5rem;color:#6b7280;background:none;'>${patient.full_name ? patient.full_name.charAt(0).toUpperCase() : '?'}</span>`;
                                }
                            } else {
                                profileImgHtml = `<span style='display:inline-block;width:48px;height:48px;line-height:48px;text-align:center;font-weight:600;font-size:1.5rem;color:#6b7280;background:none;'>${patient.full_name ? patient.full_name.charAt(0).toUpperCase() : '?'}</span>`;
                            }
                            document.getElementById('viewProfileImgContainer').innerHTML = profileImgHtml;
                            // Set modal title
                            document.getElementById('viewPatientName').textContent = patient.full_name;
                            // Format and display patient data
                            document.getElementById('viewFullName').textContent = patient.full_name || 'Not specified';
                            document.getElementById('viewAge').textContent = patient.age ? `${patient.age} years` : 'Not specified';
                            document.getElementById('viewGender').textContent = patient.gender || 'Not specified';
                            document.getElementById('viewDateOfBirth').textContent = formatDate(patient.date_of_birth);
                            document.getElementById('viewCivilStatus').textContent = patient.civil_status || 'Not specified';
                            document.getElementById('viewOccupation').textContent = patient.occupation || 'Not specified';
                            document.getElementById('viewAddress').textContent = patient.address || 'Not specified';
                            document.getElementById('viewSitio').textContent = patient.sitio || 'Not specified';
                            document.getElementById('viewContact').textContent = patient.contact || 'Not specified';
                            document.getElementById('viewDisease').textContent = patient.disease || 'Not specified';
                            document.getElementById('viewLastCheckup').textContent = formatDate(patient.last_checkup);
                            document.getElementById('viewMedicalHistory').textContent = patient.medical_history || 'Not specified';
                            document.getElementById('viewBhwAssigned').textContent = patient.bhw_assigned || 'Not specified';
                            document.getElementById('viewFamilyNo').textContent = patient.family_no || 'Not specified';
                            document.getElementById('viewFourpsMember').textContent = patient.fourps_member || 'No';
                            document.getElementById('viewConsentGiven').textContent = patient.consent_given == 1 ? 'Yes' : 'No';
                            document.getElementById('viewConsentDate').textContent = formatDateTime(patient.consent_date);
                            document.getElementById('viewCreatedAt').textContent = formatDateTime(patient.created_at);
                            document.getElementById('viewUpdatedAt').textContent = formatDateTime(patient.updated_at);
                            // Show modal
                            document.getElementById('viewModal').style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            showNotification('error', 'Failed to load patient data: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showNotification('error', 'Error loading patient data: ' + error.message);
                    });
            }

            // Hide view modal
            function hideViewModal() {
                document.getElementById('viewModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            // Switch from view to edit modal
            function viewToEdit() {
                hideViewModal();
                showEditModal(currentPatientId, currentPatientName);
            }

            // Show edit patient modal
            function showEditModal(patientId, patientName) {
                currentPatientId = patientId;
                currentPatientName = patientName;
                // Set modal title
                document.getElementById('editModalPatientName').textContent = `Editing: ${patientName}`;
                // Show loading
                showLoading();
                // Fetch patient data
                fetch(`?get_patient=${patientId}`)
                    .then(response => {
                        const ct = response.headers.get('content-type') || '';
                        if (!ct.includes('application/json')) {
                            return response.text().then(text => { throw new Error(text.substring(0, 500)); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        hideLoading();
                        if (data.success && data.patient) {
                            const patient = data.patient;
                            // Profile image logic
                            let profileImgHtml = '';
                            if (patient.user_id) {
                                const allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                                let found = false;
                                for (let ext of allowedExts) {
                                    let url = `/community-health-tracker/uploads/profiles/profile_${patient.user_id}.${ext}`;
                                    let xhr = new XMLHttpRequest();
                                    xhr.open('HEAD', url, false);
                                    xhr.send();
                                    if (xhr.status === 200) {
                                        profileImgHtml = `<img src="${url}" alt="Profile" class="w-12 h-12 rounded-full object-cover border border-gray-300" style="background:none;" />`;
                                        found = true;
                                        break;
                                    }
                                }
                                if (!found) {
                                    profileImgHtml = `<span style='display:inline-block;width:48px;height:48px;line-height:48px;text-align:center;font-weight:600;font-size:1.5rem;color:#6b7280;background:none;'>${patient.full_name ? patient.full_name.charAt(0).toUpperCase() : '?'}</span>`;
                                }
                            } else {
                                profileImgHtml = `<span style='display:inline-block;width:48px;height:48px;line-height:48px;text-align:center;font-weight:600;font-size:1.5rem;color:#6b7280;background:none;'>${patient.full_name ? patient.full_name.charAt(0).toUpperCase() : '?'}</span>`;
                            }
                            document.getElementById('editProfileImgContainer').innerHTML = profileImgHtml;
                            // Populate form fields
                            document.getElementById('editPatientId').value = patient.id;
                            document.getElementById('editFullName').value = patient.full_name || '';
                            document.getElementById('editAge').value = patient.age || '';
                            document.getElementById('editGender').value = patient.gender || '';
                            document.getElementById('editDateOfBirth').value = patient.date_of_birth || '';
                            document.getElementById('editCivilStatus').value = patient.civil_status || '';
                            document.getElementById('editOccupation').value = patient.occupation || '';
                            document.getElementById('editAddress').value = patient.address || '';
                            document.getElementById('editSitio').value = patient.sitio || '';
                            document.getElementById('editContact').value = patient.contact || '';
                            document.getElementById('editDisease').value = patient.disease || '';
                            document.getElementById('editLastCheckup').value = patient.last_checkup || '';
                            document.getElementById('editMedicalHistory').value = patient.medical_history || '';
                            document.getElementById('editBhwAssigned').value = patient.bhw_assigned || '';
                            document.getElementById('editFamilyNo').value = patient.family_no || '';
                            document.getElementById('editFourpsMember').value = patient.fourps_member || 'No';
                            document.getElementById('editConsentGiven').checked = patient.consent_given == 1;
                            document.getElementById('editConsentDate').value = patient.consent_date ? patient.consent_date.replace(' ', 'T') : '';
                            // Show modal
                            document.getElementById('editModal').style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            showNotification('error', 'Failed to load patient data: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showNotification('error', 'Error loading patient data: ' + error.message);
                    });
            }

            // Hide edit modal
            function hideEditModal() {
                document.getElementById('editModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                clearFormErrors();
            }

            // Clear form errors
            function clearFormErrors() {
                const errorElements = document.querySelectorAll('.error-message');
                errorElements.forEach(el => el.textContent = '');
            }

            // Handle edit form submission
            document.getElementById('editPatientForm').addEventListener('submit', function (e) {
                e.preventDefault();

                // Clear previous errors
                clearFormErrors();

                // Validate form
                let isValid = true;
                const requiredFields = ['full_name', 'age', 'gender', 'sitio', 'contact'];

                requiredFields.forEach(field => {
                    const input = document.getElementById(`edit${field.charAt(0).toUpperCase() + field.slice(1)}`);
                    const errorElement = document.getElementById(`error${field.charAt(0).toUpperCase() + field.slice(1)}`);

                    if (!input.value.trim()) {
                        errorElement.textContent = 'This field is required';
                        isValid = false;
                    }
                });

                if (!isValid) {
                    showNotification('error', 'Please fill in all required fields');
                    return;
                }

                // Show loading
                showLoading();

                // Prepare form data
                const formData = new FormData(this);

                // Send AJAX request
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();

                        if (data.success) {
                            // Show success notification
                            showNotification('success', data.message);

                            // Close modal and reload page after delay
                            setTimeout(() => {
                                hideEditModal();
                                location.reload();
                            }, 1500);
                        } else {
                            // Show errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    const errorElement = document.getElementById(`error${field.charAt(0).toUpperCase() + field.slice(1)}`);
                                    if (errorElement) {
                                        errorElement.textContent = data.errors[field];
                                    }
                                });
                            }

                            // Show error notification
                            showNotification('error', data.message || 'Failed to update patient');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showNotification('error', 'Network error: ' + error.message);
                    });
            });

            // Show delete confirmation modal
            function showDeleteModal(patientId, patientName) {
                currentPatientId = patientId;
                currentPatientName = patientName;

                document.getElementById('patientNameDisplay').textContent =
                    `Are you sure you want to delete "${patientName}"?`;

                document.getElementById('deleteModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            // Hide delete modal
            function hideDeleteModal() {
                document.getElementById('deleteModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                currentPatientId = null;
                currentPatientName = '';
            }

            // Show unified loading overlay
            function showLoading(message = 'Processing, please wait...') {
                const overlay = document.getElementById('loadingOverlay');
                const msg = document.getElementById('loadingOverlayMessage');
                if (msg) msg.textContent = message;
                if (overlay) overlay.style.display = 'flex';
            }

            // Hide unified loading overlay
            function hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.style.display = 'none';
            }

            // --- Records modal functions ---
            let currentRecordsPage = 1;
            let currentRecordsPerPage = 20;
            let recordsTotal = 0;

            function openRecordsModal(page = 1) {
                document.getElementById('recordsModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
                currentRecordsPage = page;
                loadPatients(page);
            }

            function hideRecordsModal() {
                document.getElementById('recordsModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                document.getElementById('recordsTableBody').innerHTML = '';
                document.getElementById('recordsPagination').innerHTML = '';
                document.getElementById('recordsSearch').value = '';
            }

            function renderRecordsTable(patients) {
                const tbody = document.getElementById('recordsTableBody');
                tbody.innerHTML = '';
                patients.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td><div class="font-medium text-gray-900">${escapeHtml(p.full_name)}</div><div class="text-sm text-gray-500">${escapeHtml(p.sitio || 'N/A')}</div>${p.user_email ? `<div class="text-xs text-gray-400">${escapeHtml(p.user_email)}</div>` : ''}</td>
                    <td>${escapeHtml(p.linking_status || (p.user_id ? 'Linked' : 'Unlinked'))}</td>
                    <td>${escapeHtml(p.added_by_name || (p.added_by ? 'Staff #' + p.added_by : 'System'))}</td>
                    <td>${formatDateTime(p.created_at)}</td>
                    <td> 
                        <div class="flex space-x-2">
                            <button class="btn-view" onclick="showViewModal(${p.id}, '${addslashesForJs(p.full_name)}')"><i class="fas fa-eye mr-1"></i>View</button>
                            <button class="btn-edit" onclick="showEditModal(${p.id}, '${addslashesForJs(p.full_name)}')"><i class="fas fa-edit mr-1"></i>Edit</button>
                            <button class="btn-delete" onclick="showDeleteModal(${p.id}, '${addslashesForJs(p.full_name)}')"><i class="fas fa-trash-alt mr-1"></i>Delete</button>
                        </div>
                    </td>
                `;
                    tbody.appendChild(tr);
                });
            }

            function buildPagination(total, page, perPage) {
                const container = document.getElementById('recordsPagination');
                container.innerHTML = '';
                const totalPages = Math.max(1, Math.ceil(total / perPage));

                const info = document.createElement('div');
                info.textContent = `Showing page ${page} of ${totalPages} (${total} records)`;
                container.appendChild(info);

                const controls = document.createElement('div');
                controls.className = 'space-x-2';

                if (page > 1) {
                    const prev = document.createElement('button');
                    prev.className = 'btn-action';
                    prev.textContent = '« Prev';
                    prev.onclick = () => loadPatients(page - 1);
                    controls.appendChild(prev);
                }

                if (page < totalPages) {
                    const next = document.createElement('button');
                    next.className = 'btn-action';
                    next.textContent = 'Next »';
                    next.onclick = () => loadPatients(page + 1);
                    controls.appendChild(next);
                }

                container.appendChild(controls);
            }

            function loadPatients(page = 1) {
                const query = document.getElementById('recordsSearch').value.trim();
                currentRecordsPage = page;
                showLoading();

                fetch(`?list_patients=1&page=${page}&per_page=${currentRecordsPerPage}` + (query ? '&q=' + encodeURIComponent(query) : ''))
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            renderRecordsTable(data.patients);
                            recordsTotal = data.total || 0;
                            buildPagination(recordsTotal, data.page, data.per_page);
                        } else {
                            showNotification('error', data.message || 'Failed to load patients');
                        }
                    })
                    .catch(err => {
                        hideLoading();
                        showNotification('error', 'Network error: ' + err.message);
                    });
            }

            // Small helper to debounce search
            let recordsSearchTimer = null;
            function debouncedLoadPatients(page = 1) {
                clearTimeout(recordsSearchTimer);
                recordsSearchTimer = setTimeout(() => loadPatients(page), 350);
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/[&<>"'`]/g, function (s) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;' })[s];
                });
            }

            function addslashesForJs(s) {
                if (!s) return '';
                return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\"/g, '\\"');
            }

            // Archive patient (soft delete)
            function archivePatient() {
                if (!currentPatientId) return;

                if (confirm(`Archive "${currentPatientName}"?\n\nThe record will be moved to the archive and can be restored later.`)) {
                    showLoading();
                    window.location.href = `?delete_patient=${currentPatientId}&confirm=true`;
                }
            }

            // Permanent delete
            function permanentDelete() {
                if (!currentPatientId) return;

                if (confirm(`PERMANENTLY DELETE "${currentPatientName}"?\n\nTHIS ACTION CANNOT BE UNDONE!\n\nAll patient data will be permanently erased from the system.`)) {
                    showLoading();
                    window.location.href = `?permanent_delete=${currentPatientId}&confirm=true`;
                }
            }

            // Format date for display
            function formatDate(dateString) {
                if (!dateString) return 'Not specified';

                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'Invalid date';

                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            // Format datetime for display
            function formatDateTime(datetimeString) {
                if (!datetimeString) return 'Not specified';

                const date = new Date(datetimeString);
                if (isNaN(date.getTime())) return 'Invalid date';

                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Initialize page
            document.addEventListener('DOMContentLoaded', function () {
                // Show any PHP session messages
                <?php if (isset($_SESSION['success_message'])): ?>
                        showNotification('success', '<?= addslashes($_SESSION['success_message']) ?>');
                        <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                        showNotification('error', '<?= addslashes($_SESSION['error_message']) ?>');
                        <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                // Animate stats cards on page load
                const statsCards = document.querySelectorAll('.stats-card');
                statsCards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                });

                // Initialize Overview Chart (Line - monthly trends)
                (function initOverviewChart() {
                    const canvasEl = document.getElementById('overviewChart');
                    if (!canvasEl || typeof Chart === 'undefined') return;
                    const ctx = canvasEl.getContext('2d');

                    // Register datalabels if available
                    if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

                    const labels = <?= $chart_month_labels ?>;
                    const staffData = <?= $chart_staff_json ?>;
                    const residentData = <?= $chart_resident_json ?>;
                    const patientData = <?= $chart_patient_json ?>;

                    // Create gradients
                    const gradStaff = ctx.createLinearGradient(0, 0, 0, canvasEl.height);
                    gradStaff.addColorStop(0, 'rgba(59,130,246,0.85)');
                    gradStaff.addColorStop(1, 'rgba(59,130,246,0.06)');

                    const gradResident = ctx.createLinearGradient(0, 0, 0, canvasEl.height);
                    gradResident.addColorStop(0, 'rgba(16,185,129,0.85)');
                    gradResident.addColorStop(1, 'rgba(16,185,129,0.06)');

                    const gradPatient = ctx.createLinearGradient(0, 0, 0, canvasEl.height);
                    gradPatient.addColorStop(0, 'rgba(139,92,246,0.85)');
                    gradPatient.addColorStop(1, 'rgba(139,92,246,0.06)');

                    const chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Active Staff (created)',
                                    data: staffData,
                                    borderColor: '#2563eb',
                                    backgroundColor: gradStaff,
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: '#3b82f6',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2
                                },
                                {
                                    label: 'Resident Accounts (created)',
                                    data: residentData,
                                    borderColor: '#059669',
                                    backgroundColor: gradResident,
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: '#10b981',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2
                                },
                                {
                                    label: 'Patient Records (created)',
                                    data: patientData,
                                    borderColor: '#7c3aed',
                                    backgroundColor: gradPatient,
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: '#8b5cf6',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top', labels: { font: { weight: 600 }, usePointStyle: true } },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    cornerRadius: 8
                                },
                                datalabels: { display: false }
                            },
                            interaction: { mode: 'index', intersect: false },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { maxRotation: 0 }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0 },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)',
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });

                    // Improve visibility: increase canvas pixel ratio for crisp lines on HiDPI
                    if (window.devicePixelRatio) {
                        chart.scale.update({});
                    }

                })();

                // Activity logs: keep users in list and show loading state
                const logsLoader = document.getElementById('activityLogsLoading');
                const logNavLinks = document.querySelectorAll('.log-tab, .activity-logs-pagination a');
                logNavLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (logsLoader) logsLoader.classList.add('active');
                    });
                });
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideViewModal();
                    hideEditModal();
                    hideDeleteModal();
                }
            });
        </script>

</body>

</html>
