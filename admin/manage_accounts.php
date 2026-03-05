<?php
// Ensure the 'updated_at' column exists in sitio1_staff before update
function ensureStaffUpdatedAtColumn($pdo) {
    $result = $pdo->query("SHOW COLUMNS FROM sitio1_staff LIKE 'updated_at'");
    if ($result->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sitio1_staff ADD COLUMN updated_at DATETIME NULL DEFAULT NULL AFTER created_at");
    }
}
// --- Secure session and security headers ---
require_once __DIR__ . '/../includes/auth.php';

// Set secure session cookie params (if not already set in php.ini)
if (PHP_VERSION_ID >= 70300 && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

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

// Ensure the sitio1_activity_log table exists before any log insert
if (!function_exists('ensureActivityLogTable')) {
    function ensureActivityLogTable($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sitio1_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}
// ============================================================================
// Account Management - Barangay Luz Health Center
// This file handles staff and resident account management, including:
// - Password changes and resets
// - Account creation and status toggling
// - Manual linking of resident and patient records
// - Security, validation, and error handling
// ============================================================================
// --- Security and session management ---
// --- CSRF token generation and validation ---
// --- Main POST handler for all account management actions ---
// --- Helper for error message and redirect (DRY principle) ---
// --- Staff password change with current password verification ---
// --- Resident password change with current password verification ---
// --- Staff password reset by admin (no current password required) ---
// --- Resident password reset by admin (no current password required) ---
// --- Staff account creation ---
// --- Resident account creation (no automatic patient record creation) ---
// --- Resident status toggle (approve, decline, suspend) ---
// --- Staff status toggle (activate, deactivate) ---
// --- Staff deletion with dependency checks and reassignment ---
// --- Function to manually link resident account to patient records ---
// --- Function to get unlinked residents (accounts without patient records) ---
// --- Function to get unlinked patient records (without user accounts) ---
// --- Data loading for staff and resident accounts, and unlinked records ---
// --- HTML and UI rendering starts here ---
// Helper for error message and redirect
function setErrorAndRedirect($msg, $type = 'error', $redirect = 'manage_accounts.php') {
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $redirect);
    exit();
}

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

// Regenerate session ID on login for security (should be in login logic, but double check here)
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = true;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    // CSRF token validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['message'] = 'Invalid or missing CSRF token. Please try again.';
        $_SESSION['message_type'] = 'error';
        header('Location: manage_accounts.php');
        exit();
    }
    // Handle staff password change with current password verification
    if (isset($_POST['change_staff_password'])) {
        // Sanitize and validate input
        $staffId = filter_var($_POST['staff_id'], FILTER_VALIDATE_INT);
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Validate required fields
        if (!$staffId || empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setErrorAndRedirect('All password fields are required.');
        }

        // Password length check
        if (strlen($newPassword) < 6) {
            setErrorAndRedirect('New password must be at least 6 characters long.');
        }

        // Password match check
        if ($newPassword !== $confirmPassword) {
            setErrorAndRedirect('New passwords do not match.');
        }
        
        try {
            // Get staff current password
            $stmt = $pdo->prepare("SELECT id, password, full_name FROM sitio1_staff WHERE id = ?");
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
            
            // Check if new password is same as old
            if (password_verify($newPassword, $staff['password'])) {
                $_SESSION['message'] = 'New password must be different from current password.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            ensureStaffUpdatedAtColumn($pdo);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_staff SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $staffId]);
            
            // Log the password change
            ensureActivityLogTable($pdo);
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'password_change', 'Changed password for staff: ' . $staff['full_name'], $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['message'] = 'Staff password changed successfully for ' . htmlspecialchars($staff['full_name']) . '!';
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
        // Sanitize and validate input
        $residentId = filter_var($_POST['resident_id'], FILTER_VALIDATE_INT);
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Validate required fields
        if (!$residentId || empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setErrorAndRedirect('All password fields are required.');
        }

        // Password length check
        if (strlen($newPassword) < 6) {
            setErrorAndRedirect('New password must be at least 6 characters long.');
        }

        // Password match check
        if ($newPassword !== $confirmPassword) {
            setErrorAndRedirect('New passwords do not match.');
        }
        
        try {
            // Get resident current password
            $stmt = $pdo->prepare("SELECT id, password, full_name FROM sitio1_users WHERE id = ? AND role = 'patient'");
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
            
            // Check if new password is same as old
            if (password_verify($newPassword, $resident['password'])) {
                $_SESSION['message'] = 'New password must be different from current password.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $residentId]);
            
            // Log the password change
            ensureActivityLogTable($pdo);
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'password_change', 'Changed password for resident: ' . $resident['full_name'], $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['message'] = 'Resident password changed successfully for ' . htmlspecialchars($resident['full_name']) . '!';
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
    // Handle staff password reset by admin (no current password required)
    elseif (isset($_POST['reset_staff_password'])) {
        $staffId = filter_var($_POST['staff_id'], FILTER_VALIDATE_INT);
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (!$staffId || empty($newPassword) || empty($confirmPassword)) {
            setErrorAndRedirect('New password is required.');
        }

        if (strlen($newPassword) < 6) {
            setErrorAndRedirect('Password must be at least 6 characters long.');
        }

        if ($newPassword !== $confirmPassword) {
            setErrorAndRedirect('Passwords do not match.');
        }
        
        try {
            // Verify staff exists
            $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_staff WHERE id = ?");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$staff) {
                $_SESSION['message'] = 'Staff not found.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_staff SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $staffId]);
            
            // Log the password reset
            ensureActivityLogTable($pdo);
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'password_reset', 'Reset password for staff: ' . $staff['full_name'], $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['message'] = 'Staff password reset successfully for ' . htmlspecialchars($staff['full_name']) . '! New password: ' . htmlspecialchars($newPassword);
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
    // Handle resident password reset (admin reset without current password)
    elseif (isset($_POST['reset_resident_password'])) {
        $residentId = filter_var($_POST['resident_id'], FILTER_VALIDATE_INT);
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (!$residentId || empty($newPassword) || empty($confirmPassword)) {
            setErrorAndRedirect('New password is required.');
        }

        if (strlen($newPassword) < 6) {
            setErrorAndRedirect('Password must be at least 6 characters long.');
        }

        if ($newPassword !== $confirmPassword) {
            setErrorAndRedirect('Passwords do not match.');
        }
        
        try {
            // Verify resident exists
            $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_users WHERE id = ? AND role = 'patient'");
            $stmt->execute([$residentId]);
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resident) {
                $_SESSION['message'] = 'Resident not found.';
                $_SESSION['message_type'] = 'error';
                header('Location: manage_accounts.php');
                exit();
            }
            
            // Update password, clear reset token, and UNLOCK account
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ?, failed_login_attempts = 0, last_failed_login = NULL, account_locked_until = NULL, password_reset_token = NULL, password_reset_token_expires = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $residentId]);
            
            // Log the password reset
            ensureActivityLogTable($pdo);
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'password_reset', 'Reset password for resident: ' . $resident['full_name'] . ' (Account unlocked automatically)', $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['message'] = 'Resident password reset successfully';
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
    elseif (isset($_POST['create_staff'])) {
        // Sanitize and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
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
                $stmt = $pdo->prepare("INSERT INTO sitio1_staff (username, password, full_name, position, specialization, license_number, work_days, created_by, status, is_active, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW())");
                $stmt->execute([$username, $hashedPassword, $fullName, $position, $specialization, $license_number, $work_days, $_SESSION['user_id']]);
                
                // Log the account creation
                $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $logStmt->execute([$_SESSION['user_id'], 'staff_created', 'Created staff account: ' . $fullName, $_SERVER['REMOTE_ADDR']]);
                
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
            setErrorAndRedirect('Full name, email and password are required.');
        }

        // Validate password length
        if (strlen($password) < 6) {
            setErrorAndRedirect('Password must be at least 6 characters long.');
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setErrorAndRedirect('Please enter a valid email address.');
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
            
            // Insert user WITHOUT linking to any patient record
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
            
            // Log the account creation
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'resident_created', 'Created resident account: ' . $fullName, $_SERVER['REMOTE_ADDR']]);
            
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
    // Handle resident status toggle
    elseif (isset($_POST['toggle_resident_status'])) {
        $residentId = intval($_POST['resident_id']);
        $action = $_POST['action'];
        
        if (in_array($action, ['approve', 'decline', 'suspend'])) {
            try {
                $newStatus = ($action === 'approve') ? 'approved' : ($action === 'decline' ? 'declined' : 'suspended');
                
                $stmt = $pdo->prepare("UPDATE sitio1_users SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $residentId]);
                
                // Get resident name for log
                $nameStmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                $nameStmt->execute([$residentId]);
                $residentName = $nameStmt->fetchColumn();
                
                // Log the status change
                $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $logStmt->execute([$_SESSION['user_id'], 'resident_status_change', ucfirst($action) . 'd resident: ' . $residentName, $_SERVER['REMOTE_ADDR']]);
                
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
                $stmt = $pdo->prepare("UPDATE sitio1_staff SET status = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $isActive, $staffId]);
                
                // Get staff name for log
                $nameStmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                $nameStmt->execute([$staffId]);
                $staffName = $nameStmt->fetchColumn();
                
                // Log the status change
                $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $logStmt->execute([$_SESSION['user_id'], 'staff_status_change', ucfirst($action) . 'd staff: ' . $staffName, $_SERVER['REMOTE_ADDR']]);
                
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
            // Get staff name for log
            $nameStmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
            $nameStmt->execute([$staffId]);
            $staffName = $nameStmt->fetchColumn();
            
            // Check for dependencies
            $dependencies = [];
            
            // ...existing code...
            
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
                        // Get reassign staff name for log
                        $reassignStmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                        $reassignStmt->execute([$reassignTo]);
                        $reassignName = $reassignStmt->fetchColumn();
                        
                        // ...existing code...
                        
                        // Reassign consultations
                        $stmt = $pdo->prepare("UPDATE sitio1_consultations SET staff_id = ?, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Reassign patient records
                        $stmt = $pdo->prepare("UPDATE sitio1_patients SET added_by = ?, updated_at = NOW() WHERE added_by = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Reassign prescriptions
                        $stmt = $pdo->prepare("UPDATE sitio1_prescriptions SET staff_id = ?, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
                        // Set announcements to NULL
                        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        // Log the reassignment
                        $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $logStmt->execute([$_SESSION['user_id'], 'staff_deleted', 'Deleted staff: ' . $staffName . ' and reassigned records to: ' . $reassignName, $_SERVER['REMOTE_ADDR']]);
                        
                        $_SESSION['message'] = 'Staff account deleted and records reassigned to ' . htmlspecialchars($reassignName) . ' successfully!';
                    } else {
                        // ...existing code...
                        
                        $stmt = $pdo->prepare("DELETE FROM sitio1_consultations WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("UPDATE sitio1_patients SET added_by = NULL, updated_at = NOW() WHERE added_by = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("DELETE FROM sitio1_prescriptions WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
                        // Log the deletion
                        $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $logStmt->execute([$_SESSION['user_id'], 'staff_deleted', 'Deleted staff: ' . $staffName . ' and all associated records', $_SERVER['REMOTE_ADDR']]);
                        
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
                
                // Log the deletion
                $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $logStmt->execute([$_SESSION['user_id'], 'staff_deleted', 'Deleted staff: ' . $staffName . ' (no dependencies)', $_SERVER['REMOTE_ADDR']]);
                
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
            $stmt = $pdo->prepare("UPDATE sitio1_users SET patient_record_uid = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$patientRecordUID, $residentUserId]);
        }
        
        // Get resident name for log
        $nameStmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
        $nameStmt->execute([$residentUserId]);
        $residentName = $nameStmt->fetchColumn();
        
        // Log the linking
        $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['user_id'], 'account_linking', 'Linked resident: ' . $residentName . ' to patient: ' . $patient['full_name'], $_SERVER['REMOTE_ADDR']]);
        
        $resultMessage = '✅ Successfully linked ' . htmlspecialchars($residentName) . ' to patient: ' . htmlspecialchars($patient['full_name']);
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
    error_log('Error loading data in manage_accounts.php: ' . $e->getMessage());
    $_SESSION['message'] = 'A database error occurred while loading account data. Please try again later or contact support.';
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
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --primary-bg: #eff6ff;
            --secondary: #10b981;
            --secondary-dark: #059669;
            --secondary-light: #34d399;
            --secondary-bg: #ecfdf5;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --warning-light: #fbbf24;
            --warning-bg: #fffbeb;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --danger-light: #f87171;
            --danger-bg: #fef2f2;
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
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #f9fafb 100%);
            min-height: 100vh;
        }

        /* Modern Card Design */
        .modern-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(229, 231, 235, 0.5);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .modern-card:hover {
            box-shadow: 0 20px 40px -12px rgba(37, 99, 235, 0.2);
        }

        /* Glassmorphism Effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Modern Button Styles */
        .btn-modern {
            padding: 0.625rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-modern-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-modern-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e3a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px var(--primary);
        }

        .btn-modern-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: white;
        }

        .btn-modern-secondary:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px var(--secondary);
        }

        .btn-modern-warning {
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: white;
        }

        .btn-modern-warning:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--warning-dark) 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px var(--warning);
        }

        .btn-modern-danger {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: white;
        }

        .btn-modern-danger:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--danger-dark) 0%, #b91c1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px var(--danger);
        }

        .btn-modern-outline {
            background: white;
            border: 2px solid var(--gray-200);
            color: var(--gray-700);
        }

        .btn-modern-outline:hover:not(:disabled) {
            background: var(--gray-50);
            border-color: var(--gray-300);
            transform: translateY(-2px);
        }

        .btn-modern:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modern Tabs */
        .modern-tabs {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }

        .modern-tab {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-600);
            transition: all 0.2s;
            cursor: pointer;
            background: transparent;
            border: none;
        }

        .modern-tab.active {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .modern-tab:hover:not(.active) {
            background: var(--gray-50);
            color: var(--gray-800);
        }

        /* Modern Form Elements */
        .modern-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 14px;
            font-size: 0.9375rem;
            transition: all 0.2s;
            background: white;
        }

        .modern-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .modern-input.error {
            border-color: var(--danger);
        }

        .modern-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        /* Modern Badges */
        .modern-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .badge-success {
            background: var(--secondary-bg);
            color: var(--secondary-dark);
            border: 1px solid #a7f3d0;
        }

        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning-dark);
            border: 1px solid #fde68a;
        }

        .badge-danger {
            background: var(--danger-bg);
            color: var(--danger-dark);
            border: 1px solid #fecaca;
        }

        .badge-info {
            background: var(--primary-bg);
            color: var(--primary-dark);
            border: 1px solid #bfdbfe;
        }

        /* Modern Stats Card */
        .stat-card-modern {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-content h3 {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .stat-content .number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
        }

        /* Account Cards - Based on image design */
        .account-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .account-card:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .account-card.active {
            background: #f0f9ff;
            border-color: #2563eb;
        }

        .account-card.inactive {
            background: #f9fafb;
            opacity: 0.8;
        }

        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .account-name {
            font-weight: 700;
            font-size: 1rem;
            color: #111827;
            margin: 0;
        }

        .account-position {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
        }

        .status-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #374151;
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1.5px solid #d1d5db;
            cursor: pointer;
        }

        .checkbox-item input[type="checkbox"]:checked {
            accent-color: #2563eb;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-icon {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .btn-icon.danger {
            color: #dc2626;
            border-color: #fee2e2;
        }

        .btn-icon.danger:hover {
            background: #fee2e2;
        }

        .btn-icon.success {
            color: #059669;
            border-color: #d1fae5;
        }

        .btn-icon.success:hover {
            background: #d1fae5;
        }

        .btn-icon.primary {
            color: #2563eb;
            border-color: #dbeafe;
        }

        .btn-icon.primary:hover {
            background: #dbeafe;
        }

        /* Modern Grid Layout */
        .modern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        /* Section Headers */
        .section-header {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title-modern {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title-modern i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-bg) 0%, #dbeafe 100%);
            color: var(--primary);
        }

        /* Stats Cards */
        .stats-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid #e5e7eb;
            min-width: 150px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Modern Modal */
        .modern-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }

        .modern-modal.show {
            display: flex;
        }

        .modern-modal-content {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            max-width: 500px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
            max-width: 400px;
            border-left: 4px solid;
        }

        .toast.success {
            border-left-color: var(--secondary);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        /* Password Field */
        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
        }

        /* Main Navigation Tabs - Based on image */
        .main-nav-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .main-nav-tab {
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #6b7280;
            cursor: pointer;
            position: relative;
        }

        .main-nav-tab.active {
            color: #2563eb;
        }

        .main-nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: -0.6rem;
            left: 0;
            right: 0;
            height: 2px;
            background: #2563eb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modern-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <?php if (isset($_SESSION['message'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?= json_encode($_SESSION['message']) ?>, <?= json_encode($_SESSION['message_type'] ?? 'success') ?>);
        });
    </script>
    <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif; 
    ?>

    <main style="background: linear-gradient(135deg, #f0f9ff 0%, #f9fafb 100%); min-height: 100vh; padding-top: 1rem;">
        <div style="width: 100%; padding: 2rem;">
            <!-- Page Header with Stats -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 1.5rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Account Management</h1>
                
                <!-- Stats Cards - Based on image -->
                <div class="stats-container">
                    <div class="stat-card">
                        <span class="stat-number">10</span>
                        <span class="stat-label">Active Admin</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">15</span>
                        <span class="stat-label">Resident Accounts</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">25</span>
                        <span class="stat-label">Linked Accounts</span>
                    </div>
                </div>
                
                <!-- Main Navigation Tabs - Based on image -->
                <div class="main-nav-tabs">
                    <div class="main-nav-tab active" onclick="switchTab('staff')" id="staffMainTab">Staff Management</div>
                    <div class="main-nav-tab" onclick="switchTab('resident')" id="residentMainTab">Resident Management</div>
                    <?php if (count($unlinkedResidents) > 0 || count($unlinkedPatients) > 0): ?>
                    <div class="main-nav-tab" onclick="switchTab('linking')" id="linkingMainTab">Manual Linking</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div>
                <!-- Staff Section -->
                <div id="staffSection" class="tab-section" style="display: block;">
                    <!-- Create Staff Form - Based on image -->
                    <div style="margin-bottom: 2rem; background: white; border-radius: 8px; padding: 1.5rem; border: 1px solid #e5e7eb;">
                        <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Create New Staff Account</h2>
                        
                        <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Username *</label>
                                <input type="text" name="username" required placeholder="Enter User Name" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Password *</label>
                                <div style="position: relative;">
                                    <input type="text" name="password" required id="staff-password" value="<?= bin2hex(random_bytes(4)) ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem; font-family: monospace;">
                                    <button type="button" onclick="togglePasswordVisibility('staff-password')" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #9ca3af;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Full Name *</label>
                                <input type="text" name="full_name" required placeholder="Enter Full Name" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Position *</label>
                                <select name="position" required style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                                    <option value="">Select Position</option>
                                    <option value="Nurse">Nurse</option>
                                    <option value="Midwife">Midwife</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Encoder">Encoder</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Specialization</label>
                                <input type="text" name="specialization" placeholder="e.g., Pediatrics" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">License Number</label>
                                <input type="text" name="license_number" placeholder="Enter License Number" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                            </div>
                            
                            <div style="grid-column: 1/-1; margin-top: 0.5rem;">
                                <button type="submit" name="create_staff" style="padding: 0.5rem 2rem; background: #2563eb; color: white; border: none; border-radius: 4px; font-weight: 500; font-size: 0.875rem; cursor: pointer;">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Staff Tabs - Based on image -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 2rem; border-bottom: 2px solid #e5e7eb;">
                            <button onclick="showStaffTab('active')" id="activeStaffMainTab" style="padding: 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #2563eb; border: none; background: none; cursor: pointer; border-bottom: 2px solid #2563eb; margin-bottom: -2px;">
                                Active Account
                            </button>
                            <button onclick="showStaffTab('inactive')" id="inactiveStaffMainTab" style="padding: 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #6b7280; border: none; background: none; cursor: pointer;">
                                Inactive Account
                            </button>
                        </div>
                    </div>

                    <!-- Active Staff Grid - Based on first image -->
                    <div id="activeStaffSection" style="display: block;">
                        <?php if (empty($activeStaff)): ?>
                            <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <p style="color: #9ca3af;">Pending to display</p>
                            </div>
                        <?php else: ?>
                            <div class="modern-grid">
                                <?php foreach ($activeStaff as $staff): ?>
                                    <div class="account-card active">
                                        <div class="account-header">
                                            <div>
                                                <h3 class="account-name"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                                <p class="account-position">Position: <?= htmlspecialchars($staff['position'] ?? 'N/A') ?></p>
                                            </div>
                                            <span class="status-badge">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                Active
                                            </span>
                                        </div>
                                        
                                        <div class="checkbox-group">
                                            <label class="checkbox-item">
                                                <input type="checkbox" onchange="togglePasswordChange(this, <?= $staff['id'] ?>)">
                                                Change Password
                                            </label>
                                            <label class="checkbox-item">
                                                <input type="checkbox" onchange="toggleDeactivate(this, <?= $staff['id'] ?>)">
                                                Deactivate
                                            </label>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" class="btn-icon primary" style="flex: 1;">
                                                <i class="fas fa-key"></i> Change
                                            </button>
                                            <form method="POST" action="" style="flex: 1;" onsubmit="return confirm('Deactivate this staff account?')">
                                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" name="toggle_staff_status" class="btn-icon danger" style="width: 100%;">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Inactive Staff Grid - Based on second image -->
                    <div id="inactiveStaffSection" style="display: none;">
                        <?php if (empty($inactiveStaff)): ?>
                            <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <p style="color: #9ca3af;">No inactive staff accounts</p>
                            </div>
                        <?php else: ?>
                            <div class="modern-grid">
                                <?php foreach ($inactiveStaff as $staff): ?>
                                    <div class="account-card inactive">
                                        <div class="account-header">
                                            <div>
                                                <h3 class="account-name" style="color: #6b7280;"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                                <p class="account-position">Position: <?= htmlspecialchars($staff['position'] ?? 'N/A') ?></p>
                                            </div>
                                            <span class="status-badge inactive">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                Inactive
                                            </span>
                                        </div>
                                        
                                        <div class="checkbox-group">
                                            <label class="checkbox-item">
                                                <input type="checkbox" onchange="toggleActivate(this, <?= $staff['id'] ?>)">
                                                Activate
                                            </label>
                                            <label class="checkbox-item">
                                                <input type="checkbox" onchange="toggleDelete(this, <?= $staff['id'] ?>)">
                                                Delete
                                            </label>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <form method="POST" action="" style="flex: 1;" onsubmit="return confirm('Activate this staff account?')">
                                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" name="toggle_staff_status" class="btn-icon success" style="width: 100%;">
                                                    <i class="fas fa-play"></i> Activate
                                                </button>
                                            </form>
                                            <button onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" class="btn-icon danger" style="flex: 1;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resident Section -->
<div id="residentSection" class="tab-section" style="display: none;">
    <!-- Create Resident Form - Based on image style -->
    <div style="margin-bottom: 2rem; background: white; border-radius: 8px; padding: 1.5rem; border: 1px solid #e5e7eb;">
        <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Create New Resident Account</h2>
        
        <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Username *</label>
                <input type="text" name="username" required placeholder="Enter Username" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Full Name *</label>
                <input type="text" name="full_name" required placeholder="Enter Full Name" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Email *</label>
                <input type="email" name="email" required placeholder="Enter Email Address" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Password *</label>
                <div style="position: relative;">
                    <input type="text" name="password" required id="resident-password" value="<?= bin2hex(random_bytes(4)) ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem; font-family: monospace;">
                    <button type="button" onclick="togglePasswordVisibility('resident-password')" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #9ca3af;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Phone</label>
                <input type="tel" name="phone" placeholder="Enter Phone Number" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Gender</label>
                <select name="gender" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Date of Birth</label>
                <input type="date" name="date_of_birth" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Sitio</label>
                <select name="sitio" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.875rem;">
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
            
            <div style="grid-column: 1/-1; margin-top: 0.5rem;">
                <button type="submit" name="create_resident" style="padding: 0.5rem 2rem; background: #10b981; color: white; border: none; border-radius: 4px; font-weight: 500; font-size: 0.875rem; cursor: pointer;">
                    Create Account
                </button>
            </div>
        </form>
    </div>

    <!-- Resident Tabs -->
    <div style="margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 2rem; border-bottom: 2px solid #e5e7eb;">
            <button onclick="showResidentTab('approved')" id="approvedResidentMainTab" style="padding: 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #10b981; border: none; background: none; cursor: pointer; border-bottom: 2px solid #10b981; margin-bottom: -2px;">
                Approved Residents (<?= count($approvedResidents) ?>)
            </button>
            <button onclick="showResidentTab('pending')" id="pendingResidentMainTab" style="padding: 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #6b7280; border: none; background: none; cursor: pointer;">
                Pending (<?= count($pendingResidents) ?>)
            </button>
            <button onclick="showResidentTab('declined')" id="declinedResidentMainTab" style="padding: 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #6b7280; border: none; background: none; cursor: pointer;">
                Declined (<?= count($declinedResidents) ?>)
            </button>
        </div>
    </div>

    <!-- Approved Residents Grid -->
    <div id="approvedResidentSection" style="display: block;">
        <?php if (empty($approvedResidents)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                <p style="color: #9ca3af;">No approved residents</p>
            </div>
        <?php else: ?>
            <div class="modern-grid">
                <?php foreach ($approvedResidents as $resident): 
                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
                    $stmt->execute([$resident['id']]);
                    $hasPatientRecord = $stmt->fetch();
                ?>
                    <div class="account-card">
                        <div class="account-header">
                            <div>
                                <h3 class="account-name"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                <p class="account-position">Username: <?= htmlspecialchars($resident['username']) ?></p>
                                <p class="account-position" style="font-size: 0.75rem;"><?= htmlspecialchars($resident['email']) ?></p>
                            </div>
                            <?php if (!$hasPatientRecord): ?>
                                <span class="status-badge warning" style="background: #fed7aa; color: #92400e;">
                                    Unlinked
                                </span>
                            <?php else: ?>
                                <span class="status-badge success">
                                    Linked
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" onchange="toggleResidentResetPass(this, <?= $resident['id'] ?>)">
                                Reset Password
                            </label>
                        </div>
                        
                        <div class="action-buttons">
                            <button onclick="openResetModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>', 'resident')" class="btn-icon warning" style="flex: 1;">
                                <i class="fas fa-key"></i> Reset Pass
                            </button>
                            <?php if (!$hasPatientRecord): ?>
                            <button onclick="switchToLinking(<?= $resident['id'] ?>)" class="btn-icon primary" style="flex: 1;">
                                <i class="fas fa-link"></i> Link
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Residents Grid -->
    <div id="pendingResidentSection" style="display: none;">
        <?php if (empty($pendingResidents)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                <p style="color: #9ca3af;">No pending residents</p>
            </div>
        <?php else: ?>
            <div class="modern-grid">
                <?php foreach ($pendingResidents as $resident): ?>
                    <div class="account-card">
                        <div class="account-header">
                            <div>
                                <h3 class="account-name"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                <p class="account-position">Username: <?= htmlspecialchars($resident['username']) ?></p>
                                <p class="account-position" style="font-size: 0.75rem;"><?= htmlspecialchars($resident['email']) ?></p>
                            </div>
                            <span class="status-badge warning">
                                Pending
                            </span>
                        </div>
                        
                        <div class="action-buttons">
                            <form method="POST" action="" style="flex: 1;">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" name="toggle_resident_status" class="btn-icon success" style="width: 100%;">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="" style="flex: 1;">
                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" name="toggle_resident_status" class="btn-icon danger" style="width: 100%;">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Declined Residents Grid -->
    <div id="declinedResidentSection" style="display: none;">
        <?php if (empty($declinedResidents)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                <p style="color: #9ca3af;">No declined residents</p>
            </div>
        <?php else: ?>
            <div class="modern-grid">
                <?php foreach ($declinedResidents as $resident): ?>
                    <div class="account-card inactive">
                        <div class="account-header">
                            <div>
                                <h3 class="account-name" style="color: #6b7280;"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                <p class="account-position">Username: <?= htmlspecialchars($resident['username']) ?></p>
                                <p class="account-position" style="font-size: 0.75rem;"><?= htmlspecialchars($resident['email']) ?></p>
                            </div>
                            <span class="status-badge inactive">
                                Declined
                            </span>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" name="toggle_resident_status" class="btn-icon success" style="width: 100%;">
                                <i class="fas fa-redo"></i> Reconsider
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

                <!-- Linking Section -->
                <div id="linkingSection" class="tab-section" style="display: none;">
                    <div style="background: white; border-radius: 8px; padding: 1.5rem; border: 1px solid #e5e7eb;">
                        <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Link Accounts to Patient Records</h2>

                        <?php if (count($unlinkedResidents) === 0 && count($unlinkedPatients) === 0): ?>
                            <div style="text-align: center; padding: 3rem;">
                                <p style="color: #9ca3af;">All accounts are linked!</p>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <!-- Unlinked Residents -->
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem;">Unlinked Residents (<?= count($unlinkedResidents) ?>)</h3>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($unlinkedResidents as $resident): ?>
                                            <div onclick="selectResident(<?= $resident['id'] ?>, this)" data-resident-id="<?= $resident['id'] ?>" style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; transition: all 0.2s;">
                                                <div style="font-weight: 600;"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                <div style="font-size: 0.875rem; color: #6b7280;"><?= htmlspecialchars($resident['email']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Unlinked Patients -->
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem;">Patient Records (<?= count($unlinkedPatients) ?>)</h3>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($unlinkedPatients as $patient): ?>
                                            <div onclick="selectPatient(<?= $patient['id'] ?>, this)" data-patient-id="<?= $patient['id'] ?>" style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; transition: all 0.2s;">
                                                <div style="font-weight: 600;"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                <div style="font-size: 0.875rem; color: #6b7280;">Age: <?= htmlspecialchars($patient['age'] ?? 'N/A') ?> | Sitio: <?= htmlspecialchars($patient['sitio'] ?? 'N/A') ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Link Button -->
                            <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                <button onclick="performLinking()" id="linkButton" disabled style="padding: 0.5rem 2rem; background: #8b5cf6; color: white; border: none; border-radius: 4px; font-weight: 500; cursor: not-allowed; opacity: 0.5;">
                                    <i class="fas fa-link"></i> Link Accounts
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modern-modal" id="deleteModal">
        <div class="modern-modal-content">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Delete Staff Account</h3>
            <p style="margin-bottom: 1.5rem; color: #6b7280;" id="deleteMessage"></p>
            
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="staff_id" id="deleteStaffId">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Handle Dependent Records:</label>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <label style="display: flex; align-items: start; gap: 0.5rem;">
                            <input type="radio" name="delete_action" value="reassign" checked>
                            <span>
                                <span style="font-weight: 500;">Reassign to another staff</span>
                                <select name="reassign_to" class="modern-input" style="margin-top: 0.25rem;">
                                    <option value="">Select staff</option>
                                    <?php foreach ($allStaff as $staff): ?>
                                        <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                            <option value="<?= $staff['id'] ?>">
                                                <?= htmlspecialchars($staff['full_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                        </label>
                        
                        <label style="display: flex; align-items: start; gap: 0.5rem;">
                            <input type="radio" name="delete_action" value="delete">
                            <span style="color: #dc2626; font-weight: 500;">Delete all associated records</span>
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="closeDeleteModal()" style="flex: 1; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; background: white; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="hard_delete" style="flex: 1; padding: 0.5rem; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Password Modal -->
    <div class="modern-modal" id="staffPasswordModal">
        <div class="modern-modal-content">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Change Staff Password</h3>
            
            <div style="background: #eff6ff; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <p style="font-weight: 500;" id="staffPasswordName"></p>
            </div>
            
            <form method="POST" action="" id="staffPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="staff_id" id="staffPasswordId">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">Current Password</label>
                    <div class="password-field">
                        <input type="password" name="current_password" id="staffCurrentPass" required class="modern-input">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffCurrentPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="staffNewPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffNewPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="staffConfirmPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffConfirmPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="closeStaffPasswordModal()" style="flex: 1; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; background: white; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="change_staff_password" style="flex: 1; padding: 0.5rem; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modern-modal" id="resetPasswordModal">
        <div class="modern-modal-content">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Reset Password</h3>
            
            <div style="background: #fffbeb; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <p style="font-weight: 500;" id="resetName"></p>
            </div>
            
            <form method="POST" action="" id="resetPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="resident_id" id="resetId">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="resetNewPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('resetNewPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="resetConfirmPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('resetConfirmPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="closeResetModal()" style="flex: 1; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; background: white; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="reset_resident_password" style="flex: 1; padding: 0.5rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toast notifications
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                </div>
                <div style="flex: 1;">${message}</div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #9ca3af; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        // Password visibility toggle
        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function togglePasswordField(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Tab switching
        function switchTab(tab) {
            // Update main nav tabs
            document.querySelectorAll('.main-nav-tab').forEach(t => {
                t.classList.remove('active');
                t.style.color = '#6b7280';
            });
            document.getElementById(tab + 'MainTab').classList.add('active');
            document.getElementById(tab + 'MainTab').style.color = '#2563eb';
            
            // Show/hide sections
            document.getElementById('staffSection').style.display = tab === 'staff' ? 'block' : 'none';
            document.getElementById('residentSection').style.display = tab === 'resident' ? 'block' : 'none';
            if (document.getElementById('linkingSection')) {
                document.getElementById('linkingSection').style.display = tab === 'linking' ? 'block' : 'none';
            }
        }

        // Staff tab switching
        function showStaffTab(tab) {
            const activeTab = document.getElementById('activeStaffMainTab');
            const inactiveTab = document.getElementById('inactiveStaffMainTab');
            
            if (tab === 'active') {
                activeTab.style.color = '#2563eb';
                activeTab.style.borderBottom = '2px solid #2563eb';
                inactiveTab.style.color = '#6b7280';
                inactiveTab.style.borderBottom = 'none';
                document.getElementById('activeStaffSection').style.display = 'block';
                document.getElementById('inactiveStaffSection').style.display = 'none';
            } else {
                inactiveTab.style.color = '#2563eb';
                inactiveTab.style.borderBottom = '2px solid #2563eb';
                activeTab.style.color = '#6b7280';
                activeTab.style.borderBottom = 'none';
                document.getElementById('inactiveStaffSection').style.display = 'block';
                document.getElementById('activeStaffSection').style.display = 'none';
            }
        }

        // Resident tab switching
        function showResidentTab(tab) {
            const tabs = ['approved', 'pending', 'declined'];
            tabs.forEach(t => {
                const el = document.getElementById(t + 'ResidentMainTab');
                if (el) {
                    el.style.color = '#6b7280';
                    el.style.borderBottom = 'none';
                }
                document.getElementById(t + 'ResidentSection').style.display = 'none';
            });
            
            document.getElementById(tab + 'ResidentMainTab').style.color = '#10b981';
            document.getElementById(tab + 'ResidentMainTab').style.borderBottom = '2px solid #10b981';
            document.getElementById(tab + 'ResidentSection').style.display = 'block';
        }

        // Modal functions
        function openStaffPasswordModal(id, name) {
            document.getElementById('staffPasswordId').value = id;
            document.getElementById('staffPasswordName').innerHTML = name;
            document.getElementById('staffPasswordModal').classList.add('show');
        }

        function closeStaffPasswordModal() {
            document.getElementById('staffPasswordModal').classList.remove('show');
            document.getElementById('staffPasswordForm').reset();
        }

        function openDeleteModal(id, name) {
            document.getElementById('deleteStaffId').value = id;
            document.getElementById('deleteMessage').innerHTML = `Delete <strong>${name}</strong>? This will affect associated records.`;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        function openResetModal(id, name, type) {
            document.getElementById('resetId').value = id;
            document.getElementById('resetName').innerHTML = name;
            document.getElementById('resetPasswordModal').classList.add('show');
        }

        function closeResetModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
            document.getElementById('resetPasswordForm').reset();
        }

        // Checkbox handlers
        function togglePasswordChange(checkbox, staffId) {
            if (checkbox.checked) {
                openStaffPasswordModal(staffId, 'Staff Member');
            }
            checkbox.checked = false;
        }

        function toggleDeactivate(checkbox, staffId) {
            if (checkbox.checked && confirm('Deactivate this staff account?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="staff_id" value="${staffId}">
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
            checkbox.checked = false;
        }

        function toggleActivate(checkbox, staffId) {
            if (checkbox.checked && confirm('Activate this staff account?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="staff_id" value="${staffId}">
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
            checkbox.checked = false;
        }

        function toggleDelete(checkbox, staffId) {
            if (checkbox.checked) {
                openDeleteModal(staffId, 'Staff');
            }
            checkbox.checked = false;
        }

        function toggleResidentResetPass(checkbox, residentId) {
            if (checkbox.checked) {
                openResetModal(residentId, 'Resident', 'resident');
            }
            checkbox.checked = false;
        }

        // Linking functions
        let selectedResidentId = 0;
        let selectedPatientId = 0;

        function selectResident(id, element) {
            document.querySelectorAll('[data-resident-id]').forEach(el => {
                el.style.background = 'white';
            });
            element.style.background = '#eff6ff';
            selectedResidentId = id;
            updateLinkButton();
        }

        function selectPatient(id, element) {
            document.querySelectorAll('[data-patient-id]').forEach(el => {
                el.style.background = 'white';
            });
            element.style.background = '#ecfdf5';
            selectedPatientId = id;
            updateLinkButton();
        }

        function updateLinkButton() {
            const btn = document.getElementById('linkButton');
            if (selectedResidentId && selectedPatientId) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
        }

        function performLinking() {
            if (selectedResidentId && selectedPatientId) {
                if (confirm('Link these accounts?')) {
                    window.location.href = `?link_resident=1&resident_id=${selectedResidentId}&patient_id=${selectedPatientId}`;
                }
            }
        }

        function switchToLinking(residentId) {
            switchTab('linking');
            setTimeout(() => {
                const card = document.querySelector(`[data-resident-id="${residentId}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth' });
                    selectResident(residentId, card);
                }
            }, 100);
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modern-modal')) {
                event.target.classList.remove('show');
            }
        }

        // Form validation
        document.getElementById('staffPasswordForm')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('staffNewPass').value;
            const confirmPass = document.getElementById('staffConfirmPass').value;
            if (newPass !== confirmPass) {
                e.preventDefault();
                showToast('Passwords do not match!', 'error');
            }
        });

        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('resetNewPass').value;
            const confirmPass = document.getElementById('resetConfirmPass').value;
            if (newPass !== confirmPass) {
                e.preventDefault();
                showToast('Passwords do not match!', 'error');
            }
        });
    </script>
</body>
</html>