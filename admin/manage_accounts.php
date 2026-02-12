<?php
require_once __DIR__ . '/../includes/auth.php';

// --- Auto-logout for resident users after 10 minutes of inactivity ---
if (isUser()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 600) {
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

// ============================================================================
// HANDLE ALL AJAX REQUESTS
// ============================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search_patients') {
    handlePatientSearch($pdo);
    exit();
}

// Handle AJAX form submissions
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'staff_password_change':
                    echo json_encode(handleStaffPasswordChangeAJAX($pdo));
                    break;
                case 'resident_password_change':
                    echo json_encode(handleResidentPasswordChangeAJAX($pdo));
                    break;
                case 'admin_password_reset':
                    echo json_encode(handleAdminPasswordResetAJAX($pdo));
                    break;
                case 'create_staff':
                    echo json_encode(handleCreateStaffAJAX($pdo));
                    break;
                case 'create_resident':
                    echo json_encode(handleCreateResidentAJAX($pdo));
                    break;
                case 'toggle_resident':
                    echo json_encode(handleToggleResidentAJAX($pdo));
                    break;
                case 'toggle_staff':
                    echo json_encode(handleToggleStaffAJAX($pdo));
                    break;
                case 'delete_staff':
                    echo json_encode(handleDeleteStaffAJAX($pdo));
                    break;
                case 'link_accounts':
                    echo json_encode(handleLinkAccountsAJAX($pdo));
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================================================
// HANDLE ALL POST REQUESTS (Form Submissions - Fallback)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'staff_password_change':
                handleStaffPasswordChange($pdo);
                break;
            case 'resident_password_change':
                handleResidentPasswordChange($pdo);
                break;
            case 'admin_password_reset':
                handleAdminPasswordReset($pdo);
                break;
            case 'create_staff':
                handleCreateStaff($pdo);
                break;
            case 'create_resident':
                handleCreateResident($pdo);
                break;
            case 'toggle_resident':
                handleToggleResident($pdo);
                break;
            case 'toggle_staff':
                handleToggleStaff($pdo);
                break;
            case 'delete_staff':
                handleDeleteStaff($pdo);
                break;
            case 'link_accounts':
                handleLinkAccounts($pdo);
                break;
        }
        exit();
    }
}

// ============================================================================
// HANDLE GET PARAMETERS (Direct Links)
// ============================================================================
if (isset($_GET['link'])) {
    handleDirectLinking($pdo);
    exit();
}

// ============================================================================
// DATA COLLECTION - LOAD ALL ACCOUNTS
// ============================================================================
$accountData = loadAllAccountData($pdo);

// ============================================================================
// FUNCTION DEFINITIONS
// ============================================================================

/**
 * Handle patient search AJAX request
 */
function handlePatientSearch($pdo) {
    $term = trim($_GET['term'] ?? '');
    
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit();
    }
    
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
        
        $stmt->execute(['%' . $term . '%']);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($patients);
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error occurred']);
    }
    exit();
}

/**
 * Handle staff password change with current password verification - AJAX version
 */
function handleStaffPasswordChangeAJAX($pdo) {
    $staffId = intval($_POST['staff_id'] ?? 0);
    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');
    
    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        return ['success' => false, 'message' => 'All password fields are required.'];
    }
    
    if ($newPass !== $confirmPass) {
        return ['success' => false, 'message' => 'New passwords do not match.'];
    }
    
    if (strlen($newPass) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM sitio1_staff WHERE id = ?");
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch();
        
        if (!$staff) {
            return ['success' => false, 'message' => 'Staff account not found.'];
        }
        
        if (!password_verify($currentPass, $staff['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        if (password_verify($newPass, $staff['password'])) {
            return ['success' => false, 'message' => 'New password must be different from current password.'];
        }
        
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE sitio1_staff SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed, $staffId]);
        
        return ['success' => true, 'message' => 'Staff password updated successfully!'];
        
    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
    }
}

/**
 * Handle staff password change - Traditional version
 */
function handleStaffPasswordChange($pdo) {
    $result = handleStaffPasswordChangeAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle resident password change with current password verification - AJAX version
 */
function handleResidentPasswordChangeAJAX($pdo) {
    $residentId = intval($_POST['resident_id'] ?? 0);
    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');
    
    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        return ['success' => false, 'message' => 'All password fields are required.'];
    }
    
    if ($newPass !== $confirmPass) {
        return ['success' => false, 'message' => 'New passwords do not match.'];
    }
    
    if (strlen($newPass) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM sitio1_users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$residentId]);
        $resident = $stmt->fetch();
        
        if (!$resident) {
            return ['success' => false, 'message' => 'Resident account not found.'];
        }
        
        if (!password_verify($currentPass, $resident['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        if (password_verify($newPass, $resident['password'])) {
            return ['success' => false, 'message' => 'New password must be different from current password.'];
        }
        
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed, $residentId]);
        
        return ['success' => true, 'message' => 'Resident password updated successfully!'];
        
    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
    }
}

/**
 * Handle resident password change - Traditional version
 */
function handleResidentPasswordChange($pdo) {
    $result = handleResidentPasswordChangeAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle admin password reset (no current password required) - AJAX version
 */
function handleAdminPasswordResetAJAX($pdo) {
    $residentId = intval($_POST['resident_id'] ?? 0);
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');
    
    if (empty($newPass) || empty($confirmPass)) {
        return ['success' => false, 'message' => 'All password fields are required.'];
    }
    
    if ($newPass !== $confirmPass) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }
    
    if (strlen($newPass) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$residentId]);
        $resident = $stmt->fetch();
        
        if (!$resident) {
            return ['success' => false, 'message' => 'Resident account not found.'];
        }
        
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed, $residentId]);
        
        return ['success' => true, 'message' => "Password reset successful! New password: {$newPass}"];
        
    } catch (PDOException $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
    }
}

/**
 * Handle admin password reset - Traditional version
 */
function handleAdminPasswordReset($pdo) {
    $result = handleAdminPasswordResetAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle staff account creation - AJAX version
 */
function handleCreateStaffAJAX($pdo) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    
    if (empty($username) || empty($password) || empty($fullName) || empty($position)) {
        return ['success' => false, 'message' => 'Please fill in all required fields.'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM sitio1_staff WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        $workDays = '1111100';
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO sitio1_staff 
            (username, password, full_name, position, specialization, license_number, work_days, created_by, status, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW())
        ");
        
        $stmt->execute([$username, $hashedPass, $fullName, $position, $specialization, $license, $workDays, $_SESSION['user_id']]);
        
        return ['success' => true, 'message' => "Staff account created successfully! Password: {$password}"];
        
    } catch (PDOException $e) {
        error_log("Create staff error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error creating account. Please try again.'];
    }
}

/**
 * Handle staff account creation - Traditional version
 */
function handleCreateStaff($pdo) {
    $result = handleCreateStaffAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle resident account creation - AJAX version
 */
function handleCreateResidentAJAX($pdo) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $sitio = trim($_POST['sitio'] ?? '');
    
    if (empty($fullName) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Full name, email and password are required.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        if (empty($username)) {
            $username = strtok($email, '@');
            $baseUsername = $username;
            $counter = 1;
            
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) break;
                $username = $baseUsername . $counter;
                $counter++;
            }
        } else {
            $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Username already exists.'];
            }
        }
        
        $age = 0;
        if (!empty($dob)) {
            $dobTimestamp = strtotime($dob);
            if (!$dobTimestamp) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Please enter a valid date of birth.'];
            }
            
            $age = date('Y') - date('Y', $dobTimestamp);
            if (date('md', $dobTimestamp) > date('md')) $age--;
            
            if ($age < 0 || $age > 120) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Please enter a valid date of birth (age 0-120).'];
            }
        }
        
        if (!empty($sitio)) {
            $uniqueNum = 'RES' . strtoupper(substr($sitio, 0, 3)) . date('Ym') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        } else {
            $uniqueNum = 'RES' . date('Ymd') . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO sitio1_users 
            (username, email, password, full_name, date_of_birth, age, gender, sitio, contact, 
             approved, status, role, unique_number, verification_method, id_verified, verified_at, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'approved', 'patient', ?, 'manual_verification', 1, NOW(), NOW())
        ");
        
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $stmt->execute([
            $username, $email, $hashedPass, $fullName, 
            !empty($dob) ? $dob : null, $age, 
            !empty($gender) ? $gender : null, 
            !empty($sitio) ? $sitio : null, 
            !empty($phone) ? $phone : null,
            $uniqueNum
        ]);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => "Resident account created successfully! Password: {$password} | Account ready for patient record linking."];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Create resident error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error creating account. Please try again.'];
    }
}

/**
 * Handle resident account creation - Traditional version
 */
function handleCreateResident($pdo) {
    $result = handleCreateResidentAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle toggle resident status - AJAX version
 */
function handleToggleResidentAJAX($pdo) {
    $residentId = intval($_POST['resident_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    
    $validStatuses = ['approved', 'declined', 'suspended'];
    if (!in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid status action.'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE sitio1_users SET status = ?, updated_at = NOW() WHERE id = ? AND role = 'patient'");
        $stmt->execute([$status, $residentId]);
        
        $action = ($status === 'approved' ? 'approved' : ($status === 'declined' ? 'declined' : 'suspended'));
        return ['success' => true, 'message' => "Resident account {$action} successfully!"];
        
    } catch (PDOException $e) {
        error_log("Toggle resident error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating account status.'];
    }
}

/**
 * Handle toggle resident status - Traditional version
 */
function handleToggleResident($pdo) {
    $result = handleToggleResidentAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle toggle staff status - AJAX version
 */
function handleToggleStaffAJAX($pdo) {
    $staffId = intval($_POST['staff_id'] ?? 0);
    $action = trim($_POST['toggle_action'] ?? '');
    
    if (!in_array($action, ['activate', 'deactivate'])) {
        return ['success' => false, 'message' => 'Invalid action.'];
    }
    
    try {
        $status = ($action === 'activate') ? 'active' : 'inactive';
        $isActive = ($action === 'activate') ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE sitio1_staff SET status = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $isActive, $staffId]);
        
        return ['success' => true, 'message' => "Staff account {$action}d successfully!"];
        
    } catch (PDOException $e) {
        error_log("Toggle staff error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating staff account.'];
    }
}

/**
 * Handle toggle staff status - Traditional version
 */
function handleToggleStaff($pdo) {
    $result = handleToggleStaffAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle delete staff account with dependency management - AJAX version
 */
function handleDeleteStaffAJAX($pdo) {
    $staffId = intval($_POST['staff_id'] ?? 0);
    $deleteAction = trim($_POST['delete_action'] ?? 'reassign');
    $reassignTo = intval($_POST['reassign_to'] ?? 0);
    
    try {
        $dependencies = checkStaffDependencies($pdo, $staffId);
        
        $pdo->beginTransaction();
        
        if (!empty($dependencies) && $deleteAction === 'reassign' && $reassignTo > 0) {
            reassignStaffRecords($pdo, $staffId, $reassignTo);
            $message = 'Staff account deleted and records reassigned successfully!';
            
        } elseif (!empty($dependencies) && $deleteAction === 'delete') {
            deleteStaffDependencies($pdo, $staffId);
            $message = 'Staff account and associated records deleted successfully!';
            
        } else {
            $message = 'Staff account deleted successfully!';
        }
        
        $stmt = $pdo->prepare("DELETE FROM sitio1_staff WHERE id = ?");
        $stmt->execute([$staffId]);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => $message];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete staff error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error deleting staff account.'];
    }
}

/**
 * Handle delete staff account - Traditional version
 */
function handleDeleteStaff($pdo) {
    $result = handleDeleteStaffAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle linking resident account to patient record - AJAX version
 */
function handleLinkAccountsAJAX($pdo) {
    $residentId = intval($_POST['resident_id'] ?? 0);
    $patientId = intval($_POST['patient_id'] ?? 0);
    
    if ($residentId <= 0 || $patientId <= 0) {
        return ['success' => false, 'message' => 'Invalid resident or patient ID.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id, full_name, user_id FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Patient record not found.'];
        }
        
        if ($patient['user_id'] !== null) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Patient record is already linked to another account.'];
        }
        
        $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$residentId]);
        $resident = $stmt->fetch();
        
        if (!$resident) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Resident account not found.'];
        }
        
        $patientUID = 'PAT-' . date('Ymd') . '-' . strtoupper(substr($patient['full_name'], 0, 3)) . '-' . mt_rand(1000, 9999);
        
        $stmt = $pdo->prepare("UPDATE sitio1_patients SET user_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$residentId, $patientId]);
        
        $checkCol = $pdo->query("SHOW COLUMNS FROM sitio1_users LIKE 'patient_record_uid'");
        if ($checkCol->fetch()) {
            $stmt = $pdo->prepare("UPDATE sitio1_users SET patient_record_uid = ? WHERE id = ?");
            $stmt->execute([$patientUID, $residentId]);
        }
        
        $pdo->commit();
        
        return ['success' => true, 'message' => "✅ Successfully linked {$resident['full_name']} to patient record: {$patient['full_name']}"];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Linking error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error linking accounts. Please try again.'];
    }
}

/**
 * Handle linking resident account to patient record - Traditional version
 */
function handleLinkAccounts($pdo) {
    $result = handleLinkAccountsAJAX($pdo);
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirectBack();
}

/**
 * Handle direct linking via GET parameter
 */
function handleDirectLinking($pdo) {
    $residentId = intval($_GET['resident'] ?? 0);
    $patientId = intval($_GET['patient'] ?? 0);
    
    if ($residentId <= 0 || $patientId <= 0) {
        setFlashMessage('Invalid parameters for linking.', 'error');
        redirectTo('manage_accounts.php');
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id, full_name, user_id FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['user_id'] !== null) {
            setFlashMessage('Patient record unavailable for linking.', 'error');
            $pdo->rollBack();
            redirectTo('manage_accounts.php');
        }
        
        $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$residentId]);
        $resident = $stmt->fetch();
        
        if (!$resident) {
            setFlashMessage('Resident account not found.', 'error');
            $pdo->rollBack();
            redirectTo('manage_accounts.php');
        }
        
        $patientUID = 'PAT-' . date('Ymd') . '-' . strtoupper(substr($patient['full_name'], 0, 3)) . '-' . mt_rand(1000, 9999);
        
        $stmt = $pdo->prepare("UPDATE sitio1_patients SET user_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$residentId, $patientId]);
        
        $checkCol = $pdo->query("SHOW COLUMNS FROM sitio1_users LIKE 'patient_record_uid'");
        if ($checkCol->fetch()) {
            $stmt = $pdo->prepare("UPDATE sitio1_users SET patient_record_uid = ? WHERE id = ?");
            $stmt->execute([$patientUID, $residentId]);
        }
        
        $pdo->commit();
        
        setFlashMessage("✅ Successfully linked accounts!", 'success');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Direct linking error: " . $e->getMessage());
        setFlashMessage('Error linking accounts.', 'error');
    }
    
    redirectTo('manage_accounts.php');
}

/**
 * Check staff dependencies
 */
function checkStaffDependencies($pdo, $staffId) {
    $dependencies = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_appointments WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    if ($stmt->fetchColumn() > 0) $dependencies[] = 'appointments';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_consultations WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    if ($stmt->fetchColumn() > 0) $dependencies[] = 'consultations';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE added_by = ?");
    $stmt->execute([$staffId]);
    if ($stmt->fetchColumn() > 0) $dependencies[] = 'patients';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_prescriptions WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    if ($stmt->fetchColumn() > 0) $dependencies[] = 'prescriptions';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_announcements WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    if ($stmt->fetchColumn() > 0) $dependencies[] = 'announcements';
    
    return $dependencies;
}

/**
 * Reassign staff records to another staff
 */
function reassignStaffRecords($pdo, $oldStaffId, $newStaffId) {
    $stmt = $pdo->prepare("UPDATE sitio1_appointments SET staff_id = ? WHERE staff_id = ?");
    $stmt->execute([$newStaffId, $oldStaffId]);
    
    $stmt = $pdo->prepare("UPDATE sitio1_consultations SET staff_id = ? WHERE staff_id = ?");
    $stmt->execute([$newStaffId, $oldStaffId]);
    
    $stmt = $pdo->prepare("UPDATE sitio1_patients SET added_by = ? WHERE added_by = ?");
    $stmt->execute([$newStaffId, $oldStaffId]);
    
    $stmt = $pdo->prepare("UPDATE sitio1_prescriptions SET staff_id = ? WHERE staff_id = ?");
    $stmt->execute([$newStaffId, $oldStaffId]);
    
    $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL WHERE staff_id = ?");
    $stmt->execute([$oldStaffId]);
}

/**
 * Delete all staff dependencies
 */
function deleteStaffDependencies($pdo, $staffId) {
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
}

/**
 * Load all account data for display
 */
function loadAllAccountData($pdo) {
    $data = [
        'active_staff' => [],
        'inactive_staff' => [],
        'all_staff' => [],
        'pending_residents' => [],
        'approved_residents' => [],
        'declined_residents' => [],
        'unlinked_residents' => [],
        'unlinked_patients' => []
    ];
    
    try {
        $stmt = $pdo->query("
            SELECT s.*, creator.username as creator_username 
            FROM sitio1_staff s
            LEFT JOIN sitio1_staff creator ON s.created_by = creator.id
            WHERE s.is_active = 1
            ORDER BY s.created_at DESC
        ");
        $data['active_staff'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("
            SELECT s.*, creator.username as creator_username 
            FROM sitio1_staff s
            LEFT JOIN sitio1_staff creator ON s.created_by = creator.id
            WHERE s.is_active = 0
            ORDER BY s.created_at DESC
        ");
        $data['inactive_staff'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT id, full_name, username FROM sitio1_staff WHERE is_active = 1 ORDER BY full_name");
        $data['all_staff'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'pending' ORDER BY created_at DESC");
        $data['pending_residents'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'approved' ORDER BY created_at DESC");
        $data['approved_residents'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT * FROM sitio1_users WHERE role = 'patient' AND status = 'declined' ORDER BY created_at DESC");
        $data['declined_residents'] = $stmt->fetchAll();
        
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
        $data['unlinked_residents'] = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT p.* 
            FROM sitio1_patients p
            WHERE p.user_id IS NULL
            AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $data['unlinked_patients'] = $stmt->fetchAll();
        
        foreach ($data['approved_residents'] as &$resident) {
            $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
            $stmt->execute([$resident['id']]);
            $resident['has_patient_record'] = $stmt->fetch() ? true : false;
        }
        
    } catch (PDOException $e) {
        error_log("Data loading error: " . $e->getMessage());
        setFlashMessage('Error loading account data.', 'error');
    }
    
    return $data;
}

/**
 * Set flash message in session
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    $type = $_SESSION['flash_type'] ?? 'success';
    
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    
    return $message ? ['message' => $message, 'type' => $type] : null;
}

/**
 * Redirect back to referrer or fallback
 */
function redirectBack() {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'manage_accounts.php';
    header("Location: {$referer}");
    exit();
}

/**
 * Redirect to specific page
 */
function redirectTo($page) {
    header("Location: {$page}");
    exit();
}

// ============================================================================
// GET FLASH MESSAGE FOR DISPLAY
// ============================================================================
$flashMessage = getFlashMessage();

// ============================================================================
// EXTRACT DATA FOR EASY ACCESS IN HTML
// ============================================================================
$activeStaff = $accountData['active_staff'];
$inactiveStaff = $accountData['inactive_staff'];
$allStaff = $accountData['all_staff'];
$pendingResidents = $accountData['pending_residents'];
$approvedResidents = $accountData['approved_residents'];
$declinedResidents = $accountData['declined_residents'];
$unlinkedResidents = $accountData['unlinked_residents'];
$unlinkedPatients = $accountData['unlinked_patients'];

$totalStaff = count($activeStaff) + count($inactiveStaff);
$totalResidents = count($pendingResidents) + count($approvedResidents) + count($declinedResidents);
$totalUnlinked = count($unlinkedResidents) + count($unlinkedPatients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management System | Barangay Luz Health Center</title>
    
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --primary-bg: #eff6ff;
            --primary-border: #bfdbfe;
            
            --success: #16a34a;
            --success-light: #22c55e;
            --success-dark: #15803d;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            
            --warning: #ca8a04;
            --warning-light: #eab308;
            --warning-dark: #a16207;
            --warning-bg: #fefce8;
            --warning-border: #fef08a;
            
            --danger: #dc2626;
            --danger-light: #ef4444;
            --danger-dark: #b91c1c;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            
            --info: #2563eb;
            --info-light: #3b82f6;
            --info-bg: #eff6ff;
            --info-border: #bfdbfe;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .card {
            background: white;
            border-radius: var(--radius-xl);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            transition: all 0.25s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-border);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-bg), white);
            border-bottom: 1px solid var(--primary-border);
            padding: 1.5rem 2rem;
        }
        
        .card-body {
            padding: 1.5rem 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.5rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            min-height: 42px;
        }
        
        .btn i {
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(37, 99, 235, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: white;
            box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, var(--success-dark), #166534);
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(22, 163, 74, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark));
            color: white;
            box-shadow: 0 4px 6px -1px rgba(202, 138, 4, 0.2);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, var(--warning-dark), #854d0e);
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(202, 138, 4, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            color: white;
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, var(--danger-dark), #991b1b);
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(220, 38, 38, 0.3);
        }
        
        .btn-outline {
            background: white;
            border: 2px solid var(--gray-300);
            color: var(--gray-700);
        }
        
        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
            color: var(--gray-900);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-weight: 600;
            font-size: 0.813rem;
            color: var(--gray-700);
            letter-spacing: 0.01em;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 0.938rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--gray-800);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
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
            color: var(--gray-500);
            cursor: pointer;
            padding: 6px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }
        
        .password-toggle:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        
        .password-container .form-input {
            padding-right: 46px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.688rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }
        
        .badge-success {
            background: var(--success-bg);
            color: var(--success-dark);
            border: 1px solid var(--success-border);
        }
        
        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning-dark);
            border: 1px solid var(--warning-border);
        }
        
        .badge-danger {
            background: var(--danger-bg);
            color: var(--danger-dark);
            border: 1px solid var(--danger-border);
        }
        
        .badge-info {
            background: var(--info-bg);
            color: var(--info);
            border: 1px solid var(--info-border);
        }
        
        .badge-gray {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }
        
        .tabs-container {
            background: white;
            border-radius: var(--radius-xl);
            padding: 0.5rem;
            border: 1px solid var(--gray-200);
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        
        .tab-btn {
            padding: 0.625rem 1.5rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-600);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tab-btn:hover {
            background: var(--gray-50);
            color: var(--gray-800);
        }
        
        .tab-btn.active {
            background: var(--primary-bg);
            color: var(--primary-dark);
            border: 1px solid var(--primary-border);
        }
        
        .account-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            transition: all 0.25s;
        }
        
        .account-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-border);
        }
        
        .account-card.selected {
            border: 2px solid var(--primary);
            background: var(--primary-bg);
        }
        
        .resident-card.selected {
            border: 2px solid var(--primary);
            background: var(--primary-bg);
        }
        
        .patient-card.selected {
            border: 2px solid var(--success);
            background: var(--success-bg);
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-card h3 {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 9999;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: modalSlideIn 0.3s ease;
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
        
        .message-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            max-width: 400px;
            width: calc(100% - 48px);
            z-index: 10000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }
        
        .message-toast.show {
            transform: translateX(0);
        }
        
        .message-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow-xl);
            border-left: 4px solid;
            position: relative;
        }
        
        .message-content.success {
            border-left-color: var(--success);
            background: linear-gradient(to right, var(--success-bg), white);
        }
        
        .message-content.error {
            border-left-color: var(--danger);
            background: linear-gradient(to right, var(--danger-bg), white);
        }
        
        .message-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            width: 100%;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            overflow: hidden;
        }
        
        .message-progress-bar {
            height: 100%;
            width: 100%;
            transition: width 3s linear;
        }
        
        .message-progress-bar.success {
            background: var(--success);
        }
        
        .message-progress-bar.error {
            background: var(--danger);
        }
        
        .link-section {
            background: linear-gradient(135deg, var(--primary-bg) 0%, white 100%);
            border: 2px solid var(--primary-border);
            border-radius: var(--radius-xl);
            padding: 2rem;
        }
        
        .link-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 1024px) {
            .link-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .grid-auto-fit {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .btn {
                width: 100%;
            }
            
            .tabs-container {
                width: 100%;
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
            
            .card-header,
            .card-body {
                padding: 1.25rem;
            }
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
        
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        
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
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    
    <?php if ($flashMessage): ?>
    <div id="messageToast" class="message-toast show">
        <div class="message-content <?= $flashMessage['type'] ?>">
            <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors" onclick="closeMessageToast()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="flex items-start gap-3">
                <i class="fas <?= $flashMessage['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> 
                      text-2xl <?= $flashMessage['type'] === 'success' ? 'text-success' : 'text-danger' ?>"></i>
                <div>
                    <h4 class="font-bold text-gray-800 mb-1">
                        <?= $flashMessage['type'] === 'success' ? 'Success' : 'Error' ?>
                    </h4>
                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($flashMessage['message']) ?></p>
                </div>
            </div>
            
            <div class="message-progress">
                <div class="message-progress-bar <?= $flashMessage['type'] ?>" style="width: 0%;"></div>
            </div>
        </div>
    </div>
    
    <script>
        setTimeout(() => {
            const progressBar = document.querySelector('.message-progress-bar');
            if (progressBar) progressBar.style.width = '100%';
        }, 10);
        
        setTimeout(() => {
            closeMessageToast();
        }, 3000);
    </script>
    <?php endif; ?>
    
    <main class="container mx-auto px-4 py-8 mt-16">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 tracking-tight">
                    Account Management
                </h1>
                <p class="text-gray-600 mt-2 text-lg">
                    Complete control over staff and resident accounts
                </p>
            </div>
            
            <div class="flex flex-wrap gap-4">
                <div class="stat-card">
                    <h3>Staff</h3>
                    <div class="stat-number"><?= $totalStaff ?></div>
                </div>
                <div class="stat-card">
                    <h3>Residents</h3>
                    <div class="stat-number"><?= $totalResidents ?></div>
                </div>
                <?php if ($totalUnlinked > 0): ?>
                <div class="stat-card">
                    <h3>Need Linking</h3>
                    <div class="stat-number text-warning"><?= $totalUnlinked ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="tabs-container mb-8">
            <button class="tab-btn active" onclick="switchMainTab('staff')" id="tabStaff">
                <i class="fas fa-user-md"></i>
                Staff Management
                <span class="badge badge-info"><?= $totalStaff ?></span>
            </button>
            <button class="tab-btn" onclick="switchMainTab('resident')" id="tabResident">
                <i class="fas fa-users"></i>
                Resident Management
                <span class="badge badge-success"><?= $totalResidents ?></span>
            </button>
            <?php if ($totalUnlinked > 0): ?>
            <button class="tab-btn" onclick="switchMainTab('linking')" id="tabLinking">
                <i class="fas fa-link"></i>
                Manual Linking
                <span class="badge badge-warning"><?= $totalUnlinked ?></span>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- ===== STAFF SECTION ===== -->
        <section id="staffSection" class="fade-in">
            <div class="card mb-10">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary-bg flex items-center justify-center">
                            <i class="fas fa-user-plus text-primary text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Create New Staff Account</h2>
                            <p class="text-gray-600 text-sm mt-1">Add healthcare professionals to the system</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="" id="createStaffForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <input type="hidden" name="action" value="create_staff">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user mr-1 text-gray-400"></i>
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="username" required class="form-input" placeholder="e.g. juan.dela.cruz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock mr-1 text-gray-400"></i>
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="password-container">
                                <input type="text" name="password" required id="staffPassword" class="form-input" value="<?= bin2hex(random_bytes(4)) ?>" readonly>
                                <button type="button" class="password-toggle" onclick="regenerateStaffPassword()" title="Generate new password">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Auto-generated password - copy it now
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card mr-1 text-gray-400"></i>
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="full_name" required class="form-input" placeholder="e.g. Dr. Juan Dela Cruz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-briefcase mr-1 text-gray-400"></i>
                                Position <span class="text-danger">*</span>
                            </label>
                            <select name="position" required class="form-input">
                                <option value="">Select position</option>
                                <option value="BHW">Barangay Health Worker (BHW)</option>
                                <option value="Nurse">Nurse</option>
                                <option value="Midwife">Midwife</option>
                                <option value="Doctor">Doctor</option>
                                <option value="Medical Technologist">Medical Technologist</option>
                                <option value="Administrative Staff">Administrative Staff</option>
                                <option value="Dentist">Dentist</option>
                                <option value="Pharmacist">Pharmacist</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-stethoscope mr-1 text-gray-400"></i>
                                Specialization
                            </label>
                            <input type="text" name="specialization" class="form-input" placeholder="e.g. Pediatrics, General Medicine">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-badge mr-1 text-gray-400"></i>
                                License Number
                            </label>
                            <input type="text" name="license_number" class="form-input" placeholder="e.g. PRC-123456">
                        </div>
                        
                        <div class="md:col-span-2">
                            <button type="submit" class="btn btn-primary btn-lg w-full md:w-auto">
                                <i class="fas fa-plus-circle"></i>
                                Create Staff Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="tabs-container mb-6">
                <button class="tab-btn active" onclick="switchStaffTab('active')" id="staffActiveTab">
                    <i class="fas fa-check-circle text-success"></i>
                    Active Staff (<?= count($activeStaff) ?>)
                </button>
                <button class="tab-btn" onclick="switchStaffTab('inactive')" id="staffInactiveTab">
                    <i class="fas fa-pause-circle text-gray-500"></i>
                    Inactive Staff (<?= count($inactiveStaff) ?>)
                </button>
            </div>
            
            <div id="activeStaffGrid" class="grid-auto-fit">
                <?php if (empty($activeStaff)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-md text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No active staff accounts</h3>
                    <p class="text-gray-500">Create your first staff account above</p>
                </div>
                <?php else: ?>
                    <?php foreach ($activeStaff as $staff): ?>
                    <div class="account-card">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($staff['full_name'] ?? '') ?></h3>
                                <p class="text-primary text-sm font-medium">@<?= htmlspecialchars($staff['username'] ?? '') ?></p>
                            </div>
                            <span class="badge badge-success">
                                <i class="fas fa-circle"></i>
                                Active
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <?php if (!empty($staff['position'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-briefcase text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($staff['position']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($staff['specialization'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-stethoscope text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($staff['specialization']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($staff['license_number'])): ?>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-id-card text-gray-400 w-5 mr-2"></i>
                                License: <?= htmlspecialchars($staff['license_number']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-xs text-gray-400 mt-2">
                                <i class="fas fa-calendar-alt w-5 mr-2"></i>
                                Added <?= date('M j, Y', strtotime($staff['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <button type="button" 
                                    class="btn btn-outline flex-1"
                                    onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars(addslashes($staff['full_name'])) ?>')">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                            
                            <form method="POST" action="" class="flex-1 toggle-staff-form">
                                <input type="hidden" name="action" value="toggle_staff">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <input type="hidden" name="toggle_action" value="deactivate">
                                <button type="submit" class="btn btn-warning w-full">
                                    <i class="fas fa-pause"></i>
                                    Deactivate
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="inactiveStaffGrid" class="grid-auto-fit" style="display: none;">
                <?php if (empty($inactiveStaff)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-slash text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No inactive staff accounts</h3>
                    <p class="text-gray-500">All staff accounts are currently active</p>
                </div>
                <?php else: ?>
                    <?php foreach ($inactiveStaff as $staff): ?>
                    <div class="account-card">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($staff['full_name'] ?? '') ?></h3>
                                <p class="text-gray-500 text-sm font-medium">@<?= htmlspecialchars($staff['username'] ?? '') ?></p>
                            </div>
                            <span class="badge badge-gray">
                                <i class="fas fa-circle"></i>
                                Inactive
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <?php if (!empty($staff['position'])): ?>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-briefcase text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($staff['position']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-xs text-gray-400 mt-2">
                                <i class="fas fa-calendar-alt w-5 mr-2"></i>
                                Added <?= date('M j, Y', strtotime($staff['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <button type="button" 
                                    class="btn btn-outline flex-1"
                                    onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars(addslashes($staff['full_name'])) ?>')">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                            
                            <form method="POST" action="" class="flex-1 toggle-staff-form">
                                <input type="hidden" name="action" value="toggle_staff">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <input type="hidden" name="toggle_action" value="activate">
                                <button type="submit" class="btn btn-success w-full">
                                    <i class="fas fa-play"></i>
                                    Activate
                                </button>
                            </form>
                            
                            <button type="button" 
                                    class="btn btn-danger flex-1"
                                    onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars(addslashes($staff['full_name'])) ?>')">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- ===== RESIDENT SECTION ===== -->
        <section id="residentSection" class="fade-in" style="display: none;">
            <div class="card mb-10">
                <div class="card-header" style="background: linear-gradient(to right, var(--success-bg), white);">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-success-bg flex items-center justify-center">
                            <i class="fas fa-user-plus text-success text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Create New Resident Account</h2>
                            <p class="text-gray-600 text-sm mt-1">Accounts are created without patient records (manual linking required)</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="" id="createResidentForm" class="space-y-6">
                        <input type="hidden" name="action" value="create_resident">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user mr-1 text-gray-400"></i>
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="full_name" required class="form-input" placeholder="e.g. Maria Santos">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" required id="residentEmail" class="form-input" placeholder="maria@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag mr-1 text-gray-400"></i>
                                    Username
                                </label>
                                <input type="text" name="username" id="residentUsername" class="form-input" placeholder="Auto-generated from email">
                                <p class="text-xs text-gray-500 mt-2">Leave blank to auto-generate</p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock mr-1 text-gray-400"></i>
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="password-container">
                                    <input type="text" name="password" required id="residentPassword" class="form-input" value="<?= bin2hex(random_bytes(3)) ?>" readonly>
                                    <button type="button" class="password-toggle" onclick="regenerateResidentPassword()" title="Generate new password">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Auto-generated password - save this now</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>
                                    Contact Number
                                </label>
                                <input type="tel" name="phone" class="form-input" placeholder="09xx xxx xxxx">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-birthday-cake mr-1 text-gray-400"></i>
                                    Date of Birth
                                </label>
                                <input type="date" name="date_of_birth" id="residentDob" class="form-input" onchange="calculateResidentAge()">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars mr-1 text-gray-400"></i>
                                    Gender
                                </label>
                                <select name="gender" class="form-input">
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                Sitio
                            </label>
                            <select name="sitio" class="form-input">
                                <option value="">Select Sitio</option>
                                <option value="Proper Luz">Proper Luz</option>
                                <option value="Lower Luz">Lower Luz</option>
                                <option value="Upper Luz">Upper Luz</option>
                                <option value="Luz Proper">Luz Proper</option>
                                <option value="Luz Heights">Luz Heights</option>
                                <option value="Panganiban">Panganiban</option>
                                <option value="Balagtas">Balagtas</option>
                                <option value="Carbon">Carbon</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        
                        <div id="residentAgeDisplay" class="hidden">
                            <div class="inline-flex items-center px-3 py-2 bg-gray-100 rounded-full">
                                <i class="fas fa-user text-gray-500 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700">Age: <span id="residentAgeValue">0</span> years</span>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex gap-3">
                                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-800 mb-1">Account Creation Note</h4>
                                    <p class="text-sm text-blue-700">
                                        This resident account will be created WITHOUT a patient record. 
                                        After creation, go to the <span class="font-bold">"Manual Linking"</span> tab to link this account to an existing patient record.
                                        Patient records are added separately through the Patient Management module.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-plus-circle"></i>
                                Create Resident Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="tabs-container mb-6">
                <button class="tab-btn active" onclick="switchResidentTab('pending')" id="residentPendingTab">
                    <i class="fas fa-clock text-warning"></i>
                    Pending (<?= count($pendingResidents) ?>)
                </button>
                <button class="tab-btn" onclick="switchResidentTab('approved')" id="residentApprovedTab">
                    <i class="fas fa-check-circle text-success"></i>
                    Approved (<?= count($approvedResidents) ?>)
                </button>
                <button class="tab-btn" onclick="switchResidentTab('declined')" id="residentDeclinedTab">
                    <i class="fas fa-times-circle text-danger"></i>
                    Declined (<?= count($declinedResidents) ?>)
                </button>
                <?php if (count($unlinkedResidents) > 0): ?>
                <button class="tab-btn" onclick="switchResidentTab('unlinked')" id="residentUnlinkedTab">
                    <i class="fas fa-unlink text-warning"></i>
                    Unlinked (<?= count($unlinkedResidents) ?>)
                </button>
                <?php endif; ?>
            </div>
            
            <div id="pendingResidentsGrid" class="grid-auto-fit">
                <?php if (empty($pendingResidents)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-3xl text-yellow-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No pending applications</h3>
                    <p class="text-gray-500">All resident applications have been processed</p>
                </div>
                <?php else: ?>
                    <?php foreach ($pendingResidents as $resident): ?>
                    <div class="account-card">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-user text-yellow-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name'] ?? '') ?></h3>
                            <p class="text-gray-500 text-sm">@<?= htmlspecialchars($resident['username'] ?? '') ?></p>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['email'] ?? '') ?>
                            </div>
                            
                            <?php if (!empty($resident['sitio'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['sitio']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($resident['age'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-birthday-cake text-gray-400 w-5 mr-2"></i>
                                Age: <?= htmlspecialchars($resident['age']) ?> years
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-xs text-gray-400 mt-2">
                                <i class="fas fa-calendar-plus w-5 mr-2"></i>
                                Applied <?= date('M j, Y', strtotime($resident['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <form method="POST" action="" class="flex-1 toggle-resident-form">
                                <input type="hidden" name="action" value="toggle_resident">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-success w-full">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                            </form>
                            
                            <form method="POST" action="" class="flex-1 toggle-resident-form">
                                <input type="hidden" name="action" value="toggle_resident">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="status" value="declined">
                                <button type="submit" class="btn btn-danger w-full">
                                    <i class="fas fa-times"></i>
                                    Decline
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="approvedResidentsGrid" class="grid-auto-fit" style="display: none;">
                <?php if (empty($approvedResidents)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No approved residents</h3>
                    <p class="text-gray-500">Approve pending accounts to see them here</p>
                </div>
                <?php else: ?>
                    <?php foreach ($approvedResidents as $resident): ?>
                    <div class="account-card">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name'] ?? '') ?></h3>
                                <p class="text-success text-sm font-medium">@<?= htmlspecialchars($resident['username'] ?? '') ?></p>
                            </div>
                            <span class="badge badge-success">Approved</span>
                        </div>
                        
                        <?php 
                        $hasRecord = $resident['has_patient_record'] ?? false;
                        ?>
                        <div class="mb-4">
                            <?php if ($hasRecord): ?>
                            <span class="badge badge-info">
                                <i class="fas fa-link"></i>
                                Linked to Patient Record
                            </span>
                            <?php else: ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-unlink"></i>
                                No Patient Record
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['email'] ?? '') ?>
                            </div>
                            
                            <?php if (!empty($resident['contact'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['contact']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-id-card w-5 mr-2"></i>
                                ID: <?= htmlspecialchars($resident['unique_number'] ?? 'N/A') ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <button type="button" 
                                    class="btn btn-outline flex-1"
                                    onclick="openResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars(addslashes($resident['full_name'])) ?>')">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                            
                            <form method="POST" action="" class="flex-1 toggle-resident-form">
                                <input type="hidden" name="action" value="toggle_resident">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="btn btn-warning w-full">
                                    <i class="fas fa-pause"></i>
                                    Suspend
                                </button>
                            </form>
                            
                            <?php if (!$hasRecord): ?>
                            <a href="?section=linking&focus=<?= $resident['id'] ?>" 
                               class="btn btn-primary flex-1 text-center"
                               onclick="switchToLinking(<?= $resident['id'] ?>); return false;">
                                <i class="fas fa-link"></i>
                                Link Record
                            </a>
                            <?php endif; ?>
                            
                            <button type="button"
                                    class="btn btn-danger flex-1"
                                    onclick="openResetPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars(addslashes($resident['full_name'])) ?>')">
                                <i class="fas fa-redo-alt"></i>
                                Reset Password
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="declinedResidentsGrid" class="grid-auto-fit" style="display: none;">
                <?php if (empty($declinedResidents)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-times-circle text-3xl text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No declined applications</h3>
                    <p class="text-gray-500">All applications are either pending or approved</p>
                </div>
                <?php else: ?>
                    <?php foreach ($declinedResidents as $resident): ?>
                    <div class="account-card">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name'] ?? '') ?></h3>
                                <p class="text-gray-500 text-sm font-medium">@<?= htmlspecialchars($resident['username'] ?? '') ?></p>
                            </div>
                            <span class="badge badge-danger">Declined</span>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['email'] ?? '') ?>
                            </div>
                            
                            <div class="flex items-center text-xs text-gray-400 mt-2">
                                <i class="fas fa-calendar-times w-5 mr-2"></i>
                                Declined <?= date('M j, Y', strtotime($resident['updated_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <form method="POST" action="" class="flex-1 toggle-resident-form">
                                <input type="hidden" name="action" value="toggle_resident">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-success w-full">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                            </form>
                            
                            <button type="button"
                                    class="btn btn-outline flex-1"
                                    onclick="openResetPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars(addslashes($resident['full_name'])) ?>')">
                                <i class="fas fa-redo-alt"></i>
                                Reset Password
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="unlinkedResidentsGrid" class="grid-auto-fit" style="display: none;">
                <?php if (empty($unlinkedResidents)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-link text-3xl text-orange-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">All accounts are linked!</h3>
                    <p class="text-gray-500">Every resident has a patient record</p>
                </div>
                <?php else: ?>
                    <?php foreach ($unlinkedResidents as $resident): ?>
                    <div class="account-card">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($resident['full_name'] ?? '') ?></h3>
                                <p class="text-warning text-sm font-medium">@<?= htmlspecialchars($resident['username'] ?? '') ?></p>
                            </div>
                            <span class="badge badge-warning">
                                <i class="fas fa-unlink"></i>
                                Unlinked
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['email'] ?? '') ?>
                            </div>
                            
                            <?php if (!empty($resident['age'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-birthday-cake text-gray-400 w-5 mr-2"></i>
                                Age: <?= htmlspecialchars($resident['age']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($resident['sitio'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt text-gray-400 w-5 mr-2"></i>
                                <?= htmlspecialchars($resident['sitio']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="button"
                                    class="btn btn-outline flex-1"
                                    onclick="openResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars(addslashes($resident['full_name'])) ?>')">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                            
                            <a href="?section=linking&focus=<?= $resident['id'] ?>" 
                               class="btn btn-primary flex-1 text-center"
                               onclick="switchToLinking(<?= $resident['id'] ?>); return false;">
                                <i class="fas fa-link"></i>
                                Link Now
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- ===== LINKING SECTION ===== -->
        <section id="linkingSection" class="fade-in" style="display: none;">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(to right, #fef3c7, white);">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-handshake text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Manual Account-Patient Linking</h2>
                            <p class="text-gray-600 text-sm mt-1">Connect resident accounts to their patient records</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($unlinkedResidents) && empty($unlinkedPatients)): ?>
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-check-circle text-4xl text-green-500"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">All Accounts Properly Linked</h3>
                        <p class="text-gray-600 max-w-md mx-auto">
                            Every resident account has a corresponding patient record. 
                            No manual linking is needed at this time.
                        </p>
                    </div>
                    <?php else: ?>
                    
                    <div class="link-section mb-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-link text-primary mr-3"></i>
                            Link Resident to Patient Record
                        </h3>
                        
                        <div class="link-grid mb-8">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-700">
                                        <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                        Unlinked Residents
                                    </h4>
                                    <span class="badge badge-info"><?= count($unlinkedResidents) ?> Available</span>
                                </div>
                                
                                <?php if (empty($unlinkedResidents)): ?>
                                <div class="bg-gray-50 rounded-lg p-8 text-center">
                                    <i class="fas fa-user-check text-3xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-500">All residents have records</p>
                                </div>
                                <?php else: ?>
                                <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto pr-2">
                                    <?php foreach ($unlinkedResidents as $resident): ?>
                                    <div class="account-card resident-card cursor-pointer p-4"
                                         data-resident-id="<?= $resident['id'] ?>"
                                         data-resident-name="<?= htmlspecialchars($resident['full_name']) ?>"
                                         data-resident-email="<?= htmlspecialchars($resident['email']) ?>"
                                         data-resident-sitio="<?= htmlspecialchars($resident['sitio'] ?? 'Not specified') ?>"
                                         data-resident-age="<?= $resident['age'] ?? 0 ?>"
                                         onclick="selectResident(this)">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-user text-blue-500"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-gray-800 truncate">
                                                    <?= htmlspecialchars($resident['full_name']) ?>
                                                </div>
                                                <div class="text-xs text-gray-500 truncate">
                                                    <?= htmlspecialchars($resident['email']) ?>
                                                </div>
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    <?php if (!empty($resident['sitio'])): ?>
                                                    <span class="badge badge-gray text-xs">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        <?= htmlspecialchars($resident['sitio']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($resident['age'])): ?>
                                                    <span class="badge badge-gray text-xs">
                                                        <i class="fas fa-birthday-cake mr-1"></i>
                                                        <?= $resident['age'] ?> yrs
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-700">
                                        <i class="fas fa-file-medical text-green-500 mr-2"></i>
                                        Unlinked Patient Records
                                    </h4>
                                    <span class="badge badge-success"><?= count($unlinkedPatients) ?> Available</span>
                                </div>
                                
                                <?php if (empty($unlinkedPatients)): ?>
                                <div class="bg-gray-50 rounded-lg p-8 text-center">
                                    <i class="fas fa-file-excel text-3xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-500">No unlinked patient records</p>
                                </div>
                                <?php else: ?>
                                <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto pr-2">
                                    <?php foreach ($unlinkedPatients as $patient): ?>
                                    <div class="account-card patient-card cursor-pointer p-4"
                                         data-patient-id="<?= $patient['id'] ?>"
                                         data-patient-name="<?= htmlspecialchars($patient['full_name']) ?>"
                                         data-patient-age="<?= $patient['age'] ?? 0 ?>"
                                         data-patient-gender="<?= htmlspecialchars($patient['gender'] ?? 'Not specified') ?>"
                                         data-patient-sitio="<?= htmlspecialchars($patient['sitio'] ?? 'Not specified') ?>"
                                         onclick="selectPatient(this)">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-file-medical text-green-500"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-gray-800 truncate">
                                                    <?= htmlspecialchars($patient['full_name']) ?>
                                                </div>
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    <?php if (!empty($patient['age'])): ?>
                                                    <span class="badge badge-gray text-xs">
                                                        <i class="fas fa-birthday-cake mr-1"></i>
                                                        <?= $patient['age'] ?> yrs
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($patient['gender'])): ?>
                                                    <span class="badge badge-gray text-xs">
                                                        <i class="fas fa-venus-mars mr-1"></i>
                                                        <?= htmlspecialchars($patient['gender']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div id="selectedPanel" class="bg-white border-2 border-primary rounded-xl p-6 shadow-lg" style="display: none;">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-primary-bg flex items-center justify-center">
                                        <i class="fas fa-handshake text-primary text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Ready to Link</h4>
                                        <p class="text-sm text-gray-600">Review your selection before linking</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline" onclick="clearLinkingSelection()">
                                    <i class="fas fa-times mr-2"></i>
                                    Clear Selection
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-600 font-semibold uppercase">Resident Account</p>
                                            <p class="font-bold text-gray-800" id="selectedResidentName">Not selected</p>
                                        </div>
                                    </div>
                                    <div id="selectedResidentDetails" class="text-xs text-gray-600 ml-11">
                                        Select a resident from the left panel
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <i class="fas fa-file-medical text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-600 font-semibold uppercase">Patient Record</p>
                                            <p class="font-bold text-gray-800" id="selectedPatientName">Not selected</p>
                                        </div>
                                    </div>
                                    <div id="selectedPatientDetails" class="text-xs text-gray-600 ml-11">
                                        Select a patient from the right panel
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="" id="linkForm">
                                <input type="hidden" name="action" value="link_accounts">
                                <input type="hidden" name="ajax" value="1">
                                <input type="hidden" name="resident_id" id="linkResidentId" value="0">
                                <input type="hidden" name="patient_id" id="linkPatientId" value="0">
                                
                                <button type="submit" id="linkButton" class="btn btn-primary w-full" disabled>
                                    <i class="fas fa-link mr-2"></i>
                                    <span id="linkButtonText">Select Both Items to Link</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="flex items-start gap-3 md:w-1/3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-blue-600 font-bold">1</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-1">Create Resident Account</h5>
                                    <p class="text-xs text-gray-600">Accounts are created without patient records</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 md:w-1/3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-blue-600 font-bold">2</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-1">Add Patient Record</h5>
                                    <p class="text-xs text-gray-600">Create patient records via Patient Management</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 md:w-1/3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-blue-600 font-bold">3</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-1">Link Here</h5>
                                    <p class="text-xs text-gray-600">One account = One record (cannot be undone)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <!-- ===== MODALS ===== -->
    
    <!-- Staff Change Password Modal -->
    <div id="staffPasswordModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-key text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Change Staff Password</h3>
                            <p class="text-sm text-gray-600 mt-1">Update staff member's login credentials</p>
                        </div>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" onclick="closeStaffPasswordModal()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" action="" id="staffPasswordForm">
                    <input type="hidden" name="action" value="staff_password_change">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="staff_id" id="staffPasswordId">
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user-md text-blue-600"></i>
                            <div>
                                <p class="text-xs text-blue-600 font-semibold uppercase">Staff Member</p>
                                <p class="font-bold text-gray-800" id="staffPasswordName"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="current_password" id="staffCurrentPass" required class="form-input" placeholder="Enter current password">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('staffCurrentPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-key mr-2 text-gray-400"></i>
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="new_password" id="staffNewPass" required class="form-input" placeholder="Enter new password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('staffNewPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-check-double mr-2 text-gray-400"></i>
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="staffConfirmPass" required class="form-input" placeholder="Confirm new password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('staffConfirmPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="staffPassMatchMessage" class="text-sm mt-2 hidden"></div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="button" class="btn btn-outline flex-1" onclick="closeStaffPasswordModal()">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="fas fa-save mr-2"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Resident Change Password Modal (Requires Current Password) -->
    <div id="residentPasswordModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-key text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Change Resident Password</h3>
                            <p class="text-sm text-gray-600 mt-1">Resident must provide current password</p>
                        </div>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" onclick="closeResidentPasswordModal()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" action="" id="residentPasswordForm">
                    <input type="hidden" name="action" value="resident_password_change">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="resident_id" id="residentPasswordId">
                    
                    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user text-green-600"></i>
                            <div>
                                <p class="text-xs text-green-600 font-semibold uppercase">Resident</p>
                                <p class="font-bold text-gray-800" id="residentPasswordName"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="current_password" id="residentCurrentPass" required class="form-input" placeholder="Enter current password">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('residentCurrentPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-key mr-2 text-gray-400"></i>
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="new_password" id="residentNewPass" required class="form-input" placeholder="Enter new password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('residentNewPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-check-double mr-2 text-gray-400"></i>
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="residentConfirmPass" required class="form-input" placeholder="Confirm new password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('residentConfirmPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="residentPassMatchMessage" class="text-sm mt-2 hidden"></div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="button" class="btn btn-outline flex-1" onclick="closeResidentPasswordModal()">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success flex-1">
                            <i class="fas fa-save mr-2"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Admin Reset Password Modal (No Current Password Required) -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-redo-alt text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Admin Password Reset</h3>
                            <p class="text-sm text-gray-600 mt-1">Reset resident password (no current password required)</p>
                        </div>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" onclick="closeResetPasswordModal()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" action="" id="resetPasswordForm">
                    <input type="hidden" name="action" value="admin_password_reset">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="resident_id" id="resetPasswordId">
                    
                    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user text-red-600"></i>
                            <div>
                                <p class="text-xs text-red-600 font-semibold uppercase">Resident</p>
                                <p class="font-bold text-gray-800" id="resetPasswordName"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-key mr-2 text-gray-400"></i>
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="text" name="new_password" id="resetNewPass" required class="form-input" value="<?= bin2hex(random_bytes(3)) ?>" readonly>
                            <button type="button" class="password-toggle" onclick="regenerateResetPassword()" title="Generate new password">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Auto-generated password - copy it now</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-check-double mr-2 text-gray-400"></i>
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="resetConfirmPass" required class="form-input" placeholder="Confirm new password">
                            <button type="button" class="password-toggle" onclick="togglePasswordField('resetConfirmPass')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="button" class="btn btn-outline flex-1" onclick="closeResetPasswordModal()">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger flex-1">
                            <i class="fas fa-redo-alt mr-2"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Staff Confirmation Modal -->
    <div id="deleteStaffModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Delete Staff Account</h3>
                            <p class="text-sm text-gray-600 mt-1">This action cannot be undone</p>
                        </div>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" onclick="closeDeleteStaffModal()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" action="" id="deleteStaffForm">
                    <input type="hidden" name="action" value="delete_staff">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="staff_id" id="deleteStaffId">
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-gray-700" id="deleteStaffMessage"></p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block font-semibold text-gray-700 mb-3">Handle dependent records:</label>
                        
                        <div class="space-y-3">
                            <label class="flex items-start p-3 border rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                                <input type="radio" name="delete_action" value="reassign" checked class="mt-1 mr-3">
                                <div class="flex-1">
                                    <span class="font-medium text-gray-700">Reassign to another staff member</span>
                                    <select name="reassign_to" class="form-input mt-2 text-sm">
                                        <option value="">Select staff member</option>
                                        <?php foreach ($allStaff as $staff): ?>
                                            <?php if ($staff['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                            <option value="<?= $staff['id'] ?>">
                                                <?= htmlspecialchars($staff['full_name']) ?> (@<?= htmlspecialchars($staff['username']) ?>)
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </label>
                            
                            <label class="flex items-start p-3 border rounded-lg hover:bg-red-50 cursor-pointer transition-colors">
                                <input type="radio" name="delete_action" value="delete" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <span class="font-medium text-red-600">Delete all associated records</span>
                                    <p class="text-xs text-red-500 mt-1">Appointments, consultations, prescriptions will be permanently deleted</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="button" class="btn btn-outline flex-1" onclick="closeDeleteStaffModal()">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger flex-1">
                            <i class="fas fa-trash mr-2"></i> Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // ============================================================================
        // GLOBAL STATE
        // ============================================================================
        let selectedResidentId = 0;
        let selectedPatientId = 0;
        let linkingFocusResident = <?= isset($_GET['focus']) ? intval($_GET['focus']) : 0 ?>;

        // Modal state tracking - PREVENTS AUTO CLOSE
        const modalStates = {
            staffPassword: false,
            residentPassword: false,
            resetPassword: false,
            deleteStaff: false
        };

        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================

        function togglePasswordField(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleBtn = field.parentElement.querySelector('.password-toggle i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggleBtn.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                toggleBtn.className = 'fas fa-eye';
            }
        }

        function closeMessageToast() {
            const toast = document.getElementById('messageToast');
            if (toast) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showNotification(message, type = 'success') {
            // Create toast container if it doesn't exist
            let toast = document.getElementById('dynamicMessageToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'dynamicMessageToast';
                toast.className = 'message-toast';
                document.body.appendChild(toast);
            }
            
            // Set content
            toast.innerHTML = `
                <div class="message-content ${type}">
                    <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors" onclick="this.closest('.message-toast').classList.remove('show')">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="flex items-start gap-3">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-2xl ${type === 'success' ? 'text-success' : 'text-danger'}"></i>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-1">${type === 'success' ? 'Success' : 'Error'}</h4>
                            <p class="text-gray-600 text-sm">${escapeHtml(message)}</p>
                        </div>
                    </div>
                    <div class="message-progress">
                        <div class="message-progress-bar ${type}" style="width: 0%;"></div>
                    </div>
                </div>
            `;
            
            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
                const progressBar = toast.querySelector('.message-progress-bar');
                if (progressBar) progressBar.style.width = '100%';
            }, 10);
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // ============================================================================
        // PASSWORD GENERATION
        // ============================================================================

        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
            let password = '';
            for (let i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }

        function regenerateStaffPassword() {
            const field = document.getElementById('staffPassword');
            if (field) field.value = generatePassword();
        }

        function regenerateResidentPassword() {
            const field = document.getElementById('residentPassword');
            if (field) field.value = generatePassword();
        }

        function regenerateResetPassword() {
            const field = document.getElementById('resetNewPass');
            if (field) field.value = generatePassword();
        }

        // ============================================================================
        // AUTO-GENERATE USERNAME FROM EMAIL
        // ============================================================================

        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('residentEmail');
            const usernameField = document.getElementById('residentUsername');
            
            if (emailField && usernameField) {
                emailField.addEventListener('blur', function() {
                    if (!usernameField.value && this.value) {
                        let username = this.value.split('@')[0];
                        username = username.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                        usernameField.value = username;
                    }
                });
            }
            
            regenerateStaffPassword();
            regenerateResidentPassword();
            
            if (linkingFocusResident > 0) {
                switchMainTab('linking');
                setTimeout(() => {
                    const residentCard = document.querySelector(`[data-resident-id="${linkingFocusResident}"]`);
                    if (residentCard) {
                        residentCard.click();
                        residentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            }
        });

        // ============================================================================
        // AGE CALCULATION
        // ============================================================================

        function calculateResidentAge() {
            const dobInput = document.getElementById('residentDob');
            const ageDisplay = document.getElementById('residentAgeDisplay');
            const ageValue = document.getElementById('residentAgeValue');
            
            if (!dobInput.value) {
                ageDisplay.style.display = 'none';
                return;
            }
            
            const dob = new Date(dobInput.value);
            const today = new Date();
            
            if (dob > today) {
                alert('Date of birth cannot be in the future');
                dobInput.value = '';
                ageDisplay.style.display = 'none';
                return;
            }
            
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;
            
            if (age < 0 || age > 120) {
                alert('Please enter a valid date of birth (age 0-120)');
                dobInput.value = '';
                ageDisplay.style.display = 'none';
                return;
            }
            
            ageValue.textContent = age;
            ageDisplay.style.display = 'block';
        }

        // ============================================================================
        // TAB SWITCHING
        // ============================================================================

        function switchMainTab(tab) {
            document.getElementById('staffSection').style.display = 'none';
            document.getElementById('residentSection').style.display = 'none';
            document.getElementById('linkingSection').style.display = 'none';
            
            document.querySelectorAll('.tabs-container .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tab + 'Section').style.display = 'block';
            
            if (tab === 'staff') document.getElementById('tabStaff').classList.add('active');
            if (tab === 'resident') document.getElementById('tabResident').classList.add('active');
            if (tab === 'linking') document.getElementById('tabLinking').classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('section', tab);
            window.history.pushState({}, '', url);
        }

        function switchStaffTab(tab) {
            document.getElementById('activeStaffGrid').style.display = 'none';
            document.getElementById('inactiveStaffGrid').style.display = 'none';
            
            document.getElementById('staffActiveTab').classList.remove('active');
            document.getElementById('staffInactiveTab').classList.remove('active');
            
            document.getElementById(tab + 'StaffGrid').style.display = 'grid';
            document.getElementById('staff' + tab.charAt(0).toUpperCase() + tab.slice(1) + 'Tab').classList.add('active');
        }

        function switchResidentTab(tab) {
            const grids = ['pending', 'approved', 'declined', 'unlinked'];
            grids.forEach(g => {
                const el = document.getElementById(g + 'ResidentsGrid');
                if (el) el.style.display = 'none';
            });
            
            document.querySelectorAll('#residentSection .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tab + 'ResidentsGrid').style.display = 'grid';
            document.getElementById('resident' + tab.charAt(0).toUpperCase() + tab.slice(1) + 'Tab').classList.add('active');
        }

        function switchToLinking(residentId) {
            switchMainTab('linking');
            setTimeout(() => {
                const residentCard = document.querySelector(`[data-resident-id="${residentId}"]`);
                if (residentCard) {
                    residentCard.click();
                    residentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        }

        // ============================================================================
        // LINKING FUNCTIONS
        // ============================================================================

        function selectResident(element) {
            document.querySelectorAll('.resident-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            
            selectedResidentId = parseInt(element.dataset.residentId);
            document.getElementById('linkResidentId').value = selectedResidentId;
            
            document.getElementById('selectedResidentName').textContent = element.dataset.residentName;
            document.getElementById('selectedResidentDetails').innerHTML = `
                <div class="mb-1"><i class="fas fa-envelope mr-1"></i> ${escapeHtml(element.dataset.residentEmail)}</div>
                <div class="mb-1"><i class="fas fa-map-marker-alt mr-1"></i> ${escapeHtml(element.dataset.residentSitio)}</div>
                ${element.dataset.residentAge > 0 ? `<div><i class="fas fa-birthday-cake mr-1"></i> Age: ${element.dataset.residentAge} years</div>` : ''}
            `;
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function selectPatient(element) {
            document.querySelectorAll('.patient-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            
            selectedPatientId = parseInt(element.dataset.patientId);
            document.getElementById('linkPatientId').value = selectedPatientId;
            
            document.getElementById('selectedPatientName').textContent = element.dataset.patientName;
            document.getElementById('selectedPatientDetails').innerHTML = `
                <div class="mb-1"><i class="fas fa-id-badge mr-1"></i> Record ID: ${selectedPatientId}</div>
                <div class="mb-1"><i class="fas fa-venus-mars mr-1"></i> ${escapeHtml(element.dataset.patientGender)}</div>
                <div class="mb-1"><i class="fas fa-map-marker-alt mr-1"></i> ${escapeHtml(element.dataset.patientSitio)}</div>
                ${element.dataset.patientAge > 0 ? `<div><i class="fas fa-birthday-cake mr-1"></i> Age: ${element.dataset.patientAge} years</div>` : ''}
            `;
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function updateLinkButton() {
            const linkButton = document.getElementById('linkButton');
            const linkButtonText = document.getElementById('linkButtonText');
            
            if (selectedResidentId > 0 && selectedPatientId > 0) {
                linkButton.disabled = false;
                linkButtonText.textContent = 'Link Accounts Now';
            } else {
                linkButton.disabled = true;
                linkButtonText.textContent = 'Select Both Items to Link';
            }
        }

        function clearLinkingSelection() {
            selectedResidentId = 0;
            selectedPatientId = 0;
            
            document.getElementById('linkResidentId').value = '0';
            document.getElementById('linkPatientId').value = '0';
            
            document.querySelectorAll('.resident-card, .patient-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            document.getElementById('selectedResidentName').textContent = 'Not selected';
            document.getElementById('selectedResidentDetails').innerHTML = 'Select a resident from the left panel';
            document.getElementById('selectedPatientName').textContent = 'Not selected';
            document.getElementById('selectedPatientDetails').innerHTML = 'Select a patient from the right panel';
            
            document.getElementById('selectedPanel').style.display = 'none';
            updateLinkButton();
        }

        // ============================================================================
        // PASSWORD VALIDATION
        // ============================================================================

        function validateStaffPassword() {
            const currentPass = document.getElementById('staffCurrentPass').value;
            const newPass = document.getElementById('staffNewPass').value;
            const confirmPass = document.getElementById('staffConfirmPass').value;
            
            if (!currentPass || !newPass || !confirmPass) {
                alert('All password fields are required');
                return false;
            }
            if (newPass.length < 6) {
                alert('Password must be at least 6 characters');
                return false;
            }
            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return false;
            }
            if (currentPass === newPass) {
                alert('New password must be different from current password');
                return false;
            }
            return true;
        }

        function validateResidentPassword() {
            const currentPass = document.getElementById('residentCurrentPass').value;
            const newPass = document.getElementById('residentNewPass').value;
            const confirmPass = document.getElementById('residentConfirmPass').value;
            
            if (!currentPass || !newPass || !confirmPass) {
                alert('All password fields are required');
                return false;
            }
            if (newPass.length < 6) {
                alert('Password must be at least 6 characters');
                return false;
            }
            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return false;
            }
            if (currentPass === newPass) {
                alert('New password must be different from current password');
                return false;
            }
            return true;
        }

        function validateResetPassword() {
            const newPass = document.getElementById('resetNewPass').value;
            const confirmPass = document.getElementById('resetConfirmPass').value;
            
            if (!newPass || !confirmPass) {
                alert('All password fields are required');
                return false;
            }
            if (newPass.length < 6) {
                alert('Password must be at least 6 characters');
                return false;
            }
            if (newPass !== confirmPass) {
                alert('Passwords do not match');
                return false;
            }
            return true;
        }

        function setupPasswordValidation() {
            const staffNew = document.getElementById('staffNewPass');
            const staffConfirm = document.getElementById('staffConfirmPass');
            
            if (staffNew && staffConfirm) {
                [staffNew, staffConfirm].forEach(field => {
                    field.addEventListener('input', function() {
                        const msg = document.getElementById('staffPassMatchMessage');
                        if (staffConfirm.value.length > 0) {
                            msg.classList.remove('hidden');
                            if (staffNew.value === staffConfirm.value) {
                                msg.className = 'text-sm mt-2 text-green-600';
                                msg.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Passwords match';
                            } else {
                                msg.className = 'text-sm mt-2 text-red-600';
                                msg.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Passwords do not match';
                            }
                        } else {
                            msg.classList.add('hidden');
                        }
                    });
                });
            }
            
            const resNew = document.getElementById('residentNewPass');
            const resConfirm = document.getElementById('residentConfirmPass');
            
            if (resNew && resConfirm) {
                [resNew, resConfirm].forEach(field => {
                    field.addEventListener('input', function() {
                        const msg = document.getElementById('residentPassMatchMessage');
                        if (resConfirm.value.length > 0) {
                            msg.classList.remove('hidden');
                            if (resNew.value === resConfirm.value) {
                                msg.className = 'text-sm mt-2 text-green-600';
                                msg.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Passwords match';
                            } else {
                                msg.className = 'text-sm mt-2 text-red-600';
                                msg.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Passwords do not match';
                            }
                        } else {
                            msg.classList.add('hidden');
                        }
                    });
                });
            }
        }

        document.addEventListener('DOMContentLoaded', setupPasswordValidation);

        // ============================================================================
        // AJAX FORM HANDLING - PREVENTS PAGE REFRESH AND MODAL CLOSE
        // ============================================================================

        function setupAjaxForms() {
            // Staff Password Change Form
            const staffPasswordForm = document.getElementById('staffPasswordForm');
            if (staffPasswordForm) {
                staffPasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateStaffPassword()) return false;
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeStaffPasswordModal();
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Resident Password Change Form
            const residentPasswordForm = document.getElementById('residentPasswordForm');
            if (residentPasswordForm) {
                residentPasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateResidentPassword()) return false;
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeResidentPasswordModal();
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Reset Password Form
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            if (resetPasswordForm) {
                resetPasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateResetPassword()) return false;
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Resetting...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeResetPasswordModal();
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Delete Staff Form
            const deleteStaffForm = document.getElementById('deleteStaffForm');
            if (deleteStaffForm) {
                deleteStaffForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!confirm('Are you sure you want to delete this staff account? This action cannot be undone.')) {
                        return false;
                    }
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeDeleteStaffModal();
                            // Reload page after 1 second to refresh data
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Create Staff Form
            const createStaffForm = document.getElementById('createStaffForm');
            if (createStaffForm) {
                createStaffForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Reset form and regenerate password
                            this.reset();
                            regenerateStaffPassword();
                            // Reload page after 1 second to refresh data
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Create Resident Form
            const createResidentForm = document.getElementById('createResidentForm');
            if (createResidentForm) {
                createResidentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Reset form
                            this.reset();
                            regenerateResidentPassword();
                            document.getElementById('residentAgeDisplay').style.display = 'none';
                            // Reload page after 1.5 seconds to refresh data
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Toggle Staff Forms
            document.querySelectorAll('.toggle-staff-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const action = formData.get('toggle_action');
                    
                    if (!confirm(`${action === 'activate' ? 'Reactivate' : 'Deactivate'} this staff account?`)) {
                        return false;
                    }
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Reload page after 1 second to refresh data
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            });
            
            // Toggle Resident Forms
            document.querySelectorAll('.toggle-resident-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const status = formData.get('status');
                    
                    let confirmMessage = '';
                    if (status === 'approved') confirmMessage = 'Approve this resident account?';
                    else if (status === 'declined') confirmMessage = 'Decline this resident application?';
                    else if (status === 'suspended') confirmMessage = 'Suspend this resident account?';
                    
                    if (!confirm(confirmMessage)) {
                        return false;
                    }
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Reload page after 1 second to refresh data
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            });
            
            // Link Accounts Form
            const linkForm = document.getElementById('linkForm');
            if (linkForm) {
                linkForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (selectedResidentId === 0 || selectedPatientId === 0) {
                        showNotification('Please select both a resident and a patient record.', 'error');
                        return false;
                    }
                    
                    if (!confirm('Are you sure you want to link these accounts? This action cannot be undone.')) {
                        return false;
                    }
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Linking...';
                    submitBtn.disabled = true;
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            clearLinkingSelection();
                            // Reload page after 1.5 seconds to refresh data
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
        }

        // ============================================================================
        // MODAL FUNCTIONS - FIXED: ONLY CLOSE ON BUTTON CLICK OR ESCAPE
        // NEVER CLOSE AUTOMATICALLY
        // ============================================================================

        function openStaffPasswordModal(staffId, staffName) {
            document.getElementById('staffPasswordId').value = staffId;
            document.getElementById('staffPasswordName').textContent = staffName;
            
            document.getElementById('staffCurrentPass').value = '';
            document.getElementById('staffNewPass').value = '';
            document.getElementById('staffConfirmPass').value = '';
            
            const matchMsg = document.getElementById('staffPassMatchMessage');
            if (matchMsg) {
                matchMsg.classList.add('hidden');
                matchMsg.innerHTML = '';
            }
            
            document.querySelectorAll('#staffPasswordForm .form-input').forEach(input => {
                input.classList.remove('border-red-500');
            });
            
            const modal = document.getElementById('staffPasswordModal');
            modal.classList.add('show');
            modalStates.staffPassword = true;
            document.body.style.overflow = 'hidden';
        }

        function closeStaffPasswordModal() {
            const modal = document.getElementById('staffPasswordModal');
            modal.classList.remove('show');
            modalStates.staffPassword = false;
            document.body.style.overflow = '';
        }

        function openResidentPasswordModal(residentId, residentName) {
            document.getElementById('residentPasswordId').value = residentId;
            document.getElementById('residentPasswordName').textContent = residentName;
            
            document.getElementById('residentCurrentPass').value = '';
            document.getElementById('residentNewPass').value = '';
            document.getElementById('residentConfirmPass').value = '';
            
            const matchMsg = document.getElementById('residentPassMatchMessage');
            if (matchMsg) {
                matchMsg.classList.add('hidden');
                matchMsg.innerHTML = '';
            }
            
            document.querySelectorAll('#residentPasswordForm .form-input').forEach(input => {
                input.classList.remove('border-red-500');
            });
            
            const modal = document.getElementById('residentPasswordModal');
            modal.classList.add('show');
            modalStates.residentPassword = true;
            document.body.style.overflow = 'hidden';
        }

        function closeResidentPasswordModal() {
            const modal = document.getElementById('residentPasswordModal');
            modal.classList.remove('show');
            modalStates.residentPassword = false;
            document.body.style.overflow = '';
        }

        function openResetPasswordModal(residentId, residentName) {
            document.getElementById('resetPasswordId').value = residentId;
            document.getElementById('resetPasswordName').textContent = residentName;
            
            regenerateResetPassword();
            document.getElementById('resetConfirmPass').value = '';
            
            document.querySelectorAll('#resetPasswordForm .form-input').forEach(input => {
                input.classList.remove('border-red-500');
            });
            
            const modal = document.getElementById('resetPasswordModal');
            modal.classList.add('show');
            modalStates.resetPassword = true;
            document.body.style.overflow = 'hidden';
        }

        function closeResetPasswordModal() {
            const modal = document.getElementById('resetPasswordModal');
            modal.classList.remove('show');
            modalStates.resetPassword = false;
            document.body.style.overflow = '';
        }

        function openDeleteModal(staffId, staffName) {
            document.getElementById('deleteStaffId').value = staffId;
            document.getElementById('deleteStaffMessage').innerHTML = 
                `Are you sure you want to delete <strong>${escapeHtml(staffName)}</strong>? This account has associated records that need to be handled.`;
            
            const reassignRadio = document.querySelector('input[name="delete_action"][value="reassign"]');
            if (reassignRadio) reassignRadio.checked = true;
            
            const reassignSelect = document.querySelector('select[name="reassign_to"]');
            if (reassignSelect) {
                reassignSelect.disabled = false;
                reassignSelect.value = '';
            }
            
            const modal = document.getElementById('deleteStaffModal');
            modal.classList.add('show');
            modalStates.deleteStaff = true;
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteStaffModal() {
            const modal = document.getElementById('deleteStaffModal');
            modal.classList.remove('show');
            modalStates.deleteStaff = false;
            document.body.style.overflow = '';
        }

        function setupDeleteModalHandlers() {
            const deleteRadios = document.querySelectorAll('input[name="delete_action"]');
            const reassignSelect = document.querySelector('select[name="reassign_to"]');
            
            if (deleteRadios.length && reassignSelect) {
                deleteRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'reassign') {
                            reassignSelect.disabled = false;
                            reassignSelect.required = true;
                        } else {
                            reassignSelect.disabled = true;
                            reassignSelect.required = false;
                            reassignSelect.value = '';
                        }
                    });
                });
            }
        }

        // ============================================================================
        // CLOSE MODALS ON OUTSIDE CLICK - BUT NEVER AUTOMATICALLY
        // ============================================================================

        window.onclick = function(event) {
            const modals = [
                'staffPasswordModal',
                'residentPasswordModal',
                'resetPasswordModal',
                'deleteStaffModal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    if (modalId === 'staffPasswordModal') closeStaffPasswordModal();
                    if (modalId === 'residentPasswordModal') closeResidentPasswordModal();
                    if (modalId === 'resetPasswordModal') closeResetPasswordModal();
                    if (modalId === 'deleteStaffModal') closeDeleteStaffModal();
                }
            });
        };

        // ============================================================================
        // KEYBOARD SHORTCUTS - ESCAPE CLOSES MODALS
        // ============================================================================

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeStaffPasswordModal();
                closeResidentPasswordModal();
                closeResetPasswordModal();
                closeDeleteStaffModal();
                closeMessageToast();
            }
        });

        // ============================================================================
        // INITIALIZE BASED ON URL PARAMETERS
        // ============================================================================

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            if (section === 'linking') {
                switchMainTab('linking');
            } else if (section === 'resident') {
                switchMainTab('resident');
            } else {
                switchMainTab('staff');
            }
            
            setupDeleteModalHandlers();
            setupAjaxForms();
        });
    </script>
</body>
</html>