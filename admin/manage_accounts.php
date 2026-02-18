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
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sitio1_users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $residentId]);
            
            // Log the password reset
            ensureActivityLogTable($pdo);
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'password_reset', 'Reset password for resident: ' . $resident['full_name'], $_SERVER['REMOTE_ADDR']]);
            
            $_SESSION['message'] = 'Resident password reset successfully for ' . htmlspecialchars($resident['full_name']) . '! New password: ' . htmlspecialchars($newPassword);
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
    // Handle staff account creation
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
                        // Get reassign staff name for log
                        $reassignStmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                        $reassignStmt->execute([$reassignTo]);
                        $reassignName = $reassignStmt->fetchColumn();
                        
                        // Reassign appointments
                        $stmt = $pdo->prepare("UPDATE sitio1_appointments SET staff_id = ?, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                        
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
                        // Delete dependent records
                        $stmt = $pdo->prepare("DELETE FROM sitio1_appointments WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                        
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
            border-radius: 28px;
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

    <main class="container mx-auto px-4 py-8 mt-16 max-w-7xl">
        <!-- Modern Header -->
        <div class="section-header">
            <div class="section-title-modern">
                <i class="fas fa-users-cog"></i>
                <span>Account Management</span>
            </div>
            <div class="flex flex-wrap gap-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: var(--primary);">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Staff</h3>
                        <div class="number"><?= count($activeStaff) ?></div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: var(--secondary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Residents</h3>
                        <div class="number"><?= count($pendingResidents) + count($approvedResidents) + count($declinedResidents) ?></div>
                    </div>
                </div>
                <?php if (count($unlinkedResidents) > 0): ?>
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: var(--warning);">
                        <i class="fas fa-unlink"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Unlinked</h3>
                        <div class="number"><?= count($unlinkedResidents) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modern Tabs -->
        <div class="modern-tabs mb-8">
            <button class="modern-tab active" onclick="switchTab('staff')" id="staffTab">
                <i class="fas fa-user-md mr-2"></i>
                Staff Management
                <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?= count($activeStaff) + count($inactiveStaff) ?></span>
            </button>
            <button class="modern-tab" onclick="switchTab('resident')" id="residentTab">
                <i class="fas fa-users mr-2"></i>
                Resident Management
                <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?= count($pendingResidents) + count($approvedResidents) + count($declinedResidents) ?></span>
            </button>
            <?php if (count($unlinkedResidents) > 0 || count($unlinkedPatients) > 0): ?>
            <button class="modern-tab" onclick="switchTab('linking')" id="linkingTab">
                <i class="fas fa-link mr-2"></i>
                Manual Linking
                <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?= count($unlinkedResidents) + count($unlinkedPatients) ?></span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Staff Section -->
        <div id="staffSection" class="tab-section">
            <!-- Create Staff Form -->
            <div class="modern-card p-8 mb-8">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Create New Staff Account
                </h2>
                
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div>
                        <label class="modern-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" required class="modern-input" placeholder="Enter username">
                    </div>
                    
                    <div>
                        <label class="modern-label">Password <span class="text-danger">*</span></label>
                        <div class="password-field">
                            <input type="text" name="password" required id="staff-password" class="modern-input" value="<?= bin2hex(random_bytes(4)) ?>">
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('staff-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar strength-strong"></div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="modern-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" required class="modern-input" placeholder="Enter full name">
                    </div>
                    
                    <div>
                        <label class="modern-label">Position <span class="text-danger">*</span></label>
                        <select name="position" required class="modern-input">
                            <option value="">Select Position</option>
                            <option value="Nurse">Nurse</option>
                            <option value="Midwife">Midwife</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Encoder">Encoder</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="modern-label">Specialization</label>
                        <input type="text" name="specialization" class="modern-input" placeholder="e.g., Pediatrics">
                    </div>
                    
                    <div>
                        <label class="modern-label">License Number</label>
                        <input type="text" name="license_number" class="modern-input" placeholder="Enter license number">
                    </div>
                    
                    <div class="md:col-span-2 lg:col-span-3">
                        <button type="submit" name="create_staff" class="btn-modern btn-modern-primary px-8">
                            <i class="fas fa-user-plus"></i>
                            Create Staff Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Staff Tabs -->
            <div class="modern-tabs mb-6">
                <button class="modern-tab active" onclick="showStaffTab('active')" id="activeStaffTab">
                    <i class="fas fa-check-circle mr-2"></i>
                    Active (<?= count($activeStaff) ?>)
                </button>
                <button class="modern-tab" onclick="showStaffTab('inactive')" id="inactiveStaffTab">
                    <i class="fas fa-pause-circle mr-2"></i>
                    Inactive (<?= count($inactiveStaff) ?>)
                </button>
            </div>

            <!-- Active Staff Grid -->
            <div id="activeStaffGrid" class="modern-grid">
                <?php if (empty($activeStaff)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-user-md text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No active staff accounts</h3>
                        <p class="text-gray-500">Create your first staff account above</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeStaff as $staff): ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                    <p class="text-sm text-primary">@<?= htmlspecialchars($staff['username']) ?></p>
                                </div>
                                <span class="modern-badge badge-success">Active</span>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <?php if ($staff['position']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-briefcase w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($staff['position']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($staff['specialization']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-stethoscope w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($staff['specialization']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-calendar-alt w-5"></i>
                                    Added <?= date('M j, Y', strtotime($staff['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn-modern btn-modern-outline flex-1">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" name="toggle_staff_status" 
                                            class="btn-modern btn-modern-warning w-full"
                                            onclick="return confirm('Deactivate this staff account?')">
                                        <i class="fas fa-pause"></i>
                                        Deactivate
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Inactive Staff Grid -->
            <div id="inactiveStaffGrid" class="modern-grid" style="display: none;">
                <?php if (empty($inactiveStaff)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-user-slash text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No inactive staff accounts</h3>
                        <p class="text-gray-500">All staff accounts are currently active</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($inactiveStaff as $staff): ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                    <p class="text-sm text-gray-500">@<?= htmlspecialchars($staff['username']) ?></p>
                                </div>
                                <span class="modern-badge badge-danger">Inactive</span>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <?php if ($staff['position']): ?>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-briefcase w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($staff['position']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-calendar-alt w-5"></i>
                                    Added <?= date('M j, Y', strtotime($staff['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn-modern btn-modern-outline flex-1">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" name="toggle_staff_status" 
                                            class="btn-modern btn-modern-secondary w-full">
                                        <i class="fas fa-play"></i>
                                        Activate
                                    </button>
                                </form>
                                
                                <button onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                        class="btn-modern btn-modern-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resident Section -->
        <div id="residentSection" class="tab-section" style="display: none;">
            <!-- Create Resident Form -->
            <div class="modern-card p-8 mb-8">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-secondary"></i>
                    Create New Resident Account
                </h2>
                
                <form method="POST" action="" id="residentForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div>
                        <label class="modern-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" required class="modern-input" placeholder="e.g., Juan Dela Cruz">
                    </div>
                    
                    <div>
                        <label class="modern-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" required class="modern-input" placeholder="juan@example.com">
                    </div>
                    
                    <div>
                        <label class="modern-label">Username</label>
                        <input type="text" name="username" class="modern-input" placeholder="Auto-generated from email">
                    </div>
                    
                    <div>
                        <label class="modern-label">Password <span class="text-danger">*</span></label>
                        <div class="password-field">
                            <input type="text" name="password" required id="resident-password" class="modern-input" value="<?= bin2hex(random_bytes(4)) ?>">
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('resident-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar strength-strong"></div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="modern-label">Phone</label>
                        <input type="tel" name="phone" class="modern-input" placeholder="+63 912 345 6789">
                    </div>
                    
                    <div>
                        <label class="modern-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="dob" class="modern-input" onchange="calculateAge()">
                    </div>
                    
                    <div>
                        <label class="modern-label">Gender</label>
                        <select name="gender" id="gender" class="modern-input">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="modern-label">Sitio</label>
                        <select name="sitio" class="modern-input">
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
                    
                    <div class="lg:col-span-3">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                <div>
                                    <p class="font-medium text-blue-800 mb-1">Important Information</p>
                                    <p class="text-sm text-blue-600">
                                        Account will be created without a patient record. 
                                        Use the Manual Linking tab to connect this account to a patient record.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-3">
                        <button type="submit" name="create_resident" class="btn-modern btn-modern-secondary px-8">
                            <i class="fas fa-user-plus"></i>
                            Create Resident Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Resident Tabs -->
            <div class="modern-tabs mb-6 flex-wrap">
                <button class="modern-tab active" onclick="showResidentTab('pending')" id="pendingResidentTab">
                    <i class="fas fa-clock mr-2"></i>
                    Pending (<?= count($pendingResidents) ?>)
                </button>
                <button class="modern-tab" onclick="showResidentTab('approved')" id="approvedResidentTab">
                    <i class="fas fa-check-circle mr-2"></i>
                    Approved (<?= count($approvedResidents) ?>)
                </button>
                <button class="modern-tab" onclick="showResidentTab('declined')" id="declinedResidentTab">
                    <i class="fas fa-times-circle mr-2"></i>
                    Declined (<?= count($declinedResidents) ?>)
                </button>
                <?php if (count($unlinkedResidents) > 0): ?>
                <button class="modern-tab" onclick="showResidentTab('unlinked')" id="unlinkedResidentTab">
                    <i class="fas fa-unlink mr-2"></i>
                    Unlinked (<?= count($unlinkedResidents) ?>)
                </button>
                <?php endif; ?>
            </div>

            <!-- Pending Residents Grid -->
            <div id="pendingResidentGrid" class="modern-grid">
                <?php if (empty($pendingResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-yellow-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-clock text-3xl text-yellow-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No pending residents</h3>
                        <p class="text-gray-500">All applications have been processed</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingResidents as $resident): ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-sm text-gray-500">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="modern-badge badge-warning">Pending</span>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                <?php if ($resident['sitio']): ?>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-map-marker-alt w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($resident['sitio']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-calendar-plus w-5"></i>
                                    Applied <?= date('M j, Y', strtotime($resident['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="toggle_resident_status" class="btn-modern btn-modern-secondary w-full">
                                        <i class="fas fa-check"></i>
                                        Approve
                                    </button>
                                </form>
                                
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" name="toggle_resident_status" class="btn-modern btn-modern-danger w-full">
                                        <i class="fas fa-times"></i>
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Approved Residents Grid -->
            <div id="approvedResidentGrid" class="modern-grid" style="display: none;">
                <?php if (empty($approvedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-green-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-check-circle text-3xl text-green-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No approved residents</h3>
                        <p class="text-gray-500">Approve pending accounts to see them here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approvedResidents as $resident): 
                        $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
                        $stmt->execute([$resident['id']]);
                        $hasPatientRecord = $stmt->fetch();
                    ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-sm text-secondary">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <?php if (!$hasPatientRecord): ?>
                                        <span class="modern-badge badge-warning">Unlinked</span>
                                    <?php else: ?>
                                        <span class="modern-badge badge-success">Linked</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                <?php if ($resident['contact']): ?>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-phone w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($resident['contact']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-id-card w-5"></i>
                                    ID: <?= htmlspecialchars($resident['unique_number']) ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="openResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                        class="btn-modern btn-modern-outline flex-1">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                                
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" name="toggle_resident_status" 
                                            class="btn-modern btn-modern-warning w-full"
                                            onclick="return confirm('Suspend this account?')">
                                        <i class="fas fa-pause"></i>
                                        Suspend
                                    </button>
                                </form>
                                
                                <?php if (!$hasPatientRecord): ?>
                                <button onclick="switchToLinking(<?= $resident['id'] ?>)"
                                        class="btn-modern btn-modern-primary">
                                    <i class="fas fa-link"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Declined Residents Grid -->
            <div id="declinedResidentGrid" class="modern-grid" style="display: none;">
                <?php if (empty($declinedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-red-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-times-circle text-3xl text-red-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No declined residents</h3>
                        <p class="text-gray-500">No applications have been declined</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($declinedResidents as $resident): ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-sm text-gray-500">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="modern-badge badge-danger">Declined</span>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-calendar-times w-5"></i>
                                    Declined <?= date('M j, Y', strtotime($resident['updated_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <form method="POST" action="" class="flex-1">
                                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="toggle_resident_status" class="btn-modern btn-modern-secondary w-full">
                                        <i class="fas fa-check"></i>
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Unlinked Residents Grid -->
            <div id="unlinkedResidentGrid" class="modern-grid" style="display: none;">
                <?php if (empty($unlinkedResidents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-link text-3xl text-orange-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">All accounts are linked!</h3>
                        <p class="text-gray-500">All resident accounts have patient records</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($unlinkedResidents as $resident): ?>
                        <div class="account-card-modern">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                    <p class="text-sm text-warning">@<?= htmlspecialchars($resident['username']) ?></p>
                                </div>
                                <span class="modern-badge badge-warning">Unlinked</span>
                            </div>
                            
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                                    <?= htmlspecialchars($resident['email']) ?>
                                </div>
                                <?php if ($resident['age'] > 0): ?>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-user w-5 text-gray-400"></i>
                                        Age: <?= htmlspecialchars($resident['age']) ?> years
                                    </div>
                                <?php endif; ?>
                                <?php if ($resident['sitio']): ?>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-map-marker-alt w-5 text-gray-400"></i>
                                        <?= htmlspecialchars($resident['sitio']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="openResidentPasswordModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                        class="btn-modern btn-modern-outline flex-1">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                                
                                <button onclick="switchToLinking(<?= $resident['id'] ?>)"
                                        class="btn-modern btn-modern-primary flex-1">
                                    <i class="fas fa-link"></i>
                                    Link Record
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Linking Section -->
        <div id="linkingSection" class="tab-section" style="display: none;">
            <div class="modern-card p-8">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-link text-purple-600"></i>
                    Manual Account-Patient Linking
                </h2>

                <?php if (count($unlinkedResidents) === 0 && count($unlinkedPatients) === 0): ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 mx-auto bg-green-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-check-circle text-3xl text-green-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">All accounts are properly linked!</h3>
                        <p class="text-gray-500">No manual linking needed at this time.</p>
                    </div>
                <?php else: ?>
                    <!-- Linking Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Unlinked Residents Column -->
                        <div>
                            <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
                                <i class="fas fa-user-circle text-blue-500"></i>
                                Unlinked Residents
                                <span class="modern-badge badge-info"><?= count($unlinkedResidents) ?></span>
                            </h3>
                            
                            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                <?php foreach ($unlinkedResidents as $resident): ?>
                                    <div class="link-card" onclick="selectResident(<?= $resident['id'] ?>, this)" data-resident-id="<?= $resident['id'] ?>">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-500"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="font-semibold"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($resident['email']) ?></div>
                                                <div class="flex gap-2 mt-2">
                                                    <?php if ($resident['sitio']): ?>
                                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">
                                                            <?= htmlspecialchars($resident['sitio']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($resident['age'] > 0): ?>
                                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">
                                                            Age: <?= htmlspecialchars($resident['age']) ?>
                                                        </span>
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
                            <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
                                <i class="fas fa-file-medical text-green-500"></i>
                                Unlinked Patient Records
                                <span class="modern-badge badge-success"><?= count($unlinkedPatients) ?></span>
                            </h3>
                            
                            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                <?php foreach ($unlinkedPatients as $patient): ?>
                                    <div class="link-card patient" onclick="selectPatient(<?= $patient['id'] ?>, this)" data-patient-id="<?= $patient['id'] ?>">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-file-medical text-green-500"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="font-semibold"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                <div class="flex gap-2 mt-2">
                                                    <?php if ($patient['age']): ?>
                                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">
                                                            Age: <?= htmlspecialchars($patient['age']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($patient['gender']): ?>
                                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">
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
                    <div id="selectedPanel" class="bg-gradient-to-r from-blue-50 to-green-50 rounded-xl p-6 border-2 border-blue-200" style="display: none;">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold flex items-center gap-2">
                                <i class="fas fa-handshake text-blue-600"></i>
                                Ready to Link
                            </h4>
                            <button onclick="clearSelection()" class="text-sm text-gray-600 hover:text-gray-800">
                                <i class="fas fa-times mr-1"></i> Clear
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-white rounded-xl p-4 border-2 border-blue-200">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-user text-blue-500"></i>
                                    <span class="font-medium" id="selectedResidentName">No resident selected</span>
                                </div>
                                <div class="text-sm text-gray-500" id="selectedResidentDetails"></div>
                            </div>

                            <div class="bg-white rounded-xl p-4 border-2 border-green-200">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-file-medical text-green-500"></i>
                                    <span class="font-medium" id="selectedPatientName">No patient selected</span>
                                </div>
                                <div class="text-sm text-gray-500" id="selectedPatientDetails"></div>
                            </div>
                        </div>

                        <button onclick="performLinking()" id="linkButton" disabled
                                class="btn-modern btn-modern-primary w-full md:w-auto px-8">
                            <i class="fas fa-link mr-2"></i>
                            Link Accounts
                        </button>
                    </div>

                    <input type="hidden" id="selectedResidentId" value="0">
                    <input type="hidden" id="selectedPatientId" value="0">
                <?php endif; ?>
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

    <!-- Resident Password Modal -->
    <div class="modern-modal" id="residentPasswordModal">
        <div class="modern-modal-content">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-key text-secondary"></i>
                Change Resident Password
            </h3>
            
            <div class="bg-green-50 rounded-xl p-4 mb-6">
                <p class="font-medium" id="residentPasswordName"></p>
            </div>
            
            <form method="POST" action="" id="residentPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="resident_id" id="residentPasswordId">
                
                <div class="mb-4">
                    <label class="modern-label">Current Password</label>
                    <div class="password-field">
                        <input type="password" name="current_password" id="residentCurrentPass" required class="modern-input">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('residentCurrentPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="modern-label">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="residentNewPass" required class="modern-input" minlength="6" oninput="checkResidentPasswordStrength()">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('residentNewPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="residentPasswordStrength"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="modern-label">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="residentConfirmPass" required class="modern-input" minlength="6" oninput="checkResidentPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePasswordField('residentConfirmPass')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="residentPassMessage" class="validation-message"></div>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeResidentPasswordModal()" class="btn-modern btn-modern-outline flex-1">
                        Cancel
                    </button>
                    <button type="submit" name="change_resident_password" class="btn-modern btn-modern-secondary flex-1">
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
            
            // Remove active class from all tabs
            document.querySelectorAll('.modern-tab').forEach(t => t.classList.remove('active'));
            
            // Show selected section and activate tab
            if (tab === 'staff') {
                document.getElementById('staffSection').style.display = 'block';
                document.getElementById('staffTab').classList.add('active');
            } else if (tab === 'resident') {
                document.getElementById('residentSection').style.display = 'block';
                document.getElementById('residentTab').classList.add('active');
            } else if (tab === 'linking') {
                document.getElementById('linkingSection').style.display = 'block';
                document.getElementById('linkingTab').classList.add('active');
            }
        }

        function showStaffTab(tab) {
            document.getElementById('activeStaffGrid').style.display = tab === 'active' ? 'grid' : 'none';
            document.getElementById('inactiveStaffGrid').style.display = tab === 'inactive' ? 'grid' : 'none';
            
            document.getElementById('activeStaffTab').classList.toggle('active', tab === 'active');
            document.getElementById('inactiveStaffTab').classList.toggle('active', tab === 'inactive');
        }

        function showResidentTab(tab) {
            const tabs = ['pending', 'approved', 'declined', 'unlinked'];
            tabs.forEach(t => {
                const grid = document.getElementById(t + 'ResidentGrid');
                if (grid) grid.style.display = 'none';
            });
            
            const selectedGrid = document.getElementById(tab + 'ResidentGrid');
            if (selectedGrid) selectedGrid.style.display = 'grid';
            
            document.querySelectorAll('#residentSection .modern-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tab + 'ResidentTab').classList.add('active');
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
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            element.classList.add('selected');
            selectedResidentId = id;
            document.getElementById('selectedResidentId').value = id;
            
            // Update selected panel
            const name = element.querySelector('.font-semibold').textContent;
            const email = element.querySelector('.text-sm.text-gray-500')?.textContent || '';
            const details = element.querySelector('.flex.gap-2')?.innerHTML || '';
            
            document.getElementById('selectedResidentName').textContent = name;
            document.getElementById('selectedResidentDetails').innerHTML = `
                <div>${email}</div>
                <div class="flex gap-2 mt-2">${details}</div>
            `;
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function selectPatient(id, element) {
            // Remove selection from all patient cards
            document.querySelectorAll('.link-card.patient').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            element.classList.add('selected');
            selectedPatientId = id;
            document.getElementById('selectedPatientId').value = id;
            
            // Update selected panel
            const name = element.querySelector('.font-semibold').textContent;
            const details = element.querySelector('.flex.gap-2')?.innerHTML || '';
            
            document.getElementById('selectedPatientName').textContent = name;
            document.getElementById('selectedPatientDetails').innerHTML = `
                <div class="flex gap-2 mt-2">${details}</div>
            `;
            
            document.getElementById('selectedPanel').style.display = 'block';
            updateLinkButton();
        }

        function updateLinkButton() {
            const linkButton = document.getElementById('linkButton');
            if (linkButton) {
                linkButton.disabled = !(selectedResidentId > 0 && selectedPatientId > 0);
            }
        }

        function clearSelection() {
            selectedResidentId = 0;
            selectedPatientId = 0;
            document.getElementById('selectedResidentId').value = '0';
            document.getElementById('selectedPatientId').value = '0';
            
            document.querySelectorAll('.link-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            document.getElementById('selectedPanel').style.display = 'none';
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
</body>
</html>