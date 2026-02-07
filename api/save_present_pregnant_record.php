<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

// Debug: Log all POST data
error_log('POST data received: ' . print_r($_POST, true));

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    // Validate required fields
    $required = ['birth_plan', 'nutrition_breastfeeding', 'family_planning', 'tt_vaccination', 'iron_folic', 'vitamin_a', 'prenatal_schedule'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // Prepare SQL statement with all form fields
    $sql = "INSERT INTO present_pregnant_records 
        (patient_id, birth_plan, nutrition_breastfeeding, family_planning, tt_vaccination, 
         iron_folic, vitamin_a, prenatal_schedule, visit_notes, referrals, 
         gravidity, parity, prev_outcomes, lmp, cycle_regularity, contraceptive_history,
         past_illnesses, allergies, family_history, edd, gestational_age, risk_assessment,
         danger_signs, bp, hr, rr, temperature, weight, height, fundal_height, 
         fetal_heart_tones, edema, hemoglobin, urinalysis, blood_typing, syphilis_test,
         hiv_test, hepatitis_b, fbs, emergency_prep, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $params = [
        !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null,
        $_POST['birth_plan'] ?? '',
        $_POST['nutrition_breastfeeding'] ?? '',
        $_POST['family_planning'] ?? '',
        $_POST['tt_vaccination'] ?? '',
        $_POST['iron_folic'] ?? '',
        $_POST['vitamin_a'] ?? '',
        $_POST['prenatal_schedule'] ?? '',
        $_POST['visit_notes'] ?? null,
        $_POST['referrals'] ?? null,
        !empty($_POST['gravidity']) ? (int)$_POST['gravidity'] : null,
        !empty($_POST['parity']) ? (int)$_POST['parity'] : null,
        $_POST['prev_outcomes'] ?? null,
        !empty($_POST['lmp']) ? $_POST['lmp'] : null,
        $_POST['cycle_regularity'] ?? null,
        $_POST['contraceptive_history'] ?? null,
        $_POST['past_illnesses'] ?? null,
        $_POST['allergies'] ?? null,
        $_POST['family_history'] ?? null,
        !empty($_POST['edd']) ? $_POST['edd'] : null,
        $_POST['gestational_age'] ?? null,
        $_POST['risk_assessment'] ?? null,
        $_POST['danger_signs'] ?? null,
        $_POST['bp'] ?? null,
        $_POST['hr'] ?? null,
        $_POST['rr'] ?? null,
        $_POST['temperature'] ?? null,
        $_POST['weight'] ?? null,
        $_POST['height'] ?? null,
        $_POST['fundal_height'] ?? null,
        $_POST['fetal_heart_tones'] ?? null,
        $_POST['edema'] ?? null,
        $_POST['hemoglobin'] ?? null,
        $_POST['urinalysis'] ?? null,
        $_POST['blood_typing'] ?? null,
        $_POST['syphilis_test'] ?? null,
        $_POST['hiv_test'] ?? null,
        $_POST['hepatitis_b'] ?? null,
        $_POST['fbs'] ?? null,
        $_POST['emergency_prep'] ?? null
    ];
    
    error_log('SQL Parameters: ' . print_r($params, true));
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Present Pregnant Record saved successfully!',
            'debug' => 'Record saved with ID: ' . $pdo->lastInsertId()
        ]);
    } else {
        $pdo->rollBack();
        $errorInfo = $stmt->errorInfo();
        error_log('Database error: ' . print_r($errorInfo, true));
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save record to database.',
            'debug' => $errorInfo
        ]);
    }
    
} catch (Exception $ex) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    error_log('Exception in save_present_pregnant_record.php: ' . $ex->getMessage());
    error_log('Stack trace: ' . $ex->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $ex->getMessage(),
        'debug_trace' => $ex->getTraceAsString()
    ]);
}
?>