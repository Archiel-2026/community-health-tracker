<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

// Check if user is staff
if (!isStaff()) {
    die(json_encode(['error' => 'Access denied']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'Patient ID is required']));
}

$patientId = $_GET['id'];
$staffId = $_SESSION['user']['id'];

try {
    // Get patient basic information
    require_once __DIR__ . '/../includes/functions.php';

    if (staff_can_view_all()) {
        $stmt = $pdo->prepare("SELECT 
            p.*,
            e.*,
            COALESCE(u.full_name, 'N/A') as registered_by_name,
            u.email as registered_email,
            u.unique_number,
            COALESCE(u.gender, e.student_sex) as user_gender,
            u.date_of_birth as user_dob,
            u.address as user_address,
            u.contact as user_contact,
            u.sitio as user_sitio,
            u.civil_status as user_civil_status,
            u.occupation as user_occupation
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.id = ? AND p.deleted_at IS NULL");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT 
            p.*,
            e.*,
            COALESCE(u.full_name, 'N/A') as registered_by_name,
            u.email as registered_email,
            u.unique_number,
            COALESCE(u.gender, e.student_sex) as user_gender,
            u.date_of_birth as user_dob,
            u.address as user_address,
            u.contact as user_contact,
            u.sitio as user_sitio,
            u.civil_status as user_civil_status,
            u.occupation as user_occupation
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.id = ? AND p.added_by = ? AND p.deleted_at IS NULL");
        $stmt->execute([$patientId, $staffId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // IMPORTANT: Check if patient exists in sitio1_patients, not existing_info_patients
    if (!$patient || empty($patient['id'])) {
        die(json_encode(['error' => 'Patient not found or access denied']));
    }

    // Parse medical conditions from JSON or comma-separated string
    $medicalConditionsArray = [];
    if (!empty($patient['medical_conditions'])) {
        if (is_string($patient['medical_conditions'])) {
            // Check if it's JSON
            $decoded = json_decode($patient['medical_conditions'], true);
            if (is_array($decoded)) {
                $medicalConditionsArray = $decoded;
            } else {
                $medicalConditionsArray = explode(',', $patient['medical_conditions']);
                $medicalConditionsArray = array_map('trim', $medicalConditionsArray);
            }
        } else {
            $medicalConditionsArray = $patient['medical_conditions'];
        }
    }

    // Parse blood pressure
    $bpSystolic = '';
    $bpDiastolic = '';
    if (!empty($patient['blood_pressure'])) {
        $bpParts = explode('/', $patient['blood_pressure']);
        if (count($bpParts) == 2) {
            $bpSystolic = trim($bpParts[0]);
            $bpDiastolic = trim($bpParts[1]);
        }
    }
    if (!empty($patient['blood_pressure_systolic'])) {
        $bpSystolic = $patient['blood_pressure_systolic'];
    }
    if (!empty($patient['blood_pressure_diastolic'])) {
        $bpDiastolic = $patient['blood_pressure_diastolic'];
    }

    // Set default values for fields that might be NULL from existing_info_patients
    $defaultFields = [
        'student_last_name' => '',
        'student_first_name' => '',
        'student_middle_name' => '',
        'student_birthdate' => $patient['date_of_birth'] ?? '',
        'student_religion' => '',
        'student_home_address' => $patient['address'] ?? '',
        'student_occupation' => $patient['occupation'] ?? '',
        'student_effective_date' => date('Y-m-d'),
        'student_sex' => $patient['gender'] ?? '',
        'student_nickname' => '',
        'student_home_phone' => '',
        'student_office_phone' => '',
        'student_fax_number' => '',
        'student_mobile_number' => $patient['contact'] ?? '',
        'student_email_address' => '',
        'parent_guardian_name' => '',
        'parent_guardian_occupation' => '',
        'student_physician_name' => '',
        'student_physician_specialty' => '',
        'student_physician_office_address' => '',
        'student_physician_office_number' => '',
        'student_good_health' => '',
        'student_under_treatment' => '',
        'student_treatment_condition' => '',
        'student_serious_illness_surgery' => '',
        'student_serious_illness_details' => '',
        'student_hospitalized' => '',
        'student_hospitalization_details' => '',
        'student_taking_medication' => '',
        'student_medication_details' => '',
        'student_uses_tobacco' => '',
        'student_uses_alcohol_drugs' => '',
        'student_has_allergies' => '',
        'student_allergy_items' => '',
        'student_allergy_other' => '',
        'student_bleeding_time' => '',
        'student_is_pregnant' => '',
        'student_is_nursing' => '',
        'student_takes_birth_control' => '',
        'student_menarche' => '',
        'student_lmp' => '',
        'student_gravida' => '',
        'student_para' => '',
        'student_abortion' => '',
        'student_conditions' => '',
        'student_condition_other' => '',
        'student_signature_name' => '',
        'blood_type' => '',
        'height' => '',
        'weight' => '',
        'temperature' => '',
        'blood_pressure' => '',
        'allergies' => '',
        'medical_history' => '',
        'current_medications' => '',
        'family_history' => '',
        'immunization_record' => '',
        'chronic_conditions' => '',
        'medical_conditions' => ''
    ];

    // Apply default values for any NULL fields from existing_info_patients
    foreach ($defaultFields as $field => $defaultValue) {
        if (!isset($patient[$field]) || $patient[$field] === null) {
            $patient[$field] = $defaultValue;
        }
    }

    // Add the parsed data to patient array
    $patient['medical_conditions_array'] = $medicalConditionsArray;
    $patient['blood_pressure_systolic'] = $bpSystolic;
    $patient['blood_pressure_diastolic'] = $bpDiastolic;

    logActivity(
        $pdo,
        $staffId,
        'view_patient',
        'staff',
        (int) $patientId,
        [
            'patient_id' => (int) $patientId,
            'patient_name' => $patient['full_name'] ?? 'Unknown',
        ]
    );

    // Calculate age if date of birth exists
    if (!empty($patient['student_birthdate'])) {
        $dob = new DateTime($patient['student_birthdate']);
        $today = new DateTime();
        $age = $dob->diff($today)->y;
        $patient['age'] = $age;
    } elseif (!empty($patient['date_of_birth'])) {
        $dob = new DateTime($patient['date_of_birth']);
        $today = new DateTime();
        $age = $dob->diff($today)->y;
        $patient['age'] = $age;
    }

    // Format allergy items if stored as JSON or comma-separated
    if (!empty($patient['student_allergy_items'])) {
        if (is_string($patient['student_allergy_items'])) {
            $patient['student_allergy_items_array'] = explode(', ', $patient['student_allergy_items']);
        } else {
            $patient['student_allergy_items_array'] = $patient['student_allergy_items'];
        }
    } else {
        $patient['student_allergy_items_array'] = [];
    }
    
    // Format conditions if stored as JSON or comma-separated
    if (!empty($patient['student_conditions'])) {
        if (is_string($patient['student_conditions'])) {
            $patient['student_conditions_array'] = explode(', ', $patient['student_conditions']);
        } else {
            $patient['student_conditions_array'] = $patient['student_conditions'];
        }
    } else {
        $patient['student_conditions_array'] = [];
    }
    
    // Get consultation notes count
    $stmt = $pdo->prepare("SELECT COUNT(*) as note_count FROM consultation_notes WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $noteCount = $stmt->fetch(PDO::FETCH_ASSOC)['note_count'];
    $patient['note_count'] = $noteCount;

} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}

// Now output the HTML form
?>

<style>
    .date-input-with-trigger {
        position: relative;
    }

    .date-input-with-trigger .form-input-modal {
        padding-right: 3.5rem;
    }

    .date-input-with-trigger input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 3.25rem;
        height: 100%;
        cursor: pointer;
    }

    .date-input-trigger {
        position: absolute;
        right: 0.95rem;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        padding: 0;
        border: none;
        background: transparent;
        color: #3C96E1;
        cursor: pointer;
        z-index: 20;
    }

    .date-input-trigger svg {
        width: 1.45rem;
        height: 1.45rem;
        pointer-events: none;
    }
    
    .form-section {
        transition: all 0.3s ease;
    }
    
    .form-section:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .required-field::after {
        content: " *";
        color: #ef4444;
    }
    
    .readonly-field {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }
    
    .form-label-modal {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #374151;
    }
    
    .form-input-modal {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #93c5fd;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    
    .form-input-modal:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-select-modal {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #93c5fd;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        background-color: white;
    }
    
    .form-textarea-modal {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #93c5fd;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        resize: vertical;
    }

    .conditions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .condition-checkbox {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background-color: #f8fafc;
        transition: all 0.2s;
    }

    .condition-checkbox:hover {
        background-color: #e0f2fe;
    }

    .condition-checkbox input {
        width: 1.1rem;
        height: 1.1rem;
        accent-color: #3b82f6;
    }

    .condition-checkbox label {
        font-size: 0.875rem;
        color: #334155;
        cursor: pointer;
        flex: 1;
    }
</style>

<!-- Patient Information Form -->
<form id="healthInfoForm" method="POST" action="existing_info_patients.php" class="space-y-8">
    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patientId) ?>">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($patient['user_id'] ?? '0') ?>">
    <input type="hidden" name="save_health_info" value="1">

    <!-- Personal Information Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.125 16.875C13.125 17.0975 13.059 17.315 12.9354 17.5C12.8118 17.685 12.6361 17.8292 12.4305 17.9144C12.225 17.9995 11.9988 18.0218 11.7805 17.9784C11.5623 17.935 11.3618 17.8278 11.2045 17.6705C11.0472 17.5132 10.94 17.3127 10.8966 17.0945C10.8532 16.8762 10.8755 16.65 10.9606 16.4445C11.0458 16.2389 11.19 16.0632 11.375 15.9396C11.56 15.816 11.7775 15.75 12 15.75C12.2984 15.75 12.5845 15.8685 12.7955 16.0795C13.0065 16.2905 13.125 16.5766 13.125 16.875ZM12 6.75C9.93188 6.75 8.25 8.26406 8.25 10.125V10.5C8.25 10.6989 8.32902 10.8897 8.46967 11.0303C8.61033 11.171 8.80109 11.25 9 11.25C9.19892 11.25 9.38968 11.171 9.53033 11.0303C9.67099 10.8897 9.75 10.6989 9.75 10.5V10.125C9.75 9.09375 10.7597 8.25 12 8.25C13.2403 8.25 14.25 9.09375 14.25 10.125C14.25 11.1562 13.2403 12 12 12C11.8011 12 11.6103 12.079 11.4697 12.2197C11.329 12.3603 11.25 12.5511 11.25 12.75V13.5C11.25 13.6989 11.329 13.8897 11.4697 14.0303C11.6103 14.171 11.8011 14.25 12 14.25C12.1989 14.25 12.3897 14.171 12.5303 14.0303C12.671 13.8897 12.75 13.6989 12.75 13.5V13.4325C14.46 13.1184 15.75 11.7544 15.75 10.125C15.75 8.26406 14.0681 6.75 12 6.75ZM21.75 12C21.75 13.9284 21.1782 15.8134 20.1068 17.4168C19.0355 19.0202 17.5127 20.2699 15.7312 21.0078C13.9496 21.7458 11.9892 21.9389 10.0979 21.5627C8.20656 21.1865 6.46928 20.2579 5.10571 18.8943C3.74215 17.5307 2.81355 15.7934 2.43735 13.9021C2.06114 12.0108 2.25422 10.0504 2.99218 8.26884C3.73013 6.48726 4.97982 4.96452 6.58319 3.89317C8.18657 2.82183 10.0716 2.25 12 2.25C14.585 2.25273 17.0634 3.28084 18.8913 5.10872C20.7192 6.93661 21.7473 9.41498 21.75 12Z" fill="#3C96E1"/>
            </svg>
            Personal Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Last Name -->
            <div>
                <label class="form-label-modal required-field">Last Name</label>
                <input type="text" name="student_last_name" value="<?= htmlspecialchars($patient['student_last_name'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter Last Name">
            </div>

            <!-- First Name -->
            <div>
                <label class="form-label-modal required-field">First Name</label>
                <input type="text" name="student_first_name" value="<?= htmlspecialchars($patient['student_first_name'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter First Name">
            </div>

            <!-- Middle Name -->
            <div>
                <label class="form-label-modal">Middle Name</label>
                <input type="text" name="student_middle_name" value="<?= htmlspecialchars($patient['student_middle_name'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Middle Name">
            </div>

            <!-- Full Name (Readonly - Combined) -->
            <div>
                <label class="form-label-modal">Full Name (Combined)</label>
                <input type="text" value="<?= htmlspecialchars($patient['full_name'] ?? '') ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <!-- Date of Birth -->
            <div>
                <label class="form-label-modal required-field">Birthdate</label>
                <div class="date-input-with-trigger">
                    <input type="date" id="view_date_of_birth" name="student_birthdate" 
                           value="<?= htmlspecialchars($patient['student_birthdate'] ?? $patient['date_of_birth'] ?? '') ?>" 
                           required max="<?= date('Y-m-d') ?>" class="form-input-modal">
                    <button type="button" id="view_date_of_birth_trigger"
                           class="date-input-trigger hover:text-[#1D4ED8]"
                           onclick="document.getElementById('view_date_of_birth').showPicker ? document.getElementById('view_date_of_birth').showPicker() : document.getElementById('view_date_of_birth').focus()"
                           aria-label="Choose date of birth">
                        <svg width="50" height="50" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24.375 3.75H21.5625V2.8125C21.5625 2.56386 21.4637 2.3254 21.2879 2.14959C21.1121 1.97377 20.8736 1.875 20.625 1.875C20.3764 1.875 20.1379 1.97377 19.9621 2.14959C19.7863 2.3254 19.6875 2.56386 19.6875 2.8125V3.75H10.3125V2.8125C10.3125 2.56386 10.2137 2.3254 10.0379 2.14959C9.8621 1.97377 9.62364 1.875 9.375 1.875C9.12636 1.875 8.8879 1.97377 8.71209 2.14959C8.53627 2.3254 8.4375 2.56386 8.4375 2.8125V3.75H5.625C5.12772 3.75 4.65081 3.94754 4.29917 4.29917C3.94754 4.65081 3.75 5.12772 3.75 5.625V24.375C3.75 24.8723 3.94754 25.3492 4.29917 25.7008C4.65081 26.0525 5.12772 26.25 5.625 26.25H24.375C24.8723 26.25 25.3492 26.0525 25.7008 25.7008C26.0525 25.3492 26.25 24.8723 26.25 24.375V5.625C26.25 5.12772 26.0525 4.65081 25.7008 4.29917C25.3492 3.94754 24.8723 3.75 24.375 3.75Z" fill="#3C96E1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Age -->
            <div>
                <label class="form-label-modal">Age (Auto-calculated)</label>
                <input type="number" name="age" value="<?= htmlspecialchars($patient['age'] ?? '') ?>" 
                       readonly class="form-input-modal readonly-field bg-gray-50">
            </div>

            <!-- Religion -->
            <div>
                <label class="form-label-modal">Religion</label>
                <input type="text" name="student_religion" value="<?= htmlspecialchars($patient['student_religion'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Religion">
            </div>

            <!-- Home Address -->
            <div>
                <label class="form-label-modal required-field">Home Address</label>
                <input type="text" name="student_home_address" value="<?= htmlspecialchars($patient['student_home_address'] ?? $patient['address'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter Complete Home Address">
            </div>

            <!-- Occupation -->
            <div>
                <label class="form-label-modal">Occupation</label>
                <input type="text" name="student_occupation" value="<?= htmlspecialchars($patient['student_occupation'] ?? $patient['occupation'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Occupation">
            </div>

            <!-- Effective Date -->
            <div>
                <label class="form-label-modal required-field">Effective Date</label>
                <div class="date-input-with-trigger">
                    <input type="date" id="view_effective_date" name="student_effective_date" 
                           value="<?= htmlspecialchars($patient['student_effective_date'] ?? date('Y-m-d')) ?>" 
                           class="form-input-modal">
                    <button type="button" id="view_effective_date_trigger"
                           class="date-input-trigger hover:text-[#1D4ED8]"
                           onclick="document.getElementById('view_effective_date').showPicker ? document.getElementById('view_effective_date').showPicker() : document.getElementById('view_effective_date').focus()"
                           aria-label="Choose effective date">
                        <svg width="50" height="50" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24.375 3.75H21.5625V2.8125C21.5625 2.56386 21.4637 2.3254 21.2879 2.14959C21.1121 1.97377 20.8736 1.875 20.625 1.875C20.3764 1.875 20.1379 1.97377 19.9621 2.14959C19.7863 2.3254 19.6875 2.56386 19.6875 2.8125V3.75H10.3125V2.8125C10.3125 2.56386 10.2137 2.3254 10.0379 2.14959C9.8621 1.97377 9.62364 1.875 9.375 1.875C9.12636 1.875 8.8879 1.97377 8.71209 2.14959C8.53627 2.3254 8.4375 2.56386 8.4375 2.8125V3.75H5.625C5.12772 3.75 4.65081 3.94754 4.29917 4.29917C3.94754 4.65081 3.75 5.12772 3.75 5.625V24.375C3.75 24.8723 3.94754 25.3492 4.29917 25.7008C4.65081 26.0525 5.12772 26.25 5.625 26.25H24.375C24.8723 26.25 25.3492 26.0525 25.7008 25.7008C26.0525 25.3492 26.25 24.8723 26.25 24.375V5.625C26.25 5.12772 26.0525 4.65081 25.7008 4.29917C25.3492 3.94754 24.8723 3.75 24.375 3.75Z" fill="#3C96E1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Sex -->
            <div>
                <label class="form-label-modal required-field">Sex</label>
                <select name="student_sex" required class="form-select-modal" id="view_student_sex">
                    <option value="">Select Sex</option>
                    <option value="M" <?= ($patient['student_sex'] ?? '') == 'M' ? 'selected' : '' ?>>Male</option>
                    <option value="F" <?= ($patient['student_sex'] ?? '') == 'F' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <!-- Nickname -->
            <div>
                <label class="form-label-modal">Nickname</label>
                <input type="text" name="student_nickname" value="<?= htmlspecialchars($patient['student_nickname'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Nickname">
            </div>

            <!-- Home Phone -->
            <div>
                <label class="form-label-modal">Home No.</label>
                <input type="text" name="student_home_phone" value="<?= htmlspecialchars($patient['student_home_phone'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Home Number">
            </div>

            <!-- Office Phone -->
            <div>
                <label class="form-label-modal">Office No.</label>
                <input type="text" name="student_office_phone" value="<?= htmlspecialchars($patient['student_office_phone'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Office Number">
            </div>

            <!-- Fax Number -->
            <div>
                <label class="form-label-modal">Fax No.</label>
                <input type="text" name="student_fax_number" value="<?= htmlspecialchars($patient['student_fax_number'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Fax Number">
            </div>

            <!-- Mobile Number -->
            <div>
                <label class="form-label-modal required-field">Mobile No.</label>
                <input type="text" name="student_mobile_number" value="<?= htmlspecialchars($patient['student_mobile_number'] ?? $patient['contact'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter Mobile Number">
            </div>

            <!-- Email Address -->
            <div>
                <label class="form-label-modal">Email Address</label>
                <input type="email" name="student_email_address" value="<?= htmlspecialchars($patient['student_email_address'] ?? $patient['registered_email'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Email Address">
            </div>

            <!-- Parent/Guardian Name -->
            <div>
                <label class="form-label-modal">Parent/Guardian's Name</label>
                <input type="text" name="parent_guardian_name" value="<?= htmlspecialchars($patient['parent_guardian_name'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Parent/Guardian Name">
            </div>

            <!-- Parent/Guardian Occupation -->
            <div>
                <label class="form-label-modal">Parent/Guardian's Occupation</label>
                <input type="text" name="parent_guardian_occupation" value="<?= htmlspecialchars($patient['parent_guardian_occupation'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Occupation">
            </div>
        </div>
    </div>

    <!-- Physician Information Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20.625 15C20.625 15.2225 20.559 15.44 20.4354 15.625C20.3118 15.81 20.1361 15.9542 19.9305 16.0394C19.725 16.1245 19.4988 16.1468 19.2805 16.1034C19.0623 16.06 18.8618 15.9528 18.7045 15.7955C18.5472 15.6382 18.44 15.4377 18.3966 15.2195C18.3532 15.0012 18.3755 14.775 18.4606 14.5695C18.5458 14.3639 18.69 14.1882 18.875 14.0646C19.06 13.941 19.2775 13.875 19.5 13.875C19.7984 13.875 20.0845 13.9935 20.2955 14.2045C20.5065 14.4155 20.625 14.7016 20.625 15ZM20.1984 18.6834C20.0337 19.7455 19.4949 20.7137 18.6793 21.4135C17.8636 22.1133 16.8247 22.4986 15.75 22.5H13.5C12.3069 22.4988 11.163 22.0243 10.3194 21.1806C9.47575 20.337 9.00124 19.1931 9 18V14.2022C7.55018 14.0195 6.21686 13.3141 5.25025 12.2182C4.28364 11.1223 3.75018 9.71128 3.75 8.25V3.75C3.75 3.55109 3.82902 3.36032 3.96967 3.21967C4.11032 3.07902 4.30109 3 4.5 3H6.75C6.94891 3 7.13968 3.07902 7.28033 3.21967C7.42098 3.36032 7.5 3.55109 7.5 3.75C7.5 3.94891 7.42098 4.13968 7.28033 4.28033C7.13968 4.42098 6.94891 4.5 6.75 4.5H5.25V8.25C5.24995 8.84603 5.3683 9.43614 5.59819 9.98605C5.82808 10.536 6.16492 11.0347 6.58916 11.4534C7.0134 11.872 7.51658 12.2022 8.06949 12.4248C8.6224 12.6474 9.21402 12.7579 9.81 12.75C12.2578 12.7181 14.25 10.6641 14.25 8.17219V4.5H12.75C12.5511 4.5 12.3603 4.42098 12.2197 4.28033C12.079 4.13968 12 3.94891 12 3.75C12 3.55109 12.079 3.36032 12.2197 3.21967C12.3603 3.07902 12.5511 3 12.75 3H15C15.1989 3 15.3897 3.07902 15.5303 3.21967C15.671 3.36032 15.75 3.55109 15.75 3.75V8.17219C15.75 11.2509 13.4503 13.8244 10.5 14.2012V18C10.5 18.7956 10.8161 19.5587 11.3787 20.1213C11.9413 20.6839 12.7044 21 13.5 21H15.75C16.4313 20.9989 17.0919 20.7663 17.6237 20.3405C18.1555 19.9147 18.5269 19.3208 18.6769 18.6562C17.7711 18.4528 16.973 17.9206 16.437 17.1626C15.9009 16.4046 15.6651 15.4748 15.7752 14.553C15.8852 13.6312 16.3332 12.7829 17.0326 12.1724C17.7319 11.5619 18.6329 11.2325 19.5611 11.2479C20.4893 11.2634 21.3788 11.6226 22.0575 12.256C22.7362 12.8895 23.1557 13.7521 23.235 14.6771C23.3143 15.602 23.0477 16.5235 22.4868 17.2633C21.9259 18.003 21.1105 18.5083 20.1984 18.6816V18.6834Z" fill="#3C96E1"/>
            </svg>
            Physician Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Physician's Name</label>
                <input type="text" name="student_physician_name" value="<?= htmlspecialchars($patient['student_physician_name'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Physician's Full Name">
            </div>

            <div>
                <label class="form-label-modal">Specialty</label>
                <input type="text" name="student_physician_specialty" value="<?= htmlspecialchars($patient['student_physician_specialty'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Specialty">
            </div>

            <div>
                <label class="form-label-modal">Office Address</label>
                <input type="text" name="student_physician_office_address" value="<?= htmlspecialchars($patient['student_physician_office_address'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Office Address">
            </div>

            <div>
                <label class="form-label-modal">Office Number</label>
                <input type="text" name="student_physician_office_number" value="<?= htmlspecialchars($patient['student_physician_office_number'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Office Number">
            </div>
        </div>
    </div>

    <!-- Medical Conditions Checklist Section (NEW) -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-4 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 8V12M12 16H12.01M4.5 12C4.5 7.85786 7.85786 4.5 12 4.5C16.1421 4.5 19.5 7.85786 19.5 12C19.5 16.1421 16.1421 19.5 12 19.5C7.85786 19.5 4.5 16.1421 4.5 12Z" stroke="#3C96E1" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Medical Conditions Checklist
        </h4>
        <p class="text-sm text-gray-500 mb-4">Please check all conditions that apply to the patient:</p>
        
        <div class="conditions-grid">
            <?php
            $medicalConditionsList = [
                'High Blood Pressure' => 'high_blood_pressure',
                'Low Blood Pressure' => 'low_blood_pressure',
                'Epilepsy / Convulsions' => 'epilepsy',
                'AIDS or HIV Infections' => 'hiv_aids',
                'Sexually Transmitted Disease' => 'std',
                'Stomach Troubles / Ulcer' => 'ulcer',
                'Fainting Seizure' => 'fainting_seizure',
                'Rapid Weight Loss' => 'rapid_weight_loss',
                'Joint Replacement / Implant' => 'joint_replacement',
                'Heart Surgery' => 'heart_surgery',
                'Heart Attack' => 'heart_attack',
                'Thyroid Problem' => 'thyroid'
            ];
            
            foreach ($medicalConditionsList as $label => $value):
                $checked = in_array($label, $patient['medical_conditions_array'] ?? []) ? 'checked' : '';
            ?>
            <div class="condition-checkbox">
                <input type="checkbox" name="medical_conditions[]" value="<?= htmlspecialchars($label) ?>" 
                       id="med_<?= $value ?>" <?= $checked ?>>
                <label for="med_<?= $value ?>"><?= htmlspecialchars($label) ?></label>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Other Medical Condition -->
        <div class="mt-4">
            <label class="form-label-modal">Other Medical Conditions (Please specify)</label>
            <input type="text" name="medical_conditions_other" value="<?= htmlspecialchars($patient['medical_conditions_other'] ?? '') ?>" 
                   class="form-input-modal" placeholder="Specify other medical conditions not listed above">
        </div>
    </div>

    <!-- Blood Pressure and Blood Type Section (NEW) -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-4 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15 7H9L12 2Z" fill="#3C96E1"/>
                <path d="M12 22L9 17H15L12 22Z" fill="#3C96E1"/>
                <path d="M7 9L2 12L7 15V9Z" fill="#3C96E1"/>
                <path d="M17 9V15L22 12L17 9Z" fill="#3C96E1"/>
                <circle cx="12" cy="12" r="3" fill="#3C96E1"/>
            </svg>
            Vital Signs & Blood Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Blood Type -->
            <div>
                <label class="form-label-modal">Blood Type</label>
                <select name="blood_type" class="form-select-modal">
                    <option value="">Select Blood Type</option>
                    <option value="A+" <?= ($patient['blood_type'] ?? '') == 'A+' ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= ($patient['blood_type'] ?? '') == 'A-' ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= ($patient['blood_type'] ?? '') == 'B+' ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= ($patient['blood_type'] ?? '') == 'B-' ? 'selected' : '' ?>>B-</option>
                    <option value="AB+" <?= ($patient['blood_type'] ?? '') == 'AB+' ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= ($patient['blood_type'] ?? '') == 'AB-' ? 'selected' : '' ?>>AB-</option>
                    <option value="O+" <?= ($patient['blood_type'] ?? '') == 'O+' ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= ($patient['blood_type'] ?? '') == 'O-' ? 'selected' : '' ?>>O-</option>
                </select>
            </div>

            <!-- Blood Pressure -->
            <div>
                <label class="form-label-modal">Blood Pressure (Systolic)</label>
                <div class="flex gap-2 items-center">
                    <input type="number" name="blood_pressure_systolic" 
                           value="<?= htmlspecialchars($patient['blood_pressure_systolic'] ?? '') ?>" 
                           class="form-input-modal w-1/2" placeholder="Systolic (mmHg)">
                    <span class="text-lg font-medium">/</span>
                    <input type="number" name="blood_pressure_diastolic" 
                           value="<?= htmlspecialchars($patient['blood_pressure_diastolic'] ?? '') ?>" 
                           class="form-input-modal w-1/2" placeholder="Diastolic (mmHg)">
                    <span class="text-sm text-gray-500 ml-1">mmHg</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Example: 120/80</p>
            </div>

            <!-- Height -->
            <div>
                <label class="form-label-modal">Height (cm)</label>
                <input type="number" step="0.1" name="height" value="<?= htmlspecialchars($patient['height'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Height in centimeters">
            </div>

            <!-- Weight -->
            <div>
                <label class="form-label-modal">Weight (kg)</label>
                <input type="number" step="0.1" name="weight" value="<?= htmlspecialchars($patient['weight'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Weight in kilograms">
            </div>

            <!-- Temperature -->
            <div>
                <label class="form-label-modal">Temperature (°C)</label>
                <input type="number" step="0.1" name="temperature" value="<?= htmlspecialchars($patient['temperature'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Body temperature">
            </div>
        </div>
    </div>

    <!-- Health Questionnaire Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 6.75C9.93188 6.75 8.25 8.26406 8.25 10.125V10.5C8.25 10.6989 8.32902 10.8897 8.46967 11.0303C8.61033 11.171 8.80109 11.25 9 11.25C9.19892 11.25 9.38968 11.171 9.53033 11.0303C9.67099 10.8897 9.75 10.6989 9.75 10.5V10.125C9.75 9.09375 10.7597 8.25 12 8.25C13.2403 8.25 14.25 9.09375 14.25 10.125C14.25 11.1562 13.2403 12 12 12C11.8011 12 11.6103 12.079 11.4697 12.2197C11.329 12.3603 11.25 12.5511 11.25 12.75V13.5C11.25 13.6989 11.329 13.8897 11.4697 14.0303C11.6103 14.171 11.8011 14.25 12 14.25C12.1989 14.25 12.3897 14.171 12.5303 14.0303C12.671 13.8897 12.75 13.6989 12.75 13.5V13.4325C14.46 13.1184 15.75 11.7544 15.75 10.125C15.75 8.26406 14.0681 6.75 12 6.75Z" fill="#3C96E1"/>
                <path d="M21.75 12C21.75 13.9284 21.1782 15.8134 20.1068 17.4168C19.0355 19.0202 17.5127 20.2699 15.7312 21.0078C13.9496 21.7458 11.9892 21.9389 10.0979 21.5627C8.20656 21.1865 6.46928 20.2579 5.10571 18.8943C3.74215 17.5307 2.81355 15.7934 2.43735 13.9021C2.06114 12.0108 2.25422 10.0504 2.99218 8.26884C3.73013 6.48726 4.97982 4.96452 6.58319 3.89317C8.18657 2.82183 10.0716 2.25 12 2.25C14.585 2.25273 17.0634 3.28084 18.8913 5.10872C20.7192 6.93661 21.7473 9.41498 21.75 12Z" fill="#3C96E1"/>
            </svg>
            Health Questionnaire
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Are you in good health?</label>
                <select name="student_good_health" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_good_health'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_good_health'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Are you presently under treatment?</label>
                <select name="student_under_treatment" class="form-select-modal" id="view_under_treatment">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_under_treatment'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_under_treatment'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="md:col-span-2" id="view_treatment_condition_div" style="display: <?= ($patient['student_under_treatment'] ?? '') == 'yes' ? 'block' : 'none' ?>;">
                <label class="form-label-modal">If yes, please describe</label>
                <textarea name="student_treatment_condition" rows="2" class="form-textarea-modal" 
                          placeholder="Describe the condition and treatment..."><?= htmlspecialchars($patient['student_treatment_condition'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Have you ever had a serious illness or surgery?</label>
                <select name="student_serious_illness_surgery" class="form-select-modal" id="view_serious_illness">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_serious_illness_surgery'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_serious_illness_surgery'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="md:col-span-2" id="view_serious_illness_div" style="display: <?= ($patient['student_serious_illness_surgery'] ?? '') == 'yes' ? 'block' : 'none' ?>;">
                <label class="form-label-modal">If yes, please describe</label>
                <textarea name="student_serious_illness_details" rows="2" class="form-textarea-modal" 
                          placeholder="Describe the illness or surgery..."><?= htmlspecialchars($patient['student_serious_illness_details'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Have you ever been hospitalized?</label>
                <select name="student_hospitalized" class="form-select-modal" id="view_hospitalized">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_hospitalized'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_hospitalized'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="md:col-span-2" id="view_hospitalized_div" style="display: <?= ($patient['student_hospitalized'] ?? '') == 'yes' ? 'block' : 'none' ?>;">
                <label class="form-label-modal">If yes, please describe</label>
                <textarea name="student_hospitalization_details" rows="2" class="form-textarea-modal" 
                          placeholder="Describe hospitalization details..."><?= htmlspecialchars($patient['student_hospitalization_details'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Are you currently taking any medication?</label>
                <select name="student_taking_medication" class="form-select-modal" id="view_taking_medication">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_taking_medication'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_taking_medication'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="md:col-span-2" id="view_medication_div" style="display: <?= ($patient['student_taking_medication'] ?? '') == 'yes' ? 'block' : 'none' ?>;">
                <label class="form-label-modal">If yes, please list</label>
                <textarea name="student_medication_details" rows="2" class="form-textarea-modal" 
                          placeholder="List medications with dosage..."><?= htmlspecialchars($patient['student_medication_details'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Do you use tobacco?</label>
                <select name="student_uses_tobacco" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_uses_tobacco'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_uses_tobacco'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Do you use alcohol or drugs?</label>
                <select name="student_uses_alcohol_drugs" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_uses_alcohol_drugs'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_uses_alcohol_drugs'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Do you have allergies?</label>
                <select name="student_has_allergies" class="form-select-modal" id="view_has_allergies">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_has_allergies'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_has_allergies'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Bleeding Time</label>
                <input type="text" name="student_bleeding_time" value="<?= htmlspecialchars($patient['student_bleeding_time'] ?? '') ?>" 
                       class="form-input-modal" placeholder="e.g., Within normal limits">
            </div>
        </div>

        <!-- Allergy Items Section -->
        <div id="view_allergy_items_section" class="mt-6" style="display: <?= ($patient['student_has_allergies'] ?? '') == 'yes' ? 'block' : 'none' ?>;">
            <label class="form-label-modal">Please check items that cause allergy:</label>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mt-2">
                <?php
                $allergyItemsArray = $patient['student_allergy_items_array'] ?? [];
                $allergyOptions = ['Food', 'Drugs', 'Insect bite', 'Pollen', 'Dust', 'Animals'];
                foreach ($allergyOptions as $option):
                ?>
                <label class="flex items-center">
                    <input type="checkbox" name="allergy_items[]" value="<?= $option ?>" 
                           class="mr-2 w-4 h-4 text-blue-600"
                           <?= in_array($option, $allergyItemsArray) ? 'checked' : '' ?>>
                    <?= $option ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label-modal">Other allergies:</label>
                <input type="text" name="student_allergy_other" value="<?= htmlspecialchars($patient['student_allergy_other'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Specify other allergies">
            </div>
        </div>
    </div>

    <!-- Women's Health Section -->
    <div id="view_womens_health_section" class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section" style="display: <?= ($patient['student_sex'] ?? '') == 'F' ? 'block' : 'none' ?>;">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C11.2044 2 10.4413 2.31607 9.87868 2.87868C9.31607 3.44129 9 4.20435 9 5V19C9 19.7956 9.31607 20.5587 9.87868 21.1213C10.4413 21.6839 11.2044 22 12 22C12.7956 22 13.5587 21.6839 14.1213 21.1213C14.6839 20.5587 15 19.7956 15 19V5C15 4.20435 14.6839 3.44129 14.1213 2.87868C13.5587 2.31607 12.7956 2 12 2Z" fill="#3C96E1"/>
                <path d="M7 9H5C4.20435 9 3.44129 9.31607 2.87868 9.87868C2.31607 10.4413 2 11.2044 2 12C2 12.7956 2.31607 13.5587 2.87868 14.1213C3.44129 14.6839 4.20435 15 5 15H7" fill="#3C96E1"/>
                <path d="M17 9H19C19.7956 9 20.5587 9.31607 21.1213 9.87868C21.6839 10.4413 22 11.2044 22 12C22 12.7956 21.6839 13.5587 21.1213 14.1213C20.5587 14.6839 19.7956 15 19 15H17" fill="#3C96E1"/>
            </svg>
            Women's Health
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Are you pregnant?</label>
                <select name="student_is_pregnant" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_is_pregnant'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_is_pregnant'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Are you nursing?</label>
                <select name="student_is_nursing" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_is_nursing'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_is_nursing'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Do you take birth control pills?</label>
                <select name="student_takes_birth_control" class="form-select-modal">
                    <option value="">Select</option>
                    <option value="yes" <?= ($patient['student_takes_birth_control'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= ($patient['student_takes_birth_control'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div>
                <label class="form-label-modal">Menarche</label>
                <input type="text" name="student_menarche" value="<?= htmlspecialchars($patient['student_menarche'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Age of first menstruation">
            </div>

            <div>
                <label class="form-label-modal">LMP (Last Menstrual Period)</label>
                <input type="text" name="student_lmp" value="<?= htmlspecialchars($patient['student_lmp'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Date">
            </div>

            <div>
                <label class="form-label-modal">Gravida</label>
                <input type="text" name="student_gravida" value="<?= htmlspecialchars($patient['student_gravida'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Number of pregnancies">
            </div>

            <div>
                <label class="form-label-modal">Para</label>
                <input type="text" name="student_para" value="<?= htmlspecialchars($patient['student_para'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Number of deliveries">
            </div>

            <div>
                <label class="form-label-modal">Abortion</label>
                <input type="text" name="student_abortion" value="<?= htmlspecialchars($patient['student_abortion'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Number of abortions">
            </div>
        </div>

        <!-- Conditions -->
        <div class="mt-6">
            <label class="form-label-modal">Please check conditions you have:</label>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mt-2">
                <?php
                $conditionsArray = $patient['student_conditions_array'] ?? [];
                $conditionOptions = ['Anemia', 'Asthma', 'Heart disease', 'High blood', 'Kidney disease', 'Goiter', 'Diabetes', 'Ulcer', 'Hepatitis', 'Cancer', 'STD', 'Epilepsy'];
                foreach ($conditionOptions as $option):
                ?>
                <label class="flex items-center">
                    <input type="checkbox" name="student_conditions[]" value="<?= $option ?>" 
                           class="mr-2 w-4 h-4 text-blue-600"
                           <?= in_array($option, $conditionsArray) ? 'checked' : '' ?>>
                    <?= $option ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label-modal">Other conditions:</label>
                <input type="text" name="student_condition_other" value="<?= htmlspecialchars($patient['student_condition_other'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Specify other conditions">
            </div>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 4H4C3.73478 4 3.48043 4.10536 3.29289 4.29289C3.10536 4.48043 3 4.73478 3 5V19C3 19.2652 3.10536 19.5196 3.29289 19.7071C3.48043 19.8946 3.73478 20 4 20H20C20.2652 20 20.5196 19.8946 20.7071 19.7071C20.8946 19.5196 21 19.2652 21 19V5C21 4.73478 20.8946 4.48043 20.7071 4.29289C20.5196 4.10536 20.2652 4 20 4Z" fill="#3C96E1"/>
                <path d="M8 8H16M8 12H12M8 16H16" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Signature
        </h4>
        
        <div>
            <label class="form-label-modal">Signature over Printed Name</label>
            <input type="text" name="student_signature_name" value="<?= htmlspecialchars($patient['student_signature_name'] ?? '') ?>" 
                   class="form-input-modal" placeholder="Enter signature name">
        </div>
    </div>

    <!-- Additional Information (Readonly) -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100 form-section">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M24.7017 4.59869L9.43807 1.90338C8.94841 1.81722 8.44458 1.92905 8.03737 2.2143C7.63015 2.49955 7.3529 2.93485 7.26659 3.42448L3.78026 23.2292C3.73762 23.4718 3.7432 23.7204 3.7967 23.9609C3.85019 24.2014 3.95054 24.4289 4.09202 24.6306C4.2335 24.8322 4.41333 25.004 4.62124 25.1362C4.82914 25.2683 5.06104 25.3582 5.30369 25.4006L20.5674 28.096C20.8101 28.1388 21.0569 28.1333 21.2975 28.0799C21.5381 28.0265 21.7658 27.9262 21.9676 27.7847C22.1694 27.6432 22.3413 27.4633 22.4735 27.2553C22.6057 27.0473 22.6956 26.8153 22.7381 26.5725L26.2263 6.76784C26.3118 6.27802 26.1991 5.77434 25.9132 5.36756C25.6273 4.96078 25.1915 4.68422 24.7017 4.59869Z" fill="#3C96E1"/>
            </svg>
            System Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Patient Type</label>
                <input type="text" value="<?= !empty($patient['user_id']) ? 'Registered Patient' : 'Regular Patient' ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <div>
                <label class="form-label-modal">Date Added</label>
                <input type="text" value="<?= !empty($patient['created_at']) ? date('F d, Y h:i A', strtotime($patient['created_at'])) : 'N/A' ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <div>
                <label class="form-label-modal">Last Updated</label>
                <input type="text" value="<?= !empty($patient['updated_at']) ? date('F d, Y h:i A', strtotime($patient['updated_at'])) : 'N/A' ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <div>
                <label class="form-label-modal">Consultation Notes</label>
                <input type="text" value="<?= $patient['note_count'] ?? 0 ?> note(s) recorded" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <?php if (!empty($patient['user_id'])): ?>
                <div>
                    <label class="form-label-modal">Unique Number</label>
                    <input type="text" value="<?= htmlspecialchars($patient['unique_number'] ?? '') ?>" 
                           class="form-input-modal readonly-field" readonly>
                </div>

                <div>
                    <label class="form-label-modal">Registered Email</label>
                    <input type="text" value="<?= htmlspecialchars($patient['registered_email'] ?? '') ?>" 
                           class="form-input-modal readonly-field" readonly>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
    // Conditional display logic for the view modal
    document.addEventListener('DOMContentLoaded', function() {
        // Under treatment conditional
        const underTreatment = document.getElementById('view_under_treatment');
        const treatmentDiv = document.getElementById('view_treatment_condition_div');
        if (underTreatment && treatmentDiv) {
            underTreatment.addEventListener('change', function() {
                treatmentDiv.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        }
        
        // Serious illness conditional
        const seriousIllness = document.getElementById('view_serious_illness');
        const seriousDiv = document.getElementById('view_serious_illness_div');
        if (seriousIllness && seriousDiv) {
            seriousIllness.addEventListener('change', function() {
                seriousDiv.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        }
        
        // Hospitalized conditional
        const hospitalized = document.getElementById('view_hospitalized');
        const hospitalizedDiv = document.getElementById('view_hospitalized_div');
        if (hospitalized && hospitalizedDiv) {
            hospitalized.addEventListener('change', function() {
                hospitalizedDiv.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        }
        
        // Taking medication conditional
        const takingMedication = document.getElementById('view_taking_medication');
        const medicationDiv = document.getElementById('view_medication_div');
        if (takingMedication && medicationDiv) {
            takingMedication.addEventListener('change', function() {
                medicationDiv.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        }
        
        // Allergies conditional
        const hasAllergies = document.getElementById('view_has_allergies');
        const allergySection = document.getElementById('view_allergy_items_section');
        if (hasAllergies && allergySection) {
            hasAllergies.addEventListener('change', function() {
                allergySection.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        }
        
        // Women's health section based on sex
        const sexSelect = document.getElementById('view_student_sex');
        const womensHealthSection = document.getElementById('view_womens_health_section');
        if (sexSelect && womensHealthSection) {
            sexSelect.addEventListener('change', function() {
                womensHealthSection.style.display = this.value === 'F' ? 'block' : 'none';
            });
        }
        
        // Auto-calculate age from birthdate
        const birthdateInput = document.getElementById('view_date_of_birth');
        const ageInput = document.querySelector('input[name="age"]');
        if (birthdateInput && ageInput) {
            function calculateAge() {
                if (birthdateInput.value) {
                    const dob = new Date(birthdateInput.value);
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const monthDiff = today.getMonth() - dob.getMonth();
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    ageInput.value = age;
                }
            }
            birthdateInput.addEventListener('change', calculateAge);
            calculateAge();
        }
        
        // Initialize all conditional sections on page load
        if (underTreatment) underTreatment.dispatchEvent(new Event('change'));
        if (seriousIllness) seriousIllness.dispatchEvent(new Event('change'));
        if (hospitalized) hospitalized.dispatchEvent(new Event('change'));
        if (takingMedication) takingMedication.dispatchEvent(new Event('change'));
        if (hasAllergies) hasAllergies.dispatchEvent(new Event('change'));
        if (sexSelect) sexSelect.dispatchEvent(new Event('change'));
    });
</script>