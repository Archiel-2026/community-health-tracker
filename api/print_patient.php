<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit('Not authenticated');
}

// Check if user is staff
if (!isStaff()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Get user permissions
require_once __DIR__ . '/../includes/functions.php';

// All authenticated staff can print all records
$canPrint = true;

$patientId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$noteId = isset($_GET['note_id']) ? intval($_GET['note_id']) : 0;

if ($patientId <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit('Patient ID is required');
}

// Check if database connection exists
if (!isset($pdo) || !$pdo) {
    die('Database connection error. Please check your database configuration.');
}

try {
    // First, check if the patient exists
    $checkStmt = $pdo->prepare("SELECT id, full_name FROM sitio1_patients WHERE id = ? AND deleted_at IS NULL");
    $checkStmt->execute([$patientId]);
    $patientExists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patientExists) {
        header('HTTP/1.0 404 Not Found');
        exit('Patient not found');
    }
    
    // Fetch full patient data - NO added_by restriction
    $query = "SELECT 
        p.id,
        p.user_id,
        p.full_name,
        p.age,
        p.date_of_birth,
        p.address,
        p.contact,
        p.bhw_assigned,
        p.last_checkup,
        p.created_at,
        p.updated_at,
        p.phic_no,
        p.family_no,
        p.fourps_member,
        p.consent_given,
        p.consent_date,
        u.id as user_id,
        u.full_name as user_full_name,
        u.email as user_email, 
        u.age as user_age,
        u.gender as user_gender,
        u.occupation as user_occupation,
        u.unique_number,
        u.date_of_birth as user_date_of_birth
    FROM sitio1_patients p 
    LEFT JOIN sitio1_users u ON p.user_id = u.id
    WHERE p.id = ? AND p.deleted_at IS NULL";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header('HTTP/1.0 404 Not Found');
        exit('Patient data not found');
    }
    
    // Get health information from existing_info_patients
    $healthInfo = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM existing_info_patients WHERE patient_id = ?");
        $stmt->execute([$patientId]);
        $healthInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$healthInfo) {
            $healthInfo = [];
        }
    } catch (PDOException $e) {
        error_log('existing_info_patients table error: ' . $e->getMessage());
        $healthInfo = [];
    }
    
    // Get doctor name from consultation notes
    $currentDoctor = '';
    try {
        if ($noteId > 0) {
            $stmt = $pdo->prepare("SELECT doctor_name FROM consultation_notes WHERE id = ? AND patient_id = ?");
            $stmt->execute([$noteId, $patientId]);
            $currentNote = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($currentNote && !empty($currentNote['doctor_name'])) {
                $currentDoctor = $currentNote['doctor_name'];
            }
        }
        
        if (empty($currentDoctor)) {
            $stmt = $pdo->prepare("SELECT doctor_name FROM consultation_notes WHERE patient_id = ? ORDER BY consultation_date DESC, created_at DESC LIMIT 1");
            $stmt->execute([$patientId]);
            $latestNote = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($latestNote && !empty($latestNote['doctor_name'])) {
                $currentDoctor = $latestNote['doctor_name'];
            }
        }
    } catch (PDOException $e) {
        error_log('consultation_notes table error: ' . $e->getMessage());
    }
    
    // Fallback to staff name if no doctor found
    if (empty($currentDoctor)) {
        $currentDoctor = $_SESSION['user']['full_name'] ?? 'School Physician';
    }
    
    // Get visit history
    $visits = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM patient_visits WHERE patient_id = ? ORDER BY visit_date DESC LIMIT 10");
        $stmt->execute([$patientId]);
        $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Visit history not available: " . $e->getMessage());
        $visits = [];
    }
    
} catch (PDOException $e) {
    error_log('Database error in print_patient.php: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit('Database error: ' . $e->getMessage());
}

// Format display values
$displayFullName = $patient['full_name'] ?? 'Unknown Patient';
$dateOfBirthDisplay = '';
if (!empty($patient['date_of_birth'])) {
    $dateOfBirthDisplay = date('F j, Y', strtotime($patient['date_of_birth']));
} elseif (!empty($patient['user_date_of_birth'])) {
    $dateOfBirthDisplay = date('F j, Y', strtotime($patient['user_date_of_birth']));
}

// Calculate age if not present
$age = $patient['age'] ?? $patient['user_age'] ?? '';
if (empty($age) && !empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $dob->diff($today)->y;
}

// Get data
$gender = $patient['user_gender'] ?? '';
$userEmail = $patient['user_email'] ?? '';

// Get BHW Assigned
$bhwAssigned = !empty($patient['bhw_assigned']) ? $patient['bhw_assigned'] : 'School Nurse';

// Staff license number
$staffLicenseNo = 'PRC-' . str_pad($_SESSION['user']['id'] ?? '0000', 6, '0', STR_PAD_LEFT);

// Helper function to format health values
function formatHealthValue($value, $default = 'Not recorded') {
    return (!empty($value) && $value !== '') ? htmlspecialchars($value) : '<span class="empty-data">' . $default . '</span>';
}

// Helper function to format allergy items
function formatAllergyItems($healthInfo) {
    $allergies = [];
    if (!empty($healthInfo['student_allergy_items'])) {
        if (is_string($healthInfo['student_allergy_items'])) {
            $items = json_decode($healthInfo['student_allergy_items'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($items)) {
                $allergies = $items;
            } else {
                $allergies = explode(',', $healthInfo['student_allergy_items']);
            }
        } elseif (is_array($healthInfo['student_allergy_items'])) {
            $allergies = $healthInfo['student_allergy_items'];
        }
    }
    if (!empty($healthInfo['student_allergy_other'])) {
        $allergies[] = $healthInfo['student_allergy_other'];
    }
    $allergies = array_map('trim', $allergies);
    $allergies = array_filter($allergies);
    return !empty($allergies) ? implode(', ', $allergies) : '';
}

// Helper function to format conditions
function formatConditions($healthInfo) {
    $conditions = [];
    if (!empty($healthInfo['student_conditions'])) {
        if (is_string($healthInfo['student_conditions'])) {
            $items = json_decode($healthInfo['student_conditions'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($items)) {
                $conditions = $items;
            } else {
                $conditions = explode(',', $healthInfo['student_conditions']);
            }
        } elseif (is_array($healthInfo['student_conditions'])) {
            $conditions = $healthInfo['student_conditions'];
        }
    }
    if (!empty($healthInfo['student_condition_other'])) {
        $conditions[] = $healthInfo['student_condition_other'];
    }
    $conditions = array_map('trim', $conditions);
    $conditions = array_filter($conditions);
    return !empty($conditions) ? implode(', ', $conditions) : '';
}

// Helper function for yes/no display
function formatYesNo($value) {
    if ($value === 'yes' || $value === 'Yes' || $value === 'YES') return 'Yes';
    if ($value === 'no' || $value === 'No' || $value === 'NO') return 'No';
    return '<span class="empty-data">Not specified</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Record - <?= htmlspecialchars($displayFullName) ?> - Cebu Eastern College</title>
    <link rel="icon" type="image/png" href="/community-health-tracker/asssets/images/finallogo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: letter;
            margin: 0.5in;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Poppins', 'Times New Roman', Arial, sans-serif;
            margin: 0;
            padding: 0.5in;
            color: #000;
            background: #ffffff;
            line-height: 1.3;
            font-size: 11pt;
        }
        
        .document-container {
            max-width: 8.5in;
            margin: 0 auto;
            background: white;
        }
        
        /* Warm Blue Theme Header */
        .college-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3C96E1;
        }
        
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .logo-left, .logo-right {
            width: 120px;
            text-align: center;
        }
        
        /* .logo-circle {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            background: white;
            overflow: hidden;
        } */
        
        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .logo-circle.fallback::before {
            content: attr(data-fallback);
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            line-height: 1.2;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
        }
        
        .header-center {
            flex: 1;
            text-align: center;
            padding: 0 15px;
        }
        
        .header-center h1 {
            font-size: 20pt;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            color: #3C96E1;
        }
        
        .header-center h2 {
            font-size: 14pt;
            font-weight: 600;
            margin: 5px 0;
            color: #2c7cb6;
        }
        
        .header-center p {
            font-size: 10pt;
            margin: 2px 0;
            color: #666;
        }
        
        .document-title {
            text-align: center;
            margin: 25px 0;
            padding: 10px 0;
            border-top: 2px solid #3C96E1;
            border-bottom: 2px solid #3C96E1;
            background: #f0f8ff;
        }
        
        .document-title h1 {
            font-size: 18pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #3C96E1;
            letter-spacing: 1px;
        }
        
        .document-title p {
            font-size: 11pt;
            margin-top: 5px;
            color: #5aadf0;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #3C96E1;
            color: white;
            padding: 8px 12px;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 12pt;
            border-radius: 3px;
        }
        
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
            font-size: 10pt;
        }
        
        .info-table th, .info-table td { 
            border: 1px solid #b3d9f7; 
            padding: 8px 10px; 
            text-align: left; 
            vertical-align: top;
        }
        
        .info-table th { 
            background: #e6f3ff;
            font-weight: 600;
            width: 25%;
            color: #2c7cb6;
        }
        
        .empty-data {
            color: #999;
            font-style: italic;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #3C96E1;
            margin: 40px 0 8px;
            padding-bottom: 4px;
            min-height: 30px;
            position: relative;
        }
        
        .signature-label {
            font-weight: 600;
            margin-top: 5px;
            font-size: 10pt;
            color: #3C96E1;
        }
        
        .signature-details {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }
        
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 8pt; 
            color: #666;
            border-top: 1px solid #3C96E1;
            padding-top: 10px;
        }
        
        .control-buttons {
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            z-index: 1000;
            display: flex;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .control-btn {
            background: #3C96E1;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
        }
        
        .control-btn:hover {
            background: #2c7cb6;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0.5in;
            }
            .control-buttons { display: none !important; }
            .section-title {
                background: #3C96E1;
                color: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .document-title {
                background: #f0f8ff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .info-table th {
                background: #e6f3ff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        .warning-banner {
            background: #e6f3ff;
            border: 1px solid #3C96E1;
            color: #3C96E1;
            padding: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 9pt;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    
    <div class="document-container">
        <!-- Header with CEC Logo (Left) and DOH Logo (Right) -->
        <div class="college-header">
            <div class="header-row">
                <div class="logo-left">
                    <div class="logo-circle" id="cecLogo" data-fallback="CEC">
                        <img src="../asssets/images/finallogo.png" alt="Cebu Eastern College Logo" 
                             onerror="this.style.display='none'; this.parentElement.classList.add('fallback')">
                    </div>
                </div>
                <div class="header-center">
                    <h1>CEBU EASTERN COLLEGE</h1>
                    <h2>Student Health Services</h2>
                    <p>Cebu City, Philippines 6000</p>
                    <p>(032) 123-4567 | ✉️ clinic@cebueastern.edu.ph</p>
                </div>
                <div class="logo-right">
                    <div class="logo-circle" id="dohLogo" data-fallback="DOH">
                        <img src="../asssets/images/DOH.webp" alt="Department of Health Logo" 
                             onerror="this.style.display='none'; this.parentElement.classList.add('fallback')">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document Title -->
        <div class="document-title">
            <h1>COMPLETE STUDENT HEALTH RECORDS</h1>
            <p>Cebu Eastern College Student Clinic Records</p>
        </div>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <p><strong>Student Record ID:</strong> CEC-<?= str_pad($patientId, 6, '0', STR_PAD_LEFT) ?> | <strong>Date Issued:</strong> <?= date('F j, Y') ?></p>
            <?php if (!empty($patient['unique_number'])): ?>
            <p><strong>Student Number:</strong> <?= htmlspecialchars($patient['unique_number']) ?></p>
            <?php endif; ?>
        </div>
        
        <!-- SECTION I: PERSONAL INFORMATION -->
        <div class="section">
            <div class="section-title">I. STUDENT PERSONAL INFORMATION</div>
            <table class="info-table">
                <tr>
                    <th>Last Name</th>
                    <td><?= formatHealthValue($healthInfo['student_last_name'] ?? '') ?></td>
                    <th>First Name</th>
                    <td><?= formatHealthValue($healthInfo['student_first_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Middle Name</th>
                    <td><?= formatHealthValue($healthInfo['student_middle_name'] ?? '') ?></td>
                    <th>Full Name</th>
                    <td><strong><?= htmlspecialchars($displayFullName) ?></strong></td>
                </tr>
                <tr>
                    <th>Sex</th>
                    <td><?= formatHealthValue($healthInfo['student_sex'] ?? $gender) ?></td>
                    <th>Date of Birth</th>
                    <td><?= !empty($dateOfBirthDisplay) ? $dateOfBirthDisplay : '<span class="empty-data">Not specified</span>' ?></td>
                </tr>
                <tr>
                    <th>Age</th>
                    <td><?= $age ?: '<span class="empty-data">Not specified</span>' ?> years old</td>
                    <th>Contact Number</th>
                    <td><?= formatHealthValue($healthInfo['student_mobile_number'] ?? $patient['contact'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Complete Address</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_home_address'] ?? $patient['address'] ?? '') ?></td>
                </tr>
                <?php if (!empty($userEmail)): ?>
                <tr>
                    <th>Email Address</th>
                    <td colspan="3"><?= htmlspecialchars($userEmail) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Religion</th>
                    <td><?= formatHealthValue($healthInfo['student_religion'] ?? '') ?></td>
                    <th>Nickname</th>
                    <td><?= formatHealthValue($healthInfo['student_nickname'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Parent/Guardian</th>
                    <td><?= formatHealthValue($healthInfo['parent_guardian_name'] ?? '') ?></td>
                    <th>Parent/Guardian Occupation</th>
                    <td><?= formatHealthValue($healthInfo['parent_guardian_occupation'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Student Type</th>
                    <td colspan="3"><?= !empty($patient['unique_number']) ? 'Registered Student' : 'Walk-in Student' ?></td>
                </tr>
            </table>
        </div>
        
        <!-- SECTION II: HEALTH INFORMATION -->
        <div class="section">
            <div class="section-title">II. HEALTH INFORMATION & MEDICAL HISTORY</div>
            <table class="info-table">
                <tr>
                    <th>Blood Type</th>
                    <td><?= formatHealthValue($healthInfo['blood_type'] ?? '') ?></td>
                    <th>Blood Pressure</th>
                    <td><?= formatHealthValue($healthInfo['blood_pressure'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>In good health?</th>
                    <td><?= formatYesNo($healthInfo['student_good_health'] ?? '') ?></td>
                    <th>Under medical treatment?</th>
                    <td><?= formatYesNo($healthInfo['student_under_treatment'] ?? '') ?></td>
                </tr>
                <?php if (!empty($healthInfo['student_treatment_condition'])): ?>
                <tr>
                    <th>Treatment Details</th>
                    <td colspan="3"><?= htmlspecialchars($healthInfo['student_treatment_condition']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Serious illness/surgery?</th>
                    <td><?= formatYesNo($healthInfo['student_serious_illness_surgery'] ?? '') ?></td>
                    <th>Ever hospitalized?</th>
                    <td><?= formatYesNo($healthInfo['student_hospitalized'] ?? '') ?></td>
                </tr>
                <?php if (!empty($healthInfo['student_serious_illness_details'])): ?>
                <tr>
                    <th>Illness/Surgery Details</th>
                    <td colspan="3"><?= htmlspecialchars($healthInfo['student_serious_illness_details']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($healthInfo['student_hospitalization_details'])): ?>
                <tr>
                    <th>Hospitalization Details</th>
                    <td colspan="3"><?= htmlspecialchars($healthInfo['student_hospitalization_details']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Currently taking medication?</th>
                    <td><?= formatYesNo($healthInfo['student_taking_medication'] ?? '') ?></td>
                    <th>Has allergies?</th>
                    <td><?= formatYesNo($healthInfo['student_has_allergies'] ?? '') ?></td>
                </tr>
                <?php if (!empty($healthInfo['student_medication_details'])): ?>
                <tr>
                    <th>Medication Details</th>
                    <td colspan="3"><?= htmlspecialchars($healthInfo['student_medication_details']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($healthInfo['student_allergy_items']) || !empty($healthInfo['student_allergy_other'])): ?>
                <tr>
                    <th>Allergies</th>
                    <td colspan="3">
                        <?php 
                        $allergyText = formatAllergyItems($healthInfo);
                        echo !empty($allergyText) ? htmlspecialchars($allergyText) : '<span class="empty-data">None reported</span>';
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($healthInfo['student_conditions']) || !empty($healthInfo['student_condition_other'])): ?>
                <tr>
                    <th>Medical Conditions</th>
                    <td colspan="3">
                        <?php 
                        $conditionsText = formatConditions($healthInfo);
                        echo !empty($conditionsText) ? htmlspecialchars($conditionsText) : '<span class="empty-data">None reported</span>';
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- SECTION III: WOMEN'S HEALTH (if female) -->
        <?php 
        $patientSex = $healthInfo['student_sex'] ?? $gender;
        if ($patientSex === 'F' || $patientSex === 'Female'): 
        ?>
        <div class="section">
            <div class="section-title">III. WOMEN'S HEALTH</div>
            <table class="info-table">
                <tr>
                    <th>Currently pregnant?</th>
                    <td><?= formatYesNo($healthInfo['student_is_pregnant'] ?? '') ?></td>
                    <th>Currently nursing?</th>
                    <td><?= formatYesNo($healthInfo['student_is_nursing'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Birth control pills?</th>
                    <td><?= formatYesNo($healthInfo['student_takes_birth_control'] ?? '') ?></td>
                    <th>LMP (Last Menstrual Period)</th>
                    <td><?= formatHealthValue($healthInfo['student_lmp'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Menarche</th>
                    <td><?= formatHealthValue($healthInfo['student_menarche'] ?? '') ?></td>
                    <th>Gravida / Para</th>
                    <td><?= formatHealthValue($healthInfo['student_gravida'] ?? '') ?> / <?= formatHealthValue($healthInfo['student_para'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Abortion</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_abortion'] ?? '') ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- SECTION IV: PHYSICIAN INFORMATION -->
        <div class="section">
            <div class="section-title">IV. CLINIC PHYSICIAN INFORMATION</div>
            <table class="info-table">
                <tr>
                    <th>Physician's Name</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_physician_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Specialty</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_physician_specialty'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Office Address</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_physician_office_address'] ?? '') ?></td>
                </tr>
                <td>
                    <th>Office Number</th>
                    <td colspan="3"><?= formatHealthValue($healthInfo['student_physician_office_number'] ?? '') ?></td>
                </tr>
            </table>
        </div>
        
        <!-- SECTION V: VISIT HISTORY -->
        <?php if (!empty($visits)): ?>
        <div class="section">
            <div class="section-title">V. CLINIC VISIT HISTORY</div>
            <table class="info-table">
                <thead>
                    <tr style="background: #e6f3ff;">
                        <th>Date</th>
                        <th>Visit Type</th>
                        <th>Complaint/Symptoms</th>
                        <th>Diagnosis</th>
                        <th>Treatment Given</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($visit['visit_date'])) ?></td>
                        <td><?= strtoupper($visit['visit_type'] ?? 'Regular') ?></td>
                        <td><?= htmlspecialchars($visit['symptoms'] ?? 'None reported') ?></td>
                        <td><?= htmlspecialchars($visit['diagnosis'] ?? 'Not specified') ?></td>
                        <td><?= htmlspecialchars($visit['treatment'] ?? 'Not specified') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?= htmlspecialchars($currentDoctor) ?></div>
                <div class="signature-details">ATTENDING SCHOOL PHYSICIAN<br>License No: <?= $staffLicenseNo ?></div>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?= htmlspecialchars($bhwAssigned) ?></div>
                <div class="signature-details">CLINIC NURSE / HEALTH WORKER</div>
            </div>
        </div>
        
        <div class="signature-section" style="margin-top: 0;">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label"><?= htmlspecialchars($displayFullName) ?></div>
                <div class="signature-details">STUDENT / GUARDIAN SIGNATURE<br>Acknowledgment of Medical Services</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">CLINIC STAFF SIGNATURE</div>
                <div class="signature-details">Attending Staff</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>CEBU EASTERN COLLEGE - STUDENT HEALTH SERVICES</strong> | Cebu City, Philippines 6000</p>
            <p>This is an official student health record. Unauthorized disclosure is prohibited under R.A. 10173 (Data Privacy Act of 2012).</p>
            <p>Generated by: <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'System') ?> | Document Code: CEC-SHR-<?= date('Ymd') ?>-<?= str_pad($patientId, 6, '0', STR_PAD_LEFT) ?></p>
            <p style="margin-top: 5px;">* This is a computer-generated document - Valid without signature *</p>
        </div>
    </div>

    <script>
        // Print functionality
        document.querySelectorAll('.control-btn').forEach(btn => {
            if (btn.textContent.includes('Print')) {
                btn.addEventListener('click', () => window.print());
            }
        });
        
        // Keyboard shortcut for print
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Image error handling
        const cecLogo = document.getElementById('cecLogo');
        const dohLogo = document.getElementById('dohLogo');
        
        if (cecLogo) {
            const img = cecLogo.querySelector('img');
            if (img && !img.complete) {
                img.onerror = function() {
                    this.style.display = 'none';
                    cecLogo.classList.add('fallback');
                    cecLogo.setAttribute('data-fallback', 'CEC');
                };
            }
        }
        
        if (dohLogo) {
            const img = dohLogo.querySelector('img');
            if (img && !img.complete) {
                img.onerror = function() {
                    this.style.display = 'none';
                    dohLogo.classList.add('fallback');
                    dohLogo.setAttribute('data-fallback', 'DOH');
                };
            }
        }
    </script>
</body>
</html>