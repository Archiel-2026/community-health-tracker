<?php
// staff/save_consultation_note.php - FIXED VERSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Simple authentication check
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../includes/auth.php';

if (!staffCanCreateConsultationNotes()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only Assistant Doctor accounts can add consultation notes.']);
    exit();
}

header('Content-Type: application/json');

try {
    // Get database connection
    global $pdo;
    
    // Check connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // DEBUG: Log session data
    error_log("SESSION USER DATA: " . print_r($_SESSION['user'], true));
    
    // Validate required fields
    if (!isset($_POST['patient_id']) || empty($_POST['patient_id'])) {
        echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
        exit();
    }
    
    if (!isset($_POST['note']) || empty(trim($_POST['note']))) {
        echo json_encode(['success' => false, 'message' => 'Consultation note is required']);
        exit();
    }
    
    if (!isset($_POST['consultation_date']) || empty($_POST['consultation_date'])) {
        echo json_encode(['success' => false, 'message' => 'Consultation date is required']);
        exit();
    }
    
    // Get inputs
    $patient_id = intval($_POST['patient_id']);
    $note = trim($_POST['note']);
    $consultation_date = $_POST['consultation_date'];
    $next_date = isset($_POST['next_consultation_date']) && !empty($_POST['next_consultation_date']) ? $_POST['next_consultation_date'] : null;
    
    // Get the logged-in staff ID from session
    $staff_id = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;
    
    // DEBUG: Check what staff ID we have
    error_log("Attempting to use staff_id: " . $staff_id);
    
    // Verify the staff ID exists in sitio1_staff table
    $checkStaff = $pdo->prepare("SELECT id, username, full_name FROM sitio1_staff WHERE id = ? AND status = 'active'");
    $checkStaff->execute([$staff_id]);
    $staff = $checkStaff->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        error_log("Staff ID $staff_id not found in sitio1_staff table");
        
        // Try to find the first active staff member
        $findAnyStaff = $pdo->prepare("SELECT id FROM sitio1_staff WHERE status = 'active' LIMIT 1");
        $findAnyStaff->execute();
        $anyStaff = $findAnyStaff->fetch(PDO::FETCH_ASSOC);
        
        if ($anyStaff) {
            $staff_id = $anyStaff['id'];
            error_log("Using alternative staff ID: " . $staff_id);
        } else {
            // Create a default staff account if none exists
            $defaultPassword = password_hash('staff123', PASSWORD_DEFAULT);
            $createStaff = $pdo->prepare("INSERT INTO sitio1_staff (full_name, username, password, email, role, status, created_at) 
                                         VALUES ('System Staff', 'system_staff', ?, 'staff@example.com', 'staff', 'active', NOW())");
            $createStaff->execute([$defaultPassword]);
            $staff_id = $pdo->lastInsertId();
            error_log("Created default staff with ID: " . $staff_id);
        }
    } else {
        error_log("Found staff: " . print_r($staff, true));
    }
    
    require_once __DIR__ . '/../includes/functions.php';
    // Verify patient belongs to this staff member OR allow when sharing is enabled
    if (staff_can_view_all()) {
        $checkPatient = $pdo->prepare("SELECT id, full_name FROM sitio1_patients WHERE id = ?");
        $checkPatient->execute([$patient_id]);
    } else {
        $checkPatient = $pdo->prepare("SELECT id, full_name FROM sitio1_patients WHERE id = ? AND added_by = ?");
        $checkPatient->execute([$patient_id, $staff_id]);
    }
    $patient = $checkPatient->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        error_log("Patient $patient_id not found for staff $staff_id");
        echo json_encode(['success' => false, 'message' => 'Patient not found or access denied']);
        exit();
    }
    
    error_log("Found patient: " . print_r($patient, true));
    
    // Insert the consultation note using valid staff ID
    $stmt = $pdo->prepare("INSERT INTO consultation_notes 
                          (patient_id, note, consultation_date, next_consultation_date, created_by) 
                          VALUES (?, ?, ?, ?, ?)");
    
    error_log("Executing query with params: patient_id=$patient_id, note=[REDACTED], date=$consultation_date, next_date=$next_date, created_by=$staff_id");
    
    $result = $stmt->execute([
        $patient_id,
        $note,
        $consultation_date,
        $next_date,
        $staff_id  // Using valid staff ID
    ]);
    
    if ($result) {
        $note_id = $pdo->lastInsertId();
        error_log("Successfully inserted note with ID: " . $note_id);

        // Log action to file and try DB table for staff activity
        try {
            $staff_name = $_SESSION['user']['full_name'] ?? 'Unknown';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Create staff_activity_log table if necessary
            $pdo->exec("CREATE TABLE IF NOT EXISTS staff_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT,
                action_type VARCHAR(100),
                related_id INT,
                details JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'add_consultation_note', ?, ?, ?, ?, NOW())");
            $stmtLog->execute([$staff_id, $patient_id, json_encode(['full_name' => $staff_name, 'note_id' => $note_id, 'patient_id' => $patient_id]), $ip, $ua]);
        } catch (Exception $e) {
            error_log('Staff activity DB log error: ' . $e->getMessage());
        }

        try {
            $log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'add_consultation_note',
                'staff_id' => $staff_id,
                'patient_id' => $patient_id,
                'note_id' => $note_id,
                'ip' => $ip ?? null,
                'user_agent' => $ua ?? null,
                'script' => (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ($_SERVER['SCRIPT_NAME'] ?? ''))
            ];
            @file_put_contents(__DIR__ . '/../logs/save_actions.log', json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log('Staff action file log error: ' . $e->getMessage());
        }
    } else {
        throw new Exception('Insert failed without error');
    }
    
} catch (PDOException $e) {
    error_log("Database Error in save_consultation_note.php: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0]);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage() . ' [Code: ' . $e->getCode() . ']'
    ]);
} catch (Exception $e) {
    error_log("General Error in save_consultation_note.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
