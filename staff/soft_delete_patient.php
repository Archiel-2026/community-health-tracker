<?php
require_once __DIR__ . '/../includes/auth.php';


redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

if (isset($_GET['id'])) {
    $patientId = intval($_GET['id']);
    
    try {
        // Get patient info first for logging
        if (function_exists('staff_can_view_all') && staff_can_view_all()) {
            $stmt = $pdo->prepare("SELECT full_name FROM sitio1_patients WHERE id = ?");
            $stmt->execute([$patientId]);
        } else {
            $stmt = $pdo->prepare("SELECT full_name FROM sitio1_patients WHERE id = ? AND added_by = ?");
            $stmt->execute([$patientId, $_SESSION['user']['id']]);
        }
        $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Soft delete by setting deleted_at timestamp
        if (function_exists('staff_can_view_all') && staff_can_view_all()) {
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$patientId]);
        } else {
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET deleted_at = NOW() WHERE id = ? AND added_by = ?");
            $stmt->execute([$patientId, $_SESSION['user']['id']]);
        }
        
        if ($stmt->rowCount() > 0) {
            // Log staff activity for archiving patient
            try {
                $staff_id = $_SESSION['user']['id'] ?? null;
                $staff_name = $_SESSION['user']['full_name'] ?? 'Unknown';
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
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
                
                $patientName = $patientInfo ? $patientInfo['full_name'] : 'Unknown';
                $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'archive_patient', ?, ?, ?, ?, NOW())");
                $stmtLog->execute([$staff_id, $patientId, json_encode(['full_name' => $staff_name, 'patient_name' => $patientName, 'patient_id' => $patientId]), $ip, $ua]);
            } catch (Exception $e) {
                error_log('Staff activity log error (archive_patient): ' . $e->getMessage());
            }
            
            $_SESSION['success'] = 'Patient record archived successfully.';
        } else {
            $_SESSION['error'] = 'Patient record not found or you don\'t have permission to delete it.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error archiving patient record: ' . $e->getMessage();
    }
}

header('Location: patient_records.php');
exit();
?>