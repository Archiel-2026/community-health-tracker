<?php
// Fetch staff report generation logs for display in dashboard
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT l.id, l.staff_id, s.full_name, l.action_type, l.details, l.created_at FROM staff_activity_log l JOIN sitio1_staff s ON l.staff_id = s.id WHERE l.action_type IN ('export_bulk_pdf', 'export_bulk_excel') ORDER BY l.created_at DESC LIMIT 50");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($logs as $log) {
        $details = json_decode($log['details'], true);
        $result[] = [
            'id' => $log['id'],
            'staff_id' => $log['staff_id'],
            'full_name' => $log['full_name'],
            'action_type' => $log['action_type'],
            'created_at' => $log['created_at'],
            'export_type' => $details['export_type'] ?? '',
            'record_count' => $details['record_count'] ?? '',
        ];
    }
    echo json_encode(['success' => true, 'logs' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
