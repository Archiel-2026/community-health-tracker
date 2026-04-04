<?php
/**
 * Activity Logger Helper
 * Centralized function to log all user activities across the system
 * Supports: residents, staff, and admin actions
 */

if (!function_exists('logActivity')) {
    /**
     * Log an activity/action by a user
     * 
     * @param PDO $pdo Database connection
     * @param int $userId User/Staff ID performing the action
     * @param string $actionType Type of action (e.g., 'login', 'accept_announcement', 'generate_report')
     * @param string $userType Type of user ('user', 'staff', 'admin')
     * @param int|null $relatedId Related record ID (e.g., announcement_id, patient_id)
     * @param array $details Additional details as JSON
     * @return bool Success status
     */
    function logActivity($pdo, $userId, $actionType, $userType = 'user', $relatedId = null, $details = []) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Ensure the activity log table exists
            ensureActivityLogTable($pdo);
            
            // Use the appropriate table based on user type
            if ($userType === 'staff') {
                $stmt = $pdo->prepare("
                    INSERT INTO staff_activity_log 
                    (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $actionType,
                    $relatedId,
                    json_encode($details),
                    $ip,
                    $ua
                ]);
            } elseif ($userType === 'admin') {
                // Log to sitio1_activity_log for admin actions
                $stmt = $pdo->prepare("
                    INSERT INTO sitio1_activity_log 
                    (user_id, action, details, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $actionType,
                    isset($details['description']) ? $details['description'] : json_encode($details),
                    $ip
                ]);
            } else {
                // Log to user_activity_log for resident actions
                // For announcement actions, also log to a dedicated table if needed
                
                // First, log to user_activity_log (for login/logout compatibility)
                $stmt = $pdo->prepare("
                    INSERT INTO user_activity_log 
                    (user_id, action_type, action_timestamp, ip_address, user_agent) 
                    VALUES (?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $actionType,
                    $ip,
                    $ua
                ]);
                
                // Also log to resident_activity_log for comprehensive tracking
                ensureResidentActivityLogTable($pdo);
                $stmt = $pdo->prepare("
                    INSERT INTO resident_activity_log 
                    (resident_id, action_type, related_id, details, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $actionType,
                    $relatedId,
                    json_encode($details),
                    $ip,
                    $ua
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Activity logging error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('ensureActivityLogTable')) {
    function ensureActivityLogTable($pdo) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS staff_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    staff_id INT,
                    action_type VARCHAR(100),
                    related_id INT,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_staff_id (staff_id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action_type VARCHAR(50),
                    action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    INDEX idx_user_id (user_id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_timestamp (action_timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS sitio1_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT DEFAULT NULL,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    ip_address VARCHAR(45),
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_action (action),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            );");
        } catch (Exception $e) {
            error_log('Error creating activity log tables: ' . $e->getMessage());
        }
    }
}

if (!function_exists('ensureResidentActivityLogTable')) {
    function ensureResidentActivityLogTable($pdo) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS resident_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    resident_id INT NOT NULL,
                    action_type VARCHAR(100) NOT NULL,
                    related_id INT,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (resident_id) REFERENCES sitio1_users(id) ON DELETE CASCADE,
                    INDEX idx_resident_id (resident_id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Exception $e) {
            error_log('Error creating resident activity log table: ' . $e->getMessage());
        }
    }
}

if (!function_exists('getActivityLogs')) {
    /**
     * Retrieve activity logs for display
     * 
     * @param PDO $pdo Database connection
     * @param string $userType Type of logs to retrieve ('user', 'staff', 'admin', or 'all')
     * @param int|null $limit Number of records to fetch
     * @param int $offset Pagination offset
     * @return array Activity logs
     */
    function getActivityLogs($pdo, $userType = 'user', $limit = 50, $offset = 0) {
        try {
            $logs = [];
            
            if ($userType === 'user' || $userType === 'all') {
                // Get resident activity logs (combine from both tables for comprehensive view)
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            'resident' as log_type,
                            r.id,
                            r.resident_id as user_id,
                            u.full_name as user_name,
                            r.action_type,
                            r.related_id,
                            r.details,
                            r.ip_address,
                            r.user_agent,
                            r.created_at
                        FROM resident_activity_log r
                        LEFT JOIN sitio1_users u ON r.resident_id = u.id
                        ORDER BY r.created_at DESC
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$limit, $offset]);
                    $logs = array_merge($logs, $stmt->fetchAll(PDO::FETCH_ASSOC));
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                // Also check user_activity_log for login/logout actions
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            'user_login' as log_type,
                            u.id,
                            u.id as user_id,
                            u.full_name as user_name,
                            ual.action_type,
                            NULL as related_id,
                            NULL as details,
                            ual.ip_address,
                            ual.user_agent,
                            ual.action_timestamp as created_at
                        FROM user_activity_log ual
                        LEFT JOIN sitio1_users u ON ual.user_id = u.id
                        ORDER BY ual.action_timestamp DESC
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$limit, $offset]);
                    $logs = array_merge($logs, $stmt->fetchAll(PDO::FETCH_ASSOC));
                } catch (Exception $e) {
                    // Table might not exist yet
                }
            }
            
            if ($userType === 'staff' || $userType === 'all') {
                // Get staff activity logs
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            'staff' as log_type,
                            sal.id,
                            sal.staff_id as user_id,
                            s.full_name as user_name,
                            sal.action_type,
                            sal.related_id,
                            sal.details,
                            sal.ip_address,
                            sal.user_agent,
                            sal.created_at
                        FROM staff_activity_log sal
                        LEFT JOIN sitio1_staff s ON sal.staff_id = s.id
                        ORDER BY sal.created_at DESC
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$limit, $offset]);
                    $logs = array_merge($logs, $stmt->fetchAll(PDO::FETCH_ASSOC));
                } catch (Exception $e) {
                    // Table might not exist yet
                }
            }
            
            if ($userType === 'admin' || $userType === 'all') {
                // Get admin activity logs
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            'admin' as log_type,
                            sal.id,
                            sal.user_id,
                            a.full_name as user_name,
                            sal.action as action_type,
                            NULL as related_id,
                            sal.details,
                            sal.ip_address,
                            NULL as user_agent,
                            sal.created_at
                        FROM sitio1_activity_log sal
                        LEFT JOIN sitio1_admins a ON sal.user_id = a.id
                        ORDER BY sal.created_at DESC
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$limit, $offset]);
                    $logs = array_merge($logs, $stmt->fetchAll(PDO::FETCH_ASSOC));
                } catch (Exception $e) {
                    // Table might not exist yet
                }
            }
            
            // Sort combined logs by created_at in descending order
            if (count($logs) > 1) {
                usort($logs, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }
            
            return $logs;
        } catch (Exception $e) {
            error_log('Error retrieving activity logs: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('formatActionType')) {
    /**
     * Format action type for display
     * 
     * @param string $actionType Raw action type
     * @return array Array with 'label' and 'icon' keys
     */
    function formatActionType($actionType) {
        $actions = [
            // Authentication
            'login' => ['label' => 'Login', 'icon' => 'fa-sign-in-alt', 'color' => 'blue'],
            'logout' => ['label' => 'Logout', 'icon' => 'fa-sign-out-alt', 'color' => 'gray'],
            'staff_login' => ['label' => 'Staff Login', 'icon' => 'fa-sign-in-alt', 'color' => 'blue'],
            'staff_logout' => ['label' => 'Staff Logout', 'icon' => 'fa-sign-out-alt', 'color' => 'gray'],
            
            // Announcements
            'accept_announcement' => ['label' => 'Accepted Announcement', 'icon' => 'fa-check-circle', 'color' => 'green'],
            'dismiss_announcement' => ['label' => 'Dismissed Announcement', 'icon' => 'fa-times-circle', 'color' => 'red'],
            'view_announcement' => ['label' => 'Viewed Announcement', 'icon' => 'fa-eye', 'color' => 'cyan'],
            'send_announcement' => ['label' => 'Sent Announcement', 'icon' => 'fa-bullhorn', 'color' => 'purple'],
            'create_announcement' => ['label' => 'Created Announcement', 'icon' => 'fa-bullhorn', 'color' => 'purple'],
            'edit_announcement' => ['label' => 'Edited Announcement', 'icon' => 'fa-edit', 'color' => 'orange'],
            'delete_announcement' => ['label' => 'Deleted Announcement', 'icon' => 'fa-trash', 'color' => 'red'],
            
            // Patient Management
            'add_patient' => ['label' => 'Added Patient', 'icon' => 'fa-user-plus', 'color' => 'green'],
            'edit_patient' => ['label' => 'Edited Patient', 'icon' => 'fa-user-edit', 'color' => 'orange'],
            'update_patient' => ['label' => 'Updated Patient', 'icon' => 'fa-save', 'color' => 'blue'],
            'archive_patient' => ['label' => 'Archived Patient', 'icon' => 'fa-archive', 'color' => 'gray'],
            'delete_patient' => ['label' => 'Deleted Patient', 'icon' => 'fa-trash', 'color' => 'red'],
            'print_patient' => ['label' => 'Printed Patient Record', 'icon' => 'fa-print', 'color' => 'cyan'],
            'print_record' => ['label' => 'Printed Record', 'icon' => 'fa-print', 'color' => 'cyan'],
            
            // Reports & Exports
            'generate_report' => ['label' => 'Generated Report', 'icon' => 'fa-chart-bar', 'color' => 'indigo'],
            'export_pdf' => ['label' => 'Exported to PDF', 'icon' => 'fa-file-pdf', 'color' => 'red'],
            'export_excel' => ['label' => 'Exported to Excel', 'icon' => 'fa-file-excel', 'color' => 'green'],
            'export_bulk_pdf' => ['label' => 'Bulk Exported to PDF', 'icon' => 'fa-file-pdf', 'color' => 'red'],
            'export_bulk_excel' => ['label' => 'Bulk Exported to Excel', 'icon' => 'fa-file-excel', 'color' => 'green'],
            'export_patient' => ['label' => 'Exported Patient', 'icon' => 'fa-download', 'color' => 'blue'],
            
            // Records
            'add_consultation_note' => ['label' => 'Added Consultation Note', 'icon' => 'fa-notes-medical', 'color' => 'blue'],
            'add_record' => ['label' => 'Added Record', 'icon' => 'fa-plus-square', 'color' => 'green'],
            
            // Account Management
            'password_change' => ['label' => 'Changed Password', 'icon' => 'fa-key', 'color' => 'orange'],
            'password_reset' => ['label' => 'Reset Password', 'icon' => 'fa-key', 'color' => 'orange'],
            'resident_created' => ['label' => 'Created Resident Account', 'icon' => 'fa-user-plus', 'color' => 'green'],
            'resident_deleted' => ['label' => 'Deleted Resident Account', 'icon' => 'fa-user-times', 'color' => 'red'],
            'resident_status_change' => ['label' => 'Changed Resident Status', 'icon' => 'fa-user-check', 'color' => 'orange'],
            'staff_created' => ['label' => 'Created Staff Account', 'icon' => 'fa-user-plus', 'color' => 'green'],
            'staff_deleted' => ['label' => 'Deleted Staff Account', 'icon' => 'fa-user-times', 'color' => 'red'],
            'staff_status_change' => ['label' => 'Changed Staff Status', 'icon' => 'fa-user-check', 'color' => 'orange'],
            'account_linking' => ['label' => 'Linked Account', 'icon' => 'fa-link', 'color' => 'blue'],
        ];
        
        return $actions[$actionType] ?? [
            'label' => ucfirst(str_replace('_', ' ', $actionType)),
            'icon' => 'fa-info-circle',
            'color' => 'gray'
        ];
    }
}
