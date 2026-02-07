<?php
require_once __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

global $pdo;

// Get filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$sitio = $_GET['sitio'] ?? 'all';

// Build query
$query = "SELECT 
            id,
            full_name,
            date_of_birth,
            age,
            gender,
            civil_status,
            address,
            sitio,
            contact,
            disease,
            last_checkup,
            consultation_type,
            occupation,
            created_at
          FROM sitio1_patients 
          WHERE DATE(created_at) BETWEEN ? AND ?
          AND deleted_at IS NULL";

$params = [$startDate, $endDate];

if ($sitio !== 'all') {
    $query .= " AND sitio = ?";
    $params[] = $sitio;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log staff/admin activity for exporting patients Excel
try {
    $staff_id = $_SESSION['user']['id'] ?? null;
    $staff_name = $_SESSION['user']['full_name'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT,
        action_type VARCHAR(100),
        related_id INT,
        details JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'export_excel', NULL, ?, ?, ?, NOW())");
    $stmtLog->execute([$staff_id, json_encode(['full_name' => $staff_name, 'record_count' => count($patients), 'date_range' => $startDate . ' to ' . $endDate, 'sitio' => $sitio]), $ip, $ua]);
} catch (Exception $e) {
    error_log('Staff activity log error (export_excel): ' . $e->getMessage());
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="patient_records_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Patient Records</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<style>';
echo '
    body { font-family: "Calibri", "Arial", sans-serif; font-size: 11pt; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    th { 
        background-color: #4472C4; 
        color: white; 
        border: 0.5pt solid #000000; 
        padding: 8px; 
        text-align: center; 
        vertical-align: middle;
        font-weight: bold;
    }
    td { 
        border: 0.5pt solid #000000; 
        padding: 5px 8px; 
        vertical-align: middle;
        color: #000000;
    }
    .header-row { height: 30pt; }
    .title { 
        font-size: 18pt; 
        font-weight: bold; 
        color: #1F4E78; 
        text-align: center; 
        border: none;
    }
    .subtitle { 
        font-size: 14pt; 
        color: #1F4E78; 
        text-align: center; 
        border: none;
    }
    .meta-label { 
        font-weight: bold; 
        background-color: #D9E1F2; 
        color: #000;
        text-align: right;
    }
    .meta-value {
        background-color: #FFFFFF;
        text-align: left;
    }
    .section-header { 
        background-color: #A9D08E; 
        color: #006100; 
        font-weight: bold; 
        text-align: left; 
        padding-left: 10px;
        font-size: 12pt;
        border: 0.5pt solid #000000;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .alt-row { background-color: #F2F2F2; }
    
    /* Type formats */
    .fmt-text { mso-number-format:"\@"; }
    .fmt-date { mso-number-format:"Short Date"; }
    .fmt-num { mso-number-format:"0"; }
    .fmt-dec { mso-number-format:"0.0"; }
';
echo '</style>';
echo '</head>';
echo '<body>';

// Header Section
echo '<table>';
echo '<tr><td colspan="14" class="title" style="border:none;">BARANGAY LUZ HEALTH CENTER</td></tr>';
echo '<tr><td colspan="14" class="subtitle" style="border:none;">Patient Records Export (Admin)</td></tr>';
echo '<tr><td colspan="14" style="border:none;">&nbsp;</td></tr>';

// Meta Info
echo '<tr>';
echo '<td colspan="2" class="meta-label">Generated On:</td>';
echo '<td colspan="5" class="meta-value">' . date('F j, Y h:i A') . '</td>';
echo '<td colspan="2" class="meta-label">Total Records:</td>';
echo '<td colspan="5" class="meta-value" class="fmt-num">' . count($patients) . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="2" class="meta-label">Date Range:</td>';
echo '<td colspan="5" class="meta-value">' . $startDate . ' to ' . $endDate . '</td>';
echo '<td colspan="2" class="meta-label">Sitio Filter:</td>';
echo '<td colspan="5" class="meta-value">' . htmlspecialchars($sitio) . '</td>';
echo '</tr>';
echo '<tr><td colspan="14" style="border:none;">&nbsp;</td></tr>';
echo '</table>';

// Create Excel content
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th style="width: 50px;">ID</th>';
echo '<th style="width: 200px;">Full Name</th>';
echo '<th style="width: 100px;">Date of Birth</th>';
echo '<th style="width: 50px;">Age</th>';
echo '<th style="width: 80px;">Gender</th>';
echo '<th style="width: 100px;">Civil Status</th>';
echo '<th style="width: 300px;">Address</th>';
echo '<th style="width: 120px;">Sitio</th>';
echo '<th style="width: 120px;">Contact</th>';
echo '<th style="width: 150px;">Disease</th>';
echo '<th style="width: 100px;">Last Checkup</th>';
echo '<th style="width: 120px;">Consultation Type</th>';
echo '<th style="width: 120px;">Occupation</th>';
echo '<th style="width: 100px;">Date Registered</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$counter = 1;
foreach ($patients as $patient) {
    $rowStyle = ($counter % 2 == 0) ? ' class="alt-row"' : '';
    $counter++;
    
    echo "<tr{$rowStyle}>";
    echo '<td class="fmt-text text-center">' . htmlspecialchars($patient['id'] ?? '') . '</td>';
    echo '<td class="text-bold">' . htmlspecialchars($patient['full_name'] ?? '') . '</td>';
    echo '<td class="fmt-date text-center">' . ($patient['date_of_birth'] ? date('Y-m-d', strtotime($patient['date_of_birth'])) : '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($patient['age'] ?? '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($patient['gender'] ?? '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($patient['civil_status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($patient['address'] ?? '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($patient['sitio'] ?? '') . '</td>';
    echo '<td class="fmt-text text-center">' . htmlspecialchars($patient['contact'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($patient['disease'] ?? '') . '</td>';
    echo '<td class="fmt-date text-center">' . ($patient['last_checkup'] ? date('Y-m-d', strtotime($patient['last_checkup'])) : '') . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($patient['consultation_type'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($patient['occupation'] ?? '') . '</td>';
    echo '<td class="fmt-date text-center">' . date('Y-m-d', strtotime($patient['created_at'])) . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Footer
echo '<br/><br/>';
echo '<table style="border:none;">';
echo '<tr>';
echo '<td colspan="14" style="border:none; color: #767676; font-size: 9pt; text-align: center;">';
echo 'CONFIDENTIALITY NOTICE: This document contains protected health information (PHI).<br/>';
echo 'Unauthorized access, disclosure, or distribution is prohibited.<br/>';
echo 'Generated by Community Health Tracker System';
echo '</td>';
echo '</tr>';
echo '</table>';

echo '</body></html>';