<?php
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirectBasedOnRole();
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'user';
    
    // Store username for repopulating form
    $_SESSION['login_form_data'] = [
        'username' => $username
    ];
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                .warning-icon {
                    color: #FACC15;
                    font-size: 3rem;
                }
                .pulsing-circle {
                    display: inline-block;
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 9999px;
                    opacity: 0.8;
                    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                .pulsing-yellow {
                    background-color: #FACC15;
                }
                .pulsing-blue {
                    background-color: #38BDF8;
                }
                @keyframes pulse {
                    0%, 100% {
                        transform: scale(1);
                        opacity: 0.8;
                    }
                    50% {
                        transform: scale(1.3);
                        opacity: 0.4;
                    }
                }
            </style>
        </head>
        <body>
            <div class="center-modal">
                <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-xl border border-gray-200 text-center">
                    <div class="flex flex-col items-center">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="warning-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-2.5L13.73 4c-.77-.83-1.96-.83-2.73 0L3.34 16.5c-.77.83.19 2.5 1.73 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">You have input Invalid and Password</h3>
                        <p class="text-gray-500 mb-6">Please input valid username and password</p>
                        <div class="flex flex-col items-center w-full">
                            <div class="w-full flex justify-center">
                                <span class="pulsing-circle pulsing-yellow"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/community-health-tracker/index.php?login=invalid';
                }, 1500);
            </script>
        </body>
        </html>
        HTML;
        exit();
    }
    
    // Attempt login
    $login_result = loginUser($username, $password, $role);

    // Resident lockout handling
    if (is_array($login_result) && isset($login_result['locked'])) {
        $minutes = $login_result['minutes'];
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                .warning-icon {
                    color: #FACC15;
                    font-size: 3rem;
                }
                .pulsing-circle {
                    display: inline-block;
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 9999px;
                    opacity: 0.8;
                    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                .pulsing-yellow {
                    background-color: #FACC15;
                }
                .pulsing-blue {
                    background-color: #38BDF8;
                }
                @keyframes pulse {
                    0%, 100% {
                        transform: scale(1);
                        opacity: 0.8;
                    }
                    50% {
                        transform: scale(1.3);
                        opacity: 0.4;
                    }
                }
            </style>
        <body>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative bg-white/90 backdrop-blur-sm rounded-2xl p-8 max-w-md w-full shadow-xl border border-gray-200 animate-fade-in">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative w-20 h-20 mb-6">
                            <div class="absolute inset-0 rounded-full border-4 border-yellow-100"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-yellow-400 border-t-transparent animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Account Locked</h3>
                        <p class="text-gray-600 text-lg">Your account has been temporarily locked for {$minutes} minutes due to multiple failed login attempts.<br>Please try again later or contact your barangay health center administrator.</p>
                                            <button onclick="window.location.href='../index.php'" class="mt-6 flex items-center px-4 py-2 bg-yellow-400 hover:bg-yellow-500 text-white font-semibold rounded-lg shadow transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="#FACC15" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                                <span class="text-black text-base font-sm">Back to Home</span>
                                            </button>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
        exit();
    }

    if ($login_result === true) {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                .success-icon {
                    color: #38BDF8;
                    font-size: 3rem;
                }
                .pulsing-circle {
                    display: inline-block;
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 9999px;
                    opacity: 0.8;
                    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                .pulsing-yellow {
                    background-color: #FACC15;
                }
                .pulsing-blue {
                    background-color: #38BDF8;
                }
                @keyframes pulse {
                    0%, 100% {
                        transform: scale(1);
                        opacity: 0.8;
                    }
                    50% {
                        transform: scale(1.3);
                        opacity: 0.4;
                    }
                }
            </style>
        </head>
        <body>
            <div class="center-modal">
                <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-xl border border-gray-200 text-center">
                    <div class="flex flex-col items-center">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="success-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">You've successfully signed in.</h3>
                        <p class="text-gray-500 mb-6">Taking you to your dashboard…</p>
                        <div class="flex flex-col items-center w-full">
                            <div class="w-full flex justify-center">
                                <span class="pulsing-circle pulsing-blue"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../user/dashboard.php';
                }, 1500);
            </script>
        </body>
        </html>
        HTML;
        exit();
    } else {
        // Fetch failed attempts for this user
        $failedAttempts = 0;
        if ($role === 'user' && !empty($username)) {
            $stmt = $pdo->prepare("SELECT failed_login_attempts FROM sitio1_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['failed_login_attempts'])) {
                $failedAttempts = (int)$row['failed_login_attempts'];
            }
        }
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                .warning-icon {
                    color: #FACC15;
                    font-size: 3rem;
                }
                .pulsing-circle {
                    display: inline-block;
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 9999px;
                    opacity: 0.8;
                    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                .pulsing-yellow {
                    background-color: #FACC15;
                }
                .pulsing-blue {
                    background-color: #38BDF8;
                }
                @keyframes pulse {
                    0%, 100% {
                        transform: scale(1);
                        opacity: 0.8;
                    }
                    50% {
                        transform: scale(1.3);
                        opacity: 0.4;
                    }
                }
            </style>
        </head>
        <body>
            <div class="center-modal">
                <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-xl border border-gray-200 text-center">
                    <div class="flex flex-col items-center">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="warning-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-2.5L13.73 4c-.77-.83-1.96-.83-2.73 0L3.34 16.5c-.77.83.19 2.5 1.73 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">You have input Invalid and Password</h3>
                        <p class="text-gray-500 mb-2">Please input valid username and password</p>
                        <p class="text-red-500 font-semibold mb-4">Attempts: {$failedAttempts} / 5</p>
                        <div class="flex flex-col items-center w-full">
                            <div class="w-full flex justify-center">
                                <span class="pulsing-circle pulsing-yellow"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../index.php?login=invalid';
                }, 1500);
            </script>
        </body>
        </html>
        HTML;
        exit();
    }
}

// If not POST request, redirect to login
header('Location: /community-health-tracker/login.php');
exit();
?>
