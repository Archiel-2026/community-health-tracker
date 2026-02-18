<?php
// Comprehensive Health Report Generator
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

function generate_health_report($pdo) {
    $report = [];
    // ...existing code for report generation...
    // 1. Resident Demographics
    $report['resident_demographics'] = [];
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL");
    $report['resident_demographics']['total_registered'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute();
    $report['resident_demographics']['new_registrations'] = (int)$stmt->fetchColumn();
    $ageGroups = [
        'children' => [0, 12],
        'adolescents' => [13, 19],
        'adults' => [20, 59],
        'seniors' => [60, 200],
    ];
    foreach ($ageGroups as $label => [$min, $max]) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?");
        $stmt->execute([$min, $max]);
        $report['resident_demographics']['age_distribution'][$label] = (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT gender, COUNT(*) as count FROM sitio1_patients WHERE deleted_at IS NULL GROUP BY gender");
    $sexDist = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $report['resident_demographics']['sex_distribution'] = [
        'Male' => isset($sexDist['Male']) ? (int)$sexDist['Male'] : 0,
        'Female' => isset($sexDist['Female']) ? (int)$sexDist['Female'] : 0
    ];
    // ...existing code for all other report sections...
    // 2. Medical Information Summary
    $report['medical_summary'] = [];
    $conditions = ['Hypertension', 'Diabetes', 'Tuberculosis'];
    foreach ($conditions as $cond) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM existing_info_patients WHERE chronic_conditions LIKE ?");
        $stmt->execute(["%$cond%"]);
        $report['medical_summary']['common_conditions'][strtolower($cond)] = (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT chronic_conditions, COUNT(*) as count FROM existing_info_patients WHERE chronic_conditions IS NOT NULL AND chronic_conditions != '' GROUP BY chronic_conditions");
    $other = [];
    foreach ($stmt as $row) {
        if (!in_array($row['chronic_conditions'], $conditions)) {
            $other[$row['chronic_conditions']] = (int)$row['count'];
        }
    }
    $report['medical_summary']['common_conditions']['other'] = $other;
    $stmt = $pdo->query("SELECT COUNT(*) FROM existing_info_patients WHERE immunization_record IS NOT NULL AND immunization_record != ''");
    $report['medical_summary']['fully_immunized_children'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT immunization_record, COUNT(*) as count FROM existing_info_patients WHERE immunization_record IS NOT NULL AND immunization_record != '' GROUP BY immunization_record");
    $report['medical_summary']['vaccines_administered'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE civil_status = 'Pregnant' AND deleted_at IS NULL");
    $report['medical_summary']['pregnant_women'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM consultation_notes WHERE note LIKE '%prenatal%' AND consultation_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $report['medical_summary']['prenatal_visits'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM child_health_records");
    $report['medical_summary']['infants_monitored'] = (int)$stmt->fetchColumn();
    // 3. Consultation Records
    $report['consultation_records'] = [];
    $stmt = $pdo->query("SELECT COUNT(*) FROM consultation_notes");
    $report['consultation_records']['total_consultations'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT visit_type, COUNT(*) as count FROM patient_visits GROUP BY visit_type");
    $report['consultation_records']['consultations_by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT symptoms, COUNT(*) as count FROM patient_visits WHERE symptoms IS NOT NULL AND symptoms != '' GROUP BY symptoms ORDER BY count DESC LIMIT 5");
    $report['consultation_records']['top_reasons'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT referral_info, COUNT(*) as count FROM patient_visits WHERE referral_info IS NOT NULL AND referral_info != '' GROUP BY referral_info");
    $referrals = [];
    foreach ($stmt as $row) {
        $referrals[$row['referral_info']] = (int)$row['count'];
    }
    $report['consultation_records']['referrals_made'] = $referrals;
    // 4. Doctor’s Notes & Case Summaries
    $report['doctor_notes'] = [];
    $stmt = $pdo->query("SELECT diagnosis, COUNT(*) as count FROM patient_visits WHERE diagnosis IS NOT NULL AND diagnosis != '' GROUP BY diagnosis ORDER BY count DESC LIMIT 10");
    $report['doctor_notes']['diagnoses'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT treatment, COUNT(*) as count FROM patient_visits WHERE treatment IS NOT NULL AND treatment != '' GROUP BY treatment ORDER BY count DESC LIMIT 10");
    $report['doctor_notes']['treatments'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT disease, COUNT(*) as count FROM sitio1_patients WHERE disease IN ('COVID-19', 'Dengue', 'Measles', 'Tuberculosis') GROUP BY disease");
    $report['doctor_notes']['cases_for_city_health'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    // 5. Public Health Indicators
    $report['public_health'] = [];
    foreach (['Dengue', 'Measles', 'COVID-19'] as $disease) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE disease = ?");
        $stmt->execute([$disease]);
        $report['public_health']['notifiable'][$disease] = (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT title, details FROM health_campaigns");
    $campaigns = [];
    foreach ($stmt as $row) {
        $campaigns[] = $row;
    }
    $report['public_health']['campaigns'] = $campaigns;
    $trend = [];
    $diseases = $pdo->query("SELECT disease FROM sitio1_patients WHERE disease IS NOT NULL AND disease != '' GROUP BY disease ORDER BY COUNT(*) DESC LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($diseases as $disease) {
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE disease = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $stmt->execute([$disease, $month]);
            $monthly[$month] = (int)$stmt->fetchColumn();
        }
        $trend[$disease] = $monthly;
    }
    $report['public_health']['community_trends'] = $trend;
    // 6. Administrative Data
    $report['admin'] = [];
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients");
    $totalPatients = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE position = 'Doctor'");
    $totalDoctors = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE position = 'Nurse'");
    $totalNurses = (int)$stmt->fetchColumn();
    $report['admin']['avg_patients_per_doctor'] = $totalDoctors ? round($totalPatients / $totalDoctors, 2) : 0;
    $report['admin']['avg_patients_per_nurse'] = $totalNurses ? round($totalPatients / $totalNurses, 2) : 0;
    $report['admin']['supplies'] = 'See inventory system or manual records.';
    $report['admin']['challenges'] = 'See staff meeting notes or manual records.';
    $report['recommendations'] = 'Please review the above data and provide recommendations for support, resources, or interventions as needed.';
    $report['prepared_by'] = isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] . ', ' . ($_SESSION['user']['role'] ?? 'Staff') : 'Unknown';
    $report['date'] = date('F d, Y');
    return $report;
}

// If called directly (not included), output JSON
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        http_response_code(500);
        $debug = [
            'error' => "PHP error: $errstr in $errfile on line $errline",
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace()
        ];
        error_log(json_encode($debug));
        echo json_encode($debug);
        exit();
    });
    set_exception_handler(function($e) {
        http_response_code(500);
        $debug = [
            'error' => 'Exception: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        error_log(json_encode($debug));
        echo json_encode($debug);
        exit();
    });
    if (!isStaff() && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }
    $pdo = $GLOBALS['pdo'];
    $report = generate_health_report($pdo);
    echo json_encode(['success' => true, 'data' => $report]);
    exit();
}

// 1. Resident Demographics
$report['resident_demographics'] = [];
// Total Registered Residents
$stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL");
$report['resident_demographics']['total_registered'] = (int)$stmt->fetchColumn();
// New Registrations This Period (current month)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->execute();
$report['resident_demographics']['new_registrations'] = (int)$stmt->fetchColumn();
// Age Distribution
$ageGroups = [
    'children' => [0, 12],
    'adolescents' => [13, 19],
    'adults' => [20, 59],
    'seniors' => [60, 200],
];
foreach ($ageGroups as $label => [$min, $max]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?");
    $stmt->execute([$min, $max]);
    $report['resident_demographics']['age_distribution'][$label] = (int)$stmt->fetchColumn();
}
// Sex Distribution
$stmt = $pdo->query("SELECT gender, COUNT(*) as count FROM sitio1_patients WHERE deleted_at IS NULL GROUP BY gender");
$sexDist = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$report['resident_demographics']['sex_distribution'] = [
    'Male' => isset($sexDist['Male']) ? (int)$sexDist['Male'] : 0,
    'Female' => isset($sexDist['Female']) ? (int)$sexDist['Female'] : 0
];

// 2. Medical Information Summary
$report['medical_summary'] = [];
$conditions = ['Hypertension', 'Diabetes', 'Tuberculosis'];
foreach ($conditions as $cond) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM existing_info_patients WHERE chronic_conditions LIKE ?");
    $stmt->execute(["%$cond%"]);
    $report['medical_summary']['common_conditions'][strtolower($cond)] = (int)$stmt->fetchColumn();
}
// Other conditions
$stmt = $pdo->query("SELECT chronic_conditions, COUNT(*) as count FROM existing_info_patients WHERE chronic_conditions IS NOT NULL AND chronic_conditions != '' GROUP BY chronic_conditions");
$other = [];
foreach ($stmt as $row) {
    if (!in_array($row['chronic_conditions'], $conditions)) {
        $other[$row['chronic_conditions']] = (int)$row['count'];
    }
}
$report['medical_summary']['common_conditions']['other'] = $other;
// Immunization Coverage
$stmt = $pdo->query("SELECT COUNT(*) FROM existing_info_patients WHERE immunization_record IS NOT NULL AND immunization_record != ''");
$report['medical_summary']['fully_immunized_children'] = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT immunization_record, COUNT(*) as count FROM existing_info_patients WHERE immunization_record IS NOT NULL AND immunization_record != '' GROUP BY immunization_record");
$report['medical_summary']['vaccines_administered'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
// Maternal & Child Health
$stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE civil_status = 'Pregnant' AND deleted_at IS NULL");
$report['medical_summary']['pregnant_women'] = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM consultation_notes WHERE note LIKE '%prenatal%' AND consultation_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$report['medical_summary']['prenatal_visits'] = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM child_health_records");
$report['medical_summary']['infants_monitored'] = (int)$stmt->fetchColumn();

// 3. Consultation Records
$report['consultation_records'] = [];
$stmt = $pdo->query("SELECT COUNT(*) FROM consultation_notes");
$report['consultation_records']['total_consultations'] = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT visit_type, COUNT(*) as count FROM patient_visits GROUP BY visit_type");
$report['consultation_records']['consultations_by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt = $pdo->query("SELECT symptoms, COUNT(*) as count FROM patient_visits WHERE symptoms IS NOT NULL AND symptoms != '' GROUP BY symptoms ORDER BY count DESC LIMIT 5");
$report['consultation_records']['top_reasons'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt = $pdo->query("SELECT referral_info, COUNT(*) as count FROM patient_visits WHERE referral_info IS NOT NULL AND referral_info != '' GROUP BY referral_info");
$referrals = [];
foreach ($stmt as $row) {
    $referrals[$row['referral_info']] = (int)$row['count'];
}
$report['consultation_records']['referrals_made'] = $referrals;

// 4. Doctor’s Notes & Case Summaries
$report['doctor_notes'] = [];
$stmt = $pdo->query("SELECT diagnosis, COUNT(*) as count FROM patient_visits WHERE diagnosis IS NOT NULL AND diagnosis != '' GROUP BY diagnosis ORDER BY count DESC LIMIT 10");
$report['doctor_notes']['diagnoses'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt = $pdo->query("SELECT treatment, COUNT(*) as count FROM patient_visits WHERE treatment IS NOT NULL AND treatment != '' GROUP BY treatment ORDER BY count DESC LIMIT 10");
$report['doctor_notes']['treatments'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt = $pdo->query("SELECT disease, COUNT(*) as count FROM sitio1_patients WHERE disease IN ('COVID-19', 'Dengue', 'Measles', 'Tuberculosis') GROUP BY disease");
$report['doctor_notes']['cases_for_city_health'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. Public Health Indicators
$report['public_health'] = [];
foreach (['Dengue', 'Measles', 'COVID-19'] as $disease) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE disease = ?");
    $stmt->execute([$disease]);
    $report['public_health']['notifiable'][$disease] = (int)$stmt->fetchColumn();
}
$stmt = $pdo->query("SELECT title, details FROM health_campaigns");
$campaigns = [];
foreach ($stmt as $row) {
    $campaigns[] = $row;
}
$report['public_health']['campaigns'] = $campaigns;
// Community Health Trends (trend in top 3 diseases over last 6 months)
$trend = [];
$diseases = $pdo->query("SELECT disease FROM sitio1_patients WHERE disease IS NOT NULL AND disease != '' GROUP BY disease ORDER BY COUNT(*) DESC LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
foreach ($diseases as $disease) {
    $monthly = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE disease = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$disease, $month]);
        $monthly[$month] = (int)$stmt->fetchColumn();
    }
    $trend[$disease] = $monthly;
}
$report['public_health']['community_trends'] = $trend;

// 6. Administrative Data
$report['admin'] = [];
$stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients");
$totalPatients = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE position = 'Doctor'");
$totalDoctors = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE position = 'Nurse'");
$totalNurses = (int)$stmt->fetchColumn();
$report['admin']['avg_patients_per_doctor'] = $totalDoctors ? round($totalPatients / $totalDoctors, 2) : 0;
$report['admin']['avg_patients_per_nurse'] = $totalNurses ? round($totalPatients / $totalNurses, 2) : 0;
// Supplies and challenges (placeholder, as these may be tracked elsewhere)
$report['admin']['supplies'] = 'See inventory system or manual records.';
$report['admin']['challenges'] = 'See staff meeting notes or manual records.';

// 7. Recommendations (placeholder)
$report['recommendations'] = 'Please review the above data and provide recommendations for support, resources, or interventions as needed.';

// Prepared by
$report['prepared_by'] = isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] . ', ' . ($_SESSION['user']['role'] ?? 'Staff') : 'Unknown';
$report['date'] = date('F d, Y');

echo json_encode(['success' => true, 'data' => $report]);
