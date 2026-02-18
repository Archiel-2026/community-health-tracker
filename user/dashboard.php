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
        border-radius: 16px;
        padding: 30px 40px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        min-height: 250px;
        /* margin-bottom: 8px; */
        display: flex;
        flex-direction: column;
        /* justify-content: center; */
    }

    /* .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        } */

    .chart-container,
    .chart-container-two {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        width: 100%;
        min-height: 400px;
        margin-bottom: 8px;
        display: flex;
        flex-direction: column;
    }

    /* Ensure consistent height for chart containers */
    .chart-content-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Mobile optimized */
    @media (max-width: 768px) {

        .chart-container,
        .chart-container-two {
            padding: 20px;
            border-radius: 12px;
            min-height: 350px;
        }

        .stats-card {
            min-height: 140px;
            padding: 20px;
        }
    }

    @media (max-width: 640px) {

        .chart-container,
        .chart-container-two {
            padding: 16px;
            border-radius: 12px;
            min-height: 320px;
        }

        .stats-card {
            min-height: 130px;
            padding: 16px;
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
        min-width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        font-size: 1.1rem;
        font-weight: 700;
        padding: 0 0.8rem;
        margin-left: 0.7rem;
    }

    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 18px;
        padding: 24px;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
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
        max-height: 260px;
        overflow-y: auto;
        flex: 1;
    }

    .activity-log-item {
        display: flex;
        align-items: flex-start;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 12px;
        background: #f8fafc;
        border-left: 5px solid;
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
        width: 44px;
        height: 44px;
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
        margin-bottom: 6px;
    }

    .activity-title {
        font-weight: 600;
        color: #1f2937;
        font-size: 16px;
    }

    .activity-time {
        font-size: 13px;
        color: #6b7280;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 14px;
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
        font-size: 13px;
        color: #6b7280;
    }

    .activity-detail i {
        font-size: 12px;
    }

    /* Bold Icons */
    .bold-icon {
        font-weight: 900 !important;
    }

    /* Chart legend items */
    .chart-legend-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 10px;
        background: #f9fafb;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }

    .chart-legend-item:hover {
        background: #f3f4f6;
    }

    .chart-legend-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        margin-right: 14px;
    }

    /* Stats card icons */
    .stats-icon-container {
        width: 68px;
        height: 68px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .bg-first-card {
        background: rgba(22, 163, 74, 0.3);
    }

    .bg-second-card {
        background: rgba(59, 130, 246, 0.3);
    }

    .bg-third-card {
        background: rgba(147, 51, 234, 0.3);
    }

    .bg-lab-result {
        background-color: #16A34A;
    }

    /* Personal Information Styles */
    .personal-info-item {
        padding: 16px;
        border-radius: 10px;
        background: #f9fafb;
        transition: all 0.2s ease;
        margin-bottom: 4px;
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
        margin-bottom: 12px;
    }

    .doctor-note-item:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    /* Modal Info Item Styles */
    .modal-info-item {
        padding: 20px;
        border-radius: 12px;
        background: #f9fafb;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
        margin-bottom: 6px;
    }

    .modal-info-item:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .ml-13 {
        margin-left: 3.75rem;
    }

    /* Consistent spacing utilities */
    .space-y-consistent {
        margin-top: 1.5rem;
    }

    .space-y-consistent>*+* {
        margin-top: 1.5rem;
    }

    .mb-consistent {
        margin-bottom: 1.5rem;
    }

    /* Ensure all content is visible with top padding */
    .content-visible {
        /* padding-top: 25px; */
        min-height: 100vh;
    }

    /* Consistent height for stats cards */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* Add this to your existing style section */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Adjust the announcements container height */
    #announcementsContainer {
        height: 288px;
        /* Exactly fits 3 announcements at ~96px each */
        max-height: 288px;
        min-height: 288px;
    }

    /* Ensure announcement items have consistent height */
    #announcementsContainer>div:not(.text-center) {
        min-height: 96px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #announcementsContainer {
            height: 264px;
            /* Slightly smaller on mobile */
            max-height: 264px;
            min-height: 264px;
        }
    }

    /* Add smooth scrolling */
    .custom-scrollbar {
        scroll-behavior: smooth;
        scrollbar-width: thin;
    }

    /* Better scrollbar styling */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>
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
                                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M22 38.5C19.7951 38.5 17.9159 37.7416 16.3625 36.2248C14.8091 34.7081 14.0323 32.857 14.0323 30.6717V14.0323C13.189 14.0323 12.4728 13.7439 11.8837 13.167C11.2946 12.5889 11 11.8782 11 11.0348V8.53233C11 7.69878 11.297 6.985 11.891 6.391C12.485 5.797 13.1988 5.5 14.0323 5.5H29.9328C30.7762 5.5 31.4985 5.797 32.0998 6.391C32.6999 6.985 33 7.69878 33 8.53233V11.0348C33 11.8782 32.6993 12.5889 32.098 13.167C31.4979 13.7439 30.7762 14.0323 29.9328 14.0323V31.2015C29.7923 33.2671 28.9575 35.0002 27.4285 36.4008C25.8995 37.8015 24.09 38.5012 22 38.5ZM26.3212 34.9397C27.5067 33.7871 28.0995 32.3651 28.0995 30.6735V29.2985H22.2457V27.4652H28.0995V22.2475H22.2457V20.4142H28.0995V14.0323H15.8657V30.6735C15.8657 32.3651 16.4646 33.7871 17.6623 34.9397C18.8626 36.091 20.3091 36.6667 22.0018 36.6667C23.6946 36.6667 25.1344 36.091 26.3212 34.9397Z"
                                            fill="#9333EA" />
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
                                    $recentNotes = array_slice($consultationNotes, 0, 4);
                                    foreach ($recentNotes as $note): ?>
                                        <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white">
                                            <div class="flex justify-between items-start w-full">
                                                <span
                                                    class="px-4 py-2 rounded bg-second-card text-[#2563EB] font-semibold text-base">Consultation
                                                    Note</span>
                                                <div class="flex flex-col items-start">
                                                    <span class="text-sm text-gray-600 font-semibold mb-0.5">Consultation Date
                                                        :</span>
                                                    <span
                                                        class="inline-block px-3 py-2 rounded bg-gray-200 text-gray-500 text-base font-medium"
                                                        style="margin-top:2px;"><?= date('F d, Y', strtotime($note['consultation_date'])) ?></span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <span class="block text-base text-gray-400 font-medium mb-0.5">Consulting Doctor
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
                            <div>
                                <div
                                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-5 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 md:w-12 md:h-12 bg-second-card rounded-lg flex items-center justify-center">
                                            <svg class="w-10 h-10" viewBox="0 0 30 30" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M26.782 10.1551L6.15 3.82695C5.87075 3.74551 5.57638 3.73015 5.29017 3.78209C5.00396 3.83404 4.73376 3.95186 4.50094 4.12625C4.26812 4.30064 4.07907 4.5268 3.94873 4.78686C3.8184 5.04691 3.75036 5.33372 3.75 5.62461V22.4996C3.75 22.9969 3.94754 23.4738 4.29917 23.8254C4.65081 24.1771 5.12772 24.3746 5.625 24.3746C5.8043 24.3747 5.98268 24.349 6.15469 24.2984L15.9375 21.2961V22.4996C15.9375 22.9969 16.135 23.4738 16.4867 23.8254C16.8383 24.1771 17.3152 24.3746 17.8125 24.3746H21.5625C22.0598 24.3746 22.5367 24.1771 22.8883 23.8254C23.24 23.4738 23.4375 22.9969 23.4375 22.4996V18.9957L26.782 17.9703C27.1691 17.854 27.5086 17.6164 27.7504 17.2925C27.9922 16.9687 28.1235 16.5757 28.125 16.1715V11.9527C28.1233 11.5488 27.9918 11.156 27.75 10.8324C27.5083 10.5088 27.1689 10.2713 26.782 10.1551ZM15.9375 19.3355L5.625 22.4996V5.62461L15.9375 8.78867V19.3355ZM21.5625 22.4996H17.8125V20.7207L21.5625 19.5699V22.4996ZM26.25 16.1715H26.2371L17.8125 18.759V9.36523L26.2371 11.9434H26.25V16.1621V16.1715Z"
                                                    fill="#2563EB" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl md:text-2xl font-600 text-gray-800">
                                            All Announcements
                                        </h3>
                                    </div>
                                    <a href="announcements.php?tab=announcements"
                                        class="text-lg font-medium text-blue-600 hover:text-blue-800" target="_blank"
                                        rel="noopener">View All</a>
                                </div>

                                <div id="announcementsContainer" class="h-72 overflow-y-auto custom-scrollbar">
                                    <?php
                                    // Filter out lab results from general announcements
                                    $generalAnnouncements = array_filter($announcements ?? [], function ($ann) {
                                        return !isset($ann['announcement_category']) || $ann['announcement_category'] !== 'lab_result';
                                    });

                                    if (empty($generalAnnouncements)):
                                        ?>
                                        <div class="text-center py-10 flex flex-col justify-center items-center h-full">
                                            <svg width="70" height="70" class="mb-4" viewBox="0 0 70 70" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M67.8125 32.8131C67.8089 29.3332 66.4249 25.9969 63.9643 23.5363C61.5037 21.0757 58.1674 19.6917 54.6875 19.6881H43.8047C43.009 19.6416 29.143 18.6654 15.9387 7.5912C15.301 7.05568 14.5238 6.71325 13.6983 6.60414C12.8728 6.49502 12.0333 6.62375 11.2784 6.9752C10.5235 7.32666 9.88464 7.88624 9.43676 8.58821C8.98888 9.29018 8.75063 10.1054 8.75 10.9381V54.6881C8.75011 55.521 8.98799 56.3366 9.43566 57.039C9.88334 57.7413 10.5222 58.3013 11.2772 58.6531C12.0322 59.0049 12.8719 59.1339 13.6976 59.0248C14.5234 58.9158 15.3009 58.5734 15.9387 58.0377C26.2664 49.3752 36.9934 46.8924 41.5625 46.1978V54.8713C41.5616 55.5922 41.7388 56.3022 42.0785 56.9381C42.4181 57.574 42.9097 58.1161 43.5094 58.5162L46.5172 60.5205C47.0986 60.9085 47.7644 61.1516 48.4591 61.2294C49.1537 61.3072 49.8568 61.2174 50.5097 60.9676C51.1625 60.7178 51.7459 60.3152 52.2111 59.7935C52.6763 59.2719 53.0098 58.6464 53.1836 57.9693L56.402 45.8396C59.5594 45.4195 62.4569 43.8671 64.5557 41.4711C66.6545 39.075 67.8118 35.9984 67.8125 32.8131ZM13.125 54.6689V10.9381C24.8309 20.7572 36.8129 23.2428 41.5625 23.8553V41.76C36.8184 42.3834 24.8391 44.8635 13.125 54.6689ZM48.9453 56.8564V56.8865L45.9375 54.8822V45.9381H51.8438L48.9453 56.8564ZM54.6875 41.5631H45.9375V24.0631H54.6875C57.0081 24.0631 59.2337 24.985 60.8747 26.6259C62.5156 28.2668 63.4375 30.4924 63.4375 32.8131C63.4375 35.1337 62.5156 37.3593 60.8747 39.0003C59.2337 40.6412 57.0081 41.5631 54.6875 41.5631Z"
                                                    fill="black" fill-opacity="0.3" />
                                            </svg>
                                            <h3 class="text-gray-500 font-semibold text-xl mb-4">No announcements posted yet</h3>
                                            <p class="text-gray-500 text-lg">No announcements are currently available.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                        $recentAnnouncements = array_slice($generalAnnouncements, 0, 5); // Get 5 announcements but only show 3 at a time
                                    
                                        foreach ($recentAnnouncements as $index => $announcement):
                                            $isPriorityHigh = $announcement['priority'] === 'high';
                                            $isPriorityMedium = $announcement['priority'] === 'medium';
                                            $isAccepted = $announcement['user_status'] === 'accepted';
                                            $isDismissed = $announcement['user_status'] === 'dismissed';
                                            // Badge logic for announcement type/audience
                                            $badge = '';
                                            $priorityBadge = '';
                                            $priority = strtolower($announcement['priority']);
                                            $greenColor = 'style="color: #FFFFFF;"';
                                            $highBg = 'style="background-color: #e6bcbc; color: #8b2323;"';
                                            $mediumBg = 'style="background-color: #FD88024D; color: #FD8802"';
                                            if ((isset($announcement['announcement_type']) && $announcement['announcement_type'] === 'lab_result') || (isset($announcement['announcement_category']) && $announcement['announcement_category'] === 'lab_result')) {
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
                                            // Prepare staff display for new layout
                                            $staffPosition = !empty($announcement['staff_position']) ? htmlspecialchars($announcement['staff_position']) : '';
                                            $staffName = !empty($announcement['staff_name']) ? htmlspecialchars($announcement['staff_name']) : '';
                                            ?>
                                            <div class="border border-gray-200 rounded-lg p-4  mb-3 announcement-item <?= $index >= 3 ? 'opacity-0 h-0 overflow-hidden' : '' ?>"
                                                data-announcement-id="<?= htmlspecialchars($announcement['id']) ?>">
                                                <div class="flex justify-between items-start w-full">
                                                    <div class="flex gap-2 items-center">
                                                        <?= $badge ?>         <?= $priorityBadge ?>
                                                    </div>
                                                    <div class="flex flex-col items-start">
                                                        <span class="text-sm text-gray-600 font-semibold mb-0.5">Date Posted
                                                            :</span>
                                                        <span
                                                            class="inline-block px-4 py-2 rounded bg-gray-200 text-gray-500 text-base font-semibold"
                                                            style="margin-top:2px;"><?= date('F d, Y', strtotime($announcement['post_date'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 mb-0.5 mt-1">
                                                    <span class="text-base text-gray-400 font-medium">Posted By :</span>
                                                    <?php if ($staffPosition): ?>
                                                        <span
                                                            class="inline-block items-center py-0.5 rounded font-medium text-base ml-0.5 align-middle"
                                                            style="margin-left:4px;"><?= $staffPosition ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($staffName): ?>
                                                    <div class="text-lg text-gray-800 font-medium mt-0.5 mb-0"><?= $staffName ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (count($recentAnnouncements) > 3): ?>
                                            <div class="text-center py-3">
                                                <span class="text-blue-600 text-sm font-medium">
                                                    +<?= count($recentAnnouncements) - 3 ?> more
                                                    announcement<?= (count($recentAnnouncements) - 3) > 1 ? 's' : '' ?>
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