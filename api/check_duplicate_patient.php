<?php
/**
 * API Endpoint to check for duplicate patient records
 * Used for real-time validation in the Add Patient modal
 * 
 * Parameters:
 * - full_name: Patient's full name
 * - date_of_birth: Patient's date of birth (YYYY-MM-DD)
 * 
 * Returns JSON response with:
 * - exists: boolean (true if duplicate found)
 * - patient: object (patient data if duplicate found)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user']) || !isStaff()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'exists' => false]);
    exit;
}

// Get parameters
$fullName = isset($_GET['full_name']) ? trim($_GET['full_name']) : '';
$dateOfBirth = isset($_GET['date_of_birth']) ? trim($_GET['date_of_birth']) : '';

// Validate input
if (empty($fullName) || empty($dateOfBirth)) {
    echo json_encode(['exists' => false, 'error' => 'Missing parameters']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
    echo json_encode(['exists' => false, 'error' => 'Invalid date format']);
    exit;
}

try {
    // Check if patient already exists
    if (staff_can_view_all()) {
        // Check all records without staff restriction
        $stmt = $pdo->prepare("SELECT id, full_name, date_of_birth, contact, age, gender, sitio 
                             FROM sitio1_patients 
                             WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
                             AND date_of_birth = ? 
                             AND deleted_at IS NULL
                             LIMIT 1");
        $stmt->execute([$fullName, $dateOfBirth]);
    } else {
        // Check only records added by current staff member
        $stmt = $pdo->prepare("SELECT id, full_name, date_of_birth, contact, age, gender, sitio 
                             FROM sitio1_patients 
                             WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
                             AND date_of_birth = ? 
                             AND added_by = ?
                             AND deleted_at IS NULL
                             LIMIT 1");
        $stmt->execute([$fullName, $dateOfBirth, $_SESSION['user']['id']]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Patient found - return exists with details
        echo json_encode([
            'exists' => true,
            'patient' => [
                'id' => $result['id'],
                'full_name' => $result['full_name'],
                'date_of_birth' => $result['date_of_birth'],
                'age' => $result['age'],
                'gender' => $result['gender'],
                'contact' => $result['contact'],
                'sitio' => $result['sitio']
            ]
        ]);
    } else {
        // No duplicate found
        echo json_encode(['exists' => false]);
    }
    
} catch (PDOException $e) {
    error_log("Duplicate check API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'exists' => false]);
}
?>
