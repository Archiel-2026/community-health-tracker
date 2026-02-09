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

// Get user information
$userInfo = [];
try {
    $stmt = $pdo->prepare("SELECT email, full_name, date_of_birth, created_at FROM sitio1_users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $error = 'Error fetching user information: ' . $e->getMessage();
}

$userEmail = $userInfo['email'] ?? $_SESSION['user']['email'] ?? 'Not provided';
$userFullName = $userInfo['full_name'] ?? $_SESSION['user']['full_name'] ?? 'Not provided';
$userDateOfBirth = $userInfo['date_of_birth'] ?? $_SESSION['user']['date_of_birth'] ?? null;
$userCreatedAt = $userInfo['created_at'] ?? $_SESSION['user']['created_at'] ?? date('Y-m-d');
$error = '';
$success = '';

// Get ALL patient info linked to this user
$allPatientInfo = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            sp.id,
            sp.full_name,
            sp.date_of_birth,
            sp.age,
            sp.gender,
            sp.sitio,
            sp.disease,
            sp.last_checkup,
            sp.phic_no,
            sp.fourps_member,
            sp.bhw_assigned,
            sp.contact,
            sp.created_at,
            sp.updated_at as patient_updated_at,
            sp.occupation,
            sp.civil_status,
            sp.family_no,
            eip.blood_type,
            eip.height,
            eip.weight,
            eip.allergies,
            eip.current_medications,
            eip.blood_pressure,
            eip.temperature,
            eip.chronic_conditions,
            eip.immunization_record,
            eip.medical_history,
            eip.family_history,
            eip.updated_at as medical_updated_at
        FROM sitio1_patients sp
        LEFT JOIN existing_info_patients eip ON sp.id = eip.patient_id
        WHERE sp.user_id = ? 
        AND sp.deleted_at IS NULL
        ORDER BY sp.full_name ASC
    ");
    $stmt->execute([$userId]);
    $allPatientInfo = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $error = 'Error fetching patient information: ' . $e->getMessage();
}

// Get consultation notes
$allConsultationNotes = [];
if (!empty($allPatientInfo)) {
    try {
        $patientIds = array_column($allPatientInfo, 'id');
        $placeholders = str_repeat('?,', count($patientIds) - 1) . '?';

        $stmt = $pdo->prepare("
            SELECT 
                cn.*,
                cn.doctor_name as doctor_name,
                sp.full_name as patient_full_name,
                sp.age as patient_age,
                sp.gender as patient_gender,
                sp.date_of_birth as patient_dob,
                sp.civil_status as patient_civil_status,
                sp.occupation as patient_occupation,
                sp.contact as patient_contact,
                sp.sitio as patient_sitio,
                sp.address as patient_address
            FROM consultation_notes cn
            LEFT JOIN sitio1_patients sp ON cn.patient_id = sp.id
            WHERE cn.patient_id IN ($placeholders)
            ORDER BY cn.consultation_date DESC
        ");
        $stmt->execute($patientIds);
        $allConsultationNotes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        // Consultation notes might not exist - not critical
    }
}

// Calculate stats
$totalConsultationNotes = count($allConsultationNotes);
$totalPatients = count($allPatientInfo);
$hasPersonalRecordUpdates = false;
$hasMedicalUpdates = false;
foreach ($allPatientInfo as $patient) {
    $patientUpdatedAt = $patient['patient_updated_at'] ?? null;
    $patientCreatedAt = $patient['created_at'] ?? null;
    $patientHasUpdate = !empty($patientUpdatedAt) && (
        empty($patientCreatedAt) || strtotime($patientUpdatedAt) > strtotime($patientCreatedAt)
    );

    if (!$hasPersonalRecordUpdates && $patientHasUpdate) {
        $hasPersonalRecordUpdates = true;
    }

    if (!$hasMedicalUpdates && !empty($patient['medical_updated_at'])) {
        $hasMedicalUpdates = true;
    }

    if ($hasPersonalRecordUpdates && $hasMedicalUpdates) {
        break;
    }
}
?>

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
        /* text-transform: uppercase; */
        color: #6b7280;
        line-height: 1.4;
    }

    /* Card Shadows - Match dashboard style */
    .card-shadow {
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
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

    .tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 400;
        line-height: 1;
        letter-spacing: 0.2px;
        margin-left: 0.5rem;
    }

    .tab-badge-count {
        background: rgba(16, 185, 129, 0.8);
        color: #ffffff;
        width: 24px !important;
        height: 24px !important;
        min-width: 24px !important;
        min-height: 24px !important;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-weight: 400;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        padding: 0;
        margin-left: 0.5rem;
        line-height: 1;
        gap: 0.5rem;
    }

    .tab-badge-update {
        background: rgba(16, 185, 129, 0.8);
        color: #fff !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 4px;
        padding: 0.5rem 1.25rem;
        font-size: 0.75rem;
        font-weight: 400;
        min-width: auto;
        height: auto;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    }

    .updated-record {
        background: rgba(16, 185, 129, 0.05);
        border-radius: 4px;
        /* padding: 1rem; */
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .update-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        background: rgba(16, 185, 129, 0.8);
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 400;
        letter-spacing: 0.2px;
    }

    .update-indicator i {
        font-size: 0.65rem;
    }


    .tab-header.active .tab-badge-count {
        background: rgba(255, 255, 255, 0.95);
        color: #2563eb !important;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(255, 255, 255, 0.6);
    }

    .tab-badge-count {
        color: #fff !important;
    }

    .tab-header.active .tab-badge-update {
        background: rgba(16, 185, 129, 0.25);
        color: #fff !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
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
        background: white;
        overflow: hidden;
        gap: 0.5rem;
    }

    @media (min-width: 640px) {
        .tab-nav-container {
            padding: 2rem 0;
            gap: 0.75rem;
        }
    }

    /* Mobile Tab Dropdown */
    .mobile-tab-selector {
        display: none;
    }

    .tab-counts-mobile {
        display: none;
    }

    .tab-select {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: #374151;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%233b82f6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1.5rem;
        padding-right: 3rem;
    }

    .tab-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.375rem 0.875rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        min-width: 5rem;
        width: 5rem;
    }

    .count-badge-primary {
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .count-badge-success {
        background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    @media (max-width: 640px) {
        .tab-nav-container {
            display: none;
        }

        .mobile-tab-selector {
            display: block;
            background: white;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-counts-mobile {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            padding: 1rem;
        }

        .count-item-mobile {
            flex: 1;
            min-width: calc(33.333% - 0.5rem);
            text-align: center;
        }

        .count-label-mobile {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 0.375rem;
        }
    }

    /* Tab Content - Allow scrolling only for content */
    .tab-content-wrapper {
        padding: 1.9rem;
        background: white;
        overflow-y: auto;
        max-height: calc(100vh - 100px);
    }

    @media (max-width: 640px) {
        .tab-content-wrapper {
            padding: 1rem;
            max-height: calc(100vh - 350px);
        }
    }

    /* Additional padding for Personal Records and Health Metrics tabs */
    #patients,
    #medical {
        padding: 1rem;
    }

    @media (min-width: 641px) {

        #patients,
        #medical {
            padding: 0;
        }
    }

    /* Health Metric Cards */
    .metric-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: #f9fafb;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
    }

    .metric-badge:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    /* Vital Signs Grid */
    .vital-item {
        padding: 16px;
        background: #f9fafb;
        border-radius: 10px;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
    }

    .vital-item:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .vital-label {
        font-size: 0.7rem;
        font-weight: 500;
        color: #6b7280;
        letter-spacing: 0.025em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .vital-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        line-height: 1.2;
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

    /* Custom Scrollbar - Match dashboard */
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

    /* Header Styles */
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

    /* Stats Card */
    .stat-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .stat-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        letter-spacing: 0.025em;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
    }

    /* Section Title */
    .section-title {
        font-size: 1.3rem;
        font-weight: 400;
        color: #3C96E1;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Patient Card */
    .patient-name {
        font-size: 1rem;
        font-weight: 600;
        color: #0d1117;
        margin-bottom: 0.5rem;
    }

    .patient-meta {
        font-size: 0.8125rem;
        color: #718096;
        font-weight: 500;
    }

    /* Consultation Item */
    .consultation-date {
        font-size: 0.95rem;
        font-weight: 600;
        color: #0d1117;
        margin-bottom: 0.25rem;
    }

    .consultation-doctor {
        font-size: 0.8125rem;
        color: #2563eb;
        font-weight: 500;
        margin-top: 0.5rem;
    }

    .consultation-note {
        font-size: 0.95rem;
        color: #4a5568;
        line-height: 1.6;
        margin-top: 0.75rem;
        font-weight: 400;
    }

    /* Button Styles - Match dashboard */
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white !important;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-primary:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn-primary span {
        color: white !important;
    }

    .btn-primary i {
        color: white !important;
    }

    /* Content Section */
    .content-section {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        line-height: 1.7;
        color: #4a5568;
        font-weight: 400;
    }

    .content-section strong {
        color: #0d1117;
        font-weight: 600;
    }

    /* Mobile Responsive Media Queries */
    @media (max-width: 640px) {
        .page-title {
            font-size: 1.5rem !important;
            line-height: 1.3;
        }

        .page-subtitle {
            font-size: 0.875rem !important;
        }

        .section-title {
            font-size: 1.125rem !important;
        }

        .stat-label {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
        }

        .stat-value {
            font-size: 1.875rem !important;
            font-weight: 700 !important;
        }

        .tab-header {
            padding: 0.65rem 0.85rem !important;
            font-size: 0.8125rem !important;
            font-weight: 700 !important;
            min-height: 40px !important;
            flex: 1 1 auto;
            white-space: normal !important;
            word-wrap: break-word !important;
            max-width: 100% !important;
        }

        .card-shadow {
            border-radius: 0.75rem !important;
        }

        .metric-badge {
            padding: 8px 12px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
        }

        .vital-item {
            padding: 14px !important;
        }

        .vital-label {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
        }

        .vital-value {
            font-size: 1rem !important;
            font-weight: 700 !important;
        }

        .btn-primary {
            padding: 0.875rem 1.25rem !important;
            font-size: 0.9375rem !important;
            font-weight: 700 !important;
            min-height: 44px !important;
        }

        .text-label {
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
        }

        .empty-state-text {
            font-size: 1rem !important;
            font-weight: 500 !important;
        }

        .empty-state-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
        }
    }

    /* Tablet Responsive */
    @media (min-width: 641px) and (max-width: 1023px) {
        .page-title {
            font-size: 1.75rem !important;
        }

        .stat-value {
            font-size: 2.25rem !important;
        }

        .section-title {
            font-size: 1.125rem !important;
        }

        .tab-header {
            padding: 0.75rem 1.5rem !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            min-height: 44px !important;
        }

        .btn-primary {
            padding: 0.875rem 1.5rem !important;
            font-size: 1rem !important;
            min-height: 44px !important;
        }
    }
</style>

<div class="bg-gray-100 h-full">
    <div>
        <?php if (!empty($error)): ?>
            <div
                class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 flex items-center mt-6">
                <div class="w-8 h-8 bg-yellow-200 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-yellow-600"></i>
                </div>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Content Tabs -->
        <div class="card-shadow overflow-hidden">
            <!-- Mobile Tab Selector (visible on mobile only) -->
            <div class="mobile-tab-selector">
                <!-- Tab Counts at the top -->
                <div class="tab-counts-mobile">
                    <div class="count-item-mobile">
                        <span class="count-label-mobile">Doctor's Notes</span>
                        <span class="count-badge count-badge-primary"><?php echo $totalConsultationNotes; ?></span>
                    </div>
                    <div class="count-item-mobile">
                        <span class="count-label-mobile">Personal Records</span>
                        <?php
                        $isFirstLink = false;
                        if (!empty($allPatientInfo)) {
                            foreach ($allPatientInfo as $patient) {
                                $createdAt = $patient['created_at'] ?? null;
                                $updatedAt = $patient['patient_updated_at'] ?? null;
                                if ($createdAt && ($updatedAt === $createdAt || empty($updatedAt))) {
                                    $isFirstLink = true;
                                    break;
                                }
                            }
                        }
                        if ($hasPersonalRecordUpdates && !$isFirstLink): ?>
                            <span class="count-badge count-badge-success">Updated</span>
                        <?php endif; ?>
                    </div>
                    <div class="count-item-mobile">
                        <span class="count-label-mobile">Health Metrics</span>
                        <?php
                        $isFirstLinkMedical = false;
                        if (!empty($allPatientInfo)) {
                            foreach ($allPatientInfo as $patient) {
                                $createdAt = $patient['created_at'] ?? null;
                                $medicalUpdatedAt = $patient['medical_updated_at'] ?? null;
                                if ($createdAt && ($medicalUpdatedAt === $createdAt || empty($medicalUpdatedAt))) {
                                    $isFirstLinkMedical = true;
                                    break;
                                }
                            }
                        }
                        if ($hasMedicalUpdates && !$isFirstLinkMedical): ?>
                            <span class="count-badge count-badge-success">Updated</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Dropdown Select -->
                <select class="tab-select" id="mobileTabSelect" aria-label="Select tab">
                    <option value="consultations" selected>Doctor's Notes</option>
                    <option value="patients">Personal Records</option>
                    <option value="medical">Health Metrics</option>
                </select>
            </div>

            <!-- Desktop Tab Navigation (visible on desktop only) -->
            <div class="tab-nav-container ">
                <button class="tab-header active" data-tab="consultations">
                    <span>Doctor's Notes</span>
                    <span class="tab-badge tab-badge-count"><?php echo $totalConsultationNotes; ?></span>
                </button>
                <button class="tab-header" data-tab="patients">
                    <span>Personal Records</span>
                </button>
                <button class="tab-header" data-tab="medical">
                    <span>Health Metrics</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-wrapper">
                <!-- Consultations Tab -->
                <div id="consultations" class="tab-content active">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <i class="fas fa-user-md icon-3xl"></i>
                            </div>
                            <h3 class="empty-state-title">No Doctor's Notes</h3>
                            <p class="empty-state-text mb-8 max-w-sm mx-auto">Your account is not yet linked to any patient
                                records. Please contact the health center to establish the connection.</p>
                            <button class="btn-primary"
                                onclick="alert('Please contact the health center to link your account.')">
                                <i class="fas fa-phone icon-sm mr-2"></i>Contact Health Center
                            </button>
                        </div>
                    <?php elseif (empty($allConsultationNotes)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <i class="fas fa-file-medical-alt icon-3xl"></i>
                            </div>
                            <h3 class="empty-state-title">No Consultations Yet</h3>
                            <p class="empty-state-text">Visit the health center for your first consultation and medical
                                evaluation.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php
                            $consultationIndex = 1;
                            foreach ($allConsultationNotes as $note):
                                // Find patient name
                                $patientName = 'Unknown';
                                foreach ($allPatientInfo as $patient) {
                                    if ($patient['id'] == $note['patient_id']) {
                                        $patientName = $patient['full_name'];
                                        break;
                                    }
                                }
                                ?>
                                <div class="card-shadow p-5">
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-3 gap-2">
                                            <span
                                                class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-600">
                                                <i class="fas fa-stethoscope icon-xs mr-1"></i>
                                                Consultation <?php echo $consultationIndex; ?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('M d, Y', strtotime($note['consultation_date'] ?? 'now')); ?>
                                            </span>
                                        </div>

                                        <h3 class="text-base font-600 text-gray-800 mb-3">
                                            Consultation on
                                            <?php echo date('M d, Y', strtotime($note['consultation_date'] ?? 'now')); ?>
                                        </h3>

                                        <div class="space-y-2">
                                            <?php if (!empty($note['doctor_name'])): ?>
                                                <p class="text-sm text-gray-600">
                                                    <span class="font-600 text-gray-800">Doctor Assigned :</span>
                                                    <span
                                                        class="text-gray-700"><?php echo htmlspecialchars($note['doctor_name']); ?></span>
                                                </p>
                                            <?php endif; ?>

                                            <p class="text-sm text-gray-600">
                                                <span class="font-600 text-gray-800">Last Consultation :</span>
                                                <span
                                                    class="text-gray-700"><?php echo date('M d, Y', strtotime($note['consultation_date'] ?? 'now')); ?></span>
                                            </p>
                                        </div>
                                    </div>



                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <?php if (!empty($note['next_consultation_date'])): ?>
                                            <div
                                                class="inline-flex items-center px-3 py-1.5 bg-green-50 rounded-full border border-green-200">
                                                <i class="fas fa-calendar-check text-green-600 mr-2 text-sm"></i>
                                                <span class="text-xs font-600 text-gray-700 mr-1">Next:</span>
                                                <span
                                                    class="text-xs font-700 text-green-700"><?php echo date('M d, Y', strtotime($note['next_consultation_date'])); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span></span>
                                        <?php endif; ?>

                                        <button
                                            onclick="viewConsultationNote(<?php echo htmlspecialchars(json_encode($note)); ?>)"
                                            class="btn-primary">
                                            <i class="fas fa-eye"></i>
                                            <span>View Details</span>
                                        </button>
                                    </div>
                                </div>
                                <?php $consultationIndex++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Patients Tab -->
                <div id="patients" class="tab-content hidden">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <i class="fas fa-user-times icon-3xl"></i>
                            </div>
                            <h3 class="empty-state-title">No Personal Records</h3>
                            <p class="empty-state-text">Contact the health center to link and establish patient records for
                                your account.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-8">
                            <?php foreach ($allPatientInfo as $patient): ?>
                                <?php
                                $patientUpdatedAt = $patient['patient_updated_at'] ?? null;
                                $patientCreatedAt = $patient['created_at'] ?? null;
                                $patientHasUpdate = false;
                                if (!empty($patientUpdatedAt) && !empty($patientCreatedAt) && strtotime($patientUpdatedAt) > strtotime($patientCreatedAt)) {
                                    $patientHasUpdate = true;
                                }
                                ?>
                                <div class="record-details-blur <?php echo $patientHasUpdate ?: ''; ?>">
                                    <!-- Identity Information Section -->
                                    <div class="mb-6">
                                        <h4 class="section-title">
                                            <i class="fas fa-id-card text-blue-500 icon-lg"></i>Identity Information
                                            <?php if ($patientHasUpdate): ?>
                                                <span class="update-indicator update-indicator-mobile">
                                                    <i class="fas fa-rotate"></i>
                                                    Updated <?php echo date('M d, Y', strtotime($patientUpdatedAt)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </h4>

                                        <!--INFORMATION DATA  -->
                                        <div class="flex flex-col md:flex-row gap-8 w-full">
                                            <div class="flex-1 bg-white p-3 rounded-md shadow-md min-h-[120px] w-full">
                                                <!-- Full Name -->
                                                <div>
                                                    <p class="text-label text-gray-50 mb-2">Full Name</p>
                                                    <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                        <?php echo htmlspecialchars($patient['full_name']); ?>
                                                    </p>
                                                </div>

                                                <div class="grid grid-cols-2 md:grid-cols-2 gap-6 py-4 record-details-columns">
                                                    <!-- Date of Birth -->


                                                    <?php if (!empty($patient['date_of_birth'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Date of Birth</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Occupation -->
                                                    <?php if (!empty($patient['occupation'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Occupation</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['occupation']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Occupation</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Civil Status -->
                                                    <?php if (!empty($patient['civil_status'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Civil Status</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['civil_status']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Civil Status</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Family No. -->
                                                    <?php if (!empty($patient['family_no'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Family No.</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['family_no']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Family No.</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- PHIC No. -->
                                                    <?php if (!empty($patient['phic_no'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">PHIC No.</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['phic_no']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">PHIC No.</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- 4P's Member -->
                                                    <div>
                                                        <p class="text-label mb-2">4P's Member</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                            <?php echo (!empty($patient['fourps_member']) && $patient['fourps_member'] == 'Yes') ? 'Yes' : 'No'; ?>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div>
                                                    <!-- BHW Assigned -->
                                                    <?php if (!empty($patient['bhw_assigned'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">BHW Assigned</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['bhw_assigned']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">BHW Assigned</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex-1 bg-white p-3 rounded-md shadow-md w-full">
                                                <div class="grid grid-cols-2 md:grid-cols-2 gap-6 py-4 record-details-columns">
                                                    <!-- Last Check-up -->
                                                    <div>
                                                        <p class="text-label mb-2">Last Check-up</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                            <?php echo (!empty($patient['last_checkup'])) ? date('M d, Y', strtotime($patient['last_checkup'])) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Consultation Type -->
                                                    <?php if (!empty($patient['consultation_type'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Consultation Type</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                                <?php echo htmlspecialchars($patient['consultation_type']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Consultation Type</p>
                                                            <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-500 font-500">Onsite
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Record Status -->
                                                    <div>
                                                        <p class="text-label mb-2">Record Status</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg">
                                                            <span
                                                                class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-500">
                                                                <i class="fas fa-check-circle icon-xs mr-1.5"></i>Active
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <!-- Link Since -->
                                                    <div>
                                                        <p class="text-label mb-2">Link Since</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-700 font-500">
                                                            <?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information Section -->
                                    <!-- <?php if (!empty($patient['sitio']) || !empty($patient['contact']) || !empty($patient['disease'])): ?>
                                    <div class="card-shadow rounded-2xl p-8 hover:shadow-lg record-details-columns">
                                        <h4 class="section-title mb-6">
                                            <i class="fas fa-info-circle text-blue-500 icon-lg"></i>Additional Information
                                            <?php if ($patientHasUpdate): ?>
                                                <span class="update-indicator">
                                                    <i class="fas fa-rotate"></i>
                                                    Updated <?php echo date('M d, Y', strtotime($patientUpdatedAt)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </h4>
                                        
                                        <div class="space-y-4">
                                            <?php if (!empty($patient['sitio'])): ?>
                                                <div class="flex items-start gap-4">
                                                    <i class="fas fa-map-marker-alt text-blue-500 icon-base flex-shrink-0 w-5 text-center mt-1"></i>
                                                    <div class="flex-1">
                                                        <p class="text-label mb-1">Location/Sitio</p>
                                                        <p class="text-gray-700 font-500"><?php echo htmlspecialchars($patient['sitio']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($patient['contact'])): ?>
                                                <div class="flex items-start gap-4">
                                                    <i class="fas fa-phone text-green-500 icon-base flex-shrink-0 w-5 text-center mt-1"></i>
                                                    <div class="flex-1">
                                                        <p class="text-label mb-1">Contact Number</p>
                                                        <p class="text-gray-700 font-500"><?php echo htmlspecialchars($patient['contact']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($patient['disease'])): ?>
                                                <div class="flex items-start gap-4">
                                                    <i class="fas fa-stethoscope text-red-500 icon-base flex-shrink-0 w-5 text-center mt-1"></i>
                                                    <div class="flex-1">
                                                        <p class="text-label mb-1">Primary Condition</p>
                                                        <p class="text-gray-700 font-500"><?php echo htmlspecialchars($patient['disease']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div> -->
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Medical Info Tab -->
                <div id="medical" class="tab-content hidden">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <i class="fas fa-heartbeat icon-3xl"></i>
                            </div>
                            <h3 class="empty-state-title">No Health Records</h3>
                            <p class="empty-state-text">No patient records have been linked to your account yet.</p>
                        </div>
                    <?php else:
                        $anyPatientHasMedicalInfo = false;
                        foreach ($allPatientInfo as $patient) {
                            if (
                                !empty($patient['blood_type']) || !empty($patient['height']) ||
                                !empty($patient['weight']) || !empty($patient['allergies']) ||
                                !empty($patient['current_medications'])
                            ) {
                                $anyPatientHasMedicalInfo = true;
                                break;
                            }
                        }

                        if (!$anyPatientHasMedicalInfo): ?>
                            <div class="text-center py-10 sm:py-20">
                                <div class="empty-state-icon">
                                    <i class="fas fa-heartbeat icon-3xl"></i>
                                </div>
                                <h3 class="empty-state-title">No Medical Information</h3>
                                <p class="empty-state-text">Medical data will be available after your first consultation with
                                    the health center staff.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6 sm:space-y-10">
                                <?php foreach ($allPatientInfo as $patient):
                                    // Check if patient has any medical info
                                    $hasMedicalInfo = !empty($patient['blood_type']) || !empty($patient['height']) ||
                                        !empty($patient['weight']) || !empty($patient['allergies']) ||
                                        !empty($patient['current_medications']);

                                    if (!$hasMedicalInfo)
                                        continue;

                                    $medicalUpdatedAt = $patient['medical_updated_at'] ?? null;
                                    $medicalHasUpdate = false;
                                    if (!empty($medicalUpdatedAt) && !empty($patient['created_at']) && strtotime($medicalUpdatedAt) > strtotime($patient['created_at'])) {
                                        $medicalHasUpdate = true;
                                    }
                                    ?>
                                    <div class="<?php echo $medicalHasUpdate ? : ''; ?>">
                                        <!-- <div class="flex items-center justify-between mb-4 sm:mb-6">
                                            <div class="text-base sm:text-lg font-600 text-gray-800">
                                                <?php echo htmlspecialchars($patient['full_name'] ?? 'Patient'); ?>
                                            </div>
                                            <?php if ($medicalHasUpdate): ?>
                                                <span class="update-indicator">
                                                    <i class="fas fa-rotate"></i>
                                                    Updated <?php echo date('M d, Y', strtotime($medicalUpdatedAt)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div> -->

                                        <!-- Two Column Layout: Vital Statistics and Medical Details -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 md:gap-8">
                                            <!-- Vital Statistics Section -->
                                            <div class="flex-1 bg-white p-3 rounded-md shadow-md">
                                                <h4
                                                    class="text-base sm:text-lg font-600 text-blue-500 mb-4 sm:mb-6 flex items-center gap-2">
                                                    <i class="fas fa-heartbeat text-blue-500 text-lg sm:text-xl"></i>Vital
                                                    Statistics
                                                </h4>
                                                <div class="grid grid-cols-2 gap-2 sm:gap-4">
                                                    <!-- Blood Type & Height Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Blood Type</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['blood_type']) ? htmlspecialchars($patient['blood_type']) : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Height</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['height']) ? htmlspecialchars($patient['height']) . ' cm' : 'N/A'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Weight & BMI Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Weight</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['weight']) ? htmlspecialchars($patient['weight']) . ' kg' : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">BMI</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php
                                                            $height = !empty($patient['height']) ? $patient['height'] / 100 : 0;
                                                            $weight = !empty($patient['weight']) ? $patient['weight'] : 0;
                                                            $bmi = $height > 0 ? $weight / ($height * $height) : 0;
                                                            if ($bmi > 0) {
                                                                $bmiStatus = '';
                                                                if ($bmi < 18.5) {
                                                                    $bmiStatus = 'Underweight';
                                                                } elseif ($bmi < 25) {
                                                                    $bmiStatus = 'Normal';
                                                                } elseif ($bmi < 30) {
                                                                    $bmiStatus = 'Overweight';
                                                                } else {
                                                                    $bmiStatus = 'Obese';
                                                                }
                                                                echo number_format($bmi, 1) . ' (' . $bmiStatus . ')';
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>

                                                    <!-- Temperature & Blood Pressure Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Temperature</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['temperature']) ? htmlspecialchars($patient['temperature']) . ' °C' : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Blood Pressure</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-emerald-500 font-500">
                                                            <?php echo !empty($patient['blood_pressure']) ? htmlspecialchars($patient['blood_pressure']) : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Medical Details Section -->
                                            <div class="flex-1 bg-white p-3 rounded-md shadow-md">
                                                <h4 class="text-lg font-600 text-blue-500 mb-6 flex items-center gap-2">
                                                    <i class="fas fa-capsules text-blue-500 icon-lg"></i>Medical Details
                                                </h4>
                                                <div class="grid sm:grid-cols-2 gap-4">
                                                    <!-- Allergies & Current Medications Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Allergies</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Current Medications</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['current_medications']) ? htmlspecialchars($patient['current_medications']) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Chronic Conditions & Immunization Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Chronic Conditions</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['chronic_conditions']) ? htmlspecialchars($patient['chronic_conditions']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Immunization Record</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['immunization_record']) ? htmlspecialchars($patient['immunization_record']) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Medical History & Family Medical History Row -->
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Medical History</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['medical_history']) ? htmlspecialchars($patient['medical_history']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600 mb-2">Family Medical History</p>
                                                        <p class="px-4 py-3 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php
                                                            if (isset($patient['family_history'])) {
                                                                $fmh = trim($patient['family_history']);
                                                                echo ($fmh !== '' && strtolower($fmh) !== 'none') ? htmlspecialchars($fmh) : 'None';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Details Modal -->
    <div id="consultationModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-[100] backdrop-blur-sm">
        <div class="bg-white rounded-2xl card-shadow max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="p-5 sm:p-8 border-b border-gray-100 sticky top-0 bg-white z-10">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="page-title text-xl sm:text-2xl mb-2" id="modalTitle"></h3>
                        <p class="text-gray-600 text-sm font-500" id="modalSubtitle"></p>
                    </div>
                    <button onclick="closeModal()"
                        class="text-gray-400 hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-times icon-lg"></i>
                    </button>
                </div>
            </div>

            <div class="p-5 sm:p-8 space-y-6" id="modalBody"></div>

            <div
                class="p-4 sm:p-8 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-end gap-3 sticky bottom-0 shadow-lg">
                <button onclick="closeModal()"
                    class="px-5 py-3 sm:py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-600 text-sm order-2 sm:order-2">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer class="mt-12 border-t bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="text-center text-gray-500 text-sm">
                <p class="font-bold">Barangay Luz Health Center • Patient Portal</p>
                <p class="mt-1">© <?php echo date('Y'); ?> All rights reserved</p>
            </div>
        </div>
    </footer> -->

    <script>
        // Tab switching with smooth animation
        document.querySelectorAll('.tab-header').forEach(button => {
            button.addEventListener('click', function () {
                switchTab(this.getAttribute('data-tab'));
            });
        });

        // Mobile dropdown tab switching
        const mobileTabSelect = document.getElementById('mobileTabSelect');
        if (mobileTabSelect) {
            mobileTabSelect.addEventListener('change', function (e) {
                switchTab(e.target.value);
            });
        }

        // Unified tab switching function
        function switchTab(tabId) {
            // Update active tab button
            document.querySelectorAll('.tab-header').forEach(btn => {
                btn.classList.remove('active');
            });
            const activeButton = document.querySelector(`.tab-header[data-tab="${tabId}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }

            // Show selected tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
                content.classList.add('hidden');
            });
            const tabContent = document.getElementById(tabId);
            if (tabContent) {
                tabContent.classList.remove('hidden');
                tabContent.classList.add('active');
            }

            // Update mobile select if exists
            if (mobileTabSelect) {
                mobileTabSelect.value = tabId;
            }
        }

        // Modal functions
        let currentConsultation = null;

        function viewConsultationNote(note) {
            currentConsultation = note;
            const modal = document.getElementById('consultationModal');
            const consultationDate = new Date(note.consultation_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            });

            // Set Header Title
            const titleEl = document.getElementById('modalTitle');
            if (titleEl) titleEl.textContent = 'Consultation Details';

            const subTitleEl = document.getElementById('modalSubtitle');
            if (subTitleEl) subTitleEl.innerHTML = '';

            let doctorHtml = note.doctor_name
                ? `<div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm flex-shrink-0">
                        <i class="fas fa-user-md text-lg"></i>
                     </div>
                     <div class="overflow-hidden">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider truncate">Attending Physician</p>
                        <p class="text-sm font-semibold text-gray-900 truncate">${note.doctor_name}</p>
                     </div>
                   </div>`
                : '';

            let dateHtml = `
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shadow-sm flex-shrink-0">
                            <i class="fas fa-calendar-day text-lg"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider truncate">Date of Visit</p>
                            <p class="text-sm font-semibold text-gray-900 truncate">${consultationDate}</p>
                        </div>
                    </div>
            `;

            let headerMeta = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 bg-gray-50 rounded-xl border border-gray-200 mb-6">
                    ${dateHtml}
                    ${doctorHtml}
                </div>
            `;

            // Personal Info Section removed for modal display
            let personalInfoHtml = '';

            let noteContent = note.note
                ? `<p class="text-gray-700 text-base leading-7 whitespace-pre-wrap">${note.note}</p>`
                : `<p class="text-gray-400 italic text-center py-4">No detailed notes were recorded for this session.</p>`;

            let nextVisitHtml = '';
            if (note.next_consultation_date) {
                const nextDate = new Date(note.next_consultation_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long'
                });

                nextVisitHtml = `
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4 shadow-sm">
                            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-emerald-600 shadow-sm flex-shrink-0">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs text-emerald-800 font-bold tracking-wider mb-1">Next appointment scheduled</p>
                                <p class="text-lg font-bold text-emerald-900">${nextDate}</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            let bodyContent = `
                <div>
                    ${headerMeta}
                    <div class="mb-2">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <i class="fas fa-align-left text-blue-500"></i> Clinical Notes
                        </h4>
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm min-h-[120px]">
                            ${noteContent}
                        </div>
                    </div>
                    ${nextVisitHtml}
                </div>
            `;

            document.getElementById('modalBody').innerHTML = bodyContent;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('consultationModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function printConsultation() {
            if (!currentConsultation) return;

            const printWindow = window.open('', '_blank');
            const consultationDate = new Date(currentConsultation.consultation_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Consultation Record</title>
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                    <style>
                        * {
                            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        }
                        body { 
                            line-height: 1.8; 
                            color: #374151; 
                            margin: 0;
                            padding: 40px 30px;
                            background: white;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 40px; 
                            border-bottom: 3px solid #e5e7eb;
                            padding-bottom: 30px;
                        }
                        .header h1 { 
                            color: #1f2937; 
                            margin: 0 0 10px 0; 
                            font-weight: 800;
                            font-size: 28px;
                            letter-spacing: -0.5px;
                        }
                        .header p {
                            margin: 6px 0;
                            color: #6b7280;
                            font-weight: 500;
                            font-size: 14px;
                        }
                        .section { 
                            margin-bottom: 30px; 
                        }
                        .section-title { 
                            color: #1f2937; 
                            font-weight: 700; 
                            margin-bottom: 12px;
                            font-size: 13px;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            color: #6366f1;
                        }
                        .notes { 
                            background: #f9fafb; 
                            padding: 20px; 
                            border-radius: 10px; 
                            margin-bottom: 20px;
                            border-left: 4px solid #2563eb;
                            line-height: 1.8;
                        }
                        .footer { 
                            margin-top: 50px; 
                            padding-top: 20px; 
                            border-top: 2px solid #e5e7eb; 
                            font-size: 12px; 
                            color: #9ca3af; 
                            text-align: center;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 10px 0;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        .info-label {
                            font-weight: 600;
                            color: #4b5563;
                        }
                        .info-value {
                            font-weight: 500;
                            color: #1f2937;
                        }
                        @media print {
                            body { padding: 20px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Consultation Record</h1>
                        <p><strong>Barangay Luz Health Center</strong></p>
                        <p style="font-size: 13px; margin-top: 12px;"><strong>Date:</strong> ${consultationDate}</p>
                    </div>
                    
                    ${currentConsultation.patient_full_name ? `
                    <div class="section">
                        <div class="section-title">Patient Personal Information</div>
                        <div class="info-row">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value">${currentConsultation.patient_full_name || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Age:</span>
                            <span class="info-value">${currentConsultation.patient_age ? currentConsultation.patient_age + ' years' : 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender:</span>
                            <span class="info-value">${currentConsultation.patient_gender || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Civil Status:</span>
                            <span class="info-value">${currentConsultation.patient_civil_status || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Occupation:</span>
                            <span class="info-value">${currentConsultation.patient_occupation || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value">${currentConsultation.patient_dob ? new Date(currentConsultation.patient_dob).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</span>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="section">
                        <div class="section-title">Healthcare Provider</div>
                        <div class="info-row">
                            <span class="info-label">Doctor:</span>
                            <span class="info-value">${currentConsultation.doctor_name || 'Healthcare Staff'}</span>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Consultation Notes</div>
                        <div class="notes"><strong>${currentConsultation.note || 'No detailed notes were recorded during this consultation.'}</strong></div>
                    </div>
                    
                    ${currentConsultation.next_consultation_date ? `
                    <div class="section">
                        <div class="section-title">Next Appointment</div>
                        <div class="info-row">
                            <span class="info-label">Scheduled For:</span>
                            <span class="info-value">${new Date(currentConsultation.next_consultation_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })}</span>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="footer">
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                        <p style="margin: 8px 0 0 0;">This is an official medical record from Barangay Luz Health Center</p>
                    </div>
                </body>
                </html>
            `;

            printWindow.document.write(content);
            printWindow.document.close();
            setTimeout(function () {
                printWindow.print();
                printWindow.close();
            }, 100);
        }

        // Close modal when clicking outside
        document.getElementById('consultationModal').addEventListener('click', function (e) {
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
</div>