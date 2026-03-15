<?php
// Ensure the 'updated_at' column exists in sitio1_staff before update
function ensureStaffUpdatedAtColumn($pdo)
{
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
    function ensureActivityLogTable($pdo)
    {
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

function setErrorAndRedirect($msg, $type = 'error', $redirect = 'manage_accounts.php')
{
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

// Handle resident hard delete
elseif (isset($_POST['hard_delete_resident'])) {
    $residentId = intval($_POST['resident_id']);
    $deleteAction = $_POST['delete_action'] ?? 'delete';
    $reassignTo = intval($_POST['reassign_to'] ?? 0);

    try {
        // Get resident name for log
        $nameStmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
        $nameStmt->execute([$residentId]);
        $residentName = $nameStmt->fetchColumn();

        if (!$residentName) {
            $_SESSION['message'] = 'Resident not found.';
            $_SESSION['message_type'] = 'error';
            header('Location: manage_accounts.php');
            exit();
        }

        $pdo->beginTransaction();

        if ($deleteAction === 'reassign' && $reassignTo > 0) {
            // Get reassign resident name for log
            $reassignStmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
            $reassignStmt->execute([$reassignTo]);
            $reassignName = $reassignStmt->fetchColumn();

            // Reassign patient records to another resident
            $stmt = $pdo->prepare("UPDATE sitio1_patients SET user_id = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$reassignTo, $residentId]);

            // Log the reassignment
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'resident_deleted', 'Deleted resident: ' . $residentName . ' and reassigned patient records to: ' . $reassignName, $_SERVER['REMOTE_ADDR']]);

            $_SESSION['message'] = 'Resident account deleted and patient records reassigned to ' . htmlspecialchars($reassignName) . ' successfully!';
        } else {
            // Delete all associated patient records
            $stmt = $pdo->prepare("DELETE FROM sitio1_patients WHERE user_id = ?");
            $stmt->execute([$residentId]);

            // Log the deletion
            $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['user_id'], 'resident_deleted', 'Deleted resident: ' . $residentName . ' and all associated patient records', $_SERVER['REMOTE_ADDR']]);

            $_SESSION['message'] = 'Resident account and associated patient records deleted successfully!';
        }

        // Delete the resident account
        $stmt = $pdo->prepare("DELETE FROM sitio1_users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$residentId]);

        $pdo->commit();
        $_SESSION['message_type'] = 'success';
        header('Location: manage_accounts.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Error deleting resident: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: manage_accounts.php');
        exit();
    }
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
    } elseif (isset($_POST['create_staff'])) {
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

        // Check for dependencies with dynamic column detection
        $dependencies = [];

        // Helper function to check column existence
        function getStaffColumn($pdo, $table, $possibleColumns = ['staff_id', 'created_by', 'user_id', 'added_by']) {
            try {
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($possibleColumns as $column) {
                    if (in_array($column, $columns)) {
                        return $column;
                    }
                }
            } catch (PDOException $e) {
                // Table doesn't exist, return null
                return null;
            }
            return null;
        }

        // Check announcements - using staff_id as per foreign key constraint
        $announcementsColumn = 'staff_id'; // Force using staff_id since that's what the foreign key uses
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_announcements WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $announcementsCount = $stmt->fetchColumn();
            if ($announcementsCount > 0) {
                $dependencies[] = "$announcementsCount announcement(s)";
            }
        } catch (PDOException $e) {
            // Table might not exist or other issue
            error_log("Error checking announcements: " . $e->getMessage());
        }

        // Check consultations
        $consultationsColumn = getStaffColumn($pdo, 'sitio1_consultations', ['staff_id', 'doctor_id', 'created_by', 'user_id']);
        if ($consultationsColumn) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_consultations WHERE $consultationsColumn = ?");
            $stmt->execute([$staffId]);
            $consultationsCount = $stmt->fetchColumn();
            if ($consultationsCount > 0) {
                $dependencies[] = "$consultationsCount consultation(s)";
            }
        }

        // Check patient records
        $patientsColumn = getStaffColumn($pdo, 'sitio1_patients', ['added_by', 'created_by', 'staff_id', 'user_id']);
        if ($patientsColumn) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE $patientsColumn = ?");
            $stmt->execute([$staffId]);
            $patientsCount = $stmt->fetchColumn();
            if ($patientsCount > 0) {
                $dependencies[] = "$patientsCount patient record(s)";
            }
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

                    // Reassign announcements - using staff_id as per foreign key constraint
                    try {
                        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = ?, updated_at = NOW() WHERE staff_id = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                    } catch (PDOException $e) {
                        error_log("Error reassigning announcements: " . $e->getMessage());
                    }

                    // Reassign consultations
                    if ($consultationsColumn) {
                        $stmt = $pdo->prepare("UPDATE sitio1_consultations SET $consultationsColumn = ?, updated_at = NOW() WHERE $consultationsColumn = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                    }

                    // Reassign patient records
                    if ($patientsColumn) {
                        $stmt = $pdo->prepare("UPDATE sitio1_patients SET $patientsColumn = ?, updated_at = NOW() WHERE $patientsColumn = ?");
                        $stmt->execute([$reassignTo, $staffId]);
                    }

                    // Log the reassignment
                    $logStmt = $pdo->prepare("INSERT INTO sitio1_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $logStmt->execute([$_SESSION['user_id'], 'staff_deleted', 'Deleted staff: ' . $staffName . ' and reassigned records to: ' . $reassignName, $_SERVER['REMOTE_ADDR']]);

                    $_SESSION['message'] = 'Staff account deleted and records reassigned to ' . htmlspecialchars($reassignName) . ' successfully!';
                } else {
                    // For "delete all associated records" option, we need to either:
                    // Option A: Delete the announcements (if that's allowed)
                    // Option B: Set them to NULL (but foreign key might prevent NULL)
                    
                    // First, try to delete announcements (if they exist)
                    try {
                        $stmt = $pdo->prepare("DELETE FROM sitio1_announcements WHERE staff_id = ?");
                        $stmt->execute([$staffId]);
                    } catch (PDOException $e) {
                        // If delete fails due to constraints, try setting to NULL
                        error_log("Could not delete announcements, trying to set NULL: " . $e->getMessage());
                        try {
                            // Check if staff_id allows NULL
                            $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = NULL, updated_at = NOW() WHERE staff_id = ?");
                            $stmt->execute([$staffId]);
                        } catch (PDOException $e2) {
                            // If NULL not allowed, we need to reassign to a default staff or skip
                            error_log("Could not set announcements to NULL: " . $e2->getMessage());
                            // Find any other active staff to reassign to
                            $defaultStaffStmt = $pdo->prepare("SELECT id FROM sitio1_staff WHERE id != ? AND is_active = 1 LIMIT 1");
                            $defaultStaffStmt->execute([$staffId]);
                            $defaultStaffId = $defaultStaffStmt->fetchColumn();
                            
                            if ($defaultStaffId) {
                                $stmt = $pdo->prepare("UPDATE sitio1_announcements SET staff_id = ?, updated_at = NOW() WHERE staff_id = ?");
                                $stmt->execute([$defaultStaffId, $staffId]);
                            }
                        }
                    }

                    // Delete consultations
                    if ($consultationsColumn) {
                        try {
                            $stmt = $pdo->prepare("DELETE FROM sitio1_consultations WHERE $consultationsColumn = ?");
                            $stmt->execute([$staffId]);
                        } catch (PDOException $e) {
                            error_log("Error deleting consultations: " . $e->getMessage());
                        }
                    }

                    // Set patients column to NULL or delete based on constraints
                    if ($patientsColumn) {
                        try {
                            $stmt = $pdo->prepare("UPDATE sitio1_patients SET $patientsColumn = NULL, updated_at = NOW() WHERE $patientsColumn = ?");
                            $stmt->execute([$staffId]);
                        } catch (PDOException $e) {
                            error_log("Error updating patients: " . $e->getMessage());
                        }
                    }

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
function manuallyLinkToPatientRecord($pdo, $residentUserId, $patientId, $patientRecordUID = null)
{
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
function getUnlinkedResidents($pdo)
{
    try {
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
function getUnlinkedPatients($pdo)
{
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
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/Superadmin-Manageaccount.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom dropdown arrow styles -->
    <style>
        /* Custom dropdown styles for consistent arrows */
        .custom-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%233C96E1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2rem;
            padding-right: 2.5rem !important;
            cursor: pointer;
        }

        .custom-select:hover {
            border-color: #2563eb;
        }

        .custom-select:focus {
            outline: none;
            ring: 2px solid #3C96E1;
            border-color: #3C96E1;
        }

        /* For Firefox */
        .custom-select::-moz-focus-inner {
            border: 0;
        }

        /* For IE */
        .custom-select::-ms-expand {
            display: none;
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

    <main style="background: linear-gradient(135deg, #f0f9ff 0%, #f9fafb 100%); min-height: 100vh;">
        <div style="width: 100%; padding: 2rem;">
            <!-- Page Header with Stats -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 1.5rem; font-weight: 600; color: #374151; margin-bottom: 1.5rem;">Account Management</h1>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <!-- ACTIVE ADMIN -->
                    <div class="stat-card">
                        <div class="flex justify-between items-center gap-4 mb-6">
                            <div>
                                <span class="stat-number"><?= count($activeStaff) ?></span>
                            </div>
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-blue-50">
                                <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z" fill="#2563EB" fill-opacity="0.3" />
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M24.666 40.75C26.7343 40.75 28.7824 40.3426 30.6933 39.5511C32.6042 38.7596 34.3404 37.5995 35.8029 36.1369C37.2655 34.6744 38.4256 32.9381 39.2171 31.0273C40.0086 29.1164 40.416 27.0683 40.416 25C40.416 22.9317 40.0086 20.8836 39.2171 18.9727C38.4256 17.0619 37.2655 15.3256 35.8029 13.8631C34.3404 12.4005 32.6042 11.2404 30.6933 10.4489C28.7824 9.65739 26.7343 9.25 24.666 9.25C20.4889 9.25 16.4828 10.9094 13.5291 13.8631C10.5754 16.8168 8.91602 20.8228 8.91602 25C8.91602 29.1772 10.5754 33.1832 13.5291 36.1369C16.4828 39.0906 20.4889 40.75 24.666 40.75ZM24.26 31.37L33.01 20.87L30.322 18.63L22.797 27.6583L18.9033 23.7628L16.4288 26.2372L21.6788 31.4872L23.0333 32.8418L24.26 31.37Z" fill="#3C96E1" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <span class="stat-label">Active Staff</span>
                        </div>
                    </div>

                    <!-- RESIDENTS ACCOUNTS -->
                    <div class="stat-card">
                        <div class="flex justify-between items-center gap-4 mb-6">
                            <div>
                                <span class="stat-number"><?= count($approvedResidents) ?></span>
                            </div>
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-blue-50">
                                <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z" fill="#D97706" fill-opacity="0.3" />
                                    <path d="M28.6042 18.4375C29.6426 18.4375 30.6576 18.1296 31.521 17.5527C32.3843 16.9758 33.0572 16.1559 33.4546 15.1966C33.8519 14.2373 33.9559 13.1817 33.7533 12.1633C33.5508 11.1449 33.0507 10.2094 32.3165 9.47519C31.5823 8.74097 30.6468 8.24095 29.6284 8.03838C28.61 7.83581 27.5544 7.93977 26.5951 8.33713C25.6358 8.73449 24.8159 9.4074 24.239 10.2708C23.6621 11.1341 23.3542 12.1492 23.3542 13.1875C23.3542 14.5799 23.9073 15.9152 24.8919 16.8998C25.8765 17.8844 27.2118 18.4375 28.6042 18.4375ZM28.6042 10.5625C29.1234 10.5625 29.6309 10.7165 30.0626 11.0049C30.4943 11.2933 30.8307 11.7033 31.0294 12.183C31.2281 12.6626 31.2801 13.1904 31.1788 13.6996C31.0775 14.2088 30.8275 14.6765 30.4604 15.0437C30.0933 15.4108 29.6255 15.6608 29.1163 15.7621C28.6071 15.8634 28.0793 15.8114 27.5997 15.6127C27.12 15.414 26.71 15.0776 26.4216 14.6459C26.1332 14.2142 25.9792 13.7067 25.9792 13.1875C25.9792 12.4913 26.2558 11.8236 26.7481 11.3313C27.2403 10.8391 27.908 10.5625 28.6042 10.5625ZM39.6473 27.0803C39.5472 27.1263 38.4184 27.6184 36.4201 27.6184C34.1479 27.6184 30.7518 26.9819 26.4632 24.3372C25.8105 26.1902 24.963 27.9688 23.935 29.643C25.7814 30.2114 27.5182 31.0884 29.0718 32.2368C32.2005 34.6223 33.8542 38.0184 33.8542 42.0625C33.8542 42.4106 33.7159 42.7444 33.4698 42.9906C33.2236 43.2367 32.8898 43.375 32.5417 43.375C32.1936 43.375 31.8598 43.2367 31.6136 42.9906C31.3675 42.7444 31.2292 42.4106 31.2292 42.0625C31.2292 35.2211 25.5379 32.7585 22.3469 31.9152C22.2566 32.0301 22.1631 32.1466 22.0696 32.2598C18.8474 36.1645 14.8098 38.1955 10.3178 38.1955C9.80616 38.1979 9.29472 38.1744 8.78546 38.125C8.43736 38.0902 8.11735 37.9185 7.89582 37.6478C7.67429 37.377 7.5694 37.0293 7.60421 36.6813C7.63902 36.3332 7.81068 36.0131 8.08144 35.7916C8.35219 35.5701 8.69986 35.4652 9.04796 35.5C13.3005 35.9233 17.0001 34.2712 20.0401 30.5781C22.0893 28.0942 23.4855 25.064 24.1827 22.8672C17.7974 19.1512 13.7188 22.3143 13.6745 22.3488C13.5408 22.4629 13.3855 22.5491 13.2179 22.6021C13.0503 22.6551 12.8737 22.6739 12.6987 22.6573C12.5237 22.6408 12.3537 22.5892 12.199 22.5057C12.0443 22.4223 11.9079 22.3085 11.798 22.1713C11.688 22.0341 11.6068 21.8763 11.559 21.7071C11.5113 21.5379 11.498 21.3608 11.52 21.1864C11.542 21.0119 11.5989 20.8437 11.6871 20.6917C11.7754 20.5396 11.8933 20.4068 12.0339 20.3013C12.28 20.1044 18.1403 15.5434 26.7191 21.3791C34.179 26.4503 38.5201 24.7113 38.5612 24.6916C38.7184 24.6173 38.8888 24.575 39.0626 24.5672C39.2363 24.5594 39.4098 24.5862 39.5731 24.646C39.7364 24.7058 39.8862 24.7974 40.0137 24.9156C40.1413 25.0338 40.2441 25.1762 40.3161 25.3345C40.3882 25.4927 40.4281 25.6637 40.4335 25.8375C40.4389 26.0113 40.4097 26.1845 40.3477 26.3469C40.2856 26.5094 40.1918 26.6579 40.0719 26.7838C39.9519 26.9097 39.8081 27.0105 39.6489 27.0803H39.6473Z" fill="#D97706" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <span class="stat-label">Resident Accounts</span>
                        </div>
                    </div>

                    <!-- LINKED ACCOUNTS -->
                    <div class="stat-card">
                        <div class="flex justify-between items-center gap-4 mb-6">
                            <div>
                                <span class="stat-number"><?= count($approvedResidents) - count($unlinkedResidents) ?></span>
                            </div>
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-blue-50">
                                <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2212_12342)">
                                        <path d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z" fill="#D97706" fill-opacity="0.3" />
                                        <g clip-path="url(#clip1_2212_12342)">
                                            <path d="M18.7085 28.6428L30.7737 18.5189C31.0404 18.2951 31.385 18.1865 31.7318 18.2168C32.0785 18.2472 32.399 18.414 32.6228 18.6807C32.8466 18.9473 32.9552 19.2919 32.9249 19.6387C32.8945 19.9855 32.7277 20.306 32.461 20.5298L20.3958 30.6537C20.1292 30.8774 19.7845 30.9861 19.4378 30.9557C19.091 30.9254 18.7705 30.7585 18.5467 30.4919C18.323 30.2252 18.2143 29.8806 18.2447 29.5338C18.275 29.1871 18.4419 28.8665 18.7085 28.6428ZM26.7868 32.1444L21.7596 36.3627C20.4263 37.4815 18.7032 38.0248 16.9693 37.8731C15.2355 37.7214 13.6329 36.8871 12.5141 35.5538C11.3954 34.2206 10.8521 32.4974 11.0038 30.7636C11.1555 29.0297 11.9897 27.4271 13.323 26.3084L18.3502 22.0901C18.6168 21.8663 18.7837 21.5458 18.814 21.199C18.8444 20.8523 18.7357 20.5077 18.5119 20.241C18.2882 19.9743 17.9677 19.8075 17.6209 19.7771C17.2741 19.7468 16.9295 19.8555 16.6629 20.0792L11.6357 24.2975C9.76909 25.8638 8.60113 28.1074 8.38876 30.5348C8.17639 32.9622 8.937 35.3746 10.5033 37.2412C12.0695 39.1078 14.3132 40.2757 16.7406 40.4881C19.168 40.7005 21.5803 39.9398 23.4469 38.3736L28.4741 34.1553C28.7407 33.9315 28.9076 33.611 28.9379 33.2642C28.9683 32.9175 28.8596 32.5729 28.6359 32.3062C28.4121 32.0395 28.0916 31.8727 27.7448 31.8423C27.398 31.812 27.0534 31.9207 26.7868 32.1444ZM27.7226 10.799L22.6955 15.0173C22.4288 15.241 22.2619 15.5615 22.2316 15.9083C22.2013 16.2551 22.3099 16.5997 22.5337 16.8664C22.7574 17.133 23.0779 17.2999 23.4247 17.3302C23.7715 17.3605 24.1161 17.2519 24.3828 17.0281L29.4099 12.8098C30.7432 11.6911 32.4663 11.1478 34.2002 11.2995C35.9341 11.4512 37.5366 12.2854 38.6554 13.6187C39.7742 14.952 40.3175 16.6751 40.1658 18.409C40.0141 20.1428 39.1798 21.7454 37.8465 22.8642L32.8194 27.0825C32.5527 27.3062 32.3859 27.6267 32.3555 27.9735C32.3252 28.3203 32.4338 28.6649 32.6576 28.9316C32.8813 29.1982 33.2019 29.3651 33.5486 29.3954C33.8954 29.4257 34.24 29.3171 34.5067 29.0933L39.5338 24.875C41.4004 23.3088 42.5684 21.0652 42.7808 18.6377C42.9931 16.2103 42.2325 13.798 40.6663 11.9314C39.1 10.0648 36.8564 8.89684 34.429 8.68447C32.0016 8.4721 29.5892 9.23271 27.7226 10.799Z" fill="#D97706" />
                                        </g>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2212_12342">
                                            <path d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z" fill="white" />
                                        </clipPath>
                                        <clipPath id="clip1_2212_12342">
                                            <rect width="42" height="42" fill="white" transform="translate(-4 21.9961) rotate(-40)" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <span class="stat-label">Linked Accounts</span>
                        </div>
                    </div>
                </div>

                <!-- Main Navigation Tabs -->
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
                <!-- Staff Section (with updated Position dropdown) -->
                <div id="staffSection" class="tab-section" style="display: block;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Create New Staff Account</h2>
                    <!-- Create Staff Form -->
                    <div class="flex flex-col md:flex-row w-full gap-8">
                        <!-- LEFT CONTENT -->
                        <div class="mb-8 p-8 rounded-lg border border-gray-300 w-full md:w-1/2">
                            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                <!-- Username -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="username"
                                        required
                                        placeholder="Enter Username"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Password -->
                                <div>
                                    <label style="display:block;font-size:1.125rem;font-weight:600;color:#374151;margin-bottom:4px;">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div style="position: relative;">
                                        <input type="text" name="password" required id="staff-password"
                                            placeholder="Enter Password"
                                            class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                        <button type="button"
                                            onclick="togglePasswordVisibility('staff-password')"
                                            style="position:absolute;right:20px;top:50%;transform:translateY(-50%);background:none;border:none;color:#3C96E1;cursor:pointer;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="full_name"
                                        required
                                        placeholder="Enter Full Name"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Position - Updated with custom-select class for consistent arrow -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Position *
                                    </label>
                                    <select
                                        name="position"
                                        required
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500 custom-select">
                                        <option value="">Select Position</option>
                                        <option value="Nurse">Nurse</option>
                                        <option value="Midwife">Midwife</option>
                                        <option value="Doctor">Doctor</option>
                                        <option value="Encoder">Encoder</option>
                                    </select>
                                </div>

                                <!-- Specialization -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Specialization
                                    </label>
                                    <input
                                        type="text"
                                        name="specialization"
                                        placeholder="e.g., Pediatrics"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- License -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        License Number
                                    </label>
                                    <input
                                        type="text"
                                        name="license_number"
                                        placeholder="Enter License Number"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Button -->
                                <div class="mt-2 col-span-2">
                                    <button
                                        type="submit"
                                        name="create_staff"
                                        class="w-auto px-6 py-4 bg-[#3C96E1] text-white rounded-md text-lg font-medium hover:bg-blue-600 transition flex items-center gap-2">
                                        <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M15 2.8125C12.5895 2.8125 10.2332 3.52728 8.22899 4.86646C6.22477 6.20564 4.66267 8.10907 3.74022 10.336C2.81778 12.563 2.57643 15.0135 3.04668 17.3777C3.51694 19.7418 4.67769 21.9134 6.38214 23.6179C8.08659 25.3223 10.2582 26.4831 12.6223 26.9533C14.9865 27.4236 17.437 27.1822 19.664 26.2598C21.8909 25.3373 23.7944 23.7752 25.1335 21.771C26.4727 19.7668 27.1875 17.4105 27.1875 15C27.1841 11.7687 25.899 8.67076 23.6141 6.3859C21.3292 4.10104 18.2313 2.81591 15 2.8125ZM15 25.3125C12.9604 25.3125 10.9666 24.7077 9.27069 23.5745C7.5748 22.4414 6.25303 20.8308 5.4725 18.9464C4.69197 17.0621 4.48775 14.9886 4.88566 12.9881C5.28357 10.9877 6.26574 9.15019 7.70797 7.70796C9.1502 6.26573 10.9877 5.28356 12.9881 4.88565C14.9886 4.48774 17.0621 4.69196 18.9464 5.47249C20.8308 6.25302 22.4414 7.5748 23.5745 9.27068C24.7077 10.9666 25.3125 12.9604 25.3125 15C25.3094 17.7341 24.2219 20.3553 22.2886 22.2886C20.3553 24.2219 17.7341 25.3094 15 25.3125ZM20.625 15C20.625 15.2486 20.5262 15.4871 20.3504 15.6629C20.1746 15.8387 19.9361 15.9375 19.6875 15.9375H15.9375V19.6875C15.9375 19.9361 15.8387 20.1746 15.6629 20.3504C15.4871 20.5262 15.2486 20.625 15 20.625C14.7514 20.625 14.5129 20.5262 14.3371 20.3504C14.1613 20.1746 14.0625 19.9361 14.0625 19.6875V15.9375H10.3125C10.0639 15.9375 9.82541 15.8387 9.64959 15.6629C9.47378 15.4871 9.375 15.2486 9.375 15C9.375 14.7514 9.47378 14.5129 9.64959 14.3371C9.82541 14.1613 10.0639 14.0625 10.3125 14.0625H14.0625V10.3125C14.0625 10.0639 14.1613 9.8254 14.3371 9.64959C14.5129 9.47377 14.7514 9.375 15 9.375C15.2486 9.375 15.4871 9.47377 15.6629 9.64959C15.8387 9.8254 15.9375 10.0639 15.9375 10.3125V14.0625H19.6875C19.9361 14.0625 20.1746 14.1613 20.3504 14.3371C20.5262 14.5129 20.625 14.7514 20.625 15Z" fill="white"/>
</svg>
Create Account
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- RIGHT CONTENT - Staff Lists -->
                        <div class="w-full md:w-1/2">
                            <div class="p-8 rounded-lg border border-gray-300">
                                <!-- Staff Tabs -->
                                <div class="mb-6">
                                    <div class="flex gap-6">
                                        <button
                                            onclick="showStaffTab('active')"
                                            id="activeStaffMainTab"
                                            class="py-3 px-6 rounded-md text-base font-semibold">
                                            Active Account
                                        </button>
                                        <button
                                            onclick="showStaffTab('inactive')"
                                            id="inactiveStaffMainTab"
                                            class="py-3 px-6 rounded-md text-base font-semibold">
                                            Inactive Account
                                        </button>
                                    </div>
                                </div>

                                <!-- ACTIVE STAFF -->
                                <div id="activeStaffSection">
                                    <?php if (empty($activeStaff)): ?>
                                        <div class="text-center p-12 bg-white rounded border border-gray-200">
                                            <p class="text-gray-400">No active staff accounts</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="grid md:grid-cols-1 gap-4">
                                            <?php foreach ($activeStaff as $staff): ?>
                                                <div class="border border-gray-200 rounded-lg p-6 shadow-sm">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div>
                                                            <p class="text-sm text-gray-500 mb-1">
                                                                <span class="font-medium" style="color: #000000;">Position:</span> <?= htmlspecialchars($staff['position'] ?? 'N/A') ?>
                                                            </p>
                                                            <h3 class="font-semibold text-base text-gray-800">
                                                                <?= htmlspecialchars($staff['full_name']) ?>
                                                            </h3>
                                                        </div>
                                                        <span class="text-sm font-medium text-green-600 bg-green-200 rounded-full px-4 py-2 flex items-center gap-1">
                                                            Active
                                                        </span>
                                                    </div>

                                                    <div class="flex gap-3">
                                                        <button
                                                            onclick="openStaffPasswordModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')"
                                                            class="py-2 text-sm px-3 rounded-md hover:bg-gray-200 flex items-center gap-1" style="border: 1px solid #808080;">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12.05 19L14.9 16.175L12.05 13.35L11 14.4L12.075 15.475C11.6083 15.4917 11.1543 15.4167 10.713 15.25C10.2717 15.0833 9.87567 14.825 9.525 14.475C9.19167 14.1417 8.93734 13.7583 8.762 13.325C8.58667 12.8917 8.49933 12.4583 8.5 12.025C8.5 11.7417 8.53767 11.4583 8.613 11.175C8.68833 10.8917 8.79234 10.6167 8.925 10.35L7.825 9.25C7.54167 9.66667 7.33333 10.1083 7.2 10.575C7.06667 11.0417 7 11.5167 7 12C7 12.6333 7.125 13.2583 7.375 13.875C7.625 14.4917 7.99167 15.0417 8.475 15.525C8.95833 16.0083 9.5 16.371 10.1 16.613C10.7 16.855 11.3167 16.984 11.95 17L11 17.95L12.05 19ZM16.175 14.75C16.4583 14.3333 16.6667 13.8917 16.8 13.425C16.9333 12.9583 17 12.4833 17 12C17 11.3667 16.879 10.7373 16.637 10.112C16.395 9.48667 16.0327 8.93267 15.55 8.45C15.0673 7.96733 14.5213 7.609 13.912 7.375C13.3027 7.141 12.682 7.02433 12.05 7.025L13 6.05L11.95 5L9.1 7.825L11.95 10.65L13 9.6L11.9 8.5C12.35 8.5 12.8083 8.58767 13.275 8.763C13.7417 8.93833 14.1417 9.19233 14.475 9.525C14.8083 9.85767 15.0627 10.241 15.238 10.675C15.4133 11.109 15.5007 11.5423 15.5 11.975C15.5 12.2583 15.4627 12.5417 15.388 12.825C15.3133 13.1083 15.209 13.3833 15.075 13.65L16.175 14.75ZM12 22C10.6167 22 9.31667 21.7373 8.1 21.212C6.88334 20.6867 5.825 19.9743 4.925 19.075C4.025 18.1757 3.31267 17.1173 2.788 15.9C2.26333 14.6827 2.00067 13.3827 2 12C1.99933 10.6173 2.262 9.31733 2.788 8.1C3.314 6.88267 4.02633 5.82433 4.925 4.925C5.82367 4.02567 6.882 3.31333 8.1 2.788C9.318 2.26267 10.618 2 12 2C13.382 2 14.682 2.26267 15.9 2.788C17.118 3.31333 18.1763 4.02567 19.075 4.925C19.9737 5.82433 20.6863 6.88267 21.213 8.1C21.7397 9.31733 22.002 10.6173 22 12C21.998 13.3827 21.7353 14.6827 21.212 15.9C20.6887 17.1173 19.9763 18.1757 19.075 19.075C18.1737 19.9743 17.1153 20.687 15.9 21.213C14.6847 21.739 13.3847 22.0013 12 22Z" fill="black" />
                                                            </svg>
                                                            Change Password
                                                        </button>

                                                        <form
                                                            method="POST"
                                                            onsubmit="return confirm('Deactivate this staff account?')">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                            <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button
                                                                type="submit"
                                                                name="toggle_staff_status"
                                                                style="background-color: #DD7D06;"
                                                                class="py-3 text-sm px-3 text-white rounded-md flex items-center gap-1">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M16.25 6.25H13.75V4.375C13.75 3.38044 13.3549 2.42661 12.6517 1.72335C11.9484 1.02009 10.9946 0.625 10 0.625C9.00544 0.625 8.05161 1.02009 7.34835 1.72335C6.64509 2.42661 6.25 3.38044 6.25 4.375V6.25H3.75C3.41848 6.25 3.10054 6.3817 2.86612 6.61612C2.6317 6.85054 2.5 7.16848 2.5 7.5V16.25C2.5 16.5815 2.6317 16.8995 2.86612 17.1339C3.10054 17.3683 3.41848 17.5 3.75 17.5H16.25C16.5815 17.5 16.8995 17.3683 17.1339 17.1339C17.3683 16.8995 17.5 16.5815 17.5 16.25V7.5C17.5 7.16848 17.3683 6.85054 17.1339 6.61612C16.8995 6.3817 16.5815 6.25 16.25 6.25ZM7.5 4.375C7.5 3.71196 7.76339 3.07607 8.23223 2.60723C8.70107 2.13839 9.33696 1.875 10 1.875C10.663 1.875 11.2989 2.13839 11.7678 2.60723C12.2366 3.07607 12.5 3.71196 12.5 4.375V6.25H7.5V4.375ZM16.25 16.25H3.75V7.5H16.25V16.25ZM10.9375 11.875C10.9375 12.0604 10.8825 12.2417 10.7795 12.3958C10.6765 12.55 10.5301 12.6702 10.3588 12.7411C10.1875 12.8121 9.99896 12.8307 9.8171 12.7945C9.63525 12.7583 9.4682 12.669 9.33709 12.5379C9.20598 12.4068 9.11669 12.2398 9.08051 12.0579C9.04434 11.876 9.06291 11.6875 9.13386 11.5162C9.20482 11.3449 9.32498 11.1985 9.47915 11.0955C9.63332 10.9925 9.81458 10.9375 10 10.9375C10.2486 10.9375 10.4871 11.0363 10.6629 11.2121C10.8387 11.3879 10.9375 11.6264 10.9375 11.875Z" fill="white" />
                                                                </svg>
                                                                Deactivate
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- INACTIVE STAFF -->
                                <div id="inactiveStaffSection" style="display: none;">
                                    <?php if (empty($inactiveStaff)): ?>
                                        <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                            <p style="color: #9ca3af;">No inactive staff accounts</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="grid md:grid-cols-1 gap-4">
                                            <?php foreach ($inactiveStaff as $staff): ?>
                                                <div class="account-card inactive">
                                                    <div class="account-header">
                                                        <div>
                                                            <p class="account-position text-gray-500 mb-1">
                                                                <span class="font-medium" style="color: #000000;">Position:</span> <?= htmlspecialchars($staff['position'] ?? 'N/A') ?>
                                                            </p>
                                                            <h3 class="account-name"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                                        </div>
                                                        <span class="status-badge inactive">
                                                            Inactive
                                                        </span>
                                                    </div>

                                                    <div class="action-buttons">
                                                        <form method="POST" action="" onsubmit="return confirm('Activate this staff account?')">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                            <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" name="toggle_staff_status" class="btn-icon success" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M16.2806 9.21937C16.3504 9.28903 16.4057 9.37175 16.4434 9.46279C16.4812 9.55384 16.5006 9.65144 16.5006 9.75C16.5006 9.84856 16.4812 9.94616 16.4434 10.0372C16.4057 10.1283 16.3504 10.211 16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21937C15.289 9.14964 15.3718 9.09432 15.4628 9.05658C15.5538 9.01884 15.6514 8.99941 15.75 8.99941C15.8486 8.99941 15.9462 9.01884 16.0372 9.05658C16.1283 9.09432 16.211 9.14964 16.2806 9.21937ZM21.75 12C21.75 13.9284 21.1782 15.8134 20.1068 17.4168C19.0355 19.0202 17.5127 20.2699 15.7312 21.0078C13.9496 21.7458 11.9892 21.9389 10.0979 21.5627C8.20656 21.1865 6.46928 20.2579 5.10571 18.8943C3.74215 17.5307 2.81355 15.7934 2.43735 13.9021C2.06114 12.0108 2.25422 10.0504 2.99218 8.26884C3.73013 6.48726 4.97982 4.96451 6.58319 3.89317C8.18657 2.82183 10.0716 2.25 12 2.25C14.585 2.25273 17.0634 3.28084 18.8913 5.10872C20.7192 6.93661 21.7473 9.41498 21.75 12ZM20.25 12C20.25 10.3683 19.7661 8.77325 18.8596 7.41655C17.9531 6.05984 16.6646 5.00242 15.1571 4.37799C13.6497 3.75357 11.9909 3.59019 10.3905 3.90852C8.79017 4.22685 7.32016 5.01259 6.16637 6.16637C5.01259 7.32015 4.22685 8.79016 3.90853 10.3905C3.5902 11.9908 3.75358 13.6496 4.378 15.1571C5.00242 16.6646 6.05984 17.9531 7.41655 18.8596C8.77326 19.7661 10.3683 20.25 12 20.25C14.1873 20.2475 16.2843 19.3775 17.8309 17.8309C19.3775 16.2843 20.2475 14.1873 20.25 12Z" fill="white" />
                                                                </svg>
                                                                <span>Activate</span>
                                                            </button>
                                                        </form>
                                                        <button onclick="openDeleteModal(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['full_name']) ?>')" class="btn-icon danger" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M20.25 4.5H16.5V3.75C16.5 3.15326 16.2629 2.58097 15.841 2.15901C15.419 1.73705 14.8467 1.5 14.25 1.5H9.75C9.15326 1.5 8.58097 1.73705 8.15901 2.15901C7.73705 2.58097 7.5 3.15326 7.5 3.75V4.5H3.75C3.55109 4.5 3.36032 4.57902 3.21967 4.71967C3.07902 4.86032 3 5.05109 3 5.25C3 5.44891 3.07902 5.63968 3.21967 5.78033C3.36032 5.92098 3.55109 6 3.75 6H4.5V19.5C4.5 19.8978 4.65804 20.2794 4.93934 20.5607C5.22064 20.842 5.60218 21 6 21H18C18.3978 21 18.7794 20.842 19.0607 20.5607C19.342 20.2794 19.5 19.8978 19.5 19.5V6H20.25C20.4489 6 20.6397 5.92098 20.7803 5.78033C20.921 5.63968 21 5.44891 21 5.25C21 5.05109 20.921 4.86032 20.7803 4.71967C20.6397 4.57902 20.4489 4.5 20.25 4.5ZM9 3.75C9 3.55109 9.07902 3.36032 9.21967 3.21967C9.36032 3.07902 9.55109 3 9.75 3H14.25C14.4489 3 14.6397 3.07902 14.7803 3.21967C14.921 3.36032 15 3.55109 15 3.75V4.5H9V3.75ZM18 19.5H6V6H18V19.5ZM10.5 9.75V15.75C10.5 15.9489 10.421 16.1397 10.2803 16.2803C10.1397 16.421 9.94891 16.5 9.75 16.5C9.55109 16.5 9.36032 16.421 9.21967 16.2803C9.07902 16.1397 9 15.9489 9 15.75V9.75C9 9.55109 9.07902 9.36032 9.21967 9.21967C9.36032 9.07902 9.55109 9 9.75 9C9.94891 9 10.1397 9.07902 10.2803 9.21967C10.421 9.36032 10.5 9.55109 10.5 9.75ZM15 9.75V15.75C15 15.9489 14.921 16.1397 14.7803 16.2803C14.6397 16.421 14.4489 16.5 14.25 16.5C14.0511 16.5 13.8603 16.421 13.7197 16.2803C13.579 16.1397 13.5 15.9489 13.5 15.75V9.75C13.5 9.55109 13.579 9.36032 13.7197 9.21967C13.8603 9.07902 14.0511 9 14.25 9C14.4489 9 14.6397 9.07902 14.7803 9.21967C14.921 9.36032 15 9.55109 15 9.75Z" fill="white" />
                                                            </svg>
                                                            <span>Delete</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resident Section (with updated Gender and Sitio dropdowns) -->
                <div id="residentSection" class="tab-section" style="display: none;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Create New Resident Account</h2>
                    
                    <!-- Create Resident Form -->
                    <div class="flex flex-col md:flex-row w-full gap-8">
                        <!-- LEFT CONTENT - Create Resident Form -->
                        <div class="mb-8 p-8 rounded-lg border border-gray-300 w-full md:w-1/2">
                            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                <!-- Username -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="username"
                                        required
                                        placeholder="Enter Username"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="full_name"
                                        required
                                        placeholder="Enter Full Name"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        required
                                        placeholder="Enter Email Address"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Password -->
                                <div>
                                    <label style="display:block;font-size:1.125rem;font-weight:600;color:#374151;margin-bottom:4px;">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div style="position: relative;">
                                        <input type="text" name="password" required id="resident-password"
                                            value="<?= bin2hex(random_bytes(4)) ?>"
                                            class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500 font-mono">
                                        <button type="button"
                                            onclick="togglePasswordVisibility('resident-password')"
                                            style="position:absolute;right:20px;top:50%;transform:translateY(-50%);background:none;border:none;color:#3C96E1;cursor:pointer;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Phone
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        placeholder="Enter Phone Number"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Gender - Updated with custom-select class for consistent arrow -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Gender
                                    </label>
                                    <select
                                        name="gender"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500 custom-select">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Date of Birth
                                    </label>
                                    <input
                                        type="date"
                                        name="date_of_birth"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500">
                                </div>

                                <!-- Sitio - Updated with custom-select class for consistent arrow -->
                                <div>
                                    <label class="block text-lg font-semibold text-gray-700 mb-1">
                                        Sitio
                                    </label>
                                    <select
                                        name="sitio"
                                        class="w-full py-3 px-6 border border-blue-500 text-base rounded-md focus:ring-1 focus:ring-blue-400 focus:border-blue-500 custom-select">
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

                                <!-- Create Button -->
                                <div class="mt-4 col-span-2">
                                    <button
                                        type="submit"
                                        name="create_resident"
                                        class="w-auto px-6 py-4 bg-[#10b981] text-white rounded-md text-lg font-medium flex items-center gap-2"
                                        style="background-color: #10b981 !important; cursor: pointer;">
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM12 20.25C10.3683 20.25 8.77326 19.7661 7.41655 18.8596C6.05984 17.9531 5.00242 16.6646 4.378 15.1571C3.75358 13.6496 3.5902 11.9908 3.90853 10.3905C4.22685 8.79016 5.01259 7.32015 6.16637 6.16637C7.32016 5.01259 8.79017 4.22685 10.3905 3.90852C11.9909 3.59019 13.6497 3.75357 15.1571 4.37799C16.6646 5.00242 17.9531 6.05984 18.8596 7.41655C19.7662 8.77325 20.25 10.3683 20.25 12C20.2475 14.1873 19.3775 16.2843 17.8309 17.8309C16.2843 19.3775 14.1873 20.2475 12 20.25ZM16.5 12C16.5 12.1989 16.421 12.3897 16.2803 12.5303C16.1397 12.671 15.9489 12.75 15.75 12.75H12.75V15.75C12.75 15.9489 12.671 16.1397 12.5303 16.2803C12.3897 16.421 12.1989 16.5 12 16.5C11.8011 16.5 11.6103 16.421 11.4697 16.2803C11.329 16.1397 11.25 15.9489 11.25 15.75V12.75H8.25C8.05109 12.75 7.86033 12.671 7.71967 12.5303C7.57902 12.3897 7.5 12.1989 7.5 12C7.5 11.8011 7.57902 11.6103 7.71967 11.4697C7.86033 11.329 8.05109 11.25 8.25 11.25H11.25V8.25C11.25 8.05109 11.329 7.86032 11.4697 7.71967C11.6103 7.57902 11.8011 7.5 12 7.5C12.1989 7.5 12.3897 7.57902 12.5303 7.71967C12.671 7.86032 12.75 8.05109 12.75 8.25V11.25H15.75C15.9489 11.25 16.1397 11.329 16.2803 11.4697C16.421 11.6103 16.5 11.8011 16.5 12Z" fill="white"/>
</svg>
Create Resident Account
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- RIGHT CONTENT - Resident Lists -->
                        <div class="w-full md:w-1/2">
                            <div class="p-8 rounded-lg border border-gray-300">
                                <!-- Resident Header -->
                                <div class="mb-6">
                                    <h3 class="text-xl font-semibold text-gray-800">Accounts Created</h3>
                                    <p class="text-sm text-gray-500 mt-1">List of all resident accounts</p>
                                </div>

                                <!-- All Residents -->
                                <div id="allResidentsSection">
                                    <?php if (empty($approvedResidents)): ?>
                                        <div class="text-center p-8 bg-white rounded border border-gray-200">
                                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            <p class="text-gray-500">No resident accounts created yet</p>
                                            <p class="text-sm text-gray-400 mt-1">Create a new resident account using the form</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                                            <?php foreach ($approvedResidents as $resident):
                                                $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ?");
                                                $stmt->execute([$resident['id']]);
                                                $hasPatientRecord = $stmt->fetch();
                                            ?>
                                                <div class="border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div>
                                                            <p class="text-sm text-gray-500 mb-1">
                                                                <span class="font-medium" style="color: #000000;">Email:</span> <?= htmlspecialchars($resident['email']) ?>
                                                            </p>
                                                            <h3 class="font-semibold text-base text-gray-800">
                                                                <?= htmlspecialchars($resident['full_name']) ?>
                                                            </h3>
                                                        </div>
                                                        <span class="text-sm font-medium <?= $hasPatientRecord ? 'text-green-600 bg-green-200' : 'text-yellow-600 bg-yellow-200' ?> rounded-full px-4 py-2 flex items-center gap-1">
                                                            <?= $hasPatientRecord ? 'Linked' : 'Unlinked' ?>
                                                        </span>
                                                    </div>

                                                    <div class="flex gap-3">
                                                        <button
                                                            onclick="openResetModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>', 'resident')"
                                                            class="py-2 text-sm px-3 rounded-md hover:bg-gray-200 flex items-center gap-1" style="border: 1px solid #808080;">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12.05 19L14.9 16.175L12.05 13.35L11 14.4L12.075 15.475C11.6083 15.4917 11.1543 15.4167 10.713 15.25C10.2717 15.0833 9.87567 14.825 9.525 14.475C9.19167 14.1417 8.93734 13.7583 8.762 13.325C8.58667 12.8917 8.49933 12.4583 8.5 12.025C8.5 11.7417 8.53767 11.4583 8.613 11.175C8.68833 10.8917 8.79234 10.6167 8.925 10.35L7.825 9.25C7.54167 9.66667 7.33333 10.1083 7.2 10.575C7.06667 11.0417 7 11.5167 7 12C7 12.6333 7.125 13.2583 7.375 13.875C7.625 14.4917 7.99167 15.0417 8.475 15.525C8.95833 16.0083 9.5 16.371 10.1 16.613C10.7 16.855 11.3167 16.984 11.95 17L11 17.95L12.05 19ZM16.175 14.75C16.4583 14.3333 16.6667 13.8917 16.8 13.425C16.9333 12.9583 17 12.4833 17 12C17 11.3667 16.879 10.7373 16.637 10.112C16.395 9.48667 16.0327 8.93267 15.55 8.45C15.0673 7.96733 14.5213 7.609 13.912 7.375C13.3027 7.141 12.682 7.02433 12.05 7.025L13 6.05L11.95 5L9.1 7.825L11.95 10.65L13 9.6L11.9 8.5C12.35 8.5 12.8083 8.58767 13.275 8.763C13.7417 8.93833 14.1417 9.19233 14.475 9.525C14.8083 9.85767 15.0627 10.241 15.238 10.675C15.4133 11.109 15.5007 11.5423 15.5 11.975C15.5 12.2583 15.4627 12.5417 15.388 12.825C15.3133 13.1083 15.209 13.3833 15.075 13.65L16.175 14.75ZM12 22C10.6167 22 9.31667 21.7373 8.1 21.212C6.88334 20.6867 5.825 19.9743 4.925 19.075C4.025 18.1757 3.31267 17.1173 2.788 15.9C2.26333 14.6827 2.00067 13.3827 2 12C1.99933 10.6173 2.262 9.31733 2.788 8.1C3.314 6.88267 4.02633 5.82433 4.925 4.925C5.82367 4.02567 6.882 3.31333 8.1 2.788C9.318 2.26267 10.618 2 12 2C13.382 2 14.682 2.26267 15.9 2.788C17.118 3.31333 18.1763 4.02567 19.075 4.925C19.9737 5.82433 20.6863 6.88267 21.213 8.1C21.7397 9.31733 22.002 10.6173 22 12C21.998 13.3827 21.7353 14.6827 21.212 15.9C20.6887 17.1173 19.9763 18.1757 19.075 19.075C18.1737 19.9743 17.1153 20.687 15.9 21.213C14.6847 21.739 13.3847 22.0013 12 22Z" fill="black" />
                                                            </svg>
                                                            Change Password
                                                        </button>
                                                        
                                                        <?php if (!$hasPatientRecord): ?>
                                                            <button
                                                                onclick="switchToLinking(<?= $resident['id'] ?>)"
                                                                class="py-2 text-sm px-3 rounded-md hover:bg-blue-50 flex items-center gap-1 border border-blue-300 text-blue-600">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M13.5 10.5L21 3M21 3H15.75M21 3V8.25M10.5 13.5L3 21M3 21H8.25M3 21L3 15.75M8.25 3H3M3 3V8.25M21 21H15.75M21 21L21 15.75" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                                Link to Patient
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- Delete Button -->
                                                        <button
                                                            onclick="openResidentDeleteModal(<?= $resident['id'] ?>, '<?= htmlspecialchars($resident['full_name']) ?>')"
                                                            class="py-2 text-sm px-3 rounded-md hover:bg-red-50 flex items-center gap-1 border border-red-300 text-red-600">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M20.25 4.5H16.5V3.75C16.5 3.15326 16.2629 2.58097 15.841 2.15901C15.419 1.73705 14.8467 1.5 14.25 1.5H9.75C9.15326 1.5 8.58097 1.73705 8.15901 2.15901C7.73705 2.58097 7.5 3.15326 7.5 3.75V4.5H3.75C3.55109 4.5 3.36032 4.57902 3.21967 4.71967C3.07902 4.86032 3 5.05109 3 5.25C3 5.44891 3.07902 5.63968 3.21967 5.78033C3.36032 5.92098 3.55109 6 3.75 6H4.5V19.5C4.5 19.8978 4.65804 20.2794 4.93934 20.5607C5.22064 20.842 5.60218 21 6 21H18C18.3978 21 18.7794 20.842 19.0607 20.5607C19.342 20.2794 19.5 19.8978 19.5 19.5V6H20.25C20.4489 6 20.6397 5.92098 20.7803 5.78033C20.921 5.63968 21 5.44891 21 5.25C21 5.05109 20.921 4.86032 20.7803 4.71967C20.6397 4.57902 20.4489 4.5 20.25 4.5ZM9 3.75C9 3.55109 9.07902 3.36032 9.21967 3.21967C9.36032 3.07902 9.55109 3 9.75 3H14.25C14.4489 3 14.6397 3.07902 14.7803 3.21967C14.921 3.36032 15 3.55109 15 3.75V4.5H9V3.75ZM18 19.5H6V6H18V19.5ZM10.5 9.75V15.75C10.5 15.9489 10.421 16.1397 10.2803 16.2803C10.1397 16.421 9.94891 16.5 9.75 16.5C9.55109 16.5 9.36032 16.421 9.21967 16.2803C9.07902 16.1397 9 15.9489 9 15.75V9.75C9 9.55109 9.07902 9.36032 9.21967 9.21967C9.36032 9.07902 9.55109 9 9.75 9C9.94891 9 10.1397 9.07902 10.2803 9.21967C10.421 9.36032 10.5 9.55109 10.5 9.75ZM15 9.75V15.75C15 15.9489 14.921 16.1397 14.7803 16.2803C14.6397 16.421 14.4489 16.5 14.25 16.5C14.0511 16.5 13.8603 16.421 13.7197 16.2803C13.579 16.1397 13.5 15.9489 13.5 15.75V9.75C13.5 9.55109 13.579 9.36032 13.7197 9.21967C13.8603 9.07902 14.0511 9 14.25 9C14.4489 9 14.6397 9.07902 14.7803 9.21967C14.921 9.36032 15 9.55109 15 9.75Z" fill="#dc2626" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add this new modal for resident deletion before the closing </body> tag -->
                <!-- Resident Delete Modal -->
                <div class="modern-modal" id="residentDeleteModal">
                    <div class="modern-modal-content">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Delete Resident Account</h3>
                        <p style="margin-bottom: 1.5rem; color: #6b7280;" id="residentDeleteMessage"></p>

                        <form method="POST" action="" id="residentDeleteForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="resident_id" id="residentDeleteId">

                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Handle Patient Records:</label>

                                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <label style="display: flex; align-items: start; gap: 0.5rem;">
                                        <input type="radio" name="delete_action" value="reassign" checked>
                                        <span>
                                            <span style="font-weight: 500;">Reassign patient records to another resident</span>
                                            <select name="reassign_to" class="modern-input" style="margin-top: 0.25rem;">
                                                <option value="">Select resident</option>
                                                <?php foreach ($approvedResidents as $otherResident): ?>
                                                    <?php if ($otherResident['id'] != $resident['id']): ?>
                                                        <option value="<?= $otherResident['id'] ?>">
                                                            <?= htmlspecialchars($otherResident['full_name']) ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                    </label>

                                    <label style="display: flex; align-items: start; gap: 0.5rem;">
                                        <input type="radio" name="delete_action" value="delete">
                                        <span style="color: #dc2626; font-weight: 500;">Delete all associated patient records</span>
                                    </label>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.75rem;">
                                <button type="button" onclick="closeResidentDeleteModal()" style="flex: 1; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; background: white; cursor: pointer;">
                                    Cancel
                                </button>
                                <button type="submit" name="hard_delete_resident" style="flex: 1; padding: 0.5rem; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Delete Resident
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Pending Residents -->
                <div id="pendingResidentSection" style="display: none;">
                    <?php if (empty($pendingResidents)): ?>
                        <div class="text-center p-8 bg-white rounded border border-gray-200">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500">No pending residents</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                            <?php foreach ($pendingResidents as $resident): ?>
                                <div class="border border-gray-200 rounded-lg p-4 bg-yellow-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($resident['email']) ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Username: <?= htmlspecialchars($resident['username']) ?></p>
                                        </div>
                                        <span class="text-xs bg-yellow-200 text-yellow-800 px-3 py-1 rounded-full">Pending</span>
                                    </div>

                                    <div class="flex gap-2 mt-3">
                                        <form method="POST" action="" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" name="toggle_resident_status" class="w-full py-2 text-sm bg-green-500 text-white rounded-md hover:bg-green-600 flex items-center justify-center gap-1">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" name="toggle_resident_status" class="w-full py-2 text-sm bg-red-500 text-white rounded-md hover:bg-red-600 flex items-center justify-center gap-1">
                                                <i class="fas fa-times"></i> Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Declined Residents -->
                <div id="declinedResidentSection" style="display: none;">
                    <?php if (empty($declinedResidents)): ?>
                        <div class="text-center p-8 bg-white rounded border border-gray-200">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500">No declined residents</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                            <?php foreach ($declinedResidents as $resident): ?>
                                <div class="border border-gray-200 rounded-lg p-4 bg-red-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($resident['full_name']) ?></h3>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($resident['email']) ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Username: <?= htmlspecialchars($resident['username']) ?></p>
                                        </div>
                                        <span class="text-xs bg-red-200 text-red-800 px-3 py-1 rounded-full">Declined</span>
                                    </div>

                                    <div class="mt-3">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" name="toggle_resident_status" class="w-full py-2 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600 flex items-center justify-center gap-1">
                                                <i class="fas fa-redo"></i> Reconsider & Approve
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Linking Section - Redesigned with Icon Inside Search Field -->
        <div id="linkingSection" class="tab-section" style="display: none;">
            <div class="flex flex-col md:flex-row w-full gap-8 px-8">
                <!-- LEFT CONTENT - Unlinked Residents -->
                <div class="w-full md:w-1/2">
                    <div class="p-8 rounded-lg border border-gray-300">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827;">Unlinked Residents</h3>
                                <p class="text-sm text-gray-500 mt-1">Select a resident account to link</p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full border border-blue-400">
                                <?= count($unlinkedResidents) ?> Available
                            </span>
                        </div>
                        
                        <!-- Enhanced Search Bar - Icon Inside Input -->
                        <div class="mb-4">
                            <form onsubmit="event.preventDefault(); filterResidents();" class="flex gap-2">
                                <div class="search-wrapper flex-1">
                                    <i class="fas fa-search search-icon"></i>
                                    <input 
                                        type="text" 
                                        id="residentSearchInput" 
                                        placeholder="Search by name, email, or sitio..." 
                                        class="search-input"
                                        value=""
                                        oninput="filterResidents()"
                                    >
                                    <button 
                                        type="button" 
                                        class="clear-search-btn" 
                                        onclick="clearResidentSearch()"
                                        title="Clear search"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <!-- <button 
                                    type="submit" 
                                    class="search-button"
                                >
                                    <i class="fas fa-search"></i>
                                    Search
                                </button> -->
                            </form>
                            
                            <!-- Search Status Indicator -->
                            <div id="residentSearchStatus" class="mt-2 text-sm text-gray-500 flex items-center gap-2 hidden">
                                <span class="inline-block w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                                <span id="residentSearchCount"></span>
                                <button onclick="clearResidentSearch()" class="text-blue-600 hover:text-blue-800 text-xs font-medium ml-2">
                                    Clear search
                                </button>
                            </div>
                        </div>
                        
                        <!-- Residents List -->
                        <?php if (empty($unlinkedResidents)): ?>
                            <div class="text-center p-8 bg-white rounded">
                                <svg width="80" height="80" class="mx-auto mb-3" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M51.5637 18.9546C51.4775 21.9466 50.2472 24.7917 48.1262 26.9038L40.6604 34.3739C39.5731 35.4672 38.2796 36.334 36.855 36.9241C35.4304 37.5141 33.9029 37.8157 32.361 37.8114H32.3502C30.782 37.8103 29.2298 37.4949 27.7855 36.8839C26.3411 36.2728 25.0339 35.3785 23.941 34.2538C22.8481 33.129 21.9917 31.7966 21.4223 30.3353C20.853 28.8741 20.5823 27.3135 20.6262 25.7458C20.639 25.29 20.8324 24.8579 21.1638 24.5446C21.4952 24.2314 21.9375 24.0626 22.3933 24.0754C22.8492 24.0882 23.2812 24.2816 23.5945 24.613C23.9078 24.9444 24.0765 25.3867 24.0637 25.8425C24.0324 26.9509 24.2236 28.0544 24.626 29.0877C25.0284 30.121 25.6339 31.0631 26.4066 31.8584C27.1793 32.6538 28.1035 33.2861 29.1248 33.7182C30.146 34.1503 31.2435 34.3732 32.3524 34.3739C33.4424 34.3767 34.5222 34.1634 35.5293 33.7463C36.5364 33.3293 37.4509 32.7167 38.2198 31.9441L45.6856 24.4782C47.2259 22.9185 48.0866 20.8127 48.0798 18.6205C48.0729 16.4284 47.1991 14.328 45.649 12.7779C44.0989 11.2279 41.9985 10.354 39.8064 10.3471C37.6143 10.3403 35.5084 11.201 33.9487 12.7413L31.5854 15.1046C31.2605 15.4133 30.8278 15.5829 30.3796 15.5772C29.9314 15.5714 29.5032 15.3908 29.1862 15.0739C28.8693 14.7569 28.6887 14.3287 28.683 13.8805C28.6772 13.4324 28.8468 12.9997 29.1555 12.6747L31.5188 10.3114C32.6091 9.22074 33.9036 8.35553 35.3284 7.76522C36.7532 7.17492 38.2803 6.87109 39.8225 6.87109C41.3647 6.87109 42.8918 7.17492 44.3166 7.76522C45.7414 8.35553 47.0359 9.22074 48.1262 10.3114C49.2556 11.4436 50.1427 12.7937 50.7337 14.2796C51.3246 15.7655 51.607 17.3561 51.5637 18.9546ZM23.4192 39.8868L21.0559 42.2501C20.2851 43.0261 19.3677 43.641 18.3571 44.0593C17.3466 44.4775 16.2629 44.6907 15.1692 44.6864C13.5284 44.6851 11.9249 44.1975 10.5611 43.2852C9.19741 42.3729 8.13469 41.0768 7.50725 39.5608C6.87982 38.0447 6.71583 36.3767 7.036 34.7675C7.35618 33.1583 8.14615 31.6801 9.30611 30.5196L16.759 23.0538C17.9329 21.8737 19.4345 21.0739 21.0688 20.7582C22.7031 20.4425 24.3945 20.6254 25.9235 21.2834C27.4524 21.9413 28.7482 23.0437 29.6426 24.4475C30.537 25.8513 30.9886 27.4916 30.9387 29.1554C30.9259 29.6112 31.0947 30.0535 31.408 30.3849C31.7212 30.7163 32.1533 30.9097 32.6091 30.9225C33.065 30.9353 33.5072 30.7665 33.8386 30.4532C34.17 30.14 34.3634 29.7079 34.3762 29.2521C34.4175 27.6561 34.1341 26.0683 33.5432 24.5851C32.9523 23.102 32.0662 21.7543 30.9387 20.6239C28.737 18.4231 25.7513 17.1868 22.6382 17.1868C19.5252 17.1868 16.5395 18.4231 14.3377 20.6239L6.87623 28.0898C5.23532 29.73 4.11745 31.8199 3.6639 34.0952C3.21036 36.3706 3.44148 38.7293 4.32807 40.8734C5.21466 43.0174 6.71693 44.8506 8.64501 46.1411C10.5731 47.4316 12.8405 48.1216 15.1606 48.1239C16.7029 48.1284 18.2308 47.8269 19.6557 47.2368C21.0807 46.6468 22.3745 45.7799 23.4622 44.6864L25.8254 42.3232C26.1036 41.9955 26.2489 41.5756 26.2327 41.1461C26.2166 40.7166 26.0401 40.3088 25.7381 40.003C25.4361 39.6972 25.0305 39.5157 24.6012 39.4942C24.172 39.4727 23.7503 39.6128 23.4192 39.8868Z" fill="#C8C8C8"/>
</svg>

                                <p class="text-gray-500">No unlinked residents available</p>
                                <p class="text-sm text-gray-400 mt-1">All residents are linked to patient records</p>
                            </div>
                        <?php else: ?>
                            <div id="residentsList" class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
                                <?php 
                                $displayCount = 0;
                                foreach ($unlinkedResidents as $resident): 
                                    if ($displayCount >= 5) break;
                                    $displayCount++;
                                ?>
                                    <div 
                                        onclick="selectResident(<?= $resident['id'] ?>, this)" 
                                        data-resident-id="<?= $resident['id'] ?>"
                                        data-resident-name="<?= strtolower(htmlspecialchars($resident['full_name'])) ?>"
                                        data-resident-email="<?= strtolower(htmlspecialchars($resident['email'] ?? '')) ?>"
                                        data-resident-sitio="<?= strtolower(htmlspecialchars($resident['sitio'] ?? '')) ?>"
                                        class="resident-item p-6 border rounded-lg cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50"
                                        style="border-color: #e5e7eb;">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold text-gray-800 resident-name mb-2"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                <div class="text-sm text-gray-500 resident-email mb-1"><?= htmlspecialchars($resident['email']) ?></div>
                                                <?php if (!empty($resident['sitio'])): ?>
                                                    <div class="text-xs text-gray-400 mt-1 resident-sitio">Sitio: <?= htmlspecialchars($resident['sitio']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs bg-blue-100 text-blue-800 px-4 py-2 border border-blue-400 rounded-full">Resident</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Additional items (hidden initially) -->
                                <div id="additionalResidents" style="display: none;">
                                    <?php 
                                    $additionalCount = 0;
                                    foreach ($unlinkedResidents as $resident): 
                                        if ($additionalCount < 5) {
                                            $additionalCount++;
                                            continue;
                                        }
                                    ?>
                                        <div 
                                            onclick="selectResident(<?= $resident['id'] ?>, this)" 
                                            data-resident-id="<?= $resident['id'] ?>"
                                            data-resident-name="<?= strtolower(htmlspecialchars($resident['full_name'])) ?>"
                                            data-resident-email="<?= strtolower(htmlspecialchars($resident['email'] ?? '')) ?>"
                                            data-resident-sitio="<?= strtolower(htmlspecialchars($resident['sitio'] ?? '')) ?>"
                                            class="resident-item p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50"
                                            style="border-color: #e5e7eb;">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-semibold text-gray-800 resident-name"><?= htmlspecialchars($resident['full_name']) ?></div>
                                                    <div class="text-sm text-gray-500 resident-email"><?= htmlspecialchars($resident['email']) ?></div>
                                                    <?php if (!empty($resident['sitio'])): ?>
                                                        <div class="text-xs text-gray-400 mt-1 resident-sitio">Sitio: <?= htmlspecialchars($resident['sitio']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Resident</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Show More/Less Buttons -->
                                <?php if (count($unlinkedResidents) > 5): ?>
                                    <div class="text-center mt-4">
                                        <button 
                                            id="showMoreResidentsBtn"
                                            onclick="toggleResidentsList()"
                                            class="px-4 py-2 text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1 transition-colors">
                                            <span>Show <?= count($unlinkedResidents) - 5 ?> more residents</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            id="showLessResidentsBtn"
                                            onclick="toggleResidentsList()"
                                            class="px-4 py-2 text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1 transition-colors hidden">
                                            <span>Show less</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- No Results Message (hidden by default) -->
                            <div id="noResidentsFound" class="text-center p-8 bg-white rounded border border-gray-200 hidden">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"></path>
                                </svg>
                                <p class="text-gray-500">No residents match your search</p>
                                <button 
                                    onclick="clearResidentSearch()" 
                                    class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Clear search
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT CONTENT - Unlinked Patients -->
                <div class="w-full md:w-1/2">
                    <div class="p-8 rounded-lg border border-gray-300">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827;">Unlinked Patient Records</h3>
                                <p class="text-sm text-gray-500 mt-1">Select a patient record to link</p>
                            </div>
                            <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full border border-green-400">
                                <?= count($unlinkedPatients) ?> Available
                            </span>
                        </div>
                        
                        <!-- Enhanced Search Bar - Icon Inside Input -->
                        <div class="mb-4">
                            <form onsubmit="event.preventDefault(); filterPatients();" class="flex gap-2">
                                <div class="search-wrapper flex-1">
                                    <i class="fas fa-search search-icon"></i>
                                    <input 
                                        type="text" 
                                        id="patientSearchInput" 
                                        placeholder="Search by name, age, or sitio..." 
                                        class="search-input"
                                        value=""
                                        oninput="filterPatients()"
                                    >
                                    <button 
                                        type="button" 
                                        class="clear-search-btn" 
                                        onclick="clearPatientSearch()"
                                        title="Clear search"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <!-- <button 
                                    type="submit" 
                                    class="search-button"
                                    style="background: #10b981;"
                                >
                                    <i class="fas fa-search"></i>
                                    Search
                                </button> -->
                            </form>
                            
                            <!-- Search Status Indicator -->
                            <div id="patientSearchStatus" class="mt-2 text-sm text-gray-500 flex items-center gap-2 hidden">
                                <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span id="patientSearchCount"></span>
                                <button onclick="clearPatientSearch()" class="text-green-600 hover:text-green-800 text-xs font-medium ml-2">
                                    Clear search
                                </button>
                            </div>
                        </div>
                        
                        <!-- Patients List -->
                        <?php if (empty($unlinkedPatients)): ?>
                            <div class="text-center p-8 bg-white rounded border border-gray-200">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p class="text-gray-500">No unlinked patient records available</p>
                                <p class="text-sm text-gray-400 mt-1">All patients are linked to resident accounts</p>
                            </div>
                        <?php else: ?>
                            <div id="patientsList" class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
                                <?php 
                                $displayCount = 0;
                                foreach ($unlinkedPatients as $patient): 
                                    if ($displayCount >= 5) break;
                                    $displayCount++;
                                ?>
                                    <div 
                                        onclick="selectPatient(<?= $patient['id'] ?>, this)" 
                                        data-patient-id="<?= $patient['id'] ?>"
                                        data-patient-name="<?= strtolower(htmlspecialchars($patient['full_name'])) ?>"
                                        data-patient-age="<?= strtolower(htmlspecialchars($patient['age'] ?? '')) ?>"
                                        data-patient-sitio="<?= strtolower(htmlspecialchars($patient['sitio'] ?? '')) ?>"
                                        class="patient-item p-6 border rounded-lg cursor-pointer transition-all hover:border-green-500 hover:bg-green-50"
                                        style="border-color: #e5e7eb;">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold text-gray-800 patient-name mb-3"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                <div class="text-sm text-gray-500 mt-2">
                                                    <span class="patient-age">Age: <?= htmlspecialchars($patient['age'] ?? 'N/A') ?></span> | 
                                                    <span class="patient-sitio">Sitio: <?= htmlspecialchars($patient['sitio'] ?? 'N/A') ?></span>
                                                </div>
                                                <?php if (!empty($patient['contact'])): ?>
                                                    <div class="text-xs text-gray-400 mt-1 patient-contact">Contact: <?= htmlspecialchars($patient['contact']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs bg-green-100 text-green-800 px-4 py-2 border border-green-400 rounded-full">Patient</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Additional items (hidden initially) -->
                                <div id="additionalPatients" style="display: none;">
                                    <?php 
                                    $additionalCount = 0;
                                    foreach ($unlinkedPatients as $patient): 
                                        if ($additionalCount < 5) {
                                            $additionalCount++;
                                            continue;
                                        }
                                    ?>
                                        <div 
                                            onclick="selectPatient(<?= $patient['id'] ?>, this)" 
                                            data-patient-id="<?= $patient['id'] ?>"
                                            data-patient-name="<?= strtolower(htmlspecialchars($patient['full_name'])) ?>"
                                            data-patient-age="<?= strtolower(htmlspecialchars($patient['age'] ?? '')) ?>"
                                            data-patient-sitio="<?= strtolower(htmlspecialchars($patient['sitio'] ?? '')) ?>"
                                            class="patient-item p-4 border rounded-lg cursor-pointer transition-all hover:border-green-500 hover:bg-green-50"
                                            style="border-color: #e5e7eb;">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-semibold text-gray-800 patient-name"><?= htmlspecialchars($patient['full_name']) ?></div>
                                                    <div class="text-sm text-gray-500">
                                                        <span class="patient-age">Age: <?= htmlspecialchars($patient['age'] ?? 'N/A') ?></span> | 
                                                        <span class="patient-sitio">Sitio: <?= htmlspecialchars($patient['sitio'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <?php if (!empty($patient['contact'])): ?>
                                                        <div class="text-xs text-gray-400 mt-1 patient-contact">Contact: <?= htmlspecialchars($patient['contact']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs bg-green-100 text-green-800 px-4 py-2 border border-green-400 rounded-full">Patient</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Show More/Less Buttons -->
                                <?php if (count($unlinkedPatients) > 5): ?>
                                    <div class="text-center mt-4">
                                        <button 
                                            id="showMorePatientsBtn"
                                            onclick="togglePatientsList()"
                                            class="px-4 py-2 text-sm text-green-600 hover:text-green-800 font-medium inline-flex items-center gap-1 transition-colors">
                                            <span>Show <?= count($unlinkedPatients) - 5 ?> more patients</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            id="showLessPatientsBtn"
                                            onclick="togglePatientsList()"
                                            class="px-4 py-2 text-sm text-green-600 hover:text-green-800 font-medium inline-flex items-center gap-1 transition-colors hidden">
                                            <span>Show less</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- No Results Message (hidden by default) -->
                            <div id="noPatientsFound" class="text-center p-8 bg-white rounded border border-gray-200 hidden">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"></path>
                                </svg>
                                <p class="text-gray-500">No patients match your search</p>
                                <button 
                                    onclick="clearPatientSearch()" 
                                    class="mt-2 text-sm text-green-600 hover:text-green-800 font-medium">
                                    Clear search
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Link Button -->
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <button 
                    onclick="performLinking()" 
                    id="linkButton" 
                    disabled
                    class="px-8 py-3 bg-purple-600 text-white rounded-lg font-medium inline-flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-purple-700 hover:shadow-lg">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.5 10.5L21 3M21 3H15.75M21 3V8.25M10.5 13.5L3 21M3 21H8.25M3 21L3 15.75M8.25 3H3M3 3V8.25M21 21H15.75M21 21L21 15.75" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Link Selected Accounts
                </button>
                <p class="text-sm text-gray-500 mt-2">Select one resident and one patient record to enable linking</p>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modern-modal" id="deleteModal">
        <div class="modern-modal-content">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Delete Staff Account</h3>
            <p style="margin-bottom: 1.5rem; color: #6b7280;" id="deleteMessage"></p>

            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
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
            const button = input.parentElement.querySelector('button');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function togglePasswordField(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Tab switching
        function switchTab(tab) {
            // Update main nav tabs
            document.querySelectorAll('.main-nav-tab').forEach(t => {
                t.classList.remove('active');
                t.style.color = '#3C96E1';
            });
            document.getElementById(tab + 'MainTab').classList.add('active');
            document.getElementById(tab + 'MainTab').style.color = '#FFFFFF';

            // Show/hide sections
            document.getElementById('staffSection').style.display = tab === 'staff' ? 'block' : 'none';
            document.getElementById('residentSection').style.display = tab === 'resident' ? 'block' : 'none';
            if (document.getElementById('linkingSection')) {
                document.getElementById('linkingSection').style.display = tab === 'linking' ? 'block' : 'none';
            }

            // Reset linking selections when switching tabs
            if (tab === 'linking') {
                selectedResidentId = 0;
                selectedPatientId = 0;
                updateLinkButton();
                
                // Remove selected styling
                document.querySelectorAll('[data-resident-id]').forEach(el => {
                    el.style.background = 'white';
                    el.style.borderColor = '#e5e7eb';
                });
                document.querySelectorAll('[data-patient-id]').forEach(el => {
                    el.style.background = 'white';
                    el.style.borderColor = '#e5e7eb';
                });
            }
        }

        // Staff tab switching
        function showStaffTab(tab) {
            const activeTab = document.getElementById('activeStaffMainTab');
            const inactiveTab = document.getElementById('inactiveStaffMainTab');
            const activeSection = document.getElementById('activeStaffSection');
            const inactiveSection = document.getElementById('inactiveStaffSection');

            if (tab === 'active') {
                activeTab.style.color = '#FFFFFF';
                activeTab.style.backgroundColor = '#3C96E1';
                inactiveTab.style.color = '#3C96E1';
                inactiveTab.style.backgroundColor = '#3C96E14D';
                
                if (activeSection) activeSection.style.display = 'block';
                if (inactiveSection) inactiveSection.style.display = 'none';
            } else {
                inactiveTab.style.color = '#FFFFFF';
                inactiveTab.style.backgroundColor = '#3C96E1';
                activeTab.style.color = '#3C96E1';
                activeTab.style.backgroundColor = '#3C96E14D';
                
                if (inactiveSection) inactiveSection.style.display = 'block';
                if (activeSection) activeSection.style.display = 'none';
            }
        }

        // Resident tab switching
        function showResidentTab(tab) {
            const approvedTab = document.getElementById('approvedResidentTab');
            const pendingTab = document.getElementById('pendingResidentTab');
            const declinedTab = document.getElementById('declinedResidentTab');
            
            const approvedSection = document.getElementById('approvedResidentSection');
            const pendingSection = document.getElementById('pendingResidentSection');
            const declinedSection = document.getElementById('declinedResidentSection');

            // Reset all tabs
            [approvedTab, pendingTab, declinedTab].forEach(tab => {
                tab.style.color = '#6b7280';
                tab.style.backgroundColor = '#f3f4f6';
            });

            // Set active tab
            if (tab === 'approved') {
                approvedTab.style.color = '#FFFFFF';
                approvedTab.style.backgroundColor = '#10b981';
                approvedSection.style.display = 'block';
                pendingSection.style.display = 'none';
                declinedSection.style.display = 'none';
            } else if (tab === 'pending') {
                pendingTab.style.color = '#FFFFFF';
                pendingTab.style.backgroundColor = '#10b981';
                approvedSection.style.display = 'none';
                pendingSection.style.display = 'block';
                declinedSection.style.display = 'none';
            } else if (tab === 'declined') {
                declinedTab.style.color = '#FFFFFF';
                declinedTab.style.backgroundColor = '#10b981';
                approvedSection.style.display = 'none';
                pendingSection.style.display = 'none';
                declinedSection.style.display = 'block';
            }
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

        // Add these JavaScript functions before the closing </body> tag
        // Resident Delete Modal functions
        function openResidentDeleteModal(id, name) {
            document.getElementById('residentDeleteId').value = id;
            document.getElementById('residentDeleteMessage').innerHTML = `Delete resident account <strong>${name}</strong>? This will affect their patient records.`;
            document.getElementById('residentDeleteModal').classList.add('show');
        }

        function closeResidentDeleteModal() {
            document.getElementById('residentDeleteModal').classList.remove('show');
        }

        // Close modal on outside click (add this to existing window.onclick function)
        // Update the existing window.onclick function to include the new modal
        const originalOnClick = window.onclick;
        window.onclick = function(event) {
            if (originalOnClick) originalOnClick(event);
            if (event.target.classList.contains('modern-modal')) {
                event.target.classList.remove('show');
            }
        };

        // Linking functions
        let selectedResidentId = 0;
        let selectedPatientId = 0;

        // Resident search and filter function
        function filterResidents() {
            const searchInput = document.getElementById('residentSearchInput');
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const residentItems = document.querySelectorAll('.resident-item');
            const noResults = document.getElementById('noResidentsFound');
            const searchStatus = document.getElementById('residentSearchStatus');
            const searchCountSpan = document.getElementById('residentSearchCount');
            let visibleCount = 0;
            
            // Update search status
            if (searchStatus) {
                if (searchTerm.length > 0) {
                    searchStatus.classList.remove('hidden');
                } else {
                    searchStatus.classList.add('hidden');
                }
            }
            
            // Filter items
            residentItems.forEach(item => {
                const name = item.dataset.residentName || '';
                const email = item.dataset.residentEmail || '';
                const sitio = item.dataset.residentSitio || '';
                
                if (searchTerm === '' || 
                    name.includes(searchTerm) || 
                    email.includes(searchTerm) || 
                    sitio.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    // If this item was selected, clear selection
                    if (selectedResidentId === parseInt(item.dataset.residentId)) {
                        selectedResidentId = 0;
                        item.style.background = 'white';
                        item.style.borderColor = '#e5e7eb';
                        item.style.borderWidth = '1px';
                    }
                }
            });
            
            // Update search count
            if (searchCountSpan) {
                if (searchTerm.length > 0) {
                    searchCountSpan.textContent = `Found ${visibleCount} resident${visibleCount !== 1 ? 's' : ''}`;
                }
            }
            
            // Show/hide no results message
            if (noResults) {
                if (visibleCount === 0 && residentItems.length > 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }
            
            updateLinkButton();
        }

        // Patient search and filter function
        function filterPatients() {
            const searchInput = document.getElementById('patientSearchInput');
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const patientItems = document.querySelectorAll('.patient-item');
            const noResults = document.getElementById('noPatientsFound');
            const searchStatus = document.getElementById('patientSearchStatus');
            const searchCountSpan = document.getElementById('patientSearchCount');
            let visibleCount = 0;
            
            // Update search status
            if (searchStatus) {
                if (searchTerm.length > 0) {
                    searchStatus.classList.remove('hidden');
                } else {
                    searchStatus.classList.add('hidden');
                }
            }
            
            // Filter items
            patientItems.forEach(item => {
                const name = item.dataset.patientName || '';
                const age = item.dataset.patientAge || '';
                const sitio = item.dataset.patientSitio || '';
                
                if (searchTerm === '' || 
                    name.includes(searchTerm) || 
                    age.includes(searchTerm) || 
                    sitio.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    // If this item was selected, clear selection
                    if (selectedPatientId === parseInt(item.dataset.patientId)) {
                        selectedPatientId = 0;
                        item.style.background = 'white';
                        item.style.borderColor = '#e5e7eb';
                        item.style.borderWidth = '1px';
                    }
                }
            });
            
            // Update search count
            if (searchCountSpan) {
                if (searchTerm.length > 0) {
                    searchCountSpan.textContent = `Found ${visibleCount} patient${visibleCount !== 1 ? 's' : ''}`;
                }
            }
            
            // Show/hide no results message
            if (noResults) {
                if (visibleCount === 0 && patientItems.length > 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }
            
            updateLinkButton();
        }

        // Clear resident search
        function clearResidentSearch() {
            const searchInput = document.getElementById('residentSearchInput');
            if (searchInput) {
                searchInput.value = '';
                filterResidents();
            }
        }

        // Clear patient search
        function clearPatientSearch() {
            const searchInput = document.getElementById('patientSearchInput');
            if (searchInput) {
                searchInput.value = '';
                filterPatients();
            }
        }

        // Select resident function
        function selectResident(id, element) {
            // Only select if the item is visible
            if (element.style.display !== 'none') {
                // Remove selection from all resident items
                document.querySelectorAll('[data-resident-id]').forEach(el => {
                    el.style.background = 'white';
                    el.style.borderColor = '#e5e7eb';
                    el.style.borderWidth = '1px';
                });
                
                // Select this item
                element.style.background = '#eff6ff';
                element.style.borderColor = '#3C96E1';
                element.style.borderWidth = '2px';
                
                selectedResidentId = id;
                updateLinkButton();
            }
        }

        // Select patient function
        function selectPatient(id, element) {
            // Only select if the item is visible
            if (element.style.display !== 'none') {
                // Remove selection from all patient items
                document.querySelectorAll('[data-patient-id]').forEach(el => {
                    el.style.background = 'white';
                    el.style.borderColor = '#e5e7eb';
                    el.style.borderWidth = '1px';
                });
                
                // Select this item
                element.style.background = '#f0fdf4';
                element.style.borderColor = '#10b981';
                element.style.borderWidth = '2px';
                
                selectedPatientId = id;
                updateLinkButton();
            }
        }

        // Update link button state
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

        // Perform linking
        function performLinking() {
            if (selectedResidentId && selectedPatientId) {
                if (confirm('Link these accounts? The resident will be connected to the patient record.')) {
                    window.location.href = `?link_resident=1&resident_id=${selectedResidentId}&patient_id=${selectedPatientId}`;
                }
            }
        }

        // Switch to linking tab with specific resident selected
        function switchToLinking(residentId) {
            switchTab('linking');
            setTimeout(() => {
                const card = document.querySelector(`[data-resident-id="${residentId}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    selectResident(residentId, card);
                }
            }, 300);
        }

        // Toggle functions for showing more/less items
        function toggleResidentsList() {
            const additionalResidents = document.getElementById('additionalResidents');
            const showMoreBtn = document.getElementById('showMoreResidentsBtn');
            const showLessBtn = document.getElementById('showLessResidentsBtn');
            
            if (additionalResidents.style.display === 'none' || !additionalResidents.style.display) {
                additionalResidents.style.display = 'block';
                showMoreBtn.classList.add('hidden');
                showLessBtn.classList.remove('hidden');
            } else {
                additionalResidents.style.display = 'none';
                showMoreBtn.classList.remove('hidden');
                showLessBtn.classList.add('hidden');
            }
        }

        function togglePatientsList() {
            const additionalPatients = document.getElementById('additionalPatients');
            const showMoreBtn = document.getElementById('showMorePatientsBtn');
            const showLessBtn = document.getElementById('showLessPatientsBtn');
            
            if (additionalPatients.style.display === 'none' || !additionalPatients.style.display) {
                additionalPatients.style.display = 'block';
                showMoreBtn.classList.add('hidden');
                showLessBtn.classList.remove('hidden');
            } else {
                additionalPatients.style.display = 'none';
                showMoreBtn.classList.remove('hidden');
                showLessBtn.classList.add('hidden');
            }
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            showStaffTab('active');
            
            // Add enter key support for search inputs
            const residentInput = document.getElementById('residentSearchInput');
            if (residentInput) {
                residentInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filterResidents();
                    }
                });
            }
            
            const patientInput = document.getElementById('patientSearchInput');
            if (patientInput) {
                patientInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filterPatients();
                    }
                });
            }
        });
    </script>
</body>

</html>