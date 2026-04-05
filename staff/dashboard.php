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
// Preload announcement response summary for instant display
$announcementSummary = [
    'All Users' => ['accepted' => 0, 'dismissed' => 0, 'total' => 0, 'count' => 0],
    'Specific Users' => ['accepted' => 0, 'dismissed' => 0, 'total' => 0, 'count' => 0],
];
try {
    // Preload announcement summary for today (default view)
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id, audience_type FROM sitio1_announcements WHERE DATE(post_date) = ? AND (audience_type = 'public' OR audience_type = 'specific') ORDER BY post_date DESC");
    $stmt->execute([$today]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($announcements as $a) {
        $aid = $a['id'];
        $cat = $a['audience_type'] === 'public' ? 'All Users' : 'Specific Users';
        // Only count responses from residents (role = 'patient')
        $stmt2 = $pdo->prepare("SELECT ua.status, COUNT(*) as cnt FROM user_announcements ua JOIN sitio1_users u ON ua.user_id = u.id WHERE ua.announcement_id = ? AND u.role = 'patient' GROUP BY ua.status");
        $stmt2->execute([$aid]);
        $counts = ['accepted' => 0, 'dismissed' => 0];
        $total = 0;
        $responseRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        if ($responseRows) {
            foreach ($responseRows as $row) {
                $status = $row['status'];
                $cnt = (int)$row['cnt'];
                if (isset($counts[$status])) $counts[$status] += $cnt;
                $total += $cnt;
            }
        }
        $announcementSummary[$cat]['accepted'] += $counts['accepted'];
        $announcementSummary[$cat]['dismissed'] += $counts['dismissed'];
        $announcementSummary[$cat]['total'] += $total;
        $announcementSummary[$cat]['count']++;
    }
    unset($a);
    unset($stmt2);
    unset($announcements);
    // End preload
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

    // Patient records trend (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM sitio1_patients 
        WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $analytics['patient_registration_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Active Staff trend (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM sitio1_staff 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $analytics['staff_registration_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resident Accounts trend (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM sitio1_users 
        WHERE role = 'patient' AND approved = TRUE AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $analytics['resident_accounts_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/staff-dashboard.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/normalize.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">

    <!-- AJAX Loader -->
    <div id="ajaxLoader" class="cht-analytics-loader-bg" style="display: none;">
        <div style="display: flex; flex-direction: column; align-items: center;">
            <div class="cht-loader-unique">
                <div class="cht-loader-bounce warmblue-dot"></div>
                <div class="cht-loader-bounce warmblue-dot"></div>
                <div class="cht-loader-bounce warmblue-dot"></div>
            </div>
            <div class="cht-loader-text">Loading data insights ...</div>
        </div>
    </div>

    <div class="w-full px-10 py-10">
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
                        <svg width="55" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M14.2498 8.25C14.8432 8.25 15.4232 8.07405 15.9165 7.74441C16.4099 7.41477 16.7944 6.94623 17.0215 6.39805C17.2485 5.84987 17.3079 5.24667 17.1922 4.66473C17.0764 4.08279 16.7907 3.54824 16.3712 3.12868C15.9516 2.70912 15.4171 2.4234 14.8351 2.30765C14.2532 2.19189 13.65 2.2513 13.1018 2.47836C12.5536 2.70543 12.0851 3.08994 11.7554 3.58329C11.4258 4.07664 11.2498 4.65666 11.2498 5.25C11.2498 6.04565 11.5659 6.80871 12.1285 7.37132C12.6911 7.93393 13.4542 8.25 14.2498 8.25ZM14.2498 3.75C14.5465 3.75 14.8365 3.83797 15.0832 4.0028C15.3299 4.16762 15.5221 4.40189 15.6357 4.67598C15.7492 4.95007 15.7789 5.25167 15.721 5.54264C15.6631 5.83361 15.5203 6.10088 15.3105 6.31066C15.1007 6.52044 14.8334 6.6633 14.5425 6.72118C14.2515 6.77906 13.9499 6.74935 13.6758 6.63582C13.4017 6.52229 13.1675 6.33003 13.0026 6.08336C12.8378 5.83668 12.7498 5.54667 12.7498 5.25C12.7498 4.85218 12.9079 4.47065 13.1892 4.18934C13.4705 3.90804 13.852 3.75 14.2498 3.75ZM20.5601 13.1888C20.503 13.215 19.858 13.4963 18.7161 13.4963C17.4176 13.4963 15.477 13.1325 13.0264 11.6213C12.6534 12.6801 12.1691 13.6964 11.5817 14.6531C12.6368 14.9779 13.6293 15.4791 14.517 16.1353C16.3048 17.4984 17.2498 19.4391 17.2498 21.75C17.2498 21.9489 17.1708 22.1397 17.0302 22.2803C16.8895 22.421 16.6987 22.5 16.4998 22.5C16.3009 22.5 16.1102 22.421 15.9695 22.2803C15.8289 22.1397 15.7498 21.9489 15.7498 21.75C15.7498 17.8406 12.4976 16.4334 10.6742 15.9516C10.6226 16.0172 10.5692 16.0838 10.5158 16.1484C8.67452 18.3797 6.36734 19.5403 3.80046 19.5403C3.50809 19.5417 3.21584 19.5282 2.92484 19.5C2.72592 19.4801 2.54306 19.382 2.41647 19.2273C2.28989 19.0726 2.22995 18.8739 2.24984 18.675C2.26973 18.4761 2.36782 18.2932 2.52254 18.1666C2.67726 18.0401 2.87592 17.9801 3.07484 18C5.50484 18.2419 7.6189 17.2978 9.35609 15.1875C10.527 13.7681 11.3248 12.0366 11.7233 10.7813C8.07452 8.65781 5.7439 10.4653 5.71859 10.485C5.64219 10.5502 5.55346 10.5995 5.45767 10.6297C5.36188 10.66 5.26099 10.6708 5.16097 10.6613C5.06095 10.6519 4.96386 10.6224 4.87544 10.5747C4.78703 10.527 4.7091 10.462 4.64628 10.3836C4.58346 10.3052 4.53703 10.215 4.50975 10.1183C4.48246 10.0216 4.47488 9.92045 4.48746 9.82077C4.50003 9.7211 4.5325 9.62497 4.58294 9.53809C4.63338 9.45121 4.70076 9.37534 4.78109 9.315C4.92171 9.2025 8.27046 6.59625 13.1726 9.93094C17.4355 12.8288 19.9161 11.835 19.9395 11.8238C20.0294 11.7813 20.1268 11.7572 20.226 11.7527C20.3253 11.7482 20.4245 11.7635 20.5178 11.7977C20.6111 11.8319 20.6967 11.8843 20.7696 11.9518C20.8424 12.0193 20.9012 12.1007 20.9424 12.1911C20.9835 12.2816 21.0063 12.3793 21.0094 12.4786C21.0125 12.5779 20.9959 12.6769 20.9604 12.7697C20.9249 12.8625 20.8713 12.9473 20.8028 13.0193C20.7342 13.0912 20.6521 13.1488 20.5611 13.1888H20.5601Z" fill="#9333EA"/>
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
                        <svg width="55" height="55" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M47.2656 6.01562H7.73438C6.78369 6.01562 6.01562 6.78369 6.01562 7.73438V47.2656C6.01562 48.2163 6.78369 48.9844 7.73438 48.9844H47.2656C48.2163 48.9844 48.9844 48.2163 48.9844 47.2656V7.73438C48.9844 6.78369 48.2163 6.01562 47.2656 6.01562ZM45.1172 45.1172H9.88281V9.88281H45.1172V45.1172ZM26.4258 21.4844H36.3086C36.5449 21.4844 36.7383 21.291 36.7383 21.0547V18.4766C36.7383 18.2402 36.5449 18.0469 36.3086 18.0469H26.4258C26.1895 18.0469 25.9961 18.2402 25.9961 18.4766V21.0547C25.9961 21.291 26.1895 21.4844 26.4258 21.4844ZM26.4258 29.2188H36.3086C36.5449 29.2188 36.7383 29.0254 36.7383 28.7891V26.2109C36.7383 25.9746 36.5449 25.7812 36.3086 25.7812H26.4258C26.1895 25.7812 25.9961 25.9746 25.9961 26.2109V28.7891C25.9961 29.0254 26.1895 29.2188 26.4258 29.2188ZM26.4258 36.9531H36.3086C36.5449 36.9531 36.7383 36.7598 36.7383 36.5234V33.9453C36.7383 33.709 36.5449 33.5156 36.3086 33.5156H26.4258C26.1895 33.5156 25.9961 33.709 25.9961 33.9453V36.5234C25.9961 36.7598 26.1895 36.9531 26.4258 36.9531ZM18.2617 19.7656C18.2617 20.3354 18.4881 20.8819 18.891 21.2848C19.2939 21.6877 19.8404 21.9141 20.4102 21.9141C20.98 21.9141 21.5264 21.6877 21.9293 21.2848C22.3322 20.8819 22.5586 20.3354 22.5586 19.7656C22.5586 19.1958 22.3322 18.6494 21.9293 18.2465C21.5264 17.8435 20.98 17.6172 20.4102 17.6172C19.8404 17.6172 19.2939 17.8435 18.891 18.2465C18.4881 18.6494 18.2617 19.1958 18.2617 19.7656ZM18.2617 27.5C18.2617 28.0698 18.4881 28.6163 18.891 29.0192C19.2939 29.4221 19.8404 29.6484 20.4102 29.6484C20.98 29.6484 21.5264 29.4221 21.9293 29.0192C22.3322 28.6163 22.5586 28.0698 22.5586 27.5C22.5586 26.9302 22.3322 26.3837 21.9293 25.9808C21.5264 25.5779 20.98 25.3516 20.4102 25.3516C19.8404 25.3516 19.2939 25.5779 18.891 25.9808C18.4881 26.3837 18.2617 26.9302 18.2617 27.5ZM18.2617 35.2344C18.2617 35.8042 18.4881 36.3506 18.891 36.7536C19.2939 37.1565 19.8404 37.3828 20.4102 37.3828C20.98 37.3828 21.5264 37.1565 21.9293 36.7536C22.3322 36.3506 22.5586 35.8042 22.5586 35.2344C22.5586 34.6646 22.3322 34.1181 21.9293 33.7152C21.5264 33.3123 20.98 33.0859 20.4102 33.0859C19.8404 33.0859 19.2939 33.3123 18.891 33.7152C18.4881 34.1181 18.2617 34.6646 18.2617 35.2344Z" fill="#F39C12"/>
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
                        <svg width="55" height="55" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M45.2829 8.43094L17.2995 3.48953C16.4018 3.33156 15.4782 3.53659 14.7316 4.05955C13.985 4.5825 13.4767 5.38055 13.3185 6.27821L6.92689 42.5868C6.84872 43.0316 6.85896 43.4875 6.95703 43.9283C7.0551 44.3691 7.23908 44.7863 7.49846 45.1561C7.75784 45.5258 8.08753 45.8407 8.46868 46.083C8.84984 46.3252 9.275 46.49 9.71986 46.5679L37.7033 51.5093C38.1482 51.5877 38.6043 51.5777 39.0454 51.4798C39.4865 51.3819 39.9039 51.198 40.2739 50.9386C40.6438 50.6792 40.959 50.3494 41.2014 49.968C41.4438 49.5867 41.6086 49.1614 41.6865 48.7163L48.0781 12.4077C48.2346 11.5097 48.0281 10.5863 47.504 9.84053C46.9798 9.09477 46.1809 8.58773 45.2829 8.43094ZM38.2962 48.1233L10.3107 43.1819L16.7023 6.87332L44.6857 11.8147L38.2962 48.1233ZM19.1923 12.5495C19.2719 12.1008 19.5264 11.7022 19.8999 11.4411C20.2734 11.18 20.7352 11.078 21.1839 11.1573L39.016 14.3048C39.4398 14.379 39.8203 14.6095 40.0824 14.9507C40.3445 15.2919 40.4691 15.719 40.4316 16.1476C40.3941 16.5762 40.1972 16.9752 39.8798 17.2657C39.5624 17.5561 39.1476 17.717 38.7173 17.7165C38.6165 17.7163 38.5159 17.7077 38.4165 17.6907L20.5845 14.5411C20.1359 14.4615 19.7372 14.207 19.4761 13.8335C19.2151 13.46 19.113 12.9982 19.1923 12.5495ZM18.0021 19.3214C18.0413 19.099 18.1239 18.8866 18.2452 18.6962C18.3665 18.5057 18.5241 18.3411 18.709 18.2116C18.894 18.0821 19.1026 17.9903 19.323 17.9415C19.5435 17.8927 19.7714 17.8878 19.9937 17.927L37.8257 21.0766C38.2525 21.148 38.6366 21.3777 38.9015 21.72C39.1663 22.0622 39.2923 22.4917 39.2543 22.9227C39.2163 23.3537 39.0172 23.7546 38.6966 24.0452C38.376 24.3358 37.9576 24.4949 37.5249 24.4905C37.4233 24.4907 37.3219 24.4814 37.222 24.4626L19.39 21.3151C18.9417 21.2345 18.5437 20.9793 18.2835 20.6054C18.0232 20.2316 17.922 19.7698 18.0021 19.3214ZM16.8097 26.0911C16.8908 25.6436 17.146 25.2465 17.5192 24.9868C17.8925 24.7271 18.3535 24.6259 18.8013 24.7054L27.713 26.2716C28.1366 26.3458 28.517 26.5761 28.7791 26.9171C29.0412 27.258 29.166 27.6849 29.1287 28.1133C29.0915 28.5418 28.895 28.9407 28.578 29.2314C28.261 29.522 27.8466 29.6833 27.4165 29.6833C27.3157 29.6832 27.2151 29.6746 27.1158 29.6575L18.1997 28.0827C17.7515 28.0026 17.3533 27.7479 17.0927 27.3745C16.8321 27.001 16.7303 26.5395 16.8097 26.0911Z" fill="#3C96E1"/>
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

                <button class="nav-tab-button tab-activity-logs" id="activity-logs-tab" type="button" role="tab"
                    onclick="openActivityLogsModal()"
                    aria-controls="activity-logs"
                    aria-selected="false">
                    <svg class="w-5 h-5 mr-1 inline" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2H15V4H9V2Z" fill="currentColor"/>
                        <path d="M3 5V22H21V5H3ZM5 7H19V20H5V7Z" fill="currentColor"/>
                        <path d="M7 9H9V11H7V9Z" fill="currentColor" opacity="0.5"/>
                        <path d="M12 9H14V11H12V9Z" fill="currentColor" opacity="0.5"/>
                        <path d="M17 9H19V11H17V9Z" fill="currentColor" opacity="0.5"/>
                        <path d="M7 14H9V16H7V14Z" fill="currentColor" opacity="0.5"/>
                        <path d="M12 14H14V16H12V14Z" fill="currentColor" opacity="0.5"/>
                        <path d="M17 14H19V16H17V14Z" fill="currentColor" opacity="0.5"/>
                    </svg>
                    Activity Logs
                </button>
            </div>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content">
            <style>
                .bg-gen-full {
                    background-color: #3C96E1;
                }

                .bg-gen-full:hover {
                    background-color: #1d82d5;
                }

                .bg-sort {
                    background: #3C96E14D;
                    color: #3C96E1;
                }
            </style>
            <!-- Reports Section -->
            <div class="<?= $activeTab === 'reports' ? '' : 'hidden' ?>" id="reports" role="tabpanel"
                aria-labelledby="reports-tab">
                <h2 class="text-2xl font-semibold mb-8 text-gray-700">Comprehensive Health Report</h2>
                <div id="fullReportContent">
                    <div class="flex gap-2 mb-6 pb-6 border-b-2 border-gray-300">
                        <button onclick="generateFullReportModal()"
                            class="px-6 py-3 bg-gen-full text-white text-lg rounded-lg transition font-medium flex items-center">
                            <svg class="w-8 h-8 mr-2" viewBox="0 0 27 27" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M10.125 19.125H7.875V11.25H10.125V19.125ZM14.625 19.125H12.375V7.875H14.625V19.125ZM19.125 19.125H16.875V14.625H19.125V19.125ZM21.375 21.375H5.625V5.625H21.375V21.4875M21.375 3.375H5.625C4.3875 3.375 3.375 4.3875 3.375 5.625V21.375C3.375 22.6125 4.3875 23.625 5.625 23.625H21.375C22.6125 23.625 23.625 22.6125 23.625 21.375V5.625C23.625 4.3875 22.6125 3.375 21.375 3.375Z"
                                    fill="white" />
                            </svg>
                            Generate Full Report</button>
                    </div>
                    <!-- Report Generation Logs Display -->
                    <div id="reportLogsSection" class="mb-6">
                        <div class="flex flex-wrap gap-6 mb-6 pb-6 border-b-2 border-gray-300">
                            <button id="sortByDateBtn"
                                class="px-6 py-3 rounded-md bg-gen-full text-lg font-medium text-white transition">Sort
                                by
                                Date</button>
                            <input type="date" id="filterDateInput"
                                class="px-6 py-3 text-lg rounded-md border border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 transition">
                            <!-- <button id="clearDateBtn"
                                class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
                                title="Clear date filter">✕</button> -->
                            <button id="sortByNameBtn"
                                class="px-6 py-3 text-lg rounded-md bg-sort font-medium text-blue-700 hover:bg-blue-200 transition">Sort
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
                                <div class="text-warmblue text-lg font-medium">Generating Reports... Please wait</div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal for Full Report Display -->
<div id="fullReportModal" class="modal-overlay hidden">
    <div class="modal-container modal-desktop cht-document-modal"
        style="max-width:800px;min-width:350px;width:100%;margin:1rem;display:flex;flex-direction:column;height:auto;max-height:90vh;">
        
        <!-- Fixed Header -->
        <div class="modal-header" style="flex-shrink:0;padding:1.25rem 1.5rem;border-bottom:1px solid #e5e7eb;background-color:white;">
            <div class="flex justify-between items-center" style="gap:1rem;width:100%;">
                <div class="flex items-center" style="gap:0.75rem;flex:1;">
                    <svg width="32" height="32" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                        <path d="M32.9331 6.13159L12.5815 2.53784C11.9286 2.42295 11.2568 2.57207 10.7139 2.9524C10.1709 3.33273 9.80127 3.91313 9.68618 4.56597L5.03774 30.9722C4.98088 31.2957 4.98833 31.6272 5.05966 31.9479C5.13098 32.2685 5.26479 32.5719 5.45343 32.8408C5.64206 33.1097 5.88184 33.3387 6.15904 33.5149C6.43625 33.6911 6.74545 33.8109 7.06899 33.8675L27.4206 37.4613C27.7442 37.5184 28.0758 37.5111 28.3966 37.4399C28.7174 37.3686 29.021 37.2349 29.2901 37.0462C29.5592 36.8576 29.7884 36.6177 29.9647 36.3404C30.1409 36.0631 30.2608 35.7537 30.3174 35.43L34.9659 9.02378C35.0797 8.37069 34.9296 7.69912 34.5483 7.15675C34.1671 6.61438 33.5861 6.24563 32.9331 6.13159ZM27.8518 34.9988L7.49868 31.405L12.1471 4.99878L32.4987 8.59253L27.8518 34.9988ZM13.9581 9.12691C14.016 8.80061 14.2011 8.51066 14.4727 8.3208C14.7443 8.13094 15.0802 8.0567 15.4065 8.11441L28.3752 10.4035C28.6835 10.4575 28.9602 10.6251 29.1508 10.8732C29.3415 11.1214 29.4321 11.432 29.4048 11.7437C29.3775 12.0554 29.2343 12.3456 29.0035 12.5568C28.7726 12.7681 28.471 12.8851 28.1581 12.8847C28.0847 12.8846 28.0116 12.8783 27.9393 12.866L14.9706 10.5753C14.6443 10.5174 14.3543 10.3323 14.1644 10.0607C13.9746 9.78912 13.9003 9.45323 13.9581 9.12691ZM13.0924 14.0519C13.1209 13.8902 13.181 13.7357 13.2692 13.5972C13.3574 13.4587 13.4721 13.339 13.6066 13.2448C13.7411 13.1506 13.8928 13.0839 14.0531 13.0484C14.2134 13.0129 14.3792 13.0093 14.5409 13.0378L27.5096 15.3285C27.82 15.3804 28.0994 15.5475 28.292 15.7963C28.4846 16.0452 28.5762 16.3576 28.5486 16.6711C28.521 16.9845 28.3761 17.2761 28.143 17.4874C27.9098 17.6988 27.6055 17.8144 27.2909 17.8113C27.217 17.8114 27.1432 17.8046 27.0706 17.791L14.1018 15.5019C13.7758 15.4433 13.4863 15.2577 13.2971 14.9858C13.1078 14.7139 13.0342 14.378 13.0924 14.0519ZM12.2252 18.9753C12.2842 18.6499 12.4698 18.3611 12.7413 18.1722C13.0128 17.9833 13.348 17.9097 13.6737 17.9675L20.1549 19.1066C20.463 19.1606 20.7397 19.3281 20.9303 19.5761C21.1209 19.824 21.2116 20.1345 21.1845 20.4461C21.1575 20.7577 21.0146 21.0478 20.784 21.2592C20.5535 21.4706 20.2521 21.5878 19.9393 21.5878C19.866 21.5878 19.7928 21.5815 19.7206 21.5691L13.2362 20.4238C12.9102 20.3655 12.6206 20.1803 12.4311 19.9087C12.2415 19.6371 12.1675 19.3014 12.2252 18.9753Z" fill="#0078DD"/>
                    </svg>
                    <h3 style="margin:0;font-size:1.25rem;font-weight:600;color:#111827;line-height:1.4;">Generated Comprehensive Health Report</h3>
                </div>
                <button type="button" onclick="closeFullReportModal()"
                    style="padding:0.5rem;margin:0;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;border-radius:6px;transition:all 0.2s ease;color:#6b7280;flex-shrink:0;"
                    onmouseover="this.style.backgroundColor='#f3f4f6';this.style.color='#374151';"
                    onmouseout="this.style.backgroundColor='transparent';this.style.color='#6b7280';">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Scrollable Content Area -->
        <div class="modal-body" style="flex:1;overflow-y:auto;padding:1.5rem;">
            <div id="fullReportModalContent"></div>
        </div>
        
        <!-- Fixed Footer -->
        <div class="modal-footer" style="flex-shrink:0;padding:1rem 1.5rem;border-top:1px solid #e5e7eb;background-color:white;">
            <div class="flex flex-wrap gap-3 justify-end">
                <button onclick="printFullReport()"
                    class="px-6 py-4 bg-green-600 text-white rounded-md hover:bg-green-700 transition font-normal flex items-center gap-2" 
                    style="min-width: 115px; justify-content: center;border:none;cursor:pointer;font-size:1rem;">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.1253 6.75H18.75V3.75C18.75 3.55109 18.671 3.36032 18.5303 3.21967C18.3897 3.07902 18.1989 3 18 3H6C5.80109 3 5.61032 3.07902 5.46967 3.21967C5.32902 3.36032 5.25 3.55109 5.25 3.75V6.75H3.87469C2.565 6.75 1.5 7.75969 1.5 9V16.5C1.5 16.6989 1.57902 16.8897 1.71967 17.0303C1.86032 17.171 2.05109 17.25 2.25 17.25H5.25V20.25C5.25 20.4489 5.32902 20.6397 5.46967 20.7803C5.61032 20.921 5.80109 21 6 21H18C18.1989 21 18.3897 20.921 18.5303 20.7803C18.671 20.6397 18.75 20.4489 18.75 20.25V17.25H21.75C21.9489 17.25 22.1397 17.171 22.2803 17.0303C22.421 16.8897 22.5 16.6989 22.5 16.5V9C22.5 7.75969 21.435 6.75 20.1253 6.75ZM6.75 4.5H17.25V6.75H6.75V4.5ZM17.25 19.5H6.75V15H17.25V19.5ZM21 15.75H18.75V14.25C18.75 14.0511 18.671 13.8603 18.5303 13.7197C18.3897 13.579 18.1989 13.5 18 13.5H6C5.80109 13.5 5.61032 13.579 5.46967 13.7197C5.32902 13.8603 5.25 14.0511 5.25 14.25V15.75H3V9C3 8.58656 3.39281 8.25 3.87469 8.25H20.1253C20.6072 8.25 21 8.58656 21 9V15.75ZM18.75 10.875C18.75 11.0975 18.684 11.315 18.5604 11.5C18.4368 11.685 18.2611 11.8292 18.0555 11.9144C17.85 11.9995 17.6238 12.0218 17.4055 11.9784C17.1873 11.935 16.9868 11.8278 16.8295 11.6705C16.6722 11.5132 16.565 11.3127 16.5216 11.0945C16.4782 10.8762 16.5005 10.65 16.5856 10.4445C16.6708 10.2389 16.815 10.0632 17 9.9396C17.185 9.81598 17.4025 9.75 17.625 9.75C17.9234 9.75 18.2095 9.86853 18.4205 10.0795C18.6315 10.2905 18.75 10.5766 18.75 10.875Z" fill="white"/>
                    </svg>
                    Print
                </button>
                <button onclick="exportFullReport('pdf')"
                    class="px-6 py-4 bg-red-600 text-white rounded-md hover:bg-red-700 transition font-normal flex items-center gap-2" 
                    style="min-width: 115px; justify-content: center;border:none;cursor:pointer;font-size:1rem;">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M26.25 12.1875C26.25 12.4361 26.1512 12.6746 25.9754 12.8504C25.7996 13.0262 25.5611 13.125 25.3125 13.125C25.0639 13.125 24.8254 13.0262 24.6496 12.8504C24.4738 12.6746 24.375 12.4361 24.375 12.1875V6.95156L16.602 14.7258C16.426 14.9017 16.1874 15.0005 15.9387 15.0005C15.6899 15.0005 15.4513 14.9017 15.2754 14.7258C15.0995 14.5499 15.0007 14.3113 15.0007 14.0625C15.0007 13.8137 15.0995 13.5751 15.2754 13.3992L23.0484 5.625H17.8125C17.5639 5.625 17.3254 5.52623 17.1496 5.35041C16.9738 5.1746 16.875 4.93614 16.875 4.6875C16.875 4.43886 16.9738 4.2004 17.1496 4.02459C17.3254 3.84877 17.5639 3.75 17.8125 3.75H25.3125C25.5611 3.75 25.7996 3.84877 25.9754 4.02459C26.1512 4.2004 26.25 4.43886 26.25 4.6875V12.1875ZM21.5625 15C21.3139 15 21.0754 15.0988 20.8996 15.2746C20.7238 15.4504 20.625 15.6889 20.625 15.9375V24.375H5.625V9.375H14.0625C14.3111 9.375 14.5496 9.27623 14.7254 9.10041C14.9012 8.9246 15 8.68614 15 8.4375C15 8.18886 14.9012 7.9504 14.7254 7.77459C14.5496 7.59877 14.3111 7.5 14.0625 7.5H5.625C5.12772 7.5 4.65081 7.69754 4.29917 8.04918C3.94754 8.40081 3.75 8.87772 3.75 9.375V24.375C3.75 24.8723 3.94754 25.3492 4.29917 25.7008C4.65081 26.0525 5.12772 26.25 5.625 26.25H20.625C21.1223 26.25 21.5992 26.0525 21.9508 25.7008C22.3025 25.3492 22.5 24.8723 22.5 24.375V15.9375C22.5 15.6889 22.4012 15.4504 22.2254 15.2746C22.0496 15.0988 21.8111 15 21.5625 15Z" fill="white"/>
                    </svg>
                    Export PDF
                </button>
                <button type="button" onclick="closeFullReportModal()"
                    class="px-6 py-4 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition font-normal flex items-center gap-2" 
                    style="min-width: 115px; justify-content: center;border:none;cursor:pointer;font-size:1rem;">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.4133 11.9133L16.3254 15L19.4133 18.0867C19.5004 18.1738 19.5695 18.2772 19.6166 18.391C19.6638 18.5048 19.688 18.6268 19.688 18.75C19.688 18.8732 19.6638 18.9952 19.6166 19.109C19.5695 19.2228 19.5004 19.3262 19.4133 19.4133C19.3262 19.5004 19.2228 19.5695 19.109 19.6166C18.9952 19.6638 18.8732 19.688 18.75 19.688C18.6268 19.688 18.5048 19.6638 18.391 19.6166C18.2772 19.5695 18.1738 19.5004 18.0867 19.4133L15 16.3254L11.9133 19.4133C11.8262 19.5004 11.7228 19.5695 11.609 19.6166C11.4952 19.6638 11.3732 19.688 11.25 19.688C11.1268 19.688 11.0048 19.6638 10.891 19.6166C10.7772 19.5695 10.6738 19.5004 10.5867 19.4133C10.4996 19.3262 10.4305 19.2228 10.3834 19.109C10.3362 18.9952 10.312 18.8732 10.312 18.75C10.312 18.6268 10.3362 18.5048 10.3834 18.391C10.4305 18.2772 10.4996 18.1738 10.5867 18.0867L13.6746 15L10.5867 11.9133C10.4108 11.7374 10.312 11.4988 10.312 11.25C10.312 11.0012 10.4108 10.7626 10.5867 10.5867C10.7626 10.4108 11.0012 10.312 11.25 10.312C11.4988 10.312 11.7374 10.4108 11.9133 10.5867L15 13.6746L18.0867 10.5867C18.1738 10.4996 18.2772 10.4305 18.391 10.3834C18.5048 10.3362 18.6268 10.312 18.75 10.312C18.8732 10.312 18.9952 10.3362 19.109 10.3834C19.2228 10.4305 19.3262 10.4996 19.4133 10.5867C19.5004 10.6738 19.5695 10.7772 19.6166 10.891C19.6638 11.0048 19.688 11.1268 19.688 11.25C19.688 11.3732 19.6638 11.4952 19.6166 11.609C19.5695 11.7228 19.5004 11.8262 19.4133 11.9133ZM27.1875 15C27.1875 17.4105 26.4727 19.7668 25.1335 21.771C23.7944 23.7752 21.8909 25.3373 19.664 26.2598C17.437 27.1822 14.9865 27.4236 12.6223 26.9533C10.2582 26.4831 8.08659 25.3223 6.38214 23.6179C4.67769 21.9134 3.51694 19.7418 3.04668 17.3777C2.57643 15.0135 2.81778 12.563 3.74022 10.336C4.66267 8.10907 6.22477 6.20564 8.22899 4.86646C10.2332 3.52728 12.5895 2.8125 15 2.8125C18.2313 2.81591 21.3292 4.10104 23.6141 6.3859C25.899 8.67076 27.1841 11.7687 27.1875 15ZM25.3125 15C25.3125 12.9604 24.7077 10.9666 23.5745 9.27068C22.4414 7.5748 20.8308 6.25302 18.9464 5.47249C17.0621 4.69196 14.9886 4.48774 12.9881 4.88565C10.9877 5.28356 9.1502 6.26573 7.70797 7.70796C6.26574 9.15019 5.28357 10.9877 4.88566 12.9881C4.48775 14.9886 4.69197 17.0621 5.4725 18.9464C6.25303 20.8308 7.5748 22.4414 9.27069 23.5745C10.9666 24.7077 12.9604 25.3125 15 25.3125C17.7341 25.3094 20.3553 24.2219 22.2886 22.2886C24.2219 20.3553 25.3094 17.7341 25.3125 15Z" fill="#555555"/>
                    </svg>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function closeFullReportModal() {
    const modal = document.getElementById('fullReportModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function printFullReport() {
    const content = document.getElementById('fullReportModalContent');
    if (content) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Health Report</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 20px; 
                            margin: 0;
                        }
                        @media print {
                            body { 
                                margin: 0; 
                                padding: 15px; 
                            }
                            @page {
                                margin: 1.5cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${content.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

function exportFullReport(format) {
    if (format === 'pdf') {
        const content = document.getElementById('fullReportModalContent');
        if (content) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Health Report</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                padding: 20px;
                            }
                        </style>
                    </head>
                    <body>
                        ${content.innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    }
}
</script>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-overlay.hidden {
    display: none;
}

.modal-container {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.modal-body {
    scrollbar-width: thin;
}

.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.flex {
    display: flex;
}

.justify-between {
    justify-content: space-between;
}

.items-center {
    align-items: center;
}

.gap-3 {
    gap: 0.75rem;
}

.justify-end {
    justify-content: flex-end;
}

.flex-wrap {
    flex-wrap: wrap;
}
</style>
                    
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
                            <div class="cht-loader-bounce warmblue-dot"></div>
                            <div class="cht-loader-bounce warmblue-dot"></div>
                            <div class="cht-loader-bounce warmblue-dot"></div>
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
                                <div class="cht-loader-bg"">
                                    <div class="cht-loader-complete" style="display:flex;flex-direction:column;align-items:center;gap:1.2rem;">
                                        <div style="position:relative;display:flex;align-items:center;justify-content:center;">
                                            <div style="position:absolute;width:110px;height:110px;"></div>
                                            <svg width="70" height="70" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
<mask id="mask0_2609_13581" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="3" y="2" width="54" height="56">
<path d="M29.9989 5L36.5652 9.79L44.6939 9.775L47.1902 17.51L53.7752 22.275L51.2489 30L53.7752 37.725L47.1902 42.49L44.6939 50.225L36.5652 50.21L29.9989 55L23.4327 50.21L15.3039 50.225L12.8077 42.49L6.22266 37.725L8.74891 30L6.22266 22.275L12.8077 17.51L15.3039 9.775L23.4327 9.79L29.9989 5Z" fill="white"/>
<path d="M28.5244 2.98027C29.4022 2.3399 30.5933 2.33993 31.4711 2.98027L37.3769 7.28691L44.6889 7.2747L45.0893 7.30644C46.0059 7.45317 46.7795 8.10134 47.0717 9.00566L49.3178 15.9637L55.2407 20.2483C56.1208 20.8852 56.4888 22.0184 56.1513 23.0511L53.8784 29.9993L56.1513 36.9476C56.4888 37.9802 56.1208 39.1134 55.2407 39.7503L49.3178 44.0325L47.0717 50.993C46.7377 52.0264 45.7751 52.7259 44.6889 52.7239L37.3769 52.7093L31.4711 57.0184C30.5933 57.6587 29.4022 57.6587 28.5244 57.0184L22.6186 52.7093L15.309 52.7239C14.2227 52.7259 13.2577 52.0267 12.9238 50.993L10.6777 44.0325L4.75728 39.7503C3.87701 39.1133 3.50894 37.9803 3.84663 36.9476L6.11958 29.9993L3.84663 23.0511C3.50894 22.0183 3.87701 20.8853 4.75728 20.2483L10.6777 15.9637L12.9238 9.00566C13.2577 7.97193 14.2227 7.2727 15.309 7.2747L22.6186 7.28691L28.5244 2.98027ZM24.9062 11.8084C24.4771 12.1214 23.9579 12.2903 23.4267 12.2894L17.1206 12.2771L15.187 18.2781C15.0238 18.7833 14.7039 19.2241 14.2739 19.5354L9.16402 23.2293L11.1245 29.2229C11.2893 29.7275 11.2893 30.2712 11.1245 30.7757L9.16402 36.7669L14.2739 40.4632L14.5742 40.7195C14.8535 40.9984 15.0645 41.3413 15.187 41.7205L17.1206 47.719L23.4267 47.7093C23.9579 47.7083 24.4771 47.8772 24.9062 48.1902L29.9965 51.9036L35.0917 48.1902L35.4287 47.9827C35.7801 47.8034 36.1706 47.7086 36.5688 47.7093L42.8725 47.719L44.811 41.7205L44.9624 41.3543C45.1414 41.0031 45.4017 40.6965 45.7241 40.4632L50.8291 36.7669L48.871 30.7757C48.7064 30.2714 48.7064 29.7272 48.871 29.2229L50.8291 23.2293L45.7241 19.5354C45.294 19.2242 44.9743 18.7833 44.811 18.2781L42.8725 12.2771L36.5688 12.2894C36.0378 12.2902 35.5208 12.1213 35.0917 11.8084L29.9965 8.09257L24.9062 11.8084Z" fill="white"/>
<path d="M38.2322 21.9822C39.2085 21.0059 40.7911 21.0059 41.7674 21.9822C42.7437 22.9585 42.7437 24.5411 41.7674 25.5174L29.2674 38.0174C28.2911 38.9937 26.7085 38.9937 25.7322 38.0174L19.4822 31.7674C18.5059 30.7911 18.5059 29.2085 19.4822 28.2322C20.4585 27.2559 22.0411 27.2559 23.0174 28.2322L27.4998 32.7147L38.2322 21.9822Z" fill="black"/>
</mask>
<g mask="url(#mask0_2609_13581)">
<path d="M0 0H60V60H0V0Z" fill="#10B981"/>
</g>
</svg>

                                        </div>
                                        <div class="cht-loader-complete-text" style="color:#059669;font-size:1.7rem;font-weight:500;letter-spacing:0.01em;">Report Generation Complete</div>
                                        <div style="color:#374151;font-size:1.2rem;font-weight:400;text-align:center;max-width:500px;">Your comprehensive health report is ready!</div>
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
                .cht-loader-bounce.warmblue-dot {
                    width: 22px; height: 22px; border-radius: 50%;
                    background: linear-gradient(135deg, #e0e7ff 0%, #60a5fa 60%, #2563eb 100%);
                    animation: cht-bounce 1.1s infinite cubic-bezier(.68,-0.55,.27,1.55);
                }
                .cht-loader-bounce.warmblue-dot:nth-child(2) {
                    animation-delay: 0.2s;
                    background: linear-gradient(135deg, #dbeafe 0%, #60a5fa 60%, #2563eb 100%);
                }
                .cht-loader-bounce.warmblue-dot:nth-child(3) {
                    animation-delay: 0.4s;
                    background: linear-gradient(135deg, #bfdbfe 0%, #60a5fa 60%, #2563eb 100%);
                }
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
                        html += `<style>
                        .cht-report-modal-container {
                            max-width: 650px;
                            margin: 0 auto;
                            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
                            background: #fff;
                            /* border-radius removed */
                            border: 1.5px solid #e5e7eb;
                            box-shadow: 0 4px 24px rgba(30,41,59,0.10);
                            padding: 2rem;
                        }
                        .cht-report-modal-header {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            margin-bottom: 0.5rem;
                        }
                        .cht-report-modal-header img {
                            height: 60px;
                            width: 60px;
                            object-fit: contain;
                            border-radius: 0;
                            background: #fff;
                            border: none;
                        }
                        .cht-report-modal-govinfo {
                            flex: 1;
                            text-align: center;
                            font-size: 1.05rem;
                            color: #22223b;
                            font-weight: 500;
                            line-height: 1.3;
                        }
                        .cht-report-modal-titlebar {
                            text-align: center;
                            margin-bottom: 1.2rem;
                        }
                        .cht-report-modal-titlebar .cht-office {
                            font-size: 1.08rem;
                            font-weight: 700;
                            color: #22223b;
                        }
                        .cht-report-modal-titlebar .cht-report-title {
                            font-size: 1.05rem;
                            color: #22223b;
                            font-weight: 600;
                        }
                        .cht-report-modal-section {
                            border: 1.5px solid #e5e7eb;
                            border-radius: 0.7rem;
                            background: #f9fafb;
                            margin-bottom: 1.2rem;
                            padding: 1.5rem;
                        }
                        .cht-report-modal-section-title {
                            font-size: 1.08rem;
                            font-weight: 700;
                            color: #22223b;
                            margin-bottom: 0.7rem;
                        }
                        .cht-report-modal-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 0.5rem;
                        }
                        .cht-report-modal-table th, .cht-report-modal-table td {
                            border: 1px solid #e5e7eb;
                            padding: 1em 1.2em;
                            text-align: left;
                            vertical-align: top;
                            font-size: 1rem;
                        }
                        .cht-report-modal-table th {
                            background: #f1f5f9;
                            font-weight: 600;
                            color: #1e293b;
                            width: 220px;
                        }
                        .cht-report-modal-table tr:not(:last-child) td {
                            border-bottom: 1px solid #e5e7eb;
                        }
                        .cht-report-modal-recommend th {
                            width: 180px;
                        }
                        .cht-report-modal-footer {
                            margin-top: 2rem;
                            font-size: 0.98em;
                            color: #64748b;
                            text-align: right;
                        }
                        </style>`;
                        html += `<div class="cht-report-modal-container">
                            <div class="cht-report-modal-header">
                                <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo">
                                <div class="cht-report-modal-govinfo">
                                    Republic of the Philippines<br>
                                    City of Cebu, Philippines<br>
                                    Barangay Luz, Cebu City
                                </div>
                                <img src="../asssets/images/DOH.webp" alt="City Health Logo">
                            </div>
                            <div class="cht-report-modal-titlebar">
                                <div class="cht-office">OFFICE OF THE CITY HEALTH</div>
                                <div class="cht-report-title">Comprehensive Health Report</div>
                            </div>
                            <div class="cht-report-modal-section">
                                <div class="cht-report-modal-section-title">Resident Demographics</div>
                                <table class="cht-report-modal-table">
                                    <tr><th>Total Registered Residents</th><td>${data.resident_demographics.total_registered}</td></tr>
                                    <tr><th>New Registrations This Period</th><td>${data.resident_demographics.new_registrations}</td></tr>
                                    <tr><th>Age Distribution</th><td>
                                        Children (0–12): ${data.resident_demographics.age_distribution.children} &nbsp; | &nbsp; Adults (20–59): ${data.resident_demographics.age_distribution.adults}<br>
                                        Adolescents (13–19): ${data.resident_demographics.age_distribution.adolescents} &nbsp; | &nbsp; Seniors (60+): ${data.resident_demographics.age_distribution.seniors}
                                    </td></tr>
                                    <tr><th>Sex Distribution</th><td>Male: ${data.resident_demographics.sex_distribution.Male} &nbsp; Female: ${data.resident_demographics.sex_distribution.Female}</td></tr>
                                </table>
                            </div>
                            <div class="cht-report-modal-section">
                                <div class="cht-report-modal-section-title">Medical Information Summary</div>
                                <table class="cht-report-modal-table">
                                    <tr><th>Common Conditions Recorded</th><td>
                                        Hypertension: ${data.medical_summary.common_conditions.hypertension}<br>
                                        Diabetes: ${data.medical_summary.common_conditions.diabetes}<br>
                                        Tuberculosis: ${data.medical_summary.common_conditions.tuberculosis}<br>
                                        Other: ${Object.keys(data.medical_summary.common_conditions.other).map(k => k + ': ' + data.medical_summary.common_conditions.other[k]).join(' ')}
                                    </td></tr>
                                    <tr><th>Immunization Coverage</th><td>
                                        Fully immunized Children: ${data.medical_summary.fully_immunized_children}<br>
                                        Vaccines Administered (by type): ${Object.keys(data.medical_summary.vaccines_administered).map(k => k + ': ' + data.medical_summary.vaccines_administered[k]).join(' ')}
                                    </td></tr>
                                </table>
                            </div>
                            <div class="cht-report-modal-section">
                                <div class="cht-report-modal-section-title">Administrative Data</div>
                                <table class="cht-report-modal-table">
                                    <tr><th>Average Patients per Doctor</th><td>${data.admin.avg_patients_per_doctor}</td></tr>
                                    <tr><th>Average Patients per Nurse</th><td>${data.admin.avg_patients_per_nurse}</td></tr>
                                </table>
                            </div>
                            <div class="cht-report-modal-section cht-report-modal-recommend">
                                <table class="cht-report-modal-table">
                                    <tr><th>Recommendation</th><td>${data.recommendations}</td></tr>
                                    <tr><th>Prepared by</th><td>${data.prepared_by}</td></tr>
                                    <tr><th>Date</th><td>${data.date}</td></tr>
                                </table>
                            </div>
                        </div>`;
                        return html;
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

            <div id="activityLogsModal" class="modal-overlay hidden">
                <div class="modal-container activity-logs-modal-shell" onclick="event.stopPropagation()">
                <div class="activity-logs-modal-card">
                    <div class="activity-logs-modal-header">
                        <div>
                            <h2 class="text-xl font-semibold text-secondary mb-1">Activity Logs</h2>
                            <p class="text-gray-500 text-base">System activity and user actions</p>
                        </div>
                        <button onclick="closeActivityLogsModal()" class="activity-logs-close-btn" type="button" aria-label="Close activity logs">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="activity-logs-toolbar">
                        <div class="flex gap-2" id="staffActivityLogsTabs">
                            <button class="log-tab log-tab-active" id="staffResidentTabBtn" type="button">Resident Log</button>
                            <button class="log-tab" id="staffAdminTabBtn" type="button">Admin Log</button>
                        </div>
                        <div class="activity-logs-actions">
                            <button type="button" id="staffExportLogsBtn" class="log-tab activity-logs-action-btn" title="Export">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                        </div>
                    </div>

                    <div class="activity-logs-loading" id="dashboardActivityLogsLoading" aria-live="polite">
                        <div class="activity-logs-spinner" aria-hidden="true"></div>
                        <div class="activity-logs-loading-text">Loading records...</div>
                    </div>

                    <div id="staffResidentLogsSection" class="activity-log-panel">
                        <div class="overflow-x-auto mb-4 activity-logs-wrapper rounded-xl">
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>Time Log</th>
                                        <th>Resident</th>
                                        <th>Action Performed</th>
                                    </tr>
                                </thead>
                                <tbody id="residentLogsTableBody"></tbody>
                            </table>
                        </div>
                        <div id="residentLogsEmpty" class="activity-log-empty text-center text-gray-500 py-6 hidden">No resident logs found.</div>
                        <div id="residentLogsPagination" class="activity-logs-pagination hidden">
                            <div id="residentLogsPageInfo" class="activity-logs-page-info"></div>
                            <div class="activity-logs-page-actions">
                                <button type="button" id="residentLogsPrevBtn" class="activity-logs-page-btn">Prev</button>
                                <button type="button" id="residentLogsNextBtn" class="activity-logs-page-btn">Next <i class="fas fa-chevron-right ml-1 text-xs"></i></button>
                            </div>
                        </div>
                    </div>

                    <div id="staffAdminLogsSection" class="activity-log-panel hidden">
                        <div class="overflow-x-auto mb-4 activity-logs-wrapper rounded-xl">
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>Time Log</th>
                                        <th>Admin Name</th>
                                        <th>Action Performed</th>
                                    </tr>
                                </thead>
                                <tbody id="adminLogsTableBody"></tbody>
                            </table>
                        </div>
                        <div id="adminLogsEmpty" class="activity-log-empty text-center text-gray-500 py-6 hidden">No admin logs found.</div>
                        <div id="adminLogsPagination" class="activity-logs-pagination hidden">
                            <div id="adminLogsPageInfo" class="activity-logs-page-info"></div>
                            <div class="activity-logs-page-actions">
                                <button type="button" id="adminLogsPrevBtn" class="activity-logs-page-btn">Prev</button>
                                <button type="button" id="adminLogsNextBtn" class="activity-logs-page-btn">Next <i class="fas fa-chevron-right ml-1 text-xs"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Analytics Dashboard Section -->
            <div class="<?= $activeTab === 'analytics' ? '' : 'hidden' ?>" id="analytics" role="tabpanel"
                aria-labelledby="analytics-tab">
                <div class="flex justify-between items-center mb-8 pb-4 border-b-2 border-gray-100">
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
                            <svg width="50" height="50" class="mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.75 19.5C21.75 19.6989 21.671 19.8897 21.5303 20.0303C21.3897 20.171 21.1989 20.25 21 20.25H3C2.80109 20.25 2.61032 20.171 2.46967 20.0303C2.32902 19.8897 2.25 19.6989 2.25 19.5V4.5C2.25 4.30109 2.32902 4.11032 2.46967 3.96967C2.61032 3.82902 2.80109 3.75 3 3.75C3.19891 3.75 3.38968 3.82902 3.53033 3.96967C3.67098 4.11032 3.75 4.30109 3.75 4.5V13.3472L8.50594 9.1875C8.63536 9.07421 8.79978 9.00885 8.97165 9.00236C9.14353 8.99587 9.31241 9.04866 9.45 9.15188L14.9634 13.2872L20.5059 8.4375C20.5786 8.36556 20.6652 8.30925 20.7605 8.27201C20.8557 8.23478 20.9575 8.21741 21.0597 8.22097C21.1619 8.22454 21.2623 8.24896 21.3547 8.29275C21.4471 8.33653 21.5296 8.39875 21.5971 8.47558C21.6645 8.5524 21.7156 8.64222 21.7471 8.7395C21.7786 8.83678 21.7899 8.93948 21.7802 9.04128C21.7706 9.14307 21.7402 9.24182 21.691 9.33146C21.6418 9.42109 21.5748 9.49972 21.4941 9.5625L15.4941 14.8125C15.3646 14.9258 15.2002 14.9912 15.0283 14.9976C14.8565 15.0041 14.6876 14.9513 14.55 14.8481L9.03656 10.7147L3.75 15.3403V18.75H21C21.1989 18.75 21.3897 18.829 21.5303 18.9697C21.671 19.1103 21.75 19.3011 21.75 19.5Z" fill="#3C96E1"/>
</svg>


                            Patient Records Trend
                        </h3>
                        <div class="chart-wrapper">
                            <canvas id="patientRegistrationChart" height="300"></canvas>
                        </div>
                    </div>
                    <!-- Announcement Response (Summary Table) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <svg width="50" height="50" class="mr-3" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M44.6367 16.9258L10.25 6.37891C9.78458 6.24316 9.29396 6.21756 8.81694 6.30414C8.33992 6.39071 7.88959 6.58709 7.50156 6.87773C7.11353 7.16838 6.79844 7.54532 6.58122 7.97874C6.364 8.41217 6.25061 8.89019 6.25 9.375V37.5C6.25 38.3288 6.57924 39.1237 7.16529 39.7097C7.75134 40.2958 8.5462 40.625 9.375 40.625C9.67383 40.6251 9.97113 40.5824 10.2578 40.4981L26.5625 35.4941V37.5C26.5625 38.3288 26.8917 39.1237 27.4778 39.7097C28.0638 40.2958 28.8587 40.625 29.6875 40.625H35.9375C36.7663 40.625 37.5612 40.2958 38.1472 39.7097C38.7333 39.1237 39.0625 38.3288 39.0625 37.5V31.6602L44.6367 29.9512C45.2819 29.7573 45.8476 29.3613 46.2506 28.8215C46.6536 28.2818 46.8725 27.6268 46.875 26.9531V19.9219C46.8721 19.2486 46.653 18.594 46.2501 18.0546C45.8471 17.5152 45.2815 17.1195 44.6367 16.9258ZM26.5625 32.2266L9.375 37.5V9.375L26.5625 14.6484V32.2266ZM35.9375 37.5H29.6875V34.5352L35.9375 32.6172V37.5ZM43.75 26.9531H43.7285L29.6875 31.2656V15.6094L43.7285 19.9063H43.75V26.9375V26.9531Z" fill="#3C96E1"/>
</svg>

                            Announcement Response
                        </h3>
                        <div class="chart-wrapper" id="announcementResponseWrapper">
                            <div class="flex flex-wrap gap-4 mb-4 items-center">
                                <label class="font-medium text-gray-600">View by:</label>
                                <select id="announcementTimeFilter" class="border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400">
                                    <option value="day">Day</option>
                                    <option value="month">Month</option>
                                    <option value="year">Year</option>
                                </select>
                                <input type="date" id="announcementDateInput" class="border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400" />
                                <button id="announcementFilterBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Apply</button>
                            </div>
                            <!-- INSTANT ANNOUNCEMENT SUMMARY (PHP) -->
                            <div id="announcementResponseLoader" class="flex justify-center items-center h-48" style="display:none;">
                                <span class="text-gray-500">Loading announcement responses...</span>
                            </div>
                            <div id="announcementResponseTable">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($announcementSummary as $cat => $summary): ?>
                                        <div class='bg-white rounded shadow p-4'>
                                            <h4 class='font-semibold text-blue-700 mb-2 text-lg'><?= htmlspecialchars($cat) ?></h4>
                                            <div class='mb-2 text-gray-700'>Total Announcements: <span class='font-bold'><?= $summary['count'] ?></span></div>
                                            <div class='mb-2 text-green-700'>Accepted: <span class='font-bold'><?= $summary['accepted'] ?></span></div>
                                            <div class='mb-2 text-red-700'>Dismissed: <span class='font-bold'><?= $summary['dismissed'] ?></span></div>
                                            <div class='mb-2 text-gray-700'>Total Responses: <span class='font-bold'><?= $summary['total'] ?></span></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Gender Distribution (Donut Graph) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <svg width="50" height="50" class="mr-3" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M40.6237 4.6875H32.8112C32.3968 4.6875 31.9994 4.85212 31.7063 5.14515C31.4133 5.43817 31.2487 5.8356 31.2487 6.25C31.2487 6.6644 31.4133 7.06183 31.7063 7.35485C31.9994 7.64788 32.3968 7.8125 32.8112 7.8125H36.8522L31.9401 12.7246C30.4977 11.3805 28.7579 10.3965 26.8627 9.85321C24.9674 9.30989 22.9706 9.22258 21.0352 9.59841C19.0997 9.97424 17.2807 10.8026 15.7265 12.0157C14.1724 13.2288 12.9272 14.7924 12.0928 16.5787C11.2583 18.365 10.8582 20.3233 10.9252 22.2937C10.9921 24.2642 11.5242 26.1908 12.478 27.9163C13.4318 29.6419 14.7803 31.1173 16.4132 32.2221C18.0461 33.327 19.9172 34.0299 21.8737 34.2734V37.5H17.1862C16.7718 37.5 16.3744 37.6646 16.0813 37.9576C15.7883 38.2507 15.6237 38.6481 15.6237 39.0625C15.6237 39.4769 15.7883 39.8743 16.0813 40.1674C16.3744 40.4604 16.7718 40.625 17.1862 40.625H21.8737V45.3125C21.8737 45.7269 22.0383 46.1243 22.3313 46.4174C22.6244 46.7104 23.0218 46.875 23.4362 46.875C23.8506 46.875 24.248 46.7104 24.541 46.4174C24.8341 46.1243 24.9987 45.7269 24.9987 45.3125V40.625H29.6862C30.1006 40.625 30.498 40.4604 30.791 40.1674C31.0841 39.8743 31.2487 39.4769 31.2487 39.0625C31.2487 38.6481 31.0841 38.2507 30.791 37.9576C30.498 37.6646 30.1006 37.5 29.6862 37.5H24.9987V34.2734C27.0931 34.0118 29.0869 33.2235 30.7939 31.9821C32.5009 30.7407 33.8653 29.0868 34.7596 27.1749C35.6539 25.2631 36.0488 23.1557 35.9076 21.0498C35.7663 18.9438 35.0934 16.9081 33.9518 15.1328L39.0612 10.0215V14.0625C39.0612 14.4769 39.2258 14.8743 39.5188 15.1674C39.8119 15.4604 40.2093 15.625 40.6237 15.625C41.0381 15.625 41.4355 15.4604 41.7285 15.1674C42.0216 14.8743 42.1862 14.4769 42.1862 14.0625V6.25C42.1862 5.8356 42.0216 5.43817 41.7285 5.14515C41.4355 4.85212 41.0381 4.6875 40.6237 4.6875ZM23.4362 31.25C21.582 31.25 19.7694 30.7002 18.2277 29.67C16.686 28.6399 15.4844 27.1757 14.7748 25.4627C14.0652 23.7496 13.8796 21.8646 14.2413 20.046C14.6031 18.2275 15.4959 16.557 16.8071 15.2459C18.1182 13.9348 19.7886 13.0419 21.6072 12.6801C23.4258 12.3184 25.3108 12.5041 27.0238 13.2136C28.7369 13.9232 30.2011 15.1248 31.2312 16.6665C32.2613 18.2082 32.8112 20.0208 32.8112 21.875C32.8086 24.3606 31.82 26.7437 30.0625 28.5013C28.3049 30.2589 25.9218 31.2474 23.4362 31.25Z" fill="#3C96E1"/>
</svg>

                            Gender Distribution
                        </h3>
                        <div class="chart-wrapper">
                            <canvas id="genderDistributionChart" height="300"></canvas>
                        </div>
                    </div>
                    <!-- Age Distribution (Donut Graph) -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <svg width="50" height="50" class="mr-3" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M33.5938 14.8438C33.5938 13.1441 33.0897 11.4826 32.1454 10.0693C31.2012 8.65609 29.859 7.5546 28.2887 6.90416C26.7184 6.25372 24.9905 6.08354 23.3234 6.41513C21.6564 6.74672 20.1252 7.5652 18.9233 8.76706C17.7214 9.96891 16.903 11.5002 16.5714 13.1672C16.2398 14.8342 16.41 16.5621 17.0604 18.1324C17.7109 19.7027 18.8123 21.0449 20.2256 21.9892C21.6388 22.9335 23.3003 23.4375 25 23.4375C27.2784 23.4349 29.4628 22.5287 31.0738 20.9176C32.6849 19.3065 33.5912 17.1222 33.5938 14.8438ZM25 20.3125C23.9184 20.3125 22.8611 19.9918 21.9617 19.3909C21.0624 18.7899 20.3615 17.9358 19.9475 16.9366C19.5336 15.9373 19.4253 14.8377 19.6363 13.7769C19.8473 12.716 20.3682 11.7416 21.133 10.9768C21.8978 10.2119 22.8723 9.6911 23.9331 9.48008C24.9939 9.26907 26.0935 9.37737 27.0928 9.79129C28.0921 10.2052 28.9462 10.9061 29.5471 11.8055C30.148 12.7048 30.4688 13.7621 30.4688 14.8438C30.4688 16.2942 29.8926 17.6852 28.867 18.7107C27.8414 19.7363 26.4504 20.3125 25 20.3125ZM36.7188 25C35.0191 25 33.3576 25.504 31.9443 26.4483C30.5311 27.3926 29.4296 28.7348 28.7792 30.3051C28.1287 31.8754 27.9585 33.6033 28.2901 35.2703C28.6217 36.9373 29.4402 38.4686 30.6421 39.6705C31.8439 40.8723 33.3752 41.6908 35.0422 42.0224C36.7092 42.354 38.4371 42.1838 40.0074 41.5333C41.5777 40.8829 42.9199 39.7814 43.8642 38.3682C44.8085 36.9549 45.3125 35.2934 45.3125 33.5938C45.3099 31.3153 44.4037 29.131 42.7926 27.5199C41.1815 25.9088 38.9972 25.0026 36.7188 25ZM36.7188 39.0625C35.6371 39.0625 34.5798 38.7418 33.6805 38.1409C32.7811 37.5399 32.0802 36.6858 31.6663 35.6866C31.2524 34.6873 31.1441 33.5877 31.3551 32.5269C31.5661 31.466 32.0869 30.4916 32.8518 29.7268C33.6166 28.9619 34.591 28.4411 35.6519 28.2301C36.7127 28.0191 37.8123 28.1274 38.8116 28.5413C39.8108 28.9552 40.6649 29.6561 41.2659 30.5555C41.8668 31.4548 42.1875 32.5121 42.1875 33.5938C42.1875 35.0442 41.6113 36.4352 40.5857 37.4607C39.5602 38.4863 38.1692 39.0625 36.7188 39.0625ZM13.2813 25C11.5816 25 9.92006 25.504 8.50682 26.4483C7.09359 27.3926 5.9921 28.7348 5.34166 30.3051C4.69122 31.8754 4.52104 33.6033 4.85263 35.2703C5.18422 36.9373 6.0027 38.4686 7.20456 39.6705C8.40641 40.8723 9.93767 41.6908 11.6047 42.0224C13.2717 42.354 14.9996 42.1838 16.5699 41.5333C18.1402 40.8829 19.4824 39.7814 20.4267 38.3682C21.371 36.9549 21.875 35.2934 21.875 33.5938C21.8724 31.3153 20.9662 29.131 19.3551 27.5199C17.744 25.9088 15.5597 25.0026 13.2813 25ZM13.2813 39.0625C12.1996 39.0625 11.1423 38.7418 10.243 38.1409C9.34365 37.5399 8.6427 36.6858 8.22879 35.6866C7.81487 34.6873 7.70657 33.5877 7.91758 32.5269C8.1286 31.466 8.64945 30.4916 9.41426 29.7268C10.1791 28.9619 11.1535 28.4411 12.2144 28.2301C13.2752 28.0191 14.3748 28.1274 15.3741 28.5413C16.3733 28.9552 17.2274 29.6561 17.8284 30.5555C18.4293 31.4548 18.75 32.5121 18.75 33.5938C18.75 35.0442 18.1738 36.4352 17.1482 37.4607C16.1227 38.4863 14.7317 39.0625 13.2813 39.0625Z" fill="#3C96E1"/>
</svg>

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
                                    <select name="sort" class="custom-select-filter">
                                        <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Newest First
                                        </option>
                                        <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Oldest First
                                        </option>
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
                                            box-shadow: 0 2px 8px 0 rgba(60, 150, 225, 0.08);
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

                <!-- NO RESIDENT ACCOUNT AND SEARCH FOUND RECORD -->
                <?php if (empty($residentUsers)): ?>
                    <div class="text-center py-12">
                        <?php if (!empty($searchQuery)): ?>
                            <div class="flex justify-center py-8">
                                <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M53.6665 29.2372L73.5581 34.533M49.4081 45.0538L59.3498 47.7038M49.904 74.858L53.879 75.9205C65.129 78.9205 70.754 80.4163 75.1873 77.8705C79.6165 75.3288 81.1248 69.733 84.1373 58.5497L88.3998 42.7288C91.4165 31.5413 92.9206 25.9497 90.3623 21.5413C87.804 17.133 82.1831 15.6372 70.929 12.6413L66.954 11.5788C55.704 8.57882 50.079 7.08299 45.6498 9.62882C41.2165 12.1705 39.7081 17.7663 36.6915 28.9497L32.4331 44.7705C29.4165 55.958 27.9081 61.5497 30.4706 65.958C33.029 70.3622 38.654 71.8622 49.904 74.858Z" stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round"/>
<path d="M50.0008 87.273L46.0341 88.3564C34.8091 91.4105 29.2008 92.9397 24.7758 90.3439C20.3591 87.7522 18.8508 82.048 15.8466 70.6439L11.5924 54.5105C8.58409 43.1064 7.07993 37.4022 9.63409 32.9106C11.8424 29.0231 16.6674 29.1647 22.9174 29.1647" stroke="black" stroke-opacity="0.7" stroke-width="1.5" stroke-linecap="round"/>
</svg>

                            </div>
                            <h3 class="text-xl font-medium text-gray-500 mb-4">No residents found matching your search.</h3>
                            <p class="mt-1 text-lg text-gray-500">No matches came up. Try another spelling or ID number to find
                                the resident you’re looking for.</p>
                        <?php else: ?>
                            <div class="flex justify-center py-8">
                                <!-- Your SECOND SVG here -->
                                <svg width="100" height="100" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M24.0625 26.25C24.0625 25.6698 24.293 25.1134 24.7032 24.7032C25.1134 24.293 25.6698 24.0625 26.25 24.0625H43.75C44.3302 24.0625 44.8866 24.293 45.2968 24.7032C45.707 25.1134 45.9375 25.6698 45.9375 26.25C45.9375 26.8302 45.707 27.3866 45.2968 27.7968C44.8866 28.207 44.3302 28.4375 43.75 28.4375H26.25C25.6698 28.4375 25.1134 28.207 24.7032 27.7968C24.293 27.3866 24.0625 26.8302 24.0625 26.25ZM26.25 37.1875H43.75C44.3302 37.1875 44.8866 36.957 45.2968 36.5468C45.707 36.1366 45.9375 35.5802 45.9375 35C45.9375 34.4198 45.707 33.8634 45.2968 33.4532C44.8866 33.043 44.3302 32.8125 43.75 32.8125H26.25C25.6698 32.8125 25.1134 33.043 24.7032 33.4532C24.293 33.8634 24.0625 34.4198 24.0625 35C24.0625 35.5802 24.293 36.1366 24.7032 36.5468C25.1134 36.957 25.6698 37.1875 26.25 37.1875ZM35 41.5625H26.25C25.6698 41.5625 25.1134 41.793 24.7032 42.2032C24.293 42.6134 24.0625 43.1698 24.0625 43.75C24.0625 44.3302 24.293 44.8866 24.7032 45.2968C25.1134 45.707 25.6698 45.9375 26.25 45.9375H35C35.5802 45.9375 36.1366 45.707 36.5468 45.2968C36.957 44.8866 37.1875 44.3302 37.1875 43.75C37.1875 43.1698 36.957 42.6134 36.5468 42.2032C36.1366 41.793 35.5802 41.5625 35 41.5625ZM61.25 13.125V42.8449C61.2518 43.4197 61.1394 43.989 60.9193 44.52C60.6991 45.0509 60.3756 45.5327 59.9676 45.9375L45.9375 59.9676C45.5327 60.3756 45.0509 60.6991 44.52 60.9193C43.989 61.1394 43.4197 61.2518 42.8449 61.25H13.125C11.9647 61.25 10.8519 60.7891 10.0314 59.9686C9.21094 59.1481 8.75 58.0353 8.75 56.875V13.125C8.75 11.9647 9.21094 10.8519 10.0314 10.0314C10.8519 9.21094 11.9647 8.75 13.125 8.75H56.875C58.0353 8.75 59.1481 9.21094 59.9686 10.0314C60.7891 10.8519 61.25 11.9647 61.25 13.125ZM13.125 56.875H41.5625V43.75C41.5625 43.1698 41.793 42.6134 42.2032 42.2032C42.6134 41.793 43.1698 41.5625 43.75 41.5625H56.875V13.125H13.125V56.875ZM45.9375 45.9375V53.7852L53.7824 45.9375H45.9375Z"
                                        fill="black" fill-opacity="0.3" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-gray-500 mb-4">No resident records accounts found.</h3>
                            <p class="mt-1 text-lg text-gray-500">No records found. Please try another spelling or account ID number to locate the account you’re looking for.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="py-3 text-left text-lg font-semibold text-gray-600">
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
                                        <td class="py-3 border-b border-gray-200">
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
                                                class="px-4 py-2 inline-flex text-base leading-5 font-medium items-center rounded-full bg-status-green text-status-green">
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
                                                class="px-7 py-3 inline-flex bg-blue-600 text-white rounded-full hover:bg-blue-700 transition font-medium text-base">
                                                <svg class="mr-1" style="width:1.5em; height:1.5em;" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14C13.1046 14 14 13.1046 14 12ZM16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" fill="white" />
            <path d="M12 3C16.4111 3 18.9532 5.23875 20.3477 7.46973C21.034 8.56793 21.4421 9.65839 21.6787 10.4697C21.7975 10.8772 21.8749 11.2197 21.9229 11.4639C21.9468 11.5859 21.9635 11.684 21.9746 11.7539C21.9801 11.7886 21.9844 11.8165 21.9873 11.8369C21.9887 11.8471 21.9894 11.8559 21.9902 11.8623C21.9907 11.8655 21.9909 11.8688 21.9912 11.8711L21.9922 11.874V11.875L20.0078 12.125V12.126C20.0077 12.1248 20.0075 12.1218 20.0068 12.1172C20.0055 12.1074 20.0028 12.09 19.999 12.0664C19.9915 12.0192 19.9789 11.9451 19.96 11.8486C19.922 11.6553 19.8586 11.3725 19.7588 11.0303C19.558 10.3417 19.2158 9.43184 18.6523 8.53027C17.5468 6.76136 15.5886 5 12 5C8.41136 5 6.45322 6.76136 5.34766 8.53027C4.78423 9.43184 4.44204 10.3417 4.24121 11.0303C4.14141 11.3725 4.07802 11.6553 4.04004 11.8486C4.02109 11.9451 4.00845 12.0192 4.00098 12.0664C3.99724 12.09 3.99454 12.1074 3.99316 12.1172L3.99219 12.126V12.125L2.00781 11.875V11.874L2.00879 11.8711C2.00908 11.8688 2.00934 11.8655 2.00977 11.8623C2.01062 11.8559 2.01126 11.8471 2.0127 11.8369C2.01558 11.8165 2.01989 11.7886 2.02539 11.7539C2.03646 11.684 2.05319 11.5859 2.07715 11.4639C2.1251 11.2197 2.20246 10.8772 2.32129 10.4697C2.55795 9.65839 2.96597 8.56793 3.65234 7.46973C5.04682 5.23875 7.58887 3 12 3Z" fill="white" />
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
                                <a href="<?= $prevUrl ?>"
                                    class="pagination-button" style="margin: 0 4px;">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span
                                    class="pagination-button disabled" style="margin: 0 4px;">
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
                                    <span class="pagination-button active" style="font-size: 1.1rem;"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= $pageUrl ?>" class="pagination-button" style="font-size: 1.1rem;"><?= $i ?></a>
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
                                <a href="<?= $nextUrl ?>"
                                    class="pagination-button" style="margin: 0 4px;">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span
                                    class="pagination-button disabled" style="margin: 0 4px;">
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
                                <span class="detail-label">Contact No:</span>
                                <span class="detail-value" id="residentContact">N/A</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="flex justify-end">
                    <button type="button" onclick="closeResidentDetailsModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition font-medium">
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

            const tabPanel = document.getElementById(tabId);
            if (!tabPanel) {
                return;
            }
            tabPanel.classList.remove('hidden');

            document.querySelectorAll('#dashboardTabs button').forEach(tabBtn => {
                tabBtn.classList.remove('active');
            });

            const activeTabBtn = document.querySelector(`#dashboardTabs button[data-tabs-target="#${tabId}"]`);
            if (activeTabBtn) {
                activeTabBtn.classList.add('active');
            }

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
                    const targetSelector = this.getAttribute('data-tabs-target');
                    if (!targetSelector) {
                        return;
                    }

                    const targetTab = targetSelector.replace('#', '');
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
                'successModal', 'errorModal', 'residentDetailsModal', 'activityLogsModal'];

            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    if (modalId === 'activityLogsModal') {
                        closeActivityLogsModal();
                    } else {
                        closeModal(modalId);
                    }
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
        .cht-loader-bounce.warmblue-dot {
            width: 22px; height: 22px; border-radius: 50%;
            background: linear-gradient(135deg, #e0e7ff 0%, #60a5fa 60%, #2563eb 100%);
            animation: cht-bounce 1.1s infinite cubic-bezier(.68,-0.55,.27,1.55);
        }
        .cht-loader-bounce.warmblue-dot:nth-child(2) {
            animation-delay: 0.2s;
            background: linear-gradient(135deg, #dbeafe 0%, #60a5fa 60%, #2563eb 100%);
        }
        .cht-loader-bounce.warmblue-dot:nth-child(3) {
            animation-delay: 0.4s;
            background: linear-gradient(135deg, #bfdbfe 0%, #60a5fa 60%, #2563eb 100%);
        }
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
            // 1. Patient Records Trend (Line Chart with three datasets)
            try {
                const patientRegCtx = patientRegistrationCanvas.getContext('2d');
                let patientData = <?= json_encode($analytics['patient_registration_trend']) ?>;
                let staffData = <?= json_encode($analytics['staff_registration_trend']) ?>;
                let residentData = <?= json_encode($analytics['resident_accounts_trend']) ?>;
                
                // Merge months for all datasets
                let allMonths = Array.from(new Set([
                    ...patientData.map(item => item.month),
                    ...staffData.map(item => item.month),
                    ...residentData.map(item => item.month)
                ])).sort();
                
                const patientLabels = allMonths.map(month => {
                    const date = new Date(month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
                });
                
                const staffValues = allMonths.map(month => {
                    const found = staffData.find(item => item.month === month);
                    return found ? found.count : 0;
                });
                
                const residentValues = allMonths.map(month => {
                    const found = residentData.find(item => item.month === month);
                    return found ? found.count : 0;
                });
                
                const patientValues = allMonths.map(month => {
                    const found = patientData.find(item => item.month === month);
                    return found ? found.count : 0;
                });
                
                new Chart(patientRegCtx, {
                    type: 'line',
                    data: {
                        labels: patientLabels,
                        datasets: [
                            {
                                label: 'Active Staff (created)',
                                data: staffValues,
                                borderColor: '#2563EB',
                                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#2563EB',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#2563EB'
                            },
                            {
                                label: 'Resident Accounts (created)',
                                data: residentValues,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#10B981',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#10B981'
                            },
                            {
                                label: 'Patient Records (created)',
                                data: patientValues,
                                borderColor: '#A855F7',
                                backgroundColor: 'rgba(168, 85, 247, 0.05)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#A855F7',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#A855F7'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    color: '#374151',
                                    font: { size: 13, weight: '500', family: 'Segoe UI, Arial' },
                                    padding: 16,
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: '#fff',
                                titleColor: '#1F2937',
                                bodyColor: '#374151',
                                borderColor: '#E5E7EB',
                                borderWidth: 2,
                                padding: 12,
                                caretSize: 0,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed.y;
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(229, 231, 235, 0.4)',
                                    drawBorder: true
                                },
                                ticks: {
                                    color: '#6B7280',
                                    font: { size: 12 },
                                    stepSize: 5
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6B7280',
                                    font: { size: 12 }
                                }
                            }
                        }
                    }
                });
            } catch (error) { console.error('Error initializing patient registration/consultation chart:', error); }
            // 2. Health Issues Breakdown (Bar)
            // Announcement Response (Table)
            // Only use AJAX for filter changes, not for initial load
            function fetchAnnouncementResponses(filter = {}) {
                const loader = document.getElementById('announcementResponseLoader');
                const tableDiv = document.getElementById('announcementResponseTable');
                loader.style.display = '';
                tableDiv.style.display = 'none';
                // Build query params
                let params = ['all_responses=1'];
                if (filter.time) params.push('time=' + encodeURIComponent(filter.time));
                if (filter.date) params.push('date=' + encodeURIComponent(filter.date));
                fetch('/community-health-tracker/api/announcements.php?' + params.join('&'))
                    .then(res => res.json())
                    .then(data => {
                        loader.style.display = 'none';
                        tableDiv.style.display = '';
                        if (!data.announcements || !data.announcements.length) {
                            tableDiv.innerHTML = '<div class="text-gray-500">No announcement response data available.</div>';
                            return;
                        }
                        // Summarize counts by category and response type
                        let summary = {
                            'All Users': {accepted: 0, dismissed: 0, total: 0, count: 0},
                            'Specific Users': {accepted: 0, dismissed: 0, total: 0, count: 0}
                        };
                        data.announcements.forEach(a => {
                            let cat = a.audience_type === 'public' ? 'All Users' : 'Specific Users';
                            summary[cat].accepted += a.response_counts.accepted;
                            summary[cat].dismissed += a.response_counts.dismissed;
                            summary[cat].total += a.response_counts.total;
                            summary[cat].count++;
                        });
                        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                        Object.keys(summary).forEach(cat => {
                            html += `<div class='bg-white rounded shadow p-4'>` +
                                `<h4 class='font-semibold text-blue-700 mb-2 text-lg'>${cat}</h4>` +
                                `<div class='mb-2 text-gray-700'>Total Announcements: <span class='font-bold'>${summary[cat].count}</span></div>` +
                                `<div class='mb-2 text-green-700'>Accepted: <span class='font-bold'>${summary[cat].accepted}</span></div>` +
                                `<div class='mb-2 text-red-700'>Dismissed: <span class='font-bold'>${summary[cat].dismissed}</span></div>` +
                                `<div class='mb-2 text-gray-700'>Total Responses: <span class='font-bold'>${summary[cat].total}</span></div>` +
                                `</div>`;
                        });
                        html += '</div>';
                        tableDiv.innerHTML = html;
                    })
                    .catch((err) => {
                        loader.style.display = 'none';
                        tableDiv.style.display = '';
                        tableDiv.innerHTML = '<div class="text-red-500">Failed to load announcement response data.</div>';
                    });
            }

            // Filter controls event listeners
            document.addEventListener('DOMContentLoaded', function() {
                const filterBtn = document.getElementById('announcementFilterBtn');
                const timeFilter = document.getElementById('announcementTimeFilter');
                const dateInput = document.getElementById('announcementDateInput');
                // Set default date to today
                dateInput.valueAsDate = new Date();
                filterBtn.addEventListener('click', function() {
                    fetchAnnouncementResponses({
                        time: timeFilter.value,
                        date: dateInput.value
                    });
                });
                // Do NOT call fetchAnnouncementResponses on initial load (PHP handles it)
            });
            // 3. Gender Distribution (Donut)
            try {
                const genderDistributionCtx = genderDistributionCanvas.getContext('2d');
                let genderData = <?= json_encode($analytics['gender_distribution']) ?>;
                // Sort by count descending
                genderData = genderData.sort((a, b) => b.count - a.count);
                const genderLabels = genderData.map(item => item.gender || 'Unknown');
                const genderValues = genderData.map(item => item.count);
                // Create gradient for area fill
                const gradient = genderDistributionCtx.createLinearGradient(0, 0, genderDistributionCtx.canvas.width, 0);
                gradient.addColorStop(0, '#42a5f5');
                gradient.addColorStop(1, '#42a5f5');
                new Chart(genderDistributionCtx, {
                    type: 'line',
                    data: {
                        labels: genderLabels,
                        datasets: [{
                            label: 'Gender Distribution',
                            data: genderValues,
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: '#42a5f5',
                            borderWidth: 3,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#42a5f5',
                            pointRadius: 8,
                            pointHoverRadius: 12,
                            tension: 0.45
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: '#fff',
                                titleColor: '#42a5f5',
                                bodyColor: '#6366f1',
                                borderColor: '#42a5f5',
                                borderWidth: 2,
                                padding: 16,
                                caretSize: 8,
                                displayColors: false
                            },
                            datalabels: {
                                display: true,
                                color: '#42a5f5',
                                font: { size: 32, weight: 'bold', family: 'Segoe UI, Arial' },
                                align: 'end',
                                anchor: 'end',
                                offset: 8,
                                backgroundColor: 'rgba(255,255,255,0.85)',
                                borderRadius: 8,
                                borderWidth: 2,
                                borderColor: '#42a5f5',
                                padding: 8,
                                formatter: (value, ctx) => value
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: '#64748b',
                                    font: { size: 16, weight: 'bold' }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(66,165,245,0.08)' },
                                ticks: { display: false }
                            }
                        },
                        animation: {
                            duration: 1200,
                            easing: 'easeInOutQuart'
                        }
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
                    wrap.innerHTML =
                        `<div class="text-center py-12 rounded-lg">
                        <div class="flex justify-center py-8">
                            <svg width="100" height="100" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M52.5 8.75H8.75V56.875H37.1875L39.375 61.25H4.375V4.375H56.875V37.1875L52.5 32.8125V8.75Z" fill="black" fill-opacity="0.3"/>
                                <path d="M13.125 21.875H48.1206V17.5H13.125V21.875ZM13.125 35V30.625H39.375L37.1875 35H13.125ZM13.125 48.125V43.75H24.0625V48.125H13.125ZM46.7556 63.56L46.6069 63.805C46.5247 63.9026 46.4223 63.981 46.3066 64.0348C46.191 64.0886 46.065 64.1165 45.9375 64.1165C45.81 64.1165 45.684 64.0886 45.5684 64.0348C45.4527 63.981 45.3503 63.9026 45.2681 63.805L45.1194 63.56L44.8175 62.7944L45.9375 63.2363L47.0531 62.7988L46.7556 63.56ZM45.1238 28.315C45.1875 28.1504 45.2995 28.0089 45.4452 27.9092C45.5908 27.8094 45.7632 27.756 45.9397 27.756C46.1162 27.756 46.2886 27.8094 46.4342 27.9092C46.5798 28.0089 46.6919 28.1504 46.7556 28.315L49.4156 35.0656C50.0756 36.7389 51.0725 38.2587 52.3444 39.5306C53.6163 40.8025 55.1361 41.7994 56.8094 42.4594L62.7988 44.8219L63.2363 45.9375L62.7988 47.0531L56.8094 49.4156L56.1838 49.6781C53.089 51.0885 50.6625 53.6452 49.4156 56.8094L47.0531 62.7988L45.9375 63.2363L44.8175 62.7988L42.4594 56.8094C41.2125 53.6452 38.786 51.0885 35.6912 49.6781L35.0656 49.4156L28.315 46.7556C28.1504 46.6919 28.0089 46.5798 27.9092 46.4342C27.8094 46.2886 27.756 46.1162 27.756 45.9397C27.756 45.7632 27.8094 45.5908 27.9092 45.4452C28.0089 45.2995 28.1504 45.1875 28.315 45.1238L29.0763 44.8175L35.0656 42.4594C38.2298 41.2125 40.7865 38.786 42.1969 35.6912L42.4594 35.0656L45.1238 28.315ZM45.9375 37.9925C44.2345 41.4421 41.4421 44.2345 37.9925 45.9375C41.4414 47.6394 44.2337 50.4301 45.9375 53.8781C47.6402 50.4308 50.4308 47.6402 53.8781 45.9375C50.4301 44.2337 47.6394 41.4414 45.9375 37.9925ZM63.56 45.1238C63.7246 45.1875 63.8661 45.2995 63.9658 45.4452C64.0656 45.5908 64.119 45.7632 64.119 45.9397C64.119 46.1162 64.0656 46.2886 63.9658 46.4342C63.8661 46.5798 63.7246 46.6919 63.56 46.7556L62.7944 47.0531L63.2363 45.9375L62.7988 44.8219L63.56 45.1238Z" fill="black" fill-opacity="0.3"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-500 mb-4">No generated reports yet</h3>
                        <p class="mt-1 text-lg text-gray-500">Report can be generated and display here</p>
                    </div>`;
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
                    let html = `<h3 class='text-xl font-sm text-gray-700 mb-4'>Generated Report Logs</h3>
                        <div class='overflow-x-auto mb-6'>
                                    <table class='min-w-full text-sm text-left'>
                                        <thead>
                                            <th class='py-3 text-lg font-semibold text-gray-600 border-b'>Date & Time</th>
                                            <th class='py-3 text-lg font-semibold text-gray-600 border-b'>Staff Name</th>
                                            <th class='py-3 text-lg font-semibold text-gray-600 border-b'>Type</th>
                                        </thead>
                                        <tbody>`;
                    for (const log of filteredLogs.slice(start, end)) {
                        html += `<tr>
                                    <td class='py-3 border-b font-normal text-gray-600 text-base whitespace-nowrap'>${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</td>
                                    <td class='py-3 border-b'>
                                        <span class="inline-block rounded-full" style="background:rgba(34,197,94,0.15); color:#15803d; backdrop-filter: blur(2px); padding: 0.5rem 1.25rem; font-weight:400; font-size:1rem;">${log.full_name || ''}</span>
                                    </td>
                                    <td class="py-3 border-b">
                                        ${log.export_type === 'bulk_patient_records' ? (log.action_type === 'export_bulk_pdf' ? `
                                        <span class="px-4 py-1 text-lg font-md rounded-md text-red-600 bg-red-200 inline-block" style="min-width:60px;text-align:center; ">
                                            PDF
                                        </span>`
                                :
                                `<span class="px-4 py-1 text-lg font-md rounded-md bg-green-200 text-green-600 inline-block" style="min-width:60px;text-align:center;">
                                            Excel
                                        </span>`
                            ) : (log.export_type || log.action_type)
                            }
                                    </td>
                                </tr>`;
                    }
                    html += '</tbody></table></div>';
                    // Pagination controls
                    const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
                    if (totalPages > 1) {
                        html += `<div class='flex items-center justify-center mt-2 gap-2'>`;

                        // Previous button
                        html += `<button style="font-size: 1.1rem; margin: 0 4px;"
                            class='mx-1 w-10 h-10 flex items-center justify-center rounded-full border border-blue-500 bg-white hover:bg-blue-100 transition ${page === 1 ? "opacity-50 cursor-not-allowed" : ""}'
                            onclick='window._renderReportLogsPage(${page - 1})'
                            ${page === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left text-base"></i>
                        </button>`;

                        for (let i = 1; i <= totalPages; i++) {
                            html += `
                                <button style="font-size: 1.1rem;"
                                    class='mx-0.5 w-10 h-10 flex items-center justify-center rounded-full border border-blue-500 text-sm font-medium transition ${i === page ? "bg-blue-500 text-white" : "bg-white text-blue-500 hover:bg-blue-100"}'
                                    onclick='window._renderReportLogsPage(${i})'>
                                    ${i}
                                </button>`;
                        }

                        // Next button
                        html += `<button style="font-size: 1.1rem; margin: 0 4px;"
                            class='mx-1 w-10 h-10 flex items-center justify-center rounded-full border border-blue-500 bg-white text-blue-500 hover:bg-blue-100 transition ${page === totalPages ? "opacity-50 cursor-not-allowed" : ""}'
                            onclick='window._renderReportLogsPage(${page + 1})'
                            ${page === totalPages ? 'disabled' : ''}>
                            <i class="fas fa-chevron-right text-base"></i>
                        </button>`;

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

    // ========== Activity Logs Tab Functions ==========
    let activityLogsLoaded = false;
    let currentActivityLogView = 'resident';
    let latestActivityLogData = { resident: [], admin: [] };
    let pendingActivityLogRequests = 0;
    const activityLogsPerPage = 10;
    let activityLogCurrentPage = { resident: 1, admin: 1 };

    function escapeActivityLogHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getActivityBadge(log) {
        const actionType = String(log.action_type || '').toLowerCase();
        const actionMap = {
            login: { label: 'Login', icon: 'fa-sign-in-alt', bg: 'rgba(29, 78, 216, 0.3)', text: '#1D4ED8' },
            logout: { label: 'Logout', icon: 'fa-sign-out-alt', bg: 'rgba(107, 114, 128, 0.3)', text: '#6B7280' },
            view_announcement: { label: 'Viewed Announcement', icon: 'fa-eye', bg: 'rgba(34, 197, 94, 0.3)', text: '#22C55E' },
            accept_announcement: { label: 'Accepted Announcement', icon: 'fa-check-circle', bg: 'rgba(54, 128, 61, 0.3)', text: '#36803D' },
            dismiss_announcement: { label: 'Dismissed Announcement', icon: 'fa-times-circle', bg: 'rgba(239, 68, 68, 0.3)', text: '#EF4444' },
            add_patient: { label: 'Added Patient', icon: 'fa-user-plus', bg: 'rgba(54, 128, 61, 0.3)', text: '#36803D' },
            view_patient: { label: 'Viewed Patient Record', icon: 'fa-eye', bg: 'rgba(34, 197, 94, 0.3)', text: '#22C55E' },
            update_patient: { label: 'Edited Patient Record', icon: 'fa-edit', bg: 'rgba(245, 158, 11, 0.3)', text: '#F59E0B' },
            edit_patient: { label: 'Edited Patient Record', icon: 'fa-edit', bg: 'rgba(245, 158, 11, 0.3)', text: '#F59E0B' },
            archive_patient: { label: 'Archived Patient Record', icon: 'fa-archive', bg: 'rgba(168, 85, 247, 0.3)', text: '#A855F7' },
            print_patient: { label: 'Printed Patient Record', icon: 'fa-print', bg: 'rgba(34, 197, 94, 0.3)', text: '#22C55E' },
            export_pdf: { label: 'Exported PDF', icon: 'fa-file-pdf', bg: 'rgba(239, 68, 68, 0.3)', text: '#EF4444' },
            export_excel: { label: 'Exported Excel', icon: 'fa-file-excel', bg: 'rgba(54, 128, 61, 0.3)', text: '#36803D' },
            send_announcement: { label: 'Posted Announcement', icon: 'fa-bullhorn', bg: 'rgba(168, 85, 247, 0.3)', text: '#A855F7' },
            edit_announcement: { label: 'Edited Announcement', icon: 'fa-edit', bg: 'rgba(245, 158, 11, 0.3)', text: '#F59E0B' },
            delete_announcement: { label: 'Archived or Deleted Announcement', icon: 'fa-trash', bg: 'rgba(239, 68, 68, 0.3)', text: '#EF4444' },
            search_announcement: { label: 'Searched Announcement', icon: 'fa-search', bg: 'rgba(29, 78, 216, 0.3)', text: '#1D4ED8' }
        };

        return actionMap[actionType] || {
            label: String(log.description || log.action_type || 'Unknown action'),
            icon: 'fa-info-circle',
            bg: 'rgba(107, 114, 128, 0.3)',
            text: '#6B7280'
        };
    }

    function renderActivityLogRows(logs, emptyLabel) {
        if (!Array.isArray(logs) || logs.length === 0) {
            return `<tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">${escapeActivityLogHtml(emptyLabel)}</td></tr>`;
        }

        return logs.map((log, index) => `
            <tr class="activity-log-row ${index % 2 === 0 ? 'bg-white' : 'bg-white'}">
                <td class="activity-log-time">${escapeActivityLogHtml(`${log.formatted_date || ''} ${log.formatted_time || ''}`.trim())}</td>
                <td class="activity-log-name">${escapeActivityLogHtml(log.actor_name || 'Unknown')}</td>
                <td class="activity-log-action-cell">
                    <span class="activity-log-badge"
                          style="background-color: ${getActivityBadge(log).bg}; color: ${getActivityBadge(log).text};">
                        <i class="fas ${getActivityBadge(log).icon} mr-1"></i>${escapeActivityLogHtml(getActivityBadge(log).label)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function setActivityLogSectionState(section, state) {
        const empty = document.getElementById(`${section}LogsEmpty`);
        const tableBody = document.getElementById(`${section}LogsTableBody`);
        const pagination = document.getElementById(`${section}LogsPagination`);

        if (empty) {
            empty.classList.toggle('hidden', state !== 'empty');
        }
        if (pagination) {
            pagination.classList.toggle('hidden', state !== 'table');
        }
        if (tableBody && (state === 'loading' || state === 'empty')) {
            tableBody.innerHTML = '';
        }
    }

    function updateActivityLogsLoadingState() {
        const loading = document.getElementById('dashboardActivityLogsLoading');
        if (loading) {
            loading.classList.toggle('active', pendingActivityLogRequests > 0);
        }
    }

    function openActivityLogsModal() {
        const modal = document.getElementById('activityLogsModal');
        const activityLogsTab = document.getElementById('activity-logs-tab');
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        if (activityLogsTab) {
            activityLogsTab.classList.add('active');
            activityLogsTab.setAttribute('aria-selected', 'true');
        }

        loadDashboardActivityLogs(true);
    }

    function closeActivityLogsModal() {
        const modal = document.getElementById('activityLogsModal');
        const activityLogsTab = document.getElementById('activity-logs-tab');
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('active');
        document.body.style.overflow = '';

        if (activityLogsTab) {
            activityLogsTab.classList.remove('active');
            activityLogsTab.setAttribute('aria-selected', 'false');
        }
    }

    function showActivityLogPanel(section) {
        currentActivityLogView = section;

        const residentBtn = document.getElementById('staffResidentTabBtn');
        const adminBtn = document.getElementById('staffAdminTabBtn');
        const residentPanel = document.getElementById('staffResidentLogsSection');
        const adminPanel = document.getElementById('staffAdminLogsSection');

        if (residentBtn && adminBtn) {
            residentBtn.classList.toggle('log-tab-active', section === 'resident');
            adminBtn.classList.toggle('log-tab-active', section === 'admin');
        }

        if (residentPanel && adminPanel) {
            residentPanel.classList.toggle('hidden', section !== 'resident');
            adminPanel.classList.toggle('hidden', section !== 'admin');
        }
    }

    function renderActivityLogPagination(section) {
        const logs = latestActivityLogData[section] || [];
        const pageInfo = document.getElementById(`${section}LogsPageInfo`);
        const prevBtn = document.getElementById(`${section}LogsPrevBtn`);
        const nextBtn = document.getElementById(`${section}LogsNextBtn`);
        const totalPages = Math.max(1, Math.ceil(logs.length / activityLogsPerPage));
        const currentPage = Math.min(activityLogCurrentPage[section], totalPages);
        activityLogCurrentPage[section] = currentPage;

        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
    }

    function renderActivityLogSection(section) {
        const logs = latestActivityLogData[section] || [];
        const tableBody = document.getElementById(`${section}LogsTableBody`);
        const totalPages = Math.max(1, Math.ceil(logs.length / activityLogsPerPage));

        activityLogCurrentPage[section] = Math.min(activityLogCurrentPage[section], totalPages);

        if (!logs.length) {
            setActivityLogSectionState(section, 'empty');
            renderActivityLogPagination(section);
            return;
        }

        const startIndex = (activityLogCurrentPage[section] - 1) * activityLogsPerPage;
        const paginatedLogs = logs.slice(startIndex, startIndex + activityLogsPerPage);

        if (tableBody) {
            tableBody.innerHTML = renderActivityLogRows(
                paginatedLogs,
                `No ${section} logs found.`
            );
        }

        setActivityLogSectionState(section, 'table');
        renderActivityLogPagination(section);
    }

    function loadActivityLogSection(section) {
        const tableBody = document.getElementById(`${section}LogsTableBody`);
        const sectionLabel = section === 'resident' ? 'resident' : 'admin';

        setActivityLogSectionState(section, 'loading');
        pendingActivityLogRequests += 1;
        updateActivityLogsLoadingState();

        const apiUrl = `/community-health-tracker/api/get_activity_logs.php?category=${sectionLabel}&limit=50`;

        return fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load logs');
                }

                if (!data.data || data.data.length === 0) {
                    latestActivityLogData[section] = [];
                    activityLogCurrentPage[section] = 1;
                    setActivityLogSectionState(section, 'empty');
                    if (tableBody) {
                        tableBody.innerHTML = '';
                    }
                    renderActivityLogPagination(section);
                    return;
                }

                latestActivityLogData[section] = data.data;
                activityLogCurrentPage[section] = 1;
                renderActivityLogSection(section);
            })
            .catch(error => {
                console.error(`Error loading ${sectionLabel} logs:`, error);
                setActivityLogSectionState(section, 'empty');
                const empty = document.getElementById(`${section}LogsEmpty`);
                if (empty) {
                    empty.textContent = `Error loading ${sectionLabel} logs. Please try again.`;
                }
                renderActivityLogPagination(section);
            })
            .finally(() => {
                pendingActivityLogRequests = Math.max(0, pendingActivityLogRequests - 1);
                updateActivityLogsLoadingState();
            });
    }

    function loadDashboardActivityLogs(forceReload = false) {
        if (activityLogsLoaded && !forceReload) {
            return;
        }

        activityLogsLoaded = true;
        setActivityLogSectionState('resident', 'loading');
        setActivityLogSectionState('admin', 'loading');
        loadActivityLogSection('resident');
        loadActivityLogSection('admin');
    }

    function exportCurrentActivityLogs() {
        const currentLogs = latestActivityLogData[currentActivityLogView] || [];
        if (!currentLogs.length) {
            return;
        }

        const header = ['Time Log', currentActivityLogView === 'resident' ? 'Resident' : 'Admin Name', 'Action Performed', 'Details', 'IP Address'];
        const rows = currentLogs.map(log => [
            `${log.formatted_date || ''} ${log.formatted_time || ''}`.trim(),
            log.actor_name || 'Unknown',
            getActivityBadge(log).label,
            log.description || '',
            log.ip_address || 'N/A'
        ]);

        const csv = [header, ...rows]
            .map(row => row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(','))
            .join('\r\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${currentActivityLogView}-activity-logs.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const residentTabBtn = document.getElementById('staffResidentTabBtn');
        const adminTabBtn = document.getElementById('staffAdminTabBtn');
        const exportBtn = document.getElementById('staffExportLogsBtn');
        const residentPrevBtn = document.getElementById('residentLogsPrevBtn');
        const residentNextBtn = document.getElementById('residentLogsNextBtn');
        const adminPrevBtn = document.getElementById('adminLogsPrevBtn');
        const adminNextBtn = document.getElementById('adminLogsNextBtn');

        showActivityLogPanel('resident');

        if (residentTabBtn) {
            residentTabBtn.addEventListener('click', function () {
                showActivityLogPanel('resident');
            });
        }

        if (adminTabBtn) {
            adminTabBtn.addEventListener('click', function () {
                showActivityLogPanel('admin');
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', exportCurrentActivityLogs);
        }

        if (residentPrevBtn) {
            residentPrevBtn.addEventListener('click', function () {
                if (activityLogCurrentPage.resident > 1) {
                    activityLogCurrentPage.resident -= 1;
                    renderActivityLogSection('resident');
                }
            });
        }

        if (residentNextBtn) {
            residentNextBtn.addEventListener('click', function () {
                const totalPages = Math.ceil((latestActivityLogData.resident || []).length / activityLogsPerPage);
                if (activityLogCurrentPage.resident < totalPages) {
                    activityLogCurrentPage.resident += 1;
                    renderActivityLogSection('resident');
                }
            });
        }

        if (adminPrevBtn) {
            adminPrevBtn.addEventListener('click', function () {
                if (activityLogCurrentPage.admin > 1) {
                    activityLogCurrentPage.admin -= 1;
                    renderActivityLogSection('admin');
                }
            });
        }

        if (adminNextBtn) {
            adminNextBtn.addEventListener('click', function () {
                const totalPages = Math.ceil((latestActivityLogData.admin || []).length / activityLogsPerPage);
                if (activityLogCurrentPage.admin < totalPages) {
                    activityLogCurrentPage.admin += 1;
                    renderActivityLogSection('admin');
                }
            });
        }
    });
</script>
<style>
    .activity-logs-modal-shell {
        max-width: 900px;
        width: calc(100% - 2rem);
        padding: 0;
        border-radius: 0.9rem;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
    }

    .activity-logs-modal-card {
        padding: 1.75rem 2rem 1.5rem;
        background: #ffffff;
    }

    .activity-logs-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .activity-logs-close-btn {
        width: 2.25rem;
        height: 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 9999px;
        background: transparent;
        color: #6b7280;
        font-size: 1.15rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .activity-logs-close-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .activity-logs-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.15rem;
        flex-wrap: wrap;
    }

    .activity-logs-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .activity-logs-search-input {
        width: 20rem;
        max-width: 100%;
        padding: 0.75rem 0.95rem;
        border: 1px solid #d1d5db;
        border-radius: 0.75rem;
        background: #ffffff;
        color: #111827;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .activity-logs-search-input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
    }

    .log-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.8rem 1.45rem;
        border-radius: 0.45rem;
        border: 1px solid #cfe0f5;
        background: #dceafb;
        color: #3b82f6;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .log-tab:hover {
        border-color: #93c5fd;
        color: #2563eb;
        background: #cfe3fb;
    }

    .log-tab-active {
        background: linear-gradient(180deg, #4da0eb 0%, #3a8fe0 100%);
        border-color: #4b97e3;
        color: #ffffff;
        box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
    }

    .activity-logs-action-btn {
        min-width: 7rem;
    }

    .activity-logs-wrapper {
        border: 1px solid #edf2f7;
        background: #ffffff;
        min-height: 24rem;
        border-radius: 0.85rem;
        max-height: 24rem;
        overflow-y: auto;
        overflow-x: auto;
    }

    .activity-logs-wrapper::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .activity-logs-wrapper::-webkit-scrollbar-thumb {
        background: #c7cdd8;
        border-radius: 9999px;
    }

    .activity-logs-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .activity-log-panel {
        transition: opacity 0.2s ease, transform 0.2s ease;
        min-height: 27rem;
    }

    .activity-log-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        background: #fafafa;
        padding: 2rem 1rem;
    }

    .activity-log-empty.hidden {
        display: none !important;
    }

    .activity-logs-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 0.25rem;
    }

    .activity-logs-page-info {
        color: #6b7280;
        font-size: 0.9rem;
    }

    .activity-logs-page-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .activity-logs-page-btn {
        padding: 0.55rem 0.9rem;
        border: 1px solid #d1d5db;
        border-radius: 0.65rem;
        background: #ffffff;
        color: #374151;
        font-size: 0.9rem;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .activity-logs-page-btn:hover:not(:disabled) {
        background: #f9fafb;
        border-color: #93c5fd;
        color: #2563eb;
    }

    .activity-logs-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .activity-logs-loading {
        display: none;
        align-items: center;
        gap: 0.85rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }

    .activity-logs-loading.active {
        display: flex;
    }

    #activityLogsModal .patient-table th {
        background: #ffffff;
        color: #111827;
        font-size: 0.92rem;
        font-weight: 700;
        letter-spacing: 0;
        border-bottom: 1px solid #dbe5f0;
    }

    #activityLogsModal .patient-table th,
    #activityLogsModal .patient-table td {
        padding: 1rem 1.1rem;
        vertical-align: top;
    }

    #activityLogsModal .patient-table tr:last-child td {
        border-bottom: none;
    }

    .activity-log-row td {
        border-bottom: 1px solid #e5edf5;
    }

    .activity-log-time {
        color: #667085;
        font-size: 0.95rem;
        white-space: nowrap;
    }

    .activity-log-name {
        color: #2d3748;
        font-weight: 600;
        font-size: 0.98rem;
    }

    .activity-log-action-cell {
        text-align: left;
    }

    .activity-log-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 5.8rem;
        padding: 0.6rem 1rem;
        border-radius: 0.4rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .activity-logs-modal-shell {
            width: calc(100% - 1rem);
        }

        .activity-logs-modal-card {
            padding: 1rem;
        }

        .activity-logs-modal-header {
            padding-bottom: 0.85rem;
        }

        .activity-logs-toolbar {
            align-items: stretch;
        }

        .activity-logs-pagination {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .activity-logs-spinner {
        width: 1rem;
        height: 1rem;
        border-radius: 9999px;
        border: 2px solid #dbeafe;
        border-top-color: #2563eb;
        animation: activity-spin 0.7s linear infinite;
    }

    .activity-logs-loading-text {
        font-size: 0.95rem;
        font-weight: 500;
    }

    @keyframes activity-spin {
        to {
            transform: rotate(360deg);
        }
    }

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
