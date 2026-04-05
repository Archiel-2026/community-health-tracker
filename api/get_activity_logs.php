<?php
/**
 * API Endpoint: Get Activity Logs
 * Provides activity log data for the staff dashboard activity log tab.
 */

require_once __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();

if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

global $pdo;

header('Content-Type: application/json');

function safeJsonDecode($value)
{
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function tableExists($pdo, $tableName)
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $cache[$tableName] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function resolveAdminName($pdo, $actorId, $fallbackName, $details)
{
    if (!empty($fallbackName)) {
        return $fallbackName;
    }

    if (!empty($details['full_name'])) {
        return $details['full_name'];
    }

    if (!$actorId) {
        return 'Unknown Admin';
    }

    $candidateTables = [
        ['table' => 'sitio1_staff', 'column' => 'full_name'],
        ['table' => 'sitio1_admins', 'column' => 'full_name'],
        ['table' => 'admin', 'column' => 'full_name'],
        ['table' => 'admin', 'column' => 'username'],
        ['table' => 'sitio1_users', 'column' => 'full_name'],
        ['table' => 'sitio1_users', 'column' => 'username'],
    ];

    foreach ($candidateTables as $candidate) {
        if (!tableExists($pdo, $candidate['table'])) {
            continue;
        }

        try {
            $stmt = $pdo->prepare("SELECT {$candidate['column']} AS actor_name FROM {$candidate['table']} WHERE id = ? LIMIT 1");
            $stmt->execute([$actorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($row['actor_name'])) {
                return $row['actor_name'];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return 'Unknown Admin';
}

function matchesSearch($log, $searchTerm)
{
    if ($searchTerm === '') {
        return true;
    }

    $haystacks = [
        $log['actor_name'] ?? '',
        $log['action_type'] ?? '',
        $log['description'] ?? '',
        $log['ip_address'] ?? '',
    ];

    foreach ($haystacks as $haystack) {
        if (stripos((string) $haystack, $searchTerm) !== false) {
            return true;
        }
    }

    return false;
}

function formatResidentDescription($actionType, $details)
{
    $title = $details['announcement_title'] ?? $details['title'] ?? null;

    if ($actionType === 'login') {
        return 'Resident logged in';
    }

    if ($actionType === 'logout') {
        return 'Resident logged out';
    }

    if ($actionType === 'view_announcement') {
        return $title ? 'Viewed announcement: ' . $title : 'Viewed announcement';
    }

    if ($actionType === 'accept_announcement') {
        return $title ? 'Accepted announcement: ' . $title : 'Accepted announcement';
    }

    if ($actionType === 'dismiss_announcement') {
        return $title ? 'Dismissed announcement: ' . $title : 'Dismissed announcement';
    }

    return ucfirst(str_replace('_', ' ', $actionType));
}

function formatAdminDescription($actionType, $details)
{
    $patientName = $details['patient_name'] ?? null;
    $announcementTitle = $details['announcement_title'] ?? $details['title'] ?? null;
    $targetUsers = $details['target_users'] ?? null;
    $actionLabel = ucfirst(str_replace('_', ' ', $actionType));

    switch ($actionType) {
        case 'add_patient':
            return $patientName ? 'Added new patient record for ' . $patientName : 'Added new patient record';
        case 'view_patient':
            return $patientName ? 'Viewed patient record for ' . $patientName : 'Viewed patient record';
        case 'edit_patient':
        case 'update_patient':
            return $patientName ? 'Edited patient record for ' . $patientName : 'Edited patient record';
        case 'archive_patient':
            return $patientName ? 'Archived patient record for ' . $patientName : 'Archived patient record';
        case 'print_patient':
            return $patientName ? 'Printed patient record for ' . $patientName : 'Printed patient record';
        case 'export_pdf':
            return $patientName ? 'Exported patient record to PDF for ' . $patientName : 'Exported patient records to PDF';
        case 'export_excel':
            return $patientName ? 'Exported patient record to Excel for ' . $patientName : 'Exported patient records to Excel';
        case 'send_announcement':
            if ($announcementTitle && $targetUsers) {
                return 'Posted announcement "' . $announcementTitle . '" to ' . $targetUsers;
            }
            if ($announcementTitle) {
                return 'Posted announcement: ' . $announcementTitle;
            }
            return 'Posted announcement';
        case 'edit_announcement':
            return $announcementTitle ? 'Edited announcement: ' . $announcementTitle : 'Edited announcement';
        case 'delete_announcement':
            if (($details['action'] ?? '') === 'archived') {
                return $announcementTitle ? 'Archived announcement: ' . $announcementTitle : 'Archived announcement';
            }
            return $announcementTitle ? 'Deleted announcement: ' . $announcementTitle : 'Deleted announcement';
        case 'search_announcement':
            $term = $details['search_term'] ?? null;
            return $term ? 'Searched announcements for "' . $term . '"' : 'Searched announcements';
        default:
            return $actionLabel;
    }
}

try {
    $category = isset($_GET['category']) ? trim($_GET['category']) : 'resident';
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 50;
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

    $residentActions = ['view_announcement', 'accept_announcement', 'dismiss_announcement'];
    $residentAuthActions = ['login', 'logout'];
    $adminActions = [
        'add_patient',
        'view_patient',
        'edit_patient',
        'update_patient',
        'archive_patient',
        'print_patient',
        'export_pdf',
        'export_excel',
        'send_announcement',
        'edit_announcement',
        'delete_announcement',
        'search_announcement',
    ];

    $logs = [];

    if ($category === 'resident') {
        $residentActionPlaceholders = implode(',', array_fill(0, count($residentActions), '?'));
        $residentSql = "
            SELECT
                ral.id,
                ral.resident_id AS actor_id,
                u.full_name AS actor_name,
                ral.action_type,
                ral.details,
                ral.ip_address,
                ral.created_at,
                'resident' AS source_type
            FROM resident_activity_log ral
            LEFT JOIN sitio1_users u ON u.id = ral.resident_id
            WHERE ral.action_type IN ($residentActionPlaceholders)
            ORDER BY ral.created_at DESC
            LIMIT 500
        ";
        $stmt = $pdo->prepare($residentSql);
        $stmt->execute($residentActions);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $details = safeJsonDecode($row['details']);
            $logs[] = [
                'id' => 'resident-' . $row['id'],
                'actor_id' => $row['actor_id'],
                'actor_name' => $row['actor_name'] ?: 'Unknown Resident',
                'action_type' => $row['action_type'],
                'description' => formatResidentDescription($row['action_type'], $details),
                'details' => $details,
                'ip_address' => $row['ip_address'] ?: 'N/A',
                'created_at' => $row['created_at'],
                'formatted_date' => date('M d, Y', strtotime($row['created_at'])),
                'formatted_time' => date('h:i A', strtotime($row['created_at'])),
                'source_type' => 'resident',
            ];
        }

        $residentAuthPlaceholders = implode(',', array_fill(0, count($residentAuthActions), '?'));
        $authSql = "
            SELECT
                ual.id,
                ual.user_id AS actor_id,
                u.full_name AS actor_name,
                ual.action_type,
                ual.ip_address,
                ual.action_timestamp AS created_at
            FROM user_activity_log ual
            LEFT JOIN sitio1_users u ON u.id = ual.user_id
            WHERE ual.action_type IN ($residentAuthPlaceholders)
            ORDER BY ual.action_timestamp DESC
            LIMIT 500
        ";
        $stmt = $pdo->prepare($authSql);
        $stmt->execute($residentAuthActions);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $logs[] = [
                'id' => 'auth-' . $row['id'],
                'actor_id' => $row['actor_id'],
                'actor_name' => $row['actor_name'] ?: 'Unknown Resident',
                'action_type' => $row['action_type'],
                'description' => formatResidentDescription($row['action_type'], []),
                'details' => [],
                'ip_address' => $row['ip_address'] ?: 'N/A',
                'created_at' => $row['created_at'],
                'formatted_date' => date('M d, Y', strtotime($row['created_at'])),
                'formatted_time' => date('h:i A', strtotime($row['created_at'])),
                'source_type' => 'resident-auth',
            ];
        }
    } elseif ($category === 'admin') {
        $adminPlaceholders = implode(',', array_fill(0, count($adminActions), '?'));

        $staffSql = "
            SELECT
                sal.id,
                sal.staff_id AS actor_id,
                s.full_name AS actor_name,
                sal.action_type,
                sal.details,
                sal.ip_address,
                sal.created_at,
                'staff' AS source_type
            FROM staff_activity_log sal
            LEFT JOIN sitio1_staff s ON s.id = sal.staff_id
            WHERE sal.action_type IN ($adminPlaceholders)
            ORDER BY sal.created_at DESC
            LIMIT 500
        ";
        $stmt = $pdo->prepare($staffSql);
        $stmt->execute($adminActions);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $details = safeJsonDecode($row['details']);
            $logs[] = [
                'id' => 'staff-' . $row['id'],
                'actor_id' => $row['actor_id'],
                'actor_name' => resolveAdminName($pdo, $row['actor_id'], $row['actor_name'] ?? '', $details),
                'action_type' => $row['action_type'],
                'description' => formatAdminDescription($row['action_type'], $details),
                'details' => $details,
                'ip_address' => $row['ip_address'] ?: 'N/A',
                'created_at' => $row['created_at'],
                'formatted_date' => date('M d, Y', strtotime($row['created_at'])),
                'formatted_time' => date('h:i A', strtotime($row['created_at'])),
                'source_type' => 'staff',
            ];
        }

        if (tableExists($pdo, 'sitio1_activity_log')) {
            $adminSql = "
                SELECT
                    al.id,
                    al.user_id AS actor_id,
                    NULL AS actor_name,
                    al.action AS action_type,
                    al.details,
                    al.ip_address,
                    al.created_at,
                    'admin' AS source_type
                FROM sitio1_activity_log al
                WHERE al.action IN ($adminPlaceholders)
                ORDER BY al.created_at DESC
                LIMIT 500
            ";
            $stmt = $pdo->prepare($adminSql);
            $stmt->execute($adminActions);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $details = safeJsonDecode($row['details']);
                $logs[] = [
                    'id' => 'admin-' . $row['id'],
                    'actor_id' => $row['actor_id'],
                    'actor_name' => resolveAdminName($pdo, $row['actor_id'], '', $details),
                    'action_type' => $row['action_type'],
                    'description' => formatAdminDescription($row['action_type'], $details),
                    'details' => $details,
                    'ip_address' => $row['ip_address'] ?: 'N/A',
                    'created_at' => $row['created_at'],
                    'formatted_date' => date('M d, Y', strtotime($row['created_at'])),
                    'formatted_time' => date('h:i A', strtotime($row['created_at'])),
                    'source_type' => 'admin',
                ];
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category']);
        exit();
    }

    $logs = array_values(array_filter($logs, function ($log) use ($searchTerm) {
        return matchesSearch($log, $searchTerm);
    }));

    usort($logs, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    $logs = array_slice($logs, 0, $limit);

    echo json_encode([
        'success' => true,
        'category' => $category,
        'count' => count($logs),
        'data' => $logs,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
?>
