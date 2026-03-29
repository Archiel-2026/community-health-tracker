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

// Auto-switch tab based on ?tab parameter
$activeTab = $_GET['tab'] ?? 'consultations';
?>

<link rel="stylesheet" href="/community-health-tracker/asssets/css/Resident-myrecord.css">

<div>
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
        <div class="overflow-hidden">
            <!-- Mobile Tab Selector (visible on mobile only) -->
            <div class="mobile-tab-selector">
                <!-- Tab Counts at the top -->
                <div class="tab-counts-mobile">
                    <div class="count-item-mobile">
                        <span class="count-label-mobile">Doctor's Note</span>
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
                    <option value="medical">Health Records</option>
                </select>
            </div>

            <div class="flex flex-col md:flex-row items-center px-6 py-8 gap-2">
                <div>
                    <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M30.7691 9.67537C31.4665 8.978 32.5969 8.978 33.2942 9.67537C33.9916 10.3727 33.9916 11.5031 33.2942 12.2005L20.4943 25.0004L33.2942 37.8004C33.9916 38.4977 33.9916 39.6281 33.2942 40.3255C32.5969 41.0228 31.4665 41.0228 30.7691 40.3255L16.7066 26.263C16.0093 25.5656 16.0093 24.4352 16.7066 23.7379L30.7691 9.67537Z"
                            fill="black" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl text-gray-200 mb-2">My Records</h3>
                    <p class="text-gray-sample">View your medical history and health records</p>
                </div>
            </div>

            <!-- Desktop Tab Navigation (visible on desktop only) -->
            <div class="tab-nav-container">
                <button class="tab-header <?= $activeTab === 'consultations' ? 'active' : '' ?>"
                    data-tab="consultations">
                    <span>Doctor's Note</span>
                    <span class="tab-badge tab-badge-count"><?php echo $totalConsultationNotes; ?></span>
                </button>
                <button class="tab-header <?= $activeTab === 'patients' ? 'active' : '' ?>" data-tab="patients">
                    <span>Personal Records</span>
                </button>
                <button class="tab-header <?= $activeTab === 'medical' ? 'active' : '' ?>" data-tab="medical">
                    <span>Health Records</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-wrapper">
                <!-- Consultations Tab -->
                <div id="consultations" class="tab-content <?= $activeTab === 'consultations' ? 'active' : 'hidden' ?>">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M53.6665 29.2372L73.5581 34.533M49.4081 45.0538L59.3498 47.7038M49.904 74.858L53.879 75.9205C65.129 78.9205 70.754 80.4163 75.1873 77.8705C79.6165 75.3288 81.1248 69.733 84.1373 58.5497L88.3998 42.7288C91.4165 31.5413 92.9206 25.9497 90.3623 21.5413C87.804 17.133 82.1831 15.6372 70.929 12.6413L66.954 11.5788C55.704 8.57882 50.079 7.08299 45.6498 9.62882C41.2165 12.1705 39.7081 17.7663 36.6915 28.9497L32.4331 44.7705C29.4165 55.958 27.9081 61.5497 30.4706 65.958C33.029 70.3622 38.654 71.8622 49.904 74.858Z"
                                        stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                    <path
                                        d="M49.9998 87.273L46.0331 88.3564C34.8081 91.4105 29.1998 92.9397 24.7748 90.3439C20.3581 87.7522 18.8498 82.048 15.8456 70.6439L11.5915 54.5105C8.58312 43.1064 7.07895 37.4022 9.63312 32.9106C11.8415 29.0231 16.6665 29.1647 22.9165 29.1647"
                                        stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="empty-state-title">No Doctor's Notes</h3>
                            <p class="empty-state-text mb-8 max-w-sm mx-auto">Your account is not yet linked to any patient
                                records. <br> Please contact the health center to establish the connection.</p>
                            <button class="btn-primary"
                                onclick="alert('Please contact the health center to link your account.')">
                                <svg width="27" height="27" viewBox="0 0 27 27" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M15.216 4.84397C15.2446 4.73689 15.2941 4.6365 15.3615 4.54854C15.429 4.46058 15.5131 4.38677 15.6091 4.33134C15.7051 4.2759 15.8111 4.23992 15.9209 4.22545C16.0308 4.21098 16.1425 4.21831 16.2496 4.24701C17.8138 4.65512 19.2409 5.47282 20.3839 6.61588C21.527 7.75894 22.3447 9.18605 22.7528 10.7502C22.7815 10.8573 22.7888 10.969 22.7743 11.0788C22.7599 11.1887 22.7239 11.2947 22.6685 11.3907C22.613 11.4867 22.5392 11.5708 22.4513 11.6383C22.3633 11.7057 22.2629 11.7552 22.1558 11.7838C22.0846 11.8025 22.0112 11.8121 21.9375 11.8123C21.7516 11.8123 21.5709 11.7509 21.4234 11.6376C21.276 11.5243 21.1701 11.3655 21.1222 11.1858C20.7894 9.90891 20.122 8.74389 19.189 7.81083C18.2559 6.87776 17.0909 6.21041 15.814 5.87756C15.7068 5.84903 15.6063 5.79965 15.5182 5.73225C15.4302 5.66485 15.3562 5.58074 15.3007 5.48474C15.2452 5.38875 15.2091 5.28274 15.1946 5.17279C15.18 5.06284 15.1873 4.9511 15.216 4.84397ZM14.9702 9.25256C16.4247 9.64069 17.3591 10.5751 17.7472 12.0296C17.7951 12.2092 17.901 12.368 18.0484 12.4813C18.1959 12.5946 18.3766 12.656 18.5625 12.656C18.6362 12.6559 18.7096 12.6463 18.7808 12.6276C18.8879 12.5989 18.9883 12.5495 19.0763 12.482C19.1642 12.4146 19.238 12.3304 19.2935 12.2344C19.3489 12.1385 19.3849 12.0325 19.3993 11.9226C19.4138 11.8127 19.4065 11.701 19.3778 11.594C18.8378 9.57319 17.4266 8.16201 15.4058 7.62201C15.1896 7.56425 14.9593 7.59475 14.7656 7.7068C14.5718 7.81885 14.4305 8.00327 14.3728 8.21949C14.315 8.43572 14.3455 8.66603 14.4575 8.85977C14.5696 9.05351 14.754 9.1948 14.9702 9.25256ZM23.6124 19.309C23.4243 20.7381 22.7224 22.0499 21.6379 22.9994C20.5533 23.9489 19.1602 24.4711 17.7188 24.4685C9.34454 24.4685 2.53126 17.6553 2.53126 9.28104C2.52868 7.83959 3.05089 6.44648 4.00037 5.36192C4.94985 4.27736 6.26166 3.5755 7.69079 3.38744C8.05218 3.34332 8.41815 3.41725 8.73407 3.59821C9.04999 3.77917 9.29891 4.05745 9.44368 4.39151L11.6712 9.36436V9.37701C11.782 9.63273 11.8278 9.91191 11.8044 10.1896C11.781 10.4673 11.6892 10.7349 11.5372 10.9685C11.5183 10.997 11.4982 11.0234 11.4771 11.0497L9.28126 13.6527C10.0712 15.258 11.7503 16.9222 13.3766 17.7143L15.9437 15.5301C15.9689 15.5089 15.9953 15.4891 16.0228 15.471C16.2562 15.3153 16.5247 15.2203 16.8041 15.1945C17.0835 15.1687 17.3648 15.213 17.6228 15.3233L17.6365 15.3297L22.6051 17.5561C22.9398 17.7004 23.2187 17.9491 23.4003 18.265C23.5818 18.581 23.6562 18.9472 23.6124 19.309ZM21.9375 19.0981C21.9375 19.0981 21.9301 19.0981 21.9259 19.0981L16.9689 16.8779L14.4007 19.0622C14.3758 19.0833 14.3497 19.103 14.3227 19.1213C14.0798 19.2833 13.7991 19.3795 13.508 19.4006C13.2168 19.4217 12.9252 19.3669 12.6615 19.2415C10.6861 18.287 8.717 16.3327 7.76145 14.3783C7.63492 14.1166 7.57814 13.8267 7.59662 13.5366C7.6151 13.2464 7.70821 12.966 7.86692 12.7225C7.88481 12.6939 7.90491 12.6667 7.92704 12.6413L10.125 10.0351L7.91017 5.07811C7.90975 5.0739 7.90975 5.06966 7.91017 5.06545C6.88739 5.19887 5.94832 5.70055 5.26882 6.47653C4.58932 7.25252 4.216 8.2496 4.21876 9.28104C4.22267 12.8603 5.64624 16.2918 8.17713 18.8227C10.708 21.3536 14.1395 22.7771 17.7188 22.781C18.7496 22.7846 19.7464 22.4126 20.5228 21.7345C21.2993 21.0565 21.8022 20.1189 21.9375 19.097V19.0981Z"
                                        fill="white" />
                                </svg>
                                Contact Admin Support
                            </button>
                        </div>
                    <?php elseif (empty($allConsultationNotes)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <svg width="100" height="100" viewBox="0 0 100 100" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M53.6665 29.2372L73.5581 34.533M49.4081 45.0538L59.3498 47.7038M49.904 74.858L53.879 75.9205C65.129 78.9205 70.754 80.4163 75.1873 77.8705C79.6165 75.3288 81.1248 69.733 84.1373 58.5497L88.3998 42.7288C91.4165 31.5413 92.9206 25.9497 90.3623 21.5413C87.804 17.133 82.1831 15.6372 70.929 12.6413L66.954 11.5788C55.704 8.57882 50.079 7.08299 45.6498 9.62882C41.2165 12.1705 39.7081 17.7663 36.6915 28.9497L32.4331 44.7705C29.4165 55.958 27.9081 61.5497 30.4706 65.958C33.029 70.3622 38.654 71.8622 49.904 74.858Z"
                                        stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                    <path
                                        d="M49.9998 87.273L46.0331 88.3564C34.8081 91.4105 29.1998 92.9397 24.7748 90.3439C20.3581 87.7522 18.8498 82.048 15.8456 70.6439L11.5915 54.5105C8.58312 43.1064 7.07895 37.4022 9.63312 32.9106C11.8415 29.0231 16.6665 29.1647 22.9165 29.1647"
                                        stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="empty-state-title">No Consultations Yet</h3>
                            <p class="empty-state-text">Visit the health center for your first consultation and medical
                                evaluation.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <?php
                            $consultationIndex = 1;
                            foreach ($allConsultationNotes as $note):
                                $patientName = 'Unknown';
                                foreach ($allPatientInfo as $patient) {
                                    if ($patient['id'] == $note['patient_id']) {
                                        $patientName = $patient['full_name'];
                                        break;
                                    }
                                }
                                ?>
                                <div
                                    class="rounded-xl bg-white px-6 py-6 flex flex-col min-h-[180px] border border-gray-200 w-full">
                                    <!-- In the consultations tab, replace the consultation header section -->
<div class="flex gap-4 justify-between">
    <!-- LEFT: Consultation Info -->
    <div class="flex flex-col gap-4">
        <div class="flex items-center">
            <span class="consultation-header-group px-4 py-1.3 font-md inline-flex items-center">
                Consultation
                <span class="consultation-count-number font-md rounded-full w-7 h-7 flex items-center justify-center ml-2 text-base">
                    <?php echo $consultationIndex; ?>
                </span>
            </span>
        </div>
        <!-- Rest of the consultation info... -->
                                            <div class="mb-2">
                                                <span class="text-gray-400 text-sm">Consultation on :</span><br>
                                                <span class="text-lg font-medium tracking-wide leading-tight">
                                                    <?php echo date('F d, Y', strtotime($note['consultation_date'] ?? 'now')); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 text-sm">Doctor Assigned :</span><br>
                                                <span
                                                    class="doctor-name-auto-shrink text-lg font-medium tracking-wide leading-tight">
                                                    <span style="
                                                        display: inline-block;
                                                        max-width: 180px;
                                                        min-width: 80px;
                                                        white-space: nowrap;
                                                        overflow: hidden;
                                                        text-overflow: ellipsis;
                                                        font-size: clamp(0.85rem, 2vw, 1.125rem);
                                                        vertical-align: middle;
                                                    ">
                                                        <?php echo htmlspecialchars($note['doctor_name']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        <!-- RIGHT: Next Consultation & Button -->
                                        <div class="flex flex-col items-end justify-between">
                                            <div>
                                                <?php if (!empty($note['next_consultation_date'])): ?>
                                                    <span class="text-gray-400 text-sm mb-2 block">Next Consultation :</span>
                                                    <span
                                                        class="block px-4 py-2 rounded-md text-emerald-700 font-sm text-base"
                                                        style="background-color:#B1F3D4;color:#059669;">
                                                        <?php echo date('F d, Y', strtotime($note['next_consultation_date'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex justify-end mt-auto">
                                                <button
                                                    onclick="viewConsultationNote(<?php echo htmlspecialchars(json_encode($note)); ?>)"
                                                    class="rounded-md px-6 py-2 bg-blue-500 text-white font-medium text-md transition hover:bg-blue-600 focus:outline-none">
                                                    View Note
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $consultationIndex++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Patients Tab -->
                <div id="patients" class="tab-content <?= $activeTab === 'patients' ? 'active' : 'hidden' ?>">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <svg width="100" height="100" viewBox="0 0 100 100" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_1605_7820)">
                                        <path
                                            d="M88.889 16.668H11.1112C9.63779 16.668 8.22472 17.2533 7.18285 18.2952C6.14098 19.337 5.55566 20.7501 5.55566 22.2235V77.7791C5.55566 79.2525 6.14098 80.6656 7.18285 81.7075C8.22472 82.7493 9.63779 83.3346 11.1112 83.3346H88.889C90.3624 83.3346 91.7755 82.7493 92.8174 81.7075C93.8592 80.6656 94.4445 79.2525 94.4445 77.7791V22.2235C94.4445 20.7501 93.8592 19.337 92.8174 18.2952C91.7755 17.2533 90.3624 16.668 88.889 16.668ZM88.889 77.7791H11.1112V22.2235H88.889V77.7791Z"
                                            fill="black" fill-opacity="0.3" />
                                        <path
                                            d="M25.0004 38.8876H75.0004C75.7371 38.8876 76.4437 38.5949 76.9646 38.074C77.4856 37.5531 77.7782 36.8465 77.7782 36.1098C77.7782 35.3731 77.4856 34.6666 76.9646 34.1456C76.4437 33.6247 75.7371 33.332 75.0004 33.332H25.0004C24.2637 33.332 23.5572 33.6247 23.0362 34.1456C22.5153 34.6666 22.2227 35.3731 22.2227 36.1098C22.2227 36.8465 22.5153 37.5531 23.0362 38.074C23.5572 38.5949 24.2637 38.8876 25.0004 38.8876Z"
                                            fill="black" fill-opacity="0.3" />
                                        <path
                                            d="M25.0004 50.0009H75.0004C75.7371 50.0009 76.4437 49.7082 76.9646 49.1873C77.4856 48.6663 77.7782 47.9598 77.7782 47.2231C77.7782 46.4864 77.4856 45.7798 76.9646 45.2589C76.4437 44.738 75.7371 44.4453 75.0004 44.4453H25.0004C24.2637 44.4453 23.5572 44.738 23.0362 45.2589C22.5153 45.7798 22.2227 46.4864 22.2227 47.2231C22.2227 47.9598 22.5153 48.6663 23.0362 49.1873C23.5572 49.7082 24.2637 50.0009 25.0004 50.0009Z"
                                            fill="black" fill-opacity="0.3" />
                                        <path
                                            d="M25.0004 61.1102H52.7782C53.5149 61.1102 54.2215 60.8176 54.7424 60.2967C55.2633 59.7757 55.556 59.0692 55.556 58.3325C55.556 57.5958 55.2633 56.8892 54.7424 56.3683C54.2215 55.8473 53.5149 55.5547 52.7782 55.5547H25.0004C24.2637 55.5547 23.5572 55.8473 23.0362 56.3683C22.5153 56.8892 22.2227 57.5958 22.2227 58.3325C22.2227 59.0692 22.5153 59.7757 23.0362 60.2967C23.5572 60.8176 24.2637 61.1102 25.0004 61.1102Z"
                                            fill="black" fill-opacity="0.3" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_1605_7820">
                                            <rect width="100" height="100" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                            <h3 class="empty-state-title">No Personal Records</h3>
                            <p class="empty-state-text">Contact the health center to link and establish personal records for
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
                                    <div>
                                        <div>
                                            <?php if ($patientHasUpdate): ?>
                                                <span class="update-indicator update-indicator-mobile">
                                                    Updated
                                                    <?php echo date('M d, Y', strtotime($patientUpdatedAt)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h4 class="section-title">
                                            <svg class="w-8 h-8" viewBox="0 0 27 27" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M9.28125 6.75C9.28125 6.52622 9.37014 6.31161 9.52838 6.15338C9.68661 5.99515 9.90122 5.90625 10.125 5.90625H22.7812C23.005 5.90625 23.2196 5.99515 23.3779 6.15338C23.5361 6.31161 23.625 6.52622 23.625 6.75C23.625 6.97378 23.5361 7.18839 23.3779 7.34662C23.2196 7.50486 23.005 7.59375 22.7812 7.59375H10.125C9.90122 7.59375 9.68661 7.50486 9.52838 7.34662C9.37014 7.18839 9.28125 6.97378 9.28125 6.75ZM22.7812 12.6562H10.125C9.90122 12.6562 9.68661 12.7451 9.52838 12.9034C9.37014 13.0616 9.28125 13.2762 9.28125 13.5C9.28125 13.7238 9.37014 13.9384 9.52838 14.0966C9.68661 14.2549 9.90122 14.3438 10.125 14.3438H22.7812C23.005 14.3438 23.2196 14.2549 23.3779 14.0966C23.5361 13.9384 23.625 13.7238 23.625 13.5C23.625 13.2762 23.5361 13.0616 23.3779 12.9034C23.2196 12.7451 23.005 12.6562 22.7812 12.6562ZM22.7812 19.4062H10.125C9.90122 19.4062 9.68661 19.4951 9.52838 19.6534C9.37014 19.8116 9.28125 20.0262 9.28125 20.25C9.28125 20.4738 9.37014 20.6884 9.52838 20.8466C9.68661 21.0049 9.90122 21.0938 10.125 21.0938H22.7812C23.005 21.0938 23.2196 21.0049 23.3779 20.8466C23.5361 20.6884 23.625 20.4738 23.625 20.25C23.625 20.0262 23.5361 19.8116 23.3779 19.6534C23.2196 19.4951 23.005 19.4062 22.7812 19.4062ZM5.90625 5.90625H4.21875C3.99497 5.90625 3.78036 5.99515 3.62213 6.15338C3.4639 6.31161 3.375 6.52622 3.375 6.75C3.375 6.97378 3.4639 7.18839 3.62213 7.34662C3.78036 7.50486 3.99497 7.59375 4.21875 7.59375H5.90625C6.13003 7.59375 6.34464 7.50486 6.50287 7.34662C6.6611 7.18839 6.75 6.97378 6.75 6.75C6.75 6.52622 6.6611 6.31161 6.50287 6.15338C6.34464 5.99515 6.13003 5.90625 5.90625 5.90625ZM5.90625 12.6562H4.21875C3.99497 12.6562 3.78036 12.7451 3.62213 12.9034C3.4639 13.0616 3.375 13.2762 3.375 13.5C3.375 13.7238 3.4639 13.9384 3.62213 14.0966C3.78036 14.2549 3.99497 14.3438 4.21875 14.3438H5.90625C6.13003 14.3438 6.34464 14.2549 6.50287 14.0966C6.6611 13.9384 6.75 13.7238 6.75 13.5C6.75 13.2762 6.6611 13.0616 6.50287 12.9034C6.34464 12.7451 6.13003 12.6562 5.90625 12.6562ZM5.90625 19.4062H4.21875C3.99497 19.4062 3.78036 19.4951 3.62213 19.6534C3.4639 19.8116 3.375 20.0262 3.375 20.25C3.375 20.4738 3.4639 20.6884 3.62213 20.8466C3.78036 21.0049 3.99497 21.0938 4.21875 21.0938H5.90625C6.13003 21.0938 6.34464 21.0049 6.50287 20.8466C6.6611 20.6884 6.75 20.4738 6.75 20.25C6.75 20.0262 6.6611 19.8116 6.50287 19.6534C6.34464 19.4951 6.13003 19.4062 5.90625 19.4062Z"
                                                    fill="#3C96E1" />
                                            </svg>
                                            Identity Information
                                        </h4>

                                        <!--INFORMATION DATA  -->
                                        <div class="flex flex-col md:flex-row gap-8 w-full items-stretch">
                                            <div class="flex-1 bg-white border-1 shadow-md rounded-lg p-8 min-h-[120px] w-full">
                                                <!-- Full Name -->
                                                <div>
                                                    <p class="text-label text-gray-50 mb-2">Full Name</p>
                                                    <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                        <?php echo htmlspecialchars($patient['full_name']); ?>
                                                    </p>
                                                </div>

                                                <div class="grid grid-cols-2 md:grid-cols-2 gap-6 py-6 record-details-columns">
                                                    <!-- Date of Birth -->
                                                    <?php if (!empty($patient['date_of_birth'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Date of Birth</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Occupation -->
                                                    <?php if (!empty($patient['occupation'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Occupation</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['occupation']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Occupation</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Civil Status -->
                                                    <?php if (!empty($patient['civil_status'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Civil Status</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['civil_status']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Civil Status</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Family No. -->
                                                    <?php if (!empty($patient['family_no'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Family No.</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['family_no']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Family No.</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- PHIC No. -->
                                                    <?php if (!empty($patient['phic_no'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">PHIC No.</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['phic_no']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">PHIC No.</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- 4P's Member -->
                                                    <div>
                                                        <p class="text-label mb-2">4P's Member</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                            <?php echo (!empty($patient['fourps_member']) && $patient['fourps_member'] == 'Yes') ? 'Yes' : 'No'; ?>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div>
                                                    <!-- BHW Assigned -->
                                                    <?php if (!empty($patient['bhw_assigned'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">BHW Assigned</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['bhw_assigned']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">BHW Assigned</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">N/A
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex-1 bg-white border-1 shadow-md rounded-lg p-8 w-full">
                                                <div class="grid grid-cols-2 md:grid-cols-2 gap-6 record-details-columns">
                                                    <!-- Last Check-up -->
                                                    <div>
                                                        <p class="text-label mb-2">Last Check-up</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                            <?php echo (!empty($patient['last_checkup'])) ? date('M d, Y', strtotime($patient['last_checkup'])) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Consultation Type -->
                                                    <?php if (!empty($patient['consultation_type'])): ?>
                                                        <div>
                                                            <p class="text-label mb-2">Consultation Type</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                <?php echo htmlspecialchars($patient['consultation_type']); ?>
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div>
                                                            <p class="text-label mb-2">Consultation Type</p>
                                                            <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                                Onsite
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Record Status -->
                                                    <div>
                                                        <p class="text-label mb-2">Record Status</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded">
                                                            <span class="record-status-active">
                                                                <i class="fas fa-check-circle icon-xs"></i>Active
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <!-- Link Since -->
                                                    <div>
                                                        <p class="text-label mb-2">Link Since</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded text-base font-medium">
                                                            <?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information Section -->
                                    <!-- <?php if (!empty($patient['sitio']) || !empty($patient['contact']) || !empty($patient['disease'])): ?>
                                    <div class="rounded-2xl p-8 record-details-columns">
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
                <div id="medical" class="tab-content <?= $activeTab === 'medical' ? 'active' : 'hidden' ?>">
                    <?php if (empty($allPatientInfo)): ?>
                        <div class="text-center py-10 sm:py-20">
                            <div class="empty-state-icon">
                                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M85.9375 62.5C85.9375 63.4271 85.6626 64.3334 85.1475 65.1042C84.6324 65.8751 83.9004 66.4759 83.0438 66.8307C82.1873 67.1855 81.2448 67.2783 80.3355 67.0974C79.4262 66.9166 78.591 66.4701 77.9354 65.8146C77.2799 65.159 76.8334 64.3238 76.6526 63.4145C76.4717 62.5052 76.5645 61.5627 76.9193 60.7062C77.2741 59.8496 77.8749 59.1176 78.6458 58.6025C79.4166 58.0874 80.3229 57.8125 81.25 57.8125C82.4932 57.8125 83.6855 58.3064 84.5646 59.1854C85.4436 60.0645 85.9375 61.2568 85.9375 62.5ZM84.1602 77.8477C83.4737 82.2728 81.2288 86.307 77.8302 89.2228C74.4316 92.1387 70.103 93.7442 65.625 93.75H56.25C51.2788 93.7448 46.5126 91.7677 42.9975 88.2525C39.4823 84.7374 37.5052 79.9712 37.5 75V59.1758C31.4591 58.4147 25.9036 55.4752 21.876 50.909C17.8485 46.3427 15.6258 40.4637 15.625 34.375V15.625C15.625 14.7962 15.9542 14.0013 16.5403 13.4153C17.1263 12.8292 17.9212 12.5 18.75 12.5H28.125C28.9538 12.5 29.7487 12.8292 30.3347 13.4153C30.9208 14.0013 31.25 14.7962 31.25 15.625C31.25 16.4538 30.9208 17.2487 30.3347 17.8347C29.7487 18.4208 28.9538 18.75 28.125 18.75H21.875V34.375C21.8748 36.8585 22.3679 39.3172 23.3258 41.6085C24.2837 43.8998 25.6872 45.978 27.4548 47.7224C29.2225 49.4668 31.3191 50.8427 33.6229 51.7701C35.9267 52.6976 38.3918 53.1581 40.875 53.125C51.0742 52.9922 59.375 44.4336 59.375 34.0508V18.75H53.125C52.2962 18.75 51.5013 18.4208 50.9153 17.8347C50.3292 17.2487 50 16.4538 50 15.625C50 14.7962 50.3292 14.0013 50.9153 13.4153C51.5013 12.8292 52.2962 12.5 53.125 12.5H62.5C63.3288 12.5 64.1237 12.8292 64.7097 13.4153C65.2958 14.0013 65.625 14.7962 65.625 15.625V34.0508C65.625 46.8789 56.043 57.6016 43.75 59.1719V75C43.75 78.3152 45.067 81.4946 47.4112 83.8388C49.7554 86.183 52.9348 87.5 56.25 87.5H65.625C68.4636 87.4953 71.2164 86.5264 73.4322 84.7521C75.648 82.9778 77.1952 80.5033 77.8203 77.7344C74.0462 76.8866 70.7208 74.6691 68.4874 71.5109C66.254 68.3527 65.2714 64.4783 65.7299 60.6374C66.1883 56.7966 68.0551 53.2623 70.9691 50.7184C73.8831 48.1746 77.637 46.8021 81.5046 46.8664C85.3722 46.9307 89.0785 48.4274 91.9062 51.0667C94.734 53.7061 96.4822 57.3005 96.8126 61.1545C97.1431 65.0085 96.0322 68.8481 93.695 71.9302C91.3578 75.0124 87.9604 77.1181 84.1602 77.8398V77.8477ZM90.625 62.5C90.625 60.6458 90.0752 58.8332 89.045 57.2915C88.0149 55.7498 86.5507 54.5482 84.8377 53.8386C83.1246 53.1291 81.2396 52.9434 79.421 53.3051C77.6025 53.6669 75.932 54.5598 74.6209 55.8709C73.3098 57.182 72.4169 58.8525 72.0551 60.671C71.6934 62.4896 71.8791 64.3746 72.5886 66.0877C73.2982 67.8007 74.4998 69.2649 76.0415 70.295C77.5832 71.3252 79.3958 71.875 81.25 71.875C83.7364 71.875 86.121 70.8873 87.8791 69.1291C89.6373 67.371 90.625 64.9864 90.625 62.5Z"
                                        fill="black" fill-opacity="0.3" />
                                </svg>
                            </div>
                            <h3 class="empty-state-title">No Health Records Yet</h3>
                            <p class="empty-state-text">Contact the health center to link and established health records for
                                your account.</p>
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
                                    <svg width="100" height="100" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M34.3743 15.625C35.6105 15.625 36.8188 15.2584 37.8466 14.5717C38.8745 13.8849 39.6755 12.9088 40.1486 11.7668C40.6216 10.6247 40.7454 9.36807 40.5042 8.15569C40.2631 6.94331 39.6678 5.82966 38.7937 4.95559C37.9197 4.08151 36.806 3.48625 35.5936 3.24509C34.3813 3.00394 33.1246 3.12771 31.9826 3.60076C30.8405 4.0738 29.8644 4.87488 29.1776 5.90269C28.4909 6.9305 28.1243 8.13887 28.1243 9.375C28.1243 11.0326 28.7828 12.6223 29.9549 13.7944C31.127 14.9665 32.7167 15.625 34.3743 15.625ZM34.3743 6.25C34.9924 6.25 35.5966 6.43328 36.1105 6.77666C36.6244 7.12004 37.0249 7.6081 37.2615 8.17912C37.498 8.75014 37.5599 9.37847 37.4393 9.98466C37.3187 10.5909 37.0211 11.1477 36.584 11.5847C36.147 12.0218 35.5902 12.3194 34.984 12.44C34.3778 12.5605 33.7495 12.4987 33.1784 12.2621C32.6074 12.0256 32.1194 11.6251 31.776 11.1112C31.4326 10.5973 31.2493 9.99307 31.2493 9.375C31.2493 8.5462 31.5786 7.75134 32.1646 7.16529C32.7507 6.57924 33.5455 6.25 34.3743 6.25ZM32.8118 32.8125C32.8118 35.2848 32.0787 37.7015 30.7052 39.7571C29.3317 41.8127 27.3794 43.4149 25.0954 44.361C22.8113 45.3071 20.298 45.5546 17.8732 45.0723C15.4484 44.59 13.2211 43.3995 11.473 41.6513C9.72484 39.9032 8.53433 37.6759 8.05201 35.2511C7.5697 32.8264 7.81724 30.313 8.76333 28.029C9.70943 25.7449 11.3116 23.7926 13.3672 22.4191C15.4228 21.0456 17.8396 20.3125 20.3118 20.3125C20.7262 20.3125 21.1237 20.4771 21.4167 20.7701C21.7097 21.0632 21.8743 21.4606 21.8743 21.875C21.8743 22.2894 21.7097 22.6868 21.4167 22.9799C21.1237 23.2729 20.7262 23.4375 20.3118 23.4375C18.4576 23.4375 16.6451 23.9873 15.1034 25.0175C13.5616 26.0476 12.36 27.5118 11.6505 29.2248C10.9409 30.9379 10.7552 32.8229 11.117 34.6415C11.4787 36.46 12.3716 38.1305 13.6827 39.4416C14.9938 40.7527 16.6643 41.6456 18.4829 42.0074C20.3014 42.3691 22.1864 42.1834 23.8995 41.4739C25.6125 40.7643 27.0767 39.5627 28.1069 38.021C29.137 36.4793 29.6868 34.6667 29.6868 32.8125C29.6868 32.3981 29.8514 32.0007 30.1445 31.7076C30.4375 31.4146 30.8349 31.25 31.2493 31.25C31.6637 31.25 32.0612 31.4146 32.3542 31.7076C32.6472 32.0007 32.8118 32.3981 32.8118 32.8125ZM40.2708 25.5723C40.4171 25.7512 40.5219 25.9603 40.5776 26.1845C40.6333 26.4088 40.6386 26.6426 40.5931 26.8691L37.4681 42.4941C37.3972 42.8482 37.206 43.1667 36.9268 43.3956C36.6477 43.6246 36.2979 43.7498 35.9368 43.75C35.8332 43.7502 35.7298 43.7397 35.6282 43.7188C35.2222 43.6372 34.8652 43.3979 34.6356 43.0532C34.406 42.7086 34.3225 42.287 34.4036 41.8809L37.1556 28.125H24.9993C24.7249 28.1254 24.4551 28.0535 24.2173 27.9165C23.9794 27.7796 23.7818 27.5823 23.6444 27.3448C23.507 27.1072 23.4346 26.8375 23.4345 26.5631C23.4344 26.2886 23.5066 26.019 23.6439 25.7813L27.5618 18.9648C24.8378 17.542 21.755 16.953 18.6983 17.2716C15.6416 17.5901 12.7465 18.802 10.3743 20.7559C10.2194 20.9038 10.0358 21.0184 9.83477 21.0924C9.63374 21.1664 9.41965 21.1983 9.20578 21.1861C8.99192 21.1739 8.78285 21.1178 8.59157 21.0214C8.40028 20.9249 8.23087 20.7902 8.09385 20.6256C7.95684 20.4609 7.85514 20.2698 7.79509 20.0642C7.73503 19.8586 7.71789 19.6428 7.74475 19.4303C7.7716 19.2177 7.84186 19.013 7.95118 18.8288C8.06049 18.6446 8.20652 18.4848 8.38019 18.3594C11.4593 15.8185 15.269 14.3267 19.2548 14.1012C23.2405 13.8757 27.1942 14.9282 30.5403 17.1055C30.875 17.3234 31.1135 17.6612 31.2068 18.0495C31.3002 18.4378 31.2413 18.8471 31.0423 19.1934L27.6985 25H39.0618C39.2931 25 39.5216 25.0513 39.7307 25.1502C39.9397 25.2492 40.1242 25.3933 40.2708 25.5723Z" fill="black"/>
</svg>

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
                                    $medicalCreatedAt = $patient['created_at'] ?? null;
                                    $medicalHasUpdate = false;
                                    if (!empty($medicalUpdatedAt) && !empty($medicalCreatedAt) && strtotime($medicalUpdatedAt) > strtotime($medicalCreatedAt)) {
                                        $medicalHasUpdate = true;
                                    }
                                    ?>
                                    <div class="<?php echo $medicalHasUpdate ?: ''; ?>">
                                        <div class="flex items-center justify-between ">
                                            <?php if ($medicalHasUpdate): ?>
                                                <span class="update-indicator">
                                                    <!-- <i class="fas fa-rotate"></i> -->
                                                    Updated <?php echo date('M d, Y', strtotime($medicalUpdatedAt)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Two Column Layout: Vital Statistics and Medical Details -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 md:gap-8">
                                            <!-- Vital Statistics Section -->
                                            <div class="flex-1 bg-white p-8 border-1 shadow-md rounded-lg">
                                                <h4 class="text-xl font-medium mb-12 sm:mb-14 flex items-center text-center gap-2">
                                                    <svg class="w-8 h-8" viewBox="0 0 27 27" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M9.28125 6.75C9.28125 6.52622 9.37014 6.31161 9.52838 6.15338C9.68661 5.99515 9.90122 5.90625 10.125 5.90625H22.7812C23.005 5.90625 23.2196 5.99515 23.3779 6.15338C23.5361 6.31161 23.625 6.52622 23.625 6.75C23.625 6.97378 23.5361 7.18839 23.3779 7.34662C23.2196 7.50486 23.005 7.59375 22.7812 7.59375H10.125C9.90122 7.59375 9.68661 7.50486 9.52838 7.34662C9.37014 7.18839 9.28125 6.97378 9.28125 6.75ZM22.7812 12.6562H10.125C9.90122 12.6562 9.68661 12.7451 9.52838 12.9034C9.37014 13.0616 9.28125 13.2762 9.28125 13.5C9.28125 13.7238 9.37014 13.9384 9.52838 14.0966C9.68661 14.2549 9.90122 14.3438 10.125 14.3438H22.7812C23.005 14.3438 23.2196 14.2549 23.3779 14.0966C23.5361 13.9384 23.625 13.7238 23.625 13.5C23.625 13.2762 23.5361 13.0616 23.3779 12.9034C23.2196 12.7451 23.005 12.6562 22.7812 12.6562ZM22.7812 19.4062H10.125C9.90122 19.4062 9.68661 19.4951 9.52838 19.6534C9.37014 19.8116 9.28125 20.0262 9.28125 20.25C9.28125 20.4738 9.37014 20.6884 9.52838 20.8466C9.68661 21.0049 9.90122 21.0938 10.125 21.0938H22.7812C23.005 21.0938 23.2196 21.0049 23.3779 20.8466C23.5361 20.6884 23.625 20.4738 23.625 20.25C23.625 20.0262 23.5361 19.8116 23.3779 19.6534C23.2196 19.4951 23.005 19.4062 22.7812 19.4062ZM5.90625 5.90625H4.21875C3.99497 5.90625 3.78036 5.99515 3.62213 6.15338C3.4639 6.31161 3.375 6.52622 3.375 6.75C3.375 6.97378 3.4639 7.18839 3.62213 7.34662C3.78036 7.50486 3.99497 7.59375 4.21875 7.59375H5.90625C6.13003 7.59375 6.34464 7.50486 6.50287 7.34662C6.6611 7.18839 6.75 6.97378 6.75 6.75C6.75 6.52622 6.6611 6.31161 6.50287 6.15338C6.34464 5.99515 6.13003 5.90625 5.90625 5.90625ZM5.90625 12.6562H4.21875C3.99497 12.6562 3.78036 12.7451 3.62213 12.9034C3.4639 13.0616 3.375 13.2762 3.375 13.5C3.375 13.7238 3.4639 13.9384 3.62213 14.0966C3.78036 14.2549 3.99497 14.3438 4.21875 14.3438H5.90625C6.13003 14.3438 6.34464 14.2549 6.50287 14.0966C6.6611 13.9384 6.75 13.7238 6.75 13.5C6.75 13.2762 6.6611 13.0616 6.50287 12.9034C6.34464 12.7451 6.13003 12.6562 5.90625 12.6562ZM5.90625 19.4062H4.21875C3.99497 19.4062 3.78036 19.4951 3.62213 19.6534C3.4639 19.8116 3.375 20.0262 3.375 20.25C3.375 20.4738 3.4639 20.6884 3.62213 20.8466C3.78036 21.0049 3.99497 21.0938 4.21875 21.0938H5.90625C6.13003 21.0938 6.34464 21.0049 6.50287 20.8466C6.6611 20.6884 6.75 20.4738 6.75 20.25C6.75 20.0262 6.6611 19.8116 6.50287 19.6534C6.34464 19.4951 6.13003 19.4062 5.90625 19.4062Z"
                                                            fill="#2e8ad6" />
                                                    </svg>
                                                    Vital Statistics
                                                </h4>
                                                <div class="grid grid-cols-2 gap-2 sm:gap-4">
                                                    <!-- Blood Type & Height Row -->
                                                    <div>
                                                        <p class="text-label mb-2">Blood Type</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['blood_type']) ? htmlspecialchars($patient['blood_type']) : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">Height</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['height']) ? htmlspecialchars($patient['height']) . ' cm' : 'N/A'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Weight & BMI Row -->
                                                    <div>
                                                        <p class="text-label mb-2">Weight</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['weight']) ? htmlspecialchars($patient['weight']) . ' kg' : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">BMI</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
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
                                                        <p class="text-label mb-2">Temperature</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['temperature']) ? htmlspecialchars($patient['temperature']) . ' °C' : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">Blood Pressure</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-emerald-500 font-500">
                                                            <?php echo !empty($patient['blood_pressure']) ? htmlspecialchars($patient['blood_pressure']) : 'N/A'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Medical Details Section -->
                                            <div class="flex-1 bg-white p-8 border-1 shadow-md rounded-lg">
                                                <h4 class="text-xl font-medium mb-12 sm:mb-14 flex items-center text-center gap-2">
                                                    <svg class="w-8 h-8" viewBox="0 0 27 27" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M9.28125 6.75C9.28125 6.52622 9.37014 6.31161 9.52838 6.15338C9.68661 5.99515 9.90122 5.90625 10.125 5.90625H22.7812C23.005 5.90625 23.2196 5.99515 23.3779 6.15338C23.5361 6.31161 23.625 6.52622 23.625 6.75C23.625 6.97378 23.5361 7.18839 23.3779 7.34662C23.2196 7.50486 23.005 7.59375 22.7812 7.59375H10.125C9.90122 7.59375 9.68661 7.50486 9.52838 7.34662C9.37014 7.18839 9.28125 6.97378 9.28125 6.75ZM22.7812 12.6562H10.125C9.90122 12.6562 9.68661 12.7451 9.52838 12.9034C9.37014 13.0616 9.28125 13.2762 9.28125 13.5C9.28125 13.7238 9.37014 13.9384 9.52838 14.0966C9.68661 14.2549 9.90122 14.3438 10.125 14.3438H22.7812C23.005 14.3438 23.2196 14.2549 23.3779 14.0966C23.5361 13.9384 23.625 13.7238 23.625 13.5C23.625 13.2762 23.5361 13.0616 23.3779 12.9034C23.2196 12.7451 23.005 12.6562 22.7812 12.6562ZM22.7812 19.4062H10.125C9.90122 19.4062 9.68661 19.4951 9.52838 19.6534C9.37014 19.8116 9.28125 20.0262 9.28125 20.25C9.28125 20.4738 9.37014 20.6884 9.52838 20.8466C9.68661 21.0049 9.90122 21.0938 10.125 21.0938H22.7812C23.005 21.0938 23.2196 21.0049 23.3779 20.8466C23.5361 20.6884 23.625 20.4738 23.625 20.25C23.625 20.0262 23.5361 19.8116 23.3779 19.6534C23.2196 19.4951 23.005 19.4062 22.7812 19.4062ZM5.90625 5.90625H4.21875C3.99497 5.90625 3.78036 5.99515 3.62213 6.15338C3.4639 6.31161 3.375 6.52622 3.375 6.75C3.375 6.97378 3.4639 7.18839 3.62213 7.34662C3.78036 7.50486 3.99497 7.59375 4.21875 7.59375H5.90625C6.13003 7.59375 6.34464 7.50486 6.50287 7.34662C6.6611 7.18839 6.75 6.97378 6.75 6.75C6.75 6.52622 6.6611 6.31161 6.50287 6.15338C6.34464 5.99515 6.13003 5.90625 5.90625 5.90625ZM5.90625 12.6562H4.21875C3.99497 12.6562 3.78036 12.7451 3.62213 12.9034C3.4639 13.0616 3.375 13.2762 3.375 13.5C3.375 13.7238 3.4639 13.9384 3.62213 14.0966C3.78036 14.2549 3.99497 14.3438 4.21875 14.3438H5.90625C6.13003 14.3438 6.34464 14.2549 6.50287 14.0966C6.6611 13.9384 6.75 13.7238 6.75 13.5C6.75 13.2762 6.6611 13.0616 6.50287 12.9034C6.34464 12.7451 6.13003 12.6562 5.90625 12.6562ZM5.90625 19.4062H4.21875C3.99497 19.4062 3.78036 19.4951 3.62213 19.6534C3.4639 19.8116 3.375 20.0262 3.375 20.25C3.375 20.4738 3.4639 20.6884 3.62213 20.8466C3.78036 21.0049 3.99497 21.0938 4.21875 21.0938H5.90625C6.13003 21.0938 6.34464 21.0049 6.50287 20.8466C6.6611 20.6884 6.75 20.4738 6.75 20.25C6.75 20.0262 6.6611 19.8116 6.50287 19.6534C6.34464 19.4951 6.13003 19.4062 5.90625 19.4062Z"
                                                            fill="#2e8ad6" />
                                                    </svg>
                                                    Medical Details
                                                </h4>
                                                <div class="grid sm:grid-cols-2 gap-4">
                                                    <!-- Allergies & Current Medications Row -->
                                                    <div>
                                                        <p class="text-label mb-2">Allergies</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">Current Medications</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['current_medications']) ? htmlspecialchars($patient['current_medications']) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Chronic Conditions & Immunization Row -->
                                                    <div>
                                                        <p class="text-label mb-2">Chronic Conditions</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['chronic_conditions']) ? htmlspecialchars($patient['chronic_conditions']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">Immunization Record</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['immunization_record']) ? htmlspecialchars($patient['immunization_record']) : 'None'; ?>
                                                        </p>
                                                    </div>

                                                    <!-- Medical History & Family Medical History Row -->
                                                    <div>
                                                        <p class="text-label mb-2">Medical History</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
                                                            <?php echo !empty($patient['medical_history']) ? htmlspecialchars($patient['medical_history']) : 'None'; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-label mb-2">Family Medical History</p>
                                                        <p class="px-4 py-4 bg-gray-100 rounded-lg text-gray-900 font-500">
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
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-8 py-6 sticky top-0 bg-white z-10 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-semibold text-gray-900" id="modalTitle">Consultation Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="px-8 py-6 space-y-6" id="modalBody"></div>
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
                ? `<div class="text-right">
                        <p class="text-base font-normal text-gray-500 mb-1">Attending Physician :</p>
                        <p class="text-[30px] font-semibold text-gray-900 leading-tight">${note.doctor_name}</p>
                   </div>`
                : '';

            let dateHtml = `
                    <div>
                        <p class="text-base font-normal text-gray-500 mb-1">Date of Visit :</p>
                        <p class="text-[30px] font-semibold text-gray-900 leading-tight">${consultationDate}</p>
                    </div>
            `;

            let headerMeta = `
                <div class="flex items-start justify-between gap-4 mb-5">
                    ${dateHtml}
                    ${doctorHtml}
                </div>
            `;

            // Personal Info Section removed for modal display
            let personalInfoHtml = '';

            let noteContent = note.note
                ? `<p class="font-medium text-base leading-7 whitespace-pre-wrap">${note.note}</p>`
                : `<p class="text-gray-400 italic text-center py-4">No detailed notes were recorded for this session.</p>`;

            let nextVisitHtml = '';
            if (note.next_consultation_date) {
                const nextDate = new Date(note.next_consultation_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                nextVisitHtml = `
                    <div class="pt-3 pb-2">
                        <div>
                            <p class="text-base font-normal text-gray-500 mb-2">Next Consultation Schedule :</p>
                            <p class="inline-block text-[28px] font-md text-green-600 bg-emerald-100 px-3 py-2 rounded-lg leading-tight">${nextDate}</p>
                        </div>
                    </div>
                `;
            }

            let bodyContent = `
                <div>
                    ${headerMeta}
                    <div class="mb-2">
                       <h4 class="inline-flex items-center gap-2 text-[30px] font-medium py-2 px-4 rounded-lg text-blue-600 mb-4" style="background-color: rgba(37, 99, 235, 0.3);">
                            Consultation Note
                         </h4>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 min-h-[130px]">
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
                    .consultation-header-group {
                        background: rgba(37, 99, 235, 0.3);
                        color: #2563EB;
                        border-radius: 8px;
                        font-size: 1rem;
                        font-weight: 500;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.5rem 1.25rem;
                    }
                    .consultation-count-number {
                        color: #2563EB;
                        border-radius: 50%;
                        width: 1.75rem;
                        height: 1.75rem;
                        min-width: 1.75rem;
                        min-height: 1.75rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1rem;
                        font-weight: 400;
                        margin-left: 0.5rem;
                    }
                    .doctor-name-auto-shrink {
                        max-width: 180px;
                        min-width: 80px;
                        display: inline-block;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        font-size: 1.125rem;
                        vertical-align: middle;
                    }
                    @media (max-width: 640px) {
                        .doctor-name-auto-shrink {
                            max-width: 120px;
                            min-width: 60px;
                            font-size: 1rem;
                        }
                    }
                    @supports (font-size: clamp(0.85rem, 2vw, 1.125rem)) {
                        .doctor-name-auto-shrink {
                            font-size: clamp(0.85rem, 2vw, 1.125rem);
                        }
                    }
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var phpActiveTab = '<?= $activeTab ?>';
            setTimeout(function () {
                if (typeof switchTab === 'function') {
                    switchTab(phpActiveTab);
                } else {
                    document.querySelectorAll('.tab-header').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    var activeButton = document.querySelector('.tab-header[data-tab="' + phpActiveTab + '"]');
                    if (activeButton) activeButton.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                        content.classList.add('hidden');
                    });
                    var tabContent = document.getElementById(phpActiveTab);
                    if (tabContent) {
                        tabContent.classList.remove('hidden');
                        tabContent.classList.add('active');
                    }
                }
            }, 100);
        });
    </script>
</div>
