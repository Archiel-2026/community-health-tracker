<?php
// Export Full Report as Excel
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet

if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    exit();
}

require_once __DIR__ . '/generate_full_report.php';
$pdo = $GLOBALS['pdo'];
$reportData = generate_health_report($pdo);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$row = 1;

// Title
$sheet->setCellValue('A'.$row, 'Comprehensive Health Report');
$sheet->mergeCells('A'.$row.':E'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(16);
$row += 2;

// Helper function
function writeSection($sheet, &$row, $title, $data) {
    $sheet->setCellValue('A'.$row, $title);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(13);
    $row++;
    foreach ($data as $label => $value) {
        if (is_array($value)) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $row++;
            if (empty($value)) {
                $sheet->setCellValue('B'.$row, '(None)');
                $row++;
            } else {
                foreach ($value as $k => $v) {
                    $sheet->setCellValue('B'.$row, $k);
                    if (is_array($v)) {
                        $formatted = [];
                        foreach ($v as $subk => $subv) {
                            $formatted[] = $subk . ': ' . $subv;
                        }
                        $sheet->setCellValue('C'.$row, $formatted ? implode("\n", $formatted) : '(None)');
                        $sheet->getStyle('C'.$row)->getAlignment()->setWrapText(true);
                    } else {
                        $sheet->setCellValue('C'.$row, ($v === '' || $v === null) ? '(None)' : $v);
                    }
                    $row++;
                }
            }
        } else {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, ($value === '' || $value === null) ? '(None)' : $value);
            $row++;
        }
    }
    $row++;
}


writeSection($sheet, $row, 'Resident Demographics', $reportData['resident_demographics']);
writeSection($sheet, $row, 'Medical Information Summary', $reportData['medical_summary']);
writeSection($sheet, $row, 'Administrative Data', $reportData['admin']);
$sheet->setCellValue('A'.$row, 'Recommendations: '.$reportData['recommendations']);
$row++;
$sheet->setCellValue('A'.$row, 'Prepared by: '.$reportData['prepared_by']);
$row++;
$sheet->setCellValue('A'.$row, 'Date: '.$reportData['date']);

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (isset($_SESSION['user']['id']) && isset($_SESSION['user']['full_name'])) {
    try {
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
        $stmt = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'export_bulk_excel', NULL, ?, ?, ?, NOW())");
        $details = json_encode([
            'full_name' => $_SESSION['user']['full_name'],
            'record_count' => isset($reportData['resident_demographics']['total_patients']) ? $reportData['resident_demographics']['total_patients'] : '',
            'export_type' => 'bulk_patient_records'
        ]);
        $stmt->execute([$_SESSION['user']['id'], $details, $ip, $ua]);
    } catch (Exception $e) {
        error_log('Export Excel log error: ' . $e->getMessage());
    }
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Comprehensive_Health_Report.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
