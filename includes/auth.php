<?php
// auth.php - UPDATED with session check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'; 
}

function isStaff() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'staff';
}

function isUser() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'user';
}

function hasProfileImage() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user']['id'];
    $profile_dir = __DIR__ . '/../uploads/profiles/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    foreach ($allowed_extensions as $ext) {
        $profile_file = $profile_dir . 'profile_' . $user_id . '.' . $ext;
        if (file_exists($profile_file)) {
            return true;
        }
    }
    
    return false;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ../index-admin-staff.php');
        exit();
    }
}

function redirectIfUserMissingProfile() {
    if (!isLoggedIn()) {
        header('Location: ../index-admin-staff.php');
        exit();
    }
    
    // Skip check if already uploading profile to prevent redirect loop
    if (isset($_SESSION['uploading_profile']) && $_SESSION['uploading_profile'] === true) {
        return;
    }
    
    // Only enforce profile image requirement for resident users
    if (isUser() && !hasProfileImage()) {
        // Store the intended page so we can redirect back after upload
        $_SESSION['redirect_after_profile'] = $_SERVER['REQUEST_URI'];
        header('Location: /community-health-tracker/user/upload-profile.php');
        exit();
    }
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header('Location: /community-health-tracker/admin/dashboard.php');
        } elseif (isStaff()) {
            header('Location: /community-health-tracker/staff/dashboard.php');
        } elseif (isUser()) {
            header('Location: /community-health-tracker/user/dashboard.php');
        }
        exit();
    }
}

function loginUser($username, $password, $role) {
    global $pdo;
    
    $table = '';
    switch ($role) {
        case 'admin':
            $table = 'admin';
            break;
        case 'staff':
            $table = 'sitio1_staff';
            break;
        case 'user':
            $table = 'sitio1_users';
            break;
        default:
            return false;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Resident login lockout logic
    if ($role === 'user' && $user) {
        $now = new DateTime();
        $lockedUntil = $user['account_locked_until'] ? new DateTime($user['account_locked_until']) : null;
        $lastFailed = $user['last_failed_login'] ? new DateTime($user['last_failed_login']) : null;

        // Reset failed attempts if last fail was over 15 min ago
        if ($lastFailed && $now->getTimestamp() - $lastFailed->getTimestamp() > 900) {
            $pdo->prepare("UPDATE sitio1_users SET failed_login_attempts = 0 WHERE id = ?")->execute([$user['id']]);
            $user['failed_login_attempts'] = 0;
        }

        // If locked, check if lockout expired
        if ($lockedUntil && $now < $lockedUntil) {
            $minutes = ceil(($lockedUntil->getTimestamp() - $now->getTimestamp()) / 60);
            return [
                'locked' => true,
                'minutes' => $minutes
            ];
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        // For staff accounts, check if they're active
        if ($role === 'staff' && isset($user['status']) && $user['status'] !== 'active') {
            return 'Your staff account is deactivated. Please contact an administrator.';
        }

        // For regular users, check if they're approved
        if ($role === 'user' && !$user['approved']) {
            return 'Your account is pending approval by the Admin!';
        }

        // Reset failed attempts and lockout on successful login
        if ($role === 'user') {
            $pdo->prepare("UPDATE sitio1_users SET failed_login_attempts = 0, last_failed_login = NULL, account_locked_until = NULL WHERE id = ?")->execute([$user['id']]);
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $role
        ];

        // Log login to database table and to file (create table if missing)
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Ensure user_activity_log exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action_type VARCHAR(50),
                action_timestamp DATETIME,
                ip_address VARCHAR(45),
                user_agent TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmtLog = $pdo->prepare("INSERT INTO user_activity_log (user_id, action_type, action_timestamp, ip_address, user_agent) VALUES (?, 'login', NOW(), ?, ?)");
            $stmtLog->execute([$user['id'], $ip, $ua]);

            // Also log staff logins to staff_activity_log for accountability
            if ($role === 'staff') {
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

                $stmtStaffLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'staff_login', NULL, ?, ?, ?, NOW())");
                $stmtStaffLog->execute([$user['id'], json_encode(['full_name' => $user['full_name'], 'username' => $user['username']]), $ip, $ua]);
            }
        } catch (Exception $e) {
            error_log('Login logging error: ' . $e->getMessage());
        }

        // Append to JSON log file
        try {
            $log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'login',
                'user_id' => $user['id'],
                'role' => $role,
                'ip' => $ip ?? null,
                'user_agent' => $ua ?? null,
                'script' => 
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ($_SERVER['SCRIPT_NAME'] ?? ''))
            ];
            @file_put_contents(__DIR__ . '/../logs/save_actions.log', json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log('Login file log error: ' . $e->getMessage());
        }

        return true;
    }

    // If failed login for resident, increment counter and lock if needed
    if ($role === 'user' && $user) {
        $now = new DateTime();
        $failed = $user['failed_login_attempts'] + 1;
        $update = [
            'failed_login_attempts' => $failed,
            'last_failed_login' => $now->format('Y-m-d H:i:s')
        ];
        $lockout = false;
        if ($failed >= 5) {
            $lockMinutes = 20;
            $update['account_locked_until'] = $now->modify("+{$lockMinutes} minutes")->format('Y-m-d H:i:s');
            $lockout = true;
        }
        $set = [];
        foreach ($update as $k => $v) $set[] = "$k = " . $pdo->quote($v);
        $pdo->exec("UPDATE sitio1_users SET ".implode(", ", $set)." WHERE id = " . intval($user['id']));
        if ($lockout) {
            return [
                'locked' => true,
                'minutes' => 20
            ];
        }
    }
    return false;
}
?>