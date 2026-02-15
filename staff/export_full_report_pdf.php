<?php
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

// Render HTML for PDF (reuse the same HTML as the dashboard, but without the <style> tag)
function renderReportHtml($data) {
    ob_start();
    ?>
    <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 0 auto;">
        <h2 style="color: #2563eb; text-align: center; margin-bottom: 2rem;">Comprehensive Health Report</h2>
        <div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="color: #2563eb; font-size: 1.2rem;">Resident Demographics</h3>
            <ul>
                <li>Total Registered Residents: <b><?= $data['resident_demographics']['total_registered'] ?></b></li>
                <li>New Registrations This Period: <b><?= $data['resident_demographics']['new_registrations'] ?></b></li>
                <li>Age Distribution:
                    <ul>
                        <li>Children (0–12): <b><?= $data['resident_demographics']['age_distribution']['children'] ?></b></li>
                        <li>Adolescents (13–19): <b><?= $data['resident_demographics']['age_distribution']['adolescents'] ?></b></li>
                        <li>Adults (20–59): <b><?= $data['resident_demographics']['age_distribution']['adults'] ?></b></li>
                        <li>Seniors (60+): <b><?= $data['resident_demographics']['age_distribution']['seniors'] ?></b></li>
                    </ul>
                </li>
                <li>Sex Distribution:
                    <ul>
                        <li>Male: <b><?= $data['resident_demographics']['sex_distribution']['Male'] ?? 0 ?></b></li>
                        <li>Female: <b><?= $data['resident_demographics']['sex_distribution']['Female'] ?? 0 ?></b></li>
                    </ul>
                </li>
            </ul>
        </div>
        <div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="color: #2563eb; font-size: 1.2rem;">Medical Information Summary</h3>
            <ul>
                <li>Common Conditions Recorded:
                    <ul>
                        <li>Hypertension: <b><?= $data['medical_summary']['common_conditions']['hypertension'] ?></b></li>
                        <li>Diabetes: <b><?= $data['medical_summary']['common_conditions']['diabetes'] ?></b></li>
                        <li>Tuberculosis: <b><?= $data['medical_summary']['common_conditions']['tuberculosis'] ?></b></li>
                        <li>Other: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['medical_summary']['common_conditions']['other']), $data['medical_summary']['common_conditions']['other'])) ?></b></li>
                    </ul>
                </li>
                <li>Immunization Coverage:
                    <ul>
                        <li>Fully Immunized Children: <b><?= $data['medical_summary']['fully_immunized_children'] ?></b></li>
                        <li>Vaccines Administered (by type): <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['medical_summary']['vaccines_administered']), $data['medical_summary']['vaccines_administered'])) ?></b></li>
                    </ul>
                </li>
            </ul>
        </div>
        <div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="color: #2563eb; font-size: 1.2rem;">Consultation Records</h3>
            <ul>
                <li>Total Consultations: <b><?= $data['consultation_records']['total_consultations'] ?></b></li>
                <li>Consultations by Type: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['consultation_records']['consultations_by_type']), $data['consultation_records']['consultations_by_type'])) ?></b></li>
                <li>Top Reasons for Consultation: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['consultation_records']['top_reasons']), $data['consultation_records']['top_reasons'])) ?></b></li>
                <li>Referrals Made: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['consultation_records']['referrals_made']), $data['consultation_records']['referrals_made'])) ?></b></li>
            </ul>
        </div>
        <div style="background: #f3f4f6; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="color: #2563eb; font-size: 1.2rem;">Doctor’s Notes & Case Summaries</h3>
            <ul>
                <li>Summary of Diagnoses: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['doctor_notes']['diagnoses']), $data['doctor_notes']['diagnoses'])) ?></b></li>
                <li>Treatments Provided: <b><?= implode(', ', array_map(function($k, $v){return $k.': '.$v;}, array_keys($data['doctor_notes']['treatments']), $data['doctor_notes']['treatments'])) ?></b></li>
            </ul>
        </div>
        <div style="text-align:right; color:#64748b; font-size:0.98em; margin-top:2.5rem;">Prepared by: <?= htmlspecialchars($data['prepared_by']) ?> | Date: <?= htmlspecialchars($data['date']) ?></div>
    </div>
    <?php
    return ob_get_clean();
}

$html = renderReportHtml($reportData);

$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('Comprehensive_Health_Report.pdf', 'D');
exit();
