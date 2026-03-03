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

        /* Account Cards */
        .account-card-modern {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .account-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .account-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 30px -12px rgba(37, 99, 235, 0.15);
            border-color: transparent;
        }

        .account-card-modern:hover::before {
            opacity: 1;
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
            border-radius: 4px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideUp 0.3s ease;
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
            max-width: 400px;
            border-left: 4px solid;
            animation: toastSlideIn 0.3s ease;
        }

        .toast.success {
            border-left-color: var(--secondary);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast.success .toast-icon {
            background: var(--secondary-bg);
            color: var(--secondary-dark);
        }

        .toast.error .toast-icon {
            background: var(--danger-bg);
            color: var(--danger-dark);
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Password Field Container */
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
            padding: 4px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--gray-600);
        }

        /* Password Strength Indicator */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: var(--gray-200);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }

        .strength-weak {
            background: var(--danger);
            width: 33.33%;
        }

        .strength-medium {
            background: var(--warning);
            width: 66.66%;
        }

        .strength-strong {
            background: var(--secondary);
            width: 100%;
        }

        /* Validation Message */
        .validation-message {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .validation-message.success {
            color: var(--secondary-dark);
        }

        .validation-message.error {
            color: var(--danger-dark);
        }

        /* Linking Cards */
        .link-card {
            border: 2px solid var(--gray-200);
            border-radius: 20px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .link-card:hover {
            border-color: var(--primary);
            background: var(--primary-bg);
            transform: translateY(-2px);
        }

        .link-card.selected {
            border-color: var(--primary);
            background: var(--primary-bg);
            box-shadow: 0 10px 25px -8px var(--primary);
        }

        .link-card.patient.selected {
            border-color: var(--secondary);
            background: var(--secondary-bg);
        }

        /* Modern Grid Layout */
        .modern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        /* Section Headers */
        .section-header {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title-modern i {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-bg) 0%, #dbeafe 100%);
            color: var(--primary);
        }

        /* Animation Classes */
        .fade-enter {
            animation: fadeIn 0.5s ease;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .modern-tabs {
                flex-direction: column;
            }
            
            .modern-grid {
                grid-template-columns: 1fr;
            }
            
            .toast {
                min-width: auto;
                width: calc(100vw - 40px);
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Custom Scrollbar */
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
        <div style="width: 100%; padding: 2.5rem;">
            <!-- Page Header -->
            <div style="margin-bottom: 3rem;">
                <h1 style="font-size: 1.5rem; font-weight: 600; color: #111827; margin: 0 0 2rem 0;">Account Management</h1>
                
                
                <!-- Tabs Navigation -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <button class="modern-tab active" onclick="switchTab('staff')" id="staffTab" style="padding: 0.875rem 1.75rem; border-radius: 4px; font-weight: 700; font-size: 0.9375rem; color: white; transition: all 0.3s ease; cursor: pointer; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); display: flex; align-items: center; gap: 0.5rem;">
                     Staff Management
                    </button>
                    <button class="modern-tab" onclick="switchTab('resident')" id="residentTab" style="padding: 0.875rem 1.75rem; border-radius: 4px; font-weight: 700; font-size: 0.9375rem; color: #6b7280; transition: all 0.3s ease; cursor: pointer; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 0.5rem;">
                         Resident Management
                    </button>
                    <?php if (count($unlinkedResidents) > 0 || count($unlinkedPatients) > 0): ?>
                    <button class="modern-tab" onclick="switchTab('linking')" id="linkingTab" style="padding: 0.875rem 1.75rem; border-radius: 4px; font-weight: 700; font-size: 0.9375rem; color: #6b7280; transition: all 0.3s ease; cursor: pointer; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 0.5rem;">
                        Manual Linking
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Content Area -->
            <div>
                <!-- Main Content -->
                <div>
                    <!-- Staff Section -->
                    <div id="staffSection" class="tab-section" style="display: block;">
                        <div style="display: grid; grid-template-columns: 500px 1fr; gap: 2rem;">
                            <!-- Left Column: Create Staff Form -->
                            <div class="modern-card" style="padding: 2rem; height: fit-content; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); position: sticky; top: 6rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                    
                                    <h2 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">
                                        Create Staff
                                    </h2>
                                </div>
                                
                                <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1.5rem 0;">Fill in details to add new staff</p>
                                
                                <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1.25rem;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Username <span style="color: #ef4444;">*</span></label>
                                        <input type="text" name="username" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Enter Username">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Full Name <span style="color: #ef4444;">*</span></label>
                                        <input type="text" name="full_name" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Enter Full Name">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Position <span style="color: #ef4444;">*</span></label>
                                        <select name="position" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit; cursor: pointer;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                            <option value="">Select Position</option>
                                            <option value="Nurse">Nurse</option>
                                            <option value="Midwife">Midwife</option>
                                            <option value="Doctor">Doctor</option>
                                            <option value="Encoder">Encoder</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Password <span style="color: #ef4444;">*</span></label>
                                        <div style="position: relative;">
                                            <input type="text" name="password" required id="staff-password" value="<?= bin2hex(random_bytes(4)) ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: monospace;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                            <button type="button" onclick="togglePasswordVisibility('staff-password')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px; transition: all 0.2s;" onmouseover="this.style.color='#6b7280'" onmouseout="this.style.color='#9ca3af'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Specialization</label>
                                        <input type="text" name="specialization" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="e.g., Pediatrics">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">License Number</label>
                                        <input type="text" name="license_number" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="e.g., LIC-2024-001">
                                    </div>
                                    
                                    <button type="submit" name="create_staff" style="width: 100%; padding: 1rem; border-radius: 4px; font-weight: 500; font-size: 1rem; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; border: none; cursor: pointer; transition: all 0.3s; margin-top: 1rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(37, 99, 235, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(37, 99, 235, 0.3)'">
                                       Create Staff
                                    </button>
                                </form>
                            </div>

                    <!-- Right Column: Staff Grid -->
                    <div>
                        <!-- Staff Tabs -->
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 2rem;">
                            <button class="modern-tab active" onclick="showStaffTab('active')" id="activeStaffTab" style="padding: 0.875rem 1.5rem; border-radius: 12px; font-weight: 700; font-size: 0.9375rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                <i class="fas fa-check-circle"></i>Active <span style="background: rgba(255,255,255,0.3); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8125rem; font-weight: 700;"><?= count($activeStaff) ?></span>
                            </button>
                            <button class="modern-tab" onclick="showStaffTab('inactive')" id="inactiveStaffTab" style="padding: 0.875rem 1.5rem; border-radius: 12px; font-weight: 700; font-size: 0.9375rem; background: white; color: #6b7280; border: 1.5px solid #e5e7eb; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-pause-circle"></i>Inactive <span style="background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8125rem; font-weight: 700;"><?= count($inactiveStaff) ?></span>
                            </button>
                        </div>

                        <!-- Active Staff Grid -->
                        <div id="activeStaffGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php if (empty($activeStaff)): ?>
                                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                                    <i class="fas fa-inbox" style="font-size: 2.5rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                    <p style="color: #9ca3af; margin: 0; font-weight: 500;">Pending to display</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeStaff as $staff): ?>
                                    <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #e5e7eb; transition: all 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" onmouseover="this.style.boxShadow='0 10px 30px rgba(0,0,0,0.1)'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'; this.style.transform='translateY(0)'">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0 0 0; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-briefcase" style="color: #9ca3af;"></i><?= htmlspecialchars($staff['position'] ?? 'N/A') ?></p>
                                            </div>
                                            <span style="background: #d1fae5; color: #065f46; padding: 0.375rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.375rem;">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>Active
                                            </span>
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" style="flex: 1; padding: 0.625rem; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.25rem;" onmouseover="this.style.background='#059669'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#10b981'; this.style.transform='scale(1)'">
                                                <i class="fas fa-check"></i>Manage
                                            </button>
                                            <button onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" style="flex: 1; padding: 0.625rem; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.25rem;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1)'">
                                                <i class="fas fa-trash"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Inactive Staff Grid -->
                        <div id="inactiveStaffGrid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php if (empty($inactiveStaff)): ?>
                                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                                    <i class="fas fa-inbox" style="font-size: 2.5rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                    <p style="color: #9ca3af; margin: 0; font-weight: 500;">No inactive staff accounts</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($inactiveStaff as $staff): ?>
                                    <div style="background: #f9fafb; border-radius: 16px; padding: 1.5rem; border: 1px solid #e5e7eb; opacity: 0.8; transition: all 0.3s;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h3 style="font-weight: 700; font-size: 1rem; color: #6b7280; margin: 0;"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                                <p style="font-size: 0.875rem; color: #9ca3af; margin: 0.5rem 0 0 0; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-briefcase" style="color: #d1d5db;"></i><?= htmlspecialchars($staff['position'] ?? 'N/A') ?></p>
                                            </div>
                                            <span style="background: #fee2e2; color: #991b1b; padding: 0.375rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.375rem;">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>Inactive
                                            </span>
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST" action="" style="flex: 1;">
                                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" name="toggle_staff_status" style="width: 100%; padding: 0.625rem; background: #2563eb; color: white; border: none; border-radius: 4px; font-weight: 700; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#2563eb'; this.style.transform='scale(1)'">
                                                    <i class="fas fa-play" style="margin-right: 0.25rem;"></i>Activate
                                                </button>
                                            </form>
                                            <button onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" style="flex: 1; padding: 0.625rem; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1)'">
                                                <i class="fas fa-trash" style="margin-right: 0.25rem;"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resident Section -->
            <div id="residentSection" class="tab-section" style="display: none;">
                <div style="display: grid; grid-template-columns: 500px 1fr; gap: 2rem;">
                    <!-- Left Column: Create Resident Form -->
                    <div class="modern-card" style="padding: 2rem; height: fit-content; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); position: sticky; top: 6rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                            
                            <h2 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">
                                Create Resident
                            </h2>
                        </div>
                        
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1.5rem 0;">Fill in details to add new resident</p>
                        
                        <form method="POST" action="" id="residentForm" style="display: flex; flex-direction: column; gap: 1.25rem;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Full Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="full_name" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Enter Full Name">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Email <span style="color: #ef4444;">*</span></label>
                                <input type="email" name="email" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Enter Email Address">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Password <span style="color: #ef4444;">*</span></label>
                                <div style="position: relative;">
                                    <input type="text" name="password" required id="resident-password" value="<?= bin2hex(random_bytes(4)) ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: monospace;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <button type="button" onclick="togglePasswordVisibility('resident-password')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px; transition: all 0.2s;" onmouseover="this.style.color='#6b7280'" onmouseout="this.style.color='#9ca3af'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Phone</label>
                                <input type="tel" name="phone" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" placeholder="Enter Phone Number">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Gender</label>
                                <select name="gender" id="gender" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit; cursor: pointer;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="dob" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'" onchange="calculateAge()">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.625rem; text-transform: uppercase; letter-spacing: 0.5px;">Sitio</label>
                                <select name="sitio" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 4px; font-size: 0.9375rem; transition: all 0.2s; background: white; font-family: inherit; cursor: pointer;" onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
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
                            
                            <button type="submit" name="create_resident" style="width: 100%; padding: 1rem; border-radius: 10px; font-weight: 700; font-size: 0.9375rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; cursor: pointer; transition: all 0.3s; margin-top: 1rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'">
                                <i class="fas fa-user-plus"></i>Create Resident
                            </button>
                        </form>
                    </div>

                    <!-- Right Column: Resident Grid -->
                    <div>
                        <!-- Resident Tabs -->
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 2rem;">
                            <button class="modern-tab active" onclick="showResidentTab('approved')" id="approvedResidentTab" style="padding: 0.875rem 1.5rem; border-radius: 4px; font-weight: 700; font-size: 0.9375rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                Approved <span style="background: rgba(255,255,255,0.3); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8125rem; font-weight: 700;"><?= count($approvedResidents) ?></span>
                            </button>
                        </div>

                        <!-- Pending Residents Grid -->
                        <div id="pendingResidentGrid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php if (empty($pendingResidents)): ?>
                                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                                    <p style="color: #6b7280;">No pending residents to display</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pendingResidents as $resident): ?>
                                    <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #e5e7eb; transition: all 0.2s;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                                <p style="font-size: 0.75rem; background: #fef3c7; color: #b45309; padding: 0.25rem 0.5rem; border-radius: 6px; display: inline-block; margin-top: 0.25rem; font-weight: 600;">Pending</p>
                                            </div>
                                        </div>
                                        
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem 0;">Email : <?= htmlspecialchars($resident['email']) ?></p>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST" action="" style="flex: 1;">
                                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" name="toggle_resident_status" style="width: 100%; padding: 0.5rem; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                    <i class="fas fa-check" style="font-size: 0.625rem; margin-right: 0.25rem;"></i>Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="flex: 1;">
                                                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                                <input type="hidden" name="action" value="decline">
                                                <button type="submit" name="toggle_resident_status" style="width: 100%; padding: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                    <i class="fas fa-times" style="font-size: 0.625rem; margin-right: 0.25rem;"></i>Decline
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Approved Residents Grid -->
                        <div id="approvedResidentGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php if (empty($approvedResidents)): ?>
                                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                                    <p style="color: #6b7280;">No Approved Residents</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($approvedResidents as $resident): 
                                    $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
                                    $stmt->execute([$resident['id']]);
                                    $hasPatientRecord = $stmt->fetch();
                                ?>
                                    <div style="background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid #e5e7eb; transition: all 0.2s;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">Email : <?= htmlspecialchars($resident['email']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-bottom: 1rem;">
                                            <?php if (!$hasPatientRecord): ?>
                                                <p style="font-size: 0.75rem; background: #fed7aa; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 6px; display: inline-block; font-weight: 600; margin: 0;">Unlinked</p>
                                            <?php else: ?>
                                                <p style="font-size: 0.75rem; background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 6px; display: inline-block; font-weight: 600; margin: 0;">Linked</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <button type="button" onclick="openResidentRecoveryModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')" style="width: 100%; padding: 0.5rem; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                <i class="fas fa-unlock-alt" style="font-size: 0.625rem; margin-right: 0.25rem;"></i>Reset Pass
                                            </button>
                                            
                                            <?php if (!$hasPatientRecord): ?>
                                            <button onclick="switchToLinking(<?= $resident['id'] ?>)" style="width: 100%; padding: 0.5rem; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                <i class="fas fa-link" style="font-size: 0.625rem; margin-right: 0.25rem;"></i>Link
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Declined Residents Grid -->
                        <div id="declinedResidentGrid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php if (empty($declinedResidents)): ?>
                                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                                    <p style="color: #6b7280;">No Declined Residents</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($declinedResidents as $resident): ?>
                                    <div style="background: #f9fafb; border-radius: 16px; padding: 1.5rem; border: 1px solid #e5e7eb; opacity: 0.75;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                                <p style="font-size: 0.75rem; background: #fecaca; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 6px; display: inline-block; margin-top: 0.25rem; font-weight: 600;">Declined</p>
                                            </div>
                                        </div>
                                        
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem 0;">Email : <?= htmlspecialchars($resident['email']) ?></p>
                                        
                                        <form method="POST" action="">
                                            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" name="toggle_resident_status" style="width: 100%; padding: 0.5rem; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                <i class="fas fa-check" style="font-size: 0.625rem; margin-right: 0.25rem;"></i>Reconsider
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linking Section -->
            <div id="linkingSection" class="tab-section" style="display: none;">
                <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-link" style="color: #8b5cf6; font-size: 1.5rem;"></i>
                        Link Accounts to Patient Records
                    </h2>
                    <p style="color: #6b7280; margin: 0 0 2rem 0; font-size: 0.95rem;">Select a resident account and a patient record to link them together</p>

                    <?php if (count($unlinkedResidents) === 0 && count($unlinkedPatients) === 0): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <div style="width: 96px; height: 96px; margin: 0 auto 1rem; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check-double" style="font-size: 2.5rem; color: #10b981;"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #374151; margin: 0 0 0.5rem 0;">All accounts linked!</h3>
                            <p style="color: #9ca3af; margin: 0;">Every resident account is properly connected to a patient record.</p>
                        </div>
                    <?php else: ?>
                        <!-- Linking Content -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <!-- Unlinked Residents Column -->
                            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div style="width: 40px; height: 40px; background: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;">Unlinked Residents</h3>
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;"><?= count($unlinkedResidents) ?> account(s) waiting</p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 384px; overflow-y: auto;">
                                    <?php if (count($unlinkedResidents) > 0): ?>
                                        <?php foreach ($unlinkedResidents as $resident): ?>
                                            <div class="link-card" onclick="selectResident(<?= $resident['id'] ?>, this)" data-resident-id="<?= $resident['id'] ?>" style="background: white; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.2s; display: flex; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; border-radius: 8px; background: #dbeafe; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #2563eb;">
                                                    <i class="fas fa-user" style="font-weight: bold;"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                    <div style="font-size: 0.875rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($resident['email']) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                            <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #10b981; margin-bottom: 0.5rem; display: block;"></i>
                                            <p style="margin: 0;">All residents are linked!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Unlinked Patients Column -->
                            <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div style="width: 40px; height: 40px; background: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem;">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;">Patient Records</h3>
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;"><?= count($unlinkedPatients) ?> record(s) unlinked</p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 384px; overflow-y: auto;">
                                    <?php if (count($unlinkedPatients) > 0): ?>
                                        <?php foreach ($unlinkedPatients as $patient): ?>
                                            <div class="link-card patient" onclick="selectPatient(<?= $patient['id'] ?>, this)" data-patient-id="<?= $patient['id'] ?>" style="background: white; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.2s; display: flex; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; border-radius: 8px; background: #d1fae5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #10b981;">
                                                    <i class="fas fa-file" style="font-weight: bold;"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                                        <?php if ($patient['age']): ?>
                                                            <span style="display: inline-block; background: white; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.25rem; border: 1px solid #e5e7eb;">Age: <?= htmlspecialchars($patient['age']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($patient['gender']): ?>
                                                            <span style="display: inline-block; background: white; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #e5e7eb;"><?= htmlspecialchars(strtoupper($patient['gender'][0])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                            <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #10b981; margin-bottom: 0.5rem; display: block;"></i>
                                            <p style="margin: 0;">All records are linked!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Items Panel -->
                        <div id="selectedPanel" style="background: linear-gradient(135deg, #f3e8ff 0%, #fce7f3 50%, #dbeafe 100%); border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 16px rgba(136, 85, 247, 0.2); display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: #a855f7; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <h4 style="font-weight: 700; font-size: 1rem; color: #111827; margin: 0;">Ready to Link</h4>
                                </div>
                                <button onclick="clearSelection()" style="color: #6b7280; background: transparent; border: none; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: all 0.2s;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="background: white; border-radius: 10px; padding: 1rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #6b7280;">
                                        <i class="fas fa-user" style="color: #2563eb;"></i>
                                        <span>Resident:</span>
                                    </div>
                                    <div style="font-weight: 700; color: #111827; margin-bottom: 0.5rem;" id="selectedResidentName">No resident selected</div>
                                    <div style="font-size: 0.875rem; color: #9ca3af;" id="selectedResidentDetails"></div>
                                </div>

                                <div style="background: white; border-radius: 10px; padding: 1rem; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #6b7280;">
                                        <i class="fas fa-file-medical" style="color: #10b981;"></i>
                                        <span>Patient:</span>
                                    </div>
                                    <div style="font-weight: 700; color: #111827; margin-bottom: 0.5rem;" id="selectedPatientName">No patient selected</div>
                                    <div style="font-size: 0.875rem; color: #9ca3af;" id="selectedPatientDetails"></div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.75rem; padding-top: 1rem; border-top: none; position: relative; margin-top: 0.5rem;">
                                <button onclick="performLinking()" id="linkButton" disabled style="flex: 1; padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 700; font-size: 0.9375rem; background: #8b5cf6; color: white; border: none; cursor: not-allowed; transition: all 0.3s; opacity: 0.6; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(139, 92, 246, 0.2);" onmouseover="if(!this.disabled) { this.style.background='#7c3aed'; this.style.boxShadow='0 4px 12px rgba(139, 92, 246, 0.4)'; this.style.transform='translateY(-2px)'; }" onmouseout="if(!this.disabled) { this.style.background='#8b5cf6'; this.style.boxShadow='0 2px 8px rgba(139, 92, 246, 0.2)'; this.style.transform='translateY(0)'; }" title="Select both a resident and patient to link">
                                    <i class="fas fa-link"></i>Link Accounts
                                </button>
                                <button onclick="clearSelection()" style="flex: 1; padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 700; font-size: 0.9375rem; background: white; color: #6b7280; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.background='#f3f4f6'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';" onmouseout="this.style.background='white'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)';">
                                    <i class="fas fa-redo"></i>Reset
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modern-modal" id="deleteModal">
        <div class="modern-modal-content">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                Delete Staff Account
            </h3>
            
            <p class="text-gray-600 mb-6" id="deleteMessage"></p>
            
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="staff_id" id="deleteStaffId">
                
                <div class="mb-6">
                    <label class="modern-label">Handle Dependent Records:</label>
                    
                    <div class="space-y-3">
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-blue-50">
                            <input type="radio" name="delete_action" value="reassign" checked class="mt-1 mr-3">
                            <div class="flex-1">
                                <span class="font-medium">Reassign to another staff</span>
                                <select name="reassign_to" class="modern-input mt-2">
                                    <option value="">Select staff</option>
                                    <?php foreach ($allStaff as $staff): ?>
                                        <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                            <option value="<?= $staff['id'] ?>">
                                                <?= htmlspecialchars($staff['full_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </label>
                        
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-red-50">
                            <input type="radio" name="delete_action" value="delete" class="mt-1 mr-3">
                            <div class="flex-1">
                                <span class="font-medium text-danger">Delete all associated records</span>
                                <p class="text-sm text-danger mt-1">Warning: This action cannot be undone</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="btn-modern btn-modern-outline flex-1">
                        Cancel
                    </button>
                    <button type="submit" name="hard_delete" class="btn-modern btn-modern-danger flex-1">
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Password Modal -->
    <div class="modern-modal" id="staffPasswordModal">
        <div class="modern-modal-content">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-key text-primary"></i>
                Change Staff Password
            </h3>
            
            <div class="bg-blue-50 rounded-xl p-4 mb-6">
                <p class="font-medium" id="staffPasswordName"></p>
            </div>
            
            <form method="POST" action="" id="staffPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="staff_id" id="staffPasswordId">
                
                <div class="mb-4">
                    <label class="modern-label">Current Password</label>
                    <div class="password-field">
                        <input type="password" name="current_password" id="staffCurrentPass" required class="modern-input">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffCurrentPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="modern-label">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="staffNewPass" required class="modern-input" minlength="6" oninput="checkStaffPasswordStrength()">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffNewPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="staffPasswordStrength"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="modern-label">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="staffConfirmPass" required class="modern-input" minlength="6" oninput="checkStaffPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('staffConfirmPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="staffPassMessage" class="validation-message"></div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeStaffPasswordModal()" class="btn-modern btn-modern-outline flex-1">
                        Cancel
                    </button>
                    <button type="submit" name="change_staff_password" class="btn-modern btn-modern-primary flex-1">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Reset Password Modal (Admin Reset) -->
    <div class="modern-modal" id="resetPasswordModal">
        <div class="modern-modal-content">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-redo-alt text-warning"></i>
                Reset Password
            </h3>
            
            <div class="bg-yellow-50 rounded-xl p-4 mb-6" id="resetInfo">
                <p class="font-medium" id="resetName"></p>
                <p class="text-sm text-yellow-600 mt-1">Admin password reset (no current password required)</p>
            </div>
            
            <form method="POST" action="" id="resetPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="resident_id" id="resetId">
                <input type="hidden" name="staff_id" id="resetStaffId">
                
                <div class="mb-4">
                    <label class="modern-label">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="resetNewPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('resetNewPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="modern-label">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="resetConfirmPass" required class="modern-input" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('resetConfirmPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeResetModal()" class="btn-modern btn-modern-outline flex-1">
                        Cancel
                    </button>
                    <button type="submit" name="reset_resident_password" class="btn-modern btn-modern-warning flex-1" id="resetSubmit">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== TOAST NOTIFICATIONS =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                </div>
                <div class="flex-1">${message}</div>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }

        // ===== PASSWORD FUNCTIONS =====
        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function togglePasswordField(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return Math.min(strength, 3);
        }

        function updatePasswordStrengthBar(strength, elementId) {
            const bar = document.getElementById(elementId);
            if (!bar) return;
            
            bar.className = 'password-strength-bar';
            
            if (strength === 1) {
                bar.classList.add('strength-weak');
            } else if (strength === 2) {
                bar.classList.add('strength-medium');
            } else if (strength >= 3) {
                bar.classList.add('strength-strong');
            }
        }

        function checkStaffPasswordStrength() {
            const password = document.getElementById('staffNewPass').value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrengthBar(strength, 'staffPasswordStrength');
            checkStaffPasswordMatch();
        }

        function checkResidentPasswordStrength() {
            const password = document.getElementById('residentNewPass').value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrengthBar(strength, 'residentPasswordStrength');
            checkResidentPasswordMatch();
        }

        function checkStaffPasswordMatch() {
            const newPass = document.getElementById('staffNewPass').value;
            const confirmPass = document.getElementById('staffConfirmPass').value;
            const messageEl = document.getElementById('staffPassMessage');
            
            if (confirmPass.length === 0) {
                messageEl.innerHTML = '';
                return;
            }
            
            if (newPass === confirmPass) {
                messageEl.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                messageEl.className = 'validation-message success';
            } else {
                messageEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                messageEl.className = 'validation-message error';
            }
        }

        function checkResidentPasswordMatch() {
            const newPass = document.getElementById('residentNewPass').value;
            const confirmPass = document.getElementById('residentConfirmPass').value;
            const messageEl = document.getElementById('residentPassMessage');
            
            if (confirmPass.length === 0) {
                messageEl.innerHTML = '';
                return;
            }
            
            if (newPass === confirmPass) {
                messageEl.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                messageEl.className = 'validation-message success';
            } else {
                messageEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                messageEl.className = 'validation-message error';
            }
        }

        // ===== STAFF PASSWORD MODAL =====
        function openStaffPasswordModal(staffId, staffName) {
            document.getElementById('staffPasswordId').value = staffId;
            document.getElementById('staffPasswordName').innerHTML = staffName;
            document.getElementById('staffPasswordModal').classList.add('show');
            
            // Reset form
            document.getElementById('staffPasswordForm').reset();
            document.getElementById('staffPassMessage').innerHTML = '';
            document.getElementById('staffPasswordStrength').className = 'password-strength-bar';
        }

        function closeStaffPasswordModal() {
            document.getElementById('staffPasswordModal').classList.remove('show');
        }

        // ===== RESIDENT PASSWORD MODAL =====
        function openResidentPasswordModal(residentId, residentName) {
            document.getElementById('residentPasswordId').value = residentId;
            document.getElementById('residentPasswordName').innerHTML = residentName;
            document.getElementById('residentPasswordModal').classList.add('show');
            
            // Reset form
            document.getElementById('residentPasswordForm').reset();
            document.getElementById('residentPassMessage').innerHTML = '';
            document.getElementById('residentPasswordStrength').className = 'password-strength-bar';
        }

        function closeResidentPasswordModal() {
            document.getElementById('residentPasswordModal').classList.remove('show');
        }

        // ===== RESET PASSWORD MODAL =====
        function openResetModal(userId, userName, type) {
            document.getElementById('resetPasswordModal').classList.add('show');
            document.getElementById('resetName').innerHTML = userName;
            
            if (type === 'resident') {
                document.getElementById('resetId').value = userId;
                document.getElementById('resetStaffId').value = '';
                document.getElementById('resetSubmit').name = 'reset_resident_password';
            } else {
                document.getElementById('resetStaffId').value = userId;
                document.getElementById('resetId').value = '';
                document.getElementById('resetSubmit').name = 'reset_staff_password';
            }
        }

        function closeResetModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
            document.getElementById('resetPasswordForm').reset();
        }

        // ===== DELETE MODAL =====
        function openDeleteModal(staffId, staffName) {
            document.getElementById('deleteStaffId').value = staffId;
            document.getElementById('deleteMessage').innerHTML = `Are you sure you want to delete <strong>${staffName}</strong>? This action will affect associated records.`;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.getElementById('deleteForm').reset();
        }

        // ===== TAB SWITCHING =====
        function switchTab(tab) {
            // Hide all sections
            document.getElementById('staffSection').style.display = 'none';
            document.getElementById('residentSection').style.display = 'none';
            if (document.getElementById('linkingSection')) {
                document.getElementById('linkingSection').style.display = 'none';
            }
            
            // Reset all tabs to inactive state
            const allTabs = document.querySelectorAll('.modern-tab.active');
            allTabs.forEach(t => {
                t.classList.remove('active');
                t.style.background = 'white';
                t.style.color = '#6b7280';
                t.style.border = '1.5px solid #e5e7eb';
                t.style.boxShadow = 'none';
            });
            
            // Show selected section and activate tab
            if (tab === 'staff') {
                document.getElementById('staffSection').style.display = 'block';
                const staffTab = document.getElementById('staffTab');
                staffTab.classList.add('active');
                staffTab.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
                staffTab.style.color = 'white';
                staffTab.style.border = 'none';
                staffTab.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)';
            } else if (tab === 'resident') {
                document.getElementById('residentSection').style.display = 'block';
                const residentTab = document.getElementById('residentTab');
                residentTab.classList.add('active');
                residentTab.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
                residentTab.style.color = 'white';
                residentTab.style.border = 'none';
                residentTab.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)';
            } else if (tab === 'linking') {
                document.getElementById('linkingSection').style.display = 'block';
                const linkingTab = document.getElementById('linkingTab');
                linkingTab.classList.add('active');
                linkingTab.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
                linkingTab.style.color = 'white';
                linkingTab.style.border = 'none';
                linkingTab.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)';
            }
        }

        function showStaffTab(tab) {
            // Show/hide grids
            document.getElementById('activeStaffGrid').style.display = tab === 'active' ? 'grid' : 'none';
            document.getElementById('inactiveStaffGrid').style.display = tab === 'inactive' ? 'grid' : 'none';
            
            // Update active tab styling
            const activeBtn = document.getElementById('activeStaffTab');
            const inactiveBtn = document.getElementById('inactiveStaffTab');
            
            if (tab === 'active') {
                activeBtn.classList.add('active');
                activeBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                activeBtn.style.color = 'white';
                activeBtn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.3)';
                
                inactiveBtn.classList.remove('active');
                inactiveBtn.style.background = 'white';
                inactiveBtn.style.color = '#6b7280';
                inactiveBtn.style.border = '1.5px solid #e5e7eb';
                inactiveBtn.style.boxShadow = 'none';
            } else {
                inactiveBtn.classList.add('active');
                inactiveBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                inactiveBtn.style.color = 'white';
                inactiveBtn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.3)';
                
                activeBtn.classList.remove('active');
                activeBtn.style.background = 'white';
                activeBtn.style.color = '#6b7280';
                activeBtn.style.border = '1.5px solid #e5e7eb';
                activeBtn.style.boxShadow = 'none';
            }
        }

        function showResidentTab(tab) {
            const tabs = ['pending', 'approved', 'declined', 'unlinked'];
            tabs.forEach(t => {
                const grid = document.getElementById(t + 'ResidentGrid');
                if (grid) grid.style.display = 'none';
            });
            
            const selectedGrid = document.getElementById(tab + 'ResidentGrid');
            if (selectedGrid) selectedGrid.style.display = 'grid';
            
            // Update tab styling
            document.querySelectorAll('#residentSection .modern-tab').forEach(t => {
                t.classList.remove('active');
                t.style.background = 'white';
                t.style.color = '#6b7280';
                t.style.border = '1.5px solid #e5e7eb';
                t.style.boxShadow = 'none';
            });
            
            const activeTab = document.getElementById(tab + 'ResidentTab');
            if (activeTab) {
                activeTab.classList.add('active');
                activeTab.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                activeTab.style.color = 'white';
                activeTab.style.border = 'none';
                activeTab.style.boxShadow = '0 4px 12px rgba(245, 158, 11, 0.3)';
            }
        }

        // ===== AGE CALCULATION =====
        function calculateAge() {
            const dob = document.getElementById('dob').value;
            if (!dob) return;
            
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 0 || age > 120) {
                showToast('Please enter a valid date of birth', 'error');
                document.getElementById('dob').value = '';
            }
        }

        // ===== LINKING FUNCTIONS =====
        let selectedResidentId = 0;
        let selectedPatientId = 0;

        function selectResident(id, element) {
            // Remove selection from all resident cards
            document.querySelectorAll('.link-card').forEach(card => {
                card.style.background = 'white';
                card.style.borderLeft = 'none';
            });
            
            // Add selection to clicked card
            element.style.background = '#dbeafe';
            element.style.borderLeft = '4px solid #2563eb';
            selectedResidentId = id;
            
            // Update selected panel
            const nameDiv = element.querySelector('div[style*="flex: 1"] > div:first-child');
            const emailDiv = element.querySelector('div[style*="flex: 1"] > div:last-child');
            const name = nameDiv ? nameDiv.textContent : 'Unknown';
            const email = emailDiv ? emailDiv.textContent : '';
            
            document.getElementById('selectedResidentName').textContent = name;
            document.getElementById('selectedResidentDetails').textContent = email;
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function selectPatient(id, element) {
            // Remove selection from all patient cards
            document.querySelectorAll('.link-card.patient').forEach(card => {
                card.style.background = 'white';
                card.style.borderLeft = 'none';
            });
            
            // Add selection to clicked card
            element.style.background = '#d1fae5';
            element.style.borderLeft = '4px solid #10b981';
            selectedPatientId = id;
            
            // Update selected panel
            const nameDiv = element.querySelector('div[style*="flex: 1"] > div:first-child');
            const name = nameDiv ? nameDiv.textContent : 'Unknown';
            
            document.getElementById('selectedPatientName').textContent = name;
            document.getElementById('selectedPatientDetails').textContent = '';
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function updateLinkButton() {
            const linkButton = document.getElementById('linkButton');
            if (linkButton) {
                if (selectedResidentId > 0 && selectedPatientId > 0) {
                    linkButton.disabled = false;
                    linkButton.style.opacity = '1';
                    linkButton.style.cursor = 'pointer';
                } else {
                    linkButton.disabled = true;
                    linkButton.style.opacity = '0.6';
                    linkButton.style.cursor = 'not-allowed';
                }
            }
        }

        function clearSelection() {
            selectedResidentId = 0;
            selectedPatientId = 0;
            
            document.querySelectorAll('.link-card').forEach(card => {
                card.style.background = 'white';
                card.style.borderLeft = 'none';
            });
            
            document.getElementById('selectedPanel').style.display = 'none';
            updateLinkButton();
        }

        function performLinking() {
            if (selectedResidentId === 0 || selectedPatientId === 0) {
                showToast('Please select both a resident and a patient record', 'error');
                return;
            }
            
            if (confirm('Are you sure you want to link these accounts?')) {
                window.location.href = `?link_resident=1&resident_id=${selectedResidentId}&patient_id=${selectedPatientId}`;
            }
        }

        function switchToLinking(residentId) {
            switchTab('linking');
            document.getElementById('staffTab').classList.remove('active');
            document.getElementById('residentTab').classList.remove('active');
            document.getElementById('linkingTab').classList.add('active');
            
            // Highlight the resident card
            setTimeout(() => {
                const residentCard = document.querySelector(`[data-resident-id="${residentId}"]`);
                if (residentCard) {
                    residentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    selectResident(residentId, residentCard);
                }
            }, 300);
        }

        // ===== AUTO-GENERATE USERNAME FROM EMAIL =====
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            const usernameInput = document.querySelector('input[name="username"]');
            
            if (emailInput && usernameInput) {
                emailInput.addEventListener('blur', function() {
                    if (!usernameInput.value && this.value) {
                        usernameInput.value = this.value.split('@')[0];
                    }
                });
            }
            
            // Check URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('section') === 'linking') {
                switchTab('linking');
            }
        });

        // ===== CLOSE MODALS ON OUTSIDE CLICK =====
        window.onclick = function(event) {
            if (event.target.classList.contains('modern-modal')) {
                event.target.classList.remove('show');
            }
        }

        // ===== PASSWORD FORM SUBMISSION VALIDATION =====
        document.getElementById('staffPasswordForm')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('staffNewPass').value;
            const confirmPass = document.getElementById('staffConfirmPass').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                showToast('Passwords do not match!', 'error');
            }
        });

        document.getElementById('residentPasswordForm')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('residentNewPass').value;
            const confirmPass = document.getElementById('residentConfirmPass').value;
            
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

<!-- Resident Password Recovery Modal (always outside main container) -->
<div id="residentRecoveryModal" class="modern-modal">
    <div class="modern-modal-content">
        <h2 class="text-xl font-bold mb-4">Recover Resident Password</h2>
        <form method="POST" action="" id="residentRecoveryForm">
            <input type="hidden" name="resident_id" id="recoveryResidentId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="mb-4">
                <label class="modern-label">New Password</label>
                <input type="password" name="new_password" class="modern-input" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="modern-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="modern-input" required minlength="6">
            </div>
            <div class="flex gap-2">
                <button type="submit" name="reset_resident_password" class="btn-modern btn-modern-primary flex-1">Set New Password</button>
                <button type="button" class="btn-modern btn-modern-outline flex-1" onclick="closeResidentRecoveryModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResidentRecoveryModal(id, name) {
    document.getElementById('residentRecoveryModal').classList.add('show');
    document.getElementById('recoveryResidentId').value = id;
}
function closeResidentRecoveryModal() {
    document.getElementById('residentRecoveryModal').classList.remove('show');
}
// Optional: Close modal on outside click
if (document.getElementById('residentRecoveryModal')) {
    document.getElementById('residentRecoveryModal').addEventListener('click', function(e) {
        if (e.target === this) closeResidentRecoveryModal();
    });
}
</script>

</body>
</html>