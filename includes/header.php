<?php
ob_start(); // Start output buffering

require_once __DIR__ . '/auth.php';

// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Get current page to determine active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get user's profile picture if exists
$profile_picture = null;
if (isset($_SESSION['user']['id'])) {
    // Define paths for profile pictures
    $profile_dir = __DIR__ . '/../uploads/profiles/';

    // Create directory if it doesn't exist
    if (!file_exists($profile_dir)) {
        mkdir($profile_dir, 0777, true);
    }

    // Check for profile picture
    $user_id = $_SESSION['user']['id'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    foreach ($allowed_extensions as $ext) {
        $potential_file = $profile_dir . 'profile_' . $user_id . '.' . $ext;
        if (file_exists($potential_file)) {
            // Add cache-buster based on file modification time to prevent stale image caching
            $file_mtime = filemtime($potential_file);
            $profile_picture = '/community-health-tracker/uploads/profiles/profile_' . $user_id . '.' . $ext . '?v=' . $file_mtime;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Luz Health Monitoring and Tracking</title>
    <link rel="icon" type="image/png" href="../asssets/images/Luz.jpg">
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/style.css">
    <script src="/community-health-tracker/asssets/js/script.js" defer></script>
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

    body,
    .sidebar,
    .nav-tab,
    .logout-btn-1,
    .logout-btn-2,
    .logout-btn-3,
    .time-display-container,
    .continue-btn,
    .complete-btn,
    .profile-upload-btn,
    .profile-remove-btn,
    .logout-cancel-btn,
    .logout-confirm-btn {
        font-family: 'Poppins', sans-serif !important;
    }

    /* Smooth transition for sidebar */
    .sidebar {
        transition: transform 0.3s ease-in-out;
    }

    .sidebar-hidden {
        transform: translateX(-100%);
    }

    /* Optional: Add overlay for mobile */
    .overlay {
        background: rgba(0, 0, 0, 0.5);
        transition: opacity 0.3s ease-in-out;
    }

    .overlay-hidden {
        opacity: 0;
        pointer-events: none;
    }

    /* Blinking colon animation */
    @keyframes blink {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }

    .blinking-colon {
        animation: blink 1s infinite;
    }

    /* CLEAN: User Navigation Tab Styles */
    .user-nav-tab {
        position: relative;
        transition: all 0.3s ease;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        z-index: 1;
        color: white !important;
        background: transparent;
        text-decoration: none;
        display: inline-block;
    }

    .user-nav-tab.user-active {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
        color: white !important;
        box-shadow: 0 2px 8px rgba(255, 255, 255, 0.15);
    }

    .user-nav-tab:hover:not(.user-active) {
        background: rgba(255, 255, 255, 0.15);
        cursor: pointer;
    }

    /* CLEAN: Staff Navigation Tab Styles */
    .staff-nav-tab {
        position: relative;
        transition: all 0.3s ease;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        z-index: 1;
        color: white !important;
        background: transparent;
        text-decoration: none;
        display: inline-block;
    }

    .staff-nav-tab.staff-active {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
        color: white !important;
        box-shadow: 0 2px 8px rgba(255, 255, 255, 0.15);
    }

    .staff-nav-tab:hover:not(.staff-active) {
        background: rgba(255, 255, 255, 0.15);
        cursor: pointer;
    }

    /* CLEAN: Admin Navigation Tab Styles */
    .admin-nav-tab {
        position: relative;
        transition: all 0.3s ease;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        z-index: 1;
        color: white !important;
        background: transparent;
        text-decoration: none;
        display: inline-block;
    }

    .admin-nav-tab.admin-active {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
        color: white !important;
        box-shadow: 0 2px 8px rgba(255, 255, 255, 0.15);
    }

    .admin-nav-tab:hover:not(.admin-active) {
        background: rgba(255, 255, 255, 0.15);
        cursor: pointer;
    }

    /* CLEAN: Resident User Logout Button - Warm Blue Text */
.logout-btn-1 {
    background: white !important;
    color: #3C96E1 !important; /* This already has !important */
    padding: 0.75rem 1.5rem !important;
    border-radius: 9999px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    text-decoration: none !important;
    cursor: pointer !important;
    border: none !important;
    outline: none !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

    nav.text-white .logout-btn-1,
    nav.text-white .logout-btn-1 span,
    nav.text-white .logout-btn-1 i {
        color: #3C96E1 !important;
    }

    .logout-btn-1:hover {
        background: white !important;
        color: #2B7CC9 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(60, 150, 225, 0.2) !important;
    }

    nav.text-white .logout-btn-1:hover,
    nav.text-white .logout-btn-1:hover span,
    nav.text-white .logout-btn-1:hover i {
        color: #2B7CC9 !important;
    }

    /* CLEAN: Staff Logout Button - Warm Violet Text */
    /* CLEAN: Staff Logout Button - Warm Violet Text */
.logout-btn-2 {
    background: white !important;
    color: #9333EA !important;
    padding: 0.75rem 1.5rem !important;
    border-radius: 9999px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    text-decoration: none !important;
    cursor: pointer !important;
    border: none !important;
    outline: none !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

    .logout-btn-2:hover {
        background: white !important;
        color: #7B2CC9 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(147, 51, 234, 0.2) !important;
    }

    /* CLEAN: Super Admin Logout Button - Warm Blue Text */
    .logout-btn-3 {
        background: white !important;
        color: #3C96E1 !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 9999px !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        text-decoration: none !important;
        cursor: pointer !important;
        border: none !important;
        outline: none !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }

    .logout-btn-3:hover {
        background: white !important;
        color: #2B7CC9 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(60, 150, 225, 0.2) !important;
    }

   
    /* NEW: Improved time display containers - Horizontal layout */
    .time-display-container {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.15);
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        margin-left: auto;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .staff-time-container {
        background-color: rgba(255, 255, 255, 0.15);
    }

    .user-time-container {
        background-color: rgba(255, 255, 255, 0.15);
    }

    .admin-time-container {
        background-color: rgba(255, 255, 255, 0.15);
    }

    /* Add padding to nav containers */
    .staff-nav-container,
    .admin-nav-container,
    .user-nav-container {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }

    /* NEW: Horizontal time display styles */
    .time-display-horizontal {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .date-display-horizontal {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .time-display-main-horizontal {
        display: flex;
        align-items: center;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 1px;
        white-space: nowrap;
    }

    .time-separator {
        height: 24px;
        width: 1px;
        background: rgba(255, 255, 255, 0.4);
        margin: 0 0.5rem;
    }

    .time-zone {
        font-size: 0.75rem;
        margin-left: 0.25rem;
        opacity: 0.9;
        font-style: italic;
        font-weight: 500;
    }

    /* Hidden refresh indicator */
    .refresh-indicator {
        position: absolute;
        width: 0;
        height: 0;
        overflow: hidden;
        opacity: 0;
    }

    /* Form validation styles */
    .form-input:invalid {
        border-color: #fca5a5;
    }

    .form-input:valid {
        border-color: #74b4fdff;
    }

    /* Updated Registration Button Styles with Rounded XL Sides */
    .continue-btn,
    .complete-btn {
        width: 100%;
        border-radius: 8px !important;
        /* rounded-full equivalent */
        padding: 0.75rem 2.8rem !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        color: white !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        text-decoration: none !important;
        /* Remove underline for links */
    }

    /* First Registration Modal Button (Red) */
    .continue-btn {
        background-color: #4A90E2 !important;
    }

    .continue-btn:hover {
        background-color: #337ed3ff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(252, 86, 108, 0.3) !important;
    }

    /* Complete Registration, Login, and Book Appointment Buttons (Warm Blue) */
    .complete-btn {
        background-color: #4A90E2 !important;
    }

    .complete-btn:hover {
        background-color: #357ABD !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3) !important;
    }

    .continue-btn:active,
    .complete-btn:active {
        transform: scale(0.98) !important;
    }

    .continue-btn:disabled,
    .complete-btn:disabled {
        cursor: not-allowed !important;
        transform: none !important;
        box-shadow: none !important;
        opacity: 0.5 !important;
    }

    .continue-btn:disabled {
        background-color: #4A90E2 !important;
    }

    .complete-btn:disabled {
        background-color: #4A90E2 !important;
    }

    .continue-btn svg,
    .complete-btn svg {
        width: 16px !important;
        height: 16px !important;
        margin-left: 8px !important;
    }

    .continue-btn:disabled:hover,
    .complete-btn:disabled:hover {
        transform: none !important;
        box-shadow: none !important;
    }

    /* Logo image styles */
    .logo-image {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Header title styles */
    .header-title-container {
        display: flex;
        flex-direction: column;
    }

    .barangay-text {
        line-height: 1;
        margin-bottom: 2px;
        opacity: 0.9;
    }

    .main-title {
        line-height: 1.2;
    }

    /* Profile section styles */
    .profile-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-left: 1rem;
        border-left: 1px solid rgba(255, 255, 255, 0.3);
    }


    .profile-avatar {
        background-color: #d1d5db;
        height: 56px;
        width: 56px;
        border-radius: 50%;
        background-size: cover;
        background-position: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        border: 3px solid #4A90E2;
        /* Smooth blue border */
        box-shadow: 0 2px 8px rgba(74, 144, 226, 0.10);
    }

    .profile-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .profile-avatar.has-image::after {
        content: 'Change';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .profile-avatar.has-image:hover::after {
        opacity: 1;
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .welcome-text {
        color: white;
        font-size: 0.875rem;
    }

    .username-text {
        font-size: 0.75rem;
    }

    /* NEW: Enhanced Modal Styles */
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

    /* Profile Picture Upload Modal */
    .profile-modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(60, 150, 225, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(60, 150, 225, 0.1);
        animation: slideInUp 0.3s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .profile-preview {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #3C96E1;
        margin: 0 auto 2rem;
        display: block;
        box-shadow: 0 8px 20px rgba(60, 150, 225, 0.25);
        transition: transform 0.3s ease;
    }

    .profile-preview:hover {
        transform: scale(1.05);
    }

    /* Enhanced File Input */
    #profile_image {
        transition: all 0.3s ease;
    }

    #profile_image:focus {
        box-shadow: 0 0 0 3px rgba(60, 150, 225, 0.1) !important;
    }

    .profile-file-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        border: 2px dashed #3C96E1;
        border-radius: 12px;
        background: rgba(60, 150, 225, 0.05);
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .profile-file-label:hover {
        background: rgba(60, 150, 225, 0.1);
        border-color: #2B7CC9;
    }

    .profile-file-label.dragover {
        background: rgba(60, 150, 225, 0.15);
        border-color: #2B7CC9;
        transform: scale(1.02);
    }

    .profile-upload-btn {
        background: linear-gradient(135deg, #3C96E1 0%, #2B7CC9 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        flex: 1;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(60, 150, 225, 0.25);
    }

    .profile-upload-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(60, 150, 225, 0.35);
    }

    .profile-upload-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .profile-upload-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .profile-remove-btn {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        flex: 1;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    .profile-remove-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
    }

    .profile-remove-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .profile-remove-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    /* Logout Modal Styles */
    .logout-modal-content {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .logout-modal-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .logout-cancel-btn {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border-radius: 9999px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 2px solid #d1d5db;
        background: white;
        color: #4b5563;
        cursor: pointer;
    }

    .logout-cancel-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .logout-confirm-btn {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border-radius: 9999px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        background: #ef4444;
        color: white;
        cursor: pointer;
    }

    .logout-confirm-btn:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    /* Add these new styles for disabled buttons */
    .continue-btn:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        transform: none !important;
        box-shadow: none !important;
    }

    .continue-btn:disabled:hover {
        background-color: #4A90E2 !important;
        transform: none !important;
        box-shadow: none !important;
    }

    /* Responsive adjustments - Desktop and larger tablets */
    @media (min-width: 1025px) {

        .staff-nav-container,
        .user-nav-container,
        .admin-nav-container {
            flex-direction: row;
            gap: 0;
            align-items: center;
            justify-content: space-between;
        }

        .user-nav-tab,
        .staff-nav-tab,
        .admin-nav-tab {
            position: relative;
            transition: all 0.3s ease;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            z-index: 1;
        }

        .user-nav-tab.user-active,
        .staff-nav-tab.staff-active,
        .admin-nav-tab.admin-active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .user-nav-tab:hover:not(.user-active),
        .staff-nav-tab:hover:not(.staff-active),
        .admin-nav-tab:hover:not(.admin-active) {
            background: rgba(255, 255, 255, 0.1);
        }

        .time-display-container {
            margin-left: auto;
            align-self: auto;
            display: flex !important;
        }

        .nav-tab-container {
            justify-content: flex-start;
            flex-wrap: nowrap;
        }

        .search-input {
            width: 200px;
        }
    }

    /* Tablet view (768px to 1024px) */
    @media (min-width: 769px) and (max-width: 1024px) {

        .staff-nav-container,
        .user-nav-container,
        .admin-nav-container {
            flex-direction: row;
            gap: 0;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .time-display-container {
            display: flex !important;
            width: 100%;
            margin-top: 0.5rem;
        }

        .nav-tab-container {
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-input {
            width: 180px;
        }
    }

    @media (max-width: 768px) {
        .time-display-container {
            display: none;
        }

        .date-display-horizontal {
            font-size: 0.8rem;
        }

        .time-display-main-horizontal {
            font-size: 0.9rem;
        }

        .user-nav-tab,
        .staff-nav-tab,
        .admin-nav-tab {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .logout-btn-1,
        .logout-btn-2,
        .logout-btn-3 {
            display: none !important;
        }

        .time-zone {
            font-size: 0.75rem;
            margin-left: 0.25rem;
            opacity: 0.9;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            display: none;
        }

        .logo-image {
            width: 50px;
            height: 50px;
        }

        .main-title {
            font-size: 1.25rem;
        }

        .search-input {
            width: 180px;
        }

        .profile-section {
            padding-left: 0.5rem;
        }

        .profile-avatar {
            height: 40px;
            width: 40px;
        }
    }

    @media (max-width: 640px) {
        .time-display-horizontal {
            flex-direction: column;
            gap: 0.2rem;
        }

        .time-separator {
            display: none;
        }

        .date-display-horizontal {
            font-size: 0.75rem;
        }

        .time-display-main-horizontal {
            font-size: 0.85rem;
        }

        .nav-tab-container {
            flex-wrap: wrap;
            justify-content: center;
        }

        .user-nav-tab,
        .staff-nav-tab,
        .admin-nav-tab {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .logo-image {
            width: 45px;
            height: 45px;
        }

        .barangay-text {
            font-size: 0.75rem;
        }

        .main-title {
            font-size: 1.1rem;
        }

        .search-input {
            width: 150px;
            font-size: 0.875rem;
        }

        .profile-info {
            display: none;
        }
    }

    @media (min-width: 769px) {
        .time-display-container {
            display: flex !important;
        }

        .nav-tab-container {
            flex-wrap: nowrap;
            justify-content: flex-start;
        }

        .profile-section {
            display: flex !important;
        }

        .logout-btn-1,
        .logout-btn-2,
        .logout-btn-3 {
            display: flex !important;
        }

        .profile-avatar {
            height: 56px;
            width: 56px;
        }

        .user-nav-section {
            display: flex !important;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: nowrap;
        }
    }

    @media (min-width: 1025px) {
        .desktop-nav-content {
            display: block !important;
        }
    }

    /* Small Mobile Adjustments (iPhone 5/SE etc) */
    @media (max-width: 400px) {
        .main-title {
            font-size: 0.9rem !important;
            white-space: normal;
            line-height: 1.2;
        }

        .barangay-text {
            font-size: 0.7rem !important;
        }

        .logo-image {
            width: 36px !important;
            height: 36px !important;
        }

        .user-nav-tab,
        .staff-nav-tab,
        .admin-nav-tab {
            padding: 0.4rem 0.8rem !important;
            font-size: 0.75rem !important;
        }

        .nav-connection {
            flex: 1 1 auto;
            display: flex;
            justify-content: center;
        }

        .nav-connection .user-nav-tab,
        .nav-connection .staff-nav-tab,
        .nav-connection .admin-nav-tab {
            width: 100%;
            text-align: center;
            white-space: nowrap;
        }
    }
</style>

<body class="bg-[#F8F8F8]">
    <?php if (isLoggedIn()): ?>
        <?php if (isAdmin()): ?>
            <!-- Admin Header - Updated to match user/staff design -->
            <nav class="bg-[#3C96E1] text-white shadow-lg sticky top-0 z-50">
                <div class="px-4 md:px-16 py-4 md:py-8 flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <!-- Barangay Toong Logo -->
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo" class="logo-image">
                        <!-- Updated Header Title with Barangay Toong text -->
                        <div class="header-title-container">
                            <div class="barangay-text mb-2">Barangay Luz</div>
                            <a href="/community-health-tracker/" class="main-title">Health Center Super-Admin Panel</a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 md:space-x-10">
                        <!-- Search Bar - Hidden on mobile -->
                        <div class="search-container hidden md:block">

                        </div>

                        <!-- Profile Section - Hidden on mobile -->
                        <div class="profile-section hidden md:flex gap-4">
                            <div class="profile-avatar <?php echo $profile_picture ? 'has-image' : ''; ?>"
                                style="<?php echo $profile_picture ? 'background-image: url(\'' . $profile_picture . '\')' : ''; ?>"
                                onclick="openProfileModal('admin')">
                                <?php if (!$profile_picture): ?>
                                    <i
                                        class="fas fa-user-circle text-2xl text-gray-400 absolute inset-0 flex items-center justify-center"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <span class="welcome-text">Welcome Super Admin!</span>
                                <span class="username-text"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                            </div>
                        </div>

                        <!-- Enhanced Logout Button - Hidden on mobile -->
                        <button type="button" onclick="showLogoutModal('admin')" class="logout-btn-3 hidden md:block">
                            <span>Signout</span>
                        </button>

                        <!-- Hamburger Menu Button - Visible only on mobile -->
                        <button type="button" onclick="toggleAdminMenu()"
                            class="md:hidden text-white hover:text-[#F0F0F0] focus:outline-none touch-target">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-[#2B7CC9] py-2 md:py-3">
                    <div
                        class="px-8 flex flex-col md:flex-row items-center justify-between gap-3 md:gap-0 admin-nav-container">
                        <!-- Admin Navigation Tabs - Fixed with proper classes -->
                        <div class="nav-tab-container flex flex-wrap justify-center md:justify-start gap-2 md:gap-4 w-full md:w-auto">
                            <div class="nav-connection">
                                <a href="../admin/dashboard.php"
                                    class="admin-nav-tab text-sm md:text-base <?= ($current_page == 'dashboard.php') ? 'admin-active' : '' ?>">
                                    Dashboard
                                </a>
                            </div>
                            <div class="nav-connection">
                                <a href="../admin/manage_accounts.php"
                                    class="admin-nav-tab text-sm md:text-base <?= ($current_page == 'manage_accounts.php') ? 'admin-active' : '' ?>">
                                    Manage Accounts
                                </a>
                            </div>
                        </div>

                        <!-- Date and Time Display - Horizontal layout on the right side -->
                        <div class="time-display-container admin-time-container w-full md:w-auto">
                            <div class="time-display-horizontal">
                                <div class="date-display-horizontal gap-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.5 3H17.25V2.25C17.25 2.05109 17.171 1.86032 17.0303 1.71967C16.8897 1.57902 16.6989 1.5 16.5 1.5C16.3011 1.5 16.1103 1.57902 15.9697 1.71967C15.829 1.86032 15.75 2.05109 15.75 2.25V3H8.25V2.25C8.25 2.05109 8.17098 1.86032 8.03033 1.71967C7.88968 1.57902 7.69891 1.5 7.5 1.5C7.30109 1.5 7.11032 1.57902 6.96967 1.71967C6.82902 1.86032 6.75 2.05109 6.75 2.25V3H4.5C4.10218 3 3.72064 3.15804 3.43934 3.43934C3.15804 3.72064 3 4.10218 3 4.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H19.5C19.8978 21 20.2794 20.842 20.5607 20.5607C20.842 20.2794 21 19.8978 21 19.5V4.5C21 4.10218 20.842 3.72064 20.5607 3.43934C20.2794 3.15804 19.8978 3 19.5 3ZM6.75 4.5V5.25C6.75 5.44891 6.82902 5.63968 6.96967 5.78033C7.11032 5.92098 7.30109 6 7.5 6C7.69891 6 7.88968 5.92098 8.03033 5.78033C8.17098 5.63968 8.25 5.44891 8.25 5.25V4.5H15.75V5.25C15.75 5.44891 15.829 5.63968 15.9697 5.78033C16.1103 5.92098 16.3011 6 16.5 6C16.6989 6 16.8897 5.92098 17.0303 5.78033C17.171 5.63968 17.25 5.44891 17.25 5.25V4.5H19.5V7.5H4.5V4.5H6.75ZM19.5 19.5H4.5V9H19.5V19.5ZM13.125 12.375C13.125 12.5975 13.059 12.815 12.9354 13C12.8118 13.185 12.6361 13.3292 12.4305 13.4144C12.225 13.4995 11.9988 13.5218 11.7805 13.4784C11.5623 13.435 11.3618 13.3278 11.2045 13.1705C11.0472 13.0132 10.94 12.8127 10.8966 12.5945C10.8532 12.3762 10.8755 12.15 10.9606 11.9445C11.0458 11.7389 11.19 11.5632 11.375 11.4396C11.56 11.316 11.7775 11.25 12 11.25C12.2984 11.25 12.5845 11.3685 12.7955 11.5795C13.0065 11.7905 13.125 12.0766 13.125 12.375ZM17.25 12.375C17.25 12.5975 17.184 12.815 17.0604 13C16.9368 13.185 16.7611 13.3292 16.5555 13.4144C16.35 13.4995 16.1238 13.5218 15.9055 13.4784C15.6873 13.435 15.4868 13.3278 15.3295 13.1705C15.1722 13.0132 15.065 12.8127 15.0216 12.5945C14.9782 12.3762 15.0005 12.15 15.0856 11.9445C15.1708 11.7389 15.315 11.5632 15.5 11.4396C15.685 11.316 15.9025 11.25 16.125 11.25C16.4234 11.25 16.7095 11.3685 16.9205 11.5795C17.1315 11.7905 17.25 12.0766 17.25 12.375ZM9 16.125C9 16.3475 8.93402 16.565 8.8104 16.75C8.68679 16.935 8.51109 17.0792 8.30552 17.1644C8.09995 17.2495 7.87375 17.2718 7.65552 17.2284C7.43729 17.185 7.23684 17.0778 7.0795 16.9205C6.92217 16.7632 6.81502 16.5627 6.77162 16.3445C6.72821 16.1262 6.75049 15.9 6.83564 15.6945C6.92078 15.4889 7.06498 15.3132 7.24998 15.1896C7.43499 15.066 7.6525 15 7.875 15C8.17337 15 8.45952 15.1185 8.6705 15.3295C8.88147 15.5405 9 15.8266 9 16.125ZM13.125 16.125C13.125 16.3475 13.059 16.565 12.9354 16.75C12.8118 16.935 12.6361 17.0792 12.4305 17.1644C12.225 17.2495 11.9988 17.2718 11.7805 17.2284C11.5623 17.185 11.3618 17.0778 11.2045 16.9205C11.0472 16.7632 10.94 16.5627 10.8966 16.3445C10.8532 16.1262 10.8755 15.9 10.9606 15.6945C11.0458 15.4889 11.19 15.3132 11.375 15.1896C11.56 15.066 11.7775 15 12 15C12.2984 15 12.5845 15.1185 12.7955 15.3295C13.0065 15.5405 13.125 15.8266 13.125 16.125ZM17.25 16.125C17.25 16.3475 17.184 16.565 17.0604 16.75C16.9368 16.935 16.7611 17.0792 16.5555 17.1644C16.35 17.2495 16.1238 17.2718 15.9055 17.2284C15.6873 17.185 15.4868 17.0778 15.3295 16.9205C15.1722 16.7632 15.065 16.5627 15.0216 16.3445C14.9782 16.1262 15.0005 15.9 15.0856 15.6945C15.1708 15.4889 15.315 15.3132 15.5 15.1896C15.685 15.066 15.9025 15 16.125 15C16.4234 15 16.7095 15.1185 16.9205 15.3295C17.1315 15.5405 17.25 15.8266 17.25 16.125Z" fill="white"/>
</svg>
                                    <span id="admin-ph-date"><?php echo date('M j, Y'); ?></span>
                                </div>
                                <div class="time-separator"></div>
                                <div class="time-display-main-horizontal">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span id="admin-ph-hours"><?php echo date('h'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="admin-ph-minutes"><?php echo date('i'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="admin-ph-seconds"><?php echo date('s'); ?></span>
                                    <span id="admin-ph-ampm" class="ml-1"><?php echo date('A'); ?></span>
                                    <span class="time-zone">PHT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

        <?php elseif (isStaff()): ?>
            <!-- Staff Header -->
             <style>
                .bg-staff-primary {
                    background-color: #9E47EC;
                }
                .bg-staff-secondary {
                    background-color: #9333EA;
                }
             </style>
            <nav class="bg-staff-primary border-b-2 text-white shadow-lg sticky top-0 z-50">
                <div class="px-4 md:px-16 py-4 md:py-8 flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <!-- Barangay Toong Logo -->
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo" class="logo-image">
                        <!-- Updated Header Title with Barangay Toong text -->
                        <div class="header-title-container">
                            <div class="barangay-text text-lg font-semibold">Barangay Luz</div>
                            <a href="/community-health-tracker/" class="main-title font-light">Health Center Admin Panel</a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 md:space-x-10">
    <div class="hidden md:flex items-center gap-4 user-nav-section">
        
        <div class="profile-avatar <?php echo $profile_picture ? 'has-image' : ''; ?>"
            style="<?php echo $profile_picture ? 'background-image: url(\'' . $profile_picture . '\')' : ''; ?>"
            onclick="openProfileModal('staff')">
        </div>

        <!-- TEXT STACKED -->
        <div class="flex flex-col">
            <span class="text-sm mt-1">Welcome Admin</span>
            <span class="font-semibold l-tight">
                <?= htmlspecialchars($_SESSION['user']['full_name']) ?>
            </span>
        </div>

    </div>

    <button type="button" onclick="showLogoutModal('staff')" class="logout-btn-2 hidden md:block">
        <span class="font-medium">Signout</span>
    </button>

    <!-- Hamburger Menu Button -->
    <button type="button" onclick="toggleStaffMenu()"
        class="md:hidden text-white hover:text-[#F0F0F0] focus:outline-none touch-target">
        <i class="fas fa-bars text-2xl"></i>
    </button>
</div>
                </div>

                <div class="bg-staff-secondary py-2 md:py-3">
                    <div class="px-8 flex flex-col md:flex-row items-center justify-between gap-3 md:gap-0 staff-nav-container">
                        <!-- Staff Navigation Tabs - Fixed with proper classes -->
                        <div class="nav-tab-container flex flex-wrap justify-center md:justify-start gap-2 md:gap-4 w-full md:w-auto">
                            <div class="nav-connection">
                                <a href="/community-health-tracker/staff/dashboard.php"
                                    class="staff-nav-tab text-sm md:text-base <?= ($current_page == 'dashboard.php') ? 'staff-active' : '' ?>">
                                    Dashboard
                                </a>
                            </div>
                            <div class="nav-connection">
                                <a href="/community-health-tracker/staff/existing_info_patients.php"
                                    class="staff-nav-tab text-sm md:text-base <?= ($current_page == 'existing_info_patients.php') ? 'staff-active' : '' ?>">
                                    Medical Records
                                </a>
                            </div>
                            <div class="nav-connection">
                                <a href="/community-health-tracker/staff/announcements.php"
                                    class="staff-nav-tab text-sm md:text-base <?= ($current_page == 'announcements.php') ? 'staff-active' : '' ?>">
                                    Announcements
                                </a>
                            </div>
                        </div>

                        <!-- Date and Time Display - Horizontal layout on the right side -->
                        <div class="time-display-container staff-time-container w-full md:w-auto">
                            <div class="time-display-horizontal">
                                <div class="date-display-horizontal gap-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.5 3H17.25V2.25C17.25 2.05109 17.171 1.86032 17.0303 1.71967C16.8897 1.57902 16.6989 1.5 16.5 1.5C16.3011 1.5 16.1103 1.57902 15.9697 1.71967C15.829 1.86032 15.75 2.05109 15.75 2.25V3H8.25V2.25C8.25 2.05109 8.17098 1.86032 8.03033 1.71967C7.88968 1.57902 7.69891 1.5 7.5 1.5C7.30109 1.5 7.11032 1.57902 6.96967 1.71967C6.82902 1.86032 6.75 2.05109 6.75 2.25V3H4.5C4.10218 3 3.72064 3.15804 3.43934 3.43934C3.15804 3.72064 3 4.10218 3 4.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H19.5C19.8978 21 20.2794 20.842 20.5607 20.5607C20.842 20.2794 21 19.8978 21 19.5V4.5C21 4.10218 20.842 3.72064 20.5607 3.43934C20.2794 3.15804 19.8978 3 19.5 3ZM6.75 4.5V5.25C6.75 5.44891 6.82902 5.63968 6.96967 5.78033C7.11032 5.92098 7.30109 6 7.5 6C7.69891 6 7.88968 5.92098 8.03033 5.78033C8.17098 5.63968 8.25 5.44891 8.25 5.25V4.5H15.75V5.25C15.75 5.44891 15.829 5.63968 15.9697 5.78033C16.1103 5.92098 16.3011 6 16.5 6C16.6989 6 16.8897 5.92098 17.0303 5.78033C17.171 5.63968 17.25 5.44891 17.25 5.25V4.5H19.5V7.5H4.5V4.5H6.75ZM19.5 19.5H4.5V9H19.5V19.5ZM13.125 12.375C13.125 12.5975 13.059 12.815 12.9354 13C12.8118 13.185 12.6361 13.3292 12.4305 13.4144C12.225 13.4995 11.9988 13.5218 11.7805 13.4784C11.5623 13.435 11.3618 13.3278 11.2045 13.1705C11.0472 13.0132 10.94 12.8127 10.8966 12.5945C10.8532 12.3762 10.8755 12.15 10.9606 11.9445C11.0458 11.7389 11.19 11.5632 11.375 11.4396C11.56 11.316 11.7775 11.25 12 11.25C12.2984 11.25 12.5845 11.3685 12.7955 11.5795C13.0065 11.7905 13.125 12.0766 13.125 12.375ZM17.25 12.375C17.25 12.5975 17.184 12.815 17.0604 13C16.9368 13.185 16.7611 13.3292 16.5555 13.4144C16.35 13.4995 16.1238 13.5218 15.9055 13.4784C15.6873 13.435 15.4868 13.3278 15.3295 13.1705C15.1722 13.0132 15.065 12.8127 15.0216 12.5945C14.9782 12.3762 15.0005 12.15 15.0856 11.9445C15.1708 11.7389 15.315 11.5632 15.5 11.4396C15.685 11.316 15.9025 11.25 16.125 11.25C16.4234 11.25 16.7095 11.3685 16.9205 11.5795C17.1315 11.7905 17.25 12.0766 17.25 12.375ZM9 16.125C9 16.3475 8.93402 16.565 8.8104 16.75C8.68679 16.935 8.51109 17.0792 8.30552 17.1644C8.09995 17.2495 7.87375 17.2718 7.65552 17.2284C7.43729 17.185 7.23684 17.0778 7.0795 16.9205C6.92217 16.7632 6.81502 16.5627 6.77162 16.3445C6.72821 16.1262 6.75049 15.9 6.83564 15.6945C6.92078 15.4889 7.06498 15.3132 7.24998 15.1896C7.43499 15.066 7.6525 15 7.875 15C8.17337 15 8.45952 15.1185 8.6705 15.3295C8.88147 15.5405 9 15.8266 9 16.125ZM13.125 16.125C13.125 16.3475 13.059 16.565 12.9354 16.75C12.8118 16.935 12.6361 17.0792 12.4305 17.1644C12.225 17.2495 11.9988 17.2718 11.7805 17.2284C11.5623 17.185 11.3618 17.0778 11.2045 16.9205C11.0472 16.7632 10.94 16.5627 10.8966 16.3445C10.8532 16.1262 10.8755 15.9 10.9606 15.6945C11.0458 15.4889 11.19 15.3132 11.375 15.1896C11.56 15.066 11.7775 15 12 15C12.2984 15 12.5845 15.1185 12.7955 15.3295C13.0065 15.5405 13.125 15.8266 13.125 16.125ZM17.25 16.125C17.25 16.3475 17.184 16.565 17.0604 16.75C16.9368 16.935 16.7611 17.0792 16.5555 17.1644C16.35 17.2495 16.1238 17.2718 15.9055 17.2284C15.6873 17.185 15.4868 17.0778 15.3295 16.9205C15.1722 16.7632 15.065 16.5627 15.0216 16.3445C14.9782 16.1262 15.0005 15.9 15.0856 15.6945C15.1708 15.4889 15.315 15.3132 15.5 15.1896C15.685 15.066 15.9025 15 16.125 15C16.4234 15 16.7095 15.1185 16.9205 15.3295C17.1315 15.5405 17.25 15.8266 17.25 16.125Z" fill="white"/>
</svg>

                                    <span id="staff-ph-date"><?php echo date('M j, Y'); ?></span>
                                </div>
                                <div class="time-separator"></div>
                                <div class="time-display-main-horizontal">
                                    <svg class="h-6 w-6 mr-2" viewBox="0 0 16 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M7.99984 15.3346C3.94984 15.3346 0.666504 12.0513 0.666504 8.0013C0.666504 3.9513 3.94984 0.667969 7.99984 0.667969C12.0498 0.667969 15.3332 3.9513 15.3332 8.0013C15.3332 12.0513 12.0498 15.3346 7.99984 15.3346ZM8.6665 3.66797H7.33317V8.2773L9.99984 10.944L10.9425 10.0013L8.6665 7.7253V3.66797Z"
                                            fill="white" />
                                    </svg>
                                    <span id="staff-ph-hours"><?php echo date('h'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="staff-ph-minutes"><?php echo date('i'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="staff-ph-seconds"><?php echo date('s'); ?></span>
                                    <span id="staff-ph-ampm" class="ml-1"><?php echo date('A'); ?></span>
                                    <span class="time-zone">PHT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

        <?php elseif (isUser()): ?>
            <!-- User Header -->
            <nav class="bg-[#3C96E1] text-white shadow-lg sticky top-0 z-50">
                <div class="px-4 md:px-16 py-4 md:py-8 flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <!-- Barangay Toong Logo -->
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo" class="logo-image">
                        <!-- Updated Header Title with Barangay Toong text -->
                        <div class="header-title-container">
                            <div class="barangay-text text-lg font-semibold">Barangay Luz</div>
                            <a href="/community-health-tracker/" class="main-title font-light">Resident Consultation Portal</a>
                        </div>
                    </div>

                    <!-- Desktop Nav Content - Hidden on mobile and tablet -->
                    <div class="hidden lg:block desktop-nav-content">
                        <ul class="flex flex-row space-x-12 font-light">
                            <li class="hover:text-[#F0F0F0] cursor-pointer">Terms & Conditions</li>
                            <li class="hover:text-[#F0F0F0] cursor-pointer">Contact Us</li>
                            <li class="hover:text-[#F0F0F0] cursor-pointer">Frequently Asked Questions</li>
                        </ul>
                    </div>

                    <div class="flex items-center space-x-4 md:space-x-10">
                        <div class="hidden md:flex items-center gap-4 user-nav-section">
                            <div class="profile-avatar <?php echo $profile_picture ? 'has-image' : ''; ?>"
                                style="<?php echo $profile_picture ? 'background-image: url(\'' . $profile_picture . '\')' : ''; ?>"
                                onclick="openProfileModal('user')">

                            </div>
                            <div class="flex flex-col">
                            <span class="text-sm mt-1">Welcome Resident</span>
                            <span class="font-medium"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                            </div>
                        </div>
                        <!-- Enhanced Logout Button - Hidden on mobile -->
                        <!-- Enhanced Logout Button - Hidden on mobile -->
<!-- Enhanced Logout Button - Hidden on mobile -->
<button type="button" onclick="showLogoutModal('user')" class="logout-btn-1" style="color: #3C96E1 !important;">
    <span style="color: #3C96E1 !important;">Signout</span>
</button>

                        <!-- Hamburger Menu Button - Visible on mobile and tablet -->
                        <button type="button" onclick="toggleUserMenu()"
                            class="lg:hidden text-white hover:text-[#F0F0F0] focus:outline-none touch-target">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-[#2B7CC9] py-2 md:py-3">
                    <div
                        class="px-4 md:px-16 flex flex-col md:flex-row items-center justify-between gap-3 md:gap-0 user-nav-container">
                        <!-- User Navigation Tabs - Fixed with proper classes -->
                        <div class="nav-tab-container flex flex-wrap justify-center md:justify-start gap-2 md:gap-4 w-full md:w-auto">
                            <div class="nav-connection">
                                <a href="dashboard.php"
                                    class="user-nav-tab text-sm md:text-base <?= ($current_page == 'dashboard.php') ? 'user-active' : '' ?>">
                                    Dashboard
                                </a>
                            </div>
                            <div class="nav-connection">
                                <a href="health_records.php"
                                    class="user-nav-tab text-sm md:text-base <?= ($current_page == 'health_records.php') ? 'user-active' : '' ?>">
                                    My Record
                                </a>
                            </div>
                            <div class="nav-connection">
                                <a href="announcements.php"
                                    class="user-nav-tab text-sm md:text-base <?= ($current_page == 'announcements.php') ? 'user-active' : '' ?>">
                                    Announcements
                                </a>
                            </div>
                        </div>

                        <!-- Date and Time Display - Horizontal layout on the right side -->
                        <div class="time-display-container user-time-container w-full md:w-auto">
                            <div class="time-display-horizontal">
                                <div class="date-display-horizontal gap-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.5 3H17.25V2.25C17.25 2.05109 17.171 1.86032 17.0303 1.71967C16.8897 1.57902 16.6989 1.5 16.5 1.5C16.3011 1.5 16.1103 1.57902 15.9697 1.71967C15.829 1.86032 15.75 2.05109 15.75 2.25V3H8.25V2.25C8.25 2.05109 8.17098 1.86032 8.03033 1.71967C7.88968 1.57902 7.69891 1.5 7.5 1.5C7.30109 1.5 7.11032 1.57902 6.96967 1.71967C6.82902 1.86032 6.75 2.05109 6.75 2.25V3H4.5C4.10218 3 3.72064 3.15804 3.43934 3.43934C3.15804 3.72064 3 4.10218 3 4.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H19.5C19.8978 21 20.2794 20.842 20.5607 20.5607C20.842 20.2794 21 19.8978 21 19.5V4.5C21 4.10218 20.842 3.72064 20.5607 3.43934C20.2794 3.15804 19.8978 3 19.5 3ZM6.75 4.5V5.25C6.75 5.44891 6.82902 5.63968 6.96967 5.78033C7.11032 5.92098 7.30109 6 7.5 6C7.69891 6 7.88968 5.92098 8.03033 5.78033C8.17098 5.63968 8.25 5.44891 8.25 5.25V4.5H15.75V5.25C15.75 5.44891 15.829 5.63968 15.9697 5.78033C16.1103 5.92098 16.3011 6 16.5 6C16.6989 6 16.8897 5.92098 17.0303 5.78033C17.171 5.63968 17.25 5.44891 17.25 5.25V4.5H19.5V7.5H4.5V4.5H6.75ZM19.5 19.5H4.5V9H19.5V19.5ZM13.125 12.375C13.125 12.5975 13.059 12.815 12.9354 13C12.8118 13.185 12.6361 13.3292 12.4305 13.4144C12.225 13.4995 11.9988 13.5218 11.7805 13.4784C11.5623 13.435 11.3618 13.3278 11.2045 13.1705C11.0472 13.0132 10.94 12.8127 10.8966 12.5945C10.8532 12.3762 10.8755 12.15 10.9606 11.9445C11.0458 11.7389 11.19 11.5632 11.375 11.4396C11.56 11.316 11.7775 11.25 12 11.25C12.2984 11.25 12.5845 11.3685 12.7955 11.5795C13.0065 11.7905 13.125 12.0766 13.125 12.375ZM17.25 12.375C17.25 12.5975 17.184 12.815 17.0604 13C16.9368 13.185 16.7611 13.3292 16.5555 13.4144C16.35 13.4995 16.1238 13.5218 15.9055 13.4784C15.6873 13.435 15.4868 13.3278 15.3295 13.1705C15.1722 13.0132 15.065 12.8127 15.0216 12.5945C14.9782 12.3762 15.0005 12.15 15.0856 11.9445C15.1708 11.7389 15.315 11.5632 15.5 11.4396C15.685 11.316 15.9025 11.25 16.125 11.25C16.4234 11.25 16.7095 11.3685 16.9205 11.5795C17.1315 11.7905 17.25 12.0766 17.25 12.375ZM9 16.125C9 16.3475 8.93402 16.565 8.8104 16.75C8.68679 16.935 8.51109 17.0792 8.30552 17.1644C8.09995 17.2495 7.87375 17.2718 7.65552 17.2284C7.43729 17.185 7.23684 17.0778 7.0795 16.9205C6.92217 16.7632 6.81502 16.5627 6.77162 16.3445C6.72821 16.1262 6.75049 15.9 6.83564 15.6945C6.92078 15.4889 7.06498 15.3132 7.24998 15.1896C7.43499 15.066 7.6525 15 7.875 15C8.17337 15 8.45952 15.1185 8.6705 15.3295C8.88147 15.5405 9 15.8266 9 16.125ZM13.125 16.125C13.125 16.3475 13.059 16.565 12.9354 16.75C12.8118 16.935 12.6361 17.0792 12.4305 17.1644C12.225 17.2495 11.9988 17.2718 11.7805 17.2284C11.5623 17.185 11.3618 17.0778 11.2045 16.9205C11.0472 16.7632 10.94 16.5627 10.8966 16.3445C10.8532 16.1262 10.8755 15.9 10.9606 15.6945C11.0458 15.4889 11.19 15.3132 11.375 15.1896C11.56 15.066 11.7775 15 12 15C12.2984 15 12.5845 15.1185 12.7955 15.3295C13.0065 15.5405 13.125 15.8266 13.125 16.125ZM17.25 16.125C17.25 16.3475 17.184 16.565 17.0604 16.75C16.9368 16.935 16.7611 17.0792 16.5555 17.1644C16.35 17.2495 16.1238 17.2718 15.9055 17.2284C15.6873 17.185 15.4868 17.0778 15.3295 16.9205C15.1722 16.7632 15.065 16.5627 15.0216 16.3445C14.9782 16.1262 15.0005 15.9 15.0856 15.6945C15.1708 15.4889 15.315 15.3132 15.5 15.1896C15.685 15.066 15.9025 15 16.125 15C16.4234 15 16.7095 15.1185 16.9205 15.3295C17.1315 15.5405 17.25 15.8266 17.25 16.125Z" fill="white"/>
</svg>
                                    <span id="ph-date"><?php echo date('M j, Y'); ?></span>
                                </div>
                                <div class="time-separator"></div>
                                <div class="time-display-main-horizontal">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span id="ph-hours"><?php echo date('h'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="ph-minutes"><?php echo date('i'); ?></span>
                                    <span class="blinking-colon">:</span>
                                    <span id="ph-seconds"><?php echo date('s'); ?></span>
                                    <span id="ph-ampm" class="ml-1"><?php echo date('A'); ?></span>
                                    <span class="time-zone">PHT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User Mobile Menu Modal -->
            <div id="userMobileMenu"
                class="fixed inset-0 hidden z-[60] h-full w-full bg-white flex-col items-center justify-center">
                <div
                    class="relative w-full h-full p-8 flex flex-col items-center justify-center modal-content user-mobile-menu-content">
                    <!-- Close Button -->
                    <button onclick="closeUserMenu()"
                        class="modal-close-btn absolute top-6 right-6 text-black hover:text-gray-600 z-10 transition-transform transform hover:rotate-90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Logo Section -->
                    <div class="flex flex-col items-center mb-8">
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo" class="w-24 h-24 mb-4 object-cover">
                        <div class="text-center">
                            <h2 class="text-lg font-bold text-gray-900">Barangay Luz</h2>
                            <p class="text-sm font-medium text-gray-900 mt-1">Resident Consultation Portal</p>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="w-full max-w-xs h-px bg-gray-200 mb-8"></div>

                    <!-- Mobile Menu Items - Centered with pill buttons -->
                    <div class="flex flex-col items-center space-y-6 mb-12 w-full max-w-xs">
                        <button
                            class="w-full py-3 bg-[#CFE2F3] text-[#3C96E1] rounded-full hover:bg-blue-200 transition font-medium text-sm border-none shadow-sm">
                            Terms & Conditions
                        </button>
                        <button
                            class="w-full py-3 bg-[#CFE2F3] text-[#3C96E1] rounded-full hover:bg-blue-200 transition font-medium text-sm border-none shadow-sm">
                            Contact Us
                        </button>
                        <button
                            class="w-full py-3 bg-[#CFE2F3] text-[#3C96E1] rounded-full hover:bg-blue-200 transition font-medium text-sm border-none shadow-sm">
                            Frequently Asked Questions
                        </button>
                    </div>

                    <!-- Logout Button -->
                    <div class="flex justify-center w-full max-w-xs">
                        <button type="button" onclick="showLogoutModal('user'); closeUserMenu();"
                            class="w-48 py-3 bg-[#E03E3E] text-white rounded-full hover:bg-red-700 transition font-medium shadow-md">
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Admin Mobile Menu Modal -->
            <div id="adminMobileMenu"
                class="fixed inset-0 hidden z-[60] h-full w-full backdrop-blur-sm bg-black/30 justify-center items-start pt-8">
                <div
                    class="relative bg-white p-8 rounded-lg shadow-lg w-full max-w-md mx-4 modal-content admin-mobile-menu-content">
                    <!-- Close Button -->
                    <button onclick="closeAdminMenu()"
                        class="modal-close-btn absolute top-4 right-4 text-gray-600 hover:text-gray-800 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Logo Section -->
                    <div class="flex justify-center mb-6">
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo"
                            class="w-20 h-20 rounded-full object-cover border-4 border-[#3C96E1] shadow-lg">
                    </div>

                    <!-- Branding -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Barangay Luz</h2>
                        <p class="text-sm text-gray-600 mt-1">Health Center Admin Panel</p>
                    </div>

                    <!-- Divider -->
                    <div class="w-full h-px bg-gray-200 mb-6"></div>

                    <!-- Admin Profile Section -->
                    <div class="text-center mb-6 pb-6 border-b border-gray-200">
                        <div class="profile-avatar <?php echo $profile_picture ? 'has-image' : ''; ?> mx-auto mb-3"
                            style="<?php echo $profile_picture ? 'background-image: url(\'' . $profile_picture . '\')' : ''; ?>; width: 60px; height: 60px;"
                            onclick="openProfileModal('admin'); closeAdminMenu();">
                            <?php if (!$profile_picture): ?>
                                <i
                                    class="fas fa-user-circle text-3xl text-gray-400 absolute inset-0 flex items-center justify-center"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-800 font-medium"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></p>
                        <p class="text-xs text-gray-500">Super Admin</p>
                    </div>

                    <!-- Logout Button -->
                    <div class="flex justify-center">
                        <button type="button" onclick="showLogoutModal('admin'); closeAdminMenu();"
                            class="px-8 py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition font-medium">
                            Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Staff Mobile Menu Modal -->
            <div id="staffMobileMenu"
                class="fixed inset-0 hidden z-[60] h-full w-full backdrop-blur-sm bg-black/30 justify-center items-start pt-8">
                <div
                    class="relative bg-white p-8 rounded-lg shadow-lg w-full max-w-md mx-4 modal-content staff-mobile-menu-content">
                    <!-- Close Button -->
                    <button onclick="closeStaffMenu()"
                        class="modal-close-btn absolute top-4 right-4 text-gray-600 hover:text-gray-800 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Logo Section -->
                    <div class="flex justify-center mb-6">
                        <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo"
                            class="w-20 h-20 rounded-full object-cover border-4 border-[#3C96E1] shadow-lg">
                    </div>

                    <!-- Branding -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Barangay Luz</h2>
                        <p class="text-sm text-gray-600 mt-1">Health Center Staff Panel</p>
                    </div>

                    <!-- Divider -->
                    <div class="w-full h-px bg-gray-200 mb-6"></div>

                    <!-- Staff Profile Section -->
                    <div class="text-center mb-6 pb-6 border-b border-gray-200">
                        <div class="profile-avatar <?php echo $profile_picture ? 'has-image' : ''; ?> mx-auto mb-3"
                            style="<?php echo $profile_picture ? 'background-image: url(\'' . $profile_picture . '\')' : ''; ?>; width: 60px; height: 60px;"
                            onclick="openProfileModal('staff'); closeStaffMenu();">
                            <?php if (!$profile_picture): ?>
                                <i
                                    class="fas fa-user-circle text-3xl text-white absolute inset-0 flex items-center justify-center"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-800 font-medium"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></p>
                        <p class="text-xs text-gray-500">Health Staff</p>
                    </div>

                    <!-- Logout Button -->
                    <div class="flex justify-center">
                        <button type="button" onclick="showLogoutModal('staff'); closeStaffMenu();"
                            class="px-8 py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition font-medium">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Public Header (Not logged in) -->
        <style>
            .mobile-menu {
                display: none;
            }

            .mobile-menu.active {
                display: block;
            }

            .touch-target {
                position: relative;
            }

            .touch-target::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
            }

            .circle-image {
                width: 65px;
                height: 65px;
                border-radius: 50%;
                object-fit: cover;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .nav-container {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            .logo-text {
                line-height: 1.2;
            }

            .nav-link {
                font-size: 1.1rem;
                padding: 0.5rem 1rem;
            }
        </style>
        </head>

        <body>


            <style>
                /* Mobile menu styles */
                .mobile-menu {
                    transition: all 0.3s ease;
                    max-height: 0;
                    overflow: hidden;
                }

                .mobile-menu-open {
                    max-height: 1000px;
                }

                /* Better touch targets for mobile */
                .touch-target {
                    min-height: 48px;
                    min-width: 48px;
                }
            </style>

            <script>
                // Mobile menu toggle
                function toggleMobileMenu() {
                    const mobileMenu = document.getElementById('mobile-menu');
                    mobileMenu.classList.toggle('mobile-menu-open');
                }

                // Close mobile menu when clicking outside
                document.addEventListener('click', function (event) {
                    const mobileMenu = document.getElementById('mobile-menu');
                    const mobileMenuButton = document.querySelector('.md\\:hidden.touch-target');

                    if (mobileMenu && mobileMenuButton &&
                        !mobileMenu.contains(event.target) &&
                        !mobileMenuButton.contains(event.target) &&
                        mobileMenu.classList.contains('mobile-menu-open')) {
                        mobileMenu.classList.remove('mobile-menu-open');
                    }
                });
            </script>

            <!-- Login Modal Only -->
            <div id="loginModal"
                class="fixed inset-0 hidden z-50 h-full w-full backdrop-blur-sm bg-black/30 justify-center items-center">
                <div
                    class="relative bg-white p-4 sm:p-8 rounded-lg shadow-lg w-full max-w-xl mx-auto max-h-xl overflow-y-auto modal-content">
                    <!-- Close Button -->
                    <button onclick="closeModal()"
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
                        <h1 class="text-2xl font-semibold text-[#4A90E2]">Barangay Luz Cebu City</h1>
                    </div>

                    <!-- Instruction Text -->
                    <div class="flex flex-col items-center mb-8 mx-4">
                        <p class="text-sm text-center text-gray-600 max-w-md leading-relaxed">
                            Please log in with your authorized account to access health records, appointments, and other
                            health services.
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form method="POST" action="auth/login.php" class="space-y-6">
                        <input type="hidden" name="role" value="user">
                        <div class="space-y-6 mx-4">
                            <!-- Username -->
                            <div>
                                <label for="login-username" class="block text-sm font-medium text-gray-700 mb-2">Username
                                    <span class="text-red-500">*</span></label>
                                <input type="text" name="username" id="login-username" placeholder="Enter Username"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3C96E1] form-input"
                                    required />
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="login-password" class="block text-sm font-medium text-gray-700 mb-2">Password
                                    <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="login-password" name="password" type="password" placeholder="Password"
                                        class="w-full p-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3C96E1] form-input"
                                        required />
                                    <button type="button" onclick="toggleLoginPassword()"
                                        class="absolute top-1/2 right-3 transform -translate-y-1/2 text-gray-500">
                                        <i id="login-eyeIcon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>



                            <!-- Login Button -->
                            <div class="mt-8">
                                <button type="submit"
                                    class="complete-btn bg-[#3C96E1] w-full p-3 rounded-md text-white transition-all duration-200 font-medium shadow-md hover:shadow-lg text-lg h-14">
                                    Login
                                </button>
                            </div>

                            <!-- Registration Notice -->
                            <div class="text-center text-sm text-gray-600 mt-6">
                                <p>New residents need to register at the Barangay Health Center to obtain login credentials.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>

        <!-- Profile Picture Upload Modal -->
        <div id="profileModal"
            class="fixed inset-0 hidden z-[70] h-full w-full backdrop-blur-sm bg-black/30 justify-center items-center">
            <div
                class="relative bg-white p-6 sm:p-8 rounded-lg shadow-lg w-full max-w-md mx-auto modal-content profile-modal-content">
                <!-- Close Button -->
                <button onclick="closeProfileModal()"
                    class="modal-close-btn absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Modal Title -->
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-medium text-gray-800">Profile Picture</h3>
                    <p class="text-gray-500 mt-2 text-sm">Upload or change your profile picture</p>
                </div>

                <!-- Current Profile Picture Preview -->
                <div class="mb-6 flex flex-col items-center">
                    <img id="profilePreview"
                        src="<?php echo $profile_picture ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxjaXJjbGUgY3g9IjEyIiBjeT0iMTIiIHI9IjEyIiBmaWxsPSIjZDFkNWRiIi8+PHBhdGggZD0iTTEyIDExYTIgMiAwIDEgMCAwLTQgMiAyIDAgMCAwIDAgNHoiIGZpbGw9IiM5Y2EzYWYiLz48cGF0aCBkPSJNMTIgMTVhNCA0IDAgMCAwLTQgNGg4YTQgNCAwIDAgMC00LTR6IiBmaWxsPSIjOWNhM2FmIi8+PC9zdmc+'; ?>" />
                    </div>

                    <!-- Upload Form -->
                    <form id="profileUploadForm" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="user_id"
                            value="<?php echo isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : '' ?>">
                        <input type="hidden" name="user_type" id="profileUserType" value="">

                        <div>
                            <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                                Choose Profile Picture
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.gif"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3C96E1] text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-2">Max file size: 2MB (JPEG, PNG, GIF)</p>
                            <div id="profileUploadError" class="text-xs text-red-500 mt-2 hidden"></div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 mt-6" style="gap: 10px;">
                            <button type="button" onclick="removeProfilePicture()"
                                class="profile-remove-btn <?php echo !$profile_picture ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                <?php echo !$profile_picture ? 'disabled' : '' ?>>
                                <i class="fas fa-trash-alt"></i>
                                Remove
                            </button>
                            <button type="submit" class="profile-upload-btn">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M22.5 12.7506V18.7506C22.5 19.1484 22.342 19.5299 22.0607 19.8112C21.7794 20.0926 21.3978 20.2506 21 20.2506H3C2.60218 20.2506 2.22064 20.0926 1.93934 19.8112C1.65804 19.5299 1.5 19.1484 1.5 18.7506V12.7506C1.5 12.3528 1.65804 11.9712 1.93934 11.6899C2.22064 11.4086 2.60218 11.2506 3 11.2506H7.5C7.69891 11.2506 7.88968 11.3296 8.03033 11.4703C8.17098 11.6109 8.25 11.8017 8.25 12.0006C8.25 12.1995 8.17098 12.3903 8.03033 12.5309C7.88968 12.6716 7.69891 12.7506 7.5 12.7506H3V18.7506H21V12.7506H16.5C16.3011 12.7506 16.1103 12.6716 15.9697 12.5309C15.829 12.3903 15.75 12.1995 15.75 12.0006C15.75 11.8017 15.829 11.6109 15.9697 11.4703C16.1103 11.3296 16.3011 11.2506 16.5 11.2506H21C21.3978 11.2506 21.7794 11.4086 22.0607 11.6899C22.342 11.9712 22.5 12.3528 22.5 12.7506ZM8.03063 7.28122L11.25 4.0609V12.0006C11.25 12.1995 11.329 12.3903 11.4697 12.5309C11.6103 12.6716 11.8011 12.7506 12 12.7506C12.1989 12.7506 12.3897 12.6716 12.5303 12.5309C12.671 12.3903 12.75 12.1995 12.75 12.0006V4.0609L15.9694 7.28122C16.1101 7.42195 16.301 7.50101 16.5 7.50101C16.699 7.50101 16.8899 7.42195 17.0306 7.28122C17.1714 7.14048 17.2504 6.94961 17.2504 6.75059C17.2504 6.55157 17.1714 6.3607 17.0306 6.21996L12.5306 1.71997C12.461 1.65023 12.3783 1.59491 12.2872 1.55717C12.1962 1.51943 12.0986 1.5 12 1.5C11.9014 1.5 11.8038 1.51943 11.7128 1.55717C11.6217 1.59491 11.539 1.65023 11.4694 1.71997L6.96937 6.21996C6.82864 6.3607 6.74958 6.55157 6.74958 6.75059C6.74958 6.94961 6.82864 7.14048 6.96938 7.28121C7.11011 7.42195 7.30098 7.50101 7.5 7.50101C7.69902 7.50101 7.88989 7.42195 8.03063 7.28122ZM18.75 15.7506C18.75 15.5281 18.684 15.3106 18.5604 15.1256C18.4368 14.9406 18.2611 14.7964 18.0555 14.7112C17.85 14.6261 17.6238 14.6038 17.4055 14.6472C17.1873 14.6906 16.9868 14.7978 16.8295 14.9551C16.6722 15.1124 16.565 15.3129 16.5216 15.5311C16.4782 15.7493 16.5005 15.9755 16.5856 16.1811C16.6708 16.3867 16.815 16.5624 17 16.686C17.185 16.8096 17.4025 16.8756 17.625 16.8756C17.9234 16.8756 18.2095 16.7571 18.4205 16.5461C18.6315 16.3351 18.75 16.049 18.75 15.7506Z" fill="white"/>
</svg>

                                Upload
                            </button>
                        </div>
                    </form>

                    <!-- Loading Indicator -->
                    <div id="profileLoading" class="hidden mt-4 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#3C96E1]"></div>
                        <p class="text-sm text-gray-600 mt-2">Uploading...</p>
                    </div>
                </div>
            </div>

            <!-- Logout Confirmation Modal -->
            <div id="logoutModal"
                class="fixed inset-0 hidden z-[60] h-full w-full backdrop-blur-sm bg-black/30 justify-center items-center">
                <div
                    class="relative bg-white p-6 sm:p-8 rounded-lg shadow-lg w-full max-w-md mx-auto modal-content logout-modal-content">
                    <!-- Close Button -->
                    <button onclick="closeLogoutModal()"
                        class="modal-close-btn absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Warning Icon -->
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Modal Title -->
                    <div class="text-center mb-2">
                        <h3 class="text-xl font-bold text-gray-800">Confirm Logout</h3>
                    </div>

                    <!-- Modal Message -->
                    <div class="text-center mb-6">
                        <p class="text-gray-600">Are you sure you want to logout?</p>
                        <p class="text-sm text-gray-500 mt-1">You will need to log in again to access your account.</p>
                    </div>

                    <!-- Modal Buttons -->
                    <div class="logout-modal-buttons">
                        <button type="button" onclick="closeLogoutModal()" class="logout-cancel-btn">
                            Cancel
                        </button>
                        <button type="button" id="confirmLogoutBtn" class="logout-confirm-btn">
                            Yes, Logout
                        </button>
                    </div>
                </div>
            </div>

            <main class="container mx-auto"> <!-- Added mt-24 to account for the fixed header height -->
                <!-- Your main content here -->
            </main>

            <!-- Hidden refresh indicator -->
            <div id="refreshIndicator" class="refresh-indicator"></div>

            <script>
                // Global variable to store logout URL
                let logoutUrl = '';

                // Function to open profile picture modal
                function openProfileModal(userType) {
                    const modal = document.getElementById("profileModal");
                    const modalContent = modal.querySelector('.modal-content');
                    const userTypeInput = document.getElementById('profileUserType');
                    const profilePreview = document.getElementById('profilePreview');

                    // Set user type for form submission
                    userTypeInput.value = userType;

                    // Refresh the profile preview image with cache-buster to ensure fresh display
                    const currentSrc = profilePreview.src;
                    if (currentSrc && !currentSrc.includes('data:image/svg')) {
                        // Add timestamp to force refresh from server
                        const baseSrc = currentSrc.split('?')[0];
                        profilePreview.src = baseSrc + '?t=' + Date.now();
                    }

                    modal.classList.remove("hidden");
                    modal.classList.add("flex");

                    // Trigger animation
                    setTimeout(() => {
                        modalContent.classList.add('open');
                    }, 10);

                    // Set focus to upload button for accessibility
                    setTimeout(() => {
                        document.querySelector('.profile-upload-btn').focus();
                    }, 50);
                }

                // Function to close profile modal
                function closeProfileModal() {
                    const modal = document.getElementById("profileModal");
                    const modalContent = modal.querySelector('.modal-content');

                    modalContent.classList.remove('open');

                    // Wait for animation to complete before hiding
                    setTimeout(() => {
                        modal.classList.remove("flex");
                        modal.classList.add("hidden");
                    }, 300);
                }

                // Function to show logout confirmation modal
                function showLogoutModal(userType) {
                    // Set the logout URL based on user type
                    switch (userType) {
                        case 'admin':
                            logoutUrl = '../auth/logout.php';
                            break;
                        case 'staff':
                            logoutUrl = '/community-health-tracker/auth/logout.php';
                            break;
                        case 'user':
                            logoutUrl = '../auth/logout_user.php';
                            break;
                        default:
                            logoutUrl = '../auth/logout.php';
                    }

                    const modal = document.getElementById("logoutModal");
                    const modalContent = modal.querySelector('.modal-content');

                    modal.classList.remove("hidden");
                    modal.classList.add("flex");

                    // Trigger animation
                    setTimeout(() => {
                        modalContent.classList.add('open');
                    }, 10);

                    // Set focus to cancel button for accessibility
                    setTimeout(() => {
                        document.querySelector('.logout-cancel-btn').focus();
                    }, 50);
                }

                // Function to close logout modal
                function closeLogoutModal() {
                    const modal = document.getElementById("logoutModal");
                    const modalContent = modal.querySelector('.modal-content');

                    modalContent.classList.remove('open');

                    // Wait for animation to complete before hiding
                    setTimeout(() => {
                        modal.classList.remove("flex");
                        modal.classList.add("hidden");
                    }, 300);
                }

                // Function to toggle user mobile menu
                function toggleUserMenu() {
                    const modal = document.getElementById("userMobileMenu");
                    if (modal && modal.classList.contains("hidden")) {
                        openUserMenu();
                    } else {
                        closeUserMenu();
                    }
                }

                // Function to open user mobile menu
                function openUserMenu() {
                    const modal = document.getElementById("userMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        modal.classList.remove("hidden");
                        modal.classList.add("flex");
                        if (modalContent) {
                            setTimeout(() => {
                                modalContent.classList.add('open');
                            }, 10);
                        }
                    }
                }

                // Function to close user mobile menu
                function closeUserMenu() {
                    const modal = document.getElementById("userMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.classList.remove('open');
                            setTimeout(() => {
                                modal.classList.add("hidden");
                                modal.classList.remove("flex");
                            }, 300);
                        } else {
                            modal.classList.add("hidden");
                            modal.classList.remove("flex");
                        }
                    }
                }

                // Function to toggle admin mobile menu
                function toggleAdminMenu() {
                    const modal = document.getElementById("adminMobileMenu");
                    if (modal && modal.classList.contains("hidden")) {
                        openAdminMenu();
                    } else {
                        closeAdminMenu();
                    }
                }

                // Function to open admin mobile menu
                function openAdminMenu() {
                    const modal = document.getElementById("adminMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        modal.classList.remove("hidden");
                        modal.classList.add("flex");
                        if (modalContent) {
                            setTimeout(() => {
                                modalContent.classList.add('open');
                            }, 10);
                        }
                    }
                }

                // Function to close admin mobile menu
                function closeAdminMenu() {
                    const modal = document.getElementById("adminMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.classList.remove('open');
                            setTimeout(() => {
                                modal.classList.add("hidden");
                                modal.classList.remove("flex");
                            }, 300);
                        } else {
                            modal.classList.add("hidden");
                            modal.classList.remove("flex");
                        }
                    }
                }

                // Function to toggle staff mobile menu
                function toggleStaffMenu() {
                    const modal = document.getElementById("staffMobileMenu");
                    if (modal && modal.classList.contains("hidden")) {
                        openStaffMenu();
                    } else {
                        closeStaffMenu();
                    }
                }

                // Function to open staff mobile menu
                function openStaffMenu() {
                    const modal = document.getElementById("staffMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        modal.classList.remove("hidden");
                        modal.classList.add("flex");
                        if (modalContent) {
                            setTimeout(() => {
                                modalContent.classList.add('open');
                            }, 10);
                        }
                    }
                }

                // Function to close staff mobile menu
                function closeStaffMenu() {
                    const modal = document.getElementById("staffMobileMenu");
                    if (modal) {
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.classList.remove('open');
                            setTimeout(() => {
                                modal.classList.add("hidden");
                                modal.classList.remove("flex");
                            }, 300);
                        } else {
                            modal.classList.add("hidden");
                            modal.classList.remove("flex");
                        }
                    }
                }

                // Close mobile menus when clicking outside
                document.addEventListener('click', function (event) {
                    // Close user menu
                    const userMobileMenu = document.getElementById("userMobileMenu");
                    const userHamburgerBtn = document.querySelector('.md\\:hidden[onclick="toggleUserMenu()"]');

                    if (userMobileMenu && userHamburgerBtn &&
                        !userMobileMenu.contains(event.target) &&
                        !userHamburgerBtn.contains(event.target) &&
                        userMobileMenu.classList.contains('flex')) {
                        closeUserMenu();
                    }

                    // Close admin menu
                    const adminMobileMenu = document.getElementById("adminMobileMenu");
                    const adminHamburgerBtn = document.querySelector('.md\\:hidden[onclick="toggleAdminMenu()"]');

                    if (adminMobileMenu && adminHamburgerBtn &&
                        !adminMobileMenu.contains(event.target) &&
                        !adminHamburgerBtn.contains(event.target) &&
                        adminMobileMenu.classList.contains('flex')) {
                        closeAdminMenu();
                    }

                    // Close staff menu
                    const staffMobileMenu = document.getElementById("staffMobileMenu");
                    const staffHamburgerBtn = document.querySelector('.md\\:hidden[onclick="toggleStaffMenu()"]');

                    if (staffMobileMenu && staffHamburgerBtn &&
                        !staffMobileMenu.contains(event.target) &&
                        !staffHamburgerBtn.contains(event.target) &&
                        staffMobileMenu.classList.contains('flex')) {
                        closeStaffMenu();
                    }
                });

                // Handle logout confirmation
                document.getElementById('confirmLogoutBtn').addEventListener('click', function () {
                    // Redirect to logout URL
                    window.location.href = logoutUrl;
                });

                // Profile picture upload functionality
                document.addEventListener('DOMContentLoaded', function () {
                    const profileUploadForm = document.getElementById('profileUploadForm');
                    const profileImageInput = document.getElementById('profile_image');
                    const profilePreview = document.getElementById('profilePreview');
                    const profileUploadError = document.getElementById('profileUploadError');
                    const profileLoading = document.getElementById('profileLoading');
                    const removeProfileBtn = document.querySelector('.profile-remove-btn');

                    // Preview image when file is selected
                    profileImageInput.addEventListener('change', function (e) {
                        const file = e.target.files[0];
                        if (file) {
                            // Validate file size (2MB)
                            if (file.size > 2 * 1024 * 1024) {
                                profileUploadError.textContent = 'File size exceeds 2MB limit.';
                                profileUploadError.classList.remove('hidden');
                                this.value = '';
                                return;
                            }

                            // Validate file type
                            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                            if (!validTypes.includes(file.type)) {
                                profileUploadError.textContent = 'Invalid file type. Please upload JPEG, PNG, or GIF images.';
                                profileUploadError.classList.remove('hidden');
                                this.value = '';
                                return;
                            }

                            // Clear any previous errors
                            profileUploadError.classList.add('hidden');

                            // Create preview
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                profilePreview.src = e.target.result;
                            }
                            reader.readAsDataURL(file);
                        }
                    });

                    // Handle form submission
                    profileUploadForm.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const formData = new FormData(this);
                        const file = profileImageInput.files[0];

                        if (!file) {
                            profileUploadError.textContent = 'Please select a file to upload.';
                            profileUploadError.classList.remove('hidden');
                            return;
                        }

                        // Show loading indicator
                        profileLoading.classList.remove('hidden');
                        profileUploadForm.classList.add('opacity-50');

                        // Submit via AJAX
                        fetch('/community-health-tracker/auth/upload_profile.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update profile picture in header
                                    updateProfilePicture(data.profile_url);

                                    // Show success message
                                    alert('Profile picture updated successfully!');
                                    // Reset file input so user can upload again
                                    profileImageInput.value = '';
                                    // Close modal
                                    closeProfileModal();
                                } else {
                                    // Show error
                                    profileUploadError.textContent = data.message || 'Upload failed. Please try again.';
                                    profileUploadError.classList.remove('hidden');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                profileUploadError.textContent = 'An error occurred. Please try again.';
                                profileUploadError.classList.remove('hidden');
                            })
                            .finally(() => {
                                // Hide loading indicator
                                profileLoading.classList.add('hidden');
                                profileUploadForm.classList.remove('opacity-50');
                            });
                    });

                    // Remove profile picture
                    window.removeProfilePicture = function () {
                        if (!confirm('Are you sure you want to remove your profile picture?')) {
                            return;
                        }

                        const formData = new FormData();
                        formData.append('user_id', document.querySelector('input[name="user_id"]').value);
                        formData.append('user_type', document.getElementById('profileUserType').value);
                        formData.append('remove', '1');

                        // Show loading indicator
                        profileLoading.classList.remove('hidden');
                        profileUploadForm.classList.add('opacity-50');

                        fetch('/community-health-tracker/auth/upload_profile.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update profile picture in header
                                    updateProfilePicture(null);

                                    // Reset preview to default
                                    profilePreview.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxjaXJjbGUgY3g9IjEyIiBjeT0iMTIiIHI9IjEyIiBmaWxsPSIjZDFkNWRiIi8+PHBhdGggZD0iTTEyIDExYTIgMiAwIDEgMCAwLTQgMiAyIDAgMCAwIDAgNHoiIGZpbGw9IiM5Y2EzYWYiLz48cGF0aCBkPSJNMTIgMTVhNCA0IDAgMCAwLTQgNGg4YTQgNCAwIDAgMC00LTR6IiBmaWxsPSIjOWNhM2FmIi8+PC9zdmc+';

                                    // Disable remove button
                                    removeProfileBtn.classList.add('opacity-50', 'cursor-not-allowed');
                                    removeProfileBtn.disabled = true;

                                    // Show success message
                                    alert('Profile picture removed successfully!');
                                } else {
                                    alert(data.message || 'Failed to remove profile picture.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred. Please try again.');
                            })
                            .finally(() => {
                                // Hide loading indicator
                                profileLoading.classList.add('hidden');
                                profileUploadForm.classList.remove('opacity-50');
                            });
                    };

                    // Function to update profile picture in header
                    function updateProfilePicture(imageUrl) {
                        const profileAvatars = document.querySelectorAll('.profile-avatar');
                        // Add cache-busting using current timestamp to ensure new image loads
                        const cacheBustedUrl = imageUrl ? imageUrl + (imageUrl.includes('?') ? '&' : '?') + 't=' + Date.now() : null;
                        profileAvatars.forEach(avatar => {
                            if (cacheBustedUrl) {
                                avatar.style.backgroundImage = `url('${cacheBustedUrl}')`;
                                avatar.classList.add('has-image');
                                // Remove the icon if it exists
                                const icon = avatar.querySelector('i');
                                if (icon) {
                                    icon.remove();
                                }
                            } else {
                                avatar.style.backgroundImage = '';
                                avatar.classList.remove('has-image');
                                // Add the default icon
                                if (!avatar.querySelector('i')) {
                                    const icon = document.createElement('i');
                                    icon.className = 'fas fa-user-circle text-4xl text-white absolute inset-0 flex items-center justify-center';
                                    avatar.appendChild(icon);
                                }
                            }
                        });

                        // Also update the preview image in the modal if it exists
                        const profilePreview = document.getElementById('profilePreview');
                        if (profilePreview && cacheBustedUrl) {
                            profilePreview.src = cacheBustedUrl;
                        }
                    }
                });

                // Close modal when clicking outside
                document.getElementById('profileModal')?.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeProfileModal();
                    }
                });

                document.getElementById('logoutModal')?.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeLogoutModal();
                    }
                });

                // Close modals with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        const profileModal = document.getElementById('profileModal');
                        const logoutModal = document.getElementById('logoutModal');

                        if (!profileModal.classList.contains('hidden')) {
                            closeProfileModal();
                        } else if (!logoutModal.classList.contains('hidden')) {
                            closeLogoutModal();
                        }
                    }

                    // Handle Enter key on confirm button
                    if (e.key === 'Enter' && document.activeElement.id === 'confirmLogoutBtn') {
                        document.getElementById('confirmLogoutBtn').click();
                    }
                });

                // Function to update Philippine time in real-time
                function updatePhilippineTime() {
                    const now = new Date();

                    // Get the current time in the Philippines (UTC+8)
                    // Since we're using the server's timezone setting (Asia/Manila),
                    // we can use local time methods
                    const hours = now.getHours();
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const seconds = now.getSeconds().toString().padStart(2, '0');
                    const ampm = hours >= 12 ? 'PM' : 'AM';

                    // Convert to 12-hour format
                    let hours12 = hours % 12;
                    hours12 = hours12 ? hours12 : 12; // Convert 0 to 12
                    const hoursStr = hours12.toString().padStart(2, '0');

                    // Format date
                    const options = { month: 'short', day: 'numeric', year: 'numeric' };
                    const dateStr = now.toLocaleDateString('en-US', options);

                    // Update the elements for user
                    if (document.getElementById('ph-date')) {
                        document.getElementById('ph-date').textContent = dateStr;
                        document.getElementById('ph-hours').textContent = hoursStr;
                        document.getElementById('ph-minutes').textContent = minutes;
                        document.getElementById('ph-seconds').textContent = seconds;
                        document.getElementById('ph-ampm').textContent = ampm;
                    }

                    // Update the elements for staff
                    if (document.getElementById('staff-ph-date')) {
                        document.getElementById('staff-ph-date').textContent = dateStr;
                        document.getElementById('staff-ph-hours').textContent = hoursStr;
                        document.getElementById('staff-ph-minutes').textContent = minutes;
                        document.getElementById('staff-ph-seconds').textContent = seconds;
                        document.getElementById('staff-ph-ampm').textContent = ampm;
                    }

                    // Update the elements for admin
                    if (document.getElementById('admin-ph-date')) {
                        document.getElementById('admin-ph-date').textContent = dateStr;
                        document.getElementById('admin-ph-hours').textContent = hoursStr;
                        document.getElementById('admin-ph-minutes').textContent = minutes;
                        document.getElementById('admin-ph-seconds').textContent = seconds;
                        document.getElementById('admin-ph-ampm').textContent = ampm;
                    }

                    // Update the hidden refresh indicator (for debugging/verification)
                    document.getElementById('refreshIndicator').textContent = `Last refresh: ${now.toLocaleTimeString()}`;
                }

                // Update time immediately and then every second
                updatePhilippineTime();
                let timeInterval = setInterval(updatePhilippineTime, 1000);

                // Advanced time synchronization function
                function synchronizeTime() {
                    const now = new Date();
                    const milliseconds = now.getMilliseconds();

                    // Calculate delay to sync with the next second change
                    const delay = 1000 - milliseconds;

                    // Clear existing interval
                    clearInterval(timeInterval);

                    // Set new interval that starts at the next second
                    setTimeout(() => {
                        updatePhilippineTime();
                        timeInterval = setInterval(updatePhilippineTime, 1000);
                    }, delay);
                }

                // Start synchronized timekeeping
                synchronizeTime();

                // Clean Navigation Tab Interaction for all user types
                document.addEventListener('DOMContentLoaded', function () {
                    // Handle User tabs
                    const userNavTabs = document.querySelectorAll('.user-nav-tab');
                    userNavTabs.forEach(tab => {
                        tab.addEventListener('click', function (e) {
                            // Remove active class from all user tabs
                            userNavTabs.forEach(t => t.classList.remove('user-active'));
                            // Add active class to clicked tab
                            this.classList.add('user-active');
                        });
                    });

                    // Handle Staff tabs
                    const staffNavTabs = document.querySelectorAll('.staff-nav-tab');
                    staffNavTabs.forEach(tab => {
                        tab.addEventListener('click', function (e) {
                            // Remove active class from all staff tabs
                            staffNavTabs.forEach(t => t.classList.remove('staff-active'));
                            // Add active class to clicked tab
                            this.classList.add('staff-active');
                        });
                    });

                    // Handle Admin tabs
                    const adminNavTabs = document.querySelectorAll('.admin-nav-tab');
                    adminNavTabs.forEach(tab => {
                        tab.addEventListener('click', function (e) {
                            // Remove active class from all admin tabs
                            adminNavTabs.forEach(t => t.classList.remove('admin-active'));
                            // Add active class to clicked tab
                            this.classList.add('admin-active');
                        });
                    });

                    // Check current page and set active state on page load
                    const currentPage = window.location.pathname.split('/').pop();
                    
                    // For User pages
                    if (document.querySelector('.user-nav-tab')) {
                        document.querySelectorAll('.user-nav-tab').forEach(tab => {
                            const href = tab.getAttribute('href');
                            if (href === currentPage) {
                                tab.classList.add('user-active');
                            }
                        });
                    }
                    
                    // For Staff pages
                    if (document.querySelector('.staff-nav-tab')) {
                        document.querySelectorAll('.staff-nav-tab').forEach(tab => {
                            const href = tab.getAttribute('href').split('/').pop();
                            if (href === currentPage) {
                                tab.classList.add('staff-active');
                            }
                        });
                    }
                    
                    // For Admin pages
                    if (document.querySelector('.admin-nav-tab')) {
                        document.querySelectorAll('.admin-nav-tab').forEach(tab => {
                            const href = tab.getAttribute('href').split('/').pop();
                            if (href === currentPage) {
                                tab.classList.add('admin-active');
                            }
                        });
                    }

                    // Background time synchronization
                    function backgroundTimeSync() {
                        // Check time accuracy every 30 seconds
                        setInterval(() => {
                            const now = new Date();
                            const expectedSeconds = (now.getSeconds() + 1) % 60;

                            // Schedule a check for the next second
                            setTimeout(() => {
                                const checkTime = new Date();
                                if (checkTime.getSeconds() !== expectedSeconds) {
                                    // Time is out of sync, resynchronize
                                    synchronizeTime();
                                }
                            }, 1000 - now.getMilliseconds());
                        }, 30000); // Check every 30 seconds
                    }

                    // Start background time synchronization
                    backgroundTimeSync();

                    // Handle mobile virtual keyboard issues
                    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                        const inputs = document.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            input.addEventListener('focus', function () {
                                // Scroll the input into view with some padding
                                setTimeout(() => {
                                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }, 300);
                            });
                        });
                    }
                });

                // Page visibility API to optimize time updates
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        // Page is hidden, reduce update frequency to save resources
                        clearInterval(timeInterval);
                        timeInterval = setInterval(updatePhilippineTime, 5000); // Update every 5 seconds when tab is hidden
                    } else {
                        // Page is visible, resume normal update frequency
                        clearInterval(timeInterval);
                        synchronizeTime(); // Resync time when returning to the tab
                    }
                });

                // Enhanced Modal functions with smooth transitions
                function openModal() {
                    const modal = document.getElementById("loginModal");
                    const modalContent = modal.querySelector('.modal-content');

                    modal.classList.remove("hidden");
                    modal.classList.add("flex");

                    // Trigger animation
                    setTimeout(() => {
                        modalContent.classList.add('open');
                    }, 10);
                }

                function closeModal() {
                    const modal = document.getElementById("loginModal");
                    const modalContent = modal.querySelector('.modal-content');

                    // Reset file input and error on close
                    const profileImageInput = document.getElementById('profile_image');
                    const profileUploadError = document.getElementById('profileUploadError');
                    if (profileImageInput) profileImageInput.value = '';
                    if (profileUploadError) profileUploadError.classList.add('hidden');
                    modalContent.classList.remove('open');
                    // Wait for animation to complete before hiding
                    setTimeout(() => {
                        modal.classList.remove("flex");
                        modal.classList.add("hidden");
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

                // Close modal when clicking outside
                document.getElementById('loginModal')?.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });
            </script>

    </body>

</html>
