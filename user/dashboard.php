<?php
// user/dashboard.php

require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for resident users after 1 hour of inactivity ---
if (isUser()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 3600) { // 1 hour = 3600 seconds
            // Destroy session and redirect to resident landing page
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/index.php');
            exit();
        } else {
            $_SESSION['last_action'] = $now;
        }
    }
}

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
                SELECT a.*, ua.status as user_status, s.full_name as staff_name
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Existing styles remain the same */
        .fixed {
            position: fixed;
        }

        .inset-0 {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        .hidden {
            display: none;
        }

        .z-50 {
            z-index: 50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        /* .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        } */

        .chart-container,
        .chart-container-two {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            width: 100%;
            /* ✅ always fluid */
        }

        /* Mobile optimized */
        @media (max-width: 768px) {
            .chart-container,
            .chart-container-two {
                padding: 16px;
                border-radius: 8px;
            }
        }

        @media (max-width: 640px) {
            .chart-container,
            .chart-container-two {
                padding: 12px;
                border-radius: 8px;
            }
        }


        .tab-active {
            border-bottom: 2px solid #3b82f6;
            color: #2563eb;
        }

        .blue-theme-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .blue-theme-text {
            color: #3b82f6;
        }

        .blue-theme-border {
            border-color: #3b82f6;
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.8rem;
            height: 1.8rem;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0 0.6rem;
            margin-left: 0.5rem;
        }

        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 20px;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* Activity Log Styles */
        .activity-log-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-log-item {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            background: #f8fafc;
            border-left: 4px solid;
            transition: all 0.2s ease;
        }

        .activity-log-item:hover {
            background: #f1f5f9;
            transform: translateX(2px);
        }

        .activity-log-item.login {
            border-left-color: #10b981;
        }

        .activity-log-item.logout {
            border-left-color: #ef4444;
        }

        .activity-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .activity-icon.login {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .activity-icon.logout {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }

        .activity-content {
            flex: 1;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
        }

        .activity-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .activity-time {
            font-size: 12px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
        }

        .activity-details {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
        }

        .activity-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #6b7280;
        }

        .activity-detail i {
            font-size: 11px;
        }

        /* Bold Icons */
        .bold-icon {
            font-weight: 900 !important;
        }

        /* Chart legend items */
        .chart-legend-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            background: #f9fafb;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .chart-legend-item:hover {
            background: #f3f4f6;
        }

        .chart-legend-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            margin-right: 12px;
        }

        /* Stats card icons */
        .stats-icon-container {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        /* Personal Information Styles */
        .personal-info-item {
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
            transition: all 0.2s ease;
        }

        .personal-info-item:hover {
            background: #f3f4f6;
        }

        /* Doctor's Notes Styles */
        .doctor-note-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .doctor-note-item:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Modal Info Item Styles */
        .modal-info-item {
            padding: 16px;
            border-radius: 12px;
            background: #f9fafb;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .modal-info-item:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .ml-13 {
            margin-left: 3.25rem;
        }
    </style>
<div class="bg-gray-100">
    <div class=" px-4 py-6 -mt-24">
        <!-- Dashboard Header -->
        <!-- <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold flex items-center">
                <div class="stats-icon-container bg-blue-100 mr-3">
                    <i class="fas fa-chart-pie text-blue-600 text-xl bold-icon"></i>
                </div>
                Health Analytics Dashboard
            </h1>
           
            <button onclick="openHelpModal()" class="help-icon text-blue-600 hover:text-blue-500 transition">
                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-circle-question text-2xl bold-icon"></i>
                </div>
            </button>
        </div> -->

        <?php if ($error): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 flex items-center">
                <div class="w-8 h-8 bg-yellow-200 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-yellow-600 bold-icon"></i>
                </div>
                <span>Note: <?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-check-circle text-green-600 bold-icon"></i>
                </div>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Tabs - Only Analytics -->
        <!-- <div class="flex border-b border-gray-200 mb-6">
            <a href="?tab=analytics" class="<?= $activeTab === 'analytics' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-4 py-2 font-medium flex items-center">
                <i class="fas fa-chart-pie text-lg mr-2 bold-icon"></i>
                Health Analytics
            </a>
        </div> -->

        <!-- Analytics Tab Content -->
        <div class="tab-content <?= $activeTab === 'analytics' ? 'active' : '' ?>">
            <!-- Health Analytics Dashboard -->
            <div class="space-y-8">
                <!-- Stats Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Consultations Card -->
                    <div class="stats-card">
                        <div class="flex items-center mt-4">
                            <div class="stats-icon-container bg-green-100">
                                <svg width="66" height="66" viewBox="0 0 66 66" fill="none"
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
                                <h3 class="text-2xl font-semibold text-gray-700">Consultations</h3>
                                <p class="text-3xl font-bold text-green-600"><?= $analytics['consultations'] ?></p>
                                <p class="text-sm text-gray-500 mt-1">Consultations Visits</p>
                            </div>
                        </div>
                        <!-- <div class="mt-4">
                            <a href="profile.php" class="text-sm text-green-600 font-medium hover:text-green-800 flex items-center">
                                <i class="fas fa-history mr-2 bold-icon"></i>
                                View consultation history
                            </a>
                        </div> -->
                    </div>

                    <!-- Announcements Card -->
                    <div class="stats-card">
                        <div class="flex items-center mt-4">
                            <div class="stats-icon-container bg-blue-100">
                                <svg width="66" height="66" viewBox="0 0 66 66" fill="none"
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
                                <h3 class="text-2xl font-semibold text-gray-700">Announcements</h3>
                                <p class="text-3xl font-bold text-blue-600"><?= $analytics['announcements'] ?></p>
                                <p class="text-sm text-gray-500 mt-1">Active announcements</p>
                            </div>
                        </div>
                        <!-- <div class="mt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-green-600 font-medium flex items-center">
                                    <i class="fas fa-check-circle mr-2 bold-icon"></i>
                                    <?= $announcementStats['accepted'] ?> Accepted
                                </span>
                                <span class="text-yellow-600 font-medium flex items-center">
                                    <i class="fas fa-clock mr-2 bold-icon"></i>
                                    <?= $announcementStats['pending'] ?> Pending
                                </span>
                            </div>
                        </div> -->
                    </div>

                    <!-- Lab Results Card -->
                    <div class="stats-card">
                        <div class="flex items-center mt-4">
                            <div class="stats-icon-container bg-purple-100">
                                <i class="fas fa-vial text-purple-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-semibold text-gray-700">Lab Result</h3>
                                <p class="text-3xl font-bold text-purple-600">
                                    <?= isset($labResultsCount) ? $labResultsCount : 0 ?>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">Available results</p>
                                <!-- Lab Result count is now always based on announcements for this user -->
                            </div>
                        </div>
                    </div>

                    
                </div>

                <!-- Charts and Activity Log Section -->
                <div class="flex flex-col md:flex-row gap-4 md:gap-6">
                    <!-- LEFT: 40% - Recent Consultations -->
                    <div class="chart-container w-full md:flex-[0_0_40%]">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-file-medical-alt text-green-600 text-base md:text-lg"></i>
                                </div>
                                <h3 class="text-lg md:text-xl font-600 text-gray-800">
                                    Recent Consultations
                                </h3>
                            </div>
                            <a href="health_records.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">View All</a>
                        </div>

                        <div class="space-y-4 h-auto">
                            <?php if (empty($consultationNotes)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-file-medical-alt text-4xl text-gray-300"></i>
                                    <p class="mt-2 text-gray-500">No consultation notes found.</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                $recentNotes = array_slice($consultationNotes, 0, 4);
                                foreach ($recentNotes as $note): ?>
                                    <div class="doctor-note-item p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($note['patient_name']) ?></p>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($note['doctor_name']) ?></p>
                                            </div>
                                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= date('M d, Y', strtotime($note['consultation_date'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RIGHT: 60% - Announcements and Lab Results -->
                    <div class="chart-container-two w-full md:flex-[0_0_58.5%] py-4 md:py-6">
                        <!-- Lab Results Section (if any) -->
                        <?php if (!empty($labAnnouncements)): ?>
                        <div class="mb-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-flask text-green-600 text-lg md:text-xl"></i>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-600 text-gray-800">
                                        Laboratory Results
                                    </h3>
                                </div>
                                <a href="announcements.php" class="text-sm font-medium text-green-600 hover:text-green-800">View All</a>
                            </div>

                            <div class="activity-log-container custom-scrollbar h-auto space-y-3 mb-6">
                                <?php 
                                $recentLabResults = array_slice($labAnnouncements, 0, 2);
                                foreach ($recentLabResults as $labResult): 
                                ?>
                                    <div class="border-2 border-green-200 bg-green-50 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-flask text-green-600"></i>
                                                <h4 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($labResult['title']) ?></h4>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded-full <?= 
                                                $labResult['priority'] === 'high' ? 'bg-red-100 text-red-800' : 
                                                ($labResult['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') 
                                            ?>">
                                                <?= ucfirst($labResult['priority']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-green-700 mb-2">
                                            <i class="fas fa-user-md"></i> <?= htmlspecialchars($labResult['staff_name'] ?? 'Medical Staff') ?> • 
                                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($labResult['post_date'])) ?>
                                        </p>
                                        <p class="text-sm text-gray-700 mb-3"><?= substr(htmlspecialchars($labResult['message']), 0, 80) ?>...</p>
                                        <div class="flex gap-2 text-xs">
                                            <span class="px-2 py-1 rounded bg-green-200 text-green-800 font-semibold">
                                                <i class="fas fa-flask"></i> Laboratory Result
                                            </span>
                                            <?php if ($labResult['user_status']): ?>
                                                <span class="px-2 py-1 rounded bg-<?= $labResult['user_status'] === 'accepted' ? 'green' : 'gray' ?>-100 text-<?= $labResult['user_status'] === 'accepted' ? 'green' : 'gray' ?>-800">
                                                    <i class="fas fa-<?= $labResult['user_status'] === 'accepted' ? 'check-circle' : 'times-circle' ?>"></i> <?= ucfirst($labResult['user_status']) ?>
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
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4 mb-4 md:mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-bullhorn text-blue-600 text-lg md:text-xl"></i>
                                </div>
                                <h3 class="text-lg md:text-xl font-600 text-gray-800">
                                    General Announcements
                                </h3>
                            </div>
                            <a href="announcements.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">View All</a>
                        </div>

                        <div id="announcementsContainer" class="activity-log-container custom-scrollbar h-auto space-y-3">
                            <?php 
                            // Filter out lab results from general announcements
                            $generalAnnouncements = array_filter($announcements ?? [], function($ann) {
                                return !isset($ann['announcement_category']) || $ann['announcement_category'] !== 'lab_result';
                            });
                            $recentAnnouncements = array_slice($generalAnnouncements, 0, 3);
                            if (empty($recentAnnouncements)): 
                            ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-bullhorn text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-gray-500">No announcements yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentAnnouncements as $announcement): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($announcement['title']) ?></h4>
                                            <span class="text-xs px-2 py-1 rounded-full <?= 
                                                $announcement['priority'] === 'high' ? 'bg-red-100 text-red-800' : 
                                                ($announcement['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') 
                                            ?>">
                                                <?= ucfirst($announcement['priority']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-2"><?= htmlspecialchars($announcement['staff_name'] ?? 'Community Staff') ?> • <?= date('M d, Y', strtotime($announcement['post_date'])) ?></p>
                                        <p class="text-sm text-gray-700 mb-3"><?= substr(htmlspecialchars($announcement['message']), 0, 80) ?>...</p>
                                        <div class="flex gap-2 text-xs">
                                            <?php if ($announcement['user_status']): ?>
                                                <span class="px-2 py-1 rounded bg-<?= $announcement['user_status'] === 'accepted' ? 'green' : 'gray' ?>-100 text-<?= $announcement['user_status'] === 'accepted' ? 'green' : 'gray' ?>-800">
                                                    <i class="fas fa-<?= $announcement['user_status'] === 'accepted' ? 'check-circle' : 'times-circle' ?>"></i> <?= ucfirst($announcement['user_status']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-clock"></i> Pending Response
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                        <h3 class="text-2xl font-bold text-white mb-1">Analytics Dashboard Guide</h3>
                        <p class="text-blue-100 text-sm">Understanding your health analytics</p>
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
                            <h4 class="font-semibold text-gray-800 text-lg mb-2">Health Records</h4>
                            <p class="text-gray-600">Number of patient profiles linked to your account from the sitio1_patients table.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-bullhorn text-blue-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-lg mb-2">Announcements</h4>
                            <p class="text-gray-600">Active announcements targeted to you. Track your responses and pending items.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-medical-alt text-green-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-lg mb-2">Consultations</h4>
                            <p class="text-gray-600">Total consultation notes from all linked patient profiles in the consultation_notes table.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-history text-purple-600 text-lg bold-icon"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 text-lg mb-2">Activity Log</h4>
                            <p class="text-gray-600">Track your login and logout activities with timestamps and IP addresses.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                <button type="button" onclick="closeHelpModal()" class="px-7 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex items-center">
                    <i class="fas fa-check mr-2 bold-icon"></i>
                    Got it, thanks!
                </button>
            </div>
        </div>
    </div> -->

        <script>
            
            // Personal Information Modal functions
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
        <div id="personalInfoModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-8 mx-auto p-0 border w-full max-w-3xl shadow-2xl rounded-2xl bg-white overflow-hidden mb-8">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-id-card text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white mb-1">Personal Information</h3>
                                <p class="text-blue-100 text-sm">Complete profile details</p>
                            </div>
                        </div>
                        <button onclick="closePersonalInfoModal()" class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-times text-2xl bold-icon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="px-8 py-6">
                    <!-- Profile Image Section -->
                    <div class="flex justify-center mb-6">
                        <?php if (!empty($userData['profile_image'])): ?>
                            <img src="/community-health-tracker/<?= htmlspecialchars($userData['profile_image']) ?>" 
                                 alt="Profile" 
                                 class="w-32 h-32 rounded-full object-cover border-4 border-blue-200 shadow-lg">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center border-4 border-blue-200 shadow-lg">
                                <i class="fas fa-user text-6xl text-blue-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Full Name</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['full_name'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Username -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-user-tag text-purple-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Username</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['username'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Email -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-envelope text-red-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Email Address</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['email'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Contact -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-phone text-green-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Contact Number</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['contact'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Date of Birth -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-birthday-cake text-yellow-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Date of Birth</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= $userData['date_of_birth'] ? date('F d, Y', strtotime($userData['date_of_birth'])) : 'N/A' ?></p>
                        </div>
                        
                        <!-- Age -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-calendar text-indigo-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Age</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= $userData['age'] ?? 'N/A' ?></p>
                        </div>
                        
                        <!-- Gender -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-venus-mars text-pink-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Gender</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= ucfirst($userData['gender'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Civil Status -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-heart text-rose-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Civil Status</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['civil_status'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Sitio -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-map-marker-alt text-teal-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Sitio</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['sitio'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Occupation -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-briefcase text-orange-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Occupation</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['occupation'] ?? 'N/A') ?></p>
                        </div>
                        
                        <!-- Address (Full Width) -->
                        <?php if (!empty($userData['address'])): ?>
                        <div class="modal-info-item md:col-span-2">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-home text-cyan-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Address</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['address']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Unique Number -->
                        <?php if (!empty($userData['unique_number'])): ?>
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-hashtag text-gray-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Unique ID</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= htmlspecialchars($userData['unique_number']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Member Since -->
                        <div class="modal-info-item">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-600">Member Since</span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 ml-13"><?= date('F d, Y', strtotime($userData['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button onclick="closePersonalInfoModal()" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        <span>Close</span>
                    </button>
                </div>
            </div>
        </div>
</div>