<?php
require_once __DIR__ . '/../includes/auth.php';
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
$respondedCount = count(array_filter($announcements, function($a) {
    return !empty($a['user_status']);
}));
$acceptedCount = count(array_filter($announcements, function($a) {
    return $a['user_status'] === 'accepted';
}));
$dismissedCount = count(array_filter($announcements, function($a) {
    return $a['user_status'] === 'dismissed';
}));
$pendingCount = count($announcements) - $respondedCount;

// Separate counts for lab results and basic announcements
$labResultsCount = count($labResults);
$labResultsPending = count(array_filter($labResults, function($a) {
    return empty($a['user_status']);
}));
$basicAnnouncementsCount = count($basicAnnouncements);
$basicAnnouncementsPending = count(array_filter($basicAnnouncements, function($a) {
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

        .icon-xs { font-size: 0.75rem; }
        .icon-sm { font-size: 0.875rem; }
        .icon-base { font-size: 1rem; }
        .icon-lg { font-size: 1.125rem; }
        .icon-xl { font-size: 1.25rem; }
        .icon-2xl { font-size: 1.5rem; }
        .icon-3xl { font-size: 1.875rem; }
        .icon-4xl { font-size: 2.25rem; }

        body {
            background: #f3f4f6;
            color: #1a202c;
        }

        /* Typography - Match dashboard style */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: #1f2937;
            letter-spacing: -0.025em;
        }

        p, span, a {
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
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .card-shadow:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Tab Styling - Original pill-style buttons */
        .tab-header {
            position: relative;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #e8f0fe 0%, #f0f4f8 100%);
            border: none;
            border-radius: 8px;
            color: #6b7280;
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
            border-bottom: 1px solid #f3f4f6;
            background: white;
            overflow: hidden;
            padding: 0.75rem 1rem;
            gap: 0.5rem;
        }

        @media (min-width: 640px) {
            .tab-nav-container {
                padding: 0.75rem 1.5rem;
                gap: 0.75rem;
            }
        }

        /* Tab Content - Allow scrolling only for content */
        .tab-content-wrapper {
            padding: 1.5rem;
            background: white;
            overflow-y: auto;
            max-height: calc(100vh - 400px);
        }

        @media (max-width: 640px) {
            .tab-content-wrapper {
                padding: 1rem;
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
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: 0.025em;
            text-transform: uppercase;
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
            padding: 1.75rem;
            margin-bottom: 1.5rem;
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

        .announcement-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #d1d5db;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .announcement-title {
            font-weight: 600;
            color: #111827;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }

        .announcement-meta {
            font-size: 0.8125rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .announcement-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        /* Badge Styling */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            border-radius: 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            border: 1px solid;
        }

        .badge-high {
            background: #FEE2E2;
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
            background: #F0F9FF;
            color: #0284C7;
            border-color: #BFDBFE;
        }

        .badge-accepted {
            background: #D1FAE5;
            color: #065F46;
            border-color: #A7F3D0;
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
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
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
                font-weight: 700 !important;
                min-height: 44px !important;
            }
        }
        .stats-grid {
                grid-template-columns: 1fr;
            }
    </style>

            
     
</head>
<body class="min-h-screen bg-gray-100">
    <div class="px-4 py-6 -mt-24">
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
        <div class="card-shadow overflow-hidden mb-10 mt-6">
            <!-- Tab Navigation -->
            <div class="tab-nav-container">
                <button class="tab-header active" data-tab="announcements">
                    <span>Announcements</span>
                </button>
                <button class="tab-header" data-tab="stats">
                    <span>Response Stats</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-wrapper custom-scrollbar">
                <!-- Announcements Tab -->
                <div id="announcements" class="tab-content active">
                    <!-- Stats Grid -->
                    <div class="stats-grid mb-6">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #DBEAFE; color: #0369A1;">
                                <i class="fas fa-bullhorn icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= count($announcements) ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #D1FAE5; color: #10B981;">
                                <i class="fas fa-flask icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value">
                                    <?= $labResultsCount ?> <span class="stat-pending">(<?= $labResultsPending ?> pending)</span>
                                </div>
                                <div class="stat-label">Lab Results</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
                                <i class="fas fa-bullhorn icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value">
                                    <?= $basicAnnouncementsCount ?> <span class="stat-pending">(<?= $basicAnnouncementsPending ?> pending)</span>
                                </div>
                                <div class="stat-label">Announcements</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #F3F4F6; color: #6b7280;">
                                <i class="fas fa-clock icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= $pendingCount ?></div>
                                <div class="stat-label">All Pending</div>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Results Section -->
                    <?php if (!empty($labResults)): ?>
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-green-700 flex items-center gap-2">
                                    <i class="fas fa-flask text-green-500"></i>
                                    Laboratory Results
                                    <span class="text-base font-normal text-gray-500">(<?= count($labResults) ?>)</span>
                                </h3>
                            </div>
                            <?php foreach ($labResults as $announcement): ?>
                                <div class="announcement-item card-shadow" style="border-left: 6px solid #10B981; background: linear-gradient(90deg,#F0FDF4 60%,#fff 100%);">
                                    <div class="announcement-header">
                                        <div class="flex-1">
                                            <h3 class="announcement-title text-green-700"><?= htmlspecialchars($announcement['title']) ?></h3>
                                            <div class="announcement-meta">
                                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($announcement['staff_name'] ?? 'Medical Staff') ?></span>
                                                <span class="mx-2">•</span>
                                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($announcement['post_date'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="announcement-actions">
                                            <button onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)" class="btn-primary" style="background: #10B981;">
                                                <i class="fas fa-eye icon-sm"></i> View Result
                                            </button>
                                        </div>
                                    </div>
                                    <div class="announcement-badges mb-2">
                                        <span class="badge badge-lab-result"><i class="fas fa-flask"></i> Lab Result</span>
                                        <span class="badge badge-<?= $announcement['priority'] ?>"><i class="fas fa-flag"></i> <?= ucfirst($announcement['priority']) ?></span>
                                        <?php if ($announcement['user_status']): ?>
                                            <span class="badge badge-<?= $announcement['user_status'] ?>">
                                                <i class="fas fa-<?= $announcement['user_status'] === 'accepted' ? 'check' : 'times' ?>"></i>
                                                <?= ucfirst($announcement['user_status']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="announcement-content mb-2">
                                        <?= htmlspecialchars($announcement['message']) ?>
                                    </div>
                                    <?php if ($announcement['image_path']): ?>
                                        <div class="image-preview mb-2">
                                            <img src="<?= htmlspecialchars($announcement['image_path']) ?>" alt="Lab Result Image" onclick="openImageModal('<?= htmlspecialchars($announcement['image_path']) ?>')">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($announcement['expiry_date']): ?>
                                        <div class="mb-2 text-green-700 text-sm flex items-center gap-2">
                                            <i class="fas fa-clock"></i> Expires: <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <!-- Response Section -->
                                        <?php if ($announcement['user_status']): ?>
                                            <div class="flex items-center gap-2 p-2 rounded bg-green-50 border border-green-200">
                                                <i class="fas fa-<?= $announcement['user_status'] === 'accepted' ? 'check-circle text-green-500' : 'times-circle text-gray-500' ?>"></i>
                                                <span class="font-semibold text-green-700">Response Recorded: <?= ucfirst($announcement['user_status']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex gap-2">
                                                <form method="POST" action="" class="flex-1">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                    <button type="submit" name="respond_to_announcement" value="accepted" class="btn-response btn-accept w-full"><i class="fas fa-check-circle"></i> Accept</button>
                                                </form>
                                                <form method="POST" action="" class="flex-1">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                    <button type="submit" name="respond_to_announcement" value="dismissed" class="btn-response btn-dismiss w-full"><i class="fas fa-times-circle"></i> Dismiss</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Basic Announcements Section -->
                    <?php if (!empty($basicAnnouncements)): ?>
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-blue-700 flex items-center gap-2">
                                    <i class="fas fa-bullhorn text-blue-500"></i>
                                    General Announcements
                                    <span class="text-base font-normal text-gray-500">(<?= count($basicAnnouncements) ?>)</span>
                                </h3>
                            </div>
                            <?php foreach ($basicAnnouncements as $announcement): ?>
                                <div class="announcement-item card-shadow" style="border-left: 6px solid #2563eb; background: linear-gradient(90deg,#DBEAFE 60%,#fff 100%);">
                                    <div class="announcement-header">
                                        <div class="flex-1">
                                            <h3 class="announcement-title text-blue-700"><?= htmlspecialchars($announcement['title']) ?></h3>
                                            <div class="announcement-meta">
                                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($announcement['staff_name'] ?? 'Community Staff') ?></span>
                                                <span class="mx-2">•</span>
                                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($announcement['post_date'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="announcement-actions">
                                            <button onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)" class="btn-primary" style="background: #2563eb;">
                                                <i class="fas fa-eye icon-sm"></i> View
                                            </button>
                                        </div>
                                    </div>
                                    <div class="announcement-badges mb-2">
                                        <span class="badge badge-simple"><i class="fas fa-bullhorn"></i> Announcement</span>
                                        <span class="badge badge-<?= $announcement['priority'] ?>"><i class="fas fa-flag"></i> <?= ucfirst($announcement['priority']) ?></span>
                                        <?php if ($announcement['user_status']): ?>
                                            <span class="badge badge-<?= $announcement['user_status'] ?>">
                                                <i class="fas fa-<?= $announcement['user_status'] === 'accepted' ? 'check-circle' : 'times-circle' ?>"></i>
                                                <?= ucfirst($announcement['user_status']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="announcement-content mb-2">
                                        <?= htmlspecialchars($announcement['message']) ?>
                                    </div>
                                    <?php if ($announcement['image_path']): ?>
                                        <div class="image-preview mb-2">
                                            <img src="<?= htmlspecialchars($announcement['image_path']) ?>" alt="Announcement Image" onclick="openImageModal('<?= htmlspecialchars($announcement['image_path']) ?>')">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($announcement['expiry_date']): ?>
                                        <div class="mb-2 text-blue-700 text-sm flex items-center gap-2">
                                            <i class="fas fa-clock"></i> Expires: <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <!-- Response Section -->
                                        <?php if ($announcement['user_status']): ?>
                                            <div class="flex items-center gap-2 p-2 rounded bg-blue-50 border border-blue-200">
                                                <i class="fas fa-<?= $announcement['user_status'] === 'accepted' ? 'check-circle text-green-500' : 'times-circle text-gray-500' ?>"></i>
                                                <span class="font-semibold text-blue-700">Response Recorded: <?= ucfirst($announcement['user_status']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex gap-2">
                                                <form method="POST" action="" class="flex-1">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                    <button type="submit" name="respond_to_announcement" value="accepted" class="btn-response btn-accept w-full"><i class="fas fa-check-circle"></i> Accept</button>
                                                </form>
                                                <form method="POST" action="" class="flex-1">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                    <button type="submit" name="respond_to_announcement" value="dismissed" class="btn-response btn-dismiss w-full"><i class="fas fa-times-circle"></i> Dismiss</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- No Announcements Message -->
                    <?php if (empty($labResults) && empty($basicAnnouncements)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <i class="fas fa-bullhorn icon-3xl"></i>
                            </div>
                            <h3 class="empty-state-title">No Announcements</h3>
                            <p class="empty-state-text">There are currently no active announcements. Check back later for updates.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Stats Tab -->
                <div id="stats" class="tab-content">
                    <div class="stats-grid mb-8">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #DBEAFE; color: #0369A1;">
                                <i class="fas fa-bullhorn icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= count($announcements) ?></div>
                                <div class="stat-label">Total Announcements</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #D1FAE5; color: #10B981;">
                                <i class="fas fa-check-circle icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= $acceptedCount ?></div>
                                <div class="stat-label">Accepted</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
                                <i class="fas fa-clock icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= $pendingCount ?></div>
                                <div class="stat-label">Pending Response</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #F3F4F6; color: #6b7280;">
                                <i class="fas fa-times-circle icon-lg"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?= $dismissedCount ?></div>
                                <div class="stat-label">Dismissed</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Your Responded Announcements</h3>
                        <div class="space-y-4">
                        <?php foreach ($announcements as $a): if (!empty($a['user_status'])): ?>
                            <div class="card-shadow p-4 rounded flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-base text-gray-900"><?= htmlspecialchars($a['title']) ?></span>
                                    <?php if ($a['user_status'] === 'accepted'): ?>
                                        <span class="badge badge-accepted"><i class="fas fa-check-circle"></i> Accepted</span>
                                    <?php elseif ($a['user_status'] === 'dismissed'): ?>
                                        <span class="badge badge-dismissed"><i class="fas fa-times-circle"></i> Dismissed</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($a['post_date'])) ?>
                                </div>
                                <div class="text-sm text-gray-700"><?= htmlspecialchars($a['message']) ?></div>
                            </div>
                        <?php endif; endforeach; ?>
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
                                    <p style="font-weight: 600; color: #0C4A6E; margin: 0;">Expires: ${new Date(announcement.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric'})}</p>
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
            downloadBtn.onclick = function() {
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
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewModal();
            }
        });

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeImageModal();
            }
        });

        // Initialize animation for announcement cards
        document.addEventListener('DOMContentLoaded', function() {
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
            document.querySelectorAll('.tab-header').forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    document.querySelectorAll('.tab-header').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>