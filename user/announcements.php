<?php
require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for resident users after 10 minutes of inactivity ---
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

// Use session-based flash messages for success/error
if (!isset($_SESSION)) session_start();
$error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : '';
$success = isset($_SESSION['flash_success']) ? $_SESSION['flash_success'] : '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Handle announcement response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_to_announcement'])) {
    $announcementId = $_POST['announcement_id'];
    $status = $_POST['respond_to_announcement'];
    $activeSubTab = $_POST['active_sub_tab'] ?? 'lab';
    $activeMainTab = $_POST['active_main_tab'] ?? 'announcements';

    if (!in_array($activeSubTab, ['lab', 'general'])) {
        $activeSubTab = 'lab';
    }

    if (!in_array($activeMainTab, ['announcements', 'stats'])) {
        $activeMainTab = 'announcements';
    }

    // Validate status
    if (!in_array($status, ['accepted', 'dismissed'])) {
        $_SESSION['flash_error'] = 'Invalid response status';
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
                $_SESSION['flash_error'] = 'This announcement is no longer available';
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

                if ($status === 'accepted') {
                    $_SESSION['flash_success'] = 'Announcement Accepted Successfully';
                } elseif ($status === 'dismissed') {
                    $_SESSION['flash_success'] = 'Announcement Dismissed Successfully';
                } else {
                    $_SESSION['flash_success'] = 'Response recorded successfully!';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Error recording response: ' . $e->getMessage();
        }
    }
    // Redirect to avoid form resubmission and preserve selected announcement sub-tab
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestQuery = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $queryParams = [];

    if (!empty($requestQuery)) {
        parse_str($requestQuery, $queryParams);
    }

    $queryParams['tab'] = $activeMainTab;
    $queryParams['sub_tab'] = $activeSubTab;

    $redirectUrl = $requestPath . '?' . http_build_query($queryParams);
    header('Location: ' . $redirectUrl);
    exit();
}

// Get announcements targeted to this user
$announcements = [];
$labResults = [];
$basicAnnouncements = [];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, ua.status as user_status, ua.response_date,
               s.full_name as staff_name, s.position as staff_position
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

$activeTab = $_GET['tab'] ?? 'announcements';
if (!in_array($activeTab, ['announcements', 'stats'])) {
    $activeTab = 'announcements';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php if (!empty($success)): ?>
        <style>
            .modal-success-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.52);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.4s cubic-bezier(.4, 0, .2, 1);
            }

            .modal-success-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }

            .modal-success-box {
                background: #ececec;
                color: #222;
                padding: 52px 34px 34px;
                border-radius: 20px;
                text-align: center;
                width: min(560px, calc(100vw - 12px));
                animation: modalFadeIn 0.5s cubic-bezier(.4, 0, .2, 1);
                position: relative;
            }

            .modal-success-box.dismissed {
                padding: 54px 30px 30px;
                border-radius: 14px;
            }

            .modal-success-icon {
                margin-bottom: 26px;
                display: flex;
                justify-content: center;
                align-items: center;
                width: 100%;
            }

            .modal-success-icon.dismissed {
                margin-bottom: 24px;
            }

            .modal-success-title {
                color: #0086c8;
                font-size: 20px;
                font-weight: 500;
                line-height: 1.3;
                margin-bottom: 10px;
            }

            .modal-success-title.dismissed {
                color: #4f4f4f;
                font-size: 20px;
                font-weight: 500;
                line-height: 1.28;
                margin-bottom: 8px;
            }

            .modal-success-subtitle {
                color: #8f8f8f;
                font-weight: 400;
                font-size: 16px;
                line-height: 1.35;
                margin-bottom: 36px;
            }

            .modal-success-subtitle.dismissed {
                color: #8f8f8f;
                font-size: 16px;
                line-height: 1.34;
                margin-bottom: 34px;
            }

            .modal-success-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                width: auto;
                background: #0086c8;
                color: #fff;
                border: none;
                border-radius: 9999px;
                font-size: 18px;
                font-weight: 600;
                line-height: 1.2;
                padding: 13px 36px;
                margin: 0 auto;
                cursor: pointer;
                transition: background 0.2s;
                box-shadow: none;
                white-space: nowrap;
            }

            .modal-success-btn.dismissed {
                background: #58585a;
                color: #fff;
                font-size: 18px;
                font-weight: 600;
                padding: 13px 36px;
            }

            .modal-success-btn.dismissed:hover {
                background: #4a4a4c;
            }

            .modal-success-btn:not(.dismissed):hover {
                background: #0672a7;
            }

            @keyframes modalFadeIn {
                from {
                    transform: scale(0.95) translateY(30px);
                    opacity: 0;
                }

                to {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var overlay = document.getElementById('success-modal-overlay');
                if (overlay) {
                    setTimeout(function () {
                        overlay.classList.add('active');
                    }, 50);
                }
            });
            function closeSuccessModal() {
                var overlay = document.getElementById('success-modal-overlay');
                if (overlay) overlay.classList.remove('active');
            }
        </script>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <?php $isDismissed = strpos($success, 'Dismissed') !== false; ?>
        <div id="success-modal-overlay" class="modal-success-overlay">
            <div class="modal-success-box<?php if ($isDismissed)
                echo ' dismissed'; ?>">
                <div class="modal-success-icon<?php if ($isDismissed)
                    echo ' dismissed'; ?>">
                    <?php if ($isDismissed): ?>
                        <svg width="54" height="54" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M14 14L42 42" stroke="#595959" stroke-width="3.6" stroke-linecap="round" />
                            <path d="M42 14L14 42" stroke="#595959" stroke-width="3.6" stroke-linecap="round" />
                        </svg>
                    <?php else: ?>
                        <svg width="74" height="74" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="48" cy="48" r="31" stroke="#0086C8" stroke-width="7" />
                            <path d="M35 49.5L44 58L61.5 39.5" stroke="#0086C8" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="modal-success-title<?php if ($isDismissed)
                    echo ' dismissed'; ?>">
                    <?= htmlspecialchars($success) ?>
                </div>
                <div class="modal-success-subtitle<?php if ($isDismissed)
                    echo ' dismissed'; ?>">
                    <?php if ($isDismissed): ?>
                        Your announcement has been dismissed <br> and recorded successfully.
                    <?php else: ?>
                        Your announcement has been accepted and <br> recorded successfully.
                    <?php endif; ?>
                </div>
                <button class="modal-success-btn<?php if ($isDismissed)
                    echo ' dismissed'; ?>" onclick="closeSuccessModal()">
                    Got it!
                </button>
            </div>
        </div>
    <?php endif; ?>
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
            padding-bottom: 2em;
            /* background: white; */
            overflow: hidden;
            gap: 1.5rem;
        }

        .text-gray-sample {
            color: rgba(0, 0, 0, 0.4);
        }

        @media (min-width: 640px) {
            .tab-nav-container {
                /* padding: 2rem 0; */
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
            width: 56px;
            height: 56px;
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
            font-size: 1rem;
            font-weight: 400;
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
            background: #FD88024D;
            color: #FD8802;
            /* border-color: #FDE68A; */
        }

        .badge-normal {
            background: #DBEAFE;
            color: #0C4A6E;
            border-color: #BAE6FD;
        }

        .badge-lab-result {
            background: #00B837;
            color: #FFFFFF;
            /* border-color: #7DD3FC; */
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
            background: #FD88024D;
            color: #FD8802;
            text-align: center;
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
            padding: 0.6rem 1.2rem;
            border-radius: 9999px;
            font-weight: 500;
            border: 1px solid;
            cursor: pointer;
            transition: all 0.2s;
            /* font-size: 0.95rem; */
        }

        .btn-accept {
            background: #10B981;
            color: #FFFFFF;
            /* border-color: #10B981; */
        }

        .btn-accept:hover {
            background: #0ca06f;
            transform: translateY(-2px);
        }

        .btn-dismiss {
            background: rgba(0, 0, 0, 0.4);
            color: rgba(255, 255, 255, 0.5);
            border-color: #e5e7eb;
        }

        .btn-dismiss:hover {
            background: rgba(0, 0, 0, 0.6);
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
            padding: 1rem 0rem;
            border-bottom: 2px solid #d3d3d3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            margin: 0 2rem;
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 500;
            color: #111827;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #000000;
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
            padding: 1rem 2rem;
        }

        #viewModal {
            background: rgba(0, 0, 0, 0.68);
        }

        #viewModal .modal {
            background: #f3f3f3;
            width: min(680px, calc(100vw - 24px));
            max-width: 680px;
            border-radius: 10px;
            max-height: calc(100vh - 24px);
            overflow: hidden;
        }

        #viewModal .modal-header {
            margin: 0;
            padding: 22px 28px 14px;
            border-bottom: 1px solid #d8d8d8;
            background: #f3f3f3;
        }

        #viewModal .modal-title {
            font-size: 20px;
            font-weight: 500;
            color: #111111;
            line-height: 1.1;
        }

        #viewModal .modal-close {
            width: auto;
            height: auto;
            padding: 0;
            border-radius: 0;
            color: #161616;
            font-weight: 300;
            line-height: 1;
        }

        #viewModal .modal-close-icon {
            width: 24px;
            height: 24px;
            display: block;
        }

        #viewModal .modal-close-icon line {
            stroke: #161616;
            stroke-width: 1.6;
            stroke-linecap: round;
        }

        #viewModal .modal-close:hover,
        #viewModal .modal-close:active {
            background: transparent;
            color: #161616;
            transform: none;
        }

        #viewModal .modal-body {
            padding: 12px 28px 26px;
        }

        .announcement-detail-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 12px;
        }

        .announcement-detail-title {
            color: #131313;
            font-size: 24px;
            font-weight: 500;
            line-height: 1.15;
            margin: 0;
        }

        .announcement-detail-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 1.18rem;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
        }

        .announcement-detail-pill.date {
            background: #c9f5e5;
            color: #11b684;
        }

        .announcement-detail-pill.expiry {
            background: #cfe1f7;
            color: #3a8ed8;
            margin-top: 8px;
        }

        .announcement-detail-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 14px;
        }

        .announcement-detail-posted {
            color: #646464;
            font-size: 1.15rem;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .announcement-detail-role {
            display: inline-flex;
            align-items: center;
            background: #bcdcff;
            color: #258ee9;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 1.02rem;
            font-weight: 500;
            margin-left: 6px;
        }

        .announcement-detail-name {
            color: #171717;
            font-size: 18px;
            font-weight: 500;
            line-height: 1.2;
        }

        .announcement-detail-message {
            background: #f5f5f5;
            border: 1px solid #dddddd;
            border-radius: 8px;
            min-height: 132px;
            max-height: 250px;
            overflow-y: auto;
            padding: 14px 16px;
            color: #222222;
            font-size: 1.18rem;
            line-height: 1.5;
            white-space: pre-wrap;
            margin-bottom: 18px;
        }

        .announcement-detail-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .announcement-detail-btn {
            border: none;
            border-radius: 4px;
            height: 48px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: filter 0.2s ease;
            line-height: 1;
        }

        .announcement-detail-btn:hover {
            filter: brightness(0.95);
        }

        .announcement-detail-btn.accept {
            background: #12b981;
            color: #ffffff;
            font-size: 18px;
            font-weight: 500;
        }

        .announcement-detail-btn.dismiss {
            background: #8f8f93;
            color: #ededed;
            font-size: 18px;
            font-weight: 500;
        }

        .announcement-detail-btn-icon {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.92rem;
            line-height: 1;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .announcement-response-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 500;
            line-height: 1;
        }

        .announcement-response-badge.accepted {
            background: linear-gradient(180deg, #d9f9ec 0%, #cef6e6 100%);
            color: #10b981;
            box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.12), 0 1px 2px rgba(16, 185, 129, 0.08);
        }

        .announcement-response-badge.dismissed {
            background: #e5e7eb;
            color: #374151;
        }

        .announcement-response-badge-icon {
            width: 26px;
            height: 26px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            flex-shrink: 0;
        }

        .announcement-response-badge.accepted .announcement-response-badge-icon {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #10b981;
        }

        .announcement-response-badge.dismissed .announcement-response-badge-icon {
            background: #6b7280;
            color: #ffffff;
        }

        .announcement-detail-btn-icon i {
            line-height: 1;
        }

        .announcement-detail-btn.accept .announcement-detail-btn-icon {
            background: transparent;
            border: 3px solid rgba(255, 255, 255, 0.95);
            color: #ffffff;
        }

        .announcement-detail-btn.dismiss .announcement-detail-btn-icon {
            background: #f2f2f2;
            border: 0;
            color: #8f8f93;
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
            color: #8d8d8d;
            margin-bottom: 12px;
        }

        .empty-state-text {
            font-size: 1rem;
            color: #6b7280;
            line-height: 1.6;
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

            #viewModal .modal {
                width: calc(100vw - 16px);
                max-height: calc(100vh - 16px);
            }

            #viewModal .modal-header {
                padding: 16px;
            }

            #viewModal .modal-body {
                padding: 12px 16px 16px;
            }

            #viewModal .modal-title {
                font-size: 1.2rem;
            }

            .announcement-detail-title {
                font-size: 1.45rem;
            }

            .announcement-detail-pill {
                font-size: 0.84rem;
                padding: 7px 10px;
            }

            .announcement-detail-posted,
            .announcement-detail-name {
                font-size: 0.95rem;
            }

            .announcement-detail-title-row,
            .announcement-detail-meta {
                flex-direction: column;
                gap: 8px;
            }

            .announcement-detail-message {
                font-size: 0.95rem;
                min-height: 110px;
            }

            .announcement-detail-btn {
                width: 100%;
                justify-content: center;
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
            <div class="flex flex-col md:flex-row items-center px-6 py-8 gap-2">
                <div>
                    <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M30.7691 9.67537C31.4665 8.978 32.5969 8.978 33.2942 9.67537C33.9916 10.3727 33.9916 11.5031 33.2942 12.2005L20.4943 25.0004L33.2942 37.8004C33.9916 38.4977 33.9916 39.6281 33.2942 40.3255C32.5969 41.0228 31.4665 41.0228 30.7691 40.3255L16.7066 26.263C16.0093 25.5656 16.0093 24.4352 16.7066 23.7379L30.7691 9.67537Z"
                            fill="black" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl text-gray-200 mb-2">Announcements</h3>
                    <p class="text-gray-sample">Announcement : Accept or Dismiss</p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-nav-container">
                <button class="tab-header <?= $activeTab === 'announcements' ? 'active' : '' ?>"
                    data-tab="announcements">
                    <span>All Announcements</span>
                </button>
                <button class="tab-header <?= $activeTab === 'stats' ? 'active' : '' ?>" data-tab="stats">
                    <span>Response Statistics</span>
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
                                    <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10.875 12C10.875 11.7775 10.941 11.56 11.0646 11.375C11.1882 11.19 11.3639 11.0458 11.5695 10.9606C11.7751 10.8755 12.0013 10.8532 12.2195 10.8966C12.4377 10.94 12.6382 11.0472 12.7955 11.2045C12.9529 11.3618 13.06 11.5623 13.1034 11.7805C13.1468 11.9988 13.1245 12.225 13.0394 12.4305C12.9542 12.6361 12.81 12.8118 12.625 12.9354C12.44 13.059 12.2225 13.125 12 13.125C11.7017 13.125 11.4155 13.0065 11.2045 12.7955C10.9936 12.5845 10.875 12.2984 10.875 12ZM7.87503 13.125C8.09753 13.125 8.31504 13.059 8.50004 12.9354C8.68505 12.8118 8.82924 12.6361 8.91439 12.4305C8.99954 12.225 9.02182 11.9988 8.97841 11.7805C8.935 11.5623 8.82785 11.3618 8.67052 11.2045C8.51319 11.0472 8.31273 10.94 8.0945 10.8966C7.87627 10.8532 7.65007 10.8755 7.44451 10.9606C7.23894 11.0458 7.06324 11.19 6.93962 11.375C6.81601 11.56 6.75003 11.7775 6.75003 12C6.75003 12.2984 6.86855 12.5845 7.07953 12.7955C7.29051 13.0065 7.57666 13.125 7.87503 13.125ZM16.125 13.125C16.3475 13.125 16.565 13.059 16.75 12.9354C16.935 12.8118 17.0792 12.6361 17.1644 12.4305C17.2495 12.225 17.2718 11.9988 17.2284 11.7805C17.185 11.5623 17.0779 11.3618 16.9205 11.2045C16.7632 11.0472 16.5627 10.94 16.3445 10.8966C16.1263 10.8532 15.9001 10.8755 15.6945 10.9606C15.4889 11.0458 15.3132 11.19 15.1896 11.375C15.066 11.56 15 11.7775 15 12C15 12.2984 15.1186 12.5845 15.3295 12.7955C15.5405 13.0065 15.8267 13.125 16.125 13.125ZM21.75 6V18C21.75 18.3978 21.592 18.7794 21.3107 19.0607C21.0294 19.342 20.6479 19.5 20.25 19.5H7.78128L4.72503 22.14L4.71659 22.1466C4.44662 22.3755 4.10397 22.5008 3.75003 22.5C3.52968 22.4995 3.31211 22.4509 3.11253 22.3575C2.85365 22.2379 2.63468 22.0462 2.48174 21.8055C2.3288 21.5648 2.24836 21.2852 2.25003 21V6C2.25003 5.60218 2.40806 5.22064 2.68937 4.93934C2.97067 4.65804 3.3522 4.5 3.75003 4.5H20.25C20.6479 4.5 21.0294 4.65804 21.3107 4.93934C21.592 5.22064 21.75 5.60218 21.75 6ZM20.25 6H3.75003V21L7.00971 18.1875C7.14523 18.068 7.31934 18.0014 7.50003 18H20.25V6Z" fill="#3C96E1"/>
</svg>

                                </div>
                            </div>
                            <div class="stat-label mt-2">Total Announcements</div>
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
                                    <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M47.5215 26.5625L28.125 7.16603C27.8359 6.87459 27.4917 6.64352 27.1125 6.48626C26.7333 6.32901 26.3266 6.2487 25.916 6.25002H7.81252C7.39811 6.25002 7.00069 6.41464 6.70766 6.70766C6.41464 7.00069 6.25002 7.39811 6.25002 7.81252V25.916C6.2487 26.3266 6.32901 26.7333 6.48626 27.1125C6.64352 27.4917 6.87459 27.8359 7.16603 28.125L26.5625 47.5215C26.8527 47.8118 27.1972 48.042 27.5764 48.1991C27.9556 48.3562 28.362 48.437 28.7725 48.437C29.1829 48.437 29.5893 48.3562 29.9685 48.1991C30.3477 48.042 30.6922 47.8118 30.9824 47.5215L47.5215 30.9824C47.8118 30.6922 48.042 30.3477 48.1991 29.9685C48.3562 29.5893 48.437 29.1829 48.437 28.7725C48.437 28.362 48.3562 27.9556 48.1991 27.5764C48.042 27.1972 47.8118 26.8527 47.5215 26.5625ZM28.7715 45.3125L9.37502 25.916V9.37502H25.916L45.3125 28.7715L28.7715 45.3125ZM18.75 16.4063C18.75 16.8698 18.6126 17.323 18.355 17.7084C18.0975 18.0938 17.7314 18.3942 17.3032 18.5716C16.8749 18.749 16.4037 18.7954 15.949 18.705C15.4944 18.6145 15.0768 18.3913 14.749 18.0635C14.4212 17.7358 14.198 17.3182 14.1075 16.8635C14.0171 16.4089 14.0635 15.9376 14.2409 15.5094C14.4183 15.0811 14.7187 14.715 15.1041 14.4575C15.4896 14.2 15.9427 14.0625 16.4063 14.0625C17.0279 14.0625 17.624 14.3094 18.0635 14.749C18.5031 15.1885 18.75 15.7847 18.75 16.4063Z"
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
                                    <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M44.6367 16.9258L10.25 6.37891C9.78458 6.24316 9.29396 6.21756 8.81694 6.30414C8.33993 6.39071 7.88959 6.58709 7.50156 6.87773C7.11353 7.16838 6.79844 7.54532 6.58122 7.97874C6.364 8.41217 6.25061 8.89019 6.25 9.375V37.5C6.25 38.3288 6.57924 39.1237 7.16529 39.7097C7.75134 40.2958 8.5462 40.625 9.375 40.625C9.67383 40.6251 9.97113 40.5824 10.2578 40.4981L26.5625 35.4941V37.5C26.5625 38.3288 26.8917 39.1237 27.4778 39.7097C28.0638 40.2958 28.8587 40.625 29.6875 40.625H35.9375C36.7663 40.625 37.5612 40.2958 38.1472 39.7097C38.7333 39.1237 39.0625 38.3288 39.0625 37.5V31.6602L44.6367 29.9512C45.2819 29.7573 45.8476 29.3613 46.2506 28.8215C46.6536 28.2817 46.8725 27.6268 46.875 26.9531V19.9219C46.8721 19.2486 46.653 18.594 46.2501 18.0546C45.8471 17.5152 45.2815 17.1195 44.6367 16.9258ZM26.5625 32.2266L9.375 37.5V9.375L26.5625 14.6484V32.2266ZM35.9375 37.5H29.6875V34.5352L35.9375 32.6172V37.5ZM43.75 26.9531H43.7285L29.6875 31.2656V15.6094L43.7285 19.9063H43.75V26.9375V26.9531Z"
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
                                    <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM17.25 20.25H6.75V16.875L12 12.9375L17.25 16.9078V20.25ZM17.25 7.09125L12 11.0625L6.75 7.125V3.75H17.25V7.09125Z" fill="#d99d06"/>
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

                        <!-- LAB RESULT CONTENT -->
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
                                        padding: 1rem;
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
                                                    <!-- STATUS BADGE -->
                                                    <?php if ($announcement['user_status'] === 'accepted'): ?>
                                                        <span class="badge badge-accepted">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                                    fill="#10B981" />
                                                            </svg>
                                                            Accepted
                                                        </span>
                                                    <?php elseif ($announcement['user_status'] === 'dismissed'): ?>
                                                        <span class="badge badge-dismissed">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25ZM9.70625 5.29375L9.65375 5.24813C9.57411 5.18926 9.47787 5.15712 9.37884 5.15634C9.2798 5.15555 9.18306 5.18615 9.1025 5.24375L9.04375 5.29375L7.5 6.83687L5.95625 5.29313L5.90375 5.24813C5.82411 5.18926 5.72787 5.15712 5.62884 5.15634C5.5298 5.15555 5.43306 5.18615 5.3525 5.24375L5.29375 5.29375L5.24813 5.34625C5.18926 5.42589 5.15712 5.52213 5.15634 5.62116C5.15555 5.7202 5.18615 5.81694 5.24375 5.8975L5.29375 5.95625L6.83687 7.5L5.29313 9.04375L5.24813 9.09625C5.18926 9.17589 5.15712 9.27213 5.15634 9.37116C5.15555 9.4702 5.18615 9.56693 5.24375 9.6475L5.29375 9.70625L5.34625 9.75187C5.42589 9.81074 5.52213 9.84288 5.62116 9.84366C5.7202 9.84445 5.81694 9.81385 5.8975 9.75625L5.95625 9.70625L7.5 8.16313L9.04375 9.70687L9.09625 9.75187C9.17589 9.81074 9.27213 9.84288 9.37116 9.84366C9.4702 9.84445 9.56693 9.81385 9.6475 9.75625L9.70625 9.70625L9.75187 9.65375C9.81074 9.57411 9.84288 9.47787 9.84366 9.37884C9.84445 9.2798 9.81385 9.18306 9.75625 9.1025L9.70625 9.04375L8.16313 7.5L9.70687 5.95625L9.75187 5.90375C9.81074 5.82411 9.84288 5.72787 9.84366 5.62884C9.84445 5.5298 9.81385 5.43306 9.75625 5.3525L9.70625 5.29375Z"
                                                                    fill="black" fill-opacity="0.5" />
                                                            </svg>
                                                            Dismissed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-pending">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M12.3748 11.5172L8.17886 7.5L12.3748 3.48281C12.3772 3.48013 12.3797 3.47759 12.3824 3.4752C12.5134 3.34409 12.6026 3.17708 12.6388 2.99528C12.6749 2.81348 12.6563 2.62505 12.5854 2.45379C12.5145 2.28254 12.3944 2.13616 12.2403 2.03314C12.0862 1.93012 11.905 1.87509 11.7197 1.875H3.28218C3.09688 1.8752 2.91579 1.93032 2.76178 2.03338C2.60778 2.13644 2.48778 2.28283 2.41693 2.45406C2.34608 2.62529 2.32757 2.81368 2.36372 2.99542C2.39988 3.17717 2.48909 3.34412 2.62007 3.4752L2.62711 3.48281L6.823 7.5L2.62711 11.5172L2.62007 11.5248C2.48909 11.6559 2.39988 11.8228 2.36372 12.0046C2.32757 12.1863 2.34608 12.3747 2.41693 12.5459C2.48778 12.7172 2.60778 12.8636 2.76178 12.9666C2.91579 13.0697 3.09688 13.1248 3.28218 13.125H11.7197C11.9051 13.125 12.0863 13.0701 12.2405 12.9671C12.3947 12.8641 12.5149 12.7178 12.5859 12.5465C12.6569 12.3752 12.6755 12.1867 12.6394 12.0049C12.6033 11.823 12.514 11.656 12.383 11.5248C12.3801 11.5224 12.3773 11.5199 12.3748 11.5172ZM11.7197 2.8125L7.50093 6.85078L3.28218 2.8125H11.7197ZM3.28218 12.1875L7.50093 8.14922L11.7197 12.1875H3.28218Z"
                                                                    fill="#FD8802" />
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

                                <!-- NO LABORATORY RESULT MESSAGE -->
                            <?php else: ?>
                                <div
                                    class="flex flex-col items-center justify-center text-center py-10 sm:py-20 min-h-[300px]">
                                    <div class="mb-4">
                                        <svg width="100" height="100" viewBox="0 0 100 100" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M8.33301 97.918H91.6663M36.0247 62.5013C38.0026 64.7119 40.4768 66.4216 43.244 67.49C46.0112 68.5584 48.9924 68.9551 51.9427 68.6474C54.893 68.3397 57.7282 67.3364 60.2153 65.7199C62.7025 64.1035 64.7706 61.92 66.2499 59.3488C67.7291 56.7777 68.5772 53.8923 68.7245 50.9297C68.8718 47.967 68.3141 45.0117 67.0972 42.3065C65.8803 39.6014 64.039 37.2235 61.7244 35.3683C59.4099 33.5131 56.6882 32.2335 53.783 31.6346M53.783 31.6346L43.7497 41.668L29.1663 27.0846L51.958 4.29297C52.6583 3.59249 53.4898 3.03683 54.4049 2.65773C55.32 2.27862 56.3008 2.0835 57.2913 2.0835C58.2819 2.0835 59.2627 2.27862 60.1778 2.65773C61.0929 3.03683 61.9243 3.59249 62.6247 4.29297L66.5413 8.20964C67.2418 8.90996 67.7975 9.74142 68.1766 10.6565C68.5557 11.5716 68.7508 12.5524 68.7508 13.543C68.7508 14.5335 68.5557 15.5143 68.1766 16.4294C67.7975 17.3445 67.2418 18.176 66.5413 18.8763L63.5747 21.843M53.783 31.6346L63.5747 21.843M21.3497 62.5013C23.2047 66.7391 25.9751 70.5136 29.4619 73.5537C32.9486 76.5939 37.0653 78.8242 41.5163 80.0846M41.5163 80.0846C43.9164 78.1409 46.9112 77.0802 49.9997 77.0802C53.0881 77.0802 56.0829 78.1409 58.483 80.0846M41.5163 80.0846C40.0169 81.2937 38.7973 82.8133 37.9413 84.5388L35.4163 89.5846C35.4163 89.5846 31.2497 97.918 22.9163 97.918H77.083C68.7497 97.918 64.583 89.5846 64.583 89.5846L62.058 84.5388C61.2021 82.8133 59.9824 81.2937 58.483 80.0846M27.083 39.5846L33.333 45.8346M8.33301 56.2513H45.833M63.5747 21.843C69.2887 24.6004 74.0285 29.0311 77.1644 34.5464C80.3003 40.0618 81.6839 46.4008 81.1314 52.7212C80.5788 59.0417 78.1163 65.0444 74.0709 69.932C70.0255 74.8195 64.5888 78.3605 58.483 80.0846M64.583 6.2513L69.1663 1.66797"
                                                stroke="black" stroke-opacity="0.7" />
                                        </svg>
                                    </div>
                                    <div class="empty-state-title">
                                        No Laboratory Results
                                    </div>
                                    <p class="empty-state-text">There are currently no active Laboratory Results. Check back
                                        later for
                                        updates.</p>
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
                                                    padding: 1rem;
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
                                                <?php if (isset($announcement['audience_type']) && $announcement['audience_type'] === 'public'): ?>
                                                    <span class="badge bg-[#2563EB] text-[#FFFFFF]">For All Residents</span>
                                                <?php elseif (isset($announcement['audience_type']) && $announcement['audience_type'] === 'specific'): ?>
                                                    <span class="badge bg-[#2563EB] text-[#FFFFFF]">For Specific Resident</span>
                                                <?php endif; ?>
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
                                                    <?php if ($announcement['user_status'] === 'accepted'): ?>
                                                        <span class="badge badge-accepted">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                                    fill="#10B981" />
                                                            </svg>
                                                            Accepted
                                                        </span>
                                                    <?php elseif ($announcement['user_status'] === 'dismissed'): ?>
                                                        <span class="badge badge-dismissed">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25ZM9.70625 5.29375L9.65375 5.24813C9.57411 5.18926 9.47787 5.15712 9.37884 5.15634C9.2798 5.15555 9.18306 5.18615 9.1025 5.24375L9.04375 5.29375L7.5 6.83687L5.95625 5.29313L5.90375 5.24813C5.82411 5.18926 5.72787 5.15712 5.62884 5.15634C5.5298 5.15555 5.43306 5.18615 5.3525 5.24375L5.29375 5.29375L5.24813 5.34625C5.18926 5.42589 5.15712 5.52213 5.15634 5.62116C5.15555 5.7202 5.18615 5.81694 5.24375 5.8975L5.29375 5.95625L6.83687 7.5L5.29313 9.04375L5.24813 9.09625C5.18926 9.17589 5.15712 9.27213 5.15634 9.37116C5.15555 9.4702 5.18615 9.56693 5.24375 9.6475L5.29375 9.70625L5.34625 9.75187C5.42589 9.81074 5.52213 9.84288 5.62116 9.84366C5.7202 9.84445 5.81694 9.81385 5.8975 9.75625L5.95625 9.70625L7.5 8.16313L9.04375 9.70687L9.09625 9.75187C9.17589 9.81074 9.27213 9.84288 9.37116 9.84366C9.4702 9.84445 9.56693 9.81385 9.6475 9.75625L9.70625 9.70625L9.75187 9.65375C9.81074 9.57411 9.84288 9.47787 9.84366 9.37884C9.84445 9.2798 9.81385 9.18306 9.75625 9.1025L9.70625 9.04375L8.16313 7.5L9.70687 5.95625L9.75187 5.90375C9.81074 5.82411 9.84288 5.72787 9.84366 5.62884C9.84445 5.5298 9.81385 5.43306 9.75625 5.3525L9.70625 5.29375Z"
                                                                    fill="black" fill-opacity="0.5" />
                                                            </svg>
                                                            Dismissed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-pending">
                                                            <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M12.3748 11.5172L8.17886 7.5L12.3748 3.48281C12.3772 3.48013 12.3797 3.47759 12.3824 3.4752C12.5134 3.34409 12.6026 3.17708 12.6388 2.99528C12.6749 2.81348 12.6563 2.62505 12.5854 2.45379C12.5145 2.28254 12.3944 2.13616 12.2403 2.03314C12.0862 1.93012 11.905 1.87509 11.7197 1.875H3.28218C3.09688 1.8752 2.91579 1.93032 2.76178 2.03338C2.60778 2.13644 2.48778 2.28283 2.41693 2.45406C2.34608 2.62529 2.32757 2.81368 2.36372 2.99542C2.39988 3.17717 2.48909 3.34412 2.62007 3.4752L2.62711 3.48281L6.823 7.5L2.62711 11.5172L2.62007 11.5248C2.48909 11.6559 2.39988 11.8228 2.36372 12.0046C2.32757 12.1863 2.34608 12.3747 2.41693 12.5459C2.48778 12.7172 2.60778 12.8636 2.76178 12.9666C2.91579 13.0697 3.09688 13.1248 3.28218 13.125H11.7197C11.9051 13.125 12.0863 13.0701 12.2405 12.9671C12.3947 12.8641 12.5149 12.7178 12.5859 12.5465C12.6569 12.3752 12.6755 12.1867 12.6394 12.0049C12.6033 11.823 12.514 11.656 12.383 11.5248C12.3801 11.5224 12.3773 11.5199 12.3748 11.5172ZM11.7197 2.8125L7.50093 6.85078L3.28218 2.8125H11.7197ZM3.28218 12.1875L7.50093 8.14922L11.7197 12.1875H3.28218Z"
                                                                    fill="#FD8802" />
                                                            </svg>
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

                            <!-- NO ANNOUNCEMENT MESSAGE -->
                            <?php if (empty($labResults) && empty($basicAnnouncements)): ?>
                                <div class="text-center py-10 sm:py-20">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-bullhorn icon-3xl"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Announcements</h3>
                                    <p class="empty-state-text">There are currently no active announcements. Check back
                                        later for
                                        updates.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
                    <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10.875 12C10.875 11.7775 10.941 11.56 11.0646 11.375C11.1882 11.19 11.3639 11.0458 11.5695 10.9606C11.7751 10.8755 12.0013 10.8532 12.2195 10.8966C12.4377 10.94 12.6382 11.0472 12.7955 11.2045C12.9529 11.3618 13.06 11.5623 13.1034 11.7805C13.1468 11.9988 13.1245 12.225 13.0394 12.4305C12.9542 12.6361 12.81 12.8118 12.625 12.9354C12.44 13.059 12.2225 13.125 12 13.125C11.7017 13.125 11.4155 13.0065 11.2045 12.7955C10.9936 12.5845 10.875 12.2984 10.875 12ZM7.87503 13.125C8.09753 13.125 8.31504 13.059 8.50004 12.9354C8.68505 12.8118 8.82924 12.6361 8.91439 12.4305C8.99954 12.225 9.02182 11.9988 8.97841 11.7805C8.935 11.5623 8.82785 11.3618 8.67052 11.2045C8.51319 11.0472 8.31273 10.94 8.0945 10.8966C7.87627 10.8532 7.65007 10.8755 7.44451 10.9606C7.23894 11.0458 7.06324 11.19 6.93962 11.375C6.81601 11.56 6.75003 11.7775 6.75003 12C6.75003 12.2984 6.86855 12.5845 7.07953 12.7955C7.29051 13.0065 7.57666 13.125 7.87503 13.125ZM16.125 13.125C16.3475 13.125 16.565 13.059 16.75 12.9354C16.935 12.8118 17.0792 12.6361 17.1644 12.4305C17.2495 12.225 17.2718 11.9988 17.2284 11.7805C17.185 11.5623 17.0779 11.3618 16.9205 11.2045C16.7632 11.0472 16.5627 10.94 16.3445 10.8966C16.1263 10.8532 15.9001 10.8755 15.6945 10.9606C15.4889 11.0458 15.3132 11.19 15.1896 11.375C15.066 11.56 15 11.7775 15 12C15 12.2984 15.1186 12.5845 15.3295 12.7955C15.5405 13.0065 15.8267 13.125 16.125 13.125ZM21.75 6V18C21.75 18.3978 21.592 18.7794 21.3107 19.0607C21.0294 19.342 20.6479 19.5 20.25 19.5H7.78128L4.72503 22.14L4.71659 22.1466C4.44662 22.3755 4.10397 22.5008 3.75003 22.5C3.52968 22.4995 3.31211 22.4509 3.11253 22.3575C2.85365 22.2379 2.63468 22.0462 2.48174 21.8055C2.3288 21.5648 2.24836 21.2852 2.25003 21V6C2.25003 5.60218 2.40806 5.22064 2.68937 4.93934C2.97067 4.65804 3.3522 4.5 3.75003 4.5H20.25C20.6479 4.5 21.0294 4.65804 21.3107 4.93934C21.592 5.22064 21.75 5.60218 21.75 6ZM20.25 6H3.75003V21L7.00971 18.1875C7.14523 18.068 7.31934 18.0014 7.50003 18H20.25V6Z" fill="#3C96E1"/>
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
                    <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M44.6172 17.4502L25.8672 4.95023C25.6104 4.77892 25.3087 4.6875 25 4.6875C24.6913 4.6875 24.3896 4.77892 24.1328 4.95023L5.38281 17.4502C5.16877 17.593 4.99331 17.7865 4.87201 18.0134C4.75071 18.2403 4.68733 18.4937 4.6875 18.751V39.0635C4.6875 39.8923 5.01674 40.6872 5.60279 41.2732C6.18884 41.8593 6.9837 42.1885 7.8125 42.1885H42.1875C43.0163 42.1885 43.8112 41.8593 44.3972 41.2732C44.9833 40.6872 45.3125 39.8923 45.3125 39.0635V18.751C45.3127 18.4937 45.2493 18.2403 45.128 18.0134C45.0067 17.7865 44.8312 17.593 44.6172 17.4502ZM18.8906 29.6885L7.8125 37.501V21.7842L18.8906 29.6885ZM22.0879 31.251H27.9121L38.9727 39.0635H11.0273L22.0879 31.251ZM31.1094 29.6885L42.1875 21.7842V37.501L31.1094 29.6885ZM25 8.12797L40.998 18.794L27.9121 28.126H22.0918L9.00586 18.794L25 8.12797Z"
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
                    <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM17.25 20.25H6.75V16.875L12 12.9375L17.25 16.9078V20.25ZM17.25 7.09125L12 11.0625L6.75 7.125V3.75H17.25V7.09125Z" fill="#D97706"/>
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
                    <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M40.625 6.25H9.375C8.5462 6.25 7.75134 6.57924 7.16529 7.16529C6.57924 7.75134 6.25 8.5462 6.25 9.375V40.625C6.25 41.4538 6.57924 42.2487 7.16529 42.8347C7.75134 43.4208 8.5462 43.75 9.375 43.75H40.625C41.4538 43.75 42.2487 43.4208 42.8347 42.8347C43.4208 42.2487 43.75 41.4538 43.75 40.625V9.375C43.75 8.5462 43.4208 7.75134 42.8347 7.16529C42.2487 6.57924 41.4538 6.25 40.625 6.25ZM40.625 40.625H9.375V9.375H40.625V40.625ZM32.3555 19.8555L27.209 25L32.3555 30.1445C32.5006 30.2897 32.6158 30.462 32.6944 30.6517C32.7729 30.8414 32.8134 31.0447 32.8134 31.25C32.8134 31.4553 32.7729 31.6586 32.6944 31.8483C32.6158 32.038 32.5006 32.2103 32.3555 32.3555C32.2103 32.5006 32.038 32.6158 31.8483 32.6944C31.6586 32.7729 31.4553 32.8134 31.25 32.8134C31.0447 32.8134 30.8414 32.7729 30.6517 32.6944C30.462 32.6158 30.2897 32.5006 30.1445 32.3555L25 27.209L19.8555 32.3555C19.7103 32.5006 19.538 32.6158 19.3483 32.6944C19.1586 32.7729 18.9553 32.8134 18.75 32.8134C18.5447 32.8134 18.3414 32.7729 18.1517 32.6944C17.962 32.6158 17.7897 32.5006 17.6445 32.3555C17.4994 32.2103 17.3842 32.038 17.3056 31.8483C17.2271 31.6586 17.1866 31.4553 17.1866 31.25C17.1866 31.0447 17.2271 30.8414 17.3056 30.6517C17.3842 30.462 17.4994 30.2897 17.6445 30.1445L22.791 25L17.6445 19.8555C17.3513 19.5623 17.1866 19.1646 17.1866 18.75C17.1866 18.3354 17.3513 17.9377 17.6445 17.6445C17.9377 17.3513 18.3354 17.1866 18.75 17.1866C19.1646 17.1866 19.5623 17.3513 19.8555 17.6445L25 22.791L30.1445 17.6445C30.2897 17.4994 30.462 17.3842 30.6517 17.3056C30.8414 17.2271 31.0447 17.1866 31.25 17.1866C31.4553 17.1866 31.6586 17.2271 31.8483 17.3056C32.038 17.3842 32.2103 17.4994 32.3555 17.6445C32.5006 17.7897 32.6158 17.962 32.6944 18.1517C32.7729 18.3414 32.8134 18.5447 32.8134 18.75C32.8134 18.9553 32.7729 19.1586 32.6944 19.3483C32.6158 19.538 32.5006 19.7103 32.3555 19.8555Z"
                            fill="black" fill-opacity="0.5" />
                    </svg>
                </div>
            </div>
            <div class="stat-label mt-2">Dismissed</div>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-xl font-medium text-gray-600 mb-4 border-b-2 pb-6"
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
                                <?php if (isset($a['audience_type']) && $a['audience_type'] === 'public'): ?>
                                    <span class="badge bg-[#2563EB] text-[#FFFFFF]">For All Residents</span>
                                <?php elseif (isset($a['audience_type']) && $a['audience_type'] === 'specific'): ?>
                                    <span class="badge bg-[#2563EB] text-[#FFFFFF]">For Specific Resident</span>
                                <?php endif; ?>
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
                            <!-- STATUS - FIXED: Now correctly shows announcement type badge with response status -->
                            <div class="flex items-center gap-2">
                                <?php if ($a['user_status'] === 'accepted'): ?>
                                    <span class="badge badge-accepted">
                                        <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.1754 5.76211C10.219 5.80564 10.2536 5.85734 10.2771 5.91425C10.3007 5.97115 10.3129 6.03215 10.3129 6.09375C10.3129 6.15535 10.3007 6.21635 10.2771 6.27325C10.2536 6.33016 10.219 6.38186 10.1754 6.42539L6.89414 9.70664C6.85061 9.75022 6.79891 9.7848 6.74201 9.80839C6.6851 9.83198 6.6241 9.84412 6.5625 9.84412C6.5009 9.84412 6.4399 9.83198 6.383 9.80839C6.32609 9.7848 6.2744 9.75022 6.23086 9.70664L4.82461 8.30039C4.73665 8.21243 4.68724 8.09314 4.68724 7.96875C4.68724 7.84436 4.73665 7.72507 4.82461 7.63711C4.91257 7.54915 5.03186 7.49974 5.15625 7.49974C5.28064 7.49974 5.39994 7.54915 5.48789 7.63711L6.5625 8.7123L9.51211 5.76211C9.55565 5.71853 9.60734 5.68395 9.66425 5.66036C9.72115 5.63677 9.78215 5.62463 9.84375 5.62463C9.90535 5.62463 9.96635 5.63677 10.0233 5.66036C10.0802 5.68395 10.1319 5.71853 10.1754 5.76211ZM13.5938 7.5C13.5938 8.70523 13.2364 9.88339 12.5668 10.8855C11.8972 11.8876 10.9455 12.6687 9.83198 13.1299C8.71849 13.5911 7.49324 13.7118 6.31117 13.4767C5.1291 13.2415 4.0433 12.6612 3.19107 11.8089C2.33884 10.9567 1.75847 9.8709 1.52334 8.68883C1.28821 7.50676 1.40889 6.28151 1.87011 5.16802C2.33133 4.05454 3.11238 3.10282 4.1145 2.43323C5.11661 1.76364 6.29477 1.40625 7.5 1.40625C9.11564 1.40796 10.6646 2.05052 11.807 3.19295C12.9495 4.33538 13.592 5.88436 13.5938 7.5ZM12.6563 7.5C12.6563 6.48019 12.3538 5.48328 11.7873 4.63534C11.2207 3.7874 10.4154 3.12651 9.47321 2.73625C8.53103 2.34598 7.49428 2.24387 6.49407 2.44283C5.49385 2.64178 4.5751 3.13287 3.85398 3.85398C3.13287 4.5751 2.64178 5.49385 2.44283 6.49407C2.24387 7.49428 2.34598 8.53103 2.73625 9.47321C3.12651 10.4154 3.7874 11.2207 4.63534 11.7873C5.48328 12.3538 6.48019 12.6562 7.5 12.6562C8.86705 12.6547 10.1777 12.111 11.1443 11.1443C12.111 10.1777 12.6547 8.86705 12.6563 7.5Z"
                                                fill="#10B981" />
                                        </svg>
                                        Accepted
                                    </span>
                                <?php elseif ($a['user_status'] === 'dismissed'): ?>
                                    <span class="badge badge-dismissed">
                                        <svg class="w-6 h-6" viewBox="0 0 15 15" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25ZM9.70625 5.29375L9.65375 5.24813C9.57411 5.18926 9.47787 5.15712 9.37884 5.15634C9.2798 5.15555 9.18306 5.18615 9.1025 5.24375L9.04375 5.29375L7.5 6.83687L5.95625 5.29313L5.90375 5.24813C5.82411 5.18926 5.72787 5.15712 5.62884 5.15634C5.5298 5.15555 5.43306 5.18615 5.3525 5.24375L5.29375 5.29375L5.24813 5.34625C5.18926 5.42589 5.15712 5.52213 5.15634 5.62116C5.15555 5.7202 5.18615 5.81694 5.24375 5.8975L5.29375 5.95625L6.83687 7.5L5.29313 9.04375L5.24813 9.09625C5.18926 9.17589 5.15712 9.27213 5.15634 9.37116C5.15555 9.4702 5.18615 9.56693 5.24375 9.6475L5.29375 9.70625L5.34625 9.75187C5.42589 9.81074 5.52213 9.84288 5.62116 9.84366C5.7202 9.84445 5.81694 9.81385 5.8975 9.75625L5.95625 9.70625L7.5 8.16313L9.04375 9.70687L9.09625 9.75187C9.17589 9.81074 9.27213 9.84288 9.37116 9.84366C9.4702 9.84445 9.56693 9.81385 9.6475 9.75625L9.70625 9.70625L9.75187 9.65375C9.81074 9.57411 9.84288 9.47787 9.84366 9.37884C9.84445 9.2798 9.81385 9.18306 9.75625 9.1025L9.70625 9.04375L8.16313 7.5L9.70687 5.95625L9.75187 5.90375C9.81074 5.82411 9.84288 5.72787 9.84366 5.62884C9.84445 5.5298 9.81385 5.43306 9.75625 5.3525L9.70625 5.29375Z"
                                                fill="black" fill-opacity="0.5" />
                                        </svg>
                                        Dismissed
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- VIEW -->
                            <div class="announcement-actions">
                                <button
                                                            onclick="openViewModal(<?= htmlspecialchars(json_encode($a, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>, 'stats', false)"
                                    class="btn-primary" style="background: #2563eb;">
                                    View
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>
        <?php if ($acceptedCount + $dismissedCount === 0): ?>
            <div class="flex flex-col items-center justify-center text-center py-10 sm:py-20 min-h-[300px]">
                <div class="mb-4">
                    <svg width="100" height="100" viewBox="0 0 100 100" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M18.3751 14.7695C9.93758 21.832 13.0001 33.3529 17.2084 37.6862M42.1251 47.9154C59.3543 48.4987 70.7293 46.0612 79.8334 37.4987C89.4793 28.4154 87.9376 21.5195 83.4584 17.4987C72.9168 8.10286 44.0834 28.3529 31.2501 42.832C22.5001 52.7279 9.45842 70.6445 18.4168 80.332C29.0001 91.7695 49.4167 81.1654 63.6459 68.707"
                            stroke="black" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3 class="empty-state-title">No Responded Announcement Yet.</h3>
                <p class="empty-state-text">There are currently no active announcements. Check back later for
                    updates.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
        </div>
    </div>
    </div>

    <!-- View Announcement Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal announcement-detail-modal">
            <div class="modal-header">
                <h2 class="modal-title">Announcement Details</h2>
                <button class="modal-close" onclick="closeViewModal()" title="Close">
                    <svg class="modal-close-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <line x1="5" y1="5" x2="19" y2="19"></line>
                        <line x1="19" y1="5" x2="5" y2="19"></line>
                    </svg>
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
        function escapeHtml(value) {
            const stringValue = value == null ? '' : String(value);
            return stringValue
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatAnnouncementDate(value) {
            if (!value) {
                return '';
            }

            const parsedDate = new Date(value);
            if (Number.isNaN(parsedDate.getTime())) {
                return '';
            }

            return parsedDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // View Announcement Modal
        function openViewModal(announcement, activeMainTab = 'announcements', showActions = true) {
            const modal = document.getElementById('viewModal');
            const modalContent = document.getElementById('modalContent');
            const activeSubTab = announcement.announcement_type === 'lab_result' ? 'lab' : 'general';
            const selectedMainTab = activeMainTab === 'stats' ? 'stats' : 'announcements';
            const responseStatus = announcement.user_status === 'accepted'
                ? 'Accepted'
                : (announcement.user_status === 'dismissed' ? 'Dismissed' : 'No response yet');
            const safeTitle = escapeHtml(announcement.title || 'Announcement');
            const safeMessage = escapeHtml(announcement.message || 'No message provided');
            const safeRole = escapeHtml(announcement.staff_position || 'Staff');
            const safeName = escapeHtml(announcement.staff_name || 'Unknown');
            const postDate = formatAnnouncementDate(announcement.post_date) || 'N/A';
            const expiryDate = formatAnnouncementDate(announcement.expiry_date) || 'N/A';

            let content = `
                <div class="announcement-detail-title-row">
                    <h3 class="announcement-detail-title">${safeTitle}</h3>
                    <div class="announcement-detail-pill date">Date Posted : ${escapeHtml(postDate)}</div>
                </div>

                <div class="announcement-detail-meta">
                    <div>
                        <div class="announcement-detail-posted">Posted By : <span class="announcement-detail-role">${safeRole}</span></div>
                        <div class="announcement-detail-name">${safeName}</div>
                    </div>
                    <div>
                        <div class="announcement-detail-pill expiry">Expiration Date : ${escapeHtml(expiryDate)}</div>
                    </div>
                </div>

                <div class="announcement-detail-message">${safeMessage}</div>
            `;

            if (showActions) {
                content += `
                    <form method="POST" action="" class="announcement-detail-actions">
                        <input type="hidden" name="announcement_id" value="${announcement.id}">
                        <input type="hidden" name="active_sub_tab" value="${activeSubTab}">
                        <input type="hidden" name="active_main_tab" value="${selectedMainTab}">
                        <button type="submit" name="respond_to_announcement" value="accepted" class="announcement-detail-btn accept">
                            <span class="announcement-detail-btn-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
                            Accept
                        </button>
                        <button type="submit" name="respond_to_announcement" value="dismissed" class="announcement-detail-btn dismiss">
                            <span class="announcement-detail-btn-icon"><i class="fas fa-times" aria-hidden="true"></i></span>
                            Dismiss
                        </button>
                    </form>`;
            } else {
                const badgeClass = announcement.user_status === 'accepted' ? 'accepted' : 'dismissed';
                const iconClass = announcement.user_status === 'accepted' ? 'fa-check' : 'fa-times';
                content += `
                    <div class="announcement-detail-actions" style="justify-content:flex-start;">
                        <span class="announcement-response-badge ${badgeClass}">
                            <span class="announcement-response-badge-icon" aria-hidden="true"><i class="fas ${iconClass}"></i></span>
                            ${escapeHtml(responseStatus)}
                        </span>
                    </div>`;
            }

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
            // Set active top tab based on query parameter
            setTimeout(function () {
                var params = new URLSearchParams(window.location.search);
                var requestedTab = params.get('tab') === 'stats' ? 'stats' : 'announcements';

                document.querySelectorAll('.tab-header[data-tab]').forEach(btn => {
                    btn.classList.remove('active');
                });

                var activeTabBtn = document.querySelector('.tab-header[data-tab="' + requestedTab + '"]');
                if (activeTabBtn) activeTabBtn.classList.add('active');

                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });

                var activeTabContent = document.getElementById(requestedTab);
                if (activeTabContent) activeTabContent.classList.add('active');

                var requestedSubTab = params.get('sub_tab');
                var btnLab = document.getElementById('btnLabResults');
                var btnGen = document.getElementById('btnGeneralAnnouncements');
                var labSection = document.getElementById('labResultsSection');
                var genSection = document.getElementById('generalAnnouncementsSection');

                if (requestedTab === 'announcements' && btnLab && btnGen && labSection && genSection) {
                    if (requestedSubTab === 'general') {
                        btnGen.classList.add('active');
                        btnLab.classList.remove('active');
                        genSection.style.display = '';
                        labSection.style.display = 'none';
                    } else {
                        btnLab.classList.add('active');
                        btnGen.classList.remove('active');
                        labSection.style.display = '';
                        genSection.style.display = 'none';
                    }
                }
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