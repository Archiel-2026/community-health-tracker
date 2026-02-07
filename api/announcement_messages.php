<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

global $pdo;
header('Content-Type: application/json');

try {
    // Get messages for an announcement
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['announcement_id'])) {
        $announcementId = $_GET['announcement_id'];
        $userId = $_SESSION['user']['id'];
        
        // Verify user has access to this announcement
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM sitio1_announcements a
            LEFT JOIN announcement_targets at ON a.id = at.announcement_id
            WHERE a.id = ? 
            AND (a.staff_id = ? OR a.audience_type = 'public' OR at.user_id = ?)
        ");
        $stmt->execute([$announcementId, $userId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit();
        }
        
        // Get all messages
        $stmt = $pdo->prepare("
            SELECT 
                am.id,
                am.message,
                am.is_edited,
                am.created_at,
                am.updated_at,
                u.id as sender_id,
                u.full_name,
                u.username,
                CASE WHEN am.sender_id = ? THEN TRUE ELSE FALSE END as is_own_message
            FROM announcement_messages am
            JOIN sitio1_users u ON am.sender_id = u.id
            WHERE am.announcement_id = ?
            ORDER BY am.created_at ASC
        ");
        $stmt->execute([$userId, $announcementId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'messages' => $messages]);
    }
    
    // Post a new message
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
        $announcementId = $_POST['announcement_id'] ?? null;
        $message = trim($_POST['message'] ?? '');
        $userId = $_SESSION['user']['id'];
        
        if (!$announcementId || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }
        
        if (strlen($message) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Message too long']);
            exit();
        }
        
        // Verify user can reply to this announcement
        $stmt = $pdo->prepare("
            SELECT a.id, a.audience_type, a.staff_id
            FROM sitio1_announcements a
            LEFT JOIN announcement_targets at ON a.id = at.announcement_id
            WHERE a.id = ? 
            AND a.status = 'active'
            AND (a.audience_type = 'public' OR at.user_id = ? OR a.staff_id = ?)
        ");
        $stmt->execute([$announcementId, $userId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot reply to this announcement']);
            exit();
        }
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO announcement_messages (announcement_id, sender_id, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$announcementId, $userId, $message]);
        
        $messageId = $pdo->lastInsertId();
        
        // Get the inserted message details
        $stmt = $pdo->prepare("
            SELECT 
                am.id,
                am.message,
                am.is_edited,
                am.created_at,
                u.id as sender_id,
                u.full_name,
                u.username,
                TRUE as is_own_message
            FROM announcement_messages am
            JOIN sitio1_users u ON am.sender_id = u.id
            WHERE am.id = ?
        ");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'message' => $newMessage]);
    }
    
    // Edit a message
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_message') {
        $messageId = $_POST['message_id'] ?? null;
        $newMessage = trim($_POST['message'] ?? '');
        $userId = $_SESSION['user']['id'];
        
        if (!$messageId || empty($newMessage)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }
        
        if (strlen($newMessage) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Message too long']);
            exit();
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("
            SELECT id FROM announcement_messages WHERE id = ? AND sender_id = ?
        ");
        $stmt->execute([$messageId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot edit this message']);
            exit();
        }
        
        // Update message
        $stmt = $pdo->prepare("
            UPDATE announcement_messages 
            SET message = ?, is_edited = TRUE, updated_at = NOW()
            WHERE id = ? AND sender_id = ?
        ");
        $stmt->execute([$newMessage, $messageId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Message updated']);
    }
    
    // Delete a message
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
        $messageId = $_POST['message_id'] ?? null;
        $userId = $_SESSION['user']['id'];
        
        if (!$messageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Message ID required']);
            exit();
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("
            SELECT id FROM announcement_messages WHERE id = ? AND sender_id = ?
        ");
        $stmt->execute([$messageId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete this message']);
            exit();
        }
        
        // Delete message
        $stmt = $pdo->prepare("
            DELETE FROM announcement_messages WHERE id = ? AND sender_id = ?
        ");
        $stmt->execute([$messageId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Message deleted']);
    }
    
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>
