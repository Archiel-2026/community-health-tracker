<?php
require_once __DIR__ . '/../includes/auth.php';
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
$error = '';
$success = '';

// Handle announcement response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_to_announcement'])) {
    $announcementId = $_POST['announcement_id'];
    $status = $_POST['respond_to_announcement'];

    // Validate status
    if (!in_array($status, ['accepted', 'dismissed'])) {
        $error = 'Invalid response status';
    } else {
        try {
            // Check if announcement is active and targeted to this user
            $stmt = $pdo->prepare("
                SELECT a.id 
                FROM sitio1_announcements a
                LEFT JOIN announcement_targets at ON a.id = at.announcement_id
                WHERE a.id = ? 
                AND a.status = 'active'
                AND (a.audience_type = 'public' OR at.user_id = ?)
            ");
            $stmt->execute([$announcementId, $userId]);

            if (!$stmt->fetch()) {
                $error = 'This announcement is no longer available';
            } else {
                // Check if response already exists
                $stmt = $pdo->prepare("SELECT id FROM user_announcements WHERE user_id = ? AND announcement_id = ?");
                $stmt->execute([$userId, $announcementId]);

                if ($stmt->fetch()) {
                    // Update existing response
                    $stmt = $pdo->prepare("UPDATE user_announcements SET status = ?, response_date = NOW() WHERE user_id = ? AND announcement_id = ?");
                    $stmt->execute([$status, $userId, $announcementId]);
                } else {
                    // Insert new response
                    $stmt = $pdo->prepare("INSERT INTO user_announcements (user_id, announcement_id, status, response_date) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$userId, $announcementId, $status]);
                }

                $success = 'Response recorded successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error recording response: ' . $e->getMessage();
        }
    }
}

// Get announcements targeted to this user
$announcements = [];
$labResults = [];
$basicAnnouncements = [];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, ua.status as user_status, ua.response_date,
               s.full_name as staff_name
        FROM sitio1_announcements a
        LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
        LEFT JOIN sitio1_staff s ON a.staff_id = s.id
        WHERE a.status = 'active'
        AND (a.audience_type = 'public' OR a.id IN (
            SELECT announcement_id FROM announcement_targets WHERE user_id = ?
        ))
        ORDER BY a.priority DESC, a.post_date DESC
    ");
    $stmt->execute([$userId, $userId]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate announcements by category
    foreach ($announcements as $announcement) {
        if (isset($announcement['announcement_type']) && $announcement['announcement_type'] === 'lab_result') {
            $labResults[] = $announcement;
        } else {
            $basicAnnouncements[] = $announcement;
        }
    }
} catch (PDOException $e) {
    $error = 'Error fetching announcements: ' . $e->getMessage();
}

// Calculate statistics
$respondedCount = count(array_filter($announcements, function ($a) {
    return !empty($a['user_status']);
}));
$acceptedCount = count(array_filter($announcements, function ($a) {
    return $a['user_status'] === 'accepted';
}));
$dismissedCount = count(array_filter($announcements, function ($a) {
    return $a['user_status'] === 'dismissed';
}));
$pendingCount = count($announcements) - $respondedCount;

// Separate counts for lab results and basic announcements
$labResultsCount = count($labResults);
$labResultsPending = count(array_filter($labResults, function ($a) {
    return empty($a['user_status']);
}));
$basicAnnouncementsCount = count($basicAnnouncements);
$basicAnnouncementsPending = count(array_filter($basicAnnouncements, function ($a) {
    return empty($a['user_status']);
}));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Luz - Community Announcements</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <style>
        * {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .icon-xs {
            font-size: 0.75rem;
        }

        .icon-sm {
            font-size: 0.875rem;
        }

        .icon-base {
            font-size: 1rem;
        }

        .icon-lg {
            font-size: 1.125rem;
        }

        .icon-xl {
            font-size: 1.25rem;
        }

        .icon-2xl {
            font-size: 1.5rem;
        }

        .icon-3xl {
            font-size: 1.875rem;
        }

        .icon-4xl {
            font-size: 2.25rem;
        }

        body {
            background: #f3f4f6;
            color: #1a202c;
        }

        /* Typography - Match dashboard style */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 600;
            color: #1f2937;
            letter-spacing: -0.025em;
        }

        p,
        span,
        a {
            color: #4b5563;
            line-height: 1.6;
        }

        /* Fix header text color overwrites */
        nav.text-white,
        nav.text-white a,
        nav.text-white span,
        nav.text-white p,
        nav.text-white i {
            color: #ffffff !important;
        }

        /* Ensure good contrast for all text */
        .text-gray-600 {
            color: #4b5563 !important;
        }

        .text-gray-700 {
            color: #374151 !important;
        }

        .text-gray-900 {
            color: #111827 !important;
        }

        .text-label {
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            color: #6b7280;
            line-height: 1.4;
        }

        /* Card Shadows - Match dashboard style */
        .card-shadow {
            /* background: white; */
            border-radius: 12px;
            /* box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); */
            /* border: 1px solid #e5e7eb; */
            transition: all 0.2s ease;
        }

        /* Tab Styling - Original pill-style buttons */
        .tab-header {
            position: relative;
            padding: 0.875rem 1.75rem;
            border: none;
            background-color: rgb(187, 216, 242);
            border-radius: 4px;
            color: #4e90c6;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            letter-spacing: 0.3px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tab-header.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white !important;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .tab-header:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .tab-header.active:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .tab-header span {
            color: inherit;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .tab-content.active {
            display: block;
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

        /* Tab Navigation - Prevent horizontal scrolling */
        .tab-nav-container {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid #e1e1e1;
            margin-left: 2em;
            margin-right: 2em;
            /* background: white; */
            overflow: hidden;
            gap: 1.5rem;
        }

        @media (min-width: 640px) {
            .tab-nav-container {
                padding: 2rem 0;
                gap: 1.75rem;
            }
        }

        /* Tab Content - Allow scrolling only for content */
        .tab-content-wrapper {
            padding: 1.9rem;
            /* background: white; */
            /* overflow-y: auto; */
            max-height: calc(100vh - 100px);
        }

        @media (max-width: 640px) {
            .tab-content-wrapper {
                padding: 1rem;
                overflow-y: auto;
                max-height: calc(100vh - 350px);
            }
        }

        /* Page Title & Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            display: flex;
            /* align-items: center; */
            min-height: 120px;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: 0.025em;
            /* text-transform: uppercase; */
        }

        .stat-pending {
            font-size: 0.875rem;
            opacity: 0.3;
            font-weight: 600;
        }

        /* Announcement Items - Match health records style */
        .announcement-item {
            background: white;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 1.4rem;
            /* margin-bottom: 1.5rem; */
            transition: all 0.2s;
        }

        /* Lab Result Highlighting */
        .announcement-item.lab-result-highlight {
            background: linear-gradient(to right, #F0FDF4, #FFFFFF);
            border: 2px solid #10B981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
        }

        .announcement-item.lab-result-highlight:hover {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .announcement-item {
                padding: 1.25rem;
                margin-bottom: 1rem;
            }
        }

        /* .announcement-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #d1d5db;
        } */

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            /* margin-bottom: 1rem; */
            gap: 1rem;
        }

        .announcement-title {
            font-weight: 600;
            color: #111827;
            font-size: 1.25rem;
            /* margin-bottom: 0.5rem; */
        }

        .announcement-meta {
            font-size: 0.8125rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-meta span {
            display: flex;
            align-items: center;
            /* gap: 0.5rem; */
        }

        .announcement-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            /* margin-bottom: 1rem; */
        }

        /* Badge Styling */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            /* border: 1px solid; */
        }

        .badge-high {
            background: #ecb8b8;
            color: #991B1B;
            border-color: #FECACA;
        }

        .badge-medium {
            background: #FEF3C7;
            color: #92400E;
            border-color: #FDE68A;
        }

        .badge-normal {
            background: #DBEAFE;
            color: #0C4A6E;
            border-color: #BAE6FD;
        }

        .badge-lab-result {
            background: #DBEAFE;
            color: #0369A1;
            border-color: #7DD3FC;
        }

        .badge-simple {
            background: #d4e8f2;
            color: #0284C7;
            /* border-color: #BFDBFE; */
        }

        .badge-accepted {
            background: #D1FAE5;
            color: #10B981;
            border-color: #9abaab;
        }

        .badge-dismissed {
            background: #F3F4F6;
            color: #374151;
            border-color: #E5E7EB;
        }

        .badge-pending {
            background: #FEF3C7;
            color: #92400E;
            border-color: #FDE68A;
        }

        /* Announcement Content */
        .announcement-content {
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Image Preview */
        .image-preview {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
            max-height: 200px;
            cursor: pointer;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-preview:hover img {
            transform: scale(1.05);
        }

        /* Action Buttons */
        .announcement-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.2rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 44px;
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-response {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-accept {
            background: white;
            color: #10B981;
            border-color: #10B981;
        }

        .btn-accept:hover {
            background: #D1FAE5;
            transform: translateY(-2px);
        }

        .btn-dismiss {
            background: white;
            color: #6b7280;
            border-color: #e5e7eb;
        }

        .btn-dismiss:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 12px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            line-height: 1;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #111827;
            transform: rotate(90deg);
        }

        .modal-close:active {
            transform: scale(0.95);
        }

        .modal-body {
            padding: 2rem;
        }

        /* Empty State */
        .empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #9ca3af;
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }

        .empty-state-text {
            font-size: 1rem;
            color: #6b7280;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Custom Scrollbar */
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

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Mobile Responsive */
        @media (max-width: 640px) {
            .page-title {
                font-size: 1.5rem !important;
            }

            .stat-value {
                font-size: 1.5rem !important;
            }

            .tab-header {
                padding: 0.65rem 0.85rem !important;
                font-size: 0.8125rem !important;
                font-weight: 700 !important;
                min-height: 40px !important;
                flex: 1 1 auto;
            }

            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .announcement-actions {
                width: 100%;
            }

            .btn-response {
                flex-direction: column;
                min-height: auto;
            }

            .modal {
                width: 95%;
                margin: 0.5rem;
            }

            .modal-header {
                padding: 1.25rem 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }
        }

        @media (min-width: 641px) and (max-width: 1023px) {
            .page-title {
                font-size: 1.75rem !important;
            }

            .stat-value {
                font-size: 2.25rem !important;
            }

            .tab-header {
                padding: 0.75rem 1.5rem !important;
                font-size: 0.95rem !important;
                font-weight: 500 !important;
                min-height: 44px !important;
            }
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    </style>



</head>

<body class="h-full bg-gray-100">
    <div class="-mt-24">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center mt-6">
                <div class="w-8 h-8 bg-red-200 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                </div>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Content Tabs -->
        <div class="card-shadow overflow-hidden mb-10 mt-24">
            <!-- Tab Navigation -->
            <div class="tab-nav-container">
                <button class="tab-header <?= $activeTab === 'announcements' ? 'active' : '' ?>"
                    data-tab="announcements">
                    <span>All Announcements</span>
                </button>
                <button class="tab-header <?= $activeTab === 'stats' ? 'active' : '' ?>" data-tab="stats">
                    <span>Response Stats</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-wrapper custom-scrollbar">
                <!-- Announcements Tab -->
                <div id="announcements" class="tab-content <?= $activeTab === 'announcements' ? 'active' : 'hidden' ?>">
                    <!-- Stats Grid -->
                    <div class="flex flex-col md:flex-row gap-8 w-full">
                        <div class="stat-card flex-1 flex-col">
                            <div class="flex justify-between items-center w-full">
                                <div class="stat-value"><?= count($announcements) ?></div>
                                <div class="stat-icon">
                                    <svg width="42" height="42" viewBox="0 0 42 42" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M20.8333 33.3333C24.3056 33.3333 27.2569 32.1181 29.6875 29.6875C32.1181 27.2569 33.3333 24.3056 33.3333 20.8333C33.3333 17.3611 32.1181 14.4097 29.6875 11.9792C27.2569 9.54861 24.3056 8.33333 20.8333 8.33333C17.3611 8.33333 14.4097 9.54861 11.9792 11.9792C9.54861 14.4097 8.33334 17.3611 8.33334 20.8333C8.33334 24.3056 9.54861 27.2569 11.9792 29.6875C14.4097 32.1181 17.3611 33.3333 20.8333 33.3333ZM14.5833 22.9167V18.75H27.0833V22.9167H14.5833ZM20.8333 41.6667C17.9514 41.6667 15.2431 41.1194 12.7083 40.025C10.1736 38.9306 7.96875 37.4465 6.09375 35.5729C4.21875 33.6993 2.73472 31.4944 1.64167 28.9583C0.548614 26.4222 0.00139153 23.7139 2.63713e-06 20.8333C-0.00138625 17.9528 0.545836 15.2444 1.64167 12.7083C2.7375 10.1722 4.22153 7.96736 6.09375 6.09375C7.96597 4.22014 10.1708 2.73611 12.7083 1.64167C15.2458 0.547222 17.9542 0 20.8333 0C23.7125 0 26.4208 0.547222 28.9583 1.64167C31.4958 2.73611 33.7007 4.22014 35.5729 6.09375C37.4451 7.96736 38.9299 10.1722 40.0271 12.7083C41.1243 15.2444 41.6708 17.9528 41.6667 20.8333C41.6625 23.7139 41.1153 26.4222 40.025 28.9583C38.9347 31.4944 37.4507 33.6993 35.5729 35.5729C33.6951 37.4465 31.4903 38.9312 28.9583 40.0271C26.4264 41.1229 23.7181 41.6694 20.8333 41.6667Z"
                                            fill="#3C96E1" />
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-label mt-2">Total Announcement</div>
                        </div>

                        <div class="stat-card flex-1 flex-col">
                            <div class="flex justify-between items-center w-full">
                                <div>
                                    <div class="stat-value">
                                        <?= $labResultsCount ?>
                                        <span class="stat-pending">(<?= $labResultsPending ?> pending)</span>
                                    </div>
                                </div>

                                <div class="stat-icon">
                                    <!-- <i class="fas fa-flask icon-lg"></i> -->
                                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M43.7479 13.2812C43.7479 13.9029 43.501 14.499 43.0615 14.9385C42.6219 15.3781 42.0258 15.625 41.4042 15.625C40.7826 15.625 40.1864 15.3781 39.7469 14.9385C39.3074 14.499 39.0604 13.9029 39.0604 13.2812V8L24.9979 22.0625V32.0312C24.9974 32.6526 24.7501 33.2484 24.3104 33.6875L14.9354 43.0625C14.6077 43.3899 14.1902 43.6128 13.7359 43.703C13.2815 43.7933 12.8106 43.7469 12.3826 43.5696C11.9545 43.3924 11.5887 43.0923 11.3311 42.7073C11.0735 42.3222 10.9358 41.8695 10.9354 41.4062V32.8125H2.34169C1.87844 32.8121 1.4257 32.6744 1.04065 32.4169C0.655602 32.1593 0.355516 31.7934 0.178293 31.3654C0.00106979 30.9374 -0.0453408 30.4664 0.044923 30.0121C0.135187 29.5577 0.358076 29.1403 0.68544 28.8125L10.0604 19.4375C10.4996 18.9978 11.0953 18.7505 11.7167 18.75H21.6854L35.7479 4.6875H30.4667C29.8451 4.6875 29.2489 4.44057 28.8094 4.00103C28.3699 3.56149 28.1229 2.96535 28.1229 2.34375C28.1229 1.72215 28.3699 1.12601 28.8094 0.686469C29.2489 0.24693 29.8451 9.26258e-09 30.4667 0H43.7479V13.2812ZM7.99794 28.125H15.6229V35.75L20.3104 31.0625V23.6187L20.2167 23.5312L20.1323 23.4375H12.6854L7.99794 28.125Z"
                                            fill="#10B981" />
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-label mt-2">Lab Results</div>
                        </div>


                        <div class="stat-card flex-1 flex-col">
                            <div class="flex justify-between items-center w-full">
                                <div>
                                    <div class="stat-value">
                                        <?= $basicAnnouncementsCount ?>
                                        <span class="stat-pending">(<?= $basicAnnouncementsPending ?> pending)</span>
                                    </div>
                                </div>

                                <div class="stat-icon">
                                    <svg width="14" height="33" viewBox="0 0 14 33" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0 27.0833V3.36667C0 2.44028 0.329167 1.64792 0.9875 0.989583C1.64861 0.329861 2.44166 0 3.36666 0H10.1042C11.0306 0 11.8215 0.329861 12.4771 0.989583C13.1326 1.64792 13.4604 2.43958 13.4604 3.36458V27.0833H0ZM0 32.0521V29.9688H13.4583V32.0521H0Z"
                                            fill="#D97706" />
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-label mt-2">General Announcements</div>
                        </div>


                        <div class="stat-card flex-1 flex-col">
                            <div class="flex justify-between items-center w-full">
                                <div>
                                    <div class="stat-value"><?= $pendingCount ?></div>
                                </div>

                                <div class="stat-icon">
                                    <svg width="34" height="34" viewBox="0 0 34 34" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M16.6667 30.2083C13.0752 30.2083 9.63082 28.7816 7.09126 26.2421C4.55171 23.7025 3.125 20.2581 3.125 16.6667C3.125 13.0752 4.55171 9.63082 7.09126 7.09126C9.63082 4.55171 13.0752 3.125 16.6667 3.125C20.2581 3.125 23.7025 4.55171 26.2421 7.09126C28.7816 9.63082 30.2083 13.0752 30.2083 16.6667C30.2083 20.2581 28.7816 23.7025 26.2421 26.2421C23.7025 28.7816 20.2581 30.2083 16.6667 30.2083ZM0 16.6667C0 12.2464 1.75595 8.00716 4.88155 4.88155C8.00716 1.75595 12.2464 0 16.6667 0C21.0869 0 25.3262 1.75595 28.4518 4.88155C31.5774 8.00716 33.3333 12.2464 33.3333 16.6667C33.3333 21.0869 31.5774 25.3262 28.4518 28.4518C25.3262 31.5774 21.0869 33.3333 16.6667 33.3333C12.2464 33.3333 8.00716 31.5774 4.88155 28.4518C1.75595 25.3262 0 21.0869 0 16.6667ZM16.6667 25C14.4565 25 12.3369 24.122 10.7741 22.5592C9.21131 20.9964 8.33333 18.8768 8.33333 16.6667H16.6667V8.33333C18.8768 8.33333 20.9964 9.21131 22.5592 10.7741C24.122 12.3369 25 14.4565 25 16.6667C25 18.8768 24.122 20.9964 22.5592 22.5592C20.9964 24.122 18.8768 25 16.6667 25Z"
                                            fill="black" fill-opacity="0.5" />
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-label mt-2">All Pending</div>
                        </div>

                    </div>

                    <!-- Lab Results Section -->

                    <div class="mb-8">
                        <div class="flex items-center border-b-2 py-6 gap-6 mb-6">
                            <button id="btnLabResults" type="button" class="tab-header active"
                                style="border-radius: 4px;">
                                Lab Results
                            </button>
                            <button id="btnGeneralAnnouncements" type="button" class="tab-header"
                                style="border-radius: 4px;">
                                General Announcements
                            </button>
                        </div>
                        <div id="labResultsSection">
                            <?php if (!empty($labResults)): ?>
                                <style>
                                    .announcement-grid {
                                        display: grid;
                                        grid-template-columns: repeat(4, 1fr);
                                        gap: 1.5rem;
                                    }

                                    @media (max-width: 1200px) {
                                        .announcement-grid {
                                            grid-template-columns: repeat(2, 1fr);
                                        }
                                    }

                                    @media (max-width: 800px) {
                                        .announcement-grid {
                                            grid-template-columns: 1fr;
                                        }
                                    }

                                    .announcement-card {
                                        background: #fff;
                                        border-radius: 12px;
                                        border: 1px solid #e5e7eb;
                                        padding: 1.5rem;
                                        display: flex;
                                        flex-direction: column;
                                        gap: 0.3rem;
                                        min-width: 0;
                                        height: auto;
                                        min-height: 0;
                                    }
                                </style>
                                <style>
                                    .lab-result-accepted {
                                        background: linear-gradient(to right, #D1FAE5, #FFFFFF);
                                        border: 2px solid #10B981;
                                    }

                                    .lab-result-dismissed {
                                        background: linear-gradient(to right, #F3F4F6, #FFFFFF);
                                        border: 2px solid #E5E7EB;
                                    }
                                </style>
                                <div class="announcement-grid">
                                    <?php foreach ($labResults as $announcement): ?>
                                        <?php
                                        $responseStatus = $announcement['user_status'];
                                        $highlightClass = '';
                                        $responseBadge = '';
                                        if ($responseStatus === 'accepted') {
                                            $highlightClass = 'lab-result-accepted';
                                            $responseBadge = '<span class="badge badge-accepted" style="margin-left:0.5em;">Accepted</span>';
                                        } elseif ($responseStatus === 'dismissed') {
                                            $highlightClass = 'lab-result-dismissed';
                                            $responseBadge = '<span class="badge badge-dismissed" style="margin-left:0.5em;">Dismissed</span>';
                                        }
                                        ?>
                                        <div class="announcement-item announcement-card <?= $highlightClass ?>">
                                            <div class="announcement-header">
                                                <div class="flex-1">
                                                    <h3 class="announcement-title text-blue-700">
                                                        <?= htmlspecialchars($announcement['title']) ?>
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="announcement-badges">
                                                <span class="badge badge-lab-result">Lab Result</span>
                                                <span class="badge badge-<?= $announcement['priority'] ?>">
                                                    <?= ucfirst($announcement['priority']) ?>
                                                </span>
                                            </div>
                                            <!-- <div class="announcement-meta items-center mb-2"> -->
                                            <div>
                                                <!-- DATE ADDED -->
                                                <div class="flex flex-col md:flex-row gap-2 text-sm mb-4">
                                                    <span class="text-gray-150">Date Added:</span>
                                                    <span class="font-bold">
                                                        <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                                    </span>
                                                </div>

                                                <!-- STATUS + BUTTON -->
                                                <div class="flex flex-col md:flex-row justify-between w-full">
                                                    <!-- STATUS -->
                                                    <?php if ($announcement['user_status']): ?>
                                                        <div class="flex flex-col md:flex-row items-center">
                                                            <span class="badge badge-<?= $announcement['user_status'] ?>">
                                                                <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                                        fill="#10B981" />
                                                                </svg>
                                                                <?= ucfirst($announcement['user_status']) ?>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge badge-pending">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM6.75 3.75H17.25V7.09125L16.7091 7.5H7.24969L6.75 7.125V3.75ZM12 11.0625L9.25031 9H14.7253L12 11.0625ZM17.25 20.25H6.75V16.875L11.25 13.5V15.75C11.25 15.9489 11.329 16.1397 11.4697 16.2803C11.6103 16.421 11.8011 16.5 12 16.5C12.1989 16.5 12.3897 16.421 12.5303 16.2803C12.671 16.1397 12.75 15.9489 12.75 15.75V13.5075L17.25 16.9088V20.25Z"
                                                                    fill="#92400E" />
                                                            </svg>
                                                            Pending
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- BUTTON -->
                                                    <div class="announcement-actions">
                                                        <button
                                                            onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                            class="btn-primary" style="background: #2563eb;">
                                                            View Result
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- </div> -->

                                            <?php if ($announcement['image_path']): ?>
                                                <div class="image-preview mb-2">
                                                    <img src="<?= htmlspecialchars($announcement['image_path']) ?>"
                                                        alt="Announcement Image"
                                                        onclick="openImageModal('<?= htmlspecialchars($announcement['image_path']) ?>')">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-10 sm:py-20">
                                    <div class="empty-state-title">No Laboratory Results</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- GENERAL ANNOUNCEMENT CONTENT -->
                        <div id="generalAnnouncementsSection" style="display:none;">
                            <?php if (!empty($basicAnnouncements)): ?>
                                <div class="announcement-grid">
                                    <?php foreach ($basicAnnouncements as $announcement): ?>
                                        <div class="announcement-item announcement-card">
                                            <style>
                                                .announcement-grid {
                                                    display: grid;
                                                    grid-template-columns: repeat(4, 1fr);
                                                    gap: 1.5rem;
                                                    /* margin-bottom: 0.5rem; */
                                                }

                                                @media (max-width: 1200px) {
                                                    .announcement-grid {
                                                        grid-template-columns: repeat(2, 1fr);
                                                    }
                                                }

                                                @media (max-width: 800px) {
                                                    .announcement-grid {
                                                        grid-template-columns: 1fr;
                                                    }
                                                }

                                                .announcement-card {
                                                    background: #fff;
                                                    border-radius: 12px;
                                                    border: 1px solid #e5e7eb;
                                                    /* box-shadow removed */
                                                    padding: 1.5rem;
                                                    display: flex;
                                                    flex-direction: column;
                                                    gap: 0.3rem;
                                                    min-width: 0;
                                                    height: auto;
                                                    min-height: 0;
                                                }
                                            </style>
                                            <div class="announcement-header">
                                                <div class="flex-1">
                                                    <h3 class="announcement-title text-blue-700">
                                                        <?= htmlspecialchars($announcement['title']) ?>
                                                    </h3>

                                                </div>
                                            </div>
                                            <div class="announcement-badges">
                                                <span class="badge badge-simple">Announcement</span>
                                                <!-- PRIORITY -->
                                                <span class="badge badge-<?= $announcement['priority'] ?>">
                                                    <?= ucfirst($announcement['priority']) ?></span>
                                            </div>

                                            <!-- <div class="announcement-meta mb-4"> -->
                                            <div>

                                                <!-- DATE ADDED -->
                                                <div class="flex flex-col md:flex-row gap-2 text-sm mb-4">
                                                    <span class="text-gray-150">Date Added:</span>
                                                    <span class="font-bold">
                                                        <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                                    </span>
                                                </div>

                                                <!-- STATUS + VIEW BUTTON -->
                                                <div class="flex flex-col md:flex-row justify-between w-full">

                                                    <!-- STATUS -->
                                                    <?php if ($announcement['user_status']): ?>
                                                        <div class="mt-1">
                                                            <span class="badge badge-<?= $announcement['user_status'] ?>">
                                                                <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                                        fill="#10B981" />
                                                                </svg>
                                                                <?= ucfirst($announcement['user_status']) ?>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge badge-pending mt-1">
                                                            Pending
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- VIEW BUTTON -->
                                                    <div class="announcement-actions mt-2 md:mt-0">
                                                        <button
                                                            onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                            class="btn-primary" style="background: #2563eb;">
                                                            View
                                                        </button>
                                                    </div>

                                                </div>

                                            </div>
                                            <!-- </div> -->
                                            <!-- <div class="announcement-content mb-2">
                                                        <?= htmlspecialchars($announcement['message']) ?>
                                                    </div> -->
                                            <?php if ($announcement['image_path']): ?>
                                                <div class="image-preview mb-2">
                                                    <img src="<?= htmlspecialchars($announcement['image_path']) ?>"
                                                        alt="Announcement Image"
                                                        onclick="openImageModal('<?= htmlspecialchars($announcement['image_path']) ?>')">
                                                </div>
                                            <?php endif; ?>
                                            <!-- <?php if ($announcement['expiry_date']): ?>
                                                        <div class="mb-2 text-blue-700 text-sm flex items-center gap-2">
                                                            Expires:
                                                            <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?>
                                                        </div>
                                                    <?php endif; ?> -->

                                            <!-- RESPONSE SECTION -->
                                            <!-- <div class="mt-2">
                                                        <?php if ($announcement['user_status']): ?>
                                                            <div class="flex items-center gap-2 p-2 rounded bg-blue-50 border border-blue-200">
                                                                <span class="font-semibold text-blue-700">Response Recorded:
                                                                    <?= ucfirst($announcement['user_status']) ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex gap-2">
                                                                <form method="POST" action="" class="flex-1">
                                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                                    <button type="submit" name="respond_to_announcement" value="accepted"
                                                                        class="btn-response btn-accept w-full">Accept</button>
                                                                </form>
                                                                <form method="POST" action="" class="flex-1">
                                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                                    <button type="submit" name="respond_to_announcement" value="dismissed"
                                                                        class="btn-response btn-dismiss w-full">Dismiss</button>
                                                                </form>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div> -->
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- No Announcements Message -->
                <?php if (empty($labResults) && empty($basicAnnouncements)): ?>
                    <div class="text-center py-10 sm:py-20">
                        <div class="empty-state-icon">
                            <i class="fas fa-bullhorn icon-3xl"></i>
                        </div>
                        <h3 class="empty-state-title">No Announcements</h3>
                        <p class="empty-state-text">There are currently no active announcements. Check back later for
                            updates.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats Tab -->
            <div id="stats" class="tab-content <?= $activeTab === 'stats' ? 'active' : 'hidden' ?>">
                <div class="flex flex-col md:flex-row gap-8 w-full mb-6" style="padding-left:2em; padding-right:2em;">
                    <!-- Total Announcement -->
                    <div class="stat-card flex-1 flex-col">
                        <div class="flex justify-between items-center w-full">
                            <div>
                                <div class="stat-value"><?= count($announcements) ?></div>
                            </div>

                            <div class="stat-icon">
                                <svg width="42" height="42" viewBox="0 0 42 42" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M20.8333 33.3333C24.3056 33.3333 27.2569 32.1181 29.6875 29.6875C32.1181 27.2569 33.3333 24.3056 33.3333 20.8333C33.3333 17.3611 32.1181 14.4097 29.6875 11.9792C27.2569 9.54861 24.3056 8.33333 20.8333 8.33333C17.3611 8.33333 14.4097 9.54861 11.9792 11.9792C9.54861 14.4097 8.33334 17.3611 8.33334 20.8333C8.33334 24.3056 9.54861 27.2569 11.9792 29.6875C14.4097 32.1181 17.3611 33.3333 20.8333 33.3333ZM14.5833 22.9167V18.75H27.0833V22.9167H14.5833ZM20.8333 41.6667C17.9514 41.6667 15.2431 41.1194 12.7083 40.025C10.1736 38.9306 7.96875 37.4465 6.09375 35.5729C4.21875 33.6993 2.73472 31.4944 1.64167 28.9583C0.548614 26.4222 0.00139153 23.7139 2.63713e-06 20.8333C-0.00138625 17.9528 0.545836 15.2444 1.64167 12.7083C2.7375 10.1722 4.22153 7.96736 6.09375 6.09375C7.96597 4.22014 10.1708 2.73611 12.7083 1.64167C15.2458 0.547222 17.9542 0 20.8333 0C23.7125 0 26.4208 0.547222 28.9583 1.64167C31.4958 2.73611 33.7007 4.22014 35.5729 6.09375C37.4451 7.96736 38.9299 10.1722 40.0271 12.7083C41.1243 15.2444 41.6708 17.9528 41.6667 20.8333C41.6625 23.7139 41.1153 26.4222 40.025 28.9583C38.9347 31.4944 37.4507 33.6993 35.5729 35.5729C33.6951 37.4465 31.4903 38.9312 28.9583 40.0271C26.4264 41.1229 23.7181 41.6694 20.8333 41.6667Z"
                                        fill="#3C96E1" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-label mt-2">Total Announcements</div>
                    </div>

                    <!-- Accepted -->
                    <div class="stat-card flex-1 flex-col">
                        <div class="flex justify-between items-center w-full">
                            <div>
                                <div class="stat-value"><?= $acceptedCount ?></div>
                            </div>

                            <div class="stat-icon">
                                <svg width="44" height="34" viewBox="0 0 44 34" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M41.667 16.625V2H2V31.25H21.833C22.3853 31.25 22.833 31.6977 22.833 32.25C22.833 32.8023 22.3853 33.25 21.833 33.25H1C0.447716 33.25 1.0308e-06 32.8023 0 32.25V1C0 0.447715 0.447715 0 1 0H42.667C43.2191 0.000173136 43.667 0.447822 43.667 1V16.625C43.667 17.1772 43.2191 17.6248 42.667 17.625C42.1147 17.625 41.667 17.1773 41.667 16.625Z"
                                        fill="#10B981" />
                                    <path
                                        d="M42.667 26.041C43.2193 26.041 43.667 26.4887 43.667 27.041C43.667 27.5933 43.2193 28.041 42.667 28.041H28.084C27.5317 28.041 27.084 27.5933 27.084 27.041C27.084 26.4887 27.5317 26.041 28.084 26.041H42.667Z"
                                        fill="#10B981" />
                                    <path
                                        d="M32.5848 21.1268C32.9753 20.7363 33.6083 20.7364 33.9989 21.1268C34.3894 21.5173 34.3894 22.1503 33.9989 22.5409L29.4969 27.0418L33.9989 31.5438C34.3894 31.9343 34.3894 32.5673 33.9989 32.9579C33.6083 33.348 32.9752 33.3483 32.5848 32.9579L27.3768 27.7489C26.9864 27.3583 26.9863 26.7253 27.3768 26.3348L32.5848 21.1268Z"
                                        fill="#10B981" />
                                    <path
                                        d="M0.199913 0.400183C0.531245 -0.0415923 1.15751 -0.131244 1.59933 0.199988L21.8327 15.3748L42.0661 0.199988C42.508 -0.131383 43.1351 -0.0416444 43.4665 0.400183C43.7976 0.841978 43.708 1.46829 43.2663 1.7996L22.4333 17.4246C22.0778 17.6913 21.5887 17.6913 21.2331 17.4246L0.400109 1.7996C-0.041667 1.46827 -0.131319 0.842003 0.199913 0.400183Z"
                                        fill="#10B981" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-label mt-2">Accepted</div>
                    </div>

                    <!-- Pending Response -->
                    <div class="stat-card flex-1 flex-col">
                        <div class="flex justify-between items-center w-full">
                            <div>
                                <div class="stat-value"><?= $pendingCount ?></div>
                            </div>

                            <div class="stat-icon">
                                <svg width="40" height="44" viewBox="0 0 40 44" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M29.1667 43.75C26.2847 43.75 23.8285 42.734 21.7979 40.7021C19.7674 38.6701 18.7514 36.2139 18.75 33.3333C18.7486 30.4528 19.7646 27.9965 21.7979 25.9646C23.8312 23.9326 26.2875 22.9167 29.1667 22.9167C32.0458 22.9167 34.5028 23.9326 36.5375 25.9646C38.5722 27.9965 39.5875 30.4528 39.5833 33.3333C39.5792 36.2139 38.5632 38.6708 36.5354 40.7042C34.5076 42.7375 32.0514 43.7528 29.1667 43.75ZM32.6562 38.2812L34.1146 36.8229L30.2083 32.9167V27.0833H28.125V33.75L32.6562 38.2812ZM4.16667 41.6667C3.02083 41.6667 2.04028 41.259 1.225 40.4437C0.409722 39.6285 0.00138889 38.6472 0 37.5V8.33333C0 7.1875 0.408333 6.20694 1.225 5.39167C2.04167 4.57639 3.02222 4.16806 4.16667 4.16667H12.8646C13.2465 2.95139 13.9931 1.95347 15.1042 1.17292C16.2153 0.392361 17.4306 0.00138889 18.75 0C20.1389 0 21.3806 0.390972 22.475 1.17292C23.5694 1.95486 24.3069 2.95278 24.6875 4.16667H33.3333C34.4792 4.16667 35.4604 4.575 36.2771 5.39167C37.0937 6.20833 37.5014 7.18889 37.5 8.33333V21.3542C36.875 20.9028 36.2153 20.5208 35.5208 20.2083C34.8264 19.8958 34.0972 19.6181 33.3333 19.375V8.33333H29.1667V14.5833H8.33333V8.33333H4.16667V37.5H15.2083C15.4514 38.2639 15.7292 38.9931 16.0417 39.6875C16.3542 40.3819 16.7361 41.0417 17.1875 41.6667H4.16667ZM18.75 8.33333C19.3403 8.33333 19.8354 8.13333 20.2354 7.73333C20.6354 7.33333 20.8347 6.83889 20.8333 6.25C20.8319 5.66111 20.6319 5.16667 20.2333 4.76667C19.8347 4.36667 19.3403 4.16667 18.75 4.16667C18.1597 4.16667 17.6653 4.36667 17.2667 4.76667C16.8681 5.16667 16.6681 5.66111 16.6667 6.25C16.6653 6.83889 16.8653 7.33403 17.2667 7.73542C17.6681 8.1368 18.1625 8.33611 18.75 8.33333Z"
                                        fill="#D97706" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-label mt-2">Pending Response</div>
                    </div>

                    <!-- Dismissed -->
                    <div class="stat-card flex-1 flex-col">
                        <div class="flex justify-between items-center w-full">
                            <div>
                                <div class="stat-value"><?= $dismissedCount ?></div>
                            </div>

                            <div class="stat-icon">
                                <svg width="43" height="43" viewBox="0 0 43 43" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M42.8571 21.4286C42.8571 9.59464 33.2625 0 21.4286 0C9.59464 0 0 9.59464 0 21.4286C0 33.2625 9.59464 42.8571 21.4286 42.8571C33.2625 42.8571 42.8571 33.2625 42.8571 21.4286ZM29.0714 13.7857C29.3222 14.0368 29.4631 14.3772 29.4631 14.7321C29.4631 15.0871 29.3222 15.4275 29.0714 15.6786L23.3214 21.4286L29.0714 27.1786C29.203 27.3012 29.3086 27.449 29.3818 27.6133C29.455 27.7776 29.4943 27.955 29.4975 28.1348C29.5007 28.3146 29.4676 28.4932 29.4002 28.66C29.3329 28.8268 29.2326 28.9782 29.1054 29.1054C28.9782 29.2326 28.8268 29.3329 28.66 29.4002C28.4932 29.4676 28.3146 29.5007 28.1348 29.4975C27.955 29.4943 27.7776 29.455 27.6133 29.3818C27.449 29.3086 27.3012 29.203 27.1786 29.0714L21.4286 23.3214L15.6786 29.0714C15.556 29.203 15.4081 29.3086 15.2438 29.3818C15.0795 29.455 14.9022 29.4943 14.7224 29.4975C14.5425 29.5007 14.3639 29.4676 14.1971 29.4002C14.0304 29.3329 13.8789 29.2326 13.7517 29.1054C13.6245 28.9782 13.5243 28.8268 13.4569 28.66C13.3896 28.4932 13.3565 28.3146 13.3597 28.1348C13.3628 27.955 13.4022 27.7776 13.4754 27.6133C13.5486 27.449 13.6541 27.3012 13.7857 27.1786L19.5357 21.4286L13.7857 15.6786C13.6541 15.556 13.5486 15.4081 13.4754 15.2438C13.4022 15.0795 13.3628 14.9022 13.3597 14.7224C13.3565 14.5425 13.3896 14.3639 13.4569 14.1971C13.5243 14.0304 13.6245 13.8789 13.7517 13.7517C13.8789 13.6245 14.0304 13.5243 14.1971 13.4569C14.3639 13.3896 14.5425 13.3565 14.7224 13.3597C14.9022 13.3628 15.0795 13.4022 15.2438 13.4754C15.4081 13.5486 15.556 13.6541 15.6786 13.7857L21.4286 19.5357L27.1786 13.7857C27.4297 13.5349 27.7701 13.394 28.125 13.394C28.4799 13.394 28.8203 13.5349 29.0714 13.7857Z"
                                        fill="black" fill-opacity="0.5" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-label mt-2">Dismissed</div>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b-2 py-2"
                        style=" margin-left: 2em; margin-right: 2em;">
                        Your Responded Announcements</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 px-8">
                        <?php foreach ($announcements as $a):
                            if (!empty($a['user_status'])): ?>
                                <div class="card-shadow bg-white border border-gray-200 p-4 rounded flex flex-col gap-2">
                                    <span class="font-bold text-lg text-gray-900"><?= htmlspecialchars($a['title']) ?></span>
                                    <div class="announcement-badges">
                                        <?php if (isset($a['announcement_type']) && $a['announcement_type'] === 'lab_result'): ?>
                                            <span class="badge badge-lab-result">Lab Result</span>
                                        <?php else: ?>
                                            <span class="badge badge-simple">General Announcement</span>
                                        <?php endif; ?>
                                        <!-- PRIORITY -->
                                        <span class="badge badge-<?= $a['priority'] ?>">
                                            <?= ucfirst($a['priority']) ?>
                                        </span>
                                    </div>
                                    <!-- DATE ADDED -->
                                    <div class="flex flex-col md:flex-row gap-2 text-sm mb-1">
                                        <span class="text-gray-150">Date Added:</span>
                                        <span class="font-bold">
                                            <?= date('M d, Y', strtotime($a['post_date'])) ?>
                                        </span>
                                    </div>
                                    <div class="flex flex-col md:flex-row justify-between">
                                        <!-- STATUS -->
                                        <div class="flex items-center gap-2">
                                            <?php if ($a['user_status'] === 'accepted'): ?>
                                                <span class="badge badge-accepted">
                                                    <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                            fill="#10B981" />
                                                    </svg>
                                                    Accepted</span>
                                            <?php elseif ($a['user_status'] === 'dismissed'): ?>
                                                <span class="badge badge-dismissed">
                                                    <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25ZM9.70625 5.29375L9.65375 5.24813C9.57411 5.18926 9.47787 5.15712 9.37884 5.15634C9.2798 5.15555 9.18306 5.18615 9.1025 5.24375L9.04375 5.29375L7.5 6.83687L5.95625 5.29313L5.90375 5.24813C5.82411 5.18926 5.72787 5.15712 5.62884 5.15634C5.5298 5.15555 5.43306 5.18615 5.3525 5.24375L5.29375 5.29375L5.24813 5.34625C5.18926 5.42589 5.15712 5.52213 5.15634 5.62116C5.15555 5.7202 5.18615 5.81694 5.24375 5.8975L5.29375 5.95625L6.83687 7.5L5.29313 9.04375L5.24813 9.09625C5.18926 9.17589 5.15712 9.27213 5.15634 9.37116C5.15555 9.4702 5.18615 9.56693 5.24375 9.6475L5.29375 9.70625L5.34625 9.75187C5.42589 9.81074 5.52213 9.84288 5.62116 9.84366C5.7202 9.84445 5.81694 9.81385 5.8975 9.75625L5.95625 9.70625L7.5 8.16313L9.04375 9.70687L9.09625 9.75187C9.17589 9.81074 9.27213 9.84288 9.37116 9.84366C9.4702 9.84445 9.56693 9.81385 9.6475 9.75625L9.70625 9.70625L9.75187 9.65375C9.81074 9.57411 9.84288 9.47787 9.84366 9.37884C9.84445 9.2798 9.81385 9.18306 9.75625 9.1025L9.70625 9.04375L8.16313 7.5L9.70687 5.95625L9.75187 5.90375C9.81074 5.82411 9.84288 5.72787 9.84366 5.62884C9.84445 5.5298 9.81385 5.43306 9.75625 5.3525L9.70625 5.29375Z"
                                                            fill="black" fill-opacity="0.5" />
                                                    </svg>
                                                    Dismissed</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- VIEW -->
                                        <div class="announcement-actions">
                                            <button
                                                onclick="openViewModal(<?= htmlspecialchars(json_encode($a, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                class="btn-primary" style="background: #2563eb;">
                                                View
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif;
                        endforeach; ?>
                        <?php if ($acceptedCount + $dismissedCount === 0): ?>
                            <div class="text-center py-8 text-gray-500">No responded announcements yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- View Announcement Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Announcement Details</h2>
                <button class="modal-close" onclick="closeViewModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Image</h2>
                <button class="modal-close" onclick="closeImageModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Announcement Image" class="w-full h-auto rounded-lg">
            </div>
        </div>
    </div>

    <script>
        // View Announcement Modal
        function openViewModal(announcement) {
            const modal = document.getElementById('viewModal');
            const modalContent = document.getElementById('modalContent');

            const postDate = new Date(announcement.post_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            let content = `
                <div style="space-y: 1.5rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem;">${announcement.title || 'Announcement'}</h3>
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: #6b7280;">
                            <span><i class="fas fa-user icon-sm"></i> ${announcement.staff_name || 'Community Staff'}</span>
                            <span><i class="fas fa-calendar icon-sm"></i> ${postDate}</span>
                        </div>
                    </div>
                    
                    ${announcement.expiry_date ? `
                        <div style="background: #DBEAFE; padding: 1rem; border-radius: 8px; border: 1px solid #BAE6FD; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas fa-clock" style="color: #0369A1;"></i>
                                <div>
                                    <p style="font-weight: 600; color: #0C4A6E; margin: 0;">Expires: ${new Date(announcement.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</p>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${announcement.image_path ? `
                        <div style="margin-bottom: 1rem;">
                            <img src="${announcement.image_path}" 
                                 alt="Announcement Image" 
                                 style="width: 100%; height: auto; max-height: 300px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer;"
                                 onclick="openImageModal('${announcement.image_path}')">
                        </div>
                    ` : ''}
                    
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border: 1px solid #e5e7eb; line-height: 1.6; color: #374151; white-space: pre-line;">
                        ${announcement.message || 'No message provided'}
                    </div>
            `;

            if (announcement.user_status === 'accepted') {
                content += `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #D1FAE5; border-radius: 8px; border: 1px solid #A7F3D0; margin-top: 1rem;">
                        <i class="fas fa-check-circle" style="color: #059669; font-size: 1.25rem;"></i>
                        <div>
                            <p style="font-weight: 600; color: #065F46; margin: 0;">Accepted</p>
                        </div>
                    </div>
                `;
            } else if (announcement.user_status === 'dismissed') {
                content += `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #F3F4F6; border-radius: 8px; border: 1px solid #E5E7EB; margin-top: 1rem;">
                        <i class="fas fa-times-circle" style="color: #6b7280; font-size: 1.25rem;"></i>
                        <div>
                            <p style="font-weight: 600; color: #374151; margin: 0;">Dismissed</p>
                        </div>
                    </div>
                `;
            } else {
                content += `
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="announcement_id" value="${announcement.id}">
                            <button type="submit" name="respond_to_announcement" value="accepted" 
                                    class="btn-response btn-accept" style="width: 100%;">
                                <i class="fas fa-check-circle"></i>
                                Accept
                            </button>
                        </form>
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="announcement_id" value="${announcement.id}">
                            <button type="submit" name="respond_to_announcement" value="dismissed" 
                                    class="btn-response btn-dismiss" style="width: 100%;">
                                <i class="fas fa-times-circle"></i>
                                Dismiss
                            </button>
                        </form>
                    </div>
                `;
            }

            content += `</div>`;

            modalContent.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close View Modal
        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Image Modal
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const image = document.getElementById('modalImage');
            const downloadBtn = document.getElementById('downloadImage');

            image.src = imageSrc;

            // Set download link
            downloadBtn.onclick = function () {
                const link = document.createElement('a');
                link.href = imageSrc;
                const fileName = imageSrc.split('/').pop() || 'announcement-image.jpg';
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('viewModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeViewModal();
            }
        });

        document.getElementById('imageModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeImageModal();
            }
        });

        // Initialize animation for announcement cards
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.announcement-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Tab switching functionality
            document.querySelectorAll('.tab-header[data-tab]').forEach(button => {
                button.addEventListener('click', function () {
                    const tabName = this.getAttribute('data-tab');
                    document.querySelectorAll('.tab-header[data-tab]').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    this.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                });
            });

            // Toggle between Laboratory Results and General Announcements (inside All Announcements tab)
            const btnLab = document.getElementById('btnLabResults');
            const btnGen = document.getElementById('btnGeneralAnnouncements');
            const labSection = document.getElementById('labResultsSection');
            const genSection = document.getElementById('generalAnnouncementsSection');
            if (btnLab && btnGen && labSection && genSection) {
                btnLab.addEventListener('click', function () {
                    btnLab.classList.add('active');
                    btnGen.classList.remove('active');
                    labSection.style.display = '';
                    genSection.style.display = 'none';
                });
                btnGen.addEventListener('click', function () {
                    btnGen.classList.add('active');
                    btnLab.classList.remove('active');
                    genSection.style.display = '';
                    labSection.style.display = 'none';
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Always default to All Announcements tab on load
            setTimeout(function () {
                document.querySelectorAll('.tab-header[data-tab]').forEach(btn => {
                    btn.classList.remove('active');
                });
                var allTabBtn = document.querySelector('.tab-header[data-tab="announcements"]');
                if (allTabBtn) allTabBtn.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                var allTabContent = document.getElementById('announcements');
                if (allTabContent) allTabContent.classList.add('active');
            }, 100);

            // Tab switching functionality
            document.querySelectorAll('.tab-header[data-tab]').forEach(button => {
                button.addEventListener('click', function () {
                    const tabName = this.getAttribute('data-tab');
                    document.querySelectorAll('.tab-header[data-tab]').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    this.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                    // If Announcements tab is clicked, always show All Announcements and reset sub-tabs
                    if (tabName === 'announcements') {
                        // Reset sub-tabs: show Lab Results by default
                        const btnLab = document.getElementById('btnLabResults');
                        const btnGen = document.getElementById('btnGeneralAnnouncements');
                        const labSection = document.getElementById('labResultsSection');
                        const genSection = document.getElementById('generalAnnouncementsSection');
                        if (btnLab && btnGen && labSection && genSection) {
                            btnLab.classList.add('active');
                            btnGen.classList.remove('active');
                            labSection.style.display = '';
                            genSection.style.display = 'none';
                        }
                    }
                });
            });
        });
    </script>
</body>