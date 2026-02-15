<?php
// Export Full Report as Excel
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    echo 'Access denied';
    exit();
}

$reportData = null;
// Use the same logic as generate_full_report.php
ob_start();
include __DIR__ . '/generate_full_report.php';
$output = ob_get_clean();
$json = json_decode($output, true);
if (!$json || empty($json['data'])) {
    echo 'Failed to generate report.';
    exit();
}
$reportData = $json['data'];

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
            foreach ($value as $k => $v) {
                $sheet->setCellValue('B'.$row, $k);
                $sheet->setCellValue('C'.$row, $v);
                $row++;
            }
        } else {
            $sheet->setCellValue('B'.$row, $label);
            $sheet->setCellValue('C'.$row, $value);
            $row++;
        }
    }
    $row++;
}

writeSection($sheet, $row, 'Resident Demographics', $reportData['resident_demographics']);
writeSection($sheet, $row, 'Medical Information Summary', $reportData['medical_summary']);
writeSection($sheet, $row, 'Consultation Records', $reportData['consultation_records']);
writeSection($sheet, $row, 'Doctor’s Notes & Case Summaries', $reportData['doctor_notes']);

$sheet->setCellValue('A'.$row, 'Prepared by: '.$reportData['prepared_by']);
$row++;
$sheet->setCellValue('A'.$row, 'Date: '.$reportData['date']);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Comprehensive_Health_Report.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
