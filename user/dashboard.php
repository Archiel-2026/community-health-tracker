<?php
// user/dashboard.php

require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for resident users after 1 hour of inactivity ---
// if (isUser()) {
//     $now = time();
//     if (!isset($_SESSION['last_action'])) {
//         $_SESSION['last_action'] = $now;
//     } else {
//         $inactive = $now - $_SESSION['last_action'];
//         if ($inactive >= 600) { // 10 minutes = 600 seconds
//             // Destroy session and redirect to resident landing page
//             session_unset();
//             session_destroy();
//             header('Location: /community-health-tracker/index.php');
//             exit();
//         } else {
//             $_SESSION['last_action'] = $now;
//         }
//     }
// }

require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();
if (!isUser()) {
    header('Location: /community-health-tracker/');
    exit();
}

// Check if user has profile image, if not redirect to upload profile page
redirectIfUserMissingProfile();

global $pdo;

$userId = $_SESSION['user']['id'];
$userData = null;
$error = '';
$success = '';
$activeTab = $_GET['tab'] ?? 'analytics';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, gender, age, date_of_birth, address, sitio, contact, civil_status, occupation, approved, approved_by, unique_number, created_at, last_login, status, role, profile_image FROM sitio1_users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        $error = 'User data not found.';
    }
} catch (PDOException $e) {
    $error = 'Error fetching user data: ' . $e->getMessage();
}

// Initialize analytics data with defaults
$analytics = [
    'health_issues' => 0,
    'announcements' => 0,
    'consultations' => 0,
    'lab_results' => 0, // Add lab_results to analytics
    'resolved_issues' => 0,
    'pending_issues' => 0
];

$healthIssuesData = [];
$activityLog = [];
$announcementStats = ['accepted' => 0, 'dismissed' => 0, 'pending' => 0];
$labResultCount = 0;

// Initialize chartData with defaults BEFORE the if block
$chartData = [
    ['category' => 'Health Records', 'count' => 0, 'color' => '#ef4444', 'icon' => 'fas fa-heartbeat'],
    ['category' => 'Announcements', 'count' => 0, 'color' => '#3b82f6', 'icon' => 'fas fa-bullhorn'],
    ['category' => 'Consultations', 'count' => 0, 'color' => '#10b981', 'icon' => 'fas fa-file-medical-alt'],
    ['category' => 'Lab Results', 'count' => 0, 'color' => '#a78bfa', 'icon' => 'fas fa-vial']
];

if ($userData) {
    try {
        // 1. Get Health Issues Count (from sitio1_patients table)
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM sitio1_patients 
                WHERE user_id = ? 
                AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            $analytics['health_issues'] = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            // Fallback if table doesn't exist
            $analytics['health_issues'] = 0;
            if (empty($error)) {
                $error = "Note: Could not fetch health issues count. " . $e->getMessage();
            }
        }

        // 2. Get Announcements Count (from announcements and user_announcements tables)
        try {
            // First, get all announcements targeted to this user with full details, sorted by latest first
            $stmt = $pdo->prepare("
                SELECT a.*, ua.status as user_status, s.full_name as staff_name, s.position as staff_position
                FROM sitio1_announcements a
                LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
                LEFT JOIN sitio1_staff s ON a.staff_id = s.id
                WHERE a.status = 'active'
                AND (a.audience_type = 'public' OR a.id IN (
                    SELECT announcement_id FROM announcement_targets WHERE user_id = ?
                ))
                ORDER BY a.post_date DESC
            ");
            $stmt->execute([$userId, $userId]);
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $analytics['announcements'] = count($announcements);

            // Get announcement statistics
            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count 
                FROM user_announcements 
                WHERE user_id = ?
                GROUP BY status
            ");
            $stmt->execute([$userId]);
            $announcementStatsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($announcementStatsResult as $stat) {
                $announcementStats[$stat['status']] = (int) $stat['count'];
            }

            // Calculate pending announcements
            $announcementStats['pending'] = $analytics['announcements'] -
                ($announcementStats['accepted'] + $announcementStats['dismissed']);

        } catch (Exception $e) {
            $analytics['announcements'] = 0;
            if (empty($error)) {
                $error .= " Note: Could not fetch announcements data.";
            }
        }

        // 3. Get Consultations Count (from consultation_notes table)
        try {
            // First, get all patients linked to this user
            $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE user_id = ? AND deleted_at IS NULL");
            $stmt->execute([$userId]);
            $patientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($patientIds)) {
                $placeholders = str_repeat('?,', count($patientIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM consultation_notes 
                    WHERE patient_id IN ($placeholders)
                ");
                $stmt->execute($patientIds);
                $analytics['consultations'] = $stmt->fetchColumn() ?: 0;
            }
        } catch (Exception $e) {
            $analytics['consultations'] = 0;
            if (empty($error)) {
                $error .= " Note: Could not fetch consultations data.";
            }
        }

        // 4. Get Activity Log (user logins/logouts)
        try {
            // First check if user_activity_log table exists
            $tableExists = false;
            try {
                $testStmt = $pdo->query("SELECT 1 FROM user_activity_log LIMIT 1");
                $tableExists = true;
            } catch (Exception $e) {
                $tableExists = false;
            }

            if ($tableExists) {
                $stmt = $pdo->prepare("
                    SELECT 
                        action_type,
                        action_timestamp,
                        ip_address,
                        user_agent
                    FROM user_activity_log 
                    WHERE user_id = ?
                    AND action_type IN ('login', 'logout')
                    ORDER BY action_timestamp DESC
                    LIMIT 10
                ");
                $stmt->execute([$userId]);
                $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Create demo activity log
                $activityLog = [
                    [
                        'action_type' => 'login',
                        'action_timestamp' => date('Y-m-d H:i:s'),
                        'ip_address' => '192.168.1.1',
                        'user_agent' => 'Chrome/Windows',
                        'browser' => 'Chrome',
                        'device' => 'Desktop'
                    ],
                    [
                        'action_type' => 'logout',
                        'action_timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                        'ip_address' => '192.168.1.1',
                        'user_agent' => 'Chrome/Windows',
                        'browser' => 'Chrome',
                        'device' => 'Desktop'
                    ],
                    [
                        'action_type' => 'login',
                        'action_timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                        'ip_address' => '192.168.1.1',
                        'user_agent' => 'Firefox/Windows',
                        'browser' => 'Firefox',
                        'device' => 'Desktop'
                    ],
                    [
                        'action_type' => 'login',
                        'action_timestamp' => date('Y-m-d H:i:s', strtotime('-3 days')),
                        'ip_address' => '192.168.1.100',
                        'user_agent' => 'Mobile Safari',
                        'browser' => 'Safari',
                        'device' => 'Mobile'
                    ],
                    [
                        'action_type' => 'logout',
                        'action_timestamp' => date('Y-m-d H:i:s', strtotime('-4 days')),
                        'ip_address' => '192.168.1.100',
                        'user_agent' => 'Mobile Safari',
                        'browser' => 'Safari',
                        'device' => 'Mobile'
                    ]
                ];
            }

            // Process activity log for better display
            foreach ($activityLog as &$activity) {
                if (!isset($activity['browser'])) {
                    // Parse user agent to get browser info
                    $ua = $activity['user_agent'] ?? '';
                    if (stripos($ua, 'chrome') !== false) {
                        $activity['browser'] = 'Chrome';
                    } elseif (stripos($ua, 'firefox') !== false) {
                        $activity['browser'] = 'Firefox';
                    } elseif (stripos($ua, 'safari') !== false) {
                        $activity['browser'] = 'Safari';
                    } else {
                        $activity['browser'] = 'Browser';
                    }

                    if (stripos($ua, 'mobile') !== false || stripos($ua, 'android') !== false || stripos($ua, 'iphone') !== false) {
                        $activity['device'] = 'Mobile';
                    } else {
                        $activity['device'] = 'Desktop';
                    }
                }

                // Format time
                $activity['formatted_time'] = date('h:i A', strtotime($activity['action_timestamp']));
                $activity['formatted_date'] = date('M d, Y', strtotime($activity['action_timestamp']));
                $activity['time_ago'] = getTimeAgo($activity['action_timestamp']);
            }

        } catch (Exception $e) {
            // Demo activity log on error
            $activityLog = [
                [
                    'action_type' => 'login',
                    'action_timestamp' => date('Y-m-d H:i:s'),
                    'ip_address' => '192.168.1.1',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'formatted_time' => date('h:i A'),
                    'formatted_date' => date('M d, Y'),
                    'time_ago' => 'Just now'
                ]
            ];
        }

        // 5. Get Consultation Notes (Doctor's Notes) - matching health_records.php
        $consultationNotes = [];
        try {
            // Get all patients linked to this user
            $stmt = $pdo->prepare("SELECT id, full_name FROM sitio1_patients WHERE user_id = ? AND deleted_at IS NULL");
            $stmt->execute([$userId]);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($patients)) {
                $patientIds = array_column($patients, 'id');
                $placeholders = str_repeat('?,', count($patientIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT 
                        cn.*,
                        cn.doctor_name as doctor_name
                    FROM consultation_notes cn
                    WHERE cn.patient_id IN ($placeholders)
                    ORDER BY cn.consultation_date DESC
                ");
                $stmt->execute($patientIds);
                $consultationNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Add patient name to each note
                foreach ($consultationNotes as &$note) {
                    foreach ($patients as $patient) {
                        if ($patient['id'] == $note['patient_id']) {
                            $note['patient_name'] = $patient['full_name'];
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Silently fail
        }

        // Update chartData with actual values
        $chartData = [
            ['category' => 'Health Records', 'count' => $analytics['health_issues'], 'color' => '#ef4444', 'icon' => 'fas fa-heartbeat'],
            ['category' => 'Announcements', 'count' => $analytics['announcements'], 'color' => '#3b82f6', 'icon' => 'fas fa-bullhorn'],
            ['category' => 'Consultations', 'count' => $analytics['consultations'], 'color' => '#10b981', 'icon' => 'fas fa-file-medical-alt'],
            ['category' => 'Lab Results', 'count' => $analytics['lab_results'], 'color' => '#a78bfa', 'icon' => 'fas fa-vial']
        ];

    } catch (PDOException $e) {
        $error = 'Error fetching analytics data: ' . $e->getMessage();
    }
}

// Get lab result announcements for the user using a direct query (handles public and specific)
$labResults = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*
        FROM sitio1_announcements a
        WHERE a.status = 'active'
        AND a.announcement_type = 'lab_result'
        AND (
            a.audience_type = 'public'
            OR a.id IN (SELECT announcement_id FROM announcement_targets WHERE user_id = ?)
        )
        ORDER BY a.post_date DESC
    ");
    $stmt->execute([$userId]);
    $labResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labResultsCount = count($labResults);
    $analytics['lab_results'] = $labResultsCount;
} catch (PDOException $e) {
    $labResultsCount = 0;
    $analytics['lab_results'] = 0;
}

// Helper function to calculate time ago
function getTimeAgo($datetime)
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>

<link rel="stylesheet" href="/asssets/css/normalize.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Local Font Awesome for offline support -->
<link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
<link rel="stylesheet" href="/community-health-tracker/asssets/css/resident-dashboard.css">
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="bg-gray-100 content-visible">
    <div class="px-6 py-8">
        <!-- Dashboard Header -->
        <!-- <div class="flex justify-between items-center mb-10">
            <h1 class="text-2xl font-bold flex items-center">
                <div class="stats-icon-container bg-blue-100 mr-4">
                    <i class="fas fa-chart-pie text-blue-600 text-2xl bold-icon"></i>
                </div>
                Health Analytics Dashboard
            </h1>
           
            <button onclick="openHelpModal()" class="help-icon text-blue-600 hover:text-blue-500 transition">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-circle-question text-2xl bold-icon"></i>
                </div>
            </button>
        </div> -->

        <?php if ($error): ?>
            <div
                class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-5 py-4 rounded mb-5 flex items-center mb-consistent">
                <div class="w-10 h-10 bg-yellow-200 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-circle text-yellow-600 bold-icon"></i>
                </div>
                <span class="text-base">Note: <?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                class="bg-green-100 border border-green-400 text-green-700 px-5 py-4 rounded mb-5 flex items-center mb-consistent">
                <div class="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-check-circle text-green-600 bold-icon"></i>
                </div>
                <span class="text-base"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Tabs - Only Analytics -->
        <!-- <div class="flex border-b border-gray-200 mb-8">
            <a href="?tab=analytics" class="<?= $activeTab === 'analytics' ? 'border-b-3 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-5 py-3 font-medium flex items-center text-lg">
                <i class="fas fa-chart-pie text-xl mr-3 bold-icon"></i>
                Health Analytics
            </a>
        </div> -->

        <!-- Analytics Tab Content -->
        <div class="tab-content <?= $activeTab === 'analytics' ? 'active' : '' ?>">
            <!-- Health Analytics Dashboard -->
            <div class="space-y-8">
                <!-- Stats Overview Cards -->
                <div class="stats-grid mb-consistent">
                    <!-- Consultations Card -->
                    <div class="stats-card">
                        <div>
                            <div class="flex items-center justify-between">
                                <div class="stats-icon-container bg-first-card">
                                    <svg width="72" height="72" viewBox="0 0 66 66" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0 4C0 1.79086 1.79086 0 4 0H62C64.2091 0 66 1.79086 66 4V62C66 64.2091 64.2091 66 62 66H4C1.79086 66 0 64.2091 0 62V4Z"
                                            fill-opacity="0.3" />
                                        <path
                                            d="M36.8105 25.2857C37.8182 25.2857 38.8032 24.9841 39.641 24.419C40.4788 23.8539 41.1318 23.0507 41.5174 22.1109C41.9031 21.1712 42.004 20.1372 41.8074 19.1395C41.6108 18.1419 41.1256 17.2256 40.413 16.5063C39.7005 15.7871 38.7927 15.2973 37.8045 15.0988C36.8162 14.9004 35.7918 15.0022 34.8609 15.3915C33.9299 15.7807 33.1342 16.4399 32.5744 17.2856C32.0146 18.1314 31.7158 19.1257 31.7158 20.1429C31.7158 21.5068 32.2526 22.8149 33.208 23.7794C34.1634 24.7439 35.4593 25.2857 36.8105 25.2857ZM36.8105 17.5714C37.3143 17.5714 37.8069 17.7222 38.2258 18.0048C38.6447 18.2873 38.9712 18.6889 39.164 19.1588C39.3568 19.6287 39.4072 20.1457 39.3089 20.6445C39.2107 21.1433 38.968 21.6015 38.6118 21.9611C38.2555 22.3208 37.8016 22.5657 37.3075 22.6649C36.8133 22.7641 36.3012 22.7132 35.8357 22.5185C35.3702 22.3239 34.9724 21.9943 34.6925 21.5715C34.4126 21.1486 34.2632 20.6514 34.2632 20.1429C34.2632 19.4609 34.5315 18.8068 35.0093 18.3246C35.487 17.8423 36.1349 17.5714 36.8105 17.5714ZM47 35.5714C47 35.9124 46.8658 36.2394 46.6269 36.4806C46.3881 36.7217 46.0641 36.8571 45.7263 36.8571C40.1046 36.8571 37.2961 33.9948 35.0401 31.695C34.6039 31.2498 34.1867 30.8271 33.7664 30.435L31.6282 35.3979L37.5509 39.668C37.7158 39.787 37.8503 39.944 37.9431 40.126C38.0358 40.3079 38.0842 40.5096 38.0842 40.7143V49.7143C38.0842 50.0553 37.95 50.3823 37.7112 50.6234C37.4723 50.8645 37.1483 51 36.8105 51C36.4727 51 36.1488 50.8645 35.9099 50.6234C35.671 50.3823 35.5368 50.0553 35.5368 49.7143V41.3764L30.5902 37.8086L25.2423 50.227C25.1433 50.4567 24.98 50.6523 24.7724 50.7897C24.5647 50.927 24.3219 51.0001 24.0737 51C23.8988 51.0004 23.7257 50.9637 23.5658 50.8923C23.2562 50.7564 23.0126 50.502 22.8888 50.185C22.7649 49.868 22.7707 49.5143 22.9051 49.2016L31.5152 29.2136C30.0329 28.9484 28.1845 29.4064 25.9906 30.5925C24.2408 31.5668 22.6078 32.7407 21.1235 34.0912C20.8758 34.3153 20.551 34.4326 20.2187 34.4181C19.8864 34.4036 19.5729 34.2585 19.3452 34.0137C19.1175 33.769 18.9937 33.444 19.0002 33.1083C19.0068 32.7727 19.1431 32.4529 19.3801 32.2173C19.7782 31.8396 29.2018 23.0196 35.0974 28.1866C35.7072 28.7202 36.2883 29.3116 36.8487 29.8854C39.0697 32.1482 41.1665 34.2857 45.7263 34.2857C46.0641 34.2857 46.3881 34.4212 46.6269 34.6623C46.8658 34.9034 47 35.2304 47 35.5714Z"
                                            fill="#16A34A" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-4xl font-bold text-green-600 mb-1"><?= $analytics['consultations'] ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-20">
                                <h3 class="text-2xl font-semibold text-gray-700 mb-1">Consultations</h3>
                                <p class="text-md text-gray-400">Consultations Visits</p>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements Card -->
                    <div class="stats-card">
                        <div>
                            <div class="flex items-center justify-between">
                                <div class="stats-icon-container bg-second-card">
                                    <svg width="72" height="72" viewBox="0 0 66 66" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0 4C0 1.79086 1.79086 0 4 0H62C64.2091 0 66 1.79086 66 4V62C66 64.2091 64.2091 66 62 66H4C1.79086 66 0 64.2091 0 62V4Z"
                                            fill-opacity="0.3" />
                                        <path
                                            d="M49.4948 26.2183L20.61 17.3589C20.219 17.2449 19.8069 17.2234 19.4062 17.2961C19.0055 17.3688 18.6273 17.5338 18.3013 17.7779C17.9754 18.0221 17.7107 18.3387 17.5282 18.7028C17.3458 19.0668 17.2505 19.4684 17.25 19.8756V43.5006C17.25 44.1968 17.5266 44.8645 18.0188 45.3568C18.5111 45.8491 19.1788 46.1256 19.875 46.1256C20.126 46.1257 20.3757 46.0898 20.6166 46.019L34.3125 41.8157V43.5006C34.3125 44.1968 34.5891 44.8645 35.0813 45.3568C35.5736 45.8491 36.2413 46.1256 36.9375 46.1256H42.1875C42.8837 46.1256 43.5514 45.8491 44.0437 45.3568C44.5359 44.8645 44.8125 44.1968 44.8125 43.5006V38.5952L49.4948 37.1596C50.0368 36.9968 50.512 36.6641 50.8505 36.2107C51.1891 35.7573 51.3729 35.2071 51.375 34.6413V28.735C51.3726 28.1694 51.1885 27.6196 50.85 27.1665C50.5116 26.7134 50.0365 26.381 49.4948 26.2183ZM34.3125 39.0709L19.875 43.5006V19.8756L34.3125 24.3053V39.0709ZM42.1875 43.5006H36.9375V41.0102L42.1875 39.3991V43.5006ZM48.75 34.6413H48.732L36.9375 38.2638V25.1125L48.732 28.7219H48.75V34.6281V34.6413Z"
                                            fill="#2563EB" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-4xl font-bold text-blue-600 mb-1"><?= $analytics['announcements'] ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-20">
                                <h3 class="text-2xl font-semibold text-gray-700 mb-1">Announcements</h3>
                                <p class="text-base text-gray-400">Active announcements</p>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Results Card -->
                    <div class="stats-card">
                        <div>
                            <div class="flex items-center justify-between">
                                <div class="stats-icon-container bg-third-card">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8 17V15H10.175C10.0917 15.3333 10.0377 15.6667 10.013 16C9.98833 16.3333 9.99233 16.6667 10.025 17H8ZM8 22C6.61667 22 5.43767 21.5123 4.463 20.537C3.48833 19.5617 3.00067 18.3827 3 17V8H1V2H15V8H13V11.025C12.6 11.275 12.2333 11.5667 11.9 11.9C11.5667 12.2333 11.275 12.6 11.025 13H8V11H11V8H5V17C5 17.8333 5.29167 18.5417 5.875 19.125C6.45833 19.7083 7.16667 20 8 20C8.5 20 8.95433 19.8917 9.363 19.675C9.77167 19.4583 10.1173 19.1583 10.4 18.775C10.5333 19.1083 10.6833 19.425 10.85 19.725C11.0167 20.025 11.2167 20.3167 11.45 20.6C11 21.0333 10.4833 21.375 9.9 21.625C9.31667 21.875 8.68333 22 8 22ZM3 6H13V4H3V6ZM18.275 18.275C18.7583 17.7917 19 17.2 19 16.5C19 15.8 18.7583 15.2083 18.275 14.725C17.7917 14.2417 17.2 14 16.5 14C15.8 14 15.2083 14.2417 14.725 14.725C14.2417 15.2083 14 15.8 14 16.5C14 17.2 14.2417 17.7917 14.725 18.275C15.2083 18.7583 15.8 19 16.5 19C17.2 19 17.7917 18.7583 18.275 18.275ZM21.6 23L18.9 20.3C18.5333 20.5333 18.15 20.7083 17.75 20.825C17.35 20.9417 16.9333 21 16.5 21C15.25 21 14.1877 20.5627 13.313 19.688C12.4383 18.8133 12.0007 17.7507 12 16.5C11.9993 15.2493 12.437 14.187 13.313 13.313C14.189 12.439 15.2513 12.0013 16.5 12C17.7487 11.9987 18.8113 12.4363 19.688 13.313C20.5647 14.1897 21.002 15.252 21 16.5C21 16.9333 20.9417 17.35 20.825 17.75C20.7083 18.15 20.5333 18.5333 20.3 18.9L23 21.6L21.6 23Z" fill="#9333EA"/>
</svg>
                                </div>
                                <div>
                                    <p class="text-4xl font-bold text-purple-600 mb-1">
                                        <?= isset($labResultsCount) ? $labResultsCount : 0 ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-20">
                                <h3 class="text-2xl font-semibold text-gray-700 mb-1">Lab Results</h3>
                                <p class="text-base text-gray-400">Available results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Activity Log Section -->
                <div class="flex flex-col md:flex-row gap-6 md:gap-7">
                    <!-- LEFT: 40% - Recent Consultations -->
                    <div class="chart-container w-full md:flex-[0_0_40%]">
                        <div class="chart-content-wrapper">
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 md:w-12 md:h-12 bg-first-card rounded-lg flex items-center justify-center ">
                                        <svg class="w-10 h-10" viewBox="0 0 30 30" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M24.375 26.25H5.625C4.59375 26.25 3.75 25.4062 3.75 24.375V5.625C3.75 4.59375 4.59375 3.75 5.625 3.75H26.25V5.625H5.625V24.375H24.375V7.5H26.25V24.375C26.25 25.4062 25.4062 26.25 24.375 26.25ZM14.0625 9.375H9.375V14.0625H14.0625V9.375ZM20.625 9.375H15.9375V14.0625H20.625V9.375ZM14.0625 15.9375H9.375V20.625H14.0625V15.9375ZM20.625 15.9375H15.9375V20.625H20.625V15.9375Z"
                                                fill="#16A34A" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xl md:text-2xl font-600 text-gray-800">
                                        Recent Consultations
                                    </h3>
                                </div>
                                <a href="health_records.php?tab=consultations"
                                    class="text-lg font-medium text-blue-600 hover:text-blue-800" target="_blank"
                                    rel="noopener">View All</a>
                            </div>

                            <div class="space-y-4 flex-1 overflow-auto">
                                <?php if (empty($consultationNotes)): ?>
                                    <div class="text-center py-10 flex flex-col justify-center items-center h-full">
                                        <svg width="70" height="70" class="mb-4" viewBox="0 0 70 70" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M24.0625 26.25C24.0625 25.6698 24.293 25.1134 24.7032 24.7032C25.1134 24.293 25.6698 24.0625 26.25 24.0625H43.75C44.3302 24.0625 44.8866 24.293 45.2968 24.7032C45.707 25.1134 45.9375 25.6698 45.9375 26.25C45.9375 26.8302 45.707 27.3866 45.2968 27.7968C44.8866 28.207 44.3302 28.4375 43.75 28.4375H26.25C25.6698 28.4375 25.1134 28.207 24.7032 27.7968C24.293 27.3866 24.0625 26.8302 24.0625 26.25ZM26.25 37.1875H43.75C44.3302 37.1875 44.8866 36.957 45.2968 36.5468C45.707 36.1366 45.9375 35.5802 45.9375 35C45.9375 34.4198 45.707 33.8634 45.2968 33.4532C44.8866 33.043 44.3302 32.8125 43.75 32.8125H26.25C25.6698 32.8125 25.1134 33.043 24.7032 33.4532C24.293 33.8634 24.0625 34.4198 24.0625 35C24.0625 35.5802 24.293 36.1366 24.7032 36.5468C25.1134 36.957 25.6698 37.1875 26.25 37.1875ZM35 41.5625H26.25C25.6698 41.5625 25.1134 41.793 24.7032 42.2032C24.293 42.6134 24.0625 43.1698 24.0625 43.75C24.0625 44.3302 24.293 44.8866 24.7032 45.2968C25.1134 45.707 25.6698 45.9375 26.25 45.9375H35C35.5802 45.9375 36.1366 45.707 36.5468 45.2968C36.957 44.8866 37.1875 44.3302 37.1875 43.75C37.1875 43.1698 36.957 42.6134 36.5468 42.2032C36.1366 41.793 35.5802 41.5625 35 41.5625ZM61.25 13.125V42.8449C61.2518 43.4197 61.1394 43.989 60.9193 44.52C60.6991 45.0509 60.3756 45.5327 59.9676 45.9375L45.9375 59.9676C45.5327 60.3756 45.0509 60.6991 44.52 60.9193C43.989 61.1394 43.4197 61.2518 42.8449 61.25H13.125C11.9647 61.25 10.8519 60.7891 10.0314 59.9686C9.21094 59.1481 8.75 58.0353 8.75 56.875V13.125C8.75 11.9647 9.21094 10.8519 10.0314 10.0314C10.8519 9.21094 11.9647 8.75 13.125 8.75H56.875C58.0353 8.75 59.1481 9.21094 59.9686 10.0314C60.7891 10.8519 61.25 11.9647 61.25 13.125ZM13.125 56.875H41.5625V43.75C41.5625 43.1698 41.793 42.6134 42.2032 42.2032C42.6134 41.793 43.1698 41.5625 43.75 41.5625H56.875V13.125H13.125V56.875ZM45.9375 45.9375V53.7852L53.7824 45.9375H45.9375Z"
                                                fill="black" fill-opacity="0.3" />
                                            </svg>
                                            <h3 class="text-gray-500 font-semibold text-xl mb-4">No consultation notes yet</h3>
                                            <p class="text-gray-500 text-lg">Consultation note missing. Visit health center for
                                                consultation.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                        $recentNotes = array_slice($consultationNotes, 0, 3);
                                        foreach ($recentNotes as $note): ?>
                                            <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white">
                                                <div class="flex justify-between items-start w-full">
                                                    <span
                                                        class="px-4 py-2 rounded bg-second-card text-[#2563EB] font-semibold text-base">Consultation
                                                        Note</span>
                                                    <div class="flex flex-col items-start">
                                                        <span class="text-sm text-gray-400 font-semibold mb-2">Consultation Date
                                                            :</span>
                                                        <span
                                                            class="inline-block px-3 py-2 rounded bg-gray-200 text-gray-600 text-base font-medium"
                                                            style="margin-top:2px;"><?= date('F d, Y', strtotime($note['consultation_date'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="block text-base text-gray-400 font-sm mb-0.5">Consulting Doctor
                                                        :</span>
                                                    <div class="text-lg text-gray-800 font-medium">
                                                        <?= htmlspecialchars($note['doctor_name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: 60% - Announcements and Lab Results -->
                    <div class="chart-container-two w-full md:flex-[0_0_60%]">
                        <div class="chart-content-wrapper">
                            <!-- Lab Results Section (if any) -->
                            <?php if (!empty($labAnnouncements)): ?>
                                <div class="mb-6">
                                    <div
                                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-5 mb-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-flask text-green-600 text-lg md:text-xl"></i>
                                            </div>
                                            <h3 class="text-xl md:text-2xl font-600 text-gray-800">
                                                Laboratory Results
                                            </h3>
                                        </div>
                                        <a href="announcements.php"
                                            class="text-base font-medium text-green-600 hover:text-green-800">View All</a>
                                    </div>

                                    <div class="activity-log-container custom-scrollbar mb-5">
                                        <?php
                                        $recentLabResults = array_slice($labAnnouncements, 0, 2);
                                        foreach ($recentLabResults as $labResult):
                                            ?>
                                            <div
                                                class="border-2 border-green-200 bg-green-50 rounded-lg p-4 hover:shadow-md transition-shadow mb-3">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex items-center gap-3">
                                                        <i class="fas fa-flask text-green-600"></i>
                                                        <h4 class="font-semibold text-gray-800 text-base">
                                                            <?= htmlspecialchars($labResult['title']) ?>
                                                        </h4>
                                                    </div>
                                                    <span class="text-sm px-2 py-1 rounded-full <?=
                                                        $labResult['priority'] === 'high' ? 'bg-red-100 text-red-800' :
                                                        ($labResult['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')
                                                        ?>">
                                                            <?= ucfirst($labResult['priority']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-green-700 mb-2">
                                                    <i class="fas fa-user-md"></i>
                                                    <?= htmlspecialchars($labResult['staff_name'] ?? 'Medical Staff') ?> •
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('M d, Y', strtotime($labResult['post_date'])) ?>
                                                </p>
                                                <p class="text-base text-gray-700 mb-2">
                                                    <?= substr(htmlspecialchars($labResult['message']), 0, 80) ?>...
                                                </p>
                                                <div class="flex gap-2 text-sm">
                                                    <span class="px-2 py-1 rounded bg-green-200 text-green-800 font-semibold">
                                                        <i class="fas fa-flask"></i> Laboratory Result
                                                    </span>
                                                    <?php if ($labResult['user_status']): ?>
                                                        <span
                                                            class="px-2 py-1 rounded bg-<?= $labResult['user_status'] === 'accepted' ? 'green' : 'gray' ?>-100 text-<?= $labResult['user_status'] === 'accepted' ? 'green' : 'gray' ?>-800">
                                                            <i
                                                                class="fas fa-<?= $labResult['user_status'] === 'accepted' ? 'check-circle' : 'times-circle' ?>"></i>
                                                            <?= ucfirst($labResult['user_status']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-clock"></i> Pending Response
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- General Announcements Section -->
<div class="flex flex-col h-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-5 mb-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-second-card rounded-lg flex items-center justify-center">
                <svg class="w-10 h-10" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M26.782 10.1551L6.15 3.82695C5.87075 3.74551 5.57638 3.73015 5.29017 3.78209C5.00396 3.83404 4.73376 3.95186 4.50094 4.12625C4.26812 4.30064 4.07907 4.5268 3.94873 4.78686C3.8184 5.04691 3.75036 5.33372 3.75 5.62461V22.4996C3.75 22.9969 3.94754 23.4738 4.29917 23.8254C4.65081 24.1771 5.12772 24.3746 5.625 24.3746C5.8043 24.3747 5.98268 24.349 6.15469 24.2984L15.9375 21.2961V22.4996C15.9375 22.9969 16.135 23.4738 16.4867 23.8254C16.8383 24.1771 17.3152 24.3746 17.8125 24.3746H21.5625C22.0598 24.3746 22.5367 24.1771 22.8883 23.8254C23.24 23.4738 23.4375 22.9969 23.4375 22.4996V18.9957L26.782 17.9703C27.1691 17.854 27.5086 17.6164 27.7504 17.2925C27.9922 16.9687 28.1235 16.5757 28.125 16.1715V11.9527C28.1233 11.5488 27.9918 11.156 27.75 10.8324C27.5083 10.5088 27.1689 10.2713 26.782 10.1551ZM15.9375 19.3355L5.625 22.4996V5.62461L15.9375 8.78867V19.3355ZM21.5625 22.4996H17.8125V20.7207L21.5625 19.5699V22.4996ZM26.25 16.1715H26.2371L17.8125 18.759V9.36523L26.2371 11.9434H26.25V16.1621V16.1715Z" fill="#2563EB" />
                </svg>
            </div>
            <h3 class="text-xl md:text-2xl font-600 text-gray-800">
                All Announcements
            </h3>
        </div>
        <a href="announcements.php?tab=announcements" class="text-lg font-medium text-blue-600 hover:text-blue-800" target="_blank" rel="noopener">View All</a>
    </div>

    <div id="announcementsContainer" class="space-y-3 flex-1" style="min-height: 0;">
        <?php
        // Filter out lab results from general announcements
        $generalAnnouncements = array_filter($announcements ?? [], function ($ann) {
            return !isset($ann['announcement_category']) || $ann['announcement_category'] !== 'lab_result';
        });

        // Reindex array to ensure proper indexing
        $generalAnnouncements = array_values($generalAnnouncements);

        if (empty($generalAnnouncements)):
        ?>
        <div class="text-center py-10 flex flex-col justify-center items-center h-full">
            <svg width="70" height="70" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21.4256 8.1225L4.92 3.06C4.6966 2.99484 4.4611 2.98255 4.23213 3.02411C4.00316 3.06567 3.787 3.15993 3.60075 3.29944C3.41449 3.43895 3.26325 3.61988 3.15899 3.82792C3.05472 4.03597 3.00029 4.26542 3 4.49813V17.9981C3 18.396 3.15804 18.7775 3.43934 19.0588C3.72064 19.3401 4.10218 19.4981 4.5 19.4981C4.64344 19.4982 4.78614 19.4777 4.92375 19.4372L12.75 17.0353V17.9981C12.75 18.396 12.908 18.7775 13.1893 19.0588C13.4706 19.3401 13.8522 19.4981 14.25 19.4981H17.25C17.6478 19.4981 18.0294 19.3401 18.3107 19.0588C18.592 18.7775 18.75 18.396 18.75 17.9981V15.195L21.4256 14.3747C21.7353 14.2816 22.0069 14.0916 22.2003 13.8325C22.3937 13.5734 22.4988 13.259 22.5 12.9356V9.56063C22.4986 9.23745 22.3934 8.92326 22.2 8.66435C22.0066 8.40544 21.7351 8.2155 21.4256 8.1225ZM12.75 15.4669L4.5 17.9981V4.49813L12.75 7.02938V15.4669ZM17.25 17.9981H14.25V16.575L17.25 15.6544V17.9981ZM21 12.9356H20.9897L14.25 15.0056V7.49063L20.9897 9.55313H21V12.9281V12.9356Z" fill="#9A9A9A"/>
            </svg>
            <h3 class="text-gray-500 font-semibold text-xl mb-4">No announcements posted yet</h3>
            <p class="text-gray-500 text-lg">No announcements are currently available.</p>
        </div>
        <?php else: ?>
            <?php
            // Get exactly 3 announcements to display
            $displayAnnouncements = array_slice($generalAnnouncements, 0, 3);
            $remainingCount = count($generalAnnouncements) - 3;
            
            foreach ($displayAnnouncements as $index => $announcement):
                $isPriorityHigh = $announcement['priority'] === 'high';
                $isPriorityMedium = $announcement['priority'] === 'medium';
                $isAccepted = $announcement['user_status'] === 'accepted';
                $isDismissed = $announcement['user_status'] === 'dismissed';
                
                // Badge logic for announcement type/audience
                $badge = '';
                $priorityBadge = '';
                $priority = strtolower($announcement['priority'] ?? 'low');
                $greenColor = 'style="color: #FFFFFF;"';
                $highBg = 'style="background-color: #e6bcbc; color: #8b2323;"';
                $mediumBg = 'style="background-color: #FD88024D; color: #FD8802"';
                
                if ((isset($announcement['announcement_type']) && $announcement['announcement_type'] === 'lab_result') || 
                    (isset($announcement['announcement_category']) && $announcement['announcement_category'] === 'lab_result')) {
                    $badge = '<span class="px-4 py-2 rounded bg-lab-result font-semibold text-base" ' . $greenColor . '>Lab Result</span>';
                    if ($priority === 'high') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base" ' . $highBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } elseif ($priority === 'medium') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base" ' . $mediumBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } else {
                        $priorityBadge = '<span class="px-4 py-2 rounded bg-blue-100 text-[#3C96E1] font-semibold text-base">' . ucfirst($announcement['priority']) . '</span>';
                    }
                } elseif (isset($announcement['audience_type']) && $announcement['audience_type'] === 'public') {
                    $badge = '<span class="px-4 py-2 rounded bg-[#2563EB] text-[#FFFFFF] font-semibold text-base">For All Resident</span>';
                    if ($priority === 'high') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base ml-2" ' . $highBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } elseif ($priority === 'medium') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base ml-2" ' . $mediumBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } else {
                        $priorityBadge = '<span class="px-4 py-2 rounded bg-blue-100 text-[#3C96E1] font-semibold text-base ml-2">' . ucfirst($announcement['priority']) . '</span>';
                    }
                } elseif (isset($announcement['audience_type']) && $announcement['audience_type'] === 'specific') {
                    $badge = '<span class="px-4 py-2 rounded bg-[#2563EB] text-[#FFFFFF] font-semibold text-base">For Specific Resident</span>';
                    if ($priority === 'high') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base ml-2" ' . $highBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } elseif ($priority === 'medium') {
                        $priorityBadge = '<span class="px-4 py-2 rounded font-semibold text-base ml-2" ' . $mediumBg . '>' . ucfirst($announcement['priority']) . '</span>';
                    } else {
                        $priorityBadge = '<span class="px-4 py-2 rounded bg-blue-100 text-blue-600 font-semibold text-base ml-2">' . ucfirst($announcement['priority']) . '</span>';
                    }
                }
                
                // Prepare staff display
                $staffPosition = !empty($announcement['staff_position']) ? htmlspecialchars($announcement['staff_position']) : '';
                $staffName = !empty($announcement['staff_name']) ? htmlspecialchars($announcement['staff_name']) : '';
                ?>
                <div class="border border-gray-200 rounded-lg p-4 announcement-item" 
                     data-announcement-id="<?= htmlspecialchars($announcement['id']) ?>"
                     style="cursor: pointer;">
                    <div class="flex justify-between items-start w-full">
                        <div class="flex gap-2 items-center flex-wrap">
                            <?= $badge ?> <?= $priorityBadge ?>
                        </div>
                        <div class="flex flex-col items-start">
                            <span class="text-sm text-gray-400 font-semibold mb-2">Date Posted :</span>
                            <span class="inline-block px-4 py-2 rounded bg-gray-200 text-gray-600 text-base font-semibold">
                                <?= date('F d, Y', strtotime($announcement['post_date'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-0.5 mt-1">
                        <span class="text-base text-gray-400 font-sm">Posted By :</span>
                        <?php if ($staffPosition): ?>
                            <span class="inline-block items-center py-0.5 rounded font-medium text-base">
                                <?= $staffPosition ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($staffName): ?>
                        <div class="text-lg text-gray-800 font-medium mt-0.5">
                            <?= $staffName ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($remainingCount > 0): ?>
                <div class="text-center py-3 mt-2 border-t border-gray-100">
                    <span class="text-blue-600 text-sm font-medium">
                        +<?= $remainingCount ?> more 
                        announcement<?= $remainingCount > 1 ? 's' : '' ?> available
                    </span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Modal -->
        <!-- <div id="helpModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-8 mx-auto p-0 border w-full max-w-2xl shadow-xl rounded-xl bg-white overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">Analytics Dashboard Guide</h3>
                        <p class="text-blue-100 text-base">Understanding your health analytics</p>
                    </div>
                    <button onclick="closeHelpModal()" class="text-white hover:text-blue-200 transition-colors">
                        <i class="fas fa-times text-2xl bold-icon"></i>
                    </button>
                </div>
            </div>
            
            <div class="px-8 py-6">
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-heartbeat text-red-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-xl mb-2">Health Records</h4>
                            <p class="text-gray-600 text-base">Number of patient profiles linked to your account from the sitio1_patients table.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-bullhorn text-blue-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-xl mb-2">Announcements</h4>
                            <p class="text-gray-600 text-base">Active announcements targeted to you. Track your responses and pending items.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-medical-alt text-green-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-xl mb-2">Consultations</h4>
                            <p class="text-gray-600 text-base">Total consultation notes from all linked patient profiles in the consultation_notes table.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-history text-purple-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-xl mb-2">Activity Log</h4>
                            <p class="text-gray-600 text-base">Track your login and logout activities with timestamps and IP addresses.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                <button type="button" onclick="closeHelpModal()" class="px-7 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex items-center text-base">
                    <i class="fas fa-check mr-2 bold-icon"></i>
                    Got it, thanks!
                </button>
            </div>
        </div>
    </div> -->

        <script>
            // Personal Information Modal functions
            // Tab redirect handlers for dashboard items
            document.addEventListener('DOMContentLoaded', function () {
                // Consultation item click: go to health_records.php with tab=record
                document.querySelectorAll('.consultation-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        window.location.href = 'health_records.php?tab=record&record_id=' + item.dataset.recordId;
                    });
                });
                // Announcement item click: go to announcements.php with tab=announcements
                document.querySelectorAll('.announcement-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        window.location.href = 'announcements.php?tab=announcements&announcement_id=' + item.dataset.announcementId;
                    });
                });
            });
            function openPersonalInfoModal() {
                document.getElementById('personalInfoModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closePersonalInfoModal() {
                document.getElementById('personalInfoModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Help modal functions
            function openHelpModal() {
                document.getElementById('helpModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeHelpModal() {
                document.getElementById('helpModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modal when clicking outside
            window.onclick = function (event) {
                const helpModal = document.getElementById('helpModal');
                const personalInfoModal = document.getElementById('personalInfoModal');
                if (event.target === helpModal) {
                    closeHelpModal();
                }
                if (event.target === personalInfoModal) {
                    closePersonalInfoModal();
                }
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeHelpModal();
                    closePersonalInfoModal();
                }
            });
        </script>

        <!-- Personal Information Modal -->
        <div id="personalInfoModal"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div
                class="relative top-8 mx-auto p-0 border w-full max-w-3xl shadow-2xl rounded-2xl bg-white overflow-hidden mb-8">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div
                                class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-5">
                                <i class="fas fa-id-card text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white mb-1">Personal Information</h3>
                                <p class="text-blue-100 text-base">Complete profile details</p>
                            </div>
                        </div>
                        <button onclick="closePersonalInfoModal()"
                            class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-times text-2xl bold-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-8 py-6">
                    <!-- Profile Image Section -->
                    <div class="flex justify-center mb-7">
                        <?php if (!empty($userData['profile_image'])): ?>
                            <img src="/community-health-tracker/<?= htmlspecialchars($userData['profile_image']) ?>"
                                alt="Profile"
                                class="w-36 h-36 rounded-full object-cover border-5 border-blue-200 shadow-lg">
                        <?php else: ?>
                            <div
                                class="w-36 h-36 rounded-full bg-blue-100 flex items-center justify-center border-5 border-blue-200 shadow-lg">
                                <i class="fas fa-user text-7xl text-blue-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Full Name -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Full Name</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['full_name'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Username -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user-tag text-purple-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Username</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['username'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-envelope text-red-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Email Address</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['email'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Contact -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-phone text-green-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Contact Number</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['contact'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Date of Birth -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-birthday-cake text-yellow-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Date of Birth</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= $userData['date_of_birth'] ? date('F d, Y', strtotime($userData['date_of_birth'])) : 'N/A' ?>
                            </p>
                        </div>

                        <!-- Age -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-calendar text-indigo-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Age</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13"><?= $userData['age'] ?? 'N/A' ?></p>
                        </div>

                        <!-- Gender -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-venus-mars text-pink-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Gender</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= ucfirst($userData['gender'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Civil Status -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-heart text-rose-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Civil Status</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['civil_status'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Sitio -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-map-marker-alt text-teal-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Sitio</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['sitio'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Occupation -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-briefcase text-orange-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Occupation</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= htmlspecialchars($userData['occupation'] ?? 'N/A') ?>
                            </p>
                        </div>

                        <!-- Address (Full Width) -->
                        <?php if (!empty($userData['address'])): ?>
                            <div class="modal-info-item md:col-span-2">
                                <div class="flex items-center mb-2">
                                    <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-home text-cyan-600"></i>
                                    </div>
                                    <span class="text-base font-medium text-gray-600">Address</span>
                                </div>
                                <p class="text-lg font-semibold text-gray-800 ml-13">
                                    <?= htmlspecialchars($userData['address']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Unique Number -->
                        <?php if (!empty($userData['unique_number'])): ?>
                            <div class="modal-info-item">
                                <div class="flex items-center mb-2">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-hashtag text-gray-600"></i>
                                    </div>
                                    <span class="text-base font-medium text-gray-600">Unique ID</span>
                                </div>
                                <p class="text-lg font-semibold text-gray-800 ml-13">
                                    <?= htmlspecialchars($userData['unique_number']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Member Since -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                                <span class="text-base font-medium text-gray-600">Member Since</span>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 ml-13">
                                <?= date('F d, Y', strtotime($userData['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button onclick="closePersonalInfoModal()"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex items-center gap-2 text-base">
                        <i class="fas fa-check"></i>
                        <span>Close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add this to your existing script section
        function adjustAnnouncementsHeight() {
            const container = document.getElementById('announcementsContainer');
            const announcements = container.querySelectorAll('.border-gray-200:not(.opacity-0)');

            if (announcements.length === 0) return;

            // Calculate ideal height for exactly 3 announcements
            const announcementHeight = 96; // Approximate height per announcement
            const spacing = 12; // mb-3 spacing
            const idealHeight = (announcementHeight + spacing) * 3 - spacing;

            container.style.height = idealHeight + 'px';
            container.style.maxHeight = idealHeight + 'px';
            container.style.minHeight = idealHeight + 'px';

            // Show exactly 3 announcements
            announcements.forEach((ann, index) => {
                if (index >= 3) {
                    ann.classList.add('opacity-0', 'h-0', 'overflow-hidden');
                    ann.classList.remove('mb-3');
                } else {
                    ann.classList.remove('opacity-0', 'h-0', 'overflow-hidden');
                    ann.classList.add('mb-3');
                }
            });
        }

        // Call on load and window resize
        document.addEventListener('DOMContentLoaded', adjustAnnouncementsHeight);
        window.addEventListener('resize', adjustAnnouncementsHeight);
    </script>
