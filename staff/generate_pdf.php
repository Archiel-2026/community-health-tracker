<?php
ob_start();

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

if (!isset($_SESSION['pdf_export_data']) || empty($_SESSION['pdf_export_data'])) {
    die('No data to generate PDF');
}

$patients = $_SESSION['pdf_export_data'];

try {
    $staffId = $_SESSION['user']['id'] ?? null;
    $staffName = $_SESSION['user']['full_name'] ?? 'Unknown';
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

    $stmtLog = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, action_type, related_id, details, ip_address, user_agent, created_at) VALUES (?, 'export_bulk_pdf', NULL, ?, ?, ?, NOW())");
    $stmtLog->execute([
        $staffId,
        json_encode([
            'full_name' => $staffName,
            'record_count' => count($patients),
            'export_type' => 'bulk_patient_records'
        ]),
        $ip,
        $ua
    ]);
} catch (Exception $e) {
    error_log('Staff activity log error (export_bulk_pdf): ' . $e->getMessage());
}

require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

class BrandedPatientPDF extends TCPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetDrawColor(210, 226, 238);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->SetY(-8);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(107, 127, 145);
        $this->Cell(120, 4, 'Barangay Luz Health Center - Confidential Healthcare Record', 0, 0, 'L');
        $this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new BrandedPatientPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Luz Health Center');
$pdf->SetAuthor('Barangay Luz Health Center');
$pdf->SetTitle('Patient Health Records Export');
$pdf->SetSubject('Patient Health Records');
$pdf->SetKeywords('Patient, Health, Records, Export, Medical');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 14);
$pdf->SetFont('helvetica', '', 10);

$generatedAt = date('F j, Y h:i A');
$generatedBy = $_SESSION['user']['full_name'] ?? 'Staff';
$logoPath = realpath(__DIR__ . '/../asssets/images/Luz.jpg');
$logoHtml = '';
$logoLargeHtml = '';

if ($logoPath && is_file($logoPath)) {
    $logoSrc = str_replace('\\', '/', $logoPath);
    $logoHtml = '<img src="' . $logoSrc . '" alt="Barangay Luz Logo" style="width:48px;height:48px;border-radius:50%;object-fit:cover;background-color:#ffffff;border:3px solid rgba(255,255,255,0.30);">';
    $logoLargeHtml = '<img src="' . $logoSrc . '" alt="Barangay Luz Logo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;background-color:#ffffff;border:4px solid rgba(255,255,255,0.30);">';
}

$pdf->AddPage();
$pdf->writeHTML(buildCoverHtml($logoLargeHtml, count($patients), $generatedBy, $generatedAt), true, false, true, false, '');

foreach ($patients as $index => $patient) {
    $pdf->AddPage();

    $patientNumber = $index + 1;
    $patientName = cleanText($patient['full_name'] ?? 'Unknown Patient');
    $patientCode = cleanText($patient['unique_number'] ?? ('PAT-' . str_pad((string) ($patient['id'] ?? $patientNumber), 4, '0', STR_PAD_LEFT)));
    $patientType = !empty($patient['user_id']) ? 'Registered User' : 'Regular Patient';

    $pdf->writeHTML(
        buildPatientHeaderHtml(
            $logoHtml,
            $patientName,
            $patientCode,
            $patientType,
            $patientNumber,
            count($patients),
            $generatedAt,
            $generatedBy
        ),
        true,
        false,
        true,
        false,
        ''
    );

    writeSectionTable($pdf, 'Personal Information', [
        ['Patient ID', cleanText($patient['id'] ?? 'N/A')],
        ['Full Name', $patientName],
        ['Date of Birth', formatDateValue($patient['date_of_birth'] ?? null)],
        ['Age', cleanText($patient['age'] ?? 'N/A')],
        ['Gender', cleanText($patient['gender'] ?? 'N/A')],
        ['Civil Status', cleanText($patient['civil_status'] ?? 'N/A')],
        ['Occupation', cleanText($patient['occupation'] ?? 'N/A')],
        ['Contact Number', cleanText($patient['contact'] ?? 'N/A')],
        ['Sitio', cleanText($patient['sitio'] ?? 'N/A')],
        ['Address', cleanText($patient['address'] ?? 'N/A')],
    ]);

    writeSectionTable($pdf, 'Clinical Information', [
        ['Height (cm)', cleanText($patient['height'] ?? 'N/A')],
        ['Weight (kg)', cleanText($patient['weight'] ?? 'N/A')],
        ['Body Mass Index', calculateBMI($patient['height'] ?? 0, $patient['weight'] ?? 0)],
        ['Temperature (deg C)', cleanText($patient['temperature'] ?? 'N/A')],
        ['Blood Pressure', cleanText($patient['blood_pressure'] ?? 'N/A')],
        ['Blood Type', cleanText($patient['blood_type'] ?? 'N/A')],
        ['PHIC Number', cleanText($patient['phic_no'] ?? 'N/A')],
        ['BHW Assigned', cleanText($patient['bhw_assigned'] ?? 'N/A')],
        ['Family Number', cleanText($patient['family_no'] ?? 'N/A')],
        ['4P\'s Member', cleanText($patient['fourps_member'] ?? 'No')],
        ['Last Check-up Date', formatDateValue($patient['last_checkup'] ?? null)],
    ]);

    writeSectionNotes($pdf, 'Medical History Notes', [
        ['Allergies', cleanText($patient['allergies'] ?? 'None recorded')],
        ['Chronic Conditions', cleanText($patient['chronic_conditions'] ?? 'None recorded')],
        ['Immunization Record', cleanText($patient['immunization_record'] ?? 'None recorded')],
        ['Current Medications', cleanText($patient['current_medications'] ?? 'None recorded')],
        ['Medical History', cleanText($patient['medical_history'] ?? 'None recorded')],
        ['Family Medical History', cleanText($patient['family_history'] ?? 'None recorded')],
    ]);

    writeSectionTable($pdf, 'Registration Information', [
        ['Patient Category', $patientType],
        ['Consent Given', !empty($patient['consent_given']) ? 'Yes' : 'No'],
        ['Consent Date', formatDateTimeValue($patient['consent_date'] ?? null)],
        ['Created At', formatDateTimeValue($patient['created_at'] ?? null)],
        ['Last Updated', formatDateTimeValue($patient['updated_at'] ?? null)],
        ['Linked User Account', !empty($patient['user_id']) ? 'Registered (ID: ' . cleanText($patient['user_id']) . ')' : 'Not linked'],
        ['Registered Email', cleanText($patient['user_email'] ?? 'N/A')],
        ['Unique Number', cleanText($patient['unique_number'] ?? 'N/A')],
    ]);

    $pdf->writeHTML(
        '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px;">
            <tr>
                <td style="border-top:1px solid #d9e5ef; padding-top:8px; font-size:8.5px; color:#6a7f91; line-height:1.5;">
                    Confidentiality Notice: This document contains protected health information and must only be handled through authorized Barangay Luz Health Center processes.
                </td>
            </tr>
        </table>',
        true,
        false,
        true,
        false,
        ''
    );
}

$registeredCount = 0;
$regularCount = 0;
foreach ($patients as $patient) {
    if (!empty($patient['user_id'])) {
        $registeredCount++;
    } else {
        $regularCount++;
    }
}

$pdf->AddPage();
$pdf->writeHTML(
    buildSummaryHtml($logoHtml, count($patients), $registeredCount, $regularCount, $generatedBy, $generatedAt),
    true,
    false,
    true,
    false,
    ''
);

ob_end_clean();
$pdf->Output('Barangay_Luz_Healthcare_Records_' . date('Y-m-d_His') . '.pdf', 'D');
unset($_SESSION['pdf_export_data']);

function buildCoverHtml($logoLargeHtml, $totalRecords, $generatedBy, $generatedAt)
{
    return '
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family:helvetica,sans-serif;">
        <tr>
            <td style="border:1px solid #0f4c81; background-color:#ffffff;">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="20%" align="center" style="background-color:#0f4c81; padding:18px 10px;">' . $logoLargeHtml . '</td>
                        <td width="80%" style="background-color:#0f4c81; color:#ffffff; padding:18px 22px;">
                            <div style="font-size:10px; letter-spacing:1.8px; text-transform:uppercase; color:#d5ecff;">Official Healthcare Export</div>
                            <div style="font-size:24px; font-weight:bold; line-height:1.2; margin-top:6px;">Barangay Luz Health Center</div>
                            <div style="font-size:17px; font-weight:bold; line-height:1.3; margin-top:2px;">Patient Records Report</div>
                            <div style="font-size:10.5px; line-height:1.7; color:#e6f3ff; margin-top:8px;">Consistent healthcare report layout for selected patient records from the Barangay Luz Health Monitoring and Tracking System.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table cellpadding="6" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif;">
        <tr>
            <td width="33.33%" style="border:1px solid #d6e6f2; background-color:#f8fbfe;">
                <div style="font-size:8.5px; text-transform:uppercase; letter-spacing:1px; color:#71879a;">Records Included</div>
                <div style="font-size:17px; font-weight:bold; color:#0f4c81; margin-top:8px;">' . cleanText($totalRecords) . '</div>
            </td>
            <td width="33.33%" style="border:1px solid #d6e6f2; background-color:#f8fbfe;">
                <div style="font-size:8.5px; text-transform:uppercase; letter-spacing:1px; color:#71879a;">Prepared By</div>
                <div style="font-size:14px; font-weight:bold; color:#0f4c81; margin-top:8px;">' . cleanText($generatedBy) . '</div>
            </td>
            <td width="33.33%" style="border:1px solid #d6e6f2; background-color:#f8fbfe;">
                <div style="font-size:8.5px; text-transform:uppercase; letter-spacing:1px; color:#71879a;">Generated On</div>
                <div style="font-size:14px; font-weight:bold; color:#0f4c81; margin-top:8px;">' . cleanText($generatedAt) . '</div>
            </td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif;">
        <tr>
            <td style="border:1px solid #d6e6f2; background-color:#fbfdff; padding:16px;">
                <div style="font-size:10.5px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; color:#0f4c81;">Document Scope</div>
                <div style="font-size:9.8px; line-height:1.75; color:#4a6174; margin-top:8px;">
                    This report contains selected patient profiles, clinical information, medical history, and registration details for authorized health center operations. The document is intended for internal healthcare review, records management, and approved administrative use only.
                    <br><br>
                    Confidentiality Notice: This file contains protected health information and must be handled according to approved Barangay Luz Health Center data privacy and records management procedures.
                </div>
            </td>
        </tr>
    </table>';
}

function buildPatientHeaderHtml($logoHtml, $patientName, $patientCode, $patientType, $patientNumber, $totalPatients, $generatedAt, $generatedBy)
{
    return '
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family:helvetica,sans-serif;">
        <tr>
            <td width="68%" style="border:1px solid #0b3b65; background-color:#0f4c81; color:#ffffff; padding:14px 16px;">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="16%">' . $logoHtml . '</td>
                        <td width="84%">
                            <div style="font-size:8.5px; letter-spacing:1.5px; text-transform:uppercase; color:#c9e6ff;">Healthcare Record Export</div>
                            <div style="font-size:18px; font-weight:bold; line-height:1.2; margin-top:4px;">Barangay Luz Health Center</div>
                            <div style="font-size:10px; line-height:1.6; color:#d8efff; margin-top:4px;">Professional patient record summary for authorized clinical and administrative use.</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="32%" style="border:1px solid #d6e6f2; background-color:#f4f9fd; padding:12px 14px;">
                <div style="font-size:8px; text-transform:uppercase; color:#6a8092; letter-spacing:1px;">Generated On</div>
                <div style="font-size:10.5px; font-weight:bold; color:#0f4c81; margin-top:2px;">' . cleanText($generatedAt) . '</div>
                <div style="font-size:8px; text-transform:uppercase; color:#6a8092; letter-spacing:1px; margin-top:7px;">Prepared By</div>
                <div style="font-size:10.5px; font-weight:bold; color:#0f4c81; margin-top:2px;">' . cleanText($generatedBy) . '</div>
                <div style="font-size:8px; text-transform:uppercase; color:#6a8092; letter-spacing:1px; margin-top:7px;">Record</div>
                <div style="font-size:10.5px; font-weight:bold; color:#0f4c81; margin-top:2px;">Patient ' . cleanText($patientNumber) . ' of ' . cleanText($totalPatients) . '</div>
            </td>
        </tr>
    </table>

    <table cellpadding="6" cellspacing="0" border="0" width="100%" style="margin-top:8px; font-family:helvetica,sans-serif;">
        <tr>
            <td width="38%" style="border:1px solid #d8e7f1; background-color:#f7fbfe;">
                <div style="font-size:8px; text-transform:uppercase; color:#70889b; letter-spacing:0.9px;">Patient Name</div>
                <div style="font-size:12px; font-weight:bold; color:#133b5c; margin-top:4px;">' . strtoupper($patientName) . '</div>
            </td>
            <td width="20%" style="border:1px solid #d8e7f1; background-color:#f7fbfe;">
                <div style="font-size:8px; text-transform:uppercase; color:#70889b; letter-spacing:0.9px;">Patient Code</div>
                <div style="font-size:12px; font-weight:bold; color:#133b5c; margin-top:4px;">' . cleanText($patientCode) . '</div>
            </td>
            <td width="18%" style="border:1px solid #d8e7f1; background-color:#f7fbfe;">
                <div style="font-size:8px; text-transform:uppercase; color:#70889b; letter-spacing:0.9px;">Type</div>
                <div style="font-size:12px; font-weight:bold; color:#133b5c; margin-top:4px;">' . cleanText($patientType) . '</div>
            </td>
            <td width="24%" style="border:1px solid #d8e7f1; background-color:#f7fbfe;">
                <div style="font-size:8px; text-transform:uppercase; color:#70889b; letter-spacing:0.9px;">Status</div>
                <div style="font-size:12px; font-weight:bold; color:#133b5c; margin-top:4px;">Active Export Record</div>
            </td>
        </tr>
    </table>';
}

function buildSummaryHtml($logoHtml, $totalRecords, $registeredCount, $regularCount, $generatedBy, $generatedAt)
{
    return '
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family:helvetica,sans-serif;">
        <tr>
            <td style="border:1px solid #0b3b65; background-color:#0f4c81; color:#ffffff; padding:16px 18px;">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="12%">' . $logoHtml . '</td>
                        <td width="88%">
                            <div style="font-size:8.5px; text-transform:uppercase; letter-spacing:1.5px; color:#cfe8ff;">Export Summary</div>
                            <div style="font-size:20px; font-weight:bold; margin-top:4px;">Patient Records PDF Export</div>
                            <div style="font-size:10px; line-height:1.6; color:#e2f1ff; margin-top:4px;">Compiled report for selected patient records from Barangay Luz Health Center.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table cellpadding="6" cellspacing="0" border="0" width="100%" style="margin-top:8px; font-family:helvetica,sans-serif;">
        <tr>
            <td width="25%" style="border:1px solid #d8e6f1; background-color:#f8fbfd;">
                <div style="font-size:8px; text-transform:uppercase; color:#71879a; letter-spacing:1px;">Total Records</div>
                <div style="font-size:16px; font-weight:bold; color:#0f4c81; margin-top:4px;">' . cleanText($totalRecords) . '</div>
            </td>
            <td width="25%" style="border:1px solid #d8e6f1; background-color:#f8fbfd;">
                <div style="font-size:8px; text-transform:uppercase; color:#71879a; letter-spacing:1px;">Registered Users</div>
                <div style="font-size:16px; font-weight:bold; color:#0f4c81; margin-top:4px;">' . cleanText($registeredCount) . '</div>
            </td>
            <td width="25%" style="border:1px solid #d8e6f1; background-color:#f8fbfd;">
                <div style="font-size:8px; text-transform:uppercase; color:#71879a; letter-spacing:1px;">Regular Patients</div>
                <div style="font-size:16px; font-weight:bold; color:#0f4c81; margin-top:4px;">' . cleanText($regularCount) . '</div>
            </td>
            <td width="25%" style="border:1px solid #d8e6f1; background-color:#f8fbfd;">
                <div style="font-size:8px; text-transform:uppercase; color:#71879a; letter-spacing:1px;">Prepared By</div>
                <div style="font-size:13px; font-weight:bold; color:#0f4c81; margin-top:4px;">' . cleanText($generatedBy) . '</div>
            </td>
        </tr>
    </table>

    <table cellpadding="8" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif; border:1px solid #dce8f2;">
        <tr style="background-color:#edf5fb;">
            <td width="34%" style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.8px; color:#0f4c81;">Summary Field</td>
            <td width="66%" style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.8px; color:#0f4c81;">Details</td>
        </tr>
        <tr><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Export Type</td><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Selected Specific Records to Export</td></tr>
        <tr><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Generated On</td><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">' . cleanText($generatedAt) . '</td></tr>
        <tr><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Document Format</td><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Healthcare PDF Report</td></tr>
        <tr><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Records Included</td><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Personal details, clinical information, medical history, and registration data</td></tr>
        <tr><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Prepared For</td><td style="font-size:9.6px; color:#253746; border-top:1px solid #e3edf5;">Authorized staff review, documentation, and administrative reporting</td></tr>
    </table>

    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif;">
        <tr>
            <td style="border:1px solid #dce8f2; background-color:#fbfdff; padding:14px; font-size:9px; line-height:1.65; color:#5f7385;">
                Confidentiality Notice: This report contains protected patient health information. Access must remain limited to authorized healthcare personnel and approved administrative processes. Reproduction or disclosure outside approved channels is prohibited.
            </td>
        </tr>
    </table>';
}

function writeSectionTable(TCPDF $pdf, $title, array $rows)
{
    $html = '
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif;">
        <tr>
            <td style="background-color:#eaf4fb; border:1px solid #d4e6f3; color:#0f4c81; padding:8px 12px; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.8px;">' . cleanText($title) . '</td>
        </tr>
    </table>
    <table cellpadding="7" cellspacing="0" border="0" width="100%" style="font-family:helvetica,sans-serif; border:1px solid #dfeaf2;">';

    foreach ($rows as $index => $row) {
        $borderTop = $index === 0 ? '0' : '1px solid #e6eef5';
        $html .= '
        <tr>
            <td width="34%" style="font-size:9.4px; font-weight:bold; color:#476277; background-color:#f8fbfd; border-top:' . $borderTop . ';">' . cleanText($row[0]) . '</td>
            <td width="66%" style="font-size:9.4px; color:#22313f; border-top:' . $borderTop . ';">' . nl2br(cleanText($row[1])) . '</td>
        </tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

function writeSectionNotes(TCPDF $pdf, $title, array $notes)
{
    $html = '
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px; font-family:helvetica,sans-serif;">
        <tr>
            <td style="background-color:#eaf4fb; border:1px solid #d4e6f3; color:#0f4c81; padding:8px 12px; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.8px;">' . cleanText($title) . '</td>
        </tr>
    </table>';

    foreach ($notes as $note) {
        $html .= '
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:6px; font-family:helvetica,sans-serif;">
            <tr>
                <td style="border:1px solid #dfeaf2; background-color:#fcfeff; padding:10px 12px;">
                    <div style="font-size:8.8px; font-weight:bold; color:#0f4c81; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:4px;">' . cleanText($note[0]) . '</div>
                    <div style="font-size:9.4px; line-height:1.6; color:#253746;">' . nl2br(cleanText($note[1])) . '</div>
                </td>
            </tr>
        </table>';
    }

    $pdf->writeHTML($html, true, false, true, false, '');
}

function cleanText($value)
{
    if ($value === null || $value === '') {
        return 'N/A';
    }

    return htmlspecialchars_decode((string) $value, ENT_QUOTES);
}

function formatDateValue($value)
{
    if (empty($value)) {
        return 'N/A';
    }

    return date('F j, Y', strtotime($value));
}

function formatDateTimeValue($value)
{
    if (empty($value)) {
        return 'N/A';
    }

    return date('F j, Y h:i A', strtotime($value));
}

function calculateBMI($height, $weight)
{
    if ($height > 0 && $weight > 0) {
        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        return number_format($bmi, 2);
    }

    return 'N/A';
}
