<?php
require_once __DIR__ . '/../includes/auth.php';

// Make sure the session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
$fullName = isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : '';
$username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : '';

// Log logout to DB and file
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
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

    if ($userId) {
        $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action_type, action_timestamp, ip_address, user_agent) VALUES (?, 'logout', NOW(), ?, ?)");
        $stmt->execute([$userId, $ip, $ua]);

        // Also log staff logout to staff_activity_log for accountability
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

            $stmtStaffLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'staff_logout', NULL, ?, ?, ?, NOW())");
            $stmtStaffLog->execute([$userId, json_encode(['full_name' => $fullName, 'username' => $username]), $ip, $ua]);
        }
    }
} catch (Exception $e) {
    error_log('Logout logging error: ' . $e->getMessage());
}

try {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'logout',
        'user_id' => $userId,
        'role' => $role,
        'ip' => $ip ?? null,
        'user_agent' => $ua ?? null,
        'script' => (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ($_SERVER['SCRIPT_NAME'] ?? ''))
    ];
    @file_put_contents(__DIR__ . '/../logs/save_actions.log', json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    error_log('Logout file log error: ' . $e->getMessage());
}

// Store the role before clearing session
$redirectRole = $role;

// Clear all session variables
$_SESSION = [];

// Remove the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Determine redirect URL
$redirectUrl = '../index-admin-staff.php';

// Show loading animation before redirect
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out</title>
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            width: 100%;
            background: #fff;
        }
        .center-modal {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-icon {
            color: #38BDF8;
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="center-modal">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-xl border border-gray-200 text-center">
            <div class="flex flex-col items-center">
                <div class="logout-icon">
                    
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-2">You're now logged out.</h3>
                <p class="text-gray-500 mb-6">See you next time!</p>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '$redirectUrl';
        }, 1500);
    </script>
</body>
</html>
HTML;
exit();
?>