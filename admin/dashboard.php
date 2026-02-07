<?php

require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for admin after 1 hour of inactivity ---
if (isAdmin()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 3600) { // 1 hour = 3600 seconds
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/auth/login.php');
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
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT p.*, u.email as user_email, s.full_name as added_by_name, CASE WHEN p.user_id IS NOT NULL THEN 'Linked' ELSE 'Unlinked' END as linking_status FROM sitio1_patients p LEFT JOIN sitio1_users u ON p.user_id = u.id LEFT JOIN sitio1_staff s ON p.added_by = s.id WHERE p.deleted_at IS NULL " . $where . " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM sitio1_patients p WHERE p.deleted_at IS NULL");
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT p.*, u.email as user_email, s.full_name as added_by_name, CASE WHEN p.user_id IS NOT NULL THEN 'Linked' ELSE 'Unlinked' END as linking_status FROM sitio1_patients p LEFT JOIN sitio1_users u ON p.user_id = u.id LEFT JOIN sitio1_staff s ON p.added_by = s.id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
        }

        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
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
        if ($idx !== false) $staffCounts[$idx] = (int)$r['cnt'];
    }

    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_users WHERE role = 'patient' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY month");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $idx = array_search($r['month'], $months);
        if ($idx !== false) $residentCounts[$idx] = (int)$r['cnt'];
    }

    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_patients WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND deleted_at IS NULL GROUP BY month");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $idx = array_search($r['month'], $months);
        if ($idx !== false) $patientCounts[$idx] = (int)$r['cnt'];
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
        if (!$decoded) continue;

        // Normalize time
        if (isset($decoded['timestamp'])) $decoded['created_at'] = $decoded['timestamp'];

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
                } catch (Exception $e) {}
            }
            if ($uid && !empty($userNames[$uid])) $decoded['display_name'] = $userNames[$uid];

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
                } catch (Exception $e) {}
            }
            if ($sid && !empty($staffNames[$sid])) $decoded['display_name'] = $staffNames[$sid];

            $staff_logs[] = $decoded;
            continue;
        }

        // Fallbacks: if contains note_id or patient_id without staff_id, treat as staff log if created_by present
        if (!empty($decoded['note_id']) || !empty($decoded['patient_id'])) {
            $staff_logs[] = $decoded;
        }
    }
}

// Also pull from user_activity_log DB
try {
    $stmt = $pdo->query("SELECT user_id, action_type, action_timestamp, ip_address, user_agent FROM user_activity_log WHERE action_type IN ('login','logout') ORDER BY action_timestamp DESC LIMIT 500");
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
                if ($row && !empty($row['full_name'])) $userNames[$uid] = $row['full_name'];
            } catch (Exception $e) {}
        }
        if ($uid && !empty($userNames[$uid])) $r['display_name'] = $userNames[$uid];
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
            } catch (Exception $e) {}
        }
        if ($sid && !empty($staffNames[$sid])) $r['display_name'] = $staffNames[$sid];
        $staff_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

// Sort both logs desc
usort($resident_logs, function($a, $b) { $ta = strtotime($a['created_at'] ?? 0); $tb = strtotime($b['created_at'] ?? 0); return $tb <=> $ta; });
usort($staff_logs, function($a, $b) { $ta = strtotime($a['created_at'] ?? 0); $tb = strtotime($b['created_at'] ?? 0); return $tb <=> $ta; });

// Pagination
$perPage = 10;
$page_resident = isset($_GET['page_resident']) ? max(1,intval($_GET['page_resident'])) : 1;
$page_staff = isset($_GET['page_staff']) ? max(1,intval($_GET['page_staff'])) : 1;

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            font-family: 'Poppins', sans-serif !important;
        }

        /* Ensure Font Awesome icons are visible */
        .fas, .far, .fab {
            font-family: 'Font Awesome 6 Free' !important;
            font-weight: 900 !important;
            display: inline-block !important;
        }

        .far {
            font-weight: 400 !important;
        }
        
        /* Main container styling - Updated to match user dashboard */
        .main-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
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
        
        /* Button Styles - Updated to match user dashboard */
        .btn-primary { 
            background-color: white; 
            color: #3b82f6; 
            border: 2px solid #bae6fd; 
            border-radius: 8px; 
            padding: 12px 24px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-primary:hover { 
            background-color: #eff6ff; 
            border-color: #3b82f6;
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .btn-action { 
            background-color: white; 
            color: #374151; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 10px 20px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-action:hover { 
            background-color: #f9fafb; 
            border-color: #3b82f6;
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .btn-view { 
            background-color: #3b82f6; 
            color: white; 
            border-radius: 8px; 
            padding: 8px 16px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            border: none;
        }
        .btn-view:hover { 
            background-color: #2563eb; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }
        
        .btn-edit { 
            background-color: #10b981; 
            color: white; 
            border-radius: 8px; 
            padding: 8px 16px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            border: none;
        }
        .btn-edit:hover { 
            background-color: #059669; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        
        .btn-delete { 
            background-color: #ef4444; 
            color: white; 
            border-radius: 8px; 
            padding: 8px 16px; 
            transition: all 0.3s ease; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            border: none;
        }
        .btn-delete:hover { 
            background-color: #dc2626; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }
        
        /* Stats card styling */
        .stats-card {
              background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
              border: 1px solid #e5e7eb;
            height: 100%;
        }
        
        .stats-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Icon containers */
        .icon-container {
              width: 56px;
              height: 56px;
            border-radius: 12px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .icon-container i {
              font-size: 24px !important;
            display: block !important;
        }
        
        /* Badge styling */
        .stats-badge {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        /* Custom notification animation */
        .custom-notification {
            animation: slideIn 0.3s ease-out;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
        }
        
        @keyframes slideIn { 
            from { transform: translateX(100%); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        /* Progress bar */
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e5e7eb;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Quick action cards */
        .quick-action-card {
              padding: 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
              border: 1px solid #e5e7eb;
            background: white;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-decoration: none !important;
            color: inherit;
            display: block;
        }
        
        .quick-action-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .quick-action-icon i {
            font-size: 24px !important;
            display: block !important;
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

        /* Activity logs improvements: fixed layout, truncation, and scroll wrapper */
        .activity-logs-wrapper { 
            max-height: 360px; 
            overflow-y: auto; 
            overflow-x: auto; 
            padding-right: 6px;
            width: 100%;
        }

        .activity-logs-container {
            position: relative;
        }

        .activity-logs-loading {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5;
            border-radius: 12px;
            backdrop-filter: blur(1px);
        }

        .activity-logs-loading.active {
            display: flex;
        }

        .activity-logs-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top-color: #6366f1;
            border-radius: 9999px;
            animation: logsSpin 0.8s linear infinite;
        }

        .activity-logs-loading-text {
            margin-left: 10px;
            font-size: 13px;
            color: #4b5563;
            font-weight: 600;
        }

        @keyframes logsSpin {
            to { transform: rotate(360deg); }
        }

        /* Activity log tabs (ensure visibility) */
        .log-tabs {
            display: flex;
            gap: 8px;
            padding: 6px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .log-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .log-tab:hover {
            background: #f1f5f9;
        }

        .log-tab-active {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.25);
        }

        .log-tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: #e2e8f0;
            color: #475569;
        }

        .log-tab-active .log-tab-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        
        .logs-fullwidth { 
            width: 100%; 
            max-width: 100%; 
            margin-left: 0; 
            margin-right: 0; 
            box-sizing: border-box; 
        }
        
        .patient-table { 
            table-layout: fixed; 
            width: auto;
            min-width: 100%;
        }
        
        .patient-table td { 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
        }
        
        .patient-table th:nth-child(1), .patient-table td:nth-child(1) { 
            min-width: 180px; 
        }
        
        .patient-table th:nth-child(2), .patient-table td:nth-child(2) { 
            min-width: 240px; 
        }
        
        .patient-table th:nth-child(3), .patient-table td:nth-child(3) { 
            min-width: 180px; 
        }
        
        /* allow details column to wrap and show full message */
        .patient-table th:nth-child(4), .patient-table td:nth-child(4) { 
            min-width: 300px; 
            white-space: normal; 
        }
        
        .patient-table td .small-text { 
            display:block; 
            color:#6b7280; 
            font-size:12px; 
        }
        
        /* Mobile: allow horizontal scroll for very small screens */
        @media (max-width: 640px) {
            .patient-table { 
                table-layout: auto; 
                min-width: 600px;
            }
            .patient-table td { 
                white-space: normal; 
            }
            .activity-logs-wrapper { 
                max-height: 280px; 
            }
        }

        /* Desktop adjustments: center logs container and improve readability */
        @media (min-width: 1024px) {
            /* Keep the activity logs visually contained and centered on large screens */
            .logs-fullwidth {
                width: 100%;
                max-width: 1200px;
                margin-left: auto;
                margin-right: auto;
                padding-left: 18px;
                padding-right: 18px;
                box-sizing: border-box;
            }

            .activity-logs-wrapper {
                max-height: 520px;
                overflow-y: auto;
                overflow-x: hidden;
                padding: 8px 12px;
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid #eef2f7;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
            }

            /* make headers and columns more readable */
            .patient-table { table-layout: auto; }
            .patient-table th, .patient-table td { padding: 10px 12px; }
            .patient-table td { white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
            .patient-table th:nth-child(1), .patient-table td:nth-child(1) { min-width: 160px; }
            .patient-table th:nth-child(2), .patient-table td:nth-child(2) { min-width: 220px; }
            .patient-table th:nth-child(3), .patient-table td:nth-child(3) { min-width: 160px; }
            .patient-table th:nth-child(4), .patient-table td:nth-child(4) { min-width: 260px; }
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-linked {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-unlinked {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1002;
        }
        
        /* Form styling */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        /* Info cards in view modal */
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 700;
            color: #2c3e50;
            font-size: 11px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 500;
        }
        
        .info-empty {
            color: #9ca3af;
            font-style: italic;
        }

        /* Content section improvements */
        .content-section {
            display: flex;
            flex-wrap: wrap;
            gap: 28px;
            margin-bottom: 32px;
        }

        .content-item {
            flex: 1;
            min-width: 320px;
        }

        /* Quick actions container */
        .quick-actions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
        }

        .quick-action-item {
            flex: 1;
            min-width: 250px;
        }

        /* Overview section */
        .overview-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .overview-chart-container {
            flex: 2;
        }

        .overview-stats-container {
            flex: 1;
        }

        @media (min-width: 1024px) {
            .overview-section {
                flex-direction: row;
            }
        }

        /* Dashboard Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .dashboard-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Quick Navigation Grid */
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        @media (min-width: 640px) {
            .nav-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .nav-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .nav-card {
            background: white;
              border-radius: 12px;
              padding: 24px 20px;
              border: 1px solid #e5e7eb;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .nav-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 22px;
        }

        .nav-card-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            line-height: 1.3;
        }

        .nav-card-count {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (min-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .stat-card {
            background: white;
              border-radius: 12px;
            padding: 24px;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
              border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card-icon {
              width: 56px;
              height: 56px;
              border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
              font-size: 24px;
        }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card-label {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .stat-card-sublabel {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Main Content Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 1280px) {
            .main-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        /* Chart + Actions Grid */
        .chart-actions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 1024px) {
            .chart-actions-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        /* Icon Action Button Styles */
        .icon-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
                        border: none;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
              background: white;
                            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.06);
            position: relative;
            overflow: hidden;
        }

        .icon-action-btn:hover {
              transform: translateY(-2px);
              box-shadow: 0 12px 24px rgba(15, 23, 42, 0.14), 0 4px 10px rgba(15, 23, 42, 0.08);
        }

        .icon-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .icon-action-content {
            flex: 1;
            min-width: 0;
        }

        .icon-action-label {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .icon-action-desc {
            font-size: 12px;
            color: #6b7280;
        }

        /* Two Column Grid */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 1024px) {
            .two-col-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Patient Records List */
        .patient-records-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-record-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
              background: #f9fafb;
            border-radius: 12px;
            transition: all 0.2s ease;
              border: 1px solid #f3f4f6;
        }

        .patient-record-item:hover {
              background: #f3f4f6;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
              border-color: #e5e7eb;
        }

        .patient-record-main {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: none !important;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }

        .patient-info {
            flex: 1;
            min-width: 0;
        }

        .patient-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .patient-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .meta-divider {
            color: #d1d5db;
        }

        .patient-record-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .record-date {
            font-size: 12px;
            color: #9ca3af;
            margin-right: 4px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn i {
            font-size: 13px;
        }

        .action-view {
            background: #e0f2fe;
            color: #0284c7;
        }

        .action-view:hover {
            background: #0284c7;
            color: white;
        }

        .action-edit {
            background: #dcfce7;
            color: #16a34a;
        }

        .action-edit:hover {
            background: #16a34a;
            color: white;
        }

        @media (max-width: 640px) {
            .patient-meta {
                display: none;
            }
            .record-date {
                display: none;
            }
        }

        /* Mini Stats Row */
        .mini-stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        @media (min-width: 640px) {
            .mini-stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .mini-stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .mini-stat {
              background: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
              border: 1px solid #e5e7eb;
              box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .mini-stat:hover {
              transform: translateY(-2px);
              box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .mini-stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .mini-stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="container mx-auto px-4">
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div class="flex items-center">
                <i class="fas fa-chart-network text-3xl font-bold text-primary mr-4"></i>
                <div>
                    <h1 class="text-3xl font-bold text-secondary">Admin Dashboard</h1>
                    <p class="text-gray-600 mt-1">Overview of system statistics and activities</p>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                <i class="fas fa-calendar-alt mr-2"></i>Last updated: <?= date('M j, Y g:i A') ?>
            </div>
        </div>
        
        <!-- Notification Area -->
        <div id="notificationArea"></div>

        <!-- Stats Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon bg-blue-100">
                    <i class="fas fa-user-doctor text-blue-600"></i>
                </div>
                <div class="stat-card-value text-blue-600"><?= intval($stats['total_active_staff']) ?></div>
                <div class="stat-card-label">Active Staff</div>
                <div class="stat-card-sublabel"><?= intval($stats['total_inactive_staff']) ?> inactive</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon bg-green-100">
                    <i class="fas fa-users text-green-600"></i>
                </div>
                <div class="stat-card-value text-green-600"><?= intval($stats['total_approved_residents']) ?></div>
                <div class="stat-card-label">Resident Accounts</div>
                <div class="stat-card-sublabel"><?= intval($stats['total_pending_residents']) ?> pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon bg-purple-100">
                    <i class="fas fa-file-medical text-purple-600"></i>
                </div>
                <div class="stat-card-value text-purple-600"><?= intval($stats['total_patients']) ?></div>
                <div class="stat-card-label">Patient Records</div>
                <div class="stat-card-sublabel"><?= intval($stats['total_unlinked_patients']) ?> unlinked</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon bg-amber-100">
                    <i class="fas fa-link text-amber-600"></i>
                </div>
                <div class="stat-card-value text-amber-600"><?= intval($stats['linked_accounts_count']) ?></div>
                <div class="stat-card-label">Linked Accounts</div>
                <div class="stat-card-sublabel"><?= intval($stats['total_unlinked_residents']) ?> unlinked</div>
            </div>
        </div>

        <!-- Main Content Grid: Chart + Quick Actions -->
        <div class="chart-actions-grid">
            <!-- Chart Section -->
            <div class="main-container p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">System Overview</h3>
                        <p class="text-sm text-gray-500">Monthly registration trends (Last 12 months)</p>
                    </div>
                </div>
                <div style="height: 350px;">
                    <canvas id="overviewChart" style="width:100%;height:100%;"></canvas>
                </div>
            </div>

            <!-- Quick Actions Panel -->
            <div class="main-container p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center mr-3">
                        <i class="fas fa-bolt text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Quick Actions</h3>
                        <p class="text-sm text-gray-500">Access key features</p>
                    </div>
                </div>

                <!-- Icon Action Buttons -->
                <div class="space-y-3">
                    <a href="viewpatients.php" class="icon-action-btn bg-purple-50 hover:bg-purple-100 border-purple-200" title="View All Patients">
                        <div class="icon-action-icon bg-purple-100 text-purple-600">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="icon-action-content">
                            <div class="icon-action-label">View All Patients</div>
                            <div class="icon-action-desc">Browse patient records</div>
                        </div>
                        <i class="fas fa-arrow-right text-purple-400"></i>
                    </a>
                    <a href="approvals.php" class="icon-action-btn bg-amber-50 hover:bg-amber-100 border-amber-200" title="Pending Approvals">
                        <div class="icon-action-icon bg-amber-100 text-amber-600">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="icon-action-content">
                            <div class="icon-action-label">Pending Approvals</div>
                            <div class="icon-action-desc"><?= intval($stats['total_pending_residents']) ?> awaiting review</div>
                        </div>
                        <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-bold"><?= intval($stats['total_pending_residents']) ?></span>
                    </a>
                    <a href="staffrecords.php" class="icon-action-btn bg-cyan-50 hover:bg-cyan-100 border-cyan-200" title="Staff Records">
                        <div class="icon-action-icon bg-cyan-100 text-cyan-600">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="icon-action-content">
                            <div class="icon-action-label">Staff Records</div>
                            <div class="icon-action-desc">Manage staff accounts</div>
                        </div>
                        <i class="fas fa-arrow-right text-cyan-400"></i>
                    </a>
                    <a href="generate_report.php" target="_blank" class="icon-action-btn bg-blue-50 hover:bg-blue-100 border-blue-200" title="Generate Report">
                        <div class="icon-action-icon bg-blue-100 text-blue-600">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <div class="icon-action-content">
                            <div class="icon-action-label">Generate Report</div>
                            <div class="icon-action-desc">Export system data</div>
                        </div>
                        <i class="fas fa-external-link-alt text-blue-400"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Patient Records and Quick Stats - Two Column Grid -->
        <div class="two-col-grid">
            <!-- Patient Records Table -->
            <div class="main-container">
                <div class="p-5 pb-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mr-3">
                                <i class="fas fa-notes-medical text-purple-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary">Recent Patient Records</h2>
                                <p class="text-gray-400 text-sm">Latest patient entries</p>
                            </div>
                        </div>
                        <button class="btn-view" onclick="openRecordsModal(1)"><i class="fas fa-list mr-2"></i>View All</button>
                    </div>
                </div>
                
                <div class="px-5 pb-5">
                    <div class="patient-records-list">
                        <?php foreach (array_slice($stats['recent_patients'], 0, 5) as $patient): ?>
                        <div class="patient-record-item">
                            <div class="patient-record-main">
                                <div class="patient-avatar">
                                    <?php
                                    $profileImg = null;
                                    if (!empty($patient['user_id'])) {
                                        $uid = $patient['user_id'];
                                        $profileDir = __DIR__ . '/../uploads/profiles/';
                                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                                        foreach ($allowedExts as $ext) {
                                            $file = $profileDir . 'profile_' . $uid . '.' . $ext;
                                            if (file_exists($file)) {
                                                $profileImg = '/community-health-tracker/uploads/profiles/profile_' . $uid . '.' . $ext;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if ($profileImg): ?>
                                        <img src="<?= htmlspecialchars($profileImg) ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border border-gray-300" style="background:none;" />
                                    <?php else: ?>
                                        <span style="display:inline-block;width:40px;height:40px;line-height:40px;text-align:center;font-weight:600;font-size:1.25rem;color:#6b7280;background:none;"><?= strtoupper(substr($patient['full_name'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="patient-info">
                                    <div class="patient-name"><?= htmlspecialchars($patient['full_name']) ?></div>
                                    <div class="patient-meta">
                                        <span><?= htmlspecialchars($patient['age'] ?? 'N/A') ?> yrs</span>
                                        <span class="meta-divider">•</span>
                                        <span><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></span>
                                        <span class="meta-divider">•</span>
                                        <span><?= htmlspecialchars($patient['sitio'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="patient-record-actions">
                                <span class="record-date"><?= date('M j', strtotime($patient['created_at'] ?? 'now')) ?></span>
                                <button class="action-btn action-view" onclick="showViewModal(<?= $patient['id'] ?>, '<?= addslashes($patient['full_name']) ?>')" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn action-edit" onclick="showEditModal(<?= $patient['id'] ?>, '<?= addslashes($patient['full_name']) ?>')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="viewpatients.php" class="btn-action">View All Patient Records</a>
                    </div>
                </div>
            </div>
            
            <!-- Account Status Overview -->
            <div class="main-container p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                        <i class="fas fa-chart-pie text-green-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-secondary">Account Status</h2>
                        <p class="text-gray-500 text-sm">Linking overview</p>
                    </div>
                </div>
                
                <!-- Linked Accounts Highlight -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-5 mb-6" style="box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-green-700 mb-1 uppercase tracking-wider">Linked Accounts</div>
                            <div class="text-4xl font-bold text-green-600"><?= $stats['linked_accounts_count'] ?></div>
                            <p class="text-xs text-green-600 font-medium mt-1">Resident-Patient Links</p>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-green-500 flex items-center justify-center flex-shrink-0 shadow-lg shadow-green-200">
                            <i class="fas fa-link text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                    
                <!-- Status Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-amber-50 rounded-xl p-4 text-center" style="box-shadow: 0 2px 8px rgba(245, 158, 11, 0.12);">
                        <div class="text-2xl font-bold text-amber-600"><?= $stats['total_unlinked_residents'] ?></div>
                        <div class="text-xs font-medium text-amber-700">Unlinked Accounts</div>
                    </div>
                    <div class="bg-orange-50 rounded-xl p-4 text-center" style="box-shadow: 0 2px 8px rgba(249, 115, 22, 0.12);">
                        <div class="text-2xl font-bold text-orange-600"><?= $stats['total_unlinked_patients'] ?></div>
                        <div class="text-xs font-medium text-orange-700">Unlinked Patients</div>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 text-center" style="box-shadow: 0 2px 8px rgba(239, 68, 68, 0.12);">
                        <div class="text-2xl font-bold text-red-600"><?= $stats['total_declined_residents'] ?></div>
                        <div class="text-xs font-medium text-red-700">Declined</div>
                    </div>
                    <div class="bg-gray-100 rounded-xl p-4 text-center" style="box-shadow: 0 2px 8px rgba(107, 114, 128, 0.12);">
                        <div class="text-2xl font-bold text-gray-600"><?= $stats['total_inactive_staff'] ?></div>
                        <div class="text-xs font-medium text-gray-700">Inactive Staff</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Logs -->
        <div class="main-container p-6 activity-logs-container" id="activity-logs">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                        <i class="fas fa-history text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-secondary">Activity Logs</h2>
                        <p class="text-gray-500 text-sm">System activity and user actions</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="generate_report.php" target="_blank" class="btn-view">
                        <i class="fas fa-file-export mr-2"></i>Export
                    </a>
                </div>
            </div>

            <!-- Log Tabs -->
            <div class="log-tabs mb-6">
                <a href="?logs_tab=resident#activity-logs" class="log-tab <?= (empty($_GET['logs_tab']) || $_GET['logs_tab']=='resident') ? 'log-tab-active' : '' ?>">
                    <i class="fas fa-user"></i>Resident Log <span class="log-tab-badge"><?= $resident_total ?></span>
                </a>
                <a href="?logs_tab=staff#activity-logs" class="log-tab <?= (isset($_GET['logs_tab']) && $_GET['logs_tab']=='staff') ? 'log-tab-active' : '' ?>">
                    <i class="fas fa-user-tie"></i>Staff Actions <span class="log-tab-badge"><?= $staff_total ?></span>
                </a>
            </div>

            <div class="activity-logs-loading" id="activityLogsLoading" aria-live="polite">
                <div class="activity-logs-spinner" aria-hidden="true"></div>
                <div class="activity-logs-loading-text">Loading records...</div>
            </div>

            <?php if (empty($_GET['logs_tab']) || $_GET['logs_tab']=='resident'): ?>
                <div class="overflow-x-auto mb-4 activity-logs-wrapper rounded-xl" style="box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);">
                    <table class="patient-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Resident</th>
                                <th>Action</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resident_logs_page as $log): ?>
                            <tr>
                                <td class="text-gray-600"><?= !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A' ?></td>
                                <td class="font-medium"><?= htmlspecialchars($log['display_name'] ?? (isset($log['user_id']) && $log['user_id'] ? 'User #' . $log['user_id'] : 'System')) ?></td>
                                <td>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= ($log['type'] ?? '') == 'login' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                        <?= htmlspecialchars($log['type'] ?? $log['action_type'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="text-gray-500 text-sm"><?= htmlspecialchars($log['ip'] ?? $log['ip_address'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center text-sm activity-logs-pagination">
                    <div class="text-gray-500">Page <?= $page_resident ?> of <?= $resident_total_pages ?></div>
                    <div class="flex gap-2">
                        <?php if ($page_resident > 1): ?>
                            <a class="btn-action" href="?logs_tab=resident&page_resident=<?= $page_resident - 1 ?>#activity-logs"><i class="fas fa-chevron-left mr-1"></i>Prev</a>
                        <?php endif; ?>

                        <?php if ($page_resident < $resident_total_pages): ?>
                            <a class="btn-action" href="?logs_tab=resident&page_resident=<?= $page_resident + 1 ?>#activity-logs">Next<i class="fas fa-chevron-right ml-1"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="overflow-x-auto mb-4 activity-logs-wrapper rounded-xl" style="box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);">
                    <table class="patient-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Staff Name</th>
                                <th>Action Performed</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_logs_page as $log): ?>
                            <tr>
                                <td class="text-gray-600"><?= !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A' ?></td>
                                <td>
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                            <i class="fas fa-user-md text-blue-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($log['display_name'] ?? 'Unknown Staff') ?></span>
                                            <?php if (!empty($log['staff_id'])): ?>
                                                <span class="text-xs text-gray-400 block">ID: <?= $log['staff_id'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php 
                                        $actionType = strtolower($log['type'] ?? $log['action_type'] ?? '');
                                        if (strpos($actionType, 'add') !== false || strpos($actionType, 'create') !== false) {
                                            echo 'bg-green-100 text-green-700';
                                        } elseif (strpos($actionType, 'edit') !== false || strpos($actionType, 'update') !== false) {
                                            echo 'bg-yellow-100 text-yellow-700';
                                        } elseif (strpos($actionType, 'delete') !== false || strpos($actionType, 'remove') !== false) {
                                            echo 'bg-red-100 text-red-700';
                                        } elseif (strpos($actionType, 'view') !== false) {
                                            echo 'bg-purple-100 text-purple-700';
                                        } else {
                                            echo 'bg-blue-100 text-blue-700';
                                        }
                                        ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['type'] ?? $log['action_type'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="text-gray-500 text-sm max-w-xs truncate" title="<?= htmlspecialchars(is_array($log['details'] ?? null) || is_object($log['details'] ?? null) ? json_encode($log['details']) : ($log['details'] ?? '')) ?>">
                                    <?= htmlspecialchars(is_array($log['details'] ?? null) || is_object($log['details'] ?? null) ? json_encode($log['details']) : ($log['details'] ?? ($log['related_id'] ? 'Record #' . $log['related_id'] : '—'))) ?>
                                </td>
                                <td class="text-gray-400 text-xs"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center text-sm activity-logs-pagination">
                    <div class="text-gray-500">Page <?= $page_staff ?> of <?= $staff_total_pages ?></div>
                    <div class="flex gap-2">
                        <?php if ($page_staff > 1): ?>
                            <a class="btn-action" href="?logs_tab=staff&page_staff=<?= $page_staff - 1 ?>#activity-logs"><i class="fas fa-chevron-left mr-1"></i>Prev</a>
                        <?php endif; ?>

                        <?php if ($page_staff < $staff_total_pages): ?>
                            <a class="btn-action" href="?logs_tab=staff&page_staff=<?= $page_staff + 1 ?>#activity-logs">Next<i class="fas fa-chevron-right ml-1"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Records Modal -->
    <div id="recordsModal" class="modal-overlay" onclick="hideRecordsModal()">
        <div class="modal-container" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center mr-4">
                            <i class="fas fa-list text-white text-xl"></i>
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
                    <input type="text" id="recordsSearch" placeholder="Search by name or sitio..." class="form-control" onkeyup="debouncedLoadPatients(1)">
                </div>

                <div class="overflow-x-auto">
                    <table class="patient-table" id="recordsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Gender</th>
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
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4" id="viewProfileImgContainer"></div>
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
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4" id="editProfileImgContainer"></div>
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
                                <input type="number" id="editAge" name="age" class="form-control" min="0" max="120" required>
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
                                <textarea id="editAddress" name="address" class="form-control form-textarea"></textarea>
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
                                <textarea id="editMedicalHistory" name="medical_history" class="form-control form-textarea"></textarea>
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
                                <input type="checkbox" id="editConsentGiven" name="consent_given" class="w-4 h-4 text-primary rounded">
                                <label for="editConsentGiven" class="ml-2 text-gray-700">Consent Given</label>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-1/2 px-2 mb-4">
                            <div class="form-group">
                                <label class="form-label" for="editConsentDate">Consent Date</label>
                                <input type="datetime-local" id="editConsentDate" name="consent_date" class="form-control">
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
                        Archiving moves the record to the deleted patients archive where it can be restored later if needed.
                    </p>
                </div>
                
                <div class="flex flex-wrap -mx-2">
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <button onclick="archivePatient()" class="btn-action w-full justify-center bg-yellow-100 border-yellow-300 text-yellow-800 hover:bg-yellow-200">
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

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
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
                            const allowedExts = ['jpg','jpeg','png','gif'];
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
                            const allowedExts = ['jpg','jpeg','png','gif'];
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
        document.getElementById('editPatientForm').addEventListener('submit', function(e) {
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
        
        // Show loading spinner
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }
        
        // Hide loading spinner
        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
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
                    <td>${p.age || 'N/A'}</td>
                    <td>${escapeHtml(p.gender || 'N/A')}</td>
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
                return ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#39;', '`': '&#96;'})[s];
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
        document.addEventListener('DOMContentLoaded', function() {
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
            (function initOverviewChart(){
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
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideViewModal();
                hideEditModal();
                hideDeleteModal();
            }
        });
    </script>
    
</body>
</html>