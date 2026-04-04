<?php
/**
 * API Endpoint: Get Activity Logs
 * Fetches activity logs from Staff and Resident tables
 * Only accessible by staff/admin users
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();

// Check if user is staff or admin
if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

global $pdo;

header('Content-Type: application/json');

try {
    // Get query parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $logType = isset($_GET['type']) ? $_GET['type'] : 'resident,admin'; // Support comma-separated types
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    // Parse the log type - can be single or comma-separated
    $logTypes = array_map('trim', explode(',', $logType));
    
    // Ensure 'all' expands to include all types
    if (in_array('all', $logTypes)) {
        $logTypes = ['staff', 'resident', 'admin'];
    }

    $logs = [];
    $totalCount = 0;

    // Fetch Staff Activity Logs
    if (in_array('staff', $logTypes)) {
        $query = "SELECT 
                    id, 
                    staff_id, 
                    action_type, 
                    related_id, 
                    details, 
                    ip_address, 
                    created_at,
                    'staff' as log_type
                  FROM staff_activity_log";
        
        $params = [];
        if (!empty($searchTerm)) {
            $query .= " WHERE action_type LIKE ? OR details LIKE ?";
            $params = ["%$searchTerm%", "%$searchTerm%"];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $staffLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for staff logs
        $countQuery = "SELECT COUNT(*) as total FROM staff_activity_log";
        if (!empty($searchTerm)) {
            $countQuery .= " WHERE action_type LIKE ? OR details LIKE ?";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute(["%$searchTerm%", "%$searchTerm%"]);
        } else {
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute();
        }
        $totalCount += $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $logs = array_merge($logs, $staffLogs);
    }

    // Fetch Resident Activity Logs
    if (in_array('resident', $logTypes)) {
        $query = "SELECT 
                    id, 
                    resident_id as user_id, 
                    action_type, 
                    related_id, 
                    details, 
                    ip_address, 
                    created_at,
                    'resident' as log_type
                  FROM resident_activity_log";
        
        $params = [];
        if (!empty($searchTerm)) {
            $query .= " WHERE action_type LIKE ? OR details LIKE ?";
            $params = ["%$searchTerm%", "%$searchTerm%"];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $residentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for resident logs
        $countQuery = "SELECT COUNT(*) as total FROM resident_activity_log";
        if (!empty($searchTerm)) {
            $countQuery .= " WHERE action_type LIKE ? OR details LIKE ?";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute(["%$searchTerm%", "%$searchTerm%"]);
        } else {
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute();
        }
        $totalCount += $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $logs = array_merge($logs, $residentLogs);
    }

    // Fetch Admin Activity Logs
    if (in_array('admin', $logTypes)) {
        $query = "SELECT 
                    id, 
                    user_id, 
                    action, 
                    details, 
                    ip_address, 
                    created_at,
                    'admin' as log_type
                  FROM sitio1_activity_log";
        
        $params = [];
        if (!empty($searchTerm)) {
            $query .= " WHERE action LIKE ? OR details LIKE ?";
            $params = ["%$searchTerm%", "%$searchTerm%"];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $adminLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for admin logs
        $countQuery = "SELECT COUNT(*) as total FROM sitio1_activity_log";
        if (!empty($searchTerm)) {
            $countQuery .= " WHERE action LIKE ? OR details LIKE ?";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute(["%$searchTerm%", "%$searchTerm%"]);
        } else {
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute();
        }
        $totalCount += $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $logs = array_merge($logs, $adminLogs);
    }

    // Sort all logs by created_at descending
    usort($logs, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Format logs for display
    $formattedLogs = array_map(function($log) {
        return [
            'id' => $log['id'],
            'user_id' => $log['user_id'] ?? $log['staff_id'] ?? null,
            'action_type' => $log['action_type'] ?? $log['action'] ?? 'Unknown',
            'related_id' => $log['related_id'] ?? null,
            'details' => $log['details'] ?? null,
            'ip_address' => $log['ip_address'] ?? 'N/A',
            'created_at' => $log['created_at'],
            'formatted_time' => date('M d, Y h:i A', strtotime($log['created_at'])),
            'log_type' => $log['log_type']
        ];
    }, $logs);

    echo json_encode([
        'success' => true,
        'data' => $formattedLogs,
        'total' => $totalCount,
        'count' => count($formattedLogs),
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>