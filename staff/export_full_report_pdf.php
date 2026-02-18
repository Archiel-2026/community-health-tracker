<?php
// Prevent accidental output before headers
ob_clean();
ob_start();
// Export Full Report as PDF
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // mPDF

if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    echo 'Access denied';
    exit();
}

require_once __DIR__ . '/generate_full_report.php';
if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    echo 'Access denied';
    exit();
}
$pdo = $GLOBALS['pdo'];
$reportData = generate_health_report($pdo);

// Render HTML for PDF (reuse the same HTML as the dashboard, but without the <style> tag)
function renderReportHtml($data) {
    ob_start();
    ?>
    <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 0 auto;">
        <h2 style="color: #2563eb; text-align: center; margin-bottom: 2rem;">Comprehensive Health Report</h2>
        <?php
        // Helper to print arrays/objects in readable format
        function printValue($val) {
            if (is_array($val)) {
                if (empty($val)) return '<i>None</i>';
                $out = '';
                foreach ($val as $k => $v) {
                    $out .= htmlspecialchars($k) . ': ' . (is_array($v) ? printValue($v) : htmlspecialchars($v)) . "<br>";
                }
                return $out;
            }
            if ($val === '' || $val === null) return '<i>None</i>';
            return htmlspecialchars($val);
        }
        foreach ($data as $section => $sectionData) {
            if ($section === 'prepared_by' || $section === 'date' || $section === 'recommendations') continue;
            echo '<div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">';
            echo '<h3 style="color: #2563eb; font-size: 1.2rem;">' . ucwords(str_replace('_', ' ', $section)) . '</h3>';
            echo '<table style="width:100%;border-collapse:collapse;">';
            foreach ($sectionData as $label => $value) {
                echo '<tr>';
                echo '<td style="vertical-align:top;padding:6px 10px;font-weight:bold;width:220px;border:1px solid #cbd5e1;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $label))) . '</td>';
                echo '<td style="vertical-align:top;padding:6px 10px;border:1px solid #cbd5e1;">' . printValue($value) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
        ?>
        <div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="color: #2563eb; font-size: 1.2rem;">Recommendations</h3>
            <div><?= htmlspecialchars($data['recommendations']) ?></div>
        </div>
        <div style="text-align:right; color:#64748b; font-size:0.98em; margin-top:2.5rem;">Prepared by: <?= htmlspecialchars($data['prepared_by']) ?> | Date: <?= htmlspecialchars($data['date']) ?></div>
    </div>
    <?php
    return ob_get_clean();
}

$html = renderReportHtml($reportData);

$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
// Set correct headers for PDF output
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Comprehensive_Health_Report.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
$mpdf->Output('Comprehensive_Health_Report.pdf', 'I'); // 'I' for inline display in browser
exit();
