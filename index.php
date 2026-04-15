<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

function ensureAnnouncementViewsTable(PDO $pdo)
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS announcement_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        announcement_id INT NOT NULL,
        viewer_token VARCHAR(128) NOT NULL,
        viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_announcement_view (announcement_id, viewer_token),
        KEY idx_announcement_id (announcement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $initialized = true;
}

function getAnnouncementViewerToken()
{
    if (empty($_SESSION['announcement_viewer_token'])) {
        $_SESSION['announcement_viewer_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['announcement_viewer_token'];
}

try {
    ensureAnnouncementViewsTable($pdo);
} catch (PDOException $e) {
    error_log("Error ensuring announcement views table: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_announcement_view'])) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');

    try {
        ensureAnnouncementViewsTable($pdo);

        $announcementId = isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0;
        if ($announcementId <= 0) {
            throw new InvalidArgumentException('Invalid announcement ID');
        }

        $stmt = $pdo->prepare("SELECT id FROM sitio1_announcements
                               WHERE id = ? AND status = 'active' AND audience_type = 'landing_page'
                               AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $stmt->execute([$announcementId]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException('Announcement not found');
        }

        $viewerToken = getAnnouncementViewerToken();
        $stmt = $pdo->prepare("INSERT IGNORE INTO announcement_views (announcement_id, viewer_token) VALUES (?, ?)");
        $stmt->execute([$announcementId, $viewerToken]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcement_views WHERE announcement_id = ?");
        $stmt->execute([$announcementId]);

        echo json_encode([
            'success' => true,
            'seen_count' => (int)$stmt->fetchColumn()
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit();
}

// Fetch active announcements for landing page
$announcements = [];
$hasAnnouncements = false;
// Fetch Medical Staff count
$medicalStaffCount = 0;
// Fetch Residents Served count
$residentsServedCount = 0;
try {
    $stmt = $pdo->prepare("SELECT a.id, a.title, a.message, a.priority, a.post_date, a.expiry_date, a.image_path,
                          COALESCE(av.seen_count, 0) AS seen_count
                          FROM sitio1_announcements 
                          a
                          LEFT JOIN (
                              SELECT announcement_id, COUNT(*) AS seen_count
                              FROM announcement_views
                              GROUP BY announcement_id
                          ) av ON av.announcement_id = a.id
                          WHERE a.status = 'active' AND a.audience_type = 'landing_page' 
                          AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
                          ORDER BY 
                            CASE a.priority 
                                WHEN 'high' THEN 1
                                WHEN 'medium' THEN 2
                                WHEN 'normal' THEN 3
                            END,
                            a.post_date DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasAnnouncements = !empty($announcements);

    // Get Medical Staff count
    $stmtStaff = $pdo->query("SELECT COUNT(*) FROM sitio1_staff");
    $medicalStaffCount = (int)$stmtStaff->fetchColumn();

    // Get Residents Served count
    $stmtPatients = $pdo->query("SELECT COUNT(*) FROM sitio1_patients");
    $residentsServedCount = (int)$stmtPatients->fetchColumn();

    // Get current month's consultation count
    $stmtConsultations = $pdo->prepare("SELECT COUNT(*) FROM consultation_notes WHERE MONTH(consultation_date) = MONTH(CURRENT_DATE()) AND YEAR(consultation_date) = YEAR(CURRENT_DATE())");
    $stmtConsultations->execute();
    $monthlyConsultationCount = (int)$stmtConsultations->fetchColumn();
} catch (PDOException $e) {
    // Silently fail - announcements are not critical for page load
    error_log("Error fetching announcements: " . $e->getMessage());
}

// Function to check if today is a weekend
function isWeekend()
{
    $dayOfWeek = date('N'); // 1-7, Monday to Sunday
    return $dayOfWeek >= 6; // 6 = Saturday, 7 = Sunday
}

// Determine availability status
$isWeekend = isWeekend();
$badgeStatus = $isWeekend ? 'Closed' : 'Open Now';
$badgeClass = $isWeekend ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="icon" type="image/png" href="/community-health-tracker/asssets/images/finallogo.png"> -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Show login modal and error if redirected with ?login=invalid
        document.addEventListener('DOMContentLoaded', function() {
            initializeLanguage();
            const params = new URLSearchParams(window.location.search);
            const hasInvalidLogin = params.get('login') === 'invalid';

            if (hasInvalidLogin) {
                openLoginModal();
                if (window.history && typeof window.history.replaceState === 'function') {
                    params.delete('login');
                    const newQuery = params.toString();
                    const newUrl = `${window.location.pathname}${newQuery ? '?' + newQuery : ''}${window.location.hash}`;
                    window.history.replaceState({}, document.title, newUrl);
                }
            }
            
            // Show instruction modal automatically on every page refresh
            if (!hasInvalidLogin) {
                setTimeout(function() {
                    openInstructionModal();
                }, 500);
            }
        });
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --warm-blue: #3a7bd5;
            --warm-blue-light: #4a90e2;
            --warm-blue-dark: #2a6bc5;
            --off-white: #f8fafc;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
            /* Add scroll padding for anchor links */
        }

        body {
            background-color: var(--off-white);
            margin: 0;
            padding: 0;
        }

        .warm-blue-bg {
            background-color: var(--warm-blue);
        }

        .warm-blue-light-bg {
            background-color: var(--warm-blue-light);
        }

        .warm-blue-text {
            color: var(--warm-blue);
        }

        .section-title01 {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .section-title01::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background-color: var(--warm-blue);
            border-radius: 30px;
        }

        .section-title02 {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .section-title02::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background-color: white;
            border-radius: 30px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 15px rgba(58, 123, 213, 0.1);
            border: 1px solid rgba(58, 123, 213, 0.1);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(58, 123, 213, 0.15);
        }

        .quick-info-wrap {
            position: relative;
            z-index: 1;
        }

        .quick-info-card {
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.82), rgba(242, 248, 255, 0.72));
            border: 1px solid rgba(255, 255, 255, 0.24);
            box-shadow: 0 22px 48px rgba(12, 43, 78, 0.2);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }

        .quick-info-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 28px 56px rgba(8, 34, 63, 0.26);
            border-color: rgba(255, 255, 255, 0.34);
        }

        .quick-info-header {
            background: linear-gradient(135deg, rgba(47, 111, 197, 0.94), rgba(72, 145, 226, 0.92));
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .quick-info-body {
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.78), rgba(240, 247, 255, 0.68));
            color: #16324b;
        }

        .quick-info-label {
            color: #6f87a0;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .quick-info-value {
            color: #17314a;
            font-weight: 600;
        }

        .quick-info-badge {
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }

        .quick-info-map {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(101, 147, 201, 0.24);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.55);
        }

        .quick-info-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 124px;
            padding: 0.8rem 1.1rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #3d82de, #5c9cf0);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 12px 24px rgba(61, 130, 222, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .quick-info-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(61, 130, 222, 0.28);
            filter: saturate(1.05);
        }

        .service-icon {
            width: 60px;
            height: 90px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .announcement-priority {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .floating-announcement-container {
            z-index: 9999;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        /* Header Styles - FIXED with proper padding */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #F8FAFC;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            padding-top: 1rem;
            padding-bottom: 1rem;
            height: auto;
        }

        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .mobile-menu.show {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }

        .touch-target {
            min-width: 44px;
            min-height: 44px;
        }

        .circle-image {
            width: 70px;
    height: 70px;
    object-fit: contain;
            /* border: 3px solid #4A90E2; */
        }

        .logo-text {
            line-height: 1.2;
        }

        .complete-btn {
            padding: 12px 28px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .complete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }

        .nav-link {
            position: relative;
            padding: 8px 0;
        }

        .nav-link.active {
            color: #010489;
        }

        /* Modal Styles */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            transition: opacity 0.3s ease-in-out;
        }

        .modal-content {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            transform: scale(0.95);
            opacity: 0;
        }

        .modal-content.open {
            transform: scale(1);
            opacity: 1;
        }

        .modal-close-btn {
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .modal-close-btn:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: rotate(90deg);
        }

        /* Login Modal Specific */
        .login-modal-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Instruction Modal Specific */
        .instruction-modal-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(58, 123, 213, 0.2);
        }

        /* SECTION STYLES - PROPER SPACING */
        section {
            width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
        }

        /* Hero Section - Full height with proper spacing from header */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            padding-top: 100px;
            /* Match this with header height */
        }

        /* Content sections with visual hierarchy */
        .section-padding {
            padding: 6rem 0;
        }

        .section-padding-lg {
            padding: 8rem 0;
        }

        .section-padding-md {
            padding: 4rem 0;
        }

        /* Ensure proper scroll margin for anchor links */
        section[id] {
            scroll-margin-top: 100px;
            /* Adjust based on actual header height */
        }

        /* Remove any default margins and paddings */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Consistent spacing for content within sections */
        .content-spacing>*+* {
            margin-top: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                min-height: calc(100vh - 80px);
                padding-top: 80px;
            }

            .section-padding {
                padding: 4rem 0;
            }

            .section-padding-lg {
                padding: 5rem 0;
            }

            .section-padding-md {
                padding: 3rem 0;
            }

            section[id] {
                scroll-margin-top: 80px;
            }

            html {
                scroll-padding-top: 80px;
            }
        }

        @media (max-width: 640px) {
            .section-padding {
                padding: 3rem 0;
            }

            .section-padding-lg {
                padding: 4rem 0;
            }

            .section-padding-md {
                padding: 2.5rem 0;
            }
        }

        /* Image background for hero with blur effect */
        .hero-gradient {
            position: relative;
            background: linear-gradient(
  135deg,
  rgba(30, 60, 200, 0.85) 0%,
  rgba(1, 4, 137, 0.85) 100%
), url('./asssets/images/cecmainbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Services Section Background */
        .services-bg {
            position: relative;
            background: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), url('./asssets/images/cecmainbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Announcements Section Background */
        .announcements-bg {
            position: relative;
            background: linear-gradient(
  135deg,
  rgba(30, 60, 200, 0.85) 0%,
  rgba(1, 4, 137, 0.85) 100%
), url('./asssets/images/cecmainbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* About Section Background */
        .about-bg {
            position: relative;
            background: linear-gradient(rgba(255, 255, 255, 0.93), rgba(255, 255, 255, 0.93)), url('./asssets/images/cecmainbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Smooth transitions */
        .transition-all {
            transition: all 0.3s ease;
        }

        /* Card hover effects */
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(58, 123, 213, 0.15);
        }

        /* Floating button animation */
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Better spacing for content */
        .grid-spacing {
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .grid-spacing {
                gap: 1.5rem;
            }
        }

        /* Consistent button styling */
        .btn-primary {
            background-color: white;
            color: var(--warm-blue);
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(58, 123, 213, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--warm-blue);
            border: 2px solid var(--warm-blue);
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: var(--warm-blue);
            color: white;
            transform: translateY(-2px);
        }

        /* Consistent text sizes */
        .text-lead {
            font-size: 1.125rem;
            line-height: 1.7;
            color: #4a5568;
        }

        /* Image styling */
        .responsive-img {
            width: 100%;
            height: auto;
            max-width: 100%;
            border-radius: 0.5rem;
        }

        /* Form styling */
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--warm-blue);
            box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.1);
        }

        /* Header mobile menu positioning */
        #mobile-menu {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            background: white;
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Instruction list styling */
        .instruction-list {
            list-style: none;
            padding-left: 0;
        }

        .instruction-list li {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .instruction-list li:before {
            content: "🔒";
            position: absolute;
            left: 0;
            color: var(--warm-blue);
            font-size: 1.1rem;
        }
        .language-select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.7rem 2.5rem 0.7rem 0.9rem;
            background: #fff;
            color: #1f2937;
            font-size: 0.95rem;
            font-weight: 500;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
        }

        .language-select-wrap {
            position: relative;
        }

        .language-select-wrap::after {
            content: '\25BE';
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #4b5563;
            pointer-events: none;
            font-size: 0.8rem;
        }
    </style>
    <script>
        // Store announcements data for modal display
        const announcementsData = <?php echo json_encode($announcements); ?>;
    </script>
</head>

<body class="font-sans antialiased">
    <!-- Header Navigation - FIXED AT TOP -->
    <header class="main-header">
        <div class="header-content">
            <nav class="text-black">
                <div class=" px-4 sm:px-8 md:px-16 lg:px-18">
                    <div class="flex justify-between items-center">
                        <!-- Logo/Title with two-line text -->
                        <div class="flex items-center">
                            <img src="./asssets/images/finallogo.png" alt="Cebu Eastern College Logo"
                                class="circle-image mr-4">
                            <div class="logo-text">
                                <div class="text-[#010489] font-medium text-xl leading-tight">Cebu Eastern College Inc.</div>
                                <div class="text-md text-gray-700">Clinic Monitoring and Tracking</div>
                            </div>
                        </div>

                        <!-- Mobile menu button - hidden on desktop -->
                        <button class="md:hidden touch-target p-2" onclick="toggleMobileMenu()">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <!-- Desktop navigation - centered nav list -->
                        <div class="hidden md:flex items-center flex-1 justify-center">
                            <!-- Centered nav links -->
                            <ul class="flex items-center space-x-14 font-md">
                                <li>
                                    <a href="#home"
                                        class="nav-link text-[#010489] hover:text-[#010489] transition-all duration-300 ease-in-out" data-i18n="navHome">Home</a>
                                </li>
                                <li>
                                    <a href="#about"
                                        class="nav-link text-[#010489] hover:text-[#010489] transition-all duration-300 ease-in-out" data-i18n="navAbout">About</a>
                                </li>
                                <li>
                                    <a href="#services"
                                        class="nav-link text-[#010489] hover:text-[#010489] transition-all duration-300 ease-in-out" data-i18n="navServices">Services</a>
                                </li>
                                <li>
                                    <a href="#footer"
                                        class="nav-link text-[#010489] hover:text-[#010489] transition-all duration-300 ease-in-out" data-i18n="navContact">Contact</a>
                                </li>

                            </ul>
                        </div>

                        <!-- Login button - positioned to the right -->
                        <div class="hidden md:flex items-center gap-3">
                            <div class="language-select-wrap">
                                <select id="languageSwitcher" class="language-select" aria-label="Choose language">
                                    <option value="en">English</option>
                                    <option value="ceb">Bisaya</option>
                                    <option value="tl">Tagalog</option>
                                </select>
                            </div>
                            <a href="#" onclick="openLoginModal()"
                                class="bg-[#010489] text-lg rounded-lg text-white flex items-center justify-center shadow-md hover:shadow-lg px-6 py-3">
                                Student Login
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Mobile menu content - only shows on mobile -->
        <div id="mobile-menu" class="mobile-menu md:hidden bg-white border-t border-gray-200 shadow-lg">
            <div class="px-4 pt-4 pb-6 space-y-2">
                <a href="#home" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link" data-i18n="navHome">Home</a>
                <a href="#about" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link" data-i18n="navAbout">About</a>
                <a href="#services" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link" data-i18n="navServices">Services</a>
                <a href="#footer" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link" data-i18n="navContact">Contact</a>
                <div class="px-4 py-2">
                    <div class="language-select-wrap w-full">
                        <select id="languageSwitcherMobile" class="language-select w-full" aria-label="Choose language">
                            <option value="en">English</option>
                            <option value="ceb">Bisaya</option>
                            <option value="tl">Tagalog</option>
                        </select>
                    </div>
                </div>
                <a href="#" onclick="openLoginModal(); toggleMobileMenu();"
                    class="complete-btn bg-[#010489] text-white px-5 py-3 transition-all text-center mt-4 flex items-center justify-center gap-2 nav-link shadow-md hover:shadow-lg" data-i18n="residentLoginShort">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content - Starts right after header -->
    <main class="content-wrapper">
        <!-- Floating Announcement Icon -->
        <?php if ($hasAnnouncements): ?>
            <div id="floatingAnnouncement" class="floating-announcement-container fixed bottom-6 right-6 z-[9999]">
                <div class="relative">
                    <?php
                    $hasHighPriority = false;
                    foreach ($announcements as $announcement) {
                        if ($announcement['priority'] == 'high') {
                            $hasHighPriority = true;
                            break;
                        }
                    }
                    ?>

                    <?php if ($hasHighPriority): ?>
                        <div class="absolute -top-1 -right-1 z-10">
                            <div class="relative">
                                <div class="animate-ping absolute inline-flex h-4 w-4 rounded-full bg-red-500 opacity-75"></div>
                                <div class="relative inline-flex rounded-full h-4 w-4 bg-red-600"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button onclick="scrollToAnnouncements()"
                        class="w-14 h-14 bg-gradient-to-br from-[#010489] to-[#2a6bc5] rounded-full shadow-xl hover:shadow-2xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center group relative border-3 border-white ring-3 ring-blue-300 ring-opacity-50 animate-float">

                        <div class="relative">
                            <svg width="35" height="35" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M37.4948 14.2183L8.61 5.35891C8.21905 5.24488 7.80693 5.22338 7.40623 5.2961C7.00554 5.36883 6.62726 5.53378 6.30131 5.77792C5.97537 6.02206 5.71069 6.33869 5.52823 6.70277C5.34576 7.06685 5.25051 7.46838 5.25 7.87563V31.5006C5.25 32.1968 5.52656 32.8645 6.01884 33.3568C6.51113 33.8491 7.17881 34.1256 7.875 34.1256C8.12601 34.1257 8.37575 34.0898 8.61656 34.019L22.3125 29.8157V31.5006C22.3125 32.1968 22.5891 32.8645 23.0813 33.3568C23.5736 33.8491 24.2413 34.1256 24.9375 34.1256H30.1875C30.8837 34.1256 31.5514 33.8491 32.0437 33.3568C32.5359 32.8645 32.8125 32.1968 32.8125 31.5006V26.5952L37.4948 25.1596C38.0368 24.9968 38.512 24.6641 38.8505 24.2107C39.1891 23.7573 39.3729 23.2071 39.375 22.6413V16.735C39.3726 16.1694 39.1885 15.6196 38.85 15.1665C38.5116 14.7134 38.0365 14.381 37.4948 14.2183ZM22.3125 27.0709L7.875 31.5006V7.87563L22.3125 12.3053V27.0709ZM30.1875 31.5006H24.9375V29.0102L30.1875 27.3991V31.5006ZM36.75 22.6413H36.732L24.9375 26.2638V13.1125L36.732 16.7219H36.75V22.6281V22.6413Z" fill="white" />
                            </svg>



                        </div>

                        <div class="absolute right-full ml-3 top-1/2 transform -translate-y-1/2 hidden group-hover:block min-w-max z-50">
                            <div class="bg-gray-900 text-white text-sm rounded-lg py-2 px-3 shadow-xl">
                                <span class="font-md" data-i18n="floatingViewAnnouncements">View Announcements</span>
                                <div class="text-xs text-gray-300 mt-1"><span><?= count($announcements) ?></span> <span data-i18n="floatingNewUpdates">new update(s)</span></div>
                            </div>
                            <div class="absolute right-full top-1/2 transform -translate-y-1/2">
                                <div class="w-0 h-0 border-t-4 border-b-4 border-l-4 border-transparent border-l-gray-900"></div>
                            </div>
                        </div>
                    </button>

                    <div class="absolute -bottom-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center shadow-lg border-2 border-white">
                        <?= count($announcements) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Informative Barangay Health Center Display -->
        <section id="home" class="hero-gradient text-white hero-section">
            <div class="mx-4 sm:mx-8 md:mx-16 lg:mx-24 w-full">
                <div class="px-4 md:px-12 mb-12 justify-between flex flex-col md:flex-row gap-8 md:gap-12 w-full">
                    <!-- LEFT -->
                    <div class="md:w-1/2 lg:w-2/5">
                        <h1 class="text-2xl md:text-4xl font-medium leading-tight mb-6 mt-8 md:mt-14">
                            <span>Student Patient Monitoring and Tracking</span>
                        </h1>

                        <p class="text-base md:text-lg text-white mb-10">
                            Your trusted partner in school healthcare. <br>
                            Delivering accessible, quality health services. <br>
                            Dedicated to the well-being of every student and staff <br>
                            at Cebu Eastern College.
                        </p>

                        <button href="Learnmore.html" >
                            <a href="Learnmore.php" class="py-4 px-7 bg-[#010489] rounded-lg text-md text-white font-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 w-max">
    <span data-i18n="learnMore">Learn More</span>
    <i class="fas fa-arrow-right"></i>
</a>
                        </button>
                    </div>

                    <!-- RIGHT -->
                    <div class="md:w-1/2 lg:w-2/5 xl:w-1/2 md:pr-14 lg:pr-20">
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 md:gap-6 justify-center mt-5">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-[blue-200] text-xs md:text-base mb-4">Stake Holders</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $medicalStaffCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-[blue-200] text-xs md:text-base mb-4">Students Served</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $residentsServedCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-[blue-200] text-xs md:text-base mb-4" data-i18n="statYearsService">Years Service</div>
                                <div class="flex items-center gap-2">
  <div class="text-2xl md:text-3xl font-bold">112</div>
  <span>Years</span>
</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-[blue-200] text-xs md:text-base mb-4" data-i18n="statMonthlyConsultations">Monthly Consultations</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $monthlyConsultationCount ?></div>
                            </div>
                        </div>
                    </div>


                </div>


                <!-- Quick Info Cards -->
                <div class="flex flex-col md:flex-row gap-24 mb-12 w-full">
                    <!-- Location Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#010489] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.5 7.5C10.5 7.20333 10.588 6.91332 10.7528 6.66664C10.9176 6.41997 11.1519 6.22771 11.426 6.11418C11.7001 6.00065 12.0017 5.97094 12.2926 6.02882C12.5836 6.0867 12.8509 6.22956 13.0607 6.43934C13.2704 6.64912 13.4133 6.91639 13.4712 7.20736C13.5291 7.49834 13.4994 7.79994 13.3858 8.07402C13.2723 8.34811 13.08 8.58238 12.8334 8.7472C12.5867 8.91203 12.2967 9 12 9C11.6022 9 11.2206 8.84196 10.9393 8.56066C10.658 8.27936 10.5 7.89782 10.5 7.5ZM6 7.5C6 5.9087 6.63214 4.38258 7.75736 3.25736C8.88258 2.13214 10.4087 1.5 12 1.5C13.5913 1.5 15.1174 2.13214 16.2426 3.25736C17.3679 4.38258 18 5.9087 18 7.5C18 13.1203 12.6019 16.2694 12.375 16.4016C12.2617 16.4663 12.1334 16.5004 12.0028 16.5004C11.8723 16.5004 11.744 16.4663 11.6306 16.4016C11.3981 16.2694 6 13.125 6 7.5ZM7.5 7.5C7.5 11.4563 10.86 14.0822 12 14.8594C13.1391 14.0831 16.5 11.4563 16.5 7.5C16.5 6.30653 16.0259 5.16193 15.182 4.31802C14.3381 3.47411 13.1935 3 12 3C10.8065 3 9.66193 3.47411 8.81802 4.31802C7.97411 5.16193 7.5 6.30653 7.5 7.5ZM19.0097 13.8403C18.8251 13.7793 18.624 13.7924 18.4489 13.8768C18.2738 13.9612 18.1382 14.1102 18.0709 14.2926C18.0035 14.475 18.0096 14.6764 18.0879 14.8543C18.1661 15.0323 18.3104 15.1729 18.4903 15.2466C20.0381 15.8194 21 16.5863 21 17.25C21 18.5025 17.5762 20.25 12 20.25C6.42375 20.25 3 18.5025 3 17.25C3 16.5863 3.96188 15.8194 5.50969 15.2475C5.6896 15.1739 5.8339 15.0332 5.91215 14.8553C5.99039 14.6773 5.99648 14.4759 5.92913 14.2935C5.86178 14.1112 5.72624 13.9621 5.5511 13.8777C5.37596 13.7933 5.1749 13.7803 4.99031 13.8412C2.73937 14.6709 1.5 15.8822 1.5 17.25C1.5 20.1731 6.91031 21.75 12 21.75C17.0897 21.75 22.5 20.1731 22.5 17.25C22.5 15.8822 21.2606 14.6709 19.0097 13.8403Z" fill="#FFFFFF" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg" data-i18n="locationCardTitle">Location</span>
                            </div>
                            <span class="bg-green-100 text-green-600 text-xs font-semibold px-3 py-1 rounded-md" data-i18n="statusActive">Active</span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">

    <!-- Updated subtitle -->
    <div class="text-gray-400 text-sm mb-3">
        Leon Kilat St., Cebu City
    </div>

    <!-- Updated modern Google Maps embed -->
    <div class="rounded-lg overflow-hidden border border-gray-200 mb-4" style="min-height:110px;max-height:160px;">
        <iframe
            src="https://maps.google.com/maps?q=Cebu%20Eastern%20College%20Inc%20Leon%20Kilat%20St%20Cebu%20City&t=&z=16&ie=UTF8&iwloc=&output=embed"
            width="100%"
            height="120"
            style="border:0; min-width:100%; min-height:110px; max-height:160px;"
            allowfullscreen=""
            loading="lazy">
        </iframe>
    </div>

    <!-- Updated button -->
    <div class="flex gap-2 mt-auto">
        <a href="https://www.google.com/maps/search/?api=1&query=Cebu+Eastern+College+Inc+Leon+Kilat+St+Cebu+City"
           target="_blank"
           class="bg-[#010489] hover:bg-[#010489] text-white text-md font-medium px-4 py-1.5 rounded-md border transition">
           Google Maps
        </a>
    </div>
</div>
                    </div>
                    <!-- Redesigned Availability Card -->
                    <!-- Availability Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#010489] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM12 20.25C10.3683 20.25 8.77326 19.7661 7.41655 18.8596C6.05984 17.9531 5.00242 16.6646 4.378 15.1571C3.75358 13.6496 3.5902 11.9908 3.90853 10.3905C4.22685 8.79016 5.01259 7.32015 6.16637 6.16637C7.32016 5.01259 8.79017 4.22685 10.3905 3.90852C11.9909 3.59019 13.6497 3.75357 15.1571 4.37799C16.6646 5.00242 17.9531 6.05984 18.8596 7.41655C19.7662 8.77325 20.25 10.3683 20.25 12C20.2475 14.1873 19.3775 16.2843 17.8309 17.8309C16.2843 19.3775 14.1873 20.2475 12 20.25ZM18 12C18 12.1989 17.921 12.3897 17.7803 12.5303C17.6397 12.671 17.4489 12.75 17.25 12.75H12C11.8011 12.75 11.6103 12.671 11.4697 12.5303C11.329 12.3897 11.25 12.1989 11.25 12V6.75C11.25 6.55109 11.329 6.36032 11.4697 6.21967C11.6103 6.07902 11.8011 6 12 6C12.1989 6 12.3897 6.07902 12.5303 6.21967C12.671 6.36032 12.75 6.55109 12.75 6.75V11.25H17.25C17.4489 11.25 17.6397 11.329 17.7803 11.4697C17.921 11.6103 18 11.8011 18 12Z" fill="white" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg" data-i18n="availabilityCardTitle">Availability</span>
                            </div>
                            <span id="availabilityStatus" class="<?php echo $badgeClass; ?> text-xs font-semibold px-3 py-1 rounded-md"><?php echo $badgeStatus; ?></span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="text-gray-400 text-sm mb-1" data-i18n="officeHoursLabel">Office Hours :</div>
                            <div class="text-gray-700 text-base font-medium mb-1" data-i18n="officeHoursValue">Monday-Friday, 8:00 AM - 5:00 PM</div>
                            <div class="text-gray-400 text-sm mb-1" data-i18n="emergencyContactLabel">Emergency Contact :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">4357-344-45</div>
                            <div class="text-gray-400 text-sm mb-1" data-i18n="contactPersonLabel">Contact Person :</div>
                            <div class="text-gray-700 text-base font-medium mb-4">Maria Santos</div>
                            <div class="flex gap-2 mt-auto">
                                <a href="#" class="bg-[#010489] hover:bg-[#010489] text-white text-md font-medium px-4 py-1.5 rounded-md border transition" data-i18n="preview">Preview</a>

                            </div>
                        </div>
                    </div>
                    <!-- Redesigned Contact Card -->
                    <!-- Contact Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#010489] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_2181_13657)">
                                            <path d="M22.2497 9.95765L5.75724 3.84421L5.74224 3.83953C5.47677 3.74812 5.19097 3.73306 4.91737 3.79605C4.64376 3.85905 4.39333 3.99756 4.19455 4.19585C3.99578 4.39413 3.85664 4.64423 3.79298 4.91768C3.72931 5.19113 3.74367 5.47696 3.83442 5.74265C3.83556 5.74777 3.83713 5.75279 3.83911 5.75765L9.95724 22.2502C10.0577 22.5442 10.2483 22.7991 10.5019 22.9787C10.7556 23.1583 11.0593 23.2534 11.37 23.2505H11.3982C11.7151 23.248 12.0228 23.1439 12.276 22.9533C12.5292 22.7627 12.7144 22.4959 12.8044 22.192L12.81 22.1733L14.8575 14.8608L22.17 12.8133L22.1888 12.8077C22.4904 12.7148 22.755 12.5293 22.9451 12.2774C23.1351 12.0255 23.2409 11.7201 23.2473 11.4046C23.2538 11.0891 23.1605 10.7796 22.9809 10.5202C22.8013 10.2608 22.5445 10.0646 22.2469 9.95953L22.2497 9.95765ZM14.0475 13.5286C13.9237 13.5634 13.8109 13.6294 13.7199 13.7203C13.629 13.8113 13.5629 13.9241 13.5282 14.048L11.3719 21.7505L11.3663 21.7345L5.25005 5.25046L21.7332 11.3648L21.7482 11.3705L14.0475 13.5286Z" fill="#FFFFFF" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_2181_13657">
                                                <rect width="24" height="24" fill="#FFFFFF" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </span>
                                <span class="text-white font-md text-lg" data-i18n="contactCardTitle">Contact</span>
                            </div>
                            <span class="bg-green-100 text-green-600 text-xs font-semibold px-3 py-1 rounded-md" data-i18n="statusActive">Active</span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="text-gray-400 text-sm mb-1" data-i18n="landlineLabel">Landline :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">(032) 123-4567</div>
                            <div class="text-gray-400 text-sm mb-1" data-i18n="mobileNumberLabel">Mobile Number :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">0917-123-4567</div>
                            <div class="text-gray-400 text-sm mb-1" data-i18n="emailLabel">Official Email Address :</div>
                            <div class="text-gray-700 text-base font-medium mb-4">cebueasterncollege@gmail.com</div>
                            <div class="flex gap-2 mt-auto">
                                <a href="#" class="bg-[#010489] hover:bg-[#010489] text-white text-md font-medium px-4 py-1.5 rounded-md border transition" data-i18n="preview">Preview</a>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </section>

        <!-- SECTION 2: Health Services -->
        <section id="services" class="services-bg section-padding-lg">
            <div class="max-w-[1800px] mx-auto px-8 sm:px-10 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="section-title02 text-3xl md:text-4xl font-semibold text-[#010489]">
                        <span>Our Health Services</span>
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto text-lg text-lead">
                        Comprehensive healthcare services designed to meet the diverse needs of our community members
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 grid-spacing">
                    <!-- Service 1 -->
                    <div class="info-card text-center card-hover">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                        
<svg width="50" height="50" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M44.7006 39.5312H32.6564C32.2006 39.5312 31.7634 39.7123 31.4411 40.0347C31.1188 40.357 30.9377 40.7942 30.9377 41.25V43.8281C30.9377 45.8808 31.7531 47.8495 33.2046 49.3009C34.6561 50.7524 36.6247 51.5679 38.6774 51.5679C40.7301 51.5679 42.6988 50.7524 44.1503 49.3009C45.6017 47.8495 46.4172 45.8808 46.4172 43.8281V41.25C46.4172 40.7945 46.2364 40.3577 45.9145 40.0354C45.5927 39.7132 45.156 39.5318 44.7006 39.5312ZM42.9818 43.8281C42.9818 44.9677 42.5291 46.0607 41.7233 46.8665C40.9175 47.6723 39.8246 48.125 38.685 48.125C37.5453 48.125 36.4524 47.6723 35.6466 46.8665C34.8408 46.0607 34.3881 44.9677 34.3881 43.8281V42.9687H42.9818V43.8281ZM22.3439 34.375H10.3127C9.85684 34.375 9.41967 34.5561 9.09735 34.8784C8.77502 35.2007 8.59394 35.6379 8.59394 36.0937V38.6719C8.59394 40.7232 9.4088 42.6904 10.8593 44.1409C12.3098 45.5914 14.277 46.4062 16.3283 46.4062C18.3796 46.4062 20.3469 45.5914 21.7973 44.1409C23.2478 42.6904 24.0627 40.7232 24.0627 38.6719V36.0937C24.0627 35.6379 23.8816 35.2007 23.5593 34.8784C23.2369 34.5561 22.7998 34.375 22.3439 34.375ZM20.6252 38.6719C20.6252 39.8115 20.1725 40.9044 19.3667 41.7102C18.5608 42.516 17.4679 42.9687 16.3283 42.9687C15.1887 42.9687 14.0958 42.516 13.29 41.7102C12.4841 40.9044 12.0314 39.8115 12.0314 38.6719V37.8125H20.6252V38.6719ZM16.3283 3.4375C13.8275 3.4375 11.4019 5.65254 9.49628 9.66797C6.50351 15.9801 5.50878 25.3516 9.55858 30.293C9.72007 30.4901 9.92335 30.6488 10.1537 30.7577C10.3841 30.8665 10.6358 30.9228 10.8906 30.9225H21.751C22.0058 30.9228 22.2575 30.8665 22.4879 30.7577C22.7182 30.6488 22.9215 30.4901 23.083 30.293C27.1328 25.3516 26.1381 15.9736 23.1453 9.66797C21.2375 5.65254 18.8141 3.4375 16.3283 3.4375ZM20.8723 27.5H11.7693C9.32011 23.6113 10.2203 16.1777 12.6051 11.1482C13.9951 8.21133 15.5055 6.875 16.3283 6.875C17.1512 6.875 18.6529 8.21133 20.0451 11.1482C22.4213 16.1777 23.3215 23.6113 20.8723 27.5ZM33.2494 36.0937H44.1098C44.3646 36.0941 44.6163 36.0378 44.8466 35.929C45.077 35.8201 45.2803 35.6614 45.4418 35.4643C49.4916 30.5229 48.4969 21.1449 45.5041 14.8393C43.5984 10.8088 41.1728 8.59375 38.6721 8.59375C36.1713 8.59375 33.7543 10.8088 31.8465 14.8242C28.8537 21.1363 27.859 30.5078 31.9088 35.4492C32.0702 35.6496 32.2743 35.8115 32.5063 35.923C32.7382 36.0345 32.9921 36.0928 33.2494 36.0937ZM34.9682 16.3045C36.356 13.3676 37.8664 12.0312 38.6721 12.0312C39.4777 12.0312 40.9967 13.3676 42.3867 16.3045C44.7715 21.334 45.6717 28.7654 43.2246 32.6562H34.1281C31.6789 28.7676 32.5791 21.334 34.9639 16.3045H34.9682Z" fill="#010489"/>
</svg>
                    </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3" data-i18n="service1Title">Primary Care Consultation</h4>
                        <p class="text-gray-200 text-lead" data-i18n="service1Description">
                            Comprehensive medical check-ups, diagnosis, and treatment for common illnesses.
                        </p>
                    </div>

                    <!-- Service 2 -->
                    <div class="info-card text-center card-hover">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                        
<svg width="50" height="50" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M51.0607 14.2537L40.7482 3.94119C40.5885 3.7815 40.399 3.65483 40.1903 3.5684C39.9817 3.48198 39.758 3.4375 39.5322 3.4375C39.3064 3.4375 39.0827 3.48198 38.8741 3.5684C38.6655 3.65483 38.4759 3.7815 38.3162 3.94119C38.1565 4.10088 38.0298 4.29046 37.9434 4.4991C37.857 4.70775 37.8125 4.93137 37.8125 5.15721C37.8125 5.38304 37.857 5.60666 37.9434 5.81531C38.0298 6.02395 38.1565 6.21353 38.3162 6.37322L42.2586 10.3135L36.0947 16.4773L28.717 9.09744C28.3945 8.77493 27.957 8.59375 27.501 8.59375C27.0449 8.59375 26.6074 8.77493 26.2849 9.09744C25.9624 9.41995 25.7812 9.85736 25.7812 10.3135C25.7812 10.7695 25.9624 11.207 26.2849 11.5295L27.6492 12.8916L9.60232 30.9385C9.28173 31.2565 9.02756 31.6351 8.85458 32.0522C8.6816 32.4694 8.59326 32.9168 8.59471 33.3683V43.9773L3.94119 48.6287C3.7815 48.7884 3.65483 48.978 3.5684 49.1866C3.48198 49.3952 3.4375 49.6189 3.4375 49.8447C3.4375 50.0705 3.48198 50.2942 3.5684 50.5028C3.65483 50.7115 3.7815 50.901 3.94119 51.0607C4.2637 51.3832 4.70111 51.5644 5.15721 51.5644C5.38304 51.5644 5.60666 51.5199 5.81531 51.4335C6.02395 51.3471 6.21353 51.2204 6.37322 51.0607L11.0246 46.4072H21.6336C22.0852 46.4086 22.5325 46.3203 22.9497 46.1473C23.3668 45.9744 23.7454 45.7202 24.0635 45.3996L42.1103 27.3527L43.4724 28.717C43.6321 28.8767 43.8217 29.0033 44.0304 29.0898C44.239 29.1762 44.4626 29.2207 44.6885 29.2207C44.9143 29.2207 45.1379 29.1762 45.3466 29.0898C45.5552 29.0033 45.7448 28.8767 45.9045 28.717C46.0642 28.5573 46.1908 28.3677 46.2773 28.1591C46.3637 27.9504 46.4082 27.7268 46.4082 27.501C46.4082 27.2751 46.3637 27.0515 46.2773 26.8429C46.1908 26.6342 46.0642 26.4446 45.9045 26.2849L38.5246 18.9072L44.6885 12.7433L48.6287 16.6857C48.9512 17.0082 49.3886 17.1894 49.8447 17.1894C50.3008 17.1894 50.7382 17.0082 51.0607 16.6857C51.3832 16.3632 51.5644 15.9258 51.5644 15.4697C51.5644 15.0136 51.3832 14.5762 51.0607 14.2537ZM21.6336 42.9697H12.0322V33.3683L15.8994 29.5012L20.2693 33.8732C20.429 34.0329 20.6186 34.1596 20.8272 34.246C21.0359 34.3324 21.2595 34.3769 21.4853 34.3769C21.7112 34.3769 21.9348 34.3324 22.1434 34.246C22.3521 34.1596 22.5417 34.0329 22.7013 33.8732C22.861 33.7135 22.9877 33.524 23.0741 33.3153C23.1606 33.1067 23.205 32.883 23.205 32.6572C23.205 32.4314 23.1606 32.2077 23.0741 31.9991C22.9877 31.7905 22.861 31.6009 22.7013 31.4412L18.3293 27.0713L21.0556 24.3449L25.4256 28.717C25.7481 29.0395 26.1855 29.2207 26.6416 29.2207C27.0977 29.2207 27.5351 29.0395 27.8576 28.717C28.1801 28.3945 28.3613 27.957 28.3613 27.501C28.3613 27.0449 28.1801 26.6074 27.8576 26.2849L23.4855 21.915L30.0791 15.3215L39.6804 24.9228L21.6336 42.9697Z" fill="#010489"/>
</svg>                    </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3" data-i18n="service2Title">Immunization Program</h4>
                        <p class="text-gray-200 text-lead" data-i18n="service2Description">
                            Complete vaccination schedule for children, adults, and senior citizens.
                        </p>
                    </div>

                    <!-- Service 3 -->
                    <div class="info-card text-center card-hover">
                        <div class=" mx-auto">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
<svg width="50" height="50" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M48.0562 14.2117L29.15 3.86694C28.6448 3.58782 28.0771 3.44141 27.5 3.44141C26.9229 3.44141 26.3552 3.58782 25.85 3.86694L6.94375 14.216C6.40382 14.5114 5.95311 14.9463 5.63869 15.4754C5.32426 16.0045 5.15765 16.6083 5.15625 17.2238V37.7714C5.15765 38.3869 5.32426 38.9907 5.63869 39.5198C5.95311 40.0489 6.40382 40.4838 6.94375 40.7792L25.85 51.1283C26.3552 51.4074 26.9229 51.5538 27.5 51.5538C28.0771 51.5538 28.6448 51.4074 29.15 51.1283L48.0562 40.7792C48.5962 40.4838 49.0469 40.0489 49.3613 39.5198C49.6757 38.9907 49.8424 38.3869 49.8438 37.7714V17.2259C49.8435 16.6094 49.6774 16.0042 49.363 15.4739C49.0485 14.9436 48.5971 14.5076 48.0562 14.2117ZM27.5 6.87475L44.7605 16.3279L38.3647 19.8298L21.102 10.3767L27.5 6.87475ZM27.5 25.781L10.2395 16.3279L17.5227 12.3404L34.7832 21.7935L27.5 25.781ZM8.59375 19.3357L25.7812 28.7415V47.173L8.59375 37.7736V19.3357ZM46.4062 37.765L29.2188 47.173V28.7501L36.0938 24.9882V32.656C36.0938 33.1118 36.2748 33.549 36.5972 33.8713C36.9195 34.1937 37.3567 34.3747 37.8125 34.3747C38.2683 34.3747 38.7055 34.1937 39.0278 33.8713C39.3502 33.549 39.5312 33.1118 39.5312 32.656V23.1062L46.4062 19.3357V37.7628V37.765Z" fill="#010489"/>
</svg>

                   </div>


                        </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3" data-i18n="service3Title">Emergency Services</h4>
                        <p class="text-gray-200 text-lead" data-i18n="service3Description">
                            24/7 emergency medical services with basic life support.
                        </p>
                    </div>


                </div>
            </div>
        </section>

        <!-- SECTION 3: Announcements Display -->
        <section id="announcementsSection" class="announcements-bg text-white section-padding">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="section-title02 text-3xl md:text-4xl font-semibold text-white">
                        <span data-i18n="announcementsTitle">Latest Announcements</span>
                    </h2>
                    <p class="text-white max-w-3xl mx-auto text-lg">
                        Stay informed with important updates, health advisories, and school events
                    </p>
                </div>

                <?php if (empty($announcements)): ?>
                    <div class="bg-white rounded-2xl p-16 text-center border border-gray-200 backdrop-blur-md">
                        <div class="mb-6 flex justify-center">
                            <svg width="70" height="70" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M62.4914 23.6945L14.35 8.92891C13.6984 8.73886 13.0115 8.70302 12.3437 8.82423C11.6759 8.94544 11.0454 9.22036 10.5022 9.62727C9.95894 10.0342 9.51782 10.5619 9.21371 11.1687C8.9096 11.7755 8.75085 12.4447 8.75 13.1234V52.4984C8.75 53.6588 9.21094 54.7716 10.0314 55.592C10.8519 56.4125 11.9647 56.8734 13.125 56.8734C13.5434 56.8736 13.9596 56.8137 14.3609 56.6957L37.1875 49.6902V52.4984C37.1875 53.6588 37.6484 54.7716 38.4689 55.592C39.2894 56.4125 40.4022 56.8734 41.5625 56.8734H50.3125C51.4728 56.8734 52.5856 56.4125 53.4061 55.592C54.2266 54.7716 54.6875 53.6588 54.6875 52.4984V44.3227L62.4914 41.9301C63.3946 41.6587 64.1867 41.1043 64.7509 40.3486C65.3151 39.5929 65.6215 38.6759 65.625 37.7328V27.8891C65.6209 26.9465 65.3142 26.0301 64.7501 25.2749C64.1859 24.5198 63.3942 23.9658 62.4914 23.6945ZM37.1875 45.1156L13.125 52.4984V13.1234L37.1875 20.5063V45.1156ZM50.3125 52.4984H41.5625V48.3477L50.3125 45.6625V52.4984ZM61.25 37.7328H61.2199L41.5625 43.7703V21.8516L61.2199 27.8672H61.25V37.7109V37.7328Z" fill="#DFDFDF"/>
</svg>




                        </div>
                        <h3 class="text-2xl font-md text-gray-300 mb-3" data-i18n="noAnnouncementsTitle">No Announcements Yet</h3>
                        <p class="text-gray-300 max-w-md mx-auto text-md">
                            Check back soon for important health updates and school announcements.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 mb-12">
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <?php if ($index >= 4) break; ?>
                            <div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                                <div class="p-6 flex flex-col h-full">
                                    <!-- Badge and Date Header -->
                                    <div class="flex items-start justify-between mb-6">
                                        <div>
                                            <?php if ($announcement['priority'] == 'high'): ?>
                                                <span class="inline-block px-4 py-2 rounded text-sm font-semibold bg-red-100 text-red-700">
                                                    <span data-i18n="priorityHigh">High Priority</span>
                                                </span>
                                            <?php elseif ($announcement['priority'] == 'medium'): ?>
                                                <span class="inline-block px-4 py-2 rounded text-sm font-semibold bg-yellow-100 text-yellow-700">
                                                    <span data-i18n="priorityMedium">Medium Priority</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-block px-4 py-2 rounded text-sm font-semibold bg-blue-100 text-blue-700">
                                                    <span data-i18n="priorityAnnouncement">Normal Priority</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-400 font-medium" data-i18n="datePosted">Date Posted :</div>
                                            <div class="text-md font-medium text-blue-500">
                                                <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <h3 class="text-1xl font-bold text-gray-700 mb-4">
                                        <?= htmlspecialchars($announcement['title']) ?>
                                    </h3>

                                    <!-- Message -->
                                    <div class="text-gray-700 mb-6 text-base leading-relaxed flex-grow">
                                        <?= nl2br(htmlspecialchars(substr($announcement['message'], 0, 180))) ?>
                                    </div>

                                    <!-- Button -->
                                    <button onclick="openAnnouncementModal(<?= $index ?>)"
                                        class="bg-blue-500 backdrop-blur hover:bg-blue-600 text-white font-md py-2 px-6 rounded text-base transition-colors duration-200 self-start">
                                        <span data-i18n="readAnnouncement">Read Announcement</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center">
                        <button onclick="openAnnouncementsModal()"
                            class="btn-primary bg-white text-[#3a7bd5] hover:bg-blue-50">
                            <span data-i18n="viewAllAnnouncements">View All Announcements</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SECTION 4: Testimonials -->
        <section id="about" class="about-bg section-padding">
            <div class="max-w-[1900px] mx-auto px-8 sm:px-10 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="section-title text-3xl md:text-4xl font-semibold text-[#010489]">
                        <span data-i18n="aboutTitle">What Our Community Says</span>
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto text-lg text-lead" data-i18n="aboutDescription">
                        Hear from our dedicated healthcare providers and community members
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 grid-spacing">
                    <!-- Testimonial 1 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full  flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.25 7.40625V7.5C20.25 8.49456 19.8549 9.44839 19.1517 10.1517C18.4484 10.8549 17.4946 11.25 16.5 11.25H12.75V18.75H13.5C13.8039 18.7502 14.1007 18.658 14.351 18.4857C14.6014 18.3135 14.7935 18.0692 14.902 17.7853C15.0104 17.5014 15.0301 17.1913 14.9584 16.896C14.8867 16.6006 14.7269 16.3341 14.5003 16.1316C14.3596 15.9971 14.2766 15.8132 14.269 15.6187C14.2614 15.4242 14.3296 15.2344 14.4594 15.0893C14.5891 14.9442 14.7702 14.8553 14.9643 14.8412C15.1584 14.8272 15.3504 14.8892 15.4997 15.0141C15.9525 15.4191 16.2716 15.9521 16.4149 16.5425C16.5582 17.1328 16.5189 17.7528 16.3021 18.3203C16.0854 18.8879 15.7014 19.3762 15.201 19.7208C14.7007 20.0654 14.1075 20.2499 13.5 20.25H12.75V21.75C12.75 21.9489 12.671 22.1397 12.5303 22.2803C12.3897 22.421 12.1989 22.5 12 22.5C11.8011 22.5 11.6103 22.421 11.4697 22.2803C11.329 22.1397 11.25 21.9489 11.25 21.75V20.25H9C8.80109 20.25 8.61032 20.171 8.46967 20.0303C8.32902 19.8897 8.25 19.6989 8.25 19.5C8.25 19.3011 8.32902 19.1103 8.46967 18.9697C8.61032 18.829 8.80109 18.75 9 18.75H11.25V11.25H9C8.60218 11.25 8.22064 11.408 7.93934 11.6893C7.65804 11.9706 7.5 12.3522 7.5 12.75C7.5 13.1478 7.65804 13.5294 7.93934 13.8107C8.22064 14.092 8.60218 14.25 9 14.25C9.19891 14.25 9.38968 14.329 9.53033 14.4697C9.67098 14.6103 9.75 14.8011 9.75 15C9.75 15.1989 9.67098 15.3897 9.53033 15.5303C9.38968 15.671 9.19891 15.75 9 15.75C8.20435 15.75 7.44129 15.4339 6.87868 14.8713C6.31607 14.3087 6 13.5456 6 12.75C6 11.9544 6.31607 11.1913 6.87868 10.6287C7.44129 10.0661 8.20435 9.75 9 9.75H11.25V2.25C11.25 2.05109 11.329 1.86032 11.4697 1.71967C11.6103 1.57902 11.8011 1.5 12 1.5C12.1989 1.5 12.3897 1.57902 12.5303 1.71967C12.671 1.86032 12.75 2.05109 12.75 2.25V9.75H16.5C17.0967 9.75 17.669 9.51295 18.091 9.09099C18.5129 8.66903 18.75 8.09674 18.75 7.5V7.40625C18.75 6.83438 18.5228 6.28593 18.1184 5.88155C17.7141 5.47718 17.1656 5.25 16.5938 5.25H15C14.8011 5.25 14.6103 5.17098 14.4697 5.03033C14.329 4.88968 14.25 4.69891 14.25 4.5C14.25 4.30109 14.329 4.11032 14.4697 3.96967C14.6103 3.82902 14.8011 3.75 15 3.75H16.5938C17.5634 3.75 18.4934 4.13521 19.1791 4.82089C19.8648 5.50657 20.25 6.43655 20.25 7.40625ZM5.25 9H3C2.80109 9 2.61032 8.92098 2.46967 8.78033C2.32902 8.63968 2.25 8.44891 2.25 8.25V7.5C2.25 6.50544 2.64509 5.55161 3.34835 4.84835C4.05161 4.14509 5.00544 3.75 6 3.75H9C9.19891 3.75 9.38968 3.82902 9.53033 3.96967C9.67098 4.11032 9.75 4.30109 9.75 4.5C9.75 4.69891 9.67098 4.88968 9.53033 5.03033C9.38968 5.17098 9.19891 5.25 9 5.25C9 5.74246 8.903 6.23009 8.71455 6.68506C8.52609 7.14003 8.24987 7.55343 7.90165 7.90165C7.55343 8.24987 7.14003 8.52609 6.68506 8.71455C6.23009 8.903 5.74246 9 5.25 9ZM7.5 5.25H6C5.40326 5.25 4.83097 5.48705 4.40901 5.90901C3.98705 6.33097 3.75 6.90326 3.75 7.5H5.25C5.84674 7.5 6.41903 7.26295 6.84099 6.84099C7.26295 6.41903 7.5 5.84674 7.5 5.25Z" fill="#010489"/>
</svg>




                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Ms. Kristine Bancure</h4>
                                <p class="text-gray-600 text-md">IT Technical Head</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead" data-i18n="testimonial1Quote">
                            "Our health center is committed to providing accessible and quality healthcare to every resident."
                        </p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full  flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M2.25 9.74953H4.5V15.7495H3C2.80109 15.7495 2.61032 15.8286 2.46967 15.9692C2.32902 16.1099 2.25 16.3006 2.25 16.4995C2.25 16.6984 2.32902 16.8892 2.46967 17.0299C2.61032 17.1705 2.80109 17.2495 3 17.2495H21C21.1989 17.2495 21.3897 17.1705 21.5303 17.0299C21.671 16.8892 21.75 16.6984 21.75 16.4995C21.75 16.3006 21.671 16.1099 21.5303 15.9692C21.3897 15.8286 21.1989 15.7495 21 15.7495H19.5V9.74953H21.75C21.9132 9.74937 22.0719 9.69599 22.202 9.59748C22.3321 9.49898 22.4265 9.36073 22.4709 9.20371C22.5153 9.04669 22.5073 8.87946 22.4481 8.7274C22.3889 8.57533 22.2817 8.44673 22.1428 8.3611L12.3928 2.3611C12.2747 2.28846 12.1387 2.25 12 2.25C11.8613 2.25 11.7253 2.28846 11.6072 2.3611L1.85719 8.3611C1.71828 8.44673 1.61108 8.57533 1.55187 8.7274C1.49266 8.87946 1.48466 9.04669 1.52908 9.20371C1.57351 9.36073 1.66793 9.49898 1.79803 9.59748C1.92814 9.69599 2.08681 9.74937 2.25 9.74953ZM6 9.74953H9V15.7495H6V9.74953ZM13.5 9.74953V15.7495H10.5V9.74953H13.5ZM18 15.7495H15V9.74953H18V15.7495ZM12 3.87985L19.1006 8.24953H4.89937L12 3.87985ZM23.25 19.4995C23.25 19.6984 23.171 19.8892 23.0303 20.0299C22.8897 20.1705 22.6989 20.2495 22.5 20.2495H1.5C1.30109 20.2495 1.11032 20.1705 0.96967 20.0299C0.829018 19.8892 0.75 19.6984 0.75 19.4995C0.75 19.3006 0.829018 19.1099 0.96967 18.9692C1.11032 18.8286 1.30109 18.7495 1.5 18.7495H22.5C22.6989 18.7495 22.8897 18.8286 23.0303 18.9692C23.171 19.1099 23.25 19.3006 23.25 19.4995Z" fill="#010489"/>
</svg>


                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Mr. Philip Cutamora</h4>
                                <p class="text-gray-600 text-md">EDP Head</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead" data-i18n="testimonial2Quote">
                            "The health monitoring system has transformed how we manage community health needs."
                        </p>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.25 7.40625V7.5C20.25 8.49456 19.8549 9.44839 19.1517 10.1517C18.4484 10.8549 17.4946 11.25 16.5 11.25H12.75V18.75H13.5C13.8039 18.7502 14.1007 18.658 14.351 18.4857C14.6014 18.3135 14.7935 18.0692 14.902 17.7853C15.0104 17.5014 15.0301 17.1913 14.9584 16.896C14.8867 16.6006 14.7269 16.3341 14.5003 16.1316C14.3596 15.9971 14.2766 15.8132 14.269 15.6187C14.2614 15.4242 14.3296 15.2344 14.4594 15.0893C14.5891 14.9442 14.7702 14.8553 14.9643 14.8412C15.1584 14.8272 15.3504 14.8892 15.4997 15.0141C15.9525 15.4191 16.2716 15.9521 16.4149 16.5425C16.5582 17.1328 16.5189 17.7528 16.3021 18.3203C16.0854 18.8879 15.7014 19.3762 15.201 19.7208C14.7007 20.0654 14.1075 20.2499 13.5 20.25H12.75V21.75C12.75 21.9489 12.671 22.1397 12.5303 22.2803C12.3897 22.421 12.1989 22.5 12 22.5C11.8011 22.5 11.6103 22.421 11.4697 22.2803C11.329 22.1397 11.25 21.9489 11.25 21.75V20.25H9C8.80109 20.25 8.61032 20.171 8.46967 20.0303C8.32902 19.8897 8.25 19.6989 8.25 19.5C8.25 19.3011 8.32902 19.1103 8.46967 18.9697C8.61032 18.829 8.80109 18.75 9 18.75H11.25V11.25H9C8.60218 11.25 8.22064 11.408 7.93934 11.6893C7.65804 11.9706 7.5 12.3522 7.5 12.75C7.5 13.1478 7.65804 13.5294 7.93934 13.8107C8.22064 14.092 8.60218 14.25 9 14.25C9.19891 14.25 9.38968 14.329 9.53033 14.4697C9.67098 14.6103 9.75 14.8011 9.75 15C9.75 15.1989 9.67098 15.3897 9.53033 15.5303C9.38968 15.671 9.19891 15.75 9 15.75C8.20435 15.75 7.44129 15.4339 6.87868 14.8713C6.31607 14.3087 6 13.5456 6 12.75C6 11.9544 6.31607 11.1913 6.87868 10.6287C7.44129 10.0661 8.20435 9.75 9 9.75H11.25V2.25C11.25 2.05109 11.329 1.86032 11.4697 1.71967C11.6103 1.57902 11.8011 1.5 12 1.5C12.1989 1.5 12.3897 1.57902 12.5303 1.71967C12.671 1.86032 12.75 2.05109 12.75 2.25V9.75H16.5C17.0967 9.75 17.669 9.51295 18.091 9.09099C18.5129 8.66903 18.75 8.09674 18.75 7.5V7.40625C18.75 6.83438 18.5228 6.28593 18.1184 5.88155C17.7141 5.47718 17.1656 5.25 16.5938 5.25H15C14.8011 5.25 14.6103 5.17098 14.4697 5.03033C14.329 4.88968 14.25 4.69891 14.25 4.5C14.25 4.30109 14.329 4.11032 14.4697 3.96967C14.6103 3.82902 14.8011 3.75 15 3.75H16.5938C17.5634 3.75 18.4934 4.13521 19.1791 4.82089C19.8648 5.50657 20.25 6.43655 20.25 7.40625ZM5.25 9H3C2.80109 9 2.61032 8.92098 2.46967 8.78033C2.32902 8.63968 2.25 8.44891 2.25 8.25V7.5C2.25 6.50544 2.64509 5.55161 3.34835 4.84835C4.05161 4.14509 5.00544 3.75 6 3.75H9C9.19891 3.75 9.38968 3.82902 9.53033 3.96967C9.67098 4.11032 9.75 4.30109 9.75 4.5C9.75 4.69891 9.67098 4.88968 9.53033 5.03033C9.38968 5.17098 9.19891 5.25 9 5.25C9 5.74246 8.903 6.23009 8.71455 6.68506C8.52609 7.14003 8.24987 7.55343 7.90165 7.90165C7.55343 8.24987 7.14003 8.52609 6.68506 8.71455C6.23009 8.903 5.74246 9 5.25 9ZM7.5 5.25H6C5.40326 5.25 4.83097 5.48705 4.40901 5.90901C3.98705 6.33097 3.75 6.90326 3.75 7.5H5.25C5.84674 7.5 6.41903 7.26295 6.84099 6.84099C7.26295 6.41903 7.5 5.84674 7.5 5.25Z" fill="#010489"/>
</svg>


                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Mr. Lisa Mendoza - RN</h4>
                                <p class="text-gray-600 text-md" data-i18n="testimonial3Role">Nurse</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead" data-i18n="testimonial3Quote">
                            "The digital health records system has made our work more efficient and focused."
                        </p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Footer -->
        <footer class="bg-[#010489] text-white" id="footer">
            <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Column 1: About -->
                    <div>
                        <h3 class="text-2xl font-semibold mb-6">Cebu Eastern College Inc.</h3>
                        <p class="text-white mb-6">
                            Providing quality healthcare services to Cebu Eastern College students and staff with compassion and excellence.
                        </p>
                        <div class="flex space-x-4">
                            <a href="https://www.facebook.com/BarangayLuzCebuCity2023" target="_blank"
                                class="w-11 h-11 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="w-11 h-11 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2: Quick Links -->
                    <div>
                        <h3 class="text-xl font-medium mb-6" data-i18n="footerQuickLinks">Quick Links</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="#home" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> <span data-i18n="navHome">Home</span>
                                </a>
                            </li>
                            <li>
                                <a href="#services" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> <span data-i18n="footerOurServices">Our Services</span>
                                </a>
                            </li>
                            <li>
                                <a href="#announcementsSection" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> <span data-i18n="footerAnnouncements">Announcements</span>
                                </a>
                            </li>
                            <li>
                                <a href="#about" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> <span data-i18n="footerAboutUs">About Us</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Contact Info -->
                    <div>
                        <h3 class="text-xl font-medium mb-6" data-i18n="footerContactInfo">Contact Info</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-200"></i>
                                <span class="text-blue-100">Cebu Eastern College Inc.</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-phone mr-3 text-blue-200"></i>
                                <span class="text-blue-100">(032) 123-4567</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-3 text-blue-200"></i>
                                <span class="text-blue-100">cebueasterncolle@gmail.com</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Hours -->
                    <!-- <div>
                        <h3 class="text-xl font-medium mb-6" data-i18n="footerOperatingHours">Operating Hours</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-blue-100" data-i18n="footerWeekdays">Monday - Friday</span>
                                <span class="text-white">8:00 AM - 5:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-100" data-i18n="footerSaturday">Saturday</span>
                                <span class="text-white">8:00 AM - 12:00 PM</span>
                            </div>
                            <div class="pt-3 mt-3 border-t border-white/20">
                                <span class="text-blue-200 text-sm" data-i18n="footerEmergency247">Emergency services available 24/7</span>
                            </div>
                        </div>
                    </div> -->
                </div>

                <div class="border-t border-blue-400 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-blue-200">
                            &copy; <?= date('Y') ?> <span>Cebu Eastern College Inc. All rights reserved.</span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-6">
                        <a href="/privacy.php" class="text-blue-200 hover:text-white text-sm" data-i18n="privacyPolicy">Privacy Policy</a>
                        <a href="/terms.php" class="text-blue-200 hover:text-white text-sm" data-i18n="termsOfService">Terms of Service</a>
                        <span class="text-blue-200 text-sm">Version 1.0</span>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Instruction Modal - Automatic Display on Every Refresh -->
    <div id="instructionModal" class="fixed inset-0 hidden z-[9998] bg-black/50 backdrop-blur-sm flex justify-center items-center">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[85vh] overflow-hidden flex flex-col modal-content instruction-modal-content">
            <!-- Close Button -->
            <button onclick="closeInstructionModal()"
                class="modal-close-btn absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10 bg-white rounded-full p-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="shrink-0 px-8 pt-8 pb-4">
                <!-- Header with icon -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M32.9331 6.13159L12.5815 2.53784C11.9286 2.42295 11.2568 2.57207 10.7139 2.9524C10.1709 3.33273 9.80127 3.91313 9.68618 4.56597L5.03774 30.9722C4.98088 31.2957 4.98833 31.6272 5.05966 31.9479C5.13098 32.2685 5.26479 32.5719 5.45343 32.8408C5.64206 33.1097 5.88184 33.3387 6.15904 33.5149C6.43625 33.6911 6.74545 33.8109 7.06899 33.8675L27.4206 37.4613C27.7442 37.5184 28.0758 37.5111 28.3966 37.4399C28.7174 37.3686 29.021 37.2349 29.2901 37.0462C29.5592 36.8576 29.7884 36.6177 29.9647 36.3404C30.1409 36.0631 30.2608 35.7537 30.3174 35.43L34.9659 9.02378C35.0797 8.37069 34.9296 7.69912 34.5483 7.15675C34.1671 6.61438 33.5861 6.24563 32.9331 6.13159ZM27.8518 34.9988L7.49868 31.405L12.1471 4.99878L32.4987 8.59253L27.8518 34.9988ZM13.9581 9.12691C14.016 8.80061 14.2011 8.51066 14.4727 8.3208C14.7443 8.13094 15.0802 8.0567 15.4065 8.11441L28.3752 10.4035C28.6835 10.4575 28.9602 10.6251 29.1508 10.8732C29.3415 11.1214 29.4321 11.432 29.4048 11.7437C29.3775 12.0554 29.2343 12.3456 29.0035 12.5568C28.7726 12.7681 28.471 12.8851 28.1581 12.8847C28.0847 12.8846 28.0116 12.8783 27.9393 12.866L14.9706 10.5753C14.6443 10.5174 14.3543 10.3323 14.1644 10.0607C13.9746 9.78912 13.9003 9.45323 13.9581 9.12691ZM13.0924 14.0519C13.1209 13.8902 13.181 13.7357 13.2692 13.5972C13.3574 13.4587 13.4721 13.339 13.6066 13.2448C13.7411 13.1506 13.8928 13.0839 14.0531 13.0484C14.2134 13.0129 14.3792 13.0093 14.5409 13.0378L27.5096 15.3285C27.82 15.3804 28.0994 15.5475 28.292 15.7963C28.4846 16.0452 28.5762 16.3576 28.5486 16.6711C28.521 16.9845 28.3761 17.2761 28.143 17.4874C27.9098 17.6988 27.6055 17.8144 27.2909 17.8113C27.217 17.8114 27.1432 17.8046 27.0706 17.791L14.1018 15.5019C13.7758 15.4433 13.4863 15.2577 13.2971 14.9858C13.1078 14.7139 13.0342 14.378 13.0924 14.0519ZM12.2252 18.9753C12.2842 18.6499 12.4698 18.3611 12.7413 18.1722C13.0128 17.9833 13.348 17.9097 13.6737 17.9675L20.1549 19.1066C20.463 19.1606 20.7397 19.3281 20.9303 19.5761C21.1209 19.824 21.2116 20.1345 21.1845 20.4461C21.1575 20.7577 21.0146 21.0478 20.784 21.2592C20.5535 21.4706 20.2521 21.5878 19.9393 21.5878C19.866 21.5878 19.7928 21.5815 19.7206 21.5691L13.2362 20.4238C12.9102 20.3655 12.6206 20.1803 12.4311 19.9087C12.2415 19.6371 12.1675 19.3014 12.2252 18.9753Z" fill="#010489"/>
</svg>

                    </div>
                    <h2 class="text-2xl font-semibold text-[#010489]" data-i18n="privacyNoticeTitle">Data Privacy & Security Notice</h2>
                    <p class="text-gray-500 mt-2" data-i18n="privacyNoticeSubtitle">Your health information is protected and confidential</p>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-8 py-4">
                <!-- Content -->
                <div class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2" data-i18n="privacyConfidentialityTitle">Confidentiality Assurance</h3>
                                <p class="text-gray-600">
                                    Cebu Eastern College Incorporated Health Center strictly adheres to the Data Privacy Act of 2012 (RA 10173). 
                                    Your personal and health information is confidential and will only be accessible to authorized 
                                    healthcare personnel involved in your care.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2" data-i18n="privacyAccountSecurityTitle">Personal Account Security</h3>
                                <p class="text-gray-600">
                                    Each Student is provided with a unique username and password. Your login credentials 
                                    are personal and should not be shared with anyone. The system tracks access to ensure 
                                    only authorized viewing of your health records.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2" data-i18n="privacyAccessTitle">What You Can Access</h3>
                                <p class="text-gray-600" data-i18n="privacyAccessIntro">
                                    Upon successful login, you will have access to:
                                </p>
                                <ul class="instruction-list mt-3">
                                    <li class="text-gray-600" data-i18n="privacyAccessItem1">Your complete health history and medical records</li>
                                    <li class="text-gray-600" data-i18n="privacyAccessItem2">Immunization records and vaccination schedules</li>
                                    <li class="text-gray-600" data-i18n="privacyAccessItem3">Upcoming health appointments and consultations</li>
                                    <li class="text-gray-600" data-i18n="privacyAccessItem4">Health advisories and announcements specific to you</li>
                                    <li class="text-gray-600" data-i18n="privacyAccessItem5">Medication prescriptions and treatment plans</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2" data-i18n="privacyRemindersTitle">Important Reminders</h3>
                                <p class="text-gray-600">
                                    • Never share your login credentials with others<br>
                                    • Log out after each session, especially on shared devices<br>
                                    • Report any unauthorized access to your account immediately<br>
                                    • Update your password regularly for added security<br>
                                    • Contact the Clinic for any account-related concerns
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 px-8 pt-4 pb-8 bg-white">
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <button onclick="closeInstructionModal()"
                        class="flex-1 bg-[#010489] hover:bg-[#010489] text-white font-semibold py-3 px-6 rounded-md transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-check-circle mr-2"></i> <span data-i18n="privacyProceed">I Understand and Proceed</span>
                    </button>
                    <button onclick="closeInstructionModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-md transition-all duration-200">
                        <i class="fas fa-times-circle mr-2"></i> <span data-i18n="close">Close</span>
                    </button>
                </div>

                <p class="text-center text-xs text-gray-400 mt-6">
                    <span data-i18n="privacyFooterNote">By proceeding, you acknowledge that you have read and understood the data privacy guidelines.</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Other Modals -->
    <div id="learnMoreModal" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm flex items-center justify-center px-4">
        <div class="relative w-full max-w-7xl max-h-[75vh] bg-white rounded-3xl shadow-2xl flex flex-col overflow-y-auto z-[1050] modal-content" style="z-index:1051; transition: opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.95);">
            <div class="sticky top-0 z-20 bg-white border-b border-blue-100 px-10 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold warm-blue-text">
                        <span data-i18n="learnMoreModalTitle">Community Health Essentials</span>
                    </h2>
                    <p class="text-base text-gray-500 mt-1">
                        <span data-i18n="learnMoreModalSubtitle">A complete guide to wellness, prevention, and safety</span>
                    </p>
                </div>

                <button onclick="closeLearnMoreModal()"
                    class="w-12 h-12 flex items-center justify-center rounded-full
                           bg-blue-50 warm-blue-text hover:bg-blue-100 transition text-xl">
                    ✕
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-10 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-8">
                        <h3 class="flex items-center gap-4 text-2xl font-semibold warm-blue-text mb-6">
                            <span class="bg-blue-100 px-4 py-2 rounded-xl warm-blue-text">✔</span>
                            <span data-i18n="learnMoreTipsTitle">Daily Health Tips</span>
                        </h3>
                        <ul class="space-y-4 text-gray-700 text-lg leading-relaxed text-lead">
                            <li data-i18n="learnMoreTip1">Get 7-9 hours of quality sleep</li>
                            <li data-i18n="learnMoreTip2">Drink at least 8 glasses of water</li>
                            <li data-i18n="learnMoreTip3">Exercise for 30 minutes daily</li>
                            <li data-i18n="learnMoreTip4">Eat fruits and vegetables daily</li>
                            <li data-i18n="learnMoreTip5">Practice mindfulness or meditation</li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-8">
                        <h3 class="flex items-center gap-4 text-2xl font-semibold warm-blue-text mb-6">
                            <span class="bg-blue-100 px-4 py-2 rounded-xl">🩺</span>
                            <span data-i18n="learnMorePreventiveTitle">Preventive Care</span>
                        </h3>
                        <ul class="space-y-4 text-gray-700 text-lg text-lead">
                            <li data-i18n="learnMorePreventive1">Annual physical checkups</li>
                            <li data-i18n="learnMorePreventive2">Updated vaccinations</li>
                            <li data-i18n="learnMorePreventive3">Age-appropriate screenings</li>
                            <li data-i18n="learnMorePreventive4">Chronic condition monitoring</li>
                            <li data-i18n="learnMorePreventive5">Dental exams twice a year</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Announcement Modal -->
    <div id="singleAnnouncementModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur-sm">
        <div class="absolute inset-0 flex min-h-screen w-full items-center justify-center p-6 z-[1050]">
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[75vh] flex flex-col overflow-y-auto modal-content" style="z-index:1051; transition: opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.95);">
                <button onclick="closeSingleAnnouncementModal()"
                    class="absolute top-4 right-4 z-50 text-gray-500 hover:text-gray-700 bg-white rounded-full p-2 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-y-auto flex-1 p-8">
                    <div id="announcementContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="announcementsModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur-sm">
        <div class="absolute inset-0 flex min-h-screen w-full items-center justify-center p-6 z-[1050]">
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col overflow-y-auto modal-content" style="z-index:1051; transition: opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.95);">
                <button onclick="closeAnnouncementsModal()"
                    class="absolute top-4 right-4 z-50 text-gray-500 hover:text-gray-700 bg-white rounded-full p-2 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-y-auto flex-1">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-20 bg-white pb-4 mb-6 border-b border-gray-100">
        <div class="text-center">
            <div class="flex items-center justify-center gap-3 mb-3 pt-6">
                <div class="bg-blue-100 p-3 rounded-full">
                    <!-- SVG stays the same -->
                    <svg width="40" height="40" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M44.6367 16.9258L10.25 6.37891C9.78458 6.24316 9.29396 6.21756 8.81694 6.30414C8.33992 6.39071 7.88959 6.58709 7.50156 6.87773C7.11353 7.16838 6.79844 7.54532 6.58122 7.97874C6.364 8.41217 6.25061 8.89019 6.25 9.375V37.5C6.25 38.3288 6.57924 39.1237 7.16529 39.7097C7.75134 40.2958 8.5462 40.625 9.375 40.625C9.67383 40.6251 9.97113 40.5824 10.2578 40.4981L26.5625 35.4941V37.5C26.5625 38.3288 26.8917 39.1237 27.4778 39.7097C28.0638 40.2958 28.8587 40.625 29.6875 40.625H35.9375C36.7663 40.625 37.5612 40.2958 38.1472 39.7097C38.7333 39.1237 39.0625 38.3288 39.0625 37.5V31.6602L44.6367 29.9512C45.2819 29.7573 45.8476 29.3613 46.2506 28.8215C46.6536 28.2818 46.8725 27.6268 46.875 26.9531V19.9219C46.8721 19.2486 46.653 18.594 46.2501 18.0546C45.8471 17.5152 45.2815 17.1195 44.6367 16.9258ZM26.5625 32.2266L9.375 37.5V9.375L26.5625 14.6484V32.2266ZM35.9375 37.5H29.6875V34.5352L35.9375 32.6172V37.5ZM43.75 26.9531H43.7285L29.6875 31.2656V15.6094L43.7285 19.9063H43.75V26.9375V26.9531Z" fill="#0078DD"/>
</svg>

                </div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    All Announcements
                </h2>
            </div>
            <p class="text-gray-500 max-w-xl mx-auto text-sm leading-relaxed">
                Stay updated with all important announcements from BO. Luz Health Center
            </p>
        </div>
    </div>

    <?php if (empty($announcements)): ?>
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <i class="fas fa-bullhorn text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-sm">
                No announcements available at this time.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-6 px-4 sm:px-6">
            <?php foreach ($announcements as $announcement): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-200">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <?php if ($announcement['priority'] == 'high'): ?>
                                <span class="badge badge-high">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> High Priority
                                </span>
                            <?php elseif ($announcement['priority'] == 'medium'): ?>
                                <span class="badge badge-medium">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Medium Priority
                                </span>
                            <?php else: ?>
                                <span class="badge badge-normal">
                                    <i class="fas fa-info-circle mr-1"></i> Normal Priority
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400">
                            <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 leading-snug">
                        <?= htmlspecialchars($announcement['title']) ?>
                    </h3>

                    <!-- Image -->
                    <?php if ($announcement['image_path']): ?>
                        <div class="mb-4 rounded-xl overflow-hidden">
                            <img src="<?= htmlspecialchars($announcement['image_path']) ?>"
                                alt="<?= htmlspecialchars($announcement['title']) ?>"
                                class="w-full h-56 object-cover">
                        </div>
                    <?php endif; ?>

                    <!-- Message -->
                    <div class="text-gray-700 text-sm leading-relaxed space-y-3">
                        <?=
                            implode('', array_map(
                                fn($line) => "<p>" . htmlspecialchars($line) . "</p>",
                                array_filter(explode("\n", $announcement['message']))
                            ))
                        ?>
                    </div>

                    <!-- Footer -->
                    <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-calendar-alt"></i>
                            <span data-meta="posted">Posted: <?= date('F j, Y', strtotime($announcement['post_date'])) ?></span>
                        </div>

                        <?php if ($announcement['expiry_date']): ?>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                <span data-meta="valid-until">Valid until: <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

                <div class="p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <div class="text-center">
                        <p class="text-gray-500 mb-4">
                            For the latest updates, please check this section regularly or contact the Barangay Health Center.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const languageTranslations = {
            en: {"pageTitle":"Landing Page | Cebu Eastern College Incorporated - Health Monitoring and Tracking","siteName":"Barangay Luz","siteTagline":"Monitoring and Tracking","navHome":"Home","navAbout":"About","navServices":"Services","navContact":"Contact","residentLogin":"Resident Login","residentLoginShort":"Login","floatingViewAnnouncements":"View Announcements","floatingNewUpdates":"new update(s)","heroTitle":"Health Monitoring and Tracking","heroDescription":"Your trusted partner in community healthcare. <br>Providing accessible, quality healthcare services. <br>Providing accessible, quality healthcare services for <br>every resident of Barangay Luz, Cebu City.","learnMore":"Learn More","statMedicalStaff":"Medical Staff","statResidentsServed":"Residents Served","statYearsService":"Years Service","statMonthlyConsultations":"Monthly Consultations","locationCardTitle":"Location","availabilityCardTitle":"Availability","contactCardTitle":"Contact","statusActive":"Active","statusOpen":"Open Now","statusClosed":"Closed","locationSubtitle":"Near Luz Elementary School","googleMaps":"Google Maps","officeHoursLabel":"Office Hours :","officeHoursValue":"Monday-Friday, 8:00 AM - 5:00 PM","emergencyContactLabel":"Emergency Contact :","contactPersonLabel":"Contact Person :","preview":"Preview","landlineLabel":"Landline :","mobileNumberLabel":"Mobile Number :","emailLabel":"Official Email Address :","servicesTitle":"Our Health Services","servicesDescription":"Comprehensive healthcare services designed to meet the diverse needs of our community members","service1Title":"Primary Care Consultation","service1Description":"Comprehensive medical check-ups, diagnosis, and treatment for common illnesses.","service2Title":"Immunization Program","service2Description":"Complete vaccination schedule for children, adults, and senior citizens.","service3Title":"Emergency Services","service3Description":"24/7 emergency medical services with basic life support.","announcementsTitle":"Latest Announcements","announcementsDescription":"Stay informed with important updates, health advisories, and community events","noAnnouncementsTitle":"No Announcements Yet","noAnnouncementsDescription":"Check back soon for important health updates and community announcements.","priorityHigh":"High Priority","priorityMedium":"Medium Priority","priorityAnnouncement":"Normal Priority","datePosted":"Date Posted :","readAnnouncement":"Read Announcement","viewAllAnnouncements":"View All Announcements","aboutTitle":"What Our Community Says","aboutDescription":"Hear from our dedicated healthcare providers and community members","testimonial1Role":"Barangay Health Midwife","testimonial1Quote":"\"Our health center is committed to providing accessible and quality healthcare to every resident.\"","testimonial2Role":"Barangay Captain","testimonial2Quote":"\"The health monitoring system has transformed how we manage community health needs.\"","testimonial3Role":"Head Nurse","testimonial3Quote":"\"The digital health records system has made our work more efficient and focused.\"","footerAboutTitle":"Barangay Luz Health Center","footerAboutDescription":"Providing quality healthcare services to Barangay Luz residents with compassion and excellence.","footerQuickLinks":"Quick Links","footerOurServices":"Our Services","footerAnnouncements":"Announcements","footerAboutUs":"About Us","footerContactInfo":"Contact Info","footerOperatingHours":"Operating Hours","footerWeekdays":"Monday - Friday","footerSaturday":"Saturday","footerEmergency247":"Emergency services available 24/7","footerCopyright":"Barangay Luz Health Center. All rights reserved.","privacyPolicy":"Privacy Policy","termsOfService":"Terms of Service","privacyNoticeTitle":"Data Privacy & Security Notice","privacyNoticeSubtitle":"Your health information is protected and confidential","privacyConfidentialityTitle":"Confidentiality Assurance","privacyConfidentialityBody":"Barangay Luz Health Center strictly adheres to the Data Privacy Act of 2012 (RA 10173). Your personal and health information is confidential and will only be accessible to authorized healthcare personnel involved in your care.","privacyAccountSecurityTitle":"Personal Account Security","privacyAccountSecurityBody":"Each resident is provided with a unique username and password. Your login credentials are personal and should not be shared with anyone. The system tracks access to ensure only authorized viewing of your health records.","privacyAccessTitle":"What You Can Access","privacyAccessIntro":"Upon successful login, you will have access to:","privacyAccessItem1":"Your complete health history and medical records","privacyAccessItem2":"Immunization records and vaccination schedules","privacyAccessItem3":"Upcoming health appointments and consultations","privacyAccessItem4":"Health advisories and announcements specific to you","privacyAccessItem5":"Medication prescriptions and treatment plans","privacyRemindersTitle":"Important Reminders","privacyRemindersBody":"Never share your login credentials with others<br>Log out after each session, especially on shared devices<br>Report any unauthorized access to your account immediately<br>Update your password regularly for added security<br>Contact the Health Center for any account-related concerns","privacyProceed":"I Understand and Proceed","close":"Close","privacyFooterNote":"By proceeding, you acknowledge that you have read and understood the data privacy guidelines.","learnMoreModalTitle":"Community Health Essentials","learnMoreModalSubtitle":"A complete guide to wellness, prevention, and safety","learnMoreTipsTitle":"Daily Health Tips","learnMoreTip1":"Get 7-9 hours of quality sleep","learnMoreTip2":"Drink at least 8 glasses of water","learnMoreTip3":"Exercise for 30 minutes daily","learnMoreTip4":"Eat fruits and vegetables daily","learnMoreTip5":"Practice mindfulness or meditation","learnMorePreventiveTitle":"Preventive Care","learnMorePreventive1":"Annual physical checkups","learnMorePreventive2":"Updated vaccinations","learnMorePreventive3":"Age-appropriate screenings","learnMorePreventive4":"Chronic condition monitoring","learnMorePreventive5":"Dental exams twice a year","allAnnouncementsTitle":"All Announcements","allAnnouncementsSubtitle":"Stay updated with all important announcements from BO. Luz Health Center","noAnnouncementsModal":"No announcements available at this time.","postedLabel":"Posted:","validUntilLabel":"Valid until:","announcementsFooterNote":"For the latest updates, please check this section regularly or contact the Barangay Health Center.","loginModalInstruction":"Please log in with your authorized account to access health records, appointments, and other health services.","loginUsername":"Username","loginPassword":"Password","loginEnterUsername":"Enter Username","loginButton":"Login","loginRegisterNotice":"New students must register at the School Health Center to obtain their login credentials."},
            ceb: {"pageTitle":"Landing Page | Cebu Eastern College Incorporated - Pagsubay ug Pagmonitor sa Panglawas","siteName":"Cebu Eastern College Incorporated","siteTagline":"Pagsubay ug Pagmonitor","navHome":"Balay","navAbout":"Mahitungod","navServices":"Serbisyo","navContact":"Kontak","residentLogin":"Resident Login","residentLoginShort":"Login","floatingViewAnnouncements":"Tan-awa ang mga Anunsyo","floatingNewUpdates":"bag-ong update","heroTitle":"Pagsubay ug Pagmonitor sa Panglawas","heroDescription":"Imong kasaligan nga kauban sa panglawas sa komunidad. <br>Naghatag og dali maabot ug dekalidad nga serbisyo sa panglawas. <br>Naghatag og dali maabot ug dekalidad nga serbisyo sa panglawas para sa <br>matag residente sa Cebu Eastern College Incorporated, Cebu City.","learnMore":"Dugang Impormasyon","statMedicalStaff":"Medical Staff","statResidentsServed":"Mga Natabangan nga Residente","statYearsService":"Tuig sa Serbisyo","statMonthlyConsultations":"Bulanan nga Konsultasyon","locationCardTitle":"Lokasyon","availabilityCardTitle":"Anaa karon","contactCardTitle":"Kontak","statusActive":"Aktibo","statusOpen":"Abli Karon","statusClosed":"Sirado","locationSubtitle":"Duol sa Luz Elementary School","googleMaps":"Google Maps","officeHoursLabel":"Oras sa Opisina :","officeHoursValue":"Lunes-Biyernes, 8:00 AM - 5:00 PM","emergencyContactLabel":"Emergency Contact :","contactPersonLabel":"Kontak nga Tawo :","preview":"Tan-awa","landlineLabel":"Landline :","mobileNumberLabel":"Mobile Number :","emailLabel":"Opisyal nga Email Address :","servicesTitle":"Atong mga Serbisyo sa Panglawas","servicesDescription":"Komprehensibong mga serbisyo sa panglawas nga gihimo aron matubag ang lain-laing panginahanglan sa atong komunidad","service1Title":"Primaryang Konsultasyon","service1Description":"Komprehensibong check-up, diagnosis, ug pagtambal sa kasagarang sakit.","service2Title":"Programa sa Bakuna","service2Description":"Kompletong iskedyul sa bakuna para sa kabataan, hamtong, ug senior citizens.","service3Title":"Serbisyong Emerhensya","service3Description":"24/7 nga emerhensyang medikal nga serbisyo nga adunay basic life support.","announcementsTitle":"Pinakabag-ong mga Anunsyo","announcementsDescription":"Magpabiling updated sa importanteng mga update, health advisory, ug kalihokan sa komunidad","noAnnouncementsTitle":"Wala Pay Anunsyo","noAnnouncementsDescription":"Balik lang unya para sa mga importanteng update sa panglawas ug anunsyo sa komunidad.","priorityHigh":"Pinakaimportante","priorityMedium":"Importante","priorityAnnouncement":"Anunsyo","datePosted":"Petsa sa Pagpost :","readAnnouncement":"Basaha ang Anunsyo","viewAllAnnouncements":"Tan-awa ang Tanan nga Anunsyo","aboutTitle":"Unsay Giingon sa Atong Komunidad","aboutDescription":"Paminawa ang giingon sa atong healthcare providers ug mga miyembro sa komunidad","testimonial1Role":"Barangay Health Midwife","testimonial1Quote":"\"Ang among health center committed sa accessible ug quality healthcare para sa matag residente.\"","testimonial2Role":"Barangay Captain","testimonial2Quote":"\"Ang health monitoring system dako kaayong tabang sa pagdumala sa panginahanglan sa panglawas sa komunidad.\"","testimonial3Role":"Head Nurse","testimonial3Quote":"\"Ang digital health records system nakapahimo sa among trabaho nga mas episyente ug mas nakatutok.\"","footerAboutTitle":"Cebu Eastern College Incorporated Health Center","footerAboutDescription":"Naghatag og dekalidad nga serbisyo sa panglawas sa mga residente sa Cebu Eastern College Incorporated uban sa kalooy ug kahusayan.","footerQuickLinks":"Dali nga mga Link","footerOurServices":"Atong mga Serbisyo","footerAnnouncements":"Mga Anunsyo","footerAboutUs":"Mahitungod Kanamo","footerContactInfo":"Impormasyon sa Kontak","footerOperatingHours":"Oras sa Pag-opensina","footerWeekdays":"Lunes - Biyernes","footerSaturday":"Sabado","footerEmergency247":"Anaa ang emergency services 24/7","footerCopyright":"Cebu Eastern College Incorporated Health Center. Tanang katungod gitagana.","privacyPolicy":"Patakaran sa Privacy","termsOfService":"Mga Termino sa Serbisyo","privacyNoticeTitle":"Pahibalo sa Data Privacy ug Seguridad","privacyNoticeSubtitle":"Ang imong health information protektado ug kompidensyal","privacyConfidentialityTitle":"Pagsiguro sa Kompidensyalidad","privacyConfidentialityBody":"Ang Cebu Eastern College Incorporated Health Center hugot nga mosunod sa Data Privacy Act of 2012 (RA 10173). Ang imong personal ug health information kompidensyal ug para ra sa awtorisadong healthcare personnel nga nag-atiman nimo.","privacyAccountSecurityTitle":"Seguridad sa Personal nga Account","privacyAccountSecurityBody":"Ang matag residente adunay talagsaon nga username ug password. Personal kini ug dili angay ipaambit sa uban. Ang sistema mosubay sa access aron masiguro nga awtorisado ra ang makakita sa imong health records.","privacyAccessTitle":"Unsa ang Imong Ma-access","privacyAccessIntro":"Sa malampusong pag-login, maka-access ka sa:","privacyAccessItem1":"Imong kumpletong health history ug medical records","privacyAccessItem2":"Immunization records ug vaccination schedules","privacyAccessItem3":"Umaabot nga health appointments ug consultations","privacyAccessItem4":"Health advisories ug mga anunsyo nga para nimo","privacyAccessItem5":"Mga reseta ug treatment plans","privacyRemindersTitle":"Importante nga mga Pahinumdom","privacyRemindersBody":"Ayaw gyud ipaambit ang imong login credentials sa uban<br>Pag log out kada human ug gamit, labi na sa shared devices<br>Ireport dayon ang bisan unsang dili awtorisadong access sa imong account<br>Usba kanunay ang imong password para sa dugang seguridad<br>Kontaka ang Health Center kung adunay concern sa account","privacyProceed":"Nasabtan Ko ug Mopadayon","close":"Sirado","privacyFooterNote":"Sa pagpadayon, nagpasabot ka nga nabasa ug nasabtan nimo ang data privacy guidelines.","learnMoreModalTitle":"Mga Basikong Kahibalo sa Panglawas sa Komunidad","learnMoreModalSubtitle":"Kompletong giya sa kaayohan, paglikay, ug kaluwasan","learnMoreTipsTitle":"Adlaw-adlaw nga Health Tips","learnMoreTip1":"Pagkuha og 7-9 ka oras nga tulog","learnMoreTip2":"Pag-inom og labing menos 8 ka baso nga tubig","learnMoreTip3":"Pag-ehersisyo sulod sa 30 minutos kada adlaw","learnMoreTip4":"Pagkaon og prutas ug utanon kada adlaw","learnMoreTip5":"Pagpraktis og mindfulness o meditation","learnMorePreventiveTitle":"Preventive Care","learnMorePreventive1":"Tuigang physical checkups","learnMorePreventive2":"Updated nga mga bakuna","learnMorePreventive3":"Screenings nga angay sa edad","learnMorePreventive4":"Pagmonitor sa chronic condition","learnMorePreventive5":"Dental exam kaduha sa usa ka tuig","allAnnouncementsTitle":"Tanan nga Anunsyo","allAnnouncementsSubtitle":"Magpabiling updated sa tanang importanteng anunsyo gikan sa BO. Luz Health Center","noAnnouncementsModal":"Walay anunsyo karong orasa.","postedLabel":"Gi-post:","validUntilLabel":"Balido hangtod:","announcementsFooterNote":"Para sa pinakabag-ong update, palihug kanunay nga tan-awa kini nga seksyon o kontaka ang Barangay Health Center.","loginModalInstruction":"Palihug pag-login gamit ang imong awtorisadong account aron maka-access sa health records, appointments, ug uban pang health services.","loginUsername":"Username","loginPassword":"Password","loginEnterUsername":"Ibutang ang Username","loginButton":"Login","loginRegisterNotice":"Ang mga bag-ong residente kinahanglan morehistro sa Barangay Health Center aron makakuha og login credentials."},
            tl: {"pageTitle":"Landing Page | Cebu Eastern College Incorporated - Pagsubaybay at Pagmomonitor ng Kalusugan","siteName":"Cebu Eastern College Incorporated","siteTagline":"Pagsubaybay at Pagmomonitor","navHome":"Home","navAbout":"Tungkol","navServices":"Serbisyo","navContact":"Kontak","residentLogin":"Resident Login","residentLoginShort":"Login","floatingViewAnnouncements":"Tingnan ang mga Anunsyo","floatingNewUpdates":"bagong update","heroTitle":"Pagsubaybay at Pagmomonitor ng Kalusugan","heroDescription":"Ang iyong mapagkakatiwalaang katuwang sa pangkalusugan ng komunidad. <br>Nagbibigay ng abot-kaya at dekalidad na serbisyong pangkalusugan. <br>Nagbibigay ng abot-kaya at dekalidad na serbisyong pangkalusugan para sa <br>bawat residente ng Cebu Eastern College Incorporated, Cebu City.","learnMore":"Alamin Pa","statMedicalStaff":"Medical Staff","statResidentsServed":"Mga Naserbisyuhang Residente","statYearsService":"Taon ng Serbisyo","statMonthlyConsultations":"Buwanang Konsultasyon","locationCardTitle":"Lokasyon","availabilityCardTitle":"Availability","contactCardTitle":"Kontak","statusActive":"Aktibo","statusOpen":"Bukas Ngayon","statusClosed":"Sarado","locationSubtitle":"Malapit sa Luz Elementary School","googleMaps":"Google Maps","officeHoursLabel":"Oras ng Opisina :","officeHoursValue":"Lunes-Biyernes, 8:00 AM - 5:00 PM","emergencyContactLabel":"Emergency Contact :","contactPersonLabel":"Contact Person :","preview":"Preview","landlineLabel":"Landline :","mobileNumberLabel":"Mobile Number :","emailLabel":"Opisyal na Email Address :","servicesTitle":"Aming Mga Serbisyong Pangkalusugan","servicesDescription":"Komprehensibong serbisyong pangkalusugan na idinisenyo para matugunan ang iba-ibang pangangailangan ng aming komunidad","service1Title":"Pangunahing Konsultasyon","service1Description":"Komprehensibong check-up, diagnosis, at paggamot para sa mga karaniwang sakit.","service2Title":"Programa sa Immunization","service2Description":"Kumpletong iskedyul ng bakuna para sa mga bata, adulto, at senior citizen.","service3Title":"Serbisyong Pang-emergency","service3Description":"24/7 na serbisyong medikal na pang-emergency na may basic life support.","announcementsTitle":"Pinakabagong Mga Anunsyo","announcementsDescription":"Manatiling updated sa mahahalagang abiso, health advisory, at mga pangkomunidad na kaganapan","noAnnouncementsTitle":"Wala Pang Mga Anunsyo","noAnnouncementsDescription":"Bumalik muli para sa mahahalagang health update at anunsyo ng komunidad.","priorityHigh":"Mataas na Prayoridad","priorityMedium":"Katamtamang Prayoridad","priorityAnnouncement":"Anunsyo","datePosted":"Petsa ng Pag-post :","readAnnouncement":"Basahin ang Anunsyo","viewAllAnnouncements":"Tingnan Lahat ng Anunsyo","aboutTitle":"Sinasabi ng Ating Komunidad","aboutDescription":"Pakinggan ang mga pahayag ng ating healthcare providers at mga miyembro ng komunidad","testimonial1Role":"Barangay Health Midwife","testimonial1Quote":"\"Ang aming health center ay nakatuon sa pagbibigay ng abot-kaya at dekalidad na healthcare para sa bawat residente.\"","testimonial2Role":"Barangay Captain","testimonial2Quote":"\"Malaki ang naitulong ng health monitoring system sa pamamahala ng pangangailangan sa kalusugan ng komunidad.\"","testimonial3Role":"Head Nurse","testimonial3Quote":"\"Mas naging episyente at mas nakatutok ang aming trabaho dahil sa digital health records system.\"","footerAboutTitle":"Cebu Eastern College Incorporated Health Center","footerAboutDescription":"Nagbibigay ng dekalidad na serbisyong pangkalusugan sa mga residente ng Cebu Eastern College Incorporated nang may malasakit at kahusayan.","footerQuickLinks":"Mabilis na Links","footerOurServices":"Aming Mga Serbisyo","footerAnnouncements":"Mga Anunsyo","footerAboutUs":"Tungkol sa Amin","footerContactInfo":"Impormasyon sa Kontak","footerOperatingHours":"Oras ng Operasyon","footerWeekdays":"Lunes - Biyernes","footerSaturday":"Sabado","footerEmergency247":"Available ang emergency services 24/7","footerCopyright":"Cebu Eastern College Incorporated Health Center. Lahat ng karapatan ay nakalaan.","privacyPolicy":"Patakaran sa Privacy","termsOfService":"Mga Tuntunin ng Serbisyo","privacyNoticeTitle":"Abiso sa Data Privacy at Seguridad","privacyNoticeSubtitle":"Protektado at kumpidensyal ang iyong health information","privacyConfidentialityTitle":"Pagtiyak sa Kumpidensyalidad","privacyConfidentialityBody":"Mahigpit na sumusunod ang Cebu Eastern College Incorporated Health Center sa Data Privacy Act of 2012 (RA 10173). Ang iyong personal at health information ay kumpidensyal at maa-access lamang ng awtorisadong healthcare personnel na kasangkot sa iyong pangangalaga.","privacyAccountSecurityTitle":"Seguridad ng Personal na Account","privacyAccountSecurityBody":"Bawat residente ay may natatanging username at password. Personal ito at hindi dapat ibahagi sa iba. Sinusubaybayan ng sistema ang access upang matiyak na awtorisado lamang ang makakakita ng iyong health records.","privacyAccessTitle":"Ano ang Maaari Mong Ma-access","privacyAccessIntro":"Kapag matagumpay ang pag-login, magkakaroon ka ng access sa:","privacyAccessItem1":"Kumpleto mong health history at medical records","privacyAccessItem2":"Immunization records at vaccination schedules","privacyAccessItem3":"Mga darating na health appointment at consultation","privacyAccessItem4":"Health advisory at mga anunsyong para sa iyo","privacyAccessItem5":"Mga reseta at treatment plan","privacyRemindersTitle":"Mahahalagang Paalala","privacyRemindersBody":"Huwag kailanman ibahagi ang iyong login credentials sa iba<br>Mag log out pagkatapos ng bawat session, lalo na sa shared devices<br>Ireport agad ang anumang hindi awtorisadong access sa iyong account<br>Regular na palitan ang iyong password para sa dagdag na seguridad<br>Makipag-ugnayan sa Health Center para sa anumang concern na may kinalaman sa account","privacyProceed":"Nauunawaan Ko at Magpapatuloy","close":"Isara","privacyFooterNote":"Sa pagpapatuloy, kinikilala mong nabasa at naunawaan mo ang data privacy guidelines.","learnMoreModalTitle":"Mahahalagang Kaalaman sa Kalusugan ng Komunidad","learnMoreModalSubtitle":"Kumpletong gabay sa wellness, prevention, at safety","learnMoreTipsTitle":"Pang-araw-araw na Health Tips","learnMoreTip1":"Matulog nang 7-9 oras na may kalidad","learnMoreTip2":"Uminom ng hindi bababa sa 8 basong tubig","learnMoreTip3":"Mag-ehersisyo ng 30 minuto araw-araw","learnMoreTip4":"Kumain ng prutas at gulay araw-araw","learnMoreTip5":"Magsanay ng mindfulness o meditation","learnMorePreventiveTitle":"Preventive Care","learnMorePreventive1":"Taunang physical checkup","learnMorePreventive2":"Updated na mga bakuna","learnMorePreventive3":"Mga screening na naaayon sa edad","learnMorePreventive4":"Pagsubaybay sa chronic condition","learnMorePreventive5":"Dental exam dalawang beses sa isang taon","allAnnouncementsTitle":"Lahat ng Anunsyo","allAnnouncementsSubtitle":"Manatiling updated sa lahat ng mahahalagang anunsyo mula sa BO. Luz Health Center","noAnnouncementsModal":"Walang available na anunsyo sa ngayon.","postedLabel":"Na-post:","validUntilLabel":"Balido hanggang:","announcementsFooterNote":"Para sa pinakabagong update, pakisuri nang regular ang seksyong ito o makipag-ugnayan sa Barangay Health Center.","loginModalInstruction":"Mangyaring mag-login gamit ang iyong awtorisadong account upang ma-access ang health records, appointments, at iba pang health services.","loginUsername":"Username","loginPassword":"Password","loginEnterUsername":"Ilagay ang Username","loginButton":"Login","loginRegisterNotice":"Ang mga bagong residente ay kailangang magparehistro sa Barangay Health Center upang makakuha ng login credentials."}
        };
        let currentLanguage = 'en';
        const availabilityState = <?php echo json_encode($isWeekend ? 'closed' : 'open'); ?>;

        function getTranslation(key) {
            return languageTranslations[currentLanguage]?.[key] ?? languageTranslations.en[key] ?? key;
        }

        function applySelectorText(selector, value) {
            const element = document.querySelector(selector);
            if (element && value) {
                element.textContent = value;
            }
        }

        function applySelectorPlaceholder(selector, value) {
            const element = document.querySelector(selector);
            if (element && value) {
                element.placeholder = value;
            }
        }

        function translateAnnouncementMeta() {
            document.querySelectorAll('#announcementsModal .badge').forEach((badge) => {
                if (badge.classList.contains('badge-high')) {
                    badge.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> ${getTranslation('priorityHigh')}`;
                } else if (badge.classList.contains('badge-medium')) {
                    badge.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i> ${getTranslation('priorityMedium')}`;
                } else if (badge.classList.contains('badge-normal')) {
                    badge.innerHTML = `<i class="fas fa-info-circle mr-1"></i> ${getTranslation('priorityAnnouncement')}`;
                }
            });

            document.querySelectorAll('#announcementsModal span[data-meta]').forEach((span) => {
                const originalText = span.textContent.trim();
                const dateText = originalText.includes(':') ? originalText.split(':').slice(1).join(':').trim() : '';
                if (span.dataset.meta === 'posted') {
                    span.textContent = `${getTranslation('postedLabel')} ${dateText}`;
                } else if (span.dataset.meta === 'valid-until') {
                    span.textContent = `${getTranslation('validUntilLabel')} ${dateText}`;
                }
            });
        }

        function applyTranslations(lang) {
            currentLanguage = languageTranslations[lang] ? lang : 'en';
            localStorage.setItem('preferredLanguage', currentLanguage);
            document.documentElement.lang = currentLanguage === 'ceb' ? 'ceb' : currentLanguage;
            document.title = getTranslation('pageTitle');

            document.querySelectorAll('[data-i18n]').forEach((element) => {
                element.textContent = getTranslation(element.dataset.i18n);
            });

            document.querySelectorAll('[data-i18n-html]').forEach((element) => {
                element.innerHTML = getTranslation(element.dataset.i18nHtml);
            });

            const availabilityStatus = document.getElementById('availabilityStatus');
            if (availabilityStatus) {
                availabilityStatus.textContent = availabilityState === 'closed' ? getTranslation('statusClosed') : getTranslation('statusOpen');
            }

            applySelectorText('#learnMoreModal h2', getTranslation('learnMoreModalTitle'));
            applySelectorText('#learnMoreModal p', getTranslation('learnMoreModalSubtitle'));
            applySelectorText('#announcementsModal h2', getTranslation('allAnnouncementsTitle'));
            applySelectorText('#announcementsModal .sticky p', getTranslation('allAnnouncementsSubtitle'));
            applySelectorText('#announcementsModal .flex.flex-col.items-center.justify-center p', getTranslation('noAnnouncementsModal'));
            applySelectorText('#announcementsModal .p-6.border-t p', getTranslation('announcementsFooterNote'));
            applySelectorText('#loginModal .text-sm.text-center.text-gray-600', getTranslation('loginModalInstruction'));
            applySelectorText('label[for=\"login-username\"]', getTranslation('loginUsername') + ' *');
            applySelectorText('label[for=\"login-password\"]', getTranslation('loginPassword') + ' *');
            applySelectorPlaceholder('#login-username', getTranslation('loginEnterUsername'));
            applySelectorPlaceholder('#login-password', getTranslation('loginPassword'));
            applySelectorText('#loginModal button[type=\"submit\"]', getTranslation('loginButton'));
            applySelectorText('#loginModal .text-center.text-sm.text-gray-600.mt-6 p', getTranslation('loginRegisterNotice'));

            translateAnnouncementMeta();
        }

        function synchronizeLanguageSelectors() {
            const desktop = document.getElementById('languageSwitcher');
            const mobile = document.getElementById('languageSwitcherMobile');

            if (desktop) desktop.value = currentLanguage;
            if (mobile) mobile.value = currentLanguage;
        }

        function initializeLanguage() {
            applyTranslations(localStorage.getItem('preferredLanguage') || 'en');
            synchronizeLanguageSelectors();
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('show');
        }

        // Modal functions
        function openLoginModal() {
            const modal = document.getElementById("loginModal");
            const modalContent = modal.querySelector('.modal-content');

            modal.classList.remove("hidden");
            modal.classList.add("flex");
            // Do not lock body scroll for login modal
            // Trigger animation
            setTimeout(() => {
                modalContent.classList.add('open');
            }, 10);
            // Set focus to username input for accessibility
            setTimeout(() => {
                document.getElementById('login-username').focus();
            }, 50);
        }

        function closeLoginModal() {
            const modal = document.getElementById("loginModal");
            const modalContent = modal.querySelector('.modal-content');

            modalContent.classList.remove('open');
            // Wait for animation to complete before hiding
            setTimeout(() => {
                modal.classList.remove("flex");
                modal.classList.add("hidden");
                // Do not change body scroll for login modal
            }, 300);
        }

        function toggleLoginPassword() {
            const input = document.getElementById("login-password");
            const icon = document.getElementById("login-eyeIcon");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Instruction Modal Functions
        function openInstructionModal() {
            const modal = document.getElementById("instructionModal");
            const modalContent = modal.querySelector('.modal-content');

            modal.classList.remove("hidden");
            modal.classList.add("flex");
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modalContent.classList.add('open');
            }, 10);
        }

        function closeInstructionModal() {
            const modal = document.getElementById("instructionModal");
            const modalContent = modal.querySelector('.modal-content');

            modalContent.classList.remove('open');
            setTimeout(() => {
                modal.classList.remove("flex");
                modal.classList.add("hidden");
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Function to scroll to announcements section
        function scrollToAnnouncements() {
            const announcementsSection = document.getElementById('announcementsSection');
            if (announcementsSection) {
                // Use scrollIntoView with offset
                const headerHeight = document.querySelector('.main-header').offsetHeight;
                const targetPosition = announcementsSection.offsetTop - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        }

        // Announcement Modal Functions

        function openAnnouncementsModal() {
            const modal = document.getElementById('announcementsModal');
            const modalContent = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'scale(1)';
            }, 10);
        }

        function closeAnnouncementsModal() {
            const modal = document.getElementById('announcementsModal');
            const modalContent = modal.querySelector('.modal-content');
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }

        function trackAnnouncementView(announcementId, index) {
            if (!announcementId) {
                return;
            }

            const payload = new URLSearchParams();
            payload.append('track_announcement_view', '1');
            payload.append('announcement_id', announcementId);

            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: payload.toString()
            })
                .then(response => response.ok ? response.json() : null)
                .then(result => {
                    if (result && result.success && announcementsData[index]) {
                        announcementsData[index].seen_count = result.seen_count;
                    }
                })
                .catch(error => {
                    console.error('Error tracking announcement view:', error);
                });
        }

        function openAnnouncementModal(index) {
            // Check if the announcement exists
            if (!announcementsData || !announcementsData[index]) {
                console.error('Announcement not found at index:', index);
                return;
            }

            const announcement = announcementsData[index];
            const contentDiv = document.getElementById('announcementContent');
            trackAnnouncementView(announcement.id, index);

            // Format date
            const postDate = new Date(announcement.post_date);
            const formattedDate = postDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Determine priority badge styling
            let badgeClass = 'bg-blue-100 text-blue-700';
            let badgeText = getTranslation('priorityAnnouncement');
            if (announcement.priority === 'high') {
                badgeClass = 'bg-red-100 text-red-700';
                badgeText = getTranslation('priorityHigh');
            } else if (announcement.priority === 'medium') {
                badgeClass = 'bg-yellow-100 text-yellow-700';
                badgeText = getTranslation('priorityMedium');
            }

            // Build announcement HTML matching the display announcement card layout
            let html = `
                <div class="announcement-detail">
    <!-- Badge and Date Header -->
    <div class="flex items-start justify-between mb-8">
        <div>
            <span class="inline-block px-4 py-2 rounded text-sm font-semibold ${badgeClass}">
                ${badgeText}
            </span>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500 font-medium">${getTranslation('datePosted')}</div>
            <div class="text-lg font-semibold text-blue-600">
                ${formattedDate}
            </div>
        </div>
    </div>
    
    <!-- Title -->
    <h2 class="text-2xl font-semibold text-gray-900 mb-6 leading-snug">
        ${announcement.title}
    </h2>
    
    <!-- Image -->
    ${announcement.image_path ? `
        <div class="mb-8 rounded-xl overflow-hidden shadow-sm">
            <img src="${announcement.image_path}" 
                 alt="${announcement.title}" 
                 class="w-full rounded-md max-h-96 object-cover">
        </div>
    ` : ''}
    
    <!-- Message -->
    <div class="text-gray-700 text-base leading-relaxed space-y-4 whitespace-pre-line break-words">
        ${announcement.message}
    </div>
</div>
            `;

            contentDiv.innerHTML = html;

            // Show the modal
            const modal = document.getElementById('singleAnnouncementModal');
            const modalContent = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'scale(1)';
            }, 10);
        }

        function closeSingleAnnouncementModal() {
            const modal = document.getElementById('singleAnnouncementModal');
            const modalContent = modal.querySelector('.modal-content');
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Learn More Modal Functions
        function openLearnMoreModal() {
            const modal = document.getElementById('learnMoreModal');
            const modalContent = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'scale(1)';
            }, 10);
        }

        function closeLearnMoreModal() {
            const modal = document.getElementById('learnMoreModal');
            const modalContent = modal.querySelector('.modal-content');
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const announcementsModal = document.getElementById('announcementsModal');
            const singleAnnouncementModal = document.getElementById('singleAnnouncementModal');
            const learnMoreModal = document.getElementById('learnMoreModal');
            const loginModal = document.getElementById('loginModal');
            const instructionModal = document.getElementById('instructionModal');

            if (announcementsModal && !announcementsModal.classList.contains('hidden') &&
                event.target === announcementsModal) {
                closeAnnouncementsModal();
            }

            if (singleAnnouncementModal && !singleAnnouncementModal.classList.contains('hidden') &&
                event.target === singleAnnouncementModal) {
                closeSingleAnnouncementModal();
            }

            if (learnMoreModal && !learnMoreModal.classList.contains('hidden') &&
                event.target === learnMoreModal) {
                closeLearnMoreModal();
            }

            if (loginModal && !loginModal.classList.contains('hidden') &&
                event.target === loginModal) {
                closeLoginModal();
            }

            if (instructionModal && !instructionModal.classList.contains('hidden') &&
                event.target === instructionModal) {
                closeInstructionModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAnnouncementsModal();
                closeSingleAnnouncementModal();
                closeLearnMoreModal();
                closeLoginModal();
                closeInstructionModal();
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobile-menu');
                    if (mobileMenu.classList.contains('show')) {
                        mobileMenu.classList.remove('show');
                    }

                    // Calculate scroll position accounting for fixed header
                    const headerHeight = document.querySelector('.main-header').offsetHeight;
                    const targetPosition = targetElement.offsetTop - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Form submission handlers
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });

        // Update active nav link on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            const headerHeight = document.querySelector('.main-header').offsetHeight;

            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (window.scrollY >= (sectionTop - headerHeight - 50)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}` ||
                    (current === '' && link.getAttribute('href') === '#')) {
                    link.classList.add('active');
                }
            });
        });

        // Initialize modal animations
        document.addEventListener('DOMContentLoaded', function() {
            const languageSwitcher = document.getElementById('languageSwitcher');
            const languageSwitcherMobile = document.getElementById('languageSwitcherMobile');

            [languageSwitcher, languageSwitcherMobile].forEach((select) => {
                if (!select) {
                    return;
                }

                select.value = currentLanguage;
                select.addEventListener('change', function() {
                    applyTranslations(this.value);
                    synchronizeLanguageSelectors();
                });
            });

            // Add click handlers for all login buttons
            document.querySelectorAll('[onclick*="openLoginModal"]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openLoginModal();
                });
            });

            // Update scroll padding based on actual header height
            const headerHeight = document.querySelector('.main-header').offsetHeight;
            document.documentElement.style.scrollPaddingTop = headerHeight + 'px';

            // Set scroll margin for all sections
            document.querySelectorAll('section[id]').forEach(section => {
                section.style.scrollMarginTop = headerHeight + 'px';
            });
        });
    </script>
</body>

</html>

