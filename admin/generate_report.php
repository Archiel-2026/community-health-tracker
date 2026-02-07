<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

// Collect data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM sitio1_patients WHERE deleted_at IS NULL");
    $totalPatients = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM sitio1_staff WHERE is_active = 1");
    $totalStaff = $stmt->fetchColumn() ?: 0;

    // Get top 200 patients
    $stmt = $pdo->query("SELECT id, full_name, age, gender, sitio, created_at FROM sitio1_patients WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 200");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $patients = [];
    $totalPatients = 0;
    $totalStaff = 0;
}

// Activity Logs (Resident and Staff)
$resident_logs = [];
$staff_logs = [];
$logFile = __DIR__ . '/../logs/save_actions.log';
$userNames = [];
$staffNames = [];

if (file_exists($logFile)) {
    $lines = array_filter(array_map('trim', array_slice(file($logFile), -500)));
    foreach ($lines as $line) {
        $decoded = @json_decode($line, true);
        if (!$decoded) continue;

        if (isset($decoded['timestamp'])) $decoded['created_at'] = $decoded['timestamp'];

        // Resident login/logout
        if (!empty($decoded['type']) && in_array($decoded['type'], ['login', 'logout'])) {
            $uid = $decoded['user_id'] ?? null;
            $decoded['display_name'] = 'User #' . ($uid ?? '');
            if ($uid && !isset($userNames[$uid])) {
                try {
                    $stmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                    $stmt->execute([$uid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['full_name'])) $userNames[$uid] = $row['full_name'];
                } catch (Exception $e) {}
            }
            if ($uid && !empty($userNames[$uid])) $decoded['display_name'] = $userNames[$uid];
            $resident_logs[] = $decoded;
            continue;
        }

        // Staff action entries
        if (!empty($decoded['staff_id']) || (!empty($decoded['type']) && strpos($decoded['type'], 'staff') !== false) || !empty($decoded['staff'])) {
            $sid = $decoded['staff_id'] ?? $decoded['staff'] ?? null;
            $decoded['display_name'] = 'Staff #' . ($sid ?? '');
            if ($sid && !isset($staffNames[$sid])) {
                try {
                    $stmt = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                    $stmt->execute([$sid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['full_name'])) $staffNames[$sid] = $row['full_name'];
                } catch (Exception $e) {}
            }
            if ($sid && !empty($staffNames[$sid])) $decoded['display_name'] = $staffNames[$sid];
            $staff_logs[] = $decoded;
            continue;
        }
    }
}

// Pull from user_activity_log DB
try {
    $stmt = $pdo->query("SELECT user_id, action_type, action_timestamp, ip_address, user_agent FROM user_activity_log WHERE action_type IN ('login','logout') ORDER BY action_timestamp DESC LIMIT 500");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $r['type'] = $r['action_type'];
        $r['created_at'] = $r['action_timestamp'];
        $uid = $r['user_id'];
        $r['display_name'] = 'User #' . $uid;
        if ($uid && !isset($userNames[$uid])) {
            try {
                $stmt2 = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                $stmt2->execute([$uid]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['full_name'])) $userNames[$uid] = $row['full_name'];
            } catch (Exception $e) {}
        }
        if ($uid && !empty($userNames[$uid])) $r['display_name'] = $userNames[$uid];
        $resident_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

// Pull from staff_activity_log DB
try {
    $stmt = $pdo->query("SELECT staff_id, action_type, related_id, details, created_at, ip_address FROM staff_activity_log ORDER BY created_at DESC LIMIT 500");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sid = $r['staff_id'];
        $r['type'] = $r['action_type'];
        $r['display_name'] = 'Staff #' . $sid;

        if (!empty($r['details'])) {
            $detailsData = json_decode($r['details'], true);
            if (isset($detailsData['full_name']) && !empty($detailsData['full_name'])) {
                $staffNames[$sid] = $detailsData['full_name'];
            }
        }

        if ($sid && !isset($staffNames[$sid])) {
            try {
                $stmt2 = $pdo->prepare("SELECT full_name FROM sitio1_staff WHERE id = ?");
                $stmt2->execute([$sid]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['full_name'])) $staffNames[$sid] = $row['full_name'];
            } catch (Exception $e) {}
        }
        if ($sid && !empty($staffNames[$sid])) $r['display_name'] = $staffNames[$sid];
        $staff_logs[] = $r;
    }
} catch (Exception $e) {
    // ignore
}

usort($resident_logs, function($a, $b) { $ta = strtotime($a['created_at'] ?? 0); $tb = strtotime($b['created_at'] ?? 0); return $tb <=> $ta; });
usort($staff_logs, function($a, $b) { $ta = strtotime($a['created_at'] ?? 0); $tb = strtotime($b['created_at'] ?? 0); return $tb <=> $ta; });

$resident_logs = array_slice($resident_logs, 0, 300);
$staff_logs = array_slice($staff_logs, 0, 300);

// Additional analytics for report
try {
    // Patients per month (last 12 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM sitio1_patients WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND deleted_at IS NULL GROUP BY month ORDER BY month ASC");
    $patients_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consultations per month (last 12 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(consultation_date, '%Y-%m') as month, COUNT(*) as cnt FROM consultation_notes WHERE consultation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
    $consults_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top diseases overall
    $stmt = $pdo->query("SELECT disease, COUNT(*) as cnt FROM sitio1_patients WHERE disease IS NOT NULL AND disease <> '' GROUP BY disease ORDER BY cnt DESC LIMIT 10");
    $top_diseases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Doctor consult counts
    $stmt = $pdo->query("SELECT s.full_name AS doctor, COUNT(*) as cnt FROM consultation_notes cn JOIN sitio1_staff s ON cn.created_by = s.id GROUP BY s.id ORDER BY cnt DESC");
    $doctor_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resident visit counts (per resident, top 50)
    $stmt = $pdo->query("SELECT p.full_name, COUNT(*) as cnt FROM consultation_notes cn JOIN sitio1_patients p ON cn.patient_id = p.id GROUP BY p.id ORDER BY cnt DESC LIMIT 50");
    $resident_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $patients_by_month = $consults_by_month = $top_diseases = $doctor_counts = $resident_visits = [];
}

// Generate PDF report using TCPDF
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Community Health Tracker');
$pdf->SetAuthor('Community Health Tracker');
$pdf->SetTitle('Resident Logs and Staff Logs');
$pdf->SetMargins(15, 20, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$logoPath = __DIR__ . '/../asssets/images/Luz.jpg';
$logoData = '';
if (file_exists($logoPath)) {
    $logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
}

$html = '
<style>
    .report-header { width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 14px; }
    .report-title { font-size: 20px; font-weight: bold; color: #111827; margin: 0; }
    .report-subtitle { font-size: 12px; color: #4b5563; margin: 4px 0 0; }
    .section-title { font-size: 15px; font-weight: bold; margin: 16px 0 8px; color: #111827; }
    .meta-row { font-size: 11px; color: #6b7280; margin-top: 2px; }
    table.report-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    table.report-table th { background: #f3f4f6; color: #111827; text-align: left; padding: 6px; border: 1px solid #e5e7eb; }
    table.report-table td { padding: 6px; border: 1px solid #e5e7eb; color: #374151; }
    .badge { font-size: 9px; padding: 2px 6px; border-radius: 10px; background: #e0e7ff; color: #3730a3; display: inline-block; }
</style>

<table class="report-header" cellpadding="0" cellspacing="0">
    <tr>
        <td width="16%" style="vertical-align: middle;">
            ' . ($logoData ? '<img src="' . $logoData . '" style="width:56px;height:56px;border-radius:8px;" />' : '') . '
        </td>
        <td width="84%" style="vertical-align: middle;">
            <div class="report-title">Resident Logs and Staff Logs</div>
            <div class="report-subtitle">Barangay Luz Health Center • Cebu City</div>
            <div class="meta-row">Generated: ' . date('F j, Y g:i A') . '</div>
        </td>
    </tr>
</table>
';

$html .= '<div class="section-title">Resident Logs</div>';
$html .= '<table class="report-table"><thead><tr><th width="22%">Time</th><th width="26%">Resident</th><th width="16%">Action</th><th width="18%">IP</th><th width="18%">Source</th></tr></thead><tbody>';
if (count($resident_logs) === 0) {
    $html .= '<tr><td colspan="5">No resident logs available.</td></tr>';
} else {
    foreach ($resident_logs as $log) {
        $time = !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A';
        $name = htmlspecialchars($log['display_name'] ?? 'Unknown');
        $action = htmlspecialchars($log['type'] ?? $log['action_type'] ?? '');
        $ip = htmlspecialchars($log['ip'] ?? $log['ip_address'] ?? '—');
        $source = !empty($log['action_timestamp']) ? 'DB' : 'Log File';
        $html .= '<tr><td>' . $time . '</td><td>' . $name . '</td><td>' . $action . '</td><td>' . $ip . '</td><td>' . $source . '</td></tr>';
    }
}
$html .= '</tbody></table>';

$html .= '<div class="section-title">Staff Logs</div>';
$html .= '<table class="report-table"><thead><tr><th width="20%">Time</th><th width="22%">Staff</th><th width="18%">Action</th><th width="24%">Details</th><th width="16%">IP</th></tr></thead><tbody>';
if (count($staff_logs) === 0) {
    $html .= '<tr><td colspan="5">No staff logs available.</td></tr>';
} else {
    foreach ($staff_logs as $log) {
        $time = !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : 'N/A';
        $name = htmlspecialchars($log['display_name'] ?? 'Unknown');
        $action = htmlspecialchars(ucwords(str_replace('_', ' ', $log['type'] ?? $log['action_type'] ?? '')));
        $details = htmlspecialchars(is_array($log['details'] ?? null) || is_object($log['details'] ?? null) ? json_encode($log['details']) : ($log['details'] ?? ($log['related_id'] ? 'Record #' . $log['related_id'] : '—')));
        $ip = htmlspecialchars($log['ip_address'] ?? $log['ip'] ?? '—');
        $html .= '<tr><td>' . $time . '</td><td>' . $name . '</td><td>' . $action . '</td><td>' . $details . '</td><td>' . $ip . '</td></tr>';
    }
}
$html .= '</tbody></table>';

$html .= '<div class="section-title">Summary</div>';
$html .= '<ul>';
$html .= '<li>Total Patients: ' . intval($totalPatients) . '</li>';
$html .= '<li>Total Active Staff: ' . intval($totalStaff) . '</li>';
$html .= '</ul>';

// Monthly patient trends
$html .= '<h3>Monthly Patient Records (Last 12 months)</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>Month</b></th><th><b>Count</b></th></tr></thead><tbody>';
foreach ($patients_by_month as $m) {
    $html .= '<tr><td>' . htmlspecialchars($m['month']) . '</td><td>' . intval($m['cnt']) . '</td></tr>';
}
$html .= '</tbody></table>';

// Consultation trends
$html .= '<h3>Monthly Consultations (Last 12 months)</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>Month</b></th><th><b>Count</b></th></tr></thead><tbody>';
foreach ($consults_by_month as $m) {
    $html .= '<tr><td>' . htmlspecialchars($m['month']) . '</td><td>' . intval($m['cnt']) . '</td></tr>';
}
$html .= '</tbody></table>';

// Health issue trends - top diseases
$html .= '<h3>Top Diseases</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>Disease</b></th><th><b>Count</b></th></tr></thead><tbody>';
foreach ($top_diseases as $d) {
    $html .= '<tr><td>' . htmlspecialchars($d['disease']) . '</td><td>' . intval($d['cnt']) . '</td></tr>';
}
$html .= '</tbody></table>';

// Doctor consults
$html .= '<h3>Consultations by Doctor</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>Doctor</b></th><th><b>Consultation Count</b></th></tr></thead><tbody>';
foreach ($doctor_counts as $doc) {
    $html .= '<tr><td>' . htmlspecialchars($doc['doctor']) . '</td><td>' . intval($doc['cnt']) . '</td></tr>';
}
$html .= '</tbody></table>';

// Resident visit counts (top residents)
$html .= '<h3>Resident Visit Counts (Top 50)</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>Name</b></th><th><b>Visits</b></th></tr></thead><tbody>';
foreach ($resident_visits as $rv) {
    $html .= '<tr><td>' . htmlspecialchars($rv['full_name']) . '</td><td>' . intval($rv['cnt']) . '</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<h3>Recent Patients (up to 200)</h3>';
$html .= '<table border="1" cellpadding="4"><thead><tr><th><b>ID</b></th><th><b>Name</b></th><th><b>Age</b></th><th><b>Gender</b></th><th><b>Sitio</b></th><th><b>Created</b></th></tr></thead><tbody>';
foreach ($patients as $p) {
    $html .= '<tr><td>' . $p['id'] . '</td><td>' . htmlspecialchars($p['full_name']) . '</td><td>' . ($p['age'] ?: '') . '</td><td>' . ($p['gender'] ?: '') . '</td><td>' . ($p['sitio'] ?: '') . '</td><td>' . ($p['created_at'] ?: '') . '</td></tr>';
}
$html .= '</tbody></table>';



$pdf->writeHTML($html, true, false, true, false, '');

$filename = 'CHT_Report_' . date('Ymd_His') . '.pdf';

$pdf->Output($filename, 'D');
exit();
