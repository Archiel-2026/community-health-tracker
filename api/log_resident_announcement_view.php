<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_logger.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isUser()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

global $pdo;

$announcementId = isset($_POST['announcement_id']) ? (int) $_POST['announcement_id'] : 0;
$userId = $_SESSION['user']['id'] ?? 0;

if ($announcementId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title
        FROM sitio1_announcements a
        LEFT JOIN announcement_targets at ON at.announcement_id = a.id
        WHERE a.id = ?
          AND a.status = 'active'
          AND (a.audience_type = 'public' OR at.user_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$announcementId, $userId]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$announcement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Announcement not found']);
        exit();
    }

    logActivity(
        $pdo,
        $userId,
        'view_announcement',
        'user',
        $announcementId,
        [
            'announcement_title' => $announcement['title'] ?? 'Unknown',
        ]
    );

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
