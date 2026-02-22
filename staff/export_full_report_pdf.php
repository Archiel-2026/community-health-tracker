<?php
// Prevent accidental output before headers
// Export Full Report as PDF
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // mPDF

if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    exit();
}

require_once __DIR__ . '/generate_full_report.php';
if (!isStaff() && !isAdmin()) {
    http_response_code(403);
    exit();
}
$pdo = $GLOBALS['pdo'];
$reportData = generate_health_report($pdo);
ob_clean();
ob_start();

// Render HTML for PDF (reuse the same HTML as the dashboard, but without the <style> tag)
function renderReportHtml($data) {
    ob_start();
    ?>
    <style>
        .pdf-container { max-width: 700px; margin: 0 auto; font-family: 'Segoe UI', Arial, sans-serif; background: #fff; border: 2px solid #cbd5e1; border-radius: 1.2rem; padding: 2rem 2rem 1.5rem 2rem; }
        .pdf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .pdf-header img { height: 55px; width: 55px; object-fit: contain; border-radius: 10px; background: #fff; border: 1px solid #cbd5e1; }
        .pdf-govinfo { flex: 1; text-align: center; font-size: 1.05rem; color: #22223b; font-weight: 500; line-height: 1.3; }
        .pdf-titlebar { text-align: center; margin-bottom: 1.2rem; }
        .pdf-section { border: 1.5px solid #cbd5e1; border-radius: 0.7rem; background: #f9fafb; margin-bottom: 1.2rem; padding: 1.2rem 1.2rem; }
        .pdf-section-title { font-size: 1.1rem; font-weight: 700; color: #22223b; margin-bottom: 0.7rem; }
        .pdf-table { width: 100%; border-collapse: collapse; margin-bottom: 0.5rem; }
        .pdf-table th, .pdf-table td { border: 1px solid #cbd5e1; padding: 0.7em 1em; text-align: left; vertical-align: top; }
        .pdf-table th { background: #f1f5f9; font-weight: 700; color: #1e293b; width: 220px; }
        .pdf-table tr:not(:last-child) td { border-bottom: 1px solid #cbd5e1; }
        .pdf-recommend { margin-top: 1.2rem; }
        .pdf-footer { margin-top: 2rem; font-size: 0.98em; color: #64748b; text-align: right; }
    </style>
    <div class="pdf-container">
        <div class="pdf-header">
            <img src="assets/images/Luz.jpg" alt="Barangay Luz Logo">
            <div class="pdf-govinfo">
                Republic of the Philippines<br>
                City of Cebu, Philippines<br>
                Barangay Luz, Cebu City<br>
            </div>
            <img src="assets/images/DOH.png" alt="City Health Logo">
        </div>
        <div class="pdf-titlebar">
            <div style="font-size:1.1rem;font-weight:700;color:#22223b;">OFFICE OF THE CITY HEALTH</div>
            <div style="font-size:1.05rem;color:#22223b;font-weight:600;">Comprehensive Health Report</div>
        </div>
        <div class="pdf-section">
            <div class="pdf-section-title">Resident Demographics</div>
            <table class="pdf-table">
                <tr><th>Total Registered Residents</th><td><?= htmlspecialchars($data['resident_demographics']['total_registered']) ?></td></tr>
                <tr><th>New Registrations This Period</th><td><?= htmlspecialchars($data['resident_demographics']['new_registrations']) ?></td></tr>
                <tr><th>Age Distribution</th><td>
                    Children (0–12): <?= htmlspecialchars($data['resident_demographics']['age_distribution']['children']) ?> | Adolescents (13–19): <?= htmlspecialchars($data['resident_demographics']['age_distribution']['adolescents']) ?> | Adults (20–59): <?= htmlspecialchars($data['resident_demographics']['age_distribution']['adults']) ?> | Seniors (60+): <?= htmlspecialchars($data['resident_demographics']['age_distribution']['seniors']) ?>
                </td></tr>
                <tr><th>Sex Distribution</th><td>Male: <?= htmlspecialchars($data['resident_demographics']['sex_distribution']['Male']) ?> | Female: <?= htmlspecialchars($data['resident_demographics']['sex_distribution']['Female']) ?></td></tr>
            </table>
        </div>
        <div class="pdf-section">
            <div class="pdf-section-title">Medical Information Summary</div>
            <table class="pdf-table">
                <tr><th>Common Conditions Recorded</th><td>
                    Hypertension: <?= htmlspecialchars($data['medical_summary']['common_conditions']['hypertension']) ?><br>
                    Diabetes: <?= htmlspecialchars($data['medical_summary']['common_conditions']['diabetes']) ?><br>
                    Tuberculosis: <?= htmlspecialchars($data['medical_summary']['common_conditions']['tuberculosis']) ?><br>
                    Other: <?= htmlspecialchars(implode(' ', array_map(function($k, $v){ return $k . ': ' . $v; }, array_keys($data['medical_summary']['common_conditions']['other']), $data['medical_summary']['common_conditions']['other']))) ?>
                </td></tr>
                <tr><th>Immunization Coverage</th><td>
                    Fully immunized children: <?= htmlspecialchars($data['medical_summary']['fully_immunized_children']) ?><br>
                    Vaccine Administered (by type): <?= htmlspecialchars(implode(' ', array_map(function($k, $v){ return $k . ': ' . $v; }, array_keys($data['medical_summary']['vaccines_administered']), $data['medical_summary']['vaccines_administered']))) ?>
                </td></tr>
            </table>
        </div>
        <div class="pdf-section">
            <div class="pdf-section-title">Administrative Data</div>
            <table class="pdf-table">
                <tr><th>Average Patients per Doctor</th><td><?= htmlspecialchars($data['admin']['avg_patients_per_doctor']) ?></td></tr>
                <tr><th>Average Patients per Nurse</th><td><?= htmlspecialchars($data['admin']['avg_patients_per_nurse']) ?></td></tr>
            </table>
        </div>
        <div class="pdf-section pdf-recommend">
            <table class="pdf-table">
                <tr><th>Recommendations</th><td><?= htmlspecialchars($data['recommendations']) ?></td></tr>
                <tr><th>Prepared by</th><td><?= htmlspecialchars($data['prepared_by']) ?></td></tr>
                <tr><th>Date</th><td><?= htmlspecialchars($data['date']) ?></td></tr>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

$html = renderReportHtml($reportData);

$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
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
        $stmt = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'export_bulk_pdf', NULL, ?, ?, ?, NOW())");
        $details = json_encode([
            'full_name' => $_SESSION['user']['full_name'],
            'record_count' => isset($reportData['resident_demographics']['total_patients']) ? $reportData['resident_demographics']['total_patients'] : '',
            'export_type' => 'bulk_patient_records'
        ]);
        $stmt->execute([$_SESSION['user']['id'], $details, $ip, $ua]);
    } catch (Exception $e) {
        error_log('Export PDF log error: ' . $e->getMessage());
    }
}
$mpdf->WriteHTML($html);
// Set correct headers for PDF output
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Comprehensive_Health_Report.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
$mpdf->Output('Comprehensive_Health_Report.pdf', 'I'); // 'I' for inline display in browser
exit();
