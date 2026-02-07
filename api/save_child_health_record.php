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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    // Validate required fields
    $required = ['family_no', 'ufc_no', 'fullname', 'sex', 'dob'];
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
    
    // Prepare SQL for child_health_records
    $sql = "INSERT INTO child_health_records 
        (family_no, ufc_no, fullname, sex, dob, birth_order, place_of_delivery, mother, mother_age, 
         father_occupation, father, father_age, address, type_of_feeding, date_referred_newborn,
         bf1, bf2, bf3, bf4, bf5, child_protected_at_birth, date_assessed, tt_status_mother,
         anemic_children_seen, anemic_children_iron, birthwt, low_birthwt_seen, low_birthwt_iron,
         date_iron_started, vit_a_1, vit_a_2, vit_a_3, completed, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $params = [
        $_POST['family_no'] ?? '',
        $_POST['ufc_no'] ?? '',
        $_POST['fullname'] ?? '',
        $_POST['sex'] ?? '',
        $_POST['dob'] ?? '',
        $_POST['birth_order'] ?? null,
        $_POST['place_of_delivery'] ?? null,
        $_POST['mother'] ?? null,
        !empty($_POST['mother_age']) ? (int)$_POST['mother_age'] : null,
        $_POST['father_occupation'] ?? null,
        $_POST['father'] ?? null,
        !empty($_POST['father_age']) ? (int)$_POST['father_age'] : null,
        $_POST['address'] ?? null,
        $_POST['type_of_feeding'] ?? null,
        !empty($_POST['date_referred_newborn']) ? $_POST['date_referred_newborn'] : null,
        !empty($_POST['bf1']) ? $_POST['bf1'] : null,
        !empty($_POST['bf2']) ? $_POST['bf2'] : null,
        !empty($_POST['bf3']) ? $_POST['bf3'] : null,
        !empty($_POST['bf4']) ? $_POST['bf4'] : null,
        !empty($_POST['bf5']) ? $_POST['bf5'] : null,
        $_POST['child_protected_at_birth'] ?? null,
        !empty($_POST['date_assessed']) ? $_POST['date_assessed'] : null,
        $_POST['tt_status_mother'] ?? null,
        $_POST['anemic_children_seen'] ?? null,
        $_POST['anemic_children_iron'] ?? null,
        $_POST['birthwt'] ?? null,
        $_POST['low_birthwt_seen'] ?? null,
        $_POST['low_birthwt_iron'] ?? null,
        !empty($_POST['date_iron_started']) ? $_POST['date_iron_started'] : null,
        $_POST['vit_a_1'] ?? null,
        $_POST['vit_a_2'] ?? null,
        $_POST['vit_a_3'] ?? null,
        $_POST['completed'] ?? null
    ];
    
    // Log for debugging
    error_log('Child Health Record Params: ' . print_r($params, true));
    
    // Execute the statement
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log('Database error: ' . print_r($errorInfo, true));
        throw new Exception('Database insert failed: ' . ($errorInfo[2] ?? 'Unknown error'));
    }
    
    $record_id = $pdo->lastInsertId();
    error_log('Child record created with ID: ' . $record_id);
    
    // Save immunizations if provided
    if (isset($_POST['immunizations']) && is_array($_POST['immunizations'])) {
        error_log('Processing immunizations: ' . print_r($_POST['immunizations'], true));
        
        foreach ($_POST['immunizations'] as $type => $vaccinations) {
            $imm_stmt = $pdo->prepare("INSERT INTO child_immunizations 
                (child_health_record_id, type, within_24hrs, first, second, third) 
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $imm_params = [
                $record_id,
                $type,
                isset($vaccinations['24hrs']) ? 1 : 0,
                isset($vaccinations['1st']) ? 1 : 0,
                isset($vaccinations['2nd']) ? 1 : 0,
                isset($vaccinations['3rd']) ? 1 : 0
            ];
            
            if (!$imm_stmt->execute($imm_params)) {
                $errorInfo = $imm_stmt->errorInfo();
                error_log('Immunization insert error: ' . print_r($errorInfo, true));
                throw new Exception('Failed to save immunization: ' . $type);
            }
        }
    } else {
        error_log('No immunizations data received');
    }
    
    // Save results if provided
    if (isset($_POST['results']) && is_array($_POST['results'])) {
        error_log('Processing results: ' . print_r($_POST['results'], true));
        
        foreach ($_POST['results'] as $index => $result) {
            if (empty($result['date'])) continue; // Skip empty results
            
            $res_stmt = $pdo->prepare("INSERT INTO child_health_results 
                (child_health_record_id, result_date, age, weight, temperature, height, findings, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $res_params = [
                $record_id,
                $result['date'] ?? null,
                $result['age'] ?? null,
                $result['weight'] ?? null,
                $result['temperature'] ?? null,
                $result['height'] ?? null,
                $result['findings'] ?? null,
                $result['notes'] ?? null
            ];
            
            if (!$res_stmt->execute($res_params)) {
                $errorInfo = $res_stmt->errorInfo();
                error_log('Result insert error: ' . print_r($errorInfo, true));
                throw new Exception('Failed to save result row: ' . $index);
            }
        }
    } else {
        error_log('No results data received');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Child Health Record saved successfully!',
        'record_id' => $record_id
    ]);
    
} catch (Exception $ex) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    error_log('Exception in save_child_health_record.php: ' . $ex->getMessage());
    error_log('Stack trace: ' . $ex->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error saving record: ' . $ex->getMessage()
    ]);
}
?>