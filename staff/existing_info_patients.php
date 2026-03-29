<?php
ob_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle AJAX request FIRST - before any HTML output
if (isset($_GET['ajax_get_patients']) && $_GET['ajax_get_patients'] == '1') {
    // Set JSON header
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    try {
        // Include necessary files
        require_once __DIR__ . '/../includes/auth.php';
        require_once __DIR__ . '/../includes/functions.php';

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            throw new Exception('User not authenticated');
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $recordsPerPage = 10;
        $offset = ($page - 1) * $recordsPerPage;

        // Build queries
        $countQuery = "SELECT COUNT(*) as total FROM sitio1_patients p WHERE p.deleted_at IS NULL";
        $selectQuery = "SELECT 
            p.id,
            p.full_name,
            p.age,
            p.last_checkup,
            e.blood_type,
            CASE 
                WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                ELSE 'Regular Patient'
            END as patient_type
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        WHERE p.deleted_at IS NULL";

        $params = [];

        // Add search
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $countQuery .= " AND p.full_name LIKE ?";
            $selectQuery .= " AND p.full_name LIKE ?";
            $params[] = $searchTerm;
        }

        // Add staff restriction
        if (function_exists('staff_can_view_all') && !staff_can_view_all()) {
            $countQuery .= " AND p.added_by = ?";
            $selectQuery .= " AND p.added_by = ?";
            $params[] = $_SESSION['user']['id'];
        }

        // Get total count
        $stmt = $pdo->prepare($countQuery);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $recordsPerPage);

        // Get paginated results
        $selectQuery .= " ORDER BY p.full_name ASC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($selectQuery);

        // Bind parameters
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $recordsPerPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format dates
        foreach ($patients as &$patient) {
            if (!empty($patient['last_checkup'])) {
                $patient['last_checkup_formatted'] = date('M d, Y', strtotime($patient['last_checkup']));
            }
        }

        echo json_encode([
            'success' => true,
            'patients' => $patients,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalRecords' => $totalRecords
        ]);
        exit();
    } catch (Exception $e) {
        // Log error
        error_log("AJAX Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());

        // Return error as JSON
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for staff after 1 hour of inactivity ---
if (isStaff()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 3600) { // 1 hour = 3600 seconds
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/index-admin-staff.php');
            exit();
        } else {
            $_SESSION['last_action'] = $now;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

$message = '';
$error = '';

// Check if columns exist in the database
$civilStatusExists = false;
$occupationExists = false;
$sitioExists = false;
$dateOfBirthExists = false;
$phicNoExists = false;
$bhwAssignedExists = false;
$familyNoExists = false;
$fourpsMemberExists = false;
$doctorNameExists = false;

try {
    // Check columns
    $columns = [
        'civil_status' => &$civilStatusExists,
        'occupation' => &$occupationExists,
        'sitio' => &$sitioExists,
        'date_of_birth' => &$dateOfBirthExists,
        'phic_no' => &$phicNoExists,
        'bhw_assigned' => &$bhwAssignedExists,
        'family_no' => &$familyNoExists,
        'fourps_member' => &$fourpsMemberExists
    ];

    foreach ($columns as $column => &$exists) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM sitio1_patients LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->rowCount() > 0;
    }

    // Add new columns if they don't exist
    $newColumns = [
        'phic_no' => "VARCHAR(20) NULL AFTER occupation",
        'bhw_assigned' => "VARCHAR(100) NULL AFTER phic_no",
        'family_no' => "VARCHAR(50) NULL AFTER bhw_assigned",
        'fourps_member' => "ENUM('Yes', 'No') DEFAULT 'No' AFTER family_no"
    ];

    foreach ($newColumns as $column => $definition) {
        if (!$phicNoExists && $column === 'phic_no') {
            $pdo->exec("ALTER TABLE sitio1_patients ADD COLUMN $column $definition");
        }
    }

    if (!$dateOfBirthExists) {
        $pdo->exec("ALTER TABLE sitio1_patients ADD COLUMN date_of_birth DATE NULL AFTER full_name");
    }

    // Check if doctor_name column exists in consultation_notes
    $stmt = $pdo->prepare("SHOW COLUMNS FROM consultation_notes LIKE 'doctor_name'");
    $stmt->execute();
    $doctorNameExists = $stmt->rowCount() > 0;

    if (!$doctorNameExists) {
        $pdo->exec("ALTER TABLE consultation_notes ADD COLUMN doctor_name VARCHAR(255) NULL AFTER note");
    }

    // Check if consultation_notes table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'consultation_notes'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $createTableQuery = "CREATE TABLE consultation_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            note TEXT NOT NULL,
            doctor_name VARCHAR(255) NULL,
            consultation_date DATE NOT NULL,
            next_consultation_date DATE NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES sitio1_patients(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES sitio1_users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_created_by (created_by)
        )";
        $pdo->exec($createTableQuery);
    }

    // Check if deleted_patients table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'deleted_patients'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $createTableQuery = "CREATE TABLE deleted_patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_id INT NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            date_of_birth DATE NULL,
            age INT,
            gender VARCHAR(50),
            address TEXT,
            contact VARCHAR(100),
            last_checkup DATE,
            added_by INT,
            user_id INT,
            deleted_by INT,
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sitio VARCHAR(255) NULL,
            civil_status VARCHAR(100) NULL,
            occupation VARCHAR(255) NULL,
            phic_no VARCHAR(20) NULL,
            bhw_assigned VARCHAR(100) NULL,
            family_no VARCHAR(50) NULL,
            fourps_member ENUM('Yes', 'No') DEFAULT 'No',
            consent_given TINYINT(1) DEFAULT 1,
            consent_date TIMESTAMP NULL,
            deleted_reason VARCHAR(500) NULL
        )";
        $pdo->exec($createTableQuery);
    }
} catch (PDOException $e) {
    // If we can't check columns, assume they don't exist
    $civilStatusExists = $occupationExists = $sitioExists = $dateOfBirthExists =
        $phicNoExists = $bhwAssignedExists = $familyNoExists = $fourpsMemberExists = false;
    $doctorNameExists = false;
}

// Handle AJAX request for paginated patient list in export modal
if (isset($_GET['ajax_get_patients']) && $_GET['ajax_get_patients'] == '1') {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    try {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $recordsPerPage = 10; // Show 10 records per page in modal
        $offset = ($page - 1) * $recordsPerPage;

        // Debug log
        error_log("AJAX Request: page=$page, search=$search, offset=$offset");

        // Base query
        $countQuery = "SELECT COUNT(*) as total FROM sitio1_patients p WHERE p.deleted_at IS NULL";
        $selectQuery = "SELECT 
            p.id,
            p.full_name,
            p.age,
            p.last_checkup,
            e.blood_type,
            CASE 
                WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                ELSE 'Regular Patient'
            END as patient_type
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        WHERE p.deleted_at IS NULL";

        $params = [];
        $countParams = [];

        // Add search condition if provided
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $countQuery .= " AND p.full_name LIKE ?";
            $selectQuery .= " AND p.full_name LIKE ?";
            $params[] = $searchTerm;
            $countParams[] = $searchTerm;
        }

        // Apply staff restriction if not viewing all records
        if (!staff_can_view_all()) {
            $countQuery .= " AND p.added_by = ?";
            $selectQuery .= " AND p.added_by = ?";
            $params[] = $_SESSION['user']['id'];
            $countParams[] = $_SESSION['user']['id'];
        }

        // Get total count
        $stmt = $pdo->prepare($countQuery);

        // Bind parameters for count query
        for ($i = 0; $i < count($countParams); $i++) {
            $stmt->bindValue($i + 1, $countParams[$i], PDO::PARAM_STR);
        }

        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $recordsPerPage);

        // Get paginated results
        $selectQuery .= " ORDER BY p.full_name ASC LIMIT ? OFFSET ?";

        $stmt = $pdo->prepare($selectQuery);

        // Bind parameters for select query
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $recordsPerPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for display
        foreach ($patients as &$patient) {
            if (!empty($patient['last_checkup'])) {
                $patient['last_checkup_formatted'] = date('M d, Y', strtotime($patient['last_checkup']));
            } else {
                $patient['last_checkup_formatted'] = 'N/A';
            }
        }

        echo json_encode([
            'success' => true,
            'patients' => $patients,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalRecords' => $totalRecords
        ]);
        exit();
    } catch (PDOException $e) {
        error_log("PDO Error in ajax_get_patients: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    } catch (Exception $e) {
        error_log("General Error in ajax_get_patients: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Handle form submission for editing health info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_health_info'])) {
    $required = ['patient_id', 'full_name', 'date_of_birth', 'gender', 'address', 'contact', 'height', 'weight', 'blood_type'];
    $missing = [];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        $error = "Please fill in all required fields: " . implode(', ', str_replace('_', ' ', $missing));
    } else {
        try {
            $patient_id = $_POST['patient_id'];

            // Personal Information
            $full_name = $_POST['full_name'];
            $date_of_birth = $_POST['date_of_birth'];
            $age = $_POST['age'];
            $gender = $_POST['gender'];
            $address = $_POST['address'];
            $sitio = $_POST['sitio'];
            $civil_status = $_POST['civil_status'];
            $occupation = !empty($_POST['occupation']) ? $_POST['occupation'] : null;
            $contact = $_POST['contact'];
            $last_checkup = !empty($_POST['last_checkup']) ? $_POST['last_checkup'] : null;

            // Additional Fields
            $phic_no = !empty($_POST['phic_no']) ? $_POST['phic_no'] : null;
            $bhw_assigned = !empty($_POST['bhw_assigned']) ? $_POST['bhw_assigned'] : null;
            $family_no = !empty($_POST['family_no']) ? $_POST['family_no'] : null;
            $fourps_member = !empty($_POST['fourps_member']) ? $_POST['fourps_member'] : 'No';

            // Medical Information
            $height = $_POST['height'];
            $weight = $_POST['weight'];
            $blood_type = $_POST['blood_type'];
            $temperature = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
            $blood_pressure = !empty($_POST['blood_pressure']) ? $_POST['blood_pressure'] : null;
            $allergies = !empty($_POST['allergies']) ? $_POST['allergies'] : null;
            $medical_history = !empty($_POST['medical_history']) ? $_POST['medical_history'] : null;
            $current_medications = !empty($_POST['current_medications']) ? $_POST['current_medications'] : null;
            $family_history = !empty($_POST['family_history']) ? $_POST['family_history'] : null;
            $immunization_record = !empty($_POST['immunization_record']) ? $_POST['immunization_record'] : null;
            $chronic_conditions = !empty($_POST['chronic_conditions']) ? $_POST['chronic_conditions'] : null;

            // Start transaction
            $pdo->beginTransaction();

            // Update main patient table with ALL personal information
            if (staff_can_view_all()) {
                $updatePatientQuery = "UPDATE sitio1_patients SET 
                    full_name = ?, 
                    date_of_birth = ?, 
                    age = ?, 
                    gender = ?, 
                    address = ?, 
                    sitio = ?, 
                    civil_status = ?, 
                    occupation = ?, 
                    contact = ?, 
                    last_checkup = ?,
                    phic_no = ?, 
                    bhw_assigned = ?, 
                    family_no = ?, 
                    fourps_member = ?,
                    updated_at = NOW()
                    WHERE id = ?";

                $stmt = $pdo->prepare($updatePatientQuery);
                $stmt->execute([
                    $full_name,
                    $date_of_birth,
                    $age,
                    $gender,
                    $address,
                    $sitio,
                    $civil_status,
                    $occupation,
                    $contact,
                    $last_checkup,
                    $phic_no,
                    $bhw_assigned,
                    $family_no,
                    $fourps_member,
                    $patient_id
                ]);
            } else {
                $updatePatientQuery = "UPDATE sitio1_patients SET 
                    full_name = ?, 
                    date_of_birth = ?, 
                    age = ?, 
                    gender = ?, 
                    address = ?, 
                    sitio = ?, 
                    civil_status = ?, 
                    occupation = ?, 
                    contact = ?, 
                    last_checkup = ?,
                    phic_no = ?, 
                    bhw_assigned = ?, 
                    family_no = ?, 
                    fourps_member = ?,
                    updated_at = NOW()
                    WHERE id = ? AND added_by = ?";

                $stmt = $pdo->prepare($updatePatientQuery);
                $stmt->execute([
                    $full_name,
                    $date_of_birth,
                    $age,
                    $gender,
                    $address,
                    $sitio,
                    $civil_status,
                    $occupation,
                    $contact,
                    $last_checkup,
                    $phic_no,
                    $bhw_assigned,
                    $family_no,
                    $fourps_member,
                    $patient_id,
                    $_SESSION['user']['id']
                ]);
            }

            // Check if medical record exists
            $stmt = $pdo->prepare("SELECT id FROM existing_info_patients WHERE patient_id = ?");
            $stmt->execute([$patient_id]);

            if ($stmt->fetch()) {
                // Update existing medical record
                $stmt = $pdo->prepare("UPDATE existing_info_patients SET 
                    gender = ?, height = ?, weight = ?, blood_type = ?, temperature = ?, 
                    blood_pressure = ?, allergies = ?, medical_history = ?, 
                    current_medications = ?, family_history = ?, immunization_record = ?,
                    chronic_conditions = ?, updated_at = NOW()
                    WHERE patient_id = ?");
                $stmt->execute([
                    $gender,
                    $height,
                    $weight,
                    $blood_type,
                    $temperature,
                    $blood_pressure,
                    $allergies,
                    $medical_history,
                    $current_medications,
                    $family_history,
                    $immunization_record,
                    $chronic_conditions,
                    $patient_id
                ]);
            } else {
                // Insert new medical record
                $stmt = $pdo->prepare("INSERT INTO existing_info_patients 
                    (patient_id, gender, height, weight, blood_type, temperature,
                    blood_pressure, allergies, medical_history, current_medications, 
                    family_history, immunization_record, chronic_conditions)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $patient_id,
                    $gender,
                    $height,
                    $weight,
                    $blood_type,
                    $temperature,
                    $blood_pressure,
                    $allergies,
                    $medical_history,
                    $current_medications,
                    $family_history,
                    $immunization_record,
                    $chronic_conditions
                ]);
            }

            $pdo->commit();
            $message = "Patient information saved successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error saving patient information: " . $e->getMessage();
        }
    }
}

/**
 * Function to check if a patient record already exists
 * Checks by full name and date of birth combination
 * 
 * @param PDO $pdo Database connection
 * @param string $fullName Patient's full name
 * @param string $dateOfBirth Patient's date of birth (YYYY-MM-DD)
 * @param int|null $staffId Current staff member's ID (for non-shared records)
 * @param bool $isStaffViewAll Whether staff can view all records
 * @return array|bool Returns array with existing patient data if found, false otherwise
 */
function checkDuplicatePatient($pdo, $fullName, $dateOfBirth, $staffId, $isStaffViewAll)
{
    try {
        if ($isStaffViewAll) {
            // Check all records without staff restriction
            $stmt = $pdo->prepare("SELECT id, full_name, date_of_birth, contact FROM sitio1_patients 
                                 WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
                                 AND date_of_birth = ? 
                                 AND deleted_at IS NULL
                                 LIMIT 1");
            $stmt->execute([$fullName, $dateOfBirth]);
        } else {
            // Check only records added by current staff member
            $stmt = $pdo->prepare("SELECT id, full_name, date_of_birth, contact FROM sitio1_patients 
                                 WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
                                 AND date_of_birth = ? 
                                 AND added_by = ?
                                 AND deleted_at IS NULL
                                 LIMIT 1");
            $stmt->execute([$fullName, $dateOfBirth, $staffId]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : false;
    } catch (PDOException $e) {
        error_log("Duplicate check error: " . $e->getMessage());
        return false;
    }
}

// Handle Child Health Record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_child_health'])) {
    try {
        // Prepare child health record data
        $childData = [
            'family_no' => $_POST['family_no'] ?? '',
            'ufc_no' => $_POST['ufc_no'] ?? '',
            'fullname' => $_POST['fullname'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'dob' => $_POST['dob'] ?? '',
            'birth_order' => $_POST['birth_order'] ?? null,
            'place_of_delivery' => $_POST['place_of_delivery'] ?? null,
            'mother' => $_POST['mother'] ?? null,
            'mother_age' => !empty($_POST['mother_age']) ? (int) $_POST['mother_age'] : null,
            'father_occupation' => $_POST['father_occupation'] ?? null,
            'father' => $_POST['father'] ?? null,
            'father_age' => !empty($_POST['father_age']) ? (int) $_POST['father_age'] : null,
            'address' => $_POST['address'] ?? null,
            'type_of_feeding' => $_POST['type_of_feeding'] ?? null,
            'date_referred_newborn' => !empty($_POST['date_referred_newborn']) ? $_POST['date_referred_newborn'] : null,
            'bf1' => !empty($_POST['bf1']) ? $_POST['bf1'] : null,
            'bf2' => !empty($_POST['bf2']) ? $_POST['bf2'] : null,
            'bf3' => !empty($_POST['bf3']) ? $_POST['bf3'] : null,
            'bf4' => !empty($_POST['bf4']) ? $_POST['bf4'] : null,
            'bf5' => !empty($_POST['bf5']) ? $_POST['bf5'] : null,
            'child_protected_at_birth' => $_POST['child_protected_at_birth'] ?? null,
            'date_assessed' => !empty($_POST['date_assessed']) ? $_POST['date_assessed'] : null,
            'tt_status_mother' => $_POST['tt_status_mother'] ?? null,
            'anemic_children_seen' => $_POST['anemic_children_seen'] ?? null,
            'anemic_children_iron' => $_POST['anemic_children_iron'] ?? null,
            'birthwt' => $_POST['birthwt'] ?? null,
            'low_birthwt_seen' => $_POST['low_birthwt_seen'] ?? null,
            'low_birthwt_iron' => $_POST['low_birthwt_iron'] ?? null,
            'date_iron_started' => !empty($_POST['date_iron_started']) ? $_POST['date_iron_started'] : null,
            'vit_a_1' => $_POST['vit_a_1'] ?? null,
            'vit_a_2' => $_POST['vit_a_2'] ?? null,
            'vit_a_3' => $_POST['vit_a_3'] ?? null,
            'completed' => $_POST['completed'] ?? null
        ];

        // Insert into child_health_records
        $stmt = $pdo->prepare("INSERT INTO child_health_records 
            (family_no, ufc_no, fullname, sex, dob, birth_order, place_of_delivery, mother, mother_age, 
             father_occupation, father, father_age, address, type_of_feeding, date_referred_newborn,
             bf1, bf2, bf3, bf4, bf5, child_protected_at_birth, date_assessed, tt_status_mother,
             anemic_children_seen, anemic_children_iron, birthwt, low_birthwt_seen, low_birthwt_iron,
             date_iron_started, vit_a_1, vit_a_2, vit_a_3, completed, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute(array_values($childData));
        $childRecordId = $pdo->lastInsertId();

        // Handle immunizations
        if (isset($_POST['immunizations']) && is_array($_POST['immunizations'])) {
            foreach ($_POST['immunizations'] as $type => $vaccinations) {
                $stmt = $pdo->prepare("INSERT INTO child_immunizations 
                    (child_health_record_id, type, within_24hrs, first, second, third) 
                    VALUES (?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $childRecordId,
                    $type,
                    isset($vaccinations['24hrs']) ? 1 : 0,
                    isset($vaccinations['1st']) ? 1 : 0,
                    isset($vaccinations['2nd']) ? 1 : 0,
                    isset($vaccinations['3rd']) ? 1 : 0
                ]);
            }
        }

        // Handle results
        if (isset($_POST['results']) && is_array($_POST['results'])) {
            foreach ($_POST['results'] as $result) {
                $stmt = $pdo->prepare("INSERT INTO child_health_results 
                    (child_health_record_id, result_date, age, weight, temperature, height, findings, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $childRecordId,
                    $result['date'] ?? null,
                    $result['age'] ?? null,
                    $result['weight'] ?? null,
                    $result['temperature'] ?? null,
                    $result['height'] ?? null,
                    $result['findings'] ?? null,
                    $result['notes'] ?? null
                ]);
            }
        }

        $message = "Child Health Record saved successfully!";
    } catch (PDOException $e) {
        $error = "Error saving Child Health Record: " . $e->getMessage();
    }
}

// Handle Present Pregnant Record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_present_pregnant'])) {
    try {
        // Prepare present pregnant record data
        $pregnantData = [
            'patient_id' => !empty($_POST['patient_id']) ? (int) $_POST['patient_id'] : null,
            'birth_plan' => $_POST['birth_plan'] ?? '',
            'nutrition_breastfeeding' => $_POST['nutrition_breastfeeding'] ?? '',
            'family_planning' => $_POST['family_planning'] ?? '',
            'tt_vaccination' => $_POST['tt_vaccination'] ?? '',
            'iron_folic' => $_POST['iron_folic'] ?? '',
            'vitamin_a' => $_POST['vitamin_a'] ?? '',
            'prenatal_schedule' => $_POST['prenatal_schedule'] ?? '',
            'visit_notes' => $_POST['visit_notes'] ?? null,
            'referrals' => $_POST['referrals'] ?? null,
            'gravidity' => !empty($_POST['gravidity']) ? (int) $_POST['gravidity'] : null,
            'parity' => !empty($_POST['parity']) ? (int) $_POST['parity'] : null,
            'prev_outcomes' => $_POST['prev_outcomes'] ?? null,
            'lmp' => !empty($_POST['lmp']) ? $_POST['lmp'] : null,
            'cycle_regularity' => $_POST['cycle_regularity'] ?? null,
            'contraceptive_history' => $_POST['contraceptive_history'] ?? null,
            'past_illnesses' => $_POST['past_illnesses'] ?? null,
            'allergies' => $_POST['allergies'] ?? null,
            'family_history' => $_POST['family_history'] ?? null,
            'edd' => !empty($_POST['edd']) ? $_POST['edd'] : null,
            'gestational_age' => $_POST['gestational_age'] ?? null,
            'risk_assessment' => $_POST['risk_assessment'] ?? null,
            'danger_signs' => $_POST['danger_signs'] ?? null,
            'bp' => $_POST['bp'] ?? null,
            'hr' => $_POST['hr'] ?? null,
            'rr' => $_POST['rr'] ?? null,
            'temperature' => $_POST['temperature'] ?? null,
            'weight' => $_POST['weight'] ?? null,
            'height' => $_POST['height'] ?? null,
            'fundal_height' => $_POST['fundal_height'] ?? null,
            'fetal_heart_tones' => $_POST['fetal_heart_tones'] ?? null,
            'edema' => $_POST['edema'] ?? null,
            'hemoglobin' => $_POST['hemoglobin'] ?? null,
            'urinalysis' => $_POST['urinalysis'] ?? null,
            'blood_typing' => $_POST['blood_typing'] ?? null,
            'syphilis_test' => $_POST['syphilis_test'] ?? null,
            'hiv_test' => $_POST['hiv_test'] ?? null,
            'hepatitis_b' => $_POST['hepatitis_b'] ?? null,
            'fbs' => $_POST['fbs'] ?? null,
            'emergency_prep' => $_POST['emergency_prep'] ?? null
        ];

        // Note: The current table structure only has basic fields.
        // You may need to expand the table to include all these fields.
        $stmt = $pdo->prepare("INSERT INTO present_pregnant_records 
            (patient_id, birth_plan, nutrition_breastfeeding, family_planning, tt_vaccination, 
             iron_folic, vitamin_a, prenatal_schedule, visit_notes, referrals, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $pregnantData['patient_id'],
            $pregnantData['birth_plan'],
            $pregnantData['nutrition_breastfeeding'],
            $pregnantData['family_planning'],
            $pregnantData['tt_vaccination'],
            $pregnantData['iron_folic'],
            $pregnantData['vitamin_a'],
            $pregnantData['prenatal_schedule'],
            $pregnantData['visit_notes'],
            $pregnantData['referrals']
        ]);

        $message = "Present Pregnant Record saved successfully!";
    } catch (PDOException $e) {
        $error = "Error saving Present Pregnant Record: " . $e->getMessage();
    }
}

// Fetch Child Health Records for display
try {
    $childHealthRecords = $pdo->query("SELECT * FROM child_health_records ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $childHealthRecords = [];
    error_log("Error fetching child health records: " . $e->getMessage());
}

// Fetch Present Pregnant Records for display
try {
    $pregnantRecords = $pdo->query("SELECT * FROM present_pregnant_records ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pregnantRecords = [];
    error_log("Error fetching present pregnant records: " . $e->getMessage());
}

// Handle form submission for adding new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $fullName = trim($_POST['full_name']);
    $dateOfBirth = trim($_POST['date_of_birth']);
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $civil_status = trim($_POST['civil_status']);
    $occupation = trim($_POST['occupation']);
    $address = trim($_POST['address']);
    $sitio = trim($_POST['sitio']);
    $contact = trim($_POST['contact']);
    $lastCheckup = trim($_POST['last_checkup']);
    $phic_no = trim($_POST['phic_no']);
    $bhw_assigned = trim($_POST['bhw_assigned']);
    $family_no = trim($_POST['family_no']);
    $fourps_member = trim($_POST['fourps_member']);
    $consent_given = 1;
    $userId = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;

    // Medical information
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $blood_pressure = trim($_POST['blood_pressure']);
    $bloodType = trim($_POST['blood_type']);
    $allergies = trim($_POST['allergies']);
    $medicalHistory = trim($_POST['medical_history']);
    $currentMedications = trim($_POST['current_medications']);
    $familyHistory = trim($_POST['family_history']);
    $immunizationRecord = trim($_POST['immunization_record']);
    $chronicConditions = trim($_POST['chronic_conditions']);

    if (!empty($fullName) && !empty($dateOfBirth)) {
        // Check if patient already exists
        $existingPatient = checkDuplicatePatient($pdo, $fullName, $dateOfBirth, $_SESSION['user']['id'], staff_can_view_all());

        if ($existingPatient) {
            // Patient already exists - show error
            $error = "This patient record already exists! <br><strong>" . htmlspecialchars($existingPatient['full_name']) . "</strong> 
                     with Date of Birth: <strong>" . date('M d, Y', strtotime($existingPatient['date_of_birth'])) . "</strong><br>
                     Contact: " . htmlspecialchars($existingPatient['contact']) . " <br>
                     <a href='javascript:void(0);' onclick='openViewModal(" . $existingPatient['id'] . ")' class='text-blue-600 hover:text-blue-800 font-semibold'>Click here to view this patient record</a>";
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();

                // Build dynamic INSERT query
                $columns = ["full_name", "date_of_birth", "age", "gender", "address", "contact", "last_checkup", "consent_given", "consent_date", "added_by", "user_id"];
                $placeholders = ["?", "?", "?", "?", "?", "?", "?", "?", "NOW()", "?", "?"];
                $values = [$fullName, $dateOfBirth, $age, $gender, $address, $contact, $lastCheckup, $consent_given, $_SESSION['user']['id'], $userId];

                if ($sitioExists) {
                    $columns[] = "sitio";
                    $placeholders[] = "?";
                    $values[] = $sitio;
                }

                if ($civilStatusExists) {
                    $columns[] = "civil_status";
                    $placeholders[] = "?";
                    $values[] = $civil_status;
                }

                if ($occupationExists) {
                    $columns[] = "occupation";
                    $placeholders[] = "?";
                    $values[] = $occupation;
                }

                if ($phicNoExists) {
                    $columns[] = "phic_no";
                    $placeholders[] = "?";
                    $values[] = $phic_no;
                }

                if ($bhwAssignedExists) {
                    $columns[] = "bhw_assigned";
                    $placeholders[] = "?";
                    $values[] = $bhw_assigned;
                }

                if ($familyNoExists) {
                    $columns[] = "family_no";
                    $placeholders[] = "?";
                    $values[] = $family_no;
                }

                if ($fourpsMemberExists) {
                    $columns[] = "fourps_member";
                    $placeholders[] = "?";
                    $values[] = $fourps_member;
                }

                $insertQuery = "INSERT INTO sitio1_patients (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

                // Insert into main patients table
                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute($values);
                $patientId = $pdo->lastInsertId();

                // Insert into medical info table
                $stmt = $pdo->prepare("INSERT INTO existing_info_patients 
                (patient_id, gender, height, weight, temperature, blood_pressure, 
                blood_type, allergies, medical_history, current_medications, 
                family_history, immunization_record, chronic_conditions) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $patientId,
                    $gender,
                    $height,
                    $weight,
                    $temperature,
                    $blood_pressure,
                    $bloodType,
                    $allergies,
                    $medicalHistory,
                    $currentMedications,
                    $familyHistory,
                    $immunizationRecord,
                    $chronicConditions
                ]);

                $pdo->commit();

                // Log staff activity for adding patient
                try {
                    $staff_id = $_SESSION['user']['id'] ?? null;
                    $staff_name = $_SESSION['user']['full_name'] ?? 'Unknown';
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

                    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    staff_id INT,
                    action_type VARCHAR(100),
                    related_id INT,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'add_patient', ?, ?, ?, ?, NOW())");
                    $stmtLog->execute([$staff_id, $patientId, json_encode(['full_name' => $staff_name, 'patient_name' => $fullName, 'patient_id' => $patientId]), $ip, $ua]);
                } catch (Exception $e) {
                    error_log('Staff activity log error (add_patient): ' . $e->getMessage());
                }

                $_SESSION['success_message'] = 'Patient record added successfully!';
                header('Location: existing_info_patients.php?tab=patients-tab');
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error adding patient record: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Full name and date of birth are required.';
    }
}

// Handle adding consultation note with doctor name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consultation_note'])) {
    $patient_id = $_POST['patient_id'];
    $note = trim($_POST['note']);
    $consultation_date = $_POST['consultation_date'];
    $next_consultation_date = !empty($_POST['next_consultation_date']) ? $_POST['next_consultation_date'] : null;

    // Get doctor name with automatic "Dr." prefix
    $doctor_name = trim($_POST['doctor_name']);
    $full_doctor_name = 'Dr. ' . $doctor_name;

    if (!empty($patient_id) && !empty($note) && !empty($consultation_date) && !empty($doctor_name)) {
        try {
            // Verify patient belongs to current staff member or sharing is enabled
            require_once __DIR__ . '/../includes/functions.php';
            if (staff_can_view_all()) {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ?");
                $stmt->execute([$patient_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND added_by = ?");
                $stmt->execute([$patient_id, $_SESSION['user']['id']]);
            }

            if (!$stmt->fetch()) {
                $error = "Patient not found or access denied!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO consultation_notes 
                    (patient_id, note, doctor_name, consultation_date, next_consultation_date, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$patient_id, $note, $full_doctor_name, $consultation_date, $next_consultation_date, $_SESSION['user']['id']]);
                $message = "Consultation note added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error adding consultation note: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields for consultation note.";
    }
}

// Handle PDF Export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_pdf'])) {
    $selectedPatients = isset($_POST['selected_patients']) ? $_POST['selected_patients'] : [];

    if (empty($selectedPatients)) {
        $error = 'Please select at least one patient to export.';
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedPatients), '?'));
            $query = "SELECT 
                p.*,
                e.*,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                    ELSE 'Regular Patient'
                END as patient_type,
                u.email as user_email,
                u.unique_number
            FROM sitio1_patients p
            LEFT JOIN existing_info_patients e ON p.id = e.patient_id
            LEFT JOIN sitio1_users u ON p.user_id = u.id
            WHERE p.id IN ($placeholders) AND p.deleted_at IS NULL
            ORDER BY p.full_name ASC";

            // Respect shared-mode: when enabled, do not restrict by added_by
            $params = $selectedPatients;
            if (!staff_can_view_all()) {
                $query = "SELECT 
                p.*,
                e.*,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                    ELSE 'Regular Patient'
                END as patient_type,
                u.email as user_email,
                u.unique_number
            FROM sitio1_patients p
            LEFT JOIN existing_info_patients e ON p.id = e.patient_id
            LEFT JOIN sitio1_users u ON p.user_id = u.id
            WHERE p.id IN ($placeholders) AND p.added_by = ? AND p.deleted_at IS NULL
            ORDER BY p.full_name ASC";
                $params = array_merge($selectedPatients, [$_SESSION['user']['id']]);
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($patients)) {
                $error = "No patients found for export.";
            } else {
                $_SESSION['pdf_export_data'] = $patients;
                header('Location: generate_pdf.php');
                exit();
            }
        } catch (Exception $e) {
            $error = "Error exporting selected patients: " . $e->getMessage();
        }
    }
}

// Handle manual export POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_manual'])) {
    $selectedPatients = isset($_POST['selected_patients']) ? $_POST['selected_patients'] : [];

    if (empty($selectedPatients)) {
        $error = 'Please select at least one patient to export.';
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedPatients), '?'));
            $query = "SELECT 
                p.*,
                e.*,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                    ELSE 'Regular Patient'
                END as patient_type,
                u.email as user_email,
                u.unique_number
            FROM sitio1_patients p
            LEFT JOIN existing_info_patients e ON p.id = e.patient_id
            LEFT JOIN sitio1_users u ON p.user_id = u.id
            WHERE p.id IN ($placeholders) AND p.deleted_at IS NULL
            ORDER BY p.full_name ASC";

            $params = $selectedPatients;
            if (!staff_can_view_all()) {
                $query = "SELECT 
                p.*,
                e.*,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                    ELSE 'Regular Patient'
                END as patient_type,
                u.email as user_email,
                u.unique_number
            FROM sitio1_patients p
            LEFT JOIN existing_info_patients e ON p.id = e.patient_id
            LEFT JOIN sitio1_users u ON p.user_id = u.id
            WHERE p.id IN ($placeholders) AND p.added_by = ? AND p.deleted_at IS NULL
            ORDER BY p.full_name ASC";
                $params = array_merge($selectedPatients, [$_SESSION['user']['id']]);
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Set filename
            $filename = 'Barangay_Luz_Manual_Export_' . date('Y-m-d_His') . '.xls';

            // Clean output
            ob_clean();

            // Output professional Excel format
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Patient Records</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo '
                body { font-family: "Calibri", "Arial", sans-serif; font-size: 11pt; }
                table { border-collapse: collapse; width: 100%; table-layout: fixed; }
                th { 
                    background-color: #4472C4; 
                    color: white; 
                    border: 0.5pt solid #000000; 
                    padding: 8px; 
                    text-align: center; 
                    vertical-align: middle;
                    font-weight: bold;
                }
                td { 
                    border: 0.5pt solid #000000; 
                    padding: 5px 8px; 
                    vertical-align: middle;
                    color: #000000;
                }
                .header-row { height: 30pt; }
                .title { 
                    font-size: 18pt; 
                    font-weight: bold; 
                    color: #1F4E78; 
                    text-align: center; 
                    border: none;
                }
                .subtitle { 
                    font-size: 14pt; 
                    color: #1F4E78; 
                    text-align: center; 
                    border: none;
                }
                .meta-label { 
                    font-weight: bold; 
                    background-color: #D9E1F2; 
                    color: #000;
                    text-align: right;
                }
                .meta-value {
                    background-color: #FFFFFF;
                    text-align: left;
                }
                .section-header { 
                    background-color: #A9D08E; 
                    color: #006100; 
                    font-weight: bold; 
                    text-align: left; 
                    padding-left: 10px;
                    font-size: 12pt;
                }
                .success-msg {
                    color: #006100;
                    background-color: #C6EFCE;
                    font-weight: bold;
                    text-align: center;
                    border: 1px solid #006100;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }
                .alt-row { background-color: #F2F2F2; }
                /* Type formats */
                .fmt-text { mso-number-format:"\@"; }
                .fmt-date { mso-number-format:"Short Date"; }
                .fmt-num { mso-number-format:"0"; }
                .fmt-dec { mso-number-format:"0.0"; }
            ';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            // Header Section
            echo '<table>';
            echo '<tr><td colspan="15" class="title" style="border:none;">BARANGAY LUZ HEALTH CENTER</td></tr>';
            echo '<tr><td colspan="15" class="subtitle" style="border:none;">Patient Records Export - Manual Selection</td></tr>';
            echo '<tr><td colspan="15" style="border:none;">&nbsp;</td></tr>';

            // Meta Info
            echo '<tr>';
            echo '<td colspan="2" class="meta-label">Export Date:</td>';
            echo '<td colspan="3" class="meta-value class="fmt-date">' . date('Y-m-d') . '</td>';
            echo '<td colspan="2" class="meta-label">Time:</td>';
            echo '<td colspan="3" class="meta-value">' . date('h:i A') . '</td>';
            echo '<td colspan="2" class="meta-label">Generated By:</td>';
            echo '<td colspan="3" class="meta-value">' . htmlspecialchars($_SESSION['user']['full_name']) . '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td colspan="2" class="meta-label">Total Records:</td>';
            echo '<td colspan="3" class="meta-value">' . count($patients) . '</td>';
            echo '<td colspan="10" class="success-msg">Manual Selection Export Successful</td>';
            echo '</tr>';
            echo '<tr><td colspan="15" style="border:none;">&nbsp;</td></tr>';
            echo '</table>';

            // Main Data Table
            echo '<table>';
            echo '<thead>';
            echo '<tr style="height: 25pt;">';
            echo '<th style="width: 50px;">No.</th>';
            echo '<th style="width: 80px;">ID</th>';
            echo '<th style="width: 200px;">Full Name</th>';
            echo '<th style="width: 100px;">Birth Date</th>';
            echo '<th style="width: 60px;">Age</th>';
            echo '<th style="width: 80px;">Gender</th>';
            echo '<th style="width: 120px;">Sitio</th>';
            echo '<th style="width: 100px;">Civil Status</th>';
            echo '<th style="width: 120px;">Occupation</th>';
            echo '<th style="width: 120px;">Contact</th>';
            echo '<th style="width: 80px;">Blood Type</th>';
            echo '<th style="width: 80px;">Height (cm)</th>';
            echo '<th style="width: 80px;">Weight (kg)</th>';
            echo '<th style="width: 80px;">BMI</th>';
            echo '<th style="width: 120px;">Last Checkup</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $counter = 1;
            foreach ($patients as $patient) {
                $rowStyle = ($counter % 2 == 0) ? ' class="alt-row"' : '';

                // BMI logic
                $height = floatval($patient['height'] ?? 0);
                $weight = floatval($patient['weight'] ?? 0);
                $bmi = ($height > 0) ? number_format($weight / (($height / 100) ** 2), 1) : '';

                // Date logic
                $dob = !empty($patient['date_of_birth']) ? date('Y-m-d', strtotime($patient['date_of_birth'])) : '';
                $lastCheckup = !empty($patient['last_checkup']) ? date('Y-m-d', strtotime($patient['last_checkup'])) : '';

                echo "<tr{$rowStyle}>";
                echo '<td class="text-center">' . $counter++ . '</td>';
                echo '<td class="fmt-text text-center">' . ($patient['id'] ?? '') . '</td>';
                echo '<td class="text-bold">' . htmlspecialchars($patient['full_name'] ?? '') . '</td>';
                echo '<td class="fmt-date text-center">' . $dob . '</td>';
                echo '<td class="text-center">' . ($patient['age'] ?? '') . '</td>';
                echo '<td class="text-center">' . htmlspecialchars($patient['gender'] ?? '') . '</td>';
                echo '<td class="text-center">' . htmlspecialchars($patient['sitio'] ?? '') . '</td>';
                echo '<td class="text-center">' . htmlspecialchars($patient['civil_status'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($patient['occupation'] ?? '') . '</td>';
                echo '<td class="fmt-text text-center">' . htmlspecialchars($patient['contact'] ?? '') . '</td>';
                echo '<td class="text-center text-bold">' . htmlspecialchars($patient['blood_type'] ?? '') . '</td>';
                echo '<td class="fmt-dec text-center">' . ($height ?: '') . '</td>';
                echo '<td class="fmt-dec text-center">' . ($weight ?: '') . '</td>';

                // BMI Color coding
                $bmiStyle = '';
                if ($bmi !== '') {
                    if ($bmi < 18.5)
                        $bmiStyle = 'color: #0070C0; font-weight:bold;';
                    elseif ($bmi >= 25)
                        $bmiStyle = 'color: #C00000; font-weight:bold;';
                    else
                        $bmiStyle = 'color: #006100; font-weight:bold;';
                }
                echo '<td class="fmt-dec text-center" style="' . $bmiStyle . '">' . $bmi . '</td>';
                echo '<td class="fmt-date text-center">' . $lastCheckup . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';

            // Footer
            echo '<br/><br/>';
            echo '<table style="border:none;">';
            echo '<tr>';
            echo '<td colspan="15" style="border:none; color: #767676; font-size: 9pt; text-align: center;">';
            echo '*** END OF REPORT ***<br/>';
            echo 'CONFIDENTIAL: This document contains detailed medical information including BMI and health records.<br/>';
            echo 'Generated by Community Health Tracker System';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '</body></html>';
            exit();
        } catch (Exception $e) {
            $error = "Error exporting selected patients: " . $e->getMessage();
        }
    }
}

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    try {
        $patientType = isset($_GET['patient_type']) ? $_GET['patient_type'] : 'all';
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $searchBy = isset($_GET['search_by']) ? trim($_GET['search_by']) : 'name';

        $query = "SELECT 
            p.*,
            e.*,
            CASE 
                WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                ELSE 'Regular Patient'
            END as patient_type,
            u.email as user_email,
            u.unique_number
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.deleted_at IS NULL";

        // If staff are configured to view all records, do not restrict by added_by
        $params = [];
        if (!staff_can_view_all()) {
            $query .= " AND p.added_by = ?";
            $params[] = $_SESSION['user']['id'];
        }

        if (!empty($searchTerm)) {
            if ($searchBy === 'unique_number') {
                $query .= " AND EXISTS (
                    SELECT 1 FROM sitio1_users u 
                    WHERE u.id = p.user_id AND u.unique_number LIKE ?
                )";
                $params[] = "%$searchTerm%";
            } else {
                $query .= " AND p.full_name LIKE ?";
                $params[] = "%$searchTerm%";
            }
        }

        // Add patient type filter
        if ($patientType == 'registered') {
            $query .= " AND p.user_id IS NOT NULL";
        } elseif ($patientType == 'regular') {
            $query .= " AND p.user_id IS NULL";
        }

        $query .= " ORDER BY p.full_name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set filename
        $filename = 'Barangay_Luz_Health_Center_Patient_Records_' . date('Y-m-d');
        if ($patientType == 'registered') {
            $filename = 'Registered_Patients_Export_' . date('Y-m-d');
        } elseif ($patientType == 'regular') {
            $filename = 'Regular_Patients_Export_' . date('Y-m-d');
        }
        if (!empty($searchTerm)) {
            $filename .= '_search_' . substr($searchTerm, 0, 20);
        }
        $filename .= '.xls';

        // Clean output
        ob_clean();

        // Output Excel content
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        // CSS Block: Export Table Styles
        // CSS Block: Patient Table Styles
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Calibri, Arial, sans-serif; }';
        echo 'th { background-color: #3498db; color: white; font-weight: bold; padding: 12px; text-align: left; border: 1px solid #ddd; }';
        echo 'td { padding: 10px; border: 1px solid #ddd; vertical-align: top; }';
        echo '.header-row { background-color: #2c3e50; color: white; font-size: 14pt; font-weight: bold; }';
        echo '.section-header { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; font-size: 12pt; }';
        echo '.info-row { background-color: #f0f9ff; }';
        echo '.summary-row { background-color: #e8f5e8; font-weight: bold; }';
        echo '.date-cell { mso-number-format:"Short Date"; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        echo '<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        margin: 20px;
        background-color: #ffffff;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .header-row {
        background-color: #1a5f7a;
        color: white;
        font-size: 16pt;
        font-weight: bold;
    }
    
    .section-header {
        background-color: #2d8c9e;
        color: white;
        font-weight: bold;
        font-size: 12pt;
    }
    
    .info-row {
        background-color: #f8f9fa;
        font-size: 10pt;
    }
    
    th {
        background-color: #e3f2fd;
        color: #1a237e;
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
        font-weight: bold;
        font-size: 10pt;
    }
    
    td {
        padding: 8px;
        border: 1px solid #ddd;
        font-size: 10pt;
        vertical-align: top;
    }
    
    .summary-row {
        background-color: #e8f5e9;
        font-weight: bold;
        color: #1b5e20;
    }
    
    .date-cell {
        white-space: nowrap;
    }
    
    .highlight {
        background-color: #fff3e0;
    }
    
    .footer {
        font-size: 8pt;
        color: #666;
        text-align: center;
        margin-top: 30px;
        padding-top: 10px;
        border-top: 1px solid #ccc;
    }
    
    .medical-section {
        page-break-before: always;
        margin-top: 30px;
    }
    
    .center-header {
        text-align: center;
        font-size: 18pt;
        color: #1a5f7a;
        margin-bottom: 20px;
    }
    
    .label {
        font-weight: bold;
        color: #333;
    }
</style>';

        echo '<html><body>';

        // Main header
        echo '<div class="center-header">BARANGAY LUZ HEALTH CENTER<br>';
        echo '<span style="font-size: 14pt;">Patient Records Export</span></div>';

        // Export Information Table
        echo '<table>';
        echo '<tr class="section-header">';
        echo '<td colspan="12">EXPORT INFORMATION</td>';
        echo '</tr>';

        echo '<tr class="info-row">';
        echo '<td colspan="3" class="label">Generated On:</td>';
        echo '<td colspan="3">' . date('F j, Y h:i A') . '</td>';
        echo '<td colspan="3" class="label">Generated By:</td>';
        echo '<td colspan="3">' . htmlspecialchars($_SESSION['user']['full_name'] ?? 'System') . '</td>';
        echo '</tr>';

        echo '<tr class="info-row">';
        echo '<td colspan="3" class="label">Total Records:</td>';
        echo '<td colspan="3">' . number_format(count($patients)) . '</td>';
        echo '<td colspan="3" class="label">Export Type:</td>';
        echo '<td colspan="3">' . ucfirst($patientType) . ' Patients</td>';
        echo '</tr>';
        echo '</table>';

        // Patient Records Table
        echo '<table>';
        echo '<tr class="section-header">';
        echo '<td colspan="12">PATIENT BASIC INFORMATION</td>';
        echo '</tr>';

        // Column headers
        echo '<tr>';
        echo '<th style="width: 3%;">No.</th>';
        echo '<th style="width: 8%;">Patient ID</th>';
        echo '<th style="width: 15%;">Full Name</th>';
        echo '<th style="width: 8%;">Date of Birth</th>';
        echo '<th style="width: 5%;">Age</th>';
        echo '<th style="width: 7%;">Gender</th>';
        echo '<th style="width: 10%;">Contact</th>';
        echo '<th style="width: 15%;">Address</th>';
        echo '<th style="width: 10%;">Sitio</th>';
        echo '<th style="width: 8%;">Blood Type</th>';
        echo '<th style="width: 10%;">Last Checkup</th>';
        echo '<th style="width: 11%;">Patient Type</th>';
        echo '</tr>';

        // Data rows
        $counter = 1;
        foreach ($patients as $patient) {
            // Alternate row coloring for better readability
            $rowClass = ($counter % 2 == 0) ? 'style="background-color: #f9f9f9;"' : '';

            echo '<tr ' . $rowClass . '>';
            echo '<td>' . $counter++ . '</td>';
            echo '<td style="font-family: Consolas, monospace;">' . ($patient['id'] ?? 'N/A') . '</td>';
            echo '<td><strong>' . htmlspecialchars($patient['full_name'] ?? '') . '</strong></td>';
            echo '<td class="date-cell">' . (!empty($patient['date_of_birth']) ? date('M d, Y', strtotime($patient['date_of_birth'])) : '') . '</td>';
            echo '<td style="text-align: center;">' . ($patient['age'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($patient['gender'] ?? '') . '</td>';
            echo '<td>' . (!empty($patient['contact']) ? htmlspecialchars($patient['contact']) : 'N/A') . '</td>';
            echo '<td>' . (!empty($patient['address']) ? htmlspecialchars($patient['address']) : 'N/A') . '</td>';
            echo '<td>' . (!empty($patient['sitio']) ? htmlspecialchars($patient['sitio']) : 'N/A') . '</td>';

            // Highlight blood type with color coding
            $bloodType = htmlspecialchars($patient['blood_type'] ?? 'N/A');
            $bloodTypeClass = ($bloodType != 'N/A') ? 'style="font-weight: bold; color: #d32f2f; text-align: center;"' : 'style="text-align: center;"';
            echo '<td ' . $bloodTypeClass . '>' . $bloodType . '</td>';

            echo '<td class="date-cell">' . (!empty($patient['last_checkup']) ? date('M d, Y', strtotime($patient['last_checkup'])) : 'N/A') . '</td>';
            echo '<td style="text-align: center;">' . (!empty($patient['patient_type']) ? htmlspecialchars($patient['patient_type']) : 'Regular') . '</td>';
            echo '</tr>';
        }

        // Summary row
        echo '<tr class="summary-row">';
        echo '<td colspan="12" style="text-align: center; padding: 15px;">';
        echo 'TOTAL PATIENTS: <strong>' . number_format(count($patients)) . '</strong>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';

        // Medical Information Table
        echo '<div class="medical-section">';
        echo '<table>';
        echo '<tr class="section-header">';
        echo '<td colspan="8">DETAILED MEDICAL INFORMATION</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th style="width: 20%;">Patient Name</th>';
        echo '<th style="width: 10%; text-align: center;">Height (cm)</th>';
        echo '<th style="width: 10%; text-align: center;">Weight (kg)</th>';
        echo '<th style="width: 10%; text-align: center;">BMI</th>';
        echo '<th style="width: 15%; text-align: center;">Blood Pressure</th>';
        echo '<th style="width: 10%; text-align: center;">Temperature</th>';
        echo '<th style="width: 15%;">Allergies</th>';
        echo '<th style="width: 20%;">Chronic Conditions</th>';
        echo '</tr>';

        foreach ($patients as $patient) {
            // Calculate BMI with proper formatting
            $height = $patient['height'] ?? 0;
            $weight = $patient['weight'] ?? 0;

            if ($height > 0 && $weight > 0) {
                $bmiValue = $weight / (($height / 100) * ($height / 100));
                $bmi = number_format($bmiValue, 1);

                // Color code BMI based on WHO standards
                if ($bmiValue < 18.5) {
                    $bmiStyle = 'style="color: #2196f3; font-weight: bold;"';
                } elseif ($bmiValue >= 18.5 && $bmiValue < 25) {
                    $bmiStyle = 'style="color: #4caf50; font-weight: bold;"';
                } elseif ($bmiValue >= 25 && $bmiValue < 30) {
                    $bmiStyle = 'style="color: #ff9800; font-weight: bold;"';
                } else {
                    $bmiStyle = 'style="color: #f44336; font-weight: bold;"';
                }
            } else {
                $bmi = 'N/A';
                $bmiStyle = '';
            }

            // Alternate row coloring
            static $medCounter = 0;
            $rowClass = (++$medCounter % 2 == 0) ? 'style="background-color: #f9f9f9;"' : '';

            echo '<tr ' . $rowClass . '>';
            echo '<td><strong>' . htmlspecialchars($patient['full_name'] ?? '') . '</strong></td>';
            echo '<td style="text-align: center;">' . ($height ? number_format($height, 1) : 'N/A') . '</td>';
            echo '<td style="text-align: center;">' . ($weight ? number_format($weight, 1) : 'N/A') . '</td>';
            echo '<td style="text-align: center;" ' . $bmiStyle . '>' . $bmi . '</td>';

            // Highlight abnormal blood pressure
            $bp = htmlspecialchars($patient['blood_pressure'] ?? 'N/A');
            if ($bp != 'N/A' && preg_match('/(\d+)\s*\/\s*(\d+)/', $bp, $matches)) {
                $systolic = intval($matches[1]);
                $diastolic = intval($matches[2]);
                if ($systolic > 140 || $diastolic > 90) {
                    $bpStyle = 'style="color: #f44336; font-weight: bold;"';
                } else {
                    $bpStyle = 'style="color: #4caf50;"';
                }
            } else {
                $bpStyle = '';
            }

            echo '<td style="text-align: center;" ' . $bpStyle . '>' . $bp . '</td>';
            echo '<td style="text-align: center;">' . (!empty($patient['temperature']) ? number_format($patient['temperature'], 1) . '°C' : 'N/A') . '</td>';

            // Truncate long text but show full text on hover
            $allergies = !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : 'None';
            $allergiesDisplay = (strlen($allergies) > 30) ? substr($allergies, 0, 30) . '...' : $allergies;

            $conditions = !empty($patient['chronic_conditions']) ? htmlspecialchars($patient['chronic_conditions']) : 'None';
            $conditionsDisplay = (strlen($conditions) > 30) ? substr($conditions, 0, 30) . '...' : $conditions;

            echo '<td title="' . htmlspecialchars($allergies) . '">' . $allergiesDisplay . '</td>';
            echo '<td title="' . htmlspecialchars($conditions) . '">' . $conditionsDisplay . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';

        // Footer
        echo '<div class="footer">';
        echo '<strong>CONFIDENTIALITY NOTICE:</strong> This document contains protected health information (PHI).<br>';
        echo 'Unauthorized access, disclosure, or distribution is prohibited under R.A. 10173 (Data Privacy Act).<br>';
        echo 'Report Date: ' . date('F j, Y') . ' | Total Records: ' . number_format(count($patients)) . ' | Generated by: ' . htmlspecialchars($_SESSION['user']['full_name'] ?? 'System') . '<br>';
        echo '© ' . date('Y') . ' Barangay Luz Health Center. For official use only.';
        echo '</div>';

        echo '</body></html>';
        exit();
    } catch (Exception $e) {
        $error = "Error exporting to Excel: " . $e->getMessage();
        error_log("Excel Export Error: " . $e->getMessage());
    }
}

// Check for success message from session
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle patient deletion
if (isset($_GET['delete_patient'])) {
    $patientId = $_GET['delete_patient'];
    try {
        require_once __DIR__ . '/../includes/functions.php';
        $pdo->beginTransaction();
        if (staff_can_view_all()) {
            $stmt = $pdo->prepare("SELECT * FROM sitio1_patients WHERE id = ?");
            $stmt->execute([$patientId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM sitio1_patients WHERE id = ? AND added_by = ?");
            $stmt->execute([$patientId, $_SESSION['user']['id']]);
        }
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            // Get medical info before archiving
            $stmt = $pdo->prepare("SELECT * FROM existing_info_patients WHERE patient_id = ?");
            $stmt->execute([$patientId]);
            $medicalInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get column information from deleted_patients table
            $stmt = $pdo->prepare("SHOW COLUMNS FROM deleted_patients");
            $stmt->execute();
            $deletedTableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Filter columns that exist in both source and destination
            $columns = [];
            $placeholders = [];
            $values = [];

            foreach ($patient as $column => $value) {
                if ($column === 'id') {
                    $columns[] = 'original_id';
                    $placeholders[] = '?';
                    $values[] = $value;
                    continue;
                }

                if (in_array($column, $deletedTableColumns) && !in_array($column, ['id', 'deleted_at'])) {
                    $columns[] = $column;
                    $placeholders[] = '?';
                    $values[] = $value;
                }
            }

            // Add medical info to archive if exists
            if ($medicalInfo) {
                $medicalFields = ['gender', 'height', 'weight', 'temperature', 'blood_pressure', 'blood_type', 'allergies', 'medical_history', 'current_medications', 'family_history', 'immunization_record', 'chronic_conditions'];
                foreach ($medicalFields as $field) {
                    if (in_array($field, $deletedTableColumns) && !in_array($field, $columns)) {
                        $columns[] = $field;
                        $placeholders[] = '?';
                        $values[] = $medicalInfo[$field] ?? null;
                    }
                }
            }

            // Add deleted_by column
            $columns[] = 'deleted_by';
            $placeholders[] = '?';
            $values[] = $_SESSION['user']['id'];

            $insertQuery = "INSERT INTO deleted_patients (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

            // Insert into deleted_patients table
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute($values);

            // Delete from main table
            $stmt = $pdo->prepare("DELETE FROM sitio1_patients WHERE id = ?");
            $stmt->execute([$patientId]);

            // Delete health info
            $stmt = $pdo->prepare("DELETE FROM existing_info_patients WHERE patient_id = ?");
            $stmt->execute([$patientId]);

            $pdo->commit();

            // Log staff activity for archiving patient
            try {
                $staff_id = $_SESSION['user']['id'] ?? null;
                $staff_name = $_SESSION['user']['full_name'] ?? 'Unknown';
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

                $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'archive_patient', ?, ?, ?, ?, NOW())");
                $stmtLog->execute([$staff_id, $patientId, json_encode(['full_name' => $staff_name, 'patient_name' => $patient['full_name'], 'original_id' => $patientId]), $ip, $ua]);
            } catch (Exception $e) {
                error_log('Staff activity log error (archive_patient): ' . $e->getMessage());
            }

            $_SESSION['success_message'] = 'Patient record moved to archive successfully!';
            header('Location: existing_info_patients.php');
            exit();
        } else {
            $error = 'Patient not found!';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error deleting patient record: ' . $e->getMessage();
    }
}

// Get search term if exists
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchBy = isset($_GET['search_by']) ? trim($_GET['search_by']) : 'name';

// Get patient type filter
$patientTypeFilter = isset($_GET['patient_type']) ? strtolower(trim($_GET['patient_type'])) : 'all';
if (!in_array($patientTypeFilter, ['all', 'account_access', 'regular_patient'])) {
    $patientTypeFilter = 'all';
}

// Check if manual selection mode is active
$manualSelectMode = isset($_GET['manual_select']) && $_GET['manual_select'] == 'true';

// Check if we're viewing all records
$viewAll = isset($_GET['view_all']) && $_GET['view_all'] == 'true';

// Pagination setup
$recordsPerPage = 5;

// Get current page from URL, default to 1
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Get total count of patients based on filter
try {
    $countQuery = "SELECT COUNT(*) as total FROM sitio1_patients p WHERE p.deleted_at IS NULL";
    $countParams = [];

    // Apply patient type filter to count query
    if ($patientTypeFilter === 'account_access') {
        $countQuery .= " AND p.user_id IS NOT NULL";
    } elseif ($patientTypeFilter === 'regular_patient') {
        $countQuery .= " AND p.user_id IS NULL";
    }

    // Apply staff restriction if not viewing all records
    if (!staff_can_view_all()) {
        $countQuery .= " AND p.added_by = ?";
        $countParams[] = $_SESSION['user']['id'];
    }

    // Apply date filter to count query if provided


    $stmt = $pdo->prepare($countQuery);

    // Bind parameters for count query
    $paramIndex = 1;
    foreach ($countParams as $param) {
        if (is_int($param) || ctype_digit((string) $param)) {
            $stmt->bindValue($paramIndex, (int) $param, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
        }
        $paramIndex++;
    }

    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    // Fix: Avoid division by zero and undefined variable
    if (!isset($recordsPerPage) || !$recordsPerPage) {
        $recordsPerPage = 1; // fallback to 1 to avoid division by zero
    }
    $totalPages = max(1, ceil($totalRecords / $recordsPerPage));
    // Ensure current page is within valid range
    if (isset($currentPage) && $currentPage > $totalPages) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $recordsPerPage;
    }
} catch (PDOException $e) {
    $error = "Error counting patient records: " . $e->getMessage();
    $totalRecords = 0;
    $totalPages = 1;
}

// Get all patients with their medical info
$allPatients = []; // initialize so it's always defined
try {
    $selectQuery = "SELECT 
            p.id,
            p.full_name,
            COALESCE(p.date_of_birth, u.date_of_birth) as date_of_birth,
            p.age,
            COALESCE(e.gender, p.gender) as gender,
            p.sitio,
            p.civil_status,
            p.occupation,
            p.phic_no,
            p.bhw_assigned,
            p.family_no,
            p.fourps_member,
            p.user_id,
            p.created_at,
            e.blood_type,
            e.height, 
            e.weight, 
            e.temperature, 
            e.blood_pressure,
            e.allergies, 
            e.immunization_record, 
            e.chronic_conditions,
            e.medical_history, 
            e.current_medications, 
            e.family_history,
            u.unique_number,
            u.email as user_email,
            u.sitio as user_sitio,
            u.civil_status as user_civil_status,
            u.occupation as user_occupation,
            u.gender as user_gender,
            u.date_of_birth as user_date_of_birth,
            CASE 
                WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                ELSE 'Regular Patient'
            END as patient_type
        FROM sitio1_patients p
        LEFT JOIN existing_info_patients e ON p.id = e.patient_id
        LEFT JOIN sitio1_users u ON p.user_id = u.id
        WHERE p.deleted_at IS NULL";

    // Build params based on shared-mode and filters
    $selectParams = [];

    // Apply patient type filter
    if ($patientTypeFilter === 'account_access') {
        $selectQuery .= " AND p.user_id IS NOT NULL";
    } elseif ($patientTypeFilter === 'regular_patient') {
        $selectQuery .= " AND p.user_id IS NULL";
    }

    // Apply staff restriction if not viewing all records
    if (!staff_can_view_all()) {
        $selectQuery .= " AND p.added_by = ?";
        $selectParams[] = $_SESSION['user']['id'];
    }

    // Filter by specific date if provided


    // Determine sort order from filter
    $dateSortOrder = (isset($_GET['date_sort']) && strtolower($_GET['date_sort']) === 'asc') ? 'ASC' : 'DESC';
    $selectQuery .= " ORDER BY p.created_at $dateSortOrder";

    // Apply pagination limits only if not in view all or manual select mode
    if ($viewAll) {
        $limitNeeded = false;
    } else {
        $limitNeeded = true;
        $selectQuery .= " LIMIT ? OFFSET ?";
    }

    // DEBUG: Output the query and params for troubleshooting
    if (isset($_GET['debug_filter'])) {
        echo '<pre style="background:#fff;color:#000;z-index:9999;position:relative;margin:20px;padding:15px;border:2px solid red;">';
        echo "<b>SQL Query:</b>\n" . htmlspecialchars($selectQuery) . "\n\n";
        echo "<b>Params:</b>\n" . print_r($selectParams, true) . "\n\n";
        echo "<b>Patient Type Filter:</b> " . htmlspecialchars($patientTypeFilter) . "\n";
        echo "<b>Date Sort:</b> " . htmlspecialchars($dateSortOrder) . "\n";
        echo "<b>Filter Date:</b> " . (isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : 'none') . "\n";
        echo "<b>View All:</b> " . ($viewAll ? 'true' : 'false') . "\n";
        echo "<b>Manual Select Mode:</b> " . ($manualSelectMode ? 'true' : 'false') . "\n";
        echo "<b>Limit Needed:</b> " . ($limitNeeded ? 'true' : 'false') . "\n";
        echo "<b>Records Per Page:</b> " . $recordsPerPage . "\n";
        echo "<b>Offset:</b> " . $offset . "\n";
        echo '</pre>';
    }

    $stmt = $pdo->prepare($selectQuery);

    // Bind parameters
    $paramIndex = 1;
    foreach ($selectParams as $param) {
        if (is_int($param) || ctype_digit((string) $param)) {
            $stmt->bindValue($paramIndex, (int) $param, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
        }
        $paramIndex++;
    }

    // Bind LIMIT and OFFSET as integers
    if ($limitNeeded) {
        $stmt->bindValue($paramIndex, (int) $recordsPerPage, PDO::PARAM_INT);
        $paramIndex++;
        $stmt->bindValue($paramIndex, (int) $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    $allPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug output for results count
    if (isset($_GET['debug_filter'])) {
        echo "<b>Total Records Found:</b> " . count($allPatients) . "\n";
        echo "<b>First Record Sample:</b>\n";
        if (!empty($allPatients)) {
            echo print_r($allPatients[0], true);
        } else {
            echo "No records found";
        }
        echo '</pre>';
    }
} catch (PDOException $e) {
    $error = "Error fetching patient records: " . $e->getMessage();
    error_log("Patient fetch error: " . $e->getMessage());
}

// Get list of patients matching search
$patients = [];
$searchedUsers = [];
if (!empty($searchTerm)) {
    try {
        if ($searchBy === 'unique_number') {
            $query = "SELECT u.id, u.full_name, u.email, u.date_of_birth, u.age, u.gender,
                             u.civil_status, u.occupation, u.address, u.sitio, u.contact, 
                             u.unique_number, 'user' as type
                      FROM sitio1_users u 
                      WHERE u.approved = 1 AND u.unique_number LIKE ? 
                      ORDER BY u.full_name LIMIT 10";

            $stmt = $pdo->prepare($query);
            $stmt->execute(["%$searchTerm%"]);
            $searchedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $selectQuery = "SELECT p.id, p.full_name, p.date_of_birth, p.age, 
                            COALESCE(e.gender, p.gender) as gender, p.sitio, p.civil_status, p.occupation,
                            p.phic_no, p.bhw_assigned, p.family_no, p.fourps_member,
                            e.blood_type, e.height, e.weight, e.temperature, e.blood_pressure,
                            CASE WHEN p.user_id IS NOT NULL THEN 'Registered Patient' ELSE 'Regular Patient' END as patient_type
                        FROM sitio1_patients p 
                        LEFT JOIN existing_info_patients e ON p.id = e.patient_id 
                        WHERE p.deleted_at IS NULL AND p.full_name LIKE ?";

            $params = ["%$searchTerm%"];
            // Add patient type filter if set
            if (isset($patientTypeFilter) && $patientTypeFilter !== 'all') {
                if ($patientTypeFilter === 'registered') {
                    $selectQuery .= " AND p.user_id IS NOT NULL";
                } elseif ($patientTypeFilter === 'regular') {
                    $selectQuery .= " AND p.user_id IS NULL";
                }
            }

            if (!staff_can_view_all()) {
                $selectQuery .= " AND p.added_by = ?";
                $params[] = $_SESSION['user']['id'];
            }

            $selectQuery .= " ORDER BY p.full_name";

            $stmt = $pdo->prepare($selectQuery);
            $stmt->execute($params);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Error fetching patients: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Records - Barangay Luz Health Center</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/medical-records.css">
    <!-- Flatpickr - Professional Calendar Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/light.css">
    <link rel="stylesheet" href="/asssets/css/normalize.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .patient-checkbox {
            cursor: pointer;
        }

        /* #exportPagination {
            margin-top: 1rem;
            padding-top: 0.75rem;
        } */

        .date-input-with-trigger {
            position: relative;
        }

        .date-input-with-trigger input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            display: none;
            -webkit-appearance: none;
        }

        .date-input-with-trigger input[type="date"]::-webkit-inner-spin-button,
        .date-input-with-trigger input[type="date"]::-webkit-clear-button {
            display: none;
            -webkit-appearance: none;
        }

        .date-input-with-trigger input[type="date"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: textfield;
            padding-right: 3rem;
        }

        .date-input-trigger {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            color: #2563EB;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="w-full px-24 py-10 lg:px-8">

        <?php if ($message): ?>
            <div id="successMessage" class="alert-success px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Main Container - Single Tab Only -->
        <div class=" mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-medium text-gray-700 flex items-center text-secondary">Resident Patient
                    Records</h2>
            </div>
            <!-- Single Tab with Add Patient Button on the right -->
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <button
                        class="tab-btn items-center inline-flex py-3 px-6 font-medium text-md hover:text-primary hover:border-primary transition active"
                        data-tab="patients-tab">
                        <svg class="w-8 h-8 mr-2" viewBox="0 0 27 27" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M9.28125 6.75C9.28125 6.52622 9.37014 6.31161 9.52838 6.15338C9.68661 5.99515 9.90122 5.90625 10.125 5.90625H22.7812C23.005 5.90625 23.2196 5.99515 23.3779 6.15338C23.5361 6.31161 23.625 6.52622 23.625 6.75C23.625 6.97378 23.5361 7.18839 23.3779 7.34662C23.2196 7.50486 23.005 7.59375 22.7812 7.59375H10.125C9.90122 7.59375 9.68661 7.50486 9.52838 7.34662C9.37014 7.18839 9.28125 6.97378 9.28125 6.75ZM22.7812 12.6562H10.125C9.90122 12.6562 9.68661 12.7451 9.52838 12.9034C9.37014 13.0616 9.28125 13.2762 9.28125 13.5C9.28125 13.7238 9.37014 13.9384 9.52838 14.0966C9.68661 14.2549 9.90122 14.3438 10.125 14.3438H22.7812C23.005 14.3438 23.2196 14.2549 23.3779 14.0966C23.5361 13.9384 23.625 13.7238 23.625 13.5C23.625 13.2762 23.5361 13.0616 23.3779 12.9034C23.2196 12.7451 23.005 12.6562 22.7812 12.6562ZM22.7812 19.4062H10.125C9.90122 19.4062 9.68661 19.4951 9.52838 19.6534C9.37014 19.8116 9.28125 20.0262 9.28125 20.25C9.28125 20.4738 9.37014 20.6884 9.52838 20.8466C9.68661 21.0049 9.90122 21.0938 10.125 21.0938H22.7812C23.005 21.0938 23.2196 21.0049 23.3779 20.8466C23.5361 20.6884 23.625 20.4738 23.625 20.25C23.625 20.0262 23.5361 19.8116 23.3779 19.6534C23.2196 19.4951 23.005 19.4062 22.7812 19.4062ZM5.90625 5.90625H4.21875C3.99497 5.90625 3.78036 5.99515 3.62213 6.15338C3.4639 6.31161 3.375 6.52622 3.375 6.75C3.375 6.97378 3.4639 7.18839 3.62213 7.34662C3.78036 7.50486 3.99497 7.59375 4.21875 7.59375H5.90625C6.13003 7.59375 6.34464 7.50486 6.50287 7.34662C6.6611 7.18839 6.75 6.97378 6.75 6.75C6.75 6.52622 6.6611 6.31161 6.50287 6.15338C6.34464 5.99515 6.13003 5.90625 5.90625 5.90625ZM5.90625 12.6562H4.21875C3.99497 12.6562 3.78036 12.7451 3.62213 12.9034C3.4639 13.0616 3.375 13.2762 3.375 13.5C3.375 13.7238 3.4639 13.9384 3.62213 14.0966C3.78036 14.2549 3.99497 14.3438 4.21875 14.3438H5.90625C6.13003 14.3438 6.34464 14.2549 6.50287 14.0966C6.6611 13.9384 6.75 13.7238 6.75 13.5C6.75 13.2762 6.6611 13.0616 6.50287 12.9034C6.34464 12.7451 6.13003 12.6562 5.90625 12.6562ZM5.90625 19.4062H4.21875C3.99497 19.4062 3.78036 19.4951 3.62213 19.6534C3.4639 19.8116 3.375 20.0262 3.375 20.25C3.375 20.4738 3.4639 20.6884 3.62213 20.8466C3.78036 21.0049 3.99497 21.0938 4.21875 21.0938H5.90625C6.13003 21.0938 6.34464 21.0049 6.50287 20.8466C6.6611 20.6884 6.75 20.4738 6.75 20.25C6.75 20.0262 6.6611 19.8116 6.50287 19.6534C6.34464 19.4951 6.13003 19.4062 5.90625 19.4062Z"
                                fill="#3C96E1" />
                        </svg>
                        List of Patient Records
                    </button>
                    <button onclick="openAddPatientModal()" class="btn-add-patient py-3 px-6 inline-flex items-center">
                        <svg class="w-7 h-7 mr-2 font-bold" viewBox="0 0 27 27" stroke="currentColor" stroke-width="1"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M23.625 13.5C23.625 13.7238 23.5361 13.9384 23.3779 14.0966C23.2196 14.2549 23.005 14.3438 22.7812 14.3438H14.3438V22.7812C14.3438 23.005 14.2549 23.2196 14.0966 23.3779C13.9384 23.5361 13.7238 23.625 13.5 23.625C13.2762 23.625 13.0616 23.5361 12.9034 23.3779C12.7451 23.2196 12.6562 23.005 12.6562 22.7812V14.3438H4.21875C3.99497 14.3438 3.78036 14.2549 3.62213 14.0966C3.4639 13.9384 3.375 13.7238 3.375 13.5C3.375 13.2762 3.4639 13.0616 3.62213 12.9034C3.78036 12.7451 3.99497 12.6562 4.21875 12.6562H12.6562V4.21875C12.6562 3.99497 12.7451 3.78036 12.9034 3.62213C13.0616 3.4639 13.2762 3.375 13.5 3.375C13.7238 3.375 13.9384 3.4639 14.0966 3.62213C14.2549 3.78036 14.3438 3.99497 14.3438 4.21875V12.6562H22.7812C23.005 12.6562 23.2196 12.7451 23.3779 12.9034C23.5361 13.0616 23.625 13.2762 23.625 13.5Z"
                                fill="#FFFFFF" />
                        </svg>
                        Add New Records
                    </button>
                </div>
                <a href="deleted_patients.php"
                    class="btn-archive rounded-md text-lg inline-flex items-center px-6 py-3">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M21 4.5H3C2.60218 4.5 2.22064 4.65804 1.93934 4.93934C1.65804 5.22064 1.5 5.60218 1.5 6V8.25C1.5 8.64782 1.65804 9.02936 1.93934 9.31066C2.22064 9.59196 2.60218 9.75 3 9.75V18C3 18.3978 3.15804 18.7794 3.43934 19.0607C3.72064 19.342 4.10218 19.5 4.5 19.5H19.5C19.8978 19.5 20.2794 19.342 20.5607 19.0607C20.842 18.7794 21 18.3978 21 18V9.75C21.3978 9.75 21.7794 9.59196 22.0607 9.31066C22.342 9.02936 22.5 8.64782 22.5 8.25V6C22.5 5.60218 22.342 5.22064 22.0607 4.93934C21.7794 4.65804 21.3978 4.5 21 4.5ZM19.5 18H4.5V9.75H19.5V18ZM21 8.25H3V6H21V8.25ZM9 12.75C9 12.5511 9.07902 12.3603 9.21967 12.2197C9.36032 12.079 9.55109 12 9.75 12H14.25C14.4489 12 14.6397 12.079 14.7803 12.2197C14.921 12.3603 15 12.5511 15 12.75C15 12.9489 14.921 13.1397 14.7803 13.2803C14.6397 13.421 14.4489 13.5 14.25 13.5H9.75C9.55109 13.5 9.36032 13.421 9.21967 13.2803C9.07902 13.1397 9 12.9489 9 12.75Z"
                            fill="white" />
                    </svg>
                    View Archive
                </a>
            </div>

            <!-- Patients Tab (Only Tab Now) -->
            <div id="patients-tab" class="tab-content py-6 active">
                <?php if ($manualSelectMode): ?>
                    <div class="manual-export-controls mb-6">
                        <form method="POST" action="" id="manualExportForm" class="manual-export-form">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-secondary mb-2">
                                        <i class="fas fa-user-check mr-2 text-primary"></i>
                                        Select Patients for Export
                                    </h4>
                                    <p class="text-sm text-gray-600">Check the patients you want to include in the export.
                                        <br><span class="text-primary font-medium">Selected: <span
                                                id="selectedCount">0</span> patients</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="selectAllPatients"
                                            class="patient-checkbox select-all-checkbox" onchange="toggleAllPatients(this)">
                                        <label for="selectAllPatients" class="ml-2 text-sm font-medium text-gray-700">Select
                                            All</label>
                                    </div>
                                    <button type="button" onclick="disableManualSelection()" class="btn-gray px-4 py-2">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                    <button type="button" onclick="confirmManualExport('excel')"
                                        class="btn-export px-4 py-2">
                                        <i class="fas fa-file-excel mr-2"></i>Export as Excel
                                    </button>
                                    <button type="button" onclick="confirmManualExport('pdf')" class="btn-pdf px-4 py-2">
                                        <i class="fas fa-file-pdf mr-2"></i>Export as PDF
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <form method="get" action="" class="mb-6" id="mainSearchForm">
                    <!-- Loading Overlay -->
                    <div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(255,255,255,0.8);backdrop-filter:blur(6px);justify-content:center;align-items:center;">
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <div class="loader" style="border:8px solid #f3f3f3;border-top:8px solid #3498db;border-radius:50%;width:80px;height:80px;animation:spin 1s linear infinite;"></div>
                            <span style="margin-top:24px;font-size:1.5rem;color:#3498db;font-weight:600;">Loading...</span>
                        </div>
                    </div>
                    <style>
                        @keyframes spin {
                            0% {
                                transform: rotate(0deg);
                            }

                            100% {
                                transform: rotate(360deg);
                            }
                        }
                    </style>
                    <script>
                        // Show loading overlay on search submit or Enter key
                        document.addEventListener('DOMContentLoaded', function() {
                            var searchForm = document.getElementById('mainSearchForm');
                            var searchInput = document.getElementById('search');
                            var searchSubmitBtn = document.getElementById('searchSubmitBtn');
                            let loadingStart = 0;

                            function updateSearchButtonState() {
                                if (!searchInput || !searchSubmitBtn) return;
                                var hasInput = searchInput.value.trim().length > 0;
                                searchSubmitBtn.disabled = !hasInput;
                                searchSubmitBtn.classList.toggle('opacity-50', !hasInput);
                                searchSubmitBtn.classList.toggle('cursor-not-allowed', !hasInput);
                            }

                            function showLoader() {
                                loadingStart = Date.now();
                                document.getElementById('loadingOverlay').style.display = 'flex';
                            }

                            function hideLoader() {
                                const elapsed = Date.now() - loadingStart;
                                const minTime = 2000;
                                if (elapsed < minTime) {
                                    setTimeout(() => {
                                        document.getElementById('loadingOverlay').style.display = 'none';
                                    }, minTime - elapsed);
                                } else {
                                    document.getElementById('loadingOverlay').style.display = 'none';
                                }
                            }
                            if (searchForm) {
                                searchForm.addEventListener('submit', function(e) {
                                    if (searchInput && searchInput.value.trim().length === 0) {
                                        e.preventDefault();
                                        updateSearchButtonState();
                                        return;
                                    }
                                    showLoader();
                                });
                            }
                            if (searchInput) {
                                updateSearchButtonState();
                                searchInput.addEventListener('input', updateSearchButtonState);
                                searchInput.addEventListener('keydown', function(e) {
                                    if (e.key === 'Enter' && searchInput.value.trim().length > 0) {
                                        showLoader();
                                    }
                                });
                            }
                            // Hide overlay after page load (in case of back navigation)
                            window.addEventListener('pageshow', function() {
                                hideLoader();
                            });
                        });
                    </script>
                    <input type="hidden" name="tab" value="patients-tab">
                    <?php if ($viewAll): ?>
                        <input type="hidden" name="view_all" value="true">
                    <?php endif; ?>
                    <?php if ($manualSelectMode): ?>
                        <input type="hidden" name="manual_select" value="true">
                    <?php endif; ?>

                    <div class="search-form-container flex flex-wrap items-end gap-5">
                        <!-- Search Term Field with icon inside input -->
                        <div class="search-field-group flex-grow min-w-[250px]">
                            <label for="search" class="block text-gray-700 text-xl mb-4 font-medium">
                                Search Record
                            </label>

                            <!-- SEARCH RECORD / EXPORT RECORDS / FILTER  -->
                            <div
                                class="flex flex-col md:flex-row border-b-2 pb-6 border-gray-100 justify-between items-center">
                                <!-- LEFT CONTENT -->
                                <div class="flex gap-4">
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-7 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none z-10"></i>
                                        <input type="text" id="search" name="search"
                                            value="<?= htmlspecialchars($searchTerm) ?>"
                                            placeholder="<?= $searchBy === 'unique_number' ? 'Enter Patients Name...' : 'Search patients by name...' ?>"
                                            class="search-input w-full pl-10 py-3 px-16 text-base font-normal rounded-md focus:outline-none border border-[#3C96E1] focus:ring-2 focus:ring-blue-400 focus:border-blue-500">
                                    </div>

                                    <!-- Search Button -->
                                    <div class="flex-shrink-0">
                                        <?php if (empty($searchTerm)): ?>
                                            <button type="submit" id="searchSubmitBtn" class="btn-primary inline-flex items-center py-3 px-10" disabled>
                                                <!-- <i class="fas fa-search mr-2"></i>  -->
                                                Search
                                            </button>
                                        <?php else: ?>
                                            <a href="existing_info_patients.php<?= $manualSelectMode ? '?manual_select=true&tab=patients-tab' : '?tab=patients-tab' ?>"
                                                class="btn-gray inline-flex items-center px-6 rounded-none"
                                                style="border-radius: 4px; border: none; color:white; background-color: #95A5A6;">
                                                <i class="fas fa-times mr-2"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- RIGHT CONTENT -->
                                <div class="flex items-center gap-4">
                                    <!-- Export Records Button -->
                                    <button type="button" onclick="openExportModal()"
                                        class="btn-export inline-flex text-base items-center px-6"
                                        style="background-color: #2ECC71;">
                                        <svg class="h-6 w-6 mr-2" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M21 9.75C21 9.94891 20.921 10.1397 20.7803 10.2803C20.6397 10.421 20.4489 10.5 20.25 10.5C20.0511 10.5 19.8603 10.421 19.7197 10.2803C19.579 10.1397 19.5 9.94891 19.5 9.75V5.56125L13.2816 11.7806C13.1408 11.9214 12.95 12.0004 12.7509 12.0004C12.5519 12.0004 12.361 11.9214 12.2203 11.7806C12.0796 11.6399 12.0005 11.449 12.0005 11.25C12.0005 11.051 12.0796 10.8601 12.2203 10.7194L18.4387 4.5H14.25C14.0511 4.5 13.8603 4.42098 13.7197 4.28033C13.579 4.13968 13.5 3.94891 13.5 3.75C13.5 3.55109 13.579 3.36032 13.7197 3.21967C13.8603 3.07902 14.0511 3 14.25 3H20.25C20.4489 3 20.6397 3.07902 20.7803 3.21967C20.921 3.36032 21 3.55109 21 3.75V9.75ZM17.25 12C17.0511 12 16.8603 12.079 16.7197 12.2197C16.579 12.3603 16.5 12.5511 16.5 12.75V19.5H4.5V7.5H11.25C11.4489 7.5 11.6397 7.42098 11.7803 7.28033C11.921 7.13968 12 6.94891 12 6.75C12 6.55109 11.921 6.36032 11.7803 6.21967C11.6397 6.07902 11.4489 6 11.25 6H4.5C4.10218 6 3.72064 6.15804 3.43934 6.43934C3.15804 6.72064 3 7.10218 3 7.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H16.5C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5V12.75C18 12.5511 17.921 12.3603 17.7803 12.2197C17.6397 12.079 17.4489 12 17.25 12Z"
                                                fill="white" />
                                        </svg>
                                        Export
                                    </button>
                                    <!-- Filter by Date Added/Timestamp -->
                                    <form method="get" action="" class="flex items-center gap-2" id="searchForm">
                                        <!-- Loading Overlay -->
                                        <div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(255,255,255,0.8);backdrop-filter:blur(6px);justify-content:center;align-items:center;">
                                            <div style="display:flex;flex-direction:column;align-items:center;">
                                                <div class="loader" style="border:8px solid #f3f3f3;border-top:8px solid #3498db;border-radius:50%;width:80px;height:80px;animation:spin 1s linear infinite;"></div>
                                                <span style="margin-top:24px;font-size:1.5rem;color:#3498db;font-weight:600;">Loading...</span>
                                            </div>
                                        </div>
                                        <style>
                                            @keyframes spin {
                                                0% {
                                                    transform: rotate(0deg);
                                                }

                                                100% {
                                                    transform: rotate(360deg);
                                                }
                                            }
                                        </style>
                                        <script>
                                            // Show loading overlay on search submit
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var searchForm = document.getElementById('searchForm');
                                                if (searchForm) {
                                                    searchForm.addEventListener('submit', function() {
                                                        document.getElementById('loadingOverlay').style.display = 'flex';
                                                    });
                                                }
                                                // Hide overlay after page load (in case of back navigation)
                                                window.addEventListener('pageshow', function() {
                                                    document.getElementById('loadingOverlay').style.display = 'none';
                                                });
                                            });
                                        </script>
                                        <input type="hidden" name="tab" value="patients-tab">
                                        <?php if ($viewAll): ?>
                                            <input type="hidden" name="view_all" value="true">
                                        <?php endif; ?>
                                        <?php if ($manualSelectMode): ?>
                                            <input type="hidden" name="manual_select" value="true">
                                        <?php endif; ?>
                                        <select name="patient_type" onchange="this.form.submit()"
                                            class="custom-select-filter">
                                            class="custom-select-filter" style="width: 232px; min-width: 232px; font-size: 18px; font-weight: 500; padding-left: 18px; padding-right: 44px;">
                                            <option value="all" <?= ($patientTypeFilter === 'all' || $patientTypeFilter === '' || !isset($patientTypeFilter)) ? 'selected' : '' ?>>All Patient Types</option>
                                            <option value="account_access" <?= $patientTypeFilter === 'account_access' ? 'selected' : '' ?>>Account Access</option>
                                            <option value="regular_patient" <?= $patientTypeFilter === 'regular_patient' ? 'selected' : '' ?>>Regular Patient</option>
                                        </select>
                                        <select name="date_sort" onchange="this.form.submit()"
                                            class="custom-select-filter ml-2">
                                            <option value="desc" <?= (empty($_GET['date_sort']) || $_GET['date_sort'] === 'desc') ? 'selected' : '' ?>>Newest First</option>
                                            <option value="asc" <?= (isset($_GET['date_sort']) && $_GET['date_sort'] === 'asc') ? 'selected' : '' ?>>Oldest First</option>
                                        </select>
                                        <style>
                                            .custom-select-filter {

                                                /* Hide default browser arrow for select and keep only custom SVG arrow */
                                                select.custom-select-filter {
                                                    -webkit-appearance: none;
                                                    -moz-appearance: none;
                                                    appearance: none;
                                                }

                                                select.custom-select-filter::-ms-expand {
                                                    display: none;
                                                }

                                                /* Hide default browser arrow for select and keep only custom SVG arrow */
                                                select.custom-select-filter {
                                                    -webkit-appearance: none;
                                                    -moz-appearance: none;
                                                    appearance: none;
                                                    background-image: url('data:image/svg+xml;utf8,<svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5" stroke="%2322233b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
                                                    background-repeat: no-repeat;
                                                    background-position: right 2rem center;
                                                    background-size: 1.5rem 1.5rem;
                                                    padding-right: 4.5rem;
                                                    /* Increased to add gap between text and arrow icon */
                                                }

                                                select.custom-select-filter::-ms-expand {
                                                    display: none;
                                                }

                                                width: auto;
                                                font-size: 1rem;
                                                font-weight: 500;
                                                color: #22223b;
                                                background: #fff;
                                                border: 1px solid #3C96E1;
                                                border-radius: 6px;
                                                height: 56px;
                                                padding: 0 3.5rem 0 1.5rem;
                                            }

                                            /* Remove custom arrow for date input */
                                            input[type="date"].custom-select-filter {
                                                background-image: none !important;
                                                padding-right: 1.5rem;
                                                appearance: none;
                                                -webkit-appearance: none;
                                                -moz-appearance: none;
                                                box-shadow: 0 2px 8px 0 rgba(60, 150, 225, 0.08);
                                                position: relative;
                                                transition: border 0.2s, box-shadow 0.2s;
                                                display: flex;
                                                align-items: center;
                                            }

                                            .custom-select-filter:focus {
                                                outline: none;
                                                border: 2px solid #3C96E1;
                                                box-shadow: 0 0 0 2px #60a5fa33;
                                            }

                                            .custom-select-filter::-ms-expand {
                                                display: none;
                                            }

                                            /* Custom arrow */
                                            /* Custom arrow for select filters only */
                                            select.custom-select-filter {
                                                background-image: url('data:image/svg+xml;utf8,<svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5" stroke="%232F80ED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
                                                background-repeat: no-repeat;
                                                background-position: right 18px center;
                                                background-size: 24px 24px;
                                                padding-right: 60px;
                                                /* Increased to add gap between text and arrow icon */
                                            }

                                            select.custom-select-filter::-ms-expand {
                                                display: none;
                                            }
                                        </style>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($searchTerm)): ?>
                    <div class="overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-secondary">Search Results for
                                "<?= htmlspecialchars($searchTerm) ?>"</h3>
                        </div>

                        <?php if (empty($patients) && empty($searchedUsers)): ?>
                            <div class="text-center py-12 rounded-lg">
                                <div class="flex justify-center">
                                    <svg width="100" height="100" class="mb-4" viewBox="0 0 100 100" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M89.7113 85.2895L70.1528 65.7348C75.8216 58.9289 78.6484 50.1996 78.0451 41.3626C77.4417 32.5257 73.4547 24.2616 66.9135 18.2894C60.3722 12.3172 51.7803 9.09671 42.925 9.29796C34.0698 9.49921 25.6331 13.1067 19.3699 19.3699C13.1067 25.6331 9.49921 34.0698 9.29796 42.925C9.09671 51.7803 12.3172 60.3722 18.2894 66.9135C24.2616 73.4547 32.5257 77.4417 41.3626 78.0451C50.1996 78.6484 58.9289 75.8216 65.7348 70.1528L85.2895 89.7113C85.5798 90.0017 85.9245 90.232 86.3039 90.3891C86.6832 90.5463 87.0898 90.6272 87.5004 90.6272C87.911 90.6272 88.3176 90.5463 88.697 90.3891C89.0763 90.232 89.421 90.0017 89.7113 89.7113C90.0017 89.421 90.232 89.0763 90.3891 88.697C90.5463 88.3176 90.6272 87.911 90.6272 87.5004C90.6272 87.0898 90.5463 86.6832 90.3891 86.3039C90.232 85.9245 90.0017 85.5798 89.7113 85.2895ZM15.6254 43.7504C15.6254 38.1878 17.2749 32.7501 20.3653 28.125C23.4557 23.4999 27.8483 19.895 32.9874 17.7663C38.1266 15.6376 43.7816 15.0806 49.2373 16.1658C54.693 17.251 59.7044 19.9297 63.6378 23.863C67.5711 27.7964 70.2498 32.8078 71.335 38.2635C72.4202 43.7192 71.8632 49.3742 69.7345 54.5134C67.6058 59.6525 64.001 64.0451 59.3758 67.1355C54.7507 70.2259 49.313 71.8754 43.7504 71.8754C36.2937 71.8671 29.1448 68.9013 23.8722 63.6287C18.5995 58.356 15.6337 51.2071 15.6254 43.7504Z"
                                            fill="black" fill-opacity="0.4" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-medium mb-4 text-gray-500">No patients or users found matching your search.</h3>
                                <p class="mt-1 text-lg text-gray-500">No record found. Maybe the spelling is different, please try again</p>
                            </div>
                        <?php else: ?>
                            <!-- PATIENT SEARCH RESULTS -->
                            <?php if (!empty($patients)): ?>
                                <div class="p-4">
                                    <div class="overflow-x-auto" style="max-height: 600px; overflow-y: auto;">
                                        <table class="patient-table">
                                            <thead>
                                                <tr>
                                                    <th>R.ID</th>
                                                    <th>Name</th>
                                                    <th>Date of Birth</th>
                                                    <th>Age</th>
                                                    <th>Gender</th>
                                                    <?php if ($sitioExists): ?>
                                                        <th>Sitio</th>
                                                    <?php endif; ?>
                                                    <?php if ($civilStatusExists): ?>
                                                        <th>Civil Status</th>
                                                    <?php endif; ?>
                                                    <?php if ($occupationExists): ?>
                                                        <th>Occupation</th>
                                                    <?php endif; ?>
                                                    <th>Record Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($patients as $index => $patient): ?>
                                                    <tr>
                                                        <td class="patient-id"><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($patient['full_name']) ?></td>
                                                        <td><?= !empty($patient['date_of_birth']) ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A' ?>
                                                        </td>
                                                        <td><?= $patient['age'] ?? 'N/A' ?></td>
                                                        <td><?= !empty($patient['gender']) ? (($patient['gender'] === 'male') ? 'Male' : (($patient['gender'] === 'female') ? 'Female' : htmlspecialchars($patient['gender']))) : 'N/A' ?>
                                                        </td>
                                                        <?php if ($sitioExists): ?>
                                                            <td><?= htmlspecialchars($patient['sitio'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <?php if ($civilStatusExists): ?>
                                                            <td><?= htmlspecialchars($patient['civil_status'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <?php if ($occupationExists): ?>
                                                            <td><?= htmlspecialchars($patient['occupation'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <?php if ($patient['patient_type'] === 'Registered Patient'): ?>
                                                                <span class="user-badge">Account Access</span>
                                                            <?php else: ?>
                                                                <span class="regular-badge">Regular Patient</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
    <button type="button" onclick="openViewModal(<?= $patient['id'] ?>)"
        class="btn-view inline-flex items-center mr-2" 
        style="background:#2196F3; color:#fff; border:none; border-radius:30px; padding:13px 24px; font-weight:500; font-size:16px; line-height:1.5; min-width:100px; justify-content:center;">
        <svg class="mr-1" style="width:1.5em; height:1.5em;" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14C13.1046 14 14 13.1046 14 12ZM16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" fill="white" />
            <path d="M12 3C16.4111 3 18.9532 5.23875 20.3477 7.46973C21.034 8.56793 21.4421 9.65839 21.6787 10.4697C21.7975 10.8772 21.8749 11.2197 21.9229 11.4639C21.9468 11.5859 21.9635 11.684 21.9746 11.7539C21.9801 11.7886 21.9844 11.8165 21.9873 11.8369C21.9887 11.8471 21.9894 11.8559 21.9902 11.8623C21.9907 11.8655 21.9909 11.8688 21.9912 11.8711L21.9922 11.874V11.875L20.0078 12.125V12.126C20.0077 12.1248 20.0075 12.1218 20.0068 12.1172C20.0055 12.1074 20.0028 12.09 19.999 12.0664C19.9915 12.0192 19.9789 11.9451 19.96 11.8486C19.922 11.6553 19.8586 11.3725 19.7588 11.0303C19.558 10.3417 19.2158 9.43184 18.6523 8.53027C17.5468 6.76136 15.5886 5 12 5C8.41136 5 6.45322 6.76136 5.34766 8.53027C4.78423 9.43184 4.44204 10.3417 4.24121 11.0303C4.14141 11.3725 4.07802 11.6553 4.04004 11.8486C4.02109 11.9451 4.00845 12.0192 4.00098 12.0664C3.99724 12.09 3.99454 12.1074 3.99316 12.1172L3.99219 12.126V12.125L2.00781 11.875V11.874L2.00879 11.8711C2.00908 11.8688 2.00934 11.8655 2.00977 11.8623C2.01062 11.8559 2.01126 11.8471 2.0127 11.8369C2.01558 11.8165 2.01989 11.7886 2.02539 11.7539C2.03646 11.684 2.05319 11.5859 2.07715 11.4639C2.1251 11.2197 2.20246 10.8772 2.32129 10.4697C2.55795 9.65839 2.96597 8.56793 3.65234 7.46973C5.04682 5.23875 7.58887 3 12 3Z" fill="white" />
        </svg>
        View
    </button>
    <button type="button" onclick="openArchiveModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['full_name']) ?>', '?delete_patient=<?= $patient['id'] ?>')" 
        class="btn-archive inline-flex items-center" 
        style="background:#F44336; color:#fff; border:none; border-radius:30px; padding:13px 24px; font-weight:500; font-size:16px; line-height:1.5; min-width:100px; justify-content:center;">
        <svg class="mr-1" style="width:1.5em; height:1.5em;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 4.5H3C2.60218 4.5 2.22064 4.65804 1.93934 4.93934C1.65804 5.22064 1.5 5.60218 1.5 6V8.25C1.5 8.64782 1.65804 9.02936 1.93934 9.31066C2.22064 9.59196 2.60218 9.75 3 9.75V18C3 18.3978 3.15804 18.7794 3.43934 19.0607C3.72064 19.342 4.10218 19.5 4.5 19.5H19.5C19.8978 19.5 20.2794 19.342 20.5607 19.0607C20.842 18.7794 21 18.3978 21 18V9.75C21.3978 9.75 21.7794 9.59196 22.0607 9.31066C22.342 9.02936 22.5 8.64782 22.5 8.25V6C22.5 5.60218 22.342 5.22064 22.0607 4.93934C21.7794 4.65804 21.3978 4.5 21 4.5ZM19.5 18H4.5V9.75H19.5V18ZM21 8.25H3V6H21V8.25ZM9 12.75C9 12.5511 9.07902 12.3603 9.21967 12.2197C9.36032 12.079 9.55109 12 9.75 12H14.25C14.4489 12 14.6397 12.079 14.7803 12.2197C14.921 12.3603 15 12.5511 15 12.75C15 12.9489 14.921 13.1397 14.7803 13.2803C14.6397 13.421 14.4489 13.5 14.25 13.5H9.75C9.55109 13.5 9.36032 13.421 9.21967 13.2803C9.07902 13.1397 9 12.9489 9 12.75Z" fill="white" />
        </svg>
        Archive
    </button>
</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>

<!-- Archive Confirmation Modal -->
<div id="archiveConfirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-[100]" style="display: none;">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="bg-red-500 px-6 py-4 flex items-center gap-3">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            <h3 class="text-xl font-semibold text-white">Archive Patient Record</h3>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linecap="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-2">Confirm Archive Action</h4>
                    <p class="text-gray-600">Are you sure you want to archive this patient record?</p>
                    <p class="text-sm text-gray-500 mt-2">This action will move the record to archive. You can restore it later from the archive page.</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <i class="fas fa-info-circle text-yellow-600"></i>
                    <p class="text-sm text-yellow-700">
                        <span class="font-semibold">Note:</span> All medical records and consultation notes will also be archived.
                    </p>
                </div>
            </div>
            
            <!-- Patient Info Preview (will be populated dynamically) -->
            <div id="archivePatientPreview" class="bg-gray-50 rounded-lg p-4 mb-6 hidden">
                <p class="text-sm font-medium text-gray-700 mb-2">Patient: <span id="archivePatientName" class="font-normal text-gray-600"></span></p>
                <p class="text-sm text-gray-500">ID: <span id="archivePatientId"></span></p>
            </div>
            
            <!-- Buttons -->
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeArchiveModal()" 
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition duration-200">
                    Cancel
                </button>
                <a href="#" id="confirmArchiveBtn" 
                    class="px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition duration-200 inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Archive Record
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Archive Confirmation Modal Functions
let currentArchiveUrl = '';

function openArchiveModal(patientId, patientName, archiveUrl) {
    const modal = document.getElementById('archiveConfirmModal');
    const patientPreview = document.getElementById('archivePatientPreview');
    const patientNameSpan = document.getElementById('archivePatientName');
    const patientIdSpan = document.getElementById('archivePatientId');
    const confirmBtn = document.getElementById('confirmArchiveBtn');
    
    // Store the archive URL
    currentArchiveUrl = archiveUrl;
    
    // Update patient info
    if (patientName) {
        patientNameSpan.textContent = patientName;
        patientIdSpan.textContent = patientId;
        patientPreview.style.display = 'block';
    } else {
        patientPreview.style.display = 'none';
    }
    
    // Set the confirm button link
    confirmBtn.href = archiveUrl;
    
    // Show modal with animation
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

function closeArchiveModal() {
    const modal = document.getElementById('archiveConfirmModal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        // Clear stored URL
        currentArchiveUrl = '';
    }, 300);
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('archiveConfirmModal');
    if (event.target === modal) {
        closeArchiveModal();
    }
});

// Keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeArchiveModal();
    }
});
</script>
                            

                            <!-- USER SEARCH RESULT -->
                            <?php if (!empty($searchedUsers)): ?>
                                <div class="p-4 border-t border-gray-200">
                                    <h4 class="text-md font-medium text-secondary mb-3">Registered Users</h4>
                                    <div class="overflow-x-auto">
                                        <table class="patient-table">
                                            <thead>
                                                <tr>
                                                    <th>R.ID</th>
                                                    <th>Name</th>
                                                    <th>Date of Birth</th>
                                                    <th>Age</th>
                                                    <th>Gender</th>
                                                    <?php if ($sitioExists): ?>
                                                        <th>Sitio</th>
                                                    <?php endif; ?>
                                                    <?php if ($civilStatusExists): ?>
                                                        <th>Civil Status</th>
                                                    <?php endif; ?>
                                                    <?php if ($occupationExists): ?>
                                                        <th>Occupation</th>
                                                    <?php endif; ?>
                                                    <th class="record-type-col">Record Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($searchedUsers as $index => $user): ?>
                                                    <tr>
                                                        <td class="patient-id"><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                        <td><?= !empty($user['date_of_birth']) ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A' ?>
                                                        </td>
                                                        <td><?= $user['age'] ?? 'N/A' ?></td>
                                                        <td><?= !empty($user['gender']) ? htmlspecialchars($user['gender']) : 'N/A' ?>
                                                        </td>
                                                        <?php if ($sitioExists): ?>
                                                            <td><?= htmlspecialchars($user['sitio'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <?php if ($civilStatusExists): ?>
                                                            <td><?= htmlspecialchars($user['civil_status'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <?php if ($occupationExists): ?>
                                                            <td><?= htmlspecialchars($user['occupation'] ?? 'N/A') ?></td>
                                                        <?php endif; ?>
                                                        <td><span class="user-badge">Registered User</span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($searchTerm)): ?>
                    <div class="overflow-hidden">
                        <?php if (empty($allPatients)): ?>
                            <div class="text-center py-12 rounded-lg">
                                <div class="flex justify-center py-8">
                                    <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M53.6665 29.2372L73.5581 34.533M49.4081 45.0538L59.3498 47.7038M49.904 74.858L53.879 75.9205C65.129 78.9205 70.754 80.4163 75.1873 77.8705C79.6165 75.3288 81.1248 69.733 84.1373 58.5497L88.3998 42.7288C91.4165 31.5413 92.9206 25.9497 90.3623 21.5413C87.804 17.133 82.1831 15.6372 70.929 12.6413L66.954 11.5788C55.704 8.57882 50.079 7.08299 45.6498 9.62882C41.2165 12.1705 39.7081 17.7663 36.6915 28.9497L32.4331 44.7705C29.4165 55.958 27.9081 61.5497 30.4706 65.958C33.029 70.3622 38.654 71.8622 49.904 74.858Z" stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M50.0008 87.273L46.0341 88.3564C34.8091 91.4105 29.2008 92.9397 24.7758 90.3439C20.3591 87.7522 18.8508 82.048 15.8466 70.6439L11.5924 54.5105C8.58409 43.1064 7.07993 37.4022 9.63409 32.9106C11.8424 29.0231 16.6674 29.1647 22.9174 29.1647" stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round" />
                                    </svg>

                                </div>
                                <h3 class="text-xl font-medium text-gray-500 mb-4">No Residents Records Yet</h3>
                                <p class="mt-1 text-lg text-gray-500">Get started by adding a new resident records.</p>
                                <div class="mt-6">
                                    <button onclick="openAddPatientModal()"
                                        class="btn-add-patient inline-flex items-center py-3 px-6">
                                        <svg class="h-8 w-8 mr-2" viewBox="0 0 27 27" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M23.625 13.5C23.625 13.7238 23.5361 13.9384 23.3779 14.0966C23.2196 14.2549 23.005 14.3438 22.7812 14.3438H14.3438V22.7812C14.3438 23.005 14.2549 23.2196 14.0966 23.3779C13.9384 23.5361 13.7238 23.625 13.5 23.625C13.2762 23.625 13.0616 23.5361 12.9034 23.3779C12.7451 23.2196 12.6562 23.005 12.6562 22.7812V14.3438H4.21875C3.99497 14.3438 3.78036 14.2549 3.62213 14.0966C3.4639 13.9384 3.375 13.7238 3.375 13.5C3.375 13.2762 3.4639 13.0616 3.62213 12.9034C3.78036 12.7451 3.99497 12.6562 4.21875 12.6562H12.6562V4.21875C12.6562 3.99497 12.7451 3.78036 12.9034 3.62213C13.0616 3.4639 13.2762 3.375 13.5 3.375C13.7238 3.375 13.9384 3.4639 14.0966 3.62213C14.2549 3.78036 14.3438 3.99497 14.3438 4.21875V12.6562H22.7812C23.005 12.6562 23.2196 12.7451 23.3779 12.9034C23.5361 13.0616 23.625 13.2762 23.625 13.5Z"
                                                fill="white" />
                                        </svg>
                                        Add New Records
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>

                            <!-- VIEW ALL PATIENT -->
                            <?php if ($viewAll || $manualSelectMode): ?>
                                <div class="p-4">
                                    <?php if (!$manualSelectMode): ?>
                                        <a href="existing_info_patients.php?tab=patients-tab"
                                            class="btn-back-to-pagination inline-flex items-center">
                                            <i class="fas fa-arrow-left mr-3"></i>Back to Pagination View
                                        </a>
                                    <?php endif; ?>
                                    <div class="scrollable-table-container">
                                        <form method="POST" action="" id="patientSelectionForm">
                                            <table class="patient-table view-all-patients-table">
                                                <thead>
                                                    <tr>
                                                        <?php if ($manualSelectMode): ?>
                                                            <th class="checkbox-column">
                                                                <input type="checkbox" id="selectAll" class="patient-checkbox"
                                                                    onchange="toggleAllSelection(this)">
                                                            </th>
                                                        <?php endif; ?>
                                                        <th>R.ID</th>
                                                        <th>Name</th>
                                                        <th>Date of Birth</th>
                                                        <th>Age</th>
                                                        <th>Gender</th>
                                                        <?php if ($sitioExists): ?>
                                                            <th>Sitio</th>
                                                        <?php endif; ?>
                                                        <?php if ($civilStatusExists): ?>
                                                            <th>Civil Status</th>
                                                        <?php endif; ?>
                                                        <?php if ($occupationExists): ?>
                                                            <th>Occupation</th>
                                                        <?php endif; ?>
                                                        <th>Record Type</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allPatients as $index => $patient): ?>
                                                        <tr data-patient-id="<?= $patient['id'] ?>">
                                                            <?php if ($manualSelectMode): ?>
                                                                <td class="checkbox-column">
                                                                    <input type="checkbox" name="selected_patients[]"
                                                                        value="<?= $patient['id'] ?>"
                                                                        class="patient-checkbox patient-select"
                                                                        onchange="updateSelectedCount()">
                                                                </td>
                                                            <?php endif; ?>
                                                            <td class="patient-id"><?= $index + 1 ?></td>
                                                            <td><?= htmlspecialchars($patient['full_name']) ?></td>
                                                            <td><?= !empty($patient['date_of_birth']) ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A' ?>
                                                            </td>
                                                            <td><?= $patient['age'] ?? 'N/A' ?></td>
                                                            <td><?= !empty($patient['gender']) ? htmlspecialchars($patient['gender']) : 'N/A' ?>
                                                            </td>
                                                            <?php if ($sitioExists): ?>
                                                                <td><?= !empty($patient['sitio']) ? htmlspecialchars($patient['sitio']) : (!empty($patient['user_sitio']) ? htmlspecialchars($patient['user_sitio']) : 'N/A') ?>
                                                                </td>
                                                            <?php endif; ?>
                                                            <?php if ($civilStatusExists): ?>
                                                                <td><?= !empty($patient['civil_status']) ? htmlspecialchars($patient['civil_status']) : (!empty($patient['user_civil_status']) ? htmlspecialchars($patient['user_civil_status']) : 'N/A') ?>
                                                                </td>
                                                            <?php endif; ?>
                                                            <?php if ($occupationExists): ?>
                                                                <td><?= !empty($patient['occupation']) ? htmlspecialchars($patient['occupation']) : (!empty($patient['user_occupation']) ? htmlspecialchars($patient['user_occupation']) : 'N/A') ?>
                                                                </td>
                                                            <?php endif; ?>
                                                            <td><?= $patient['patient_type'] === 'Registered Patient' ? '<span class="user-badge">Account Access</span>' : '<span class="regular-badge">Regular Patient</span>' ?>
                                                            </td>
                                                            <td>
                                                                <button type="button" onclick="openViewModal(<?= $patient['id'] ?>)"
                                                                    class="btn-view inline-flex items-center mr-2">

                                                                    <svg class="mr-1 mt-1" style="width:1.5em;height:1.5em;vertical-align:middle;" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14C13.1046 14 14 13.1046 14 12ZM16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" fill="white" />
                                                                        <path d="M12 3C16.4111 3 18.9532 5.23875 20.3477 7.46973C21.034 8.56793 21.4421 9.65839 21.6787 10.4697C21.7975 10.8772 21.8749 11.2197 21.9229 11.4639C21.9468 11.5859 21.9635 11.684 21.9746 11.7539C21.9801 11.7886 21.9844 11.8165 21.9873 11.8369C21.9887 11.8471 21.9894 11.8559 21.9902 11.8623C21.9907 11.8655 21.9909 11.8688 21.9912 11.8711L21.9922 11.874V11.875L20.0078 12.125V12.126C20.0077 12.1248 20.0075 12.1218 20.0068 12.1172C20.0055 12.1074 20.0028 12.09 19.999 12.0664C19.9915 12.0192 19.9789 11.9451 19.96 11.8486C19.922 11.6553 19.8586 11.3725 19.7588 11.0303C19.558 10.3417 19.2158 9.43184 18.6523 8.53027C17.5468 6.76136 15.5886 5 12 5C8.41136 5 6.45322 6.76136 5.34766 8.53027C4.78423 9.43184 4.44204 10.3417 4.24121 11.0303C4.14141 11.3725 4.07802 11.6553 4.04004 11.8486C4.02109 11.9451 4.00845 12.0192 4.00098 12.0664C3.99724 12.09 3.99454 12.1074 3.99316 12.1172L3.99219 12.126V12.125L2.00781 11.875V11.874L2.00879 11.8711C2.00908 11.8688 2.00934 11.8655 2.00977 11.8623C2.01062 11.8559 2.01126 11.8471 2.0127 11.8369C2.01558 11.8165 2.01989 11.7886 2.02539 11.7539C2.03646 11.684 2.05319 11.5859 2.07715 11.4639C2.1251 11.2197 2.20246 10.8772 2.32129 10.4697C2.55795 9.65839 2.96597 8.56793 3.65234 7.46973C5.04682 5.23875 7.58887 3 12 3Z" fill="white" />
                                                                    </svg>

                                                                    View
                                                                </button>
                                                                <?php if (!$manualSelectMode): ?>
                                                                    <a href="?delete_patient=<?= $patient['id'] ?>"
                                                                        class="btn-archive inline-flex items-center"
                                                                        onclick="return confirm('Are you sure you want to archive this patient record?')">
                                                                        <svg class="mr-1" style="width:1.5em;height:1.5em;vertical-align:middle;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                            <path d="M21 4.5H3C2.60218 4.5 2.22064 4.65804 1.93934 4.93934C1.65804 5.22064 1.5 5.60218 1.5 6V8.25C1.5 8.64782 1.65804 9.02936 1.93934 9.31066C2.22064 9.59196 2.60218 9.75 3 9.75V18C3 18.3978 3.15804 18.7794 3.43934 19.0607C3.72064 19.342 4.10218 19.5 4.5 19.5H19.5C19.8978 19.5 20.2794 19.342 20.5607 19.0607C20.842 18.7794 21 18.3978 21 18V9.75C21.3978 9.75 21.7794 9.59196 22.0607 9.31066C22.342 9.02936 22.5 8.64782 22.5 8.25V6C22.5 5.60218 22.342 5.22064 22.0607 4.93934C21.7794 4.65804 21.3978 4.5 21 4.5ZM19.5 18H4.5V9.75H19.5V18ZM21 8.25H3V6H21V8.25ZM9 12.75C9 12.5511 9.07902 12.3603 9.21967 12.2197C9.36032 12.079 9.55109 12 9.75 12H14.25C14.4489 12 14.6397 12.079 14.7803 12.2197C14.921 12.3603 15 12.5511 15 12.75C15 12.9489 14.921 13.1397 14.7803 13.2803C14.6397 13.421 14.4489 13.5 14.25 13.5H9.75C9.55109 13.5 9.36032 13.421 9.21967 13.2803C9.07902 13.1397 9 12.9489 9 12.75Z" fill="white" />
                                                                        </svg> Archive
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>

                                <!-- ALL PATIENT TYPE -->
                                <div class="overflow-x-auto">
                                    <table class="patient-table">
                                        <thead>
                                            <tr>
                                                <th>R.ID</th>
                                                <th>Name</th>
                                                <th>Date of Birth</th>
                                                <th>Age</th>
                                                <th>Gender</th>
                                                <?php if ($sitioExists): ?>
                                                    <th>Sitio</th>
                                                <?php endif; ?>
                                                <?php if ($civilStatusExists): ?>
                                                    <th>Civil Status</th>
                                                <?php endif; ?>
                                                <?php if ($occupationExists): ?>
                                                    <th>Occupation</th>
                                                <?php endif; ?>
                                                <th>Record Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (!isset($offset)) {
                                                $offset = 0;
                                            }
                                            foreach ($allPatients as $index => $patient): ?>
                                                <tr data-patient-id="<?= $patient['id'] ?>">
                                                    <td class="patient-id"><?= $offset + $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($patient['full_name']) ?></td>
                                                    <td><?= !empty($patient['date_of_birth']) ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A' ?>
                                                    </td>
                                                    <td><?= $patient['age'] ?? 'N/A' ?></td>
                                                    <td><?= !empty($patient['gender']) ? htmlspecialchars($patient['gender']) : 'N/A' ?>
                                                    </td>
                                                    <?php if ($sitioExists): ?>
                                                        <td><?= !empty($patient['sitio']) ? htmlspecialchars($patient['sitio']) : (!empty($patient['user_sitio']) ? htmlspecialchars($patient['user_sitio']) : 'N/A') ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php if ($civilStatusExists): ?>
                                                        <td><?= !empty($patient['civil_status']) ? htmlspecialchars($patient['civil_status']) : (!empty($patient['user_civil_status']) ? htmlspecialchars($patient['user_civil_status']) : 'N/A') ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php if ($occupationExists): ?>
                                                        <td><?= !empty($patient['occupation']) ? htmlspecialchars($patient['occupation']) : (!empty($patient['user_occupation']) ? htmlspecialchars($patient['user_occupation']) : 'N/A') ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <?php if ($patient['patient_type'] === 'Registered Patient'): ?>
                                                            <span class="user-badge record-type-col">Account Access</span>
                                                        <?php elseif ($patient['patient_type'] === 'Regular Patient'): ?>
                                                            <span class="regular-badge record-type-col">Regular Patient</span>
                                                        <?php else: ?>
                                                            <span class="regular-badge">Regular Patient</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" onclick="openViewModal(<?= $patient['id'] ?>)"
                                                            class="btn-view mr-2">

                                                            <svg class="mr-1 mt-1" style="width:1.5em;height:1.5em;vertical-align:middle;" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14C13.1046 14 14 13.1046 14 12ZM16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" fill="white" />
                                                                <path d="M12 3C16.4111 3 18.9532 5.23875 20.3477 7.46973C21.034 8.56793 21.4421 9.65839 21.6787 10.4697C21.7975 10.8772 21.8749 11.2197 21.9229 11.4639C21.9468 11.5859 21.9635 11.684 21.9746 11.7539C21.9801 11.7886 21.9844 11.8165 21.9873 11.8369C21.9887 11.8471 21.9894 11.8559 21.9902 11.8623C21.9907 11.8655 21.9909 11.8688 21.9912 11.8711L21.9922 11.874V11.875L20.0078 12.125V12.126C20.0077 12.1248 20.0075 12.1218 20.0068 12.1172C20.0055 12.1074 20.0028 12.09 19.999 12.0664C19.9915 12.0192 19.9789 11.9451 19.96 11.8486C19.922 11.6553 19.8586 11.3725 19.7588 11.0303C19.558 10.3417 19.2158 9.43184 18.6523 8.53027C17.5468 6.76136 15.5886 5 12 5C8.41136 5 6.45322 6.76136 5.34766 8.53027C4.78423 9.43184 4.44204 10.3417 4.24121 11.0303C4.14141 11.3725 4.07802 11.6553 4.04004 11.8486C4.02109 11.9451 4.00845 12.0192 4.00098 12.0664C3.99724 12.09 3.99454 12.1074 3.99316 12.1172L3.99219 12.126V12.125L2.00781 11.875V11.874L2.00879 11.8711C2.00908 11.8688 2.00934 11.8655 2.00977 11.8623C2.01062 11.8559 2.01126 11.8471 2.0127 11.8369C2.01558 11.8165 2.01989 11.7886 2.02539 11.7539C2.03646 11.684 2.05319 11.5859 2.07715 11.4639C2.1251 11.2197 2.20246 10.8772 2.32129 10.4697C2.55795 9.65839 2.96597 8.56793 3.65234 7.46973C5.04682 5.23875 7.58887 3 12 3Z" fill="white" />
                                                            </svg>
                                                            View
                                                        </button>
                                                        <!-- With this: -->
<button type="button" onclick="openArchiveModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['full_name']) ?>', '?delete_patient=<?= $patient['id'] ?>')" 
    class="btn-archive inline-flex items-center">
    <svg class="mr-1" style="width:1.5em;height:1.5em;vertical-align:middle;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M21 4.5H3C2.60218 4.5 2.22064 4.65804 1.93934 4.93934C1.65804 5.22064 1.5 5.60218 1.5 6V8.25C1.5 8.64782 1.65804 9.02936 1.93934 9.31066C2.22064 9.59196 2.60218 9.75 3 9.75V18C3 18.3978 3.15804 18.7794 3.43934 19.0607C3.72064 19.342 4.10218 19.5 4.5 19.5H19.5C19.8978 19.5 20.2794 19.342 20.5607 19.0607C20.842 18.7794 21 18.3978 21 18V9.75C21.3978 9.75 21.7794 9.59196 22.0607 9.31066C22.342 9.02936 22.5 8.64782 22.5 8.25V6C22.5 5.60218 22.342 5.22064 22.0607 4.93934C21.7794 4.65804 21.3978 4.5 21 4.5ZM19.5 18H4.5V9.75H19.5V18ZM21 8.25H3V6H21V8.25ZM9 12.75C9 12.5511 9.07902 12.3603 9.21967 12.2197C9.36032 12.079 9.55109 12 9.75 12H14.25C14.4489 12 14.6397 12.079 14.7803 12.2197C14.921 12.3603 15 12.5511 15 12.75C15 12.9489 14.921 13.1397 14.7803 13.2803C14.6397 13.421 14.4489 13.5 14.25 13.5H9.75C9.55109 13.5 9.36032 13.421 9.21967 13.2803C9.07902 13.1397 9 12.9489 9 12.75Z" fill="white" />
    </svg> Archive
</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>


                                <!-- Archive Confirmation Modal -->
<div id="archiveConfirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-[100]" style="display: none;">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="bg-red-500 px-6 py-4 flex items-center gap-3">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            <h3 class="text-xl font-semibold text-white">Archive Patient Record</h3>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linecap="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-medium text-gray-800 mb-2">Confirm Archive Action</h4>
                    <p class="text-gray-600">Are you sure you want to archive this patient record?</p>
                    <p class="text-sm text-gray-500 mt-2">This action will move the record to archive. You can restore it later from the archive page.</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <i class="fas fa-info-circle text-yellow-600"></i>
                    <p class="text-sm text-yellow-700">
                        <span class="font-semibold">Note:</span> All medical records and consultation notes will also be archived.
                    </p>
                </div>
            </div>
            
            <!-- Patient Info Preview (will be populated dynamically) -->
            <div id="archivePatientPreview" class="bg-gray-50 rounded-lg p-4 mb-6 hidden">
                <p class="text-sm font-medium text-gray-700 mb-2">Patient: <span id="archivePatientName" class="font-normal text-gray-600"></span></p>
                <p class="text-sm text-gray-500">ID: <span id="archivePatientId"></span></p>
            </div>
            
            <!-- Buttons -->
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeArchiveModal()" 
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition duration-200">
                    Cancel
                </button>
                <a href="#" id="confirmArchiveBtn" 
                    class="px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition duration-200 inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Archive Record
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Archive Confirmation Modal Functions
let currentArchiveUrl = '';

function openArchiveModal(patientId, patientName, archiveUrl) {
    const modal = document.getElementById('archiveConfirmModal');
    const patientPreview = document.getElementById('archivePatientPreview');
    const patientNameSpan = document.getElementById('archivePatientName');
    const patientIdSpan = document.getElementById('archivePatientId');
    const confirmBtn = document.getElementById('confirmArchiveBtn');
    
    // Store the archive URL
    currentArchiveUrl = archiveUrl;
    
    // Update patient info
    if (patientName) {
        patientNameSpan.textContent = patientName;
        patientIdSpan.textContent = patientId;
        patientPreview.style.display = 'block';
    } else {
        patientPreview.style.display = 'none';
    }
    
    // Set the confirm button link
    confirmBtn.href = archiveUrl;
    
    // Show modal with animation
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

function closeArchiveModal() {
    const modal = document.getElementById('archiveConfirmModal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        // Clear stored URL
        currentArchiveUrl = '';
    }, 300);
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('archiveConfirmModal');
    if (event.target === modal) {
        closeArchiveModal();
    }
});

// Keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeArchiveModal();
    }
});
</script>

                                <!-- Enhanced Pagination Container with preserved filters -->
                                <div class="pagination-container">
                                    <!-- CSS Block: Pagination Styles -->
                                    <style>
                                        .bg-showing-paginate {
                                            background-color: rgba(52, 152, 219, 0.3);
                                            color: #3498DB;
                                        }
                                    </style>
                                    <div class="bg-showing-paginate rounded-full px-6 py-2 items-center ">
                                        <div>
                                            <p class="text-md text-[#3498DB] font-medium mt-1">
                                                <?php if ($viewAll): ?>
                                                    Showing all
                                                    <?= count($allPatients) ?> records
                                                <?php elseif ($manualSelectMode): ?>
                                                    Select patients to include in export
                                                <?php else: ?>
                                                    Showing
                                                    <?= count($allPatients) ?> of
                                                    <?= $totalRecords ?> records
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="pagination">
                                        <?php
                                        // Build query string for pagination links
                                        $queryParams = [];
                                        if (!empty($patientTypeFilter) && $patientTypeFilter !== 'all') {
                                            $queryParams[] = 'patient_type=' . urlencode($patientTypeFilter);
                                        }
                                        // Removed filter_date from query params
                                        if (!empty($_GET['date_sort'])) {
                                            $queryParams[] = 'date_sort=' . urlencode($_GET['date_sort']);
                                        }
                                        $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                                        ?>

                                        <!-- Previous Button -->
                                        <a href="?tab=patients-tab&page=<?= $currentPage - 1 ?><?= $queryString ?>"
                                            class="pagination-btn<?= ($currentPage <= 1 ? ' disabled' : '') ?>" style="margin: 0 4px;">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>

                                        <!-- Page Numbers -->
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <?php if ($i == 1 || $i == $totalPages || ($i >= $currentPage - 1 && $i <= $currentPage + 1)): ?>
                                                <a href="?tab=patients-tab&page=<?= $i ?><?= $queryString ?>"
                                                    class="pagination-btn<?= ($i == $currentPage ? ' active' : '') ?>" style="font-size: 1.1rem;">
                                                    <?= $i ?>
                                                </a>
                                            <?php elseif ($i == $currentPage - 2 || $i == $currentPage + 2): ?>
                                                <span class="pagination-btn disabled" style="pointer-events: none;">...</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <!-- Next Button -->
                                        <a href="?tab=patients-tab&page=<?= $currentPage + 1 ?><?= $queryString ?>"
                                            class="pagination-btn<?= ($currentPage >= $totalPages ? ' disabled' : '') ?>" style="margin: 0 4px;">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>

                                    <!-- Update the View All button in the header -->
                                    <div class="flex items-center gap-4">
                                        <a href="?tab=patients-tab&view_all=true&patient_type=<?= urlencode($patientTypeFilter) ?><?= !empty($_GET['date_sort']) ? '&date_sort=' . urlencode($_GET['date_sort']) : '' ?>"
                                            class="btn-view-all">
                                            <svg class="w-6 h-6 mr-2" viewBox="0 0 27 27" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M9.28125 6.75C9.28125 6.52622 9.37014 6.31161 9.52838 6.15338C9.68661 5.99515 9.90122 5.90625 10.125 5.90625H22.7812C23.005 5.90625 23.2196 5.99515 23.3779 6.15338C23.5361 6.31161 23.625 6.52622 23.625 6.75C23.625 6.97378 23.5361 7.18839 23.3779 7.34662C23.2196 7.50486 23.005 7.59375 22.7812 7.59375H10.125C9.90122 7.59375 9.68661 7.50486 9.52838 7.34662C9.37014 7.18839 9.28125 6.97378 9.28125 6.75ZM22.7812 12.6562H10.125C9.90122 12.6562 9.68661 12.7451 9.52838 12.9034C9.37014 13.0616 9.28125 13.2762 9.28125 13.5C9.28125 13.7238 9.37014 13.9384 9.52838 14.0966C9.68661 14.2549 9.90122 14.3438 10.125 14.3438H22.7812C23.005 14.3438 23.2196 14.2549 23.3779 14.0966C23.5361 13.9384 23.625 13.7238 23.625 13.5C23.625 13.2762 23.5361 13.0616 23.3779 12.9034C23.2196 12.7451 23.005 12.6562 22.7812 12.6562ZM22.7812 19.4062H10.125C9.90122 19.4062 9.68661 19.4951 9.52838 19.6534C9.37014 19.8116 9.28125 20.0262 9.28125 20.25C9.28125 20.4738 9.37014 20.6884 9.52838 20.8466C9.68661 21.0049 9.90122 21.0938 10.125 21.0938H22.7812C23.005 21.0938 23.2196 21.0049 23.3779 20.8466C23.5361 20.6884 23.625 20.4738 23.625 20.25C23.625 20.0262 23.5361 19.8116 23.3779 19.6534C23.2196 19.4951 23.005 19.4062 22.7812 19.4062ZM5.90625 5.90625H4.21875C3.99497 5.90625 3.78036 5.99515 3.62213 6.15338C3.4639 6.31161 3.375 6.52622 3.375 6.75C3.375 6.97378 3.4639 7.18839 3.62213 7.34662C3.78036 7.50486 3.99497 7.59375 4.21875 7.59375H5.90625C6.13003 7.59375 6.34464 7.50486 6.50287 7.34662C6.6611 7.18839 6.75 6.97378 6.75 6.75C6.75 6.52622 6.6611 6.31161 6.50287 6.15338C6.34464 5.99515 6.13003 5.90625 5.90625 5.90625ZM5.90625 12.6562H4.21875C3.99497 12.6562 3.78036 12.7451 3.62213 12.9034C3.4639 13.0616 3.375 13.2762 3.375 13.5C3.375 13.7238 3.4639 13.9384 3.62213 14.0966C3.78036 14.2549 3.99497 14.3438 4.21875 14.3438H5.90625C6.13003 14.3438 6.34464 14.2549 6.50287 14.0966C6.6611 13.9384 6.75 13.7238 6.75 13.5C6.75 13.2762 6.6611 13.0616 6.50287 12.9034C6.34464 12.7451 6.13003 12.6562 5.90625 12.6562ZM5.90625 19.4062H4.21875C3.99497 19.4062 3.78036 19.4951 3.62213 19.6534C3.4639 19.8116 3.375 20.0262 3.375 20.25C3.375 20.4738 3.4639 20.6884 3.62213 20.8466C3.78036 21.0049 3.99497 21.0938 4.21875 21.0938H5.90625C6.13003 21.0938 6.34464 21.0049 6.50287 20.8466C6.6611 20.6884 6.75 20.4738 6.75 20.25C6.75 20.0262 6.6611 19.8116 6.50287 19.6534C6.34464 19.4951 6.13003 19.4062 5.90625 19.4062Z"
                                                    fill="white" />
                                            </svg>
                                            View All Patients
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enhanced Wider Modal for Viewing Patient Info -->

    <div id="viewModal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 modal"
        style="display:none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-7xl h-[92vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="sticky top-0 z-20 bg-[#2563EB] px-10 py-6 flex items-center">
                <h3 class="text-xl font-medium flex gap-3 text-center w-full items-center text-white">
                    <!-- Eye/View Icon for Patient Health Information Modal -->
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"
                        class="mr-2">
                        <circle cx="18" cy="18" r="18" fill="#fff" fill-opacity="0.15" />
                        <path d="M18 11C12.5 11 8 18 8 18C8 18 12.5 25 18 25C23.5 25 28 18 28 18C28 18 23.5 11 18 11Z"
                            stroke="#fff" stroke-width="2" />
                        <circle cx="18" cy="18" r="4" fill="#fff" fill-opacity="0.7" stroke="#2563EB"
                            stroke-width="2" />
                    </svg>
                    Patient Health Information
                </h3>
                <button onclick="closeViewModal()" class="modal-close-btn">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.281 18.2198C19.3507 18.2895 19.406 18.3722 19.4437 18.4632C19.4814 18.5543 19.5008 18.6519 19.5008 18.7504C19.5008 18.849 19.4814 18.9465 19.4437 19.0376C19.406 19.1286 19.3507 19.2114 19.281 19.281C19.2114 19.3507 19.1286 19.406 19.0376 19.4437C18.9465 19.4814 18.849 19.5008 18.7504 19.5008C18.6519 19.5008 18.5543 19.4814 18.4632 19.4437C18.3722 19.406 18.2895 19.3507 18.2198 19.281L12.0004 13.0607L5.78104 19.281C5.64031 19.4218 5.44944 19.5008 5.25042 19.5008C5.05139 19.5008 4.86052 19.4218 4.71979 19.281C4.57906 19.1403 4.5 18.9494 4.5 18.7504C4.5 18.5514 4.57906 18.3605 4.71979 18.2198L10.9401 12.0004L4.71979 5.78104C4.57906 5.64031 4.5 5.44944 4.5 5.25042C4.5 5.05139 4.57906 4.86052 4.71979 4.71979C4.86052 4.57906 5.05139 4.5 5.25042 4.5C5.44944 4.5 5.64031 4.57906 5.78104 4.71979L12.0004 10.9401L18.2198 4.71979C18.3605 4.57906 18.5514 4.5 18.7504 4.5C18.9494 4.5 19.1403 4.57906 19.281 4.71979C19.4218 4.86052 19.5008 5.05139 19.5008 5.25042C19.5008 5.44944 19.4218 5.64031 19.281 5.78104L13.0607 12.0004L19.281 18.2198Z" fill="white" />
                    </svg>

                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-16">
                <div id="modalContent" class="min-h-[500px] py-6">
                    <!-- Content will be loaded via AJAX -->
                    <div class="flex justify-center items-center py-20">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-5xl text-primary mb-4"></i>
                            <p class="text-lg text-gray-600 font-medium">Loading patient data...</p>
                            <p class="text-sm text-gray-500 mt-2">Please wait while we retrieve the information</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Footer -->
            <div class="p-8 border-t border-gray-200 bg-white rounded-b-lg sticky bottom-0">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex flex-col items-start">
                        <span
                            class="flex items-center text-center gap-3 text-sm text-gray-500 bg-gray-100 px-8 py-4 rounded-full">
                            <svg width="34" height="34" viewBox="0 0 34 34" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M16.6667 33.3333C25.8717 33.3333 33.3333 25.8717 33.3333 16.6667C33.3333 7.46167 25.8717 0 16.6667 0C7.46167 0 0 7.46167 0 16.6667C0 25.8717 7.46167 33.3333 16.6667 33.3333ZM19.1667 9.58333C19.1667 10.3569 18.8594 11.0987 18.3124 11.6457C17.7654 12.1927 17.0235 12.5 16.25 12.5C15.4765 12.5 14.7346 12.1927 14.1876 11.6457C13.6406 11.0987 13.3333 10.3569 13.3333 9.58333C13.3333 8.80978 13.6406 8.06792 14.1876 7.52094C14.7346 6.97396 15.4765 6.66667 16.25 6.66667C17.0235 6.66667 17.7654 6.97396 18.3124 7.52094C18.8594 8.06792 19.1667 8.80978 19.1667 9.58333ZM17.6008 14.87C17.8264 15.0227 18.0111 15.2283 18.1388 15.4689C18.2665 15.7094 18.3333 15.9776 18.3333 16.25V22.72L19.9117 21.9308L21.4033 24.9117L17.4117 26.9075C17.1576 27.0345 16.8752 27.0944 16.5915 27.0816C16.3077 27.0688 16.0319 26.9836 15.7903 26.8343C15.5487 26.6849 15.3493 26.4763 15.2109 26.2282C15.0726 25.9801 15 25.7007 15 25.4167V18.7117L13.655 19.25L12.4167 16.155L16.0475 14.7025C16.3003 14.6013 16.5741 14.5635 16.8449 14.5926C17.1157 14.6216 17.3752 14.7174 17.6008 14.87Z"
                                    fill="black" fill-opacity="0.25" />
                            </svg>
                            View and edit patient information
                        </span>
                    </div>
                    <div class="flex space-x-4">
                        <div class="flex flex-col items-center mt-2">
                            <button id="printRecordBtn" onclick="printPatientRecord()" class="btn-export text-lg px-8 py-3 font-normal gap-2">
                                <svg width="35" height="35" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.1253 6.75H18.75V3.75C18.75 3.55109 18.671 3.36032 18.5303 3.21967C18.3897 3.07902 18.1989 3 18 3H6C5.80109 3 5.61032 3.07902 5.46967 3.21967C5.32902 3.36032 5.25 3.55109 5.25 3.75V6.75H3.87469C2.565 6.75 1.5 7.75969 1.5 9V16.5C1.5 16.6989 1.57902 16.8897 1.71967 17.0303C1.86032 17.171 2.05109 17.25 2.25 17.25H5.25V20.25C5.25 20.4489 5.32902 20.6397 5.46967 20.7803C5.61032 20.921 5.80109 21 6 21H18C18.1989 21 18.3897 20.921 18.5303 20.7803C18.671 20.6397 18.75 20.4489 18.75 20.25V17.25H21.75C21.9489 17.25 22.1397 17.171 22.2803 17.0303C22.421 16.8897 22.5 16.6989 22.5 16.5V9C22.5 7.75969 21.435 6.75 20.1253 6.75ZM6.75 4.5H17.25V6.75H6.75V4.5ZM17.25 19.5H6.75V15H17.25V19.5ZM21 15.75H18.75V14.25C18.75 14.0511 18.671 13.8603 18.5303 13.7197C18.3897 13.579 18.1989 13.5 18 13.5H6C5.80109 13.5 5.61032 13.579 5.46967 13.7197C5.32902 13.8603 5.25 14.0511 5.25 14.25V15.75H3V9C3 8.58656 3.39281 8.25 3.87469 8.25H20.1253C20.6072 8.25 21 8.58656 21 9V15.75ZM18.75 10.875C18.75 11.0975 18.684 11.315 18.5604 11.5C18.4368 11.685 18.2611 11.8292 18.0555 11.9144C17.85 11.9995 17.6238 12.0218 17.4055 11.9784C17.1873 11.935 16.9868 11.8278 16.8295 11.6705C16.6722 11.5132 16.565 11.3127 16.5216 11.0945C16.4782 10.8762 16.5005 10.65 16.5856 10.4445C16.6708 10.2389 16.815 10.0632 17 9.9396C17.185 9.81598 17.4025 9.75 17.625 9.75C17.9234 9.75 18.2095 9.86853 18.4205 10.0795C18.6315 10.2905 18.75 10.5766 18.75 10.875Z" fill="white" />
                                </svg>
                                Print Patient Records
                            </button>
                        </div>
                        <div class="flex flex-col items-center">
                            <button id="saveMedicalBtn" type="button" onclick="saveMedicalInformation()"
                                class="btn-save-medical px-8 py-5 text-lg gap-2">
                                <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M29.9838 10.3438L25.1562 5.51622C24.9539 5.31221 24.7129 5.15046 24.4475 5.04038C24.182 4.93031 23.8973 4.87409 23.61 4.87501H6.5625C5.98234 4.87501 5.42594 5.10548 5.0157 5.51571C4.60547 5.92595 4.375 6.48235 4.375 7.06251V28.9375C4.375 29.5177 4.60547 30.0741 5.0157 30.4843C5.42594 30.8945 5.98234 31.125 6.5625 31.125H28.4375C29.0177 31.125 29.5741 30.8945 29.9843 30.4843C30.3945 30.0741 30.625 29.5177 30.625 28.9375V11.89C30.6259 11.6027 30.5697 11.318 30.4596 11.0525C30.3495 10.7871 30.1878 10.5462 29.9838 10.3438ZM22.9688 28.9375H12.0312V21.2813H22.9688V28.9375ZM28.4375 28.9375H25.1562V21.2813C25.1562 20.7011 24.9258 20.1447 24.5155 19.7345C24.1053 19.3242 23.5489 19.0938 22.9688 19.0938H12.0312C11.4511 19.0938 10.8947 19.3242 10.4845 19.7345C10.0742 20.1447 9.84375 20.7011 9.84375 21.2813V28.9375H6.5625V7.06251H23.61L28.4375 11.89V28.9375ZM21.875 10.3438C21.875 10.6338 21.7598 10.912 21.5546 11.1172C21.3495 11.3223 21.0713 11.4375 20.7812 11.4375H13.125C12.8349 11.4375 12.5567 11.3223 12.3516 11.1172C12.1465 10.912 12.0312 10.6338 12.0312 10.3438C12.0312 10.0537 12.1465 9.77548 12.3516 9.57036C12.5567 9.36525 12.8349 9.25001 13.125 9.25001H20.7812C21.0713 9.25001 21.3495 9.36525 21.5546 9.57036C21.7598 9.77548 21.875 10.0537 21.875 10.3438Z" fill="white" />
                                </svg>
                                Save All Information
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-[100]"
        style="display:none; opacity:0; transition:opacity 0.3s;">
        <div class="bg-white rounded-[6px] shadow-2xl max-w-md w-full p-8 text-center">
            <div class='mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4'><i
                    class='fas fa-check text-green-600 text-2xl'></i></div>
            <h3 class='text-xl font-semibold mb-2 text-green-700'>Success</h3>
            <div id='successModalMessage' class='mb-6 text-gray-700'>Record saved successfully!</div>
            <button onclick='hideSuccessModal()'
                class='px-8 py-3 bg-green-600 text-white rounded-full hover:bg-green-700 transition font-medium'>OK</button>
        </div>
    </div>

    <div id="presentPregnantModal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 modal"
        style="display:none;">

        <div class="bg-white rounded-[6px] shadow-2xl w-full max-w-7xl h-[92vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="sticky top-0 z-20 bg-[#2563EB] px-10 py-6 flex items-center">
                <h3 class="text-xl font-medium flex gap-3 text-center w-full items-center text-white">
                    <i class="fas fa-female mr-2"></i>Present Pregnant Record
                </h3>
                <button onclick="closePresentPregnantModal()" class="modal-close-btn"><i
                        class="fas fa-times"></i></button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-16">
                <form id="presentPregnantForm" method="POST">
                    <!-- Basic Information -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-female mr-2"></i>Present Pregnant Record Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <input type="hidden" name="patient_id" value="">

                            <!-- Required Fields -->
                            <div>
                                <label class="form-label-modal">Birth Plan <span class="text-red-500">*</span></label>
                                <input type="text" name="birth_plan" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Nutrition/Breastfeeding <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="nutrition_breastfeeding" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Family Planning <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="family_planning" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">TT Vaccination <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="tt_vaccination" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Iron & Folic Acid <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="iron_folic" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Vitamin A <span class="text-red-500">*</span></label>
                                <input type="text" name="vitamin_a" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Prenatal Schedule <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="prenatal_schedule" class="form-input-modal" required>
                            </div>
                            <div>
                                <label class="form-label-modal">Visit Notes</label>
                                <textarea name="visit_notes" class="form-input-modal" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="form-label-modal">Referrals</label>
                                <input type="text" name="referrals" class="form-input-modal">
                            </div>
                        </div>
                    </div>

                    <!-- Add ALL other sections with proper form field names -->
                    <!-- Obstetric and Gynecologic History -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-baby-carriage mr-2"></i>Obstetric and Gynecologic History
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Gravidity</label>
                                <input type="number" name="gravidity" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Parity</label>
                                <input type="number" name="parity" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Previous Pregnancy Outcomes</label>
                                <input type="text" name="prev_outcomes" class="form-input-modal"
                                    placeholder="Miscarriages, stillbirths, etc.">
                            </div>
                            <div>
                                <label class="form-label-modal">Menstrual History (LMP)</label>
                                <input type="date" name="lmp" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Cycle Regularity</label>
                                <input type="text" name="cycle_regularity" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Contraceptive History</label>
                                <input type="text" name="contraceptive_history" class="form-input-modal">
                            </div>
                        </div>
                    </div>

                    <!-- Medical and Family History -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-notes-medical mr-2"></i>Medical and Family History
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Past Illnesses</label>
                                <input type="text" name="past_illnesses" class="form-input-modal"
                                    placeholder="Hypertension, diabetes, etc.">
                            </div>
                            <div>
                                <label class="form-label-modal">Allergies</label>
                                <input type="text" name="allergies" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Family History</label>
                                <input type="text" name="family_history" class="form-input-modal"
                                    placeholder="Hereditary diseases">
                            </div>
                        </div>
                    </div>

                    <!-- Current Pregnancy Information -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-baby mr-2"></i>Current Pregnancy Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Estimated Date of Delivery (EDD)</label>
                                <input type="date" name="edd" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Gestational Age at First Visit</label>
                                <input type="text" name="gestational_age" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Pregnancy Risk Assessment</label>
                                <input type="text" name="risk_assessment" class="form-input-modal"
                                    placeholder="High-risk/Normal">
                            </div>
                            <div>
                                <label class="form-label-modal">Danger Signs</label>
                                <input type="text" name="danger_signs" class="form-input-modal"
                                    placeholder="Bleeding, headache, etc.">
                            </div>
                        </div>
                    </div>

                    <!-- Physical Examination Records -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-stethoscope mr-2"></i>Physical Examination Records
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Blood Pressure</label>
                                <input type="text" name="bp" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Heart Rate</label>
                                <input type="text" name="hr" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Respiratory Rate</label>
                                <input type="text" name="rr" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Temperature</label>
                                <input type="text" name="temperature" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Weight</label>
                                <input type="text" name="weight" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Height</label>
                                <input type="text" name="height" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Fundal Height</label>
                                <input type="text" name="fundal_height" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Fetal Heart Tones</label>
                                <input type="text" name="fetal_heart_tones" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Edema</label>
                                <input type="text" name="edema" class="form-input-modal"
                                    placeholder="Swelling in hands/feet">
                            </div>
                        </div>
                    </div>

                    <!-- Laboratory and Diagnostic Results -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-vials mr-2"></i>Laboratory and Diagnostic Results
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Hemoglobin/Hematocrit</label>
                                <input type="text" name="hemoglobin" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Urinalysis</label>
                                <input type="text" name="urinalysis" class="form-input-modal"
                                    placeholder="Protein, sugar, infection">
                            </div>
                            <div>
                                <label class="form-label-modal">Blood Typing & Rh Factor</label>
                                <input type="text" name="blood_typing" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Syphilis Test (VDRL/RPR)</label>
                                <input type="text" name="syphilis_test" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">HIV Test (with consent)</label>
                                <input type="text" name="hiv_test" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Hepatitis B Screening</label>
                                <input type="text" name="hepatitis_b" class="form-input-modal">
                            </div>
                            <div>
                                <label class="form-label-modal">Fasting Blood Sugar</label>
                                <input type="text" name="fbs" class="form-input-modal">
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Preparedness -->
                    <div class="bg-white my-10">
                        <h3
                            class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>Emergency Preparedness
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label-modal">Emergency Preparedness</label>
                                <input type="text" name="emergency_prep" class="form-input-modal"
                                    placeholder="Referral hospital, transport plan">
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-white border-t border-blue-100 px-10 py-6 flex justify-end">
                <button type="button" onclick="closePresentPregnantModal()"
                    class="px-6 py-4 rounded-full border border-[#2563EB] text-[#2563EB] hover:bg-gray-200 font-medium mr-3">Cancel</button>
                <button type="button" onclick="submitPresentPregnantForm(event)" id="savePregnantRecordBtn"
                    class="px-8 py-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-medium shadow">
                    <i class="fas fa-save mr-2"></i>Save Record
                </button>
            </div>
        </div>
    </div>

    <!-- Export Modal (Warm Blue & White, Improved UX) -->
    <div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal" style="display: none;">
        <div class="bg-white rounded-md shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Sticky Header - Warm Blue -->
            <div class="sticky top-0 z-20 px-10 py-10 flex items-center justify-center">
                <h3 class="text-2xl font-medium border-b-2 border-gray-300 w-full pb-6 flex items-center gap-3">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24.7017 4.59869L9.43807 1.90338C8.94841 1.81722 8.44458 1.92905 8.03737 2.2143C7.63016 2.49955 7.3529 2.93485 7.26659 3.42448L3.78026 23.2292C3.73762 23.4718 3.7432 23.7204 3.7967 23.9609C3.85019 24.2014 3.95054 24.4289 4.09202 24.6306C4.2335 24.8322 4.41333 25.004 4.62124 25.1362C4.82914 25.2683 5.06104 25.3582 5.30369 25.4006L20.5674 28.096C20.8101 28.1388 21.0588 28.1333 21.2994 28.0799C21.54 28.0265 21.7677 27.9262 21.9695 27.7847C22.1713 27.6432 22.3432 27.4633 22.4754 27.2553C22.6077 27.0473 22.6976 26.8153 22.74 26.5725L26.2264 6.76784C26.3118 6.27802 26.1991 5.77434 25.9132 5.36756C25.6273 4.96078 25.1915 4.68422 24.7017 4.59869ZM20.8908 26.2491L5.62596 23.5538L9.11229 3.74909L24.376 6.4444L20.8908 26.2491ZM10.4705 6.84518C10.5139 6.60046 10.6527 6.383 10.8565 6.2406C11.0602 6.0982 11.3121 6.04252 11.5568 6.0858L21.2834 7.8026C21.5145 7.8431 21.7221 7.96882 21.8651 8.15493C22.008 8.34104 22.076 8.574 22.0555 8.80779C22.0351 9.04158 21.9277 9.25919 21.7546 9.41763C21.5814 9.57607 21.3552 9.66382 21.1205 9.66354C21.0655 9.66346 21.0106 9.65876 20.9564 9.64948L11.2299 7.93151C10.9851 7.88808 10.7677 7.74926 10.6253 7.54555C10.4829 7.34184 10.4272 7.08992 10.4705 6.84518ZM9.82127 10.5389C9.84264 10.4177 9.88769 10.3018 9.95386 10.1979C10.02 10.094 10.106 10.0042 10.2069 9.9336C10.3078 9.86298 10.4216 9.81292 10.5418 9.78628C10.662 9.75965 10.7863 9.75696 10.9076 9.77838L20.6342 11.4964C20.867 11.5353 21.0765 11.6606 21.2209 11.8472C21.3654 12.0339 21.4341 12.2682 21.4134 12.5033C21.3927 12.7384 21.284 12.957 21.1092 13.1156C20.9343 13.2741 20.7061 13.3608 20.4701 13.3585C20.4147 13.3586 20.3593 13.3535 20.3049 13.3432L10.5783 11.6264C10.3338 11.5825 10.1167 11.4432 9.97475 11.2393C9.8328 11.0354 9.7776 10.7835 9.82127 10.5389ZM9.17088 14.2315C9.21512 13.9874 9.35429 13.7708 9.5579 13.6292C9.76152 13.4875 10.013 13.4323 10.2572 13.4756L15.1181 14.3299C15.3492 14.3704 15.5567 14.4961 15.6997 14.682C15.8426 14.868 15.9107 15.1008 15.8904 15.3345C15.87 15.5682 15.7629 15.7858 15.59 15.9444C15.4171 16.1029 15.191 16.1909 14.9564 16.1909C14.9014 16.1909 14.8466 16.1862 14.7924 16.1768L9.92909 15.3178C9.68458 15.2741 9.46741 15.1352 9.32525 14.9315C9.1831 14.7278 9.12758 14.4761 9.17088 14.2315Z" fill="#3C96E1" />
                    </svg>
                    <span style="color: #387EC3;">Export Patient Records</span>
                </h3>
                <button onclick="closeExportModal()" class="text-white hover:text-gray-200 text-2xl transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Scrollable Content - White -->
            <div class="px-8 bg-white flex-1 overflow-y-auto">
                <!-- Export All Records Section -->
                <div class="mb-10">
                    <h4 class="text-lg font-semibold mb-2 flex items-center gap-2" style="color: #515151;">
                        Export Patient Records
                    </h4>
                    <p class="text-[#666666] text-base mb-6">Download all accessible patient records in your preferred format.</p>
                    <div class="flex w-full gap-4">
                        <!-- Excel Export Button (Green) -->
                        <button onclick="exportAllRecords('excel')"
                            class="w-1/2 px-6 py-4 rounded-md bg-[#3C96E1] transition-all group cursor-pointer flex justify-center items-center gap-4 font-medium">
                            <div class="flex flex-col md:flex-row items-center gap-4">
                                <div>
                                    <h5 class="font-medium text-white text-lg">Export All Records as Excel</h5>
                                </div>
                                <div>
                                    <svg width="35" height="35" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.6998 4.59869L9.43612 1.90338C8.94646 1.81722 8.44263 1.92905 8.03541 2.2143C7.6282 2.49955 7.35095 2.93485 7.26463 3.42448L3.7783 23.2292C3.73566 23.4718 3.74125 23.7204 3.79474 23.9609C3.84824 24.2014 3.94859 24.4289 4.09007 24.6306C4.23155 24.8322 4.41138 25.004 4.61928 25.1362C4.82719 25.2683 5.05909 25.3582 5.30174 25.4006L20.5654 28.096C20.8081 28.1388 21.0569 28.1333 21.2975 28.0799C21.5381 28.0265 21.7658 27.9262 21.9676 27.7847C22.1694 27.6432 22.3413 27.4633 22.4735 27.2553C22.6057 27.0473 22.6956 26.8153 22.7381 26.5725L26.2244 6.76784C26.3098 6.27802 26.1972 5.77434 25.9113 5.36756C25.6254 4.96078 25.1896 4.68422 24.6998 4.59869ZM20.8888 26.2491L5.62401 23.5538L9.11033 3.74909L24.374 6.4444L20.8888 26.2491ZM10.4685 6.84518C10.512 6.60046 10.6508 6.383 10.8545 6.2406C11.0582 6.0982 11.3101 6.04252 11.5549 6.0858L21.2814 7.8026C21.5126 7.8431 21.7202 7.96882 21.8631 8.15493C22.0061 8.34104 22.0741 8.574 22.0536 8.80779C22.0331 9.04158 21.9257 9.25919 21.7526 9.41763C21.5795 9.57607 21.3532 9.66382 21.1185 9.66354C21.0636 9.66346 21.0087 9.65876 20.9545 9.64948L11.2279 7.93151C10.9832 7.88808 10.7657 7.74926 10.6233 7.54555C10.4809 7.34184 10.4253 7.08992 10.4685 6.84518ZM9.81932 10.5389C9.84069 10.4177 9.88574 10.3018 9.9519 10.1979C10.0181 10.094 10.104 10.0042 10.2049 9.9336C10.3058 9.86298 10.4196 9.81292 10.5398 9.78628C10.6601 9.75965 10.7844 9.75696 10.9056 9.77838L20.6322 11.4964C20.865 11.5353 21.0745 11.6606 21.219 11.8472C21.3634 12.0339 21.4322 12.2682 21.4114 12.5033C21.3907 12.7384 21.2821 12.957 21.1072 13.1156C20.9324 13.2741 20.7042 13.3608 20.4681 13.3585C20.4127 13.3586 20.3574 13.3535 20.3029 13.3432L10.5764 11.6264C10.3318 11.5825 10.1147 11.4432 9.97279 11.2393C9.83085 11.0354 9.77565 10.7835 9.81932 10.5389ZM9.16893 14.2315C9.21317 13.9874 9.35234 13.7708 9.55595 13.6292C9.75956 13.4875 10.011 13.4323 10.2553 13.4756L15.1162 14.3299C15.3473 14.3704 15.5547 14.4961 15.6977 14.682C15.8407 14.868 15.9087 15.1008 15.8884 15.3345C15.8681 15.5682 15.7609 15.7858 15.588 15.9444C15.4151 16.1029 15.1891 16.1909 14.9545 16.1909C14.8995 16.1909 14.8446 16.1862 14.7904 16.1768L9.92713 15.3178C9.68263 15.2741 9.46546 15.1352 9.3233 14.9315C9.18114 14.7278 9.12562 14.4761 9.16893 14.2315Z" fill="white"/>
</svg>

                                </div>
                            </div>
                        </button>
                        <!-- Specific Record Button (Blue) -->
                        <button onclick="openManualSelectionModal()"
                            class="w-1/2 rounded-md transition-all group cursor-pointer flex justify-center items-center gap-4 px-6 py-4" style="background-color: #3C96E14D;">
                            <div class="flex flex-col md:flex-row items-center gap-4">
                                <div>
                                    <h5 class="font-medium text-[#3C96E1] text-lg">Specific Record Selection</h5>
                                </div>
                                <div>
                                    <svg width="35" height="35" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14.5899 21.6912C14.5114 21.6128 14.4492 21.5198 14.4067 21.4173C14.3643 21.3149 14.3424 21.2051 14.3424 21.0942C14.3424 20.9833 14.3643 20.8735 14.4067 20.7711C14.4492 20.6687 14.5114 20.5756 14.5899 20.4973L20.744 14.3442H4.21809C3.99431 14.3442 3.7797 14.2553 3.62147 14.0971C3.46323 13.9389 3.37434 13.7242 3.37434 13.5005C3.37434 13.2767 3.46323 13.0621 3.62147 12.9038C3.7797 12.7456 3.99431 12.6567 4.21809 12.6567H20.744L14.5899 6.50367C14.4316 6.34535 14.3426 6.13062 14.3426 5.90672C14.3426 5.68282 14.4316 5.46809 14.5899 5.30977C14.7482 5.15144 14.9629 5.0625 15.1868 5.0625C15.4107 5.0625 15.6255 5.15144 15.7838 5.30977L23.3775 12.9035C23.456 12.9819 23.5182 13.0749 23.5607 13.1774C23.6031 13.2798 23.625 13.3896 23.625 13.5005C23.625 13.6114 23.6031 13.7211 23.5607 13.8236C23.5182 13.926 23.456 14.0191 23.3775 14.0974L15.7838 21.6912C15.7054 21.7696 15.6124 21.8319 15.5099 21.8743C15.4075 21.9168 15.2977 21.9386 15.1868 21.9386C15.076 21.9386 14.9662 21.9168 14.8637 21.8743C14.7613 21.8319 14.6682 21.7696 14.5899 21.6912Z" fill="#3C96E1" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
                <!-- Info Box -->
                <div class="py-20 w-full flex justify-center items-center">
                    <!-- Full‑width image -->
                    <img
                        src="../asssets/images/export-image.png"
                        alt="Export"
                        class="w-full h-auto" />
                </div>

            </div>
            <!-- Sticky Footer -->
            <div class="bg-white p-4 sticky bottom-0 flex items-center justify-between gap-3 shadow-lg">
                <div>
                    <p class="px-6 py-3 rounded-lg font-medium" style="background-color: #0000000D; color: #51515180;">Download all accessible patient records in your preferred format.</p>
                </div>
                <button type="button" onclick="closeExportModal()"
                    class="px-6 py-3 rounded-lg text-[#3C96E1] text-lg hover:bg-[#357ABD] transition font-medium" style="background-color: #3C96E14D;">
                    <i class="fas fa-times mr-2 "></i>Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Manual Selection Modal (Warm Blue & White) with Search and Pagination -->
    <div id="manualSelectionModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal"
        style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Sticky Header -->
            <div class="sticky top-0 z-20 px-10 py-8 flex items-center justify-between bg-white">
                <h3 class="text-2xl font-medium flex items-center gap-3">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.6998 4.59869L9.43612 1.90338C8.94646 1.81722 8.44263 1.92905 8.03541 2.2143C7.6282 2.49955 7.35095 2.93485 7.26463 3.42448L3.7783 23.2292C3.73566 23.4718 3.74125 23.7204 3.79474 23.9609C3.84824 24.2014 3.94859 24.4289 4.09007 24.6306C4.23155 24.8322 4.41138 25.004 4.61928 25.1362C4.82719 25.2683 5.05909 25.3582 5.30174 25.4006L20.5654 28.096C20.8081 28.1388 21.0569 28.1333 21.2975 28.0799C21.5381 28.0265 21.7658 27.9262 21.9676 27.7847C22.1694 27.6432 22.3413 27.4633 22.4735 27.2553C22.6057 27.0473 22.6956 26.8153 22.7381 26.5725L26.2244 6.76784C26.3098 6.27802 26.1972 5.77434 25.9113 5.36756C25.6254 4.96078 25.1896 4.68422 24.6998 4.59869ZM20.8888 26.2491L5.62401 23.5538L9.11033 3.74909L24.374 6.4444L20.8888 26.2491ZM10.4685 6.84518C10.512 6.60046 10.6508 6.383 10.8545 6.2406C11.0582 6.0982 11.3101 6.04252 11.5549 6.0858L21.2814 7.8026C21.5126 7.8431 21.7202 7.96882 21.8631 8.15493C22.0061 8.34104 22.0741 8.574 22.0536 8.80779C22.0331 9.04158 21.9257 9.25919 21.7526 9.41763C21.5795 9.57607 21.3532 9.66382 21.1185 9.66354C21.0636 9.66346 21.0087 9.65876 20.9545 9.64948L11.2279 7.93151C10.9832 7.88808 10.7657 7.74926 10.6233 7.54555C10.4809 7.34184 10.4253 7.08992 10.4685 6.84518ZM9.81932 10.5389C9.84069 10.4177 9.88574 10.3018 9.9519 10.1979C10.0181 10.094 10.104 10.0042 10.2049 9.9336C10.3058 9.86298 10.4196 9.81292 10.5398 9.78628C10.6601 9.75965 10.7844 9.75696 10.9056 9.77838L20.6322 11.4964C20.865 11.5353 21.0745 11.6606 21.219 11.8472C21.3634 12.0339 21.4322 12.2682 21.4114 12.5033C21.3907 12.7384 21.2821 12.957 21.1072 13.1156C20.9324 13.2741 20.7042 13.3608 20.4681 13.3585C20.4127 13.3586 20.3574 13.3535 20.3029 13.3432L10.5764 11.6264C10.3318 11.5825 10.1147 11.4432 9.97279 11.2393C9.83085 11.0354 9.77565 10.7835 9.81932 10.5389ZM9.16893 14.2315C9.21317 13.9874 9.35234 13.7708 9.55595 13.6292C9.75956 13.4875 10.011 13.4323 10.2553 13.4756L15.1162 14.3299C15.3473 14.3704 15.5547 14.4961 15.6977 14.682C15.8407 14.868 15.9087 15.1008 15.8884 15.3345C15.8681 15.5682 15.7609 15.7858 15.588 15.9444C15.4151 16.1029 15.1891 16.1909 14.9545 16.1909C14.8995 16.1909 14.8446 16.1862 14.7904 16.1768L9.92713 15.3178C9.68263 15.2741 9.46546 15.1352 9.3233 14.9315C9.18114 14.7278 9.12562 14.4761 9.16893 14.2315Z" fill="#3C96E1"/>
</svg>

                    <span style="color: #387EC3;">Select Specific Records to Export</span>
                </h3>
                <!-- <button onclick="closeManualSelectionModal()" class="text-gray-500 hover:text-gray-700 text-2xl transition">
                    <i class="fas fa-times"></i>
                </button> -->
            </div>

            <!-- Scrollable Content -->
            <div class="px-10 flex-1 overflow-y-auto">
                <!-- Search Bar -->
                <div class="mb-6 gap-4 border-b-2 borde-gray-300 pb-6 flex flex-col md:flex-row items-center">

                    <!-- Back Button -->
                    <button type="button"
                        onclick="goBackToExportModal()"
                        class="flex-none inline-flex items-center px-4 py-3 rounded-md text-[#3C96E1] hover:bg-[#F0F7FF] transition font-medium" style="background-color: #3C96E14C;">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back
                    </button>

                    <!-- Search Input -->
                    <div class="relative w-full">
                        <!-- Icon -->
                        <span class="absolute inset-y-0 left-2 top-1/2 -translate-y-1/2 flex items-center pl-4">
                            <i class="fas fa-search mx-3 text-xl text-gray-400"></i>
                        </span>

                        <!-- Input -->
                        <input type="text"
                            id="exportPatientSearch"
                            placeholder="Search patients by name..."
                            class="w-full px-10 py-3 border border-[#3C96E1] rounded-md"
                            oninput="debounceSearchExportPatients()" />
                    </div>

                </div>


                <!-- Selection Controls -->
                <div class="mb-4">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <p class="text-sm text-[#666666]">
                            <h1 class="font-medium text-xl">Record Selection</h1>
                            <span class="font-normal text-gray-500 text-base">Selected Record :</span>
                            <span id="selectedCount" class="font-medium text-lg text-[#3C96E1]">0</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1 cursor-pointer px-4 rounded-lg hover:bg-[#D4E3F7] transition">
                                <input type="checkbox" id="selectAllPatients"
                                    class="patient-checkbox select-all-checkbox w-5 h-5 accent-[#4A90E2]"
                                    onchange="toggleAllPatients(this)">
                                <span class="font-normal text-gray-500 text-lg">Select All on Current Page</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Patients Table -->
                <div class="overflow-hidden">
                    <div class="scrollable-table-container" style="max-height: 350px;">
                        <table class="patient-table w-full">
                            <thead class="bg-[#F8FBFF] sticky top-0">
                                <tr>
                                    <th class="checkbox-column w-12 text-center py-3 px-2"></th>
                                    <th class="px-6 py-3 text-left font-bold text-[#2E5C8A]">Name</th>
                                    <th class="px-6 py-3 text-left font-bold text-[#2E5C8A]">Age</th>
                                    <th class="px-6 py-3 text-left font-bold text-[#2E5C8A]">Last Check-up</th>
                                </tr>
                            </thead>
                            <tbody id="patientSelectionList">
                                <!-- Populated by JavaScript -->
                                <tr>
                                    <td colspan="4" class="text-center py-8">
                                        <div class="flex justify-center items-center">
                                            <div class="loading-spinner mr-3"></div>
                                            <span class="text-gray-600">Loading patients...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination will be inserted here dynamically -->
                <div id="exportPagination" class="flex justify-center gap-2 mb-6"></div>

                <div class="mb-2">
                    <p class="font-normal text-lg text-gray-500">Download all accessible patient records in your preferred format.</p>
                </div>
            </div>

            <!-- Sticky Footer -->
            <div class="w-full px-10 py-6 sticky bottom-0 bg-white flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Export Excel Button - Green -->
                    <button type="button" onclick="confirmManualExport('excel')"
                        class="inline-flex items-center px-6 py-4 gap-2 rounded-lg text-white font-medium shadow-md hover:shadow-lg transition-all duration-200"
                        style="background-color: #10B981; border: none;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21 9.75C21 9.94891 20.921 10.1397 20.7803 10.2803C20.6397 10.421 20.4489 10.5 20.25 10.5C20.0511 10.5 19.8603 10.421 19.7197 10.2803C19.579 10.1397 19.5 9.94891 19.5 9.75V5.56125L13.2816 11.7806C13.1408 11.9214 12.95 12.0004 12.7509 12.0004C12.5519 12.0004 12.361 11.9214 12.2203 11.7806C12.0796 11.6399 12.0005 11.449 12.0005 11.25C12.0005 11.051 12.0796 10.8601 12.2203 10.7194L18.4387 4.5H14.25C14.0511 4.5 13.8603 4.42098 13.7197 4.28033C13.579 4.13968 13.5 3.94891 13.5 3.75C13.5 3.55109 13.579 3.36032 13.7197 3.21967C13.8603 3.07902 14.0511 3 14.25 3H20.25C20.4489 3 20.6397 3.07902 20.7803 3.21967C20.921 3.36032 21 3.55109 21 3.75V9.75ZM17.25 12C17.0511 12 16.8603 12.079 16.7197 12.2197C16.579 12.3603 16.5 12.5511 16.5 12.75V19.5H4.5V7.5H11.25C11.4489 7.5 11.6397 7.42098 11.7803 7.28033C11.921 7.13968 12 6.94891 12 6.75C12 6.55109 11.921 6.36032 11.7803 6.21967C11.6397 6.07902 11.4489 6 11.25 6H4.5C4.10218 6 3.72064 6.15804 3.43934 6.43934C3.15804 6.72064 3 7.10218 3 7.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H16.5C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5V12.75C18 12.5511 17.921 12.3603 17.7803 12.2197C17.6397 12.079 17.4489 12 17.25 12Z" fill="white"/>
</svg>

                        Export as Excel
                    </button>

                    <!-- Export PDF Button - Red -->
                    <button type="button" onclick="confirmManualExport('pdf')"
                        class="inline-flex items-center px-6 py-4 gap-2 rounded-lg text-white font-medium shadow-md hover:shadow-lg transition-all duration-200"
                        style="background-color: #DC2626; border: none;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21 9.75C21 9.94891 20.921 10.1397 20.7803 10.2803C20.6397 10.421 20.4489 10.5 20.25 10.5C20.0511 10.5 19.8603 10.421 19.7197 10.2803C19.579 10.1397 19.5 9.94891 19.5 9.75V5.56125L13.2816 11.7806C13.1408 11.9214 12.95 12.0004 12.7509 12.0004C12.5519 12.0004 12.361 11.9214 12.2203 11.7806C12.0796 11.6399 12.0005 11.449 12.0005 11.25C12.0005 11.051 12.0796 10.8601 12.2203 10.7194L18.4387 4.5H14.25C14.0511 4.5 13.8603 4.42098 13.7197 4.28033C13.579 4.13968 13.5 3.94891 13.5 3.75C13.5 3.55109 13.579 3.36032 13.7197 3.21967C13.8603 3.07902 14.0511 3 14.25 3H20.25C20.4489 3 20.6397 3.07902 20.7803 3.21967C20.921 3.36032 21 3.55109 21 3.75V9.75ZM17.25 12C17.0511 12 16.8603 12.079 16.7197 12.2197C16.579 12.3603 16.5 12.5511 16.5 12.75V19.5H4.5V7.5H11.25C11.4489 7.5 11.6397 7.42098 11.7803 7.28033C11.921 7.13968 12 6.94891 12 6.75C12 6.55109 11.921 6.36032 11.7803 6.21967C11.6397 6.07902 11.4489 6 11.25 6H4.5C4.10218 6 3.72064 6.15804 3.43934 6.43934C3.15804 6.72064 3 7.10218 3 7.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H16.5C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5V12.75C18 12.5511 17.921 12.3603 17.7803 12.2197C17.6397 12.079 17.4489 12 17.25 12Z" fill="white"/>
</svg>

                        Export as PDF
                    </button>
                </div>
                <div class="flex gap-3">
                    <!-- Cancel Button -->
                    <button type="button" onclick="closeManualSelectionModal()"
                        class="px-6 py-3 rounded-lg border border-[#3C96E1] text-[#3C96E1] hover:bg-[#F8FBFF] transition font-medium bg-white">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Note Modal -->
    <div id="consultationNoteModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal"
        style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Sticky Header -->
            <div class="sticky top-0 z-20 px-10 py-6 flex items-center">
                <h3 class="text-2xl font-sm flex mt-3 border-b-2 border-gray-100 pb-6  text-center w-full items-center text-white gap-2">
                    <svg width="40" height="40" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.6998 4.59869L9.43612 1.90338C8.94646 1.81722 8.44263 1.92905 8.03542 2.2143C7.6282 2.49955 7.35095 2.93485 7.26463 3.42448L3.7783 23.2292C3.73566 23.4718 3.74125 23.7204 3.79474 23.9609C3.84824 24.2014 3.94859 24.4289 4.09007 24.6306C4.23155 24.8322 4.41138 25.004 4.61928 25.1362C4.82719 25.2683 5.05909 25.3582 5.30174 25.4006L20.5654 28.096C20.8081 28.1388 21.0569 28.1333 21.2975 28.0799C21.5381 28.0265 21.7658 27.9262 21.9676 27.7847C22.1694 27.6432 22.3413 27.4633 22.4735 27.2553C22.6057 27.0473 22.6956 26.8153 22.7381 26.5725L26.2244 6.76784C26.3098 6.27802 26.1972 5.77434 25.9113 5.36756C25.6254 4.96078 25.1896 4.68422 24.6998 4.59869ZM20.8889 26.2491L5.62401 23.5538L9.11034 3.74909L24.374 6.4444L20.8889 26.2491ZM10.4685 6.84518C10.512 6.60046 10.6508 6.383 10.8545 6.2406C11.0582 6.0982 11.3101 6.04252 11.5549 6.0858L21.2814 7.8026C21.5126 7.8431 21.7202 7.96882 21.8631 8.15493C22.0061 8.34104 22.0741 8.574 22.0536 8.80779C22.0331 9.04158 21.9257 9.25919 21.7526 9.41763C21.5795 9.57607 21.3532 9.66382 21.1185 9.66354C21.0636 9.66346 21.0087 9.65876 20.9545 9.64948L11.2279 7.93151C10.9832 7.88808 10.7657 7.74926 10.6233 7.54555C10.4809 7.34184 10.4253 7.08992 10.4685 6.84518ZM9.81932 10.5389C9.84069 10.4177 9.88574 10.3018 9.9519 10.1979C10.0181 10.094 10.104 10.0042 10.2049 9.9336C10.3058 9.86298 10.4196 9.81292 10.5398 9.78628C10.6601 9.75965 10.7844 9.75696 10.9056 9.77838L20.6322 11.4964C20.865 11.5353 21.0745 11.6606 21.219 11.8472C21.3634 12.0339 21.4322 12.2682 21.4114 12.5033C21.3907 12.7384 21.2821 12.957 21.1072 13.1156C20.9324 13.2741 20.7042 13.3608 20.4681 13.3585C20.4127 13.3586 20.3574 13.3535 20.3029 13.3432L10.5764 11.6264C10.3318 11.5825 10.1147 11.4432 9.97279 11.2393C9.83085 11.0354 9.77565 10.7835 9.81932 10.5389ZM9.16893 14.2315C9.21317 13.9874 9.35234 13.7708 9.55595 13.6292C9.75956 13.4875 10.011 13.4323 10.2553 13.4756L15.1162 14.3299C15.3473 14.3704 15.5547 14.4961 15.6977 14.682C15.8407 14.868 15.9087 15.1008 15.8884 15.3345C15.8681 15.5682 15.7609 15.7858 15.588 15.9444C15.4151 16.1029 15.1891 16.1909 14.9545 16.1909C14.8995 16.1909 14.8446 16.1862 14.7904 16.1768L9.92713 15.3178C9.68263 15.2741 9.46546 15.1352 9.3233 14.9315C9.18115 14.7278 9.12563 14.4761 9.16893 14.2315Z" fill="#3C96E1"/>
</svg>


                    <span style="color: #387EC3;" id="consultationNoteTitle">Add Consultation Note</span>
                </h3>
                <button onclick="closeConsultationNoteModal()" class="text-gray-500 hover:text-gray-500 text-3xl transition absolute right-8 top-6">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="px-10 py-4 bg-gray-50 flex-1 overflow-y-auto">
                <div id="consultationNoteContent">
                    <!-- Add Note Form -->
                    <form id="addNoteForm" method="POST" action="">
                        <input type="hidden" name="patient_id" id="notePatientId" value="">
                        <div class="space-y-6">
                            <div>
                                <label for="doctor_name" class="block text-gray-700 mb-2 font-medium">
                                    Doctor's Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="doctor_name" name="doctor_name"
                                    placeholder="Enter doctor's full name"
                                    class="w-full px-4 py-3 border border-[#85ccfb] rounded-lg"
                                    required>
                                <!-- <p class="text-sm text-gray-500 mt-1">"Dr." will be added automatically.</p> -->
                            </div>

                            <div>
                                <label for="consultation_date" class="block text-gray-500 mb-2 font-medium">
                                    Consultation Date (Auto Filled)
                                </label>
                                <input type="date" id="consultation_date" name="consultation_date"
                                    value="<?= date('Y-m-d') ?>"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-100 text-gray-700"
                                    readonly aria-readonly="true" required>
                            </div>
                            <div>
    <label for="next_consultation_date" class="block text-gray-700 mb-2 font-medium">
        Next Consultation Date <span class="text-red-500">*</span>
    </label>

    <div class="relative">
        <input 
            type="date" 
            id="next_consultation_date" 
            name="next_consultation_date"
            class="w-full px-4 py-3 pr-12 border border-[#a4dafd] rounded-lg"
        >

        <!-- Icon aligned with input padding -->
        <span onclick="document.getElementById('next_consultation_date').showPicker()"
              class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500">
            <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.3125 3.125H17.9688V2.34375C17.9688 2.13655 17.8864 1.93784 17.7399 1.79132C17.5934 1.64481 17.3947 1.5625 17.1875 1.5625C16.9803 1.5625 16.7816 1.64481 16.6351 1.79132C16.4886 1.93784 16.4062 2.13655 16.4062 2.34375V3.125H8.59375V2.34375C8.59375 2.13655 8.51144 1.93784 8.36493 1.79132C8.21841 1.64481 8.0197 1.5625 7.8125 1.5625C7.6053 1.5625 7.40659 1.64481 7.26007 1.79132C7.11356 1.93784 7.03125 2.13655 7.03125 2.34375V3.125H4.6875C4.2731 3.125 3.87567 3.28962 3.58265 3.58265C3.28962 3.87567 3.125 4.2731 3.125 4.6875V20.3125C3.125 20.7269 3.28962 21.1243 3.58265 21.4174C3.87567 21.7104 4.2731 21.875 4.6875 21.875H20.3125C20.7269 21.875 21.1243 21.7104 21.4174 21.4174C21.7104 21.1243 21.875 20.7269 21.875 20.3125V4.6875C21.875 4.2731 21.7104 3.87567 21.4174 3.58265C21.1243 3.28962 20.7269 3.125 20.3125 3.125ZM7.03125 4.6875V5.46875C7.03125 5.67595 7.11356 5.87466 7.26007 6.02118C7.40659 6.16769 7.6053 6.25 7.8125 6.25C8.0197 6.25 8.21841 6.16769 8.36493 6.02118C8.51144 5.87466 8.59375 5.67595 8.59375 5.46875V4.6875H16.4062V5.46875C16.4062 5.67595 16.4886 5.87466 16.6351 6.02118C16.7816 6.16769 16.9803 6.25 17.1875 6.25C17.3947 6.25 17.5934 6.16769 17.7399 6.02118C17.8864 5.87466 17.9688 5.67595 17.9688 5.46875V4.6875H20.3125V7.8125H4.6875V4.6875H7.03125ZM20.3125 20.3125H4.6875V9.375H20.3125V20.3125ZM13.6719 12.8906C13.6719 13.1224 13.6031 13.349 13.4744 13.5417C13.3456 13.7344 13.1626 13.8846 12.9485 13.9733C12.7343 14.062 12.4987 14.0852 12.2714 14.04C12.0441 13.9948 11.8352 13.8832 11.6714 13.7193C11.5075 13.5554 11.3959 13.3466 11.3506 13.1192C11.3054 12.8919 11.3286 12.6563 11.4173 12.4422C11.506 12.228 11.6562 12.045 11.8489 11.9162C12.0417 11.7875 12.2682 11.7188 12.5 11.7188C12.8108 11.7188 13.1089 11.8422 13.3286 12.062C13.5484 12.2818 13.6719 12.5798 13.6719 12.8906ZM17.9688 12.8906C17.9688 13.1224 17.9 13.349 17.7713 13.5417C17.6425 13.7344 17.4595 13.8846 17.2453 13.9733C17.0312 14.062 16.7956 14.0852 16.5683 14.04C16.3409 13.9948 16.1321 13.8832 15.9682 13.7193C15.8043 13.5554 15.6927 13.3466 15.6475 13.1192C15.6023 12.8919 15.6255 12.6563 15.7142 12.4422C15.8029 12.228 15.9531 12.045 16.1458 11.9162C16.3385 11.7875 16.5651 11.7188 16.7969 11.7188C17.1077 11.7188 17.4057 11.8422 17.6255 12.062C17.8453 12.2818 17.9688 12.5798 17.9688 12.8906ZM9.375 16.7969C9.375 17.0286 9.30627 17.2552 9.1775 17.4479C9.04874 17.6406 8.86571 17.7908 8.65158 17.8795C8.43745 17.9682 8.20182 17.9914 7.9745 17.9462C7.74718 17.901 7.53837 17.7894 7.37448 17.6255C7.21059 17.4616 7.09898 17.2528 7.05377 17.0255C7.00855 16.7982 7.03176 16.5626 7.12045 16.3484C7.20915 16.1343 7.35935 15.9513 7.55207 15.8225C7.74478 15.6937 7.97135 15.625 8.20312 15.625C8.51393 15.625 8.812 15.7485 9.03177 15.9682C9.25154 16.188 9.375 16.4861 9.375 16.7969ZM13.6719 16.7969C13.6719 17.0286 13.6031 17.2552 13.4744 17.4479C13.3456 17.6406 13.1626 17.7908 12.9485 17.8795C12.7343 17.9682 12.4987 17.9914 12.2714 17.9462C12.0441 17.901 11.8352 17.7894 11.6714 17.6255C11.5075 17.4616 11.3959 17.2528 11.3506 17.0255C11.3054 16.7982 11.3286 16.5626 11.4173 16.3484C11.506 16.1343 11.6562 15.9513 11.8489 15.8225C12.0417 15.6937 12.2682 15.625 12.5 15.625C12.8108 15.625 13.1089 15.7485 13.3286 15.9682C13.5484 16.188 13.6719 16.4861 13.6719 16.7969ZM17.9688 16.7969C17.9688 17.0286 17.9 17.2552 17.7713 17.4479C17.6425 17.6406 17.4595 17.7908 17.2453 17.8795C17.0312 17.9682 16.7956 17.9914 16.5683 17.9462C16.3409 17.901 16.1321 17.7894 15.9682 17.6255C15.8043 17.4616 15.6927 17.2528 15.6475 17.0255C15.6023 16.7982 15.6255 16.5626 15.7142 16.3484C15.8029 16.1343 15.9531 15.9513 16.1458 15.8225C16.3385 15.6937 16.5651 15.625 16.7969 15.625C17.1077 15.625 17.4057 15.7485 17.6255 15.9682C17.8453 16.188 17.9688 16.4861 17.9688 16.7969Z" fill="#1C1C1C"/>
</svg>


        </span>
    </div>
</div>
                            <div>
                                <label for="note" class="block text-gray-700 mb-2 font-medium">
                                    Consultation Note <span class="text-red-500">*</span>
                                </label>
                                <textarea id="note" name="note" rows="8"
                                    class="w-full px-6 py-3 border border-[#85ccfb] rounded-lg"
                                    placeholder="Enter consultation notes, observations, recommendations, and treatment plans..."
                                    required></textarea>
                            </div>
                        </div>
                    </form>

                    <!-- View Notes Content -->
                    <div id="viewNotesContent" style="display: none;">
                        <div class="space-y-4" id="consultationNotesList">
                            <!-- Notes will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 bg-white">
                <div class="flex justify-between items-center">
                    <button type="button" onclick="closeConsultationNoteModal()" class="btn-gray px-6">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>

                    <!-- Add Note Button (shown when in Add mode) -->
                    <div id="addNoteActions">
                        <button type="button" onclick="saveConsultationNote()" class="btn-add-note px-6 py-6 gap-2">
                            <svg width="30" height="30" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.2899 3.21909L6.60528 1.33237C6.26252 1.27205 5.90984 1.35034 5.62479 1.55001C5.33974 1.74968 5.14567 2.05439 5.08524 2.39713L2.64481 16.2604C2.61496 16.4302 2.61887 16.6043 2.65632 16.7726C2.69377 16.9409 2.76401 17.1002 2.86305 17.2414C2.96208 17.3826 3.08796 17.5028 3.2335 17.5953C3.37903 17.6878 3.54136 17.7507 3.71122 17.7805L14.3958 19.6672C14.5657 19.6971 14.7398 19.6933 14.9082 19.6559C15.0767 19.6185 15.236 19.5483 15.3773 19.4493C15.5186 19.3502 15.6389 19.2243 15.7314 19.0787C15.824 18.9331 15.8869 18.7707 15.9166 18.6008L18.3571 4.73748C18.4169 4.39461 18.338 4.04204 18.1379 3.75729C17.9377 3.47255 17.6327 3.27895 17.2899 3.21909ZM14.6222 18.3744L3.93681 16.4876L6.37723 2.62436L17.0618 4.51108L14.6222 18.3744ZM7.32798 4.79163C7.35837 4.62032 7.45555 4.4681 7.59815 4.36842C7.74074 4.26874 7.91708 4.22977 8.08841 4.26006L14.897 5.46182C15.0588 5.49017 15.2041 5.57817 15.3042 5.70845C15.4043 5.83873 15.4518 6.0018 15.4375 6.16545C15.4232 6.3291 15.348 6.48143 15.2268 6.59234C15.1056 6.70325 14.9473 6.76467 14.783 6.76448C14.7445 6.76442 14.7061 6.76113 14.6681 6.75463L7.85954 5.55206C7.68823 5.52166 7.53601 5.42448 7.43633 5.28188C7.33666 5.13929 7.29768 4.96295 7.32798 4.79163ZM6.87352 7.37725C6.88848 7.29236 6.92002 7.21124 6.96633 7.13853C7.01264 7.06583 7.07283 7.00296 7.14345 6.95352C7.21406 6.90408 7.29373 6.86904 7.37789 6.8504C7.46205 6.83175 7.54906 6.82987 7.63395 6.84487L14.4425 8.04745C14.6055 8.0747 14.7522 8.16241 14.8533 8.29307C14.9544 8.42373 15.0025 8.58772 14.988 8.7523C14.9735 8.91688 14.8975 9.06993 14.7751 9.1809C14.6527 9.29187 14.4929 9.35258 14.3277 9.35092C14.2889 9.351 14.2502 9.34743 14.212 9.34026L7.40345 8.1385C7.23227 8.10773 7.08032 8.01027 6.98096 7.86753C6.88159 7.7248 6.84295 7.54846 6.87352 7.37725ZM6.41825 9.96206C6.44922 9.7912 6.54664 9.63959 6.68917 9.54042C6.83169 9.44125 7.00772 9.40261 7.17868 9.43295L10.5813 10.031C10.7431 10.0593 10.8883 10.1472 10.9884 10.2774C11.0885 10.4076 11.1361 10.5706 11.1219 10.7342C11.1077 10.8978 11.0326 11.0501 10.9116 11.1611C10.7906 11.272 10.6323 11.3336 10.4681 11.3336C10.4296 11.3336 10.3912 11.3303 10.3533 11.3238L6.94899 10.7225C6.77784 10.6919 6.62582 10.5946 6.52631 10.4521C6.4268 10.3095 6.38794 10.1333 6.41825 9.96206Z" fill="white" />
                            </svg>
                            Save Note
                        </button>
                    </div>

                    <!-- View Note Actions (shown when in View mode) -->
                    <div id="viewNoteActions" style="display: none;">
                        <button type="button" onclick="switchToAddNote()" class="btn-add-note px-6 py-3">
                            <i class="fas fa-plus mr-2"></i>Add New Note
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 modal"
        style="display:none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-7xl h-[92vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="sticky top-0 z-20 bg-[#2563EB] px-10 py-6 flex items-center">
                <h3 class="text-xl font-medium flex gap-3 text-center w-full items-center text-white">
                    <svg width="36" height="36" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_989_9772" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0"
                            width="44" height="44">
                            <path
                                d="M38.8125 1H4.4375C2.53902 1 1 2.53902 1 4.4375V38.8125C1 40.711 2.53902 42.25 4.4375 42.25H38.8125C40.711 42.25 42.25 40.711 42.25 38.8125V4.4375C42.25 2.53902 40.711 1 38.8125 1Z"
                                fill="white" stroke="white" stroke-width="2" stroke-linejoin="round" />
                            <path d="M21.6247 12.4585V30.7918M12.458 21.6252H30.7913" stroke="black" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </mask>
                        <g mask="url(#mask0_989_9772)">
                            <path d="M-5.875 -5.875H49.125V49.125H-5.875V-5.875Z" fill="white" />
                        </g>
                    </svg>
                    Registration For New Patient
                </h3>
                <button onclick="closeAddPatientModal()" class="modal-close-btn">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.281 18.2198C19.3507 18.2895 19.406 18.3722 19.4437 18.4632C19.4814 18.5543 19.5008 18.6519 19.5008 18.7504C19.5008 18.849 19.4814 18.9465 19.4437 19.0376C19.406 19.1286 19.3507 19.2114 19.281 19.281C19.2114 19.3507 19.1286 19.406 19.0376 19.4437C18.9465 19.4814 18.849 19.5008 18.7504 19.5008C18.6519 19.5008 18.5543 19.4814 18.4632 19.4437C18.3722 19.406 18.2895 19.3507 18.2198 19.281L12.0004 13.0607L5.78104 19.281C5.64031 19.4218 5.44944 19.5008 5.25042 19.5008C5.05139 19.5008 4.86052 19.4218 4.71979 19.281C4.57906 19.1403 4.5 18.9494 4.5 18.7504C4.5 18.5514 4.57906 18.3605 4.71979 18.2198L10.9401 12.0004L4.71979 5.78104C4.57906 5.64031 4.5 5.44944 4.5 5.25042C4.5 5.05139 4.57906 4.86052 4.71979 4.71979C4.86052 4.57906 5.05139 4.5 5.25042 4.5C5.44944 4.5 5.64031 4.57906 5.78104 4.71979L12.0004 10.9401L18.2198 4.71979C18.3605 4.57906 18.5514 4.5 18.7504 4.5C18.9494 4.5 19.1403 4.57906 19.281 4.71979C19.4218 4.86052 19.5008 5.05139 19.5008 5.25042C19.5008 5.44944 19.4218 5.64031 19.281 5.78104L13.0607 12.0004L19.281 18.2198Z" fill="white" />
                    </svg>

                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-16">
                <form method="POST" action="" id="patientForm" enctype="multipart/form-data">
                    <!-- Step 1: Personal Information -->
                    <div id="personalInfoStep" class="bg-white my-10">
                        <h3 class="text-2xl font-normal border-b border-black-100 py-6 text-[#2563EB] mb-6 gap-4 flex items-center">
                            <svg width="42" height="38" viewBox="0 0 42 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 2.06875C0.00381259 1.52162 0.222709 0.997953 0.609402 0.61087C0.996095 0.223787 1.51954 0.00436385 2.06667 0H39.6C40.7417 0 41.6667 0.927083 41.6667 2.06875V35.4312C41.6629 35.9784 41.444 36.502 41.0573 36.8891C40.6706 37.2762 40.1471 37.4956 39.6 37.5H2.06667C1.51836 37.4994 0.992702 37.2812 0.605186 36.8933C0.217671 36.5054 -2.78032e-07 35.9796 0 35.4312V2.06875ZM8.33333 25V29.1667H33.3333V25H8.33333ZM8.33333 8.33333V20.8333H20.8333V8.33333H8.33333ZM25 8.33333V12.5H33.3333V8.33333H25ZM25 16.6667V20.8333H33.3333V16.6667H25ZM12.5 12.5H16.6667V16.6667H12.5V12.5Z" fill="#2563EB" />
                            </svg>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label for="modal_full_name" class="block text-sm font-medium mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modal_full_name" name="full_name" placeholder="Enter Full Name"
                                    required class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_date_of_birth" class="block text-sm font-medium mb-2">
                                    Date of Birth <span class="text-red-500">*</span>
                                </label>
                                <div class="date-input-with-trigger">
                                    <input type="date" id="modal_date_of_birth" name="date_of_birth" required
                                        max="<?= date('Y-m-d') ?>"
                                        class="form-input-modal w-full rounded-xl border-blue-200 py-3 pl-4">
                                    <button type="button" id="modal_date_of_birth_trigger"
                                        class="date-input-trigger hover:text-[#1D4ED8]"
                                        aria-label="Choose date of birth">
                                        <svg width="50" height="50" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.375 3.75H21.5625V2.8125C21.5625 2.56386 21.4637 2.3254 21.2879 2.14959C21.1121 1.97377 20.8736 1.875 20.625 1.875C20.3764 1.875 20.1379 1.97377 19.9621 2.14959C19.7863 2.3254 19.6875 2.56386 19.6875 2.8125V3.75H10.3125V2.8125C10.3125 2.56386 10.2137 2.3254 10.0379 2.14959C9.8621 1.97377 9.62364 1.875 9.375 1.875C9.12636 1.875 8.8879 1.97377 8.71209 2.14959C8.53627 2.3254 8.4375 2.56386 8.4375 2.8125V3.75H5.625C5.12772 3.75 4.65081 3.94754 4.29917 4.29917C3.94754 4.65081 3.75 5.12772 3.75 5.625V24.375C3.75 24.8723 3.94754 25.3492 4.29917 25.7008C4.65081 26.0525 5.12772 26.25 5.625 26.25H24.375C24.8723 26.25 25.3492 26.0525 25.7008 25.7008C26.0525 25.3492 26.25 24.8723 26.25 24.375V5.625C26.25 5.12772 26.0525 4.65081 25.7008 4.29917C25.3492 3.94754 24.8723 3.75 24.375 3.75ZM8.4375 5.625V6.5625C8.4375 6.81114 8.53627 7.0496 8.71209 7.22541C8.8879 7.40123 9.12636 7.5 9.375 7.5C9.62364 7.5 9.8621 7.40123 10.0379 7.22541C10.2137 7.0496 10.3125 6.81114 10.3125 6.5625V5.625H19.6875V6.5625C19.6875 6.81114 19.7863 7.0496 19.9621 7.22541C20.1379 7.40123 20.3764 7.5 20.625 7.5C20.8736 7.5 21.1121 7.40123 21.2879 7.22541C21.4637 7.0496 21.5625 6.81114 21.5625 6.5625V5.625H24.375V9.375H5.625V5.625H8.4375ZM24.375 24.375H5.625V11.25H24.375V24.375ZM16.4062 15.4688C16.4062 15.7469 16.3238 16.0188 16.1693 16.25C16.0147 16.4813 15.7951 16.6615 15.5381 16.768C15.2812 16.8744 14.9984 16.9022 14.7257 16.848C14.4529 16.7937 14.2023 16.6598 14.0056 16.4631C13.809 16.2665 13.675 16.0159 13.6208 15.7431C13.5665 15.4703 13.5944 15.1876 13.7008 14.9306C13.8072 14.6736 13.9875 14.454 14.2187 14.2995C14.45 14.145 14.7219 14.0625 15 14.0625C15.373 14.0625 15.7306 14.2107 15.9944 14.4744C16.2581 14.7381 16.4062 15.0958 16.4062 15.4688ZM21.5625 15.4688C21.5625 15.7469 21.48 16.0188 21.3255 16.25C21.171 16.4813 20.9514 16.6615 20.6944 16.768C20.4374 16.8744 20.1547 16.9022 19.8819 16.848C19.6091 16.7937 19.3585 16.6598 19.1619 16.4631C18.9652 16.2665 18.8313 16.0159 18.777 15.7431C18.7228 15.4703 18.7506 15.1876 18.857 14.9306C18.9635 14.6736 19.1437 14.454 19.375 14.2995C19.6062 14.145 19.8781 14.0625 20.1562 14.0625C20.5292 14.0625 20.8869 14.2107 21.1506 14.4744C21.4143 14.7381 21.5625 15.0958 21.5625 15.4688ZM11.25 20.1562C11.25 20.4344 11.1675 20.7063 11.013 20.9375C10.8585 21.1688 10.6389 21.349 10.3819 21.4555C10.1249 21.5619 9.84219 21.5897 9.5694 21.5355C9.29662 21.4812 9.04605 21.3473 8.84938 21.1506C8.65271 20.954 8.51878 20.7034 8.46452 20.4306C8.41026 20.1578 8.43811 19.8751 8.54454 19.6181C8.65098 19.3611 8.83122 19.1415 9.06248 18.987C9.29374 18.8325 9.56562 18.75 9.84375 18.75C10.2167 18.75 10.5744 18.8982 10.8381 19.1619C11.1018 19.4256 11.25 19.7833 11.25 20.1562ZM16.4062 20.1562C16.4062 20.4344 16.3238 20.7063 16.1693 20.9375C16.0147 21.1688 15.7951 21.349 15.5381 21.4555C15.2812 21.5619 14.9984 21.5897 14.7257 21.5355C14.4529 21.4812 14.2023 21.3473 14.0056 21.1506C13.809 20.954 13.675 20.7034 13.6208 20.4306C13.5665 20.1578 13.5944 19.8751 13.7008 19.6181C13.8072 19.3611 13.9875 19.1415 14.2187 18.987C14.45 18.8325 14.7219 18.75 15 18.75C15.373 18.75 15.7306 18.8982 15.9944 19.1619C16.2581 19.4256 16.4062 19.7833 16.4062 20.1562ZM21.5625 20.1562C21.5625 20.4344 21.48 20.7063 21.3255 20.9375C21.171 21.1688 20.9514 21.349 20.6944 21.4555C20.4374 21.5619 20.1547 21.5897 19.8819 21.5355C19.6091 21.4812 19.3585 21.3473 19.1619 21.1506C18.9652 20.954 18.8313 20.7034 18.777 20.4306C18.7228 20.1578 18.7506 19.8751 18.857 19.6181C18.9635 19.3611 19.1437 19.1415 19.375 18.987C19.6062 18.8325 19.8781 18.75 20.1562 18.75C20.5292 18.75 20.8869 18.8982 21.1506 19.1619C21.4143 19.4256 21.5625 19.7833 21.5625 20.1562Z" fill="#3C96E1"/>
</svg>

                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="modal_age" class="block text-sm font-medium mb-2">
                                    Age (Auto-calculated)
                                </label>
                                <input type="number" id="modal_age" name="age" placeholder="0" readonly
                                    class="form-input-modal w-full rounded-xl bg-[#F0F0F0] border border-blue-200 px-4 py-3 cursor-not-allowed">
                            </div>

                            <div>
                                <label for="modal_gender" class="block text-sm font-medium mb-2">
                                    Gender <span class="text-red-500">*</span>
                                </label>
                                <select id="modal_gender" name="gender" required
                                    class="form-select-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <?php if ($civilStatusExists): ?>
                                <div>
                                    <label for="modal_civil_status" class="block text-sm font-medium mb-2">
                                        Civil Status <span class="text-red-500">*</span>
                                    </label>
                                    <select id="modal_civil_status" name="civil_status"
                                        class="form-select-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                        <option value="">Select Status</option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Widowed</option>
                                        <option>Separated</option>
                                        <option>Divorced</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php if ($occupationExists): ?>
                                <div>
                                    <label for="modal_occupation" class="block text-sm font-medium mb-2">
                                        Occupation
                                    </label>
                                    <input type="text" id="modal_occupation" name="occupation"
                                        placeholder="Enter Occupation"
                                        class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                </div>
                            <?php endif; ?>

                            <div>
                                <label for="modal_phic_no" class="block text-sm font-medium mb-2">
                                    PHIC No.
                                </label>
                                <input type="text" id="modal_phic_no" name="phic_no" placeholder="Enter PHIC Number"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_bhw_assigned" class="block text-sm font-medium mb-2">
                                    BHW Assigned
                                </label>
                                <input type="text" id="modal_bhw_assigned" name="bhw_assigned"
                                    placeholder="Enter BHW Name"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_family_no" class="block text-sm font-medium mb-2">
                                    Family No.
                                </label>
                                <input type="text" id="modal_family_no" name="family_no"
                                    placeholder="Enter Family Number"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_fourps_member" class="block text-sm font-medium mb-2">
                                    4P's Member
                                </label>
                                <select id="modal_fourps_member" name="fourps_member"
                                    class="form-select-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>

                            <?php if ($sitioExists): ?>
                                <div>
                                    <label for="modal_sitio" class="block text-sm font-medium mb-2">
                                        Sitio <span class="text-red-500">*</span>
                                    </label>
                                    <select id="modal_sitio" name="sitio"
                                        class="form-select-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                        <option value="">Select Sitio</option>
                                        <option value="Kalinao">Kalinao</option>
                                        <option value="Nangka">Nangka</option>
                                        <option value="Lubi">Lubi</option>
                                        <option value="Sta. Cruz">Sta. Cruz</option>
                                        <option value="Regla">Regla</option>
                                        <option value="Abellana">Abellana</option>
                                        <option value="Sto.niño l">Sto.niño l</option>
                                        <option value="Sto.niño ll">Sto.niño ll</option>
                                        <option value="Sto.niño lll">Sto.niño lll</option>
                                        <option value="Zapatera">Zapatera</option>
                                        <option value="Mabuhay">Mabuhay</option>
                                        <option value="San Vicente">San Vicente</option>
                                        <option value="City Central">City Central</option>
                                        <option value="San. Antonio">San. Antonio</option>
                                        <option value="San Roque">San Roque</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div>
                                <label for="modal_address" class="block text-sm font-medium mb-2">
                                    Complete Address <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modal_address" name="address"
                                    placeholder="Enter Complete Address"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>
                            <div>
                                <label for="modal_contact" class="block text-sm font-medium mb-2">
                                    Contact Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modal_contact" name="contact" placeholder="Enter Contact Number"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>
                        </div>
                        <div class="flex justify-end mt-8">
                            <button type="button" id="nextToMedicalBtn" class="btn-primary px-8 py-3 rounded-full text-white font-medium shadow flex items-center gap-2" disabled>
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div id="medicalInfoStep" class="bg-white" style="display:none;">
                        <h3
                            class="text-2xl border-b border-black-100 font-normal text-blue-700 gap-4 py-6 mb-6 flex items-center">
                            <svg width="42" height="42" viewBox="0 0 42 42" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M14.5833 26.9104V28.125C14.5833 30.6114 15.5711 32.996 17.3292 34.7541C19.0874 36.5123 21.4719 37.5 23.9583 37.5C26.4447 37.5 28.8293 36.5123 30.5875 34.7541C32.3456 32.996 33.3333 30.6114 33.3333 28.125V24.6458C31.9427 24.1544 30.7706 23.1871 30.0243 21.915C29.2779 20.6429 29.0052 19.1479 29.2546 17.6942C29.5039 16.2405 30.2591 14.9218 31.3867 13.9711C32.5144 13.0204 33.9418 12.499 35.4167 12.499C36.8916 12.499 38.319 13.0204 39.4466 13.9711C40.5742 14.9218 41.3295 16.2405 41.5788 17.6942C41.8281 19.1479 41.5555 20.6429 40.8091 21.915C40.0627 23.1871 38.8906 24.1544 37.5 24.6458V28.125C37.5 31.7165 36.0733 35.1608 33.5337 37.7004C30.9942 40.24 27.5498 41.6667 23.9583 41.6667C20.3669 41.6667 16.9225 40.24 14.3829 37.7004C11.8434 35.1608 10.4167 31.7165 10.4167 28.125V26.9104C7.50365 26.418 4.85919 24.9098 2.95235 22.6532C1.04551 20.3967 -0.000452952 17.5377 1.47146e-07 14.5833V4.16667C1.47146e-07 3.0616 0.438987 2.00179 1.22039 1.22039C2.00179 0.438987 3.0616 0 4.16667 0L6.25 0C6.80253 0 7.33244 0.219493 7.72314 0.610194C8.11384 1.00089 8.33333 1.5308 8.33333 2.08333C8.33333 2.63587 8.11384 3.16577 7.72314 3.55647C7.33244 3.94717 6.80253 4.16667 6.25 4.16667H4.16667V14.5833C4.16667 16.7935 5.04464 18.9131 6.60744 20.4759C8.17025 22.0387 10.2899 22.9167 12.5 22.9167C14.7101 22.9167 16.8298 22.0387 18.3926 20.4759C19.9554 18.9131 20.8333 16.7935 20.8333 14.5833V4.16667H18.75C18.1975 4.16667 17.6676 3.94717 17.2769 3.55647C16.8862 3.16577 16.6667 2.63587 16.6667 2.08333C16.6667 1.5308 16.8862 1.00089 17.2769 0.610194C17.6676 0.219493 18.1975 0 18.75 0L20.8333 0C21.9384 0 22.9982 0.438987 23.7796 1.22039C24.561 2.00179 25 3.0616 25 4.16667V14.5833C25.0005 17.5377 23.9545 20.3967 22.0477 22.6532C20.1408 24.9098 17.4963 26.418 14.5833 26.9104ZM35.4167 20.8333C35.9692 20.8333 36.4991 20.6138 36.8898 20.2231C37.2805 19.8324 37.5 19.3025 37.5 18.75C37.5 18.1975 37.2805 17.6676 36.8898 17.2769C36.4991 16.8862 35.9692 16.6667 35.4167 16.6667C34.8641 16.6667 34.3342 16.8862 33.9435 17.2769C33.5528 17.6676 33.3333 18.1975 33.3333 18.75C33.3333 19.3025 33.5528 19.8324 33.9435 20.2231C34.3342 20.6138 34.8641 20.8333 35.4167 20.8333Z"
                                    fill="#2563EB" />
                            </svg>
                            Medical Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label for="modal_height" class="block text-sm font-medium mb-2">
                                    Height (cm) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal_height" name="height" placeholder="0.0" required
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_weight" class="block text-sm font-medium mb-2">
                                    Weight (kg) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal_weight" name="weight" placeholder="0.0" required
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_temperature" class="block text-sm font-medium mb-2">
                                    Temperature (°C)
                                </label>
                                <input type="number" id="modal_temperature" name="temperature" placeholder="0"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_blood_pressure" class="block text-sm font-medium mb-2">
                                    Blood Pressure
                                </label>
                                <input type="text" id="modal_blood_pressure" name="blood_pressure" placeholder="120/80"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>

                            <div>
                                <label for="modal_blood_type" class="block text-sm font-medium mb-2">
                                    Blood Type <span class="text-red-500">*</span>
                                </label>
                                <select id="modal_blood_type" name="blood_type" required
                                    class="form-select-modal w-full rounded-xl border-blue-200 px-4 py-3">
                                    <option value="">Select Blood Type</option>
                                    <option>A+</option>
                                    <option>A-</option>
                                    <option>B+</option>
                                    <option>B-</option>
                                    <option>AB+</option>
                                    <option>AB-</option>
                                    <option>O+</option>
                                    <option>O-</option>
                                    <option>Unknown</option>
                                </select>
                            </div>

                            <div>
                                <label for="modal_last_checkup" class="block text-sm font-medium mb-2">
                                    Last Check-up Date
                                </label>
                                <input type="date" id="modal_last_checkup" name="last_checkup"
                                    class="form-input-modal w-full rounded-xl border-blue-200 px-4 py-3">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 mb-5">
                            <div class="flex flex-col gap-2">
                                <label for="modal_allergies" class="text-gray-700 font-medium">Allergies</label>
                                <textarea id="modal_allergies" name="allergies" rows="3"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Food, drug, environmental allergies..."></textarea>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="modal_current_medications" class="text-gray-700 font-medium">Current
                                    Medications</label>
                                <textarea id="modal_current_medications" name="current_medications" rows="3"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Medications with dosage and frequency..."></textarea>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="modal_immunization_record" class="text-gray-700 font-medium">Immunization
                                    Record</label>
                                <textarea id="modal_immunization_record" name="immunization_record" rows="3"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Provide immunization history or recent vaccines"></textarea>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="modal_chronic_conditions" class="text-gray-700 font-medium">Chronic
                                    Conditions</label>
                                <textarea id="modal_chronic_conditions" name="chronic_conditions" rows="3"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Hypertension, diabetes, asthma, etc..."></textarea>
                            </div>
                        </div>

                        <div class="flex flex-col gap-6 mb-3">
                            <div class="flex flex-col gap-2">
                                <label for="modal_medical_history" class="text-gray-700 font-medium">Medical
                                    History</label>
                                <textarea id="modal_medical_history" name="medical_history" rows="4"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Past illnesses, surgeries, hospitalizations, chronic conditions..."></textarea>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="modal_family_history" class="text-gray-700 font-medium">Family Medical
                                    History</label>
                                <textarea id="modal_family_history" name="family_history" rows="4"
                                    class="form-textarea-modal w-full rounded-xl border-blue-200 px-4 py-3"
                                    placeholder="Family history of diseases (parents, siblings)..."></textarea>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="add_patient" value="1">
                    <input type="hidden" name="consent_given" value="1">
                </form>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-white border-t border-blue-100 px-10 py-6">
                <div class="flex justify-between items-center flex-wrap gap-4">
                    <span
                        class="flex items-center text-center gap-3 text-md text-gray-500 bg-gray-100 px-8 py-5 rounded-full">
                        <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M16.6667 33.3333C25.8717 33.3333 33.3333 25.8717 33.3333 16.6667C33.3333 7.46167 25.8717 0 16.6667 0C7.46167 0 0 7.46167 0 16.6667C0 25.8717 7.46167 33.3333 16.6667 33.3333ZM19.1667 9.58333C19.1667 10.3569 18.8594 11.0987 18.3124 11.6457C17.7654 12.1927 17.0235 12.5 16.25 12.5C15.4765 12.5 14.7346 12.1927 14.1876 11.6457C13.6406 11.0987 13.3333 10.3569 13.3333 9.58333C13.3333 8.80978 13.6406 8.06792 14.1876 7.52094C14.7346 6.97396 15.4765 6.66667 16.25 6.66667C17.0235 6.66667 17.7654 6.97396 18.3124 7.52094C18.8594 8.06792 19.1667 8.80978 19.1667 9.58333ZM17.6008 14.87C17.8264 15.0227 18.0111 15.2283 18.1388 15.4689C18.2665 15.7094 18.3333 15.9776 18.3333 16.25V22.72L19.9117 21.9308L21.4033 24.9117L17.4117 26.9075C17.1576 27.0345 16.8752 27.0944 16.5915 27.0816C16.3077 27.0688 16.0319 26.9836 15.7903 26.8343C15.5487 26.6849 15.3493 26.4763 15.2109 26.2282C15.0726 25.9801 15 25.7007 15 25.4167V18.7117L13.655 19.25L12.4167 16.155L16.0475 14.7025C16.3003 14.6013 16.5741 14.5635 16.8449 14.5926C17.1157 14.6216 17.3752 14.7174 17.6008 14.87Z"
                                fill="black" fill-opacity="0.25" />
                        </svg>
                        Fields marked with * are required
                    </span>

                    <div class="flex gap-3">
                        <button type="button" onclick="clearAddPatientForm()"
                            class="flex px-6 py-4 text-center items-center gap-3 rounded-[4px] border border-[#2563EB] text-[#2563EB] hover:bg-gray-200 font-medium">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M14.781 13.7198C14.8507 13.7895 14.906 13.8722 14.9437 13.9632C14.9814 14.0543 15.0008 14.1519 15.0008 14.2504C15.0008 14.349 14.9814 14.4465 14.9437 14.5376C14.906 14.6286 14.8507 14.7114 14.781 14.781C14.7114 14.8507 14.6286 14.906 14.5376 14.9437C14.4465 14.9814 14.349 15.0008 14.2504 15.0008C14.1519 15.0008 14.0543 14.9814 13.9632 14.9437C13.8722 14.906 13.7895 14.8507 13.7198 14.781L7.50042 8.56073L1.28104 14.781C1.14031 14.9218 0.94944 15.0008 0.750417 15.0008C0.551394 15.0008 0.360523 14.9218 0.219792 14.781C0.0790615 14.6403 3.92322e-09 14.4494 0 14.2504C-3.92322e-09 14.0514 0.0790615 13.8605 0.219792 13.7198L6.4401 7.50042L0.219792 1.28104C0.0790615 1.14031 0 0.94944 0 0.750417C0 0.551394 0.0790615 0.360523 0.219792 0.219792C0.360523 0.0790615 0.551394 0 0.750417 0C0.94944 0 1.14031 0.0790615 1.28104 0.219792L7.50042 6.4401L13.7198 0.219792C13.8605 0.0790615 14.0514 -3.92322e-09 14.2504 0C14.4494 3.92322e-09 14.6403 0.0790615 14.781 0.219792C14.9218 0.360523 15.0008 0.551394 15.0008 0.750417C15.0008 0.94944 14.9218 1.14031 14.781 1.28104L8.56073 7.50042L14.781 13.7198Z"
                                    fill="#2563EB" />
                            </svg>
                            Clear Form
                        </button>
                        <button type="submit" name="add_patient" form="patientForm"
                            class="flex items-center text-center gap-3 px-8 py-4 rounded-[6px] bg-blue-600 hover:bg-blue-700 text-white font-medium shadow">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21.3112 2.689C21.1225 2.5005 20.8871 2.36569 20.629 2.29846C20.371 2.23122 20.0997 2.234 19.843 2.3065H19.829L1.83461 7.7665C1.54248 7.85069 1.28283 8.02166 1.09007 8.25676C0.897302 8.49185 0.780525 8.77997 0.75521 9.08294C0.729895 9.3859 0.797238 9.6894 0.948314 9.95323C1.09939 10.2171 1.32707 10.4287 1.60117 10.5602L9.56242 14.4377L13.4343 22.3943C13.5547 22.6513 13.7462 22.8685 13.9861 23.0201C14.226 23.1718 14.5042 23.2517 14.788 23.2502C14.8312 23.2502 14.8743 23.2484 14.9174 23.2446C15.2201 23.2201 15.5081 23.1036 15.7427 22.9107C15.9773 22.7178 16.1473 22.4578 16.2299 22.1656L21.6862 4.17119C21.6862 4.1665 21.6862 4.16181 21.6862 4.15712C21.7596 3.90115 21.7636 3.63024 21.6977 3.37223C21.6318 3.11421 21.4984 2.8784 21.3112 2.689ZM14.7965 21.7362L14.7918 21.7493V21.7427L11.0362 14.0271L15.5362 9.52712C15.6709 9.38533 15.7449 9.19651 15.7424 9.00094C15.7399 8.80537 15.6611 8.61852 15.5228 8.48022C15.3845 8.34191 15.1976 8.26311 15.002 8.26061C14.8065 8.2581 14.6177 8.3321 14.4759 8.46681L9.97586 12.9668L2.25742 9.21119H2.25086H2.26399L20.2499 3.75025L14.7965 21.7362Z"
                                    fill="white" />
                            </svg>
                            Register Patient
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Stepper Logic for Add Patient Modal ---
        document.addEventListener('DOMContentLoaded', function() {
            const personalStep = document.getElementById('personalInfoStep');
            const medicalStep = document.getElementById('medicalInfoStep');
            const nextBtn = document.getElementById('nextToMedicalBtn');
            // List all required personal info fields
            const requiredFields = [
                document.getElementById('modal_full_name'),
                document.getElementById('modal_date_of_birth'),
                document.getElementById('modal_gender'),
                document.getElementById('modal_address'),
                document.getElementById('modal_contact'),
            ];
            // Optional: Add civil status and sitio if present
            if (document.getElementById('modal_civil_status')) requiredFields.push(document.getElementById('modal_civil_status'));
            if (document.getElementById('modal_sitio')) requiredFields.push(document.getElementById('modal_sitio'));

            function validatePersonalFields() {
                return requiredFields.every(field => {
                    if (!field) return true;
                    if (field.tagName === 'SELECT') {
                        return field.value && field.value !== '';
                    }
                    return field.value && field.value.trim() !== '';
                });
            }

            function updateNextBtnState() {
                nextBtn.disabled = !validatePersonalFields();
            }

            requiredFields.forEach(field => {
                if (!field) return;
                field.addEventListener('input', updateNextBtnState);
                field.addEventListener('change', updateNextBtnState);
            });

            nextBtn.addEventListener('click', function() {
                if (!validatePersonalFields()) return;
                personalStep.style.display = 'none';
                medicalStep.style.display = '';
            });

            // When modal opens, always reset to step 1
            window.openAddPatientModal = (function(origFn) {
                return function() {
                    personalStep.style.display = '';
                    medicalStep.style.display = 'none';
                    nextBtn.disabled = true;
                    if (typeof origFn === 'function') origFn();
                };
            })(window.openAddPatientModal);
        });

        // Variables for pagination in export modal
        let currentExportPage = 1;
        let totalExportPages = 1;
        let currentExportSearch = '';
        let isLoadingPatients = false;

        // Populate patient list in manual selection modal with pagination and search
        function populatePatientSelectionList(page = 1, search = '') {
            const tbody = document.getElementById('patientSelectionList');
            if (!tbody) return;

            // Prevent multiple simultaneous requests
            if (isLoadingPatients) return;

            currentExportPage = page;
            currentExportSearch = search;
            isLoadingPatients = true;

            // Show loading state
            tbody.innerHTML = `
        <tr>
            <td colspan="4" class="text-center py-8">
                <div class="flex justify-center items-center">
                    <div class="loading-spinner mr-3"></div>
                    <span class="text-gray-600">Loading patients...</span>
                </div>
            </td>
        </tr>
    `;

            // Build URL with parameters
            let url = window.location.pathname + '?ajax_get_patients=1&page=' + page;
            if (search) {
                url += '&search=' + encodeURIComponent(search);
            }

            console.log('Fetching patients from:', url); // Debug log

            // Fetch patients via AJAX with timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            fetch(url, {
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    isLoadingPatients = false;
                    if (data.success) {
                        renderPatientTable(data.patients, data.totalPages, data.currentPage, data.totalRecords);
                    } else {
                        showErrorMessage('Error loading patients: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    isLoadingPatients = false;
                    clearTimeout(timeoutId);
                    console.error('Error loading patients:', error);

                    if (error.name === 'AbortError') {
                        showErrorMessage('Request timeout. Please try again.');
                    } else {
                        showErrorMessage('Network error: ' + error.message + '. Please check your connection and try again.');
                    }
                });
        }

        // Show error message in table
        function showErrorMessage(message) {
            const tbody = document.getElementById('patientSelectionList');
            if (!tbody) return;

            tbody.innerHTML = `
        <tr>
            <td colspan="4" class="text-center py-8">
                <div class="flex flex-col items-center justify-center text-red-500">
                    <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                    <span class="text-lg font-medium">${message}</span>
                    <button onclick="populatePatientSelectionList(1, '${currentExportSearch}')" 
                            class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-redo mr-2"></i>Try Again
                    </button>
                </div>
            </td>
        </tr>
    `;
        }

        // Render patient table with pagination
        function renderPatientTable(patients, totalPages, currentPage, totalRecords) {
            const tbody = document.getElementById('patientSelectionList');
            if (!tbody) return;

            totalExportPages = totalPages;

            if (!patients || patients.length === 0) {
                tbody.innerHTML = `
            <tr>
    <td colspan="4" class="text-center py-24 text-gray-500">
        <div class="flex flex-col items-center justify-center min-h-[300px] py-16 px-10 gap-4">

            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M20 4H4C3.73478 4 3.48043 4.10536 3.29289 4.29289C3.10536 4.48043 3 4.73478 3 5V19C3 19.2652 3.10536 19.5196 3.29289 19.7071C3.48043 19.8946 3.73478 20 4 20H20C20.2652 20 20.5196 19.8946 20.7071 19.7071C20.8946 19.5196 21 19.2652 21 19V5C21 4.73478 20.8946 4.48043 20.7071 4.29289C20.5196 4.10536 20.2652 4 20 4ZM4 2C3.20435 2 2.44129 2.31607 1.87868 2.87868C1.31607 3.44129 1 4.20435 1 5V19C1 19.7956 1.31607 20.5587 1.87868 21.1213C2.44129 21.6839 3.20435 22 4 22H20C20.7956 22 21.5587 21.6839 22.1213 21.1213C22.6839 20.5587 23 19.7956 23 19V5C23 4.20435 22.6839 3.44129 22.1213 2.87868C21.5587 2.31607 20.7956 2 20 2H4ZM6 7H8V9H6V7ZM11 7C10.7348 7 10.4804 7.10536 10.2929 7.29289C10.1054 7.48043 10 7.73478 10 8C10 8.26522 10.1054 8.51957 10.2929 8.70711C10.4804 8.89464 10.7348 9 11 9H17C17.2652 9 17.5196 8.89464 17.7071 8.70711C17.8946 8.51957 18 8.26522 18 8C18 7.73478 17.8946 7.48043 17.7071 7.29289C17.5196 7.10536 17.2652 7 17 7H11ZM8 11H6V13H8V11ZM10 12C10 11.7348 10.1054 11.4804 10.2929 11.2929C10.4804 11.1054 10.7348 11 11 11H17C17.2652 11 17.5196 11.1054 17.7071 11.2929C17.8946 11.4804 18 11.7348 18 12C18 12.2652 17.8946 12.5196 17.7071 12.7071C17.5196 12.8946 17.2652 13 17 13H11C10.7348 13 10.4804 12.8946 10.2929 12.7071C10.1054 12.5196 10 12.2652 10 12ZM8 15H6V17H8V15ZM10 16C10 15.7348 10.1054 15.4804 10.2929 15.2929C10.4804 15.1054 10.7348 15 11 15H17C17.2652 15 17.5196 15.1054 17.7071 15.2929C17.8946 15.4804 18 15.7348 18 16C18 16.2652 17.8946 16.5196 17.7071 16.7071C17.5196 16.8946 17.2652 17 17 17H11C10.7348 17 10.4804 16.8946 10.2929 16.7071C10.1054 16.5196 10 16.2652 10 16Z" fill="#B9B9B9"/>
</svg>

            <span class="text-lg font-medium text-gray-400">No patients found</span>
        </div>
    </td>
</tr>
        `;
                updatePaginationControls();
                return;
            }

            let html = '';
            patients.forEach(patient => {
                const lastCheckup = patient.last_checkup_formatted || patient.last_checkup || 'N/A';

                html += `
            <tr class="hover:bg-[#F8FBFF] transition">
                <td class="checkbox-column px-4 py-3 text-center">
                    <input type="checkbox" class="patient-select w-5 h-5 accent-[#4A90E2]" 
                           value="${patient.id}" onchange="updateSelectedCount(); updateFooterCount()">
                </td>
                <td class="px-6 py-3 text-[#2E5C8A] font-medium">${escapeHtml(patient.full_name)}</td>
                <td class="px-6 py-3 text-[#666666]">${patient.age || '-'}</td>
                <td class="px-6 py-3 text-[#888888] text-sm">${lastCheckup}</td>
            </tr>
        `;
            });

            tbody.innerHTML = html;
            updatePaginationControls();
            updateSelectedCount();
        }

        // Update pagination controls in the modal
        function updatePaginationControls() {
            // Check if pagination container exists, if not create it
            let paginationContainer = document.getElementById('exportPagination');
            if (!paginationContainer) {
                const modalContent = document.querySelector('#manualSelectionModal .flex-1.overflow-y-auto');
                if (modalContent) {
                    paginationContainer = document.createElement('div');
                    paginationContainer.id = 'exportPagination';
                    paginationContainer.className = 'flex items-center justify-between mt-4 py-3 border-t border-gray-200';
                    modalContent.appendChild(paginationContainer);
                }
            }

            if (!paginationContainer) return;

            if (totalExportPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            // Build pagination HTML
            //         let paginationHtml = `
            //     <div class="flex items-center text-sm text-gray-600">
            //         <span>Page ${currentExportPage} of ${totalExportPages}</span>
            //     </div>
            //     <div class="flex items-center gap-2">
            // `;

            // Previous button
            let paginationHtml = `
    <button onclick="changeExportPage(${currentExportPage - 1})" 
            class="px-4 py-2 mx-1 text-lg rounded-full  ${currentExportPage <= 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'hover:bg-blue-50 text-blue-600'}"
            style="border: 1.5px solid #3498DB;"
            ${currentExportPage <= 1 ? 'disabled' : ''}>
        <i class="fas fa-chevron-left text-sm"></i>
    </button>
`;

            // Page numbers (show limited range)
            const startPage = Math.max(1, currentExportPage - 2);
            const endPage = Math.min(totalExportPages, currentExportPage + 2);

            if (startPage > 1) {
                paginationHtml += `<button onclick="changeExportPage(1)" class="px-4 py-2 text-lg rounded-full hover:bg-blue-50 text-blue-600" style="border: 1.5px solid #3498DB;">1</button>`;
                if (startPage > 2) {
                    // paginationHtml += `<span class="px-4 py-2">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
        <button onclick="changeExportPage(${i})" 
                class="px-4 py-2 text-lg rounded-full ${i === currentExportPage ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 text-blue-600'}"
                style="border: 1.5px solid #3498DB;"
                >
            ${i}
        </button>
    `;
            }

            if (endPage < totalExportPages) {
                if (endPage < totalExportPages - 1) {
                    // paginationHtml += `<span class="px-4 py-2">...</span>`;
                }
                paginationHtml += `<button onclick="changeExportPage(${totalExportPages})" class="px-4 py-2 text-lg rounded-full hover:bg-blue-50 text-blue-600" 
                style="border: 1.5px solid #3498DB;"
                >${totalExportPages}</button>`;
            }

            // Next button
            paginationHtml += `
    <button onclick="changeExportPage(${currentExportPage + 1})" 
            class="px-4 py-2 mx-1 text-lg rounded-full ${currentExportPage >= totalExportPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'hover:bg-blue-50 text-blue-600'}"
            style="border: 1.5px solid #3498DB;"
            ${currentExportPage >= totalExportPages ? 'disabled' : ''}>
        <i class="fas fa-chevron-right text-sm"></i>
    </button>
`;


            paginationHtml += `</div>`;

            paginationContainer.innerHTML = paginationHtml;
        }

        // Change page in export modal
        function changeExportPage(newPage) {
            if (newPage < 1 || newPage > totalExportPages || isLoadingPatients) return;
            populatePatientSelectionList(newPage, currentExportSearch);
        }

        // Update selected count in modal
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('#manualSelectionModal .patient-select:checked');
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = checkboxes.length;
            }

            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('selectAllPatients');
            const allCheckboxes = document.querySelectorAll('#manualSelectionModal .patient-select');
            if (selectAllCheckbox && allCheckboxes.length > 0) {
                selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
                selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
            }
        }

        // Update footer count
        function updateFooterCount() {
            const checkboxes = document.querySelectorAll('#manualSelectionModal .patient-select:checked');
            const footerCount = document.getElementById('footerCount');
            if (footerCount) {
                footerCount.textContent = checkboxes.length;
            }
        }

        // Toggle all patients for export
        function toggleAllPatients(checkbox) {
            const checkboxes = document.querySelectorAll('#manualSelectionModal .patient-select');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateSelectedCount();
            updateFooterCount();
        }

        // Open manual selection modal
        function openManualSelectionModal() {
            closeExportModal();

            const modal = document.getElementById('manualSelectionModal');
            if (!modal) {
                console.error('Manual selection modal not found');
                showNotification('error', 'Modal not found. Please refresh the page.');
                return;
            }

            modal.style.display = 'flex';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
                // Reset to first page and clear search
                currentExportPage = 1;
                currentExportSearch = '';

                // Clear search input if it exists
                const searchInput = document.getElementById('exportPatientSearch');
                if (searchInput) {
                    searchInput.value = '';
                }

                // Load patients
                populatePatientSelectionList(1, '');
            }, 10);
        }

        // Close manual selection modal
        function closeManualSelectionModal() {
            const modal = document.getElementById('manualSelectionModal');
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    // Reset loading state
                    isLoadingPatients = false;
                }, 300);
            }
        }

        // Search patients in export modal
        function searchExportPatients() {
            const searchInput = document.getElementById('exportPatientSearch');
            if (!searchInput) return;

            const searchTerm = searchInput.value.trim();
            currentExportSearch = searchTerm;
            currentExportPage = 1;

            populatePatientSelectionList(1, searchTerm);
        }

        // Debounce search to avoid too many requests
        let searchTimeout;

        function debounceSearchExportPatients() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchExportPatients();
            }, 500);
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        }

        // Consultation Notes Variables
        let currentPatientId = null;
        let hasNotes = false;
        let noteCount = 0;

        // Age calculation function
        function calculateAge(dateOfBirth, ageInput) {
            if (!dateOfBirth) {
                ageInput.value = '';
                return;
            }

            const dob = new Date(dateOfBirth);
            const today = new Date();

            // Calculate age
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();

            // Adjust age if birthday hasn't occurred yet this year
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            // Validate that date is not in the future
            if (dob > today) {
                showNotification('error', 'Date of birth cannot be in the future!');
                document.getElementById('modal_date_of_birth').value = '';
                ageInput.value = '';
                return;
            }

            // Validate reasonable age (0-120 years)
            if (age < 0 || age > 120) {
                showNotification('error', 'Please enter a valid date of birth (age must be between 0-120 years)');
                document.getElementById('modal_date_of_birth').value = '';
                ageInput.value = '';
                return;
            }

            ageInput.value = age;
        }

        // Enhanced Consultation Note Functions
        function openConsultationNoteModal() {
            const patientIdInput = document.querySelector('#modalContent input[name="patient_id"]');
            if (!patientIdInput) {
                showNotification('error', 'Unable to get patient information.');
                return;
            }

            currentPatientId = patientIdInput.value;
            document.getElementById('notePatientId').value = currentPatientId;

            // Check if patient has existing notes
            checkConsultationNotes(currentPatientId);

            // Show modal
            const modal = document.getElementById('consultationNoteModal');
            modal.style.display = 'flex';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);

            // Reset form to add mode
            switchToAddNote();
        }

        function closeConsultationNoteModal() {
            const modal = document.getElementById('consultationNoteModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                resetConsultationNoteModal();
            }, 300);
        }

        function checkConsultationNotes(patientId) {
            fetch(`../api/check_consultation_notes.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    hasNotes = data.hasNotes;
                    noteCount = data.noteCount || 0;

                    if (hasNotes) {
                        loadConsultationNotesInline(patientId);
                    }

                    updateNoteButtonCount(noteCount);
                })
                .catch(error => {
                    console.error('Error checking notes:', error);
                });
        }

        function loadConsultationNotes(patientId) {
            const notesList = document.getElementById('consultationNotesList');
            notesList.innerHTML = `
                <div class="loading-notes">
                    <div class="loading-spinner"></div>
                    <p class="ml-3 text-gray-600">Loading consultation notes...</p>
                </div>
            `;

            fetch(`../api/get_consultation_notes.php?patient_id=${patientId}`)
                .then(response => response.text())
                .then(html => {
                    notesList.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading notes:', error);
                    notesList.innerHTML =
                        '<div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">' +
                        '<i class="fas fa-exclamation-triangle text-2xl mb-3 text-gray-400"></i>' +
                        '<p>Error loading consultation notes.</p>' +
                        '</div>';
                });
        }

        function updateNoteButtonCount(count) {
            const noteButton = document.getElementById('noteButton');
            if (noteButton) {
                if (count > 0) {
                    let badge = noteButton.querySelector('.note-badge');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'note-badge';
                        noteButton.appendChild(badge);
                    }
                    badge.textContent = count;

                    noteButton.appendChild(badge);
                    noteButton.classList.remove('btn-add-note');
                    noteButton.classList.add('btn-view-notes');
                } else {
                    const badge = noteButton.querySelector('.note-badge');
                    if (badge) {
                        badge.remove();
                    }

                    noteButton.classList.remove('btn-view-notes');
                    noteButton.classList.add('btn-add-note');
                }
            }
        }

        function switchToViewNotes() {
            document.getElementById('consultationNoteTitle').innerHTML =
                `<i class="fas fa-sticky-note mr-2"></i>Consultation Notes (${noteCount})`;
            document.getElementById('addNoteForm').style.display = 'none';
            document.getElementById('viewNotesContent').style.display = 'block';
            document.getElementById('addNoteActions').style.display = 'none';
            document.getElementById('viewNoteActions').style.display = 'block';

            // Load notes in view mode
            loadConsultationNotes(currentPatientId);
        }

        function switchToAddNote() {
            document.getElementById('consultationNoteTitle').innerHTML =
                'Add Consultation Note';
            document.getElementById('addNoteForm').style.display = 'block';
            document.getElementById('viewNotesContent').style.display = 'none';
            document.getElementById('addNoteActions').style.display = 'block';
            document.getElementById('viewNoteActions').style.display = 'none';

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('consultation_date').value = today;
            document.getElementById('next_consultation_date').value = '';
            document.getElementById('note').value = '';
            document.getElementById('doctor_name').value = '';

            // Focus on doctor name field
            setTimeout(() => {
                document.getElementById('doctor_name').focus();
            }, 100);
        }

        function resetConsultationNoteModal() {
            switchToAddNote();
            hasNotes = false;
            noteCount = 0;
            currentPatientId = null;
        }

        function saveConsultationNote() {
            const patientId = document.getElementById('notePatientId').value;
            const note = document.getElementById('note').value.trim();
            const consultationDate = document.getElementById('consultation_date').value;
            const nextDate = document.getElementById('next_consultation_date').value;
            const doctorName = document.getElementById('doctor_name').value.trim();

            if (!patientId || !note || !consultationDate || !doctorName) {
                showNotification('error', 'Please fill in all required fields.');
                return;
            }

            const formData = new FormData();
            formData.append('patient_id', patientId);
            formData.append('note', note);
            formData.append('consultation_date', consultationDate);
            formData.append('doctor_name', doctorName);
            if (nextDate) {
                formData.append('next_consultation_date', nextDate);
            }
            formData.append('add_consultation_note', '1');

            const saveBtn = document.querySelector('#addNoteActions button');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Record...';
            saveBtn.disabled = true;

            fetch('existing_info_patients.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result.includes('successfully') || result.includes('Consultation note added')) {
                        showNotification('success', 'Consultation note added successfully!');
                        closeConsultationNoteModal();

                        setTimeout(() => {
                            loadConsultationNotesInline(patientId);
                            checkConsultationNotes(patientId);
                        }, 500);

                    } else {
                        showNotification('error', 'Error saving consultation note. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Network error. Check console for details.');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        // Export functionality
        // Enhanced Export Modal Functions
        function openExportModal() {
            const modal = document.getElementById('exportModal');
            modal.style.display = 'flex';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);
        }

        function closeExportModal() {
            const modal = document.getElementById('exportModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function toggleExportOptions() {
            const exportOptions = document.getElementById('exportOptions');
            exportOptions.classList.toggle('show');
        }

        function exportAllRecords(format) {
            closeExportModal();

            const urlParams = new URLSearchParams(window.location.search);
            const patientTypeSelect = document.querySelector('select[name="patient_type"]');
            const currentPatientType = patientTypeSelect ? patientTypeSelect.value : 'all';

            let url = `existing_info_patients.php?export=${format}&patient_type=${currentPatientType}`;
            const tab = urlParams.get('tab');
            const search = urlParams.get('search');
            const searchBy = urlParams.get('search_by');

            if (tab) url += `&tab=${tab}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (searchBy) url += `&search_by=${searchBy}`;

            const typeLabels = {
                'all': 'All Patients',
                'registered': 'Registered Patients',
                'regular': 'Regular Patients'
            };
            const formatLabels = {
                'excel': 'Excel (.xlsx)',
                'pdf': 'PDF Report'
            };

            showNotification('info', `Exporting ${typeLabels[currentPatientType] || 'All Patients'} as ${formatLabels[format] || format}...`, 5000);

            const exportWindow = window.open(url, '_blank');

            if (!exportWindow || exportWindow.closed || typeof exportWindow.closed == 'undefined') {
                showNotification('error', 'Pop-up blocked! Please allow pop-ups for this site to export.');

                const form = document.createElement('form');
                form.method = 'GET';
                form.action = url;
                form.target = '_blank';
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } else {
                setTimeout(() => {
                    showNotification('success', 'Export started! Your file will download shortly.', 3000);
                }, 1000);
            }
        }

        // Confirm manual export with selected patients
        function confirmManualExport(format) {
            const checkboxes = document.querySelectorAll('#manualSelectionModal .patient-select:checked');
            if (checkboxes.length === 0) {
                showNotification('warning', 'Please select at least one patient to export.');
                return;
            }

            const patientIds = Array.from(checkboxes).map(cb => cb.value);
            const patientCount = patientIds.length;

            const typeLabels = {
                'excel': 'Excel',
                'pdf': 'PDF'
            };

            // Show processing notification
            showNotification('info', `Exporting ${patientCount} patient(s) as ${typeLabels[format]}...`, 6000);

            // Create form with all required data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'existing_info_patients.php';
            form.style.display = 'none';

            // Add patient IDs
            patientIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_patients[]';
                input.value = id;
                form.appendChild(input);
            });

            // Add export type
            if (format === 'excel') {
                const exportInput = document.createElement('input');
                exportInput.type = 'hidden';
                exportInput.name = 'export_manual';
                exportInput.value = '1';
                form.appendChild(exportInput);
            } else if (format === 'pdf') {
                const exportInput = document.createElement('input');
                exportInput.type = 'hidden';
                exportInput.name = 'export_pdf';
                exportInput.value = '1';
                form.appendChild(exportInput);
            }

            // Submit form
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            // Close modal after submission
            setTimeout(() => {
                closeManualSelectionModal();
                showNotification('success', `Successfully exported ${patientCount} patient(s) as ${typeLabels[format]}!`, 3000);
            }, 800);
        }

        // Close export dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const exportBtn = document.querySelector('.btn-export');
            const exportOptions = document.getElementById('exportOptions');

            if (exportBtn && !exportBtn.contains(event.target) &&
                exportOptions && !exportOptions.contains(event.target)) {
                exportOptions.classList.remove('show');
            }
        });

        // Keyboard shortcuts for modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const exportModal = document.getElementById('exportModal');
                const manualSelectionModal = document.getElementById('manualSelectionModal');

                if (exportModal && exportModal.style.display === 'flex') {
                    closeExportModal();
                }

                if (manualSelectionModal && manualSelectionModal.style.display === 'flex') {
                    closeManualSelectionModal();
                }
            }
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const exportModal = document.getElementById('exportModal');
            const manualSelectionModal = document.getElementById('manualSelectionModal');

            if (exportModal && event.target === exportModal) {
                closeExportModal();
            }

            if (manualSelectionModal && event.target === manualSelectionModal) {
                closeManualSelectionModal();
            }
        });

        // Initialize selected count on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Age calculation for Add Patient modal
            const dobInput = document.getElementById('modal_date_of_birth');
            const ageInput = document.getElementById('modal_age');

            if (dobInput && ageInput) {
                dobInput.addEventListener('change', function() {
                    calculateAge(this.value, ageInput);
                });

                if (dobInput.value) {
                    calculateAge(dobInput.value, ageInput);
                }
            }
        });

        // Modal functions
        function openAddPatientModal() {
            const modal = document.getElementById('addPatientModal');
            modal.style.display = 'flex';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);

            // Reset age field
            const ageInput = document.getElementById('modal_age');
            if (ageInput) {
                ageInput.value = '';
            }
        }

        function closeAddPatientModal() {
            const modal = document.getElementById('addPatientModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function clearAddPatientForm() {
            const form = document.getElementById('patientForm');
            if (form) {
                form.reset();
                const ageInput = document.getElementById('modal_age');
                if (ageInput) {
                    ageInput.value = '';
                }
            }
        }

        // Enhanced modal functions for viewing patient info
        function openViewModal(patientId) {
            document.getElementById('modalContent').innerHTML = `
                <div class="flex justify-center items-center py-20">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-5xl text-primary mb-4"></i>
                        <p class="text-lg text-gray-600 font-medium">Loading patient data...</p>
                        <p class="text-sm text-gray-500 mt-2">Please wait while we retrieve the information</p>
                    </div>
                </div>
            `;

            const modal = document.getElementById('viewModal');
            modal.style.display = 'flex';
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);

            fetch(`./get_patient_data.php?id=${patientId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    addConsultationNotesSection(patientId);
                    bindDatePickerTrigger('view_date_of_birth', 'view_date_of_birth_trigger');

                    const modalContent = document.getElementById('modalContent');
                    const forms = modalContent.querySelectorAll('form');
                    forms.forEach(form => {
                        form.classList.add('w-full', 'max-w-full');

                        const containers = form.querySelectorAll('.grid, .flex');
                        containers.forEach(container => {
                            container.classList.add('w-full');
                        });

                        const inputs = form.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (!input.classList.contains('readonly-field')) {
                                input.classList.add('text-lg', 'px-4', 'py-3');
                            }
                        });
                    });

                    setupMedicalForm();
                    checkConsultationNotes(patientId);
                })
                .catch(error => {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="text-center py-12 bg-red-50 rounded-xl border-2 border-red-200">
                            <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                            <h3 class="text-xl font-semibold text-red-700 mb-2">Error Loading Patient Data</h3>
                            <p class="text-red-600 mb-4">Unable to load patient information. Please try again.</p>
                            <button type="button" onclick="openViewModal(${patientId})" class="btn-primary px-6 py-3">
                                <i class="fas fa-redo mr-2"></i>Retry
                            </button>
                        </div>
                    `;
                });
        }

        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Function to collect all medical data and submit
        function saveMedicalInformation() {
            const healthInfoForm = document.getElementById('healthInfoForm');

            if (!healthInfoForm) {
                showNotification('error', 'Medical form not found. Please reload the page.');
                return;
            }

            const requiredFields = healthInfoForm.querySelectorAll('[required]');
            let isValid = true;
            let missingFields = [];

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    missingFields.push(field.name.replace('_', ' '));
                    field.classList.add('field-empty');
                    field.classList.remove('field-filled');
                } else {
                    field.classList.add('field-filled');
                    field.classList.remove('field-empty');
                }
            });

            if (!isValid) {
                showNotification('error', `Please fill in all required fields: ${missingFields.join(', ')}`);
                const firstMissing = healthInfoForm.querySelector('.field-empty');
                if (firstMissing) {
                    firstMissing.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstMissing.focus();
                }
                return;
            }

            const formData = new FormData(healthInfoForm);
            const medicalFields = [
                'height', 'weight', 'blood_type', 'temperature',
                'blood_pressure', 'allergies', 'medical_history',
                'current_medications', 'family_history',
                'immunization_record', 'chronic_conditions', 'gender',
                'phic_no', 'bhw_assigned', 'family_no', 'fourps_member'
            ];

            medicalFields.forEach(field => {
                const element = healthInfoForm.querySelector(`[name="${field}"]`);
                if (element) {
                    formData.set(field, element.value);
                }
            });

            const saveBtn = document.getElementById('saveMedicalBtn');
            const originalBtnText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Record...';
            saveBtn.disabled = true;

            fetch(healthInfoForm.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result.includes('successfully') || result.includes('Success') || result.includes('saved')) {
                        showNotification('success', 'Medical information saved successfully!');

                        const patientId = formData.get('patient_id');
                        if (patientId) {
                            setTimeout(() => {
                                closeViewModal();
                                setTimeout(() => {
                                    openViewModal(patientId);
                                }, 500);
                            }, 2000);
                        }
                    } else {
                        showNotification('error', 'Error saving medical information. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Network error: ' + error.message);
                })
                .finally(() => {
                    saveBtn.innerHTML = originalBtnText;
                    saveBtn.disabled = false;
                });
        }

        // Updated setupMedicalForm function (simplified)
        function setupMedicalForm() {
            const healthInfoForm = document.getElementById('healthInfoForm');

            if (healthInfoForm) {
                const inputs = healthInfoForm.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.classList.add('field-filled');
                            this.classList.remove('field-empty');
                        } else if (this.hasAttribute('required')) {
                            this.classList.add('field-empty');
                            this.classList.remove('field-filled');
                        }
                    });

                    input.addEventListener('blur', function() {
                        if (this.hasAttribute('required') && !this.value.trim()) {
                            this.classList.add('field-empty');
                            this.classList.remove('field-filled');
                        }
                    });
                });
            }
        }

        // Print Patient Record Function
        function printPatientRecord() {
            const patientId = getPatientId();
            const printBtn = document.getElementById('printRecordBtn');
            const originalPrintBtnHtml = printBtn ? printBtn.innerHTML : '';

            // Get the current consultation note ID if viewing a specific note
            let noteId = null;
            const noteModal = document.getElementById('consultationNoteModal');
            if (noteModal && noteModal.style.display === 'flex') {
                const noteIdInput = document.getElementById('notePatientId');
                if (noteIdInput && noteIdInput.value) {
                    // If we're viewing a specific note, get its ID
                    noteId = getCurrentNoteId();
                }
            }

            if (patientId) {
                let url = `/community-health-tracker/api/print_patient.php?id=${patientId}`;
                if (noteId) {
                    url += `&note_id=${noteId}`;
                }

                if (printBtn) {
                    printBtn.disabled = true;
                    printBtn.innerHTML = '<span class="inline-flex items-center"><span class="loading-spinner mr-2" style="width:18px;height:18px;border-width:2px;"></span>Preparing Print...</span>';
                }

                const popupWidth = 1200;
                const popupHeight = 800;
                const screenWidth = window.screen.availWidth || window.screen.width;
                const screenHeight = window.screen.availHeight || window.screen.height;
                const left = Math.max(0, Math.floor((screenWidth - popupWidth) / 2));
                const top = Math.max(0, Math.floor((screenHeight - popupHeight) / 2));

                const windowFeatures = `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`;

                const printWindow = window.open(url, '_blank', windowFeatures);
                if (printWindow) {
                    printWindow.focus();
                    printWindow.onload = function() {
                        setTimeout(() => {
                            printWindow.print();
                            if (printBtn) {
                                printBtn.disabled = false;
                                printBtn.innerHTML = originalPrintBtnHtml;
                            }
                        }, 1000);
                    };

                    setTimeout(() => {
                        if (printBtn && printBtn.disabled) {
                            printBtn.disabled = false;
                            printBtn.innerHTML = originalPrintBtnHtml;
                        }
                    }, 6000);
                } else {
                    if (printBtn) {
                        printBtn.disabled = false;
                        printBtn.innerHTML = originalPrintBtnHtml;
                    }
                    showNotification('error', 'Please allow pop-ups for this site to print');
                }
            } else {
                if (printBtn) {
                    printBtn.disabled = false;
                    printBtn.innerHTML = originalPrintBtnHtml;
                }
                showNotification('error', 'No patient selected for printing');
            }
        }

        // Helper function to get current note ID (implement based on your UI)
        function getCurrentNoteId() {
            // Check if we're viewing a specific note in the modal
            const viewNotesContent = document.getElementById('viewNotesContent');
            if (viewNotesContent && viewNotesContent.style.display !== 'none') {
                // Look for active note in the notes list
                const activeNote = document.querySelector('.note-card.active');
                if (activeNote) {
                    return activeNote.dataset.noteId;
                }
            }
            return null;
        }

        function showNotification(type, message, duration = 5000) {
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `custom-notification fixed top-6 right-6 z-50 px-6 py-4 rounded-xl shadow-lg border-2 ${type === 'error' ? 'alert-error' :
                type === 'success' ? 'alert-success' :
                    type === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                        'bg-blue-100 text-blue-800 border-blue-200'
                }`;

            const icon = type === 'error' ? 'fa-exclamation-circle' :
                type === 'success' ? 'fa-check-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle';

            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas ${icon} text-xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            const timeoutId = setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, duration);

            // Allow manual dismissal by clicking
            notification.style.cursor = 'pointer';
            notification.addEventListener('click', () => {
                clearTimeout(timeoutId);
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            });
        }

        // Enhanced modal close on outside click
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const consultationNoteModal = document.getElementById('consultationNoteModal');
            const exportModal = document.getElementById('exportModal');
            const manualSelectionModal = document.getElementById('manualSelectionModal');

            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === consultationNoteModal) {
                closeConsultationNoteModal();
            }
            if (event.target === exportModal) {
                closeExportModal();
            }
            if (event.target === manualSelectionModal) {
                closeManualSelectionModal();
            }
        };

        // Add keyboard support for modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeViewModal();
                closeAddPatientModal();
                closeConsultationNoteModal();
                closeExportModal();
                closeManualSelectionModal();
            }
        });

        // Auto-hide messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var successMessage = document.getElementById('successMessage');
                var errorMessage = document.querySelector('.alert-error');

                if (successMessage) {
                    successMessage.style.display = 'none';
                }

                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            }, 3000);
        });

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const tabTriggers = document.querySelectorAll('.tab-trigger');

            // Handle tab button clicks
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');

                    // Update active tab button
                    tabButtons.forEach(btn => btn.classList.remove('border-primary', 'text-primary', 'active'));
                    button.classList.add('border-primary', 'text-primary', 'active');

                    // Show active tab content
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');

                    // Update URL with tab parameter
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url);
                });
            });

            // Handle external tab triggers
            tabTriggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const tabId = trigger.getAttribute('data-tab');

                    // Update active tab button
                    tabButtons.forEach(btn => {
                        if (btn.getAttribute('data-tab') === tabId) {
                            btn.classList.add('border-primary', 'text-primary', 'active');
                        } else {
                            btn.classList.remove('border-primary', 'text-primary', 'active');
                        }
                    });

                    // Show active tab content
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');

                    // Update URL with tab parameter
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url);
                });
            });

            // Check if URL has tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                const tabButton = document.querySelector(`.tab-btn[data-tab="${tabParam}"]`);
                if (tabButton) tabButton.click();
            }
        });

        // Clear search on page refresh
        if (window.history.replaceState && !window.location.search.includes('search=')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Ensure buttons have proper styling
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-view, .btn-archive, .btn-add-patient, .btn-primary, .btn-success, .btn-print, .btn-edit, .btn-save-medical, .btn-add-note, .btn-view-notes, .btn-view-all, .btn-back-to-pagination, .pagination-btn, .btn-pdf');
            buttons.forEach(button => {
                button.style.borderStyle = 'solid';
            });
        });

        function addConsultationNotesSection(patientId) {
            const healthInfoForm = document.getElementById('healthInfoForm');
            if (!healthInfoForm) return;

            // Try to get user_id from a hidden input or data attribute
            let userId = null;
            const userIdInput = healthInfoForm.querySelector('input[name="user_id"]');
            if (userIdInput) {
                userId = userIdInput.value;
            } else if (healthInfoForm.dataset.userId) {
                userId = healthInfoForm.dataset.userId;
            }

            // Build profile image URL if userId exists
            let profileImgHtml = '';
            if (userId && userId !== '0' && userId !== '') {
                profileImgHtml = `<img src="/community-health-tracker/uploads/profiles/profile_${userId}.jpg?cb=${Date.now()}" 
                                alt="Profile" 
                                class="rounded-full border-2 border-blue-300 shadow w-24 h-24 object-cover mr-4"
                                onerror="this.onerror=null; this.src='/community-health-tracker/assets/images/default-avatar.png'">`;
            }

            const notesSection = document.createElement('div');
            notesSection.className = 'consultation-notes-section mb-8';
            notesSection.innerHTML = `
        <div class="consultation-notes-header">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-medium text-blue-800 flex items-center gap-3">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24.7017 4.59869L9.43807 1.90338C8.94841 1.81722 8.44458 1.92905 8.03737 2.2143C7.63015 2.49955 7.3529 2.93485 7.26659 3.42448L3.78026 23.2292C3.73762 23.4718 3.7432 23.7204 3.7967 23.9609C3.85019 24.2014 3.95054 24.4289 4.09202 24.6306C4.2335 24.8322 4.41333 25.004 4.62124 25.1362C4.82914 25.2683 5.06104 25.3582 5.30369 25.4006L20.5674 28.096C20.8101 28.1388 21.0588 28.1333 21.2994 28.0799C21.54 28.0265 21.7677 27.9262 21.9695 27.7847C22.1713 27.6432 22.3432 27.4633 22.4754 27.2553C22.6077 27.0473 22.6976 26.8153 22.74 26.5725L26.2263 6.76784C26.3118 6.27802 26.1991 5.77434 25.9132 5.36756C25.6273 4.96078 25.1915 4.68422 24.7017 4.59869ZM20.8908 26.2491L5.62596 23.5538L9.11229 3.74909L24.376 6.4444L20.8908 26.2491ZM10.4705 6.84518C10.5139 6.60046 10.6527 6.383 10.8564 6.2406C11.0602 6.0982 11.3121 6.04252 11.5568 6.0858L21.2834 7.8026C21.5145 7.8431 21.7221 7.96882 21.8651 8.15493C22.008 8.34104 22.076 8.574 22.0555 8.80779C22.0351 9.04158 21.9277 9.25919 21.7546 9.41763C21.5814 9.57607 21.3552 9.66382 21.1205 9.66354C21.0655 9.66346 21.0106 9.65876 20.9564 9.64948L11.2299 7.93151C10.9851 7.88808 10.7677 7.74926 10.6253 7.54555C10.4829 7.34184 10.4272 7.08992 10.4705 6.84518ZM9.82127 10.5389C9.84264 10.4177 9.88769 10.3018 9.95385 10.1979C10.02 10.094 10.106 10.0042 10.2069 9.9336C10.3078 9.86298 10.4216 9.81292 10.5418 9.78628C10.662 9.75965 10.7863 9.75696 10.9076 9.77838L20.6342 11.4964C20.867 11.5353 21.0765 11.6606 21.2209 11.8472C21.3654 12.0339 21.4341 12.2682 21.4134 12.5033C21.3927 12.7384 21.284 12.957 21.1092 13.1156C20.9343 13.2741 20.7061 13.3608 20.4701 13.3585C20.4147 13.3586 20.3593 13.3535 20.3049 13.3432L10.5783 11.6264C10.3338 11.5825 10.1167 11.4432 9.97475 11.2393C9.8328 11.0354 9.7776 10.7835 9.82127 10.5389ZM9.17088 14.2315C9.21512 13.9874 9.35429 13.7708 9.5579 13.6292C9.76152 13.4875 10.013 13.4323 10.2572 13.4756L15.1181 14.3299C15.3492 14.3704 15.5567 14.4961 15.6997 14.682C15.8426 14.868 15.9107 15.1008 15.8904 15.3345C15.87 15.5682 15.7629 15.7858 15.59 15.9444C15.4171 16.1029 15.191 16.1909 14.9564 16.1909C14.9014 16.1909 14.8466 16.1862 14.7924 16.1768L9.92908 15.3178C9.68458 15.2741 9.46741 15.1352 9.32525 14.9315C9.1831 14.7278 9.12758 14.4761 9.17088 14.2315Z" fill="#3C96E1"/>
                    </svg>
                    Consultation Notes History
                    <span id="notesCountBadge" class="bg-green-500 text-white text-sm px-3 py-1 rounded-full">0 notes</span>
                </h3>
                <div class="flex items-center gap-2">
                    ${profileImgHtml}
                    <button onclick="openConsultationNoteModal()" 
                            class="btn-add-note text-sm font-medium px-4 py-2">
                        <i class="fas fa-plus mr-2"></i>Add Note
                    </button>
                </div>
            </div>
            <p class="text-gray-500 mt-2 text-sm">View past consultations and add new notes for this patient. Older notes appear on the left.</p>
        </div>
        <div id="notesHistoryContainer" class="p-6">
            <div class="horizontal-notes-container">
                <div class="loading-notes">
                    <div class="loading-spinner"></div>
                    <p class="text-gray-600">Loading consultation notes...</p>
                </div>
            </div>
        </div>
    `;

            healthInfoForm.parentNode.insertBefore(notesSection, healthInfoForm);
            loadConsultationNotesInline(patientId);
        }

        // Function to load consultation notes inline in the main modal
        function loadConsultationNotesInline(patientId) {
            const container = document.getElementById('notesHistoryContainer');
            if (!container) return;

            fetch(`./get_consultation_notes.php?patient_id=${patientId}&inline=true`)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;

                    const notesCount = container.querySelectorAll('.note-card').length;
                    const notesCountBadge = document.getElementById('notesCountBadge');
                    if (notesCountBadge) {
                        notesCountBadge.textContent = `${notesCount} Note${notesCount !== 1 ? 's' : ''}`;
                    }

                    // Update the note button in the footer
                    updateNoteButtonCount(notesCount);
                })
                .catch(error => {
                    console.error('Error loading notes:', error);
                    container.innerHTML = `
                        <div class="empty-notes">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Unable to load consultation notes. Please try again.</p>
                            <button onclick="loadConsultationNotesInline(${patientId})" 
                                    class="btn-primary px-4 py-2 text-sm">
                                <i class="fas fa-redo mr-1"></i> Retry
                            </button>
                        </div>
                    `;
                });
        }

        // Function to view note details
        function viewNoteDetails(noteId) {
            fetch(`../api/get_note_details.php?id=${noteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const note = data.note;

                        // Format date only (without time)
                        const createdDate = formatDateOnly(note.created_at);
                        // Format time only
                        const createdTime = formatTimeOnly(note.created_at);

                        const noteHtml = `
                    <div class="bg-white px-3 rounded-lg max-w-2xl">
                        <div class="flex justify-between border-b-2 border-gray-100 pb-4 items-start mb-4">
                            <div>
                                <h4 class="text-xl font-medium mt-4" style="color: #387EC3;">Consultation Note Details</h4>
                            </div>
                            <button onclick="closeNoteDetails()" 
                                    class="text-gray-500 text-2xl hover:text-gray-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-base font-medium text-gray-400 mb-1">Physician Assign :</label>
                                    <div class="font-medium text-lg" style="color: #387EC3;">${note.doctor_name || 'Not specified'}</div>
                                </div>
                                <div>
                                    <label class="block text-base font-medium text-gray-500 mb-1">Date created :</label>
                                    <div class="font-medium text-lg" style="color: #387EC3;">${createdDate}</div>
                                </div>
                            </div>
                            
                           <div>
                                <div class="bg-gray-50 text-gray-600 p-4 rounded-lg" 
                                    style="border: 1px solid #DEDEDE; height: 200px;">
                                    ${note.note.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-2">
                                ${note.next_consultation_date ? `
                                <div class="py-2 px-4 rounded-md" style="background-color: #007BFF4D; width: fit-content;">
                                    <span class="text-base" style="color: #007BFF;">Next Consultation :</span>
                                    <span class="font-medium text-lg ml-1" style="color: #007BFF;">${formatDateOnly(note.next_consultation_date)}</span>
                                </div>
                                ` : '<div></div>'}
                                
                                <div>
                                    <span class="text-base text-gray-500">Time Created :</span>
                                    <span class="font-medium text-lg ml-1" style="color: #387EC3;">${createdTime}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                        showCustomModal(noteHtml, 'Note Details');
                    }
                })
                .catch(error => {
                    console.error('Error loading note details:', error);
                    showNotification('error', 'Unable to load note details.');
                });
        }

        // Helper function to format date only (e.g., "March 05, 2026")
        function formatDateOnly(dateTimeString) {
            if (!dateTimeString) return '';
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: '2-digit'
            });
        }

        // Helper function to format time only (e.g., "02:26 PM")
        function formatTimeOnly(dateTimeString) {
            if (!dateTimeString) return '';
            const date = new Date(dateTimeString);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        // Function to show note details in modal
        function showNoteDetailsModal(note) {
            const modalHtml = `
                <div class="bg-white p-6 rounded-lg">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h4 class="text-xl font-semibold text-gray-800">Consultation Note Details</h4>
                            <p class="text-gray-500 mt-1">
                                <i class="fas fa-calendar-day mr-1"></i>
                                ${formatDate(note.consultation_date)}
                            </p>
                        </div>
                        <button onclick="closeNoteDetailsModal()" 
                                class="text-gray-400 hover:text-gray-600 text-xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Doctor:</h5>
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 mb-4">
                                <i class="fas fa-user-md mr-2 text-blue-600"></i>
                                <span class="font-medium">${note.doctor_name || 'Not specified'}</span>
                            </div>
                        </div>
                        
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Consultation Notes:</h5>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 whitespace-pre-line">
                                ${note.note}
                            </div>
                        </div>
                        
                        ${note.next_consultation_date ? `
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <h5 class="text-sm font-medium text-green-700 mb-1">
                                <i class="fas fa-calendar-check mr-1"></i>
                                Next Consultation Date:
                            </h5>
                            <p class="text-green-800 font-medium">${formatDate(note.next_consultation_date)}</p>
                        </div>
                        ` : ''}
                        
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-user mr-2"></i>
                                <span>Recorded by: ${note.created_by_name || 'Staff Member'}</span>
                                <span class="mx-2">•</span>
                                <i class="fas fa-clock mr-2"></i>
                                <span>${formatDateTime(note.created_at)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end space-x-3">
                        <button onclick="closeNoteDetailsModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg">
                            Close
                        </button>
                        <button onclick="useNoteAsTemplate(${note.id})" 
                                class="px-4 py-2 bg-primary text-white hover:bg-primary-dark rounded-lg">
                            <i class="fas fa-copy mr-1"></i> Use as Template
                        </button>
                    </div>
                </div>
            `;

            let modal = document.getElementById('noteDetailsModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'noteDetailsModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999]';
                modal.style.display = 'none';
                document.body.appendChild(modal);
            }

            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                    ${modalHtml}
                </div>
            `;

            modal.style.display = 'flex';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);
        }

        function closeNoteDetailsModal() {
            const modal = document.getElementById('noteDetailsModal');
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        }

        // Helper functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Function to add a new note based on an existing one
        function addSimilarNote(noteId) {
            fetch(`../api/get_note_details.php?id=${noteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const note = data.note;

                        openConsultationNoteModal();

                        setTimeout(() => {
                            const noteTextarea = document.getElementById('note');
                            if (noteTextarea) {
                                noteTextarea.value = `Based on previous consultation on ${formatDate(note.consultation_date)}:\n\n${note.note}`;
                            }

                            const today = new Date().toISOString().split('T')[0];
                            const dateInput = document.getElementById('consultation_date');
                            if (dateInput) dateInput.value = today;

                            // Set doctor name from existing note
                            const doctorNameInput = document.getElementById('doctor_name');
                            if (doctorNameInput && note.doctor_name) {
                                // Extract just the name part (remove "Dr." prefix)
                                const doctorName = note.doctor_name.replace(/^Dr\.\s*/i, '');
                                doctorNameInput.value = doctorName;
                            }

                            if (noteTextarea) noteTextarea.focus();
                        }, 300);
                    }
                })
                .catch(error => {
                    console.error('Error loading note for template:', error);
                    showNotification('error', 'Unable to load note for template.');
                });
        }

        function showCustomModal(content, title) {
            let modal = document.getElementById('customModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'customModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999] modal';
                modal.style.display = 'none';
                modal.innerHTML = `
                    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                        <div id="customModalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            document.getElementById('customModalContent').innerHTML = content;
            // document.querySelector('#customModalHeader h3').textContent = title;

            modal.style.display = 'flex';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);
        }

        function closeCustomModal() {
            const modal = document.getElementById('customModal');
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        }

        function closeNoteDetails() {
            closeCustomModal();
        }

        // Get patient ID from the form
        function getPatientId() {
            const patientIdInput = document.querySelector('#modalContent input[name="patient_id"]');
            return patientIdInput ? patientIdInput.value : null;
        }

        // Update the note button to handle both add and view modes
        function handleNoteButtonClick() {
            if (noteCount > 0) {
                openConsultationNoteModal();
                switchToViewNotes();
            } else {
                openConsultationNoteModal();
                switchToAddNote();
            }
        }

        // Update the note button event listener
        document.addEventListener('DOMContentLoaded', function() {
            const noteButton = document.getElementById('noteButton');
            if (noteButton) {
                noteButton.addEventListener('click', handleNoteButtonClick);
            }

            // Setup real-time duplicate check for Add Patient modal
            setupDuplicateCheckValidation();
        });

        /**
         * Real-time validation for duplicate patient records in Add Patient modal
         * Checks full name and date of birth against existing records
         */
        function setupDuplicateCheckValidation() {
            const fullNameInput = document.getElementById('modal_full_name');
            const dobInput = document.getElementById('modal_date_of_birth');
            const patientForm = document.getElementById('patientForm');

            if (!fullNameInput || !dobInput || !patientForm) return;

            // Create container for duplicate warning message
            const warningContainer = document.createElement('div');
            warningContainer.id = 'duplicateWarningContainer';
            warningContainer.style.display = 'none';
            warningContainer.style.paddingTop = '20px';
            warningContainer.style.marginBottom = '24px';
            warningContainer.innerHTML = `
                <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl flex-shrink-0"></i>
                        <h4 class="font-semibold text-red-700">⚠️ This patient already has a record in the system. You can view or update the existing record instead.</h4>
                    </div>
                    <button type="button" onclick="viewExistingPatientRecord()" 
                            class="text-sm bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium transition whitespace-nowrap flex-shrink-0">
                        <i class="fas fa-eye mr-2"></i>View Record
                    </button>
                </div>
            `;

            // Insert warning container at the very beginning of the form (before Personal Information)
            const firstChild = patientForm.firstChild;
            patientForm.insertBefore(warningContainer, firstChild);

            // Event listeners for real-time validation
            fullNameInput.addEventListener('blur', checkForDuplicate);
            fullNameInput.addEventListener('input', checkForDuplicate);
            dobInput.addEventListener('change', checkForDuplicate);
            dobInput.addEventListener('blur', checkForDuplicate);
        }

        // Store the current duplicate patient ID for view action
        let currentDuplicatePatientId = null;

        /**
         * Check if a patient record already exists
         * Called in real-time as user types full name or selects date of birth
         */
        function checkForDuplicate() {
            const fullNameInput = document.getElementById('modal_full_name');
            const dobInput = document.getElementById('modal_date_of_birth');
            const warningContainer = document.getElementById('duplicateWarningContainer');

            if (!fullNameInput || !dobInput || !warningContainer) return;

            const fullName = fullNameInput.value.trim();
            const dateOfBirth = dobInput.value;

            // Clear if either field is empty
            if (!fullName || !dateOfBirth) {
                warningContainer.style.display = 'none';
                clearFieldHighlight();
                return;
            }

            // Make AJAX request to check for duplicates
            fetch(`../api/check_duplicate_patient.php?full_name=${encodeURIComponent(fullName)}&date_of_birth=${encodeURIComponent(dateOfBirth)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.exists && data.patient) {
                        // Duplicate found - highlight fields and show warning
                        showDuplicateWarning(data.patient);
                        highlightDuplicateFields();
                    } else {
                        // No duplicate - clear highlight and warning
                        warningContainer.style.display = 'none';
                        clearFieldHighlight();
                    }
                })
                .catch(error => {
                    console.error('Error checking for duplicate:', error);
                    // Don't block form on error, just log it
                });
        }

        /**
         * Display the duplicate warning message with patient details
         */
        function showDuplicateWarning(patientData) {
            const warningContainer = document.getElementById('duplicateWarningContainer');

            if (!warningContainer) return;

            // Store patient ID for view action
            currentDuplicatePatientId = patientData.id;

            // Show the warning container (no patient details displayed)
            warningContainer.style.display = 'block';
            warningContainer.style.animation = 'slideDown 0.3s ease-out';

            // Disable all form fields
            disableFormFields();
        }

        /**
         * Disable all form fields in the Add Patient modal
         */
        function disableFormFields() {
            const form = document.getElementById('patientForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.id !== 'modal_full_name' && input.id !== 'modal_date_of_birth') {
                    input.disabled = true;
                    input.style.opacity = '0.6';
                    input.style.cursor = 'not-allowed';
                    input.style.backgroundColor = '#f3f4f6';
                }
            });

            // Disable all buttons except View Record button and close button
            const buttons = document.querySelectorAll('#addPatientModal button');
            buttons.forEach(button => {
                // Don't disable the close button, View Record button, or buttons with viewExistingPatientRecord onclick
                if (!button.classList.contains('modal-close-btn') && !button.getAttribute('onclick')?.includes('viewExistingPatientRecord')) {
                    button.disabled = true;
                    button.style.opacity = '0.5';
                    button.style.cursor = 'not-allowed';
                    button.style.pointerEvents = 'none';
                }
            });
        }

        /**
         * Enable all form fields in the Add Patient modal
         */
        function enableFormFields() {
            const form = document.getElementById('patientForm');
            if (!form) return;

            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.disabled = false;
                input.style.opacity = '1';
                input.style.cursor = 'auto';
                input.style.backgroundColor = '';
            });

            // Enable all buttons in the modal
            const buttons = document.querySelectorAll('#addPatientModal button');
            buttons.forEach(button => {
                // Don't enable the close button, only action buttons
                if (!button.classList.contains('modal-close-btn')) {
                    button.disabled = false;
                    button.style.opacity = '1';
                    button.style.cursor = 'pointer';
                    button.style.pointerEvents = 'auto';
                }
            });
        }

        /**
         * Highlight the duplicate fields with red border and background
         */
        function highlightDuplicateFields() {
            const fullNameInput = document.getElementById('modal_full_name');
            const dobInput = document.getElementById('modal_date_of_birth');

            if (fullNameInput) {
                fullNameInput.style.borderColor = '#DC2626';
                fullNameInput.style.borderWidth = '2px';
                fullNameInput.style.backgroundColor = '#FEE2E2';
                fullNameInput.classList.add('duplicate-field');
            }

            if (dobInput) {
                dobInput.style.borderColor = '#DC2626';
                dobInput.style.borderWidth = '2px';
                dobInput.style.backgroundColor = '#FEE2E2';
                dobInput.classList.add('duplicate-field');
            }
        }

        /**
         * Clear the highlight from fields
         */
        function clearFieldHighlight() {
            const fullNameInput = document.getElementById('modal_full_name');
            const dobInput = document.getElementById('modal_date_of_birth');

            if (fullNameInput) {
                fullNameInput.style.borderColor = '#85ccfb';
                fullNameInput.style.backgroundColor = 'white';
                fullNameInput.classList.remove('duplicate-field');
            }

            if (dobInput) {
                dobInput.style.borderColor = '#85ccfb';
                dobInput.style.backgroundColor = 'white';
                dobInput.classList.remove('duplicate-field');
            }

            // Enable all form fields when no duplicate is found
            enableFormFields();
        }

        /**
         * Open the existing patient record in view modal
         */
        function viewExistingPatientRecord() {
            if (currentDuplicatePatientId) {
                closeAddPatientModal();
                setTimeout(() => {
                    openViewModal(currentDuplicatePatientId);
                }, 300);
            }
        }

        // Add CSS animation for sliding down the warning
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .duplicate-field {
                transition: all 0.2s ease !important;
            }

            /* Custom Flatpickr Calendar Styles */
            .flatpickr-calendar {
                width: 380px !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.16) !important;
                border-radius: 8px !important;
            }

            .flatpickr-calendar.open {
                display: inline-block !important;
                animation: slideInUp 0.3s ease;
            }

            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .flatpickr-months {
                padding: 20px !important;
            }

            .flatpickr-month {
                font-size: 16px !important;
                font-weight: 600 !important;
                color: #2c3e50 !important;
            }

            .flatpickr-prev-month,
            .flatpickr-next-month {
                height: 32px !important;
                width: 32px !important;
                line-height: 32px !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }

            .flatpickr-prev-month:hover,
            .flatpickr-next-month:hover {
                background-color: #e8f4f8 !important;
                color: #3498db !important;
            }

            .flatpickr-weekdays {
                padding: 10px 0 !important;
                background-color: #f8fafc !important;
                font-weight: 600 !important;
                color: #374151 !important;
                font-size: 13px !important;
                text-transform: uppercase;
            }

            .flatpickr-days {
                padding: 10px 0 !important;
            }

            .flatpickr-day {
                height: 40px !important;
                line-height: 40px !important;
                font-size: 14px !important;
                color: #4b5563 !important;
                transition: all 0.2s ease !important;
                margin: 2px !important;
                border-radius: 6px !important;
            }

            .flatpickr-day:hover {
                background-color: #e0f2fe !important;
                color: #0369a1 !important;
            }

            .flatpickr-day.selected {
                background-color: #3498db !important;
                color: white !important;
                border-radius: 6px !important;
                font-weight: 600 !important;
            }

            .flatpickr-day.today {
                background-color: #d1fae5 !important;
                color: #065f46 !important;
                border-radius: 6px !important;
                font-weight: 600 !important;
            }

            .flatpickr-day.disabled {
                color: #cbd5e1 !important;
                cursor: not-allowed !important;
            }

            .flatpickr-time {
                display: none !important;
            }

            .flatpickr-input {
                font-size: 16px !important;
                padding: 12px 16px !important;
            }
        `;
        document.head.appendChild(style);

        /**
         * Initialize Flatpickr Date Picker for Date of Birth
         */
        function bindDatePickerTrigger(inputId, triggerId) {
            const dateInput = document.getElementById(inputId);
            const dateTrigger = document.getElementById(triggerId);

            if (!dateInput || !dateTrigger) {
                return;
            }

            dateTrigger.addEventListener('click', function() {
                if (typeof dateInput.showPicker === 'function') {
                    dateInput.showPicker();
                } else {
                    dateInput.focus();
                    dateInput.click();
                }
            });
        }

        function initializeDatePicker() {
            // Initialize native date picker for add patient modal
            const dobInput = document.getElementById('modal_date_of_birth');
            if (dobInput) {
                // Clear any default value
                dobInput.value = '';

                // Set up change event for age calculation and duplicate check
                dobInput.addEventListener('change', function() {
                    if (this.value) {
                        const ageInput = document.getElementById('modal_age');
                        calculateAge(this.value, ageInput);
                        checkForDuplicate();
                    }
                });
            }
            
            bindDatePickerTrigger('modal_date_of_birth', 'modal_date_of_birth_trigger');
        }

        // Initialize date picker when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeDatePicker();
        });
    </script>

    <!-- JS Block: Consultation Notes Functions -->
    <!-- JS Block: Child Health Record Form Submission -->
    <!-- JS Block: Test Connection Function -->
    <!-- JS Block: DOMContentLoaded Event Listeners -->
    <script>
        // Function to handle Child Health Record form submission
        function submitChildHealthForm(event) {
            event.preventDefault();

            const form = document.getElementById('childHealthForm');
            if (!form) {
                showNotification('error', 'Child Health form not found.');
                return;
            }

            // Get the submit button
            const submitBtn = document.getElementById('saveChildRecordBtn');
            if (!submitBtn) {
                showNotification('error', 'Save button not found.');
                return;
            }

            // Check required fields for Child Health form
            const requiredFields = ['family_no', 'ufc_no', 'fullname', 'sex', 'dob'];

            let hasEmptyFields = false;
            let emptyFieldNames = [];

            requiredFields.forEach(field => {
                const fieldElement = form.querySelector(`[name="${field}"]`);
                if (fieldElement && !fieldElement.value.trim()) {
                    hasEmptyFields = true;
                    emptyFieldNames.push(field.replace('_', ' '));

                    // Highlight empty field
                    fieldElement.style.borderColor = '#DC2626';
                    fieldElement.style.borderWidth = '2px';
                }
            });

            if (hasEmptyFields) {
                showNotification('error', `Please fill in required fields: ${emptyFieldNames.join(', ')}`);
                return;
            }

            // Create FormData
            const formData = new FormData(form);

            // Show loading
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Record...';
            submitBtn.disabled = true;

            // Submit via AJAX
            fetch('save_child_health_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSuccessModal(result.message || 'Child Health Record saved successfully!');
                        closeChildHealthModal();

                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification('error', result.message || 'Failed to save record.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Network error. Please try again.');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        // Function to handle Present Pregnant form submission
        function submitPresentPregnantForm(event) {
            event.preventDefault();

            const form = document.getElementById('presentPregnantForm');
            if (!form) {
                console.error('Form not found');
                showNotification('error', 'Form not found. Please refresh the page.');
                return;
            }

            // Get the submit button by ID
            const submitBtn = document.getElementById('savePregnantRecordBtn');
            if (!submitBtn) {
                console.error('Submit button not found');
                showNotification('error', 'Submit button not found. Please refresh the page.');
                return;
            }

            // Create FormData object
            const formData = new FormData(form);

            // Add any missing required fields with default values if empty
            const requiredFields = ['birth_plan', 'nutrition_breastfeeding', 'family_planning', 'tt_vaccination', 'iron_folic', 'vitamin_a', 'prenatal_schedule'];

            let hasEmptyFields = false;
            let emptyFieldNames = [];

            // Check required fields
            requiredFields.forEach(field => {
                const fieldElement = form.querySelector(`[name="${field}"]`);
                if (fieldElement && !fieldElement.value.trim()) {
                    hasEmptyFields = true;
                    emptyFieldNames.push(field.replace('_', ' '));

                    // Highlight empty field
                    fieldElement.style.borderColor = '#DC2626';
                    fieldElement.style.borderWidth = '2px';
                    setTimeout(() => {
                        fieldElement.style.borderColor = '';
                        fieldElement.style.borderWidth = '';
                    }, 3000);
                }
            });

            if (hasEmptyFields) {
                showNotification('error', `Please fill in required fields: ${emptyFieldNames.join(', ')}`);

                // Scroll to first empty field
                const firstEmptyField = form.querySelector(`[name="${requiredFields.find(f => !form.querySelector(`[name="${f}"]`).value.trim())}"]`);
                if (firstEmptyField) {
                    firstEmptyField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstEmptyField.focus();
                }
                return;
            }

            // Debug: Log form data
            console.log('Present Pregnant Form Data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            // Show loading
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Record...';
            submitBtn.disabled = true;

            // Submit via AJAX
            fetch('save_present_pregnant_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return response.json();
                })
                .then(result => {
                    console.log('Response result:', result);

                    if (result.success) {
                        showSuccessModal(result.message || 'Present Pregnant Record saved successfully!');
                        closePresentPregnantModal();

                        // Refresh the pregnant records table after 2 seconds
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification('error', result.message || 'Failed to save record.');

                        // If there's debug info in response, log it
                        if (result.debug) {
                            console.error('Debug info:', result.debug);
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showNotification('error', 'Network error: ' + error.message);

                    // Try alternative method if fetch fails
                    console.log('Trying alternative submission method...');
                    submitFormAlternative(form);
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        // Alternative submission method (as regular form submission)
        function submitFormAlternative(form) {
            // Create a hidden input to trigger the form submission
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'save_present_pregnant';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);

            // Submit the form normally
            form.submit();
        }

        // Function to view Child Health Record details
        function viewChildHealthRecord(recordId) {
            fetch(`view_child_health_record.php?id=${recordId}`)
                .then(response => response.text())
                .then(html => {
                    showCustomModal(html, 'Child Health Record Details');
                })
                .catch(error => {
                    showNotification('error', 'Unable to load record details.');
                });
        }

        // Function to view Present Pregnant Record details
        function viewPregnantRecord(recordId) {
            fetch(`view_pregnant_record.php?id=${recordId}`)
                .then(response => response.text())
                .then(html => {
                    showCustomModal(html, 'Present Pregnant Record Details');
                })
                .catch(error => {
                    showNotification('error', 'Unable to load record details.');
                });
        }

        // Add event listeners when modals open
        document.getElementById('childHealthForm').addEventListener('submit', submitChildHealthForm);
        document.getElementById('presentPregnantForm').addEventListener('submit', submitPresentPregnantForm);
    </script>

    <script>
        // Debug function to test the connection
        function testConnection() {
            const testData = new FormData();
            testData.append('test', 'connection_test');
            testData.append('birth_plan', 'Test Birth Plan');
            testData.append('nutrition_breastfeeding', 'Test Nutrition');
            testData.append('family_planning', 'Test Planning');
            testData.append('tt_vaccination', 'Test TT');
            testData.append('iron_folic', 'Test Iron');
            testData.append('vitamin_a', 'Test Vitamin A');
            testData.append('prenatal_schedule', 'Test Schedule');

            fetch('save_present_pregnant_record.php', {
                    method: 'POST',
                    body: testData
                })
                .then(response => {
                    console.log('Test response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Test response text:', text);
                    alert('Test response: ' + text.substring(0, 200));
                })
                .catch(error => {
                    console.error('Test error:', error);
                    alert('Test failed: ' + error.message);
                });
        }

        // You can call this function from browser console: testConnection()
    </script>


    <script>
        // Add event listeners when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any existing form submit event listeners to prevent default submission
            const childForm = document.getElementById('childHealthForm');
            const pregnantForm = document.getElementById('presentPregnantForm');

            if (childForm) {
                childForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitChildHealthForm(event);
                });
            }

            if (pregnantForm) {
                pregnantForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitPresentPregnantForm(event);
                });
            }

            // Also add click event listeners to buttons (as backup)
            const saveChildBtn = document.getElementById('saveChildRecordBtn');
            const savePregnantBtn = document.getElementById('savePregnantRecordBtn');

            if (saveChildBtn) {
                saveChildBtn.addEventListener('click', submitChildHealthForm);
            }

            if (savePregnantBtn) {
                savePregnantBtn.addEventListener('click', submitPresentPregnantForm);
            }
        });
    </script>

    <!-- Go back for Export Option Modal -->
    <script>
        // Go back to Export Modal from Manual Selection Modal
        function goBackToExportModal() {
            // Close manual selection modal
            closeManualSelectionModal();

            // Small delay to ensure smooth transition
            setTimeout(() => {
                // Open export modal
                openExportModal();
            }, 300);
        }
    </script>


</body>

</html>
