<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
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
            COALESCE(u.full_name, 'N/A') as registered_by_name,
            u.email as registered_email,
            u.unique_number,
            COALESCE(u.gender, p.gender) as user_gender,
            u.date_of_birth as user_dob,
            u.address as user_address,
            u.contact as user_contact,
            u.sitio as user_sitio,
            u.civil_status as user_civil_status,
            u.occupation as user_occupation
        FROM sitio1_patients p
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.id = ? AND p.deleted_at IS NULL");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT 
            p.*,
            COALESCE(u.full_name, 'N/A') as registered_by_name,
            u.email as registered_email,
            u.unique_number,
            COALESCE(u.gender, p.gender) as user_gender,
            u.date_of_birth as user_dob,
            u.address as user_address,
            u.contact as user_contact,
            u.sitio as user_sitio,
            u.civil_status as user_civil_status,
            u.occupation as user_occupation
        FROM sitio1_patients p
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.id = ? AND p.added_by = ? AND p.deleted_at IS NULL");
        $stmt->execute([$patientId, $staffId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$patient) {
        die(json_encode(['error' => 'Patient not found or access denied']));
    }

    // Get health information
    $stmt = $pdo->prepare("SELECT * FROM existing_info_patients WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $healthInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Merge data
    $patientData = array_merge($patient, $healthInfo ?: []);

    // Calculate age if date of birth exists
    if (!empty($patientData['date_of_birth'])) {
        $dob = new DateTime($patientData['date_of_birth']);
        $today = new DateTime();
        $age = $dob->diff($today)->y;
        $patientData['age'] = $age;
    }

} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}

// Now output the HTML form
?>

<!-- Patient Information Form -->
<form id="healthInfoForm" method="POST" action="existing_info_patients.php" class="space-y-8">
    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patientId) ?>">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($patientData['user_id'] ?? '0') ?>">
    <input type="hidden" name="save_health_info" value="1">

    <!-- Personal Information Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.125 16.875C13.125 17.0975 13.059 17.315 12.9354 17.5C12.8118 17.685 12.6361 17.8292 12.4305 17.9144C12.225 17.9995 11.9988 18.0218 11.7805 17.9784C11.5623 17.935 11.3618 17.8278 11.2045 17.6705C11.0472 17.5132 10.94 17.3127 10.8966 17.0945C10.8532 16.8762 10.8755 16.65 10.9606 16.4445C11.0458 16.2389 11.19 16.0632 11.375 15.9396C11.56 15.816 11.7775 15.75 12 15.75C12.2984 15.75 12.5845 15.8685 12.7955 16.0795C13.0065 16.2905 13.125 16.5766 13.125 16.875ZM12 6.75C9.93188 6.75 8.25 8.26406 8.25 10.125V10.5C8.25 10.6989 8.32902 10.8897 8.46967 11.0303C8.61033 11.171 8.80109 11.25 9 11.25C9.19892 11.25 9.38968 11.171 9.53033 11.0303C9.67099 10.8897 9.75 10.6989 9.75 10.5V10.125C9.75 9.09375 10.7597 8.25 12 8.25C13.2403 8.25 14.25 9.09375 14.25 10.125C14.25 11.1562 13.2403 12 12 12C11.8011 12 11.6103 12.079 11.4697 12.2197C11.329 12.3603 11.25 12.5511 11.25 12.75V13.5C11.25 13.6989 11.329 13.8897 11.4697 14.0303C11.6103 14.171 11.8011 14.25 12 14.25C12.1989 14.25 12.3897 14.171 12.5303 14.0303C12.671 13.8897 12.75 13.6989 12.75 13.5V13.4325C14.46 13.1184 15.75 11.7544 15.75 10.125C15.75 8.26406 14.0681 6.75 12 6.75ZM21.75 12C21.75 13.9284 21.1782 15.8134 20.1068 17.4168C19.0355 19.0202 17.5127 20.2699 15.7312 21.0078C13.9496 21.7458 11.9892 21.9389 10.0979 21.5627C8.20656 21.1865 6.46928 20.2579 5.10571 18.8943C3.74215 17.5307 2.81355 15.7934 2.43735 13.9021C2.06114 12.0108 2.25422 10.0504 2.99218 8.26884C3.73013 6.48726 4.97982 4.96452 6.58319 3.89317C8.18657 2.82183 10.0716 2.25 12 2.25C14.585 2.25273 17.0634 3.28084 18.8913 5.10872C20.7192 6.93661 21.7473 9.41498 21.75 12ZM20.25 12C20.25 10.3683 19.7662 8.77325 18.8596 7.41655C17.9531 6.05984 16.6646 5.00242 15.1571 4.37799C13.6497 3.75357 11.9909 3.59019 10.3905 3.90852C8.79017 4.22685 7.32016 5.01259 6.16637 6.16637C5.01259 7.32015 4.22685 8.79016 3.90853 10.3905C3.5902 11.9908 3.75358 13.6496 4.378 15.1571C5.00242 16.6646 6.05984 17.9531 7.41655 18.8596C8.77326 19.7661 10.3683 20.25 12 20.25C14.1873 20.2475 16.2843 19.3775 17.8309 17.8309C19.3775 16.2843 20.2475 14.1873 20.25 12Z" fill="#3C96E1"/>
</svg>
Personal Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Full Name -->
            <div>
                <label class="form-label-modal required-field">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($patientData['full_name'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter full name">
            </div>

            <!-- Date of Birth -->
            <div>
                <label class="form-label-modal required-field">Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= htmlspecialchars($patientData['date_of_birth'] ?? '') ?>" 
                       required max="<?= date('Y-m-d') ?>" class="form-input-modal">
            </div>

            <!-- Age -->
            <div>
                <label class="form-label-modal">Age (Auto-calculated)</label>
                <input type="number" name="age" value="<?= htmlspecialchars($patientData['age'] ?? '') ?>" 
                       readonly class="form-input-modal readonly-field bg-gray-50">
            </div>

            <!-- Gender -->
            <div>
                <label class="form-label-modal required-field">Gender</label>
                <select name="gender" required class="form-select-modal">
                    <option value="">Select Gender</option>
                    <option value="Male" <?= ($patientData['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($patientData['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($patientData['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <!-- Address -->
            <div>
                <label class="form-label-modal required-field">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($patientData['address'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter complete address">
            </div>

            <!-- Sitio -->
            <div>
                <label class="form-label-modal required-field">Sitio</label>
                <select name="sitio" required class="form-select-modal">
                    <option value="">Select Sitio</option>
                    <option value="Kalinao" <?= ($patientData['sitio'] ?? '') == 'Kalinao' ? 'selected' : '' ?>>Kalinao</option>
                    <option value="Nangka" <?= ($patientData['sitio'] ?? '') == 'Nangka' ? 'selected' : '' ?>>Nangka</option>
                    <option value="Lubi" <?= ($patientData['sitio'] ?? '') == 'Lubi' ? 'selected' : '' ?>>Lubi</option>
                    <option value="Sta. Cruz" <?= ($patientData['sitio'] ?? '') == 'Sta. Cruz' ? 'selected' : '' ?>>Sta. Cruz</option>
                    <option value="Regla" <?= ($patientData['sitio'] ?? '') == 'Regla' ? 'selected' : '' ?>>Regla</option>
                    <option value="Abellana" <?= ($patientData['sitio'] ?? '') == 'Abellana' ? 'selected' : '' ?>>Abellana</option>
                    <option value="Sto.niño l" <?= ($patientData['sitio'] ?? '') == 'Sto.niño l' ? 'selected' : '' ?>>Sto.niño l</option>
                    <option value="Sto.niño ll" <?= ($patientData['sitio'] ?? '') == 'Sto.niño ll' ? 'selected' : '' ?>>Sto.niño ll</option>
                    <option value="Sto.niño lll" <?= ($patientData['sitio'] ?? '') == 'Sto.niño lll' ? 'selected' : '' ?>>Sto.niño lll</option>
                    <option value="Zapatera" <?= ($patientData['sitio'] ?? '') == 'Zapatera' ? 'selected' : '' ?>>Zapatera</option>
                    <option value="Mabuhay" <?= ($patientData['sitio'] ?? '') == 'Mabuhay' ? 'selected' : '' ?>>Mabuhay</option>
                    <option value="San Vicente" <?= ($patientData['sitio'] ?? '') == 'San Vicente' ? 'selected' : '' ?>>San Vicente</option>
                    <option value="City Central" <?= ($patientData['sitio'] ?? '') == 'City Central' ? 'selected' : '' ?>>City Central</option>
                    <option value="San. Antonio" <?= ($patientData['sitio'] ?? '') == 'San. Antonio' ? 'selected' : '' ?>>San. Antonio</option>
                    <option value="San Roque" <?= ($patientData['sitio'] ?? '') == 'San Roque' ? 'selected' : '' ?>>San Roque</option>
                </select>
            </div>

            <!-- Civil Status -->
            <div>
                <label class="form-label-modal required-field">Civil Status</label>
                <select name="civil_status" required class="form-select-modal">
                    <option value="">Select Status</option>
                    <option value="Single" <?= ($patientData['civil_status'] ?? '') == 'Single' ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= ($patientData['civil_status'] ?? '') == 'Married' ? 'selected' : '' ?>>Married</option>
                    <option value="Widowed" <?= ($patientData['civil_status'] ?? '') == 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                    <option value="Separated" <?= ($patientData['civil_status'] ?? '') == 'Separated' ? 'selected' : '' ?>>Separated</option>
                    <option value="Divorced" <?= ($patientData['civil_status'] ?? '') == 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                </select>
            </div>

            <!-- Occupation -->
            <div>
                <label class="form-label-modal">Occupation</label>
                <input type="text" name="occupation" value="<?= htmlspecialchars($patientData['occupation'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter occupation">
            </div>

            <!-- Contact Number -->
            <div>
                <label class="form-label-modal required-field">Contact Number</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($patientData['contact'] ?? '') ?>" 
                       required class="form-input-modal" placeholder="Enter contact number">
            </div>

            <!-- PHIC No. -->
            <div>
                <label class="form-label-modal">PHIC Number</label>
                <input type="text" name="phic_no" value="<?= htmlspecialchars($patientData['phic_no'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter PHIC Number">
            </div>

            <!-- BHW Assigned -->
            <div>
                <label class="form-label-modal">BHW Assigned</label>
                <input type="text" name="bhw_assigned" value="<?= htmlspecialchars($patientData['bhw_assigned'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter BHW Name">
            </div>

            <!-- Family No. -->
            <div>
                <label class="form-label-modal">Family Number</label>
                <input type="text" name="family_no" value="<?= htmlspecialchars($patientData['family_no'] ?? '') ?>" 
                       class="form-input-modal" placeholder="Enter Family Number">
            </div>

            <!-- 4P's Member -->
            <div>
                <label class="form-label-modal">4P's Member</label>
                <select name="fourps_member" class="form-select-modal">
                    <option value="No" <?= ($patientData['fourps_member'] ?? 'No') == 'No' ? 'selected' : '' ?>>No</option>
                    <option value="Yes" <?= ($patientData['fourps_member'] ?? 'No') == 'Yes' ? 'selected' : '' ?>>Yes</option>
                </select>
            </div>

            <!-- Last Checkup -->
            <div>
                <label class="form-label-modal">Last Check-up Date</label>
                <input type="date" name="last_checkup" value="<?= htmlspecialchars($patientData['last_checkup'] ?? '') ?>" 
                       class="form-input-modal">
            </div>
        </div>
    </div>

    <!-- Medical Information Section -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.625 15C20.625 15.2225 20.559 15.44 20.4354 15.625C20.3118 15.81 20.1361 15.9542 19.9305 16.0394C19.725 16.1245 19.4988 16.1468 19.2805 16.1034C19.0623 16.06 18.8618 15.9528 18.7045 15.7955C18.5472 15.6382 18.44 15.4377 18.3966 15.2195C18.3532 15.0012 18.3755 14.775 18.4606 14.5695C18.5458 14.3639 18.69 14.1882 18.875 14.0646C19.06 13.941 19.2775 13.875 19.5 13.875C19.7984 13.875 20.0845 13.9935 20.2955 14.2045C20.5065 14.4155 20.625 14.7016 20.625 15ZM20.1984 18.6834C20.0337 19.7455 19.4949 20.7137 18.6793 21.4135C17.8636 22.1133 16.8247 22.4986 15.75 22.5H13.5C12.3069 22.4988 11.163 22.0243 10.3194 21.1806C9.47575 20.337 9.00124 19.1931 9 18V14.2022C7.55018 14.0195 6.21686 13.3141 5.25025 12.2182C4.28364 11.1223 3.75018 9.71128 3.75 8.25V3.75C3.75 3.55109 3.82902 3.36032 3.96967 3.21967C4.11032 3.07902 4.30109 3 4.5 3H6.75C6.94891 3 7.13968 3.07902 7.28033 3.21967C7.42098 3.36032 7.5 3.55109 7.5 3.75C7.5 3.94891 7.42098 4.13968 7.28033 4.28033C7.13968 4.42098 6.94891 4.5 6.75 4.5H5.25V8.25C5.24995 8.84603 5.3683 9.43614 5.59819 9.98605C5.82808 10.536 6.16492 11.0347 6.58916 11.4534C7.0134 11.872 7.51658 12.2022 8.06949 12.4248C8.6224 12.6474 9.21402 12.7579 9.81 12.75C12.2578 12.7181 14.25 10.6641 14.25 8.17219V4.5H12.75C12.5511 4.5 12.3603 4.42098 12.2197 4.28033C12.079 4.13968 12 3.94891 12 3.75C12 3.55109 12.079 3.36032 12.2197 3.21967C12.3603 3.07902 12.5511 3 12.75 3H15C15.1989 3 15.3897 3.07902 15.5303 3.21967C15.671 3.36032 15.75 3.55109 15.75 3.75V8.17219C15.75 11.2509 13.4503 13.8244 10.5 14.2012V18C10.5 18.7956 10.8161 19.5587 11.3787 20.1213C11.9413 20.6839 12.7044 21 13.5 21H15.75C16.4313 20.9989 17.0919 20.7663 17.6237 20.3405C18.1555 19.9147 18.5269 19.3208 18.6769 18.6562C17.7711 18.4528 16.973 17.9206 16.437 17.1626C15.9009 16.4046 15.6651 15.4748 15.7752 14.553C15.8852 13.6312 16.3332 12.7829 17.0326 12.1724C17.7319 11.5619 18.6329 11.2325 19.5611 11.2479C20.4893 11.2634 21.3788 11.6226 22.0575 12.256C22.7362 12.8895 23.1557 13.7521 23.235 14.6771C23.3143 15.602 23.0477 16.5235 22.4868 17.2633C21.9259 18.003 21.1105 18.5083 20.1984 18.6816V18.6834ZM21.75 15C21.75 14.555 21.618 14.12 21.3708 13.75C21.1236 13.38 20.7722 13.0916 20.361 12.9213C19.9499 12.751 19.4975 12.7064 19.061 12.7932C18.6246 12.88 18.2237 13.0943 17.909 13.409C17.5943 13.7237 17.38 14.1246 17.2932 14.561C17.2064 14.9975 17.251 15.4499 17.4213 15.861C17.5916 16.2722 17.88 16.6236 18.25 16.8708C18.62 17.118 19.055 17.25 19.5 17.25C20.0967 17.25 20.669 17.0129 21.091 16.591C21.5129 16.169 21.75 15.5967 21.75 15Z" fill="#3C96E1"/>
</svg>
Medical Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Height -->
            <div>
                <label class="form-label-modal required-field">Height (cm)</label>
                <input type="number" name="height" value="<?= htmlspecialchars($patientData['height'] ?? '') ?>" 
                       required step="0.1" min="0" class="form-input-modal" placeholder="Enter height">
            </div>

            <!-- Weight -->
            <div>
                <label class="form-label-modal required-field">Weight (kg)</label>
                <input type="number" name="weight" value="<?= htmlspecialchars($patientData['weight'] ?? '') ?>" 
                       required step="0.1" min="0" class="form-input-modal" placeholder="Enter weight">
            </div>

            <!-- Temperature -->
            <div>
                <label class="form-label-modal">Temperature (°C)</label>
                <input type="number" name="temperature" value="<?= htmlspecialchars($patientData['temperature'] ?? '') ?>" 
                       step="0.1" class="form-input-modal" placeholder="Enter temperature">
            </div>

            <!-- Blood Pressure -->
            <div>
                <label class="form-label-modal">Blood Pressure</label>
                <input type="text" name="blood_pressure" value="<?= htmlspecialchars($patientData['blood_pressure'] ?? '') ?>" 
                       class="form-input-modal" placeholder="e.g., 120/80">
            </div>

            <!-- Blood Type -->
            <div>
                <label class="form-label-modal required-field">Blood Type</label>
                <select name="blood_type" required class="form-select-modal">
                    <option value="">Select Blood Type</option>
                    <option value="A+" <?= ($patientData['blood_type'] ?? '') == 'A+' ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= ($patientData['blood_type'] ?? '') == 'A-' ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= ($patientData['blood_type'] ?? '') == 'B+' ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= ($patientData['blood_type'] ?? '') == 'B-' ? 'selected' : '' ?>>B-</option>
                    <option value="AB+" <?= ($patientData['blood_type'] ?? '') == 'AB+' ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= ($patientData['blood_type'] ?? '') == 'AB-' ? 'selected' : '' ?>>AB-</option>
                    <option value="O+" <?= ($patientData['blood_type'] ?? '') == 'O+' ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= ($patientData['blood_type'] ?? '') == 'O-' ? 'selected' : '' ?>>O-</option>
                    <option value="Unknown" <?= ($patientData['blood_type'] ?? '') == 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                </select>
            </div>
        </div>

        <!-- Medical History Textareas -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Allergies</label>
                <textarea name="allergies" rows="3" class="form-textarea-modal" 
                          placeholder="List any allergies..."><?= htmlspecialchars($patientData['allergies'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Current Medications</label>
                <textarea name="current_medications" rows="3" class="form-textarea-modal" 
                          placeholder="List current medications..."><?= htmlspecialchars($patientData['current_medications'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Immunization Record</label>
                <textarea name="immunization_record" rows="3" class="form-textarea-modal" 
                          placeholder="Immunization history..."><?= htmlspecialchars($patientData['immunization_record'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label-modal">Chronic Conditions</label>
                <textarea name="chronic_conditions" rows="3" class="form-textarea-modal" 
                          placeholder="Chronic health conditions..."><?= htmlspecialchars($patientData['chronic_conditions'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="mt-6">
            <label class="form-label-modal">Medical History</label>
            <textarea name="medical_history" rows="4" class="form-textarea-modal" 
                      placeholder="Detailed medical history..."><?= htmlspecialchars($patientData['medical_history'] ?? '') ?></textarea>
        </div>

        <div class="mt-6">
            <label class="form-label-modal">Family Medical History</label>
            <textarea name="family_history" rows="4" class="form-textarea-modal" 
                      placeholder="Family medical history..."><?= htmlspecialchars($patientData['family_history'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Additional Information (Readonly) -->
    <div class="bg-white p-8 rounded-2xl border-2 border-blue-100">
        <h4 class="text-xl mb-6 font-medium text-[#3C96E1] gap-3 flex items-center">
            <svg width="40" height="40" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.7017 4.59869L9.43807 1.90338C8.94841 1.81722 8.44458 1.92905 8.03737 2.2143C7.63015 2.49955 7.3529 2.93485 7.26659 3.42448L3.78026 23.2292C3.73762 23.4718 3.7432 23.7204 3.7967 23.9609C3.85019 24.2014 3.95054 24.4289 4.09202 24.6306C4.2335 24.8322 4.41333 25.004 4.62124 25.1362C4.82914 25.2683 5.06104 25.3582 5.30369 25.4006L20.5674 28.096C20.8101 28.1388 21.0588 28.1333 21.2994 28.0799C21.54 28.0265 21.7677 27.9262 21.9695 27.7847C22.1713 27.6432 22.3432 27.4633 22.4754 27.2553C22.6077 27.0473 22.6976 26.8153 22.74 26.5725L26.2263 6.76784C26.3118 6.27802 26.1991 5.77434 25.9132 5.36756C25.6273 4.96078 25.1915 4.68422 24.7017 4.59869ZM20.8908 26.2491L5.62596 23.5538L9.11229 3.74909L24.376 6.4444L20.8908 26.2491ZM10.4705 6.84518C10.5139 6.60046 10.6527 6.383 10.8564 6.2406C11.0602 6.0982 11.3121 6.04252 11.5568 6.0858L21.2834 7.8026C21.5145 7.8431 21.7221 7.96882 21.8651 8.15493C22.008 8.34104 22.076 8.574 22.0555 8.80779C22.0351 9.04158 21.9277 9.25919 21.7546 9.41763C21.5814 9.57607 21.3552 9.66382 21.1205 9.66354C21.0655 9.66346 21.0106 9.65876 20.9564 9.64948L11.2299 7.93151C10.9851 7.88808 10.7677 7.74926 10.6253 7.54555C10.4829 7.34184 10.4272 7.08992 10.4705 6.84518ZM9.82127 10.5389C9.84264 10.4177 9.88769 10.3018 9.95385 10.1979C10.02 10.094 10.106 10.0042 10.2069 9.9336C10.3078 9.86298 10.4216 9.81292 10.5418 9.78628C10.662 9.75965 10.7863 9.75696 10.9076 9.77838L20.6342 11.4964C20.867 11.5353 21.0765 11.6606 21.2209 11.8472C21.3654 12.0339 21.4341 12.2682 21.4134 12.5033C21.3927 12.7384 21.284 12.957 21.1092 13.1156C20.9343 13.2741 20.7061 13.3608 20.4701 13.3585C20.4147 13.3586 20.3593 13.3535 20.3049 13.3432L10.5783 11.6264C10.3338 11.5825 10.1167 11.4432 9.97475 11.2393C9.8328 11.0354 9.7776 10.7835 9.82127 10.5389ZM9.17088 14.2315C9.21512 13.9874 9.35429 13.7708 9.5579 13.6292C9.76152 13.4875 10.013 13.4323 10.2572 13.4756L15.1181 14.3299C15.3492 14.3704 15.5567 14.4961 15.6997 14.682C15.8426 14.868 15.9107 15.1008 15.8904 15.3345C15.87 15.5682 15.7629 15.7858 15.59 15.9444C15.4171 16.1029 15.191 16.1909 14.9564 16.1909C14.9014 16.1909 14.8466 16.1862 14.7924 16.1768L9.92908 15.3178C9.68458 15.2741 9.46741 15.1352 9.32525 14.9315C9.1831 14.7278 9.12758 14.4761 9.17088 14.2315Z" fill="#3C96E1"/>
</svg>
Additional Information
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label-modal">Patient Type</label>
                <input type="text" value="<?= !empty($patientData['user_id']) ? 'Registered Patient' : 'Regular Patient' ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <div>
                <label class="form-label-modal">Date Added</label>
                <input type="text" value="<?= !empty($patientData['created_at']) ? date('F d, Y h:i A', strtotime($patientData['created_at'])) : 'N/A' ?>" 
                       class="form-input-modal readonly-field" readonly>
            </div>

            <?php if (!empty($patientData['user_id'])): ?>
                <div>
                    <label class="form-label-modal">Unique Number</label>
                    <input type="text" value="<?= htmlspecialchars($patientData['unique_number'] ?? '') ?>" 
                           class="form-input-modal readonly-field" readonly>
                </div>

                <div>
                    <label class="form-label-modal">Registered Email</label>
                    <input type="text" value="<?= htmlspecialchars($patientData['registered_email'] ?? '') ?>" 
                           class="form-input-modal readonly-field" readonly>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>