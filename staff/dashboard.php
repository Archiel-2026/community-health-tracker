<?php
require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for staff after 1 hour of inactivity ---
if (isStaff()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 3600) { // 1 hour = 3600 seconds
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/index-admin-staff.php');
            exit();
        } else {
            $_SESSION['last_action'] = $now;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;

// Function to generate unique number
function generateUniqueNumber($pdo)
{
    $prefix = 'CHT';
    $unique = false;
    $uniqueNumber = '';

    while (!$unique) {
        $randomNumber = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $uniqueNumber = $prefix . $randomNumber;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_users WHERE unique_number = ?");
        $stmt->execute([$uniqueNumber]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $unique = true;
        }
    }

    return $uniqueNumber;
}

// Function to send email notification
function sendAccountStatusEmail($email, $status, $message = '', $uniqueNumber = '')
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cabanagarchiel@gmail.com';
        $mail->Password = 'qmdh ofnf bhfj wxsa';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('cabanagarchiel@gmail.com', 'Barangay Luz Health Center');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);

        if ($status === 'approved') {
            $mail->Subject = 'Barangay Luz Health Monitoring and Tracking System';
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <body style="margin:0; padding:0; background-color:#ffffff; font-family: Arial, Helvetica, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;">
            <tr>
            <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
            <tr>
            <td style="background-color:#2563eb; padding:30px; text-align:center;">
                <div style="font-size:26px; font-weight:bold; color:#ffffff;">
                    <img src="/asssets/images/Luz.jpg" style="width: 100px; height: auto; margin-right: 10px;">
                    Barangay Luz Health Monitoring and Tracking
                </div>
                <div style="margin-top:8px; font-size:16px; color:#dbeafe;">
                    Account Approval Notice
                </div>
            </td>
            </tr>
            <tr>
            <td style="padding:30px; color:#1f2937; font-size:15px; line-height:1.7;">
            <p>Hello, </p>
            <p>
            We are happy to inform you that your registration has been
            <strong style="color:#2563eb;">successfully approved</strong>.
            Your account is now active and ready for use.
            </p>
            <div style="
                background-color:#eff6ff;
                border:1px solid #3b82f6;
                border-radius:8px;
                padding:16px;
                text-align:center;
                margin:25px 0;
            ">
                <div style="font-size:13px; color:#2563eb; margin-bottom:6px;">
                    Your Unique Identification Number
                </div>
                <div style="font-size:22px; font-weight:bold; letter-spacing:1px; color:#1e3a8a;">
                    ' . $uniqueNumber . '
                </div>
            </div>
            <p>
            Please keep this number secure. It will be used for medical records and identity verification.
            </p>
            <ul style="padding-left:18px;">
                <li>View medical history</li>
                <li>Receive health updates</li>
                <li>Access health services</li>
            </ul>
            <div style="text-align:center; margin-top:30px;">
                <a href="https://your-health-portal.com/login"
                   style="
                    background-color:#3b82f6;
                    color:#ffffff;
                    text-decoration:none;
                    padding:14px 28px;
                    border-radius:6px;
                    font-size:15px;
                    display:inline-block;
                   ">
                    Access Your Account
                </a>
            </div>
            <p style="margin-top:30px;">
            Thank you for trusting <strong>Barangay Luz Health Monitoring and Tracking Platform</strong> with your healthcare needs.
            </p>
            <p>
            Warm regards,<br>
            <strong>The Barangay Luz Health Center Team</strong>
            </p>
            </td>
            </tr>
            <tr>
            <td style="
                background-color:#f8fafc;
                padding:20px;
                text-align:center;
                font-size:12px;
                color:#6b7280;
                border-top:1px solid #e5e7eb;
            ">
            This is an automated message. Please do not reply.<br>
            © ' . date('Y') . ' Barangay Luz Health Monitoring and Tracking System
            </td>
            </tr>
            </table>
            </td>
            </tr>
            </table>
            </body>
            </html>
            ';
        } else {
            $mail->Subject = 'Account Registration Update – Barangay Luz Health Monitoring and Tracking System';
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <body style="margin:0; padding:0; background-color:#ffffff; font-family: Arial, Helvetica, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
            <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px;">
            <tr>
            <td style="background-color:#3b82f6; padding:30px; text-align:center;">
                <div style="font-size:26px; font-weight:bold; color:#ffffff;">
                    🏥 Barangay Luz Health Monitoring and Tracking System
                </div>
                <div style="margin-top:8px; font-size:16px; color:#dbeafe;">
                    Registration Status Update
                </div>
            </td>
            </tr>
            <tr>
            <td style="padding:30px; color:#1f2937; font-size:15px; line-height:1.7;">
            <p>Hello,</p>
            <p>
            Thank you for submitting your registration. After careful review,
            we are unable to approve your account at this time.
            </p>
            <div style="
                background-color:#f8fafc;
                border-left:4px solid #3b82f6;
                padding:15px;
                margin:20px 0;
            ">
                <strong>Reason Provided:</strong><br>
                ' . htmlspecialchars($message) . '
            </div>
            <p>
            You may reapply or contact our support team if you believe this decision
            requires further review.
            </p>
            <div style="
                background-color:#eff6ff;
                padding:15px;
                border-radius:8px;
                margin:25px 0;
            ">
                <strong>Support Contact</strong><br>
                📞 (02) 8-123-4567<br>
                ✉️ support@communityhealthtracker.ph
            </div>
            <p>
            We appreciate your understanding and interest in our services.
            </p>
            <p>
            Sincerely,<br>
            <strong>The Barangay Luz Health Monitoring and Tracking System Team</strong>
            </p>
            </td>
            </tr>
            <tr>
            <td style="
                background-color:#f8fafc;
                padding:20px;
                text-align:center;
                font-size:12px;
                color:#6b7280;
                border-top:1px solid #e5e7eb;
            ">
            This is an automated message. Please do not reply.<br>
            © ' . date('Y') . ' Barangay Luz Health Monitoring and Tracking System
            </td>
            </tr>
            </table>
            </td>
            </tr>
            </table>
            </body>
            </html>
            ';
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to automatically check ID type
function checkIdType($imagePath)
{
    // Common keywords for different ID types (lowercase for matching)
    $idKeywords = [
        'senior' => ['senior', 'sc', 'senior citizen', 'senior-citizen', 'osc', 'office of senior citizen', 'senior card', 's.c.', 'sc id', 'senior citizen id'],
        'national' => ['national', 'phil', 'philippine', 'phil id', 'phil-id', 'philid', 'national id', 'philippine id', 'phil sys', 'philsys', 'phil. id', 'philippine national id', 'pnid'],
        'driver' => ['driver', 'license', 'dl', 'lto', 'land transportation', 'driver\'s license', 'driving license', 'driver license'],
        'voter' => ['voter', 'comelec', 'voter id', 'voter-id', 'voter\'s id', 'voters id', 'voter certification'],
        'passport' => ['passport', 'dfa', 'department of foreign affairs', 'philippine passport', 'passport id'],
        'umid' => ['umid', 'unified', 'multi-purpose', 'unified multi-purpose', 'unified id', 'multi-purpose id'],
        'sss' => ['sss', 'social security', 'social security system', 'sss id', 'sss card', 'sss number'],
        'gsis' => ['gsis', 'government service insurance system', 'gsis id', 'gsis card'],
        'tin' => ['tin', 'tax', 'taxpayer', 'taxpayer identification', 'tin id', 'tin card', 'tax identification'],
        'postal' => ['postal', 'post office', 'philpost', 'postal id', 'post office id'],
        'prc' => ['prc', 'professional', 'professional regulation', 'prc id', 'prc license', 'professional id'],
        'nbi' => ['nbi', 'national bureau', 'national bureau of investigation', 'nbi clearance', 'nbi id'],
        'birth' => ['birth', 'certificate', 'birth certificate', 'psa', 'civil registry', 'certificate of live birth'],
        'company' => ['company', 'employee', 'employee id', 'company id', 'office', 'work', 'office id', 'work id', 'employment id'],
        'student' => ['student', 'school', 'university', 'college', 'school id', 'student id', 'student card', 'university id']
    ];

    // Extract filename without extension and path
    $filename = strtolower(pathinfo($imagePath, PATHINFO_FILENAME));

    // Check if image path contains any ID keywords
    $lowerPath = strtolower($imagePath);

    foreach ($idKeywords as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($filename, $keyword) !== false || strpos($lowerPath, $keyword) !== false) {
                return ucfirst($type) . ' ID';
            }
        }
    }

    // If no specific type found, check for general ID indicators
    $generalIdIndicators = ['id', 'identification', 'card', 'certificate', 'license'];
    foreach ($generalIdIndicators as $indicator) {
        if (strpos($filename, $indicator) !== false || strpos($lowerPath, $indicator) !== false) {
            return 'Valid ID';
        }
    }

    return 'Unknown ID Type';
}

// Function to check if ID is valid for verification (Senior Citizen or National ID)
function isIdValidForVerification($idType)
{
    $validTypes = ['Senior ID', 'National ID'];

    foreach ($validTypes as $validType) {
        if (stripos($idType, $validType) !== false) {
            return true;
        }
    }

    return false;
}

// Get stats for dashboard
$stats = [
    'total_patients' => 0,
    'unapproved_users' => 0
];

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_user'])) {
        $userId = intval($_POST['user_id']);
        $action = $_POST['action'];

        // Get user details first
        try {
            $stmt = $pdo->prepare("SELECT * FROM sitio1_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found.");
            }

            if (in_array($action, ['approve', 'decline'])) {
                if ($action === 'approve') {
                    // Generate unique number
                    $uniqueNumber = generateUniqueNumber($pdo);

                    $stmt = $pdo->prepare("UPDATE sitio1_users SET approved = TRUE, unique_number = ?, status = 'approved' WHERE id = ?");
                    $stmt->execute([$uniqueNumber, $userId]);

                    // Send approval email
                    if (isset($user['email'])) {
                        sendAccountStatusEmail($user['email'], 'approved', '', $uniqueNumber);
                    }

                    $_SESSION['success_message'] = 'Resident Account Approved Successfully!<br>Patient ID: <strong>' . $uniqueNumber . '</strong>';

                    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=account-management&success=true");
                    exit();
                } else {
                    $declineReason = isset($_POST['decline_reason']) ? trim($_POST['decline_reason']) : 'No reason provided';

                    $stmt = $pdo->prepare("UPDATE sitio1_users SET approved = FALSE, status = 'declined' WHERE id = ?");
                    $stmt->execute([$userId]);

                    // Send decline email with reason
                    if (isset($user['email'])) {
                        sendAccountStatusEmail($user['email'], 'declined', $declineReason);
                    }

                    $_SESSION['success_message'] = 'Account Declined Successfully!';

                    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=account-management&success=true");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Error processing user: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Check for success message from session (after redirect)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get active tab from URL parameter
$activeTab = $_GET['tab'] ?? 'analytics';

// Get data for dashboard
try {
    // Basic stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL");
    $stats['total_patients'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_users WHERE role = 'patient' AND approved = TRUE");
    $stats['resident_users'] = $stmt->fetchColumn();

    // Analytics data for charts
    $analytics = [];

    // Total patient records
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_patients WHERE deleted_at IS NULL");
    $analytics['total_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Health issues count and rate
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_patients WHERE deleted_at IS NULL AND disease IS NOT NULL AND disease != ''");
    $analytics['health_issues_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $analytics['health_issues_rate'] = $analytics['total_patients'] > 0 ?
        round(($analytics['health_issues_count'] / $analytics['total_patients']) * 100) : 0;

    // Top health issues
    $stmt = $pdo->query("SELECT disease as label, COUNT(*) as count FROM sitio1_patients WHERE deleted_at IS NULL AND disease IS NOT NULL AND disease != '' GROUP BY disease ORDER BY count DESC LIMIT 6");
    $analytics['top_diseases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Approved patients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_users WHERE role = 'patient' AND approved = TRUE");
    $analytics['approved_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Patient records trend (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM sitio1_patients 
        WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $analytics['patient_registration_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consultations per month (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(consultation_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM consultation_notes
        WHERE consultation_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(consultation_date, '%Y-%m')
        ORDER BY month
    ");
    $analytics['consultations_per_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get approval statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_users WHERE role = 'patient' AND approved = TRUE");
    $analytics['approved_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_users WHERE role = 'patient' AND (approved = FALSE OR status = 'declined')");
    $analytics['pending_declined_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sitio1_users WHERE role = 'patient' AND approved = FALSE AND (status IS NULL OR status != 'declined')");
    $analytics['pending_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get patient distribution by gender
    $stmt = $pdo->query("
        SELECT gender, COUNT(*) as count 
        FROM sitio1_patients 
        WHERE deleted_at IS NULL AND gender IS NOT NULL AND gender != ''
        GROUP BY gender
    ");
    $analytics['gender_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get patient distribution by age group
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN age < 18 THEN 'Under 18'
                WHEN age BETWEEN 18 AND 30 THEN '18-30'
                WHEN age BETWEEN 31 AND 45 THEN '31-45'
                WHEN age BETWEEN 46 AND 60 THEN '46-60'
                WHEN age > 60 THEN 'Over 60'
                ELSE 'Unknown'
            END as age_group,
            COUNT(*) as count
        FROM sitio1_patients 
        WHERE deleted_at IS NULL
        GROUP BY age_group
        ORDER BY 
            CASE age_group
                WHEN 'Under 18' THEN 1
                WHEN '18-30' THEN 2
                WHEN '31-45' THEN 3
                WHEN '46-60' THEN 4
                WHEN 'Over 60' THEN 5
                ELSE 6
            END
    ");
    $analytics['age_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Staff action metrics (connected to staff actions)
    $staffId = $_SESSION['user']['id'] ?? null;
    $analytics['staff_actions_30d'] = 0;
    $analytics['patients_added_by_staff'] = 0;
    $analytics['consultations_logged'] = 0;

    if ($staffId) {
        // Count consultations in the last 30 days
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM consultation_notes WHERE created_by = ? AND consultation_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute([$staffId]);
            $analytics['staff_actions_30d'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            $analytics['staff_actions_30d'] = 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL AND added_by = ?");
            $stmt->execute([$staffId]);
            $analytics['patients_added_by_staff'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            $analytics['patients_added_by_staff'] = 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM consultation_notes WHERE created_by = ?");
            $stmt->execute([$staffId]);
            $analytics['consultations_logged'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            $analytics['consultations_logged'] = 0;
        }
    }

    // Get resident users with pagination, search and filter
    // Show only 5 per page for dashboard preview
    $usersPerPage = isset($_GET['view_all_residents']) && $_GET['view_all_residents'] == '1' ? 1000 : 5;
    $currentPage = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
    $offset = ($currentPage - 1) * $usersPerPage;

    // Get search and filter parameters
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'desc'; // 'asc' or 'desc'

    // Build query with search and filters
    $whereConditions = ["role = 'patient'", "approved = TRUE"];
    $params = [];

    if (!empty($searchQuery)) {
        $whereConditions[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ? OR unique_number LIKE ?)";
        $searchParam = "%" . $searchQuery . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $whereClause = implode(' AND ', $whereConditions);
    $orderBy = $sortOrder === 'asc' ? 'ASC' : 'DESC';

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM sitio1_users WHERE " . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    if (!empty($params)) {
        $countStmt->execute($params);
    } else {
        $countStmt->execute();
    }
    $totalResidentUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalResidentUsers / $usersPerPage);

    // Get paginated resident users
    $sql = "
        SELECT *, 
               CASE 
                   WHEN id_image_path IS NOT NULL AND id_image_path != '' THEN 
                       CASE 
                           WHEN id_image_path LIKE 'http%' THEN id_image_path
                           WHEN id_image_path LIKE '/%' THEN id_image_path
                           ELSE CONCAT('../', id_image_path)
                       END
                   ELSE NULL 
               END as display_image_path
        FROM sitio1_users 
        WHERE " . $whereClause . "
        ORDER BY created_at " . $orderBy . "
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);

    // Build execute parameters array
    $executeParams = $params;
    $executeParams[] = $usersPerPage;
    $executeParams[] = $offset;

    // Bind all parameters as appropriate types
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, PDO::PARAM_STR);
    }
    $stmt->bindValue(count($params) + 1, $usersPerPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $residentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Automatically check ID type for each user
    foreach ($residentUsers as &$user) {
        if (!empty($user['id_image_path'])) {
            $user['id_type'] = checkIdType($user['id_image_path']);
            $user['is_valid_id'] = isIdValidForVerification($user['id_type']);
        } else {
            $user['id_type'] = 'No ID Uploaded';
            $user['is_valid_id'] = false;
        }
    }
    unset($user); // Break the reference

} catch (PDOException $e) {
    $error = 'Error fetching data: ' . $e->getMessage();
    // Initialize analytics array with default values on error
    $analytics = [
        'total_patients' => 0,
        'approved_patients' => 0,
        'patient_registration_trend' => [],
        'approved_count' => 0,
        'pending_declined_count' => 0,
        'pending_count' => 0,
        'approval_rate' => 0,
        'health_issues_count' => 0,
        'health_issues_rate' => 0,
        'top_diseases' => [],
        'staff_actions_30d' => 0,
        'patients_added_by_staff' => 0,
        'consultations_logged' => 0,
        'gender_distribution' => [],
        'age_distribution' => []
    ];
}

// Pagination configuration
$recordsPerPage = 5;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Community Health Tracker</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/asssets/css/normalize.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Enhanced Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            position: relative;
            margin: auto;
        }

        .modal-overlay.active .modal-container {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 24px 24px 0 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 0 24px 24px 24px;
        }

        /* Action Modal */
        .action-modal {
            max-width: 400px;
        }

        .action-modal .modal-body {
            text-align: center;
            padding: 40px 24px;
        }

        .action-modal-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
        }

        .action-modal-success .action-modal-icon {
            background: #d1fae5;
            color: #059669;
        }

        .action-modal-error .action-modal-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .hidden {
            display: none;
        }

        .tab-active {
            border-bottom: 2px solid #3C96E1;
            color: #3C96E1;
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.8rem;
            height: 1.8rem;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0 0.6rem;
            margin-left: 0.5rem;
        }

        .stat-count {
            width: 3.5rem;
            height: 3.5rem;
            display: inline-flex;
            align-items: center;
            border-radius: 0.75rem;
            font-size: 2rem;
            font-weight: 700;
            line-height: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px 36px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            min-height: 7.5rem;
        }

        .chart-container,
        .chart-container-two {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {

            .chart-container,
            .chart-container-two,
            .stat-card {
                padding: 16px;
                border-radius: 8px;
            }
        }

        @media (max-width: 640px) {

            .chart-container,
            .chart-container-two,
            .stat-card {
                padding: 12px;
                border-radius: 8px;
            }
        }

        /* Button styles */
        .btn-blue {
            background: #3C96E1 !important;
            color: white !important;
            border-radius: 6px !important;
            padding: 12px 24px !important;
            font-weight: 400 !important;
            font-size: 1.2rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(60, 150, 225, 0.3) !important;
        }

        .btn-blue:hover {
            background: #2a7bc8 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(60, 150, 225, 0.4) !important;
        }

        .btn-success-blue {
            background: #48BB78 !important;
            color: white !important;
            border-radius: 9999px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(72, 187, 120, 0.3) !important;
        }

        .btn-success-blue:hover {
            background: #38A169 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(72, 187, 120, 0.4) !important;
        }

        .btn-danger-blue {
            background: #F56565 !important;
            color: white !important;
            border-radius: 9999px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(245, 101, 101, 0.3) !important;
        }

        .btn-danger-blue:hover {
            background: #E53E3E !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(245, 101, 101, 0.4) !important;
        }

        .btn-view-details {
            background-color: #3C96E1 !important;
            color: white !important;
            border-radius: 9999px !important;
            padding: 8px 16px !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(60, 150, 225, 0.3) !important;
        }

        .btn-view-details:hover {
            background-color: #2a7bc8 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(60, 150, 225, 0.4) !important;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 8px;
        }

        .pagination-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            text-decoration: none;
        }

        .pagination-button:hover {
            background: #3C96E1;
            color: white;
            border-color: #3C96E1;
            transform: translateY(-1px);
        }

        .pagination-button.active {
            background: #3C96E1;
            color: white;
            border-color: #3C96E1;
        }

        .pagination-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f3f4f6;
            color: #9ca3af;
        }

        /* Tab Button Styles */
        .nav-tab-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 18px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            margin-right: 12px;
            margin-bottom: 8px;
            background: #3C96E14D;
            color: #3C96E1;
        }

        /* .nav-tab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(60, 150, 225, 0.3);
            background: #2a7bc8;
        } */

        .nav-tab-button.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(60, 150, 225, 0.4);
            background: #3C96E1;
            color: white;
            border: 2px solid #3C96E1;
        }

        .nav-tab-button i {
            margin-right: 8px;
            font-size: 18px;
        }

        .nav-tab-button .count-badge {
            margin-left: 8px;
            font-size: 0.8rem;
            min-width: 1.6rem;
            height: 1.6rem;
            background: #3C96E1;
            color: #FFFFFF;
        }

        .nav-tab-button.active .count-badge {
            background: #FFFFFF;
            color: #3C96E1;
        }

        /* User details styles */
        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .detail-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
            flex: 1;
        }

        .detail-value {
            color: #2d3748;
            flex: 2;
            text-align: right;
        }

        .id-preview-container {
            max-height: 200px;
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .id-preview-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* Chart Container Styles */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            height: 400px;
            position: relative;
            overflow: hidden;
        }

        .chart-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }

        .chart-title i {
            margin-right: 8px;
            color: #3C96E1;
        }

        /* Analytics grid layout improvements */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Chart canvas responsive sizing */
        .chart-container canvas {
            max-width: 100% !important;
            max-height: 100% !important;
            width: auto !important;
            height: auto !important;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .analytics-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .analytics-value {
            font-size: 32px;
            font-weight: 700;
            color: #3C96E1;
            margin: 8px 0;
        }

        .analytics-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .analytics-trend {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .trend-up {
            background: #dcfce7;
            color: #166534;
        }

        .trend-down {
            background: #fecaca;
            color: #991b1b;
        }

        .trend-neutral {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* Chart responsive sizing */
        #patientRegistrationChart,
        #healthIssuesChart,
        #genderDistributionChart,
        #ageDistributionChart {
            display: block !important;
            width: 100% !important;
            height: 300px !important;
            min-height: 300px !important;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
            min-height: 300px;
        }

        .chart-container canvas {
            display: block !important;
            width: 100% !important;
            height: 300px !important;
            min-height: 300px !important;
        }
    </style>
</head>

<body class="bg-gray-100">

    <!-- AJAX Loader -->
    <div id="ajaxLoader" class="cht-analytics-loader-bg" style="display: none;">
        <div style="display: flex; flex-direction: column; align-items: center;">
            <div class="cht-loader-unique">
                <div class="cht-loader-bounce"></div>
                <div class="cht-loader-bounce"></div>
                <div class="cht-loader-bounce"></div>
            </div>
            <div class="cht-loader-text">Loading analytics...</div>
        </div>
    </div>

    <div class="w-full px-8 py-10 lg:px-8">
        <!-- Dashboard Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-700 flex items-center">
                Admin Dashboard
            </h1>
        </div>

        <!-- Stats Cards -->
        <div
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-6 mb-8">
            <div class="bg-white rounded-lg shadow stat-card inline-block">
                <div class="flex flex-col md:flex-row items-center justify-between mb-12">
                    <div class="stat-count">
                        <?= number_format($stats['total_patients']) ?>
                    </div>
                    <div>
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#2563EB" fill-opacity="0.3" />
                            <g clip-path="url(#clip0_1737_8855)">
                                <path
                                    d="M42.1437 26.8164C42.3558 26.7456 42.5463 26.6218 42.6971 26.4568C42.8479 26.2918 42.9541 26.0909 43.0055 25.8733C43.057 25.6558 43.052 25.4286 42.991 25.2135C42.93 24.9984 42.8152 24.8024 42.6572 24.6442L23.7096 5.69662C23.4635 5.45066 23.1298 5.3125 22.7819 5.3125C22.4339 5.3125 22.1002 5.45066 21.8541 5.69662L15.1915 12.3674L11.1556 8.32162C10.9093 8.07556 10.5753 7.93741 10.2272 7.93756C9.87908 7.93772 9.54526 8.07616 9.2992 8.32244C9.05314 8.56872 8.91499 8.90266 8.91514 9.25079C8.9153 9.59893 9.05374 9.93275 9.30002 10.1788L13.336 14.2147L6.12705 21.4187C5.38868 22.1571 4.97388 23.1586 4.97388 24.2029C4.97388 25.2471 5.38868 26.2486 6.12705 26.987L20.0543 40.9143C20.7927 41.6526 21.7942 42.0675 22.8385 42.0675C23.8827 42.0675 24.8842 41.6526 25.6226 40.9143L38.4999 28.037L42.1437 26.8164ZM36.8625 25.9567L23.7654 39.0538C23.5193 39.2998 23.1856 39.4379 22.8376 39.4379C22.4897 39.4379 22.156 39.2998 21.9099 39.0538L7.98752 25.1315C7.74156 24.8853 7.6034 24.5516 7.6034 24.2037C7.6034 23.8557 7.74156 23.522 7.98752 23.2759L15.1915 16.0703L19.969 20.8478C19.4447 21.8187 19.2873 22.9458 19.5257 24.0232C19.7642 25.1006 20.3824 26.0561 21.2674 26.7151C22.1524 27.3741 23.245 27.6926 24.3455 27.6122C25.446 27.5319 26.4808 27.0581 27.2607 26.2775C28.0406 25.4969 28.5135 24.4617 28.5928 23.3611C28.6722 22.2606 28.3528 21.1682 27.693 20.2838C27.0332 19.3994 26.0771 18.782 24.9996 18.5445C23.922 18.3071 22.795 18.4654 21.8246 18.9906L17.0487 14.2147L22.7909 8.48076L39.2988 25.0002L37.376 25.6401C37.1828 25.7048 37.0071 25.8131 36.8625 25.9567ZM22.617 21.6402C22.9873 21.1985 23.5178 20.9218 24.0919 20.8709C24.6661 20.8199 25.237 20.999 25.6793 21.3686C26.1215 21.7383 26.399 22.2684 26.4508 22.8425C26.5025 23.4166 26.3244 23.9877 25.9553 24.4305C25.5863 24.8733 25.0567 25.1516 24.4827 25.2042C23.9086 25.2568 23.3372 25.0795 22.8939 24.7111C22.4505 24.3428 22.1715 23.8135 22.118 23.2396C22.0646 22.6656 22.2411 22.094 22.6088 21.6501C22.6088 21.6501 22.617 21.6419 22.617 21.6402ZM42.8213 30.8277C42.7015 30.6477 42.539 30.5 42.3484 30.3979C42.1578 30.2958 41.9449 30.2424 41.7286 30.2424C41.5124 30.2424 41.2995 30.2958 41.1088 30.3979C40.9182 30.5 40.7558 30.6477 40.636 30.8277C40.3456 31.2723 37.7911 35.1869 37.7911 38.1252C37.7911 39.1695 38.206 40.171 38.9444 40.9095C39.6828 41.6479 40.6843 42.0627 41.7286 42.0627C42.7729 42.0627 43.7744 41.6479 44.5128 40.9095C45.2513 40.171 45.6661 39.1695 45.6661 38.1252C45.6661 35.1869 43.1117 31.2723 42.8213 30.8343V30.8277ZM41.7286 39.4377C41.3805 39.4377 41.0467 39.2994 40.8005 39.0533C40.5544 38.8072 40.4161 38.4733 40.4161 38.1252C40.4161 37.0096 41.0724 35.4477 41.7286 34.1746C42.3849 35.4477 43.0411 37.0194 43.0411 38.1252C43.0411 38.4733 42.9028 38.8072 42.6567 39.0533C42.4105 39.2994 42.0767 39.4377 41.7286 39.4377Z"
                                    fill="#3C96E1" />
                            </g>
                            <defs>
                                <clipPath id="clip0_1737_8855">
                                    <rect width="42" height="42" fill="white" transform="translate(3.66602 4)" />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-medium text-gray-500">Total Patients</h3>
            </div>
            <div class="bg-white p-6 rounded-lg shadow stat-card inline-block">
                <div class="flex flex-col md:flex-row items-center justify-between mb-12">
                    <div class="stat-count">
                        <?= number_format($stats['resident_users']) ?>
                    </div>
                    <div>
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#D97706" fill-opacity="0.3" />
                            <path
                                d="M28.6035 18.4375C29.6418 18.4375 30.6569 18.1296 31.5202 17.5527C32.3836 16.9758 33.0565 16.1559 33.4538 15.1966C33.8512 14.2373 33.9552 13.1817 33.7526 12.1633C33.55 11.1449 33.05 10.2094 32.3158 9.47519C31.5816 8.74097 30.6461 8.24095 29.6277 8.03838C28.6093 7.83581 27.5537 7.93977 26.5944 8.33713C25.6351 8.73449 24.8151 9.4074 24.2383 10.2708C23.6614 11.1341 23.3535 12.1492 23.3535 13.1875C23.3535 14.5799 23.9066 15.9152 24.8912 16.8998C25.8757 17.8844 27.2111 18.4375 28.6035 18.4375ZM28.6035 10.5625C29.1227 10.5625 29.6302 10.7165 30.0618 11.0049C30.4935 11.2933 30.83 11.7033 31.0287 12.183C31.2273 12.6626 31.2793 13.1904 31.178 13.6996C31.0767 14.2088 30.8267 14.6765 30.4596 15.0437C30.0925 15.4108 29.6248 15.6608 29.1156 15.7621C28.6064 15.8634 28.0786 15.8114 27.5989 15.6127C27.1193 15.414 26.7093 15.0776 26.4209 14.6459C26.1324 14.2142 25.9785 13.7067 25.9785 13.1875C25.9785 12.4913 26.255 11.8236 26.7473 11.3313C27.2396 10.8391 27.9073 10.5625 28.6035 10.5625ZM39.6465 27.0803C39.5464 27.1263 38.4177 27.6184 36.4194 27.6184C34.1471 27.6184 30.7511 26.9819 26.4625 24.3372C25.8097 26.1902 24.9622 27.9688 23.9343 29.643C25.7807 30.2114 27.5175 31.0884 29.0711 32.2368C32.1997 34.6223 33.8535 38.0184 33.8535 42.0625C33.8535 42.4106 33.7152 42.7444 33.4691 42.9906C33.2229 43.2367 32.8891 43.375 32.541 43.375C32.1929 43.375 31.859 43.2367 31.6129 42.9906C31.3668 42.7444 31.2285 42.4106 31.2285 42.0625C31.2285 35.2211 25.5371 32.7585 22.3461 31.9152C22.2559 32.0301 22.1624 32.1466 22.0689 32.2598C18.8467 36.1645 14.8091 38.1955 10.3171 38.1955C9.80542 38.1979 9.29399 38.1744 8.78472 38.125C8.43663 38.0902 8.11662 37.9185 7.89509 37.6478C7.67356 37.377 7.56866 37.0293 7.60347 36.6813C7.63828 36.3332 7.80995 36.0131 8.0807 35.7916C8.35146 35.5701 8.69913 35.4652 9.04722 35.5C13.2997 35.9233 16.9993 34.2712 20.0394 30.5781C22.0886 28.0942 23.4847 25.064 24.182 22.8672C17.7967 19.1512 13.7181 22.3143 13.6738 22.3488C13.5401 22.4629 13.3848 22.5491 13.2172 22.6021C13.0496 22.6551 12.873 22.6739 12.698 22.6573C12.5229 22.6408 12.353 22.5892 12.1983 22.5057C12.0436 22.4223 11.9072 22.3085 11.7972 22.1713C11.6873 22.0341 11.6061 21.8763 11.5583 21.7071C11.5106 21.5379 11.4973 21.3608 11.5193 21.1864C11.5413 21.0119 11.5981 20.8437 11.6864 20.6917C11.7747 20.5396 11.8926 20.4068 12.0332 20.3013C12.2793 20.1044 18.1396 15.5434 26.7184 21.3791C34.1783 26.4503 38.5194 24.7113 38.5604 24.6916C38.7177 24.6173 38.8881 24.575 39.0618 24.5672C39.2355 24.5594 39.4091 24.5862 39.5724 24.646C39.7357 24.7058 39.8854 24.7974 40.013 24.9156C40.1405 25.0338 40.2433 25.1762 40.3154 25.3345C40.3875 25.4927 40.4274 25.6637 40.4328 25.8375C40.4382 26.0113 40.409 26.1845 40.3469 26.3469C40.2848 26.5094 40.1911 26.6579 40.0711 26.7838C39.9512 26.9097 39.8074 27.0105 39.6482 27.0803H39.6465Z"
                                fill="#D97706" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-medium text-gray-500">Resident Accounts</h3>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow stat-card inline-block">
                <div class="flex flex-col md:flex-row items-center justify-between mb-12">
                    <div class="stat-count">
                        <?php $stmt = $pdo->query("SELECT s.full_name AS doctor, COUNT(*) as cnt FROM consultation_notes cn JOIN sitio1_staff s ON cn.created_by = s.id GROUP BY s.id");
                        $doctorNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $totalDoctorNotes = array_sum(array_column($doctorNotes, 'cnt'));
                        echo number_format($totalDoctorNotes); ?>
                    </div>
                    <div>
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#9333EA" fill-opacity="0.3" />
                            <path
                                d="M18.1035 19.75C18.1035 19.4019 18.2418 19.0681 18.4879 18.8219C18.7341 18.5758 19.0679 18.4375 19.416 18.4375H29.916C30.2641 18.4375 30.598 18.5758 30.8441 18.8219C31.0902 19.0681 31.2285 19.4019 31.2285 19.75C31.2285 20.0981 31.0902 20.4319 30.8441 20.6781C30.598 20.9242 30.2641 21.0625 29.916 21.0625H19.416C19.0679 21.0625 18.7341 20.9242 18.4879 20.6781C18.2418 20.4319 18.1035 20.0981 18.1035 19.75ZM19.416 26.3125H29.916C30.2641 26.3125 30.598 26.1742 30.8441 25.9281C31.0902 25.6819 31.2285 25.3481 31.2285 25C31.2285 24.6519 31.0902 24.3181 30.8441 24.0719C30.598 23.8258 30.2641 23.6875 29.916 23.6875H19.416C19.0679 23.6875 18.7341 23.8258 18.4879 24.0719C18.2418 24.3181 18.1035 24.6519 18.1035 25C18.1035 25.3481 18.2418 25.6819 18.4879 25.9281C18.7341 26.1742 19.0679 26.3125 19.416 26.3125ZM24.666 28.9375H19.416C19.0679 28.9375 18.7341 29.0758 18.4879 29.3219C18.2418 29.5681 18.1035 29.9019 18.1035 30.25C18.1035 30.5981 18.2418 30.9319 18.4879 31.1781C18.7341 31.4242 19.0679 31.5625 19.416 31.5625H24.666C25.0141 31.5625 25.348 31.4242 25.5941 31.1781C25.8402 30.9319 25.9785 30.5981 25.9785 30.25C25.9785 29.9019 25.8402 29.5681 25.5941 29.3219C25.348 29.0758 25.0141 28.9375 24.666 28.9375ZM40.416 11.875V29.707C40.4171 30.0518 40.3497 30.3934 40.2176 30.712C40.0855 31.0305 39.8914 31.3196 39.6466 31.5625L31.2285 39.9805C30.9856 40.2254 30.6965 40.4195 30.378 40.5516C30.0594 40.6836 29.7178 40.7511 29.373 40.75H11.541C10.8448 40.75 10.1771 40.4734 9.68486 39.9812C9.19258 39.4889 8.91602 38.8212 8.91602 38.125V11.875C8.91602 11.1788 9.19258 10.5111 9.68486 10.0188C10.1771 9.52656 10.8448 9.25 11.541 9.25H37.791C38.4872 9.25 39.1549 9.52656 39.6472 10.0188C40.1395 10.5111 40.416 11.1788 40.416 11.875ZM11.541 38.125H28.6035V30.25C28.6035 29.9019 28.7418 29.5681 28.9879 29.3219C29.2341 29.0758 29.5679 28.9375 29.916 28.9375H37.791V11.875H11.541V38.125ZM31.2285 31.5625V36.2711L35.9355 31.5625H31.2285Z"
                                fill="#9333EA" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-medium text-gray-500">Doctor's Notes</h3>
                </div>
            </div>
        </div>


        <!-- Navigation Tabs -->
        <div class="mb-6">
            <div class="flex flex-wrap gap-4 items-center" id="dashboardTabs" role="tablist">
                <button class="nav-tab-button tab-analytics <?= $activeTab === 'analytics' ? 'active' : '' ?>"
                    id="analytics-tab" data-tabs-target="#analytics" type="button" role="tab" aria-controls="analytics"
                    aria-selected="<?= $activeTab === 'analytics' ? 'true' : 'false' ?>">
                    Analytics Dashboard
                </button>

                <button
                    class="nav-tab-button tab-account-management <?= $activeTab === 'account-management' ? 'active' : '' ?>"
                    id="account-tab" data-tabs-target="#account-management" type="button" role="tab"
                    aria-controls="account-management"
                    aria-selected="<?= $activeTab === 'account-management' ? 'true' : 'false' ?>">
                    Resident Accounts
                    <span class="count-badge"><?= $stats['resident_users'] ?></span>
                </button>
                <button class="nav-tab-button tab-reports <?= $activeTab === 'reports' ? 'active' : '' ?>"
                    id="reports-tab" data-tabs-target="#reports" type="button" role="tab" aria-controls="reports"
                    aria-selected="<?= $activeTab === 'reports' ? 'true' : 'false' ?>">
                    Generate Reports
                </button>
            </div>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content">
            <!-- Reports Section -->
            <div class="<?= $activeTab === 'reports' ? '' : 'hidden' ?> p-6 bg-white rounded-lg border border-gray-200"
                id="reports" role="tabpanel" aria-labelledby="reports-tab">
                <h2 class="text-2xl font-semibold mb-6 text-blue-700">Comprehensive Health Report</h2>
                <div id="fullReportContent">
                    <div class="flex gap-2 mb-4">
                        <button onclick="generateFullReportModal()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center"><i
                                class="fas fa-sync-alt mr-2"></i> Generate Full Report</button>
                    </div>
                    <!-- Report Generation Logs Display -->
                    <div id="reportLogsSection" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2 flex items-center"><i
                                class="fas fa-history mr-2 text-blue-500"></i> Generate Report Logs</h3>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <button id="sortByDateBtn"
                                class="px-3 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200 transition">Sort by
                                Date</button>
                            <input type="date" id="filterDateInput"
                                class="px-2 py-1 rounded border border-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-200 transition"
                                style="height:36px;" />
                            <button id="clearDateBtn"
                                class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
                                title="Clear date filter">✕</button>
                            <button id="sortByNameBtn"
                                class="px-3 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200 transition">Sort
                                A-Z</button>
                        </div>
                        <div id="reportLogsTableWrap">
                            <div id="reportLogsLoading" class="flex flex-col items-center justify-center py-8">
                                <div class="warmblue-wave-loader mb-3">
                                    <span class="wave-bar"></span>
                                    <span class="wave-bar"></span>
                                    <span class="wave-bar"></span>
                                    <span class="wave-bar"></span>
                                    <span class="wave-bar"></span>
                                </div>
                                <div class="text-warmblue text-base font-medium">Generating Reports... Please wait</div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal for Full Report Display -->
                    <div id="fullReportModal" class="modal-overlay hidden">
                        <div class="modal-container modal-desktop cht-document-modal"
                            style="max-width:1000px;min-width:350px;">
                            <div class="modal-header">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                                        Generated Comprehensive Health Report
                                    </h3>
                                    <button type="button" onclick="closeFullReportModal()"
                                        class="text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
                                <div id="fullReportModalContent"></div>
                            </div>
                            <div class="modal-footer">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <button onclick="printFullReport()"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center"><i
                                            class="fas fa-print mr-2"></i> Print</button>
                                    <button onclick="exportFullReport('pdf')"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium flex items-center"><i
                                            class="fas fa-file-pdf mr-2"></i> Export PDF</button>
                                    <button onclick="exportFullReport('excel')"
                                        class="px-4 py-2 bg-yellow-600 text-gray-900 rounded-lg hover:bg-yellow-500 transition font-medium flex items-center"><i
                                            class="fas fa-file-excel mr-2"></i> Export Excel</button>
                                    <button type="button" onclick="closeFullReportModal()"
                                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 transition font-medium"><i
                                            class="fas fa-times mr-2"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="fullReportResult" class="mt-4"></div>
                    <!-- Report Logs Script will be placed at the end of the file -->
                </div>
                <script>
                    let lastReportHtml = '';
                    function generateFullReportModal() {
                        const modal = document.getElementById('fullReportModal');
                        const modalContent = document.getElementById('fullReportModalContent');
                        // Show loading overlay (match announcement loader)
                        let loader = document.getElementById('reportLoadingOverlay');
                        const loaderHTML = `
                    <div class="cht-loader-bg">
                        <div class="cht-loader-unique">
                            <div class="cht-loader-bounce"></div>
                            <div class="cht-loader-bounce"></div>
                            <div class="cht-loader-bounce"></div>
                        </div>
                        <div class="cht-loader-text">Generating report, please wait...</div>
                        <span class="mt-3 text-base text-slate-500 text-center" style="font-family: Poppins, Arial, Helvetica, sans-serif; font-weight: 400;">Please wait while we process your report.<br>Do not close or refresh this page.</span>
                    </div>
                `;
                        if (!loader) {
                            loader = document.createElement('div');
                            loader.id = 'reportLoadingOverlay';
                            loader.innerHTML = loaderHTML;
                            document.body.appendChild(loader);
                        } else {
                            loader.innerHTML = loaderHTML;
                        }
                        loader.style.display = 'flex';
                        modalContent.innerHTML = '';
                        const startTime = Date.now();
                        fetch('./generate_full_report.php')
                            .then(async res => {
                                if (res.status === 403) {
                                    setTimeout(() => {
                                        loader.style.display = 'none';
                                        modalContent.innerHTML = '<div class="text-red-600">Access denied. Please log in as staff to generate the report.</div>';
                                        modal.classList.remove('hidden');
                                        modal.classList.add('active');
                                    }, Math.max(0, 3000 - (Date.now() - startTime)));
                                    return;
                                }
                                let data;
                                try {
                                    data = await res.json();
                                } catch (e) {
                                    setTimeout(() => {
                                        loader.style.display = 'none';
                                        modalContent.innerHTML = '<div class="text-red-600">Invalid response from report generator.</div>';
                                        modal.classList.remove('hidden');
                                        modal.classList.add('active');
                                    }, Math.max(0, 3000 - (Date.now() - startTime)));
                                    return;
                                }
                                setTimeout(() => {
                                    // Show completion checkmark and message for 1s before hiding loader
                                    loader.innerHTML = `
                                <div class="cht-loader-bg">
                                    <div class="cht-loader-complete">
                                        <svg width="110" height="110" viewBox="0 0 110 110">
                                            <defs>
                                                <radialGradient id="warmBlueBg" cx="50%" cy="50%" r="50%">
                                                    <stop offset="0%" stop-color="#e0e7ff"/>
                                                    <stop offset="100%" stop-color="#60a5fa"/>
                                                </radialGradient>
                                                <linearGradient id="warmBlueStroke" x1="0" y1="0" x2="1" y2="1">
                                                    <stop offset="0%" stop-color="#2563eb"/>
                                                    <stop offset="100%" stop-color="#3b82f6"/>
                                                </linearGradient>
                                            </defs>
                                            <circle cx="55" cy="55" r="50" fill="url(#warmBlueBg)" stroke="url(#warmBlueStroke)" stroke-width="8"/>
                                            <polyline points="35,60 52,78 80,38" fill="none" stroke="#2563eb" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <div class="cht-loader-complete-text" style="color:#2563eb;font-size:1.5rem;font-weight:500;">Generation Completed!</div>
                                    </div>
                                </div>
                            `;
                                    setTimeout(() => {
                                        loader.style.display = 'none';
                                        if (data.success) {
                                            lastReportHtml = renderFullReport(data.data);
                                            modalContent.innerHTML = lastReportHtml;
                                        } else if (data.error) {
                                            modalContent.innerHTML = `<div class='text-red-600'>${data.error}</div>`;
                                        } else {
                                            modalContent.innerHTML = '<div class="text-red-600">Failed to generate report.</div>';
                                        }
                                        modal.classList.remove('hidden');
                                        modal.classList.add('active');
                                    }, 1000);
                                }, Math.max(0, 3000 - (Date.now() - startTime)));
                            })
                            .catch(() => {
                                setTimeout(() => {
                                    loader.style.display = 'none';
                                    modalContent.innerHTML = '<div class="text-red-600">Failed to connect to report generator.</div>';
                                    modal.classList.remove('hidden');
                                    modal.classList.add('active');
                                }, Math.max(0, 3000 - (Date.now() - startTime)));
                            });
                    }
                    // Professional unique loader styles
                    const loaderStyles = document.createElement('style');
                    loaderStyles.innerHTML = `
                #reportLoadingOverlay {
                    position: fixed; z-index: 9999; top: 0; left: 0; width: 100vw; height: 100vh;
                    display: none; align-items: center; justify-content: center; background: rgba(248,250,252,0.85);
                }
                .cht-loader-bg { display: flex; flex-direction: column; align-items: center; }
                .cht-loader-unique {
                    display: flex; gap: 0.7em; margin-bottom: 1.5rem;
                }
                .cht-loader-bounce {
                    width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #60a5fa 40%, #2563eb 100%);
                    animation: cht-bounce 1.1s infinite cubic-bezier(.68,-0.55,.27,1.55);
                }
                .cht-loader-bounce:nth-child(2) { animation-delay: 0.2s; background: linear-gradient(135deg, #93c5fd 40%, #3b82f6 100%); }
                .cht-loader-bounce:nth-child(3) { animation-delay: 0.4s; background: linear-gradient(135deg, #a5b4fc 40%, #6366f1 100%); }
                @keyframes cht-bounce {
                    0%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-30px); }
                }
                .cht-loader-text {
                    color: #22223b; font-size: 1.2rem; font-weight: 500; letter-spacing: 0.01em;
                }
                .cht-loader-complete {
                    display: flex; flex-direction: column; align-items: center; margin-bottom: 1.5rem;
                }
                .cht-loader-complete svg {
                    display: block; margin-bottom: 0.7em;
                }
                .cht-loader-complete-text {
                    color: #2563eb; font-size: 1.5rem; font-weight: 500; letter-spacing: 0.01em;
                    text-align: center;
                }
                `;
                    document.head.appendChild(loaderStyles);
                    function renderFullReport(data) {
                        let html = '';
                        // Add custom CSS for report backgrounds
                        html += `<style>
                                .cht-report-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-bottom: 2rem;
                                }
                                .cht-report-table th, .cht-report-table td {
                                    border: 1.5px solid #cbd5e1;
                                    padding: 0.7em 1em;
                                    text-align: left;
                                    vertical-align: top;
                                }
                                .cht-report-table th {
                                    background: #f1f5f9;
                                    font-weight: 700;
                                    color: #1e293b;
                                    width: 220px;
                                }
                                .cht-report-table tr:not(:last-child) td {
                                    border-bottom: 1.5px solid #cbd5e1;
                                }
                                .cht-report-section-title {
                                    margin-top: 2.2rem;
                                }
                .cht-report-container {
                    max-width: 900px;
                    margin: 0 auto;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    background: #fff;
                    padding: 2.5rem 2.5rem 2rem 2.5rem;
                    border: 2.5px solid #1e293b;
                    border-radius: 1.2rem;
                    box-shadow: 0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10);
                    position: relative;
                }
                .cht-report-container:before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    border-radius: 1.2rem;
                    pointer-events: none;
                    border: 1.5px dashed #64748b;
                    z-index: 1;
                }
                .cht-document-modal {
                    background: #f1f5f9;
                    border: 2.5px solid #1e293b;
                    border-radius: 1.5rem;
                    box-shadow: 0 12px 40px rgba(30,41,59,0.18), 0 2px 12px rgba(30,41,59,0.10);
                    padding: 0.5rem 0.5rem 0.5rem 0.5rem;
                }
                .cht-report-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 0.5rem;
                }
                .cht-report-header img {
                    height: 65px;
                    width: 65px;
                    object-fit: contain;
                    border-radius: 10px;
                    background: #fff;
                    border: 1px solid #cbd5e1;
                }
                .cht-report-govinfo {
                    flex: 1;
                    text-align: center;
                    font-size: 1.05rem;
                    color: #22223b;
                    font-weight: 500;
                    line-height: 1.3;
                }
                .cht-report-titlebar {
                    text-align: center;
                    margin-bottom: 1.2rem;
                }
                .cht-report-titlebar .cht-report-section-title {
                    color: #b91c1c;
                    font-size: 1.15rem;
                    font-weight: 700;
                    letter-spacing: 0.04em;
                    margin-bottom: 0.2rem;
                }
                .cht-section-card {
                    background: #fff;
                    border-radius: 0.7rem;
                    border: 1.5px solid #cbd5e1;
                    padding: 2.2rem 2.2rem;
                    margin-bottom: 2.2rem;
                }
                .cht-section-title {
                    color: #1e293b;
                    font-size: 1.2rem;
                    font-weight: 700;
                    margin-bottom: 1.1rem;
                    letter-spacing: 0.01em;
                    display: flex;
                    align-items: center;
                    gap: 0.7em;
                }
                .cht-section-title i {
                    font-size: 1.1em;
                    color: #64748b;
                }
                .cht-report-list {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                .cht-report-list > li {
                    margin-bottom: 0.8em;
                    font-size: 1.05em;
                }
                .cht-report-list ul {
                    margin-left: 2em;
                    margin-top: 0.3em;
                }
                .cht-label {
                    background: #f1f5f9;
                    color: #22223b;
                    border-radius: 0.4em;
                    padding: 0.13em 0.7em;
                    font-size: 0.98em;
                    font-weight: 600;
                    margin-right: 0.5em;
                }
                .cht-report-list b {
                    color: #1e293b;
                    font-weight: 700;
                }
                @media (max-width: 900px) {
                    .cht-section-card { padding: 1rem 0.4rem; }
                    .cht-report-container { padding: 0.7rem 0; }
                }
                </style>`;
                        html += `<div class='cht-report-container'>`;
                        // Header with two logos and centered info
                        html += `<div class='cht-report-header'>
                    <img src='asssets/images/Luz.jpg' alt='Barangay Luz Logo' style='height:75px;width:75px;object-fit:contain;border-radius:12px;background:#fff;border:2px solid #cbd5e1;box-shadow:0 2px 8px rgba(30,41,59,0.10);margin-right:1.5rem;'>
                    <div class='cht-report-govinfo'>
                        Republic of the Philippines<br>
                        Province of Occidental Mindoro<br>
                        Municipality of [Your City/Town]<br>
                        Barangay Luz Health Center
                    </div>
                    <img src='asssets/images/DOH.webp' alt='City Health Logo'>
                </div>`;
                        html += `<div class='cht-report-titlebar'>
                    <div class='cht-report-section-title'>OFFICE OF THE CITY HEALTH</div>
                    <div style='font-size:1.05rem;color:#22223b;font-weight:600;'>Comprehensive Health Report</div>
                </div>`;
                        html += `<div class='cht-section-card'>`;

                        // Resident Demographics
                        let d = data.resident_demographics;
                        html += `<div class='cht-section-title'><i class='fas fa-users'></i> Resident Demographics</div>`;
                        html += `<table class='cht-report-table'>`;
                        if (typeof d.total_registered !== 'undefined') html += `<tr><th>Total Registered Residents</th><td>${d.total_registered}</td></tr>`;
                        if (typeof d.new_registrations !== 'undefined') html += `<tr><th>New Registrations This Period</th><td>${d.new_registrations}</td></tr>`;
                        if (d.age_distribution) html += `<tr><th>Age Distribution</th><td>
                    <ul style='margin:0;padding-left:1.2em;'>
                        <li>Children (0–12): <b>${d.age_distribution.children}</b></li>
                        <li>Adolescents (13–19): <b>${d.age_distribution.adolescents}</b></li>
                        <li>Adults (20–59): <b>${d.age_distribution.adults}</b></li>
                        <li>Seniors (60+): <b>${d.age_distribution.seniors}</b></li>
                    </ul>
                </td></tr>`;
                        if (d.sex_distribution) html += `<tr><th>Sex Distribution</th><td>
                    <ul style='margin:0;padding-left:1.2em;'>
                        <li>Male: <b>${d.sex_distribution.Male || 0}</b></li>
                        <li>Female: <b>${d.sex_distribution.Female || 0}</b></li>
                    </ul>
                </td></tr>`;
                        html += `</table>`;

                        // Medical Information Summary
                        let m = data.medical_summary;
                        html += `<div class='cht-section-title'><i class='fas fa-notes-medical'></i> Medical Information Summary</div>`;
                        html += `<table class='cht-report-table'>`;
                        if (m.common_conditions) html += `<tr><th>Common Conditions Recorded</th><td>
                    <ul style='margin:0;padding-left:1.2em;'>
                        <li>Hypertension: <b>${m.common_conditions.hypertension}</b></li>
                        <li>Diabetes: <b>${m.common_conditions.diabetes}</b></li>
                        <li>Tuberculosis: <b>${m.common_conditions.tuberculosis}</b></li>
                        <li>Other: <b>${Object.keys(m.common_conditions.other).map(k => k + ': ' + m.common_conditions.other[k]).join(', ')}</b></li>
                    </ul>
                </td></tr>`;
                        if (typeof m.fully_immunized_children !== 'undefined' || m.vaccines_administered) html += `<tr><th>Immunization Coverage</th><td>
                    <ul style='margin:0;padding-left:1.2em;'>
                        ${typeof m.fully_immunized_children !== 'undefined' ? `<li>Fully Immunized Children: <b>${m.fully_immunized_children}</b></li>` : ''}
                        ${m.vaccines_administered ? `<li>Vaccines Administered (by type): <b>${Object.keys(m.vaccines_administered).map(k => k + ': ' + m.vaccines_administered[k]).join(', ')}</b></li>` : ''}
                    </ul>
                </td></tr>`;
                        html += `</table>`;

                        // Consultation Records
                        let c = data.consultation_records;
                        html += `<div class='cht-section-title'><i class='fas fa-stethoscope'></i> Consultation Records</div>`;
                        html += `<table class='cht-report-table'>`;
                        if (typeof c.total_consultations !== 'undefined') html += `<tr><th>Total Consultations</th><td>${c.total_consultations}</td></tr>`;
                        if (c.consultations_by_type) html += `<tr><th>Consultations by Type</th><td>${Object.keys(c.consultations_by_type).map(k => k + ': ' + c.consultations_by_type[k]).join(', ')}</td></tr>`;
                        if (c.top_reasons) html += `<tr><th>Top Reasons for Consultation</th><td>${Object.keys(c.top_reasons).map(k => k + ': ' + c.top_reasons[k]).join(', ')}</td></tr>`;
                        if (c.referrals_made) html += `<tr><th>Referrals Made</th><td>${Object.keys(c.referrals_made).map(k => k + ': ' + c.referrals_made[k]).join(', ')}</td></tr>`;
                        html += `</table>`;

                        // Doctor’s Notes & Case Summaries
                        let dn = data.doctor_notes;
                        html += `<div class='cht-section-title'><i class='fas fa-user-md'></i> Doctor’s Notes & Case Summaries</div>`;
                        html += `<table class='cht-report-table'>`;
                        if (dn.diagnoses) html += `<tr><th>Summary of Diagnoses</th><td>${Object.keys(dn.diagnoses).map(k => k + ': ' + dn.diagnoses[k]).join(', ')}</td></tr>`;
                        if (dn.treatments) html += `<tr><th>Treatments Provided</th><td>${Object.keys(dn.treatments).map(k => k + ': ' + dn.treatments[k]).join(', ')}</td></tr>`;
                        html += `<tr><th>Cases Requiring City Health Department Attention</th><td>${Object.keys(dn.cases_for_city_health).map(k => k + ': ' + dn.cases_for_city_health[k]).join(', ')}</td></tr>`;
                        html += `</table>`;
                        html += `<h3 class='text-lg font-bold mb-2'>Public Health Indicators</h3>`;
                        let ph = data.public_health;
                        html += `<div class='cht-section-title'>Public Health Indicators</div>`;
                        html += `<table class='cht-report-table'>`;
                        html += `<tr><th>Notifiable Diseases Reported</th><td>${Object.keys(ph.notifiable).map(k => k + ': ' + ph.notifiable[k]).join(', ')}</td></tr>`;
                        html += `<tr><th>Health Campaigns Conducted</th><td>${ph.campaigns.map(c => c.title + ': ' + c.details).join('; ')}</td></tr>`;
                        html += `<tr><th>Community Health Trends</th><td>${Object.keys(ph.community_trends).map(k => k + ':' + Object.keys(ph.community_trends[k]).map(m => m + ':' + ph.community_trends[k][m]).join(', ')).join(' | ')}</td></tr>`;
                        html += `</table>`;
                        html += `<h3 class='text-lg font-bold mb-2'>Administrative Data</h3>`;
                        let a = data.admin;
                        html += `<div class='cht-section-title'>Administrative Data</div>`;
                        html += `<table class='cht-report-table'>`;
                        html += `<tr><th>Average Patients per Doctor</th><td>${a.avg_patients_per_doctor}</td></tr>`;
                        html += `<tr><th>Average Patients per Nurse</th><td>${a.avg_patients_per_nurse}</td></tr>`;
                        html += `<tr><th>Medical Supplies Used/Needed</th><td>${a.supplies}</td></tr>`;
                        html += `<tr><th>Challenges Encountered</th><td>${a.challenges}</td></tr>`;
                        html += `</table>`;
                        html += `<h3 class='text-lg font-bold mb-2'>Recommendations</h3>`;
                        html += `<div class='cht-section-title'>Recommendations</div>`;
                        html += `<table class='cht-report-table'>`;
                        html += `<tr><th>Recommendations</th><td>${data.recommendations}</td></tr>`;
                        html += `<tr><th>Prepared by</th><td>${data.prepared_by}</td></tr>`;
                        html += `<tr><th>Date</th><td>${data.date}</td></tr>`;
                        html += `</table>`;
                        html += `</div></div>`;
                        return html;
                    }
                    function printFullReport() {
                        if (!lastReportHtml) { alert('Please generate the report first.'); return; }
                        const win = window.open('', '', 'width=900,height=700');
                        win.document.write('<html><head><title>Print Report</title><style>body{font-family:sans-serif;}h3{margin-top:1.5em;}ul{margin-bottom:1em;}li{margin-bottom:0.3em;}</style></head><body>' + lastReportHtml + '</body></html>');
                        win.document.close();
                        win.focus();
                        win.print();
                    }
                    function exportFullReport(type) {
                        if (type === 'pdf') {
                            window.open('./export_full_report_pdf.php', '_blank');
                        } else if (type === 'excel') {
                            window.open('./export_full_report_excel.php', '_blank');
                        }
                    }
                </script>
            </div>
            <!-- Analytics Dashboard Section -->
            <div class="<?= $activeTab === 'analytics' ? '' : 'hidden' ?>" id="analytics" role="tabpanel"
                aria-labelledby="analytics-tab">
                <div class="flex justify-between items-center mb-8 pb-4 border-b-2 border-gray-300">
                    <h2 class="text-2xl font-semibold mt-5 mb-6">Analytics Dashboard</h2>
                    <button onclick="refreshAnalytics()" class="btn-blue flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh Data
                    </button>
                </div>

                <!-- No overview cards, only charts below -->

                <!-- Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Patient Records Trend (Bar Graph) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="fas fa-user-plus"></i>
                            Patient Records Trend
                        </h3>
                        <div class="chart-wrapper">
                            <canvas id="patientRegistrationChart" height="300"></canvas>
                        </div>
                    </div>
                    <!-- Announcement Response (Summary Table) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="fas fa-bullhorn"></i>
                            Announcement Response
                        </h3>
                        <div class="chart-wrapper" id="announcementResponseWrapper">
                            <div id="announcementResponseLoader" class="flex justify-center items-center h-48">
                                <span class="text-gray-500">Loading announcement responses...</span>
                            </div>
                            <div id="announcementResponseTable" style="display:none;"></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Gender Distribution (Donut Graph) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="fas fa-venus-mars"></i>
                            Gender Distribution
                        </h3>
                        <div class="chart-wrapper">
                            <canvas id="genderDistributionChart" height="300"></canvas>
                        </div>
                    </div>
                    <!-- Age Distribution (Donut Graph) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="fas fa-user-friends"></i>
                            Age Distribution
                        </h3>
                        <div class="chart-wrapper">
                            <canvas id="ageDistributionChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resident Accounts Section -->
            <div class="<?= $activeTab === 'account-management' ? '' : 'hidden' ?>" id="account-management"
                role="tabpanel" aria-labelledby="account-tab">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-700">Resident Accounts</h2>
                    <!-- <div class="text-sm text-gray-600">
                        Total: <span class="font-semibold"><?= $totalResidentUsers ?></span> residents
                    </div> -->
                    <?php if ($totalResidentUsers > 5 && (!isset($_GET['view_all_residents']) || $_GET['view_all_residents'] != '1')): ?>
                        <!-- <a href="?tab=account-management&view_all_residents=1"
                            class="ml-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                            <i class="fas fa-list mr-1"></i> View All Resident Accounts
                        </a> -->
                    <?php elseif (isset($_GET['view_all_residents']) && $_GET['view_all_residents'] == '1'): ?>
                        <!-- <a href="?tab=account-management"
                            class="ml-4 px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition font-medium text-sm">
                            <i class="fas fa-eye-slash mr-1"></i> Show Less
                        </a> -->
                    <?php endif; ?>
                </div>

                <!-- Search and Filter Section -->
                <div class="mb-6 rounded-lg">
                    <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="tab" value="account-management">
                        <div class="flex flex-col md:flex-row justify-between w-full border-b-2 pb-6">
                            <!-- RIGHT CONTENT -->
                            <div class="flex flex-col md:flex-row items-center gap-4">
                                <!-- Search Bar -->
                                <div class="relative min-w-[250px] w-full gap">
                                    <div>
                                        <i
                                            class="fas fa-search absolute text-xl px-6 py-4 left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                    </div>
                                    <div>
                                        <input type="text" id="search" name="search"
                                            value="<?= htmlspecialchars($searchQuery) ?>"
                                            placeholder="Search record by name..."
                                            class="w-full pl-10 py-3 px-16 text-base font-normal rounded-lg focus:outline-none border border-[#3C96E1] focus:ring-2 focus:ring-blue-400 focus:border-blue-500">
                                    </div>
                                </div>
                                <!-- Search Button -->
                                <div>
                                    <button class="bg-[#3C96E1] text-white py-3 px-10 text-base rounded-md">
                                        Search
                                    </button>
                                </div>
                            </div>

                            <!-- LEFT CONTENT -->
                            <div class="flex flex-col md:flex-row items-center gap-4">
                                <!-- Sort By Date -->
                                <div class="w-48">
                                    <!-- <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-sort mr-1"></i> Sort by Date
                                </label> -->
                                    <select
                                        name="sort"
                                        class="custom-select-filter">
                                        <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                                    </select>
                                    <style>
                                    .custom-select-filter {
                                        width: 100%;
                                        font-size: 1rem;
                                        font-weight: 500;
                                        color: #22223b;
                                        background: #fff;
                                        border: 1px solid #3C96E1;
                                        border-radius: 6px;
                                        height: 48px;
                                        padding: 0 2.5rem 0 1.5rem;
                                        appearance: none;
                                        -webkit-appearance: none;
                                        -moz-appearance: none;
                                        box-shadow: 0 2px 8px 0 rgba(60,150,225,0.08);
                                        position: relative;
                                        transition: border 0.2s, box-shadow 0.2s;
                                        display: flex;
                                        align-items: center;
                                    }
                                    .custom-select-filter:focus {
                                        outline: none;
                                        border: 1.5px solid #3C96E1;
                                        box-shadow: 0 0 0 2px #60a5fa33;
                                    }
                                    .custom-select-filter::-ms-expand {
                                        display: none;
                                    }
                                    /* Custom arrow */
                                    .custom-select-filter {
                                        background-image: url('data:image/svg+xml;utf8,<svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5" stroke="%2322233b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
                                        background-repeat: no-repeat;
                                        background-position: right 1.2rem center;
                                        background-size: 1.5rem 1.5rem;
                                    }
                                    </style>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="flex flex-col md:flex-row px-6 py-3 bg-[#3C96E1] text-white rounded-lg text-base hover:bg-blue-700 transition font-medium">
                                        <svg class="h-6 w-6 mr-2" viewBox="0 0 27 27" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M24.3212 5.22389C24.1913 4.92385 23.9761 4.6686 23.7023 4.48989C23.4286 4.31118 23.1083 4.2169 22.7813 4.21878H4.21884C3.89224 4.21942 3.57284 4.31483 3.29939 4.49342C3.02595 4.67202 2.8102 4.92613 2.67833 5.22493C2.54646 5.52373 2.50414 5.85438 2.55649 6.17676C2.60884 6.49914 2.75362 6.7994 2.97326 7.04112L2.9817 7.05061L10.1251 14.6781V22.7813C10.125 23.0867 10.2078 23.3864 10.3647 23.6484C10.5216 23.9105 10.7466 24.1251 11.0159 24.2692C11.2851 24.4134 11.5884 24.4819 11.8935 24.4672C12.1985 24.4526 12.4939 24.3554 12.7481 24.1861L16.1231 21.9354C16.3545 21.7813 16.5442 21.5724 16.6754 21.3273C16.8065 21.0823 16.8752 20.8086 16.8751 20.5306V14.6781L24.0195 7.05061L24.028 7.04112C24.2499 6.80051 24.3961 6.49984 24.4483 6.17667C24.5004 5.85349 24.4562 5.52211 24.3212 5.22389ZM15.4175 13.7721C15.2716 13.9269 15.1894 14.1311 15.1876 14.3438V20.5306L11.8126 22.7813V14.3438C11.8127 14.1295 11.7312 13.9233 11.5848 13.7669L4.21884 5.90628H22.7813L15.4175 13.7721Z"
                                                fill="white" />
                                        </svg>
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (empty($residentUsers)): ?>
                    <div class="bg-blue-50 p-6 rounded-lg text-center border border-blue-200">
                        <i class="fas fa-users text-blue-400 text-4xl mb-3"></i>
                        <p class="text-gray-600 text-lg">
                            <?php if (!empty($searchQuery)): ?>
                                No residents found matching your search.
                            <?php else: ?>
                                No resident accounts found.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Patient ID</th>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Full Name</th>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Username</th>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Email</th>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Registered Date</th>
                                    <th class="py-3 px-4 text-left text-lg font-semibold text-gray-600">
                                        Status</th>
                                    <th class="py-3 px-4 text-center text-lg font-semibold text-gray-600">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($residentUsers as $user): ?>
                                    <style>
                                        .bg-status-green {
                                            background-color: rgba(22, 163, 74, 0.3);
                                        }

                                        .text-status-green {
                                            color: #16A34A;
                                        }
                                    </style>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span class="font-mono text-base font-semibold text-blue-600">
                                                <?= htmlspecialchars($user['unique_number'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <div class="font-normal text-gray-600"><?= htmlspecialchars($user['full_name']) ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span
                                                class="font-normal text-gray-600"><?= htmlspecialchars($user['username']) ?></span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span
                                                class="font-normal text-gray-600"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span
                                                class="font-normal text-gray-600"><?= date('M d, Y', strtotime($user['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span
                                                class="px-6 py-2 inline-flex text-base leading-5 font-medium items-center rounded-full bg-status-green text-status-green">
                                                <svg class="w-6 h-6 mr-1" viewBox="0 0 18 18" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12.2105 6.91453C12.2628 6.96677 12.3043 7.02881 12.3326 7.0971C12.3609 7.16538 12.3754 7.23858 12.3754 7.3125C12.3754 7.38642 12.3609 7.45962 12.3326 7.5279C12.3043 7.59619 12.2628 7.65823 12.2105 7.71047L8.27297 11.648C8.22073 11.7003 8.15869 11.7418 8.09041 11.7701C8.02212 11.7984 7.94892 11.8129 7.875 11.8129C7.80108 11.8129 7.72789 11.7984 7.6596 11.7701C7.59131 11.7418 7.52928 11.7003 7.47703 11.648L5.78953 9.96047C5.68399 9.85492 5.62469 9.71177 5.62469 9.5625C5.62469 9.41323 5.68399 9.27008 5.78953 9.16453C5.89508 9.05898 6.03824 8.99969 6.1875 8.99969C6.33677 8.99969 6.47992 9.05898 6.58547 9.16453L7.875 10.4548L11.4145 6.91453C11.4668 6.86223 11.5288 6.82074 11.5971 6.79244C11.6654 6.76413 11.7386 6.74956 11.8125 6.74956C11.8864 6.74956 11.9596 6.76413 12.0279 6.79244C12.0962 6.82074 12.1582 6.86223 12.2105 6.91453ZM16.3125 9C16.3125 10.4463 15.8836 11.8601 15.0801 13.0626C14.2766 14.2651 13.1346 15.2024 11.7984 15.7559C10.4622 16.3093 8.99189 16.4541 7.57341 16.172C6.15492 15.8898 4.85196 15.1934 3.82928 14.1707C2.80661 13.148 2.11017 11.8451 1.82801 10.4266C1.54586 9.00811 1.69067 7.53781 2.24413 6.20163C2.7976 4.86544 3.73486 3.72339 4.9374 2.91988C6.13993 2.11637 7.55373 1.6875 9 1.6875C10.9388 1.68955 12.7975 2.46063 14.1685 3.83154C15.5394 5.20246 16.3105 7.06123 16.3125 9ZM15.1875 9C15.1875 7.77623 14.8246 6.57994 14.1447 5.56241C13.4648 4.54488 12.4985 3.75181 11.3679 3.2835C10.2372 2.81518 8.99314 2.69264 7.79288 2.93139C6.59262 3.17014 5.49012 3.75944 4.62478 4.62478C3.75944 5.49011 3.17014 6.59262 2.93139 7.79288C2.69265 8.99314 2.81518 10.2372 3.2835 11.3679C3.75182 12.4985 4.54488 13.4648 5.56241 14.1447C6.57994 14.8246 7.77623 15.1875 9 15.1875C10.6405 15.1856 12.2132 14.5331 13.3732 13.3732C14.5331 12.2132 15.1856 10.6405 15.1875 9Z"
                                                        fill="#16A34A" />
                                                </svg>
                                                Active
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200 text-center">
                                            <button
                                                onclick="openResidentDetailsModal(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                class="px-6 py-4 inline-flex bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-base">
                                                <svg class="w-6 h-6 mr-1" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M23.1853 11.6962C23.1525 11.6222 22.3584 9.86062 20.5931 8.09531C18.2409 5.74312 15.27 4.5 12 4.5C8.72999 4.5 5.75905 5.74312 3.40687 8.09531C1.64155 9.86062 0.843741 11.625 0.814679 11.6962C0.772035 11.7922 0.75 11.896 0.75 12.0009C0.75 12.1059 0.772035 12.2097 0.814679 12.3056C0.847491 12.3797 1.64155 14.1403 3.40687 15.9056C5.75905 18.2569 8.72999 19.5 12 19.5C15.27 19.5 18.2409 18.2569 20.5931 15.9056C22.3584 14.1403 23.1525 12.3797 23.1853 12.3056C23.2279 12.2097 23.25 12.1059 23.25 12.0009C23.25 11.896 23.2279 11.7922 23.1853 11.6962ZM12 18C9.11437 18 6.59343 16.9509 4.50655 14.8828C3.65028 14.0313 2.92179 13.0603 2.34374 12C2.92164 10.9396 3.65014 9.9686 4.50655 9.11719C6.59343 7.04906 9.11437 6 12 6C14.8856 6 17.4066 7.04906 19.4934 9.11719C20.3514 9.9684 21.0815 10.9394 21.6609 12C20.985 13.2619 18.0403 18 12 18ZM12 7.5C11.11 7.5 10.2399 7.76392 9.49993 8.25839C8.7599 8.75285 8.18313 9.45566 7.84253 10.2779C7.50194 11.1002 7.41282 12.005 7.58646 12.8779C7.76009 13.7508 8.18867 14.5526 8.81801 15.182C9.44735 15.8113 10.2492 16.2399 11.1221 16.4135C11.995 16.5872 12.8998 16.4981 13.7221 16.1575C14.5443 15.8169 15.2471 15.2401 15.7416 14.5001C16.2361 13.76 16.5 12.89 16.5 12C16.4988 10.8069 16.0242 9.66303 15.1806 8.81939C14.337 7.97575 13.1931 7.50124 12 7.5ZM12 15C11.4066 15 10.8266 14.8241 10.3333 14.4944C9.83993 14.1648 9.45542 13.6962 9.22835 13.1481C9.00129 12.5999 8.94188 11.9967 9.05764 11.4147C9.17339 10.8328 9.45911 10.2982 9.87867 9.87868C10.2982 9.45912 10.8328 9.1734 11.4147 9.05764C11.9967 8.94189 12.5999 9.0013 13.148 9.22836C13.6962 9.45542 14.1648 9.83994 14.4944 10.3333C14.824 10.8266 15 11.4067 15 12C15 12.7956 14.6839 13.5587 14.1213 14.1213C13.5587 14.6839 12.7956 15 12 15Z"
                                                        fill="white" />
                                                </svg>
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination mt-6">
                            <!-- Previous Button -->
                            <?php if ($currentPage > 1): ?>
                                <?php
                                $prevUrl = "?tab=account-management&user_page=" . ($currentPage - 1);
                                if (!empty($searchQuery))
                                    $prevUrl .= "&search=" . urlencode($searchQuery);
                                if (!empty($sortOrder))
                                    $prevUrl .= "&sort=" . urlencode($sortOrder);
                                ?>
                                <a href="<?= $prevUrl ?>" class="pagination-button">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-button disabled">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php
                                $pageUrl = "?tab=account-management&user_page=" . $i;
                                if (!empty($searchQuery))
                                    $pageUrl .= "&search=" . urlencode($searchQuery);
                                if (!empty($sortOrder))
                                    $pageUrl .= "&sort=" . urlencode($sortOrder);
                                ?>
                                <?php if ($i == $currentPage): ?>
                                    <span class="pagination-button active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= $pageUrl ?>" class="pagination-button"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($currentPage < $totalPages): ?>
                                <?php
                                $nextUrl = "?tab=account-management&user_page=" . ($currentPage + 1);
                                if (!empty($searchQuery))
                                    $nextUrl .= "&search=" . urlencode($searchQuery);
                                if (!empty($sortOrder))
                                    $nextUrl .= "&sort=" . urlencode($sortOrder);
                                ?>
                                <a href="<?= $nextUrl ?>" class="pagination-button">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-button disabled">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enhanced Action Success/Error Modals -->
    <div id="successModal" class="modal-overlay hidden">
        <div class="modal-container action-modal action-modal-success">
            <div class="modal-body">
                <div class="action-modal-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="action-modal-title" id="successModalTitle">Success</h3>
                <div class="action-modal-message" id="successModalMessage"></div>
            </div>
            <div class="modal-footer">
                <div class="flex justify-center">
                    <button type="button" onclick="closeSuccessModal()"
                        class="px-8 py-3 bg-green-600 text-white rounded-full hover:bg-green-700 transition font-medium">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="errorModal" class="modal-overlay hidden">
        <div class="modal-container action-modal action-modal-error">
            <div class="modal-body">
                <div class="action-modal-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 class="action-modal-title" id="errorModalTitle">Error</h3>
                <div class="action-modal-message" id="errorModalMessage"></div>
            </div>
            <div class="modal-footer">
                <div class="flex justify-center">
                    <button type="button" onclick="closeErrorModal()"
                        class="px-8 py-3 bg-red-600 text-white rounded-full hover:bg-red-700 transition font-medium">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resident Details Modal (Read-Only) -->
    <div id="residentDetailsModal" class="modal-overlay hidden">
        <div class="modal-container modal-desktop">
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                        Resident Account Details
                    </h3>
                    <button type="button" onclick="closeResidentDetailsModal()"
                        class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <!-- Account Status Banner -->
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                            <div>
                                <h4 class="text-lg font-semibold text-green-800">Active Resident Account</h4>
                                <p class="text-sm text-green-700">Patient ID: <span class="font-mono font-bold"
                                        id="residentPatientId">N/A</span></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">Member Since</div>
                            <div class="text-sm font-semibold text-gray-700" id="residentMemberSince">N/A</div>
                        </div>
                    </div>
                </div>

                <div class="horizontal-user-details">
                    <!-- Personal Information -->
                    <div class="detail-section">
                        <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                            <i class="fas fa-user mr-2"></i> Personal Information
                        </h4>
                        <div class="user-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Full Name:</span>
                                <span class="detail-value font-semibold" id="residentFullName">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Username:</span>
                                <span class="detail-value" id="residentUsername">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value" id="residentEmail">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Contact Number:</span>
                                <span class="detail-value" id="residentContact">N/A</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="flex justify-end">
                    <button type="button" onclick="closeResidentDetailsModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 transition font-medium">
                        <i class="fas fa-times mr-2"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal-overlay hidden">
        <div class="modal-container modal-desktop">
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-900">Patient Registration Details</h3>
                    <button type="button" onclick="closeUserDetailsModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="horizontal-user-details">
                    <!-- Personal Information -->
                    <div class="detail-section">
                        <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                            <i class="fas fa-user mr-2"></i> Personal Information
                        </h4>
                        <div class="user-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Full Name:</span>
                                <span class="detail-value" id="userFullName">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Username:</span>
                                <span class="detail-value" id="userUsername">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value" id="userEmail">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Contact Number:</span>
                                <span class="detail-value" id="userContact">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Gender:</span>
                                <span class="detail-value" id="userGender">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Age:</span>
                                <span class="detail-value" id="userAge">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Civil Status:</span>
                                <span class="detail-value" id="userCivilStatus">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Occupation:</span>
                                <span class="detail-value" id="userOccupation">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date of Birth:</span>
                                <span class="detail-value" id="userDateOfBirth">N/A</span>
                            </div>
                        </div>
                    </div>

                    <!-- Address & Account Information -->
                    <div class="detail-section">
                        <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                            <i class="fas fa-map-marker-alt mr-2"></i> Address Information
                        </h4>
                        <div class="user-details-grid">
                            <div class="detail-item" style="grid-column: span 2;">
                                <span class="detail-label">Complete Address:</span>
                                <span class="detail-value" id="userAddress">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sitio:</span>
                                <span class="detail-value" id="userSitio">N/A</span>
                            </div>
                        </div>

                        <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4 mt-6">
                            <i class="fas fa-user-circle mr-2"></i> Account Information
                        </h4>
                        <div class="user-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Account Status:</span>
                                <span class="detail-value" id="userStatus">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Approved:</span>
                                <span class="detail-value" id="userApproved">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">User Role:</span>
                                <span class="detail-value" id="userRole">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Verification Method:</span>
                                <span class="detail-value" id="userVerificationMethod">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ID Verified:</span>
                                <span class="detail-value" id="userIdVerified">N/A</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Verification Consent:</span>
                                <span class="detail-value" id="userVerificationConsent">N/A</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ID Verification Section -->
                <div class="detail-section mt-6">
                    <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                        <i class="fas fa-id-card mr-2"></i> ID Verification
                    </h4>
                    <div class="user-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">ID Type:</span>
                            <span class="detail-value" id="userIdType">N/A</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">ID Status:</span>
                            <span class="detail-value" id="userIdValidationStatus">N/A</span>
                        </div>
                    </div>

                    <!-- ID Image -->
                    <div class="mt-6">
                        <h5 class="font-semibold text-gray-700 mb-3">ID Document</h5>

                        <div id="idImageSection" class="hidden">
                            <div class="id-preview-container">
                                <img id="userIdImage" src="" alt="ID Image" class="w-full h-auto">
                            </div>

                            <div class="flex gap-3 mt-3">
                                <a id="userIdImageLink" href="#" target="_blank" class="btn-blue">
                                    <i class="fas fa-external-link-alt mr-2"></i> View Original
                                </a>
                                <button onclick="openImageModal()" class="btn-blue">
                                    <i class="fas fa-search mr-2"></i> Zoom Preview
                                </button>
                            </div>
                        </div>

                        <div id="noIdImageSection" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                <span class="text-yellow-700">No ID image uploaded</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline & Notes -->
                <div class="detail-section mt-6">
                    <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                        <i class="fas fa-history mr-2"></i> Registration Timeline
                    </h4>
                    <div class="user-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Registered Date:</span>
                            <span class="detail-value" id="userRegisteredDate">N/A</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Updated:</span>
                            <span class="detail-value" id="userUpdatedDate">N/A</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Verified At:</span>
                            <span class="detail-value" id="userVerifiedAt">N/A</span>
                        </div>
                    </div>

                    <div id="verificationNotesSection" class="hidden mt-6">
                        <h4 class="text-lg font-semibold text-blue-700 border-b pb-3 mb-4">
                            <i class="fas fa-sticky-note mr-2"></i> Verification Notes
                        </h4>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-gray-700" id="userVerificationNotes">No verification notes available.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="flex justify-end space-x-3">
                    <button onclick="openApproveConfirmationModal()" class="btn-success-blue">
                        <i class="fas fa-check mr-2"></i> Approve Registration
                    </button>
                    <button onclick="openDeclineModalFromDetails()" class="btn-danger-blue">
                        <i class="fas fa-times mr-2"></i> Decline Registration
                    </button>
                    <button type="button" onclick="closeUserDetailsModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 transition font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <div id="approveConfirmationModal" class="modal-overlay hidden">
        <div class="modal-container max-w-md">
            <div class="modal-body">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Confirm Resident Account Approval</h3>
                <p class="text-gray-600 text-center mb-4">
                    Are you sure you want to approve this user account? This action will generate a unique patient
                    number and grant full system access.
                </p>
                <div class="bg-blue-50 p-3 rounded-lg mb-6">
                    <p class="text-sm text-blue-700 font-medium text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        An approval email with the unique patient number will be sent to the user.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeApproveConfirmationModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 transition font-medium">
                        Cancel
                    </button>
                    <form method="POST" action="" class="inline" id="finalApproveForm">
                        <input type="hidden" name="user_id" id="finalApproveUserId">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" name="approve_user" class="btn-success-blue">
                            <i class="fas fa-check mr-1"></i> Confirm Approval
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal-overlay hidden">
        <div class="modal-container max-w-2xl">
            <div class="modal-header">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 text-center mb-2">Decline User Account</h3>
                <p class="text-gray-600 text-center">Please provide a reason for declining this user registration.</p>
            </div>

            <div class="modal-body">
                <form id="declineForm" method="POST" action="">
                    <input type="hidden" name="user_id" id="declineUserId">
                    <input type="hidden" name="action" value="decline">

                    <div class="mb-6">
                        <label for="decline_reason" class="block text-gray-700 text-sm font-semibold mb-3">Reason for
                            Declination *</label>
                        <textarea id="decline_reason" name="decline_reason" rows="6"
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Please provide a detailed reason for declining this user account. This will be included in the notification email sent to the user..."
                            required></textarea>
                        <p class="text-xs text-gray-500 mt-2">This reason will be sent to the user via email.</p>
                    </div>

                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-semibold text-red-800">Important Notice</h4>
                                <p class="text-sm text-red-700 mt-1">
                                    Declining this account will prevent the user from accessing the system. They will
                                    receive an email notification with the reason provided above.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeclineModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 transition font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Cancel
                    </button>
                    <button type="submit" form="declineForm" name="approve_user" class="btn-danger-blue">
                        <i class="fas fa-ban mr-2"></i> Confirm Decline
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div id="imageModal" class="modal-overlay hidden">
        <div class="modal-container max-w-4xl">
            <div class="modal-header">
                <h3 class="text-lg font-semibold">ID Document Preview</h3>
                <button type="button" onclick="closeImageModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex justify-center">
                    <img id="zoomedUserIdImage" src="" alt="Zoomed ID Image" class="max-w-full h-auto rounded-lg">
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-center">
                    <a id="zoomedUserIdImageLink" href="#" target="_blank" class="btn-blue">
                        <i class="fas fa-external-link-alt mr-2"></i> Open in New Tab
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variable to store current user ID
        let currentUserDetailsId = null;

        // Enhanced Modal Management Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Enhanced Success/Error Modal Functions
        function showSuccessModal(message) {
            document.getElementById('successModalTitle').textContent = 'Success';
            document.getElementById('successModalMessage').innerHTML = message;
            openModal('successModal');

            // Auto-close after 5 seconds
            setTimeout(() => {
                closeSuccessModal();
            }, 5000);
        }

        function closeSuccessModal() {
            closeModal('successModal');
        }

        function showErrorModal(message) {
            document.getElementById('errorModalTitle').textContent = 'Error';
            document.getElementById('errorModalMessage').textContent = message;
            openModal('errorModal');

            // Auto-close after 5 seconds
            setTimeout(() => {
                closeErrorModal();
            }, 5000);
        }

        function closeErrorModal() {
            closeModal('errorModal');
        }

        // User Details Modal functions
        function openUserDetailsModal(user) {
            console.log('User data for modal:', user);

            currentUserDetailsId = user.id;

            // Set user data in the modal
            document.getElementById('userFullName').textContent = user.full_name || 'N/A';
            document.getElementById('userUsername').textContent = user.username || 'N/A';
            document.getElementById('userEmail').textContent = user.email || 'N/A';
            document.getElementById('userGender').textContent = user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : 'N/A';
            document.getElementById('userAge').textContent = user.age || 'N/A';
            document.getElementById('userContact').textContent = user.contact || 'N/A';
            document.getElementById('userCivilStatus').textContent = user.civil_status || 'N/A';
            document.getElementById('userOccupation').textContent = user.occupation || 'N/A';
            document.getElementById('userDateOfBirth').textContent = user.date_of_birth ? new Date(user.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

            document.getElementById('userAddress').textContent = user.address || 'N/A';
            document.getElementById('userSitio').textContent = user.sitio || 'N/A';

            const verificationMethod = user.verification_method ?
                user.verification_method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
            document.getElementById('userVerificationMethod').textContent = verificationMethod;

            const idVerifiedElement = document.getElementById('userIdVerified');
            if (user.id_verified === 1 || user.id_verified === true) {
                idVerifiedElement.textContent = 'Yes';
                idVerifiedElement.className = 'detail-value text-green-600';
            } else {
                idVerifiedElement.textContent = 'No';
                idVerifiedElement.className = 'detail-value text-red-600';
            }

            const consentElement = document.getElementById('userVerificationConsent');
            if (user.verification_consent === 1 || user.verification_consent === true) {
                consentElement.textContent = 'Yes';
                consentElement.className = 'detail-value text-green-600';
            } else {
                consentElement.textContent = 'No';
                consentElement.className = 'detail-value text-red-600';
            }

            // Handle ID image
            const idImageSection = document.getElementById('idImageSection');
            const noIdImageSection = document.getElementById('noIdImageSection');
            const idImage = document.getElementById('userIdImage');
            const idImageLink = document.getElementById('userIdImageLink');
            const zoomedIdImage = document.getElementById('zoomedUserIdImage');
            const zoomedIdImageLink = document.getElementById('zoomedUserIdImageLink');

            const imagePath = user.display_image_path || user.id_image_path;

            if (imagePath && imagePath.trim() !== '') {
                console.log('Displaying ID image from path:', imagePath);

                const testImage = new Image();
                testImage.onload = function () {
                    idImage.src = imagePath;
                    zoomedIdImage.src = imagePath;
                    idImageLink.href = imagePath;
                    zoomedIdImageLink.href = imagePath;

                    idImageSection.classList.remove('hidden');
                    noIdImageSection.classList.add('hidden');
                };

                testImage.onerror = function () {
                    console.error('Failed to load ID image:', imagePath);
                    idImageSection.classList.add('hidden');
                    noIdImageSection.classList.remove('hidden');
                };

                testImage.src = imagePath;

            } else {
                console.log('No ID image available');
                idImageSection.classList.add('hidden');
                noIdImageSection.classList.remove('hidden');
            }

            // Display ID type and validation status
            const idTypeElement = document.getElementById('userIdType');
            const idValidationStatus = document.getElementById('userIdValidationStatus');

            if (user.id_type) {
                idTypeElement.textContent = user.id_type;

                const isValidId = user.is_valid_id;
                if (isValidId) {
                    idValidationStatus.textContent = 'Valid ID';
                    idValidationStatus.className = 'detail-value text-green-600';
                } else {
                    idValidationStatus.textContent = 'Invalid ID Type';
                    idValidationStatus.className = 'detail-value text-red-600';
                }
            } else {
                idTypeElement.textContent = 'No ID Uploaded';
                idValidationStatus.textContent = 'No ID Found';
                idValidationStatus.className = 'detail-value text-gray-600';
            }

            // Handle verification notes
            const verificationNotesSection = document.getElementById('verificationNotesSection');
            const verificationNotes = document.getElementById('userVerificationNotes');
            if (user.verification_notes && user.verification_notes.trim() !== '') {
                verificationNotesSection.classList.remove('hidden');
                verificationNotes.textContent = user.verification_notes;
            } else {
                verificationNotesSection.classList.add('hidden');
            }

            // Registration details
            document.getElementById('userRegisteredDate').textContent = user.created_at ?
                new Date(user.created_at).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

            document.getElementById('userUpdatedDate').textContent = user.updated_at ?
                new Date(user.updated_at).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

            document.getElementById('userVerifiedAt').textContent = user.verified_at ?
                new Date(user.verified_at).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'Not verified';

            // Status information
            const statusElement = document.getElementById('userStatus');
            statusElement.textContent = user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : 'Pending';
            statusElement.className = 'detail-value ' +
                (user.status === 'approved' ? 'text-green-600' :
                    user.status === 'pending' ? 'text-yellow-600' :
                        user.status === 'declined' ? 'text-red-600' : 'text-yellow-600');

            const approvedElement = document.getElementById('userApproved');
            if (user.approved === 1 || user.approved === true) {
                approvedElement.textContent = 'Yes';
                approvedElement.className = 'detail-value text-green-600';
            } else {
                approvedElement.textContent = 'No';
                approvedElement.className = 'detail-value text-red-600';
            }

            document.getElementById('userRole').textContent = user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Patient';

            openModal('userDetailsModal');
        }

        function closeUserDetailsModal() {
            closeModal('userDetailsModal');
        }

        // Resident Details Modal Functions (Read-Only)
        function openResidentDetailsModal(user) {
            console.log('Resident data for modal:', user);

            // Set patient ID and member since (Account Status)
            document.getElementById('residentPatientId').textContent = user.unique_number || 'N/A';
            document.getElementById('residentMemberSince').textContent = user.created_at ?
                new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';

            // Set personal information
            document.getElementById('residentFullName').textContent = user.full_name || 'N/A';
            document.getElementById('residentUsername').textContent = user.username || 'N/A';
            document.getElementById('residentEmail').textContent = user.email || 'N/A';
            document.getElementById('residentContact').textContent = user.contact || 'N/A';

            openModal('residentDetailsModal');
        }

        function closeResidentDetailsModal() {
            closeModal('residentDetailsModal');
        }

        // Approve Confirmation Modal functions
        function openApproveConfirmationModal() {
            document.getElementById('finalApproveUserId').value = currentUserDetailsId;
            openModal('approveConfirmationModal');
        }

        function closeApproveConfirmationModal() {
            closeModal('approveConfirmationModal');
        }

        // Decline Modal functions
        function openDeclineModalFromDetails() {
            closeUserDetailsModal();
            setTimeout(() => {
                openDeclineModal(currentUserDetailsId);
            }, 100);
        }

        function openDeclineModal(userId) {
            document.getElementById('declineUserId').value = userId;
            document.getElementById('decline_reason').value = '';
            openModal('declineModal');
        }

        function closeDeclineModal() {
            closeModal('declineModal');
        }

        // Image Modal functions
        function openImageModal() {
            openModal('imageModal');
        }

        function closeImageModal() {
            closeModal('imageModal');
        }

        // Tab functionality
        function switchTab(tabId) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            document.querySelectorAll('.tab-content > div').forEach(tab => {
                tab.classList.add('hidden');
            });

            document.getElementById(tabId).classList.remove('hidden');

            document.querySelectorAll('#dashboardTabs button').forEach(tabBtn => {
                tabBtn.classList.remove('active');
            });

            const activeTabBtn = document.querySelector(`#dashboardTabs button[data-tabs-target="#${tabId}"]`);
            activeTabBtn.classList.add('active');

            // Initialize charts when switching to analytics tab
            if (tabId === 'analytics') {
                // Small delay to ensure DOM is ready
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            }
        }

        // Initialize tabs and charts
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'analytics';

            switchTab(activeTab);

            document.querySelectorAll('#dashboardTabs button').forEach(tabBtn => {
                tabBtn.addEventListener('click', function () {
                    const targetTab = this.getAttribute('data-tabs-target').replace('#', '');
                    switchTab(targetTab);
                });
            });

            // ALWAYS initialize charts if on analytics tab
            if (activeTab === 'analytics') {
                // Small delay to ensure all DOM elements are ready
                setTimeout(() => {
                    initializeCharts();
                }, 200);
            }

            // Show success/error messages
            <?php if ($success): ?>
                showSuccessModal('<?= addslashes($success) ?>');
            <?php endif; ?>

            <?php if ($error): ?>
                showErrorModal('<?= addslashes($error) ?>');
            <?php endif; ?>
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = ['userDetailsModal', 'approveConfirmationModal', 'declineModal', 'imageModal',
                'successModal', 'errorModal', 'residentDetailsModal'];

            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    closeModal(modalId);
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal-overlay.active');
                openModals.forEach(modal => {
                    const modalId = modal.id;
                    closeModal(modalId);
                });
            }
        });

        // Refresh analytics function
        function refreshAnalytics() {
            const loader = document.getElementById('ajaxLoader');
            loader.style.display = 'flex';

            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Loader animation styles for analytics loader (reuse from Reports tab)
        if (!document.getElementById('chtLoaderUniqueStyles')) {
            const style = document.createElement('style');
            style.id = 'chtLoaderUniqueStyles';
            style.innerHTML = `
        .cht-analytics-loader-bg {
            position: fixed; z-index: 9999; top: 0; left: 0; width: 100vw; height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: rgba(248,250,252,0.85);
            backdrop-filter: blur(6px);
        }
        .cht-loader-unique {
            display: flex; flex-direction: row; gap: 0.7em; margin-bottom: 1.5rem;
        }
        .cht-loader-vertical {
            /* No longer used for horizontal layout */
        }
        .cht-loader-bounce {
            width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #2563eb 60%, #38bdf8 100%);
            animation: cht-bounce 1.1s infinite cubic-bezier(.68,-0.55,.27,1.55);
        }
        .cht-loader-bounce:nth-child(2) { animation-delay: 0.2s; background: linear-gradient(135deg, #22c55e 60%, #bef264 100%); }
        .cht-loader-bounce:nth-child(3) { animation-delay: 0.4s; background: linear-gradient(135deg, #f59e42 60%, #fbbf24 100%); }
        @keyframes cht-bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-30px); }
        }
        .cht-loader-text {
            color: #22223b; font-size: 1.2rem; font-weight: 500; letter-spacing: 0.01em;
            text-align: center;
        }
    `;
            document.head.appendChild(style);
        }

        // Chart initialization
        function initializeCharts() {
            // Check if chart elements exist
            const patientRegistrationCanvas = document.getElementById('patientRegistrationChart');
            const healthIssuesCanvas = document.getElementById('healthIssuesChart');
            const genderDistributionCanvas = document.getElementById('genderDistributionChart');
            const ageDistributionCanvas = document.getElementById('ageDistributionChart');
            // Destroy existing charts if they exist
            Chart.getChart(patientRegistrationCanvas)?.destroy();
            Chart.getChart(healthIssuesCanvas)?.destroy();
            Chart.getChart(genderDistributionCanvas)?.destroy();
            Chart.getChart(ageDistributionCanvas)?.destroy();
            // 1. Patient Records Trend (Line) & Consultations per Month (Bar)
            try {
                const patientRegCtx = patientRegistrationCanvas.getContext('2d');
                let patientData = <?= json_encode($analytics['patient_registration_trend']) ?>;
                let consultData = <?= json_encode($analytics['consultations_per_month']) ?>;
                // Merge months for both datasets
                let allMonths = Array.from(new Set([
                    ...patientData.map(item => item.month),
                    ...consultData.map(item => item.month)
                ])).sort();
                const patientLabels = allMonths.map(month => {
                    const date = new Date(month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                });
                const patientValues = allMonths.map(month => {
                    const found = patientData.find(item => item.month === month);
                    return found ? found.count : 0;
                });
                const consultValues = allMonths.map(month => {
                    const found = consultData.find(item => item.month === month);
                    return found ? found.count : 0;
                });
                new Chart(patientRegCtx, {
                    type: 'bar',
                    data: {
                        labels: patientLabels,
                        datasets: [
                            {
                                label: 'New Patient Records',
                                data: patientValues,
                                backgroundColor: 'rgba(99, 102, 241, 0.25)',
                                borderColor: '#6366F1',
                                borderWidth: 2,
                                borderRadius: 6
                            },
                            {
                                label: 'Consultations',
                                data: consultValues,
                                backgroundColor: 'rgba(16, 185, 129, 0.45)',
                                borderColor: '#10B981',
                                borderWidth: 2,
                                borderRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { drawBorder: false, color: 'rgba(229, 231, 235, 0.5)' },
                                ticks: { font: { size: 11 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 11 } }
                            }
                        }
                    }
                });
            } catch (error) { console.error('Error initializing patient registration/consultation chart:', error); }
            // 2. Health Issues Breakdown (Bar)
            // Announcement Response (Table)
            fetchAnnouncementResponses();

            function fetchAnnouncementResponses() {
                const loader = document.getElementById('announcementResponseLoader');
                const tableDiv = document.getElementById('announcementResponseTable');
                loader.style.display = '';
                tableDiv.style.display = 'none';
                fetch('/community-health-tracker/api/announcements.php?all_responses=1')
                    .then(res => res.json())
                    .then(data => {
                        loader.style.display = 'none';
                        tableDiv.style.display = '';
                        if (!data.announcements || !data.announcements.length) {
                            tableDiv.innerHTML = '<div class="text-gray-500">No announcement response data available.</div>';
                            return;
                        }
                        let html = `<table class='min-w-full divide-y divide-gray-200'><thead><tr>` +
                            `<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase'>Title</th>` +
                            `<th class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase'>Audience</th>` +
                            `<th class='px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase'>Accepted</th>` +
                            `<th class='px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase'>Dismissed</th>` +
                            `<th class='px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase'>Total Responded</th>` +
                            `<th class='px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase'>Date</th>` +
                            `</tr></thead><tbody>`;
                        data.announcements.forEach(a => {
                            html += `<tr class='bg-white hover:bg-blue-50'>` +
                                `<td class='px-4 py-2'>${a.title}</td>` +
                                `<td class='px-4 py-2'>${a.audience_type === 'all' ? 'All Users' : 'Specific User'}</td>` +
                                `<td class='px-4 py-2 text-center'>${a.response_counts.accepted}</td>` +
                                `<td class='px-4 py-2 text-center'>${a.response_counts.dismissed}</td>` +
                                `<td class='px-4 py-2 text-center'>${a.response_counts.total}</td>` +
                                `<td class='px-4 py-2 text-center'>${a.post_date ? new Date(a.post_date).toLocaleDateString() : ''}</td>` +
                                `</tr>`;
                        });
                        html += '</tbody></table>';
                        tableDiv.innerHTML = html;
                    })
                    .catch(() => {
                        loader.style.display = 'none';
                        tableDiv.style.display = '';
                        tableDiv.innerHTML = '<div class="text-red-500">Failed to load announcement response data.</div>';
                    });
            }
            // 3. Gender Distribution (Donut)
            try {
                const genderDistributionCtx = genderDistributionCanvas.getContext('2d');
                let genderData = <?= json_encode($analytics['gender_distribution']) ?>;
                // Sort by count descending
                genderData = genderData.sort((a, b) => b.count - a.count);
                const genderLabels = genderData.map(item => item.gender || 'Unknown');
                const genderValues = genderData.map(item => item.count);
                const genderColors = genderLabels.map(label => {
                    if (label.toLowerCase() === 'male') return '#3B82F6';
                    if (label.toLowerCase() === 'female') return '#EC4899';
                    return '#6B7280';
                });
                new Chart(genderDistributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: genderLabels,
                        datasets: [{
                            data: genderValues,
                            backgroundColor: genderColors,
                            borderWidth: 1,
                            borderColor: '#ffffff',
                            hoverBackgroundColor: genderColors.map(color => color + 'CC')
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: { size: 12 }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            } catch (error) { console.error('Error initializing gender distribution chart:', error); }
            // 4. Age Distribution (Donut)
            try {
                const ageDistributionCtx = ageDistributionCanvas.getContext('2d');
                let ageData = <?= json_encode($analytics['age_distribution']) ?>;
                // Sort by count descending
                ageData = ageData.sort((a, b) => b.count - a.count);
                const ageLabels = ageData.map(item => item.age_group);
                const ageValues = ageData.map(item => item.count);
                const ageColors = ['#60A5FA', '#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF'];
                new Chart(ageDistributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ageLabels,
                        datasets: [{
                            data: ageValues,
                            backgroundColor: ageColors,
                            borderWidth: 1,
                            borderColor: '#ffffff',
                            hoverBackgroundColor: ageColors.map(color => color + 'CC')
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: { size: 12 }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            } catch (error) { console.error('Error initializing age distribution chart:', error); }
        }

        function closeFullReportModal() {
            const modal = document.getElementById('fullReportModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('active');
            }
        }
    </script>
</body>
<script>
    // Fetch and display report logs
    function fetchReportLogs() {
        const wrap = document.getElementById('reportLogsTableWrap');
        if (!wrap) return;
        // Show custom loader
        wrap.innerHTML = document.getElementById('reportLogsLoading') ? document.getElementById('reportLogsLoading').outerHTML : '';
        fetch('./get_report_logs.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    wrap.innerHTML = `<div class='text-red-600'>Failed to load logs: ${data.error}</div>`;
                    return;
                }
                if (!data.logs.length) {
                    wrap.innerHTML = '<div class="text-gray-500 text-sm">No report generation logs found.</div>';
                    return;
                }
                const logsPerPage = 6;
                let currentPage = 1;
                let sortMode = 'date'; // 'date' or 'name'
                let filterDate = '';
                let logs = [...data.logs];
                function sortLogs() {
                    if (sortMode === 'date') {
                        logs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    } else if (sortMode === 'name') {
                        logs.sort((a, b) => (a.full_name || '').localeCompare(b.full_name || ''));
                    }
                }
                function filterLogsByDate(logsArr) {
                    if (!filterDate) return logsArr;
                    return logsArr.filter(log => {
                        if (!log.created_at) return false;
                        const logDate = new Date(log.created_at);
                        const filter = new Date(filterDate);
                        return logDate.getFullYear() === filter.getFullYear() &&
                            logDate.getMonth() === filter.getMonth() &&
                            logDate.getDate() === filter.getDate();
                    });
                }
                function renderLogs(page) {
                    sortLogs();
                    let filteredLogs = filterLogsByDate(logs);
                    const start = (page - 1) * logsPerPage;
                    const end = start + logsPerPage;
                    let html = `<div class='overflow-x-auto'><table class='min-w-full text-sm text-left border border-gray-200 rounded-lg'><thead><tr class='bg-blue-50 text-blue-900'><th class='py-2 px-3 border-b'>Date & Time</th><th class='py-2 px-3 border-b'>Staff Name</th><th class='py-2 px-3 border-b'>Type</th><th class='py-2 px-3 border-b'>Records</th></tr></thead><tbody>`;
                    for (const log of filteredLogs.slice(start, end)) {
                        html += `<tr class='hover:bg-blue-50'>
                        <td class='py-2 px-3 border-b whitespace-nowrap'>${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</td>
                        <td class='py-2 px-3 border-b'>${log.full_name || ''}</td>
                        <td class='py-2 px-3 border-b'>${log.export_type === 'bulk_patient_records' ? (log.action_type === 'export_bulk_pdf' ? 'PDF' : 'Excel') : (log.export_type || log.action_type)}</td>
                        <td class='py-2 px-3 border-b text-center'>${log.record_count || ''}</td>
                    </tr>`;
                    }
                    html += '</tbody></table></div>';
                    // Pagination controls
                    const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
                    if (totalPages > 1) {
                        html += `<div class='flex justify-end mt-2'>`;
                        for (let i = 1; i <= totalPages; i++) {
                            html += `<button class='px-3 py-1 mx-1 rounded border ${i === page ? 'bg-blue-500 text-white' : 'bg-white text-blue-500'}' onclick='window._renderReportLogsPage(${i})'>${i}</button>`;
                        }
                        html += `</div>`;
                    }
                    wrap.innerHTML = html;
                }
                window._renderReportLogsPage = function (page) {
                    currentPage = page;
                    renderLogs(currentPage);
                };
                // Attach sort and filter button events
                setTimeout(() => {
                    const sortByDateBtn = document.getElementById('sortByDateBtn');
                    const sortByNameBtn = document.getElementById('sortByNameBtn');
                    const filterDateInput = document.getElementById('filterDateInput');
                    const clearDateBtn = document.getElementById('clearDateBtn');
                    if (sortByDateBtn) {
                        sortByDateBtn.onclick = () => {
                            sortMode = 'date';
                            renderLogs(1);
                        };
                    }
                    if (sortByNameBtn) {
                        sortByNameBtn.onclick = () => {
                            sortMode = 'name';
                            renderLogs(1);
                        };
                    }
                    if (filterDateInput) {
                        filterDateInput.onchange = (e) => {
                            filterDate = e.target.value;
                            renderLogs(1);
                        };
                    }
                    if (clearDateBtn) {
                        clearDateBtn.onclick = () => {
                            filterDate = '';
                            if (filterDateInput) filterDateInput.value = '';
                            renderLogs(1);
                        };
                    }
                }, 0);
                renderLogs(currentPage);
            })
            .catch(() => {
                wrap.innerHTML = '<div class="text-red-600">Failed to load logs.</div>';
            });
    }
    // Auto-load logs on page load
    document.addEventListener('DOMContentLoaded', fetchReportLogs);
</script>
<style>
    .warmblue-wave-loader {
        display: flex;
        align-items: flex-end;
        height: 40px;
        gap: 4px;
    }

    .warmblue-wave-loader .wave-bar {
        display: inline-block;
        width: 8px;
        height: 18px;
        background: linear-gradient(180deg, #60a5fa 60%, #3b82f6 100%);
        border-radius: 4px 4px 12px 12px;
        margin: 0 2px;
        animation: warmblue-wave 1.2s infinite ease-in-out;
    }

    .warmblue-wave-loader .wave-bar:nth-child(1) {
        animation-delay: 0s;
    }

    .warmblue-wave-loader .wave-bar:nth-child(2) {
        animation-delay: 0.15s;
    }

    .warmblue-wave-loader .wave-bar:nth-child(3) {
        animation-delay: 0.3s;
    }

    .warmblue-wave-loader .wave-bar:nth-child(4) {
        animation-delay: 0.45s;
    }

    .warmblue-wave-loader .wave-bar:nth-child(5) {
        animation-delay: 0.6s;
    }

    @keyframes warmblue-wave {

        0%,
        100% {
            height: 18px;
            background: linear-gradient(180deg, #60a5fa 60%, #3b82f6 100%);
        }

        50% {
            height: 36px;
            background: linear-gradient(180deg, #3b82f6 60%, #60a5fa 100%);
        }
    }

    .text-warmblue {
        color: #3b82f6;
    }
</style>
</script>

</html>