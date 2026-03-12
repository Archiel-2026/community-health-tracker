<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Fetch active announcements for landing page
$announcements = [];
$hasAnnouncements = false;
// Fetch Medical Staff count
$medicalStaffCount = 0;
// Fetch Residents Served count
$residentsServedCount = 0;
try {
    $stmt = $pdo->prepare("SELECT title, message, priority, post_date, expiry_date, image_path 
                          FROM sitio1_announcements 
                          WHERE status = 'active' AND audience_type = 'landing_page' 
                          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                          ORDER BY 
                            CASE priority 
                                WHEN 'high' THEN 1
                                WHEN 'medium' THEN 2
                                WHEN 'normal' THEN 3
                            END,
                            post_date DESC");
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
$badgeClass = $isWeekend ? 'bg-red-100 text-red-500' : 'bg-green-100 text-green-500';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Luz - Health Monitoring and Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Show login modal and error if redirected with ?login=invalid
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('login') === 'invalid') {
                openLoginModal();
                setTimeout(function() {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                    errorMsg.innerHTML = '<strong class="font-bold">Invalid Credentials:</strong> <span class="block sm:inline">Please check your username and password.</span>';
                    const modalContent = document.querySelector('#loginModal .modal-content');
                    if (modalContent) {
                        modalContent.insertBefore(errorMsg, modalContent.firstChild);
                    }
                }, 200);
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
            border-radius: 2px;
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
            border-radius: 2px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(58, 123, 213, 0.1);
            border: 1px solid rgba(58, 123, 213, 0.1);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(58, 123, 213, 0.15);
        }

        .service-icon {
            width: 60px;
            height: 60px;

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
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4A90E2;
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
            color: #4A90E2;
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
            background: linear-gradient(135deg, rgba(58, 123, 213, 0.85) 0%, rgba(42, 107, 197, 0.85) 100%), url('./asssets/images/brgyluz.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Services Section Background */
        .services-bg {
            position: relative;
            background: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), url('./asssets/images/brgyluz.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Announcements Section Background */
        .announcements-bg {
            position: relative;
            background: linear-gradient(rgba(74, 144, 226, 0.85), rgba(58, 123, 213, 0.85)), url('./asssets/images/brgyluz.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* About Section Background */
        .about-bg {
            position: relative;
            background: linear-gradient(rgba(255, 255, 255, 0.93), rgba(255, 255, 255, 0.93)), url('./asssets/images/brgyluz.jpg');
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
                            <img src="./asssets/images/Luz.jpg" alt="Barangay Luz Logo"
                                class="circle-image mr-4">
                            <div class="logo-text">
                                <div class="font-bold text-xl leading-tight">Barangay Luz</div>
                                <div class="text-lg text-gray-700">Monitoring and Tracking</div>
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
                                        class="nav-link text-gray-800 hover:text-[#4A90E2] transition-all duration-300 ease-in-out">Home</a>
                                </li>
                                <li>
                                    <a href="#about"
                                        class="nav-link text-gray-800 hover:text-[#4A90E2] transition-all duration-300 ease-in-out">About</a>
                                </li>
                                <li>
                                    <a href="#services"
                                        class="nav-link text-gray-800 hover:text-[#4A90E2] transition-all duration-300 ease-in-out">Services</a>
                                </li>
                                <li>
                                    <a href="#footer"
                                        class="nav-link text-gray-800 hover:text-[#4A90E2] transition-all duration-300 ease-in-out">Contact</a>
                                </li>
                            </ul>
                        </div>

                        <!-- Login button - positioned to the right -->
                        <div class="hidden md:flex items-center">
                            <a href="#" onclick="openLoginModal()"
                                class="bg-[#4A90E2] text-lg rounded-lg text-white flex items-center justify-center shadow-md hover:shadow-lg px-6 py-3">
                                Resident Login
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Mobile menu content - only shows on mobile -->
        <div id="mobile-menu" class="mobile-menu md:hidden bg-white border-t border-gray-200 shadow-lg">
            <div class="px-4 pt-4 pb-6 space-y-2">
                <a href="#home" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link">Home</a>
                <a href="#about" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link">About</a>
                <a href="#services" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link">Services</a>
                <a href="#footer" onclick="toggleMobileMenu()" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-[#4A90E2] rounded-lg transition-all duration-300 nav-link">Contact</a>
                <a href="#" onclick="openLoginModal(); toggleMobileMenu();"
                    class="complete-btn bg-[#4A90E2] text-white px-5 py-3 transition-all text-center mt-4 flex items-center justify-center gap-2 nav-link shadow-md hover:shadow-lg">
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
                        class="w-14 h-14 bg-gradient-to-br from-[#3a7bd5] to-[#2a6bc5] rounded-full shadow-xl hover:shadow-2xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center group relative border-3 border-white ring-3 ring-blue-300 ring-opacity-50 animate-float">

                        <div class="relative">
                            <svg width="35" height="35" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M37.4948 14.2183L8.61 5.35891C8.21905 5.24488 7.80693 5.22338 7.40623 5.2961C7.00554 5.36883 6.62726 5.53378 6.30131 5.77792C5.97537 6.02206 5.71069 6.33869 5.52823 6.70277C5.34576 7.06685 5.25051 7.46838 5.25 7.87563V31.5006C5.25 32.1968 5.52656 32.8645 6.01884 33.3568C6.51113 33.8491 7.17881 34.1256 7.875 34.1256C8.12601 34.1257 8.37575 34.0898 8.61656 34.019L22.3125 29.8157V31.5006C22.3125 32.1968 22.5891 32.8645 23.0813 33.3568C23.5736 33.8491 24.2413 34.1256 24.9375 34.1256H30.1875C30.8837 34.1256 31.5514 33.8491 32.0437 33.3568C32.5359 32.8645 32.8125 32.1968 32.8125 31.5006V26.5952L37.4948 25.1596C38.0368 24.9968 38.512 24.6641 38.8505 24.2107C39.1891 23.7573 39.3729 23.2071 39.375 22.6413V16.735C39.3726 16.1694 39.1885 15.6196 38.85 15.1665C38.5116 14.7134 38.0365 14.381 37.4948 14.2183ZM22.3125 27.0709L7.875 31.5006V7.87563L22.3125 12.3053V27.0709ZM30.1875 31.5006H24.9375V29.0102L30.1875 27.3991V31.5006ZM36.75 22.6413H36.732L24.9375 26.2638V13.1125L36.732 16.7219H36.75V22.6281V22.6413Z" fill="white" />
                            </svg>



                        </div>

                        <div class="absolute right-full ml-3 top-1/2 transform -translate-y-1/2 hidden group-hover:block min-w-max z-50">
                            <div class="bg-gray-900 text-white text-sm rounded-lg py-2 px-3 shadow-xl">
                                <span class="font-md">View Announcements</span>
                                <div class="text-xs text-gray-300 mt-1"><?= count($announcements) ?> new update(s)</div>
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
                        <h1 class="text-3xl md:text-4xl font-semibold font-md leading-tight mb-6 mt-8 md:mt-14">
                            Health Monitoring and Tracking
                        </h1>

                        <p class="text-base md:text-lg text-white mb-10">
                            Your trusted partner in community healthcare. <br>
                            Providing accessible, quality healthcare services. <br>
                            Providing accessible, quality healthcare services for <br>
                            every resident of Barangay Luz, Cebu City.
                        </p>

                        <button>
                            <div class="py-3 px-6  bg-[#3879D3] rounded-lg text-white font-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 w-max">
                                <p>Learn More</p>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </button>
                    </div>

                    <!-- RIGHT -->
                    <div class="md:w-1/2 lg:w-2/5 xl:w-1/2 md:pr-14 lg:pr-20">
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 md:gap-6 justify-center mt-5">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Medical Staff</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $medicalStaffCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Residents Served</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $residentsServedCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Years Service</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1">28</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Monthly Consultations</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $monthlyConsultationCount ?></div>
                            </div>
                        </div>
                    </div>


                </div>


                <!-- Quick Info Cards -->
                <div class="flex flex-col md:flex-row gap-24 mb-12 w-full">
                    <!-- Location Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#4A90E2] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.5 7.5C10.5 7.20333 10.588 6.91332 10.7528 6.66664C10.9176 6.41997 11.1519 6.22771 11.426 6.11418C11.7001 6.00065 12.0017 5.97094 12.2926 6.02882C12.5836 6.0867 12.8509 6.22956 13.0607 6.43934C13.2704 6.64912 13.4133 6.91639 13.4712 7.20736C13.5291 7.49834 13.4994 7.79994 13.3858 8.07402C13.2723 8.34811 13.08 8.58238 12.8334 8.7472C12.5867 8.91203 12.2967 9 12 9C11.6022 9 11.2206 8.84196 10.9393 8.56066C10.658 8.27936 10.5 7.89782 10.5 7.5ZM6 7.5C6 5.9087 6.63214 4.38258 7.75736 3.25736C8.88258 2.13214 10.4087 1.5 12 1.5C13.5913 1.5 15.1174 2.13214 16.2426 3.25736C17.3679 4.38258 18 5.9087 18 7.5C18 13.1203 12.6019 16.2694 12.375 16.4016C12.2617 16.4663 12.1334 16.5004 12.0028 16.5004C11.8723 16.5004 11.744 16.4663 11.6306 16.4016C11.3981 16.2694 6 13.125 6 7.5ZM7.5 7.5C7.5 11.4563 10.86 14.0822 12 14.8594C13.1391 14.0831 16.5 11.4563 16.5 7.5C16.5 6.30653 16.0259 5.16193 15.182 4.31802C14.3381 3.47411 13.1935 3 12 3C10.8065 3 9.66193 3.47411 8.81802 4.31802C7.97411 5.16193 7.5 6.30653 7.5 7.5ZM19.0097 13.8403C18.8251 13.7793 18.624 13.7924 18.4489 13.8768C18.2738 13.9612 18.1382 14.1102 18.0709 14.2926C18.0035 14.475 18.0096 14.6764 18.0879 14.8543C18.1661 15.0323 18.3104 15.1729 18.4903 15.2466C20.0381 15.8194 21 16.5863 21 17.25C21 18.5025 17.5762 20.25 12 20.25C6.42375 20.25 3 18.5025 3 17.25C3 16.5863 3.96188 15.8194 5.50969 15.2475C5.6896 15.1739 5.8339 15.0332 5.91215 14.8553C5.99039 14.6773 5.99648 14.4759 5.92913 14.2935C5.86178 14.1112 5.72624 13.9621 5.5511 13.8777C5.37596 13.7933 5.1749 13.7803 4.99031 13.8412C2.73937 14.6709 1.5 15.8822 1.5 17.25C1.5 20.1731 6.91031 21.75 12 21.75C17.0897 21.75 22.5 20.1731 22.5 17.25C22.5 15.8822 21.2606 14.6709 19.0097 13.8403Z" fill="#FFFFFF" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg">Location</span>
                            </div>
                            <span class="bg-green-100 text-green-500 text-xs font-semibold px-3 py-1 rounded-full">Active</span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="font-semibold text-gray-800 text-base mb-0.5">Barangay Luz, Cebu City</div>
                            <div class="text-gray-400 text-sm mb-3">Near Luz Elementary School</div>
                            <div class="rounded-lg overflow-hidden border border-gray-200 mb-4" style="min-height:110px;max-height:160px;">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.2468265508577!2d123.88340332346936!3d10.315665574621914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a9993d16c5932d%3A0x9ad8b888ffcd2aa7!2sBarangay%20Luz%2C%20Cebu%20City!5e0!3m2!1sen!2sph!4v1677840000000"
                                    width="100%" height="120" style="border:0; min-width:100%; min-height:110px; max-height:160px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                            <div class="flex gap-2 mt-auto">
                                <a href="https://www.google.com/maps/place/Luz+Barangay+Hall/@10.3234786,123.9051946,17z/data=!4m14!1m7!3m6!1s0x33a9993d16c5932d:0x9ad8b888ffcd2aa7!2sLuz+Barangay+Hall!8m2!3d10.3237636!4d123.9061173!16s%2Fg%2F11bw7r3h1m!3m5!1s0x33a9993d16c5932d:0x9ad8b888ffcd2aa7!8m2!3d10.3237636!4d123.9061173!16s%2Fg%2F11bw7r3h1m?entry=ttu&g_ep=EgoyMDI2MDIyNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-sm font-medium px-4 py-1.5 rounded border transition">View Location</a>
                            </div>
                        </div>
                    </div>
                    <!-- Redesigned Availability Card -->
                    <!-- Availability Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#4A90E2] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM12 20.25C10.3683 20.25 8.77326 19.7661 7.41655 18.8596C6.05984 17.9531 5.00242 16.6646 4.378 15.1571C3.75358 13.6496 3.5902 11.9908 3.90853 10.3905C4.22685 8.79016 5.01259 7.32015 6.16637 6.16637C7.32016 5.01259 8.79017 4.22685 10.3905 3.90852C11.9909 3.59019 13.6497 3.75357 15.1571 4.37799C16.6646 5.00242 17.9531 6.05984 18.8596 7.41655C19.7662 8.77325 20.25 10.3683 20.25 12C20.2475 14.1873 19.3775 16.2843 17.8309 17.8309C16.2843 19.3775 14.1873 20.2475 12 20.25ZM18 12C18 12.1989 17.921 12.3897 17.7803 12.5303C17.6397 12.671 17.4489 12.75 17.25 12.75H12C11.8011 12.75 11.6103 12.671 11.4697 12.5303C11.329 12.3897 11.25 12.1989 11.25 12V6.75C11.25 6.55109 11.329 6.36032 11.4697 6.21967C11.6103 6.07902 11.8011 6 12 6C12.1989 6 12.3897 6.07902 12.5303 6.21967C12.671 6.36032 12.75 6.55109 12.75 6.75V11.25H17.25C17.4489 11.25 17.6397 11.329 17.7803 11.4697C17.921 11.6103 18 11.8011 18 12Z" fill="white" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg">Availability</span>
                            </div>
                            <span class="<?php echo $badgeClass; ?> text-xs font-semibold px-3 py-1 rounded-full"><?php echo $badgeStatus; ?></span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-map-marker-alt text-[#4A90E2] text-base"></i>
                                <span class="font-semibold text-gray-800 text-base">Barangay Luz, Cebu City</span>
                            </div>
                            <div class="text-gray-400 text-sm mb-1">Office Hours :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">Monday–Friday, 8:00 AM – 5:00 PM</div>
                            <div class="text-gray-400 text-sm mb-1">Emergency Contact :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">4357-344-45</div>
                            <div class="text-gray-400 text-sm mb-1">Contact Person :</div>
                            <div class="text-gray-700 text-base font-medium mb-4">Maria Santos</div>
                            <div class="flex gap-2 mt-auto">
                                <a href="#" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-sm font-medium px-4 py-1.5 rounded border transition">Preview</a>

                            </div>
                        </div>
                    </div>
                    <!-- Redesigned Contact Card -->
                    <!-- Contact Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#4A90E2] px-6 py-4 flex items-center justify-between">
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
                                <span class="text-white font-md text-lg">Contact</span>
                            </div>
                            <span class="bg-green-100 text-green-500 text-xs font-semibold px-3 py-1 rounded-full">Active</span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-map-marker-alt text-[#4A90E2] text-base"></i>
                                <span class="font-semibold text-gray-800 text-base">Barangay Luz, Cebu City</span>
                            </div>
                            <div class="text-gray-400 text-sm mb-1">Landline :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">(032) 123-4567</div>
                            <div class="text-gray-400 text-sm mb-1">Mobile Number :</div>
                            <div class="text-gray-700 text-base font-medium mb-1">0917-123-4567</div>
                            <div class="text-gray-400 text-sm mb-1">Official Email Address :</div>
                            <div class="text-gray-700 text-base font-medium mb-4">healthcenter@barangayluz.gov.ph</div>
                            <div class="flex gap-2 mt-auto">
                                <a href="#" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-sm font-medium px-4 py-1.5 rounded border transition">Preview</a>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </section>

        <!-- SECTION 2: Health Services -->
        <section id="services" class="services-bg section-padding-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="section-title01 text-2xl md:text-4xl font-md text-gray-900">
                        Our Health Services
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto text-lg text-lead">
                        Comprehensive healthcare services designed to meet the diverse needs of our community members
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 grid-spacing">
                    <!-- Service 1 -->
                    <div class="info-card text-center card-hover">
                        <div class="service-icon mx-auto">
                            <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.4991 7.5C17.0924 7.5 17.6724 7.32405 18.1658 6.99441C18.6591 6.66477 19.0436 6.19623 19.2707 5.64805C19.4978 5.09987 19.5572 4.49667 19.4414 3.91473C19.3257 3.33279 19.0399 2.79824 18.6204 2.37868C18.2008 1.95912 17.6663 1.6734 17.0843 1.55765C16.5024 1.44189 15.8992 1.5013 15.351 1.72836C14.8028 1.95543 14.3343 2.33994 14.0046 2.83329C13.675 3.32664 13.4991 3.90666 13.4991 4.5C13.4991 5.29565 13.8151 6.05871 14.3777 6.62132C14.9403 7.18393 15.7034 7.5 16.4991 7.5ZM16.4991 3C16.7957 3 17.0857 3.08797 17.3324 3.2528C17.5791 3.41762 17.7713 3.65189 17.8849 3.92598C17.9984 4.20007 18.0281 4.50167 17.9702 4.79264C17.9124 5.08361 17.7695 5.35088 17.5597 5.56066C17.3499 5.77044 17.0827 5.9133 16.7917 5.97118C16.5007 6.02906 16.1991 5.99935 15.925 5.88582C15.6509 5.77229 15.4167 5.58003 15.2518 5.33336C15.087 5.08668 14.9991 4.79667 14.9991 4.5C14.9991 4.10218 15.1571 3.72065 15.4384 3.43934C15.7197 3.15804 16.1012 3 16.4991 3ZM15.7491 15.75C15.7491 16.9367 15.3972 18.0967 14.7379 19.0834C14.0786 20.0701 13.1415 20.8392 12.0452 21.2933C10.9488 21.7474 9.7424 21.8662 8.57851 21.6347C7.41462 21.4032 6.34553 20.8318 5.50641 19.9926C4.6673 19.1535 4.09585 18.0844 3.86434 16.9205C3.63283 15.7567 3.75165 14.5503 4.20577 13.4539C4.6599 12.3575 5.42894 11.4205 6.41563 10.7612C7.40233 10.1019 8.56236 9.75 9.74905 9.75C9.94796 9.75 10.1387 9.82902 10.2794 9.96967C10.42 10.1103 10.4991 10.3011 10.4991 10.5C10.4991 10.6989 10.42 10.8897 10.2794 11.0303C10.1387 11.171 9.94796 11.25 9.74905 11.25C8.85904 11.25 7.98901 11.5139 7.24899 12.0084C6.50896 12.5029 5.93219 13.2057 5.59159 14.0279C5.251 14.8502 5.16188 15.755 5.33552 16.6279C5.50915 17.5008 5.93774 18.3026 6.56707 18.932C7.19641 19.5613 7.99823 19.9899 8.87115 20.1635C9.74406 20.3372 10.6489 20.2481 11.4711 19.9075C12.2934 19.5669 12.9962 18.9901 13.4907 18.2501C13.9851 17.51 14.2491 16.64 14.2491 15.75C14.2491 15.5511 14.3281 15.3603 14.4687 15.2197C14.6094 15.079 14.8001 15 14.9991 15C15.198 15 15.3887 15.079 15.5294 15.2197C15.67 15.3603 15.7491 15.5511 15.7491 15.75ZM19.3294 12.2747C19.3996 12.3606 19.4499 12.4609 19.4766 12.5686C19.5034 12.6762 19.5059 12.7884 19.4841 12.8972L17.9841 20.3972C17.95 20.5671 17.8582 20.72 17.7242 20.8299C17.5903 20.9398 17.4223 20.9999 17.2491 21C17.1993 21.0001 17.1497 20.995 17.1009 20.985C16.906 20.9459 16.7347 20.831 16.6245 20.6656C16.5142 20.5001 16.4742 20.2977 16.5131 20.1028L17.8341 13.5H11.9991C11.8673 13.5002 11.7378 13.4657 11.6237 13.3999C11.5095 13.3342 11.4147 13.2395 11.3487 13.1255C11.2827 13.0114 11.248 12.882 11.2479 12.7503C11.2479 12.6185 11.2826 12.4891 11.3484 12.375L13.2291 9.10313C11.9215 8.42015 10.4418 8.13746 8.97457 8.29035C7.50735 8.44324 6.11768 9.02494 4.97905 9.96281C4.90471 10.0338 4.81655 10.0888 4.72006 10.1244C4.62357 10.1599 4.52081 10.1752 4.41815 10.1693C4.3155 10.1635 4.21514 10.1365 4.12333 10.0903C4.03151 10.044 3.95019 9.9793 3.88442 9.90027C3.81866 9.82123 3.76984 9.72951 3.74102 9.63081C3.71219 9.53211 3.70396 9.42854 3.71685 9.32653C3.72974 9.22452 3.76347 9.12624 3.81594 9.03782C3.86841 8.94939 3.9385 8.8727 4.02186 8.8125C5.49984 7.59288 7.32852 6.87682 9.24168 6.76857C11.1548 6.66031 13.0526 7.16552 14.6587 8.21063C14.8194 8.31524 14.9338 8.47739 14.9786 8.66377C15.0235 8.85015 14.9952 9.04662 14.8997 9.21281L13.2947 12H18.7491C18.8601 12 18.9697 12.0246 19.0701 12.0721C19.1704 12.1196 19.259 12.1888 19.3294 12.2747Z" fill="#3879D3" />
                            </svg>


                        </div>
                        <h4 class="text-xl font-sm text-gray-900 mb-3">Primary Care Consultation</h4>
                        <p class="text-gray-200 text-lead">
                            Comprehensive medical check-ups, diagnosis, and treatment for common illnesses.
                        </p>
                    </div>

                    <!-- Service 2 -->
                    <div class="info-card text-center card-hover">
                        <div class="service-icon mx-auto">
                            <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.281 6.21979L17.781 1.71979C17.7114 1.65011 17.6286 1.59483 17.5376 1.55712C17.4465 1.51941 17.349 1.5 17.2504 1.5C17.1519 1.5 17.0543 1.51941 16.9632 1.55712C16.8722 1.59483 16.7895 1.65011 16.7198 1.71979C16.6501 1.78947 16.5948 1.8722 16.5571 1.96324C16.5194 2.05429 16.5 2.15187 16.5 2.25042C16.5 2.34896 16.5194 2.44654 16.5571 2.53759C16.5948 2.62863 16.6501 2.71136 16.7198 2.78104L18.4401 4.50042L15.7504 7.1901L12.531 3.96979C12.3903 3.82906 12.1994 3.75 12.0004 3.75C11.8014 3.75 11.6105 3.82906 11.4698 3.96979C11.3291 4.11052 11.25 4.30139 11.25 4.50042C11.25 4.69944 11.3291 4.89031 11.4698 5.03104L12.0651 5.62542L4.1901 13.5004C4.05021 13.6392 3.9393 13.8044 3.86382 13.9864C3.78833 14.1685 3.74979 14.3637 3.75042 14.5607V19.1901L1.71979 21.2198C1.65011 21.2895 1.59483 21.3722 1.55712 21.4632C1.51941 21.5543 1.5 21.6519 1.5 21.7504C1.5 21.849 1.51941 21.9465 1.55712 22.0376C1.59483 22.1286 1.65011 22.2114 1.71979 22.281C1.86052 22.4218 2.05139 22.5008 2.25042 22.5008C2.34896 22.5008 2.44654 22.4814 2.53759 22.4437C2.62863 22.406 2.71136 22.3507 2.78104 22.281L4.81073 20.2504H9.4401C9.63716 20.251 9.83237 20.2125 10.0144 20.137C10.1964 20.0615 10.3616 19.9506 10.5004 19.8107L18.3754 11.9357L18.9698 12.531C19.0395 12.6007 19.1222 12.656 19.2132 12.6937C19.3043 12.7314 19.4019 12.7508 19.5004 12.7508C19.599 12.7508 19.6965 12.7314 19.7876 12.6937C19.8786 12.656 19.9614 12.6007 20.031 12.531C20.1007 12.4614 20.156 12.3786 20.1937 12.2876C20.2314 12.1965 20.2508 12.099 20.2508 12.0004C20.2508 11.9019 20.2314 11.8043 20.1937 11.7132C20.156 11.6222 20.1007 11.5395 20.031 11.4698L16.8107 8.25042L19.5004 5.56073L21.2198 7.28104C21.3605 7.42177 21.5514 7.50083 21.7504 7.50083C21.9494 7.50083 22.1403 7.42177 22.281 7.28104C22.4218 7.14031 22.5008 6.94944 22.5008 6.75042C22.5008 6.55139 22.4218 6.36052 22.281 6.21979ZM9.4401 18.7504H5.25042V14.5607L6.93792 12.8732L8.84479 14.781C8.91447 14.8507 8.9972 14.906 9.08824 14.9437C9.17929 14.9814 9.27687 15.0008 9.37542 15.0008C9.47396 15.0008 9.57154 14.9814 9.66259 14.9437C9.75363 14.906 9.83636 14.8507 9.90604 14.781C9.97573 14.7114 10.031 14.6286 10.0687 14.5376C10.1064 14.4465 10.1258 14.349 10.1258 14.2504C10.1258 14.1519 10.1064 14.0543 10.0687 13.9632C10.031 13.8722 9.97573 13.7895 9.90604 13.7198L7.99823 11.8129L9.18792 10.6232L11.0948 12.531C11.2355 12.6718 11.4264 12.7508 11.6254 12.7508C11.8244 12.7508 12.0153 12.6718 12.156 12.531C12.2968 12.3903 12.3758 12.1994 12.3758 12.0004C12.3758 11.8014 12.2968 11.6105 12.156 11.4698L10.2482 9.56292L13.1254 6.68573L17.3151 10.8754L9.4401 18.7504Z" fill="#3879D3" />
                            </svg>

                        </div>
                        <h4 class="text-xl font-sm text-gray-900 mb-3">Immunization Program</h4>
                        <p class="text-gray-200 text-lead">
                            Complete vaccination schedule for children, adults, and senior citizens.
                        </p>
                    </div>

                    <!-- Service 3 -->
                    <div class="info-card text-center card-hover">
                        <div class="service-icon mx-auto">
                            <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20.625 15C20.625 15.2225 20.559 15.44 20.4354 15.625C20.3118 15.81 20.1361 15.9542 19.9305 16.0394C19.725 16.1245 19.4988 16.1468 19.2805 16.1034C19.0623 16.06 18.8618 15.9528 18.7045 15.7955C18.5472 15.6382 18.44 15.4377 18.3966 15.2195C18.3532 15.0012 18.3755 14.775 18.4606 14.5695C18.5458 14.3639 18.69 14.1882 18.875 14.0646C19.06 13.941 19.2775 13.875 19.5 13.875C19.7984 13.875 20.0845 13.9935 20.2955 14.2045C20.5065 14.4155 20.625 14.7016 20.625 15ZM20.1984 18.6834C20.0337 19.7455 19.4949 20.7137 18.6793 21.4135C17.8636 22.1133 16.8247 22.4986 15.75 22.5H13.5C12.3069 22.4988 11.163 22.0243 10.3194 21.1806C9.47575 20.337 9.00124 19.1931 9 18V14.2022C7.55018 14.0195 6.21686 13.3141 5.25025 12.2182C4.28364 11.1223 3.75018 9.71128 3.75 8.25V3.75C3.75 3.55109 3.82902 3.36032 3.96967 3.21967C4.11032 3.07902 4.30109 3 4.5 3H6.75C6.94891 3 7.13968 3.07902 7.28033 3.21967C7.42098 3.36032 7.5 3.55109 7.5 3.75C7.5 3.94891 7.42098 4.13968 7.28033 4.28033C7.13968 4.42098 6.94891 4.5 6.75 4.5H5.25V8.25C5.24995 8.84603 5.3683 9.43614 5.59819 9.98605C5.82808 10.536 6.16492 11.0347 6.58916 11.4534C7.0134 11.872 7.51658 12.2022 8.06949 12.4248C8.6224 12.6474 9.21402 12.7579 9.81 12.75C12.2578 12.7181 14.25 10.6641 14.25 8.17219V4.5H12.75C12.5511 4.5 12.3603 4.42098 12.2197 4.28033C12.079 4.13968 12 3.94891 12 3.75C12 3.55109 12.079 3.36032 12.2197 3.21967C12.3603 3.07902 12.5511 3 12.75 3H15C15.1989 3 15.3897 3.07902 15.5303 3.21967C15.671 3.36032 15.75 3.55109 15.75 3.75V8.17219C15.75 11.2509 13.4503 13.8244 10.5 14.2012V18C10.5 18.7956 10.8161 19.5587 11.3787 20.1213C11.9413 20.6839 12.7044 21 13.5 21H15.75C16.4313 20.9989 17.0919 20.7663 17.6237 20.3405C18.1555 19.9147 18.5269 19.3208 18.6769 18.6562C17.7711 18.4528 16.973 17.9206 16.437 17.1626C15.9009 16.4046 15.6651 15.4748 15.7752 14.553C15.8852 13.6312 16.3332 12.7829 17.0326 12.1724C17.7319 11.5619 18.6329 11.2325 19.5611 11.2479C20.4893 11.2634 21.3788 11.6226 22.0575 12.256C22.7362 12.8895 23.1557 13.7521 23.235 14.6771C23.3143 15.602 23.0477 16.5235 22.4868 17.2633C21.9259 18.003 21.1105 18.5083 20.1984 18.6816V18.6834ZM21.75 15C21.75 14.555 21.618 14.12 21.3708 13.75C21.1236 13.38 20.7722 13.0916 20.361 12.9213C19.9499 12.751 19.4975 12.7064 19.061 12.7932C18.6246 12.88 18.2237 13.0943 17.909 13.409C17.5943 13.7237 17.38 14.1246 17.2932 14.561C17.2064 14.9975 17.251 15.4499 17.4213 15.861C17.5916 16.2722 17.88 16.6236 18.25 16.8708C18.62 17.118 19.055 17.25 19.5 17.25C20.0967 17.25 20.669 17.0129 21.091 16.591C21.5129 16.169 21.75 15.5967 21.75 15Z" fill="#3879D3" />
                            </svg>

                        </div>
                        <h4 class="text-xl font-sm text-gray-900 mb-3">Emergency Services</h4>
                        <p class="text-gray-200 text-lead">
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
                    <h2 class="section-title02 text-3xl md:text-4xl font-md text-white">
                        Latest Announcements
                    </h2>
                    <p class="text-white max-w-3xl mx-auto text-lg">
                        Stay informed with important updates, health advisories, and community events
                    </p>
                </div>

                <?php if (empty($announcements)): ?>
                    <div class="bg-white rounded-2xl p-16 text-center border border-gray-200 backdrop-blur-md">
                        <div class="mb-6 flex justify-center">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21.4256 8.1225L4.92 3.06C4.6966 2.99484 4.4611 2.98255 4.23213 3.02411C4.00316 3.06567 3.787 3.15993 3.60075 3.29944C3.41449 3.43895 3.26325 3.61988 3.15899 3.82792C3.05472 4.03597 3.00029 4.26542 3 4.49813V17.9981C3 18.396 3.15804 18.7775 3.43934 19.0588C3.72064 19.3401 4.10218 19.4981 4.5 19.4981C4.64344 19.4982 4.78614 19.4777 4.92375 19.4372L12.75 17.0353V17.9981C12.75 18.396 12.908 18.7775 13.1893 19.0588C13.4706 19.3401 13.8522 19.4981 14.25 19.4981H17.25C17.6478 19.4981 18.0294 19.3401 18.3107 19.0588C18.592 18.7775 18.75 18.396 18.75 17.9981V15.195L21.4256 14.3747C21.7353 14.2816 22.0069 14.0916 22.2003 13.8325C22.3937 13.5734 22.4988 13.259 22.5 12.9356V9.56063C22.4986 9.23745 22.3934 8.92326 22.2 8.66435C22.0066 8.40544 21.7351 8.2155 21.4256 8.1225ZM12.75 15.4669L4.5 17.9981V4.49813L12.75 7.02938V15.4669ZM17.25 17.9981H14.25V16.575L17.25 15.6544V17.9981ZM21 12.9356H20.9897L14.25 15.0056V7.49063L20.9897 9.55313H21V12.9281V12.9356Z" fill="#9C9C9C" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-md text-gray-500 mb-3">No Announcements Yet</h3>
                        <p class="text-gray-400 max-w-md mx-auto text-md">
                            Check back soon for important health updates and community announcements.
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
                                                    High Priority
                                                </span>
                                            <?php elseif ($announcement['priority'] == 'medium'): ?>
                                                <span class="inline-block px-4 py-2 rounded text-sm font-semibold bg-yellow-100 text-yellow-700">
                                                    Medium Priority
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-block px-4 py-2 rounded text-sm font-semibold bg-blue-100 text-blue-700">
                                                    Announcement
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-400 font-medium">Date Posted :</div>
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
                                        Read Announcement
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center">
                        <button onclick="openAnnouncementsModal()"
                            class="btn-primary bg-white text-[#3a7bd5] hover:bg-blue-50">
                            View All Announcements
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SECTION 4: Testimonials -->
        <section id="about" class="about-bg section-padding">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="section-title text-3xl md:text-4xl font-md text-gray-900">
                        What Our Community Says
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto text-lg text-lead">
                        Hear from our dedicated healthcare providers and community members
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 grid-spacing">
                    <!-- Testimonial 1 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full  flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.2897 3.7124C19.3533 2.77604 18.0833 2.25 16.7591 2.25C15.4348 2.25 14.1648 2.77604 13.2284 3.7124L3.71281 13.2271C2.79602 14.1675 2.28664 15.4312 2.29502 16.7445C2.30341 18.0578 2.82888 19.3149 3.75761 20.2435C4.68634 21.1721 5.94352 21.6974 7.25683 21.7057C8.57013 21.7139 9.83379 21.2043 10.7741 20.2874L20.2906 10.7727C21.2254 9.83563 21.7503 8.56599 21.7502 7.24237C21.75 5.91874 21.2247 4.64924 20.2897 3.7124ZM9.71375 19.2271C9.0587 19.8823 8.17022 20.2504 7.24376 20.2505C6.31731 20.2506 5.42876 19.8826 4.77359 19.2276C4.11842 18.5725 3.7503 17.684 3.75022 16.7576C3.75013 15.8311 4.11808 14.9426 4.77312 14.2874L9.00031 10.0602L13.9409 14.9999L9.71375 19.2271ZM19.2294 9.7124L15.0003 13.9396L10.0616 8.9999L14.2897 4.77272C14.9473 4.13027 15.8317 3.77299 16.7511 3.77836C17.6704 3.78373 18.5506 4.15132 19.2007 4.80141C19.8508 5.4515 20.2184 6.33166 20.2237 7.25101C20.2291 8.17036 19.8718 9.05476 19.2294 9.7124ZM17.7828 7.71928C17.8525 7.78893 17.9079 7.87165 17.9456 7.9627C17.9833 8.05375 18.0028 8.15134 18.0028 8.2499C18.0028 8.34847 17.9833 8.44606 17.9456 8.53711C17.9079 8.62816 17.8525 8.71087 17.7828 8.78053L15.5328 11.0305C15.4631 11.1002 15.3804 11.1554 15.2894 11.193C15.1984 11.2307 15.1008 11.25 15.0023 11.25C14.9038 11.2499 14.8063 11.2305 14.7153 11.1928C14.6243 11.155 14.5416 11.0997 14.472 11.0301C14.4024 10.9604 14.3472 10.8777 14.3095 10.7866C14.2719 10.6956 14.2525 10.5981 14.2526 10.4996C14.2526 10.4011 14.2721 10.3035 14.3098 10.2126C14.3475 10.1216 14.4028 10.0389 14.4725 9.96928L16.7225 7.71928C16.8631 7.57873 17.0538 7.49978 17.2527 7.49978C17.4515 7.49978 17.6422 7.57873 17.7828 7.71928Z" fill="#3B82F6" />
                                </svg>


                            </div>
                            <div>
                                <h4 class="text-lg font-medium text-gray-900">Dr. Maria Santos</h4>
                                <p class="text-gray-600 text-sm">Barangay Health Officer</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead">
                            "Our health center is committed to providing accessible and quality healthcare to every resident."
                        </p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full  flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.25 3H6.75C6.35218 3 5.97064 3.15804 5.68934 3.43934C5.40804 3.72064 5.25 4.10218 5.25 4.5V21C5.25007 21.1338 5.28595 21.2652 5.35393 21.3805C5.42191 21.4958 5.5195 21.5908 5.63659 21.6557C5.75367 21.7206 5.88598 21.7529 6.01978 21.7494C6.15358 21.7458 6.284 21.7066 6.3975 21.6356L12 18.1341L17.6034 21.6356C17.7169 21.7063 17.8472 21.7454 17.9809 21.7488C18.1146 21.7522 18.2467 21.7198 18.3636 21.655C18.4806 21.5902 18.5781 21.4953 18.646 21.3801C18.7139 21.2649 18.7498 21.1337 18.75 21V4.5C18.75 4.10218 18.592 3.72064 18.3107 3.43934C18.0294 3.15804 17.6478 3 17.25 3ZM17.25 4.5V15.1472L12.3966 12.1144C12.2774 12.0399 12.1396 12.0004 11.9991 12.0004C11.8585 12.0004 11.7208 12.0399 11.6016 12.1144L6.75 15.1462V4.5H17.25ZM12.3966 16.6144C12.2774 16.5399 12.1396 16.5004 11.9991 16.5004C11.8585 16.5004 11.7208 16.5399 11.6016 16.6144L6.75 19.6472V16.9153L12 13.6341L17.25 16.9153V19.6472L12.3966 16.6144Z" fill="#3B82F6" />
                                </svg>

                            </div>
                            <div>
                                <h4 class="text-lg font-medium text-gray-900">Capt. Juan Dela Cruz</h4>
                                <p class="text-gray-600 text-sm">Barangay Captain</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead">
                            "The health monitoring system has transformed how we manage community health needs."
                        </p>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="info-card card-hover">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22.4893 18.5737L21.1525 7.32375C21.1091 6.95721 20.9321 6.61952 20.6554 6.37529C20.3786 6.13106 20.0215 5.99744 19.6525 6H16.4996C16.4996 4.80653 16.0255 3.66193 15.1816 2.81802C14.3377 1.97411 13.1931 1.5 11.9996 1.5C10.8062 1.5 9.66157 1.97411 8.81766 2.81802C7.97374 3.66193 7.49964 4.80653 7.49964 6H4.34308C3.97399 5.99744 3.61691 6.13106 3.34016 6.37529C3.06342 6.61952 2.88644 6.95721 2.84308 7.32375L1.5062 18.5737C1.4817 18.7837 1.50186 18.9965 1.56536 19.1981C1.62885 19.3998 1.73425 19.5857 1.87464 19.7438C2.01604 19.9025 2.18932 20.0296 2.38317 20.1168C2.57701 20.204 2.78707 20.2494 2.99964 20.25H20.9921C21.206 20.2505 21.4175 20.2056 21.6127 20.1183C21.8079 20.0311 21.9824 19.9034 22.1246 19.7438C22.2644 19.5854 22.3691 19.3993 22.4319 19.1977C22.4948 18.9961 22.5143 18.7835 22.4893 18.5737ZM11.9996 3C12.7953 3 13.5583 3.31607 14.121 3.87868C14.6836 4.44129 14.9996 5.20435 14.9996 6H8.99964C8.99964 5.20435 9.31571 4.44129 9.87832 3.87868C10.4409 3.31607 11.204 3 11.9996 3ZM2.99964 18.75L4.34308 7.5H7.49964V9.75C7.49964 9.94891 7.57866 10.1397 7.71931 10.2803C7.85996 10.421 8.05073 10.5 8.24964 10.5C8.44855 10.5 8.63932 10.421 8.77997 10.2803C8.92062 10.1397 8.99964 9.94891 8.99964 9.75V7.5H14.9996V9.75C14.9996 9.94891 15.0787 10.1397 15.2193 10.2803C15.36 10.421 15.5507 10.5 15.7496 10.5C15.9486 10.5 16.1393 10.421 16.28 10.2803C16.4206 10.1397 16.4996 9.94891 16.4996 9.75V7.5H19.6637L20.9921 18.75H2.99964Z" fill="#3B82F6" />
                                </svg>

                            </div>
                            <div>
                                <h4 class="text-lg font-medium text-gray-900">Nurse Lisa Mendoza</h4>
                                <p class="text-gray-600 text-sm">Head Nurse</p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-lead">
                            "The digital health records system has made our work more efficient and focused."
                        </p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Footer -->
        <footer class="warm-blue-bg text-white" id="footer">
            <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                    <!-- Column 1: About -->
                    <div>
                        <h3 class="text-xl font-md mb-6">Barangay Luz Health Center</h3>
                        <p class="text-white mb-6">
                            Providing quality healthcare services to Barangay Luz residents with compassion and excellence.
                        </p>
                        <div class="flex space-x-4">
                            <a href="https://www.facebook.com/BarangayLuzCebuCity2023" target="_blank"
                                class="bg-white/10 p-3 rounded-lg hover:bg-white/20 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="bg-white/10 p-3 rounded-lg hover:bg-white/20 transition">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2: Quick Links -->
                    <div>
                        <h3 class="text-xl font-medium mb-6">Quick Links</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="#home" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> Home
                                </a>
                            </li>
                            <li>
                                <a href="#services" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> Our Services
                                </a>
                            </li>
                            <li>
                                <a href="#announcementsSection" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> Announcements
                                </a>
                            </li>
                            <li>
                                <a href="#about" class="text-blue-100 hover:text-white transition flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-2"></i> About Us
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Contact Info -->
                    <div>
                        <h3 class="text-xl font-medium mb-6">Contact Info</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-200"></i>
                                <span class="text-blue-100">Barangay Luz, Cebu City</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-phone mr-3 text-blue-200"></i>
                                <span class="text-blue-100">(032) 123-4567</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-3 text-blue-200"></i>
                                <span class="text-blue-100">barangayluz.gov.ph</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Hours -->
                    <div>
                        <h3 class="text-xl font-medium mb-6">Operating Hours</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-blue-100">Monday - Friday</span>
                                <span class="text-white">8:00 AM - 5:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-100">Saturday</span>
                                <span class="text-white">8:00 AM - 12:00 PM</span>
                            </div>
                            <div class="pt-3 mt-3 border-t border-white/20">
                                <span class="text-blue-200 text-sm">Emergency services available 24/7</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-blue-400 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-blue-200">
                            &copy; <?= date('Y') ?> Barangay Luz Health Center. All rights reserved.
                        </p>
                    </div>
                    <div class="flex items-center space-x-6">
                        <a href="/privacy.php" class="text-blue-200 hover:text-white text-sm">Privacy Policy</a>
                        <a href="/terms.php" class="text-blue-200 hover:text-white text-sm">Terms of Service</a>
                        <span class="text-blue-200 text-sm">Version 1.0</span>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 hidden z-50 h-full w-full backdrop-blur-sm bg-black/30 flex justify-center items-center">
        <div class="relative bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-md mx-auto max-h-[90vh] overflow-y-auto modal-content login-modal-content">
            <!-- Close Button -->
            <button onclick="closeLoginModal()"
                class="modal-close-btn absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Logo at the top -->
            <div class="flex justify-center mb-6 mx-4">
                <img src="./asssets/images/Luz.jpg" alt="Barangay Luz Logo"
                    class="w-20 h-20 rounded-full object-cover border-4 border-[#3C96E1] shadow-lg">
            </div>

            <!-- Main Title -->
            <div class="text-center mb-4 mx-4">
                <h1 class="text-2xl font-bold text-[#4A90E2]">Barangay Luz Cebu City</h1>
            </div>

            <!-- Instruction Text -->
            <div class="flex flex-col items-center mb-8 mx-4">
                <p class="text-sm text-center text-gray-600 max-w-md leading-relaxed text-lead">
                    Please log in with your authorized account to access health records and other health services.
                </p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="/includes/auth/login.php" class="space-y-6">
                <input type="hidden" name="role" value="user">
                <div class="space-y-6 mx-4">
                    <!-- Username -->
                    <div>
                        <label for="login-username" class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="login-username" placeholder="Enter Username"
                            class="form-input w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3C96E1]" required />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="login-password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input id="login-password" name="password" type="password" placeholder="Password"
                                class="form-input w-full p-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3C96E1]" required />
                            <button type="button" onclick="toggleLoginPassword()"
                                class="absolute top-1/2 right-3 transform -translate-y-1/2 text-gray-500">
                                <i id="login-eyeIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <div class="mt-8">
                        <button type="submit"
                            class="complete-btn bg-[#3C96E1] w-full p-3 text-white transition-all duration-200 font-medium shadow-md hover:shadow-lg text-lg h-14">
                            Login
                        </button>
                    </div>

                    <!-- Registration Notice -->
                    <div class="text-center text-sm text-gray-600 mt-6">
                        <p class="text-lead">New residents need to register at the Barangay Health Center to obtain login credentials.</p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Other Modals -->
    <div id="learnMoreModal" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm flex items-center justify-center px-4">
        <div class="relative w-full max-w-7xl max-h-[75vh] bg-white rounded-3xl shadow-2xl flex flex-col overflow-y-auto z-[1050] modal-content" style="z-index:1051; transition: opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.95);">
            <div class="sticky top-0 z-20 bg-white border-b border-blue-100 px-10 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold warm-blue-text">
                        Community Health Essentials
                    </h2>
                    <p class="text-base text-gray-500 mt-1">
                        A complete guide to wellness, prevention, and safety
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
                            Daily Health Tips
                        </h3>
                        <ul class="space-y-4 text-gray-700 text-lg leading-relaxed text-lead">
                            <li>• Get 7–9 hours of quality sleep</li>
                            <li>• Drink at least 8 glasses of water</li>
                            <li>• Exercise for 30 minutes daily</li>
                            <li>• Eat fruits and vegetables daily</li>
                            <li>• Practice mindfulness or meditation</li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-8">
                        <h3 class="flex items-center gap-4 text-2xl font-semibold warm-blue-text mb-6">
                            <span class="bg-blue-100 px-4 py-2 rounded-xl">🩺</span>
                            Preventive Care
                        </h3>
                        <ul class="space-y-4 text-gray-700 text-lg text-lead">
                            <li>• Annual physical checkups</li>
                            <li>• Updated vaccinations</li>
                            <li>• Age-appropriate screenings</li>
                            <li>• Chronic condition monitoring</li>
                            <li>• Dental exams twice a year</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Announcement Modal -->
    <div id="singleAnnouncementModal" class="fixed inset-0 hidden z-50 bg-black/30">
        <div class="absolute inset-0 flex items-center justify-center p-4 z-[1050]">
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

    <div id="announcementsModal" class="fixed inset-0 hidden z-50 bg-black/30">
        <div class="absolute inset-0 flex items-center justify-center p-4 z-[1050]">
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[75vh] flex flex-col overflow-y-auto modal-content" style="z-index:1051; transition: opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.95);">
                <button onclick="closeAnnouncementsModal()"
                    class="absolute top-4 right-4 z-50 text-gray-500 hover:text-gray-700 bg-white rounded-full p-2 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-y-auto flex-1 p-8">
                    <div class="sticky top-0 z-20 bg-white pb-4 mb-4">
                        <div class="text-center">
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <svg width="35" height="35" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.4256 8.1225L4.92 3.06C4.6966 2.99484 4.4611 2.98255 4.23213 3.02411C4.00316 3.06567 3.787 3.15993 3.60075 3.29944C3.41449 3.43895 3.26325 3.61988 3.15899 3.82792C3.05472 4.03597 3.00029 4.26542 3 4.49813V17.9981C3 18.396 3.15804 18.7775 3.43934 19.0588C3.72064 19.3401 4.10218 19.4981 4.5 19.4981C4.64344 19.4982 4.78614 19.4777 4.92375 19.4372L12.75 17.0353V17.9981C12.75 18.396 12.908 18.7775 13.1893 19.0588C13.4706 19.3401 13.8522 19.4981 14.25 19.4981H17.25C17.6478 19.4981 18.0294 19.3401 18.3107 19.0588C18.592 18.7775 18.75 18.396 18.75 17.9981V15.195L21.4256 14.3747C21.7353 14.2816 22.0069 14.0916 22.2003 13.8325C22.3937 13.5734 22.4988 13.259 22.5 12.9356V9.56063C22.4986 9.23745 22.3934 8.92326 22.2 8.66435C22.0066 8.40544 21.7351 8.2155 21.4256 8.1225ZM12.75 15.4669L4.5 17.9981V4.49813L12.75 7.02938V15.4669ZM17.25 17.9981H14.25V16.575L17.25 15.6544V17.9981ZM21 12.9356H20.9897L14.25 15.0056V7.49063L20.9897 9.55313H21V12.9281V12.9356Z" fill="#0EA4EE" />
                                    </svg>

                                </div>
                                <h2 class="text-2xl font-semibold text-gray-700">All Announcements</h2>
                            </div>
                            <p class="text-gray-400 max-w-1xl mx-auto text-lead">
                                Stay updated with all important announcements from BO. Luz Health Center
                            </p>
                        </div>
                    </div>

                    <?php if (empty($announcements)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-bullhorn text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No announcements available at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($announcements as $index => $announcement): ?>
                                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow card-hover">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <?php if ($announcement['priority'] == 'high'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> High Priority
                                                </span>
                                            <?php elseif ($announcement['priority'] == 'medium'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> Medium Priority
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                    <i class="fas fa-info-circle mr-1"></i> Announcement
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                        </div>
                                    </div>

                                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                                        <?= htmlspecialchars($announcement['title']) ?>
                                    </h3>

                                    <?php if ($announcement['image_path']): ?>
                                        <div class="mb-4 rounded-lg overflow-hidden">
                                            <img src="<?= htmlspecialchars($announcement['image_path']) ?>"
                                                alt="<?= htmlspecialchars($announcement['title']) ?>"
                                                class="responsive-img h-64 object-cover">
                                        </div>
                                    <?php endif; ?>

                                    <div class="text-gray-700 whitespace-pre-line text-lead">
                                        <?= nl2br(htmlspecialchars($announcement['message'])) ?>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                <span>Posted: <?= date('F j, Y', strtotime($announcement['post_date'])) ?></span>
                                            </div>
                                            <?php if ($announcement['expiry_date']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-clock mr-2"></i>
                                                    <span>Valid until: <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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

        function openAnnouncementModal(index) {
            // Check if the announcement exists
            if (!announcementsData || !announcementsData[index]) {
                console.error('Announcement not found at index:', index);
                return;
            }

            const announcement = announcementsData[index];
            const contentDiv = document.getElementById('announcementContent');

            // Format date
            const postDate = new Date(announcement.post_date);
            const formattedDate = postDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Determine priority badge styling
            let badgeClass = 'bg-blue-100 text-blue-700';
            let badgeText = 'Announcement';
            if (announcement.priority === 'high') {
                badgeClass = 'bg-red-100 text-red-700';
                badgeText = 'High Priority';
            } else if (announcement.priority === 'medium') {
                badgeClass = 'bg-yellow-100 text-yellow-700';
                badgeText = 'Medium Priority';
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
                            <div class="text-sm text-gray-500 font-medium">Date Posted :</div>
                            <div class="text-lg font-semibold text-blue-600">
                                ${formattedDate}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h2 class="text-3xl font-medium text-gray-900 mb-6 leading-tight">${announcement.title}</h2>
                    
                    <!-- Image -->
                    ${announcement.image_path ? `
                        <div class="mb-8 rounded-lg overflow-hidden">
                            <img src="${announcement.image_path}" alt="${announcement.title}" class="w-full rounded-lg max-h-96 object-cover">
                        </div>
                    ` : ''}
                    
                    <!-- Message -->
                    <div class="text-gray-800 mb-6 text-lg leading-relaxed whitespace-pre-wrap break-words overflow-wrap-break-word">
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
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAnnouncementsModal();
                closeSingleAnnouncementModal();
                closeLearnMoreModal();
                closeLoginModal();
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