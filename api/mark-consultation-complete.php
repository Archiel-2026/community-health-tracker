<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is staff
if (!isset($_SESSION['user']['id']) || !isStaff()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!staffCanAccessConsultationNotes()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only Assistant Doctor accounts can manage consultation notes']);
    exit();
}

// Get the request data (supports both JSON and form data)
$input = json_decode(file_get_contents('php://input'), true);
$noteId = isset($input['note_id']) ? (int)$input['note_id'] : (isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0);

if (!$noteId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid consultation ID']);
    exit();
}

$staffId = $_SESSION['user']['id'];

try {
    // First, check if consultation exists and get its current status
    $stmt = $pdo->prepare("
        SELECT cn.*, sp.full_name as patient_name 
        FROM consultation_notes cn
        LEFT JOIN sitio1_patients sp ON cn.patient_id = sp.id
        WHERE cn.id = ?
    ");
    $stmt->execute([$noteId]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consultation) {
        echo json_encode(['success' => false, 'message' => 'Consultation not found']);
        exit();
    }

    // Check if already completed
    if ($consultation['status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Consultation is already marked as completed']);
        exit();
    }

    // Update status to 'completed' in the database
    $updateStmt = $pdo->prepare("
        UPDATE consultation_notes 
        SET status = 'completed', 
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$noteId]);
    
    // Verify the update worked
    $verifyStmt = $pdo->prepare("SELECT status FROM consultation_notes WHERE id = ?");
    $verifyStmt->execute([$noteId]);
    $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updated['status'] !== 'completed') {
        echo json_encode(['success' => false, 'message' => 'Failed to update status in database']);
        exit();
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Consultation marked as completed successfully',
        'consultation_id' => $noteId,
        'new_status' => 'completed'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
