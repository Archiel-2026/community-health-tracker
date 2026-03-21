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
$badgeClass = $isWeekend ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600';
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
            
            // Show instruction modal automatically on every page refresh
            setTimeout(function() {
                openInstructionModal();
            }, 500);
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
                        <h1 class="text-3xl md:text-4xl font-semibold leading-tight mb-6 mt-8 md:mt-14">
                            Health Monitoring and Tracking
                        </h1>

                        <p class="text-base md:text-lg text-white mb-10">
                            Your trusted partner in community healthcare. <br>
                            Providing accessible, quality healthcare services. <br>
                            Providing accessible, quality healthcare services for <br>
                            every resident of Barangay Luz, Cebu City.
                        </p>

                        <button href="Learnmore.html" >
                            <a href="Learnmore.php" class="py-4 px-7 bg-[#3879D3] rounded-lg text-xl text-white font-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 w-max">
    Learn More
    <i class="fas fa-arrow-right"></i>
</a>
                        </button>
                    </div>

                    <!-- RIGHT -->
                    <div class="md:w-1/2 lg:w-2/5 xl:w-1/2 md:pr-14 lg:pr-20">
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 md:gap-6 justify-center mt-5">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Medical Staff</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $medicalStaffCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Residents Served</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1"><?= $residentsServedCount ?></div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
                                <div class="text-blue-200 text-xs md:text-base mb-4">Years Service</div>
                                <div class="text-2xl md:text-3xl font-bold mb-1">28</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 md:p-6 border border-white/20">
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
                        <div class="bg-[#3879D3] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.5 7.5C10.5 7.20333 10.588 6.91332 10.7528 6.66664C10.9176 6.41997 11.1519 6.22771 11.426 6.11418C11.7001 6.00065 12.0017 5.97094 12.2926 6.02882C12.5836 6.0867 12.8509 6.22956 13.0607 6.43934C13.2704 6.64912 13.4133 6.91639 13.4712 7.20736C13.5291 7.49834 13.4994 7.79994 13.3858 8.07402C13.2723 8.34811 13.08 8.58238 12.8334 8.7472C12.5867 8.91203 12.2967 9 12 9C11.6022 9 11.2206 8.84196 10.9393 8.56066C10.658 8.27936 10.5 7.89782 10.5 7.5ZM6 7.5C6 5.9087 6.63214 4.38258 7.75736 3.25736C8.88258 2.13214 10.4087 1.5 12 1.5C13.5913 1.5 15.1174 2.13214 16.2426 3.25736C17.3679 4.38258 18 5.9087 18 7.5C18 13.1203 12.6019 16.2694 12.375 16.4016C12.2617 16.4663 12.1334 16.5004 12.0028 16.5004C11.8723 16.5004 11.744 16.4663 11.6306 16.4016C11.3981 16.2694 6 13.125 6 7.5ZM7.5 7.5C7.5 11.4563 10.86 14.0822 12 14.8594C13.1391 14.0831 16.5 11.4563 16.5 7.5C16.5 6.30653 16.0259 5.16193 15.182 4.31802C14.3381 3.47411 13.1935 3 12 3C10.8065 3 9.66193 3.47411 8.81802 4.31802C7.97411 5.16193 7.5 6.30653 7.5 7.5ZM19.0097 13.8403C18.8251 13.7793 18.624 13.7924 18.4489 13.8768C18.2738 13.9612 18.1382 14.1102 18.0709 14.2926C18.0035 14.475 18.0096 14.6764 18.0879 14.8543C18.1661 15.0323 18.3104 15.1729 18.4903 15.2466C20.0381 15.8194 21 16.5863 21 17.25C21 18.5025 17.5762 20.25 12 20.25C6.42375 20.25 3 18.5025 3 17.25C3 16.5863 3.96188 15.8194 5.50969 15.2475C5.6896 15.1739 5.8339 15.0332 5.91215 14.8553C5.99039 14.6773 5.99648 14.4759 5.92913 14.2935C5.86178 14.1112 5.72624 13.9621 5.5511 13.8777C5.37596 13.7933 5.1749 13.7803 4.99031 13.8412C2.73937 14.6709 1.5 15.8822 1.5 17.25C1.5 20.1731 6.91031 21.75 12 21.75C17.0897 21.75 22.5 20.1731 22.5 17.25C22.5 15.8822 21.2606 14.6709 19.0097 13.8403Z" fill="#FFFFFF" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg">Location</span>
                            </div>
                            <span class="bg-green-100 text-green-600 text-xs font-semibold px-3 py-1 rounded-md">Active</span>
                        </div>
                        <div class="px-6 pt-4 pb-6 flex-1 flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-map-marker-alt text-[#4A90E2] text-base"></i>
                                <span class="font-semibold text-gray-800 text-base">Barangay Luz, Cebu City</span>
                            </div>
                            <div class="text-gray-400 text-sm mb-3">Near Luz Elementary School</div>
                            <div class="rounded-lg overflow-hidden border border-gray-200 mb-4" style="min-height:110px;max-height:160px;">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.2468265508577!2d123.88340332346936!3d10.315665574621914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a9993d16c5932d%3A0x9ad8b888ffcd2aa7!2sBarangay%20Luz%2C%20Cebu%20City!5e0!3m2!1sen!2sph!4v1677840000000"
                                    width="100%" height="120" style="border:0; min-width:100%; min-height:110px; max-height:160px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                            <div class="flex gap-2 mt-auto">
                                <a href="https://www.google.com/maps/place/Luz+Barangay+Hall/@10.3234786,123.9051946,17z/data=!4m14!1m7!3m6!1s0x33a9993d16c5932d:0x9ad8b888ffcd2aa7!2sLuz+Barangay+Hall!8m2!3d10.3237636!4d123.9061173!16s%2Fg%2F11bw7r3h1m!3m5!1s0x33a9993d16c5932d:0x9ad8b888ffcd2aa7!8m2!3d10.3237636!4d123.9061173!16s%2Fg%2F11bw7r3h1m?entry=ttu&g_ep=EgoyMDI2MDIyNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-md font-medium px-4 py-1.5 rounded-md border transition">Google Maps</a>
                            </div>
                        </div>
                    </div>
                    <!-- Redesigned Availability Card -->
                    <!-- Availability Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#3879D3] px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM12 20.25C10.3683 20.25 8.77326 19.7661 7.41655 18.8596C6.05984 17.9531 5.00242 16.6646 4.378 15.1571C3.75358 13.6496 3.5902 11.9908 3.90853 10.3905C4.22685 8.79016 5.01259 7.32015 6.16637 6.16637C7.32016 5.01259 8.79017 4.22685 10.3905 3.90852C11.9909 3.59019 13.6497 3.75357 15.1571 4.37799C16.6646 5.00242 17.9531 6.05984 18.8596 7.41655C19.7662 8.77325 20.25 10.3683 20.25 12C20.2475 14.1873 19.3775 16.2843 17.8309 17.8309C16.2843 19.3775 14.1873 20.2475 12 20.25ZM18 12C18 12.1989 17.921 12.3897 17.7803 12.5303C17.6397 12.671 17.4489 12.75 17.25 12.75H12C11.8011 12.75 11.6103 12.671 11.4697 12.5303C11.329 12.3897 11.25 12.1989 11.25 12V6.75C11.25 6.55109 11.329 6.36032 11.4697 6.21967C11.6103 6.07902 11.8011 6 12 6C12.1989 6 12.3897 6.07902 12.5303 6.21967C12.671 6.36032 12.75 6.55109 12.75 6.75V11.25H17.25C17.4489 11.25 17.6397 11.329 17.7803 11.4697C17.921 11.6103 18 11.8011 18 12Z" fill="white" />
                                    </svg>

                                </span>
                                <span class="text-white font-md text-lg">Availability</span>
                            </div>
                            <span class="<?php echo $badgeClass; ?> text-xs font-semibold px-3 py-1 rounded-md"><?php echo $badgeStatus; ?></span>
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
                                <a href="#" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-md font-medium px-4 py-1.5 rounded-md border transition">Preview</a>

                            </div>
                        </div>
                    </div>
                    <!-- Redesigned Contact Card -->
                    <!-- Contact Card - matches provided image -->
                    <div class="rounded-xl overflow-hidden bg-white flex flex-col shadow-lg hover:shadow-xl transition-shadow flex-1">
                        <div class="bg-[#3879D3] px-6 py-4 flex items-center justify-between">
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
                            <span class="bg-green-100 text-green-600 text-xs font-semibold px-3 py-1 rounded-md">Active</span>
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
                                <a href="#" class="bg-[#4A90E2] hover:bg-[#4A90E2] text-white text-md font-medium px-4 py-1.5 rounded-md border transition">Preview</a>
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
                    <h2 class="section-title01 text-2xl md:text-4xl font-semibold text-gray-700 mb-4">
                        Our Health Services
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
<path d="M44.7006 39.5312H32.6564C32.2006 39.5312 31.7634 39.7123 31.4411 40.0347C31.1188 40.357 30.9377 40.7942 30.9377 41.25V43.8281C30.9377 45.8808 31.7531 47.8495 33.2046 49.3009C34.6561 50.7524 36.6247 51.5679 38.6774 51.5679C40.7301 51.5679 42.6988 50.7524 44.1503 49.3009C45.6017 47.8495 46.4172 45.8808 46.4172 43.8281V41.25C46.4172 40.7945 46.2364 40.3577 45.9145 40.0354C45.5927 39.7132 45.156 39.5318 44.7006 39.5312ZM42.9818 43.8281C42.9818 44.9677 42.5291 46.0607 41.7233 46.8665C40.9175 47.6723 39.8246 48.125 38.685 48.125C37.5453 48.125 36.4524 47.6723 35.6466 46.8665C34.8408 46.0607 34.3881 44.9677 34.3881 43.8281V42.9687H42.9818V43.8281ZM22.3439 34.375H10.3127C9.85684 34.375 9.41967 34.5561 9.09735 34.8784C8.77502 35.2007 8.59394 35.6379 8.59394 36.0937V38.6719C8.59394 40.7232 9.4088 42.6904 10.8593 44.1409C12.3098 45.5914 14.277 46.4062 16.3283 46.4062C18.3796 46.4062 20.3469 45.5914 21.7973 44.1409C23.2478 42.6904 24.0627 40.7232 24.0627 38.6719V36.0937C24.0627 35.6379 23.8816 35.2007 23.5593 34.8784C23.2369 34.5561 22.7998 34.375 22.3439 34.375ZM20.6252 38.6719C20.6252 39.8115 20.1725 40.9044 19.3667 41.7102C18.5608 42.516 17.4679 42.9687 16.3283 42.9687C15.1887 42.9687 14.0958 42.516 13.29 41.7102C12.4841 40.9044 12.0314 39.8115 12.0314 38.6719V37.8125H20.6252V38.6719ZM16.3283 3.4375C13.8275 3.4375 11.4019 5.65254 9.49628 9.66797C6.50351 15.9801 5.50878 25.3516 9.55858 30.293C9.72007 30.4901 9.92335 30.6488 10.1537 30.7577C10.3841 30.8665 10.6358 30.9228 10.8906 30.9225H21.751C22.0058 30.9228 22.2575 30.8665 22.4879 30.7577C22.7182 30.6488 22.9215 30.4901 23.083 30.293C27.1328 25.3516 26.1381 15.9736 23.1453 9.66797C21.2375 5.65254 18.8141 3.4375 16.3283 3.4375ZM20.8723 27.5H11.7693C9.32011 23.6113 10.2203 16.1777 12.6051 11.1482C13.9951 8.21133 15.5055 6.875 16.3283 6.875C17.1512 6.875 18.6529 8.21133 20.0451 11.1482C22.4213 16.1777 23.3215 23.6113 20.8723 27.5ZM33.2494 36.0937H44.1098C44.3646 36.0941 44.6163 36.0378 44.8466 35.929C45.077 35.8201 45.2803 35.6614 45.4418 35.4643C49.4916 30.5229 48.4969 21.1449 45.5041 14.8393C43.5984 10.8088 41.1728 8.59375 38.6721 8.59375C36.1713 8.59375 33.7543 10.8088 31.8465 14.8242C28.8537 21.1363 27.859 30.5078 31.9088 35.4492C32.0702 35.6496 32.2743 35.8115 32.5063 35.923C32.7382 36.0345 32.9921 36.0928 33.2494 36.0937ZM34.9682 16.3045C36.356 13.3676 37.8664 12.0312 38.6721 12.0312C39.4777 12.0312 40.9967 13.3676 42.3867 16.3045C44.7715 21.334 45.6717 28.7654 43.2246 32.6562H34.1281C31.6789 28.7676 32.5791 21.334 34.9639 16.3045H34.9682Z" fill="#0078DD"/>
</svg>
                    </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3">Primary Care Consultation</h4>
                        <p class="text-gray-200 text-lead">
                            Comprehensive medical check-ups, diagnosis, and treatment for common illnesses.
                        </p>
                    </div>

                    <!-- Service 2 -->
                    <div class="info-card text-center card-hover">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                        
<svg width="50" height="50" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M51.0607 14.2537L40.7482 3.94119C40.5885 3.7815 40.399 3.65483 40.1903 3.5684C39.9817 3.48198 39.758 3.4375 39.5322 3.4375C39.3064 3.4375 39.0827 3.48198 38.8741 3.5684C38.6655 3.65483 38.4759 3.7815 38.3162 3.94119C38.1565 4.10088 38.0298 4.29046 37.9434 4.4991C37.857 4.70775 37.8125 4.93137 37.8125 5.15721C37.8125 5.38304 37.857 5.60666 37.9434 5.81531C38.0298 6.02395 38.1565 6.21353 38.3162 6.37322L42.2586 10.3135L36.0947 16.4773L28.717 9.09744C28.3945 8.77493 27.957 8.59375 27.501 8.59375C27.0449 8.59375 26.6074 8.77493 26.2849 9.09744C25.9624 9.41995 25.7812 9.85736 25.7812 10.3135C25.7812 10.7695 25.9624 11.207 26.2849 11.5295L27.6492 12.8916L9.60232 30.9385C9.28173 31.2565 9.02756 31.6351 8.85458 32.0522C8.6816 32.4694 8.59326 32.9168 8.59471 33.3683V43.9773L3.94119 48.6287C3.7815 48.7884 3.65483 48.978 3.5684 49.1866C3.48198 49.3952 3.4375 49.6189 3.4375 49.8447C3.4375 50.0705 3.48198 50.2942 3.5684 50.5028C3.65483 50.7115 3.7815 50.901 3.94119 51.0607C4.2637 51.3832 4.70111 51.5644 5.15721 51.5644C5.38304 51.5644 5.60666 51.5199 5.81531 51.4335C6.02395 51.3471 6.21353 51.2204 6.37322 51.0607L11.0246 46.4072H21.6336C22.0852 46.4086 22.5325 46.3203 22.9497 46.1473C23.3668 45.9744 23.7454 45.7202 24.0635 45.3996L42.1103 27.3527L43.4724 28.717C43.6321 28.8767 43.8217 29.0033 44.0304 29.0898C44.239 29.1762 44.4626 29.2207 44.6885 29.2207C44.9143 29.2207 45.1379 29.1762 45.3466 29.0898C45.5552 29.0033 45.7448 28.8767 45.9045 28.717C46.0642 28.5573 46.1908 28.3677 46.2773 28.1591C46.3637 27.9504 46.4082 27.7268 46.4082 27.501C46.4082 27.2751 46.3637 27.0515 46.2773 26.8429C46.1908 26.6342 46.0642 26.4446 45.9045 26.2849L38.5246 18.9072L44.6885 12.7433L48.6287 16.6857C48.9512 17.0082 49.3886 17.1894 49.8447 17.1894C50.3008 17.1894 50.7382 17.0082 51.0607 16.6857C51.3832 16.3632 51.5644 15.9258 51.5644 15.4697C51.5644 15.0136 51.3832 14.5762 51.0607 14.2537ZM21.6336 42.9697H12.0322V33.3683L15.8994 29.5012L20.2693 33.8732C20.429 34.0329 20.6186 34.1596 20.8272 34.246C21.0359 34.3324 21.2595 34.3769 21.4853 34.3769C21.7112 34.3769 21.9348 34.3324 22.1434 34.246C22.3521 34.1596 22.5417 34.0329 22.7013 33.8732C22.861 33.7135 22.9877 33.524 23.0741 33.3153C23.1606 33.1067 23.205 32.883 23.205 32.6572C23.205 32.4314 23.1606 32.2077 23.0741 31.9991C22.9877 31.7905 22.861 31.6009 22.7013 31.4412L18.3293 27.0713L21.0556 24.3449L25.4256 28.717C25.7481 29.0395 26.1855 29.2207 26.6416 29.2207C27.0977 29.2207 27.5351 29.0395 27.8576 28.717C28.1801 28.3945 28.3613 27.957 28.3613 27.501C28.3613 27.0449 28.1801 26.6074 27.8576 26.2849L23.4855 21.915L30.0791 15.3215L39.6804 24.9228L21.6336 42.9697Z" fill="#0078DD"/>
</svg>                    </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3">Immunization Program</h4>
                        <p class="text-gray-200 text-lead">
                            Complete vaccination schedule for children, adults, and senior citizens.
                        </p>
                    </div>

                    <!-- Service 3 -->
                    <div class="info-card text-center card-hover">
                        <div class=" mx-auto">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
<svg width="50" height="50" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M48.0562 14.2117L29.15 3.86694C28.6448 3.58782 28.0771 3.44141 27.5 3.44141C26.9229 3.44141 26.3552 3.58782 25.85 3.86694L6.94375 14.216C6.40382 14.5114 5.95311 14.9463 5.63869 15.4754C5.32426 16.0045 5.15765 16.6083 5.15625 17.2238V37.7714C5.15765 38.3869 5.32426 38.9907 5.63869 39.5198C5.95311 40.0489 6.40382 40.4838 6.94375 40.7792L25.85 51.1283C26.3552 51.4074 26.9229 51.5538 27.5 51.5538C28.0771 51.5538 28.6448 51.4074 29.15 51.1283L48.0562 40.7792C48.5962 40.4838 49.0469 40.0489 49.3613 39.5198C49.6757 38.9907 49.8424 38.3869 49.8438 37.7714V17.2259C49.8435 16.6094 49.6774 16.0042 49.363 15.4739C49.0485 14.9436 48.5971 14.5076 48.0562 14.2117ZM27.5 6.87475L44.7605 16.3279L38.3647 19.8298L21.102 10.3767L27.5 6.87475ZM27.5 25.781L10.2395 16.3279L17.5227 12.3404L34.7832 21.7935L27.5 25.781ZM8.59375 19.3357L25.7812 28.7415V47.173L8.59375 37.7736V19.3357ZM46.4062 37.765L29.2188 47.173V28.7501L36.0938 24.9882V32.656C36.0938 33.1118 36.2748 33.549 36.5972 33.8713C36.9195 34.1937 37.3567 34.3747 37.8125 34.3747C38.2683 34.3747 38.7055 34.1937 39.0278 33.8713C39.3502 33.549 39.5312 33.1118 39.5312 32.656V23.1062L46.4062 19.3357V37.7628V37.765Z" fill="#0078DD"/>
</svg>

                   </div>


                        </div>
                        <h4 class="text-xl font-semibold text-gray-700 mb-3">Emergency Services</h4>
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
                    <h2 class="section-title02 text-3xl md:text-4xl font-semibold text-white">
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
            <div class="max-w-[1900px] mx-auto px-8 sm:px-10 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="section-title text-3xl md:text-4xl font-semibold text-gray-900">
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
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M52.9359 24.0984C52.0523 23.175 51.1383 22.2234 50.7938 21.3867C50.475 20.6203 50.4562 19.35 50.4375 18.1195C50.4023 15.832 50.3648 13.2398 48.5625 11.4375C46.7602 9.63516 44.168 9.59766 41.8805 9.5625C40.65 9.54375 39.3797 9.525 38.6133 9.20625C37.7789 8.86172 36.825 7.94766 35.9016 7.06406C34.2844 5.51016 32.4469 3.75 30 3.75C27.5531 3.75 25.718 5.51016 24.0984 7.06406C23.175 7.94766 22.2234 8.86172 21.3867 9.20625C20.625 9.525 19.35 9.54375 18.1195 9.5625C15.832 9.59766 13.2398 9.63516 11.4375 11.4375C9.63516 13.2398 9.60937 15.832 9.5625 18.1195C9.54375 19.35 9.525 20.6203 9.20625 21.3867C8.86172 22.2211 7.94766 23.175 7.06406 24.0984C5.51016 25.7156 3.75 27.5531 3.75 30C3.75 32.4469 5.51016 34.282 7.06406 35.9016C7.94766 36.825 8.86172 37.7766 9.20625 38.6133C9.525 39.3797 9.54375 40.65 9.5625 41.8805C9.59766 44.168 9.63516 46.7602 11.4375 48.5625C13.2398 50.3648 15.832 50.4023 18.1195 50.4375C19.35 50.4562 20.6203 50.475 21.3867 50.7938C22.2211 51.1383 23.175 52.0523 24.0984 52.9359C25.7156 54.4898 27.5531 56.25 30 56.25C32.4469 56.25 34.282 54.4898 35.9016 52.9359C36.825 52.0523 37.7766 51.1383 38.6133 50.7938C39.3797 50.475 40.65 50.4562 41.8805 50.4375C44.168 50.4023 46.7602 50.3648 48.5625 48.5625C50.3648 46.7602 50.4023 44.168 50.4375 41.8805C50.4562 40.65 50.475 39.3797 50.7938 38.6133C51.1383 37.7789 52.0523 36.825 52.9359 35.9016C54.4898 34.2844 56.25 32.4469 56.25 30C56.25 27.5531 54.4898 25.718 52.9359 24.0984ZM50.2289 33.307C49.1062 34.4789 47.9437 35.6906 47.3273 37.1789C46.7367 38.6086 46.7109 40.2422 46.6875 41.8242C46.6641 43.4648 46.6383 45.1828 45.9094 45.9094C45.1805 46.6359 43.4742 46.6641 41.8242 46.6875C40.2422 46.7109 38.6086 46.7367 37.1789 47.3273C35.6906 47.9437 34.4789 49.1062 33.307 50.2289C32.1352 51.3516 30.9375 52.5 30 52.5C29.0625 52.5 27.8555 51.3469 26.693 50.2289C25.5305 49.1109 24.3094 47.9437 22.8211 47.3273C21.3914 46.7367 19.7578 46.7109 18.1758 46.6875C16.5352 46.6641 14.8172 46.6383 14.0906 45.9094C13.3641 45.1805 13.3359 43.4742 13.3125 41.8242C13.2891 40.2422 13.2633 38.6086 12.6727 37.1789C12.0562 35.6906 10.8937 34.4789 9.77109 33.307C8.64844 32.1352 7.5 30.9375 7.5 30C7.5 29.0625 8.65312 27.8555 9.77109 26.693C10.8891 25.5305 12.0562 24.3094 12.6727 22.8211C13.2633 21.3914 13.2891 19.7578 13.3125 18.1758C13.3359 16.5352 13.3617 14.8172 14.0906 14.0906C14.8195 13.3641 16.5258 13.3359 18.1758 13.3125C19.7578 13.2891 21.3914 13.2633 22.8211 12.6727C24.3094 12.0562 25.5211 10.8937 26.693 9.77109C27.8648 8.64844 29.0625 7.5 30 7.5C30.9375 7.5 32.1445 8.65312 33.307 9.77109C34.4695 10.8891 35.6906 12.0562 37.1789 12.6727C38.6086 13.2633 40.2422 13.2891 41.8242 13.3125C43.4648 13.3359 45.1828 13.3617 45.9094 14.0906C46.6359 14.8195 46.6641 16.5258 46.6875 18.1758C46.7109 19.7578 46.7367 21.3914 47.3273 22.8211C47.9437 24.3094 49.1062 25.5211 50.2289 26.693C51.3516 27.8648 52.5 29.0625 52.5 30C52.5 30.9375 51.3469 32.1445 50.2289 33.307ZM40.7016 23.0484C40.8759 23.2226 41.0142 23.4294 41.1086 23.657C41.2029 23.8846 41.2515 24.1286 41.2515 24.375C41.2515 24.6214 41.2029 24.8654 41.1086 25.093C41.0142 25.3206 40.8759 25.5274 40.7016 25.7016L27.5766 38.8266C27.4024 39.0009 27.1956 39.1392 26.968 39.2336C26.7404 39.3279 26.4964 39.3765 26.25 39.3765C26.0036 39.3765 25.7596 39.3279 25.532 39.2336C25.3044 39.1392 25.0976 39.0009 24.9234 38.8266L19.2984 33.2016C18.9466 32.8497 18.749 32.3726 18.749 31.875C18.749 31.3774 18.9466 30.9003 19.2984 30.5484C19.6503 30.1966 20.1274 29.999 20.625 29.999C21.1226 29.999 21.5997 30.1966 21.9516 30.5484L26.25 34.8492L38.0484 23.0484C38.2226 22.8741 38.4294 22.7358 38.657 22.6415C38.8846 22.5471 39.1286 22.4985 39.375 22.4985C39.6214 22.4985 39.8654 22.5471 40.093 22.6415C40.3206 22.7358 40.5274 22.8741 40.7016 23.0484Z" fill="#0078DD"/>
</svg>




                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Mrs. Juzaly VIllaruz</h4>
                                <p class="text-gray-600 text-md">Barangay Health Midwife</p>
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
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M52.9359 24.0984C52.0523 23.175 51.1383 22.2234 50.7938 21.3867C50.475 20.6203 50.4562 19.35 50.4375 18.1195C50.4023 15.832 50.3648 13.2398 48.5625 11.4375C46.7602 9.63516 44.168 9.59766 41.8805 9.5625C40.65 9.54375 39.3797 9.525 38.6133 9.20625C37.7789 8.86172 36.825 7.94766 35.9016 7.06406C34.2844 5.51016 32.4469 3.75 30 3.75C27.5531 3.75 25.718 5.51016 24.0984 7.06406C23.175 7.94766 22.2234 8.86172 21.3867 9.20625C20.625 9.525 19.35 9.54375 18.1195 9.5625C15.832 9.59766 13.2398 9.63516 11.4375 11.4375C9.63516 13.2398 9.60937 15.832 9.5625 18.1195C9.54375 19.35 9.525 20.6203 9.20625 21.3867C8.86172 22.2211 7.94766 23.175 7.06406 24.0984C5.51016 25.7156 3.75 27.5531 3.75 30C3.75 32.4469 5.51016 34.282 7.06406 35.9016C7.94766 36.825 8.86172 37.7766 9.20625 38.6133C9.525 39.3797 9.54375 40.65 9.5625 41.8805C9.59766 44.168 9.63516 46.7602 11.4375 48.5625C13.2398 50.3648 15.832 50.4023 18.1195 50.4375C19.35 50.4562 20.6203 50.475 21.3867 50.7938C22.2211 51.1383 23.175 52.0523 24.0984 52.9359C25.7156 54.4898 27.5531 56.25 30 56.25C32.4469 56.25 34.282 54.4898 35.9016 52.9359C36.825 52.0523 37.7766 51.1383 38.6133 50.7938C39.3797 50.475 40.65 50.4562 41.8805 50.4375C44.168 50.4023 46.7602 50.3648 48.5625 48.5625C50.3648 46.7602 50.4023 44.168 50.4375 41.8805C50.4562 40.65 50.475 39.3797 50.7938 38.6133C51.1383 37.7789 52.0523 36.825 52.9359 35.9016C54.4898 34.2844 56.25 32.4469 56.25 30C56.25 27.5531 54.4898 25.718 52.9359 24.0984ZM50.2289 33.307C49.1062 34.4789 47.9437 35.6906 47.3273 37.1789C46.7367 38.6086 46.7109 40.2422 46.6875 41.8242C46.6641 43.4648 46.6383 45.1828 45.9094 45.9094C45.1805 46.6359 43.4742 46.6641 41.8242 46.6875C40.2422 46.7109 38.6086 46.7367 37.1789 47.3273C35.6906 47.9437 34.4789 49.1062 33.307 50.2289C32.1352 51.3516 30.9375 52.5 30 52.5C29.0625 52.5 27.8555 51.3469 26.693 50.2289C25.5305 49.1109 24.3094 47.9437 22.8211 47.3273C21.3914 46.7367 19.7578 46.7109 18.1758 46.6875C16.5352 46.6641 14.8172 46.6383 14.0906 45.9094C13.3641 45.1805 13.3359 43.4742 13.3125 41.8242C13.2891 40.2422 13.2633 38.6086 12.6727 37.1789C12.0562 35.6906 10.8937 34.4789 9.77109 33.307C8.64844 32.1352 7.5 30.9375 7.5 30C7.5 29.0625 8.65312 27.8555 9.77109 26.693C10.8891 25.5305 12.0562 24.3094 12.6727 22.8211C13.2633 21.3914 13.2891 19.7578 13.3125 18.1758C13.3359 16.5352 13.3617 14.8172 14.0906 14.0906C14.8195 13.3641 16.5258 13.3359 18.1758 13.3125C19.7578 13.2891 21.3914 13.2633 22.8211 12.6727C24.3094 12.0562 25.5211 10.8937 26.693 9.77109C27.8648 8.64844 29.0625 7.5 30 7.5C30.9375 7.5 32.1445 8.65312 33.307 9.77109C34.4695 10.8891 35.6906 12.0562 37.1789 12.6727C38.6086 13.2633 40.2422 13.2891 41.8242 13.3125C43.4648 13.3359 45.1828 13.3617 45.9094 14.0906C46.6359 14.8195 46.6641 16.5258 46.6875 18.1758C46.7109 19.7578 46.7367 21.3914 47.3273 22.8211C47.9437 24.3094 49.1062 25.5211 50.2289 26.693C51.3516 27.8648 52.5 29.0625 52.5 30C52.5 30.9375 51.3469 32.1445 50.2289 33.307ZM40.7016 23.0484C40.8759 23.2226 41.0142 23.4294 41.1086 23.657C41.2029 23.8846 41.2515 24.1286 41.2515 24.375C41.2515 24.6214 41.2029 24.8654 41.1086 25.093C41.0142 25.3206 40.8759 25.5274 40.7016 25.7016L27.5766 38.8266C27.4024 39.0009 27.1956 39.1392 26.968 39.2336C26.7404 39.3279 26.4964 39.3765 26.25 39.3765C26.0036 39.3765 25.7596 39.3279 25.532 39.2336C25.3044 39.1392 25.0976 39.0009 24.9234 38.8266L19.2984 33.2016C18.9466 32.8497 18.749 32.3726 18.749 31.875C18.749 31.3774 18.9466 30.9003 19.2984 30.5484C19.6503 30.1966 20.1274 29.999 20.625 29.999C21.1226 29.999 21.5997 30.1966 21.9516 30.5484L26.25 34.8492L38.0484 23.0484C38.2226 22.8741 38.4294 22.7358 38.657 22.6415C38.8846 22.5471 39.1286 22.4985 39.375 22.4985C39.6214 22.4985 39.8654 22.5471 40.093 22.6415C40.3206 22.7358 40.5274 22.8741 40.7016 23.0484Z" fill="#0078DD"/>
</svg>

                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Capt. Juan Dela Cruz</h4>
                                <p class="text-gray-600 text-md">Barangay Captain</p>
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
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M52.9359 24.0984C52.0523 23.175 51.1383 22.2234 50.7938 21.3867C50.475 20.6203 50.4562 19.35 50.4375 18.1195C50.4023 15.832 50.3648 13.2398 48.5625 11.4375C46.7602 9.63516 44.168 9.59766 41.8805 9.5625C40.65 9.54375 39.3797 9.525 38.6133 9.20625C37.7789 8.86172 36.825 7.94766 35.9016 7.06406C34.2844 5.51016 32.4469 3.75 30 3.75C27.5531 3.75 25.718 5.51016 24.0984 7.06406C23.175 7.94766 22.2234 8.86172 21.3867 9.20625C20.625 9.525 19.35 9.54375 18.1195 9.5625C15.832 9.59766 13.2398 9.63516 11.4375 11.4375C9.63516 13.2398 9.60937 15.832 9.5625 18.1195C9.54375 19.35 9.525 20.6203 9.20625 21.3867C8.86172 22.2211 7.94766 23.175 7.06406 24.0984C5.51016 25.7156 3.75 27.5531 3.75 30C3.75 32.4469 5.51016 34.282 7.06406 35.9016C7.94766 36.825 8.86172 37.7766 9.20625 38.6133C9.525 39.3797 9.54375 40.65 9.5625 41.8805C9.59766 44.168 9.63516 46.7602 11.4375 48.5625C13.2398 50.3648 15.832 50.4023 18.1195 50.4375C19.35 50.4562 20.6203 50.475 21.3867 50.7938C22.2211 51.1383 23.175 52.0523 24.0984 52.9359C25.7156 54.4898 27.5531 56.25 30 56.25C32.4469 56.25 34.282 54.4898 35.9016 52.9359C36.825 52.0523 37.7766 51.1383 38.6133 50.7938C39.3797 50.475 40.65 50.4562 41.8805 50.4375C44.168 50.4023 46.7602 50.3648 48.5625 48.5625C50.3648 46.7602 50.4023 44.168 50.4375 41.8805C50.4562 40.65 50.475 39.3797 50.7938 38.6133C51.1383 37.7789 52.0523 36.825 52.9359 35.9016C54.4898 34.2844 56.25 32.4469 56.25 30C56.25 27.5531 54.4898 25.718 52.9359 24.0984ZM50.2289 33.307C49.1062 34.4789 47.9437 35.6906 47.3273 37.1789C46.7367 38.6086 46.7109 40.2422 46.6875 41.8242C46.6641 43.4648 46.6383 45.1828 45.9094 45.9094C45.1805 46.6359 43.4742 46.6641 41.8242 46.6875C40.2422 46.7109 38.6086 46.7367 37.1789 47.3273C35.6906 47.9437 34.4789 49.1062 33.307 50.2289C32.1352 51.3516 30.9375 52.5 30 52.5C29.0625 52.5 27.8555 51.3469 26.693 50.2289C25.5305 49.1109 24.3094 47.9437 22.8211 47.3273C21.3914 46.7367 19.7578 46.7109 18.1758 46.6875C16.5352 46.6641 14.8172 46.6383 14.0906 45.9094C13.3641 45.1805 13.3359 43.4742 13.3125 41.8242C13.2891 40.2422 13.2633 38.6086 12.6727 37.1789C12.0562 35.6906 10.8937 34.4789 9.77109 33.307C8.64844 32.1352 7.5 30.9375 7.5 30C7.5 29.0625 8.65312 27.8555 9.77109 26.693C10.8891 25.5305 12.0562 24.3094 12.6727 22.8211C13.2633 21.3914 13.2891 19.7578 13.3125 18.1758C13.3359 16.5352 13.3617 14.8172 14.0906 14.0906C14.8195 13.3641 16.5258 13.3359 18.1758 13.3125C19.7578 13.2891 21.3914 13.2633 22.8211 12.6727C24.3094 12.0562 25.5211 10.8937 26.693 9.77109C27.8648 8.64844 29.0625 7.5 30 7.5C30.9375 7.5 32.1445 8.65312 33.307 9.77109C34.4695 10.8891 35.6906 12.0562 37.1789 12.6727C38.6086 13.2633 40.2422 13.2891 41.8242 13.3125C43.4648 13.3359 45.1828 13.3617 45.9094 14.0906C46.6359 14.8195 46.6641 16.5258 46.6875 18.1758C46.7109 19.7578 46.7367 21.3914 47.3273 22.8211C47.9437 24.3094 49.1062 25.5211 50.2289 26.693C51.3516 27.8648 52.5 29.0625 52.5 30C52.5 30.9375 51.3469 32.1445 50.2289 33.307ZM40.7016 23.0484C40.8759 23.2226 41.0142 23.4294 41.1086 23.657C41.2029 23.8846 41.2515 24.1286 41.2515 24.375C41.2515 24.6214 41.2029 24.8654 41.1086 25.093C41.0142 25.3206 40.8759 25.5274 40.7016 25.7016L27.5766 38.8266C27.4024 39.0009 27.1956 39.1392 26.968 39.2336C26.7404 39.3279 26.4964 39.3765 26.25 39.3765C26.0036 39.3765 25.7596 39.3279 25.532 39.2336C25.3044 39.1392 25.0976 39.0009 24.9234 38.8266L19.2984 33.2016C18.9466 32.8497 18.749 32.3726 18.749 31.875C18.749 31.3774 18.9466 30.9003 19.2984 30.5484C19.6503 30.1966 20.1274 29.999 20.625 29.999C21.1226 29.999 21.5997 30.1966 21.9516 30.5484L26.25 34.8492L38.0484 23.0484C38.2226 22.8741 38.4294 22.7358 38.657 22.6415C38.8846 22.5471 39.1286 22.4985 39.375 22.4985C39.6214 22.4985 39.8654 22.5471 40.093 22.6415C40.3206 22.7358 40.5274 22.8741 40.7016 23.0484Z" fill="#0078DD"/>
</svg>

                            </div>
                            <div>
                                <h4 class="text-xl font-normal text-gray-900">Nurse Lisa Mendoza</h4>
                                <p class="text-gray-600 text-md">Head Nurse</p>
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

    <!-- Instruction Modal - Automatic Display on Every Refresh -->
    <div id="instructionModal" class="fixed inset-0 hidden z-[9998] bg-black/50 backdrop-blur-sm flex justify-center items-center">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[85vh] overflow-y-auto modal-content instruction-modal-content">
            <!-- Close Button -->
            <button onclick="closeInstructionModal()"
                class="modal-close-btn absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10 bg-white rounded-full p-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="p-8">
                <!-- Header with icon -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M32.9331 6.13159L12.5815 2.53784C11.9286 2.42295 11.2568 2.57207 10.7139 2.9524C10.1709 3.33273 9.80127 3.91313 9.68618 4.56597L5.03774 30.9722C4.98088 31.2957 4.98833 31.6272 5.05966 31.9479C5.13098 32.2685 5.26479 32.5719 5.45343 32.8408C5.64206 33.1097 5.88184 33.3387 6.15904 33.5149C6.43625 33.6911 6.74545 33.8109 7.06899 33.8675L27.4206 37.4613C27.7442 37.5184 28.0758 37.5111 28.3966 37.4399C28.7174 37.3686 29.021 37.2349 29.2901 37.0462C29.5592 36.8576 29.7884 36.6177 29.9647 36.3404C30.1409 36.0631 30.2608 35.7537 30.3174 35.43L34.9659 9.02378C35.0797 8.37069 34.9296 7.69912 34.5483 7.15675C34.1671 6.61438 33.5861 6.24563 32.9331 6.13159ZM27.8518 34.9988L7.49868 31.405L12.1471 4.99878L32.4987 8.59253L27.8518 34.9988ZM13.9581 9.12691C14.016 8.80061 14.2011 8.51066 14.4727 8.3208C14.7443 8.13094 15.0802 8.0567 15.4065 8.11441L28.3752 10.4035C28.6835 10.4575 28.9602 10.6251 29.1508 10.8732C29.3415 11.1214 29.4321 11.432 29.4048 11.7437C29.3775 12.0554 29.2343 12.3456 29.0035 12.5568C28.7726 12.7681 28.471 12.8851 28.1581 12.8847C28.0847 12.8846 28.0116 12.8783 27.9393 12.866L14.9706 10.5753C14.6443 10.5174 14.3543 10.3323 14.1644 10.0607C13.9746 9.78912 13.9003 9.45323 13.9581 9.12691ZM13.0924 14.0519C13.1209 13.8902 13.181 13.7357 13.2692 13.5972C13.3574 13.4587 13.4721 13.339 13.6066 13.2448C13.7411 13.1506 13.8928 13.0839 14.0531 13.0484C14.2134 13.0129 14.3792 13.0093 14.5409 13.0378L27.5096 15.3285C27.82 15.3804 28.0994 15.5475 28.292 15.7963C28.4846 16.0452 28.5762 16.3576 28.5486 16.6711C28.521 16.9845 28.3761 17.2761 28.143 17.4874C27.9098 17.6988 27.6055 17.8144 27.2909 17.8113C27.217 17.8114 27.1432 17.8046 27.0706 17.791L14.1018 15.5019C13.7758 15.4433 13.4863 15.2577 13.2971 14.9858C13.1078 14.7139 13.0342 14.378 13.0924 14.0519ZM12.2252 18.9753C12.2842 18.6499 12.4698 18.3611 12.7413 18.1722C13.0128 17.9833 13.348 17.9097 13.6737 17.9675L20.1549 19.1066C20.463 19.1606 20.7397 19.3281 20.9303 19.5761C21.1209 19.824 21.2116 20.1345 21.1845 20.4461C21.1575 20.7577 21.0146 21.0478 20.784 21.2592C20.5535 21.4706 20.2521 21.5878 19.9393 21.5878C19.866 21.5878 19.7928 21.5815 19.7206 21.5691L13.2362 20.4238C12.9102 20.3655 12.6206 20.1803 12.4311 19.9087C12.2415 19.6371 12.1675 19.3014 12.2252 18.9753Z" fill="#0078DD"/>
</svg>

                    </div>
                    <h2 class="text-2xl font-semibold text-gray-800">Data Privacy & Security Notice</h2>
                    <p class="text-gray-500 mt-2">Your health information is protected and confidential</p>
                </div>

                <!-- Content -->
                <div class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Confidentiality Assurance</h3>
                                <p class="text-gray-600">
                                    Barangay Luz Health Center strictly adheres to the Data Privacy Act of 2012 (RA 10173). 
                                    Your personal and health information is confidential and will only be accessible to authorized 
                                    healthcare personnel involved in your care.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Personal Account Security</h3>
                                <p class="text-gray-600">
                                    Each resident is provided with a unique username and password. Your login credentials 
                                    are personal and should not be shared with anyone. The system tracks access to ensure 
                                    only authorized viewing of your health records.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">What You Can Access</h3>
                                <p class="text-gray-600">
                                    Upon successful login, you will have access to:
                                </p>
                                <ul class="instruction-list mt-3">
                                    <li class="text-gray-600">Your complete health history and medical records</li>
                                    <li class="text-gray-600">Immunization records and vaccination schedules</li>
                                    <li class="text-gray-600">Upcoming health appointments and consultations</li>
                                    <li class="text-gray-600">Health advisories and announcements specific to you</li>
                                    <li class="text-gray-600">Medication prescriptions and treatment plans</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-5">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Important Reminders</h3>
                                <p class="text-gray-600">
                                    • Never share your login credentials with others<br>
                                    • Log out after each session, especially on shared devices<br>
                                    • Report any unauthorized access to your account immediately<br>
                                    • Update your password regularly for added security<br>
                                    • Contact the Health Center for any account-related concerns
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8">
                    <button onclick="closeInstructionModal()"
                        class="flex-1 bg-[#3a7bd5] hover:bg-[#2a6bc5] text-white font-semibold py-3 px-6 rounded-md transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-check-circle mr-2"></i> I Understand and Proceed
                    </button>
                    <button onclick="closeInstructionModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-md transition-all duration-200">
                        <i class="fas fa-times-circle mr-2"></i> Close
                    </button>
                </div>

                <p class="text-center text-xs text-gray-400 mt-6">
                    By proceeding, you acknowledge that you have read and understood the data privacy guidelines.
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
        <div class="space-y-6 px-1">
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
                                    <i class="fas fa-info-circle mr-1"></i> Announcement
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
                            <span>Posted: <?= date('F j, Y', strtotime($announcement['post_date'])) ?></span>
                        </div>

                        <?php if ($announcement['expiry_date']): ?>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                <span>Valid until: <?= date('M d, Y', strtotime($announcement['expiry_date'])) ?></span>
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