<?php
require_once __DIR__ . '/../includes/auth.php';
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
require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;

// Handle patient search AJAX request (for manage_accounts.php)
if (isset($_GET['search_patients']) && isset($_GET['term'])) {
    $term = trim($_GET['term']);
    
    if (strlen($term) >= 2) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    full_name,
                    DATE_FORMAT(date_of_birth, '%M %d, %Y') as date_of_birth,
                    age,
                    gender,
                    sitio,
                    contact,
                    civil_status,
                    DATE_FORMAT(last_checkup, '%M %d, %Y') as last_checkup
                FROM sitio1_patients 
                WHERE full_name LIKE ? 
                AND user_id IS NULL
                AND deleted_at IS NULL
                ORDER BY full_name ASC
                LIMIT 10
            ");
            
            $searchTerm = '%' . $term . '%';
            $stmt->execute([$searchTerm]);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($patients);
            exit();
        } catch (PDOException $e) {
            error_log("Search error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit();
        }
    }
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Handle manual linking request
if (isset($_GET['link_resident'])) {
    $residentId = intval($_GET['resident_id']);
    $patientId = intval($_GET['patient_id']);
    
    try {
        $result = manuallyLinkToPatientRecord($pdo, $residentId, $patientId);
        $_SESSION['message'] = $result;
        $_SESSION['message_type'] = 'success';
        header('Location: manage_accounts.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = 'Error linking: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: manage_accounts.php');
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle staff password change with current password verification
    if (isset($_POST['change_staff_password'])) {
        $staffId = intval($_POST['staff_id']);
        $currentPassword = trim($_POST['current_password']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['message'] = 'All password fields are required.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = 'New passwords do not match.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        try {
            // Get staff current password
            $stmt = $pdo->prepare("SELECT id, password FROM sitio1_staff WHERE id = ?");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$staff) {
                $_SESSION['message'] = 'Staff not found.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $staff['password'])) {
                $_SESSION['message'] = 'Current password is incorrect.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_staff SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $staffId]);
            
            $_SESSION['message'] = 'Staff password changed successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: manage_accounts.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error changing password: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    }
    // Handle resident password change with current password verification
    elseif (isset($_POST['change_resident_password'])) {
        $residentId = intval($_POST['resident_id']);
        $currentPassword = trim($_POST['current_password']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['message'] = 'All password fields are required.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = 'New passwords do not match.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        try {
            // Get resident current password
            $stmt = $pdo->prepare("SELECT id, password FROM sitio1_users WHERE id = ? AND role = 'patient'");
            $stmt->execute([$residentId]);
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resident) {
                $_SESSION['message'] = 'Resident not found.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $resident['password'])) {
                $_SESSION['message'] = 'Current password is incorrect.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $residentId]);
            
            $_SESSION['message'] = 'Resident password changed successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: manage_accounts.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error changing password: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    }
    // Handle staff account creation
    elseif (isset($_POST['create_staff'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $fullName = trim($_POST['full_name']);
        $position = trim($_POST['position'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
        
        // Default work days: Monday to Friday working, Saturday-Sunday off
        $work_days = '1111100';
        
        if (!empty($username) && !empty($password) && !empty($fullName) && !empty($position)) {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM sitio1_staff WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Username already exists.';
                    $_SESSION['message_type'] = 'error';
                    header('Location: manage_accounts.php');
                    exit();
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO sitio1_staff (username, password, full_name, position, specialization, license_number, work_days, created_by, status, is_active) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)");
                $stmt->execute([$username, $hashedPassword, $fullName, $position, $specialization, $license_number, $work_days, $_SESSION['user_id']]);
                
                $_SESSION['message'] = 'Staff account created successfully! Password: ' . htmlspecialchars($password);
                $_SESSION['message_type'] = 'success';
                header('Location: manage_accounts.php');
                exit();
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Error: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
        } else {
            $_SESSION['message'] = 'Please fill in all required fields.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    } 
    // Handle resident account creation (SIMPLIFIED - NO AUTOMATIC PATIENT RECORD CREATION)
    elseif (isset($_POST['create_resident'])) {
        // 🔒 CORE FIELDS
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // 📱 OPTIONAL FIELDS
        $phone = trim($_POST['phone'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $sitio = trim($_POST['sitio'] ?? '');
        
        // Validate required fields
        if (empty($fullName) || empty($email) || empty($password)) {
            $_SESSION['message'] = 'Full name, email and password are required.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = 'Please enter a valid email address.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        // Generate username if not provided
        if (empty($username)) {
            $username = strtok($email, '@');
            $baseUsername = $username;
            $counter = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) {
                    break;
                }
                $username = $baseUsername . $counter;
                $counter++;
            }
        }
        
        // Validate date if provided
        $age = 0;
        if (!empty($dateOfBirth)) {
            $dobTimestamp = strtotime($dateOfBirth);
            if (!$dobTimestamp) {
                $_SESSION['message'] = 'Please enter a valid date of birth.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            $age = date('Y') - date('Y', $dobTimestamp);
            if (date('md', $dobTimestamp) > date('md')) {
                $age--;
            }
            
            if ($age < 0 || $age > 120) {
                $_SESSION['message'] = 'Please enter a valid date of birth (age must be between 0-120 years)';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
        }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['message'] = 'Email already exists.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $_SESSION['message'] = 'Username already exists.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Generate unique number
            if (!empty($sitio)) {
                $uniqueNumber = 'RES' . strtoupper(substr($sitio, 0, 3)) . date('Ym') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            } else {
                $uniqueNumber = 'RES' . date('Ymd') . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            // Generate Patient Record UID (will be used when linking later)
            $patientRecordUID = 'PAT-' . date('Ymd') . '-' . strtoupper(substr($fullName, 0, 3)) . '-' . mt_rand(1000, 9999);
            
            // Insert user WITHOUT linking to any patient record
            // Account will remain unlinked until admin manually links it
            $stmt = $pdo->prepare("INSERT INTO sitio1_users 
                (username, email, password, full_name, date_of_birth, age, gender, sitio, contact, 
                 approved, status, role, unique_number, verification_method, id_verified, 
                 verified_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'approved', 'patient', ?, 
                        'manual_verification', 1, NOW(), NOW())");
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([
                $username, 
                $email, 
                $hashedPassword, 
                $fullName, 
                !empty($dateOfBirth) ? $dateOfBirth : null,
                $age,
                !empty($gender) ? $gender : null,
                !empty($sitio) ? $sitio : null,
                !empty($phone) ? $phone : null,
                $uniqueNumber
            ]);
            
            $residentUserId = $pdo->lastInsertId();
            
            // ✅ **NO PATIENT RECORD CREATION - ACCOUNT STANDS ALONE**
            // Patient record will be linked later via manual linking
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['message'] = 'Resident account created successfully! Password: ' . htmlspecialchars($password) . ' Account is ready for patient record linking.';
            $_SESSION['message_type'] = 'success';
            header('Location: manage_accounts.php');
            exit();
            
        } catch (PDOException $e) {
            // Rollback on error
            $pdo->rollBack();
            $_SESSION['message'] = 'Error creating resident account: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    }
    // Handle resident password reset (admin reset without current password)
    elseif (isset($_POST['reset_resident_password'])) {
        $residentId = intval($_POST['resident_id']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        if (empty($newPassword)) {
            $_SESSION['message'] = 'New password is required.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = 'Passwords do not match.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
        
        try {
            // Verify resident exists
            $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE id = ? AND role = 'patient'");
            $stmt->execute([$residentId]);
            if (!$stmt->fetch()) {
                $_SESSION['message'] = 'Resident not found.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $residentId]);
            
            $_SESSION['message'] = 'Resident password reset successfully! New password: ' . htmlspecialchars($newPassword);
            $_SESSION['message_type'] = 'success';
            header('Location: manage_accounts.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error resetting password: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    }
    // Handle resident status toggle
    elseif (isset($_POST['toggle_resident_status'])) {
        $residentId = intval($_POST['resident_id']);
        $action = $_POST['action'];
        
        if (in_array($action, ['approve', 'decline', 'suspend'])) {
            try {
                $newStatus = ($action === 'approve') ? 'approved' : ($action === 'decline' ? 'declined' : 'suspended');
                
                $stmt = $pdo->prepare("UPDATE sitio1_users SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $residentId]);
                
                $_SESSION['message'] = 'Resident account ' . $action . 'd successfully!';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_accounts.php');
                exit();
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Error updating resident: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
        }
    }
    // Handle staff status toggle
    elseif (isset($_POST['toggle_staff_status'])) {
        $staffId = intval($_POST['staff_id']);
        $action = $_POST['action'];
        
        if (in_array($action, ['activate', 'deactivate'])) {
            try {
                $newStatus = ($action === 'activate') ? 'active' : 'inactive';
                $isActive = ($action === 'activate') ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE sitio1_staff SET status = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$newStatus, $isActive, $staffId]);
                
                $_SESSION['message'] = 'Staff account ' . $action . 'd successfully!';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_accounts.php');
                exit();
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Error updating account: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
        }
    } 
    // Handle staff deletion
    elseif (isset($_POST['hard_delete'])) {
        $staffId = intval($_POST['staff_id']);
        
        try {
            // Check for dependencies
            $dependencies = [];
            
            // Check appointments
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_appointments WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $appointmentsCount = $stmt->fetchColumn();
            if ($appointmentsCount > 0) {
                $dependencies[] = "$appointmentsCount appointment(s)";
            }
            
            // Check announcements
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_announcements WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $announcementsCount = $stmt->fetchColumn();
            if ($announcementsCount > 0) {
                $dependencies[] = "$announcementsCount announcement(s)";
            }
            
            // Check consultations
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_consultations WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $consultationsCount = $stmt->fetchColumn();
            if ($consultationsCount > 0) {
                $dependencies[] = "$consultationsCount consultation(s)";
            }
            
            // Check patient records
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE added_by = ?");
            $stmt->execute([$staffId]);
            $patientsCount = $stmt->fetchColumn();
            if ($patientsCount > 0) {
                $dependencies[] = "$patientsCount patient record(s)";
            }
            
            // Check prescriptions
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_prescriptions WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $prescriptionsCount = $stmt->fetchColumn();
            if ($prescriptionsCount > 0) {
                $dependencies[] = "$prescriptionsCount prescription(s)";
            }
            
            // If dependencies exist, handle them
            if (!empty($dependencies)) {
                $deleteAction = $_POST['delete_action'] ?? 'reassign';
                $reassignTo = intval($_POST['reassign_to'] ?? 0);
                
                $pdo->beginTransaction();
                
                try {
                    if ($deleteAction === 'reassign' && $reassignTo > 0) {
                        // Reassign appointments
                        $stmt = $pdo->prepare("UPDATE sitio1_appointments SET staff_id = ? WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Reassign consultations
                        $stmt = $pdo->prepare("UPDATE sitio1_consultations SET staff_id = ? WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Reassign patient records
                        $stmt = $pdo->prepare("UPDATE sitio1_patients SET added_by = ? WHERE added_by = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Reassign prescriptions
                        $stmt = $pdo->prepare("UPDATE sitio1_prescriptions SET staff_id = ? WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Set announcements to NULL
                        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $_SESSION['message'] = 'Staff account deleted and records reassigned successfully!';
                    } else {
                        // Delete dependent records
                        $stmt = $pdo->prepare("DELETE FROM sitio1_appointments WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("DELETE FROM sitio1_consultations WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("UPDATE sitio1_patients SET added_by = NULL WHERE added_by = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("DELETE FROM sitio1_prescriptions WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $_SESSION['message'] = 'Staff account and associated records deleted successfully!';
                    }
                    
                    // Delete staff account
                    $stmt = $pdo->prepare("DELETE FROM sitio1_staff WHERE id = ?");
                    $stmt->execute([$staffId]);
                    
                    $pdo->commit();
                    $_SESSION['message_type'] = 'success';
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                // No dependencies, delete directly
                $stmt = $pdo->prepare("DELETE FROM sitio1_staff WHERE id = ?");
                $stmt->execute([$staffId]);
                
                $_SESSION['message'] = 'Staff account deleted successfully!';
                $_SESSION['message_type'] = 'success';
            }
            
            header('Location: manage_accounts.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error deleting account: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }
    }
}

/**
 * Function to manually link resident account to patient records
 */
function manuallyLinkToPatientRecord($pdo, $residentUserId, $patientId, $patientRecordUID = null) {
    $resultMessage = '';
    
    try {
        // Verify patient exists and is not already linked
        $stmt = $pdo->prepare("SELECT id, full_name, user_id FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$patient) {
            return '⚠️ Selected patient record not found.';
        }
        
        if ($patient['user_id'] !== null) {
            if ($patient['user_id'] == $residentUserId) {
                return 'ℹ️ Patient record already linked to this account.';
            }
            return '⚠️ Patient record already linked to another account.';
        }
        
        // Generate UID if provided
        if (!$patientRecordUID) {
            $patientRecordUID = 'PAT-' . date('Ymd') . '-' . strtoupper(substr($patient['full_name'], 0, 3)) . '-' . mt_rand(1000, 9999);
        }
        
        // Check if patient_record_uid column exists in patients table
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_patients LIKE 'patient_record_uid'");
        $patientUidColumnExists = $stmt->fetch();
        
        if ($patientUidColumnExists) {
            // Link with UID
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET user_id = ?, patient_record_uid = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$residentUserId, $patientRecordUID, $patientId]);
        } else {
            // Link without UID
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET user_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$residentUserId, $patientId]);
        }
        
        // Check if patient_record_uid column exists in users table
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_users LIKE 'patient_record_uid'");
        $userUidColumnExists = $stmt->fetch();
        
        if ($userUidColumnExists) {
            // Update user record with patient_record_uid
            $stmt = $pdo->prepare("UPDATE sitio1_users SET patient_record_uid = ? WHERE id = ?");
            $stmt->execute([$patientRecordUID, $residentUserId]);
        }
        
        $resultMessage = '✅ Manually linked to patient: ' . htmlspecialchars($patient['full_name']);
        if ($patientRecordUID) {
            $resultMessage .= ' (UID: ' . $patientRecordUID . ')';
        }
        
        return $resultMessage;
        
    } catch (PDOException $e) {
        return '⚠️ Manual linking failed: ' . $e->getMessage();
    }
}

/**
 * Function to get unlinked residents (accounts without patient records)
 */
function getUnlinkedResidents($pdo) {
    try {
        // Alternative query that doesn't use patient_record_uid
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM sitio1_users u
            LEFT JOIN sitio1_patients p ON u.id = p.user_id
            WHERE u.role = 'patient' 
            AND u.status = 'approved'
            AND p.id IS NULL
            ORDER BY u.created_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting unlinked residents: " . $e->getMessage());
        return [];
    }
}

/**
 * Function to get unlinked patient records (without user accounts)
 */
function getUnlinkedPatients($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM sitio1_patients p
        WHERE p.user_id IS NULL
        AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all staff accounts
$activeStaff = [];
$inactiveStaff = [];

// Get all resident accounts
$pendingResidents = [];
$approvedResidents = [];
$declinedResidents = [];
$unlinkedResidents = [];

// Get unlinked patient records
$unlinkedPatients = [];

// Get staff for reassignment
$allStaff = [];

try {
    // Active staff
    $stmt = $pdo->query("SELECT s.*, creator.username as creator_username 
                         FROM sitio1_staff s
                         LEFT JOIN sitio1_staff creator ON s.created_by = creator.id
                         WHERE s.is_active = 1
                         ORDER BY s.created_at DESC");
    $activeStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inactive staff
    $stmt = $pdo->query("SELECT s.*, creator.username as creator_username 
                         FROM sitio1_staff s
                         LEFT JOIN sitio1_staff creator ON s.created_by = creator.id
                         WHERE s.is_active = 0
                         ORDER BY s.created_at DESC");
    $inactiveStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All active staff for reassignment
    $stmt = $pdo->query("SELECT id, full_name, username FROM sitio1_staff WHERE is_active = 1 ORDER BY full_name");
    $allStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pending residents
    $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'pending' ORDER BY created_at DESC");
    $pendingResidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Approved residents
    $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'approved' ORDER BY created_at DESC");
    $approvedResidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Declined residents
    $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'declined' ORDER BY created_at DESC");
    $declinedResidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Unlinked residents
    $unlinkedResidents = getUnlinkedResidents($pdo);
    
    // Unlinked patient records
    $unlinkedPatients = getUnlinkedPatients($pdo);
} catch (PDOException $e) {
    $_SESSION['message'] = 'Error loading data: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - Barangay Luz Health Center</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #60a5fa;
            --primary-dark: #1d4ed8;
            --primary-bg: #f0f9ff;
            --primary-border: #bae6fd;
            --success: #10b981;
            --success-light: #34d399;
            --success-dark: #059669;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --danger: #ef4444;
            --danger-light: #f87171;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            line-height: 1.5;
        }
        
        /* Enhanced Header */
        .main-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Consistent Card Design */
        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.08);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-bg) 0%, #e0f2fe 100%);
            border-bottom: 1px solid var(--primary-border);
            padding: 1.5rem 2rem;
        }
        
        /* Consistent Button Design */
        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 42px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--success-dark) 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            color: white;
        }
        
        .btn-warning:hover:not(:disabled) {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.25);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.25);
        }
        
        .btn-outline {
            background: white;
            border: 2px solid var(--gray-300);
            color: var(--gray-700);
        }
        
        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Consistent Tabs */
        .tabs-container {
            background: white;
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            background: transparent;
            color: var(--gray-600);
        }
        
        .tab-btn.active {
            background: var(--primary-bg);
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .tab-btn:hover:not(.active) {
            background: var(--gray-50);
            color: var(--gray-800);
        }
        
        /* Consistent Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 10px;
            font-size: 0.9375rem;
            transition: all 0.3s;
            background: white;
            color: var(--gray-800);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input:read-only {
            background-color: var(--gray-50);
            cursor: not-allowed;
        }
        
        /* Password Toggle */
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-500);
            padding: 6px;
            border-radius: 6px;
        }
        
        .password-toggle:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        
        .password-container .form-input {
            padding-right: 46px;
        }
        
        /* Account Cards */
        .account-card {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: all 0.3s;
            padding: 1.5rem;
        }
        
        .account-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-border);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .stat-card h3 {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        
        /* Consistent Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 520px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--gray-200);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Consistent Badges */
        .badge {
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            letter-spacing: 0.02em;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        /* Manual Linking Styles */
        .link-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f7fa 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .link-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 1024px) {
            .link-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .link-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 0.75rem;
            background: white;
        }
        
        .link-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
        }
        
        .link-item:hover {
            background: var(--gray-50);
        }
        
        .link-item.selected {
            background: var(--primary-bg);
            border-left: 3px solid var(--primary);
        }
        
        /* Message Modal */
        .message-modal {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            height: auto;
            transform: translateX(120%);
            transition: transform 0.3s ease-in-out;
            pointer-events: none;
        }
        
        .message-modal.show {
            transform: translateX(0);
            pointer-events: auto;
        }
        
        .message-content {
            background: white;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            border: 2px solid transparent;
            animation: slideInRight 0.3s ease-out;
        }
        
        .message-content.success {
            border-color: var(--success);
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }
        
        .message-content.error {
            border-color: var(--danger);
            background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
        }
        
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .message-icon {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        
        .message-icon.success {
            color: var(--success);
        }
        
        .message-icon.error {
            color: var(--danger);
        }
        
        .message-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-900);
        }
        
        .message-body {
            color: var(--gray-700);
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        
        .message-close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .message-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--gray-600);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Progress Bar */
        .message-progress {
            height: 4px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
            margin-top: 0.75rem;
            overflow: hidden;
            position: relative;
        }
        
        .message-progress-bar {
            height: 100%;
            width: 100%;
            border-radius: 2px;
            transition: width 3s linear;
        }
        
        .message-progress-bar.success {
            background: var(--success);
        }
        
        .message-progress-bar.error {
            background: var(--danger);
        }
        
        /* Selection Styles */
        .resident-card.selected {
            border-color: var(--primary) !important;
            background: var(--primary-bg);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .patient-card.selected {
            border-color: var(--success) !important;
            background: #f0fdf4;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
            transform: translateY(-2px);
        }
        
        /* Consistent Spacing */
        .section-spacing {
            margin-bottom: 2.5rem;
        }
        
        .grid-spacing {
            gap: 1.5rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .grid-container {
                grid-template-columns: 1fr;
            }
            
            .tabs-container {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                text-align: center;
            }
            
            .link-grid {
                grid-template-columns: 1fr;
            }
            
            .message-modal {
                width: calc(100% - 40px) !important;
                right: 20px;
                left: 20px;
                max-width: none;
            }
            
            .account-card {
                padding: 1.25rem;
            }
            
            .modal-content {
                padding: 1.5rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Visual Hierarchy */
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
        }
        
        .page-subtitle {
            font-size: 1rem;
            color: var(--gray-600);
            margin-top: 0.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
        }
        
        .subsection-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1rem;
        }
        
        /* Animation for section transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Loading states */
        .loading {
            position: relative;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Focus states for accessibility */
        .focusable:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }
    </style>
</head>
<body class="min-h-screen">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    
    <!-- Message Modal -->
    <?php if (isset($_SESSION['message']) && ($_SESSION['message_type'] ?? 'success') === 'success'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showMessageModal(<?= json_encode($_SESSION['message']) ?>, 'success');
        });
    </script>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <main class="container mx-auto px-4 py-8 mt-16">
        <!-- Page Header -->
        <div class="section-spacing">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                <div>
                    <h1 class="page-title">Account Management</h1>
                    <p class="page-subtitle">Manage staff and resident accounts with patient record linking</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="stat-card">
                        <h3>Active Staff</h3>
                        <div class="number"><?= count($activeStaff) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Residents</h3>
                        <div class="number"><?= count($pendingResidents) + count($approvedResidents) + count($declinedResidents) ?></div>
                    </div>
                    <?php if (count($unlinkedResidents) > 0): ?>
                    <div class="stat-card">
                        <h3>Unlinked</h3>
                        <div class="number text-warning"><?= count($unlinkedResidents) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Tabs -->
        <div class="tabs-container">
            <div class="flex flex-wrap gap-2">
                <button class="tab-btn active focusable" onclick="showSection('staff')" id="staff-tab" aria-selected="true" aria-controls="staff-section">
                    <i class="fas fa-user-md mr-2"></i> Staff Management
                    <span class="badge badge-info ml-2"><?= count($activeStaff) + count($inactiveStaff) ?></span>
                </button>
                <button class="tab-btn focusable" onclick="showSection('resident')" id="resident-tab" aria-selected="false" aria-controls="resident-section">
                    <i class="fas fa-users mr-2"></i> Resident Management
                    <span class="badge badge-success ml-2"><?= count($pendingResidents) + count($approvedResidents) + count($declinedResidents) ?></span>
                </button>
                <?php if (count($unlinkedResidents) > 0 || count($unlinkedPatients) > 0): ?>
                <button class="tab-btn focusable" onclick="showSection('linking')" id="linking-tab" aria-selected="false" aria-controls="linking-section">
                    <i class="fas fa-link mr-2"></i> Manual Linking
                    <span class="badge badge-warning ml-2"><?= count($unlinkedResidents) + count($unlinkedPatients) ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Management Section -->
        <section id="staff-section" class="fade-in section-spacing" aria-labelledby="staff-tab">
            <!-- Create Staff Form -->
            <div class="card mb-10">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-plus mr-3 text-primary"></i>
                        Create New Staff Account
                    </h2>
                    <p class="text-gray-600 mt-1">Add healthcare staff members to the system</p>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" required class="form-input" placeholder="Enter username">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Password <span class="text-red-500">*</span></label>
                            <div class="password-container">
                                <input type="password" name="password" required id="staff-password" class="form-input" placeholder="Enter password">
                                <button type="button" class="password-toggle" onclick="togglePassword('staff-password')" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Password will be visible for reference</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" required class="form-input" placeholder="Enter full name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Position <span class="text-red-500">*</span></label>
                            <select name="position" required class="form-input">
                                <option value="">Select Position</option>
                                <option value="Nurse">Nurse</option>
                                <option value="Midwife">Midwife</option>
                                <option value="Doctor">Doctor</option>
                                <option value="Encoder">Encoder</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-input" placeholder="e.g., Pediatrics, OB-GYN, General Medicine">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">License Number</label>
                            <input type="text" name="license_number" class="form-input" placeholder="Enter license number (if applicable)">
                        </div>
                        
                        <div class="md:col-span-2">
                            <button type="submit" name="create_staff" class="btn btn-primary px-8 py-3">
                                <i class="fas fa-user-plus mr-2"></i> Create Staff Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Staff Account Management -->
            <div class="mb-6">
                <div class="tabs-container inline-flex">
                    <button class="tab-btn active" onclick="showStaffTab('active')">
                        <i class="fas fa-check-circle mr-2"></i>
                        Active Staff (<?= count($activeStaff) ?>)
                    </button>
                    <button class="tab-btn" onclick="showStaffTab('inactive')">
                        <i class="fas fa-pause-circle mr-2"></i>
                        Inactive Staff (<?= count($inactiveStaff) ?>)
                    </button>
                </div>
            </div>
            
            <!-- Active Staff Tab -->
            <div id="active-staff-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing">
                <?php if (empty($activeStaff)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-user-md text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No active staff accounts</h3>
                        <p class="text-gray-500">Create your first staff account above</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeStaff as $staff): ?>
                        <div class="account-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                    <p class="text-primary text-sm font-medium">@<?= htmlspecialchars($staff['username']) ?></p>
                                </div>
                                <span class="badge badge-success">Active</span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <?php if ($staff['position']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-briefcase text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($staff['position']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($staff['specialization']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-stethoscope text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($staff['specialization']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($staff['license_number']): ?>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-id-card text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($staff['license_number']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-sm text-gray-400">
                                    <i class="fas fa-calendar-alt mr-3 w-5"></i>
                                    Added <?= date('M j, Y', strtotime($staff['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button onclick="showChangeStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn btn-outline flex-1 min-w-[120px]">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" name="toggle_staff_status" 
                                            class="btn btn-warning w-full"
                                            onclick="return confirm('Deactivate this staff account?')">
                                        <i class="fas fa-pause mr-2"></i> Deactivate
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Inactive Staff Tab -->
            <div id="inactive-staff-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing" style="display: none;">
                <?php if (empty($inactiveStaff)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-user-slash text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No inactive staff accounts</h3>
                        <p class="text-gray-500">All staff accounts are currently active</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($inactiveStaff as $staff): ?>
                        <div class="account-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                    <p class="text-gray-500 text-sm font-medium">@<?= htmlspecialchars($staff['username']) ?></p>
                                </div>
                                <span class="badge badge-error">Inactive</span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <?php if ($staff['position']): ?>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-briefcase text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($staff['position']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-sm text-gray-400">
                                    <i class="fas fa-calendar-alt mr-3 w-5"></i>
                                    Added <?= date('M j, Y', strtotime($staff['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button onclick="showChangeStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn btn-outline flex-1 min-w-[120px]">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" name="toggle_staff_status" 
                                            class="btn btn-success w-full"
                                            onclick="return confirm('Reactivate this account?')">
                                        <i class="fas fa-play mr-2"></i> Activate
                                    </button>
                                </form>
                                
                                <button onclick="showDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn btn-danger flex-1 min-w-[120px]">
                                    <i class="fas fa-trash mr-2"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Resident Management Section -->
        <section id="resident-section" class="fade-in section-spacing" style="display: none;" aria-labelledby="resident-tab">
            <!-- Create Resident Form -->
            <div class="card mb-10">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-plus mr-3 text-success"></i>
                        Create New Resident Account
                    </h2>
                    <p class="text-gray-600 mt-1">Create resident accounts for patient record linking</p>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="" id="resident-form" class="space-y-8">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="full_name" required class="form-input" placeholder="e.g. Juan Dela Cruz">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" required class="form-input" placeholder="juan@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-input" placeholder="Leave blank for auto-generation">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password <span class="text-red-500">*</span></label>
                                <div class="password-container">
                                    <input type="password" name="password" required id="resident-password" class="form-input" placeholder="••••••••">
                                    <button type="button" class="password-toggle" onclick="togglePassword('resident-password')" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">Password will be visible for reference</p>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input" placeholder="+63 912 345 6789">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date-of-birth" onchange="calculateAge()" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="gender" class="form-input">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Sitio -->
                        <div class="form-group">
                            <label class="form-label">Sitio</label>
                            <select name="sitio" id="sitio" class="form-input">
                                <option value="">Select Sitio</option>
                                <option value="Kalinao">Kalinao</option>
                                <option value="Nangka">Nangka</option>
                                <option value="Lubi">Lubi</option>
                                <option value="Sta. Cruz">Sta. Cruz</option>
                                <option value="Regla">Regla</option>
                                <option value="Abellana">Abellana</option>
                                <option value="Sto.niño l">Sto.niño l</option>
                                <option value="Sto.niño ll">Sto.niño ll</option>
                                <option value="Sto.niño lll">Sto.niño lll</option>
                                <option value="Zapatera">Zapatera</option>
                                <option value="Mabuhay">Mabuhay</option>
                                <option value="San Vicente">San Vicente</option>
                                <option value="City Central">City Central</option>
                                <option value="San. Antonio">San. Antonio</option>
                                <option value="San Roque">San Roque</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        
                        <!-- Age Display -->
                        <div id="age-display" class="hidden">
                            <div class="inline-flex items-center px-3 py-1 bg-gray-100 rounded-full">
                                <span class="text-sm font-medium text-gray-700">Age: <span id="calculated-age">0</span> years</span>
                            </div>
                        </div>
                        
                        <!-- Information Note -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-500 text-lg mt-0.5 mr-3"></i>
                                <div>
                                    <p class="font-medium text-blue-800 mb-1">Important Information</p>
                                    <p class="text-sm text-blue-600">
                                        This account will be created without a patient record. 
                                        To link this account to a patient record, go to the 
                                        <span class="font-semibold">"Manual Linking"</span> tab after creating the account.
                                        Patient records are added separately by admin staff.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit" name="create_resident" class="btn btn-success px-8 py-3">
                                <i class="fas fa-plus-circle mr-2"></i> Create Resident Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resident Account Management -->
            <div class="mb-6">
                <div class="tabs-container inline-flex flex-wrap">
                    <button class="tab-btn active" onclick="showResidentTab('pending')">
                        <i class="fas fa-clock mr-2"></i>
                        Pending (<?= count($pendingResidents) ?>)
                    </button>
                    <button class="tab-btn" onclick="showResidentTab('approved')">
                        <i class="fas fa-check-circle mr-2"></i>
                        Approved (<?= count($approvedResidents) ?>)
                    </button>
                    <button class="tab-btn" onclick="showResidentTab('declined')">
                        <i class="fas fa-times-circle mr-2"></i>
                        Declined (<?= count($declinedResidents) ?>)
                    </button>
                    <?php if (count($unlinkedResidents) > 0): ?>
                    <button class="tab-btn" onclick="showResidentTab('unlinked')">
                        <i class="fas fa-unlink mr-2"></i>
                        Unlinked (<?= count($unlinkedResidents) ?>)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Residents Tab -->
            <div id="pending-residents-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing">
                <?php if (empty($pendingResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-50 rounded-full mb-4">
                            <i class="fas fa-clock text-2xl text-yellow-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No pending resident accounts</h3>
                        <p class="text-gray-500">All applications have been processed</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingResidents as $resident): ?>
                        <div class="account-card">
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user text-xl"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                <p class="text-gray-500 text-sm font-medium">@<?= htmlspecialchars($resident['username']) ?></p>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                
                                <?php if ($resident['sitio']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($resident['sitio']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($resident['age'] > 0): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-user text-gray-400 mr-3 w-5"></i>
                                        Age: <?= htmlspecialchars($resident['age']) ?> years
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-sm text-gray-400">
                                    <i class="fas fa-calendar-plus text-gray-400 mr-3 w-5"></i>
                                    Applied <?= date('M j, Y', strtotime($resident['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="toggle_resident_status" class="btn btn-success w-full">
                                        <i class="fas fa-check mr-2"></i> Approve
                                    </button>
                                </form>
                                
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" name="toggle_resident_status" class="btn btn-danger w-full">
                                        <i class="fas fa-times mr-2"></i> Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Approved Residents Tab -->
            <div id="approved-residents-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing" style="display: none;">
                <?php if (empty($approvedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-50 rounded-full mb-4">
                            <i class="fas fa-check-circle text-2xl text-green-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No approved residents</h3>
                        <p class="text-gray-500">Approve some pending accounts to see them here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approvedResidents as $resident): ?>
                        <div class="account-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-success text-sm font-medium">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="badge badge-success">Approved</span>
                            </div>
                            
                            <?php 
                            // Check if account has linked patient record
                            $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
                            $stmt->execute([$resident['id']]);
                            $hasPatientRecord = $stmt->fetch();
                            ?>
                            
                            <?php if (!$hasPatientRecord): ?>
                            <span class="badge badge-warning mb-4">Unlinked Account</span>
                            <?php else: ?>
                            <span class="badge badge-info mb-4">Linked Account</span>
                            <?php endif; ?>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                
                                <?php if ($resident['contact']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-phone text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($resident['contact']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-id-card text-gray-400 mr-3 w-5"></i>
                                    ID: <?= htmlspecialchars($resident['unique_number']) ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button onclick="showChangeResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                        class="btn btn-outline flex-1 min-w-[120px]">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" name="toggle_resident_status" 
                                            class="btn btn-warning w-full"
                                            onclick="return confirm('Suspend this account?')">
                                        <i class="fas fa-pause mr-2"></i> Suspend
                                    </button>
                                </form>
                                
                                <?php if (!$hasPatientRecord): ?>
                                <a href="?section=linking&focus_resident=<?= $resident['id'] ?>" 
                                   class="btn btn-outline flex-1 min-w-[120px] text-center">
                                    <i class="fas fa-link mr-2"></i> Link Record
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Declined Residents Tab -->
            <div id="declined-residents-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing" style="display: none;">
                <?php if (empty($declinedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4">
                            <i class="fas fa-times-circle text-2xl text-red-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No declined residents</h3>
                        <p class="text-gray-500">No resident applications have been declined</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($declinedResidents as $resident): ?>
                        <div class="account-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-gray-500 text-sm font-medium">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="badge badge-error">Declined</span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-400">
                                    <i class="fas fa-calendar-times text-gray-400 mr-3 w-5"></i>
                                    Declined <?= date('M j, Y', strtotime($resident['updated_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button onclick="showChangeResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                        class="btn btn-outline flex-1 min-w-[120px]">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="toggle_resident_status" 
                                            class="btn btn-success w-full"
                                            onclick="return confirm('Approve this declined account?')">
                                        <i class="fas fa-check mr-2"></i> Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Unlinked Residents Tab -->
            <div id="unlinked-residents-tab" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-spacing" style="display: none;">
                <?php if (empty($unlinkedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-50 rounded-full mb-4">
                            <i class="fas fa-unlink text-2xl text-orange-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">All accounts are linked!</h3>
                        <p class="text-gray-500">All resident accounts have patient records linked</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($unlinkedResidents as $resident): ?>
                        <div class="account-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-warning text-sm font-medium">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="badge badge-warning">Unlinked</span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                
                                <?php if ($resident['age'] > 0): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-user text-gray-400 mr-3 w-5"></i>
                                        Age: <?= htmlspecialchars($resident['age']) ?> years
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($resident['sitio']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-3 w-5"></i>
                                        <?= htmlspecialchars($resident['sitio']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button onclick="showChangeResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                        class="btn btn-outline flex-1 min-w-[120px]">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                                
                                <a href="?section=linking&focus_resident=<?= $resident['id'] ?>" 
                                   class="btn btn-primary flex-1 min-w-[120px] text-center">
                                    <i class="fas fa-link mr-2"></i> Link Record
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Manual Linking Section -->
        <section id="linking-section" class="fade-in" style="display: none;" aria-labelledby="linking-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-link mr-3 text-purple-600"></i>
                        Manual Account-Patient Linking
                    </h2>
                    <p class="text-gray-600 mt-1">Link resident accounts to existing patient records</p>
                </div>
                
                <div class="p-6">
                    <?php if (count($unlinkedResidents) === 0 && count($unlinkedPatients) === 0): ?>
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-50 rounded-full mb-4">
                                <i class="fas fa-check-circle text-2xl text-green-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">All accounts are properly linked!</h3>
                            <p class="text-gray-500">No manual linking needed at this time.</p>
                        </div>
                    <?php else: ?>
                        <!-- Link Section -->
                        <div class="mb-8">
                            <h3 class="subsection-title mb-6">
                                <i class="fas fa-handshake text-blue-600 mr-2"></i>
                                Link Accounts to Patient Records
                            </h3>
                            
                            <!-- Grid Layout -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                                <!-- Unlinked Residents Column -->
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-semibold text-gray-700 text-base">
                                            <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                            Unlinked Resident Accounts
                                            <span class="badge badge-info ml-2"><?= count($unlinkedResidents) ?></span>
                                        </h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="resident-grid">
                                        <?php foreach ($unlinkedResidents as $resident): ?>
                                            <div class="account-card resident-card cursor-pointer"
                                                 data-resident-id="<?= $resident['id'] ?>"
                                                 onclick="selectResidentCard(this, <?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>', '<?= htmlspecialchars($resident['email']) ?>', '<?= htmlspecialchars($resident['sitio'] ?? '') ?>', <?= $resident['age'] ?? 0 ?>)">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                        <i class="fas fa-user text-blue-500"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                        <div class="text-xs text-gray-500 truncate mt-1">
                                                            <?= htmlspecialchars($resident['email']) ?>
                                                        </div>
                                                        <div class="flex items-center gap-2 mt-2">
                                                            <?php if ($resident['sitio']): ?>
                                                                <span class="inline-flex items-center gap-1 text-xs text-gray-600 bg-gray-100 rounded-full px-2 py-1">
                                                                    <i class="fas fa-map-marker-alt text-xs"></i>
                                                                    <?= htmlspecialchars($resident['sitio']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($resident['age'] > 0): ?>
                                                                <span class="text-xs text-gray-600">Age: <?= htmlspecialchars($resident['age']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Unlinked Patients Column -->
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-semibold text-gray-700 text-base">
                                            <i class="fas fa-file-medical text-green-500 mr-2"></i>
                                            Unlinked Patient Records
                                            <span class="badge badge-success ml-2"><?= count($unlinkedPatients) ?></span>
                                        </h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="patient-grid">
                                        <?php foreach ($unlinkedPatients as $patient): ?>
                                            <div class="account-card patient-card cursor-pointer"
                                                 data-patient-id="<?= $patient['id'] ?>"
                                                 onclick="selectPatientCard(this, <?= $patient['id'] ?>, '<?= htmlspecialchars($patient['full_name']) ?>', <?= $patient['age'] ?? 0 ?>, '<?= htmlspecialchars($patient['gender'] ?? '') ?>', '<?= htmlspecialchars($patient['sitio'] ?? '') ?>')">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                        <i class="fas fa-file-medical text-green-500"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                        <div class="flex flex-wrap gap-2 mt-2">
                                                            <?php if ($patient['age']): ?>
                                                                <span class="inline-flex items-center gap-1 text-xs text-gray-600 bg-gray-100 rounded-full px-2 py-1">
                                                                    <i class="fas fa-birthday-cake text-xs"></i>
                                                                    <?= htmlspecialchars($patient['age']) ?> yrs
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($patient['gender']): ?>
                                                                <span class="inline-flex items-center gap-1 text-xs text-gray-600 bg-gray-100 rounded-full px-2 py-1">
                                                                    <i class="fas fa-venus-mars text-xs"></i>
                                                                    <?= htmlspecialchars($patient['gender']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selected Items Panel -->
                            <div class="bg-gradient-to-r from-blue-50 to-green-50 border-2 border-blue-200 rounded-lg p-6 mb-6 shadow-sm" id="selected-items-panel" style="display: none;">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                            <i class="fas fa-handshake text-blue-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg">Ready to Link</h4>
                                            <p class="text-sm text-gray-600">Selected items for linking</p>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-outline"
                                            onclick="clearLinkingSelection()">
                                        <i class="fas fa-times mr-2"></i> Clear Selection
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Selected Resident Card -->
                                    <div class="bg-white rounded-xl border-2 border-blue-200 p-4" id="selected-resident-card">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-500"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-semibold text-gray-700 text-sm">Selected Resident</h5>
                                                <div class="text-gray-800 font-medium text-base" id="selected-resident-name">No resident selected</div>
                                            </div>
                                            <div class="badge badge-info">
                                                Account
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500" id="selected-resident-details">
                                            Select a resident account from the left panel
                                        </div>
                                    </div>
                                    
                                    <!-- Selected Patient Card -->
                                    <div class="bg-white rounded-xl border-2 border-green-200 p-4" id="selected-patient-card">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-file-medical text-green-500"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-semibold text-gray-700 text-sm">Selected Patient Record</h5>
                                                <div class="text-gray-800 font-medium text-base" id="selected-patient-name">No patient selected</div>
                                            </div>
                                            <div class="badge badge-success">
                                                Record
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500" id="selected-patient-details">
                                            Select a patient record from the right panel
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <button type="button" 
                                            class="btn btn-primary w-full md:w-auto"
                                            id="link-action-button" 
                                            onclick="performLinking()" 
                                            disabled>
                                        <i class="fas fa-link mr-2"></i>
                                        <span id="link-button-text">Select Both Items</span>
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" id="selected-resident-id" value="0">
                            <input type="hidden" id="selected-patient-id" value="0">
                        </div>
                        
                        <!-- Information Section -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-lg p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-yellow-500 text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-yellow-800 mb-3">How This Works:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-sm font-bold mt-0.5">
                                                1
                                            </div>
                                            <p class="text-sm text-yellow-700">Create resident accounts (no patient records initially)</p>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-sm font-bold mt-0.5">
                                                2
                                            </div>
                                            <p class="text-sm text-yellow-700">Add patient records separately through patient management</p>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-sm font-bold mt-0.5">
                                                3
                                            </div>
                                            <p class="text-sm text-yellow-700">Link accounts to records here (one account = one record)</p>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-sm font-bold mt-0.5">
                                                4
                                            </div>
                                            <p class="text-sm text-yellow-700">Residents can view medical history after linking</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Delete Staff Account</h3>
                    <p class="text-gray-600 text-sm mt-1" id="delete-message"></p>
                </div>
            </div>
            
            <form method="POST" action="" id="delete-form">
                <input type="hidden" name="staff_id" id="delete-staff-id">
                
                <div class="mb-6">
                    <label class="block font-semibold text-gray-700 mb-3">Handle Dependent Records:</label>
                    
                    <div class="space-y-3">
                        <label class="flex items-start p-3 border rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="radio" name="delete_action" value="reassign" checked class="mt-1 mr-3">
                            <div class="flex-1">
                                <span class="font-medium text-gray-700">Reassign to another staff</span>
                                <select name="reassign_to" class="form-input mt-2" required>
                                    <option value="">Select staff member</option>
                                    <?php foreach ($allStaff as $staff): ?>
                                        <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                            <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['full_name']) ?> (@<?= htmlspecialchars($staff['username']) ?>)</option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </label>
                        
                        <label class="flex items-start p-3 border rounded-lg hover:bg-red-50 cursor-pointer transition-colors">
                            <input type="radio" name="delete_action" value="delete" class="mt-1 mr-3">
                            <div class="flex-1">
                                <span class="font-medium text-red-600">Delete all associated records</span>
                                <p class="text-sm text-red-500 mt-1">Warning: This action cannot be undone</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-outline">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" name="hard_delete" class="btn btn-danger">
                        <i class="fas fa-trash mr-2"></i> Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Staff Password Modal -->
    <div id="changeStaffPasswordModal" class="modal">
        <div class="modal-content max-w-md">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-key text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Change Staff Password</h3>
                        <p class="text-gray-600 text-sm mt-1">Update staff member's password securely</p>
                    </div>
                </div>
                <button type="button" onclick="closeChangeStaffPasswordModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="change-staff-password-form" onsubmit="return validateStaffPasswordForm()">
                <input type="hidden" name="staff_id" id="change-staff-id">
                
                <!-- Staff Info Display -->
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-md text-blue-600"></i>
                        <div>
                            <p class="text-xs text-blue-600 font-semibold uppercase tracking-wide">Staff Member</p>
                            <p class="text-lg font-bold text-gray-800" id="change-staff-name"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Current Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        Current Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="current_password" required 
                               id="staff-current-password"
                               class="form-input" 
                               placeholder="Enter current password"
                               oninput="checkPasswordMatch('staff')">
                        <button type="button" class="password-toggle" onclick="togglePassword('staff-current-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- New Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key mr-2 text-gray-400"></i>
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="new_password" required 
                               id="staff-new-password"
                               class="form-input" 
                               placeholder="Enter new password"
                               minlength="6"
                               oninput="checkPasswordMatch('staff')">
                        <button type="button" class="password-toggle" onclick="togglePassword('staff-new-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group mb-8">
                    <label class="form-label">
                        <i class="fas fa-check-double mr-2 text-gray-400"></i>
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" required 
                               id="staff-confirm-password"
                               class="form-input" 
                               placeholder="Confirm new password"
                               minlength="6"
                               oninput="checkPasswordMatch('staff')">
                        <button type="button" class="password-toggle" onclick="togglePassword('staff-confirm-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="staff-password-match-message" class="text-sm mt-2 hidden"></div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeChangeStaffPasswordModal()" class="btn btn-outline flex-1">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" name="change_staff_password" class="btn btn-primary flex-1">
                        <i class="fas fa-save mr-2"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Resident Password Modal -->
    <div id="changeResidentPasswordModal" class="modal">
        <div class="modal-content max-w-md">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-key text-green-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Change Resident Password</h3>
                        <p class="text-gray-600 text-sm mt-1">Update resident account password securely</p>
                    </div>
                </div>
                <button type="button" onclick="closeChangeResidentPasswordModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="change-resident-password-form" onsubmit="return validateResidentPasswordForm()">
                <input type="hidden" name="resident_id" id="change-resident-id">
                
                <!-- Resident Info Display -->
                <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user text-green-600"></i>
                        <div>
                            <p class="text-xs text-green-600 font-semibold uppercase tracking-wide">Resident</p>
                            <p class="text-lg font-bold text-gray-800" id="change-resident-name"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Current Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        Current Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="current_password" required 
                               id="resident-current-password"
                               class="form-input" 
                               placeholder="Enter current password"
                               oninput="checkPasswordMatch('resident')">
                        <button type="button" class="password-toggle" onclick="togglePassword('resident-current-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- New Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key mr-2 text-gray-400"></i>
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="new_password" required 
                               id="resident-new-password"
                               class="form-input" 
                               placeholder="Enter new password"
                               minlength="6"
                               oninput="checkPasswordMatch('resident')">
                        <button type="button" class="password-toggle" onclick="togglePassword('resident-new-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group mb-8">
                    <label class="form-label">
                        <i class="fas fa-check-double mr-2 text-gray-400"></i>
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" required 
                               id="resident-confirm-password"
                               class="form-input" 
                               placeholder="Confirm new password"
                               minlength="6"
                               oninput="checkPasswordMatch('resident')">
                        <button type="button" class="password-toggle" onclick="togglePassword('resident-confirm-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="resident-password-match-message" class="text-sm mt-2 hidden"></div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeChangeResidentPasswordModal()" class="btn btn-outline flex-1">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" name="change_resident_password" class="btn btn-success flex-1">
                        <i class="fas fa-save mr-2"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal (Admin Reset - No Current Password) -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content max-w-md">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-key text-red-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Reset Resident Password</h3>
                        <p class="text-gray-600 text-sm mt-1">Admin password reset (no current password required)</p>
                    </div>
                </div>
                <button type="button" onclick="closeResetModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="reset-password-form">
                <input type="hidden" name="resident_id" id="reset-resident-id">
                
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user text-red-600"></i>
                        <div>
                            <p class="text-xs text-red-600 font-semibold uppercase tracking-wide">Resident</p>
                            <p class="text-lg font-bold text-gray-800" id="reset-resident-name"></p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password <span class="text-red-500">*</span></label>
                    <div class="password-container">
                        <input type="password" name="new_password" required 
                               id="reset-new-password"
                               class="form-input" 
                               placeholder="Enter new password"
                               minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('reset-new-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group mb-8">
                    <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" required 
                               id="reset-confirm-password"
                               class="form-input" 
                               placeholder="Confirm new password"
                               minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('reset-confirm-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeResetModal()" class="btn btn-outline flex-1">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" name="reset_resident_password" class="btn btn-danger flex-1">
                        <i class="fas fa-redo mr-2"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== GLOBAL VARIABLES =====
        let selectedResidentId = 0;
        let selectedPatientId = 0;

        // ===== PASSWORD TOGGLE FUNCTION =====
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggleButton = input.parentElement.querySelector('.password-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                toggleButton.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                toggleButton.setAttribute('aria-label', 'Show password');
            }
        }
        
        // ===== PASSWORD VALIDATION FUNCTIONS =====
        function validateStaffPasswordForm() {
            const currentPassword = document.getElementById('staff-current-password').value;
            const newPassword = document.getElementById('staff-new-password').value;
            const confirmPassword = document.getElementById('staff-confirm-password').value;
            
            // Validate all fields filled
            if (!currentPassword || !newPassword || !confirmPassword) {
                showMessageModal('All password fields are required.', 'error');
                return false;
            }
            
            // Validate minimum length
            if (newPassword.length < 6) {
                showMessageModal('New password must be at least 6 characters long.', 'error');
                return false;
            }
            
            // Validate passwords match
            if (newPassword !== confirmPassword) {
                showMessageModal('New passwords do not match. Please try again.', 'error');
                return false;
            }
            
            // Validate new password is different from current
            if (currentPassword === newPassword) {
                showMessageModal('New password must be different from current password.', 'error');
                return false;
            }
            
            return true;
        }
        
        function validateResidentPasswordForm() {
            const currentPassword = document.getElementById('resident-current-password').value;
            const newPassword = document.getElementById('resident-new-password').value;
            const confirmPassword = document.getElementById('resident-confirm-password').value;
            
            // Validate all fields filled
            if (!currentPassword || !newPassword || !confirmPassword) {
                showMessageModal('All password fields are required.', 'error');
                return false;
            }
            
            // Validate minimum length
            if (newPassword.length < 6) {
                showMessageModal('New password must be at least 6 characters long.', 'error');
                return false;
            }
            
            // Validate passwords match
            if (newPassword !== confirmPassword) {
                showMessageModal('New passwords do not match. Please try again.', 'error');
                return false;
            }
            
            // Validate new password is different from current
            if (currentPassword === newPassword) {
                showMessageModal('New password must be different from current password.', 'error');
                return false;
            }
            
            return true;
        }
        
        // ===== REAL-TIME PASSWORD MATCH VALIDATION =====
        function checkPasswordMatch(type) {
            const newPasswordId = type === 'staff' ? 'staff-new-password' : 'resident-new-password';
            const confirmPasswordId = type === 'staff' ? 'staff-confirm-password' : 'resident-confirm-password';
            const messageId = type === 'staff' ? 'staff-password-match-message' : 'resident-password-match-message';
            
            const newPassword = document.getElementById(newPasswordId).value;
            const confirmPassword = document.getElementById(confirmPasswordId).value;
            const message = document.getElementById(messageId);
            
            if (confirmPassword.length === 0) {
                message.classList.add('hidden');
                return;
            }
            
            message.classList.remove('hidden');
            
            if (newPassword === confirmPassword) {
                message.className = 'text-sm mt-2 text-green-600 flex items-center gap-2';
                message.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match!';
            } else {
                message.className = 'text-sm mt-2 text-red-600 flex items-center gap-2';
                message.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
            }
        }
        
        // ===== AGE CALCULATION =====
        function calculateAge() {
            const dobInput = document.getElementById('date-of-birth');
            const ageDisplay = document.getElementById('age-display');
            const calculatedAge = document.getElementById('calculated-age');
            
            if (!dobInput.value) {
                ageDisplay.classList.add('hidden');
                return;
            }
            
            const dob = new Date(dobInput.value);
            const today = new Date();
            
            if (dob > today) {
                showMessageModal('Date of birth cannot be in the future', 'error');
                dobInput.value = '';
                ageDisplay.classList.add('hidden');
                return;
            }
            
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            if (age < 0 || age > 120) {
                showMessageModal('Please enter a valid date of birth (age 0-120)', 'error');
                dobInput.value = '';
                ageDisplay.classList.add('hidden');
                return;
            }
            
            calculatedAge.textContent = age;
            ageDisplay.classList.remove('hidden');
        }
        
        // ===== MESSAGE MODAL =====
        function showMessageModal(message, type = 'success') {
            const modal = document.getElementById('messageModal');
            if (!modal) {
                // Create modal if it doesn't exist
                createMessageModal(message, type);
                return;
            }
            
            // Update content
            const content = modal.querySelector('.message-content');
            const icon = modal.querySelector('.message-icon');
            const title = modal.querySelector('.message-title');
            const body = modal.querySelector('.message-body');
            const progressBar = document.getElementById('messageProgressBar');
            
            content.className = 'message-content ' + type;
            icon.className = 'message-icon ' + type + ' fas ' + (type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle');
            title.className = 'message-title';
            title.textContent = type === 'error' ? 'Error' : 'Success';
            body.textContent = message;
            
            // Set dynamic width class based on message length
            const msgLength = message.length;
            let widthClass = 'short';
            if (msgLength < 50) widthClass = 'short';
            else if (msgLength < 80) widthClass = 'medium';
            else if (msgLength < 120) widthClass = 'long';
            else if (msgLength < 160) widthClass = 'extra-long';
            else widthClass = 'max';
            
            modal.className = 'message-modal ' + widthClass;
            
            // Reset progress bar
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.transition = 'none';
                void progressBar.offsetWidth; // Trigger reflow
                progressBar.style.transition = 'width 3s linear';
                progressBar.style.width = '0%';
            }
            
            // Show modal
            modal.classList.add('show');
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                closeMessageModal();
            }, 3000);
        }
        
        function createMessageModal(message, type) {
            const modal = document.createElement('div');
            modal.id = 'messageModal';
            modal.className = 'message-modal';
            
            // Calculate width class
            const msgLength = message.length;
            let widthClass = 'short';
            if (msgLength < 50) widthClass = 'short';
            else if (msgLength < 80) widthClass = 'medium';
            else if (msgLength < 120) widthClass = 'long';
            else if (msgLength < 160) widthClass = 'extra-long';
            else widthClass = 'max';
            
            modal.className = 'message-modal ' + widthClass;
            
            modal.innerHTML = `
                <div class="message-content ${type}">
                    <button class="message-close" onclick="closeMessageModal()" aria-label="Close message">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="message-header">
                        <i class="message-icon ${type} fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                        <span class="message-title">
                            ${type === 'error' ? 'Error' : 'Success'}
                        </span>
                    </div>
                    <div class="message-body">
                        ${message}
                    </div>
                    <div class="message-progress">
                        <div class="message-progress-bar ${type}" id="messageProgressBar"></div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Reset progress bar
            const progressBar = document.getElementById('messageProgressBar');
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.transition = 'none';
                void progressBar.offsetWidth; // Trigger reflow
                progressBar.style.transition = 'width 3s linear';
                progressBar.style.width = '0%';
            }
            
            // Show modal
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                closeMessageModal();
            }, 3000);
        }
        
        function closeMessageModal() {
            const modal = document.getElementById('messageModal');
            if (modal) {
                modal.classList.remove('show');
                // Remove from DOM after animation
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                }, 300);
            }
        }
        
        // ===== TAB MANAGEMENT =====
        function showSection(section) {
            // Hide all sections
            document.getElementById('staff-section').style.display = 'none';
            document.getElementById('resident-section').style.display = 'none';
            document.getElementById('linking-section').style.display = 'none';
            
            // Remove active class from all main tabs
            document.querySelectorAll('.tabs-container .tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Add active class to clicked tab
            event.currentTarget.classList.add('active');
            event.currentTarget.setAttribute('aria-selected', 'true');
            
            // If switching to linking tab, check if we need to focus on a specific resident
            if (section === 'linking' && window.location.search.includes('focus_resident=')) {
                const urlParams = new URLSearchParams(window.location.search);
                const residentId = urlParams.get('focus_resident');
                if (residentId) {
                    setTimeout(() => {
                        const residentCard = document.querySelector(`[data-resident-id="${residentId}"]`);
                        if (residentCard) {
                            residentCard.click();
                            residentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 300);
                }
            }
        }
        
        function showStaffTab(tab) {
            // Hide all staff tabs
            document.getElementById('active-staff-tab').style.display = 'none';
            document.getElementById('inactive-staff-tab').style.display = 'none';
            
            // Remove active class from all buttons
            document.querySelectorAll('#staff-section .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-staff-tab').style.display = 'grid';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function showResidentTab(tab) {
            // Hide all resident tabs
            const tabs = ['pending', 'approved', 'declined', 'unlinked'];
            tabs.forEach(t => {
                const element = document.getElementById(t + '-residents-tab');
                if (element) element.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('#resident-section .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            const selectedTab = document.getElementById(tab + '-residents-tab');
            if (selectedTab) selectedTab.style.display = 'grid';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // ===== MODAL FUNCTIONS =====
        function showDeleteModal(staffId, staffName) {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                document.getElementById('delete-staff-id').value = staffId;
                document.getElementById('delete-message').textContent = 
                    `Are you sure you want to delete "${staffName}"? This action will affect associated records.`;
                modal.classList.add('show');
            }
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('delete-form').reset();
            }
        }
        
        function showChangeStaffPasswordModal(staffId, staffName) {
            const modal = document.getElementById('changeStaffPasswordModal');
            if (modal) {
                document.getElementById('change-staff-id').value = staffId;
                document.getElementById('change-staff-name').textContent = staffName;
                modal.classList.add('show');
            }
        }
        
        function closeChangeStaffPasswordModal() {
            const modal = document.getElementById('changeStaffPasswordModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('change-staff-password-form').reset();
                const message = document.getElementById('staff-password-match-message');
                if (message) message.classList.add('hidden');
            }
        }
        
        function showChangeResidentPasswordModal(residentId, residentName) {
            const modal = document.getElementById('changeResidentPasswordModal');
            if (modal) {
                document.getElementById('change-resident-id').value = residentId;
                document.getElementById('change-resident-name').textContent = residentName;
                modal.classList.add('show');
            }
        }
        
        function closeChangeResidentPasswordModal() {
            const modal = document.getElementById('changeResidentPasswordModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('change-resident-password-form').reset();
                const message = document.getElementById('resident-password-match-message');
                if (message) message.classList.add('hidden');
            }
        }
        
        function showResetPasswordModal(residentId, residentName) {
            const modal = document.getElementById('resetPasswordModal');
            if (modal) {
                document.getElementById('reset-resident-id').value = residentId;
                document.getElementById('reset-resident-name').textContent = residentName;
                modal.classList.add('show');
            }
        }
        
        function closeResetModal() {
            const modal = document.getElementById('resetPasswordModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('reset-password-form').reset();
            }
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            const modals = [
                'deleteModal',
                'changeStaffPasswordModal',
                'changeResidentPasswordModal',
                'resetPasswordModal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    if (modalId === 'deleteModal') closeDeleteModal();
                    if (modalId === 'changeStaffPasswordModal') closeChangeStaffPasswordModal();
                    if (modalId === 'changeResidentPasswordModal') closeChangeResidentPasswordModal();
                    if (modalId === 'resetPasswordModal') closeResetModal();
                }
            });
        }
        
        // ===== LINKING FUNCTIONS =====
        function selectResidentCard(element, residentId, residentName, email, sitio, age) {
            // Remove previous selection from resident cards
            document.querySelectorAll('.resident-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection
            element.classList.add('selected');
            selectedResidentId = residentId;
            document.getElementById('selected-resident-id').value = residentId;
            
            // Update display
            document.getElementById('selected-resident-name').textContent = residentName;
            document.getElementById('selected-resident-details').innerHTML = `
                <div class="mb-1"><i class="fas fa-envelope text-xs text-gray-400 mr-2"></i>${email || 'No email'}</div>
                <div class="mb-1"><i class="fas fa-map-marker-alt text-xs text-gray-400 mr-2"></i>${sitio || 'Not specified'}</div>
                <div><i class="fas fa-user text-xs text-gray-400 mr-2"></i>Age: ${age > 0 ? age : 'Not specified'}</div>
            `;
            
            // Show selected items panel
            document.getElementById('selected-items-panel').style.display = 'block';
            
            // Enable link button if both selected
            updateLinkButton();
        }
        
        function selectPatientCard(element, patientId, patientName, age, gender, sitio) {
            // Remove previous selection from patient cards
            document.querySelectorAll('.patient-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection
            element.classList.add('selected');
            selectedPatientId = patientId;
            document.getElementById('selected-patient-id').value = patientId;
            
            // Update display
            document.getElementById('selected-patient-name').textContent = patientName;
            document.getElementById('selected-patient-details').innerHTML = `
                <div class="mb-1"><i class="fas fa-id-badge text-xs text-gray-400 mr-2"></i>Record ID: ${patientId}</div>
                <div class="mb-1"><i class="fas fa-venus-mars text-xs text-gray-400 mr-2"></i>${gender || 'Not specified'}</div>
                <div><i class="fas fa-map-marker-alt text-xs text-gray-400 mr-2"></i>${sitio || 'Not specified'}</div>
                ${age > 0 ? '<div><i class="fas fa-birthday-cake text-xs text-gray-400 mr-2"></i>Age: ' + age + ' years</div>' : ''}
            `;
            
            // Show selected items panel
            document.getElementById('selected-items-panel').style.display = 'block';
            
            // Enable link button if both selected
            updateLinkButton();
        }
        
        function updateLinkButton() {
            const linkButton = document.getElementById('link-action-button');
            const linkButtonText = document.getElementById('link-button-text');
            
            if (selectedResidentId > 0 && selectedPatientId > 0) {
                linkButton.disabled = false;
                linkButtonText.textContent = 'Link Accounts Now';
            } else {
                linkButton.disabled = true;
                linkButtonText.textContent = 'Select Both Items';
            }
        }
        
        function clearLinkingSelection() {
            // Clear selections
            selectedResidentId = 0;
            selectedPatientId = 0;
            document.getElementById('selected-resident-id').value = '0';
            document.getElementById('selected-patient-id').value = '0';
            
            // Remove selection classes
            document.querySelectorAll('.resident-card, .patient-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Reset displays
            document.getElementById('selected-resident-name').textContent = 'No resident selected';
            document.getElementById('selected-resident-details').innerHTML = 'Select a resident account from the left panel';
            document.getElementById('selected-patient-name').textContent = 'No patient selected';
            document.getElementById('selected-patient-details').innerHTML = 'Select a patient record from the right panel';
            
            // Hide selected items panel
            document.getElementById('selected-items-panel').style.display = 'none';
            
            // Reset link button
            updateLinkButton();
        }
        
        function performLinking() {
            if (selectedResidentId === 0 || selectedPatientId === 0) {
                showMessageModal('Please select both a resident account and a patient record.', 'error');
                return;
            }
            
            if (confirm('Are you sure you want to link these accounts?\n\n✓ Resident will be able to view their medical history\n✓ One-to-one linking ensured\n✓ Cannot be undone without admin access')) {
                // Show loading
                const linkButton = document.getElementById('link-action-button');
                const linkButtonText = document.getElementById('link-button-text');
                linkButtonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Linking...';
                linkButton.disabled = true;
                
                // Perform linking
                window.location.href = `?link_resident=1&resident_id=${selectedResidentId}&patient_id=${selectedPatientId}`;
            }
        }
        
        // ===== INITIALIZATION =====
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generate username from email
            const emailInput = document.querySelector('input[name="email"]');
            const usernameInput = document.querySelector('input[name="username"]');
            
            if (emailInput && usernameInput) {
                emailInput.addEventListener('blur', function() {
                    if (!usernameInput.value && this.value) {
                        const username = this.value.split('@')[0];
                        usernameInput.value = username;
                    }
                });
            }
            
            // Check URL for section parameter
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            if (section === 'linking') {
                // Switch to linking tab
                const linkingTab = document.getElementById('linking-tab');
                if (linkingTab) {
                    showSection('linking');
                    document.getElementById('staff-tab').classList.remove('active');
                    document.getElementById('resident-tab').classList.remove('active');
                    linkingTab.classList.add('active');
                }
            }
            
            // Make passwords visible by default for better UX
            setTimeout(() => {
                const staffPassword = document.getElementById('staff-password');
                if (staffPassword) {
                    staffPassword.type = 'text';
                    const toggleBtn = staffPassword.parentElement.querySelector('.password-toggle');
                    if (toggleBtn) {
                        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    }
                }
                
                const residentPassword = document.getElementById('resident-password');
                if (residentPassword) {
                    residentPassword.type = 'text';
                    const toggleBtn = residentPassword.parentElement.querySelector('.password-toggle');
                    if (toggleBtn) {
                        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    }
                }
            }, 100);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key closes modals
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeChangeStaffPasswordModal();
                closeChangeResidentPasswordModal();
                closeResetModal();
                closeMessageModal();
            }
        });
    </script>
</body>
</html>