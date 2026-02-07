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
    <title>Logging Out</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
</head>
<body>
    <div class="fixed inset-0 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-20 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-xl border border-gray-200 animate-fade-in">
            <div class="flex flex-col items-center text-center">
                <!-- Loading Spinner -->
                <div class="relative w-20 h-20 mb-6">
                    <div class="absolute inset-0 rounded-full border-4 border-blue-100"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-blue-400 border-t-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                </div>
                
                <!-- Title -->
                <h3 class="text-2xl font-semibold text-gray-800 mb-3">Logging Out</h3>
                
                <!-- Instruction -->
                <p class="text-gray-600 text-lg">Please wait while we securely sign you out...</p>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '$redirectUrl';
        }, 1500);
    </script>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(rgba(66, 66, 66, 0.5), rgba(0, 0, 0, 0.5)),
                        url('/community-health-tracker/asssets/images/Dev.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</body>
</html>
HTML;
exit();
?>